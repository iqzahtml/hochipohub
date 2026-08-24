<?php
/**
 * HOCHIPOHUB
 * Shared Admin Sidebar
 *
 * Used by:
 * admin/dashboard.php
 * admin/users.php
 * admin/vendors.php
 * admin/products.php
 * admin/orders.php
 * admin/payments.php
 * admin/commission.php
 * admin/reviews.php
 * admin/settings.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

$adminName = $_SESSION['name'] ?? 'Administrator';
$adminEmail = $_SESSION['email'] ?? 'admin@hochipohub.com';

$initial = strtoupper(
    substr(
        trim($adminName) !== ''
            ? trim($adminName)
            : 'A',
        0,
        1
    )
);

function adminNavActive($page)
{
    global $currentPage;

    return $currentPage === $page
        ? 'active'
        : '';
}
?>

<style>

/* =========================================================
   HOCHIPOHUB ADMIN SIDEBAR
========================================================= */

:root {
    --admin-sidebar-width: 285px;
    --admin-sidebar-bg: #0b1020;
    --admin-sidebar-bg-2: #111936;
    --admin-red: #e5092f;
    --admin-red-dark: #b90726;
    --admin-blue: #2864f0;
    --admin-blue-light: #5c8dff;
    --admin-text: #f8fafc;
    --admin-muted: #94a3b8;
    --admin-border: rgba(255,255,255,.08);
}


/* =========================================================
   SIDEBAR
========================================================= */

.admin-sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: var(--admin-sidebar-width);

    height: 100vh;

    z-index: 9999;

    display: flex;

    flex-direction: column;

    padding: 22px 16px 18px;

    overflow-y: auto;

    background:
        radial-gradient(
            circle at 0% 0%,
            rgba(229,9,47,.18),
            transparent 30%
        ),
        radial-gradient(
            circle at 100% 45%,
            rgba(40,100,240,.14),
            transparent 30%
        ),
        linear-gradient(
            180deg,
            #0b1020 0%,
            #101833 55%,
            #0a0f1f 100%
        );

    border-right:
        1px solid
        rgba(255,255,255,.08);

    box-shadow:
        14px 0 45px
        rgba(2,6,23,.18);

    scrollbar-width: thin;
    scrollbar-color:
        rgba(255,255,255,.16)
        transparent;
}


.admin-sidebar::-webkit-scrollbar {
    width: 5px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.admin-sidebar::-webkit-scrollbar-thumb {

    background:
        rgba(255,255,255,.15);

    border-radius: 20px;
}


/* =========================================================
   BRAND
========================================================= */

.admin-brand {

    position: relative;

    padding:
        4px
        8px
        22px;

    margin-bottom: 8px;
}


.admin-brand-main {

    display: flex;

    align-items: center;

    gap: 11px;
}


.admin-brand-logo {

    width: 46px;
    height: 46px;

    flex: 0 0 46px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #ef1740,
            #b9072a
        );

    color: white;

    font-size: 20px;

    font-weight: 900;

    box-shadow:
        0 10px 25px
        rgba(229,9,47,.28);
}


.admin-brand-text {

    min-width: 0;
}


.admin-brand-name {

    color: #ffffff;

    font-size: 21px;

    font-weight: 850;

    line-height: 1.1;

    letter-spacing: -.5px;
}


.admin-brand-name span {

    color: #ff3154;
}


.admin-brand-subtitle {

    display: block;

    margin-top: 4px;

    color: #8d9ab4;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1.6px;

    text-transform: uppercase;
}


/* =========================================================
   ADMIN PROFILE
========================================================= */

.admin-profile-card {

    position: relative;

    margin:
        0 2px
        18px;

    padding: 14px;

    border:
        1px solid
        rgba(255,255,255,.09);

    border-radius: 17px;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.07),
            rgba(255,255,255,.025)
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.04);
}


.admin-profile-top {

    display: flex;

    align-items: center;

    gap: 11px;
}


.admin-avatar {

    position: relative;

    width: 43px;
    height: 43px;

    flex: 0 0 43px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2c68f3,
            #704cff
        );

    color: white;

    font-size: 17px;

    font-weight: 850;

    box-shadow:
        0 8px 20px
        rgba(40,100,240,.25);
}


