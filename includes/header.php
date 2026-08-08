<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Global Header
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/database/db.php';

require_once dirname(__DIR__) . '/includes/session.php';

require_once dirname(__DIR__) . '/includes/functions.php';


$pageTitle =
    $pageTitle ?? SITE_NAME;

$pageDescription =
    $pageDescription
    ?? 'Discover, shop and support local vendors with HochipoHub.';


$currentPage =
    basename($_SERVER['PHP_SELF'] ?? 'index.php');


$flashMessage =
    function_exists('getFlashMessage')
        ? getFlashMessage()
        : null;


$isLoggedIn =
    isLoggedIn();


$currentUserId =
    currentUserId();


$currentUserName =
    currentUserName();


$currentUserRole =
    currentUserRole();


$cartCount = 0;

$wishlistCount = 0;


if (
    $isLoggedIn
    &&
    $currentUserRole === 'customer'
    &&
    isset($pdo)
) {

    if (function_exists('getCartCount')) {

        $cartCount =
            getCartCount(
                $pdo,
                $currentUserId
            );

    }

    if (function_exists('getWishlistCount')) {

        $wishlistCount =
            getWishlistCount(
                $pdo,
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


    <title>

        <?php echo htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ); ?>

        <?php if ($pageTitle !== SITE_NAME): ?>

            | <?php echo htmlspecialchars(
                SITE_NAME,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        <?php endif; ?>

    </title>


    <!-- GOOGLE FONT -->

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


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- MAIN CSS -->

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
        href="<?php echo BASE_URL; ?>css/wishlist.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/admin.css"
    >


    <?php if (
        isset($additionalCSS)
        &&
        is_array($additionalCSS)
    ): ?>

        <?php foreach ($additionalCSS as $cssFile): ?>

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


    <!-- FAVICON -->

    <link
        rel="icon"
        type="image/jpeg"
        href="<?php echo BASE_URL; ?>image/logo.jpg"
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


<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>


<?php if ($flashMessage): ?>

    <div
        class="global-flash global-flash-<?php echo htmlspecialchars(
            $flashMessage['type'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
    >

        <div class="flash-inner">

            <span class="flash-message">

                <?php echo htmlspecialchars(
                    $flashMessage['message'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </span>

            <button
                type="button"
                class="flash-close"
                onclick="this.closest('.global-flash').remove();"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

    </div>

<?php endif; ?>


<main
    id="main-content"
    class="main-content"
>