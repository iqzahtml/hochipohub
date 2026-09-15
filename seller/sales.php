<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER SALES
|--------------------------------------------------------------------------
| File:
| seller/sales.php
|--------------------------------------------------------------------------
| Seller sales, revenue, commission and earnings dashboard.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG / DATABASE / SESSION / FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| ROLE
|--------------------------------------------------------------------------
*/

$currentRole =
    strtolower(
        trim(
            (string) (
                $_SESSION['role']
                ?? $_SESSION['user_role']
                ?? ''
            )
        )
    );


if ($currentRole !== 'vendor') {

    header(
        'Location: ' .
        BASE_URL .
        'dashboard.php'
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

$db = getDB();


if (!($db instanceof PDO)) {

    die(
        'Database connection is not available.'
    );
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('salesEscape')) {

    function salesEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('salesMoney')) {

    function salesMoney($value): string
    {
        return number_format(
            (float) $value,
            2
        );
    }
}


if (!function_exists('salesDate')) {

    function salesDate($value): string
    {
        if (empty($value)) {
            return '—';
        }


        $time =
            strtotime(
                (string) $value
            );


        if (!$time) {
            return '—';
        }


        return date(
            'd M Y',
            $time
        );
    }
}


if (!function_exists('salesDateTime')) {

    function salesDateTime($value): string
    {
        if (empty($value)) {
            return '—';
        }


        $time =
            strtotime(
                (string) $value
            );


        if (!$time) {
            return '—';
        }


        return date(
            'd M Y, h:i A',
            $time
        );
    }
}


if (!function_exists('salesImage')) {

    function salesImage($image): string
    {
        $image =
            trim(
                (string) $image
            );


        if ($image === '') {
            return '';
        }


        if (
            preg_match(
                '/^https?:\/\//i',
                $image
            )
        ) {

            return $image;
        }


        if (
            str_starts_with(
                $image,
                'uploads/'
            )
        ) {

            return
                BASE_URL .
                $image;
        }


        return
            BASE_URL .
            'uploads/products/' .
            rawurlencode(
                basename(
                    $image
                )
            );
    }
}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Old field:
|     cod_delivery_fee
|
| Current database field:
|     vendor_delivery_fee
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                v.vendor_id,
                v.user_id,
                v.business_name,
                v.business_logo,
                v.business_description,
                v.business_address,
                v.category,
                v.delivery_method,
                v.postage_fee,
                v.allow_vendor_delivery,
                v.cod_enabled,
                v.vendor_delivery_fee,
                v.commission_rate,
                v.approval_status,
                v.created_at,

                u.name,
                u.email,
                u.phone

            FROM vendors v

            INNER JOIN users u
                ON u.user_id =
                   v.user_id

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


} catch (Throwable $e) {

    $vendor = false;
}


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    header(
        'Location: setup_profile.php'
    );

    exit;
}


$vendorId =
    (int) $vendor['vendor_id'];


$currentCommissionRate =
    (float) (
        $vendor['commission_rate']
        ?? 0
    );


$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

$startDate =
    trim(
        (string) (
            $_GET['start_date']
            ?? ''
        )
    );


$endDate =
    trim(
        (string) (
            $_GET['end_date']
            ?? ''
        )
    );


if ($startDate === '') {

    $startDate =
        date(
            'Y-m-01'
        );
}


if ($endDate === '') {

    $endDate =
        date(
            'Y-m-d'
        );
}


/*
|--------------------------------------------------------------------------
| VALIDATE DATE
|--------------------------------------------------------------------------
*/

$startObject =
    DateTime::createFromFormat(
        'Y-m-d',
        $startDate
    );


$endObject =
    DateTime::createFromFormat(
        'Y-m-d',
        $endDate
    );


$validStart =
    $startObject &&
    $startObject->format(
        'Y-m-d'
    ) === $startDate;


$validEnd =
    $endObject &&
    $endObject->format(
        'Y-m-d'
    ) === $endDate;


if (
    !$validStart ||
    !$validEnd ||
    $startDate > $endDate
) {

    $startDate =
        date(
            'Y-m-01'
        );


    $endDate =
        date(
            'Y-m-d'
        );
}


/*
|--------------------------------------------------------------------------
| SUMMARY DEFAULT
|--------------------------------------------------------------------------
*/

$summary = [

    'total_orders' => 0,

    'product_sales' => 0.00,

    'delivery_fees' => 0.00,

    'gross_collected' => 0.00,

    'commission' => 0.00,

    'net_earnings' => 0.00,

    'completed_sales' => 0.00,

    'processing_sales' => 0.00,

    'cancelled_sales' => 0.00,

    'paid_orders' => 0,

    'pending_payments' => 0
];


