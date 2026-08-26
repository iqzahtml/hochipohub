<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM CATALOG
|--------------------------------------------------------------------------
| File:
| catalog.php
|--------------------------------------------------------------------------
|
| Features:
| - Search products
| - Category filter
| - Vendor filter
| - Product sorting
| - Wishlist
| - Add to cart
| - Customer navigation
| - Responsive product grid
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
| CONFIG
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
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| PAGE
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


        return
            BASE_URL .
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('catalogBuildUrl')) {

    function catalogBuildUrl(
        array $changes = []
    ): string {

        $params = $_GET;


        foreach ($changes as $key => $value) {

            if (
                $value === null ||
                $value === '' ||
                $value === 0
            ) {

                unset($params[$key]);

            } else {

                $params[$key] = $value;
            }
        }


        $query =
            http_build_query(
                $params
            );


        return
            BASE_URL .
            'catalog.php' .
            (
                $query !== ''
                    ? '?' . $query
                    : ''
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
        ? trim(
            (string) $_GET['search']
        )
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
        ? trim(
            (string) $_GET['sort']
        )
        : 'latest';


/*
|--------------------------------------------------------------------------
| VALID SORT
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
| DATA ARRAYS
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

    $stmt =
        $db->prepare("
            SELECT

                category_id,
                category_name

            FROM categories

            ORDER BY
                category_name ASC
        ");


    $stmt->execute();


    $categories =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $categories = [];
}


/*
|--------------------------------------------------------------------------
| LOAD APPROVED VENDORS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                v.vendor_id,
                v.business_name

            FROM vendors v

            INNER JOIN users u
                ON v.user_id = u.user_id

            WHERE LOWER(v.approval_status) = 'approved'

            AND LOWER(u.status) = 'active'

            ORDER BY
                v.business_name ASC
        ");


    $stmt->execute();


    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $vendors = [];
}


/*
|--------------------------------------------------------------------------
| ACTIVE CATEGORY
|--------------------------------------------------------------------------
*/

if ($categoryId > 0) {

    try {

        $stmt =
            $db->prepare("
                SELECT
                    category_name

                FROM categories

                WHERE category_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $categoryId
        ]);


        $activeCategoryName =
            $stmt->fetchColumn();


        if (
            $activeCategoryName === false
        ) {

            $activeCategoryName = '';
        }

    } catch (Throwable $e) {

        $activeCategoryName = '';
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVE VENDOR
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    try {

        $stmt =
            $db->prepare("
                SELECT
                    business_name

                FROM vendors

                WHERE vendor_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $vendorId
        ]);


        $activeVendorName =
            $stmt->fetchColumn();


        if (
            $activeVendorName === false
        ) {

            $activeVendorName = '';
        }

    } catch (Throwable $e) {

        $activeVendorName = '';
    }
}


/*
|--------------------------------------------------------------------------
| SORT
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

    AND LOWER(p.status) IN
    (
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
| ORDER BY
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


    $productCount =
        count(
            $products
        );

} catch (Throwable $e) {

    $products = [];

    $productCount = 0;
}


/*
|--------------------------------------------------------------------------
| CATEGORY COUNT
|--------------------------------------------------------------------------
*/

$categoryCount =
    count(
        $categories
    );


/*
|--------------------------------------------------------------------------
| VENDOR COUNT
|--------------------------------------------------------------------------
*/

$vendorCount =
    count(
        $vendors
    );


/*
|--------------------------------------------------------------------------
| CUSTOMER NAV COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = 0;

$wishlistCount = 0;


if (
    isset(
        $_SESSION['user_id']
    ) &&
    strtolower(
        (string) (
            $_SESSION['role']
            ?? ''
        )
    ) === 'customer'
) {

    $customerId =
        (int) $_SESSION['user_id'];


    /*
    |--------------------------------------------------------------------------
    | CART
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $db->prepare("
                SELECT

                    COALESCE(
                        SUM(quantity),
                        0
                    ) AS total

                FROM cart

                WHERE customer_id = ?
            ");


        $stmt->execute([
            $customerId
        ]);


        $row =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        $cartCount =
            (int) (
                $row['total']
                ?? 0
            );

    } catch (Throwable $e) {

        $cartCount = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | WISHLIST
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $db->prepare("
                SELECT
                    COUNT(*) AS total

                FROM wishlist

                WHERE user_id = ?
            ");


        $stmt->execute([
            $customerId
        ]);


        $row =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        $wishlistCount =
            (int) (
                $row['total']
                ?? 0
            );

    } catch (Throwable $e) {

        $wishlistCount = 0;
    }
}


/*
|--------------------------------------------------------------------------
| PAGE CSS
|--------------------------------------------------------------------------
*/

$extraCSS = [
    'dashboard.css'
];


$hideSiteMainWrapper =
    true;


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/header.php';


/*
|--------------------------------------------------------------------------
| CUSTOMER NAV
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['role']) &&
    strtolower(
        (string) $_SESSION['role']
    ) === 'customer'
) {

    require_once __DIR__ .
        '/includes/customer_sidebar.php';
}

?>


<style>

/* ==========================================================================
   HOCHIPOHUB PREMIUM CATALOG
   ========================================================================== */

.hh-catalog-page {

    --catalog-blue:
        #2563eb;

    --catalog-navy:
        #0b2d69;

    --catalog-text:
        #14213d;

    --catalog-muted:
        #8291a7;

    --catalog-border:
        #e1e9f4;

    width: 100%;

    min-height: 100vh;

    padding:
        42px
        24px
        75px;

    overflow-x: hidden;

    color:
        var(--catalog-text);

    background:

        radial-gradient(
            circle at 92% 4%,
            rgba(59,130,246,.08),
            transparent 24%
        ),

        linear-gradient(
            180deg,
            #f5f8ff 0%,
            #f8faff 55%,
            #ffffff 100%
        );

}


.hh-catalog-container {

    width: 100%;

    max-width: 1340px;

    margin: 0 auto;

}


/* ==========================================================================
   HERO
   ========================================================================== */

.hh-catalog-hero {

    position: relative;

    min-height: 350px;

    margin-bottom: 23px;

    padding:
        48px
        52px;

    overflow: hidden;

    display: grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        390px;

    align-items: center;

    gap: 35px;

    color: #ffffff;

    background:

        linear-gradient(
            115deg,
            #0b2b69 0%,
            #174998 48%,
            #2683ef 100%
        );

    border-radius: 28px;

    box-shadow:

        0
        20px
        50px
        rgba(
            23,
            79,
            165,
            .16
        );

}


.hh-catalog-hero::before {

    content: "";

    position: absolute;

    width: 310px;

    height: 310px;

    top: -165px;

    right: -65px;

    border-radius: 50%;

    background:

        rgba(
            255,
            255,
            255,
            .08
        );

}


.hh-catalog-hero::after {

    content: "";

    position: absolute;

    width: 190px;

    height: 190px;

    right: 190px;

    bottom: -130px;

    border-radius: 50%;

    background:

        rgba(
            95,
            229,
            244,
            .14
        );

}


.hh-catalog-hero-copy {

    position: relative;

    z-index: 3;

}


.hh-catalog-pill {

    min-height: 34px;

    padding:
        0
        13px;

    margin-bottom: 19px;

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #ffffff;

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
            .23
        );

    border-radius: 999px;

    font-size: 10px;

    font-weight: 850;

    letter-spacing: .5px;

}


.hh-catalog-hero h1 {

    max-width: 720px;

    margin: 0;

    color: #ffffff;

    font-family:
        "Poppins",
        "Inter",
        Arial,
        sans-serif;

    font-size:

        clamp(
            36px,
            4.7vw,
            58px
        );

    line-height: 1.07;

    font-weight: 800;

    letter-spacing: -2px;

}


.hh-catalog-hero h1 span {

    color: #6fe7f3;

}


.hh-catalog-hero p {

    max-width: 625px;

    margin:
        17px
        0
        0;

    color:

        rgba(
            255,
            255,
            255,
            .78
        );

    font-size: 13px;

    line-height: 1.75;

}


/* ==========================================================================
   SEARCH
   ========================================================================== */

.hh-catalog-search {

    max-width: 700px;

    margin-top: 25px;

    display: grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        auto;

    gap: 9px;

}


.hh-catalog-search-field {

    height: 49px;

    padding:
        0
        15px;

    display: flex;

    align-items: center;

    gap: 9px;

    background:

        rgba(
            255,
            255,
            255,
            .96
        );

    border:

        1px solid
        rgba(
            255,
            255,
            255,
            .8
        );

    border-radius: 12px;

    box-shadow:

        0
        8px
        20px
        rgba(
            5,
            35,
            85,
            .13
        );

}


.hh-catalog-search-field i {

    color: #2563eb;

    font-size: 14px;

}


.hh-catalog-search-field input {

    width: 100%;

    height: 100%;

    padding: 0;

    outline: none;

    color: #30445f;

    background: transparent;

    border: 0;

    font-family: inherit;

    font-size: 10px;

}


.hh-catalog-search-button {

    min-width: 108px;

    height: 49px;

    padding:
        0
        16px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #1955ad;

    background: #ffffff;

    border: 0;

    border-radius: 12px;

    box-shadow:

        0
        8px
        20px
        rgba(
            5,
            35,
            85,
            .13
        );

    font-family: inherit;

    font-size: 9px;

    font-weight: 850;

    cursor: pointer;

}


/* ==========================================================================
   HERO VISUAL
   ========================================================================== */

.hh-catalog-hero-visual {

    position: relative;

    z-index: 3;

    height: 240px;

}


.hh-catalog-hero-bag {

    position: absolute;

    width: 165px;

    height: 165px;

    top: 35px;

    right: 75px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

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

    border-radius: 41px;

    font-size: 68px;

    backdrop-filter: blur(12px);

    transform:
        rotate(-4deg);

}


.hh-catalog-float {

    position: absolute;

    min-width: 145px;

    padding:
        12px
        14px;

    display: flex;

    align-items: center;

    gap: 9px;

    color: #26405f;

    background:

        rgba(
            255,
            255,
            255,
            .95
        );

    border-radius: 13px;

    box-shadow:

        0
        14px
        32px
        rgba(
            5,
            35,
            80,
            .17
        );

    font-size: 9px;

    font-weight: 850;

}


.hh-catalog-float i {

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 9px;

}


.hh-catalog-float.one {

    top: 5px;

    left: 0;

    transform:
        rotate(-3deg);

}


.hh-catalog-float.two {

    right: 0;

    bottom: 5px;

    transform:
        rotate(3deg);

}


/* ==========================================================================
   STATS
   ========================================================================== */

.hh-catalog-stats {

    margin-bottom: 23px;

    display: grid;

    grid-template-columns:

        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap: 15px;

}


.hh-catalog-stat {

    min-height: 90px;

    padding:
        17px
        19px;

    display: flex;

    align-items: center;

    gap: 13px;

    background:

        rgba(
            255,
            255,
            255,
            .95
        );

    border:
        1px solid
        var(--catalog-border);

    border-radius: 17px;

    box-shadow:

        0
        8px
        24px
        rgba(
            40,
            65,
            120,
            .045
        );

}


.hh-catalog-stat-icon {

    width: 43px;

    height: 43px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    font-size: 15px;

}


.hh-catalog-stat-icon.blue {

    color: #2563eb;

    background: #eff6ff;

}


.hh-catalog-stat-icon.purple {

    color: #7c3aed;

    background: #f5f3ff;

}


.hh-catalog-stat-icon.green {

    color: #15803d;

    background: #ecfdf3;

}


.hh-catalog-stat span {

    display: block;

    margin-bottom: 4px;

    color: #8996a9;

    font-size: 7px;

    font-weight: 850;

    letter-spacing: .7px;

}


.hh-catalog-stat strong {

    display: block;

    color: #17233c;

    font-size: 18px;

    font-weight: 900;

}


/* ==========================================================================
   LAYOUT
   ========================================================================== */

.hh-catalog-layout {

    display: grid;

    grid-template-columns:

        245px

        minmax(
            0,
            1fr
        );

    align-items: start;

    gap: 22px;

}


/* ==========================================================================
   FILTER COLUMN
   ========================================================================== */

.hh-catalog-sidebar {

    position: sticky;

    top: 22px;

    display: flex;

    flex-direction: column;

    gap: 16px;

}


.hh-catalog-filter-card {

    padding: 19px;

    background: #ffffff;

    border:
        1px solid
        var(--catalog-border);

    border-radius: 18px;

    box-shadow:

        0
        8px
        24px
        rgba(
            40,
            65,
            120,
            .045
        );

}


.hh-catalog-filter-header {

    margin-bottom: 15px;

    display: flex;

    align-items: center;

    gap: 10px;

}


.hh-catalog-filter-icon {

    width: 40px;

    height: 40px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 11px;

    font-size: 14px;

}


.hh-catalog-filter-header small {

    display: block;

    margin-bottom: 2px;

    color: #2563eb;

    font-size: 6px;

    font-weight: 900;

    letter-spacing: .8px;

}


.hh-catalog-filter-header h3 {

    margin: 0;

    color: #17233c;

    font-size: 13px;

    font-weight: 900;

}


.hh-catalog-filter-list {

    display: flex;

    flex-direction: column;

    gap: 4px;

}


.hh-catalog-filter-list a {

    min-height: 37px;

    padding:
        0
        10px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    color: #687b95;

    border-radius: 9px;

    font-size: 8px;

    font-weight: 700;

    text-decoration: none;

    transition: .18s ease;

}


.hh-catalog-filter-list a:hover {

    color: #2563eb;

    background: #f3f7ff;

}


.hh-catalog-filter-list a.active {

    color: #1d5dcc;

    background: #eaf2ff;

    font-weight: 850;

}


.hh-catalog-filter-list i {

    font-size: 9px;

}


/* ==========================================================================
   FILTER CTA
   ========================================================================== */

.hh-catalog-side-cta {

    position: relative;

    overflow: hidden;

    padding: 21px;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #0b2e6e,
            #277bea
        );

    border-radius: 18px;

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


.hh-catalog-side-cta::after {

    content: "";

    position: absolute;

    width: 95px;

    height: 95px;

    right: -35px;

    bottom: -40px;

    border-radius: 50%;

    background:

        rgba(
            255,
            255,
            255,
            .07
        );

}


.hh-catalog-side-cta > * {

    position: relative;

    z-index: 2;

}


.hh-catalog-side-cta-icon {

    width: 40px;

    height: 40px;

    margin-bottom: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:

        rgba(
            255,
            255,
            255,
            .13
        );

    border-radius: 11px;

    font-size: 14px;

}


.hh-catalog-side-cta h3 {

    margin:
        0
        0
        6px;

    color: #ffffff;

    font-size: 12px;

    font-weight: 900;

}


.hh-catalog-side-cta p {

    margin:
        0
        0
        12px;

    color:

        rgba(
            255,
            255,
            255,
            .72
        );

    font-size: 8px;

    line-height: 1.65;

}


.hh-catalog-side-cta a {

    color: #ffffff;

    font-size: 8px;

    font-weight: 850;

    text-decoration: none;

}


/* ==========================================================================
   MAIN
   ========================================================================== */

.hh-catalog-main {

    min-width: 0;

}


/* ==========================================================================
   TOOLBAR
   ========================================================================== */

.hh-catalog-toolbar {

    min-height: 95px;

    margin-bottom: 17px;

    padding:
        19px
        22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    background: #ffffff;

    border:
        1px solid
        var(--catalog-border);

    border-radius: 18px;

    box-shadow:

        0
        8px
        24px
        rgba(
            40,
            65,
            120,
            .045
        );

}


.hh-catalog-toolbar small {

    display: block;

    margin-bottom: 3px;

    color: #2563eb;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .8px;

}


.hh-catalog-toolbar h2 {

    margin:
        0
        0
        4px;

    color: #17233c;

    font-size: 18px;

    font-weight: 900;

}


.hh-catalog-toolbar p {

    margin: 0;

    color: #8997aa;

    font-size: 8px;

}


.hh-catalog-toolbar p strong {

    color: #2563eb;

}


.hh-catalog-sort {

    display: flex;

    align-items: center;

    gap: 8px;

}


.hh-catalog-sort label {

    color: #76879e;

    font-size: 8px;

    font-weight: 750;

}


.hh-catalog-sort select {

    min-width: 155px;

    height: 39px;

    padding:
        0
        10px;

    outline: none;

    color: #35465f;

    background: #f8fbff;

    border:
        1px solid #dbe5ef;

    border-radius: 9px;

    font-family: inherit;

    font-size: 8px;

}


/* ==========================================================================
   ACTIVE FILTERS
   ========================================================================== */

.hh-catalog-active {

    margin-bottom: 17px;

    padding:
        11px
        13px;

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 7px;

    background: #f8fbff;

    border:
        1px solid #dfeaf7;

    border-radius: 12px;

}


.hh-catalog-active-label {

    color: #8090a7;

    font-size: 7px;

    font-weight: 900;

}


.hh-catalog-filter-tag {

    min-height: 27px;

    padding:
        0
        9px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: #1d5dcc;

    background: #eaf2ff;

    border:
        1px solid #d6e5fb;

    border-radius: 999px;

    font-size: 7px;

    font-weight: 800;

}


.hh-catalog-clear {

    margin-left: auto;

    color: #dc2626;

    font-size: 7px;

    font-weight: 850;

    text-decoration: none;

}


/* ==========================================================================
   PRODUCT GRID
   ========================================================================== */

.hh-catalog-grid {

    display: grid;

    grid-template-columns:

        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap: 17px;

}


/* ==========================================================================
   PRODUCT CARD
   ========================================================================== */

.hh-catalog-card {

    min-width: 0;

    overflow: hidden;

    display: flex;

    flex-direction: column;

    background: #ffffff;

    border:
        1px solid
        var(--catalog-border);

    border-radius: 18px;

    box-shadow:

        0
        7px
        22px
        rgba(
            40,
            65,
            120,
            .045
        );

    transition:

        transform .20s ease,
        box-shadow .20s ease,
        border-color .20s ease;

}


.hh-catalog-card:hover {

    transform:
        translateY(-4px);

    border-color: #c6dcf8;

    box-shadow:

        0
        15px
        33px
        rgba(
            40,
            65,
            120,
            .10
        );

}


/* ==========================================================================
   PRODUCT IMAGE
   ========================================================================== */

.hh-catalog-image {

    position: relative;

    width: 100%;

    height: 220px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    background:

        linear-gradient(
            135deg,
            #f1f6ff,
            #eaf2ff
        );

}


.hh-catalog-image > a {

    width: 100%;

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

}


/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| CONTAIN prevents seller images from being cropped.
|--------------------------------------------------------------------------
*/

.hh-catalog-image img {

    width: 100%;

    height: 100%;

    padding: 10px;

    display: block;

    object-fit: contain;

    object-position: center;

    transition:
        transform .25s ease;

}


.hh-catalog-card:hover
.hh-catalog-image img {

    transform:
        scale(1.03);

}


.hh-catalog-placeholder {

    width: 100%;

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    font-size: 35px;

}


/* ==========================================================================
   IMAGE BADGES
   ========================================================================== */

.hh-catalog-category-badge {

    position: absolute;

    left: 11px;

    top: 11px;

    z-index: 3;

    min-height: 24px;

    padding:
        0
        8px;

    display: inline-flex;

    align-items: center;

    color: #2563eb;

    background:

        rgba(
            255,
            255,
            255,
            .94
        );

    border:
        1px solid #dbeafe;

    border-radius: 8px;

    font-size: 6px;

    font-weight: 850;

    backdrop-filter: blur(8px);

}


.hh-catalog-stock {

    position: absolute;

    left: 11px;

    bottom: 11px;

    z-index: 3;

    min-height: 26px;

    padding:
        0
        8px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: #15803d;

    background:

        rgba(
            240,
            253,
            244,
            .94
        );

    border:
        1px solid #bbf7d0;

    border-radius: 999px;

    font-size: 6px;

    font-weight: 850;

}


.hh-catalog-stock i {

    font-size: 5px;

}


/* ==========================================================================
   WISHLIST
   ========================================================================== */

.hh-catalog-wishlist {

    position: absolute;

    top: 10px;

    right: 10px;

    z-index: 5;

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #e11d48;

    background:

        rgba(
            255,
            255,
            255,
            .94
        );

    border:
        1px solid #ffe1e7;

    border-radius: 10px;

    box-shadow:

        0
        6px
        16px
        rgba(
            33,
            50,
            90,
            .10
        );

    font-size: 14px;

    cursor: pointer;

    transition:
        transform .18s ease;

}


.hh-catalog-wishlist:hover {

    transform:
        scale(1.07);

}


/* ==========================================================================
   CARD BODY
   ========================================================================== */

.hh-catalog-card-body {

    flex: 1;

    padding: 16px;

    display: flex;

    flex-direction: column;

}


.hh-catalog-vendor {

    margin-bottom: 7px;

    display: flex;

    align-items: center;

    gap: 5px;

    color: #8795aa;

    font-size: 7px;

    font-weight: 750;

}


.hh-catalog-vendor i {

    color: #2563eb;

}


.hh-catalog-card h3 {

    min-height: 39px;

    margin: 0;

    display: -webkit-box;

    overflow: hidden;

    font-size: 13px;

    line-height: 1.45;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

}


.hh-catalog-card h3 a {

    color: #17233c;

    font-weight: 900;

    text-decoration: none;

}


.hh-catalog-card h3 a:hover {

    color: #2563eb;

}


.hh-catalog-description {

    min-height: 34px;

    margin:
        8px
        0
        0;

    display: -webkit-box;

    overflow: hidden;

    color: #8a98ac;

    font-size: 7px;

    line-height: 1.6;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

}


/* ==========================================================================
   CARD FOOTER
   ========================================================================== */

.hh-catalog-card-footer {

    margin-top: auto;

    padding-top: 14px;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 10px;

    border-top:
        1px solid #edf1f5;

}


.hh-catalog-price-label {

    display: block;

    margin-bottom: 2px;

    color: #97a4b5;

    font-size: 6px;

    font-weight: 800;

}


.hh-catalog-price {

    display: block;

    color: #1b4a87;

    font-size: 16px;

    font-weight: 900;

}


/* ==========================================================================
   ADD CART
   ========================================================================== */

.hh-catalog-add {

    min-height: 38px;

    padding:
        0
        12px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    color: #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #377fef
        );

    border: 0;

    border-radius: 9px;

    box-shadow:

        0
        7px
        16px
        rgba(
            37,
            99,
            235,
            .18
        );

    font-family: inherit;

    font-size: 8px;

    font-weight: 850;

    text-decoration: none;

    cursor: pointer;

}


