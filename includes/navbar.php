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


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (!isset($db) || !($db instanceof PDO)) {

    if (function_exists('getDB')) {
        $db = getDB();
    }

}


/*
|--------------------------------------------------------------------------
| USER SESSION
|--------------------------------------------------------------------------
*/

$isLoggedIn = isset($_SESSION['user_id']);

$userName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'User';

$userRole = $_SESSION['user_role']
    ?? $_SESSION['role']
    ?? '';

$userId = $_SESSION['user_id'] ?? null;


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = $cartCount ?? 0;
$wishlistCount = $wishlistCount ?? 0;


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl = defined('BASE_URL')
    ? BASE_URL
    : '/hochipohub/';


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage = basename(
    $_SERVER['PHP_SELF'] ?? 'index.php'
);


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$navbarCategories = [];

try {

    if (isset($db) && $db instanceof PDO) {

        $stmt = $db->query("
            SELECT
                category_id,
                category_name
            FROM categories
            ORDER BY category_name ASC
        ");

        $navbarCategories =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

} catch (PDOException $e) {

    $navbarCategories = [];

}

?>

<header class="site-header">

    <div class="navbar-container">


        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a
            href="<?= e($baseUrl) ?>index.php"
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


            <!-- HOME -->

            <a
                href="<?= e($baseUrl) ?>index.php"
                class="<?= $currentPage === 'index.php'
                    ? 'active'
                    : '' ?>"
            >
                Home
            </a>


            <!-- SHOP -->

            <a
                href="<?= e($baseUrl) ?>catalog.php"
                class="<?= $currentPage === 'catalog.php'
                    ? 'active'
                    : '' ?>"
            >
                Shop
            </a>


            <!-- =================================================
                 CATEGORIES DROPDOWN
            ================================================== -->

            <div class="nav-dropdown">

                <button
                    type="button"
                    class="nav-dropdown-button
                    <?= $currentPage === 'category.php'
                        ? 'active'
                        : '' ?>"
                    id="categoryDropdownButton"
                    aria-expanded="false"
                >

                    Categories

                    <span class="nav-chevron">
                        ▾
                    </span>

                </button>


                <div
                    class="nav-dropdown-menu"
                    id="categoryDropdownMenu"
                >

                    <!-- ALL CATEGORIES -->

                    <a
                        href="<?= e($baseUrl) ?>category.php"
                        class="category-dropdown-item
                        category-all"
                    >

                        <span class="category-item-icon">
                            ✦
                        </span>

                        <span>
                            All Categories
                        </span>

                    </a>


                    <div class="category-divider"></div>


                    <?php if (
                        !empty($navbarCategories)
                    ): ?>


                        <?php foreach (
                            $navbarCategories
                            as $navCategory
                        ): ?>

                            <a
                                href="<?= e(
                                    $baseUrl
                                ) ?>category.php?category_id=<?= (int)
                                    $navCategory[
                                        'category_id'
                                    ] ?>"
                                class="category-dropdown-item"
                            >

                                <span class="category-item-icon">
                                    ▪
                                </span>

                                <span>
                                    <?= e(
                                        $navCategory[
                                            'category_name'
                                        ]
                                    ) ?>
                                </span>

                            </a>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <div
                            class="category-empty"
                        >

                            No categories available.

                        </div>

                    <?php endif; ?>


                </div>

            </div>


            <!-- VENDORS -->

            <a
                href="<?= e($baseUrl) ?>vendor.php"
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
            action="<?= e($baseUrl) ?>search.php"
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
                value="<?= e(
                    $_GET['q'] ?? ''
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


            <!-- WISHLIST -->

            <a
                href="<?= e($baseUrl) ?>wishlist.php"
                class="nav-icon-btn"
                aria-label="Wishlist"
                title="Wishlist"
            >

                <span>
                    ♡
                </span>

                <?php if (
                    $wishlistCount > 0
                ): ?>

                    <small class="nav-badge">

                        <?= $wishlistCount > 99
                            ? '99+'
                            : (int) $wishlistCount ?>

                    </small>

                <?php endif; ?>

            </a>


            <!-- CART -->

            <a
                href="<?= e($baseUrl) ?>cart.php"
                class="nav-icon-btn"
                aria-label="Cart"
                title="Shopping Cart"
            >

                <span>
                    🛒
                </span>

                <?php if (
                    $cartCount > 0
                ): ?>

                    <small class="nav-badge">

                        <?= $cartCount > 99
                            ? '99+'
                            : (int) $cartCount ?>

                    </small>

                <?php endif; ?>

            </a>


            <!-- =================================================
                 LOGGED IN USER
            ================================================== -->

            <?php if ($isLoggedIn): ?>

                <div class="user-menu">


                    <button
                        type="button"
                        class="user-menu-button"
                        id="userMenuButton"
                    >

                        <span class="user-avatar">

                            <?= e(
                                strtoupper(
                                    substr(
                                        trim($userName),
                                        0,
                                        1
                                    )
                                )
                            ) ?>

                        </span>

                        <span class="user-menu-name">

                            <?= e($userName) ?>

                        </span>

                        <span class="user-chevron">
                            ▾
                        </span>

                    </button>


                    <div
                        class="user-dropdown"
                        id="userDropdown"
                    >


                        <div
                            class="dropdown-user-info"
                        >

                            <span
                                class="dropdown-avatar"
                            >

                                <?= e(
                                    strtoupper(
                                        substr(
                                            trim(
                                                $userName
                                            ),
                                            0,
                                            1
                                        )
                                    )
                                ) ?>

                            </span>


                            <div>

                                <strong>
                                    <?= e(
                                        $userName
                                    ) ?>
                                </strong>

                                <small>

                                    <?= e(
                                        ucfirst(
                                            $userRole
                                            ?: 'customer'
                                        )
                                    ) ?>

                                </small>

                            </div>

                        </div>


                        <div
                            class="dropdown-divider"
                        ></div>


                        <!-- PROFILE -->

                        <a
                            href="<?= e(
                                $baseUrl
                            ) ?>profile.php"
                            class="dropdown-link"
                        >

                            <span>
                                👤
                            </span>

                            My Profile

                        </a>


                        <!-- CUSTOMER -->

                        <?php if (
                            $userRole === 'customer'
                        ): ?>

                            <a
                                href="<?= e(
                                    $baseUrl
                                ) ?>order.php"
                                class="dropdown-link"
                            >

                                <span>
                                    📦
                                </span>

                                My Orders

                            </a>


                            <a
                                href="<?= e(
                                    $baseUrl
                                ) ?>wishlist.php"
                                class="dropdown-link"
                            >

                                <span>
                                    ♡
                                </span>

                                Wishlist

                            </a>

                        <?php endif; ?>


                        <!-- VENDOR -->

                        <?php if (
                            $userRole === 'vendor'
                        ): ?>

                            <a
                                href="<?= e(
                                    $baseUrl
                                ) ?>seller/dashboard.php"
                                class="dropdown-link"
                            >

                                <span>
                                    📊
                                </span>

                                Seller Center

                            </a>

                        <?php endif; ?>


                        <!-- ADMIN -->

                        <?php if (
                            $userRole === 'admin'
                        ): ?>

                            <a
                                href="<?= e(
                                    $baseUrl
                                ) ?>admin/dashboard.php"
                                class="dropdown-link"
                            >

                                <span>
                                    ⚙️
                                </span>

                                Admin Panel

                            </a>

                        <?php endif; ?>


                        <div
                            class="dropdown-divider"
                        ></div>


                        <!-- LOGOUT -->

                        <a
                            href="<?= e(
                                $baseUrl
                            ) ?>auth/logout.php"
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
                     LOGIN / REGISTER
                ================================================== -->

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


            <!-- =================================================
                 MOBILE MENU BUTTON
            ================================================== -->

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


    <!-- =========================================================
         MOBILE NAVIGATION
    ========================================================== -->

    <div
        class="mobile-menu"
        id="mobileMenu"
    >

        <div class="mobile-menu-inner">


            <!-- MOBILE SEARCH -->

            <form
                action="<?= e($baseUrl) ?>search.php"
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


            <!-- MOBILE LINKS -->

            <a
                href="<?= e($baseUrl) ?>index.php"
            >
                Home
            </a>


            <a
                href="<?= e($baseUrl) ?>catalog.php"
            >
                Shop
            </a>


            <!-- MOBILE CATEGORIES -->

            <div class="mobile-category-section">

                <button
                    type="button"
                    class="mobile-category-button"
                    id="mobileCategoryButton"
                >

                    <span>
                        Categories
                    </span>

                    <span>
                        ▾
                    </span>

                </button>


                <div
                    class="mobile-category-list"
                    id="mobileCategoryList"
                >

                    <a
                        href="<?= e(
                            $baseUrl
                        ) ?>category.php"
                    >
                        All Categories
                    </a>


                    <?php foreach (
                        $navbarCategories
                        as $navCategory
                    ): ?>

                        <a
                            href="<?= e(
                                $baseUrl
                            ) ?>category.php?category_id=<?= (int)
                                $navCategory[
                                    'category_id'
                                ] ?>"
                        >

                            <?= e(
                                $navCategory[
                                    'category_name'
                                ]
                            ) ?>

                        </a>

                    <?php endforeach; ?>

                </div>

            </div>


            <a
                href="<?= e($baseUrl) ?>vendor.php"
            >
                Vendors
            </a>


            <a
                href="<?= e($baseUrl) ?>wishlist.php"
            >
                Wishlist
            </a>


            <a
                href="<?= e($baseUrl) ?>cart.php"
            >
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


                <a
                    href="<?= e(
                        $baseUrl
                    ) ?>profile.php"
                >
                    My Profile
                </a>


                <a
                    href="<?= e(
                        $baseUrl
                    ) ?>auth/logout.php"
                >
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

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | CATEGORY DROPDOWN
        |--------------------------------------------------------------------------
        */

        const categoryButton =
            document.getElementById(
                'categoryDropdownButton'
            );

        const categoryMenu =
            document.getElementById(
                'categoryDropdownMenu'
            );


        if (
            categoryButton &&
            categoryMenu
        ) {

            categoryButton.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    const isOpen =
                        categoryMenu.classList.contains(
                            'show'
                        );


                    /*
                    | Close user dropdown
                    */

                    const userDropdown =
                        document.getElementById(
                            'userDropdown'
                        );

                    if (userDropdown) {

                        userDropdown.classList.remove(
                            'show'
                        );

                    }


                    /*
                    | Toggle category dropdown
                    */

                    categoryMenu.classList.toggle(
                        'show'
                    );

                    categoryButton.setAttribute(
                        'aria-expanded',
                        !isOpen
                    );

                }
            );


            categoryMenu.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                }
            );

        }


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


                    /*
                    | Close category dropdown
                    */

                    if (
                        categoryMenu
                    ) {

                        categoryMenu.classList.remove(
                            'show'
                        );

                    }


                    userDropdown.classList.toggle(
                        'show'
                    );

                }
            );


            userDropdown.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CLOSE DROPDOWNS WHEN CLICK OUTSIDE
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function () {

                if (categoryMenu) {

                    categoryMenu.classList.remove(
                        'show'
                    );

                    categoryButton?.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }


                if (userDropdown) {

                    userDropdown.classList.remove(
                        'show'
                    );

                }

            }
        );


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


        /*
        |--------------------------------------------------------------------------
        | MOBILE CATEGORY
        |--------------------------------------------------------------------------
        */

        const mobileCategoryButton =
            document.getElementById(
                'mobileCategoryButton'
            );

        const mobileCategoryList =
            document.getElementById(
                'mobileCategoryList'
            );


        if (
            mobileCategoryButton &&
            mobileCategoryList
        ) {

            mobileCategoryButton.addEventListener(
                'click',
                function () {

                    mobileCategoryList.classList.toggle(
                        'show'
                    );

                    mobileCategoryButton.classList.toggle(
                        'active'
                    );

                }
            );

        }

    }
);

</script>