/*
|--------------------------------------------------------------------------
| SALES SUMMARY
|--------------------------------------------------------------------------
|
| Product Sales
| = vendor_orders.subtotal
|
| Delivery Fees
| = vendor_orders.delivery_fee
|
| Gross
| = Product Sales + Delivery Fees
|
| Commission
| = commission.commission_amount
|
| Net
| = Product Sales - Commission + Delivery Fees
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                COUNT(
                    DISTINCT
                    vo.vendor_order_id
                ) AS total_orders,

                COALESCE(
                    SUM(
                        CASE
                            WHEN vo.vendor_status <> 'Cancelled'
                            THEN vo.subtotal
                            ELSE 0
                        END
                    ),
                    0
                ) AS product_sales,

                COALESCE(
                    SUM(
                        CASE
                            WHEN vo.vendor_status <> 'Cancelled'
                            THEN vo.delivery_fee
                            ELSE 0
                        END
                    ),
                    0
                ) AS delivery_fees,

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

                            WHEN vo.vendor_status IN
                            (
                                'Pending',
                                'Processing',
                                'Ready',
                                'Shipped'
                            )

                            THEN vo.subtotal

                            ELSE 0

                        END
                    ),
                    0
                ) AS processing_sales,

                COALESCE(
                    SUM(
                        CASE
                            WHEN vo.vendor_status = 'Cancelled'
                            THEN vo.subtotal
                            ELSE 0
                        END
                    ),
                    0
                ) AS cancelled_sales

            FROM vendor_orders vo

            WHERE vo.vendor_id = ?

            AND DATE(vo.created_at)
                BETWEEN ? AND ?
        ");


    $stmt->execute([
        $vendorId,
        $startDate,
        $endDate
    ]);


    $data =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($data) {

        $summary['total_orders'] =
            (int) (
                $data['total_orders']
                ?? 0
            );


        $summary['product_sales'] =
            (float) (
                $data['product_sales']
                ?? 0
            );


        $summary['delivery_fees'] =
            (float) (
                $data['delivery_fees']
                ?? 0
            );


        $summary['completed_sales'] =
            (float) (
                $data['completed_sales']
                ?? 0
            );


        $summary['processing_sales'] =
            (float) (
                $data['processing_sales']
                ?? 0
            );


        $summary['cancelled_sales'] =
            (float) (
                $data['cancelled_sales']
                ?? 0
            );
    }


} catch (Throwable $e) {

    // Keep zero values.
}


/*
|--------------------------------------------------------------------------
| COMMISSION SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                COALESCE(
                    SUM(
                        c.commission_amount
                    ),
                    0
                ) AS commission_total

            FROM commission c

            INNER JOIN vendor_orders vo
                ON vo.vendor_order_id =
                   c.vendor_order_id

            WHERE c.vendor_id = ?

            AND vo.vendor_status <> 'Cancelled'

            AND DATE(vo.created_at)
                BETWEEN ? AND ?
        ");


    $stmt->execute([
        $vendorId,
        $startDate,
        $endDate
    ]);


    $commissionData =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($commissionData) {

        $summary['commission'] =
            (float) (
                $commissionData[
                    'commission_total'
                ]
                ?? 0
            );
    }


} catch (Throwable $e) {

    // Keep zero value.
}


/*
|--------------------------------------------------------------------------
| PAYMENT SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                COUNT(
                    DISTINCT
                    CASE

                        WHEN LOWER(
                            COALESCE(
                                p.payment_status,
                                ''
                            )
                        ) = 'paid'

                        THEN vo.vendor_order_id

                    END
                ) AS paid_orders,

                COUNT(
                    DISTINCT
                    CASE

                        WHEN LOWER(
                            COALESCE(
                                p.payment_status,
                                'pending'
                            )
                        ) = 'pending'

                        THEN vo.vendor_order_id

                    END
                ) AS pending_payments

            FROM vendor_orders vo

            INNER JOIN orders o
                ON o.order_id =
                   vo.order_id

            LEFT JOIN payments p
                ON p.payment_id = (

                    SELECT
                        p2.payment_id

                    FROM payments p2

                    WHERE p2.order_id =
                          o.order_id

                    ORDER BY
                        p2.payment_id DESC

                    LIMIT 1
                )

            WHERE vo.vendor_id = ?

            AND vo.vendor_status <> 'Cancelled'

            AND DATE(vo.created_at)
                BETWEEN ? AND ?
        ");


    $stmt->execute([
        $vendorId,
        $startDate,
        $endDate
    ]);


    $paymentData =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($paymentData) {

        $summary['paid_orders'] =
            (int) (
                $paymentData[
                    'paid_orders'
                ]
                ?? 0
            );


        $summary['pending_payments'] =
            (int) (
                $paymentData[
                    'pending_payments'
                ]
                ?? 0
            );
    }


} catch (Throwable $e) {

    // Keep defaults.
}


/*
|--------------------------------------------------------------------------
| FINAL CALCULATIONS
|--------------------------------------------------------------------------
*/

$summary['gross_collected'] =
    $summary['product_sales']
    +
    $summary['delivery_fees'];


$summary['net_earnings'] =
    max(
        0,
        $summary['product_sales']
        -
        $summary['commission']
        +
        $summary['delivery_fees']
    );


/*
|--------------------------------------------------------------------------
| PRODUCT PERFORMANCE
|--------------------------------------------------------------------------
*/

$productSales = [];


