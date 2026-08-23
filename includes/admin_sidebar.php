<?php

$adminCurrentPage = basename($_SERVER['PHP_SELF'] ?? '');

$adminBaseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '../';

$adminName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'Administrator';

$adminEmail = $_SESSION['user_email']
    ?? $_SESSION['email']
    ?? '';

$parts = preg_split('/\s+/', trim((string) $adminName));

$adminInitials = count($parts) > 1
    ? strtoupper(
        substr($parts[0], 0, 1) .
        substr($parts[count($parts) - 1], 0, 1)
    )
    : strtoupper(substr($parts[0] ?? 'A', 0, 2));


function adminSidebarIcon(string $name): string
{
    $icons = [

        'dashboard' => '
            <svg viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
        ',

        'users' => '
            <svg viewBox="0 0 24 24">
                <path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20"/>
                <circle cx="10" cy="7" r="3.5"/>
                <path d="M16 5.5a3.5 3.5 0 0 1 0 6.8M18 14.2a4.5 4.5 0 0 1 2 4.3V20"/>
            </svg>
        ',

        'store' => '
            <svg viewBox="0 0 24 24">
                <path d="M4 10v10h16V10"/>
                <path d="M3 10l2-6h14l2 6"/>
                <path d="M3 10a3 3 0 0 0 5 2 3 3 0 0 0 5 0 3 3 0 0 0 5 0 3 3 0 0 0 3-2"/>
                <path d="M8 20v-5h8v5"/>
            </svg>
        ',

        'box' => '
            <svg viewBox="0 0 24 24">
                <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/>
                <path d="m4.5 7.8 7.5 4 7.5-4M12 12v9"/>
            </svg>
        ',

        'orders' => '
            <svg viewBox="0 0 24 24">
                <path d="M6 3h12v18H6z"/>
                <path d="M9 7h6M9 11h6M9 15h4"/>
            </svg>
        ',

        'card' => '
            <svg viewBox="0 0 24 24">
                <rect x="3" y="5" width="18" height="14" rx="2"/>
                <path d="M3 10h18M7 15h4"/>
            </svg>
        ',

        'grid' => '
            <svg viewBox="0 0 24 24">
                <rect x="4" y="4" width="6" height="6" rx="1"/>
                <rect x="14" y="4" width="6" height="6" rx="1"/>
                <rect x="4" y="14" width="6" height="6" rx="1"/>
                <rect x="14" y="14" width="6" height="6" rx="1"/>
            </svg>
        ',

        'logs' => '
            <svg viewBox="0 0 24 24">
                <path d="M5 4h14v16H5z"/>
                <path d="M8 8h8M8 12h8M8 16h5"/>
            </svg>
        '
    ];

    return $icons[$name] ?? $icons['dashboard'];
}


$groups = [

    'overview' => [
        ['dashboard.php', 'Dashboard', 'dashboard']
    ],

    'management' => [
        ['users.php', 'Users', 'users'],
        ['vendors.php', 'Vendors', 'store'],
        ['products.php', 'Products', 'box']
    ],

    'transactions' => [
        ['orders.php', 'Orders', 'orders'],
        ['payments.php', 'Payments', 'card']
    ],

    'system' => [
        ['categories.php', 'Categories', 'grid'],
        ['admin_logs.php', 'Admin Logs', 'logs']
    ]
];

?>

<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">

    <div class="admin-sidebar-inner">

        <!-- BRAND -->
        <div class="admin-brand">

            <a
                class="admin-brand-link"
                href="<?= htmlspecialchars(
                    $adminBaseUrl . 'admin/dashboard.php',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
                <span class="admin-brand-mark">H</span>

                <span class="admin-brand-text">
                    <strong>HochipoHub</strong>
                    <small>ADMIN PANEL</small>
                </span>
            </a>

            <button
                type="button"
                class="admin-sidebar-close"
                id="adminSidebarClose"
                aria-label="Close sidebar"
            >
                &times;
            </button>

        </div>


        <!-- NAVIGATION -->
        <nav class="admin-nav">

            <?php foreach ($groups as $group => $items): ?>

                <?php
                $visible = [];

                foreach ($items as $item) {
                    if (
                        $item[0] === 'dashboard.php' ||
                        file_exists(dirname(__DIR__) . '/admin/' . $item[0])
                    ) {
                        $visible[] = $item;
                    }
                }

                if (!$visible) {
                    continue;
                }
                ?>

                <div class="admin-nav-group">

                    <span class="admin-nav-label">
                        <?= htmlspecialchars(
                            ucwords($group),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <div class="admin-nav-list">

                        <?php foreach ($visible as $item): ?>

                            <?php
                            $active = $adminCurrentPage === $item[0];
                            ?>

                            <a
                                class="admin-nav-link<?= $active ? ' is-active' : '' ?>"
                                href="<?= htmlspecialchars(
                                    $adminBaseUrl . 'admin/' . $item[0],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $active ? 'aria-current="page"' : '' ?>
                            >

                                <span class="admin-nav-icon">
                                    <?= adminSidebarIcon($item[2]) ?>
                                </span>

                                <span>
                                    <?= htmlspecialchars(
                                        $item[1],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </nav>


        <!-- SPACER -->
        <div class="admin-sidebar-spacer"></div>


        <!-- ADMIN PROFILE -->
        <div class="admin-profile-card">

            <div class="admin-avatar">
                <?= htmlspecialchars(
                    $adminInitials,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="admin-profile-copy">

                <strong>
                    <?= htmlspecialchars(
                        $adminName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $adminEmail ?: 'Administrator',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

            </div>

            <span class="admin-online-dot"></span>

        </div>

    </div>

</aside>


<!-- SIDEBAR OVERLAY -->
<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
    aria-hidden="true"
></div>


<script>
(function () {

    function init() {

        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('adminSidebarOverlay');
        const toggle = document.getElementById('adminSidebarToggle');
        const closeButton = document.getElementById('adminSidebarClose');

        if (!sidebar || !overlay) {
            return;
        }

        const closeSidebar = () => {

            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-visible');
            document.body.classList.remove('sidebar-open');

            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        };

        const openSidebar = () => {

            sidebar.classList.add('is-open');
            overlay.classList.add('is-visible');
            document.body.classList.add('sidebar-open');

            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
        };


        if (toggle) {

            toggle.addEventListener('click', () => {

                if (sidebar.classList.contains('is-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }

            });

        }


        if (closeButton) {
            closeButton.addEventListener('click', closeSidebar);
        }


        overlay.addEventListener('click', closeSidebar);


        sidebar
            .querySelectorAll('a')
            .forEach(link => {

                link.addEventListener('click', () => {

                    if (window.innerWidth <= 1100) {
                        closeSidebar();
                    }

                });

            });


        document.addEventListener('keydown', event => {

            if (
                event.key === 'Escape' &&
                sidebar.classList.contains('is-open')
            ) {
                closeSidebar();
            }

        });


        window.addEventListener('resize', () => {

            if (window.innerWidth > 1100) {
                closeSidebar();
            }

        });

    }


    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();

    }

})();
</script>