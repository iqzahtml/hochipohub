<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM PRODUCT DETAILS
|--------------------------------------------------------------------------
| File: product_details.php
|
| Features:
| - Product information
| - Vendor information
| - Cart / Wishlist
| - Chat with seller
| - Premium review summary
| - Rating breakdown 5★ to 1★
| - Verified Purchase reviews
| - Review title
| - Review image
| - Helpful count display
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('pdEscape')) {

    function pdEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('pdProductImage')) {

    function pdProductImage($image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return 'image/logo.jpg';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
        ) {
            return $image;
        }

        return
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('pdVendorImage')) {

    function pdVendorImage($image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
        ) {
            return $image;
        }

        return
            'uploads/vendors/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('pdReviewImage')) {

    function pdReviewImage($image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
        ) {
            return $image;
        }

        return
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : (int) ($_GET['product_id'] ?? 0);


if ($productId <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        'product.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT

            p.*,

            v.vendor_id,
            v.user_id AS vendor_user_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.business_address,
            v.delivery_method,
            v.postage_fee,
            v.allow_vendor_delivery,
            v.cod_enabled,
            v.vendor_delivery_fee,

            c.category_id,
            c.category_name

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id =
               v.vendor_id

        INNER JOIN categories c
            ON p.category_id =
               c.category_id

        WHERE p.product_id = ?

        LIMIT 1
    ");


    $stmt->execute([
        $productId
    ]);


    $product =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $product = false;
}


if (!$product) {

    header(
        'Location: ' .
        BASE_URL .
        'product.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| IMAGES
|--------------------------------------------------------------------------
*/

$productImage =
    pdProductImage(
        $product['image']
        ?? ''
    );


$vendorImage =
    pdVendorImage(
        $product['business_logo']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT

            COUNT(*) AS review_count,

            COALESCE(
                AVG(rating),
                0
            ) AS average_rating,

            SUM(
                CASE
                    WHEN rating = 5
                    THEN 1
                    ELSE 0
                END
            ) AS rating_5,

            SUM(
                CASE
                    WHEN rating = 4
                    THEN 1
                    ELSE 0
                END
            ) AS rating_4,

            SUM(
                CASE
                    WHEN rating = 3
                    THEN 1
                    ELSE 0
                END
            ) AS rating_3,

            SUM(
                CASE
                    WHEN rating = 2
                    THEN 1
                    ELSE 0
                END
            ) AS rating_2,

            SUM(
                CASE
                    WHEN rating = 1
                    THEN 1
                    ELSE 0
                END
            ) AS rating_1

        FROM reviews

        WHERE product_id = ?
          AND status = 'Visible'
    ");


    $stmt->execute([
        $productId
    ]);


    $reviewSummary =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $reviewSummary = [
        'review_count' => 0,
        'average_rating' => 0,
        'rating_5' => 0,
        'rating_4' => 0,
        'rating_3' => 0,
        'rating_2' => 0,
        'rating_1' => 0
    ];
}


/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT

            r.review_id,
            r.customer_id,
            r.product_id,
            r.order_id,
            r.order_detail_id,
            r.rating,
            r.review_title,
            r.review,
            r.image,
            r.helpful_count,
            r.review_date,

            u.name AS customer_name,
            u.profile_image,

            o.order_date

        FROM reviews r

        INNER JOIN users u
            ON r.customer_id =
               u.user_id

        LEFT JOIN orders o
            ON r.order_id =
               o.order_id

        WHERE r.product_id = ?
          AND r.status = 'Visible'

        ORDER BY
            r.review_date DESC
    ");


    $stmt->execute([
        $productId
    ]);


    $reviews =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $reviews = [];
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


$currentRole =
    strtolower(
        trim(
            (string) (
                $_SESSION['role']
                ??
                $_SESSION['user_role']
                ??
                ''
            )
        )
    );


/*
|--------------------------------------------------------------------------
| CART / WISHLIST
|--------------------------------------------------------------------------
*/

$inCart = false;
$inWishlist = false;
$cartQuantity = 0;


if (
    $userId > 0 &&
    $currentRole === 'customer'
) {

    /*
    |--------------------------------------------------------------------------
    | CART
    |--------------------------------------------------------------------------
    */

    try {

        $stmt = $db->prepare("
            SELECT quantity

            FROM cart

            WHERE customer_id = ?
              AND product_id = ?

            LIMIT 1
        ");


        $stmt->execute([
            $userId,
            $productId
        ]);


        $cartRow =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($cartRow) {

            $inCart = true;

            $cartQuantity =
                (int)
                $cartRow['quantity'];
        }

    } catch (Throwable $e) {

        $inCart = false;
        $cartQuantity = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | WISHLIST
    |--------------------------------------------------------------------------
    */

    try {

        $stmt = $db->prepare("
            SELECT wishlist_id

            FROM wishlist

            WHERE user_id = ?
              AND product_id = ?

            LIMIT 1
        ");


        $stmt->execute([
            $userId,
            $productId
        ]);


        $inWishlist =
            (bool)
            $stmt->fetch();

    } catch (Throwable $e) {

        $inWishlist = false;
    }
}


/*
|--------------------------------------------------------------------------
| REVIEWABLE PURCHASE FOR CURRENT CUSTOMER
|--------------------------------------------------------------------------
|
| If customer has completed purchase that has not been reviewed,
| show Write Review button.
|--------------------------------------------------------------------------
*/

$reviewablePurchase = null;


if (
    $userId > 0 &&
    $currentRole === 'customer'
) {

    try {

        $stmt = $db->prepare("
            SELECT

                od.order_detail_id,
                od.order_id,

                vo.vendor_status,

                r.review_id

            FROM order_details od

            INNER JOIN orders o
                ON od.order_id =
                   o.order_id

            INNER JOIN products p
                ON od.product_id =
                   p.product_id

            INNER JOIN vendor_orders vo
                ON vo.order_id =
                   od.order_id
               AND vo.vendor_id =
                   p.vendor_id

            LEFT JOIN reviews r
                ON r.order_detail_id =
                   od.order_detail_id

            WHERE o.customer_id = ?
              AND od.product_id = ?
              AND vo.vendor_status = 'Completed'
              AND r.review_id IS NULL

            ORDER BY
                od.order_detail_id DESC

            LIMIT 1
        ");


        $stmt->execute([
            $userId,
            $productId
        ]);


        $reviewablePurchase =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

    } catch (Throwable $e) {

        $reviewablePurchase =
            null;
    }
}


/*
|--------------------------------------------------------------------------
| SIDEBAR COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = 0;
$wishlistCount = 0;


if (
    $userId > 0 &&
    $currentRole === 'customer'
) {

    try {

        $stmt = $db->prepare("
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                )

            FROM cart

            WHERE customer_id = ?
        ");


        $stmt->execute([
            $userId
        ]);


        $cartCount =
            (int)
            $stmt->fetchColumn();

    } catch (Throwable $e) {

        $cartCount = 0;
    }


    try {

        $stmt = $db->prepare("
            SELECT COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");


        $stmt->execute([
            $userId
        ]);


        $wishlistCount =
            (int)
            $stmt->fetchColumn();

    } catch (Throwable $e) {

        $wishlistCount = 0;
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT DATA
|--------------------------------------------------------------------------
*/

$stockQuantity =
    (int) (
        $product['stock_quantity']
        ?? 0
    );


$price =
    (float) (
        $product['price']
        ?? 0
    );


$averageRating =
    (float) (
        $reviewSummary['average_rating']
        ?? 0
    );


$reviewCount =
    (int) (
        $reviewSummary['review_count']
        ?? 0
    );


$productStatus =
    $product['status']
    ?? 'Available';


/*
|--------------------------------------------------------------------------
| CHAT URL
|--------------------------------------------------------------------------
*/

$chatUrl =
    BASE_URL .
    'messages.php?vendor_id=' .
    (int) $product['vendor_id'];


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    $product['product_name'] .
    ' - ' .
    SITE_NAME;


$hideSiteMainWrapper = true;


require_once __DIR__ .
    '/includes/header.php';


if (
    $userId > 0 &&
    $currentRole === 'customer'
) {

    require_once __DIR__ .
        '/includes/customer_sidebar.php';
}

?>


<style>

* {
    box-sizing: border-box;
}


/* =========================================================
   PAGE
========================================================= */

.product-view-page {

    --pd-blue: #2563eb;
    --pd-blue-dark: #1d4ed8;
    --pd-purple: #7c3aed;
    --pd-navy: #08265a;
    --pd-text: #17365f;
    --pd-muted: #7e91ae;
    --pd-border: #dce7f3;
    --pd-soft: #edf5ff;

    width: 100%;
    min-height: 100vh;

    color: var(--pd-text);

    background:
        radial-gradient(
            circle at 92% 2%,
            rgba(124, 58, 237, .07),
            transparent 22%
        ),
        linear-gradient(
            180deg,
            #f4f8fd 0%,
            #ffffff 44%,
            #ffffff 100%
        );

    font-family:
        Inter,
        Arial,
        sans-serif;
}


.product-view-container {

    width:
        min(
            1320px,
            calc(100% - 52px)
        );

    margin:
        0 auto;
}


.product-view-inner {

    padding:
        45px 0 80px;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.product-breadcrumb {

    margin-bottom: 21px;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        8px;

    color:
        #8595ad;

    font-size:
        9px;

    font-weight:
        650;
}


.product-breadcrumb a {

    color:
        #58708f;

    text-decoration:
        none;
}


.product-breadcrumb a:hover {

    color:
        var(--pd-blue);
}


.product-breadcrumb strong {

    color:
        var(--pd-blue);
}


/* =========================================================
   MAIN CARD
========================================================= */

.product-main-card {

    position:
        relative;

    overflow:
        hidden;

    display:
        grid;

    grid-template-columns:
        minmax(0, 520px)
        minmax(0, 1fr);

    gap:
        46px;

    padding:
        34px;

    background:
        #ffffff;

    border:
        1px solid
        var(--pd-border);

    border-radius:
        29px;

    box-shadow:
        0 20px 50px
        rgba(
            31,
            69,
            125,
            .075
        );
}


/* =========================================================
   IMAGE
========================================================= */

.product-media-section {

    min-width: 0;
}


.product-media-box {

    position:
        relative;

    width:
        100%;

    height:
        520px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        24px;

    background:
        radial-gradient(
            circle at 75% 20%,
            rgba(37, 99, 235, .10),
            transparent 32%
        ),
        linear-gradient(
            135deg,
            #edf5ff,
            #fafcff
        );

    border:
        1px solid
        #dce9f8;

    border-radius:
        24px;
}


.product-media-box::before {

    content:
        "";

    position:
        absolute;

    width:
        170px;

    height:
        170px;

    left:
        -70px;

    bottom:
        -70px;

    border-radius:
        50%;

    background:
        rgba(
            124,
            58,
            237,
            .07
        );
}


.product-media-box img {

    position:
        relative;

    z-index:
        2;

    width:
        100%;

    height:
        100%;

    max-width:
        470px;

    max-height:
        470px;

    object-fit:
        contain;

    object-position:
        center;

    border-radius:
        16px;
}


.product-media-badge {

    position:
        absolute;

    top:
        18px;

    left:
        18px;

    z-index:
        4;

    min-height:
        30px;

    padding:
        0 11px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #245fc3;

    background:
        rgba(
            255,
            255,
            255,
            .94
        );

    border:
        1px solid
        #d5e7fb;

    border-radius:
        999px;

    box-shadow:
        0 7px 18px
        rgba(
            25,
            70,
            140,
            .09
        );

    font-size:
        8px;

    font-weight:
        850;
}


/* =========================================================
   PRODUCT INFO
========================================================= */

.product-main-info {

    min-width: 0;

    padding:
        8px 3px 3px;

    display:
        flex;

    flex-direction:
        column;
}


.product-category-pill {

    width:
        fit-content;

    margin-bottom:
        14px;

    padding:
        6px 11px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    color:
        var(--pd-blue);

    background:
        #edf5ff;

    border:
        1px solid
        #d6e8ff;

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        850;
}


.product-main-info h1 {

    margin:
        0 0 13px;

    color:
        #08275b;

    font-size:
        clamp(
            31px,
            4vw,
            45px
        );

    line-height:
        1.12;

    font-weight:
        850;

    letter-spacing:
        -1.5px;

    word-break:
        break-word;
}


/* =========================================================
   RATING TOP
========================================================= */

.product-rating-row {

    margin-bottom:
        22px;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        9px;
}


.product-rating-stars {

    color:
        #f59e0b;

    font-size:
        15px;

    letter-spacing:
        1px;
}


.product-rating-value {

    color:
        #273f62;

    font-size:
        11px;

    font-weight:
        850;
}


.product-review-count {

    color:
        #8a9ab0;

    font-size:
        9px;
}


/* =========================================================
   PRICE
========================================================= */

.product-price-label {

    display:
        block;

    margin-bottom:
        4px;

    color:
        #8a9ab0;

    font-size:
        8px;

    font-weight:
        850;

    letter-spacing:
        .8px;

    text-transform:
        uppercase;
}


.product-main-price {

    margin-bottom:
        23px;

    color:
        #0b3473;

    font-size:
        34px;

    line-height:
        1;

    font-weight:
        850;

    letter-spacing:
        -1px;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.product-description-block {

    margin-bottom:
        23px;

    padding:
        18px;

    color:
        #667995;

    background:
        #f8fbff;

    border:
        1px solid
        #e3ebf5;

    border-radius:
        16px;

    font-size:
        10px;

    line-height:
        1.85;
}


.product-description-block strong {

    display:
        block;

    margin-bottom:
        7px;

    color:
        #153665;

    font-size:
        9px;

    font-weight:
        850;

    letter-spacing:
        .5px;

    text-transform:
        uppercase;
}


/* =========================================================
   STOCK
========================================================= */

.product-stock-row {

    margin-bottom:
        23px;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        9px;
}


.product-stock-badge {

    min-height:
        31px;

    padding:
        0 11px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    border-radius:
        999px;

    font-size:
        8px;

    font-weight:
        850;
}


.product-stock-badge.in-stock {

    color:
        #15803d;

    background:
        #ecfdf3;

    border:
        1px solid
        #bbf7d0;
}


.product-stock-badge.out-stock {

    color:
        #b91c1c;

    background:
        #fff1f2;

    border:
        1px solid
        #fecdd3;
}


/* =========================================================
   VENDOR CARD
========================================================= */

.product-vendor-card {

    margin-bottom:
        23px;

    padding:
        16px;

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    background:
        linear-gradient(
            135deg,
            #f7faff,
            #edf5ff
        );

    border:
        1px solid
        #dbe8f6;

    border-radius:
        17px;
}


.product-vendor-logo {

    width:
        51px;

    height:
        51px;

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
        #2563eb;

    background:
        #ffffff;

    border:
        1px solid
        #d9e7f6;

    border-radius:
        14px;

    font-size:
        22px;
}


.product-vendor-logo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}


.product-vendor-content {

    min-width:
        0;

    flex:
        1;
}


.product-vendor-label {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #8495ae;

    font-size:
        7px;

    font-weight:
        850;

    text-transform:
        uppercase;

    letter-spacing:
        .6px;
}


.product-vendor-content strong {

    display:
        block;

    color:
        #0b3069;

    font-size:
        12px;

    font-weight:
        850;
}


.product-vendor-actions {

    margin-left:
        auto;

    display:
        flex;

    align-items:
        center;

    justify-content:
        flex-end;

    flex-wrap:
        wrap;

    gap:
        8px;
}


.product-vendor-link,
.product-vendor-chat {

    min-height:
        36px;

    padding:
        0 12px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        5px;

    border-radius:
        10px;

    font-family:
        inherit;

    font-size:
        8px;

    font-weight:
        850;

    text-decoration:
        none;

    white-space:
        nowrap;

    transition:
        .2s ease;
}


.product-vendor-link {

    color:
        #2563eb;

    background:
        #ffffff;

    border:
        1px solid
        #d6e7fb;
}


.product-vendor-chat {

    color:
        #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1675df
        );

    border:
        1px solid
        #2563eb;

    box-shadow:
        0 7px 16px
        rgba(
            37,
            99,
            235,
            .16
        );
}


/* =========================================================
   PURCHASE
========================================================= */

.product-purchase-area {

    margin-top:
        auto;

    padding-top:
        21px;

    border-top:
        1px solid
        #e8eef5;
}


.product-action-grid {

    display:
        grid;

    grid-template-columns:
        1fr auto;

    gap:
        10px;
}


.product-cart-form-modern {

    display:
        grid;

    grid-template-columns:
        96px
        minmax(0, 1fr);

    gap:
        10px;
}


.product-quantity-field label {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #7789a4;

    font-size:
        8px;

    font-weight:
        850;
}


.product-quantity-field input {

    width:
        100%;

    height:
        44px;

    padding:
        0 10px;

    color:
        #234063;

    background:
        #f8fbff;

    border:
        1px solid
        #d8e5f3;

    border-radius:
        10px;

    outline:
        none;

    font-family:
        inherit;

    font-size:
        10px;

    text-align:
        center;
}


.product-cart-submit {

    align-self:
        end;

    height:
        44px;

    padding:
        0 17px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1675df
        );

    border:
        0;

    border-radius:
        10px;

    box-shadow:
        0 9px 20px
        rgba(
            37,
            99,
            235,
            .22
        );

    font-family:
        inherit;

    font-size:
        9px;

    font-weight:
        850;

    cursor:
        pointer;
}


.product-wishlist-form {

    display:
        flex;

    align-items:
        flex-end;
}


.product-wishlist-submit {

    height:
        44px;

    padding:
        0 15px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #edf5ff;

    border:
        1px solid
        #d5e6fc;

    border-radius:
        10px;

    font-family:
        inherit;

    font-size:
        9px;

    font-weight:
        850;

    cursor:
        pointer;

    white-space:
        nowrap;
}


.product-login-actions {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        10px;
}


.product-login-button {

    min-height:
        45px;

    padding:
        0 15px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    color:
        #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1675df
        );

    border-radius:
        10px;

    font-size:
        9px;

    font-weight:
        850;

    text-decoration:
        none;
}


.product-login-button.secondary {

    color:
        #2563eb;

    background:
        #edf5ff;

    border:
        1px solid
        #d5e6fc;
}


.product-account-notice {

    padding:
        13px 15px;

    color:
        #64748b;

    background:
        #f8fafc;

    border:
        1px solid
        #e2e8f0;

    border-radius:
        11px;

    font-size:
        9px;

    line-height:
        1.6;
}


/* =========================================================
   REVIEW SECTION
========================================================= */

.product-reviews-section {

    margin-top:
        35px;

    overflow:
        hidden;

    background:
        #ffffff;

    border:
        1px solid
        var(--pd-border);

    border-radius:
        28px;

    box-shadow:
        0 18px 45px
        rgba(
            31,
            69,
            125,
            .06
        );
}


/* =========================================================
   REVIEW HERO
========================================================= */

.product-review-hero {

    position:
        relative;

    overflow:
        hidden;

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
            115deg,
            #211153 0%,
            #5630b1 46%,
            #2563eb 100%
        );
}


.product-review-hero::after {

    content:
        "★";

    position:
        absolute;

    right:
        20%;

    top:
        -65px;

    color:
        rgba(
            255,
            255,
            255,
            .06
        );

    font-size:
        190px;

    transform:
        rotate(12deg);
}


.product-review-hero-copy {

    position:
        relative;

    z-index:
        2;
}


.product-review-eyebrow {

    margin-bottom:
        6px;

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

    font-weight:
        850;

    letter-spacing:
        .9px;

    text-transform:
        uppercase;
}


.product-review-hero h2 {

    margin:
        0 0 7px;

    font-size:
        24px;

    font-weight:
        850;
}


.product-review-hero p {

    max-width:
        680px;

    margin:
        0;

    color:
        rgba(
            255,
            255,
            255,
            .74
        );

    font-size:
        10px;

    line-height:
        1.7;
}


.product-review-hero-actions {

    position:
        relative;

    z-index:
        2;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    justify-content:
        flex-end;

    gap:
        9px;
}


.product-review-write {

    min-height:
        41px;

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

    color:
        #5630b1;

    background:
        #ffffff;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .8
        );

    border-radius:
        12px;

    font-size:
        9px;

    font-weight:
        850;

    text-decoration:
        none;

    box-shadow:
        0 10px 24px
        rgba(
            18,
            23,
            69,
            .15
        );
}


.product-review-pill {

    min-height:
        41px;

    padding:
        0 14px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            .11
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .18
        );

    border-radius:
        12px;

    font-size:
        9px;

    font-weight:
        850;
}


/* =========================================================
   REVIEW SUMMARY GRID
========================================================= */

.product-review-summary {

    padding:
        30px;

    display:
        grid;

    grid-template-columns:
        280px
        minmax(0, 1fr);

    gap:
        30px;

    border-bottom:
        1px solid
        #e9eef5;
}


.product-review-score {

    min-height:
        245px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    text-align:
        center;

    background:
        linear-gradient(
            145deg,
            #faf8ff,
            #f3f7ff
        );

    border:
        1px solid
        #e6e3f6;

    border-radius:
        21px;
}


.product-review-score strong {

    color:
        #1c3762;

    font-size:
        64px;

    line-height:
        1;

    letter-spacing:
        -3px;
}


.product-review-score-stars {

    margin:
        10px 0 7px;

    color:
        #f59e0b;

    font-size:
        20px;

    letter-spacing:
        2px;
}


.product-review-score span {

    color:
        #8293aa;

    font-size:
        9px;
}


.product-rating-breakdown {

    display:
        flex;

    flex-direction:
        column;

    justify-content:
        center;

    gap:
        14px;
}


.product-rating-row-item {

    display:
        grid;

    grid-template-columns:
        45px
        minmax(0, 1fr)
        42px;

    align-items:
        center;

    gap:
        11px;

    color:
        #687d99;

    font-size:
        9px;

    font-weight:
        750;
}


.product-rating-bar {

    height:
        9px;

    overflow:
        hidden;

    background:
        #edf1f6;

    border-radius:
        999px;
}


.product-rating-fill {

    height:
        100%;

    background:
        linear-gradient(
            90deg,
            #f59e0b,
            #f8c44e
        );

    border-radius:
        inherit;
}


/* =========================================================
   REVIEW LIST
========================================================= */

.product-review-content {

    padding:
        30px;
}


.product-review-heading {

    margin-bottom:
        23px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;
}


.product-review-heading span {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #8b9bb1;

    font-size:
        8px;

    font-weight:
        850;

    letter-spacing:
        .8px;

    text-transform:
        uppercase;
}


.product-review-heading h3 {

    margin:
        0;

    color:
        #153862;

    font-size:
        20px;
}


.product-review-count-pill {

    padding:
        7px 11px;

    color:
        #5b34b5;

    background:
        #f2edff;

    border:
        1px solid
        #e4d9ff;

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        850;
}


.product-review-list {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap:
        16px;
}


.product-review-card {

    padding:
        20px;

    background:
        linear-gradient(
            145deg,
            #fcfdff,
            #f7faff
        );

    border:
        1px solid
        #e0e9f3;

    border-radius:
        19px;
}


.product-review-header {

    margin-bottom:
        13px;

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        12px;
}


.product-review-customer {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;
}


.product-review-avatar {

    width:
        41px;

    height:
        41px;

    overflow:
        hidden;

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
            #7c3aed,
            #2563eb
        );

    border-radius:
        50%;

    font-size:
        12px;

    font-weight:
        850;
}


.product-review-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}


