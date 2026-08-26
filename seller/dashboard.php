<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER DASHBOARD
|--------------------------------------------------------------------------
| File:
| seller/dashboard.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


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
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ../index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| VENDOR ROLE CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    strtolower(
        $_SESSION['role']
    ) !== 'vendor'
) {

    header(
        'Location: ../dashboard.php'
    );

    exit;

}


$userId =
    (int) $_SESSION['user_id'];


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
| VENDOR INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
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


$stmt->execute([
    $userId
]);


$vendor =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$vendor) {

    header(
        'Location: setup_profile.php'
    );

    exit;

}


$vendorId =
    (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| SESSION BUSINESS NAME
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];

$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


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


$stmt = $db->prepare("
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


$stmt->execute([
    $vendorId
]);


$data =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($data) {

    $productStats['total'] =
        (int) (
            $data['total']
            ?? 0
        );

    $productStats['available'] =
        (int) (
            $data['available']
            ?? 0
        );

    $productStats['out_of_stock'] =
        (int) (
            $data['out_of_stock']
            ?? 0
        );

    $productStats['hidden'] =
        (int) (
            $data['hidden']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| ORDER STATISTICS
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


$stmt = $db->prepare("
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


$stmt->execute([
    $vendorId
]);


$data =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($data) {

    foreach (
        $orderStats
        as $key => $value
    ) {

        $orderStats[$key] =
            (int) (
                $data[$key]
                ?? 0
            );

    }

}


/*
|--------------------------------------------------------------------------
| SALES
|--------------------------------------------------------------------------
*/

$salesStats = [
    'total_sales'     => 0,
    'completed_sales' => 0,
    'pending_sales'   => 0
];


$stmt = $db->prepare("
    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN vendor_status != 'Cancelled'
                    THEN subtotal
                    ELSE 0
                END
            ),
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


$stmt->execute([
    $vendorId
]);


$data =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($data) {

    $salesStats['total_sales'] =
        (float) (
            $data['total_sales']
            ?? 0
        );

    $salesStats['completed_sales'] =
        (float) (
            $data['completed_sales']
            ?? 0
        );

    $salesStats['pending_sales'] =
        (float) (
            $data['pending_sales']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| INVENTORY
|--------------------------------------------------------------------------
*/

$inventoryStats = [
    'total_stock'  => 0,
    'low_stock'    => 0,
    'out_of_stock' => 0
];


$stmt = $db->prepare("
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


$stmt->execute([
    $vendorId
]);


$data =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($data) {

    $inventoryStats['total_stock'] =
        (int) (
            $data['total_stock']
            ?? 0
        );

    $inventoryStats['low_stock'] =
        (int) (
            $data['low_stock']
            ?? 0
        );

    $inventoryStats['out_of_stock'] =
        (int) (
            $data['out_of_stock']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        vo.vendor_order_id,
        vo.order_id,
        vo.subtotal,
        vo.vendor_status,
        vo.created_at,

        u.name AS customer_name

    FROM vendor_orders vo

    INNER JOIN orders o
        ON vo.order_id = o.order_id

    INNER JOIN users u
        ON o.customer_id = u.user_id

    WHERE vo.vendor_id = ?

    ORDER BY
        vo.created_at DESC

    LIMIT 5
");


$stmt->execute([
    $vendorId
]);


$recentOrders =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Seller Dashboard';

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
        Seller Dashboard - HochipoHub
    </title>


    <!-- GOOGLE FONT -->

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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >

</head>


<body class="seller-dashboard-page">


<?php

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<!-- =========================================================
     MAIN
========================================================== -->

<main class="seller-dashboard-main">


    <!-- =====================================================
         TOP BAR
    ====================================================== -->

    <header class="seller-topbar">


        <div>

            <span class="seller-topbar-label">
                Seller Center
            </span>

        </div>


        <div class="seller-topbar-actions">


            <button
                type="button"
                class="seller-topbar-icon"
            >

                <i class="fa-regular fa-bell"></i>

                <?php if (
                    $orderStats['pending'] > 0
                ): ?>

                    <span>

                        <?= min(
                            9,
                            $orderStats['pending']
                        ) ?>

                    </span>

                <?php endif; ?>

            </button>


            <div class="seller-topbar-user">

                <div class="seller-topbar-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $vendor['name'],
                                0,
                                1
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $vendor['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </strong>

                    <small>
                        Vendor
                    </small>

                </div>

            </div>


        </div>


    </header>


    <div class="seller-dashboard-content">


        <!-- =================================================
             WELCOME
        ================================================== -->

        <section class="seller-welcome">


            <div>

                <span class="seller-eyebrow">
                    SELLER DASHBOARD
                </span>


                <h1>

                    Welcome back,

                    <?= htmlspecialchars(
                        $vendor['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>!

                    👋

                </h1>


                <p>
                    Here's what's happening with your store today.
                </p>

            </div>


            <a
                href="add_product.php"
                class="seller-add-product-btn"
            >

                <i class="fa-solid fa-plus"></i>

                Add Product

            </a>


        </section>


        <!-- =================================================
             MAIN STATS
        ================================================== -->

        <section class="seller-main-stats">


            <!-- PRODUCTS -->

            <article class="seller-summary-card blue">


                <div class="seller-summary-icon">

                    <i class="fa-solid fa-bag-shopping"></i>

                </div>


                <div class="seller-summary-copy">

                    <span>
                        TOTAL PRODUCTS
                    </span>

                    <strong>

                        <?= $productStats['total'] ?>

                    </strong>

                    <p>
                        All your products
                    </p>


                    <a href="products.php">

                        Manage

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


            </article>


            <!-- ORDERS -->

            <article class="seller-summary-card green">


                <div class="seller-summary-icon">

                    <i class="fa-solid fa-clipboard-check"></i>

                </div>


                <div class="seller-summary-copy">

                    <span>
                        TOTAL ORDERS
                    </span>

                    <strong>

                        <?= $orderStats['total'] ?>

                    </strong>

                    <p>
                        All customer orders
                    </p>


                    <a href="orders.php">

                        View orders

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


            </article>


            <!-- SALES -->

            <article class="seller-summary-card orange">


                <div class="seller-summary-icon">

                    <i class="fa-solid fa-dollar-sign"></i>

                </div>


                <div class="seller-summary-copy">

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

                    <p>
                        Overall sales
                    </p>


                    <a href="sales.php">

                        View sales

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


            </article>


            <!-- PENDING -->

            <article class="seller-summary-card purple">


                <div class="seller-summary-icon">

                    <i class="fa-solid fa-arrow-trend-up"></i>

                </div>


                <div class="seller-summary-copy">

                    <span>
                        PENDING ORDERS
                    </span>

                    <strong>

                        <?= $orderStats['pending'] ?>

                    </strong>

                    <p>
                        Orders in progress
                    </p>


                    <a href="orders.php">

                        View pending

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


            </article>


        </section>


        <!-- =================================================
             DASHBOARD GRID
        ================================================== -->

        <section class="seller-dashboard-grid">


            <!-- =================================================
                 SALES OVERVIEW
            ================================================== -->

            <div class="seller-dashboard-card seller-sales-overview">


                <div class="seller-card-heading">


                    <div class="seller-heading-icon blue">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>


                    <div>

                        <h2>
                            Sales Overview
                        </h2>

                        <p>
                            Monitor your store sales performance
                        </p>

                    </div>


                </div>


                <div class="seller-sales-mini-grid">


                    <div class="seller-sales-mini">


                        <div class="seller-mini-icon blue">

                            <i class="fa-solid fa-coins"></i>

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
                                From all orders
                            </small>

                        </div>


                    </div>


                    <div class="seller-sales-mini">


                        <div class="seller-mini-icon green">

                            <i class="fa-solid fa-circle-check"></i>

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


                    </div>


                    <div class="seller-sales-mini">


                        <div class="seller-mini-icon orange">

                            <i class="fa-regular fa-clock"></i>

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


                    </div>


                </div>


                <!-- VISUAL CHART -->

                <div class="seller-chart">


                    <div class="seller-chart-title">
                        Sales Performance
                    </div>


                    <div class="seller-chart-area">


                        <div class="seller-chart-line line-one"></div>

                        <div class="seller-chart-line line-two"></div>

                        <div class="seller-chart-line line-three"></div>

                        <div class="seller-chart-line line-four"></div>


                        <div class="seller-chart-placeholder">

                            <i class="fa-solid fa-chart-area"></i>

                            <span>
                                Sales data will appear here
                            </span>

                        </div>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 QUICK ACTIONS
            ================================================== -->

            <div class="seller-dashboard-card">


                <div class="seller-card-heading">


                    <div class="seller-heading-icon purple">

                        <i class="fa-solid fa-bolt"></i>

                    </div>


                    <div>

                        <h2>
                            Quick Actions
                        </h2>

                        <p>
                            Manage your store efficiently
                        </p>

                    </div>


                </div>


                <div class="seller-quick-grid">


                    <a
                        href="add_product.php"
                        class="seller-quick-card blue"
                    >

                        <div>

                            <i class="fa-solid fa-circle-plus"></i>

                        </div>

                        <strong>
                            Add New Product
                        </strong>

                        <span>
                            Create new product
                        </span>

                    </a>


                    <a
                        href="products.php"
                        class="seller-quick-card green"
                    >

                        <div>

                            <i class="fa-solid fa-cube"></i>

                        </div>

                        <strong>
                            Manage Products
                        </strong>

                        <span>
                            View all products
                        </span>

                    </a>


                    <a
                        href="orders.php"
                        class="seller-quick-card orange"
                    >

                        <div>

                            <i class="fa-solid fa-bag-shopping"></i>

                        </div>

                        <strong>
                            View Orders
                        </strong>

                        <span>
                            Check customer orders
                        </span>

                    </a>


                    <a
                        href="sales.php"
                        class="seller-quick-card purple"
                    >

                        <div>

                            <i class="fa-solid fa-chart-column"></i>

                        </div>

                        <strong>
                            View Sales
                        </strong>

                        <span>
                            Sales performance
                        </span>

                    </a>


                </div>


            </div>


            <!-- =================================================
                 RECENT ORDERS
            ================================================== -->

            <div class="seller-dashboard-card">


                <div class="seller-card-heading seller-heading-between">


                    <div class="seller-heading-left">


                        <div class="seller-heading-icon blue">

                            <i class="fa-solid fa-bag-shopping"></i>

                        </div>


                        <div>

                            <h2>
                                Recent Orders
                            </h2>

                            <p>
                                Latest orders from your customers
                            </p>

                        </div>


                    </div>


                    <a
                        href="orders.php"
                        class="seller-view-all"
                    >

                        View all orders

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>


                </div>


                <?php if (
                    empty(
                        $recentOrders
                    )
                ): ?>


                    <div class="seller-empty-orders">


                        <div>

                            <i class="fa-solid fa-box-open"></i>

                        </div>


                        <strong>
                            No orders yet
                        </strong>


                        <p>
                            Orders will appear here when customers purchase your products.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="seller-order-list">


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>


                            <div class="seller-order-row">


                                <div class="seller-order-icon">

                                    <i class="fa-solid fa-box"></i>

                                </div>


                                <div class="seller-order-info">

                                    <strong>

                                        Order
                                        #<?= (int)
                                            $order['order_id']
                                        ?>

                                    </strong>

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


                                <strong class="seller-order-price">

                                    RM
                                    <?= number_format(
                                        (float)
                                        $order['subtotal'],
                                        2
                                    ) ?>

                                </strong>


                                <span
                                    class="seller-order-status
                                    <?= strtolower(
                                        $order[
                                            'vendor_status'
                                        ]
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


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 STORE STATUS
            ================================================== -->

            <div class="seller-dashboard-card">


                <div class="seller-card-heading seller-heading-between">


                    <div class="seller-heading-left">


                        <div class="seller-heading-icon cyan">

                            <i class="fa-solid fa-store"></i>

                        </div>


                        <div>

                            <h2>
                                Store Status
                            </h2>

                            <p>
                                Store information
                            </p>

                        </div>


                    </div>


                    <span
                        class="seller-store-status
                        <?= strtolower(
                            $vendor[
                                'approval_status'
                            ]
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            $vendor[
                                'approval_status'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>


                </div>


                <div class="seller-store-info">


                    <div>

                        <span>
                            Store Name
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $vendor[
                                    'business_name'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            Vendor Status
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $vendor[
                                    'approval_status'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            Total Stock
                        </span>

                        <strong>

                            <?= $inventoryStats[
                                'total_stock'
                            ] ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            Member Since
                        </span>

                        <strong>

                            <?= !empty(
                                $vendor[
                                    'created_at'
                                ]
                            )
                                ? date(
                                    'd M Y',
                                    strtotime(
                                        $vendor[
                                            'created_at'
                                        ]
                                    )
                                )
                                : '—'
                            ?>

                        </strong>

                    </div>


                </div>


            </div>


        </section>


    </div>


</main>


</body>

</html>
