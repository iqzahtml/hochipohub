<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN INVENTORY
|--------------------------------------------------------------------------
| File: admin/inventory.php
|--------------------------------------------------------------------------
*/

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
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ../index.php');
    exit;
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {

    header('Location: ../dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


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


if (!function_exists('inventoryStockClass')) {

    function inventoryStockClass(int $stock): string
    {
        if ($stock <= 0) {
            return 'stock-out';
        }

        if ($stock <= 10) {
            return 'stock-low';
        }

        return 'stock-good';
    }
}


if (!function_exists('inventoryStockLabel')) {

    function inventoryStockLabel(int $stock): string
    {
        if ($stock <= 0) {
            return 'Out of Stock';
        }

        if ($stock <= 10) {
            return 'Low Stock';
        }

        return 'In Stock';
    }
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';

$search =
    isset($_GET['search'])
        ? trim($_GET['search'])
        : '';

$vendorFilter =
    isset($_GET['vendor_id'])
        ? (int) $_GET['vendor_id']
        : 0;

$stockFilter =
    isset($_GET['stock_status'])
        ? trim($_GET['stock_status'])
        : '';


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| UPDATE STOCK
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        $_POST['action']
        ?? '';

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $message =
            'Invalid security token. Please refresh and try again.';

        $messageType =
            'error';
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE STOCK
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'update_stock') {

        $productId =
            isset($_POST['product_id'])
                ? (int) $_POST['product_id']
                : 0;

        $newStock =
            isset($_POST['stock_quantity'])
                ? (int) $_POST['stock_quantity']
                : -1;


        /*
        |--------------------------------------------------------------------------
        | VALIDATE
        |--------------------------------------------------------------------------
        */

        if ($productId <= 0) {

            $message =
                'Invalid product.';

            $messageType =
                'error';
        }

        elseif ($newStock < 0) {

            $message =
                'Stock quantity cannot be negative.';

            $messageType =
                'error';
        }

        else {

            try {

                /*
                |--------------------------------------------------------------------------
                | CHECK PRODUCT
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        SELECT
                            product_id,
                            product_name,
                            status

                        FROM products

                        WHERE product_id = ?

                        LIMIT 1
                    ");


                $stmt->execute([
                    $productId
                ]);


                $product =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (!$product) {

                    $message =
                        'Product not found.';

                    $messageType =
                        'error';
                }

                else {

                    $db->beginTransaction();


                    /*
                    |--------------------------------------------------------------------------
                    | DETERMINE STATUS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $product['status'] === 'Hidden'
                    ) {

                        $newStatus =
                            'Hidden';
                    }

                    elseif ($newStock <= 0) {

                        $newStatus =
                            'Out of Stock';
                    }

                    else {

                        $newStatus =
                            'Available';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE PRODUCTS
                    |--------------------------------------------------------------------------
                    */

                    $stmt =
                        $db->prepare("
                            UPDATE products

                            SET
                                stock_quantity = ?,
                                status = ?

                            WHERE product_id = ?
                        ");


                    $stmt->execute([
                        $newStock,
                        $newStatus,
                        $productId
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK INVENTORY ROW
                    |--------------------------------------------------------------------------
                    */

                    $stmt =
                        $db->prepare("
                            SELECT inventory_id

                            FROM inventory

                            WHERE product_id = ?

                            LIMIT 1
                        ");


                    $stmt->execute([
                        $productId
                    ]);


                    $inventoryRow =
                        $stmt->fetch(
                            PDO::FETCH_ASSOC
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE INVENTORY
                    |--------------------------------------------------------------------------
                    */

                    if ($inventoryRow) {

                        $stmt =
                            $db->prepare("
                                UPDATE inventory

                                SET quantity = ?

                                WHERE product_id = ?
                            ");


                        $stmt->execute([
                            $newStock,
                            $productId
                        ]);
                    }

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
                            $newStock
                        ]);
                    }


                    $db->commit();


                    $_SESSION['inventory_success'] =
                        'Stock for "' .
                        $product['product_name'] .
                        '" updated successfully.';


                    header(
                        'Location: inventory.php'
                    );

                    exit;
                }

            }

            catch (Throwable $e) {

                if (
                    isset($db) &&
                    $db instanceof PDO &&
                    $db->inTransaction()
                ) {

                    $db->rollBack();
                }


                $message =
                    'Stock update failed: ' .
                    $e->getMessage();

                $messageType =
                    'error';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (
    isset(
        $_SESSION[
            'inventory_success'
        ]
    )
) {

    $message =
        $_SESSION[
            'inventory_success'
        ];

    $messageType =
        'success';

    unset(
        $_SESSION[
            'inventory_success'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

$stats = [

    'products' => 0,
    'total_stock' => 0,
    'low_stock' => 0,
    'out_stock' => 0

];


try {

    /*
    |--------------------------------------------------------------------------
    | TOTAL PRODUCTS
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->query("
            SELECT COUNT(*)
            FROM products
        ");


    $stats['products'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL STOCK
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->query("
            SELECT
                COALESCE(
                    SUM(stock_quantity),
                    0
                )

            FROM products

            WHERE status != 'Hidden'
        ");


    $stats['total_stock'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | LOW STOCK
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->query("
            SELECT COUNT(*)

            FROM products

            WHERE stock_quantity BETWEEN 1 AND 10

            AND status != 'Hidden'
        ");


    $stats['low_stock'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | OUT OF STOCK
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->query("
            SELECT COUNT(*)

            FROM products

            WHERE stock_quantity <= 0

            AND status != 'Hidden'
        ");


    $stats['out_stock'] =
        (int) $stmt->fetchColumn();

}

catch (Throwable $e) {

    $message =
        'Failed to load inventory statistics: ' .
        $e->getMessage();

    $messageType =
        'error';
}


/*
|--------------------------------------------------------------------------
| VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];


try {

    $stmt =
        $db->query("
            SELECT
                vendor_id,
                business_name

            FROM vendors

            ORDER BY business_name ASC
        ");


    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $vendors = [];
}


/*
|--------------------------------------------------------------------------
| PRODUCTS QUERY
|--------------------------------------------------------------------------
*/

$products = [];


try {

    $sql = "
        SELECT

            p.product_id,
            p.product_name,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            p.created_at,

            v.vendor_id,
            v.business_name,

            c.category_name

        FROM products p

        LEFT JOIN vendors v
            ON p.vendor_id = v.vendor_id

        LEFT JOIN categories c
            ON p.category_id = c.category_id

        WHERE 1 = 1
    ";


    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            AND
            (
                p.product_name LIKE ?
                OR v.business_name LIKE ?
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
    | VENDOR FILTER
    |--------------------------------------------------------------------------
    */

    if ($vendorFilter > 0) {

        $sql .= "
            AND p.vendor_id = ?
        ";


        $params[] =
            $vendorFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | STOCK FILTER
    |--------------------------------------------------------------------------
    */

    if ($stockFilter === 'available') {

        $sql .= "
            AND p.stock_quantity > 10
        ";
    }

    elseif ($stockFilter === 'low') {

        $sql .= "
            AND p.stock_quantity BETWEEN 1 AND 10
        ";
    }

    elseif ($stockFilter === 'out') {

        $sql .= "
            AND p.stock_quantity <= 0
        ";
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


    $message =
        'Failed to load products: ' .
        $e->getMessage();


    $messageType =
        'error';
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
        Inventory | HochipoHub Admin
    </title>


    <!-- ============================================================
         POPPINS
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | ROOT
        |--------------------------------------------------------------------------
        */

        :root {

            --inventory-sidebar-width:
                260px;

            --inventory-blue:
                #2563eb;

            --inventory-navy:
                #08265a;

            --inventory-bg:
                #eef5fd;

            --inventory-border:
                #dce7f3;

            --inventory-text:
                #0b2d63;

            --inventory-muted:
                #8294b3;

        }


        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing:
                border-box;

        }


        html,
        body {

            margin:
                0;

            padding:
                0;

            min-height:
                100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                #eef5fd;

        }


        body {

            overflow-x:
                hidden;

        }


        button,
        input,
        select {

            font-family:
                inherit;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR FONT
        |--------------------------------------------------------------------------
        */

        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {

            font-family:
                'Poppins',
                sans-serif !important;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .inventory-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --inventory-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --inventory-sidebar-width
                    )
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .inventory-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                38px
                35px
                70px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .inventory-hero {

            position:
                relative;

            min-height:
                155px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                34px
                38px;

            margin-bottom:
                26px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius:
                26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .inventory-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                260px;

            height:
                260px;

            right:
                -70px;

            top:
                -140px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .inventory-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                170px;

            height:
                170px;

            right:
                155px;

            bottom:
                -110px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .inventory-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .inventory-hero h1 {

            margin:
                0
                0
                8px;

            color:
                #ffffff;

            font-size:
                38px;

            line-height:
                1.05;

            font-weight:
                800;

            letter-spacing:
                -1.5px;

        }


        .inventory-hero p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size:
                14px;

            font-weight:
                500;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO ICON
        |--------------------------------------------------------------------------
        */

        .inventory-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                82px;

            height:
                82px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .26
                );

            border-radius:
                22px;

            background:

                linear-gradient(
                    145deg,
                    rgba(
                        255,
                        255,
                        255,
                        .20
                    ),
                    rgba(
                        255,
                        255,
                        255,
                        .10
                    )
                );

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .25
                ),

                0
                12px
                30px
                rgba(
                    0,
                    35,
                    100,
                    .18
                );

            font-size:
                34px;

            line-height:
                1;

        }


        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        .inventory-message {

            margin-bottom:
                22px;

            padding:
                14px
                17px;

            border-radius:
                12px;

            font-size:
                11px;

            font-weight:
                600;

        }


        .inventory-message.success {

            color:
                #166534;

            background:
                #ecfdf5;

            border:
                1px solid
                #bbf7d0;

        }


        .inventory-message.error {

            color:
                #991b1b;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .inventory-stats {

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
                30px;

        }


        .inventory-stat {

            position:
                relative;

            min-height:
                150px;

            overflow:
                hidden;

            padding:
                26px
                24px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --inventory-border
                );

            border-top:
                4px solid
                #2563eb;

            border-radius:
                20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .inventory-stat::after {

            content:
                "";

            position:
                absolute;

            right:
                -29px;

            bottom:
                -45px;

            width:
                110px;

            height:
                110px;

            border-radius:
                50%;

            background:
                #edf4ff;

        }


        .inventory-stat.total-stock {

            border-top-color:
                #16a34a;

        }


        .inventory-stat.total-stock::after {

            background:
                #eaf9ef;

        }


        .inventory-stat.low-stock {

            border-top-color:
                #f59e0b;

        }


        .inventory-stat.low-stock::after {

            background:
                #fff7df;

        }


        .inventory-stat.out-stock {

            border-top-color:
                #ef4444;

        }


        .inventory-stat.out-stock::after {

            background:
                #fff0f1;

        }


        .inventory-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                15px;

            color:
                #61728e;

            font-size:
                10px;

            font-weight:
                800;

            letter-spacing:
                .75px;

            text-transform:
                uppercase;

        }


        .inventory-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #0b326d;

            font-size:
                32px;

            line-height:
                1;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .inventory-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --inventory-border
                );

            border-radius:
                24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .inventory-panel-header {

            min-height:
                110px;

            padding:
                26px
                30px;

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
                #e7edf5;

        }


        .inventory-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .inventory-panel-icon {

            width:
                53px;

            height:
                53px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                16px;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size:
                22px;

            line-height:
                1;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .inventory-panel-header h2 {

            margin:
                0
                0
                5px;

            color:
                #092e65;

            font-size:
                20px;

            font-weight:
                800;

        }


        .inventory-panel-header p {

            margin:
                0;

            color:
                #8999b4;

            font-size:
                11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT
        |--------------------------------------------------------------------------
        */

        .inventory-count {

            min-height:
                36px;

            padding:
                0
                16px;

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
                #d6e7ff;

            border-radius:
                999px;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .inventory-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .inventory-filter-form {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1.5fr
                )

                minmax(
                    160px,
                    .6fr
                )

                minmax(
                    160px,
                    .6fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .inventory-filter-form input,
        .inventory-filter-form select {

            width:
                100%;

            height:
                43px;

            padding:
                0
                13px;

            outline:
                none;

            color:
                #26354e;

            background:
                #ffffff;

            border:
                1px solid
                #d8e3ef;

            border-radius:
                10px;

            font-size:
                10px;

        }


        .inventory-filter-form input::placeholder {

            color:
                #96a5b9;

        }


        .inventory-filter-form input:focus,
        .inventory-filter-form select:focus {

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


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .inventory-btn {

            min-height:
                43px;

            padding:
                0
                17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .inventory-btn-primary {

            color:
                #ffffff;

            border:
                0;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            box-shadow:

                0
                7px
                15px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

        }


        .inventory-btn-secondary {

            color:
                #66758b;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ee;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE WRAPPER
        |--------------------------------------------------------------------------
        */

        .inventory-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .inventory-table {

            width:
                100%;

            min-width:
                1120px;

            border-collapse:
                collapse;

        }


        .inventory-table thead {

            background:
                #f6f9fd;

        }


        .inventory-table th {

            height:
                44px;

            padding:
                0
                16px;

            color:
                #65758f;

            border-bottom:
                1px solid
                #dfe7f0;

            font-size:
                8px;

            font-weight:
                800;

            text-align:
                left;

            letter-spacing:
                .55px;

            text-transform:
                uppercase;

            white-space:
                nowrap;

        }


        .inventory-table td {

            padding:
                16px;

            color:
                #435169;

            border-bottom:
                1px solid
                #edf1f6;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .inventory-table tbody tr:hover {

            background:
                #f9fbff;

        }


        .inventory-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        */

        .inventory-product {

            display:
                flex;

            align-items:
                center;

            gap:
                11px;

            min-width:
                220px;

        }


        .inventory-product-image {

            width:
                46px;

            height:
                46px;

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

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                11px;

            font-size:
                18px;

            line-height:
                1;

        }


        .inventory-product-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        .inventory-product-name {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #112b55;

            font-size:
                10px;

            font-weight:
                800;

        }


        .inventory-product-id {

            display:
                block;

            color:
                #8897ac;

            font-size:
                8px;

        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        .inventory-category {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            color:
                #52647f;

            background:
                #f1f5f9;

            border:
                1px solid
                #e2e8f0;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                700;

        }


        /*
        |--------------------------------------------------------------------------
        | STOCK
        |--------------------------------------------------------------------------
        */

        .inventory-stock {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        .stock-good {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .stock-low {

            color:
                #a16207;

            background:
                #fffbea;

        }


        .stock-out {

            color:
                #b91c1c;

            background:
                #fff1f2;

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT STATUS
        |--------------------------------------------------------------------------
        */

        .inventory-status {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
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


        /*
        |--------------------------------------------------------------------------
        | STOCK UPDATE
        |--------------------------------------------------------------------------
        */

        .stock-form {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

            margin:
                0;

        }


        .stock-input {

            width:
                68px;

            height:
                34px;

            padding:
                0
                8px;

            outline:
                none;

            color:
                #26354e;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ef;

            border-radius:
                9px;

            font-size:
                9px;

            text-align:
                center;

        }


        .stock-input:focus {

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


        .stock-save {

            height:
                34px;

            padding:
                0
                11px;

            border:
                1px solid
                #dbeafe;

            border-radius:
                9px;

            color:
                #1d4ed8;

            background:
                #eff6ff;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .stock-save:hover {

            background:
                #dbeafe;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .inventory-empty {

            padding:
                70px
                20px;

            text-align:
                center;

        }


        .inventory-empty-icon {

            width:
                58px;

            height:
                58px;

            margin:
                0
                auto
                14px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                15px;

            font-size:
                27px;

        }


        .inventory-empty h3 {

            margin:
                0
                0
                6px;

            color:
                #49617f;

            font-size:
                14px;

        }


        .inventory-empty p {

            margin:
                0;

            color:
                #94a3b8;

            font-size:
                10px;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {

            .inventory-stats {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .inventory-filter-form {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .inventory-filter-form input {

                grid-column:
                    1 / -1;

            }

        }


        @media (max-width: 900px) {

            :root {

                --inventory-sidebar-width:
                    0px;

            }


            .inventory-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .inventory-content {

                padding:
                    25px
                    20px
                    50px;

            }


            .inventory-hero {

                min-height:
                    140px;

                padding:
                    28px;

            }


            .inventory-hero h1 {

                font-size:
                    31px;

            }


            .inventory-hero-icon {

                width:
                    67px;

                height:
                    67px;

                font-size:
                    28px;

            }

        }


        @media (max-width: 650px) {

            .inventory-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .inventory-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .inventory-hero h1 {

                font-size:
                    27px;

            }


            .inventory-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .inventory-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .inventory-stats {

                grid-template-columns:
                    1fr;

                gap:
                    12px;

            }


            .inventory-stat {

                min-height:
                    120px;

            }


            .inventory-panel-header {

                padding:
                    20px
                    17px;

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }


            .inventory-filter-form {

                grid-template-columns:
                    1fr;

            }


            .inventory-filter-form input {

                grid-column:
                    auto;

            }


            .inventory-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    /*
    |--------------------------------------------------------------------------
    | ADMIN SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="inventory-main">


        <div class="inventory-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="inventory-hero">


                <div class="inventory-hero-text">

                    <h1>
                        Inventory
                    </h1>

                    <p>
                        Monitor and manage product stock across HochipoHub vendors.
                    </p>

                </div>


                <div class="inventory-hero-icon">

                    📦

                </div>


            </section>


            <!-- =====================================================
                 MESSAGE
            ====================================================== -->

            <?php if ($message !== ''): ?>


                <div
                    class="
                        inventory-message
                        <?= inventoryEscape(
                            $messageType
                        ) ?>
                    "
                >

                    <?= inventoryEscape(
                        $message
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="inventory-stats">


                <!-- TOTAL PRODUCTS -->

                <div class="inventory-stat">

                    <span class="inventory-stat-label">

                        Total Products

                    </span>


                    <strong class="inventory-stat-value">

                        <?= number_format(
                            $stats['products']
                        ) ?>

                    </strong>

                </div>


                <!-- TOTAL STOCK -->

                <div
                    class="
                        inventory-stat
                        total-stock
                    "
                >

                    <span class="inventory-stat-label">

                        Total Stock

                    </span>


                    <strong class="inventory-stat-value">

                        <?= number_format(
                            $stats[
                                'total_stock'
                            ]
                        ) ?>

                    </strong>

                </div>


                <!-- LOW STOCK -->

                <div
                    class="
                        inventory-stat
                        low-stock
                    "
                >

                    <span class="inventory-stat-label">

                        Low Stock

                    </span>


                    <strong class="inventory-stat-value">

                        <?= number_format(
                            $stats[
                                'low_stock'
                            ]
                        ) ?>

                    </strong>

                </div>


                <!-- OUT STOCK -->

                <div
                    class="
                        inventory-stat
                        out-stock
                    "
                >

                    <span class="inventory-stat-label">

                        Out of Stock

                    </span>


                    <strong class="inventory-stat-value">

                        <?= number_format(
                            $stats[
                                'out_stock'
                            ]
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 INVENTORY PANEL
            ====================================================== -->

            <section class="inventory-panel">


                <!-- =================================================
                     PANEL HEADER
                ================================================== -->

                <div class="inventory-panel-header">


                    <div class="inventory-panel-title">


                        <div class="inventory-panel-icon">

                            📊

                        </div>


                        <div>

                            <h2>
                                Product Inventory
                            </h2>

                            <p>
                                Search products and update stock quantities.
                            </p>

                        </div>


                    </div>


                    <span class="inventory-count">

                        <?= number_format(
                            count(
                                $products
                            )
                        ) ?>

                        products

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="inventory-filter-wrapper">


                    <form
                        method="GET"
                        action="inventory.php"
                        class="inventory-filter-form"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= inventoryEscape(
                                $search
                            ) ?>"
                            placeholder="Search product, vendor or category..."
                            autocomplete="off"
                        >


                        <!-- VENDOR -->

                        <select
                            name="vendor_id"
                            aria-label="Filter vendor"
                        >

                            <option value="0">

                                All Vendors

                            </option>


                            <?php foreach ($vendors as $vendor): ?>


                                <option
                                    value="<?= (int)
                                        $vendor[
                                            'vendor_id'
                                        ] ?>"
                                    <?= $vendorFilter ===
                                        (int)
                                        $vendor[
                                            'vendor_id'
                                        ]
                                            ? 'selected'
                                            : '' ?>
                                >

                                    <?= inventoryEscape(
                                        $vendor[
                                            'business_name'
                                        ]
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- STOCK -->

                        <select
                            name="stock_status"
                            aria-label="Filter stock"
                        >

                            <option value="">

                                All Stock

                            </option>


                            <option
                                value="available"
                                <?= $stockFilter ===
                                    'available'
                                        ? 'selected'
                                        : '' ?>
                            >

                                In Stock

                            </option>


                            <option
                                value="low"
                                <?= $stockFilter ===
                                    'low'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Low Stock

                            </option>


                            <option
                                value="out"
                                <?= $stockFilter ===
                                    'out'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Out of Stock

                            </option>


                        </select>


                        <!-- SEARCH BUTTON -->

                        <button
                            type="submit"
                            class="
                                inventory-btn
                                inventory-btn-primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="inventory.php"
                            class="
                                inventory-btn
                                inventory-btn-secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <?php if (empty($products)): ?>


                    <div class="inventory-empty">


                        <div class="inventory-empty-icon">

                            🗃️

                        </div>


                        <h3>
                            No products found
                        </h3>


                        <p>
                            Try changing your search or stock filter.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="inventory-table-wrapper">


                        <table class="inventory-table">


                            <thead>

                                <tr>

                                    <th>
                                        Product
                                    </th>

                                    <th>
                                        Vendor
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Stock
                                    </th>

                                    <th>
                                        Stock Status
                                    </th>

                                    <th>
                                        Product Status
                                    </th>

                                    <th>
                                        Update
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($products as $product): ?>


                                    <?php

                                    $stock =
                                        (int)
                                        $product[
                                            'stock_quantity'
                                        ];


                                    $image =
                                        trim(
                                            $product[
                                                'image'
                                            ]
                                            ?? ''
                                        );

                                    ?>


                                    <tr>


                                        <!-- PRODUCT -->

                                        <td>


                                            <div class="inventory-product">


                                                <div class="inventory-product-image">


                                                    <?php if ($image !== ''): ?>


                                                        <img
                                                            src="<?= inventoryEscape(
                                                                '../uploads/products/' .
                                                                rawurlencode(
                                                                    basename(
                                                                        $image
                                                                    )
                                                                )
                                                            ) ?>"
                                                            alt="<?= inventoryEscape(
                                                                $product[
                                                                    'product_name'
                                                                ]
                                                            ) ?>"
                                                            onerror="
                                                                this.style.display='none';
                                                                this.parentElement.innerHTML='📦';
                                                            "
                                                        >


                                                    <?php else: ?>


                                                        📦


                                                    <?php endif; ?>


                                                </div>


                                                <div>


                                                    <span class="inventory-product-name">

                                                        <?= inventoryEscape(
                                                            $product[
                                                                'product_name'
                                                            ]
                                                        ) ?>

                                                    </span>


                                                    <span class="inventory-product-id">

                                                        ID:
                                                        #<?= (int)
                                                            $product[
                                                                'product_id'
                                                            ] ?>

                                                    </span>


                                                </div>


                                            </div>


                                        </td>


                                        <!-- VENDOR -->

                                        <td>

                                            <?= inventoryEscape(
                                                $product[
                                                    'business_name'
                                                ]
                                                ?? 'Unknown Vendor'
                                            ) ?>

                                        </td>


                                        <!-- CATEGORY -->

                                        <td>

                                            <span class="inventory-category">

                                                <?= inventoryEscape(
                                                    $product[
                                                        'category_name'
                                                    ]
                                                    ?? 'Uncategorized'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- PRICE -->

                                        <td>

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

                                        </td>


                                        <!-- STOCK -->

                                        <td>

                                            <strong>

                                                <?= number_format(
                                                    $stock
                                                ) ?>

                                            </strong>

                                            units

                                        </td>


                                        <!-- STOCK STATUS -->

                                        <td>

                                            <span
                                                class="
                                                    inventory-stock
                                                    <?= inventoryEscape(
                                                        inventoryStockClass(
                                                            $stock
                                                        )
                                                    ) ?>
                                                "
                                            >

                                                <?= inventoryEscape(
                                                    inventoryStockLabel(
                                                        $stock
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- PRODUCT STATUS -->

                                        <td>

                                            <span class="inventory-status">

                                                <?= inventoryEscape(
                                                    $product[
                                                        'status'
                                                    ]
                                                    ?? '-'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- UPDATE -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="inventory.php"
                                                class="stock-form"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= inventoryEscape(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="update_stock"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="product_id"
                                                    value="<?= (int)
                                                        $product[
                                                            'product_id'
                                                        ] ?>"
                                                >


                                                <input
                                                    type="number"
                                                    name="stock_quantity"
                                                    class="stock-input"
                                                    min="0"
                                                    value="<?= $stock ?>"
                                                    required
                                                >


                                                <button
                                                    type="submit"
                                                    class="stock-save"
                                                >

                                                    SAVE

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


</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH SYNC
    |--------------------------------------------------------------------------
    */

    function syncInventorySidebar() {

        const main =
            document.querySelector(
                '.inventory-main'
            );


        if (!main) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            document.documentElement
                .style
                .setProperty(
                    '--inventory-sidebar-width',
                    '0px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        if (!sidebar) {

            document.documentElement
                .style
                .setProperty(
                    '--inventory-sidebar-width',
                    '260px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | REAL WIDTH
        |--------------------------------------------------------------------------
        */

        const rect =
            sidebar
                .getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement
                .style
                .setProperty(
                    '--inventory-sidebar-width',
                    rect.right + 'px'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncInventorySidebar();


            setTimeout(
                syncInventorySidebar,
                100
            );


            setTimeout(
                syncInventorySidebar,
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncInventorySidebar
    );

</script>


</body>

</html>