<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR SIDEBAR
|--------------------------------------------------------------------------
| File:
| includes/vendor_sidebar.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage = basename(
    $_SERVER['PHP_SELF'] ?? ''
);


/*
|--------------------------------------------------------------------------
| ACTIVE MENU HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorSidebarActive')) {

    function vendorSidebarActive($pages)
    {
        global $currentPage;

        if (!is_array($pages)) {
            $pages = [$pages];
        }

        return in_array(
            $currentPage,
            $pages,
            true
        )
            ? 'active'
            : '';
    }

}


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
|--------------------------------------------------------------------------
|
| Kalau page seperti dashboard.php sudah mempunyai $vendor,
| gunakan data tersebut.
|
|--------------------------------------------------------------------------
*/

$sidebarBusinessName =
    $vendor['business_name']
    ?? $_SESSION['business_name']
    ?? 'My Store';

$sidebarVendorName =
    $vendor['name']
    ?? $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? 'Vendor';

$sidebarStatus =
    $vendor['approval_status']
    ?? $_SESSION['vendor_approval_status']
    ?? 'Pending';


/*
|--------------------------------------------------------------------------
| BUSINESS LOGO
|--------------------------------------------------------------------------
*/

$sidebarLogo = '';

if (
    isset($vendor['business_logo']) &&
    !empty($vendor['business_logo'])
) {

    $sidebarLogo =
        '../uploads/vendors/' .
        basename(
            $vendor['business_logo']
        );

}

?>


<aside
    class="vendor-sidebar"
    id="vendorSidebar"
