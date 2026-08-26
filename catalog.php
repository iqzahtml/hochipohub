<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CATALOG
|--------------------------------------------------------------------------
| File: catalog.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


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

    function catalogProductImage($image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }

        if (
            str_starts_with($image, 'http://') ||
            str_starts_with($image, 'https://')
        ) {
            return $image;
        }

        if (str_starts_with($image, 'uploads/')) {
            return BASE_URL . ltrim($image, '/');
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

    function catalogBuildUrl(array $changes = []): string
    {
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

        $query = http_build_query($params);

        return
            BASE_URL .
            'catalog.php' .
            ($query !== '' ? '?' . $query : '');
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN STATE
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
                ?? $_SESSION['user_role']
                ?? ''
            )
        )
    );


$isCustomerLoggedIn =
    $userId > 0 &&
    $currentRole === 'customer';


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$csrfToken = '';

if ($isCustomerLoggedIn) {

    if (function_exists('generateCsrfToken')) {

        $csrfToken =
            generateCsrfToken();

    } elseif (function_exists('csrfToken')) {

        $csrfToken =
            csrfToken();
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


$categoryId =
    (int) (
        $_GET['category']
        ?? 0
    );


$vendorId =
    (int) (
        $_GET['vendor']
        ?? 0
    );


$sort =
    trim(
        (string) (
            $_GET['sort']
            ?? 'latest'
        )
    );


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
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $stmt = $db->query("
        SELECT
            category_id,
            category_name
        FROM categories
        ORDER BY category_name ASC
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
| LOAD VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];

try {

    $stmt = $db->query("
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

    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $vendors = [];
}


/*
|--------------------------------------------------------------------------
| ACTIVE NAMES
|--------------------------------------------------------------------------
*/

$activeCategoryName = '';
$activeVendorName = '';


if ($categoryId > 0) {

    try {

        $stmt = $db->prepare("
            SELECT category_name
            FROM categories
            WHERE category_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $categoryId
        ]);

        $activeCategoryName =
            (string) (
                $stmt->fetchColumn()
                ?: ''
            );

    } catch (Throwable $e) {
        $activeCategoryName = '';
    }
}


if ($vendorId > 0) {

    try {

        $stmt = $db->prepare("
            SELECT business_name
            FROM vendors
            WHERE vendor_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $vendorId
        ]);

        $activeVendorName =
            (string) (
                $stmt->fetchColumn()
                ?: ''
            );

    } catch (Throwable $e) {
        $activeVendorName = '';
    }
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

$orderBy = 'p.created_at DESC';

switch ($sort) {

    case 'price_low':
        $orderBy = 'p.price ASC';
        break;

    case 'price_high':
        $orderBy = 'p.price DESC';
        break;

    case 'name':
        $orderBy = 'p.product_name ASC';
        break;

    case 'oldest':
        $orderBy = 'p.created_at ASC';
        break;
}


/*
|--------------------------------------------------------------------------
| PRODUCTS QUERY
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


if ($search !== '') {

    $sql .= "
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
        )
    ";

    $searchTerm =
        '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}


if ($categoryId > 0) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] = $categoryId;
}


if ($vendorId > 0) {

    $sql .= "
        AND p.vendor_id = ?
    ";

    $params[] = $vendorId;
}


$sql .= "
    ORDER BY {$orderBy}
";


try {

    $stmt =
        $db->prepare($sql);

    $stmt->execute($params);

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
    count($products);

$categoryCount =
    count($categories);

$vendorCount =
    count($vendors);

$cartCount = 0;
$wishlistCount = 0;


if ($isCustomerLoggedIn) {

    try {

        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(quantity), 0)
            FROM cart
            WHERE customer_id = ?
        ");

        $stmt->execute([
            $userId
        ]);

        $cartCount =
            (int) $stmt->fetchColumn();

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
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {
        $wishlistCount = 0;
    }
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Catalog - ' .
    SITE_NAME;

$hideSiteMainWrapper = true;

$extraCSS = [
    'dashboard.css'
];

require_once __DIR__ .
    '/includes/header.php';


if ($isCustomerLoggedIn) {

    require_once __DIR__ .
        '/includes/customer_sidebar.php';
}

?>


<style>

/* ================================================================
   PAGE
================================================================ */

.hh-catalog-page {
    width: 100%;
    min-height: 100vh;
    padding: 42px 24px 75px;

    background:
        radial-gradient(
            circle at 92% 4%,
            rgba(59,130,246,.08),
            transparent 24%
        ),
        linear-gradient(
            180deg,
            #f5f8ff,
            #ffffff
        );

    font-family:
        Inter,
        Arial,
        sans-serif;
}


.hh-catalog-container {
    width: 100%;
    max-width: 1340px;
    margin: 0 auto;
}


/* ================================================================
   HERO
================================================================ */

.hh-catalog-hero {
    position: relative;

    min-height: 340px;

    margin-bottom: 23px;

    padding: 48px 52px;

    overflow: hidden;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        370px;

    align-items: center;

    gap: 35px;

    color: #ffffff;

    background:
        linear-gradient(
            115deg,
            #0b2b69,
            #174998 48%,
            #2683ef
        );

    border-radius: 28px;

    box-shadow:
        0 20px 50px
        rgba(23,79,165,.16);
}


.hh-catalog-hero::before {
    content: "";

    position: absolute;

    width: 300px;
    height: 300px;

    right: -70px;
    top: -160px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}


.hh-catalog-hero-copy {
    position: relative;
    z-index: 2;
}


.hh-catalog-pill {
    min-height: 34px;

    padding: 0 13px;

    margin-bottom: 18px;

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #ffffff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius: 999px;

    font-size: 9px;

    font-weight: 900;
}


.hh-catalog-hero h1 {
    max-width: 720px;

    margin: 0;

    color: #ffffff;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:
        clamp(
            36px,
            4.7vw,
            57px
        );

    line-height: 1.08;

    font-weight: 800;

    letter-spacing: -2px;
}


.hh-catalog-hero h1 span {
    color: #6fe7f3;
}


.hh-catalog-hero p {
    max-width: 610px;

    margin: 16px 0 0;

    color:
        rgba(255,255,255,.76);

    font-size: 12px;

    line-height: 1.75;
}


/* ================================================================
   SEARCH
================================================================ */

.hh-catalog-search {
    max-width: 690px;

    margin-top: 24px;

    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        auto;

    gap: 8px;
}


.hh-catalog-search-field {
    height: 49px;

    padding: 0 14px;

    display: flex;

    align-items: center;

    gap: 9px;

    background: #ffffff;

    border-radius: 12px;
}


.hh-catalog-search-field i {
    color: #2563eb;
}


.hh-catalog-search-field input {
    width: 100%;
    height: 100%;

    outline: 0;

    color: #334155;

    background: transparent;

    border: 0;

    font-size: 10px;
}


.hh-catalog-search button {
    min-width: 105px;

    border: 0;

    color: #1e56a8;

    background: #ffffff;

    border-radius: 12px;

    font-size: 9px;

    font-weight: 900;

    cursor: pointer;
}


/* ================================================================
   HERO ART
================================================================ */

.hh-catalog-art {
    position: relative;

    z-index: 2;

    height: 220px;
}


.hh-catalog-art-main {
    position: absolute;

    width: 155px;
    height: 155px;

    top: 35px;
    right: 80px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius: 40px;

    font-size: 63px;

    transform:
        rotate(-4deg);
}


.hh-catalog-floating {
    position: absolute;

    min-width: 135px;

    padding: 11px 13px;

    display: flex;

    align-items: center;

    gap: 8px;

    color: #23405f;

    background: #ffffff;

    border-radius: 12px;

    box-shadow:
        0 13px 30px
        rgba(0,30,80,.18);

    font-size: 8px;

    font-weight: 850;
}


.hh-catalog-floating.one {
    top: 4px;
    left: 0;
}


.hh-catalog-floating.two {
    bottom: 4px;
    right: 0;
}


/* ================================================================
   STATS
================================================================ */

.hh-catalog-stats {
    margin-bottom: 22px;

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 15px;
}


.hh-catalog-stat {
    min-height: 88px;

    padding: 16px 18px;

    display: flex;

    align-items: center;

    gap: 12px;

    background: #ffffff;

    border:
        1px solid #e2e9f4;

    border-radius: 16px;
}


.hh-catalog-stat-icon {
    width: 43px;
    height: 43px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 12px;
}


.hh-catalog-stat span {
    display: block;

    color: #8a98aa;

    font-size: 7px;

    font-weight: 850;
}


.hh-catalog-stat strong {
    display: block;

    margin-top: 3px;

    color: #17233c;

    font-size: 18px;

    font-weight: 900;
}


/* ================================================================
   LAYOUT
================================================================ */

.hh-catalog-layout {
    display: grid;

    grid-template-columns:
        235px
        minmax(0,1fr);

    gap: 20px;

    align-items: start;
}


/* ================================================================
   FILTER
================================================================ */

.hh-catalog-sidebar {
    position: sticky;

    top: 20px;

    display: flex;

    flex-direction: column;

    gap: 15px;
}


.hh-filter-card {
    padding: 18px;

    background: #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius: 17px;
}


.hh-filter-card h3 {
    margin: 0 0 13px;

    color: #17233c;

    font-size: 12px;

    font-weight: 900;
}


.hh-filter-list {
    display: flex;

    flex-direction: column;

    gap: 4px;
}


.hh-filter-list a {
    min-height: 36px;

    padding: 0 9px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    color: #6b7c93;

    border-radius: 8px;

    font-size: 8px;

    font-weight: 700;

    text-decoration: none;
}


.hh-filter-list a:hover,
.hh-filter-list a.active {
    color: #2563eb;

    background: #eff6ff;
}


/* ================================================================
   TOOLBAR
================================================================ */

.hh-catalog-toolbar {
    min-height: 85px;

    margin-bottom: 16px;

    padding: 17px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    background: #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius: 17px;
}


.hh-catalog-toolbar h2 {
    margin: 0 0 3px;

    color: #17233c;

    font-size: 17px;

    font-weight: 900;
}


.hh-catalog-toolbar p {
    margin: 0;

    color: #8a98aa;

    font-size: 8px;
}


.hh-catalog-sort {
    display: flex;

    align-items: center;

    gap: 7px;
}


.hh-catalog-sort select {
    height: 38px;

    padding: 0 10px;

    color: #475569;

    background: #f8fafc;

    border:
        1px solid #dce5ef;

    border-radius: 9px;

    font-size: 8px;
}


/* ================================================================
   GRID
================================================================ */

.hh-catalog-grid {
    display: grid;

    grid-template-columns:
        repeat(3,minmax(0,1fr));

    gap: 17px;
}


/* ================================================================
   CARD
================================================================ */

.hh-product-card {
    min-width: 0;

    overflow: hidden;

    display: flex;

    flex-direction: column;

    background: #ffffff;

    border:
        1px solid #e1e9f4;

    border-radius: 18px;

    box-shadow:
        0 7px 22px
        rgba(40,65,120,.045);

    transition: .2s ease;
}


.hh-product-card:hover {
    transform:
        translateY(-4px);

    box-shadow:
        0 14px 30px
        rgba(40,65,120,.10);
}


/* ================================================================
   IMAGE
================================================================ */

.hh-product-image {
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


.hh-product-image > a {
    width: 100%;
    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;
}


.hh-product-image img {
    width: 100%;
    height: 100%;

    padding: 10px;

    object-fit: contain;

    object-position: center;
}


.hh-product-category {
    position: absolute;

    top: 10px;
    left: 10px;

    min-height: 24px;

    padding: 0 8px;

    display: inline-flex;

    align-items: center;

    color: #2563eb;

    background:
        rgba(255,255,255,.94);

    border-radius: 8px;

    font-size: 6px;

    font-weight: 850;
}


.hh-product-stock {
    position: absolute;

    left: 10px;
    bottom: 10px;

    min-height: 25px;

    padding: 0 8px;

    display: inline-flex;

    align-items: center;

    color: #15803d;

    background:
        rgba(240,253,244,.95);

    border-radius: 999px;

    font-size: 6px;

    font-weight: 850;
}


/* ================================================================
   HEART
================================================================ */

.hh-wishlist-button {
    position: absolute;

    top: 10px;
    right: 10px;

    width: 37px;
    height: 37px;

    z-index: 5;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #e11d48;

    background:
        rgba(255,255,255,.96);

    border:
        1px solid #ffe1e7;

    border-radius: 10px;

    font-size: 14px;

    cursor: pointer;
}


.hh-wishlist-button.saved {
    color: #ffffff;

    background: #e11d48;
}


/* ================================================================
   BODY
================================================================ */

.hh-product-body {
    flex: 1;

    padding: 15px;

    display: flex;

    flex-direction: column;
}


.hh-product-vendor {
    margin-bottom: 6px;

    color: #8795aa;

    font-size: 7px;

    font-weight: 700;
}


.hh-product-vendor i {
    color: #2563eb;
}


.hh-product-body h3 {
    min-height: 38px;

    margin: 0;

    display: -webkit-box;

    overflow: hidden;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    font-size: 12px;

    line-height: 1.45;
}


.hh-product-body h3 a {
    color: #17233c;

    font-weight: 900;

    text-decoration: none;
}


.hh-product-description {
    min-height: 34px;

    margin: 8px 0 0;

    display: -webkit-box;

    overflow: hidden;

    color: #8a98ac;

    font-size: 7px;

    line-height: 1.6;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;
}


/* ================================================================
   CARD FOOTER
================================================================ */

.hh-product-footer {
    margin-top: auto;

    padding-top: 13px;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 10px;

    border-top:
        1px solid #edf1f5;
}


.hh-product-price small {
    display: block;

    color: #97a4b5;

    font-size: 6px;

    font-weight: 850;
}


.hh-product-price strong {
    display: block;

    margin-top: 2px;

    color: #1b4a87;

    font-size: 16px;

    font-weight: 900;
}


/* ================================================================
   ADD BUTTON
================================================================ */

.hh-add-cart {
    min-height: 38px;

    padding: 0 12px;

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

    font-size: 8px;

    font-weight: 850;

    cursor: pointer;

    text-decoration: none;
}


.hh-add-cart.added {
    background:
        linear-gradient(
            135deg,
            #16a34a,
            #22c55e
        );
}


.hh-add-cart:disabled,
.hh-wishlist-button:disabled {
    opacity: .65;

    cursor: wait;
}


/* ================================================================
   EMPTY
================================================================ */

.hh-catalog-empty {
    min-height: 350px;

    padding: 40px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    background: #ffffff;

    border:
        1px dashed #bdd8f7;

    border-radius: 18px;
}


.hh-catalog-empty i {
    margin-bottom: 12px;

    color: #2563eb;

    font-size: 35px;
}


.hh-catalog-empty h2 {
    margin: 0 0 7px;

    color: #17233c;
}


.hh-catalog-empty p {
    color: #8492a6;

    font-size: 9px;
}


/* ================================================================
   TOAST
================================================================ */

.hh-toast-container {
    position: fixed;

    top: 90px;
    right: 24px;

    z-index: 999999;

    width:
        min(
            370px,
            calc(100vw - 30px)
        );

    display: flex;

    flex-direction: column;

    gap: 10px;
}


.hh-toast {
    min-height: 67px;

    padding: 13px;

    display: grid;

    grid-template-columns:
        40px
        minmax(0,1fr)
        28px;

    align-items: center;

    gap: 10px;

    background:
        rgba(255,255,255,.98);

    border:
        1px solid #e1e8f1;

    border-radius: 15px;

    box-shadow:
        0 18px 45px
        rgba(28,54,100,.17);

    opacity: 0;

    transform:
        translateX(20px);

    transition: .23s ease;
}


.hh-toast.show {
    opacity: 1;

    transform:
        translateX(0);
}


.hh-toast-icon {
    width: 40px;
    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    font-size: 15px;
}


.hh-toast.success .hh-toast-icon {
    color: #15803d;

    background: #ecfdf3;
}


.hh-toast.error .hh-toast-icon {
    color: #dc2626;

    background: #fef2f2;
}


.hh-toast.info .hh-toast-icon {
    color: #e11d48;

    background: #fff1f2;
}


.hh-toast-copy strong {
    display: block;

    margin-bottom: 2px;

    color: #263a55;

    font-size: 9px;
}


.hh-toast-copy span {
    display: block;

    color: #738399;

    font-size: 8px;

    line-height: 1.45;
}


.hh-toast-close {
    width: 28px;
    height: 28px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #94a3b8;

    background: transparent;

    border: 0;

    border-radius: 7px;

    cursor: pointer;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1100px) {

    .hh-catalog-grid {
        grid-template-columns:
            repeat(2,1fr);
    }
}


@media (max-width: 900px) {

    .hh-catalog-hero {
        grid-template-columns: 1fr;
    }

    .hh-catalog-art {
        display: none;
    }

    .hh-catalog-layout {
        grid-template-columns: 1fr;
    }

    .hh-catalog-sidebar {
        position: static;

        display: grid;

        grid-template-columns:
            1fr
            1fr;
    }
}


@media (max-width: 650px) {

    .hh-catalog-page {
        padding:
            22px
            13px
            50px;
    }

    .hh-catalog-hero {
        padding: 28px 23px;
    }

    .hh-catalog-hero h1 {
        font-size: 31px;
    }

    .hh-catalog-search {
        grid-template-columns: 1fr;
    }

    .hh-catalog-stats {
        grid-template-columns: 1fr;
    }

    .hh-catalog-sidebar {
        grid-template-columns: 1fr;
    }

    .hh-catalog-toolbar {
        align-items: flex-start;

        flex-direction: column;
    }

    .hh-catalog-grid {
        grid-template-columns: 1fr;
    }

    .hh-product-image {
        height: 245px;
    }

    .hh-toast-container {
        top: 80px;

        left: 15px;
        right: 15px;

        width: auto;
    }
}

</style>


<!-- ===============================================================
     TOAST CONTAINER
================================================================ -->

<div
    class="hh-toast-container"
    id="hhToastContainer"
></div>


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

                    <span>
                        Worth Bringing Home.
                    </span>

                </h1>


                <p>

                    Explore products from approved
                    HochipoHub sellers and discover
                    something worth adding to your cart.

                </p>


                <form
                    method="GET"
                    action="catalog.php"
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


                    <div class="hh-catalog-search-field">

                        <i class="bi bi-search"></i>

                        <input
                            type="search"
                            name="search"
                            value="<?= catalogEscape(
                                $search
                            ) ?>"
                            placeholder="Search products, sellers or categories..."
                        >

                    </div>


                    <button type="submit">

                        Search

                    </button>


                </form>


            </div>



            <div class="hh-catalog-art">


                <div class="hh-catalog-art-main">

                    <i class="bi bi-bag-heart"></i>

                </div>


                <div class="hh-catalog-floating one">

                    📦

                    <?= number_format(
                        $productCount
                    ) ?>

                    products

                </div>


                <div class="hh-catalog-floating two">

                    🏪

                    <?= number_format(
                        $vendorCount
                    ) ?>

                    sellers

                </div>


            </div>


        </section>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="hh-catalog-stats">


            <article class="hh-catalog-stat">

                <div class="hh-catalog-stat-icon">

                    <i class="bi bi-box-seam"></i>

                </div>

                <div>

                    <span>
                        PRODUCTS
                    </span>

                    <strong>
                        <?= $productCount ?>
                    </strong>

                </div>

            </article>


            <article class="hh-catalog-stat">

                <div class="hh-catalog-stat-icon">

                    <i class="bi bi-grid"></i>

                </div>

                <div>

                    <span>
                        CATEGORIES
                    </span>

                    <strong>
                        <?= $categoryCount ?>
                    </strong>

                </div>

            </article>


            <article class="hh-catalog-stat">

                <div class="hh-catalog-stat-icon">

                    <i class="bi bi-shop"></i>

                </div>

                <div>

                    <span>
                        SELLERS
                    </span>

                    <strong>
                        <?= $vendorCount ?>
                    </strong>

                </div>

            </article>


        </section>



        <!-- =======================================================
             LAYOUT
        ======================================================== -->

        <section class="hh-catalog-layout">


            <!-- ===================================================
                 SIDEBAR
            ==================================================== -->

            <aside class="hh-catalog-sidebar">


                <div class="hh-filter-card">

                    <h3>

                        <i class="bi bi-grid"></i>

                        Categories

                    </h3>


                    <div class="hh-filter-list">


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

                            All Products

                            <i class="bi bi-chevron-right"></i>

                        </a>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>


                            <a
                                href="<?= catalogEscape(
                                    catalogBuildUrl([
                                        'category' =>
                                            (int)
                                            $category[
                                                'category_id'
                                            ]
                                    ])
                                ) ?>"
                                class="<?= $categoryId ===
                                (int)
                                $category['category_id']
                                    ? 'active'
                                    : '' ?>"
                            >

                                <?= catalogEscape(
                                    $category[
                                        'category_name'
                                    ]
                                ) ?>

                                <i class="bi bi-chevron-right"></i>

                            </a>


                        <?php endforeach; ?>


                    </div>

                </div>



                <div class="hh-filter-card">

                    <h3>

                        <i class="bi bi-shop"></i>

                        Sellers

                    </h3>


                    <div class="hh-filter-list">


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

                            All Sellers

                            <i class="bi bi-chevron-right"></i>

                        </a>


                        <?php foreach (
                            $vendors
                            as $vendor
                        ): ?>


                            <a
                                href="<?= catalogEscape(
                                    catalogBuildUrl([
                                        'vendor' =>
                                            (int)
                                            $vendor[
                                                'vendor_id'
                                            ]
                                    ])
                                ) ?>"
                                class="<?= $vendorId ===
                                (int)
                                $vendor['vendor_id']
                                    ? 'active'
                                    : '' ?>"
                            >

                                <?= catalogEscape(
                                    $vendor[
                                        'business_name'
                                    ]
                                ) ?>

                                <i class="bi bi-chevron-right"></i>

                            </a>


                        <?php endforeach; ?>


                    </div>

                </div>


            </aside>



            <!-- ===================================================
                 PRODUCTS
            ==================================================== -->

            <div>


                <section class="hh-catalog-toolbar">


                    <div>

                        <h2>

                            <?php if ($activeCategoryName !== ''): ?>

                                <?= catalogEscape(
                                    $activeCategoryName
                                ) ?>

                            <?php elseif ($activeVendorName !== ''): ?>

                                <?= catalogEscape(
                                    $activeVendorName
                                ) ?>

                            <?php elseif ($search !== ''): ?>

                                Search Results

                            <?php else: ?>

                                All Products

                            <?php endif; ?>

                        </h2>


                        <p>

                            <?= $productCount ?>

                            product<?= $productCount !== 1
                                ? 's'
                                : '' ?>

                            found

                        </p>

                    </div>



                    <form
                        method="GET"
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


                        <select
                            name="sort"
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



                <?php if (!empty($products)): ?>


                    <div class="hh-catalog-grid">


                        <?php foreach (
                            $products
                            as $product
                        ): ?>


                            <?php

                            $productId =
                                (int)
                                $product['product_id'];


                            $productImage =
                                catalogProductImage(
                                    $product['image']
                                    ?? ''
                                );

                            ?>


                            <article class="hh-product-card">


                                <div class="hh-product-image">


                                    <a
                                        href="product_details.php?id=<?= $productId ?>"
                                    >


                                        <?php if ($productImage !== ''): ?>


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
                                            >


                                        <?php else: ?>


                                            <i
                                                class="bi bi-image"
                                                style="
                                                    font-size:35px;
                                                    color:#2563eb;
                                                "
                                            ></i>


                                        <?php endif; ?>


                                    </a>



                                    <span class="hh-product-category">

                                        <?= catalogEscape(
                                            $product[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </span>



                                    <span class="hh-product-stock">

                                        <?= (int)
                                            $product[
                                                'stock_quantity'
                                            ] ?>

                                        in stock

                                    </span>



                                    <?php if ($isCustomerLoggedIn): ?>


                                        <button
                                            type="button"
                                            class="
                                                hh-wishlist-button
                                                js-wishlist-button
                                            "
                                            data-product-id="<?= $productId ?>"
                                            data-csrf="<?= catalogEscape(
                                                $csrfToken
                                            ) ?>"
                                            title="Add to wishlist"
                                        >

                                            <i class="bi bi-heart-fill"></i>

                                        </button>


                                    <?php endif; ?>


                                </div>



                                <div class="hh-product-body">


                                    <div class="hh-product-vendor">

                                        <i class="bi bi-shop"></i>

                                        <?= catalogEscape(
                                            $product[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <h3>

                                        <a
                                            href="product_details.php?id=<?= $productId ?>"
                                        >

                                            <?= catalogEscape(
                                                $product[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </a>

                                    </h3>


                                    <p class="hh-product-description">

                                        <?= catalogEscape(
                                            $product[
                                                'description'
                                            ]
                                            ?: 'Discover this product from HochipoHub.'
                                        ) ?>

                                    </p>



                                    <div class="hh-product-footer">


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



                                        <?php if ($isCustomerLoggedIn): ?>


                                            <button
                                                type="button"
                                                class="
                                                    hh-add-cart
                                                    js-add-cart
                                                "
                                                data-product-id="<?= $productId ?>"
                                                data-csrf="<?= catalogEscape(
                                                    $csrfToken
                                                ) ?>"
                                            >

                                                <i class="bi bi-cart-plus"></i>

                                                Add

                                            </button>


                                        <?php elseif ($userId <= 0): ?>


                                            <a
                                                href="index.php?login=1"
                                                class="hh-add-cart"
                                            >

                                                Login

                                            </a>


                                        <?php else: ?>


                                            <button
                                                type="button"
                                                class="hh-add-cart"
                                                disabled
                                            >

                                                Customer Only

                                            </button>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="hh-catalog-empty">

                        <i class="bi bi-search"></i>

                        <h2>
                            No products found
                        </h2>

                        <p>
                            Try another search, category or seller.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </div>

</main>



<!-- ===============================================================
     CATALOG AJAX
================================================================ -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | URLS FROM PHP
        |--------------------------------------------------------------------------
        */

        const addCartUrl =
            <?= json_encode(
                BASE_URL .
                'ajax/add_cart.php'
            ) ?>;


        const addWishlistUrl =
            <?= json_encode(
                BASE_URL .
                'ajax/add_wishlist.php'
            ) ?>;


        /*
        |--------------------------------------------------------------------------
        | ADD CART
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '.js-add-cart'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        async function () {

                            const productId =
                                button.dataset.productId;


                            const csrf =
                                button.dataset.csrf;


                            if (!productId) {

                                showToast(
                                    'error',
                                    'Unable to add product',
                                    'Product ID is missing.'
                                );

                                return;
                            }


                            if (!csrf) {

                                showToast(
                                    'error',
                                    'Security error',
                                    'CSRF token is missing. Refresh the page.'
                                );

                                return;
                            }


                            const original =
                                button.innerHTML;


                            button.disabled =
                                true;


                            button.innerHTML =
                                '<i class="bi bi-hourglass-split"></i> Adding...';


                            try {

                                const formData =
                                    new FormData();


                                formData.append(
                                    'product_id',
                                    productId
                                );


                                formData.append(
                                    'quantity',
                                    '1'
                                );


                                formData.append(
                                    'csrf_token',
                                    csrf
                                );


                                const response =
                                    await fetch(
                                        addCartUrl,
                                        {
                                            method: 'POST',
                                            body: formData,
                                            credentials: 'same-origin'
                                        }
                                    );


                                const responseText =
                                    await response.text();


                                let data;


                                try {

                                    data =
                                        JSON.parse(
                                            responseText
                                        );

                                } catch (error) {

                                    console.error(
                                        'Invalid add cart response:',
                                        responseText
                                    );


                                    throw new Error(
                                        'Server did not return valid JSON.'
                                    );
                                }


                                if (
                                    !response.ok ||
                                    data.success !== true
                                ) {

                                    throw new Error(
                                        data.message ||
                                        'Unable to add product to cart.'
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | SUCCESS
                                |--------------------------------------------------------------------------
                                */

                                button.classList.add(
                                    'added'
                                );


                                button.innerHTML =
                                    '<i class="bi bi-check-lg"></i> Added';


                                showToast(
                                    'success',
                                    'Added to cart',
                                    data.message ||
                                    'Product added successfully.'
                                );


                                if (
                                    data.cart_count !== undefined
                                ) {

                                    updateSidebarBadge(
                                        'cart.php',
                                        data.cart_count
                                    );
                                }


                                setTimeout(
                                    function () {

                                        button.classList.remove(
                                            'added'
                                        );


                                        button.innerHTML =
                                            original;

                                    },
                                    1800
                                );


                            } catch (error) {

                                console.error(
                                    error
                                );


                                button.innerHTML =
                                    original;


                                showToast(
                                    'error',
                                    'Cart error',
                                    error.message
                                );


                            } finally {

                                button.disabled =
                                    false;
                            }

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | WISHLIST
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '.js-wishlist-button'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        async function () {

                            const productId =
                                button.dataset.productId;


                            const csrf =
                                button.dataset.csrf;


                            if (!productId) {

                                showToast(
                                    'error',
                                    'Wishlist error',
                                    'Product ID is missing.'
                                );

                                return;
                            }


                            if (!csrf) {

                                showToast(
                                    'error',
                                    'Security error',
                                    'CSRF token is missing. Refresh the page.'
                                );

                                return;
                            }


                            button.disabled =
                                true;


                            try {

                                const formData =
                                    new FormData();


                                formData.append(
                                    'product_id',
                                    productId
                                );


                                formData.append(
                                    'csrf_token',
                                    csrf
                                );


                                const response =
                                    await fetch(
                                        addWishlistUrl,
                                        {
                                            method: 'POST',
                                            body: formData,
                                            credentials: 'same-origin'
                                        }
                                    );


                                const responseText =
                                    await response.text();


                                let data;


                                try {

                                    data =
                                        JSON.parse(
                                            responseText
                                        );

                                } catch (error) {

                                    console.error(
                                        'Invalid wishlist response:',
                                        responseText
                                    );


                                    throw new Error(
                                        'Server did not return valid JSON.'
                                    );
                                }


                                if (
                                    !response.ok ||
                                    data.success !== true
                                ) {

                                    throw new Error(
                                        data.message ||
                                        'Unable to add product to wishlist.'
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | HEART ACTIVE
                                |--------------------------------------------------------------------------
                                */

                                button.classList.add(
                                    'saved'
                                );


                                showToast(
                                    data.already_exists
                                        ? 'info'
                                        : 'success',
                                    data.already_exists
                                        ? 'Already saved'
                                        : 'Added to wishlist',
                                    data.message ||
                                    'Product saved successfully.'
                                );


                                if (
                                    data.wishlist_count !== undefined
                                ) {

                                    updateSidebarBadge(
                                        'wishlist.php',
                                        data.wishlist_count
                                    );
                                }


                            } catch (error) {

                                console.error(
                                    error
                                );


                                showToast(
                                    'error',
                                    'Wishlist error',
                                    error.message
                                );


                            } finally {

                                button.disabled =
                                    false;
                            }

                        }
                    );

                }
            );

    }
);


