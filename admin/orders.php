<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN ORDERS
|--------------------------------------------------------------------------
| File: admin/orders.php
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

$db = getDB();


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
    $_SESSION['role'] !== 'admin'
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
        return "RM " . number_format(
            (float) $value,
            2
        );
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $order_id = isset($_POST['order_id'])
        ? (int) $_POST['order_id']
        : 0;

    $new_status = isset($_POST['order_status'])
        ? trim($_POST['order_status'])
        : '';


    $allowed_status = [
        'Pending',
        'Processing',
        'Completed',
        'Cancelled'
    ];


    if ($order_id <= 0) {

        $error = "Invalid order ID.";

    } elseif (!in_array(
        $new_status,
        $allowed_status,
        true
    )) {

        $error = "Invalid order status.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | COMPLETED
            |--------------------------------------------------------------------------
            */

            if ($new_status === 'Completed') {

                $stmt = $db->prepare("
                    UPDATE orders
                    SET
                        order_status = :status,
                        completed_date = NOW()
                    WHERE order_id = :order_id
                ");

            } else {

                $stmt = $db->prepare("
                    UPDATE orders
                    SET
                        order_status = :status,
                        completed_date = NULL
                    WHERE order_id = :order_id
                ");
            }


            $stmt->execute([
                ':status' => $new_status,
                ':order_id' => $order_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $action =
                "Updated order #"
                . $order_id
                . " status to "
                . $new_status;


            $log = $db->prepare("
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
                ':target_type' => 'order',
                ':target_id' => $order_id
            ]);


            $message =
                "Order #"
                . $order_id
                . " status updated successfully.";


        } catch (PDOException $e) {

            error_log(
                "HOCHIPOHUB ADMIN ORDERS UPDATE ERROR: "
                . $e->getMessage()
            );

            $error =
                "Failed to update order status.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| ORDER DETAILS
|--------------------------------------------------------------------------
*/

$selected_order = null;
$order_items = [];
$vendor_orders = [];


if (isset($_GET['view'])) {

    $view_order_id =
        (int) $_GET['view'];


    if ($view_order_id > 0) {

        try {

            /*
            |--------------------------------------------------------------------------
            | MAIN ORDER
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                SELECT
                    o.order_id,
                    o.customer_id,
                    o.order_date,
                    o.total_amount,
                    o.delivery_method,
                    o.delivery_address,
                    o.tracking_number,
                    o.order_status,
                    o.completed_date,

                    u.name AS customer_name,
                    u.email AS customer_email,
                    u.phone AS customer_phone

                FROM orders o

                INNER JOIN users u
                    ON o.customer_id = u.user_id

                WHERE o.order_id = :order_id

                LIMIT 1
            ");


            $stmt->execute([
                ':order_id' => $view_order_id
            ]);


            $selected_order =
                $stmt->fetch(PDO::FETCH_ASSOC);


            /*
            |--------------------------------------------------------------------------
            | ORDER ITEMS
            |--------------------------------------------------------------------------
            */

            if ($selected_order) {

                $stmt = $db->prepare("
                    SELECT
                        od.order_detail_id,
                        od.product_id,
                        od.quantity,
                        od.unit_price,
                        od.subtotal,

                        p.product_name,
                        p.image,

                        v.vendor_id,
                        v.business_name

                    FROM order_details od

                    INNER JOIN products p
                        ON od.product_id = p.product_id

                    INNER JOIN vendors v
                        ON p.vendor_id = v.vendor_id

                    WHERE od.order_id = :order_id

                    ORDER BY od.order_detail_id ASC
                ");


                $stmt->execute([
                    ':order_id' => $view_order_id
                ]);


                $order_items =
                    $stmt->fetchAll(PDO::FETCH_ASSOC);


                /*
                |--------------------------------------------------------------------------
                | VENDOR ORDERS
                |--------------------------------------------------------------------------
                */

                $stmt = $db->prepare("
                    SELECT
                        vo.vendor_order_id,
                        vo.vendor_id,
                        vo.subtotal,
                        vo.delivery_fee,
                        vo.vendor_status,
                        vo.tracking_number,
                        vo.created_at,
                        vo.completed_at,

                        v.business_name

                    FROM vendor_orders vo

                    INNER JOIN vendors v
                        ON vo.vendor_id = v.vendor_id

                    WHERE vo.order_id = :order_id

                    ORDER BY vo.vendor_order_id ASC
                ");


                $stmt->execute([
                    ':order_id' => $view_order_id
                ]);


                $vendor_orders =
                    $stmt->fetchAll(PDO::FETCH_ASSOC);
            }


        } catch (PDOException $e) {

            error_log(
                "HOCHIPOHUB ADMIN ORDER DETAILS ERROR: "
                . $e->getMessage()
            );


            $error =
                "Unable to load order details.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH ALL ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];


try {

    $sql = "

        SELECT

            o.order_id,
            o.customer_id,
            o.order_date,
            o.total_amount,
            o.delivery_method,
            o.delivery_address,
            o.tracking_number,
            o.order_status,
            o.completed_date,

            u.name AS customer_name,
            u.email AS customer_email,

            COALESCE(

                (
                    SELECT
                        p.payment_status

                    FROM payments p

                    WHERE
                        p.order_id = o.order_id

                    ORDER BY
                        p.payment_id DESC

                    LIMIT 1
                ),

                'Pending'

            ) AS payment_status,


            COALESCE(

                (
                    SELECT
                        COUNT(*)

                    FROM order_details od

                    WHERE
                        od.order_id = o.order_id
                ),

                0

            ) AS total_items,


            COALESCE(

                (
                    SELECT
                        COUNT(*)

                    FROM vendor_orders vo

                    WHERE
                        vo.order_id = o.order_id
                ),

                0

            ) AS vendor_count


        FROM orders o


        INNER JOIN users u

            ON o.customer_id = u.user_id


        ORDER BY
            o.order_date DESC

    ";


    $stmt = $db->query($sql);


    $orders =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        "HOCHIPOHUB ADMIN FETCH ORDERS ERROR: "
        . $e->getMessage()
    );


    $error =
        "Unable to load orders.";
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$total_orders =
    count($orders);

$pending_orders = 0;
$processing_orders = 0;
$completed_orders = 0;
$cancelled_orders = 0;


foreach ($orders as $order) {

    switch ($order['order_status']) {

        case 'Pending':
            $pending_orders++;
            break;

        case 'Processing':
            $processing_orders++;
            break;

        case 'Completed':
            $completed_orders++;
            break;

        case 'Cancelled':
            $cancelled_orders++;
            break;
    }
}


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

function orderStatusClass($status)
{
    switch ($status) {

        case 'Completed':
            return 'status-completed';

        case 'Processing':
            return 'status-processing';

        case 'Cancelled':
            return 'status-cancelled';

        case 'Pending':
        default:
            return 'status-pending';
    }
}


/*
|--------------------------------------------------------------------------
| PAYMENT CLASS
|--------------------------------------------------------------------------
*/

function paymentStatusClass($status)
{
    $status = strtolower(trim($status));

    if (
        in_array(
            $status,
            ['paid', 'completed', 'success', 'successful'],
            true
        )
    ) {
        return 'payment-paid';
    }

    if (
        in_array(
            $status,
            ['failed', 'cancelled', 'refunded'],
            true
        )
    ) {
        return 'payment-failed';
    }

    return 'payment-pending';
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
        Orders | HochipoHub Admin
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


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
           HOCHIPOHUB ORDERS PAGE
        ===================================================== */

        :root {
            --blue-950: #06142f;
            --blue-900: #08245c;
            --blue-800: #0b3b91;
            --blue-700: #1255c8;
            --blue-600: #1769e8;
            --blue-500: #2583ff;
            --blue-400: #4ca0ff;
            --blue-100: #e8f2ff;
            --blue-50: #f5f9ff;

            --text-dark: #10213f;
            --text-muted: #71809b;

            --white: #ffffff;

            --green: #16a673;
            --green-bg: #e9faf3;

            --orange: #ef8b24;
            --orange-bg: #fff5e8;

            --red: #e5484d;
            --red-bg: #fff0f1;

            --shadow:
                0 18px 50px rgba(8, 36, 92, .10);

            --radius: 22px;
        }


        * {
            box-sizing: border-box;
        }


        body {
            font-family: 'Poppins', sans-serif !important;
            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(37, 131, 255, .12),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(23, 105, 232, .08),
                    transparent 25%
                ),
                #f5f8fd !important;

            color: var(--text-dark);
        }


        button,
        input,
        select,
        textarea {
            font-family: 'Poppins', sans-serif;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .admin-main {
            padding: 34px !important;
        }


        /* =====================================================
           TOP HEADER
        ===================================================== */

        .admin-header,
        .admin-topbar {
            background:
                linear-gradient(
                    135deg,
                    #071b43,
                    #0d3e98 58%,
                    #1769e8
                );

            border-radius: 26px;
            padding: 30px 34px;
            margin-bottom: 25px;

            color: white;

            box-shadow:
                0 18px 45px
                rgba(9, 54, 135, .20);

            position: relative;
            overflow: hidden;
        }


        .admin-header::after,
        .admin-topbar::after {
            content: "";
            position: absolute;

            width: 230px;
            height: 230px;

            right: -60px;
            top: -100px;

            border-radius: 50%;

            background:
                rgba(255,255,255,.08);
        }


        .admin-header h1,
        .admin-topbar h1 {
            margin: 0 0 6px;

            font-size: 34px;
            font-weight: 800;

            color: white;
            letter-spacing: -.8px;
        }


        .admin-header p,
        .admin-topbar p {
            margin: 0;

            color: rgba(255,255,255,.75);

            font-size: 14px;
        }


        /* =====================================================
           ALERTS
        ===================================================== */

        .admin-alert {
            border-radius: 15px;

            padding: 15px 18px;

            margin-bottom: 22px;

            font-size: 13px;
            font-weight: 600;

            border: 1px solid transparent;
        }


        .admin-alert.success {
            background: var(--green-bg);
            color: #08784f;
            border-color: #bdeedb;
        }


        .admin-alert.error {
            background: var(--red-bg);
            color: #b4232b;
            border-color: #ffc9cc;
        }


        /* =====================================================
           STATISTICS
        ===================================================== */

        .admin-stats {
            display: grid;

            grid-template-columns:
                repeat(5, minmax(0, 1fr));

            gap: 17px;

            margin-bottom: 25px;
        }


        .stat-card {
            background: white;

            border-radius: 20px;

            padding: 22px;

            box-shadow: var(--shadow);

            border: 1px solid #e7eef8;

            position: relative;

            overflow: hidden;

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .stat-card::before {
            content: "";

            position: absolute;

            left: 0;
            top: 0;

            width: 5px;
            height: 100%;

            background:
                linear-gradient(
                    180deg,
                    var(--blue-500),
                    var(--blue-800)
                );
        }


        .stat-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 22px 55px
                rgba(8, 36, 92, .14);
        }


        .stat-card span {
            display: block;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .8px;

            color: var(--text-muted);

            margin-bottom: 8px;
        }


        .stat-card strong {
            display: block;

            font-size: 30px;

            line-height: 1;

            color: var(--blue-900);

            font-weight: 800;
        }


        .stat-card:nth-child(2)::before {
            background: #ef8b24;
        }


        .stat-card:nth-child(3)::before {
            background: #2583ff;
        }


        .stat-card:nth-child(4)::before {
            background: #16a673;
        }


        .stat-card:nth-child(5)::before {
            background: #e5484d;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .admin-card {
            background: rgba(255,255,255,.96);

            border-radius: var(--radius);

            padding: 28px;

            margin-bottom: 25px;

            border:
                1px solid #e4ebf5;

            box-shadow: var(--shadow);
        }


        .card-header {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            margin-bottom: 24px;
        }


        .card-header h2 {
            margin: 0 0 5px;

            font-size: 21px;

            font-weight: 800;

            color: var(--blue-950);
        }


        .card-header p {
            margin: 0;

            color: var(--text-muted);

            font-size: 13px;
        }


        /* =====================================================
           ORDER INFO
        ===================================================== */

        .order-info-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 30px;
        }


        .order-info-grid > div {
            background:
                linear-gradient(
                    145deg,
                    #f7faff,
                    #edf5ff
                );

            border: 1px solid #dfeafa;

            border-radius: 18px;

            padding: 20px;
        }


        .order-info-grid h3 {
            margin: 0 0 10px;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 1px;

            color: var(--blue-700);

            font-weight: 800;
        }


        .order-info-grid p {
            margin: 5px 0;

            color: #51617c;

            font-size: 13px;

            line-height: 1.55;
        }


        .order-info-grid strong {
            font-size: 20px;

            color: var(--blue-900);

            font-weight: 800;
        }


        /* =====================================================
           SECTION TITLE
        ===================================================== */

        .section-title {
            font-size: 17px;

            font-weight: 800;

            color: var(--blue-950);

            margin:
                28px 0 15px;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .admin-table-wrapper {
            width: 100%;

            overflow-x: auto;

            border-radius: 18px;

            border: 1px solid #e5ecf6;
        }


        .admin-table {
            width: 100%;

            border-collapse: separate;

            border-spacing: 0;

            min-width: 900px;

            background: white;
        }


        .admin-table thead th {
            background:
                linear-gradient(
                    180deg,
                    #f4f8fe,
                    #edf4fc
                );

            color: #71809b;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .8px;

            font-weight: 800;

            padding: 16px 14px;

            text-align: left;

            border-bottom:
                1px solid #dfe8f3;

            white-space: nowrap;
        }


        .admin-table tbody td {
            padding: 17px 14px;

            font-size: 12px;

            color: #40516f;

            border-bottom:
                1px solid #edf1f7;

            vertical-align: middle;
        }


        .admin-table tbody tr {
            transition:
                background .2s ease;
        }


        .admin-table tbody tr:hover {
            background: #f7faff;
        }


        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }


        .admin-table strong {
            color: var(--blue-950);

            font-weight: 700;
        }


        .admin-table small {
            display: block;

            margin-top: 3px;

            color: #8995aa;

            font-size: 10px;
        }


        /* =====================================================
           ORDER ID
        ===================================================== */

        .order-id {
            display: inline-flex;

            align-items: center;

            padding: 7px 11px;

            border-radius: 10px;

            background: var(--blue-50);

            color: var(--blue-700);

            font-size: 11px;

            font-weight: 800;
        }


        /* =====================================================
           CUSTOMER
        ===================================================== */

        .customer-cell {
            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 190px;
        }


        .customer-avatar {
            width: 38px;
            height: 38px;

            flex-shrink: 0;

            border-radius: 12px;

            display: flex;

            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #1769e8,
                    #6a5cff
                );

            color: white;

            font-size: 14px;

            font-weight: 800;

            box-shadow:
                0 6px 16px
                rgba(23,105,232,.22);
        }


        /* =====================================================
           STATUS BADGES
        ===================================================== */

        .status-badge,
        .payment-badge {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;

            white-space: nowrap;
        }


        .status-badge::before,
        .payment-badge::before {
            content: "";

            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: currentColor;
        }


        .status-pending {
            color: #b66a00;
            background: #fff5df;
        }


        .status-processing {
            color: #075dbb;
            background: #e7f2ff;
        }


        .status-completed {
            color: #08784f;
            background: #e8faf2;
        }


        .status-cancelled {
            color: #b4232b;
            background: #fff0f1;
        }


        .payment-paid {
            color: #08784f;
            background: #e8faf2;
        }


        .payment-pending {
            color: #a86800;
            background: #fff5df;
        }


        .payment-failed {
            color: #b4232b;
            background: #fff0f1;
        }


        /* =====================================================
           STATUS SELECT
        ===================================================== */

        .order-status-select {
            min-width: 125px;

            border: 1px solid #d7e3f3;

            background: white;

            border-radius: 10px;

            padding: 8px 10px;

            color: var(--blue-900);

            font-size: 11px;

            font-weight: 700;

            outline: none;

            cursor: pointer;
        }


        .order-status-select:focus {
            border-color: var(--blue-500);

            box-shadow:
                0 0 0 3px
                rgba(37,131,255,.12);
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .admin-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            text-decoration: none;

            border: none;

            border-radius: 11px;

            padding: 10px 15px;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .admin-btn:hover {
            transform: translateY(-2px);
        }


        .admin-btn.primary {
            background:
                linear-gradient(
                    135deg,
                    #1769e8,
                    #0b3b91
                );

            color: white;

            box-shadow:
                0 8px 18px
                rgba(23,105,232,.22);
        }


        .admin-btn.secondary {
            background: #edf4ff;

            color: var(--blue-700);
        }


        .admin-btn.small {
            padding: 8px 12px;

            background:
                linear-gradient(
                    135deg,
                    #2583ff,
                    #1255c8
                );

            color: white;

            box-shadow:
                0 6px 14px
                rgba(18,85,200,.18);
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            text-align: center;

            padding: 55px 20px !important;

            color: #8a97ac !important;

            font-size: 13px !important;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1200px) {

            .admin-stats {
                grid-template-columns:
                    repeat(3, 1fr);
            }

            .order-info-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 800px) {

            .admin-main {
                padding: 18px !important;
            }


            .admin-header,
            .admin-topbar {
                padding: 24px;

                border-radius: 20px;
            }


            .admin-header h1,
            .admin-topbar h1 {
                font-size: 27px;
            }


            .admin-stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .order-info-grid {
                grid-template-columns: 1fr;
            }


            .admin-card {
                padding: 18px;
            }
        }


        @media (max-width: 500px) {

            .admin-stats {
                grid-template-columns: 1fr;
            }


            .card-header {
                align-items: flex-start;

                flex-direction: column;
            }
        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
         ONLY ONE SIDEBAR
    ====================================================== -->

    <?php
    require_once dirname(__DIR__) . '/includes/admin_sidebar.php';
    ?>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="admin-main">


        <!-- =================================================
             HEADER
        ================================================== -->

        <header class="admin-header">

            <div>

                <h1>
                    Orders
                </h1>

                <p>
                    Manage, monitor and track every customer order.
                </p>

            </div>

        </header>


        <!-- =================================================
             SUCCESS
        ================================================== -->

        <?php if ($message): ?>

            <div class="admin-alert success">

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error): ?>

            <div class="admin-alert error">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="admin-stats">


            <div class="stat-card">

                <span>
                    Total Orders
                </span>

                <strong>
                    <?= number_format($total_orders) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Pending
                </span>

                <strong>
                    <?= number_format($pending_orders) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Processing
                </span>

                <strong>
                    <?= number_format($processing_orders) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Completed
                </span>

                <strong>
                    <?= number_format($completed_orders) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Cancelled
                </span>

                <strong>
                    <?= number_format($cancelled_orders) ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             SELECTED ORDER DETAILS
        ================================================== -->

        <?php if ($selected_order): ?>


            <section class="admin-card">


                <div class="card-header">

                    <div>

                        <h2>

                            Order #

                            <?= e(
                                $selected_order['order_id']
                            ) ?>

                        </h2>

                        <p>

                            Placed on

                            <?= e(
                                $selected_order['order_date']
                            ) ?>

                        </p>

                    </div>


                    <a
                        href="orders.php"
                        class="admin-btn secondary"
                    >
                        ← Back to Orders
                    </a>

                </div>


                <!-- ORDER INFO -->

                <div class="order-info-grid">


                    <!-- CUSTOMER -->

                    <div>

                        <h3>
                            Customer
                        </h3>

                        <p>
                            <strong>
                                <?= e(
                                    $selected_order['customer_name']
                                ) ?>
                            </strong>
                        </p>

                        <p>
                            <?= e(
                                $selected_order['customer_email']
                            ) ?>
                        </p>

                        <p>
                            <?= e(
                                $selected_order['customer_phone']
                                ?? '-'
                            ) ?>
                        </p>

                    </div>


                    <!-- DELIVERY -->

                    <div>

                        <h3>
                            Delivery
                        </h3>

                        <p>
                            <strong>
                                <?= e(
                                    $selected_order[
                                        'delivery_method'
                                    ] ?? '-'
                                ) ?>
                            </strong>
                        </p>


                        <?php if (
                            !empty(
                                $selected_order[
                                    'delivery_address'
                                ]
                            )
                        ): ?>

                            <p>

                                <?= nl2br(
                                    e(
                                        $selected_order[
                                            'delivery_address'
                                        ]
                                    )
                                ) ?>

                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- STATUS -->

                    <div>

                        <h3>
                            Order Status
                        </h3>

                        <span
                            class="status-badge <?= e(
                                orderStatusClass(
                                    $selected_order[
                                        'order_status'
                                    ]
                                )
                            ) ?>"
                        >

                            <?= e(
                                $selected_order[
                                    'order_status'
                                ]
                            ) ?>

                        </span>

                    </div>


                    <!-- TOTAL -->

                    <div>

                        <h3>
                            Order Total
                        </h3>

                        <strong>

                            <?= money(
                                $selected_order[
                                    'total_amount'
                                ]
                            ) ?>

                        </strong>

                    </div>


                </div>


                <!-- =================================================
                     ORDER ITEMS
                ================================================== -->

                <h3 class="section-title">
                    Order Items
                </h3>


                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Vendor
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Unit Price
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            empty($order_items)
                        ): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-state"
                                >
                                    No order items found.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach (
                                $order_items as $item
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>

                                            <?= e(
                                                $item[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $item[
                                                'quantity'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= money(
                                            $item[
                                                'unit_price'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= money(
                                                $item[
                                                    'subtotal'
                                                ]
                                            ) ?>

                                        </strong>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     VENDOR SUB ORDERS
                ================================================== -->

                <h3 class="section-title">
                    Vendor Sub-orders
                </h3>


                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>
                                    Vendor
                                </th>

                                <th>
                                    Subtotal
                                </th>

                                <th>
                                    Delivery Fee
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Tracking
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            empty($vendor_orders)
                        ): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-state"
                                >
                                    No vendor orders found.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach (
                                $vendor_orders as $vo
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>

                                            <?= e(
                                                $vo[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= money(
                                            $vo[
                                                'subtotal'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= money(
                                            $vo[
                                                'delivery_fee'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <span class="status-badge status-processing">

                                            <?= e(
                                                $vo[
                                                    'vendor_status'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= e(
                                            $vo[
                                                'tracking_number'
                                            ] ?: '-'
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


            </section>


        <?php endif; ?>


        <!-- =================================================
             ALL ORDERS
        ================================================== -->

        <section class="admin-card">


            <div class="card-header">

                <div>

                    <h2>
                        All Orders
                    </h2>

                    <p>
                        Customer orders and payment information.
                    </p>

                </div>

            </div>


            <div class="admin-table-wrapper">

                <table class="admin-table">


                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Items
                            </th>

                            <th>
                                Vendors
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        empty($orders)
                    ): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-state"
                            >

                                No orders found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $orders as $order
                        ): ?>


                            <tr>


                                <!-- ORDER -->

                                <td>

                                    <span class="order-id">

                                        #

                                        <?= e(
                                            $order[
                                                'order_id'
                                            ]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <div class="customer-cell">


                                        <div class="customer-avatar">

                                            <?= e(
                                                strtoupper(
                                                    substr(
                                                        $order[
                                                            'customer_name'
                                                        ],
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>

                                        </div>


                                        <div>

                                            <strong>

                                                <?= e(
                                                    $order[
                                                        'customer_name'
                                                    ]
                                                ) ?>

                                            </strong>

                                            <small>

                                                <?= e(
                                                    $order[
                                                        'customer_email'
                                                    ]
                                                ) ?>

                                            </small>

                                        </div>


                                    </div>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= e(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $order[
                                                    'order_date'
                                                ]
                                            )
                                        )
                                    ) ?>

                                    <small>

                                        <?= e(
                                            date(
                                                'h:i A',
                                                strtotime(
                                                    $order[
                                                        'order_date'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </small>

                                </td>


                                <!-- ITEMS -->

                                <td>

                                    <strong>

                                        <?= e(
                                            $order[
                                                'total_items'
                                            ]
                                        ) ?>

                                    </strong>

                                    <small>
                                        item(s)
                                    </small>

                                </td>


                                <!-- VENDORS -->

                                <td>

                                    <strong>

                                        <?= e(
                                            $order[
                                                'vendor_count'
                                            ]
                                        ) ?>

                                    </strong>

                                    <small>
                                        vendor(s)
                                    </small>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <strong>

                                        <?= money(
                                            $order[
                                                'total_amount'
                                            ]
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- PAYMENT -->

                                <td>

                                    <span
                                        class="payment-badge <?= e(
                                            paymentStatusClass(
                                                $order[
                                                    'payment_status'
                                                ]
                                            )
                                        ) ?>"
                                    >

                                        <?= e(
                                            $order[
                                                'payment_status'
                                            ]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <form
                                        method="POST"
                                        action=""
                                    >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= e(
                                                $order[
                                                    'order_id'
                                                ]
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="update_status"
                                            value="1"
                                        >


                                        <select
                                            name="order_status"
                                            class="order-status-select"
                                            onchange="this.form.submit()"
                                        >

                                            <?php foreach (
                                                [
                                                    'Pending',
                                                    'Processing',
                                                    'Completed',
                                                    'Cancelled'
                                                ]
                                                as $status
                                            ): ?>

                                                <option
                                                    value="<?= e(
                                                        $status
                                                    ) ?>"
                                                    <?= (
                                                        $order[
                                                            'order_status'
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


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="orders.php?view=<?= e(
                                            $order[
                                                'order_id'
                                            ]
                                        ) ?>"
                                        class="admin-btn small"
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