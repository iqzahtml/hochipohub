<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - NEARBY STORES
|--------------------------------------------------------------------------
| File: nearby_stores.php
|
| Features:
| - Original HochipoHub navbar
| - Catalog-style interface
| - Browser current location
| - Manual location search
| - Location accuracy warning
| - 5 KM / 10 KM / 20 KM / ALL filters
| - AJAX nearby store loading
| - Nearest store first
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
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
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('nearbyEscape')) {

    function nearbyEscape($value): string
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
| USER
|--------------------------------------------------------------------------
*/

$isLoggedIn =
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] > 0;


$userId =
    $isLoggedIn
        ? (int) $_SESSION['user_id']
        : 0;


$currentUser = null;


if ($userId > 0) {

    try {

        $userStmt = $db->prepare("
            SELECT
                user_id,
                name,
                email,
                role,
                profile_image
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

    } catch (Throwable $e) {

        $currentUser = null;
    }
}


/*
|--------------------------------------------------------------------------
| NAVBAR DATA
|--------------------------------------------------------------------------
*/

$userName = trim(
    (string) (
        $currentUser['name']
        ?? ''
    )
);


$userRole = strtolower(
    trim(
        (string) (
            $currentUser['role']
            ?? 'customer'
        )
    )
);


if ($userRole === '') {
    $userRole = 'customer';
}


$cartCount = 0;
$wishlistCount = 0;


/*
|--------------------------------------------------------------------------
| CUSTOMER COUNTS
|--------------------------------------------------------------------------
*/

if (
    $isLoggedIn &&
    $userRole === 'customer'
) {

    try {

        $cartStmt = $db->prepare("
            SELECT
                COALESCE(SUM(quantity), 0)
            FROM cart
            WHERE user_id = ?
        ");

        $cartStmt->execute([
            $userId
        ]);

        $cartCount =
            (int) $cartStmt->fetchColumn();

    } catch (Throwable $e) {

        $cartCount = 0;
    }


    try {

        $wishlistStmt = $db->prepare("
            SELECT COUNT(*)
            FROM wishlist
            WHERE user_id = ?
        ");

        $wishlistStmt->execute([
            $userId
        ]);

        $wishlistCount =
            (int) $wishlistStmt->fetchColumn();

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
    'Nearby Stores | HochipoHub';


$currentPage =
    'nearby_stores.php';

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
    <?= nearbyEscape($pageTitle) ?>
</title>


<!-- =========================================================
     FONTS
========================================================== -->

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
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@500;600;700;800&display=swap"
    rel="stylesheet"
>


<!-- =========================================================
     FONT AWESOME
========================================================== -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>


<!-- =========================================================
     PROJECT CSS
========================================================== -->

<link
    rel="stylesheet"
    href="<?= nearbyEscape(BASE_URL) ?>css/style.css"
>

<link
    rel="stylesheet"
    href="<?= nearbyEscape(BASE_URL) ?>css/responsive.css"
>


<style>

/* =========================================================
   PAGE RESET
========================================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body.nearby-page {
    margin: 0;

    min-height: 100vh;

    color: #17233c;

    background:
        linear-gradient(
            180deg,
            #f0f5ff 0%,
            #f7f9fd 52%,
            #f8fafc 100%
        );

    font-family:
        Inter,
        Arial,
        sans-serif;
}

button,
input {
    font-family: inherit;
}


/* =========================================================
   MAIN
========================================================= */

.nearby-main {
    min-height: calc(100vh - 80px);

    padding:
        42px
        0
        80px;
}

.nearby-container {
    width: calc(100% - 48px);

    max-width: 1390px;

    margin: 0 auto;
}


/* =========================================================
   HERO
========================================================= */

.nearby-hero {
    position: relative;

    min-height: 390px;

    overflow: hidden;

    padding:
        52px
        54px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 45px;

    color: #ffffff;

    background:
        linear-gradient(
            115deg,
            #123777 0%,
            #194d9d 42%,
            #2676dc 75%,
            #328cf1 100%
        );

    border-radius: 29px;

    box-shadow:
        0 24px 60px
        rgba(22, 73, 154, .15);
}


/* decorative circles */

.nearby-hero::before {
    content: "";

    position: absolute;

    width: 360px;
    height: 360px;

    right: -95px;
    top: -190px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .07
        );
}

.nearby-hero::after {
    content: "";

    position: absolute;

    width: 230px;
    height: 230px;

    right: 230px;
    bottom: -165px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .045
        );
}


/* =========================================================
   HERO LEFT
========================================================= */

.nearby-hero-left {
    position: relative;

    z-index: 3;

    width: 100%;
    max-width: 770px;
}

.nearby-hero-badge {
    width: fit-content;

    margin-bottom: 20px;

    padding:
        8px
        14px;

    display: inline-flex;
    align-items: center;

    gap: 7px;

    color: #ffffff;

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
            .24
        );

    border-radius: 999px;

    font-size: 8px;
    font-weight: 850;

    letter-spacing: .2px;
}

.nearby-hero-badge i {
    color: #dbeafe;
}

.nearby-hero h1 {
    max-width: 720px;

    margin:
        0
        0
        15px;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:
        clamp(
            37px,
            4.8vw,
            61px
        );

    font-weight: 800;

    line-height: 1.04;

    letter-spacing: -2.5px;
}

.nearby-highlight {
    color: #59dbe7;
}

.nearby-hero-description {
    max-width: 680px;

    margin:
        0
        0
        25px;

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size: 11px;

    line-height: 1.75;
}


/* =========================================================
   HERO LOCATION SEARCH
========================================================= */

.hero-location-search {
    max-width: 720px;

    display: flex;
    align-items: stretch;

    gap: 9px;
}

.hero-location-input-wrap {
    position: relative;

    min-width: 0;

    flex: 1;
}

.hero-location-input-wrap > i {
    position: absolute;

    left: 18px;
    top: 50%;

    transform:
        translateY(-50%);

    color: #2563eb;

    font-size: 13px;

    pointer-events: none;
}

.hero-location-input {
    width: 100%;
    height: 52px;

    padding:
        0
        17px
        0
        45px;

    color: #314764;

    background: #ffffff;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .8
        );

    border-radius: 13px;

    outline: none;

    font-size: 9px;
    font-weight: 600;

    box-shadow:
        0 9px 22px
        rgba(7, 32, 77, .09);
}

.hero-location-input::placeholder {
    color: #9ca8ba;
}

.hero-location-input:focus {
    border-color: #93c5fd;

    box-shadow:
        0 0 0 4px
        rgba(255, 255, 255, .10);
}

.hero-search-button {
    min-width: 112px;
    height: 52px;

    padding: 0 20px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    color: #153b76;
    background: #ffffff;

    border: none;
    border-radius: 13px;

    font-size: 9px;
    font-weight: 900;

    cursor: pointer;

    transition:
        transform .18s ease,
        box-shadow .18s ease;

    box-shadow:
        0 9px 22px
        rgba(7, 32, 77, .10);
}

.hero-search-button:hover {
    transform:
        translateY(-1px);

    box-shadow:
        0 12px 27px
        rgba(7, 32, 77, .16);
}

.hero-search-button:disabled {
    opacity: .65;

    cursor: wait;

    transform: none;
}


/* =========================================================
   HERO CURRENT LOCATION
========================================================= */

.hero-current-location {
    margin-top: 11px;

    display: inline-flex;
    align-items: center;

    gap: 7px;

    padding: 0;

    color:
        rgba(
            255,
            255,
            255,
            .88
        );

    background: transparent;

    border: none;

    font-size: 8px;
    font-weight: 750;

    cursor: pointer;
}

.hero-current-location:hover {
    color: #ffffff;
    text-decoration: underline;
}

.hero-current-location:disabled {
    opacity: .6;

    cursor: wait;
}


/* =========================================================
   HERO VISUAL
========================================================= */

.nearby-hero-visual {
    position: relative;

    z-index: 2;

    width: 330px;
    min-width: 330px;

    height: 260px;

    display: flex;
    align-items: center;
    justify-content: center;
}

.nearby-map-card {
    position: relative;

    width: 165px;
    height: 165px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            145deg,
            rgba(255, 255, 255, .14),
            rgba(255, 255, 255, .08)
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .20
        );

    border-radius: 36px;

    transform:
        rotate(-4deg);

    backdrop-filter:
        blur(12px);

    box-shadow:
        0 24px 50px
        rgba(8, 44, 104, .17);
}

