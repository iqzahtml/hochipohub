<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$categoryId = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$sort = isset($_GET['sort'])
    ? $_GET['sort']
    : 'latest';


/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$categories = [];

$categoryResult = $conn->query("
    SELECT
        category_id,
        category_name,
        category_image
    FROM categories
    ORDER BY category_name ASC
");

if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| Build Product Query
|--------------------------------------------------------------------------
*/

$where = [
    "p.status = 'Available'",
    "p.stock_quantity > 0",
    "v.approval_status = 'Approved'"
];

$params = [];
$types = '';

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
    $types .= 'i';
}

if ($search !== '') {
    $where[] = "
        (
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'ssss';
}


/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'price_low':
        $orderBy = "p.price ASC";
        break;

    case 'price_high':
        $orderBy = "p.price DESC";
        break;

    case 'name':
        $orderBy = "p.product_name ASC";
        break;

    default:
        $orderBy = "p.created_at DESC";
        break;
}


/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
*/

$products = [];

$sql = "
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
        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE " . implode(' AND ', $where) . "

    ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Product Rating
|--------------------------------------------------------------------------
*/

function catalogRating($conn, $productId)
{
    $productId = (int) $productId;

    $result = $conn->query("
        SELECT
            AVG(rating) AS average_rating,
            COUNT(review_id) AS total_reviews
        FROM reviews
        WHERE product_id = $productId
        AND status = 'Visible'
    ");

    if ($result && $row = $result->fetch_assoc()) {

        return [
            'rating' => $row['average_rating']
                ? round((float) $row['average_rating'], 1)
                : 0,

            'reviews' => (int) $row['total_reviews']
        ];
    }

    return [
        'rating' => 0,
        'reviews' => 0
    ];
}


/*
|--------------------------------------------------------------------------
| Image Helper
|--------------------------------------------------------------------------
*/

function catalogProductImage($image)
{
    if (!empty($image)) {
        return site_url(
            'image/product/' . ltrim($image, '/')
        );
    }

    return DEFAULT_PRODUCT_IMAGE;
}


/*
|--------------------------------------------------------------------------
| Preserve Query
|--------------------------------------------------------------------------
*/

function catalogUrl($changes = [])
{
    $query = [];

    if (isset($_GET['category']) && (int)$_GET['category'] > 0) {
        $query['category'] = (int)$_GET['category'];
    }

    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $query['search'] = trim($_GET['search']);
    }

    if (isset($_GET['sort']) && $_GET['sort'] !== '') {
        $query['sort'] = $_GET['sort'];
    }

    foreach ($changes as $key => $value) {

        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    $url = site_url('catalog.php');

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
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
        Explore Products | <?php echo SITE_NAME; ?>
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

<div class="catalog-page">

    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="catalog-hero">

        <div class="catalog-hero-content">

            <span class="catalog-eyebrow">
                HOCHIPOHUB MARKETPLACE
            </span>

            <h1>
                Find Your
                <span>Next Favourite.</span>
            </h1>

            <p>
                Explore products from local businesses,
                creators and independent vendors.
            </p>


            <!-- SEARCH -->

            <form
                method="GET"
                action="catalog.php"
                class="catalog-search"
            >

                <?php if ($categoryId > 0): ?>

                    <input
                        type="hidden"
                        name="category"
                        value="<?php echo $categoryId; ?>"
                    >

                <?php endif; ?>


                <div class="search-icon">
                    ⌕
                </div>

                <input
                    type="search"
                    name="search"
                    placeholder="Search products, vendors or categories..."
                    value="<?php
                        echo htmlspecialchars($search);
                    ?>"
                >

                <button type="submit">
                    Search
                </button>

            </form>

        </div>

    </section>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="catalog-container">

        <!-- =================================================
             CATEGORY FILTER
        ================================================== -->

        <section class="catalog-categories">

            <div class="catalog-heading">

                <div>

                    <span>
                        BROWSE
                    </span>

                    <h2>
                        Shop by Category
                    </h2>

                </div>

                <?php if ($categoryId > 0 || $search !== ''): ?>

                    <a
                        href="<?php echo site_url('catalog.php'); ?>"
                        class="clear-filter"
                    >
                        Clear Filters ×
                    </a>

                <?php endif; ?>

            </div>


            <div class="category-scroll">

                <a
                    href="<?php echo site_url('catalog.php'); ?>"
                    class="
                        category-pill
                        <?php
                        echo $categoryId === 0
                            ? 'active'
                            : '';
                        ?>
                    "
                >
                    <span>✦</span>
                    All Products
                </a>


                <?php foreach ($categories as $category): ?>

                    <a
                        href="<?php
                            echo catalogUrl([
                                'category' =>
                                    $category['category_id']
                            ]);
                        ?>"
                        class="
                            category-pill
                            <?php
                            echo $categoryId ===
                                (int)$category['category_id']
                                ? 'active'
                                : '';
                            ?>
                        "
                    >

                        <span>◈</span>

                        <?php
                        echo htmlspecialchars(
                            $category['category_name']
                        );
                        ?>

                    </a>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- =================================================
             PRODUCTS HEADER
        ================================================== -->

        <section class="catalog-products">

            <div class="products-toolbar">

                <div>

                    <span class="products-label">
                        DISCOVER
                    </span>

                    <h2>

                        <?php if ($search !== ''): ?>

                            Results for
                            "<?php
                            echo htmlspecialchars($search);
                            ?>"

                        <?php elseif ($categoryId > 0): ?>

                            <?php

                            $selectedCategoryName =
                                'Products';

                            foreach ($categories as $cat) {

                                if (
                                    (int)$cat['category_id']
                                    === $categoryId
                                ) {
                                    $selectedCategoryName =
                                        $cat['category_name'];
                                    break;
                                }
                            }

                            echo htmlspecialchars(
                                $selectedCategoryName
                            );

                            ?>

                        <?php else: ?>

                            All Products

                        <?php endif; ?>

                    </h2>

                    <p>
                        <?php echo count($products); ?>
                        product<?php
                        echo count($products) === 1
                            ? ''
                            : 's';
                        ?>
                        found
                    </p>

                </div>


                <!-- SORT -->

                <form
                    method="GET"
                    action="catalog.php"
                    class="sort-form"
                >

                    <?php if ($categoryId > 0): ?>

                        <input
                            type="hidden"
                            name="category"
                            value="<?php echo $categoryId; ?>"
                        >

                    <?php endif; ?>


                    <?php if ($search !== ''): ?>

                        <input
                            type="hidden"
                            name="search"
                            value="<?php
                                echo htmlspecialchars($search);
                            ?>"
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
                            <?php
                            echo $sort === 'latest'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Latest
                        </option>

                        <option
                            value="price_low"
                            <?php
                            echo $sort === 'price_low'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Price: Low to High
                        </option>

                        <option
                            value="price_high"
                            <?php
                            echo $sort === 'price_high'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Price: High to Low
                        </option>

                        <option
                            value="name"
                            <?php
                            echo $sort === 'name'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Name: A-Z
                        </option>

                    </select>

                </form>

            </div>


            <!-- =================================================
                 PRODUCT GRID
            ================================================== -->

            <?php if (!empty($products)): ?>

                <div class="catalog-product-grid">

                    <?php foreach ($products as $product): ?>

                        <?php

                        $rating = catalogRating(
                            $conn,
                            $product['product_id']
                        );

                        $productImage =
                            catalogProductImage(
                                $product['image']
                            );

                        ?>

                        <article class="catalog-product-card">

                            <!-- IMAGE -->

                            <a
                                href="<?php
                                    echo site_url(
                                        'product_details.php?id=' .
                                        (int)$product['product_id']
                                    );
                                ?>"
                                class="catalog-product-image"
                            >

                                <img
                                    src="<?php
                                        echo htmlspecialchars(
                                            $productImage
                                        );
                                    ?>"
                                    alt="<?php
                                        echo htmlspecialchars(
                                            $product['product_name']
                                        );
                                    ?>"
                                    loading="lazy"
                                    onerror="
                                        this.src='<?php
                                            echo DEFAULT_PRODUCT_IMAGE;
                                        ?>';
                                    "
                                >

                                <span class="product-status">
                                    AVAILABLE
                                </span>

                            </a>


                            <!-- CONTENT -->

                            <div class="catalog-product-content">

                                <span class="catalog-product-category">

                                    <?php
                                    echo htmlspecialchars(
                                        $product['category_name']
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
                                    class="catalog-product-name"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );
                                    ?>

                                </a>


                                <p class="catalog-product-vendor">

                                    by
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $product['business_name']
                                        );
                                        ?>
                                    </strong>

                                </p>


                                <?php if (
                                    !empty(
                                        $product['description']
                                    )
                                ): ?>

                                    <p class="catalog-product-description">

                                        <?php
                                        echo htmlspecialchars(
                                            mb_strimwidth(
                                                $product['description'],
                                                0,
                                                90,
                                                '...'
                                            )
                                        );
                                        ?>

                                    </p>

                                <?php endif; ?>


                                <div class="catalog-product-meta">

                                    <div>

                                        <div class="catalog-price">

                                            RM
                                            <?php
                                            echo number_format(
                                                (float)$product['price'],
                                                2
                                            );
                                            ?>

                                        </div>


                                        <div class="catalog-rating">

                                            <?php if (
                                                $rating['reviews'] > 0
                                            ): ?>

                                                <span>
                                                    ★
                                                </span>

                                                <?php
                                                echo number_format(
                                                    $rating['rating'],
                                                    1
                                                );
                                                ?>

                                                <small>
                                                    (
                                                    <?php
                                                    echo $rating['reviews'];
                                                    ?>
                                                    )
                                                </small>

                                            <?php else: ?>

                                                <small>
                                                    No reviews
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>


                                    <a
                                        href="<?php
                                            echo site_url(
                                                'product_details.php?id=' .
                                                (int)$product['product_id']
                                            );
                                        ?>"
                                        class="catalog-view-btn"
                                        title="View product"
                                    >
                                        →
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <!-- =================================================
                     EMPTY SEARCH
                ================================================== -->

                <div class="catalog-empty">

                    <div class="empty-icon">
                        ◌
                    </div>

                    <h2>
                        Nothing matched your search.
                    </h2>

                    <p>
                        Try another keyword or explore
                        a different category.
                    </p>

                    <a
                        href="<?php echo site_url('catalog.php'); ?>"
                        class="catalog-empty-btn"
                    >
                        View All Products
                    </a>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>


<!-- =========================================================
     SMALL INLINE STYLE
========================================================= -->

<style>

.catalog-page {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37,99,235,.13),
            transparent 25%
        ),
        #020617;
    color: #f8fafc;
}

