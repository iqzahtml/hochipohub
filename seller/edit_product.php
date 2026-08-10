<?php
require_once '../database/db.php';
require_once '../includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK VENDOR ROLE
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$errors = [];
$success = "";

/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT vendor_id, approval_status
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
    header("Location: dashboard.php?error=vendor_not_found");
    exit;
}

$vendor_id = (int) $vendor['vendor_id'];

/*
|--------------------------------------------------------------------------
| CHECK VENDOR APPROVAL
|--------------------------------------------------------------------------
*/
if ($vendor['approval_status'] !== 'Approved') {
    header("Location: dashboard.php?error=vendor_not_approved");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php?error=invalid_product");
    exit;
}

$product_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        product_id,
        vendor_id,
        category_id,
        product_name,
        description,
        price,
        stock_quantity,
        image,
        status
    FROM products
    WHERE product_id = ?
    AND vendor_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $product_id, $vendor_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    header("Location: products.php?error=product_not_found");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/
$categories = [];

$result = $conn->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock_quantity = trim($_POST['stock_quantity'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($product_name === '') {
        $errors[] = "Product name is required.";
    }

    if ($category_id === '' || !is_numeric($category_id)) {
        $errors[] = "Please select a valid category.";
    }

    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = "Please enter a valid price.";
    }

    if (
        $stock_quantity === '' ||
        !is_numeric($stock_quantity) ||
        (int)$stock_quantity < 0
    ) {
        $errors[] = "Please enter a valid stock quantity.";
    }

    $allowed_status = [
        'Available',
        'Out of Stock',
        'Hidden'
    ];

    if (!in_array($status, $allowed_status, true)) {
        $errors[] = "Invalid product status.";
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO STATUS BASED ON STOCK
    |--------------------------------------------------------------------------
    */
    $stock_quantity_int = (int)$stock_quantity;

    if ($stock_quantity_int <= 0 && $status === 'Available') {
        $status = 'Out of Stock';
    }

    if ($stock_quantity_int > 0 && $status === 'Out of Stock') {
        $status = 'Available';
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */
    $new_image_name = $product['image'];

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Failed to upload image.";
        } else {

            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_size = $_FILES['image']['size'];

            $extension = strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed_extensions, true)) {
                $errors[] = "Only JPG, JPEG, PNG and WEBP images are allowed.";
            }

            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "Image size must not exceed 5MB.";
            }

            if (empty($errors)) {

                $new_image_name =
                    'product_' .
                    $product_id .
                    '_' .
                    time() .
                    '_' .
                    bin2hex(random_bytes(4)) .
                    '.' .
                    $extension;

                $upload_directory = '../uploads/products/';

                if (!is_dir($upload_directory)) {
                    mkdir(
                        $upload_directory,
                        0755,
                        true
                    );
                }

                $upload_path =
                    $upload_directory .
                    $new_image_name;

                if (!move_uploaded_file(
                    $file_tmp,
                    $upload_path
                )) {
                    $errors[] = "Unable to save uploaded image.";
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE DATABASE
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {

        $category_id_int = (int)$category_id;
        $price_float = (float)$price;

        $stmt = $conn->prepare("
            UPDATE products
            SET
                category_id = ?,
                product_name = ?,
                description = ?,
                price = ?,
                stock_quantity = ?,
                image = ?,
                status = ?
            WHERE product_id = ?
            AND vendor_id = ?
        ");

        $stmt->bind_param(
            "issdissii",
            $category_id_int,
            $product_name,
            $description,
            $price_float,
            $stock_quantity_int,
            $new_image_name,
            $status,
            $product_id,
            $vendor_id
        );

        if ($stmt->execute()) {

            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | UPDATE INVENTORY
            |--------------------------------------------------------------------------
            */
            $stmt = $conn->prepare("
                INSERT INTO inventory
                (
                    product_id,
                    quantity
                )
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                    quantity = VALUES(quantity)
            ");

            $stmt->bind_param(
                "ii",
                $product_id,
                $stock_quantity_int
            );

            $stmt->execute();
            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | DELETE OLD IMAGE
            |--------------------------------------------------------------------------
            */
            if (
                $new_image_name !== $product['image'] &&
                !empty($product['image'])
            ) {

                $old_image =
                    '../uploads/products/' .
                    $product['image'];

                if (
                    file_exists($old_image) &&
                    is_file($old_image)
                ) {
                    unlink($old_image);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */
            header(
                "Location: products.php?success=product_updated"
            );
            exit;

        } else {

            $errors[] =
                "Failed to update product: " .
                $stmt->error;

            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | REMOVE NEW IMAGE IF DATABASE UPDATE FAILED
            |--------------------------------------------------------------------------
            */
            if (
                $new_image_name !== $product['image'] &&
                !empty($new_image_name)
            ) {

                $new_file =
                    '../uploads/products/' .
                    $new_image_name;

                if (
                    file_exists($new_file) &&
                    is_file($new_file)
                ) {
                    unlink($new_file);
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| USE POST VALUES AFTER ERROR
|--------------------------------------------------------------------------
*/
$current_name =
    $_POST['product_name']
    ?? $product['product_name'];

$current_description =
    $_POST['description']
    ?? $product['description'];

$current_price =
    $_POST['price']
    ?? $product['price'];

$current_stock =
    $_POST['stock_quantity']
    ?? $product['stock_quantity'];

$current_category =
    $_POST['category_id']
    ?? $product['category_id'];

$current_status =
    $_POST['status']
    ?? $product['status'];

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Product | HochipoHub</title>

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

<?php
if (file_exists('../includes/navbar.php')) {
    include '../includes/navbar.php';
}
?>

<div class="dashboard-layout">

    <?php
    if (file_exists('../includes/vendor_sidebar.php')) {
        include '../includes/vendor_sidebar.php';
    }
    ?>

    <main class="dashboard-content">

        <div class="page-header">

            <div>
                <h1>Edit Product</h1>

                <p>
                    Update your product information
                </p>
            </div>

            <a
                href="products.php"
                class="btn btn-secondary"
            >
                ← Back to Products
            </a>

        </div>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <strong>
                    Please fix the following:
                </strong>

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <div class="form-card">

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="form-group">

                    <label for="product_name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="<?= htmlspecialchars($current_name) ?>"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category['category_id'] ?>"
                                <?= (
                                    (string)$current_category ===
                                    (string)$category['category_id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $category['category_name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="price">
                            Price (RM)
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="<?= htmlspecialchars($current_price) ?>"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="stock_quantity">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            id="stock_quantity"
                            name="stock_quantity"
                            value="<?= htmlspecialchars($current_stock) ?>"
                            min="0"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                    ><?= htmlspecialchars($current_description) ?></textarea>

                </div>


                <div class="form-group">

                    <label for="status">
                        Product Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option
                            value="Available"
                            <?= $current_status === 'Available'
                                ? 'selected'
                                : '' ?>
                        >
                            Available
                        </option>

                        <option
                            value="Out of Stock"
                            <?= $current_status === 'Out of Stock'
                                ? 'selected'
                                : '' ?>
                        >
                            Out of Stock
                        </option>

                        <option
                            value="Hidden"
                            <?= $current_status === 'Hidden'
                                ? 'selected'
                                : '' ?>
                        >
                            Hidden
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Current Image
                    </label>

                    <div class="current-product-image">

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="../uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['product_name']) ?>"
                                style="
                                    max-width:220px;
                                    max-height:220px;
                                    object-fit:cover;
                                    border-radius:12px;
                                "
                            >

                        <?php else: ?>

                            <p>
                                No image uploaded.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="form-group">

                    <label for="image">
                        Replace Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        JPG, JPEG, PNG or WEBP. Maximum 5MB.
                    </small>

                </div>


                <div class="form-actions">

                    <a
                        href="products.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Update Product
                    </button>

                </div>

            </form>

        </div>

    </main>

</div>

<?php
if (file_exists('../includes/footer.php')) {
    include '../includes/footer.php';
}
?>

</body>
</html>