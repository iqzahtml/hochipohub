<?php
/**
 * HOCHIPOHUB
 * Shared Admin Sidebar
 *
 * This file contains ONLY the shared admin navigation UI.
 * Do not place another sidebar HTML inside individual admin pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

$adminName = $_SESSION['name'] ?? 'Administrator';
$adminEmail = $_SESSION['email'] ?? 'admin@hochipoHub.com';

$menuItems = [
    [
        'label' => 'Dashboard',
        'url'   => 'dashboard.php',
        'icon'  => 'dashboard'
    ],
    [
        'label' => 'Users',
        'url'   => 'users.php',
        'icon'  => 'users'
    ],
    [
        'label' => 'Vendors',
        'url'   => 'vendors.php',
        'icon'  => 'vendors'
    ],
    [
        'label' => 'Products',
        'url'   => 'products.php',
        'icon'  => 'products'
    ],
    [
        'label' => 'Orders',
        'url'   => 'orders.php',
        'icon'  => 'orders'
    ],
    [
        'label' => 'Payments',
        'url'   => 'payments.php',
        'icon'  => 'payments'
    ],
    [
        'label' => 'Commission',
        'url'   => 'commission.php',
        'icon'  => 'commission'
    ],
    [
        'label' => 'Reviews',
        'url'   => 'reviews.php',
        'icon'  => 'reviews'
    ],
    [
        'label' => 'Settings',
        'url'   => 'settings.php',
        'icon'  => 'settings'
    ]
];

function adminSidebarIcon(string $icon): string
{
    switch ($icon) {

        case 'dashboard':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                </svg>
            ';

        case 'users':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            ';

        case 'vendors':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 10h18"></path>
                    <path d="M5 10v10h14V10"></path>
                    <path d="M3 10l2-7h14l2 7"></path>
                    <path d="M8 20v-6h8v6"></path>
                </svg>
            ';

        case 'products':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
            ';

        case 'orders':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 2h9l5 5v15H6z"></path>
                    <path d="M14 2v6h6"></path>
                    <path d="M9 13h6"></path>
                    <path d="M9 17h6"></path>
                </svg>
            ';

        case 'payments':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                    <line x1="6" y1="15" x2="10" y2="15"></line>
                </svg>
            ';

        case 'commission':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M8 15c1 1 2.2 1.5 4 1.5 2.2 0 3.5-1 3.5-2.5S14 11.5 12 11.5 8.5 10.5 8.5 9 10 6.5 12 6.5c1.5 0 2.7.4 3.5 1"></path>
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                </svg>
            ';

        case 'reviews':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 11.5a8.38 8.38 0 0 1-9 8.5 9.1 9.1 0 0 1-4-.9L3 21l1.9-4.2A8.3 8.3 0 0 1 3 11.5 8.5 8.5 0 0 1 12 3a8.5 8.5 0 0 1 9 8.5z"></path>
                    <path d="M8 12h.01"></path>
                    <path d="M12 12h.01"></path>
                    <path d="M16 12h.01"></path>
                </svg>
            ';

        case 'settings':
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 1.7-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.4v-.09a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.7-1.7.06-.06A1.7 1.7 0 0 0 8.4 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.4h.84A1.7 1.7 0 0 0 8.4 10a1.7 1.7 0 0 0-.34-1.88L8 8.06l1.7-1.7.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 12.67 5.2V4h2.4v1.2a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06 1.7 1.7-.06.06A1.7 1.7 0 0 0 19.34 10a1.7 1.7 0 0 0 1.56 1.03H21v2.4h-.1A1.7 1.7 0 0 0 19.4 15z"></path>
                </svg>
            ';

        default:
            return '
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                </svg>
            ';
    }
}
?>

<!-- MOBILE TOP BAR -->

<div class="admin-mobile-bar">

    <button
        type="button"
        class="admin-mobile-toggle"
        id="adminMobileOpen"
        aria-label="Open admin navigation"
        aria-controls="adminSidebar"
        aria-expanded="false"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="admin-mobile-brand">
        <strong>HochipoHub</strong>
        <small>Admin Panel</small>
    </div>

</div>


<!-- SIDEBAR OVERLAY -->

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>


<!-- SIDEBAR -->

<aside
    class="admin-sidebar"
    id="adminSidebar"
>

    <div class="admin-sidebar-inner">

        <!-- BRAND -->

        <div class="admin-brand">

            <a
                href="dashboard.php"
                class="admin-brand-link"
            >

                <div class="admin-brand-mark">
                    H
                </div>

                <div class="admin-brand-text">

                    <strong>
                        HochipoHub
                    </strong>

                    <span>
                        Administration Panel
                    </span>

                </div>

            </a>


            <button
                type="button"
                class="admin-sidebar-close"
                id="adminSidebarClose"
                aria-label="Close navigation"
            >
                &times;
            </button>

        </div>


        <!-- ADMIN PROFILE -->

        <div class="admin-sidebar-profile">

            <div class="admin-avatar">

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            trim($adminName),
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

            <div class="admin-profile-info">

                <strong>
                    <?= htmlspecialchars($adminName) ?>
                </strong>

                <span>
                    <?= htmlspecialchars($adminEmail) ?>
                </span>

                <small>
                    <i></i>
                    Administrator
                </small>

            </div>

        </div>


        <!-- NAVIGATION -->

        <nav class="admin-navigation">

            <div class="admin-nav-label">
                MAIN MENU
            </div>


            <ul>

                <?php foreach ($menuItems as $item): ?>

                    <?php
                    $isActive =
                        $currentPage === $item['url'];
                    ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars($item['url']) ?>"
                            class="
                                admin-nav-link
                                <?= $isActive ? 'active' : '' ?>
                            "
                            <?= $isActive ? 'aria-current="page"' : '' ?>
                        >

                            <span class="admin-nav-icon">

                                <?= adminSidebarIcon(
                                    $item['icon']
                                ) ?>

                            </span>

                            <span class="admin-nav-text">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>

                            <?php if ($isActive): ?>

                                <span class="admin-nav-active-dot"></span>

                            <?php endif; ?>

                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        </nav>


        <!-- SIDEBAR FOOTER -->

        <div class="admin-sidebar-footer">

            <div class="admin-sidebar-tip">

                <div class="tip-icon">
                    ✦
                </div>

                <div>

                    <strong>
                        Admin Control
                    </strong>

                    <span>
                        Manage your marketplace from one place.
                    </span>

                </div>

            </div>


            <a
                href="../auth/logout.php"
                class="admin-logout-link"
            >

                <span class="admin-nav-icon">

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
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


<script>
(function () {

    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminSidebarOverlay');
    const openButton = document.getElementById('adminMobileOpen');
    const closeButton = document.getElementById('adminSidebarClose');

    if (!sidebar || !overlay || !openButton) {
        return;
    }

    function openSidebar() {

        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');

        openButton.setAttribute(
            'aria-expanded',
            'true'
        );

        document.body.classList.add(
            'admin-sidebar-open'
        );
    }


    function closeSidebar() {

        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');

        openButton.setAttribute(
            'aria-expanded',
            'false'
        );

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


    sidebar
        .querySelectorAll('a')
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth <= 1100
                    ) {
                        closeSidebar();
                    }

                }
            );

        });


    window.addEventListener(
        'resize',
        function () {

            if (
                window.innerWidth > 1100
            ) {
                closeSidebar();
            }

        }
    );

})();
</script>