<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();
$pdo = getDB();

$adminName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Administrator';
$adminEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';

$totalUsers = $totalVendors = $totalProducts = $totalOrders = $pendingVendors = $pendingOrders = $pendingPayments = 0;
$totalRevenue=0.0;

$queries = [
    'totalUsers' => ["SELECT COUNT(*) FROM users WHERE role='customer'", 'int'],
    'totalVendors' => ["SELECT COUNT(*) FROM vendors WHERE approval_status='Approved'", 'int'],
    'totalProducts' => ["SELECT COUNT(*) FROM products", 'int'],
    'totalOrders' => ["SELECT COUNT(*) FROM orders", 'int'],
    'totalRevenue' => ["SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status='Completed'", 'float'],
    'pendingVendors' => ["SELECT COUNT(*) FROM vendor_applications WHERE status='Pending'", 'int'],
    'pendingOrders' => ["SELECT COUNT(*) FROM orders WHERE order_status='Pending'", 'int'],
    'pendingPayments' => ["SELECT COUNT(*) FROM payments WHERE payment_status='Pending'", 'int']
];
foreach ($queries as $key => $q) {
    try {
        $value = $pdo->query($q[0])->fetchColumn();
        $GLOBALS[$key] = $q[1] === 'float' ? (float) $value : (int) $value;
    } catch (PDOException $e) {
        error_log('Admin Dashboard ' . $key . ' Error: ' . $e->getMessage());
    }
}

$recentOrders = [];
try {
    $statement = $pdo->query("SELECT o.order_id,o.order_date,o.total_amount,o.order_status,u.name AS customer_name FROM orders o INNER JOIN users u ON o.customer_id=u.user_id ORDER BY o.order_date DESC LIMIT 5");
    $recentOrders = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Admin Dashboard Orders Error: ' . $e->getMessage());
}

$recentApplications = [];
try {
    $statement = $pdo->query("SELECT va.application_id,va.business_name,va.status,va.created_at,u.name AS applicant_name FROM vendor_applications va INNER JOIN users u ON va.user_id=u.user_id ORDER BY va.created_at DESC LIMIT 5");
    $recentApplications = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Admin Dashboard Applications Error: ' . $e->getMessage());
}

function dashboardStatusClass($status)
{
    return 'status-' . strtolower(str_replace(' ', '-', trim((string) $status)));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css">
</head>
<body class="admin-body">
    <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header-left">
                <button type="button" id="adminSidebarToggle" class="admin-sidebar-toggle" aria-label="Open sidebar" aria-expanded="false">☰</button>
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?= e($adminName) ?>.</p>
                </div>
            </div>
            <div class="admin-header-user"><span>Administrator</span><strong><?= e($adminName) ?></strong></div>
        </header>

        <section class="admin-content">
            <div class="admin-stats-grid">
                <?php foreach ([
                    ['Total Customers', $totalUsers, 'users'],
                    ['Approved Vendors', $totalVendors, 'store'],
                    ['Total Products', $totalProducts, 'box'],
                    ['Total Orders', $totalOrders, 'orders'],
                    ['Total Revenue', 'RM ' . number_format($totalRevenue, 2), 'card']
                ] as $stat): ?>
                    <div class="admin-stat-card"><div class="admin-stat-icon"><span aria-hidden="true"><?= adminSidebarIcon($stat[2]) ?></span></div><div class="admin-stat-info"><span><?= e($stat[0]) ?></span><strong><?= is_numeric($stat[1]) ? number_format($stat[1]) : e($stat[1]) ?></strong></div></div>
                <?php endforeach; ?>
            </div>

            <div class="admin-section">
                <div class="admin-section-header"><div><h2>Quick Overview</h2><p>Items that may require your attention.</p></div></div>
                <div class="admin-overview-grid">
                    <a href="<?= BASE_URL ?>admin/vendors.php" class="admin-overview-card"><div class="admin-overview-icon"><?= adminSidebarIcon('store') ?></div><div><span>Pending Vendors</span><strong><?= number_format($pendingVendors) ?></strong></div></a>
                    <a href="<?= BASE_URL ?>admin/orders.php" class="admin-overview-card"><div class="admin-overview-icon"><?= adminSidebarIcon('orders') ?></div><div><span>Pending Orders</span><strong><?= number_format($pendingOrders) ?></strong></div></a>
                    <a href="<?= BASE_URL ?>admin/payments.php" class="admin-overview-card"><div class="admin-overview-icon"><?= adminSidebarIcon('card') ?></div><div><span>Pending Payments</span><strong><?= number_format($pendingPayments) ?></strong></div></a>
                </div>
            </div>

            <div class="admin-section">
                <div class="admin-section-header"><div><h2>Recent Orders</h2><p>Latest customer orders.</p></div><a class="admin-view-all" href="<?= BASE_URL ?>admin/orders.php">View All</a></div>
                <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
                <?php if (!$recentOrders): ?><tr><td colspan="5" class="admin-empty">No orders found.</td></tr>
                <?php else: foreach ($recentOrders as $order): ?><tr>
                    <td>#<?= (int) $order['order_id'] ?></td><td><?= e($order['customer_name']) ?></td>
                    <td><?= e(date('d M Y, h:i A', strtotime($order['order_date']))) ?></td>
                    <td>RM <?= number_format((float) $order['total_amount'], 2) ?></td>
                    <td><span class="admin-status <?= dashboardStatusClass($order['order_status']) ?>"><?= e($order['order_status']) ?></span></td>
                </tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>

            <div class="admin-section">
                <div class="admin-section-header"><div><h2>Vendor Applications</h2><p>Latest vendor applications.</p></div><a class="admin-view-all" href="<?= BASE_URL ?>admin/vendors.php">View All</a></div>
                <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Applicant</th><th>Business</th><th>Date</th><th>Status</th></tr></thead><tbody>
                <?php if (!$recentApplications): ?><tr><td colspan="4" class="admin-empty">No vendor applications found.</td></tr>
                <?php else: foreach ($recentApplications as $application): ?><tr>
                    <td><?= e($application['applicant_name']) ?></td><td><?= e($application['business_name'] ?? '-') ?></td>
                    <td><?= e(date('d M Y, h:i A', strtotime($application['created_at']))) ?></td>
                    <td><span class="admin-status <?= dashboardStatusClass($application['status']) ?>"><?= e($application['status']) ?></span></td>
                </tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>
        </section>
    </main>
</body>
</html>
