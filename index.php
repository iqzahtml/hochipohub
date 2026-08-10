<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - HOME PAGE
|--------------------------------------------------------------------------
| File:
| index.php
|
| Purpose:
| - Load global header
| - Load database
| - Load categories
| - Load latest products
| - Load approved vendors
| - Display homepage
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle = 'Home';


/*
|--------------------------------------------------------------------------
| LOAD GLOBAL HEADER
|--------------------------------------------------------------------------
|
| header.php already loads:
| - config.php
| - session
| - database
| - functions.php
| - global CSS
| - navbar.php
|
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = null;

if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {

    $db = $GLOBALS['db'];

}


/*
|--------------------------------------------------------------------------
| FALLBACK DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (!$db) {

    $dbPath = __DIR__ . '/database/db.php';

    if (file_exists($dbPath)) {

        require_once $dbPath;

    }

    if (function_exists('getDB')) {

        $db = getDB();

    }

}


/*
|--------------------------------------------------------------------------
| DEFAULT DATA
|--------------------------------------------------------------------------
*/

$categories = [];
$products = [];
$vendors = [];
$flash = null;


/*
|--------------------------------------------------------------------------
| LOAD FLASH MESSAGE
|--------------------------------------------------------------------------
*/

if (function_exists('getFlash')) {

    $flash = getFlash();

}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

