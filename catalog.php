<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| CATALOG DATA
|--------------------------------------------------------------------------
|
| Products:
| - product_id
| - product_name
| - vendor_id
| - category_id
| - price
| - image
| - description
| - stock_quantity
| - status
| - created_at
|
| Products are joined with:
| - vendors
| - categories
|--------------------------------------------------------------------------
*/

$products = [];

try {

    $stmt = $db->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.vendor_id,
            p.category_id,
            p.price,
            p.image,
            p.description,
            p.stock_quantity,
            p.status,
            p.created_at,

            v.business_name AS vendor_name,
            c.category_name

        FROM products p

        LEFT JOIN vendors v
            ON p.vendor_id = v.vendor_id

        LEFT JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.status = 'Available'

        ORDER BY p.created_at DESC
    ");

    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    if (APP_DEBUG) {

        die(
            '<div style="
                font-family:Arial,sans-serif;
                padding:30px;
                background:#020617;
                color:#fff;
                min-height:100vh;
            ">
                <h2 style="
                    color:#60a5fa;
                    margin-top:0;
                ">
                    HochipoHub Catalog Error
                </h2>

                <p style="
                    color:#cbd5e1;
                ">
                    Unable to load products.
                </p>

                <pre style="
                    background:#0f172a;
                    padding:20px;
                    border-radius:15px;
                    overflow:auto;
                    color:#f87171;
                ">'
                . e($e->getMessage())
                . '</pre>
            </div>'
        );
    }

    $products = [];
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
        Product Catalog | <?= e(APP_NAME) ?>
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

        /*
        |--------------------------------------------------------------------------
        | CATALOG PAGE
        |--------------------------------------------------------------------------
        */

        .catalog-page {
            min-height: 100vh;
            padding: 35px 4% 60px;

            background:
                radial-gradient(
                    circle at 5% 10%,
                    rgba(37,99,235,.14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 20%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                #f8fbff;
        }

        .catalog-container {
            max-width: 1450px;
            margin: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .catalog-hero {
            position: relative;
            overflow: hidden;

            margin-bottom: 30px;
            padding: 38px;

            border-radius: 30px;

            background:
                linear-gradient(
                    135deg,
                    #020617 0%,
                    #0f2b69 42%,
                    #2563eb 72%,
                    #0284c7 100%
                );

            color: #fff;

            box-shadow:
                0 25px 70px
                rgba(30,64,175,.25);
        }

        .catalog-hero::before {
            content: "";

            position: absolute;

            width: 350px;
            height: 350px;

            top: -210px;
            right: -80px;

            border-radius: 50%;

            background:
                rgba(96,165,250,.16);
        }

        .catalog-hero::after {
            content: "";

            position: absolute;

            width: 230px;
            height: 230px;

            right: 180px;
            bottom: -180px;

            border-radius: 50%;

            background:
                rgba(56,189,248,.10);
        }

        .catalog-hero-content {
            position: relative;
            z-index: 2;
        }

        .catalog-kicker {
            margin-bottom: 8px;

            color:
                rgba(255,255,255,.62);

            font-size: 10px;
            font-weight: 950;

            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .catalog-hero h1 {
            margin: 0 0 10px;

            font-size:
                clamp(
                    30px,
                    5vw,
                    48px
                );

            line-height: 1.05;
            font-weight: 950;
        }

        .catalog-hero p {
            max-width: 650px;

            margin: 0;

            color:
                rgba(255,255,255,.75);

            font-size: 12px;
            line-height: 1.7;
        }

        .catalog-count {
            display: inline-flex;

            margin-top: 20px;
            padding: 8px 13px;

            border:
                1px solid
                rgba(255,255,255,.18);

            border-radius: 999px;

            background:
                rgba(255,255,255,.08);

            color: #dbeafe;

            font-size: 8px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | SECTION HEADER
        |--------------------------------------------------------------------------
        */

        .catalog-section-header {
            display: flex;

            align-items: flex-end;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 18px;
        }

        .catalog-section-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 19px;
            font-weight: 950;
        }

        .catalog-section-header span {
            color: #64748b;

            font-size: 9px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT GRID
        |--------------------------------------------------------------------------
        */

        .catalog-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCT CARD
        |--------------------------------------------------------------------------
        */

        .catalog-card {
            position: relative;

            overflow: hidden;

            border:
                1px solid #dbeafe;

            border-radius: 22px;

            background: #fff;

            box-shadow:
                0 12px 35px
                rgba(15,23,42,.055);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }

        .catalog-card:hover {
            transform:
                translateY(-7px);

            border-color:
                #93c5fd;

            box-shadow:
                0 22px 50px
                rgba(37,99,235,.14);
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        .catalog-image-wrap {
            position: relative;

            overflow: hidden;

            height: 220px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );
        }

        .catalog-image {
            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;

            transition:
                transform .35s ease;
        }

        .catalog-card:hover
        .catalog-image {
            transform: scale(1.06);
        }

        .catalog-image-placeholder {
            width: 100%;
            height: 100%;

            display: flex;

            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #bfdbfe
                );

            color: #2563eb;

            font-size: 38px;
            font-weight: 950;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS TAG
        |--------------------------------------------------------------------------
        */

        .available-tag {
            position: absolute;

            top: 12px;
            left: 12px;

            padding: 6px 9px;

            border-radius: 999px;

            background:
                rgba(255,255,255,.92);

            color: #166534;

            font-size: 7px;
            font-weight: 950;

            text-transform: uppercase;

            box-shadow:
                0 5px 15px
                rgba(15,23,42,.08);
        }

        /*
        |--------------------------------------------------------------------------
        | CARD CONTENT
        |--------------------------------------------------------------------------
        */

        .catalog-content {
            padding: 17px;
        }

        .catalog-category {
            margin-bottom: 7px;

            color: #2563eb;

            font-size: 7px;
            font-weight: 950;

            letter-spacing: .7px;

            text-transform: uppercase;
        }

        .catalog-product-name {
            min-height: 42px;

            margin: 0 0 7px;

            color: #0f172a;

            font-size: 14px;
            line-height: 1.35;

            font-weight: 950;
        }

        .catalog-vendor {
            display: flex;

            align-items: center;

            gap: 6px;

            margin-bottom: 14px;

            color: #64748b;

            font-size: 8px;
            font-weight: 750;
        }

        .vendor-dot {
            width: 6px;
            height: 6px;

            flex-shrink: 0;

            border-radius: 50%;

            background:
                #3b82f6;
        }

        /*
        |--------------------------------------------------------------------------
        | PRICE
        |--------------------------------------------------------------------------
        */

        .catalog-bottom {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 10px;

            padding-top: 13px;

            border-top:
                1px solid #eff6ff;
        }

        .catalog-price {
            color: #1d4ed8;

            font-size: 17px;
            font-weight: 950;
        }

        .catalog-price-label {
            display: block;

            margin-bottom: 2px;

            color: #94a3b8;

            font-size: 7px;
            font-weight: 800;

            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | VIEW BUTTON
        |--------------------------------------------------------------------------
        */

        .view-product-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-width: 95px;

            padding: 9px 12px;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0284c7
                );

            color: white;

            text-decoration: none;

            font-size: 8px;
            font-weight: 950;

            box-shadow:
                0 7px 18px
                rgba(37,99,235,.18);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .view-product-btn:hover {
            transform:
                translateY(-2px);

            box-shadow:
                0 11px 25px
                rgba(37,99,235,.25);
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .catalog-empty {
            padding: 70px 20px;

            border:
                1px solid #dbeafe;

            border-radius: 25px;

            background: #fff;

            text-align: center;

            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .catalog-empty-icon {
            margin-bottom: 12px;

            font-size: 45px;
        }

        .catalog-empty h3 {
            margin: 0 0 7px;

            color: #334155;

            font-size: 16px;
            font-weight: 950;
        }

        .catalog-empty p {
            margin: 0;

            color: #94a3b8;

            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1150px) {

            .catalog-grid {
                grid-template-columns:
                    repeat(
                        3,
                        minmax(0, 1fr)
                    );
            }

        }

        @media (max-width: 800px) {

            .catalog-page {
                padding:
                    25px 15px 50px;
            }

            .catalog-hero {
                padding: 28px 23px;
            }

            .catalog-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

                gap: 13px;
            }

            .catalog-image-wrap {
                height: 190px;
            }

        }

        @media (max-width: 520px) {

            .catalog-grid {
                grid-template-columns: 1fr;
            }

            .catalog-image-wrap {
                height: 230px;
            }

            .catalog-section-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/includes/navbar.php';
?>


<main class="catalog-page">

    <div class="catalog-container">


        <!-- HERO -->

        <section class="catalog-hero">

            <div class="catalog-hero-content">

                <div class="catalog-kicker">
                    HochipoHub Marketplace
                </div>

                <h1>
                    Product Catalog
                </h1>

                <p>
                    Discover products from
                    different vendors and find
                    something worth adding to
                    your cart.
                </p>

                <div class="catalog-count">

                    <?= number_format(
                        count($products)
                    ) ?>

                    &nbsp; products available

                </div>

            </div>

        </section>


        <!-- SECTION HEADER -->

        <div class="catalog-section-header">

            <div>

                <h2>
                    Explore Products
                </h2>

            </div>

            <span>
                Latest products first
            </span>

        </div>


        <!-- PRODUCTS -->

        <?php if (
            !empty($products)
        ): ?>

            <div class="catalog-grid">

                <?php foreach (
                    $products
                    as $product
                ): ?>

                    <?php

                    $productImage =
                        productImageUrl(
                            $product['image']
                                ?? null
                        );

                    $vendorName =
                        !empty(
                            $product[
                                'vendor_name'
                            ]
                        )
                        ? $product[
                            'vendor_name'
                        ]
                        : 'HochipoHub Vendor';

                    $categoryName =
                        !empty(
                            $product[
                                'category_name'
                            ]
                        )
                        ? $product[
                            'category_name'
                        ]
                        : 'Product';

                    ?>

                    <article
                        class="catalog-card"
                    >


                        <!-- IMAGE -->

                        <div
                            class="
                                catalog-image-wrap
                            "
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
                                    class="
                                        catalog-image
                                    "
                                    loading="lazy"
                                    onerror="
                                        this.style.display='none';
                                        this.nextElementSibling.style.display='flex';
                                    "
                                >

                                <div
                                    class="
                                        catalog-image-placeholder
                                    "
                                    style="
                                        display:none;
                                    "
                                >
                                    🛍️
                                </div>

                            <?php else: ?>

                                <div
                                    class="
                                        catalog-image-placeholder
                                    "
                                >
                                    🛍️
                                </div>

                            <?php endif; ?>


                            <span
                                class="available-tag"
                            >
                                Available
                            </span>

                        </div>


                        <!-- CONTENT -->

                        <div
                            class="catalog-content"
                        >

                            <div
                                class="
                                    catalog-category
                                "
                            >
                                <?= e(
                                    $categoryName
                                ) ?>
                            </div>


                            <h3
                                class="
                                    catalog-product-name
                                "
                            >
                                <?= e(
                                    $product[
                                        'product_name'
                                    ]
                                ) ?>
                            </h3>


                            <div
                                class="
                                    catalog-vendor
                                "
                            >

                                <span
                                    class="vendor-dot"
                                ></span>

                                <span>
                                    <?= e(
                                        $vendorName
                                    ) ?>
                                </span>

                            </div>


                            <div
                                class="
                                    catalog-bottom
                                "
                            >

                                <div>

                                    <span
                                        class="
                                            catalog-price-label
                                        "
                                    >
                                        Price
                                    </span>

                                    <div
                                        class="
                                            catalog-price
                                        "
                                    >
                                        <?= formatPrice(
                                            $product[
                                                'price'
                                            ]
                                        ) ?>
                                    </div>

                                </div>


                                <a
                                    href="<?= BASE_URL ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                    class="
                                        view-product-btn
                                    "
                                >
                                    View Product
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>


            <!-- EMPTY -->

            <div
                class="catalog-empty"
            >

                <div
                    class="
                        catalog-empty-icon
                    "
                >
                    🛍️
                </div>

                <h3>
                    No products available
                </h3>

                <p>
                    There are currently no
                    available products in the
                    marketplace.
                </p>

            </div>


        <?php endif; ?>

    </div>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>

</body>

</html>