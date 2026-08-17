<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN DASHBOARD
|--------------------------------------------------------------------------
| File:
|     admin/dashboard.php
|
| Purpose:
|     Admin-only dashboard.
|
| Compatible with:
|     config.php
|     includes/session.php
|     auth/login_process.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
|
| config.php already provides:
|
|     requireAdmin()
|
| It checks:
|
|     user_id
|     role = admin
|
|--------------------------------------------------------------------------
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| GET ADMIN SESSION DATA
|--------------------------------------------------------------------------
|
| login_process.php creates:
|
|     $_SESSION['user_id']
|     $_SESSION['user_name']
|     $_SESSION['user_email']
|     $_SESSION['name']
|     $_SESSION['email']
|     $_SESSION['role']
|     $_SESSION['user_role']
|
|--------------------------------------------------------------------------
*/

$adminId = getUserId();

$adminName = getUserName();

$adminEmail = getUserEmail();

$adminRole = getUserRole();


/*
|--------------------------------------------------------------------------
| EXTRA SAFETY CHECK
|--------------------------------------------------------------------------
|
| Do NOT destroy the session here.
| If something is wrong, simply redirect back to login.
|--------------------------------------------------------------------------
*/

if (
    empty($adminId) ||
    $adminRole !== 'admin'
) {

    redirect(
        BASE_URL . 'index.php?login=1'
    );
}


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$dashboardStats = [
    'users' => 0,
    'vendors' => 0,
    'customers' => 0,
    'products' => 0
];

$dashboardError = null;


/*
|--------------------------------------------------------------------------
| LOAD DATABASE STATISTICS
|--------------------------------------------------------------------------
*/

