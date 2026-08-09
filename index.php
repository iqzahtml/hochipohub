<?php
// =========================================================
// HOCHIPO HUB
// File: index.php
// Main landing page
// =========================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = $conn ?? $pdo ?? null;

if (!$db) {
    die("Database connection not found.");
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? '';
$user_role = $_SESSION['role'] ?? 'customer';

$categories = [];
$products = [];
$vendors = [];

// =========================================================
// LOAD CATEGORIES
// =========================================================

try {

    $sql = "
        SELECT
            category_id,
            category_name,
            category_image
        FROM categories
        ORDER BY category_name ASC
        LIMIT 8
    ";

    if ($db instanceof PDO) {

        $stmt = $db->query($sql);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        $result = $db->query($sql);

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

} catch (Throwable $e) {
    $categories = [];
}


// =========================================================
// LOAD FEATURED PRODUCTS
// =========================================================

try {

    $sql = "
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

            c.category_name

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        LEFT JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.status = 'Available'
          AND p.stock_quantity > 0
          AND v.approval_status = 'Approved'

        ORDER BY p.created_at DESC

        LIMIT 8
    ";

    if ($db instanceof PDO) {

        $stmt = $db->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        $result = $db->query($sql);

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

} catch (Throwable $e) {
    $products = [];
}


// =========================================================
// LOAD APPROVED VENDORS
// =========================================================

try {

    $sql = "
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
    ";

    if ($db instanceof PDO) {

        $stmt = $db->query($sql);
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        $result = $db->query($sql);

        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }
    }

} catch (Throwable $e) {
    $vendors = [];
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

    <title>HochipoHub | Discover. Shop. Support Local.</title>

    <meta
        name="description"
        content="HochipoHub - A local marketplace connecting customers with local vendors."
    >

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >

    <style>

        /* =====================================================
           HOCHIPO HUB HOME PAGE
           GEN Z BLUE MARKETPLACE
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Inter,
                Poppins,
                Arial,
                sans-serif;

            background: #f5f8ff;
            color: #10265e;
        }

        a {
            text-decoration: none;
        }

        .home-page {
            overflow: hidden;
        }

        /* =====================================================
           HERO
        ===================================================== */

        .home-hero {
            position: relative;
            min-height: 620px;

            display: flex;
            align-items: center;

            padding:
                80px 7%;

            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(0, 210, 255, .35),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 10% 80%,
                    rgba(50, 90, 255, .35),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #04133e,
                    #063a9e 55%,
                    #007dff
                );

            color: white;
        }

        .home-hero::before {
            content: "";

            position: absolute;

            width: 420px;
            height: 420px;

            right: -130px;
            bottom: -180px;

            border-radius: 50%;

            border:
                60px solid
                rgba(255,255,255,.06);
        }

        .hero-content {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 1250px;

            margin: auto;
        }

        .hero-small-title {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding:
                8px 14px;

            margin-bottom: 20px;

            border:
                1px solid
                rgba(255,255,255,.2);

            border-radius: 999px;

            background:
                rgba(255,255,255,.08);

            backdrop-filter: blur(10px);

            font-size: 13px;
            font-weight: 700;

            color:
                rgba(255,255,255,.9);
        }

        .hero-content h1 {
            max-width: 760px;

            margin: 0;

            font-size:
                clamp(44px, 7vw, 82px);

            line-height: .98;

            letter-spacing:
                -4px;

            font-weight: 900;
        }

        .hero-content h1 span {
            color: #62ddff;
        }

        .hero-content p {
            max-width: 620px;

            margin:
                25px 0 30px;

            color:
                rgba(255,255,255,.78);

            font-size: 17px;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 52px;

            padding:
                0 24px;

            border-radius: 15px;

            font-weight: 800;

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .hero-btn:hover {
            transform:
                translateY(-3px);
        }

        .hero-btn-primary {
            background: white;
            color: #0754d8;

            box-shadow:
                0 12px 30px
                rgba(0,0,0,.18);
        }

        .hero-btn-secondary {
            color: white;

            border:
                1px solid
                rgba(255,255,255,.25);

            background:
                rgba(255,255,255,.08);

            backdrop-filter:
                blur(10px);
        }

        /* =====================================================
           HERO FLOATING CARD
        ===================================================== */

        .hero-card {
            position: absolute;

            right: 7%;
            bottom: 70px;

            z-index: 3;

            width: 280px;

            padding: 22px;

            border:
                1px solid
                rgba(255,255,255,.18);

            border-radius: 25px;

            background:
                rgba(255,255,255,.11);

            backdrop-filter:
                blur(18px);

            box-shadow:
                0 25px 70px
                rgba(0,0,0,.2);
        }

        .hero-card-top {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 18px;
        }

        .hero-card-icon {
            width: 45px;
            height: 45px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            background:
                rgba(255,255,255,.15);

            font-size: 21px;
        }

        .hero-card-label {
            color:
                rgba(255,255,255,.65);

            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .hero-card-value {
            color: white;

            font-size: 18px;
            font-weight: 800;
        }

        .hero-card-line {
            height: 1px;

            margin:
                16px 0;

            background:
                rgba(255,255,255,.12);
        }

        .hero-card-text {
            color:
                rgba(255,255,255,.7);

            font-size: 13px;
            line-height: 1.5;
        }

        /* =====================================================
           SECTIONS
        ===================================================== */

        .home-section {
            max-width: 1250px;

            margin:
                auto;

            padding:
                75px 25px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: end;

            gap: 20px;

            margin-bottom: 28px;
        }

        .section-heading h2 {
            margin: 0;

            font-size: 31px;

            letter-spacing:
                -.8px;

            color: #10265e;
        }

        .section-heading p {
            margin:
                8px 0 0;

            color: #7b88a1;

            font-size: 14px;
        }

        .section-link {
            color: #0868ff;

            font-size: 13px;
            font-weight: 800;
        }

        /* =====================================================
           CATEGORY
        ===================================================== */

        .category-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 17px;
        }

        .category-card {
            position: relative;

            min-height: 145px;

            overflow: hidden;

            padding: 23px;

            border-radius: 22px;

            background: white;

            border:
                1px solid
                rgba(20,65,160,.08);

            box-shadow:
                0 12px 35px
                rgba(30,65,130,.07);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .category-card:hover {
            transform:
                translateY(-6px);

            box-shadow:
                0 20px 45px
                rgba(0,80,220,.13);
        }

        .category-card::after {
            content: "";

            position: absolute;

            width: 100px;
            height: 100px;

            right: -40px;
            bottom: -45px;

            border-radius: 50%;

            background:
                #e8f2ff;
        }

        .category-image {
            width: 55px;
            height: 55px;

            object-fit: cover;

            border-radius: 16px;

            background: #eaf2ff;

            margin-bottom: 15px;
        }

        .category-placeholder {
            width: 55px;
            height: 55px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 15px;

            border-radius: 16px;

            background:
                linear-gradient(
                    135deg,
                    #e9f2ff,
                    #d8e8ff
                );

            color: #0868ff;

            font-size: 22px;
            font-weight: 900;
        }

        .category-card h3 {
            position: relative;
            z-index: 2;

            margin: 0;

            color: #10265e;

            font-size: 15px;
        }

        /* =====================================================
           PRODUCTS
        ===================================================== */

        .product-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;
        }

        .home-product {
            overflow: hidden;

            border-radius: 22px;

            background: white;

            border:
                1px solid
                rgba(20,65,160,.08);

            box-shadow:
                0 12px 35px
                rgba(30,65,130,.07);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .home-product:hover {
            transform:
                translateY(-6px);

            box-shadow:
                0 22px 45px
                rgba(0,80,220,.13);
        }

        .product-image-wrap {
            position: relative;

            height: 220px;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #eaf2ff,
                    #dcecff
                );
        }

        .product-image {
            width: 100%;
            height: 100%;

            object-fit: cover;

            transition:
                transform .3s ease;
        }

        .home-product:hover
        .product-image {
            transform:
                scale(1.06);
        }

        .product-tag {
            position: absolute;

            top: 13px;
            left: 13px;

            padding:
                6px 10px;

            border-radius: 999px;

            background:
                rgba(255,255,255,.92);

            color: #0868ff;

            font-size: 10px;
            font-weight: 900;

            text-transform: uppercase;
        }

        .product-info {
            padding: 18px;
        }

        .product-category {
            color: #8090aa;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
        }

        .product-info h3 {
            margin:
                7px 0;

            color: #10265e;

            font-size: 16px;
        }

        .product-vendor {
            color: #7c89a2;

            font-size: 12px;
        }

        .product-bottom {
            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-top: 16px;
        }

        .product-price {
            color: #0759dc;

            font-size: 18px;
            font-weight: 900;
        }

        .product-view {
            width: 35px;
            height: 35px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: #eaf2ff;

            color: #0868ff;

            font-weight: 900;
        }

        /* =====================================================
           VENDORS
        ===================================================== */

        .vendor-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;
        }

        .vendor-card {
            display: flex;
            align-items: center;

            gap: 15px;

            padding: 20px;

            border-radius: 20px;

            background: white;

            border:
                1px solid
                rgba(20,65,160,.08);

            box-shadow:
                0 10px 30px
                rgba(30,65,130,.06);

            transition:
                transform .2s ease;
        }

        .vendor-card:hover {
            transform:
                translateY(-4px);
        }

        .vendor-logo {
            width: 62px;
            height: 62px;

            flex-shrink: 0;

            border-radius: 17px;

            object-fit: cover;

            background:
                linear-gradient(
                    135deg,
                    #dceaff,
                    #eff6ff
                );
        }

        .vendor-info h3 {
            margin: 0 0 5px;

            color: #10265e;

            font-size: 15px;
        }

        .vendor-info p {
            margin: 0;

            color: #8491aa;

            font-size: 12px;

            line-height: 1.5;
        }

        .vendor-pill {
            display: inline-block;

            margin-top: 8px;

            padding:
                5px 9px;

            border-radius: 999px;

            background: #eaf2ff;

            color: #0868ff;

            font-size: 10px;
            font-weight: 800;
        }

        /* =====================================================
           CTA
        ===================================================== */

        .home-cta {
            position: relative;

            overflow: hidden;

            margin:
                10px auto 70px;

            max-width: 1200px;

            padding:
                55px 60px;

            border-radius: 30px;

            background:
                linear-gradient(
                    135deg,
                    #06194b,
                    #0758d9,
                    #008cff
                );

            color: white;

            box-shadow:
                0 25px 60px
                rgba(0,75,190,.2);
        }

        .home-cta::before {
            content: "";

            position: absolute;

            width: 280px;
            height: 280px;

            right: -90px;
            top: -130px;

            border-radius: 50%;

            border:
                55px solid
                rgba(255,255,255,.08);
        }

        .home-cta h2 {
            position: relative;

            margin: 0 0 10px;

            font-size: 32px;
        }

        .home-cta p {
            position: relative;

            max-width: 600px;

            margin:
                0 0 25px;

            color:
                rgba(255,255,255,.75);

            line-height: 1.6;
        }

        .cta-btn {
            position: relative;

            display: inline-flex;

            padding:
                13px 20px;

            border-radius: 13px;

            background: white;

            color: #0759dc;

            font-weight: 900;
        }

        /* =====================================================
           EMPTY
        ===================================================== */

        .home-empty {
            padding: 50px 20px;

            text-align: center;

            border-radius: 22px;

            background: white;

            color: #7b88a1;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .hero-card {
                display: none;
            }

            .category-grid,
            .product-grid {
                grid-template-columns:
                    repeat(3, 1fr);
            }

        }

        @media (max-width: 800px) {

            .home-hero {
                min-height: 560px;

                padding:
                    70px 25px;
            }

            .hero-content h1 {
                letter-spacing: -2px;
            }

            .category-grid,
            .product-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .vendor-grid {
                grid-template-columns: 1fr;
            }

            .home-cta {
                margin:
                    10px 20px 50px;

                padding: 40px 28px;
            }

        }

        @media (max-width: 520px) {

            .category-grid,
            .product-grid {
                grid-template-columns: 1fr;
            }

            .home-section {
                padding:
                    55px 20px;
            }

            .section-heading {
                align-items: start;

                flex-direction: column;
            }

            .home-hero {
                min-height: 520px;
            }

            .hero-content p {
                font-size: 15px;
            }

        }

    </style>

</head>

<body>

<?php
// =========================================================
// EXISTING NAVBAR
// =========================================================

require_once __DIR__ . '/includes/navbar.php';
?>


<div class="home-page">

    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="home-hero">

        <div class="hero-content">

            <div class="hero-small-title">
                ✦ LOCAL MARKETPLACE
            </div>

            <?php if ($logged_in): ?>

                <h1>
                    Welcome back,
                    <span><?= e($user_name); ?></span>.
                </h1>

                <p>
                    Your local marketplace is ready.
                    Discover products, support local vendors,
                    and find something worth adding to your cart.
                </p>

            <?php else: ?>

                <h1>
                    Your local finds,
                    <span>all in one place.</span>
                </h1>

                <p>
                    Discover unique products from local vendors
                    around you. Shop smarter, support small
                    businesses and make every purchase count.
                </p>

            <?php endif; ?>


            <div class="hero-actions">

                <a
                    href="catalog.php"
                    class="hero-btn hero-btn-primary"
                >
                    Explore Products →
                </a>

                <?php if (!$logged_in): ?>

                    <a
                        href="vendor.php"
                        class="hero-btn hero-btn-secondary"
                    >
                        Become a Vendor
                    </a>

                <?php else: ?>

                    <a
                        href="dashboard.php"
                        class="hero-btn hero-btn-secondary"
                    >
                        My Dashboard
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- FLOATING INFO CARD -->

        <div class="hero-card">

            <div class="hero-card-top">

                <div class="hero-card-icon">
                    ⚡
                </div>

                <div>

                    <div class="hero-card-label">
                        HochipoHub
                    </div>

                    <div class="hero-card-value">
                        Built for local
                    </div>

                </div>

            </div>

            <div class="hero-card-line"></div>

            <div class="hero-card-text">
                One platform connecting customers
                and local businesses in one place.
            </div>

        </div>

    </section>


    <!-- =====================================================
         CATEGORIES
    ====================================================== -->

    <section class="home-section">

        <div class="section-heading">

            <div>

                <h2>
                    Explore categories
                </h2>

                <p>
                    Find what you're looking for faster.
                </p>

            </div>

            <a
                href="category.php"
                class="section-link"
            >
                View all →
            </a>

        </div>


        <?php if (!empty($categories)): ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <?php

                        $category_image =
                            trim(
                                $category[
                                    'category_image'
                                ] ?? ''
                            );

                    ?>

                    <a
                        href="category.php?id=<?= (int) $category['category_id']; ?>"
                        class="category-card"
                    >

                        <?php if (
                            $category_image !== ''
                        ): ?>

                            <img
                                src="image/product/<?= e(
                                    ltrim(
                                        $category_image,
                                        '/\\'
                                    )
                                ); ?>"
                                class="category-image"
                                alt="<?= e(
                                    $category['category_name']
                                ); ?>"
                                onerror="
                                    this.style.display='none';
                                "
                            >

                        <?php else: ?>

                            <div
                                class="category-placeholder"
                            >
                                #
                            </div>

                        <?php endif; ?>


                        <h3>
                            <?= e(
                                $category['category_name']
                            ); ?>
                        </h3>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="home-empty">
                Categories will appear here once they are added.
            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         PRODUCTS
    ====================================================== -->

    <section class="home-section">

        <div class="section-heading">

            <div>

                <h2>
                    Fresh from local vendors
                </h2>

                <p>
                    Recently added products worth checking out.
                </p>

            </div>

            <a
                href="catalog.php"
                class="section-link"
            >
                Shop all →
            </a>

        </div>


        <?php if (!empty($products)): ?>

            <div class="product-grid">

                <?php foreach ($products as $product): ?>

                    <?php

                        $product_image =
                            trim(
                                $product['image'] ?? ''
                            );

                        if ($product_image !== '') {

                            $product_image_path =
                                'image/product/' .
                                ltrim(
                                    $product_image,
                                    '/\\'
                                );

                        } else {

                            $product_image_path =
                                'image/logo.jpg';
                        }

                    ?>

                    <a
                        href="product_details.php?id=<?= (int) $product['product_id']; ?>"
                        class="home-product"
                    >

                        <div class="product-image-wrap">

                            <img
                                src="<?= e(
                                    $product_image_path
                                ); ?>"
                                class="product-image"
                                alt="<?= e(
                                    $product['product_name']
                                ); ?>"
                                onerror="
                                    this.src='image/logo.jpg';
                                "
                            >

                            <?php if (
                                !empty(
                                    $product['category_name']
                                )
                            ): ?>

                                <span class="product-tag">
                                    <?= e(
                                        $product['category_name']
                                    ); ?>
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="product-info">

                            <div class="product-category">
                                <?= e(
                                    $product['category_name']
                                    ?? 'Product'
                                ); ?>
                            </div>

                            <h3>
                                <?= e(
                                    $product['product_name']
                                ); ?>
                            </h3>

                            <div class="product-vendor">
                                by
                                <?= e(
                                    $product['business_name']
                                ); ?>
                            </div>


                            <div class="product-bottom">

                                <div class="product-price">
                                    RM
                                    <?= number_format(
                                        (float)
                                        $product['price'],
                                        2
                                    ); ?>
                                </div>

                                <div class="product-view">
                                    →
                                </div>

                            </div>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="home-empty">
                No products are available right now.
            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         VENDORS
    ====================================================== -->

    <section class="home-section">

        <div class="section-heading">

            <div>

                <h2>
                    Meet local vendors
                </h2>

                <p>
                    Discover the people behind the products.
                </p>

            </div>

            <a
                href="vendor.php"
                class="section-link"
            >
                View vendors →
            </a>

        </div>


        <?php if (!empty($vendors)): ?>

            <div class="vendor-grid">

                <?php foreach ($vendors as $vendor): ?>

                    <?php

                        $vendor_logo =
                            trim(
                                $vendor[
                                    'business_logo'
                                ] ?? ''
                            );

                        if (
                            $vendor_logo !== ''
                        ) {

                            $vendor_logo_path =
                                'image/vendors/' .
                                ltrim(
                                    $vendor_logo,
                                    '/\\'
                                );

                        } else {

                            $vendor_logo_path =
                                'image/logo.jpg';
                        }

                    ?>

                    <a
                        href="vendor.php?id=<?= (int) $vendor['vendor_id']; ?>"
                        class="vendor-card"
                    >

                        <img
                            src="<?= e(
                                $vendor_logo_path
                            ); ?>"
                            class="vendor-logo"
                            alt="<?= e(
                                $vendor['business_name']
                            ); ?>"
                            onerror="
                                this.src='image/logo.jpg';
                            "
                        >


                        <div class="vendor-info">

                            <h3>
                                <?= e(
                                    $vendor['business_name']
                                ); ?>
                            </h3>

                            <p>
                                <?= e(
                                    mb_strimwidth(
                                        $vendor[
                                            'business_description'
                                        ] ?? 'Local vendor on HochipoHub.',
                                        0,
                                        75,
                                        '...'
                                    )
                                ); ?>
                            </p>

                            <?php if (
                                !empty(
                                    $vendor['category']
                                )
                            ): ?>

                                <span
                                    class="vendor-pill"
                                >
                                    <?= e(
                                        $vendor['category']
                                    ); ?>
                                </span>

                            <?php endif; ?>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="home-empty">
                Approved vendors will appear here.
            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         CTA
    ====================================================== -->

    <section class="home-cta">

        <h2>
            Got something to sell?
        </h2>

        <p>
            Turn your local business into an online storefront
            and reach customers through HochipoHub.
        </p>

        <a
            href="vendor.php"
            class="cta-btn"
        >
            Start Selling →
        </a>

    </section>

</div>


<?php
require_once __DIR__ . '/includes/footer.php';
?>

</body>
</html>