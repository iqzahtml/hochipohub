<?php

/**
 * =========================================================
 * HOCHIPOHUB
 * SELLER - DASHBOARD
 * File: seller/dashboard.php
 * =========================================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| SECURITY - VENDOR ONLY
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ../index.php');
    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK VENDOR ROLE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'vendor'
) {

    header('Location: ../dashboard.php');
    exit;

}


$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (!isset($db) || !($db instanceof PDO)) {

    die(
        'Database connection is not available.'
    );

}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

$vendorStmt = $db->prepare("
    SELECT
        v.vendor_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.business_address,
        v.category,
        v.delivery_method,
        v.approval_status,
        v.created_at,
        u.name,
        u.email,
        u.phone

    FROM vendors v

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE v.user_id = ?

    LIMIT 1
");

$vendorStmt->execute([
    $userId
]);

$vendor = $vendorStmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$vendor) {

    die(
        'Vendor profile not found. ' .
        'Please make sure this user has a record in the vendors table.'
    );

}


$vendorId = (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| PRODUCT STATISTICS
|--------------------------------------------------------------------------
*/

$productStats = [
    'total'        => 0,
    'available'    => 0,
    'out_of_stock' => 0,
    'hidden'       => 0
];


$productStatsStmt = $db->prepare("
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'Available'
                THEN 1
                ELSE 0
            END
        ) AS available,

        SUM(
            CASE
                WHEN status = 'Out of Stock'
                THEN 1
                ELSE 0
            END
        ) AS out_of_stock,

        SUM(
            CASE
                WHEN status = 'Hidden'
                THEN 1
                ELSE 0
            END
        ) AS hidden

    FROM products

    WHERE vendor_id = ?
");


$productStatsStmt->execute([
    $vendorId
]);


