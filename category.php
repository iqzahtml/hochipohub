<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$categoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/*
|--------------------------------------------------------------------------
| Fetch Categories
|--------------------------------------------------------------------------
*/

$categories = [];

$result = $conn->query("
    SELECT
        c.category_id,
        c.category_name,
        c.category_image,
        COUNT(p.product_id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON c.category_id = p.category_id
        AND p.status = 'Available'
    GROUP BY
        c.category_id,
        c.category_name,
        c.category_image
    ORDER BY c.category_name ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Selected Category
|--------------------------------------------------------------------------
*/

$selectedCategory = null;
$products = [];

if ($categoryId > 0) {

    $stmt = $conn->prepare("
        SELECT
            c.category_id,
            c.category_name,
            c.category_image
        FROM categories c
        WHERE c.category_id = ?
        LIMIT 1
    ");

    $stmt->bind_param('i', $categoryId);
    $stmt->execute();

    $categoryResult = $stmt->get_result();

    if ($categoryResult->num_rows > 0) {
        $selectedCategory = $categoryResult->fetch_assoc();
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Products in Selected Category
    |--------------------------------------------------------------------------
    */

    if ($selectedCategory) {

        $stmt = $conn->prepare("
            SELECT
                p.product_id,
                p.product_name,
                p.description,
                p.price,
                p.stock_quantity,
                p.image,

                v.vendor_id,
                v.business_name,

                c.category_name

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN categories c
                ON p.category_id = c.category_id

            WHERE p.category_id = ?
            AND p.status = 'Available'
            AND p.stock_quantity > 0
            AND v.approval_status = 'Approved'

            ORDER BY p.created_at DESC
        ");

        $stmt->bind_param('i', $categoryId);
        $stmt->execute();

        $productResult = $stmt->get_result();

        while ($row = $productResult->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function categoryImage($image)
{
    if (!empty($image)) {
        return site_url(
            'image/' . ltrim($image, '/')
        );
    }

    return '';
}

function productImage($image)
{
    if (!empty($image)) {
        return site_url(
            'image/product/' . ltrim($image, '/')
        );
    }

    return defined('DEFAULT_PRODUCT_IMAGE')
        ? DEFAULT_PRODUCT_IMAGE
        : site_url('image/logo.jpg');
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
        Categories | <?php echo SITE_NAME; ?>
    </title>

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/product.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >

</head>

<body>

<div class="category-page">

    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="category-hero">

        <div class="category-hero-inner">

            <span class="category-eyebrow">
                EXPLORE HOCHIPOHUB
            </span>

            <h1>
                Find Your
                <span>Category.</span>
            </h1>

            <p>
                From local favourites to unique finds,
                discover products made for your vibe.
            </p>

        </div>

    </section>


    <!-- =====================================================
         CATEGORY LIST
    ====================================================== -->

    <main class="category-container">

        <section class="category-section">

            <div class="category-section-header">

                <div>

                    <span>
                        BROWSE EVERYTHING
                    </span>

                    <h2>
                        What are you looking for?
                    </h2>

                </div>

                <?php if ($selectedCategory): ?>

                    <a
                        href="<?php echo site_url('category.php'); ?>"
                        class="category-back"
                    >
                        ← All Categories
                    </a>

                <?php endif; ?>

            </div>


            <div class="category-grid">

                <?php if (!empty($categories)): ?>

                    <?php foreach ($categories as $category): ?>

                        <?php
                        $isActive =
                            $selectedCategory &&
                            (int)$selectedCategory['category_id']
                            === (int)$category['category_id'];

                        $image = categoryImage(
                            $category['category_image']
                        );
                        ?>

                        <a
                            href="<?php
                                echo site_url(
                                    'category.php?id=' .
                                    (int)$category['category_id']
                                );
                            ?>"
                            class="
                                category-card
                                <?php
                                echo $isActive
                                    ? 'active'
                                    : '';
                                ?>
                            "
                        >

                            <div class="category-card-image">

                                <?php if ($image): ?>

                                    <img
                                        src="<?php
                                            echo htmlspecialchars($image);
                                        ?>"
                                        alt="<?php
                                            echo htmlspecialchars(
                                                $category['category_name']
                                            );
                                        ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="category-placeholder">
                                        ✦
                                    </div>

                                <?php endif; ?>

                                <span class="category-arrow">
                                    ↗
                                </span>

                            </div>


                            <div class="category-card-content">

                                <div>

                                    <h3>
                                        <?php
                                        echo htmlspecialchars(
                                            $category['category_name']
                                        );
                                        ?>
                                    </h3>

                                    <p>
                                        <?php
                                        echo (int)
                                            $category['product_count'];
                                        ?>
                                        products
                                    </p>

                                </div>

                                <span class="category-number">
                                    <?php
                                    echo str_pad(
                                        (string)$category['category_id'],
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    );
                                    ?>
                                </span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="category-empty">
                        <h3>No categories yet.</h3>
                        <p>
                            Categories will appear here once
                            they are added by the admin.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- =================================================
             SELECTED CATEGORY PRODUCTS
        ================================================== -->

        <?php if ($selectedCategory): ?>

            <section class="selected-category">

                <div class="selected-category-heading">

                    <div>

                        <span>
                            CATEGORY
                        </span>

                        <h2>
                            <?php
                            echo htmlspecialchars(
                                $selectedCategory['category_name']
                            );
                            ?>
                        </h2>

                        <p>
                            <?php echo count($products); ?>
                            available product<?php
                            echo count($products) === 1
                                ? ''
                                : 's';
                            ?>
                        </p>

                    </div>

                    <a
                        href="<?php
                            echo site_url(
                                'catalog.php?category=' .
                                $categoryId
                            );
                        ?>"
                        class="browse-category-btn"
                    >
                        View Full Catalog →
                    </a>

                </div>


                <?php if (!empty($products)): ?>

                    <div class="category-product-grid">

                        <?php foreach ($products as $product): ?>

                            <article
                                class="category-product-card"
                            >

                                <a
                                    href="<?php
                                        echo site_url(
                                            'product_details.php?id=' .
                                            (int)$product['product_id']
                                        );
                                    ?>"
                                    class="category-product-image"
                                >

                                    <img
                                        src="<?php
                                            echo htmlspecialchars(
                                                productImage(
                                                    $product['image']
                                                )
                                            );
                                        ?>"
                                        alt="<?php
                                            echo htmlspecialchars(
                                                $product['product_name']
                                            );
                                        ?>"
                                        loading="lazy"
                                    >

                                </a>


                                <div class="category-product-info">

                                    <span>
                                        <?php
                                        echo htmlspecialchars(
                                            $product['business_name']
                                        );
                                        ?>
                                    </span>

                                    <a
                                        href="<?php
                                            echo site_url(
                                                'product_details.php?id=' .
                                                (int)$product['product_id']
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $product['product_name']
                                        );
                                        ?>

                                    </a>


                                    <?php if (
                                        !empty(
                                            $product['description']
                                        )
                                    ): ?>

                                        <p>
                                            <?php
                                            echo htmlspecialchars(
                                                mb_strimwidth(
                                                    $product[
                                                        'description'
                                                    ],
                                                    0,
                                                    80,
                                                    '...'
                                                )
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>


                                    <div
                                        class="
                                            category-product-bottom
                                        "
                                    >

                                        <strong>
                                            RM
                                            <?php
                                            echo number_format(
                                                (float)
                                                $product['price'],
                                                2
                                            );
                                            ?>
                                        </strong>

                                        <a
                                            href="<?php
                                                echo site_url(
                                                    'product_details.php?id=' .
                                                    (int)
                                                    $product['product_id']
                                                );
                                            ?>"
                                        >
                                            →
                                        </a>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="category-no-products">

                        <div>
                            ◌
                        </div>

                        <h3>
                            No products available yet.
                        </h3>

                        <p>
                            This category exists, but vendors
                            haven't listed any available products.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>


        <!-- =================================================
             CTA
        ================================================== -->

        <section class="category-cta">

            <div>

                <span>
                    CAN'T FIND WHAT YOU NEED?
                </span>

                <h2>
                    Explore everything.
                </h2>

                <p>
                    Don't box yourself into one category.
                    There is more waiting for you.
                </p>

            </div>

            <a
                href="<?php echo site_url('catalog.php'); ?>"
            >
                Explore Catalog →
            </a>

        </section>

    </main>

</div>


<style>

/* =========================================================
   CATEGORY PAGE
========================================================= */

.category-page {
    min-height: 100vh;

    background:
        radial-gradient(
            circle at 85% 10%,
            rgba(14,165,233,.13),
            transparent 25%
        ),
        #020617;

    color: #f8fafc;
}


/* =========================================================
   HERO
========================================================= */

.category-hero {
    position: relative;

    overflow: hidden;

    padding: 90px 7% 80px;

    background:
        radial-gradient(
            circle at 75% 20%,
            rgba(37,99,235,.3),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #020617 10%,
            #06285c 55%,
            #075985 100%
        );
}

.category-hero::after {
    content: "";

    position: absolute;

    width: 260px;
    height: 260px;

    right: -90px;
    bottom: -130px;

    border-radius: 50%;

    background:
        rgba(56,189,248,.15);

    filter: blur(5px);
}

.category-hero-inner {
    position: relative;
    z-index: 2;

    max-width: 900px;
    margin: auto;
}

.category-eyebrow,
.category-section-header > div > span,
.selected-category-heading > div > span,
.category-cta > div > span {
    color: #38bdf8;

    font-size: 11px;
    font-weight: 900;

    letter-spacing: 2px;
}

.category-hero h1 {
    margin: 16px 0;

    font-size:
        clamp(50px, 8vw, 82px);

    line-height: .95;

    font-weight: 950;

    letter-spacing: -5px;
}

.category-hero h1 span {
    background:
        linear-gradient(
            90deg,
            #38bdf8,
            #60a5fa,
            #a5f3fc
        );

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.category-hero p {
    max-width: 620px;

    color: #cbd5e1;

    line-height: 1.7;

    font-size: 16px;
}


/* =========================================================
   CONTAINER
========================================================= */

.category-container {
    width: 86%;
    max-width: 1400px;

    margin: auto;
}


/* =========================================================
   CATEGORY SECTION
========================================================= */

.category-section {
    padding: 60px 0 30px;
}

.category-section-header {
    display: flex;

    align-items: end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 30px;
}

.category-section-header h2,
.selected-category-heading h2 {
    margin: 8px 0 0;

    font-size: 32px;
    font-weight: 900;
}

.category-back {
    color: #7dd3fc;

    font-weight: 800;
}


/* =========================================================
   CATEGORY GRID
========================================================= */

.category-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;
}

.category-card {
    position: relative;

    overflow: hidden;

    border:
        1px solid
        rgba(148,163,184,.13);

    border-radius: 22px;

    background:
        linear-gradient(
            145deg,
            rgba(15,23,42,.98),
            rgba(8,47,87,.65)
        );

    transition:
        transform .3s ease,
        border-color .3s ease,
        box-shadow .3s ease;
}

.category-card:hover,
.category-card.active {
    transform: translateY(-6px);

    border-color:
        rgba(56,189,248,.5);

    box-shadow:
        0 25px 55px
        rgba(14,165,233,.14);
}

.category-card-image {
    position: relative;

    height: 170px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #0f3d78,
            #172554
        );
}

.category-card-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition: transform .4s ease;
}

.category-card:hover
.category-card-image img {
    transform: scale(1.08);
}

.category-placeholder {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 100%;
    height: 100%;

    font-size: 60px;

    color: #38bdf8;

    background:
        radial-gradient(
            circle,
            rgba(56,189,248,.2),
            transparent 60%
        );
}

.category-arrow {
    position: absolute;

    top: 13px;
    right: 13px;

    display: flex;

    align-items: center;
    justify-content: center;

    width: 35px;
    height: 35px;

    border-radius: 11px;

    background:
        rgba(2,6,23,.7);

    color: #7dd3fc;

    backdrop-filter: blur(8px);
}

.category-card-content {
    display: flex;

    align-items: end;
    justify-content: space-between;

    gap: 15px;

    padding: 18px;
}

.category-card-content h3 {
    margin: 0;

    color: white;

    font-size: 18px;
    font-weight: 900;
}

.category-card-content p {
    margin: 5px 0 0;

    color: #64748b;

    font-size: 12px;
}

.category-number {
    color: #334155;

    font-size: 22px;
    font-weight: 900;
}


/* =========================================================
   SELECTED CATEGORY
========================================================= */

.selected-category {
    padding: 65px 0 40px;
}

.selected-category-heading {
    display: flex;

    align-items: end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 28px;
}

.selected-category-heading p {
    margin: 7px 0 0;

    color: #64748b;
}

.browse-category-btn {
    padding: 12px 17px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-size: 13px;
    font-weight: 800;
}


/* =========================================================
   PRODUCT GRID
========================================================= */

.category-product-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;
}

.category-product-card {
    overflow: hidden;

    border:
        1px solid
        rgba(148,163,184,.12);

    border-radius: 20px;

    background:
        rgba(15,23,42,.85);

    transition: .3s ease;
}

.category-product-card:hover {
    transform: translateY(-6px);

    border-color:
        rgba(56,189,248,.4);
}

.category-product-image {
    display: block;

    height: 210px;

    overflow: hidden;

    background: #0f172a;
}

.category-product-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition: .4s ease;
}

.category-product-card:hover
.category-product-image img {
    transform: scale(1.06);
}

.category-product-info {
    padding: 17px;
}

.category-product-info > span {
    color: #38bdf8;

    font-size: 10px;
    font-weight: 900;

    letter-spacing: 1px;
    text-transform: uppercase;
}

.category-product-info > a {
    display: block;

    margin-top: 7px;

    color: white;

    font-size: 17px;
    font-weight: 850;
}

.category-product-info p {
    min-height: 38px;

    margin: 8px 0;

    color: #64748b;

    font-size: 12px;

    line-height: 1.5;
}

.category-product-bottom {
    display: flex;

    align-items: center;
    justify-content: space-between;

    margin-top: 15px;
}

.category-product-bottom strong {
    color: #7dd3fc;

    font-size: 20px;
}

.category-product-bottom a {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 38px;
    height: 38px;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-size: 18px;
}


/* =========================================================
   EMPTY
========================================================= */

.category-empty,
.category-no-products {
    grid-column: 1 / -1;

    padding: 55px 20px;

    text-align: center;

    border:
        1px dashed
        rgba(148,163,184,.2);

    border-radius: 20px;

    color: #64748b;
}

.category-empty h3,
.category-no-products h3 {
    color: white;
}

.category-no-products {
    margin-bottom: 50px;
}

.category-no-products > div {
    color: #38bdf8;

    font-size: 48px;
}


/* =========================================================
   CTA
========================================================= */

.category-cta {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 30px;

    margin: 55px 0 80px;

    padding: 42px;

    border:
        1px solid
        rgba(56,189,248,.18);

    border-radius: 28px;

    background:
        radial-gradient(
            circle at 80% 20%,
            rgba(14,165,233,.2),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            rgba(15,23,42,.95),
            rgba(7,47,87,.8)
        );
}

.category-cta h2 {
    margin: 8px 0;

    font-size: 30px;
    font-weight: 900;
}

.category-cta p {
    margin: 0;

    color: #94a3b8;
}

.category-cta > a {
    flex-shrink: 0;

    padding: 14px 20px;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-weight: 850;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

    .category-grid,
    .category-product-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .category-container {
        width: 92%;
    }

    .category-hero {
        padding: 65px 6%;
    }

    .category-hero h1 {
        font-size: 50px;
        letter-spacing: -3px;
    }

    .category-section-header,
    .selected-category-heading,
    .category-cta {
        display: block;
    }

    .category-back,
    .browse-category-btn,
    .category-cta > a {
        display: inline-block;

        margin-top: 18px;
    }

    .category-grid,
    .category-product-grid {
        grid-template-columns: 1fr;
    }

    .category-card-image {
        height: 210px;
    }

}

@media (max-width: 480px) {

    .category-hero h1 {
        font-size: 42px;
    }

    .category-section-header h2,
    .selected-category-heading h2 {
        font-size: 27px;
    }

    .category-cta {
        padding: 28px 22px;
    }

}

</style>

</body>
</html>