<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

function sidebarActive($pages)
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

<aside class="dashboard-sidebar">

    <div class="dashboard-sidebar-inner">

        <!-- SIDEBAR BRAND -->

        <div class="sidebar-brand">

            <div class="sidebar-brand-icon">
                H
            </div>

            <div class="sidebar-brand-text">
                <strong>HochipoHub</strong>
                <span>Customer Portal</span>
            </div>

        </div>


        <!-- SIDEBAR MENU -->

        <nav class="sidebar-menu">

            <a
                href="dashboard.php"
                class="<?= sidebarActive('dashboard.php') ?>"
            >
                <span class="sidebar-icon">🏠</span>
                <span>Dashboard</span>
            </a>


            <a
                href="catalog.php"
                class="<?= sidebarActive([
                    'catalog.php',
                    'product.php',
                    'product_details.php'
                ]) ?>"
            >
                <span class="sidebar-icon">🛍️</span>
                <span>Browse Products</span>
            </a>


            <a
                href="cart.php"
                class="<?= sidebarActive('cart.php') ?>"
            >
                <span class="sidebar-icon">🛒</span>
                <span>Cart</span>
            </a>


            <a
                href="wishlist.php"
                class="<?= sidebarActive('wishlist.php') ?>"
            >
                <span class="sidebar-icon">❤️</span>
                <span>Wishlist</span>
            </a>


            <a
                href="order.php"
                class="<?= sidebarActive([
                    'order.php',
                    'orders.php'
                ]) ?>"
            >
                <span class="sidebar-icon">📦</span>
                <span>My Orders</span>
            </a>


            <a
                href="profile.php"
                class="<?= sidebarActive('profile.php') ?>"
            >
                <span class="sidebar-icon">👤</span>
                <span>My Profile</span>
            </a>


            <a
                href="index.php"
                class="<?= sidebarActive('index.php') ?>"
            >
                <span class="sidebar-icon">🏡</span>
                <span>Home</span>
            </a>


            <a
                href="auth/logout.php"
                class="sidebar-logout"
            >
                <span class="sidebar-icon">↪</span>
                <span>Logout</span>
            </a>

        </nav>

    </div>

</aside>