<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PRODUCT DETAILS
|--------------------------------------------------------------------------
| File:
| product_details.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';


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
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$product_id =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : (int) ($_GET['product_id'] ?? 0);


if ($product_id <= 0) {

    header(
        'Location: product.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                p.*,

                v.vendor_id,
                v.business_name,
                v.business_logo,
                v.business_description,

                c.category_id,
                c.category_name

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN categories c
                ON p.category_id = c.category_id

            WHERE p.product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $product_id
    ]);


    $product =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $product = false;

}


/*
|--------------------------------------------------------------------------
| PRODUCT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$product) {

    header(
        'Location: product.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

$productImage =
    !empty(
        $product['image']
    )
        ? 'uploads/products/' .
            rawurlencode(
                basename(
                    $product['image']
                )
            )
        : 'image/logo.jpg';


/*
|--------------------------------------------------------------------------
| VENDOR IMAGE
|--------------------------------------------------------------------------
*/

$vendorImage =
    !empty(
        $product['business_logo']
    )
        ? 'uploads/vendors/' .
            rawurlencode(
                basename(
                    $product['business_logo']
                )
            )
        : '';


/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                COUNT(*) AS review_count,

                COALESCE(
                    AVG(rating),
                    0
                ) AS average_rating

            FROM reviews

            WHERE product_id = ?

            AND status = 'Visible'
        ");


    $stmt->execute([
        $product_id
    ]);


    $reviewSummary =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $reviewSummary = [
        'review_count' => 0,
        'average_rating' => 0
    ];

}


/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                r.*,

                u.name AS customer_name

            FROM reviews r

            INNER JOIN users u
                ON r.customer_id = u.user_id

            WHERE r.product_id = ?

            AND r.status = 'Visible'

            ORDER BY r.review_date DESC
        ");


    $stmt->execute([
        $product_id
    ]);


    $reviews =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $reviews = [];

}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$user_id =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


/*
|--------------------------------------------------------------------------
| CART / WISHLIST STATUS
|--------------------------------------------------------------------------
*/

$inCart = false;

$inWishlist = false;

$cartQuantity = 0;


if ($user_id > 0) {


    /*
    |--------------------------------------------------------------------------
    | CART
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $db->prepare("
                SELECT quantity

                FROM cart

                WHERE customer_id = ?

                AND product_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $user_id,
            $product_id
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

    }

    catch (Throwable $e) {

        $inCart = false;

        $cartQuantity = 0;

    }


    /*
    |--------------------------------------------------------------------------
    | WISHLIST
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $db->prepare("
                SELECT wishlist_id

                FROM wishlist

                WHERE user_id = ?

                AND product_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $user_id,
            $product_id
        ]);


        $inWishlist =
            (bool)
            $stmt->fetch();

    }

    catch (Throwable $e) {

        $inWishlist = false;

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


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    $product['product_name'] .
    ' - ' .
    SITE_NAME;


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
|
| header.php already handles the global navbar.
| Do not include navbar.php again here.
|
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

?>


<style>

/* ==========================================================================
   HOCHIPOHUB PRODUCT DETAILS
   ========================================================================== */

.product-view-page {

    --pd-blue:
        #2563eb;

    --pd-navy:
        #08265a;

    --pd-text:
        #0b2d63;

    --pd-muted:
        #7e91ae;

    --pd-border:
        #dce7f3;

    --pd-soft:
        #edf5ff;

    width:
        100%;

    min-height:
        100vh;

    color:
        var(
            --pd-text
        );

    background:

        linear-gradient(
            180deg,
            #f4f8fd 0%,
            #ffffff 40%,
            #ffffff 100%
        );

}


/* ==========================================================================
   CONTAINER
   ========================================================================== */

.product-view-container {

    width:
        min(
            1315px,
            calc(
                100% - 52px
            )
        );

    margin:
        0 auto;

}


.product-view-inner {

    padding:
        48px 0 80px;

}


/* ==========================================================================
   BREADCRUMB
   ========================================================================== */

.product-breadcrumb {

    margin-bottom:
        21px;

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
        600;

}


.product-breadcrumb a {

    color:
        #58708f;

    text-decoration:
        none;

}


.product-breadcrumb a:hover {

    color:
        #2563eb;

}


.product-breadcrumb strong {

    color:
        #2563eb;

}


/* ==========================================================================
   PRODUCT PANEL
   ========================================================================== */

.product-main-card {

    position:
        relative;

    overflow:
        hidden;

    display:
        grid;

    grid-template-columns:
        minmax(
            0,
            520px
        )
        minmax(
            0,
            1fr
        );

    gap:
        46px;

    padding:
        34px;

    background:
        #ffffff;

    border:
        1px solid
        var(
            --pd-border
        );

    border-radius:
        29px;

    box-shadow:

        0
        20px
        50px
        rgba(
            31,
            69,
            125,
            .075
        );

}


/* ==========================================================================
   IMAGE AREA
   ========================================================================== */

.product-media-section {

    min-width:
        0;

}


/*
|--------------------------------------------------------------------------
| IMPORTANT IMAGE SIZE FIX
|--------------------------------------------------------------------------
|
| Every uploaded product image is placed inside this fixed media box.
| User image dimensions no longer control the page layout.
|
|--------------------------------------------------------------------------
*/

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
            rgba(
                37,
                99,
                235,
                .10
            ),
            transparent 32%
        ),

        linear-gradient(
            135deg,
            #edf5ff,
            #f9fcff
        );

    border:
        1px solid
        #dce9f8;

    border-radius:
        24px;

}


