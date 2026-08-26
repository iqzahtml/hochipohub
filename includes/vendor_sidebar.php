<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR SIDEBAR
|--------------------------------------------------------------------------
| File:
| includes/vendor_sidebar.php
|--------------------------------------------------------------------------
|
| IMPORTANT:
| All sidebar URLs use BASE_URL.
|
| This means the same sidebar can safely be used from:
|
| - seller/dashboard.php
| - seller/products.php
| - seller/add_product.php
| - seller/orders.php
| - seller/sales.php
| - seller/setup_profile.php
| - inventory.php
| - commission.php
|
| without ../ path problems.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
|--------------------------------------------------------------------------
| FALLBACK BASE URL
|--------------------------------------------------------------------------
|
| Normally config.php already defines BASE_URL.
|
|--------------------------------------------------------------------------
*/

$vendorSidebarBaseUrl =
    defined('BASE_URL')
        ? rtrim(
            BASE_URL,
            '/'
        ) . '/'
        : '/hochipohub/';


/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorSidebarEscape')) {

    function vendorSidebarEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER['PHP_SELF']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| CURRENT DIRECTORY
|--------------------------------------------------------------------------
|
| This is useful because dashboard.php also exists at project root.
|
|--------------------------------------------------------------------------
*/

$currentScript =
    str_replace(
        '\\',
        '/',
        $_SERVER['PHP_SELF']
        ?? ''
    );


$isSellerDirectory =
    strpos(
        $currentScript,
        '/seller/'
    ) !== false;


/*
|--------------------------------------------------------------------------
| ACTIVE MENU HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorSidebarActive')) {

    function vendorSidebarActive(
        $pages
    ): string {

        global $currentPage;


        if (!is_array($pages)) {

            $pages = [
                $pages
            ];

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
| DASHBOARD ACTIVE HELPER
|--------------------------------------------------------------------------
|
| We only highlight Seller Dashboard when the current dashboard.php
| is actually inside /seller/.
|
|--------------------------------------------------------------------------
*/

