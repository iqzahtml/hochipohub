<?php

/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - ADMIN DASHBOARD
|--------------------------------------------------------------------------
| File:
|     admin/dashboard.php
|
| Purpose:
|     Main administration dashboard.
|
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
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| ADMIN SESSION
|--------------------------------------------------------------------------
*/

$adminId =
    function_exists('getUserId')
        ? getUserId()
        : ($_SESSION['user_id'] ?? null);

$adminName =
    function_exists('getUserName')
        ? getUserName()
        : (
            $_SESSION['name']
            ?? $_SESSION['user_name']
            ?? 'Administrator'
        );

$adminEmail =
    function_exists('getUserEmail')
        ? getUserEmail()
        : (
            $_SESSION['email']
            ?? $_SESSION['user_email']
            ?? ''
        );

$adminRole =
    function_exists('getUserRole')
        ? getUserRole()
        : (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? 'admin'
        );


/*
|--------------------------------------------------------------------------
| EXTRA SAFETY
|--------------------------------------------------------------------------
*/

if (
    empty($adminId) ||
    strtolower((string) $adminRole) !== 'admin'
) {

    redirect(
        BASE_URL . 'index.php?login=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| ESCAPE
|--------------------------------------------------------------------------
*/

if (!function_exists('adminEscape')) {

    function adminEscape($value)
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
| DEFAULT DASHBOARD DATA
|--------------------------------------------------------------------------
*/

$dashboardStats = [

    'users'       => 0,
    'vendors'     => 0,
    'products'    => 0,
    'orders'      => 0,
    'revenue'     => 0,
    'customers'   => 0,
    'payments'    => 0,
    'reviews'     => 0,
    'categories'  => 0,
    'inventory'   => 0

];


$pendingVendors =
    0;

$pendingOrders =
    0;

$pendingPayments =
    0;

$hiddenReviews =
    0;

$lowStockProducts =
    0;

$outOfStockProducts =
    0;

$pendingApplications =
    0;

$dashboardError =
    null;

$recentOrders =
    [];

$recentApplications =
    [];


$pdo =
    null;


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $pdo =
        getDB();


    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM users
            ");

        $dashboardStats['users'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Users Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM users
                WHERE LOWER(role) = 'customer'
            ");

        $dashboardStats['customers'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Customers Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VENDORS
    |--------------------------------------------------------------------------
    |
    | Prefer the vendors table because the project has a dedicated
    | vendors table.
    |
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM vendors
            ");

        $dashboardStats['vendors'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------
        | Fallback to users.role = vendor
        |--------------------------------------------------------------
        */

        try {

            $stmt =
                $pdo->query("
                    SELECT COUNT(*)
                    FROM users
                    WHERE LOWER(role) = 'vendor'
                ");

            $dashboardStats['vendors'] =
                (int) $stmt->fetchColumn();

        } catch (Throwable $fallbackError) {

            error_log(
                'Dashboard Vendors Error: '
                . $fallbackError->getMessage()
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCTS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM products
            ");

        $dashboardStats['products'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Products Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORIES
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM categories
            ");

        $dashboardStats['categories'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Categories Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ORDERS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM orders
            ");

        $dashboardStats['orders'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Orders Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | REVENUE
    |--------------------------------------------------------------------------
    |
    | Only completed orders are counted.
    |
    */

    try {

        $stmt =
            $pdo->query("
                SELECT
                    COALESCE(
                        SUM(total_amount),
                        0
                    )
                FROM orders
                WHERE order_status = 'Completed'
            ");

        $dashboardStats['revenue'] =
            (float) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Revenue Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM payments
            ");

        $dashboardStats['payments'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Payments Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | REVIEWS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM reviews
            ");

        $dashboardStats['reviews'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Reviews Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | INVENTORY
    |--------------------------------------------------------------------------
    |
    | Number of inventory records.
    |
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM inventory
            ");

        $dashboardStats['inventory'] =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Inventory Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LOW STOCK
    |--------------------------------------------------------------------------
    |
    | Products with quantity between 1 and 5.
    |
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM inventory
                WHERE quantity > 0
                  AND quantity <= 5
            ");

        $lowStockProducts =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Low Stock Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | OUT OF STOCK
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM inventory
                WHERE quantity <= 0
            ");

        $outOfStockProducts =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Out Of Stock Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PENDING VENDOR APPLICATIONS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM vendor_applications
                WHERE status = 'Pending'
            ");

        $pendingVendors =
            (int) $stmt->fetchColumn();

        $pendingApplications =
            $pendingVendors;

    } catch (Throwable $e) {

        error_log(
            'Dashboard Vendor Applications Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PENDING ORDERS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM orders
                WHERE order_status = 'Pending'
            ");

        $pendingOrders =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Pending Orders Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PENDING PAYMENTS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM payments
                WHERE payment_status = 'Pending'
            ");

        $pendingPayments =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Pending Payments Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | HIDDEN REVIEWS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT COUNT(*)
                FROM reviews
                WHERE status = 'Hidden'
            ");

        $hiddenReviews =
            (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Dashboard Hidden Reviews Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RECENT ORDERS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT
                    o.order_id,
                    o.order_date,
                    o.total_amount,
                    o.order_status,
                    u.name AS customer_name
                FROM orders o
                INNER JOIN users u
                    ON o.customer_id = u.user_id
                ORDER BY o.order_date DESC
                LIMIT 5
            ");

        $recentOrders =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (Throwable $e) {

        error_log(
            'Dashboard Recent Orders Error: '
            . $e->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RECENT VENDOR APPLICATIONS
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->query("
                SELECT
                    va.application_id,
                    va.business_name,
                    va.status,
                    va.created_at,
                    u.name AS applicant_name
                FROM vendor_applications va
                INNER JOIN users u
                    ON va.user_id = u.user_id
                ORDER BY va.created_at DESC
                LIMIT 5
            ");

        $recentApplications =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (Throwable $e) {

        error_log(
            'Dashboard Recent Applications Error: '
            . $e->getMessage()
        );

    }


} catch (Throwable $e) {

    error_log(
        'HochipoHub Admin Dashboard Error: '
        . $e->getMessage()
    );


    $dashboardError =
        'Unable to connect to the marketplace database.';

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

        <?= adminEscape($pageTitle) ?>

        -

        <?= adminEscape(
            defined('SITE_NAME')
                ? SITE_NAME
                : 'HochipoHub'
        ) ?>

    </title>


    <link
        rel="stylesheet"
        href="<?= adminEscape(
            BASE_URL
        ) ?>css/admin.css"
    >


    <link
        rel="stylesheet"
        href="<?= adminEscape(
            BASE_URL
        ) ?>css/responsive.css"
    >


    <!-- =====================================================
         DASHBOARD FONT
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <style>


        /*
        |--------------------------------------------------------------------------
        | DASHBOARD DESIGN SYSTEM
        |--------------------------------------------------------------------------
        */

        :root {

            --dash-blue:
                #2563eb;

            --dash-blue-dark:
                #1d4ed8;

            --dash-blue-deep:
                #172554;

            --dash-blue-soft:
                #eff6ff;

            --dash-sky:
                #0ea5e9;

            --dash-cyan:
                #06b6d4;

            --dash-indigo:
                #4f46e5;

            --dash-violet:
                #6366f1;

            --dash-green:
                #10b981;

            --dash-bg:
                #f5f8ff;

            --dash-card:
                #ffffff;

            --dash-text:
                #172033;

            --dash-muted:
                #718096;

            --dash-border:
                #e5ebf4;

        }


        /*
        |--------------------------------------------------------------------------
        | BASE
        |--------------------------------------------------------------------------
        */

        .admin-dashboard-main {

            min-height:
                100vh;

            background:

                radial-gradient(
                    circle at 88% 8%,
                    rgba(
                        37,
                        99,
                        235,
                        .07
                    ),
                    transparent 26%
                ),

                linear-gradient(
                    180deg,
                    #fafdff 0,
                    var(--dash-bg) 520px
                );

        }


        .dashboard-page {

            width:
                100%;

            min-height:
                100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .dashboard-header {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            min-height:
                86px;

            padding:
                18px 42px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .90
                );

            border-bottom:
                1px solid var(--dash-border);

            backdrop-filter:
                blur(18px);

        }


        .dashboard-header-left {

            display:
                flex;

            align-items:
                center;

            gap:
                14px;

        }


        .dashboard-title {

            margin:
                0;

            color:
                var(--dash-text);

            font-size:
                24px;

            font-weight:
                850;

            letter-spacing:
                -.6px;

        }


        .dashboard-subtitle {

            margin:
                4px 0 0;

            color:
                var(--dash-muted);

            font-size:
                12px;

        }


        .dashboard-header-right {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

        }


        .dashboard-header-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            padding:
                8px 12px;

            border:
                1px solid var(--dash-border);

            border-radius:
                12px;

            background:
                #fff;

            color:
                var(--dash-text);

            font-size:
                12px;

            font-weight:
                750;

        }


        .dashboard-header-user-icon {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                30px;

            height:
                30px;

            border-radius:
                9px;

            background:
                linear-gradient(
                    135deg,
                    var(--dash-blue),
                    var(--dash-indigo)
                );

            color:
                #fff;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .dashboard-content {

            width:
                calc(100% - 72px);

            max-width:
                1420px;

            margin:
                0 auto;

            padding:
                28px 0 42px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .dashboard-hero {

            position:
                relative;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                25px;

            overflow:
                hidden;

            padding:
                29px 32px;

            border:
                1px solid rgba(
                    255,
                    255,
                    255,
                    .16
                );

            border-radius:
                22px;

            background:

                radial-gradient(
                    circle at 92% 15%,
                    rgba(
                        125,
                        211,
                        252,
                        .24
                    ),
                    transparent 25%
                ),

                radial-gradient(
                    circle at 70% 120%,
                    rgba(
                        129,
                        140,
                        248,
                        .22
                    ),
                    transparent 35%
                ),

                linear-gradient(
                    135deg,
                    #123b92 0%,
                    #2563eb 45%,
                    #4f46e5 100%
                );

            box-shadow:
                0 18px 42px rgba(
                    37,
                    99,
                    235,
                    .18
                );

        }


        .dashboard-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                240px;

            height:
                240px;

            right:
                -80px;

            top:
                -130px;

            border-radius:
                50%;

            border:
                1px solid rgba(
                    255,
                    255,
                    255,
                    .13
                );

            box-shadow:
                0 0 0 30px rgba(
                    255,
                    255,
                    255,
                    .025
                );

        }


        .dashboard-hero-content {

            position:
                relative;

            z-index:
                1;

            max-width:
                750px;

        }


        .dashboard-hero-kicker {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                7px;

            padding:
                6px 10px;

            border:
                1px solid rgba(
                    255,
                    255,
                    255,
                    .18
                );

            border-radius:
                999px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .10
                );

            color:
                #dbeafe;

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                .7px;

            text-transform:
                uppercase;

        }


        .dashboard-hero-kicker-dot {

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;

            background:
                #67e8f9;

            box-shadow:
                0 0 0 4px rgba(
                    103,
                    232,
                    249,
                    .12
                );

        }


        .dashboard-hero h2 {

            margin:
                12px 0 0;

            color:
                #fff;

            font-size:
                29px;

            line-height:
                1.15;

            font-weight:
                850;

            letter-spacing:
                -.8px;

        }


        .dashboard-hero p {

            max-width:
                650px;

            margin:
                8px 0 0;

            color:
                #dbeafe;

            font-size:
                12px;

            line-height:
                1.6;

        }


        .dashboard-hero-actions {

            position:
                relative;

            z-index:
                2;

            display:
                flex;

            gap:
                9px;

            flex-shrink:
                0;

        }


        .hero-button {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            min-height:
                39px;

            padding:
                0 15px;

            border-radius:
                10px;

            text-decoration:
                none;

            font-size:
                10px;

            font-weight:
                800;

            transition:
                .18s ease;

        }


        .hero-button:hover {

            transform:
                translateY(-2px);

        }


        .hero-button.primary {

            background:
                #fff;

            color:
                var(--dash-blue-dark);

            box-shadow:
                0 7px 20px rgba(
                    15,
                    23,
                    42,
                    .15
                );

        }


        .hero-button.ghost {

            border:
                1px solid rgba(
                    255,
                    255,
                    255,
                    .25
                );

            background:
                rgba(
                    255,
                    255,
                    255,
                    .09
                );

            color:
                #fff;

        }


        /*
        |--------------------------------------------------------------------------
        | SECTION TITLE
        |--------------------------------------------------------------------------
        */

        .dashboard-section-title {

            display:
                flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            margin:
                28px 0 13px;

        }


        .dashboard-section-title h2 {

            margin:
                0;

            color:
                var(--dash-text);

            font-size:
                16px;

            font-weight:
                850;

            letter-spacing:
                -.25px;

        }


        .dashboard-section-title p {

            margin:
                3px 0 0;

            color:
                var(--dash-muted);

            font-size:
                10px;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .dashboard-stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                13px;

        }


        .dashboard-stat-card {

            position:
                relative;

            min-height:
                142px;

            padding:
                18px;

            overflow:
                hidden;

            border:
                1px solid var(--dash-border);

            border-radius:
                17px;

            background:
                #fff;

            box-shadow:
                0 8px 26px rgba(
                    15,
                    23,
                    42,
                    .045
                );

            transition:
                .2s ease;

        }


        .dashboard-stat-card::after {

            content:
                "";

            position:
                absolute;

            width:
                105px;

            height:
                105px;

            right:
                -44px;

            bottom:
                -51px;

            border-radius:
                50%;

            background:
                rgba(
                    37,
                    99,
                    235,
                    .07
                );

        }


        .dashboard-stat-card:hover,
        .dashboard-stat-active {

            transform:
                translateY(-3px);

            border-color:
                #d2def6;

            box-shadow:
                0 16px 34px rgba(
                    15,
                    23,
                    42,
                    .08
                );

        }


        .dashboard-stat-top {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

        }


        .dashboard-stat-icon {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                36px;

            height:
                36px;

            border-radius:
                11px;

        }


        .dashboard-stat-icon svg {

            width:
                18px;

            height:
                18px;

            fill:
                none;

            stroke:
                currentColor;

            stroke-width:
                1.8;

            stroke-linecap:
                round;

            stroke-linejoin:
                round;

        }


        .stat-blue {

            background:
                #eaf2ff;

            color:
                #2563eb;

        }


        .stat-sky {

            background:
                #e8f8ff;

            color:
                #0284c7;

        }


        .stat-cyan {

            background:
                #e7fbff;

            color:
                #0891b2;

        }


        .stat-indigo {

            background:
                #eef2ff;

            color:
                #4f46e5;

        }


        .stat-purple {

            background:
                #f0edff;

            color:
                #6366f1;

        }


        .stat-green {

            background:
                #eafaf3;

            color:
                #059669;

        }


        .stat-orange {

            background:
                #fff7df;

            color:
                #d97706;

        }


        .stat-teal {

            background:
                #e8fffb;

            color:
                #0f766e;

        }


        .dashboard-stat-label {

            margin-top:
                13px;

            color:
                var(--dash-muted);

            font-size:
                10px;

            font-weight:
                650;

        }


        .dashboard-stat-value {

            margin-top:
                4px;

            color:
                var(--dash-text);

            font-size:
                25px;

            line-height:
                1.1;

            font-weight:
                900;

            letter-spacing:
                -.7px;

        }


        .dashboard-stat-meta {

            margin-top:
                6px;

            color:
                #9aa7b8;

            font-size:
                9px;

        }


        /*
        |--------------------------------------------------------------------------
        | ATTENTION
        |--------------------------------------------------------------------------
        */

        .attention-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                11px;

        }


        .attention-card {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            min-height:
                68px;

            padding:
                12px;

            border:
                1px solid var(--dash-border);

            border-radius:
                13px;

            background:
                #fff;

            text-decoration:
                none;

            transition:
                .18s ease;

        }


        .attention-card:hover {

            transform:
                translateY(-2px);

            border-color:
                #c9d9f5;

            box-shadow:
                0 10px 24px rgba(
                    15,
                    23,
                    42,
                    .06
                );

        }


        .attention-icon {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                34px;

            height:
                34px;

            flex:
                0 0 34px;

            border-radius:
                10px;

        }


        .attention-icon svg {

            width:
                16px;

            height:
                16px;

            fill:
                none;

            stroke:
                currentColor;

            stroke-width:
                1.8;

            stroke-linecap:
                round;

            stroke-linejoin:
                round;

        }


        .attention-order {

            background:
                #eaf2ff;

            color:
                #2563eb;

        }


        .attention-payment {

            background:
                #e8f8ff;

            color:
                #0284c7;

        }


        .attention-review {

            background:
                #eef2ff;

            color:
                #4f46e5;

        }


        .attention-vendor {

            background:
                #e8fffb;

            color:
                #0f766e;

        }


        .attention-info {

            display:
                flex;

            flex-direction:
                column;

            min-width:
                0;

        }


        .attention-info strong {

            color:
                var(--dash-text);

            font-size:
                10px;

            font-weight:
                800;

        }


        .attention-info span {

            margin-top:
                2px;

            color:
                #9aa7b8;

            font-size:
                8px;

        }


        /*
        |--------------------------------------------------------------------------
        | QUICK MANAGEMENT
        |--------------------------------------------------------------------------
        */

        .quick-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                12px;

        }


        .quick-card {

            position:
                relative;

            display:
                block;

            min-height:
                118px;

            padding:
                16px;

            overflow:
                hidden;

            border:
                1px solid var(--dash-border);

            border-radius:
                15px;

            background:
                #fff;

            text-decoration:
                none;

            box-shadow:
                0 6px 20px rgba(
                    15,
                    23,
                    42,
                    .035
                );

            transition:
                .18s ease;

        }


        .quick-card::after {

            content:
                "→";

            position:
                absolute;

            right:
                13px;

            top:
                13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                23px;

            height:
                23px;

            border-radius:
                7px;

            background:
                #f4f7fb;

            color:
                #9aa7b8;

            font-size:
                11px;

            transition:
                .18s ease;

        }


        .quick-card:hover {

            transform:
                translateY(-3px);

            border-color:
                #cbd9f4;

            box-shadow:
                0 15px 32px rgba(
                    15,
                    23,
                    42,
                    .075
                );

        }


        .quick-card:hover::after {

            background:
                var(--dash-blue);

            color:
                #fff;

        }


        .quick-icon {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                34px;

            height:
                34px;

            margin-bottom:
                12px;

            border-radius:
                10px;

        }


        .quick-icon svg {

            width:
                16px;

            height:
                16px;

            fill:
                none;

            stroke:
                currentColor;

            stroke-width:
                1.8;

            stroke-linecap:
                round;

            stroke-linejoin:
                round;

        }


        .quick-blue {

            background:
                #eaf2ff;

            color:
                #2563eb;

        }


        .quick-sky {

            background:
                #e8f8ff;

            color:
                #0284c7;

        }


        .quick-cyan {

            background:
                #e7fbff;

            color:
                #0891b2;

        }


        .quick-indigo {

            background:
                #eef2ff;

            color:
                #4f46e5;

        }


        .quick-green {

            background:
                #eafaf3;

            color:
                #059669;

        }


        .quick-violet {

            background:
                #f0edff;

            color:
                #6366f1;

        }


        .quick-teal {

            background:
                #e8fffb;

            color:
                #0f766e;

        }


        .quick-card-title {

            color:
                var(--dash-text);

            font-size:
                11px;

            font-weight:
                800;

        }


        .quick-card-description {

            max-width:
                220px;

            margin-top:
                5px;

            color:
                #8a98aa;

            font-size:
                8px;

            line-height:
                1.45;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTTOM GRID
        |--------------------------------------------------------------------------
        */

        .dashboard-bottom-grid {

            display:
                grid;

            grid-template-columns:
                minmax(
                    0,
                    1.55fr
                )
                minmax(
                    320px,
                    .85fr
                );

            gap:
                13px;

            margin-top:
                22px;

        }


        .dashboard-panel {

            overflow:
                hidden;

            border:
                1px solid var(--dash-border);

            border-radius:
                16px;

            background:
                #fff;

            box-shadow:
                0 7px 23px rgba(
                    15,
                    23,
                    42,
                    .04
                );

        }


        .dashboard-panel-header {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                12px;

            padding:
                17px 18px;

            border-bottom:
                1px solid #edf1f6;

        }


        .dashboard-panel-header h3 {

            margin:
                0;

            color:
                var(--dash-text);

            font-size:
                12px;

            font-weight:
                850;

        }


        .dashboard-panel-header p {

            margin:
                3px 0 0;

            color:
                #9aa7b8;

            font-size:
                8px;

        }


        .dashboard-view-all {

            color:
                var(--dash-blue);

            text-decoration:
                none;

            font-size:
                8px;

            font-weight:
                800;

        }


        .dashboard-view-all:hover {

            text-decoration:
                underline;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .dashboard-table-wrap {

            width:
                100%;

            overflow-x:
                auto;

        }


        .dashboard-table {

            width:
                100%;

            min-width:
                530px;

            border-collapse:
                collapse;

        }


        .dashboard-table th {

            padding:
                10px 14px;

            background:
                #f8faff;

            border-bottom:
                1px solid #edf1f6;

            color:
                #8a98aa;

            font-size:
                7px;

            font-weight:
                850;

            letter-spacing:
                .6px;

            text-align:
                left;

            text-transform:
                uppercase;

        }


        .dashboard-table td {

            padding:
                12px 14px;

            border-bottom:
                1px solid #f0f3f7;

            color:
                var(--dash-text);

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .dashboard-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        .dashboard-table tbody tr {

            transition:
                .15s ease;

        }


        .dashboard-table tbody tr:hover {

            background:
                #fbfdff;

        }


        .dashboard-order-id {

            color:
                var(--dash-blue);

            font-weight:
                800;

        }


        .dashboard-customer {

            display:
                block;

            max-width:
                150px;

            overflow:
                hidden;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

            font-weight:
                700;

        }


        .dashboard-order-date {

            display:
                block;

            margin-top:
                2px;

            color:
                #a0acbc;

            font-size:
                7px;

        }


        .dashboard-status {

            display:
                inline-flex;

            align-items:
                center;

            min-height:
                22px;

            padding:
                0 8px;

            border-radius:
                999px;

            background:
                #edf4ff;

            color:
                #2563eb;

            font-size:
                7px;

            font-weight:
                800;

        }


        .dashboard-status-ready {

            box-shadow:
                inset 0 0 0 1px rgba(
                    37,
                    99,
                    235,
                    .08
                );

        }


        .dashboard-status.status-completed {

            background:
                #eafaf3;

            color:
                #059669;

        }


        .dashboard-status.status-processing {

            background:
                #e8f8ff;

            color:
                #0284c7;

        }


        .dashboard-status.status-cancelled {

            background:
                #f1f5f9;

            color:
                #64748b;

        }


        /*
        |--------------------------------------------------------------------------
        | APPLICATION LIST
        |--------------------------------------------------------------------------
        */

        .application-list {

            padding:
                3px 0;

        }


        .application-item {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            padding:
                12px 17px;

            border-bottom:
                1px solid #f0f3f7;

        }


        .application-item:last-child {

            border-bottom:
                0;

        }


        .application-avatar {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                34px;

            height:
                34px;

            flex:
                0 0 34px;

            border-radius:
                10px;

            background:
                linear-gradient(
                    135deg,
                    #eaf2ff,
                    #eef2ff
                );

            color:
                #3657c8;

            font-size:
                11px;

            font-weight:
                850;

        }


        .application-info {

            min-width:
                0;

            flex:
                1;

        }


        .application-info strong {

            display:
                block;

            overflow:
                hidden;

            color:
                var(--dash-text);

            font-size:
                9px;

            font-weight:
                800;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        .application-info span {

            display:
                block;

            overflow:
                hidden;

            margin-top:
                3px;

            color:
                #9aa7b8;

            font-size:
                7px;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        .application-status {

            padding:
                5px 7px;

            border-radius:
                999px;

            background:
                #eaf2ff;

            color:
                #2563eb;

            font-size:
                7px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .dashboard-empty {

            padding:
                30px 15px;

            color:
                #9aa7b8;

            font-size:
                9px;

            text-align:
                center;

        }


        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        .dashboard-error {

            margin-top:
                16px;

            padding:
                12px 15px;

            border:
                1px solid #dbeafe;

            border-radius:
                11px;

            background:
                #eff6ff;

            color:
                #1d4ed8;

            font-size:
                10px;

            font-weight:
                650;

        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .dashboard-footer {

            padding:
                20px 0 4px;

            color:
                #9aa7b8;

            font-size:
                8px;

            text-align:
                center;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1250px) {

            .dashboard-stats-grid {

                grid-template-columns:
                    repeat(
                        4,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }


            .quick-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }


            .attention-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }

        }


        @media (max-width: 1000px) {

            .dashboard-content {

                width:
                    calc(100% - 40px);

            }


            .dashboard-header {

                padding:
                    16px 25px;

            }


            .dashboard-bottom-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 760px) {

            .dashboard-header {

                min-height:
                    72px;

                padding:
                    13px 17px;

            }


            .dashboard-title {

                font-size:
                    21px;

            }


            .dashboard-header-user {

                padding:
                    5px;

            }


            .dashboard-user-info {

                display:
                    none;

            }


            .dashboard-content {

                width:
                    calc(100% - 24px);

                padding-top:
                    18px;

            }


            .dashboard-hero {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                padding:
                    24px 22px;

                border-radius:
                    20px;

            }


            .dashboard-hero-actions {

                width:
                    100%;

            }


            .hero-button {

                flex:
                    1;

            }


            .dashboard-stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }


            .quick-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }

        }


        @media (max-width: 500px) {

            .dashboard-stats-grid {

                grid-template-columns:
                    1fr;

            }


            .attention-grid {

                grid-template-columns:
                    1fr;

            }


            .quick-grid {

                grid-template-columns:
                    1fr;

            }


            .dashboard-section-title {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .dashboard-hero h2 {

                font-size:
                    26px;

            }


            .dashboard-hero p {

                font-size:
                    10px;

            }


            .dashboard-header {

                padding:
                    12px;

            }

        }

    </style>


</head>


<body>


<div
    class="admin-wrapper dashboard-page"
>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php

    require_once
        dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    ?>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main
        class="admin-main admin-dashboard-main"
    >


        <!-- =================================================
             HEADER
        ================================================== -->

        <header
            class="dashboard-header"
        >


            <div
                class="dashboard-header-left"
            >


                <div>

                    <h1
                        class="dashboard-title"
                    >
                        Admin Dashboard
                    </h1>


                    <p
                        class="dashboard-subtitle"
                    >
                        Monitor your HochipoHub marketplace from one place.
                    </p>

                </div>


            </div>


            <div
                class="dashboard-header-right"
            >


                <div
                    class="dashboard-header-user"
                >


                    <span
                        class="dashboard-header-user-icon"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="15"
                            height="15"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path
                                d="M20 21a8 8 0 0 0-16 0"
                            ></path>

                            <circle
                                cx="12"
                                cy="7"
                                r="4"
                            ></circle>

                        </svg>

                    </span>


                    <span>

                        <?= adminEscape(
                            $adminName !== ''
                                ? $adminName
                                : 'Administrator'
                        ) ?>

                    </span>


                </div>


            </div>


        </header>


        <!-- =================================================
             DASHBOARD CONTENT
        ================================================== -->

        <section
            class="dashboard-content"
        >


            <!-- =================================================
                 HERO
            ================================================== -->

            <div
                class="dashboard-hero"
            >


                <div
                    class="dashboard-hero-content"
                >


                    <div
                        class="dashboard-hero-kicker"
                    >

                        <span
                            class="dashboard-hero-kicker-dot"
                        ></span>

                        Marketplace Control Center

                    </div>


                    <h2>

                        Welcome back,
                        <?= adminEscape(
                            $adminName !== ''
                                ? $adminName
                                : 'Admin'
                        ) ?>!

                    </h2>


                    <p>

                        Everything you need to monitor users,
                        vendors, products, inventory, orders,
                        payments and marketplace activity
                        is right here.

                    </p>


                </div>


                <div
                    class="dashboard-hero-actions"
                >


                    <a
                        href="orders.php"
                        class="hero-button primary dashboard-action"
                    >
                        View Orders
                    </a>


                    <a
                        href="inventory.php"
                        class="hero-button ghost dashboard-action"
                    >
                        Inventory
                    </a>


                </div>


            </div>


            <?php if (
                $dashboardError !== null
            ): ?>

                <div
                    class="dashboard-error"
                >

                    <?= adminEscape(
                        $dashboardError
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 MARKETPLACE OVERVIEW
            ================================================== -->

            <div
                class="dashboard-section-title"
            >

                <div>

                    <h2>
                        Marketplace Overview
                    </h2>

                    <p>
                        Your marketplace at a glance.
                    </p>

                </div>

            </div>


            <div
                class="dashboard-stats-grid"
            >


                <!-- USERS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-blue"
                        >

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

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Total Users
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['users']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        Customers & vendors
                    </div>

                </div>


                <!-- VENDORS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-sky"
                        >

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

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Vendors
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['vendors']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        Marketplace sellers
                    </div>

                </div>


                <!-- PRODUCTS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-indigo"
                        >

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 2 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
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

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Products
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['products']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        Listed marketplace items
                    </div>

                </div>


                <!-- ORDERS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-cyan"
                        >

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

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Orders
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['orders']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        <?= number_format(
                            $pendingOrders
                        ) ?>
                        pending order(s)
                    </div>

                </div>


                <!-- REVENUE -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-blue"
                        >

                            <svg viewBox="0 0 24 24">

                                <line
                                    x1="12"
                                    y1="1"
                                    x2="12"
                                    y2="23"
                                ></line>

                                <path
                                    d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"
                                ></path>

                            </svg>

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Marketplace Revenue
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >

                        RM
                        <?= number_format(
                            $dashboardStats['revenue'],
                            2
                        ) ?>

                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        Completed orders
                    </div>

                </div>


                <!-- CUSTOMERS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-sky"
                        >

                            <svg viewBox="0 0 24 24">

                                <circle
                                    cx="12"
                                    cy="8"
                                    r="4"
                                ></circle>

                                <path
                                    d="M4 21a8 8 0 0 1 16 0"
                                ></path>

                            </svg>

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Customers
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['customers']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        Registered customers
                    </div>

                </div>


                <!-- PAYMENTS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-cyan"
                        >

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

                            </svg>

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Payments
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['payments']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        <?= number_format(
                            $pendingPayments
                        ) ?>
                        pending
                    </div>

                </div>


                <!-- REVIEWS -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-indigo"
                        >

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                                ></path>

                            </svg>

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Reviews
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['reviews']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        <?= number_format(
                            $hiddenReviews
                        ) ?>
                        hidden
                    </div>

                </div>


                <!-- CATEGORIES -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-purple"
                        >

                            <svg viewBox="0 0 24 24">

                                <rect
                                    x="4"
                                    y="4"
                                    width="6"
                                    height="6"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="14"
                                    y="4"
                                    width="6"
                                    height="6"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="4"
                                    y="14"
                                    width="6"
                                    height="6"
                                    rx="1"
                                ></rect>

                                <rect
                                    x="14"
                                    y="14"
                                    width="6"
                                    height="6"
                                    rx="1"
                                ></rect>

                            </svg>

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Categories
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['categories']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        Product categories
                    </div>

                </div>


                <!-- INVENTORY -->

                <div
                    class="dashboard-stat dashboard-stat-card"
                >

                    <div
                        class="dashboard-stat-top"
                    >

                        <div
                            class="dashboard-stat-icon stat-teal"
                        >

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

                            </svg>

                        </div>

                    </div>


                    <div
                        class="dashboard-stat-label"
                    >
                        Inventory
                    </div>


                    <div
                        class="dashboard-stat-value"
                    >
                        <?= number_format(
                            $dashboardStats['inventory']
                        ) ?>
                    </div>


                    <div
                        class="dashboard-stat-meta"
                    >
                        <?= number_format(
                            $lowStockProducts
                        ) ?>
                        low stock
                    </div>

                </div>


            </div>


            <!-- =================================================
                 ATTENTION
            ================================================== -->

            <div
                class="dashboard-section-title"
            >

                <div>

                    <h2>
                        Needs Your Attention
                    </h2>

                    <p>
                        Quick access to items that may require action.
                    </p>

                </div>

            </div>


            <div
                class="attention-grid"
            >


                <!-- PENDING ORDERS -->

                <a
                    href="orders.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-order"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6"
                            ></path>

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

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $pendingOrders
                            ) ?>
                            Pending Orders
                        </strong>

                        <span>
                            Review order status
                        </span>

                    </div>

                </a>


                <!-- PENDING PAYMENTS -->

                <a
                    href="payments.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-payment"
                    >

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

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $pendingPayments
                            ) ?>
                            Pending Payments
                        </strong>

                        <span>
                            Check payment transactions
                        </span>

                    </div>

                </a>


                <!-- HIDDEN REVIEWS -->

                <a
                    href="reviews.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-review"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $hiddenReviews
                            ) ?>
                            Hidden Reviews
                        </strong>

                        <span>
                            Manage customer feedback
                        </span>

                    </div>

                </a>


                <!-- VENDOR APPLICATIONS -->

                <a
                    href="vendors.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-vendor"
                    >

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

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $pendingApplications
                            ) ?>
                            Vendor Applications
                        </strong>

                        <span>
                            Pending approval
                        </span>

                    </div>

                </a>


            </div>


            <!-- =================================================
                 INVENTORY ALERT
            ================================================== -->

            <div
                class="dashboard-section-title"
            >

                <div>

                    <h2>
                        Inventory Health
                    </h2>

                    <p>
                        Keep an eye on marketplace stock levels.
                    </p>

                </div>

            </div>


            <div
                class="attention-grid"
            >


                <a
                    href="inventory.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-order"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 8l-9-5-9 5 9 5 9-5z"
                            ></path>

                            <path
                                d="M3 8v8l9 5 9-5V8"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $lowStockProducts
                            ) ?>
                            Low Stock
                        </strong>

                        <span>
                            Products with 1–5 units
                        </span>

                    </div>

                </a>


                <a
                    href="inventory.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-payment"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M12 3v18"
                            ></path>

                            <path
                                d="M5 8h14"
                            ></path>

                            <path
                                d="M5 16h14"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $outOfStockProducts
                            ) ?>
                            Out of Stock
                        </strong>

                        <span>
                            Products needing restock
                        </span>

                    </div>

                </a>


                <a
                    href="categories.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-review"
                    >

                        <svg viewBox="0 0 24 24">

                            <rect
                                x="4"
                                y="4"
                                width="6"
                                height="6"
                            ></rect>

                            <rect
                                x="14"
                                y="4"
                                width="6"
                                height="6"
                            ></rect>

                            <rect
                                x="4"
                                y="14"
                                width="6"
                                height="6"
                            ></rect>

                            <rect
                                x="14"
                                y="14"
                                width="6"
                                height="6"
                            ></rect>

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $dashboardStats['categories']
                            ) ?>
                            Categories
                        </strong>

                        <span>
                            Organised marketplace catalog
                        </span>

                    </div>

                </a>


                <a
                    href="products.php"
                    class="attention-card dashboard-action"
                >

                    <div
                        class="attention-icon attention-vendor"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="attention-info"
                    >

                        <strong>
                            <?= number_format(
                                $dashboardStats['products']
                            ) ?>
                            Products
                        </strong>

                        <span>
                            Total marketplace listings
                        </span>

                    </div>

                </a>


            </div>


            <!-- =================================================
                 QUICK MANAGEMENT
            ================================================== -->

            <div
                class="dashboard-section-title"
            >

                <div>

                    <h2>
                        Quick Management
                    </h2>

                    <p>
                        Jump straight into your admin tools.
                    </p>

                </div>

            </div>


            <div
                class="quick-grid"
            >


                <!-- USERS -->

                <a
                    href="users.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-blue"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            ></path>

                            <circle
                                cx="9"
                                cy="7"
                                r="4"
                            ></circle>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Manage Users
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Manage customer and administrator accounts.
                    </div>

                </a>


                <!-- VENDORS -->

                <a
                    href="vendors.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-sky"
                    >

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

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Manage Vendors
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Review marketplace sellers and applications.
                    </div>

                </a>


                <!-- PRODUCTS -->

                <a
                    href="products.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-indigo"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Manage Products
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Control products listed by marketplace vendors.
                    </div>

                </a>


                <!-- CATEGORIES -->

                <a
                    href="categories.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-violet"
                    >

                        <svg viewBox="0 0 24 24">

                            <rect
                                x="4"
                                y="4"
                                width="6"
                                height="6"
                            ></rect>

                            <rect
                                x="14"
                                y="4"
                                width="6"
                                height="6"
                            ></rect>

                            <rect
                                x="4"
                                y="14"
                                width="6"
                                height="6"
                            ></rect>

                            <rect
                                x="14"
                                y="14"
                                width="6"
                                height="6"
                            ></rect>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Categories
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Organise and manage product categories.
                    </div>

                </a>


                <!-- INVENTORY -->

                <a
                    href="inventory.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-teal"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 8l-9-5-9 5 9 5 9-5z"
                            ></path>

                            <path
                                d="M3 8v8l9 5 9-5V8"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Inventory
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Monitor stock levels and restocking needs.
                    </div>

                </a>


                <!-- ORDERS -->

                <a
                    href="orders.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-cyan"
                    >

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

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Manage Orders
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Monitor customer orders and fulfilment.
                    </div>

                </a>


                <!-- PAYMENTS -->

                <a
                    href="payments.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-blue"
                    >

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

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Payments
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Monitor payment transactions and status.
                    </div>

                </a>


                <!-- COMMISSION -->

                <a
                    href="commission.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-sky"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M12 1v22"
                            ></path>

                            <path
                                d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Commission
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Track marketplace commission and earnings.
                    </div>

                </a>


                <!-- REVIEWS -->

                <a
                    href="reviews.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-indigo"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Reviews
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Moderate customer feedback and reviews.
                    </div>

                </a>


                <!-- SETTINGS -->

                <a
                    href="settings.php"
                    class="quick-card dashboard-action"
                >

                    <div
                        class="quick-icon quick-violet"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"
                            ></path>

                            <path
                                d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 1.7-.06-.06A1.7 1.7 0 0 0 16.16 18a1.7 1.7 0 0 0-1.03 1.56V20h-2.4v-.44A1.7 1.7 0 0 0 11.7 18a1.7 1.7 0 0 0-1.88.34l-.06.06-1.7-1.7.06-.06A1.7 1.7 0 0 0 8.46 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.4h.9A1.7 1.7 0 0 0 8.46 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06 1.7-1.7.06.06A1.7 1.7 0 0 0 11.7 6a1.7 1.7 0 0 0 1.03-1.56V4h2.4v.44A1.7 1.7 0 0 0 16.16 6a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.7 1.7-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.56 1.03H22v2.4h-.9A1.7 1.7 0 0 0 19.4 15z"
                            ></path>

                        </svg>

                    </div>


                    <div
                        class="quick-card-title"
                    >
                        Admin Settings
                    </div>


                    <div
                        class="quick-card-description"
                    >
                        Manage administrator account and security.
                    </div>

                </a>


            </div>


            <!-- =================================================
                 RECENT ACTIVITY
            ================================================== -->

            <div
                class="dashboard-bottom-grid"
            >


                <!-- RECENT ORDERS -->

                <div
                    class="dashboard-panel"
                >


                    <div
                        class="dashboard-panel-header"
                    >

                        <div>

                            <h3>
                                Recent Orders
                            </h3>

                            <p>
                                Latest marketplace orders.
                            </p>

                        </div>


                        <a
                            href="orders.php"
                            class="dashboard-view-all"
                        >
                            View All
                        </a>

                    </div>


                    <div
                        class="dashboard-table-wrap"
                    >


                        <?php if (
                            empty($recentOrders)
                        ): ?>

                            <div
                                class="dashboard-empty"
                            >
                                No recent orders found.
                            </div>

                        <?php else: ?>


                            <table
                                class="dashboard-table"
                            >


                                <thead>

                                <tr>

                                    <th>
                                        Order
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                                </thead>


                                <tbody>


                                <?php foreach (
                                    $recentOrders
                                    as $order
                                ): ?>


                                    <?php

                                    $status =
                                        $order[
                                            'order_status'
                                        ]
                                        ?? 'Unknown';

                                    $statusClass =
                                        '';


                                    if (
                                        strtolower(
                                            $status
                                        ) === 'completed'
                                    ) {

                                        $statusClass =
                                            'status-completed';

                                    } elseif (
                                        strtolower(
                                            $status
                                        ) === 'processing'
                                    ) {

                                        $statusClass =
                                            'status-processing';

                                    } elseif (
                                        strtolower(
                                            $status
                                        ) === 'cancelled'
                                    ) {

                                        $statusClass =
                                            'status-cancelled';

                                    }

                                    ?>


                                    <tr>


                                        <td>

                                            <a
                                                href="orders.php"
                                                class="dashboard-order-id dashboard-view-link"
                                            >

                                                #<?= adminEscape(
                                                    $order[
                                                        'order_id'
                                                    ]
                                                ) ?>

                                            </a>

                                        </td>


                                        <td>

                                            <span
                                                class="dashboard-customer"
                                            >

                                                <?= adminEscape(
                                                    $order[
                                                        'customer_name'
                                                    ]
                                                    ?? 'Customer'
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span>

                                                <?= adminEscape(
                                                    $order[
                                                        'order_date'
                                                    ]
                                                    ?? '-'
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <strong>

                                                RM
                                                <?= number_format(
                                                    (float) (
                                                        $order[
                                                            'total_amount'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    2
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <span
                                                class="dashboard-status <?= adminEscape($statusClass) ?>"
                                            >

                                                <?= adminEscape(
                                                    $status
                                                ) ?>

                                            </span>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                                </tbody>


                            </table>


                        <?php endif; ?>


                    </div>


                </div>


                <!-- VENDOR APPLICATIONS -->

                <div
                    class="dashboard-panel"
                >


                    <div
                        class="dashboard-panel-header"
                    >

                        <div>

                            <h3>
                                Vendor Applications
                            </h3>

                            <p>
                                Latest applications.
                            </p>

                        </div>


                        <a
                            href="vendors.php"
                            class="dashboard-view-all"
                        >
                            View All
                        </a>

                    </div>


                    <div
                        class="application-list"
                    >


                        <?php if (
                            empty(
                                $recentApplications
                            )
                        ): ?>


                            <div
                                class="dashboard-empty"
                            >

                                No vendor applications found.

                            </div>


                        <?php else: ?>


                            <?php foreach (
                                $recentApplications
                                as $application
                            ): ?>


                                <?php

                                $applicationName =
                                    $application[
                                        'applicant_name'
                                    ]
                                    ?? 'Vendor';


                                $applicationInitial =
                                    strtoupper(
                                        substr(
                                            trim(
                                                $applicationName
                                            ),
                                            0,
                                            1
                                        )
                                    );

                                ?>


                                <div
                                    class="application-item"
                                >


                                    <div
                                        class="application-avatar"
                                    >

                                        <?= adminEscape(
                                            $applicationInitial
                                        ) ?>

                                    </div>


                                    <div
                                        class="application-info"
                                    >

                                        <strong>

                                            <?= adminEscape(
                                                $application[
                                                    'business_name'
                                                ]
                                                ?? 'Business'
                                            ) ?>

                                        </strong>


                                        <span>

                                            <?= adminEscape(
                                                $applicationName
                                            ) ?>

                                        </span>

                                    </div>


                                    <span
                                        class="application-status"
                                    >

                                        <?= adminEscape(
                                            $application[
                                                'status'
                                            ]
                                            ?? 'Pending'
                                        ) ?>

                                    </span>


                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div
                class="dashboard-footer"
            >

                HochipoHub Administration Panel
                ·
                <?= date('Y') ?>

            </div>


        </section>


    </main>


</div>


<script
    src="<?= adminEscape(
        BASE_URL
    ) ?>js/dashboard.js"
></script>


</body>

</html>