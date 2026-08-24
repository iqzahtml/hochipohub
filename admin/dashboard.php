<?php
/**
 * HOCHIPOHUB
 * Admin Dashboard
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdmin();


/*
|--------------------------------------------------------------------------
| ADMIN SESSION
|--------------------------------------------------------------------------
*/

$adminId = getUserId();

$adminName = getUserName();

$adminEmail = getUserEmail();

$adminRole = getUserRole();


if (
    empty($adminId) ||
    $adminRole !== 'admin'
) {

    redirect(
        BASE_URL . 'index.php?login=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function adminEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| DASHBOARD STATS
|--------------------------------------------------------------------------
*/

$dashboardStats = [

    'users' => 0,

    'vendors' => 0,

    'customers' => 0,

    'products' => 0,

    'orders' => 0,

    'reviews' => 0,

    'payments' => 0,

    'revenue' => 0

];


$dashboardError = null;


/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
    ");

    $dashboardStats['users'] =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardError =
        'Unable to load user statistics.';

}


/*
|--------------------------------------------------------------------------
| VENDORS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE LOWER(role) = 'vendor'
    ");

    $dashboardStats['vendors'] =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardStats['vendors'] = 0;

}


/*
|--------------------------------------------------------------------------
| CUSTOMERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE LOWER(role) = 'customer'
    ");

    $dashboardStats['customers'] =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardStats['customers'] = 0;

}


/*
|--------------------------------------------------------------------------
| PRODUCTS
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


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
    ");

    $dashboardStats['orders'] =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardStats['orders'] = 0;

}


/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM reviews
    ");

    $dashboardStats['reviews'] =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardStats['reviews'] = 0;

}


/*
|--------------------------------------------------------------------------
| PAYMENTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
    ");

    $dashboardStats['payments'] =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardStats['payments'] = 0;

}


/*
|--------------------------------------------------------------------------
| REVENUE
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COALESCE(
            SUM(total_amount),
            0
        )
        FROM orders
        WHERE order_status != 'Cancelled'
    ");

    $dashboardStats['revenue'] =
        (float) $stmt->fetchColumn();

} catch (Throwable $e) {

    $dashboardStats['revenue'] = 0;

}


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$recentOrders = [];


try {

    $stmt = $pdo->query("
        SELECT

            o.order_id,

            o.order_date,

            o.total_amount,

            o.order_status,

            u.name AS customer_name

        FROM orders o

        INNER JOIN users u
            ON o.customer_id = u.user_id

        ORDER BY
            o.order_date DESC

        LIMIT 5
    ");

    $recentOrders =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $recentOrders = [];

}


/*
|--------------------------------------------------------------------------
| VENDOR APPLICATIONS
|--------------------------------------------------------------------------
*/

$recentApplications = [];


try {

    $stmt = $pdo->query("
        SELECT

            va.application_id,

            va.business_name,

            va.status,

            va.created_at,

            u.name AS applicant_name

        FROM vendor_applications va

        INNER JOIN users u
            ON va.user_id = u.user_id

        ORDER BY
            va.created_at DESC

        LIMIT 5
    ");

    $recentApplications =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $recentApplications = [];

}


/*
|--------------------------------------------------------------------------
| PENDING VENDOR APPLICATION COUNT
|--------------------------------------------------------------------------
*/

$pendingApplications = 0;


try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM vendor_applications
        WHERE status = 'Pending'
    ");

    $pendingApplications =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $pendingApplications = 0;

}


/*
|--------------------------------------------------------------------------
| PENDING PAYMENT COUNT
|--------------------------------------------------------------------------
*/

$pendingPayments = 0;


try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
        WHERE payment_status = 'Pending'
    ");

    $pendingPayments =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $pendingPayments = 0;

}


/*
|--------------------------------------------------------------------------
| PENDING ORDER COUNT
|--------------------------------------------------------------------------
*/

$pendingOrders = 0;


try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'Pending'
    ");

    $pendingOrders =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $pendingOrders = 0;

}


/*
|--------------------------------------------------------------------------
| HIDDEN REVIEW COUNT
|--------------------------------------------------------------------------
*/

$hiddenReviews = 0;


