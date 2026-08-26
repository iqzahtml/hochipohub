<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER STORE PROFILE
|--------------------------------------------------------------------------
| File:
| seller/setup_profile.php
|--------------------------------------------------------------------------
|
| Purpose:
| - Create vendor store profile
| - Update vendor store profile
| - Upload / change business logo
| - Display vendor approval status
|
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
        trim(
            (string) $_SESSION['role']
        )
    ) !== 'vendor'
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
| USER ID
|--------------------------------------------------------------------------
*/

$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('storeProfileEscape')) {

    function storeProfileEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('storeProfileStatusClass')) {

    function storeProfileStatusClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        switch ($status) {

            case 'approved':
                return 'approved';

            case 'pending':
                return 'pending';

            case 'rejected':
                return 'rejected';

            default:
                return 'default';

        }
    }

}


/*
|--------------------------------------------------------------------------
| ERROR LIST
|--------------------------------------------------------------------------
*/

$errors = [];


/*
|--------------------------------------------------------------------------
| GET EXISTING VENDOR
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                v.vendor_id,
                v.user_id,
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


    $stmt->execute([
        $userId
    ]);


    $vendor =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $vendor = false;

}


/*
|--------------------------------------------------------------------------
| GET USER DATA WHEN VENDOR PROFILE DOES NOT EXIST
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    try {

        $stmt =
            $db->prepare("
                SELECT

                    user_id,
                    name,
                    email,
                    phone

                FROM users

                WHERE user_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $userId
        ]);


        $userData =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

    }

    catch (Throwable $e) {

        $userData = [];

    }

}

else {

    $userData = $vendor;

}


/*
|--------------------------------------------------------------------------
| SIDEBAR SESSION DATA
|--------------------------------------------------------------------------
*/

if ($vendor) {

    $_SESSION['business_name'] =
        $vendor['business_name'];


    $_SESSION['vendor_approval_status'] =
        $vendor['approval_status'];

}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$currentBusinessName =
    $vendor['business_name']
    ?? '';


$currentBusinessDescription =
    $vendor['business_description']
    ?? '';


$currentBusinessAddress =
    $vendor['business_address']
    ?? '';


$currentCategory =
    $vendor['category']
    ?? '';


$currentDeliveryMethod =
    $vendor['delivery_method']
    ?? 'Both';


$currentBusinessLogo =
    $vendor['business_logo']
    ?? '';


