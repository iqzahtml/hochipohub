<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - NEARBY STORES
|--------------------------------------------------------------------------
| File:
| nearby_stores.php
|
| Function:
| - Use original HochipoHub customer navbar
| - Customer can use current browser location
| - Customer can search/select location manually
| - Send latitude / longitude to AJAX
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
| NAVBAR VALUES
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
| CUSTOMER CART / WISHLIST COUNT
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
    min-height: calc(100vh - 96px);

    padding-top: 1px;
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
    padding: 24px;

    background: #ffffff;

    border:
        1px solid #e2e8f2;

    border-radius: 20px;

    box-shadow:
        0 16px 38px
        rgba(28, 58, 103, .09);
}

.nearby-location-heading {
    margin-bottom: 18px;
}

.nearby-location-heading h2 {
    margin: 0 0 5px;

    color: #17365f;

    font-size: 15px;
}

.nearby-location-heading p {
    margin: 0;

    color: #8a98ab;

    font-size: 9px;
    line-height: 1.6;
}


/* =========================================================
   LOCATION OPTIONS
========================================================= */

.nearby-location-options {
    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 14px;
}

.nearby-location-option {
    min-width: 0;

    padding: 18px;

    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 16px;
}

.nearby-location-option-top {
    margin-bottom: 14px;

    display: flex;
    align-items: center;

    gap: 11px;
}

.nearby-control-icon {
    width: 43px;
    height: 43px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: #2563eb;
    background: #edf5ff;

    border:
        1px solid #dbeafe;

    border-radius: 12px;

    font-size: 15px;
}

.nearby-location-option-title {
    min-width: 0;
}

.nearby-location-option-title strong {
    display: block;

    margin-bottom: 3px;

    color: #17365f;

    font-size: 11px;
}

.nearby-location-option-title span {
    display: block;

    color: #8a98ab;

    font-size: 8px;
    line-height: 1.5;
}


/* =========================================================
   CURRENT LOCATION BUTTON
========================================================= */