.hh-catalog-add:hover {

    color: #ffffff;

}


/* ==========================================================================
   EMPTY
   ========================================================================== */

.hh-catalog-empty {

    min-height: 390px;

    padding:
        55px
        25px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    background: #ffffff;

    border:
        1px dashed #bdd8f7;

    border-radius: 20px;

}


.hh-catalog-empty-inner {

    max-width: 470px;

}


.hh-catalog-empty-icon {

    width: 65px;

    height: 65px;

    margin:
        0
        auto
        15px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 18px;

    font-size: 25px;

}


.hh-catalog-empty small {

    display: block;

    margin-bottom: 5px;

    color: #2563eb;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .9px;

}


.hh-catalog-empty h2 {

    margin: 0;

    color: #17233c;

    font-size: 19px;

    font-weight: 900;

}


.hh-catalog-empty p {

    margin:
        9px
        auto
        18px;

    color: #8492a6;

    font-size: 9px;

    line-height: 1.7;

}


.hh-catalog-empty a {

    min-height: 40px;

    padding:
        0
        14px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    color: #ffffff;

    background: #2563eb;

    border-radius: 9px;

    font-size: 8px;

    font-weight: 850;

    text-decoration: none;

}


/* ==========================================================================
   RESPONSIVE
   ========================================================================== */

