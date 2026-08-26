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
| HELPER
|--------------------------------------------------------------------------
*/

function inventoryEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


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
    | UPDATE
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
                    | DETERMINE PRODUCT STATUS
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
                    | SYNC INVENTORY TABLE
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
                        'Stock updated successfully.';


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
    isset($_SESSION['inventory_success'])
) {

    $message =
        $_SESSION['inventory_success'];

    $messageType =
        'success';

    unset(
        $_SESSION['inventory_success']
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
    | VENDOR
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
    | STOCK
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
        Inventory - HochipoHub
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
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;

            min-height: 100%;

            background: #f4f7fb;
        }


        body {
            overflow-x: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .inventory-main {
            min-height: 100vh;

            margin-left: 260px;

            width:
                calc(
                    100% - 260px
                );

            background: #f4f7fb;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .inventory-header {
            min-height: 105px;

            display: flex;
            align-items: center;

            padding:
                22px
                32px;

            background: #ffffff;

            border-bottom:
                1px solid
                #e5eaf1;
        }


        .inventory-header h1 {
            margin:
                0
                0
                5px;

            color: #101828;

            font-size: 26px;

            font-weight: 800;
        }


        .inventory-header p {
            margin: 0;

            color: #8996a8;

            font-size: 12px;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .inventory-content {
            width: 100%;

            max-width: 1280px;

            margin:
                0
                auto;

            padding:
                28px
                26px
                60px;
        }


        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        .inventory-message {
            margin-bottom: 18px;

            padding:
                14px
                16px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 600;
        }


        .inventory-message.success {
            color: #166534;

            background: #ecfdf5;

            border:
                1px solid
                #bbf7d0;
        }


        .inventory-message.error {
            color: #991b1b;

            background: #fff1f2;

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

            margin-bottom: 20px;
        }


        .inventory-stat {
            position: relative;

            overflow: hidden;

            min-height: 110px;

            padding:
                20px;

            background: #ffffff;

            border:
                1px solid
                #dce4ee;

            border-top:
                3px solid
                #3478f6;

            border-radius: 14px;

            box-shadow:
                0
                5px
                18px
                rgba(
                    30,
                    55,
                    90,
                    0.04
                );
        }


        .inventory-stat::after {
            content: "";

            position: absolute;

            right: -20px;
            bottom: -35px;

            width: 100px;
            height: 100px;

            border-radius: 50%;

            background: #edf4ff;
        }


        .inventory-stat:nth-child(2)::after {
            background: #eaf8f1;
        }


        .inventory-stat:nth-child(3)::after {
            background: #fff8db;
        }


        .inventory-stat:nth-child(4)::after {
            background: #fff0f1;
        }


        .inventory-stat-label {
            position: relative;

            z-index: 2;

            display: block;

            margin-bottom: 14px;

            color: #68778c;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;
        }


        .inventory-stat-value {
            position: relative;

            z-index: 2;

            color: #101828;

            font-size: 27px;

            font-weight: 900;
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .inventory-filter {
            margin-bottom: 20px;

            padding:
                25px;

            background: #ffffff;

            border:
                1px solid
                #dce4ee;

            border-radius: 14px;
        }


        .inventory-filter-form {
            display: grid;

            grid-template-columns:
                2fr
                1fr
                1fr
                auto;

            align-items: end;

            gap: 10px;
        }


        .inventory-field label {
            display: block;

            margin-bottom: 6px;

            color: #68778c;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;
        }


        .inventory-field input,
        .inventory-field select {
            width: 100%;

            height: 41px;

            padding:
                0
                12px;

            border:
                1px solid
                #d7e0eb;

            border-radius: 8px;

            outline: none;

            background: #ffffff;

            color: #253247;

            font-family: inherit;

            font-size: 11px;
        }


        .inventory-field input:focus,
        .inventory-field select:focus {
            border-color: #3478f6;
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER BUTTON
        |--------------------------------------------------------------------------
        */

        .inventory-filter-actions {
            display: flex;

            gap: 8px;
        }


        .inventory-btn {
            height: 41px;

            padding:
                0
                16px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            border: 0;

            font-family: inherit;

            font-size: 10px;

            font-weight: 800;

            text-decoration: none;

            cursor: pointer;
        }


        .inventory-btn-primary {
            color: #ffffff;

            background: #2868e8;
        }


        .inventory-btn-secondary {
            color: #65758a;

            background: #ffffff;

            border:
                1px solid
                #d7e0eb;
        }


        /*
        |--------------------------------------------------------------------------
        | CARD
        |--------------------------------------------------------------------------
        */

        .inventory-card {
            padding:
                20px;

            background: #ffffff;

            border:
                1px solid
                #dce4ee;

            border-radius: 14px;
        }


        .inventory-card-header {
            padding:
                5px
                10px
                17px;

            border-bottom:
                1px solid
                #e7edf4;
        }


        .inventory-card-header h2 {
            margin:
                0
                0
                4px;

            color: #101828;

            font-size: 14px;

            font-weight: 800;
        }


        .inventory-card-header p {
            margin: 0;

            color: #95a1b2;

            font-size: 9px;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .inventory-table-wrapper {
            margin-top: 16px;

            width: 100%;

            overflow-x: auto;

            border:
                1px solid
                #dfe6ef;

            border-radius: 10px;
        }


        .inventory-table {
            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;
        }


        .inventory-table th {
            height: 38px;

            padding:
                0
                14px;

            background: #f6f8fb;

            color: #65748a;

            border-bottom:
                1px solid
                #dfe6ef;

            font-size: 8px;

            font-weight: 800;

            text-align: left;

            text-transform: uppercase;
        }


        .inventory-table td {
            padding:
                13px
                14px;

            color: #435169;

            border-bottom:
                1px solid
                #e8edf4;

            font-size: 10px;

            vertical-align: middle;
        }


        .inventory-table tr:last-child td {
            border-bottom: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        */

        .inventory-product {
            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 200px;
        }


        .inventory-product-image {
            width: 40px;
            height: 40px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            overflow: hidden;

            background: #edf4ff;

            border-radius: 9px;
        }


        .inventory-product-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .inventory-product-name {
            display: block;

            color: #101828;

            font-size: 10px;

            font-weight: 800;
        }


        .inventory-product-id {
            display: block;

            margin-top: 3px;

            color: #8d9aad;

            font-size: 8px;
        }


        /*
        |--------------------------------------------------------------------------
        | STOCK
        |--------------------------------------------------------------------------
        */

        .inventory-stock {
            display: inline-flex;

            align-items: center;

            min-height: 24px;

            padding:
                0
                9px;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 800;
        }


        .stock-good {
            color: #15803d;

            background: #ecfdf3;
        }


        .stock-low {
            color: #a16207;

            background: #fffbea;
        }


        .stock-out {
            color: #b91c1c;

            background: #fff1f2;
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT STATUS
        |--------------------------------------------------------------------------
        */

        .inventory-status {
            display: inline-flex;

            align-items: center;

            min-height: 24px;

            padding:
                0
                9px;

            background: #f1f5f9;

            color: #475569;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 800;
        }


        /*
        |--------------------------------------------------------------------------
        | STOCK UPDATE
        |--------------------------------------------------------------------------
        */

        .stock-form {
            display: flex;

            align-items: center;

            gap: 6px;
        }


        .stock-input {
            width: 65px;

            height: 31px;

            padding:
                0
                7px;

            border:
                1px solid
                #d7e0eb;

            border-radius: 7px;

            outline: none;

            text-align: center;
        }


        .stock-save {
            height: 31px;

            padding:
                0
                10px;

            border: 0;

            border-radius: 7px;

            background: #edf4ff;

            color: #1f62df;

            font-size: 8px;

            font-weight: 800;

            cursor: pointer;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .inventory-empty {
            padding:
                60px
                20px;

            text-align: center;
        }


        .inventory-empty h3 {
            margin:
                0
                0
                6px;

            color: #101828;
        }


        .inventory-empty p {
            margin: 0;

            color: #8996a8;

            font-size: 10px;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1100px) {

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

        }


        @media (max-width: 900px) {

            .inventory-main {
                margin-left: 0;

                width: 100%;
            }

        }


        @media (max-width: 600px) {

            .inventory-header {
                padding:
                    20px
                    15px;
            }


            .inventory-content {
                padding:
                    20px
                    14px
                    40px;
            }


            .inventory-stats {
                grid-template-columns:
                    1fr;
            }


            .inventory-filter-form {
                grid-template-columns:
                    1fr;
            }


            .inventory-filter-actions {
                flex-direction: column;
            }


            .inventory-btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| ADMIN SIDEBAR
|--------------------------------------------------------------------------
*/

$sidebarPath =
    __DIR__ .
    '/../includes/admin_sidebar.php';

if (file_exists($sidebarPath)) {
    require_once $sidebarPath;
}

?>


<main class="inventory-main">


    <!-- ===========================================================
         HEADER
    ============================================================ -->

    <header class="inventory-header">

        <div>

            <h1>
                Inventory
            </h1>

            <p>
                Monitor and manage product stock across HochipoHub vendors.
            </p>

        </div>

    </header>


    <!-- ===========================================================
         CONTENT
    ============================================================ -->

    <div class="inventory-content">


        <!-- MESSAGE -->

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


        <!-- =======================================================
             STATISTICS
        ======================================================== -->

        <section class="inventory-stats">


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


            <div class="inventory-stat">

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


            <div class="inventory-stat">

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


            <div class="inventory-stat">

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


        <!-- =======================================================
             FILTER
        ======================================================== -->

        <section class="inventory-filter">


            <form
                method="GET"
                action="inventory.php"
                class="inventory-filter-form"
            >


                <div class="inventory-field">

                    <label>
                        Search
                    </label>

                    <input
                        type="search"
                        name="search"
                        value="<?= inventoryEscape(
                            $search
                        ) ?>"
                        placeholder="Search product, vendor or category..."
                    >

                </div>


                <div class="inventory-field">

                    <label>
                        Vendor
                    </label>

                    <select name="vendor_id">

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

                </div>


                <div class="inventory-field">

                    <label>
                        Stock Status
                    </label>

                    <select name="stock_status">

                        <option value="">
                            All Stock
                        </option>

                        <option
                            value="available"
                            <?= $stockFilter === 'available'
                                ? 'selected'
                                : '' ?>
                        >
                            In Stock
                        </option>

                        <option
                            value="low"
                            <?= $stockFilter === 'low'
                                ? 'selected'
                                : '' ?>
                        >
                            Low Stock
                        </option>

                        <option
                            value="out"
                            <?= $stockFilter === 'out'
                                ? 'selected'
                                : '' ?>
                        >
                            Out of Stock
                        </option>

                    </select>

                </div>


                <div class="inventory-filter-actions">

                    <button
                        type="submit"
                        class="
                            inventory-btn
                            inventory-btn-primary
                        "
                    >
                        Search
                    </button>

                    <a
                        href="inventory.php"
                        class="
                            inventory-btn
                            inventory-btn-secondary
                        "
                    >
                        Reset
                    </a>

                </div>


            </form>


        </section>


        <!-- =======================================================
             INVENTORY TABLE
        ======================================================== -->

        <section class="inventory-card">


            <div class="inventory-card-header">

                <h2>
                    Product Inventory
                </h2>

                <p>

                    <?= number_format(
                        count(
                            $products
                        )
                    ) ?>

                    product(s) found

                </p>

            </div>


            <?php if (empty($products)): ?>


                <div class="inventory-empty">

                    <h3>
                        No products found
                    </h3>

                    <p>
                        Try changing your search or filter.
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
                                    $product['image']
                                    ?? '';

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
                                                        "
                                                    >

                                                <?php else: ?>

                                                    #

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

                                        <?= inventoryEscape(
                                            $product[
                                                'category_name'
                                            ]
                                            ?? 'Uncategorized'
                                        ) ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $product[
                                                'price'
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <strong>

                                            <?= number_format(
                                                $stock
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- STOCK STATUS -->

                                    <td>

                                        <span
                                            class="
                                                inventory-stock
                                                <?= inventoryStockClass(
                                                    $stock
                                                ) ?>
                                            "
                                        >

                                            <?= inventoryStockLabel(
                                                $stock
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


</body>

</html>