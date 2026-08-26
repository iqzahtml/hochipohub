<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR PAGE
|--------------------------------------------------------------------------
| File: vendor.php
|--------------------------------------------------------------------------
|
| Purpose:
| - Display all approved vendors
| - Display individual vendor profile
| - Display vendor products
| - UI matched with category.php
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG / DATABASE / FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';


$db = getDB();


/*
|--------------------------------------------------------------------------
| VENDOR ID
|--------------------------------------------------------------------------
*/

$vendorId =
    (int) (
        $_GET['id']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| DEFAULT DATA
|--------------------------------------------------------------------------
*/

$vendor = null;

$vendors = [];

$products = [];


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorEscape')) {

    function vendorEscape($value): string
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
| VENDOR IMAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorLogoUrl')) {

    function vendorLogoUrl(?string $image): string
    {
        if (empty($image)) {
            return '';
        }


        if (function_exists('getVendorImage')) {

            return getVendorImage(
                $image
            );

        }


        return BASE_URL .
            'uploads/vendors/' .
            rawurlencode(
                basename(
                    $image
                )
            );
    }

}


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorProductImageUrl')) {

    function vendorProductImageUrl(?string $image): string
    {
        if (empty($image)) {
            return '';
        }


        if (function_exists('getProductImage')) {

            return getProductImage(
                $image
            );

        }


        return BASE_URL .
            'uploads/products/' .
            rawurlencode(
                basename(
                    $image
                )
            );
    }

}


