<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER EDIT PRODUCT
|--------------------------------------------------------------------------
| File:
| seller/edit_product.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG / DATABASE / SESSION / FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
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
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ../index.php');
    exit;
}


if (
    !isset($_SESSION['role']) ||
    strtolower((string) $_SESSION['role']) !== 'vendor'
) {

    header('Location: ../dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (
    !isset($db) ||
    !($db instanceof PDO)
) {
    $db = getDB();
}


if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| BASIC VARIABLES
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];

$errors = [];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('editProductEscape')) {

    function editProductEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('editProductStatusClass')) {

    function editProductStatusClass($status): string
    {
        $status = strtolower(
            trim((string) $status)
        );


        if ($status === 'available') {
            return 'available';
        }


        if ($status === 'out of stock') {
            return 'out-of-stock';
        }


        if ($status === 'hidden') {
            return 'hidden';
        }


        return 'default';
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
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
|
| Load full profile so shared vendor sidebar looks the same everywhere.
|
|--------------------------------------------------------------------------
*/

try {

    $vendorStmt = $db->prepare("
        SELECT

            v.vendor_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.business_address,
            v.category,
            v.delivery_method,
            v.approval_status,
            v.created_at,

            u.name,
            u.email,
            u.phone

        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.user_id = ?

        LIMIT 1
    ");


    $vendorStmt->execute([
        $userId
    ]);


    $vendor = $vendorStmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

    $vendor = false;
}


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    header(
        'Location: dashboard.php?error=vendor_not_found'
    );

    exit;
}


$vendorId = (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| SIDEBAR SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| APPROVAL
|--------------------------------------------------------------------------
*/

if (
    strtolower(
        trim(
            (string) $vendor['approval_status']
        )
    ) !== 'approved'
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
            status,
            created_at,
            updated_at

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

} catch (Throwable $e) {

    $product = false;
}


/*
|--------------------------------------------------------------------------
| PRODUCT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$product) {

    header(
        'Location: products.php?error=product_not_found'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];


try {

    $categoryStmt = $db->prepare("
        SELECT
            category_id,
            category_name

        FROM categories

        ORDER BY
            category_name ASC
    ");


    $categoryStmt->execute();


    $categories = $categoryStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

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
    (string) $product['product_name'];


$currentDescription =
    (string) (
        $product['description']
        ?? ''
    );


$currentPrice =
    $product['price'];


$currentStock =
    $product['stock_quantity'];


$currentCategory =
    (int) $product['category_id'];


$currentStatus =
    (string) $product['status'];


/*
|--------------------------------------------------------------------------
| ALLOWED STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'Available',
    'Out of Stock',
    'Hidden'
];


/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | FORM DATA
    |--------------------------------------------------------------------------
    */

    $currentName =
        trim(
            $_POST['product_name']
            ?? ''
        );


    $currentDescription =
        trim(
            $_POST['description']
            ?? ''
        );


    $currentPrice =
        trim(
            $_POST['price']
            ?? ''
        );


    $currentStock =
        trim(
            $_POST['stock_quantity']
            ?? ''
        );


    $currentCategory =
        (int) (
            $_POST['category_id']
            ?? 0
        );


    $currentStatus =
        trim(
            $_POST['status']
            ?? 'Available'
        );


    /*
    |--------------------------------------------------------------------------
    | PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if ($currentName === '') {

        $errors[] =
            'Product name is required.';
    }


    if (
        mb_strlen($currentName) > 150
    ) {

        $errors[] =
            'Product name cannot exceed 150 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($currentCategory <= 0) {

        $errors[] =
            'Please select a product category.';
    }


    /*
    |--------------------------------------------------------------------------
    | PRICE
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
    | STOCK
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
    | STATUS
    |--------------------------------------------------------------------------
    */

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
    | FIXED PRODUCT STATUS LOGIC
    |--------------------------------------------------------------------------
    |
    | OLD LOGIC:
    |
    | Any status except Hidden + stock > 0
    | was automatically changed to Available.
    |
    | That meant:
    |
    | User selected Out of Stock
    | Stock = 10
    | Result = Available
    |
    | NEW LOGIC:
    |
    | Hidden
    |     -> ALWAYS Hidden
    |
    | Out of Stock
    |     -> ALWAYS Out of Stock
    |
    | Available + stock > 0
    |     -> Available
    |
    | Available + stock <= 0
    |     -> Out of Stock
    |
    |--------------------------------------------------------------------------
    */

    $stockValue =
        (int) $currentStock;


    if (
        $currentStatus === 'Available' &&
        $stockValue <= 0
    ) {

        $currentStatus =
            'Out of Stock';
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY EXISTS
    |--------------------------------------------------------------------------
    */

    if ($currentCategory > 0) {

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


            if (
                !$categoryCheck->fetch(
                    PDO::FETCH_ASSOC
                )
            ) {

                $errors[] =
                    'Selected category does not exist.';
            }

        } catch (Throwable $e) {

            $errors[] =
                'Unable to verify selected category.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
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
    | NEW IMAGE
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['image'];


        /*
        |--------------------------------------------------------------------------
        | UPLOAD ERROR
        |--------------------------------------------------------------------------
        */

        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                'There was a problem uploading the product image.';
        }


        /*
        |--------------------------------------------------------------------------
        | SIZE
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors) &&
            (
                !isset($file['size']) ||
                $file['size'] > 5 * 1024 * 1024
            )
        ) {

            $errors[] =
                'Product image must not exceed 5MB.';
        }


        /*
        |--------------------------------------------------------------------------
        | VALID UPLOAD
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors) &&
            (
                empty($file['tmp_name']) ||
                !is_uploaded_file(
                    $file['tmp_name']
                )
            )
        ) {

            $errors[] =
                'Invalid product image upload.';
        }


        /*
        |--------------------------------------------------------------------------
        | MIME
        |--------------------------------------------------------------------------
        */

        $realMime =
            null;


        if (empty($errors)) {

            if (!class_exists('finfo')) {

                $errors[] =
                    'PHP Fileinfo extension is required for image uploads.';

            } else {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );


                $realMime =
                    $finfo->file(
                        $file['tmp_name']
                    );


                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                if (
                    !$realMime ||
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
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATE FILE
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


            $newImage =
                'product_' .
                $vendorId .
                '_' .
                bin2hex(
                    random_bytes(8)
                ) .
                '.' .
                $extension;


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
                        'Unable to create product upload directory.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | MOVE IMAGE
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

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
    | DATABASE UPDATE
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $priceValue =
                (float) $currentPrice;


            $stockValue =
                (int) $currentStock;


            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | PRODUCTS
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
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP

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
            | INVENTORY RECORD
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


            /*
            |--------------------------------------------------------------------------
            | UPDATE INVENTORY
            |--------------------------------------------------------------------------
            */

            if ($inventoryExists) {

                $inventoryStmt =
                    $db->prepare("
                        UPDATE inventory

                        SET
                            quantity = ?,
                            last_updated = CURRENT_TIMESTAMP

                        WHERE product_id = ?
                    ");


                $inventoryStmt->execute([
                    $stockValue,
                    $productId
                ]);

            } else {

                /*
                |--------------------------------------------------------------------------
                | CREATE INVENTORY
                |--------------------------------------------------------------------------
                */

                $inventoryStmt =
                    $db->prepare("
                        INSERT INTO inventory
                        (
                            product_id,
                            quantity
                        )

                        VALUES
                        (
                            ?,
                            ?
                        )
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
            */

            if (
                $newImage !== $oldImage &&
                !empty($oldImage)
            ) {

                $oldImagePath =
                    __DIR__ .
                    '/../uploads/products/' .
                    basename($oldImage);


                if (
                    file_exists($oldImagePath) &&
                    is_file($oldImagePath)
                ) {

                    @unlink($oldImagePath);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                'Location: products.php?success=product_updated'
            );

            exit;

        } catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }


            /*
            |--------------------------------------------------------------------------
            | REMOVE NEW IMAGE ON ERROR
            |--------------------------------------------------------------------------
            */

            if (
                $newImagePath !== null &&
                file_exists($newImagePath) &&
                is_file($newImagePath)
            ) {

                @unlink($newImagePath);
            }


            $errors[] =
                'Unable to update product. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| IMAGE
|--------------------------------------------------------------------------
*/

$productImage =
    !empty($product['image'])
        ? '../uploads/products/' .
          rawurlencode(
              basename(
                  $product['image']
              )
          )
        : '';


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

$statusClass =
    editProductStatusClass(
        $currentStatus
    );


/*
|--------------------------------------------------------------------------
| TITLE
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
        <?= editProductEscape(
            $pageTitle
        ) ?>
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- PROJECT CSS -->

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


<style>

/* ================================================================
   EDIT PRODUCT
================================================================ */

.seller-edit-page {

    margin: 0;

    min-height: 100vh;

    overflow-x: hidden;

    background: #f6f8fc;

    color: #14213d;

    font-family:
        Inter,
        Arial,
        sans-serif;
}


/* ================================================================
   MAIN
================================================================ */

.seller-edit-main {

    width:
        calc(
            100% -
            var(--seller-sidebar)
        );

    min-height: 100vh;

    margin-left:
        var(--seller-sidebar);

    background:

        radial-gradient(
            circle at 95% 6%,
            rgba(37,99,235,.07),
            transparent 25%
        ),

        #f6f8fc;
}


/* ================================================================
   TOP BAR
================================================================ */

.seller-edit-topbar {

    height: 72px;

    padding: 0 32px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background:
        rgba(255,255,255,.96);

    border-bottom:
        1px solid #e8edf5;
}


.seller-edit-topbar-label {

    color: #94a3b8;

    font-size: 11px;

    font-weight: 700;
}


.seller-edit-user {

    display: flex;

    align-items: center;

    gap: 9px;
}


.seller-edit-avatar {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #3b82f6,
            #6366f1
        );

    border-radius: 50%;

    font-size: 12px;

    font-weight: 900;
}


.seller-edit-user strong {

    display: block;

    color: #14213d;

    font-size: 11px;
}


.seller-edit-user small {

    display: block;

    margin-top: 2px;

    color: #94a3b8;

    font-size: 8px;
}


/* ================================================================
   CONTENT
================================================================ */

.seller-edit-content {

    width: 100%;

    max-width: 1450px;

    margin: 0 auto;

    padding:
        28px
        32px
        60px;
}


/* ================================================================
   PAGE HEADING
================================================================ */

.seller-edit-heading {

    margin-bottom: 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.seller-edit-eyebrow {

    display: block;

    margin-bottom: 5px;

    color: #2563eb;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.5px;
}


.seller-edit-heading h1 {

    margin: 0;

    color: #14213d;

    font-size:
        clamp(
            25px,
            3vw,
            33px
        );

    font-weight: 900;

    letter-spacing: -.8px;
}


.seller-edit-heading p {

    margin:
        7px
        0
        0;

    color: #7b879c;

    font-size: 11px;
}


.seller-edit-back {

    min-height: 42px;

    padding: 0 15px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #475569;

    background: #ffffff;

    border:
        1px solid #dfe6ef;

    border-radius: 11px;

    box-shadow:
        0
        8px
        20px
        rgba(40,65,120,.04);

    font-size: 9px;

    font-weight: 800;

    text-decoration: none;
}


/* ================================================================
   HERO
================================================================ */

.seller-edit-hero {

    position: relative;

    overflow: hidden;

    min-height: 170px;

    margin-bottom: 22px;

    padding: 31px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

    color: #ffffff;

    background:

        linear-gradient(
            110deg,
            #08265a 0%,
            #123d8c 48%,
            #2783ef 100%
        );

    border-radius: 23px;

    box-shadow:
        0
        17px
        38px
        rgba(18,70,150,.13);
}


.seller-edit-hero::before {

    content: "";

    position: absolute;

    width: 220px;

    height: 220px;

    top: -130px;

    right: -45px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}


.seller-edit-hero::after {

    content: "";

    position: absolute;

    width: 145px;

    height: 145px;

    right: 150px;

    bottom: -100px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.05);
}


.seller-edit-hero-copy {

    position: relative;

    z-index: 2;
}


.seller-edit-hero-label {

    display: block;

    margin-bottom: 8px;

    color: #a8d4ff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.3px;
}


.seller-edit-hero h2 {

    margin:
        0
        0
        8px;

    color: #ffffff;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 24px;

    font-weight: 800;
}


.seller-edit-hero p {

    max-width: 600px;

    margin: 0;

    color:
        rgba(255,255,255,.76);

    font-size: 10px;

    line-height: 1.7;
}


.seller-edit-hero-icon {

    position: relative;

    z-index: 2;

    width: 70px;

    height: 70px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.13);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius: 19px;

    font-size: 24px;
}


/* ================================================================
   ERROR
================================================================ */

.seller-edit-alert {

    margin-bottom: 20px;

    padding: 15px 17px;

    display: flex;

    align-items: flex-start;

    gap: 10px;

    color: #991b1b;

    background: #fef2f2;

    border: 1px solid #fecaca;

    border-radius: 13px;

    font-size: 9px;

    line-height: 1.6;
}


.seller-edit-alert ul {

    margin:
        5px
        0
        0;

    padding-left: 17px;
}


/* ================================================================
   GRID
================================================================ */

.seller-edit-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        300px;

    align-items: start;

    gap: 22px;
}


/* ================================================================
   CARD
================================================================ */

.seller-edit-card {

    overflow: hidden;

    background: #ffffff;

    border:
        1px solid #e5eaf2;

    border-radius: 21px;

    box-shadow:
        0
        12px
        32px
        rgba(40,65,120,.055);
}


.seller-edit-card-header {

    min-height: 88px;

    padding:
        20px
        24px;

    display: flex;

    align-items: center;

    gap: 13px;

    border-bottom:
        1px solid #edf1f7;
}


.seller-edit-card-icon {

    width: 46px;

    height: 46px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #3b82f6
        );

    border-radius: 13px;

    box-shadow:
        0
        8px
        18px
        rgba(37,99,235,.22);

    font-size: 16px;
}


.seller-edit-card-header h3 {

    margin:
        0
        0
        4px;

    color: #14213d;

    font-size: 16px;

    font-weight: 900;
}


.seller-edit-card-header p {

    margin: 0;

    color: #8a97aa;

    font-size: 9px;
}


.seller-edit-card-body {

    padding: 25px;
}


/* ================================================================
   FIELDS
================================================================ */

.seller-edit-field-grid {

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 17px;
}


.seller-edit-field {

    margin-bottom: 18px;
}


.seller-edit-field.full {

    grid-column:
        1 / -1;
}


.seller-edit-field label {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-bottom: 7px;

    color: #334155;

    font-size: 9px;

    font-weight: 800;
}


.seller-edit-field label i {

    color: #2563eb;

    font-size: 9px;
}


.seller-edit-field input,
.seller-edit-field select,
.seller-edit-field textarea {

    width: 100%;

    outline: none;

    color: #253750;

    background: #fbfdff;

    border:
        1px solid #dbe5f0;

    border-radius: 11px;

    font-family: inherit;

    font-size: 10px;

    transition: .18s ease;
}


.seller-edit-field input,
.seller-edit-field select {

    height: 45px;

    padding:
        0
        13px;
}


.seller-edit-field textarea {

    min-height: 145px;

    padding: 13px;

    resize: vertical;

    line-height: 1.7;
}


.seller-edit-field input:focus,
.seller-edit-field select:focus,
.seller-edit-field textarea:focus {

    background: #ffffff;

    border-color: #3b82f6;

    box-shadow:
        0
        0
        0
        3px
        rgba(59,130,246,.08);
}


.seller-edit-field small {

    display: block;

    margin-top: 6px;

    color: #94a3b8;

    font-size: 8px;

    line-height: 1.55;
}


/* ================================================================
   PRICE PREFIX
================================================================ */

.seller-edit-price {

    position: relative;
}


.seller-edit-price span {

    position: absolute;

    top: 50%;

    left: 13px;

    z-index: 2;

    transform:
        translateY(-50%);

    color: #64748b;

    font-size: 9px;

    font-weight: 800;
}


.seller-edit-price input {

    padding-left: 39px;
}


/* ================================================================
   STATUS VISUAL
================================================================ */

.seller-edit-status-options {

    margin-top: 12px;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 8px;
}


.seller-status-note {

    padding: 11px;

    background: #f8fafc;

    border:
        1px solid #edf1f5;

    border-radius: 10px;
}


.seller-status-note strong {

    display: block;

    margin-bottom: 3px;

    color: #34445d;

    font-size: 8px;
}


.seller-status-note span {

    color: #8996a8;

    font-size: 7px;

    line-height: 1.45;
}


.seller-status-dot {

    width: 6px;

    height: 6px;

    margin-right: 4px;

    display: inline-block;

    border-radius: 50%;
}


.seller-status-dot.green {

    background: #22c55e;
}


.seller-status-dot.orange {

    background: #f59e0b;
}


.seller-status-dot.purple {

    background: #8b5cf6;
}


/* ================================================================
   IMAGE
================================================================ */

.seller-edit-image-box {

    min-height: 205px;

    padding: 18px;

    display: grid;

    grid-template-columns:
        155px
        minmax(0, 1fr);

    align-items: center;

    gap: 18px;

    background:

        linear-gradient(
            135deg,
            #f8fbff,
            #eef6ff
        );

    border:
        1px dashed #b9d4f5;

    border-radius: 16px;
}


.seller-edit-preview {

    width: 155px;

    height: 155px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #ffffff;

    border:
        1px solid #dce8f5;

    border-radius: 16px;

    font-size: 32px;
}


.seller-edit-preview img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    object-position: center;
}


.seller-edit-upload strong {

    display: block;

    margin-bottom: 6px;

    color: #17345f;

    font-size: 11px;

    font-weight: 900;
}


.seller-edit-upload p {

    margin:
        0
        0
        12px;

    color: #8090a8;

    font-size: 9px;

    line-height: 1.6;
}


.seller-edit-upload input {

    height: auto;

    padding: 9px;

    background: #ffffff;
}


.seller-edit-upload
input[type="file"]::file-selector-button {

    margin-right: 8px;

    padding:
        8px
        10px;

    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius: 8px;

    font-family: inherit;

    font-size: 8px;

    font-weight: 800;

    cursor: pointer;
}


/* ================================================================
   ACTION
================================================================ */

.seller-edit-actions {

    margin-top: 4px;

    padding-top: 20px;

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 9px;

    border-top:
        1px solid #edf1f5;
}


.seller-edit-action {

    min-height: 42px;

    padding:
        0
        16px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    border-radius: 10px;

    font-family: inherit;

    font-size: 9px;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;
}


.seller-edit-action.cancel {

    color: #64748b;

    background: #ffffff;

    border:
        1px solid #dce5ef;
}


.seller-edit-action.save {

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #1d67df
        );

    border: 0;

    box-shadow:
        0
        9px
        20px
        rgba(37,99,235,.22);
}


/* ================================================================
   SIDE
================================================================ */

.seller-edit-side {

    display: flex;

    flex-direction: column;

    gap: 16px;
}


.seller-edit-side-card {

    padding: 20px;

    background: #ffffff;

    border:
        1px solid #e5eaf2;

    border-radius: 18px;

    box-shadow:
        0
        9px
        24px
        rgba(40,65,120,.045);
}


.seller-edit-side-icon {

    width: 42px;

    height: 42px;

    margin-bottom: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 12px;

    font-size: 15px;
}


.seller-edit-side-card h4 {

    margin:
        0
        0
        7px;

    color: #14213d;

    font-size: 12px;

    font-weight: 900;
}


.seller-edit-side-card p {

    margin: 0;

    color: #8593a8;

    font-size: 9px;

    line-height: 1.7;
}


/* ================================================================
   CURRENT PRODUCT
================================================================ */

.seller-current-product {

    position: relative;

    overflow: hidden;

    padding: 20px;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #0b2d69,
            #276fda
        );

    border-radius: 18px;

    box-shadow:
        0
        11px
        27px
        rgba(24,70,145,.14);
}


.seller-current-product::after {

    content: "";

    position: absolute;

    width: 100px;

    height: 100px;

    right: -35px;

    bottom: -40px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.07);
}


