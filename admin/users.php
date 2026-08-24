<?php

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e($value)
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


$admin_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| UPDATE USER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_user'])
) {

    $user_id = (int) ($_POST['user_id'] ?? 0);

    $role = $_POST['role'] ?? '';

    $status = $_POST['status'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $user_id <= 0 ||
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
    | PREVENT ADMIN FROM MODIFYING OWN ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($user_id === $admin_id) {

        header(
            'Location: users.php?error=self'
        );

        exit;
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | CHECK USER
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare(
            "SELECT role, status
             FROM users
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $user_id
        ]);


        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

            header(
                'Location: users.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare(
            "UPDATE users
             SET role = ?,
                 status = ?
             WHERE user_id = ?"
        );

        $stmt->execute([
            $role,
            $status,
            $user_id
        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare(
            "INSERT INTO admin_logs
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
                )"
        );

        $stmt->execute([
            $admin_id,
            "Updated user #{$user_id} role to {$role} and status to {$status}",
            'user',
            $user_id
        ]);


        header(
            'Location: users.php?success=updated'
        );

        exit;

    } catch (PDOException $e) {

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

$search = trim(
    $_GET['search'] ?? ''
);

$role_filter = $_GET['role'] ?? '';

$status_filter = $_GET['status'] ?? '';


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
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )
    ";

    $v = "%{$search}%";


    array_push(
        $params,
        $v,
        $v,
        $v
    );
}


/*
|--------------------------------------------------------------------------
| ROLE FILTER
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $role_filter,
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

    $params[] = $role_filter;
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $status_filter,
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

    $params[] = $status_filter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        u.created_at DESC
";


$stmt = $db->prepare($sql);

$stmt->execute($params);

$users = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| USER STATISTICS
|--------------------------------------------------------------------------
*/

$total_users = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users"
    )
    ->fetchColumn();


$total_customers = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'customer'"
    )
    ->fetchColumn();


$total_vendors = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'vendor'"
    )
    ->fetchColumn();


$total_admins = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'admin'"
    )
    ->fetchColumn();

?>

<!doctype html>

<html
    lang="en"
>

