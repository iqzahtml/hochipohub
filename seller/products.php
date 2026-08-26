<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER PRODUCTS
|--------------------------------------------------------------------------
| File:
| seller/products.php
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
| ROLE CHECK
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
| DATABASE CHECK
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

if (!function_exists('sellerProductEscape')) {

    function sellerProductEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('sellerProductStatusClass')) {

    function sellerProductStatusClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        switch ($status) {

            case 'available':

                return 'available';


            case 'out of stock':

                return 'out-of-stock';


            case 'hidden':

                return 'hidden';


            default:

                return 'default';

        }
    }

}


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| We fetch the same vendor information used by seller/dashboard.php.
| vendor_sidebar.php can therefore show exactly the same profile details.
|
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
| VENDOR PROFILE NOT FOUND
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
| KEEP SIDEBAR SESSION DATA IN SYNC
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

$statusFilter =
    trim(
        $_GET['status']
        ?? ''
    );


$allowedStatuses = [

    'Available',
    'Out of Stock',
    'Hidden'

];


if (
    $statusFilter !== '' &&
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $statusFilter = '';

}


/*
|--------------------------------------------------------------------------
| PRODUCTS QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        p.created_at,
        p.updated_at,

        c.category_name,

        COALESCE(
            i.quantity,
            p.stock_quantity
        ) AS inventory_quantity

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.category_id

    LEFT JOIN inventory i
        ON p.product_id = i.product_id

    WHERE p.vendor_id = ?
";


$params = [
    $vendorId
];


/*
|--------------------------------------------------------------------------
| SEARCH CONDITION
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND
        (
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR c.category_name LIKE ?
        )
    ";


    $searchValue =
        '%' .
        $search .
        '%';


    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

}


/*
|--------------------------------------------------------------------------
| STATUS CONDITION
|--------------------------------------------------------------------------
*/

if ($statusFilter !== '') {

    $sql .= "
        AND p.status = ?
    ";


    $params[] =
        $statusFilter;

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        p.created_at DESC,
        p.product_id DESC
";


/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $products = [];

}


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