try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM reviews
        WHERE status = 'Hidden'
    ");

    $hiddenReviews =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $hiddenReviews = 0;

}

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
        Admin Dashboard | HochipoHub
    </title>


    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

    /* =====================================================
       DASHBOARD
    ====================================================== */

    :root {

        --dash-bg: #f5f7fb;

        --dash-card: #ffffff;

        --dash-text: #0f172a;

        --dash-muted: #64748b;

        --dash-border: #e6eaf1;

        --dash-red: #e5092f;

        --dash-red-dark: #b90726;

        --dash-blue: #2864f0;

        --dash-purple: #704cff;

        --dash-green: #16a34a;

        --dash-orange: #f97316;

        --dash-cyan: #0891b2;
    }


    * {
        box-sizing: border-box;
    }


    html {
        scroll-behavior: smooth;
    }


    body {

        margin: 0;

        background:
            linear-gradient(
                135deg,
                #f8fafc,
                #f3f6fb
            );

        color:
            var(--dash-text);

        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;
    }


    /* =====================================================
       MAIN
    ====================================================== */

    .admin-dashboard-main {

        min-height: 100vh;

        padding-bottom: 50px;
    }


    /* =====================================================
       TOP HEADER
    ====================================================== */

    .dashboard-header {

        position: sticky;

        top: 0;

        z-index: 100;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

        min-height: 82px;

        padding:
            17px
            34px;

        background:
            rgba(255,255,255,.92);

        border-bottom:
            1px solid
            rgba(226,232,240,.85);

        backdrop-filter:
            blur(14px);
    }


    .dashboard-header-left {

        display: flex;

        align-items: center;

        gap: 15px;

        min-width: 0;
    }


    .dashboard-title-wrap {

        min-width: 0;
    }


    .dashboard-title {

        margin: 0;

        font-size: 25px;

        font-weight: 850;

        letter-spacing:
            -.7px;
    }


    .dashboard-subtitle {

        margin:
            4px
            0
            0;

        color:
            var(--dash-muted);

        font-size: 12px;
    }


    .dashboard-header-user {

        display: flex;

        align-items: center;

        gap: 11px;

        padding:
            7px
            10px
            7px
            7px;

        border:
            1px solid
            var(--dash-border);

        border-radius: 14px;

        background: #ffffff;
    }


    .dashboard-user-avatar {

        width: 38px;
        height: 38px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 11px;

        background:
            linear-gradient(
                135deg,
                #2864f0,
                #704cff
            );

        color: #ffffff;

        font-size: 13px;

        font-weight: 850;
    }


    .dashboard-user-info strong {

        display: block;

        font-size: 12px;

        font-weight: 800;
    }


    .dashboard-user-info span {

        display: block;

        margin-top: 2px;

        color:
            var(--dash-muted);

        font-size: 9px;
    }


    .dashboard-admin-badge {

        display: inline-flex;

        margin-top: 3px;

        padding:
            3px
            7px;

        border-radius: 999px;

        background:
            #fff0f3;

        color:
            var(--dash-red);

        font-size: 8px;

        font-weight: 850;

        text-transform: uppercase;

        letter-spacing: .6px;
    }


    /* =====================================================
       CONTENT
    ====================================================== */

    .dashboard-content {

        width:
            min(
                calc(100% - 56px),
                1450px
            );

        margin:
            0 auto;

        padding-top: 30px;
    }


    /* =====================================================
       HERO
    ====================================================== */

    .dashboard-hero {

        position: relative;

        overflow: hidden;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 30px;

        padding:
            30px
            34px;

        margin-bottom: 25px;

        border-radius: 25px;

        color: #ffffff;

        background:

            radial-gradient(
                circle at 80% 15%,
                rgba(255,255,255,.22),
                transparent 24%
            ),

            radial-gradient(
                circle at 15% 100%,
                rgba(40,100,240,.38),
                transparent 32%
            ),

            linear-gradient(
                120deg,
                #e5092f 0%,
                #c9082d 43%,
                #8f123b 100%
            );

        box-shadow:
            0 18px 45px
            rgba(229,9,47,.18);
    }


    .dashboard-hero::after {

        content: "";

        position: absolute;

        right: -75px;

        bottom: -95px;

        width: 260px;
        height: 260px;

        border-radius: 50%;

        border:
            35px solid
            rgba(255,255,255,.08);
    }


    .dashboard-hero-content {

        position: relative;

        z-index: 1;

        max-width: 700px;
    }


    .dashboard-hero-kicker {

        display: inline-flex;

        align-items: center;

        gap: 7px;

        margin-bottom: 9px;

        padding:
            6px
            10px;

        border:
            1px solid
            rgba(255,255,255,.16);

        border-radius: 999px;

        background:
            rgba(255,255,255,.10);

        font-size: 9px;

        font-weight: 800;

        letter-spacing: 1px;

        text-transform: uppercase;
    }


    .dashboard-hero-kicker-dot {

        width: 6px;
        height: 6px;

        border-radius: 50%;

        background: #ffffff;

        box-shadow:
            0 0 0 4px
            rgba(255,255,255,.12);
    }


    .dashboard-hero h2 {

        margin: 0;

        font-size: clamp(
            25px,
            3vw,
            38px
        );

        line-height: 1.08;

        letter-spacing:
            -1.1px;
    }


    .dashboard-hero p {

        max-width: 610px;

        margin:
            11px
            0
            0;

        color:
            rgba(255,255,255,.82);

        font-size: 13px;

        line-height: 1.7;
    }


    .dashboard-hero-actions {

        position: relative;

        z-index: 1;

        display: flex;

        flex-wrap: wrap;

        gap: 9px;
    }


    .hero-button {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        min-height: 42px;

        padding:
            0
            16px;

        border-radius: 12px;

        text-decoration: none;

        font-size: 11px;

        font-weight: 800;

        transition:
            .2s ease;
    }


    .hero-button.primary {

        background: #ffffff;

        color:
            var(--dash-red);

        box-shadow:
            0 8px 22px
            rgba(0,0,0,.12);
    }


    .hero-button.primary:hover {

        transform:
            translateY(-2px);

        box-shadow:
            0 12px 28px
            rgba(0,0,0,.17);
    }


    .hero-button.ghost {

        border:
            1px solid
            rgba(255,255,255,.25);

        background:
            rgba(255,255,255,.08);

        color: #ffffff;
    }


    .hero-button.ghost:hover {

        background:
            rgba(255,255,255,.15);

        transform:
            translateY(-2px);
    }


    /* =====================================================
       STATS
    ====================================================== */

    .dashboard-section-title {

        display: flex;

        align-items: end;

        justify-content: space-between;

        gap: 15px;

        margin:
            0
            0
            13px;
    }


    .dashboard-section-title h2 {

        margin: 0;

        font-size: 18px;

        font-weight: 850;

        letter-spacing:
            -.4px;
    }


    .dashboard-section-title p {

        margin:
            4px
            0
            0;

        color:
            var(--dash-muted);

        font-size: 10px;
    }


    .dashboard-stats-grid {

        display: grid;

        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );

        gap: 15px;

        margin-bottom: 28px;
    }


    .dashboard-stat-card {

        position: relative;

        overflow: hidden;

        padding: 19px;

        border:
            1px solid
            var(--dash-border);

        border-radius: 19px;

        background: #ffffff;

        box-shadow:
            0 7px 24px
            rgba(15,23,42,.035);

        transition:
            transform .22s ease,
            box-shadow .22s ease;
    }


    .dashboard-stat-card:hover {

        transform:
            translateY(-4px);

        box-shadow:
            0 15px 32px
            rgba(15,23,42,.08);
    }


    .dashboard-stat-card::after {

        content: "";

        position: absolute;

        right: -35px;

        bottom: -45px;

        width: 100px;
        height: 100px;

        border-radius: 50%;

        background:
            rgba(40,100,240,.035);
    }


    .dashboard-stat-top {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;
    }


    .dashboard-stat-icon {

        width: 43px;
        height: 43px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 13px;
    }


    .dashboard-stat-icon svg {

        width: 20px;
        height: 20px;

        fill: none;

        stroke:
            currentColor;

        stroke-width: 1.8;

        stroke-linecap: round;

        stroke-linejoin: round;
    }


    .stat-red .dashboard-stat-icon {

        background:
            #fff0f3;

        color:
            var(--dash-red);
    }


    .stat-blue .dashboard-stat-icon {

        background:
            #edf3ff;

        color:
            var(--dash-blue);
    }


    .stat-purple .dashboard-stat-icon {

        background:
            #f2efff;

        color:
            var(--dash-purple);
    }


    .stat-green .dashboard-stat-icon {

        background:
            #ebfbf0;

        color:
            var(--dash-green);
    }


    .stat-orange .dashboard-stat-icon {

        background:
            #fff4eb;

        color:
            var(--dash-orange);
    }


    .dashboard-stat-label {

        margin-top: 15px;

        color:
            var(--dash-muted);

        font-size: 10px;

        font-weight: 700;
    }


    .dashboard-stat-value {

        margin-top: 3px;

        color:
            var(--dash-text);

        font-size: 27px;

        font-weight: 900;

        letter-spacing:
            -1px;
    }


    .dashboard-stat-meta {

        display: flex;

        align-items: center;

        gap: 5px;

        margin-top: 5px;

        color:
            #94a3b8;

        font-size: 9px;
    }


    /* =====================================================
       ATTENTION
    ====================================================== */

    .attention-grid {

        display: grid;

        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );

        gap: 12px;

        margin-bottom: 29px;
    }


    .attention-card {

        display: flex;

        align-items: center;

        gap: 11px;

        padding: 14px;

        border:
            1px solid
            var(--dash-border);

        border-radius: 16px;

        background: #ffffff;

        text-decoration: none;

        transition:
            .2s ease;
    }


    .attention-card:hover {

        transform:
            translateY(-2px);

        border-color:
            #d7deeb;

        box-shadow:
            0 10px 25px
            rgba(15,23,42,.06);
    }


    .attention-icon {

        width: 38px;
        height: 38px;

        flex: 0 0 38px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 11px;
    }


    .attention-icon svg {

        width: 18px;
        height: 18px;

        fill: none;

        stroke: currentColor;

        stroke-width: 1.8;

        stroke-linecap: round;

        stroke-linejoin: round;
    }


    .attention-warning {

        background:
            #fff5e8;

        color:
            #ea7b00;
    }


    .attention-payment {

        background:
            #edf3ff;

        color:
            var(--dash-blue);
    }


    .attention-review {

        background:
            #fff0f3;

        color:
            var(--dash-red);
    }


    .attention-vendor {

        background:
            #f2efff;

        color:
            var(--dash-purple);
    }


    .attention-info strong {

        display: block;

        color:
            var(--dash-text);

        font-size: 12px;
    }


    .attention-info span {

        display: block;

        margin-top: 2px;

        color:
            var(--dash-muted);

        font-size: 9px;
    }


    /* =====================================================
       QUICK MANAGEMENT
    ====================================================== */

    .quick-grid {

        display: grid;

        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );

        gap: 13px;

        margin-bottom: 30px;
    }


    .quick-card {

        position: relative;

        overflow: hidden;

        min-height: 150px;

        padding: 19px;

        border:
            1px solid
            var(--dash-border);

        border-radius: 18px;

        background: #ffffff;

        text-decoration: none;

        transition:
            transform .22s ease,
            border-color .22s ease,
            box-shadow .22s ease;
    }


    .quick-card:hover {

        transform:
            translateY(-4px);

        border-color:
            rgba(40,100,240,.20);

        box-shadow:
            0 15px 30px
            rgba(15,23,42,.07);
    }


    .quick-card::after {

        content: "→";

        position: absolute;

        top: 17px;

        right: 18px;

        width: 27px;
        height: 27px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 9px;

        background:
            #f5f7fb;

        color:
            #64748b;

        font-size: 14px;

        transition:
            .2s ease;
    }


    .quick-card:hover::after {

        background:
            var(--dash-blue);

        color: #ffffff;

        transform:
            translateX(2px);
    }


    .quick-icon {

        width: 39px;
        height: 39px;

        display: flex;

        align-items: center;
        justify-content: center;

        margin-bottom: 15px;

        border-radius: 12px;
    }


    .quick-icon svg {

        width: 18px;
        height: 18px;

        fill: none;

        stroke: currentColor;

        stroke-width: 1.8;

        stroke-linecap: round;

        stroke-linejoin: round;
    }


    .quick-red {

        background:
            #fff0f3;

        color:
            var(--dash-red);
    }


    .quick-blue {

        background:
            #edf3ff;

        color:
            var(--dash-blue);
    }


    .quick-purple {

        background:
            #f2efff;

        color:
            var(--dash-purple);
    }


    .quick-green {

        background:
            #ebfbf0;

        color:
            var(--dash-green);
    }


    .quick-orange {

        background:
            #fff4eb;

        color:
            var(--dash-orange);
    }


    .quick-cyan {

        background:
            #e9faff;

        color:
            var(--dash-cyan);
    }


    .quick-card-title {

        padding-right: 30px;

        color:
            var(--dash-text);

        font-size: 13px;

        font-weight: 850;
    }


    .quick-card-description {

        max-width: 220px;

        margin-top: 7px;

        color:
            var(--dash-muted);

        font-size: 9px;

        line-height: 1.6;
    }


    /* =====================================================
       BOTTOM GRID
    ====================================================== */

    .dashboard-bottom-grid {

        display: grid;

        grid-template-columns:
            minmax(0, 1.3fr)
            minmax(320px, .7fr);

        gap: 16px;

        margin-bottom: 30px;
    }


    .dashboard-panel {

        overflow: hidden;

        border:
            1px solid
            var(--dash-border);

        border-radius: 19px;

        background: #ffffff;

        box-shadow:
            0 7px 24px
            rgba(15,23,42,.03);
    }


    .dashboard-panel-header {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 12px;

        padding:
            18px
            19px;

        border-bottom:
            1px solid
            #eef1f5;
    }


    .dashboard-panel-header h3 {

        margin: 0;

        font-size: 14px;

        font-weight: 850;
    }


    .dashboard-panel-header p {

        margin:
            4px
            0
            0;

        color:
            var(--dash-muted);

        font-size: 9px;
    }


    .dashboard-view-all {

        color:
            var(--dash-blue);

        font-size: 9px;

        font-weight: 800;

        text-decoration: none;
    }


    .dashboard-view-all:hover {

        text-decoration:
            underline;
    }


    /* =====================================================
       TABLE
    ====================================================== */

    .dashboard-table-wrap {

        overflow-x: auto;
    }


    .dashboard-table {

        width: 100%;

        border-collapse:
            collapse;
    }


    .dashboard-table th {

        padding:
            11px
            15px;

        background:
            #f8fafc;

        color:
            #718096;

        font-size: 8px;

        font-weight: 800;

        letter-spacing:
            .4px;

        text-align: left;

        text-transform: uppercase;

        white-space: nowrap;
    }


    .dashboard-table td {

        padding:
            13px
            15px;

        border-top:
            1px solid
            #f0f2f5;

        color:
            #334155;

        font-size: 9px;

        white-space: nowrap;
    }


    .dashboard-order-id {

        color:
            var(--dash-blue);

        font-weight: 850;
    }


    .dashboard-customer {

        color:
            var(--dash-text);

        font-weight: 750;
    }


    .dashboard-money {

        color:
            var(--dash-text);

        font-weight: 800;
    }


    .dashboard-status {

        display: inline-flex;

        padding:
            5px
            8px;

        border-radius: 999px;

        font-size: 8px;

        font-weight: 800;
    }


    .status-pending {

        background:
            #fff5e8;

        color:
            #c56a00;
    }


    .status-processing {

        background:
            #edf3ff;

        color:
            #2259d5;
    }


    .status-completed {

        background:
            #ebfbf0;

        color:
            #16823d;
    }


    .status-cancelled {

        background:
            #fff0f3;

        color:
            #c90a2e;
    }


    .status-default {

        background:
            #f1f5f9;

        color:
            #64748b;
    }


    /* =====================================================
       VENDOR APPLICATIONS
    ====================================================== */

    .application-list {

        padding: 4px 17px 10px;
    }


    .application-item {

        display: flex;

        align-items: center;

        gap: 11px;

        padding:
            13px
            0;

        border-bottom:
            1px solid
            #eef1f5;
    }


    .application-item:last-child {

        border-bottom:
            0;
    }


    .application-avatar {

        width: 36px;
        height: 36px;

        flex: 0 0 36px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 11px;

        background:
            linear-gradient(
                135deg,
                #f1f5ff,
                #e7edff
            );

        color:
            var(--dash-blue);

        font-size: 11px;

        font-weight: 850;
    }


    .application-info {

        min-width: 0;

        flex: 1;
    }


    .application-name {

        overflow: hidden;

        color:
            var(--dash-text);

        font-size: 10px;

        font-weight: 800;

        white-space: nowrap;

        text-overflow: ellipsis;
    }


    .application-business {

        overflow: hidden;

        margin-top: 3px;

        color:
            var(--dash-muted);

        font-size: 8px;

        white-space: nowrap;

        text-overflow: ellipsis;
    }


    .application-status {

        padding:
            5px
            7px;

        border-radius: 999px;

        background:
            #fff5e8;

        color:
            #c56a00;

        font-size: 7px;

        font-weight: 850;

        text-transform: uppercase;
    }


    /* =====================================================
       ERROR
    ====================================================== */

    .dashboard-error {

        margin-bottom: 20px;

        padding:
            14px
            17px;

        border:
            1px solid
            #fecaca;

        border-radius: 13px;

        background:
            #fff1f2;

        color:
            #b91c1c;

        font-size: 10px;
    }


    /* =====================================================
       FOOTER
    ====================================================== */

    .dashboard-footer {

        padding:
            10px
            0
            30px;

        color:
            #94a3b8;

        font-size: 9px;

        text-align: center;
    }


    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 1250px) {

        .dashboard-stats-grid {

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }


        .attention-grid {

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }


        .quick-grid {

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );
        }

    }


    @media (max-width: 950px) {

        .dashboard-header {

            padding:
                15px
                22px;
        }


        .dashboard-content {

            width:
                min(
                    calc(100% - 36px),
                    1450px
                );
        }


        .dashboard-bottom-grid {

            grid-template-columns:
                1fr;
        }

    }


    @media (max-width: 720px) {

        .dashboard-header {

            min-height:
                72px;

            padding:
                13px
                17px;
        }


        .dashboard-title {

            font-size: 21px;
        }


        .dashboard-header-user {

            padding: 4px;
        }


        .dashboard-user-info {

            display: none;
        }


        .dashboard-content {

            width:
                calc(100% - 24px);

            padding-top: 18px;
        }


        .dashboard-hero {

            align-items:
                flex-start;

            flex-direction:
                column;

            padding:
                24px
                22px;

            border-radius:
                20px;
        }


        .dashboard-hero-actions {

            width: 100%;
        }


        .hero-button {

            flex: 1;
        }


        .quick-grid {

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
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
                27px;
        }


        .dashboard-hero p {

            font-size:
                11px;
        }

    }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    require_once dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    ?>


    <!-- MAIN -->

    <main class="admin-main admin-dashboard-main">


        <!-- MOBILE BUTTON + HEADER -->

        <header class="dashboard-header">


            <div class="dashboard-header-left">


                <button
                    type="button"
                    class="admin-mobile-toggle"
                    onclick="openAdminSidebar()"
                    aria-label="Open admin menu"
                >

                    <svg viewBox="0 0 24 24">

                        <line
                            x1="4"
                            y1="6"
                            x2="20"
                            y2="6"
                        ></line>

                        <line
                            x1="4"
                            y1="12"
                            x2="20"
                            y2="12"
                        ></line>

                        <line
                            x1="4"
                            y1="18"
                            x2="20"
                            y2="18"
                        ></line>

                    </svg>

                </button>


                <div class="dashboard-title-wrap">

                    <h1 class="dashboard-title">
                        Admin Dashboard
                    </h1>

                    <p class="dashboard-subtitle">
                        Manage your HochipoHub marketplace.
                    </p>

                </div>

            </div>


            <div class="dashboard-header-user">


                <div class="dashboard-user-avatar">

                    <?= adminEscape(
                        strtoupper(
                            substr(
                                trim(
                                    $adminName
                                ) !== ''
                                    ? trim(
                                        $adminName
                                    )
                                    : 'A',
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>


                <div class="dashboard-user-info">

                    <strong>
                        <?= adminEscape(
                            $adminName !== ''
                                ? $adminName
                                : 'Administrator'
                        ) ?>
                    </strong>

                    <span>
                        <?= adminEscape(
                            $adminEmail !== ''
                                ? $adminEmail
                                : 'No email available'
                        ) ?>
                    </span>

                    <span class="dashboard-admin-badge">
                        Administrator
                    </span>

                </div>


            </div>


        </header>


        <!-- CONTENT -->

        <section class="dashboard-content">


            <!-- HERO -->

            <div class="dashboard-hero">


                <div class="dashboard-hero-content">


                    <div class="dashboard-hero-kicker">

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

                        Everything you need to monitor
                        users, vendors, products, orders,
                        payments and marketplace activity
                        is right here.

                    </p>


                </div>


                <div class="dashboard-hero-actions">


                    <a
                        href="orders.php"
                        class="hero-button primary"
                    >
                        View Orders
                    </a>


                    <a
                        href="payments.php"
                        class="hero-button ghost"
                    >
                        Payments
                    </a>


                </div>


            </div>


            <?php if ($dashboardError !== null): ?>

                <div class="dashboard-error">

                    <?= adminEscape(
                        $dashboardError
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 OVERVIEW
            ================================================== -->

            <div class="dashboard-section-title">

                <div>

                    <h2>
                        Marketplace Overview
                    </h2>

                    <p>
                        Your marketplace at a glance.
                    </p>

                </div>

            </div>


            <div class="dashboard-stats-grid">


                <!-- USERS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-red">

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


                    <div class="dashboard-stat-label">
                        Total Users
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['users']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        Customers & vendors
                    </div>

                </div>


                <!-- VENDORS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-blue">

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


                    <div class="dashboard-stat-label">
                        Vendors
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['vendors']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        Marketplace sellers
                    </div>

                </div>


                <!-- PRODUCTS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-purple">

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

                        </div>

                    </div>


                    <div class="dashboard-stat-label">
                        Products
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['products']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        Listed marketplace items
                    </div>

                </div>


                <!-- ORDERS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-green">

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


                    <div class="dashboard-stat-label">
                        Orders
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['orders']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        <?= number_format(
                            $pendingOrders
                        ) ?>
                        pending order(s)
                    </div>

                </div>


                <!-- REVENUE -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-orange">

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


                    <div class="dashboard-stat-label">
                        Marketplace Revenue
                    </div>

                    <div class="dashboard-stat-value">

                        RM
                        <?= number_format(
                            $dashboardStats['revenue'],
                            2
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        Excludes cancelled orders
                    </div>

                </div>


                <!-- CUSTOMERS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-blue">

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


                    <div class="dashboard-stat-label">
                        Customers
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['customers']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        Registered customers
                    </div>

                </div>


                <!-- PAYMENTS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-green">

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


                    <div class="dashboard-stat-label">
                        Payments
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['payments']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        <?= number_format(
                            $pendingPayments
                        ) ?>
                        pending
                    </div>

                </div>


                <!-- REVIEWS -->

                <div class="dashboard-stat-card">

                    <div class="dashboard-stat-top">

                        <div class="dashboard-stat-icon stat-red">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                                ></path>

                            </svg>

                        </div>

                    </div>


                    <div class="dashboard-stat-label">
                        Reviews
                    </div>

                    <div class="dashboard-stat-value">

                        <?= number_format(
                            $dashboardStats['reviews']
                        ) ?>

                    </div>

                    <div class="dashboard-stat-meta">
                        <?= number_format(
                            $hiddenReviews
                        ) ?>
                        hidden
                    </div>

                </div>


            </div>


            <!-- =================================================
                 ATTENTION
            ================================================== -->

            <div class="dashboard-section-title">

                <div>

                    <h2>
                        Needs Your Attention
                    </h2>

                    <p>
                        Quick access to items that may require action.
                    </p>

                </div>

            </div>


            <div class="attention-grid">


                <a
                    href="orders.php"
                    class="attention-card"
                >

                    <div
                        class="attention-icon attention-warning"
                    >

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M10.3 3.2L2.2 17a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3L13.7 3.2a2 2 0 0 0-3.4 0z"
                            ></path>

                            <line
                                x1="12"
                                y1="9"
                                x2="12"
                                y2="13"
                            ></line>

                            <line
                                x1="12"
                                y1="17"
                                x2="12"
                                y2="17"
                            ></line>

                        </svg>

                    </div>


                    <div class="attention-info">

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


                <a
                    href="payments.php"
                    class="attention-card"
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


                    <div class="attention-info">

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


                <a
                    href="reviews.php"
                    class="attention-card"
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


                    <div class="attention-info">

                        <strong>
                            <?= number_format(
                                $hiddenReviews
                            ) ?>
                            Hidden Reviews
                        </strong>

                        <span>
                            Manage customer reviews
                        </span>

                    </div>

                </a>


                <a
                    href="vendors.php"
                    class="attention-card"
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


                    <div class="attention-info">

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
                 QUICK MANAGEMENT
            ================================================== -->

            <div class="dashboard-section-title">

                <div>

                    <h2>
                        Quick Management
                    </h2>

                    <p>
                        Jump straight into your admin tools.
                    </p>

                </div>

            </div>


            <div class="quick-grid">


                <!-- USERS -->

                <a
                    href="users.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-red">

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

                    <div class="quick-card-title">
                        Manage Users
                    </div>

                    <div class="quick-card-description">
                        Manage customer, vendor and administrator accounts.
                    </div>

                </a>


                <!-- VENDORS -->

                <a
                    href="vendors.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-blue">

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

                    <div class="quick-card-title">
                        Manage Vendors
                    </div>

                    <div class="quick-card-description">
                        Review vendor accounts and applications.
                    </div>

                </a>


                <!-- PRODUCTS -->

                <a
                    href="products.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-purple">

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
                            ></path>

                        </svg>

                    </div>

                    <div class="quick-card-title">
                        Manage Products
                    </div>

                    <div class="quick-card-description">
                        Control products listed by marketplace vendors.
                    </div>

                </a>


                <!-- ORDERS -->

                <a
                    href="orders.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-green">

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

                    <div class="quick-card-title">
                        Manage Orders
                    </div>

                    <div class="quick-card-description">
                        Monitor customer orders and order status.
                    </div>

                </a>


                <!-- PAYMENTS -->

                <a
                    href="payments.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-orange">

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

                    <div class="quick-card-title">
                        Payments
                    </div>

                    <div class="quick-card-description">
                        Monitor payment transactions and statuses.
                    </div>

                </a>


                <!-- COMMISSION -->

                <a
                    href="commission.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-cyan">

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

                    <div class="quick-card-title">
                        Commission
                    </div>

                    <div class="quick-card-description">
                        Track marketplace commission and vendor earnings.
                    </div>

                </a>


                <!-- REVIEWS -->

                <a
                    href="reviews.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-red">

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                            ></path>

                        </svg>

                    </div>

                    <div class="quick-card-title">
                        Reviews
                    </div>

                    <div class="quick-card-description">
                        Monitor customer feedback and review visibility.
                    </div>

                </a>


                <!-- SETTINGS -->

                <a
                    href="settings.php"
                    class="quick-card"
                >

                    <div class="quick-icon quick-purple">

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

                    </div>

                    <div class="quick-card-title">
                        Admin Settings
                    </div>

                    <div class="quick-card-description">
                        Manage administrator account and security.
                    </div>

                </a>


            </div>


            <!-- =================================================
                 RECENT ACTIVITY
            ================================================== -->

            <div class="dashboard-bottom-grid">


                <!-- RECENT ORDERS -->

                <div class="dashboard-panel">


                    <div class="dashboard-panel-header">

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


                    <div class="dashboard-table-wrap">


                        <table class="dashboard-table">


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


                            <?php if (
                                empty(
                                    $recentOrders
                                )
                            ): ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        style="
                                            text-align:center;
                                            padding:28px;
                                            color:#94a3b8;
                                        "
                                    >
                                        No recent orders found.
                                    </td>

                                </tr>

                            <?php else: ?>


                                <?php foreach (
                                    $recentOrders
                                    as $order
                                ): ?>


                                    <?php

                                    $status =
                                        strtolower(
                                            trim(
                                                $order[
                                                    'order_status'
                                                ] ?? ''
                                            )
                                        );

                                    $statusClass =
                                        'status-default';

                                    if (
                                        $status === 'pending'
                                    ) {

                                        $statusClass =
                                            'status-pending';

                                    } elseif (
                                        $status === 'processing'
                                    ) {

                                        $statusClass =
                                            'status-processing';

                                    } elseif (
                                        $status === 'completed'
                                    ) {

                                        $statusClass =
                                            'status-completed';

                                    } elseif (
                                        $status === 'cancelled'
                                    ) {

                                        $statusClass =
                                            'status-cancelled';

                                    }

                                    ?>


                                    <tr>


                                        <td>

                                            <span
                                                class="dashboard-order-id"
                                            >
                                                #
                                                <?= (int)
                                                    $order[
                                                        'order_id'
                                                    ] ?>
                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="dashboard-customer"
                                            >

                                                <?= adminEscape(
                                                    $order[
                                                        'customer_name'
                                                    ] ??
                                                    'Customer'
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= !empty(
                                                $order[
                                                    'order_date'
                                                ]
                                            )
                                                ? adminEscape(
                                                    date(
                                                        'd M Y',
                                                        strtotime(
                                                            $order[
                                                                'order_date'
                                                            ]
                                                        )
                                                    )
                                                )
                                                : '-'
                                            ?>

                                        </td>


                                        <td>

                                            <span
                                                class="dashboard-money"
                                            >

                                                RM
                                                <?= number_format(
                                                    (float)
                                                    (
                                                        $order[
                                                            'total_amount'
                                                        ] ?? 0
                                                    ),
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    dashboard-status
                                                    <?= $statusClass ?>
                                                "
                                            >

                                                <?= adminEscape(
                                                    $order[
                                                        'order_status'
                                                    ] ??
                                                    'Unknown'
                                                ) ?>

                                            </span>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


                <!-- VENDOR APPLICATIONS -->

                <div class="dashboard-panel">


                    <div class="dashboard-panel-header">

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


                    <div class="application-list">


                        <?php if (
                            empty(
                                $recentApplications
                            )
                        ): ?>


                            <div
                                style="
                                    padding:28px 0;
                                    color:#94a3b8;
                                    font-size:10px;
                                    text-align:center;
                                "
                            >

                                No vendor applications found.

                            </div>


                        <?php else: ?>


                            <?php foreach (
                                $recentApplications
                                as $application
                            ): ?>


                                <div class="application-item">


                                    <div
                                        class="application-avatar"
                                    >

                                        <?= adminEscape(
                                            strtoupper(
                                                substr(
                                                    trim(
                                                        $application[
                                                            'applicant_name'
                                                        ] ?? 'V'
                                                    ) !== ''
                                                        ? trim(
                                                            $application[
                                                                'applicant_name'
                                                            ] ?? 'V'
                                                        )
                                                        : 'V',
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>

                                    </div>


                                    <div class="application-info">


                                        <div
                                            class="application-name"
                                        >

                                            <?= adminEscape(
                                                $application[
                                                    'applicant_name'
                                                ] ??
                                                'Applicant'
                                            ) ?>

                                        </div>


                                        <div
                                            class="application-business"
                                        >

                                            <?= adminEscape(
                                                $application[
                                                    'business_name'
                                                ] ??
                                                'Vendor application'
                                            ) ?>

                                        </div>


                                    </div>


                                    <span
                                        class="application-status"
                                    >

                                        <?= adminEscape(
                                            $application[
                                                'status'
                                            ] ??
                                            'Pending'
                                        ) ?>

                                    </span>


                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


            <!-- FOOTER -->

            <div class="dashboard-footer">

                HochipoHub Administration Panel
                • <?= date('Y') ?>

            </div>


        </section>


    </main>


</div>


</body>

</html>