.nearby-map-card i {
    font-size: 64px;

    transform:
        rotate(4deg);
}


/* floating info */

.nearby-floating {
    position: absolute;

    min-width: 142px;

    padding:
        11px
        14px;

    display: flex;
    align-items: center;

    gap: 7px;

    color: #173b73;
    background: #ffffff;

    border-radius: 12px;

    box-shadow:
        0 14px 35px
        rgba(5, 34, 82, .16);

    font-size: 8px;
    font-weight: 850;
}

.nearby-floating i {
    color: #2563eb;
}

.nearby-floating-one {
    top: 20px;
    left: -5px;
}

.nearby-floating-two {
    right: -4px;
    bottom: 21px;
}


/* =========================================================
   LOCATION STATUS PANEL
========================================================= */

.nearby-location-panel {
    margin-top: 22px;

    padding:
        20px
        22px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 18px;

    background:
        rgba(
            255,
            255,
            255,
            .94
        );

    border:
        1px solid #dce5f2;

    border-radius: 17px;

    box-shadow:
        0 9px 28px
        rgba(37, 65, 110, .055);
}

.location-panel-left {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 13px;
}

.location-panel-icon {
    width: 44px;
    height: 44px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: #2563eb;
    background: #eaf2ff;

    border-radius: 13px;

    font-size: 16px;
}

.location-panel-content {
    min-width: 0;
}

.location-panel-content small {
    display: block;

    margin-bottom: 4px;

    color: #8493a9;

    font-size: 7px;
    font-weight: 850;

    letter-spacing: .5px;
    text-transform: uppercase;
}

.location-panel-content strong {
    display: block;

    overflow: hidden;

    color: #1b365d;

    font-size: 10px;
    font-weight: 800;

    line-height: 1.5;

    text-overflow: ellipsis;
}

.location-status {
    max-width: 480px;

    padding:
        9px
        12px;

    display: flex;
    align-items: flex-start;

    gap: 7px;

    color: #52667f;
    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 10px;

    font-size: 8px;

    line-height: 1.5;
}

.location-status.success {
    color: #087443;
    background: #ecfdf3;

    border-color: #a7f3d0;
}

.location-status.error {
    color: #b42318;
    background: #fff2f1;

    border-color: #fecaca;
}

.location-status.loading {
    color: #1d4ed8;
    background: #eff6ff;

    border-color: #bfdbfe;
}

.location-status.warning {
    color: #92400e;
    background: #fffbeb;

    border-color: #fde68a;
}


/* =========================================================
   STATS
========================================================= */

.nearby-stats {
    margin-top: 22px;

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap: 16px;
}

.nearby-stat-card {
    min-height: 92px;

    padding:
        17px
        19px;

    display: flex;
    align-items: center;

    gap: 14px;

    background:
        rgba(
            255,
            255,
            255,
            .94
        );

    border:
        1px solid #dce5f2;

    border-radius: 17px;

    box-shadow:
        0 8px 25px
        rgba(37, 65, 110, .04);
}

.nearby-stat-icon {
    width: 43px;
    height: 43px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: #2563eb;
    background: #edf4ff;

    border-radius: 12px;

    font-size: 14px;
}

.nearby-stat-info small {
    display: block;

    margin-bottom: 5px;

    color: #8090a7;

    font-size: 7px;
    font-weight: 850;

    letter-spacing: .4px;

    text-transform: uppercase;
}

