<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CUSTOMER DASHBOARD
|--------------------------------------------------------------------------
| File: dashboard.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
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
| LOGIN STATE
|--------------------------------------------------------------------------
*/

$userId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;


$currentRole = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER LOGIN
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}


if ($currentRole === 'admin') {

    header(
        'Location: ' .
        BASE_URL .
        'admin/dashboard.php'
    );

    exit;
}


if ($currentRole === 'vendor') {

    header(
        'Location: ' .
        BASE_URL .
        'seller/dashboard.php'
    );

    exit;
}


if ($currentRole !== 'customer') {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('dashboardEscape')) {

    function dashboardEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('dashboardProfileImage')) {

    function dashboardProfileImage($image): string
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

        return BASE_URL .
            'uploads/' .
            ltrim($image, '/');
    }
}


/*
|--------------------------------------------------------------------------
| LOAD CUSTOMER
|--------------------------------------------------------------------------
*/

$user = null;

try {

    $stmt = $db->prepare("
        SELECT
            user_id,
            name,
            email,
            phone,
            profile_image,
            role,
            status,
            created_at
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userId
    ]);

    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

    $user = null;
}


if (!$user) {

    session_unset();
    session_destroy();

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| NAVIGATION VARIABLES
|--------------------------------------------------------------------------
| Same idea used by the other customer pages.
|--------------------------------------------------------------------------
*/

$isLoggedIn = true;

$userName = (string) (
    $user['name']
    ?? 'Customer'
);

$userRole = 'customer';

$currentPage = 'dashboard.php';


/*
|--------------------------------------------------------------------------
| CUSTOMER NAME
|--------------------------------------------------------------------------
*/

$displayName = trim(
    (string) (
        $user['name']
        ?? 'Customer'
    )
);


if ($displayName === '') {
    $displayName = 'Customer';
}


$nameParts = preg_split(
    '/\s+/',
    $displayName
);


$firstName = !empty($nameParts[0])
    ? $nameParts[0]
    : 'Customer';


/*
|--------------------------------------------------------------------------
| PROFILE IMAGE
|--------------------------------------------------------------------------
*/

$profileImage = dashboardProfileImage(
    $user['profile_image']
    ?? ''
);


/*
|--------------------------------------------------------------------------
| MEMBER SINCE
|--------------------------------------------------------------------------
*/

$memberSince = '-';

if (!empty($user['created_at'])) {

    $timestamp = strtotime(
        (string) $user['created_at']
    );

    if ($timestamp !== false) {

        $memberSince = date(
            'M Y',
            $timestamp
        );
    }
}


/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$orderCount = 0;

$pendingCount = 0;

$processingCount = 0;

$completedCount = 0;

$cancelledCount = 0;

$wishlistCount = 0;

$cartCount = 0;

$unreadMessages = 0;


/*
|--------------------------------------------------------------------------
| ORDER COUNTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT
            COUNT(*) AS total_orders,

            SUM(
                CASE
                    WHEN LOWER(order_status) = 'pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending_orders,

            SUM(
                CASE
                    WHEN LOWER(order_status) = 'processing'
                    THEN 1
                    ELSE 0
                END
            ) AS processing_orders,

            SUM(
                CASE
                    WHEN LOWER(order_status) = 'completed'
                    THEN 1
                    ELSE 0
                END
            ) AS completed_orders,

            SUM(
                CASE
                    WHEN LOWER(order_status) = 'cancelled'
                    THEN 1
                    ELSE 0
                END
            ) AS cancelled_orders

        FROM orders

        WHERE customer_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $orderStats = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if ($orderStats) {

        $orderCount = (int) (
            $orderStats['total_orders']
            ?? 0
        );

        $pendingCount = (int) (
            $orderStats['pending_orders']
            ?? 0
        );

        $processingCount = (int) (
            $orderStats['processing_orders']
            ?? 0
        );

        $completedCount = (int) (
            $orderStats['completed_orders']
            ?? 0
        );

        $cancelledCount = (int) (
            $orderStats['cancelled_orders']
            ?? 0
        );
    }

} catch (Throwable $e) {

    $orderCount = 0;
    $pendingCount = 0;
    $processingCount = 0;
    $completedCount = 0;
    $cancelledCount = 0;
}


/*
|--------------------------------------------------------------------------
| WISHLIST COUNT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $wishlistCount = (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
| IMPORTANT:
| cart uses customer_id in the current database.
|--------------------------------------------------------------------------
*/

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

    $cartCount = (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $cartCount = 0;
}


/*
|--------------------------------------------------------------------------
| UNREAD MESSAGES
|--------------------------------------------------------------------------
| If messaging tables are unavailable for any reason,
| dashboard still works.
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)

        FROM messages m

        INNER JOIN conversations c
            ON m.conversation_id = c.conversation_id

        WHERE c.customer_id = ?

        AND m.sender_id <> ?

        AND m.is_read = 0
    ");

    $stmt->execute([
        $userId,
        $userId
    ]);

    $unreadMessages = (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $unreadMessages = 0;
}


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
| SAME CSS + HEADER STRUCTURE AS catalog.php
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Dashboard - ' .
    SITE_NAME;


$hideSiteMainWrapper = true;


$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

/* ================================================================
   CUSTOMER DASHBOARD
================================================================ */

.hh-dashboard-page {
    width: 100%;
    min-height: 100vh;

    padding:
        42px
        24px
        75px;

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


.hh-dashboard-container {
    width: 100%;
    max-width: 1340px;
    margin: 0 auto;
}


/* ================================================================
   HERO
================================================================ */

.hh-dashboard-hero {
    position: relative;

    min-height: 335px;

    margin-bottom: 22px;

    padding:
        48px
        52px;

    overflow: hidden;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        360px;

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
        0
        20px
        50px
        rgba(23,79,165,.16);
}


.hh-dashboard-hero::before {
    content: "";

    position: absolute;

    width: 310px;
    height: 310px;

    right: -70px;
    top: -165px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}


.hh-dashboard-hero::after {
    content: "";

    position: absolute;

    width: 230px;
    height: 230px;

    right: 230px;
    bottom: -180px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.05);
}


.hh-dashboard-hero-copy {
    position: relative;
    z-index: 2;
}


.hh-dashboard-pill {
    min-height: 34px;

    padding:
        0
        13px;

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

    letter-spacing: .3px;
}


.hh-dashboard-hero h1 {
    max-width: 730px;

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


.hh-dashboard-hero h1 span {
    color: #6fe7f3;
}


.hh-dashboard-hero p {
    max-width: 620px;

    margin:
        16px
        0
        0;

    color:
        rgba(255,255,255,.76);

    font-size: 12px;

    line-height: 1.75;
}


/* ================================================================
   HERO BUTTONS
================================================================ */

.hh-dashboard-hero-actions {
    margin-top: 24px;

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 9px;
}


.hh-dashboard-primary-btn,
.hh-dashboard-secondary-btn {
    min-height: 46px;

    padding:
        0
        17px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    border-radius: 11px;

    font-size: 9px;

    font-weight: 900;

    text-decoration: none;

    transition: .2s ease;
}


.hh-dashboard-primary-btn {
    color: #1e56a8;
    background: #ffffff;

    box-shadow:
        0
        8px
        20px
        rgba(0,0,0,.08);
}


.hh-dashboard-primary-btn:hover {
    color: #174998;

    transform:
        translateY(-2px);
}


.hh-dashboard-secondary-btn {
    color: #ffffff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.22);
}


.hh-dashboard-secondary-btn:hover {
    color: #ffffff;

    background:
        rgba(255,255,255,.17);

    transform:
        translateY(-2px);
}


/* ================================================================
   HERO ART
================================================================ */

.hh-dashboard-art {
    position: relative;
    z-index: 2;

    height: 220px;
}


.hh-dashboard-art-main {
    position: absolute;

    width: 155px;
    height: 155px;

    top: 34px;
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

    font-size: 62px;

    transform:
        rotate(-4deg);
}


.hh-dashboard-floating {
    position: absolute;

    min-width: 145px;

    padding:
        11px
        13px;

    display: flex;

    align-items: center;

    gap: 9px;

    color: #23405f;

    background: #ffffff;

    border-radius: 12px;

    box-shadow:
        0
        13px
        30px
        rgba(0,30,80,.18);
}


.hh-dashboard-floating.one {
    top: 3px;
    left: 0;
}


.hh-dashboard-floating.two {
    right: 0;
    bottom: 4px;
}


.hh-dashboard-floating-icon {
    width: 32px;
    height: 32px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 9px;

    font-size: 13px;
}


.hh-dashboard-floating span {
    display: block;

    color: #8a98aa;

    font-size: 6px;

    font-weight: 850;
}


.hh-dashboard-floating strong {
    display: block;

    margin-top: 1px;

    color: #17233c;

    font-size: 14px;

    font-weight: 900;
}


/* ================================================================
   STATS
================================================================ */

.hh-dashboard-stats {
    margin-bottom: 22px;

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;
}


.hh-dashboard-stat {
    min-height: 98px;

    padding:
        17px
        18px;

    display: flex;

    align-items: center;

    gap: 13px;

    color: inherit;

    background: #ffffff;

    border:
        1px solid
        #e2e9f4;

    border-radius: 16px;

    text-decoration: none;

    box-shadow:
        0
        7px
        22px
        rgba(40,65,120,.04);

    transition: .2s ease;
}


.hh-dashboard-stat:hover {
    color: inherit;

    transform:
        translateY(-3px);

    border-color: #cbdcf4;

    box-shadow:
        0
        13px
        30px
        rgba(40,65,120,.09);
}


.hh-dashboard-stat-icon {
    width: 47px;
    height: 47px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 13px;

    font-size: 18px;
}


.hh-dashboard-stat-icon.pink {
    color: #e11d48;
    background: #fff1f2;
}


.hh-dashboard-stat-icon.green {
    color: #15803d;
    background: #ecfdf3;
}


.hh-dashboard-stat-icon.purple {
    color: #7c3aed;
    background: #f5f3ff;
}


.hh-dashboard-stat-copy span {
    display: block;

    color: #8a98aa;

    font-size: 7px;

    font-weight: 850;

    letter-spacing: .2px;
}


.hh-dashboard-stat-copy strong {
    display: block;

    margin-top: 3px;

    color: #17233c;

    font-size: 21px;

    font-weight: 900;
}


.hh-dashboard-stat-copy small {
    display: block;

    margin-top: 2px;

    color: #9aa7b7;

    font-size: 6px;
}


/* ================================================================
   MAIN GRID
================================================================ */

.hh-dashboard-layout {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        320px;

    gap: 20px;

    align-items: start;
}


/* ================================================================
   CARD
================================================================ */

.hh-dashboard-card {
    padding: 22px;

    background: #ffffff;

    border:
        1px solid
        #e2e9f3;

    border-radius: 18px;

    box-shadow:
        0
        7px
        22px
        rgba(40,65,120,.04);
}


.hh-dashboard-card + .hh-dashboard-card {
    margin-top: 18px;
}


.hh-dashboard-card-head {
    margin-bottom: 18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}


.hh-dashboard-card-title {
    display: flex;

    align-items: center;

    gap: 11px;
}


.hh-dashboard-card-title-icon {
    width: 42px;
    height: 42px;

    flex-shrink: 0;

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

    border-radius: 12px;

    font-size: 16px;
}


.hh-dashboard-card-title span {
    display: block;

    margin-bottom: 2px;

    color: #3773dc;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .7px;
}


.hh-dashboard-card-title h2 {
    margin: 0;

    color: #17233c;

    font-size: 15px;

    font-weight: 900;
}


/* ================================================================
   QUICK ACCESS
================================================================ */

.hh-dashboard-quick-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;
}


.hh-dashboard-quick {
    position: relative;

    min-height: 145px;

    padding: 17px;

    display: flex;

    flex-direction: column;

    justify-content: space-between;

    color: inherit;

    background: #fbfdff;

    border:
        1px solid
        #e3eaf3;

    border-radius: 15px;

    text-decoration: none;

    transition: .2s ease;
}


.hh-dashboard-quick:hover {
    color: inherit;

    transform:
        translateY(-3px);

    border-color: #c9daf5;

    box-shadow:
        0
        12px
        25px
        rgba(40,65,120,.07);
}


.hh-dashboard-quick-arrow {
    position: absolute;

    top: 15px;
    right: 15px;

    color: #a5b1c0;

    font-size: 10px;
}


.hh-dashboard-quick-icon {
    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 12px;

    font-size: 17px;
}


.hh-dashboard-quick-icon.pink {
    color: #e11d48;
    background: #fff1f2;
}


.hh-dashboard-quick-icon.green {
    color: #15803d;
    background: #ecfdf3;
}


.hh-dashboard-quick-icon.orange {
    color: #ea580c;
    background: #fff7ed;
}


.hh-dashboard-quick-icon.purple {
    color: #7c3aed;
    background: #f5f3ff;
}


.hh-dashboard-quick-icon.cyan {
    color: #0891b2;
    background: #ecfeff;
}


.hh-dashboard-quick strong {
    display: block;

    margin-bottom: 4px;

    color: #17233c;

    font-size: 10px;

    font-weight: 900;
}


.hh-dashboard-quick span {
    color: #8997aa;

    font-size: 7px;

    line-height: 1.5;
}


/* ================================================================
   ORDER OVERVIEW
================================================================ */

.hh-dashboard-order-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 10px;
}


