<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM PRODUCT LISTING
|--------------------------------------------------------------------------
| File:
| product.php
|--------------------------------------------------------------------------
|
| Public marketplace product listing.
|
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
| DATABASE CONNECTION
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
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('productPageEscape')) {

    function productPageEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('productPageImage')) {

    function productPageImage($image): string
    {
        $image =
            trim(
                (string) $image
            );


        if ($image === '') {

            return 'image/logo.jpg';
        }


        /*
        |--------------------------------------------------------------------------
        | FULL URL
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $image,
                'http://'
            )
            ||
            str_starts_with(
                $image,
                'https://'
            )
        ) {

            return $image;
        }


        /*
        |--------------------------------------------------------------------------
        | ALREADY HAS UPLOAD PATH
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $image,
                'uploads/'
            )
        ) {

            return $image;
        }


        /*
        |--------------------------------------------------------------------------
        | NORMAL PRODUCT IMAGE
        |--------------------------------------------------------------------------
        */

        return
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
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim(
        (string) (
            $_GET['search']
            ?? ''
        )
    );


$category_id =
    (int) (
        $_GET['category_id']
        ?? 0
    );


$vendor_id =
    (int) (
        $_GET['vendor_id']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];


try {

    $stmt =
        $db->query("
            SELECT
                category_id,
                category_name

            FROM categories

            ORDER BY
                category_name ASC
        ");


    $categories =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $categories = [];
}


/*
|--------------------------------------------------------------------------
| VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];


try {

    $stmt =
        $db->query("
            SELECT

                vendor_id,
                business_name

            FROM vendors

            WHERE approval_status = 'Approved'

            ORDER BY
                business_name ASC
        ");


    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $vendors = [];
}


/*
|--------------------------------------------------------------------------
| BUILD PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$sql = "
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

        v.business_name,

        c.category_name,

        COALESCE(
            AVG(
                CASE
                    WHEN r.status = 'Visible'
                    THEN r.rating
                END
            ),
            0
        ) AS average_rating,

        COUNT(
            CASE
                WHEN r.status = 'Visible'
                THEN r.review_id
            END
        ) AS review_count

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id =
           v.vendor_id

    INNER JOIN categories c
        ON p.category_id =
           c.category_id

    LEFT JOIN reviews r
        ON p.product_id =
           r.product_id

    WHERE p.status != 'Hidden'

    AND v.approval_status = 'Approved'
";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND
        (
            p.product_name LIKE ?

            OR p.description LIKE ?

            OR v.business_name LIKE ?

            OR c.category_name LIKE ?
        )
    ";


    $searchValue =
        '%' .
        $search .
        '%';


    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($category_id > 0) {

    $sql .= "
        AND p.category_id = ?
    ";


    $params[] =
        $category_id;
}


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

if ($vendor_id > 0) {

    $sql .= "
        AND p.vendor_id = ?
    ";


    $params[] =
        $vendor_id;
}


/*
|--------------------------------------------------------------------------
| GROUP + SORT
|--------------------------------------------------------------------------
*/

$sql .= "
    GROUP BY

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

        v.business_name,

        c.category_name

    ORDER BY
        p.created_at DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE PRODUCT QUERY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $products = [];
}


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$productCount =
    count(
        $products
    );


$categoryCount =
    count(
        $categories
    );


$vendorCount =
    count(
        $vendors
    );


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Products - ' .
    SITE_NAME;


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/navbar.php';

?>


<style>

/* ================================================================
   PAGE
================================================================ */

.hh-products-page {

    width:
        100%;

    min-height:
        100vh;

    padding:
        42px
        24px
        75px;

    overflow-x:
        hidden;

    color:
        #14213d;

    background:

        radial-gradient(
            circle at 90% 2%,
            rgba(59,130,246,.08),
            transparent 25%
        ),

        linear-gradient(
            180deg,
            #f5f8ff 0%,
            #f8faff 55%,
            #ffffff 100%
        );

    font-family:
        Inter,
        Arial,
        sans-serif;

}


.hh-products-container {

    width:
        100%;

    max-width:
        1340px;

    margin:
        0 auto;

}


/* ================================================================
   HERO
================================================================ */

.hh-products-hero {

    position:
        relative;

    min-height:
        320px;

    margin-bottom:
        22px;

    padding:
        47px
        50px;

    overflow:
        hidden;

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        360px;

    align-items:
        center;

    gap:
        40px;

    color:
        #ffffff;

    background:

        linear-gradient(
            115deg,
            #0b2c6b 0%,
            #154a98 48%,
            #2784ee 100%
        );

    border-radius:
        28px;

    box-shadow:

        0
        20px
        50px
        rgba(23,79,165,.16);

}


.hh-products-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        305px;

    height:
        305px;

    top:
        -160px;

    right:
        -65px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.08);

}


