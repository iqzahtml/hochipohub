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
| Authentication:
|     Uses session created by auth/login_process.php
|
| IMPORTANT:
|     Admin access is controlled by the session role.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
|
| Only users with:
|
|     $_SESSION['role'] === 'admin'
|
| can access this page.
|
| requireAdmin() is already defined in config.php.
|--------------------------------------------------------------------------
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| GET CURRENT ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminId =
    getUserId();

$adminName =
    getUserName();

$adminEmail =
    getUserEmail();

$adminRole =
    getUserRole();


/*
|--------------------------------------------------------------------------
| SAFETY CHECK
|--------------------------------------------------------------------------
|
| requireAdmin() should already handle this.
| This extra check makes the dashboard defensive.
|--------------------------------------------------------------------------
*/

if (
    empty($adminId) ||
    $adminRole !== 'admin'
) {

    logoutUser();

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| DASHBOARD DATA
|--------------------------------------------------------------------------
|
| Keep this section simple for now.
| We can add real database statistics later.
|--------------------------------------------------------------------------
*/

$dashboardStats = [
    'users' => 0,
    'vendors' => 0,
    'customers' => 0,
    'products' => 0
];


/*
|--------------------------------------------------------------------------
| DATABASE STATISTICS
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
        WHERE role = 'vendor'
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
        WHERE role = 'customer'
    ");

    $dashboardStats['customers'] =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL PRODUCTS
    |--------------------------------------------------------------------------
    |
    | Products table may not exist yet.
    | Therefore this query is handled separately.
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

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS TABLE NOT READY
        |--------------------------------------------------------------------------
        |
        | Keep dashboard working even if product module
        | has not been created yet.
        |--------------------------------------------------------------------------
        */

        $dashboardStats['products'] = 0;
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

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

$pageTitle =
    'Admin Dashboard';

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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

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

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .admin-nav a {
            color: #d1d5db;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 8px;
            display: block;
            transition: 0.2s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: #1f2937;
            color: #ffffff;
        }

        .admin-nav .logout {
            margin-top: 25px;
            color: #fca5a5;
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
            border-bottom: 1px solid #e5e7eb;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .admin-topbar h1 {
            margin: 0;
            font-size: 24px;
        }

        .admin-user {
            text-align: right;
        }

        .admin-user-name {
            font-weight: 700;
        }

        .admin-user-email {
            font-size: 13px;
            color: #6b7280;
            margin-top: 3px;
        }

        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .admin-content {
            padding: 28px;
        }

        .welcome-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .welcome-card h2 {
            margin: 0 0 8px;
        }

        .welcome-card p {
            margin: 0;
            color: #6b7280;
        }

        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        .dashboard-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
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

        .stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 22px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .stats-grid {
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
            }

            .admin-nav {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .admin-nav a {
                flex: 1;
                min-width: 120px;
            }

            .admin-topbar {
                align-items: flex-start;
                flex-direction: column;
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
                        $adminEmail
                    ) ?>

                </div>

            </div>

        </header>


        <!--
        |--------------------------------------------------------------------------
        | DASHBOARD CONTENT
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
                    You are logged in as an administrator.
                </p>

            </div>


            <!--
            |--------------------------------------------------------------------------
            | DATABASE ERROR
            |--------------------------------------------------------------------------
            -->

            <?php if (
                isset($dashboardError)
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


        </section>

    </main>

</div>

</body>

</html>