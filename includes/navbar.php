<?php
/*
 * HOCHIPOHUB
 * includes/navbar.php
 *
 * Main navigation
 *
 * Supported roles:
 * - customer
 * - vendor
 * - admin
 * - guest
 */


/*
 * START SESSION SAFELY
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
 * USER LOGIN STATUS
 */

$isLoggedIn = isset($_SESSION['user_id']);

$userName = $_SESSION['name'] ?? '';

$userRole = $_SESSION['role'] ?? 'customer';


/*
 * PROFILE IMAGE
 */

$userProfileImage =
    $_SESSION['profile_image'] ?? '';


/*
 * CURRENT PAGE
 */

$currentPage =
    basename($_SERVER['PHP_SELF']);


/*
 * CART COUNT
 *
 * If cart count already exists in session,
 * use it.
 */

$cartCount =
    isset($_SESSION['cart_count'])
        ? (int) $_SESSION['cart_count']
        : 0;


/*
 * WISHLIST COUNT
 */

$wishlistCount =
    isset($_SESSION['wishlist_count'])
        ? (int) $_SESSION['wishlist_count']
        : 0;

?>

<nav class="main-navbar">

    <div class="navbar-container">


        <!-- =========================================
             LOGO
        ========================================== -->

        <a
            href="index.php"
            class="navbar-logo">

            <img
                src="image/logo.jpg"
                alt="HochipoHub Logo">

            <span>
                Hochipo<span>Hub</span>
            </span>

        </a>


        <!-- =========================================
             MOBILE MENU BUTTON
        ========================================== -->

        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            aria-label="Open navigation menu">

            <i class="fas fa-bars"></i>

        </button>


        <!-- =========================================
             NAVIGATION
        ========================================== -->

        <div
            class="navbar-menu"
            id="navbarMenu">


            <!-- MAIN LINKS -->

            <a
                href="index.php"
                class="nav-link
                <?php echo $currentPage === 'index.php'
                    ? 'active'
                    : ''; ?>">

                <i class="fas fa-home"></i>

                <span>
                    Home
                </span>

            </a>


            <a
                href="catalog.php"
                class="nav-link
                <?php echo $currentPage === 'catalog.php'
                    ? 'active'
                    : ''; ?>">

                <i class="fas fa-store"></i>

                <span>
                    Shop
                </span>

            </a>


            <a
                href="category.php"
                class="nav-link
                <?php echo $currentPage === 'category.php'
                    ? 'active'
                    : ''; ?>">

                <i class="fas fa-th-large"></i>

                <span>
                    Categories
                </span>

            </a>


            <a
                href="vendor.php"
                class="nav-link
                <?php echo $currentPage === 'vendor.php'
                    ? 'active'
                    : ''; ?>">

                <i class="fas fa-users"></i>

                <span>
                    Vendors
                </span>

            </a>


            <a
                href="contact.php"
                class="nav-link
                <?php echo $currentPage === 'contact.php'
                    ? 'active'
                    : ''; ?>">

                <i class="fas fa-envelope"></i>

                <span>
                    Contact
                </span>

            </a>


            <!-- =====================================
                 SEARCH
            ====================================== -->

            <div class="navbar-search">

                <form
                    action="search.php"
                    method="GET">

                    <i class="fas fa-search"></i>

                    <input
                        type="search"
                        name="q"
                        placeholder="Search products..."
                        autocomplete="off">

                    <button
                        type="submit"
                        aria-label="Search">

                        <i class="fas fa-arrow-right"></i>

                    </button>

                </form>

            </div>


            <!-- =====================================
                 RIGHT SIDE
            ====================================== -->

            <div class="navbar-actions">


                <!-- =================================
                     WISHLIST
                ================================== -->

                <a
                    href="wishlist.php"
                    class="nav-icon-btn"
                    title="Wishlist">

                    <i class="fas fa-heart"></i>

                    <?php if ($wishlistCount > 0): ?>

                        <span class="nav-badge wishlist-badge">

                            <?php
                            echo $wishlistCount;
                            ?>

                        </span>

                    <?php endif; ?>

                </a>


                <!-- =================================
                     CART
                ================================== -->

                <a
                    href="cart.php"
                    class="nav-icon-btn"
                    title="Shopping Cart">

                    <i class="fas fa-shopping-cart"></i>

                    <?php if ($cartCount > 0): ?>

                        <span class="nav-badge cart-badge">

                            <?php
                            echo $cartCount;
                            ?>

                        </span>

                    <?php endif; ?>

                </a>


                <!-- =================================
                     USER
                ================================== -->

                <?php if ($isLoggedIn): ?>


                    <!-- LOGGED IN USER -->

                    <div
                        class="navbar-user"
                        id="navbarUser">


                        <button
                            type="button"
                            class="navbar-user-btn"
                            id="navbarUserBtn">


                            <?php if (
                                !empty($userProfileImage)
                            ): ?>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        $userProfileImage
                                    );
                                    ?>"
                                    alt="Profile">

                            <?php else: ?>

                                <span class="user-avatar">

                                    <?php
                                    echo strtoupper(
                                        substr(
                                            $userName,
                                            0,
                                            1
                                        )
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>


                            <span class="user-name">

                                <?php
                                echo htmlspecialchars(
                                    $userName
                                );
                                ?>

                            </span>


                            <i
                                class="fas fa-chevron-down">
                            </i>

                        </button>


                        <!-- USER DROPDOWN -->

                        <div
                            class="user-dropdown"
                            id="userDropdown">


                            <div
                                class="user-dropdown-header">

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $userName
                                    );
                                    ?>

                                </strong>

                                <small>

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $userRole
                                        )
                                    );
                                    ?>

                                </small>

                            </div>


                            <div
                                class="dropdown-divider">
                            </div>


                            <!-- PROFILE -->

                            <a
                                href="profile.php">

                                <i class="fas fa-user"></i>

                                My Profile

                            </a>


                            <!-- DASHBOARD -->

                            <?php if (
                                $userRole === 'admin'
                            ): ?>

                                <a
                                    href="admin/dashboard.php">

                                    <i
                                        class="fas fa-chart-line">
                                    </i>

                                    Admin Dashboard

                                </a>

                            <?php elseif (
                                $userRole === 'vendor'
                            ): ?>

                                <a
                                    href="seller/dashboard.php">

                                    <i
                                        class="fas fa-store">
                                    </i>

                                    Seller Dashboard

                                </a>

                            <?php else: ?>

                                <a
                                    href="dashboard.php">

                                    <i
                                        class="fas fa-tachometer-alt">
                                    </i>

                                    Dashboard

                                </a>

                            <?php endif; ?>


                            <!-- ORDERS -->

                            <a
                                href="order.php">

                                <i
                                    class="fas fa-box">
                                </i>

                                My Orders

                            </a>


                            <!-- WISHLIST -->

                            <a
                                href="wishlist.php">

                                <i
                                    class="fas fa-heart">
                                </i>

                                Wishlist

                            </a>


                            <div
                                class="dropdown-divider">
                            </div>


                            <!-- LOGOUT -->

                            <a
                                href="auth/logout.php"
                                class="logout-link">

                                <i
                                    class="fas fa-sign-out-alt">
                                </i>

                                Logout

                            </a>

                        </div>

                    </div>


                <?php else: ?>


                    <!-- =================================
                         GUEST
                    ================================== -->

                    <button
                        type="button"
                        class="navbar-login-btn"
                        onclick="openLoginModal()">

                        <i class="fas fa-user"></i>

                        <span>
                            Login
                        </span>

                    </button>


                    <a
                        href="auth/register_process.php"
                        class="navbar-register-btn">

                        Register

                    </a>


                <?php endif; ?>


            </div>

        </div>

    </div>

