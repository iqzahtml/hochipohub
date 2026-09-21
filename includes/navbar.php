<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL NAVBAR
|--------------------------------------------------------------------------
| File:
| includes/navbar.php
|
| Login/Register menggunakan modal.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$navBaseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '/hochipohub/';


/*
|--------------------------------------------------------------------------
| URL HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('navUrl')) {

    function navUrl($path = '')
    {
        global $navBaseUrl;

        return $navBaseUrl . ltrim($path, '/');
    }
}


/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('navE')) {

    function navE($value)
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
| SAFE DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$isLoggedIn = $isLoggedIn ?? false;

$userName = $userName ?? '';

$userRole = strtolower(
    trim(
        $userRole ?? 'customer'
    )
);

$cartCount = (int) (
    $cartCount ?? 0
);

$wishlistCount = (int) (
    $wishlistCount ?? 0
);


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage = $currentPage
    ?? basename(
        $_SERVER['PHP_SELF'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| ACTIVE NAVIGATION
|--------------------------------------------------------------------------
*/

if (!function_exists('navActive')) {

    function navActive($pages)
    {
        global $currentPage;

        if (!is_array($pages)) {
            $pages = [$pages];
        }

        return in_array(
            $currentPage,
            $pages,
            true
        )
            ? 'active'
            : '';
    }
}


/*
|--------------------------------------------------------------------------
| USER INITIAL
|--------------------------------------------------------------------------
*/

$userInitial = 'U';

if (!empty($userName)) {

    $cleanName = trim($userName);

    if ($cleanName !== '') {
        $userInitial = strtoupper(
            substr(
                $cleanName,
                0,
                1
            )
        );
    }
}

?>


<!-- =========================================================
     AUTH MODAL CSS
========================================================= -->

<link
    rel="stylesheet"
    href="<?= navE(
        navUrl('css/modal.css')
    ) ?>"
>


<!-- =========================================================
     NAVBAR CSS
========================================================= -->

