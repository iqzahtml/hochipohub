<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - NEARBY STORES
|--------------------------------------------------------------------------
| File:
| nearby_stores.php
|
| Function:
| - Customer allows browser location
| - Send customer latitude / longitude to AJAX
| - Load approved nearby vendors
| - Filter 5 KM / 10 KM / 20 KM / All
| - Display nearest stores first
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

        $userStmt =
            $db->prepare("
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
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Nearby Stores | HochipoHub';


$userName =
    trim(
        (string) (
            $currentUser['name']
            ?? ''
        )
    );


$userInitial =
    strtoupper(
        substr(
            $userName !== ''
                ? $userName
                : 'C',
            0,
            1
        )
    );

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
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap"
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
   RESET
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

    color: #14213d;
    background: #f6f8fc;

    font-family:
        Inter,
        Arial,
        sans-serif;
}


/* =========================================================
   PAGE
========================================================= */

.nearby-main {
    min-height: 100vh;
    padding-bottom: 70px;

    background:
        radial-gradient(
            circle at 90% 5%,
            rgba(37, 99, 235, .08),
            transparent 24%
        ),
        radial-gradient(
            circle at 5% 30%,
            rgba(99, 102, 241, .05),
            transparent 22%
        ),
        #f6f8fc;
}

.nearby-container {
    width: calc(100% - 40px);
    max-width: 1280px;

    margin: 0 auto;
}


/* =========================================================
   TOPBAR
========================================================= */

.nearby-topbar {
    position: sticky;
    top: 0;
    z-index: 100;

    min-height: 70px;

    background:
        rgba(
            255,
            255,
            255,
            .96
        );

    border-bottom:
        1px solid #e8edf5;

    backdrop-filter:
        blur(14px);
}

.nearby-topbar-inner {
    min-height: 70px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.nearby-brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;

    color: #15376b;
    text-decoration: none;
}

.nearby-brand-logo {
    width: 39px;
    height: 39px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    border-radius: 12px;

    box-shadow:
        0 8px 20px
        rgba(37, 99, 235, .18);
}

.nearby-brand strong {
    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 17px;
    font-weight: 800;
}

.nearby-nav {
    display: flex;
    align-items: center;
    gap: 8px;
}

.nearby-nav-link {
    min-height: 39px;
    padding: 0 13px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    color: #50647f;
    background: transparent;

    border-radius: 10px;

    font-size: 9px;
    font-weight: 800;
    text-decoration: none;

    transition: .18s ease;
}

.nearby-nav-link:hover {
    color: #2563eb;
    background: #eff6ff;
}

.nearby-nav-link.active {
    color: #2563eb;
    background: #edf5ff;
}

.nearby-user {
    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    border-radius: 50%;

    font-size: 11px;
    font-weight: 900;
}


/* =========================================================
   HERO
========================================================= */

.nearby-hero {
    position: relative;
    overflow: hidden;

    margin-top: 30px;
    padding: 40px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 35px;

    color: #ffffff;

    background:
        linear-gradient(
            115deg,
            #123c7c 0%,
            #2466c6 48%,
            #398cf0 100%
        );

    border-radius: 25px;

    box-shadow:
        0 20px 50px
        rgba(35, 91, 180, .18);
}

.nearby-hero::before {
    content: "";

    position: absolute;

    width: 290px;
    height: 290px;

    right: -85px;
    top: -165px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .08
        );
}

.nearby-hero::after {
    content: "";

    position: absolute;

    width: 200px;
    height: 200px;

    right: 220px;
    bottom: -155px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .05
        );
}

.nearby-hero-content {
    position: relative;
    z-index: 2;

    max-width: 670px;
}

.nearby-eyebrow {
    display: block;

    margin-bottom: 10px;

    color: #bfdbfe;

    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1.5px;

    text-transform: uppercase;
}

.nearby-hero h1 {
    margin: 0 0 12px;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:
        clamp(
            29px,
            4vw,
            42px
        );

    line-height: 1.15;
    letter-spacing: -1.2px;
}

.nearby-hero p {
    max-width: 620px;

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            .80
        );

    font-size: 11px;
    line-height: 1.75;
}

