<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Homepage
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';

require_once __DIR__ . '/database/db.php';

require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Home';

$pageDescription =
    'Discover local products, support local vendors and shop with HochipoHub.';


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->query("
    SELECT
        category_id,
        category_name,
        category_image

    FROM categories

    ORDER BY category_name ASC

    LIMIT 6
");

$categories =
    $categoryStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| FEATURED PRODUCTS
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->query("
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
        v.business_logo,

        c.category_id,
        c.category_name,

        COALESCE(
            AVG(
                CASE
                    WHEN r.status = 'Visible'
                    THEN r.rating
                END
            ),
            0
        ) AS average_rating,

        COUNT(
            CASE
                WHEN r.status = 'Visible'
                THEN r.review_id
            END
        ) AS review_count

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    LEFT JOIN reviews r
        ON p.product_id = r.product_id

    WHERE p.status = 'Available'

    AND p.stock_quantity > 0

    AND v.approval_status = 'Approved'

    GROUP BY

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
        v.business_logo,

        c.category_id,
        c.category_name

    ORDER BY p.created_at DESC

    LIMIT 8
");

$featuredProducts =
    $productStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| APPROVED VENDORS
|--------------------------------------------------------------------------
*/

$vendorStmt = $pdo->query("
    SELECT

        v.vendor_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.category,

        COUNT(p.product_id) AS product_count

    FROM vendors v

    LEFT JOIN products p
        ON v.vendor_id = p.vendor_id
        AND p.status = 'Available'

    WHERE v.approval_status = 'Approved'

    GROUP BY

        v.vendor_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.category

    ORDER BY v.created_at DESC

    LIMIT 4
");

$featuredVendors =
    $vendorStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

?>

<!-- =========================================================
     HERO
========================================================= -->

<section class="hero-section">

    <div class="hero-background-shape shape-one"></div>

    <div class="hero-background-shape shape-two"></div>


    <div class="hero-container">


        <!-- HERO CONTENT -->

        <div class="hero-content">

            <div class="hero-badge">

                <i class="fa-solid fa-sparkles"></i>

                <span>
                    Malaysia's Local Marketplace
                </span>

            </div>


            <h1 class="hero-title">

                Discover.

                <span>
                    Shop.
                </span>

                Support Local.

            </h1>


            <p class="hero-description">

                Your one-stop marketplace to discover
                amazing products from local vendors
                around Malaysia.

            </p>


            <div class="hero-actions">

                <a
                    href="<?php echo BASE_URL; ?>catalog.php"
                    class="btn btn-primary btn-large"
                >

                    <span>
                        Explore Products
                    </span>

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


                <a
                    href="<?php echo BASE_URL; ?>vendor.php"
                    class="btn btn-outline btn-large"
                >

                    <i class="fa-solid fa-store"></i>

                    <span>
                        Meet Our Vendors
                    </span>

                </a>

            </div>


            <!-- HERO STATS -->

            <div class="hero-stats">

                <div class="hero-stat">

                    <strong>
                        <?php echo count($featuredProducts); ?>+
                    </strong>

                    <span>
                        Products
                    </span>

                </div>


                <div class="hero-stat-divider"></div>


                <div class="hero-stat">

                    <strong>
                        <?php echo count($featuredVendors); ?>+
                    </strong>

                    <span>
                        Local Vendors
                    </span>

                </div>


                <div class="hero-stat-divider"></div>


                <div class="hero-stat">

                    <strong>
                        100%
                    </strong>

                    <span>
                        Local Love
                    </span>

                </div>

            </div>

        </div>


        <!-- HERO VISUAL -->

        <div class="hero-visual">

            <div class="hero-card hero-card-main">

                <div class="hero-card-image">

                    <img
                        src="<?php echo BASE_URL; ?>image/banner.jpg"
                        alt="HochipoHub"
                    >

                </div>


                <div class="hero-card-content">

                    <span class="hero-card-label">
                        TRENDING NOW
                    </span>

                    <h3>
                        Shop Local,
                        Shop Different.
                    </h3>

                    <a
                        href="<?php echo BASE_URL; ?>catalog.php"
                    >

                        Discover Now

                        <i class="fa-solid fa-arrow-up-right-from-square"></i>

                    </a>

                </div>

            </div>


            <div class="floating-card floating-card-one">

                <div class="floating-icon">

                    <i class="fa-solid fa-heart"></i>

                </div>

                <div>

                    <strong>
                        Local Love
                    </strong>

                    <span>
                        Support small businesses
                    </span>

                </div>

            </div>


            <div class="floating-card floating-card-two">

                <div class="floating-icon">

                    <i class="fa-solid fa-bolt"></i>

                </div>

                <div>

                    <strong>
                        Fresh Finds
                    </strong>

                    <span>
                        Discover something new
                    </span>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     CATEGORY SECTION
========================================================= -->

<section class="home-section categories-section">

    <div class="section-container">


        <div class="section-heading">

            <div>

                <span class="section-kicker">
                    EXPLORE
                </span>

                <h2>
                    Shop by Category
                </h2>

                <p>
                    Find exactly what you're looking for.
                </p>

            </div>


            <a
                href="<?php echo BASE_URL; ?>catalog.php"
                class="section-link"
            >

                View All

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($categories)): ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <a
                        href="<?php echo BASE_URL; ?>category.php?id=<?php echo (int) $category['category_id']; ?>"
                        class="category-card"
                    >

                        <div class="category-image">

                            <?php if (
                                !empty(
                                    $category['category_image']
                                )
                            ): ?>

                                <img
                                    src="<?php echo BASE_URL; ?>image/<?php echo e($category['category_image']); ?>"
                                    alt="<?php echo e($category['category_name']); ?>"
                                >

                            <?php else: ?>

                                <div class="category-placeholder">

                                    <i class="fa-solid fa-layer-group"></i>

                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="category-content">

                            <h3>
                                <?php echo e(
                                    $category['category_name']
                                ); ?>
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

            <div class="empty-state">

                <i class="fa-solid fa-box-open"></i>

                <h3>
                    No categories yet
                </h3>

                <p>
                    Categories will appear here once they are added.
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
                    FRESH FINDS
                </span>

                <h2>
                    Latest Products
                </h2>

                <p>
                    Fresh products from our local vendors.
                </p>

            </div>


            <a
                href="<?php echo BASE_URL; ?>catalog.php"
                class="section-link"
            >

                Browse All

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($featuredProducts)): ?>

            <div class="product-grid">

                <?php foreach (
                    $featuredProducts
                    as $product
                ): ?>

                    <article class="product-card">


                        <!-- PRODUCT IMAGE -->

                        <div class="product-card-image">

                            <a
                                href="<?php echo BASE_URL; ?>product_details.php?id=<?php echo (int) $product['product_id']; ?>"
                            >

                                <?php if (
                                    !empty(
                                        $product['image']
                                    )
                                ): ?>

                                    <img
                                        src="<?php echo productImageUrl($product['image']); ?>"
                                        alt="<?php echo e($product['product_name']); ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="product-placeholder">

                                        <i class="fa-solid fa-image"></i>

                                    </div>

                                <?php endif; ?>

                            </a>


                            <!-- WISHLIST -->

                            <?php if (
                                isLoggedIn()
                                &&
                                currentUserRole()
                                    === 'customer'
                            ): ?>

                                <button
                                    type="button"
                                    class="product-wishlist-btn"
                                    data-product-id="<?php echo (int) $product['product_id']; ?>"
                                    title="Add to wishlist"
                                >

                                    <i class="fa-regular fa-heart"></i>

                                </button>

                            <?php endif; ?>


                            <!-- CATEGORY -->

                            <span class="product-category-badge">

                                <?php echo e(
                                    $product['category_name']
                                ); ?>

                            </span>

                        </div>


                        <!-- PRODUCT INFO -->

                        <div class="product-card-content">


                            <div class="product-vendor">

                                <i class="fa-solid fa-store"></i>

                                <?php echo e(
                                    $product['business_name']
                                ); ?>

                            </div>


                            <h3 class="product-title">

                                <a
                                    href="<?php echo BASE_URL; ?>product_details.php?id=<?php echo (int) $product['product_id']; ?>"
                                >

                                    <?php echo e(
                                        $product['product_name']
                                    ); ?>

                                </a>

                            </h3>


                            <!-- RATING -->

                            <div class="product-rating">

                                <span class="stars">

                                    <?php

                                    $rating =
                                        round(
                                            (float)
                                            $product['average_rating']
                                        );

                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ):

                                    ?>

                                        <?php if (
                                            $i <= $rating
                                        ): ?>

                                            <i class="fa-solid fa-star"></i>

                                        <?php else: ?>

                                            <i class="fa-regular fa-star"></i>

                                        <?php endif; ?>

                                    <?php endfor; ?>

                                </span>


                                <span class="rating-count">

                                    (
                                    <?php echo (int) $product['review_count']; ?>
                                    )

                                </span>

                            </div>


                            <!-- PRICE -->

                            <div class="product-card-bottom">

                                <div class="product-price">

                                    <?php echo formatMoney(
                                        (float) $product['price']
                                    ); ?>

                                </div>


                                <a
                                    href="<?php echo BASE_URL; ?>product_details.php?id=<?php echo (int) $product['product_id']; ?>"
                                    class="product-view-btn"
                                    title="View product"
                                >

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-solid fa-box-open"></i>

                </div>

                <h3>
                    No products available yet
                </h3>

                <p>
                    Our vendors haven't added their products yet.
                </p>

                <a
                    href="<?php echo BASE_URL; ?>vendor.php"
                    class="btn btn-primary"
                >

                    Meet Our Vendors

                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================================
     VENDOR SECTION