.admin-avatar-status {

    position: absolute;

    right: -2px;
    bottom: -2px;

    width: 11px;
    height: 11px;

    border-radius: 50%;

    background: #22c55e;

    border:
        2px solid
        #111936;
}


.admin-profile-info {

    min-width: 0;

    flex: 1;
}


.admin-profile-name {

    overflow: hidden;

    color: #ffffff;

    font-size: 13px;

    font-weight: 800;

    white-space: nowrap;

    text-overflow: ellipsis;
}


.admin-profile-email {

    margin-top: 3px;

    overflow: hidden;

    color: #8290aa;

    font-size: 10px;

    white-space: nowrap;

    text-overflow: ellipsis;
}


.admin-profile-role {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    margin-top: 10px;

    padding:
        5px
        8px;

    border-radius: 999px;

    background:
        rgba(229,9,47,.12);

    color: #ff5b76;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .7px;

    text-transform: uppercase;
}


/* =========================================================
   MENU LABEL
========================================================= */

.admin-menu-label {

    margin:
        8px
        10px
        9px;

    color: #596781;

    font-size: 9px;

    font-weight: 850;

    letter-spacing: 1.6px;

    text-transform: uppercase;
}


/* =========================================================
   NAV
========================================================= */

.admin-nav {

    display: flex;

    flex-direction: column;

    gap: 4px;
}


.admin-nav a {

    position: relative;

    display: flex;

    align-items: center;

    gap: 12px;

    min-height: 47px;

    padding:
        0 12px;

    border-radius: 13px;

    color: #aeb9cc;

    text-decoration: none;

    font-size: 13px;

    font-weight: 650;

    transition:
        color .2s ease,
        background .2s ease,
        transform .2s ease,
        box-shadow .2s ease;
}


.admin-nav a:hover {

    color: #ffffff;

    background:
        rgba(255,255,255,.065);

    transform:
        translateX(3px);
}


.admin-nav a.active {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #e5092f 0%,
            #c9092d 48%,
            #a8072b 100%
        );

    box-shadow:
        0 10px 25px
        rgba(229,9,47,.22);

    font-weight: 800;
}


.admin-nav a.active::before {

    content: "";

    position: absolute;

    left: -16px;

    width: 4px;
    height: 25px;

    border-radius:
        0 5px 5px 0;

    background: #ff5572;
}


/* =========================================================
   NAV ICON
========================================================= */

.admin-nav-icon {

    width: 20px;
    height: 20px;

    flex: 0 0 20px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    color: currentColor;

    opacity: .95;
}


.admin-nav-icon svg {

    width: 19px;
    height: 19px;

    stroke:
        currentColor;

    fill: none;

    stroke-width: 1.9;

    stroke-linecap: round;
    stroke-linejoin: round;
}


.admin-nav-text {

    flex: 1;
}


.admin-nav-arrow {

    color: currentColor;

    font-size: 15px;

    opacity: .35;

    transition:
        transform .2s ease,
        opacity .2s ease;
}


.admin-nav a:hover .admin-nav-arrow {

    opacity: .75;

    transform:
        translateX(2px);
}


.admin-nav a.active .admin-nav-arrow {

    opacity: .9;
}


/* =========================================================
   SPECIAL MENU BADGE
========================================================= */

.admin-nav-badge {

    min-width: 20px;

    padding:
        3px
        6px;

    border-radius: 999px;

    background:
        rgba(255,255,255,.13);

    color: #ffffff;

    font-size: 8px;

    font-weight: 850;

    text-align: center;
}


/* =========================================================
   CONTROL CARD
========================================================= */

.admin-control-card {

    margin:
        auto
        2px
        15px;

    padding: 15px;

    border-radius: 17px;

    border:
        1px solid
        rgba(255,255,255,.08);

    background:
        linear-gradient(
            135deg,
            rgba(40,100,240,.16),
            rgba(229,9,47,.09)
        );
}


.admin-control-icon {

    width: 34px;
    height: 34px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 10px;

    border-radius: 11px;

    background:
        rgba(255,255,255,.08);

    color: #ffffff;
}


.admin-control-icon svg {

    width: 18px;
    height: 18px;

    stroke: currentColor;

    fill: none;

    stroke-width: 1.8;

    stroke-linecap: round;
    stroke-linejoin: round;
}


.admin-control-title {

    color: #ffffff;

    font-size: 12px;

    font-weight: 800;
}


