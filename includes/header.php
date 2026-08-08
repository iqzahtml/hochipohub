<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Global Header
|--------------------------------------------------------------------------
*/

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/session.php';
}


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle =
    $pageTitle
    ?? 'HochipoHub';

$pageDescription =
    $pageDescription
    ?? 'Discover local products and support local businesses.';

$bodyClass =
    $bodyClass
    ?? '';

?>


<!DOCTYPE html>

<html
    lang="en"
>

<head>


    <!-- =====================================================
         BASIC META
    ====================================================== -->

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
        content="#2563eb"
    >


    <!-- =====================================================
         TITLE
    ====================================================== -->

    <title>

        <?php echo htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ); ?>

        | HochipoHub

    </title>


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

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
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWixLJ8tW0Yp8qJk0j9JfF7qL9jX8j5m7K3m8sYQ2mJ6J7Y7dY7k2fF5J0kY7j3A8Q=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >


    <!-- =====================================================
         GLOBAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/responsive.css"
    >


    <!-- =====================================================
         PAGE-SPECIFIC CSS
    ====================================================== -->

    <?php

    $pageCss = [

        'cart.php'
            => 'cart.css',

        'checkout.php'
            => 'checkout.css',

        'dashboard.php'
            => 'dashboard.css',

        'product.php'
            => 'product.css',

        'product_details.php'
            => 'product.css',

        'vendor.php'
            => 'vendor.css',

        'wishlist.php'
            => 'wishlist.css'

    ];


    if (
        isset(
            $pageCss[
                basename(
                    $_SERVER['PHP_SELF']
                )
            ]
        )
    ):

    ?>

        <link
            rel="stylesheet"
            href="<?php echo BASE_URL; ?>css/<?php echo $pageCss[
                basename(
                    $_SERVER['PHP_SELF']
                )
            ]; ?>"
        >

    <?php endif; ?>


</head>


<body
    class="<?php echo htmlspecialchars(
        $bodyClass,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
>


<!-- =========================================================
     PAGE LOADER
========================================================= -->

<div
    class="page-loader"
    id="pageLoader"
>

    <div class="loader-content">

        <div class="loader-logo">

            <i class="fa-solid fa-bolt"></i>

        </div>

        <div class="loader-spinner"></div>

        <span>
            Loading HochipoHub...
        </span>

    </div>

</div>


<!-- =========================================================
     NAVBAR
========================================================= -->

<?php

$navbarPath =
    __DIR__ . '/navbar.php';

if (
    file_exists(
        $navbarPath
    )
) {

    require $navbarPath;
}

?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main
    id="mainContent"
    class="main-content"
>