.nearby-stat-info strong {
    display: block;

    color: #132b50;

    font-size: 17px;
    font-weight: 900;
}


/* =========================================================
   STORE SECTION
========================================================= */

.nearby-store-section {
    margin-top: 22px;

    padding: 23px;

    background:
        rgba(
            255,
            255,
            255,
            .95
        );

    border:
        1px solid #dce5f2;

    border-radius: 19px;

    box-shadow:
        0 8px 28px
        rgba(37, 65, 110, .045);
}


/* =========================================================
   SECTION HEADER
========================================================= */

.nearby-section-header {
    margin-bottom: 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;
}

.nearby-section-title h2 {
    margin:
        0
        0
        5px;

    color: #132a4d;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 19px;
    font-weight: 800;
}

.nearby-section-title p {
    margin: 0;

    color: #8997aa;

    font-size: 8px;
}


/* =========================================================
   FILTER
========================================================= */

.nearby-filter-area {
    display: flex;
    align-items: center;

    gap: 7px;

    flex-wrap: wrap;
}

.nearby-filter {
    min-width: 63px;
    height: 35px;

    padding: 0 12px;

    color: #60728c;
    background: #f8fafc;

    border:
        1px solid #dfe7f1;

    border-radius: 9px;

    font-size: 8px;
    font-weight: 850;

    cursor: pointer;

    transition: .18s ease;
}

.nearby-filter:hover {
    color: #2563eb;

    border-color: #a9c7f5;
}

.nearby-filter.active {
    color: #ffffff;
    background: #2563eb;

    border-color: #2563eb;

    box-shadow:
        0 7px 16px
        rgba(37, 99, 235, .14);
}


/* =========================================================
   STORE GRID
========================================================= */

.nearby-store-grid {
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


/* =========================================================
   STORE CARD
========================================================= */

.nearby-store-card {
    min-width: 0;

    overflow: hidden;

    display: flex;
    flex-direction: column;

    background: #ffffff;

    border:
        1px solid #e0e7f1;

    border-radius: 17px;

    transition:
        transform .18s ease,
        border-color .18s ease,
        box-shadow .18s ease;
}

.nearby-store-card:hover {
    transform:
        translateY(-3px);

    border-color: #bfd3f2;

    box-shadow:
        0 16px 35px
        rgba(30, 67, 120, .09);
}

.nearby-store-card-top {
    padding: 18px;

    display: flex;
    align-items: flex-start;

    gap: 13px;
}

.nearby-store-logo {
    width: 63px;
    height: 63px;

    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: #2563eb;

    background:
        linear-gradient(
            135deg,
            #edf4ff,
            #f3f0ff
        );

    border:
        1px solid #dfe8f5;

    border-radius: 15px;

    font-size: 22px;
}

.nearby-store-logo img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}

.nearby-store-heading {
    min-width: 0;

    flex: 1;
}

.nearby-store-category {
    display: block;

    margin-bottom: 5px;

    color: #2563eb;

    font-size: 7px;
    font-weight: 900;

    letter-spacing: .5px;
    text-transform: uppercase;
}

.nearby-store-name {
    margin:
        0
        0
        7px;

    overflow: hidden;

    color: #17345c;

    font-size: 13px;
    font-weight: 850;

    line-height: 1.35;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.nearby-distance {
    display: inline-flex;
    align-items: center;

    gap: 5px;

    padding:
        5px
        8px;

    color: #087443;
    background: #ecfdf3;

    border-radius: 999px;

    font-size: 7px;
    font-weight: 850;
}


/* =========================================================
   STORE BODY
========================================================= */

.nearby-store-body {
    padding:
        0
        18px
        18px;

    display: flex;
    flex: 1;
    flex-direction: column;
}

.nearby-store-address {
    min-height: 39px;

    margin:
        0
        0
        11px;

    display: flex;
    align-items: flex-start;

    gap: 7px;

    color: #718198;

    font-size: 8px;

    line-height: 1.55;
}

.nearby-store-address i {
    margin-top: 2px;

    color: #ef4444;
}

.nearby-store-description {
    margin:
        0
        0
        14px;

    color: #8190a5;

    font-size: 8px;

    line-height: 1.6;

    display: -webkit-box;

    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    overflow: hidden;
}


/* =========================================================
   STORE META
========================================================= */

.nearby-store-meta {
    margin-top: auto;
    padding-top: 13px;

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 8px;

    border-top:
        1px solid #edf1f6;
}

.nearby-meta-box {
    padding: 9px;

    background: #f8fafc;

    border-radius: 9px;
}

.nearby-meta-box span {
    display: block;

    margin-bottom: 3px;

    color: #94a1b2;

    font-size: 6px;
    font-weight: 850;

    letter-spacing: .3px;
}

.nearby-meta-box strong {
    display: block;

    overflow: hidden;

    color: #29466d;

    font-size: 8px;
    font-weight: 850;

    text-overflow: ellipsis;
    white-space: nowrap;
}


/* =========================================================
   DELIVERY TAGS
========================================================= */

.nearby-delivery-tags {
    margin-top: 11px;

    display: flex;
    flex-wrap: wrap;

    gap: 5px;
}

.nearby-delivery-tag {
    padding:
        5px
        7px;

    color: #506681;
    background: #f1f5f9;

    border-radius: 7px;

    font-size: 6px;
    font-weight: 750;
}

.nearby-delivery-tag i {
    margin-right: 3px;

    color: #2563eb;
}


/* =========================================================
   STORE ACTION
========================================================= */

.nearby-store-action {
    padding:
        0
        18px
        18px;
}

.nearby-visit-store {
    width: 100%;
    min-height: 39px;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #347cf0
        );

    border-radius: 10px;

    font-size: 8px;
    font-weight: 850;

    text-decoration: none;

    transition: .18s ease;
}