try {

    $stmt =
        $db->prepare("
            SELECT

                p.product_id,
                p.product_name,
                p.image,

                COALESCE(
                    SUM(
                        CASE

                            WHEN vo.vendor_status <> 'Cancelled'

                            THEN od.quantity

                            ELSE 0

                        END
                    ),
                    0
                ) AS total_quantity,

                COALESCE(
                    SUM(
                        CASE

                            WHEN vo.vendor_status <> 'Cancelled'

                            THEN od.subtotal

                            ELSE 0

                        END
                    ),
                    0
                ) AS total_revenue

            FROM order_details od

            INNER JOIN products p
                ON p.product_id =
                   od.product_id

            INNER JOIN vendor_orders vo
                ON vo.order_id =
                   od.order_id

                AND vo.vendor_id =
                    p.vendor_id

            WHERE p.vendor_id = ?

            AND DATE(vo.created_at)
                BETWEEN ? AND ?

            GROUP BY

                p.product_id,
                p.product_name,
                p.image

            HAVING total_quantity > 0

            ORDER BY

                total_revenue DESC,
                total_quantity DESC
        ");


    $stmt->execute([
        $vendorId,
        $startDate,
        $endDate
    ]);


    $productSales =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $productSales = [];
}


/*
|--------------------------------------------------------------------------
| DAILY SALES
|--------------------------------------------------------------------------
*/

$dailySales = [];


try {

    $stmt =
        $db->prepare("
            SELECT

                DATE(vo.created_at)
                    AS sale_date,

                COUNT(
                    DISTINCT
                    CASE

                        WHEN vo.vendor_status <> 'Cancelled'

                        THEN vo.vendor_order_id

                    END
                ) AS total_orders,

                COALESCE(
                    SUM(
                        CASE

                            WHEN vo.vendor_status <> 'Cancelled'

                            THEN vo.subtotal

                            ELSE 0

                        END
                    ),
                    0
                ) AS product_sales,

                COALESCE(
                    SUM(
                        CASE

                            WHEN vo.vendor_status <> 'Cancelled'

                            THEN vo.delivery_fee

                            ELSE 0

                        END
                    ),
                    0
                ) AS delivery_fees,

                COALESCE(
                    SUM(
                        CASE

                            WHEN vo.vendor_status <> 'Cancelled'

                            THEN c.commission_amount

                            ELSE 0

                        END
                    ),
                    0
                ) AS commission_amount

            FROM vendor_orders vo

            LEFT JOIN commission c
                ON c.vendor_order_id =
                   vo.vendor_order_id

            WHERE vo.vendor_id = ?

            AND DATE(vo.created_at)
                BETWEEN ? AND ?

            GROUP BY
                DATE(vo.created_at)

            ORDER BY
                sale_date DESC
        ");


    $stmt->execute([
        $vendorId,
        $startDate,
        $endDate
    ]);


    $dailySales =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $dailySales = [];
}


/*
|--------------------------------------------------------------------------
| ORDER SALES HISTORY
|--------------------------------------------------------------------------
*/

$orderSales = [];


try {

    $stmt =
        $db->prepare("
            SELECT

                vo.vendor_order_id,
                vo.order_id,
                vo.subtotal,
                vo.delivery_fee,
                vo.vendor_status,
                vo.tracking_number,
                vo.created_at,
                vo.completed_at,

                o.delivery_method,
                o.order_status,
                o.order_date,

                u.name
                    AS customer_name,

                COALESCE(
                    c.commission_rate,
                    0
                ) AS commission_rate,

                COALESCE(
                    c.commission_amount,
                    0
                ) AS commission_amount,

                p.payment_method,
                p.payment_status

            FROM vendor_orders vo

            INNER JOIN orders o
                ON o.order_id =
                   vo.order_id

            INNER JOIN users u
                ON u.user_id =
                   o.customer_id

            LEFT JOIN commission c
                ON c.vendor_order_id =
                   vo.vendor_order_id

            LEFT JOIN payments p
                ON p.payment_id = (

                    SELECT
                        p2.payment_id

                    FROM payments p2

                    WHERE p2.order_id =
                          o.order_id

                    ORDER BY
                        p2.payment_id DESC

                    LIMIT 1
                )

            WHERE vo.vendor_id = ?

            AND DATE(vo.created_at)
                BETWEEN ? AND ?

            ORDER BY

                vo.created_at DESC,
                vo.vendor_order_id DESC
        ");


    $stmt->execute([
        $vendorId,
        $startDate,
        $endDate
    ]);


    $orderSales =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $orderSales = [];
}


/*
|--------------------------------------------------------------------------
| EXTRA INSIGHTS
|--------------------------------------------------------------------------
*/

$bestSellingProduct =
    !empty($productSales)
        ? $productSales[0]
        : null;


$totalUnitsSold = 0;


foreach (
    $productSales
    as $sale
) {

    $totalUnitsSold +=
        (int) (
            $sale[
                'total_quantity'
            ]
            ?? 0
        );
}


$averageOrderValue = 0;


if (
    $summary['total_orders'] > 0
) {

    $averageOrderValue =
        $summary['product_sales']
        /
        $summary['total_orders'];
}


/*
|--------------------------------------------------------------------------
| PAGE INFORMATION
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Sales | Seller | HochipoHub';


$vendorName =
    trim(
        (string) (
            $vendor['name']
            ?? 'Vendor'
        )
    );


$avatarInitial =
    strtoupper(
        substr(
            $vendorName,
            0,
            1
        )
    );

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
        <?= salesEscape(
            $pageTitle
        ) ?>
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


    <!-- PROJECT CSS -->

    <link
        rel="stylesheet"
        href="<?= salesEscape(
            BASE_URL
        ) ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= salesEscape(
            BASE_URL
        ) ?>css/vendor.css"
    >

    <link
        rel="stylesheet"
        href="<?= salesEscape(
            BASE_URL
        ) ?>css/responsive.css"
    >


<style>

/* =========================================================
   BASE
========================================================= */

* {
    box-sizing: border-box;
}


body.seller-sales-page {

    margin: 0;

    min-height: 100vh;

    overflow-x: hidden;

    color: #14213d;

    background: #f6f8fc;

    font-family:
        Inter,
        Arial,
        sans-serif;
}


/* =========================================================
   SELLER MAIN
========================================================= */

.seller-sales-main {

    width:
        calc(
            100% -
            var(
                --seller-sidebar
            )
        );

    min-height: 100vh;

    margin-left:
        var(
            --seller-sidebar
        );

    background:

        radial-gradient(
            circle at 95% 5%,
            rgba(
                37,
                99,
                235,
                .065
            ),
            transparent 24%
        ),

        #f6f8fc;
}


/* =========================================================
   TOPBAR
========================================================= */

.sales-topbar {

    height: 72px;

    padding:
        0 32px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background:
        rgba(
            255,
            255,
            255,
            .97
        );

    border-bottom:
        1px solid
        #e8edf5;
}


.sales-topbar-label {

    color: #94a3b8;

    font-size: 11px;

    font-weight: 700;
}


.sales-user {

    display: flex;

    align-items: center;

    gap: 10px;
}


.sales-avatar {

    width: 38px;

    height: 38px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #3b82f6,
            #6366f1
        );

    border-radius: 50%;

    font-size: 12px;

    font-weight: 900;
}


.sales-user strong {

    display: block;

    color: #14213d;

    font-size: 11px;
}


.sales-user small {

    display: block;

    margin-top: 2px;

    color: #94a3b8;

    font-size: 8px;
}


/* =========================================================
   CONTENT
========================================================= */

.sales-content {

    width: 100%;

    max-width: 1480px;

    margin:
        0 auto;

    padding:
        30px 32px
        65px;
}


/* =========================================================
   HEADING
========================================================= */

.sales-heading {

    margin-bottom: 22px;
}


.sales-eyebrow {

    display: block;

    margin-bottom: 6px;

    color: #2563eb;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.5px;
}


.sales-heading h1 {

    margin: 0;

    color: #14213d;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:

        clamp(
            26px,
            3vw,
            34px
        );

    font-weight: 800;

    letter-spacing: -.8px;
}


.sales-heading p {

    margin:
        7px 0 0;

    color: #7d8ba0;

    font-size: 11px;
}


/* =========================================================
   HERO
========================================================= */

.sales-hero {

    position: relative;

    overflow: hidden;

    min-height: 185px;

    margin-bottom: 22px;

    padding: 32px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 30px;

    color: #ffffff;

    background:

        linear-gradient(
            115deg,
            #08265a 0%,
            #123d8c 50%,
            #2783ef 100%
        );

    border-radius: 24px;

    box-shadow:

        0
        18px
        42px
        rgba(
            18,
            70,
            150,
            .14
        );
}


.sales-hero::before {

    content: "";

    position: absolute;

    width: 260px;

    height: 260px;

    top: -150px;

    right: -60px;

    background:
        rgba(
            255,
            255,
            255,
            .08
        );

    border-radius: 50%;
}


.sales-hero::after {

    content: "";

    position: absolute;

    width: 140px;

    height: 140px;

    right: 180px;

    bottom: -95px;

    background:
        rgba(
            255,
            255,
            255,
            .05
        );

    border-radius: 50%;
}


.sales-hero-copy {

    position: relative;

    z-index: 2;

    max-width: 700px;
}


.sales-hero-label {

    display: block;

    margin-bottom: 8px;

    color: #a8d4ff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.4px;
}


.sales-hero h2 {

    margin:
        0 0 8px;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 25px;

    font-weight: 800;
}


.sales-hero p {

    max-width: 700px;

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size: 10px;

    line-height: 1.75;
}


.sales-hero-icon {

    position: relative;

    z-index: 2;

    width: 78px;

    height: 78px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            .13
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .22
        );

    border-radius: 21px;

    font-size: 27px;
}


/* =========================================================
   FILTER
========================================================= */

.sales-filter {

    margin-bottom: 22px;

    padding:
        19px 20px;

    display: grid;

    grid-template-columns:
        minmax(
            180px,
            .7fr
        )
        minmax(
            0,
            2fr
        );

    align-items: end;

    gap: 20px;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 17px;

    box-shadow:

        0
        8px
        22px
        rgba(
            40,
            65,
            120,
            .045
        );
}


.sales-filter-title strong {

    display: block;

    margin-bottom: 4px;

    font-size: 11px;

    font-weight: 900;
}


.sales-filter-title span {

    color: #8b99ad;

    font-size: 8px;
}


.sales-filter-form {

    display: grid;

    grid-template-columns:
        1fr
        1fr
        auto
        auto;

    align-items: end;

    gap: 9px;
}


.sales-field label {

    display: block;

    margin-bottom: 6px;

    color: #475569;

    font-size: 8px;

    font-weight: 800;
}


.sales-field input {

    width: 100%;

    height: 40px;

    padding:
        0 11px;

    outline: none;

    color: #334155;

    background: #fbfdff;

    border:
        1px solid
        #dce5ef;

    border-radius: 9px;

    font-family: inherit;

    font-size: 9px;
}


.sales-field input:focus {

    border-color:
        #3b82f6;

    box-shadow:

        0
        0
        0
        3px
        rgba(
            59,
            130,
            246,
            .07
        );
}


.sales-filter-btn {

    min-height: 40px;

    padding:
        0 15px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    border-radius: 9px;

    font-family: inherit;

    font-size: 8px;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;
}


.sales-filter-btn.apply {

    color: #ffffff;

    background: #2563eb;

    border: 0;
}


.sales-filter-btn.reset {

    color: #64748b;

    background: #ffffff;

    border:
        1px solid
        #dce5ef;
}


/* =========================================================
   STAT CARDS
========================================================= */

.sales-stats {

    margin-bottom: 22px;

    display: grid;

    grid-template-columns:

        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap: 16px;
}


.sales-stat {

    position: relative;

    overflow: hidden;

    min-height: 142px;

    padding: 20px;

    background: #ffffff;

    border:
        1px solid
        #e4eaf2;

    border-radius: 18px;

    box-shadow:

        0
        9px
        25px
        rgba(
            40,
            65,
            120,
            .05
        );
}


.sales-stat::after {

    content: "";

    position: absolute;

    width: 95px;

    height: 95px;

    right: -34px;

    bottom: -40px;

    background: #eef4ff;

    border-radius: 50%;
}


.sales-stat.green::after {

    background: #ecfdf3;
}


.sales-stat.orange::after {

    background: #fff7ed;
}


.sales-stat.purple::after {

    background: #f5f3ff;
}


.sales-stat.red::after {

    background: #fef2f2;
}


.sales-stat-icon {

    position: relative;

    z-index: 2;

    width: 41px;

    height: 41px;

    margin-bottom: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 11px;

    font-size: 15px;
}


.sales-stat.green
.sales-stat-icon {

    color: #16a34a;

    background: #ecfdf3;
}


.sales-stat.orange
.sales-stat-icon {

    color: #ea580c;

    background: #fff7ed;
}


.sales-stat.purple
.sales-stat-icon {

    color: #7c3aed;

    background: #f5f3ff;
}


.sales-stat.red
.sales-stat-icon {

    color: #dc2626;

    background: #fef2f2;
}


.sales-stat-label {

    position: relative;

    z-index: 2;

    display: block;

    margin-bottom: 5px;

    color: #7d899d;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .8px;
}


.sales-stat-value {

    position: relative;

    z-index: 2;

    display: block;

    color: #14213d;

    font-size: 21px;

    font-weight: 900;
}


/* =========================================================
   MONEY FLOW
========================================================= */

.sales-flow {

    margin-bottom: 22px;

    padding: 20px;

    display: grid;

    grid-template-columns:

        1fr
        auto
        1fr
        auto
        1fr
        auto
        1fr;

    align-items: center;

    gap: 12px;

    background: #ffffff;

    border:
        1px solid
        #e4eaf2;

    border-radius: 18px;
}


.sales-flow-card {

    min-height: 95px;

    padding: 15px;

    display: flex;

    align-items: center;

    gap: 12px;

    background: #fbfdff;

    border:
        1px solid
        #e6ecf4;

    border-radius: 13px;
}


.sales-flow-icon {

    width: 40px;

    height: 40px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 11px;
}


.sales-flow-card.delivery
.sales-flow-icon {

    color: #7c3aed;

    background: #f5f3ff;
}


.sales-flow-card.commission
.sales-flow-icon {

    color: #dc2626;

    background: #fef2f2;
}


.sales-flow-card.net
.sales-flow-icon {

    color: #15803d;

    background: #ecfdf3;
}


.sales-flow-card span {

    display: block;

    margin-bottom: 4px;

    color: #8a98aa;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .6px;
}


.sales-flow-card strong {

    color: #17345f;

    font-size: 13px;

    font-weight: 900;
}


.sales-flow-symbol {

    color: #a2afbf;

    font-size: 17px;

    font-weight: 900;
}


/* =========================================================
   INSIGHTS
========================================================= */

.sales-insights {

    margin-bottom: 22px;

    display: grid;

    grid-template-columns:

        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap: 16px;
}


.sales-insight {

    padding: 18px;

    display: flex;

    align-items: center;

    gap: 13px;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 16px;
}


.sales-insight-icon {

    width: 45px;

    height: 45px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 12px;

    font-size: 15px;
}


.sales-insight small {

    display: block;

    margin-bottom: 4px;

    color: #8b99ad;

    font-size: 7px;

    font-weight: 800;

    letter-spacing: .6px;
}


.sales-insight strong {

    display: block;

    font-size: 12px;

    font-weight: 900;
}


.sales-insight p {

    margin:
        3px 0 0;

    color: #8090a7;

    font-size: 8px;
}


/* =========================================================
   SECTION
========================================================= */

.sales-section {

    overflow: hidden;

    margin-bottom: 22px;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 21px;

    box-shadow:

        0
        11px
        30px
        rgba(
            40,
            65,
            120,
            .055
        );
}


.sales-section-header {

    min-height: 83px;

    padding:
        19px 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    border-bottom:
        1px solid
        #edf1f5;
}


.sales-section-title {

    display: flex;

    align-items: center;

    gap: 12px;
}


.sales-section-icon {

    width: 44px;

    height: 44px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #3b82f6
        );

    border-radius: 12px;

    font-size: 15px;
}


