<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

?>

<aside class="dashboard-sidebar">

    <div class="sidebar-header">

        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">H</div>

            <div>
                <h2>HochipoHub</h2>
                <span>Customer</span>
            </div>
        </div>

    </div>


    <nav class="sidebar-menu">

        <a
            href="dashboard.php"
            class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
        >
            <span>🏠</span>
            <span>Dashboard</span>
        </a>


        <a
            href="catalog.php"
            class="<?= $currentPage === 'catalog.php' ? 'active' : '' ?>"
        >
            <span>🛍️</span>
            <span>Browse Products</span>
        </a>


        <a
            href="cart.php"
            class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>"
        >
            <span>🛒</span>
            <span>Cart</span>
        </a>


        <a
            href="wishlist.php"
            class="<?= $currentPage === 'wishlist.php' ? 'active' : '' ?>"
        >
            <span>❤️</span>
            <span>Wishlist</span>
        </a>


        <a
            href="order.php"
            class="<?= $currentPage === 'order.php' ? 'active' : '' ?>"
        >
            <span>📦</span>
            <span>My Orders</span>
        </a>


        <a
            href="profile.php"
            class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>"
        >
            <span>👤</span>
            <span>My Profile</span>
        </a>


        <a href="index.php">
            <span>🏠</span>
            <span>Home</span>
        </a>


        <a
            href="auth/logout.php"
            class="sidebar-logout"
        >
            <span>🚪</span>
            <span>Logout</span>
        </a>

    </nav>

</aside>