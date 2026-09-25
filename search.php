<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM PRODUCT SEARCH
|--------------------------------------------------------------------------
| File: search.php
|
| Features:
| - Product keyword search
| - Category filter
| - Vendor filter
| - Approved vendors only
| - Premium responsive UI
| - Product cards
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('searchE')) {
    function searchE($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('searchProductImage')) {
    function searchProductImage($image): string
    {
        if (function_exists('getProductImage')) {
            return getProductImage($image);
        }

        $image = trim((string) $image);

        if ($image === '') {
            return 'image/logo.jpeg';
        }

        if (
            str_starts_with($image, 'http://') ||
            str_starts_with($image, 'https://') ||
            str_starts_with($image, 'uploads/')
        ) {
            return $image;
        }

        return 'uploads/products/' . rawurlencode(basename($image));
    }
}

if (!function_exists('searchStockText')) {
    function searchStockText($stock): string
    {
        $stock = (int) $stock;

        if ($stock <= 0) {
            return 'Out of Stock';
        }

        if ($stock <= 5) {
            return 'Low Stock';
        }

        return 'In Stock';
    }
}

if (!function_exists('searchStockClass')) {
    function searchStockClass($stock): string
    {
        $stock = (int) $stock;

        if ($stock <= 0) {
            return 'out';
        }

        if ($stock <= 5) {
            return 'low';
        }

        return 'in';
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH PARAMETERS
|--------------------------------------------------------------------------
*/

$query = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);
$vendorId = (int) ($_GET['vendor'] ?? 0);

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| GET APPROVED VENDORS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        vendor_id,
        business_name
    FROM vendors
    WHERE approval_status = 'Approved'
    ORDER BY business_name ASC
");

$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| SEARCH PRODUCTS
|--------------------------------------------------------------------------
*/

$products = [];

if (
    $query !== '' ||
    $categoryId > 0 ||
    $vendorId > 0
) {
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
            v.vendor_id,
            v.business_name,
            c.category_id,
            c.category_name
        FROM products p
        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id
        INNER JOIN categories c
            ON p.category_id = c.category_id
        WHERE p.status != 'Hidden'
          AND v.approval_status = 'Approved'
    ";

    $params = [];

    if ($query !== '') {
        $sql .= "
            AND (
                p.product_name LIKE ?
                OR p.description LIKE ?
                OR v.business_name LIKE ?
                OR c.category_name LIKE ?
            )
        ";

        $searchTerm = '%' . $query . '%';

        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($categoryId > 0) {
        $sql .= " AND p.category_id = ? ";
        $params[] = $categoryId;
    }

    if ($vendorId > 0) {
        $sql .= " AND p.vendor_id = ? ";
        $params[] = $vendorId;
    }

    $sql .= " ORDER BY p.created_at DESC ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle = 'Search - ' . SITE_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>

<style>
* {
    box-sizing: border-box;
}

.hh-search-page {
    min-height: 100vh;
    padding: 0 24px 80px;
    background:
        radial-gradient(circle at 88% 8%, rgba(47,118,255,.12), transparent 26%),
        radial-gradient(circle at 8% 30%, rgba(93,156,255,.08), transparent 22%),
        linear-gradient(180deg, #f5f9ff 0%, #f8fbff 48%, #ffffff 100%);
    color: #172b4d;
    font-family: Inter, Arial, sans-serif;
}

.hh-search-shell {
    width: 100%;
    max-width: 1370px;
    margin: 0 auto;
}

.hh-search-hero {
    position: relative;
    overflow: hidden;
    margin: 0 0 26px;
    padding: 48px 46px;
    border-radius: 0 0 32px 32px;
    background:
        linear-gradient(135deg, #123d8d 0%, #2468ed 55%, #5a92ff 100%);
    box-shadow: 0 22px 55px rgba(35, 91, 190, .18);
    color: #fff;
}

.hh-search-hero::before,
.hh-search-hero::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    background: rgba(255,255,255,.09);
}

.hh-search-hero::before {
    width: 280px;
    height: 280px;
    right: -80px;
    top: -130px;
}

.hh-search-hero::after {
    width: 180px;
    height: 180px;
    right: 170px;
    bottom: -115px;
}

.hh-search-hero-content {
    position: relative;
    z-index: 2;
    max-width: 760px;
}

.hh-search-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #dce9ff;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 1.8px;
    text-transform: uppercase;
}

.hh-search-hero h1 {
    margin: 0;
    font-size: clamp(30px, 4vw, 48px);
    line-height: 1.05;
    font-weight: 900;
    letter-spacing: -.8px;
}

.hh-search-hero p {
    max-width: 660px;
    margin: 14px 0 0;
    color: rgba(255,255,255,.86);
    font-size: 15px;
    line-height: 1.75;
    font-weight: 500;
}

.hh-search-panel {
    position: relative;
    z-index: 5;
    margin-top: -6px;
    padding: 25px;
    background: rgba(255,255,255,.96);
    border: 1px solid #e1e9f5;
    border-radius: 24px;
    box-shadow: 0 16px 42px rgba(26, 66, 132, .09);
}

.hh-search-form {
    display: grid;
    grid-template-columns: minmax(280px, 1.7fr) minmax(180px, .75fr) minmax(180px, .75fr) auto;
    gap: 15px;
    align-items: end;
}

.hh-field {
    min-width: 0;
}

.hh-field label {
    display: block;
    margin: 0 0 8px 3px;
    color: #415577;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .2px;
}

.hh-input-wrap {
    position: relative;
}

.hh-input-icon {
    position: absolute;
    left: 17px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 16px;
}

.hh-field input,
.hh-field select {
    width: 100%;
    height: 54px;
    border: 1px solid #dbe5f2;
    border-radius: 15px;
    outline: none;
    background: #f9fbff;
    color: #213858;
    font: inherit;
    font-size: 13px;
    font-weight: 700;
    transition: .2s ease;
}

.hh-field input {
    padding: 0 17px 0 48px;
}

.hh-field select {
    padding: 0 42px 0 16px;
    cursor: pointer;
}

.hh-field input:focus,
.hh-field select:focus {
    background: #fff;
    border-color: #7eabff;
    box-shadow: 0 0 0 4px rgba(47,118,255,.09);
}

.hh-search-button {
    height: 54px;
    min-width: 138px;
    padding: 0 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    border: 0;
    border-radius: 15px;
    background: linear-gradient(135deg, #347cff, #205ed8);
    color: #fff;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 900;
    box-shadow: 0 11px 24px rgba(47,118,255,.24);
    transition: transform .2s ease, box-shadow .2s ease;
}

.hh-search-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(47,118,255,.30);
}

.hh-results-panel {
    margin-top: 26px;
    padding: 28px;
    background: #fff;
    border: 1px solid #e3eaf4;
    border-radius: 24px;
    box-shadow: 0 14px 38px rgba(22, 59, 116, .07);
}

.hh-results-heading {
    margin-bottom: 23px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}

.hh-results-heading small {
    display: block;
    margin-bottom: 7px;
    color: #2468ed;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.6px;
}

.hh-results-heading h2 {
    margin: 0;
    color: #162f55;
    font-size: 23px;
    font-weight: 900;
}

.hh-result-count {
    padding: 9px 13px;
    border: 1px solid #dbe7fa;
    border-radius: 999px;
    background: #f3f7ff;
    color: #2468ed;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.hh-product-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
}

.hh-product-card {
    overflow: hidden;
    background: #fff;
    border: 1px solid #e5ebf4;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(26, 59, 110, .06);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}

.hh-product-card:hover {
    transform: translateY(-5px);
    border-color: #c8dafb;
    box-shadow: 0 18px 38px rgba(29, 71, 143, .13);
}

.hh-product-image-link {
    position: relative;
    display: block;
    overflow: hidden;
    aspect-ratio: 1 / .78;
    background: #f4f7fb;
}

.hh-product-image-link img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition: transform .3s ease;
}

.hh-product-card:hover .hh-product-image-link img {
    transform: scale(1.035);
}

.hh-category-chip {
    position: absolute;
    left: 13px;
    top: 13px;
    padding: 7px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.94);
    color: #2468ed;
    font-size: 10px;
    font-weight: 900;
    box-shadow: 0 5px 14px rgba(20, 52, 104, .10);
}

.hh-product-body {
    padding: 17px;
}

.hh-product-vendor {
    margin-bottom: 7px;
    color: #8190a5;
    font-size: 11px;
    font-weight: 800;
}

.hh-product-name {
    margin: 0;
    min-height: 44px;
    font-size: 16px;
    line-height: 1.35;
    font-weight: 900;
}

.hh-product-name a {
    color: #173568;
    text-decoration: none;
}

.hh-product-name a:hover {
    color: #2468ed;
}

.hh-product-bottom {
    margin-top: 15px;
    padding-top: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-top: 1px solid #eef2f7;
}

.hh-product-price {
    color: #175ddc;
    font-size: 17px;
    font-weight: 900;
}

.hh-stock {
    padding: 6px 9px;
    border-radius: 999px;
    font-size: 9px;
    font-weight: 900;
    text-transform: uppercase;
}

.hh-stock.in {
    background: #e9f9ef;
    color: #168044;
}

.hh-stock.low {
    background: #fff7df;
    color: #a66a00;
}

.hh-stock.out {
    background: #fff0f1;
    color: #c23c4b;
}

.hh-empty {
    padding: 62px 25px;
    text-align: center;
    border: 1px dashed #d6e1f0;
    border-radius: 20px;
    background: linear-gradient(180deg, #fbfdff, #f6f9fe);
}

.hh-empty-icon {
    width: 66px;
    height: 66px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    background: #eaf2ff;
    font-size: 28px;
}

.hh-empty h3 {
    margin: 0;
    color: #173568;
    font-size: 19px;
    font-weight: 900;
}

.hh-empty p {
    margin: 8px 0 0;
    color: #8190a5;
    font-size: 13px;
    line-height: 1.6;
}

@media (max-width: 1120px) {
    .hh-search-form {
        grid-template-columns: 1fr 1fr;
    }

    .hh-search-button {
        width: 100%;
    }

    .hh-product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 780px) {
    .hh-search-page {
        padding: 0 14px 55px;
    }

    .hh-search-hero {
        padding: 34px 24px;
        border-radius: 0 0 24px 24px;
    }

    .hh-search-panel,
    .hh-results-panel {
        padding: 19px;
        border-radius: 19px;
    }

    .hh-search-form {
        grid-template-columns: 1fr;
    }

    .hh-product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 520px) {
    .hh-results-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .hh-product-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="hh-search-page">
    <div class="hh-search-shell">

        <section class="hh-search-hero">
            <div class="hh-search-hero-content">
                <div class="hh-search-eyebrow">
                    <span>✦</span>
                    Discover Marketplace
                </div>

                <h1>Find Something You'll Love</h1>

                <p>
                    Search products from approved HochipoHub vendors,
                    explore categories and discover the right item faster.
                </p>
            </div>
        </section>

        <section class="hh-search-panel">
            <form method="GET" action="search.php" class="hh-search-form">

                <div class="hh-field">
                    <label for="searchQuery">Search Products</label>

                    <div class="hh-input-wrap">
                        <span class="hh-input-icon">⌕</span>

                        <input
                            id="searchQuery"
                            type="search"
                            name="q"
                            value="<?= searchE($query) ?>"
                            placeholder="Try product, seller or category..."
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="hh-field">
                    <label for="searchCategory">Category</label>

                    <select id="searchCategory" name="category">
                        <option value="0">All Categories</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['category_id'] ?>"
                                <?= $categoryId === (int) $category['category_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= searchE($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="hh-field">
                    <label for="searchVendor">Vendor</label>

                    <select id="searchVendor" name="vendor">
                        <option value="0">All Vendors</option>

                        <?php foreach ($vendors as $vendor): ?>
                            <option
                                value="<?= (int) $vendor['vendor_id'] ?>"
                                <?= $vendorId === (int) $vendor['vendor_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= searchE($vendor['business_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="hh-search-button">
                    <span>🔍</span>
                    Search
                </button>

            </form>
        </section>

        <section class="hh-results-panel">

            <div class="hh-results-heading">
                <div>
                    <small>SEARCH RESULTS</small>

                    <h2>
                        <?php if ($query !== ''): ?>
                            Results for "<?= searchE($query) ?>"
                        <?php elseif ($categoryId > 0 || $vendorId > 0): ?>
                            Filtered Products
                        <?php else: ?>
                            Start Your Search
                        <?php endif; ?>
                    </h2>
                </div>

                <span class="hh-result-count">
                    <?= count($products) ?>
                    <?= count($products) === 1 ? 'Product' : 'Products' ?>
                </span>
            </div>

            <?php if (empty($products)): ?>

                <div class="hh-empty">
                    <div class="hh-empty-icon">🔎</div>

                    <h3>
                        <?= (
                            $query === '' &&
                            $categoryId <= 0 &&
                            $vendorId <= 0
                        )
                            ? 'What are you looking for?'
                            : 'No products found' ?>
                    </h3>

                    <p>
                        <?= (
                            $query === '' &&
                            $categoryId <= 0 &&
                            $vendorId <= 0
                        )
                            ? 'Enter a keyword or choose a category and vendor to begin.'
                            : 'Try another keyword, category or vendor.' ?>
                    </p>
                </div>

            <?php else: ?>

                <div class="hh-product-grid">

                    <?php foreach ($products as $product): ?>

                        <?php
                        $productId = (int) $product['product_id'];
                        $stock = (int) $product['stock_quantity'];
                        ?>

                        <article class="hh-product-card">

                            <a
                                class="hh-product-image-link"
                                href="product_details.php?id=<?= $productId ?>"
                            >
                                <img
                                    src="<?= searchE(
                                        searchProductImage(
                                            $product['image']
                                        )
                                    ) ?>"
                                    alt="<?= searchE(
                                        $product['product_name']
                                    ) ?>"
                                    loading="lazy"
                                >

                                <span class="hh-category-chip">
                                    <?= searchE(
                                        $product['category_name']
                                    ) ?>
                                </span>
                            </a>

                            <div class="hh-product-body">

                                <div class="hh-product-vendor">
                                    Sold by
                                    <?= searchE(
                                        $product['business_name']
                                    ) ?>
                                </div>

                                <h3 class="hh-product-name">
                                    <a
                                        href="product_details.php?id=<?= $productId ?>"
                                    >
                                        <?= searchE(
                                            $product['product_name']
                                        ) ?>
                                    </a>
                                </h3>

                                <div class="hh-product-bottom">

                                    <span class="hh-product-price">
                                        RM <?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>
                                    </span>

                                    <span
                                        class="hh-stock <?= searchE(
                                            searchStockClass($stock)
                                        ) ?>"
                                    >
                                        <?= searchE(
                                            searchStockText($stock)
                                        ) ?>
                                    </span>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
