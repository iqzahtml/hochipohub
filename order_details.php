<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM ORDER DETAILS
|--------------------------------------------------------------------------
| File:
| order_details.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

requireLogin();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


if (!($db instanceof PDO)) {

    die(
        'Database connection is not available.'
    );
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId =
    (int) currentUserId();


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('orderDetailsEscape')) {

    function orderDetailsEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('orderDetailsImage')) {

    function orderDetailsImage($image): string
    {
        $image =
            trim(
                (string) $image
            );


        if ($image === '') {
            return '';
        }


        if (
            str_starts_with(
                $image,
                'http://'
            ) ||
            str_starts_with(
                $image,
                'https://'
            )
        ) {

            return $image;
        }


        if (
            str_starts_with(
                $image,
                'uploads/'
            )
        ) {

            return $image;
        }


        return
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('orderStatusClass')) {

    function orderStatusClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        return match ($status) {

            'completed',
            'delivered' =>
                'success',

            'processing',
            'ready',
            'shipped' =>
                'processing',

            'cancelled',
            'failed' =>
                'danger',

            default =>
                'pending'
        };
    }
}


if (!function_exists('orderStatusIcon')) {

    function orderStatusIcon($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        return match ($status) {

            'completed',
            'delivered' =>
                'bi-check-circle-fill',

            'processing' =>
                'bi-arrow-repeat',

            'ready' =>
                'bi-box-seam',

            'shipped' =>
                'bi-truck',

            'cancelled',
            'failed' =>
                'bi-x-circle-fill',

            default =>
                'bi-clock-fill'
        };
    }
}


if (!function_exists('orderStatusMessage')) {

    function orderStatusMessage($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        return match ($status) {

            'completed',
            'delivered' =>
                'Your order has been completed successfully.',

            'processing' =>
                'Your order is currently being prepared by the seller.',

            'ready' =>
                'Your order is ready for the next delivery step.',

            'shipped' =>
                'Your order has been shipped and is on the way.',

            'cancelled' =>
                'This order has been cancelled.',

            default =>
                'Your order has been received and is waiting to be processed.'
        };
    }
}


/*
|--------------------------------------------------------------------------
| ORDER ID
|--------------------------------------------------------------------------
*/

$orderId =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;