.hh-products-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        190px;

    height:
        190px;

    right:
        180px;

    bottom:
        -138px;

    border-radius:
        50%;

    background:
        rgba(111,231,243,.11);

}


.hh-products-hero-copy {

    position:
        relative;

    z-index:
        2;

}


.hh-products-pill {

    min-height:
        33px;

    padding:
        0
        13px;

    margin-bottom:
        17px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        900;

    letter-spacing:
        .4px;

}


.hh-products-hero h1 {

    margin:
        0;

    color:
        #ffffff;

    font-family:
        Poppins,
        Inter,
        Arial,
        sans-serif;

    font-size:

        clamp(
            36px,
            4.6vw,
            56px
        );

    line-height:
        1.08;

    font-weight:
        800;

    letter-spacing:
        -1.8px;

}


.hh-products-hero h1 span {

    color:
        #6fe7f3;

}


.hh-products-hero p {

    max-width:
        600px;

    margin:
        15px
        0
        0;

    color:
        rgba(255,255,255,.77);

    font-size:
        11px;

    line-height:
        1.75;

}


/* ================================================================
   HERO SEARCH
================================================================ */

.hh-products-hero-search {

    max-width:
        620px;

    margin-top:
        24px;

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        auto;

    gap:
        8px;

}


.hh-products-search-box {

    height:
        49px;

    padding:
        0
        14px;

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    background:
        #ffffff;

    border-radius:
        12px;

    box-shadow:

        0
        8px
        20px
        rgba(5,35,85,.12);

}


.hh-products-search-box i {

    color:
        #2563eb;

    font-size:
        14px;

}


.hh-products-search-box input {

    width:
        100%;

    height:
        100%;

    padding:
        0;

    outline:
        none;

    color:
        #34445d;

    background:
        transparent;

    border:
        0;

    font-family:
        inherit;

    font-size:
        10px;

}


.hh-products-search-button {

    min-width:
        105px;

    height:
        49px;

    padding:
        0
        15px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    color:
        #1757ad;

    background:
        #ffffff;

    border:
        0;

    border-radius:
        12px;

    box-shadow:

        0
        8px
        20px
        rgba(5,35,85,.12);

    font-family:
        inherit;

    font-size:
        9px;

    font-weight:
        900;

    cursor:
        pointer;

}


/* ================================================================
   HERO VISUAL
================================================================ */

.hh-products-art {

    position:
        relative;

    z-index:
        2;

    height:
        220px;

}


.hh-products-art-main {

    position:
        absolute;

    width:
        150px;

    height:
        150px;

    top:
        30px;

    right:
        80px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius:
        39px;

    backdrop-filter:
        blur(12px);

    font-size:
        61px;

    transform:
        rotate(-4deg);

}


.hh-products-float {

    position:
        absolute;

    min-width:
        145px;

    padding:
        11px
        13px;

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        #26405f;

    background:
        rgba(255,255,255,.96);

    border-radius:
        12px;

    box-shadow:

        0
        14px
        32px
        rgba(5,35,80,.17);

    font-size:
        8px;

    font-weight:
        850;

}


.hh-products-float i {

    width:
        31px;

    height:
        31px;

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
        9px;

}


.hh-products-float.one {

    top:
        4px;

    left:
        0;

}


.hh-products-float.two {

    right:
        0;

    bottom:
        4px;

}


/* ================================================================
   STATS
================================================================ */

.hh-products-stats {

    margin-bottom:
        22px;

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
        14px;

}


.hh-products-stat {

    min-height:
        94px;

    padding:
        17px;

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    background:
        #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius:
        17px;

    box-shadow:

        0
        8px
        24px
        rgba(40,65,120,.045);

}