if (!function_exists('vendorSidebarDashboardActive')) {

    function vendorSidebarDashboardActive(): string
    {
        global $currentPage;
        global $isSellerDirectory;


        return (
            $currentPage === 'dashboard.php' &&
            $isSellerDirectory
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
| If current page already has $vendor, use it.
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
    isset(
        $vendor['business_logo']
    ) &&
    !empty(
        $vendor['business_logo']
    )
) {

    $sidebarLogo =
        $vendorSidebarBaseUrl .
        'uploads/vendors/' .
        rawurlencode(
            basename(
                $vendor['business_logo']
            )
        );

}


/*
|--------------------------------------------------------------------------
| URLS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Every URL below is absolute relative to HOCHIPOHUB BASE_URL.
|
|--------------------------------------------------------------------------
*/

$sidebarDashboardUrl =
    $vendorSidebarBaseUrl .
    'seller/dashboard.php';


$sidebarProductsUrl =
    $vendorSidebarBaseUrl .
    'seller/products.php';


$sidebarAddProductUrl =
    $vendorSidebarBaseUrl .
    'seller/add_product.php';


$sidebarOrdersUrl =
    $vendorSidebarBaseUrl .
    'seller/orders.php';


$sidebarSalesUrl =
    $vendorSidebarBaseUrl .
    'seller/sales.php';


$sidebarInventoryUrl =
    $vendorSidebarBaseUrl .
    'inventory.php';


$sidebarCommissionUrl =
    $vendorSidebarBaseUrl .
    'commission.php';


$sidebarStoreProfileUrl =
    $vendorSidebarBaseUrl .
    'seller/setup_profile.php';


$sidebarCatalogUrl =
    $vendorSidebarBaseUrl .
    'catalog.php';


$sidebarHomeUrl =
    $vendorSidebarBaseUrl .
    'index.php';


$sidebarProfileUrl =
    $vendorSidebarBaseUrl .
    'profile.php';


$sidebarLogoutUrl =
    $vendorSidebarBaseUrl .
    'auth/logout.php';

?>


<!-- ===============================================================
     VENDOR SIDEBAR
================================================================ -->

<aside
    class="vendor-sidebar"
    id="vendorSidebar"
>


    <!-- ===========================================================
         BRAND
    ============================================================ -->

    <div class="vendor-sidebar-brand">


        <a
            href="<?= vendorSidebarEscape(
                $sidebarDashboardUrl
            ) ?>"
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



        <!-- =======================================================
             MOBILE CLOSE
        ======================================================== -->

        <button
            type="button"
            class="vendor-sidebar-close"
            id="vendorSidebarClose"
            aria-label="Close sidebar"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


    </div>



    <!-- ===========================================================
         PROFILE
    ============================================================ -->

    <div class="vendor-sidebar-profile">


        <!-- =======================================================
             AVATAR
        ======================================================== -->

        <div class="vendor-sidebar-avatar">


            <?php if (
                $sidebarLogo !== ''
            ): ?>


                <img
                    src="<?= vendorSidebarEscape(
                        $sidebarLogo
                    ) ?>"
                    alt="<?= vendorSidebarEscape(
                        $sidebarBusinessName
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



        <!-- =======================================================
             PROFILE TEXT
        ======================================================== -->

        <div class="vendor-sidebar-profile-copy">


            <strong>

                <?= vendorSidebarEscape(
                    $sidebarBusinessName
                ) ?>

            </strong>


            <span>

                <?= vendorSidebarEscape(
                    $sidebarVendorName
                ) ?>

            </span>


            <small
                class="
                    vendor-account-status
                    <?= vendorSidebarEscape(
                        strtolower(
                            trim(
                                (string)
                                $sidebarStatus
                            )
                        )
                    ) ?>
                "
            >

                <?= vendorSidebarEscape(
                    $sidebarStatus
                ) ?>

            </small>


        </div>


    </div>



    <!-- ===========================================================
         NAVIGATION
    ============================================================ -->

    <nav class="vendor-sidebar-nav">


        <!-- =======================================================
             MAIN MENU LABEL
        ======================================================== -->

        <div class="vendor-sidebar-label">

            MAIN MENU

        </div>



        <!-- =======================================================
             DASHBOARD
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarDashboardUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarDashboardActive() ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-table-columns"></i>

            </span>


            <span class="vendor-link-text">

                Dashboard

            </span>


        </a>



        <!-- =======================================================
             PRODUCTS
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarProductsUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive([
                    'products.php',
                    'edit_product.php'
                ]) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-cube"></i>

            </span>


            <span class="vendor-link-text">

                My Products

            </span>


        </a>



        <!-- =======================================================
             ADD PRODUCT
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarAddProductUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'add_product.php'
                ) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-circle-plus"></i>

            </span>


            <span class="vendor-link-text">

                Add Product

            </span>


        </a>



        <!-- =======================================================
             ORDERS
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarOrdersUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'orders.php'
                ) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-bag-shopping"></i>

            </span>


            <span class="vendor-link-text">

                Orders

            </span>


        </a>



        <!-- =======================================================
             SALES
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarSalesUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'sales.php'
                ) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-chart-column"></i>

            </span>


            <span class="vendor-link-text">

                Sales

            </span>


        </a>



        <!-- =======================================================
             MANAGEMENT LABEL
        ======================================================== -->

        <div
            class="
                vendor-sidebar-label
                vendor-label-space
            "
        >

            MANAGEMENT

        </div>



        <!-- =======================================================
             INVENTORY
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarInventoryUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'inventory.php'
                ) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-warehouse"></i>

            </span>


            <span class="vendor-link-text">

                Inventory

            </span>


        </a>



        <!-- =======================================================
             COMMISSION
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarCommissionUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'commission.php'
                ) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-circle-dollar-to-slot"></i>

            </span>


            <span class="vendor-link-text">

                Commission

            </span>


        </a>



        <!-- =======================================================
             STORE PROFILE
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarStoreProfileUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'setup_profile.php'
                ) ?>
            "
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-store"></i>

            </span>


            <span class="vendor-link-text">

                Store Profile

            </span>


        </a>



        <!-- =======================================================
             DIVIDER
        ======================================================== -->

        <div class="vendor-sidebar-divider"></div>



        <!-- =======================================================
             VIEW MARKETPLACE
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarCatalogUrl
            ) ?>"
            class="vendor-sidebar-link"
        >


            <span class="vendor-link-icon">

                <i class="fa-solid fa-cart-shopping"></i>

            </span>


            <span class="vendor-link-text">

                View Marketplace

            </span>


        </a>



        <!-- =======================================================
             HOME
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarHomeUrl
            ) ?>"
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



    <!-- ===========================================================
         SIDEBAR FOOTER
    ============================================================ -->

    <div class="vendor-sidebar-footer">


        <!-- =======================================================
             PROFILE
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarProfileUrl
            ) ?>"
            class="vendor-footer-link"
        >


            <span>

                <i class="fa-solid fa-user"></i>

            </span>


            <span>

                My Profile

            </span>


        </a>



        <!-- =======================================================
             LOGOUT
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarLogoutUrl
            ) ?>"
            class="
                vendor-footer-link
                logout
            "
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



