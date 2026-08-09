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