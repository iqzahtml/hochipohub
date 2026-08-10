<?php
/**
 * HOCHIPOHUB
 * Admin - Products Management
 */

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $product_id = (int) $_GET['delete'];

    if ($product_id > 0) {

        try {

            $db->beginTransaction();

            /*
             * Delete wishlist records
             */
            $stmt = $db->prepare("
                DELETE FROM wishlist
                WHERE product_id = ?
            ");
            $stmt->execute([$product_id]);

            /*
             * Delete cart records
             */
            $stmt = $db->prepare("
                DELETE FROM cart
                WHERE product_id = ?
            ");
            $stmt->execute([$product_id]);

            /*
             * Delete reviews
             */
            $stmt = $db->prepare("
                DELETE FROM reviews
                WHERE product_id = ?
            ");
            $stmt->execute([$product_id]);

            /*
             * Delete inventory
             */
            $stmt = $db->prepare("
                DELETE FROM inventory
                WHERE product_id = ?
            ");
            $stmt->execute([$product_id]);

            /*
             * Delete order details
             *
             * We don't delete order_details because
             * old orders should remain as transaction records.
             *
             * Therefore product deletion is blocked if
             * the product already exists in an order.
             */

            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM order_details
                WHERE product_id = ?
            ");

            $stmt->execute([$product_id]);

            $orderCount = (int) $stmt->fetchColumn();

            if ($orderCount > 0) {

                $db->rollBack();

                header("Location: products.php?error=order");
                exit;
            }

            /*
             * Get vendor ID before deleting
             */
            $stmt = $db->prepare("
                SELECT vendor_id
                FROM products
                WHERE product_id = ?
            ");

            $stmt->execute([$product_id]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {

                $db->rollBack();

                header("Location: products.php?error=notfound");
                exit;
            }

            /*
             * Delete product
             */
            $stmt = $db->prepare("
                DELETE FROM products
                WHERE product_id = ?
            ");

            $stmt->execute([$product_id]);

            /*
             * Admin log
             */
            $admin_id = (int) $_SESSION['user_id'];

            $stmt = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $admin_id,
                'Deleted product',
                'product',
                $product_id
            ]);

            $db->commit();

            header("Location: products.php?success=deleted");
            exit;

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            header("Location: products.php?error=delete");
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STATUS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $product_id = (int) ($_POST['product_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed_status = [
        'Available',
        'Out of Stock',
        'Hidden'
    ];

    if (
        $product_id > 0 &&
        in_array($status, $allowed_status, true)
    ) {

        try {

            $stmt = $db->prepare("
                UPDATE products
                SET status = ?
                WHERE product_id = ?
            ");

            $stmt->execute([
                $status,
                $product_id
            ]);

            /*
             * Admin log
             */
            $stmt = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                'Updated product status to ' . $status,
                'product',
                $product_id
            ]);

            header("Location: products.php?success=status");
            exit;

        } catch (PDOException $e) {

            header("Location: products.php?error=update");
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$category_filter = (int) ($_GET['category'] ?? 0);

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

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PRODUCTS QUERY
|--------------------------------------------------------------------------
*/

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

        c.category_name,

        v.vendor_id,
        v.business_name,

        u.name AS vendor_name

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.category_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE 1 = 1
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
            OR v.business_name LIKE ?
            OR u.name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status_filter !== '') {

    $sql .= "
        AND p.status = ?
    ";

    $params[] = $status_filter;
}

/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($category_filter > 0) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] = $category_filter;
}

/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY p.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*)
    FROM products
");

$total_products = (int) $stmt->fetchColumn();

$stmt = $db->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'Available'
");

$available_products = (int) $stmt->fetchColumn();

$stmt = $db->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'Out of Stock'
");

$out_of_stock = (int) $stmt->fetchColumn();

$stmt = $db->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'Hidden'
");

$hidden_products = (int) $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products | HochipoHub Admin</title>

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

</head>

<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->

    <?php
    $admin_sidebar = dirname(__DIR__) . '/includes/admin_sidebar.php';

    if (file_exists($admin_sidebar)) {
        require_once $admin_sidebar;
    }
    ?>

    <!-- MAIN CONTENT -->

    <main class="admin-main">

        <!-- TOP BAR -->

        <div class="admin-topbar">

            <div>
                <h1>Products</h1>

                <p>
                    Manage all products listed by HochipoHub vendors.
                </p>
            </div>

            <div class="admin-user">

                <span>
                    <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator') ?>
                </span>

            </div>

        </div>

        <!-- ALERT -->

        <?php if (isset($_GET['success'])): ?>

            <div class="admin-alert success">

                <?php if ($_GET['success'] === 'deleted'): ?>

                    Product deleted successfully.

                <?php elseif ($_GET['success'] === 'status'): ?>

                    Product status updated successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['error'])): ?>

            <div class="admin-alert error">

                <?php if ($_GET['error'] === 'order'): ?>

                    This product cannot be deleted because it is already
                    connected to an existing order.

                <?php elseif ($_GET['error'] === 'notfound'): ?>

                    Product not found.

                <?php else: ?>

                    Unable to process the request.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- STATISTICS -->

        <section class="admin-stats">

            <div class="stat-card">

                <span class="stat-label">
                    Total Products
                </span>

                <strong>
                    <?= $total_products ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Available
                </span>

                <strong>
                    <?= $available_products ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Out of Stock
                </span>

                <strong>
                    <?= $out_of_stock ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Hidden
                </span>

                <strong>
                    <?= $hidden_products ?>
                </strong>

            </div>

        </section>


        <!-- FILTER -->

        <section class="admin-panel">

            <form
                method="GET"
                class="admin-filter-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Search product or vendor..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="Available"
                        <?= $status_filter === 'Available' ? 'selected' : '' ?>
                    >
                        Available
                    </option>

                    <option
                        value="Out of Stock"
                        <?= $status_filter === 'Out of Stock' ? 'selected' : '' ?>
                    >
                        Out of Stock
                    </option>

                    <option
                        value="Hidden"
                        <?= $status_filter === 'Hidden' ? 'selected' : '' ?>
                    >
                        Hidden
                    </option>

                </select>


                <select name="category">

                    <option value="">
                        All Categories
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= $category['category_id'] ?>"
                            <?= $category_filter == $category['category_id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $category['category_name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="admin-btn primary"
                >
                    Search
                </button>


                <a
                    href="products.php"
                    class="admin-btn secondary"
                >
                    Reset
                </a>

            </form>

        </section>


        <!-- PRODUCTS TABLE -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Product List
                    </h2>

                    <p>
                        <?= count($products) ?> product(s) found
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>ID</th>

                        <th>Product</th>

                        <th>Vendor</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Stock</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th>Action</th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($products)): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-state"
                            >

                                No products found.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($products as $product): ?>

                            <tr>

                                <!-- ID -->

                                <td>

                                    #<?= (int) $product['product_id'] ?>

                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <div class="product-table-info">

                                        <?php
                                        $image = trim(
                                            $product['image'] ?? ''
                                        );

                                        if ($image !== ''):

                                            $imagePath =
                                                '../uploads/products/' .
                                                basename($image);
                                        ?>

                                            <img
                                                src="<?= htmlspecialchars($imagePath) ?>"
                                                alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                class="table-product-image"
                                                onerror="this.style.display='none';"
                                            >

                                        <?php endif; ?>


                                        <div>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $product['product_name']
                                                ) ?>

                                            </strong>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $product['description'] ?? ''
                                                ) ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- VENDOR -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $product['business_name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= htmlspecialchars(
                                            $product['vendor_name']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product['category_name']
                                    ) ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    RM
                                    <?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>

                                </td>


                                <!-- STOCK -->

                                <td>

                                    <?= (int) $product['stock_quantity'] ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <form
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int) $product['product_id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="update_status"
                                            value="1"
                                        >

                                        <select
                                            name="status"
                                            onchange="this.form.submit()"
                                            class="status-select"
                                        >

                                            <option
                                                value="Available"
                                                <?= $product['status'] === 'Available' ? 'selected' : '' ?>
                                            >
                                                Available
                                            </option>

                                            <option
                                                value="Out of Stock"
                                                <?= $product['status'] === 'Out of Stock' ? 'selected' : '' ?>
                                            >
                                                Out of Stock
                                            </option>

                                            <option
                                                value="Hidden"
                                                <?= $product['status'] === 'Hidden' ? 'selected' : '' ?>
                                            >
                                                Hidden
                                            </option>

                                        </select>

                                    </form>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $product['created_at']
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="../product_details.php?id=<?= (int) $product['product_id'] ?>"
                                            class="admin-btn small"
                                            target="_blank"
                                        >
                                            View
                                        </a>


                                        <a
                                            href="products.php?delete=<?= (int) $product['product_id'] ?>"
                                            class="admin-btn small danger"
                                            onclick="return confirm('Are you sure you want to delete this product?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>