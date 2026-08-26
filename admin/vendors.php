<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN VENDORS
|--------------------------------------------------------------------------
| File: admin/vendors.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';


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
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| ESCAPE FUNCTION
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
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';


if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case 'approved':

            $message =
                'Vendor application approved successfully.';

            $messageType =
                'success';

            break;


        case 'rejected':

            $message =
                'Vendor application rejected successfully.';

            $messageType =
                'success';

            break;


        case 'status':

            $message =
                'Vendor status updated successfully.';

            $messageType =
                'success';

            break;
    }
}


if (isset($_GET['error'])) {

    $messageType =
        'error';


    switch ($_GET['error']) {

        case 'security':

            $message =
                'Invalid security token. Please refresh the page and try again.';

            break;


        case 'invalid':

            $message =
                'Invalid vendor information.';

            break;


        case 'notfound':

            $message =
                'Vendor or application was not found.';

            break;


        default:

            $message =
                'Unable to process vendor request. Please try again.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| HANDLE POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
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
            'Location: vendors.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE / REJECT APPLICATION
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_POST['application_action']
        )
    ) {

        $applicationId =
            (int) (
                $_POST['application_id']
                ?? 0
            );


        $action =
            $_POST['application_action']
            ?? '';


        if (
            $applicationId <= 0 ||
            !in_array(
                $action,
                [
                    'approve',
                    'reject'
                ],
                true
            )
        ) {

            header(
                'Location: vendors.php?error=invalid'
            );

            exit;
        }


        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | LOAD APPLICATION
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT

                        application_id,
                        user_id,
                        business_name,
                        reason,
                        status

                    FROM vendor_applications

                    WHERE application_id = ?

                    LIMIT 1

                    FOR UPDATE
                ");


            $stmt->execute([
                $applicationId
            ]);


            $application =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$application) {

                $db->rollBack();


                header(
                    'Location: vendors.php?error=notfound'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | APPROVE APPLICATION
            |--------------------------------------------------------------------------
            */

            if ($action === 'approve') {


                /*
                |--------------------------------------------------------------------------
                | APPLICATION STATUS
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE vendor_applications

                        SET
                            status = 'Approved',
                            reviewed_at = NOW(),
                            reviewed_by = ?

                        WHERE application_id = ?
                    ");


                $stmt->execute([

                    $adminId,
                    $applicationId

                ]);


                /*
                |--------------------------------------------------------------------------
                | USER ROLE
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET
                            role = 'vendor',
                            status = 'active'

                        WHERE user_id = ?
                    ");


                $stmt->execute([
                    $application['user_id']
                ]);


                /*
                |--------------------------------------------------------------------------
                | CHECK EXISTING VENDOR
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        SELECT vendor_id

                        FROM vendors

                        WHERE user_id = ?

                        LIMIT 1
                    ");


                $stmt->execute([
                    $application['user_id']
                ]);


                $existingVendor =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                /*
                |--------------------------------------------------------------------------
                | UPDATE EXISTING VENDOR
                |--------------------------------------------------------------------------
                */

                if ($existingVendor) {

                    $stmt =
                        $db->prepare("
                            UPDATE vendors

                            SET
                                business_name = ?,
                                approval_status = 'Approved'

                            WHERE user_id = ?
                        ");


                    $stmt->execute([

                        $application['business_name'],
                        $application['user_id']

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE VENDOR
                |--------------------------------------------------------------------------
                */

                else {

                    $stmt =
                        $db->prepare("
                            INSERT INTO vendors
                            (
                                user_id,
                                business_name,
                                approval_status
                            )

                            VALUES
                            (
                                ?,
                                ?,
                                'Approved'
                            )
                        ");


                    $stmt->execute([

                        $application['user_id'],
                        $application['business_name']

                    ]);
                }


                $logAction =
                    'Approved vendor application';
            }


            /*
            |--------------------------------------------------------------------------
            | REJECT APPLICATION
            |--------------------------------------------------------------------------
            */

            else {


                /*
                |--------------------------------------------------------------------------
                | APPLICATION STATUS
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE vendor_applications

                        SET
                            status = 'Rejected',
                            reviewed_at = NOW(),
                            reviewed_by = ?

                        WHERE application_id = ?
                    ");


                $stmt->execute([

                    $adminId,
                    $applicationId

                ]);


                /*
                |--------------------------------------------------------------------------
                | VENDOR STATUS
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE vendors

                        SET
                            approval_status = 'Rejected'

                        WHERE user_id = ?
                    ");


                $stmt->execute([
                    $application['user_id']
                ]);


                /*
                |--------------------------------------------------------------------------
                | USER ROLE
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET
                            role = 'customer'

                        WHERE user_id = ?

                        AND role != 'admin'
                    ");


                $stmt->execute([
                    $application['user_id']
                ]);


                $logAction =
                    'Rejected vendor application';
            }


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
                $logAction,
                'vendor_application',
                $applicationId

            ]);


            $db->commit();


            header(
                'Location: vendors.php?success=' .
                (
                    $action === 'approve'
                        ? 'approved'
                        : 'rejected'
                )
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
                'Location: vendors.php?error=process'
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE VENDOR STATUS
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_POST['update_vendor_status']
        )
    ) {

        $vendorId =
            (int) (
                $_POST['vendor_id']
                ?? 0
            );


        $approvalStatus =
            $_POST['approval_status']
            ?? '';


        $allowedStatuses = [

            'Pending',
            'Approved',
            'Rejected',
            'Suspended'

        ];


        if (
            $vendorId <= 0 ||
            !in_array(
                $approvalStatus,
                $allowedStatuses,
                true
            )
        ) {

            header(
                'Location: vendors.php?error=invalid'
            );

            exit;
        }


        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | GET VENDOR
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT
                        vendor_id,
                        user_id

                    FROM vendors

                    WHERE vendor_id = ?

                    LIMIT 1
                ");


            $stmt->execute([
                $vendorId
            ]);


            $vendor =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$vendor) {

                $db->rollBack();


                header(
                    'Location: vendors.php?error=notfound'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE VENDOR
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    UPDATE vendors

                    SET
                        approval_status = ?

                    WHERE vendor_id = ?
                ");


            $stmt->execute([

                $approvalStatus,
                $vendorId

            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE USER WHEN SUSPENDED
            |--------------------------------------------------------------------------
            */

            if (
                $approvalStatus ===
                'Suspended'
            ) {

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET
                            status = 'suspended'

                        WHERE user_id = ?
                    ");


                $stmt->execute([
                    $vendor['user_id']
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE USER WHEN APPROVED
            |--------------------------------------------------------------------------
            */

            elseif (
                $approvalStatus ===
                'Approved'
            ) {

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET
                            role = 'vendor',
                            status = 'active'

                        WHERE user_id = ?
                    ");


                $stmt->execute([
                    $vendor['user_id']
                ]);
            }


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

                'Updated vendor approval status to ' .
                $approvalStatus,

                'vendor',

                $vendorId

            ]);


            $db->commit();


            header(
                'Location: vendors.php?success=status'
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
                'Location: vendors.php?error=update'
            );

            exit;
        }
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


$statusFilter =
    $_GET['status']
    ?? '';


/*
|--------------------------------------------------------------------------
| LOAD VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];


try {

    $sql = "
        SELECT

            v.vendor_id,
            v.user_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.business_address,
            v.category,
            v.delivery_method,
            v.approval_status,
            v.created_at,

            u.name AS owner_name,
            u.email AS owner_email,
            u.phone AS owner_phone,
            u.status AS user_status

        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

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
                v.business_name LIKE ?
                OR u.name LIKE ?
                OR u.email LIKE ?
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
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $statusFilter,
            [
                'Pending',
                'Approved',
                'Rejected',
                'Suspended'
            ],
            true
        )
    ) {

        $sql .= "
            AND v.approval_status = ?
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
            v.created_at DESC,
            v.vendor_id DESC
    ";


    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $vendors = [];

    error_log(
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| PENDING APPLICATIONS
|--------------------------------------------------------------------------
*/

$applications = [];


try {

    $stmt =
        $db->query("
            SELECT

                va.application_id,
                va.user_id,
                va.business_name,
                va.reason,
                va.status,
                va.created_at,

                u.name AS applicant_name,
                u.email AS applicant_email,
                u.phone AS applicant_phone

            FROM vendor_applications va

            INNER JOIN users u
                ON va.user_id = u.user_id

            WHERE va.status = 'Pending'

            ORDER BY
                va.created_at DESC
        ");


    $applications =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $applications = [];

    error_log(
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalVendors = 0;
$approvedVendors = 0;
$pendingVendors = 0;
$suspendedVendors = 0;


try {

    $totalVendors =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM vendors
            ")
            ->fetchColumn();


    $approvedVendors =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM vendors
                WHERE approval_status = 'Approved'
            ")
            ->fetchColumn();


    $pendingVendors =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM vendors
                WHERE approval_status = 'Pending'
            ")
            ->fetchColumn();


    $suspendedVendors =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM vendors
                WHERE approval_status = 'Suspended'
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
        Vendors | HochipoHub Admin
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

            --vendor-blue:
                #2563eb;

            --vendor-blue-dark:
                #174ca8;

            --vendor-navy:
                #08265a;

            --vendor-bg:
                #eef5fd;

            --vendor-white:
                #ffffff;

            --vendor-text:
                #0b2d63;

            --vendor-muted:
                #8294b3;

            --vendor-border:
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
        | MAIN
        |--------------------------------------------------------------------------
        */

        .vendors-main {

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

        .vendors-content {

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

        .vendors-hero {

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


        .vendors-hero::before {

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


        .vendors-hero::after {

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


        .vendors-hero-text {

            position: relative;

            z-index: 2;

        }


        .vendors-hero h1 {

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


        .vendors-hero p {

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

        .vendors-hero-icon {

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

        }


        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        .vendors-message {

            margin-bottom: 22px;

            padding:

                14px
                17px;

            border-radius: 12px;

            font-size: 11px;

            font-weight: 600;

        }


        .vendors-message.success {

            color: #166534;

            background: #ecfdf5;

            border:

                1px solid
                #bbf7d0;

        }


        .vendors-message.error {

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

        .vendors-stats {

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


        .vendor-stat-card {

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


        .vendor-stat-card::after {

            content: "";

            position: absolute;

            right: -29px;
            bottom: -45px;

            width: 110px;
            height: 110px;

            border-radius: 50%;

            background: #edf4ff;

        }


        .vendor-stat-card.approved {

            border-top-color: #16a34a;

        }


        .vendor-stat-card.approved::after {

            background: #eaf9ef;

        }


        .vendor-stat-card.pending {

            border-top-color: #f59e0b;

        }


        .vendor-stat-card.pending::after {

            background: #fff7df;

        }


        .vendor-stat-card.suspended {

            border-top-color: #ef4444;

        }


        .vendor-stat-card.suspended::after {

            background: #fff0f1;

        }


        .vendor-stat-label {

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


        .vendor-stat-value {

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
        | PANEL
        |--------------------------------------------------------------------------
        */

        .vendors-panel {

            overflow: hidden;

            margin-bottom: 28px;

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

        .vendors-panel-header {

            min-height: 110px;

            padding:

                26px
                30px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            border-bottom:

                1px solid
                #e7edf5;

        }


        .vendors-panel-title {

            display: flex;

            align-items: center;

            gap: 16px;

        }


        .vendors-panel-icon {

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


        .vendors-panel-header h2 {

            margin:

                0
                0
                5px;

            color: #092e65;

            font-size: 20px;

            font-weight: 800;

        }


        .vendors-panel-header p {

            margin: 0;

            color: #8999b4;

            font-size: 11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT BADGE
        |--------------------------------------------------------------------------
        */

        .vendors-count-badge {

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

            white-space: nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .vendors-filter-wrapper {

            padding:

                22px
                28px;

            border-bottom:

                1px solid
                #edf1f6;

            background: #fbfdff;

        }


        .vendors-filter {

            display: grid;

            grid-template-columns:

                minmax(
                    260px,
                    1.7fr
                )

                minmax(
                    160px,
                    .55fr
                )

                auto

                auto;

            gap: 10px;

        }


        .vendors-filter input,
        .vendors-filter select {

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


        .vendors-filter input::placeholder {

            color: #96a5b9;

        }


        .vendors-filter input:focus,
        .vendors-filter select:focus {

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
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .vendor-btn {

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


        .vendor-btn.primary {

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


        .vendor-btn.secondary {

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

        .vendors-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .vendors-table {

            width: 100%;

            min-width: 1000px;

            border-collapse: collapse;

        }


        .vendors-table thead {

            background: #f6f9fd;

        }


        .vendors-table th {

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


        .vendors-table td {

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


        .vendors-table tbody tr:hover {

            background: #f9fbff;

        }


        .vendors-table tbody tr:last-child td {

            border-bottom: 0;

        }


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        .vendor-id {

            color: #8796ac;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | PERSON
        |--------------------------------------------------------------------------
        */

        .vendor-person {

            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 200px;

        }


        .vendor-avatar {

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


        .vendor-person strong,
        .vendor-business strong {

            display: block;

            margin-bottom: 3px;

            color: #112b55;

            font-size: 10px;

            font-weight: 800;

        }


        .vendor-person small,
        .vendor-business small {

            display: block;

            max-width: 210px;

            overflow: hidden;

            color: #8897ac;

            font-size: 8px;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | TAG
        |--------------------------------------------------------------------------
        */

        .vendor-tag {

            min-height: 27px;

            padding:

                0
                9px;

            display: inline-flex;

            align-items: center;

            color: #52647f;

            background: #f1f5f9;

            border:

                1px solid
                #e2e8f0;

            border-radius: 999px;

            font-size: 8px;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS SELECT
        |--------------------------------------------------------------------------
        */

        .vendor-status-select {

            min-width: 115px;

            height: 34px;

            padding:

                0
                10px;

            outline: none;

            border-radius: 9px;

            font-size: 8px;

            font-weight: 800;

            cursor: pointer;

        }


        .vendor-status-select.approved {

            color: #15803d;

            background: #ecfdf3;

            border:

                1px solid
                #bbf7d0;

        }


        .vendor-status-select.pending {

            color: #a16207;

            background: #fffbea;

            border:

                1px solid
                #fde68a;

        }


        .vendor-status-select.rejected {

            color: #b91c1c;

            background: #fff1f2;

            border:

                1px solid
                #fecdd3;

        }


        .vendor-status-select.suspended {

            color: #b91c1c;

            background: #fff1f2;

            border:

                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | APPLICATION ACTIONS
        |--------------------------------------------------------------------------
        */

        .application-actions {

            display: flex;

            align-items: center;

            gap: 7px;

        }


        .application-actions form {

            margin: 0;

        }


        .application-btn {

            min-height: 32px;

            padding:

                0
                11px;

            border-radius: 8px;

            font-size: 8px;

            font-weight: 800;

            cursor: pointer;

        }


        .application-btn.approve {

            color: #15803d;

            background: #ecfdf3;

            border:

                1px solid
                #bbf7d0;

        }


        .application-btn.reject {

            color: #b91c1c;

            background: #fff1f2;

            border:

                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .vendor-empty {

            padding:

                65px
                20px !important;

            color: #94a3b8 !important;

            text-align: center;

        }


        .vendor-empty strong {

            display: block;

            margin-bottom: 6px;

            color: #49617f;

            font-size: 11px;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {

            .vendors-stats {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .vendors-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .vendors-filter input {

                grid-column:

                    1 /
                    -1;

            }

        }


        @media (max-width: 900px) {

            .vendors-main {

                margin-left: 0;

                width: 100%;

            }


            .vendors-content {

                padding:

                    25px
                    20px
                    50px;

            }


            .vendors-hero {

                min-height: 140px;

                padding:

                    28px;

            }


            .vendors-hero h1 {

                font-size: 31px;

            }


            .vendors-hero-icon {

                width: 67px;
                height: 67px;

            }

        }


        @media (max-width: 650px) {

            .vendors-content {

                padding:

                    18px
                    13px
                    40px;

            }


            .vendors-hero {

                min-height: auto;

                padding:

                    25px
                    21px;

                border-radius: 20px;

            }


            .vendors-hero h1 {

                font-size: 27px;

            }


            .vendors-hero p {

                max-width: 230px;

                font-size: 11px;

            }


            .vendors-hero-icon {

                width: 55px;
                height: 55px;

                border-radius: 15px;

                font-size: 24px;

            }


            .vendors-stats {

                grid-template-columns: 1fr;

                gap: 12px;

            }


            .vendor-stat-card {

                min-height: 120px;

            }


            .vendors-panel-header {

                padding:

                    20px
                    17px;

                flex-direction: column;

                align-items: flex-start;

            }


            .vendors-filter {

                grid-template-columns: 1fr;

            }


            .vendors-filter input {

                grid-column: auto;

            }


            .vendor-btn {

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


    <main class="vendors-main">


        <div class="vendors-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="vendors-hero">


                <div class="vendors-hero-text">

                    <h1>
                        Vendors
                    </h1>

                    <p>
                        Monitor and manage marketplace vendors and applications.
                    </p>

                </div>


                <div class="vendors-hero-icon">

                    🏪

                </div>


            </section>


            <!-- =====================================================
                 MESSAGE
            ====================================================== -->

            <?php if ($message !== ''): ?>


                <div
                    class="
                        vendors-message
                        <?= e($messageType) ?>
                    "
                >

                    <?= e($message) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="vendors-stats">


                <!-- TOTAL -->

                <div class="vendor-stat-card">

                    <span class="vendor-stat-label">

                        Total Vendors

                    </span>


                    <strong class="vendor-stat-value">

                        <?= number_format(
                            $totalVendors
                        ) ?>

                    </strong>

                </div>


                <!-- APPROVED -->

                <div
                    class="
                        vendor-stat-card
                        approved
                    "
                >

                    <span class="vendor-stat-label">

                        Approved

                    </span>


                    <strong class="vendor-stat-value">

                        <?= number_format(
                            $approvedVendors
                        ) ?>

                    </strong>

                </div>


                <!-- PENDING -->

                <div
                    class="
                        vendor-stat-card
                        pending
                    "
                >

                    <span class="vendor-stat-label">

                        Pending

                    </span>


                    <strong class="vendor-stat-value">

                        <?= number_format(
                            $pendingVendors
                        ) ?>

                    </strong>

                </div>


                <!-- SUSPENDED -->

                <div
                    class="
                        vendor-stat-card
                        suspended
                    "
                >

                    <span class="vendor-stat-label">

                        Suspended

                    </span>


                    <strong class="vendor-stat-value">

                        <?= number_format(
                            $suspendedVendors
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 PENDING APPLICATIONS PANEL
            ====================================================== -->

            <section class="vendors-panel">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="vendors-panel-header">


                    <div class="vendors-panel-title">


                        <div class="vendors-panel-icon">

                            ✓

                        </div>


                        <div>

                            <h2>
                                Vendor Applications
                            </h2>

                            <p>
                                Review pending applications before approving vendors.
                            </p>

                        </div>


                    </div>


                    <span class="vendors-count-badge">

                        <?= number_format(
                            count(
                                $applications
                            )
                        ) ?>

                        pending

                    </span>


                </div>


                <!-- =================================================
                     APPLICATION TABLE
                ================================================== -->

                <div class="vendors-table-wrapper">


                    <table class="vendors-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Applicant
                                </th>

                                <th>
                                    Business
                                </th>

                                <th>
                                    Reason
                                </th>

                                <th>
                                    Applied
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($applications)): ?>


                                <tr>

                                    <td
                                        colspan="6"
                                        class="vendor-empty"
                                    >

                                        <strong>
                                            No pending applications
                                        </strong>

                                        New vendor applications will appear here.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($applications as $application): ?>


                                    <?php

                                    $applicantInitial =
                                        strtoupper(
                                            substr(
                                                trim(
                                                    $application[
                                                        'applicant_name'
                                                    ]
                                                    ?? 'A'
                                                ),
                                                0,
                                                1
                                            )
                                        );

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span class="vendor-id">

                                                #<?= (int)
                                                    $application[
                                                        'application_id'
                                                    ] ?>

                                            </span>

                                        </td>


                                        <!-- APPLICANT -->

                                        <td>


                                            <div class="vendor-person">


                                                <div class="vendor-avatar">

                                                    <?= e(
                                                        $applicantInitial
                                                    ) ?>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= e(
                                                            $application[
                                                                'applicant_name'
                                                            ]
                                                        ) ?>

                                                    </strong>


                                                    <small>

                                                        <?= e(
                                                            $application[
                                                                'applicant_email'
                                                            ]
                                                        ) ?>

                                                    </small>


                                                    <?php if (
                                                        !empty(
                                                            $application[
                                                                'applicant_phone'
                                                            ]
                                                        )
                                                    ): ?>


                                                        <small>

                                                            <?= e(
                                                                $application[
                                                                    'applicant_phone'
                                                                ]
                                                            ) ?>

                                                        </small>


                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                        </td>


                                        <!-- BUSINESS -->

                                        <td>


                                            <div class="vendor-business">

                                                <strong>

                                                    <?= e(
                                                        $application[
                                                            'business_name'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <small>
                                                    Vendor application
                                                </small>

                                            </div>


                                        </td>


                                        <!-- REASON -->

                                        <td>

                                            <?= e(
                                                $application[
                                                    'reason'
                                                ]
                                                ?? '-'
                                            ) ?>

                                        </td>


                                        <!-- APPLIED -->

                                        <td>

                                            <?= !empty(
                                                $application[
                                                    'created_at'
                                                ]
                                            )
                                                ? e(
                                                    date(
                                                        'd M Y',
                                                        strtotime(
                                                            $application[
                                                                'created_at'
                                                            ]
                                                        )
                                                    )
                                                )
                                                : '-' ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>


                                            <div class="application-actions">


                                                <!-- APPROVE -->

                                                <form
                                                    method="POST"
                                                    action="vendors.php"
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
                                                        name="application_id"
                                                        value="<?= (int)
                                                            $application[
                                                                'application_id'
                                                            ] ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="application_action"
                                                        value="approve"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="
                                                            application-btn
                                                            approve
                                                        "
                                                        onclick="
                                                            return confirm(
                                                                'Approve this vendor application?'
                                                            );
                                                        "
                                                    >

                                                        Approve

                                                    </button>


                                                </form>


                                                <!-- REJECT -->

                                                <form
                                                    method="POST"
                                                    action="vendors.php"
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
                                                        name="application_id"
                                                        value="<?= (int)
                                                            $application[
                                                                'application_id'
                                                            ] ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="application_action"
                                                        value="reject"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="
                                                            application-btn
                                                            reject
                                                        "
                                                        onclick="
                                                            return confirm(
                                                                'Reject this vendor application?'
                                                            );
                                                        "
                                                    >

                                                        Reject

                                                    </button>


                                                </form>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


            <!-- =====================================================
                 VENDOR MANAGEMENT PANEL
            ====================================================== -->

            <section class="vendors-panel">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="vendors-panel-header">


                    <div class="vendors-panel-title">


                        <div class="vendors-panel-icon">

                            🏪

                        </div>


                        <div>

                            <h2>
                                Vendor Management
                            </h2>

                            <p>
                                Search vendors and manage marketplace approval status.
                            </p>

                        </div>


                    </div>


                    <span class="vendors-count-badge">

                        <?= number_format(
                            count(
                                $vendors
                            )
                        ) ?>

                        vendors

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="vendors-filter-wrapper">


                    <form
                        method="GET"
                        action="vendors.php"
                        class="vendors-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search business, owner or email..."
                            autocomplete="off"
                        >


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter vendor status"
                        >

                            <option value="">
                                All Status
                            </option>


                            <?php foreach (
                                [
                                    'Pending',
                                    'Approved',
                                    'Rejected',
                                    'Suspended'
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

                                    <?= e($status) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- SEARCH -->

                        <button
                            type="submit"
                            class="
                                vendor-btn
                                primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="vendors.php"
                            class="
                                vendor-btn
                                secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     VENDOR TABLE
                ================================================== -->

                <div class="vendors-table-wrapper">


                    <table class="vendors-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Business
                                </th>

                                <th>
                                    Owner
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Delivery
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Joined
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($vendors)): ?>


                                <tr>

                                    <td
                                        colspan="7"
                                        class="vendor-empty"
                                    >

                                        <strong>
                                            No vendors found
                                        </strong>

                                        Try another search keyword or status.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($vendors as $vendor): ?>


                                    <?php

                                    $ownerInitial =
                                        strtoupper(
                                            substr(
                                                trim(
                                                    $vendor[
                                                        'owner_name'
                                                    ]
                                                    ?? 'V'
                                                ),
                                                0,
                                                1
                                            )
                                        );


                                    $statusClass =
                                        strtolower(
                                            $vendor[
                                                'approval_status'
                                            ]
                                            ?? 'pending'
                                        );

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span class="vendor-id">

                                                #<?= (int)
                                                    $vendor[
                                                        'vendor_id'
                                                    ] ?>

                                            </span>

                                        </td>


                                        <!-- BUSINESS -->

                                        <td>


                                            <div class="vendor-business">

                                                <strong>

                                                    <?= e(
                                                        $vendor[
                                                            'business_name'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <small>

                                                    <?= e(
                                                        $vendor[
                                                            'business_address'
                                                        ]
                                                        ?? '-'
                                                    ) ?>

                                                </small>

                                            </div>


                                        </td>


                                        <!-- OWNER -->

                                        <td>


                                            <div class="vendor-person">


                                                <div class="vendor-avatar">

                                                    <?= e(
                                                        $ownerInitial
                                                    ) ?>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= e(
                                                            $vendor[
                                                                'owner_name'
                                                            ]
                                                        ) ?>

                                                    </strong>


                                                    <small>

                                                        <?= e(
                                                            $vendor[
                                                                'owner_email'
                                                            ]
                                                        ) ?>

                                                    </small>

                                                </div>


                                            </div>


                                        </td>


                                        <!-- CATEGORY -->

                                        <td>

                                            <span class="vendor-tag">

                                                <?= e(
                                                    $vendor[
                                                        'category'
                                                    ]
                                                    ?? 'General'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DELIVERY -->

                                        <td>

                                            <?= e(
                                                $vendor[
                                                    'delivery_method'
                                                ]
                                                ?? '-'
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="vendors.php"
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
                                                    name="update_vendor_status"
                                                    value="1"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="vendor_id"
                                                    value="<?= (int)
                                                        $vendor[
                                                            'vendor_id'
                                                        ] ?>"
                                                >


                                                <select
                                                    name="approval_status"
                                                    class="
                                                        vendor-status-select
                                                        <?= e(
                                                            $statusClass
                                                        ) ?>
                                                    "
                                                    onchange="
                                                        if (
                                                            confirm(
                                                                'Update vendor status to ' +
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
                                                            'Pending',
                                                            'Approved',
                                                            'Rejected',
                                                            'Suspended'
                                                        ]
                                                        as $status
                                                    ): ?>


                                                        <option
                                                            value="<?= e(
                                                                $status
                                                            ) ?>"
                                                            <?= (
                                                                $vendor[
                                                                    'approval_status'
                                                                ]
                                                                ===
                                                                $status
                                                            )
                                                                ? 'selected'
                                                                : '' ?>
                                                        >

                                                            <?= e(
                                                                $status
                                                            ) ?>

                                                        </option>


                                                    <?php endforeach; ?>


                                                </select>


                                            </form>


                                        </td>


                                        <!-- JOINED -->

                                        <td>

                                            <?= !empty(
                                                $vendor[
                                                    'created_at'
                                                ]
                                            )
                                                ? e(
                                                    date(
                                                        'd M Y',
                                                        strtotime(
                                                            $vendor[
                                                                'created_at'
                                                            ]
                                                        )
                                                    )
                                                )
                                                : '-' ?>

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
     JAVASCRIPT
================================================================ -->

<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH SYNC
    |--------------------------------------------------------------------------
    */

    function syncVendorSidebarWidth() {

        const main =
            document.querySelector(
                '.vendors-main'
            );


        if (!main) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            main.style.marginLeft =
                '0px';

            main.style.width =
                '100%';

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | FIND SIDEBAR
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */

        if (!sidebar) {

            main.style.marginLeft =
                '260px';

            main.style.width =
                'calc(100% - 260px)';

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | REAL WIDTH
        |--------------------------------------------------------------------------
        */

        const sidebarRect =
            sidebar.getBoundingClientRect();


        if (sidebarRect.right > 0) {

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

            syncVendorSidebarWidth();


            setTimeout(
                syncVendorSidebarWidth,
                100
            );


            setTimeout(
                syncVendorSidebarWidth,
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
        syncVendorSidebarWidth
    );

</script>


</body>

</html>