========================================================= -->

<section class="home-section vendors-section">

    <div class="section-container">


        <div class="section-heading">

            <div>

                <span class="section-kicker">
                    OUR COMMUNITY
                </span>

                <h2>
                    Meet Local Vendors
                </h2>

                <p>
                    Behind every product is a local entrepreneur.
                </p>

            </div>


            <a
                href="<?php echo BASE_URL; ?>vendor.php"
                class="section-link"
            >

                View Vendors

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($featuredVendors)): ?>

            <div class="vendor-grid">

                <?php foreach (
                    $featuredVendors
                    as $vendor
                ): ?>

                    <a
                        href="<?php echo BASE_URL; ?>vendor.php?id=<?php echo (int) $vendor['vendor_id']; ?>"
                        class="vendor-card"
                    >

                        <div class="vendor-card-logo">

                            <?php if (
                                !empty(
                                    $vendor['business_logo']
                                )
                            ): ?>

                                <img
                                    src="<?php echo vendorLogoUrl($vendor['business_logo']); ?>"
                                    alt="<?php echo e($vendor['business_name']); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="vendor-logo-placeholder">

                                    <i class="fa-solid fa-store"></i>

                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="vendor-card-content">

                            <h3>

                                <?php echo e(
                                    $vendor['business_name']
                                ); ?>

                            </h3>


                            <?php if (
                                !empty(
                                    $vendor['category']
                                )
                            ): ?>

                                <span class="vendor-category">

                                    <?php echo e(
                                        $vendor['category']
                                    ); ?>

                                </span>

                            <?php endif; ?>


                            <p>

                                <?php

                                $description =
                                    trim(
                                        $vendor[
                                            'business_description'
                                        ] ?? ''
                                    );

                                if (
                                    $description === ''
                                ) {

                                    $description =
                                        'Local vendor on HochipoHub.';
                                }

                                echo e(
                                    mb_strimwidth(
                                        $description,
                                        0,
                                        90,
                                        '...'
                                    )
                                );

                                ?>

                            </p>


                            <div class="vendor-card-footer">

                                <span>

                                    <?php echo (int) $vendor['product_count']; ?>

                                    Products

                                </span>


                                <i class="fa-solid fa-arrow-right"></i>

                            </div>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="home-cta-section">

    <div class="section-container">

        <div class="home-cta">


            <div class="cta-decoration cta-decoration-one"></div>

            <div class="cta-decoration cta-decoration-two"></div>


            <div class="cta-content">

                <span class="section-kicker">
                    JOIN HOCHIPOHUB
                </span>

                <h2>
                    Got something
                    amazing to sell?
                </h2>

                <p>

                    Turn your passion into a business
                    and reach more customers through
                    HochipoHub.

                </p>


                <div class="cta-actions">

                    <?php if (
                        isLoggedIn()
                    ): ?>

                        <?php if (
                            currentUserRole()
                                === 'vendor'
                        ): ?>

                            <a
                                href="<?php echo BASE_URL; ?>dashboard.php"
                                class="btn btn-white"
                            >

                                Go to Dashboard

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>

                        <?php else: ?>

                            <a
                                href="<?php echo BASE_URL; ?>profile.php"
                                class="btn btn-white"
                            >

                                Become a Vendor

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>

                        <?php endif; ?>

                    <?php else: ?>

                        <button
                            type="button"
                            class="btn btn-white"
                            id="openRegisterModalCta"
                        >

                            Join HochipoHub

                            <i class="fa-solid fa-arrow-right"></i>

                        </button>

                    <?php endif; ?>

                </div>

            </div>


            <div class="cta-visual">

                <div class="cta-icon">

                    <i class="fa-solid fa-store"></i>

                </div>

                <div class="cta-icon-small icon-one">

                    <i class="fa-solid fa-heart"></i>

                </div>

                <div class="cta-icon-small icon-two">

                    <i class="fa-solid fa-box"></i>

                </div>

                <div class="cta-icon-small icon-three">

                    <i class="fa-solid fa-star"></i>

                </div>

            </div>

        </div>

    </div>

</section>


<?php

/*
|--------------------------------------------------------------------------
| LOGIN MODAL
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/login_modal.php';


/*
|--------------------------------------------------------------------------
| REGISTER MODAL
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/register_modal.php';


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/footer.php';

?>