<?php

require_once DIR . '/config.php';
require_once DIR . '/database/db.php';
require_once DIR . '/includes/session.php';
require_once DIR . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . site_url('index.php?login=required'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        profile_image,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

$stmt->close();

if (!$user) {
    session_destroy();

    header('Location: ' . site_url('index.php'));
    exit;
}

if ($user['status'] !== 'active') {
    session_destroy();

    header('Location: ' . site_url('index.php?account=inactive'));
    exit;
}

$role = $user['role'];

/*
|--------------------------------------------------------------------------
| DEFAULT DASHBOARD DATA
|--------------------------------------------------------------------------
*/

$stats = [
    'orders' => 0,
    'cart' => 0,
    'wishlist' => 0,
    'reviews' => 0,

    'products' => 0,
    'stock' => 0,
    'sales' => 0,
    'commission' => 0,

    'users' => 0,
    'vendors' => 0,
    'pending_vendors' => 0,
    'pending_orders' => 0
];

$recentOrders = [];
$recentProducts = [];
$recentCommissions = [];

/*
|--------------------------------------------------------------------------
| CUSTOMER DASHBOARD
|--------------------------------------------------------------------------
*/

if ($role === 'customer') {

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER STATS
    |--------------------------------------------------------------------------
    */

    // Orders
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE customer_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $stats['orders'] = (int) ($result->fetch_assoc()['total'] ?? 0);

    $stmt->close();


    // Cart
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart
        WHERE customer_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $stats['cart'] = (int) ($result->fetch_assoc()['total'] ?? 0);

    $stmt->close();


    // Wishlist
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $stats['wishlist'] = (int) ($result->fetch_assoc()['total'] ?? 0);

    $stmt->close();


    // Reviews
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM reviews
        WHERE customer_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $stats['reviews'] = (int) ($result->fetch_assoc()['total'] ?? 0);

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | RECENT ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            o.order_id,
            o.order_date,
            o.total_amount,
            o.delivery_method,
            o.order_status
        FROM orders o
        WHERE o.customer_id = ?
        ORDER BY o.order_date DESC
        LIMIT 6
    ");$stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| VENDOR DASHBOARD
|--------------------------------------------------------------------------
*/

elseif ($role === 'vendor') {

    /*
    |--------------------------------------------------------------------------
    | GET VENDOR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            vendor_id,
            business_name,
            business_logo,
            approval_status,
            delivery_method
        FROM vendors
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $vendor = $result->fetch_assoc();

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | VENDOR EXISTS
    |--------------------------------------------------------------------------
    */

    if ($vendor) {

        $vendorId = (int) $vendor['vendor_id'];


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM products
            WHERE vendor_id = ?
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        $stats['products'] =
            (int) ($result->fetch_assoc()['total'] ?? 0);

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | STOCK
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                COALESCE(SUM(stock_quantity), 0) AS total
            FROM products
            WHERE vendor_id = ?
            AND status != 'Hidden'
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        $stats['stock'] =
            (int) ($result->fetch_assoc()['total'] ?? 0);

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | SALES
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                COALESCE(
                    SUM(
                        CASE
                            WHEN vendor_status != 'Cancelled'
                            THEN subtotal
                            ELSE 0
                        END
                    ),
                    0
                ) AS total
            FROM vendor_orders
            WHERE vendor_id = ?
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        $stats['sales'] =
            (float) ($result->fetch_assoc()['total'] ?? 0);

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | COMMISSION
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                COALESCE(
                    SUM(commission_amount),
                    0
                ) AS total
            FROM commission
            WHERE vendor_id = ?
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        $stats['commission'] =
            (float) ($result->fetch_assoc()['total'] ?? 0);

        $stmt->close();


        /*|--------------------------------------------------------------------------
        | VENDOR ORDER COUNT
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM vendor_orders
            WHERE vendor_id = ?
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        $stats['orders'] =
            (int) ($result->fetch_assoc()['total'] ?? 0);

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | RECENT VENDOR ORDERS
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                vo.vendor_order_id,
                vo.order_id,
                vo.subtotal,
                vo.delivery_fee,
                vo.vendor_status,
                vo.tracking_number,
                vo.created_at,

                u.name AS customer_name

            FROM vendor_orders vo

            INNER JOIN orders o
                ON vo.order_id = o.order_id

            INNER JOIN users u
                ON o.customer_id = u.user_id

            WHERE vo.vendor_id = ?

            ORDER BY vo.created_at DESC

            LIMIT 6
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $recentOrders[] = $row;
        }

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | RECENT PRODUCTS
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                p.product_id,
                p.product_name,
                p.price,
                p.stock_quantity,
                p.image,
                p.status,

                c.category_name

            FROM products p

            LEFT JOIN categories c
                ON p.category_id = c.category_id

            WHERE p.vendor_id = ?

            ORDER BY p.created_at DESC

            LIMIT 6
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $recentProducts[] = $row;
        }

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | RECENT COMMISSION
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                commission_id,
                order_id,
                commission_rate,
                commission_amount,
                status,
                created_at

            FROM commission

            WHERE vendor_id = ?

            ORDER BY created_at DESC

            LIMIT 5
        ");

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $recentCommissions[] = $row;
        }

        $stmt->close();

    } else {

        $vendor = null;
        $vendorId = 0;

    }
}


/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

elseif ($role === 'admin') {

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
    ");

    $stats['users'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);


    /*|--------------------------------------------------------------------------
    | VENDORS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM vendors
        WHERE approval_status = 'Approved'
    ");

    $stats['vendors'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | PENDING VENDOR APPLICATIONS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM vendor_applications
        WHERE status = 'Pending'
    ");

    $stats['pending_vendors'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | PENDING ORDERS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE order_status = 'Pending'
    ");

    $stats['pending_orders'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | PRODUCTS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
    ");

    $stats['products'] =
        (int) ($result->fetch_assoc()['total'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | TOTAL SALES
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN order_status != 'Cancelled'
                        THEN total_amount
                        ELSE 0
                    END
                ),
                0
            ) AS total
        FROM orders
    ");

    $stats['sales'] =
        (float) ($result->fetch_assoc()['total'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | RECENT ORDERS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
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

        LIMIT 8
    ");

    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }


    /*
    |--------------------------------------------------------------------------
    | RECENT PRODUCTS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT
            p.product_id,
            p.product_name,
            p.price,
            p.stock_quantity,
            p.status,

            v.business_name

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        ORDER BY p.created_at DESC

        LIMIT 6
    ");

    while ($row = $result->fetch_assoc()) {
        $recentProducts[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| FORMAT FUNCTIONS
|--------------------------------------------------------------------------
*/

function dashboard_money($amount)
{
    return 'RM ' . number_format(
        (float) $amount,
        2
    );
}


function dashboard_date($date)
{
    if (!$date) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );
}


function dashboard_status_class($status)
{
    $status = strtolower(
        str_replace(
            ' ',
            '-',
            $status)
    );

    return 'status-' . $status;
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
        Dashboard |
        <?php echo htmlspecialchars(SITE_NAME); ?>
    </title>


    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/dashboard.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >


    <style>

        /* =====================================================
           DASHBOARD
        ===================================================== */

        .dashboard-page {

            min-height: 100vh;

            padding-bottom: 80px;

            background:

                radial-gradient(
                    circle at 10% 0%,
                    rgba(37, 99, 235, .18),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 90% 10%,
                    rgba(14, 165, 233, .13),
                    transparent 25%
                ),

                linear-gradient(
                    145deg,
                    #020617,
                    #061a35 55%,
                    #020617
                );

            color: #f8fafc;

        }


        .dashboard-container {

            width: 90%;

            max-width: 1400px;

            margin: auto;

            padding-top: 40px;

        }


        /* =====================================================
           HERO
        ===================================================== */

        .dashboard-hero {

            position: relative;

            overflow: hidden;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 25px;

            margin-bottom: 22px;

            padding: 34px;

            border:

                1px solid
                rgba(56, 189, 248, .18);

            border-radius: 27px;

            background:

                linear-gradient(
                    135deg,
                    rgba(15, 23, 42, .96),
                    rgba(8, 47, 73, .72)
                );

            box-shadow:

                0 25px 80px
                rgba(0, 0, 0, .25);

        }


        .dashboard-hero::before {

            content: "";

            position: absolute;

            width: 300px;

            height: 300px;

            right: -110px;

            top: -150px;

            border-radius: 50%;

            background:

                rgba(14, 165, 233, .12);

            filter: blur(4px);

        }


        .dashboard-hero-content {

            position: relative;

            z-index: 2;

        }


        .dashboard-eyebrow {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 9px;

            color: #38bdf8;

            font-size: 9px;

            font-weight: 950;

            letter-spacing: 1.8px;

        }


        .dashboard-eyebrow::before {

            content: "";

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #22d3ee;

            box-shadow:

                0 0 15px
                rgba(34, 211, 238, .8);

        }


        .dashboard-hero h1 {

            margin: 0;

            font-size: clamp(
                30px,
                4.5vw,
                48px
            );

            line-height: 1;

            font-weight: 950;

            letter-spacing: -1.7px;

        }


        .dashboard-hero h1 span {

            color: #38bdf8;

        }


        .dashboard-hero p {

            max-width: 620px;

            margin: 12px 0 0;

            color: #64748b;

            font-size: 12px;

            line-height: 1.7;

        }


        .dashboard-role {position: relative;

            z-index: 2;

            min-width: 170px;

            padding: 19px;

            border:

                1px solid
                rgba(56, 189, 248, .17);

            border-radius: 18px;

            background:

                rgba(2, 6, 23, .45);

            text-align: center;

        }


        .dashboard-role small {

            display: block;

            margin-bottom: 5px;

            color: #475569;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.2px;

        }


        .dashboard-role strong {

            display: block;

            color: #7dd3fc;

            font-size: 18px;

            font-weight: 950;

            text-transform: uppercase;

        }


        /* =====================================================
           STATS
        ===================================================== */

        .dashboard-stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 14px;

            margin-bottom: 22px;

        }


        .dashboard-stat {

            position: relative;

            overflow: hidden;

            padding: 20px;

            border:

                1px solid
                rgba(148, 163, 184, .10);

            border-radius: 18px;

            background:

                rgba(15, 23, 42, .78);

            transition: .25s ease;

        }


        .dashboard-stat:hover {

            transform:
                translateY(-3px);

            border-color:

                rgba(56, 189, 248, .28);

            box-shadow:

                0 15px 40px
                rgba(0, 0, 0, .20);

        }


        .dashboard-stat-icon {

            position: absolute;

            top: 17px;

            right: 17px;

            display: flex;

            align-items: center;

            justify-content: center;

            width: 33px;

            height: 33px;

            border-radius: 10px;

            background:

                rgba(14, 165, 233, .09);

            color: #38bdf8;

            font-size: 13px;

            font-weight: 950;

        }


        .dashboard-stat-label {

            display: block;

            margin-bottom: 8px;

            color: #64748b;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-transform: uppercase;

        }


        .dashboard-stat-value {

            display: block;

            color: #f8fafc;

            font-size: 24px;

            font-weight: 950;

        }


        .dashboard-stat-sub {

            display: block;

            margin-top: 5px;

            color: #475569;

            font-size: 9px;

        }


        /* =====================================================
           MAIN GRID
        ===================================================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                1.55fr 1fr;

            gap: 18px;

        }


        .dashboard-card {

            overflow: hidden;

            border:

                1px solid
                rgba(148, 163, 184, .09);

            border-radius: 20px;

            background:

                rgba(15, 23, 42, .78);

        }


        .dashboard-card-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 18px 20px;

            border-bottom:

                1px solid
                rgba(148, 163, 184, .07);

        }


        .dashboard-card-header h2 {

            margin: 0;

            color: #e2e8f0;

            font-size: 14px;

            font-weight: 900;

        }


        .dashboard-card-header span {

            color: #475569;

            font-size: 9px;

        }


        .dashboard-view-link {

            color: #38bdf8;

            font-size: 9px;

            font-weight: 900;text-decoration: none;

        }


        .dashboard-view-link:hover {

            color: #7dd3fc;

        }


        /* =====================================================
           ORDER LIST
        ===================================================== */

        .dashboard-list {

            display: grid;

        }


        .dashboard-list-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 15px 20px;

            border-bottom:

                1px solid
                rgba(148, 163, 184, .055);

            transition: .2s ease;

        }


        .dashboard-list-item:last-child {

            border-bottom: 0;

        }


        .dashboard-list-item:hover {

            background:

                rgba(14, 165, 233, .035);

        }


        .dashboard-item-left {

            min-width: 0;

        }


        .dashboard-item-title {

            display: block;

            overflow: hidden;

            color: #cbd5e1;

            font-size: 11px;

            font-weight: 850;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .dashboard-item-meta {

            display: block;

            margin-top: 4px;

            color: #475569;

            font-size: 9px;

        }


        .dashboard-item-right {

            flex-shrink: 0;

            text-align: right;

        }


        .dashboard-item-amount {

            display: block;

            color: #f8fafc;

            font-size: 10px;

            font-weight: 900;

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .dashboard-status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            margin-top: 5px;

            padding: 5px 8px;

            border-radius: 99px;

            font-size: 8px;

            font-weight: 900;

        }


        .dashboard-status::before {

            content: "";

            width: 4px;

            height: 4px;

            border-radius: 50%;

        }


        .status-pending {

            background:
                rgba(250, 204, 21, .08);

            color: #fde047;

        }


        .status-pending::before {

            background: #facc15;

        }


        .status-processing {

            background:
                rgba(56, 189, 248, .08);

            color: #7dd3fc;

        }


        .status-processing::before {

            background: #38bdf8;

        }


        .status-completed,
        .status-paid,
        .status-approved,
        .status-ready {

            background:
                rgba(34, 197, 94, .08);

            color: #86efac;

        }


        .status-completed::before,
        .status-paid::before,
        .status-approved::before,
        .status-ready::before {

            background: #22c55e;

        }


        .status-cancelled,
        .status-rejected,
        .status-suspended {

            background:
                rgba(239, 68, 68, .08);

            color: #fca5a5;

        }


        .status-cancelled::before,
        .status-rejected::before,
        .status-suspended::before {

            background: #ef4444;

        }


        .status-shipped {

            background:
                rgba(168, 85, 247, .08);

            color: #d8b4fe;

        }


        .status-shipped::before {

            background: #a855f7;

        }


        /* =====================================================
           PRODUCT LIST
        ===================================================== */

        .dashboard-product {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 20px;

            border-bottom:

                1px solid
                rgba(148, 163, 184, .055);

        }


        .dashboard-product:last-child {border-bottom: 0;

        }


        .dashboard-product-image {

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            width: 43px;

            height: 43px;

            overflow: hidden;

            border-radius: 12px;

            background:

                rgba(2, 6, 23, .7);

            border:

                1px solid
                rgba(148, 163, 184, .08);

        }


        .dashboard-product-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .dashboard-product-placeholder {

            color: #334155;

            font-size: 16px;

        }


        .dashboard-product-info {

            min-width: 0;

            flex: 1;

        }


        .dashboard-product-name {

            display: block;

            overflow: hidden;

            color: #cbd5e1;

            font-size: 10px;

            font-weight: 850;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .dashboard-product-meta {

            display: block;

            margin-top: 4px;

            color: #475569;

            font-size: 8px;

        }


        .dashboard-product-price {

            flex-shrink: 0;

            color: #7dd3fc;

            font-size: 10px;

            font-weight: 900;

        }


        /* =====================================================
           QUICK ACTIONS
        ===================================================== */

        .dashboard-actions {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 9px;

            padding: 17px;

        }


        .dashboard-action {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 12px;

            border:

                1px solid
                rgba(148, 163, 184, .08);

            border-radius: 13px;

            background:

                rgba(2, 6, 23, .32);

            color: #94a3b8;

            font-size: 9px;

            font-weight: 800;

            text-decoration: none;

            transition: .2s ease;

        }


        .dashboard-action:hover {

            border-color:

                rgba(56, 189, 248, .25);

            background:

                rgba(14, 165, 233, .07);

            color: #7dd3fc;

            transform:
                translateY(-2px);

        }


        .dashboard-action-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 28px;

            height: 28px;

            border-radius: 9px;

            background:

                rgba(14, 165, 233, .08);

            color: #38bdf8;

            font-weight: 900;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .dashboard-empty {

            padding: 42px 20px;

            text-align: center;

        }


        .dashboard-empty-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 50px;

            height: 50px;

            margin: 0 auto 12px;

            border-radius: 16px;

            background:

                rgba(14, 165, 233, .08);

            color: #38bdf8;

            font-size: 19px;

        }


        .dashboard-empty h3 {

            margin: 0;

            color: #cbd5e1;

            font-size: 13px;

        }


        .dashboard-empty p {

            margin: 6px auto 0;

            max-width: 350px;

            color: #475569;

            font-size: 9px;

            line-height: 1.6;

        }


        /* =====================================================
           VENDOR NOTICE
        ===================================================== */

        .vendor-notice {

            margin-bottom: 18px;padding: 16px 19px;

            border:

                1px solid
                rgba(250, 204, 21, .16);

            border-radius: 16px;

            background:

                rgba(250, 204, 21, .06);

        }


        .vendor-notice strong {

            display: block;

            color: #fde047;

            font-size: 11px;

        }


        .vendor-notice p {

            margin: 5px 0 0;

            color: #a16207;

            font-size: 9px;

            line-height: 1.6;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1050px) {

            .dashboard-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .dashboard-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 700px) {

            .dashboard-container {

                width: 92%;

                padding-top: 25px;

            }

            .dashboard-hero {

                flex-direction: column;

                align-items: flex-start;

                padding: 27px;

            }

            .dashboard-role {

                width: 100%;

                box-sizing: border-box;

            }

        }


        @media (max-width: 500px) {

            .dashboard-stats {

                grid-template-columns: 1fr;

            }

            .dashboard-actions {

                grid-template-columns: 1fr;

            }

            .dashboard-list-item {

                align-items: flex-start;

            }

        }

    </style>

</head>


<body>


<?php

require_once DIR . '/includes/navbar.php';

?>


<main class="dashboard-page">


    <div class="dashboard-container">


        <!-- =================================================
             HERO
        ================================================== -->

        <section class="dashboard-hero">


            <div class="dashboard-hero-content">

                <div class="dashboard-eyebrow">

                    <?php
                    echo strtoupper(
                        htmlspecialchars($role)
                    );
                    ?>
                    DASHBOARD

                </div>


                <h1>

                    Hey,
                    <span>
                        <?php
                        echo htmlspecialchars(
                            $user['name']
                        );
                        ?>.
                    </span>

                </h1>


                <p>

                    <?php if ($role === 'customer'): ?>

                        Manage your orders, wishlist,
                        shopping cart and reviews from
                        one place.

                    <?php elseif ($role === 'vendor'): ?>

                        Monitor your products, orders,
                        sales and commission from your
                        vendor workspace.

                    <?php else: ?>

                        Keep an eye on users, vendors,
                        products and orders across
                        HochipoHub.

                    <?php endif; ?>

                </p>

            </div>


            <div class="dashboard-role">

                <small>
                    ACCOUNT ROLE
                </small>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $role
                    );
                    ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             VENDOR APPROVAL NOTICE
        ================================================== -->

        <?php if (
            $role === 'vendor' &&
            isset($vendor) &&
            $vendor &&
            $vendor['approval_status'] !== 'Approved'
        ): ?><div class="vendor-notice">

                <strong>

                    Vendor Account:
                    <?php
                    echo htmlspecialchars(
                        $vendor['approval_status']
                    );
                    ?>

                </strong>

                <p>

                    Your vendor account is currently
                    <?php
                    echo strtolower(
                        htmlspecialchars(
                            $vendor['approval_status']
                        )
                    );
                    ?>.
                    Some vendor features may remain
                    unavailable until your application
                    is approved.

                </p>

            </div>

        <?php endif; ?>


        <!-- =================================================
             CUSTOMER STATS
        ================================================== -->

        <?php if ($role === 'customer'): ?>


            <section class="dashboard-stats">


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        #
                    </div>

                    <span class="dashboard-stat-label">
                        Orders
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['orders']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Your purchases
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        🛒
                    </div>

                    <span class="dashboard-stat-label">
                        Cart Items
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['cart']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Ready for checkout
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        ♡
                    </div>

                    <span class="dashboard-stat-label">
                        Wishlist
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['wishlist']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Saved products
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        ★
                    </div>

                    <span class="dashboard-stat-label">
                        Reviews
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['reviews']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Your reviews
                    </span>

                </div>


            </section>


        <?php endif; ?>


        <!-- =================================================
             VENDOR STATS
        ================================================== -->

        <?php if ($role === 'vendor'): ?>


            <section class="dashboard-stats"><div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        ◈
                    </div>

                    <span class="dashboard-stat-label">
                        Products
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['products']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Products in your store
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        #
                    </div>

                    <span class="dashboard-stat-label">
                        Orders
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['orders']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Vendor orders
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        RM
                    </div>

                    <span class="dashboard-stat-label">
                        Sales
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo dashboard_money(
                            $stats['sales']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Total vendor sales
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        %
                    </div>

                    <span class="dashboard-stat-label">
                        Commission
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo dashboard_money(
                            $stats['commission']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Recorded commission
                    </span>

                </div>


            </section>


        <?php endif; ?>


        <!-- =================================================
             ADMIN STATS
        ================================================== -->

        <?php if ($role === 'admin'): ?>


            <section class="dashboard-stats">


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        ◉
                    </div>

                    <span class="dashboard-stat-label">
                        Users
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['users']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Registered accounts
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        ◆
                    </div>

                    <span class="dashboard-stat-label">
                        Vendors
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php echo 
                        number_format(
                            $stats['vendors']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Approved vendors
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        !
                    </div>

                    <span class="dashboard-stat-label">
                        Pending Vendors
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['pending_vendors']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Applications to review
                    </span>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        #
                    </div>

                    <span class="dashboard-stat-label">
                        Pending Orders
                    </span>

                    <strong class="dashboard-stat-value">
                        <?php
                        echo number_format(
                            $stats['pending_orders']
                        );
                        ?>
                    </strong>

                    <span class="dashboard-stat-sub">
                        Orders awaiting action
                    </span>

                </div>


            </section>


        <?php endif; ?>


        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <div class="dashboard-grid">


            <!-- =================================================
                 RECENT ORDERS
            ================================================== -->

            <section class="dashboard-card">


                <div class="dashboard-card-header">

                    <div>

                        <h2>
                            Recent Orders
                        </h2>

                        <span>
                            Latest activity
                        </span>

                    </div>


                    <?php if (
                        $role === 'customer'
                    ): ?>

                        <a
                            href="<?php
                            echo site_url('order.php');
                            ?>"
                            class="dashboard-view-link"
                        >
                            VIEW ALL →
                        </a>

                    <?php elseif (
                        $role === 'vendor'
                    ): ?>

                        <a
                            href="<?php
                            echo site_url(
                                'seller/orders.php'
                            );
                            ?>"
                            class="dashboard-view-link"
                        >
                            VIEW ALL →
                        </a>

                    <?php elseif (
                        $role === 'admin'
                    ): ?>

                        <a
                            href="<?php
                            echo site_url(
                                'admin/orders.php'
                            );
                            ?>"
                            class="dashboard-view-link"
                        >
                            MANAGE →
                        </a>

                    <?php endif; ?>

                </div>


                <div class="dashboard-list">


                    <?php if (
                        empty($recentOrders)
                    ): ?><div class="dashboard-empty">

                            <div class="dashboard-empty-icon">
                                #
                            </div>

                            <h3>
                                No orders yet
                            </h3>

                            <p>
                                Order activity will appear
                                here when there is something
                                to display.
                            </p>

                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>


                            <div class="dashboard-list-item">


                                <div
                                    class="
                                        dashboard-item-left
                                    "
                                >

                                    <span
                                        class="
                                            dashboard-item-title
                                        "
                                    >

                                        Order #

                                        <?php
                                        echo (int)
                                            $order['order_id'];
                                        ?>

                                        <?php if (
                                            isset(
                                                $order[
                                                    'customer_name'
                                                ]
                                            )
                                        ): ?>

                                            ·

                                            <?php
                                            echo htmlspecialchars(
                                                $order[
                                                    'customer_name'
                                                ]
                                            );
                                            ?>

                                        <?php endif; ?>

                                    </span>


                                    <span
                                        class="
                                            dashboard-item-meta
                                        "
                                    >

                                        <?php
                                        echo dashboard_date(
                                            $order[
                                                'order_date'
                                            ] ??
                                            $order[
                                                'created_at'
                                            ] ??
                                            null
                                        );
                                        ?>

                                    </span>


                                    <?php
                                    $orderStatus =
                                        $order[
                                            'order_status'
                                        ] ??
                                        $order[
                                            'vendor_status'
                                        ] ??
                                        'Pending';
                                    ?>


                                    <span
                                        class="
                                            dashboard-status
                                            <?php
                                            echo dashboard_status_class($orderStatus
                                            );
                                            ?>
                                        "
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $orderStatus
                                        );
                                        ?>

                                    </span>

                                </div>


                                <div
                                    class="
                                        dashboard-item-right
                                    "
                                >

                                    <span
                                        class="
                                            dashboard-item-amount
                                        "
                                    >

                                        <?php

                                        if (
                                            $role === 'vendor'
                                        ) {

                                            echo dashboard_money(
                                                $order[
                                                    'subtotal'
                                                ] ?? 0
                                            );

                                        } else {

                                            echo dashboard_money(
                                                $order[
                                                    'total_amount'
                                                ] ?? 0
                                            );

                                        }

                                        ?>

                                    </span>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


            </section>


            <!-- =================================================
                 QUICK ACTIONS
            ================================================== -->

            <section class="dashboard-card">


                <div class="dashboard-card-header">

                    <div>

                        <h2>
                            Quick Actions
                        </h2>

                        <span>
                            Jump right in
                        </span>

                    </div>

                </div>


                <div class="dashboard-actions">


                    <?php if (
                        $role === 'customer'
                    ): ?>


                        <a
                            href="<?php
                            echo site_url(
                                'catalog.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ◈
                            </span>

                            Shop Products

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'cart.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                🛒</span>

                            My Cart

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'wishlist.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ♡
                            </span>

                            Wishlist

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'profile.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ◉
                            </span>

                            My Profile

                        </a>


                    <?php elseif (
                        $role === 'vendor'
                    ): ?>


                        <a
                            href="<?php
                            echo site_url(
                                'seller/add_product.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                +
                            </span>

                            Add Product

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'seller/products.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ◈
                            </span>

                            My Products

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'seller/orders.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                #
                            </span>

                            Orders

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'commission.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                %
                            </span>

                            Commission

                        </a>


                    <?php else: ?><a
                            href="<?php
                            echo site_url(
                                'admin/users.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ◉
                            </span>

                            Users

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'admin/vendors.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ◆
                            </span>

                            Vendors

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'admin/products.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                ◈
                            </span>

                            Products

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'admin/orders.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                #
                            </span>

                            Orders

                        </a>


                    <?php endif; ?>


                </div>


            </section>


            <!-- =================================================
                 PRODUCTS
            ================================================== -->

            <?php if (
                $role === 'vendor' ||
                $role === 'admin'
            ): ?>


                <section class="dashboard-card">


                    <div class="dashboard-card-header">

                        <div>

                            <h2>
                                Recent Products
                            </h2>

                            <span>
                                Latest catalogue activity
                            </span>

                        </div>


                        <?php if (
                            $role === 'vendor'
                        ): ?>

                            <a
                                href="<?php
                                echo site_url(
                                    'seller/products.php'
                                );
                                ?>"
                                class="dashboard-view-link"
                            >
                                PRODUCTS →
                            </a>

                        <?php else: ?>

                            <a
                                href="<?php
                                echo site_url(
                                    'admin/products.php');
                                ?>"
                                class="dashboard-view-link"
                            >
                                MANAGE →
                            </a>

                        <?php endif; ?>

                    </div>


                    <?php if (
                        empty($recentProducts)
                    ): ?>


                        <div class="dashboard-empty">

                            <div class="dashboard-empty-icon">
                                ◈
                            </div>

                            <h3>
                                No products yet
                            </h3>

                            <p>
                                Product activity will appear
                                here once products are added.
                            </p>

                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $recentProducts
                            as $product
                        ): ?>


                            <div
                                class="
                                    dashboard-product
                                "
                            >


                                <div
                                    class="
                                        dashboard-product-image
                                    "
                                >

                                    <?php

                                    $productImage =
                                        $product['image']
                                        ?? '';

                                    if (
                                        $productImage !== ''
                                    ):

                                        $imagePath =
                                            site_url(
                                                'image/product/'
                                                . $productImage
                                            );

                                    ?>

                                        <img
                                            src="<?php
                                            echo htmlspecialchars(
                                                $imagePath
                                            );
                                            ?>"
                                            alt="<?php
                                            echo htmlspecialchars(
                                                $product[
                                                    'product_name'
                                                ]
                                            );
                                            ?>"
                                            onerror="
                                                this.style.display='none';
                                                this.nextElementSibling.style.display='flex';
                                            "
                                        >

                                    <?php endif; ?>


                                    <span
                                        class="
                                            dashboard-product-placeholder
                                        "
                                        style="
                                            <?php
                                            echo $productImage !== ''
                                                ? 'display:none;'
                                                : '';
                                            ?>
                                        "
                                    >
                                        ◈
                                    </span>

                                </div>


                                <divclass="
                                        dashboard-product-info
                                    "
                                >

                                    <span
                                        class="
                                            dashboard-product-name
                                        "
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $product[
                                                'product_name'
                                            ]
                                        );
                                        ?>

                                    </span>


                                    <span
                                        class="
                                            dashboard-product-meta
                                        "
                                    >

                                        <?php

                                        if (
                                            $role === 'vendor'
                                        ) {

                                            echo
                                                'Stock: '
                                                .
                                                number_format(
                                                    (int)
                                                    $product[
                                                        'stock_quantity'
                                                    ]
                                                );

                                        } else {

                                            echo htmlspecialchars(
                                                $product[
                                                    'business_name'
                                                ]
                                            );

                                        }

                                        ?>

                                    </span>

                                </div>


                                <span
                                    class="
                                        dashboard-product-price
                                    "
                                >

                                    <?php
                                    echo dashboard_money(
                                        $product[
                                            'price'
                                        ]
                                    );
                                    ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </section>


            <?php endif; ?>


            <!-- =================================================
                 CUSTOMER PROFILE CARD
            ================================================== -->

            <?php if (
                $role === 'customer'
            ): ?>


                <section class="dashboard-card">


                    <div class="dashboard-card-header">

                        <div>

                            <h2>
                                Account Overview
                            </h2>

                            <span>
                                Your HochipoHub profile
                            </span>

                        </div>


                        <a
                            href="<?php
                            echo site_url(
                                'profile.php'
                            );
                            ?>"
                            class="dashboard-view-link"
                        >
                            EDIT →
                        </a></div>


                    <div
                        style="
                            padding: 20px;
                        "
                    >


                        <div
                            style="
                                display:flex;
                                align-items:center;
                                gap:14px;
                                margin-bottom:18px;
                            "
                        >

                            <div
                                style="
                                    width:50px;
                                    height:50px;
                                    border-radius:15px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    background:rgba(14,165,233,.10);
                                    color:#38bdf8;
                                    font-weight:950;
                                    font-size:18px;
                                    overflow:hidden;
                                "
                            >

                                <?php

                                $profileImage =
                                    $user[
                                        'profile_image'
                                    ] ?? '';

                                if (
                                    $profileImage !== ''
                                ):

                                ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            site_url(
                                                'image/'
                                                . $profileImage
                                            )
                                        );
                                        ?>"
                                        alt="Profile"
                                        style="
                                            width:100%;
                                            height:100%;
                                            object-fit:cover;
                                        "
                                    >

                                <?php else: ?>

                                    <?php
                                    echo strtoupper(
                                        substr(
                                            $user['name'],
                                            0,
                                            1
                                        )
                                    );
                                    ?>

                                <?php endif; ?>

                            </div>


                            <div>

                                <strong
                                    style="
                                        display:block;
                                        color:#e2e8f0;
                                        font-size:13px;
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $user['name']
                                    );
                                    ?>

                                </strong>


                                <span
                                    style="
                                        display:block;
                                        margin-top:4px;
                                        color:#475569;
                                        font-size:9px;
                                    "
                                >

                                    <?php echo htmlspecialchars(
                                        $user['email']
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>


                        <div
                            style="
                                display:grid;
                                grid-template-columns:1fr 1fr;
                                gap:9px;
                            "
                        >

                            <div
                                style="
                                    padding:12px;
                                    border:1px solid rgba(148,163,184,.07);
                                    border-radius:12px;
                                    background:rgba(2,6,23,.3);
                                "
                            >

                                <span
                                    style="
                                        display:block;
                                        color:#475569;
                                        font-size:8px;
                                        font-weight:900;
                                        text-transform:uppercase;
                                    "
                                >
                                    Phone
                                </span>

                                <strong
                                    style="
                                        display:block;
                                        margin-top:5px;
                                        color:#94a3b8;
                                        font-size:9px;
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $user['phone']
                                        ?: 'Not provided'
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div
                                style="
                                    padding:12px;
                                    border:1px solid rgba(148,163,184,.07);
                                    border-radius:12px;
                                    background:rgba(2,6,23,.3);
                                "
                            >

                                <span
                                    style="
                                        display:block;
                                        color:#475569;
                                        font-size:8px;
                                        font-weight:900;
                                        text-transform:uppercase;
                                    "
                                >
                                    Member Since
                                </span>

                                <strong
                                    style="
                                        display:block;
                                        margin-top:5px;
                                        color:#94a3b8;
                                        font-size:9px;
                                    "
                                >

                                    <?php
                                    echo dashboard_date(
                                        $user['created_at']
                                    );
                                    ?>

                                </strong>

                            </div>

                        </div>


                    </div>


                </section>


            <?php endif; ?>


            <!-- =================================================
                 ADMIN ALERTS================================================== -->

            <?php if (
                $role === 'admin'
            ): ?>


                <section class="dashboard-card">


                    <div class="dashboard-card-header">

                        <div>

                            <h2>
                                Admin Attention
                            </h2>

                            <span>
                                Items requiring review
                            </span>

                        </div>

                    </div>


                    <div class="dashboard-actions">


                        <a
                            href="<?php
                            echo site_url(
                                'admin/vendors.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                <?php
                                echo number_format(
                                    $stats[
                                        'pending_vendors'
                                    ]
                                );
                                ?>
                            </span>

                            Vendor Applications

                        </a>


                        <a
                            href="<?php
                            echo site_url(
                                'admin/orders.php'
                            );
                            ?>"
                            class="dashboard-action"
                        >

                            <span
                                class="
                                    dashboard-action-icon
                                "
                            >
                                <?php
                                echo number_format(
                                    $stats[
                                        'pending_orders'
                                    ]
                                );
                                ?>
                            </span>

                            Pending Orders

                        </a>


                    </div>


                </section>


            <?php endif; ?>


        </div>


    </div>


</main>


<?php

require_once DIR . '/includes/footer.php';

?>


</body>

</html>