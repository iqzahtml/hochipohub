<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

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
| VARIABLES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$products = [];
$categories = [];

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_product'])
) {

    $productId = (int) (
        $_POST['product_id'] ?? 0
    );

    if ($productId <= 0) {

        $errorMessage =
            'Invalid product selected.';

    } else {

        try {

            $stmt = $db->prepare("
                DELETE FROM products
                WHERE product_id = :product_id
                LIMIT 1
            ");

            $stmt->execute([
                ':product_id' => $productId
            ]);

            if ($stmt->rowCount() > 0) {

                $successMessage =
                    'Product deleted successfully.';

            } else {

                $errorMessage =
                    'Product could not be found.';
            }

        } catch (PDOException $e) {

            $errorMessage = APP_DEBUG
                ? $e->getMessage()
                : 'Unable to delete product.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $productId = (int) (
        $_POST['product_id'] ?? 0
    );

    $newStatus = trim(
        $_POST['status'] ?? ''
    );

    $allowedStatuses = [
        'Available',
        'Unavailable'
    ];

    if ($productId <= 0) {

        $errorMessage =
            'Invalid product selected.';

    } elseif (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $errorMessage =
            'Invalid product status.';

    } else {

        try {

            $stmt = $db->prepare("
                UPDATE products

                SET
                    status = :status

                WHERE product_id = :product_id

                LIMIT 1
            ");

            $stmt->execute([
                ':status' => $newStatus,
                ':product_id' => $productId
            ]);

            $successMessage =
                'Product status updated successfully.';

        } catch (PDOException $e) {

            $errorMessage = APP_DEBUG
                ? $e->getMessage()
                : 'Unable to update product status.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

try {

    $categoryStmt = $db->query("
        SELECT
            category_id,
            category_name

        FROM categories

        ORDER BY category_name ASC
    ");

    $categories =
        $categoryStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $categories = [];

    if (APP_DEBUG) {
        $errorMessage =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| PRODUCT SUMMARY
|--------------------------------------------------------------------------
*/

$totalProducts = 0;
$availableProducts = 0;
$unavailableProducts = 0;
$totalStock = 0;

try {

    $summaryStmt = $db->query("
        SELECT

            COUNT(*) AS total_products,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'Available'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS available_products,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'Unavailable'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS unavailable_products,

            COALESCE(
                SUM(stock_quantity),
                0
            ) AS total_stock

        FROM products
    ");

    $summary =
        $summaryStmt->fetch();

    if ($summary) {

        $totalProducts =
            (int) $summary['total_products'];

        $availableProducts =
            (int) $summary['available_products'];

        $unavailableProducts =
            (int) $summary['unavailable_products'];

        $totalStock =
            (int) $summary['total_stock'];
    }

} catch (PDOException $e) {

    if (APP_DEBUG) {

        $errorMessage =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT

            p.product_id,
            p.product_name,
            p.vendor_id,
            p.category_id,
            p.price,
            p.image,
            p.description,
            p.stock_quantity,
            p.status,
            p.created_at,

            v.business_name AS vendor_name,

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
            AND (
                p.product_name LIKE :search

                OR p.description LIKE :search

                OR v.business_name LIKE :search

                OR c.category_name LIKE :search

                OR CAST(
                    p.product_id
                    AS CHAR
                ) LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY FILTER
    |--------------------------------------------------------------------------
    */

    if ($categoryFilter !== '') {

        $sql .= "
            AND p.category_id = :category_id
        ";

        $params[':category_id'] =
            (int) $categoryFilter;
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $statusFilter !== 'all' &&
        in_array(
            $statusFilter,
            [
                'Available',
                'Unavailable'
            ],
            true
        )
    ) {

        $sql .= "
            AND p.status = :status
        ";

        $params[':status'] =
            $statusFilter;
    }

    $sql .= "
        ORDER BY p.created_at DESC
    ";

    $stmt =
        $db->prepare($sql);

    $stmt->execute(
        $params
    );

    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $products = [];

    $errorMessage = APP_DEBUG
        ? $e->getMessage()
        : 'Unable to load products.';
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
        Product Management |
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
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        .products-page {
            min-height: 100vh;

            padding:
                35px 4%
                60px;

            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(37,99,235,.14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 20%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                #f8fbff;
        }

        .products-container {
            max-width: 1500px;
            margin: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .products-hero {
            position: relative;

            overflow: hidden;

            margin-bottom: 24px;

            padding: 35px;

            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #020617,
                    #172554 35%,
                    #1d4ed8 68%,
                    #0284c7
                );

            color: white;

            box-shadow:
                0 25px 65px
                rgba(29,78,216,.22);
        }

        .products-hero::before {
            content: "";

            position: absolute;

            width: 370px;
            height: 370px;

            top: -220px;
            right: -80px;

            border-radius: 50%;

            background:
                rgba(96,165,250,.14);
        }

        .products-hero::after {
            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            right: 250px;
            bottom: -175px;

            border-radius: 50%;

            background:
                rgba(56,189,248,.09);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;

            color:
                rgba(255,255,255,.62);

            font-size: 9px;
            font-weight: 950;

            letter-spacing: 2px;

            text-transform: uppercase;
        }

        .products-hero h1 {
            margin: 0 0 8px;

            font-size:
                clamp(
                    29px,
                    5vw,
                    46px
                );

            font-weight: 950;
        }

        .products-hero p {
            max-width: 700px;

            margin: 0;

            color:
                rgba(255,255,255,.75);

            font-size: 11px;

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

            gap: 14px;

            margin-bottom: 22px;
        }

        .summary-card {
            padding: 19px;

            border:
                1px solid #dbeafe;

            border-radius: 20px;

            background: white;

            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .summary-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 8px;
            font-weight: 950;

            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;

            font-size: 25px;
            font-weight: 950;
        }

        .summary-value.blue {
            color: #2563eb;
        }

        .summary-value.green {
            color: #16a34a;
        }

        .summary-value.orange {
            color: #d97706;
        }

        .summary-note {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | MESSAGES
        |--------------------------------------------------------------------------
        */

        .message {
            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 12px;

            font-size: 9px;
            font-weight: 850;
        }

        .message.success {
            border:
                1px solid #bbf7d0;

            background: #f0fdf4;

            color: #166534;
        }

        .message.error {
            border:
                1px solid #fecaca;

            background: #fef2f2;

            color: #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN PANEL
        |--------------------------------------------------------------------------
        */

        .products-panel {
            overflow: hidden;

            border:
                1px solid #dbeafe;

            border-radius: 23px;

            background: white;

            box-shadow:
                0 12px 40px
                rgba(15,23,42,.055);
        }

        .panel-header {
            padding: 21px 23px;

            border-bottom:
                1px solid #eff6ff;
        }

        .panel-header h2 {
            margin: 0 0 4px;

            color: #0f172a;

            font-size: 15px;
            font-weight: 950;
        }

        .panel-header p {
            margin: 0;

            color: #94a3b8;

            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER AREA
        |--------------------------------------------------------------------------
        */

        .filter-area {
            padding: 16px 22px;

            border-bottom:
                1px solid #eff6ff;

            background: #fbfdff;
        }

        .filter-form {
            display: grid;

            grid-template-columns:
                1.5fr
                1fr
                1fr
                auto;

            gap: 8px;

            align-items: center;
        }

        .filter-input,
        .filter-select {
            width: 100%;

            padding: 11px 12px;

            border:
                1px solid #dbeafe;

            border-radius: 11px;

            outline: none;

            background: white;

            color: #334155;

            font-size: 9px;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(37,99,235,.06);
        }

        .filter-button {
            padding: 11px 16px;

            border: 0;

            border-radius: 11px;

            cursor: pointer;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0284c7
                );

            color: white;

            font-size: 8px;
            font-weight: 950;
        }

        .clear-filter {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 11px 14px;

            border:
                1px solid #dbeafe;

            border-radius: 11px;

            background: white;

            color: #64748b;

            text-decoration: none;

            font-size: 8px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrap {
            overflow-x: auto;
        }

        .products-table {
            width: 100%;

            min-width: 1250px;

            border-collapse: collapse;
        }

        .products-table th {
            padding: 13px 15px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fbff;

            color: #64748b;

            font-size: 7px;
            font-weight: 950;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: .5px;
        }

        .products-table td {
            padding: 14px 15px;

            border-bottom:
                1px solid #f1f5f9;

            vertical-align: middle;

            color: #334155;

            font-size: 9px;
        }

        .products-table tbody tr {
            transition:
                background .15s ease;
        }

        .products-table tbody tr:hover {
            background: #f8fbff;
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

            min-width: 230px;
        }

        .product-image {
            width: 58px;
            height: 58px;

            flex-shrink: 0;

            overflow: hidden;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #bfdbfe
                );
        }

        .product-image img {
            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }

        .product-placeholder {
            width: 100%;
            height: 100%;

            display: flex;

            align-items: center;
            justify-content: center;

            color: #2563eb;

            font-size: 21px;
        }

        .product-info {
            min-width: 0;
        }

        .product-name {
            margin-bottom: 4px;

            color: #0f172a;

            font-size: 10px;
            font-weight: 950;

            line-height: 1.4;
        }

        .product-id {
            color: #94a3b8;

            font-size: 7px;
            font-weight: 800;
        }

        /*
        |--------------------------------------------------------------------------
        | VENDOR / CATEGORY
        |--------------------------------------------------------------------------
        */

        .vendor-name {
            margin-bottom: 4px;

            color: #334155;

            font-size: 9px;
            font-weight: 850;
        }

        .category-name {
            display: inline-block;

            padding: 5px 8px;

            border-radius: 999px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 7px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | PRICE / STOCK
        |--------------------------------------------------------------------------
        */

        .price {
            color: #1d4ed8;

            font-size: 11px;
            font-weight: 950;
        }

        .stock {
            color: #334155;

            font-size: 10px;
            font-weight: 900;
        }

        .stock.low {
            color: #d97706;
        }

        .stock.empty {
            color: #dc2626;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status-badge {
            display: inline-block;

            padding: 6px 9px;

            border-radius: 999px;

            font-size: 7px;
            font-weight: 950;

            text-transform: uppercase;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-unavailable {
            background: #fee2e2;
            color: #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS FORM
        |--------------------------------------------------------------------------
        */

        .status-form {
            display: flex;

            align-items: center;

            gap: 5px;
        }

        .status-select {
            padding: 7px 8px;

            border:
                1px solid #dbeafe;

            border-radius: 8px;

            outline: none;

            background: white;

            color: #334155;

            font-size: 7px;
            font-weight: 800;
        }

        .save-status {
            padding: 7px 9px;

            border: 0;

            border-radius: 8px;

            cursor: pointer;

            background: #eff6ff;

            color: #2563eb;

            font-size: 7px;
            font-weight: 950;
        }

        .save-status:hover {
            background: #dbeafe;
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        .actions {
            display: flex;

            align-items: center;

            gap: 6px;
        }

        .action-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 7px 9px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 7px;
            font-weight: 950;
        }

        .edit-btn {
            border:
                1px solid #bfdbfe;

            background: #eff6ff;

            color: #2563eb;
        }

        .delete-btn {
            border:
                1px solid #fecaca;

            background: #fef2f2;

            color: #dc2626;

            cursor: pointer;
        }

        .edit-btn:hover {
            background: #dbeafe;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .empty-state {
            padding: 70px 20px;

            text-align: center;
        }

        .empty-icon {
            margin-bottom: 12px;

            font-size: 42px;
        }

        .empty-state strong {
            display: block;

            margin-bottom: 6px;

            color: #334155;

            font-size: 13px;
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

            .filter-form {
                grid-template-columns:
                    1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .products-page {
                padding:
                    25px 15px 50px;
            }

            .products-hero {
                padding: 27px 21px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/navbar.php';
?>


<main class="products-page">

    <div class="products-container">


        <!-- HERO -->

        <section class="products-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    Product Management
                </h1>

                <p>
                    Manage marketplace products,
                    monitor stock, check vendors
                    and control product availability
                    from one place.
                </p>

            </div>

        </section>


        <!-- MESSAGES -->

        <?php if (
            $successMessage !== ''
        ): ?>

            <div class="message success">
                ✓
                <?= e(
                    $successMessage
                ) ?>
            </div>

        <?php endif; ?>


        <?php if (
            $errorMessage !== ''
        ): ?>

            <div class="message error">
                ⚠
                <?= e(
                    $errorMessage
                ) ?>
            </div>

        <?php endif; ?>


        <!-- SUMMARY -->

        <section class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Products
                </div>

                <div
                    class="summary-value blue"
                >
                    <?= number_format(
                        $totalProducts
                    ) ?>
                </div>

                <div class="summary-note">
                    All marketplace products
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Available
                </div>

                <div
                    class="summary-value green"
                >
                    <?= number_format(
                        $availableProducts
                    ) ?>
                </div>

                <div class="summary-note">
                    Currently visible products
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Unavailable
                </div>

                <div
                    class="summary-value orange"
                >
                    <?= number_format(
                        $unavailableProducts
                    ) ?>
                </div>

                <div class="summary-note">
                    Hidden or unavailable
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
                    Units across products
                </div>

            </div>

        </section>


        <!-- PRODUCTS PANEL -->

        <section class="products-panel">


            <div class="panel-header">

                <h2>
                    All Products
                </h2>

                <p>
                    Search, filter and manage
                    marketplace products.
                </p>

            </div>


            <!-- FILTER -->

            <div class="filter-area">

                <form
                    method="GET"
                    class="filter-form"
                >

                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Search product, vendor, category..."
                        value="<?= e(
                            $search
                        ) ?>"
                    >


                    <select
                        name="category"
                        class="filter-select"
                    >

                        <option value="">
                            All Categories
                        </option>

                        <?php foreach (
                            $categories
                            as $category
                        ): ?>

                            <option
                                value="<?= (int) $category['category_id'] ?>"
                                <?= (string) $categoryFilter ===
                                    (string) $category['category_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e(
                                    $category[
                                        'category_name'
                                    ]
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>


                    <select
                        name="status"
                        class="filter-select"
                    >

                        <option
                            value="all"
                            <?= $statusFilter === 'all'
                                ? 'selected'
                                : '' ?>
                        >
                            All Status
                        </option>

                        <option
                            value="Available"
                            <?= $statusFilter === 'Available'
                                ? 'selected'
                                : '' ?>
                        >
                            Available
                        </option>

                        <option
                            value="Unavailable"
                            <?= $statusFilter === 'Unavailable'
                                ? 'selected'
                                : '' ?>
                        >
                            Unavailable
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="filter-button"
                    >
                        FILTER
                    </button>

                </form>


                <?php if (
                    $search !== '' ||
                    $categoryFilter !== '' ||
                    $statusFilter !== 'all'
                ): ?>

                    <div
                        style="
                            margin-top:10px;
                        "
                    >

                        <a
                            href="<?= BASE_URL ?>admin/products.php"
                            class="clear-filter"
                        >
                            Clear Filters
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- PRODUCT TABLE -->

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
                        Try changing your search
                        or filter.
                    </span>

                </div>

            <?php else: ?>


                <div class="table-wrap">

                    <table
                        class="products-table"
                    >

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
                                    Status
                                </th>

                                <th>
                                    Update
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $products
                                as $product
                            ): ?>

                                <?php

                                $status =
                                    $product[
                                        'status'
                                    ] ?? 'Unavailable';

                                $statusClass =
                                    strtolower(
                                        $status
                                    );

                                $stock =
                                    (int) (
                                        $product[
                                            'stock_quantity'
                                        ] ?? 0
                                    );

                                ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div
                                            class="
                                                product-cell
                                            "
                                        >

                                            <div
                                                class="
                                                    product-image
                                                "
                                            >

                                                <?php if (
                                                    !empty(
                                                        $product[
                                                            'image'
                                                        ]
                                                    )
                                                ): ?>

                                                    <img
                                                        src="<?= e(
                                                            productImageUrl(
                                                                $product[
                                                                    'image'
                                                                ]
                                                            )
                                                        ) ?>"
                                                        alt="<?= e(
                                                            $product[
                                                                'product_name'
                                                            ]
                                                        ) ?>"
                                                        onerror="
                                                            this.style.display='none';
                                                            this.nextElementSibling.style.display='flex';
                                                        "
                                                    >

                                                    <div
                                                        class="
                                                            product-placeholder
                                                        "
                                                        style="
                                                            display:none;
                                                        "
                                                    >
                                                        📦
                                                    </div>

                                                <?php else: ?>

                                                    <div
                                                        class="
                                                            product-placeholder
                                                        "
                                                    >
                                                        📦
                                                    </div>

                                                <?php endif; ?>

                                            </div>


                                            <div
                                                class="
                                                    product-info
                                                "
                                            >

                                                <div
                                                    class="
                                                        product-name
                                                    "
                                                >
                                                    <?= e(
                                                        $product[
                                                            'product_name'
                                                        ]
                                                    ) ?>
                                                </div>

                                                <div
                                                    class="
                                                        product-id
                                                    "
                                                >
                                                    Product
                                                    #<?= (int) $product[
                                                        'product_id'
                                                    ] ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- VENDOR -->

                                    <td>

                                        <div
                                            class="
                                                vendor-name
                                            "
                                        >
                                            <?= e(
                                                $product[
                                                    'vendor_name'
                                                ] ??
                                                'Unknown Vendor'
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span
                                            class="
                                                category-name
                                            "
                                        >
                                            <?= e(
                                                $product[
                                                    'category_name'
                                                ] ??
                                                'Uncategorized'
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <span
                                            class="price"
                                        >
                                            <?= formatPrice(
                                                $product[
                                                    'price'
                                                ]
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <?php

                                        $stockClass = '';

                                        if (
                                            $stock <= 0
                                        ) {

                                            $stockClass =
                                                'empty';

                                        } elseif (
                                            $stock <= 5
                                        ) {

                                            $stockClass =
                                                'low';
                                        }

                                        ?>

                                        <span
                                            class="
                                                stock
                                                <?= $stockClass ?>
                                            "
                                        >
                                            <?= number_format(
                                                $stock
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                status-badge
                                                status-<?= e(
                                                    $statusClass
                                                ) ?>
                                            "
                                        >
                                            <?= e(
                                                $status
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- STATUS UPDATE -->

                                    <td>

                                        <form
                                            method="POST"
                                            class="status-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product[
                                                    'product_id'
                                                ] ?>"
                                            >

                                            <select
                                                name="status"
                                                class="
                                                    status-select
                                                "
                                            >

                                                <option
                                                    value="Available"
                                                    <?= $status === 'Available'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Available
                                                </option>

                                                <option
                                                    value="Unavailable"
                                                    <?= $status === 'Unavailable'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Unavailable
                                                </option>

                                            </select>


                                            <button
                                                type="submit"
                                                name="update_status"
                                                value="1"
                                                class="
                                                    save-status
                                                "
                                            >
                                                SAVE
                                            </button>

                                        </form>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div
                                            class="actions"
                                        >

                                            <a
                                                href="<?= BASE_URL ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                                class="
                                                    action-btn
                                                    edit-btn
                                                "
                                            >
                                                VIEW
                                            </a>


                                            <form
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to delete this product?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="product_id"
                                                    value="<?= (int) $product[
                                                        'product_id'
                                                    ] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_product"
                                                    value="1"
                                                    class="
                                                        action-btn
                                                        delete-btn
                                                    "
                                                >
                                                    DELETE
                                                </button>

                                            </form>

                                        </div>

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