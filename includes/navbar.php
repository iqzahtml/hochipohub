<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Main Navigation Bar
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/session.php';


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$loggedIn =
    isLoggedIn();

$userRole =
    currentUserRole();

$userName =
    currentUserName();


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER['PHP_SELF'] ?? 'index.php'
    );


/*
|--------------------------------------------------------------------------
| ACTIVE LINK HELPER
|--------------------------------------------------------------------------
*/

function navActive(
    string $page
): string {

    global $currentPage;

    return $currentPage === $page
        ? 'active'
        : '';
}


/*
|--------------------------------------------------------------------------
| CART / WISHLIST COUNT
|--------------------------------------------------------------------------
*/

$cartCount = 0;

$wishlistCount = 0;

if (
    $loggedIn
    &&
    $userRole === 'customer'
) {

    /*
     * functions.php may already provide
     * these functions.
     */

    if (
        function_exists(
            'getCartCount'
        )
    ) {

        $cartCount =
            (int) getCartCount(
                (int) currentUserId()
            );
    }


    if (
        function_exists(
            'getWishlistCount'
        )
    ) {

        $wishlistCount =
            (int) getWishlistCount(
                (int) currentUserId()
            );
    }
}

?>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="site-header">

    <nav
        class="main-navbar"
        aria-label="Main Navigation"
    >


        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="navbar-brand">

            <a
                href="<?php echo BASE_URL; ?>index.php"
                class="brand-link"
            >

                <div class="brand-logo">

                    <img
                        src="<?php echo BASE_URL; ?>image/logo.jpg"
                        alt="HochipoHub Logo"
                    >

                </div>


                <div class="brand-text">

                    <span class="brand-name">
                        HochipoHub
                    </span>

                    <span class="brand-tagline">
                        Shop Local. Think Big.
                    </span>

                </div>

            </a>

        </div>


        <!-- =================================================
             MOBILE MENU BUTTON
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


        <!-- =================================================
             NAVIGATION
        ================================================== -->

        <div
            class="navbar-menu"
            id="navbarMenu"
        >


            <ul class="navbar-links">


                <!-- HOME -->

                <li>

                    <a
                        href="<?php echo BASE_URL; ?>index.php"
                        class="<?php echo navActive('index.php'); ?>"
                    >

                        <i class="fa-solid fa-house"></i>

                        <span>
                            Home
                        </span>

                    </a>

                </li>


                <!-- CATALOG -->

                <li>

                    <a
                        href="<?php echo BASE_URL; ?>catalog.php"
                        class="<?php echo navActive('catalog.php'); ?>"
                    >

                        <i class="fa-solid fa-compass"></i>

                        <span>
                            Catalog
                        </span>

                    </a>

                </li>


                <!-- VENDORS -->

                <li>

                    <a
                        href="<?php echo BASE_URL; ?>vendor.php"
                        class="<?php echo navActive('vendor.php'); ?>"
                    >

                        <i class="fa-solid fa-store"></i>

                        <span>
                            Vendors
                        </span>

                    </a>

                </li>


                <?php if (
                    $loggedIn
                    &&
                    $userRole === 'customer'
                ): ?>


                    <!-- CUSTOMER CART -->

                    <li>

                        <a
                            href="<?php echo BASE_URL; ?>cart.php"
                            class="<?php echo navActive('cart.php'); ?>"
                        >

                            <span class="nav-icon-wrapper">

                                <i class="fa-solid fa-cart-shopping"></i>

                                <?php if (
                                    $cartCount > 0
                                ): ?>

                                    <span class="nav-badge">

                                        <?php echo $cartCount > 99
                                            ? '99+'
                                            : $cartCount; ?>

                                    </span>

                                <?php endif; ?>

                            </span>

                            <span>
                                Cart
                            </span>

                        </a>

                    </li>


                    <!-- CUSTOMER WISHLIST -->

                    <li>

                        <a
                            href="<?php echo BASE_URL; ?>wishlist.php"
                            class="<?php echo navActive('wishlist.php'); ?>"
                        >

                            <span class="nav-icon-wrapper">

                                <i class="fa-solid fa-heart"></i>

                                <?php if (
                                    $wishlistCount > 0
                                ): ?>

                                    <span class="nav-badge">

                                        <?php echo $wishlistCount > 99
                                            ? '99+'
                                            : $wishlistCount; ?>

                                    </span>

                                <?php endif; ?>

                            </span>

                            <span>
                                Wishlist
                            </span>

                        </a>

                    </li>


                <?php endif; ?>


                <?php if (
                    $loggedIn
                    &&
                    $userRole === 'vendor'
                ): ?>


                    <!-- VENDOR DASHBOARD -->

                    <li>

                        <a
                            href="<?php echo BASE_URL; ?>seller/dashboard.php"
                        >

                            <i class="fa-solid fa-chart-line"></i>

                            <span>
                                Seller Dashboard
                            </span>

                        </a>

                    </li>


                    <!-- VENDOR PRODUCTS -->

                    <li>

                        <a
                            href="<?php echo BASE_URL; ?>seller/products.php"
                        >

                            <i class="fa-solid fa-box"></i>

                            <span>
                                My Products
                            </span>

                        </a>

                    </li>


                <?php endif; ?>


                <?php if (
                    $loggedIn
                    &&
                    $userRole === 'admin'
                ): ?>


                    <!-- ADMIN DASHBOARD -->

                    <li>

                        <a
                            href="<?php echo BASE_URL; ?>admin/dashboard.php"
                            class="<?php echo navActive('dashboard.php'); ?>"
                        >

                            <i class="fa-solid fa-gauge-high"></i>

                            <span>
                                Admin Dashboard
                            </span>

                        </a>

                    </li>


                <?php endif; ?>

            </ul>


            <!-- =================================================
                 RIGHT SIDE
            ================================================== -->

            <div class="navbar-actions">


                <!-- SEARCH -->

                <a
                    href="<?php echo BASE_URL; ?>search.php"
                    class="navbar-search-btn"
                    aria-label="Search products"
                    title="Search"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                </a>


                <?php if (
                    !$loggedIn
                ): ?>


                    <!-- LOGIN -->

                    <a
                        href="<?php echo BASE_URL; ?>login.php"
                        class="nav-login-btn"
                    >

                        <i class="fa-solid fa-right-to-bracket"></i>

                        <span>
                            Login
                        </span>

                    </a>


                    <!-- REGISTER -->

                    <a
                        href="<?php echo BASE_URL; ?>register.php"
                        class="nav-register-btn"
                    >

                        <span>
                            Join Us
                        </span>

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>


                <?php else: ?>


                    <!-- USER DROPDOWN -->

                    <div
                        class="navbar-user"
                        id="navbarUser"
                    >

                        <button
                            type="button"
                            class="navbar-user-button"
                            id="navbarUserButton"
                            aria-expanded="false"
                        >

                            <div class="user-avatar">

                                <?php

                                $profileImage =
                                    '';

                                if (
                                    function_exists(
                                        'getCurrentUser'
                                    )
                                ) {

                                    $currentUser =
                                        getCurrentUser();

                                    if (
                                        !empty(
                                            $currentUser[
                                                'profile_image'
                                            ]
                                        )
                                    ) {

                                        $profileImage =
                                            $currentUser[
                                                'profile_image'
                                            ];
                                    }
                                }

                                ?>


                                <?php if (
                                    $profileImage !== ''
                                ): ?>

                                    <img
                                        src="<?php echo BASE_URL; ?>image/<?php echo htmlspecialchars(
                                            $profileImage,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        alt="Profile"
                                    >

                                <?php else: ?>

                                    <span>

                                        <?php

                                        echo strtoupper(
                                            substr(
                                                $userName !== ''
                                                    ? $userName
                                                    : 'U',
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="user-info">

                                <strong>

                                    <?php echo htmlspecialchars(
                                        $userName !== ''
                                            ? $userName
                                            : 'User',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>


                                <small>

                                    <?php

                                    echo htmlspecialchars(
                                        ucfirst(
                                            $userRole
                                            ?? 'user'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </small>

                            </div>


                            <i class="fa-solid fa-chevron-down user-chevron"></i>

                        </button>


                        <!-- USER DROPDOWN -->

                        <div
                            class="user-dropdown"
                            id="userDropdown"
                        >


                            <?php if (
                                $userRole === 'customer'
                            ): ?>

                                <a
                                    href="<?php echo BASE_URL; ?>dashboard.php"
                                >

                                    <i class="fa-solid fa-gauge"></i>

                                    <span>
                                        Dashboard
                                    </span>

                                </a>

                            <?php elseif (
                                $userRole === 'vendor'
                            ): ?>

                                <a
                                    href="<?php echo BASE_URL; ?>seller/dashboard.php"
                                >

                                    <i class="fa-solid fa-gauge"></i>

                                    <span>
                                        Seller Dashboard
                                    </span>

                                </a>

                            <?php elseif (
                                $userRole === 'admin'
                            ): ?>

                                <a
                                    href="<?php echo BASE_URL; ?>admin/dashboard.php"
                                >

                                    <i class="fa-solid fa-gauge-high"></i>

                                    <span>
                                        Admin Dashboard
                                    </span>

                                </a>

                            <?php endif; ?>


                            <a
                                href="<?php echo BASE_URL; ?>profile.php"
                            >

                                <i class="fa-solid fa-user"></i>

                                <span>
                                    My Profile
                                </span>

                            </a>


                            <div class="dropdown-divider"></div>


                            <a
                                href="<?php echo BASE_URL; ?>auth/logout.php"
                                class="dropdown-logout"
                            >

                                <i class="fa-solid fa-right-from-bracket"></i>

                                <span>
                                    Logout
                                </span>

                            </a>

                        </div>

                    </div>


                <?php endif; ?>

            </div>

        </div>

    </nav>

</header>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="mobile-menu-overlay"
    id="mobileMenuOverlay"
></div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | MOBILE MENU
        |--------------------------------------------------------------------------
        */

        const toggle =
            document.getElementById(
                'mobileMenuToggle'
            );

        const menu =
            document.getElementById(
                'navbarMenu'
            );

        const overlay =
            document.getElementById(
                'mobileMenuOverlay'
            );


        if (
            toggle
            &&
            menu
        ) {

            toggle.addEventListener(
                'click',
                function () {

                    const opened =
                        menu.classList.toggle(
                            'mobile-open'
                        );

                    toggle.classList.toggle(
                        'active',
                        opened
                    );

                    toggle.setAttribute(
                        'aria-expanded',
                        opened
                            ? 'true'
                            : 'false'
                    );

                    if (overlay) {

                        overlay.classList.toggle(
                            'active',
                            opened
                        );
                    }

                    document.body.classList.toggle(
                        'menu-open',
                        opened
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CLOSE MOBILE MENU
        |--------------------------------------------------------------------------
        */

        if (overlay) {

            overlay.addEventListener(
                'click',
                function () {

                    menu?.classList.remove(
                        'mobile-open'
                    );

                    toggle?.classList.remove(
                        'active'
                    );

                    overlay.classList.remove(
                        'active'
                    );

                    document.body.classList.remove(
                        'menu-open'
                    );

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
                'navbarUserButton'
            );

        const userDropdown =
            document.getElementById(
                'userDropdown'
            );


        if (
            userButton
            &&
            userDropdown
        ) {

            userButton.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();


                    const opened =
                        userDropdown.classList.toggle(
                            'show'
                        );


                    userButton.setAttribute(
                        'aria-expanded',
                        opened
                            ? 'true'
                            : 'false'
                    );

                }
            );


            document.addEventListener(
                'click',
                function () {

                    userDropdown.classList.remove(
                        'show'
                    );

                    userButton.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }
            );

        }

    }
);

</script>