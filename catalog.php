<?php

/* =====================================================
   HOCHIPOHUB - CATALOG PAGE
   File: catalog.php
===================================================== */


/* =====================================================
   SESSION
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   CONFIGURATION
===================================================== */

require_once __DIR__ . '/config.php';


/* =====================================================
   DATABASE
===================================================== */

require_once __DIR__ . '/database/db.php';


/* =====================================================
   GLOBAL FUNCTIONS
===================================================== */

require_once __DIR__ . '/includes/functions.php';


/* =====================================================
   PAGE SETTINGS
===================================================== */

$pageTitle = 'Catalog';


/*
|--------------------------------------------------------------------------
| CSS FILES
|--------------------------------------------------------------------------
| style.css + responsive.css are already loaded by header.php.
| Add page-specific CSS here only if the file exists.
|
*/

$extraCSS = [];


/*
|--------------------------------------------------------------------------
| JAVASCRIPT FILES
|--------------------------------------------------------------------------
| script.js and other global JS can be loaded here.
|
*/

$allJS = [
    'script.js',
    'search.js',
    'cart.js'
];


/* =====================================================
   GET FILTER VALUES
===================================================== */

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$categoryId = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

$vendorId = isset($_GET['vendor'])
    ? (int) $_GET['vendor']
    : 0;

$sort = isset($_GET['sort'])
    ? trim($_GET['sort'])
    : 'latest';


/* =====================================================
   VALID SORT OPTIONS
===================================================== */

$allowedSorts = [
    'latest',
    'price_low',
    'price_high',
    'name',
    'oldest'
];


if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'latest';
}


/* =====================================================
   INITIAL VARIABLES
===================================================== */

$categories = [];

$vendors = [];

$products = [];

$productCount = 0;

$activeCategoryName = '';

$activeVendorName = '';


/* =====================================================
   LOAD CATEGORIES
===================================================== */

try {

    $categoryStmt = $db->prepare("
        SELECT
            category_id,
            category_name
        FROM categories
        ORDER BY category_name ASC
    ");

    $categoryStmt->execute();

    $categories = $categoryStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $categories = [];

}


/* =====================================================
   LOAD VENDORS
===================================================== */

try {

    $vendorStmt = $db->prepare("
        SELECT
            vendor_id,
            business_name
        FROM vendors
        ORDER BY business_name ASC
    ");

    $vendorStmt->execute();

    $vendors = $vendorStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $vendors = [];

}


/* =====================================================
   GET ACTIVE CATEGORY NAME
===================================================== */

if ($categoryId > 0) {

    try {

        $categoryNameStmt = $db->prepare("
            SELECT
                category_name
            FROM categories
            WHERE category_id = ?
            LIMIT 1
        ");

        $categoryNameStmt->execute([
            $categoryId
        ]);

        $activeCategoryName =
            $categoryNameStmt->fetchColumn();

        if ($activeCategoryName === false) {
            $activeCategoryName = '';
        }

    } catch (PDOException $e) {

        $activeCategoryName = '';

    }
}


/* =====================================================
   GET ACTIVE VENDOR NAME
===================================================== */

if ($vendorId > 0) {

    try {

        $vendorNameStmt = $db->prepare("
            SELECT
                business_name
            FROM vendors
            WHERE vendor_id = ?
            LIMIT 1
        ");

        $vendorNameStmt->execute([
            $vendorId
        ]);

        $activeVendorName =
            $vendorNameStmt->fetchColumn();

        if ($activeVendorName === false) {
            $activeVendorName = '';
        }

    } catch (PDOException $e) {

        $activeVendorName = '';

    }
}


/* =====================================================
   PRODUCT SORTING
===================================================== */

$orderBy = 'p.created_at DESC';


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


    case 'oldest':

        $orderBy = 'p.created_at ASC';

        break;


    case 'latest':

    default:

        $orderBy = 'p.created_at DESC';

        break;
}


/* =====================================================
   BUILD PRODUCT QUERY
===================================================== */

$sql = "

    SELECT

        p.*,

        v.business_name,

        v.business_logo,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE 1 = 1

";


$params = [];


/* =====================================================
   SEARCH FILTER
===================================================== */

if ($search !== '') {

    $sql .= "

        AND (

            p.product_name LIKE ?

            OR v.business_name LIKE ?

            OR c.category_name LIKE ?

            OR p.description LIKE ?

        )

    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/* =====================================================
   CATEGORY FILTER
===================================================== */

if ($categoryId > 0) {

    $sql .= "

        AND p.category_id = ?

    ";

    $params[] = $categoryId;
}


/* =====================================================
   VENDOR FILTER
===================================================== */

if ($vendorId > 0) {

    $sql .= "

        AND p.vendor_id = ?

    ";

    $params[] = $vendorId;
}


/* =====================================================
   SORT
===================================================== */

$sql .= "

    ORDER BY {$orderBy}

";


/* =====================================================
   LOAD PRODUCTS
===================================================== */

try {

    $productStmt = $db->prepare($sql);

    $productStmt->execute($params);

    $products = $productStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    $productCount = count($products);

} catch (PDOException $e) {

    $products = [];

    $productCount = 0;

}


/* =====================================================
   HEADER
===================================================== */

$extraCSS = ['dashboard.css'];

require_once __DIR__ . '/includes/header.php';

if (
    isset($_SESSION['role']) &&
    strtolower($_SESSION['role']) === 'customer'
) {
    require_once __DIR__ . '/includes/customer_sidebar.php';
}

?>


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

/* =====================================================
   FOOTER
===================================================== */

$footerPath = __DIR__ . '/includes/footer.php';

if (file_exists($footerPath)) {
    require_once $footerPath;
}

?>
