<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Header
|--------------------------------------------------------------------------
| This file:
| - Loads configuration
| - Loads session
| - Loads global functions
| - Sets HTML document structure
| - Loads CSS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Load Required Files
|--------------------------------------------------------------------------
|
| dirname(__DIR__) points to:
| C:\laragon\www\hochipohub
|
| Therefore these paths remain correct even when this file is included
| from different PHP pages.
|
*/

require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/database/db.php';

require_once DIR . '/session.php';

require_once DIR . '/functions.php';


/*
|--------------------------------------------------------------------------
| Page Variables
|--------------------------------------------------------------------------
|
| Individual pages can define:
|
| $pageTitle = "Products";
|
| before including header.php.
|
*/

$pageTitle = $pageTitle ?? SITE_NAME;

$pageDescription =
    $pageDescription
    ?? 'HochipoHub - Discover, Shop & Support Local Vendors';


/*
|--------------------------------------------------------------------------
| Current Page
|--------------------------------------------------------------------------
*/

$currentPage = basename(
    $_SERVER['PHP_SELF'] ?? 'index.php'
);


/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

$flashMessage = getFlashMessage();


/*
|--------------------------------------------------------------------------
| User Information
|--------------------------------------------------------------------------
*/

$isLoggedIn = isLoggedIn();

$currentUserId =
    currentUserId();

$currentUserName =
    currentUserName();

$currentUserRole =
    currentUserRole();


/*
|--------------------------------------------------------------------------
| Cart / Wishlist Count
|--------------------------------------------------------------------------
*/

$cartCount = 0;

$wishlistCount = 0;

if ($isLoggedIn) {

    if ($currentUserRole === 'customer') {

        $cartCount =
            getCartCount(
                $currentUserId
            );

        $wishlistCount =
            getWishlistCount(
                $currentUserId
            );
    }
}

?>
<!DOCTYPE html>

<html
    lang="en"
    data-theme="blue"
>

<head>

    <!-- =========================================================
         BASIC META
    ========================================================== -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="<?php echo htmlspecialchars(
            $pageDescription,
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
    >

    <meta
        name="theme-color"
        content="#1557D6"
    >


    <!-- =========================================================
         SECURITY / REFERRER
    ========================================================== -->

    <meta
        name="referrer"
        content="strict-origin-when-cross-origin"
    >


    <!-- =========================================================
         PAGE TITLE
    ========================================================== -->

    <title>
        <?php echo htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ); ?>

        <?php if ($pageTitle !== SITE_NAME): ?>

            | <?php echo SITE_NAME; ?>

        <?php endif; ?>
    </title>


    <!-- =========================================================
         GOOGLE FONTS
    ========================================================== -->

    <link
        rel="preconnect"href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =========================================================
         ICONS
    ========================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =========================================================
         MAIN CSS
    ========================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/responsive.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/modal.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/login.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/product.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/cart.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/checkout.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/vendor.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/wishlist.css"
    >


    <!-- =========================================================
         PAGE-SPECIFIC CSS
    ========================================================== -->

    <?php if (
        isset($additionalCSS)
        &&
        is_array($additionalCSS)
    ): ?>

        <?php foreach (
            $additionalCSS
            as $cssFile
        ): ?>

            <link
                rel="stylesheet"
                href="<?php echo BASE_URL; ?>css/<?php echo htmlspecialchars(
                    $cssFile,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >

        <?php endforeach; ?>

    <?php endif; ?>


    <!-- =========================================================
         FAVICON
    ========================================================== -->

    <?php
    $logoPath =
        BASE_URL . 'image/logo.jpg';
    ?>

    <link
        rel="icon"
        type="image/jpeg"
        href="<?php echo $logoPath; ?>"
    >

</head>


<body
    class="hochipo-body page-<?php echo htmlspecialchars(
        pathinfo(
            $currentPage,
            PATHINFO_FILENAME
        ),
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
>


<!-- =============================================================
     PAGE LOADER
============================================================== -->

<div
    id="pageLoader"
    class="page-loader"
    aria-hidden="true"
>

    <div class="loader-content">

        <div class="loader-logo">

            <img
                src="<?php echo BASE_URL; ?>image/logo.jpg"
                alt="<?php echo SITE_NAME; ?> Logo"
            >

        </div>

        <div class="loader-spinner"></div>

        <p>
            Loading HochipoHub...
        </p>

    </div>

</div>


<!-- =============================================================
     ACCESSIBILITY - SKIP LINK
============================================================== -->

<a
    href="#main-content"
    class="skip-link"
>
    Skip to content
</a>


<!-- =============================================================
     FLASH MESSAGE
============================================================== -->

<?php if ($flashMessage): ?>

    <div
        class="global-flashflash-<?php echo htmlspecialchars(
            $flashMessage['type'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
        role="alert"
    >

        <div class="flash-inner">

            <div class="flash-icon">

                <?php if (
                    $flashMessage['type']
                    === 'success'
                ): ?>

                    <i class="fa-solid fa-circle-check"></i>

                <?php elseif (
                    $flashMessage['type']
                    === 'error'
                ): ?>

                    <i class="fa-solid fa-circle-xmark"></i>

                <?php elseif (
                    $flashMessage['type']
                    === 'warning'
                ): ?>

                    <i class="fa-solid fa-triangle-exclamation"></i>

                <?php else: ?>

                    <i class="fa-solid fa-circle-info"></i>

                <?php endif; ?>

            </div>


            <div class="flash-message">

                <?php echo htmlspecialchars(
                    $flashMessage['message'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>


            <button
                type="button"
                class="flash-close"
                aria-label="Close message"
                onclick="this.closest('.global-flash').remove();"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

    </div>

<?php endif; ?>


<!-- =============================================================
     MAIN WEBSITE WRAPPER
============================================================== -->

<div
    id="websiteWrapper"
    class="website-wrapper"
>


    <!-- =========================================================
         NAVBAR
    ========================================================== -->

    <?php

    /*
    |--------------------------------------------------------------------------
    | Navbar
    |--------------------------------------------------------------------------
    */

    $navbarPath =
        DIR . '/navbar.php';

    if (file_exists($navbarPath)) {

        include $navbarPath;

    }

    ?>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main
        id="main-content"
        class="main-content"
    >