.product-review-customer strong {

    display:
        block;

    color:
        #18365f;

    font-size:
        10px;

    font-weight:
        850;
}


.product-review-verified {

    margin-top:
        4px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        4px;

    color:
        #047857;

    font-size:
        7px;

    font-weight:
        800;
}


.product-review-date {

    color:
        #95a3b6;

    font-size:
        8px;
}


.product-review-stars {

    margin-bottom:
        8px;

    color:
        #f59e0b;

    font-size:
        12px;

    letter-spacing:
        1px;
}


.product-review-title {

    margin:
        0 0 8px;

    color:
        #183a64;

    font-size:
        12px;

    font-weight:
        850;
}


.product-review-text {

    margin:
        0;

    color:
        #687a94;

    font-size:
        10px;

    line-height:
        1.8;
}


.product-review-image {

    width:
        145px;

    height:
        145px;

    margin-top:
        13px;

    overflow:
        hidden;

    padding:
        5px;

    background:
        #ffffff;

    border:
        1px solid
        #dfe7f2;

    border-radius:
        14px;
}


.product-review-image img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    border-radius:
        9px;
}


.product-review-footer {

    margin-top:
        13px;

    padding-top:
        11px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        10px;

    border-top:
        1px solid
        #e8edf4;

    color:
        #8595aa;

    font-size:
        8px;
}