if ($orderId <= 0) {

    $_SESSION['error'] =
        'Invalid order.';


    redirect(
        BASE_URL .
        'order.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            o.order_id,
            o.customer_id,
            o.order_date,
            o.total_amount,
            o.delivery_method,
            o.delivery_address,
            o.tracking_number,
            o.order_status,
            o.completed_date,

            u.name AS customer_name,
            u.email AS customer_email,
            u.phone AS customer_phone

        FROM orders o

        INNER JOIN users u
            ON o.customer_id =
               u.user_id

        WHERE o.order_id = ?

        AND o.customer_id = ?

        LIMIT 1
    ");


$stmt->execute([
    $orderId,
    $userId
]);


$order =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$order) {

    $_SESSION['error'] =
        'Order not found or you do not have permission to view it.';


    redirect(
        BASE_URL .
        'order.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
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
            ON od.product_id =
               p.product_id

        INNER JOIN vendors v
            ON p.vendor_id =
               v.vendor_id

        WHERE od.order_id = ?

        ORDER BY
            od.order_detail_id ASC
    ");


$stmt->execute([
    $orderId
]);


$items =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GET VENDOR ORDERS
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            vo.vendor_order_id,
            vo.vendor_id,
            vo.subtotal,
            vo.delivery_fee,
            vo.vendor_status,
            vo.tracking_number,
            vo.created_at,
            vo.completed_at,

            v.business_name

        FROM vendor_orders vo

        INNER JOIN vendors v
            ON vo.vendor_id =
               v.vendor_id

        WHERE vo.order_id = ?

        ORDER BY
            vo.vendor_order_id ASC
    ");


$stmt->execute([
    $orderId
]);


$vendorOrders =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GET PAYMENT
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            payment_id,
            payment_method,
            payment_status,
            payment_date,
            amount,
            transaction_reference

        FROM payments

        WHERE order_id = ?

        ORDER BY
            payment_id DESC

        LIMIT 1
    ");


$stmt->execute([
    $orderId
]);


$payment =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalItems = 0;


foreach ($items as $item) {

    $totalItems +=
        (int) $item['quantity'];
}


$vendorCount =
    count(
        $vendorOrders
    );


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$orderStatus =
    $order['order_status']
    ?? 'Pending';


$orderStatusClass =
    orderStatusClass(
        $orderStatus
    );


$orderStatusIcon =
    orderStatusIcon(
        $orderStatus
    );


$orderStatusMessage =
    orderStatusMessage(
        $orderStatus
    );


/*
|--------------------------------------------------------------------------
| PAYMENT
|--------------------------------------------------------------------------
*/

$paymentStatus =
    $payment['payment_status']
    ?? 'Pending';


$paymentMethod =
    $payment['payment_method']
    ?? '—';


/*
|--------------------------------------------------------------------------
| CUSTOMER NAV COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = 0;

$wishlistCount = 0;


try {

    $stmt =
        $db->prepare("
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                )

            FROM cart

            WHERE customer_id = ?
        ");


    $stmt->execute([
        $userId
    ]);


    $cartCount =
        (int)
        $stmt->fetchColumn();


} catch (Throwable $e) {

    $cartCount = 0;
}


try {

    $stmt =
        $db->prepare("
            SELECT
                COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");


    $stmt->execute([
        $userId
    ]);


    $wishlistCount =
        (int)
        $stmt->fetchColumn();


} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Order #' .
    $orderId .
    ' - HochipoHub';


$hideSiteMainWrapper =
    true;


$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

/* ================================================================
   PAGE
================================================================ */

.hh-order-page {
    width: 100%;
    min-height: 100vh;

    padding:
        42px
        24px
        75px;

    overflow-x: hidden;

    color: #14213d;

    background:
        radial-gradient(
            circle at 92% 4%,
            rgba(59,130,246,.08),
            transparent 24%
        ),
        linear-gradient(
            180deg,
            #f5f8ff 0%,
            #f8faff 55%,
            #ffffff 100%
        );

    font-family:
        Inter,
        Arial,
        sans-serif;
}


.hh-order-container {
    width: 100%;
    max-width: 1340px;
    margin: 0 auto;
}


/* ================================================================
   SUCCESS BANNER
================================================================ */

.hh-order-success {
    margin-bottom: 18px;
    padding: 14px 17px;

    display: flex;
    align-items: center;
    gap: 10px;

    color: #166534;

    background:
        linear-gradient(
            135deg,
            #f0fdf4,
            #ecfdf5
        );

    border:
        1px solid #bbf7d0;

    border-radius: 13px;

    font-size: 9px;
    font-weight: 750;
}


.hh-order-success i {
    font-size: 15px;
}


/* ================================================================
   HERO
================================================================ */

.hh-order-hero {
    position: relative;

    min-height: 310px;

    margin-bottom: 22px;

    padding:
        45px
        50px;

    overflow: hidden;

    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        350px;

    align-items: center;

    gap: 40px;

    color: #ffffff;

    background:
        linear-gradient(
            115deg,
            #0b2c6b 0%,
            #154a98 48%,
            #2784ee 100%
        );

    border-radius: 28px;

    box-shadow:
        0
        20px
        50px
        rgba(23,79,165,.16);
}


.hh-order-hero::before {
    content: "";

    position: absolute;

    width: 300px;
    height: 300px;

    top: -160px;
    right: -65px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}


.hh-order-hero::after {
    content: "";

    position: absolute;

    width: 185px;
    height: 185px;

    right: 185px;
    bottom: -135px;

    border-radius: 50%;

    background:
        rgba(111,231,243,.10);
}


.hh-order-hero-copy {
    position: relative;
    z-index: 2;
}


.hh-order-pill {
    min-height: 33px;

    padding: 0 13px;

    margin-bottom: 17px;

    display: inline-flex;
    align-items: center;
    gap: 7px;

    color: #ffffff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius: 999px;

    font-size: 9px;
    font-weight: 900;
}


.hh-order-hero h1 {
    margin: 0;

    color: #ffffff;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:
        clamp(
            35px,
            4.4vw,
            54px
        );

    line-height: 1.08;

    font-weight: 800;

    letter-spacing: -1.8px;
}


.hh-order-hero h1 span {
    color: #6fe7f3;
}


.hh-order-hero p {
    margin: 14px 0 0;

    color:
        rgba(255,255,255,.76);

    font-size: 11px;
    line-height: 1.75;
}


.hh-order-hero-actions {
    margin-top: 21px;

    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}


.hh-order-back,
.hh-order-shop {
    min-height: 43px;

    padding: 0 15px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    border-radius: 11px;

    font-size: 8px;
    font-weight: 850;

    text-decoration: none;
}


.hh-order-back {
    color: #1757ad;
    background: #ffffff;
}


.hh-order-shop {
    color: #ffffff;

    background:
        rgba(255,255,255,.10);

    border:
        1px solid
        rgba(255,255,255,.22);
}


/* ================================================================
   HERO VISUAL
================================================================ */

.hh-order-art {
    position: relative;
    z-index: 2;

    height: 220px;
}


.hh-order-main-icon {
    position: absolute;

    width: 150px;
    height: 150px;

    top: 30px;
    right: 78px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius: 39px;

    font-size: 61px;

    backdrop-filter:
        blur(12px);

    transform:
        rotate(-4deg);
}


.hh-order-float {
    position: absolute;

    min-width: 145px;

    padding:
        11px
        13px;

    display: flex;
    align-items: center;
    gap: 8px;

    color: #26405f;

    background:
        rgba(255,255,255,.96);

    border-radius: 12px;

    box-shadow:
        0
        14px
        32px
        rgba(5,35,80,.17);

    font-size: 8px;
    font-weight: 850;
}


.hh-order-float i {
    width: 31px;
    height: 31px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eff6ff;

    border-radius: 9px;
}


.hh-order-float.one {
    top: 4px;
    left: 0;
}


.hh-order-float.two {
    right: 0;
    bottom: 4px;
}


/* ================================================================
   STATS
================================================================ */

.hh-order-stats {
    margin-bottom: 22px;

    display: grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap: 14px;
}


.hh-order-stat {
    min-height: 92px;

    padding: 17px;

    display: flex;
    align-items: center;
    gap: 12px;

    background: #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius: 17px;

    box-shadow:
        0
        8px
        24px
        rgba(40,65,120,.045);
}


.hh-order-stat-icon {
    width: 43px;
    height: 43px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    font-size: 15px;
}


.hh-order-stat-icon.blue {
    color: #2563eb;
    background: #eff6ff;
}


.hh-order-stat-icon.green {
    color: #15803d;
    background: #ecfdf3;
}


.hh-order-stat-icon.orange {
    color: #c2410c;
    background: #fff7ed;
}


.hh-order-stat-icon.purple {
    color: #7c3aed;
    background: #f5f3ff;
}


.hh-order-stat span {
    display: block;

    margin-bottom: 4px;

    color: #8a98aa;

    font-size: 6px;
    font-weight: 850;
    letter-spacing: .7px;
}


.hh-order-stat strong {
    display: block;

    color: #17233c;

    font-size: 16px;
    font-weight: 900;
}


/* ================================================================
   STATUS PANEL
================================================================ */

.hh-order-status-card {
    margin-bottom: 22px;
    padding: 22px;

    display: grid;

    grid-template-columns:
        auto
        minmax(0,1fr)
        auto;

    align-items: center;

    gap: 16px;

    background: #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius: 18px;

    box-shadow:
        0
        8px
        24px
        rgba(40,65,120,.045);
}


.hh-status-main-icon {
    width: 52px;
    height: 52px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 14px;

    font-size: 19px;
}


.hh-order-status-card.pending
.hh-status-main-icon {
    color: #b45309;
    background: #fff7ed;
}


.hh-order-status-card.processing
.hh-status-main-icon {
    color: #2563eb;
    background: #eff6ff;
}


.hh-order-status-card.success
.hh-status-main-icon {
    color: #15803d;
    background: #ecfdf3;
}


.hh-order-status-card.danger
.hh-status-main-icon {
    color: #dc2626;
    background: #fef2f2;
}


.hh-status-copy small {
    display: block;

    margin-bottom: 3px;

    color: #2563eb;

    font-size: 6px;
    font-weight: 900;
    letter-spacing: .9px;
}


.hh-status-copy h2 {
    margin: 0 0 4px;

    color: #17233c;

    font-size: 15px;
    font-weight: 900;
}


.hh-status-copy p {
    margin: 0;

    color: #8391a4;

    font-size: 8px;
    line-height: 1.6;
}


.hh-status-badge {
    min-height: 31px;

    padding: 0 11px;

    display: inline-flex;
    align-items: center;
    gap: 6px;

    border-radius: 999px;

    font-size: 7px;
    font-weight: 900;
}


.hh-status-badge.pending {
    color: #b45309;
    background: #fff7ed;
}


.hh-status-badge.processing {
    color: #1d4ed8;
    background: #eff6ff;
}


.hh-status-badge.success {
    color: #15803d;
    background: #ecfdf3;
}


.hh-status-badge.danger {
    color: #dc2626;
    background: #fef2f2;
}


/* ================================================================
   MAIN GRID
================================================================ */

.hh-order-layout {
    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        350px;

    align-items: start;

    gap: 21px;
}


.hh-order-main {
    min-width: 0;

    display: flex;
    flex-direction: column;
    gap: 19px;
}


.hh-order-side {
    position: sticky;
    top: 22px;

    display: flex;
    flex-direction: column;
    gap: 15px;
}


/* ================================================================
   SECTION
================================================================ */

.hh-order-section {
    overflow: hidden;

    background: #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius: 20px;

    box-shadow:
        0
        10px
        28px
        rgba(40,65,120,.045);
}


.hh-order-section-header {
    min-height: 88px;

    padding: 19px 22px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;

    border-bottom:
        1px solid #edf1f5;
}


.hh-section-title {
    display: flex;
    align-items: center;
    gap: 11px;
}


.hh-section-icon {
    width: 44px;
    height: 44px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #438bf2
        );

    border-radius: 12px;

    box-shadow:
        0
        8px
        17px
        rgba(37,99,235,.16);
}


.hh-section-icon.purple {
    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #9b6af5
        );
}


.hh-section-icon.green {
    background:
        linear-gradient(
            135deg,
            #16a34a,
            #4ade80
        );
}


.hh-section-title small {
    display: block;

    margin-bottom: 2px;

    color: #2563eb;

    font-size: 6px;
    font-weight: 900;
    letter-spacing: .8px;
}


.hh-section-title h2 {
    margin: 0;

    color: #17233c;

    font-size: 15px;
    font-weight: 900;
}


.hh-section-count {
    min-height: 29px;

    padding: 0 10px;

    display: inline-flex;
    align-items: center;

    color: #2563eb;
    background: #eff6ff;

    border-radius: 999px;

    font-size: 7px;
    font-weight: 850;
}


/* ================================================================
   PRODUCT ITEMS
================================================================ */

.hh-order-items {
    padding: 5px 20px;
}


.hh-order-item {
    padding: 16px 0;

    display: grid;

    grid-template-columns:
        82px
        minmax(0,1fr)
        auto;

    align-items: center;

    gap: 14px;

    border-bottom:
        1px solid #edf1f5;
}


.hh-order-item:last-child {
    border-bottom: 0;
}


.hh-order-item-image {
    width: 82px;
    height: 82px;

    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;

    background:
        linear-gradient(
            135deg,
            #f1f6ff,
            #eaf2ff
        );

    border:
        1px solid #dee8f5;

    border-radius: 13px;

    font-size: 24px;
}


.hh-order-item-image img {
    width: 100%;
    height: 100%;

    padding: 6px;

    object-fit: contain;
    object-position: center;
}


.hh-order-item-info {
    min-width: 0;
}


.hh-order-item-info h3 {
    margin: 0 0 5px;

    color: #263a55;

    font-size: 11px;
    font-weight: 900;
}


.hh-item-vendor {
    margin-bottom: 6px;

    display: flex;
    align-items: center;
    gap: 5px;

    color: #8492a6;

    font-size: 7px;
}


.hh-item-vendor i {
    color: #2563eb;
}


.hh-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}