.catalog-hero {
    position: relative;
    overflow: hidden;

    padding:
        85px 7% 70px;

    background:
        radial-gradient(
            circle at 85% 25%,
            rgba(14,165,233,.25),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #020617,
            #06285c 55%,
            #0b4fa3
        );
}

.catalog-hero-content {
    max-width: 900px;
    margin: auto;
}

.catalog-eyebrow,
.products-label,
.catalog-heading > div > span {
    color: #38bdf8;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 2px;
}

.catalog-hero h1 {
    margin: 15px 0;

    font-size:
        clamp(48px, 7vw, 80px);

    line-height: .98;
    font-weight: 900;
    letter-spacing: -4px;
}

.catalog-hero h1 span {
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

.catalog-hero p {
    max-width: 600px;

    color: #cbd5e1;
    line-height: 1.7;
}

.catalog-search {
    display: flex;
    align-items: center;

    max-width: 760px;

    margin-top: 30px;

    padding: 7px;

    border:
        1px solid
        rgba(148,163,184,.22);

    border-radius: 18px;

    background:
        rgba(15,23,42,.65);

    backdrop-filter: blur(18px);
}

.search-icon {
    padding: 0 15px;

    color: #38bdf8;
    font-size: 24px;
}

.catalog-search input {
    flex: 1;

    min-width: 0;

    padding: 14px 5px;

    border: 0;
    outline: 0;

    background: transparent;

    color: white;

    font-size: 15px;
}

.catalog-search input::placeholder {
    color: #64748b;
}

.catalog-search button {
    padding: 14px 24px;

    border: 0;
    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-weight: 800;

    cursor: pointer;
}

.catalog-container {
    width: 86%;
    max-width: 1400px;

    margin: auto;
}

.catalog-categories {
    padding: 50px 0 35px;
}

.catalog-heading {
    display: flex;

    align-items: end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 20px;
}

.catalog-heading h2,
.products-toolbar h2 {
    margin: 6px 0 0;

    font-size: 30px;
    font-weight: 900;
}

.clear-filter {
    color: #f87171;
    font-weight: 700;
}

.category-scroll {
    display: flex;

    gap: 10px;

    overflow-x: auto;

    padding-bottom: 8px;
}

.category-pill {
    flex: 0 0 auto;

    display: flex;
    align-items: center;
    gap: 8px;

    padding: 12px 18px;

    border:
        1px solid
        rgba(148,163,184,.18);

    border-radius: 999px;

    background:
        rgba(15,23,42,.65);

    color: #cbd5e1;

    font-size: 14px;
    font-weight: 700;

    transition: .25s ease;
}

.category-pill:hover,
.category-pill.active {
    border-color:
        rgba(56,189,248,.55);

    background:
        rgba(14,165,233,.15);

    color: #7dd3fc;
}

.products-toolbar {
    display: flex;

    align-items: end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 30px;
}

.products-toolbar p {
    margin: 8px 0 0;
    color: #64748b;
}

.sort-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-form label {
    color: #94a3b8;
    font-size: 13px;
}

.sort-form select {
    padding: 11px 15px;

    border:
        1px solid
        rgba(148,163,184,.2);

    border-radius: 12px;

    background: #0f172a;
    color: white;

    outline: none;
}

.catalog-product-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 22px;

    padding-bottom: 90px;
}

