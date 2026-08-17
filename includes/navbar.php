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

    $userInitial = strtoupper(
        substr(
            $cleanName,
            0,
            1
        )
    );

}

?>

<!-- =========================================================
     AUTH MODAL CSS
     ========================================================= -->

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        navUrl('css/modal.css'),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>


<!-- =========================================================
     NAVBAR
     ========================================================= -->

<header class="site-header">

    <div class="navbar-container">

        <!-- =====================================================
             BRAND
             ===================================================== -->

        <a
    href="<?= htmlspecialchars(
        navUrl('index.php'),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    class="brand"
    aria-label="HochipoHub Home"
>

    <img
        src="<?= htmlspecialchars(
            navUrl('image/logo.jpeg'),
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        alt="HochipoHub"
        class="header-logo"
    >

</a>


        <!-- =====================================================
             MAIN NAVIGATION
             ===================================================== -->

        <nav
            class="main-nav"
            aria-label="Main Navigation"
        >

            <a
                href="<?= htmlspecialchars(
                    navUrl('index.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive('index.php') ?>"
            >
                Home
            </a>


            <a
                href="<?= htmlspecialchars(
                    navUrl('catalog.php'),
                    ENT_QUOTES,
                    'UTF-8'
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
                href="<?= htmlspecialchars(
                    navUrl('category.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive('category.php') ?>"
            >
                Categories
            </a>


            <a
                href="<?= htmlspecialchars(
                    navUrl('vendor.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive('vendor.php') ?>"
            >
                Vendors
            </a>

        </nav>


        <!-- =====================================================
             SEARCH
             ===================================================== -->

        <form
            class="navbar-search"
            action="<?= htmlspecialchars(
                navUrl('search.php'),
                ENT_QUOTES,
                'UTF-8'
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
                value="<?= htmlspecialchars(
                    $_GET['q'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                autocomplete="off"
            >


            <button type="submit">
                Search
            </button>

        </form>


        <!-- =====================================================
             RIGHT ACTIONS
             ===================================================== -->

        <div class="navbar-actions">

            <?php if ($isLoggedIn): ?>

                <!-- CUSTOMER -->

                <?php if ($userRole === 'customer'): ?>

                    <a
                        href="<?= htmlspecialchars(
                            navUrl('cart.php'),
                            ENT_QUOTES,
                            'UTF-8'
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


                    <a
                        href="<?= htmlspecialchars(
                            navUrl('wishlist.php'),
                            ENT_QUOTES,
                            'UTF-8'
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


                <!-- USER MENU -->

                <div class="user-menu">

                    <button
                        type="button"
                        class="user-menu-button"
                        id="userMenuButton"
                        aria-expanded="false"
                    >

                        <span class="user-avatar">

                            <?= htmlspecialchars(
                                $userInitial,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>


                        <span class="user-menu-name">

                            <?= htmlspecialchars(
                                $userName ?: 'Account',
                                ENT_QUOTES,
                                'UTF-8'
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

                                <?= htmlspecialchars(
                                    $userInitial,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>


                            <div>

                                <strong>

                                    <?= htmlspecialchars(
                                        $userName ?: 'User',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>


                                <small>

                                    <?= htmlspecialchars(
                                        ucfirst($userRole),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </small>

                            </div>

                        </div>


                        <div class="dropdown-divider"></div>


                        <a
                            href="<?= htmlspecialchars(
                                navUrl('profile.php'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="dropdown-link"
                        >
                            👤 Profile
                        </a>


                        <?php if ($userRole === 'customer'): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    navUrl('order.php'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                📦 My Orders
                            </a>

                        <?php endif; ?>


                        <?php if ($userRole === 'vendor'): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    navUrl(
                                        'seller/dashboard.php'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                🏪 Vendor Dashboard
                            </a>


                            <a
                                href="<?= htmlspecialchars(
                                    navUrl(
                                        'seller/products.php'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                🛍️ My Products
                            </a>


                            <a
                                href="<?= htmlspecialchars(
                                    navUrl(
                                        'seller/orders.php'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                📦 Orders
                            </a>

                        <?php endif; ?>


                        <?php if ($userRole === 'admin'): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    navUrl(
                                        'admin/dashboard.php'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                ⚙️ Admin Dashboard
                            </a>


                            <a
                                href="<?= htmlspecialchars(
                                    navUrl(
                                        'admin/products.php'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                🛍️ Manage Products
                            </a>


                            <a
                                href="<?= htmlspecialchars(
                                    navUrl(
                                        'admin/users.php'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                👥 Manage Users
                            </a>

                        <?php endif; ?>


                        <div class="dropdown-divider"></div>


                        <a
                            href="<?= htmlspecialchars(
                                navUrl(
                                    'auth/logout.php'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="dropdown-link dropdown-danger"
                        >
                            ↪ Logout
                        </a>

                    </div>

                </div>


            <?php else: ?>

                <!-- =================================================
                     GUEST AUTH
                     ================================================= -->

                <div class="auth-buttons">

                    <button
                        type="button"
                        class="btn-login"
                        data-modal-open="loginModal"
                    >
                        Login
                    </button>


                    <button
                        type="button"
                        class="btn-register"
                        data-modal-open="registerModal"
                    >
                        Register
                    </button>

                </div>

            <?php endif; ?>


            <!-- MOBILE -->

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
                action="<?= htmlspecialchars(
                    navUrl('search.php'),
                    ENT_QUOTES,
                    'UTF-8'
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


            <a href="<?= htmlspecialchars(navUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>">
                🏠 Home
            </a>


            <a href="<?= htmlspecialchars(navUrl('catalog.php'), ENT_QUOTES, 'UTF-8') ?>">
                🛍️ Catalog
            </a>


            <a href="<?= htmlspecialchars(navUrl('category.php'), ENT_QUOTES, 'UTF-8') ?>">
                📂 Categories
            </a>


            <a href="<?= htmlspecialchars(navUrl('vendor.php'), ENT_QUOTES, 'UTF-8') ?>">
                🏪 Vendors
            </a>


            <?php if (!$isLoggedIn): ?>

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

    require_once __DIR__ . '/login_modal.php';

    require_once __DIR__ . '/register_modal.php';

endif;

?>


<!-- =========================================================
     NAVBAR JAVASCRIPT
     ========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /* =====================================================
           USER DROPDOWN
           ===================================================== */

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
                        userDropdown.classList.contains(
                            'show'
                        );

                    userDropdown.classList.toggle(
                        'show'
                    );

                    userButton.setAttribute(
                        'aria-expanded',
                        String(!isOpen)
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

                        userDropdown.classList.remove(
                            'show'
                        );

                        userButton.setAttribute(
                            'aria-expanded',
                            'false'
                        );

                    }

                }
            );

        }


        /* =====================================================
           MOBILE MENU
           ===================================================== */

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
                        mobileMenu.classList.contains(
                            'show'
                        );

                    mobileMenu.classList.toggle(
                        'show'
                    );

                    mobileToggle.classList.toggle(
                        'active'
                    );

                    mobileToggle.setAttribute(
                        'aria-expanded',
                        String(!isOpen)
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
    src="<?= htmlspecialchars(
        navUrl('js/modal.js'),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    defer
></script>