.product-review-helpful {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;
}


/* =========================================================
   EMPTY
========================================================= */

.product-review-empty {

    padding:
        55px 20px;

    text-align:
        center;

    background:
        #f7faff;

    border:
        1px dashed
        #bfd9f6;

    border-radius:
        19px;
}


.product-review-empty-icon {

    width:
        58px;

    height:
        58px;

    margin:
        0 auto 13px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #f59e0b;

    background:
        #fff7dc;

    border-radius:
        17px;

    font-size:
        25px;
}


.product-review-empty h3 {

    margin:
        0 0 6px;

    color:
        #173864;

    font-size:
        15px;

    font-weight:
        850;
}


.product-review-empty p {

    margin:
        0;

    color:
        #8495ad;

    font-size:
        9px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1050px
) {

    .product-main-card {

        grid-template-columns:
            minmax(0, 440px)
            minmax(0, 1fr);

        gap:
            30px;
    }


    .product-media-box {

        height:
            440px;
    }


    .product-review-list {

        grid-template-columns:
            1fr;
    }
}


@media (
    max-width: 850px
) {

    .product-main-card {

        grid-template-columns:
            1fr;
    }


    .product-media-box {

        height:
            470px;
    }


    .product-review-summary {

        grid-template-columns:
            1fr;
    }


    .product-review-score {

        min-height:
            210px;
    }
}