/* ==========================================================================
   FIXED PRODUCT IMAGE
   ========================================================================== */

.product-media-box img {

    display:
        block;

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


/* ==========================================================================
   IMAGE DECORATION
   ========================================================================== */

.product-media-box::before {

    content:
        "";

    position:
        absolute;

    width:
        130px;

    height:
        130px;

    left:
        -60px;

    bottom:
        -55px;

    border-radius:
        50%;

    background:
        rgba(
            37,
            99,
            235,
            .07
        );

}


.product-media-badge {

    position:
        absolute;

    top:
        18px;

    left:
        18px;

    z-index:
        3;

    min-height:
        30px;

    padding:
        0 10px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #1f5fd2;

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

        0
        7px
        18px
        rgba(
            25,
            70,
            140,
            .09
        );

    font-size:
        8px;

    font-weight:
        800;

}


/* ==========================================================================
   PRODUCT INFORMATION
   ========================================================================== */

.product-main-info {

    min-width:
        0;

    padding:
        9px 4px 4px;

    display:
        flex;

    flex-direction:
        column;

}


/* ==========================================================================
   CATEGORY
   ========================================================================== */

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

    color:
        #2563eb;

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
        800;

}


/* ==========================================================================
   TITLE
   ========================================================================== */

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
        800;

    letter-spacing:
        -1.5px;

    word-break:
        break-word;

}


/* ==========================================================================
   RATING
   ========================================================================== */

.product-rating-row {

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        9px;

    margin-bottom:
        22px;

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
        800;

}


.product-review-count {

    color:
        #8a9ab0;

    font-size:
        9px;

}


/* ==========================================================================
   PRICE
   ========================================================================== */

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
        800;

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
        800;

    letter-spacing:
        -1px;

}


/* ==========================================================================
   DESCRIPTION
   ========================================================================== */

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
        800;

    letter-spacing:
        .5px;

    text-transform:
        uppercase;

}


/* ==========================================================================
   STOCK
   ========================================================================== */

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
        800;

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


/* ==========================================================================
   VENDOR CARD
   ========================================================================== */

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
        800;

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
        800;

}


.product-vendor-link {

    min-height:
        34px;

    padding:
        0 11px;

    display:
        inline-flex;

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
        #d6e7fb;

    border-radius:
        9px;

    font-size:
        8px;

    font-weight:
        800;

    text-decoration:
        none;

}


/* ==========================================================================
   BUY AREA
   ========================================================================== */

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


/* ==========================================================================
   CART FORM
   ========================================================================== */