<head>

    <meta charset="UTF-8">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <title>
        Users | HochipoHub Admin
    </title>


    <!-- FONT -->

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


    <!-- EXISTING ADMIN CSS -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >


    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

    /* =========================================================
       HOCHIPOHUB USERS PAGE
       BLUE ADMIN THEME
    ========================================================= */


    :root {

        --users-navy:
            #071a3d;

        --users-navy-2:
            #0b2454;

        --users-blue:
            #2563eb;

        --users-blue-dark:
            #1d4ed8;

        --users-blue-light:
            #3b82f6;

        --users-blue-soft:
            #eff6ff;

        --users-blue-pale:
            #dbeafe;

        --users-bg:
            #f5f8fd;

        --users-white:
            #ffffff;

        --users-text:
            #0f172a;

        --users-text-2:
            #1e293b;

        --users-muted:
            #64748b;

        --users-muted-2:
            #94a3b8;

        --users-border:
            #e2e8f0;

        --users-border-blue:
            #bfdbfe;

        --users-shadow:
            0 10px 30px
            rgba(15, 23, 42, .055);
    }


    /*
    |--------------------------------------------------------------------------
    | GLOBAL
    |--------------------------------------------------------------------------
    */

    * {

        box-sizing:
            border-box;
    }


    html {

        scroll-behavior:
            smooth;
    }


    body {

        margin:
            0;

        background:
            linear-gradient(
                135deg,
                #f8fbff 0%,
                #f3f7fd 100%
            );

        color:
            var(--users-text);

        font-family:
            "Poppins",
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;
    }


    button,
    input,
    select {

        font-family:
            inherit;
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN WRAPPER
    |--------------------------------------------------------------------------
    */

    .admin-wrapper {

        min-height:
            100vh;

        background:
            var(--users-bg);
    }


    /*
    |--------------------------------------------------------------------------
    | BLUE SIDEBAR OVERRIDE
    |--------------------------------------------------------------------------
    |
    | This keeps this page consistent with the new blue theme.
    |
    */

    .admin-sidebar {

        background:
            linear-gradient(
                180deg,
                #071a3d 0%,
                #0a1e46 52%,
                #06152f 100%
            ) !important;
    }


    .admin-sidebar
    .admin-nav-item.active,
    .admin-sidebar
    .admin-menu-item.active {

        background:
            linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            ) !important;

        color:
            #ffffff !important;
    }


    /*
    |--------------------------------------------------------------------------
    | MAIN
    |--------------------------------------------------------------------------
    */

    .admin-main {

        min-width:
            0;

        min-height:
            100vh;

        padding-bottom:
            50px;

        background:
            transparent;
    }


    /*
    |--------------------------------------------------------------------------
    | TOP HEADER
    |--------------------------------------------------------------------------
    */

    .admin-topbar {

        position:
            sticky;

        top:
            0;

        z-index:
            50;

        display:
            flex;

        align-items:
            center;

        justify-content:
            space-between;

        min-height:
            92px;

        padding:
            20px
            38px;

        background:
            rgba(
                255,
                255,
                255,
                .94
            );

        border-bottom:
            1px solid
            rgba(
                226,
                232,
                240,
                .9
            );

        backdrop-filter:
            blur(16px);
    }


    .admin-header-left {

        display:
            flex;

        align-items:
            center;

        gap:
            17px;

        min-width:
            0;
    }


    .admin-header-left > div:last-child {

        min-width:
            0;
    }


    .admin-topbar h1 {

        margin:
            0;

        color:
            var(--users-text);

        font-size:
            29px;

        line-height:
            1.15;

        font-weight:
            800;

        letter-spacing:
            -.8px;
    }


    .admin-topbar p {

        margin:
            6px
            0
            0;

        color:
            var(--users-muted);

        font-size:
            12px;

        font-weight:
            500;
    }


    /*
    |--------------------------------------------------------------------------
    | MOBILE SIDEBAR BUTTON
    |--------------------------------------------------------------------------
    */

    .admin-sidebar-toggle {

        width:
            42px;

        height:
            42px;

        display:
            inline-flex;

        align-items:
            center;

        justify-content:
            center;

        border:
            1px solid
            var(--users-border);

        border-radius:
            12px;

        background:
            #ffffff;

        color:
            var(--users-navy);

        font-size:
            19px;

        cursor:
            pointer;

        box-shadow:
            0 4px 12px
            rgba(
                15,
                23,
                42,
                .05
            );

        transition:
            .2s ease;
    }


    .admin-sidebar-toggle:hover {

        color:
            var(--users-blue);

        border-color:
            var(--users-border-blue);

        background:
            var(--users-blue-soft);

        transform:
            translateY(-1px);
    }


    /*
    |--------------------------------------------------------------------------
    | PAGE CONTENT
    |--------------------------------------------------------------------------
    */

    .admin-main {

        padding-left:
            0;
    }


    .admin-main > section,
    .admin-main > .admin-alert {

        width:
            min(
                calc(100% - 76px),
                1480px
            );

        margin-left:
            auto;

        margin-right:
            auto;
    }


    /*
    |--------------------------------------------------------------------------
    | ALERT
    |--------------------------------------------------------------------------
    */

    .admin-alert {

        margin-top:
            22px;

        margin-bottom:
            0;

        padding:
            13px
            17px;

        border:
            1px solid
            var(--users-border-blue);

        border-radius:
            13px;

        background:
            var(--users-blue-soft);

        color:
            var(--users-blue-dark);

        font-size:
            11px;

        font-weight:
            600;
    }


    .admin-alert.success {

        border-color:
            #bfdbfe;

        background:
            #eff6ff;

        color:
            #1d4ed8;
    }


    .admin-alert.error {

        border-color:
            #93c5fd;

        background:
            #eff6ff;

        color:
            #1e40af;
    }


    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    .admin-stats {

        display:
            grid;

        grid-template-columns:
            repeat(
                4,
                minmax(
                    0,
                    1fr
                )
            );

        gap:
            15px;

        margin-top:
            28px;

        margin-bottom:
            22px;
    }


    .stat-card {

        position:
            relative;

        overflow:
            hidden;

        min-height:
            135px;

        padding:
            20px;

        border:
            1px solid
            var(--users-border);

        border-radius:
            18px;

        background:
            #ffffff;

        box-shadow:
            var(--users-shadow);

        transition:
            transform .22s ease,
            box-shadow .22s ease,
            border-color .22s ease;
    }


    .stat-card::after {

        content:
            "";

        position:
            absolute;

        right:
            -38px;

        bottom:
            -48px;

        width:
            115px;

        height:
            115px;

        border-radius:
            50%;

        background:
            var(--users-blue-soft);
    }


    .stat-card:hover {

        transform:
            translateY(-3px);

        border-color:
            var(--users-border-blue);

        box-shadow:
            0 15px 34px
            rgba(
                37,
                99,
                235,
                .09
            );
    }


    .stat-card::before {

        content:
            "";

        position:
            absolute;

        top:
            0;

        left:
            0;

        width:
            100%;

        height:
            3px;

        background:
            linear-gradient(
                90deg,
                #2563eb,
                #60a5fa
            );
    }


    .stat-label {

        position:
            relative;

        z-index:
            1;

        display:
            block;

        margin-bottom:
            9px;

        color:
            var(--users-muted);

        font-size:
            10px;

        font-weight:
            600;

        text-transform:
            uppercase;

        letter-spacing:
            .55px;
    }


    .stat-card strong {

        position:
            relative;

        z-index:
            1;

        display:
            block;

        color:
            var(--users-text);

        font-size:
            30px;

        line-height:
            1;

        font-weight:
            800;

        letter-spacing:
            -1px;
    }


    /*
    |--------------------------------------------------------------------------
    | STAT ICONS
    |--------------------------------------------------------------------------
    */

    .stat-card .stat-label::before {

        display:
            inline-flex;

        align-items:
            center;

        justify-content:
            center;

        width:
            25px;

        height:
            25px;

        margin-right:
            8px;

        border-radius:
            8px;

        background:
            var(--users-blue-soft);

        color:
            var(--users-blue);

        content:
            "•";

        font-size:
            18px;

        vertical-align:
            middle;
    }


    /*
    |--------------------------------------------------------------------------
    | PANEL
    |--------------------------------------------------------------------------
    */

    .admin-panel {

        overflow:
            hidden;

        margin-bottom:
            22px;

        border:
            1px solid
            var(--users-border);

        border-radius:
            18px;

        background:
            #ffffff;

        box-shadow:
            var(--users-shadow);
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER PANEL
    |--------------------------------------------------------------------------
    */

    .admin-filter-form {

        display:
            grid;

        grid-template-columns:
            minmax(
                260px,
                1.8fr
            )
            minmax(
                150px,
                .7fr
            )
            minmax(
                150px,
                .7fr
            )
            auto
            auto;

        align-items:
            center;

        gap:
            10px;

        padding:
            18px;
    }


    .admin-filter-form input,
    .admin-filter-form select {

        width:
            100%;

        min-height:
            43px;

        padding:
            0
            13px;

        border:
            1px solid
            var(--users-border);

        border-radius:
            11px;

        outline:
            none;

        background:
            #ffffff;

        color:
            var(--users-text-2);

        font-size:
            11px;

        font-weight:
            500;

        transition:
            .2s ease;
    }


    .admin-filter-form input::placeholder {

        color:
            #94a3b8;
    }


    .admin-filter-form input:hover,
    .admin-filter-form select:hover {

        border-color:
            #cbd5e1;
    }


    .admin-filter-form input:focus,
    .admin-filter-form select:focus {

        border-color:
            var(--users-blue);

        box-shadow:
            0 0 0 3px
            rgba(
                37,
                99,
                235,
                .10
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BUTTONS
    |--------------------------------------------------------------------------
    */

    .admin-btn {

        min-height:
            42px;

        display:
            inline-flex;

        align-items:
            center;

        justify-content:
            center;

        padding:
            0
            17px;

        border:
            1px solid
            transparent;

        border-radius:
            11px;

        font-size:
            10px;

        font-weight:
            700;

        text-decoration:
            none;

        cursor:
            pointer;

        white-space:
            nowrap;

        transition:
            transform .2s ease,
            box-shadow .2s ease,
            background .2s ease,
            border-color .2s ease;
    }


    .admin-btn.primary {

        background:
            linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

        color:
            #ffffff;

        box-shadow:
            0 7px 17px
            rgba(
                37,
                99,
                235,
                .18
            );
    }


    .admin-btn.primary:hover {

        background:
            linear-gradient(
                135deg,
                #1d4ed8,
                #1e40af
            );

        transform:
            translateY(-1px);

        box-shadow:
            0 10px 22px
            rgba(
                37,
                99,
                235,
                .25
            );
    }


    .admin-btn.secondary {

        background:
            #f8fafc;

        border-color:
            var(--users-border);

        color:
            var(--users-muted);
    }


    .admin-btn.secondary:hover {

        background:
            var(--users-blue-soft);

        border-color:
            var(--users-border-blue);

        color:
            var(--users-blue-dark);

        transform:
            translateY(-1px);
    }


    /*
    |--------------------------------------------------------------------------
    | PANEL HEADER
    |--------------------------------------------------------------------------
    */

    .panel-header {

        display:
            flex;

        align-items:
            center;

        justify-content:
            space-between;

        gap:
            15px;

        padding:
            20px 21px;

        border-bottom:
            1px solid
            #edf1f6;

        background:
            linear-gradient(
                180deg,
                #ffffff,
                #fbfdff
            );
    }


    .panel-header h2 {

        margin:
            0;

        color:
            var(--users-text);

        font-size:
            17px;

        font-weight:
            800;

        letter-spacing:
            -.35px;
    }


    .panel-header p {

        margin:
            5px
            0
            0;

        color:
            var(--users-muted);

        font-size:
            9px;

        font-weight:
            500;
    }


    /*
    |--------------------------------------------------------------------------
    | TABLE WRAPPER
    |--------------------------------------------------------------------------
    */

    .table-wrapper {

        width:
            100%;

        overflow-x:
            auto;
    }


    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    .admin-table {

        width:
            100%;

        min-width:
            1000px;

        border-collapse:
            collapse;

        border-spacing:
            0;
    }


    .admin-table thead {

        background:
            #f7faff;
    }


    .admin-table th {

        padding:
            13px
            15px;

        border-bottom:
            1px solid
            var(--users-border);

        color:
            #64748b;

        font-size:
            8px;

        font-weight:
            800;

        letter-spacing:
            .65px;

        text-align:
            left;

        text-transform:
            uppercase;

        white-space:
            nowrap;
    }


    .admin-table th:first-child {

        padding-left:
            21px;
    }


    .admin-table td {

        padding:
            15px;

        border-bottom:
            1px solid
            #edf1f6;

        color:
            var(--users-text-2);

        font-size:
            10px;

        font-weight:
            500;

        vertical-align:
            middle;

        white-space:
            nowrap;
    }


    .admin-table td:first-child {

        padding-left:
            21px;

        color:
            #94a3b8;

        font-weight:
            700;
    }


    .admin-table tbody tr {

        transition:
            background .18s ease;
    }


    .admin-table tbody tr:hover {

        background:
            #f8fbff;
    }


    .admin-table tbody tr:last-child td {

        border-bottom:
            0;
    }


    /*
    |--------------------------------------------------------------------------
    | USER CELL
    |--------------------------------------------------------------------------
    */

    .admin-table td:nth-child(2) {

        min-width:
            220px;

        white-space:
            normal;
    }


    .admin-table td:nth-child(2) strong {

        display:
            block;

        margin-bottom:
            3px;

        color:
            var(--users-text);

        font-size:
            11px;

        font-weight:
            750;
    }


    .admin-table td:nth-child(2) small {

        display:
            block;

        margin-top:
            2px;

        color:
            var(--users-muted);

        font-size:
            8px;

        font-weight:
            500;
    }


    .admin-table td:nth-child(2) small:last-child {

        color:
            var(--users-blue);

        font-weight:
            600;
    }


    /*
    |--------------------------------------------------------------------------
    | INLINE FORM
    |--------------------------------------------------------------------------
    */

    .inline-form {

        display:
            inline-flex;

        align-items:
            center;

        margin:
            0;
    }


    .inline-form select {

        min-width:
            110px;

        height:
            34px;

        padding:
            0 30px 0 10px;

        border:
            1px solid
            #dbe3ef;

        border-radius:
            9px;

        outline:
            none;

        background:
            #ffffff;

        color:
            var(--users-text-2);

        font-family:
            inherit;

        font-size:
            9px;

        font-weight:
            600;

        cursor:
            pointer;

        transition:
            .2s ease;
    }


    .inline-form select:hover {

        border-color:
            #93c5fd;

        background:
            #f8fbff;
    }


    .inline-form select:focus {

        border-color:
            var(--users-blue);

        box-shadow:
            0 0 0 3px
            rgba(
                37,
                99,
                235,
                .10
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN ROLE
    |--------------------------------------------------------------------------
    */

    .admin-table td > span:not(.admin-status) {

        display:
            inline-flex;

        align-items:
            center;

        min-height:
            30px;

        padding:
            0 10px;

        border:
            1px solid
            var(--users-border-blue);

        border-radius:
            8px;

        background:
            var(--users-blue-soft);

        color:
            var(--users-blue-dark);

        font-size:
            9px;

        font-weight:
            700;

        text-transform:
            capitalize;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS BADGES
    |--------------------------------------------------------------------------
    */

    .admin-status {

        display:
            inline-flex;

        align-items:
            center;

        gap:
            6px;

        min-height:
            30px;

        padding:
            0 10px;

        border:
            1px solid
            var(--users-border-blue);

        border-radius:
            999px;

        background:
            var(--users-blue-soft);

        color:
            var(--users-blue-dark);

        font-size:
            8px;

        font-weight:
            750;

        text-transform:
            capitalize;
    }


    .admin-status::before {

        content:
            "";

        width:
            6px;

        height:
            6px;

        border-radius:
            50%;

        background:
            var(--users-blue);

        box-shadow:
            0 0 0 3px
            rgba(
                37,
                99,
                235,
                .10
            );
    }


    /*
    |--------------------------------------------------------------------------
    | SELECT STATUS BLUE THEME
    |--------------------------------------------------------------------------
    */

    .admin-table select {

        color:
            var(--users-text-2);
    }


    /*
    |--------------------------------------------------------------------------
    | MFA
    |--------------------------------------------------------------------------
    */

    .admin-table td:nth-child(6) {

        color:
            var(--users-blue-dark);

        font-size:
            9px;

        font-weight:
            650;
    }


    .admin-table td:nth-child(6)::before {

        content:
            "";

        display:
            inline-block;

        width:
            6px;

        height:
            6px;

        margin-right:
            6px;

        border-radius:
            50%;

        background:
            var(--users-blue);

        vertical-align:
            middle;
    }


    /*
    |--------------------------------------------------------------------------
    | VIEW BUTTON
    |--------------------------------------------------------------------------
    */

    .admin-btn.small {

        min-height:
            32px;

        padding:
            0 12px;

        border:
            1px solid
            #bfdbfe;

        border-radius:
            9px;

        background:
            #eff6ff;

        color:
            #1d4ed8;

        font-size:
            9px;

        font-weight:
            750;
    }


    .admin-btn.small:hover {

        border-color:
            #93c5fd;

        background:
            #dbeafe;

        color:
            #1e40af;

        transform:
            translateY(-1px);

        box-shadow:
            0 5px 14px
            rgba(
                37,
                99,
                235,
                .12
            );
    }


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    .empty-state {

        padding:
            55px 20px !important;

        color:
            #94a3b8 !important;

        font-size:
            11px !important;

        font-weight:
            600 !important;

        text-align:
            center;
    }


    /*
    |--------------------------------------------------------------------------
    | SCROLLBAR
    |--------------------------------------------------------------------------
    */

    .table-wrapper::-webkit-scrollbar {

        height:
            8px;
    }


    .table-wrapper::-webkit-scrollbar-track {

        background:
            #f1f5f9;
    }


    .table-wrapper::-webkit-scrollbar-thumb {

        border-radius:
            999px;

        background:
            #bfdbfe;
    }


    .table-wrapper::-webkit-scrollbar-thumb:hover {

        background:
            #93c5fd;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE 1200
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1200px) {

        .admin-main > section,
        .admin-main > .admin-alert {

            width:
                calc(
                    100% - 48px
                );
        }


        .admin-stats {

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );
        }


        .admin-filter-form {

            grid-template-columns:
                1fr 1fr;

        }


        .admin-filter-form input {

            grid-column:
                1 / -1;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE 900
    |--------------------------------------------------------------------------
    */

    @media (max-width: 900px) {

        .admin-topbar {

            min-height:
                78px;

            padding:
                16px
                24px;
        }


        .admin-topbar h1 {

            font-size:
                24px;
        }


        .admin-topbar p {

            font-size:
                10px;
        }


        .admin-main > section,
        .admin-main > .admin-alert {

            width:
                calc(
                    100% - 36px
                );
        }

    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE 650
    |--------------------------------------------------------------------------
    */

    @media (max-width: 650px) {

        .admin-topbar {

            padding:
                14px
                17px;
        }


        .admin-header-left {

            gap:
                11px;
        }


        .admin-topbar h1 {

            font-size:
                21px;
        }


        .admin-topbar p {

            margin-top:
                4px;

            font-size:
                9px;
        }


        .admin-main > section,
        .admin-main > .admin-alert {

            width:
                calc(
                    100% - 24px
                );
        }


        .admin-stats {

            grid-template-columns:
                1fr;

            gap:
                11px;

            margin-top:
                18px;
        }


        .stat-card {

            min-height:
                115px;
        }


        .admin-filter-form {

            grid-template-columns:
                1fr;

            padding:
                14px;
        }


        .admin-filter-form input {

            grid-column:
                auto;
        }


        .admin-filter-form .admin-btn {

            width:
                100%;
        }


        .panel-header {

            padding:
                17px;
        }


        .panel-header h2 {

            font-size:
                15px;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE 450
    |--------------------------------------------------------------------------
    */

    @media (max-width: 450px) {

        .admin-topbar h1 {

            font-size:
                19px;
        }


        .admin-topbar p {

            display:
                none;
        }


        .admin-sidebar-toggle {

            width:
                38px;

            height:
                38px;
        }


        .stat-card {

            padding:
                17px;
        }


        .stat-card strong {

            font-size:
                27px;
        }

    }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    require_once dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    ?>


    <main class="admin-main">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <header class="admin-topbar">


            <div class="admin-header-left">


                <button
                    type="button"
                    id="adminSidebarToggle"
                    class="admin-sidebar-toggle"
                    aria-label="Open sidebar"
                    aria-expanded="false"
                >
                    ☰
                </button>


                <div>

                    <h1>
                        Users
                    </h1>

                    <p>
                        Manage HochipoHub user accounts and access.
                    </p>

                </div>


            </div>


        </header>


        <!-- =====================================================
             SUCCESS MESSAGE
        ====================================================== -->

        <?php if (isset($_GET['success'])): ?>

            <div class="admin-alert success">

                User updated successfully.

            </div>

        <?php endif; ?>


        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if (isset($_GET['error'])): ?>

            <div class="admin-alert error">

                <?php

                $err = $_GET['error'];


                echo $err === 'self'

                    ? 'You cannot modify your own administrator account from this page.'

                    : (

                        $err === 'notfound'

                            ? 'User not found.'

                            : (

                                $err === 'invalid'

                                    ? 'Invalid user information.'

                                    : 'Unable to process the request.'
                            )
                    );

                ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <section class="admin-stats">


            <?php

            $statistics = [

                [
                    'Total Users',
                    $total_users
                ],

                [
                    'Customers',
                    $total_customers
                ],

                [
                    'Vendors',
                    $total_vendors
                ],

                [
                    'Admins',
                    $total_admins
                ]

            ];

            ?>


            <?php foreach (
                $statistics
                as $s
            ): ?>


                <div class="stat-card">


                    <span class="stat-label">

                        <?= e(
                            $s[0]
                        ) ?>

                    </span>


                    <strong>

                        <?= number_format(
                            $s[1]
                        ) ?>

                    </strong>


                </div>


            <?php endforeach; ?>


        </section>


        <!-- =====================================================
             SEARCH & FILTER
        ====================================================== -->

        <section class="admin-panel">


            <form
                method="GET"
                class="admin-filter-form"
            >


                <input
                    type="text"
                    name="search"
                    placeholder="Search name, email or phone..."
                    value="<?= e($search) ?>"
                    autocomplete="off"
                >


                <select
                    name="role"
                    aria-label="Filter by role"
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
                        as $r
                    ): ?>


                        <option
                            value="<?= e($r) ?>"
                            <?= $role_filter === $r
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= ucfirst(
                                e($r)
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


                <select
                    name="status"
                    aria-label="Filter by status"
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
                        as $s
                    ): ?>


                        <option
                            value="<?= e($s) ?>"
                            <?= $status_filter === $s
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= ucfirst(
                                e($s)
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


                <button
                    class="admin-btn primary"
                    type="submit"
                >

                    Search

                </button>


                <a
                    class="admin-btn secondary"
                    href="users.php"
                >

                    Reset

                </a>


            </form>


        </section>


        <!-- =====================================================
             USER LIST
        ====================================================== -->

        <section class="admin-panel">


            <div class="panel-header">


                <div>

                    <h2>
                        User List
                    </h2>


                    <p>

                        <?= number_format(
                            count($users)
                        ) ?>

                        user(s) found

                    </p>

                </div>


            </div>


            <div class="table-wrapper">


                <table class="admin-table">


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


                    <?php if (!$users): ?>


                        <tr>

                            <td
                                colspan="8"
                                class="empty-state"
                            >

                                No users found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $users
                            as $u
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    #

                                    <?= (int)
                                        $u['user_id']
                                    ?>

                                </td>


                                <!-- USER -->

                                <td>


                                    <strong>

                                        <?= e(
                                            $u['name']
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= e(
                                            $u['email']
                                        ) ?>

                                    </small>


                                    <?php if (
                                        !empty(
                                            $u['business_name']
                                        )
                                    ): ?>


                                        <small>

                                            <?= e(
                                                $u[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </small>


                                    <?php endif; ?>


                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?= e(
                                        $u['phone'] ??
                                        '-'
                                    ) ?>

                                </td>


                                <!-- ROLE -->

                                <td>


                                    <?php if (
                                        (int)
                                        $u['user_id']
                                        ===
                                        $admin_id
                                    ): ?>


                                        <span>

                                            <?= e(
                                                $u['role']
                                            ) ?>

                                        </span>


                                    <?php else: ?>


                                        <form
                                            method="POST"
                                            class="inline-form"
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
                                                    $u['user_id']
                                                ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="status"
                                                value="<?= e(
                                                    $u['status']
                                                ) ?>"
                                            >


                                            <select
                                                name="role"
                                                onchange="this.form.submit()"
                                                aria-label="Change role"
                                            >


                                                <?php foreach (
                                                    [
                                                        'customer',
                                                        'vendor',
                                                        'admin'
                                                    ]
                                                    as $r
                                                ): ?>


                                                    <option
                                                        value="<?= e($r) ?>"
                                                        <?= $u['role'] === $r
                                                            ? 'selected'
                                                            : ''
                                                        ?>
                                                    >

                                                        <?= ucfirst(
                                                            e($r)
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
                                        $u['user_id']
                                        ===
                                        $admin_id
                                    ): ?>


                                        <span
                                            class="admin-status status-active"
                                        >

                                            <?= e(
                                                $u['status']
                                            ) ?>

                                        </span>


                                    <?php else: ?>


                                        <form
                                            method="POST"
                                            class="inline-form"
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
                                                    $u['user_id']
                                                ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="role"
                                                value="<?= e(
                                                    $u['role']
                                                ) ?>"
                                            >


                                            <select
                                                name="status"
                                                onchange="this.form.submit()"
                                                aria-label="Change status"
                                            >


                                                <?php foreach (
                                                    [
                                                        'active',
                                                        'inactive',
                                                        'pending',
                                                        'suspended'
                                                    ]
                                                    as $s
                                                ): ?>


                                                    <option
                                                        value="<?= e($s) ?>"
                                                        <?= $u['status'] === $s
                                                            ? 'selected'
                                                            : ''
                                                        ?>
                                                    >

                                                        <?= ucfirst(
                                                            e($s)
                                                        ) ?>

                                                    </option>


                                                <?php endforeach; ?>


                                            </select>


                                        </form>


                                    <?php endif; ?>


                                </td>


                                <!-- MFA -->

                                <td>

                                    <?= !empty(
                                        $u['mfa_enabled']
                                    )
                                        ? 'Enabled'
                                        : 'Disabled'
                                    ?>

                                </td>


                                <!-- JOINED -->

                                <td>

                                    <?php

                                    $createdTimestamp =
                                        !empty(
                                            $u['created_at']
                                        )
                                            ? strtotime(
                                                $u['created_at']
                                            )
                                            : false;

                                    ?>


                                    <?= $createdTimestamp
                                        ? e(
                                            date(
                                                'd M Y',
                                                $createdTimestamp
                                            )
                                        )
                                        : '-'
                                    ?>

                                </td>


                                <!-- ACTION -->

                                <td>


                                    <a
                                        class="admin-btn small"
                                        href="../profile.php?id=<?= (int) $u['user_id'] ?>"
                                        target="_blank"
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


    </main>


</div>


</body>

</html>