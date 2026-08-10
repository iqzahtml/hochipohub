<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['user_id'])) {
    redirect(baseUrl('index.php'));
}
$userId = (int) $_SESSION['user_id'];
$db = getDB();
/*
|--------------------------------------------------------------------------
| GET CUSTOMER ORDERS
|--------------------------------------------------------------------------
|
| orders
|   ├── order_details
|   ├── payments
|   └── vendor_orders
|
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.delivery_method,
        o.delivery_address,
        o.tracking_number,
        o.order_status,
        o.completed_date,
        p.payment_method,
        p.payment_status,
        COUNT(DISTINCT od.order_detail_id) AS item_count
    FROM orders o
    LEFT JOIN payments p
        ON p.order_id = o.order_id
    LEFT JOIN order_details od
        ON od.order_id = o.order_id
    WHERE o.customer_id = ?
    GROUP BY
        o.order_id,
        o.order_date,
        o.total_amount,
        o.delivery_method,
        o.delivery_address,
        o.tracking_number,
        o.order_status,
        o.completed_date,
        p.payment_method,
        p.payment_status
    ORDER BY o.order_date DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS
|--------------------------------------------------------------------------
*/
function getOrderItems(PDO $db, int $orderId): array
{
    $stmt = $db->prepare("
        SELECT
            od.order_detail_id,
            od.product_id,
            od.quantity,
            od.unit_price,
            od.subtotal,
            p.product_name,
            p.image,
            v.vendor_id,
            v.business_name
        FROM order_details od
        INNER JOIN products p
            ON p.product_id = od.product_id
        INNER JOIN vendors v
            ON v.vendor_id = p.vendor_id
        WHERE od.order_id = ?
        ORDER BY od.order_detail_id ASC
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}
/*
|--------------------------------------------------------------------------
| GET VENDOR ORDERS
|--------------------------------------------------------------------------
*/
function getVendorOrders(PDO $db, int $orderId): array
{
    $stmt = $db->prepare("
        SELECT
            vo.vendor_order_id,
            vo.vendor_id,
            vo.subtotal,
            vo.delivery_fee,
            vo.vendor_status,
            vo.tracking_number,
            vo.created_at,
            vo.completed_at,
            v.business_name,
            v.business_logo
        FROM vendor_orders vo
        INNER JOIN vendors v
            ON v.vendor_id = vo.vendor_id
        WHERE vo.order_id = ?
        ORDER BY vo.vendor_order_id ASC
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}
/*
|--------------------------------------------------------------------------
| PAGE DATA
|--------------------------------------------------------------------------
*/
$pageTitle = 'My Orders';
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
    <?= e($pageTitle) ?> | <?= e(APP_NAME) ?>
</title>
<link
    rel="stylesheet"
    href="<?= assetUrl('css/style.css') ?>"
>
<link
    rel="stylesheet"
    href="<?= assetUrl('css/dashboard.css') ?>"
>
<link
    rel="stylesheet"
    href="<?= assetUrl('css/responsive.css') ?>"
>
<style>
    .orders-page {
        min-height: 100vh;
        padding: 40px 20px 80px;
        background:
            radial-gradient(
                circle at top right,
                rgba(37,99,235,.16),
                transparent 35%
            ),
            #020617;
    }
    .orders-container {
        width: min(1180px, 100%);
        margin: auto;
    }
    .orders-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 35px;
    }
    .orders-header h1 {
        margin: 0;
        color: #fff;
        font-size: 38px;
        font-weight: 800;
        letter-spacing: -1px;
    }
    .orders-header p {
        margin: 8px 0 0;
        color: #94a3b8;
    }
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        border-radius: 14px;
        color: #fff;
        text-decoration: none;
        background: rgba(30,41,59,.8);
        border: 1px solid rgba(96,165,250,.2);
        transition: .2s ease;
    }
    .back-btn:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        background: #1e3a8a;
    }
    .order-card {
        margin-bottom: 24px;
        padding: 25px;
        border-radius: 24px;
        background:
            linear-gradient(
                145deg,
                rgba(15,23,42,.98),
                rgba(15,23,42,.88)
            );
        border: 1px solid rgba(59,130,246,.2);
        box-shadow:
            0 20px 60px rgba(0,0,0,.25);
        transition: .25s ease;
    }
    .order-card:hover {
        border-color: rgba(59,130,246,.55);
        transform: translateY(-3px);
    }
    .order-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(148,163,184,.1);
    }
    .order-number {
        color: #60a5fa;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .order-date {
        margin-top: 6px;
        color: #94a3b8;
        font-size: 14px;
    }
    .order-total {
        color: #fff;
        font-size: 24px;
        font-weight: 800;
        text-align: right;
    }
    .status {
        display: inline-flex;
        padding: 7px 13px;
        margin-top: 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }
    .status-pending {
        color: #fbbf24;
        background: rgba(245,158,11,.12);
    }
    .status-processing {
        color: #60a5fa;
        background: rgba(59,130,246,.12);
    }
    .status-completed {
        color: #34d399;
        background: rgba(16,185,129,.12);
    }
    .status-cancelled {
        color: #f87171;
        background: rgba(239,68,68,.12);
    }
    .order-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin: 20px 0;
    }
    .info-box {
        padding: 15px;
        border-radius: 16px;
        background: rgba(30,41,59,.55);
    }
    .info-label {
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .info-value {
        color: #e2e8f0;
        font-size: 14px;
        font-weight: 700;
    }
    .vendor-section {
        margin-top: 20px;
    }
    .vendor-title {
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        margin-bottom: 12px;
    }
    .vendor-order {
        padding: 16px;
        margin-bottom: 10px;
        border-radius: 16px;
        background: rgba(2,6,23,.55);
        border: 1px solid rgba(148,163,184,.08);
    }
    .vendor-row {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
    }
    .vendor-name {
        color: #e2e8f0;
        font-weight: 700;
    }
    .vendor-subtotal {
        color: #60a5fa;
        font-weight: 800;
    }
    .vendor-status {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 5px;
    }
    .order-items {
        margin-top: 20px;
    }
    .item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(148,163,184,.07);
    }
    .item:last-child {
        border-bottom: 0;
    }
    .item-image {
        width: 58px;
        height: 58px;
        object-fit: cover;
        border-radius: 14px;
        background: #1e293b;
    }
    .item-info {
        flex: 1;
    }
    .item-name {
        color: #fff;
        font-weight: 700;
    }
    .item-vendor {
        color: #64748b;
        font-size: 12px;
        margin-top: 4px;
    }
    .item-qty {
        color: #94a3b8;
        font-size: 13px;
    }
    .item-price {
        color: #e2e8f0;
        font-weight: 800;
        min-width: 100px;
        text-align: right;
    }
    .order-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
    }
    .details-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        border-radius: 14px;
        color: #fff;
        text-decoration: none;
        font-weight: 700;
        background: linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );
        box-shadow:
            0 8px 25px rgba(37,99,235,.25);
        transition: .2s ease;
    }
    .details-btn:hover {
        transform: translateY(-2px);
        box-shadow:
            0 12px 30px rgba(37,99,235,.4);
    }
    .empty-orders {
        padding: 70px 20px;
        text-align: center;
        border-radius: 25px;
        background: rgba(15,23,42,.9);
        border: 1px solid rgba(59,130,246,.18);
    }
    .empty-icon {
        font-size: 55px;
        margin-bottom: 15px;
    }
    .empty-orders h2 {
        color: #fff;
        margin: 0 0 8px;
    }
    .empty-orders p {
        color: #64748b;
        margin-bottom: 25px;
    }
    @media (max-width: 768px) {
        .orders-page {
            padding: 25px 15px 60px;
        }
        .orders-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .orders-header h1 {
            font-size: 30px;
        }
        .order-top {
            flex-direction: column;
        }
        .order-total {
            text-align: left;
        }
        .order-info {
            grid-template-columns: 1fr;
        }
        .vendor-row {
            flex-direction: column;
            align-items: flex-start;
        }
        .item-price {
            min-width: auto;
        }
    }