.hh-item-meta span {
    min-height: 25px;

    padding: 0 8px;

    display: inline-flex;
    align-items: center;
    gap: 4px;

    color: #66788f;

    background: #f8fafc;

    border:
        1px solid #e5eaf1;

    border-radius: 7px;

    font-size: 6px;
    font-weight: 750;
}


.hh-order-item-price {
    text-align: right;
}


.hh-order-item-price small {
    display: block;

    margin-bottom: 3px;

    color: #94a0b2;

    font-size: 6px;
    font-weight: 850;
}


.hh-order-item-price strong {
    color: #1c4a87;

    font-size: 14px;
    font-weight: 900;

    white-space: nowrap;
}


/* ================================================================
   TOTAL BAR
================================================================ */

.hh-order-total-bar {
    padding: 18px 21px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    background: #f8fbff;

    border-top:
        1px solid #edf1f5;
}


.hh-order-total-bar span {
    color: #708198;

    font-size: 8px;
    font-weight: 850;
}


.hh-order-total-bar strong {
    color: #2563eb;

    font-size: 19px;
    font-weight: 900;
}


/* ================================================================
   VENDOR ORDERS
================================================================ */

.hh-vendor-orders {
    padding: 18px;

    display: grid;
    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap: 12px;
}


.hh-vendor-order {
    padding: 16px;

    background: #fbfdff;

    border:
        1px solid #e0e8f2;

    border-radius: 14px;
}


