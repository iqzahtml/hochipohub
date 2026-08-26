<?php

/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - ADMIN SIDEBAR
|--------------------------------------------------------------------------
| File:
|     includes/admin_sidebar.php
|
| Purpose:
|     Shared navigation for all admin pages.
|
| Includes:
|     Dashboard
|     Users
|     Vendors
|     Products
|     Categories
|     Inventory
|     Orders
|     Payments
|     Commission
|     Reviews
|     Settings
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CURRENT ADMIN PAGE
|--------------------------------------------------------------------------
*/

$currentAdminPage =
    basename(
        parse_url(
            $_SERVER['REQUEST_URI'] ?? '',
            PHP_URL_PATH
        )
    );


/*
|--------------------------------------------------------------------------
| ACTIVE NAVIGATION HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('adminNavActive')) {

    function adminNavActive($page)
    {
        global $currentAdminPage;

        return $currentAdminPage === $page
            ? 'active'
            : '';
    }

}


/*
|--------------------------------------------------------------------------
| ADMIN NAME
|--------------------------------------------------------------------------
*/

$sidebarAdminName =
    $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? 'Administrator';


/*
|--------------------------------------------------------------------------
| ADMIN EMAIL
|--------------------------------------------------------------------------
*/

$sidebarAdminEmail =
    $_SESSION['email']
    ?? $_SESSION['user_email']
    ?? '';


/*
|--------------------------------------------------------------------------
| ADMIN INITIAL
|--------------------------------------------------------------------------
*/

$sidebarInitial =
    strtoupper(
        substr(
            trim($sidebarAdminName),
            0,
            1
        )
    );

if ($sidebarInitial === '') {
    $sidebarInitial = 'A';
}