.hh-products-stat-icon {

    width:
        44px;

    height:
        44px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        12px;

    font-size:
        15px;

}


.hh-products-stat-icon.blue {

    color:
        #2563eb;

    background:
        #eff6ff;

}


.hh-products-stat-icon.purple {

    color:
        #7c3aed;

    background:
        #f5f3ff;

}


.hh-products-stat-icon.green {

    color:
        #15803d;

    background:
        #ecfdf3;

}


.hh-products-stat span {

    display:
        block;

    margin-bottom:
        4px;

    color:
        #8a98aa;

    font-size:
        6px;

    font-weight:
        850;

    letter-spacing:
        .7px;

}


.hh-products-stat strong {

    display:
        block;

    color:
        #17233c;

    font-size:
        19px;

    font-weight:
        900;

}


/* ================================================================
   FILTER PANEL
================================================================ */

.hh-products-filter {

    margin-bottom:
        22px;

    padding:
        21px;

    background:
        #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius:
        20px;

    box-shadow:

        0
        10px
        27px
        rgba(40,65,120,.045);

}


.hh-products-filter-top {

    margin-bottom:
        16px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

}


.hh-products-filter-title {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

}


.hh-products-filter-icon {

    width:
        43px;

    height:
        43px;

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
            #438bf2
        );

    border-radius:
        12px;

    font-size:
        14px;

}


.hh-products-filter-title small {

    display:
        block;

    margin-bottom:
        2px;

    color:
        #2563eb;

    font-size:
        6px;

    font-weight:
        900;

    letter-spacing:
        .8px;

}


.hh-products-filter-title h2 {

    margin:
        0;

    color:
        #17233c;

    font-size:
        14px;

    font-weight:
        900;

}


.hh-products-filter-count {

    min-height:
        30px;

    padding:
        0
        10px;

    display:
        inline-flex;

    align-items:
        center;

    color:
        #2563eb;

    background:
        #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius:
        999px;

    font-size:
        7px;

    font-weight:
        900;

}


/* ================================================================
   FILTER FORM
================================================================ */

.hh-product-filter-form {

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            2fr
        )

        minmax(
            160px,
            1fr
        )

        minmax(
            160px,
            1fr
        )

        auto
        auto;

    align-items:
        end;

    gap:
        11px;

}


.hh-filter-group label {

    display:
        block;

    margin-bottom:
        7px;

    color:
        #53647b;

    font-size:
        7px;

    font-weight:
        900;

}


.hh-filter-control {

    position:
        relative;

}


.hh-filter-control i {

    position:
        absolute;

    left:
        13px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        #2563eb;

    font-size:
        12px;

    pointer-events:
        none;

}


.hh-filter-control input,
.hh-filter-control select {

    width:
        100%;

    height:
        44px;

    padding:
        0
        13px;

    outline:
        none;

    color:
        #34445d;

    background:
        #fbfdff;

    border:
        1px solid #dce5ef;

    border-radius:
        10px;

    font-family:
        inherit;

    font-size:
        8px;

    transition:
        .18s ease;

}


.hh-filter-control.has-icon input {

    padding-left:
        37px;

}


.hh-filter-control input:focus,
.hh-filter-control select:focus {

    background:
        #ffffff;

    border-color:
        #3b82f6;

    box-shadow:

        0
        0
        0
        3px
        rgba(59,130,246,.08);

}


.hh-filter-search-button,
.hh-filter-reset-button {

    height:
        44px;

    padding:
        0
        14px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    border-radius:
        10px;

    font-family:
        inherit;

    font-size:
        8px;

    font-weight:
        900;

    text-decoration:
        none;

    cursor:
        pointer;

}


.hh-filter-search-button {

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #377fef
        );

    border:
        0;

    box-shadow:

        0
        7px
        16px
        rgba(37,99,235,.18);

}


.hh-filter-reset-button {

    color:
        #64748b;

    background:
        #ffffff;

    border:
        1px solid #dce5ef;

}


/* ================================================================
   SECTION HEADER
================================================================ */

.hh-products-list-header {

    margin-bottom:
        15px;

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        16px;

}


