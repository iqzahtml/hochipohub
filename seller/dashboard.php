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
|
| database/db.php already creates:
|
| $db = getDB();
|
*/

if (!isset($db) || !($db instanceof PDO)) {

    die('Database connection is not available.');
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

$vendor = $vendorStmt->fetch(PDO::FETCH_ASSOC);


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
    $productStatsStmt->fetch(PDO::FETCH_ASSOC);


if ($productStatsData) {

    $productStats['total'] =
        (int) ($productStatsData['total'] ?? 0);

    $productStats['available'] =
        (int) ($productStatsData['available'] ?? 0);

    $productStats['out_of_stock'] =
        (int) ($productStatsData['out_of_stock'] ?? 0);

    $productStats['hidden'] =
        (int) ($productStatsData['hidden'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| VENDOR ORDER STATISTICS
|--------------------------------------------------------------------------
*/

$orderStats = [
    'total'     => 0,
    'pending'   => 0,
    'processing'=> 0,
    'ready'     => 0,
    'shipped'   => 0,
    'completed' => 0,
    'cancelled' => 0
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
    $orderStatsStmt->fetch(PDO::FETCH_ASSOC);


if ($orderStatsData) {

    $orderStats['total'] =
        (int) ($orderStatsData['total'] ?? 0);

    $orderStats['pending'] =
        (int) ($orderStatsData['pending'] ?? 0);

    $orderStats['processing'] =
        (int) ($orderStatsData['processing'] ?? 0);

    $orderStats['ready'] =
        (int) ($orderStatsData['ready'] ?? 0);

    $orderStats['shipped'] =
        (int) ($orderStatsData['shipped'] ?? 0);

    $orderStats['completed'] =
        (int) ($orderStatsData['completed'] ?? 0);

    $orderStats['cancelled'] =
        (int) ($orderStatsData['cancelled'] ?? 0);
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
    $salesStmt->fetch(PDO::FETCH_ASSOC);


if ($salesData) {

    $salesStats['total_sales'] =
        (float) ($salesData['total_sales'] ?? 0);

    $salesStats['completed_sales'] =
        (float) ($salesData['completed_sales'] ?? 0);

    $salesStats['pending_sales'] =
        (float) ($salesData['pending_sales'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| INVENTORY STATISTICS
|--------------------------------------------------------------------------
*/

$inventoryStats = [
    'total_stock'   => 0,
    'low_stock'     => 0,
    'out_of_stock'  => 0
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
    $inventoryStmt->fetch(PDO::FETCH_ASSOC);


if ($inventoryData) {

    $inventoryStats['total_stock'] =
        (int) ($inventoryData['total_stock'] ?? 0);

    $inventoryStats['low_stock'] =
        (int) ($inventoryData['low_stock'] ?? 0);

    $inventoryStats['out_of_stock'] =
        (int) ($inventoryData['out_of_stock'] ?? 0);
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
    $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);


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
    $recentProductsStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle = 'Seller Dashboard - HochipoHub';

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
        <?php echo htmlspecialchars($pageTitle); ?>
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


<body>


<?php

$sidebarFile =
    dirname(__DIR__) .
    '/includes/vendor_sidebar.php';


if (file_exists($sidebarFile)) {

    include $sidebarFile;

}

?>


<main class="dashboard-main">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="dashboard-header">

        <div>

            <span
                style="
                    color:#2563eb;
                    font-weight:700;
                    font-size:13px;
                    letter-spacing:1px;
                "
            >
                SELLER CENTER
            </span>

            <h1>

                Welcome back,

                <?php

                echo htmlspecialchars(
                    $vendor['business_name']
                );

                ?>

                👋

            </h1>

            <p>
                Manage your products, orders and sales
                from one place.
            </p>

        </div>


        <a
            href="add_product.php"
            class="btn"
            style="
                background:#2563eb;
                color:#fff;
                text-decoration:none;
            "
        >
            + Add Product
        </a>

    </div>


    <!-- =====================================================
         APPROVAL STATUS
    ====================================================== -->

    <div
        style="
            margin:25px 0;
            padding:20px;
            border-radius:16px;

            <?php

            if (
                $vendor['approval_status'] === 'Approved'
            ) {

                echo '
                    background:#dcfce7;
                    color:#166534;
                ';

            } elseif (
                $vendor['approval_status'] === 'Pending'
            ) {

                echo '
                    background:#fef3c7;
                    color:#92400e;
                ';

            } else {

                echo '
                    background:#fee2e2;
                    color:#991b1b;
                ';

            }

            ?>
        "
    >

        <strong>
            Vendor Status:
        </strong>

        <?php

        echo htmlspecialchars(
            $vendor['approval_status']
        );

        ?>

    </div>


    <!-- =====================================================
         PRODUCT CARDS
    ====================================================== -->

    <div
        style="
            display:grid;
            grid-template-columns:
                repeat(4, minmax(0,1fr));
            gap:20px;
            margin-bottom:25px;
        "
    >


        <div
            style="
                background:#fff;
                padding:24px;
                border-radius:18px;
                box-shadow:
                    0 8px 25px
                    rgba(15,23,42,.07);
            "
        >

            <small>
                Total Products
            </small>

            <h2>
                <?php
                echo $productStats['total'];
                ?>
            </h2>

        </div>


        <div
            style="
                background:#fff;
                padding:24px;
                border-radius:18px;
                box-shadow:
                    0 8px 25px
                    rgba(15,23,42,.07);
            "
        >

            <small>
                Available
            </small>

            <h2
                style="color:#16a34a;"
            >
                <?php
                echo $productStats['available'];
                ?>
            </h2>

        </div>


        <div
            style="
                background:#fff;
                padding:24px;
                border-radius:18px;
                box-shadow:
                    0 8px 25px
                    rgba(15,23,42,.07);
            "
        >

            <small>
                Out of Stock
            </small>

            <h2
                style="color:#dc2626;"
            >
                <?php
                echo $productStats['out_of_stock'];
                ?>
            </h2>

        </div>


        <div
            style="
                background:#fff;
                padding:24px;
                border-radius:18px;
                box-shadow:
                    0 8px 25px
                    rgba(15,23,42,.07);
            "
        >

            <small>
                Hidden
            </small>

            <h2>
                <?php
                echo $productStats['hidden'];
                ?>
            </h2>

        </div>


    </div>


    <!-- =====================================================
         SALES CARDS
    ====================================================== -->

    <div
        style="
            display:grid;
            grid-template-columns:
                repeat(3, minmax(0,1fr));
            gap:20px;
            margin-bottom:30px;
        "
    >


        <div
            style="
                background:#2563eb;
                color:#fff;
                padding:26px;
                border-radius:20px;
            "
        >

            <small>
                Total Sales
            </small>

            <h2>

                RM

                <?php

                echo number_format(
                    $salesStats['total_sales'],
                    2
                );

                ?>

            </h2>

        </div>


        <div
            style="
                background:#fff;
                padding:26px;
                border-radius:20px;
                box-shadow:
                    0 8px 25px
                    rgba(15,23,42,.07);
            "
        >

            <small>
                Completed Sales
            </small>

            <h2>

                RM

                <?php

                echo number_format(
                    $salesStats['completed_sales'],
                    2
                );

                ?>

            </h2>

        </div>


        <div
            style="
                background:#fff;
                padding:26px;
                border-radius:20px;
                box-shadow:
                    0 8px 25px
                    rgba(15,23,42,.07);
            "
        >

            <small>
                Pending Sales
            </small>

            <h2>

                RM

                <?php

                echo number_format(
                    $salesStats['pending_sales'],
                    2
                );

                ?>

            </h2>

        </div>


    </div>


    <!-- =====================================================
         ORDER STATUS
    ====================================================== -->

    <section
        style="
            background:#fff;
            padding:25px;
            border-radius:20px;
            box-shadow:
                0 8px 25px
                rgba(15,23,42,.07);
            margin-bottom:30px;
        "
    >

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:20px;
            "
        >

            <div>

                <h2>
                    Order Overview
                </h2>

                <p>
                    Your vendor order statistics.
                </p>

            </div>

            <a
                href="orders.php"
                style="
                    color:#2563eb;
                    text-decoration:none;
                    font-weight:700;
                "
            >
                View All →
            </a>

        </div>


        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(6, minmax(0,1fr));
                gap:12px;
            "
        >

            <div>

                <strong>
                    <?php
                    echo $orderStats['pending'];
                    ?>
                </strong>

                <br>

                Pending

            </div>


            <div>

                <strong>
                    <?php
                    echo $orderStats['processing'];
                    ?>
                </strong>

                <br>

                Processing

            </div>


            <div>

                <strong>
                    <?php
                    echo $orderStats['ready'];
                    ?>
                </strong>

                <br>

                Ready

            </div>


            <div>

                <strong>
                    <?php
                    echo $orderStats['shipped'];
                    ?>
                </strong>

                <br>

                Shipped

            </div>


            <div>

                <strong>
                    <?php
                    echo $orderStats['completed'];
                    ?>
                </strong>

                <br>

                Completed

            </div>


            <div>

                <strong>
                    <?php
                    echo $orderStats['cancelled'];
                    ?>
                </strong>

                <br>

                Cancelled

            </div>

        </div>

    </section>


    <!-- =====================================================
         INVENTORY
    ====================================================== -->

    <section
        style="
            background:#fff;
            padding:25px;
            border-radius:20px;
            box-shadow:
                0 8px 25px
                rgba(15,23,42,.07);
            margin-bottom:30px;
        "
    >

        <h2>
            Inventory Overview
        </h2>

        <div
            style="
                display:flex;
                gap:35px;
                margin-top:15px;
                flex-wrap:wrap;
            "
        >

            <div>

                <strong>
                    <?php
                    echo $inventoryStats['total_stock'];
                    ?>
                </strong>

                <br>

                Total Stock

            </div>


            <div>

                <strong
                    style="color:#f59e0b;"
                >

                    <?php

                    echo $inventoryStats['low_stock'];

                    ?>

                </strong>

                <br>

                Low Stock

            </div>


            <div>

                <strong
                    style="color:#dc2626;"
                >

                    <?php

                    echo $inventoryStats['out_of_stock'];

                    ?>

                </strong>

                <br>

                Out of Stock

            </div>

        </div>

    </section>


    <!-- =====================================================
         RECENT ORDERS
    ====================================================== -->

    <section
        style="
            background:#fff;
            padding:25px;
            border-radius:20px;
            box-shadow:
                0 8px 25px
                rgba(15,23,42,.07);
            margin-bottom:30px;
            overflow-x:auto;
        "
    >

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:20px;
            "
        >

            <h2>
                Recent Orders
            </h2>

            <a
                href="orders.php"
                style="
                    color:#2563eb;
                    text-decoration:none;
                    font-weight:700;
                "
            >
                View All →
            </a>

        </div>


        <table
            style="
                width:100%;
                border-collapse:collapse;
            "
        >

            <thead>

                <tr>

                    <th
                        style="
                            text-align:left;
                            padding:12px;
                        "
                    >
                        Order
                    </th>

                    <th
                        style="
                            text-align:left;
                            padding:12px;
                        "
                    >
                        Customer
                    </th>

                    <th
                        style="
                            text-align:left;
                            padding:12px;
                        "
                    >
                        Amount
                    </th>

                    <th
                        style="
                            text-align:left;
                            padding:12px;
                        "
                    >
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($recentOrders)): ?>

                    <tr>

                        <td
                            colspan="4"
                            style="
                                padding:25px;
                                text-align:center;
                                color:#64748b;
                            "
                        >

                            No orders yet.

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach (
                        $recentOrders
                        as $order
                    ): ?>

                        <tr>

                            <td
                                style="
                                    padding:12px;
                                    border-top:
                                        1px solid #e2e8f0;
                                "
                            >

                                #

                                <?php

                                echo (int)
                                    $order['order_id'];

                                ?>

                            </td>


                            <td
                                style="
                                    padding:12px;
                                    border-top:
                                        1px solid #e2e8f0;
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $order['customer_name']
                                );

                                ?>

                            </td>


                            <td
                                style="
                                    padding:12px;
                                    border-top:
                                        1px solid #e2e8f0;
                                "
                            >

                                RM

                                <?php

                                echo number_format(
                                    (float)
                                    $order['subtotal'],
                                    2
                                );

                                ?>

                            </td>


                            <td
                                style="
                                    padding:12px;
                                    border-top:
                                        1px solid #e2e8f0;
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $order['vendor_status']
                                );

                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </section>


    <!-- =====================================================
         RECENT PRODUCTS
    ====================================================== -->

    <section
        style="
            background:#fff;
            padding:25px;
            border-radius:20px;
            box-shadow:
                0 8px 25px
                rgba(15,23,42,.07);
        "
    >

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:20px;
            "
        >

            <h2>
                Recent Products
            </h2>

            <a
                href="products.php"
                style="
                    color:#2563eb;
                    text-decoration:none;
                    font-weight:700;
                "
            >
                Manage Products →
            </a>

        </div>


        <?php if (empty($recentProducts)): ?>

            <p>
                You have not added any products yet.
            </p>

            <a
                href="add_product.php"
                class="btn"
                style="
                    background:#2563eb;
                    color:#fff;
                    text-decoration:none;
                "
            >
                Add Your First Product
            </a>

        <?php else: ?>


            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(3, minmax(0,1fr));
                    gap:20px;
                "
            >

                <?php foreach (
                    $recentProducts
                    as $product
                ): ?>


                    <div
                        style="
                            border:1px solid #e2e8f0;
                            border-radius:16px;
                            overflow:hidden;
                            background:#fff;
                        "
                    >

                        <?php

                        $productImage =
                            !empty($product['image'])
                            ? '../uploads/products/' .
                              $product['image']
                            : '../image/logo.jpg';

                        ?>


                        <img
                            src="<?php
                                echo htmlspecialchars(
                                    $productImage
                                );
                            ?>"
                            alt="<?php
                                echo htmlspecialchars(
                                    $product['product_name']
                                );
                            ?>"
                            style="
                                width:100%;
                                height:180px;
                                object-fit:cover;
                            "
                        >


                        <div
                            style="
                                padding:16px;
                            "
                        >

                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $product['product_name']
                                );

                                ?>

                            </h3>


                            <p
                                style="
                                    color:#64748b;
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $product['category_name']
                                    ?? 'Uncategorized'
                                );

                                ?>

                            </p>


                            <strong
                                style="
                                    color:#2563eb;
                                "
                            >

                                RM

                                <?php

                                echo number_format(
                                    (float)
                                    $product['price'],
                                    2
                                );

                                ?>

                            </strong>


                            <p>

                                Stock:

                                <?php

                                echo (int)
                                    $product['stock_quantity'];

                                ?>

                            </p>


                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $product['status']
                                );

                                ?>

                            </span>

                        </div>

                    </div>


                <?php endforeach; ?>

            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>