.sales-section-title h2 {

    margin:
        0 0 4px;

    color: #14213d;

    font-size: 15px;

    font-weight: 900;
}


.sales-section-title p {

    margin: 0;

    color: #8b99ad;

    font-size: 8px;
}


.sales-range-pill {

    min-height: 32px;

    padding:
        0 11px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid
        #dbeafe;

    border-radius: 999px;

    font-size: 8px;

    font-weight: 800;

    white-space: nowrap;
}


/* =========================================================
   TABLE
========================================================= */

.sales-table-wrap {

    width: 100%;

    overflow-x: auto;
}


.sales-table {

    width: 100%;

    min-width: 850px;

    border-collapse: collapse;
}


.sales-table.wide {

    min-width: 1250px;
}


.sales-table thead {

    background: #f8fafc;
}


.sales-table th {

    height: 44px;

    padding:
        0 17px;

    color: #64748b;

    border-bottom:
        1px solid
        #e6ebf2;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .55px;

    text-align: left;

    text-transform: uppercase;

    white-space: nowrap;
}


.sales-table td {

    padding:
        14px 17px;

    color: #4d607a;

    border-bottom:
        1px solid
        #edf1f5;

    font-size: 9px;

    vertical-align: middle;
}


.sales-table tbody tr:hover {

    background: #fbfdff;
}


