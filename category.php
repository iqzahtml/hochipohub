<?php

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$category_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($category_id <= 0) {
    header("Location: catalog.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CATEGORY
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT category_id, category_name, category_image
    FROM categories
    WHERE category_id = ?
    LIMIT 1
");

$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: catalog.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

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
        v.business_name
    FROM products p
    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id
    WHERE p.category_id = ?
      AND p.status = 'Available'
      AND v.approval_status = 'Approved'
    ORDER BY p.created_at DESC
");

$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CATEGORY LIST
|--------------------------------------------------------------------------
*/

$categoryStmt = $db->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = htmlspecialchars($category['category_name']) . " - HochipoHub";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>

<main class="category-page">

    <section class="category-hero">

        <div class="category-hero-content">

            <span class="category-label">
                CATEGORY
            </span>

            <h1>
                <?= htmlspecialchars($category['category_name']) ?>
            </h1>

            <p>
                Explore products from this category on HochipoHub.
            </p>

        </div>

        <?php if (!empty($category['category_image'])): ?>

            <div class="category-hero-image">

                <img
                    src="<?= htmlspecialchars($category['category_image']) ?>"
                    alt="<?= htmlspecialchars($category['category_name']) ?>"
                >

            </div>

        <?php endif; ?>

    </section>


    <section class="category-content">

        <aside class="category-sidebar">

            <h3>Categories</h3>

            <div class="category-list">

                <a href="catalog.php">
                    All Products
                </a>

                <?php foreach ($categories as $cat): ?>

                    <a
                        href="category.php?id=<?= (int) $cat['category_id'] ?>"
                        class="<?= $cat['category_id'] == $category_id ? 'active' : '' ?>"
                    >
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </a>

                <?php endforeach; ?>

            </div>

        </aside>


        <section class="product-section">

            <div class="product-section-header">

                <div>

                    <span class="small-label">
                        DISCOVER
                    </span>

                    <h2>
                        <?= htmlspecialchars($category['category_name']) ?>
                    </h2>

                </div>

                <span class="product-count">
                    <?= count($products) ?> products
                </span>

            </div>


            <?php if (empty($products)): ?>

                <div class="empty-products">

                    <div class="empty-icon">
                        🛍️
                    </div>

                    <h3>
                        No products found
                    </h3>

                    <p>
                        There are currently no available products in this category.
                    </p>

                    <a href="catalog.php" class="btn-primary">
                        Browse All Products
                    </a>

                </div>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach ($products as $product): ?>

                        <article class="product-card">

                            <a
                                href="product_details.php?id=<?= (int) $product['product_id'] ?>"
                                class="product-image-link"
                            >

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($product['image']) ?>"
                                        alt="<?= htmlspecialchars($product['product_name']) ?>"
                                        class="product-image"
                                    >

                                <?php else: ?>

                                    <div class="product-image-placeholder">
                                        🛍️
                                    </div>

                                <?php endif; ?>

                            </a>


                            <div class="product-card-content">

                                <span class="vendor-name">

                                    <?= htmlspecialchars($product['business_name']) ?>

                                </span>

                                <h3>

                                    <a
                                        href="product_details.php?id=<?= (int) $product['product_id'] ?>"
                                    >
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </a>

                                </h3>


                                <div class="product-price">

                                    RM <?= number_format((float) $product['price'], 2) ?>

                                </div>


                                <div class="product-stock">

                                    <?php if ((int) $product['stock_quantity'] > 0): ?>

                                        <span class="stock-available">
                                            <?= (int) $product['stock_quantity'] ?> in stock
                                        </span>

                                    <?php else: ?>

                                        <span class="stock-out">
                                            Out of stock
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="product-actions">

                                    <a
                                        href="product_details.php?id=<?= (int) $product['product_id'] ?>"
                                        class="btn-view"
                                    >
                                        View Product
                                    </a>


                                    <?php if ((int) $product['stock_quantity'] > 0): ?>

                                        <?php if (isLoggedIn()): ?>

                                            <button
                                                type="button"
                                                class="btn-cart add-to-cart"
                                                data-product-id="<?= (int) $product['product_id'] ?>"
                                            >
                                                Add to Cart
                                            </button>

                                        <?php else: ?>

                                            <a
                                                href="index.php"
                                                class="btn-cart"
                                            >
                                                Login to Buy
                                            </a>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </section>

</main>


<script>

document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".add-to-cart").forEach(function (button) {

        button.addEventListener("click", function () {

            const productId = this.dataset.productId;

            if (!productId) {
                return;
            }

            fetch("ajax/add_cart.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },

                body:
                    "product_id=" +
                    encodeURIComponent(productId) +
                    "&quantity=1"

            })

            .then(response => response.json())

            .then(data => {

                if (data.success) {

                    button.textContent = "Added ✓";

                    setTimeout(function () {
                        button.textContent = "Add to Cart";
                    }, 1500);

                } else {

                    alert(data.message || "Unable to add product to cart.");

                }

            })

            .catch(error => {

                console.error(error);

                alert("Something went wrong. Please try again.");

            });

        });

    });

});

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>