<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN PAYMENTS
|--------------------------------------------------------------------------
| File: admin/payments.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CONFIG + DATABASE
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (
    !isset($_SESSION['role']) ||
    strtolower(trim($_SESSION['role'])) !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
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


if (!function_exists('money')) {

    function money($value)
    {
        return 'RM ' . number_format(
            (float) $value,
            2
        );
    }
}


if (!function_exists('payment_status_class')) {

    function payment_status_class($status)
    {
        return 'payment-status-' .
            strtolower(
                str_replace(
                    ' ',
                    '-',
                    trim((string) $status)
                )
            );
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_payment'])
) {

    $payment_id = isset($_POST['payment_id'])
        ? (int) $_POST['payment_id']
        : 0;

    $new_status = isset($_POST['payment_status'])
        ? trim($_POST['payment_status'])
        : '';

    $allowed_status = [
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ];


    if ($payment_id <= 0) {

        $error = 'Invalid payment ID.';

    } elseif (!in_array($new_status, $allowed_status, true)) {

        $error = 'Invalid payment status.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | UPDATE PAYMENT
            |--------------------------------------------------------------------------
            */

            if ($new_status === 'Paid') {

                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET
                        payment_status = :payment_status,
                        payment_date = COALESCE(
                            payment_date,
                            NOW()
                        )
                    WHERE payment_id = :payment_id
                ");

            } else {

                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET
                        payment_status = :payment_status
                    WHERE payment_id = :payment_id
                ");
            }


            $stmt->execute([
                ':payment_status' => $new_status,
                ':payment_id' => $payment_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | GET ORDER ID
            |--------------------------------------------------------------------------
            */

            $order_id = 0;

            $get_order = $pdo->prepare("
                SELECT order_id
                FROM payments
                WHERE payment_id = :payment_id
                LIMIT 1
            ");

            $get_order->execute([
                ':payment_id' => $payment_id
            ]);

            $row = $get_order->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $order_id = (int) $row['order_id'];
            }


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $action =
                'Updated payment #' .
                $payment_id .
                ' status to ' .
                $new_status;

            $target_type = 'payment';

            $log = $pdo->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES
                (
                    :admin_id,
                    :action,
                    :target_type,
                    :target_id
                )
            ");

            $log->execute([
                ':admin_id' => $admin_id,
                ':action' => $action,
                ':target_type' => $target_type,
                ':target_id' => $payment_id
            ]);


            $message =
                'Payment #' .
                $payment_id .
                ' status updated successfully.';

        } catch (PDOException $e) {

            error_log(
                "HOCHIPOHUB ADMIN PAYMENT UPDATE ERROR: "
                . $e->getMessage()
            );

            $error =
                'Unable to update payment status.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH PAYMENTS
|--------------------------------------------------------------------------
*/

$payments = [];

try {

    $sql = "
        SELECT
            p.payment_id,
            p.order_id,
            p.payment_method,
            p.payment_status,
            p.payment_date,
            p.amount,
            p.transaction_reference,

            o.order_date,
            o.order_status,

            u.user_id AS customer_id,
            u.name AS customer_name,
            u.email AS customer_email

        FROM payments p

        INNER JOIN orders o
            ON p.order_id = o.order_id

        INNER JOIN users u
            ON o.customer_id = u.user_id

        ORDER BY p.payment_id DESC
    ";


    $stmt = $pdo->query($sql);

    if ($stmt) {

        $payments = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

} catch (PDOException $e) {

    error_log(
        "HOCHIPOHUB ADMIN FETCH PAYMENTS ERROR: "
        . $e->getMessage()
    );

    $error =
        'Unable to load payments.';
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$total_payments = count($payments);

$pending_payments = 0;
$paid_payments = 0;
$failed_payments = 0;
$refunded_payments = 0;

$total_paid_amount = 0;
$total_pending_amount = 0;
$total_refunded_amount = 0;


foreach ($payments as $payment) {

    $status = $payment['payment_status'] ?? '';

    $amount = (float) (
        $payment['amount'] ?? 0
    );


    switch ($status) {

        case 'Pending':

            $pending_payments++;

            $total_pending_amount += $amount;

            break;


        case 'Paid':

            $paid_payments++;

            $total_paid_amount += $amount;

            break;


        case 'Failed':

            $failed_payments++;

            break;


        case 'Refunded':

            $refunded_payments++;

            $total_refunded_amount += $amount;

            break;
    }
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
        Payments | HochipoHub Admin
    </title>


    <!-- Poppins -->

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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- Existing Admin CSS -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /* =====================================================
           GLOBAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        body {
            font-family: 'Poppins', sans-serif !important;
            background: #f4f8ff;
        }


        .admin-main {
            font-family: 'Poppins', sans-serif !important;
        }


        /*
        =========================================================
        PAYMENT PAGE
        =========================================================
        */

        .payment-page {
            min-height: 100vh;

            padding: 34px;

            background:
                radial-gradient(
                    circle at 92% 0%,
                    rgba(37, 99, 235, 0.16),
                    transparent 27%
                ),

                radial-gradient(
                    circle at 15% 35%,
                    rgba(14, 165, 233, 0.08),
                    transparent 28%
                ),

                linear-gradient(
                    135deg,
                    #f7faff 0%,
                    #eef5ff 100%
                );
        }


        .payment-container {
            width: 100%;
            max-width: 1550px;
            margin: 0 auto;
        }


        /*
        =========================================================
        HEADER
        =========================================================
        */

        .payment-header {
            position: relative;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 24px;

            padding: 28px 30px;

            margin-bottom: 24px;

            border-radius: 24px;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #071d49 0%,
                    #0b3b91 55%,
                    #1476e8 100%
                );

            box-shadow:
                0 18px 45px
                rgba(15, 68, 150, 0.22);
        }


        .payment-header::before {
            content: "";

            position: absolute;

            width: 240px;
            height: 240px;

            right: -70px;
            top: -100px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.08);
        }


        .payment-header::after {
            content: "";

            position: absolute;

            width: 130px;
            height: 130px;

            right: 130px;
            bottom: -90px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.06);
        }


        .payment-header-content {
            position: relative;
            z-index: 2;
        }


        .payment-header h1 {
            margin: 0;

            color: #ffffff;

            font-size: 32px;
            line-height: 1.2;

            font-weight: 900;

            letter-spacing: -0.8px;
        }


        .payment-header p {
            margin: 8px 0 0;

            color: rgba(255,255,255,0.76);

            font-size: 13px;

            font-weight: 500;
        }


        .payment-header-icon {
            position: relative;

            z-index: 2;

            width: 64px;
            height: 64px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.14);

            border:
                1px solid
                rgba(255,255,255,0.20);

            color: #ffffff;

            font-size: 27px;

            font-weight: 900;

            backdrop-filter: blur(10px);

            box-shadow:
                inset 0 1px 0
                rgba(255,255,255,0.18);
        }


        /*
        =========================================================
        ALERT
        =========================================================
        */

        .payment-alert {
            display: flex;
            align-items: center;

            min-height: 50px;

            padding: 13px 18px;

            margin-bottom: 22px;

            border-radius: 14px;

            font-size: 13px;

            font-weight: 700;
        }


        .payment-alert.success {
            color: #047857;

            background:
                linear-gradient(
                    135deg,
                    #ecfdf5,
                    #d1fae5
                );

            border:
                1px solid #a7f3d0;

            box-shadow:
                0 8px 25px
                rgba(16,185,129,0.08);
        }


        .payment-alert.error {
            color: #b91c1c;

            background:
                linear-gradient(
                    135deg,
                    #fff1f2,
                    #fee2e2
                );

            border:
                1px solid #fecaca;
        }


        /*
        =========================================================
        STATISTICS
        =========================================================
        */

        .payment-stats {
            display: grid;

            grid-template-columns:
                repeat(5, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 26px;
        }


        .payment-stat {
            position: relative;

            min-height: 150px;

            padding: 21px;

            overflow: hidden;

            border-radius: 20px;

            background: rgba(255,255,255,0.88);

            border:
                1px solid
                rgba(148,163,184,0.22);

            box-shadow:
                0 12px 32px
                rgba(15, 50, 100, 0.07);

            transition:
                transform .22s ease,
                box-shadow .22s ease,
                border-color .22s ease;
        }


        .payment-stat::after {
            content: "";

            position: absolute;

            width: 90px;
            height: 90px;

            right: -30px;
            bottom: -35px;

            border-radius: 50%;

            background:
                rgba(37,99,235,0.07);
        }


        .payment-stat:hover {
            transform: translateY(-5px);

            border-color:
                rgba(37,99,235,0.28);

            box-shadow:
                0 18px 38px
                rgba(15,68,150,0.13);
        }


        .payment-stat-label {
            display: block;

            margin-bottom: 9px;

            color: #64748b;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.9px;
        }


        .payment-stat-value {
            display: block;

            color: #0f2f68;

            font-size: 28px;

            line-height: 1;

            font-weight: 900;

            letter-spacing: -0.8px;
        }


        .payment-stat-money {
            display: block;

            margin-top: 9px;

            color: #2563eb;

            font-size: 11px;

            font-weight: 700;
        }


        /*
        =========================================================
        STAT COLOR ACCENTS
        =========================================================
        */

        .payment-stat:nth-child(1) {
            border-top:
                4px solid #2563eb;
        }


        .payment-stat:nth-child(2) {
            border-top:
                4px solid #16a34a;
        }


        .payment-stat:nth-child(3) {
            border-top:
                4px solid #f59e0b;
        }


        .payment-stat:nth-child(4) {
            border-top:
                4px solid #ef4444;
        }


        .payment-stat:nth-child(5) {
            border-top:
                4px solid #8b5cf6;
        }


        /*
        =========================================================
        MAIN CARD
        =========================================================
        */

        .payment-card {
            overflow: hidden;

            border-radius: 24px;

            background: #ffffff;

            border:
                1px solid
                rgba(148,163,184,0.22);

            box-shadow:
                0 18px 45px
                rgba(15,50,100,0.08);
        }


        .payment-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 24px 26px;

            background:
                linear-gradient(
                    180deg,
                    #ffffff,
                    #fafdff
                );

            border-bottom:
                1px solid #e8eef7;
        }


        .payment-card-title {
            display: flex;
            align-items: center;

            gap: 13px;
        }


        .payment-card-title-icon {
            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0ea5e9
                );

            box-shadow:
                0 8px 18px
                rgba(37,99,235,0.22);

            font-size: 17px;

            font-weight: 900;
        }


        .payment-card-header h2 {
            margin: 0;

            color: #102f66;

            font-size: 18px;

            font-weight: 900;
        }


        .payment-card-header p {
            margin: 4px 0 0;

            color: #8a9bb5;

            font-size: 11px;

            font-weight: 500;
        }


        .payment-count {
            padding: 8px 13px;

            border-radius: 999px;

            color: #2563eb;

            background: #eff6ff;

            border:
                1px solid #dbeafe;

            font-size: 11px;

            font-weight: 800;
        }


        /*
        =========================================================
        TABLE
        =========================================================
        */

        .payment-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }


        .payment-table {
            width: 100%;

            min-width: 1150px;

            border-collapse: separate;

            border-spacing: 0;
        }


        .payment-table th {
            padding: 15px 18px;

            background:
                #f5f8fd;

            color: #71819a;

            border-bottom:
                1px solid #e5ebf4;

            font-size: 10px;

            font-weight: 900;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: 0.7px;

            white-space: nowrap;
        }


        .payment-table th:first-child {
            padding-left: 25px;
        }


        .payment-table td {
            padding: 17px 18px;

            color: #334155;

            border-bottom:
                1px solid #edf1f7;

            font-size: 12px;

            font-weight: 500;

            vertical-align: middle;
        }


        .payment-table td:first-child {
            padding-left: 25px;
        }


        .payment-table tbody tr {
            transition:
                background .18s ease;
        }


        .payment-table tbody tr:hover {
            background:
                linear-gradient(
                    90deg,
                    #f4f8ff,
                    #ffffff
                );
        }


        .payment-table tbody tr:last-child td {
            border-bottom: none;
        }


        /*
        =========================================================
        PAYMENT ID
        =========================================================
        */

        .payment-id {
            display: inline-flex;

            align-items: center;

            padding: 7px 10px;

            border-radius: 9px;

            color: #2563eb;

            background: #eff6ff;

            border:
                1px solid #dbeafe;

            font-size: 11px;

            font-weight: 900;
        }


        /*
        =========================================================
        ORDER LINK
        =========================================================
        */

        .payment-order-link {
            display: inline-flex;

            align-items: center;

            padding: 6px 9px;

            border-radius: 8px;

            color: #1d4ed8;

            background: #f1f6ff;

            text-decoration: none;

            font-size: 11px;

            font-weight: 900;

            transition: .18s ease;
        }


        .payment-order-link:hover {
            color: #ffffff;

            background:
                #2563eb;

            transform: translateY(-1px);
        }


        /*
        =========================================================
        CUSTOMER
        =========================================================
        */

        .payment-customer strong {
            display: block;

            color: #173665;

            font-size: 12px;

            font-weight: 800;
        }


        .payment-customer small {
            display: block;

            max-width: 180px;

            margin-top: 3px;

            overflow: hidden;

            color: #94a3b8;

            font-size: 10px;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        /*
        =========================================================
        PAYMENT METHOD
        =========================================================
        */

        .payment-method {
            display: inline-flex;

            align-items: center;

            padding: 6px 9px;

            border-radius: 8px;

            background: #f8fafc;

            color: #475569;

            border:
                1px solid #e2e8f0;

            font-size: 10px;

            font-weight: 800;
        }


        /*
        =========================================================
        AMOUNT
        =========================================================
        */

        .payment-amount {
            color: #059669;

            font-size: 13px;

            font-weight: 900;

            white-space: nowrap;
        }


        /*
        =========================================================
        REFERENCE
        =========================================================
        */

        .payment-reference {
            display: inline-block;

            max-width: 170px;

            padding: 6px 9px;

            overflow: hidden;

            border-radius: 8px;

            background: #f5f7fb;

            color: #64748b;

            border:
                1px solid #e5eaf2;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 9px;

            font-weight: 700;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        /*
        =========================================================
        DATE
        =========================================================
        */

        .payment-date {
            color: #64748b;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;
        }


        /*
        =========================================================
        STATUS
        =========================================================
        */

        .payment-status {
            position: relative;

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 900;

            white-space: nowrap;
        }


        .payment-status::before {
            content: "";

            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: currentColor;
        }


        .payment-status-pending {
            color: #a16207;

            background: #fef9c3;

            border:
                1px solid #fde68a;
        }


        .payment-status-paid {
            color: #15803d;

            background: #dcfce7;

            border:
                1px solid #bbf7d0;
        }


        .payment-status-failed {
            color: #dc2626;

            background: #fee2e2;

            border:
                1px solid #fecaca;
        }


        .payment-status-refunded {
            color: #7c3aed;

            background: #ede9fe;

            border:
                1px solid #ddd6fe;
        }


        /*
        =========================================================
        UPDATE SELECT
        =========================================================
        */

        .payment-form {
            margin: 0;
        }


        .payment-form select {
            min-width: 125px;

            padding: 8px 28px 8px 10px;

            border:
                1px solid #d7e0ec;

            border-radius: 10px;

            background:
                #ffffff;

            color: #334155;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;

            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }


        .payment-form select:hover {
            border-color:
                #93b4ec;
        }


        .payment-form select:focus {
            outline: none;

            border-color:
                #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,0.10);
        }


        /*
        =========================================================
        EMPTY
        =========================================================
        */

        .payment-empty {
            padding: 75px 25px;

            text-align: center;
        }


        .payment-empty-icon {
            width: 68px;
            height: 68px;

            margin:
                0 auto 17px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 20px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );

            color: #2563eb;

            font-size: 27px;

            font-weight: 900;

            box-shadow:
                0 12px 25px
                rgba(37,99,235,0.10);
        }


        .payment-empty h3 {
            margin: 0;

            color: #173665;

            font-size: 17px;

            font-weight: 900;
        }


        .payment-empty p {
            max-width: 420px;

            margin: 7px auto 0;

            color: #94a3b8;

            font-size: 11px;

            line-height: 1.7;
        }


        /*
        =========================================================
        SCROLLBAR
        =========================================================
        */

        .payment-table-wrapper::-webkit-scrollbar {
            height: 8px;
        }


        .payment-table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
        }


        .payment-table-wrapper::-webkit-scrollbar-thumb {
            background: #b9cbea;

            border-radius: 999px;
        }


        .payment-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #7fa1d4;
        }


        /*
        =========================================================
        RESPONSIVE
        =========================================================
        */

        @media (max-width: 1250px) {

            .payment-stats {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }


        @media (max-width: 850px) {

            .payment-page {
                padding: 22px;
            }


            .payment-header {
                padding: 24px;
            }


            .payment-header h1 {
                font-size: 27px;
            }


            .payment-stats {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 550px) {

            .payment-page {
                padding: 15px;
            }


            .payment-header {
                padding: 21px;

                border-radius: 19px;
            }


            .payment-header-icon {
                width: 50px;
                height: 50px;

                border-radius: 15px;

                font-size: 21px;
            }


            .payment-header h1 {
                font-size: 23px;
            }


            .payment-header p {
                font-size: 10px;
            }


            .payment-stats {
                grid-template-columns: 1fr;

                gap: 12px;
            }


            .payment-stat {
                min-height: 125px;
            }


            .payment-card {
                border-radius: 18px;
            }


            .payment-card-header {
                padding: 18px;
            }


            .payment-card-title-icon {
                width: 37px;
                height: 37px;
            }


            .payment-card-header h2 {
                font-size: 15px;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
         IMPORTANT:
         Only include admin_sidebar.php.
         DO NOT duplicate sidebar HTML here.
    ====================================================== -->

    <?php
    require_once dirname(__DIR__) . '/includes/admin_sidebar.php';
    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="admin-main">


        <div class="payment-page">

            <div class="payment-container">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <header class="payment-header">

                    <div class="payment-header-content">

                        <h1>
                            Payments
                        </h1>

                        <p>
                            Monitor and manage all customer payment transactions.
                        </p>

                    </div>


                    <div class="payment-header-icon">
                        $
                    </div>

                </header>


                <!-- =================================================
                     ALERTS
                ================================================== -->

                <?php if ($message): ?>

                    <div class="payment-alert success">

                        ✓ &nbsp;

                        <?= e($message) ?>

                    </div>

                <?php endif; ?>


                <?php if ($error): ?>

                    <div class="payment-alert error">

                        ⚠ &nbsp;

                        <?= e($error) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     STATISTICS
                ================================================== -->

                <section class="payment-stats">


                    <!-- TOTAL -->

                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Total Payments
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($total_payments) ?>
                        </strong>

                    </div>


                    <!-- PAID -->

                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Paid
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($paid_payments) ?>
                        </strong>

                        <span class="payment-stat-money">
                            <?= money($total_paid_amount) ?>
                        </span>

                    </div>


                    <!-- PENDING -->

                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Pending
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($pending_payments) ?>
                        </strong>

                        <span class="payment-stat-money">
                            <?= money($total_pending_amount) ?>
                        </span>

                    </div>


                    <!-- FAILED -->

                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Failed
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($failed_payments) ?>
                        </strong>

                    </div>


                    <!-- REFUNDED -->

                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Refunded
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($refunded_payments) ?>
                        </strong>

                        <span class="payment-stat-money">
                            <?= money($total_refunded_amount) ?>
                        </span>

                    </div>

                </section>


                <!-- =================================================
                     PAYMENT TABLE
                ================================================== -->

                <section class="payment-card">


                    <!-- CARD HEADER -->

                    <div class="payment-card-header">

                        <div class="payment-card-title">

                            <div class="payment-card-title-icon">
                                $
                            </div>

                            <div>

                                <h2>
                                    Payment Transactions
                                </h2>

                                <p>
                                    All payment records from customer orders.
                                </p>

                            </div>

                        </div>


                        <span class="payment-count">

                            <?= number_format($total_payments) ?>

                            transaction<?= $total_payments == 1 ? '' : 's' ?>

                        </span>

                    </div>


                    <!-- =================================================
                         EMPTY
                    ================================================== -->

                    <?php if (empty($payments)): ?>

                        <div class="payment-empty">

                            <div class="payment-empty-icon">
                                $
                            </div>

                            <h3>
                                No payment records
                            </h3>

                            <p>
                                Payment transactions will appear here
                                when customers make payments.
                            </p>

                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             TABLE
                        ================================================== -->

                        <div class="payment-table-wrapper">

                            <table class="payment-table">


                                <thead>

                                    <tr>

                                        <th>
                                            Payment
                                        </th>

                                        <th>
                                            Order
                                        </th>

                                        <th>
                                            Customer
                                        </th>

                                        <th>
                                            Method
                                        </th>

                                        <th>
                                            Amount
                                        </th>

                                        <th>
                                            Reference
                                        </th>

                                        <th>
                                            Date
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Update
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach ($payments as $payment): ?>

                                    <tr>


                                        <!-- PAYMENT -->

                                        <td>

                                            <span class="payment-id">

                                                #

                                                <?= e(
                                                    $payment['payment_id']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ORDER -->

                                        <td>

                                            <a
                                                class="payment-order-link"
                                                href="orders.php?view=<?= e(
                                                    $payment['order_id']
                                                ) ?>"
                                            >

                                                #

                                                <?= e(
                                                    $payment['order_id']
                                                ) ?>

                                            </a>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>

                                            <div class="payment-customer">

                                                <strong>

                                                    <?= e(
                                                        $payment[
                                                            'customer_name'
                                                        ]
                                                    ) ?>

                                                </strong>

                                                <small>

                                                    <?= e(
                                                        $payment[
                                                            'customer_email'
                                                        ]
                                                    ) ?>

                                                </small>

                                            </div>

                                        </td>


                                        <!-- METHOD -->

                                        <td>

                                            <span class="payment-method">

                                                <?= e(
                                                    $payment[
                                                        'payment_method'
                                                    ] ?: '-'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- AMOUNT -->

                                        <td>

                                            <span class="payment-amount">

                                                <?= money(
                                                    $payment['amount']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- REFERENCE -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $payment[
                                                        'transaction_reference'
                                                    ]
                                                )
                                            ): ?>

                                                <code
                                                    class="payment-reference"
                                                    title="<?= e(
                                                        $payment[
                                                            'transaction_reference'
                                                        ]
                                                    ) ?>"
                                                >

                                                    <?= e(
                                                        $payment[
                                                            'transaction_reference'
                                                        ]
                                                    ) ?>

                                                </code>

                                            <?php else: ?>

                                                <span
                                                    style="
                                                        color:#c0cad8;
                                                        font-weight:700;
                                                    "
                                                >
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <span class="payment-date">

                                                <?= e(
                                                    $payment[
                                                        'payment_date'
                                                    ] ?: '-'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="payment-status <?= e(
                                                    payment_status_class(
                                                        $payment[
                                                            'payment_status'
                                                        ]
                                                    )
                                                ) ?>"
                                            >

                                                <?= e(
                                                    $payment[
                                                        'payment_status'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- UPDATE -->

                                        <td>

                                            <form
                                                method="POST"
                                                class="payment-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="payment_id"
                                                    value="<?= e(
                                                        $payment[
                                                            'payment_id'
                                                        ]
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="update_payment"
                                                    value="1"
                                                >


                                                <select
                                                    name="payment_status"
                                                    onchange="this.form.submit()"
                                                    aria-label="Update payment status"
                                                >

                                                    <?php

                                                    $statuses = [
                                                        'Pending',
                                                        'Paid',
                                                        'Failed',
                                                        'Refunded'
                                                    ];

                                                    ?>


                                                    <?php foreach (
                                                        $statuses
                                                        as $status
                                                    ): ?>

                                                        <option
                                                            value="<?= e(
                                                                $status
                                                            ) ?>"
                                                            <?= (
                                                                $payment[
                                                                    'payment_status'
                                                                ] === $status
                                                            )
                                                                ? 'selected'
                                                                : ''
                                                            ?>
                                                        >

                                                            <?= e(
                                                                $status
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </form>

                                        </td>


                                    </tr>

                                <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>


                    <?php endif; ?>


                </section>


            </div>

        </div>


    </main>


</div>


</body>

</html>