.seller-current-product > * {

    position: relative;

    z-index: 2;
}


.seller-current-product small {

    display: block;

    margin-bottom: 6px;

    color: #a9d2ff;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .8px;
}


.seller-current-product strong {

    display: block;

    margin-bottom: 8px;

    color: #ffffff;

    font-size: 13px;

    font-weight: 900;
}


.seller-current-status {

    min-height: 27px;

    padding:
        0
        9px;

    display: inline-flex;

    align-items: center;

    gap: 6px;

    color: #ffffff;

    background:
        rgba(255,255,255,.14);

    border:
        1px solid rgba(255,255,255,.17);

    border-radius: 999px;

    font-size: 7px;

    font-weight: 800;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1100px) {

    .seller-edit-layout {

        grid-template-columns:
            1fr;
    }


    .seller-edit-side {

        display: grid;

        grid-template-columns:
            1fr
            1fr;
    }


    .seller-current-product {

        grid-column:
            1 / -1;
    }
}


@media (max-width: 768px) {

    .seller-edit-main {

        width: 100%;

        margin-left: 0;
    }


    .seller-edit-topbar {

        padding:
            0
            20px;
    }


    .seller-edit-content {

        padding:
            24px
            20px
            50px;
    }


    .seller-edit-field-grid {

        grid-template-columns:
            1fr;
    }


    .seller-edit-field.full {

        grid-column: auto;
    }
}


