<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . site_url('index.php?login=required'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET ADMIN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        profile_image,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

if (!$admin) {
    session_destroy();

    header('Location: ' . site_url('index.php'));
    exit;
}

if ($admin['role'] !== 'admin') {
    header('Location: ' . site_url('dashboard.php'));
    exit;
}

if ($admin['status'] !== 'active') {
    session_destroy();

    header('Location: ' . site_url('index.php?account=inactive'));
    exit;
}

/*
|--------------------------------------------------------------------------
| DEFAULT STATS
|--------------------------------------------------------------------------
*/

$stats = [
    'users' => 0,
    'vendors' => 0,
    'pending_vendors' => 0,
    'products' => 0,
    'orders' => 0,
    'pending_orders' => 0,
    'sales' => 0,
    'commission' => 0,
    'reviews' => 0
];

$recentOrders = [];
$recentProducts = [];
$recentVendors = [];
$recentReviews = [];

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['users'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| APPROVED VENDORS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM vendors
    WHERE approval_status = 'Approved'
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['vendors'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| PENDING VENDOR APPLICATIONS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM vendor_applications
    WHERE status = 'Pending'
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['pending_vendors'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['products'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| TOTAL ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['orders'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| PENDING ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Pending'
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['pending_orders'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN order_status != 'Cancelled'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total
    FROM orders
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['sales'] =
        (float) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| TOTAL COMMISSION
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN status != 'Cancelled'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS total
    FROM commission
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['commission'] =
        (float) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM reviews
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['reviews'] =
        (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.order_status,
        u.name AS customer_name

    FROM orders o

    LEFT JOIN users u
        ON o.customer_id = u.user_id

    ORDER BY o.order_date DESC

    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentOrders[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| RECENT PRODUCTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        p.product_id,
        p.product_name,
        p.price,
        p.stock_quantity,
        p.status,
        p.created_at,

        v.business_name

    FROM products p

    LEFT JOIN vendors v
        ON p.vendor_id = v.vendor_id

    ORDER BY p.created_at DESC

    LIMIT 6
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentProducts[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| RECENT VENDORS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        v.vendor_id,
        v.business_name,
        v.approval_status,
        v.delivery_method,

        u.name AS owner_name,
        u.email

    FROM vendors v

    LEFT JOIN users u
        ON v.user_id = u.user_id

    ORDER BY v.vendor_id DESC

    LIMIT 6
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentVendors[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| RECENT REVIEWS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        r.review_id,
        r.rating,
        r.comment,
        r.created_at,

        u.name AS customer_name,

        p.product_name

    FROM reviews r

    LEFT JOIN users u
        ON r.customer_id = u.user_id

    LEFT JOIN products p
        ON r.product_id = p.product_id

    ORDER BY r.created_at DESC

    LIMIT 6
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentReviews[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| FORMAT HELPERS
|--------------------------------------------------------------------------
*/

function admin_dashboard_money($amount)
{
    return 'RM ' .
        number_format(
            (float) $amount,
            2
        );
}

function admin_dashboard_date($date)
{
    if (!$date) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );
}

function admin_dashboard_status($status)
{
    return 'admin-status-' .
        strtolower(
            str_replace(
                ' ',
                '-',
                $status
            )
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
        Admin Dashboard |
        <?php echo htmlspecialchars(SITE_NAME); ?>
    </title>

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/admin.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >

    <style>

        .admin-dashboard {
            min-height: 100vh;
            padding: 35px 0 80px;
            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(37,99,235,.18),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 5%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                linear-gradient(
                    145deg,
                    #020617,
                    #061a35 55%,
                    #020617
                );
            color: #f8fafc;
        }

        .admin-dashboard-container {
            width: 90%;
            max-width: 1450px;
            margin: auto;
        }

        .admin-dashboard-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            margin-bottom: 20px;
            padding: 30px;
            border: 1px solid rgba(56,189,248,.15);
            border-radius: 24px;
            background:
                linear-gradient(
                    135deg,
                    rgba(15,23,42,.96),
                    rgba(8,47,73,.70)
                );
        }

        .admin-dashboard-hero::after {
            content: "";
            position: absolute;
            width: 270px;
            height: 270px;
            right: -100px;
            top: -130px;
            border-radius: 50%;
            background: rgba(14,165,233,.10);
        }

        .admin-dashboard-hero-content {
            position: relative;
            z-index: 2;
        }

        .admin-dashboard-eyebrow {
            margin-bottom: 8px;
            color: #38bdf8;
            font-size: 8px;
            font-weight: 950;
            letter-spacing: 1.7px;
        }

        .admin-dashboard-hero h1 {
            margin: 0;
            font-size: clamp(29px, 4vw, 45px);
            line-height: 1;
            font-weight: 950;
            letter-spacing: -1.5px;
        }

        .admin-dashboard-hero h1 span {
            color: #38bdf8;
        }

        .admin-dashboard-hero p {
            max-width: 650px;
            margin: 11px 0 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.7;
        }

        .admin-dashboard-badge {
            position: relative;
            z-index: 2;
            min-width: 145px;
            padding: 17px;
            border: 1px solid rgba(56,189,248,.14);
            border-radius: 16px;
            background: rgba(2,6,23,.45);
            text-align: center;
        }

        .admin-dashboard-badge small {
            display: block;
            margin-bottom: 5px;
            color: #475569;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .admin-dashboard-badge strong {
            color: #7dd3fc;
            font-size: 17px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .admin-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 13px;
            margin-bottom: 20px;
        }

        .admin-dashboard-stat {
            position: relative;
            overflow: hidden;
            padding: 19px;
            border: 1px solid rgba(148,163,184,.09);
            border-radius: 17px;
            background: rgba(15,23,42,.78);
            transition: .2s ease;
        }

        .admin-dashboard-stat:hover {
            transform: translateY(-3px);
            border-color: rgba(56,189,248,.25);
        }

        .admin-dashboard-stat-label {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .9px;
            text-transform: uppercase;
        }

        .admin-dashboard-stat-value {
            display: block;
            color: #f8fafc;
            font-size: 23px;
            font-weight: 950;
        }

        .admin-dashboard-stat-value.blue {
            color: #7dd3fc;
        }

        .admin-dashboard-stat-value.green {
            color: #86efac;
        }

        .admin-dashboard-stat-value.yellow {
            color: #fde047;
        }

        .admin-dashboard-stat-value.red {
            color: #fca5a5;
        }

        .admin-dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 17px;
        }

        .admin-dashboard-card {
            overflow: hidden;
            border: 1px solid rgba(148,163,184,.09);
            border-radius: 19px;
            background: rgba(15,23,42,.78);
        }

        .admin-dashboard-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 17px 19px;
            border-bottom: 1px solid rgba(148,163,184,.07);
        }

        .admin-dashboard-card-header h2 {
            margin: 0;
            color: #e2e8f0;
            font-size: 13px;
            font-weight: 900;
        }

        .admin-dashboard-card-header span {
            color: #475569;
            font-size: 8px;
        }

        .admin-dashboard-link {
            color: #38bdf8;
            font-size: 8px;
            font-weight: 900;
            text-decoration: none;
        }

        .admin-dashboard-link:hover {
            color: #7dd3fc;
        }

        .admin-order {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 19px;
            border-bottom: 1px solid rgba(148,163,184,.05);
        }

        .admin-order:last-child {
            border-bottom: 0;
        }

        .admin-order-title {
            display: block;
            color: #cbd5e1;
            font-size: 10px;
            font-weight: 850;
        }

        .admin-order-meta {
            display: block;
            margin-top: 4px;
            color: #475569;
            font-size: 8px;
        }

        .admin-order-right {
            flex-shrink: 0;
            text-align: right;
        }

        .admin-order-price {
            display: block;
            color: #f8fafc;
            font-size: 9px;
            font-weight: 900;
        }

        .admin-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 5px;
            padding: 4px 7px;
            border-radius: 99px;
            font-size: 7px;
            font-weight: 900;
        }

        .admin-status::before {
            content: "";
            width: 4px;
            height: 4px;
            border-radius: 50%;
        }

        .admin-status-pending {
            background: rgba(250,204,21,.08);
            color: #fde047;
        }

        .admin-status-pending::before {
            background: #facc15;
        }

        .admin-status-processing {
            background: rgba(56,189,248,.08);
            color: #7dd3fc;
        }

        .admin-status-processing::before {
            background: #38bdf8;
        }

        .admin-status-completed,
        .admin-status-paid,
        .admin-status-approved {
            background: rgba(34,197,94,.08);
            color: #86efac;
        }

        .admin-status-completed::before,
        .admin-status-paid::before,
        .admin-status-approved::before {
            background: #22c55e;
        }

        .admin-status-cancelled,
        .admin-status-rejected {
            background: rgba(239,68,68,.08);
            color: #fca5a5;
        }

        .admin-status-cancelled::before,
        .admin-status-rejected::before {
            background: #ef4444;
        }

        .admin-product {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px 19px;
            border-bottom: 1px solid rgba(148,163,184,.05);
        }

        .admin-product:last-child {
            border-bottom: 0;
        }

        .admin-product-image {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            overflow: hidden;
            border: 1px solid rgba(148,163,184,.08);
            border-radius: 11px;
            background: rgba(2,6,23,.65);
            color: #334155;
            font-size: 14px;
        }

        .admin-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-product-info {
            min-width: 0;
            flex: 1;
        }

        .admin-product-name {
            display: block;
            overflow: hidden;
            color: #cbd5e1;
            font-size: 9px;
            font-weight: 850;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-product-meta {
            display: block;
            margin-top: 4px;
            color: #475569;
            font-size: 7px;
        }

        .admin-product-price {
            color: #7dd3fc;
            font-size: 9px;
            font-weight: 900;
        }

        .admin-vendor {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 13px 19px;
            border-bottom: 1px solid rgba(148,163,184,.05);
        }

        .admin-vendor:last-child {
            border-bottom: 0;
        }

        .admin-vendor-name {
            color: #cbd5e1;
            font-size: 9px;
            font-weight: 850;
        }

        .admin-vendor-owner {
            display: block;
            margin-top: 3px;
            color: #475569;
            font-size: 7px;
        }

        .admin-review {
            padding: 14px 19px;
            border-bottom: 1px solid rgba(148,163,184,.05);
        }

        .admin-review:last-child {
            border-bottom: 0;
        }

        .admin-review-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .admin-review-user {
            color: #cbd5e1;
            font-size: 9px;
            font-weight: 850;
        }

        .admin-review-rating {
            color: #facc15;
            font-size: 9px;
            font-weight: 900;
        }

        .admin-review-product {
            margin-top: 4px;
            color: #475569;
            font-size: 7px;
        }

        .admin-review-comment {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 8px;
            line-height: 1.5;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 9px;
            padding: 17px;
        }

        .admin-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px;
            border: 1px solid rgba(148,163,184,.08);
            border-radius: 12px;
            background: rgba(2,6,23,.30);
            color: #94a3b8;
            font-size: 8px;
            font-weight: 850;
            text-decoration: none;
            transition: .2s ease;
        }

        .admin-action:hover {
            transform: translateY(-2px);
            border-color: rgba(56,189,248,.25);
            background: rgba(14,165,233,.06);
            color: #7dd3fc;
        }

        .admin-action-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 25px;
            height: 25px;
            border-radius: 8px;
            background: rgba(14,165,233,.08);
            color: #38bdf8;
            font-size: 8px;
            font-weight: 950;
        }

        .admin-empty {
            padding: 35px 20px;
            color: #475569;
            font-size: 9px;
            text-align: center;
        }

        @media (max-width: 1050px) {

            .admin-dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .admin-dashboard-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 650px) {

            .admin-dashboard-container {
                width: 92%;
            }

            .admin-dashboard-hero {
                flex-direction: column;
                align-items: flex-start;
                padding: 25px;
            }

            .admin-dashboard-badge {
                width: 100%;
                box-sizing: border-box;
            }

            .admin-dashboard-stats {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 480px) {

            .admin-actions {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>


<main class="admin-dashboard">

    <div class="admin-dashboard-container">


        <!-- HERO -->

        <section class="admin-dashboard-hero">

            <div class="admin-dashboard-hero-content">

                <div class="admin-dashboard-eyebrow">
                    ADMIN CONTROL CENTER
                </div>

                <h1>
                    Hey,
                    <span>
                        <?php
                        echo htmlspecialchars(
                            $admin['name']
                        );
                        ?>
                    </span>.
                </h1>

                <p>
                    Manage users, vendors, products,
                    orders, payments and overall
                    HochipoHub marketplace activity
                    from one place.
                </p>

            </div>


            <div class="admin-dashboard-badge">

                <small>
                    ACCOUNT ROLE
                </small>

                <strong>
                    ADMIN
                </strong>

            </div>

        </section>


        <!-- STATS -->

        <section class="admin-dashboard-stats">


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Total Users
                </span>

                <strong class="admin-dashboard-stat-value blue">
                    <?php
                    echo number_format(
                        $stats['users']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Approved Vendors
                </span>

                <strong class="admin-dashboard-stat-value green">
                    <?php
                    echo number_format(
                        $stats['vendors']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Products
                </span>

                <strong class="admin-dashboard-stat-value blue">
                    <?php
                    echo number_format(
                        $stats['products']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Total Orders
                </span>

                <strong class="admin-dashboard-stat-value">
                    <?php
                    echo number_format(
                        $stats['orders']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Total Sales
                </span>

                <strong class="admin-dashboard-stat-value green">
                    <?php
                    echo admin_dashboard_money(
                        $stats['sales']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Commission
                </span>

                <strong class="admin-dashboard-stat-value blue">
                    <?php
                    echo admin_dashboard_money(
                        $stats['commission']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Pending Vendors
                </span>

                <strong class="admin-dashboard-stat-value yellow">
                    <?php
                    echo number_format(
                        $stats['pending_vendors']
                    );
                    ?>
                </strong>

            </div>


            <div class="admin-dashboard-stat">

                <span class="admin-dashboard-stat-label">
                    Pending Orders
                </span>

                <strong class="admin-dashboard-stat-value red">
                    <?php
                    echo number_format(
                        $stats['pending_orders']
                    );
                    ?>
                </strong>

            </div>

        </section>


        <!-- MAIN GRID -->

        <div class="admin-dashboard-grid">


            <!-- RECENT ORDERS -->

            <section class="admin-dashboard-card">

                <div class="admin-dashboard-card-header">

                    <div>

                        <h2>
                            Recent Orders
                        </h2>

                        <span>
                            Latest customer transactions
                        </span>

                    </div>

                    <a
                        href="<?php
                        echo site_url(
                            'admin/orders.php'
                        );
                        ?>"
                        class="admin-dashboard-link"
                    >
                        MANAGE →
                    </a>

                </div>


                <?php if (
                    empty($recentOrders)
                ): ?>

                    <div class="admin-empty">
                        No orders found.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentOrders
                        as $order
                    ): ?>

                        <div class="admin-order">

                            <div>

                                <span class="admin-order-title">

                                    Order #
                                    <?php
                                    echo (int)
                                        $order['order_id'];
                                    ?>

                                    ·

                                    <?php
                                    echo htmlspecialchars(
                                        $order[
                                            'customer_name'
                                        ] ??
                                        'Unknown Customer'
                                    );
                                    ?>

                                </span>

                                <span class="admin-order-meta">

                                    <?php
                                    echo admin_dashboard_date(
                                        $order['order_date']
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="admin-order-right">

                                <span class="admin-order-price">

                                    <?php
                                    echo admin_dashboard_money(
                                        $order['total_amount']
                                    );
                                    ?>

                                </span>


                                <span
                                    class="
                                        admin-status
                                        <?php
                                        echo admin_dashboard_status(
                                            $order[
                                                'order_status'
                                            ]
                                        );
                                        ?>
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $order[
                                            'order_status'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>

            </section>


            <!-- QUICK ACTIONS -->

            <section class="admin-dashboard-card">

                <div class="admin-dashboard-card-header">

                    <div>

                        <h2>
                            Quick Actions
                        </h2>

                        <span>
                            Admin management
                        </span>

                    </div>

                </div>


                <div class="admin-actions">


                    <a
                        href="<?php
                        echo site_url(
                            'admin/users.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            <?php
                            echo number_format(
                                $stats['users']
                            );
                            ?>
                        </span>

                        Users

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/vendors.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            <?php
                            echo number_format(
                                $stats['pending_vendors']
                            );
                            ?>
                        </span>

                        Vendors

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/products.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            <?php
                            echo number_format(
                                $stats['products']
                            );
                            ?>
                        </span>

                        Products

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/orders.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            <?php
                            echo number_format(
                                $stats['pending_orders']
                            );
                            ?>
                        </span>

                        Orders

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/payments.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            RM
                        </span>

                        Payments

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/commission.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            %
                        </span>

                        Commission

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/reviews.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            <?php
                            echo number_format(
                                $stats['reviews']
                            );
                            ?>
                        </span>

                        Reviews

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/settings.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">
                            ⚙
                        </span>

                        Settings

                    </a>

                </div>

            </section>


            <!-- RECENT PRODUCTS -->

            <section class="admin-dashboard-card">

                <div class="admin-dashboard-card-header">

                    <div>

                        <h2>
                            Recent Products
                        </h2>

                        <span>
                            Latest catalogue activity
                        </span>

                    </div>

                    <a
                        href="<?php
                        echo site_url(
                            'admin/products.php'
                        );
                        ?>"
                        class="admin-dashboard-link"
                    >
                        PRODUCTS →
                    </a>

                </div>


                <?php if (
                    empty($recentProducts)
                ): ?>

                    <div class="admin-empty">
                        No products found.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentProducts
                        as $product
                    ): ?>

                        <div class="admin-product">


                            <div class="admin-product-image">

                                <?php
                                $productImage =
                                    $product['image']
                                    ?? '';
                                ?>

                                <?php if (
                                    $productImage !== ''
                                ): ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            site_url(
                                                'uploads/products/'
                                                . $productImage
                                            )
                                        );
                                        ?>"
                                        alt="<?php
                                        echo htmlspecialchars(
                                            $product[
                                                'product_name'
                                            ]
                                        );
                                        ?>"
                                        onerror="
                                            this.style.display='none';
                                        "
                                    >

                                <?php else: ?>

                                    ◈

                                <?php endif; ?>

                            </div>


                            <div class="admin-product-info">

                                <span class="admin-product-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $product[
                                            'product_name'
                                        ]
                                    );
                                    ?>

                                </span>

                                <span class="admin-product-meta">

                                    <?php
                                    echo htmlspecialchars(
                                        $product[
                                            'business_name'
                                        ]
                                        ??
                                        'Unknown Vendor'
                                    );
                                    ?>

                                    · Stock:

                                    <?php
                                    echo number_format(
                                        (int)
                                        $product[
                                            'stock_quantity'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                            <span class="admin-product-price">

                                <?php
                                echo admin_dashboard_money(
                                    $product['price']
                                );
                                ?>

                            </span>

                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>

            </section>


            <!-- RECENT VENDORS -->

            <section class="admin-dashboard-card">

                <div class="admin-dashboard-card-header">

                    <div>

                        <h2>
                            Recent Vendors
                        </h2>

                        <span>
                            Vendor accounts
                        </span>

                    </div>

                    <a
                        href="<?php
                        echo site_url(
                            'admin/vendors.php'
                        );
                        ?>"
                        class="admin-dashboard-link"
                    >
                        MANAGE →
                    </a>

                </div>


                <?php if (
                    empty($recentVendors)
                ): ?>

                    <div class="admin-empty">
                        No vendors found.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentVendors
                        as $vendor
                    ): ?>

                        <div class="admin-vendor">

                            <div>

                                <div class="admin-vendor-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $vendor[
                                            'business_name'
                                        ]
                                        ??
                                        'Unknown Vendor'
                                    );
                                    ?>

                                </div>

                                <span class="admin-vendor-owner">

                                    <?php
                                    echo htmlspecialchars(
                                        $vendor[
                                            'owner_name'
                                        ]
                                        ??
                                        'Unknown Owner'
                                    );
                                    ?>

                                </span>

                            </div>


                            <span
                                class="
                                    admin-status
                                    <?php
                                    echo admin_dashboard_status(
                                        $vendor[
                                            'approval_status'
                                        ]
                                    );
                                    ?>
                                "
                            >

                                <?php
                                echo htmlspecialchars(
                                    $vendor[
                                        'approval_status'
                                    ]
                                );
                                ?>

                            </span>

                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>

            </section>


            <!-- RECENT REVIEWS -->

            <section class="admin-dashboard-card">

                <div class="admin-dashboard-card-header">

                    <div>

                        <h2>
                            Recent Reviews
                        </h2>

                        <span>
                            Latest customer feedback
                        </span>

                    </div>

                    <a
                        href="<?php
                        echo site_url(
                            'admin/reviews.php'
                        );
                        ?>"
                        class="admin-dashboard-link"
                    >
                        REVIEWS →
                    </a>

                </div>


                <?php if (
                    empty($recentReviews)
                ): ?>

                    <div class="admin-empty">
                        No reviews found.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentReviews
                        as $review
                    ): ?>

                        <div class="admin-review">

                            <div class="admin-review-top">

                                <span class="admin-review-user">

                                    <?php
                                    echo htmlspecialchars(
                                        $review[
                                            'customer_name'
                                        ]
                                        ??
                                        'Customer'
                                    );
                                    ?>

                                </span>

                                <span class="admin-review-rating">

                                    <?php
                                    echo str_repeat(
                                        '★',
                                        max(
                                            0,
                                            min(
                                                5,
                                                (int)
                                                $review[
                                                    'rating'
                                                ]
                                            )
                                        )
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="admin-review-product">

                                <?php
                                echo htmlspecialchars(
                                    $review[
                                        'product_name'
                                    ]
                                    ??
                                    'Product'
                                );
                                ?>

                            </div>


                            <?php if (
                                !empty(
                                    $review['comment']
                                )
                            ): ?>

                                <p class="admin-review-comment">

                                    <?php
                                    echo htmlspecialchars(
                                        $review[
                                            'comment'
                                        ]
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>

            </section>


            <!-- ADMIN ATTENTION -->

            <section class="admin-dashboard-card">

                <div class="admin-dashboard-card-header">

                    <div>

                        <h2>
                            Admin Attention
                        </h2>

                        <span>
                            Items requiring action
                        </span>

                    </div>

                </div>


                <div class="admin-actions">


                    <a
                        href="<?php
                        echo site_url(
                            'admin/vendors.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">

                            <?php
                            echo number_format(
                                $stats[
                                    'pending_vendors'
                                ]
                            );
                            ?>

                        </span>

                        Pending Vendor Applications

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/orders.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <span class="admin-action-number">

                            <?php
                            echo number_format(
                                $stats[
                                    'pending_orders'
                                ]
                            );
                            ?>

                        </span>

                        Pending Orders

                    </a>

                </div>

            </section>


        </div>

    </div>

</main>


<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

</body>

</html>