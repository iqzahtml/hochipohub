<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER INVENTORY
|--------------------------------------------------------------------------
| File:
| inventory.php
|--------------------------------------------------------------------------
|
| Note:
| This file is located in the project root, but this page belongs to the
| seller/vendor workspace.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/functions.php';


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
        'Location: index.php'
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

if (!function_exists('inventoryEscape')) {

    function inventoryEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('inventoryDate')) {

    function inventoryDate($value): string
    {
        if (empty($value)) {
            return '-';
        }


        $timestamp =
            strtotime(
                (string) $value
            );


        if (!$timestamp) {
            return '-';
        }


        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }

}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                user_id,
                name,
                email,
                phone,
                role,
                status

            FROM users

            WHERE user_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $userId
    ]);


    $user =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $user = false;

}


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    $_SESSION['error'] =
        'User account could not be found.';


    header(
        'Location: index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| VENDOR ROLE ONLY
|--------------------------------------------------------------------------
*/

if (
    strtolower(
        trim(
            (string) $user['role']
        )
    ) !== 'vendor'
) {

    $_SESSION['error'] =
        'Vendor access required.';


    header(
        'Location: dashboard.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
|
| Full vendor data is loaded so shared vendor_sidebar.php looks exactly
| like Seller Dashboard / Products / Orders / Sales.
|
|--------------------------------------------------------------------------
*/

try {

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

}

catch (Throwable $e) {

    $vendor = false;

}


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    $_SESSION['error'] =
        'Vendor profile not found.';


    header(
        'Location: dashboard.php'
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
| SIDEBAR SESSION DATA
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| UPDATE INVENTORY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $csrf =
        $_POST['csrf_token']
        ?? '';


    if (!verifyCsrfToken($csrf)) {

        $_SESSION['error'] =
            'Invalid security token. Please try again.';


        header(
            'Location: inventory.php'
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | INPUT
    |--------------------------------------------------------------------------
    */

    $productId =
        isset($_POST['product_id'])
            ? (int) $_POST['product_id']
            : 0;


    $quantity =
        isset($_POST['quantity'])
            ? (int) $_POST['quantity']
            : -1;


    /*
    |--------------------------------------------------------------------------
    | VALIDATE
    |--------------------------------------------------------------------------
    */

    if (
        $productId <= 0 ||
        $quantity < 0
    ) {

        $_SESSION['error'] =
            'Please enter a valid stock quantity.';


        header(
            'Location: inventory.php'
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY PRODUCT OWNERSHIP
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $db->prepare("
                SELECT

                    product_id,
                    product_name

                FROM products

                WHERE product_id = ?

                AND vendor_id = ?

                LIMIT 1
            ");


        $stmt->execute([

            $productId,

            $vendorId

        ]);


        $product =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

    }

    catch (Throwable $e) {

        $product = false;

    }


    if (!$product) {

        $_SESSION['error'] =
            'You cannot update this product.';


        header(
            'Location: inventory.php'
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    try {

        $db->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS TABLE
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                UPDATE products

                SET
                    stock_quantity = ?,

                    status =
                        CASE

                            WHEN ? <= 0
                                THEN 'Out of Stock'

                            ELSE 'Available'

                        END

                WHERE product_id = ?

                AND vendor_id = ?
            ");


        $stmt->execute([

            $quantity,

            $quantity,

            $productId,

            $vendorId

        ]);


        /*
        |--------------------------------------------------------------------------
        | CHECK INVENTORY RECORD
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    inventory_id

                FROM inventory

                WHERE product_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $productId
        ]);


        $inventoryRecord =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | UPDATE INVENTORY
        |--------------------------------------------------------------------------
        */

        if ($inventoryRecord) {

            $stmt =
                $db->prepare("
                    UPDATE inventory

                    SET
                        quantity = ?,
                        last_updated = CURRENT_TIMESTAMP

                    WHERE product_id = ?
                ");


            $stmt->execute([

                $quantity,

                $productId

            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | CREATE INVENTORY
        |--------------------------------------------------------------------------
        */

        else {

            $stmt =
                $db->prepare("
                    INSERT INTO inventory
                    (
                        product_id,
                        quantity
                    )

                    VALUES
                    (
                        ?,
                        ?
                    )
                ");


            $stmt->execute([

                $productId,

                $quantity

            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        $db->commit();


        $_SESSION['success'] =
            'Inventory for "' .
            $product['product_name'] .
            '" has been updated successfully.';

    }

    catch (Throwable $e) {

        if ($db->inTransaction()) {

            $db->rollBack();

        }


        $_SESSION['error'] =
            'Unable to update inventory. Please try again.';

    }


    header(
        'Location: inventory.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| GET INVENTORY
|--------------------------------------------------------------------------
*/

$inventory = [];


try {

    $stmt =
        $db->prepare("
            SELECT

                p.product_id,
                p.product_name,
                p.price,
                p.stock_quantity,
                p.image,
                p.status,
                p.updated_at,

                c.category_name,

                i.inventory_id,
                i.quantity AS inventory_quantity,
                i.last_updated

            FROM products p

            INNER JOIN categories c
                ON p.category_id = c.category_id

            LEFT JOIN inventory i
                ON p.product_id = i.product_id

            WHERE p.vendor_id = ?

            ORDER BY
                p.updated_at DESC,
                p.product_id DESC
        ");


    $stmt->execute([
        $vendorId
    ]);


    $inventory =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $inventory = [];

}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalProducts = 0;

$totalStock = 0;

$lowStock = 0;

$outOfStock = 0;


/*
|--------------------------------------------------------------------------
| INVENTORY LOOP
|--------------------------------------------------------------------------
*/

foreach ($inventory as &$item) {

    $quantity =
        $item['inventory_quantity'] !== null
            ? (int) $item['inventory_quantity']
            : (int) $item['stock_quantity'];


    $item['_quantity'] =
        $quantity;


    $totalStock +=
        $quantity;


    if ($quantity <= 0) {

        $outOfStock++;

    }

    elseif ($quantity <= 5) {

        $lowStock++;

    }

}


unset($item);


$totalProducts =
    count(
        $inventory
    );


/*
|--------------------------------------------------------------------------
| HEALTHY STOCK
|--------------------------------------------------------------------------
*/

$healthyStock =
    max(
        0,
        $totalProducts -
        $lowStock -
        $outOfStock
    );


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION['success']
    ?? '';


$errorMessage =
    $_SESSION['error']
    ?? '';


unset(
    $_SESSION['success'],
    $_SESSION['error']
);


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Inventory | Seller | HochipoHub';

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
        <?= inventoryEscape(
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
        href="css/style.css"
    >


    <link
        rel="stylesheet"
        href="css/vendor.css"
    >


    <link
        rel="stylesheet"
        href="css/responsive.css"
    >


    <style>


        /* ==========================================================
           PAGE
        ========================================================== */

        .seller-inventory-page {

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

        .seller-inventory-main {

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

        .seller-inventory-topbar {

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


        .seller-inventory-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-inventory-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-inventory-avatar {

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


        .seller-inventory-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-inventory-user small {

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

        .seller-inventory-content {

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

        .seller-inventory-heading {

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


        .seller-inventory-eyebrow {

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


        .seller-inventory-heading h1 {

            margin: 0;

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


        .seller-inventory-heading p {

            margin:
                7px 0 0;

            color:
                #7b879c;

            font-size:
                11px;

        }


        .seller-inventory-add {

            min-height:
                42px;

            padding:
                0 16px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                7px;

            color:
                #ffffff;

            background:
                #2563eb;

            border-radius:
                10px;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

            font-size:
                9px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        /* ==========================================================
           HERO
        ========================================================== */

        .seller-inventory-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                170px;

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


        .seller-inventory-hero::before {

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


        .seller-inventory-hero::after {

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


        .seller-inventory-hero-copy {

            position:
                relative;

            z-index:
                2;

        }


        .seller-inventory-hero-label {

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


        .seller-inventory-hero h2 {

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


        .seller-inventory-hero p {

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


        .seller-inventory-hero-icon {

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
           ALERT
        ========================================================== */

        .seller-inventory-alert {

            margin-bottom:
                20px;

            padding:
                14px 16px;

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            border-radius:
                12px;

            font-size:
                9px;

            font-weight:
                700;

        }


        .seller-inventory-alert.success {

            color:
                #166534;

            background:
                #f0fdf4;

            border:
                1px solid
                #bbf7d0;

        }


        .seller-inventory-alert.warning {

            color:
                #92400e;

            background:
                #fffbeb;

            border:
                1px solid
                #fde68a;

        }


        .seller-inventory-alert.error {

            color:
                #b91c1c;

            background:
                #fef2f2;

            border:
                1px solid
                #fecaca;

        }


        /* ==========================================================
           STATS
        ========================================================== */

        .seller-inventory-stats {

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


        .seller-inventory-stat {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                135px;

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


        .seller-inventory-stat::after {

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


        .seller-inventory-stat.green::after {

            background:
                #ecfdf3;

        }


        .seller-inventory-stat.orange::after {

            background:
                #fff7ed;

        }


        .seller-inventory-stat.red::after {

            background:
                #fef2f2;

        }


        .seller-inventory-stat-icon {

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


        .seller-inventory-stat.green
        .seller-inventory-stat-icon {

            color:
                #16a34a;

            background:
                #ecfdf3;

        }


        .seller-inventory-stat.orange
        .seller-inventory-stat-icon {

            color:
                #ea580c;

            background:
                #fff7ed;

        }


        .seller-inventory-stat.red
        .seller-inventory-stat-icon {

            color:
                #dc2626;

            background:
                #fef2f2;

        }


        .seller-inventory-stat span {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                4px;

            color:
                #7d899d;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .7px;

        }


        .seller-inventory-stat strong {

            position:
                relative;

            z-index:
                2;

            color:
                #14213d;

            font-size:
                25px;

            font-weight:
                900;

        }


        /* ==========================================================
           INVENTORY PANEL
        ========================================================== */

        .seller-inventory-panel {

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
                11px
                30px
                rgba(
                    40,
                    65,
                    120,
                    .055
                );

        }


        .seller-inventory-panel-header {

            min-height:
                88px;

            padding:
                20px 22px;

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


        .seller-inventory-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

        }


        .seller-inventory-panel-icon {

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


        .seller-inventory-panel-title h2 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                16px;

            font-weight:
                900;

        }


        .seller-inventory-panel-title p {

            margin:
                0;

            color:
                #8b99ad;

            font-size:
                8px;

        }


        .seller-inventory-count {

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

        }


        /* ==========================================================
           TABLE
        ========================================================== */

        .seller-inventory-table-wrap {

            width:
                100%;

            overflow-x:
                auto;

        }


        .seller-inventory-table {

            width:
                100%;

            min-width:
                970px;

            border-collapse:
                collapse;

        }


        .seller-inventory-table thead {

            background:
                #f8fafc;

        }


        .seller-inventory-table th {

            height:
                43px;

            padding:
                0 17px;

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
                .55px;

            text-align:
                left;

            text-transform:
                uppercase;

        }


        .seller-inventory-table td {

            padding:
                14px 17px;

            color:
                #52647d;

            border-bottom:
                1px solid
                #edf1f5;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .seller-inventory-table tbody tr:hover {

            background:
                #fbfdff;

        }


        .seller-inventory-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /* ==========================================================
           PRODUCT
        ========================================================== */

        .seller-inventory-product {

            display:
                flex;

            align-items:
                center;

            gap:
                11px;

        }


        .seller-inventory-image {

            width:
                55px;

            height:
                55px;

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


        .seller-inventory-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                contain;

            object-position:
                center;

        }


        .seller-inventory-product-info {

            min-width:
                0;

        }


        .seller-inventory-product-info strong {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #14213d;

            font-size:
                9px;

            font-weight:
                900;

        }


        .seller-inventory-product-info small {

            color:
                #94a3b8;

            font-size:
                7px;

        }


        /* ==========================================================
           CATEGORY
        ========================================================== */

        .seller-inventory-category {

            min-height:
                27px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

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


        /* ==========================================================
           PRICE
        ========================================================== */

        .seller-inventory-price {

            color:
                #12366a;

            font-size:
                10px;

            font-weight:
                900;

        }


        /* ==========================================================
           STOCK NUMBER
        ========================================================== */

        .seller-inventory-stock {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

        }


        .seller-inventory-stock strong {

            color:
                #14213d;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-inventory-stock span {

            color:
                #94a3b8;

            font-size:
                7px;

        }


        /* ==========================================================
           STATUS
        ========================================================== */

        .seller-inventory-status {

            min-height:
                28px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            border-radius:
                999px;

            font-size:
                7px;

            font-weight:
                900;

        }


        .seller-inventory-status::before {

            content:
                "";

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .seller-inventory-status.good {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .seller-inventory-status.low {

            color:
                #b45309;

            background:
                #fffbeb;

        }


        .seller-inventory-status.empty {

            color:
                #b91c1c;

            background:
                #fef2f2;

        }


        /* ==========================================================
           UPDATE FORM
        ========================================================== */

        .seller-inventory-update {

            display:
                grid;

            grid-template-columns:
                85px
                auto;

            align-items:
                center;

            gap:
                7px;

        }


        .seller-inventory-update input[type="number"] {

            width:
                85px;

            height:
                37px;

            padding:
                0 9px;

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
                9px;

        }


        .seller-inventory-update input[type="number"]:focus {

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


        .seller-inventory-update button {

            min-height:
                37px;

            padding:
                0 11px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                5px;

            color:
                #ffffff;

            background:
                #2563eb;

            border:
                0;

            border-radius:
                9px;

            font-family:
                inherit;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        /* ==========================================================
           EMPTY
        ========================================================== */

        .seller-inventory-empty {

            padding:
                68px 20px;

            text-align:
                center;

        }


        .seller-inventory-empty-icon {

            width:
                62px;

            height:
                62px;

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
                24px;

        }


        .seller-inventory-empty h3 {

            margin:
                0 0 6px;

            color:
                #14213d;

            font-size:
                14px;

            font-weight:
                900;

        }


        .seller-inventory-empty p {

            margin:
                0 0 16px;

            color:
                #8492a6;

            font-size:
                9px;

        }


        .seller-inventory-empty a {

            min-height:
                39px;

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

            color:
                #ffffff;

            background:
                #2563eb;

            border-radius:
                9px;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        /* ==========================================================
           STOCK LEGEND
        ========================================================== */

        .seller-inventory-legend {

            padding:
                14px 20px;

            display:
                flex;

            align-items:
                center;

            flex-wrap:
                wrap;

            gap:
                15px;

            background:
                #fbfcfe;

            border-bottom:
                1px solid
                #edf1f5;

        }


        .seller-inventory-legend-label {

            color:
                #64748b;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .5px;

        }


        .seller-inventory-legend-item {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            color:
                #75859b;

            font-size:
                7px;

            font-weight:
                700;

        }


        .seller-inventory-legend-dot {

            width:
                7px;

            height:
                7px;

            border-radius:
                50%;

        }


        .seller-inventory-legend-dot.green {

            background:
                #22c55e;

        }


        .seller-inventory-legend-dot.orange {

            background:
                #f59e0b;

        }


        .seller-inventory-legend-dot.red {

            background:
                #ef4444;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (
            max-width: 1150px
        ) {

            .seller-inventory-stats {

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
            max-width: 768px
        ) {

            .seller-inventory-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-inventory-topbar {

                padding:
                    0 20px;

            }


            .seller-inventory-content {

                padding:
                    24px 20px 50px;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-inventory-user
            > div:last-child {

                display:
                    none;

            }


            .seller-inventory-content {

                padding:
                    20px 14px 45px;

            }


            .seller-inventory-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-inventory-add {

                width:
                    100%;

            }


            .seller-inventory-hero {

                min-height:
                    auto;

                padding:
                    23px;

                align-items:
                    flex-start;

            }


            .seller-inventory-hero h2 {

                font-size:
                    20px;

            }


            .seller-inventory-hero-icon {

                width:
                    53px;

                height:
                    53px;

                font-size:
                    19px;

            }


            .seller-inventory-stats {

                grid-template-columns:
                    1fr;

            }


            .seller-inventory-panel-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-inventory-page">


<?php

/*
|--------------------------------------------------------------------------
| SELLER SIDEBAR
|--------------------------------------------------------------------------
|
| inventory.php is in project root, therefore the include path begins here.
|
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-inventory-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-inventory-topbar">


        <span class="seller-inventory-topbar-label">

            Seller Center

        </span>


        <div class="seller-inventory-user">


            <div class="seller-inventory-avatar">

                <?= inventoryEscape(
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

                    <?= inventoryEscape(
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

    <div class="seller-inventory-content">


        <!-- =======================================================
             PAGE HEADING
        ======================================================== -->

        <section class="seller-inventory-heading">


            <div>


                <span class="seller-inventory-eyebrow">

                    STOCK MANAGEMENT

                </span>


                <h1>

                    Inventory

                </h1>


                <p>

                    Manage stock levels for products under

                    <?= inventoryEscape(
                        $vendor['business_name']
                    ) ?>.

                </p>


            </div>


            <a
                href="<?= inventoryEscape(
                    BASE_URL
                ) ?>seller/add_product.php"
                class="seller-inventory-add"
            >

                <i class="fa-solid fa-plus"></i>

                Add Product

            </a>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-inventory-hero">


            <div class="seller-inventory-hero-copy">


                <span class="seller-inventory-hero-label">

                    SELLER WORKSPACE

                </span>


                <h2>

                    Keep your stock accurate.

                </h2>


                <p>

                    Update available quantities, identify low-stock
                    products and keep customers from ordering items
                    that are no longer available.

                </p>


            </div>


            <div class="seller-inventory-hero-icon">

                <i class="fa-solid fa-boxes-stacked"></i>

            </div>


        </section>



        <!-- =======================================================
             APPROVAL WARNING
        ======================================================== -->

        <?php if (
            strtolower(
                trim(
                    (string)
                    $vendor['approval_status']
                )
            ) !== 'approved'
        ): ?>


            <div
                class="
                    seller-inventory-alert
                    warning
                "
            >

                <i class="fa-solid fa-triangle-exclamation"></i>


                Your vendor account is currently

                <strong>

                    <?= inventoryEscape(
                        $vendor['approval_status']
                    ) ?>

                </strong>.

                Some vendor features may remain unavailable until
                your account is approved.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if (
            $successMessage !== ''
        ): ?>


            <div
                class="
                    seller-inventory-alert
                    success
                "
            >

                <i class="fa-solid fa-circle-check"></i>

                <?= inventoryEscape(
                    $successMessage
                ) ?>

            </div>


        <?php endif; ?>



        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (
            $errorMessage !== ''
        ): ?>


            <div
                class="
                    seller-inventory-alert
                    error
                "
            >

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= inventoryEscape(
                    $errorMessage
                ) ?>

            </div>


        <?php endif; ?>



        <!-- =======================================================
             STATISTICS
        ======================================================== -->

        <section class="seller-inventory-stats">


            <!-- TOTAL PRODUCTS -->

            <article class="seller-inventory-stat">


                <div class="seller-inventory-stat-icon">

                    <i class="fa-solid fa-cubes"></i>

                </div>


                <span>

                    TOTAL PRODUCTS

                </span>


                <strong>

                    <?= number_format(
                        $totalProducts
                    ) ?>

                </strong>


            </article>



            <!-- TOTAL STOCK -->

            <article
                class="
                    seller-inventory-stat
                    green
                "
            >


                <div class="seller-inventory-stat-icon">

                    <i class="fa-solid fa-boxes-stacked"></i>

                </div>


                <span>

                    TOTAL STOCK

                </span>


                <strong>

                    <?= number_format(
                        $totalStock
                    ) ?>

                </strong>


            </article>



            <!-- LOW STOCK -->

            <article
                class="
                    seller-inventory-stat
                    orange
                "
            >


                <div class="seller-inventory-stat-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>


                <span>

                    LOW STOCK

                </span>


                <strong>

                    <?= number_format(
                        $lowStock
                    ) ?>

                </strong>


            </article>



            <!-- OUT OF STOCK -->

            <article
                class="
                    seller-inventory-stat
                    red
                "
            >


                <div class="seller-inventory-stat-icon">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>


                <span>

                    OUT OF STOCK

                </span>


                <strong>

                    <?= number_format(
                        $outOfStock
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =======================================================
             INVENTORY PANEL
        ======================================================== -->

        <section class="seller-inventory-panel">


            <!-- ===================================================
                 HEADER
            ==================================================== -->

            <div class="seller-inventory-panel-header">


                <div class="seller-inventory-panel-title">


                    <div class="seller-inventory-panel-icon">

                        <i class="fa-solid fa-warehouse"></i>

                    </div>


                    <div>


                        <h2>

                            Product Inventory

                        </h2>


                        <p>

                            Review stock condition and update
                            quantities instantly.

                        </p>


                    </div>


                </div>


                <span class="seller-inventory-count">

                    <?= number_format(
                        $healthyStock
                    ) ?>

                    healthy stock

                </span>


            </div>



            <!-- ===================================================
                 LEGEND
            ==================================================== -->

            <div class="seller-inventory-legend">


                <span class="seller-inventory-legend-label">

                    STOCK STATUS

                </span>


                <span class="seller-inventory-legend-item">


                    <span
                        class="
                            seller-inventory-legend-dot
                            green
                        "
                    ></span>

                    More than 5 units

                </span>


                <span class="seller-inventory-legend-item">


                    <span
                        class="
                            seller-inventory-legend-dot
                            orange
                        "
                    ></span>

                    1 - 5 units

                </span>


                <span class="seller-inventory-legend-item">


                    <span
                        class="
                            seller-inventory-legend-dot
                            red
                        "
                    ></span>

                    No stock

                </span>


            </div>



            <!-- ===================================================
                 EMPTY
            ==================================================== -->

            <?php if (
                empty(
                    $inventory
                )
            ): ?>


                <div class="seller-inventory-empty">


                    <div class="seller-inventory-empty-icon">

                        <i class="fa-solid fa-box-open"></i>

                    </div>


                    <h3>

                        No products yet

                    </h3>


                    <p>

                        Add your first product to start
                        managing inventory.

                    </p>


                    <a
                        href="<?= inventoryEscape(
                            BASE_URL
                        ) ?>seller/add_product.php"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Product

                    </a>


                </div>


            <?php else: ?>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="seller-inventory-table-wrap">


                    <table class="seller-inventory-table">


                        <thead>


                            <tr>


                                <th>

                                    Product

                                </th>


                                <th>

                                    Category

                                </th>


                                <th>

                                    Price

                                </th>


                                <th>

                                    Current Stock

                                </th>


                                <th>

                                    Status

                                </th>


                                <th>

                                    Last Updated

                                </th>


                                <th>

                                    Update Stock

                                </th>


                            </tr>


                        </thead>


                        <tbody>


                            <?php foreach (
                                $inventory
                                as $item
                            ): ?>


                                <?php

                                $quantity =
                                    (int)
                                    $item['_quantity'];


                                $image =
                                    trim(
                                        (string)
                                        (
                                            $item['image']
                                            ?? ''
                                        )
                                    );


                                $lastUpdated =
                                    !empty(
                                        $item['last_updated']
                                    )
                                        ? $item['last_updated']
                                        : $item['updated_at'];

                                ?>


                                <tr>


                                    <!-- ===========================
                                         PRODUCT
                                    ============================ -->

                                    <td>


                                        <div class="seller-inventory-product">


                                            <div class="seller-inventory-image">


                                                <?php if (
                                                    $image !== ''
                                                ): ?>


                                                    <img
                                                        src="<?= inventoryEscape(
                                                            BASE_URL
                                                        ) ?>uploads/products/<?= inventoryEscape(
                                                            rawurlencode(
                                                                basename(
                                                                    $image
                                                                )
                                                            )
                                                        ) ?>"
                                                        alt="<?= inventoryEscape(
                                                            $item[
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


                                            <div class="seller-inventory-product-info">


                                                <strong>

                                                    <?= inventoryEscape(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <small>

                                                    Product
                                                    #<?= (int)
                                                        $item[
                                                            'product_id'
                                                        ] ?>

                                                </small>


                                            </div>


                                        </div>


                                    </td>



                                    <!-- ===========================
                                         CATEGORY
                                    ============================ -->

                                    <td>


                                        <span class="seller-inventory-category">

                                            <?= inventoryEscape(
                                                $item[
                                                    'category_name'
                                                ]
                                            ) ?>

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         PRICE
                                    ============================ -->

                                    <td>


                                        <span class="seller-inventory-price">

                                            RM
                                            <?= number_format(
                                                (float)
                                                $item['price'],
                                                2
                                            ) ?>

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         CURRENT STOCK
                                    ============================ -->

                                    <td>


                                        <div class="seller-inventory-stock">


                                            <strong>

                                                <?= number_format(
                                                    $quantity
                                                ) ?>

                                            </strong>


                                            <span>

                                                unit<?= $quantity !== 1
                                                    ? 's'
                                                    : '' ?>

                                            </span>


                                        </div>


                                    </td>



                                    <!-- ===========================
                                         STATUS
                                    ============================ -->

                                    <td>


                                        <?php if (
                                            $quantity <= 0
                                        ): ?>


                                            <span
                                                class="
                                                    seller-inventory-status
                                                    empty
                                                "
                                            >

                                                Out of Stock

                                            </span>


                                        <?php elseif (
                                            $quantity <= 5
                                        ): ?>


                                            <span
                                                class="
                                                    seller-inventory-status
                                                    low
                                                "
                                            >

                                                Low Stock

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    seller-inventory-status
                                                    good
                                                "
                                            >

                                                In Stock

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- ===========================
                                         UPDATED
                                    ============================ -->

                                    <td>

                                        <?= inventoryEscape(
                                            inventoryDate(
                                                $lastUpdated
                                            )
                                        ) ?>

                                    </td>



                                    <!-- ===========================
                                         UPDATE
                                    ============================ -->

                                    <td>


                                        <form
                                            method="POST"
                                            action="<?= inventoryEscape(
                                                BASE_URL
                                            ) ?>inventory.php"
                                            class="seller-inventory-update"
                                        >


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= inventoryEscape(
                                                    csrfToken()
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)
                                                    $item[
                                                        'product_id'
                                                    ] ?>"
                                            >


                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?= $quantity ?>"
                                                min="0"
                                                step="1"
                                                aria-label="Stock quantity for <?= inventoryEscape(
                                                    $item[
                                                        'product_name'
                                                    ]
                                                ) ?>"
                                                required
                                            >


                                            <button
                                                type="submit"
                                            >

                                                <i class="fa-solid fa-check"></i>

                                                Update

                                            </button>


                                        </form>


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