<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$sidebarBaseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '/hochipohub/';

?>

<aside class="dashboard-sidebar">

    <!-- SIDEBAR BRAND -->
    <div class="sidebar-brand">

        <div class="sidebar-brand-icon">
            H
        </div>

        <div>
            <strong>HochipoHub</strong>
            <span>Customer Center</span>
        </div>

    </div>


    <!-- NAVIGATION -->
    <nav class="sidebar-menu">


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'dashboard.php') ?>"
            class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">⌂</span>

            <span class="sidebar-text">
                Dashboard
            </span>
        </a>


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'catalog.php') ?>"
            class="<?= in_array(
                $currentPage,
                ['catalog.php', 'product.php', 'product_details.php']
            ) ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">🛍</span>

            <span class="sidebar-text">
                Browse Products
            </span>
        </a>


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'cart.php') ?>"
            class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">🛒</span>

            <span class="sidebar-text">
                Cart
            </span>

            <?php if (!empty($cartCount)): ?>

                <span class="sidebar-badge">
                    <?= $cartCount > 99 ? '99+' : (int)$cartCount ?>
                </span>

            <?php endif; ?>

        </a>


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'wishlist.php') ?>"
            class="<?= $currentPage === 'wishlist.php' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">♡</span>

            <span class="sidebar-text">
                Wishlist
            </span>

            <?php if (!empty($wishlistCount)): ?>

                <span class="sidebar-badge">
                    <?= $wishlistCount > 99 ? '99+' : (int)$wishlistCount ?>
                </span>

            <?php endif; ?>

        </a>


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'order.php') ?>"
            class="<?= $currentPage === 'order.php' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">📦</span>

            <span class="sidebar-text">
                My Orders
            </span>
        </a>


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'profile.php') ?>"
            class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">♙</span>

            <span class="sidebar-text">
                My Profile
            </span>
        </a>


        <a
            href="<?= htmlspecialchars($sidebarBaseUrl . 'index.php') ?>"
            class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">⌂</span>

            <span class="sidebar-text">
                Home
            </span>
        </a>


    </nav>


    <!-- SIDEBAR FOOTER -->
    <div class="sidebar-footer">

        <a
            href="<?= htmlspecialchars(
                $sidebarBaseUrl . 'auth/logout.php'
            ) ?>"
            class="sidebar-logout"
        >

            <span class="sidebar-icon">
                ↪
            </span>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>