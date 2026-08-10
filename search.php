<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$db = getDB();

$keyword = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category'] ?? 0);

$products = [];
$categories = [];
$totalResults = 0;

/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categoryStmt = $db->query("
    SELECT
        category_id,
        category_name,
        category_image
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| SEARCH PRODUCTS
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
        c.category_name,
        v.business_name
    FROM products p

    INNER JOIN categories c
        ON c.category_id = p.category_id

    INNER JOIN vendors v
        ON v.vendor_id = p.vendor_id

    WHERE p.status = 'Available'

    AND v.approval_status = 'Approved'
";

$params = [];

if ($keyword !== '') {

    $sql .= "
        AND (
            p.product_name LIKE :keyword
            OR p.description LIKE :keyword
            OR v.business_name LIKE :keyword
            OR c.category_name LIKE :keyword
        )
    ";

    $params[':keyword'] =
        '%' . $keyword . '%';
}

if ($categoryId > 0) {

    $sql .= "
        AND p.category_id = :category_id
    ";

    $params[':category_id'] =
        $categoryId;
}

$sql .= "
    ORDER BY p.created_at DESC
";

$productStmt = $db->prepare($sql);

$productStmt->execute($params);

$products =
    $productStmt->fetchAll();

$totalResults =
    count($products);

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
        Search | <?= e(APP_NAME) ?>
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

        .search-page {
            min-height: 100vh;
            padding: 45px 5%;
            background:
                radial-gradient(
                    circle at 10% 5%,
                    rgba(37,99,235,.14),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 15%,
                    rgba(14,165,233,.12),
                    transparent 30%
                ),
                #f8fbff;
        }

        .search-container {
            max-width: 1250px;
            margin: auto;
        }

        .search-hero {
            padding: 35px;
            margin-bottom: 28px;
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    #0f3c91,
                    #2563eb 55%,
                    #0284c7
                );
            color: white;
            box-shadow:
                0 25px 55px rgba(37,99,235,.22);
        }

        .search-hero span {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: .8;
        }

        .search-hero h1 {
            margin: 10px 0 8px;
            font-size: clamp(30px, 5vw, 48px);
            font-weight: 950;
        }

        .search-hero p {
            margin: 0 0 25px;
            opacity: .82;
            line-height: 1.6;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 190px 120px;
            gap: 10px;
        }

        .search-input,
        .search-select {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 13px;
            outline: none;
            background: white;
            color: #0f172a;
            font-family: inherit;
        }

        .search-btn {
            border: none;
            border-radius: 13px;
            background: #0f172a;
            color: white;
            font-weight: 900;
            cursor: pointer;
        }

        .search-btn:hover {
            background: #020617;
        }

        .search-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 25px;
        }

        .filter-card {
            align-self: start;
            padding: 22px;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 15px 40px rgba(15,23,42,.06);
        }

        .filter-card h3 {
            margin: 0 0 15px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .category-link {
            display: block;
            padding: 10px 12px;
            margin-bottom: 5px;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            font-weight: 800;
        }

        .category-link:hover,
        .category-link.active {
            background: #eff6ff;
            color: #2563eb;
        }

        .results-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
        }

        .results-top h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 950;
        }

        .result-count {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .product-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .search-product {
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px rgba(15,23,42,.06);
            transition: .2s ease;
        }

        .search-product:hover {
            transform: translateY(-5px);
            box-shadow:
                0 20px 45px rgba(37,99,235,.13);
        }

        .product-image-wrap {
            position: relative;
            height: 210px;
            background: #eff6ff;
        }

        .product-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 9px;
            border-radius: 999px;
            background: rgba(255,255,255,.92);
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 900;
        }

        .product-content {
            padding: 17px;
        }

        .product-content h3 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .vendor-name {
            margin-bottom: 10px;
            color: #64748b;
            font-size: 10px;
        }

        .product-description {
            display: -webkit-box;
            margin-bottom: 15px;
            overflow: hidden;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.6;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .product-price {
            color: #1d4ed8;
            font-size: 17px;
            font-weight: 950;
        }

        .view-btn {
            padding: 9px 12px;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            font-size: 10px;
            font-weight: 900;
        }

        .empty-results {
            padding: 70px 25px;
            border: 1px dashed #bfdbfe;
            border-radius: 20px;
            background: white;
            text-align: center;
        }

        .empty-results .icon {
            font-size: 45px;
            margin-bottom: 10px;
        }

        .empty-results h3 {
            margin: 0 0 7px;
            color: #0f172a;
        }

        .empty-results p {
            margin: 0;
            color: #94a3b8;
            font-size: 12px;
        }

        @media (max-width: 1000px) {

            .product-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 800px) {

            .search-layout {
                grid-template-columns: 1fr;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 550px) {

            .search-page {
                padding: 25px 15px;
            }

            .search-hero {
                padding: 25px 20px;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="search-page">

    <div class="search-container">

        <section class="search-hero">

            <span>
                HochipoHub Discovery
            </span>

            <h1>
                Find Your Next Favourite.
            </h1>

            <p>
                Search products, categories and vendors
                across HochipoHub.
            </p>

            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="search"
                    name="q"
                    class="search-input"
                    placeholder="Search products, vendors..."
                    value="<?= e($keyword) ?>"
                >

                <select
                    name="category"
                    class="search-select"
                >

                    <option value="0">
                        All Categories
                    </option>

                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?= (int) $category['category_id'] ?>"
                            <?= $categoryId ===
                                (int) $category['category_id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e(
                                $category['category_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button
                    type="submit"
                    class="search-btn"
                >
                    Search
                </button>

            </form>

        </section>


        <div class="search-layout">

            <aside class="filter-card">

                <h3>
                    Categories
                </h3>

                <a
                    href="<?= BASE_URL ?>search.php<?= $keyword !== '' ? '?q=' . urlencode($keyword) : '' ?>"
                    class="category-link <?= $categoryId === 0 ? 'active' : '' ?>"
                >
                    All Products
                </a>

                <?php foreach (
                    $categories
                    as $category
                ): ?>

                    <a
                        href="<?= BASE_URL ?>search.php?q=<?= urlencode($keyword) ?>&category=<?= (int) $category['category_id'] ?>"
                        class="category-link <?= $categoryId === (int) $category['category_id'] ? 'active' : '' ?>"
                    >
                        <?= e(
                            $category['category_name']
                        ) ?>
                    </a>

                <?php endforeach; ?>

            </aside>


            <section>

                <div class="results-top">

                    <h2>
                        <?= $keyword !== ''
                            ? 'Search Results'
                            : 'Discover Products' ?>
                    </h2>

                    <span class="result-count">
                        <?= $totalResults ?>
                        product<?= $totalResults !== 1 ? 's' : '' ?>
                    </span>

                </div>


                <?php if (
                    empty($products)
                ): ?>

                    <div class="empty-results">

                        <div class="icon">
                            🔎
                        </div>

                        <h3>
                            No products found
                        </h3>

                        <p>
                            Try another keyword or category.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="product-grid">

                        <?php foreach (
                            $products
                            as $product
                        ): ?>

                            <article class="search-product">

                                <div class="product-image-wrap">

                                    <img
                                        src="<?= e(
                                            productImageUrl(
                                                $product['image']
                                            )
                                        ) ?>"
                                        alt="<?= e(
                                            $product['product_name']
                                        ) ?>"
                                        onerror="this.src='<?= e(
                                            BASE_URL .
                                            'image/product/default-product.jpg'
                                        ) ?>'"
                                    >

                                    <span
                                        class="category-badge"
                                    >
                                        <?= e(
                                            $product['category_name']
                                        ) ?>
                                    </span>

                                </div>


                                <div class="product-content">

                                    <h3>
                                        <?= e(
                                            $product['product_name']
                                        ) ?>
                                    </h3>

                                    <div class="vendor-name">
                                        by
                                        <strong>
                                            <?= e(
                                                $product['business_name']
                                            ) ?>
                                        </strong>
                                    </div>

                                    <div class="product-description">
                                        <?= e(
                                            $product['description']
                                            ?: 'No description available.'
                                        ) ?>
                                    </div>


                                    <div class="product-bottom">

                                        <span class="product-price">
                                            <?= formatPrice(
                                                $product['price']
                                            ) ?>
                                        </span>

                                        <a
                                            href="<?= BASE_URL ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                            class="view-btn"
                                        >
                                            View Product
                                        </a>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>

</html>