<!-- ===============================================================
     MOBILE SIDEBAR BUTTON
================================================================ -->

<button
    type="button"
    class="seller-mobile-menu"
    data-vendor-sidebar-toggle
    aria-label="Open seller navigation"
>

    <i class="fa-solid fa-bars"></i>

</button>



<!-- ===============================================================
     MOBILE OVERLAY
================================================================ -->

<div
    class="vendor-sidebar-overlay"
    id="vendorSidebarOverlay"
></div>



<script>

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB VENDOR SIDEBAR
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

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


        const toggleButtons =
            document.querySelectorAll(
                '[data-vendor-sidebar-toggle]'
            );


        /*
        |--------------------------------------------------------------------------
        | OPEN SIDEBAR
        |--------------------------------------------------------------------------
        */

        function openVendorSidebar() {

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


            document.body.classList.add(
                'vendor-sidebar-open'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CLOSE SIDEBAR
        |--------------------------------------------------------------------------
        */

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


            document.body.classList.remove(
                'vendor-sidebar-open'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | TOGGLE BUTTONS
        |--------------------------------------------------------------------------
        */

        toggleButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        if (
                            sidebar &&
                            sidebar.classList.contains(
                                'open'
                            )
                        ) {

                            closeVendorSidebar();

                        }

                        else {

                            openVendorSidebar();

                        }

                    }
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE BUTTON
        |--------------------------------------------------------------------------
        */

        if (closeButton) {

            closeButton.addEventListener(
                'click',
                closeVendorSidebar
            );

        }


        /*
        |--------------------------------------------------------------------------
        | OVERLAY
        |--------------------------------------------------------------------------
        */

        if (overlay) {

            overlay.addEventListener(
                'click',
                closeVendorSidebar
            );

        }


        /*
        |--------------------------------------------------------------------------
        | ESC KEY
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {

                    closeVendorSidebar();

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | MOBILE LINK CLICK
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '.vendor-sidebar-link, .vendor-footer-link'
            )
            .forEach(
                function (link) {

                    link.addEventListener(
                        'click',
                        function () {

                            if (
                                window.innerWidth <= 768
                            ) {

                                closeVendorSidebar();

                            }

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | WINDOW RESIZE
        |--------------------------------------------------------------------------
        */

        window.addEventListener(
            'resize',
            function () {

                if (
                    window.innerWidth > 768
                ) {

                    closeVendorSidebar();

                }

            }
        );

    }
);

</script>