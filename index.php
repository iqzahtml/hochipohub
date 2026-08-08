<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - HOMEPAGE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = 'Home';

$pageDescription =
    'Discover local products, support local vendors and shop your way on HochipoHub.';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function homeProductImage(
    ?string $image
): string {

    if (
        empty($image)
    ) {

        return BASE_URL
            . 'image/product/default-product.jpg';
    }

    return PRODUCT_IMAGE_URL
        . rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| FEATURED PRODUCTS
|--------------------------------------------------------------------------
|
| Only products belonging to approved vendors
| and available products are displayed.
|
*/

$featuredProducts = [];

try {

    $stmt = $db->prepare("
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

        WHERE
            p.status = 'Available'
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
            v.vendor_id,
            v.business_name,
            v.business_logo,
            c.category_id,
            c.category_name

        ORDER BY
            p.created_at DESC

        LIMIT 8
    ");

    $stmt->execute();

    $featuredProducts =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $featuredProducts = [];
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

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

    $categories =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $categories = [];
}


/*
|--------------------------------------------------------------------------
| POPULAR VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];

try {

    $stmt = $db->query("
        SELECT
            v.vendor_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.category,

            COUNT(
                DISTINCT p.product_id
            ) AS product_count

        FROM vendors v

        LEFT JOIN products p
            ON v.vendor_id = p.vendor_id
            AND p.status = 'Available'

        WHERE
            v.approval_status = 'Approved'

        GROUP BY
            v.vendor_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.category

        ORDER BY
            product_count DESC,
            v.business_name ASC

        LIMIT 4
    ");

    $vendors =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $vendors = [];
}


/*
|--------------------------------------------------------------------------
| INCLUDE HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

?>

<!-- =========================================================
     HERO SECTION
========================================================= -->

<section class="hero-section">

    <div class="hero-background">

        <div class="hero-glow hero-glow-one"></div>

        <div class="hero-glow hero-glow-two"></div>

        <div class="hero-grid"></div>

    </div>


    <div class="container hero-container">


        <!-- =================================================
             HERO CONTENT
        ================================================== -->

        <div class="hero-content">

            <div class="hero-badge">

                <span class="hero-badge-dot"></span>

                Malaysia's Local Marketplace

                <i class="fa-solid fa-arrow-trend-up"></i>

            </div>


            <h1>

                Shop Local.

                <span>
                    Shop Different.
                </span>

            </h1>


            <p>

                Discover products from local vendors,
                find hidden gems and support businesses
                that deserve the spotlight.

            </p>


            <div class="hero-actions">

                <a
                    href="<?php echo BASE_URL; ?>catalog.php"
                    class="btn btn-primary hero-primary-btn"
                >

                    Explore Products

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


                <a
                    href="<?php echo BASE_URL; ?>vendor.php"
                    class="btn btn-outline hero-secondary-btn"
                >

                    <i class="fa-solid fa-store"></i>

                    Meet Our Vendors

                </a>

            </div>


            <!-- HERO STATS -->

            <div class="hero-stats">

                <div class="hero-stat">

                    <strong>
                        <?php
                        echo number_format(
                            count($featuredProducts)
                        );
                        ?>+
                    </strong>

                    <span>
                        Featured Products
                    </span>

                </div>


                <div class="hero-stat-divider"></div>


                <div class="hero-stat">

                    <strong>
                        <?php
                        echo number_format(
                            count($vendors)
                        ); 
                        ?>+
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
                        Local Spirit
                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             HERO VISUAL
        ================================================== -->

        <div class="hero-visual">


            <div class="hero-orbit orbit-one"></div>

            <div class="hero-orbit orbit-two"></div>


            <div class="hero-main-card">

                <div class="hero-card-top">

                    <span>
                        TRENDING NOW
                    </span>

                    <i class="fa-solid fa-fire"></i>

                </div>


                <div class="hero-product-showcase">

                    <div class="hero-product-icon">

                        <i class="fa-solid fa-bag-shopping"></i>

                    </div>

                </div>


                <div class="hero-card-info">

                    <span>
                        Local Finds
                    </span>

                    <strong>
                        Made. Found. Loved.
                    </strong>

                </div>


                <div class="hero-card-footer">

                    <span>
                        Explore the hub
                    </span>

                    <i class="fa-solid fa-arrow-up-right-from-square"></i>

                </div>

            </div>


            <!-- FLOATING CARD 1 -->

            <div class="floating-card floating-card-one">

                <div class="floating-icon">

                    <i class="fa-solid fa-bolt"></i>

                </div>

                <div>

                    <strong>
                        Discover
                    </strong>

                    <span>
                        Something new
                    </span>

                </div>

            </div>


            <!-- FLOATING CARD 2 -->

            <div class="floating-card floating-card-two">

                <div class="floating-icon">

                    <i class="fa-solid fa-heart"></i>

                </div>

                <div>

                    <strong>
                        Support Local
                    </strong>

                    <span>
                        Shop with purpose
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

    <div class="container">


        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    EXPLORE
                </span>

                <h2>
                    Find your <span>thing.</span>
                </h2>

            </div>


            <a
                href="<?php echo BASE_URL; ?>category.php"
                class="section-link"
            >

                View all

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="category-grid">

            <?php if (
                !empty($categories)
            ): ?>


                <?php foreach (
                    $categories
                    as $category
                ): ?>

                    <a
                        href="<?php echo BASE_URL; ?>catalog.php?category=<?php echo (int) $category['category_id']; ?>"
                        class="category-card"
                    >

                        <div class="category-card-image">

                            <?php if (
                                !empty(
                                    $category[
                                        'category_image'
                                    ]
                                )
                            ): ?>

                                <img
                                    src="<?php echo IMAGE_URL . htmlspecialchars(
                                        $category[
                                            'category_image'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    alt="<?php echo htmlspecialchars(
                                        $category[
                                            'category_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >

                            <?php else: ?>

                                <div class="category-placeholder">

                                    <i class="fa-solid fa-layer-group"></i>

                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="category-card-content">

                            <h3>

                                <?php echo htmlspecialchars(
                                    $category[
                                        'category_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </h3>


                            <span>

                                Explore

                                <i class="fa-solid fa-arrow-right"></i>

                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-home-state">

                    <i class="fa-solid fa-layer-group"></i>

                    <h3>
                        Categories coming soon
                    </h3>

                    <p>
                        New local categories will appear here.
                    </p>

                </div>


            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================================================
     FEATURED PRODUCTS
========================================================= -->

<section class="home-section products-section">

    <div class="container">


        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    FRESH FINDS
                </span>

                <h2>
                    Worth adding to <span>cart.</span>
                </h2>

            </div>


            <a
                href="<?php echo BASE_URL; ?>catalog.php"
                class="section-link"
            >

                Browse all

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="product-grid">

            <?php if (
                !empty($featuredProducts)
            ): ?>


                <?php foreach (
                    $featuredProducts
                    as $product
                ): ?>


                    <article class="product-card">


                        <!-- PRODUCT IMAGE -->

                        <a
                            href="<?php echo BASE_URL; ?>product_details.php?id=<?php echo (int) $product['product_id']; ?>"
                            class="product-card-image"
                        >

                            <?php if (
                                !empty(
                                    $product['image']
                                )
                            ): ?>

                                <img
                                    src="<?php echo homeProductImage(
                                        $product['image']
                                    ); ?>"
                                    alt="<?php echo htmlspecialchars(
                                        $product['product_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >

                            <?php else: ?>

                                <div class="product-image-placeholder">

                                    <i class="fa-solid fa-box-open"></i>

                                </div>

                            <?php endif; ?>


                            <span class="product-category-tag">

                                <?php echo htmlspecialchars(
                                    $product['category_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>


                            <button
                                type="button"
                                class="product-wishlist-btn"
                                data-product-id="<?php echo (int) $product['product_id']; ?>"
                                aria-label="Add to wishlist"
                            >

                                <i class="fa-regular fa-heart"></i>

                            </button>

                        </a>


                        <!-- PRODUCT INFO -->

                        <div class="product-card-body">


                            <a
                                href="<?php echo BASE_URL; ?>product_details.php?id=<?php echo (int) $product['product_id']; ?>"
                                class="product-name"
                            >

                                <?php echo htmlspecialchars(
                                    $product['product_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </a>


                            <a
                                href="<?php echo BASE_URL; ?>vendor.php?id=<?php echo (int) $product['vendor_id']; ?>"
                                class="product-vendor"
                            >

                                <i class="fa-solid fa-store"></i>

                                <?php echo htmlspecialchars(
                                    $product['business_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </a>


                            <div class="product-rating">

                                <span class="rating-stars">

                                    <?php

                                    $rating =
                                        (float)
                                        $product[
                                            'average_rating'
                                        ];

                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ):

                                    ?>

                                        <?php if (
                                            $i <=
                                            round($rating)
                                        ): ?>

                                            <i class="fa-solid fa-star"></i>

                                        <?php else: ?>

                                            <i class="fa-regular fa-star"></i>

                                        <?php endif; ?>

                                    <?php endfor; ?>

                                </span>


                                <span>

                                    <?php echo number_format(
                                        $rating,
                                        1
                                    ); ?>

                                    (
                                    <?php echo (int) $product[
                                        'review_count'
                                    ]; ?>
                                    )

                                </span>

                            </div>


                            <div class="product-card-bottom">

                                <strong class="product-price">

                                    <?php echo formatPrice(
                                        $product['price']
                                    ); ?>

                                </strong>


                                <button
                                    type="button"
                                    class="product-add-btn"
                                    data-product-id="<?php echo (int) $product['product_id']; ?>"
                                    aria-label="Add to cart"
                                >

                                    <i class="fa-solid fa-plus"></i>

                                </button>

                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-home-state">

                    <i class="fa-solid fa-box-open"></i>

                    <h3>
                        No products yet
                    </h3>

                    <p>
                        Local vendors will start appearing here
                        once products are available.
                    </p>

                </div>


            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================================================
     WHY HOCHIPOHUB
========================================================= -->

<section class="home-section why-section">

    <div class="container">

        <div class="why-wrapper">


            <div class="why-content">

                <span class="section-eyebrow">
                    WHY HOCHIPOHUB?
                </span>


                <h2>

                    More than a marketplace.

                    <span>
                        It's a local movement.
                    </span>

                </h2>


                <p>

                    From small businesses to hidden local gems,
                    HochipoHub brings them into one place so
                    discovering something good doesn't have
                    to be complicated.

                </p>


                <a
                    href="<?php echo BASE_URL; ?>catalog.php"
                    class="btn btn-primary"
                >

                    Start Exploring

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>


            <div class="why-features">


                <div class="why-feature-card">

                    <div class="why-feature-icon">

                        <i class="fa-solid fa-store"></i>

                    </div>

                    <h3>
                        Local Vendors
                    </h3>

                    <p>
                        Discover products directly from
                        approved local businesses.
                    </p>

                </div>


                <div class="why-feature-card">

                    <div class="why-feature-icon">

                        <i class="fa-solid fa-shield-halved"></i>

                    </div>

                    <h3>
                        Safer Shopping
                    </h3>

                    <p>
                        Vendor approval and order tracking
                        keep the marketplace organised.
                    </p>

                </div>


                <div class="why-feature-card">

                    <div class="why-feature-icon">

                        <i class="fa-solid fa-bolt"></i>

                    </div>

                    <h3>
                        Easy Discovery
                    </h3>

                    <p>
                        Search, browse categories and find
                        products without the usual hassle.
                    </p>

                </div>


                <div class="why-feature-card">

                    <div class="why-feature-icon">

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

    </div>

</section>


<!-- =========================================================
     VENDOR SECTION
========================================================= -->

<section class="home-section vendor-section">

    <div class="container">


        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    MEET THE SELLERS
                </span>

                <h2>
                    Shops worth <span>knowing.</span>
                </h2>

            </div>


            <a
                href="<?php echo BASE_URL; ?>vendor.php"
                class="section-link"
            >

                View vendors

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>


        <div class="vendor-grid">

            <?php if (
                !empty($vendors)
            ): ?>


                <?php foreach (
                    $vendors
                    as $vendor
                ): ?>

                    <a
                        href="<?php echo BASE_URL; ?>vendor.php?id=<?php echo (int) $vendor['vendor_id']; ?>"
                        class="vendor-card"
                    >

                        <div class="vendor-card-logo">

                            <?php if (
                                !empty(
                                    $vendor[
                                        'business_logo'
                                    ]
                                )
                            ): ?>

                                <img
                                    src="<?php echo vendorImageUrl(
                                        $vendor[
                                            'business_logo'
                                        ]
                                    ); ?>"
                                    alt="<?php echo htmlspecialchars(
                                        $vendor[
                                            'business_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >

                            <?php else: ?>

                                <span>

                                    <?php

                                    echo strtoupper(
                                        substr(
                                            $vendor[
                                                'business_name'
                                            ],
                                            0,
                                            1
                                        )
                                    );

                                    ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="vendor-card-info">

                            <h3>

                                <?php echo htmlspecialchars(
                                    $vendor[
                                        'business_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </h3>


                            <p>

                                <?php echo htmlspecialchars(
                                    $vendor[
                                        'category'
                                    ]
                                    ?: 'Local Business',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </p>


                            <span>

                                <?php echo (int) $vendor[
                                    'product_count'
                                ]; ?>

                                products

                                <i class="fa-solid fa-arrow-right"></i>

                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-home-state">

                    <i class="fa-solid fa-store"></i>

                    <h3>
                        Vendors coming soon
                    </h3>

                    <p>
                        Approved vendors will appear here.
                    </p>

                </div>


            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="home-cta">

    <div class="container">

        <div class="cta-box">

            <div class="cta-decoration cta-decoration-one"></div>

            <div class="cta-decoration cta-decoration-two"></div>


            <div class="cta-content">

                <span class="section-eyebrow">
                    READY?
                </span>


                <h2>
                    Your next favourite find
                    is probably <span>here.</span>
                </h2>


                <p>
                    Browse local products and discover
                    something worth adding to your world.
                </p>


                <a
                    href="<?php echo BASE_URL; ?>catalog.php"
                    class="btn btn-white"
                >

                    Shop HochipoHub

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>

        </div>

    </div>

</section>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';

?>