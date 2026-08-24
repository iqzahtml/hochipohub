<?php
/**
 * HOCHIPOHUB
 * Shared Admin Sidebar
 *
 * IMPORTANT:
 * This file contains ONLY the shared admin sidebar.
 * Do not create another sidebar manually inside admin pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');


/*
|--------------------------------------------------------------------------
| ADMIN USER
|--------------------------------------------------------------------------
*/

$adminName = trim(
    $_SESSION['name'] ??
    $_SESSION['user_name'] ??
    'Administrator'
);

if ($adminName === '') {
    $adminName = 'Administrator';
}

$adminEmail = trim(
    $_SESSION['email'] ??
    $_SESSION['user_email'] ??
    ''
);

$adminInitial = strtoupper(
    substr(
        preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            $adminName
        ),
        0,
        1
    )
);

if ($adminInitial === '') {
    $adminInitial = 'A';
}


/*
|--------------------------------------------------------------------------
| ACTIVE PAGE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('adminNavActive')) {

    function adminNavActive(
        string $page,
        string $currentPage
    ): string {

        return $page === $currentPage
            ? 'active'
            : '';
    }
}

?>

<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<aside
    class="admin-sidebar"
    id="adminSidebar"
    aria-label="Admin navigation"
>

    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="admin-sidebar-brand">

        <a
            href="dashboard.php"
            class="admin-brand-link"
            aria-label="HochipoHub Admin Dashboard"
        >

            <div class="admin-brand-mark">

                <span>H</span>

            </div>


            <div class="admin-brand-text">

                <strong>
                    HochipoHub
                </strong>

                <span>
                    ADMIN PANEL
                </span>

            </div>

        </a>


        <!-- Mobile close -->

        <button
            type="button"
            class="admin-sidebar-close"
            id="adminSidebarClose"
            aria-label="Close sidebar"
        >

            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
            >

                <path
                    d="M6 6l12 12M18 6L6 18"
                />

            </svg>

        </button>

    </div>


    <!-- =====================================================
         ADMIN PROFILE
    ====================================================== -->

    <div class="admin-profile-card">

        <div class="admin-profile-avatar">

            <?= htmlspecialchars(
                $adminInitial,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>


        <div class="admin-profile-info">

            <strong>

                <?= htmlspecialchars(
                    $adminName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </strong>

            <span>

                Administrator

            </span>

        </div>


        <span
            class="admin-online-dot"
            title="Online"
        ></span>

    </div>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <div class="admin-nav-heading">

        <span>
            MAIN MENU
        </span>

    </div>


    <nav class="admin-nav">

        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="<?= adminNavActive(
                'dashboard.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                        rx="1.5"
                    />

                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                        rx="1.5"
                    />

                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                        rx="1.5"
                    />

                    <rect
                        x="14"
                        y="14"
                        width="7"
                        height="7"
                        rx="1.5"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Dashboard
            </span>

        </a>


        <!-- USERS -->

        <a
            href="users.php"
            class="<?= adminNavActive(
                'users.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <circle
                        cx="9"
                        cy="8"
                        r="3"
                    />

                    <path
                        d="M3.5 20c.6-3.1 2.4-5 5.5-5s4.9 1.9 5.5 5"
                    />

                    <path
                        d="M16 11c2.5.1 4.1 1.6 4.5 4"
                    />

                    <path
                        d="M16 5.2a3 3 0 0 1 0 5.6"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Users
            </span>

        </a>


        <!-- VENDORS -->

        <a
            href="vendors.php"
            class="<?= adminNavActive(
                'vendors.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <path
                        d="M4 10h16"
                    />

                    <path
                        d="M5 10l1-5h12l1 5"
                    />

                    <path
                        d="M6 10v9h12v-9"
                    />

                    <path
                        d="M9 19v-5h6v5"
                    />

                    <path
                        d="M4 10c0 1.2 1 2 2.2 2S8.5 11.2 8.5 10c0 1.2 1 2 2.3 2s2.3-.8 2.3-2c0 1.2 1 2 2.3 2s2.3-.8 2.3-2c0 1.2 1 2 2.3 2S20 11.2 20 10"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Vendors
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="products.php"
            class="<?= adminNavActive(
                'products.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <path
                        d="M4 7.5L12 3l8 4.5v9L12 21l-8-4.5z"
                    />

                    <path
                        d="M4 7.5L12 12l8-4.5"
                    />

                    <path
                        d="M12 12v9"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Products
            </span>

        </a>


        <!-- ORDERS -->

        <a
            href="orders.php"
            class="<?= adminNavActive(
                'orders.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <path
                        d="M5 5h14v15H5z"
                    />

                    <path
                        d="M8 3v4M16 3v4"
                    />

                    <path
                        d="M8 11h8M8 15h5"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Orders
            </span>

        </a>


        <!-- PAYMENTS -->

        <a
            href="payments.php"
            class="<?= adminNavActive(
                'payments.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <rect
                        x="3"
                        y="5"
                        width="18"
                        height="14"
                        rx="2"
                    />

                    <path
                        d="M3 10h18"
                    />

                    <path
                        d="M7 15h4"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Payments
            </span>

        </a>


        <!-- COMMISSION -->

        <a
            href="commission.php"
            class="<?= adminNavActive(
                'commission.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <circle
                        cx="12"
                        cy="12"
                        r="8"
                    />

                    <path
                        d="M15 8.5c-.7-.7-1.7-1-3-1-1.8 0-3 .9-3 2.2 0 1.4 1.2 1.9 3 2.3 1.8.4 3 1 3 2.4 0 1.4-1.2 2.3-3 2.3-1.4 0-2.5-.4-3.2-1.2"
                    />

                    <path
                        d="M12 6v12"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Commission
            </span>

        </a>


        <!-- REVIEWS -->

        <a
            href="reviews.php"
            class="<?= adminNavActive(
                'reviews.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <path
                        d="M4 5h16v11H8l-4 4z"
                    />

                    <path
                        d="M8 9h8M8 12h5"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Reviews
            </span>

        </a>


        <!-- SETTINGS -->

        <a
            href="settings.php"
            class="<?= adminNavActive(
                'settings.php',
                $currentPage
            ) ?>"
        >

            <span class="admin-nav-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >

                    <path
                        d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z"
                    />

                    <path
                        d="M19 13.5a7.8 7.8 0 0 0 .1-1.5 7.8 7.8 0 0 0-.1-1.5l2-1.5-2-3.4-2.4 1a8.5 8.5 0 0 0-2.6-1.5L13.7 3h-3.4l-.3 2.6a8.5 8.5 0 0 0-2.6 1.5l-2.4-1-2 3.4 2 1.5a7.8 7.8 0 0 0-.1 1.5 7.8 7.8 0 0 0 .1 1.5l-2 1.5 2 3.4 2.4-1a8.5 8.5 0 0 0 2.6 1.5l.3 2.6h3.4l.3-2.6a8.5 8.5 0 0 0 2.6-1.5l2.4 1 2-3.4z"
                    />

                </svg>

            </span>

            <span class="admin-nav-label">
                Settings
            </span>

        </a>

    </nav>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->

    <div class="admin-sidebar-spacer"></div>


    <div class="admin-nav-heading admin-nav-heading-bottom">

        <span>
            ACCOUNT
        </span>

    </div>


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
                    d="M10 4H5v16h5"
                />

                <path
                    d="M14 8l4 4-4 4"
                />

                <path
                    d="M8 12h10"
                />

            </svg>

        </span>

        <span class="admin-nav-label">
            Logout
        </span>

    </a>


    <div class="admin-sidebar-footer">

        <span>
            HochipoHub
        </span>

        <small>
            Admin Control Center
        </small>

    </div>

</aside>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
    aria-hidden="true"
></div>


<!-- =========================================================
     MOBILE SIDEBAR BUTTON
========================================================= -->

<button
    type="button"
    class="admin-mobile-sidebar-button"
    id="adminMobileSidebarButton"
    aria-label="Open admin navigation"
    aria-controls="adminSidebar"
    aria-expanded="false"
>

    <svg
        viewBox="0 0 24 24"
        aria-hidden="true"
    >

        <path d="M4 6h16M4 12h16M4 18h16" />

    </svg>

</button>


<script>
(function () {

    function initAdminSidebar() {

        const sidebar =
            document.getElementById('adminSidebar');

        const overlay =
            document.getElementById(
                'adminSidebarOverlay'
            );

        const mobileButton =
            document.getElementById(
                'adminMobileSidebarButton'
            );

        const closeButton =
            document.getElementById(
                'adminSidebarClose'
            );

        const headerButtons =
            document.querySelectorAll(
                '.admin-sidebar-toggle'
            );

        if (!sidebar) {
            return;
        }


        function openSidebar() {

            document.body.classList.add(
                'admin-sidebar-open'
            );

            sidebar.classList.add(
                'is-open'
            );

            if (overlay) {
                overlay.classList.add(
                    'is-visible'
                );
            }

            if (mobileButton) {
                mobileButton.setAttribute(
                    'aria-expanded',
                    'true'
                );
            }

            headerButtons.forEach(
                function (button) {

                    button.setAttribute(
                        'aria-expanded',
                        'true'
                    );

                }
            );
        }


        function closeSidebar() {

            document.body.classList.remove(
                'admin-sidebar-open'
            );

            sidebar.classList.remove(
                'is-open'
            );

            if (overlay) {
                overlay.classList.remove(
                    'is-visible'
                );
            }

            if (mobileButton) {
                mobileButton.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }

            headerButtons.forEach(
                function (button) {

                    button.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }
            );
        }


        if (mobileButton) {

            mobileButton.addEventListener(
                'click',
                function () {

                    if (
                        sidebar.classList.contains(
                            'is-open'
                        )
                    ) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }

                }
            );
        }


        if (closeButton) {

            closeButton.addEventListener(
                'click',
                closeSidebar
            );
        }


        if (overlay) {

            overlay.addEventListener(
                'click',
                closeSidebar
            );
        }


        headerButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        if (
                            sidebar.classList.contains(
                                'is-open'
                            )
                        ) {
                            closeSidebar();
                        } else {
                            openSidebar();
                        }

                    }
                );

            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {
                    closeSidebar();
                }

            }
        );


        sidebar
            .querySelectorAll('a')
            .forEach(
                function (link) {

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

                }
            );


        window.addEventListener(
            'resize',
            function () {

                if (
                    window.innerWidth > 900
                ) {
                    closeSidebar();
                }

            }
        );

    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initAdminSidebar
        );

    } else {

        initAdminSidebar();

    }

})();
</script>