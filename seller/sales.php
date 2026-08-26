<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER SALES
|--------------------------------------------------------------------------
| File:
| seller/sales.php
|
| Purpose:
| - Display vendor sales
| - Filter sales by date
| - Show sales summary
| - Show product performance
| - Show daily sales
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REQUIRE FILES
|--------------------------------------------------------------------------
*/

require_once '../database/db.php';
require_once '../includes/session.php';


/*
|--------------------------------------------------------------------------
| START SESSION
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

    header("Location: ../index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VENDOR ROLE CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'vendor'
) {

    header("Location: ../dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        vendor_id,
        business_name,
        approval_status
    FROM vendors
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $user_id
]);

$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt->closeCursor();


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    header("Location: setup_profile.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VENDOR ID
|--------------------------------------------------------------------------
*/

$vendor_id = (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';


/*
|--------------------------------------------------------------------------
| DEFAULT START DATE
|--------------------------------------------------------------------------
*/

if ($start_date === '') {

    $start_date = date('Y-m-01');
}


/*
|--------------------------------------------------------------------------
| DEFAULT END DATE
|--------------------------------------------------------------------------
*/

if ($end_date === '') {

    $end_date = date('Y-m-d');
}


/*
|--------------------------------------------------------------------------
| VALIDATE DATE
|--------------------------------------------------------------------------
*/

$start_timestamp = strtotime($start_date);
$end_timestamp   = strtotime($end_date);

if (
    !$start_timestamp ||
    !$end_timestamp ||
    $start_timestamp > $end_timestamp
) {

    $start_date = date('Y-m-01');
    $end_date   = date('Y-m-d');
}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT

        COUNT(
            DISTINCT vo.vendor_order_id
        ) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN vo.vendor_status != 'Cancelled'
                    THEN vo.subtotal
                    ELSE 0
                END
            ),
            0
        ) AS total_sales,

        COALESCE(
            SUM(
                CASE
                    WHEN vo.vendor_status = 'Completed'
                    THEN vo.subtotal
                    ELSE 0
                END
            ),
            0
        ) AS completed_sales,

        COALESCE(
            SUM(
                CASE
                    WHEN vo.vendor_status = 'Pending'
                    THEN vo.subtotal
                    ELSE 0
                END
            ),
            0
        ) AS pending_sales

    FROM vendor_orders vo

    WHERE vo.vendor_id = :vendor_id

    AND DATE(vo.created_at)
        BETWEEN :start_date AND :end_date
");