$productStatsData =
    $productStatsStmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($productStatsData) {

    $productStats['total'] =
        (int) (
            $productStatsData['total']
            ?? 0
        );

    $productStats['available'] =
        (int) (
            $productStatsData['available']
            ?? 0
        );

    $productStats['out_of_stock'] =
        (int) (
            $productStatsData['out_of_stock']
            ?? 0
        );

    $productStats['hidden'] =
        (int) (
            $productStatsData['hidden']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| VENDOR ORDER STATISTICS
|--------------------------------------------------------------------------
*/

$orderStats = [

    'total'      => 0,
    'pending'    => 0,
    'processing' => 0,
    'ready'      => 0,
    'shipped'    => 0,
    'completed'  => 0,
    'cancelled'  => 0

];


$orderStatsStmt = $db->prepare("
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN vendor_status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending,

        SUM(
            CASE
                WHEN vendor_status = 'Processing'
                THEN 1
                ELSE 0
            END
        ) AS processing,

        SUM(
            CASE
                WHEN vendor_status = 'Ready'
                THEN 1
                ELSE 0
            END
        ) AS ready,

        SUM(
            CASE
                WHEN vendor_status = 'Shipped'
                THEN 1
                ELSE 0
            END
        ) AS shipped,

        SUM(
            CASE
                WHEN vendor_status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed,

        SUM(
            CASE
                WHEN vendor_status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled

    FROM vendor_orders

    WHERE vendor_id = ?
");


$orderStatsStmt->execute([
    $vendorId
]);


$orderStatsData =
    $orderStatsStmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($orderStatsData) {

    $orderStats['total'] =
        (int) (
            $orderStatsData['total']
            ?? 0
        );

    $orderStats['pending'] =
        (int) (
            $orderStatsData['pending']
            ?? 0
        );

    $orderStats['processing'] =
        (int) (
            $orderStatsData['processing']
            ?? 0
        );

    $orderStats['ready'] =
        (int) (
            $orderStatsData['ready']
            ?? 0
        );

    $orderStats['shipped'] =
        (int) (
            $orderStatsData['shipped']
            ?? 0
        );

    $orderStats['completed'] =
        (int) (
            $orderStatsData['completed']
            ?? 0
        );

    $orderStats['cancelled'] =
        (int) (
            $orderStatsData['cancelled']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| SALES STATISTICS
|--------------------------------------------------------------------------
*/

$salesStats = [

    'total_sales'     => 0,
    'completed_sales' => 0,
    'pending_sales'   => 0

];


$salesStmt = $db->prepare("
    SELECT

        COALESCE(
            SUM(subtotal),
            0
        ) AS total_sales,

        COALESCE(
            SUM(
                CASE
                    WHEN vendor_status = 'Completed'
                    THEN subtotal
                    ELSE 0
                END
            ),
            0
        ) AS completed_sales,

        COALESCE(
            SUM(
                CASE
                    WHEN vendor_status IN (
                        'Pending',
                        'Processing',
                        'Ready',
                        'Shipped'
                    )
                    THEN subtotal
                    ELSE 0
                END
            ),
            0
        ) AS pending_sales

    FROM vendor_orders

    WHERE vendor_id = ?
");


$salesStmt->execute([
    $vendorId
]);


$salesData =
    $salesStmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($salesData) {

    $salesStats['total_sales'] =
        (float) (
            $salesData['total_sales']
            ?? 0
        );

    $salesStats['completed_sales'] =
        (float) (
            $salesData['completed_sales']
            ?? 0
        );

    $salesStats['pending_sales'] =
        (float) (
            $salesData['pending_sales']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| INVENTORY STATISTICS
|--------------------------------------------------------------------------
*/

$inventoryStats = [

    'total_stock'  => 0,
    'low_stock'    => 0,
    'out_of_stock' => 0

];


$inventoryStmt = $db->prepare("
    SELECT

        COALESCE(
            SUM(i.quantity),
            0
        ) AS total_stock,

        COALESCE(
            SUM(
                CASE
                    WHEN i.quantity BETWEEN 1 AND 5
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS low_stock,

        COALESCE(
            SUM(
                CASE
                    WHEN i.quantity = 0
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS out_of_stock

    FROM inventory i

    INNER JOIN products p
        ON i.product_id = p.product_id

    WHERE p.vendor_id = ?
");


$inventoryStmt->execute([
    $vendorId
]);


$inventoryData =
    $inventoryStmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($inventoryData) {

    $inventoryStats['total_stock'] =
        (int) (
            $inventoryData['total_stock']
            ?? 0
        );

    $inventoryStats['low_stock'] =
        (int) (
            $inventoryData['low_stock']
            ?? 0
        );

    $inventoryStats['out_of_stock'] =
        (int) (
            $inventoryData['out_of_stock']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| RECENT VENDOR ORDERS
|--------------------------------------------------------------------------
*/

$recentOrders = [];


$recentOrdersStmt = $db->prepare("
    SELECT

        vo.vendor_order_id,
        vo.order_id,
        vo.subtotal,
        vo.delivery_fee,
        vo.vendor_status,
        vo.tracking_number,
        vo.created_at,

        o.order_date,

        u.name AS customer_name

    FROM vendor_orders vo

    INNER JOIN orders o
        ON vo.order_id = o.order_id

    INNER JOIN users u
        ON o.customer_id = u.user_id

    WHERE vo.vendor_id = ?

    ORDER BY vo.created_at DESC

    LIMIT 8
");


$recentOrdersStmt->execute([
    $vendorId
]);


$recentOrders =
    $recentOrdersStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| RECENT PRODUCTS
|--------------------------------------------------------------------------
*/

$recentProducts = [];


$recentProductsStmt = $db->prepare("
    SELECT

        p.product_id,
        p.product_name,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,

        c.category_name

    FROM products p

    LEFT JOIN categories c
        ON p.category_id = c.category_id

    WHERE p.vendor_id = ?

    ORDER BY p.created_at DESC

    LIMIT 6
");


$recentProductsStmt->execute([
    $vendorId
]);


$recentProducts =
    $recentProductsStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Seller Dashboard - HochipoHub';

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
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>


    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

</head>


<body class="vendor-dashboard-page">


<?php

$sidebarFile =
    dirname(__DIR__) .
    '/includes/vendor_sidebar.php';

if (file_exists($sidebarFile)) {

    include $sidebarFile;

}

?>


<main class="vendor-dashboard-main">


    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <section class="seller-welcome">

        <div class="seller-welcome-content">

            <span class="seller-eyebrow">
                SELLER CENTER
            </span>

            <h1>

                Welcome back,

                <span>
                    <?= htmlspecialchars(
                        $vendor['business_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                👋

            </h1>

            <p>
                Manage your products, orders and sales
                from one powerful dashboard.
            </p>

        </div>


        <div class="seller-welcome-actions">

            <a
                href="add_product.php"
                class="seller-primary-btn"
            >
                <i class="fas fa-plus"></i>
                Add Product
            </a>

            <a
                href="products.php"
                class="seller-secondary-btn"
            >
                <i class="fas fa-box"></i>
                Manage Products
            </a>

        </div>

    </section>


    <!-- =====================================================
         APPROVAL STATUS
    ====================================================== -->

    <section
        class="vendor-status-card
        <?= strtolower(
            $vendor['approval_status']
        ) ?>"
    >

        <div class="vendor-status-icon">

            <?php if (
                $vendor['approval_status']
                === 'Approved'
            ): ?>

                <i class="fas fa-check"></i>

            <?php elseif (
                $vendor['approval_status']
                === 'Pending'
            ): ?>

                <i class="fas fa-clock"></i>

            <?php else: ?>

                <i class="fas fa-exclamation"></i>

            <?php endif; ?>

        </div>


        <div class="vendor-status-content">

            <span>
                VENDOR ACCOUNT STATUS
            </span>

            <strong>
                <?= htmlspecialchars(
                    $vendor['approval_status'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </div>


        <div class="vendor-status-right">

            <?php if (
                $vendor['approval_status']
                === 'Approved'
            ): ?>

                <span class="status-pill approved">
                    Active
                </span>

            <?php elseif (
                $vendor['approval_status']
                === 'Pending'
            ): ?>

                <span class="status-pill pending">
                    Under Review
                </span>

            <?php else: ?>

                <span class="status-pill rejected">
                    Attention Required
                </span>

            <?php endif; ?>

        </div>

    </section>


    <!-- =====================================================
         PRODUCT OVERVIEW
    ====================================================== -->

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    PRODUCT OVERVIEW
                </span>

                <h2>
                    Your Products
                </h2>

                <p>
                    Keep track of your product inventory.
                </p>

            </div>


            <a
                href="products.php"
                class="section-link"
            >
                View Products
                <i class="fas fa-arrow-right"></i>
            </a>

        </div>


        <div class="stats-grid product-stats">


            <!-- TOTAL -->

            <article class="stat-card blue">

                <div class="stat-card-top">

                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>

                    <span>
                        ALL PRODUCTS
                    </span>

                </div>

                <strong>
                    <?= $productStats['total'] ?>
                </strong>

                <p>
                    Total products
                </p>

            </article>


            <!-- AVAILABLE -->

            <article class="stat-card green">

                <div class="stat-card-top">

                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <span>
                        AVAILABLE
                    </span>

                </div>

                <strong>
                    <?= $productStats['available'] ?>
                </strong>

                <p>
                    Currently available
                </p>

            </article>


            <!-- OUT OF STOCK -->

            <article class="stat-card red">

                <div class="stat-card-top">

                    <div class="stat-icon">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>

                    <span>
                        OUT OF STOCK
                    </span>

                </div>

                <strong>
                    <?= $productStats['out_of_stock'] ?>
                </strong>

                <p>
                    Need restocking
                </p>

            </article>


            <!-- HIDDEN -->

            <article class="stat-card purple">

                <div class="stat-card-top">

                    <div class="stat-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>

                    <span>
                        HIDDEN
                    </span>

                </div>

                <strong>
                    <?= $productStats['hidden'] ?>
                </strong>

                <p>
                    Not visible to customers
                </p>

            </article>


        </div>

    </section>


    <!-- =====================================================
         SALES PERFORMANCE
    ====================================================== -->

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    BUSINESS PERFORMANCE
                </span>

                <h2>
                    Sales Performance
                </h2>

                <p>
                    Monitor your marketplace sales.
                </p>

            </div>

        </div>


        <div class="sales-grid">


            <!-- TOTAL SALES -->

            <article class="sales-card featured">

                <div class="sales-card-icon">
                    <i class="fas fa-wallet"></i>
                </div>

                <div>

                    <span>
                        TOTAL SALES
                    </span>

                    <strong>
                        RM
                        <?= number_format(
                            $salesStats['total_sales'],
                            2
                        ) ?>
                    </strong>

                    <small>
                        Overall vendor sales
                    </small>

                </div>

            </article>


            <!-- COMPLETED -->

            <article class="sales-card">

                <div class="sales-card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>

                <div>

                    <span>
                        COMPLETED SALES
                    </span>

                    <strong>
                        RM
                        <?= number_format(
                            $salesStats['completed_sales'],
                            2
                        ) ?>
                    </strong>

                    <small>
                        Successfully completed
                    </small>

                </div>

            </article>


            <!-- PENDING -->

            <article class="sales-card">

                <div class="sales-card-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>

                <div>

                    <span>
                        PENDING SALES
                    </span>

                    <strong>
                        RM
                        <?= number_format(
                            $salesStats['pending_sales'],
                            2
                        ) ?>
                    </strong>

                    <small>
                        Orders in progress
                    </small>

                </div>

            </article>


        </div>

    </section>


    <!-- =====================================================
         ORDER OVERVIEW
    ====================================================== -->

    <section class="dashboard-panel">

        <div class="panel-heading">

            <div>

                <span class="section-eyebrow">
                    ORDER MANAGEMENT
                </span>

                <h2>
                    Order Overview
                </h2>

                <p>
                    Track the status of your vendor orders.
                </p>

            </div>


            <a
                href="orders.php"
                class="section-link"
            >
                View All
                <i class="fas fa-arrow-right"></i>
            </a>

        </div>


        <div class="order-status-grid">


            <div class="order-status-item pending">

                <div class="order-status-icon">
                    <i class="fas fa-clock"></i>
                </div>

                <strong>
                    <?= $orderStats['pending'] ?>
                </strong>

                <span>
                    Pending
                </span>

            </div>


            <div class="order-status-item processing">

                <div class="order-status-icon">
                    <i class="fas fa-gear"></i>
                </div>

                <strong>
                    <?= $orderStats['processing'] ?>
                </strong>

                <span>
                    Processing
                </span>

            </div>


            <div class="order-status-item ready">

                <div class="order-status-icon">
                    <i class="fas fa-box-open"></i>
                </div>

                <strong>
                    <?= $orderStats['ready'] ?>
                </strong>

                <span>
                    Ready
                </span>

            </div>


            <div class="order-status-item shipped">

                <div class="order-status-icon">
                    <i class="fas fa-truck"></i>
                </div>

                <strong>
                    <?= $orderStats['shipped'] ?>
                </strong>

                <span>
                    Shipped
                </span>

            </div>


            <div class="order-status-item completed">

                <div class="order-status-icon">
                    <i class="fas fa-circle-check"></i>
                </div>

                <strong>
                    <?= $orderStats['completed'] ?>
                </strong>

                <span>
                    Completed
                </span>

            </div>


            <div class="order-status-item cancelled">

                <div class="order-status-icon">
                    <i class="fas fa-xmark"></i>
                </div>

                <strong>
                    <?= $orderStats['cancelled'] ?>
                </strong>

                <span>
                    Cancelled
                </span>

            </div>


        </div>

    </section>


    <!-- =====================================================
         INVENTORY
    ====================================================== -->

    <section class="dashboard-panel">

        <div class="panel-heading">

            <div>

                <span class="section-eyebrow">
                    STOCK CONTROL
                </span>

                <h2>
                    Inventory Overview
                </h2>

                <p>
                    Monitor your current stock levels.
                </p>

            </div>


            <a
                href="../inventory.php"
                class="section-link"
            >
                Manage Inventory
                <i class="fas fa-arrow-right"></i>
            </a>

        </div>


        <div class="inventory-grid">


            <div class="inventory-item">

                <div class="inventory-icon blue">
                    <i class="fas fa-cubes"></i>
                </div>

                <div>

                    <strong>
                        <?= $inventoryStats['total_stock'] ?>
                    </strong>

                    <span>
                        Total Stock
                    </span>

                </div>

            </div>


            <div class="inventory-item">

                <div class="inventory-icon yellow">
                    <i class="fas fa-battery-quarter"></i>
                </div>

                <div>

                    <strong>
                        <?= $inventoryStats['low_stock'] ?>
                    </strong>

                    <span>
                        Low Stock
                    </span>

                </div>

            </div>


            <div class="inventory-item">

                <div class="inventory-icon red">
                    <i class="fas fa-box-open"></i>
                </div>

                <div>

                    <strong>
                        <?= $inventoryStats['out_of_stock'] ?>
                    </strong>

                    <span>
                        Out of Stock
                    </span>

                </div>

            </div>


        </div>

    </section>


    <!-- =====================================================
         RECENT ORDERS
    ====================================================== -->

    <section class="dashboard-panel">

        <div class="panel-heading">

            <div>

                <span class="section-eyebrow">
                    LATEST ACTIVITY
                </span>

                <h2>
                    Recent Orders
                </h2>

                <p>
                    Your latest customer orders.
                </p>

            </div>


            <a
                href="orders.php"
                class="section-link"
            >
                View All
                <i class="fas fa-arrow-right"></i>
            </a>

        </div>


        <div class="table-wrapper">

            <table class="seller-table">

                <thead>

                    <tr>

                        <th>
                            Order
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (
                        empty($recentOrders)
                    ): ?>

                        <tr>

                            <td
                                colspan="4"
                                class="empty-table"
                            >

                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>

                                <strong>
                                    No orders yet
                                </strong>

                                <span>
                                    Your recent orders will appear here.
                                </span>

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>

                            <tr>

                                <td>

                                    <strong class="order-number">

                                        #
                                        <?= (int)
                                            $order['order_id']
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <div class="customer-cell">

                                        <div class="customer-avatar">

                                            <?= strtoupper(
                                                substr(
                                                    $order[
                                                        'customer_name'
                                                    ],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>

                                        <span>

                                            <?= htmlspecialchars(
                                                $order[
                                                    'customer_name'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <td>

                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $order['subtotal'],
                                            2
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <span
                                        class="table-status
                                        <?= strtolower(
                                            str_replace(
                                                ' ',
                                                '-',
                                                $order[
                                                    'vendor_status'
                                                ]
                                            )
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $order[
                                                'vendor_status'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- =====================================================
         RECENT PRODUCTS
    ====================================================== -->

    <section class="dashboard-panel">

        <div class="panel-heading">

            <div>

                <span class="section-eyebrow">
                    YOUR CATALOG
                </span>

                <h2>
                    Recent Products
                </h2>

                <p>
                    Your latest products added to the marketplace.
                </p>

            </div>


            <a
                href="products.php"
                class="section-link"
            >
                Manage Products
                <i class="fas fa-arrow-right"></i>
            </a>

        </div>


        <?php if (
            empty($recentProducts)
        ): ?>

            <div class="empty-products">

                <div class="empty-icon">
                    <i class="fas fa-box-open"></i>
                </div>

                <h3>
                    No products yet
                </h3>

                <p>
                    Start adding products to your store.
                </p>

                <a
                    href="add_product.php"
                    class="seller-primary-btn"
                >
                    <i class="fas fa-plus"></i>
                    Add Your First Product
                </a>

            </div>

        <?php else: ?>


            <div class="product-grid">


                <?php foreach (
                    $recentProducts
                    as $product
                ): ?>


                    <?php

                    $productImage =
                        !empty(
                            $product['image']
                        )

                        ? '../uploads/products/' .
                          $product['image']

                        : '../image/logo.jpg';

                    ?>


                    <article class="dashboard-product-card">


                        <div class="product-image-wrapper">

                            <img
                                src="<?= htmlspecialchars(
                                    $productImage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $product[
                                        'product_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                onerror="
                                    this.src='../image/logo.jpg';
                                "
                            >

                            <span
                                class="product-status
                                <?= strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $product[
                                            'status'
                                        ]
                                    )
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $product[
                                        'status'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </div>


                        <div class="product-card-content">

                            <span class="product-category">

                                <?= htmlspecialchars(
                                    $product[
                                        'category_name'
                                    ]
                                    ?? 'Uncategorized',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>


                            <h3>

                                <?= htmlspecialchars(
                                    $product[
                                        'product_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h3>


                            <div class="product-card-bottom">

                                <strong>

                                    RM
                                    <?= number_format(
                                        (float)
                                        $product['price'],
                                        2
                                    ) ?>

                                </strong>


                                <span>

                                    <i class="fas fa-cubes"></i>

                                    <?= (int)
                                        $product[
                                            'stock_quantity'
                                        ] ?>

                                </span>

                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>