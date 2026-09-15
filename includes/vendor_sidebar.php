<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR SIDEBAR
|--------------------------------------------------------------------------
| File:
| includes/vendor_sidebar.php
|
| Shared seller sidebar for:
| - seller/dashboard.php
| - seller/products.php
| - seller/add_product.php
| - seller/orders.php
| - seller/sales.php
| - seller/messages.php
| - seller/setup_profile.php
| - inventory.php
| - commission.php
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
| BASE URL
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
| ESCAPE
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
| CURRENT SCRIPT
|--------------------------------------------------------------------------
*/

$currentScript =
    str_replace(
        '\\',
        '/',
        $_SERVER['PHP_SELF']
        ?? ''
    );


$currentPage =
    basename(
        $currentScript
    );


$isSellerDirectory =
    strpos(
        $currentScript,
        '/seller/'
    ) !== false;


/*
|--------------------------------------------------------------------------
| ACTIVE HELPERS
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
| DATABASE
|--------------------------------------------------------------------------
|
| Sidebar boleh load sendiri vendor information jika page
| tak provide $vendor.
|--------------------------------------------------------------------------
*/

$sidebarDb = null;


try {

    if (
        function_exists('getDB')
    ) {

        $sidebarDb =
            getDB();
    }

} catch (Throwable $e) {

    $sidebarDb = null;
}


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$sidebarUserId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| LOAD VENDOR IF PAGE DOES NOT HAVE IT
|--------------------------------------------------------------------------
*/