.admin-control-text {

    margin-top: 4px;

    color: #7f8ba4;

    font-size: 9px;

    line-height: 1.5;
}


/* =========================================================
   SIDEBAR BOTTOM
========================================================= */

.admin-sidebar-bottom {

    margin-top: 0;
}


.admin-logout {

    display: flex;

    align-items: center;

    gap: 12px;

    min-height: 46px;

    padding:
        0 12px;

    border:
        1px solid
        rgba(255,255,255,.055);

    border-radius: 13px;

    background:
        rgba(255,255,255,.025);

    color: #ff7188;

    text-decoration: none;

    font-size: 13px;

    font-weight: 750;

    transition:
        .2s ease;
}


.admin-logout:hover {

    background:
        rgba(229,9,47,.12);

    border-color:
        rgba(229,9,47,.22);

    color: #ff9aaa;

    transform:
        translateX(3px);
}


.admin-logout-icon {

    width: 20px;
    height: 20px;

    display: inline-flex;

    align-items: center;
    justify-content: center;
}


.admin-logout-icon svg {

    width: 18px;
    height: 18px;

    stroke: currentColor;

    fill: none;

    stroke-width: 1.9;

    stroke-linecap: round;
    stroke-linejoin: round;
}


/* =========================================================
   SIDEBAR FOOTER
========================================================= */

.admin-sidebar-footer {

    padding:
        13px
        5px
        0;

    color: #4d5a72;

    font-size: 9px;

    text-align: center;
}


/* =========================================================
   MOBILE OVERLAY
========================================================= */

.admin-sidebar-overlay {

    position: fixed;

    inset: 0;

    z-index: 9998;

    display: none;

    background:
        rgba(2,6,23,.65);

    backdrop-filter:
        blur(3px);
}


.admin-mobile-toggle {

    display: none;

    width: 42px;
    height: 42px;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        #e2e8f0;

    border-radius: 12px;

    background: #ffffff;

    color: #0f172a;

    cursor: pointer;

    box-shadow:
        0 5px 18px
        rgba(15,23,42,.07);
}


.admin-mobile-toggle svg {

    width: 20px;
    height: 20px;

    stroke: currentColor;

    fill: none;

    stroke-width: 2;

    stroke-linecap: round;
}


/* =========================================================
   MAIN OFFSET
========================================================= */

.admin-wrapper {

    min-height: 100vh;
}


.admin-main {

    margin-left:
        var(--admin-sidebar-width);

    min-height: 100vh;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 950px) {

    .admin-sidebar {

        transform:
            translateX(-105%);

        transition:
            transform .28s ease;

        box-shadow:
            20px 0 55px
            rgba(2,6,23,.35);
    }


    body.admin-sidebar-open .admin-sidebar {

        transform:
            translateX(0);
    }


    body.admin-sidebar-open
    .admin-sidebar-overlay {

        display: block;
    }


    .admin-main {

        margin-left: 0;
    }


    .admin-mobile-toggle {

        display: inline-flex;
    }
}


@media (max-width: 600px) {

    .admin-sidebar {

        width:
            min(
                88vw,
                310px
            );
    }

}

</style>


<!-- MOBILE OVERLAY -->

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>


<!-- SIDEBAR -->