.hh-vendor-order-top {
    margin-bottom: 13px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}


.hh-vendor-name {
    display: flex;
    align-items: center;
    gap: 8px;
}


.hh-vendor-icon {
    width: 37px;
    height: 37px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eff6ff;

    border-radius: 10px;
}


.hh-vendor-name strong {
    display: block;

    color: #263a55;

    font-size: 9px;
    font-weight: 900;
}


.hh-vendor-name small {
    color: #8b98aa;

    font-size: 6px;
}


.hh-vendor-data {
    display: grid;
    grid-template-columns: 1fr 1fr;

    gap: 8px;
}


.hh-vendor-data div {
    padding: 10px;

    background: #ffffff;

    border:
        1px solid #e7ecf3;

    border-radius: 9px;
}


.hh-vendor-data span {
    display: block;

    margin-bottom: 3px;

    color: #97a3b3;

    font-size: 5px;
    font-weight: 850;
    letter-spacing: .6px;
}


.hh-vendor-data strong {
    color: #354760;

    font-size: 8px;
    font-weight: 850;
}


/* ================================================================
   INFORMATION
================================================================ */

.hh-info-grid {
    padding: 18px;

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap: 11px;
}


.hh-info-card {
    min-height: 95px;

    padding: 15px;

    background: #fbfdff;

    border:
        1px solid #e1e8f2;

    border-radius: 13px;
}


.hh-info-card.full {
    grid-column:
        1 / -1;
}


.hh-info-icon {
    width: 34px;
    height: 34px;

    margin-bottom: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;
    background: #eff6ff;

    border-radius: 9px;
}


.hh-info-card span {
    display: block;

    margin-bottom: 4px;

    color: #8b98aa;

    font-size: 5px;
    font-weight: 900;
    letter-spacing: .7px;
}


.hh-info-card strong,
.hh-info-card h3 {
    margin: 0;

    color: #263a55;

    font-size: 10px;
    font-weight: 900;
}


.hh-info-card p {
    margin: 0;

    color: #53657d;

    font-size: 8px;
    line-height: 1.65;
}


/* ================================================================
   SUMMARY SIDE
================================================================ */

.hh-side-card {
    overflow: hidden;

    background: #ffffff;

    border:
        1px solid #e1e8f2;

    border-radius: 18px;

    box-shadow:
        0
        11px
        28px
        rgba(40,65,120,.05);
}


.hh-side-card-header {
    padding: 19px;

    border-bottom:
        1px solid #edf1f5;
}


.hh-side-card-header small {
    display: block;

    margin-bottom: 3px;

    color: #2563eb;

    font-size: 6px;
    font-weight: 900;
    letter-spacing: .8px;
}


.hh-side-card-header h3 {
    margin: 0;

    color: #17233c;

    font-size: 14px;
    font-weight: 900;
}


.hh-side-body {
    padding: 18px;
}


.hh-side-row {
    min-height: 34px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 10px;
}


.hh-side-row span {
    color: #8290a4;

    font-size: 7px;
}


.hh-side-row strong {
    color: #32455f;

    font-size: 8px;
    font-weight: 850;

    text-align: right;
}


.hh-side-divider {
    height: 1px;

    margin: 11px 0;

    background: #e8edf4;
}


.hh-side-total {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;

    gap: 10px;
}


.hh-side-total span {
    color: #74849a;

    font-size: 7px;
    font-weight: 850;
}


.hh-side-total strong {
    color: #2563eb;

    font-size: 20px;
    font-weight: 900;
}


/* ================================================================
   CUSTOMER
================================================================ */

