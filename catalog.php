<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CATALOG PAGE
|--------------------------------------------------------------------------
| File: catalog.php
|--------------------------------------------------------------------------
|
| Purpose:
| - Product catalog
| - Search products
| - Filter by category
| - Filter by vendor
| - Sort products
| - Wishlist
| - Add to cart
| - UI matched with category.php / vendor.php
|
|--------------------------------------------------------------------------
*/


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
| CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';


/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = 'Catalog';


/*
|--------------------------------------------------------------------------
| JAVASCRIPT
|--------------------------------------------------------------------------
*/

$allJS = [
    'script.js',
    'search.js',
    'cart.js'
];


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('catalogEscape')) {

    function catalogEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('catalogProductImage')) {

    function catalogProductImage(?string $image): string
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
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search =
    isset($_GET['search'])
        ? trim($_GET['search'])
        : '';


$categoryId =
    isset($_GET['category'])
        ? (int) $_GET['category']
        : 0;


$vendorId =
    isset($_GET['vendor'])
        ? (int) $_GET['vendor']
        : 0;


$sort =
    isset($_GET['sort'])
        ? trim($_GET['sort'])
        : 'latest';


/*
|--------------------------------------------------------------------------
| VALID SORT OPTIONS
|--------------------------------------------------------------------------
*/

$allowedSorts = [

    'latest',
    'price_low',
    'price_high',
    'name',
    'oldest'

];


if (
    !in_array(
        $sort,
        $allowedSorts,
        true
    )
) {

    $sort = 'latest';

}


/*
|--------------------------------------------------------------------------
| INITIAL VARIABLES
|--------------------------------------------------------------------------
*/

$categories = [];

$vendors = [];

$products = [];

$productCount = 0;

$activeCategoryName = '';

$activeVendorName = '';


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

try {

    $categoryStmt =
        $db->prepare("
            SELECT
                category_id,
                category_name
            FROM categories
            ORDER BY category_name ASC
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
| LOAD VENDORS
|--------------------------------------------------------------------------
*/

try {

    $vendorStmt =
        $db->prepare("
            SELECT
                v.vendor_id,
                v.business_name
            FROM vendors v
            INNER JOIN users u
                ON v.user_id = u.user_id
            WHERE LOWER(v.approval_status) = 'approved'
            AND LOWER(u.status) = 'active'
            ORDER BY v.business_name ASC
        ");


    $vendorStmt->execute();


    $vendors =
        $vendorStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $vendors = [];

}


/*
|--------------------------------------------------------------------------
| ACTIVE CATEGORY NAME
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    try {

        $categoryNameStmt =
            $db->prepare("
                SELECT
                    category_name
                FROM categories
                WHERE category_id = ?
                LIMIT 1
            ");


        $categoryNameStmt->execute([
            $categoryId
        ]);


        $activeCategoryName =
            $categoryNameStmt->fetchColumn();


        if ($activeCategoryName === false) {

            $activeCategoryName = '';

        }

    }

    catch (Throwable $e) {

        $activeCategoryName = '';

    }

}


/*
|--------------------------------------------------------------------------
| ACTIVE VENDOR NAME
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    try {

        $vendorNameStmt =
            $db->prepare("
                SELECT
                    business_name
                FROM vendors
                WHERE vendor_id = ?
                LIMIT 1
            ");


        $vendorNameStmt->execute([
            $vendorId
        ]);


        $activeVendorName =
            $vendorNameStmt->fetchColumn();


        if ($activeVendorName === false) {

            $activeVendorName = '';

        }

    }

    catch (Throwable $e) {

        $activeVendorName = '';

    }

}


/*
|--------------------------------------------------------------------------
| PRODUCT SORTING
|--------------------------------------------------------------------------
*/

$orderBy =
    'p.created_at DESC';


switch ($sort) {

    case 'price_low':

        $orderBy =
            'p.price ASC';

        break;


    case 'price_high':

        $orderBy =
            'p.price DESC';

        break;


    case 'name':

        $orderBy =
            'p.product_name ASC';

        break;


    case 'oldest':

        $orderBy =
            'p.created_at ASC';

        break;


    case 'latest':

    default:

        $orderBy =
            'p.created_at DESC';

        break;

}


/*
|--------------------------------------------------------------------------
| PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        p.*,

        v.business_name,

        v.business_logo,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN users u
        ON v.user_id = u.user_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE p.stock_quantity > 0

    AND LOWER(p.status) IN (
        'available',
        'active'
    )

    AND LOWER(v.approval_status) = 'approved'

    AND LOWER(u.status) = 'active'

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
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
            OR p.description LIKE ?
        )

    ";


    $searchValue =
        '%' .
        $search .
        '%';


    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    $sql .= "

        AND p.category_id = ?

    ";


    $params[] =
        $categoryId;

}


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    $sql .= "

        AND p.vendor_id = ?

    ";


    $params[] =
        $vendorId;

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "

    ORDER BY {$orderBy}

";


/*
|--------------------------------------------------------------------------
| LOAD PRODUCTS
|--------------------------------------------------------------------------
*/

try {

    $productStmt =
        $db->prepare(
            $sql
        );


    $productStmt->execute(
        $params
    );


    $products =
        $productStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    $productCount =
        count(
            $products
        );

}

catch (Throwable $e) {

    $products = [];

    $productCount = 0;

}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


/*
|--------------------------------------------------------------------------
| CUSTOMER SIDEBAR
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['role']) &&
    strtolower(
        $_SESSION['role']
    ) === 'customer'
) {

    require_once __DIR__ .
        '/includes/customer_sidebar.php';

}

?>


<style>

/* ==========================================================================
   HOCHIPOHUB CATALOG
   MATCH CATEGORY + VENDOR UI
   ========================================================================== */

.catalog-page {

    --catalog-blue:
        #2563eb;

    --catalog-navy:
        #08265a;

    --catalog-text:
        #0b2d63;

    --catalog-muted:
        #7e91ae;

    --catalog-border:
        #dce7f3;

    --catalog-soft:
        #edf5ff;

    width:
        100%;

    min-height:
        100vh;

    background:

        linear-gradient(
            180deg,
            #f4f8fd 0%,
            #ffffff 33%,
            #ffffff 100%
        );

    color:
        var(
            --catalog-text
        );

}


.catalog-container {

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


.catalog-page-inner {

    padding:
        58px 0 80px;

}


/* ==========================================================================
   HERO
   ========================================================================== */

.catalog-hero-modern {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        320px;

    padding:
        52px 58px;

    margin-bottom:
        48px;

    display:
        flex;

    align-items:
        center;

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


.catalog-hero-modern::before {

    content:
        "";

    position:
        absolute;

    width:
        280px;

    height:
        280px;

    top:
        -140px;

    right:
        -25px;

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


.catalog-hero-modern::after {

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
            74,
            194,
            255,
            .18
        );

}


.catalog-hero-content-modern {

    position:
        relative;

    z-index:
        2;

    width:
        100%;

    max-width:
        850px;

}


.catalog-hero-label {

    width:
        fit-content;

    min-height:
        37px;

    padding:
        0 14px;

    margin-bottom:
        18px;

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
        11px;

    font-weight:
        800;

    letter-spacing:
        .8px;

    text-transform:
        uppercase;

}


.catalog-hero-modern h1 {

    margin:
        0 0 16px;

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


.catalog-hero-modern h1 span {

    color:
        #5fe5f4;

}


.catalog-hero-modern p {

    max-width:
        680px;

    margin:
        0 0 27px;

    color:
        rgba(
            255,
            255,
            255,
            .84
        );

    font-size:
        15px;

    line-height:
        1.75;

}


/* ==========================================================================
   SEARCH
   ========================================================================== */

.catalog-search-modern {

    max-width:
        720px;

    display:
        grid;

    grid-template-columns:
        1fr auto;

    gap:
        10px;

}


.catalog-search-field {

    height:
        50px;

    padding:
        0 16px;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    background:
        rgba(
            255,
            255,
            255,
            .95
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .7
        );

    border-radius:
        13px;

    box-shadow:

        0
        8px
        22px
        rgba(
            0,
            30,
            90,
            .13
        );

}


.catalog-search-field span {

    font-size:
        16px;

}


.catalog-search-field input {

    flex:
        1;

    width:
        100%;

    height:
        100%;

    padding:
        0;

    color:
        #19365f;

    background:
        transparent;

    border:
        0;

    outline:
        none;

    font-family:
        inherit;

    font-size:
        11px;

}


.catalog-search-button {

    min-width:
        105px;

    height:
        50px;

    padding:
        0 18px;

    color:
        #1e5cb8;

    background:
        #ffffff;

    border:
        0;

    border-radius:
        13px;

    box-shadow:

        0
        8px
        22px
        rgba(
            0,
            30,
            90,
            .13
        );

    font-family:
        inherit;

    font-size:
        10px;

    font-weight:
        800;

    cursor:
        pointer;

}


/* ==========================================================================
   LAYOUT
   ========================================================================== */

.catalog-layout-modern {

    display:
        grid;

    grid-template-columns:
        245px minmax(0, 1fr);

    align-items:
        start;

    gap:
        28px;

}


/* ==========================================================================
   SIDEBAR
   ========================================================================== */

.catalog-sidebar-modern {

    display:
        flex;

    flex-direction:
        column;

    gap:
        17px;

    position:
        sticky;

    top:
        24px;

}


.catalog-filter-card-modern {

    padding:
        20px;

    background:
        #ffffff;

    border:
        1px solid
        var(
            --catalog-border
        );

    border-radius:
        21px;

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

}


.catalog-filter-heading-modern {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    margin-bottom:
        16px;

}


.catalog-filter-icon {

    width:
        42px;

    height:
        42px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:

        linear-gradient(
            135deg,
            #1476e8,
            #1d95f3
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
            .20
        );

    font-size:
        17px;

}


.catalog-small-label {

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
        .9px;

    text-transform:
        uppercase;

}


.catalog-filter-heading-modern h3 {

    margin:
        0;

    color:
        #0b2d63;

    font-size:
        15px;

    font-weight:
        800;

}


.catalog-filter-list-modern {

    display:
        flex;

    flex-direction:
        column;

    gap:
        5px;

}


.catalog-filter-list-modern a {

    min-height:
        38px;

    padding:
        0 11px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    color:
        #657995;

    background:
        transparent;

    border-radius:
        10px;

    font-size:
        9px;

    font-weight:
        600;

    text-decoration:
        none;

    transition:
        .18s ease;

}


.catalog-filter-list-modern a:hover {

    color:
        #2563eb;

    background:
        #f2f7ff;

}


.catalog-filter-list-modern a.active {

    color:
        #1f5fcf;

    background:
        #eaf2ff;

    font-weight:
        800;

}


/* ==========================================================================
   SIDEBAR CTA
   ========================================================================== */

.catalog-side-cta {

    padding:
        21px;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #0b2e6e,
            #277bea
        );

    border-radius:
        21px;

    box-shadow:

        0
        12px
        28px
        rgba(
            25,
            75,
            155,
            .15
        );

}


.catalog-side-cta-icon {

    width:
        39px;

    height:
        39px;

    margin-bottom:
        13px;

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
            .13
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .17
        );

    border-radius:
        12px;

    font-size:
        16px;

}


.catalog-side-cta h3 {

    margin:
        0 0 7px;

    color:
        #ffffff;

    font-size:
        14px;

    font-weight:
        800;

}


.catalog-side-cta p {

    margin:
        0 0 13px;

    color:
        rgba(
            255,
            255,
            255,
            .72
        );

    font-size:
        9px;

    line-height:
        1.65;

}


.catalog-side-cta a {

    color:
        #ffffff;

    font-size:
        9px;

    font-weight:
        800;

    text-decoration:
        none;

}


/* ==========================================================================
   MAIN AREA
   ========================================================================== */

.catalog-main-modern {

    min-width:
        0;

}


/* ==========================================================================
   TOOLBAR
   ========================================================================== */

.catalog-toolbar-modern {

    min-height:
        105px;

    padding:
        22px 25px;

    margin-bottom:
        18px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    background:
        #ffffff;

    border:
        1px solid
        var(
            --catalog-border
        );

    border-radius:
        22px;

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

}


.catalog-toolbar-modern h2 {

    margin:
        2px 0 4px;

    color:
        #092b60;

    font-size:
        23px;

    line-height:
        1.2;

    font-weight:
        800;

}


.catalog-toolbar-modern p {

    margin:
        0;

    color:
        #8193b1;

    font-size:
        9px;

}


.catalog-toolbar-modern p strong {

    color:
        #2563eb;

}


.catalog-sort-modern {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

}


.catalog-sort-modern label {

    color:
        #7385a1;

    font-size:
        9px;

    font-weight:
        700;

    white-space:
        nowrap;

}


.catalog-sort-modern select {

    min-width:
        150px;

    height:
        39px;

    padding:
        0 10px;

    color:
        #30435f;

    background:
        #f8fbff;

    border:
        1px solid
        #d9e5f3;

    border-radius:
        10px;

    outline:
        none;

    font-family:
        inherit;

    font-size:
        9px;

}


/* ==========================================================================
   ACTIVE FILTERS
   ========================================================================== */

.catalog-active-filters {

    margin-bottom:
        18px;

    padding:
        12px 15px;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        8px;

    background:
        #f7faff;

    border:
        1px solid
        #dce8f5;

    border-radius:
        14px;

}


.catalog-active-label {

    color:
        #7486a2;

    font-size:
        8px;

    font-weight:
        800;

    text-transform:
        uppercase;

}


.catalog-filter-tag {

    min-height:
        29px;

    padding:
        0 10px;

    display:
        inline-flex;

    align-items:
        center;

    color:
        #1d5dcc;

    background:
        #eaf2ff;

    border:
        1px solid
        #d5e6ff;

    border-radius:
        999px;

    font-size:
        8px;

    font-weight:
        800;

}


.catalog-clear {

    margin-left:
        auto;

    color:
        #e04c4c;

    font-size:
        8px;

    font-weight:
        800;

    text-decoration:
        none;

}


/* ==========================================================================
   PRODUCT GRID
   ========================================================================== */

.catalog-product-grid-modern {

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
        20px;

}


/* ==========================================================================
   PRODUCT CARD
   ========================================================================== */

.catalog-product-card-modern {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        390px;

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
        22px;

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


.catalog-product-card-modern:hover {

    transform:
        translateY(-5px);

    border-color:
        #bfd8f8;

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
   PRODUCT IMAGE
   ========================================================================== */

.catalog-product-image-modern {

    position:
        relative;

    height:
        210px;

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
            #edf5ff,
            #f7fbff
        );

}


.catalog-product-image-modern > a {

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

}


.catalog-product-image-modern img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    transition:
        transform .3s ease;

}


.catalog-product-card-modern:hover
.catalog-product-image-modern img {

    transform:
        scale(1.04);

}


.catalog-product-placeholder-modern {

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
        42px;

}


/* ==========================================================================
   WISHLIST
   ========================================================================== */

.catalog-wishlist {

    position:
        absolute;

    top:
        13px;

    right:
        13px;

    z-index:
        4;

    width:
        35px;

    height:
        35px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        rgba(
            255,
            255,
            255,
            .93
        );

    border:
        1px solid
        #dbe8f8;

    border-radius:
        10px;

    box-shadow:

        0
        6px
        16px
        rgba(
            23,
            70,
            130,
            .10
        );

    font-size:
        17px;

    cursor:
        pointer;

}


.catalog-stock-badge {

    position:
        absolute;

    left:
        13px;

    bottom:
        13px;

    z-index:
        3;

    min-height:
        28px;

    padding:
        0 9px;

    display:
        inline-flex;

    align-items:
        center;

    color:
        #166534;

    background:
        rgba(
            240,
            253,
            244,
            .95
        );

    border:
        1px solid
        #bbf7d0;

    border-radius:
        999px;

    font-size:
        8px;

    font-weight:
        800;

}


/* ==========================================================================
   PRODUCT BODY
   ========================================================================== */

.catalog-product-body-modern {

    flex:
        1;

    padding:
        19px;

    display:
        flex;

    flex-direction:
        column;

}


.catalog-product-category-modern {

    width:
        fit-content;

    margin-bottom:
        9px;

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
        8px;

    font-weight:
        800;

}


.catalog-product-body-modern h3 {

    margin:
        0 0 7px;

    color:
        #092b5f;

    font-size:
        16px;

    line-height:
        1.35;

    font-weight:
        800;

}


.catalog-product-body-modern h3 a {

    color:
        inherit;

    text-decoration:
        none;

}


.catalog-product-body-modern h3 a:hover {

    color:
        #2563eb;

}


.catalog-product-vendor-modern {

    margin:
        0 0 16px;

    color:
        #8192aa;

    font-size:
        9px;

}


.catalog-product-vendor-modern strong {

    color:
        #49617f;

}


/* ==========================================================================
   PRODUCT FOOTER
   ========================================================================== */

.catalog-product-footer-modern {

    margin-top:
        auto;

    padding-top:
        15px;

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        10px;

    border-top:
        1px solid
        #edf1f6;

}


.catalog-price-label {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #97a5b8;

    font-size:
        7px;

    font-weight:
        700;

    text-transform:
        uppercase;

}


.catalog-price {

    color:
        #0b326d;

    font-size:
        16px;

    font-weight:
        800;

}


/* ==========================================================================
   CART BUTTON
   ========================================================================== */

.catalog-add-cart {

    min-height:
        35px;

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

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #1676df
        );

    border:
        0;

    border-radius:
        9px;

    box-shadow:

        0
        7px
        16px
        rgba(
            37,
            99,
            235,
            .20
        );

    font-family:
        inherit;

    font-size:
        8px;

    font-weight:
        800;

    text-decoration:
        none;

    cursor:
        pointer;

}


/* ==========================================================================
   EMPTY
   ========================================================================== */

.catalog-empty-modern {

    padding:
        75px 25px;

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


.catalog-empty-icon-modern {

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


.catalog-empty-modern h2 {

    margin:
        7px 0;

    color:
        #143564;

    font-size:
        21px;

    font-weight:
        800;

}


.catalog-empty-modern p {

    max-width:
        450px;

    margin:
        0 auto 18px;

    color:
        #8596ad;

    font-size:
        10px;

    line-height:
        1.7;

}


.catalog-empty-button {

    min-height:
        40px;

    padding:
        0 15px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        #2563eb;

    border-radius:
        9px;

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

@media (max-width: 1150px) {

    .catalog-product-grid-modern {

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


@media (max-width: 950px) {

    .catalog-layout-modern {

        grid-template-columns:
            1fr;

    }


    .catalog-sidebar-modern {

        position:
            static;

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

    }


    .catalog-side-cta {

        grid-column:
            1 / -1;

    }

}


@media (max-width: 720px) {

    .catalog-container {

        width:
            calc(
                100% - 26px
            );

    }


    .catalog-page-inner {

        padding:
            30px 0 55px;

    }


    .catalog-hero-modern {

        min-height:
            auto;

        padding:
            35px 25px;

        margin-bottom:
            35px;

        border-radius:
            23px;

    }


    .catalog-hero-modern h1 {

        font-size:
            36px;

    }


    .catalog-hero-modern p {

        font-size:
            12px;

    }


    .catalog-search-modern {

        grid-template-columns:
            1fr;

    }


    .catalog-search-button {

        width:
            100%;

    }


    .catalog-sidebar-modern {

        grid-template-columns:
            1fr;

    }


    .catalog-side-cta {

        grid-column:
            auto;

    }


    .catalog-toolbar-modern {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .catalog-sort-modern {

        width:
            100%;

        justify-content:
            space-between;

    }


    .catalog-sort-modern select {

        flex:
            1;

    }


    .catalog-product-grid-modern {

        grid-template-columns:
            1fr;

    }

}

</style>



<main class="catalog-page">


    <div class="catalog-page-inner">


        <div class="catalog-container">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="catalog-hero-modern">


                <div class="catalog-hero-content-modern">


                    <div class="catalog-hero-label">

                        ✦ Discover • Shop • Support Local

                    </div>


                    <h1>

                        Explore

                        <span>
                            HochipoHub.
                        </span>

                    </h1>


                    <p>

                        Discover unique products from local
                        vendors and find something worth
                        bringing home.

                    </p>


                    <!-- =============================================
                         SEARCH
                    ============================================== -->

                    <form
                        action="<?= e(BASE_URL) ?>catalog.php"
                        method="GET"
                        class="catalog-search-modern"
                    >


                        <?php if ($categoryId > 0): ?>

                            <input
                                type="hidden"
                                name="category"
                                value="<?= $categoryId ?>"
                            >

                        <?php endif; ?>


                        <?php if ($vendorId > 0): ?>

                            <input
                                type="hidden"
                                name="vendor"
                                value="<?= $vendorId ?>"
                            >

                        <?php endif; ?>


                        <div class="catalog-search-field">

                            <span>
                                🔎
                            </span>


                            <input
                                type="search"
                                name="search"
                                value="<?= e($search) ?>"
                                placeholder="Search products, vendors or categories..."
                                autocomplete="off"
                            >

                        </div>


                        <button
                            type="submit"
                            class="catalog-search-button"
                        >

                            Search

                        </button>


                    </form>


                </div>


            </section>



            <!-- =====================================================
                 CATALOG LAYOUT
            ====================================================== -->

            <section class="catalog-layout-modern">


                <!-- =================================================
                     SIDEBAR
                ================================================== -->

                <aside class="catalog-sidebar-modern">


                    <!-- =============================================
                         CATEGORY FILTER
                    ============================================== -->

                    <div class="catalog-filter-card-modern">


                        <div class="catalog-filter-heading-modern">


                            <div class="catalog-filter-icon">

                                🗂️

                            </div>


                            <div>

                                <span class="catalog-small-label">

                                    Browse

                                </span>


                                <h3>

                                    Categories

                                </h3>

                            </div>


                        </div>


                        <div class="catalog-filter-list-modern">


                            <a
                                href="<?= e(BASE_URL) ?>catalog.php"
                                class="<?= $categoryId === 0
                                    ? 'active'
                                    : '' ?>"
                            >

                                <span>
                                    All Products
                                </span>

                                <span>
                                    →
                                </span>

                            </a>


                            <?php foreach ($categories as $category): ?>


                                <a
                                    href="<?= e(BASE_URL) ?>catalog.php?category=<?= (int) $category['category_id'] ?>"
                                    class="<?= $categoryId ===
                                        (int) $category['category_id']
                                            ? 'active'
                                            : '' ?>"
                                >

                                    <span>

                                        <?= e(
                                            $category[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </span>

                                    <span>
                                        →
                                    </span>

                                </a>


                            <?php endforeach; ?>


                        </div>


                    </div>



                    <!-- =============================================
                         VENDOR FILTER
                    ============================================== -->

                    <div class="catalog-filter-card-modern">


                        <div class="catalog-filter-heading-modern">


                            <div class="catalog-filter-icon">

                                🏪

                            </div>


                            <div>

                                <span class="catalog-small-label">

                                    Marketplace

                                </span>


                                <h3>

                                    Sellers

                                </h3>

                            </div>


                        </div>


                        <div class="catalog-filter-list-modern">


                            <a
                                href="<?= e(BASE_URL) ?>catalog.php"
                                class="<?= $vendorId === 0
                                    ? 'active'
                                    : '' ?>"
                            >

                                <span>
                                    All Sellers
                                </span>

                                <span>
                                    →
                                </span>

                            </a>


                            <?php foreach ($vendors as $vendorItem): ?>


                                <a
                                    href="<?= e(BASE_URL) ?>catalog.php?vendor=<?= (int) $vendorItem['vendor_id'] ?>"
                                    class="<?= $vendorId ===
                                        (int) $vendorItem['vendor_id']
                                            ? 'active'
                                            : '' ?>"
                                >

                                    <span>

                                        <?= e(
                                            $vendorItem[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </span>

                                    <span>
                                        →
                                    </span>

                                </a>


                            <?php endforeach; ?>


                        </div>


                    </div>



                    <!-- =============================================
                         CTA
                    ============================================== -->

                    <div class="catalog-side-cta">


                        <div class="catalog-side-cta-icon">

                            ✨

                        </div>


                        <h3>

                            Looking for something else?

                        </h3>


                        <p>

                            Browse all categories and discover
                            more products across HochipoHub.

                        </p>


                        <a
                            href="<?= e(BASE_URL) ?>category.php"
                        >

                            Browse Categories →

                        </a>


                    </div>


                </aside>



                <!-- =================================================
                     MAIN PRODUCT AREA
                ================================================== -->

                <div class="catalog-main-modern">


                    <!-- =============================================
                         TOOLBAR
                    ============================================== -->

                    <div class="catalog-toolbar-modern">


                        <div>


                            <span class="catalog-small-label">

                                Products

                            </span>


                            <h2>


                                <?php if ($activeCategoryName !== ''): ?>


                                    <?= e(
                                        $activeCategoryName
                                    ) ?>


                                <?php elseif ($activeVendorName !== ''): ?>


                                    <?= e(
                                        $activeVendorName
                                    ) ?>


                                <?php elseif ($search !== ''): ?>


                                    Search Results


                                <?php else: ?>


                                    All Products


                                <?php endif; ?>


                            </h2>


                            <p>

                                <strong>

                                    <?= number_format(
                                        $productCount
                                    ) ?>

                                </strong>

                                product<?= $productCount !== 1
                                    ? 's'
                                    : '' ?>

                                found

                            </p>


                        </div>



                        <!-- =========================================
                             SORT
                        ========================================== -->

                        <form
                            action="<?= e(BASE_URL) ?>catalog.php"
                            method="GET"
                            class="catalog-sort-modern"
                        >


                            <?php if ($search !== ''): ?>

                                <input
                                    type="hidden"
                                    name="search"
                                    value="<?= e($search) ?>"
                                >

                            <?php endif; ?>


                            <?php if ($categoryId > 0): ?>

                                <input
                                    type="hidden"
                                    name="category"
                                    value="<?= $categoryId ?>"
                                >

                            <?php endif; ?>


                            <?php if ($vendorId > 0): ?>

                                <input
                                    type="hidden"
                                    name="vendor"
                                    value="<?= $vendorId ?>"
                                >

                            <?php endif; ?>


                            <label for="sort">

                                Sort by

                            </label>


                            <select
                                name="sort"
                                id="sort"
                                onchange="this.form.submit()"
                            >


                                <option
                                    value="latest"
                                    <?= $sort === 'latest'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Latest

                                </option>


                                <option
                                    value="price_low"
                                    <?= $sort === 'price_low'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Price: Low to High

                                </option>


                                <option
                                    value="price_high"
                                    <?= $sort === 'price_high'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Price: High to Low

                                </option>


                                <option
                                    value="name"
                                    <?= $sort === 'name'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Name

                                </option>


                                <option
                                    value="oldest"
                                    <?= $sort === 'oldest'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Oldest

                                </option>


                            </select>


                        </form>


                    </div>



                    <!-- =============================================
                         ACTIVE FILTERS
                    ============================================== -->

                    <?php if (
                        $search !== '' ||
                        $categoryId > 0 ||
                        $vendorId > 0
                    ): ?>


                        <div class="catalog-active-filters">


                            <span class="catalog-active-label">

                                Active:

                            </span>


                            <?php if ($search !== ''): ?>


                                <span class="catalog-filter-tag">

                                    🔎
                                    <?= e($search) ?>

                                </span>


                            <?php endif; ?>


                            <?php if ($activeCategoryName !== ''): ?>


                                <span class="catalog-filter-tag">

                                    🗂️
                                    <?= e(
                                        $activeCategoryName
                                    ) ?>

                                </span>


                            <?php endif; ?>


                            <?php if ($activeVendorName !== ''): ?>


                                <span class="catalog-filter-tag">

                                    🏪
                                    <?= e(
                                        $activeVendorName
                                    ) ?>

                                </span>


                            <?php endif; ?>


                            <a
                                href="<?= e(BASE_URL) ?>catalog.php"
                                class="catalog-clear"
                            >

                                Clear all

                            </a>


                        </div>


                    <?php endif; ?>



                    <!-- =============================================
                         PRODUCTS
                    ============================================== -->

                    <?php if (!empty($products)): ?>


                        <div class="catalog-product-grid-modern">


                            <?php foreach ($products as $product): ?>


                                <?php

                                $productId =
                                    (int)
                                    $product[
                                        'product_id'
                                    ];


                                $price =
                                    (float)
                                    $product[
                                        'price'
                                    ];


                                $productImage =
                                    catalogProductImage(
                                        $product[
                                            'image'
                                        ]
                                        ?? ''
                                    );

                                ?>


                                <article class="catalog-product-card-modern">


                                    <!-- =============================
                                         IMAGE
                                    ============================== -->

                                    <div class="catalog-product-image-modern">


                                        <a
                                            href="<?= e(BASE_URL) ?>product_details.php?id=<?= $productId ?>"
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
                                                        this.parentElement.innerHTML='<div class=&quot;catalog-product-placeholder-modern&quot;>🛍️</div>';
                                                    "
                                                >


                                            <?php else: ?>


                                                <div class="catalog-product-placeholder-modern">

                                                    🛍️

                                                </div>


                                            <?php endif; ?>


                                        </a>



                                        <!-- WISHLIST -->

                                        <?php if (
                                            isset(
                                                $_SESSION[
                                                    'user_id'
                                                ]
                                            )
                                        ): ?>


                                            <button
                                                type="button"
                                                class="
                                                    wishlist-btn
                                                    catalog-wishlist
                                                "
                                                data-product-id="<?= $productId ?>"
                                                title="Add to wishlist"
                                            >

                                                ♡

                                            </button>


                                        <?php endif; ?>



                                        <!-- STOCK -->

                                        <span class="catalog-stock-badge">

                                            <?= (int)
                                                $product[
                                                    'stock_quantity'
                                                ] ?>

                                            left

                                        </span>


                                    </div>



                                    <!-- =============================
                                         BODY
                                    ============================== -->

                                    <div class="catalog-product-body-modern">


                                        <span class="catalog-product-category-modern">

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


                                        <p class="catalog-product-vendor-modern">

                                            by

                                            <strong>

                                                <?= e(
                                                    $product[
                                                        'business_name'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </p>



                                        <!-- =========================
                                             FOOTER
                                        ========================== -->

                                        <div class="catalog-product-footer-modern">


                                            <div>


                                                <span class="catalog-price-label">

                                                    Price

                                                </span>


                                                <strong class="catalog-price">

                                                    RM
                                                    <?= number_format(
                                                        $price,
                                                        2
                                                    ) ?>

                                                </strong>


                                            </div>



                                            <?php if (
                                                isset(
                                                    $_SESSION[
                                                        'user_id'
                                                    ]
                                                )
                                            ): ?>


                                                <button
                                                    type="button"
                                                    class="
                                                        add-cart-btn
                                                        catalog-add-cart
                                                    "
                                                    data-product-id="<?= $productId ?>"
                                                >

                                                    🛒
                                                    Add

                                                </button>


                                            <?php else: ?>


                                                <a
                                                    href="<?= e(BASE_URL) ?>index.php?login=1"
                                                    class="catalog-add-cart"
                                                >

                                                    Login

                                                </a>


                                            <?php endif; ?>


                                        </div>


                                    </div>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <!-- =========================================
                             EMPTY
                        ========================================== -->

                        <div class="catalog-empty-modern">


                            <div class="catalog-empty-icon-modern">

                                🔎

                            </div>


                            <span class="catalog-small-label">

                                Nothing Here Yet

                            </span>


                            <h2>

                                No products found

                            </h2>


                            <p>

                                Try another keyword, category or seller.
                                There might be something else waiting for you.

                            </p>


                            <a
                                href="<?= e(BASE_URL) ?>catalog.php"
                                class="catalog-empty-button"
                            >

                                View All Products

                            </a>


                        </div>


                    <?php endif; ?>


                </div>


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

$footerPath =
    __DIR__ .
    '/includes/footer.php';


if (file_exists($footerPath)) {

    require_once $footerPath;

}

?>