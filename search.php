<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PRODUCT SEARCH
|--------------------------------------------------------------------------
| File:
| search.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();


/*
|--------------------------------------------------------------------------
| SEARCH QUERY
|--------------------------------------------------------------------------
*/

$query = trim(
    $_GET['q'] ?? ''
);


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

$categoryId =
    (int) ($_GET['category'] ?? 0);


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

$vendorId =
    (int) ($_GET['vendor'] ?? 0);


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

$products = [];

if (
    $query !== '' ||
    $categoryId > 0 ||
    $vendorId > 0
) {

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

            c.category_id,
            c.category_name

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.status != 'Hidden'

        AND v.approval_status = 'Approved'
    ";

    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($query !== '') {

        $sql .= "
            AND (
                p.product_name LIKE ?
                OR p.description LIKE ?
                OR v.business_name LIKE ?
                OR c.category_name LIKE ?
            )
        ";

        $searchTerm =
            '%' . $query . '%';

        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($categoryId > 0) {

        $sql .= "
            AND p.category_id = ?
        ";

        $params[] =
            $categoryId;
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR
    |--------------------------------------------------------------------------
    */

    if ($vendorId > 0) {

        $sql .= "
            AND p.vendor_id = ?
        ";

        $params[] =
            $vendorId;
    }


    $sql .= "
        ORDER BY p.created_at DESC
    ";


    $stmt =
        $db->prepare($sql);

    $stmt->execute($params);

    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
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
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GET VENDORS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        vendor_id,
        business_name

    FROM vendors

    WHERE approval_status = 'Approved'

    ORDER BY business_name ASC
");

$vendors =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


$pageTitle = 'Search - ' . SITE_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    DISCOVER
                </span>

                <h1>
                    Search Products
                </h1>

                <p>
                    Find products, vendors and categories
                    across HochipoHub.
                </p>

            </div>

        </section>


        <!-- SEARCH FORM -->

        <section class="dashboard-section">

            <form
                method="GET"
                class="dashboard-form"
            >

                <div class="form-group">

                    <label>
                        Search
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="<?= e($query) ?>"
                        placeholder="Search products, vendors..."
                    >

                </div>


                <div class="form-group">

                    <label>
                        Category
                    </label>

                    <select name="category">

                        <option value="0">
                            All Categories
                        </option>

                        <?php foreach (
                            $categories
                            as $category
                        ): ?>

                            <option
                                value="<?= (int)
                                    $category['category_id'] ?>"
                                <?= $categoryId ===
                                    (int) $category[
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


                <div class="form-group">

                    <label>
                        Vendor
                    </label>

                    <select name="vendor">

                        <option value="0">
                            All Vendors
                        </option>

                        <?php foreach (
                            $vendors
                            as $vendor
                        ): ?>

                            <option
                                value="<?= (int)
                                    $vendor['vendor_id'] ?>"
                                <?= $vendorId ===
                                    (int) $vendor[
                                        'vendor_id'
                                    ]
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e(
                                    $vendor[
                                        'business_name'
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

            </form>

        </section>


        <!-- RESULTS -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        RESULTS
                    </span>

                    <h2>

                        <?php if (
                            $query !== ''
                        ): ?>

                            Results for
                            "<?= e($query) ?>"

                        <?php else: ?>

                            Products

                        <?php endif; ?>

                    </h2>

                </div>


                <span>

                    <?= count($products) ?>
                    product(s)

                </span>

            </div>


            <?php if (
                empty($products)
            ): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        🔎
                    </div>

                    <h3>
                        No products found
                    </h3>

                    <p>
                        Try another keyword, category
                        or vendor.
                    </p>

                </div>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach (
                        $products
                        as $product
                    ): ?>

                        <article
                            class="product-card"
                        >

                            <a
                                href="product_details.php?id=<?= (int)
                                    $product['product_id'] ?>"
                            >

                                <img
                                    src="<?= e(
                                        getProductImage(
                                            $product['image']
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $product[
                                            'product_name'
                                        ]
                                    ) ?>"
                                >

                            </a>


                            <div
                                class="product-card-body"
                            >

                                <small>

                                    <?= e(
                                        $product[
                                            'category_name'
                                        ]
                                    ) ?>

                                </small>


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


                                <p>

                                    <?= e(
                                        $product[
                                            'business_name'
                                        ]
                                    ) ?>

                                </p>


                                <strong>

                                    RM
                                    <?= number_format(
                                        (float) $product[
                                            'price'
                                        ],
                                        2
                                    ) ?>

                                </strong>


                                <div>

                                    <span class="status-badge status-<?= e(
                                        statusClass(
                                            getStockStatus(
                                                $product[
                                                    'stock_quantity'
                                                ]
                                            )
                                        )
                                    ) ?>">

                                        <?= e(
                                            getStockStatus(
                                                $product[
                                                    'stock_quantity'
                                                ]
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>