<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - HOME PAGE
|--------------------------------------------------------------------------
| File:
| index.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

$db = getDB();

$pageTitle = 'HochipoHub - Home';


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
| GET FEATURED PRODUCTS
|--------------------------------------------------------------------------
*/

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

    WHERE p.status = 'Available'
      AND p.stock_quantity > 0
      AND v.approval_status = 'Approved'

    ORDER BY p.created_at DESC

    LIMIT 12
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET VENDORS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        v.vendor_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.category

    FROM vendors v

    WHERE v.approval_status = 'Approved'

    ORDER BY v.created_at DESC

    LIMIT 6
");

$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = getFlash();


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="home-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero-section">

        <div class="hero-overlay">

            <div class="hero-content">

                <span class="small-label">
                    WELCOME TO HOCHIPOHUB
                </span>

                <h1>
                    Discover.
                    <span>Shop.</span>
                    Support Local.
                </h1>

                <p>
                    Discover unique products from local
                    vendors and support the businesses
                    around you.
                </p>


                <div class="hero-actions">

                    <a
                        href="<?= e(BASE_URL) ?>catalog.php"
                        class="btn btn-primary"
                    >
                        Explore Products
                    </a>

                    <a
                        href="<?= e(BASE_URL) ?>vendor.php"
                        class="btn btn-secondary"
                    >
                        Explore Vendors
                    </a>

                </div>

            </div>

        </div>

    </section>



    <!-- =====================================================
         FLASH MESSAGE
    ====================================================== -->

    <?php if ($flash): ?>

        <div class="container">

            <div class="alert alert-<?= e($flash['type']) ?>">

                <?= e($flash['message']) ?>

            </div>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         CATEGORIES
    ====================================================== -->

    <section class="home-section">

        <div class="container">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        BROWSE
                    </span>

                    <h2>
                        Shop by Category
                    </h2>

                    <p>
                        Find something that matches
                        what you're looking for.
                    </p>

                </div>

                <a
                    href="<?= e(BASE_URL) ?>category.php"
                    class="view-all-link"
                >
                    View All
                </a>

            </div>


            <?php if (!empty($categories)): ?>

                <div class="category-grid">

                    <?php foreach ($categories as $category): ?>

                        <a
                            href="<?= e(BASE_URL) ?>category.php?id=<?= (int) $category['category_id'] ?>"
                            class="category-card"
                        >

                            <div class="category-image">

                                <?php if (!empty($category['category_image'])): ?>

                                    <img
                                        src="<?= e(BASE_URL) ?>uploads/products/logo.jpeg<?= e(basename($category['category_image'])) ?>"
                                        alt="<?= e($category['category_name']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="category-placeholder">
                                        🛍️
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="category-info">

                                <h3>
                                    <?= e($category['category_name']) ?>
                                </h3>

                                <span>
                                    Explore →
                                </span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>
                        No categories available
                    </h3>

                    <p>
                        Categories will appear here once
                        vendors add products.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>



    <!-- =====================================================
         PRODUCTS
    ====================================================== -->

    <section class="home-section products-section">

        <div class="container">

            <div class="section-heading">

                <div>

                    <span class="small-label">
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
                    class="view-all-link"
                >
                    View All Products
                </a>

            </div>


            <?php if (!empty($products)): ?>

                <div class="product-grid">

                    <?php foreach ($products as $product): ?>

                        <article class="product-card">


                            <a
                                href="<?= e(BASE_URL) ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                class="product-image"
                            >

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="<?= e(BASE_URL) ?>uploads/products/<?= e(basename($product['image'])) ?>"
                                        alt="<?= e($product['product_name']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="product-placeholder">
                                        🛍️
                                    </div>

                                <?php endif; ?>

                            </a>


                            <div class="product-info">

                                <span class="product-category">

                                    <?= e($product['category_name']) ?>

                                </span>


                                <h3>

                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                    >
                                        <?= e($product['product_name']) ?>
                                    </a>

                                </h3>


                                <p class="product-vendor">

                                    <?= e($product['business_name']) ?>

                                </p>


                                <div class="product-bottom">

                                    <strong class="product-price">

                                        RM
                                        <?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>

                                    </strong>


                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                        class="product-view-btn"
                                    >
                                        View
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        🛍️
                    </div>

                    <h3>
                        No products yet
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

    <section class="home-section vendor-section">

        <div class="container">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        LOCAL SELLERS
                    </span>

                    <h2>
                        Meet Our Vendors
                    </h2>

                    <p>
                        Discover businesses and creators
                        behind the products.
                    </p>

                </div>

                <a
                    href="<?= e(BASE_URL) ?>vendor.php"
                    class="view-all-link"
                >
                    View All Vendors
                </a>

            </div>


            <?php if (!empty($vendors)): ?>

                <div class="vendor-grid">

                    <?php foreach ($vendors as $vendor): ?>

                        <a
                            href="<?= e(BASE_URL) ?>vendor.php?id=<?= (int) $vendor['vendor_id'] ?>"
                            class="vendor-card"
                        >

                            <div class="vendor-logo">

                                <?php if (!empty($vendor['business_logo'])): ?>

                                    <img
                                        src="<?= e(BASE_URL) ?>uploads/vendors/<?= e(basename($vendor['business_logo'])) ?>"
                                        alt="<?= e($vendor['business_name']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="vendor-placeholder">
                                        🏪
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="vendor-info">

                                <h3>
                                    <?= e($vendor['business_name']) ?>
                                </h3>


                                <?php if (!empty($vendor['category'])): ?>

                                    <span class="vendor-category">

                                        <?= e($vendor['category']) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (!empty($vendor['business_description'])): ?>

                                    <p>

                                        <?= e(
                                            mb_strimwidth(
                                                $vendor['business_description'],
                                                0,
                                                100,
                                                '...'
                                            )
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>
                        No vendors available
                    </h3>

                </div>

            <?php endif; ?>

        </div>

    </section>



    <!-- =====================================================
         CTA
    ====================================================== -->

    <section class="cta-section">

        <div class="container">

            <div class="cta-content">

                <span class="small-label">
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
                    class="btn btn-primary"
                >
                    Get Started
                </a>

            </div>

        </div>

    </section>


</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>