<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - MAIN NAVBAR
|--------------------------------------------------------------------------
| File:
| includes/navbar.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);

$userName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'User';

$userRole = $_SESSION['user_role']
    ?? $_SESSION['role']
    ?? '';

$userId = $_SESSION['user_id'] ?? null;

$cartCount = $cartCount ?? 0;
$wishlistCount = $wishlistCount ?? 0;

$baseUrl = defined('BASE_URL')
    ? BASE_URL
    : '/hochipohub/';

$currentPage = basename(
    $_SERVER['PHP_SELF'] ?? 'index.php'
);

?>

<header class="site-header">

    <div class="navbar-container">

        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a
            href="<?= $baseUrl ?>index.php"
            class="brand"
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
        ====================================================== -->

        <nav class="main-nav">

            <a
                href="<?= $baseUrl ?>index.php"
                class="<?= $currentPage === 'index.php'
                    ? 'active'
                    : '' ?>"
            >
                Home
            </a>

            <a
                href="<?= $baseUrl ?>catalog.php"
                class="<?= $currentPage === 'catalog.php'
                    ? 'active'
                    : '' ?>"
            >
                Shop
            </a>

            <a
                href="<?= $baseUrl ?>category.php"
                class="<?= $currentPage === 'category.php'
                    ? 'active'
                    : '' ?>"
            >
                Categories
            </a>

            <a
                href="<?= $baseUrl ?>vendor.php"
                class="<?= $currentPage === 'vendor.php'
                    ? 'active'
                    : '' ?>"
            >
                Vendors
            </a>

        </nav>


        <!-- =====================================================
             SEARCH
        ====================================================== -->

        <form
            action="<?= $baseUrl ?>search.php"
            method="GET"
            class="navbar-search"
        >

            <span class="search-icon">
                🔎
            </span>

            <input
                type="search"
                name="q"
                placeholder="Search products, vendors..."
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
             ACTIONS
        ====================================================== -->

        <div class="navbar-actions">


            <!-- Wishlist -->

            <a
                href="<?= $baseUrl ?>wishlist.php"
                class="nav-icon-btn"
                aria-label="Wishlist"
                title="Wishlist"
            >

                <span>
                    ♡
                </span>

                <?php if ($wishlistCount > 0): ?>

                    <small class="nav-badge">
                        <?= $wishlistCount > 99
                            ? '99+'
                            : $wishlistCount ?>
                    </small>

                <?php endif; ?>

            </a>


            <!-- Cart -->

            <a
                href="<?= $baseUrl ?>cart.php"
                class="nav-icon-btn"
                aria-label="Cart"
                title="Shopping Cart"
            >

                <span>
                    🛒
                </span>

                <?php if ($cartCount > 0): ?>

                    <small class="nav-badge">
                        <?= $cartCount > 99
                            ? '99+'
                            : $cartCount ?>
                    </small>

                <?php endif; ?>

            </a>


            <?php if ($isLoggedIn): ?>

                <!-- USER -->

                <div class="user-menu">

                    <button
                        type="button"
                        class="user-menu-button"
                        id="userMenuButton"
                    >

                        <span class="user-avatar">
                            <?= strtoupper(
                                substr(
                                    trim($userName),
                                    0,
                                    1
                                )
                            ) ?>
                        </span>

                        <span class="user-menu-name">

                            <?= htmlspecialchars(
                                $userName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                        <span class="user-chevron">
                            ▾
                        </span>

                    </button>


                    <div
                        class="user-dropdown"
                        id="userDropdown"
                    >

                        <div class="dropdown-user-info">

                            <span class="dropdown-avatar">

                                <?= strtoupper(
                                    substr(
                                        trim($userName),
                                        0,
                                        1
                                    )
                                ) ?>

                            </span>

                            <div>

                                <strong>
                                    <?= htmlspecialchars(
                                        $userName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $userRole ?: 'customer'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </div>

                        </div>


                        <div class="dropdown-divider"></div>


                        <a
                            href="<?= $baseUrl ?>profile.php"
                            class="dropdown-link"
                        >
                            <span>👤</span>
                            My Profile
                        </a>


                        <?php if ($userRole === 'customer'): ?>

                            <a
                                href="<?= $baseUrl ?>order.php"
                                class="dropdown-link"
                            >
                                <span>📦</span>
                                My Orders
                            </a>

                            <a
                                href="<?= $baseUrl ?>wishlist.php"
                                class="dropdown-link"
                            >
                                <span>♡</span>
                                Wishlist
                            </a>

                        <?php endif; ?>


                        <?php if ($userRole === 'vendor'): ?>

                            <a
                                href="<?= $baseUrl ?>seller/dashboard.php"
                                class="dropdown-link"
                            >
                                <span>📊</span>
                                Seller Center
                            </a>

                        <?php endif; ?>


                        <?php if ($userRole === 'admin'): ?>

                            <a
                                href="<?= $baseUrl ?>admin/dashboard.php"
                                class="dropdown-link"
                            >
                                <span>⚙️</span>
                                Admin Panel
                            </a>

                        <?php endif; ?>


                        <div class="dropdown-divider"></div>


                        <a
                            href="<?= $baseUrl ?>auth/logout.php"
                            class="dropdown-link dropdown-danger"
                        >
                            <span>↪</span>
                            Logout
                        </a>

                    </div>

                </div>


            <?php else: ?>

                <!-- AUTH BUTTONS -->

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


            <!-- MOBILE MENU -->

            <button
                type="button"
                class="mobile-menu-toggle"
                id="mobileMenuToggle"
                aria-label="Open menu"
            >

                <span></span>
                <span></span>
                <span></span>

            </button>

        </div>

    </div>


    <!-- =====================================================
         MOBILE NAVIGATION
    ====================================================== -->

    <div
        class="mobile-menu"
        id="mobileMenu"
    >

        <div class="mobile-menu-inner">

            <form
                action="<?= $baseUrl ?>search.php"
                method="GET"
                class="mobile-search"
            >

                <input
                    type="search"
                    name="q"
                    placeholder="Search..."
                >

                <button type="submit">
                    🔎
                </button>

            </form>


            <a href="<?= $baseUrl ?>index.php">
                Home
            </a>

            <a href="<?= $baseUrl ?>catalog.php">
                Shop
            </a>

            <a href="<?= $baseUrl ?>category.php">
                Categories
            </a>

            <a href="<?= $baseUrl ?>vendor.php">
                Vendors
            </a>

            <a href="<?= $baseUrl ?>wishlist.php">
                Wishlist
            </a>

            <a href="<?= $baseUrl ?>cart.php">
                Cart
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
                    Create Account
                </button>

            <?php else: ?>

                <a href="<?= $baseUrl ?>profile.php">
                    My Profile
                </a>

                <a href="<?= $baseUrl ?>auth/logout.php">
                    Logout
                </a>

            <?php endif; ?>

        </div>

    </div>

</header>


<!-- =========================================================
     NAVBAR JAVASCRIPT
========================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | USER DROPDOWN
    |--------------------------------------------------------------------------
    */

    const userButton =
        document.getElementById('userMenuButton');

    const userDropdown =
        document.getElementById('userDropdown');


    if (userButton && userDropdown) {

        userButton.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                userDropdown.classList.toggle(
                    'show'
                );

            }
        );


        document.addEventListener(
            'click',
            function () {

                userDropdown.classList.remove(
                    'show'
                );

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


    if (mobileToggle && mobileMenu) {

        mobileToggle.addEventListener(
            'click',
            function () {

                mobileToggle.classList.toggle(
                    'active'
                );

                mobileMenu.classList.toggle(
                    'show'
                );

                document.body.classList.toggle(
                    'menu-open'
                );

            }
        );

    }

});

</script>