$stmt->execute([
    ':vendor_id' => $vendor_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);


$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt->closeCursor();


/*
|--------------------------------------------------------------------------
| SALES BY PRODUCT
|--------------------------------------------------------------------------
*/

$product_sales = [];


$stmt = $conn->prepare("
    SELECT

        p.product_id,
        p.product_name,
        p.image,

        COALESCE(
            SUM(
                CASE
                    WHEN vo.vendor_status != 'Cancelled'
                    THEN od.quantity
                    ELSE 0
                END
            ),
            0
        ) AS total_quantity,

        COALESCE(
            SUM(
                CASE
                    WHEN vo.vendor_status != 'Cancelled'
                    THEN od.subtotal
                    ELSE 0
                END
            ),
            0
        ) AS total_revenue

    FROM order_details od

    INNER JOIN products p
        ON od.product_id = p.product_id

    INNER JOIN vendor_orders vo
        ON vo.order_id = od.order_id
        AND vo.vendor_id = p.vendor_id

    WHERE p.vendor_id = :vendor_id

    AND DATE(vo.created_at)
        BETWEEN :start_date AND :end_date

    GROUP BY
        p.product_id,
        p.product_name,
        p.image

    ORDER BY total_revenue DESC
");


$stmt->execute([
    ':vendor_id' => $vendor_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);


while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $product_sales[] = $row;
}


$stmt->closeCursor();


/*
|--------------------------------------------------------------------------
| DAILY SALES
|--------------------------------------------------------------------------
*/

$daily_sales = [];


$stmt = $conn->prepare("
    SELECT

        DATE(vo.created_at) AS sale_date,

        COALESCE(
            SUM(
                CASE
                    WHEN vo.vendor_status != 'Cancelled'
                    THEN vo.subtotal
                    ELSE 0
                END
            ),
            0
        ) AS total_sales,

        COUNT(
            DISTINCT vo.vendor_order_id
        ) AS total_orders

    FROM vendor_orders vo

    WHERE vo.vendor_id = :vendor_id

    AND DATE(vo.created_at)
        BETWEEN :start_date AND :end_date

    GROUP BY DATE(vo.created_at)

    ORDER BY sale_date DESC
");


$stmt->execute([
    ':vendor_id' => $vendor_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);


while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $daily_sales[] = $row;
}


$stmt->closeCursor();

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
        Sales | Seller | HochipoHub
    </title>


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <!-- VENDOR CSS -->

    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >


    <!-- RESPONSIVE CSS -->

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="seller-dashboard-page seller-inner-page">


<div class="dashboard-layout">


    <!-- =====================================================
         VENDOR SIDEBAR
         ===================================================== -->

    <?php include '../includes/vendor_sidebar.php'; ?>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="dashboard-content">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <div class="page-header">

            <div>

                <h1>
                    Sales Overview
                </h1>


                <p>
                    Track your store performance and revenue.
                </p>

            </div>

        </div>


        <!-- =================================================
             DATE FILTER
             ================================================= -->

        <form
            method="GET"
            class="product-filter-form"
        >


            <div>

                <label>
                    From
                </label>


                <input
                    type="date"
                    name="start_date"
                    value="<?= htmlspecialchars(
                        $start_date,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>


            <div>

                <label>
                    To
                </label>


                <input
                    type="date"
                    name="end_date"
                    value="<?= htmlspecialchars(
                        $end_date,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                Apply
            </button>


        </form>


        <!-- =================================================
             SUMMARY
             ================================================= -->

        <div class="stats-grid">


            <!-- TOTAL ORDERS -->

            <div class="stat-card">

                <span>
                    Total Orders
                </span>


                <strong>

                    <?= (int) (
                        $summary['total_orders'] ?? 0
                    ) ?>

                </strong>

            </div>


            <!-- TOTAL SALES -->

            <div class="stat-card">

                <span>
                    Total Sales
                </span>


                <strong>

                    RM

                    <?= number_format(
                        (float) (
                            $summary['total_sales'] ?? 0
                        ),
                        2
                    ) ?>

                </strong>

            </div>


            <!-- COMPLETED SALES -->

            <div class="stat-card">

                <span>
                    Completed Sales
                </span>


                <strong>

                    RM

                    <?= number_format(
                        (float) (
                            $summary['completed_sales'] ?? 0
                        ),
                        2
                    ) ?>

                </strong>

            </div>


            <!-- PENDING SALES -->

            <div class="stat-card">

                <span>
                    Pending Sales
                </span>


                <strong>

                    RM

                    <?= number_format(
                        (float) (
                            $summary['pending_sales'] ?? 0
                        ),
                        2
                    ) ?>

                </strong>

            </div>


        </div>


        <!-- =================================================
             PRODUCT SALES
             ================================================= -->

        <div class="section-card">


            <div class="section-header">

                <h2>
                    Product Performance
                </h2>


                <span>

                    <?= htmlspecialchars(
                        $start_date,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    →

                    <?= htmlspecialchars(
                        $end_date,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>


            <?php if (empty($product_sales)): ?>


                <!-- EMPTY -->

                <div class="empty-state">

                    <p>
                        No sales data available for this period.
                    </p>

                </div>


            <?php else: ?>


                <!-- TABLE -->

                <div class="table-responsive">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>


                                <th>
                                    Units Sold
                                </th>


                                <th>
                                    Revenue
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $product_sales
                                as $sale
                            ): ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>


                                        <div class="table-product">


                                            <?php if (
                                                !empty(
                                                    $sale['image']
                                                )
                                            ): ?>


                                                <img
                                                    src="../uploads/products/<?= htmlspecialchars(
                                                        $sale['image'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $sale['product_name'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                >


                                            <?php endif; ?>


                                            <span>

                                                <?= htmlspecialchars(
                                                    $sale['product_name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </span>


                                        </div>


                                    </td>


                                    <!-- UNITS SOLD -->

                                    <td>

                                        <?= (int) (
                                            $sale['total_quantity']
                                        ) ?>

                                    </td>


                                    <!-- REVENUE -->

                                    <td>

                                        <strong>

                                            RM

                                            <?= number_format(
                                                (float) (
                                                    $sale['total_revenue']
                                                ),
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


        <!-- =================================================
             DAILY SALES
             ================================================= -->

        <div class="section-card">


            <div class="section-header">

                <h2>
                    Daily Sales
                </h2>

            </div>


            <?php if (empty($daily_sales)): ?>


                <!-- EMPTY -->

                <div class="empty-state">

                    <p>
                        No daily sales data available.
                    </p>

                </div>


            <?php else: ?>


                <!-- TABLE -->

                <div class="table-responsive">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Date
                                </th>


                                <th>
                                    Orders
                                </th>


                                <th>
                                    Sales
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                            <?php foreach (
                                $daily_sales
                                as $daily
                            ): ?>


                                <tr>


                                    <!-- DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $daily['sale_date'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <!-- ORDERS -->

                                    <td>

                                        <?= (int) (
                                            $daily['total_orders']
                                        ) ?>

                                    </td>


                                    <!-- SALES -->

                                    <td>

                                        <strong>

                                            RM

                                            <?= number_format(
                                                (float) (
                                                    $daily['total_sales']
                                                ),
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


<?php include '../includes/footer.php'; ?>


</body>

</html>
