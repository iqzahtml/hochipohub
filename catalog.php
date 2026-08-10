<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PRODUCT CATALOG
|--------------------------------------------------------------------------
| File:
| catalog.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$pageTitle = 'Shop';

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$categoryId = (int) ($_GET['category'] ?? 0);
$vendorId = (int) ($_GET['vendor'] ?? 0);

$sort = $_GET['sort'] ?? 'latest';

$allowedSorts = [
    'latest',
    'oldest',
    'price_low',
    'price_high',
    'name'
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'latest';
}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        category_id,
        category_name,
        category_image
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
        v.vendor_id,
        v.business_name,
        v.business_logo
    FROM vendors v

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE v.approval_status = 'Approved'
      AND u.status = 'active'

    ORDER BY v.business_name ASC
");

$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| BUILD PRODUCT QUERY
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

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE p.status = 'Available'

      AND p.stock_quantity > 0

      AND v.approval_status = 'Approved'

      AND u.status = 'active'
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
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] = $categoryId;
}


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    $sql .= "
        AND p.vendor_id = ?
    ";

    $params[] = $vendorId;
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'oldest':

        $sql .= "
            ORDER BY p.created_at ASC
        ";

        break;


    case 'price_low':

        $sql .= "
            ORDER BY p.price ASC
        ";

        break;


    case 'price_high':

        $sql .= "
            ORDER BY p.price DESC
        ";

        break;


    case 'name':

        $sql .= "
            ORDER BY p.product_name ASC
        ";

        break;


    case 'latest':
    default:

        $sql .= "
            ORDER BY p.created_at DESC
        ";

        break;
}


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productCount = count($products);


/*
|--------------------------------------------------------------------------
| ACTIVE FILTER LABEL
|--------------------------------------------------------------------------
*/

$activeCategoryName = '';

if ($categoryId > 0) {

    foreach ($categories as $category) {

        if ((int) $category['category_id'] === $categoryId) {

            $activeCategoryName =
                $category['category_name'];

            break;
        }
    }
}


$activeVendorName = '';