@media (max-width: 1150px) {

    .hh-catalog-hero {

        grid-template-columns:

            minmax(
                0,
                1fr
            )

            310px;

    }


    .hh-catalog-grid {

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

    .hh-catalog-layout {

        grid-template-columns:
            1fr;

    }


    .hh-catalog-sidebar {

        position: static;

        display: grid;

        grid-template-columns:

            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );

    }


    .hh-catalog-side-cta {

        grid-column:
            1 / -1;

    }

}


@media (max-width: 850px) {

    .hh-catalog-page {

        padding:
            30px
            18px
            60px;

    }


    .hh-catalog-hero {

        grid-template-columns:
            1fr;

        min-height: auto;

        padding: 38px;

    }


    .hh-catalog-hero-visual {

        display: none;

    }


    .hh-catalog-stats {

        grid-template-columns:

            1fr
            1fr;

    }


    .hh-catalog-stat:last-child {

        grid-column:
            1 / -1;

    }

}


@media (max-width: 700px) {

    .hh-catalog-sidebar {

        grid-template-columns:
            1fr;

    }


    .hh-catalog-side-cta {

        grid-column:
            auto;

    }


    .hh-catalog-toolbar {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .hh-catalog-sort {

        width: 100%;

    }


    .hh-catalog-sort select {

        flex: 1;

    }


    .hh-catalog-grid {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 520px) {

    .hh-catalog-page {

        padding:
            21px
            13px
            48px;

    }


    .hh-catalog-hero {

        padding:
            28px
            23px;

        border-radius: 21px;

    }


    .hh-catalog-hero h1 {

        font-size: 31px;

        letter-spacing: -1.1px;

    }


    .hh-catalog-hero p {

        font-size: 10px;

    }


    .hh-catalog-search {

        grid-template-columns:
            1fr;

    }


    .hh-catalog-search-button {

        width: 100%;

    }


    .hh-catalog-stats {

        grid-template-columns:
            1fr;

    }


    .hh-catalog-stat:last-child {

        grid-column:
            auto;

    }


    .hh-catalog-active {

        align-items:
            flex-start;

    }


    .hh-catalog-clear {

        width: 100%;

        margin-left: 0;

        margin-top: 3px;

    }


    .hh-catalog-image {

        height: 240px;

    }

}

</style>


<!-- ===============================================================
     CATALOG
================================================================ -->

<main class="hh-catalog-page">


    <div class="hh-catalog-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-catalog-hero">


            <div class="hh-catalog-hero-copy">


                <span class="hh-catalog-pill">

                    <i class="bi bi-stars"></i>

                    DISCOVER • SHOP • SUPPORT LOCAL

                </span>


                <h1>

                    Find Something
                    <span>Worth Bringing Home.</span>

                </h1>


                <p>

                    Explore unique products from approved
                    HochipoHub sellers, discover local businesses
                    and shop everything from one marketplace.

                </p>



                <!-- =================================================
                     SEARCH
                ================================================== -->

                <form
                    method="GET"
                    action="<?= catalogEscape(
                        BASE_URL
                    ) ?>catalog.php"
                    class="hh-catalog-search"
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


                    <?php if ($sort !== 'latest'): ?>

                        <input
                            type="hidden"
                            name="sort"
                            value="<?= catalogEscape(
                                $sort
                            ) ?>"
                        >

                    <?php endif; ?>


                    <div class="hh-catalog-search-field">


                        <i class="bi bi-search"></i>


                        <input
                            type="search"
                            name="search"
                            value="<?= catalogEscape(
                                $search
                            ) ?>"
                            placeholder="Search products, sellers or categories..."
                            autocomplete="off"
                        >


                    </div>


                    <button
                        type="submit"
                        class="hh-catalog-search-button"
                    >

                        Search

                        <i class="bi bi-arrow-right"></i>

                    </button>


                </form>


            </div>



            <!-- ===================================================
                 VISUAL
            ==================================================== -->

            <div class="hh-catalog-hero-visual">


                <div class="hh-catalog-hero-bag">

                    <i class="bi bi-bag-heart"></i>

                </div>


                <div class="hh-catalog-float one">

                    <i class="bi bi-box-seam"></i>

                    <span>

                        <?= number_format(
                            $productCount
                        ) ?>

                        products

                    </span>

                </div>


                <div class="hh-catalog-float two">

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

        <section class="hh-catalog-stats">


            <article class="hh-catalog-stat">


                <div class="hh-catalog-stat-icon blue">

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



            <article class="hh-catalog-stat">


                <div class="hh-catalog-stat-icon purple">

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



            <article class="hh-catalog-stat">


                <div class="hh-catalog-stat-icon green">

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
             CATALOG LAYOUT
        ======================================================== -->

        <section class="hh-catalog-layout">


            <!-- ===================================================
                 SIDEBAR
            ==================================================== -->

            <aside class="hh-catalog-sidebar">


                <!-- ===============================================
                     CATEGORY FILTER
                ================================================ -->

                <section class="hh-catalog-filter-card">


                    <div class="hh-catalog-filter-header">


                        <div class="hh-catalog-filter-icon">

                            <i class="bi bi-grid"></i>

                        </div>


                        <div>

                            <small>
                                BROWSE
                            </small>

                            <h3>
                                Categories
                            </h3>

                        </div>


                    </div>


                    <div class="hh-catalog-filter-list">


                        <a
                            href="<?= catalogEscape(
                                catalogBuildUrl([
                                    'category' => null
                                ])
                            ) ?>"
                            class="<?= $categoryId === 0
                                ? 'active'
                                : '' ?>"
                        >

                            <span>
                                All Products
                            </span>

                            <i class="bi bi-arrow-right"></i>

                        </a>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>


                            <a
                                href="<?= catalogEscape(
                                    catalogBuildUrl([
                                        'category' =>
                                            (int) $category['category_id']
                                    ])
                                ) ?>"
                                class="<?= $categoryId ===
                                    (int) $category['category_id']
                                        ? 'active'
                                        : '' ?>"
                            >

                                <span>

                                    <?= catalogEscape(
                                        $category[
                                            'category_name'
                                        ]
                                    ) ?>

                                </span>


                                <i class="bi bi-chevron-right"></i>

                            </a>


                        <?php endforeach; ?>


                    </div>


                </section>



                <!-- ===============================================
                     SELLER FILTER
                ================================================ -->

                <section class="hh-catalog-filter-card">


                    <div class="hh-catalog-filter-header">


                        <div class="hh-catalog-filter-icon">

                            <i class="bi bi-shop"></i>

                        </div>


                        <div>

                            <small>
                                MARKETPLACE
                            </small>

                            <h3>
                                Sellers
                            </h3>

                        </div>


                    </div>


                    <div class="hh-catalog-filter-list">


                        <a
                            href="<?= catalogEscape(
                                catalogBuildUrl([
                                    'vendor' => null
                                ])
                            ) ?>"
                            class="<?= $vendorId === 0
                                ? 'active'
                                : '' ?>"
                        >

                            <span>
                                All Sellers
                            </span>

                            <i class="bi bi-arrow-right"></i>

                        </a>


                        <?php foreach (
                            $vendors
                            as $vendorItem
                        ): ?>


                            <a
                                href="<?= catalogEscape(
                                    catalogBuildUrl([
                                        'vendor' =>
                                            (int) $vendorItem['vendor_id']
                                    ])
                                ) ?>"
                                class="<?= $vendorId ===
                                    (int) $vendorItem['vendor_id']
                                        ? 'active'
                                        : '' ?>"
                            >

                                <span>

                                    <?= catalogEscape(
                                        $vendorItem[
                                            'business_name'
                                        ]
                                    ) ?>

                                </span>


                                <i class="bi bi-chevron-right"></i>

                            </a>


                        <?php endforeach; ?>


                    </div>


                </section>



                <!-- ===============================================
                     CTA
                ================================================ -->

                <section class="hh-catalog-side-cta">


                    <div class="hh-catalog-side-cta-icon">

                        <i class="bi bi-stars"></i>

                    </div>


                    <h3>

                        Discover something new.

                    </h3>


                    <p>

                        Browse every category and discover
                        more products from local HochipoHub
                        sellers.

                    </p>


                    <a href="<?= catalogEscape(
                        BASE_URL
                    ) ?>category.php">

                        Explore Categories

                        <i class="bi bi-arrow-right"></i>

                    </a>


                </section>


            </aside>



            <!-- ===================================================
                 PRODUCTS
            ==================================================== -->

            <div class="hh-catalog-main">


                <!-- ===============================================
                     TOOLBAR
                ================================================ -->

                <section class="hh-catalog-toolbar">


                    <div>


                        <small>
                            PRODUCTS
                        </small>


                        <h2>


                            <?php if (
                                $activeCategoryName !== ''
                            ): ?>


                                <?= catalogEscape(
                                    $activeCategoryName
                                ) ?>


                            <?php elseif (
                                $activeVendorName !== ''
                            ): ?>


                                <?= catalogEscape(
                                    $activeVendorName
                                ) ?>


                            <?php elseif (
                                $search !== ''
                            ): ?>


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



                    <!-- ===========================================
                         SORT
                    ============================================ -->

                    <form
                        method="GET"
                        action="<?= catalogEscape(
                            BASE_URL
                        ) ?>catalog.php"
                        class="hh-catalog-sort"
                    >


                        <?php if ($search !== ''): ?>

                            <input
                                type="hidden"
                                name="search"
                                value="<?= catalogEscape(
                                    $search
                                ) ?>"
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


                </section>



                <!-- ===============================================
                     ACTIVE FILTERS
                ================================================ -->

                <?php if (
                    $search !== '' ||
                    $categoryId > 0 ||
                    $vendorId > 0
                ): ?>


                    <div class="hh-catalog-active">


                        <span class="hh-catalog-active-label">

                            ACTIVE FILTERS

                        </span>


                        <?php if ($search !== ''): ?>


                            <span class="hh-catalog-filter-tag">

                                <i class="bi bi-search"></i>

                                <?= catalogEscape(
                                    $search
                                ) ?>

                            </span>


                        <?php endif; ?>


                        <?php if (
                            $activeCategoryName !== ''
                        ): ?>


                            <span class="hh-catalog-filter-tag">

                                <i class="bi bi-grid"></i>

                                <?= catalogEscape(
                                    $activeCategoryName
                                ) ?>

                            </span>


                        <?php endif; ?>


                        <?php if (
                            $activeVendorName !== ''
                        ): ?>


                            <span class="hh-catalog-filter-tag">

                                <i class="bi bi-shop"></i>

                                <?= catalogEscape(
                                    $activeVendorName
                                ) ?>

                            </span>


                        <?php endif; ?>


                        <a
                            href="<?= catalogEscape(
                                BASE_URL
                            ) ?>catalog.php"
                            class="hh-catalog-clear"
                        >

                            Clear All

                        </a>


                    </div>


                <?php endif; ?>



                <!-- ===============================================
                     PRODUCT GRID
                ================================================ -->

                <?php if (!empty($products)): ?>


                    <div class="hh-catalog-grid">


                        <?php foreach (
                            $products
                            as $product
                        ): ?>


                            <?php

                            $currentProductId =
                                (int) $product[
                                    'product_id'
                                ];


                            $price =
                                (float) $product[
                                    'price'
                                ];


                            $productImage =
                                catalogProductImage(
                                    $product[
                                        'image'
                                    ]
                                    ?? ''
                                );


                            $description =
                                trim(
                                    (string) (
                                        $product[
                                            'description'
                                        ]
                                        ?? ''
                                    )
                                );

                            ?>


                            <article class="hh-catalog-card">


                                <!-- ===================================
                                     IMAGE
                                ==================================== -->

                                <div class="hh-catalog-image">


                                    <a
                                        href="<?= catalogEscape(
                                            BASE_URL
                                        ) ?>product_details.php?id=<?= $currentProductId ?>"
                                    >


                                        <?php if (
                                            $productImage !== ''
                                        ): ?>


                                            <img
                                                src="<?= catalogEscape(
                                                    $productImage
                                                ) ?>"
                                                alt="<?= catalogEscape(
                                                    $product[
                                                        'product_name'
                                                    ]
                                                ) ?>"
                                                loading="lazy"
                                                onerror="
                                                    this.style.display='none';
                                                    this.parentElement.innerHTML='<div class=&quot;hh-catalog-placeholder&quot;><i class=&quot;bi bi-bag&quot;></i></div>';
                                                "
                                            >


                                        <?php else: ?>


                                            <div class="hh-catalog-placeholder">

                                                <i class="bi bi-bag"></i>

                                            </div>


                                        <?php endif; ?>


                                    </a>



                                    <!-- CATEGORY -->

                                    <span class="hh-catalog-category-badge">

                                        <?= catalogEscape(
                                            $product[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </span>



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
                                                hh-catalog-wishlist
                                            "
                                            data-product-id="<?= $currentProductId ?>"
                                            title="Add to wishlist"
                                            aria-label="Add to wishlist"
                                        >

                                            <i class="bi bi-heart-fill"></i>

                                        </button>


                                    <?php endif; ?>



                                    <!-- STOCK -->

                                    <span class="hh-catalog-stock">

                                        <i class="bi bi-circle-fill"></i>

                                        <?= (int)
                                            $product[
                                                'stock_quantity'
                                            ] ?>

                                        left

                                    </span>


                                </div>



                                <!-- ===================================
                                     BODY
                                ==================================== -->

                                <div class="hh-catalog-card-body">


                                    <div class="hh-catalog-vendor">

                                        <i class="bi bi-shop"></i>

                                        <?= catalogEscape(
                                            $product[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <h3>


                                        <a
                                            href="<?= catalogEscape(
                                                BASE_URL
                                            ) ?>product_details.php?id=<?= $currentProductId ?>"
                                        >

                                            <?= catalogEscape(
                                                $product[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </a>


                                    </h3>


                                    <?php if (
                                        $description !== ''
                                    ): ?>


                                        <p class="hh-catalog-description">

                                            <?= catalogEscape(
                                                $description
                                            ) ?>

                                        </p>


                                    <?php else: ?>


                                        <p class="hh-catalog-description">

                                            Discover this product
                                            from

                                            <?= catalogEscape(
                                                $product[
                                                    'business_name'
                                                ]
                                            ) ?>.

                                        </p>


                                    <?php endif; ?>



                                    <!-- =================================
                                         FOOTER
                                    ================================== -->

                                    <div class="hh-catalog-card-footer">


                                        <div>


                                            <span class="hh-catalog-price-label">

                                                PRICE

                                            </span>


                                            <strong class="hh-catalog-price">

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
                                                    hh-catalog-add
                                                "
                                                data-product-id="<?= $currentProductId ?>"
                                            >

                                                <i class="bi bi-cart-plus"></i>

                                                Add

                                            </button>


                                        <?php else: ?>


                                            <a
                                                href="<?= catalogEscape(
                                                    BASE_URL
                                                ) ?>index.php?login=1"
                                                class="hh-catalog-add"
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


                    <!-- ===============================================
                         EMPTY
                    ================================================ -->

                    <section class="hh-catalog-empty">


                        <div class="hh-catalog-empty-inner">


                            <div class="hh-catalog-empty-icon">

                                <i class="bi bi-search"></i>

                            </div>


                            <small>
                                NOTHING HERE YET
                            </small>


                            <h2>

                                No products found

                            </h2>


                            <p>

                                Try another search keyword,
                                category or seller. Your next
                                favourite product might be under
                                a different filter.

                            </p>


                            <a
                                href="<?= catalogEscape(
                                    BASE_URL
                                ) ?>catalog.php"
                            >

                                <i class="bi bi-grid"></i>

                                View All Products

                            </a>


                        </div>


                    </section>


                <?php endif; ?>


            </div>


        </section>


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


if (
    file_exists(
        $footerPath
    )
) {

    require_once $footerPath;
}

?>