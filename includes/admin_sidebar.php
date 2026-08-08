<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Admin Sidebar
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/session.php';

requireAdmin();


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

function adminSidebarActive(
    string $page
): string {

    global $currentPage;

    return $currentPage === $page
        ? 'active'
        : '';
}


/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminName =
    currentUserName()
    ?: 'Administrator';


/*
|--------------------------------------------------------------------------
| CURRENT USER PROFILE
|--------------------------------------------------------------------------
*/

$adminImage = '';

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

        $adminImage =
            $currentUser[
                'profile_image'
            ];
    }
}

?>

<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<aside
    class="dashboard-sidebar admin-sidebar"
    id="adminSidebar"
>


    <!-- =====================================================
         ADMIN PROFILE
    ====================================================== -->

    <div class="sidebar-profile admin-profile">

        <div class="sidebar-avatar admin-avatar">

            <?php if (
                $adminImage !== ''
            ): ?>

                <img
                    src="<?php echo BASE_URL; ?>image/<?php echo htmlspecialchars(
                        $adminImage,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                    alt="Administrator"
                >

            <?php else: ?>

                <span>

                    <?php

                    echo strtoupper(
                        substr(
                            $adminName,
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
                    $adminName,
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </strong>


            <small>
                System Administrator
            </small>

        </div>

    </div>


    <!-- =====================================================
         ADMIN BADGE
    ====================================================== -->

    <div class="admin-status-badge">

        <i class="fa-solid fa-shield-halved"></i>

        <span>
            ADMIN ACCESS
        </span>

    </div>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav
        class="sidebar-navigation"
        aria-label="Admin Navigation"
    >


        <!-- =================================================
             OVERVIEW
        ================================================== -->

        <div class="sidebar-section-title">

            <span>
                OVERVIEW
            </span>

        </div>


        <!-- DASHBOARD -->

        <a
            href="<?php echo BASE_URL; ?>admin/dashboard.php"
            class="sidebar-link <?php echo adminSidebarActive('dashboard.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-gauge-high"></i>

            </span>


            <span class="sidebar-link-text">
                Dashboard
            </span>

        </a>


        <!-- =================================================
             MANAGEMENT
        ================================================== -->

        <div class="sidebar-section-title">

            <span>
                MANAGEMENT
            </span>

        </div>


        <!-- USERS -->

        <a
            href="<?php echo BASE_URL; ?>admin/users.php"
            class="sidebar-link <?php echo adminSidebarActive('users.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-users"></i>

            </span>


            <span class="sidebar-link-text">
                Users
            </span>

        </a>


        <!-- VENDORS -->

        <a
            href="<?php echo BASE_URL; ?>admin/vendors.php"
            class="sidebar-link <?php echo adminSidebarActive('vendors.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-store"></i>

            </span>


            <span class="sidebar-link-text">
                Vendors
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="<?php echo BASE_URL; ?>admin/products.php"
            class="sidebar-link <?php echo adminSidebarActive('products.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-boxes-stacked"></i>

            </span>


            <span class="sidebar-link-text">
                Products
            </span>

        </a>


        <!-- INVENTORY -->

        <a
            href="<?php echo BASE_URL; ?>admin/inventory.php"
            class="sidebar-link <?php echo adminSidebarActive('inventory.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-warehouse"></i>

            </span>


            <span class="sidebar-link-text">
                Inventory
            </span>

        </a>


        <!-- =================================================
             TRANSACTIONS
        ================================================== -->

        <div class="sidebar-section-title">

            <span>
                TRANSACTIONS
            </span>

        </div>


        <!-- ORDERS -->

        <a
            href="<?php echo BASE_URL; ?>admin/orders.php"
            class="sidebar-link <?php echo adminSidebarActive('orders.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-receipt"></i>

            </span>


            <span class="sidebar-link-text">
                Orders
            </span>

        </a>


        <!-- PAYMENTS -->

        <a
            href="<?php echo BASE_URL; ?>admin/payments.php"
            class="sidebar-link <?php echo adminSidebarActive('payments.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-credit-card"></i>

            </span>


            <span class="sidebar-link-text">
                Payments
            </span>

        </a>


        <!-- COMMISSION -->

        <a
            href="<?php echo BASE_URL; ?>admin/commission.php"
            class="sidebar-link <?php echo adminSidebarActive('commission.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-percent"></i>

            </span>


            <span class="sidebar-link-text">
                Commission
            </span>

        </a>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="sidebar-section-title">

            <span>
                CONTENT
            </span>

        </div>


        <!-- REVIEWS -->

        <a
            href="<?php echo BASE_URL; ?>admin/reviews.php"
            class="sidebar-link <?php echo adminSidebarActive('reviews.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-star"></i>

            </span>


            <span class="sidebar-link-text">
                Reviews
            </span>

        </a>


        <!-- =================================================
             SYSTEM
        ================================================== -->

        <div class="sidebar-section-title">

            <span>
                SYSTEM
            </span>

        </div>


        <!-- SETTINGS -->

        <a
            href="<?php echo BASE_URL; ?>admin/settings.php"
            class="sidebar-link <?php echo adminSidebarActive('settings.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-gear"></i>

            </span>


            <span class="sidebar-link-text">
                Settings
            </span>

        </a>


        <!-- MAIN SITE -->

        <a
            href="<?php echo BASE_URL; ?>index.php"
            class="sidebar-link"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-arrow-up-right-from-square"></i>

            </span>


            <span class="sidebar-link-text">
                View Marketplace
            </span>

        </a>

    </nav>


    <!-- =====================================================
         ADMIN SECURITY NOTICE
    ====================================================== -->

    <div class="admin-security-card">

        <div class="admin-security-icon">

            <i class="fa-solid fa-shield-halved"></i>

        </div>


        <div>

            <strong>
                Secure Area
            </strong>

            <p>
                Admin actions are monitored and logged.
            </p>

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