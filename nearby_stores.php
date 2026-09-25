<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - NEARBY STORES
|--------------------------------------------------------------------------
| File: nearby_stores.php
|--------------------------------------------------------------------------
| Functions:
| - Detect customer's current location
| - Warn when browser/device location is inaccurate
| - Allow customer to search location manually
| - Convert searched address to latitude / longitude
| - Load nearby approved vendors through AJAX
| - Filter 5 KM / 10 KM / 20 KM / All
| - Sort stores from nearest to furthest
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
| CURRENT USER
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

        $stmt = $db->prepare("
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

        $stmt->execute([$userId]);

        $currentUser =
            $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {

        $currentUser = null;
    }
}

$pageTitle = 'Nearby Stores | HochipoHub';

$userName = trim(
    (string) ($currentUser['name'] ?? '')
);

$userInitial = strtoupper(
    substr(
        $userName !== '' ? $userName : 'C',
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

<title><?= nearbyEscape($pageTitle) ?></title>

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

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>

<link
    rel="stylesheet"
    href="<?= nearbyEscape(BASE_URL) ?>css/style.css"
>

<link
    rel="stylesheet"
    href="<?= nearbyEscape(BASE_URL) ?>css/responsive.css"
>

<style>

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body.nearby-page {
    margin: 0;
    min-height: 100vh;
    background: #f6f8fc;
    color: #14213d;
    font-family: Inter, Arial, sans-serif;
}

button,
input {
    font: inherit;
}

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

    background: rgba(255, 255, 255, .96);
    border-bottom: 1px solid #e8edf5;

    backdrop-filter: blur(14px);
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
    font-family: Poppins, Inter, sans-serif;
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

    font-size: 11px;
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

    font-size: 10px;
    font-weight: 900;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.nearby-hero h1 {
    margin: 0 0 12px;

    font-family: Poppins, Inter, sans-serif;

    font-size: clamp(27px, 4vw, 42px);
    line-height: 1.15;
    font-weight: 800;
}

.nearby-hero p {
    max-width: 610px;
    margin: 0;

    color: #dbeafe;

    font-size: 14px;
    line-height: 1.8;
}

.nearby-hero-icon {
    position: relative;
    z-index: 2;

    width: 110px;
    height: 110px;

    flex: 0 0 110px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background: rgba(255, 255, 255, .12);

    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 28px;

    font-size: 42px;
}


/* =========================================================
   LOCATION PANEL
========================================================= */

.nearby-location-panel {
    position: relative;
    z-index: 5;

    margin-top: -1px;
    padding: 30px;

    background: #ffffff;

    border: 1px solid #e6ebf3;
    border-radius: 0 0 25px 25px;

    box-shadow:
        0 18px 40px
        rgba(24, 52, 93, .08);
}

.nearby-location-heading {
    display: flex;
    align-items: center;
    gap: 16px;

    margin-bottom: 24px;
}

.nearby-location-icon {
    width: 58px;
    height: 58px;

    flex: 0 0 58px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eff6ff;

    border: 1px solid #dbeafe;
    border-radius: 17px;

    font-size: 21px;
}

.nearby-location-heading h2 {
    margin: 0 0 5px;

    color: #17376a;

    font-family: Poppins, Inter, sans-serif;
    font-size: 17px;
}

.nearby-location-heading p {
    margin: 0;

    color: #8493aa;

    font-size: 11px;
    line-height: 1.6;
}


/* =========================================================
   LOCATION METHODS
========================================================= */

.nearby-location-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;

    margin-bottom: 20px;
}

.nearby-method-card {
    padding: 20px;

    background: #f9fbff;

    border: 1px solid #e3eaf5;
    border-radius: 18px;
}

.nearby-method-title {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 7px;

    color: #17376a;

    font-size: 12px;
    font-weight: 900;
}

.nearby-method-title i {
    color: #2563eb;
}

.nearby-method-description {
    margin: 0 0 15px;

    color: #8291a7;

    font-size: 10px;
    line-height: 1.7;
}


/* =========================================================
   CURRENT LOCATION BUTTON
========================================================= */

.nearby-location-button {
    width: 100%;
    min-height: 48px;

    padding: 0 18px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    border: 0;
    border-radius: 13px;

    cursor: pointer;

    font-size: 11px;
    font-weight: 900;

    transition:
        transform .18s ease,
        box-shadow .18s ease,
        opacity .18s ease;
}

.nearby-location-button:hover {
    transform: translateY(-1px);

    box-shadow:
        0 10px 25px
        rgba(37, 99, 235, .20);
}

.nearby-location-button:disabled {
    cursor: not-allowed;
    opacity: .65;
    transform: none;
}


/* =========================================================
   MANUAL LOCATION
========================================================= */

.nearby-search-row {
    display: flex;
    gap: 10px;
}

.nearby-location-input {
    width: 100%;
    min-width: 0;
    height: 48px;

    padding: 0 15px;

    color: #17376a;
    background: #ffffff;

    border: 1px solid #dbe4f0;
    border-radius: 13px;

    outline: none;

    font-size: 11px;

    transition:
        border-color .18s ease,
        box-shadow .18s ease;
}

.nearby-location-input::placeholder {
    color: #9aa8bb;
}

.nearby-location-input:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, .08);
}