/*
|--------------------------------------------------------------------------
| FORM SUBMIT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | FORM VALUES
    |--------------------------------------------------------------------------
    */

    $businessName =
        trim(
            $_POST['business_name']
            ?? ''
        );


    $businessDescription =
        trim(
            $_POST['business_description']
            ?? ''
        );


    $businessAddress =
        trim(
            $_POST['business_address']
            ?? ''
        );


    $category =
        trim(
            $_POST['category']
            ?? ''
        );


    $deliveryMethod =
        trim(
            $_POST['delivery_method']
            ?? 'Both'
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($businessName === '') {

        $errors[] =
            'Business name is required.';

    }


    if (
        mb_strlen(
            $businessName
        ) > 150
    ) {

        $errors[] =
            'Business name must not exceed 150 characters.';

    }


    if ($category === '') {

        $errors[] =
            'Business category is required.';

    }


    if (
        mb_strlen(
            $category
        ) > 100
    ) {

        $errors[] =
            'Business category must not exceed 100 characters.';

    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERY
    |--------------------------------------------------------------------------
    */

    $allowedDeliveryMethods = [

        'Pickup',
        'Postage',
        'Both'

    ];


    if (
        !in_array(
            $deliveryMethod,
            $allowedDeliveryMethods,
            true
        )
    ) {

        $errors[] =
            'Invalid delivery method.';


        $deliveryMethod =
            'Both';

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    $businessLogo =
        $currentBusinessLogo;


    $newLogoUploaded =
        false;


    $newLogoPath =
        null;


    if (
        isset(
            $_FILES['business_logo']
        ) &&
        $_FILES['business_logo']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {


        /*
        |--------------------------------------------------------------------------
        | FILE DATA
        |--------------------------------------------------------------------------
        */

        $file =
            $_FILES['business_logo'];


        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                'Unable to upload business logo.';

        }


        /*
        |--------------------------------------------------------------------------
        | SIZE
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
                'Business logo must not exceed 5MB.';

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
                'Invalid business logo upload.';

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
                        'Only JPG, JPEG, PNG and WEBP files are allowed.';

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | FILE NAME
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


            $businessLogo =
                'vendor_' .
                $userId .
                '_' .
                time() .
                '_' .
                bin2hex(
                    random_bytes(4)
                ) .
                '.' .
                $extension;


            /*
            |--------------------------------------------------------------------------
            | DIRECTORY
            |--------------------------------------------------------------------------
            */

            $uploadDirectory =
                __DIR__ .
                '/../uploads/vendors/';


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
                        'Unable to create vendor upload directory.';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | MOVE
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $newLogoPath =
                    $uploadDirectory .
                    $businessLogo;


                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $newLogoPath
                    )
                ) {

                    $errors[] =
                        'Unable to save business logo.';


                    $businessLogo =
                        $currentBusinessLogo;

                }

                else {

                    $newLogoUploaded =
                        true;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SAVE
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

                $stmt =
                    $db->prepare("
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

                    $businessName,

                    $businessLogo,

                    $businessDescription,

                    $businessAddress,

                    $category,

                    $deliveryMethod,

                    (int)
                    $vendor['vendor_id'],

                    $userId

                ]);


                /*
                |--------------------------------------------------------------------------
                | DELETE OLD LOGO
                |--------------------------------------------------------------------------
                */

                if (
                    $newLogoUploaded &&
                    !empty(
                        $currentBusinessLogo
                    ) &&
                    $currentBusinessLogo !==
                    $businessLogo
                ) {

                    $oldLogoPath =
                        __DIR__ .
                        '/../uploads/vendors/' .
                        basename(
                            $currentBusinessLogo
                        );


                    if (
                        file_exists(
                            $oldLogoPath
                        ) &&
                        is_file(
                            $oldLogoPath
                        )
                    ) {

                        @unlink(
                            $oldLogoPath
                        );

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | SESSION
                |--------------------------------------------------------------------------
                */

                $_SESSION['business_name'] =
                    $businessName;


                header(
                    'Location: setup_profile.php?success=updated'
                );

                exit;

            }

            catch (Throwable $e) {

                $errors[] =
                    'Failed to update vendor profile.';


                /*
                |--------------------------------------------------------------------------
                | REMOVE NEW LOGO
                |--------------------------------------------------------------------------
                */

                if (
                    $newLogoUploaded &&
                    $newLogoPath &&
                    file_exists(
                        $newLogoPath
                    )
                ) {

                    @unlink(
                        $newLogoPath
                    );

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CREATE NEW VENDOR
        |--------------------------------------------------------------------------
        */

        else {

            $approvalStatus =
                'Pending';


            try {

                $stmt =
                    $db->prepare("
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

                    $userId,

                    $businessName,

                    $businessLogo,

                    $businessDescription,

                    $businessAddress,

                    $category,

                    $deliveryMethod,

                    $approvalStatus

                ]);


                $_SESSION['business_name'] =
                    $businessName;


                $_SESSION['vendor_approval_status'] =
                    $approvalStatus;


                header(
                    'Location: setup_profile.php?success=created'
                );

                exit;

            }

            catch (Throwable $e) {

                $errors[] =
                    'Failed to create vendor profile.';


                if (
                    $newLogoUploaded &&
                    $newLogoPath &&
                    file_exists(
                        $newLogoPath
                    )
                ) {

                    @unlink(
                        $newLogoPath
                    );

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | KEEP FORM VALUES AFTER ERROR
    |--------------------------------------------------------------------------
    */

    $currentBusinessName =
        $businessName;


    $currentBusinessDescription =
        $businessDescription;


    $currentBusinessAddress =
        $businessAddress;


    $currentCategory =
        $category;


    $currentDeliveryMethod =
        $deliveryMethod;


    $currentBusinessLogo =
        $businessLogo;

}


/*
|--------------------------------------------------------------------------
| DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$ownerName =
    $vendor['name']
    ?? $userData['name']
    ?? $_SESSION['name']
    ?? 'Vendor';


$ownerEmail =
    $vendor['email']
    ?? $userData['email']
    ?? '';


$currentApprovalStatus =
    $vendor['approval_status']
    ?? 'Not Submitted';


$statusClass =
    storeProfileStatusClass(
        $currentApprovalStatus
    );


/*
|--------------------------------------------------------------------------
| LOGO URL
|--------------------------------------------------------------------------
*/

$currentLogoUrl =
    $currentBusinessLogo !== ''
        ? BASE_URL .
            'uploads/vendors/' .
            rawurlencode(
                basename(
                    $currentBusinessLogo
                )
            )
        : '';


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    $vendor
        ? 'Store Profile | Seller | HochipoHub'
        : 'Setup Store | Seller | HochipoHub';

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
        <?= storeProfileEscape(
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

        .seller-store-page {

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

        .seller-store-main {

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
                    circle at 96% 7%,
                    rgba(
                        37,
                        99,
                        235,
                        .07
                    ),
                    transparent 24%
                ),

                #f6f8fc;

        }


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .seller-store-topbar {

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
                    .96
                );

            border-bottom:
                1px solid
                #e8edf5;

        }


        .seller-store-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-store-topbar-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-store-topbar-avatar {

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

            overflow:
                hidden;

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


        .seller-store-topbar-avatar img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        .seller-store-topbar-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-store-topbar-user small {

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

        .seller-store-content {

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
           HEADING
        ========================================================== */

        .seller-store-heading {

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


        .seller-store-eyebrow {

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


        .seller-store-heading h1 {

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


        .seller-store-heading p {

            margin:
                7px 0 0;

            color:
                #7b879c;

            font-size:
                11px;

        }


        .seller-store-dashboard-link {

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


        /* ==========================================================
           HERO
        ========================================================== */

        .seller-store-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                180px;

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
                25px;

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


        .seller-store-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                230px;

            height:
                230px;

            top:
                -135px;

            right:
                -50px;

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


        .seller-store-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                150px;

            height:
                150px;

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


        .seller-store-hero-copy {

            position:
                relative;

            z-index:
                2;

            max-width:
                650px;

        }


        .seller-store-hero-label {

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
                1.3px;

        }


        .seller-store-hero h2 {

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

        }


        .seller-store-hero p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .77
                );

            font-size:
                10px;

            line-height:
                1.7;

        }


        .seller-store-hero-logo {

            position:
                relative;

            z-index:
                2;

            width:
                90px;

            height:
                90px;

            flex-shrink:
                0;

            overflow:
                hidden;

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
                    .14
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .25
                );

            border-radius:
                22px;

            font-size:
                31px;

            backdrop-filter:
                blur(10px);

        }


        .seller-store-hero-logo img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        /* ==========================================================
           ALERTS
        ========================================================== */

        .seller-store-alert {

            margin-bottom:
                18px;

            padding:
                14px 16px;

            display:
                flex;

            align-items:
                flex-start;

            gap:
                10px;

            border-radius:
                12px;

            font-size:
                9px;

            line-height:
                1.6;

        }


        .seller-store-alert.success {

            color:
                #166534;

            background:
                #f0fdf4;

            border:
                1px solid
                #bbf7d0;

        }


        .seller-store-alert.error {

            color:
                #991b1b;

            background:
                #fef2f2;

            border:
                1px solid
                #fecaca;

        }


        .seller-store-alert.info {

            color:
                #1e40af;

            background:
                #eff6ff;

            border:
                1px solid
                #bfdbfe;

        }


        .seller-store-alert ul {

            margin:
                5px 0 0;

            padding-left:
                18px;

        }


        /* ==========================================================
           STATUS
        ========================================================== */

        .seller-store-status {

            min-height:
                28px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            border-radius:
                999px;

            font-size:
                7px;

            font-weight:
                900;

            text-transform:
                uppercase;

        }


        .seller-store-status::before {

            content:
                "";

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .seller-store-status.approved {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .seller-store-status.pending {

            color:
                #b45309;

            background:
                #fffbeb;

        }


        .seller-store-status.rejected {

            color:
                #b91c1c;

            background:
                #fef2f2;

        }


        .seller-store-status.default {

            color:
                #64748b;

            background:
                #f1f5f9;

        }


        /* ==========================================================
           LAYOUT
        ========================================================== */

        .seller-store-layout {

            display:
                grid;

            grid-template-columns:

                minmax(
                    0,
                    1fr
                )

                300px;

            align-items:
                start;

            gap:
                22px;

        }


        /* ==========================================================
           FORM CARD
        ========================================================== */

        .seller-store-form-card {

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


        .seller-store-form-header {

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


        .seller-store-form-icon {

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


        .seller-store-form-header h3 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                16px;

            font-weight:
                900;

        }


        .seller-store-form-header p {

            margin:
                0;

            color:
                #8a97aa;

            font-size:
                9px;

        }


        .seller-store-form-body {

            padding:
                25px;

        }


        /* ==========================================================
           FIELDS
        ========================================================== */

        .seller-store-field-grid {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                17px;

        }


        .seller-store-field {

            margin-bottom:
                18px;

        }


        .seller-store-field.full {

            grid-column:
                1 / -1;

        }


        .seller-store-field label {

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


        .seller-store-field label i {

            color:
                #2563eb;

            font-size:
                9px;

        }


        .seller-store-required {

            color:
                #ef4444;

        }


        .seller-store-field input,
        .seller-store-field textarea,
        .seller-store-field select {

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


        .seller-store-field input,
        .seller-store-field select {

            height:
                45px;

            padding:
                0 13px;

        }


        .seller-store-field textarea {

            min-height:
                135px;

            padding:
                13px;

            resize:
                vertical;

            line-height:
                1.7;

        }


        .seller-store-field input:focus,
        .seller-store-field textarea:focus,
        .seller-store-field select:focus {

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


        .seller-store-field small {

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
           LOGO UPLOAD
        ========================================================== */

        .seller-store-upload {

            min-height:
                180px;

            padding:
                20px;

            display:
                grid;

            grid-template-columns:
                125px
                minmax(
                    0,
                    1fr
                );

            align-items:
                center;

            gap:
                18px;

            background:

                linear-gradient(
                    135deg,
                    #f8fbff,
                    #eef6ff
                );

            border:
                1px dashed
                #b9d4f5;

            border-radius:
                16px;

        }


        .seller-store-preview {

            width:
                125px;

            height:
                125px;

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
                18px;

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
                35px;

        }


        .seller-store-preview img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

            object-position:
                center;

        }


        .seller-store-upload-copy strong {

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


        .seller-store-upload-copy p {

            margin:
                0 0 12px;

            color:
                #8090a8;

            font-size:
                9px;

            line-height:
                1.6;

        }


        .seller-store-upload-copy input {

            height:
                auto;

            padding:
                9px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .9
                );

        }


        .seller-store-upload-copy
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

        .seller-store-actions {

            margin-top:
                3px;

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


        .seller-store-action {

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

        }


        .seller-store-action.cancel {

            color:
                #64748b;

            background:
                #ffffff;

            border:
                1px solid
                #dce5ef;

        }


        .seller-store-action.save {

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


        /* ==========================================================
           SIDE COLUMN
        ========================================================== */

        .seller-store-side {

            display:
                flex;

            flex-direction:
                column;

            gap:
                16px;

        }


        .seller-store-side-card {

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


        .seller-store-side-icon {

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


        .seller-store-side-card h4 {

            margin:
                0 0 7px;

            color:
                #14213d;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-store-side-card p {

            margin:
                0;

            color:
                #8593a8;

            font-size:
                9px;

            line-height:
                1.7;

        }


        .seller-store-side-list {

            margin:
                12px 0 0;

            padding:
                0;

            display:
                flex;

            flex-direction:
                column;

            gap:
                9px;

            list-style:
                none;

        }


        .seller-store-side-list li {

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


        .seller-store-side-list i {

            margin-top:
                2px;

            color:
                #22c55e;

        }


        /* ==========================================================
           OWNER CARD
        ========================================================== */

        .seller-store-owner {

            position:
                relative;

            overflow:
                hidden;

            padding:
                20px;

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


        .seller-store-owner::after {

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


        .seller-store-owner > * {

            position:
                relative;

            z-index:
                2;

        }


        .seller-store-owner small {

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


        .seller-store-owner strong {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #ffffff;

            font-size:
                13px;

            font-weight:
                900;

        }


        .seller-store-owner span {

            display:
                block;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .72
                );

            font-size:
                8px;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (
            max-width: 1100px
        ) {

            .seller-store-layout {

                grid-template-columns:
                    1fr;

            }


            .seller-store-side {

                display:
                    grid;

                grid-template-columns:
                    1fr
                    1fr;

            }


            .seller-store-owner {

                grid-column:
                    1 / -1;

            }

        }


        @media (
            max-width: 768px
        ) {

            .seller-store-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-store-topbar {

                padding:
                    0 20px;

            }


            .seller-store-content {

                padding:
                    24px 20px 50px;

            }


            .seller-store-field-grid {

                grid-template-columns:
                    1fr;

            }


            .seller-store-field.full {

                grid-column:
                    auto;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-store-topbar-user
            > div:last-child {

                display:
                    none;

            }


            .seller-store-content {

                padding:
                    20px 14px 45px;

            }


            .seller-store-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-store-dashboard-link {

                width:
                    100%;

            }


            .seller-store-hero {

                min-height:
                    auto;

                padding:
                    23px;

                align-items:
                    flex-start;

            }


            .seller-store-hero h2 {

                font-size:
                    20px;

            }


            .seller-store-hero-logo {

                width:
                    58px;

                height:
                    58px;

                border-radius:
                    16px;

                font-size:
                    21px;

            }


            .seller-store-form-body {

                padding:
                    18px;

            }


            .seller-store-upload {

                grid-template-columns:
                    1fr;

            }


            .seller-store-preview {

                width:
                    100%;

                height:
                    190px;

            }


            .seller-store-actions {

                flex-direction:
                    column-reverse;

            }


            .seller-store-action {

                width:
                    100%;

            }


            .seller-store-side {

                display:
                    flex;

            }


            .seller-store-owner {

                grid-column:
                    auto;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-store-page">


<?php

/*
|--------------------------------------------------------------------------
| SHARED SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-store-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-store-topbar">


        <span class="seller-store-topbar-label">

            Seller Center

        </span>


        <div class="seller-store-topbar-user">


            <div class="seller-store-topbar-avatar">


                <?php if (
                    $currentLogoUrl !== ''
                ): ?>


                    <img
                        src="<?= storeProfileEscape(
                            $currentLogoUrl
                        ) ?>"
                        alt="<?= storeProfileEscape(
                            $currentBusinessName
                        ) ?>"
                        onerror="
                            this.style.display='none';
                            this.parentElement.innerHTML='<?= storeProfileEscape(
                                strtoupper(
                                    substr(
                                        $ownerName,
                                        0,
                                        1
                                    )
                                )
                            ) ?>';
                        "
                    >


                <?php else: ?>


                    <?= storeProfileEscape(
                        strtoupper(
                            substr(
                                $ownerName,
                                0,
                                1
                            )
                        )
                    ) ?>


                <?php endif; ?>


            </div>


            <div>


                <strong>

                    <?= storeProfileEscape(
                        $ownerName
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

    <div class="seller-store-content">


        <!-- =======================================================
             HEADING
        ======================================================== -->

        <section class="seller-store-heading">


            <div>


                <span class="seller-store-eyebrow">

                    STORE MANAGEMENT

                </span>


                <h1>

                    <?= $vendor
                        ? 'Store Profile'
                        : 'Setup Your Store' ?>

                </h1>


                <p>

                    Manage the business information customers
                    see across HochipoHub.

                </p>


            </div>


            <a
                href="<?= storeProfileEscape(
                    BASE_URL
                ) ?>seller/dashboard.php"
                class="seller-store-dashboard-link"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Dashboard

            </a>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-store-hero">


            <div class="seller-store-hero-copy">


                <span class="seller-store-hero-label">

                    YOUR STOREFRONT

                </span>


                <h2>

                    Make your store easy to recognise.

                </h2>


                <p>

                    Keep your store name, description,
                    delivery options and branding up to date
                    so customers know exactly who they are
                    buying from.

                </p>


            </div>


            <div class="seller-store-hero-logo">


                <?php if (
                    $currentLogoUrl !== ''
                ): ?>


                    <img
                        src="<?= storeProfileEscape(
                            $currentLogoUrl
                        ) ?>"
                        alt="<?= storeProfileEscape(
                            $currentBusinessName
                        ) ?>"
                        onerror="
                            this.style.display='none';
                            this.parentElement.innerHTML='<i class=&quot;fa-solid fa-store&quot;></i>';
                        "
                    >


                <?php else: ?>


                    <i class="fa-solid fa-store"></i>


                <?php endif; ?>


            </div>


        </section>



        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if (
            isset(
                $_GET['success']
            )
        ): ?>


            <div
                class="
                    seller-store-alert
                    success
                "
            >


                <i class="fa-solid fa-circle-check"></i>


                <div>


                    <?php if (
                        $_GET['success']
                        === 'created'
                    ): ?>


                        <strong>
                            Store profile created.
                        </strong>

                        Your vendor profile has been submitted
                        and is now waiting for admin approval.


                    <?php elseif (
                        $_GET['success']
                        === 'updated'
                    ): ?>


                        <strong>
                            Store profile updated.
                        </strong>

                        Your store information has been saved successfully.


                    <?php endif; ?>


                </div>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             ERRORS
        ======================================================== -->

        <?php if (
            !empty(
                $errors
            )
        ): ?>


            <div
                class="
                    seller-store-alert
                    error
                "
            >


                <i class="fa-solid fa-triangle-exclamation"></i>


                <div>


                    <strong>

                        Please fix the following:

                    </strong>


                    <ul>


                        <?php foreach (
                            $errors
                            as $error
                        ): ?>


                            <li>

                                <?= storeProfileEscape(
                                    $error
                                ) ?>

                            </li>


                        <?php endforeach; ?>


                    </ul>


                </div>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             APPROVAL STATUS
        ======================================================== -->

        <?php if ($vendor): ?>


            <div
                class="
                    seller-store-alert
                    info
                "
            >


                <i class="fa-solid fa-shield-halved"></i>


                <div>


                    <strong>

                        Store approval status

                    </strong>


                    <div
                        style="
                            margin-top:7px;
                        "
                    >


                        <span
                            class="
                                seller-store-status
                                <?= storeProfileEscape(
                                    $statusClass
                                ) ?>
                            "
                        >

                            <?= storeProfileEscape(
                                $currentApprovalStatus
                            ) ?>

                        </span>


                    </div>


                </div>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             MAIN LAYOUT
        ======================================================== -->

        <div class="seller-store-layout">


            <!-- ===================================================
                 FORM
            ==================================================== -->

            <section class="seller-store-form-card">


                <div class="seller-store-form-header">


                    <div class="seller-store-form-icon">

                        <i class="fa-solid fa-store"></i>

                    </div>


                    <div>


                        <h3>

                            Store Information

                        </h3>


                        <p>

                            Update the public information
                            shown on your vendor profile.

                        </p>


                    </div>


                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="seller-store-form-body"
                >


                    <div class="seller-store-field-grid">


                        <!-- =========================================
                             BUSINESS NAME
                        ========================================== -->

                        <div class="seller-store-field">


                            <label for="business_name">

                                <i class="fa-solid fa-shop"></i>

                                Business Name

                                <span class="seller-store-required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="business_name"
                                name="business_name"
                                value="<?= storeProfileEscape(
                                    $currentBusinessName
                                ) ?>"
                                maxlength="150"
                                placeholder="Example: Hochipo Crafts"
                                required
                            >


                            <small>

                                This name appears across your
                                store and product listings.

                            </small>


                        </div>



                        <!-- =========================================
                             CATEGORY
                        ========================================== -->

                        <div class="seller-store-field">


                            <label for="category">

                                <i class="fa-solid fa-layer-group"></i>

                                Business Category

                                <span class="seller-store-required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="category"
                                name="category"
                                value="<?= storeProfileEscape(
                                    $currentCategory
                                ) ?>"
                                maxlength="100"
                                placeholder="Example: Food, Fashion, Technology"
                                required
                            >


                            <small>

                                Describe the main type of
                                products your business sells.

                            </small>


                        </div>



                        <!-- =========================================
                             DELIVERY
                        ========================================== -->

                        <div class="seller-store-field full">


                            <label for="delivery_method">

                                <i class="fa-solid fa-truck"></i>

                                Delivery Method

                                <span class="seller-store-required">
                                    *
                                </span>

                            </label>


                            <select
                                id="delivery_method"
                                name="delivery_method"
                                required
                            >


                                <option
                                    value="Pickup"
                                    <?= $currentDeliveryMethod === 'Pickup'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Pickup Only

                                </option>


                                <option
                                    value="Postage"
                                    <?= $currentDeliveryMethod === 'Postage'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Postage Only

                                </option>


                                <option
                                    value="Both"
                                    <?= $currentDeliveryMethod === 'Both'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Pickup & Postage

                                </option>


                            </select>


                            <small>

                                Choose how customers can
                                receive products from your store.

                            </small>


                        </div>



                        <!-- =========================================
                             DESCRIPTION
                        ========================================== -->

                        <div class="seller-store-field full">


                            <label for="business_description">

                                <i class="fa-solid fa-align-left"></i>

                                Business Description

                            </label>


                            <textarea
                                id="business_description"
                                name="business_description"
                                placeholder="Tell customers about your business, products and what makes your store special..."
                            ><?= storeProfileEscape(
                                $currentBusinessDescription
                            ) ?></textarea>


                            <small>

                                A short introduction helps customers
                                understand your store.

                            </small>


                        </div>



                        <!-- =========================================
                             ADDRESS
                        ========================================== -->

                        <div class="seller-store-field full">


                            <label for="business_address">

                                <i class="fa-solid fa-location-dot"></i>

                                Business Address

                            </label>


                            <textarea
                                id="business_address"
                                name="business_address"
                                placeholder="Enter your business address..."
                            ><?= storeProfileEscape(
                                $currentBusinessAddress
                            ) ?></textarea>


                            <small>

                                Keep this accurate if your
                                business supports customer pickup.

                            </small>


                        </div>



                        <!-- =========================================
                             BUSINESS LOGO
                        ========================================== -->

                        <div class="seller-store-field full">


                            <label for="business_logo">

                                <i class="fa-solid fa-image"></i>


                                <?= $vendor
                                    ? 'Business Logo'
                                    : 'Upload Business Logo' ?>


                            </label>


                            <div class="seller-store-upload">


                                <div
                                    class="seller-store-preview"
                                    id="storeLogoPreview"
                                >


                                    <?php if (
                                        $currentLogoUrl !== ''
                                    ): ?>


                                        <img
                                            id="storeLogoPreviewImage"
                                            src="<?= storeProfileEscape(
                                                $currentLogoUrl
                                            ) ?>"
                                            alt="Store Logo"
                                        >


                                        <i
                                            class="fa-solid fa-store"
                                            id="storeLogoPreviewIcon"
                                            style="display:none;"
                                        ></i>


                                    <?php else: ?>


                                        <img
                                            id="storeLogoPreviewImage"
                                            src=""
                                            alt="Store Logo"
                                            style="display:none;"
                                        >


                                        <i
                                            class="fa-solid fa-store"
                                            id="storeLogoPreviewIcon"
                                        ></i>


                                    <?php endif; ?>


                                </div>


                                <div class="seller-store-upload-copy">


                                    <strong>


                                        <?= $vendor
                                            ? 'Change store logo'
                                            : 'Choose your store logo' ?>


                                    </strong>


                                    <p>

                                        Upload a clear square or near-square
                                        image. HochipoHub will display it inside
                                        controlled logo containers throughout
                                        the marketplace.

                                    </p>


                                    <input
                                        type="file"
                                        id="business_logo"
                                        name="business_logo"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    >


                                    <small>

                                        JPG, JPEG, PNG or WEBP · Maximum 5MB.

                                    </small>


                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- =============================================
                         ACTIONS
                    ============================================== -->

                    <div class="seller-store-actions">


                        <a
                            href="<?= storeProfileEscape(
                                BASE_URL
                            ) ?>seller/dashboard.php"
                            class="
                                seller-store-action
                                cancel
                            "
                        >

                            Cancel

                        </a>


                        <button
                            type="submit"
                            class="
                                seller-store-action
                                save
                            "
                        >

                            <i class="fa-solid fa-floppy-disk"></i>


                            <?= $vendor
                                ? 'Update Store'
                                : 'Create Store' ?>


                        </button>


                    </div>


                </form>


            </section>



            <!-- ===================================================
                 SIDE CONTENT
            ==================================================== -->

            <aside class="seller-store-side">


                <!-- ===============================================
                     OWNER
                ================================================ -->

                <section class="seller-store-owner">


                    <small>

                        STORE OWNER

                    </small>


                    <strong>

                        <?= storeProfileEscape(
                            $ownerName
                        ) ?>

                    </strong>


                    <span>

                        <?= storeProfileEscape(
                            $ownerEmail
                        ) ?>

                    </span>


                </section>



                <!-- ===============================================
                     STATUS
                ================================================ -->

                <section class="seller-store-side-card">


                    <div class="seller-store-side-icon">

                        <i class="fa-solid fa-shield-halved"></i>

                    </div>


                    <h4>

                        Approval Status

                    </h4>


                    <p>

                        Admin approval determines when your store
                        and products can appear normally throughout
                        the HochipoHub marketplace.

                    </p>


                    <div
                        style="
                            margin-top:13px;
                        "
                    >


                        <span
                            class="
                                seller-store-status
                                <?= storeProfileEscape(
                                    $statusClass
                                ) ?>
                            "
                        >

                            <?= storeProfileEscape(
                                $currentApprovalStatus
                            ) ?>

                        </span>


                    </div>


                </section>



                <!-- ===============================================
                     PROFILE TIPS
                ================================================ -->

                <section class="seller-store-side-card">


                    <div class="seller-store-side-icon">

                        <i class="fa-regular fa-lightbulb"></i>

                    </div>


                    <h4>

                        Better Store Profile

                    </h4>


                    <p>

                        Complete profiles make your seller
                        page easier for customers to trust.

                    </p>


                    <ul class="seller-store-side-list">


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Use a recognisable business name.

                        </li>


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Add a clear business logo.

                        </li>


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Explain what your store sells.

                        </li>


                        <li>

                            <i class="fa-solid fa-check"></i>

                            Keep delivery information accurate.

                        </li>


                    </ul>


                </section>



                <!-- ===============================================
                     LOGO INFO
                ================================================ -->

                <section class="seller-store-side-card">


                    <div class="seller-store-side-icon">

                        <i class="fa-solid fa-camera"></i>

                    </div>


                    <h4>

                        Logo Guidelines

                    </h4>


                    <p>

                        Supported formats are JPG, JPEG, PNG
                        and WEBP with a maximum size of 5MB.
                        A square image usually gives the best
                        result in store cards and the seller sidebar.

                    </p>


                </section>


            </aside>


        </div>


    </div>


</main>



<script>

/*
|--------------------------------------------------------------------------
| STORE LOGO PREVIEW
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const fileInput =
            document.getElementById(
                'business_logo'
            );


        const previewImage =
            document.getElementById(
                'storeLogoPreviewImage'
            );


        const previewIcon =
            document.getElementById(
                'storeLogoPreviewIcon'
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