.hh-dashboard-order-status {
    min-height: 95px;

    padding: 14px;

    background: #f8fbff;

    border:
        1px solid
        #e3eaf3;

    border-radius: 13px;
}


.hh-dashboard-order-status-top {
    margin-bottom: 10px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;
}


.hh-dashboard-order-status i {
    font-size: 16px;
}


.hh-dashboard-order-status.pending i {
    color: #d97706;
}


.hh-dashboard-order-status.processing i {
    color: #2563eb;
}


.hh-dashboard-order-status.completed i {
    color: #16a34a;
}


.hh-dashboard-order-status.cancelled i {
    color: #dc2626;
}


.hh-dashboard-order-status strong {
    color: #17233c;

    font-size: 18px;

    font-weight: 900;
}


.hh-dashboard-order-status span {
    display: block;

    color: #8795a8;

    font-size: 7px;

    font-weight: 800;
}


/* ================================================================
   PROFILE
================================================================ */

.hh-dashboard-profile {
    position: relative;

    overflow: hidden;

    padding: 22px;

    color: #ffffff;

    background:
        linear-gradient(
            145deg,
            #0b2b69,
            #1956ad
        );

    border-radius: 18px;

    box-shadow:
        0
        16px
        35px
        rgba(20,65,145,.14);
}


