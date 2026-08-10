<?php

/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - PRODUCT CATALOG
|--------------------------------------------------------------------------
| File: catalog.php
|
| Purpose:
| - Display all available products
| - Filter by category
| - Filter by vendor
| - Search products
| - Sort products
| - Add product to cart
| - Add product to wishlist
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$categoryId = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

$vendorId = isset($_GET['vendor'])
    ? (int) $_GET['vendor']
    : 0;

$sort = isset($_GET['sort'])
    ? $_GET['sort']
    : 'latest';


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

$categoryStmt = $db->query("
    SELECT
        category_id,
        category_name,
        category_image
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| LOAD VENDORS
|--------------------------------------------------------------------------
*/

$vendorStmt = $db->query("
    SELECT
        v.vendor_id,
        v.business_name,
        v.business_logo
    FROM vendors v

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE v.approval_status = 'Approved'
    AND u.status = 'active'

    ORDER BY v.business_name ASC
");

$vendors = $vendorStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| BUILD PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$sql = "
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

        v.business_name,
        v.business_logo,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE p.status = 'Available'

    AND v.approval_status = 'Approved'

    AND u.status = 'active'

    AND p.stock_quantity > 0
";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] = $categoryId;
}


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    $sql .= "
        AND p.vendor_id = ?
    ";

    $params[] = $vendorId;
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'price_low':
        $sql .= "
            ORDER BY p.price ASC
        ";
        break;

    case 'price_high':
        $sql .= "
            ORDER BY p.price DESC
        ";
        break;

    case 'name':
        $sql .= "
            ORDER BY p.product_name ASC
        ";
        break;

    case 'oldest':
        $sql .= "
            ORDER BY p.created_at ASC
        ";
        break;

    case 'latest':
    default:
        $sql .= "
            ORDER BY p.created_at DESC
        ";
        break;
}


/*
|--------------------------------------------------------------------------
| EXECUTE PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$productStmt = $db->prepare($sql);

$productStmt->execute($params);

$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| COUNT PRODUCTS
|--------------------------------------------------------------------------
*/

$productCount = count($products);


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle = "Product Catalog";


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
        <?php echo htmlspecialchars($pageTitle); ?> | HochipoHub
    </title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/product.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >

</head>


<body>


<?php
require_once __DIR__ . '/includes/navbar.php';
?>