@media (
    max-width: 650px
) {

    .product-view-container {

        width:
            calc(
                100% - 26px
            );
    }


    .product-view-inner {

        padding:
            27px 0 55px;
    }


    .product-main-card {

        padding:
            17px;

        gap:
            22px;

        border-radius:
            22px;
    }


    .product-media-box {

        height:
            330px;

        padding:
            18px;

        border-radius:
            18px;
    }


    .product-main-info h1 {

        font-size:
            29px;
    }


    .product-vendor-card {

        align-items:
            flex-start;

        flex-wrap:
            wrap;
    }


    .product-vendor-actions {

        width:
            100%;

        margin-left:
            0;

        display:
            grid;

        grid-template-columns:
            1fr 1fr;
    }


    .product-vendor-link,
    .product-vendor-chat {

        width:
            100%;
    }


    .product-action-grid {

        grid-template-columns:
            1fr;
    }


    .product-cart-form-modern {

        grid-template-columns:
            90px 1fr;
    }


    .product-wishlist-form {

        display:
            block;
    }


    .product-wishlist-submit {

        width:
            100%;
    }


    .product-login-actions {

        grid-template-columns:
            1fr;
    }


    .product-review-hero {

        padding:
            24px;

        align-items:
            flex-start;

        flex-direction:
            column;
    }


    .product-review-hero-actions {

        justify-content:
            flex-start;
    }


    .product-review-summary,
    .product-review-content {

        padding:
            20px;
    }


    .product-review-heading {

        align-items:
            flex-start;

        flex-direction:
            column;
    }


    .product-review-header {

        flex-direction:
            column;
    }
}