.hh-dashboard-profile::before {
    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    top: -100px;
    right: -90px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.06);
}


.hh-dashboard-profile-label {
    position: relative;
    z-index: 2;

    display: block;

    margin-bottom: 3px;

    color:
        rgba(255,255,255,.55);

    font-size: 7px;

    font-weight: 850;

    letter-spacing: .5px;
}


.hh-dashboard-profile h2 {
    position: relative;
    z-index: 2;

    margin:
        0
        0
        18px;

    color: #ffffff;

    font-size: 15px;

    font-weight: 900;
}


.hh-dashboard-user {
    position: relative;
    z-index: 2;

    margin-bottom: 17px;

    display: flex;

    align-items: center;

    gap: 11px;
}


.hh-dashboard-avatar {
    width: 57px;
    height: 57px;

    flex-shrink: 0;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #1e56a8;

    background: #ffffff;

    border-radius: 15px;

    font-size: 21px;
}


.hh-dashboard-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.hh-dashboard-user strong {
    display: block;

    color: #ffffff;

    font-size: 11px;

    font-weight: 900;
}


.hh-dashboard-user span {
    display: block;

    margin-top: 3px;

    color:
        rgba(255,255,255,.6);

    font-size: 7px;
}


.hh-dashboard-profile-info {
    position: relative;
    z-index: 2;

    display: flex;

    flex-direction: column;

    gap: 8px;
}