.sales-table tbody tr:last-child td {

    border-bottom: 0;
}


/* =========================================================
   PRODUCT
========================================================= */

.sales-product {

    display: flex;

    align-items: center;

    gap: 11px;
}


.sales-product-image {

    width: 52px;

    height: 52px;

    flex-shrink: 0;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid
        #dbeafe;

    border-radius: 11px;

    font-size: 18px;
}


.sales-product-image img {

    width: 100%;

    height: 100%;

    object-fit: contain;
}


.sales-product-name {

    color: #14213d;

    font-size: 9px;

    font-weight: 900;
}


/* =========================================================
   MONEY
========================================================= */

.sales-money {

    color: #12366a;

    font-size: 10px;

    font-weight: 900;

    white-space: nowrap;
}


.sales-money.green {

    color: #15803d;
}


.sales-money.red {

    color: #dc2626;
}


/* =========================================================
   BADGES
========================================================= */

.sales-badge {

    min-height: 27px;

    padding:
        0 9px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 999px;

    font-size: 8px;

    font-weight: 800;

    white-space: nowrap;
}


.sales-badge.blue {

    color: #2563eb;

    background: #eff6ff;
}


.sales-badge.green {

    color: #15803d;

    background: #ecfdf3;
}


.sales-badge.orange {

    color: #b45309;

    background: #fffbeb;
}


.sales-badge.red {

    color: #b91c1c;

    background: #fef2f2;
}


.sales-badge.gray {

    color: #64748b;

    background: #f1f5f9;
}


/* =========================================================
   ORDER CELL
========================================================= */

.sales-order strong {

    display: block;

    margin-bottom: 3px;

    color: #14213d;

    font-size: 9px;

    font-weight: 900;
}


.sales-order small {

    color: #94a3b8;

    font-size: 7px;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.sales-empty {

    padding:
        65px 20px;

    text-align: center;
}


.sales-empty-icon {

    width: 60px;

    height: 60px;

    margin:
        0 auto 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 17px;

    font-size: 23px;
}


.sales-empty h3 {

    margin:
        0 0 6px;

    font-size: 14px;

    font-weight: 900;
}


.sales-empty p {

    margin: 0;

    color: #8492a6;

    font-size: 9px;
}


/* =========================================================
   COMMISSION INFO
========================================================= */

.sales-commission-info {

    margin-bottom: 22px;

    padding:
        14px 17px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 14px;
}


.sales-commission-info-left {

    display: flex;

    align-items: center;

    gap: 11px;
}


.sales-commission-info-icon {

    width: 39px;

    height: 39px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #7c3aed;

    background: #f5f3ff;

    border-radius: 10px;
}


.sales-commission-info span {

    display: block;

    margin-bottom: 2px;

    color: #8492a6;

    font-size: 8px;
}


.sales-commission-info strong {

    color: #14213d;

    font-size: 11px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1180px
) {

    .sales-stats {

        grid-template-columns:

            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );
    }


    .sales-flow {

        grid-template-columns:
            1fr;
    }


    .sales-flow-symbol {

        text-align: center;

        transform:
            rotate(
                90deg
            );
    }


    .sales-insights {

        grid-template-columns:
            1fr;
    }
}


@media (
    max-width: 900px
) {

    .sales-filter {

        grid-template-columns:
            1fr;
    }
}


@media (
    max-width: 768px
) {

    .seller-sales-main {

        width: 100%;

        margin-left: 0;
    }


    .sales-topbar {

        padding:
            0 20px;
    }


    .sales-content {

        padding:
            24px 20px
            50px;
    }


    .sales-filter-form {

        grid-template-columns:
            1fr
            1fr;
    }


    .sales-filter-btn {

        width: 100%;
    }
}


@media (
    max-width: 600px
) {

    .sales-content {

        padding:
            20px 14px
            45px;
    }


    .sales-user
    > div:last-child {

        display: none;
    }


    .sales-hero {

        min-height: auto;

        padding: 23px;

        align-items: flex-start;
    }


    .sales-hero h2 {

        font-size: 20px;
    }


    .sales-hero-icon {

        width: 53px;

        height: 53px;

        font-size: 19px;
    }


    .sales-stats {

        grid-template-columns:
            1fr;
    }


    .sales-filter-form {

        grid-template-columns:
            1fr;
    }


    .sales-section-header {

        align-items: flex-start;

        flex-direction: column;
    }


    .sales-commission-info {

        align-items: flex-start;

        flex-direction: column;
    }
}

</style>

</head>


<body
    class="
        seller-dashboard-page
        seller-sales-page
    "
>


<?php