if (
    (
        !isset($vendor) ||
        !is_array($vendor) ||
        empty($vendor)
    ) &&
    $sidebarUserId > 0 &&
    $sidebarDb instanceof PDO
) {

    try {

        $sidebarVendorStmt =
            $sidebarDb->prepare("
                SELECT
                    v.*,
                    u.name,
                    u.email,
                    u.phone

                FROM vendors v

                INNER JOIN users u
                    ON v.user_id = u.user_id

                WHERE v.user_id = ?

                LIMIT 1
            ");


        $sidebarVendorStmt->execute([
            $sidebarUserId
        ]);


        $sidebarLoadedVendor =
            $sidebarVendorStmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($sidebarLoadedVendor) {

            $vendor =
                $sidebarLoadedVendor;
        }

    } catch (Throwable $e) {

        // Sidebar still works using session fallback.
    }
}


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
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


$sidebarVendorId =
    (int) (
        $vendor['vendor_id']
        ?? $_SESSION['vendor_id']
        ?? 0
    );


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
    trim(
        (string)
        $vendor['business_logo']
    ) !== ''
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
| PENDING ORDERS NOTIFICATION
|--------------------------------------------------------------------------
*/

$sidebarPendingOrders = 0;


if (
    $sidebarVendorId > 0 &&
    $sidebarDb instanceof PDO
) {

    try {

        $sidebarOrderStmt =
            $sidebarDb->prepare("
                SELECT
                    COUNT(*)

                FROM vendor_orders

                WHERE vendor_id = ?
                  AND vendor_status = 'Pending'
            ");


        $sidebarOrderStmt->execute([
            $sidebarVendorId
        ]);


        $sidebarPendingOrders =
            (int)
            $sidebarOrderStmt->fetchColumn();

    } catch (Throwable $e) {

        $sidebarPendingOrders = 0;
    }
}


/*
|--------------------------------------------------------------------------
| URLS
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


$sidebarPendingOrdersUrl =
    $vendorSidebarBaseUrl .
    'seller/orders.php?status=Pending';


$sidebarSalesUrl =
    $vendorSidebarBaseUrl .
    'seller/sales.php';


$sidebarMessagesUrl =
    $vendorSidebarBaseUrl .
    'seller/messages.php';


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


<style>

/*
|--------------------------------------------------------------------------
| SIDEBAR SAFETY FIX
|--------------------------------------------------------------------------
| Prevent seller page content from sitting above sidebar links.
|--------------------------------------------------------------------------
*/

.vendor-sidebar {

    position:
        fixed !important;

    top:
        0 !important;

    left:
        0 !important;

    bottom:
        0 !important;

    z-index:
        10000 !important;

    pointer-events:
        auto !important;

    isolation:
        isolate;
}


/*
|--------------------------------------------------------------------------
| LINKS
|--------------------------------------------------------------------------
*/

.vendor-sidebar-nav {

    position:
        relative;

    z-index:
        2;

    pointer-events:
        auto !important;
}


.vendor-sidebar-link {

    position:
        relative !important;

    z-index:
        3 !important;

    width:
        100%;

    pointer-events:
        auto !important;

    cursor:
        pointer !important;

    overflow:
        hidden;
}


.vendor-sidebar-link > * {

    position:
        relative;

    z-index:
        1;

    pointer-events:
        none;
}


/*
|--------------------------------------------------------------------------
| ORDER NOTIFICATION BADGE
|--------------------------------------------------------------------------
*/

.vendor-order-notification {

    min-width:
        20px;

    height:
        20px;

    padding:
        0 6px;

    margin-left:
        auto;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        #ef4444;

    border:
        2px solid
        rgba(
            255,
            255,
            255,
            .15
        );

    border-radius:
        999px;

    box-shadow:
        0 5px 12px
        rgba(
            239,
            68,
            68,
            .25
        );

    font-size:
        9px;

    font-weight:
        900;

    line-height:
        1;

    pointer-events:
        none;
}


/*
|--------------------------------------------------------------------------
| ACTIVE LINK SAFETY
|--------------------------------------------------------------------------
*/

.vendor-sidebar-link.active {

    z-index:
        4 !important;
}


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.vendor-sidebar-footer {

    position:
        relative;

    z-index:
        3;

    pointer-events:
        auto;
}


.vendor-footer-link {

    position:
        relative;

    z-index:
        4;

    pointer-events:
        auto !important;
}


.vendor-footer-link > * {

    pointer-events:
        none;
}


/*
|--------------------------------------------------------------------------
| MOBILE BUTTON
|--------------------------------------------------------------------------
*/

.seller-mobile-menu {

    position:
        fixed;

    z-index:
        10020;
}


/*
|--------------------------------------------------------------------------
| OVERLAY
|--------------------------------------------------------------------------
*/

.vendor-sidebar-overlay {

    position:
        fixed;

    inset:
        0;

    z-index:
        9990;

    pointer-events:
        none;
}


/*
|--------------------------------------------------------------------------
| DESKTOP
|--------------------------------------------------------------------------
*/

@media (
    min-width: 769px
) {

    .vendor-sidebar-overlay {

        display:
            none !important;

        pointer-events:
            none !important;
    }


    .seller-mobile-menu {

        display:
            none !important;
    }
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (
    max-width: 768px
) {

    .vendor-sidebar-overlay.show {

        display:
            block;

        pointer-events:
            auto;
    }

}

</style>


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


        <!-- MAIN MENU -->

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
             MY PRODUCTS
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


            <?php if (
                $sidebarPendingOrders > 0
            ): ?>

                <span
                    class="vendor-order-notification"
                    title="<?= vendorSidebarEscape(
                        $sidebarPendingOrders .
                        ' pending order(s)'
                    ) ?>"
                >

                    <?= $sidebarPendingOrders > 99
                        ? '99+'
                        : (int)
                            $sidebarPendingOrders ?>

                </span>

            <?php endif; ?>

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
             MESSAGES
        ======================================================== -->

        <a
            href="<?= vendorSidebarEscape(
                $sidebarMessagesUrl
            ) ?>"
            class="
                vendor-sidebar-link
                <?= vendorSidebarActive(
                    'messages.php'
                ) ?>
            "
        >

            <span class="vendor-link-icon">

                <i class="fa-solid fa-message"></i>

            </span>


            <span class="vendor-link-text">

                Messages

            </span>

        </a>


        <!-- =======================================================
             MANAGEMENT
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


        <!-- PROFILE -->

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


        <!-- LOGOUT -->

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
     MOBILE OPEN BUTTON
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
| HOCHIPOHUB - VENDOR SIDEBAR
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
        | OPEN
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
        | CLOSE
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
        | TOGGLE
        |--------------------------------------------------------------------------
        */

        toggleButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function (event) {

                        event.preventDefault();

                        event.stopPropagation();


                        if (
                            sidebar &&
                            sidebar.classList.contains(
                                'open'
                            )
                        ) {

                            closeVendorSidebar();

                        } else {

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
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();

                    closeVendorSidebar();
                }
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
                function (event) {

                    event.preventDefault();

                    closeVendorSidebar();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ESC
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key ===
                    'Escape'
                ) {

                    closeVendorSidebar();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | MOBILE LINK
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
                                window.innerWidth <=
                                768
                            ) {

                                closeVendorSidebar();
                            }
                        }
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | DESKTOP RESIZE
        |--------------------------------------------------------------------------
        */

        window.addEventListener(
            'resize',
            function () {

                if (
                    window.innerWidth >
                    768
                ) {

                    closeVendorSidebar();
                }
            }
        );

    }
);

</script>