.nearby-visit-store:hover {
    transform:
        translateY(-1px);

    box-shadow:
        0 8px 18px
        rgba(37, 99, 235, .17);
}


/* =========================================================
   STATE
========================================================= */

.nearby-state {
    grid-column: 1 / -1;

    min-height: 300px;

    padding:
        45px
        25px;

    display: flex;
    align-items: center;
    justify-content: center;

    text-align: center;

    background: #f9fbfe;

    border:
        1px dashed #d8e2ef;

    border-radius: 16px;
}

.nearby-state-inner {
    max-width: 470px;
}

.nearby-state-icon {
    width: 68px;
    height: 68px;

    margin:
        0
        auto
        16px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eaf2ff;

    border-radius: 19px;

    font-size: 24px;
}

.nearby-state-icon.error {
    color: #dc2626;
    background: #fef2f2;
}

.nearby-state-icon.empty {
    color: #64748b;
    background: #f1f5f9;
}

.nearby-state h3 {
    margin:
        0
        0
        7px;

    color: #17365f;

    font-size: 15px;
}

.nearby-state p {
    margin: 0;

    color: #8493a8;

    font-size: 8px;

    line-height: 1.7;
}


/* =========================================================
   LOADER
========================================================= */

.nearby-loader {
    width: 34px;
    height: 34px;

    margin:
        0
        auto
        17px;

    border:
        4px solid #e5edfa;

    border-top-color: #2563eb;

    border-radius: 50%;

    animation:
        nearbySpin .75s
        linear
        infinite;
}

@keyframes nearbySpin {

    to {
        transform:
            rotate(360deg);
    }
}


/* =========================================================
   PRIVACY NOTE
========================================================= */

.nearby-privacy {
    margin-top: 14px;

    display: flex;
    align-items: flex-start;

    gap: 7px;

    color: #8795a8;

    font-size: 7px;

    line-height: 1.55;
}