.product-cart-form-modern {

    display:
        grid;

    grid-template-columns:
        96px minmax(
            0,
            1fr
        );

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
        800;

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

        0
        9px
        20px
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
        800;

    cursor:
        pointer;

}


/* ==========================================================================
   WISHLIST
   ========================================================================== */

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
        800;

    cursor:
        pointer;

    white-space:
        nowrap;

}


/* ==========================================================================
   LOGIN
   ========================================================================== */

.product-login-button {

    width:
        100%;

    min-height:
        45px;

    display:
        inline-flex;

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
            #1675df
        );

    border-radius:
        10px;

    font-size:
        9px;

    font-weight:
        800;

    text-decoration:
        none;

}


/* ==========================================================================
   REVIEWS SECTION
   ========================================================================== */

.product-reviews-section {

    margin-top:
        35px;

    padding:
        31px;

    background:
        #ffffff;

    border:
        1px solid
        var(
            --pd-border
        );

    border-radius:
        26px;

    box-shadow:

        0
        14px
        35px
        rgba(
            31,
            69,
            125,
            .055
        );

}


/* ==========================================================================
   REVIEW HEADING
   ========================================================================== */

.product-section-heading {

    margin-bottom:
        24px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

}


.product-heading-group {

    display:
        flex;

    align-items:
        center;

    gap:
        14px;

}


.product-heading-icon {

    width:
        51px;

    height:
        51px;

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
            #1476e8,
            #1d95f3
        );

    border-radius:
        15px;

    box-shadow:

        0
        8px
        20px
        rgba(
            37,
            99,
            235,
            .20
        );

    font-size:
        20px;

}


.product-heading-text span {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #2563eb;

    font-size:
        7px;

    font-weight:
        800;

    letter-spacing:
        .8px;

    text-transform:
        uppercase;

}


.product-heading-text h2 {

    margin:
        0;

    color:
        #0a2d64;

    font-size:
        20px;

    font-weight:
        800;

}


.product-review-summary-pill {

    min-height:
        36px;

    padding:
        0 13px;

    display:
        inline-flex;

    align-items:
        center;

    color:
        #1e5fcc;

    background:
        #edf5ff;

    border:
        1px solid
        #d7e8ff;

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        800;

}


/* ==========================================================================
   REVIEW LIST
   ========================================================================== */

.product-review-list {

    display:
        grid;

    grid-template-columns:

        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap:
        15px;

}


.product-review-card {

    padding:
        19px;

    background:

        linear-gradient(
            145deg,
            #fbfdff,
            #f5f9ff
        );

    border:
        1px solid
        #e0e9f3;

    border-radius:
        17px;

}


.product-review-header {

    margin-bottom:
        12px;

    display:
        flex;

    align-items:
        center;

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
        9px;

}


.product-review-avatar {

    width:
        37px;

    height:
        37px;

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
            #60a5fa
        );

    border-radius:
        10px;

    font-size:
        11px;

    font-weight:
        800;

}


.product-review-customer strong {

    color:
        #18365f;

    font-size:
        10px;

    font-weight:
        800;

}


.product-review-stars {

    color:
        #f59e0b;

    font-size:
        11px;

    letter-spacing:
        .5px;

}


.product-review-card p {

    margin:
        0 0 12px;

    color:
        #687a94;

    font-size:
        10px;

    line-height:
        1.75;

}


.product-review-date {

    color:
        #95a3b6;

    font-size:
        8px;

    font-weight:
        600;

}


/* ==========================================================================
   EMPTY REVIEWS
   ========================================================================== */

.product-review-empty {

    padding:
        60px 20px;

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

    background:
        #e9f2ff;

    border-radius:
        16px;

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
        800;

}


.product-review-empty p {

    margin:
        0;

    color:
        #8495ad;

    font-size:
        9px;

}


/* ==========================================================================
   RESPONSIVE
   ========================================================================== */

