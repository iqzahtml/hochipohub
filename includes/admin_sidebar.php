<?php
// =========================================================
// HOCHIPOHUB - ADMIN SIDEBAR
// File: includes/admin_sidebar.php
// =========================================================

// Pastikan session sudah dimulakan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya admin boleh akses sidebar ini
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Current page untuk active menu
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="admin-sidebar" id="adminSidebar">

    <!-- =====================================================
         SIDEBAR HEADER
    ====================================================== -->
    <div class="admin-sidebar-header">

        <a href="../admin/dashboard.php" class="admin-logo">

            <div class="admin-logo-icon">
                H
            </div>

            <div class="admin-logo-text">
                <strong>HOCHIPO</strong>
                <span>HUB ADMIN</span>
            </div>

        </a>

        <!-- Mobile close button -->
        <button
            type="button"
            class="admin-sidebar-close"
            id="adminSidebarClose"
            aria-label="Close Sidebar"
        >
            &times;
        </button>

    </div>


    <!-- =====================================================
         ADMIN PROFILE
    ====================================================== -->
    <div class="admin-profile">

        <div class="admin-profile-avatar">
            <i class="fa-solid fa-user-shield"></i>
        </div>

        <div class="admin-profile-info">

            <strong>
                <?php
                echo htmlspecialchars($_SESSION['name'] ?? 'Administrator');
                ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>

    </div>


    <!-- =====================================================
         MAIN NAVIGATION
    ====================================================== -->
    <nav class="admin-nav">

        <!-- Dashboard -->
        <div class="admin-nav-section">

            <span class="admin-nav-title">
                MAIN
            </span>

            <a
                href="../admin/dashboard.php"
                class="admin-nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>

        </div>


        <!-- =================================================
             MANAGEMENT
        ================================================== -->
        <div class="admin-nav-section">

            <span class="admin-nav-title">
                MANAGEMENT
            </span>


            <!-- Products -->
            <a
                href="../admin/products.php"
                class="admin-nav-link <?php echo ($currentPage === 'products.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-box-open"></i>
                <span>Products</span>
            </a>


            <!-- Users -->
            <a
                href="../admin/users.php"
                class="admin-nav-link <?php echo ($currentPage === 'users.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-users"></i>
                <span>Users</span>
            </a>


            <!-- Vendors -->
            <a
                href="../admin/vendors.php"
                class="admin-nav-link <?php echo ($currentPage === 'vendors.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-store"></i>
                <span>Vendors</span>
            </a>


            <!-- Orders -->
            <a
                href="../admin/orders.php"
                class="admin-nav-link <?php echo ($currentPage === 'orders.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Orders</span>
            </a>


            <!-- Payments -->
            <a
                href="../admin/payments.php"
                class="admin-nav-link <?php echo ($currentPage === 'payments.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-credit-card"></i>
                <span>Payments</span>
            </a>


            <!-- Reviews -->
            <a
                href="../admin/reviews.php"
                class="admin-nav-link <?php echo ($currentPage === 'reviews.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-star"></i>
                <span>Reviews</span>
            </a>

        </div>


        <!-- =================================================
             FINANCIAL
        ================================================== -->
        <div class="admin-nav-section">

            <span class="admin-nav-title">
                FINANCIAL
            </span>


            <!-- Commission -->
            <a
                href="../admin/commission.php"
                class="admin-nav-link <?php echo ($currentPage === 'commission.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Commission</span>
            </a>

        </div>


        <!-- =================================================
             SYSTEM
        ================================================== -->
        <div class="admin-nav-section">

            <span class="admin-nav-title">
                SYSTEM
            </span>


            <!-- Settings -->
            <a
                href="../admin/settings.php"
                class="admin-nav-link <?php echo ($currentPage === 'settings.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>


            <!-- Back to Website -->
            <a
                href="../index.php"
                class="admin-nav-link"
            >
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Back to Website</span>
            </a>

        </div>

    </nav>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->
    <div class="admin-sidebar-footer">

        <a
            href="../auth/logout.php"
            class="admin-logout"
            onclick="return confirm('Are you sure you want to logout?');"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- =========================================================
     MOBILE SIDEBAR OVERLAY
========================================================= -->
<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>


<!-- =========================================================
     SIDEBAR JAVASCRIPT
========================================================= -->
<script>

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("adminSidebar");
    const closeBtn = document.getElementById("adminSidebarClose");
    const overlay = document.getElementById("adminSidebarOverlay");

    /*
     * Sidebar toggle function.
     *
     * Admin page boleh ada button dengan:
     *
     * id="adminSidebarToggle"
     *
     * dalam header.
     */
    const toggleBtn = document.getElementById("adminSidebarToggle");


    if (toggleBtn) {

        toggleBtn.addEventListener("click", function () {

            sidebar.classList.toggle("active");
            overlay.classList.toggle("active");

        });

    }


    // Close sidebar
    if (closeBtn) {

        closeBtn.addEventListener("click", function () {

            sidebar.classList.remove("active");
            overlay.classList.remove("active");

        });

    }


    // Close bila click overlay
    if (overlay) {

        overlay.addEventListener("click", function () {

            sidebar.classList.remove("active");
            overlay.classList.remove("active");

        });

    }


    // Close sidebar selepas click navigation pada mobile
    const navLinks = document.querySelectorAll(".admin-nav-link");

    navLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            if (window.innerWidth <= 768) {

                sidebar.classList.remove("active");
                overlay.classList.remove("active");

            }

        });

    });

});

</script>