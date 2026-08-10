<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - DASHBOARD
|--------------------------------------------------------------------------
| Customer / Vendor / Admin Dashboard
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

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


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    session_destroy();

    header('Location: ' . site_url('index.php'));
    exit;
}


/*
|--------------------------------------------------------------------------
| ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

if ($user['status'] !== 'active') {

    session_destroy();

    header('Location: ' . site_url('index.php?account=inactive'));
    exit;
}


$role = $user['role'];


/*
|--------------------------------------------------------------------------
| DEFAULT DATA
|--------------------------------------------------------------------------
*/

$stats = [

    // Customer
    'orders' => 0,
    'cart' => 0,
    'wishlist' => 0,
    'reviews' => 0,

    // Vendor
    'products' => 0,
    'stock' => 0,
    'sales' => 0,
    'commission' => 0,

    // Admin
    'users' => 0,
    'vendors' => 0,
    'pending_vendors' => 0,
    'pending_orders' => 0
];


$recentOrders = [];
$recentProducts = [];
$recentCommissions = [];

$vendor = null;
$vendorId = 0;


/*
|--------------------------------------------------------------------------
| CUSTOMER DASHBOARD
|--------------------------------------------------------------------------
*/

if ($role === 'customer') {


    /*
    |--------------------------------------------------------------------------
    | TOTAL ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE customer_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $stats['orders'] = (int) (
        $result->fetch_assoc()['total'] ?? 0
    );

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | CART ITEMS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart
        WHERE customer_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $stats['cart'] = (int) (
        $result->fetch_assoc()['total'] ?? 0
    );

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | WISHLIST
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $stats['wishlist'] = (int) (
        $result->fetch_assoc()['total'] ?? 0
    );

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | REVIEWS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM reviews
        WHERE customer_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $stats['reviews'] = (int) (
        $result->fetch_assoc()['total'] ?? 0
    );

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | RECENT CUSTOMER ORDERS
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
    ");

    $stmt->bind_param("i", $userId);
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
    | GET VENDOR INFORMATION
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
        | TOTAL PRODUCTS
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

        $stats['products'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | TOTAL STOCK
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

        $stats['stock'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | TOTAL SALES
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

        $stats['sales'] = (float) (
            $result->fetch_assoc()['total'] ?? 0
        );

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | TOTAL COMMISSION
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

        $stats['commission'] = (float) (
            $result->fetch_assoc()['total'] ?? 0
        );

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
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

        $stats['orders'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );

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
    | TOTAL USERS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
    ");

    if ($result) {

        $stats['users'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVED VENDORS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM vendors
        WHERE approval_status = 'Approved'
    ");

    if ($result) {

        $stats['vendors'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );
    }


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

    if ($result) {

        $stats['pending_vendors'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );
    }


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

    if ($result) {

        $stats['pending_orders'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL PRODUCTS
    |--------------------------------------------------------------------------
    */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
    ");

    if ($result) {

        $stats['products'] = (int) (
            $result->fetch_assoc()['total'] ?? 0
        );
    }


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

    if ($result) {

        $stats['sales'] = (float) (
            $result->fetch_assoc()['total'] ?? 0
        );
    }


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

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $recentOrders[] = $row;
        }
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

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $recentProducts[] = $row;
        }
    }
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
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

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


