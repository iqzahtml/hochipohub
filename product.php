<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PRODUCT LISTING
|--------------------------------------------------------------------------
| File:
| product.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$category_id =
    (int) ($_GET['category_id'] ?? 0);

$vendor_id =
    (int) ($_GET['vendor_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories =
    $stmt->fetchAll();


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

    WHERE p.status != 'Hidden'
    AND v.approval_status = 'Approved'
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
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($category_id > 0) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] =
        $category_id;
}


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

if ($vendor_id > 0) {

    $sql .= "
        AND p.vendor_id = ?
    ";

    $params[] =
        $vendor_id;
}


$sql .= "
    GROUP BY
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
        c.category_name

    ORDER BY p.created_at DESC
";


$stmt = $db->prepare($sql);

$stmt->execute($params);

$products =
    $stmt->fetchAll();


$pageTitle =
    'Products - ' . SITE_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="products-page">

    <div class="dashboard-container">


        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    HOCHIPOHUB MARKETPLACE
                </span>

                <h1>
                    Products
                </h1>

                <p>
                    Discover products from our vendors.
                </p>

            </div>

        </section>


        <!-- =====================================================
             FILTER
        ====================================================== -->

        <section class="dashboard-section">

            <form
                method="GET"
                class="product-filter-form"
            >


                <div class="form-group">

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="<?= e($search) ?>"
                        placeholder="Search products..."
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        name="category_id"
                        id="category_id"
                    >

                        <option value="">
                            All Categories
                        </option>

                        <?php foreach (
                            $categories
                            as $category
                        ): ?>

                            <option
                                value="<?= (int)
                                    $category[
                                        'category_id'
                                    ] ?>"
                                <?= $category_id ===
                                    (int)
                                    $category[
                                        'category_id'
                                    ]
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e(
                                    $category[
                                        'category_name'
                                    ]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>


                <a
                    href="product.php"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </form>

        </section>


        <!-- =====================================================
             PRODUCT GRID
        ====================================================== -->

        <section class="product-grid-section">


            <?php if (empty($products)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        🔎
                    </div>

                    <h3>
                        No products found
                    </h3>

                    <p>
                        Try another search or category.
                    </p>

                </div>


            <?php else: ?>


                <div class="product-grid">


                    <?php foreach (
                        $products
                        as $product
                    ): ?>


                        <?php

                        $image =
                            !empty(
                                $product['image']
                            )
                            ? 'uploads/products/' .
                              basename(
                                  $product['image']
                              )
                            : 'image/logo.jpg';

                        ?>


                        <article class="product-card">


                            <a
                                href="product_details.php?id=<?= (int)
                                    $product[
                                        'product_id'
                                    ] ?>"
                                class="product-card-image"
                            >

                                <img
                                    src="<?= e($image) ?>"
                                    alt="<?= e(
                                        $product[
                                            'product_name'
                                        ]
                                    ) ?>"
                                >

                            </a>


                            <div class="product-card-content">


                                <span class="small-label">

                                    <?= e(
                                        $product[
                                            'category_name'
                                        ]
                                    ) ?>

                                </span>


                                <h3>

                                    <a
                                        href="product_details.php?id=<?= (int)
                                            $product[
                                                'product_id'
                                            ] ?>"
                                    >

                                        <?= e(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </a>

                                </h3>


                                <p class="product-vendor-name">

                                    <?= e(
                                        $product[
                                            'business_name'
                                        ]
                                    ) ?>

                                </p>


                                <div class="product-rating">

                                    ⭐

                                    <?= number_format(
                                        (float)
                                        $product[
                                            'average_rating'
                                        ],
                                        1
                                    ) ?>

                                    <small>

                                        (
                                        <?= (int)
                                            $product[
                                                'review_count'
                                            ] ?>

                                        )

                                    </small>

                                </div>


                                <div class="product-card-bottom">


                                    <strong class="product-price">

                                        RM

                                        <?= number_format(
                                            (float)
                                            $product[
                                                'price'
                                            ],
                                            2
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        (int)
                                        $product[
                                            'stock_quantity'
                                        ] > 0
                                    ): ?>

                                        <span
                                            class="status-badge status-success"
                                        >
                                            In Stock
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="status-badge status-danger"
                                        >
                                            Out of Stock
                                        </span>

                                    <?php endif; ?>


                                </div>


                                <a
                                    href="product_details.php?id=<?= (int)
                                        $product[
                                            'product_id'
                                        ] ?>"
                                    class="btn btn-primary product-view-button"
                                >
                                    View Product
                                </a>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>

            <?php endif; ?>


        </section>


    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>