<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN USERS
|--------------------------------------------------------------------------
| File: admin/users.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {

    header('Location: ../index.php');
    exit;
}


$adminId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| UPDATE USER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_user'])
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: users.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | FORM DATA
    |--------------------------------------------------------------------------
    */

    $userId =
        (int) (
            $_POST['user_id']
            ?? 0
        );


    $role =
        $_POST['role']
        ?? '';


    $status =
        $_POST['status']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $userId <= 0 ||

        !in_array(
            $role,
            [
                'customer',
                'vendor',
                'admin'
            ],
            true
        ) ||

        !in_array(
            $status,
            [
                'active',
                'inactive',
                'pending',
                'suspended'
            ],
            true
        )
    ) {

        header(
            'Location: users.php?error=invalid'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | PREVENT SELF MODIFICATION
    |--------------------------------------------------------------------------
    */

    if ($userId === $adminId) {

        header(
            'Location: users.php?error=self'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DATABASE
    |--------------------------------------------------------------------------
    */

    try {

        $db->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | CHECK USER
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    user_id,
                    role,
                    status
                FROM users
                WHERE user_id = ?
                LIMIT 1
            ");


        $stmt->execute([
            $userId
        ]);


        $existingUser =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$existingUser) {

            $db->rollBack();

            header(
                'Location: users.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE USER
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                UPDATE users
                SET
                    role = ?,
                    status = ?
                WHERE user_id = ?
            ");


        $stmt->execute([

            $role,
            $status,
            $userId

        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        $stmt->execute([

            $adminId,

            "Updated user #{$userId} role to {$role} and status to {$status}",

            'user',

            $userId

        ]);


        $db->commit();


        header(
            'Location: users.php?success=updated'
        );

        exit;

    }

    catch (Throwable $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }


        error_log(
            $e->getMessage()
        );


        header(
            'Location: users.php?error=update'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$roleFilter =
    $_GET['role']
    ?? '';


$statusFilter =
    $_GET['status']
    ?? '';


/*
|--------------------------------------------------------------------------
| USERS QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        u.user_id,
        u.name,
        u.email,
        u.phone,
        u.role,
        u.status,
        u.mfa_enabled,
        u.created_at,

        v.vendor_id,
        v.business_name,
        v.approval_status

    FROM users u

    LEFT JOIN vendors v
        ON u.user_id = v.user_id

    WHERE 1 = 1
";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND
        (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )
    ";


    $searchValue =
        '%' .
        $search .
        '%';


    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;
}


/*
|--------------------------------------------------------------------------
| ROLE FILTER
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $roleFilter,
        [
            'customer',
            'vendor',
            'admin'
        ],
        true
    )
) {

    $sql .= "
        AND u.role = ?
    ";


    $params[] =
        $roleFilter;
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $statusFilter,
        [
            'active',
            'inactive',
            'pending',
            'suspended'
        ],
        true
    )
) {

    $sql .= "
        AND u.status = ?
    ";


    $params[] =
        $statusFilter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        u.created_at DESC,
        u.user_id DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$users = [];


try {

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $users =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $users = [];

    error_log(
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| USER STATISTICS
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$totalCustomers = 0;
$totalVendors = 0;
$totalAdmins = 0;


try {

    $totalUsers =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM users
            ")
            ->fetchColumn();


    $totalCustomers =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'customer'
            ")
            ->fetchColumn();


    $totalVendors =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'vendor'
            ")
            ->fetchColumn();


    $totalAdmins =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'admin'
            ")
            ->fetchColumn();

}

catch (Throwable $e) {

    error_log(
        $e->getMessage()
    );
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Users | HochipoHub Admin
    </title>


    <!-- ============================================================
         FONT
    ============================================================= -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         ADMIN CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | ROOT
        |--------------------------------------------------------------------------
        */

        :root {

            --users-blue:
                #2563eb;

            --users-blue-dark:
                #1647a8;

            --users-navy:
                #09275b;

            --users-bg:
                #eef5fd;

            --users-white:
                #ffffff;

            --users-text:
                #0b2d63;

            --users-muted:
                #8294b3;

            --users-border:
                #dbe5f1;

        }


        /*
        |--------------------------------------------------------------------------
        | GLOBAL
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing: border-box;

        }


        html,
        body {

            margin: 0;

            padding: 0;

            min-height: 100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        body {

            overflow-x: hidden;

        }


        button,
        input,
        select {

            font-family: inherit;

        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN WRAPPER
        |--------------------------------------------------------------------------
        */

        .admin-wrapper {

            min-height: 100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .users-main {

            min-height: 100vh;

            margin-left: 260px;

            width:
                calc(
                    100% - 260px
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .users-content {

            width: 100%;

            max-width: 1450px;

            margin:
                0
                auto;

            padding:

                38px
                35px
                70px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .users-hero {

            position: relative;

            min-height: 155px;

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:

                34px
                38px;

            margin-bottom: 26px;

            color: #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius: 26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .users-hero::before {

            content: "";

            position: absolute;

            width: 260px;
            height: 260px;

            right: -70px;
            top: -140px;

            border-radius: 50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .users-hero::after {

            content: "";

            position: absolute;

            width: 170px;
            height: 170px;

            right: 155px;
            bottom: -110px;

            border-radius: 50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .users-hero-text {

            position: relative;

            z-index: 2;

        }


        .users-hero h1 {

            margin:

                0
                0
                8px;

            color: #ffffff;

            font-size: 38px;

            line-height: 1.05;

            font-weight: 800;

            letter-spacing: -1.5px;

        }


        .users-hero p {

            margin: 0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size: 14px;

            font-weight: 500;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO ICON
        |--------------------------------------------------------------------------
        */

        .users-hero-icon {

            position: relative;

            z-index: 2;

            width: 82px;
            height: 82px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border:

                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .25
                );

            border-radius: 22px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .14
                );

            color: #ffffff;

            font-size: 34px;

            font-weight: 800;

            box-shadow:

                inset
                0
                0
                20px
                rgba(
                    255,
                    255,
                    255,
                    .06
                );

        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .users-alert {

            margin-bottom: 22px;

            padding:

                14px
                17px;

            border-radius: 12px;

            font-size: 11px;

            font-weight: 600;

        }


        .users-alert.success {

            color: #166534;

            background: #ecfdf5;

            border:

                1px solid
                #bbf7d0;

        }


        .users-alert.error {

            color: #991b1b;

            background: #fff1f2;

            border:

                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | STATISTICS
        |--------------------------------------------------------------------------
        */

        .users-stats {

            display: grid;

            grid-template-columns:

                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 18px;

            margin-bottom: 30px;

        }


        .user-stat-card {

            position: relative;

            min-height: 150px;

            overflow: hidden;

            padding:

                26px
                24px;

            background: #ffffff;

            border:

                1px solid
                #dce7f3;

            border-top:

                4px solid
                #2563eb;

            border-radius: 20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .user-stat-card::after {

            content: "";

            position: absolute;

            right: -29px;
            bottom: -45px;

            width: 110px;
            height: 110px;

            border-radius: 50%;

            background: #edf4ff;

        }


        .user-stat-card.customers {

            border-top-color: #16a34a;

        }


        .user-stat-card.customers::after {

            background: #eaf9ef;

        }


        .user-stat-card.vendors {

            border-top-color: #f59e0b;

        }


        .user-stat-card.vendors::after {

            background: #fff7df;

        }


        .user-stat-card.admins {

            border-top-color: #8b5cf6;

        }


        .user-stat-card.admins::after {

            background: #f4efff;

        }


        .user-stat-label {

            position: relative;

            z-index: 2;

            display: block;

            margin-bottom: 15px;

            color: #61728e;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .75px;

            text-transform: uppercase;

        }


        .user-stat-value {

            position: relative;

            z-index: 2;

            display: block;

            color: #0b326d;

            font-size: 32px;

            line-height: 1;

            font-weight: 800;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN PANEL
        |--------------------------------------------------------------------------
        */

        .users-panel {

            overflow: hidden;

            background: #ffffff;

            border:

                1px solid
                #dce7f3;

            border-radius: 24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .users-panel-header {

            min-height: 115px;

            padding:

                27px
                30px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            border-bottom:

                1px solid
                #e7edf5;

        }


        .users-panel-title {

            display: flex;

            align-items: center;

            gap: 16px;

        }


        .users-panel-icon {

            width: 53px;
            height: 53px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 16px;

            color: #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size: 22px;

            font-weight: 800;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .users-panel-header h2 {

            margin:

                0
                0
                5px;

            color: #092e65;

            font-size: 20px;

            font-weight: 800;

        }


        .users-panel-header p {

            margin: 0;

            color: #8999b4;

            font-size: 11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT BADGE
        |--------------------------------------------------------------------------
        */

        .users-count-badge {

            min-height: 36px;

            padding:

                0
                16px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            color: #2563eb;

            background: #eff6ff;

            border:

                1px solid
                #d6e7ff;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .users-filter-wrapper {

            padding:

                22px
                28px;

            border-bottom:

                1px solid
                #edf1f6;

            background: #fbfdff;

        }


        .users-filter {

            display: grid;

            grid-template-columns:

                minmax(
                    260px,
                    1.7fr
                )

                minmax(
                    145px,
                    .6fr
                )

                minmax(
                    145px,
                    .6fr
                )

                auto

                auto;

            gap: 10px;

        }


        .users-filter input,
        .users-filter select {

            width: 100%;

            height: 43px;

            padding:

                0
                13px;

            outline: none;

            color: #26354e;

            background: #ffffff;

            border:

                1px solid
                #d8e3ef;

            border-radius: 10px;

            font-size: 10px;

        }


        .users-filter input::placeholder {

            color: #96a5b9;

        }


        .users-filter input:focus,
        .users-filter select:focus {

            border-color: #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | BUTTONS
        |--------------------------------------------------------------------------
        */

        .users-btn {

            min-height: 43px;

            padding:

                0
                17px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            font-size: 10px;

            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            white-space: nowrap;

        }


        .users-btn.primary {

            color: #ffffff;

            border: 0;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            box-shadow:

                0
                7px
                15px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

        }


        .users-btn.secondary {

            color: #66758b;

            border:

                1px solid
                #d7e2ee;

            background: #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .users-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .users-table {

            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;

        }


        .users-table thead {

            background: #f6f9fd;

        }


        .users-table th {

            height: 44px;

            padding:

                0
                18px;

            color: #65758f;

            border-bottom:

                1px solid
                #dfe7f0;

            font-size: 8px;

            font-weight: 800;

            text-align: left;

            letter-spacing: .55px;

            text-transform: uppercase;

            white-space: nowrap;

        }


        .users-table td {

            padding:

                16px
                18px;

            color: #435169;

            border-bottom:

                1px solid
                #edf1f6;

            font-size: 9px;

            vertical-align: middle;

        }


        .users-table tbody tr {

            transition:
                background
                .15s ease;

        }


        .users-table tbody tr:hover {

            background: #f9fbff;

        }


        .users-table tbody tr:last-child td {

            border-bottom: 0;

        }


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        .user-id {

            color: #8796ac;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | USER CELL
        |--------------------------------------------------------------------------
        */

        .user-cell {

            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 210px;

        }


        .user-avatar {

            width: 39px;
            height: 39px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            color: #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #60a5fa
                );

            font-size: 12px;

            font-weight: 800;

        }


        .user-cell-info {

            min-width: 0;

        }


        .user-cell strong {

            display: block;

            margin-bottom: 3px;

            color: #112b55;

            font-size: 10px;

            font-weight: 800;

        }


        .user-cell small {

            display: block;

            max-width: 200px;

            overflow: hidden;

            color: #8897ac;

            font-size: 8px;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .user-cell small.business {

            margin-top: 2px;

            color: #2563eb;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | ROLE / STATUS SELECT
        |--------------------------------------------------------------------------
        */

        .user-inline-form {

            display: inline-block;

            margin: 0;

        }


        .user-inline-form select {

            min-width: 108px;

            height: 33px;

            padding:

                0
                9px;

            outline: none;

            color: #334155;

            background: #ffffff;

            border:

                1px solid
                #d7e2ef;

            border-radius: 8px;

            font-size: 8px;

            font-weight: 700;

            cursor: pointer;

        }


        .user-inline-form select:focus {

            border-color: #3b82f6;

        }


        /*
        |--------------------------------------------------------------------------
        | ROLE BADGE
        |--------------------------------------------------------------------------
        */

        .user-role-badge {

            min-height: 30px;

            padding:

                0
                10px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            color: #2563eb;

            background: #eff6ff;

            border:

                1px solid
                #d6e7ff;

            border-radius: 8px;

            font-size: 8px;

            font-weight: 800;

            text-transform: capitalize;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS BADGE
        |--------------------------------------------------------------------------
        */

        .user-status-badge {

            min-height: 28px;

            padding:

                0
                10px;

            display: inline-flex;

            align-items: center;

            gap: 5px;

            border-radius: 999px;

            font-size: 8px;

            font-weight: 800;

            text-transform: capitalize;

        }


        .user-status-badge::before {

            content: "";

            width: 5px;
            height: 5px;

            border-radius: 50%;

        }


        .user-status-badge.active {

            color: #15803d;

            background: #ecfdf3;

        }


        .user-status-badge.active::before {

            background: #22c55e;

        }


        .user-status-badge.inactive {

            color: #64748b;

            background: #f1f5f9;

        }


        .user-status-badge.inactive::before {

            background: #94a3b8;

        }


        .user-status-badge.pending {

            color: #a16207;

            background: #fffbea;

        }


        .user-status-badge.pending::before {

            background: #eab308;

        }


        .user-status-badge.suspended {

            color: #b91c1c;

            background: #fff1f2;

        }


        .user-status-badge.suspended::before {

            background: #ef4444;

        }


        /*
        |--------------------------------------------------------------------------
        | MFA
        |--------------------------------------------------------------------------
        */

        .user-mfa {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            color: #2563eb;

            font-size: 8px;

            font-weight: 700;

        }


        .user-mfa::before {

            content: "";

            width: 5px;
            height: 5px;

            border-radius: 50%;

            background: #2563eb;

        }


        .user-mfa.disabled {

            color: #94a3b8;

        }


        .user-mfa.disabled::before {

            background: #94a3b8;

        }


        /*
        |--------------------------------------------------------------------------
        | VIEW BUTTON
        |--------------------------------------------------------------------------
        */

        .user-view-btn {

            min-height: 31px;

            padding:

                0
                11px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            color: #2563eb;

            background: #eff6ff;

            border:

                1px solid
                #cfe1ff;

            border-radius: 8px;

            font-size: 8px;

            font-weight: 800;

            text-decoration: none;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .users-empty {

            padding:

                70px
                20px !important;

            color: #94a3b8 !important;

            text-align: center;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {

            .users-stats {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .users-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .users-filter input {

                grid-column:

                    1 /
                    -1;

            }

        }


        @media (max-width: 900px) {

            .users-main {

                margin-left: 0;

                width: 100%;

            }


            .users-content {

                padding:

                    25px
                    20px
                    50px;

            }


            .users-hero {

                min-height: 140px;

                padding:

                    28px
                    28px;

            }


            .users-hero h1 {

                font-size: 31px;

            }


            .users-hero-icon {

                width: 67px;
                height: 67px;

            }

        }


        @media (max-width: 650px) {

            .users-content {

                padding:

                    18px
                    13px
                    40px;

            }


            .users-hero {

                min-height: auto;

                padding:

                    25px
                    21px;

                border-radius: 20px;

            }


            .users-hero h1 {

                font-size: 27px;

            }


            .users-hero p {

                max-width: 230px;

                font-size: 11px;

            }


            .users-hero-icon {

                width: 55px;
                height: 55px;

                border-radius: 15px;

                font-size: 24px;

            }


            .users-stats {

                grid-template-columns: 1fr;

                gap: 12px;

            }


            .user-stat-card {

                min-height: 120px;

            }


            .users-panel-header {

                padding:

                    20px
                    17px;

                flex-direction: column;

                align-items: flex-start;

            }


            .users-filter {

                grid-template-columns: 1fr;

            }


            .users-filter input {

                grid-column: auto;

            }


            .users-btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    /*
    |--------------------------------------------------------------------------
    | ADMIN SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="users-main">


        <div class="users-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="users-hero">


                <div class="users-hero-text">

                    <h1>
                        Users
                    </h1>

                    <p>
                        Monitor and manage all HochipoHub user accounts.
                    </p>

                </div>


                <div class="users-hero-icon">

                    👥

                </div>


            </section>


            <!-- =====================================================
                 SUCCESS MESSAGE
            ====================================================== -->

            <?php if (
                isset($_GET['success']) &&
                $_GET['success'] === 'updated'
            ): ?>


                <div
                    class="
                        users-alert
                        success
                    "
                >

                    User updated successfully.

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 ERROR MESSAGE
            ====================================================== -->

            <?php if (isset($_GET['error'])): ?>


                <div
                    class="
                        users-alert
                        error
                    "
                >


                    <?php

                    $error =
                        $_GET['error'];


                    if ($error === 'self') {

                        echo
                            'You cannot modify your own administrator account from this page.';

                    }

                    elseif ($error === 'notfound') {

                        echo
                            'User not found.';

                    }

                    elseif ($error === 'invalid') {

                        echo
                            'Invalid user information.';

                    }

                    elseif ($error === 'security') {

                        echo
                            'Invalid security token. Please refresh the page.';

                    }

                    else {

                        echo
                            'Unable to process the request.';

                    }

                    ?>


                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="users-stats">


                <!-- TOTAL USERS -->

                <div class="user-stat-card">

                    <span class="user-stat-label">

                        Total Users

                    </span>


                    <strong class="user-stat-value">

                        <?= number_format(
                            $totalUsers
                        ) ?>

                    </strong>

                </div>


                <!-- CUSTOMERS -->

                <div
                    class="
                        user-stat-card
                        customers
                    "
                >

                    <span class="user-stat-label">

                        Customers

                    </span>


                    <strong class="user-stat-value">

                        <?= number_format(
                            $totalCustomers
                        ) ?>

                    </strong>

                </div>


                <!-- VENDORS -->

                <div
                    class="
                        user-stat-card
                        vendors
                    "
                >

                    <span class="user-stat-label">

                        Vendors

                    </span>


                    <strong class="user-stat-value">

                        <?= number_format(
                            $totalVendors
                        ) ?>

                    </strong>

                </div>


                <!-- ADMINS -->

                <div
                    class="
                        user-stat-card
                        admins
                    "
                >

                    <span class="user-stat-label">

                        Admins

                    </span>


                    <strong class="user-stat-value">

                        <?= number_format(
                            $totalAdmins
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 USERS PANEL
            ====================================================== -->

            <section class="users-panel">


                <!-- =================================================
                     PANEL HEADER
                ================================================== -->

                <div class="users-panel-header">


                    <div class="users-panel-title">


                        <div class="users-panel-icon">

                            👤

                        </div>


                        <div>

                            <h2>
                                User Accounts
                            </h2>

                            <p>
                                Manage user roles, account status and access.
                            </p>

                        </div>


                    </div>


                    <div class="users-count-badge">

                        <?= number_format(
                            count(
                                $users
                            )
                        ) ?>

                        users

                    </div>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="users-filter-wrapper">


                    <form
                        method="GET"
                        action="users.php"
                        class="users-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search name, email or phone..."
                            autocomplete="off"
                        >


                        <!-- ROLE -->

                        <select
                            name="role"
                            aria-label="Filter role"
                        >

                            <option value="">
                                All Roles
                            </option>


                            <?php foreach (
                                [
                                    'customer',
                                    'vendor',
                                    'admin'
                                ]
                                as $role
                            ): ?>


                                <option
                                    value="<?= e(
                                        $role
                                    ) ?>"
                                    <?= $roleFilter === $role
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= ucfirst(
                                        e(
                                            $role
                                        )
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter status"
                        >

                            <option value="">
                                All Status
                            </option>


                            <?php foreach (
                                [
                                    'active',
                                    'inactive',
                                    'pending',
                                    'suspended'
                                ]
                                as $status
                            ): ?>


                                <option
                                    value="<?= e(
                                        $status
                                    ) ?>"
                                    <?= $statusFilter === $status
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= ucfirst(
                                        e(
                                            $status
                                        )
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- SEARCH BUTTON -->

                        <button
                            type="submit"
                            class="
                                users-btn
                                primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="users.php"
                            class="
                                users-btn
                                secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="users-table-wrapper">


                    <table class="users-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    User
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    MFA
                                </th>

                                <th>
                                    Joined
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($users)): ?>


                                <tr>

                                    <td
                                        colspan="8"
                                        class="users-empty"
                                    >

                                        No users found.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($users as $user): ?>


                                    <?php

                                    $initial =
                                        strtoupper(
                                            substr(
                                                trim(
                                                    $user['name']
                                                    ?? 'U'
                                                ),
                                                0,
                                                1
                                            )
                                        );


                                    $createdTimestamp =
                                        !empty(
                                            $user[
                                                'created_at'
                                            ]
                                        )
                                            ? strtotime(
                                                $user[
                                                    'created_at'
                                                ]
                                            )
                                            : false;

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span class="user-id">

                                                #<?= (int)
                                                    $user[
                                                        'user_id'
                                                    ] ?>

                                            </span>

                                        </td>


                                        <!-- USER -->

                                        <td>


                                            <div class="user-cell">


                                                <div class="user-avatar">

                                                    <?= e(
                                                        $initial
                                                    ) ?>

                                                </div>


                                                <div class="user-cell-info">

                                                    <strong>

                                                        <?= e(
                                                            $user[
                                                                'name'
                                                            ]
                                                        ) ?>

                                                    </strong>


                                                    <small>

                                                        <?= e(
                                                            $user[
                                                                'email'
                                                            ]
                                                        ) ?>

                                                    </small>


                                                    <?php if (
                                                        !empty(
                                                            $user[
                                                                'business_name'
                                                            ]
                                                        )
                                                    ): ?>


                                                        <small class="business">

                                                            <?= e(
                                                                $user[
                                                                    'business_name'
                                                                ]
                                                            ) ?>

                                                        </small>


                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                        </td>


                                        <!-- PHONE -->

                                        <td>

                                            <?= e(
                                                $user[
                                                    'phone'
                                                ]
                                                ?? '-'
                                            ) ?>

                                        </td>


                                        <!-- ROLE -->

                                        <td>


                                            <?php if (
                                                (int)
                                                $user[
                                                    'user_id'
                                                ]
                                                ===
                                                $adminId
                                            ): ?>


                                                <span class="user-role-badge">

                                                    <?= e(
                                                        $user[
                                                            'role'
                                                        ]
                                                    ) ?>

                                                </span>


                                            <?php else: ?>


                                                <form
                                                    method="POST"
                                                    action="users.php"
                                                    class="user-inline-form"
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
                                                            $csrfToken
                                                        ) ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="update_user"
                                                        value="1"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?= (int)
                                                            $user[
                                                                'user_id'
                                                            ] ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="<?= e(
                                                            $user[
                                                                'status'
                                                            ]
                                                        ) ?>"
                                                    >


                                                    <select
                                                        name="role"
                                                        aria-label="Change role"
                                                        onchange="
                                                            if (
                                                                confirm(
                                                                    'Change this user role to ' +
                                                                    this.value +
                                                                    '?'
                                                                )
                                                            ) {
                                                                this.form.submit();
                                                            } else {
                                                                window.location.reload();
                                                            }
                                                        "
                                                    >


                                                        <?php foreach (
                                                            [
                                                                'customer',
                                                                'vendor',
                                                                'admin'
                                                            ]
                                                            as $role
                                                        ): ?>


                                                            <option
                                                                value="<?= e(
                                                                    $role
                                                                ) ?>"
                                                                <?= $user[
                                                                    'role'
                                                                ] === $role
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= ucfirst(
                                                                    e(
                                                                        $role
                                                                    )
                                                                ) ?>

                                                            </option>


                                                        <?php endforeach; ?>


                                                    </select>


                                                </form>


                                            <?php endif; ?>


                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <?php if (
                                                (int)
                                                $user[
                                                    'user_id'
                                                ]
                                                ===
                                                $adminId
                                            ): ?>


                                                <span
                                                    class="
                                                        user-status-badge
                                                        <?= e(
                                                            $user[
                                                                'status'
                                                            ]
                                                        ) ?>
                                                    "
                                                >

                                                    <?= e(
                                                        $user[
                                                            'status'
                                                        ]
                                                    ) ?>

                                                </span>


                                            <?php else: ?>


                                                <form
                                                    method="POST"
                                                    action="users.php"
                                                    class="user-inline-form"
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
                                                            $csrfToken
                                                        ) ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="update_user"
                                                        value="1"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?= (int)
                                                            $user[
                                                                'user_id'
                                                            ] ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="role"
                                                        value="<?= e(
                                                            $user[
                                                                'role'
                                                            ]
                                                        ) ?>"
                                                    >


                                                    <select
                                                        name="status"
                                                        aria-label="Change status"
                                                        onchange="
                                                            if (
                                                                confirm(
                                                                    'Change this user status to ' +
                                                                    this.value +
                                                                    '?'
                                                                )
                                                            ) {
                                                                this.form.submit();
                                                            } else {
                                                                window.location.reload();
                                                            }
                                                        "
                                                    >


                                                        <?php foreach (
                                                            [
                                                                'active',
                                                                'inactive',
                                                                'pending',
                                                                'suspended'
                                                            ]
                                                            as $status
                                                        ): ?>


                                                            <option
                                                                value="<?= e(
                                                                    $status
                                                                ) ?>"
                                                                <?= $user[
                                                                    'status'
                                                                ] === $status
                                                                    ? 'selected'
                                                                    : '' ?>
                                                            >

                                                                <?= ucfirst(
                                                                    e(
                                                                        $status
                                                                    )
                                                                ) ?>

                                                            </option>


                                                        <?php endforeach; ?>


                                                    </select>


                                                </form>


                                            <?php endif; ?>


                                        </td>


                                        <!-- MFA -->

                                        <td>


                                            <span
                                                class="
                                                    user-mfa
                                                    <?= empty(
                                                        $user[
                                                            'mfa_enabled'
                                                        ]
                                                    )
                                                        ? 'disabled'
                                                        : '' ?>
                                                "
                                            >

                                                <?= !empty(
                                                    $user[
                                                        'mfa_enabled'
                                                    ]
                                                )
                                                    ? 'Enabled'
                                                    : 'Disabled' ?>

                                            </span>


                                        </td>


                                        <!-- JOINED -->

                                        <td>

                                            <?= $createdTimestamp
                                                ? e(
                                                    date(
                                                        'd M Y',
                                                        $createdTimestamp
                                                    )
                                                )
                                                : '-' ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <a
                                                href="../profile.php?id=<?= (int)
                                                    $user[
                                                        'user_id'
                                                    ] ?>"
                                                target="_blank"
                                                class="user-view-btn"
                                            >

                                                View

                                            </a>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


        </div>


    </main>


</div>


<!-- ===============================================================
     SIDEBAR WIDTH SYNC
================================================================ -->

<script>

    /*
    |--------------------------------------------------------------------------
    | AUTO DETECT REAL SIDEBAR WIDTH
    |--------------------------------------------------------------------------
    |
    | Supaya content Users tak masuk belakang sidebar.
    |
    |--------------------------------------------------------------------------
    */

    function syncUsersSidebarWidth() {

        const main =
            document.querySelector(
                '.users-main'
            );


        if (!main) {

            return;

        }


        if (
            window.innerWidth <= 900
        ) {

            main.style.marginLeft =
                '0px';

            main.style.width =
                '100%';

            return;

        }


        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        if (!sidebar) {

            main.style.marginLeft =
                '260px';

            main.style.width =
                'calc(100% - 260px)';

            return;

        }


        const sidebarRect =
            sidebar.getBoundingClientRect();


        if (
            sidebarRect.right > 0
        ) {

            main.style.marginLeft =
                sidebarRect.right +
                'px';


            main.style.width =
                'calc(100% - ' +
                sidebarRect.right +
                'px)';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncUsersSidebarWidth();


            setTimeout(
                syncUsersSidebarWidth,
                100
            );


            setTimeout(
                syncUsersSidebarWidth,
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncUsersSidebarWidth
    );

</script>


</body>

</html>