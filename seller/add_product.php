<?php
/**
 * =========================================================
 * HOCHIPOHUB
 * SELLER - ADD PRODUCT
 * File: seller/add_product.php
 * =========================================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| Security - Vendor Only
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'vendor'
) {
    header("Location: ../dashboard.php");
    exit;
}


$userId = (int) $_SESSION['user_id'];

$errors = [];
$success = "";


/*
|--------------------------------------------------------------------------
| Get Vendor Information
|--------------------------------------------------------------------------
*/

$vendorStmt = $conn->prepare("
    SELECT
        vendor_id,
        business_name,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$vendorStmt->bind_param("i", $userId);
$vendorStmt->execute();

$vendorResult = $vendorStmt->get_result();

$vendor = $vendorResult->fetch_assoc();

$vendorStmt->close();


if (!$vendor) {
    $errors[] = "Vendor profile was not found.";
}


/*
|--------------------------------------------------------------------------
| Check Vendor Approval
|--------------------------------------------------------------------------
*/

if (
    $vendor &&
    $vendor['approval_status'] !== 'Approved'
) {

    $errors[] =
        "Your vendor account must be approved before you can add products.";

}


/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$categories = [];

$categoryQuery = $conn->query("
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

if ($categoryQuery) {

    while ($row = $categoryQuery->fetch_assoc()) {
        $categories[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($errors)
) {

    $productName = trim($_POST['product_name'] ?? '');

    $description = trim($_POST['description'] ?? '');

    $price = trim($_POST['price'] ?? '');

    $stockQuantity = trim($_POST['stock_quantity'] ?? '');

    $categoryId = (int) ($_POST['category_id'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($productName === '') {

        $errors[] = "Product name is required.";

    }

    if (strlen($productName) > 150) {

        $errors[] =
            "Product name cannot exceed 150 characters.";

    }


    if ($categoryId <= 0) {

        $errors[] =
            "Please select a product category.";

    }


    if ($price === '' || !is_numeric($price)) {

        $errors[] =
            "Please enter a valid product price.";

    } elseif ((float) $price < 0) {

        $errors[] =
            "Product price cannot be negative.";

    }


    if (
        $stockQuantity === '' ||
        !is_numeric($stockQuantity) ||
        (int) $stockQuantity < 0
    ) {

        $errors[] =
            "Please enter a valid stock quantity.";

    }


    /*
    |--------------------------------------------------------------------------
    | Check Category
    |--------------------------------------------------------------------------
    */

    if ($categoryId > 0) {

        $categoryCheck = $conn->prepare("
            SELECT category_id
            FROM categories
            WHERE category_id = ?
            LIMIT 1
        ");

        $categoryCheck->bind_param(
            "i",
            $categoryId
        );

        $categoryCheck->execute();

        $categoryExists =
            $categoryCheck->get_result()->num_rows > 0;

        $categoryCheck->close();


        if (!$categoryExists) {

            $errors[] =
                "Selected category does not exist.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Product Image
    |--------------------------------------------------------------------------
    */

    $imageName = null;

    if (
        isset($_FILES['product_image']) &&
        $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['product_image'];


        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errors[] =
                "There was a problem uploading the product image.";

        } else {

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $maxSize = 5 * 1024 * 1024;


            if (!in_array($file['type'], $allowedTypes, true)) {

                $errors[] =
                    "Only JPG, PNG and WEBP images are allowed.";

            }


            if ($file['size'] > $maxSize) {

                $errors[] =
                    "Product image must not exceed 5MB.";

            }


            /*
            |--------------------------------------------------------------------------
            | More Reliable MIME Check
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $finfo =
                    new finfo(FILEINFO_MIME_TYPE);

                $realMime =
                    $finfo->file($file['tmp_name']);


                if (
                    !in_array(
                        $realMime,
                        $allowedTypes,
                        true
                    )
                ) {

                    $errors[] =
                        "Invalid image file.";

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Generate Safe Filename
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $extension =
                    strtolower(
                        pathinfo(
                            $file['name'],
                            PATHINFO_EXTENSION
                        )
                    );

                $imageName =
                    'product_' .
                    $vendor['vendor_id'] .
                    '_' .
                    uniqid('', true) .
                    '.' .
                    $extension;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Insert Product
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $priceValue =
            number_format(
                (float) $price,
                2,
                '.',
                ''
            );

        $stockValue =
            (int) $stockQuantity;


        /*
        |--------------------------------------------------------------------------
        | Product Status
        |--------------------------------------------------------------------------
        */

        $productStatus =
            $stockValue > 0
            ? 'Available'
            : 'Out of Stock';


        /*
        |--------------------------------------------------------------------------
        | Upload Directory
        |--------------------------------------------------------------------------
        */

        $uploadDirectory =
            dirname(__DIR__) .
            '/uploads/products/';


        if (!is_dir($uploadDirectory)) {

            mkdir(
                $uploadDirectory,
                0755,
                true
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Move Image
        |--------------------------------------------------------------------------
        */

        $imageUploaded = false;

        if ($imageName !== null) {

            $targetPath =
                $uploadDirectory .
                $imageName;


            if (
                !move_uploaded_file(
                    $_FILES['product_image']['tmp_name'],
                    $targetPath
                )
            ) {

                $errors[] =
                    "Failed to save product image.";

                $imageName = null;

            } else {

                $imageUploaded = true;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            $conn->begin_transaction();

            try {

                /*
                --------------------------------------------------------------
                | Insert Product
                --------------------------------------------------------------
                */

                $productStmt = $conn->prepare("
                    INSERT INTO products (
                        vendor_id,
                        category_id,
                        product_name,
                        description,
                        price,
                        stock_quantity,
                        image,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");


                $productStmt->bind_param(
                    "iissdiss",
                    $vendor['vendor_id'],
                    $categoryId,
                    $productName,
                    $description,
                    $priceValue,
                    $stockValue,
                    $imageName,
                    $productStatus
                );


                if (!$productStmt->execute()) {

                    throw new Exception(
                        "Failed to create product."
                    );

                }


                $productId =
                    $conn->insert_id;


                $productStmt->close();


                /*
                --------------------------------------------------------------
                | Insert Inventory
                --------------------------------------------------------------
                */

                $inventoryStmt = $conn->prepare("
                    INSERT INTO inventory (
                        product_id,
                        quantity
                    )
                    VALUES (?, ?)
                ");


                $inventoryStmt->bind_param(
                    "ii",
                    $productId,
                    $stockValue
                );


                if (!$inventoryStmt->execute()) {

                    throw new Exception(
                        "Failed to create inventory record."
                    );

                }


                $inventoryStmt->close();


                /*
                --------------------------------------------------------------
                | Commit
                --------------------------------------------------------------
                */

                $conn->commit();


                $success =
                    "Product added successfully.";


                /*
                --------------------------------------------------------------
                | Clear Form
                --------------------------------------------------------------
                */

                $productName = '';
                $description = '';
                $price = '';
                $stockQuantity = '';
                $categoryId = 0;


            } catch (Exception $e) {

                $conn->rollback();


                /*
                --------------------------------------------------------------
                | Remove uploaded image if DB failed
                --------------------------------------------------------------
                */

                if (
                    $imageName !== null &&
                    isset($targetPath) &&
                    file_exists($targetPath)
                ) {

                    unlink($targetPath);

                }


                $errors[] =
                    "Unable to add product. Please try again.";

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Page Variables
|--------------------------------------------------------------------------
*/

$pageTitle = "Add Product - HochipoHub";

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
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
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

if (file_exists(
    dirname(__DIR__) .
    '/includes/vendor_sidebar.php'
)) {

    include dirname(__DIR__) .
        '/includes/vendor_sidebar.php';

}

?>


<main class="dashboard-main">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="dashboard-header">

        <div>

            <span
                style="
                    color:#2563eb;
                    font-weight:700;
                "
            >
                SELLER PANEL
            </span>

            <h1>
                Add New Product
            </h1>

            <p>
                Add your product to HochipoHub marketplace.
            </p>

        </div>


        <a
            href="products.php"
            class="btn"
        >
            ← My Products
        </a>

    </div>


    <!-- =====================================================
         ALERTS
    ====================================================== -->

    <?php if (!empty($errors)): ?>

        <div
            style="
                margin:20px 0;
                padding:16px 20px;
                border-radius:12px;
                background:#fee2e2;
                color:#991b1b;
            "
        >

            <?php foreach ($errors as $error): ?>

                <div>
                    •
                    <?php
                    echo htmlspecialchars($error);
                    ?>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div
            style="
                margin:20px 0;
                padding:16px 20px;
                border-radius:12px;
                background:#dcfce7;
                color:#166534;
            "
        >

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FORM
    ====================================================== -->

    <section
        style="
            max-width:900px;
            margin:30px auto;
            background:#ffffff;
            padding:30px;
            border-radius:20px;
            box-shadow:0 8px 30px rgba(15,23,42,.08);
        "
    >

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- PRODUCT NAME -->

            <div
                style="
                    margin-bottom:22px;
                "
            >

                <label
                    for="product_name"
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    Product Name
                </label>

                <input
                    type="text"
                    id="product_name"
                    name="product_name"
                    maxlength="150"
                    required
                    value="<?php
                        echo htmlspecialchars(
                            $productName ?? ''
                        );
                    ?>"
                    style="
                        width:100%;
                        padding:14px;
                        border:1px solid #cbd5e1;
                        border-radius:10px;
                    "
                >

            </div>


            <!-- CATEGORY -->

            <div
                style="
                    margin-bottom:22px;
                "
            >

                <label
                    for="category_id"
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    Category
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                    style="
                        width:100%;
                        padding:14px;
                        border:1px solid #cbd5e1;
                        border-radius:10px;
                    "
                >

                    <option value="">
                        Select Category
                    </option>

                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?php
                                echo (int)
                                    $category['category_id'];
                            ?>"
                            <?php
                            echo (
                                (int)
                                ($categoryId ?? 0)
                                ===
                                (int)
                                $category['category_id']
                            )
                            ? 'selected'
                            : '';
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $category['category_name']
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- DESCRIPTION -->

            <div
                style="
                    margin-bottom:22px;
                "
            >

                <label
                    for="description"
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    style="
                        width:100%;
                        padding:14px;
                        border:1px solid #cbd5e1;
                        border-radius:10px;
                        resize:vertical;
                    "
                ><?php
                    echo htmlspecialchars(
                        $description ?? ''
                    );
                ?></textarea>

            </div>


            <!-- PRICE + STOCK -->

            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:20px;
                    margin-bottom:22px;
                "
            >


                <div>

                    <label
                        for="price"
                        style="
                            display:block;
                            font-weight:700;
                            margin-bottom:8px;
                        "
                    >
                        Price (RM)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        required
                        value="<?php
                            echo htmlspecialchars(
                                $price ?? ''
                            );
                        ?>"
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #cbd5e1;
                            border-radius:10px;
                        "
                    >

                </div>


                <div>

                    <label
                        for="stock_quantity"
                        style="
                            display:block;
                            font-weight:700;
                            margin-bottom:8px;
                        "
                    >
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        min="0"
                        step="1"
                        required
                        value="<?php
                            echo htmlspecialchars(
                                $stockQuantity ?? ''
                            );
                        ?>"
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #cbd5e1;
                            border-radius:10px;
                        "
                    >

                </div>

            </div>


            <!-- IMAGE -->

            <div
                style="
                    margin-bottom:25px;
                "
            >

                <label
                    for="product_image"
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    Product Image
                </label>

                <input
                    type="file"
                    id="product_image"
                    name="product_image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small
                    style="
                        display:block;
                        margin-top:8px;
                        color:#64748b;
                    "
                >
                    JPG, PNG or WEBP. Maximum 5MB.
                </small>

            </div>


            <!-- BUTTONS -->

            <div
                style="
                    display:flex;
                    gap:12px;
                    justify-content:flex-end;
                "
            >

                <a
                    href="products.php"
                    class="btn"
                    style="
                        text-decoration:none;
                    "
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="btn"
                    style="
                        border:none;
                        cursor:pointer;
                        background:#2563eb;
                        color:#ffffff;
                    "
                >
                    Add Product
                </button>

            </div>


        </form>

    </section>


</main>


</body>

</html>