.hh-customer-card {
    padding: 18px;

    display: flex;
    align-items: center;
    gap: 12px;

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #f8fbff
        );

    border:
        1px solid #e1e8f2;

    border-radius: 18px;
}


.hh-customer-avatar {
    width: 47px;
    height: 47px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    border-radius: 13px;

    font-size: 17px;
}


.hh-customer-copy strong {
    display: block;

    margin-bottom: 3px;

    color: #263a55;

    font-size: 9px;
    font-weight: 900;
}


.hh-customer-copy span {
    display: block;

    color: #8492a6;

    font-size: 6px;
    line-height: 1.6;
}


/* ================================================================
   SAFE CARD
================================================================ */

.hh-order-safe {
    padding: 17px;

    display: flex;
    align-items: flex-start;
    gap: 10px;

    background:
        linear-gradient(
            135deg,
            #f3fbf6,
            #ecfdf3
        );

    border:
        1px solid #c8f1d5;

    border-radius: 16px;
}


.hh-order-safe-icon {
    width: 37px;
    height: 37px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #15803d;
    background: #ffffff;

    border-radius: 10px;
}


.hh-order-safe strong {
    display: block;

    margin-bottom: 3px;

    color: #24583a;

    font-size: 8px;
    font-weight: 900;
}


.hh-order-safe p {
    margin: 0;

    color: #668776;

    font-size: 7px;
    line-height: 1.6;
}


/* ================================================================
   EMPTY
================================================================ */

.hh-order-empty {
    padding: 45px 20px;

    text-align: center;

    color: #8391a5;
}


.hh-order-empty i {
    display: block;

    margin-bottom: 10px;

    color: #2563eb;

    font-size: 28px;
}


.hh-order-empty h3 {
    margin: 0 0 5px;

    color: #263a55;

    font-size: 12px;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1080px) {

    .hh-order-stats {
        grid-template-columns:
            repeat(2,1fr);
    }


    .hh-order-layout {
        grid-template-columns:
            1fr;
    }


    .hh-order-side {
        position: static;

        display: grid;

        grid-template-columns:
            1fr 1fr;
    }


    .hh-side-card {
        grid-row:
            span 2;
    }
}


@media (max-width: 850px) {

    .hh-order-page {
        padding:
            30px
            18px
            60px;
    }


    .hh-order-hero {
        grid-template-columns:
            1fr;

        min-height: auto;

        padding: 37px;
    }


    .hh-order-art {
        display: none;
    }


    .hh-vendor-orders {
        grid-template-columns:
            1fr;
    }
}


@media (max-width: 650px) {

    .hh-order-page {
        padding:
            21px
            13px
            50px;
    }


    .hh-order-hero {
        padding:
            28px
            23px;

        border-radius: 21px;
    }


    .hh-order-hero h1 {
        font-size: 30px;
    }


    .hh-order-hero-actions {
        flex-direction: column;
    }


    .hh-order-back,
    .hh-order-shop {
        width: 100%;
    }


    .hh-order-stats {
        grid-template-columns:
            1fr;
    }


    .hh-order-status-card {
        grid-template-columns:
            auto
            minmax(0,1fr);
    }


    .hh-status-badge {
        grid-column:
            1 / -1;

        width: fit-content;
    }


    .hh-order-item {
        grid-template-columns:
            65px
            minmax(0,1fr);
    }


    .hh-order-item-image {
        width: 65px;
        height: 65px;
    }


    .hh-order-item-price {
        grid-column: 2;
        text-align: left;
    }


    .hh-info-grid {
        grid-template-columns:
            1fr;
    }


    .hh-info-card.full {
        grid-column:
            auto;
    }


    .hh-order-side {
        display: flex;
    }

}

</style>


<!-- ===============================================================
     ORDER DETAILS
================================================================ -->

