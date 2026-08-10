<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL NAVBAR
|--------------------------------------------------------------------------
| File:
| includes/navbar.php
|
| Required variables from header.php:
| - $currentPage
| - $isLoggedIn
| - $userName
| - $userRole
| - $userId
| - $cartCount
| - $wishlistCount
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
| HELPER
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
| USER INITIAL
|--------------------------------------------------------------------------
*/

$userInitial = 'U';

if (!empty($userName)) {
    $userInitial = strtoupper(
        substr(
            trim($userName),
            0,
            1
        )
    );
}

/*
|--------------------------------------------------------------------------
| CURRENT PAGE CHECK
|--------------------------------------------------------------------------
*/

function navActive($pages)
{
    global $currentPage;

    if (!is_array($pages)) {
        $pages = [$pages];
    }

    return in_array($currentPage, $pages, true)
        ? 'active'
        : '';
}

?>

<!-- =========================================================
     HOCHIPOHUB NAVBAR
     ========================================================= -->

<header class="site-header">

    <div class="navbar-container">

        <!-- =====================================================
             BRAND
             ===================================================== -->

        <a
            href="<?= htmlspecialchars(navUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>"
            class="brand"
            aria-label="HochipoHub Home"
        >

            <span class="brand-mark">
                H
            </span>

            <span class="brand-text">
                Hochipo<span>Hub</span>
            </span>

        </a>


        <!-- =====================================================
             DESKTOP NAVIGATION
             ===================================================== -->

        <nav
            class="main-nav"
            aria-label="Main Navigation"
        >

            <a
                href="<?= htmlspecialchars(navUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= navActive(['index.php']) ?>"
            >
                Home
            </a>

            <a
                href="<?= htmlspecialchars(navUrl('catalog.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= navActive(['catalog.php', 'product.php', 'product_details.php', 'category.php']) ?>"
            >
                Catalog
            </a>

            <a
                href="<?= htmlspecialchars(navUrl('vendor.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= navActive(['vendor.php']) ?>"
            >
                Vendors
            </a>

        </nav>


        <!-- =====================================================
             SEARCH
             ===================================================== -->

        <form
            class="navbar-search"
            action="<?= htmlspecialchars(navUrl('search.php'), ENT_QUOTES, 'UTF-8') ?>"
            method="GET"
        >

            <span class="search-icon">
                🔍
            </span>

            <input
                type="search"
                name="q"
                placeholder="Search products..."
                value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
            >

            <button
                type="submit"
            >
                Search
            </button>

        </form>


        <!-- =====================================================
             RIGHT ACTIONS
             ===================================================== -->

        <div class="navbar-actions">


            <?php if ($isLoggedIn): ?>

                <!-- =================================================
                     CART
                     ================================================= -->

                <?php if ($userRole === 'customer'): ?>

                    <a
                        href="<?= htmlspecialchars(navUrl('cart.php'), ENT_QUOTES, 'UTF-8') ?>"
                        class="nav-icon-btn"
                        aria-label="Shopping Cart"
                        title="Shopping Cart"
                    >

                        🛒

                        <?php if ($cartCount > 0): ?>

                            <span class="nav-badge">
                                <?= $cartCount > 99 ? '99+' : $cartCount ?>
                            </span>

                        <?php endif; ?>

                    </a>


                    <!-- =============================================
                         WISHLIST
                         ============================================= -->

                    <a
                        href="<?= htmlspecialchars(navUrl('wishlist.php'), ENT_QUOTES, 'UTF-8') ?>"
                        class="nav-icon-btn"
                        aria-label="Wishlist"
                        title="Wishlist"
                    >

                        ♡

                        <?php if ($wishlistCount > 0): ?>

                            <span class="nav-badge">
                                <?= $wishlistCount > 99 ? '99+' : $wishlistCount ?>
                            </span>

                        <?php endif; ?>

                    </a>

                <?php endif; ?>


                <!-- =================================================
                     USER MENU
                     ================================================= -->

                <div class="user-menu">

                    <button
                        type="button"
                        class="user-menu-button"
                        id="userMenuButton"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >

                        <span class="user-avatar">
                            <?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?>
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


                    <!-- =============================================
                         DROPDOWN
                         ============================================= -->

                    <div
                        class="user-dropdown"
                        id="userDropdown"
                    >

                        <div class="dropdown-user-info">

                            <span class="dropdown-avatar">
                                <?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?>
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
                                        ucfirst($userRole ?: 'customer'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                        </div>


                        <div class="dropdown-divider"></div>


                        <!-- Profile -->

                        <a
                            href="<?= htmlspecialchars(navUrl('profile.php'), ENT_QUOTES, 'UTF-8') ?>"
                            class="dropdown-link"
                        >

                            <span>👤</span>

                            Profile

                        </a>


                        <!-- Customer -->

                        <?php if ($userRole === 'customer'): ?>

                            <a
                                href="<?= htmlspecialchars(navUrl('order.php'), ENT_QUOTES, 'UTF-8') ?>"
                                class="dropdown-link"
                            >

                                <span>📦</span>

                                My Orders

                            </a>

                        <?php endif; ?>


                        <!-- Vendor -->

                        <?php if ($userRole === 'vendor'): ?>

                            <a
                                href="<?= htmlspecialchars(navUrl('seller/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                                class="dropdown-link"
                            >

                                <span>🏪</span>

                                Vendor Dashboard

                            </a>

                            <a
                                href="<?= htmlspecialchars(navUrl('seller/products.php'), ENT_QUOTES, 'UTF-8') ?>"
                                class="dropdown-link"
                            >

                                <span>🛍️</span>

                                My Products

                            </a>

                        <?php endif; ?>


                        <!-- Admin -->

                        <?php if ($userRole === 'admin'): ?>

                            <a
                                href="<?= htmlspecialchars(navUrl('admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                                class="dropdown-link"
                            >

                                <span>⚙️</span>

                                Admin Dashboard

                            </a>

                        <?php endif; ?>


                        <div class="dropdown-divider"></div>


                        <!-- Logout -->

                        <a
                            href="<?= htmlspecialchars(navUrl('auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                            class="dropdown-link dropdown-danger"
                        >

                            <span>↪</span>

                            Logout

                        </a>

                    </div>

                </div>


            <?php else: ?>


                <!-- =================================================
                     GUEST AUTH BUTTONS
                     ================================================= -->

                <div class="auth-buttons">

                    <a
                        href="<?= htmlspecialchars(navUrl('auth/login.php'), ENT_QUOTES, 'UTF-8') ?>"
                        class="btn-login"
                    >
                        Login
                    </a>

                    <a
                        href="<?= htmlspecialchars(navUrl('auth/register.php'), ENT_QUOTES, 'UTF-8') ?>"
                        class="btn-register"
                    >
                        Register
                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 MOBILE TOGGLE
                 ================================================= -->

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


            <!-- Mobile Search -->

            <form
                class="mobile-search"
                action="<?= htmlspecialchars(navUrl('search.php'), ENT_QUOTES, 'UTF-8') ?>"
                method="GET"
            >

                <input
                    type="search"
                    name="q"
                    placeholder="Search products..."
                    value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >

                <button type="submit">
                    🔍
                </button>

            </form>


            <!-- Mobile Navigation -->

            <a
                href="<?= htmlspecialchars(navUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= navActive(['index.php']) ?>"
            >
                🏠 Home
            </a>

            <a
                href="<?= htmlspecialchars(navUrl('catalog.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= navActive(['catalog.php', 'product.php', 'product_details.php', 'category.php']) ?>"
            >
                🛍️ Catalog
            </a>

            <a
                href="<?= htmlspecialchars(navUrl('vendor.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="<?= navActive(['vendor.php']) ?>"
            >
                🏪 Vendors
            </a>


            <?php if ($isLoggedIn): ?>


                <?php if ($userRole === 'customer'): ?>

                    <a
                        href="<?= htmlspecialchars(navUrl('cart.php'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        🛒 Cart
                        <?php if ($cartCount > 0): ?>
                            (<?= $cartCount ?>)
                        <?php endif; ?>
                    </a>

                    <a
                        href="<?= htmlspecialchars(navUrl('wishlist.php'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        ♡ Wishlist
                        <?php if ($wishlistCount > 0): ?>
                            (<?= $wishlistCount ?>)
                        <?php endif; ?>
                    </a>

                    <a
                        href="<?= htmlspecialchars(navUrl('order.php'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        📦 My Orders
                    </a>

                <?php endif; ?>


                <?php if ($userRole === 'vendor'): ?>

                    <a
                        href="<?= htmlspecialchars(navUrl('seller/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        🏪 Vendor Dashboard
                    </a>

                    <a
                        href="<?= htmlspecialchars(navUrl('seller/products.php'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        🛍️ My Products
                    </a>

                <?php endif; ?>


                <?php if ($userRole === 'admin'): ?>

                    <a
                        href="<?= htmlspecialchars(navUrl('admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        ⚙️ Admin Dashboard
                    </a>

                <?php endif; ?>


                <a
                    href="<?= htmlspecialchars(navUrl('profile.php'), ENT_QUOTES, 'UTF-8') ?>"
                >
                    👤 Profile
                </a>

                <a
                    href="<?= htmlspecialchars(navUrl('auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                >
                    ↪ Logout
                </a>


            <?php else: ?>


                <a
                    href="<?= htmlspecialchars(navUrl('auth/login.php'), ENT_QUOTES, 'UTF-8') ?>"
                    class="mobile-login-button"
                >
                    Login
                </a>

                <a
                    href="<?= htmlspecialchars(navUrl('auth/register.php'), ENT_QUOTES, 'UTF-8') ?>"
                    class="mobile-register-button"
                >
                    Register
                </a>


            <?php endif; ?>

        </div>

    </div>

</header>


<!-- =========================================================
     NAVBAR JAVASCRIPT
     ========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       USER DROPDOWN
       ===================================================== */

    const userButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');

    if (userButton && userDropdown) {

        userButton.addEventListener('click', function (event) {

            event.stopPropagation();

            const isOpen =
                userDropdown.classList.contains('show');

            userDropdown.classList.toggle('show');

            userButton.setAttribute(
                'aria-expanded',
                !isOpen
            );

        });


        document.addEventListener('click', function (event) {

            if (
                !userDropdown.contains(event.target) &&
                !userButton.contains(event.target)
            ) {

                userDropdown.classList.remove('show');

                userButton.setAttribute(
                    'aria-expanded',
                    'false'
                );

            }

        });

    }


    /* =====================================================
       MOBILE MENU
       ===================================================== */

    const mobileToggle =
        document.getElementById('mobileMenuToggle');

    const mobileMenu =
        document.getElementById('mobileMenu');


    if (mobileToggle && mobileMenu) {

        mobileToggle.addEventListener('click', function () {

            const isOpen =
                mobileMenu.classList.contains('show');

            mobileMenu.classList.toggle('show');

            mobileToggle.classList.toggle('active');

            mobileToggle.setAttribute(
                'aria-expanded',
                !isOpen
            );

        });

    }


    /* =====================================================
       CLOSE MOBILE MENU WHEN LINK CLICKED
       ===================================================== */

    const mobileLinks =
        document.querySelectorAll(
            '.mobile-menu-inner > a'
        );

    mobileLinks.forEach(function (link) {

        link.addEventListener('click', function () {

            if (mobileMenu) {
                mobileMenu.classList.remove('show');
            }

            if (mobileToggle) {
                mobileToggle.classList.remove('active');

                mobileToggle.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }

        });

    });

});

</script>