</nav>


<!-- =============================================
     NAVBAR JAVASCRIPT
================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
         * MOBILE MENU
         */

        const mobileMenuBtn =
            document.getElementById(
                "mobileMenuBtn"
            );

        const navbarMenu =
            document.getElementById(
                "navbarMenu"
            );


        if (
            mobileMenuBtn &&
            navbarMenu
        ) {

            mobileMenuBtn.addEventListener(
                "click",
                function () {

                    navbarMenu.classList.toggle(
                        "active"
                    );


                    const icon =
                        mobileMenuBtn.querySelector(
                            "i"
                        );


                    if (
                        navbarMenu.classList.contains(
                            "active"
                        )
                    ) {

                        icon.classList.remove(
                            "fa-bars"
                        );

                        icon.classList.add(
                            "fa-times"
                        );

                    } else {

                        icon.classList.remove(
                            "fa-times"
                        );

                        icon.classList.add(
                            "fa-bars"
                        );

                    }

                }
            );

        }


        /*
         * USER DROPDOWN
         */

        const userBtn =
            document.getElementById(
                "navbarUserBtn"
            );

        const userDropdown =
            document.getElementById(
                "userDropdown"
            );


        if (
            userBtn &&
            userDropdown
        ) {

            userBtn.addEventListener(
                "click",
                function (event) {

                    event.stopPropagation();

                    userDropdown.classList.toggle(
                        "active"
                    );

                }
            );


            document.addEventListener(
                "click",
                function () {

                    userDropdown.classList.remove(
                        "active"
                    );

                }
            );

        }


        /*
         * CLOSE MOBILE MENU
         * WHEN LINK IS CLICKED
         */

        const navLinks =
            document.querySelectorAll(
                ".navbar-menu .nav-link"
            );


        navLinks.forEach(
            function (link) {

                link.addEventListener(
                    "click",
                    function () {

                        if (
                            navbarMenu
                        ) {

                            navbarMenu.classList.remove(
                                "active"
                            );

                        }


                        if (
                            mobileMenuBtn
                        ) {

                            const icon =
                                mobileMenuBtn.querySelector(
                                    "i"
                                );

                            if (icon) {

                                icon.classList.remove(
                                    "fa-times"
                                );

                                icon.classList.add(
                                    "fa-bars"
                                );

                            }

                        }

                    }
                );

            }
        );


        /*
         * SEARCH INPUT
         */

        const searchInput =
            document.querySelector(
                ".navbar-search input"
            );


        if (searchInput) {

            searchInput.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Escape"
                    ) {

                        searchInput.value = "";

                        searchInput.blur();

                    }

                }
            );

        }

    }
);

</script>