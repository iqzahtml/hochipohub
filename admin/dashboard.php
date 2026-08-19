<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN DASHBOARD
|--------------------------------------------------------------------------
| File:
|     admin/dashboard.php
|
| Purpose:
|     Admin dashboard
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
| ADMIN ACCESS ONLY
|--------------------------------------------------------------------------
|
| config.php already provides requireAdmin().
|
| It checks:
|     1. User is logged in
|     2. $_SESSION['role'] === 'admin'
|
|--------------------------------------------------------------------------
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminName =
    $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'Administrator';

$adminEmail =
    $_SESSION['user_email']
    ?? $_SESSION['email']
    ?? '';


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$totalVendors = 0;
$totalProducts = 0;
$totalOrders = 0;
$totalRevenue = 0;
$pendingVendors = 0;
$pendingOrders = 0;
$pendingPayments = 0;


/*
|--------------------------------------------------------------------------
| TOTAL USERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM users
        WHERE role = 'customer'
    ");

    $totalUsers = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Users Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL VENDORS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM vendors
        WHERE approval_status = 'Approved'
    ");

    $totalVendors = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Vendors Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM products
    ");

    $totalProducts = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Products Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL ORDERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
    ");

    $totalOrders = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Orders Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL REVENUE
|--------------------------------------------------------------------------
|
| Only count completed/paid orders.
|
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM orders
        WHERE order_status = 'Completed'
    ");

    $totalRevenue =
        (float) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Revenue Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| PENDING VENDOR APPLICATIONS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM vendor_applications
        WHERE status = 'Pending'
    ");

    $pendingVendors =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Pending Vendors Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| PENDING ORDERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'Pending'
    ");

    $pendingOrders =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Pending Orders Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| PENDING PAYMENTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
        WHERE payment_status = 'Pending'
    ");

    $pendingPayments =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Pending Payments Error: '
        . $e->getMessage()
    );
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
        ORDER BY o.order_date DESC
        LIMIT 5
    ");

    $recentOrders =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Recent Orders Error: '
        . $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| RECENT VENDOR APPLICATIONS
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
        ORDER BY va.created_at DESC
        LIMIT 5
    ");

    $recentApplications =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        'Admin Dashboard - Vendor Applications Error: '
        . $e->getMessage()
    );
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
        Admin Dashboard | <?php echo e(SITE_NAME); ?>
    </title>


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>css/admin.css"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

</head>


<body class="admin-body">


<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<?php

require_once dirname(__DIR__) . '/includes/admin_sidebar.php';

?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="admin-main">


    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <header class="admin-header">

        <div class="admin-header-left">

            <button
                type="button"
                id="adminSidebarToggle"
                class="admin-sidebar-toggle"
                aria-label="Open Sidebar"
            >

                <i class="fa-solid fa-bars"></i>

            </button>


            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome back,
                    <?php echo e($adminName); ?>.
                </p>

            </div>

        </div>


        <div class="admin-header-right">

            <div class="admin-header-user">

                <i class="fa-solid fa-user-shield"></i>

                <span>
                    <?php echo e($adminName); ?>
                </span>

            </div>

        </div>

    </header>


    <!-- =====================================================
         DASHBOARD CONTENT
    ====================================================== -->

    <section class="admin-content">


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="admin-stats-grid">


            <!-- Users -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div class="admin-stat-info">

                    <span>
                        Total Customers
                    </span>

                    <strong>
                        <?php echo number_format($totalUsers); ?>
                    </strong>

                </div>

            </div>


            <!-- Vendors -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-store"></i>

                </div>

                <div class="admin-stat-info">

                    <span>
                        Approved Vendors
                    </span>

                    <strong>
                        <?php echo number_format($totalVendors); ?>
                    </strong>

                </div>

            </div>


            <!-- Products -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-box-open"></i>

                </div>

                <div class="admin-stat-info">

                    <span>
                        Total Products
                    </span>

                    <strong>
                        <?php echo number_format($totalProducts); ?>
                    </strong>

                </div>

            </div>


            <!-- Orders -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-cart-shopping"></i>

                </div>

                <div class="admin-stat-info">

                    <span>
                        Total Orders
                    </span>

                    <strong>
                        <?php echo number_format($totalOrders); ?>
                    </strong>

                </div>

            </div>


            <!-- Revenue -->

            <div class="admin-stat-card">

                <div class="admin-stat-icon">

                    <i class="fa-solid fa-money-bill-trend-up"></i>

                </div>

                <div class="admin-stat-info">

                    <span>
                        Total Revenue
                    </span>

                    <strong>
                        RM <?php echo number_format($totalRevenue, 2); ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <div class="admin-section">

            <div class="admin-section-header">

                <div>

                    <h2>
                        Quick Overview
                    </h2>

                    <p>
                        Items that may require your attention.
                    </p>

                </div>

            </div>


            <div class="admin-overview-grid">


                <!-- Pending Vendors -->

                <a
                    href="<?php echo BASE_URL; ?>admin/vendors.php"
                    class="admin-overview-card"
                >

                    <div class="admin-overview-icon">

                        <i class="fa-solid fa-store"></i>

                    </div>

                    <div>

                        <span>
                            Pending Vendors
                        </span>

                        <strong>
                            <?php echo number_format($pendingVendors); ?>
                        </strong>

                    </div>

                </a>


                <!-- Pending Orders -->

                <a
                    href="<?php echo BASE_URL; ?>admin/orders.php"
                    class="admin-overview-card"
                >

                    <div class="admin-overview-icon">

                        <i class="fa-solid fa-clock"></i>

                    </div>

                    <div>

                        <span>
                            Pending Orders
                        </span>

                        <strong>
                            <?php echo number_format($pendingOrders); ?>
                        </strong>

                    </div>

                </a>


                <!-- Pending Payments -->

                <a
                    href="<?php echo BASE_URL; ?>admin/payments.php"
                    class="admin-overview-card"
                >

                    <div class="admin-overview-icon">

                        <i class="fa-solid fa-credit-card"></i>

                    </div>

                    <div>

                        <span>
                            Pending Payments
                        </span>

                        <strong>
                            <?php echo number_format($pendingPayments); ?>
                        </strong>

                    </div>

                </a>

            </div>

        </div>


        <!-- =================================================
             RECENT ORDERS
        ================================================== -->

        <div class="admin-section">

            <div class="admin-section-header">

                <div>

                    <h2>
                        Recent Orders
                    </h2>

                    <p>
                        Latest customer orders.
                    </p>

                </div>


                <a
                    href="<?php echo BASE_URL; ?>admin/orders.php"
                    class="admin-view-all"
                >
                    View All
                </a>

            </div>


            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>

                            <th>
                                Order ID
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($recentOrders)): ?>

                        <tr>

                            <td
                                colspan="5"
                                class="admin-empty"
                            >
                                No orders found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>

                                <td>

                                    #
                                    <?php
                                    echo (int) $order['order_id'];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $order['customer_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $order['order_date']
                                            )
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    RM
                                    <?php
                                    echo number_format(
                                        (float) $order['total_amount'],
                                        2
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="admin-status
                                        status-<?php
                                            echo strtolower(
                                                str_replace(
                                                    ' ',
                                                    '-',
                                                    $order['order_status']
                                                )
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo e(
                                            $order['order_status']
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =================================================
             RECENT VENDOR APPLICATIONS
        ================================================== -->

        <div class="admin-section">

            <div class="admin-section-header">

                <div>

                    <h2>
                        Vendor Applications
                    </h2>

                    <p>
                        Latest vendor applications.
                    </p>

                </div>


                <a
                    href="<?php echo BASE_URL; ?>admin/vendors.php"
                    class="admin-view-all"
                >
                    View All
                </a>

            </div>


            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>

                            <th>
                                Applicant
                            </th>

                            <th>
                                Business
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($recentApplications)): ?>

                        <tr>

                            <td
                                colspan="4"
                                class="admin-empty"
                            >
                                No vendor applications found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $recentApplications
                            as $application
                        ): ?>

                            <tr>

                                <td>

                                    <?php
                                    echo e(
                                        $application['applicant_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $application['business_name']
                                        ?? '-'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $application['created_at']
                                            )
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="admin-status
                                        status-<?php
                                            echo strtolower(
                                                $application['status']
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo e(
                                            $application['status']
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </section>

</main>


</body>

</html>