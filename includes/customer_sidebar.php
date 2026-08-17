<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<aside class="dashboard-sidebar">

    <div class="sidebar-header">

        <h2>
            HochipoHub
        </h2>

        <span>
            Customer
        </span>

    </div>


    <nav class="sidebar-menu">

        <a href="dashboard.php">
            🏠 Dashboard
        </a>

        <a href="catalog.php">
            🛍️ Browse Products
        </a>

        <a href="cart.php">
            🛒 Cart
        </a>

        <a href="wishlist.php">
            ❤️ Wishlist
        </a>

        <a href="order.php">
            📦 My Orders
        </a>

        <a href="profile.php">
            👤 My Profile
        </a>

        <a href="index.php">
            🏠 Home
        </a>

        <a href="auth/logout.php">
            🚪 Logout
        </a>

    </nav>

</aside>