/*
|--------------------------------------------------------------------------
| SHARED SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<main class="seller-sales-main">


    <!-- =========================================================
         TOPBAR
    ========================================================== -->

    <header class="sales-topbar">


        <span class="sales-topbar-label">

            Seller Center

        </span>


        <div class="sales-user">


            <div class="sales-avatar">

                <?= salesEscape(
                    $avatarInitial
                ) ?>

            </div>


            <div>


                <strong>

                    <?= salesEscape(
                        $vendorName
                    ) ?>

                </strong>


                <small>

                    <?= salesEscape(
                        $vendor[
                            'business_name'
                        ]
                    ) ?>

                </small>


            </div>


        </div>


    </header>



    <!-- =========================================================
         CONTENT
    ========================================================== -->

    <div class="sales-content">


        <!-- =====================================================
             TITLE
        ====================================================== -->

        <section class="sales-heading">


            <span class="sales-eyebrow">

                SALES & PERFORMANCE

            </span>


            <h1>

                Sales Overview

            </h1>


            <p>

                Track sales, delivery fees,
                commission and earnings for

                <strong>

                    <?= salesEscape(
                        $vendor[
                            'business_name'
                        ]
                    ) ?>

                </strong>.

            </p>


        </section>



        <!-- =====================================================
             HERO
        ====================================================== -->

        <section class="sales-hero">


            <div class="sales-hero-copy">


                <span class="sales-hero-label">

                    STORE PERFORMANCE

                </span>


                <h2>

                    Understand where your money goes.

                </h2>


                <p>

                    Product sales, delivery fees,
                    commission deductions and estimated
                    net earnings are separated so you can
                    understand your store's actual performance.

                </p>


            </div>


            <div class="sales-hero-icon">

                <i class="fa-solid fa-chart-line"></i>

            </div>


        </section>



        <!-- =====================================================
             FILTER
        ====================================================== -->

        <section class="sales-filter">


            <div class="sales-filter-title">


                <strong>

                    Sales Period

                </strong>


                <span>

                    Choose a date range to analyse
                    your store performance.

                </span>


            </div>


            <form
                method="GET"
                action="sales.php"
                class="sales-filter-form"
            >


                <div class="sales-field">


                    <label for="start_date">

                        From

                    </label>


                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= salesEscape(
                            $startDate
                        ) ?>"
                        required
                    >


                </div>


                <div class="sales-field">


                    <label for="end_date">

                        To

                    </label>


                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?= salesEscape(
                            $endDate
                        ) ?>"
                        required
                    >


                </div>


                <button
                    type="submit"
                    class="
                        sales-filter-btn
                        apply
                    "
                >

                    <i class="fa-solid fa-filter"></i>

                    Apply

                </button>


                <a
                    href="sales.php"
                    class="
                        sales-filter-btn
                        reset
                    "
                >

                    Reset

                </a>


            </form>


        </section>



        <!-- =====================================================
             PRIMARY STATS
        ====================================================== -->

        <section class="sales-stats">


            <article class="sales-stat">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>


                <span class="sales-stat-label">

                    TOTAL ORDERS

                </span>


                <strong class="sales-stat-value">

                    <?= number_format(
                        $summary[
                            'total_orders'
                        ]
                    ) ?>

                </strong>


            </article>



            <article class="sales-stat purple">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-bag-shopping"></i>

                </div>


                <span class="sales-stat-label">

                    PRODUCT SALES

                </span>


                <strong class="sales-stat-value">

                    RM <?= salesMoney(
                        $summary[
                            'product_sales'
                        ]
                    ) ?>

                </strong>


            </article>



            <article class="sales-stat orange">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-percent"></i>

                </div>


                <span class="sales-stat-label">

                    COMMISSION

                </span>


                <strong class="sales-stat-value">

                    RM <?= salesMoney(
                        $summary[
                            'commission'
                        ]
                    ) ?>

                </strong>


            </article>



            <article class="sales-stat green">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-wallet"></i>

                </div>


                <span class="sales-stat-label">

                    NET EARNINGS

                </span>


                <strong class="sales-stat-value">

                    RM <?= salesMoney(
                        $summary[
                            'net_earnings'
                        ]
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =====================================================
             COMMISSION RATE INFO
        ====================================================== -->

        <section class="sales-commission-info">


            <div class="sales-commission-info-left">


                <div class="sales-commission-info-icon">

                    <i class="fa-solid fa-percent"></i>

                </div>


                <div>


                    <span>

                        CURRENT STORE COMMISSION RATE

                    </span>


                    <strong>

                        <?= number_format(
                            $currentCommissionRate,
                            2
                        ) ?>%

                    </strong>


                </div>


            </div>


            <span>

                The rate shown here is your
                current vendor commission setting.

            </span>


        </section>



        <!-- =====================================================
             MONEY FLOW
        ====================================================== -->

        <section class="sales-flow">


            <article class="sales-flow-card">


                <div class="sales-flow-icon">

                    <i class="fa-solid fa-box"></i>

                </div>


                <div>

                    <span>

                        PRODUCT SALES

                    </span>

                    <strong>

                        RM <?= salesMoney(
                            $summary[
                                'product_sales'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


            <div class="sales-flow-symbol">
                +
            </div>


            <article class="
                sales-flow-card
                delivery
            ">


                <div class="sales-flow-icon">

                    <i class="fa-solid fa-truck"></i>

                </div>


                <div>

                    <span>

                        DELIVERY FEES

                    </span>

                    <strong>

                        RM <?= salesMoney(
                            $summary[
                                'delivery_fees'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


            <div class="sales-flow-symbol">
                −
            </div>


            <article class="
                sales-flow-card
                commission
            ">


                <div class="sales-flow-icon">

                    <i class="fa-solid fa-percent"></i>

                </div>


                <div>

                    <span>

                        COMMISSION

                    </span>

                    <strong>

                        RM <?= salesMoney(
                            $summary[
                                'commission'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


            <div class="sales-flow-symbol">
                =
            </div>


            <article class="
                sales-flow-card
                net
            ">


                <div class="sales-flow-icon">

                    <i class="fa-solid fa-sack-dollar"></i>

                </div>


                <div>

                    <span>

                        NET EARNINGS

                    </span>

                    <strong>

                        RM <?= salesMoney(
                            $summary[
                                'net_earnings'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


        </section>



        <!-- =====================================================
             SECONDARY STATS
        ====================================================== -->

        <section class="sales-stats">


            <article class="sales-stat green">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <span class="sales-stat-label">

                    COMPLETED SALES

                </span>


                <strong class="sales-stat-value">

                    RM <?= salesMoney(
                        $summary[
                            'completed_sales'
                        ]
                    ) ?>

                </strong>


            </article>



            <article class="sales-stat orange">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <span class="sales-stat-label">

                    ACTIVE SALES

                </span>


                <strong class="sales-stat-value">

                    RM <?= salesMoney(
                        $summary[
                            'processing_sales'
                        ]
                    ) ?>

                </strong>


            </article>



            <article class="sales-stat purple">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-credit-card"></i>

                </div>


                <span class="sales-stat-label">

                    PAID ORDERS

                </span>


                <strong class="sales-stat-value">

                    <?= number_format(
                        $summary[
                            'paid_orders'
                        ]
                    ) ?>

                </strong>


            </article>



            <article class="sales-stat red">


                <div class="sales-stat-icon">

                    <i class="fa-solid fa-hourglass-half"></i>

                </div>


                <span class="sales-stat-label">

                    PENDING PAYMENTS

                </span>


                <strong class="sales-stat-value">

                    <?= number_format(
                        $summary[
                            'pending_payments'
                        ]
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =====================================================
             INSIGHTS
        ====================================================== -->

        <section class="sales-insights">


            <article class="sales-insight">


                <div class="sales-insight-icon">

                    <i class="fa-solid fa-trophy"></i>

                </div>


                <div>


                    <small>

                        TOP PRODUCT

                    </small>


                    <strong>


                        <?php if ($bestSellingProduct): ?>


                            <?= salesEscape(
                                $bestSellingProduct[
                                    'product_name'
                                ]
                            ) ?>


                        <?php else: ?>


                            No sales yet


                        <?php endif; ?>


                    </strong>


                    <p>


                        <?php if ($bestSellingProduct): ?>


                            RM
                            <?= salesMoney(
                                $bestSellingProduct[
                                    'total_revenue'
                                ]
                            ) ?>
                            revenue


                        <?php else: ?>


                            Product performance
                            will appear here.


                        <?php endif; ?>


                    </p>


                </div>


            </article>



            <article class="sales-insight">


                <div class="sales-insight-icon">

                    <i class="fa-solid fa-boxes-stacked"></i>

                </div>


                <div>


                    <small>

                        UNITS SOLD

                    </small>


                    <strong>

                        <?= number_format(
                            $totalUnitsSold
                        ) ?>

                        unit<?= $totalUnitsSold !== 1
                            ? 's'
                            : '' ?>

                    </strong>


                    <p>

                        Across non-cancelled sales.

                    </p>


                </div>


            </article>



            <article class="sales-insight">


                <div class="sales-insight-icon">

                    <i class="fa-solid fa-calculator"></i>

                </div>


                <div>


                    <small>

                        AVERAGE ORDER VALUE

                    </small>


                    <strong>

                        RM <?= salesMoney(
                            $averageOrderValue
                        ) ?>

                    </strong>


                    <p>

                        Average product subtotal
                        per seller order.

                    </p>


                </div>


            </article>


        </section>



        <!-- =====================================================
             SALES TRANSACTIONS
        ====================================================== -->

        <section class="sales-section">


            <div class="sales-section-header">


                <div class="sales-section-title">


                    <div class="sales-section-icon">

                        <i class="fa-solid fa-receipt"></i>

                    </div>


                    <div>


                        <h2>

                            Sales Transactions

                        </h2>


                        <p>

                            Revenue, commission and net earnings
                            for each seller order.

                        </p>


                    </div>


                </div>


                <span class="sales-range-pill">

                    <?= number_format(
                        count(
                            $orderSales
                        )
                    ) ?>

                    transaction<?= count(
                        $orderSales
                    ) !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>



            <?php if (
                empty(
                    $orderSales
                )
            ): ?>


                <div class="sales-empty">


                    <div class="sales-empty-icon">

                        <i class="fa-solid fa-receipt"></i>

                    </div>


                    <h3>

                        No sales transactions

                    </h3>


                    <p>

                        No orders were found
                        for this period.

                    </p>


                </div>


            <?php else: ?>


                <div class="sales-table-wrap">


                    <table class="
                        sales-table
                        wide
                    ">


                        <thead>


                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Delivery
                                </th>

                                <th>
                                    Product Sales
                                </th>

                                <th>
                                    Delivery Fee
                                </th>

                                <th>
                                    Commission
                                </th>

                                <th>
                                    Net
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Order Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                        <?php foreach (
                            $orderSales
                            as $sale
                        ): ?>


                            <?php

                            $subtotal =
                                (float) (
                                    $sale[
                                        'subtotal'
                                    ]
                                    ?? 0
                                );


                            $deliveryFee =
                                (float) (
                                    $sale[
                                        'delivery_fee'
                                    ]
                                    ?? 0
                                );


                            $commission =
                                (float) (
                                    $sale[
                                        'commission_amount'
                                    ]
                                    ?? 0
                                );


                            $net =
                                max(
                                    0,
                                    $subtotal
                                    -
                                    $commission
                                    +
                                    $deliveryFee
                                );


                            $vendorStatus =
                                $sale[
                                    'vendor_status'
                                ]
                                ?? 'Pending';


                            $statusLower =
                                strtolower(
                                    (string)
                                    $vendorStatus
                                );


                            if (
                                $statusLower ===
                                'completed'
                            ) {

                                $statusClass =
                                    'green';

                            } elseif (
                                $statusLower ===
                                'cancelled'
                            ) {

                                $statusClass =
                                    'red';

                            } elseif (
                                in_array(
                                    $statusLower,
                                    [
                                        'processing',
                                        'ready',
                                        'shipped'
                                    ],
                                    true
                                )
                            ) {

                                $statusClass =
                                    'blue';

                            } else {

                                $statusClass =
                                    'orange';
                            }


                            $paymentStatus =
                                $sale[
                                    'payment_status'
                                ]
                                ?? 'Pending';


                            $paymentLower =
                                strtolower(
                                    (string)
                                    $paymentStatus
                                );


                            if (
                                $paymentLower ===
                                'paid'
                            ) {

                                $paymentClass =
                                    'green';

                            } elseif (
                                $paymentLower ===
                                'failed'
                            ) {

                                $paymentClass =
                                    'red';

                            } elseif (
                                $paymentLower ===
                                'refunded'
                            ) {

                                $paymentClass =
                                    'blue';

                            } else {

                                $paymentClass =
                                    'orange';
                            }

                            ?>


                            <tr>


                                <td>


                                    <div class="sales-order">


                                        <strong>

                                            Order
                                            #<?= (int)
                                                $sale[
                                                    'order_id'
                                                ] ?>

                                        </strong>


                                        <small>

                                            Vendor Order
                                            #<?= (int)
                                                $sale[
                                                    'vendor_order_id'
                                                ] ?>

                                        </small>


                                    </div>


                                </td>



                                <td>

                                    <?= salesEscape(
                                        $sale[
                                            'customer_name'
                                        ]
                                        ?? 'Customer'
                                    ) ?>

                                </td>



                                <td>


                                    <span class="
                                        sales-badge
                                        blue
                                    ">

                                        <?= salesEscape(
                                            $sale[
                                                'delivery_method'
                                            ]
                                            ?? '—'
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="sales-money">

                                        RM
                                        <?= salesMoney(
                                            $subtotal
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="sales-money">

                                        RM
                                        <?= salesMoney(
                                            $deliveryFee
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="
                                        sales-money
                                        red
                                    ">

                                        − RM
                                        <?= salesMoney(
                                            $commission
                                        ) ?>

                                    </span>


                                    <div
                                        style="
                                            margin-top:3px;
                                            color:#94a3b8;
                                            font-size:7px;
                                        "
                                    >

                                        <?= number_format(
                                            (float) (
                                                $sale[
                                                    'commission_rate'
                                                ]
                                                ?? 0
                                            ),
                                            2
                                        ) ?>%

                                    </div>


                                </td>



                                <td>


                                    <span class="
                                        sales-money
                                        green
                                    ">

                                        RM
                                        <?= salesMoney(
                                            $net
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <strong
                                        style="
                                            display:block;
                                            margin-bottom:4px;
                                            color:#334155;
                                            font-size:8px;
                                        "
                                    >

                                        <?= salesEscape(
                                            $sale[
                                                'payment_method'
                                            ]
                                            ?? '—'
                                        ) ?>

                                    </strong>


                                    <span class="
                                        sales-badge
                                        <?= salesEscape(
                                            $paymentClass
                                        ) ?>
                                    ">

                                        <?= salesEscape(
                                            $paymentStatus
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="
                                        sales-badge
                                        <?= salesEscape(
                                            $statusClass
                                        ) ?>
                                    ">

                                        <?= salesEscape(
                                            $vendorStatus
                                        ) ?>

                                    </span>


                                </td>



                                <td>

                                    <?= salesEscape(
                                        salesDate(
                                            $sale[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </section>



        <!-- =====================================================
             PRODUCT PERFORMANCE
        ====================================================== -->

        <section class="sales-section">


            <div class="sales-section-header">


                <div class="sales-section-title">


                    <div class="sales-section-icon">

                        <i class="fa-solid fa-box"></i>

                    </div>


                    <div>


                        <h2>

                            Product Performance

                        </h2>


                        <p>

                            Compare units sold and
                            product revenue.

                        </p>


                    </div>


                </div>


                <span class="sales-range-pill">

                    <?= salesEscape(
                        salesDate(
                            $startDate
                        )
                    ) ?>

                    &nbsp;→&nbsp;

                    <?= salesEscape(
                        salesDate(
                            $endDate
                        )
                    ) ?>

                </span>


            </div>



            <?php if (
                empty(
                    $productSales
                )
            ): ?>


                <div class="sales-empty">


                    <div class="sales-empty-icon">

                        <i class="fa-solid fa-chart-column"></i>

                    </div>


                    <h3>

                        No product sales yet

                    </h3>


                    <p>

                        No product sales were found
                        for this period.

                    </p>


                </div>


            <?php else: ?>


                <div class="sales-table-wrap">


                    <table class="sales-table">


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
                            $productSales
                            as $productSale
                        ): ?>


                            <?php

                            $image =
                                salesImage(
                                    $productSale[
                                        'image'
                                    ]
                                    ?? ''
                                );

                            ?>


                            <tr>


                                <td>


                                    <div class="sales-product">


                                        <div class="sales-product-image">


                                            <?php if (
                                                $image !== ''
                                            ): ?>


                                                <img
                                                    src="<?= salesEscape(
                                                        $image
                                                    ) ?>"
                                                    alt="<?= salesEscape(
                                                        $productSale[
                                                            'product_name'
                                                        ]
                                                    ) ?>"
                                                    loading="lazy"
                                                    onerror="
                                                        this.style.display='none';
                                                        this.parentElement.innerHTML='<i class=&quot;fa-solid fa-image&quot;></i>';
                                                    "
                                                >


                                            <?php else: ?>


                                                <i class="fa-solid fa-image"></i>


                                            <?php endif; ?>


                                        </div>


                                        <span class="sales-product-name">

                                            <?= salesEscape(
                                                $productSale[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </span>


                                    </div>


                                </td>



                                <td>


                                    <span class="
                                        sales-badge
                                        blue
                                    ">

                                        <?= number_format(
                                            (int)
                                            $productSale[
                                                'total_quantity'
                                            ]
                                        ) ?>

                                        unit<?= (int)
                                            $productSale[
                                                'total_quantity'
                                            ] !== 1
                                                ? 's'
                                                : '' ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="sales-money">

                                        RM
                                        <?= salesMoney(
                                            $productSale[
                                                'total_revenue'
                                            ]
                                        ) ?>

                                    </span>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </section>



        <!-- =====================================================
             DAILY SALES
        ====================================================== -->

        <section class="sales-section">


            <div class="sales-section-header">


                <div class="sales-section-title">


                    <div class="sales-section-icon">

                        <i class="fa-solid fa-calendar-days"></i>

                    </div>


                    <div>


                        <h2>

                            Daily Sales

                        </h2>


                        <p>

                            Daily product sales, delivery,
                            commission and earnings.

                        </p>


                    </div>


                </div>


                <span class="sales-range-pill">

                    <?= number_format(
                        count(
                            $dailySales
                        )
                    ) ?>

                    active day<?= count(
                        $dailySales
                    ) !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>



            <?php if (
                empty(
                    $dailySales
                )
            ): ?>


                <div class="sales-empty">


                    <div class="sales-empty-icon">

                        <i class="fa-solid fa-calendar-xmark"></i>

                    </div>


                    <h3>

                        No daily sales data

                    </h3>


                    <p>

                        Daily sales will appear once
                        orders are recorded.

                    </p>


                </div>


            <?php else: ?>


                <div class="sales-table-wrap">


                    <table class="sales-table">


                        <thead>


                            <tr>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Orders
                                </th>

                                <th>
                                    Product Sales
                                </th>

                                <th>
                                    Delivery
                                </th>

                                <th>
                                    Commission
                                </th>

                                <th>
                                    Net
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                        <?php foreach (
                            $dailySales
                            as $daily
                        ): ?>


                            <?php

                            $dailyProductSales =
                                (float) (
                                    $daily[
                                        'product_sales'
                                    ]
                                    ?? 0
                                );


                            $dailyDelivery =
                                (float) (
                                    $daily[
                                        'delivery_fees'
                                    ]
                                    ?? 0
                                );


                            $dailyCommission =
                                (float) (
                                    $daily[
                                        'commission_amount'
                                    ]
                                    ?? 0
                                );


                            $dailyNet =
                                max(
                                    0,
                                    $dailyProductSales
                                    -
                                    $dailyCommission
                                    +
                                    $dailyDelivery
                                );

                            ?>


                            <tr>


                                <td>


                                    <div class="sales-order">


                                        <strong>

                                            <?= salesEscape(
                                                salesDate(
                                                    $daily[
                                                        'sale_date'
                                                    ]
                                                )
                                            ) ?>

                                        </strong>


                                        <small>

                                            Sales day

                                        </small>


                                    </div>


                                </td>



                                <td>


                                    <span class="
                                        sales-badge
                                        blue
                                    ">

                                        <?= number_format(
                                            (int)
                                            $daily[
                                                'total_orders'
                                            ]
                                        ) ?>

                                        order<?= (int)
                                            $daily[
                                                'total_orders'
                                            ] !== 1
                                                ? 's'
                                                : '' ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="sales-money">

                                        RM
                                        <?= salesMoney(
                                            $dailyProductSales
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="sales-money">

                                        RM
                                        <?= salesMoney(
                                            $dailyDelivery
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="
                                        sales-money
                                        red
                                    ">

                                        − RM
                                        <?= salesMoney(
                                            $dailyCommission
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <span class="
                                        sales-money
                                        green
                                    ">

                                        RM
                                        <?= salesMoney(
                                            $dailyNet
                                        ) ?>

                                    </span>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </section>


    </div>


</main>


</body>

</html>