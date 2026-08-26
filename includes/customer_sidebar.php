<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$userName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'Customer';

$userRole = $_SESSION['user_role']
    ?? $_SESSION['role']
    ?? 'customer';

$userRole = ucfirst(strtolower($userRole));


/*
|--------------------------------------------------------------------------
| ACTIVE SIDEBAR
|--------------------------------------------------------------------------
*/

if (!function_exists('customerSidebarActive')) {

    function customerSidebarActive($pages)
    {
        global $currentPage;

        if (!is_array($pages)) {
            $pages = [$pages];
        }

        return in_array($currentPage, $pages, true)
            ? 'active'
            : '';
    }

}


/* Keep logout available throughout the customer area. */
$showCustomerLogout =
    strtolower($userRole) === 'customer';

?>

<nav class="customer-sidebar">

    <div class="customer-sidebar-inner">


        <!-- =====================================================
             DASHBOARD
        ====================================================== -->

        <a
            href="dashboard.php"
            class="customer-sidebar-item <?= customerSidebarActive('dashboard.php') ?>"
        >

            <span class="sidebar-icon">
                <i class="bi bi-house"></i>
            </span>

            <span class="sidebar-text">
                Dashboard
            </span>

        </a>


        <!-- =====================================================
             PRODUCTS
        ====================================================== -->

        <a
            href="catalog.php"
            class="customer-sidebar-item <?= customerSidebarActive([
                'catalog.php',
                'product.php',
                'product_details.php'
            ]) ?>"
        >

            <span class="sidebar-icon">
                <i class="bi bi-bag"></i>
            </span>

            <span class="sidebar-text">
                Browse Products
            </span>

        </a>


        <!-- =====================================================
             CART
        ====================================================== -->

        <a
            href="cart.php"
            class="customer-sidebar-item <?= customerSidebarActive('cart.php') ?>"
        >

            <span class="sidebar-icon">
                <i class="bi bi-cart3"></i>
            </span>

            <span class="sidebar-text">
                Cart
            </span>

            <?php if (!empty($cartCount) && $cartCount > 0): ?>

                <span class="sidebar-badge">
                    <?= $cartCount > 99 ? '99+' : $cartCount ?>
                </span>

            <?php endif; ?>

        </a>


        <!-- =====================================================
             WISHLIST
        ====================================================== -->

        <a
            href="wishlist.php"
            class="customer-sidebar-item <?= customerSidebarActive('wishlist.php') ?>"
        >

            <span class="sidebar-icon">
                <i class="bi bi-heart"></i>
            </span>

            <span class="sidebar-text">
                Wishlist
            </span>

            <?php if (!empty($wishlistCount) && $wishlistCount > 0): ?>

                <span class="sidebar-badge">
                    <?= $wishlistCount > 99 ? '99+' : $wishlistCount ?>
                </span>

            <?php endif; ?>

        </a>


        <!-- =====================================================
             ORDERS
        ====================================================== -->

        <a
            href="order.php"
            class="customer-sidebar-item <?= customerSidebarActive([
                'order.php',
                'order_details.php'
            ]) ?>"
        >

            <span class="sidebar-icon">
                <i class="bi bi-box-seam"></i>
            </span>

            <span class="sidebar-text">
                My Orders
            </span>

        </a>


        <!-- =====================================================
             PROFILE
        ====================================================== -->

        <a
            href="profile.php"
            class="customer-sidebar-item <?= customerSidebarActive('profile.php') ?>"
        >

            <span class="sidebar-icon">
                <i class="bi bi-person"></i>
            </span>

            <span class="sidebar-text">
                My Profile
            </span>

        </a>


        <!-- =====================================================
             HOME
        ====================================================== -->

        <a
            href="index.php"
            class="customer-sidebar-item"
        >

            <span class="sidebar-icon">
                <i class="bi bi-house-door"></i>
            </span>

            <span class="sidebar-text">
                Home
            </span>

        </a>


        <!-- =====================================================
             LOGOUT
             ONLY CUSTOMER DASHBOARD
        ====================================================== -->

        <?php if ($showCustomerLogout): ?>

            <a
                href="auth/logout.php"
                class="customer-sidebar-item sidebar-logout"
            >

                <span class="sidebar-icon">
                    <i class="bi bi-box-arrow-right"></i>
                </span>

                <span class="sidebar-text">
                    Logout
                </span>

            </a>

        <?php endif; ?>


    </div>

</nav>