if ($db instanceof PDO) {

    try {

        $stmt = $db->query("
            SELECT
                category_id,
                category_name,
                category_image
            FROM categories
            ORDER BY category_name ASC
            LIMIT 8
        ");

        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {

        $categories = [];

    }

}


/*
|--------------------------------------------------------------------------
| GET LATEST PRODUCTS
|--------------------------------------------------------------------------
*/

if ($db instanceof PDO) {

    try {

        $stmt = $db->query("
            SELECT
                p.product_id,
                p.product_name,
                p.description,
                p.price,
                p.stock_quantity,
                p.image,
                p.status,

                v.vendor_id,
                v.business_name,

                c.category_id,
                c.category_name

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN categories c
                ON p.category_id = c.category_id

            WHERE p.stock_quantity > 0

              AND (
                    p.status = 'Available'
                    OR p.status = 'available'
                    OR p.status = 'Active'
                    OR p.status = 'active'
                  )

              AND (
                    v.approval_status = 'Approved'
                    OR v.approval_status = 'approved'
                  )

            ORDER BY p.created_at DESC

            LIMIT 8
        ");

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {

        $products = [];

    }

}


/*
|--------------------------------------------------------------------------
| GET APPROVED VENDORS
|--------------------------------------------------------------------------
*/

if ($db instanceof PDO) {

    try {

        $stmt = $db->query("
            SELECT
                v.vendor_id,
                v.business_name,
                v.business_logo,
                v.business_description,
                v.category

            FROM vendors v

            WHERE
                v.approval_status = 'Approved'
                OR v.approval_status = 'approved'

            ORDER BY v.created_at DESC

            LIMIT 6
        ");

        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {

        $vendors = [];

    }

}


/*
|--------------------------------------------------------------------------
| SAFE COUNTS
|--------------------------------------------------------------------------
*/

$categoryCount = count($categories);
$productCount = count($products);
$vendorCount = count($vendors);

?>



<!-- =========================================================
     HOMEPAGE
========================================================= -->

<div class="home-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="home-hero">

        <div class="home-hero-decoration home-hero-circle-one"></div>

        <div class="home-hero-decoration home-hero-circle-two"></div>

        <div class="container">

            <div class="home-hero-content">


                <!-- LABEL -->

                <div class="home-hero-label">

                    <span>✦</span>

                    HOCHIPOHUB MARKETPLACE

                </div>


                <!-- TITLE -->

                <h1>

                    Discover.

                    <span>Shop.</span>

                    Support Local.

                </h1>


                <!-- DESCRIPTION -->

                <p>

                    Discover unique products from local
                    vendors and support the businesses
                    around you.

                </p>


                <!-- BUTTONS -->

                <div class="home-hero-actions">

                    <a
                        href="<?= e(BASE_URL) ?>catalog.php"
                        class="home-btn home-btn-primary"
                    >

                        Explore Products

                        <span>→</span>

                    </a>


                    <a
                        href="<?= e(BASE_URL) ?>vendor.php"
                        class="home-btn home-btn-outline"
                    >

                        Explore Vendors

                        <span>→</span>

                    </a>

                </div>


                <!-- STATS -->

                <div class="home-hero-stats">


                    <div class="home-stat">

                        <strong>
                            <?= $productCount ?>+
                        </strong>

                        <span>
                            Products
                        </span>

                    </div>


                    <div class="home-stat-line"></div>


                    <div class="home-stat">

                        <strong>
                            <?= $vendorCount ?>+
                        </strong>

                        <span>
                            Local Vendors
                        </span>

                    </div>


                    <div class="home-stat-line"></div>


                    <div class="home-stat">

                        <strong>
                            <?= $categoryCount ?>+
                        </strong>

                        <span>
                            Categories
                        </span>

                    </div>


                </div>

            </div>



            <!-- =================================================
                 HERO VISUAL
            ================================================== -->

            <div class="home-hero-visual">


                <div class="home-hero-main-card">


                    <div class="home-hero-card-top">

                        <span class="home-hero-card-icon">
                            🛍️
                        </span>

                        <span class="home-hero-card-tag">
                            SHOP LOCAL
                        </span>

                    </div>


                    <h2>

                        Everything you need,

                        <span>
                            all in one hub.
                        </span>

                    </h2>


                    <p>

                        Find products from local
                        businesses and discover
                        something made just for you.

                    </p>


                    <a
                        href="<?= e(BASE_URL) ?>catalog.php"
                        class="home-hero-card-link"
                    >

                        Start Shopping

                        <span>→</span>

                    </a>


                    <div class="home-card-mini-grid">


                        <div class="home-mini-item">

                            <span>
                                ✨
                            </span>

                            <div>

                                <strong>
                                    Fresh Finds
                                </strong>

                                <small>
                                    New products
                                </small>

                            </div>

                        </div>


                        <div class="home-mini-item">

                            <span>
                                ❤️
                            </span>

                            <div>

                                <strong>
                                    Local Love
                                </strong>

                                <small>
                                    Support sellers
                                </small>

                            </div>

                        </div>


                    </div>


                </div>


                <!-- FLOATING CARD -->

                <div class="home-floating-card home-floating-one">

                    <div class="home-floating-icon">
                        ✨
                    </div>

                    <div>

                        <strong>
                            Fresh Finds
                        </strong>

                        <small>
                            New products waiting
                        </small>

                    </div>

                </div>


                <!-- FLOATING CARD -->

                <div class="home-floating-card home-floating-two">

                    <div class="home-floating-icon">
                        ❤️
                    </div>

                    <div>

                        <strong>
                            Support Local
                        </strong>

                        <small>
                            Shop small businesses
                        </small>

                    </div>

                </div>


            </div>

        </div>

    </section>



    <!-- =====================================================
         FLASH MESSAGE
    ====================================================== -->

    <?php if (!empty($flash)): ?>

        <div class="container">

            <div class="alert alert-<?= e($flash['type'] ?? 'info') ?>">

                <?= e($flash['message'] ?? '') ?>

            </div>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         CATEGORIES
    ====================================================== -->

    <section class="home-category-section">

        <div class="container">


            <div class="home-section-heading">


                <div>

                    <span class="home-section-label">
                        BROWSE
                    </span>

                    <h2>
                        Shop by Category
                    </h2>

                    <p>
                        Explore different categories
                        and find your next favourite.
                    </p>

                </div>


                <a
                    href="<?= e(BASE_URL) ?>category.php"
                    class="home-view-all"
                >

                    View All

                    <span>
                        →
                    </span>

                </a>


            </div>



            <?php if (!empty($categories)): ?>


                <div class="home-category-grid">


                    <?php foreach ($categories as $category): ?>


                        <a
                            href="<?= e(BASE_URL) ?>category.php?id=<?= (int) $category['category_id'] ?>"
                            class="home-category-card"
                        >


                            <div class="home-category-image">


                                <?php if (!empty($category['category_image'])): ?>


                                    <img
                                        src="<?= e(BASE_URL) ?>uploads/products/<?= e(basename($category['category_image'])) ?>"
                                        alt="<?= e($category['category_name']) ?>"
                                    >


                                <?php else: ?>


                                    <div class="home-category-placeholder">

                                        <span>
                                            🛍️
                                        </span>

                                    </div>


                                <?php endif; ?>


                                <div class="home-category-number">

                                    <?= str_pad(
                                        (string) (($category['category_id'] ?? 0)),
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>

                                </div>


                            </div>


                            <div class="home-category-info">


                                <h3>

                                    <?= e(
                                        $category['category_name']
                                    ) ?>

                                </h3>


                                <span>

                                    Explore

                                    <b>
                                        →
                                    </b>

                                </span>


                            </div>


                        </a>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="home-empty">

                    <div class="home-empty-icon">
                        🛍️
                    </div>

                    <h3>
                        No categories available
                    </h3>

                    <p>
                        Categories will appear here
                        once products are added.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </section>



    <!-- =====================================================
         PRODUCTS
    ====================================================== -->

    <section class="home-products-section">

        <div class="container">


            <div class="home-section-heading">


                <div>

                    <span class="home-section-label">
                        LATEST FINDS
                    </span>

                    <h2>
                        Fresh From Our Vendors
                    </h2>

                    <p>
                        Check out the latest products
                        available on HochipoHub.
                    </p>

                </div>


                <a
                    href="<?= e(BASE_URL) ?>catalog.php"
                    class="home-view-all"
                >

                    View All Products

                    <span>
                        →
                    </span>

                </a>


            </div>



            <?php if (!empty($products)): ?>


                <div class="home-product-grid">


                    <?php foreach ($products as $product): ?>


                        <article class="home-product-card">


                            <a
                                href="<?= e(BASE_URL) ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                class="home-product-image"
                            >


                                <?php if (!empty($product['image'])): ?>


                                    <img
                                        src="<?= e(BASE_URL) ?>uploads/products/<?= e(basename($product['image'])) ?>"
                                        alt="<?= e($product['product_name']) ?>"
                                    >


                                <?php else: ?>


                                    <div class="home-product-placeholder">

                                        🛍️

                                    </div>


                                <?php endif; ?>


                                <span class="home-product-category">

                                    <?= e(
                                        $product['category_name']
                                    ) ?>

                                </span>


                            </a>



                            <div class="home-product-info">


                                <h3>

                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                    >

                                        <?= e(
                                            $product['product_name']
                                        ) ?>

                                    </a>

                                </h3>


                                <p class="home-product-vendor">

                                    🏪

                                    <?= e(
                                        $product['business_name']
                                    ) ?>

                                </p>


                                <div class="home-product-bottom">


                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>

                                    </strong>


                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                        class="home-product-button"
                                    >

                                        View

                                        <span>
                                            →
                                        </span>

                                    </a>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="home-empty">

                    <div class="home-empty-icon">
                        🛍️
                    </div>

                    <h3>
                        No products available
                    </h3>

                    <p>
                        New products will appear here
                        once vendors start selling.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </section>



    <!-- =====================================================
         VENDORS
    ====================================================== -->

    <section class="home-vendor-section">

        <div class="container">


            <div class="home-section-heading">


                <div>

                    <span class="home-section-label">
                        LOCAL SELLERS
                    </span>

                    <h2>
                        Meet Our Vendors
                    </h2>

                    <p>
                        Discover the businesses
                        behind the products.
                    </p>

                </div>


                <a
                    href="<?= e(BASE_URL) ?>vendor.php"
                    class="home-view-all"
                >

                    View All Vendors

                    <span>
                        →
                    </span>

                </a>


            </div>



            <?php if (!empty($vendors)): ?>


                <div class="home-vendor-grid">


                    <?php foreach ($vendors as $vendor): ?>


                        <a
                            href="<?= e(BASE_URL) ?>vendor.php?id=<?= (int) $vendor['vendor_id'] ?>"
                            class="home-vendor-card"
                        >


                            <div class="home-vendor-logo">


                                <?php if (!empty($vendor['business_logo'])): ?>


                                    <img
                                        src="<?= e(BASE_URL) ?>uploads/vendors/<?= e(basename($vendor['business_logo'])) ?>"
                                        alt="<?= e($vendor['business_name']) ?>"
                                    >


                                <?php else: ?>


                                    <div class="home-vendor-placeholder">

                                        🏪

                                    </div>


                                <?php endif; ?>


                            </div>


                            <div class="home-vendor-info">


                                <h3>

                                    <?= e(
                                        $vendor['business_name']
                                    ) ?>

                                </h3>


                                <?php if (!empty($vendor['category'])): ?>


                                    <span>

                                        <?= e(
                                            $vendor['category']
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                                <?php if (!empty($vendor['business_description'])): ?>


                                    <p>

                                        <?= e(
                                            mb_strimwidth(
                                                $vendor['business_description'],
                                                0,
                                                90,
                                                '...'
                                            )
                                        ) ?>

                                    </p>


                                <?php endif; ?>


                            </div>


                            <div class="home-vendor-arrow">

                                →

                            </div>


                        </a>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="home-empty">

                    <div class="home-empty-icon">
                        🏪
                    </div>

                    <h3>
                        No vendors available
                    </h3>

                    <p>
                        Approved vendors will appear here.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </section>



    <!-- =====================================================
         CTA
    ====================================================== -->

    <section class="home-cta-section">

        <div class="home-cta-decoration"></div>

        <div class="container">


            <div class="home-cta-content">


                <span class="home-section-label">
                    SELL WITH HOCHIPOHUB
                </span>


                <h2>
                    Got something worth selling?
                </h2>


                <p>
                    Turn your products into opportunities
                    and reach more customers through
                    HochipoHub.
                </p>


                <a
                    href="<?= e(BASE_URL) ?>dashboard.php"
                    class="home-cta-button"
                >

                    Get Started

                    <span>
                        →
                    </span>

                </a>


            </div>


        </div>

    </section>


</div>



<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';

?>