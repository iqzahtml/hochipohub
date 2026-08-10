<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL HEADER
|--------------------------------------------------------------------------
| File:
| includes/header.php
|
| Purpose:
| - Load configuration
| - Start session
| - Load database connection
| - Load helper functions
| - Detect current page
| - Prepare logged-in user information
| - Prepare cart & wishlist count
| - Load global CSS
| - Load modal JavaScript
| - Include navbar
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| PREVENT DIRECT ACCESS
|--------------------------------------------------------------------------
*/
if (!defined('HOCHIPOHUB')) {
    define('HOCHIPOHUB', true);
}
/*
|--------------------------------------------------------------------------
| LOAD CONFIG
|--------------------------------------------------------------------------
*/
$configPath = dirname(__DIR__) . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}
/*
|--------------------------------------------------------------------------
| BASE URL FALLBACK
|--------------------------------------------------------------------------
*/
if (!defined('BASE_URL')) {
    define(
        'BASE_URL',
        '/hochipohub/'
    );
}
/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Expected file:
| database/db.php
|--------------------------------------------------------------------------
*/
$dbPath = dirname(__DIR__) . '/database/db.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
}
/*
|--------------------------------------------------------------------------
| LOAD GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
*/
$functionsPath = __DIR__ . '/functions.php';
if (file_exists($functionsPath)) {
    require_once $functionsPath;
}
/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/
$currentPage = basename(
    $_SERVER['PHP_SELF'] ?? ''
);
/*
|--------------------------------------------------------------------------
| LOGIN STATUS
|--------------------------------------------------------------------------
*/
$isLoggedIn = false;
if (
    isset($_SESSION['user_id']) &&
    !empty($_SESSION['user_id'])
) {
    $isLoggedIn = true;
}
/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/
$userId =
    $_SESSION['user_id']
    ?? null;
$userName =
    $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? '';
$userRole =
    $_SESSION['role']
    ?? $_SESSION['user_role']
    ?? 'customer';
/*
|--------------------------------------------------------------------------
| NORMALIZE USER ROLE
|--------------------------------------------------------------------------
*/
$userRole = strtolower(
    trim(
        (string) $userRole
    )
);
/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/
$cartCount = 0;
if (
    $isLoggedIn &&
    $userRole === 'customer'
) {
    /*
    |--------------------------------------------------------------------------
    | SESSION CART COUNT
    |--------------------------------------------------------------------------
    */
    if (
        isset(
            $_SESSION['cart_count']
        )
    ) {
        $cartCount =
            (int) $_SESSION['cart_count'];
    } else {
        /*
        |--------------------------------------------------------------------------
        | PDO CART COUNT
        |--------------------------------------------------------------------------
        |
        | Use the same PDO structure as functions.php.
        |--------------------------------------------------------------------------
        */
        try {
            if (
                function_exists('getCartCount') &&
                isset($db) &&
                $db instanceof PDO &&
                $userId
            ) {
                $cartCount =
                    getCartCount(
                        $db,
                        $userId
                    );
            }
        } catch (Throwable $e) {
            $cartCount = 0;
        }
    }
}
/*
|--------------------------------------------------------------------------
| WISHLIST COUNT
|--------------------------------------------------------------------------
*/
$wishlistCount = 0;
if (
    $isLoggedIn &&
    $userRole === 'customer'
) {
    /*
    |--------------------------------------------------------------------------
    | SESSION WISHLIST COUNT
    |--------------------------------------------------------------------------
    */
    if (
        isset(
            $_SESSION['wishlist_count']
        )
    ) {
        $wishlistCount =
            (int) $_SESSION['wishlist_count'];
    } else {
        /*
        |--------------------------------------------------------------------------
        | PDO WISHLIST COUNT
        |--------------------------------------------------------------------------
        */
        try {
            if (
                function_exists('getWishlistCount') &&
                isset($db) &&
                $db instanceof PDO &&
                $userId
            ) {
                $wishlistCount =
                    getWishlistCount(
                        $db,
                        $userId
                    );
            }
        } catch (Throwable $e) {
            $wishlistCount = 0;
        }
    }
}
/*
|--------------------------------------------------------------------------
| HTML TITLE
|--------------------------------------------------------------------------
*/
$pageTitle =
    $pageTitle
    ?? 'HochipoHub';
/*
|--------------------------------------------------------------------------
| EXTRA CSS
|--------------------------------------------------------------------------
|
| Individual pages can set:
|
| $extraCSS = ['product.css'];
|--------------------------------------------------------------------------
*/
if (!isset($extraCSS)) {
    $extraCSS = [];
}
if (!is_array($extraCSS)) {
    $extraCSS = [$extraCSS];
}
/*
|--------------------------------------------------------------------------
| EXTRA JS
|--------------------------------------------------------------------------
|
| Individual pages can add:
|
| $extraJS = ['product.js'];
|--------------------------------------------------------------------------
*/
if (!isset($extraJS)) {
    $extraJS = [];
}
if (!is_array($extraJS)) {
    $extraJS = [$extraJS];
}
/*
|--------------------------------------------------------------------------
| DEFAULT GLOBAL JAVASCRIPT
|--------------------------------------------------------------------------
|
| IMPORTANT:
| modal.js is required for:
|
| - Login button
| - Register button
| - Modal switching
| - Close button
| - ESC
| - Click outside modal
| - Password visibility
|
|--------------------------------------------------------------------------
*/
$globalJS = [
    'modal.js'
];
/*
|--------------------------------------------------------------------------
| COMBINE GLOBAL JS + PAGE JS
|--------------------------------------------------------------------------
*/
$allJS = array_merge(
    $globalJS,
    $extraJS
);
/*
|--------------------------------------------------------------------------
| REMOVE DUPLICATE JS
|--------------------------------------------------------------------------
*/
$allJS = array_unique(
    $allJS
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
    <meta
        name="description"
        content="HochipoHub - Marketplace"
    >
    <meta
        name="theme-color"
        content="#2563eb"
    >
    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        | HochipoHub
    </title>
    <!-- =====================================================
         GOOGLE FONT
         ===================================================== -->
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
    <!-- =====================================================
         GLOBAL CSS
         ===================================================== -->
    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            rtrim(BASE_URL, '/') .
            '/css/style.css',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >
    <!-- =====================================================
         RESPONSIVE CSS
         ===================================================== -->
    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            rtrim(BASE_URL, '/') .
            '/css/responsive.css',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >
    <!-- =====================================================
         PAGE-SPECIFIC CSS
         ===================================================== -->
    <?php foreach ($extraCSS as $cssFile): ?>
        <?php if (!empty($cssFile)): ?>
            <link
                rel="stylesheet"
                href="<?= htmlspecialchars(
                    rtrim(BASE_URL, '/') .
                    '/css/' .
                    ltrim($cssFile, '/'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
        <?php endif; ?>
    <?php endforeach; ?>
    <!-- =====================================================
         GLOBAL JAVASCRIPT
         ===================================================== -->
    <?php foreach ($allJS as $jsFile): ?>
        <?php if (!empty($jsFile)): ?>
            <script
                src="<?= htmlspecialchars(
                    rtrim(BASE_URL, '/') .
                    '/js/' .
                    ltrim($jsFile, '/'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                defer
            ></script>
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body>
    <!-- =====================================================
         NAVBAR
         ===================================================== -->
    <?php
    $navbarPath =
        __DIR__ . '/navbar.php';
    if (
        file_exists(
            $navbarPath
        )
    ) {
        require_once $navbarPath;
    }
    ?>
    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->
    <main
        id="main-content"
        class="site-main"
    >