/*
|--------------------------------------------------------------------------
| SINGLE VENDOR
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {


    /*
    |--------------------------------------------------------------------------
    | LOAD VENDOR
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

                WHERE v.vendor_id = ?

                AND LOWER(v.approval_status) = 'approved'

                AND LOWER(u.status) = 'active'

                LIMIT 1
            ");


        $stmt->execute([
            $vendorId
        ]);


        $vendor =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

    }

    catch (Throwable $e) {

        $vendor = null;

    }


    /*
    |--------------------------------------------------------------------------
    | INVALID VENDOR
    |--------------------------------------------------------------------------
    */

    if (!$vendor) {

        header(
            'Location: ' .
            BASE_URL .
            'vendor.php'
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | LOAD PRODUCTS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $db->prepare("
                SELECT

                    p.product_id,
                    p.vendor_id,
                    p.category_id,
                    p.product_name,
                    p.description,
                    p.price,
                    p.stock_quantity,
                    p.image,
                    p.status,
                    p.created_at,

                    c.category_name

                FROM products p

                INNER JOIN categories c
                    ON p.category_id = c.category_id

                WHERE p.vendor_id = ?

                AND LOWER(p.status) IN (
                    'available',
                    'active'
                )

                AND p.stock_quantity > 0

                ORDER BY
                    p.created_at DESC,
                    p.product_id DESC
            ");


        $stmt->execute([
            $vendorId
        ]);


        $products =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    }

    catch (Throwable $e) {

        $products = [];

    }


    $pageTitle =
        $vendor['business_name'] .
        ' - ' .
        SITE_NAME;

}


/*
|--------------------------------------------------------------------------
| ALL VENDORS
|--------------------------------------------------------------------------
*/

else {


    try {

        $stmt =
            $db->query("
                SELECT

                    v.vendor_id,
                    v.business_name,
                    v.business_logo,
                    v.business_description,
                    v.category,
                    v.delivery_method,
                    v.created_at

                FROM vendors v

                INNER JOIN users u
                    ON v.user_id = u.user_id

                WHERE LOWER(v.approval_status) = 'approved'

                AND LOWER(u.status) = 'active'

                ORDER BY
                    v.business_name ASC
            ");


        $vendors =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    }

    catch (Throwable $e) {

        $vendors = [];

    }


    $pageTitle =
        'Vendors - ' .
        SITE_NAME;

}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

?>


<style>

/* ==========================================================================
   VENDOR PAGE - MATCH CATEGORY UI
   ========================================================================== */


.vendor-page {

    --vendor-blue:
        #2563eb;

    --vendor-navy:
        #0b2d6d;

    --vendor-text:
        #08265a;

    --vendor-muted:
        #7e91ae;

    --vendor-border:
        #dce7f3;

    --vendor-soft:
        #edf5ff;

    width:
        100%;

    min-height:
        100vh;

    background:

        linear-gradient(
            180deg,
            #f4f8fd 0%,
            #ffffff 35%,
            #ffffff 100%
        );

    color:
        var(
            --vendor-text
        );

}


/* ==========================================================================
   CONTAINER
   ========================================================================== */

.vendor-container {

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


/* ==========================================================================
   PAGE SPACING
   ========================================================================== */

.vendor-page-inner {

    padding:
        58px 0 80px;

}


/* ==========================================================================
   HERO
   ========================================================================== */

.vendor-hero {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        305px;

    display:
        flex;

    align-items:
        center;

    padding:
        54px 58px;

    margin-bottom:
        52px;

    color:
        #ffffff;

    background:

        linear-gradient(
            110deg,
            #0b2b69 0%,
            #1948a0 48%,
            #2683ef 100%
        );

    border-radius:
        31px;

    box-shadow:

        0
        20px
        48px
        rgba(
            31,
            80,
            160,
            .13
        );

}


/* ==========================================================================
   HERO DECORATION
   ========================================================================== */

.vendor-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        270px;

    height:
        270px;

    top:
        -135px;

    right:
        -20px;

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


.vendor-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        190px;

    height:
        190px;

    right:
        185px;

    bottom:
        -120px;

    border-radius:
        50%;

    background:
        rgba(
            62,
            184,
            255,
            .18
        );

}


/* ==========================================================================
   HERO CONTENT
   ========================================================================== */

.vendor-hero-content {

    position:
        relative;

    z-index:
        2;

    max-width:
        760px;

}


/* ==========================================================================
   HERO LABEL
   ========================================================================== */

.vendor-hero-label {

    width:
        fit-content;

    min-height:
        38px;

    padding:
        0 15px;

    margin-bottom:
        20px;

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
            .12
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .24
        );

    border-radius:
        999px;

    font-size:
        12px;

    font-weight:
        800;

    letter-spacing:
        .85px;

    text-transform:
        uppercase;

}


/* ==========================================================================
   HERO TITLE
   ========================================================================== */

.vendor-hero h1 {

    margin:
        0 0 18px;

    color:
        #ffffff;

    font-size:
        clamp(
            42px,
            5vw,
            61px
        );

    line-height:
        1.05;

    font-weight:
        800;

    letter-spacing:
        -2.4px;

}


.vendor-hero h1 span {

    color:
        #5fe5f4;

}


/* ==========================================================================
   HERO TEXT
   ========================================================================== */

.vendor-hero p {

    max-width:
        660px;

    margin:
        0;

    color:
        rgba(
            255,
            255,
            255,
            .87
        );

    font-size:
        17px;

    line-height:
        1.75;

    font-weight:
        400;

}


/* ==========================================================================
   SECTION HEADING
   ========================================================================== */

.vendor-section-heading {

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        24px;

    margin-bottom:
        27px;

}


.vendor-section-heading h2 {

    margin:
        0 0 9px;

    color:
        #08265a;

    font-size:
        32px;

    line-height:
        1.15;

    font-weight:
        800;

    letter-spacing:
        -1px;

}


.vendor-section-heading p {

    margin:
        0;

    color:
        #8193b1;

    font-size:
        14px;

}


/* ==========================================================================
   COUNT
   ========================================================================== */

.vendor-count-pill {

    min-height:
        41px;

    padding:
        0 17px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #1d5dd4;

    background:
        #eaf2ff;

    border-radius:
        999px;

    font-size:
        13px;

    font-weight:
        800;

    white-space:
        nowrap;

}


/* ==========================================================================
   MARKETPLACE GRID
   ========================================================================== */

.vendor-grid {

    display:
        grid;

    grid-template-columns:

        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap:
        22px;

}


/* ==========================================================================
   VENDOR CARD
   ========================================================================== */

.vendor-card {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        320px;

    padding:
        29px;

    display:
        flex;

    flex-direction:
        column;

    background:
        #ffffff;

    border:
        1px solid
        #dce7f3;

    border-radius:
        24px;

    box-shadow:

        0
        10px
        28px
        rgba(
            31,
            69,
            125,
            .045
        );

    transition:
        transform .22s ease,
        box-shadow .22s ease,
        border-color .22s ease;

}


.vendor-card::after {

    content:
        "";

    position:
        absolute;

    width:
        110px;

    height:
        110px;

    right:
        -42px;

    bottom:
        -48px;

    border-radius:
        50%;

    background:
        #edf4ff;

}


.vendor-card:nth-child(3n + 2)::after {

    background:
        #f0ebff;

}


.vendor-card:nth-child(3n + 3)::after {

    background:
        #fff1ed;

}


.vendor-card:hover {

    transform:
        translateY(-5px);

    border-color:
        #bfd8fa;

    box-shadow:

        0
        18px
        42px
        rgba(
            31,
            69,
            125,
            .11
        );

}


/* ==========================================================================
   VENDOR LOGO
   ========================================================================== */

.vendor-card-logo {

    position:
        relative;

    z-index:
        2;

    width:
        72px;

    height:
        72px;

    margin-bottom:
        22px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    overflow:
        hidden;

    background:
        #edf5ff;

    border:
        1px solid
        #dceaff;

    border-radius:
        19px;

    text-decoration:
        none;

}


.vendor-card-logo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.vendor-card-placeholder {

    width:
        100%;

    height:
        100%;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        31px;

}


/* ==========================================================================
   APPROVED LABEL
   ========================================================================== */

.vendor-approved {

    position:
        relative;

    z-index:
        2;

    width:
        fit-content;

    margin-bottom:
        10px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #16a34a;

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        .7px;

    text-transform:
        uppercase;

}


.vendor-approved::before {

    content:
        "";

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        #22c55e;

}


/* ==========================================================================
   CARD TITLE
   ========================================================================== */

.vendor-card h3 {

    position:
        relative;

    z-index:
        2;

    margin:
        0 0 8px;

    color:
        #092b5f;

    font-size:
        18px;

    line-height:
        1.3;

    font-weight:
        800;

}


.vendor-card h3 a {

    color:
        inherit;

    text-decoration:
        none;

}


.vendor-card h3 a:hover {

    color:
        #2563eb;

}


/* ==========================================================================
   CATEGORY PILL
   ========================================================================== */

.vendor-category {

    position:
        relative;

    z-index:
        2;

    width:
        fit-content;

    margin-bottom:
        13px;

    padding:
        5px 9px;

    color:
        #2563eb;

    background:
        #edf5ff;

    border:
        1px solid
        #dceaff;

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        700;

}


/* ==========================================================================
   DESCRIPTION
   ========================================================================== */

.vendor-card-description {

    position:
        relative;

    z-index:
        2;

    margin:
        0 0 17px;

    color:
        #7c8daa;

    font-size:
        11px;

    line-height:
        1.65;

}


/* ==========================================================================
   CARD FOOTER
   ========================================================================== */

.vendor-card-footer {

    position:
        relative;

    z-index:
        2;

    margin-top:
        auto;

    padding-top:
        16px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    border-top:
        1px solid
        #edf1f6;

}


.vendor-delivery {

    color:
        #7487a5;

    font-size:
        9px;

    font-weight:
        600;

}


.vendor-store-link {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #2563eb;

    font-size:
        10px;

    font-weight:
        800;

    text-decoration:
        none;

}


.vendor-store-link:hover {

    gap:
        9px;

}


/* ==========================================================================
   EMPTY
   ========================================================================== */

.vendor-empty {

    padding:
        72px 25px;

    text-align:
        center;

    background:
        #f5f9ff;

    border:
        1px dashed
        #bdd8f7;

    border-radius:
        24px;

}


.vendor-empty-icon {

    width:
        64px;

    height:
        64px;

    margin:
        0 auto 14px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #e9f2ff;

    border-radius:
        18px;

    font-size:
        28px;

}


.vendor-empty h3 {

    margin:
        0 0 7px;

    color:
        #143564;

    font-size:
        18px;

    font-weight:
        800;

}


.vendor-empty p {

    margin:
        0;

    color:
        #8596ad;

    font-size:
        11px;

}


/* ==========================================================================
   CTA
   ========================================================================== */

.vendor-cta {

    margin-top:
        70px;

    padding:
        65px 20px;

    text-align:
        center;

    color:
        #ffffff;

    background:

        linear-gradient(
            110deg,
            #082b6b,
            #1b54b7,
            #2886ef
        );

    border-radius:
        28px;

    position:
        relative;

    overflow:
        hidden;

}


.vendor-cta::before {

    content:
        "";

    position:
        absolute;

    width:
        220px;

    height:
        220px;

    right:
        -70px;

    top:
        -115px;

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


.vendor-cta-content {

    position:
        relative;

    z-index:
        2;

}


.vendor-cta-label {

    display:
        inline-block;

    margin-bottom:
        9px;

    color:
        #9cd0ff;

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        1px;

    text-transform:
        uppercase;

}


.vendor-cta h2 {

    margin:
        0 0 10px;

    color:
        #ffffff;

    font-size:
        30px;

    font-weight:
        800;

}


.vendor-cta p {

    max-width:
        590px;

    margin:
        0 auto 20px;

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size:
        11px;

    line-height:
        1.7;

}


.vendor-cta-button {

    min-height:
        43px;

    padding:
        0 17px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    color:
        #1c5eb9;

    background:
        #ffffff;

    border-radius:
        10px;

    box-shadow:

        0
        9px
        25px
        rgba(
            0,
            20,
            70,
            .18
        );

    font-size:
        10px;

    font-weight:
        800;

    text-decoration:
        none;

}


/* ==========================================================================
   SINGLE VENDOR DETAIL HERO
   ========================================================================== */

.vendor-detail-hero-content {

    position:
        relative;

    z-index:
        2;

    width:
        100%;

}


.vendor-back {

    display:
        inline-flex;

    margin-bottom:
        23px;

    color:
        #d4e9ff;

    font-size:
        10px;

    font-weight:
        700;

    text-decoration:
        none;

}


.vendor-detail-grid {

    display:
        grid;

    grid-template-columns:
        auto 1fr;

    align-items:
        center;

    gap:
        30px;

}


.vendor-detail-logo {

    width:
        135px;

    height:
        135px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

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
            .24
        );

    border-radius:
        28px;

    backdrop-filter:
        blur(10px);

    box-shadow:

        0
        18px
        40px
        rgba(
            0,
            25,
            75,
            .22
        );

}


.vendor-detail-logo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.vendor-detail-placeholder {

    font-size:
        52px;

}


.vendor-detail-heading {

    max-width:
        760px;

}


.vendor-detail-category {

    display:
        inline-flex;

    margin-bottom:
        11px;

    padding:
        6px 10px;

    color:
        #ffffff;

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
            .18
        );

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        700;

}