.hh-dashboard-profile-row {
    min-height: 51px;

    padding:
        9px
        10px;

    display: flex;

    align-items: center;

    gap: 9px;

    background:
        rgba(255,255,255,.08);

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius: 10px;
}


.hh-dashboard-profile-row > i {
    width: 25px;

    color: #79dff0;

    text-align: center;

    font-size: 13px;
}


.hh-dashboard-profile-row span {
    display: block;

    margin-bottom: 2px;

    color:
        rgba(255,255,255,.5);

    font-size: 6px;

    font-weight: 800;
}


.hh-dashboard-profile-row strong {
    display: block;

    color: #ffffff;

    font-size: 8px;

    font-weight: 800;

    word-break: break-word;
}


.hh-dashboard-profile-btn {
    position: relative;
    z-index: 2;

    width: 100%;
    min-height: 41px;

    margin-top: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #174998;

    background: #ffffff;

    border-radius: 10px;

    font-size: 8px;

    font-weight: 900;

    text-decoration: none;

    transition: .2s ease;
}


.hh-dashboard-profile-btn:hover {
    color: #174998;

    transform:
        translateY(-2px);
}


/* ================================================================
   SIDE INFO
================================================================ */

.hh-dashboard-side-card {
    margin-top: 15px;

    padding: 18px;

    background: #ffffff;

    border:
        1px solid
        #e2e9f3;

    border-radius: 17px;
}