<style>

    /* =========================================================
       HEADER
    ========================================================= */

    .site-header {
        position: relative;
        z-index: 1000;

        width: 100%;

        background: rgba(255, 255, 255, 0.98);

        border-bottom:
            1px solid #edf1f7;

        box-shadow:
            0 4px 20px
            rgba(15, 45, 100, 0.04);
    }


    /* =========================================================
       NAVBAR CONTAINER
    ========================================================= */

    .navbar-container {
        width: 100%;
        max-width: 1720px;

        min-height: 96px;

        margin: 0 auto;

        padding:
            12px 44px;

        display: flex;
        align-items: center;

        gap: 34px;
    }


    /* =========================================================
       BRAND / LOGO
    ========================================================= */

    .brand {
        width: 190px;
        min-width: 190px;

        height: 72px;

        display: flex;
        align-items: center;
        justify-content: flex-start;

        overflow: hidden;

        text-decoration: none;

        flex-shrink: 0;
    }


    .header-logo {
        display: block;

        width: 165px;
        height: 72px;

        object-fit: contain;
        object-position: left center;

        mix-blend-mode: multiply;

        transition:
            transform .2s ease,
            opacity .2s ease;
    }


    .brand:hover .header-logo {
        transform: scale(1.025);
    }


    /* =========================================================
       MAIN NAVIGATION
    ========================================================= */

    .main-nav {
        display: flex;
        align-items: center;

        gap: 8px;

        flex-shrink: 0;
    }


    .main-nav a {
        position: relative;

        min-height: 54px;

        padding:
            0 18px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        color: #42516a;

        text-decoration: none;

        border-radius: 14px;

        font-size: 15px;
        font-weight: 800;

        white-space: nowrap;

        transition:
            color .2s ease,
            background .2s ease,
            transform .2s ease;
    }


    .main-nav a:hover {
        color: #2468ed;

        background: #f4f8ff;
    }


    .main-nav a.active {
        color: #2468ed;

        background: #eaf2ff;
    }


    .main-nav a.active::after {
        content: '';

        position: absolute;

        left: 50%;
        bottom: 7px;

        width: 23px;
        height: 4px;

        transform:
            translateX(-50%);

        border-radius: 999px;

        background: #2f76ff;
    }


    /* =========================================================
       SEARCH
    ========================================================= */

    .navbar-search {
        flex: 1;

        min-width: 240px;
        max-width: 570px;

        height: 60px;

        margin-left: auto;

        padding:
            6px 7px 6px 20px;

        display: flex;
        align-items: center;

        gap: 12px;

        background: #f8faff;

        border:
            1px solid #dfe7f3;

        border-radius: 18px;

        transition:
            border-color .2s ease,
            box-shadow .2s ease,
            background .2s ease;
    }


    .navbar-search:focus-within {
        background: #ffffff;

        border-color: #a9c6ff;

        box-shadow:
            0 0 0 4px
            rgba(47, 118, 255, .08);
    }


    .navbar-search .search-icon {
        width: 26px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        flex-shrink: 0;

        font-size: 17px;
    }


    .navbar-search input {
        flex: 1;

        min-width: 0;

        height: 46px;

        padding: 0;

        background: transparent;

        border: none;
        outline: none;

        color: #1b315a;

        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
    }


    .navbar-search input::placeholder {
        color: #9aa8bd;
    }


    .navbar-search button {
        height: 46px;

        padding:
            0 25px;

        border: none;

        border-radius: 13px;

        background:
            linear-gradient(
                135deg,
                #347cff,
                #2566e6
            );

        color: #ffffff;

        cursor: pointer;

        font-family: inherit;
        font-size: 14px;
        font-weight: 800;

        box-shadow:
            0 8px 20px
            rgba(47, 118, 255, .20);

        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }


    .navbar-search button:hover {
        transform:
            translateY(-1px);

        box-shadow:
            0 10px 24px
            rgba(47, 118, 255, .28);
    }


    /* =========================================================
       RIGHT ACTIONS
    ========================================================= */

    .navbar-actions {
        display: flex;
        align-items: center;

        gap: 12px;

        flex-shrink: 0;
    }


    /* =========================================================
       CART / WISHLIST ICON BUTTON
    ========================================================= */

    .nav-icon-btn {
        position: relative;

        width: 52px;
        height: 52px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        background: #ffffff;

        border:
            1px solid #e3e9f3;

        border-radius: 15px;

        color: #324867;

        text-decoration: none;

        box-shadow:
            0 7px 18px
            rgba(18, 49, 98, .05);

        transition:
            transform .2s ease,
            border-color .2s ease,
            box-shadow .2s ease;
    }


    .nav-icon-btn:hover {
        transform:
            translateY(-2px);

        border-color: #c6d9ff;

        box-shadow:
            0 10px 24px
            rgba(18, 49, 98, .09);
    }


    .nav-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        font-size: 20px;
    }


    .wishlist-icon {
        font-size: 27px;
        line-height: 1;
    }


    .nav-badge {
        position: absolute;

        top: -6px;
        right: -6px;

        min-width: 21px;
        height: 21px;

        padding:
            0 6px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #ef4444;

        border:
            2px solid #ffffff;

        border-radius: 999px;

        color: #ffffff;

        font-size: 10px;
        font-weight: 900;
    }


    /* =========================================================
       AUTH BUTTONS
    ========================================================= */

    .auth-buttons {
        display: flex;
        align-items: center;

        gap: 12px;
    }


    .btn-login,
    .btn-register {
        height: 54px;

        padding:
            0 23px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border-radius: 14px;

        cursor: pointer;

        font-family: inherit;
        font-size: 14px;
        font-weight: 800;

        transition:
            transform .2s ease,
            box-shadow .2s ease,
            background .2s ease;
    }


    .btn-login {
        background: #ffffff;

        color: #2362db;

        border:
            1px solid #cfddf8;
    }


    .btn-login:hover {
        background: #f5f8ff;

        transform:
            translateY(-1px);
    }


    .btn-register {
        border: none;

        color: #ffffff;

        background:
            linear-gradient(
                135deg,
                #347cff,
                #2464e3
            );

        box-shadow:
            0 10px 22px
            rgba(47, 118, 255, .22);
    }


    .btn-register:hover {
        transform:
            translateY(-1px);

        box-shadow:
            0 12px 27px
            rgba(47, 118, 255, .30);
    }


    /* =========================================================
       USER MENU
    ========================================================= */

    .user-menu {
        position: relative;
    }


    .user-menu-button {
        min-height: 54px;

        padding:
            6px 12px 6px 7px;

        display: flex;
        align-items: center;

        gap: 9px;

        background: #ffffff;

        border:
            1px solid #dfe7f3;

        border-radius: 15px;

        cursor: pointer;

        font-family: inherit;

        transition:
            border-color .2s ease,
            box-shadow .2s ease;
    }


    .user-menu-button:hover {
        border-color: #bfd4ff;

        box-shadow:
            0 8px 22px
            rgba(30, 65, 130, .08);
    }


    .user-avatar,
    .dropdown-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        flex-shrink: 0;

        background:
            linear-gradient(
                135deg,
                #347cff,
                #1c59d3
            );

        color: #ffffff;

        font-weight: 900;
    }


    .user-avatar {
        width: 39px;
        height: 39px;

        border-radius: 12px;

        font-size: 14px;
    }


    .user-menu-name {
        max-width: 130px;

        overflow: hidden;

        color: #243b61;

        text-overflow: ellipsis;
        white-space: nowrap;

        font-size: 13px;
        font-weight: 800;
    }


    .user-chevron {
        color: #8593a8;

        font-size: 9px;
    }


    /* =========================================================
       USER DROPDOWN
    ========================================================= */

    .user-dropdown {
        position: absolute;

        top: calc(100% + 12px);
        right: 0;

        width: 255px;

        padding: 10px;

        display: none;

        background: #ffffff;

        border:
            1px solid #e2e8f2;

        border-radius: 18px;

        box-shadow:
            0 22px 55px
            rgba(15, 42, 89, .16);

        z-index: 1200;
    }


    .user-dropdown.show {
        display: block;
    }


    .dropdown-user-info {
        padding: 10px;

        display: flex;
        align-items: center;

        gap: 11px;
    }


    .dropdown-avatar {
        width: 43px;
        height: 43px;

        border-radius: 13px;

        font-size: 15px;
    }


    .dropdown-user-info strong {
        display: block;

        max-width: 155px;

        overflow: hidden;

        color: #173568;

        text-overflow: ellipsis;
        white-space: nowrap;

        font-size: 13px;
    }


    .dropdown-user-info small {
        display: block;

        margin-top: 3px;

        color: #8997aa;

        font-size: 11px;
    }


    .dropdown-divider {
        height: 1px;

        margin:
            7px 5px;

        background: #edf1f6;
    }


    .dropdown-link {
        min-height: 43px;

        padding:
            9px 11px;

        display: flex;
        align-items: center;

        color: #445675;

        text-decoration: none;

        border-radius: 11px;

        font-size: 12px;
        font-weight: 700;

        transition:
            background .2s ease,
            color .2s ease;
    }


    .dropdown-link:hover {
        color: #2468ed;

        background: #f3f7ff;
    }


    .dropdown-danger {
        color: #dc3545;
    }


    .dropdown-danger:hover {
        color: #c82333;

        background: #fff1f2;
    }


    /* =========================================================
       MOBILE TOGGLE
    ========================================================= */

    .mobile-menu-toggle {
        width: 46px;
        height: 46px;

        padding: 0;

        display: none;

        align-items: center;
        justify-content: center;
        flex-direction: column;

        gap: 5px;

        background: #f7f9fd;

        border:
            1px solid #e0e7f2;

        border-radius: 13px;

        cursor: pointer;
    }


    .mobile-menu-toggle span {
        width: 20px;
        height: 2px;

        display: block;

        background: #263e65;

        border-radius: 999px;

        transition: .2s ease;
    }


    .mobile-menu-toggle.active span:nth-child(1) {
        transform:
            translateY(7px)
            rotate(45deg);
    }


    .mobile-menu-toggle.active span:nth-child(2) {
        opacity: 0;
    }


    .mobile-menu-toggle.active span:nth-child(3) {
        transform:
            translateY(-7px)
            rotate(-45deg);
    }


    /* =========================================================
       MOBILE MENU
    ========================================================= */

    .mobile-menu {
        display: none;

        background: #ffffff;

        border-top:
            1px solid #edf1f7;

        box-shadow:
            0 14px 30px
            rgba(20, 50, 100, .08);
    }


    .mobile-menu.show {
        display: block;
    }


    .mobile-menu-inner {
        max-width: 1720px;

        margin: 0 auto;

        padding:
            18px 24px 24px;

        display: flex;
        flex-direction: column;

        gap: 7px;
    }


    .mobile-search {
        height: 50px;

        margin-bottom: 10px;

        padding:
            5px 6px 5px 15px;

        display: flex;
        align-items: center;

        background: #f7f9fd;

        border:
            1px solid #e0e7f2;

        border-radius: 13px;
    }


    .mobile-search input {
        flex: 1;

        min-width: 0;

        border: none;
        outline: none;

        background: transparent;

        color: #243b61;

        font-family: inherit;
        font-size: 13px;
    }


    .mobile-search button {
        width: 40px;
        height: 40px;

        border: none;

        border-radius: 10px;

        background: #2f76ff;

        color: #ffffff;

        cursor: pointer;
    }


    .mobile-menu-inner > a {
        min-height: 45px;

        padding:
            10px 12px;

        display: flex;
        align-items: center;

        color: #3e5273;

        text-decoration: none;

        border-radius: 11px;

        font-size: 13px;
        font-weight: 700;
    }


    .mobile-menu-inner > a:hover {
        color: #2468ed;

        background: #f3f7ff;
    }


    .mobile-login-button,
    .mobile-register-button {
        min-height: 45px;

        padding:
            10px 15px;

        border-radius: 11px;

        cursor: pointer;

        font-family: inherit;
        font-size: 13px;
        font-weight: 800;
    }


    .mobile-login-button {
        margin-top: 8px;

        background: #ffffff;

        color: #2468ed;

        border:
            1px solid #cadcff;
    }


    .mobile-register-button {
        border: none;

        color: #ffffff;

        background: #2f76ff;
    }


    /* =========================================================
       RESPONSIVE - LARGE LAPTOP
    ========================================================= */

    @media (max-width: 1450px) {

        .navbar-container {
            padding:
                12px 28px;

            gap: 22px;
        }


        .brand {
            width: 165px;
            min-width: 165px;
        }


        .header-logo {
            width: 150px;
        }


        .main-nav a {
            padding:
                0 13px;
        }


        .navbar-search {
            max-width: 470px;
        }
    }


    /* =========================================================
       RESPONSIVE - SMALL LAPTOP
    ========================================================= */

    @media (max-width: 1180px) {

        .navbar-container {
            gap: 16px;
        }


        .brand {
            width: 145px;
            min-width: 145px;
        }


        .header-logo {
            width: 135px;
        }


        .main-nav a {
            padding:
                0 10px;

            font-size: 13px;
        }


        .navbar-search {
            min-width: 210px;
        }


        .navbar-search button {
            padding:
                0 18px;
        }


        .user-menu-name {
            display: none;
        }
    }


    /* =========================================================
       RESPONSIVE - TABLET / MOBILE
    ========================================================= */

    @media (max-width: 980px) {

        .navbar-container {
            min-height: 82px;

            padding:
                8px 20px;

            gap: 14px;
        }


        .brand {
            width: 145px;
            min-width: 145px;

            height: 64px;
        }


        .header-logo {
            width: 135px;
            height: 64px;
        }


        .main-nav,
        .navbar-search {
            display: none;
        }


        .navbar-actions {
            margin-left: auto;
        }


        .mobile-menu-toggle {
            display: flex;
        }
    }


    @media (max-width: 650px) {

        .navbar-container {
            min-height: 74px;

            padding:
                7px 14px;
        }


        .brand {
            width: 118px;
            min-width: 118px;

            height: 58px;
        }


        .header-logo {
            width: 112px;
            height: 58px;
        }


        .auth-buttons {
            gap: 7px;
        }


        .btn-login,
        .btn-register {
            height: 44px;

            padding:
                0 13px;

            font-size: 12px;
        }


        .nav-icon-btn {
            width: 44px;
            height: 44px;
        }


        .user-menu-button {
            min-height: 44px;

            padding:
                4px 7px;
        }


        .user-avatar {
            width: 35px;
            height: 35px;
        }


        .user-chevron {
            display: none;
        }
    }


    @media (max-width: 470px) {

        .brand {
            width: 100px;
            min-width: 100px;
        }


        .header-logo {
            width: 96px;
        }


        .btn-login {
            display: none;
        }


        .btn-register {
            padding:
                0 11px;
        }
    }