</style>
</head>
<body>
<?php
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="orders-page">
<div class="orders-container">
    <div class="orders-header">
        <div>
            <h1>My Orders</h1>
            <p>
                Track your HochipoHub purchases and order status.
            </p>
        </div>
        <a
            href="<?= baseUrl('catalog.php') ?>"
            class="back-btn"
        >
            ← Continue Shopping
        </a>
    </div>
    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <div class="empty-icon">
                🛍️
            </div>
            <h2>No orders yet</h2>
            <p>
                Your completed purchases will appear here.
            </p>
            <a
                href="<?= baseUrl('catalog.php') ?>"
                class="details-btn"
            >
                Explore Products
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int) $order['order_id'];
            $items = getOrderItems(
                $db,
                $orderId
            );
            $vendorOrders = getVendorOrders(
                $db,
                $orderId
            );
            $statusClass =
                match ($order['order_status']) {
                    'Processing' =>
                        'status-processing',
                    'Completed' =>
                        'status-completed',
                    'Cancelled' =>
                        'status-cancelled',
                    default =>
                        'status-pending'
                };
            ?>
            <article class="order-card">
                <div class="order-top">
                    <div>
                        <div class="order-number">
                            Order #<?= e($orderId) ?>
                        </div>
                        <div class="order-date">
                            <?= e(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['order_date']
                                    )
                                )
                            ) ?>
                        </div>
                        <span
                            class="status <?= e($statusClass) ?>"
                        >
                            <?= e($order['order_status']) ?>
                        </span>
                    </div>
                    <div class="order-total">
                        <?= formatPrice(
                            $order['total_amount']
                        ) ?>
                    </div>
                </div>
                <div class="order-info">
                    <div class="info-box">
                        <div class="info-label">
                            Items
                        </div>
                        <div class="info-value">
                            <?= e($order['item_count']) ?>
                            item(s)
                        </div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">
                            Delivery
                        </div>
                        <div class="info-value">
                            <?= e(
                                $order['delivery_method']
                                ?: 'Not specified'
                            ) ?>
                        </div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">
                            Payment
                        </div>
                        <div class="info-value">
                            <?= e(
                                $order['payment_status']
                                ?: 'Pending'
                            ) ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($vendorOrders)): ?>
                    <div class="vendor-section">
                        <div class="vendor-title">
                            Vendor Orders
                        </div>
                        <?php foreach ($vendorOrders as $vendor): ?>
                            <div class="vendor-order">
                                <div class="vendor-row">
                                    <div>
                                        <div class="vendor-name">
                                            <?= e(
                                                $vendor['business_name']
                                            ) ?>
                                        </div>
                                        <div class="vendor-status">
                                            Status:
                                            <?= e(
                                                $vendor['vendor_status']
                                            ) ?>
                                            <?php if (
                                                !empty(
                                                    $vendor['tracking_number']
                                                )
                                            ): ?>
                                                · Tracking:
                                                <?= e(
                                                    $vendor['tracking_number']
                                                ) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="vendor-subtotal">
                                        <?= formatPrice(
                                            $vendor['subtotal']
                                        ) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($items)): ?>
                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                            <div class="item">
                                <img
                                    class="item-image"
                                    src="<?= e(
                                        productImageUrl(
                                            $item['image']
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $item['product_name']
                                    ) ?>"
                                    onerror="
                                        this.src='<?= e(
                                            productImageUrl(null)
                                        ) ?>'
                                    "
                                >
                                <div class="item-info">
                                    <div class="item-name">
                                        <?= e(
                                            $item['product_name']
                                        ) ?>
                                    </div>
                                    <div class="item-vendor">
                                        <?= e(
                                            $item['business_name']
                                        ) ?>
                                    </div>
                                </div>
                                <div class="item-qty">
                                    ×<?= e(
                                        $item['quantity']
                                    ) ?>
                                </div>
                                <div class="item-price">
                                    <?= formatPrice(
                                        $item['subtotal']
                                    ) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="order-footer">
                    <a
                        href="<?= baseUrl(
                            'order_details.php?order_id='
                            . $orderId
                        ) ?>"
                        class="details-btn"
                    >
                        View Order Details →
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
</body>
</html>