.nearby-hero-icon {
    position: relative;
    z-index: 2;

    width: 112px;
    height: 112px;

    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

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

    border-radius: 27px;

    backdrop-filter:
        blur(10px);

    font-size: 43px;
}


/* =========================================================
   LOCATION CONTROL
========================================================= */

.nearby-control {
    position: relative;
    z-index: 5;

    margin-top: -20px;
    padding: 23px;

    background: #ffffff;

    border:
        1px solid #e2e8f2;

    border-radius: 20px;

    box-shadow:
        0 16px 38px
        rgba(28, 58, 103, .09);
}

.nearby-control-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.nearby-control-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nearby-control-icon {
    width: 45px;
    height: 45px;

    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    color: #2563eb;
    background: #edf5ff;

    border:
        1px solid #dbeafe;

    border-radius: 13px;

    font-size: 16px;
}

.nearby-control-title h2 {
    margin: 0 0 4px;

    color: #17365f;

    font-size: 14px;
}

.nearby-control-title p {
    margin: 0;

    color: #8a98ab;

    font-size: 9px;
    line-height: 1.55;
}

.nearby-location-button {
    min-height: 44px;
    padding: 0 17px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

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
        0 9px 22px
        rgba(37, 99, 235, .19);

    font-family: inherit;
    font-size: 9px;
    font-weight: 850;

    cursor: pointer;

    transition: .18s ease;
}

.nearby-location-button:hover {
    transform:
        translateY(-1px);
}

.nearby-location-button:disabled {
    opacity: .65;
    cursor: wait;
    transform: none;
}


/* =========================================================
   LOCATION STATUS
========================================================= */

.nearby-location-status {
    margin-top: 16px;
    padding: 12px 14px;

    display: flex;
    align-items: flex-start;
    gap: 9px;

    color: #52667f;
    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 12px;

    font-size: 9px;
    line-height: 1.55;
}

.nearby-location-status.success {
    color: #087443;
    background: #ecfdf3;
    border-color: #a7f3d0;
}

.nearby-location-status.error {
    color: #b42318;
    background: #fff2f1;
    border-color: #fecaca;
}

.nearby-location-status.loading {
    color: #1d4ed8;
    background: #eff6ff;
    border-color: #bfdbfe;
}


/* =========================================================
   FILTER
========================================================= */

.nearby-filter-area {
    margin-top: 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;

    flex-wrap: wrap;
}

.nearby-filter-label {
    color: #718198;

    font-size: 9px;
    font-weight: 800;
}

.nearby-filter-buttons {
    display: flex;
    align-items: center;
    gap: 8px;

    flex-wrap: wrap;
}

.nearby-filter {
    min-width: 67px;
    min-height: 35px;

    padding: 0 12px;

    color: #63758d;
    background: #ffffff;

    border:
        1px solid #dfe7f1;

    border-radius: 10px;

    font-family: inherit;
    font-size: 8px;
    font-weight: 850;

    cursor: pointer;

    transition: .18s ease;
}

.nearby-filter:hover {
    color: #2563eb;
    border-color: #93baf7;
}

.nearby-filter.active {
    color: #ffffff;
    background: #2563eb;
    border-color: #2563eb;

    box-shadow:
        0 7px 16px
        rgba(37, 99, 235, .16);
}


/* =========================================================
   RESULT HEADER
========================================================= */

.nearby-result-header {
    margin-top: 30px;
    margin-bottom: 17px;

    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}

.nearby-result-header h2 {
    margin: 0 0 5px;

    color: #142f55;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 20px;
}

.nearby-result-header p {
    margin: 0;

    color: #8a98ab;

    font-size: 9px;
}

.nearby-result-count {
    padding: 8px 12px;

    color: #2563eb;
    background: #edf5ff;

    border:
        1px solid #dbeafe;

    border-radius: 999px;

    font-size: 8px;
    font-weight: 850;
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

    gap: 18px;
}


/* =========================================================
   STORE CARD
========================================================= */

.nearby-store-card {
    position: relative;

    min-width: 0;
    overflow: hidden;

    display: flex;
    flex-direction: column;

    background: #ffffff;

    border:
        1px solid #e0e7f1;

    border-radius: 20px;

    box-shadow:
        0 10px 30px
        rgba(28, 59, 102, .055);

    transition:
        transform .18s ease,
        box-shadow .18s ease,
        border-color .18s ease;
}

