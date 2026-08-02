<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Main Navigation Bar
|--------------------------------------------------------------------------
*/

if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once DIR . '/session.php';
}

if (!function_exists('getCartCount')) {
    require_once DIR . '/functions.php';
}


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
| User Information
|--------------------------------------------------------------------------
*/

$navLoggedIn = isLoggedIn();

$navUserId = currentUserId();

$navUserName = currentUserName();

$navUserRole = currentUserRole();


/*
|--------------------------------------------------------------------------
| Cart / Wishlist
|--------------------------------------------------------------------------
*/

$navCartCount = 0;

$navWishlistCount = 0;

if (
    $navLoggedIn &&
    $navUserRole === 'customer'
) {

    $navCartCount =
        getCartCount($navUserId);

    $navWishlistCount =
        getWishlistCount($navUserId);
}


/*
|--------------------------------------------------------------------------
| Active Navigation Helper
|--------------------------------------------------------------------------
*/

function navActive($page)
{
    global $currentPage;

    if ($currentPage === $page) {
        return 'active';
    }

    return '';
}

?>

<!-- =============================================================
     HOCHIPOHUB NAVBAR
============================================================== -->

<header
    class="main-navbar"
    id="mainNavbar"
>

    <!-- =========================================================
         TOP NAVBAR
    ========================================================== -->

    <div class="navbar-container">


        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a
            href="<?php echo BASE_URL; ?>index.php"
            class="navbar-brand"
            aria-label="HochipoHub Home"
        >

            <div class="brand-logo-wrapper">

                <img
                    src="<?php echo BASE_URL; ?>image/logo.jpg"
                    alt="HochipoHub Logo"
                    class="brand-logo"
                >

            </div>


            <div class="brand-text">

                <span class="brand-name">
                    Hochipo<span>Hub</span>
                </span>

                <small class="brand-tagline">
                    Discover. Shop. Support.
                </small>

            </div>

        </a>


        <!-- =====================================================
             DESKTOP SEARCH
        ====================================================== -->

        <div
            class="navbar-search"
            id="navbarSearch"
        >

            <form
                action="<?php echo BASE_URL; ?>search.php"
                method="GET"
                class="navbar-search-form"
                id="navbarSearchForm"
                autocomplete="off"
            >

                <div class="search-icon">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>


                <input
                    type="search"
                    name="q"
                    id="navbarSearchInput"
                    class="navbar-search-input"
                    placeholder="Search products, vendors, categories..."
                    aria-label="Search HochipoHub"
                    maxlength="100"
                >


                <button
                    type="submit"
                    class="search-submit"aria-label="Search"
                >

                    <span>
                        Search
                    </span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>


            <!-- AJAX SEARCH RESULTS -->

            <div
                class="navbar-search-results"
                id="navbarSearchResults"
                hidden
            >

                <div class="search-results-header">

                    <span>
                        Quick Results
                    </span>

                    <i class="fa-solid fa-bolt"></i>

                </div>


                <div
                    class="search-results-content"
                    id="searchResultsContent"
                >
                </div>


                <a
                    href="<?php echo BASE_URL; ?>search.php"
                    class="view-all-results"
                    id="viewAllSearchResults"
                >

                    View all results

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>

        </div>


        <!-- =====================================================
             NAVBAR ACTIONS
        ====================================================== -->

        <div class="navbar-actions">


            <!-- =================================================
                 MOBILE SEARCH BUTTON
            ================================================== -->

            <button
                type="button"
                class="navbar-icon-btn mobile-search-toggle"
                id="mobileSearchToggle"
                aria-label="Open search"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

            </button>


            <?php if (
                $navLoggedIn &&
                $navUserRole === 'customer'
            ): ?>


                <!-- =============================================
                     WISHLIST
                ============================================== -->

                <a
                    href="<?php echo BASE_URL; ?>wishlist.php"
                    class="navbar-icon-btn wishlist-btn"
                    aria-label="Wishlist"
                    title="Wishlist"
                >

                    <i class="fa-regular fa-heart"></i>


                    <?php if (
                        $navWishlistCount > 0
                    ): ?>

                        <span
                            class="navbar-badge wishlist-badge"
                            id="wishlistCount"
                        >

                            <?php echo $navWishlistCount; ?>

                        </span>

                    <?php endif; ?>

                </a>


                <!-- =============================================
                     CART
                ============================================== -->

                <a
                    href="<?php echo BASE_URL; ?>cart.php"
                    class="navbar-icon-btn cart-btn"
                    aria-label="Shopping cart"
                    title="Shopping Cart"
                >

                    <i class="fa-solid fa-bag-shopping"></i>


                    <span
                        class="navbar-badge cart-badge <?php echo $navCartCount <= 0 ? 'hidden' : ''; ?>"
                        id="cartCount"
                    >

                        <?php echo $navCartCount; ?>

                    </span>

                </a>


            <?php endif; ?>


            <!-- =================================================
                 USER AREA
            ================================================== -->

            <?php if ($navLoggedIn): ?>


                <div
                    class="navbar-user"
                    id="navbarUser"
                >

                    <button
                        type="button"
                        class="navbar-user-toggle"id="navbarUserToggle"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >

                        <div class="user-avatar">

                            <span>
                                <?php
                                echo strtoupper(
                                    substr(
                                        $navUserName ?? 'U',
                                        0,
                                        1
                                    )
                                );
                                ?>
                            </span>

                        </div>


                        <div class="user-info">

                            <span class="user-greeting">
                                Hi,
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $navUserName ?? 'User',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </strong>

                        </div>


                        <i
                            class="fa-solid fa-chevron-down user-chevron"
                        ></i>

                    </button>


                    <!-- =========================================
                         USER DROPDOWN
                    ========================================== -->

                    <div
                        class="navbar-user-dropdown"
                        id="navbarUserDropdown"
                        hidden
                    >

                        <!-- USER HEADER -->

                        <div class="dropdown-user-header">

                            <div class="dropdown-avatar">

                                <?php
                                echo strtoupper(
                                    substr(
                                        $navUserName ?? 'U',
                                        0,
                                        1
                                    )
                                );
                                ?>

                            </div>


                            <div>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $navUserName ?? 'User',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </strong>


                                <span>

                                    <?php
                                    echo htmlspecialchars(
                                        $navUserRole ?? 'customer',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>


                        <div class="dropdown-divider"></div>


                        <!-- PROFILE -->

                        <a
                            href="<?php echo BASE_URL; ?>profile.php"
                            class="dropdown-item"
                        >

                            <span class="dropdown-item-icon">

                                <i class="fa-regular fa-user"></i>

                            </span>

                            <span class="dropdown-item-text">

                                <strong>
                                    My Profile
                                </strong>

                                <small>Manage your account
                                </small>

                            </span>

                        </a>


                        <?php if (
                            $navUserRole === 'customer'
                        ): ?>


                            <!-- ORDERS -->

                            <a
                                href="<?php echo BASE_URL; ?>order.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-box"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        My Orders
                                    </strong>

                                    <small>
                                        Track your purchases
                                    </small>

                                </span>

                            </a>


                            <!-- WISHLIST -->

                            <a
                                href="<?php echo BASE_URL; ?>wishlist.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-regular fa-heart"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Wishlist
                                    </strong>

                                    <small>
                                        Products you love
                                    </small>

                                </span>

                            </a>


                            <!-- CART -->

                            <a
                                href="<?php echo BASE_URL; ?>cart.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-bag-shopping"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Shopping Cart
                                    </strong>

                                    <small>
                                        <?php echo $navCartCount; ?>
                                        item(s)
                                    </small>

                                </span>

                            </a>


                            <!-- CUSTOMER DASHBOARD -->

                            <a
                                href="<?php echo BASE_URL; ?>dashboard.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-chart-line"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Dashboard
                                    </strong>

                                    <small>
                                        Your activity
                                    </small>

                                </span>

                            </a>


                        <?php elseif (
                            $navUserRole === 'vendor'
                        ): ?>


                            <!-- VENDOR DASHBOARD -->

                            <a
                                href="<?php echo BASE_URL; ?>vendor/dashboard.php"class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-store"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Vendor Dashboard
                                    </strong>

                                    <small>
                                        Manage your store
                                    </small>

                                </span>

                            </a>


                            <!-- VENDOR PRODUCTS -->

                            <a
                                href="<?php echo BASE_URL; ?>vendor/products.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-boxes-stacked"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        My Products
                                    </strong>

                                    <small>
                                        Manage products
                                    </small>

                                </span>

                            </a>


                            <!-- VENDOR ORDERS -->

                            <a
                                href="<?php echo BASE_URL; ?>vendor/orders.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-receipt"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Orders
                                    </strong>

                                    <small>
                                        Manage customer orders
                                    </small>

                                </span>

                            </a>


                            <!-- VENDOR SALES -->

                            <a
                                href="<?php echo BASE_URL; ?>vendor/sales.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-chart-column"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Sales
                                    </strong>

                                    <small>
                                        View store performance
                                    </small>

                                </span>

                            </a>


                        <?php elseif (
                            $navUserRole === 'admin'
                        ): ?>


                            <!-- ADMIN DASHBOARD -->

                            <a
                                href="<?php echo BASE_URL; ?>admin/dashboard.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-shield-halved"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Admin Dashboard
                                    </strong><small>
                                        Manage HochipoHub
                                    </small>

                                </span>

                            </a>


                            <!-- ADMIN USERS -->

                            <a
                                href="<?php echo BASE_URL; ?>admin/users.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-users"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Users
                                    </strong>

                                    <small>
                                        Manage customers
                                    </small>

                                </span>

                            </a>


                            <!-- ADMIN VENDORS -->

                            <a
                                href="<?php echo BASE_URL; ?>admin/vendors.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-store"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Vendors
                                    </strong>

                                    <small>
                                        Manage vendor accounts
                                    </small>

                                </span>

                            </a>


                            <!-- ADMIN ORDERS -->

                            <a
                                href="<?php echo BASE_URL; ?>admin/orders.php"
                                class="dropdown-item"
                            >

                                <span class="dropdown-item-icon">

                                    <i class="fa-solid fa-cart-shopping"></i>

                                </span>

                                <span class="dropdown-item-text">

                                    <strong>
                                        Orders
                                    </strong>

                                    <small>
                                        Monitor orders
                                    </small>

                                </span>

                            </a>


                        <?php endif; ?>


                        <div class="dropdown-divider"></div>


                        <!-- LOGOUT -->

                        <a
                            href="<?php echo BASE_URL; ?>auth/logout.php"
                            class="dropdown-item dropdown-logout"
                            onclick="return confirm('Are you sure you want to logout?');"
                        >

                            <span class="dropdown-item-icon">

                                <i class="fa-solid fa-right-from-bracket"></i>

                            </span>

                            <span class="dropdown-item-text">

                                <strong>
                                    Logout
                                </strong>

                                <small>
                                    Sign out of your account
                                </small>

                            </span>

                        </a>

                    </div>

                </div>


            <?php else: ?>


                <!-- =================================================
                     LOGIN BUTTON
                ================================================== -->

                <buttontype="button"
                    class="navbar-login-btn"
                    id="openLoginModal"
                    data-modal-target="loginModal"
                >

                    <i class="fa-regular fa-user"></i>

                    <span>
                        Login
                    </span>

                </button>


                <!-- =================================================
                     REGISTER BUTTON
                ================================================== -->

                <button
                    type="button"
                    class="navbar-register-btn"
                    id="openRegisterModal"
                    data-modal-target="registerModal"
                >

                    <span>
                        Join Us
                    </span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            <?php endif; ?>


            <!-- =================================================
                 MOBILE MENU BUTTON
            ================================================== -->

            <button
                type="button"
                class="navbar-menu-toggle"
                id="navbarMenuToggle"
                aria-label="Open navigation menu"
                aria-expanded="false"
            >

                <span></span>

                <span></span>

                <span></span>

            </button>

        </div>

    </div>


    <!-- =========================================================
         NAVIGATION LINKS
    ========================================================== -->

    <div
        class="navbar-navigation"
        id="navbarNavigation"
    >

        <div class="navbar-navigation-inner">


            <!-- HOME -->

            <a
                href="<?php echo BASE_URL; ?>index.php"
                class="nav-link <?php echo navActive('index.php'); ?>"
            >

                <i class="fa-solid fa-house"></i>

                <span>
                    Home
                </span>

            </a>


            <!-- CATALOG -->

            <a
                href="<?php echo BASE_URL; ?>catalog.php"
                class="nav-link <?php echo navActive('catalog.php'); ?>"
            >

                <i class="fa-solid fa-compass"></i>

                <span>
                    Explore
                </span>

            </a>


            <!-- CATEGORY -->

            <a
                href="<?php echo BASE_URL; ?>category.php"
                class="nav-link <?php echo navActive('category.php'); ?>"
            >

                <i class="fa-solid fa-layer-group"></i>

                <span>
                    Categories
                </span>

            </a>


            <!-- VENDORS -->

            <a
                href="<?php echo BASE_URL; ?>vendor.php"
                class="nav-link <?php echo navActive('vendor.php'); ?>"
            >

                <i class="fa-solid fa-store"></i>

                <span>
                    Vendors
                </span>

            </a>


            <!-- TRENDING -->

            <a
                href="<?php echo BASE_URL; ?>catalog.php?sort=trending"
                class="nav-link nav-link-special"
            >

                <span class="nav-special-icon">

                    <i class="fa-solid fa-fire"></i>

                </span>

                <span>
                    Trending
                </span>

                <span class="nav-new-badge">
                    HOT
                </span>

            </a>


            <!-- CONTACT -->

            <a
                href="<?php echo BASE_URL; ?>contact.php"
                class="nav-link <?php echo navActive('contact.php'); ?>"
            >

                <i class="fa-regular fa-message"></i>

                <span>
                    Contact
                </span>

            </a>


            <!-- VENDOR CTA -->

            <?php if (
                !$navLoggedIn): ?>

                <div class="navbar-vendor-cta">

                    <span>
                        Want to sell?
                    </span>

                    <button
                        type="button"
                        class="vendor-cta-btn"
                        data-modal-target="registerModal"
                        data-register-role="vendor"
                    >

                        Become a Vendor

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>

                </div>

            <?php elseif (
                $navUserRole === 'customer'
            ): ?>

                <div class="navbar-vendor-cta">

                    <span>
                        Got something to sell?
                    </span>

                    <a
                        href="<?php echo BASE_URL; ?>vendor/setup_profile.php"
                        class="vendor-cta-btn"
                    >

                        Become a Vendor

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =========================================================
         MOBILE SEARCH
    ========================================================== -->

    <div
        class="mobile-search-container"
        id="mobileSearchContainer"
        hidden
    >

        <form
            action="<?php echo BASE_URL; ?>search.php"
            method="GET"
            class="mobile-search-form"
        >

            <div class="mobile-search-input-wrapper">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    name="q"
                    placeholder="Search HochipoHub..."
                    autocomplete="off"
                    maxlength="100"
                >

            </div>


            <button
                type="submit"
                aria-label="Search"
            >

                <i class="fa-solid fa-arrow-right"></i>

            </button>

        </form>

    </div>

</header>


<!-- =============================================================
     NAVBAR SPACER
============================================================== -->

<div class="navbar-spacer"></div>