<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER STORE PROFILE
|--------------------------------------------------------------------------
| File:
| seller/setup_profile.php
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
| LOGIN
|--------------------------------------------------------------------------
*/

requireLogin();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


if (!($db instanceof PDO)) {

    die(
        'Database connection is not available.'
    );
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );


if ($userId <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

$userStmt =
    $db->prepare("
        SELECT
            user_id,
            name,
            email,
            phone,
            role,
            status

        FROM users

        WHERE user_id = ?

        LIMIT 1
    ");


$userStmt->execute([
    $userId
]);


$currentUser =
    $userStmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$currentUser) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| ROLE
|--------------------------------------------------------------------------
*/

if (
    strtolower(
        (string)
        $currentUser['role']
    ) !== 'vendor'
) {

    header(
        'Location: ' .
        BASE_URL .
        'dashboard.php'
    );

    exit;
}


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


if (!function_exists('storeProfileMoney')) {

    function storeProfileMoney($value): string
    {
        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }
}


if (!function_exists('storeProfileStatusClass')) {

    function storeProfileStatusClass($status): string
    {
        return strtolower(
            preg_replace(
                '/[^a-zA-Z0-9]+/',
                '-',
                trim(
                    (string) $status
                )
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

$vendorStmt =
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
            v.postage_fee,
            v.allow_vendor_delivery,
            v.cod_enabled,
            v.vendor_delivery_fee,
            v.commission_rate,
            v.approval_status,
            v.created_at,
            v.updated_at,

            u.name,
            u.email,
            u.phone

        FROM vendors v

        INNER JOIN users u
            ON v.user_id =
               u.user_id

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


/*
|--------------------------------------------------------------------------
| CREATE BASIC VENDOR PROFILE IF MISSING
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    $defaultBusinessName =
        trim(
            (string)
            $currentUser['name']
        ) !== ''
            ? trim(
                (string)
                $currentUser['name']
            )
            : 'My Store';


    $createVendor =
        $db->prepare("
            INSERT INTO vendors
            (
                user_id,
                business_name,
                delivery_method,
                postage_fee,
                allow_vendor_delivery,
                cod_enabled,
                vendor_delivery_fee,
                commission_rate,
                approval_status
            )

            VALUES
            (
                ?,
                ?,
                'Both',
                0.00,
                0,
                0,
                0.00,
                5.00,
                'Pending'
            )
        ");


    $createVendor->execute([
        $userId,
        $defaultBusinessName
    ]);


    $vendorStmt->execute([
        $userId
    ]);


    $vendor =
        $vendorStmt->fetch(
            PDO::FETCH_ASSOC
        );
}


/*
|--------------------------------------------------------------------------
| CURRENT VALUES
|--------------------------------------------------------------------------
*/

$vendorId =
    (int) (
        $vendor['vendor_id']
        ?? 0
    );


$currentBusinessName =
    (string) (
        $vendor['business_name']
        ?? ''
    );


$currentBusinessLogo =
    (string) (
        $vendor['business_logo']
        ?? ''
    );


$currentBusinessDescription =
    (string) (
        $vendor['business_description']
        ?? ''
    );


$currentBusinessAddress =
    (string) (
        $vendor['business_address']
        ?? ''
    );


$currentCategory =
    (string) (
        $vendor['category']
        ?? ''
    );


$currentDeliveryMethod =
    (string) (
        $vendor['delivery_method']
        ?? 'Both'
    );


$currentPostageFee =
    (float) (
        $vendor['postage_fee']
        ?? 0
    );


$currentAllowVendorDelivery =
    (int) (
        $vendor['allow_vendor_delivery']
        ?? 0
    );


$currentCodEnabled =
    (int) (
        $vendor['cod_enabled']
        ?? 0
    );


$currentVendorDeliveryFee =
    (float) (
        $vendor['vendor_delivery_fee']
        ?? 0
    );


$currentCommissionRate =
    (float) (
        $vendor['commission_rate']
        ?? 5
    );


/*
|--------------------------------------------------------------------------
| SAFETY FOR OLD DATA
|--------------------------------------------------------------------------
|
| Commission minimum is 5%.
| If old vendor data contains less than 5, show 5.
|--------------------------------------------------------------------------
*/

if ($currentCommissionRate < 5) {

    $currentCommissionRate =
        5.00;
}


$currentApprovalStatus =
    (string) (
        $vendor['approval_status']
        ?? 'Pending'
    );


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

$successMessage = '';

$errorMessage = '';


if (
    isset(
        $_SESSION[
            'store_profile_success'
        ]
    )
) {

    $successMessage =
        (string)
        $_SESSION[
            'store_profile_success'
        ];


    unset(
        $_SESSION[
            'store_profile_success'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| SAVE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] ===
    'POST'
) {

    /*
    |--------------------------------------------------------------------------
    | BUSINESS
    |--------------------------------------------------------------------------
    */

    $businessName =
        trim(
            (string) (
                $_POST[
                    'business_name'
                ]
                ?? ''
            )
        );


    $businessDescription =
        trim(
            (string) (
                $_POST[
                    'business_description'
                ]
                ?? ''
            )
        );


    $businessAddress =
        trim(
            (string) (
                $_POST[
                    'business_address'
                ]
                ?? ''
            )
        );


    $category =
        trim(
            (string) (
                $_POST[
                    'category'
                ]
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | DELIVERY
    |--------------------------------------------------------------------------
    */

    $deliveryMethod =
        trim(
            (string) (
                $_POST[
                    'delivery_method'
                ]
                ?? 'Both'
            )
        );


    $postageFee =
        isset(
            $_POST[
                'postage_fee'
            ]
        )
            ? (float)
                $_POST[
                    'postage_fee'
                ]
            : 0.00;


    $allowVendorDelivery =
        isset(
            $_POST[
                'allow_vendor_delivery'
            ]
        )
            ? 1
            : 0;


    $codEnabled =
        isset(
            $_POST[
                'cod_enabled'
            ]
        )
            ? 1
            : 0;


    $vendorDeliveryFee =
        isset(
            $_POST[
                'vendor_delivery_fee'
            ]
        )
            ? (float)
                $_POST[
                    'vendor_delivery_fee'
                ]
            : 0.00;


    /*
    |--------------------------------------------------------------------------
    | COMMISSION
    |--------------------------------------------------------------------------
    |
    | Vendor chooses their own commission.
    |
    | Minimum: 5%
    | Maximum: 100%
    |--------------------------------------------------------------------------
    */

    $commissionInput =
        trim(
            (string) (
                $_POST[
                    'commission_rate'
                ]
                ?? ''
            )
        );


    $commissionRate =
        is_numeric(
            $commissionInput
        )
            ? (float)
                $commissionInput
            : 0.00;


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    $allowedDeliveryMethods = [
        'Pickup',
        'Postage',
        'Both'
    ];


    if ($businessName === '') {

        $errorMessage =
            'Business name is required.';


    } elseif ($category === '') {

        $errorMessage =
            'Business category is required.';


    } elseif (
        !in_array(
            $deliveryMethod,
            $allowedDeliveryMethods,
            true
        )
    ) {

        $errorMessage =
            'Invalid delivery method.';


    } elseif (
        in_array(
            $deliveryMethod,
            [
                'Pickup',
                'Both'
            ],
            true
        ) &&
        $businessAddress === ''
    ) {

        $errorMessage =
            'Business / pickup address is required when Pickup is enabled.';


    } elseif ($postageFee < 0) {

        $errorMessage =
            'Postage fee cannot be negative.';


    } elseif ($vendorDeliveryFee < 0) {

        $errorMessage =
            'Vendor delivery fee cannot be negative.';


    } elseif (
        $commissionInput === '' ||
        !is_numeric(
            $commissionInput
        )
    ) {

        $errorMessage =
            'Please enter a valid commission rate.';


    } elseif ($commissionRate < 5) {

        $errorMessage =
            'Commission rate must be at least 5%.';


    } elseif ($commissionRate > 100) {

        $errorMessage =
            'Commission rate cannot exceed 100%.';
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE DELIVERY
    |--------------------------------------------------------------------------
    */

    if (
        $deliveryMethod ===
        'Pickup'
    ) {

        $postageFee =
            0.00;
    }


    if (!$allowVendorDelivery) {

        $vendorDeliveryFee =
            0.00;

        $codEnabled =
            0;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE COMMISSION
    |--------------------------------------------------------------------------
    */

    $commissionRate =
        round(
            $commissionRate,
            2
        );


    /*
    |--------------------------------------------------------------------------
    | LOGO
    |--------------------------------------------------------------------------
    */

    $newLogo =
        $currentBusinessLogo;


    if (
        $errorMessage === '' &&
        isset(
            $_FILES[
                'business_logo'
            ]
        ) &&
        isset(
            $_FILES[
                'business_logo'
            ][
                'error'
            ]
        ) &&
        $_FILES[
            'business_logo'
        ][
            'error'
        ] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES[
                'business_logo'
            ][
                'error'
            ] !== UPLOAD_ERR_OK
        ) {

            $errorMessage =
                'Unable to upload business logo.';


        } else {

            $maxSize =
                5 * 1024 * 1024;


            if (
                (int)
                $_FILES[
                    'business_logo'
                ][
                    'size'
                ] >
                $maxSize
            ) {

                $errorMessage =
                    'Business logo must be 5MB or smaller.';


            } else {

                $tmpFile =
                    $_FILES[
                        'business_logo'
                    ][
                        'tmp_name'
                    ];


                $mimeType = '';


                if (
                    function_exists(
                        'finfo_open'
                    )
                ) {

                    $finfo =
                        finfo_open(
                            FILEINFO_MIME_TYPE
                        );


                    if ($finfo) {

                        $mimeType =
                            (string)
                            finfo_file(
                                $finfo,
                                $tmpFile
                            );


                        finfo_close(
                            $finfo
                        );
                    }
                }


                if ($mimeType === '') {

                    $imageInfo =
                        @getimagesize(
                            $tmpFile
                        );


                    $mimeType =
                        $imageInfo[
                            'mime'
                        ]
                        ?? '';
                }


                $allowedImages = [

                    'image/jpeg' =>
                        'jpg',

                    'image/png' =>
                        'png',

                    'image/webp' =>
                        'webp'
                ];


                if (
                    !isset(
                        $allowedImages[
                            $mimeType
                        ]
                    )
                ) {

                    $errorMessage =
                        'Logo must be JPG, PNG or WEBP.';


                } else {

                    $uploadDirectory =
                        __DIR__ .
                        '/../uploads/vendors/';


                    if (
                        !is_dir(
                            $uploadDirectory
                        )
                    ) {

                        @mkdir(
                            $uploadDirectory,
                            0775,
                            true
                        );
                    }


                    if (
                        !is_dir(
                            $uploadDirectory
                        ) ||
                        !is_writable(
                            $uploadDirectory
                        )
                    ) {

                        $errorMessage =
                            'Vendor logo folder is not writable.';


                    } else {

                        $extension =
                            $allowedImages[
                                $mimeType
                            ];


                        try {

                            $randomString =
                                bin2hex(
                                    random_bytes(4)
                                );

                        } catch (Throwable $e) {

                            $randomString =
                                uniqid();
                        }


                        $fileName =
                            'vendor_' .
                            $vendorId .
                            '_' .
                            time() .
                            '_' .
                            $randomString .
                            '.' .
                            $extension;


                        $destination =
                            $uploadDirectory .
                            $fileName;


                        if (
                            !move_uploaded_file(
                                $tmpFile,
                                $destination
                            )
                        ) {

                            $errorMessage =
                                'Failed to save business logo.';


                        } else {

                            $newLogo =
                                $fileName;
                        }
                    }
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DATABASE
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        try {

            $db->beginTransaction();


            $updateStmt =
                $db->prepare("
                    UPDATE vendors

                    SET

                        business_name = ?,
                        business_logo = ?,
                        business_description = ?,
                        business_address = ?,
                        category = ?,
                        delivery_method = ?,
                        postage_fee = ?,
                        allow_vendor_delivery = ?,
                        cod_enabled = ?,
                        vendor_delivery_fee = ?,
                        commission_rate = ?

                    WHERE vendor_id = ?

                    AND user_id = ?
                ");


            $updateStmt->execute([

                $businessName,

                $newLogo !== ''
                    ? basename(
                        $newLogo
                    )
                    : null,

                $businessDescription !== ''
                    ? $businessDescription
                    : null,

                $businessAddress !== ''
                    ? $businessAddress
                    : null,

                $category,

                $deliveryMethod,

                storeProfileMoney(
                    $postageFee
                ),

                $allowVendorDelivery,

                $codEnabled,

                storeProfileMoney(
                    $vendorDeliveryFee
                ),

                storeProfileMoney(
                    $commissionRate
                ),

                $vendorId,

                $userId
            ]);


            $db->commit();


            $_SESSION[
                'store_profile_success'
            ] =
                'Store profile updated successfully. Commission rate is now ' .
                storeProfileMoney(
                    $commissionRate
                ) .
                '%.';


            header(
                'Location: ' .
                BASE_URL .
                'seller/setup_profile.php'
            );


            exit;


        } catch (Throwable $exception) {

            if (
                $db->inTransaction()
            ) {

                $db->rollBack();
            }


            $errorMessage =
                'Unable to update store profile. ' .
                $exception->getMessage();
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


    $currentPostageFee =
        max(
            0,
            $postageFee
        );


    $currentAllowVendorDelivery =
        $allowVendorDelivery;


    $currentCodEnabled =
        $codEnabled;


    $currentVendorDeliveryFee =
        max(
            0,
            $vendorDeliveryFee
        );


    /*
    |--------------------------------------------------------------------------
    | KEEP COMMISSION VALUE
    |--------------------------------------------------------------------------
    */

    if (
        is_numeric(
            $commissionInput
        )
    ) {

        $currentCommissionRate =
            (float)
            $commissionInput;
    }


    $currentBusinessLogo =
        $newLogo;
}


/*
|--------------------------------------------------------------------------
| DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$ownerName =
    $vendor['name']
    ?? $currentUser['name']
    ?? 'Vendor';


$ownerEmail =
    $vendor['email']
    ?? $currentUser['email']
    ?? '';


$statusClass =
    storeProfileStatusClass(
        $currentApprovalStatus
    );


$currentLogoUrl = '';


if (
    trim(
        (string)
        $currentBusinessLogo
    ) !== ''
) {

    $logoFile =
        basename(
            $currentBusinessLogo
        );


    $currentLogoUrl =
        BASE_URL .
        'uploads/vendors/' .
        rawurlencode(
            $logoFile
        );
}


/*
|--------------------------------------------------------------------------
| USER INITIAL
|--------------------------------------------------------------------------
*/

$userInitial =
    strtoupper(
        substr(
            trim(
                (string)
                $ownerName
            ) !== ''
                ? trim(
                    (string)
                    $ownerName
                )
                : 'V',
            0,
            1
        )
    );


$pageTitle =
    'Store Profile | Seller | HochipoHub';

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

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>

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

/* =========================================================
   BASE
========================================================= */

* {
    box-sizing: border-box;
}


html,
body {

    margin: 0;

    min-height: 100%;

    padding: 0;
}


body.seller-dashboard-page {

    overflow-x: hidden;

    color: #14213d;

    background: #f5f8fc;

    font-family:
        Inter,
        Arial,
        sans-serif;
}


/* =========================================================
   MAIN
========================================================= */

.seller-store-main {

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
            circle at 96% 5%,
            rgba(
                37,
                99,
                235,
                .08
            ),
            transparent 22%
        ),

        #f5f8fc;
}


/* =========================================================
   TOPBAR
========================================================= */

.seller-store-topbar {

    height: 72px;

    padding:
        0 32px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background:
        rgba(
            255,
            255,
            255,
            .97
        );

    border-bottom:
        1px solid
        #e8edf5;
}


.seller-store-topbar-label {

    color: #8292ab;

    font-size: 11px;

    font-weight: 700;
}


.seller-store-topbar-user {

    display: flex;

    align-items: center;

    gap: 10px;
}


.seller-store-topbar-avatar {

    width: 39px;

    height: 39px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    border-radius: 50%;

    font-size: 12px;

    font-weight: 900;
}


.seller-store-topbar-avatar img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.seller-store-topbar-user strong {

    display: block;

    color: #14213d;

    font-size: 11px;

    font-weight: 800;
}


.seller-store-topbar-user small {

    display: block;

    margin-top: 2px;

    color: #94a3b8;

    font-size: 8px;
}


/* =========================================================
   CONTENT
========================================================= */

.seller-store-content {

    width: 100%;

    max-width: 1450px;

    margin:
        0 auto;

    padding:
        29px 32px
        65px;
}


/* =========================================================
   HEADING
========================================================= */

.seller-store-heading {

    margin-bottom: 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.seller-store-eyebrow {

    display: block;

    margin-bottom: 7px;

    color: #2563eb;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.4px;

    text-transform: uppercase;
}


.seller-store-heading h1 {

    margin: 0;

    color: #10213f;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:
        clamp(
            28px,
            3vw,
            35px
        );

    line-height: 1.15;

    letter-spacing: -1px;
}


.seller-store-heading p {

    margin:
        8px 0 0;

    color: #8492a8;

    font-size: 11px;
}


.seller-store-dashboard-link {

    min-height: 42px;

    padding:
        0 15px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #2c496d;

    background: #ffffff;

    border:
        1px solid
        #dfe7f2;

    border-radius: 13px;

    box-shadow:
        0 9px 24px
        rgba(
            32,
            60,
            104,
            .06
        );

    font-size: 9px;

    font-weight: 800;

    text-decoration: none;
}


/* =========================================================
   HERO
========================================================= */

.seller-store-hero {

    position: relative;

    overflow: hidden;

    min-height: 176px;

    margin-bottom: 22px;

    padding: 31px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 35px;

    color: #ffffff;

    background:

        linear-gradient(
            112deg,
            #103b82 0%,
            #2367ca 48%,
            #3488ee 100%
        );

    border-radius: 23px;

    box-shadow:
        0 18px 44px
        rgba(
            33,
            94,
            186,
            .17
        );
}


.seller-store-hero::before {

    content: "";

    position: absolute;

    width: 250px;

    height: 250px;

    right: -70px;

    top: -140px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .07
        );
}


.seller-store-hero::after {

    content: "";

    position: absolute;

    width: 185px;

    height: 185px;

    right: 175px;

    bottom: -135px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .045
        );
}


.seller-store-hero-copy {

    position: relative;

    z-index: 2;
}


.seller-store-hero-label {

    display: block;

    margin-bottom: 9px;

    color: #b9d7ff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.3px;

    text-transform: uppercase;
}


.seller-store-hero h2 {

    margin:
        0 0 9px;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:
        clamp(
            21px,
            3vw,
            29px
        );

    letter-spacing: -.7px;
}


.seller-store-hero p {

    max-width: 670px;

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size: 10px;

    line-height: 1.75;
}


.seller-store-hero-status {

    position: relative;

    z-index: 2;

    min-width: 140px;

    min-height: 50px;

    padding:
        0 17px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    color: #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .20
        );

    border-radius: 15px;

    backdrop-filter:
        blur(8px);

    font-size: 10px;

    font-weight: 850;
}


/* =========================================================
   ALERT
========================================================= */

.seller-store-alert {

    margin-bottom: 18px;

    padding:
        14px 17px;

    display: flex;

    align-items: center;

    gap: 9px;

    border-radius: 14px;

    font-size: 10px;

    font-weight: 750;
}


.seller-store-alert.success {

    color: #087443;

    background: #ecfdf3;

    border:
        1px solid
        #a7f3d0;
}


.seller-store-alert.error {

    color: #b42318;

    background: #fff2f1;

    border:
        1px solid
        #fecaca;
}


/* =========================================================
   LAYOUT
========================================================= */

.seller-store-layout {

    display: grid;

    grid-template-columns:
        minmax(
            0,
            1fr
        )
        320px;

    align-items: start;

    gap: 22px;
}


/* =========================================================
   FORM CARD
========================================================= */

.seller-store-form-card {

    overflow: hidden;

    background: #ffffff;

    border:
        1px solid
        #e1e8f2;

    border-radius: 22px;

    box-shadow:
        0 12px 34px
        rgba(
            28,
            59,
            103,
            .055
        );
}


.seller-store-form-header {

    padding:
        22px 24px;

    display: flex;

    align-items: center;

    gap: 12px;

    border-bottom:
        1px solid
        #edf1f6;
}


.seller-store-form-icon {

    width: 46px;

    height: 46px;

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
        0 10px 20px
        rgba(
            37,
            99,
            235,
            .18
        );

    font-size: 16px;
}


.seller-store-form-header h3 {

    margin:
        0 0 4px;

    color: #11213e;

    font-size: 16px;
}


.seller-store-form-header p {

    margin: 0;

    color: #8896aa;

    font-size: 9px;
}


/* =========================================================
   FORM SECTION
========================================================= */

.store-form-section {

    padding: 25px;

    border-bottom:
        1px solid
        #edf1f6;
}


.store-form-section:last-of-type {

    border-bottom: none;
}


.store-section-title {

    margin-bottom: 21px;

    display: flex;

    align-items: center;

    gap: 11px;
}


.store-section-title-icon {

    width: 39px;

    height: 39px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #edf5ff;

    border:
        1px solid
        #dceaff;

    border-radius: 11px;

    font-size: 14px;
}


.store-section-title h4 {

    margin:
        0 0 3px;

    color: #18365e;

    font-size: 14px;
}


.store-section-title p {

    margin: 0;

    color: #8c9aaf;

    font-size: 8px;
}


/* =========================================================
   FIELDS
========================================================= */

.seller-store-field-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 18px;
}


.seller-store-field.full {

    grid-column:
        1 / -1;
}


.seller-store-field label {

    margin-bottom: 7px;

    display: flex;

    align-items: center;

    gap: 6px;

    color: #2d405e;

    font-size: 9px;

    font-weight: 800;
}


.seller-store-field label i {

    color: #2563eb;
}


.seller-store-required {

    color: #ef4444;
}


.seller-store-field input,
.seller-store-field select,
.seller-store-field textarea {

    width: 100%;

    outline: none;

    color: #1a3559;

    background: #fbfdff;

    border:
        1px solid
        #dce5f0;

    border-radius: 12px;

    font-family: inherit;

    font-size: 10px;

    transition:
        .18s ease;
}


.seller-store-field input,
.seller-store-field select {

    height: 45px;

    padding:
        0 13px;
}


.seller-store-field textarea {

    min-height: 115px;

    padding: 13px;

    line-height: 1.65;

    resize: vertical;
}


.seller-store-field input:focus,
.seller-store-field select:focus,
.seller-store-field textarea:focus {

    border-color: #4d8cf8;

    background: #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(
            37,
            99,
            235,
            .07
        );
}


.seller-store-field small {

    display: block;

    margin-top: 6px;

    color: #95a2b5;

    font-size: 8px;

    line-height: 1.55;
}


/* =========================================================
   FILE
========================================================= */

.store-file-input {

    padding:
        10px !important;

    height:
        auto !important;
}


/* =========================================================
   MONEY
========================================================= */

.store-money {

    position: relative;
}


.store-money span {

    position: absolute;

    left: 13px;

    top: 50%;

    z-index: 2;

    transform:
        translateY(
            -50%
        );

    color: #65768e;

    font-size: 9px;

    font-weight: 850;

    pointer-events: none;
}


.store-money input {

    padding-left: 43px;
}


/* =========================================================
   PERCENT FIELD
========================================================= */

.store-percent {

    position: relative;
}


.store-percent input {

    padding-right:
        42px;
}


.store-percent span {

    position: absolute;

    top: 50%;

    right: 14px;

    transform:
        translateY(
            -50%
        );

    color: #2563eb;

    font-size: 10px;

    font-weight: 900;

    pointer-events: none;
}


/* =========================================================
   DELIVERY CARDS
========================================================= */

.store-delivery-grid {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap: 11px;
}


.store-delivery-choice {

    position: relative;

    cursor: pointer;
}


.store-delivery-choice input {

    position: absolute;

    opacity: 0;

    pointer-events: none;
}


.store-delivery-card {

    min-height: 115px;

    height: 100%;

    padding: 16px;

    background: #fbfdff;

    border:
        1.5px solid
        #dfe7f1;

    border-radius: 15px;

    transition:
        .18s ease;
}


.store-delivery-card i {

    margin-bottom: 11px;

    display: block;

    color: #3b82f6;

    font-size: 18px;
}


.store-delivery-card strong {

    display: block;

    margin-bottom: 5px;

    color: #203c64;

    font-size: 10px;
}


.store-delivery-card small {

    color: #8998ad;

    font-size: 8px;

    line-height: 1.5;
}


.store-delivery-choice
input:checked +
.store-delivery-card {

    border-color: #3b82f6;

    background: #f0f6ff;

    box-shadow:
        0 0 0 3px
        rgba(
            59,
            130,
            246,
            .07
        );
}


/* =========================================================
   CONDITIONAL
========================================================= */

.store-conditional {

    margin-top: 16px;

    padding: 18px;

    background: #f8fbff;

    border:
        1px solid
        #e0e8f3;

    border-radius: 14px;
}


/* =========================================================
   TOGGLE
========================================================= */

.store-toggle-row {

    padding: 16px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background: #fbfcfe;

    border:
        1px solid
        #e3e9f2;

    border-radius: 14px;
}


.store-toggle-copy strong {

    display: block;

    margin-bottom: 4px;

    color: #213e67;

    font-size: 10px;
}


.store-toggle-copy small {

    color: #8c9bae;

    font-size: 8px;

    line-height: 1.5;
}


.store-switch {

    position: relative;

    width: 46px;

    height: 25px;

    flex-shrink: 0;
}


.store-switch input {

    position: absolute;

    opacity: 0;
}


.store-switch-slider {

    position: absolute;

    inset: 0;

    cursor: pointer;

    background: #cbd4e1;

    border-radius: 999px;

    transition: .2s;
}


.store-switch-slider::before {

    content: "";

    position: absolute;

    width: 19px;

    height: 19px;

    left: 3px;

    top: 3px;

    background: #ffffff;

    border-radius: 50%;

    box-shadow:
        0 2px 6px
        rgba(
            0,
            0,
            0,
            .17
        );

    transition: .2s;
}


.store-switch
input:checked +
.store-switch-slider {

    background: #2563eb;
}


.store-switch
input:checked +
.store-switch-slider::before {

    transform:
        translateX(
            21px
        );
}


/* =========================================================
   COMMISSION
========================================================= */

.store-commission-box {

    padding: 18px;

    background:

        linear-gradient(
            135deg,
            #f3f8ff,
            #eef5ff
        );

    border:
        1px solid
        #d9e8fb;

    border-radius: 14px;
}


.store-commission-notice {

    margin-top: 12px;

    padding:
        11px 13px;

    display: flex;

    gap: 8px;

    align-items: flex-start;

    color: #315987;

    background:
        rgba(
            255,
            255,
            255,
            .65
        );

    border:
        1px solid
        #dae8fa;

    border-radius: 10px;

    font-size: 8px;

    line-height: 1.55;
}


.store-commission-notice i {

    margin-top: 2px;

    color: #2563eb;
}


/* =========================================================
   SAVE
========================================================= */

.seller-store-save-row {

    padding:
        21px 25px;

    display: flex;

    justify-content: flex-end;

    background: #fafcff;

    border-top:
        1px solid
        #edf1f6;
}


.seller-store-save {

    min-height: 45px;

    padding:
        0 19px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    border: none;

    border-radius: 12px;

    box-shadow:
        0 9px 21px
        rgba(
            37,
            99,
            235,
            .20
        );

    font-family: inherit;

    font-size: 9px;

    font-weight: 850;

    cursor: pointer;
}


/* =========================================================
   RIGHT SIDE
========================================================= */

.seller-store-side {

    display: grid;

    gap: 17px;
}


.seller-store-summary {

    padding: 22px;

    background: #ffffff;

    border:
        1px solid
        #e0e7f1;

    border-radius: 21px;

    box-shadow:
        0 12px 31px
        rgba(
            29,
            58,
            100,
            .055
        );
}


.seller-store-logo {

    width: 103px;

    height: 103px;

    margin:
        0 auto 15px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #4169e1;

    background:

        linear-gradient(
            135deg,
            #eef4ff,
            #f4efff
        );

    border:
        1px solid
        #e0e5ef;

    border-radius: 22px;

    font-size: 34px;
}


.seller-store-logo img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.seller-store-summary h3 {

    margin:
        0 0 4px;

    text-align: center;

    color: #152d53;

    font-size: 17px;
}


.seller-store-category {

    margin:
        0 0 18px;

    text-align: center;

    color: #8b98ab;

    font-size: 9px;
}


.seller-store-info-row {

    padding:
        11px 0;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    border-top:
        1px solid
        #edf1f6;

    font-size: 9px;
}


.seller-store-info-row span {

    color: #7f8da3;
}


.seller-store-info-row strong {

    color: #213b62;

    text-align: right;
}


.seller-store-status {

    padding:
        5px 8px;

    display: inline-flex;

    align-items: center;

    border-radius: 999px;

    font-size: 8px;

    font-weight: 850;
}


.seller-store-status.approved {

    color: #047857;

    background: #e9f9ef;
}


.seller-store-status.pending {

    color: #a15c00;

    background: #fff4d8;
}


.seller-store-status.rejected,
.seller-store-status.suspended {

    color: #b42318;

    background: #ffefed;
}


/* =========================================================
   GUIDE
========================================================= */

.seller-store-guide {

    padding: 21px;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #123b7a,
            #2866c0
        );

    border-radius: 20px;

    box-shadow:
        0 14px 35px
        rgba(
            25,
            76,
            154,
            .14
        );
}


.seller-store-guide-icon {

    width: 42px;

    height: 42px;

    margin-bottom: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    border-radius: 12px;

    font-size: 16px;
}


.seller-store-guide h3 {

    margin:
        0 0 7px;

    font-size: 13px;
}


.seller-store-guide p {

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            .76
        );

    font-size: 9px;

    line-height: 1.7;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1100px
) {

    .seller-store-layout {

        grid-template-columns:
            1fr;
    }


    .seller-store-side {

        grid-template-columns:
            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );
    }
}


@media (
    max-width: 850px
) {

    .seller-store-main {

        width: 100%;

        margin-left: 0;
    }


    .seller-store-topbar {

        padding-left: 70px;
    }


    .seller-store-content {

        padding:
            24px
            20px
            50px;
    }
}


@media (
    max-width: 650px
) {

    .seller-store-topbar-user
    > div:last-child {

        display: none;
    }


    .seller-store-content {

        padding:
            20px
            14px
            45px;
    }


    .seller-store-heading {

        align-items: flex-start;

        flex-direction: column;
    }


    .seller-store-dashboard-link {

        width: 100%;
    }


    .seller-store-hero {

        min-height: auto;

        padding: 24px;

        align-items: flex-start;

        flex-direction: column;
    }


    .seller-store-hero-status {

        width: 100%;
    }


    .seller-store-field-grid,
    .store-delivery-grid,
    .seller-store-side {

        grid-template-columns:
            1fr;
    }


    .store-form-section {

        padding: 20px;
    }


    .seller-store-save-row {

        padding:
            18px 20px;
    }


    .seller-store-save {

        width: 100%;
    }
}

</style>

</head>


<body
    class="
        seller-dashboard-page
        seller-store-profile-page
    "
>


<?php

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<main class="seller-store-main">


    <!-- =========================================================
         TOPBAR
    ========================================================== -->

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
                        alt="Store"
                    >


                <?php else: ?>


                    <?= storeProfileEscape(
                        $userInitial
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



    <!-- =========================================================
         CONTENT
    ========================================================== -->

    <div class="seller-store-content">


        <!-- =====================================================
             HEADING
        ====================================================== -->

        <section class="seller-store-heading">


            <div>


                <span class="seller-store-eyebrow">

                    STORE MANAGEMENT

                </span>


                <h1>

                    Store Profile

                </h1>


                <p>

                    Manage your public store information,
                    delivery settings and commission rate.

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



        <!-- =====================================================
             HERO
        ====================================================== -->

        <section class="seller-store-hero">


            <div class="seller-store-hero-copy">


                <span class="seller-store-hero-label">

                    SELLER WORKSPACE

                </span>


                <h2>

                    Build a store customers trust.

                </h2>


                <p>

                    Keep your business details,
                    pickup location, postage fee,
                    vendor delivery settings and
                    commission rate accurate.

                </p>


            </div>


            <div class="seller-store-hero-status">

                <i class="fa-solid fa-circle-check"></i>

                <?= storeProfileEscape(
                    $currentApprovalStatus
                ) ?>

            </div>


        </section>



        <!-- =====================================================
             ALERT
        ====================================================== -->

        <?php if (
            $successMessage !== ''
        ): ?>


            <div class="
                seller-store-alert
                success
            ">

                <i class="fa-solid fa-circle-check"></i>

                <span>

                    <?= storeProfileEscape(
                        $successMessage
                    ) ?>

                </span>

            </div>


        <?php endif; ?>


        <?php if (
            $errorMessage !== ''
        ): ?>


            <div class="
                seller-store-alert
                error
            ">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>

                    <?= storeProfileEscape(
                        $errorMessage
                    ) ?>

                </span>

            </div>


        <?php endif; ?>



        <!-- =====================================================
             LAYOUT
        ====================================================== -->

        <div class="seller-store-layout">


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                enctype="multipart/form-data"
                class="seller-store-form-card"
            >


                <div class="seller-store-form-header">


                    <div class="seller-store-form-icon">

                        <i class="fa-solid fa-store"></i>

                    </div>


                    <div>

                        <h3>

                            Store Settings

                        </h3>

                        <p>

                            Update your business,
                            delivery and commission settings.

                        </p>

                    </div>


                </div>



                <!-- =============================================
                     BUSINESS INFORMATION
                ============================================== -->

                <section class="store-form-section">


                    <div class="store-section-title">


                        <div class="store-section-title-icon">

                            <i class="fa-solid fa-building"></i>

                        </div>


                        <div>

                            <h4>
                                Business Information
                            </h4>

                            <p>
                                Public information shown to customers.
                            </p>

                        </div>


                    </div>


                    <div class="seller-store-field-grid">


                        <div class="seller-store-field">


                            <label for="business_name">

                                <i class="fa-solid fa-store"></i>

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
                                required
                            >


                        </div>



                        <div class="seller-store-field">


                            <label for="category">

                                <i class="fa-solid fa-tag"></i>

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
                                placeholder="Example: Food & Beverage"
                                required
                            >


                        </div>



                        <div class="
                            seller-store-field
                            full
                        ">


                            <label for="business_description">

                                <i class="fa-solid fa-align-left"></i>

                                Business Description

                            </label>


                            <textarea
                                id="business_description"
                                name="business_description"
                                maxlength="2000"
                                placeholder="Tell customers about your store..."
                            ><?= storeProfileEscape(
                                $currentBusinessDescription
                            ) ?></textarea>


                        </div>



                        <div class="
                            seller-store-field
                            full
                        ">


                            <label for="business_address">

                                <i class="fa-solid fa-location-dot"></i>

                                Business / Pickup Address

                            </label>


                            <textarea
                                id="business_address"
                                name="business_address"
                                maxlength="1000"
                                placeholder="Enter the complete store address..."
                            ><?= storeProfileEscape(
                                $currentBusinessAddress
                            ) ?></textarea>


                            <small>

                                Required when Pickup
                                is available.

                            </small>


                        </div>



                        <div class="
                            seller-store-field
                            full
                        ">


                            <label for="business_logo">

                                <i class="fa-solid fa-image"></i>

                                Business Logo

                            </label>


                            <input
                                type="file"
                                id="business_logo"
                                name="business_logo"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                class="store-file-input"
                            >


                            <small>

                                JPG, PNG or WEBP.
                                Maximum file size 5MB.

                            </small>


                        </div>


                    </div>


                </section>



                <!-- =============================================
                     DELIVERY SETTINGS
                ============================================== -->

                <section class="store-form-section">


                    <div class="store-section-title">


                        <div class="store-section-title-icon">

                            <i class="fa-solid fa-truck"></i>

                        </div>


                        <div>

                            <h4>
                                Delivery Settings
                            </h4>

                            <p>
                                Choose how customers receive orders.
                            </p>

                        </div>


                    </div>



                    <div class="store-delivery-grid">


                        <label class="store-delivery-choice">


                            <input
                                type="radio"
                                name="delivery_method"
                                value="Pickup"
                                <?= $currentDeliveryMethod ===
                                    'Pickup'
                                        ? 'checked'
                                        : '' ?>
                            >


                            <div class="store-delivery-card">

                                <i class="fa-solid fa-store"></i>

                                <strong>
                                    Pickup
                                </strong>

                                <small>

                                    Customers collect
                                    orders from your store.

                                </small>

                            </div>


                        </label>



                        <label class="store-delivery-choice">


                            <input
                                type="radio"
                                name="delivery_method"
                                value="Postage"
                                <?= $currentDeliveryMethod ===
                                    'Postage'
                                        ? 'checked'
                                        : '' ?>
                            >


                            <div class="store-delivery-card">

                                <i class="fa-solid fa-box"></i>

                                <strong>
                                    Postage
                                </strong>

                                <small>

                                    Ship orders using
                                    courier services.

                                </small>

                            </div>


                        </label>



                        <label class="store-delivery-choice">


                            <input
                                type="radio"
                                name="delivery_method"
                                value="Both"
                                <?= $currentDeliveryMethod ===
                                    'Both'
                                        ? 'checked'
                                        : '' ?>
                            >


                            <div class="store-delivery-card">

                                <i class="fa-solid fa-arrows-left-right"></i>

                                <strong>
                                    Both
                                </strong>

                                <small>

                                    Allow Pickup
                                    and Postage.

                                </small>

                            </div>


                        </label>


                    </div>



                    <!-- POSTAGE -->

                    <div
                        class="store-conditional"
                        id="postageSettings"
                    >


                        <div class="seller-store-field">


                            <label for="postage_fee">

                                <i class="fa-solid fa-money-bill"></i>

                                Postage Fee

                            </label>


                            <div class="store-money">

                                <span>
                                    RM
                                </span>

                                <input
                                    type="number"
                                    id="postage_fee"
                                    name="postage_fee"
                                    min="0"
                                    step="0.01"
                                    value="<?= storeProfileEscape(
                                        storeProfileMoney(
                                            $currentPostageFee
                                        )
                                    ) ?>"
                                >

                            </div>


                        </div>


                    </div>



                    <!-- VENDOR DELIVERY -->

                    <div
                        class="store-conditional"
                        style="margin-top:16px;"
                    >


                        <div class="store-toggle-row">


                            <div class="store-toggle-copy">

                                <strong>

                                    Enable Vendor Delivery

                                </strong>

                                <small>

                                    Deliver orders directly
                                    to your customers yourself.

                                </small>

                            </div>


                            <label class="store-switch">

                                <input
                                    type="checkbox"
                                    id="allow_vendor_delivery"
                                    name="allow_vendor_delivery"
                                    value="1"
                                    <?= $currentAllowVendorDelivery
                                        ? 'checked'
                                        : '' ?>
                                >

                                <span class="store-switch-slider"></span>

                            </label>


                        </div>



                        <div
                            id="vendorDeliverySettings"
                            style="margin-top:15px;"
                        >


                            <div class="seller-store-field-grid">


                                <div class="seller-store-field">


                                    <label for="vendor_delivery_fee">

                                        <i class="fa-solid fa-motorcycle"></i>

                                        Vendor Delivery Fee

                                    </label>


                                    <div class="store-money">

                                        <span>
                                            RM
                                        </span>

                                        <input
                                            type="number"
                                            id="vendor_delivery_fee"
                                            name="vendor_delivery_fee"
                                            min="0"
                                            step="0.01"
                                            value="<?= storeProfileEscape(
                                                storeProfileMoney(
                                                    $currentVendorDeliveryFee
                                                )
                                            ) ?>"
                                        >

                                    </div>


                                </div>



                                <div class="seller-store-field">


                                    <label>

                                        <i class="fa-solid fa-money-bill-wave"></i>

                                        Cash on Delivery

                                    </label>


                                    <div class="store-toggle-row">


                                        <div class="store-toggle-copy">

                                            <strong>
                                                Allow COD
                                            </strong>

                                            <small>

                                                Customer can pay
                                                cash during delivery.

                                            </small>

                                        </div>


                                        <label class="store-switch">

                                            <input
                                                type="checkbox"
                                                id="cod_enabled"
                                                name="cod_enabled"
                                                value="1"
                                                <?= $currentCodEnabled
                                                    ? 'checked'
                                                    : '' ?>
                                            >

                                            <span class="store-switch-slider"></span>

                                        </label>


                                    </div>


                                </div>


                            </div>


                        </div>


                    </div>


                </section>



                <!-- =============================================
                     COMMISSION
                ============================================== -->

                <section class="store-form-section">


                    <div class="store-section-title">


                        <div class="store-section-title-icon">

                            <i class="fa-solid fa-percent"></i>

                        </div>


                        <div>

                            <h4>

                                Commission Rate

                            </h4>

                            <p>

                                Set your preferred
                                commission percentage.

                            </p>

                        </div>


                    </div>



                    <div class="store-commission-box">


                        <div class="seller-store-field">


                            <label for="commission_rate">

                                <i class="fa-solid fa-percent"></i>

                                Store Commission Rate

                                <span class="seller-store-required">
                                    *
                                </span>

                            </label>


                            <div class="store-percent">


                                <input
                                    type="number"
                                    id="commission_rate"
                                    name="commission_rate"
                                    min="5"
                                    max="100"
                                    step="0.01"
                                    value="<?= storeProfileEscape(
                                        storeProfileMoney(
                                            $currentCommissionRate
                                        )
                                    ) ?>"
                                    required
                                >


                                <span>
                                    %
                                </span>


                            </div>


                            <small>

                                Minimum commission rate
                                is <strong>5%</strong>.
                                You may choose any rate
                                from 5% up to 100%.

                            </small>


                        </div>



                        <div class="store-commission-notice">

                            <i class="fa-solid fa-circle-info"></i>

                            <div>

                                You control your store's
                                commission rate. HochipoHub
                                requires a minimum rate of
                                <strong>5%</strong>. A rate
                                below 5% will not be saved.

                            </div>

                        </div>


                    </div>


                </section>



                <!-- =============================================
                     SAVE
                ============================================== -->

                <div class="seller-store-save-row">


                    <button
                        type="submit"
                        class="seller-store-save"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Save Store Profile

                    </button>


                </div>


            </form>



            <!-- =================================================
                 RIGHT SIDE
            ================================================== -->

            <aside class="seller-store-side">


                <!-- STORE SUMMARY -->

                <section class="seller-store-summary">


                    <div class="seller-store-logo">


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
                            >


                        <?php else: ?>


                            <i class="fa-solid fa-store"></i>


                        <?php endif; ?>


                    </div>


                    <h3>

                        <?= storeProfileEscape(
                            $currentBusinessName !== ''
                                ? $currentBusinessName
                                : 'My Store'
                        ) ?>

                    </h3>


                    <p class="seller-store-category">

                        <?= storeProfileEscape(
                            $currentCategory !== ''
                                ? $currentCategory
                                : 'Store category'
                        ) ?>

                    </p>



                    <div class="seller-store-info-row">

                        <span>
                            Owner
                        </span>

                        <strong>

                            <?= storeProfileEscape(
                                $ownerName
                            ) ?>

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            Email
                        </span>

                        <strong>

                            <?= storeProfileEscape(
                                $ownerEmail !== ''
                                    ? $ownerEmail
                                    : '-'
                            ) ?>

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            Status
                        </span>

                        <strong>

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

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            Delivery
                        </span>

                        <strong>

                            <?= storeProfileEscape(
                                $currentDeliveryMethod
                            ) ?>

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            Postage
                        </span>

                        <strong>

                            RM
                            <?= storeProfileEscape(
                                storeProfileMoney(
                                    $currentPostageFee
                                )
                            ) ?>

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            Vendor Delivery
                        </span>

                        <strong>

                            <?= $currentAllowVendorDelivery
                                ? 'Enabled'
                                : 'Disabled' ?>

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            COD
                        </span>

                        <strong>

                            <?= $currentCodEnabled
                                ? 'Enabled'
                                : 'Disabled' ?>

                        </strong>

                    </div>



                    <div class="seller-store-info-row">

                        <span>
                            Commission
                        </span>

                        <strong>

                            <?= storeProfileEscape(
                                storeProfileMoney(
                                    $currentCommissionRate
                                )
                            ) ?>%

                        </strong>

                    </div>


                </section>



                <!-- GUIDE -->

                <section class="seller-store-guide">


                    <div class="seller-store-guide-icon">

                        <i class="fa-solid fa-lightbulb"></i>

                    </div>


                    <h3>

                        Store Profile Tips

                    </h3>


                    <p>

                        Keep your store information,
                        pickup address and delivery fees
                        accurate. You may also set your
                        preferred commission rate, but
                        HochipoHub requires at least 5%.

                    </p>


                </section>


            </aside>


        </div>


    </div>


</main>


<script>

/*
|--------------------------------------------------------------------------
| DELIVERY UI
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const deliveryRadios =
            document.querySelectorAll(
                'input[name="delivery_method"]'
            );

        const postageSettings =
            document.getElementById(
                'postageSettings'
            );

        const postageFee =
            document.getElementById(
                'postage_fee'
            );


        const vendorDeliveryToggle =
            document.getElementById(
                'allow_vendor_delivery'
            );

        const vendorDeliverySettings =
            document.getElementById(
                'vendorDeliverySettings'
            );

        const vendorDeliveryFee =
            document.getElementById(
                'vendor_delivery_fee'
            );

        const codEnabled =
            document.getElementById(
                'cod_enabled'
            );


        const commissionRate =
            document.getElementById(
                'commission_rate'
            );


        /*
        |--------------------------------------------------------------------------
        | POSTAGE
        |--------------------------------------------------------------------------
        */

        function updatePostageSettings()
        {
            const selected =
                document.querySelector(
                    'input[name="delivery_method"]:checked'
                );


            const method =
                selected
                    ? selected.value
                    : 'Both';


            const showPostage =
                method === 'Postage' ||
                method === 'Both';


            if (postageSettings) {

                postageSettings.style.display =
                    showPostage
                        ? 'block'
                        : 'none';
            }


            if (postageFee) {

                postageFee.disabled =
                    !showPostage;
            }
        }


        deliveryRadios.forEach(
            function (radio) {

                radio.addEventListener(
                    'change',
                    updatePostageSettings
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | VENDOR DELIVERY
        |--------------------------------------------------------------------------
        */

        function updateVendorDeliverySettings()
        {
            const enabled =
                vendorDeliveryToggle &&
                vendorDeliveryToggle.checked;


            if (vendorDeliverySettings) {

                vendorDeliverySettings.style.display =
                    enabled
                        ? 'block'
                        : 'none';
            }


            if (vendorDeliveryFee) {

                vendorDeliveryFee.disabled =
                    !enabled;
            }


            if (codEnabled) {

                codEnabled.disabled =
                    !enabled;


                if (!enabled) {

                    codEnabled.checked =
                        false;
                }
            }
        }


        if (vendorDeliveryToggle) {

            vendorDeliveryToggle.addEventListener(
                'change',
                updateVendorDeliverySettings
            );
        }


        /*
        |--------------------------------------------------------------------------
        | COMMISSION CLIENT VALIDATION
        |--------------------------------------------------------------------------
        */

        if (commissionRate) {

            commissionRate.addEventListener(
                'input',
                function () {

                    const value =
                        parseFloat(
                            commissionRate.value
                        );


                    if (
                        commissionRate.value !== '' &&
                        !Number.isNaN(value) &&
                        value < 5
                    ) {

                        commissionRate.setCustomValidity(
                            'Commission rate must be at least 5%.'
                        );

                    } else if (
                        !Number.isNaN(value) &&
                        value > 100
                    ) {

                        commissionRate.setCustomValidity(
                            'Commission rate cannot exceed 100%.'
                        );

                    } else {

                        commissionRate.setCustomValidity(
                            ''
                        );
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | INITIAL
        |--------------------------------------------------------------------------
        */

        updatePostageSettings();

        updateVendorDeliverySettings();
    }
);

</script>


</body>

</html>