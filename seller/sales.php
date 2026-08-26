<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER SALES
|--------------------------------------------------------------------------
| File:
| seller/sales.php
|--------------------------------------------------------------------------
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
| CONFIG
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/session.php';


/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/functions.php';


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
        (string) $_SESSION['role']
    ) !== 'vendor'
) {

    header(
        'Location: ../dashboard.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (
    !isset($db) ||
    !($db instanceof PDO)
) {

    $db =
        getDB();

}


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

if (!function_exists('sellerSalesEscape')) {

    function sellerSalesEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('sellerSalesDate')) {

    function sellerSalesDate($date): string
    {
        $timestamp =
            strtotime(
                (string) $date
            );


        if (!$timestamp) {
            return '-';
        }


        return date(
            'd M Y',
            $timestamp
        );
    }

}


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
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


/*
|--------------------------------------------------------------------------
| VENDOR ID
|--------------------------------------------------------------------------
*/

$vendorId =
    (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| SYNC SIDEBAR SESSION
|--------------------------------------------------------------------------
*/

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
        $_GET['start_date']
        ?? ''
    );


$endDate =
    trim(
        $_GET['end_date']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| DEFAULT DATE RANGE
|--------------------------------------------------------------------------
*/

if ($startDate === '') {

    $startDate =
        date('Y-m-01');

}


if ($endDate === '') {

    $endDate =
        date('Y-m-d');

}


/*
|--------------------------------------------------------------------------
| VALIDATE DATE
|--------------------------------------------------------------------------
*/

$startTimestamp =
    strtotime(
        $startDate
    );


$endTimestamp =
    strtotime(
        $endDate
    );


if (
    !$startTimestamp ||
    !$endTimestamp ||
    $startTimestamp > $endTimestamp
) {

    $startDate =
        date('Y-m-01');


    $endDate =
        date('Y-m-d');


    $startTimestamp =
        strtotime(
            $startDate
        );


    $endTimestamp =
        strtotime(
            $endDate
        );

}


/*
|--------------------------------------------------------------------------
| SUMMARY DEFAULT
|--------------------------------------------------------------------------
*/

$summary = [

    'total_orders'    => 0,
    'total_sales'     => 0,
    'completed_sales' => 0,
    'pending_sales'   => 0

];


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
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

        $summary = [

            'total_orders' =>
                (int) (
                    $data['total_orders']
                    ?? 0
                ),

            'total_sales' =>
                (float) (
                    $data['total_sales']
                    ?? 0
                ),

            'completed_sales' =>
                (float) (
                    $data['completed_sales']
                    ?? 0
                ),

            'pending_sales' =>
                (float) (
                    $data['pending_sales']
                    ?? 0
                )

        ];

    }

}

catch (Throwable $e) {

}


/*
|--------------------------------------------------------------------------
| PRODUCT SALES
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

            WHERE p.vendor_id = ?

            AND DATE(vo.created_at)
                BETWEEN ? AND ?

            GROUP BY
                p.product_id,
                p.product_name,
                p.image

            ORDER BY
                total_revenue DESC
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

}

catch (Throwable $e) {

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

}

catch (Throwable $e) {

    $dailySales = [];

}


/*
|--------------------------------------------------------------------------
| EXTRA DATA
|--------------------------------------------------------------------------
*/

$bestSellingProduct =
    !empty($productSales)
        ? $productSales[0]
        : null;


$totalUnitsSold = 0;