/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('adminSidebarEscape')) {

    function adminSidebarEscape($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}

?>


<!-- =========================================================
     SIDEBAR OVERLAY
========================================================= -->

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
    onclick="closeAdminSidebar()"
></div>


<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<aside
    class="admin-sidebar"
    id="adminSidebar"
>


    <div class="admin-sidebar-inner">


        <!-- =================================================
             BRAND
        ================================================== -->

        <div class="admin-brand">

            <a
                href="dashboard.php"
                class="admin-brand-link"
            >

                <span class="admin-brand-mark">
                    H
                </span>

                <span class="admin-brand-text">

                    <strong>
                        HochipoHub
                    </strong>

                    <span>
                        ADMIN CONTROL CENTER
                    </span>

                </span>

            </a>


            <button
                type="button"
                class="admin-sidebar-close"
                onclick="closeAdminSidebar()"
                aria-label="Close admin menu"
            >
                ×
            </button>

        </div>


        <!-- =================================================
             ADMIN PROFILE
        ================================================== -->

        <div class="admin-sidebar-profile">


            <div class="admin-avatar">

                <?= adminSidebarEscape(
                    $sidebarInitial
                ) ?>

            </div>


            <div class="admin-profile-info">

                <strong>

                    <?= adminSidebarEscape(
                        $sidebarAdminName
                    ) ?>

                </strong>


                <span>

                    <?= adminSidebarEscape(
                        $sidebarAdminEmail !== ''
                            ? $sidebarAdminEmail
                            : 'Administrator'
                    ) ?>

                </span>


                <small>

                    <i></i>

                    Administrator

                </small>

            </div>


        </div>


        <!-- =================================================
             NAVIGATION
        ================================================== -->

        <nav
            class="admin-navigation"
            aria-label="Admin navigation"
        >


            <!-- MAIN MENU -->

            <div class="admin-nav-label">
                MAIN MENU
            </div>


            <ul>


                <!-- DASHBOARD -->

                <li>

                    <a
                        href="dashboard.php"
                        class="admin-nav-link <?= adminNavActive('dashboard.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <rect
                                    x="3"
                                    y="3"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="14"
                                    y="3"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="3"
                                    y="14"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="14"
                                    y="14"
                                    width="7"
                                    height="7"
                                    rx="1"
                                ></rect>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Dashboard
                        </span>


                        <?php if (
                            adminNavActive('dashboard.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- USERS -->

                <li>

                    <a
                        href="users.php"
                        class="admin-nav-link <?= adminNavActive('users.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                ></path>

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                ></circle>

                                <path
                                    d="M22 21v-2a4 4 0 0 0-3-3.87"
                                ></path>

                                <path
                                    d="M16 3.13a4 4 0 0 1 0 7.75"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Users
                        </span>


                        <?php if (
                            adminNavActive('users.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- VENDORS -->

                <li>

                    <a
                        href="vendors.php"
                        class="admin-nav-link <?= adminNavActive('vendors.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M3 10h18"
                                ></path>

                                <path
                                    d="M5 10v10h14V10"
                                ></path>

                                <path
                                    d="M3 10l2-6h14l2 6"
                                ></path>

                                <path
                                    d="M9 20v-6h6v6"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Vendors
                        </span>


                        <?php if (
                            adminNavActive('vendors.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- PRODUCTS -->

                <li>

                    <a
                        href="products.php"
                        class="admin-nav-link <?= adminNavActive('products.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"
                                ></path>

                                <polyline
                                    points="3.27 6.96 12 12.01 20.73 6.96"
                                ></polyline>

                                <line
                                    x1="12"
                                    y1="22.08"
                                    x2="12"
                                    y2="12"
                                ></line>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Products
                        </span>


                        <?php if (
                            adminNavActive('products.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- CATEGORIES -->

                <li>

                    <a
                        href="categories.php"
                        class="admin-nav-link <?= adminNavActive('categories.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M4 5h6v6H4z"
                                ></path>

                                <path
                                    d="M14 5h6v6h-6z"
                                ></path>

                                <path
                                    d="M4 15h6v4H4z"
                                ></path>

                                <path
                                    d="M14 15h6v4h-6z"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Categories
                        </span>


                        <?php if (
                            adminNavActive('categories.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- INVENTORY -->

                <li>

                    <a
                        href="inventory.php"
                        class="admin-nav-link <?= adminNavActive('inventory.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M21 8l-9-5-9 5 9 5 9-5z"
                                ></path>

                                <path
                                    d="M3 8v8l9 5 9-5V8"
                                ></path>

                                <path
                                    d="M12 13v8"
                                ></path>

                                <path
                                    d="M7.5 10.5l9-5"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Inventory
                        </span>


                        <?php if (
                            adminNavActive('inventory.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


            </ul>


            <!-- TRANSACTIONS -->

            <div
                class="admin-nav-label admin-nav-label-spaced"
            >
                TRANSACTIONS
            </div>


            <ul>


                <!-- ORDERS -->

                <li>

                    <a
                        href="orders.php"
                        class="admin-nav-link <?= adminNavActive('orders.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <circle
                                    cx="9"
                                    cy="20"
                                    r="1"
                                ></circle>

                                <circle
                                    cx="19"
                                    cy="20"
                                    r="1"
                                ></circle>

                                <path
                                    d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Orders
                        </span>


                        <?php if (
                            adminNavActive('orders.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- PAYMENTS -->

                <li>

                    <a
                        href="payments.php"
                        class="admin-nav-link <?= adminNavActive('payments.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <rect
                                    x="2"
                                    y="5"
                                    width="20"
                                    height="14"
                                    rx="2"
                                ></rect>

                                <line
                                    x1="2"
                                    y1="10"
                                    x2="22"
                                    y2="10"
                                ></line>

                                <line
                                    x1="6"
                                    y1="15"
                                    x2="10"
                                    y2="15"
                                ></line>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Payments
                        </span>


                        <?php if (
                            adminNavActive('payments.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- COMMISSION -->

                <li>

                    <a
                        href="commission.php"
                        class="admin-nav-link <?= adminNavActive('commission.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M12 1v22"
                                ></path>

                                <path
                                    d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Commission
                        </span>


                        <?php if (
                            adminNavActive('commission.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- REVIEWS -->

                <li>

                    <a
                        href="reviews.php"
                        class="admin-nav-link <?= adminNavActive('reviews.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Reviews
                        </span>


                        <?php if (
                            adminNavActive('reviews.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


            </ul>


            <!-- SYSTEM -->

            <div
                class="admin-nav-label admin-nav-label-spaced"
            >
                SYSTEM
            </div>


            <ul>


                <!-- SETTINGS -->

                <li>

                    <a
                        href="settings.php"
                        class="admin-nav-link <?= adminNavActive('settings.php') ?>"
                    >

                        <span class="admin-nav-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"
                                ></path>

                                <path
                                    d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 1.7-.06-.06A1.7 1.7 0 0 0 16.16 18a1.7 1.7 0 0 0-1.03 1.56V20h-2.4v-.44A1.7 1.7 0 0 0 11.7 18a1.7 1.7 0 0 0-1.88.34l-.06.06-1.7-1.7.06-.06A1.7 1.7 0 0 0 8.46 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.4h.9A1.7 1.7 0 0 0 8.46 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06 1.7-1.7.06.06A1.7 1.7 0 0 0 11.7 6a1.7 1.7 0 0 0 1.03-1.56V4h2.4v.44A1.7 1.7 0 0 0 16.16 6a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.7 1.7-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.56 1.03H22v2.4h-.9A1.7 1.7 0 0 0 19.4 15z"
                                ></path>

                            </svg>

                        </span>


                        <span class="admin-nav-text">
                            Settings
                        </span>


                        <?php if (
                            adminNavActive('settings.php')
                        ): ?>

                            <span
                                class="admin-nav-active-dot"
                            ></span>

                        <?php endif; ?>

                    </a>

                </li>


            </ul>


        </nav>


        <!-- =================================================
             SIDEBAR FOOTER
        ================================================== -->

        <div class="admin-sidebar-footer">


            <div class="admin-sidebar-tip">

                <div class="tip-icon">

                    <svg
                        viewBox="0 0 24 24"
                        width="15"
                        height="15"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        ></circle>

                        <path
                            d="M12 10v6"
                        ></path>

                        <circle
                            cx="12"
                            cy="7"
                            r=".8"
                            fill="currentColor"
                        ></circle>

                    </svg>

                </div>


                <div>

                    <strong>
                        Admin Control
                    </strong>

                    <span>
                        Keep your marketplace healthy and organised.
                    </span>

                </div>

            </div>


            <!-- LOGOUT -->

            <a
                href="../auth/logout.php"
                class="admin-logout-link"
            >

                <span class="admin-nav-icon">

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M10 17l5-5-5-5"
                        ></path>

                        <path
                            d="M15 12H3"
                        ></path>

                        <path
                            d="M21 19V5a2 2 0 0 0-2-2h-7"
                        ></path>

                    </svg>

                </span>


                <span>
                    Logout
                </span>

            </a>


            <div class="admin-sidebar-copyright">

                © <?= date('Y') ?> HochipoHub

            </div>


        </div>


    </div>

</aside>


<!-- =========================================================
     MOBILE ADMIN BAR
========================================================= -->

<div class="admin-mobile-bar">

    <button
        type="button"
        class="admin-mobile-toggle"
        onclick="openAdminSidebar()"
        aria-label="Open admin menu"
    >

        <span></span>
        <span></span>
        <span></span>

    </button>


    <div class="admin-mobile-brand">

        <strong>
            HochipoHub Admin
        </strong>

        <small>
            Control Center
        </small>

    </div>

</div>


<script>

(function () {

    window.openAdminSidebar = function () {

        const sidebar =
            document.getElementById(
                'adminSidebar'
            );

        const overlay =
            document.getElementById(
                'adminSidebarOverlay'
            );

        if (sidebar) {
            sidebar.classList.add(
                'is-open'
            );
        }

        if (overlay) {
            overlay.classList.add(
                'is-visible'
            );
        }

        document.body.classList.add(
            'admin-sidebar-open'
        );

    };


    window.closeAdminSidebar = function () {

        const sidebar =
            document.getElementById(
                'adminSidebar'
            );

        const overlay =
            document.getElementById(
                'adminSidebarOverlay'
            );

        if (sidebar) {
            sidebar.classList.remove(
                'is-open'
            );
        }

        if (overlay) {
            overlay.classList.remove(
                'is-visible'
            );
        }

        document.body.classList.remove(
            'admin-sidebar-open'
        );

    };


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {
                closeAdminSidebar();
            }

        }
    );


})();

</script>