.hh-products-list-header small {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #2563eb;

    font-size:
        6px;

    font-weight:
        900;

    letter-spacing:
        .8px;

}


.hh-products-list-header h2 {

    margin:
        0;

    color:
        #17233c;

    font-size:
        20px;

    font-weight:
        900;

}


.hh-products-list-header p {

    margin:
        4px
        0
        0;

    color:
        #8694a8;

    font-size:
        8px;

}


/* ================================================================
   PRODUCT GRID
================================================================ */

.hh-product-grid {

    display:
        grid;

    grid-template-columns:

        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap:
        18px;

}


/* ================================================================
   PRODUCT CARD
================================================================ */

.hh-product-card {

    min-width:
        0;

    overflow:
        hidden;

    display:
        flex;

    flex-direction:
        column;

    background:
        #ffffff;

    border:
        1px solid #e1e8f2;

    border-radius:
        18px;

    box-shadow:

        0
        8px
        24px
        rgba(40,65,120,.05);

    transition:

        transform .20s ease,
        box-shadow .20s ease,
        border-color .20s ease;

}


.hh-product-card:hover {

    transform:
        translateY(-5px);

    border-color:
        #c8dcf5;

    box-shadow:

        0
        16px
        34px
        rgba(40,65,120,.11);

}


/* ================================================================
   PRODUCT IMAGE
================================================================ */

.hh-product-image {

    position:
        relative;

    width:
        100%;

    height:
        220px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:

        linear-gradient(
            135deg,
            #f2f7ff,
            #e9f2ff
        );

}


.hh-product-image img {

    width:
        100%;

    height:
        100%;

    padding:
        10px;

    display:
        block;

    object-fit:
        contain;

    object-position:
        center;

    transition:
        transform .25s ease;

}


.hh-product-card:hover
.hh-product-image img {

    transform:
        scale(1.04);

}


.hh-product-category {

    position:
        absolute;

    top:
        11px;

    left:
        11px;

    z-index:
        3;

    min-height:
        25px;

    padding:
        0
        8px;

    display:
        inline-flex;

    align-items:
        center;

    color:
        #2563eb;

    background:
        rgba(255,255,255,.95);

    border:
        1px solid #dbeafe;

    border-radius:
        8px;

    backdrop-filter:
        blur(7px);

    font-size:
        6px;

    font-weight:
        900;

    letter-spacing:
        .3px;

}


.hh-product-stock {

    position:
        absolute;

    right:
        11px;

    bottom:
        11px;

    z-index:
        3;

    min-height:
        25px;

    padding:
        0
        8px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    border-radius:
        999px;

    font-size:
        6px;

    font-weight:
        900;

}


.hh-product-stock.in-stock {

    color:
        #15803d;

    background:
        rgba(240,253,244,.95);

    border:
        1px solid #bbf7d0;

}


.hh-product-stock.out-stock {

    color:
        #dc2626;

    background:
        rgba(254,242,242,.95);

    border:
        1px solid #fecaca;

}


/* ================================================================
   PRODUCT CONTENT
================================================================ */

.hh-product-content {

    flex:
        1;

    padding:
        16px;

    display:
        flex;

    flex-direction:
        column;

}


.hh-product-vendor {

    margin-bottom:
        7px;

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    color:
        #8291a6;

    font-size:
        7px;

    font-weight:
        750;

}


.hh-product-vendor i {

    color:
        #2563eb;

}


.hh-product-name {

    min-height:
        39px;

    margin:
        0;

    display:
        -webkit-box;

    overflow:
        hidden;

    -webkit-line-clamp:
        2;

    -webkit-box-orient:
        vertical;

    font-size:
        13px;

    line-height:
        1.45;

}


.hh-product-name a {

    color:
        #17233c;

    font-weight:
        900;

    text-decoration:
        none;

}


.hh-product-name a:hover {

    color:
        #2563eb;

}


.hh-product-description {

    min-height:
        35px;

    margin:
        8px
        0
        0;

    display:
        -webkit-box;

    overflow:
        hidden;

    color:
        #8997aa;

    font-size:
        7px;

    line-height:
        1.6;

    -webkit-line-clamp:
        2;

    -webkit-box-orient:
        vertical;

}


