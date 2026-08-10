<?php
// =========================================================
// HOCHIPOHUB - GLOBAL HEADER
// File: includes/header.php
// =========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// =========================================================
// BASE PATH
// =========================================================
//
// Root page:
//     $basePath = '';
//
// Page dalam admin/seller/auth:
//     $basePath = '../';
//
// Kalau page belum define $basePath,
// kita default kepada root.
//

if (!isset($basePath)) {
    $basePath = '';
}


// =========================================================
// PAGE TITLE
// =========================================================

if (!isset($pageTitle)) {
    $pageTitle = 'HochipoHub';
}


// =========================================================
// USER DATA
// =========================================================

$isLoggedIn = isset($_SESSION['user_id']);

$userName = $_SESSION['name'] ?? '';

$userRole = $_SESSION['role'] ?? '';


// =========================================================
// CURRENT PAGE
// =========================================================

$currentPage = basename(
    $_SERVER['PHP_SELF']
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
        content="HochipoHub - Your trusted digital marketplace."
    >

    <meta
        name="theme-color"
        content="#2563eb"
    >

    <title>
        <?php echo htmlspecialchars($pageTitle); ?>
        | HochipoHub
    </title>


    <!-- =====================================================
         FONTS
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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap"
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
         GLOBAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo $basePath; ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo $basePath; ?>css/responsive.css"
    >


    <!-- =====================================================
         PAGE SPECIFIC CSS
    ====================================================== -->

    <?php if (!empty($pageCss)): ?>

        <?php foreach ((array) $pageCss as $cssFile): ?>

            <link
                rel="stylesheet"
                href="<?php echo $basePath . 'css/' . htmlspecialchars($cssFile); ?>"
            >

        <?php endforeach; ?>

    <?php endif; ?>


    <!-- =====================================================
         EXTRA HEAD
    ====================================================== -->

    <?php if (!empty($extraHead)): ?>

        <?php echo $extraHead; ?>

    <?php endif; ?>

</head>


<body
    class="<?php echo htmlspecialchars($bodyClass ?? ''); ?>"
>


<!-- =========================================================
     GLOBAL PAGE WRAPPER
========================================================= -->

<div class="site-wrapper">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="site-header">

        <div class="header-container">


            <!-- =================================================
                 LOGO
            ================================================== -->

            <a
                href="<?php echo $basePath; ?>index.php"
                class="site-logo"
            >

                <div class="site-logo-icon">
                    H
                </div>

                <div class="site-logo-text">

                    <strong>
                        HOCHIPO
                    </strong>

                    <span>
                        HUB
                    </span>

                </div>

            </a>


            <!-- =================================================
                 DESKTOP NAVIGATION
            ================================================== -->

            <nav class="main-navigation">

                <a
                    href="<?php echo $basePath; ?>index.php"
                    class="<?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>"
                >
                    Home
                </a>


                <a
                    href="<?php echo $basePath; ?>catalog.php"
                    class="<?php echo ($currentPage === 'catalog.php') ? 'active' : ''; ?>"
                >
                    Products
                </a>


                <a
                    href="<?php echo $basePath; ?>category.php"
                    class="<?php echo ($currentPage === 'category.php') ? 'active' : ''; ?>"
                >
                    Categories
                </a>


                <a
                    href="<?php echo $basePath; ?>vendor.php"
                    class="<?php echo ($currentPage === 'vendor.php') ? 'active' : ''; ?>"
                >
                    Vendors
                </a>


                <a
                    href="<?php echo $basePath; ?>contact.php"
                    class="<?php echo ($currentPage === 'contact.php') ? 'active' : ''; ?>"
                >
                    Contact
                </a>

            </nav>


            <!-- =================================================
                 HEADER ACTIONS
            ================================================== -->

            <div class="header-actions">


                <!-- Search -->
                <a
                    href="<?php echo $basePath; ?>search.php"
                    class="header-action"
                    aria-label="Search"
                    title="Search"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                </a>


                <?php if ($isLoggedIn): ?>


                    <!-- Wishlist -->
                    <a
                        href="<?php echo $basePath; ?>wishlist.php"
                        class="header-action"
                        aria-label="Wishlist"
                        title="Wishlist"
                    >

                        <i class="fa-regular fa-heart"></i>

                    </a>


                    <!-- Cart -->
                    <a
                        href="<?php echo $basePath; ?>cart.php"
                        class="header-action"
                        aria-label="Cart"
                        title="Cart"
                    >

                        <i class="fa-solid fa-cart-shopping"></i>

                    </a>


                    <!-- Profile -->
                    <a
                        href="<?php echo $basePath; ?>profile.php"
                        class="header-user"
                    >

                        <div class="header-user-avatar">

                            <i class="fa-solid fa-user"></i>

                        </div>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $userName ?: 'Account'
                            );
                            ?>
                        </span>

                    </a>


                <?php else: ?>


                    <!-- Login -->
                    <a
                        href="<?php echo $basePath; ?>index.php"
                        class="header-login"
                    >
                        Login
                    </a>


                    <!-- Register -->
                    <a
                        href="<?php echo $basePath; ?>index.php"
                        class="header-register"
                    >
                        Register
                    </a>


                <?php endif; ?>


                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="mobile-menu-toggle"
                    id="mobileMenuToggle"
                    aria-label="Open Menu"
                >

                    <i class="fa-solid fa-bars"></i>

                </button>

            </div>

        </div>


        <!-- =====================================================
             MOBILE NAVIGATION
        ====================================================== -->

        <div
            class="mobile-navigation"
            id="mobileNavigation"
        >

            <a
                href="<?php echo $basePath; ?>index.php"
            >
                <i class="fa-solid fa-house"></i>
                Home
            </a>


            <a
                href="<?php echo $basePath; ?>catalog.php"
            >
                <i class="fa-solid fa-store"></i>
                Products
            </a>


            <a
                href="<?php echo $basePath; ?>category.php"
            >
                <i class="fa-solid fa-layer-group"></i>
                Categories
            </a>


            <a
                href="<?php echo $basePath; ?>vendor.php"
            >
                <i class="fa-solid fa-shop"></i>
                Vendors
            </a>


            <a
                href="<?php echo $basePath; ?>cart.php"
            >
                <i class="fa-solid fa-cart-shopping"></i>
                Cart
            </a>


            <a
                href="<?php echo $basePath; ?>wishlist.php"
            >
                <i class="fa-regular fa-heart"></i>
                Wishlist
            </a>


            <a
                href="<?php echo $basePath; ?>contact.php"
            >
                <i class="fa-solid fa-envelope"></i>
                Contact
            </a>


            <?php if ($isLoggedIn): ?>

                <a
                    href="<?php echo $basePath; ?>profile.php"
                >
                    <i class="fa-solid fa-user"></i>
                    My Profile
                </a>


                <a
                    href="<?php echo $basePath; ?>auth/logout.php"
                    class="mobile-logout"
                >
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>

            <?php endif; ?>

        </div>

    </header>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="site-main">