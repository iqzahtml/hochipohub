<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - HOMEPAGE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $categoryStmt = $db->query("
        SELECT
            category_id,
            category_name,
            category_image
        FROM categories
        ORDER BY category_name ASC
        LIMIT 8
    ");

    $categories = $categoryStmt->fetchAll();

} catch (PDOException $e) {

    $categories = [];

}


/*
|--------------------------------------------------------------------------
| FETCH FEATURED PRODUCTS
|--------------------------------------------------------------------------
*/

$products = [];

try {

    $productStmt = $db->query("
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

        WHERE p.status = 'Available'
        AND p.stock_quantity > 0
        AND v.approval_status = 'Approved'

        ORDER BY p.created_at DESC

        LIMIT 8
    ");

    $products = $productStmt->fetchAll();

} catch (PDOException $e) {

    $products = [];

}


/*
|--------------------------------------------------------------------------
| FETCH APPROVED VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];

try {

    $vendorStmt = $db->query("
        SELECT
            vendor_id,
            business_name,
            business_logo,
            business_description,
            category

        FROM vendors

        WHERE approval_status = 'Approved'

        ORDER BY created_at DESC

        LIMIT 6
    ");

    $vendors = $vendorStmt->fetchAll();

} catch (PDOException $e) {

    $vendors = [];

}


/*
|--------------------------------------------------------------------------
| CUSTOMER CART COUNT
|--------------------------------------------------------------------------
*/

$cartCount = 0;

if (
    function_exists('isLoggedIn')
    && isLoggedIn()
) {

    $currentUserId = $_SESSION['user_id'] ?? null;

    if ($currentUserId) {

        try {

            $cartStmt = $db->prepare("
                SELECT COALESCE(
                    SUM(quantity),
                    0
                )

                FROM cart

                WHERE customer_id = ?
            ");

            $cartStmt->execute([
                $currentUserId
            ]);

            $cartCount = (int) $cartStmt->fetchColumn();

        } catch (PDOException $e) {

            $cartCount = 0;

        }

    }

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
        <?= e(APP_NAME) ?> | Discover. Shop. Support.
    </title>

    <meta
        name="description"
        content="HochipoHub - A Gen Z marketplace for discovering products from local vendors."
    >

    <link
        rel="stylesheet"
        href="<?= assetUrl('css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= assetUrl('css/responsive.css') ?>"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="home-page">


<?php
/*
|--------------------------------------------------------------------------
| NAVBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/navbar.php';
?>


<main>


<!-- =========================================================
     HERO
========================================================= -->

<section class="hero-section">

    <div class="hero-glow hero-glow-one"></div>
    <div class="hero-glow hero-glow-two"></div>

    <div class="hero-container">

        <div class="hero-content">

            <div class="hero-badge">

                <span class="hero-badge-dot"></span>

                Malaysia's Gen Z Marketplace

            </div>


            <h1>

                Discover.
                <span>Shop.</span>
                <br>

                Support Local.

            </h1>


            <p>

                Find unique products from local vendors,
                discover hidden gems and shop everything
                you love in one place.

            </p>


            <div class="hero-actions">

                <a
                    href="<?= baseUrl('catalog.php') ?>"
                    class="btn-primary hero-btn"
                >

                    <i class="fa-solid fa-bag-shopping"></i>

                    Explore Products

                </a>


                <a
                    href="<?= baseUrl('vendor.php') ?>"
                    class="btn-secondary hero-btn"
                >

                    <i class="fa-solid fa-store"></i>

                    Meet Our Vendors

                </a>

            </div>


            <div class="hero-mini-stats">

                <div class="mini-stat">

                    <strong>
                        <?= count($products) > 0 ? '100+' : '0' ?>
                    </strong>

                    <span>Products</span>

                </div>


                <div class="mini-divider"></div>


                <div class="mini-stat">

                    <strong>
                        <?= count($vendors) > 0 ? '50+' : '0' ?>
                    </strong>

                    <span>Vendors</span>

                </div>


                <div class="mini-divider"></div>


                <div class="mini-stat">

                    <strong>
                        24/7
                    </strong>

                    <span>Shopping</span>

                </div>

            </div>

        </div>


        <div class="hero-visual">

            <div class="hero-card hero-card-main">

                <div class="hero-card-top">

                    <span class="hero-card-label">
                        TRENDING NOW
                    </span>

                    <span class="hero-card-icon">
                        <i class="fa-solid fa-fire"></i>
                    </span>

                </div>


                <div class="hero-product-preview">

                    <div class="hero-product-image">

                        <i class="fa-solid fa-bag-shopping"></i>

                    </div>

                    <div>

                        <span>
                            Fresh Finds
                        </span>

                        <strong>
                            Made For You
                        </strong>

                    </div>

                </div>


                <div class="hero-floating-price">

                    <small>
                        starting from
                    </small>

                    <strong>
                        RM 5.00
                    </strong>

                </div>

            </div>


            <div class="floating-card floating-card-one">

                <i class="fa-solid fa-heart"></i>

                <div>

                    <strong>
                        100%
                    </strong>

                    <span>
                        Local Love
                    </span>

                </div>

            </div>


            <div class="floating-card floating-card-two">

                <i class="fa-solid fa-bolt"></i>

                <div>

                    <strong>
                        Easy
                    </strong>

                    <span>
                        Shopping
                    </span>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     QUICK SEARCH
========================================================= -->

<section class="quick-search-section">

    <div class="section-container">

        <form
            action="<?= baseUrl('search.php') ?>"
            method="GET"
            class="home-search"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="search"
                name="q"
                placeholder="What are you looking for?"
                autocomplete="off"
            >

            <button type="submit">

                Search

            </button>

        </form>

    </div>

</section>



<!-- =========================================================
     CATEGORIES
========================================================= -->

<section class="home-section categories-section">

    <div class="section-container">

        <div class="section-heading">

            <div>

                <span class="section-kicker">
                    EXPLORE
                </span>

                <h2>
                    Shop By Category
                </h2>

                <p>
                    Find exactly what matches your vibe.
                </p>

            </div>


            <a
                href="<?= baseUrl('category.php') ?>"
                class="view-all-link"
            >

                View All

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($categories)): ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <a
                        href="<?= baseUrl(
                            'catalog.php?category='
                            . (int) $category['category_id']
                        ) ?>"
                        class="category-card"
                    >

                        <div class="category-icon">

                            <?php if (!empty($category['category_image'])): ?>

                                <img
                                    src="<?= assetUrl(
                                        'image/product/'
                                        . rawurlencode(
                                            $category['category_image']
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $category['category_name']
                                    ) ?>"
                                >

                            <?php else: ?>

                                <i class="fa-solid fa-layer-group"></i>

                            <?php endif; ?>

                        </div>


                        <div class="category-info">

                            <h3>
                                <?= e(
                                    $category['category_name']
                                ) ?>
                            </h3>

                            <span>

                                Explore

                                <i class="fa-solid fa-arrow-right"></i>

                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-home-state">

                <i class="fa-solid fa-layer-group"></i>

                <h3>
                    Categories coming soon
                </h3>

                <p>
                    Vendors are getting everything ready.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>



<!-- =========================================================
     FEATURED PRODUCTS
========================================================= -->

<section class="home-section products-section">

    <div class="section-container">

        <div class="section-heading">

            <div>

                <span class="section-kicker">
                    FRESH DROP
                </span>

                <h2>
                    Latest Products
                </h2>

                <p>
                    New finds worth checking out.
                </p>

            </div>


            <a
                href="<?= baseUrl('catalog.php') ?>"
                class="view-all-link"
            >

                Shop All

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($products)): ?>

            <div class="product-grid">

                <?php foreach ($products as $product): ?>

                    <article class="home-product-card">


                        <a
                            href="<?= baseUrl(
                                'product_details.php?id='
                                . (int) $product['product_id']
                            ) ?>"
                            class="product-image-wrap"
                        >

                            <?php if (!empty($product['image'])): ?>

                                <img
                                    src="<?= productImageUrl(
                                        $product['image']
                                    ) ?>"
                                    alt="<?= e(
                                        $product['product_name']
                                    ) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="product-placeholder">

                                    <i class="fa-solid fa-image"></i>

                                </div>

                            <?php endif; ?>


                            <span class="product-category-tag">

                                <?= e(
                                    $product['category_name']
                                ) ?>

                            </span>


                            <span class="product-stock-badge">

                                <i class="fa-solid fa-circle"></i>

                                In Stock

                            </span>

                        </a>


                        <div class="product-card-body">

                            <a
                                href="<?= baseUrl(
                                    'product_details.php?id='
                                    . (int) $product['product_id']
                                ) ?>"
                            >

                                <h3>

                                    <?= e(
                                        $product['product_name']
                                    ) ?>

                                </h3>

                            </a>


                            <p class="product-vendor">

                                <i class="fa-solid fa-store"></i>

                                <?= e(
                                    $product['business_name']
                                ) ?>

                            </p>


                            <div class="product-card-bottom">

                                <strong class="product-price">

                                    <?= formatPrice(
                                        $product['price']
                                    ) ?>

                                </strong>


                                <a
                                    href="<?= baseUrl(
                                        'product_details.php?id='
                                        . (int) $product['product_id']
                                    ) ?>"
                                    class="product-view-btn"
                                    aria-label="View product"
                                >

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-home-state">

                <i class="fa-solid fa-box-open"></i>

                <h3>
                    No products yet
                </h3>

                <p>
                    Check back soon for fresh products from our vendors.
                </p>

                <a
                    href="<?= baseUrl('vendor.php') ?>"
                    class="btn-primary"
                >

                    Explore Vendors

                </a>

            </div>

        <?php endif; ?>

    </div>

</section>



<!-- =========================================================
     WHY HOCHIPOHUB
========================================================= -->

<section class="why-section">

    <div class="section-container">

        <div class="why-heading">

            <span class="section-kicker">
                WHY HOCHIPOHUB?
            </span>

            <h2>
                More Than Just
                <span>Shopping.</span>
            </h2>

            <p>
                A marketplace built around local sellers,
                fresh products and a better shopping experience.
            </p>

        </div>


        <div class="why-grid">

            <div class="why-card">

                <div class="why-icon">
                    <i class="fa-solid fa-store"></i>
                </div>

                <h3>
                    Local Vendors
                </h3>

                <p>
                    Discover products from independent
                    Malaysian sellers and small businesses.
                </p>

            </div>


            <div class="why-card">

                <div class="why-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>

                <h3>
                    Trusted Shopping
                </h3>

                <p>
                    Browse verified vendors and shop
                    with confidence.
                </p>

            </div>


            <div class="why-card">

                <div class="why-icon">
                    <i class="fa-solid fa-bolt"></i>
                </div>

                <h3>
                    Simple & Fast
                </h3>

                <p>
                    Find products, add to cart and checkout
                    without unnecessary hassle.
                </p>

            </div>


            <div class="why-card">

                <div class="why-icon">
                    <i class="fa-solid fa-heart"></i>
                </div>

                <h3>
                    Support Local
                </h3>

                <p>
                    Every purchase helps local vendors
                    grow their businesses.
                </p>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     VENDORS
========================================================= -->

<section class="home-section vendors-section">

    <div class="section-container">

        <div class="section-heading">

            <div>

                <span class="section-kicker">
                    MEET THE CREATORS
                </span>

                <h2>
                    Featured Vendors
                </h2>

                <p>
                    The people behind the products you love.
                </p>

            </div>


            <a
                href="<?= baseUrl('vendor.php') ?>"
                class="view-all-link"
            >

                View Vendors

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($vendors)): ?>

            <div class="vendor-home-grid">

                <?php foreach ($vendors as $vendor): ?>

                    <a
                        href="<?= baseUrl(
                            'vendor.php?id='
                            . (int) $vendor['vendor_id']
                        ) ?>"
                        class="vendor-home-card"
                    >

                        <div class="vendor-home-logo">

                            <?php if (!empty($vendor['business_logo'])): ?>

                                <img
                                    src="<?= vendorImageUrl(
                                        $vendor['business_logo']
                                    ) ?>"
                                    alt="<?= e(
                                        $vendor['business_name']
                                    ) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <i class="fa-solid fa-store"></i>

                            <?php endif; ?>

                        </div>


                        <div class="vendor-home-info">

                            <span>
                                VERIFIED VENDOR
                            </span>

                            <h3>
                                <?= e(
                                    $vendor['business_name']
                                ) ?>
                            </h3>

                            <?php if (!empty($vendor['category'])): ?>

                                <p>
                                    <?= e(
                                        $vendor['category']
                                    ) ?>
                                </p>

                            <?php endif; ?>

                        </div>


                        <div class="vendor-arrow">

                            <i class="fa-solid fa-arrow-up-right-from-square"></i>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-home-state">

                <i class="fa-solid fa-store"></i>

                <h3>
                    Vendors coming soon
                </h3>

                <p>
                    New local businesses are joining HochipoHub.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>



<!-- =========================================================
     CTA
========================================================= -->

<section class="cta-section">

    <div class="cta-decoration cta-decoration-one"></div>
    <div class="cta-decoration cta-decoration-two"></div>

    <div class="section-container">

        <div class="cta-content">

            <span class="section-kicker">
                READY TO START?
            </span>

            <h2>
                Your Next Favourite
                <span>Find</span> Is Here.
            </h2>

            <p>
                Browse unique products, support local vendors
                and make your next shopping experience different.
            </p>


            <div class="cta-actions">

                <a
                    href="<?= baseUrl('catalog.php') ?>"
                    class="btn-primary"
                >

                    <i class="fa-solid fa-bag-shopping"></i>

                    Start Shopping

                </a>


                <?php if (
                    !function_exists('isLoggedIn')
                    || !isLoggedIn()
                ): ?>

                    <a
                        href="<?= baseUrl('auth/register.php') ?>"
                        class="btn-secondary"
                    >

                        <i class="fa-solid fa-user-plus"></i>

                        Join HochipoHub

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>


</main>


<?php
/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';
?>


<script src="<?= assetUrl('js/script.js') ?>"></script>

</body>

</html>