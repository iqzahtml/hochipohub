<?php
/**
 * =========================================================
 * HOCHIPOHUB
 * SELLER - ADD PRODUCT
 * File: seller/add_product.php
 * =========================================================
 */

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| SECURITY - VENDOR ONLY
|--------------------------------------------------------------------------
*/

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
| DEFAULT FORM VALUES
|--------------------------------------------------------------------------
*/

$productName = '';
$description = '';
$price = '';
$stockQuantity = '';
$categoryId = 0;


/*
|--------------------------------------------------------------------------
| GET VENDOR INFORMATION
|--------------------------------------------------------------------------
*/

$vendorStmt = $db->prepare("
    SELECT
        vendor_id,
        business_name,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$vendorStmt->execute([
    $userId
]);

$vendor = $vendorStmt->fetch(PDO::FETCH_ASSOC);


if (!$vendor) {

    $errors[] = "Vendor profile was not found.";

}


/*
|--------------------------------------------------------------------------
| CHECK VENDOR APPROVAL
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
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

$categoryStmt = $db->prepare("
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

$categoryStmt->execute();

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($errors)
) {

    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $productName = trim(
        $_POST['product_name'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $price = trim(
        $_POST['price'] ?? ''
    );

    $stockQuantity = trim(
        $_POST['stock_quantity'] ?? ''
    );

    $categoryId = (int) (
        $_POST['category_id'] ?? 0
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($productName === '') {

        $errors[] =
            "Product name is required.";

    }


    if (strlen($productName) > 150) {

        $errors[] =
            "Product name cannot exceed 150 characters.";

    }


    if ($categoryId <= 0) {

        $errors[] =
            "Please select a product category.";

    }


    if (
        $price === '' ||
        !is_numeric($price)
    ) {

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
    | CHECK CATEGORY EXISTS
    |--------------------------------------------------------------------------
    */

    if ($categoryId > 0) {

        $categoryCheck = $db->prepare("
            SELECT
                category_id
            FROM categories
            WHERE category_id = ?
            LIMIT 1
        ");

        $categoryCheck->execute([
            $categoryId
        ]);

        $categoryExists =
            $categoryCheck->fetch(PDO::FETCH_ASSOC);


        if (!$categoryExists) {

            $errors[] =
                "Selected category does not exist.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT IMAGE
    |--------------------------------------------------------------------------
    */

    $imageName = null;
    $targetPath = null;


    if (
        isset($_FILES['product_image']) &&
        $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['product_image'];


        /*
        |--------------------------------------------------------------------------
        | CHECK UPLOAD ERROR
        |--------------------------------------------------------------------------
        */

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errors[] =
                "There was a problem uploading the product image.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | ALLOWED MIME TYPES
            |--------------------------------------------------------------------------
            */

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $maxSize = 5 * 1024 * 1024;


            /*
            |--------------------------------------------------------------------------
            | FILE SIZE
            |--------------------------------------------------------------------------
            */

            if ($file['size'] > $maxSize) {

                $errors[] =
                    "Product image must not exceed 5MB.";

            }


            /*
            |--------------------------------------------------------------------------
            | MIME CHECK
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $finfo =
                    new finfo(FILEINFO_MIME_TYPE);

                $realMime =
                    $finfo->file(
                        $file['tmp_name']
                    );


                if (
                    !in_array(
                        $realMime,
                        $allowedTypes,
                        true
                    )
                ) {

                    $errors[] =
                        "Only JPG, PNG and WEBP images are allowed.";

                }

            }


            /*
            |--------------------------------------------------------------------------
            | GENERATE SAFE FILE NAME
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $extensionMap = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $extension =
                    $extensionMap[$realMime];


                $imageName =
                    'product_' .
                    $vendor['vendor_id'] .
                    '_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $extension;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT PRODUCT
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $priceValue =
            (float) $price;

        $stockValue =
            (int) $stockQuantity;


        /*
        |--------------------------------------------------------------------------
        | PRODUCT STATUS
        |--------------------------------------------------------------------------
        */

        $productStatus =
            $stockValue > 0
                ? 'Available'
                : 'Out of Stock';


        /*
        |--------------------------------------------------------------------------
        | UPLOAD DIRECTORY
        |--------------------------------------------------------------------------
        */

        $uploadDirectory =
            __DIR__ .
            '/../uploads/products/';


        if (!is_dir($uploadDirectory)) {

            if (
                !mkdir(
                    $uploadDirectory,
                    0755,
                    true
                )
            ) {

                $errors[] =
                    "Unable to create product upload directory.";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | MOVE IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors) &&
            $imageName !== null
        ) {

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

            }

        }


        /*
        |--------------------------------------------------------------------------
        | DATABASE TRANSACTION
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                /*
                |--------------------------------------------------------------------------
                | START TRANSACTION
                |--------------------------------------------------------------------------
                */

                $db->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | INSERT PRODUCT
                |--------------------------------------------------------------------------
                */

                $productStmt = $db->prepare("
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


                $productStmt->execute([
                    (int) $vendor['vendor_id'],
                    $categoryId,
                    $productName,
                    $description,
                    $priceValue,
                    $stockValue,
                    $imageName,
                    $productStatus
                ]);


                /*
                |--------------------------------------------------------------------------
                | GET PRODUCT ID
                |--------------------------------------------------------------------------
                */

                $productId =
                    (int) $db->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | INSERT INVENTORY
                |--------------------------------------------------------------------------
                */

                $inventoryStmt = $db->prepare("
                    INSERT INTO inventory (
                        product_id,
                        quantity
                    )
                    VALUES (?, ?)
                ");


                $inventoryStmt->execute([
                    $productId,
                    $stockValue
                ]);


                /*
                |--------------------------------------------------------------------------
                | COMMIT
                |--------------------------------------------------------------------------
                */

                $db->commit();


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                $success =
                    "Product added successfully.";


                /*
                |--------------------------------------------------------------------------
                | CLEAR FORM
                |--------------------------------------------------------------------------
                */

                $productName = '';
                $description = '';
                $price = '';
                $stockQuantity = '';
                $categoryId = 0;


            } catch (PDOException $e) {

                /*
                |--------------------------------------------------------------------------
                | ROLLBACK
                |--------------------------------------------------------------------------
                */

                if ($db->inTransaction()) {
                    $db->rollBack();
                }


                /*
                |--------------------------------------------------------------------------
                | REMOVE UPLOADED IMAGE
                |--------------------------------------------------------------------------
                */

                if (
                    $imageName !== null &&
                    $targetPath !== null &&
                    file_exists($targetPath)
                ) {

                    unlink($targetPath);

                }


                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */

                $errors[] =
                    "Unable to add product. Please try again.";

                /*
                | For debugging during development, you can temporarily use:
                |
                | $errors[] = $e->getMessage();
                |
                */

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| PAGE VARIABLES
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Add Product - HochipoHub";

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

$sidebarPath =
    __DIR__ .
    '/../includes/vendor_sidebar.php';

if (file_exists($sidebarPath)) {

    include $sidebarPath;

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