</style>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="site-header">

    <div class="navbar-container">


        <!-- =====================================================
             BRAND / LOGO
        ====================================================== -->

        <a
            href="<?= navE(
                navUrl('index.php')
            ) ?>"
            class="brand"
            aria-label="HochipoHub Home"
        >

            <img
                src="<?= navE(
                    navUrl('image/logo.jpg')
                ) ?>"
                alt="HochipoHub"
                class="header-logo"
            >

        </a>


        <!-- =====================================================
             MAIN NAVIGATION
        ====================================================== -->

        <nav
            class="main-nav"
            aria-label="Main Navigation"
        >

            <a
                href="<?= navE(
                    navUrl('index.php')
                ) ?>"
                class="<?= navActive(
                    'index.php'
                ) ?>"
            >
                Home
            </a>


            <a
                href="<?= navE(
                    navUrl('catalog.php')
                ) ?>"
                class="<?= navActive([
                    'catalog.php',
                    'product.php',
                    'product_details.php'
                ]) ?>"
            >
                Catalog
            </a>


            <a
                href="<?= navE(
                    navUrl('category.php')
                ) ?>"
                class="<?= navActive(
                    'category.php'
                ) ?>"
            >
                Categories
            </a>


            <a
                href="<?= navE(
                    navUrl('vendor.php')
                ) ?>"
                class="<?= navActive(
                    'vendor.php'
                ) ?>"
            >
                Vendors
            </a>

        </nav>


        <!-- =====================================================
             SEARCH
        ====================================================== -->

        <form
            class="navbar-search"
            action="<?= navE(
                navUrl('search.php')
            ) ?>"
            method="GET"
        >

            <span class="search-icon">
                🔍
            </span>


            <input
                type="search"
                name="q"
                placeholder="Search products..."
                value="<?= navE(
                    $_GET['q'] ?? ''
                ) ?>"
                autocomplete="off"
            >


            <button type="submit">
                Search
            </button>

        </form>


        <!-- =====================================================
             RIGHT ACTIONS
        ====================================================== -->

        <div class="navbar-actions">


            <?php if ($isLoggedIn): ?>


                <!-- =================================================
                     CUSTOMER
                ================================================== -->

                <?php if ($userRole === 'customer'): ?>


                    <!-- CART -->

                    <a
                        href="<?= navE(
                            navUrl('cart.php')
                        ) ?>"
                        class="nav-icon-btn"
                        aria-label="Shopping Cart"
                    >

                        <span class="nav-icon">
                            🛒
                        </span>


                        <?php if ($cartCount > 0): ?>

                            <span class="nav-badge">

                                <?= $cartCount > 99
                                    ? '99+'
                                    : $cartCount ?>

                            </span>

                        <?php endif; ?>

                    </a>


                    <!-- WISHLIST -->

                    <a
                        href="<?= navE(
                            navUrl('wishlist.php')
                        ) ?>"
                        class="nav-icon-btn"
                        aria-label="Wishlist"
                    >

                        <span class="nav-icon wishlist-icon">
                            ♡
                        </span>


                        <?php if ($wishlistCount > 0): ?>

                            <span class="nav-badge">

                                <?= $wishlistCount > 99
                                    ? '99+'
                                    : $wishlistCount ?>

                            </span>

                        <?php endif; ?>

                    </a>


                <?php endif; ?>


                <!-- =================================================
                     VENDOR / ADMIN USER MENU
                ================================================== -->

                <?php if (
                    $userRole === 'vendor' ||
                    $userRole === 'admin'
                ): ?>


                    <div class="user-menu">


                        <button
                            type="button"
                            class="user-menu-button"
                            id="userMenuButton"
                            aria-expanded="false"
                        >

                            <span class="user-avatar">

                                <?= navE(
                                    $userInitial
                                ) ?>

                            </span>


                            <span class="user-menu-name">

                                <?= navE(
                                    $userName
                                    ?: 'Account'
                                ) ?>

                            </span>


                            <span class="user-chevron">
                                ▼
                            </span>

                        </button>


                        <div
                            class="user-dropdown"
                            id="userDropdown"
                        >


                            <div class="dropdown-user-info">

                                <span class="dropdown-avatar">

                                    <?= navE(
                                        $userInitial
                                    ) ?>

                                </span>


                                <div>

                                    <strong>

                                        <?= navE(
                                            $userName
                                            ?: 'User'
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= navE(
                                            ucfirst(
                                                $userRole
                                            )
                                        ) ?>

                                    </small>

                                </div>

                            </div>


                            <div class="dropdown-divider"></div>


                            <a
                                href="<?= navE(
                                    navUrl(
                                        'profile.php'
                                    )
                                ) ?>"
                                class="dropdown-link"
                            >
                                👤 Profile
                            </a>


                            <?php if ($userRole === 'vendor'): ?>


                                <a
                                    href="<?= navE(
                                        navUrl(
                                            'seller/dashboard.php'
                                        )
                                    ) ?>"
                                    class="dropdown-link"
                                >
                                    🏪 Vendor Dashboard
                                </a>


                                <a
                                    href="<?= navE(
                                        navUrl(
                                            'seller/products.php'
                                        )
                                    ) ?>"
                                    class="dropdown-link"
                                >
                                    🛍️ My Products
                                </a>


                                <a
                                    href="<?= navE(
                                        navUrl(
                                            'seller/orders.php'
                                        )
                                    ) ?>"
                                    class="dropdown-link"
                                >
                                    📦 Orders
                                </a>


                            <?php endif; ?>


                            <?php if ($userRole === 'admin'): ?>


                                <a
                                    href="<?= navE(
                                        navUrl(
                                            'admin/dashboard.php'
                                        )
                                    ) ?>"
                                    class="dropdown-link"
                                >
                                    ⚙️ Admin Dashboard
                                </a>


                                <a
                                    href="<?= navE(
                                        navUrl(
                                            'admin/products.php'
                                        )
                                    ) ?>"
                                    class="dropdown-link"
                                >
                                    🛍️ Manage Products
                                </a>


                                <a
                                    href="<?= navE(
                                        navUrl(
                                            'admin/users.php'
                                        )
                                    ) ?>"
                                    class="dropdown-link"
                                >
                                    👥 Manage Users
                                </a>


                            <?php endif; ?>


                            <div class="dropdown-divider"></div>


                            <a
                                href="<?= navE(
                                    navUrl(
                                        'auth/logout.php'
                                    )
                                ) ?>"
                                class="
                                    dropdown-link
                                    dropdown-danger
                                "
                            >
                                ↪ Logout
                            </a>

                        </div>

                    </div>


                <?php endif; ?>


            <?php else: ?>


                <!-- =================================================
                     GUEST AUTH
                ================================================== -->

                <div class="auth-buttons">


                    <!-- LOGIN -->

                    <button
                        type="button"
                        class="btn-login"
                        id="navbarLoginButton"
                        data-modal-open="loginModal"
                    >
                        Login
                    </button>


                    <!-- REGISTER -->

                    <button
                        type="button"
                        class="btn-register"
                        id="navbarRegisterButton"
                        data-modal-open="registerModal"
                    >
                        Register
                    </button>


                </div>


            <?php endif; ?>


            <!-- =================================================
                 MOBILE TOGGLE
            ================================================== -->

            <button
                type="button"
                class="mobile-menu-toggle"
                id="mobileMenuToggle"
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
         MOBILE MENU
    ========================================================= -->

    <div
        class="mobile-menu"
        id="mobileMenu"
    >

        <div class="mobile-menu-inner">


            <form
                class="mobile-search"
                action="<?= navE(
                    navUrl(
                        'search.php'
                    )
                ) ?>"
                method="GET"
            >

                <input
                    type="search"
                    name="q"
                    placeholder="Search products..."
                >


                <button type="submit">
                    🔍
                </button>

            </form>


            <a
                href="<?= navE(
                    navUrl(
                        'index.php'
                    )
                ) ?>"
            >
                🏠 Home
            </a>


            <a
                href="<?= navE(
                    navUrl(
                        'catalog.php'
                    )
                ) ?>"
            >
                🛍️ Catalog
            </a>


            <a
                href="<?= navE(
                    navUrl(
                        'category.php'
                    )
                ) ?>"
            >
                📂 Categories
            </a>


            <a
                href="<?= navE(
                    navUrl(
                        'vendor.php'
                    )
                ) ?>"
            >
                🏪 Vendors
            </a>


            <?php if ($isLoggedIn): ?>


                <?php if ($userRole === 'customer'): ?>

                    <a
                        href="<?= navE(
                            navUrl(
                                'cart.php'
                            )
                        ) ?>"
                    >
                        🛒 Cart
                    </a>


                    <a
                        href="<?= navE(
                            navUrl(
                                'wishlist.php'
                            )
                        ) ?>"
                    >
                        ♡ Wishlist
                    </a>


                    <a
                        href="<?= navE(
                            navUrl(
                                'dashboard.php'
                            )
                        ) ?>"
                    >
                        👤 My Account
                    </a>

                <?php endif; ?>


                <?php if ($userRole === 'vendor'): ?>

                    <a
                        href="<?= navE(
                            navUrl(
                                'seller/dashboard.php'
                            )
                        ) ?>"
                    >
                        🏪 Seller Dashboard
                    </a>

                <?php endif; ?>


                <?php if ($userRole === 'admin'): ?>

                    <a
                        href="<?= navE(
                            navUrl(
                                'admin/dashboard.php'
                            )
                        ) ?>"
                    >
                        ⚙️ Admin Dashboard
                    </a>

                <?php endif; ?>


                <a
                    href="<?= navE(
                        navUrl(
                            'auth/logout.php'
                        )
                    ) ?>"
                >
                    ↪ Logout
                </a>


            <?php else: ?>


                <button
                    type="button"
                    class="mobile-login-button"
                    data-modal-open="loginModal"
                >
                    Login
                </button>


                <button
                    type="button"
                    class="mobile-register-button"
                    data-modal-open="registerModal"
                >
                    Register
                </button>


            <?php endif; ?>


        </div>

    </div>