<main class="catalog-page">


    <!-- =====================================================
         CATALOG HERO
    ====================================================== -->

    <section class="catalog-hero">

        <div class="catalog-hero-content">

            <span class="catalog-label">
                DISCOVER • SHOP • SUPPORT LOCAL
            </span>

            <h1>
                Explore
                <span>HochipoHub</span>
            </h1>

            <p>
                Discover products from local vendors
                all in one place.
            </p>


            <!-- Search -->

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


                <?php if ($vendorId > 0): ?>

                    <input
                        type="hidden"
                        name="vendor"
                        value="<?php echo $vendorId; ?>"
                    >

                <?php endif; ?>


                <div class="search-input-wrapper">

                    <span class="search-icon">
                        🔍
                    </span>

                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search products, vendors or categories..."
                    >

                </div>


                <button
                    type="submit"
                    class="btn-primary"
                >
                    Search
                </button>

            </form>

        </div>

    </section>


    <!-- =====================================================
         CATALOG CONTENT
    ====================================================== -->

    <section class="catalog-container">


        <!-- =============================================
             FILTER SIDEBAR
        ============================================== -->

        <aside class="catalog-sidebar">


            <!-- Category -->

            <div class="filter-box">

                <div class="filter-title">

                    <span>
                        CATEGORIES
                    </span>

                </div>


                <a
                    href="catalog.php"
                    class="<?php echo $categoryId === 0 ? 'active' : ''; ?>"
                >

                    All Products

                </a>


                <?php foreach ($categories as $category): ?>

                    <a
                        href="catalog.php?category=<?php echo (int) $category['category_id']; ?>"
                        class="<?php echo $categoryId === (int) $category['category_id'] ? 'active' : ''; ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $category['category_name']
                        );
                        ?>

                    </a>

                <?php endforeach; ?>

            </div>


            <!-- Vendors -->

            <div class="filter-box">

                <div class="filter-title">

                    <span>
                        SELLERS
                    </span>

                </div>


                <a
                    href="catalog.php"
                    class="<?php echo $vendorId === 0 ? 'active' : ''; ?>"
                >

                    All Sellers

                </a>


                <?php foreach ($vendors as $vendor): ?>

                    <a
                        href="catalog.php?vendor=<?php echo (int) $vendor['vendor_id']; ?>"
                        class="<?php echo $vendorId === (int) $vendor['vendor_id'] ? 'active' : ''; ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $vendor['business_name']
                        );
                        ?>

                    </a>

                <?php endforeach; ?>

            </div>


        </aside>


        <!-- =============================================
             PRODUCT AREA
        ============================================== -->

        <div class="catalog-products">


            <!-- Product Toolbar -->

            <div class="catalog-toolbar">


                <div class="catalog-result">

                    <strong>
                        <?php echo $productCount; ?>
                    </strong>

                    product<?php echo $productCount != 1 ? 's' : ''; ?>
                    found

                </div>


                <form
                    method="GET"
                    action="catalog.php"
                    class="sort-form"
                >


                    <?php if ($search !== ''): ?>

                        <input
                            type="hidden"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    <?php endif; ?>


                    <?php if ($categoryId > 0): ?>

                        <input
                            type="hidden"
                            name="category"
                            value="<?php echo $categoryId; ?>"
                        >

                    <?php endif; ?>


                    <?php if ($vendorId > 0): ?>

                        <input
                            type="hidden"
                            name="vendor"
                            value="<?php echo $vendorId; ?>"
                        >

                    <?php endif; ?>


                    <label>
                        Sort:
                    </label>


                    <select
                        name="sort"
                        onchange="this.form.submit()"
                    >

                        <option
                            value="latest"
                            <?php echo $sort === 'latest' ? 'selected' : ''; ?>
                        >
                            Latest
                        </option>

                        <option
                            value="price_low"
                            <?php echo $sort === 'price_low' ? 'selected' : ''; ?>
                        >
                            Price: Low to High
                        </option>

                        <option
                            value="price_high"
                            <?php echo $sort === 'price_high' ? 'selected' : ''; ?>
                        >
                            Price: High to Low
                        </option>

                        <option
                            value="name"
                            <?php echo $sort === 'name' ? 'selected' : ''; ?>
                        >
                            Name
                        </option>

                        <option
                            value="oldest"
                            <?php echo $sort === 'oldest' ? 'selected' : ''; ?>
                        >
                            Oldest
                        </option>

                    </select>

                </form>


            </div>


            <!-- =========================================
                 PRODUCTS
            ========================================== -->

            <?php if (empty($products)): ?>


                <div class="no-products">

                    <div class="no-products-icon">
                        🔎
                    </div>

                    <h2>
                        No products found
                    </h2>

                    <p>
                        Try another search or browse
                        through our categories.
                    </p>

                    <a
                        href="catalog.php"
                        class="btn-primary"
                    >
                        View All Products
                    </a>

                </div>


            <?php else: ?>


                <div class="product-grid">


                    <?php foreach ($products as $product): ?>

                        <?php

                        $productId = (int) $product['product_id'];

                        $price = (float) $product['price'];

                        ?>


                        <article class="product-card">


                            <!-- Product Image -->

                            <div class="product-image-wrapper">


                                <a
                                    href="product_details.php?id=<?php echo $productId; ?>"
                                >

                                    <?php if (!empty($product['image'])): ?>

                                        <img
                                            src="<?php echo htmlspecialchars($product['image']); ?>"
                                            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                            class="product-image"
                                        >

                                    <?php else: ?>

                                        <div class="product-image-placeholder">
                                            📦
                                        </div>

                                    <?php endif; ?>

                                </a>


                                <!-- Wishlist -->

                                <?php if (isset($_SESSION['user_id'])): ?>

                                    <button
                                        type="button"
                                        class="wishlist-btn"
                                        data-product-id="<?php echo $productId; ?>"
                                        title="Add to wishlist"
                                    >
                                        ♡
                                    </button>

                                <?php endif; ?>


                            </div>


                            <!-- Product Info -->

                            <div class="product-card-body">


                                <span class="product-category">

                                    <?php
                                    echo htmlspecialchars(
                                        $product['category_name']
                                    );
                                    ?>

                                </span>


                                <h3 class="product-name">

                                    <a
                                        href="product_details.php?id=<?php echo $productId; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $product['product_name']
                                        );
                                        ?>

                                    </a>

                                </h3>


                                <p class="product-vendor">

                                    by

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $product['business_name']
                                        );
                                        ?>

                                    </strong>

                                </p>


                                <div class="product-card-bottom">


                                    <div class="product-price">

                                        RM
                                        <?php
                                        echo number_format(
                                            $price,
                                            2
                                        );
                                        ?>

                                    </div>


                                    <?php if (isset($_SESSION['user_id'])): ?>

                                        <button
                                            type="button"
                                            class="add-cart-btn"
                                            data-product-id="<?php echo $productId; ?>"
                                        >

                                            🛒
                                            Add

                                        </button>

                                    <?php else: ?>

                                        <a
                                            href="index.php"
                                            class="add-cart-btn"
                                        >

                                            Login

                                        </a>

                                    <?php endif; ?>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>


<script src="js/script.js"></script>
<script src="js/cart.js"></script>
<script src="js/wishlist.js"></script>
<script src="js/search.js"></script>

</body>
</html>