<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL NAVBAR
|--------------------------------------------------------------------------
| File:
| includes/navbar.php
|
| Works with:
| /index.php
| /catalog.php
| /category.php
| /vendor.php
| /cart.php
| /wishlist.php
| /profile.php
| /order.php
| /seller/*
| /admin/*
| /auth/*
|
| LOGIN & REGISTER:
| Uses existing login_modal.php and register_modal.php.
| There is NO auth/login.php or auth/register.php.
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
| ACTIVE NAVIGATION HELPER
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
    $cleanName = trim(
        $userName
    );
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
     HOCHIPOHUB NAVBAR
     ========================================================= -->
<header class="site-header">
    <div class="navbar-container">
        <!-- =====================================================
             BRAND / LOGO
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
            <span class="brand-mark">
                H
            </span>
            <span class="brand-text">
                Hochipo<span>Hub</span>
            </span>
        </a>
        <!-- =====================================================
             MAIN NAVIGATION
             ===================================================== -->
        <nav
            class="main-nav"
            aria-label="Main Navigation"
        >
            <!-- HOME -->
            <a
                href="<?= htmlspecialchars(
                    navUrl('index.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive([
                    'index.php'
                ]) ?>"
            >
                Home
            </a>
            <!-- CATALOG -->
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
            <!-- CATEGORIES -->
            <a
                href="<?= htmlspecialchars(
                    navUrl('category.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive([
                    'category.php'
                ]) ?>"
            >
                Categories
            </a>
            <!-- VENDORS -->
            <a
                href="<?= htmlspecialchars(
                    navUrl('vendor.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive([
                    'vendor.php'
                ]) ?>"
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
                <!-- =================================================
                     CUSTOMER CART + WISHLIST
                     ================================================= -->
                <?php if ($userRole === 'customer'): ?>
                    <!-- CART -->
                    <a
                        href="<?= htmlspecialchars(
                            navUrl('cart.php'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="nav-icon-btn"
                        aria-label="Shopping Cart"
                        title="Shopping Cart"
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
                        href="<?= htmlspecialchars(
                            navUrl('wishlist.php'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="nav-icon-btn"
                        aria-label="Wishlist"
                        title="Wishlist"
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
                    <!-- =================================================
                         USER DROPDOWN
                         ================================================= -->
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
                        <!-- PROFILE -->
                        <a
                            href="<?= htmlspecialchars(
                                navUrl('profile.php'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="dropdown-link"
                        >
                            <span>
                                👤
                            </span>
                            Profile
                        </a>
                        <!-- CUSTOMER ORDERS -->
                        <?php if ($userRole === 'customer'): ?>
                            <a
                                href="<?= htmlspecialchars(
                                    navUrl('order.php'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="dropdown-link"
                            >
                                <span>
                                    📦
                                </span>
                                My Orders
                            </a>
                        <?php endif; ?>
                        <!-- =================================================
                             VENDOR
                             ================================================= -->
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
                                <span>
                                    🏪
                                </span>
                                Vendor Dashboard
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
                                <span>
                                    🛍️
                                </span>
                                My Products
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
                                <span>
                                    📦
                                </span>
                                Orders
                            </a>
                        <?php endif; ?>
                        <!-- =================================================
                             ADMIN
                             ================================================= -->
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
                                <span>
                                    ⚙️
                                </span>
                                Admin Dashboard
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
                                <span>
                                    🛍️
                                </span>
                                Manage Products
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
                                <span>
                                    👥
                                </span>
                                Manage Users
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <!-- LOGOUT -->
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
                            <span>
                                ↪
                            </span>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- =================================================
                     GUEST AUTH BUTTONS
                     =================================================
                     
                     IMPORTANT:
                     There is NO:
                     auth/login.php
                     auth/register.php
                     Login/Register use existing modals.
                     ================================================= -->
                <div class="auth-buttons">
                    <!-- LOGIN -->
                    <button
                        type="button"
                        class="btn-login"
                        data-modal-open="loginModal"
                    >
                        Login
                    </button>
                    <!-- REGISTER -->
                    <button
                        type="button"
                        class="btn-register"
                        data-modal-open="registerModal"
                    >
                        Register
                    </button>
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
            <!-- MOBILE SEARCH -->
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
                    value="<?= htmlspecialchars(
                        $_GET['q'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                <button type="submit">
                    🔍
                </button>
            </form>
            <!-- HOME -->
            <a
                href="<?= htmlspecialchars(
                    navUrl('index.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive([
                    'index.php'
                ]) ?>"
            >
                🏠 Home
            </a>
            <!-- CATALOG -->
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
                🛍️ Catalog
            </a>
            <!-- CATEGORIES -->
            <a
                href="<?= htmlspecialchars(
                    navUrl('category.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive([
                    'category.php'
                ]) ?>"
            >
                📂 Categories
            </a>
            <!-- VENDORS -->
            <a
                href="<?= htmlspecialchars(
                    navUrl('vendor.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= navActive([
                    'vendor.php'
                ]) ?>"
            >
                🏪 Vendors
            </a>
            <?php if ($isLoggedIn): ?>
                <!-- =================================================
                     CUSTOMER MOBILE
                     ================================================= -->
                <?php if ($userRole === 'customer'): ?>
                    <!-- CART -->
                    <a
                        href="<?= htmlspecialchars(
                            navUrl('cart.php'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        🛒 Cart
                        <?php if ($cartCount > 0): ?>
                            (<?= $cartCount ?>)
                        <?php endif; ?>
                    </a>
                    <!-- WISHLIST -->
                    <a
                        href="<?= htmlspecialchars(
                            navUrl('wishlist.php'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        ♡ Wishlist
                        <?php if ($wishlistCount > 0): ?>
                            (<?= $wishlistCount ?>)
                        <?php endif; ?>
                    </a>
                    <!-- ORDERS -->
                    <a
                        href="<?= htmlspecialchars(
                            navUrl('order.php'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        📦 My Orders
                    </a>
                <?php endif; ?>
                <!-- =================================================
                     VENDOR MOBILE
                     ================================================= -->
                <?php if ($userRole === 'vendor'): ?>
                    <a
                        href="<?= htmlspecialchars(
                            navUrl(
                                'seller/dashboard.php'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
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
                    >
                        📦 Orders
                    </a>
                <?php endif; ?>
                <!-- =================================================
                     ADMIN MOBILE
                     ================================================= -->
                <?php if ($userRole === 'admin'): ?>
                    <a
                        href="<?= htmlspecialchars(
                            navUrl(
                                'admin/dashboard.php'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        ⚙️ Admin Dashboard
                    </a>
                <?php endif; ?>
                <!-- PROFILE -->
                <a
                    href="<?= htmlspecialchars(
                        navUrl('profile.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    👤 Profile
                </a>
                <!-- LOGOUT -->
                <a
                    href="<?= htmlspecialchars(
                        navUrl(
                            'auth/logout.php'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    ↪ Logout
                </a>
            <?php else: ?>
                <!-- =================================================
                     GUEST MOBILE AUTH
                     ================================================= -->
                <!-- LOGIN -->
                <button
                    type="button"
                    class="mobile-login-button"
                    data-modal-open="loginModal"
                >
                    Login
                </button>
                <!-- REGISTER -->
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
                        )
                        &&
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
        /*
        |--------------------------------------------------------------------------
        | CLOSE MOBILE MENU AFTER NORMAL LINK CLICK
        |--------------------------------------------------------------------------
        */
        const mobileLinks =
            document.querySelectorAll(
                '.mobile-menu-inner > a'
            );
        mobileLinks.forEach(
            function (link) {
                link.addEventListener(
                    'click',
                    function () {
                        if (mobileMenu) {
                            mobileMenu.classList.remove(
                                'show'
                            );
                        }
                        if (mobileToggle) {
                            mobileToggle.classList.remove(
                                'active'
                            );
                            mobileToggle.setAttribute(
                                'aria-expanded',
                                'false'
                            );
                        }
                    }
                );
            }
        );
        /*
        |--------------------------------------------------------------------------
        | CLOSE MOBILE MENU WHEN LOGIN / REGISTER IS CLICKED
        |--------------------------------------------------------------------------
        */
        const mobileAuthButtons =
            document.querySelectorAll(
                '.mobile-login-button, .mobile-register-button'
            );
        mobileAuthButtons.forEach(
            function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        if (mobileMenu) {
                            mobileMenu.classList.remove(
                                'show'
                            );
                        }
                        if (mobileToggle) {
                            mobileToggle.classList.remove(
                                'active'
                            );
                            mobileToggle.setAttribute(
                                'aria-expanded',
                                'false'
                            );
                        }
                    }
                );
            }
        );
    }
);
</script>