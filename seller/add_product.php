<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER ADD PRODUCT
|--------------------------------------------------------------------------
| File:
| seller/add_product.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/session.php';


/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
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
| VENDOR ROLE CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    strtolower(
        (string) $_SESSION['role']
    ) !== 'vendor'
) {

    header(
        'Location: ../dashboard.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($db) ||
    !($db instanceof PDO)
) {

    $db =
        getDB();

}


if (!($db instanceof PDO)) {

    die(
        'Database connection is not available.'
    );

}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('addProductEscape')) {

    function addProductEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$errors = [];

$success = '';

$productName = '';

$description = '';

$price = '';

$stockQuantity = '';

$categoryId = 0;


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
|--------------------------------------------------------------------------
|
| Same vendor data shape as seller/dashboard.php so vendor_sidebar.php
| displays exactly the same store name, owner, logo and status.
|
|--------------------------------------------------------------------------
*/

try {

    $vendorStmt =
        $db->prepare("
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


    $vendor =
        $vendorStmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $vendor = false;

}


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    header(
        'Location: setup_profile.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| SYNC SIDEBAR SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| VENDOR APPROVAL
|--------------------------------------------------------------------------
*/

$vendorApproved =
    strtolower(
        trim(
            (string)
            $vendor['approval_status']
        )
    ) === 'approved';


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];


try {

    $categoryStmt =
        $db->prepare("
            SELECT

                category_id,
                category_name

            FROM categories

            ORDER BY
                category_name ASC
        ");


    $categoryStmt->execute();


    $categories =
        $categoryStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $categories = [];

}


/*
|--------------------------------------------------------------------------
| POST - ADD PRODUCT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | VENDOR APPROVAL CHECK
    |--------------------------------------------------------------------------
    */

    if (!$vendorApproved) {

        $errors[] =
            'Your vendor account must be approved before you can add products.';

    }


    /*
    |--------------------------------------------------------------------------
    | FORM DATA
    |--------------------------------------------------------------------------
    */

    $productName =
        trim(
            $_POST['product_name']
            ?? ''
        );


    $description =
        trim(
            $_POST['description']
            ?? ''
        );


    $price =
        trim(
            $_POST['price']
            ?? ''
        );


    $stockQuantity =
        trim(
            $_POST['stock_quantity']
            ?? ''
        );


    $categoryId =
        (int) (
            $_POST['category_id']
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if ($productName === '') {

        $errors[] =
            'Product name is required.';

    }


    if (
        mb_strlen(
            $productName
        ) > 150
    ) {

        $errors[] =
            'Product name cannot exceed 150 characters.';

    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($categoryId <= 0) {

        $errors[] =
            'Please select a product category.';

    }


    /*
    |--------------------------------------------------------------------------
    | PRICE
    |--------------------------------------------------------------------------
    */

    if (
        $price === '' ||
        !is_numeric(
            $price
        )
    ) {

        $errors[] =
            'Please enter a valid product price.';

    }

    elseif (
        (float) $price < 0
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
        $stockQuantity === '' ||
        !is_numeric(
            $stockQuantity
        ) ||
        (int) $stockQuantity < 0
    ) {

        $errors[] =
            'Please enter a valid stock quantity.';

    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY EXISTS
    |--------------------------------------------------------------------------
    */

    if ($categoryId > 0) {

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
                $categoryId
            ]);


            $categoryExists =
                $categoryCheck->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$categoryExists) {

                $errors[] =
                    'Selected category does not exist.';

            }

        }

        catch (Throwable $e) {

            $errors[] =
                'Unable to validate the selected category.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    $imageName = null;

    $targetPath = null;


    if (
        isset(
            $_FILES['product_image']
        ) &&
        $_FILES['product_image']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['product_image'];


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
        | FILE SIZE
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors) &&
            (
                !isset(
                    $file['size']
                ) ||
                $file['size']
                    > 5 * 1024 * 1024
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
                empty(
                    $file['tmp_name']
                ) ||
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
        | MIME TYPE
        |--------------------------------------------------------------------------
        */

        $realMime = null;


        if (empty($errors)) {

            if (!class_exists('finfo')) {

                $errors[] =
                    'PHP Fileinfo extension is required for image uploads.';

            }

            else {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );


                $realMime =
                    $finfo->file(
                        $file['tmp_name']
                    );


                $allowedTypes = [

                    'image/jpeg',
                    'image/png',
                    'image/webp'

                ];


                if (
                    !$realMime ||
                    !in_array(
                        $realMime,
                        $allowedTypes,
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
        | SAFE FILE NAME
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            $extensionMap = [

                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'

            ];


            $extension =
                $extensionMap[
                    $realMime
                ];


            $imageName =
                'product_' .
                (int) $vendor['vendor_id'] .
                '_' .
                bin2hex(
                    random_bytes(8)
                ) .
                '.' .
                $extension;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SAVE PRODUCT
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $priceValue =
            (float) $price;


        $stockValue =
            (int) $stockQuantity;


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
                    'Unable to create product upload directory.';

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
                    'Failed to save product image.';


                $imageName =
                    null;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | DATABASE
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $db->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | PRODUCT
                |--------------------------------------------------------------------------
                */

                $productStmt =
                    $db->prepare("
                        INSERT INTO products
                        (
                            vendor_id,
                            category_id,
                            product_name,
                            description,
                            price,
                            stock_quantity,
                            image,
                            status
                        )

                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");


                $productStmt->execute([

                    (int)
                    $vendor['vendor_id'],

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
                | PRODUCT ID
                |--------------------------------------------------------------------------
                */

                $productId =
                    (int)
                    $db->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | INVENTORY
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
                    'Product added successfully.';


                /*
                |--------------------------------------------------------------------------
                | RESET
                |--------------------------------------------------------------------------
                */

                $productName = '';

                $description = '';

                $price = '';

                $stockQuantity = '';

                $categoryId = 0;

            }

            catch (Throwable $e) {

                if (
                    $db->inTransaction()
                ) {

                    $db->rollBack();

                }


                if (
                    $imageName !== null &&
                    $targetPath !== null &&
                    file_exists(
                        $targetPath
                    )
                ) {

                    @unlink(
                        $targetPath
                    );

                }


                $errors[] =
                    'Unable to add product. Please try again.';

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Add Product - HochipoHub';

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
        <?= addProductEscape(
            $pageTitle
        ) ?>
    </title>


    <!-- ============================================================
         GOOGLE FONTS
    ============================================================= -->

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


    <!-- ============================================================
         FONT AWESOME
    ============================================================= -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

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


        /* ==========================================================
           PAGE
        ========================================================== */

        .seller-add-page {

            margin:
                0;

            min-height:
                100vh;

            overflow-x:
                hidden;

            color:
                #14213d;

            background:
                #f6f8fc;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        /* ==========================================================
           MAIN
        ========================================================== */

        .seller-add-main {

            width:
                calc(
                    100% -
                    var(
                        --seller-sidebar
                    )
                );

            min-height:
                100vh;

            margin-left:
                var(
                    --seller-sidebar
                );

            background:

                radial-gradient(
                    circle at 95% 8%,
                    rgba(
                        37,
                        99,
                        235,
                        .07
                    ),
                    transparent 22%
                ),

                #f6f8fc;

        }


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .seller-add-topbar {

            height:
                72px;

            padding:
                0 32px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .95
                );

            border-bottom:
                1px solid
                #e8edf5;

        }


        .seller-add-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-add-topbar-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-add-topbar-avatar {

            width:
                38px;

            height:
                38px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #3b82f6,
                    #6366f1
                );

            border-radius:
                50%;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-add-topbar-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-add-topbar-user small {

            display:
                block;

            margin-top:
                2px;

            color:
                #94a3b8;

            font-size:
                8px;

        }


        /* ==========================================================
           CONTENT
        ========================================================== */

        .seller-add-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                28px 32px 60px;

        }


        /* ==========================================================
           PAGE HEADING
        ========================================================== */

        .seller-add-heading {

            margin-bottom:
                22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

        }


        .seller-add-eyebrow {

            display:
                block;

            margin-bottom:
                5px;

            color:
                #2563eb;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.5px;

        }


        .seller-add-heading h1 {

            margin:
                0;

            color:
                #14213d;

            font-size:

                clamp(
                    25px,
                    3vw,
                    33px
                );

            font-weight:
                900;

            letter-spacing:
                -.8px;

        }


        .seller-add-heading p {

            margin:
                7px 0 0;

            color:
                #7b879c;

            font-size:
                11px;

        }


        .seller-back-products {

            min-height:
                42px;

            padding:
                0 15px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                7px;

            color:
                #475569;

            background:
                #ffffff;

            border:
                1px solid
                #dfe6ef;

            border-radius:
                11px;

            box-shadow:

                0
                8px
                20px
                rgba(
                    40,
                    65,
                    120,
                    .04
                );

            font-size:
                9px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        .seller-back-products:hover {

            color:
                #2563eb;

            border-color:
                #bfdbfe;

        }


        /* ==========================================================
           HERO
        ========================================================== */

        .seller-add-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                170px;

            margin-bottom:
                22px;

            padding:
                31px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                30px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123d8c 48%,
                    #2783ef 100%
                );

            border-radius:
                23px;

            box-shadow:

                0
                17px
                38px
                rgba(
                    18,
                    70,
                    150,
                    .13
                );

        }


        .seller-add-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                220px;

            height:
                220px;

            top:
                -130px;

            right:
                -40px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .08
                );

        }


        .seller-add-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                145px;

            height:
                145px;

            right:
                155px;

            bottom:
                -100px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

        }


        .seller-add-hero-copy {

            position:
                relative;

            z-index:
                2;

            max-width:
                650px;

        }


        .seller-add-hero-label {

            display:
                block;

            margin-bottom:
                8px;

            color:
                #a8d4ff;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.4px;

        }


        .seller-add-hero h2 {

            margin:
                0 0 8px;

            color:
                #ffffff;

            font-family:
                Poppins,
                Inter,
                sans-serif;

            font-size:
                25px;

            font-weight:
                800;

            letter-spacing:
                -.6px;

        }


        .seller-add-hero p {

            max-width:
                600px;

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .76
                );

            font-size:
                10px;

            line-height:
                1.7;

        }


        .seller-add-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                72px;

            height:
                72px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .13
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .22
                );

            border-radius:
                20px;

            backdrop-filter:
                blur(10px);

            font-size:
                25px;

        }


        /* ==========================================================
           ALERTS
        ========================================================== */

        .seller-add-alert {

            margin-bottom:
                20px;

            padding:
                15px 17px;

            display:
                flex;

            align-items:
                flex-start;

            gap:
                11px;

            border-radius:
                13px;

            font-size:
                10px;

            line-height:
                1.6;

        }


        .seller-add-alert-icon {

            width:
                31px;

            height:
                31px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                9px;

        }


        .seller-add-alert.error {

            color:
                #991b1b;

            background:
                #fef2f2;

            border:
                1px solid
                #fecaca;

        }


        .seller-add-alert.error
        .seller-add-alert-icon {

            background:
                #fee2e2;

        }


        .seller-add-alert.success {

            color:
                #166534;

            background:
                #f0fdf4;

            border:
                1px solid
                #bbf7d0;

        }


        .seller-add-alert.success
        .seller-add-alert-icon {

            background:
                #dcfce7;

        }


        .seller-add-alert ul {

            margin:
                5px 0 0;

            padding-left:
                17px;

        }


        /* ==========================================================
           FORM LAYOUT
        ========================================================== */

        .seller-add-layout {

            display:
                grid;

            grid-template-columns:

                minmax(
                    0,
                    1fr
                )

                285px;

            align-items:
                start;

            gap:
                22px;

        }


        /* ==========================================================
           FORM PANEL
        ========================================================== */

        .seller-add-form-card {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                21px;

            box-shadow:

                0
                12px
                32px
                rgba(
                    40,
                    65,
                    120,
                    .055
                );

        }


        .seller-add-form-header {

            min-height:
                88px;

            padding:
                20px 24px;

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

            border-bottom:
                1px solid
                #edf1f7;

        }


        .seller-add-form-icon {

            width:
                46px;

            height:
                46px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #3b82f6
                );

            border-radius:
                13px;

            box-shadow:

                0
                8px
                18px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

            font-size:
                16px;

        }


        .seller-add-form-header h3 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                16px;

            font-weight:
                900;

        }


        .seller-add-form-header p {

            margin:
                0;

            color:
                #8a97aa;

            font-size:
                9px;

        }


        .seller-add-form-body {

            padding:
                25px;

        }


        /* ==========================================================
           FORM GRID
        ========================================================== */

        .seller-field-grid {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                17px;

        }


        .seller-field {

            margin-bottom:
                18px;

        }


        .seller-field.full {

            grid-column:
                1 / -1;

        }


        .seller-field label {

            display:
                flex;

            align-items:
                center;

            gap:
                6px;

            margin-bottom:
                7px;

            color:
                #334155;

            font-size:
                9px;

            font-weight:
                800;

        }


        .seller-field label i {

            color:
                #2563eb;

            font-size:
                9px;

        }


        .seller-required {

            color:
                #ef4444;

        }


        .seller-field input,
        .seller-field select,
        .seller-field textarea {

            width:
                100%;

            outline:
                none;

            color:
                #253750;

            background:
                #fbfdff;

            border:
                1px solid
                #dbe5f0;

            border-radius:
                11px;

            font-family:
                inherit;

            font-size:
                10px;

            transition:
                .18s ease;

        }


        .seller-field input,
        .seller-field select {

            height:
                45px;

            padding:
                0 13px;

        }


        .seller-field textarea {

            min-height:
                145px;

            padding:
                13px;

            line-height:
                1.7;

            resize:
                vertical;

        }


        .seller-field input:focus,
        .seller-field select:focus,
        .seller-field textarea:focus {

            background:
                #ffffff;

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        .seller-field small {

            display:
                block;

            margin-top:
                6px;

            color:
                #94a3b8;

            font-size:
                8px;

            line-height:
                1.5;

        }


        /* ==========================================================
           PRICE WRAPPER
        ========================================================== */

        .seller-input-prefix {

            position:
                relative;

        }


        .seller-input-prefix span {

            position:
                absolute;

            top:
                50%;

            left:
                13px;

            z-index:
                2;

            transform:
                translateY(-50%);

            color:
                #64748b;

            font-size:
                9px;

            font-weight:
                800;

        }


        .seller-input-prefix input {

            padding-left:
                39px;

        }


        /* ==========================================================
           IMAGE UPLOAD
        ========================================================== */

        .seller-upload-box {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                175px;

            padding:
                20px;

            display:
                grid;

            grid-template-columns:
                115px
                1fr;

            align-items:
                center;

            gap:
                18px;

            background:

                linear-gradient(
                    135deg,
                    #f7faff,
                    #eef6ff
                );

            border:
                1px dashed
                #b9d4f5;

            border-radius:
                16px;

        }


        .seller-upload-preview {

            width:
                115px;

            height:
                115px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #ffffff;

            border:
                1px solid
                #dce8f5;

            border-radius:
                15px;

            box-shadow:

                0
                7px
                18px
                rgba(
                    40,
                    65,
                    120,
                    .06
                );

            font-size:
                31px;

        }


        .seller-upload-preview img {

            display:
                none;

            width:
                100%;

            height:
                100%;

            object-fit:
                contain;

            object-position:
                center;

        }


        .seller-upload-copy strong {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #17345f;

            font-size:
                11px;

            font-weight:
                900;

        }


        .seller-upload-copy p {

            margin:
                0 0 12px;

            color:
                #8090a8;

            font-size:
                9px;

            line-height:
                1.6;

        }


        .seller-upload-copy input {

            height:
                auto;

            padding:
                9px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .88
                );

        }


        .seller-upload-copy
        input[type="file"]::file-selector-button {

            margin-right:
                9px;

            padding:
                8px 10px;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                8px;

            font-family:
                inherit;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        /* ==========================================================
           ACTIONS
        ========================================================== */

        .seller-add-actions {

            margin-top:
                4px;

            padding-top:
                20px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                flex-end;

            gap:
                9px;

            border-top:
                1px solid
                #edf1f5;

        }


        .seller-add-action {

            min-height:
                42px;

            padding:
                0 16px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                7px;

            border-radius:
                10px;

            font-family:
                inherit;

            font-size:
                9px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            transition:
                .18s ease;

        }


        .seller-add-action.cancel {

            color:
                #64748b;

            background:
                #ffffff;

            border:
                1px solid
                #dce5ef;

        }


        .seller-add-action.submit {

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d67df
                );

            border:
                0;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .seller-add-action:hover {

            transform:
                translateY(-1px);

        }


        .seller-add-action:disabled {

            opacity:
                .5;

            cursor:
                not-allowed;

            transform:
                none;

        }


        /* ==========================================================
           SIDE COLUMN
        ========================================================== */

        .seller-add-side {

            display:
                flex;

            flex-direction:
                column;

            gap:
                16px;

        }


        .seller-side-card {

            padding:
                20px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                18px;

            box-shadow:

                0
                9px
                24px
                rgba(
                    40,
                    65,
                    120,
                    .045
                );

        }


        .seller-side-icon {

            width:
                42px;

            height:
                42px;

            margin-bottom:
                13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                12px;

            font-size:
                15px;

        }


        .seller-side-card h4 {

            margin:
                0 0 7px;

            color:
                #14213d;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-side-card p {

            margin:
                0;

            color:
                #8593a8;

            font-size:
                9px;

            line-height:
                1.7;

        }


        .seller-side-list {

            margin:
                12px 0 0;

            padding:
                0;

            list-style:
                none;

            display:
                flex;

            flex-direction:
                column;

            gap:
                9px;

        }


        .seller-side-list li {

            display:
                flex;

            align-items:
                flex-start;

            gap:
                8px;

            color:
                #67778f;

            font-size:
                8px;

            line-height:
                1.55;

        }


        .seller-side-list i {

            margin-top:
                2px;

            color:
                #22c55e;

        }


        /* ==========================================================
           STORE CARD
        ========================================================== */

        .seller-store-card {

            position:
                relative;

            overflow:
                hidden;

            padding:
                21px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #0b2d69,
                    #276fda
                );

            border-radius:
                18px;

            box-shadow:

                0
                11px
                27px
                rgba(
                    24,
                    70,
                    145,
                    .14
                );

        }


        .seller-store-card::after {

            content:
                "";

            position:
                absolute;

            width:
                100px;

            height:
                100px;

            right:
                -35px;

            bottom:
                -40px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .seller-store-card > * {

            position:
                relative;

            z-index:
                2;

        }


        .seller-store-card small {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #a9d2ff;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .8px;

        }


        .seller-store-card strong {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #ffffff;

            font-size:
                13px;

            font-weight:
                900;

        }


        .seller-store-card p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .72
                );

            font-size:
                8px;

            line-height:
                1.6;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (
            max-width: 1100px
        ) {

            .seller-add-layout {

                grid-template-columns:
                    1fr;

            }


            .seller-add-side {

                display:
                    grid;

                grid-template-columns:
                    1fr
                    1fr;

            }


            .seller-store-card {

                grid-column:
                    1 / -1;

            }

        }


        @media (
            max-width: 768px
        ) {

            .seller-add-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-add-topbar {

                padding:
                    0 20px;

            }


            .seller-add-content {

                padding:
                    24px 20px 50px;

            }


            .seller-field-grid {

                grid-template-columns:
                    1fr;

            }


            .seller-field.full {

                grid-column:
                    auto;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-add-topbar-user
            > div:last-child {

                display:
                    none;

            }


            .seller-add-content {

                padding:
                    20px 14px 45px;

            }


            .seller-add-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-back-products {

                width:
                    100%;

            }


            .seller-add-hero {

                min-height:
                    auto;

                padding:
                    24px;

                align-items:
                    flex-start;

                border-radius:
                    19px;

            }


            .seller-add-hero-icon {

                width:
                    54px;

                height:
                    54px;

                border-radius:
                    15px;

                font-size:
                    19px;

            }


            .seller-add-hero h2 {

                font-size:
                    20px;

            }


            .seller-add-form-body {

                padding:
                    18px;

            }


            .seller-add-form-header {

                padding:
                    18px;

            }


            .seller-upload-box {

                grid-template-columns:
                    1fr;

            }


            .seller-upload-preview {

                width:
                    100%;

                height:
                    180px;

            }


            .seller-add-actions {

                flex-direction:
                    column-reverse;

            }


            .seller-add-action {

                width:
                    100%;

            }


            .seller-add-side {

                display:
                    flex;

            }


            .seller-store-card {

                grid-column:
                    auto;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-add-page">


<?php

/*
|--------------------------------------------------------------------------
| SAME SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-add-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-add-topbar">


        <span class="seller-add-topbar-label">

            Seller Center

        </span>


        <div class="seller-add-topbar-user">


            <div class="seller-add-topbar-avatar">

                <?= addProductEscape(
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

                    <?= addProductEscape(
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



    <!-- ===========================================================
         CONTENT
    ============================================================ -->

    <div class="seller-add-content">


        <!-- =======================================================
             HEADING
        ======================================================== -->

        <section class="seller-add-heading">


            <div>


                <span class="seller-add-eyebrow">

                    PRODUCT MANAGEMENT

                </span>


                <h1>

                    Add New Product

                </h1>


                <p>

                    Create a new product listing for
                    <?= addProductEscape(
                        $vendor['business_name']
                    ) ?>.

                </p>


            </div>


            <a
                href="products.php"
                class="seller-back-products"
            >

                <i class="fa-solid fa-arrow-left"></i>

                My Products

            </a>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-add-hero">


            <div class="seller-add-hero-copy">


                <span class="seller-add-hero-label">

                    SELLER WORKSPACE

                </span>


                <h2>

                    Create a product customers will notice.

                </h2>


                <p>

                    Add clear information, accurate pricing,
                    stock and a good product image so your
                    listing looks professional throughout
                    HochipoHub.

                </p>


            </div>


            <div class="seller-add-hero-icon">

                <i class="fa-solid fa-box-open"></i>

            </div>


        </section>



        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (!empty($errors)): ?>


            <div
                class="
                    seller-add-alert
                    error
                "
            >


                <div class="seller-add-alert-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>


                <div>


                    <strong>

                        Unable to add product

                    </strong>


                    <ul>


                        <?php foreach (
                            $errors
                            as $error
                        ): ?>


                            <li>

                                <?= addProductEscape(
                                    $error
                                ) ?>

                            </li>


                        <?php endforeach; ?>


                    </ul>


                </div>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if ($success !== ''): ?>


            <div
                class="
                    seller-add-alert
                    success
                "
            >


                <div class="seller-add-alert-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <div>


                    <strong>

                        Product created

                    </strong>


                    <div>

                        <?= addProductEscape(
                            $success
                        ) ?>

                    </div>


                </div>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             FORM + SIDE INFO
        ======================================================== -->

        <div class="seller-add-layout">


            <!-- ===================================================
                 FORM
            ==================================================== -->

            <section class="seller-add-form-card">


                <div class="seller-add-form-header">


                    <div class="seller-add-form-icon">

                        <i class="fa-solid fa-cube"></i>

                    </div>


                    <div>


                        <h3>

                            Product Information

                        </h3>


                        <p>

                            Fill in the details customers
                            will see in your listing.

                        </p>


                    </div>


                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="seller-add-form-body"
                >


                    <div class="seller-field-grid">


                        <!-- =========================================
                             PRODUCT NAME
                        ========================================== -->

                        <div class="seller-field full">


                            <label for="product_name">

                                <i class="fa-solid fa-tag"></i>

                                Product Name

                                <span class="seller-required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="product_name"
                                name="product_name"
                                maxlength="150"
                                value="<?= addProductEscape(
                                    $productName
                                ) ?>"
                                placeholder="Example: Handmade Tote Bag"
                                required
                            >


                            <small>

                                Keep the name clear and easy
                                for customers to understand.

                            </small>


                        </div>



                        <!-- =========================================
                             CATEGORY
                        ========================================== -->

                        <div class="seller-field">


                            <label for="category_id">

                                <i class="fa-solid fa-layer-group"></i>

                                Category

                                <span class="seller-required">
                                    *
                                </span>

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
                                        <?= $categoryId ===
                                            (int)
                                            $category[
                                                'category_id'
                                            ]
                                                ? 'selected'
                                                : '' ?>
                                    >

                                        <?= addProductEscape(
                                            $category[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                            <small>

                                Choose the closest category
                                for your product.

                            </small>


                        </div>



                        <!-- =========================================
                             STOCK
                        ========================================== -->

                        <div class="seller-field">


                            <label for="stock_quantity">

                                <i class="fa-solid fa-boxes-stacked"></i>

                                Stock Quantity

                                <span class="seller-required">
                                    *
                                </span>

                            </label>


                            <input
                                type="number"
                                id="stock_quantity"
                                name="stock_quantity"
                                min="0"
                                step="1"
                                value="<?= addProductEscape(
                                    $stockQuantity
                                ) ?>"
                                placeholder="0"
                                required
                            >


                            <small>

                                Products with zero stock will
                                be marked Out of Stock.

                            </small>


                        </div>



                        <!-- =========================================
                             PRICE
                        ========================================== -->

                        <div class="seller-field full">


                            <label for="price">

                                <i class="fa-solid fa-money-bill-wave"></i>

                                Selling Price

                                <span class="seller-required">
                                    *
                                </span>

                            </label>


                            <div class="seller-input-prefix">


                                <span>
                                    RM
                                </span>


                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    min="0"
                                    step="0.01"
                                    value="<?= addProductEscape(
                                        $price
                                    ) ?>"
                                    placeholder="0.00"
                                    required
                                >


                            </div>


                            <small>

                                Enter the final selling price
                                customers will pay.

                            </small>


                        </div>



                        <!-- =========================================
                             DESCRIPTION
                        ========================================== -->

                        <div class="seller-field full">


                            <label for="description">

                                <i class="fa-solid fa-align-left"></i>

                                Description

                            </label>


                            <textarea
                                id="description"
                                name="description"
                                placeholder="Describe your product, features, material, size or anything customers should know..."
                            ><?= addProductEscape(
                                $description
                            ) ?></textarea>


                            <small>

                                A useful description helps
                                customers make better purchase decisions.

                            </small>


                        </div>



                        <!-- =========================================
                             IMAGE
                        ========================================== -->

                        <div class="seller-field full">


                            <label for="product_image">

                                <i class="fa-solid fa-image"></i>

                                Product Image

                            </label>


                            <div class="seller-upload-box">


                                <div
                                    class="seller-upload-preview"
                                    id="sellerUploadPreview"
                                >


                                    <i
                                        class="fa-regular fa-image"
                                        id="sellerPreviewIcon"
                                    ></i>


                                    <img
                                        id="sellerPreviewImage"
                                        src=""
                                        alt="Product preview"
                                    >


                                </div>


                                <div class="seller-upload-copy">


                                    <strong>

                                        Upload product photo

                                    </strong>


                                    <p>

                                        Use a clear image with good lighting.
                                        The website will automatically keep
                                        product images inside a controlled size.

                                    </p>


                                    <input
                                        type="file"
                                        id="product_image"
                                        name="product_image"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    >


                                    <small>

                                        JPG, PNG or WEBP · Maximum 5MB.

                                    </small>


                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- =============================================
                         ACTIONS
                    ============================================== -->

                    <div class="seller-add-actions">


                        <a
                            href="products.php"
                            class="
                                seller-add-action
                                cancel
                            "
                        >

                            Cancel

                        </a>


                        <button
                            type="submit"
                            class="
                                seller-add-action
                                submit
                            "
                            <?= !$vendorApproved
                                ? 'disabled'
                                : '' ?>
                        >

                            <i class="fa-solid fa-plus"></i>

                            Add Product

                        </button>


                    </div>


                </form>


            </section>



            <!-- ===================================================
                 SIDE CONTENT
            ==================================================== -->

            <aside class="seller-add-side">


                <!-- ===============================================
                     STORE
                ================================================ -->

                <div class="seller-store-card">


                    <small>

                        CURRENT STORE

                    </small>


                    <strong>

                        <?= addProductEscape(
                            $vendor[
                                'business_name'
                            ]
                        ) ?>

                    </strong>


                    <p>

                        This product will be published
                        under your HochipoHub vendor store.

                    </p>


                </div>



                <!-- ===============================================
                     LISTING TIPS
                ================================================ -->

                <div class="seller-side-card">


                    <div class="seller-side-icon">

                        <i class="fa-regular fa-lightbulb"></i>

                    </div>


                    <h4>

                        Listing Tips

                    </h4>


                    <p>

                        Small details can make your
                        product listing look much better.

                    </p>


                    <ul class="seller-side-list">


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Use a short and clear product name.

                        </li>


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Upload a clean product photo.

                        </li>


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Keep stock quantity accurate.

                        </li>


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Describe important product details.

                        </li>


                    </ul>


                </div>



                <!-- ===============================================
                     IMAGE INFO
                ================================================ -->

                <div class="seller-side-card">


                    <div class="seller-side-icon">

                        <i class="fa-solid fa-camera"></i>

                    </div>


                    <h4>

                        Image Guidelines

                    </h4>


                    <p>

                        JPG, PNG and WEBP are supported.
                        Maximum upload size is 5MB.
                        Large images are displayed inside
                        fixed-size product containers.

                    </p>


                </div>


            </aside>


        </div>


    </div>


</main>



<script>

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE PREVIEW
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const fileInput =
            document.getElementById(
                'product_image'
            );


        const previewImage =
            document.getElementById(
                'sellerPreviewImage'
            );


        const previewIcon =
            document.getElementById(
                'sellerPreviewIcon'
            );


        if (
            !fileInput ||
            !previewImage ||
            !previewIcon
        ) {

            return;

        }


        fileInput.addEventListener(
            'change',
            function () {

                const file =
                    this.files &&
                    this.files.length
                        ? this.files[0]
                        : null;


                if (!file) {

                    previewImage.src =
                        '';

                    previewImage.style.display =
                        'none';

                    previewIcon.style.display =
                        '';

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

                        previewImage.src =
                            event.target.result;

                        previewImage.style.display =
                            'block';

                        previewIcon.style.display =
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