@media (max-width: 600px) {

    .seller-edit-user
    > div:last-child {

        display: none;
    }


    .seller-edit-content {

        padding:
            20px
            14px
            45px;
    }


    .seller-edit-heading {

        align-items: flex-start;

        flex-direction: column;
    }


    .seller-edit-back {

        width: 100%;
    }


    .seller-edit-hero {

        min-height: auto;

        padding: 23px;

        align-items: flex-start;
    }


    .seller-edit-hero h2 {

        font-size: 20px;
    }


    .seller-edit-hero-icon {

        width: 53px;

        height: 53px;

        font-size: 19px;
    }


    .seller-edit-card-body {

        padding: 18px;
    }


    .seller-edit-status-options {

        grid-template-columns:
            1fr;
    }


    .seller-edit-image-box {

        grid-template-columns:
            1fr;
    }


    .seller-edit-preview {

        width: 100%;

        height: 220px;
    }


    .seller-edit-actions {

        flex-direction:
            column-reverse;
    }


    .seller-edit-action {

        width: 100%;
    }


    .seller-edit-side {

        display: flex;
    }


    .seller-current-product {

        grid-column: auto;
    }
}

</style>

</head>


<body class="seller-dashboard-page seller-edit-page">


<?php

/*
|--------------------------------------------------------------------------
| SHARED VENDOR SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<main class="seller-edit-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-edit-topbar">


        <span class="seller-edit-topbar-label">

            Seller Center

        </span>


        <div class="seller-edit-user">


            <div class="seller-edit-avatar">

                <?= editProductEscape(
                    strtoupper(
                        substr(
                            $vendor['name']
                            ?? 'V',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div>

                <strong>

                    <?= editProductEscape(
                        $vendor['name']
                        ?? 'Vendor'
                    ) ?>

                </strong>

                <small>
                    Vendor
                </small>

            </div>


        </div>


    </header>



    <div class="seller-edit-content">


        <!-- =======================================================
             HEADER
        ======================================================== -->

        <section class="seller-edit-heading">


            <div>

                <span class="seller-edit-eyebrow">

                    PRODUCT MANAGEMENT

                </span>


                <h1>

                    Edit Product

                </h1>


                <p>

                    Update product details, stock,
                    visibility and product image.

                </p>

            </div>


            <a
                href="products.php"
                class="seller-edit-back"
            >

                <i class="fa-solid fa-arrow-left"></i>

                My Products

            </a>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-edit-hero">


            <div class="seller-edit-hero-copy">

                <span class="seller-edit-hero-label">

                    SELLER WORKSPACE

                </span>


                <h2>

                    Keep your product information accurate.

                </h2>


                <p>

                    Update pricing, stock, category, visibility
                    and product images so customers always see
                    the correct information.

                </p>

            </div>


            <div class="seller-edit-hero-icon">

                <i class="fa-solid fa-pen-to-square"></i>

            </div>


        </section>



        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (!empty($errors)): ?>


            <div class="seller-edit-alert">


                <i class="fa-solid fa-triangle-exclamation"></i>


                <div>

                    <strong>
                        Unable to update product
                    </strong>


                    <ul>

                        <?php foreach (
                            $errors
                            as $error
                        ): ?>

                            <li>

                                <?= editProductEscape(
                                    $error
                                ) ?>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             LAYOUT
        ======================================================== -->

        <div class="seller-edit-layout">


            <!-- ===================================================
                 FORM
            ==================================================== -->

            <section class="seller-edit-card">


                <div class="seller-edit-card-header">


                    <div class="seller-edit-card-icon">

                        <i class="fa-solid fa-cube"></i>

                    </div>


                    <div>

                        <h3>
                            Product Information
                        </h3>

                        <p>
                            Make changes to the selected product.
                        </p>

                    </div>


                </div>



                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="seller-edit-card-body"
                >


                    <div class="seller-edit-field-grid">


                        <!-- PRODUCT NAME -->

                        <div class="seller-edit-field full">


                            <label for="product_name">

                                <i class="fa-solid fa-tag"></i>

                                Product Name

                            </label>


                            <input
                                type="text"
                                id="product_name"
                                name="product_name"
                                maxlength="150"
                                value="<?= editProductEscape(
                                    $currentName
                                ) ?>"
                                required
                            >


                        </div>



                        <!-- CATEGORY -->

                        <div class="seller-edit-field">


                            <label for="category_id">

                                <i class="fa-solid fa-layer-group"></i>

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


                                <?php foreach (
                                    $categories
                                    as $category
                                ): ?>


                                    <option
                                        value="<?= (int)
                                            $category[
                                                'category_id'
                                            ] ?>"
                                        <?= (
                                            (int) $currentCategory ===
                                            (int) $category[
                                                'category_id'
                                            ]
                                        )
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= editProductEscape(
                                            $category[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>



                        <!-- STATUS -->

                        <div class="seller-edit-field">


                            <label for="status">

                                <i class="fa-solid fa-eye"></i>

                                Product Status

                            </label>


                            <select
                                id="status"
                                name="status"
                                required
                            >


                                <option
                                    value="Available"
                                    <?= $currentStatus === 'Available'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Available

                                </option>


                                <option
                                    value="Out of Stock"
                                    <?= $currentStatus === 'Out of Stock'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Out of Stock

                                </option>


                                <option
                                    value="Hidden"
                                    <?= $currentStatus === 'Hidden'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Hidden

                                </option>


                            </select>


                        </div>



                        <!-- PRICE -->

                        <div class="seller-edit-field">


                            <label for="price">

                                <i class="fa-solid fa-money-bill-wave"></i>

                                Selling Price

                            </label>


                            <div class="seller-edit-price">

                                <span>
                                    RM
                                </span>


                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    min="0"
                                    step="0.01"
                                    value="<?= editProductEscape(
                                        $currentPrice
                                    ) ?>"
                                    required
                                >

                            </div>


                        </div>



                        <!-- STOCK -->

                        <div class="seller-edit-field">


                            <label for="stock_quantity">

                                <i class="fa-solid fa-boxes-stacked"></i>

                                Stock Quantity

                            </label>


                            <input
                                type="number"
                                id="stock_quantity"
                                name="stock_quantity"
                                min="0"
                                step="1"
                                value="<?= editProductEscape(
                                    $currentStock
                                ) ?>"
                                required
                            >


                        </div>



                        <!-- STATUS EXPLANATION -->

                        <div class="seller-edit-field full">


                            <div class="seller-edit-status-options">


                                <div class="seller-status-note">

                                    <strong>

                                        <span class="seller-status-dot green"></span>

                                        Available

                                    </strong>

                                    <span>
                                        Product can be shown as available.
                                    </span>

                                </div>


                                <div class="seller-status-note">

                                    <strong>

                                        <span class="seller-status-dot orange"></span>

                                        Out of Stock

                                    </strong>

                                    <span>
                                        Manually mark this product unavailable.
                                    </span>

                                </div>


                                <div class="seller-status-note">

                                    <strong>

                                        <span class="seller-status-dot purple"></span>

                                        Hidden

                                    </strong>

                                    <span>
                                        Hide this listing from marketplace customers.
                                    </span>

                                </div>


                            </div>


                            <small>

                                Important: only Available with
                                stock 0 is automatically changed
                                to Out of Stock. Manual Out of Stock
                                and Hidden selections are preserved.

                            </small>


                        </div>



                        <!-- DESCRIPTION -->

                        <div class="seller-edit-field full">


                            <label for="description">

                                <i class="fa-solid fa-align-left"></i>

                                Description

                            </label>


                            <textarea
                                id="description"
                                name="description"
                                placeholder="Describe your product..."
                            ><?= editProductEscape(
                                $currentDescription
                            ) ?></textarea>


                        </div>



                        <!-- IMAGE -->

                        <div class="seller-edit-field full">


                            <label for="image">

                                <i class="fa-solid fa-image"></i>

                                Product Image

                            </label>


                            <div class="seller-edit-image-box">


                                <div
                                    class="seller-edit-preview"
                                    id="editImagePreview"
                                >


                                    <?php if (
                                        $productImage !== ''
                                    ): ?>


                                        <img
                                            id="editPreviewImage"
                                            src="<?= editProductEscape(
                                                $productImage
                                            ) ?>"
                                            alt="<?= editProductEscape(
                                                $currentName
                                            ) ?>"
                                        >


                                        <i
                                            id="editPreviewIcon"
                                            class="fa-solid fa-image"
                                            style="display:none;"
                                        ></i>


                                    <?php else: ?>


                                        <img
                                            id="editPreviewImage"
                                            src=""
                                            alt="Product preview"
                                            style="display:none;"
                                        >


                                        <i
                                            id="editPreviewIcon"
                                            class="fa-solid fa-image"
                                        ></i>


                                    <?php endif; ?>


                                </div>


                                <div class="seller-edit-upload">


                                    <strong>

                                        Replace product image

                                    </strong>


                                    <p>

                                        Choose another image only if
                                        you want to replace the current
                                        product photo. Leaving this empty
                                        keeps the existing image.

                                    </p>


                                    <input
                                        type="file"
                                        id="image"
                                        name="image"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    >


                                    <small>

                                        JPG, PNG or WEBP · Maximum 5MB.

                                    </small>


                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- ACTIONS -->

                    <div class="seller-edit-actions">


                        <a
                            href="products.php"
                            class="
                                seller-edit-action
                                cancel
                            "
                        >

                            Cancel

                        </a>


                        <button
                            type="submit"
                            class="
                                seller-edit-action
                                save
                            "
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Update Product

                        </button>


                    </div>


                </form>


            </section>



            <!-- ===================================================
                 SIDE
            ==================================================== -->

            <aside class="seller-edit-side">


                <!-- CURRENT -->

                <section class="seller-current-product">


                    <small>

                        CURRENT PRODUCT

                    </small>


                    <strong>

                        <?= editProductEscape(
                            $currentName
                        ) ?>

                    </strong>


                    <span class="seller-current-status">

                        <i class="fa-solid fa-circle"></i>

                        <?= editProductEscape(
                            $currentStatus
                        ) ?>

                    </span>


                </section>



                <!-- STATUS HELP -->

                <section class="seller-edit-side-card">


                    <div class="seller-edit-side-icon">

                        <i class="fa-solid fa-eye"></i>

                    </div>


                    <h4>

                        Product Visibility

                    </h4>


                    <p>

                        Choose Available for a normal listing.
                        Choose Out of Stock when you temporarily
                        don't want customers purchasing the item.
                        Choose Hidden when the product should no
                        longer be visible to marketplace customers.

                    </p>


                </section>



                <!-- STOCK HELP -->

                <section class="seller-edit-side-card">


                    <div class="seller-edit-side-icon">

                        <i class="fa-solid fa-boxes-stacked"></i>

                    </div>


                    <h4>

                        Stock Sync

                    </h4>


                    <p>

                        Updating stock here also updates the
                        inventory table. If Available is selected
                        with zero stock, HochipoHub automatically
                        changes the status to Out of Stock.

                    </p>


                </section>



                <!-- IMAGE HELP -->

                <section class="seller-edit-side-card">


                    <div class="seller-edit-side-icon">

                        <i class="fa-solid fa-camera"></i>

                    </div>


                    <h4>

                        Product Image

                    </h4>


                    <p>

                        Your existing image is preserved unless
                        another image is selected. Product pages
                        will display the image inside a controlled
                        image container.

                    </p>


                </section>


            </aside>


        </div>


    </div>


</main>



<script>

/*
|--------------------------------------------------------------------------
| IMAGE PREVIEW
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const input =
            document.getElementById(
                'image'
            );


        const image =
            document.getElementById(
                'editPreviewImage'
            );


        const icon =
            document.getElementById(
                'editPreviewIcon'
            );


        if (
            !input ||
            !image ||
            !icon
        ) {
            return;
        }


        input.addEventListener(
            'change',
            function () {

                const file =
                    this.files &&
                    this.files.length
                        ? this.files[0]
                        : null;


                if (!file) {
                    return;
                }


                if (
                    !file.type.startsWith(
                        'image/'
                    )
                ) {
                    return;
                }


                const reader =
                    new FileReader();


                reader.addEventListener(
                    'load',
                    function (event) {

                        image.src =
                            event.target.result;


                        image.style.display =
                            'block';


                        icon.style.display =
                            'none';
                    }
                );


                reader.readAsDataURL(
                    file
                );
            }
        );
    }
);

</script>


</body>

</html>