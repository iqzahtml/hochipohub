<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| PRODUCT PAGE
|--------------------------------------------------------------------------
| Displays all available products.
| Supports:
| - Search
| - Category filter
| - Price sorting
| - Product status
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$sort = $_GET['sort'] ?? 'latest';

$where = [
    "p.status = 'Available'"
];

$params = [];

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            p.product_name LIKE :search
            OR p.description LIKE :search
            OR v.business_name LIKE :search
            OR c.category_name LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}

/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    $where[] = "p.category_id = :category_id";

    $params[':category_id'] = $categoryId;
}

/*
|--------------------------------------------------------------------------
| SORTING
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'price_low':
        $orderBy = 'p.price ASC';
        break;

    case 'price_high':
        $orderBy = 'p.price DESC';
        break;

    case 'name':
        $orderBy = 'p.product_name ASC';
        break;

    case 'popular':
        $orderBy = 'p.product_id DESC';
        break;

    default:
        $orderBy = 'p.created_at DESC';
        break;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.product_id,
        p.vendor_id,
        p.category_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        p.created_at,

        v.business_name,
        v.business_logo,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE " . implode(' AND ', $where) . "

    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categoryStmt = $db->query("
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function productPageImage(?string $image): string
{
    if (empty($image)) {
        return BASE_URL . 'image/product/default-product.jpg';
    }

    if (
        str_contains($image, '/') ||
        str_contains($image, '\\')
    ) {
        return BASE_URL . ltrim(
            str_replace('\\', '/', $image),
            '/'
        );
    }

    return PRODUCT_IMAGE_URL . rawurlencode($image);
}

function productPageVendorImage(?string $image): string
{
    if (empty($image)) {
        return BASE_URL . 'image/vendors/default-vendor.jpg';
    }

    if (
        str_contains($image, '/') ||
        str_contains($image, '\\')
    ) {
        return BASE_URL . ltrim(
            str_replace('\\', '/', $image),
            '/'
        );
    }

    return VENDOR_IMAGE_URL . rawurlencode($image);
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
        Products | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
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

        .product-page {
            min-height: 100vh;
            padding: 40px 5%;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(37, 99, 235, 0.16),
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(14, 165, 233, 0.12),
                    transparent 35%
                );
        }

        .product-hero {
            position: relative;
            overflow: hidden;
            padding: 45px;
            margin-bottom: 35px;
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    #0f172a,
                    #172554 55%,
                    #1d4ed8
                );
            color: #ffffff;
            box-shadow:
                0 25px 60px rgba(15, 23, 42, 0.25);
        }

        .product-hero::before {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -70px;
            top: -80px;
            border-radius: 50%;
            background: rgba(96, 165, 250, 0.25);
        }

        .product-hero::after {
            content: "";
            position: absolute;
            width: 140px;
            height: 140px;
            right: 150px;
            bottom: -80px;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.15);
        }

        .product-hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
        }

        .product-hero-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .product-hero h1 {
            margin: 0 0 12px;
            font-size: clamp(32px, 5vw, 58px);
            line-height: 1;
            font-weight: 900;
        }

        .product-hero p {
            margin: 0;
            max-width: 600px;
            color: #bfdbfe;
            font-size: 16px;
            line-height: 1.7;
        }

        .product-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .product-search {
            display: flex;
            flex: 1;
            min-width: 260px;
            max-width: 600px;
            gap: 10px;
        }

        .product-search input {
            width: 100%;
            padding: 15px 18px;
            border: 1px solid #dbeafe;
            border-radius: 15px;
            outline: none;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .product-search input:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 4px rgba(37, 99, 235, 0.10);
        }

        .product-search button {
            padding: 0 22px;
            border: none;
            border-radius: 15px;
            background: #2563eb;
            color: #ffffff;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .product-search button:hover {
            transform: translateY(-2px);
            background: #1d4ed8;
        }

        .product-sort {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .product-sort select {
            padding: 14px 18px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #ffffff;
            color: #0f172a;
            font-weight: 700;
            outline: none;
        }

        .category-bar {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 8px;
            margin-bottom: 30px;
            scrollbar-width: thin;
        }

        .category-chip {
            flex-shrink: 0;
            padding: 11px 17px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            transition: 0.2s ease;
        }

        .category-chip:hover,
        .category-chip.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .product-result-info {
            margin-bottom: 20px;
            color: #64748b;
            font-size: 14px;
            font-weight: 700;
        }

        .products-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fill, minmax(245px, 1fr));
            gap: 25px;
        }

        .product-card {
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background: #ffffff;
            box-shadow:
                0 12px 30px rgba(15, 23, 42, 0.07);
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow:
                0 25px 50px rgba(37, 99, 235, 0.16);
        }

        .product-image-wrap {
            position: relative;
            height: 230px;
            overflow: hidden;
            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.06);
        }

        .product-category {
            position: absolute;
            left: 14px;
            top: 14px;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.78);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            backdrop-filter: blur(8px);
        }

        .product-stock {
            position: absolute;
            right: 14px;
            top: 14px;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(255,255,255,0.92);
            color: #166534;
            font-size: 11px;
            font-weight: 900;
        }

        .product-stock.empty {
            color: #b91c1c;
        }

        .product-content {
            padding: 20px;
        }

        .product-vendor {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .vendor-mini-logo {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dbeafe;
        }

        .product-name {
            display: -webkit-box;
            overflow: hidden;
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 18px;
            line-height: 1.35;
            font-weight: 900;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-description {
            display: -webkit-box;
            overflow: hidden;
            min-height: 40px;
            margin-bottom: 17px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .product-price {
            color: #1d4ed8;
            font-size: 21px;
            font-weight: 950;
        }

        .product-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1d4ed8;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            transition: 0.2s ease;
        }

        .product-view-btn:hover {
            background: #2563eb;
            color: #ffffff;
        }

        .empty-products {
            grid-column: 1 / -1;
            padding: 70px 20px;
            text-align: center;
            border: 2px dashed #bfdbfe;
            border-radius: 25px;
            background: #f8fbff;
        }

        .empty-products-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .empty-products h3 {
            margin: 0 0 8px;
            color: #0f172a;
        }

        .empty-products p {
            margin: 0;
            color: #64748b;
        }

        @media (max-width: 700px) {

            .product-page {
                padding: 25px 18px;
            }

            .product-hero {
                padding: 30px 24px;
                border-radius: 22px;
            }

            .product-toolbar {
                align-items: stretch;
            }

            .product-search {
                max-width: none;
            }

            .product-sort {
                width: 100%;
            }

            .product-sort select {
                width: 100%;
            }

            .products-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .product-image-wrap {
                height: 180px;
            }

            .product-content {
                padding: 15px;
            }

            .product-name {
                font-size: 15px;
            }

            .product-price {
                font-size: 17px;
            }

            .product-view-btn {
                padding: 8px 10px;
            }
        }

        @media (max-width: 430px) {

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-image-wrap {
                height: 230px;
            }

            .product-search {
                flex-direction: column;
            }

            .product-search button {
                min-height: 45px;
            }
        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="product-page">

    <section class="product-hero">

        <div class="product-hero-content">

            <div class="product-hero-badge">
                ✦ HOCHIPOHUB MARKETPLACE
            </div>

            <h1>
                Discover Your Next Find.
            </h1>

            <p>
                Explore products from local vendors,
                discover unique items and shop everything
                in one place.
            </p>

        </div>

    </section>


    <!-- SEARCH + SORT -->

    <section class="product-toolbar">

        <form
            method="GET"
            action="<?= BASE_URL ?>product.php"
            class="product-search"
        >

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search products, vendors or categories..."
            >

            <?php if ($categoryId > 0): ?>

                <input
                    type="hidden"
                    name="category"
                    value="<?= $categoryId ?>"
                >

            <?php endif; ?>

            <button type="submit">
                Search
            </button>

        </form>


        <form
            method="GET"
            action="<?= BASE_URL ?>product.php"
            class="product-sort"
        >

            <?php if ($search !== ''): ?>

                <input
                    type="hidden"
                    name="search"
                    value="<?= e($search) ?>"
                >

            <?php endif; ?>

            <?php if ($categoryId > 0): ?>

                <input
                    type="hidden"
                    name="category"
                    value="<?= $categoryId ?>"
                >

            <?php endif; ?>

            <select
                name="sort"
                onchange="this.form.submit()"
            >

                <option
                    value="latest"
                    <?= $sort === 'latest' ? 'selected' : '' ?>
                >
                    Latest
                </option>

                <option
                    value="popular"
                    <?= $sort === 'popular' ? 'selected' : '' ?>
                >
                    Popular
                </option>

                <option
                    value="price_low"
                    <?= $sort === 'price_low' ? 'selected' : '' ?>
                >
                    Price: Low to High
                </option>

                <option
                    value="price_high"
                    <?= $sort === 'price_high' ? 'selected' : '' ?>
                >
                    Price: High to Low
                </option>

                <option
                    value="name"
                    <?= $sort === 'name' ? 'selected' : '' ?>
                >
                    Name: A-Z
                </option>

            </select>

        </form>

    </section>


    <!-- CATEGORY FILTER -->

    <div class="category-bar">

        <a
            href="<?= BASE_URL ?>product.php"
            class="category-chip <?= $categoryId === 0 ? 'active' : '' ?>"
        >
            All Products
        </a>

        <?php foreach ($categories as $category): ?>

            <a
                href="<?= BASE_URL ?>product.php?category=<?= (int) $category['category_id'] ?>"
                class="category-chip <?= $categoryId === (int) $category['category_id'] ? 'active' : '' ?>"
            >
                <?= e($category['category_name']) ?>
            </a>

        <?php endforeach; ?>

    </div>


    <!-- RESULT COUNT -->

    <div class="product-result-info">

        <?= count($products) ?>
        product<?= count($products) !== 1 ? 's' : '' ?>
        found

        <?php if ($search !== ''): ?>

            for
            "<strong><?= e($search) ?></strong>"

        <?php endif; ?>

    </div>


    <!-- PRODUCTS -->

    <section class="products-grid">

        <?php if (empty($products)): ?>

            <div class="empty-products">

                <div class="empty-products-icon">
                    🔎
                </div>

                <h3>
                    No products found
                </h3>

                <p>
                    Try another search keyword or category.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($products as $product): ?>

                <?php
                $stock = (int) $product['stock_quantity'];
                $productImage = productPageImage(
                    $product['image']
                );

                $vendorImage = productPageVendorImage(
                    $product['business_logo']
                );
                ?>

                <article class="product-card">

                    <div class="product-image-wrap">

                        <img
                            src="<?= e($productImage) ?>"
                            alt="<?= e($product['product_name']) ?>"
                            class="product-image"
                            loading="lazy"
                            onerror="this.src='<?= e(BASE_URL . 'image/product/default-product.jpg') ?>'"
                        >

                        <span class="product-category">
                            <?= e($product['category_name']) ?>
                        </span>

                        <?php if ($stock > 0): ?>

                            <span class="product-stock">
                                <?= $stock ?> left
                            </span>

                        <?php else: ?>

                            <span class="product-stock empty">
                                Out of stock
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="product-content">

                        <div class="product-vendor">

                            <img
                                src="<?= e($vendorImage) ?>"
                                alt="<?= e($product['business_name']) ?>"
                                class="vendor-mini-logo"
                                onerror="this.src='<?= e(BASE_URL . 'image/vendors/default-vendor.jpg') ?>'"
                            >

                            <span>
                                <?= e($product['business_name']) ?>
                            </span>

                        </div>


                        <h2 class="product-name">
                            <?= e($product['product_name']) ?>
                        </h2>


                        <div class="product-description">

                            <?= e(
                                $product['description']
                                    ?: 'No description available.'
                            ) ?>

                        </div>


                        <div class="product-bottom">

                            <div class="product-price">
                                <?= formatPrice($product['price']) ?>
                            </div>

                            <a
                                href="<?= BASE_URL ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                class="product-view-btn"
                            >
                                View Product →
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>


<script src="<?= BASE_URL ?>js/script.js"></script>

</body>

</html>