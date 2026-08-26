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

$errors = [];


/*
|--------------------------------------------------------------------------
| GET EXISTING VENDOR
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        vendor_id,
        user_id,
        business_name,
        business_logo,
        business_description,
        business_address,
        category,
        delivery_method,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$vendor = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$current_business_name =
    $vendor['business_name'] ?? '';

$current_business_description =
    $vendor['business_description'] ?? '';

$current_business_address =
    $vendor['business_address'] ?? '';

$current_category =
    $vendor['category'] ?? '';

$current_delivery_method =
    $vendor['delivery_method'] ?? 'Both';

$current_business_logo =
    $vendor['business_logo'] ?? '';


/*
|--------------------------------------------------------------------------
| FORM SUBMIT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $business_name =
        trim($_POST['business_name'] ?? '');

    $business_description =
        trim($_POST['business_description'] ?? '');

    $business_address =
        trim($_POST['business_address'] ?? '');

    $category =
        trim($_POST['category'] ?? '');

    $delivery_method =
        trim($_POST['delivery_method'] ?? 'Both');


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($business_name === '') {

        $errors[] =
            "Business name is required.";

    }

    if (mb_strlen($business_name) > 150) {

        $errors[] =
            "Business name must not exceed 150 characters.";

    }

    if ($category === '') {

        $errors[] =
            "Business category is required.";

    }


    $allowed_delivery_methods = [
        'Pickup',
        'Postage',
        'Both'
    ];


    if (
        !in_array(
            $delivery_method,
            $allowed_delivery_methods,
            true
        )
    ) {

        $errors[] =
            "Invalid delivery method.";

        $delivery_method = 'Both';

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    $business_logo =
        $current_business_logo;

    $new_logo_uploaded = false;


    if (
        isset($_FILES['business_logo']) &&
        $_FILES['business_logo']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['business_logo']['error']
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                "Unable to upload business logo.";

        } else {

            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];


            $file_name =
                $_FILES['business_logo']['name'];

            $file_tmp =
                $_FILES['business_logo']['tmp_name'];

            $file_size =
                $_FILES['business_logo']['size'];


            $extension =
                strtolower(
                    pathinfo(
                        $file_name,
                        PATHINFO_EXTENSION
                    )
                );


            if (
                !in_array(
                    $extension,
                    $allowed_extensions,
                    true
                )
            ) {

                $errors[] =
                    "Only JPG, JPEG, PNG and WEBP files are allowed.";

            }


            if (
                $file_size >
                5 * 1024 * 1024
            ) {

                $errors[] =
                    "Business logo must not exceed 5MB.";

            }


            if (empty($errors)) {

                $business_logo =
                    'vendor_' .
                    $user_id .
                    '_' .
                    time() .
                    '_' .
                    bin2hex(
                        random_bytes(4)
                    ) .
                    '.' .
                    $extension;


                $upload_directory =
                    '../uploads/vendors/';


                if (!is_dir($upload_directory)) {

                    mkdir(
                        $upload_directory,
                        0755,
                        true
                    );

                }


                $upload_path =
                    $upload_directory .
                    $business_logo;


                if (
                    !move_uploaded_file(
                        $file_tmp,
                        $upload_path
                    )
                ) {

                    $errors[] =
                        "Unable to save business logo.";

                    $business_logo =
                        $current_business_logo;

                } else {

                    $new_logo_uploaded = true;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SAVE PROFILE
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        /*
        |--------------------------------------------------------------------------
        | UPDATE EXISTING VENDOR
        |--------------------------------------------------------------------------
        */

        if ($vendor) {

            try {

                $stmt = $conn->prepare("
                    UPDATE vendors
                    SET
                        business_name = ?,
                        business_logo = ?,
                        business_description = ?,
                        business_address = ?,
                        category = ?,
                        delivery_method = ?
                    WHERE vendor_id = ?
                    AND user_id = ?
                ");


                $stmt->execute([
                    $business_name,
                    $business_logo,
                    $business_description,
                    $business_address,
                    $category,
                    $delivery_method,
                    $vendor['vendor_id'],
                    $user_id
                ]);


                /*
                |--------------------------------------------------------------------------
                | DELETE OLD LOGO
                |--------------------------------------------------------------------------
                */

                if (
                    $new_logo_uploaded &&
                    !empty($current_business_logo) &&
                    $current_business_logo !== $business_logo
                ) {

                    $old_logo =
                        '../uploads/vendors/' .
                        $current_business_logo;


                    if (
                        file_exists($old_logo) &&
                        is_file($old_logo)
                    ) {

                        unlink($old_logo);

                    }

                }


                header(
                    "Location: setup_profile.php?success=updated"
                );

                exit;


            } catch (PDOException $e) {

                $errors[] =
                    "Failed to update vendor profile.";


                /*
                |--------------------------------------------------------------------------
                | REMOVE NEW LOGO IF UPDATE FAILED
                |--------------------------------------------------------------------------
                */

                if ($new_logo_uploaded) {

                    $new_logo_path =
                        '../uploads/vendors/' .
                        $business_logo;


                    if (
                        file_exists($new_logo_path) &&
                        is_file($new_logo_path)
                    ) {

                        unlink($new_logo_path);

                    }

                }

            }


        } else {

            /*
            |--------------------------------------------------------------------------
            | CREATE NEW VENDOR PROFILE
            |--------------------------------------------------------------------------
            */

            $approval_status =
                'Pending';


            try {

                $stmt = $conn->prepare("
                    INSERT INTO vendors
                    (
                        user_id,
                        business_name,
                        business_logo,
                        business_description,
                        business_address,
                        category,
                        delivery_method,
                        approval_status
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


                $stmt->execute([
                    $user_id,
                    $business_name,
                    $business_logo,
                    $business_description,
                    $business_address,
                    $category,
                    $delivery_method,
                    $approval_status
                ]);


                header(
                    "Location: setup_profile.php?success=created"
                );

                exit;


            } catch (PDOException $e) {

                $errors[] =
                    "Failed to create vendor profile.";


                /*
                |--------------------------------------------------------------------------
                | REMOVE UPLOADED LOGO IF INSERT FAILED
                |--------------------------------------------------------------------------
                */

                if ($new_logo_uploaded) {

                    $new_logo_path =
                        '../uploads/vendors/' .
                        $business_logo;


                    if (
                        file_exists($new_logo_path) &&
                        is_file($new_logo_path)
                    ) {

                        unlink($new_logo_path);

                    }

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | KEEP FORM VALUES AFTER ERROR
    |--------------------------------------------------------------------------
    */

    $current_business_name =
        $business_name;

    $current_business_description =
        $business_description;

    $current_business_address =
        $business_address;

    $current_category =
        $category;

    $current_delivery_method =
        $delivery_method;

    $current_business_logo =
        $business_logo;

}

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
        Setup Store | Seller | HochipoHub
    </title>


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

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="seller-dashboard-page seller-inner-page">


<div class="dashboard-layout">


    <?php include '../includes/vendor_sidebar.php'; ?>


    <main class="dashboard-content">


        <div class="page-header">

            <div>

                <h1>

                    <?= $vendor
                        ? 'Store Profile'
                        : 'Setup Your Store' ?>

                </h1>


                <p>

                    Create and manage your HochipoHub vendor profile.

                </p>

            </div>

        </div>


        <?php if (isset($_GET['success'])): ?>


            <div class="alert alert-success">


                <?php if ($_GET['success'] === 'created'): ?>


                    Your vendor profile has been created.

                    It is now waiting for admin approval.


                <?php elseif ($_GET['success'] === 'updated'): ?>


                    Your vendor profile has been updated successfully.


                <?php endif; ?>


            </div>


        <?php endif; ?>


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


        <?php if ($vendor): ?>


            <div class="alert alert-info">


                Current approval status:


                <strong>

                    <?= htmlspecialchars(
                        $vendor['approval_status']
                    ) ?>

                </strong>


            </div>


        <?php endif; ?>


        <div class="form-card">


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- BUSINESS NAME -->


                <div class="form-group">


                    <label for="business_name">

                        Business Name

                    </label>


                    <input
                        type="text"
                        id="business_name"
                        name="business_name"
                        value="<?= htmlspecialchars(
                            $current_business_name
                        ) ?>"
                        maxlength="150"
                        required
                    >


                </div>


                <!-- CATEGORY -->


                <div class="form-group">


                    <label for="category">

                        Business Category

                    </label>


                    <input
                        type="text"
                        id="category"
                        name="category"
                        value="<?= htmlspecialchars(
                            $current_category
                        ) ?>"
                        maxlength="100"
                        placeholder="Example: Food, Fashion, Technology"
                        required
                    >


                </div>


                <!-- DESCRIPTION -->


                <div class="form-group">


                    <label for="business_description">

                        Business Description

                    </label>


                    <textarea
                        id="business_description"
                        name="business_description"
                        rows="6"
                        placeholder="Tell customers about your business..."
                    ><?= htmlspecialchars(
                        $current_business_description
                    ) ?></textarea>


                </div>


                <!-- ADDRESS -->


                <div class="form-group">


                    <label for="business_address">

                        Business Address

                    </label>


                    <textarea
                        id="business_address"
                        name="business_address"
                        rows="5"
                        placeholder="Enter your business address..."
                    ><?= htmlspecialchars(
                        $current_business_address
                    ) ?></textarea>


                </div>


                <!-- DELIVERY -->


                <div class="form-group">


                    <label for="delivery_method">

                        Delivery Method

                    </label>


                    <select
                        id="delivery_method"
                        name="delivery_method"
                        required
                    >


                        <option
                            value="Pickup"
                            <?= $current_delivery_method === 'Pickup'
                                ? 'selected'
                                : '' ?>
                        >

                            Pickup Only

                        </option>


                        <option
                            value="Postage"
                            <?= $current_delivery_method === 'Postage'
                                ? 'selected'
                                : '' ?>
                        >

                            Postage Only

                        </option>


                        <option
                            value="Both"
                            <?= $current_delivery_method === 'Both'
                                ? 'selected'
                                : '' ?>
                        >

                            Pickup & Postage

                        </option>


                    </select>


                </div>


                <!-- CURRENT LOGO -->


                <?php if (!empty($current_business_logo)): ?>


                    <div class="form-group">


                        <label>

                            Current Business Logo

                        </label>


                        <div>


                            <img
                                src="../uploads/vendors/<?= htmlspecialchars($current_business_logo) ?>"
                                alt="Business Logo"
                                style="
                                    width:150px;
                                    height:150px;
                                    object-fit:cover;
                                    border-radius:15px;
                                "
                            >


                        </div>


                    </div>


                <?php endif; ?>


                <!-- LOGO -->


                <div class="form-group">


                    <label for="business_logo">


                        <?= $vendor
                            ? 'Change Business Logo'
                            : 'Business Logo' ?>


                    </label>


                    <input
                        type="file"
                        id="business_logo"
                        name="business_logo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >


                    <small>

                        JPG, JPEG, PNG or WEBP. Maximum 5MB.

                    </small>


                </div>


                <!-- BUTTON -->


                <div class="form-actions">


                    <a
                        href="dashboard.php"
                        class="btn btn-secondary"
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >


                        <?= $vendor
                            ? 'Update Store'
                            : 'Create Store' ?>


                    </button>


                </div>


            </form>


        </div>


    </main>


</div>


<?php include '../includes/footer.php'; ?>


</body>

</html>