if ($vendorId > 0) {

    foreach ($vendors as $vendor) {

        if ((int) $vendor['vendor_id'] === $vendorId) {

            $activeVendorName =
                $vendor['business_name'];

            break;
        }
    }
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>

<main class="catalog-page">

    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="catalog-hero">

        <div class="catalog-hero-inner">

            <div class="catalog-hero-content">

                <span class="small-label">
                    DISCOVER • SHOP • SUPPORT LOCAL
                </span>

                <h1>
                    Explore
                    <span>HochipoHub</span>
                </h1>

                <p>
                    Discover unique products from local
                    vendors and find something worth bringing
                    home.
                </p>


                <!-- SEARCH -->

                <form
                    action="<?= e(BASE_URL) ?>catalog.php"
                    method="GET"
                    class="catalog-search"
                >

                    <?php if ($categoryId > 0): ?>

                        <input
                            type="hidden"
                            name="category"
                            value="<?= $categoryId ?>"
                        >

                    <?php endif; ?>


                    <?php if ($vendorId > 0): ?>

                        <input
                            type="hidden"
                            name="vendor"
                            value="<?= $vendorId ?>"
                        >

                    <?php endif; ?>


                    <div class="catalog-search-input">

                        <span>
                            🔎
                        </span>

                        <input
                            type="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search products, vendors or categories..."
                            autocomplete="off"
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Search
                    </button>

                </form>

            </div>

        </div>

    </section>


    <!-- =====================================================
         CATALOG BODY
    ====================================================== -->

    <section class="catalog-content">

        <div class="catalog-layout">


            <!-- =================================================
                 SIDEBAR
            ================================================== -->

            <aside class="catalog-sidebar">


                <!-- CATEGORY FILTER -->

                <div class="catalog-filter-card">

                    <div class="catalog-filter-heading">

                        <span class="filter-icon">
                            ◈
                        </span>

                        <div>

                            <span class="small-label">
                                BROWSE
                            </span>

                            <h3>
                                Categories
                            </h3>

                        </div>

                    </div>


                    <div class="catalog-filter-list">

                        <a
                            href="<?= e(BASE_URL) ?>catalog.php"
                            class="<?= $categoryId === 0 ? 'active' : '' ?>"
                        >

                            <span>
                                All Products
                            </span>

                            <span>
                                →
                            </span>

                        </a>


                        <?php foreach ($categories as $category): ?>

                            <a
                                href="<?= e(BASE_URL) ?>catalog.php?category=<?= (int) $category['category_id'] ?>"
                                class="<?= $categoryId === (int) $category['category_id'] ? 'active' : '' ?>"
                            >

                                <span>
                                    <?= e($category['category_name']) ?>
                                </span>

                                <span>
                                    →
                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- VENDOR FILTER -->

                <div class="catalog-filter-card">

                    <div class="catalog-filter-heading">

                        <span class="filter-icon">
                            ◉
                        </span>

                        <div>

                            <span class="small-label">
                                MARKETPLACE
                            </span>

                            <h3>
                                Sellers
                            </h3>

                        </div>

                    </div>


                    <div class="catalog-filter-list">

                        <a
                            href="<?= e(BASE_URL) ?>catalog.php"
                            class="<?= $vendorId === 0 ? 'active' : '' ?>"
                        >

                            <span>
                                All Sellers
                            </span>

                            <span>
                                →
                            </span>

                        </a>


                        <?php foreach ($vendors as $vendor): ?>

                            <a
                                href="<?= e(BASE_URL) ?>catalog.php?vendor=<?= (int) $vendor['vendor_id'] ?>"
                                class="<?= $vendorId === (int) $vendor['vendor_id'] ? 'active' : '' ?>"
                            >

                                <span>
                                    <?= e($vendor['business_name']) ?>
                                </span>

                                <span>
                                    →
                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- QUICK LINK -->

                <div class="catalog-sidebar-cta">

                    <span>
                        ✦
                    </span>

                    <h3>
                        Looking for something else?
                    </h3>

                    <p>
                        Browse all categories and discover
                        more products.
                    </p>

                    <a
                        href="<?= e(BASE_URL) ?>category.php"
                    >
                        Browse Categories →
                    </a>

                </div>

            </aside>


            <!-- =================================================
                 PRODUCT AREA
            ================================================== -->

            <div class="catalog-main">


                <!-- TOOLBAR -->

                <div class="catalog-toolbar">

                    <div class="catalog-toolbar-info">

                        <span class="small-label">
                            PRODUCTS
                        </span>

                        <h2>
                            <?php if ($activeCategoryName !== ''): ?>

                                <?= e($activeCategoryName) ?>

                            <?php elseif ($activeVendorName !== ''): ?>

                                <?= e($activeVendorName) ?>

                            <?php elseif ($search !== ''): ?>

                                Search results

                            <?php else: ?>

                                All Products

                            <?php endif; ?>

                        </h2>

                        <p>

                            <strong>
                                <?= $productCount ?>
                            </strong>

                            product<?= $productCount !== 1 ? 's' : '' ?>
                            found

                        </p>

                    </div>


                    <form
                        action="<?= e(BASE_URL) ?>catalog.php"
                        method="GET"
                        class="catalog-sort"
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


                        <?php if ($vendorId > 0): ?>

                            <input
                                type="hidden"
                                name="vendor"
                                value="<?= $vendorId ?>"
                            >

                        <?php endif; ?>


                        <label for="sort">
                            Sort by
                        </label>

                        <select
                            name="sort"
                            id="sort"
                            onchange="this.form.submit()"
                        >

                            <option
                                value="latest"
                                <?= $sort === 'latest' ? 'selected' : '' ?>
                            >
                                Latest
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
                                Name
                            </option>

                            <option
                                value="oldest"
                                <?= $sort === 'oldest' ? 'selected' : '' ?>
                            >
                                Oldest
                            </option>

                        </select>

                    </form>

                </div>


                <!-- ACTIVE FILTERS -->

                <?php if (
                    $search !== '' ||
                    $categoryId > 0 ||
                    $vendorId > 0
                ): ?>

                    <div class="active-filters">

                        <span>
                            Active:
                        </span>


                        <?php if ($search !== ''): ?>

                            <span class="filter-tag">

                                🔎
                                <?= e($search) ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($activeCategoryName !== ''): ?>

                            <span class="filter-tag">

                                <?= e($activeCategoryName) ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($activeVendorName !== ''): ?>

                            <span class="filter-tag">

                                <?= e($activeVendorName) ?>

                            </span>

                        <?php endif; ?>


                        <a
                            href="<?= e(BASE_URL) ?>catalog.php"
                        >
                            Clear all
                        </a>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     PRODUCTS
                ================================================== -->

                <?php if (!empty($products)): ?>

                    <div class="catalog-product-grid">

                        <?php foreach ($products as $product): ?>

                            <?php

                            $productId =
                                (int) $product['product_id'];

                            $price =
                                (float) $product['price'];

                            $productImage =
                                getProductImage(
                                    $product['image']
                                );

                            ?>

                            <article class="catalog-product-card">


                                <!-- IMAGE -->

                                <div class="catalog-product-image">

                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                    >

                                        <?php if (!empty($product['image'])): ?>

                                            <img
                                                src="<?= e($productImage) ?>"
                                                alt="<?= e($product['product_name']) ?>"
                                                loading="lazy"
                                            >

                                        <?php else: ?>

                                            <div class="catalog-product-placeholder">
                                                🛍️
                                            </div>

                                        <?php endif; ?>

                                    </a>


                                    <?php if (
                                        isset($_SESSION['user_id'])
                                    ): ?>

                                        <button
                                            type="button"
                                            class="wishlist-btn"
                                            data-product-id="<?= $productId ?>"
                                            title="Add to wishlist"
                                        >
                                            ♡
                                        </button>

                                    <?php endif; ?>


                                    <span class="catalog-stock">

                                        <?= (int) $product['stock_quantity'] ?>
                                        left

                                    </span>

                                </div>


                                <!-- BODY -->

                                <div class="catalog-product-body">


                                    <span class="catalog-product-category">

                                        <?= e(
                                            $product['category_name']
                                        ) ?>

                                    </span>


                                    <h3>

                                        <a
                                            href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                        >

                                            <?= e(
                                                $product['product_name']
                                            ) ?>

                                        </a>

                                    </h3>


                                    <p class="catalog-product-vendor">

                                        by

                                        <strong>
                                            <?= e(
                                                $product['business_name']
                                            ) ?>
                                        </strong>

                                    </p>


                                    <div class="catalog-product-footer">

                                        <div>

                                            <span>
                                                Price
                                            </span>

                                            <strong>

                                                RM
                                                <?= number_format(
                                                    $price,
                                                    2
                                                ) ?>

                                            </strong>

                                        </div>


                                        <?php if (
                                            isset($_SESSION['user_id'])
                                        ): ?>

                                            <button
                                                type="button"
                                                class="add-cart-btn"
                                                data-product-id="<?= $productId ?>"
                                            >

                                                🛒
                                                Add

                                            </button>

                                        <?php else: ?>

                                            <a
                                                href="<?= e(BASE_URL) ?>index.php"
                                                class="add-cart-btn"
                                            >

                                                Login

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>


                <?php else: ?>


                    <!-- EMPTY -->

                    <div class="catalog-empty">

                        <div class="catalog-empty-icon">
                            🔎
                        </div>

                        <span class="small-label">
                            NOTHING HERE YET
                        </span>

                        <h2>
                            No products found
                        </h2>

                        <p>
                            Try another keyword, category or seller.
                            There might be something else waiting for you.
                        </p>

                        <a
                            href="<?= e(BASE_URL) ?>catalog.php"
                            class="btn btn-primary"
                        >
                            View All Products
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>


<script src="<?= e(BASE_URL) ?>js/script.js"></script>
<script src="<?= e(BASE_URL) ?>js/cart.js"></script>
<script src="<?= e(BASE_URL) ?>js/wishlist.js"></script>
<script src="<?= e(BASE_URL) ?>js/search.js"></script>

</body>
</html>