<main class="hh-order-page">


    <div class="hh-order-container">


        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] == '1'
        ): ?>


            <div class="hh-order-success">

                <i class="bi bi-check-circle-fill"></i>

                Your order has been placed successfully.
                Order #<?= $orderId ?> is now being processed.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-order-hero">


            <div class="hh-order-hero-copy">


                <span class="hh-order-pill">

                    <i class="bi bi-receipt"></i>

                    ORDER DETAILS

                </span>


                <h1>

                    Order

                    <span>
                        #<?= $orderId ?>
                    </span>

                </h1>


                <p>

                    Placed on

                    <?= orderDetailsEscape(
                        date(
                            'd M Y, h:i A',
                            strtotime(
                                $order[
                                    'order_date'
                                ]
                            )
                        )
                    ) ?>

                </p>


                <div class="hh-order-hero-actions">


                    <a
                        href="order.php"
                        class="hh-order-back"
                    >

                        <i class="bi bi-arrow-left"></i>

                        My Orders

                    </a>


                    <a
                        href="catalog.php"
                        class="hh-order-shop"
                    >

                        <i class="bi bi-bag"></i>

                        Continue Shopping

                    </a>


                </div>


            </div>



            <!-- HERO ART -->

            <div class="hh-order-art">


                <div class="hh-order-main-icon">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="hh-order-float one">

                    <i class="bi bi-bag-check"></i>

                    <?= $totalItems ?>

                    item<?= $totalItems !== 1
                        ? 's'
                        : '' ?>

                </div>


                <div class="hh-order-float two">

                    <i class="bi bi-shop"></i>

                    <?= $vendorCount ?>

                    seller<?= $vendorCount !== 1
                        ? 's'
                        : '' ?>

                </div>


            </div>


        </section>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="hh-order-stats">


            <article class="hh-order-stat">


                <div class="hh-order-stat-icon blue">

                    <i class="bi bi-bag"></i>

                </div>


                <div>

                    <span>
                        TOTAL ITEMS
                    </span>

                    <strong>
                        <?= $totalItems ?>
                    </strong>

                </div>


            </article>



            <article class="hh-order-stat">


                <div class="hh-order-stat-icon green">

                    <i class="bi bi-cash-stack"></i>

                </div>


                <div>

                    <span>
                        ORDER TOTAL
                    </span>

                    <strong>

                        RM
                        <?= number_format(
                            (float)
                            $order[
                                'total_amount'
                            ],
                            2
                        ) ?>

                    </strong>

                </div>


            </article>



            <article class="hh-order-stat">


                <div class="hh-order-stat-icon orange">

                    <i class="bi bi-truck"></i>

                </div>


                <div>

                    <span>
                        DELIVERY
                    </span>

                    <strong>

                        <?= orderDetailsEscape(
                            $order[
                                'delivery_method'
                            ]
                            ?? '—'
                        ) ?>

                    </strong>

                </div>


            </article>



            <article class="hh-order-stat">


                <div class="hh-order-stat-icon purple">

                    <i class="bi bi-credit-card"></i>

                </div>


                <div>

                    <span>
                        PAYMENT
                    </span>

                    <strong>

                        <?= orderDetailsEscape(
                            $paymentStatus
                        ) ?>

                    </strong>

                </div>


            </article>


        </section>



        <!-- =======================================================
             STATUS
        ======================================================== -->

        <section
            class="
                hh-order-status-card
                <?= orderDetailsEscape(
                    $orderStatusClass
                ) ?>
            "
        >


            <div class="hh-status-main-icon">

                <i
                    class="
                        bi
                        <?= orderDetailsEscape(
                            $orderStatusIcon
                        ) ?>
                    "
                ></i>

            </div>


            <div class="hh-status-copy">

                <small>
                    CURRENT STATUS
                </small>

                <h2>

                    <?= orderDetailsEscape(
                        $orderStatus
                    ) ?>

                </h2>

                <p>

                    <?= orderDetailsEscape(
                        $orderStatusMessage
                    ) ?>

                </p>

            </div>


            <span
                class="
                    hh-status-badge
                    <?= orderDetailsEscape(
                        $orderStatusClass
                    ) ?>
                "
            >

                <i
                    class="
                        bi
                        <?= orderDetailsEscape(
                            $orderStatusIcon
                        ) ?>
                    "
                ></i>

                <?= orderDetailsEscape(
                    $orderStatus
                ) ?>

            </span>


        </section>



        <!-- =======================================================
             MAIN LAYOUT
        ======================================================== -->

        <section class="hh-order-layout">


            <!-- ===================================================
                 LEFT
            ==================================================== -->

            <div class="hh-order-main">


                <!-- ===============================================
                     PURCHASED ITEMS
                ================================================ -->

                <section class="hh-order-section">


                    <div class="hh-order-section-header">


                        <div class="hh-section-title">


                            <div class="hh-section-icon">

                                <i class="bi bi-bag-check"></i>

                            </div>


                            <div>

                                <small>
                                    PURCHASED ITEMS
                                </small>

                                <h2>
                                    Order Products
                                </h2>

                            </div>


                        </div>


                        <span class="hh-section-count">

                            <?= $totalItems ?>

                            item<?= $totalItems !== 1
                                ? 's'
                                : '' ?>

                        </span>


                    </div>



                    <?php if (empty($items)): ?>


                        <div class="hh-order-empty">

                            <i class="bi bi-box"></i>

                            <h3>
                                No order items found
                            </h3>

                            <p>
                                Product information is unavailable.
                            </p>

                        </div>


                    <?php else: ?>


                        <div class="hh-order-items">


                            <?php foreach (
                                $items
                                as $item
                            ): ?>


                                <?php

                                $itemImage =
                                    orderDetailsImage(
                                        $item[
                                            'image'
                                        ]
                                        ?? ''
                                    );

                                ?>


                                <article class="hh-order-item">


                                    <div class="hh-order-item-image">


                                        <?php if (
                                            $itemImage !== ''
                                        ): ?>


                                            <img
                                                src="<?= orderDetailsEscape(
                                                    $itemImage
                                                ) ?>"
                                                alt="<?= orderDetailsEscape(
                                                    $item[
                                                        'product_name'
                                                    ]
                                                ) ?>"
                                                onerror="
                                                    this.style.display='none';
                                                    this.parentElement.innerHTML='<i class=&quot;bi bi-image&quot;></i>';
                                                "
                                            >


                                        <?php else: ?>


                                            <i class="bi bi-image"></i>


                                        <?php endif; ?>


                                    </div>



                                    <div class="hh-order-item-info">


                                        <h3>

                                            <?= orderDetailsEscape(
                                                $item[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </h3>


                                        <div class="hh-item-vendor">

                                            <i class="bi bi-shop"></i>

                                            <?= orderDetailsEscape(
                                                $item[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </div>


                                        <div class="hh-item-meta">


                                            <span>

                                                <i class="bi bi-box"></i>

                                                Qty:
                                                <?= (int)
                                                    $item[
                                                        'quantity'
                                                    ] ?>

                                            </span>


                                            <span>

                                                RM
                                                <?= number_format(
                                                    (float)
                                                    $item[
                                                        'unit_price'
                                                    ],
                                                    2
                                                ) ?>

                                                each

                                            </span>


                                        </div>


                                    </div>



                                    <div class="hh-order-item-price">


                                        <small>
                                            SUBTOTAL
                                        </small>


                                        <strong>

                                            RM
                                            <?= number_format(
                                                (float)
                                                $item[
                                                    'subtotal'
                                                ],
                                                2
                                            ) ?>

                                        </strong>


                                    </div>


                                </article>


                            <?php endforeach; ?>


                        </div>



                        <div class="hh-order-total-bar">

                            <span>
                                Order Total
                            </span>

                            <strong>

                                RM
                                <?= number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>

                            </strong>

                        </div>


                    <?php endif; ?>


                </section>



                <!-- ===============================================
                     VENDOR ORDERS
                ================================================ -->

                <?php if (
                    !empty(
                        $vendorOrders
                    )
                ): ?>


                    <section class="hh-order-section">


                        <div class="hh-order-section-header">


                            <div class="hh-section-title">


                                <div class="hh-section-icon purple">

                                    <i class="bi bi-shop"></i>

                                </div>


                                <div>

                                    <small>
                                        MULTI-VENDOR ORDER
                                    </small>

                                    <h2>
                                        Seller Fulfilment
                                    </h2>

                                </div>


                            </div>


                            <span class="hh-section-count">

                                <?= $vendorCount ?>

                                seller<?= $vendorCount !== 1
                                    ? 's'
                                    : '' ?>

                            </span>


                        </div>



                        <div class="hh-vendor-orders">


                            <?php foreach (
                                $vendorOrders
                                as $vendorOrder
                            ): ?>


                                <?php

                                $vendorStatus =
                                    $vendorOrder[
                                        'vendor_status'
                                    ]
                                    ?? 'Pending';


                                $vendorClass =
                                    orderStatusClass(
                                        $vendorStatus
                                    );

                                ?>


                                <article class="hh-vendor-order">


                                    <div class="hh-vendor-order-top">


                                        <div class="hh-vendor-name">


                                            <div class="hh-vendor-icon">

                                                <i class="bi bi-shop"></i>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= orderDetailsEscape(
                                                        $vendorOrder[
                                                            'business_name'
                                                        ]
                                                    ) ?>

                                                </strong>

                                                <small>
                                                    Seller Order
                                                </small>

                                            </div>


                                        </div>


                                        <span
                                            class="
                                                hh-status-badge
                                                <?= orderDetailsEscape(
                                                    $vendorClass
                                                ) ?>
                                            "
                                        >

                                            <?= orderDetailsEscape(
                                                $vendorStatus
                                            ) ?>

                                        </span>


                                    </div>



                                    <div class="hh-vendor-data">


                                        <div>

                                            <span>
                                                SUBTOTAL
                                            </span>

                                            <strong>

                                                RM
                                                <?= number_format(
                                                    (float)
                                                    $vendorOrder[
                                                        'subtotal'
                                                    ],
                                                    2
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                DELIVERY
                                            </span>

                                            <strong>

                                                RM
                                                <?= number_format(
                                                    (float)
                                                    $vendorOrder[
                                                        'delivery_fee'
                                                    ],
                                                    2
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                TRACKING
                                            </span>

                                            <strong>

                                                <?= !empty(
                                                    $vendorOrder[
                                                        'tracking_number'
                                                    ]
                                                )
                                                    ? orderDetailsEscape(
                                                        $vendorOrder[
                                                            'tracking_number'
                                                        ]
                                                    )
                                                    : 'Not available' ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                ORDER ID
                                            </span>

                                            <strong>

                                                #
                                                <?= (int)
                                                    $vendorOrder[
                                                        'vendor_order_id'
                                                    ] ?>

                                            </strong>

                                        </div>


                                    </div>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    </section>


                <?php endif; ?>



                <!-- ===============================================
                     DELIVERY
                ================================================ -->

                <section class="hh-order-section">


                    <div class="hh-order-section-header">


                        <div class="hh-section-title">


                            <div class="hh-section-icon green">

                                <i class="bi bi-truck"></i>

                            </div>


                            <div>

                                <small>
                                    DELIVERY
                                </small>

                                <h2>
                                    Delivery Information
                                </h2>

                            </div>


                        </div>


                    </div>



                    <div class="hh-info-grid">


                        <article class="hh-info-card">


                            <div class="hh-info-icon">

                                <i class="bi bi-box-seam"></i>

                            </div>


                            <span>
                                DELIVERY METHOD
                            </span>


                            <strong>

                                <?= orderDetailsEscape(
                                    $order[
                                        'delivery_method'
                                    ]
                                    ?? '—'
                                ) ?>

                            </strong>


                        </article>



                        <article class="hh-info-card">


                            <div class="hh-info-icon">

                                <i class="bi bi-upc-scan"></i>

                            </div>


                            <span>
                                TRACKING NUMBER
                            </span>


                            <strong>

                                <?= !empty(
                                    $order[
                                        'tracking_number'
                                    ]
                                )
                                    ? orderDetailsEscape(
                                        $order[
                                            'tracking_number'
                                        ]
                                    )
                                    : 'Not available yet' ?>

                            </strong>


                        </article>



                        <?php if (
                            $order[
                                'delivery_method'
                            ] === 'Postage'
                        ): ?>


                            <article class="hh-info-card full">


                                <div class="hh-info-icon">

                                    <i class="bi bi-geo-alt"></i>

                                </div>


                                <span>
                                    DELIVERY ADDRESS
                                </span>


                                <p>

                                    <?= nl2br(
                                        orderDetailsEscape(
                                            $order[
                                                'delivery_address'
                                            ]
                                            ?? ''
                                        )
                                    ) ?>

                                </p>


                            </article>


                        <?php endif; ?>


                    </div>


                </section>


            </div>



            <!-- ===================================================
                 RIGHT
            ==================================================== -->

            <aside class="hh-order-side">


                <!-- ===============================================
                     SUMMARY
                ================================================ -->

                <section class="hh-side-card">


                    <div class="hh-side-card-header">

                        <small>
                            ORDER SUMMARY
                        </small>

                        <h3>
                            Payment & Total
                        </h3>

                    </div>


                    <div class="hh-side-body">


                        <div class="hh-side-row">

                            <span>
                                Order ID
                            </span>

                            <strong>
                                #<?= $orderId ?>
                            </strong>

                        </div>


                        <div class="hh-side-row">

                            <span>
                                Items
                            </span>

                            <strong>
                                <?= $totalItems ?>
                            </strong>

                        </div>


                        <div class="hh-side-row">

                            <span>
                                Sellers
                            </span>

                            <strong>
                                <?= $vendorCount ?>
                            </strong>

                        </div>


                        <div class="hh-side-row">

                            <span>
                                Delivery
                            </span>

                            <strong>

                                <?= orderDetailsEscape(
                                    $order[
                                        'delivery_method'
                                    ]
                                    ?? '—'
                                ) ?>

                            </strong>

                        </div>


                        <div class="hh-side-row">

                            <span>
                                Payment Method
                            </span>

                            <strong>

                                <?= orderDetailsEscape(
                                    $paymentMethod
                                ) ?>

                            </strong>

                        </div>


                        <div class="hh-side-row">

                            <span>
                                Payment Status
                            </span>

                            <strong>

                                <?= orderDetailsEscape(
                                    $paymentStatus
                                ) ?>

                            </strong>

                        </div>


                        <div class="hh-side-divider"></div>


                        <div class="hh-side-total">

                            <span>
                                TOTAL
                            </span>

                            <strong>

                                RM
                                <?= number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>

                            </strong>

                        </div>


                    </div>


                </section>



                <!-- ===============================================
                     CUSTOMER
                ================================================ -->

                <section class="hh-customer-card">


                    <div class="hh-customer-avatar">

                        <i class="bi bi-person-fill"></i>

                    </div>


                    <div class="hh-customer-copy">

                        <strong>

                            <?= orderDetailsEscape(
                                $order[
                                    'customer_name'
                                ]
                                ?? 'Customer'
                            ) ?>

                        </strong>


                        <span>

                            <?= orderDetailsEscape(
                                $order[
                                    'customer_email'
                                ]
                                ?? ''
                            ) ?>

                        </span>


                        <?php if (
                            !empty(
                                $order[
                                    'customer_phone'
                                ]
                            )
                        ): ?>


                            <span>

                                <?= orderDetailsEscape(
                                    $order[
                                        'customer_phone'
                                    ]
                                ) ?>

                            </span>


                        <?php endif; ?>


                    </div>


                </section>



                <!-- ===============================================
                     PAYMENT DETAILS
                ================================================ -->

                <?php if ($payment): ?>


                    <section class="hh-side-card">


                        <div class="hh-side-card-header">

                            <small>
                                PAYMENT DETAILS
                            </small>

                            <h3>
                                Transaction
                            </h3>

                        </div>


                        <div class="hh-side-body">


                            <div class="hh-side-row">

                                <span>
                                    Method
                                </span>

                                <strong>

                                    <?= orderDetailsEscape(
                                        $payment[
                                            'payment_method'
                                        ]
                                        ?? '—'
                                    ) ?>

                                </strong>

                            </div>


                            <div class="hh-side-row">

                                <span>
                                    Status
                                </span>

                                <strong>

                                    <?= orderDetailsEscape(
                                        $payment[
                                            'payment_status'
                                        ]
                                        ?? 'Pending'
                                    ) ?>

                                </strong>

                            </div>


                            <div class="hh-side-row">

                                <span>
                                    Amount
                                </span>

                                <strong>

                                    RM
                                    <?= number_format(
                                        (float)
                                        $payment[
                                            'amount'
                                        ],
                                        2
                                    ) ?>

                                </strong>

                            </div>


                            <div class="hh-side-row">

                                <span>
                                    Reference
                                </span>

                                <strong>

                                    <?= !empty(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    )
                                        ? orderDetailsEscape(
                                            $payment[
                                                'transaction_reference'
                                            ]
                                        )
                                        : '—' ?>

                                </strong>

                            </div>


                        </div>


                    </section>


                <?php endif; ?>



                <!-- ===============================================
                     SECURITY
                ================================================ -->

                <section class="hh-order-safe">


                    <div class="hh-order-safe-icon">

                        <i class="bi bi-shield-check"></i>

                    </div>


                    <div>

                        <strong>
                            Order information secured
                        </strong>

                        <p>

                            Only your customer account
                            can view the details of this
                            order.

                        </p>

                    </div>


                </section>


            </aside>


        </section>


    </div>


</main>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/footer.php';

?>