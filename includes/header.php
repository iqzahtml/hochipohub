<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Global Header
|--------------------------------------------------------------------------
*/

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    require_once dirname(__DIR__) . '/includes/session.php';
}


/*
|--------------------------------------------------------------------------
| PAGE DEFAULTS
|--------------------------------------------------------------------------
*/

$pageTitle = $pageTitle ?? 'HochipoHub';

$pageDescription =
    $pageDescription
    ??
    'Discover and support local businesses with HochipoHub.';

$pageKeywords =
    $pageKeywords
    ??
    'HochipoHub, local marketplace, Malaysia, local vendors, products';


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER['PHP_SELF'] ?? 'index.php'
    );

?>
<!DOCTYPE html>

<html
    lang="en"
>

<head>

    <meta
        charset="UTF-8"
    >

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
        name="keywords"
        content="<?php echo htmlspecialchars(
            $pageKeywords,
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
    >

    <meta
        name="theme-color"
        content="#2563eb"
    >

    <title>
        <?php echo htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ); ?>

        <?php if ($pageTitle !== 'HochipoHub'): ?>
            | HochipoHub
        <?php endif; ?>
    </title>


    <!-- =====================================================
         GOOGLE FONTS
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
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         MAIN CSS
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
            $pageCss[$currentPage]
        )
    ):

    ?>

        <link
            rel="stylesheet"
            href="<?php echo BASE_URL; ?>css/<?php echo $pageCss[$currentPage]; ?>"
        >

    <?php endif; ?>


    <!-- =====================================================
         MODAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/modal.css"
    >


    <!-- =====================================================
         LOGIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/login.css"
    >


    <!-- =====================================================
         ADMIN CSS
    ====================================================== -->

    <?php

    $isAdminPage =
        strpos(
            $_SERVER['PHP_SELF'] ?? '',
            '/admin/'
        ) !== false;

    if ($isAdminPage):

    ?>

        <link
            rel="stylesheet"
            href="<?php echo BASE_URL; ?>css/admin.css"
        >

    <?php endif; ?>


    <!-- =====================================================
         DASHBOARD CSS
    ====================================================== -->

    <?php

    $dashboardPages = [

        'dashboard.php',
        'inventory.php',
        'commission.php',
        'profile.php',
        'order.php',
        'order_details.php',
        'review.php'

    ];

    if (
        in_array(
            $currentPage,
            $dashboardPages,
            true
        )
    ):

    ?>

        <link
            rel="stylesheet"
            href="<?php echo BASE_URL; ?>css/dashboard.css"
        >

    <?php endif; ?>


    <!-- =====================================================
         EXTRA HEAD CONTENT
    ====================================================== -->

    <?php

    if (
        isset(
            $extraHead
        )
    ) {

        echo $extraHead;
    }

    ?>

</head>


<body
    class="<?php echo htmlspecialchars(
        pathinfo(
            $currentPage,
            PATHINFO_FILENAME
        ),
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
>


<!-- =========================================================
     NAVBAR
========================================================= -->

<?php

require_once dirname(__DIR__) .
    '/includes/navbar.php';

?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main
    id="main-content"
    class="site-main"
>