try {

    $pdo = getDB();


    /*
    |--------------------------------------------------------------------------
    | TOTAL USERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
    ");

    $dashboardStats['users'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL VENDORS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE LOWER(role) = 'vendor'
    ");

    $dashboardStats['vendors'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL CUSTOMERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE LOWER(role) = 'customer'
    ");

    $dashboardStats['customers'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL PRODUCTS
    |--------------------------------------------------------------------------
    |
    | Product table may not exist yet.
    | If it does not exist, keep value as 0.
    |--------------------------------------------------------------------------
    */

    try {

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM products
        ");

        $dashboardStats['products'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        $dashboardStats['products'] = 0;
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'HochipoHub Admin Dashboard Error: '
        . $e->getMessage()
    );


    if (
        defined('APP_DEBUG') &&
        APP_DEBUG
    ) {

        $dashboardError =
            $e->getMessage();

    } else {

        $dashboardError =
            'Unable to load dashboard statistics.';
    }
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle = 'Admin Dashboard';

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($pageTitle) ?>
        -
        <?= e(SITE_NAME) ?>
    </title>


    <style>

        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
        }


        body {
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7fb;

            color: #1f2937;
        }


        /*
        |--------------------------------------------------------------------------
        | LAYOUT
        |--------------------------------------------------------------------------
        */

        .admin-layout {

            min-height: 100vh;

            display: flex;
        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .admin-sidebar {

            width: 250px;

            min-height: 100vh;

            background: #111827;

            color: #ffffff;

            padding: 24px 18px;

            flex-shrink: 0;
        }


        .admin-brand {

            font-size: 22px;

            font-weight: 700;

            margin-bottom: 35px;
        }


        .admin-brand span {

            display: block;

            font-size: 12px;

            font-weight: 400;

            opacity: 0.65;

            margin-top: 5px;
        }


        /*
        |--------------------------------------------------------------------------
        | NAVIGATION
        |--------------------------------------------------------------------------
        */

        .admin-nav {

            display: flex;

            flex-direction: column;

            gap: 8px;
        }


        .admin-nav a {

            display: block;

            padding: 12px 14px;

            border-radius: 8px;

            color: #d1d5db;

            text-decoration: none;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        .admin-nav a:hover {

            background: #1f2937;

            color: #ffffff;
        }


        .admin-nav a.active {

            background: #2563eb;

            color: #ffffff;
        }


        .admin-nav .logout {

            margin-top: 25px;

            color: #fca5a5;
        }


        .admin-nav .logout:hover {

            background: #7f1d1d;

            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .admin-main {

            flex: 1;

            min-width: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | TOP BAR
        |--------------------------------------------------------------------------
        */

        .admin-topbar {

            background: #ffffff;

            border-bottom:
                1px solid #e5e7eb;

            padding: 18px 28px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .admin-topbar h1 {

            margin: 0;

            font-size: 24px;

            color: #111827;
        }


        .admin-topbar p {

            margin: 5px 0 0;

            color: #6b7280;

            font-size: 14px;
        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN USER
        |--------------------------------------------------------------------------
        */

        .admin-user {

            text-align: right;
        }


        .admin-user-name {

            font-weight: 700;

            color: #111827;
        }


        .admin-user-email {

            margin-top: 3px;

            font-size: 13px;

            color: #6b7280;
        }


        .admin-user-role {

            display: inline-block;

            margin-top: 7px;

            padding: 4px 9px;

            border-radius: 999px;

            background: #dbeafe;

            color: #1d4ed8;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .admin-content {

            padding: 28px;
        }


        /*
        |--------------------------------------------------------------------------
        | WELCOME CARD
        |--------------------------------------------------------------------------
        */

        .welcome-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            padding: 24px;

            margin-bottom: 24px;
        }


        .welcome-card h2 {

            margin: 0 0 8px;

            color: #111827;

            font-size: 22px;
        }


        .welcome-card p {

            margin: 0;

            color: #6b7280;

            font-size: 14px;
        }


        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        .dashboard-error {

            background: #fef2f2;

            border:
                1px solid #fecaca;

            color: #991b1b;

            padding: 14px 16px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | STATISTICS GRID
        |--------------------------------------------------------------------------
        */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }


        /*
        |--------------------------------------------------------------------------
        | STAT CARD
        |--------------------------------------------------------------------------
        */

        .stat-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            padding: 22px;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .stat-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, 0.06);
        }


        .stat-label {

            color: #6b7280;

            font-size: 14px;

            margin-bottom: 10px;
        }


        .stat-value {

            color: #111827;

            font-size: 30px;

            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | QUICK LINKS
        |--------------------------------------------------------------------------
        */

        .quick-section {

            margin-top: 28px;
        }


        .quick-section h2 {

            margin: 0 0 15px;

            font-size: 19px;

            color: #111827;
        }


        .quick-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap: 16px;
        }


        .quick-card {

            display: block;

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            padding: 20px;

            text-decoration: none;

            color: #111827;

            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .quick-card:hover {

            transform: translateY(-2px);

            border-color: #93c5fd;

            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, 0.06);
        }


        .quick-card-title {

            font-size: 16px;

            font-weight: 700;

            margin-bottom: 6px;
        }


        .quick-card-description {

            color: #6b7280;

            font-size: 13px;

            line-height: 1.5;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .admin-footer {

            margin-top: 35px;

            padding-top: 20px;

            border-top:
                1px solid #e5e7eb;

            color: #9ca3af;

            font-size: 13px;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }


            .quick-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }


        @media (max-width: 700px) {

            .admin-layout {

                display: block;
            }


            .admin-sidebar {

                width: 100%;

                min-height: auto;
            }


            .admin-nav {

                flex-direction: row;

                flex-wrap: wrap;
            }


            .admin-nav a {

                flex: 1;

                min-width: 120px;

                text-align: center;
            }


            .admin-topbar {

                flex-direction: column;

                align-items: flex-start;
            }


            .admin-user {

                text-align: left;
            }
        }


        @media (max-width: 500px) {

            .admin-content {

                padding: 18px;
            }


            .stats-grid {

                grid-template-columns: 1fr;
            }


            .quick-grid {

                grid-template-columns: 1fr;
            }


            .admin-topbar {

                padding: 18px;
            }
        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!--
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    -->

    <aside class="admin-sidebar">


        <div class="admin-brand">

            <?= e(SITE_NAME) ?>

            <span>
                Administration Panel
            </span>

        </div>


        <nav class="admin-nav">


            <a
                href="<?= e(
                    BASE_URL . 'admin/dashboard.php'
                ) ?>"
                class="active"
            >
                Dashboard
            </a>


            <a
                href="<?= e(
                    BASE_URL . 'admin/users.php'
                ) ?>"
            >
                Users
            </a>


            <a
                href="<?= e(
                    BASE_URL . 'admin/vendors.php'
                ) ?>"
            >
                Vendors
            </a>


            <a
                href="<?= e(
                    BASE_URL . 'admin/products.php'
                ) ?>"
            >
                Products
            </a>


            <a
                href="<?= e(
                    BASE_URL . 'admin/orders.php'
                ) ?>"
            >
                Orders
            </a>


            <a
                href="<?= e(
                    BASE_URL . 'admin/settings.php'
                ) ?>"
            >
                Settings
            </a>


            <a
                href="<?= e(
                    BASE_URL . 'auth/logout.php'
                ) ?>"
                class="logout"
            >
                Logout
            </a>


        </nav>


    </aside>


    <!--
    |--------------------------------------------------------------------------
    | MAIN CONTENT
    |--------------------------------------------------------------------------
    -->

    <main class="admin-main">


        <!--
        |--------------------------------------------------------------------------
        | TOP BAR
        |--------------------------------------------------------------------------
        -->

        <header class="admin-topbar">


            <div>

                <h1>
                    Admin Dashboard
                </h1>

                <p>
                    Manage your HochipoHub marketplace.
                </p>

            </div>


            <div class="admin-user">


                <div class="admin-user-name">

                    <?= e(
                        $adminName !== ''
                            ? $adminName
                            : 'Administrator'
                    ) ?>

                </div>


                <div class="admin-user-email">

                    <?= e(
                        $adminEmail !== ''
                            ? $adminEmail
                            : 'No email available'
                    ) ?>

                </div>


                <div class="admin-user-role">

                    <?= e($adminRole) ?>

                </div>


            </div>


        </header>


        <!--
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        -->

        <section class="admin-content">


            <!--
            |--------------------------------------------------------------------------
            | WELCOME
            |--------------------------------------------------------------------------
            -->

            <div class="welcome-card">

                <h2>

                    Welcome back,
                    <?= e(
                        $adminName !== ''
                            ? $adminName
                            : 'Administrator'
                    ) ?>.

                </h2>


                <p>

                    You are successfully logged in
                    as an administrator.

                </p>

            </div>


            <!--
            |--------------------------------------------------------------------------
            | DATABASE ERROR
            |--------------------------------------------------------------------------
            -->

            <?php if (
                $dashboardError !== null
            ): ?>

                <div class="dashboard-error">

                    <?= e(
                        $dashboardError
                    ) ?>

                </div>

            <?php endif; ?>


            <!--
            |--------------------------------------------------------------------------
            | STATISTICS
            |--------------------------------------------------------------------------
            -->

            <div class="stats-grid">


                <!-- USERS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Total Users
                    </div>

                    <div class="stat-value">

                        <?= e(
                            $dashboardStats['users']
                        ) ?>

                    </div>

                </div>


                <!-- VENDORS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Vendors
                    </div>

                    <div class="stat-value">

                        <?= e(
                            $dashboardStats['vendors']
                        ) ?>

                    </div>

                </div>


                <!-- CUSTOMERS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Customers
                    </div>

                    <div class="stat-value">

                        <?= e(
                            $dashboardStats['customers']
                        ) ?>

                    </div>

                </div>


                <!-- PRODUCTS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Products
                    </div>

                    <div class="stat-value">

                        <?= e(
                            $dashboardStats['products']
                        ) ?>

                    </div>

                </div>


            </div>


            <!--
            |--------------------------------------------------------------------------
            | QUICK ACTIONS
            |--------------------------------------------------------------------------
            -->

            <div class="quick-section">


                <h2>
                    Quick Management
                </h2>


                <div class="quick-grid">


                    <a
                        href="<?= e(
                            BASE_URL . 'admin/users.php'
                        ) ?>"
                        class="quick-card"
                    >

                        <div class="quick-card-title">
                            Manage Users
                        </div>

                        <div class="quick-card-description">
                            View and manage customer,
                            vendor and administrator accounts.
                        </div>

                    </a>


                    <a
                        href="<?= e(
                            BASE_URL . 'admin/vendors.php'
                        ) ?>"
                        class="quick-card"
                    >

                        <div class="quick-card-title">
                            Manage Vendors
                        </div>

                        <div class="quick-card-description">
                            Review and manage marketplace vendors.
                        </div>

                    </a>


                    <a
                        href="<?= e(
                            BASE_URL . 'admin/products.php'
                        ) ?>"
                        class="quick-card"
                    >

                        <div class="quick-card-title">
                            Manage Products
                        </div>

                        <div class="quick-card-description">
                            View and manage products listed
                            on the marketplace.
                        </div>

                    </a>


                    <a
                        href="<?= e(
                            BASE_URL . 'admin/orders.php'
                        ) ?>"
                        class="quick-card"
                    >

                        <div class="quick-card-title">
                            Manage Orders
                        </div>

                        <div class="quick-card-description">
                            Monitor marketplace orders
                            and transactions.
                        </div>

                    </a>


                    <a
                        href="<?= e(
                            BASE_URL . 'admin/settings.php'
                        ) ?>"
                        class="quick-card"
                    >

                        <div class="quick-card-title">
                            Admin Settings
                        </div>

                        <div class="quick-card-description">
                            Configure administrator and
                            marketplace settings.
                        </div>

                    </a>


                    <a
                        href="<?= e(
                            BASE_URL . 'auth/logout.php'
                        ) ?>"
                        class="quick-card"
                    >

                        <div class="quick-card-title">
                            Logout
                        </div>

                        <div class="quick-card-description">
                            Sign out of the administrator account.
                        </div>

                    </a>


                </div>


            </div>


            <!--
            |--------------------------------------------------------------------------
            | FOOTER
            |--------------------------------------------------------------------------
            -->

            <div class="admin-footer">

                HochipoHub Administration Panel

            </div>


        </section>


    </main>


</div>


</body>

</html>