.nearby-location-button {
    min-height: 44px;
    width: 100%;

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
        rgba(37, 99, 235, .16);

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
   MANUAL LOCATION
========================================================= */

.nearby-manual-search {
    display: flex;
    align-items: stretch;

    gap: 8px;
}

.nearby-manual-input-wrap {
    min-width: 0;
    flex: 1;

    position: relative;
}

.nearby-manual-input-wrap i {
    position: absolute;

    left: 14px;
    top: 50%;

    transform:
        translateY(-50%);

    color: #8292aa;

    font-size: 12px;

    pointer-events: none;
}

.nearby-manual-input {
    width: 100%;
    height: 44px;

    padding:
        0 13px
        0 37px;

    color: #253b5e;
    background: #ffffff;

    border:
        1px solid #dbe3ee;

    border-radius: 12px;

    outline: none;

    font-family: inherit;
    font-size: 9px;
    font-weight: 600;

    transition:
        border-color .18s ease,
        box-shadow .18s ease;
}

.nearby-manual-input::placeholder {
    color: #a1adbd;
}

.nearby-manual-input:focus {
    border-color: #93baf7;

    box-shadow:
        0 0 0 4px
        rgba(37, 99, 235, .07);
}

.nearby-search-location-button {
    min-height: 44px;

    padding: 0 15px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 6px;

    flex-shrink: 0;

    color: #2563eb;
    background: #edf5ff;

    border:
        1px solid #cfe1ff;

    border-radius: 12px;

    font-family: inherit;
    font-size: 9px;
    font-weight: 850;

    cursor: pointer;

    transition: .18s ease;
}

.nearby-search-location-button:hover {
    color: #ffffff;
    background: #2563eb;

    border-color: #2563eb;
}

.nearby-search-location-button:disabled {
    opacity: .65;

    cursor: wait;
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

.nearby-location-status.warning {
    color: #92400e;
    background: #fffbeb;

    border-color: #fde68a;
}


/* =========================================================
   SELECTED LOCATION
========================================================= */

.nearby-selected-location {
    margin-top: 12px;
    padding: 13px 14px;

    display: none;
    align-items: flex-start;

    gap: 10px;

    color: #38516f;
    background: #f7faff;

    border:
        1px solid #dce8fa;

    border-radius: 12px;
}

.nearby-selected-location.show {
    display: flex;
}

.nearby-selected-location-icon {
    width: 30px;
    height: 30px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: #2563eb;
    background: #e8f1ff;

    border-radius: 9px;

    font-size: 11px;
}

.nearby-selected-location-content {
    min-width: 0;
}

.nearby-selected-location-content small {
    display: block;

    margin-bottom: 3px;

    color: #8a98ab;

    font-size: 7px;
    font-weight: 800;

    letter-spacing: .4px;
    text-transform: uppercase;
}

.nearby-selected-location-content strong {
    display: block;

    color: #29476f;

    font-size: 9px;
    line-height: 1.55;
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
   PRIVACY NOTE
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

    .nearby-location-options {
        grid-template-columns: 1fr;
    }
}


@media (max-width: 620px) {

    .nearby-container {
        width: calc(100% - 28px);
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

    .nearby-manual-search {
        flex-direction: column;
    }

    .nearby-search-location-button {
        width: 100%;
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


<!-- =========================================================
     ORIGINAL CUSTOMER NAVBAR
========================================================== -->

<?php
require_once __DIR__ . '/includes/navbar.php';
?>


<!-- =========================================================
     PAGE
========================================================== -->

<main class="nearby-main">

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
        Discover HochipoHub sellers based on your location.
        Choose your current location or search for a location
        manually, then browse stores arranged from nearest
        to furthest.
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


<div class="nearby-location-heading">

    <h2>
        Choose Your Location
    </h2>

    <p>
        Use your device location or search for a location manually.
    </p>

</div>


<div class="nearby-location-options">


<!-- =========================================================
     CURRENT LOCATION
========================================================== -->

<div class="nearby-location-option">

    <div class="nearby-location-option-top">

        <div class="nearby-control-icon">

            <i class="fa-solid fa-location-crosshairs"></i>

        </div>

        <div class="nearby-location-option-title">

            <strong>
                Use Current Location
            </strong>

            <span>
                Detect your location using your browser.
            </span>

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


<!-- =========================================================
     MANUAL LOCATION
========================================================== -->

<div class="nearby-location-option">

    <div class="nearby-location-option-top">

        <div class="nearby-control-icon">

            <i class="fa-solid fa-magnifying-glass-location"></i>

        </div>

        <div class="nearby-location-option-title">

            <strong>
                Search Location Manually
            </strong>

            <span>
                Search a place, area, campus or address.
            </span>

        </div>

    </div>


    <div class="nearby-manual-search">

        <div class="nearby-manual-input-wrap">

            <i class="fa-solid fa-location-dot"></i>

            <input
                type="text"
                id="manualLocationInput"
                class="nearby-manual-input"
                placeholder="Example: Politeknik Mersing Johor"
                autocomplete="off"
            >

        </div>


        <button
            type="button"
            id="searchLocationButton"
            class="nearby-search-location-button"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            Search

        </button>

    </div>

</div>


</div>


<!-- =========================================================
     STATUS
========================================================== -->

<div
    id="locationStatus"
    class="nearby-location-status"
>

    <i
        id="locationStatusIcon"
        class="fa-solid fa-circle-info"
    ></i>

    <span id="locationStatusText">

        Choose your location to begin finding nearby stores.

    </span>

</div>


<!-- =========================================================
     SELECTED LOCATION
========================================================== -->

<div
    id="selectedLocation"
    class="nearby-selected-location"
>

    <div class="nearby-selected-location-icon">

        <i class="fa-solid fa-location-dot"></i>

    </div>


    <div class="nearby-selected-location-content">

        <small>
            SELECTED LOCATION
        </small>

        <strong id="selectedLocationText">
            -
        </strong>

    </div>

</div>


<!-- =========================================================
     FILTER
========================================================== -->

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


<!-- =========================================================
     PRIVACY NOTE
========================================================== -->

<div class="nearby-privacy-note">

    <i class="fa-solid fa-shield-halved"></i>

    <span>
        Your location is used to calculate the distance
        between you and stores. Some computers and laptops
        may provide an approximate location instead of an
        exact GPS location. You can use manual search if
        your detected location is inaccurate.
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
        Choose your location to discover stores near you.
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
            Use your current location or search for a location
            manually. HochipoHub will show approved stores
            closest to your selected location.
        </p>

    </div>

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


        const selectedLocation =
            document.getElementById(
                'selectedLocation'
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
        | STATUS
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
        | SELECTED LOCATION
        |--------------------------------------------------------------------------
        */

        function showSelectedLocation(
            name
        ) {

            selectedLocationName =
                String(
                    name || ''
                ).trim();


            if (
                selectedLocationName === ''
            ) {

                selectedLocation.classList.remove(
                    'show'
                );

                return;
            }


            selectedLocationText.textContent =
                selectedLocationName;


            selectedLocation.classList.add(
                'show'
            );
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


            showSelectedLocation(
                locationName
            );


            setLocationStatus(
                statusType || 'success',
                statusMessage ||
                    'Location selected successfully. Nearby stores are being loaded.',
                statusType === 'warning'
                    ? 'fa-solid fa-triangle-exclamation'
                    : 'fa-solid fa-circle-check'
            );


            loadNearbyStores();
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
                'Choose your location to discover stores near you.';


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
                            will show approved stores closest
                            to your selected location.
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
                            your selected location and
                            available stores.
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


            let distanceText = 'Distance unavailable';


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
                            'Geolocation is not supported by this browser. Please use manual location search.',
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

                                Update My Location
                            `;


                            /*
                            |--------------------------------------------------------------------------
                            | POOR ACCURACY WARNING
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
                                    'Your browser detected an approximate location with about ' +
                                        approximateKm +
                                        ' KM accuracy. The distance shown may be inaccurate. Use manual location search for a more accurate result.',
                                    'warning'
                                );


                                return;
                            }


                            applyLocation(
                                latitude,
                                longitude,
                                'Current device location',
                                'Location detected successfully. Nearby stores are being loaded.',
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
                                    'Location permission was denied. Please allow location access or use manual location search.';

                            } else if (
                                error.code === 2
                            ) {

                                message =
                                    'Your current location is unavailable. Please use manual location search.';

                            } else if (
                                error.code === 3
                            ) {

                                message =
                                    'Location request timed out. Please try again or use manual location search.';
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
                        'Location not found. Try entering a more specific location, for example "Politeknik Mersing Johor".'
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
                    displayName.length > 160
                ) {

                    displayName =
                        displayName.substring(
                            0,
                            157
                        ) +
                        '...';
                }


                applyLocation(
                    latitude,
                    longitude,
                    displayName,
                    'Location selected successfully. Nearby stores are being loaded.',
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
        | MANUAL SEARCH BUTTON
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

        showInitialState();

    }
);

</script>


</body>

</html>