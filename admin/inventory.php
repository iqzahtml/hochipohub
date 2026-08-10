<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . site_url('index.php?login=required'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| CHECK ADMIN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        role,
        status
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    session_destroy();

    header('Location: ' . site_url('index.php'));
    exit;
}

if ($user['role'] !== 'admin') {
    header('Location: ' . site_url('dashboard.php?access=denied'));
    exit;
}

if ($user['status'] !== 'active') {
    session_destroy();

    header('Location: ' . site_url('index.php?account=inactive'));
    exit;
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['stock_status'] ?? '');
$vendorFilter = (int) ($_GET['vendor_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| UPDATE STOCK
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'update_stock') {

        $productId = (int) ($_POST['product_id'] ?? 0);
        $newStock = (int) ($_POST['stock_quantity'] ?? 0);

        if ($productId <= 0) {

            $message = 'Invalid product selected.';
            $messageType = 'error';

        } elseif ($newStock < 0) {

            $message = 'Stock quantity cannot be negative.';
            $messageType = 'error';

        } else {

            $stmt = $conn->prepare("
                UPDATE products
                SET stock_quantity = ?
                WHERE product_id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $newStock,
                $productId
            );

            if ($stmt->execute()) {

                $message = 'Stock updated successfully.';
                $messageType = 'success';

            } else {

                $message = 'Failed to update stock.';
                $messageType = 'error';
            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| INVENTORY STATISTICS
|--------------------------------------------------------------------------
*/

$stats = [
    'products' => 0,
    'total_stock' => 0,
    'low_stock' => 0,
    'out_stock' => 0
];


/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $stats['products'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| TOTAL STOCK
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COALESCE(SUM(stock_quantity), 0) AS total
    FROM products
    WHERE status != 'Hidden'
");

if ($result) {
    $stats['total_stock'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| LOW STOCK
|--------------------------------------------------------------------------
| Low stock = 1 - 10
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stock_quantity BETWEEN 1 AND 10
    AND status != 'Hidden'
");

if ($result) {
    $stats['low_stock'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| OUT OF STOCK
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stock_quantity <= 0
    AND status != 'Hidden'
");

if ($result) {
    $stats['out_stock'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| GET VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];

$result = $conn->query("
    SELECT
        vendor_id,
        business_name
    FROM vendors
    ORDER BY business_name ASC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| BUILD PRODUCT QUERY
|--------------------------------------------------------------------------
*/

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
$types = '';


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            p.product_name LIKE ?
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'sss';
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

    $params[] = $vendorFilter;
    $types .= 'i';
}


/*
|--------------------------------------------------------------------------
| STOCK FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter === 'out') {

    $sql .= "
        AND p.stock_quantity <= 0
    ";

} elseif ($statusFilter === 'low') {

    $sql .= "
        AND p.stock_quantity BETWEEN 1 AND 10
    ";

} elseif ($statusFilter === 'available') {

    $sql .= "
        AND p.stock_quantity > 10
    ";
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY p.created_at DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$products = [];

$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function inventory_stock_class($stock)
{
    $stock = (int) $stock;

    if ($stock <= 0) {
        return 'stock-out';
    }

    if ($stock <= 10) {
        return 'stock-low';
    }

    return 'stock-good';
}


function inventory_stock_label($stock)
{
    $stock = (int) $stock;

    if ($stock <= 0) {
        return 'Out of Stock';
    }

    if ($stock <= 10) {
        return 'Low Stock';
    }

    return 'In Stock';
}


function inventory_product_image($image)
{
    if (!$image) {
        return '';
    }

    return site_url(
        'image/product/' . $image
    );
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
        Inventory |
        <?php echo htmlspecialchars(SITE_NAME); ?>
    </title>


    <link
        rel="stylesheet"
        href="<?php
        echo site_url('css/style.css');
        ?>"
    >

    <link
        rel="stylesheet"
        href="<?php
        echo site_url('css/admin.css');
        ?>"
    >

    <link
        rel="stylesheet"
        href="<?php
        echo site_url('css/responsive.css');
        ?>"
    >


    <style>

        /* =====================================================
           INVENTORY PAGE
        ===================================================== */

        .inventory-page {

            min-height: 100vh;

            padding: 35px 0 80px;

            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(37, 99, 235, .16),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(14, 165, 233, .12),
                    transparent 25%
                ),
                linear-gradient(
                    145deg,
                    #020617,
                    #061a35 55%,
                    #020617
                );

            color: #f8fafc;

        }


        .inventory-container {

            width: 92%;

            max-width: 1450px;

            margin: auto;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .inventory-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-end;

            gap: 20px;

            margin-bottom: 24px;

        }


        .inventory-eyebrow {

            margin-bottom: 7px;

            color: #38bdf8;

            font-size: 9px;

            font-weight: 950;

            letter-spacing: 2px;

            text-transform: uppercase;

        }


        .inventory-header h1 {

            margin: 0;

            color: #f8fafc;

            font-size: clamp(
                27px,
                4vw,
                42px
            );

            font-weight: 950;

            letter-spacing: -1.5px;

        }


        .inventory-header p {

            margin: 8px 0 0;

            color: #64748b;

            font-size: 11px;

        }


        /* =====================================================
           STATS
        ===================================================== */

        .inventory-stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 13px;

            margin-bottom: 20px;

        }


        .inventory-stat {

            position: relative;

            overflow: hidden;

            padding: 20px;

            border:
                1px solid
                rgba(148, 163, 184, .09);

            border-radius: 18px;

            background:
                rgba(15, 23, 42, .78);

            transition: .2s ease;

        }


        .inventory-stat:hover {

            transform: translateY(-3px);

            border-color:
                rgba(56, 189, 248, .25);

        }


        .inventory-stat-label {

            display: block;

            margin-bottom: 7px;

            color: #64748b;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-transform: uppercase;

        }


        .inventory-stat-value {

            color: #f8fafc;

            font-size: 25px;

            font-weight: 950;

        }


        .inventory-stat.blue
        .inventory-stat-value {

            color: #38bdf8;

        }


        .inventory-stat.yellow
        .inventory-stat-value {

            color: #facc15;

        }


        .inventory-stat.red
        .inventory-stat-value {

            color: #f87171;

        }


        /* =====================================================
           FILTER CARD
        ===================================================== */

        .inventory-filter {

            margin-bottom: 18px;

            padding: 17px;

            border:
                1px solid
                rgba(148, 163, 184, .09);

            border-radius: 18px;

            background:
                rgba(15, 23, 42, .78);

        }


        .inventory-filter-form {

            display: grid;

            grid-template-columns:
                1.8fr 1fr 1fr auto;

            gap: 10px;

            align-items: end;

        }


        .inventory-field label {

            display: block;

            margin-bottom: 6px;

            color: #64748b;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-transform: uppercase;

        }


        .inventory-field input,
        .inventory-field select {

            width: 100%;

            box-sizing: border-box;

            padding: 11px 12px;

            border:
                1px solid
                rgba(148, 163, 184, .12);

            border-radius: 11px;

            outline: none;

            background:
                rgba(2, 6, 23, .65);

            color: #cbd5e1;

            font-size: 10px;

        }


        .inventory-field input:focus,
        .inventory-field select:focus {

            border-color:
                rgba(56, 189, 248, .45);

        }


        .inventory-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 17px;

            border: 0;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #0284c7,
                    #2563eb
                );

            color: white;

            font-size: 9px;

            font-weight: 900;

            cursor: pointer;

            text-decoration: none;

        }


        .inventory-button:hover {

            filter: brightness(1.12);

        }


        .inventory-reset {

            margin-left: 6px;

            color: #64748b;

            font-size: 9px;

            text-decoration: none;

        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .inventory-message {

            margin-bottom: 17px;

            padding: 13px 16px;

            border-radius: 12px;

            font-size: 10px;

            font-weight: 800;

        }


        .inventory-message.success {

            border:
                1px solid
                rgba(34, 197, 94, .18);

            background:
                rgba(34, 197, 94, .07);

            color: #86efac;

        }


        .inventory-message.error {

            border:
                1px solid
                rgba(239, 68, 68, .18);

            background:
                rgba(239, 68, 68, .07);

            color: #fca5a5;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .inventory-card {

            overflow: hidden;

            border:
                1px solid
                rgba(148, 163, 184, .09);

            border-radius: 20px;

            background:
                rgba(15, 23, 42, .78);

        }


        .inventory-card-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 18px 20px;

            border-bottom:
                1px solid
                rgba(148, 163, 184, .07);

        }


        .inventory-card-header h2 {

            margin: 0;

            color: #e2e8f0;

            font-size: 14px;

            font-weight: 900;

        }


        .inventory-card-header span {

            color: #475569;

            font-size: 9px;

        }


        .inventory-table-wrap {

            overflow-x: auto;

        }


        .inventory-table {

            width: 100%;

            min-width: 900px;

            border-collapse: collapse;

        }


        .inventory-table th {

            padding: 13px 15px;

            border-bottom:
                1px solid
                rgba(148, 163, 184, .07);

            background:
                rgba(2, 6, 23, .25);

            color: #475569;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-align: left;

            text-transform: uppercase;

        }


        .inventory-table td {

            padding: 13px 15px;

            border-bottom:
                1px solid
                rgba(148, 163, 184, .055);

            color: #94a3b8;

            font-size: 9px;

            vertical-align: middle;

        }


        .inventory-table tbody tr {

            transition: .2s ease;

        }


        .inventory-table tbody tr:hover {

            background:
                rgba(14, 165, 233, .035);

        }


        .inventory-table tbody tr:last-child td {

            border-bottom: 0;

        }


        /* =====================================================
           PRODUCT
        ===================================================== */

        .inventory-product {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 230px;

        }


        .inventory-product-image {

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            width: 43px;

            height: 43px;

            overflow: hidden;

            border:
                1px solid
                rgba(148, 163, 184, .08);

            border-radius: 11px;

            background:
                rgba(2, 6, 23, .65);

        }


        .inventory-product-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .inventory-product-placeholder {

            color: #334155;

            font-size: 15px;

        }


        .inventory-product-name {

            display: block;

            max-width: 230px;

            overflow: hidden;

            color: #cbd5e1;

            font-size: 10px;

            font-weight: 850;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .inventory-product-id {

            display: block;

            margin-top: 3px;

            color: #475569;

            font-size: 8px;

        }


        /* =====================================================
           STOCK
        ===================================================== */

        .inventory-stock {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            border-radius: 99px;

            font-size: 8px;

            font-weight: 900;

        }


        .inventory-stock::before {

            content: "";

            width: 5px;

            height: 5px;

            border-radius: 50%;

        }


        .stock-good {

            color: #86efac;

            background:
                rgba(34, 197, 94, .08);

        }


        .stock-good::before {

            background: #22c55e;

        }


        .stock-low {

            color: #fde047;

            background:
                rgba(250, 204, 21, .08);

        }


        .stock-low::before {

            background: #facc15;

        }


        .stock-out {

            color: #fca5a5;

            background:
                rgba(239, 68, 68, .08);

        }


        .stock-out::before {

            background: #ef4444;

        }


        /* =====================================================
           STOCK UPDATE
        ===================================================== */

        .stock-form {

            display: flex;

            align-items: center;

            gap: 6px;

        }


        .stock-input {

            width: 65px;

            padding: 7px 8px;

            box-sizing: border-box;

            border:
                1px solid
                rgba(148, 163, 184, .12);

            border-radius: 8px;

            outline: none;

            background:
                rgba(2, 6, 23, .6);

            color: #e2e8f0;

            font-size: 9px;

            text-align: center;

        }


        .stock-input:focus {

            border-color:
                rgba(56, 189, 248, .45);

        }


        .stock-save {

            padding: 7px 9px;

            border: 0;

            border-radius: 8px;

            background:
                rgba(14, 165, 233, .10);

            color: #38bdf8;

            font-size: 8px;

            font-weight: 900;

            cursor: pointer;

        }


        .stock-save:hover {

            background:
                rgba(14, 165, 233, .18);

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .product-status {

            display: inline-flex;

            padding: 5px 8px;

            border-radius: 99px;

            background:
                rgba(148, 163, 184, .07);

            color: #94a3b8;

            font-size: 8px;

            font-weight: 900;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .inventory-empty {

            padding: 55px 20px;

            text-align: center;

        }


        .inventory-empty-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 55px;

            height: 55px;

            margin: 0 auto 13px;

            border-radius: 17px;

            background:
                rgba(14, 165, 233, .08);

            color: #38bdf8;

            font-size: 20px;

        }


        .inventory-empty h3 {

            margin: 0;

            color: #cbd5e1;

            font-size: 14px;

        }


        .inventory-empty p {

            margin: 6px auto 0;

            max-width: 360px;

            color: #475569;

            font-size: 9px;

            line-height: 1.6;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .inventory-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .inventory-filter-form {

                grid-template-columns:
                    1fr 1fr;

            }

        }


        @media (max-width: 600px) {

            .inventory-page {

                padding-top: 25px;

            }

            .inventory-header {

                align-items: flex-start;

                flex-direction: column;

            }

            .inventory-stats {

                grid-template-columns: 1fr;

            }

            .inventory-filter-form {

                grid-template-columns: 1fr;

            }

            .inventory-reset {

                display: inline-block;

                margin: 8px 0 0;

            }

        }

    </style>

</head>


<body>


<?php
require_once __DIR__ . '/../includes/navbar.php';
?>


<main class="inventory-page">


    <div class="inventory-container">


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="inventory-header">

            <div>

                <div class="inventory-eyebrow">
                    ADMIN CONTROL
                </div>

                <h1>
                    Inventory
                </h1>

                <p>
                    Monitor product stock across all
                    HochipoHub vendors.
                </p>

            </div>

        </div>


        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if ($message !== ''): ?>

            <div
                class="
                    inventory-message
                    <?php echo htmlspecialchars($messageType); ?>
                "
            >

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATS
        ================================================== -->

        <section class="inventory-stats">


            <div class="inventory-stat">

                <span class="inventory-stat-label">
                    Total Products
                </span>

                <strong class="inventory-stat-value">
                    <?php
                    echo number_format(
                        $stats['products']
                    );
                    ?>
                </strong>

            </div>


            <div class="inventory-stat blue">

                <span class="inventory-stat-label">
                    Total Stock
                </span>

                <strong class="inventory-stat-value">
                    <?php
                    echo number_format(
                        $stats['total_stock']
                    );
                    ?>
                </strong>

            </div>


            <div class="inventory-stat yellow">

                <span class="inventory-stat-label">
                    Low Stock
                </span>

                <strong class="inventory-stat-value">
                    <?php
                    echo number_format(
                        $stats['low_stock']
                    );
                    ?>
                </strong>

            </div>


            <div class="inventory-stat red">

                <span class="inventory-stat-label">
                    Out of Stock
                </span>

                <strong class="inventory-stat-value">
                    <?php
                    echo number_format(
                        $stats['out_stock']
                    );
                    ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             FILTER
        ================================================== -->

        <section class="inventory-filter">


            <form
                method="GET"
                action="<?php
                echo htmlspecialchars(
                    site_url('admin/inventory.php')
                );
                ?>"
                class="inventory-filter-form"
            >


                <div class="inventory-field">

                    <label>
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>"
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

                        <?php foreach (
                            $vendors
                            as $vendor
                        ): ?>

                            <option
                                value="<?php
                                echo (int)
                                    $vendor['vendor_id'];
                                ?>"
                                <?php
                                echo $vendorFilter ===
                                    (int)
                                    $vendor['vendor_id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $vendor[
                                        'business_name'
                                    ]
                                );
                                ?>

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
                            <?php
                            echo $statusFilter ===
                                'available'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            In Stock
                        </option>

                        <option
                            value="low"
                            <?php
                            echo $statusFilter ===
                                'low'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Low Stock
                        </option>

                        <option
                            value="out"
                            <?php
                            echo $statusFilter ===
                                'out'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Out of Stock
                        </option>

                    </select>

                </div>


                <div>

                    <button
                        type="submit"
                        class="inventory-button"
                    >
                        FILTER
                    </button>

                    <a
                        href="<?php
                        echo site_url(
                            'admin/inventory.php'
                        );
                        ?>"
                        class="inventory-reset"
                    >
                        Reset
                    </a>

                </div>


            </form>


        </section>


        <!-- =================================================
             INVENTORY TABLE
        ================================================== -->

        <section class="inventory-card">


            <div class="inventory-card-header">

                <div>

                    <h2>
                        Product Inventory
                    </h2>

                    <span>
                        <?php
                        echo number_format(
                            count($products)
                        );
                        ?>
                        product(s) found
                    </span>

                </div>

            </div>


            <?php if (empty($products)): ?>


                <div class="inventory-empty">

                    <div class="inventory-empty-icon">
                        #
                    </div>

                    <h3>
                        No products found
                    </h3>

                    <p>
                        Try changing your search or
                        stock filters.
                    </p>

                </div>


            <?php else: ?>


                <div class="inventory-table-wrap">


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
                                    Current Stock
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


                            <?php foreach (
                                $products
                                as $product
                            ): ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div
                                            class="
                                                inventory-product
                                            "
                                        >


                                            <div
                                                class="
                                                    inventory-product-image
                                                "
                                            >

                                                <?php
                                                $image =
                                                    $product[
                                                        'image'
                                                    ] ?? '';
                                                ?>


                                                <?php if (
                                                    $image !== ''
                                                ): ?>

                                                    <img
                                                        src="<?php
                                                        echo htmlspecialchars(
                                                            inventory_product_image(
                                                                $image
                                                            )
                                                        );
                                                        ?>"
                                                        alt="<?php
                                                        echo htmlspecialchars(
                                                            $product[
                                                                'product_name'
                                                            ]
                                                        );
                                                        ?>"
                                                        onerror="
                                                            this.style.display='none';
                                                            this.nextElementSibling.style.display='block';
                                                        "
                                                    >

                                                <?php endif; ?>


                                                <span
                                                    class="
                                                        inventory-product-placeholder
                                                    "
                                                    style="
                                                        <?php
                                                        echo $image !== ''
                                                            ? 'display:none;'
                                                            : '';
                                                        ?>
                                                    "
                                                >
                                                    #
                                                </span>


                                            </div>


                                            <div>

                                                <span
                                                    class="
                                                        inventory-product-name
                                                    "
                                                >

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $product[
                                                            'product_name'
                                                        ]
                                                    );
                                                    ?>

                                                </span>


                                                <span
                                                    class="
                                                        inventory-product-id
                                                    "
                                                >

                                                    ID:
                                                    #<?php
                                                    echo (int)
                                                        $product[
                                                            'product_id'
                                                        ];
                                                    ?>

                                                </span>

                                            </div>


                                        </div>

                                    </td>


                                    <!-- VENDOR -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $product[
                                                'business_name'
                                            ]
                                            ?? 'Unknown Vendor'
                                        );
                                        ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $product[
                                                'category_name'
                                            ]
                                            ?? 'Uncategorized'
                                        );
                                        ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        RM
                                        <?php
                                        echo number_format(
                                            (float)
                                            $product[
                                                'price'
                                            ],
                                            2
                                        );
                                        ?>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <strong
                                            style="
                                                color:#f8fafc;
                                                font-size:11px;
                                            "
                                        >

                                            <?php
                                            echo number_format(
                                                (int)
                                                $product[
                                                    'stock_quantity'
                                                ]
                                            );
                                            ?>

                                        </strong>

                                    </td>


                                    <!-- STOCK STATUS -->

                                    <td>

                                        <span
                                            class="
                                                inventory-stock
                                                <?php
                                                echo inventory_stock_class(
                                                    $product[
                                                        'stock_quantity'
                                                    ]
                                                );
                                                ?>
                                            "
                                        >

                                            <?php
                                            echo inventory_stock_label(
                                                $product[
                                                    'stock_quantity'
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- PRODUCT STATUS -->

                                    <td>

                                        <span
                                            class="
                                                product-status
                                            "
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $product[
                                                    'status'
                                                ]
                                                ?? 'Unknown'
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- UPDATE -->

                                    <td>


                                        <form
                                            method="POST"
                                            class="stock-form"
                                        >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_stock"
                                            >


                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?php
                                                echo (int)
                                                    $product[
                                                        'product_id'
                                                    ];
                                                ?>"
                                            >


                                            <input
                                                type="number"
                                                name="stock_quantity"
                                                class="stock-input"
                                                min="0"
                                                value="<?php
                                                echo (int)
                                                    $product[
                                                        'stock_quantity'
                                                    ];
                                                ?>"
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


<?php
require_once __DIR__ . '/../includes/footer.php';
?>


</body>

</html>