<aside class="admin-sidebar" id="adminSidebar">


    <!-- BRAND -->

    <div class="admin-brand">

        <div class="admin-brand-main">

            <div class="admin-brand-logo">
                H
            </div>

            <div class="admin-brand-text">

                <div class="admin-brand-name">
                    Hochipo<span>Hub</span>
                </div>

                <span class="admin-brand-subtitle">
                    Administration Panel
                </span>

            </div>

        </div>

    </div>


    <!-- ADMIN PROFILE -->

    <div class="admin-profile-card">

        <div class="admin-profile-top">

            <div class="admin-avatar">

                <?= htmlspecialchars(
                    $initial,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

                <span
                    class="admin-avatar-status"
                ></span>

            </div>


            <div class="admin-profile-info">

                <div class="admin-profile-name">

                    <?= htmlspecialchars(
                        $adminName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

                <div class="admin-profile-email">

                    <?= htmlspecialchars(
                        $adminEmail,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            </div>

        </div>


        <div class="admin-profile-role">

            <span>●</span>

            Administrator

        </div>

    </div>


    <!-- MENU -->

    <div class="admin-menu-label">
        Main Menu
    </div>


    <nav class="admin-nav">


        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="<?= adminNavActive('dashboard.php') ?>"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- USERS -->

        <a
            href="users.php"
            class="<?= adminNavActive('users.php') ?>"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- VENDORS -->

        <a
            href="vendors.php"
            class="<?= adminNavActive('vendors.php') ?>"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="products.php"
            class="<?= adminNavActive('products.php') ?>"
        >

            <span class="admin-nav-icon">

                <svg viewBox="0 0 24 24">

                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- ORDERS -->

        <a
            href="orders.php"
            class="<?= adminNavActive('orders.php') ?>"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- PAYMENTS -->

        <a
            href="payments.php"
            class="<?= adminNavActive('payments.php') ?>"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- COMMISSION -->

        <a
            href="commission.php"
            class="<?= adminNavActive('commission.php') ?>"
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

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- REVIEWS -->

        <a
            href="reviews.php"
            class="<?= adminNavActive('reviews.php') ?>"
        >

            <span class="admin-nav-icon">

                <svg viewBox="0 0 24 24">

                    <path
                        d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                    ></path>

                    <path
                        d="M8 10h8"
                    ></path>

                    <path
                        d="M8 14h5"
                    ></path>

                </svg>

            </span>

            <span class="admin-nav-text">
                Reviews
            </span>

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


        <!-- SETTINGS -->

        <a
            href="settings.php"
            class="<?= adminNavActive('settings.php') ?>"
        >

            <span class="admin-nav-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                    ></circle>

                    <path
                        d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 1.7-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.4v-.2a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.7-1.7.06-.06A1.7 1.7 0 0 0 8.4 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.4h.84A1.7 1.7 0 0 0 8.4 10a1.7 1.7 0 0 0-.34-1.88L8 8.06l1.7-1.7.06.06A1.7 1.7 0 0 0 11.64 6H12v-.84h2.4V6h.36a1.7 1.7 0 0 0 1.88.34l.06-.06 1.7 1.7-.06.06A1.7 1.7 0 0 0 18 10a1.7 1.7 0 0 0 1.56 1.03h.84v2.4h-.84A1.7 1.7 0 0 0 19.4 15z"
                    ></path>

                </svg>

            </span>

            <span class="admin-nav-text">
                Settings
            </span>

            <span class="admin-nav-arrow">
                ›
            </span>

        </a>


    </nav>


    <!-- CONTROL CARD -->

    <div class="admin-control-card">

        <div class="admin-control-icon">

            <svg viewBox="0 0 24 24">

                <path
                    d="M12 2l7 4v5c0 5-3.5 9.4-7 11-3.5-1.6-7-6-7-11V6l7-4z"
                ></path>

                <path
                    d="M9 12l2 2 4-4"
                ></path>

            </svg>

        </div>

        <div class="admin-control-title">
            Admin Control
        </div>

        <div class="admin-control-text">
            Manage your marketplace safely from one place.
        </div>

    </div>


    <!-- LOGOUT -->

    <div class="admin-sidebar-bottom">

        <a
            href="../auth/logout.php"
            class="admin-logout"
        >

            <span class="admin-logout-icon">

                <svg viewBox="0 0 24 24">

                    <path
                        d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                    ></path>

                    <polyline
                        points="16 17 21 12 16 7"
                    ></polyline>

                    <line
                        x1="21"
                        y1="12"
                        x2="9"
                        y2="12"
                    ></line>

                </svg>

            </span>

            <span>
                Logout
            </span>

        </a>

    </div>


    <div class="admin-sidebar-footer">
        © <?= date('Y') ?> HochipoHub
    </div>


</aside>


<script>

(function () {

    const body =
        document.body;

    const overlay =
        document.getElementById(
            'adminSidebarOverlay'
        );


    window.openAdminSidebar =
        function () {

            body.classList.add(
                'admin-sidebar-open'
            );

        };


    window.closeAdminSidebar =
        function () {

            body.classList.remove(
                'admin-sidebar-open'
            );

        };


    if (overlay) {

        overlay.addEventListener(
            'click',
            closeAdminSidebar
        );

    }


    document
        .querySelectorAll(
            '.admin-sidebar a'
        )
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth <= 950
                    ) {

                        closeAdminSidebar();

                    }

                }
            );

        });


})();

</script>