<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$stockFilter = $_GET['stock'] ?? 'all';

/*
|--------------------------------------------------------------------------
| INVENTORY QUERY
|--------------------------------------------------------------------------
|
| Product inventory is displayed using the products table.
|
*/

$products = [];

try {

    $sql = "
        SELECT
            p.*
        FROM products p
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
            AND (
                p.product_name LIKE :search
                OR p.product_id LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK FILTER
    |--------------------------------------------------------------------------
    */

    if ($stockFilter === 'out') {

        $sql .= "
            AND p.stock <= 0
        ";

    } elseif ($stockFilter === 'low') {

        $sql .= "
            AND p.stock > 0
            AND p.stock <= 10
        ";

    } elseif ($stockFilter === 'available') {

        $sql .= "
            AND p.stock > 10
        ";
    }

    $sql .= "
        ORDER BY p.stock ASC, p.product_id DESC
    ";

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $products =
        $stmt->fetchAll();

} catch (Throwable $e) {

    $products = [];

    if (APP_DEBUG) {
        $inventoryError =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| INVENTORY SUMMARY
|--------------------------------------------------------------------------
*/

$totalProducts = 0;
$totalStock = 0;
$outOfStock = 0;
$lowStock = 0;
$stockValue = 0;

try {

    $summaryStmt = $db->query("
        SELECT
            COUNT(*) AS total_products,
            COALESCE(
                SUM(stock),
                0
            ) AS total_stock,
            COALESCE(
                SUM(
                    CASE
                        WHEN stock <= 0
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS out_of_stock,
            COALESCE(
                SUM(
                    CASE
                        WHEN stock > 0
                        AND stock <= 10
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS low_stock,
            COALESCE(
                SUM(
                    stock * price
                ),
                0
            ) AS stock_value
        FROM products
    ");

    $summary =
        $summaryStmt->fetch();

    if ($summary) {

        $totalProducts =
            (int) $summary['total_products'];

        $totalStock =
            (int) $summary['total_stock'];

        $outOfStock =
            (int) $summary['out_of_stock'];

        $lowStock =
            (int) $summary['low_stock'];

        $stockValue =
            (float) $summary['stock_value'];
    }

} catch (Throwable $e) {

    $totalProducts = 0;
    $totalStock = 0;
    $outOfStock = 0;
    $lowStock = 0;
    $stockValue = 0;
}

/*
|--------------------------------------------------------------------------
| PAGE URL
|--------------------------------------------------------------------------
*/

$currentQuery = '';

if ($search !== '') {
    $currentQuery .=
        '&search=' .
        urlencode($search);
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
        Inventory Management |
        <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/product.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .inventory-page {
            min-height: 100vh;
            padding: 35px 4%;
            background:
                radial-gradient(
                    circle at 8% 5%,
                    rgba(37,99,235,.12),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 12%,
                    rgba(14,165,233,.10),
                    transparent 25%
                ),
                #f8fbff;
        }

        .inventory-container {
            max-width: 1450px;
            margin: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .inventory-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            margin-bottom: 24px;
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    #071f4d,
                    #1d4ed8 55%,
                    #0284c7
                );
            color: white;
            box-shadow:
                0 25px 60px
                rgba(29,78,216,.22);
        }

        .inventory-hero::before {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            top: -190px;
            right: -80px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.08);
        }

        .inventory-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            bottom: -120px;
            right: 250px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.05);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;
            color:
                rgba(255,255,255,.65);
            font-size: 10px;
            font-weight: 950;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .inventory-hero h1 {
            margin: 0 0 8px;
            font-size: clamp(
                28px,
                5vw,
                44px
            );
            font-weight: 950;
        }

        .inventory-hero p {
            max-width: 720px;
            margin: 0;
            color:
                rgba(255,255,255,.76);
            font-size: 12px;
            line-height: 1.7;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 22px;
        }

        .summary-card {
            position: relative;
            overflow: hidden;
            padding: 21px;
            border:
                1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .summary-card::after {
            content: "";
            position: absolute;
            width: 80px;
            height: 80px;
            right: -35px;
            bottom: -35px;
            border-radius: 50%;
            background:
                rgba(37,99,235,.06);
        }

        .summary-label {
            margin-bottom: 9px;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            font-size: 27px;
            font-weight: 950;
        }

        .summary-value.blue {
            color: #2563eb;
        }

        .summary-value.orange {
            color: #d97706;
        }

        .summary-value.red {
            color: #dc2626;
        }

        .summary-note {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN PANEL
        |--------------------------------------------------------------------------
        */

        .inventory-panel {
            overflow: hidden;
            border:
                1px solid #dbeafe;
            border-radius: 22px;
            background: white;
            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .panel-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 20px 22px;
            border-bottom:
                1px solid #eff6ff;
        }

        .panel-title h2 {
            margin: 0 0 4px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .panel-title p {
            margin: 0;
            color: #94a3b8;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        .filter-area {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 22px;
            border-bottom:
                1px solid #eff6ff;
            background: #fbfdff;
        }

        .search-form {
            display: flex;
            flex: 1;
            max-width: 500px;
            gap: 8px;
        }

        .search-input {
            flex: 1;
            min-width: 0;
            padding: 12px 14px;
            border:
                1px solid #dbeafe;
            border-radius: 12px;
            outline: none;
            background: white;
            color: #0f172a;
            font-size: 10px;
        }

        .search-input:focus {
            border-color:
                #2563eb;
            box-shadow:
                0 0 0 4px
                rgba(37,99,235,.07);
        }

        .search-btn {
            padding: 12px 16px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            background:
                #2563eb;
            color: white;
            font-size: 9px;
            font-weight: 900;
        }

        .clear-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px;
            border:
                1px solid #dbeafe;
            border-radius: 12px;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-size: 9px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TABS
        |--------------------------------------------------------------------------
        */

        .filter-tabs {
            display: flex;
            gap: 7px;
            padding: 0 22px 18px;
            background: #fbfdff;
        }

        .filter-tab {
            padding: 8px 12px;
            border:
                1px solid #dbeafe;
            border-radius: 999px;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            transition: .2s ease;
        }

        .filter-tab:hover {
            border-color:
                #93c5fd;
            color: #2563eb;
        }

        .filter-tab.active {
            border-color:
                #2563eb;
            background:
                #2563eb;
            color: white;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrap {
            overflow-x: auto;
        }

        .inventory-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .inventory-table th {
            padding:
                13px 16px;
            border-bottom:
                1px solid #e2e8f0;
            background: #f8fbff;
            color: #64748b;
            font-size: 8px;
            font-weight: 950;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .inventory-table td {
            padding:
                14px 16px;
            border-bottom:
                1px solid #f1f5f9;
            color: #334155;
            font-size: 9px;
            vertical-align: middle;
        }

        .inventory-table tbody tr {
            transition: .15s ease;
        }

        .inventory-table tbody tr:hover {
            background:
                #f8fbff;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        */

        .product-cell {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 220px;
        }

        .product-image {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 12px;
            border:
                1px solid #dbeafe;
            background:
                #eff6ff;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info strong {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 10px;
            font-weight: 950;
        }

        .product-info span {
            color: #94a3b8;
            font-size: 8px;
        }

        .price {
            color: #1d4ed8;
            font-weight: 950;
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK
        |--------------------------------------------------------------------------
        */

        .stock-number {
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .stock-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 7px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .stock-badge.available {
            background:
                #dcfce7;
            color:
                #166534;
        }

        .stock-badge.low {
            background:
                #fef3c7;
            color:
                #92400e;
        }

        .stock-badge.out {
            background:
                #fee2e2;
            color:
                #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK BAR
        |--------------------------------------------------------------------------
        */

        .stock-progress {
            width: 100px;
            height: 6px;
            overflow: hidden;
            margin-top: 7px;
            border-radius: 999px;
            background:
                #e2e8f0;
        }

        .stock-progress span {
            display: block;
            height: 100%;
            border-radius: 999px;
        }

        .progress-good {
            background:
                #22c55e;
        }

        .progress-low {
            background:
                #f59e0b;
        }

        .progress-out {
            background:
                #ef4444;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-icon {
            margin-bottom: 12px;
            font-size: 38px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 12px;
            font-weight: 950;
        }

        .empty-state span {
            color: #94a3b8;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1050px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 700px) {

            .inventory-page {
                padding: 25px 15px;
            }

            .inventory-hero {
                padding: 25px 20px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .panel-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .filter-area {
                align-items: stretch;
                flex-direction: column;
            }

            .search-form {
                max-width: none;
            }

            .filter-tabs {
                overflow-x: auto;
            }

        }

    </style>

</head>

<body>

<?php
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<main class="inventory-page">

    <div class="inventory-container">

        <!-- HERO -->

        <section class="inventory-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    Inventory Control
                </h1>

                <p>
                    Monitor product stock across
                    the marketplace and quickly
                    identify products that need
                    attention.
                </p>

            </div>

        </section>


        <!-- SUMMARY -->

        <section class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Total Products
                </div>

                <div class="summary-value blue">
                    <?= number_format(
                        $totalProducts
                    ) ?>
                </div>

                <div class="summary-note">
                    Active marketplace products
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Stock
                </div>

                <div class="summary-value">
                    <?= number_format(
                        $totalStock
                    ) ?>
                </div>

                <div class="summary-note">
                    Units currently listed
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Low Stock
                </div>

                <div class="summary-value orange">
                    <?= number_format(
                        $lowStock
                    ) ?>
                </div>

                <div class="summary-note">
                    1–10 units remaining
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Out of Stock
                </div>

                <div class="summary-value red">
                    <?= number_format(
                        $outOfStock
                    ) ?>
                </div>

                <div class="summary-note">
                    Products requiring restock
                </div>

            </div>

        </section>


        <!-- INVENTORY -->

        <section class="inventory-panel">

            <div class="panel-top">

                <div class="panel-title">

                    <h2>
                        Product Inventory
                    </h2>

                    <p>
                        Manage and monitor
                        marketplace stock levels.
                    </p>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="filter-area">

                <form
                    method="GET"
                    class="search-form"
                >

                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search product..."
                        value="<?= e(
                            $search
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="stock"
                        value="<?= e(
                            $stockFilter
                        ) ?>"
                    >

                    <button
                        type="submit"
                        class="search-btn"
                    >
                        SEARCH
                    </button>

                </form>


                <?php if (
                    $search !== ''
                ): ?>

                    <a
                        href="<?= BASE_URL ?>admin/inventory.php"
                        class="clear-btn"
                    >
                        Clear
                    </a>

                <?php endif; ?>

            </div>


            <!-- FILTERS -->

            <div class="filter-tabs">

                <a
                    href="?stock=all<?= $currentQuery ?>"
                    class="filter-tab
                    <?= $stockFilter === 'all'
                        ? 'active'
                        : '' ?>"
                >
                    All Products
                </a>

                <a
                    href="?stock=available<?= $currentQuery ?>"
                    class="filter-tab
                    <?= $stockFilter === 'available'
                        ? 'active'
                        : '' ?>"
                >
                    Available
                </a>

                <a
                    href="?stock=low<?= $currentQuery ?>"
                    class="filter-tab
                    <?= $stockFilter === 'low'
                        ? 'active'
                        : '' ?>"
                >
                    Low Stock
                </a>

                <a
                    href="?stock=out<?= $currentQuery ?>"
                    class="filter-tab
                    <?= $stockFilter === 'out'
                        ? 'active'
                        : '' ?>"
                >
                    Out of Stock
                </a>

            </div>


            <?php if (
                isset($inventoryError)
                &&
                $inventoryError !== ''
            ): ?>

                <div
                    style="
                        margin:20px;
                        padding:15px;
                        border-radius:12px;
                        background:#fef2f2;
                        border:1px solid #fecaca;
                        color:#991b1b;
                        font-size:10px;
                    "
                >
                    <?= e(
                        $inventoryError
                    ) ?>
                </div>

            <?php endif; ?>


            <?php if (
                empty($products)
            ): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <strong>
                        No products found
                    </strong>

                    <span>
                        There are no products matching
                        the current inventory filter.
                    </span>

                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table
                        class="inventory-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th>
                                    Stock Level
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $products
                                as $product
                            ): ?>

                                <?php

                                $stock =
                                    (int) (
                                        $product['stock']
                                        ?? 0
                                    );

                                $price =
                                    (float) (
                                        $product['price']
                                        ?? 0
                                    );

                                /*
                                |--------------------------------------------------------------------------
                                | Product image
                                |--------------------------------------------------------------------------
                                */

                                $image =
                                    $product['image']
                                    ?? $product[
                                        'product_image'
                                    ]
                                    ?? '';

                                $imageUrl =
                                    productImageUrl(
                                        $image
                                    );

                                /*
                                |--------------------------------------------------------------------------
                                | Stock status
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $stock <= 0
                                ) {

                                    $stockStatus =
                                        'out';

                                    $stockLabel =
                                        'Out of Stock';

                                } elseif (
                                    $stock <= 10
                                ) {

                                    $stockStatus =
                                        'low';

                                    $stockLabel =
                                        'Low Stock';

                                } else {

                                    $stockStatus =
                                        'available';

                                    $stockLabel =
                                        'Available';
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | Progress
                                |--------------------------------------------------------------------------
                                */

                                $progress =
                                    min(
                                        100,
                                        max(
                                            4,
                                            $stock
                                        )
                                    );

                                ?>

                                <tr>

                                    <!-- PRODUCT -->

                                    <td>

                                        <div
                                            class="product-cell"
                                        >

                                            <div
                                                class="product-image"
                                            >

                                                <img
                                                    src="<?= e(
                                                        $imageUrl
                                                    ) ?>"
                                                    alt="<?= e(
                                                        $product[
                                                            'product_name'
                                                        ]
                                                        ?? 'Product'
                                                    ) ?>"
                                                    onerror="
                                                        this.src='<?= e(
                                                            productImageUrl(
                                                                ''
                                                            )
                                                        ) ?>';
                                                    "
                                                >

                                            </div>


                                            <div
                                                class="product-info"
                                            >

                                                <strong>
                                                    <?= e(
                                                        $product[
                                                            'product_name'
                                                        ]
                                                        ?? 'Unnamed Product'
                                                    ) ?>
                                                </strong>

                                                <span>
                                                    Product ID:
                                                    #<?= e(
                                                        $product[
                                                            'product_id'
                                                        ]
                                                        ?? '-'
                                                    ) ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <span
                                            class="price"
                                        >
                                            <?= formatPrice(
                                                $price
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- STOCK NUMBER -->

                                    <td>

                                        <div
                                            class="stock-number"
                                        >
                                            <?= number_format(
                                                $stock
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- PROGRESS -->

                                    <td>

                                        <div
                                            class="stock-progress"
                                        >

                                            <span
                                                class="progress-<?= e(
                                                    $stockStatus
                                                ) ?>"
                                                style="
                                                    width:
                                                    <?= $progress ?>%;
                                                "
                                            ></span>

                                        </div>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="stock-badge
                                            <?= e(
                                                $stockStatus
                                            ) ?>"
                                        >
                                            <?= e(
                                                $stockLabel
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

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>

</body>

</html>