.vendor-detail-meta {

    margin-top:
        17px;

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        10px;

}


.vendor-detail-meta span {

    min-height:
        38px;

    padding:
        0 12px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            .10
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .16
        );

    border-radius:
        10px;

    font-size:
        9px;

}


/* ==========================================================================
   PRODUCT GRID
   ========================================================================== */

.vendor-products-grid {

    display:
        grid;

    grid-template-columns:

        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap:
        22px;

}


.vendor-product-card {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        365px;

    display:
        flex;

    flex-direction:
        column;

    background:
        #ffffff;

    border:
        1px solid
        #dce7f3;

    border-radius:
        24px;

    box-shadow:

        0
        10px
        28px
        rgba(
            31,
            69,
            125,
            .045
        );

    transition:
        transform .22s ease,
        box-shadow .22s ease;

}


.vendor-product-card:hover {

    transform:
        translateY(-5px);

    box-shadow:

        0
        18px
        42px
        rgba(
            31,
            69,
            125,
            .11
        );

}


.vendor-product-image {

    height:
        205px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    overflow:
        hidden;

    background:
        #edf5ff;

    text-decoration:
        none;

}


.vendor-product-image img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    transition:
        transform .3s ease;

}


.vendor-product-card:hover
.vendor-product-image img {

    transform:
        scale(1.04);

}