.hh-dashboard-side-card h3 {
    margin:
        0
        0
        12px;

    color: #17233c;

    font-size: 11px;

    font-weight: 900;
}


.hh-dashboard-side-link {
    min-height: 47px;

    padding:
        8px
        9px;

    display: flex;

    align-items: center;

    gap: 9px;

    color: #53657c;

    border-radius: 10px;

    text-decoration: none;

    transition: .2s ease;
}


.hh-dashboard-side-link:hover {
    color: #2563eb;

    background: #f3f7ff;
}


.hh-dashboard-side-link i {
    width: 32px;
    height: 32px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 9px;
}


.hh-dashboard-side-link strong {
    display: block;

    margin-bottom: 2px;

    color: #27364d;

    font-size: 8px;

    font-weight: 900;
}


.hh-dashboard-side-link span {
    display: block;

    color: #91a0b2;

    font-size: 6px;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1100px) {

    .hh-dashboard-stats {
        grid-template-columns:
            repeat(2, 1fr);
    }


    .hh-dashboard-quick-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }


    .hh-dashboard-order-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 900px) {

    .hh-dashboard-hero {
        grid-template-columns: 1fr;
    }


    .hh-dashboard-art {
        display: none;
    }


    .hh-dashboard-layout {
        grid-template-columns: 1fr;
    }
}


@media (max-width: 650px) {

    .hh-dashboard-page {
        padding:
            22px
            13px
            50px;
    }


    .hh-dashboard-hero {
        padding:
            28px
            23px;
    }


    .hh-dashboard-hero h1 {
        font-size: 31px;
    }


    .hh-dashboard-hero-actions {
        display: grid;

        grid-template-columns: 1fr;
    }


    .hh-dashboard-primary-btn,
    .hh-dashboard-secondary-btn {
        width: 100%;
    }


    .hh-dashboard-stats {
        grid-template-columns: 1fr;
    }


    .hh-dashboard-quick-grid {
        grid-template-columns: 1fr;
    }


    .hh-dashboard-order-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 420px) {

    .hh-dashboard-order-grid {
        grid-template-columns: 1fr;
    }
}

</style>


<!-- ===============================================================
     CUSTOMER DASHBOARD
================================================================ -->

