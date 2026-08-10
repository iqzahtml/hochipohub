<?php
require_once '../database/db.php';
require_once '../includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        vendor_id,
        business_name,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$vendor = $result->fetch_assoc();

$stmt->close();

if (!$vendor) {
    header("Location: setup_profile.php");
    exit;
}

$vendor_id = (int) $vendor['vendor_id'];

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

$allowed_status = [
    'Available',
    'Out of Stock',
    'Hidden'
];

if (
    $status_filter !== '' &&
    !in_array($status_filter, $allowed_status, true)
) {
    $status_filter = '';
}

/*
|--------------------------------------------------------------------------
| PRODUCTS QUERY
|--------------------------------------------------------------------------
*/
$products = [];

if ($search !== '' && $status_filter !== '') {

    $search_value = '%' . $search . '%';

    $stmt = $conn->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            p.created_at,
            p.updated_at,

            c.category_name,

            COALESCE(i.quantity, p.stock_quantity) AS inventory_quantity

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        LEFT JOIN inventory i
            ON p.product_id = i.product_id

        WHERE p.vendor_id = ?

        AND p.status = ?

        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )

        ORDER BY p.created_at DESC
    ");

    $stmt->bind_param(
        "isss",
        $vendor_id,
        $status_filter,
        $search_value,
        $search_value
    );

} elseif ($search !== '') {

    $search_value = '%' . $search . '%';

    $stmt = $conn->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            p.created_at,
            p.updated_at,

            c.category_name,

            COALESCE(i.quantity, p.stock_quantity) AS inventory_quantity

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        LEFT JOIN inventory i
            ON p.product_id = i.product_id

        WHERE p.vendor_id = ?

        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )

        ORDER BY p.created_at DESC
    ");

    $stmt->bind_param(
        "iss",
        $vendor_id,
        $search_value,
        $search_value
    );

} elseif ($status_filter !== '') {

    $stmt = $conn->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            p.created_at,
            p.updated_at,

            c.category_name,

            COALESCE(i.quantity, p.stock_quantity) AS inventory_quantity

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        LEFT JOIN inventory i
            ON p.product_id = i.product_id

        WHERE p.vendor_id = ?

        AND p.status = ?

        ORDER BY p.created_at DESC
    ");

    $stmt->bind_param(
        "is",
        $vendor_id,
        $status_filter
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            p.created_at,
            p.updated_at,

            c.category_name,

            COALESCE(i.quantity, p.stock_quantity) AS inventory_quantity

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        LEFT JOIN inventory i
            ON p.product_id = i.product_id

        WHERE p.vendor_id = ?

        ORDER BY p.created_at DESC
    ");

    $stmt->bind_param(
        "i",
        $vendor_id
    );
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| PRODUCT COUNTS
|--------------------------------------------------------------------------
*/
$count_stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_products,

        SUM(
            CASE
                WHEN status = 'Available'
                THEN 1
                ELSE 0
            END
        ) AS available_products,

        SUM(
            CASE
                WHEN status = 'Out of Stock'
                THEN 1
                ELSE 0
            END
        ) AS out_of_stock,

        SUM(
            CASE
                WHEN status = 'Hidden'
                THEN 1
                ELSE 0
            END
        ) AS hidden_products

    FROM products

    WHERE vendor_id = ?
");

$count_stmt->bind_param(
    "i",
    $vendor_id
);

$count_stmt->execute();

$count_result = $count_stmt->get_result();
$counts = $count_result->fetch_assoc();

$count_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Products | Seller | HochipoHub</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

</head>

<body>

<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">

    <?php include '../includes/vendor_sidebar.php'; ?>

    <main class="dashboard-content">

        <div class="page-header">

            <div>

                <h1>My Products</h1>

                <p>
                    Manage products listed under your store.
                </p>

            </div>

            <?php if ($vendor['approval_status'] === 'Approved'): ?>

                <a
                    href="add_product.php"
                    class="btn btn-primary"
                >
                    + Add Product
                </a>

            <?php endif; ?>

        </div>


        <?php if ($vendor['approval_status'] !== 'Approved'): ?>

            <div class="alert alert-warning">

                Your vendor account is currently

                <strong>
                    <?= htmlspecialchars(
                        $vendor['approval_status']
                    ) ?>
                </strong>.

                You cannot add or edit products until your vendor
                account has been approved.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['success'])): ?>

            <div class="alert alert-success">

                <?php

                switch ($_GET['success']) {

                    case 'product_updated':
                        echo "Product updated successfully.";
                        break;

                    case 'product_deleted':
                        echo "Product deleted successfully.";
                        break;

                    case 'product_hidden':
                        echo "Product has been hidden because it has existing order history.";
                        break;

                    default:
                        echo "Action completed successfully.";
                }

                ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['error'])): ?>

            <div class="alert alert-danger">

                <?php

                switch ($_GET['error']) {

                    case 'product_not_found':
                        echo "Product not found.";
                        break;

                    case 'invalid_product':
                        echo "Invalid product.";
                        break;

                    case 'delete_failed':
                        echo "Unable to delete product.";
                        break;

                    default:
                        echo "Something went wrong.";
                }

                ?>

            </div>

        <?php endif; ?>


        <!-- PRODUCT COUNTS -->

        <div class="stats-grid">

            <div class="stat-card">

                <span>Total Products</span>

                <strong>
                    <?= (int)(
                        $counts['total_products'] ?? 0
                    ) ?>
                </strong>

            </div>

            <div class="stat-card">

                <span>Available</span>

                <strong>
                    <?= (int)(
                        $counts['available_products'] ?? 0
                    ) ?>
                </strong>

            </div>

            <div class="stat-card">

                <span>Out of Stock</span>

                <strong>
                    <?= (int)(
                        $counts['out_of_stock'] ?? 0
                    ) ?>
                </strong>

            </div>

            <div class="stat-card">

                <span>Hidden</span>

                <strong>
                    <?= (int)(
                        $counts['hidden_products'] ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <!-- SEARCH -->

        <form
            method="GET"
            class="product-filter-form"
        >

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search product..."
            >

            <select name="status">

                <option value="">
                    All Status
                </option>

                <?php foreach ($allowed_status as $status): ?>

                    <option
                        value="<?= htmlspecialchars($status) ?>"
                        <?= $status_filter === $status
                            ? 'selected'
                            : '' ?>
                    >

                        <?= htmlspecialchars($status) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Search
            </button>

            <a
                href="products.php"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </form>


        <?php if (empty($products)): ?>

            <div class="empty-state">

                <h3>No products found</h3>

                <p>
                    Try another search or add a new product.
                </p>

            </div>

        <?php else: ?>


            <div class="product-grid">

                <?php foreach ($products as $product): ?>

                    <div class="product-card">

                        <div class="product-image">

                            <?php if (!empty($product['image'])): ?>

                                <img
                                    src="../uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['product_name']) ?>"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    No Image
                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="product-content">

                            <span class="product-category">

                                <?= htmlspecialchars(
                                    $product['category_name']
                                ) ?>

                            </span>


                            <h3>

                                <?= htmlspecialchars(
                                    $product['product_name']
                                ) ?>

                            </h3>


                            <p class="product-description">

                                <?= htmlspecialchars(
                                    mb_strimwidth(
                                        $product['description'] ?? '',
                                        0,
                                        100,
                                        '...'
                                    )
                                ) ?>

                            </p>


                            <div class="product-price">

                                RM
                                <?= number_format(
                                    (float)$product['price'],
                                    2
                                ) ?>

                            </div>


                            <div class="product-stock">

                                Stock:

                                <strong>
                                    <?= (int)(
                                        $product['inventory_quantity']
                                    ) ?>
                                </strong>

                            </div>


                            <span class="status-badge">

                                <?= htmlspecialchars(
                                    $product['status']
                                ) ?>

                            </span>


                            <div class="product-actions">

                                <a
                                    href="edit_product.php?id=<?= (int)$product['product_id'] ?>"
                                    class="btn btn-primary"
                                >
                                    Edit
                                </a>

                                <a
                                    href="delete_product.php?id=<?= (int)$product['product_id'] ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this product?');"
                                >
                                    Delete
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>