.nearby-privacy i {
    margin-top: 2px;

    color: #2563eb;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

    .nearby-hero {
        min-height: auto;
    }

    .nearby-hero-visual {
        width: 260px;
        min-width: 260px;
    }

    .nearby-store-grid {
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


@media (max-width: 850px) {

    .nearby-main {
        padding-top: 25px;
    }

    .nearby-hero {
        padding: 38px;

        flex-direction: column;
        align-items: flex-start;
    }

    .nearby-hero-left {
        max-width: none;
    }

    .nearby-hero-visual {
        width: 100%;
        height: 190px;
    }

    .nearby-location-panel {
        align-items: flex-start;

        flex-direction: column;
    }

    .location-status {
        max-width: none;

        width: 100%;
    }

    .nearby-section-header {
        align-items: flex-start;

        flex-direction: column;
    }
}


@media (max-width: 650px) {

    .nearby-container {
        width: calc(100% - 28px);
    }

    .nearby-main {
        padding:
            18px
            0
            55px;
    }

    .nearby-hero {
        padding:
            28px
            22px;

        border-radius: 21px;
    }

    .nearby-hero h1 {
        font-size: 35px;

        letter-spacing: -1.6px;
    }

    .nearby-hero-description {
        font-size: 9px;
    }

    .hero-location-search {
        flex-direction: column;
    }

    .hero-search-button {
        width: 100%;
    }

    .nearby-hero-visual {
        display: none;
    }

    .nearby-location-panel {
        padding: 17px;
    }

    .nearby-stats {
        grid-template-columns: 1fr;
    }

    .nearby-stat-card {
        min-height: 78px;
    }

    .nearby-store-section {
        padding: 17px;

        border-radius: 16px;
    }

    .nearby-filter-area {
        width: 100%;
    }

    .nearby-filter {
        flex: 1;
    }

    .nearby-store-grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body class="nearby-page">


<!-- =========================================================
     ORIGINAL NAVBAR
========================================================== -->

<?php
require_once __DIR__ . '/includes/navbar.php';
?>


<!-- =========================================================
     MAIN PAGE
========================================================== -->

<main class="nearby-main">

<div class="nearby-container">


<!-- =========================================================
     HERO
========================================================== -->

<section class="nearby-hero">


<!-- LEFT -->

<div class="nearby-hero-left">


    <div class="nearby-hero-badge">

        <i class="fa-solid fa-location-dot"></i>

        DISCOVER • NEARBY • SUPPORT LOCAL

    </div>


    <h1>

        Find Stores

        <span class="nearby-highlight">
            Near You.
        </span>

    </h1>


    <p class="nearby-hero-description">

        Discover approved HochipoHub sellers near your
        selected location. Search an area manually or
        use your current location and find stores
        arranged from nearest to furthest.

    </p>


    <!-- MANUAL SEARCH -->

    <div class="hero-location-search">


        <div class="hero-location-input-wrap">

            <i class="fa-solid fa-location-dot"></i>

            <input
                type="text"
                id="manualLocationInput"
                class="hero-location-input"
                placeholder="Search your location, area or address..."
                autocomplete="off"
            >

        </div>


        <button
            type="button"
            id="searchLocationButton"
            class="hero-search-button"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            Search

        </button>


    </div>


    <!-- CURRENT LOCATION -->

    <button
        type="button"
        id="useLocationButton"
        class="hero-current-location"
    >

        <i class="fa-solid fa-location-crosshairs"></i>

        Use My Current Location

    </button>


</div>


<!-- RIGHT -->

<div class="nearby-hero-visual">


    <div class="nearby-floating nearby-floating-one">

        <i class="fa-solid fa-store"></i>

        Local Sellers

    </div>


    <div class="nearby-map-card">

        <i class="fa-solid fa-map-location-dot"></i>

    </div>


    <div class="nearby-floating nearby-floating-two">

        <i class="fa-solid fa-location-arrow"></i>

        Nearest First

    </div>


</div>


</section>


<!-- =========================================================
     LOCATION STATUS
========================================================== -->

<section class="nearby-location-panel">


<div class="location-panel-left">


    <div class="location-panel-icon">

        <i class="fa-solid fa-location-dot"></i>

    </div>


    <div class="location-panel-content">

        <small>
            SELECTED LOCATION
        </small>

        <strong id="selectedLocationText">

            No location selected yet

        </strong>

    </div>


</div>


<div
    id="locationStatus"
    class="location-status"
>


    <i
        id="locationStatusIcon"
        class="fa-solid fa-circle-info"
    ></i>


    <span id="locationStatusText">

        Search a location or use your current location to begin.

    </span>


</div>


</section>


<!-- =========================================================
     STATS
========================================================== -->

<section class="nearby-stats">


<div class="nearby-stat-card">


    <div class="nearby-stat-icon">

        <i class="fa-solid fa-store"></i>

    </div>


    <div class="nearby-stat-info">

        <small>
            STORES FOUND
        </small>

        <strong id="storeCountNumber">
            0
        </strong>

    </div>


</div>


<div class="nearby-stat-card">


    <div class="nearby-stat-icon">

        <i class="fa-solid fa-location-arrow"></i>

    </div>


    <div class="nearby-stat-info">

        <small>
            SEARCH RADIUS
        </small>

        <strong id="radiusStat">
            20 KM
        </strong>

    </div>


</div>


<div class="nearby-stat-card">


    <div class="nearby-stat-icon">

        <i class="fa-solid fa-arrow-down-short-wide"></i>

    </div>


    <div class="nearby-stat-info">

        <small>
            STORE ORDER
        </small>

        <strong>
            Nearest
        </strong>

    </div>


</div>


</section>


<!-- =========================================================
     STORE SECTION
========================================================== -->

<section class="nearby-store-section">


<!-- HEADER -->

<div class="nearby-section-header">


<div class="nearby-section-title">

    <h2>
        Nearby Stores
    </h2>

    <p id="resultDescription">

        Choose your location to discover nearby stores.

    </p>

</div>


<!-- FILTER -->

<div class="nearby-filter-area">


<button
    type="button"
    class="nearby-filter"
    data-radius="5"
>
    5 KM
</button>


<button
    type="button"
    class="nearby-filter"
    data-radius="10"
>
    10 KM
</button>


<button
    type="button"
    class="nearby-filter active"
    data-radius="20"
>
    20 KM
</button>


<button
    type="button"
    class="nearby-filter"
    data-radius="all"
>
    ALL
</button>


</div>


</div>


<!-- =========================================================
     STORE GRID
========================================================== -->

<div
    id="storeGrid"
    class="nearby-store-grid"
>


<div class="nearby-state">


    <div class="nearby-state-inner">


        <div class="nearby-state-icon">

            <i class="fa-solid fa-location-dot"></i>

        </div>


        <h3>
            Find stores around you
        </h3>


        <p>

            Search for your location above or use your
            current location. HochipoHub will display
            approved stores closest to you.

        </p>


    </div>


</div>


</div>


<!-- PRIVACY -->

<div class="nearby-privacy">

    <i class="fa-solid fa-shield-halved"></i>

    <span>

        Your location is only used to calculate the
        distance between you and available stores.
        Laptop location may be approximate, so manual
        location search can provide a better result.

    </span>

</div>


</section>


</div>

</main>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const useLocationButton =
            document.getElementById(
                'useLocationButton'
            );


        const manualLocationInput =
            document.getElementById(
                'manualLocationInput'
            );


        const searchLocationButton =
            document.getElementById(
                'searchLocationButton'
            );


        const locationStatus =
            document.getElementById(
                'locationStatus'
            );


        const locationStatusIcon =
            document.getElementById(
                'locationStatusIcon'
            );


        const locationStatusText =
            document.getElementById(
                'locationStatusText'
            );


        const selectedLocationText =
            document.getElementById(
                'selectedLocationText'
            );


        const storeCountNumber =
            document.getElementById(
                'storeCountNumber'
            );


        const radiusStat =
            document.getElementById(
                'radiusStat'
            );


        const resultDescription =
            document.getElementById(
                'resultDescription'
            );


        const storeGrid =
            document.getElementById(
                'storeGrid'
            );


        const filterButtons =
            document.querySelectorAll(
                '.nearby-filter'
            );


        /*
        |--------------------------------------------------------------------------
        | STATE
        |--------------------------------------------------------------------------
        */

        let customerLatitude = null;

        let customerLongitude = null;

        let selectedRadius = '20';

        let selectedLocationName = '';


        /*
        |--------------------------------------------------------------------------
        | ESCAPE HTML
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value)
        {
            const div =
                document.createElement(
                    'div'
                );


            div.textContent =
                value === null ||
                value === undefined
                    ? ''
                    : String(value);


            return div.innerHTML;
        }


        /*
        |--------------------------------------------------------------------------
        | LOCATION STATUS
        |--------------------------------------------------------------------------
        */

        function setLocationStatus(
            type,
            message,
            iconClass
        ) {

            locationStatus.classList.remove(
                'success',
                'error',
                'loading',
                'warning'
            );


            if (type) {

                locationStatus.classList.add(
                    type
                );
            }


            locationStatusText.textContent =
                message;


            locationStatusIcon.className =
                iconClass;
        }


        /*
        |--------------------------------------------------------------------------
        | STORE COUNT
        |--------------------------------------------------------------------------
        */

        function updateStoreCount(count)
        {
            const total =
                Number(count) || 0;


            storeCountNumber.textContent =
                total;
        }


        /*
        |--------------------------------------------------------------------------
        | RADIUS STAT
        |--------------------------------------------------------------------------
        */

        function updateRadiusStat()
        {
            radiusStat.textContent =
                selectedRadius === 'all'
                    ? 'ALL'
                    : selectedRadius + ' KM';
        }


        /*
        |--------------------------------------------------------------------------
        | APPLY LOCATION
        |--------------------------------------------------------------------------
        */

        function applyLocation(
            latitude,
            longitude,
            locationName,
            statusMessage,
            statusType
        ) {

            const lat =
                Number(latitude);


            const lng =
                Number(longitude);


            if (
                !Number.isFinite(lat) ||
                !Number.isFinite(lng) ||
                lat < -90 ||
                lat > 90 ||
                lng < -180 ||
                lng > 180
            ) {

                setLocationStatus(
                    'error',
                    'The selected location is invalid.',
                    'fa-solid fa-circle-exclamation'
                );


                return;
            }


            customerLatitude =
                lat.toFixed(8);


            customerLongitude =
                lng.toFixed(8);


            selectedLocationName =
                String(
                    locationName ||
                    'Selected Location'
                ).trim();


            selectedLocationText.textContent =
                selectedLocationName;


            setLocationStatus(
                statusType || 'success',
                statusMessage ||
                    'Location selected successfully.',
                statusType === 'warning'
                    ? 'fa-solid fa-triangle-exclamation'
                    : 'fa-solid fa-circle-check'
            );


            loadNearbyStores();
        }


        /*
        |--------------------------------------------------------------------------
        | INITIAL STATE
        |--------------------------------------------------------------------------
        */

        function showInitialState()
        {
            updateStoreCount(0);


            resultDescription.textContent =
                'Choose your location to discover nearby stores.';


            storeGrid.innerHTML = `

                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-state-icon">

                            <i class="fa-solid fa-location-dot"></i>

                        </div>

                        <h3>
                            Find stores around you
                        </h3>

                        <p>
                            Search for your location above or
                            use your current location.
                            HochipoHub will display approved
                            stores closest to you.
                        </p>

                    </div>

                </div>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */

        function showLoadingState()
        {
            storeGrid.innerHTML = `

                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-loader"></div>

                        <h3>
                            Finding nearby stores...
                        </h3>

                        <p>
                            Please wait while HochipoHub
                            calculates the distance between
                            your selected location and
                            available stores.
                        </p>

                    </div>

                </div>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        function showErrorState(message)
        {
            updateStoreCount(0);


            storeGrid.innerHTML = `

                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-state-icon error">

                            <i class="fa-solid fa-circle-exclamation"></i>

                        </div>

                        <h3>
                            Unable to load stores
                        </h3>

                        <p>
                            ${escapeHtml(message)}
                        </p>

                    </div>

                </div>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        function showEmptyState()
        {
            updateStoreCount(0);


            const radiusText =
                selectedRadius === 'all'
                    ? 'the available area'
                    : selectedRadius + ' KM';


            resultDescription.textContent =
                'No approved stores were found within ' +
                radiusText +
                '.';


            storeGrid.innerHTML = `

                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-state-icon empty">

                            <i class="fa-solid fa-store-slash"></i>

                        </div>

                        <h3>
                            No nearby stores found
                        </h3>

                        <p>
                            There are currently no approved
                            stores with a saved location within
                            ${escapeHtml(radiusText)}.
                            Try selecting a larger distance.
                        </p>

                    </div>

                </div>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | DELIVERY ICON
        |--------------------------------------------------------------------------
        */

        function getDeliveryIcon(option)
        {
            if (option === 'Pickup') {

                return 'fa-store';
            }


            if (option === 'Postage') {

                return 'fa-box';
            }


            if (
                option === 'Vendor Delivery'
            ) {

                return 'fa-motorcycle';
            }


            return 'fa-truck';
        }


        /*
        |--------------------------------------------------------------------------
        | STORE CARD
        |--------------------------------------------------------------------------
        */

        function createStoreCard(store)
        {
            const businessName =
                escapeHtml(
                    store.business_name ||
                    'Store'
                );


            const category =
                escapeHtml(
                    store.category ||
                    'General'
                );


            const address =
                escapeHtml(
                    store.business_address ||
                    'Address not provided'
                );


            const description =
                escapeHtml(
                    store.business_description ||
                    'Discover products available from this HochipoHub seller.'
                );


            const productCount =
                Number(
                    store.product_count ||
                    0
                );


            const deliveryMethod =
                escapeHtml(
                    store.delivery_method ||
                    '-'
                );


            const storeUrl =
                escapeHtml(
                    store.store_url ||
                    '#'
                );


            /*
            |--------------------------------------------------------------------------
            | DISTANCE
            |--------------------------------------------------------------------------
            */

            let distanceText =
                'Distance unavailable';


            if (
                store.distance_text !== undefined &&
                store.distance_text !== null &&
                String(
                    store.distance_text
                ).trim() !== ''
            ) {

                distanceText =
                    String(
                        store.distance_text
                    );

            } else if (
                store.distance_km !== undefined &&
                store.distance_km !== null &&
                !Number.isNaN(
                    Number(
                        store.distance_km
                    )
                )
            ) {

                distanceText =
                    Number(
                        store.distance_km
                    ).toFixed(1) +
                    ' km away';
            }


            distanceText =
                escapeHtml(
                    distanceText
                );


            /*
            |--------------------------------------------------------------------------
            | LOGO
            |--------------------------------------------------------------------------
            */

            let logoHtml = `

                <i class="fa-solid fa-store"></i>
            `;


            let logoSource = '';


            if (
                store.logo_url &&
                String(
                    store.logo_url
                ).trim() !== ''
            ) {

                logoSource =
                    String(
                        store.logo_url
                    );

            } else if (
                store.business_logo &&
                String(
                    store.business_logo
                ).trim() !== ''
            ) {

                logoSource =
                    String(
                        store.business_logo
                    );
            }


            if (logoSource !== '') {

                const logoUrl =
                    escapeHtml(
                        logoSource
                    );


                logoHtml = `

                    <img
                        src="${logoUrl}"
                        alt="${businessName}"
                        loading="lazy"
                        onerror="
                            this.style.display='none';
                            this.parentElement.innerHTML=
                            '<i class=&quot;fa-solid fa-store&quot;></i>';
                        "
                    >
                `;
            }


            /*
            |--------------------------------------------------------------------------
            | DELIVERY TAGS
            |--------------------------------------------------------------------------
            */

            let deliveryTags = '';


            if (
                Array.isArray(
                    store.delivery_options
                ) &&
                store.delivery_options.length > 0
            ) {

                store.delivery_options.forEach(
                    function (option) {

                        const cleanOption =
                            escapeHtml(
                                option
                            );


                        const icon =
                            getDeliveryIcon(
                                option
                            );


                        deliveryTags += `

                            <span class="nearby-delivery-tag">

                                <i class="fa-solid ${icon}"></i>

                                ${cleanOption}

                            </span>
                        `;
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | HTML
            |--------------------------------------------------------------------------
            */

            return `

                <article class="nearby-store-card">


                    <div class="nearby-store-card-top">


                        <div class="nearby-store-logo">

                            ${logoHtml}

                        </div>


                        <div class="nearby-store-heading">


                            <span class="nearby-store-category">

                                ${category}

                            </span>


                            <h3 class="nearby-store-name">

                                ${businessName}

                            </h3>


                            <span class="nearby-distance">

                                <i class="fa-solid fa-location-arrow"></i>

                                ${distanceText}

                            </span>


                        </div>


                    </div>


                    <div class="nearby-store-body">


                        <p class="nearby-store-address">

                            <i class="fa-solid fa-location-dot"></i>

                            <span>
                                ${address}
                            </span>

                        </p>


                        <p class="nearby-store-description">

                            ${description}

                        </p>


                        <div class="nearby-store-meta">


                            <div class="nearby-meta-box">

                                <span>
                                    PRODUCTS
                                </span>

                                <strong>
                                    ${productCount}
                                </strong>

                            </div>


                            <div class="nearby-meta-box">

                                <span>
                                    DELIVERY
                                </span>

                                <strong>
                                    ${deliveryMethod}
                                </strong>

                            </div>


                        </div>


                        <div class="nearby-delivery-tags">

                            ${deliveryTags}

                        </div>


                    </div>


                    <div class="nearby-store-action">


                        <a
                            href="${storeUrl}"
                            class="nearby-visit-store"
                        >

                            <i class="fa-solid fa-store"></i>

                            Visit Store

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>


                </article>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | RENDER
        |--------------------------------------------------------------------------
        */

        function renderStores(stores)
        {
            if (
                !Array.isArray(stores) ||
                stores.length === 0
            ) {

                showEmptyState();

                return;
            }


            updateStoreCount(
                stores.length
            );


            const radiusText =
                selectedRadius === 'all'
                    ? 'all available distances'
                    : selectedRadius + ' KM';


            resultDescription.textContent =
                'Showing ' +
                stores.length +
                (
                    stores.length === 1
                        ? ' store'
                        : ' stores'
                ) +
                ' within ' +
                radiusText +
                ', nearest first.';


            let html = '';


            stores.forEach(
                function (store) {

                    html +=
                        createStoreCard(
                            store
                        );
                }
            );


            storeGrid.innerHTML =
                html;
        }


        /*
        |--------------------------------------------------------------------------
        | AJAX LOAD STORES
        |--------------------------------------------------------------------------
        */

        async function loadNearbyStores()
        {
            if (
                customerLatitude === null ||
                customerLongitude === null
            ) {

                showInitialState();

                return;
            }


            showLoadingState();


            const formData =
                new FormData();


            formData.append(
                'latitude',
                customerLatitude
            );


            formData.append(
                'longitude',
                customerLongitude
            );


            formData.append(
                'radius',
                selectedRadius
            );


            try {

                const response =
                    await fetch(
                        '<?= nearbyEscape(BASE_URL) ?>ajax/load_nearby_stores.php',
                        {
                            method: 'POST',
                            body: formData
                        }
                    );


                const rawResponse =
                    await response.text();


                let data;


                try {

                    data =
                        JSON.parse(
                            rawResponse
                        );

                } catch (jsonError) {

                    console.error(
                        'Nearby Stores raw response:',
                        rawResponse
                    );


                    throw new Error(
                        'The server returned an invalid response. Check ajax/load_nearby_stores.php.'
                    );
                }


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Unable to load nearby stores.'
                    );
                }


                if (!data.success) {

                    throw new Error(
                        data.message ||
                        'Unable to load nearby stores.'
                    );
                }


                renderStores(
                    data.stores || []
                );


            } catch (error) {

                console.error(
                    'Nearby Stores Error:',
                    error
                );


                resultDescription.textContent =
                    'Unable to load nearby stores.';


                showErrorState(
                    error.message ||
                    'Unable to load nearby stores.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CURRENT LOCATION
        |--------------------------------------------------------------------------
        */

        if (useLocationButton) {

            useLocationButton.addEventListener(
                'click',
                function () {


                    if (!navigator.geolocation) {

                        setLocationStatus(
                            'error',
                            'Geolocation is not supported by this browser. Please search your location manually.',
                            'fa-solid fa-circle-exclamation'
                        );


                        return;
                    }


                    useLocationButton.disabled =
                        true;


                    useLocationButton.innerHTML = `

                        <i class="fa-solid fa-spinner fa-spin"></i>

                        Detecting location...
                    `;


                    setLocationStatus(
                        'loading',
                        'Detecting your current location...',
                        'fa-solid fa-location-crosshairs'
                    );


                    navigator.geolocation.getCurrentPosition(


                        /*
                        |--------------------------------------------------------------------------
                        | SUCCESS
                        |--------------------------------------------------------------------------
                        */

                        function (position) {

                            const latitude =
                                Number(
                                    position.coords.latitude
                                );


                            const longitude =
                                Number(
                                    position.coords.longitude
                                );


                            const accuracy =
                                Number(
                                    position.coords.accuracy ||
                                    0
                                );


                            useLocationButton.disabled =
                                false;


                            useLocationButton.innerHTML = `

                                <i class="fa-solid fa-location-crosshairs"></i>

                                Update My Current Location
                            `;


                            /*
                            |--------------------------------------------------------------------------
                            | LOW ACCURACY
                            |--------------------------------------------------------------------------
                            */

                            if (
                                accuracy > 5000
                            ) {

                                const approximateKm =
                                    (
                                        accuracy /
                                        1000
                                    ).toFixed(1);


                                applyLocation(
                                    latitude,
                                    longitude,
                                    'Current browser location (approximate)',
                                    'Browser location accuracy is approximately ' +
                                        approximateKm +
                                        ' KM. Search your location manually if the distance looks incorrect.',
                                    'warning'
                                );


                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | GOOD LOCATION
                            |--------------------------------------------------------------------------
                            */

                            applyLocation(
                                latitude,
                                longitude,
                                'Current device location',
                                'Current location detected successfully.',
                                'success'
                            );
                        },


                        /*
                        |--------------------------------------------------------------------------
                        | ERROR
                        |--------------------------------------------------------------------------
                        */

                        function (error) {

                            let message =
                                'Unable to detect your current location.';


                            if (
                                error.code === 1
                            ) {

                                message =
                                    'Location permission was denied. Please allow location access or search manually.';

                            } else if (
                                error.code === 2
                            ) {

                                message =
                                    'Your current location is unavailable. Please search manually.';

                            } else if (
                                error.code === 3
                            ) {

                                message =
                                    'Location request timed out. Please try again or search manually.';
                            }


                            setLocationStatus(
                                'error',
                                message,
                                'fa-solid fa-circle-exclamation'
                            );


                            useLocationButton.disabled =
                                false;


                            useLocationButton.innerHTML = `

                                <i class="fa-solid fa-location-crosshairs"></i>

                                Use My Current Location
                            `;
                        },


                        /*
                        |--------------------------------------------------------------------------
                        | OPTIONS
                        |--------------------------------------------------------------------------
                        */

                        {
                            enableHighAccuracy:
                                true,

                            timeout:
                                15000,

                            maximumAge:
                                0
                        }
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MANUAL LOCATION SEARCH
        |--------------------------------------------------------------------------
        */

        async function searchManualLocation()
        {
            const query =
                manualLocationInput
                    ? manualLocationInput.value.trim()
                    : '';


            if (query === '') {

                setLocationStatus(
                    'error',
                    'Please enter a location, area or address first.',
                    'fa-solid fa-circle-exclamation'
                );


                if (manualLocationInput) {

                    manualLocationInput.focus();
                }


                return;
            }


            searchLocationButton.disabled =
                true;


            searchLocationButton.innerHTML = `

                <i class="fa-solid fa-spinner fa-spin"></i>

                Searching
            `;


            setLocationStatus(
                'loading',
                'Searching for "' +
                    query +
                    '"...',
                'fa-solid fa-magnifying-glass-location'
            );


            try {

                /*
                |--------------------------------------------------------------------------
                | OPENSTREETMAP NOMINATIM
                |--------------------------------------------------------------------------
                */

                const searchUrl =
                    'https://nominatim.openstreetmap.org/search' +
                    '?format=jsonv2' +
                    '&limit=1' +
                    '&countrycodes=my' +
                    '&addressdetails=1' +
                    '&q=' +
                    encodeURIComponent(
                        query
                    );


                const response =
                    await fetch(
                        searchUrl,
                        {
                            method: 'GET',
                            headers: {
                                'Accept':
                                    'application/json'
                            }
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        'Location search service is currently unavailable.'
                    );
                }


                const results =
                    await response.json();


                if (
                    !Array.isArray(results) ||
                    results.length === 0
                ) {

                    throw new Error(
                        'Location not found. Try entering a more specific location.'
                    );
                }


                const location =
                    results[0];


                const latitude =
                    Number(
                        location.lat
                    );


                const longitude =
                    Number(
                        location.lon
                    );


                let displayName =
                    String(
                        location.display_name ||
                        query
                    ).trim();


                if (
                    displayName.length > 170
                ) {

                    displayName =
                        displayName.substring(
                            0,
                            167
                        ) +
                        '...';
                }


                applyLocation(
                    latitude,
                    longitude,
                    displayName,
                    'Location selected successfully.',
                    'success'
                );


            } catch (error) {

                console.error(
                    'Manual Location Search Error:',
                    error
                );


                setLocationStatus(
                    'error',
                    error.message ||
                        'Unable to search for this location.',
                    'fa-solid fa-circle-exclamation'
                );


            } finally {

                searchLocationButton.disabled =
                    false;


                searchLocationButton.innerHTML = `

                    <i class="fa-solid fa-magnifying-glass"></i>

                    Search
                `;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH BUTTON
        |--------------------------------------------------------------------------
        */

        if (searchLocationButton) {

            searchLocationButton.addEventListener(
                'click',
                function () {

                    searchManualLocation();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ENTER KEY
        |--------------------------------------------------------------------------
        */

        if (manualLocationInput) {

            manualLocationInput.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key === 'Enter'
                    ) {

                        event.preventDefault();

                        searchManualLocation();
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        filterButtons.forEach(
            function (button) {


                button.addEventListener(
                    'click',
                    function () {


                        filterButtons.forEach(
                            function (filterButton) {

                                filterButton.classList.remove(
                                    'active'
                                );
                            }
                        );


                        button.classList.add(
                            'active'
                        );


                        selectedRadius =
                            button.dataset.radius ||
                            '20';


                        updateRadiusStat();


                        if (
                            customerLatitude !== null &&
                            customerLongitude !== null
                        ) {

                            loadNearbyStores();

                        } else {

                            resultDescription.textContent =
                                'Choose your location before using the distance filter.';
                        }
                    }
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | INITIAL
        |--------------------------------------------------------------------------
        */

        updateRadiusStat();

        showInitialState();

    }
);

</script>


</body>

</html>