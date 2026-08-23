<?php

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {

        try {

            $db->beginTransaction();

            foreach (
                ['wishlist', 'cart', 'reviews', 'inventory'] as $table
            ) {

                $stmt = $db->prepare(
                    "DELETE FROM {$table}
                     WHERE product_id = ?"
                );

                $stmt->execute([$id]);
            }

            $stmt = $db->prepare(
                "SELECT COUNT(*)
                 FROM order_details
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {

                $db->rollBack();

                header('Location: products.php?error=order');
                exit;
            }

            $stmt = $db->prepare(
                "SELECT product_id
                 FROM products
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

                $db->rollBack();

                header('Location: products.php?error=notfound');
                exit;
            }

            $stmt = $db->prepare(
                "DELETE FROM products
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);

            $stmt = $db->prepare(
                "INSERT INTO admin_logs
                    (admin_id, action, target_type, target_id)
                 VALUES
                    (?, ?, ?, ?)"
            );

            $stmt->execute([
                (int) $_SESSION['user_id'],
                'Deleted product',
                'product',
                $id
            ]);

            $db->commit();

            header('Location: products.php?success=deleted');
            exit;

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log($e->getMessage());

            header('Location: products.php?error=delete');
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $id = (int) ($_POST['product_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = [
        'Available',
        'Out of Stock',
        'Hidden'
    ];

    if (
        $id > 0 &&
        in_array($status, $allowed, true)
    ) {

        try {

            $stmt = $db->prepare(
                "UPDATE products
                 SET status = ?
                 WHERE product_id = ?"
            );

            $stmt->execute([
                $status,
                $id
            ]);

            $stmt = $db->prepare(
                "INSERT INTO admin_logs
                    (admin_id, action, target_type, target_id)
                 VALUES
                    (?, ?, ?, ?)"
            );

            $stmt->execute([
                $_SESSION['user_id'],
                'Updated product status to ' . $status,
                'product',
                $id
            ]);

            header('Location: products.php?success=status');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            header('Location: products.php?error=update');
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

$categories = $db
    ->query(
        "SELECT category_id, category_name
         FROM categories
         ORDER BY category_name ASC"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

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

if ($search !== '') {

    $sql .= "
        AND (
            p.product_name LIKE ?
            OR v.business_name LIKE ?
            OR u.name LIKE ?
        )
    ";

    $v = "%$search%";

    array_push(
        $params,
        $v,
        $v,
        $v
    );
}

if ($status_filter !== '') {

    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}

if ($category_filter > 0) {

    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PRODUCT STATISTICS
|--------------------------------------------------------------------------
*/

$total_products = (int) $db
    ->query("SELECT COUNT(*) FROM products")
    ->fetchColumn();

$available_products = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM products
         WHERE status = 'Available'"
    )
    ->fetchColumn();

$out_of_stock = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM products
         WHERE status = 'Out of Stock'"
    )
    ->fetchColumn();

$hidden_products = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM products
         WHERE status = 'Hidden'"
    )
    ->fetchColumn();

?>

<!doctype html>
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

        <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main">

            <!-- Header -->

            <header class="admin-topbar">

                <div class="admin-header-left">

                    <button
                        type="button"
                        id="adminSidebarToggle"
                        class="admin-sidebar-toggle"
                        aria-label="Open sidebar"
                        aria-expanded="false"
                    >
                        ☰
                    </button>

                    <div>

                        <h1>Products</h1>

                        <p>
                            Manage all products listed by HochipoHub vendors.
                        </p>

                    </div>

                </div>

            </header>

            <!-- Success Message -->

            <?php if (isset($_GET['success'])): ?>

                <div class="admin-alert success">

                    <?= $_GET['success'] === 'deleted'
                        ? 'Product deleted successfully.'
                        : 'Product status updated successfully.'
                    ?>

                </div>

            <?php endif; ?>

            <!-- Error Message -->

            <?php if (isset($_GET['error'])): ?>

                <div class="admin-alert error">

                    <?php if ($_GET['error'] === 'order'): ?>

                        This product cannot be deleted because it is already connected to an existing order.

                    <?php elseif ($_GET['error'] === 'notfound'): ?>

                        Product not found.

                    <?php else: ?>

                        Unable to process the request.

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <!-- Statistics -->

            <section class="admin-stats">

                <?php
                foreach (
                    [
                        ['Total Products', $total_products],
                        ['Available', $available_products],
                        ['Out of Stock', $out_of_stock],
                        ['Hidden', $hidden_products]
                    ] as $s
                ):
                ?>

                    <div class="stat-card">

                        <span class="stat-label">
                            <?= e($s[0]) ?>
                        </span>

                        <strong>
                            <?= number_format($s[1]) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>

            </section>

            <!-- Filter -->

            <section class="admin-panel">

                <form
                    method="GET"
                    class="admin-filter-form"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search product or vendor..."
                        value="<?= e($search) ?>"
                    >

                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <?php foreach (
                            ['Available', 'Out of Stock', 'Hidden'] as $s
                        ): ?>

                            <option
                                value="<?= e($s) ?>"
                                <?= $status_filter === $s ? 'selected' : '' ?>
                            >
                                <?= e($s) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <select name="category">

                        <option value="">
                            All Categories
                        </option>

                        <?php foreach ($categories as $c): ?>

                            <option
                                value="<?= (int) $c['category_id'] ?>"
                                <?= $category_filter == (int) $c['category_id'] ? 'selected' : '' ?>
                            >
                                <?= e($c['category_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        class="admin-btn primary"
                        type="submit"
                    >
                        Search
                    </button>

                    <a
                        class="admin-btn secondary"
                        href="products.php"
                    >
                        Reset
                    </a>

                </form>

            </section>

            <!-- Product List -->

            <section class="admin-panel">

                <div class="panel-header">

                    <div>

                        <h2>Product List</h2>

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

                            <?php if (!$products): ?>

                                <tr>

                                    <td
                                        colspan="9"
                                        class="empty-state"
                                    >
                                        No products found.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($products as $p): ?>

                                    <tr>

                                        <td>
                                            #<?= (int) $p['product_id'] ?>
                                        </td>

                                        <!-- Product -->

                                        <td>

                                            <div class="product-table-info">

                                                <?php
                                                $image = trim($p['image'] ?? '');
                                                ?>

                                                <?php if ($image !== ''): ?>

                                                    <img
                                                        class="table-product-image"
                                                        src="../uploads/products/<?= e(basename($image)) ?>"
                                                        alt="<?= e($p['product_name']) ?>"
                                                        onerror="this.style.display='none';"
                                                    >

                                                <?php endif; ?>

                                                <div>

                                                    <strong>
                                                        <?= e($p['product_name']) ?>
                                                    </strong>

                                                    <small>
                                                        <?= e($p['description'] ?? '') ?>
                                                    </small>

                                                </div>

                                            </div>

                                        </td>

                                        <!-- Vendor -->

                                        <td>

                                            <strong>
                                                <?= e($p['business_name']) ?>
                                            </strong>

                                            <small>
                                                <?= e($p['vendor_name']) ?>
                                            </small>

                                        </td>

                                        <!-- Category -->

                                        <td>
                                            <?= e($p['category_name']) ?>
                                        </td>

                                        <!-- Price -->

                                        <td>
                                            RM <?= number_format(
                                                (float) $p['price'],
                                                2
                                            ) ?>
                                        </td>

                                        <!-- Stock -->

                                        <td>
                                            <?= (int) $p['stock_quantity'] ?>
                                        </td>

                                        <!-- Status -->

                                        <td>

                                            <form
                                                method="POST"
                                                class="inline-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="product_id"
                                                    value="<?= (int) $p['product_id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="update_status"
                                                    value="1"
                                                >

                                                <select
                                                    class="status-select"
                                                    name="status"
                                                    onchange="this.form.submit()"
                                                >

                                                    <?php foreach (
                                                        ['Available', 'Out of Stock', 'Hidden'] as $s
                                                    ): ?>

                                                        <option
                                                            value="<?= e($s) ?>"
                                                            <?= $p['status'] === $s ? 'selected' : '' ?>
                                                        >
                                                            <?= e($s) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </form>

                                        </td>

                                        <!-- Created -->

                                        <td>
                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime($p['created_at'])
                                                )
                                            ) ?>
                                        </td>

                                        <!-- Action -->

                                        <td>

                                            <div class="table-actions">

                                                <a
                                                    class="admin-btn small"
                                                    href="../product_details.php?id=<?= (int) $p['product_id'] ?>"
                                                    target="_blank"
                                                >
                                                    View
                                                </a>

                                                <a
                                                    class="admin-btn small danger"
                                                    href="products.php?delete=<?= (int) $p['product_id'] ?>"
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