try {

    $stmt =
        $db->prepare("
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


    $stats =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($stats) {

        $productStats['total'] =
            (int) (
                $stats['total']
                ?? 0
            );


        $productStats['available'] =
            (int) (
                $stats['available']
                ?? 0
            );


        $productStats['out_of_stock'] =
            (int) (
                $stats['out_of_stock']
                ?? 0
            );


        $productStats['hidden'] =
            (int) (
                $stats['hidden']
                ?? 0
            );

    }

}

catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | KEEP ZERO DEFAULTS
    |--------------------------------------------------------------------------
    */

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'My Products';

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
        My Products - HochipoHub
    </title>


    <!-- ============================================================
         GOOGLE FONT
         SAME AS SELLER DASHBOARD
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
         REQUIRED BY vendor_sidebar.php
    ============================================================= -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- ============================================================
         SAME CSS AS SELLER DASHBOARD
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
           PRODUCTS PAGE
        ========================================================== */

        .seller-products-page {

            margin:
                0;

            min-height:
                100vh;

            overflow-x:
                hidden;

            background:
                #f6f8fc;

            color:
                var(
                    --seller-text
                );

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        /* ==========================================================
           IMPORTANT
           USE EXACT SAME SIDEBAR WIDTH AS DASHBOARD
        ========================================================== */

        .seller-products-main {

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
                #f6f8fc;

        }


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .seller-products-topbar {

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

            border-bottom:
                1px solid
                #e8edf5;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .95
                );

        }


        .seller-products-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-products-topbar-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-products-topbar-avatar {

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

            border-radius:
                50%;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #3b82f6,
                    #6366f1
                );

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-products-topbar-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-products-topbar-user small {

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
           PAGE CONTENT
        ========================================================== */

        .seller-products-content {

            width:
                100%;

            padding:
                28px 32px 55px;

        }


        /* ==========================================================
           HEADER
        ========================================================== */

        .seller-products-header {

            margin-bottom:
                22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

        }


        .seller-products-eyebrow {

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


        .seller-products-header h1 {

            margin:
                0;

            color:
                #14213d;

            font-size:

                clamp(
                    24px,
                    3vw,
                    32px
                );

            font-weight:
                900;

            letter-spacing:
                -.7px;

        }


        .seller-products-header p {

            margin:
                7px 0 0;

            color:
                #7b879c;

            font-size:
                11px;

        }


        .seller-products-add-btn {

            min-height:
                42px;

            padding:
                0 17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                8px;

            color:
                #ffffff;

            background:
                #2563eb;

            border-radius:
                11px;

            box-shadow:

                0
                9px
                22px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            transition:
                .2s ease;

        }


        .seller-products-add-btn:hover {

            background:
                #1d4ed8;

            transform:
                translateY(-1px);

        }


        /* ==========================================================
           ALERT
        ========================================================== */

        .seller-products-alert {

            margin-bottom:
                20px;

            padding:
                14px 16px;

            border-radius:
                12px;

            font-size:
                10px;

            font-weight:
                600;

        }


        .seller-products-alert.success {

            color:
                #166534;

            background:
                #f0fdf4;

            border:
                1px solid
                #bbf7d0;

        }


        .seller-products-alert.warning {

            color:
                #92400e;

            background:
                #fffbeb;

            border:
                1px solid
                #fde68a;

        }


        .seller-products-alert.error {

            color:
                #b91c1c;

            background:
                #fef2f2;

            border:
                1px solid
                #fecaca;

        }


        /* ==========================================================
           STATISTICS
        ========================================================== */

        .seller-products-stats {

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
                18px;

            margin-bottom:
                24px;

        }


        .seller-product-stat {

            position:
                relative;

            min-height:
                135px;

            overflow:
                hidden;

            padding:
                22px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                20px;

            box-shadow:

                0
                10px
                28px
                rgba(
                    40,
                    65,
                    120,
                    .055
                );

        }


        .seller-product-stat::after {

            content:
                "";

            position:
                absolute;

            width:
                90px;

            height:
                90px;

            right:
                -30px;

            bottom:
                -35px;

            border-radius:
                50%;

            background:
                #eef4ff;

        }


        .seller-product-stat.green::after {

            background:
                #ecfdf3;

        }


        .seller-product-stat.orange::after {

            background:
                #fff7ed;

        }


        .seller-product-stat.purple::after {

            background:
                #f5f3ff;

        }


        .seller-product-stat-icon {

            position:
                relative;

            z-index:
                2;

            width:
                42px;

            height:
                42px;

            margin-bottom:
                13px;

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


        .seller-product-stat.green
        .seller-product-stat-icon {

            color:
                #16a34a;

            background:
                #ecfdf3;

        }


        .seller-product-stat.orange
        .seller-product-stat-icon {

            color:
                #ea580c;

            background:
                #fff7ed;

        }


        .seller-product-stat.purple
        .seller-product-stat-icon {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        .seller-product-stat span {

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
                8px;

            font-weight:
                900;

            letter-spacing:
                .8px;

        }


        .seller-product-stat strong {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #14213d;

            font-size:
                27px;

            font-weight:
                900;

        }


        /* ==========================================================
           PANEL
        ========================================================== */

        .seller-products-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                21px;

            box-shadow:

                0
                12px
                32px
                rgba(
                    40,
                    65,
                    120,
                    .055
                );

        }


        /* ==========================================================
           PANEL HEADER
        ========================================================== */

        .seller-products-panel-header {

            min-height:
                92px;

            padding:
                22px 25px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid
                #edf1f7;

        }


        .seller-products-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

        }


        .seller-products-panel-icon {

            width:
                47px;

            height:
                47px;

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
                13px;

            box-shadow:

                0
                8px
                18px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

            font-size:
                16px;

        }


        .seller-products-panel-header h2 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                17px;

            font-weight:
                900;

        }


        .seller-products-panel-header p {

            margin:
                0;

            color:
                #8793a7;

            font-size:
                9px;

        }


        .seller-products-count {

            min-height:
                34px;

            padding:
                0 13px;

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
                9px;

            font-weight:
                800;

        }


        /* ==========================================================
           FILTER
        ========================================================== */

        .seller-products-filter-wrap {

            padding:
                18px 25px;

            background:
                #fbfcfe;

            border-bottom:
                1px solid
                #edf1f7;

        }


        .seller-products-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1fr
                )

                minmax(
                    150px,
                    210px
                )

                auto

                auto;

            gap:
                9px;

        }


        .seller-products-filter input,
        .seller-products-filter select {

            width:
                100%;

            height:
                41px;

            padding:
                0 12px;

            outline:
                none;

            color:
                #334155;

            background:
                #ffffff;

            border:
                1px solid
                #dfe6ef;

            border-radius:
                10px;

            font-size:
                9px;

        }


        .seller-products-filter input:focus,
        .seller-products-filter select:focus {

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
                    .08
                );

        }


        .seller-products-filter-btn {

            min-height:
                41px;

            padding:
                0 15px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                6px;

            border-radius:
                10px;

            font-size:
                9px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

        }


        .seller-products-filter-btn.primary {

            color:
                #ffffff;

            background:
                #2563eb;

            border:
                0;

        }


        .seller-products-filter-btn.secondary {

            color:
                #64748b;

            background:
                #ffffff;

            border:
                1px solid
                #dfe6ef;

        }


        /* ==========================================================
           PRODUCTS GRID
        ========================================================== */

        .seller-products-grid {

            padding:
                25px;

            display:
                grid;

            grid-template-columns:

                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                20px;

        }


        /* ==========================================================
           PRODUCT CARD
        ========================================================== */

        .seller-product-card {

            min-width:
                0;

            overflow:
                hidden;

            display:
                flex;

            flex-direction:
                column;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                18px;

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

            transition:
                transform .22s ease,
                box-shadow .22s ease;

        }


        .seller-product-card:hover {

            transform:
                translateY(-4px);

            box-shadow:

                0
                16px
                32px
                rgba(
                    40,
                    65,
                    120,
                    .10
                );

        }


        /* ==========================================================
           FIXED PRODUCT IMAGE
        ========================================================== */

        .seller-product-image {

            position:
                relative;

            width:
                100%;

            height:
                220px;

            overflow:
                hidden;

            padding:
                14px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:

                radial-gradient(
                    circle at 80% 15%,
                    rgba(
                        37,
                        99,
                        235,
                        .09
                    ),
                    transparent 30%
                ),

                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fbff
                );

            border-bottom:
                1px solid
                #edf1f7;

        }


        .seller-product-image img {

            display:
                block;

            width:
                100%;

            height:
                100%;

            max-width:
                100%;

            max-height:
                100%;

            object-fit:
                contain;

            object-position:
                center;

            border-radius:
                10px;

        }


        .seller-product-image-empty {

            width:
                66px;

            height:
                66px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #ffffff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                17px;

            font-size:
                25px;

        }


        /* ==========================================================
           PRODUCT BODY
        ========================================================== */

        .seller-product-body {

            flex:
                1;

            padding:
                17px;

            display:
                flex;

            flex-direction:
                column;

        }


        .seller-product-category {

            width:
                fit-content;

            margin-bottom:
                9px;

            padding:
                4px 8px;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                999px;

            font-size:
                7px;

            font-weight:
                800;

        }


        .seller-product-body h3 {

            margin:
                0 0 7px;

            color:
                #14213d;

            font-size:
                14px;

            line-height:
                1.4;

            font-weight:
                900;

            word-break:
                break-word;

        }


        .seller-product-description {

            min-height:
                42px;

            margin:
                0 0 14px;

            color:
                #7b879c;

            font-size:
                9px;

            line-height:
                1.65;

        }


        /* ==========================================================
           PRODUCT META
        ========================================================== */

        .seller-product-meta {

            margin-top:
                auto;

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                8px;

        }


        .seller-product-meta-item {

            padding:
                10px;

            background:
                #f8fafc;

            border:
                1px solid
                #edf1f5;

            border-radius:
                10px;

        }


        .seller-product-meta-item span {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #94a3b8;

            font-size:
                7px;

            font-weight:
                800;

            letter-spacing:
                .4px;

        }


        .seller-product-meta-item strong {

            color:
                #14213d;

            font-size:
                10px;

            font-weight:
                900;

        }


        /* ==========================================================
           STATUS
        ========================================================== */

        .seller-product-status {

            width:
                fit-content;

            min-height:
                27px;

            margin-top:
                11px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            border-radius:
                999px;

            font-size:
                7px;

            font-weight:
                900;

        }


        .seller-product-status::before {

            content:
                "";

            width:
                5px;

            height:
                5px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .seller-product-status.available {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .seller-product-status.out-of-stock {

            color:
                #c2410c;

            background:
                #fff7ed;

        }


        .seller-product-status.hidden {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        .seller-product-status.default {

            color:
                #64748b;

            background:
                #f1f5f9;

        }


        /* ==========================================================
           ACTIONS
        ========================================================== */

        .seller-product-actions {

            margin-top:
                14px;

            padding-top:
                13px;

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                8px;

            border-top:
                1px solid
                #edf1f5;

        }


        .seller-product-action {

            min-height:
                35px;

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

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

            transition:
                .18s ease;

        }


        .seller-product-action.edit {

            color:
                #ffffff;

            background:
                #2563eb;

        }


        .seller-product-action.delete {

            color:
                #b91c1c;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        .seller-product-action:hover {

            transform:
                translateY(-1px);

        }


        /* ==========================================================
           EMPTY STATE
        ========================================================== */

        .seller-products-empty {

            padding:
                70px 20px;

            text-align:
                center;

        }


        .seller-products-empty-icon {

            width:
                62px;

            height:
                62px;

            margin:
                0 auto 14px;

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
                24px;

        }


        .seller-products-empty h3 {

            margin:
                0 0 6px;

            color:
                #14213d;

            font-size:
                14px;

            font-weight:
                900;

        }


        .seller-products-empty p {

            margin:
                0;

            color:
                #8491a6;

            font-size:
                9px;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (
            max-width: 1200px
        ) {

            .seller-products-stats {

                grid-template-columns:

                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }


            .seller-products-grid {

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
            max-width: 900px
        ) {

            .seller-products-filter {

                grid-template-columns:
                    1fr
                    1fr;

            }


            .seller-products-filter input {

                grid-column:
                    1 / -1;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | SAME MOBILE BEHAVIOUR AS VENDOR SIDEBAR
        |--------------------------------------------------------------------------
        */

        @media (
            max-width: 768px
        ) {

            .seller-products-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-products-topbar {

                padding:
                    0 20px;

            }


            .seller-products-content {

                padding:
                    24px 20px 50px;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-products-topbar-user
            > div:last-child {

                display:
                    none;

            }


            .seller-products-content {

                padding:
                    20px 14px 45px;

            }


            .seller-products-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-products-add-btn {

                width:
                    100%;

            }


            .seller-products-stats {

                grid-template-columns:
                    1fr;

            }


            .seller-products-panel-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-products-filter {

                grid-template-columns:
                    1fr;

            }


            .seller-products-filter input {

                grid-column:
                    auto;

            }


            .seller-products-filter-btn {

                width:
                    100%;

            }


            .seller-products-grid {

                padding:
                    16px;

                grid-template-columns:
                    1fr;

            }


            .seller-product-image {

                height:
                    250px;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-products-page">


<?php

/*
|--------------------------------------------------------------------------
| EXACT SAME SHARED SIDEBAR AS DASHBOARD
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-products-main">


    <!-- ===========================================================
         TOPBAR
         MATCH SELLER DASHBOARD
    ============================================================ -->

    <header class="seller-products-topbar">


        <div>


            <span class="seller-products-topbar-label">

                Seller Center

            </span>


        </div>


        <div class="seller-products-topbar-user">


            <div class="seller-products-topbar-avatar">


                <?= sellerProductEscape(
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

                    <?= sellerProductEscape(
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

    <div class="seller-products-content">


        <!-- =======================================================
             PAGE HEADER
        ======================================================== -->

        <section class="seller-products-header">


            <div>


                <span class="seller-products-eyebrow">

                    PRODUCT MANAGEMENT

                </span>


                <h1>

                    My Products

                </h1>


                <p>

                    Manage products listed under

                    <?= sellerProductEscape(
                        $vendor[
                            'business_name'
                        ]
                    ) ?>.

                </p>


            </div>


            <?php if (
                strtolower(
                    trim(
                        (string)
                        $vendor[
                            'approval_status'
                        ]
                    )
                ) === 'approved'
            ): ?>


                <a
                    href="add_product.php"
                    class="seller-products-add-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Product

                </a>


            <?php endif; ?>


        </section>



        <!-- =======================================================
             APPROVAL WARNING
        ======================================================== -->

        <?php if (
            strtolower(
                trim(
                    (string)
                    $vendor[
                        'approval_status'
                    ]
                )
            ) !== 'approved'
        ): ?>


            <div
                class="
                    seller-products-alert
                    warning
                "
            >

                Your vendor account is currently

                <strong>

                    <?= sellerProductEscape(
                        $vendor[
                            'approval_status'
                        ]
                    ) ?>

                </strong>.

                Product creation and editing may be limited
                until your store is approved.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if (
            isset(
                $_GET['success']
            )
        ): ?>


            <div
                class="
                    seller-products-alert
                    success
                "
            >


                <?php

                switch (
                    $_GET['success']
                ) {

                    case 'product_added':

                        echo 'Product added successfully.';

                        break;


                    case 'product_updated':

                        echo 'Product updated successfully.';

                        break;


                    case 'product_deleted':

                        echo 'Product deleted successfully.';

                        break;


                    case 'product_hidden':

                        echo 'Product was hidden because it has existing order history.';

                        break;


                    default:

                        echo 'Action completed successfully.';

                        break;

                }

                ?>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (
            isset(
                $_GET['error']
            )
        ): ?>


            <div
                class="
                    seller-products-alert
                    error
                "
            >


                <?php

                switch (
                    $_GET['error']
                ) {

                    case 'product_not_found':

                        echo 'Product not found.';

                        break;


                    case 'invalid_product':

                        echo 'Invalid product.';

                        break;


                    case 'delete_failed':

                        echo 'Unable to delete product.';

                        break;


                    default:

                        echo 'Something went wrong.';

                        break;

                }

                ?>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             STATISTICS
        ======================================================== -->

        <section class="seller-products-stats">


            <!-- TOTAL -->

            <article class="seller-product-stat">


                <div class="seller-product-stat-icon">

                    <i class="fa-solid fa-boxes-stacked"></i>

                </div>


                <span>

                    TOTAL PRODUCTS

                </span>


                <strong>

                    <?= number_format(
                        $productStats[
                            'total'
                        ]
                    ) ?>

                </strong>


            </article>



            <!-- AVAILABLE -->

            <article
                class="
                    seller-product-stat
                    green
                "
            >


                <div class="seller-product-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <span>

                    AVAILABLE

                </span>


                <strong>

                    <?= number_format(
                        $productStats[
                            'available'
                        ]
                    ) ?>

                </strong>


            </article>



            <!-- OUT OF STOCK -->

            <article
                class="
                    seller-product-stat
                    orange
                "
            >


                <div class="seller-product-stat-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>


                <span>

                    OUT OF STOCK

                </span>


                <strong>

                    <?= number_format(
                        $productStats[
                            'out_of_stock'
                        ]
                    ) ?>

                </strong>


            </article>



            <!-- HIDDEN -->

            <article
                class="
                    seller-product-stat
                    purple
                "
            >


                <div class="seller-product-stat-icon">

                    <i class="fa-solid fa-eye-slash"></i>

                </div>


                <span>

                    HIDDEN

                </span>


                <strong>

                    <?= number_format(
                        $productStats[
                            'hidden'
                        ]
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =======================================================
             PRODUCTS PANEL
        ======================================================== -->

        <section class="seller-products-panel">


            <!-- ===================================================
                 PANEL HEADER
            ==================================================== -->

            <div class="seller-products-panel-header">


                <div class="seller-products-panel-title">


                    <div class="seller-products-panel-icon">

                        <i class="fa-solid fa-cube"></i>

                    </div>


                    <div>


                        <h2>

                            Product Management

                        </h2>


                        <p>

                            Search, review and manage your
                            HochipoHub store products.

                        </p>


                    </div>


                </div>


                <span class="seller-products-count">

                    <?= number_format(
                        count(
                            $products
                        )
                    ) ?>

                    product<?= count($products) !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>



            <!-- ===================================================
                 FILTER
            ==================================================== -->

            <div class="seller-products-filter-wrap">


                <form
                    action="products.php"
                    method="GET"
                    class="seller-products-filter"
                >


                    <input
                        type="search"
                        name="search"
                        value="<?= sellerProductEscape(
                            $search
                        ) ?>"
                        placeholder="Search product, description or category..."
                        autocomplete="off"
                    >


                    <select
                        name="status"
                    >


                        <option value="">

                            All Status

                        </option>


                        <?php foreach (
                            $allowedStatuses
                            as $status
                        ): ?>


                            <option
                                value="<?= sellerProductEscape(
                                    $status
                                ) ?>"
                                <?= $statusFilter === $status
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= sellerProductEscape(
                                    $status
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                    <button
                        type="submit"
                        class="
                            seller-products-filter-btn
                            primary
                        "
                    >

                        <i class="fa-solid fa-magnifying-glass"></i>

                        Search

                    </button>


                    <a
                        href="products.php"
                        class="
                            seller-products-filter-btn
                            secondary
                        "
                    >

                        Reset

                    </a>


                </form>


            </div>



            <!-- ===================================================
                 PRODUCTS
            ==================================================== -->

            <?php if (
                empty(
                    $products
                )
            ): ?>


                <div class="seller-products-empty">


                    <div class="seller-products-empty-icon">

                        <i class="fa-solid fa-box-open"></i>

                    </div>


                    <h3>

                        No products found

                    </h3>


                    <p>

                        Try another search/filter or add
                        your first product.

                    </p>


                </div>


            <?php else: ?>


                <div class="seller-products-grid">


                    <?php foreach (
                        $products
                        as $product
                    ): ?>


                        <?php

                        $productId =
                            (int)
                            $product[
                                'product_id'
                            ];


                        $imageName =
                            trim(
                                (string)
                                (
                                    $product[
                                        'image'
                                    ]
                                    ?? ''
                                )
                            );


                        $description =
                            trim(
                                (string)
                                (
                                    $product[
                                        'description'
                                    ]
                                    ?? ''
                                )
                            );


                        $inventoryQuantity =
                            (int)
                            (
                                $product[
                                    'inventory_quantity'
                                ]
                                ?? 0
                            );


                        $productStatus =
                            (string)
                            (
                                $product[
                                    'status'
                                ]
                                ?? ''
                            );


                        $statusClass =
                            sellerProductStatusClass(
                                $productStatus
                            );

                        ?>


                        <article class="seller-product-card">


                            <!-- ===============================
                                 IMAGE
                            ================================ -->

                            <div class="seller-product-image">


                                <?php if (
                                    $imageName !== ''
                                ): ?>


                                    <img
                                        src="../uploads/products/<?= sellerProductEscape(
                                            rawurlencode(
                                                basename(
                                                    $imageName
                                                )
                                            )
                                        ) ?>"
                                        alt="<?= sellerProductEscape(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>"
                                        loading="lazy"
                                        onerror="
                                            this.style.display='none';
                                            this.parentElement.innerHTML='<div class=&quot;seller-product-image-empty&quot;><i class=&quot;fa-solid fa-image&quot;></i></div>';
                                        "
                                    >


                                <?php else: ?>


                                    <div class="seller-product-image-empty">

                                        <i class="fa-solid fa-image"></i>

                                    </div>


                                <?php endif; ?>


                            </div>



                            <!-- ===============================
                                 BODY
                            ================================ -->

                            <div class="seller-product-body">


                                <span class="seller-product-category">

                                    <?= sellerProductEscape(
                                        $product[
                                            'category_name'
                                        ]
                                    ) ?>

                                </span>


                                <h3>

                                    <?= sellerProductEscape(
                                        $product[
                                            'product_name'
                                        ]
                                    ) ?>

                                </h3>


                                <p class="seller-product-description">


                                    <?= $description !== ''
                                        ? sellerProductEscape(
                                            mb_strimwidth(
                                                $description,
                                                0,
                                                115,
                                                '...'
                                            )
                                        )
                                        : 'No description provided.' ?>


                                </p>



                                <!-- ===========================
                                     META
                                ============================ -->

                                <div class="seller-product-meta">


                                    <div class="seller-product-meta-item">


                                        <span>

                                            PRICE

                                        </span>


                                        <strong>

                                            RM
                                            <?= number_format(
                                                (float)
                                                $product[
                                                    'price'
                                                ],
                                                2
                                            ) ?>

                                        </strong>


                                    </div>


                                    <div class="seller-product-meta-item">


                                        <span>

                                            STOCK

                                        </span>


                                        <strong>

                                            <?= number_format(
                                                $inventoryQuantity
                                            ) ?>

                                        </strong>


                                    </div>


                                </div>



                                <!-- ===========================
                                     STATUS
                                ============================ -->

                                <span
                                    class="
                                        seller-product-status
                                        <?= sellerProductEscape(
                                            $statusClass
                                        ) ?>
                                    "
                                >

                                    <?= sellerProductEscape(
                                        $productStatus
                                    ) ?>

                                </span>



                                <!-- ===========================
                                     ACTION
                                ============================ -->

                                <div class="seller-product-actions">


                                    <a
                                        href="edit_product.php?id=<?= $productId ?>"
                                        class="
                                            seller-product-action
                                            edit
                                        "
                                    >

                                        <i class="fa-solid fa-pen"></i>

                                        Edit

                                    </a>


                                    <a
                                        href="delete_product.php?id=<?= $productId ?>"
                                        class="
                                            seller-product-action
                                            delete
                                        "
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this product?'
                                            );
                                        "
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                        Delete

                                    </a>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>


    </div>


</main>


</body>


</html>