foreach ($productSales as $sale) {

    $totalUnitsSold +=
        (int) (
            $sale['total_quantity']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Sales | Seller | HochipoHub';

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
        <?= sellerSalesEscape(
            $pageTitle
        ) ?>
    </title>


    <!-- ============================================================
         GOOGLE FONT
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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         FONT AWESOME
    ============================================================= -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >


    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>


        /* ==========================================================
           PAGE
        ========================================================== */

        .seller-sales-page {

            margin: 0;

            min-height:
                100vh;

            overflow-x:
                hidden;

            color:
                #14213d;

            background:
                #f6f8fc;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        /* ==========================================================
           MAIN
        ========================================================== */

        .seller-sales-main {

            width:
                calc(
                    100% -
                    var(
                        --seller-sidebar
                    )
                );

            min-height:
                100vh;

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


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .seller-sales-topbar {

            height:
                72px;

            padding:
                0 32px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .96
                );

            border-bottom:
                1px solid
                #e8edf5;

        }


        .seller-sales-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-sales-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-sales-avatar {

            width:
                38px;

            height:
                38px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #3b82f6,
                    #6366f1
                );

            border-radius:
                50%;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-sales-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-sales-user small {

            display:
                block;

            margin-top:
                2px;

            color:
                #94a3b8;

            font-size:
                8px;

        }


        /* ==========================================================
           CONTENT
        ========================================================== */

        .seller-sales-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                28px 32px 60px;

        }


        /* ==========================================================
           PAGE HEADER
        ========================================================== */

        .seller-sales-header {

            margin-bottom:
                22px;

        }


        .seller-sales-eyebrow {

            display:
                block;

            margin-bottom:
                5px;

            color:
                #2563eb;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.5px;

        }


        .seller-sales-header h1 {

            margin:
                0;

            color:
                #14213d;

            font-size:

                clamp(
                    25px,
                    3vw,
                    33px
                );

            font-weight:
                900;

            letter-spacing:
                -.8px;

        }


        .seller-sales-header p {

            margin:
                7px 0 0;

            color:
                #7b879c;

            font-size:
                11px;

        }


        /* ==========================================================
           HERO
        ========================================================== */

        .seller-sales-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                175px;

            margin-bottom:
                22px;

            padding:
                31px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                25px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123d8c 48%,
                    #2783ef 100%
                );

            border-radius:
                23px;

            box-shadow:

                0
                17px
                38px
                rgba(
                    18,
                    70,
                    150,
                    .13
                );

        }


        .seller-sales-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                220px;

            height:
                220px;

            top:
                -130px;

            right:
                -45px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .08
                );

        }


        .seller-sales-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                145px;

            height:
                145px;

            right:
                150px;

            bottom:
                -100px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

        }


        .seller-sales-hero-copy {

            position:
                relative;

            z-index:
                2;

        }


        .seller-sales-hero-label {

            display:
                block;

            margin-bottom:
                8px;

            color:
                #a8d4ff;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.3px;

        }


        .seller-sales-hero h2 {

            margin:
                0 0 8px;

            color:
                #ffffff;

            font-family:
                Poppins,
                Inter,
                sans-serif;

            font-size:
                25px;

            font-weight:
                800;

        }


        .seller-sales-hero p {

            max-width:
                620px;

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .77
                );

            font-size:
                10px;

            line-height:
                1.7;

        }


        .seller-sales-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                72px;

            height:
                72px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

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

            border-radius:
                20px;

            font-size:
                25px;

        }


        /* ==========================================================
           DATE FILTER
        ========================================================== */

        .seller-sales-filter-card {

            margin-bottom:
                22px;

            padding:
                18px 20px;

            display:
                flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            gap:
                20px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                17px;

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


        .seller-sales-filter-title {

            min-width:
                190px;

        }


        .seller-sales-filter-title strong {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #14213d;

            font-size:
                11px;

            font-weight:
                900;

        }


        .seller-sales-filter-title span {

            color:
                #8b99ad;

            font-size:
                8px;

        }


        .seller-sales-filter-form {

            flex:
                1;

            display:
                grid;

            grid-template-columns:
                1fr
                1fr
                auto
                auto;

            align-items:
                end;

            gap:
                9px;

        }


        .seller-sales-filter-field label {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #475569;

            font-size:
                8px;

            font-weight:
                800;

        }


        .seller-sales-filter-field input {

            width:
                100%;

            height:
                40px;

            padding:
                0 11px;

            outline:
                none;

            color:
                #334155;

            background:
                #fbfdff;

            border:
                1px solid
                #dce5ef;

            border-radius:
                9px;

            font-family:
                inherit;

            font-size:
                8px;

        }


        .seller-sales-filter-field input:focus {

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


        .seller-sales-filter-button {

            min-height:
                40px;

            padding:
                0 14px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                6px;

            border-radius:
                9px;

            font-family:
                inherit;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

        }


        .seller-sales-filter-button.apply {

            color:
                #ffffff;

            background:
                #2563eb;

            border:
                0;

        }


        .seller-sales-filter-button.reset {

            color:
                #64748b;

            background:
                #ffffff;

            border:
                1px solid
                #dce5ef;

        }


        /* ==========================================================
           STATS
        ========================================================== */

        .seller-sales-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                17px;

            margin-bottom:
                22px;

        }


        .seller-sales-stat {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                142px;

            padding:
                20px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                18px;

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


        .seller-sales-stat::after {

            content:
                "";

            position:
                absolute;

            width:
                90px;

            height:
                90px;

            right:
                -32px;

            bottom:
                -38px;

            border-radius:
                50%;

            background:
                #eef4ff;

        }


        .seller-sales-stat.green::after {

            background:
                #ecfdf3;

        }


        .seller-sales-stat.orange::after {

            background:
                #fff7ed;

        }


        .seller-sales-stat.purple::after {

            background:
                #f5f3ff;

        }


        .seller-sales-stat-icon {

            position:
                relative;

            z-index:
                2;

            width:
                40px;

            height:
                40px;

            margin-bottom:
                12px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                11px;

            font-size:
                14px;

        }


        .seller-sales-stat.green
        .seller-sales-stat-icon {

            color:
                #16a34a;

            background:
                #ecfdf3;

        }


        .seller-sales-stat.orange
        .seller-sales-stat-icon {

            color:
                #ea580c;

            background:
                #fff7ed;

        }


        .seller-sales-stat.purple
        .seller-sales-stat-icon {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        .seller-sales-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                5px;

            color:
                #7d899d;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .8px;

        }


        .seller-sales-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #14213d;

            font-size:
                22px;

            line-height:
                1.15;

            font-weight:
                900;

        }


        /* ==========================================================
           INSIGHTS ROW
        ========================================================== */

        .seller-sales-insights {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                17px;

            margin-bottom:
                22px;

        }


        .seller-sales-insight {

            padding:
                18px;

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                16px;

            box-shadow:

                0
                8px
                22px
                rgba(
                    40,
                    65,
                    120,
                    .04
                );

        }


        .seller-sales-insight-icon {

            width:
                45px;

            height:
                45px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                12px;

            font-size:
                15px;

        }


        .seller-sales-insight small {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #8b99ad;

            font-size:
                7px;

            font-weight:
                800;

            letter-spacing:
                .6px;

        }


        .seller-sales-insight strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-sales-insight p {

            margin:
                3px 0 0;

            color:
                #8090a7;

            font-size:
                8px;

        }


        /* ==========================================================
           SECTION CARD
        ========================================================== */

        .seller-sales-section {

            overflow:
                hidden;

            margin-bottom:
                22px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                21px;

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


        .seller-sales-section-header {

            min-height:
                83px;

            padding:
                19px 22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                18px;

            border-bottom:
                1px solid
                #edf1f5;

        }


        .seller-sales-section-title {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

        }


        .seller-sales-section-icon {

            width:
                44px;

            height:
                44px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #3b82f6
                );

            border-radius:
                12px;

            box-shadow:

                0
                8px
                18px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

            font-size:
                15px;

        }


        .seller-sales-section-title h2 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                15px;

            font-weight:
                900;

        }


        .seller-sales-section-title p {

            margin:
                0;

            color:
                #8b99ad;

            font-size:
                8px;

        }


        .seller-sales-range-pill {

            min-height:
                32px;

            padding:
                0 11px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /* ==========================================================
           TABLE
        ========================================================== */

        .seller-sales-table-wrap {

            width:
                100%;

            overflow-x:
                auto;

        }


        .seller-sales-table {

            width:
                100%;

            min-width:
                720px;

            border-collapse:
                collapse;

        }


        .seller-sales-table thead {

            background:
                #f8fafc;

        }


        .seller-sales-table th {

            height:
                42px;

            padding:
                0 18px;

            color:
                #64748b;

            border-bottom:
                1px solid
                #e6ebf2;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .6px;

            text-align:
                left;

            text-transform:
                uppercase;

        }


        .seller-sales-table td {

            padding:
                14px 18px;

            color:
                #4d607a;

            border-bottom:
                1px solid
                #edf1f5;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .seller-sales-table tbody tr:hover {

            background:
                #fbfdff;

        }


        .seller-sales-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /* ==========================================================
           PRODUCT TABLE
        ========================================================== */

        .seller-sales-product {

            display:
                flex;

            align-items:
                center;

            gap:
                11px;

        }


        .seller-sales-product-image {

            width:
                52px;

            height:
                52px;

            flex-shrink:
                0;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                11px;

            font-size:
                18px;

        }


        .seller-sales-product-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                contain;

            object-position:
                center;

        }


        .seller-sales-product-name {

            color:
                #14213d;

            font-size:
                9px;

            font-weight:
                900;

        }


        .seller-sales-unit-badge {

            min-height:
                27px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        .seller-sales-money {

            color:
                #12366a;

            font-size:
                10px;

            font-weight:
                900;

        }


        /* ==========================================================
           DAILY SALES
        ========================================================== */

        .seller-daily-date {

            display:
                flex;

            align-items:
                center;

            gap:
                8px;

        }


        .seller-daily-date-icon {

            width:
                31px;

            height:
                31px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                9px;

            font-size:
                10px;

        }


        .seller-daily-date strong {

            color:
                #14213d;

            font-size:
                9px;

            font-weight:
                900;

        }


        /* ==========================================================
           EMPTY
        ========================================================== */

        .seller-sales-empty {

            padding:
                62px 20px;

            text-align:
                center;

        }


        .seller-sales-empty-icon {

            width:
                60px;

            height:
                60px;

            margin:
                0 auto 13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                17px;

            font-size:
                23px;

        }


        .seller-sales-empty h3 {

            margin:
                0 0 6px;

            color:
                #14213d;

            font-size:
                14px;

            font-weight:
                900;

        }


        .seller-sales-empty p {

            margin:
                0;

            color:
                #8492a6;

            font-size:
                9px;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (
            max-width: 1150px
        ) {

            .seller-sales-stats {

                grid-template-columns:

                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }

        }


        @media (
            max-width: 950px
        ) {

            .seller-sales-filter-card {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-sales-filter-form {

                width:
                    100%;

            }

        }


        @media (
            max-width: 768px
        ) {

            .seller-sales-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-sales-topbar {

                padding:
                    0 20px;

            }


            .seller-sales-content {

                padding:
                    24px 20px 50px;

            }


            .seller-sales-filter-form {

                grid-template-columns:
                    1fr
                    1fr;

            }


            .seller-sales-filter-button {

                width:
                    100%;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-sales-user
            > div:last-child {

                display:
                    none;

            }


            .seller-sales-content {

                padding:
                    20px 14px 45px;

            }


            .seller-sales-hero {

                min-height:
                    auto;

                padding:
                    23px;

                align-items:
                    flex-start;

            }


            .seller-sales-hero h2 {

                font-size:
                    20px;

            }


            .seller-sales-hero-icon {

                width:
                    53px;

                height:
                    53px;

                font-size:
                    19px;

            }


            .seller-sales-stats,
            .seller-sales-insights {

                grid-template-columns:
                    1fr;

            }


            .seller-sales-filter-form {

                grid-template-columns:
                    1fr;

            }


            .seller-sales-section-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-sales-page">


<?php

/*
|--------------------------------------------------------------------------
| SHARED SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-sales-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-sales-topbar">


        <span class="seller-sales-topbar-label">

            Seller Center

        </span>


        <div class="seller-sales-user">


            <div class="seller-sales-avatar">

                <?= sellerSalesEscape(
                    strtoupper(
                        substr(
                            $vendor['name']
                            ?? 'V',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div>


                <strong>

                    <?= sellerSalesEscape(
                        $vendor['name']
                        ?? 'Vendor'
                    ) ?>

                </strong>


                <small>

                    Vendor

                </small>


            </div>


        </div>


    </header>



    <!-- ===========================================================
         CONTENT
    ============================================================ -->

    <div class="seller-sales-content">


        <!-- =======================================================
             PAGE HEADER
        ======================================================== -->

        <section class="seller-sales-header">


            <span class="seller-sales-eyebrow">

                SALES & PERFORMANCE

            </span>


            <h1>

                Sales Overview

            </h1>


            <p>

                Track revenue and product performance for

                <?= sellerSalesEscape(
                    $vendor['business_name']
                ) ?>.

            </p>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-sales-hero">


            <div class="seller-sales-hero-copy">


                <span class="seller-sales-hero-label">

                    STORE PERFORMANCE

                </span>


                <h2>

                    Understand how your store is performing.

                </h2>


                <p>

                    Review your sales totals, completed revenue,
                    pending transactions, top-selling products
                    and daily sales activity in one place.

                </p>


            </div>


            <div class="seller-sales-hero-icon">

                <i class="fa-solid fa-chart-line"></i>

            </div>


        </section>



        <!-- =======================================================
             DATE FILTER
        ======================================================== -->

        <section class="seller-sales-filter-card">


            <div class="seller-sales-filter-title">


                <strong>

                    Sales Period

                </strong>


                <span>

                    Choose the date range you want to analyse.

                </span>


            </div>


            <form
                method="GET"
                action="sales.php"
                class="seller-sales-filter-form"
            >


                <div class="seller-sales-filter-field">


                    <label for="start_date">

                        From

                    </label>


                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= sellerSalesEscape(
                            $startDate
                        ) ?>"
                        required
                    >


                </div>


                <div class="seller-sales-filter-field">


                    <label for="end_date">

                        To

                    </label>


                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?= sellerSalesEscape(
                            $endDate
                        ) ?>"
                        required
                    >


                </div>


                <button
                    type="submit"
                    class="
                        seller-sales-filter-button
                        apply
                    "
                >

                    <i class="fa-solid fa-filter"></i>

                    Apply

                </button>


                <a
                    href="sales.php"
                    class="
                        seller-sales-filter-button
                        reset
                    "
                >

                    Reset

                </a>


            </form>


        </section>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="seller-sales-stats">


            <!-- TOTAL ORDERS -->

            <article class="seller-sales-stat">


                <div class="seller-sales-stat-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>


                <span class="seller-sales-stat-label">

                    TOTAL ORDERS

                </span>


                <strong class="seller-sales-stat-value">

                    <?= number_format(
                        $summary['total_orders']
                    ) ?>

                </strong>


            </article>



            <!-- TOTAL SALES -->

            <article
                class="
                    seller-sales-stat
                    purple
                "
            >


                <div class="seller-sales-stat-icon">

                    <i class="fa-solid fa-chart-column"></i>

                </div>


                <span class="seller-sales-stat-label">

                    TOTAL SALES

                </span>


                <strong class="seller-sales-stat-value">

                    RM
                    <?= number_format(
                        $summary['total_sales'],
                        2
                    ) ?>

                </strong>


            </article>



            <!-- COMPLETED -->

            <article
                class="
                    seller-sales-stat
                    green
                "
            >


                <div class="seller-sales-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <span class="seller-sales-stat-label">

                    COMPLETED SALES

                </span>


                <strong class="seller-sales-stat-value">

                    RM
                    <?= number_format(
                        $summary['completed_sales'],
                        2
                    ) ?>

                </strong>


            </article>



            <!-- PENDING -->

            <article
                class="
                    seller-sales-stat
                    orange
                "
            >


                <div class="seller-sales-stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <span class="seller-sales-stat-label">

                    PENDING SALES

                </span>


                <strong class="seller-sales-stat-value">

                    RM
                    <?= number_format(
                        $summary['pending_sales'],
                        2
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =======================================================
             SMALL INSIGHTS
        ======================================================== -->

        <section class="seller-sales-insights">


            <!-- BEST SELLER -->

            <article class="seller-sales-insight">


                <div class="seller-sales-insight-icon">

                    <i class="fa-solid fa-trophy"></i>

                </div>


                <div>


                    <small>

                        TOP PRODUCT

                    </small>


                    <strong>


                        <?php if ($bestSellingProduct): ?>


                            <?= sellerSalesEscape(
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
                            <?= number_format(
                                (float)
                                $bestSellingProduct[
                                    'total_revenue'
                                ],
                                2
                            ) ?>

                            revenue


                        <?php else: ?>


                            Product performance will appear here.


                        <?php endif; ?>


                    </p>


                </div>


            </article>



            <!-- UNITS -->

            <article class="seller-sales-insight">


                <div class="seller-sales-insight-icon">

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

                        Across all non-cancelled sales in this period.

                    </p>


                </div>


            </article>


        </section>



        <!-- =======================================================
             PRODUCT PERFORMANCE
        ======================================================== -->

        <section class="seller-sales-section">


            <div class="seller-sales-section-header">


                <div class="seller-sales-section-title">


                    <div class="seller-sales-section-icon">

                        <i class="fa-solid fa-box"></i>

                    </div>


                    <div>


                        <h2>

                            Product Performance

                        </h2>


                        <p>

                            Compare units sold and revenue by product.

                        </p>


                    </div>


                </div>


                <span class="seller-sales-range-pill">

                    <?= sellerSalesDate(
                        $startDate
                    ) ?>

                    &nbsp;→&nbsp;

                    <?= sellerSalesDate(
                        $endDate
                    ) ?>

                </span>


            </div>



            <?php if (
                empty(
                    $productSales
                )
            ): ?>


                <div class="seller-sales-empty">


                    <div class="seller-sales-empty-icon">

                        <i class="fa-solid fa-chart-column"></i>

                    </div>


                    <h3>

                        No product sales yet

                    </h3>


                    <p>

                        No sales data is available for
                        the selected period.

                    </p>


                </div>


            <?php else: ?>


                <div class="seller-sales-table-wrap">


                    <table class="seller-sales-table">


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
                                as $sale
                            ): ?>


                                <?php

                                $saleImage =
                                    trim(
                                        (string)
                                        (
                                            $sale['image']
                                            ?? ''
                                        )
                                    );

                                ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>


                                        <div class="seller-sales-product">


                                            <div class="seller-sales-product-image">


                                                <?php if (
                                                    $saleImage !== ''
                                                ): ?>


                                                    <img
                                                        src="../uploads/products/<?= sellerSalesEscape(
                                                            rawurlencode(
                                                                basename(
                                                                    $saleImage
                                                                )
                                                            )
                                                        ) ?>"
                                                        alt="<?= sellerSalesEscape(
                                                            $sale[
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


                                            <span class="seller-sales-product-name">

                                                <?= sellerSalesEscape(
                                                    $sale[
                                                        'product_name'
                                                    ]
                                                ) ?>

                                            </span>


                                        </div>


                                    </td>



                                    <!-- UNITS -->

                                    <td>


                                        <span class="seller-sales-unit-badge">

                                            <?= number_format(
                                                (int)
                                                $sale[
                                                    'total_quantity'
                                                ]
                                            ) ?>

                                            unit<?= (int)
                                                $sale[
                                                    'total_quantity'
                                                ] !== 1
                                                    ? 's'
                                                    : '' ?>

                                        </span>


                                    </td>



                                    <!-- REVENUE -->

                                    <td>


                                        <span class="seller-sales-money">

                                            RM
                                            <?= number_format(
                                                (float)
                                                $sale[
                                                    'total_revenue'
                                                ],
                                                2
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



        <!-- =======================================================
             DAILY SALES
        ======================================================== -->

        <section class="seller-sales-section">


            <div class="seller-sales-section-header">


                <div class="seller-sales-section-title">


                    <div class="seller-sales-section-icon">

                        <i class="fa-solid fa-calendar-days"></i>

                    </div>


                    <div>


                        <h2>

                            Daily Sales

                        </h2>


                        <p>

                            Daily order activity and sales value.

                        </p>


                    </div>


                </div>


                <span class="seller-sales-range-pill">

                    <?= number_format(
                        count(
                            $dailySales
                        )
                    ) ?>

                    active day<?= count($dailySales) !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>



            <?php if (
                empty(
                    $dailySales
                )
            ): ?>


                <div class="seller-sales-empty">


                    <div class="seller-sales-empty-icon">

                        <i class="fa-solid fa-calendar-xmark"></i>

                    </div>


                    <h3>

                        No daily sales data

                    </h3>


                    <p>

                        Daily sales will appear once
                        orders are recorded in this period.

                    </p>


                </div>


            <?php else: ?>


                <div class="seller-sales-table-wrap">


                    <table class="seller-sales-table">


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
                                $dailySales
                                as $daily
                            ): ?>


                                <tr>


                                    <!-- DATE -->

                                    <td>


                                        <div class="seller-daily-date">


                                            <div class="seller-daily-date-icon">

                                                <i class="fa-regular fa-calendar"></i>

                                            </div>


                                            <strong>

                                                <?= sellerSalesDate(
                                                    $daily[
                                                        'sale_date'
                                                    ]
                                                ) ?>

                                            </strong>


                                        </div>


                                    </td>



                                    <!-- ORDERS -->

                                    <td>


                                        <span class="seller-sales-unit-badge">

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



                                    <!-- SALES -->

                                    <td>


                                        <span class="seller-sales-money">

                                            RM
                                            <?= number_format(
                                                (float)
                                                $daily[
                                                    'total_sales'
                                                ],
                                                2
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