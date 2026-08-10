<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR PAGE
|--------------------------------------------------------------------------
| File:
| vendor.php
|--------------------------------------------------------------------------
|
| Purpose:
| - Display all approved vendors
| - Display individual vendor profile
| - Display vendor products
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$vendorId = (int) ($_GET['id'] ?? 0);


/*
|--------------------------------------------------------------------------
| SINGLE VENDOR
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    /*
    |--------------------------------------------------------------------------
    | GET VENDOR
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT

            v.vendor_id,
            v.user_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.category,
            v.delivery_method,
            v.approval_status,

            u.name,
            u.email,
            u.phone

        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.vendor_id = ?
          AND v.approval_status = 'Approved'
          AND u.status = 'active'

        LIMIT 1
    ");

    $stmt->execute([
        $vendorId
    ]);

    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | INVALID VENDOR
    |--------------------------------------------------------------------------
    */

    if (!$vendor) {

        header(
            'Location: ' .
            BASE_URL .
            'vendor.php'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GET PRODUCTS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
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

            c.category_name

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.vendor_id = ?

          AND p.status = 'Available'

          AND p.stock_quantity > 0

        ORDER BY p.created_at DESC
    ");

    $stmt->execute([
        $vendorId
    ]);

    $products =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    $pageTitle =
        $vendor['business_name'] .
        ' - ' .
        SITE_NAME;


} else {


    /*
    |--------------------------------------------------------------------------
    | ALL VENDORS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->query("
        SELECT

            v.vendor_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.category,
            v.delivery_method

        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.approval_status = 'Approved'
          AND u.status = 'active'

        ORDER BY v.business_name ASC
    ");

    $vendors =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    $pageTitle =
        'Vendors - ' .
        SITE_NAME;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="vendor-page">


<?php if ($vendorId > 0): ?>


    <!-- =====================================================
         SINGLE VENDOR HERO
    ====================================================== -->

    <section class="vendor-detail-hero">

        <div class="vendor-detail-hero-inner">


            <a
                href="<?= e(BASE_URL) ?>vendor.php"
                class="vendor-back-link"
            >
                ← Back to Vendors
            </a>


            <div class="vendor-detail-main">


                <!-- LOGO -->

                <div class="vendor-detail-logo">

                    <?php if (
                        !empty($vendor['business_logo'])
                    ): ?>

                        <img
                            src="<?= e(
                                getVendorImage(
                                    $vendor['business_logo']
                                )
                            ) ?>"
                            alt="<?= e(
                                $vendor['business_name']
                            ) ?>"
                        >

                    <?php else: ?>

                        <div class="vendor-logo-placeholder">
                            🏪
                        </div>

                    <?php endif; ?>

                </div>


                <!-- INFORMATION -->

                <div class="vendor-detail-info">

                    <span class="small-label">
                        VERIFIED LOCAL VENDOR
                    </span>

                    <h1>

                        <?= e(
                            $vendor['business_name']
                        ) ?>

                    </h1>


                    <?php if (
                        !empty(
                            $vendor['category']
                        )
                    ): ?>

                        <span class="vendor-detail-category">

                            <?= e(
                                $vendor['category']
                            ) ?>

                        </span>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $vendor['business_description']
                        )
                    ): ?>

                        <p>

                            <?= nl2br(
                                e(
                                    $vendor[
                                        'business_description'
                                    ]
                                )
                            ) ?>

                        </p>

                    <?php endif; ?>


                    <div class="vendor-detail-meta">


                        <?php if (
                            !empty(
                                $vendor['delivery_method']
                            )
                        ): ?>

                            <span>

                                🚚

                                <strong>
                                    Delivery
                                </strong>

                                <?= e(
                                    $vendor[
                                        'delivery_method'
                                    ]
                                ) ?>

                            </span>

                        <?php endif; ?>


                        <span>

                            📦

                            <strong>
                                Products
                            </strong>

                            <?= count($products) ?>

                        </span>


                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         VENDOR PRODUCTS
    ====================================================== -->

    <section class="vendor-products-section">

        <div class="vendor-content-container">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        SHOP FROM THIS STORE
                    </span>

                    <h2>
                        <?= e(
                            $vendor['business_name']
                        ) ?>
                        Products
                    </h2>

                    <p>
                        Browse products available from this vendor.
                    </p>

                </div>

            </div>


            <?php if (!empty($products)): ?>


                <div class="vendor-product-grid">

                    <?php foreach (
                        $products
                        as $product
                    ): ?>

                        <?php

                        $productId =
                            (int)
                            $product['product_id'];

                        $productImage =
                            getProductImage(
                                $product['image']
                            );

                        ?>


                        <article class="vendor-product-card">


                            <a
                                href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                class="vendor-product-image"
                            >

                                <?php if (
                                    !empty(
                                        $product['image']
                                    )
                                ): ?>

                                    <img
                                        src="<?= e(
                                            $productImage
                                        ) ?>"
                                        alt="<?= e(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="vendor-product-placeholder">
                                        🛍️
                                    </div>

                                <?php endif; ?>

                            </a>


                            <div class="vendor-product-info">


                                <span class="vendor-product-category">

                                    <?= e(
                                        $product[
                                            'category_name'
                                        ]
                                    ) ?>

                                </span>


                                <h3>

                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                    >

                                        <?= e(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </a>

                                </h3>


                                <?php if (
                                    !empty(
                                        $product[
                                            'description'
                                        ]
                                    )
                                ): ?>

                                    <p>

                                        <?= e(
                                            mb_strimwidth(
                                                $product[
                                                    'description'
                                                ],
                                                0,
                                                90,
                                                '...'
                                            )
                                        ) ?>

                                    </p>

                                <?php endif; ?>


                                <div class="vendor-product-bottom">

                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $product[
                                                'price'
                                            ],
                                            2
                                        ) ?>

                                    </strong>


                                    <a
                                        href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                        class="vendor-view-product"
                                    >
                                        View →
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <div class="vendor-empty">

                    <div class="vendor-empty-icon">
                        📦
                    </div>

                    <span class="small-label">
                        STORE EMPTY
                    </span>

                    <h2>
                        No products available
                    </h2>

                    <p>
                        This vendor has not listed any
                        available products yet.
                    </p>

                    <a
                        href="<?= e(BASE_URL) ?>vendor.php"
                        class="btn btn-primary"
                    >
                        Browse Other Vendors
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>


<?php else: ?>


    <!-- =====================================================
         ALL VENDORS HERO
    ====================================================== -->

    <section class="vendor-hero">

        <div class="vendor-hero-inner">

            <span class="small-label">
                MARKETPLACE • LOCAL SELLERS
            </span>

            <h1>
                Meet Our
                <span>Vendors.</span>
            </h1>

            <p>
                Discover the people, businesses and creators
                behind the products on HochipoHub.
            </p>

        </div>

    </section>


    <!-- =====================================================
         VENDORS
    ====================================================== -->

    <section class="vendors-section">

        <div class="vendor-content-container">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        OUR MARKETPLACE
                    </span>

                    <h2>
                        Local Sellers
                    </h2>

                    <p>
                        Find a vendor and explore their store.
                    </p>

                </div>


                <span class="vendor-count">

                    <?= count($vendors) ?>

                    vendor<?= count($vendors) !== 1 ? 's' : '' ?>

                </span>

            </div>


            <?php if (!empty($vendors)): ?>


                <div class="vendor-marketplace-grid">

                    <?php foreach (
                        $vendors
                        as $item
                    ): ?>

                        <?php

                        $itemId =
                            (int)
                            $item['vendor_id'];

                        ?>

                        <article class="vendor-marketplace-card">


                            <!-- LOGO -->

                            <a
                                href="<?= e(BASE_URL) ?>vendor.php?id=<?= $itemId ?>"
                                class="vendor-marketplace-logo"
                            >

                                <?php if (
                                    !empty(
                                        $item[
                                            'business_logo'
                                        ]
                                    )
                                ): ?>

                                    <img
                                        src="<?= e(
                                            getVendorImage(
                                                $item[
                                                    'business_logo'
                                                ]
                                            )
                                        ) ?>"
                                        alt="<?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="vendor-marketplace-placeholder">
                                        🏪
                                    </div>

                                <?php endif; ?>

                            </a>


                            <!-- CONTENT -->

                            <div class="vendor-marketplace-body">


                                <span class="vendor-status">
                                    ● APPROVED VENDOR
                                </span>


                                <h2>

                                    <a
                                        href="<?= e(BASE_URL) ?>vendor.php?id=<?= $itemId ?>"
                                    >

                                        <?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </a>

                                </h2>


                                <?php if (
                                    !empty(
                                        $item[
                                            'category'
                                        ]
                                    )
                                ): ?>

                                    <span
                                        class="vendor-marketplace-category"
                                    >

                                        <?= e(
                                            $item[
                                                'category'
                                            ]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $item[
                                            'business_description'
                                        ]
                                    )
                                ): ?>

                                    <p>

                                        <?= e(
                                            mb_strimwidth(
                                                $item[
                                                    'business_description'
                                                ],
                                                0,
                                                130,
                                                '...'
                                            )
                                        ) ?>

                                    </p>

                                <?php else: ?>

                                    <p>
                                        Discover products
                                        from this local seller
                                        on HochipoHub.
                                    </p>

                                <?php endif; ?>


                                <div
                                    class="vendor-marketplace-footer"
                                >

                                    <?php if (
                                        !empty(
                                            $item[
                                                'delivery_method'
                                            ]
                                        )
                                    ): ?>

                                        <span>

                                            🚚

                                            <?= e(
                                                $item[
                                                    'delivery_method'
                                                ]
                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                    <a
                                        href="<?= e(BASE_URL) ?>vendor.php?id=<?= $itemId ?>"
                                        class="vendor-store-btn"
                                    >
                                        View Store →
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <div class="vendor-empty">

                    <div class="vendor-empty-icon">
                        🏪
                    </div>

                    <span class="small-label">
                        MARKETPLACE
                    </span>

                    <h2>
                        No vendors available
                    </h2>

                    <p>
                        There are currently no approved vendors
                        on HochipoHub.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =====================================================
         CTA
    ====================================================== -->

    <section class="vendor-cta">

        <div class="vendor-content-container">

            <div class="vendor-cta-content">

                <span class="small-label">
                    GROW WITH HOCHIPOHUB
                </span>

                <h2>
                    Ready to become a vendor?
                </h2>

                <p>
                    Put your products in front of more customers
                    and grow your local business with HochipoHub.
                </p>

                <a
                    href="<?= e(BASE_URL) ?>dashboard.php"
                    class="btn btn-primary"
                >
                    Start Selling →
                </a>

            </div>

        </div>

    </section>


<?php endif; ?>


</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>


<script src="<?= e(BASE_URL) ?>js/script.js"></script>
<script src="<?= e(BASE_URL) ?>js/cart.js"></script>
<script src="<?= e(BASE_URL) ?>js/wishlist.js"></script>

</body>
</html>