/* ================================================================
   RATING
================================================================ */

.hh-product-rating {

    margin-top:
        11px;

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

}


.hh-product-stars {

    color:
        #fbbf24;

    font-size:
        9px;

    letter-spacing:
        .5px;

}


.hh-product-rating strong {

    color:
        #475569;

    font-size:
        7px;

    font-weight:
        900;

}


.hh-product-rating span {

    color:
        #94a3b8;

    font-size:
        6px;

}


/* ================================================================
   PRICE
================================================================ */

.hh-product-price-row {

    margin-top:
        14px;

    padding-top:
        13px;

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        10px;

    border-top:
        1px solid #edf1f5;

}


.hh-product-price small {

    display:
        block;

    margin-bottom:
        2px;

    color:
        #98a4b4;

    font-size:
        5px;

    font-weight:
        900;

    letter-spacing:
        .7px;

}


.hh-product-price strong {

    display:
        block;

    color:
        #1d4e89;

    font-size:
        16px;

    font-weight:
        900;

}


.hh-product-stock-text {

    color:
        #718096;

    font-size:
        6px;

    font-weight:
        800;

}


/* ================================================================
   BUTTON
================================================================ */

.hh-product-view {

    width:
        100%;

    min-height:
        40px;

    margin-top:
        13px;

    padding:
        0
        13px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        8px;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #377fef
        );

    border-radius:
        10px;

    box-shadow:

        0
        7px
        16px
        rgba(37,99,235,.17);

    font-size:
        8px;

    font-weight:
        900;

    text-decoration:
        none;

    transition:
        .18s ease;

}


.hh-product-view:hover {

    color:
        #ffffff;

    transform:
        translateY(-1px);

    box-shadow:

        0
        10px
        22px
        rgba(37,99,235,.22);

}


/* ================================================================
   EMPTY
================================================================ */

.hh-product-empty {

    min-height:
        390px;

    padding:
        50px
        25px;

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
        #ffffff;

    border:
        1px dashed #bdd8f7;

    border-radius:
        20px;

}


.hh-product-empty-icon {

    width:
        70px;

    height:
        70px;

    margin-bottom:
        15px;

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
        19px;

    font-size:
        27px;

}


.hh-product-empty small {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #2563eb;

    font-size:
        7px;

    font-weight:
        900;

    letter-spacing:
        .9px;

}


.hh-product-empty h2 {

    margin:
        0;

    color:
        #17233c;

    font-size:
        21px;

    font-weight:
        900;

}


.hh-product-empty p {

    max-width:
        450px;

    margin:
        9px
        auto
        17px;

    color:
        #8492a6;

    font-size:
        9px;

    line-height:
        1.7;

}


.hh-product-empty a {

    min-height:
        40px;

    padding:
        0
        14px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #ffffff;

    background:
        #2563eb;

    border-radius:
        9px;

    font-size:
        8px;

    font-weight:
        900;

    text-decoration:
        none;

}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1180px) {

    .hh-product-grid {

        grid-template-columns:

            repeat(
                3,
                minmax(
                    0,
                    1fr
                )
            );

    }

}