</header>


<!-- =========================================================
     AUTH MODALS
========================================================= -->

<?php

if (!defined('HOCHIPOHUB_AUTH_MODALS_LOADED')):

    define(
        'HOCHIPOHUB_AUTH_MODALS_LOADED',
        true
    );

    require_once __DIR__
        . '/login_modal.php';

    require_once __DIR__
        . '/register_modal.php';

endif;

?>


<!-- =========================================================
     NAVBAR JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | USER DROPDOWN
        |--------------------------------------------------------------------------
        */

        const userButton =
            document.getElementById(
                'userMenuButton'
            );

        const userDropdown =
            document.getElementById(
                'userDropdown'
            );


        if (
            userButton &&
            userDropdown
        ) {

            userButton.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    const isOpen =
                        userDropdown
                            .classList
                            .contains(
                                'show'
                            );

                    userDropdown
                        .classList
                        .toggle(
                            'show'
                        );

                    userButton
                        .setAttribute(
                            'aria-expanded',
                            String(
                                !isOpen
                            )
                        );
                }
            );


            document.addEventListener(
                'click',
                function (event) {

                    if (
                        !userDropdown.contains(
                            event.target
                        ) &&
                        !userButton.contains(
                            event.target
                        )
                    ) {

                        userDropdown
                            .classList
                            .remove(
                                'show'
                            );

                        userButton
                            .setAttribute(
                                'aria-expanded',
                                'false'
                            );
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE MENU
        |--------------------------------------------------------------------------
        */

        const mobileToggle =
            document.getElementById(
                'mobileMenuToggle'
            );

        const mobileMenu =
            document.getElementById(
                'mobileMenu'
            );


        if (
            mobileToggle &&
            mobileMenu
        ) {

            mobileToggle.addEventListener(
                'click',
                function () {

                    const isOpen =
                        mobileMenu
                            .classList
                            .contains(
                                'show'
                            );

                    mobileMenu
                        .classList
                        .toggle(
                            'show'
                        );

                    mobileToggle
                        .classList
                        .toggle(
                            'active'
                        );

                    mobileToggle
                        .setAttribute(
                            'aria-expanded',
                            String(
                                !isOpen
                            )
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CLOSE MOBILE MENU AFTER LINK CLICK
        |--------------------------------------------------------------------------
        */

        if (mobileMenu) {

            const mobileLinks =
                mobileMenu.querySelectorAll(
                    'a'
                );

            mobileLinks.forEach(
                function (link) {

                    link.addEventListener(
                        'click',
                        function () {

                            mobileMenu
                                .classList
                                .remove(
                                    'show'
                                );

                            if (mobileToggle) {

                                mobileToggle
                                    .classList
                                    .remove(
                                        'active'
                                    );

                                mobileToggle
                                    .setAttribute(
                                        'aria-expanded',
                                        'false'
                                    );
                            }
                        }
                    );
                }
            );
        }

    }
);

</script>


<!-- =========================================================
     AUTH MODAL JAVASCRIPT
========================================================= -->

<script
    src="<?= navE(
        navUrl(
            'js/modal.js'
        )
    ) ?>"
    defer
></script>