.vendor-product-placeholder {

    font-size:
        40px;

}


.vendor-product-body {

    flex:
        1;

    padding:
        20px;

    display:
        flex;

    flex-direction:
        column;

}


.vendor-product-body h3 {

    margin:
        0 0 8px;

    color:
        #0a2c61;

    font-size:
        17px;

    line-height:
        1.35;

    font-weight:
        800;

}


.vendor-product-body h3 a {

    color:
        inherit;

    text-decoration:
        none;

}


.vendor-product-body p {

    margin:
        0 0 15px;

    color:
        #7c8daa;

    font-size:
        10px;

    line-height:
        1.65;

}


.vendor-product-footer {

    margin-top:
        auto;

    padding-top:
        15px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    border-top:
        1px solid
        #edf1f6;

}


.vendor-product-price {

    color:
        #0b326d;

    font-size:
        16px;

    font-weight:
        800;

}


.vendor-product-link {

    color:
        #2563eb;

    font-size:
        9px;

    font-weight:
        800;

    text-decoration:
        none;

}


/* ==========================================================================
   RESPONSIVE
   ========================================================================== */

@media (
    max-width: 1050px
) {

    .vendor-grid,
    .vendor-products-grid {

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
    max-width: 800px
) {

    .vendor-hero {

        min-height:
            auto;

        padding:
            44px 35px;

    }


    .vendor-detail-grid {

        grid-template-columns:
            1fr;

    }

}


@media (
    max-width: 650px
) {

    .vendor-container {

        width:
            calc(
                100% - 26px
            );

    }


    .vendor-page-inner {

        padding:
            30px 0 55px;

    }


    .vendor-hero {

        padding:
            35px 25px;

        margin-bottom:
            38px;

        border-radius:
            23px;

    }


    .vendor-hero-label {

        min-height:
            34px;

        font-size:
            9px;

    }


    .vendor-hero h1 {

        font-size:
            36px;

    }


    .vendor-hero p {

        font-size:
            12px;

    }


    .vendor-section-heading {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .vendor-section-heading h2 {

        font-size:
            26px;

    }


    .vendor-grid,
    .vendor-products-grid {

        grid-template-columns:
            1fr;

    }


    .vendor-card {

        min-height:
            300px;

    }


    .vendor-cta {

        margin-top:
            48px;

        padding:
            48px 22px;

    }


    .vendor-detail-logo {

        width:
            110px;

        height:
            110px;

    }

}

</style>



<main class="vendor-page">


    <div class="vendor-page-inner">


        <div class="vendor-container">


        <?php if ($vendorId > 0): ?>


            <!-- =====================================================
                 SINGLE VENDOR HERO
            ====================================================== -->

            <section class="vendor-hero">


                <div class="vendor-detail-hero-content">


                    <a
                        href="<?= e(BASE_URL) ?>vendor.php"
                        class="vendor-back"
                    >

                        ← Back to Vendors

                    </a>


                    <div class="vendor-detail-grid">


                        <!-- LOGO -->

                        <div class="vendor-detail-logo">


                            <?php if (
                                !empty(
                                    $vendor[
                                        'business_logo'
                                    ]
                                )
                            ): ?>


                                <img
                                    src="<?= e(
                                        vendorLogoUrl(
                                            $vendor[
                                                'business_logo'
                                            ]
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $vendor[
                                            'business_name'
                                        ]
                                    ) ?>"
                                    onerror="
                                        this.style.display='none';
                                        this.parentElement.innerHTML='<div class=&quot;vendor-detail-placeholder&quot;>🏪</div>';
                                    "
                                >


                            <?php else: ?>


                                <div class="vendor-detail-placeholder">

                                    🏪

                                </div>


                            <?php endif; ?>


                        </div>


                        <!-- DETAILS -->

                        <div class="vendor-detail-heading">


                            <div class="vendor-hero-label">

                                ✦ HochipoHub Vendor

                            </div>


                            <h1>

                                <?= e(
                                    $vendor[
                                        'business_name'
                                    ]
                                ) ?>

                            </h1>


                            <?php if (
                                !empty(
                                    $vendor[
                                        'category'
                                    ]
                                )
                            ): ?>


                                <span class="vendor-detail-category">

                                    <?= e(
                                        $vendor[
                                            'category'
                                        ]
                                    ) ?>

                                </span>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $vendor[
                                        'business_description'
                                    ]
                                )
                            ): ?>


                                <p>

                                    <?= nl2br(
                                        e(
                                            $vendor[
                                                'business_description'
                                            ]
                                        )
                                    ) ?>

                                </p>


                            <?php else: ?>


                                <p>

                                    Discover products available
                                    from this local HochipoHub vendor.

                                </p>


                            <?php endif; ?>


                            <div class="vendor-detail-meta">


                                <?php if (
                                    !empty(
                                        $vendor[
                                            'delivery_method'
                                        ]
                                    )
                                ): ?>


                                    <span>

                                        🚚

                                        <?= e(
                                            $vendor[
                                                'delivery_method'
                                            ]
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                                <span>

                                    📦

                                    <?= number_format(
                                        count(
                                            $products
                                        )
                                    ) ?>

                                    Product<?= count($products) !== 1
                                        ? 's'
                                        : '' ?>

                                </span>


                            </div>


                        </div>


                    </div>


                </div>


            </section>



            <!-- =====================================================
                 PRODUCTS
            ====================================================== -->

            <section>


                <div class="vendor-section-heading">


                    <div>


                        <h2>

                            Explore Products

                        </h2>


                        <p>

                            Browse products available from
                            <?= e(
                                $vendor[
                                    'business_name'
                                ]
                            ) ?>.

                        </p>


                    </div>


                    <span class="vendor-count-pill">

                        <?= number_format(
                            count(
                                $products
                            )
                        ) ?>

                        Products

                    </span>


                </div>



                <?php if (
                    !empty(
                        $products
                    )
                ): ?>


                    <div class="vendor-products-grid">


                        <?php foreach (
                            $products
                            as $product
                        ): ?>


                            <?php

                            $productId =
                                (int)
                                $product[
                                    'product_id'
                                ];


                            $productImage =
                                vendorProductImageUrl(
                                    $product[
                                        'image'
                                    ]
                                    ?? ''
                                );

                            ?>


                            <article class="vendor-product-card">


                                <a
                                    href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                    class="vendor-product-image"
                                >


                                    <?php if (
                                        !empty(
                                            $product[
                                                'image'
                                            ]
                                        )
                                    ): ?>


                                        <img
                                            src="<?= e(
                                                $productImage
                                            ) ?>"
                                            alt="<?= e(
                                                $product[
                                                    'product_name'
                                                ]
                                            ) ?>"
                                            loading="lazy"
                                            onerror="
                                                this.style.display='none';
                                                this.parentElement.innerHTML='<div class=&quot;vendor-product-placeholder&quot;>🛍️</div>';
                                            "
                                        >


                                    <?php else: ?>


                                        <div class="vendor-product-placeholder">

                                            🛍️

                                        </div>


                                    <?php endif; ?>


                                </a>


                                <div class="vendor-product-body">


                                    <span class="vendor-category">

                                        <?= e(
                                            $product[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </span>


                                    <h3>


                                        <a
                                            href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                        >

                                            <?= e(
                                                $product[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </a>


                                    </h3>


                                    <?php if (
                                        !empty(
                                            $product[
                                                'description'
                                            ]
                                        )
                                    ): ?>


                                        <p>

                                            <?= e(
                                                mb_strimwidth(
                                                    $product[
                                                        'description'
                                                    ],
                                                    0,
                                                    100,
                                                    '...'
                                                )
                                            ) ?>

                                        </p>


                                    <?php endif; ?>


                                    <div class="vendor-product-footer">


                                        <span class="vendor-product-price">

                                            RM
                                            <?= number_format(
                                                (float)
                                                $product[
                                                    'price'
                                                ],
                                                2
                                            ) ?>

                                        </span>


                                        <a
                                            href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
                                            class="vendor-product-link"
                                        >

                                            View Product →

                                        </a>


                                    </div>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="vendor-empty">


                        <div class="vendor-empty-icon">

                            📦

                        </div>


                        <h3>

                            No products available

                        </h3>


                        <p>

                            This vendor has not listed
                            any available products yet.

                        </p>


                    </div>


                <?php endif; ?>


            </section>



        <?php else: ?>


            <!-- =====================================================
                 ALL VENDORS HERO
            ====================================================== -->

            <section class="vendor-hero">


                <div class="vendor-hero-content">


                    <div class="vendor-hero-label">

                        ✦ HochipoHub Vendors

                    </div>


                    <h1>

                        Meet Our

                        <span>
                            Vendors.
                        </span>

                    </h1>


                    <p>

                        Discover local businesses and creators
                        behind the products on HochipoHub and
                        explore what each seller has to offer.

                    </p>


                </div>


            </section>



            <!-- =====================================================
                 VENDOR LIST
            ====================================================== -->

            <section>


                <div class="vendor-section-heading">


                    <div>


                        <h2>

                            Explore Vendors

                        </h2>


                        <p>

                            Choose a local seller and explore their store.

                        </p>


                    </div>


                    <span class="vendor-count-pill">

                        <?= number_format(
                            count(
                                $vendors
                            )
                        ) ?>

                        Vendor<?= count($vendors) !== 1
                            ? 's'
                            : '' ?>

                    </span>


                </div>



                <?php if (
                    !empty(
                        $vendors
                    )
                ): ?>


                    <div class="vendor-grid">


                        <?php foreach (
                            $vendors as $item
                        ): ?>


                            <?php

                            $itemId =
                                (int)
                                $item[
                                    'vendor_id'
                                ];


                            $logo =
                                vendorLogoUrl(
                                    $item[
                                        'business_logo'
                                    ]
                                    ?? ''
                                );

                            ?>


                            <article class="vendor-card">


                                <!-- LOGO -->

                                <a
                                    href="<?= e(BASE_URL) ?>vendor.php?id=<?= $itemId ?>"
                                    class="vendor-card-logo"
                                >


                                    <?php if (
                                        !empty(
                                            $item[
                                                'business_logo'
                                            ]
                                        )
                                    ): ?>


                                        <img
                                            src="<?= e(
                                                $logo
                                            ) ?>"
                                            alt="<?= e(
                                                $item[
                                                    'business_name'
                                                ]
                                            ) ?>"
                                            loading="lazy"
                                            onerror="
                                                this.style.display='none';
                                                this.parentElement.innerHTML='<div class=&quot;vendor-card-placeholder&quot;>🏪</div>';
                                            "
                                        >


                                    <?php else: ?>


                                        <div class="vendor-card-placeholder">

                                            🏪

                                        </div>


                                    <?php endif; ?>


                                </a>


                                <!-- APPROVED -->

                                <span class="vendor-approved">

                                    Approved Vendor

                                </span>


                                <!-- NAME -->

                                <h3>


                                    <a
                                        href="<?= e(BASE_URL) ?>vendor.php?id=<?= $itemId ?>"
                                    >

                                        <?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </a>


                                </h3>


                                <!-- CATEGORY -->

                                <?php if (
                                    !empty(
                                        $item[
                                            'category'
                                        ]
                                    )
                                ): ?>


                                    <span class="vendor-category">

                                        <?= e(
                                            $item[
                                                'category'
                                            ]
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                                <!-- DESCRIPTION -->

                                <?php if (
                                    !empty(
                                        $item[
                                            'business_description'
                                        ]
                                    )
                                ): ?>


                                    <p class="vendor-card-description">

                                        <?= e(
                                            mb_strimwidth(
                                                $item[
                                                    'business_description'
                                                ],
                                                0,
                                                130,
                                                '...'
                                            )
                                        ) ?>

                                    </p>


                                <?php else: ?>


                                    <p class="vendor-card-description">

                                        Discover products from this
                                        local seller on HochipoHub.

                                    </p>


                                <?php endif; ?>


                                <!-- FOOTER -->

                                <div class="vendor-card-footer">


                                    <?php if (
                                        !empty(
                                            $item[
                                                'delivery_method'
                                            ]
                                        )
                                    ): ?>


                                        <span class="vendor-delivery">

                                            🚚
                                            <?= e(
                                                $item[
                                                    'delivery_method'
                                                ]
                                            ) ?>

                                        </span>


                                    <?php else: ?>


                                        <span class="vendor-delivery">

                                            🏪 Local Vendor

                                        </span>


                                    <?php endif; ?>


                                    <a
                                        href="<?= e(BASE_URL) ?>vendor.php?id=<?= $itemId ?>"
                                        class="vendor-store-link"
                                    >

                                        Explore

                                        <span>
                                            →
                                        </span>

                                    </a>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="vendor-empty">


                        <div class="vendor-empty-icon">

                            🏪

                        </div>


                        <h3>

                            No vendors available

                        </h3>


                        <p>

                            Approved HochipoHub vendors will
                            appear here.

                        </p>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =====================================================
                 CTA
            ====================================================== -->

            <section class="vendor-cta">


                <div class="vendor-cta-content">


                    <span class="vendor-cta-label">

                        Sell With HochipoHub

                    </span>


                    <h2>

                        Ready to become a vendor?

                    </h2>


                    <p>

                        Put your products in front of more customers
                        and grow your local business through HochipoHub.

                    </p>


                    <a
                        href="<?= e(BASE_URL) ?>index.php?register=1"
                        class="vendor-cta-button"
                    >

                        Start Selling

                        <span>
                            →
                        </span>

                    </a>


                </div>


            </section>


        <?php endif; ?>


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


<script src="<?= e(BASE_URL) ?>js/script.js"></script>

<script src="<?= e(BASE_URL) ?>js/cart.js"></script>


</body>

</html>