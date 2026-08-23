<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN SIDEBAR
|--------------------------------------------------------------------------
| Shared sidebar for ALL admin pages.
| This file handles:
| - Sidebar navigation
| - Active page
| - Mobile hamburger
| - Overlay
| - Responsive sidebar
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

$current_page = basename($_SERVER['PHP_SELF']);

/*
|--------------------------------------------------------------------------
| ADMIN NAME
|--------------------------------------------------------------------------
*/

$admin_name = $_SESSION['name'] ?? 'Administrator';

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('admin_sidebar_e')) {

    function admin_sidebar_e($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
?>

<!--
|--------------------------------------------------------------------------
| MOBILE OVERLAY
|--------------------------------------------------------------------------
-->

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>


<!--
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
-->

<aside
    class="admin-sidebar"
    id="adminSidebar"
>

    <!-- SIDEBAR HEADER -->

    <div class="admin-sidebar-header">

        <a
            href="dashboard.php"
            class="admin-brand"
        >

            <div class="admin-brand-mark">
                H
            </div>

            <div class="admin-brand-text">

                <strong>
                    HOCHIPO<span>HUB</span>
                </strong>

                <small>
                    ADMIN PANEL
                </small>

            </div>

        </a>


        <!-- MOBILE CLOSE -->

        <button
            type="button"
            class="admin-sidebar-close"
            id="adminSidebarClose"
            aria-label="Close sidebar"
        >
            &times;
        </button>

    </div>


    <!-- ADMIN PROFILE -->

    <div class="admin-profile">

        <div class="admin-profile-avatar">
            <span>
                <?= strtoupper(
                    substr(
                        $admin_name,
                        0,
                        1
                    )
                ) ?>
            </span>
        </div>

        <div class="admin-profile-info">

            <strong>
                <?= admin_sidebar_e($admin_name) ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>

        <span class="admin-online-dot"></span>

    </div>


    <!-- NAVIGATION -->

    <nav
        class="admin-nav"
        aria-label="Admin navigation"
    >

        <!-- MAIN -->

        <div class="admin-nav-section">

            <span class="admin-nav-title">
                MAIN
            </span>


            <a
                href="dashboard.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'dashboard.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Dashboard
                </span>

            </a>

        </div>


        <!-- MANAGEMENT -->

        <div class="admin-nav-section">

            <span class="admin-nav-title">
                MANAGEMENT
            </span>


            <a
                href="products.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'products.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M20 8h-3V4H7v4H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2zM9 6h6v2H9V6zm6 9H9v-2h6v2z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Products
                </span>

            </a>


            <a
                href="users.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'users.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.92 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Users
                </span>

            </a>


            <a
                href="vendors.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'vendors.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm-1 4H5V6h14v2zm-9 7H5v-2h5v2zm4 0h-2v-2h2v2zm3 0h-2v-2h2v2z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Vendors
                </span>

            </a>


            <a
                href="orders.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'orders.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.44C5.09 14.32 5 14.65 5 15c0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03L20.88 5H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Orders
                </span>

            </a>


            <a
                href="payments.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'payments.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Payments
                </span>

            </a>


            <a
                href="commission.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'commission.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M11 17h2v-2h-2v2zm0-4h2V7h-2v6zm1-11C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Commission
                </span>

            </a>


            <a
                href="reviews.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'reviews.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Reviews
                </span>

            </a>

        </div>


        <!-- SYSTEM -->

        <div class="admin-nav-section">

            <span class="admin-nav-title">
                SYSTEM
            </span>


            <a
                href="settings.php"
                class="
                    admin-nav-link
                    <?= $current_page === 'settings.php'
                        ? 'active'
                        : '' ?>
                "
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.37-.31-.6-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98L14.5 2.42C14.47 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.5.42L9.12 5.07c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.08-.48 0-.6.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.08.65-.08.98s.03.66.08.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.37.31.6.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1c.23.08.48 0 .6-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Settings
                </span>

            </a>


            <a
                href="../index.php"
                class="admin-nav-link"
            >

                <span class="admin-nav-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"
                        />
                    </svg>
                </span>

                <span class="admin-nav-label">
                    Back to Website
                </span>

            </a>

        </div>

    </nav>


    <!-- SIDEBAR FOOTER -->

    <div class="admin-sidebar-footer">

        <a
            href="../auth/logout.php"
            class="admin-logout"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.1 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"
                    />
                </svg>

            </span>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!--
|--------------------------------------------------------------------------
| MOBILE TOPBAR
|--------------------------------------------------------------------------
-->

<header class="admin-mobile-header">

    <button
        type="button"
        class="admin-menu-button"
        id="adminSidebarOpen"
        aria-label="Open navigation"
    >

        <span></span>
        <span></span>
        <span></span>

    </button>


    <a
        href="dashboard.php"
        class="admin-mobile-brand"
    >
        Hochipo<span>Hub</span>
    </a>

</header>


<!--
|--------------------------------------------------------------------------
| SIDEBAR JAVASCRIPT
|--------------------------------------------------------------------------
-->

<script>

(function () {

    const sidebar =
        document.getElementById('adminSidebar');

    const overlay =
        document.getElementById('adminSidebarOverlay');

    const openButton =
        document.getElementById('adminSidebarOpen');

    const closeButton =
        document.getElementById('adminSidebarClose');


    if (!sidebar || !overlay || !openButton) {
        return;
    }


    function openSidebar() {

        sidebar.classList.add('is-open');

        overlay.classList.add('is-visible');

        document.body.classList.add(
            'admin-sidebar-open'
        );

    }


    function closeSidebar() {

        sidebar.classList.remove('is-open');

        overlay.classList.remove('is-visible');

        document.body.classList.remove(
            'admin-sidebar-open'
        );

    }


    openButton.addEventListener(
        'click',
        openSidebar
    );


    if (closeButton) {

        closeButton.addEventListener(
            'click',
            closeSidebar
        );

    }


    overlay.addEventListener(
        'click',
        closeSidebar
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeSidebar();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLOSE MOBILE SIDEBAR WHEN LINK IS CLICKED
    |--------------------------------------------------------------------------
    */

    sidebar
        .querySelectorAll('a')
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth <= 900
                    ) {

                        closeSidebar();

                    }

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | RESET WHEN SCREEN BECOMES DESKTOP
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 900) {

                closeSidebar();

            }

        }
    );

})();

</script>