</style>


<main class="product-view-page">

    <div class="product-view-inner">

        <div class="product-view-container">


            <!-- =====================================================
                 BREADCRUMB
            ====================================================== -->

            <nav class="product-breadcrumb">

                <a
                    href="<?= pdEscape(
                        BASE_URL
                    ) ?>index.php"
                >
                    Home
                </a>

                <span>
                    ›
                </span>

                <a
                    href="<?= pdEscape(
                        BASE_URL
                    ) ?>catalog.php"
                >
                    Catalog
                </a>

                <span>
                    ›
                </span>

                <a
                    href="<?= pdEscape(
                        BASE_URL
                    ) ?>catalog.php?category=<?= (int)
                        $product[
                            'category_id'
                        ] ?>"
                >

                    <?= pdEscape(
                        $product[
                            'category_name'
                        ]
                    ) ?>

                </a>

                <span>
                    ›
                </span>

                <strong>

                    <?= pdEscape(
                        $product[
                            'product_name'
                        ]
                    ) ?>

                </strong>

            </nav>


            <!-- =====================================================
                 PRODUCT MAIN CARD
            ====================================================== -->

            <section class="product-main-card">


                <!-- PRODUCT IMAGE -->

                <div class="product-media-section">

                    <div class="product-media-box">

                        <span class="product-media-badge">

                            <i class="bi bi-box-seam"></i>

                            Product Preview

                        </span>


                        <img
                            src="<?= pdEscape(
                                $productImage
                            ) ?>"
                            alt="<?= pdEscape(
                                $product[
                                    'product_name'
                                ]
                            ) ?>"
                            onerror="
                                this.src='image/logo.jpg';
                            "
                        >

                    </div>

                </div>


                <!-- PRODUCT INFORMATION -->

                <div class="product-main-info">


                    <!-- CATEGORY -->

                    <span class="product-category-pill">

                        <i class="bi bi-grid-fill"></i>

                        <?= pdEscape(
                            $product[
                                'category_name'
                            ]
                        ) ?>

                    </span>


                    <!-- TITLE -->

                    <h1>

                        <?= pdEscape(
                            $product[
                                'product_name'
                            ]
                        ) ?>

                    </h1>


                    <!-- RATING -->

                    <div class="product-rating-row">

                        <span class="product-rating-stars">

                            <?php

                            $roundedRating =
                                (int)
                                round(
                                    $averageRating
                                );


                            for (
                                $star = 1;
                                $star <= 5;
                                $star++
                            ) {

                                echo
                                    $star <=
                                    $roundedRating
                                        ? '★'
                                        : '☆';
                            }

                            ?>

                        </span>


                        <strong class="product-rating-value">

                            <?= number_format(
                                $averageRating,
                                1
                            ) ?>

                            / 5

                        </strong>


                        <span class="product-review-count">

                            <?= number_format(
                                $reviewCount
                            ) ?>

                            review<?= $reviewCount !== 1
                                ? 's'
                                : '' ?>

                        </span>

                    </div>


                    <!-- PRICE -->

                    <span class="product-price-label">

                        Product Price

                    </span>


                    <div class="product-main-price">

                        RM
                        <?= number_format(
                            $price,
                            2
                        ) ?>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="product-description-block">

                        <strong>
                            Description
                        </strong>


                        <?php if (
                            !empty(
                                trim(
                                    (string) (
                                        $product[
                                            'description'
                                        ]
                                        ?? ''
                                    )
                                )
                            )
                        ): ?>

                            <?= nl2br(
                                pdEscape(
                                    $product[
                                        'description'
                                    ]
                                )
                            ) ?>

                        <?php else: ?>

                            No product description provided.

                        <?php endif; ?>

                    </div>


                    <!-- STOCK -->

                    <div class="product-stock-row">

                        <?php if (
                            $stockQuantity > 0 &&
                            $productStatus ===
                            'Available'
                        ): ?>

                            <span
                                class="
                                    product-stock-badge
                                    in-stock
                                "
                            >

                                <i class="bi bi-check-circle-fill"></i>

                                In Stock

                                ·

                                <?= number_format(
                                    $stockQuantity
                                ) ?>

                                available

                            </span>

                        <?php else: ?>

                            <span
                                class="
                                    product-stock-badge
                                    out-stock
                                "
                            >

                                <i class="bi bi-x-circle-fill"></i>

                                Out of Stock

                            </span>

                        <?php endif; ?>

                    </div>


                    <!-- =================================================
                         SELLER
                    ================================================== -->

                    <div class="product-vendor-card">

                        <div class="product-vendor-logo">

                            <?php if (
                                $vendorImage !== ''
                            ): ?>

                                <img
                                    src="<?= pdEscape(
                                        $vendorImage
                                    ) ?>"
                                    alt="<?= pdEscape(
                                        $product[
                                            'business_name'
                                        ]
                                    ) ?>"
                                >

                            <?php else: ?>

                                <i class="bi bi-shop"></i>

                            <?php endif; ?>

                        </div>


                        <div class="product-vendor-content">

                            <span class="product-vendor-label">
                                Sold By
                            </span>


                            <strong>

                                <?= pdEscape(
                                    $product[
                                        'business_name'
                                    ]
                                ) ?>

                            </strong>

                        </div>


                        <div class="product-vendor-actions">

                            <a
                                href="<?= pdEscape(
                                    BASE_URL
                                ) ?>vendor.php?id=<?= (int)
                                    $product[
                                        'vendor_id'
                                    ] ?>"
                                class="product-vendor-link"
                            >

                                <i class="bi bi-shop"></i>

                                View Store

                            </a>


                            <?php if (
                                $userId > 0 &&
                                $currentRole ===
                                'customer'
                            ): ?>

                                <a
                                    href="<?= pdEscape(
                                        $chatUrl
                                    ) ?>"
                                    class="product-vendor-chat"
                                >

                                    <i class="bi bi-chat-dots-fill"></i>

                                    Chat with Seller

                                </a>

                            <?php elseif (
                                $userId <= 0
                            ): ?>

                                <a
                                    href="<?= pdEscape(
                                        BASE_URL
                                    ) ?>index.php?login=1"
                                    class="product-vendor-chat"
                                >

                                    <i class="bi bi-chat-dots-fill"></i>

                                    Login to Chat

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- =================================================
                         PURCHASE AREA
                    ================================================== -->

                    <?php if (
                        $stockQuantity > 0 &&
                        $productStatus ===
                        'Available'
                    ): ?>

                        <div class="product-purchase-area">


                            <?php if (
                                $userId > 0 &&
                                $currentRole ===
                                'customer'
                            ): ?>


                                <div class="product-action-grid">


                                    <!-- ADD TO CART -->

                                    <form
                                        action="<?= pdEscape(
                                            BASE_URL
                                        ) ?>ajax/add_cart.php"
                                        method="POST"
                                        class="product-cart-form-modern"
                                    >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)
                                                $productId ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= pdEscape(
                                                csrfToken()
                                            ) ?>"
                                        >


                                        <div class="product-quantity-field">

                                            <label>
                                                Quantity
                                            </label>


                                            <input
                                                type="number"
                                                name="quantity"
                                                min="1"
                                                max="<?= (int)
                                                    $stockQuantity ?>"
                                                value="1"
                                                required
                                            >

                                        </div>


                                        <button
                                            type="submit"
                                            class="product-cart-submit"
                                        >

                                            <i class="bi bi-cart-plus-fill"></i>

                                            <?= $inCart
                                                ? 'Add More to Cart'
                                                : 'Add to Cart' ?>

                                        </button>

                                    </form>


                                    <!-- WISHLIST -->

                                    <form
                                        action="<?= pdEscape(
                                            BASE_URL
                                        ) ?>ajax/add_wishlist.php"
                                        method="POST"
                                        class="product-wishlist-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)
                                                $productId ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= pdEscape(
                                                csrfToken()
                                            ) ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="product-wishlist-submit"
                                        >

                                            <?= $inWishlist
                                                ? '♥ In Wishlist'
                                                : '♡ Wishlist' ?>

                                        </button>

                                    </form>

                                </div>


                            <?php elseif (
                                $userId <= 0
                            ): ?>

                                <div class="product-login-actions">

                                    <a
                                        href="<?= pdEscape(
                                            BASE_URL
                                        ) ?>index.php?login=1"
                                        class="product-login-button"
                                    >

                                        <i class="bi bi-box-arrow-in-right"></i>

                                        Login to Purchase

                                    </a>


                                    <a
                                        href="<?= pdEscape(
                                            BASE_URL
                                        ) ?>index.php?login=1"
                                        class="
                                            product-login-button
                                            secondary
                                        "
                                    >

                                        <i class="bi bi-chat-dots"></i>

                                        Login to Chat

                                    </a>

                                </div>


                            <?php else: ?>

                                <div class="product-account-notice">

                                    This product can only be purchased
                                    using a customer account.

                                </div>

                            <?php endif; ?>

                        </div>


                    <?php else: ?>

                        <div class="product-purchase-area">

                            <div class="product-account-notice">

                                This product is currently unavailable
                                for purchase.

                                <?php if (
                                    $userId > 0 &&
                                    $currentRole ===
                                    'customer'
                                ): ?>

                                    You can still use
                                    <strong>
                                        Chat with Seller
                                    </strong>
                                    above to contact the seller.

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =====================================================
                 PREMIUM REVIEWS
            ====================================================== -->

            <section class="product-reviews-section">


                <!-- REVIEW HERO -->

                <div class="product-review-hero">

                    <div class="product-review-hero-copy">

                        <span class="product-review-eyebrow">

                            Verified Customer Experiences

                        </span>


                        <h2>

                            What buyers really think

                        </h2>


                        <p>

                            Reviews marked as Verified Purchase
                            come from customers who actually bought
                            this product through HochipoHub.

                        </p>

                    </div>


                    <div class="product-review-hero-actions">

                        <span class="product-review-pill">

                            <i class="bi bi-star-fill"></i>

                            <?= number_format(
                                $averageRating,
                                1
                            ) ?>

                            / 5

                        </span>


                        <?php if (
                            $reviewablePurchase &&
                            !empty(
                                $reviewablePurchase[
                                    'order_detail_id'
                                ]
                            )
                        ): ?>

                            <a
                                href="<?= pdEscape(
                                    BASE_URL
                                ) ?>review.php?order_detail_id=<?= (int)
                                    $reviewablePurchase[
                                        'order_detail_id'
                                    ] ?>"
                                class="product-review-write"
                            >

                                <i class="bi bi-stars"></i>

                                Write Review

                            </a>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- REVIEW SUMMARY -->

                <div class="product-review-summary">

                    <div class="product-review-score">

                        <strong>

                            <?= number_format(
                                $averageRating,
                                1
                            ) ?>

                        </strong>


                        <div class="product-review-score-stars">

                            <?php

                            for (
                                $star = 1;
                                $star <= 5;
                                $star++
                            ) {

                                echo
                                    $star <=
                                    $roundedRating
                                        ? '★'
                                        : '☆';
                            }

                            ?>

                        </div>


                        <span>

                            Based on

                            <?= number_format(
                                $reviewCount
                            ) ?>

                            customer review<?= $reviewCount !== 1
                                ? 's'
                                : '' ?>

                        </span>

                    </div>


                    <!-- BREAKDOWN -->

                    <div class="product-rating-breakdown">

                        <?php

                        for (
                            $ratingRow = 5;
                            $ratingRow >= 1;
                            $ratingRow--
                        ):

                            $ratingCount =
                                (int) (
                                    $reviewSummary[
                                        'rating_' .
                                        $ratingRow
                                    ]
                                    ?? 0
                                );


                            $ratingPercentage =
                                $reviewCount > 0
                                    ? (
                                        $ratingCount /
                                        $reviewCount
                                    ) * 100
                                    : 0;

                        ?>

                            <div class="product-rating-row-item">

                                <span>

                                    <?= $ratingRow ?>
                                    ★

                                </span>


                                <div class="product-rating-bar">

                                    <div
                                        class="product-rating-fill"
                                        style="
                                            width:
                                            <?= number_format(
                                                $ratingPercentage,
                                                2,
                                                '.',
                                                ''
                                            ) ?>%;
                                        "
                                    ></div>

                                </div>


                                <span>

                                    <?= number_format(
                                        $ratingCount
                                    ) ?>

                                </span>

                            </div>

                        <?php endfor; ?>

                    </div>

                </div>


                <!-- REVIEW LIST -->

                <div class="product-review-content">

                    <div class="product-review-heading">

                        <div>

                            <span>
                                Customer Feedback
                            </span>

                            <h3>
                                Product Reviews
                            </h3>

                        </div>


                        <div class="product-review-count-pill">

                            <?= number_format(
                                $reviewCount
                            ) ?>

                            review<?= $reviewCount !== 1
                                ? 's'
                                : '' ?>

                        </div>

                    </div>


                    <?php if (
                        empty(
                            $reviews
                        )
                    ): ?>

                        <div class="product-review-empty">

                            <div class="product-review-empty-icon">

                                ★

                            </div>


                            <h3>
                                No reviews yet
                            </h3>


                            <p>

                                Completed buyers can be the first
                                to share their verified experience.

                            </p>

                        </div>


                    <?php else: ?>

                        <div class="product-review-list">


                            <?php foreach (
                                $reviews
                                as $review
                            ): ?>


                                <?php

                                $customerName =
                                    trim(
                                        (string) (
                                            $review[
                                                'customer_name'
                                            ]
                                            ??
                                            'Customer'
                                        )
                                    );


                                $customerInitial =
                                    function_exists(
                                        'mb_substr'
                                    )
                                        ? strtoupper(
                                            mb_substr(
                                                $customerName,
                                                0,
                                                1
                                            )
                                        )
                                        : strtoupper(
                                            substr(
                                                $customerName,
                                                0,
                                                1
                                            )
                                        );


                                $reviewRating =
                                    max(
                                        1,
                                        min(
                                            5,
                                            (int) (
                                                $review[
                                                    'rating'
                                                ]
                                                ?? 1
                                            )
                                        )
                                    );


                                $profileImage =
                                    trim(
                                        (string) (
                                            $review[
                                                'profile_image'
                                            ]
                                            ?? ''
                                        )
                                    );


                                $reviewPhoto =
                                    pdReviewImage(
                                        $review[
                                            'image'
                                        ]
                                        ?? ''
                                    );

                                ?>


                                <article class="product-review-card">


                                    <!-- CUSTOMER -->

                                    <div class="product-review-header">

                                        <div class="product-review-customer">

                                            <div class="product-review-avatar">

                                                <?php if (
                                                    $profileImage !== ''
                                                ): ?>

                                                    <img
                                                        src="<?= pdEscape(
                                                            strpos(
                                                                $profileImage,
                                                                'uploads/'
                                                            ) === 0
                                                                ? $profileImage
                                                                : 'uploads/' .
                                                                    rawurlencode(
                                                                        basename(
                                                                            $profileImage
                                                                        )
                                                                    )
                                                        ) ?>"
                                                        alt="<?= pdEscape(
                                                            $customerName
                                                        ) ?>"
                                                    >

                                                <?php else: ?>

                                                    <?= pdEscape(
                                                        $customerInitial
                                                    ) ?>

                                                <?php endif; ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= pdEscape(
                                                        $customerName
                                                    ) ?>

                                                </strong>


                                                <?php if (
                                                    !empty(
                                                        $review[
                                                            'order_detail_id'
                                                        ]
                                                    )
                                                ): ?>

                                                    <span class="product-review-verified">

                                                        <i
                                                            class="bi bi-patch-check-fill"
                                                        ></i>

                                                        Verified Purchase

                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <span class="product-review-date">

                                            <?= pdEscape(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $review[
                                                            'review_date'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                    <!-- STARS -->

                                    <div class="product-review-stars">

                                        <?php

                                        for (
                                            $star = 1;
                                            $star <= 5;
                                            $star++
                                        ) {

                                            echo
                                                $star <=
                                                $reviewRating
                                                    ? '★'
                                                    : '☆';
                                        }

                                        ?>

                                    </div>


                                    <!-- TITLE -->

                                    <?php if (
                                        !empty(
                                            trim(
                                                (string) (
                                                    $review[
                                                        'review_title'
                                                    ]
                                                    ?? ''
                                                )
                                            )
                                        )
                                    ): ?>

                                        <h4 class="product-review-title">

                                            <?= pdEscape(
                                                $review[
                                                    'review_title'
                                                ]
                                            ) ?>

                                        </h4>

                                    <?php endif; ?>


                                    <!-- TEXT -->

                                    <?php if (
                                        !empty(
                                            trim(
                                                (string) (
                                                    $review[
                                                        'review'
                                                    ]
                                                    ?? ''
                                                )
                                            )
                                        )
                                    ): ?>

                                        <p class="product-review-text">

                                            <?= nl2br(
                                                pdEscape(
                                                    $review[
                                                        'review'
                                                    ]
                                                )
                                            ) ?>

                                        </p>

                                    <?php endif; ?>


                                    <!-- REVIEW IMAGE -->

                                    <?php if (
                                        $reviewPhoto !== ''
                                    ): ?>

                                        <div class="product-review-image">

                                            <img
                                                src="<?= pdEscape(
                                                    $reviewPhoto
                                                ) ?>"
                                                alt="Customer review photo"
                                            >

                                        </div>

                                    <?php endif; ?>


                                    <!-- FOOTER -->

                                    <div class="product-review-footer">

                                        <span class="product-review-helpful">

                                            <i
                                                class="bi bi-hand-thumbs-up"
                                            ></i>

                                            Helpful

                                            <?php if (
                                                (int) (
                                                    $review[
                                                        'helpful_count'
                                                    ]
                                                    ?? 0
                                                ) > 0
                                            ): ?>

                                                ·

                                                <?= number_format(
                                                    (int)
                                                    $review[
                                                        'helpful_count'
                                                    ]
                                                ) ?>

                                            <?php endif; ?>

                                        </span>


                                        <?php if (
                                            !empty(
                                                $review[
                                                    'order_date'
                                                ]
                                            )
                                        ): ?>

                                            <span>

                                                Purchased
                                                <?= pdEscape(
                                                    date(
                                                        'M Y',
                                                        strtotime(
                                                            $review[
                                                                'order_date'
                                                            ]
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </div>

</main>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>