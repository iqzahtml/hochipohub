<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminId = (int) $_SESSION['user_id'];

$adminStmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        profile_image
    FROM users
    WHERE user_id = :user_id
    AND role = 'admin'
    LIMIT 1
");

$adminStmt->execute([
    ':user_id' => $adminId
]);

$admin = $adminStmt->fetch();

if (!$admin) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

function adminCount(PDO $db, string $sql): int
{
    try {
        return (int) $db
            ->query($sql)
            ->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$totalUsers = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
    "
);

$totalVendors = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM vendors
    "
);

$pendingVendors = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM vendors
    WHERE approval_status = 'Pending'
    "
);

$totalProducts = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM products
    "
);

$totalOrders = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM orders
    "
);

$pendingOrders = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'Pending'
    "
);

$totalReviews = adminCount(
    $db,
    "
    SELECT COUNT(*)
    FROM reviews
    "
);

/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/

$totalSales = 0;

try {

    $salesStmt = $db->query("
        SELECT COALESCE(
            SUM(total_amount),
            0
        )
        FROM orders
        WHERE status NOT IN (
            'Cancelled',
            'Rejected'
        )
    ");

    $totalSales =
        (float) $salesStmt->fetchColumn();

} catch (Throwable $e) {

    $totalSales = 0;
}

/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$recentOrders = [];

try {

    $recentOrderStmt = $db->query("
        SELECT
            o.order_id,
            o.order_number,
            o.total_amount,
            o.status,
            o.created_at,
            u.name AS customer_name
        FROM orders o
        INNER JOIN users u
            ON u.user_id = o.customer_id
        ORDER BY o.created_at DESC
        LIMIT 6
    ");

    $recentOrders =
        $recentOrderStmt->fetchAll();

} catch (Throwable $e) {

    $recentOrders = [];
}

/*
|--------------------------------------------------------------------------
| RECENT VENDORS
|--------------------------------------------------------------------------
*/

$recentVendors = [];

try {

    $recentVendorStmt = $db->query("
        SELECT
            v.vendor_id,
            v.business_name,
            v.approval_status,
            v.created_at,
            u.name AS owner_name
        FROM vendors v
        INNER JOIN users u
            ON u.user_id = v.user_id
        ORDER BY v.created_at DESC
        LIMIT 5
    ");

    $recentVendors =
        $recentVendorStmt->fetchAll();

} catch (Throwable $e) {

    $recentVendors = [];
}

/*
|--------------------------------------------------------------------------
| MONTHLY SALES
|--------------------------------------------------------------------------
*/

$monthlySales = [];

try {

    $monthlyStmt = $db->query("
        SELECT
            DATE_FORMAT(
                created_at,
                '%b'
            ) AS month_name,
            COALESCE(
                SUM(total_amount),
                0
            ) AS total_sales
        FROM orders
        WHERE status NOT IN (
            'Cancelled',
            'Rejected'
        )
        AND created_at >= DATE_SUB(
            CURDATE(),
            INTERVAL 5 MONTH
        )
        GROUP BY
            YEAR(created_at),
            MONTH(created_at),
            DATE_FORMAT(
                created_at,
                '%b'
            )
        ORDER BY
            YEAR(created_at),
            MONTH(created_at)
    ");

    $monthlySales =
        $monthlyStmt->fetchAll();

} catch (Throwable $e) {

    $monthlySales = [];
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
        Admin Dashboard | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .admin-page {
            min-height: 100vh;
            padding: 35px 4%;
            background:
                radial-gradient(
                    circle at 10% 5%,
                    rgba(37,99,235,.13),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(14,165,233,.10),
                    transparent 25%
                ),
                #f8fbff;
        }

        .admin-container {
            max-width: 1400px;
            margin: auto;
        }

        .admin-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            margin-bottom: 25px;
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    #0b2a66,
                    #1d4ed8 55%,
                    #0284c7
                );
            color: white;
            box-shadow:
                0 25px 60px rgba(29,78,216,.22);
        }

        .admin-hero::before {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            top: -170px;
            right: -80px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.08);
        }

        .admin-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            bottom: -100px;
            right: 250px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.05);
        }

        .admin-hero-content {
            position: relative;
            z-index: 2;
        }

        .admin-kicker {
            margin-bottom: 8px;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: .7;
        }

        .admin-hero h1 {
            margin: 0 0 8px;
            font-size: clamp(
                28px,
                5vw,
                45px
            );
            font-weight: 950;
        }

        .admin-hero p {
            max-width: 700px;
            margin: 0;
            color: rgba(
                255,
                255,
                255,
                .75
            );
            font-size: 12px;
            line-height: 1.7;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 20px;
            padding: 8px 12px;
            border-radius: 999px;
            background:
                rgba(255,255,255,.12);
            font-size: 9px;
            font-weight: 900;
        }

        .admin-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow:
                0 0 10px #4ade80;
        }

        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 22px;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px rgba(
                    15,
                    23,
                    42,
                    .055
                );
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 80px;
            height: 80px;
            right: -30px;
            bottom: -30px;
            border-radius: 50%;
            background:
                rgba(37,99,235,.06);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 16px;
        }

        .stat-value {
            color: #0f172a;
            font-size: 28px;
            font-weight: 950;
        }

        .stat-note {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 9px;
        }

        .stat-note.warning {
            color: #d97706;
            font-weight: 900;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.6fr)
                minmax(300px, 1fr);
            gap: 20px;
        }

        .panel {
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 22px;
            background: white;
            box-shadow:
                0 12px 35px rgba(
                    15,
                    23,
                    42,
                    .05
                );
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 20px 22px;
            border-bottom: 1px solid #eff6ff;
        }

        .panel-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .panel-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 9px;
            font-weight: 900;
        }

        .panel-body {
            padding: 20px 22px;
        }

        .chart-area {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            height: 230px;
            padding-top: 15px;
        }

        .chart-column {
            display: flex;
            flex: 1;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            gap: 8px;
        }

        .chart-value {
            color: #2563eb;
            font-size: 8px;
            font-weight: 900;
        }

        .chart-bar-wrap {
            display: flex;
            align-items: flex-end;
            width: 100%;
            height: 175px;
        }

        .chart-bar {
            width: 100%;
            min-height: 4px;
            border-radius:
                8px 8px 3px 3px;
            background:
                linear-gradient(
                    180deg,
                    #38bdf8,
                    #2563eb
                );
        }

        .chart-label {
            color: #94a3b8;
            font-size: 9px;
            font-weight: 800;
        }

        .sales-total {
            margin-bottom: 10px;
            color: #0f172a;
            font-size: 26px;
            font-weight: 950;
        }

        .sales-total span {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
        }

        .order-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .order-item {
            display: grid;
            grid-template-columns:
                1fr auto auto;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-radius: 13px;
            background: #f8fbff;
        }

        .order-info strong {
            display: block;
            margin-bottom: 3px;
            color: #0f172a;
            font-size: 10px;
            font-weight: 950;
        }

        .order-info span {
            color: #94a3b8;
            font-size: 8px;
        }

        .order-amount {
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 950;
        }

        .status {
            padding: 6px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 900;
            text-transform: capitalize;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.approved,
        .status.completed,
        .status.delivered {
            background: #dcfce7;
            color: #166534;
        }

        .status.cancelled,
        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status.processing,
        .status.shipped {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .vendor-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .vendor-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border-radius: 13px;
            background: #f8fbff;
        }

        .vendor-info strong {
            display: block;
            margin-bottom: 3px;
            color: #0f172a;
            font-size: 10px;
            font-weight: 950;
        }

        .vendor-info span {
            color: #94a3b8;
            font-size: 8px;
        }

        .vendor-status {
            padding: 6px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 900;
        }

        .vendor-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .vendor-status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .vendor-status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .quick-actions {
            display: grid;
            grid-template-columns:
                repeat(2, 1fr);
            gap: 10px;
        }

        .quick-action {
            display: block;
            padding: 15px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f8fbff;
            color: #0f172a;
            text-decoration: none;
            transition: .2s ease;
        }

        .quick-action:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            transform: translateY(-2px);
        }

        .quick-action-icon {
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 18px;
        }

        .quick-action strong {
            display: block;
            margin-bottom: 3px;
            font-size: 10px;
            font-weight: 950;
        }

        .quick-action span {
            color: #94a3b8;
            font-size: 8px;
        }

        .full-panel {
            margin-top: 20px;
        }

        .admin-footer-space {
            height: 30px;
        }

        @media (max-width: 1100px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .admin-page {
                padding: 25px 15px;
            }

            .admin-hero {
                padding: 25px 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .order-item {
                grid-template-columns: 1fr auto;
            }

            .order-amount {
                display: none;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<main class="admin-page">

    <div class="admin-container">

        <section class="admin-hero">

            <div class="admin-hero-content">

                <div class="admin-kicker">
                    HochipoHub Control Centre
                </div>

                <h1>
                    Welcome back,
                    <?= e($admin['name']) ?>.
                </h1>

                <p>
                    Manage your marketplace,
                    monitor vendors, products,
                    orders and platform activity
                    from one place.
                </p>

                <div class="admin-badge">
                    Admin Access Active
                </div>

            </div>

        </section>


        <!-- STATISTICS -->

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Customers
                    </span>

                    <span class="stat-icon">
                        👥
                    </span>

                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalUsers
                    ) ?>
                </div>

                <div class="stat-note">
                    Registered customers
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Vendors
                    </span>

                    <span class="stat-icon">
                        🏪
                    </span>

                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalVendors
                    ) ?>
                </div>

                <div
                    class="stat-note
                    <?= $pendingVendors > 0
                        ? 'warning'
                        : '' ?>"
                >
                    <?= $pendingVendors > 0
                        ? $pendingVendors .
                          ' awaiting approval'
                        : 'All vendors reviewed' ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Products
                    </span>

                    <span class="stat-icon">
                        📦
                    </span>

                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalProducts
                    ) ?>
                </div>

                <div class="stat-note">
                    Marketplace listings
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Orders
                    </span>

                    <span class="stat-icon">
                        🛒
                    </span>

                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalOrders
                    ) ?>
                </div>

                <div
                    class="stat-note
                    <?= $pendingOrders > 0
                        ? 'warning'
                        : '' ?>"
                >
                    <?= $pendingOrders > 0
                        ? $pendingOrders .
                          ' pending orders'
                        : 'No pending orders' ?>
                </div>

            </div>

        </section>


        <div class="dashboard-grid">

            <!-- SALES -->

            <section class="panel">

                <div class="panel-header">

                    <h2>
                        Sales Overview
                    </h2>

                    <span
                        class="panel-link"
                    >
                        Last 6 Months
                    </span>

                </div>

                <div class="panel-body">

                    <div class="sales-total">

                        <?= formatPrice(
                            $totalSales
                        ) ?>

                        <span>
                            total marketplace sales
                        </span>

                    </div>


                    <?php

                    $maxSales = 1;

                    foreach (
                        $monthlySales
                        as $sale
                    ) {

                        $maxSales =
                            max(
                                $maxSales,
                                (float)
                                $sale['total_sales']
                            );
                    }

                    ?>

                    <div class="chart-area">

                        <?php if (
                            empty($monthlySales)
                        ): ?>

                            <div
                                style="
                                    width:100%;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    color:#94a3b8;
                                    font-size:11px;
                                "
                            >
                                No sales data available yet.
                            </div>

                        <?php else: ?>

                            <?php foreach (
                                $monthlySales
                                as $sale
                            ): ?>

                                <?php

                                $height =
                                    (
                                        (float)
                                        $sale['total_sales']
                                        /
                                        $maxSales
                                    ) * 100;

                                ?>

                                <div
                                    class="chart-column"
                                >

                                    <div
                                        class="chart-value"
                                    >
                                        RM
                                        <?= number_format(
                                            (float)
                                            $sale['total_sales'],
                                            0
                                        ) ?>
                                    </div>

                                    <div
                                        class="chart-bar-wrap"
                                    >

                                        <div
                                            class="chart-bar"
                                            style="
                                                height:
                                                <?= max(
                                                    4,
                                                    $height
                                                ) ?>%;
                                            "
                                        ></div>

                                    </div>

                                    <div
                                        class="chart-label"
                                    >
                                        <?= e(
                                            $sale['month_name']
                                        ) ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </section>


            <!-- QUICK ACTIONS -->

            <section class="panel">

                <div class="panel-header">

                    <h2>
                        Quick Actions
                    </h2>

                </div>

                <div class="panel-body">

                    <div class="quick-actions">

                        <a
                            href="<?= BASE_URL ?>admin/vendors.php"
                            class="quick-action"
                        >

                            <div
                                class="quick-action-icon"
                            >
                                🏪
                            </div>

                            <strong>
                                Manage Vendors
                            </strong>

                            <span>
                                Review vendor accounts
                            </span>

                        </a>


                        <a
                            href="<?= BASE_URL ?>admin/products.php"
                            class="quick-action"
                        >

                            <div
                                class="quick-action-icon"
                            >
                                📦
                            </div>

                            <strong>
                                Products
                            </strong>

                            <span>
                                Manage marketplace products
                            </span>

                        </a>


                        <a
                            href="<?= BASE_URL ?>admin/orders.php"
                            class="quick-action"
                        >

                            <div
                                class="quick-action-icon"
                            >
                                🛒
                            </div>

                            <strong>
                                Orders
                            </strong>

                            <span>
                                Monitor customer orders
                            </span>

                        </a>


                        <a
                            href="<?= BASE_URL ?>admin/users.php"
                            class="quick-action"
                        >

                            <div
                                class="quick-action-icon"
                            >
                                👥
                            </div>

                            <strong>
                                Users
                            </strong>

                            <span>
                                Manage customer accounts
                            </span>

                        </a>


                        <a
                            href="<?= BASE_URL ?>admin/payments.php"
                            class="quick-action"
                        >

                            <div
                                class="quick-action-icon"
                            >
                                💳
                            </div>

                            <strong>
                                Payments
                            </strong>

                            <span>
                                Review payment activity
                            </span>

                        </a>


                        <a
                            href="<?= BASE_URL ?>admin/reviews.php"
                            class="quick-action"
                        >

                            <div
                                class="quick-action-icon"
                            >
                                ⭐
                            </div>

                            <strong>
                                Reviews
                            </strong>

                            <span>
                                Moderate customer reviews
                            </span>

                        </a>

                    </div>

                </div>

            </section>

        </div>


        <!-- RECENT ORDERS -->

        <section class="panel full-panel">

            <div class="panel-header">

                <h2>
                    Recent Orders
                </h2>

                <a
                    href="<?= BASE_URL ?>admin/orders.php"
                    class="panel-link"
                >
                    View All
                </a>

            </div>

            <div class="panel-body">

                <?php if (
                    empty($recentOrders)
                ): ?>

                    <div
                        style="
                            padding:25px;
                            text-align:center;
                            color:#94a3b8;
                            font-size:11px;
                        "
                    >
                        No orders found.
                    </div>

                <?php else: ?>

                    <div class="order-list">

                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>

                            <?php

                            $statusClass =
                                strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $order['status']
                                    )
                                );

                            ?>

                            <div
                                class="order-item"
                            >

                                <div
                                    class="order-info"
                                >

                                    <strong>
                                        #<?= e(
                                            $order['order_number']
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= e(
                                            $order['customer_name']
                                        ) ?>

                                        ·

                                        <?= date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $order['created_at']
                                            )
                                        ) ?>
                                    </span>

                                </div>


                                <div
                                    class="order-amount"
                                >
                                    <?= formatPrice(
                                        $order['total_amount']
                                    ) ?>
                                </div>


                                <span
                                    class="status
                                    <?= e(
                                        $statusClass
                                    ) ?>"
                                >
                                    <?= e(
                                        $order['status']
                                    ) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- RECENT VENDORS -->

        <section class="panel full-panel">

            <div class="panel-header">

                <h2>
                    Recent Vendors
                </h2>

                <a
                    href="<?= BASE_URL ?>admin/vendors.php"
                    class="panel-link"
                >
                    Manage Vendors
                </a>

            </div>

            <div class="panel-body">

                <?php if (
                    empty($recentVendors)
                ): ?>

                    <div
                        style="
                            padding:25px;
                            text-align:center;
                            color:#94a3b8;
                            font-size:11px;
                        "
                    >
                        No vendors found.
                    </div>

                <?php else: ?>

                    <div class="vendor-list">

                        <?php foreach (
                            $recentVendors
                            as $vendor
                        ): ?>

                            <div
                                class="vendor-item"
                            >

                                <div
                                    class="vendor-info"
                                >

                                    <strong>
                                        <?= e(
                                            $vendor['business_name']
                                        ) ?>
                                    </strong>

                                    <span>
                                        Owner:
                                        <?= e(
                                            $vendor['owner_name']
                                        ) ?>

                                        ·

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $vendor['created_at']
                                            )
                                        ) ?>
                                    </span>

                                </div>


                                <span
                                    class="vendor-status
                                    <?= strtolower(
                                        e(
                                            $vendor[
                                                'approval_status'
                                            ]
                                        )
                                    ) ?>"
                                >
                                    <?= e(
                                        $vendor[
                                            'approval_status'
                                        ]
                                    ) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <div class="admin-footer-space"></div>

    </div>

</main>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>

</body>

</html>