.catalog-product-card {
    overflow: hidden;

    border:
        1px solid
        rgba(148,163,184,.13);

    border-radius: 23px;

    background:
        linear-gradient(
            145deg,
            rgba(15,23,42,.96),
            rgba(8,47,87,.7)
        );

    transition: .3s ease;
}

.catalog-product-card:hover {
    transform: translateY(-7px);

    border-color:
        rgba(56,189,248,.45);

    box-shadow:
        0 25px 60px
        rgba(2,132,199,.18);
}

.catalog-product-image {
    position: relative;

    display: block;

    height: 245px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #0f3d78,
            #172554
        );
}

.catalog-product-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition: .4s ease;
}

.catalog-product-card:hover
.catalog-product-image img {
    transform: scale(1.07);
}

.product-status {
    position: absolute;

    top: 14px;
    left: 14px;

    padding: 7px 10px;

    border-radius: 999px;

    background:
        rgba(15,23,42,.75);

    color: #7dd3fc;

    font-size: 10px;
    font-weight: 900;

    backdrop-filter: blur(10px);
}

.catalog-product-content {
    padding: 20px;
}

.catalog-product-category {
    color: #38bdf8;

    font-size: 11px;
    font-weight: 900;

    letter-spacing: 1px;
    text-transform: uppercase;
}

