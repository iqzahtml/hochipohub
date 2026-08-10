<?php
// =========================================================
// HOCHIPO HUB - CUSTOMER SIDEBAR
// File: includes/customer_sidebar.php
// =========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Customer sahaja
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'customer'
) {
    header("Location: ../index.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

$customerName = $_SESSION['name'] ?? 'Customer';
?>

<aside class="customer-sidebar" id="customerSidebar">

    <!-- =====================================================
         SIDEBAR HEADER
    ====================================================== -->
    <div class="customer-sidebar-header">

        <a href="../dashboard.php" class="customer-sidebar-logo">

            <div class="customer-logo-icon">
                H
            </div>

            <div class="customer-logo-text">
                <strong>HOCHIPO</strong>
                <span>HUB</span>
            </div>

        </a>

        <button
            type="button"
            class="customer-sidebar-close"
            id="customerSidebarClose"
            aria-label="Close Sidebar"
        >
            &times;
        </button>

    </div>


    <!-- =====================================================
         CUSTOMER PROFILE
    ====================================================== -->
    <div class="customer-sidebar-profile">

        <div class="customer-avatar">

            <i class="fa-solid fa-user"></i>

        </div>

        <div class="customer-profile-info">

            <strong>
                <?php echo htmlspecialchars($customerName); ?>
            </strong>

            <span>
                Customer
            </span>

        </div>

    </div>


    <!-- =====================================================
         CUSTOMER NAVIGATION
    ====================================================== -->
    <nav class="customer-sidebar-nav">

        <!-- MAIN -->
        <div class="customer-nav-section">

            <span class="customer-nav-title">
                MAIN
            </span>


            <!-- Dashboard -->
            <a
                href="../dashboard.php"
                class="customer-nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-house"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- Browse Products -->
            <a
                href="../catalog.php"
                class="customer-nav-link <?php echo ($currentPage === 'catalog.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-store"></i>

                <span>
                    Browse Products
                </span>

            </a>


            <!-- Categories -->
            <a
                href="../category.php"
                class="customer-nav-link <?php echo ($currentPage === 'category.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-layer-group"></i>

                <span>
                    Categories
                </span>

            </a>


            <!-- Vendors -->
            <a
                href="../vendor.php"
                class="customer-nav-link <?php echo ($currentPage === 'vendor.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-shop"></i>

                <span>
                    Vendors
                </span>

            </a>

        </div>


        <!-- SHOPPING -->
        <div class="customer-nav-section">

            <span class="customer-nav-title">
                SHOPPING
            </span>


            <!-- Cart -->
            <a
                href="../cart.php"
                class="customer-nav-link <?php echo ($currentPage === 'cart.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                <span>
                    My Cart
                </span>

            </a>


            <!-- Wishlist -->
            <a
                href="../wishlist.php"
                class="customer-nav-link <?php echo ($currentPage === 'wishlist.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-heart"></i>

                <span>
                    Wishlist
                </span>

            </a>


            <!-- Orders -->
            <a
                href="../order.php"
                class="customer-nav-link <?php echo ($currentPage === 'order.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-box"></i>

                <span>
                    My Orders
                </span>

            </a>

        </div>


        <!-- ACCOUNT -->
        <div class="customer-nav-section">

            <span class="customer-nav-title">
                ACCOUNT
            </span>


            <!-- Profile -->
            <a
                href="../profile.php"
                class="customer-nav-link <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-user-circle"></i>

                <span>
                    My Profile
                </span>

            </a>


            <!-- Reviews -->
            <a
                href="../review.php"
                class="customer-nav-link <?php echo ($currentPage === 'review.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-star"></i>

                <span>
                    My Reviews
                </span>

            </a>


            <!-- Contact -->
            <a
                href="../contact.php"
                class="customer-nav-link <?php echo ($currentPage === 'contact.php') ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-envelope"></i>

                <span>
                    Contact Us
                </span>

            </a>

        </div>


        <!-- OTHER -->
        <div class="customer-nav-section">

            <span class="customer-nav-title">
                OTHER
            </span>


            <!-- Back to Homepage -->
            <a
                href="../index.php"
                class="customer-nav-link"
            >

                <i class="fa-solid fa-arrow-left"></i>

                <span>
                    Back to Homepage
                </span>

            </a>

        </div>

    </nav>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->
    <div class="customer-sidebar-footer">

        <a
            href="../auth/logout.php"
            class="customer-logout"
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
     MOBILE OVERLAY
========================================================= -->
<div
    class="customer-sidebar-overlay"
    id="customerSidebarOverlay"
></div>


<!-- =========================================================
     CUSTOMER SIDEBAR JAVASCRIPT
========================================================= -->
<script>

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("customerSidebar");
    const closeBtn = document.getElementById("customerSidebarClose");
    const overlay = document.getElementById("customerSidebarOverlay");

    /*
     * Header/navbar boleh gunakan button:
     *
     * id="customerSidebarToggle"
     *
     * untuk buka sidebar pada mobile.
     */

    const toggleBtn = document.getElementById("customerSidebarToggle");


    // Open sidebar
    if (toggleBtn) {

        toggleBtn.addEventListener("click", function () {

            sidebar.classList.add("active");
            overlay.classList.add("active");

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


    // Close selepas pilih menu pada mobile
    const navLinks = document.querySelectorAll(".customer-nav-link");

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