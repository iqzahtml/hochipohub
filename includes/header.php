<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL HEADER
|--------------------------------------------------------------------------
| File:
| includes/header.php
|
| Purpose:
| - Load database connection
| - Load session
| - Load global functions
| - Set page title
| - Load global CSS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| LOAD CONFIG
|--------------------------------------------------------------------------
*/

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/*
|--------------------------------------------------------------------------
| LOAD DATABASE
|--------------------------------------------------------------------------
*/

if (file_exists(__DIR__ . '/../database/db.php')) {
    require_once __DIR__ . '/../database/db.php';
}

/*
|--------------------------------------------------------------------------
| LOAD SESSION
|--------------------------------------------------------------------------
*/

if (file_exists(__DIR__ . '/session.php')) {
    require_once __DIR__ . '/session.php';
}

/*
|--------------------------------------------------------------------------
| LOAD FUNCTIONS
|--------------------------------------------------------------------------
*/

if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

if (!isset($pageTitle) || empty($pageTitle)) {
    $pageTitle = SITE_NAME ?? 'HochipoHub';
}

/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');

/*
|--------------------------------------------------------------------------
| USER DATA
|--------------------------------------------------------------------------
*/

$isLoggedIn = function_exists('isLoggedIn')
    ? isLoggedIn()
    : isset($_SESSION['user_id']);

$userName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? '';

$userRole = $_SESSION['user_role']
    ?? $_SESSION['role']
    ?? '';

$userId = $_SESSION['user_id'] ?? null;

/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/

$cartCount = 0;

if ($isLoggedIn && $userId && isset($db) && $db instanceof PDO) {

    try {

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM cart
            WHERE customer_id = ?
        ");

        $stmt->execute([
            (int) $userId
        ]);

        $cartCount = (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        $cartCount = 0;

    }
}

/*
|--------------------------------------------------------------------------
| WISHLIST COUNT
|--------------------------------------------------------------------------
*/

$wishlistCount = 0;

if ($isLoggedIn && $userId && isset($db) && $db instanceof PDO) {

    try {

        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM wishlist
            WHERE user_id = ?
        ");

        $stmt->execute([
            (int) $userId
        ]);

        $wishlistCount = (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        $wishlistCount = 0;

    }
}

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
        content="HochipoHub - Local marketplace for products and vendors."
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        | HochipoHub
    </title>

    <!-- Google Font -->
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
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="<?= defined('BASE_URL')
            ? BASE_URL . 'css/style.css'
            : 'css/style.css' ?>"
    >

    <!-- Page Specific CSS -->
    <?php if (isset($pageCSS) && is_array($pageCSS)): ?>

        <?php foreach ($pageCSS as $css): ?>

            <link
                rel="stylesheet"
                href="<?= htmlspecialchars(
                    $css,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

        <?php endforeach; ?>

    <?php endif; ?>

</head>

<body>

    <!-- Page Wrapper -->

    <div id="page-wrapper">

        <?php
        /*
        |--------------------------------------------------------------------------
        | NAVBAR
        |--------------------------------------------------------------------------
        |
        | Individual pages can load navbar manually.
        | Therefore header.php DOES NOT automatically include navbar.
        |
        */
        ?>