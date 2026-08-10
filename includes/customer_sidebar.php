<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Customer Sidebar
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/session.php';

requireCustomer();


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER['PHP_SELF'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| ACTIVE LINK
|--------------------------------------------------------------------------
*/

function customerSidebarActive(
    string $page
): string {

    global $currentPage;

    return $currentPage === $page
        ? 'active'
        : '';
}

?>


<!-- =========================================================
     CUSTOMER SIDEBAR
========================================================= -->

<aside
    class="dashboard-sidebar customer-sidebar"
    id="customerSidebar"
>


    <!-- =====================================================
         SIDEBAR PROFILE
    ====================================================== -->

    <div class="sidebar-profile">

        <div class="sidebar-avatar">

            <?php

            $profileImage = '';

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
                    alt="Profile Image"
                >

            <?php else: ?>

                <span>

                    <?php

                    echo strtoupper(
                        substr(
                            currentUserName()
                                ?: 'U',
                            0,
                            1
                        )
                    );

                    ?>

                </span>

            <?php endif; ?>

        </div>


        <div class="sidebar-profile-info">

            <strong>

                <?php

                echo htmlspecialchars(
                    currentUserName()
                        ?: 'Customer',
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </strong>


            <small>
                Customer Account
            </small>

        </div>

    </div>


    <!-- =====================================================
         SIDEBAR NAVIGATION
    ====================================================== -->

    <nav
        class="sidebar-navigation"
        aria-label="Customer Navigation"
    >


        <div class="sidebar-section-title">

            <span>
                MAIN MENU
            </span>

        </div>


        <!-- DASHBOARD -->

        <a
            href="<?php echo BASE_URL; ?>dashboard.php"
            class="sidebar-link <?php echo customerSidebarActive('dashboard.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-gauge-high"></i>

            </span>


            <span class="sidebar-link-text">
                Dashboard
            </span>

        </a>


        <!-- SHOP -->

        <a
            href="<?php echo BASE_URL; ?>catalog.php"
            class="sidebar-link"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-store"></i>

            </span>


            <span class="sidebar-link-text">
                Browse Products
            </span>

        </a>


        <!-- CATEGORIES -->

        <a
            href="<?php echo BASE_URL; ?>category.php"
            class="sidebar-link <?php echo customerSidebarActive('category.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-layer-group"></i>

            </span>


            <span class="sidebar-link-text">
                Categories
            </span>

        </a>


        <div class="sidebar-section-title">

            <span>
                MY SHOPPING
            </span>

        </div>


        <!-- CART -->

        <a
            href="<?php echo BASE_URL; ?>cart.php"
            class="sidebar-link <?php echo customerSidebarActive('cart.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-cart-shopping"></i>

            </span>


            <span class="sidebar-link-text">
                My Cart
            </span>

        </a>


        <!-- WISHLIST -->

        <a
            href="<?php echo BASE_URL; ?>wishlist.php"
            class="sidebar-link <?php echo customerSidebarActive('wishlist.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-heart"></i>

            </span>


            <span class="sidebar-link-text">
                Wishlist
            </span>

        </a>


        <!-- ORDERS -->

        <a
            href="<?php echo BASE_URL; ?>order.php"
            class="sidebar-link <?php echo customerSidebarActive('order.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-box-open"></i>

            </span>


            <span class="sidebar-link-text">
                My Orders
            </span>

        </a>


        <div class="sidebar-section-title">

            <span>
                ACCOUNT
            </span>

        </div>


        <!-- PROFILE -->

        <a
            href="<?php echo BASE_URL; ?>profile.php"
            class="sidebar-link <?php echo customerSidebarActive('profile.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-user"></i>

            </span>


            <span class="sidebar-link-text">
                My Profile
            </span>

        </a>


        <!-- REVIEWS -->

        <a
            href="<?php echo BASE_URL; ?>review.php"
            class="sidebar-link <?php echo customerSidebarActive('review.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-star"></i>

            </span>


            <span class="sidebar-link-text">
                My Reviews
            </span>

        </a>


        <!-- CONTACT -->

        <a
            href="<?php echo BASE_URL; ?>contact.php"
            class="sidebar-link <?php echo customerSidebarActive('contact.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-headset"></i>

            </span>


            <span class="sidebar-link-text">
                Contact Support
            </span>

        </a>


    </nav>


    <!-- =====================================================
         SELL WITH US
    ====================================================== -->

    <div class="sidebar-promo">

        <div class="sidebar-promo-icon">

            <i class="fa-solid fa-shop"></i>

        </div>


        <div class="sidebar-promo-content">

            <strong>
                Want to sell?
            </strong>

            <p>
                Start your own local store.
            </p>


            <a
                href="<?php echo BASE_URL; ?>seller/setup_profile.php"
            >

                Become a Vendor

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </div>


    <!-- =====================================================
         LOGOUT
    ====================================================== -->

    <div class="sidebar-bottom">

        <a
            href="<?php echo BASE_URL; ?>auth/logout.php"
            class="sidebar-link sidebar-logout"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-right-from-bracket"></i>

            </span>


            <span class="sidebar-link-text">
                Logout
            </span>

        </a>

    </div>

</aside>