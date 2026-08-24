<?php

/**
 * =========================================================
 * HOCHIPOHUB
 * SELLER - EDIT PRODUCT
 * File: seller/edit_product.php
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| LOAD DATABASE + SESSION + FUNCTIONS
|--------------------------------------------------------------------------
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
| SECURITY - LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ../index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| SECURITY - VENDOR ONLY
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'vendor'
) {

    header(
        'Location: ../dashboard.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];

$errors = [];

$success = '';


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
|
| Example:
|
| seller/edit_product.php?id=5
|
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id']) ||
    (int) $_GET['id'] <= 0
) {

    header(
        'Location: products.php?error=invalid_product'
    );

    exit;
}


$productId = (int) $_GET['id'];


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

try {

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

    $vendor = $vendorStmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $vendor = false;

}


if (!$vendor) {

    header(
        'Location: dashboard.php?error=vendor_not_found'
    );

    exit;
}


$vendorId = (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| CHECK VENDOR APPROVAL
|--------------------------------------------------------------------------
*/

if (
    $vendor['approval_status'] !== 'Approved'
) {

    header(
        'Location: dashboard.php?error=vendor_not_approved'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Product must belong to the logged-in vendor.
|
|--------------------------------------------------------------------------
*/

try {

    $productStmt = $db->prepare("
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

    $productStmt->execute([
        $productId,
        $vendorId
    ]);

    $product = $productStmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $product = false;

}


if (!$product) {

    header(
        'Location: products.php?error=product_not_found'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];


try {

    $categoryStmt = $db->prepare("
        SELECT
            category_id,
            category_name
        FROM categories
        ORDER BY category_name ASC
    ");

    $categoryStmt->execute();

    $categories = $categoryStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $categories = [];

    $errors[] =
        'Unable to load product categories.';

}


/*
|--------------------------------------------------------------------------
| DEFAULT FORM VALUES
|--------------------------------------------------------------------------
*/

$currentName =
    $product['product_name'];

$currentDescription =
    $product['description'];

$currentPrice =
    $product['price'];

$currentStock =
    $product['stock_quantity'];

$currentCategory =
    $product['category_id'];

$currentStatus =
    $product['status'];


/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $currentName =
        trim(
            $_POST['product_name'] ?? ''
        );

    $currentDescription =
        trim(
            $_POST['description'] ?? ''
        );

    $currentPrice =
        trim(
            $_POST['price'] ?? ''
        );

    $currentStock =
        trim(
            $_POST['stock_quantity'] ?? ''
        );

    $currentCategory =
        (int) (
            $_POST['category_id'] ?? 0
        );

    $currentStatus =
        trim(
            $_POST['status'] ?? 'Available'
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if (
        $currentName === ''
    ) {

        $errors[] =
            'Product name is required.';

    }


    if (
        strlen($currentName) > 150
    ) {

        $errors[] =
            'Product name cannot exceed 150 characters.';

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CATEGORY
    |--------------------------------------------------------------------------
    */

    if (
        $currentCategory <= 0
    ) {

        $errors[] =
            'Please select a product category.';

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRICE
    |--------------------------------------------------------------------------
    */

    if (
        $currentPrice === '' ||
        !is_numeric($currentPrice)
    ) {

        $errors[] =
            'Please enter a valid product price.';

    } elseif (
        (float) $currentPrice < 0
    ) {

        $errors[] =
            'Product price cannot be negative.';

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STOCK
    |--------------------------------------------------------------------------
    */

    if (
        $currentStock === '' ||
        !is_numeric($currentStock) ||
        (int) $currentStock < 0
    ) {

        $errors[] =
            'Please enter a valid stock quantity.';

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
    |--------------------------------------------------------------------------
    */

    $allowedStatuses = [
        'Available',
        'Out of Stock',
        'Hidden'
    ];


    if (
        !in_array(
            $currentStatus,
            $allowedStatuses,
            true
        )
    ) {

        $errors[] =
            'Invalid product status.';

    }


    /*
    |--------------------------------------------------------------------------
    | AUTO STOCK STATUS
    |--------------------------------------------------------------------------
    |
    | Hidden remains Hidden.
    |
    | Available + stock 0
    |       -> Out of Stock
    |
    | Out of Stock + stock > 0
    |       -> Available
    |
    |--------------------------------------------------------------------------
    */

    $stockValue =
        (int) $currentStock;


    if (
        $currentStatus !== 'Hidden'
    ) {

        if (
            $stockValue <= 0
        ) {

            $currentStatus =
                'Out of Stock';

        } else {

            $currentStatus =
                'Available';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK CATEGORY EXISTS
    |--------------------------------------------------------------------------
    */

    if (
        $currentCategory > 0
    ) {

        try {

            $categoryCheck =
                $db->prepare("
                    SELECT
                        category_id
                    FROM categories
                    WHERE category_id = ?
                    LIMIT 1
                ");

            $categoryCheck->execute([
                $currentCategory
            ]);

            $categoryExists =
                $categoryCheck->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$categoryExists) {

                $errors[] =
                    'Selected category does not exist.';

            }

        } catch (PDOException $e) {

            $errors[] =
                'Unable to verify selected category.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE VARIABLES
    |--------------------------------------------------------------------------
    */

    $oldImage =
        $product['image'];

    $newImage =
        $oldImage;

    $newImagePath =
        null;


    /*
    |--------------------------------------------------------------------------
    | IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        $file =
            $_FILES['image'];


        /*
        |--------------------------------------------------------------------------
        | UPLOAD ERROR
        |--------------------------------------------------------------------------
        */

        if (
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                'There was a problem uploading the product image.';

        }


        /*
        |--------------------------------------------------------------------------
        | FILE SIZE
        |--------------------------------------------------------------------------
        */

        $maxSize =
            5 * 1024 * 1024;


        if (
            empty($errors) &&
            $file['size'] > $maxSize
        ) {

            $errors[] =
                'Product image must not exceed 5MB.';

        }


        /*
        |--------------------------------------------------------------------------
        | MIME TYPE
        |--------------------------------------------------------------------------
        */

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];


        $realMime =
            null;


        if (
            empty($errors)
        ) {

            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $realMime =
                $finfo->file(
                    $file['tmp_name']
                );


            if (
                !in_array(
                    $realMime,
                    $allowedMimeTypes,
                    true
                )
            ) {

                $errors[] =
                    'Only JPG, PNG and WEBP images are allowed.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | MIME TO EXTENSION
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors)
        ) {

            $extensionMap = [

                'image/jpeg' =>
                    'jpg',

                'image/png' =>
                    'png',

                'image/webp' =>
                    'webp'

            ];


            $extension =
                $extensionMap[$realMime];


            /*
            |--------------------------------------------------------------------------
            | SAFE RANDOM FILE NAME
            |--------------------------------------------------------------------------
            */

            $newImage =
                'product_' .
                $vendorId .
                '_' .
                bin2hex(
                    random_bytes(8)
                ) .
                '.' .
                $extension;


            /*
            |--------------------------------------------------------------------------
            | UPLOAD DIRECTORY
            |--------------------------------------------------------------------------
            */

            $uploadDirectory =
                __DIR__ .
                '/../uploads/products/';


            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    $errors[] =
                        'Unable to create product upload directory.';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | MOVE IMAGE
            |--------------------------------------------------------------------------
            */

            if (
                empty($errors)
            ) {

                $newImagePath =
                    $uploadDirectory .
                    $newImage;


                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $newImagePath
                    )
                ) {

                    $errors[] =
                        'Failed to save the new product image.';

                    $newImage =
                        $oldImage;

                    $newImagePath =
                        null;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {

        try {


            /*
            |--------------------------------------------------------------------------
            | CONVERT VALUES
            |--------------------------------------------------------------------------
            */

            $priceValue =
                (float) $currentPrice;

            $stockValue =
                (int) $currentStock;


            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | UPDATE PRODUCTS
            |--------------------------------------------------------------------------
            */

            $updateProductStmt =
                $db->prepare("
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


            $updateProductStmt->execute([

                $currentCategory,

                $currentName,

                $currentDescription,

                $priceValue,

                $stockValue,

                $newImage,

                $currentStatus,

                $productId,

                $vendorId

            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE INVENTORY
            |--------------------------------------------------------------------------
            |
            | INSERT if product does not have inventory record.
            | UPDATE if record already exists.
            |
            |--------------------------------------------------------------------------
            */

            $inventoryCheckStmt =
                $db->prepare("
                    SELECT
                        product_id
                    FROM inventory
                    WHERE product_id = ?
                    LIMIT 1
                ");


            $inventoryCheckStmt->execute([
                $productId
            ]);


            $inventoryExists =
                $inventoryCheckStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if ($inventoryExists) {


                /*
                |--------------------------------------------------------------------------
                | UPDATE INVENTORY
                |--------------------------------------------------------------------------
                */

                $inventoryStmt =
                    $db->prepare("
                        UPDATE inventory

                        SET
                            quantity = ?

                        WHERE product_id = ?
                    ");


                $inventoryStmt->execute([
                    $stockValue,
                    $productId
                ]);


            } else {


                /*
                |--------------------------------------------------------------------------
                | INSERT INVENTORY
                |--------------------------------------------------------------------------
                */

                $inventoryStmt =
                    $db->prepare("
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

            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $db->commit();


            /*
            |--------------------------------------------------------------------------
            | DELETE OLD IMAGE
            |--------------------------------------------------------------------------
            |
            | Only delete old image AFTER database update succeeds.
            |
            |--------------------------------------------------------------------------
            */

            if (
                $newImage !== $oldImage &&
                !empty($oldImage)
            ) {

                $oldImagePath =
                    __DIR__ .
                    '/../uploads/products/' .
                    $oldImage;


                if (
                    file_exists(
                        $oldImagePath
                    ) &&
                    is_file(
                        $oldImagePath
                    )
                ) {

                    unlink(
                        $oldImagePath
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                'Location: products.php?success=product_updated'
            );

            exit;


        } catch (PDOException $e) {


            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if (
                $db->inTransaction()
            ) {

                $db->rollBack();

            }


            /*
            |--------------------------------------------------------------------------
            | DELETE NEW IMAGE
            |--------------------------------------------------------------------------
            |
            | Database update failed, therefore remove newly uploaded image.
            |
            |--------------------------------------------------------------------------
            */

            if (
                $newImagePath !== null &&
                file_exists(
                    $newImagePath
                ) &&
                is_file(
                    $newImagePath
                )
            ) {

                unlink(
                    $newImagePath
                );

            }


            /*
            |--------------------------------------------------------------------------
            | RESTORE OLD IMAGE
            |--------------------------------------------------------------------------
            */

            $newImage =
                $oldImage;


            /*
            |--------------------------------------------------------------------------
            | ERROR MESSAGE
            |--------------------------------------------------------------------------
            */

            $errors[] =
                'Unable to update product. Please try again.';


            /*
            |--------------------------------------------------------------------------
            | DEVELOPMENT DEBUG
            |--------------------------------------------------------------------------
            |
            | DO NOT show database errors to customers.
            |
            | During development you may temporarily use:
            |
            | $errors[] = $e->getMessage();
            |
            |--------------------------------------------------------------------------
            */

        }

    }

}


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Edit Product - HochipoHub';

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
        <?= htmlspecialchars($pageTitle) ?>
    </title>


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <!-- DASHBOARD CSS -->

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >


    <!-- VENDOR CSS -->

    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >


    <!-- RESPONSIVE CSS -->

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| VENDOR SIDEBAR
|--------------------------------------------------------------------------
*/

$sidebarPath =
    __DIR__ .
    '/../includes/vendor_sidebar.php';


if (
    file_exists(
        $sidebarPath
    )
) {

    include $sidebarPath;

}

?>


<main class="dashboard-main">


    <!-- =====================================================
         PAGE HEADER
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
                Edit Product
            </h1>


            <p>
                Update your product information.
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
         ERROR ALERT
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

            <strong>
                Please fix the following:
            </strong>


            <?php foreach ($errors as $error): ?>

                <div style="margin-top:6px;">

                    •
                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PRODUCT FORM
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


            <!-- =================================================
                 PRODUCT NAME
            ================================================== -->

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
                    value="<?= htmlspecialchars($currentName) ?>"
                    style="
                        width:100%;
                        padding:14px;
                        border:1px solid #cbd5e1;
                        border-radius:10px;
                        box-sizing:border-box;
                    "
                >

            </div>


            <!-- =================================================
                 CATEGORY
            ================================================== -->

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
                        box-sizing:border-box;
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
                            value="<?= (int) $category['category_id'] ?>"
                            <?= (
                                (int) $currentCategory ===
                                (int) $category['category_id']
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $category['category_name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 PRICE + STOCK
            ================================================== -->

            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:20px;
                    margin-bottom:22px;
                "
            >


                <!-- PRICE -->

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
                        value="<?= htmlspecialchars($currentPrice) ?>"
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #cbd5e1;
                            border-radius:10px;
                            box-sizing:border-box;
                        "
                    >

                </div>


                <!-- STOCK -->

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
                        value="<?= htmlspecialchars($currentStock) ?>"
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #cbd5e1;
                            border-radius:10px;
                            box-sizing:border-box;
                        "
                    >

                </div>

            </div>


            <!-- =================================================
                 DESCRIPTION
            ================================================== -->

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
                        box-sizing:border-box;
                    "
                ><?= htmlspecialchars($currentDescription) ?></textarea>

            </div>


            <!-- =================================================
                 STATUS
            ================================================== -->

            <div
                style="
                    margin-bottom:22px;
                "
            >

                <label
                    for="status"
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    Product Status
                </label>


                <select
                    id="status"
                    name="status"
                    style="
                        width:100%;
                        padding:14px;
                        border:1px solid #cbd5e1;
                        border-radius:10px;
                        box-sizing:border-box;
                    "
                >

                    <option
                        value="Available"
                        <?= $currentStatus === 'Available'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Available
                    </option>


                    <option
                        value="Out of Stock"
                        <?= $currentStatus === 'Out of Stock'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Out of Stock
                    </option>


                    <option
                        value="Hidden"
                        <?= $currentStatus === 'Hidden'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Hidden
                    </option>

                </select>


                <small
                    style="
                        display:block;
                        margin-top:8px;
                        color:#64748b;
                    "
                >
                    Available and Out of Stock are automatically
                    adjusted according to stock quantity.
                    Hidden remains hidden.
                </small>

            </div>


            <!-- =================================================
                 CURRENT IMAGE
            ================================================== -->

            <div
                style="
                    margin-bottom:22px;
                "
            >

                <label
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:10px;
                    "
                >
                    Current Product Image
                </label>


                <?php if (
                    !empty($product['image'])
                ): ?>


                    <div
                        style="
                            display:inline-flex;
                            padding:8px;
                            background:#f8fafc;
                            border:1px solid #e2e8f0;
                            border-radius:14px;
                        "
                    >

                        <img
                            src="../uploads/products/<?= htmlspecialchars($product['image']) ?>"
                            alt="<?= htmlspecialchars($product['product_name']) ?>"
                            style="
                                width:220px;
                                height:220px;
                                object-fit:cover;
                                border-radius:10px;
                                display:block;
                            "
                        >

                    </div>


                <?php else: ?>


                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            background:#f8fafc;
                            border:1px dashed #cbd5e1;
                            border-radius:12px;
                            color:#64748b;
                        "
                    >

                        No image uploaded.

                    </div>


                <?php endif; ?>

            </div>


            <!-- =================================================
                 REPLACE IMAGE
            ================================================== -->

            <div
                style="
                    margin-bottom:25px;
                "
            >

                <label
                    for="image"
                    style="
                        display:block;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    Replace Product Image
                </label>


                <input
                    type="file"
                    id="image"
                    name="image"
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


            <!-- =================================================
                 BUTTONS
            ================================================== -->

            <div
                style="
                    display:flex;
                    gap:12px;
                    justify-content:flex-end;
                    flex-wrap:wrap;
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
                    Update Product
                </button>

            </div>


        </form>

    </section>


</main>


</body>

</html>