/*
|--------------------------------------------------------------------------
| UPDATE CUSTOMER NAV BADGE
|--------------------------------------------------------------------------
*/

function updateSidebarBadge(
    page,
    count
) {

    count =
        parseInt(
            count,
            10
        ) || 0;


    const links =
        document.querySelectorAll(
            'a[href$="' +
            page +
            '"]'
        );


    links.forEach(
        function (link) {

            let badge =
                link.querySelector(
                    '.sidebar-badge'
                );


            if (count <= 0) {

                if (badge) {
                    badge.remove();
                }

                return;
            }


            if (!badge) {

                badge =
                    document.createElement(
                        'span'
                    );


                badge.className =
                    'sidebar-badge';


                link.appendChild(
                    badge
                );
            }


            badge.textContent =
                count > 99
                    ? '99+'
                    : count;

        }
    );

}


/*
|--------------------------------------------------------------------------
| TOAST
|--------------------------------------------------------------------------
*/

function showToast(
    type,
    title,
    message
) {

    const container =
        document.getElementById(
            'hhToastContainer'
        );


    if (!container) {
        return;
    }


    const toast =
        document.createElement(
            'div'
        );


    toast.className =
        'hh-toast ' +
        type;


    let icon =
        'bi-info-circle-fill';


    if (type === 'success') {
        icon =
            'bi-check-circle-fill';

    } else if (type === 'error') {
        icon =
            'bi-exclamation-circle-fill';

    } else if (type === 'info') {
        icon =
            'bi-heart-fill';
    }


    toast.innerHTML = `
        <div class="hh-toast-icon">
            <i class="bi ${icon}"></i>
        </div>

        <div class="hh-toast-copy">
            <strong>
                ${escapeCatalogHtml(title)}
            </strong>

            <span>
                ${escapeCatalogHtml(message)}
            </span>
        </div>

        <button
            type="button"
            class="hh-toast-close"
        >
            <i class="bi bi-x-lg"></i>
        </button>
    `;


    container.appendChild(
        toast
    );


    requestAnimationFrame(
        function () {

            toast.classList.add(
                'show'
            );

        }
    );


    toast
        .querySelector(
            '.hh-toast-close'
        )
        .addEventListener(
            'click',
            function () {

                removeToast(
                    toast
                );

            }
        );


    setTimeout(
        function () {

            removeToast(
                toast
            );

        },
        3500
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE TOAST
|--------------------------------------------------------------------------
*/

function removeToast(
    toast
) {

    if (!toast) {
        return;
    }


    toast.classList.remove(
        'show'
    );


    setTimeout(
        function () {

            if (
                toast.parentNode
            ) {
                toast.remove();
            }

        },
        250
    );

}


/*
|--------------------------------------------------------------------------
| ESCAPE
|--------------------------------------------------------------------------
*/

function escapeCatalogHtml(
    value
) {

    return String(
        value ?? ''
    )
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

}

</script>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>