.nearby-search-button {
    min-width: 130px;
    height: 48px;

    padding: 0 17px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    color: #2563eb;
    background: #ffffff;

    border: 1px solid #bcd3ff;
    border-radius: 13px;

    cursor: pointer;

    font-size: 10px;
    font-weight: 900;

    transition: .18s ease;
}

.nearby-search-button:hover {
    color: #ffffff;
    background: #2563eb;
    border-color: #2563eb;
}

.nearby-search-button:disabled {
    cursor: not-allowed;
    opacity: .65;
}


/* =========================================================
   LOCATION STATUS
========================================================= */

.nearby-location-status {
    display: none;

    margin-top: 17px;
    padding: 13px 15px;

    align-items: flex-start;
    gap: 10px;

    border-radius: 13px;

    font-size: 10px;
    line-height: 1.6;
}

.nearby-location-status.show {
    display: flex;
}

.nearby-location-status.success {
    color: #087443;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
}

.nearby-location-status.warning {
    color: #9a5b00;
    background: #fff8e8;
    border: 1px solid #f8d98a;
}

.nearby-location-status.error {
    color: #b42318;
    background: #fff1f0;
    border: 1px solid #fecaca;
}

.nearby-location-status.loading {
    color: #1d4ed8;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}

.nearby-location-status i {
    margin-top: 2px;
}


/* =========================================================
   SELECTED LOCATION
========================================================= */

.nearby-selected-location {
    display: none;

    margin-top: 15px;
    padding: 15px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;
    border-radius: 14px;
}

.nearby-selected-location.show {
    display: flex;
    align-items: flex-start;
    gap: 11px;
}

.nearby-selected-location-icon {
    width: 35px;
    height: 35px;

    flex: 0 0 35px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eaf2ff;

    border-radius: 10px;
}

.nearby-selected-location strong {
    display: block;

    margin-bottom: 3px;

    color: #17376a;

    font-size: 10px;
}

.nearby-selected-location span {
    display: block;

    color: #718096;

    font-size: 10px;
    line-height: 1.6;
}


/* =========================================================
   FILTER
========================================================= */

.nearby-filter-area {
    margin-top: 24px;
    padding-top: 20px;

    border-top: 1px solid #edf1f6;
}

.nearby-filter-label {
    display: flex;
    align-items: center;
    gap: 7px;

    margin-bottom: 12px;

    color: #6c7d94;

    font-size: 10px;
    font-weight: 800;
}

.nearby-filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.nearby-filter {
    min-width: 72px;
    min-height: 38px;

    padding: 0 15px;

    color: #60738e;
    background: #f7f9fc;

    border: 1px solid #e2e8f0;
    border-radius: 11px;

    cursor: pointer;

    font-size: 10px;
    font-weight: 900;

    transition: .18s ease;
}

.nearby-filter:hover {
    color: #2563eb;
    border-color: #bfdbfe;
    background: #eff6ff;
}

.nearby-filter.active {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    border-color: transparent;

    box-shadow:
        0 8px 18px
        rgba(37, 99, 235, .17);
}

.nearby-privacy-note {
    margin-top: 18px;

    display: flex;
    align-items: flex-start;
    gap: 9px;

    color: #8391a5;

    font-size: 9px;
    line-height: 1.6;
}

.nearby-privacy-note i {
    margin-top: 2px;
    color: #2563eb;
}


/* =========================================================
   RESULT HEADER
========================================================= */

.nearby-result-header {
    margin-top: 38px;
    margin-bottom: 18px;

    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}

.nearby-result-header h2 {
    margin: 0 0 5px;

    color: #17376a;

    font-family: Poppins, Inter, sans-serif;
    font-size: 23px;
}

.nearby-result-header p {
    margin: 0;

    color: #8493aa;

    font-size: 10px;
    line-height: 1.6;
}

.nearby-result-count {
    padding: 8px 11px;

    color: #2563eb;
    background: #eaf2ff;

    border-radius: 10px;

    font-size: 9px;
    font-weight: 900;
}


/* =========================================================
   STORE GRID
========================================================= */

.nearby-store-grid {
    display: grid;
    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap: 18px;
}

.nearby-store-card {
    position: relative;

    padding: 22px;

    display: flex;
    flex-direction: column;

    background: #ffffff;

    border: 1px solid #e1e8f2;
    border-radius: 22px;

    box-shadow:
        0 10px 28px
        rgba(25, 55, 100, .06);

    transition:
        transform .18s ease,
        box-shadow .18s ease,
        border-color .18s ease;
}

.nearby-store-card:hover {
    transform: translateY(-3px);

    border-color: #cfe0ff;

    box-shadow:
        0 16px 35px
        rgba(25, 55, 100, .10);
}

.nearby-store-top {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.nearby-store-logo {
    width: 82px;
    height: 82px;

    flex: 0 0 82px;

    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eef4ff;

    border: 1px solid #dce7f7;
    border-radius: 20px;

    font-size: 31px;
}

.nearby-store-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nearby-store-info {
    flex: 1;
    min-width: 0;
}

.nearby-store-category {
    margin-bottom: 6px;

    color: #2563eb;

    font-size: 9px;
    font-weight: 900;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.nearby-store-name {
    margin: 0 0 9px;

    color: #17376a;

    font-family: Poppins, Inter, sans-serif;

    font-size: 17px;
    line-height: 1.35;
    font-weight: 800;
}

.nearby-distance {
    width: fit-content;

    padding: 7px 10px;

    display: inline-flex;
    align-items: center;
    gap: 6px;

    color: #087443;
    background: #ecfdf5;

    border-radius: 999px;

    font-size: 9px;
    font-weight: 900;
}

.nearby-store-address {
    margin-top: 17px;

    display: flex;
    align-items: flex-start;
    gap: 8px;

    color: #6e7f96;

    font-size: 10px;
    line-height: 1.65;
}

.nearby-store-address i {
    margin-top: 3px;
    color: #ef4444;
}

.nearby-store-description {
    margin: 14px 0 0;

    color: #7b8ba1;

    font-size: 10px;
    line-height: 1.7;
}

.nearby-store-meta {
    margin-top: 17px;

    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.nearby-meta-item {
    padding: 7px 9px;

    display: inline-flex;
    align-items: center;
    gap: 6px;

    color: #62738a;
    background: #f7f9fc;

    border: 1px solid #edf0f5;
    border-radius: 9px;

    font-size: 9px;
    font-weight: 700;
}

.nearby-meta-item i {
    color: #2563eb;
}

.nearby-delivery-tags {
    margin-top: 14px;

    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.nearby-delivery-tag {
    padding: 6px 9px;

    color: #5b6c83;
    background: #f8fafc;

    border: 1px solid #e5eaf1;
    border-radius: 999px;

    font-size: 8px;
    font-weight: 800;
}

.nearby-store-action {
    margin-top: auto;
    padding-top: 20px;
}

.nearby-visit-store {
    min-height: 42px;

    padding: 0 15px;

    display: flex;
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

    border-radius: 12px;

    text-decoration: none;

    font-size: 10px;
    font-weight: 900;

    transition: .18s ease;
}

.nearby-visit-store:hover {
    transform: translateY(-1px);

    box-shadow:
        0 9px 20px
        rgba(37, 99, 235, .18);
}


/* =========================================================
   STATES
========================================================= */

.nearby-state {
    grid-column: 1 / -1;

    min-height: 270px;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 35px;

    background: #ffffff;

    border: 1px solid #e2e8f0;
    border-radius: 22px;
}

.nearby-state-inner {
    max-width: 460px;
    text-align: center;
}

.nearby-state-icon {
    width: 64px;
    height: 64px;

    margin: 0 auto 16px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eff6ff;

    border-radius: 19px;

    font-size: 24px;
}

.nearby-state h3 {
    margin: 0 0 8px;

    color: #17376a;

    font-family: Poppins, Inter, sans-serif;
    font-size: 16px;
}

.nearby-state p {
    margin: 0;

    color: #8291a7;

    font-size: 10px;
    line-height: 1.8;
}

.nearby-spinner {
    animation: nearbySpin 1s linear infinite;
}

@keyframes nearbySpin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .nearby-location-methods {
        grid-template-columns: 1fr;
    }

    .nearby-store-grid {
        grid-template-columns: 1fr;
    }

    .nearby-hero-icon {
        display: none;
    }
}

@media (max-width: 700px) {

    .nearby-container {
        width: calc(100% - 24px);
    }

    .nearby-nav-link span {
        display: none;
    }

    .nearby-hero {
        margin-top: 18px;
        padding: 28px 22px;
        border-radius: 20px;
    }

    .nearby-location-panel {
        padding: 22px 17px;
        border-radius: 0 0 20px 20px;
    }

    .nearby-search-row {
        flex-direction: column;
    }

    .nearby-search-button {
        width: 100%;
    }

    .nearby-result-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .nearby-store-top {
        align-items: center;
    }

    .nearby-store-logo {
        width: 68px;
        height: 68px;
        flex-basis: 68px;
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
            <span>Home</span>
        </a>

        <a
            href="<?= nearbyEscape(BASE_URL) ?>catalog.php"
            class="nearby-nav-link"
        >
            <i class="fa-solid fa-bag-shopping"></i>
            <span>Shop</span>
        </a>

        <a
            href="<?= nearbyEscape(BASE_URL) ?>nearby_stores.php"
            class="nearby-nav-link active"
        >
            <i class="fa-solid fa-location-dot"></i>
            <span>Nearby Stores</span>
        </a>

        <?php if ($isLoggedIn): ?>

            <a
                href="<?= nearbyEscape(BASE_URL) ?>dashboard.php"
                class="nearby-user"
                title="<?= nearbyEscape($userName) ?>"
            >
                <?= nearbyEscape($userInitial) ?>
            </a>

        <?php else: ?>

            <a
                href="<?= nearbyEscape(BASE_URL) ?>index.php"
                class="nearby-nav-link"
            >
                <i class="fa-solid fa-user"></i>
                <span>Login</span>
            </a>

        <?php endif; ?>

    </nav>

</div>

</header>


<div class="nearby-container">


<!-- =========================================================
     HERO
========================================================== -->

<section class="nearby-hero">

    <div class="nearby-hero-content">

        <span class="nearby-eyebrow">
            Shop Near You
        </span>

        <h1>
            Discover Nearby Stores
        </h1>

        <p>
            Find approved HochipoHub vendors closest to your
            location. Use your device location or search for
            a location manually, then choose how far you want
            to explore.
        </p>

    </div>

    <div class="nearby-hero-icon">
        <i class="fa-solid fa-map-location-dot"></i>
    </div>

</section>


<!-- =========================================================
     LOCATION PANEL
========================================================== -->

<section class="nearby-location-panel">

    <div class="nearby-location-heading">

        <div class="nearby-location-icon">
            <i class="fa-solid fa-location-crosshairs"></i>
        </div>

        <div>

            <h2>
                Your Location
            </h2>

            <p>
                Choose your current device location or search
                for a location manually.
            </p>

        </div>

    </div>


    <!-- LOCATION METHODS -->

    <div class="nearby-location-methods">


        <!-- CURRENT LOCATION -->

        <div class="nearby-method-card">

            <div class="nearby-method-title">

                <i class="fa-solid fa-location-crosshairs"></i>

                Use Current Location

            </div>

            <p class="nearby-method-description">
                Let your browser detect your current location.
                This works best on a phone with GPS enabled.
            </p>

            <button
                type="button"
                id="useLocationButton"
                class="nearby-location-button"
            >

                <i class="fa-solid fa-location-crosshairs"></i>

                Use My Current Location

            </button>

        </div>


        <!-- MANUAL LOCATION -->

        <div class="nearby-method-card">

            <div class="nearby-method-title">

                <i class="fa-solid fa-magnifying-glass-location"></i>

                Search Location Manually

            </div>

            <p class="nearby-method-description">
                If your device location is inaccurate, search
                for your area, landmark or address manually.
            </p>

            <div class="nearby-search-row">

                <input
                    type="text"
                    id="manualLocationInput"
                    class="nearby-location-input"
                    placeholder="e.g. Politeknik Mersing Johor"
                    autocomplete="off"
                >

                <button
                    type="button"
                    id="searchLocationButton"
                    class="nearby-search-button"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    Find Location

                </button>

            </div>

        </div>

    </div>


    <!-- LOCATION STATUS -->

    <div
        id="locationStatus"
        class="nearby-location-status"
    >

        <i
            id="locationStatusIcon"
            class="fa-solid fa-circle-info"
        ></i>

        <span id="locationStatusText"></span>

    </div>


    <!-- SELECTED LOCATION -->

    <div
        id="selectedLocation"
        class="nearby-selected-location"
    >

        <div class="nearby-selected-location-icon">
            <i class="fa-solid fa-location-dot"></i>
        </div>

        <div>

            <strong id="selectedLocationTitle">
                Selected Location
            </strong>

            <span id="selectedLocationText"></span>

        </div>

    </div>


    <!-- DISTANCE FILTER -->

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
            Your location is used only to calculate the
            distance between you and nearby stores.
            If your device location is inaccurate, use
            Search Location Manually.
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
            Select your location to discover stores near you.
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
                Use your current location or search for
                a location manually. HochipoHub will show
                approved stores closest to your selected
                location.
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

        const selectedLocation =
            document.getElementById(
                'selectedLocation'
            );

        const selectedLocationTitle =
            document.getElementById(
                'selectedLocationTitle'
            );

        const selectedLocationText =
            document.getElementById(
                'selectedLocationText'
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

        let selectedLocationName = '';

        let locationAccuracy = null;


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

            locationStatus.className =
                'nearby-location-status show ' +
                type;

            locationStatusIcon.className =
                iconClass;

            locationStatusText.textContent =
                message;
        }


        /*
        |--------------------------------------------------------------------------
        | SHOW SELECTED LOCATION
        |--------------------------------------------------------------------------
        */

        function showSelectedLocation(
            title,
            text
        ) {

            selectedLocation.classList.add(
                'show'
            );

            selectedLocationTitle.textContent =
                title;

            selectedLocationText.textContent =
                text;
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
                'Select your location to discover stores near you.';

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
                            Use your current location or search
                            for a location manually. HochipoHub
                            will show approved stores closest to
                            your selected location.
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
            updateStoreCount(0);

            resultDescription.textContent =
                'Searching for stores near your selected location...';

            storeGrid.innerHTML = `
                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-state-icon">

                            <i
                                class="fa-solid fa-spinner nearby-spinner"
                            ></i>

                        </div>

                        <h3>
                            Finding nearby stores
                        </h3>

                        <p>
                            Please wait while HochipoHub
                            calculates the nearest approved
                            stores based on your location.
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

            const radiusText =
                selectedRadius === 'all'
                    ? 'the selected location'
                    : selectedRadius +
                      ' KM of the selected location';

            resultDescription.textContent =
                'No approved stores were found within ' +
                radiusText +
                '.';

            storeGrid.innerHTML = `
                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-state-icon">
                            <i class="fa-solid fa-store-slash"></i>
                        </div>

                        <h3>
                            No nearby stores found
                        </h3>

                        <p>
                            Try selecting a larger distance
                            such as 20 KM or ALL, or choose
                            another location.
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

            resultDescription.textContent =
                'Unable to load nearby stores.';

            storeGrid.innerHTML = `
                <div class="nearby-state">

                    <div class="nearby-state-inner">

                        <div class="nearby-state-icon">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>

                        <h3>
                            Something went wrong
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
        | DELIVERY TAGS
        |--------------------------------------------------------------------------
        */

        function createDeliveryTags(store)
        {
            const tags = [];

            const deliveryMethod =
                String(
                    store.delivery_method || ''
                );

            if (
                deliveryMethod === 'Pickup' ||
                deliveryMethod === 'Both'
            ) {
                tags.push(
                    '<span class="nearby-delivery-tag">' +
                    '<i class="fa-solid fa-bag-shopping"></i> ' +
                    'Pickup' +
                    '</span>'
                );
            }

            if (
                deliveryMethod === 'Postage' ||
                deliveryMethod === 'Both'
            ) {
                tags.push(
                    '<span class="nearby-delivery-tag">' +
                    '<i class="fa-solid fa-truck"></i> ' +
                    'Postage' +
                    '</span>'
                );
            }

            if (
                Number(
                    store.allow_vendor_delivery
                ) === 1
            ) {
                tags.push(
                    '<span class="nearby-delivery-tag">' +
                    '<i class="fa-solid fa-motorcycle"></i> ' +
                    'Vendor Delivery' +
                    '</span>'
                );
            }

            return tags.join('');
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
                    ''
                );

            const distance =
                Number(
                    store.distance_km || 0
                ).toFixed(1);

            const productCount =
                Number(
                    store.product_count || 0
                );

            const storeUrl =
                escapeHtml(
                    store.store_url || '#'
                );

            const logoUrl =
                store.logo_url
                    ? escapeHtml(
                        store.logo_url
                    )
                    : '';

            const deliveryTags =
                createDeliveryTags(store);

            let logoHtml = `
                <i class="fa-solid fa-store"></i>
            `;

            if (logoUrl !== '') {

                logoHtml = `
                    <img
                        src="${logoUrl}"
                        alt="${businessName}"
                        onerror="
                            this.style.display='none';
                            this.parentElement.innerHTML=
                            '<i class=\\'fa-solid fa-store\\'></i>';
                        "
                    >
                `;
            }

            let descriptionHtml = '';

            if (description !== '') {

                descriptionHtml = `
                    <p class="nearby-store-description">
                        ${description}
                    </p>
                `;
            }

            return `
                <article class="nearby-store-card">

                    <div class="nearby-store-top">

                        <div class="nearby-store-logo">
                            ${logoHtml}
                        </div>

                        <div class="nearby-store-info">

                            <div class="nearby-store-category">
                                ${category}
                            </div>

                            <h3 class="nearby-store-name">
                                ${businessName}
                            </h3>

                            <div class="nearby-distance">

                                <i class="fa-solid fa-location-arrow"></i>

                                ${distance} km away

                            </div>

                        </div>

                    </div>

                    <div class="nearby-store-address">

                        <i class="fa-solid fa-location-dot"></i>

                        <span>
                            ${address}
                        </span>

                    </div>

                    ${descriptionHtml}

                    <div class="nearby-store-meta">

                        <span class="nearby-meta-item">

                            <i class="fa-solid fa-box"></i>

                            ${productCount}
                            ${
                                productCount === 1
                                    ? 'Product'
                                    : 'Products'
                            }

                        </span>

                    </div>

                    <div class="nearby-delivery-tags">
                        ${deliveryTags}
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
                        'The server returned an invalid response.'
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
        | APPLY LOCATION
        |--------------------------------------------------------------------------
        */

        function applyLocation(
            latitude,
            longitude,
            locationName,
            source,
            accuracy = null
        ) {

            const latitudeNumber =
                Number(latitude);

            const longitudeNumber =
                Number(longitude);

            if (
                !Number.isFinite(latitudeNumber) ||
                !Number.isFinite(longitudeNumber) ||
                latitudeNumber < -90 ||
                latitudeNumber > 90 ||
                longitudeNumber < -180 ||
                longitudeNumber > 180
            ) {

                setLocationStatus(
                    'error',
                    'Invalid location coordinates.',
                    'fa-solid fa-circle-exclamation'
                );

                return;
            }

            customerLatitude =
                latitudeNumber.toFixed(8);

            customerLongitude =
                longitudeNumber.toFixed(8);

            selectedLocationName =
                locationName || '';

            locationAccuracy =
                accuracy !== null
                    ? Number(accuracy)
                    : null;


            /*
            |--------------------------------------------------------------------------
            | CURRENT DEVICE LOCATION
            |--------------------------------------------------------------------------
            */

            if (source === 'device') {

                let selectedText =
                    'Current device location';

                if (
                    locationAccuracy !== null &&
                    Number.isFinite(locationAccuracy)
                ) {

                    selectedText +=
                        ' • Accuracy approximately ' +
                        Math.round(
                            locationAccuracy
                        ).toLocaleString() +
                        ' metres';
                }

                showSelectedLocation(
                    'Current Location',
                    selectedText
                );


                /*
                |--------------------------------------------------------------------------
                | POOR ACCURACY WARNING
                |--------------------------------------------------------------------------
                |
                | More than 5 KM accuracy is considered too rough
                | for a nearby-store feature.
                |
                |--------------------------------------------------------------------------
                */

                if (
                    locationAccuracy !== null &&
                    locationAccuracy > 5000
                ) {

                    setLocationStatus(
                        'warning',
                        'Your device location was detected, but the accuracy is low (' +
                        Math.round(
                            locationAccuracy
                        ).toLocaleString() +
                        ' metres). Nearby store distances may be inaccurate. Please use Search Location Manually for better results.',
                        'fa-solid fa-triangle-exclamation'
                    );

                } else {

                    setLocationStatus(
                        'success',
                        'Current location detected successfully. Nearby stores are being loaded.',
                        'fa-solid fa-circle-check'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | MANUAL SEARCH LOCATION
            |--------------------------------------------------------------------------
            */

            if (source === 'manual') {

                showSelectedLocation(
                    'Selected Location',
                    locationName
                );

                setLocationStatus(
                    'success',
                    'Location set successfully to "' +
                    locationName +
                    '". Nearby stores are being loaded.',
                    'fa-solid fa-circle-check'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | LOAD STORES
            |--------------------------------------------------------------------------
            */

            loadNearbyStores();
        }


        /*
        |--------------------------------------------------------------------------
        | USE CURRENT LOCATION
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
                        Detecting Location...
                    `;


                    setLocationStatus(
                        'loading',
                        'Detecting your current location. Please allow location access when your browser asks for permission.',
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
                                position.coords.latitude;

                            const longitude =
                                position.coords.longitude;

                            const accuracy =
                                position.coords.accuracy;


                            console.log(
                                'Customer latitude:',
                                latitude
                            );

                            console.log(
                                'Customer longitude:',
                                longitude
                            );

                            console.log(
                                'Location accuracy:',
                                accuracy,
                                'metres'
                            );


                            useLocationButton.disabled =
                                false;

                            useLocationButton.innerHTML = `
                                <i class="fa-solid fa-location-crosshairs"></i>
                                Use My Current Location
                            `;


                            applyLocation(
                                latitude,
                                longitude,
                                'Current Location',
                                'device',
                                accuracy
                            );
                        },


                        /*
                        |--------------------------------------------------------------------------
                        | ERROR
                        |--------------------------------------------------------------------------
                        */

                        function (error) {

                            let message =
                                'Unable to detect your current location. Please search your location manually.';

                            if (
                                error.code ===
                                error.PERMISSION_DENIED
                            ) {

                                message =
                                    'Location permission was denied. Please allow location access or search your location manually.';

                            } else if (
                                error.code ===
                                error.POSITION_UNAVAILABLE
                            ) {

                                message =
                                    'Your current location is unavailable. Please search your location manually.';

                            } else if (
                                error.code ===
                                error.TIMEOUT
                            ) {

                                message =
                                    'Location detection timed out. Please try again or search your location manually.';
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
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH LOCATION MANUALLY
        |--------------------------------------------------------------------------
        */

        async function searchManualLocation()
        {
            const query =
                manualLocationInput.value.trim();

            if (query === '') {

                setLocationStatus(
                    'error',
                    'Please enter a location, area, landmark or address first.',
                    'fa-solid fa-circle-exclamation'
                );

                manualLocationInput.focus();

                return;
            }


            searchLocationButton.disabled =
                true;

            searchLocationButton.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Searching...
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
                | OPENSTREETMAP NOMINATIM GEOCODING
                |--------------------------------------------------------------------------
                |
                | User manually searches an address.
                | Nominatim returns latitude and longitude.
                |
                |--------------------------------------------------------------------------
                */

                const url =
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
                        url,
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
                        'Location not found. Try entering a more complete location, for example "Politeknik Mersing Johor".'
                    );
                }


                const result =
                    results[0];


                const latitude =
                    Number(
                        result.lat
                    );

                const longitude =
                    Number(
                        result.lon
                    );


                let displayName =
                    String(
                        result.display_name ||
                        query
                    );


                /*
                |--------------------------------------------------------------------------
                | SHORTEN VERY LONG DISPLAY NAME
                |--------------------------------------------------------------------------
                */

                if (
                    displayName.length > 160
                ) {

                    displayName =
                        displayName.substring(
                            0,
                            157
                        ) + '...';
                }


                applyLocation(
                    latitude,
                    longitude,
                    displayName,
                    'manual',
                    null
                );


            } catch (error) {

                console.error(
                    'Manual Location Search Error:',
                    error
                );


                setLocationStatus(
                    'error',
                    error.message ||
                    'Unable to find that location. Please try again.',
                    'fa-solid fa-circle-exclamation'
                );

            } finally {

                searchLocationButton.disabled =
                    false;

                searchLocationButton.innerHTML = `
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Find Location
                `;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | MANUAL SEARCH BUTTON
        |--------------------------------------------------------------------------
        */

        if (searchLocationButton) {

            searchLocationButton.addEventListener(
                'click',
                searchManualLocation
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PRESS ENTER TO SEARCH
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
        | RADIUS FILTER
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


                        if (
                            customerLatitude !== null &&
                            customerLongitude !== null
                        ) {

                            loadNearbyStores();

                        } else {

                            resultDescription.textContent =
                                'Select your location before using the distance filter.';

                            setLocationStatus(
                                'warning',
                                'Please use your current location or search for a location manually first.',
                                'fa-solid fa-circle-info'
                            );
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