.catalog-product-name {
    display: block;

    margin-top: 8px;

    color: white;

    font-size: 19px;
    font-weight: 850;

    line-height: 1.25;
}

.catalog-product-vendor {
    margin: 8px 0 0;

    color: #64748b;

    font-size: 13px;
}

.catalog-product-vendor strong {
    color: #94a3b8;
}

.catalog-product-description {
    color: #64748b;

    font-size: 13px;

    line-height: 1.5;

    min-height: 39px;

    margin: 12px 0 0;
}

.catalog-product-meta {
    display: flex;

    align-items: end;
    justify-content: space-between;

    margin-top: 18px;
}

.catalog-price {
    color: #7dd3fc;

    font-size: 22px;
    font-weight: 900;
}

.catalog-rating {
    margin-top: 5px;

    color: #facc15;

    font-size: 13px;
}

.catalog-rating small {
    color: #64748b;
}

.catalog-view-btn {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 42px;
    height: 42px;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-size: 20px;
    font-weight: 900;
}

.catalog-empty {
    padding: 90px 20px;

    margin-bottom: 80px;

    text-align: center;

    border:
        1px dashed
        rgba(148,163,184,.25);

    border-radius: 25px;

    color: #94a3b8;
}

.empty-icon {
    font-size: 55px;
    color: #38bdf8;
}

.catalog-empty h2 {
    color: white;
}

.catalog-empty-btn {
    display: inline-block;

    margin-top: 15px;

    padding: 13px 20px;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-weight: 800;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 1000px) {

    .catalog-product-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .catalog-container {
        width: 92%;
    }

    .catalog-hero {
        padding: 65px 6%;
    }

    .catalog-hero h1 {
        font-size: 48px;
        letter-spacing: -2px;
    }

    .catalog-search {
        border-radius: 15px;
    }

    .catalog-search button {
        padding: 13px 16px;
    }

    .products-toolbar {
        display: block;
    }

    .sort-form {
        margin-top: 20px;
    }

    .sort-form select {
        flex: 1;
    }

    .catalog-product-grid {
        grid-template-columns: 1fr;
    }

    .catalog-product-image {
        height: 250px;
    }

}

@media (max-width: 480px) {

    .catalog-hero h1 {
        font-size: 40px;
    }

    .catalog-search input {
        font-size: 13px;
    }

    .catalog-search button {
        font-size: 12px;
    }

    .catalog-heading h2,
    .products-toolbar h2 {
        font-size: 25px;
    }

}

</style>

</body>
</html>