<main class="hh-dashboard-page">

    <div class="hh-dashboard-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-dashboard-hero">


            <div class="hh-dashboard-hero-copy">


                <span class="hh-dashboard-pill">

                    <i class="bi bi-stars"></i>

                    HOCHIPOHUB CUSTOMER CENTER

                </span>


                <h1>

                    Welcome Back,

                    <span>

                        <?= dashboardEscape(
                            $firstName
                        ) ?>.

                    </span>

                </h1>


                <p>

                    Everything you need for your
                    HochipoHub shopping experience
                    is right here. Discover products,
                    manage your cart, chat with sellers
                    and keep track of your orders.

                </p>


                <div class="hh-dashboard-hero-actions">


                    <a
                        href="<?= BASE_URL ?>catalog.php"
                        class="hh-dashboard-primary-btn"
                    >

                        <i class="bi bi-bag"></i>

                        Start Shopping

                        <i class="bi bi-arrow-right"></i>

                    </a>


                    <a
                        href="<?= BASE_URL ?>order.php"
                        class="hh-dashboard-secondary-btn"
                    >

                        <i class="bi bi-box-seam"></i>

                        My Orders

                    </a>


                </div>


            </div>



            <div class="hh-dashboard-art">


                <div class="hh-dashboard-art-main">

                    <i class="bi bi-bag-heart"></i>

                </div>



                <div
                    class="
                        hh-dashboard-floating
                        one
                    "
                >

                    <div class="hh-dashboard-floating-icon">

                        <i class="bi bi-box-seam"></i>

                    </div>


                    <div>

                        <span>
                            TOTAL ORDERS
                        </span>

                        <strong>
                            <?= $orderCount ?>
                        </strong>

                    </div>

                </div>



                <div
                    class="
                        hh-dashboard-floating
                        two
                    "
                >

                    <div class="hh-dashboard-floating-icon">

                        <i class="bi bi-cart3"></i>

                    </div>


                    <div>

                        <span>
                            CART ITEMS
                        </span>

                        <strong>
                            <?= $cartCount ?>
                        </strong>

                    </div>

                </div>


            </div>


        </section>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="hh-dashboard-stats">


            <a
                href="<?= BASE_URL ?>order.php"
                class="hh-dashboard-stat"
            >

                <div class="hh-dashboard-stat-icon">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="hh-dashboard-stat-copy">

                    <span>
                        MY ORDERS
                    </span>

                    <strong>
                        <?= $orderCount ?>
                    </strong>

                    <small>
                        View all purchases
                    </small>

                </div>

            </a>



            <a
                href="<?= BASE_URL ?>cart.php"
                class="hh-dashboard-stat"
            >

                <div
                    class="
                        hh-dashboard-stat-icon
                        green
                    "
                >

                    <i class="bi bi-cart3"></i>

                </div>


                <div class="hh-dashboard-stat-copy">

                    <span>
                        SHOPPING CART
                    </span>

                    <strong>
                        <?= $cartCount ?>
                    </strong>

                    <small>
                        Items waiting for checkout
                    </small>

                </div>

            </a>



            <a
                href="<?= BASE_URL ?>wishlist.php"
                class="hh-dashboard-stat"
            >

                <div
                    class="
                        hh-dashboard-stat-icon
                        pink
                    "
                >

                    <i class="bi bi-heart"></i>

                </div>


                <div class="hh-dashboard-stat-copy">

                    <span>
                        WISHLIST
                    </span>

                    <strong>
                        <?= $wishlistCount ?>
                    </strong>

                    <small>
                        Saved products
                    </small>

                </div>

            </a>



            <a
                href="<?= BASE_URL ?>messages.php"
                class="hh-dashboard-stat"
            >

                <div
                    class="
                        hh-dashboard-stat-icon
                        purple
                    "
                >

                    <i class="bi bi-chat-dots"></i>

                </div>


                <div class="hh-dashboard-stat-copy">

                    <span>
                        MESSAGES
                    </span>

                    <strong>
                        <?= $unreadMessages ?>
                    </strong>

                    <small>
                        Unread conversations
                    </small>

                </div>

            </a>


        </section>



        <!-- =======================================================
             MAIN CONTENT
        ======================================================== -->

        <section class="hh-dashboard-layout">


            <!-- ===================================================
                 LEFT
            ==================================================== -->

            <div>


                <!-- =================================================
                     QUICK ACCESS
                ================================================== -->

                <section class="hh-dashboard-card">


                    <div class="hh-dashboard-card-head">


                        <div class="hh-dashboard-card-title">


                            <div class="hh-dashboard-card-title-icon">

                                <i class="bi bi-grid"></i>

                            </div>


                            <div>

                                <span>
                                    QUICK ACCESS
                                </span>

                                <h2>
                                    What would you like to do?
                                </h2>

                            </div>


                        </div>


                    </div>



                    <div class="hh-dashboard-quick-grid">


                        <!-- PRODUCTS -->

                        <a
                            href="<?= BASE_URL ?>catalog.php"
                            class="hh-dashboard-quick"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-right
                                    hh-dashboard-quick-arrow
                                "
                            ></i>


                            <div class="hh-dashboard-quick-icon">

                                <i class="bi bi-bag"></i>

                            </div>


                            <div>

                                <strong>
                                    Browse Products
                                </strong>

                                <span>
                                    Discover products from
                                    approved HochipoHub sellers.
                                </span>

                            </div>

                        </a>



                        <!-- CART -->

                        <a
                            href="<?= BASE_URL ?>cart.php"
                            class="hh-dashboard-quick"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-right
                                    hh-dashboard-quick-arrow
                                "
                            ></i>


                            <div
                                class="
                                    hh-dashboard-quick-icon
                                    green
                                "
                            >

                                <i class="bi bi-cart3"></i>

                            </div>


                            <div>

                                <strong>
                                    Shopping Cart
                                </strong>

                                <span>

                                    <?= $cartCount ?>

                                    item<?= $cartCount !== 1
                                        ? 's'
                                        : '' ?>

                                    currently in your cart.

                                </span>

                            </div>

                        </a>



                        <!-- WISHLIST -->

                        <a
                            href="<?= BASE_URL ?>wishlist.php"
                            class="hh-dashboard-quick"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-right
                                    hh-dashboard-quick-arrow
                                "
                            ></i>


                            <div
                                class="
                                    hh-dashboard-quick-icon
                                    pink
                                "
                            >

                                <i class="bi bi-heart"></i>

                            </div>


                            <div>

                                <strong>
                                    Wishlist
                                </strong>

                                <span>

                                    <?= $wishlistCount ?>

                                    saved
                                    product<?= $wishlistCount !== 1
                                        ? 's'
                                        : '' ?>.

                                </span>

                            </div>

                        </a>



                        <!-- ORDERS -->

                        <a
                            href="<?= BASE_URL ?>order.php"
                            class="hh-dashboard-quick"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-right
                                    hh-dashboard-quick-arrow
                                "
                            ></i>


                            <div
                                class="
                                    hh-dashboard-quick-icon
                                    orange
                                "
                            >

                                <i class="bi bi-box-seam"></i>

                            </div>


                            <div>

                                <strong>
                                    My Orders
                                </strong>

                                <span>
                                    Track current orders
                                    and review purchase history.
                                </span>

                            </div>

                        </a>



                        <!-- MESSAGES -->

                        <a
                            href="<?= BASE_URL ?>messages.php"
                            class="hh-dashboard-quick"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-right
                                    hh-dashboard-quick-arrow
                                "
                            ></i>


                            <div
                                class="
                                    hh-dashboard-quick-icon
                                    purple
                                "
                            >

                                <i class="bi bi-chat-dots"></i>

                            </div>


                            <div>

                                <strong>
                                    Messages
                                </strong>

                                <span>

                                    <?php if ($unreadMessages > 0): ?>

                                        You have
                                        <?= $unreadMessages ?>
                                        unread message<?= $unreadMessages !== 1
                                            ? 's'
                                            : '' ?>.

                                    <?php else: ?>

                                        Chat directly with
                                        HochipoHub sellers.

                                    <?php endif; ?>

                                </span>

                            </div>

                        </a>



                        <!-- PROFILE -->

                        <a
                            href="<?= BASE_URL ?>profile.php"
                            class="hh-dashboard-quick"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-right
                                    hh-dashboard-quick-arrow
                                "
                            ></i>


                            <div
                                class="
                                    hh-dashboard-quick-icon
                                    cyan
                                "
                            >

                                <i class="bi bi-person"></i>

                            </div>


                            <div>

                                <strong>
                                    My Profile
                                </strong>

                                <span>
                                    Manage your personal
                                    information and account.
                                </span>

                            </div>

                        </a>


                    </div>


                </section>



                <!-- =================================================
                     ORDER OVERVIEW
                ================================================== -->

                <section class="hh-dashboard-card">


                    <div class="hh-dashboard-card-head">


                        <div class="hh-dashboard-card-title">


                            <div class="hh-dashboard-card-title-icon">

                                <i class="bi bi-activity"></i>

                            </div>


                            <div>

                                <span>
                                    ORDER ACTIVITY
                                </span>

                                <h2>
                                    Order Overview
                                </h2>

                            </div>


                        </div>


                        <a
                            href="<?= BASE_URL ?>order.php"
                            style="
                                color:#2563eb;
                                font-size:8px;
                                font-weight:900;
                                text-decoration:none;
                            "
                        >

                            View All

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>



                    <div class="hh-dashboard-order-grid">


                        <div
                            class="
                                hh-dashboard-order-status
                                pending
                            "
                        >

                            <div class="hh-dashboard-order-status-top">

                                <i class="bi bi-clock"></i>

                                <strong>
                                    <?= $pendingCount ?>
                                </strong>

                            </div>

                            <span>
                                Pending
                            </span>

                        </div>



                        <div
                            class="
                                hh-dashboard-order-status
                                processing
                            "
                        >

                            <div class="hh-dashboard-order-status-top">

                                <i class="bi bi-arrow-repeat"></i>

                                <strong>
                                    <?= $processingCount ?>
                                </strong>

                            </div>

                            <span>
                                Processing
                            </span>

                        </div>



                        <div
                            class="
                                hh-dashboard-order-status
                                completed
                            "
                        >

                            <div class="hh-dashboard-order-status-top">

                                <i class="bi bi-check-circle"></i>

                                <strong>
                                    <?= $completedCount ?>
                                </strong>

                            </div>

                            <span>
                                Completed
                            </span>

                        </div>



                        <div
                            class="
                                hh-dashboard-order-status
                                cancelled
                            "
                        >

                            <div class="hh-dashboard-order-status-top">

                                <i class="bi bi-x-circle"></i>

                                <strong>
                                    <?= $cancelledCount ?>
                                </strong>

                            </div>

                            <span>
                                Cancelled
                            </span>

                        </div>


                    </div>


                </section>


            </div>



            <!-- ===================================================
                 RIGHT
            ==================================================== -->

            <aside>


                <!-- =================================================
                     PROFILE CARD
                ================================================== -->

                <section class="hh-dashboard-profile">


                    <span class="hh-dashboard-profile-label">

                        MY ACCOUNT

                    </span>


                    <h2>
                        Customer Profile
                    </h2>



                    <div class="hh-dashboard-user">


                        <div class="hh-dashboard-avatar">


                            <?php if ($profileImage !== ''): ?>


                                <img
                                    src="<?= dashboardEscape(
                                        $profileImage
                                    ) ?>"
                                    alt="<?= dashboardEscape(
                                        $displayName
                                    ) ?>"
                                >


                            <?php else: ?>


                                <i class="bi bi-person"></i>


                            <?php endif; ?>


                        </div>



                        <div>

                            <strong>

                                <?= dashboardEscape(
                                    $displayName
                                ) ?>

                            </strong>

                            <span>
                                HochipoHub Customer
                            </span>

                        </div>


                    </div>



                    <div class="hh-dashboard-profile-info">


                        <div class="hh-dashboard-profile-row">

                            <i class="bi bi-envelope"></i>

                            <div>

                                <span>
                                    EMAIL
                                </span>

                                <strong>

                                    <?= dashboardEscape(
                                        $user['email']
                                        ?? '-'
                                    ) ?>

                                </strong>

                            </div>

                        </div>



                        <div class="hh-dashboard-profile-row">

                            <i class="bi bi-telephone"></i>

                            <div>

                                <span>
                                    PHONE
                                </span>

                                <strong>

                                    <?= dashboardEscape(
                                        !empty($user['phone'])
                                            ? $user['phone']
                                            : 'Not provided'
                                    ) ?>

                                </strong>

                            </div>

                        </div>



                        <div class="hh-dashboard-profile-row">

                            <i class="bi bi-calendar3"></i>

                            <div>

                                <span>
                                    MEMBER SINCE
                                </span>

                                <strong>

                                    <?= dashboardEscape(
                                        $memberSince
                                    ) ?>

                                </strong>

                            </div>

                        </div>



                        <div class="hh-dashboard-profile-row">

                            <i class="bi bi-shield-check"></i>

                            <div>

                                <span>
                                    ACCOUNT STATUS
                                </span>

                                <strong>

                                    <?= dashboardEscape(
                                        ucfirst(
                                            (string) (
                                                $user['status']
                                                ?? 'active'
                                            )
                                        )
                                    ) ?>

                                </strong>

                            </div>

                        </div>


                    </div>



                    <a
                        href="<?= BASE_URL ?>profile.php"
                        class="hh-dashboard-profile-btn"
                    >

                        <i class="bi bi-pencil-square"></i>

                        Edit My Profile

                    </a>


                </section>



                <!-- =================================================
                     SHOPPING SHORTCUTS
                ================================================== -->

                <section class="hh-dashboard-side-card">


                    <h3>
                        Shopping Shortcuts
                    </h3>



                    <a
                        href="<?= BASE_URL ?>catalog.php"
                        class="hh-dashboard-side-link"
                    >

                        <i class="bi bi-search"></i>

                        <div>

                            <strong>
                                Discover Products
                            </strong>

                            <span>
                                Explore HochipoHub
                            </span>

                        </div>

                    </a>



                    <a
                        href="<?= BASE_URL ?>wishlist.php"
                        class="hh-dashboard-side-link"
                    >

                        <i class="bi bi-heart"></i>

                        <div>

                            <strong>
                                Saved Items
                            </strong>

                            <span>

                                <?= $wishlistCount ?>

                                product<?= $wishlistCount !== 1
                                    ? 's'
                                    : '' ?>

                            </span>

                        </div>

                    </a>



                    <a
                        href="<?= BASE_URL ?>messages.php"
                        class="hh-dashboard-side-link"
                    >

                        <i class="bi bi-chat-dots"></i>

                        <div>

                            <strong>
                                Seller Messages
                            </strong>

                            <span>
                                Contact your sellers
                            </span>

                        </div>

                    </a>


                </section>


            </aside>


        </section>


    </div>

</main>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>