@media (max-width: 960px) {

    .hh-products-hero {

        grid-template-columns:
            1fr;

    }


    .hh-products-art {

        display:
            none;

    }


    .hh-product-filter-form {

        grid-template-columns:

            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );

    }


    .hh-product-grid {

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


@media (max-width: 700px) {

    .hh-products-page {

        padding:
            29px
            17px
            60px;

    }


    .hh-products-stats {

        grid-template-columns:
            1fr;

    }


    .hh-product-filter-form {

        grid-template-columns:
            1fr;

    }


    .hh-filter-search-button,
    .hh-filter-reset-button {

        width:
            100%;

    }

}


@media (max-width: 560px) {

    .hh-products-page {

        padding:
            21px
            13px
            50px;

    }


    .hh-products-hero {

        padding:
            28px
            23px;

        border-radius:
            21px;

    }


    .hh-products-hero h1 {

        font-size:
            31px;

    }


    .hh-products-hero-search {

        grid-template-columns:
            1fr;

    }


    .hh-products-search-button {

        width:
            100%;

    }


    .hh-products-filter-top,
    .hh-products-list-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .hh-product-grid {

        grid-template-columns:
            1fr;

    }


    .hh-product-image {

        height:
            250px;

    }

}

</style>


<!-- ===============================================================
     PRODUCT PAGE
================================================================ -->

<main class="hh-products-page">


    <div class="hh-products-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-products-hero">


            <div class="hh-products-hero-copy">


                <span class="hh-products-pill">

                    <i class="bi bi-stars"></i>

                    HOCHIPOHUB MARKETPLACE

                </span>


                <h1>

                    Explore Products

                    <span>
                        Worth Discovering.
                    </span>

                </h1>


                <p>

                    Discover products from approved HochipoHub
                    sellers, explore different categories and
                    find something you will love.

                </p>


                <!-- HERO SEARCH -->

                <form
                    method="GET"
                    action="product.php"
                    class="hh-products-hero-search"
                >


                    <?php if (
                        $category_id > 0
                    ): ?>

                        <input
                            type="hidden"
                            name="category_id"
                            value="<?= $category_id ?>"
                        >

                    <?php endif; ?>


                    <?php if (
                        $vendor_id > 0
                    ): ?>

                        <input
                            type="hidden"
                            name="vendor_id"
                            value="<?= $vendor_id ?>"
                        >

                    <?php endif; ?>


                    <div class="hh-products-search-box">

                        <i class="bi bi-search"></i>

                        <input
                            type="search"
                            name="search"
                            value="<?= productPageEscape(
                                $search
                            ) ?>"
                            placeholder="Search products, sellers or categories..."
                        >

                    </div>


                    <button
                        type="submit"
                        class="hh-products-search-button"
                    >

                        Search

                        <i class="bi bi-arrow-right"></i>

                    </button>


                </form>


            </div>


            <!-- HERO ART -->

            <div class="hh-products-art">


                <div class="hh-products-art-main">

                    <i class="bi bi-bag-heart"></i>

                </div>


                <div class="hh-products-float one">

                    <i class="bi bi-box-seam"></i>

                    <span>

                        <?= number_format(
                            $productCount
                        ) ?>

                        products

                    </span>

                </div>


                <div class="hh-products-float two">

                    <i class="bi bi-shop"></i>

                    <span>

                        <?= number_format(
                            $vendorCount
                        ) ?>

                        sellers

                    </span>

                </div>


            </div>


        </section>


        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="hh-products-stats">


            <article class="hh-products-stat">


                <div class="
                    hh-products-stat-icon
                    blue
                ">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div>

                    <span>
                        PRODUCTS FOUND
                    </span>

                    <strong>

                        <?= number_format(
                            $productCount
                        ) ?>

                    </strong>

                </div>


            </article>


            <article class="hh-products-stat">


                <div class="
                    hh-products-stat-icon
                    purple
                ">

                    <i class="bi bi-grid"></i>

                </div>


                <div>

                    <span>
                        CATEGORIES
                    </span>

                    <strong>

                        <?= number_format(
                            $categoryCount
                        ) ?>

                    </strong>

                </div>


            </article>


            <article class="hh-products-stat">


                <div class="
                    hh-products-stat-icon
                    green
                ">

                    <i class="bi bi-shop"></i>

                </div>


                <div>

                    <span>
                        APPROVED SELLERS
                    </span>

                    <strong>

                        <?= number_format(
                            $vendorCount
                        ) ?>

                    </strong>

                </div>


            </article>


        </section>


        <!-- =======================================================
             FILTER
        ======================================================== -->

        <section class="hh-products-filter">


            <div class="hh-products-filter-top">


                <div class="hh-products-filter-title">


                    <div class="hh-products-filter-icon">

                        <i class="bi bi-sliders"></i>

                    </div>


                    <div>

                        <small>
                            FIND YOUR PRODUCT
                        </small>

                        <h2>
                            Search & Filter
                        </h2>

                    </div>


                </div>


                <span class="hh-products-filter-count">

                    <?= number_format(
                        $productCount
                    ) ?>

                    result<?= $productCount !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>


            <form
                method="GET"
                action="product.php"
                class="hh-product-filter-form"
            >


                <!-- SEARCH -->

                <div class="hh-filter-group">


                    <label for="searchFilter">

                        Product Search

                    </label>


                    <div class="
                        hh-filter-control
                        has-icon
                    ">


                        <i class="bi bi-search"></i>


                        <input
                            type="text"
                            name="search"
                            id="searchFilter"
                            value="<?= productPageEscape(
                                $search
                            ) ?>"
                            placeholder="Search products..."
                        >


                    </div>


                </div>


                <!-- CATEGORY -->

                <div class="hh-filter-group">


                    <label for="category_id">

                        Category

                    </label>


                    <div class="hh-filter-control">


                        <select
                            name="category_id"
                            id="category_id"
                        >


                            <option value="">

                                All Categories

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
                                    <?= $category_id ===
                                        (int)
                                        $category[
                                            'category_id'
                                        ]
                                            ? 'selected'
                                            : '' ?>
                                >

                                    <?= productPageEscape(
                                        $category[
                                            'category_name'
                                        ]
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                </div>


                <!-- VENDOR -->

                <div class="hh-filter-group">


                    <label for="vendor_id">

                        Seller

                    </label>


                    <div class="hh-filter-control">


                        <select
                            name="vendor_id"
                            id="vendor_id"
                        >


                            <option value="">

                                All Sellers

                            </option>


                            <?php foreach (
                                $vendors
                                as $vendor
                            ): ?>


                                <option
                                    value="<?= (int)
                                        $vendor[
                                            'vendor_id'
                                        ] ?>"
                                    <?= $vendor_id ===
                                        (int)
                                        $vendor[
                                            'vendor_id'
                                        ]
                                            ? 'selected'
                                            : '' ?>
                                >

                                    <?= productPageEscape(
                                        $vendor[
                                            'business_name'
                                        ]
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                </div>


                <!-- SEARCH BUTTON -->

                <button
                    type="submit"
                    class="hh-filter-search-button"
                >

                    <i class="bi bi-search"></i>

                    Search

                </button>


                <!-- RESET -->

                <a
                    href="product.php"
                    class="hh-filter-reset-button"
                >

                    <i class="bi bi-arrow-counterclockwise"></i>

                    Reset

                </a>


            </form>


        </section>


        <!-- =======================================================
             LIST HEADER
        ======================================================== -->

        <section class="hh-products-list-header">


            <div>

                <small>
                    MARKETPLACE PRODUCTS
                </small>

                <h2>

                    <?php if (
                        $search !== ''
                    ): ?>

                        Search Results

                    <?php elseif (
                        $category_id > 0
                    ): ?>

                        Category Products

                    <?php elseif (
                        $vendor_id > 0
                    ): ?>

                        Seller Products

                    <?php else: ?>

                        Latest Products

                    <?php endif; ?>

                </h2>


                <p>

                    <?= number_format(
                        $productCount
                    ) ?>

                    product<?= $productCount !== 1
                        ? 's'
                        : '' ?>

                    available to explore.

                </p>


            </div>


        </section>


        <!-- =======================================================
             PRODUCT GRID
        ======================================================== -->

        <?php if (
            empty(
                $products
            )
        ): ?>


            <section class="hh-product-empty">


                <div class="hh-product-empty-icon">

                    <i class="bi bi-search"></i>

                </div>


                <small>
                    NO RESULTS FOUND
                </small>


                <h2>
                    No products found
                </h2>


                <p>

                    We couldn't find products matching your
                    current filters. Try another keyword,
                    category or seller.

                </p>


                <a href="product.php">

                    <i class="bi bi-grid"></i>

                    View All Products

                </a>


            </section>


        <?php else: ?>


            <section class="hh-product-grid">


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


                    $image =
                        productPageImage(
                            $product[
                                'image'
                            ]
                            ?? ''
                        );


                    $averageRating =
                        (float)
                        $product[
                            'average_rating'
                        ];


                    $reviewCount =
                        (int)
                        $product[
                            'review_count'
                        ];


                    $stock =
                        (int)
                        $product[
                            'stock_quantity'
                        ];


                    $description =
                        trim(
                            (string) (
                                $product[
                                    'description'
                                ]
                                ?? ''
                            )
                        );


                    $filledStars =
                        (int)
                        round(
                            $averageRating
                        );


                    if (
                        $filledStars < 0
                    ) {

                        $filledStars = 0;
                    }


                    if (
                        $filledStars > 5
                    ) {

                        $filledStars = 5;
                    }

                    ?>


                    <article class="hh-product-card">


                        <!-- =====================================
                             IMAGE
                        ====================================== -->

                        <a
                            href="product_details.php?id=<?= $productId ?>"
                            class="hh-product-image"
                        >


                            <img
                                src="<?= productPageEscape(
                                    $image
                                ) ?>"
                                alt="<?= productPageEscape(
                                    $product[
                                        'product_name'
                                    ]
                                ) ?>"
                                loading="lazy"
                                onerror="
                                    this.src='image/logo.jpg';
                                    this.onerror=null;
                                "
                            >


                            <span class="hh-product-category">

                                <?= productPageEscape(
                                    $product[
                                        'category_name'
                                    ]
                                ) ?>

                            </span>


                            <?php if (
                                $stock > 0
                            ): ?>


                                <span class="
                                    hh-product-stock
                                    in-stock
                                ">

                                    <i class="bi bi-circle-fill"></i>

                                    In Stock

                                </span>


                            <?php else: ?>


                                <span class="
                                    hh-product-stock
                                    out-stock
                                ">

                                    <i class="bi bi-x-circle-fill"></i>

                                    Out of Stock

                                </span>


                            <?php endif; ?>


                        </a>


                        <!-- =====================================
                             CONTENT
                        ====================================== -->

                        <div class="hh-product-content">


                            <div class="hh-product-vendor">

                                <i class="bi bi-shop"></i>

                                <?= productPageEscape(
                                    $product[
                                        'business_name'
                                    ]
                                ) ?>

                            </div>


                            <h3 class="hh-product-name">


                                <a
                                    href="product_details.php?id=<?= $productId ?>"
                                >

                                    <?= productPageEscape(
                                        $product[
                                            'product_name'
                                        ]
                                    ) ?>

                                </a>


                            </h3>


                            <p class="hh-product-description">


                                <?php if (
                                    $description !== ''
                                ): ?>

                                    <?= productPageEscape(
                                        $description
                                    ) ?>

                                <?php else: ?>

                                    Discover this product from
                                    <?= productPageEscape(
                                        $product[
                                            'business_name'
                                        ]
                                    ) ?>.

                                <?php endif; ?>


                            </p>


                            <!-- RATING -->

                            <div class="hh-product-rating">


                                <div class="hh-product-stars">


                                    <?php for (
                                        $star = 1;
                                        $star <= 5;
                                        $star++
                                    ): ?>


                                        <?php if (
                                            $star <=
                                            $filledStars
                                        ): ?>

                                            ★

                                        <?php else: ?>

                                            ☆

                                        <?php endif; ?>


                                    <?php endfor; ?>


                                </div>


                                <strong>

                                    <?= number_format(
                                        $averageRating,
                                        1
                                    ) ?>

                                </strong>


                                <span>

                                    (
                                    <?= number_format(
                                        $reviewCount
                                    ) ?>

                                    review<?= $reviewCount !== 1
                                        ? 's'
                                        : '' ?>
                                    )

                                </span>


                            </div>


                            <!-- PRICE -->

                            <div class="hh-product-price-row">


                                <div class="hh-product-price">


                                    <small>
                                        PRICE
                                    </small>


                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $product[
                                                'price'
                                            ],
                                            2
                                        ) ?>

                                    </strong>


                                </div>


                                <span class="hh-product-stock-text">


                                    <?php if (
                                        $stock > 0
                                    ): ?>

                                        <?= number_format(
                                            $stock
                                        ) ?>

                                        available

                                    <?php else: ?>

                                        Sold out

                                    <?php endif; ?>


                                </span>


                            </div>


                            <!-- BUTTON -->

                            <a
                                href="product_details.php?id=<?= $productId ?>"
                                class="hh-product-view"
                            >

                                <span>

                                    <i class="bi bi-eye"></i>

                                    View Product

                                </span>


                                <i class="bi bi-arrow-right"></i>

                            </a>


                        </div>


                    </article>


                <?php endforeach; ?>


            </section>


        <?php endif; ?>


    </div>


</main>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>