@media (
    max-width: 1050px
) {

    .product-main-card {

        grid-template-columns:

            minmax(
                0,
                440px
            )

            minmax(
                0,
                1fr
            );

        gap:
            30px;

    }


    .product-media-box {

        height:
            440px;

    }


    .product-media-box img {

        max-width:
            400px;

        max-height:
            400px;

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
            480px;

    }


    .product-media-box img {

        max-width:
            430px;

        max-height:
            430px;

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


    .product-media-box img {

        max-width:
            290px;

        max-height:
            290px;

    }


    .product-main-info {

        padding:
            2px;

    }


    .product-main-info h1 {

        font-size:
            29px;

    }


    .product-main-price {

        font-size:
            28px;

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


    .product-vendor-card {

        align-items:
            flex-start;

        flex-wrap:
            wrap;

    }


    .product-vendor-link {

        width:
            100%;

    }


    .product-reviews-section {

        margin-top:
            24px;

        padding:
            18px;

        border-radius:
            21px;

    }


    .product-section-heading {

        align-items:
            flex-start;

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
                    href="<?= e(BASE_URL) ?>index.php"
                >

                    Home

                </a>


                <span>
                    ›
                </span>


                <a
                    href="<?= e(BASE_URL) ?>catalog.php"
                >

                    Catalog

                </a>


                <span>
                    ›
                </span>


                <a
                    href="<?= e(BASE_URL) ?>catalog.php?category=<?= (int) $product['category_id'] ?>"
                >

                    <?= e(
                        $product[
                            'category_name'
                        ]
                    ) ?>

                </a>


                <span>
                    ›
                </span>


                <strong>

                    <?= e(
                        $product[
                            'product_name'
                        ]
                    ) ?>

                </strong>


            </nav>



            <!-- =====================================================
                 PRODUCT DETAILS
            ====================================================== -->

            <section class="product-main-card">


                <!-- =================================================
                     IMAGE
                ================================================== -->

                <div class="product-media-section">


                    <div class="product-media-box">


                        <span class="product-media-badge">

                            📦 Product Preview

                        </span>


                        <img
                            src="<?= e(
                                $productImage
                            ) ?>"
                            alt="<?= e(
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



                <!-- =================================================
                     PRODUCT INFO
                ================================================== -->

                <div class="product-main-info">


                    <!-- CATEGORY -->

                    <span class="product-category-pill">

                        🗂️

                        <?= e(
                            $product[
                                'category_name'
                            ]
                        ) ?>

                    </span>



                    <!-- TITLE -->

                    <h1>

                        <?= e(
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

                                echo $star <= $roundedRating
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
                                    $product[
                                        'description'
                                    ]
                                    ?? ''
                                )
                            )
                        ): ?>


                            <?= nl2br(
                                e(
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
                            $stockQuantity > 0
                        ): ?>


                            <span
                                class="
                                    product-stock-badge
                                    in-stock
                                "
                            >

                                ● In Stock

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

                                ● Out of Stock

                            </span>


                        <?php endif; ?>


                    </div>



                    <!-- =================================================
                         VENDOR
                    ================================================== -->

                    <div class="product-vendor-card">


                        <div class="product-vendor-logo">


                            <?php if (
                                $vendorImage !== ''
                            ): ?>


                                <img
                                    src="<?= e(
                                        $vendorImage
                                    ) ?>"
                                    alt="<?= e(
                                        $product[
                                            'business_name'
                                        ]
                                    ) ?>"
                                    onerror="
                                        this.style.display='none';
                                        this.parentElement.innerHTML='🏪';
                                    "
                                >


                            <?php else: ?>


                                🏪


                            <?php endif; ?>


                        </div>


                        <div class="product-vendor-content">


                            <span class="product-vendor-label">

                                Sold By

                            </span>


                            <strong>

                                <?= e(
                                    $product[
                                        'business_name'
                                    ]
                                ) ?>

                            </strong>


                        </div>


                        <a
                            href="<?= e(BASE_URL) ?>vendor.php?id=<?= (int) $product['vendor_id'] ?>"
                            class="product-vendor-link"
                        >

                            View Store →

                        </a>


                    </div>



                    <!-- =================================================
                         PURCHASE
                    ================================================== -->

                    <?php if (
                        $stockQuantity > 0
                    ): ?>


                        <div class="product-purchase-area">


                            <?php if (
                                $user_id > 0
                            ): ?>


                                <div class="product-action-grid">


                                    <!-- CART -->

                                    <form
                                        action="ajax/add_cart.php"
                                        method="POST"
                                        class="product-cart-form-modern"
                                    >


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= $product_id ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
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
                                                max="<?= $stockQuantity ?>"
                                                value="1"
                                                required
                                            >


                                        </div>


                                        <button
                                            type="submit"
                                            class="product-cart-submit"
                                        >

                                            🛒

                                            <?= $inCart
                                                ? 'Add More to Cart'
                                                : 'Add to Cart' ?>

                                        </button>


                                    </form>



                                    <!-- WISHLIST -->

                                    <form
                                        action="ajax/add_wishlist.php"
                                        method="POST"
                                        class="product-wishlist-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= $product_id ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
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


                            <?php else: ?>


                                <a
                                    href="<?= e(BASE_URL) ?>index.php?login=1"
                                    class="product-login-button"
                                >

                                    🔐 Login to Purchase

                                </a>


                            <?php endif; ?>


                        </div>


                    <?php endif; ?>


                </div>


            </section>



            <!-- =====================================================
                 REVIEWS
            ====================================================== -->

            <section class="product-reviews-section">


                <div class="product-section-heading">


                    <div class="product-heading-group">


                        <div class="product-heading-icon">

                            ⭐

                        </div>


                        <div class="product-heading-text">


                            <span>

                                Customer Feedback

                            </span>


                            <h2>

                                Product Reviews

                            </h2>


                        </div>


                    </div>


                    <span class="product-review-summary-pill">

                        ⭐
                        <?= number_format(
                            $averageRating,
                            1
                        ) ?>

                        ·

                        <?= number_format(
                            $reviewCount
                        ) ?>

                        review<?= $reviewCount !== 1
                            ? 's'
                            : '' ?>

                    </span>


                </div>



                <?php if (
                    empty(
                        $reviews
                    )
                ): ?>


                    <div class="product-review-empty">


                        <div class="product-review-empty-icon">

                            ⭐

                        </div>


                        <h3>

                            No reviews yet

                        </h3>


                        <p>

                            Be the first customer to review this product.

                        </p>


                    </div>


                <?php else: ?>


                    <div class="product-review-list">


                        <?php foreach (
                            $reviews as $review
                        ): ?>


                            <?php

                            $customerName =
                                trim(
                                    $review[
                                        'customer_name'
                                    ]
                                    ?? 'Customer'
                                );


                            $customerInitial =
                                strtoupper(
                                    substr(
                                        $customerName,
                                        0,
                                        1
                                    )
                                );


                            $reviewRating =
                                max(
                                    0,
                                    min(
                                        5,
                                        (int)
                                        (
                                            $review[
                                                'rating'
                                            ]
                                            ?? 0
                                        )
                                    )
                                );

                            ?>


                            <article class="product-review-card">


                                <div class="product-review-header">


                                    <div class="product-review-customer">


                                        <div class="product-review-avatar">

                                            <?= e(
                                                $customerInitial
                                            ) ?>

                                        </div>


                                        <strong>

                                            <?= e(
                                                $customerName
                                            ) ?>

                                        </strong>


                                    </div>


                                    <span class="product-review-stars">

                                        <?= str_repeat(
                                            '★',
                                            $reviewRating
                                        ) ?>

                                        <?= str_repeat(
                                            '☆',
                                            5 - $reviewRating
                                        ) ?>

                                    </span>


                                </div>



                                <?php if (
                                    !empty(
                                        trim(
                                            $review[
                                                'review'
                                            ]
                                            ?? ''
                                        )
                                    )
                                ): ?>


                                    <p>

                                        <?= nl2br(
                                            e(
                                                $review[
                                                    'review'
                                                ]
                                            )
                                        ) ?>

                                    </p>


                                <?php endif; ?>



                                <span class="product-review-date">

                                    <?= e(
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


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


        </div>


    </div>


</main>



<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';

?>