>


    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="vendor-sidebar-brand">

        <a
            href="dashboard.php"
            class="vendor-brand"
        >

            <div class="vendor-brand-logo">

                <i class="fa-solid fa-store"></i>

            </div>


            <div class="vendor-brand-copy">

                <strong>
                    HOCHIPO<span>HUB</span>
                </strong>

                <small>
                    VENDOR PANEL
                </small>

            </div>

        </a>


        <button
            type="button"
            class="vendor-sidebar-close"
            id="vendorSidebarClose"
            aria-label="Close sidebar"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>


    <!-- =====================================================
         PROFILE
    ====================================================== -->

    <div class="vendor-sidebar-profile">


        <div class="vendor-sidebar-avatar">


            <?php if ($sidebarLogo !== ''): ?>

                <img
                    src="<?= htmlspecialchars(
                        $sidebarLogo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    alt="<?= htmlspecialchars(
                        $sidebarBusinessName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    onerror="
                        this.style.display='none';
                        this.nextElementSibling.style.display='flex';
                    "
                >


                <div
                    class="vendor-avatar-fallback"
                    style="display:none;"
                >

                    <i class="fa-solid fa-store"></i>

                </div>


            <?php else: ?>


                <div class="vendor-avatar-fallback">

                    <i class="fa-solid fa-store"></i>

                </div>


            <?php endif; ?>


        </div>


        <div class="vendor-sidebar-profile-copy">

            <strong>

                <?= htmlspecialchars(
                    $sidebarBusinessName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </strong>


            <span>

                <?= htmlspecialchars(
                    $sidebarVendorName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>


            <small
                class="vendor-account-status
                <?= strtolower(
                    trim(
                        $sidebarStatus
                    )
                ) ?>"
            >

                <?= htmlspecialchars(
                    $sidebarStatus,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </small>

        </div>


    </div>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav class="vendor-sidebar-nav">


        <!-- MAIN MENU -->

        <div class="vendor-sidebar-label">
            MAIN MENU
        </div>


        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'dashboard.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-table-columns"></i>

            </span>

            <span class="vendor-link-text">
                Dashboard
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="products.php"
            class="vendor-sidebar-link <?= vendorSidebarActive([
                'products.php',
                'edit_product.php'
            ]) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-cube"></i>

            </span>

            <span class="vendor-link-text">
                My Products
            </span>

        </a>


        <!-- ADD PRODUCT -->

        <a
            href="add_product.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'add_product.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-circle-plus"></i>

            </span>

            <span class="vendor-link-text">
                Add Product
            </span>

        </a>


        <!-- ORDERS -->

        <a
            href="orders.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'orders.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-bag-shopping"></i>

            </span>

            <span class="vendor-link-text">
                Orders
            </span>

        </a>


        <!-- SALES -->

        <a
            href="sales.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'sales.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-chart-column"></i>

            </span>

            <span class="vendor-link-text">
                Sales
            </span>

        </a>


        <!-- MANAGEMENT -->

        <div class="vendor-sidebar-label vendor-label-space">
            MANAGEMENT
        </div>


        <!-- INVENTORY -->

        <a
            href="../inventory.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'inventory.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-warehouse"></i>

            </span>

            <span class="vendor-link-text">
                Inventory
            </span>

        </a>


        <!-- COMMISSION -->

        <a
            href="../commission.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'commission.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-circle-dollar-to-slot"></i>

            </span>

            <span class="vendor-link-text">
                Commission
            </span>

        </a>


        <!-- STORE PROFILE -->

        <a
            href="setup_profile.php"
            class="vendor-sidebar-link <?= vendorSidebarActive(
                'setup_profile.php'
            ) ?>"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-store"></i>

            </span>

            <span class="vendor-link-text">
                Store Profile
            </span>

        </a>


        <!-- DIVIDER -->

        <div class="vendor-sidebar-divider"></div>


        <!-- MARKETPLACE -->

        <a
            href="../catalog.php"
            class="vendor-sidebar-link"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-cart-shopping"></i>

            </span>

            <span class="vendor-link-text">
                View Marketplace
            </span>

        </a>


        <!-- WEBSITE -->

        <a
            href="../index.php"
            class="vendor-sidebar-link"
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-house"></i>

            </span>

            <span class="vendor-link-text">
                Home
            </span>

        </a>


    </nav>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <div class="vendor-sidebar-footer">


        <a
            href="../profile.php"
            class="vendor-footer-link"
        >

            <span>

                <i class="fa-solid fa-user"></i>

            </span>

            <span>
                My Profile
            </span>

        </a>


        <a
            href="../auth/logout.php"
            class="vendor-footer-link logout"
        >

            <span>

                <i class="fa-solid fa-right-from-bracket"></i>

            </span>

            <span>
                Logout
            </span>

        </a>


    </div>


</aside>

<!-- MOBILE SIDEBAR BUTTON (AVAILABLE ON EVERY SELLER PAGE) -->
<button
    type="button"
    class="seller-mobile-menu"
    data-vendor-sidebar-toggle
    aria-label="Open seller navigation"
>
    <i class="fa-solid fa-bars"></i>
</button>


<!-- =========================================================
     MOBILE OVERLAY
========================================================== -->

<div
    class="vendor-sidebar-overlay"
    id="vendorSidebarOverlay"
></div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const sidebar =
            document.getElementById(
                'vendorSidebar'
            );

        const overlay =
            document.getElementById(
                'vendorSidebarOverlay'
            );

        const closeButton =
            document.getElementById(
                'vendorSidebarClose'
            );


        function closeVendorSidebar() {

            if (sidebar) {

                sidebar.classList.remove(
                    'open'
                );

            }

            if (overlay) {

                overlay.classList.remove(
                    'show'
                );

            }

        }


        if (closeButton) {

            closeButton.addEventListener(
                'click',
                closeVendorSidebar
            );

        }


        if (overlay) {

            overlay.addEventListener(
                'click',
                closeVendorSidebar
            );

        }


        document
            .querySelectorAll(
                '[data-vendor-sidebar-toggle]'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        function () {

                            if (sidebar) {

                                sidebar.classList.add(
                                    'open'
                                );

                            }

                            if (overlay) {

                                overlay.classList.add(
                                    'show'
                                );

                            }

                        }
                    );

                }
            );

    }
);

</script>