function dashboard_status_class($status)
{
    $status = strtolower(
        trim(
            str_replace(
                ' ',
                '-',
                (string) $status
            )
        )
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


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >


    <!-- Dashboard CSS -->

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/dashboard.css'); ?>"
    >


    <!-- Responsive CSS -->

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >

</head>


<body>


<?php

require_once __DIR__ . '/includes/navbar.php';

?>


<main class="dashboard-page">


    <div class="dashboard-container">


        <!-- ==================================================
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
                        ?>

                    </span>.

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



        <!-- ==================================================
             VENDOR APPROVAL NOTICE
        ================================================== -->

        <?php if (
            $role === 'vendor' &&
            $vendor &&
            $vendor['approval_status'] !== 'Approved'
        ): ?>


            <div class="vendor-notice">

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



        <!-- ==================================================
             CUSTOMER STATS
        ================================================== -->

        <?php if ($role === 'customer'): ?>


            <section class="dashboard-stats">


                <!-- Orders -->

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


                <!-- Cart -->

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


                <!-- Wishlist -->

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


                <!-- Reviews -->

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



        <!-- ==================================================
             VENDOR STATS
        ================================================== -->

        <?php if ($role === 'vendor'): ?>


            <section class="dashboard-stats">


                <!-- Products -->

                <div class="dashboard-stat">

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


                <!-- Orders -->

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


                <!-- Sales -->

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


                <!-- Commission -->

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



        <!-- ==================================================
             ADMIN STATS
        ================================================== -->

        <?php if ($role === 'admin'): ?>


            <section class="dashboard-stats">


                <!-- Users -->

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


                <!-- Vendors -->

                <div class="dashboard-stat">

                    <div class="dashboard-stat-icon">
                        ◇
                    </div>

                    <span class="dashboard-stat-label">
                        Vendors
                    </span>

                    <strong class="dashboard-stat-value">

                        <?php
                        echo number_format(
                            $stats['vendors']
                        );
                        ?>

                    </strong>

                    <span class="dashboard-stat-sub">
                        Approved vendors
                    </span>

                </div>


                <!-- Pending Vendors -->

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


                <!-- Pending Orders -->

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



        <!-- ==================================================
             MAIN GRID
        ================================================== -->

        <div class="dashboard-grid">



            <!-- ==================================================
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


                    <?php if ($role === 'customer'): ?>

                        <a
                            href="<?php echo site_url('order.php'); ?>"
                            class="dashboard-view-link"
                        >
                            VIEW ALL →
                        </a>


                    <?php elseif ($role === 'vendor'): ?>

                        <a
                            href="<?php echo site_url('seller/orders.php'); ?>"
                            class="dashboard-view-link"
                        >
                            VIEW ALL →
                        </a>


                    <?php else: ?>

                        <a
                            href="<?php echo site_url('admin/orders.php'); ?>"
                            class="dashboard-view-link"
                        >
                            MANAGE →
                        </a>

                    <?php endif; ?>


                </div>



                <div class="dashboard-list">


                    <?php if (empty($recentOrders)): ?>


                        <div class="dashboard-empty">

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
                            $recentOrders as $order
                        ): ?>


                            <div class="dashboard-list-item">


                                <div class="dashboard-item-left">


                                    <span
                                        class="dashboard-item-title"
                                    >

                                        Order #

                                        <?php
                                        echo (int)
                                            $order['order_id'];
                                        ?>


                                        <?php if (
                                            isset(
                                                $order['customer_name']
                                            )
                                        ): ?>

                                            ·

                                            <?php
                                            echo htmlspecialchars(
                                                $order['customer_name']
                                            );
                                            ?>

                                        <?php endif; ?>

                                    </span>


                                    <span
                                        class="dashboard-item-meta"
                                    >

                                        <?php
                                        echo dashboard_date(
                                            $order['order_date']
                                            ??
                                            $order['created_at']
                                            ??
                                            null
                                        );
                                        ?>

                                    </span>


                                    <?php

                                    $orderStatus =
                                        $order['order_status']
                                        ??
                                        $order['vendor_status']
                                        ??
                                        'Pending';

                                    ?>


                                    <span
                                        class="
                                            dashboard-status
                                            <?php
                                            echo dashboard_status_class(
                                                $orderStatus
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


                                <div class="dashboard-item-right">


                                    <span
                                        class="dashboard-item-amount"
                                    >

                                        <?php

                                        if ($role === 'vendor') {

                                            echo dashboard_money(
                                                $order['subtotal'] ?? 0
                                            );

                                        } else {

                                            echo dashboard_money(
                                                $order['total_amount'] ?? 0
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



            <!-- ==================================================
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


                    <?php if ($role === 'customer'): ?>


                        <a
                            href="<?php echo site_url('catalog.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ◈
                            </span>

                            Shop Products

                        </a>


                        <a
                            href="<?php echo site_url('cart.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                🛒
                            </span>

                            My Cart

                        </a>


                        <a
                            href="<?php echo site_url('wishlist.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ♡
                            </span>

                            Wishlist

                        </a>


                        <a
                            href="<?php echo site_url('profile.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ◉
                            </span>

                            My Profile

                        </a>


                    <?php elseif ($role === 'vendor'): ?>


                        <a
                            href="<?php echo site_url('seller/add_product.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                +
                            </span>

                            Add Product

                        </a>


                        <a
                            href="<?php echo site_url('seller/products.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ◈
                            </span>

                            My Products

                        </a>


                        <a
                            href="<?php echo site_url('seller/orders.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                #
                            </span>

                            Orders

                        </a>


                        <a
                            href="<?php echo site_url('commission.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                %
                            </span>

                            Commission

                        </a>


                    <?php else: ?>


                        <a
                            href="<?php echo site_url('admin/users.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ◉
                            </span>

                            Users

                        </a>


                        <a
                            href="<?php echo site_url('admin/vendors.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ◇
                            </span>

                            Vendors

                        </a>


                        <a
                            href="<?php echo site_url('admin/products.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                ◈
                            </span>

                            Products

                        </a>


                        <a
                            href="<?php echo site_url('admin/orders.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">
                                #
                            </span>

                            Orders

                        </a>


                    <?php endif; ?>


                </div>


            </section>



            <!-- ==================================================
                 RECENT PRODUCTS
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


                        <?php if ($role === 'vendor'): ?>

                            <a
                                href="<?php echo site_url('seller/products.php'); ?>"
                                class="dashboard-view-link"
                            >
                                PRODUCTS →
                            </a>

                        <?php else: ?>

                            <a
                                href="<?php echo site_url('admin/products.php'); ?>"
                                class="dashboard-view-link"
                            >
                                MANAGE →
                            </a>

                        <?php endif; ?>


                    </div>



                    <?php if (empty($recentProducts)): ?>


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
                            $recentProducts as $product
                        ): ?>


                            <div class="dashboard-product">


                                <div
                                    class="dashboard-product-image"
                                >


                                    <?php

                                    $productImage =
                                        $product['image'] ?? '';

                                    ?>


                                    <?php if (
                                        $productImage !== ''
                                    ): ?>


                                        <img
                                            src="<?php
                                            echo htmlspecialchars(
                                                site_url(
                                                    'image/product/' .
                                                    $productImage
                                                )
                                            );
                                            ?>"
                                            alt="<?php
                                            echo htmlspecialchars(
                                                $product['product_name']
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
                                        style="<?php
                                        echo $productImage !== ''
                                            ? 'display:none;'
                                            : '';
                                        ?>"
                                    >
                                        ◈
                                    </span>


                                </div>



                                <div class="dashboard-product-info">


                                    <span
                                        class="dashboard-product-name"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $product['product_name']
                                        );
                                        ?>

                                    </span>


                                    <span
                                        class="dashboard-product-meta"
                                    >

                                        <?php if (
                                            $role === 'vendor'
                                        ): ?>

                                            Stock:

                                            <?php
                                            echo number_format(
                                                (int)
                                                $product[
                                                    'stock_quantity'
                                                ]
                                            );
                                            ?>

                                        <?php else: ?>

                                            <?php
                                            echo htmlspecialchars(
                                                $product[
                                                    'business_name'
                                                ]
                                            );
                                            ?>

                                        <?php endif; ?>

                                    </span>


                                </div>



                                <span
                                    class="dashboard-product-price"
                                >

                                    <?php
                                    echo dashboard_money(
                                        $product['price']
                                    );
                                    ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </section>


            <?php endif; ?>



            <!-- ==================================================
                 CUSTOMER ACCOUNT OVERVIEW
            ================================================== -->

            <?php if ($role === 'customer'): ?>


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
                            href="<?php echo site_url('profile.php'); ?>"
                            class="dashboard-view-link"
                        >
                            EDIT →
                        </a>


                    </div>



                    <div class="dashboard-account-content">


                        <div class="dashboard-profile-row">


                            <div class="dashboard-profile-image">


                                <?php

                                $profileImage =
                                    $user['profile_image'] ?? '';

                                ?>


                                <?php if (
                                    $profileImage !== ''
                                ): ?>


                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            site_url(
                                                'image/' .
                                                $profileImage
                                            )
                                        );
                                        ?>"
                                        alt="Profile"
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


                                <strong class="dashboard-profile-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $user['name']
                                    );
                                    ?>

                                </strong>


                                <span class="dashboard-profile-email">

                                    <?php
                                    echo htmlspecialchars(
                                        $user['email']
                                    );
                                    ?>

                                </span>


                            </div>


                        </div>



                        <div class="dashboard-profile-details">


                            <div class="dashboard-profile-detail">


                                <span>
                                    Phone
                                </span>


                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $user['phone']
                                        ?: 'Not provided'
                                    );
                                    ?>

                                </strong>


                            </div>



                            <div class="dashboard-profile-detail">


                                <span>
                                    Member Since
                                </span>


                                <strong>

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



            <!-- ==================================================
                 ADMIN ATTENTION
            ================================================== -->

            <?php if ($role === 'admin'): ?>


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
                            href="<?php echo site_url('admin/vendors.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">

                                <?php
                                echo number_format(
                                    $stats['pending_vendors']
                                );
                                ?>

                            </span>

                            Vendor Applications

                        </a>



                        <a
                            href="<?php echo site_url('admin/orders.php'); ?>"
                            class="dashboard-action"
                        >

                            <span class="dashboard-action-icon">

                                <?php
                                echo number_format(
                                    $stats['pending_orders']
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

require_once __DIR__ . '/includes/footer.php';

?>


</body>

</html>