.nearby-store-card:hover {
    transform:
        translateY(-3px);

    border-color: #c6daf8;

    box-shadow:
        0 17px 38px
        rgba(28, 59, 102, .10);
}


/* =========================================================
   CARD TOP
========================================================= */

.nearby-store-top {
    padding: 19px;

    display: flex;
    align-items: flex-start;
    gap: 13px;
}

.nearby-store-logo {
    width: 65px;
    height: 65px;

    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    color: #2563eb;

    background:
        linear-gradient(
            135deg,
            #eef5ff,
            #f3f0ff
        );

    border:
        1px solid #dfe7f2;

    border-radius: 16px;

    font-size: 23px;
}

.nearby-store-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nearby-store-main-info {
    min-width: 0;
    flex: 1;
}

.nearby-store-category {
    display: block;

    margin-bottom: 5px;

    color: #2563eb;

    font-size: 8px;
    font-weight: 850;

    text-transform: uppercase;
    letter-spacing: .6px;
}

.nearby-store-name {
    margin: 0 0 7px;

    overflow: hidden;

    color: #18365e;

    font-size: 14px;
    font-weight: 850;

    line-height: 1.35;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.nearby-distance {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    padding: 5px 8px;

    color: #087443;
    background: #ecfdf3;

    border-radius: 999px;

    font-size: 8px;
    font-weight: 850;
}


/* =========================================================
   CARD BODY
========================================================= */

.nearby-store-body {
    padding: 0 19px 19px;

    display: flex;
    flex: 1;
    flex-direction: column;
}

.nearby-store-address {
    min-height: 42px;

    margin: 0 0 13px;

    display: flex;
    align-items: flex-start;
    gap: 7px;

    color: #718198;

    font-size: 9px;
    line-height: 1.6;
}

.nearby-store-address i {
    margin-top: 3px;
    color: #ef4444;
}

.nearby-store-description {
    margin: 0 0 15px;

    color: #7c8da4;

    font-size: 9px;
    line-height: 1.65;

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
    padding-top: 14px;

    display: grid;
    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 9px;

    border-top:
        1px solid #edf1f6;
}

.nearby-meta-box {
    min-width: 0;
    padding: 9px;

    background: #f8fafc;
    border-radius: 10px;
}

.nearby-meta-box span {
    display: block;

    margin-bottom: 4px;

    color: #93a0b2;

    font-size: 7px;
    font-weight: 750;
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
    margin-top: 13px;

    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.nearby-delivery-tag {
    padding: 5px 7px;

    color: #506681;
    background: #f1f5f9;

    border-radius: 7px;

    font-size: 7px;
    font-weight: 750;
}

.nearby-delivery-tag i {
    margin-right: 3px;
    color: #2563eb;
}


/* =========================================================
   VISIT BUTTON
========================================================= */

.nearby-store-action {
    padding: 0 19px 19px;
}

.nearby-visit-store {
    min-height: 40px;
    width: 100%;

    display: flex;
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

    border-radius: 11px;

    box-shadow:
        0 8px 18px
        rgba(37, 99, 235, .16);

    font-size: 8px;
    font-weight: 850;
    text-decoration: none;

    transition: .18s ease;
}

.nearby-visit-store:hover {
    transform:
        translateY(-1px);
}


/* =========================================================
   EMPTY / INITIAL / LOADING
========================================================= */

.nearby-state {
    grid-column: 1 / -1;

    min-height: 320px;
    padding: 45px 25px;

    display: flex;
    align-items: center;
    justify-content: center;

    text-align: center;

    background: #ffffff;

    border:
        1px solid #e1e8f2;

    border-radius: 21px;
}

.nearby-state-inner {
    max-width: 470px;
}

.nearby-state-icon {
    width: 76px;
    height: 76px;

    margin: 0 auto 17px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;

    background: #edf5ff;

    border-radius: 22px;

    font-size: 27px;
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
    margin: 0 0 8px;

    color: #17365f;

    font-size: 16px;
}

.nearby-state p {
    margin: 0;

    color: #8493a8;

    font-size: 9px;
    line-height: 1.7;
}


/* =========================================================
   LOADER
========================================================= */

.nearby-loader {
    width: 35px;
    height: 35px;

    margin: 0 auto 17px;

    border:
        4px solid #e5edfa;

    border-top-color:
        #2563eb;

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
   SECURITY NOTE
========================================================= */

.nearby-privacy-note {
    margin-top: 15px;

    display: flex;
    align-items: flex-start;
    gap: 7px;

    color: #8493a7;

    font-size: 8px;
    line-height: 1.55;
}

.nearby-privacy-note i {
    margin-top: 2px;
    color: #2563eb;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

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


@media (max-width: 780px) {

    .nearby-nav {
        display: none;
    }

    .nearby-hero {
        padding: 30px;

        align-items: flex-start;
        flex-direction: column;
    }

    .nearby-hero-icon {
        width: 80px;
        height: 80px;

        font-size: 30px;
    }

    .nearby-control-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .nearby-location-button {
        width: 100%;
    }
}


@media (max-width: 620px) {

    .nearby-container {
        width: calc(100% - 28px);
    }

    .nearby-topbar-inner {
        min-height: 64px;
    }

    .nearby-brand strong {
        font-size: 15px;
    }

    .nearby-hero {
        margin-top: 18px;
        padding: 25px 21px;

        border-radius: 20px;
    }

    .nearby-hero h1 {
        font-size: 27px;
    }

    .nearby-control {
        margin-top: -12px;
        padding: 18px;

        border-radius: 17px;
    }

    .nearby-control-title {
        align-items: flex-start;
    }

    .nearby-filter-area {
        align-items: flex-start;
        flex-direction: column;
    }

    .nearby-filter-buttons {
        width: 100%;
    }

    .nearby-filter {
        flex: 1;
    }

    .nearby-result-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .nearby-store-grid {
        grid-template-columns: 1fr;
    }

    .nearby-store-card {
        border-radius: 17px;
    }
}

</style>

</head>


<body class="nearby-page">


<div class="nearby-main">


<!-- =========================================================
     TOPBAR
========================================================== -->

<header class="nearby-topbar">

<div class="nearby-container nearby-topbar-inner">


<a
    href="<?= nearbyEscape(BASE_URL) ?>index.php"
    class="nearby-brand"
>

    <span class="nearby-brand-logo">

        <i class="fa-solid fa-store"></i>

    </span>

    <strong>
        HochipoHub
    </strong>

</a>


<nav class="nearby-nav">

    <a
        href="<?= nearbyEscape(BASE_URL) ?>index.php"
        class="nearby-nav-link"
    >

        <i class="fa-solid fa-house"></i>

        Home

    </a>


    <a
        href="<?= nearbyEscape(BASE_URL) ?>catalog.php"
        class="nearby-nav-link"
    >

        <i class="fa-solid fa-bag-shopping"></i>

        Shop

    </a>


    <a
        href="<?= nearbyEscape(BASE_URL) ?>nearby_stores.php"
        class="nearby-nav-link active"
    >

        <i class="fa-solid fa-location-dot"></i>

        Nearby Stores

    </a>


    <?php if ($isLoggedIn): ?>

        <a
            href="<?= nearbyEscape(BASE_URL) ?>dashboard.php"
            class="nearby-nav-link"
        >

            <i class="fa-solid fa-user"></i>

            Dashboard

        </a>

    <?php endif; ?>

</nav>


<div class="nearby-user">

    <?php if ($isLoggedIn): ?>

        <?= nearbyEscape(
            $userInitial
        ) ?>

    <?php else: ?>

        <i class="fa-solid fa-user"></i>

    <?php endif; ?>

</div>


</div>

</header>


<!-- =========================================================
     CONTENT
========================================================== -->

<div class="nearby-container">


<!-- =========================================================
     HERO
========================================================== -->

<section class="nearby-hero">


<div class="nearby-hero-content">

    <span class="nearby-eyebrow">
        DISCOVER LOCAL SELLERS
    </span>

    <h1>
        Find stores near you.
    </h1>

    <p>
        Discover HochipoHub sellers based on
        your current location. Stores are
        automatically arranged from nearest
        to furthest so you can shop locally
        with ease.
    </p>

</div>


<div class="nearby-hero-icon">

    <i class="fa-solid fa-map-location-dot"></i>

</div>


</section>


<!-- =========================================================
     LOCATION CONTROL
========================================================== -->

<section class="nearby-control">


<div class="nearby-control-top">


<div class="nearby-control-title">

    <div class="nearby-control-icon">

        <i class="fa-solid fa-location-crosshairs"></i>

    </div>


    <div>

        <h2>
            Your Current Location
        </h2>

        <p>
            Allow location access to discover
            stores closest to you.
        </p>

    </div>

</div>


<button
    type="button"
    id="useLocationButton"
    class="nearby-location-button"
>

    <i class="fa-solid fa-location-crosshairs"></i>

    Use My Current Location

</button>


</div>


<div
    id="locationStatus"
    class="nearby-location-status"
>

    <i
        id="locationStatusIcon"
        class="fa-solid fa-circle-info"
    ></i>

    <span id="locationStatusText">
        Your location has not been detected yet.
        Press "Use My Current Location" to begin.
    </span>

</div>


<div class="nearby-filter-area">


<div class="nearby-filter-label">

    <i class="fa-solid fa-filter"></i>

    Show stores within:

</div>


<div class="nearby-filter-buttons">


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


<div class="nearby-privacy-note">

    <i class="fa-solid fa-shield-halved"></i>

    <span>
        Your browser will only provide your
        location after you give permission.
        The location is used to calculate
        distance between you and nearby stores.
    </span>

</div>


</section>


<!-- =========================================================
     RESULTS HEADER
========================================================== -->

<section class="nearby-result-header">


<div>

    <h2>
        Nearby Stores
    </h2>

    <p id="resultDescription">
        Allow location access to discover stores near you.
    </p>

</div>


<div
    id="storeCount"
    class="nearby-result-count"
>
    0 STORES
</div>


</section>


<!-- =========================================================
     STORE RESULTS
========================================================== -->

<section
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
            Press "Use My Current Location"
            above and allow location access.
            HochipoHub will then show approved
            stores closest to your location.
        </p>


    </div>

</div>


</section>


</div>

</div>


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


        const filterButtons =
            document.querySelectorAll(
                '.nearby-filter'
            );


        const storeGrid =
            document.getElementById(
                'storeGrid'
            );


        const storeCount =
            document.getElementById(
                'storeCount'
            );


        const resultDescription =
            document.getElementById(
                'resultDescription'
            );


        /*
        |--------------------------------------------------------------------------
        | STATE
        |--------------------------------------------------------------------------
        */

        let customerLatitude = null;

        let customerLongitude = null;

        let selectedRadius = '20';


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
                'loading'
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
        | RESULT COUNT
        |--------------------------------------------------------------------------
        */

        function updateStoreCount(count)
        {
            const total =
                Number(count) || 0;


            storeCount.textContent =
                total +
                (
                    total === 1
                        ? ' STORE'
                        : ' STORES'
                );
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
                'Allow location access to discover stores near you.';


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
                            Press "Use My Current Location"
                            above and allow location access.
                            HochipoHub will then show approved
                            stores closest to your location.
                        </p>

                    </div>

                </div>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | LOADING STATE
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
                            your location and available stores.
                        </p>

                    </div>

                </div>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | ERROR STATE
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
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        function showEmptyState()
        {
            updateStoreCount(0);


            let radiusText =
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


            if (option === 'Vendor Delivery') {

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


            const distanceText =
                escapeHtml(
                    store.distance_text ||
                    ''
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
            | LOGO
            |--------------------------------------------------------------------------
            */

            let logoHtml = `

                <i class="fa-solid fa-store"></i>
            `;


            if (
                store.business_logo &&
                String(
                    store.business_logo
                ).trim() !== ''
            ) {

                const logoUrl =
                    escapeHtml(
                        store.business_logo
                    );


                logoHtml = `

                    <img
                        src="${logoUrl}"
                        alt="${businessName}"
                        loading="lazy"
                        onerror="
                            this.style.display='none';
                            this.parentElement.innerHTML=
                            '<i class=\\'fa-solid fa-store\\'></i>';
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
            | CARD
            |--------------------------------------------------------------------------
            */

            return `

                <article class="nearby-store-card">


                    <div class="nearby-store-top">


                        <div class="nearby-store-logo">

                            ${logoHtml}

                        </div>


                        <div class="nearby-store-main-info">


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
        | RENDER STORES
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
                'Showing stores within ' +
                radiusText +
                ', sorted from nearest to furthest.';


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
        | LOAD NEARBY STORES
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


                /*
                |--------------------------------------------------------------------------
                | READ RAW RESPONSE FIRST
                |--------------------------------------------------------------------------
                |
                | This makes debugging easier if PHP returns
                | an error page instead of JSON.
                |
                |--------------------------------------------------------------------------
                */

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
                        'The server returned an invalid response. Check ajax/load_nearby_stores.php for PHP or database errors.'
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


                showErrorState(
                    error.message ||
                    'Unable to load nearby stores.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | GET CUSTOMER CURRENT LOCATION
        |--------------------------------------------------------------------------
        */

        if (useLocationButton) {

            useLocationButton.addEventListener(
                'click',
                function () {


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK GEOLOCATION
                    |--------------------------------------------------------------------------
                    */

                    if (!navigator.geolocation) {

                        setLocationStatus(
                            'error',
                            'Geolocation is not supported by this browser.',
                            'fa-solid fa-circle-exclamation'
                        );


                        showErrorState(
                            'Your browser does not support location services.'
                        );


                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LOADING
                    |--------------------------------------------------------------------------
                    */

                    useLocationButton.disabled =
                        true;


                    useLocationButton.innerHTML = `

                        <i class="fa-solid fa-spinner fa-spin"></i>

                        Detecting Location...
                    `;


                    setLocationStatus(
                        'loading',
                        'Detecting your current location. Please allow location access when your browser asks for permission.',
                        'fa-solid fa-location-crosshairs'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | GEOLOCATION
                    |--------------------------------------------------------------------------
                    */

                    navigator.geolocation.getCurrentPosition(

                        /*
                        |--------------------------------------------------------------------------
                        | SUCCESS
                        |--------------------------------------------------------------------------
                        */

                        function (position) {


                            customerLatitude =
                                Number(
                                    position.coords.latitude
                                ).toFixed(8);


                            customerLongitude =
                                Number(
                                    position.coords.longitude
                                ).toFixed(8);


                            setLocationStatus(
                                'success',
                                'Location detected successfully. Nearby stores are being loaded.',
                                'fa-solid fa-circle-check'
                            );


                            useLocationButton.disabled =
                                false;


                            useLocationButton.innerHTML = `

                                <i class="fa-solid fa-location-crosshairs"></i>

                                Update My Location
                            `;


                            /*
                            |--------------------------------------------------------------------------
                            | LOAD STORES
                            |--------------------------------------------------------------------------
                            */

                            loadNearbyStores();
                        },


                        /*
                        |--------------------------------------------------------------------------
                        | ERROR
                        |--------------------------------------------------------------------------
                        */

                        function (error) {


                            let message =
                                'Unable to detect your current location.';


                            if (error.code === 1) {

                                message =
                                    'Location permission was denied. Please allow location access in your browser and try again.';

                            } else if (
                                error.code === 2
                            ) {

                                message =
                                    'Your current location is unavailable. Please turn on your device location service and try again.';

                            } else if (
                                error.code === 3
                            ) {

                                message =
                                    'Location request timed out. Please try again.';
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


                            showErrorState(
                                message
                            );
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
        | RADIUS FILTER
        |--------------------------------------------------------------------------
        */

        filterButtons.forEach(
            function (button) {


                button.addEventListener(
                    'click',
                    function () {


                        /*
                        |--------------------------------------------------------------------------
                        | ACTIVE BUTTON
                        |--------------------------------------------------------------------------
                        */

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


                        /*
                        |--------------------------------------------------------------------------
                        | NEW RADIUS
                        |--------------------------------------------------------------------------
                        */

                        selectedRadius =
                            button.dataset.radius ||
                            '20';


                        /*
                        |--------------------------------------------------------------------------
                        | IF LOCATION EXISTS, RELOAD
                        |--------------------------------------------------------------------------
                        */

                        if (
                            customerLatitude !== null &&
                            customerLongitude !== null
                        ) {

                            loadNearbyStores();

                        } else {

                            resultDescription.textContent =
                                'Allow location access before using the distance filter.';
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

        showInitialState();

    }
);

</script>


</body>

</html>