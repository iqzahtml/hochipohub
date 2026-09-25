<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM ORDER DETAILS
|--------------------------------------------------------------------------
| File: order_details.php
|
| Features:
| - Customer can only view own order
| - Per-vendor order status
| - Payment information
| - Delivery / tracking information
| - Verified Purchase Review unlock
| - One review per order_detail
| - Review status:
|       Locked
|       Write Review
|       Reviewed
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| LOGIN REQUIRED
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
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId = (int) currentUserId();

if ($userId <= 0) {
    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('odEscape')) {

    function odEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('odMoney')) {

    function odMoney($amount): string
    {
        return number_format(
            (float) $amount,
            2
        );
    }
}


if (!function_exists('odImage')) {

    function odImage($image): string
    {
        $image = trim(
            (string) $image
        );

        if ($image === '') {
            return 'image/logo.jpg';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
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


if (!function_exists('odStatusClass')) {

    function odStatusClass($status): string
    {
        $status = strtolower(
            trim(
                (string) $status
            )
        );

        switch ($status) {

            case 'completed':
            case 'delivered':
            case 'paid':
                return 'success';

            case 'processing':
            case 'ready':
            case 'shipped':
                return 'processing';

            case 'cancelled':
            case 'failed':
            case 'refunded':
                return 'danger';

            default:
                return 'pending';
        }
    }
}


if (!function_exists('odStatusIcon')) {

    function odStatusIcon($status): string
    {
        $status = strtolower(
            trim(
                (string) $status
            )
        );

        switch ($status) {

            case 'completed':
            case 'delivered':
                return 'bi-check-circle-fill';

            case 'processing':
                return 'bi-arrow-repeat';

            case 'ready':
                return 'bi-box-seam-fill';

            case 'shipped':
                return 'bi-truck';

            case 'cancelled':
            case 'failed':
                return 'bi-x-circle-fill';

            default:
                return 'bi-clock-fill';
        }
    }
}


if (!function_exists('odStatusMessage')) {

    function odStatusMessage($status): string
    {
        $status = strtolower(
            trim(
                (string) $status
            )
        );

        switch ($status) {

            case 'completed':
                return
                    'Your order has been completed successfully.';

            case 'processing':
                return
                    'Your order is currently being prepared by the seller.';

            case 'ready':
                return
                    'Your order is ready for the next delivery step.';

            case 'shipped':
                return
                    'Your order has been shipped and is on the way.';

            case 'cancelled':
                return
                    'This order has been cancelled.';

            default:
                return
                    'Your order has been received and is waiting to be processed.';
        }
    }
}


if (!function_exists('odVendorStatusMessage')) {

    function odVendorStatusMessage($status): string
    {
        $status = strtolower(
            trim(
                (string) $status
            )
        );

        switch ($status) {

            case 'completed':
                return
                    'Order received. Review is now unlocked.';

            case 'shipped':
                return
                    'Your parcel is currently on the way.';

            case 'ready':
                return
                    'Your order is ready.';

            case 'processing':
                return
                    'Seller is preparing your order.';

            case 'cancelled':
                return
                    'This seller order has been cancelled.';

            default:
                return
                    'Waiting for seller to process your order.';
        }
    }
}


if (!function_exists('odTrackingUrl')) {
    function odTrackingUrl($courierName, $trackingNumber): string
    {
        $courierName = trim((string) $courierName);
        $trackingNumber = trim((string) $trackingNumber);
        if ($courierName === '' || $trackingNumber === '') { return ''; }
        return 'https://www.google.com/search?q=' . rawurlencode($courierName . ' ' . $trackingNumber . ' tracking');
    }
}


if (!function_exists('odMapsUrl')) {

    function odMapsUrl($address): string
    {
        $address = trim(
            (string) $address
        );

        if ($address === '') {
            return '';
        }

        return
            'https://www.google.com/maps/dir/?api=1&destination=' .
            rawurlencode($address);
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

    header(
        'Location: ' .
        BASE_URL .
        'order.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
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

    header(
        'Location: ' .
        BASE_URL .
        'order.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS
|--------------------------------------------------------------------------
|
| Important:
| - We fetch order_detail_id
| - Vendor information
| - Vendor order status
| - Existing review
|
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        od.order_detail_id,
        od.order_id,
        od.product_id,
        od.quantity,
        od.unit_price,
        od.subtotal,

        p.product_name,
        p.image,
        p.vendor_id,

        v.business_name,
        v.business_logo,
        v.business_address,

        vo.vendor_order_id,
        vo.vendor_status,
        vo.tracking_number AS vendor_tracking_number,
        vo.courier_name AS vendor_courier_name,
        vo.delivery_fee,
        vo.completed_at,

        r.review_id,
        r.rating AS review_rating,
        r.review_title,
        r.review AS review_text,
        r.image AS review_image,
        r.review_date

    FROM order_details od

    INNER JOIN products p
        ON od.product_id =
           p.product_id

    INNER JOIN vendors v
        ON p.vendor_id =
           v.vendor_id

    LEFT JOIN vendor_orders vo
        ON vo.order_id =
           od.order_id
       AND vo.vendor_id =
           p.vendor_id

    LEFT JOIN reviews r
        ON r.order_detail_id =
           od.order_detail_id
       AND r.customer_id = ?

    WHERE od.order_id = ?

    ORDER BY
        v.business_name ASC,
        od.order_detail_id ASC
");


$stmt->execute([
    $userId,
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

$stmt = $db->prepare("
    SELECT

        vo.vendor_order_id,
        vo.order_id,
        vo.vendor_id,
        vo.subtotal,
        vo.delivery_fee,
        vo.vendor_status,
        vo.tracking_number,
        vo.courier_name,
        vo.created_at,
        vo.completed_at,

        v.business_name,
        v.business_logo,
        v.business_address,
        v.delivery_method,
        v.allow_vendor_delivery,
        v.cod_enabled

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
| GET LATEST PAYMENT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        payment_id,
        payment_method,
        payment_status,
        payment_date,
        amount,
        transaction_reference,
        payment_gateway,
        gateway_order_reference

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
| CALCULATIONS
|--------------------------------------------------------------------------
*/

$totalItems = 0;
$productSubtotal = 0.00;
$totalDeliveryFee = 0.00;


foreach ($items as $item) {

    $totalItems +=
        (int) $item['quantity'];

    $productSubtotal +=
        (float) $item['subtotal'];
}


foreach ($vendorOrders as $vendorOrder) {

    $totalDeliveryFee +=
        (float) (
            $vendorOrder['delivery_fee']
            ?? 0
        );
}


$grandTotal =
    (float) $order['total_amount'];


$deliveryMethod =
    trim(
        (string) (
            $order['delivery_method']
            ?? ''
        )
    );


$orderStatus =
    $order['order_status']
    ?? 'Pending';


$orderStatusClass =
    odStatusClass(
        $orderStatus
    );


$orderStatusIcon =
    odStatusIcon(
        $orderStatus
    );


$orderStatusMessage =
    odStatusMessage(
        $orderStatus
    );


/*
|--------------------------------------------------------------------------
| PAYMENT DISPLAY
|--------------------------------------------------------------------------
*/

$paymentStatus =
    $payment['payment_status']
    ?? 'Pending';


$paymentMethod =
    $payment['payment_method']
    ?? '—';


$paymentDisplay =
    $paymentMethod;


if (
    strtolower(
        (string) $paymentMethod
    ) === 'cash'
) {

    if ($deliveryMethod === 'Pickup') {

        $paymentDisplay =
            'Cash at Pickup';

    } elseif (
        $deliveryMethod ===
        'Vendor Delivery'
    ) {

        $paymentDisplay =
            'Cash on Delivery';

    } else {

        $paymentDisplay =
            'Cash';
    }
}


/*
|--------------------------------------------------------------------------
| DELIVERY INFO
|--------------------------------------------------------------------------
*/

$deliveryIcon =
    'bi-truck';


$deliveryDescription =
    'Your order will be delivered using postage or courier.';


switch ($deliveryMethod) {

    case 'Pickup':

        $deliveryIcon =
            'bi-shop';

        $deliveryDescription =
            'Collect your order directly from the seller.';

        break;


    case 'Vendor Delivery':

        $deliveryIcon =
            'bi-truck-front-fill';

        $deliveryDescription =
            'The seller will personally deliver your order.';

        break;


    case 'Postage':

    default:

        $deliveryIcon =
            'bi-truck';

        $deliveryDescription =
            'Your order will be delivered using postage or courier.';

        break;
}


/*
|--------------------------------------------------------------------------
| SIDEBAR COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = 0;
$wishlistCount = 0;


try {

    $stmt = $db->prepare("
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
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $cartCount = 0;
}


try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $wishlistCount =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| GROUP ITEMS BY VENDOR
|--------------------------------------------------------------------------
*/

$groupedItems = [];


foreach ($items as $item) {

    $vendorId =
        (int) $item['vendor_id'];

    if (
        !isset(
            $groupedItems[$vendorId]
        )
    ) {

        $groupedItems[$vendorId] = [
            'vendor_id' =>
                $vendorId,

            'business_name' =>
                $item['business_name'],

            'business_logo' =>
                $item['business_logo'],

            'business_address' =>
                $item['business_address'],

            'vendor_status' =>
                $item['vendor_status']
                ?? 'Pending',

            'tracking_number' =>
                $item['vendor_tracking_number']
                ?? '',

            'courier_name' =>
                $item['vendor_courier_name']
                ?? '',

            'delivery_fee' =>
                $item['delivery_fee']
                ?? 0,

            'completed_at' =>
                $item['completed_at']
                ?? null,

            'items' => []
        ];
    }


    $groupedItems[
        $vendorId
    ]['items'][] =
        $item;
}


/*
|--------------------------------------------------------------------------
| REVIEW COUNTS
|--------------------------------------------------------------------------
*/

$reviewableCount = 0;
$reviewedCount = 0;
$lockedCount = 0;


foreach ($items as $item) {

    $vendorStatus =
        strtolower(
            trim(
                (string) (
                    $item['vendor_status']
                    ?? ''
                )
            )
        );


    $hasReview =
        !empty(
            $item['review_id']
        );


    if ($hasReview) {

        $reviewedCount++;

    } elseif (
        $vendorStatus ===
        'completed'
    ) {

        $reviewableCount++;

    } else {

        $lockedCount++;
    }
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


$hideSiteMainWrapper = true;


$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

* {
    box-sizing: border-box;
}


.hh-order-page {

    width: 100%;
    min-height: 100vh;

    padding:
        36px 25px 75px;

    font-family:
        Inter,
        Arial,
        sans-serif;

    color:
        #172b4d;

    background:
        radial-gradient(
            circle at 92% 4%,
            rgba(37, 99, 235, .10),
            transparent 26%
        ),
        linear-gradient(
            180deg,
            #f4f8ff 0%,
            #f8fbff 45%,
            #ffffff 100%
        );
}


.hh-order-container {

    width: 100%;
    max-width: 1360px;

    margin:
        0 auto;
}


/* =========================================================
   SUCCESS MESSAGE
========================================================= */

.hh-success {

    margin-bottom: 20px;

    padding:
        15px 18px;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    color:
        #166534;

    background:
        #ecfdf3;

    border:
        1px solid #bbf7d0;

    border-radius:
        16px;

    font-size:
        13px;

    font-weight:
        700;
}


/* =========================================================
   HERO
========================================================= */

.hh-order-hero {

    position:
        relative;

    overflow:
        hidden;

    margin-bottom:
        24px;

    padding:
        46px 48px;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        320px;

    align-items:
        center;

    gap:
        30px;

    color:
        #ffffff;

    background:
        linear-gradient(
            120deg,
            #071d4f 0%,
            #0d4290 45%,
            #2685f4 100%
        );

    border-radius:
        30px;

    box-shadow:
        0 24px 55px
        rgba(20, 77, 166, .18);
}


.hh-order-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        330px;

    height:
        330px;

    right:
        -120px;

    top:
        -160px;

    border-radius:
        50%;

    background:
        rgba(
            255,
            255,
            255,
            .08
        );
}


.hh-order-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        220px;

    height:
        220px;

    right:
        180px;

    bottom:
        -170px;

    border-radius:
        50%;

    background:
        rgba(
            255,
            255,
            255,
            .06
        );
}


.hh-hero-content {

    position:
        relative;

    z-index:
        2;
}


.hh-hero-label {

    width:
        fit-content;

    margin-bottom:
        15px;

    padding:
        8px 13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .20
        );

    border-radius:
        999px;

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        .6px;

    text-transform:
        uppercase;
}


.hh-order-hero h1 {

    margin:
        0 0 11px;

    font-size:
        clamp(
            34px,
            5vw,
            53px
        );

    line-height:
        1;

    letter-spacing:
        -2px;

    font-weight:
        850;
}


.hh-order-hero p {

    max-width:
        680px;

    margin:
        0;

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size:
        13px;

    line-height:
        1.8;
}


.hh-hero-status {

    position:
        relative;

    z-index:
        2;

    padding:
        24px;

    color:
        #133561;

    background:
        rgba(
            255,
            255,
            255,
            .94
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .55
        );

    border-radius:
        22px;

    box-shadow:
        0 15px 35px
        rgba(
            0,
            30,
            80,
            .13
        );
}


.hh-hero-status-icon {

    width:
        48px;

    height:
        48px;

    margin-bottom:
        13px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #eaf3ff;

    border-radius:
        15px;

    font-size:
        21px;
}


.hh-hero-status span {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #7990af;

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        .7px;

    text-transform:
        uppercase;
}


.hh-hero-status strong {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #102f5c;

    font-size:
        20px;
}


.hh-hero-status p {

    color:
        #7288a5;

    font-size:
        10px;

    line-height:
        1.6;
}


/* =========================================================
   STATS
========================================================= */

.hh-stat-grid {

    margin-bottom:
        24px;

    display:
        grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap:
        16px;
}


.hh-stat-card {

    padding:
        21px;

    background:
        #ffffff;

    border:
        1px solid
        #e0eaf7;

    border-radius:
        20px;

    box-shadow:
        0 11px 30px
        rgba(
            43,
            76,
            125,
            .055
        );
}


.hh-stat-icon {

    width:
        38px;

    height:
        38px;

    margin-bottom:
        12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #eff6ff;

    border-radius:
        12px;

    font-size:
        16px;
}


.hh-stat-label {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #8a9ab0;

    font-size:
        9px;

    font-weight:
        800;

    text-transform:
        uppercase;

    letter-spacing:
        .7px;
}


.hh-stat-value {

    color:
        #153660;

    font-size:
        17px;

    font-weight:
        850;
}


/* =========================================================
   SECTION
========================================================= */

.hh-section {

    margin-bottom:
        24px;

    padding:
        27px;

    background:
        rgba(
            255,
            255,
            255,
            .96
        );

    border:
        1px solid
        #dfe9f5;

    border-radius:
        24px;

    box-shadow:
        0 14px 38px
        rgba(
            39,
            78,
            132,
            .06
        );
}


.hh-section-heading {

    margin-bottom:
        21px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        18px;
}


.hh-section-heading-left {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;
}


.hh-section-heading-icon {

    width:
        43px;

    height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2469da;

    background:
        #edf5ff;

    border:
        1px solid
        #d9e9ff;

    border-radius:
        14px;

    font-size:
        17px;
}


.hh-section-heading span {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #8ca0ba;

    font-size:
        8px;

    font-weight:
        850;

    text-transform:
        uppercase;

    letter-spacing:
        .9px;
}


.hh-section-heading h2 {

    margin:
        0;

    color:
        #123463;

    font-size:
        19px;
}


/* =========================================================
   REVIEW DASHBOARD
========================================================= */

.hh-review-banner {

    position:
        relative;

    overflow:
        hidden;

    margin-bottom:
        24px;

    padding:
        26px 28px;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        auto;

    align-items:
        center;

    gap:
        25px;

    color:
        #ffffff;

    background:
        linear-gradient(
            120deg,
            #6d28d9 0%,
            #7c3aed 42%,
            #2563eb 100%
        );

    border-radius:
        24px;

    box-shadow:
        0 18px 38px
        rgba(
            91,
            33,
            182,
            .18
        );
}


.hh-review-banner::after {

    content:
        "★";

    position:
        absolute;

    right:
        28%;

    top:
        -38px;

    color:
        rgba(
            255,
            255,
            255,
            .08
        );

    font-size:
        150px;

    transform:
        rotate(12deg);
}


.hh-review-banner-content {

    position:
        relative;

    z-index:
        2;
}


.hh-review-banner-label {

    margin-bottom:
        7px;

    display:
        block;

    color:
        rgba(
            255,
            255,
            255,
            .72
        );

    font-size:
        9px;

    font-weight:
        850;

    letter-spacing:
        1px;

    text-transform:
        uppercase;
}


.hh-review-banner h2 {

    margin:
        0 0 8px;

    font-size:
        23px;
}


.hh-review-banner p {

    max-width:
        720px;

    margin:
        0;

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size:
        11px;

    line-height:
        1.7;
}


.hh-review-count {

    position:
        relative;

    z-index:
        2;

    min-width:
        120px;

    padding:
        17px;

    text-align:
        center;

    background:
        rgba(
            255,
            255,
            255,
            .13
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .19
        );

    border-radius:
        18px;
}


.hh-review-count strong {

    display:
        block;

    font-size:
        29px;
}


.hh-review-count span {

    color:
        rgba(
            255,
            255,
            255,
            .78
        );

    font-size:
        8px;

    font-weight:
        800;

    text-transform:
        uppercase;
}


/* =========================================================
   SELLER GROUP
========================================================= */

.hh-seller-card {

    margin-bottom:
        18px;

    overflow:
        hidden;

    background:
        #ffffff;

    border:
        1px solid
        #dfe8f5;

    border-radius:
        22px;
}


.hh-seller-header {

    padding:
        18px 20px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        16px;

    background:
        linear-gradient(
            180deg,
            #fbfdff,
            #f7faff
        );

    border-bottom:
        1px solid
        #e7eef7;
}


.hh-seller-info {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;
}


.hh-seller-logo {

    width:
        43px;

    height:
        43px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2469da;

    background:
        #edf5ff;

    border:
        1px solid
        #d8e9ff;

    border-radius:
        13px;

    font-weight:
        850;
}


.hh-seller-logo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}


.hh-seller-name {

    color:
        #173a67;

    font-size:
        13px;

    font-weight:
        850;
}


.hh-seller-sub {

    margin-top:
        2px;

    color:
        #8b9bb1;

    font-size:
        9px;
}


.hh-status-badge {

    padding:
        7px 11px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        850;
}


.hh-status-badge.success {

    color:
        #047857;

    background:
        #ecfdf5;

    border:
        1px solid
        #a7f3d0;
}


.hh-status-badge.processing {

    color:
        #1d4ed8;

    background:
        #eff6ff;

    border:
        1px solid
        #bfdbfe;
}


.hh-status-badge.pending {

    color:
        #92400e;

    background:
        #fffbeb;

    border:
        1px solid
        #fde68a;
}


.hh-status-badge.danger {

    color:
        #b91c1c;

    background:
        #fef2f2;

    border:
        1px solid
        #fecaca;
}


/* =========================================================
   PRODUCT ITEM
========================================================= */

.hh-product {

    padding:
        20px;

    display:
        grid;

    grid-template-columns:
        108px
        minmax(0, 1fr)
        auto;

    align-items:
        center;

    gap:
        18px;

    border-bottom:
        1px solid
        #edf1f7;
}


.hh-product:last-child {

    border-bottom:
        none;
}


.hh-product-image {

    width:
        108px;

    height:
        108px;

    overflow:
        hidden;

    padding:
        8px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #f5f9ff;

    border:
        1px solid
        #e0eaf7;

    border-radius:
        18px;
}


.hh-product-image img {

    width:
        100%;

    height:
        100%;

    object-fit:
        contain;

    border-radius:
        11px;
}


.hh-product-info {

    min-width:
        0;
}


.hh-product-info h3 {

    margin:
        0 0 5px;

    color:
        #153864;

    font-size:
        14px;

    font-weight:
        850;
}


.hh-product-info h3 a {

    color:
        inherit;

    text-decoration:
        none;
}


.hh-product-meta {

    margin-bottom:
        10px;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        7px;

    color:
        #8497b1;

    font-size:
        9px;
}


.hh-product-pricing {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        12px;

    color:
        #58708e;

    font-size:
        10px;
}


.hh-product-pricing strong {

    color:
        #173b6c;
}


.hh-review-action {

    min-width:
        165px;

    text-align:
        right;
}


.hh-review-button {

    min-height:
        42px;

    padding:
        0 17px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        linear-gradient(
            110deg,
            #7c3aed,
            #2563eb
        );

    border:
        none;

    border-radius:
        13px;

    box-shadow:
        0 9px 20px
        rgba(
            82,
            65,
            220,
            .18
        );

    font-size:
        9px;

    font-weight:
        850;

    text-decoration:
        none;

    transition:
        .2s ease;
}


.hh-review-button:hover {

    color:
        #ffffff;

    transform:
        translateY(-2px);

    box-shadow:
        0 13px 25px
        rgba(
            82,
            65,
            220,
            .24
        );
}


.hh-review-completed {

    padding:
        10px 13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #047857;

    background:
        #ecfdf5;

    border:
        1px solid
        #a7f3d0;

    border-radius:
        12px;

    font-size:
        9px;

    font-weight:
        850;
}


.hh-review-stars-small {

    display:
        block;

    margin-top:
        7px;

    color:
        #f59e0b;

    font-size:
        11px;

    letter-spacing:
        1px;
}


.hh-review-locked {

    padding:
        10px 12px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #7d8da5;

    background:
        #f7f9fc;

    border:
        1px solid
        #e2e8f0;

    border-radius:
        12px;

    font-size:
        8px;

    font-weight:
        800;
}


.hh-review-hint {

    display:
        block;

    margin-top:
        6px;

    color:
        #94a3b8;

    font-size:
        8px;
}


/* =========================================================
   TWO COLUMN INFO
========================================================= */

.hh-info-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap:
        20px;
}


.hh-info-card {

    padding:
        23px;

    background:
        #ffffff;

    border:
        1px solid
        #e0e9f5;

    border-radius:
        22px;

    box-shadow:
        0 13px 34px
        rgba(
            39,
            78,
            132,
            .05
        );
}


.hh-info-row {

    padding:
        11px 0;

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        20px;

    border-bottom:
        1px dashed
        #e7edf5;

    font-size:
        10px;
}


.hh-info-row:last-child {

    border-bottom:
        none;
}


.hh-info-row span {

    color:
        #899ab0;
}


.hh-info-row strong {

    color:
        #173a66;

    text-align:
        right;
}


.hh-payment-status {

    padding:
        5px 9px;

    border-radius:
        999px;
}


.hh-payment-status.success {

    color:
        #047857;

    background:
        #ecfdf5;
}


.hh-payment-status.pending {

    color:
        #92400e;

    background:
        #fffbeb;
}


.hh-payment-status.danger {

    color:
        #b91c1c;

    background:
        #fef2f2;
}


/* =========================================================
   TRACKING
========================================================= */

.hh-tracking-box {

    margin-top:
        15px;

    padding:
        15px;

    color:
        #31577f;

    background:
        #f6faff;

    border:
        1px solid
        #dceaff;

    border-radius:
        14px;

    font-size:
        10px;

    line-height:
        1.7;
}


.hh-tracking-number {

    margin-top:
        7px;

    display:
        inline-flex;

    padding:
        6px 10px;

    color:
        #1458ba;

    background:
        #eaf3ff;

    border-radius:
        9px;

    font-weight:
        850;

    letter-spacing:
        .4px;
}


/* =========================================================
   ORDER TOTAL
========================================================= */

.hh-total-box {

    margin-top:
        18px;

    padding:
        20px;

    background:
        linear-gradient(
            135deg,
            #f5f9ff,
            #edf5ff
        );

    border:
        1px solid
        #d7e7fb;

    border-radius:
        18px;
}


.hh-total-row {

    margin-bottom:
        10px;

    display:
        flex;

    justify-content:
        space-between;

    gap:
        20px;

    color:
        #6c819e;

    font-size:
        10px;
}


.hh-total-row:last-child {

    margin:
        13px 0 0;

    padding-top:
        13px;

    border-top:
        1px solid
        #d4e2f3;

    color:
        #103a72;

    font-size:
        16px;

    font-weight:
        850;
}


/* =========================================================
   BUTTONS
========================================================= */

.hh-bottom-actions {

    margin-top:
        24px;

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        10px;
}


.hh-btn {

    min-height:
        43px;

    padding:
        0 17px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    border-radius:
        13px;

    font-size:
        9px;

    font-weight:
        850;

    text-decoration:
        none;

    transition:
        .2s ease;
}


.hh-btn-primary {

    color:
        #ffffff;

    background:
        #2563eb;

    border:
        1px solid
        #2563eb;
}


.hh-btn-secondary {

    color:
        #315985;

    background:
        #ffffff;

    border:
        1px solid
        #dce6f2;
}


.hh-btn:hover {

    transform:
        translateY(-2px);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1050px
) {

    .hh-order-hero {

        grid-template-columns:
            1fr;
    }


    .hh-stat-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }


    .hh-info-grid {

        grid-template-columns:
            1fr;
    }
}


@media (
    max-width: 720px
) {

    .hh-order-page {

        padding:
            20px 14px 55px;
    }


    .hh-order-hero {

        padding:
            30px 24px;

        border-radius:
            22px;
    }


    .hh-order-hero h1 {

        font-size:
            35px;
    }


    .hh-stat-grid {

        grid-template-columns:
            1fr 1fr;
    }


    .hh-review-banner {

        grid-template-columns:
            1fr;
    }


    .hh-review-count {

        width:
            fit-content;
    }


    .hh-product {

        grid-template-columns:
            78px
            minmax(0, 1fr);
    }


    .hh-product-image {

        width:
            78px;

        height:
            78px;
    }


    .hh-review-action {

        grid-column:
            1 / -1;

        width:
            100%;

        text-align:
            left;
    }


    .hh-review-button,
    .hh-review-completed,
    .hh-review-locked {

        width:
            100%;
    }


    .hh-seller-header {

        align-items:
            flex-start;

        flex-direction:
            column;
    }


    .hh-section {

        padding:
            20px;
    }
}


@media (
    max-width: 470px
) {

    .hh-stat-grid {

        grid-template-columns:
            1fr;
    }
}

</style>


<main class="hh-order-page">

    <div class="hh-order-container">


        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === '1'
        ): ?>

            <div class="hh-success">

                <i class="bi bi-check-circle-fill"></i>

                Order placed successfully.
                Thank you for shopping with HochipoHub.

            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['payment'])
        ): ?>

            <?php

            $paymentResult =
                strtolower(
                    trim(
                        (string)
                        $_GET['payment']
                    )
                );

            ?>

            <?php if (
                $paymentResult === 'success'
            ): ?>

                <div class="hh-success">

                    <i class="bi bi-credit-card-fill"></i>

                    Payment completed successfully.

                </div>

            <?php elseif (
                $paymentResult === 'processing'
            ): ?>

                <div
                    class="hh-success"
                    style="
                        color:#92400e;
                        background:#fffbeb;
                        border-color:#fde68a;
                    "
                >

                    <i class="bi bi-hourglass-split"></i>

                    Payment is currently being verified.

                </div>

            <?php elseif (
                $paymentResult === 'failed'
            ): ?>

                <div
                    class="hh-success"
                    style="
                        color:#b91c1c;
                        background:#fef2f2;
                        border-color:#fecaca;
                    "
                >

                    <i class="bi bi-x-circle-fill"></i>

                    Payment was not successful.

                </div>

            <?php endif; ?>

        <?php endif; ?>


        <!-- =====================================================
             HERO
        ====================================================== -->

        <section class="hh-order-hero">

            <div class="hh-hero-content">

                <div class="hh-hero-label">

                    <i class="bi bi-bag-check-fill"></i>

                    Order Journey

                </div>


                <h1>

                    Order
                    #<?= (int) $orderId ?>

                </h1>


                <p>

                    Everything about your purchase is here —
                    payment, delivery, seller progress and your
                    verified-purchase review access.

                </p>

            </div>


            <div class="hh-hero-status">

                <div class="hh-hero-status-icon">

                    <i
                        class="bi <?= odEscape(
                            $orderStatusIcon
                        ) ?>"
                    ></i>

                </div>


                <span>
                    Current Status
                </span>


                <strong>

                    <?= odEscape(
                        $orderStatus
                    ) ?>

                </strong>


                <p>

                    <?= odEscape(
                        $orderStatusMessage
                    ) ?>

                </p>

            </div>

        </section>


        <!-- =====================================================
             STATS
        ====================================================== -->

        <section class="hh-stat-grid">

            <div class="hh-stat-card">

                <div class="hh-stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

                <span class="hh-stat-label">
                    Total Items
                </span>

                <strong class="hh-stat-value">

                    <?= (int) $totalItems ?>

                </strong>

            </div>


            <div class="hh-stat-card">

                <div class="hh-stat-icon">
                    <i class="bi bi-shop"></i>
                </div>

                <span class="hh-stat-label">
                    Sellers
                </span>

                <strong class="hh-stat-value">

                    <?= count(
                        $groupedItems
                    ) ?>

                </strong>

            </div>


            <div class="hh-stat-card">

                <div class="hh-stat-icon">
                    <i class="bi bi-credit-card"></i>
                </div>

                <span class="hh-stat-label">
                    Payment
                </span>

                <strong class="hh-stat-value">

                    <?= odEscape(
                        $paymentStatus
                    ) ?>

                </strong>

            </div>


            <div class="hh-stat-card">

                <div class="hh-stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>

                <span class="hh-stat-label">
                    Order Total
                </span>

                <strong class="hh-stat-value">

                    RM
                    <?= odMoney(
                        $grandTotal
                    ) ?>

                </strong>

            </div>

        </section>


        <!-- =====================================================
             REVIEW JOURNEY
        ====================================================== -->

        <section class="hh-review-banner">

            <div class="hh-review-banner-content">

                <span class="hh-review-banner-label">

                    Verified Purchase Reviews

                </span>


                <h2>

                    Your voice unlocks after delivery ✨

                </h2>


                <p>

                    When a seller marks your purchased item as
                    Completed, HochipoHub automatically unlocks
                    your verified review. Every review here is
                    connected to an actual purchased order item.

                </p>

            </div>


            <div class="hh-review-count">

                <strong>

                    <?= (int)
                        $reviewableCount ?>

                </strong>

                <span>

                    Ready to Review

                </span>

            </div>

        </section>


        <!-- =====================================================
             PRODUCTS BY SELLER
        ====================================================== -->

        <section class="hh-section">

            <div class="hh-section-heading">

                <div class="hh-section-heading-left">

                    <div class="hh-section-heading-icon">

                        <i class="bi bi-stars"></i>

                    </div>


                    <div>

                        <span>
                            Purchased Products
                        </span>

                        <h2>
                            Your Order Items
                        </h2>

                    </div>

                </div>


                <div
                    style="
                        color:#8b9bb1;
                        font-size:9px;
                        font-weight:700;
                    "
                >

                    <?= (int)
                        $reviewedCount ?>
                    reviewed ·

                    <?= (int)
                        $reviewableCount ?>
                    ready

                </div>

            </div>


            <?php if (
                empty(
                    $groupedItems
                )
            ): ?>

                <div
                    style="
                        padding:45px 20px;
                        text-align:center;
                        color:#8ca0b8;
                    "
                >

                    No order items found.

                </div>

            <?php else: ?>


                <?php foreach (
                    $groupedItems
                    as $seller
                ): ?>


                    <?php

                    $sellerStatus =
                        $seller[
                            'vendor_status'
                        ]
                        ?? 'Pending';


                    $sellerStatusClass =
                        odStatusClass(
                            $sellerStatus
                        );


                    $sellerStatusIcon =
                        odStatusIcon(
                            $sellerStatus
                        );


                    $sellerLogo =
                        trim(
                            (string) (
                                $seller[
                                    'business_logo'
                                ]
                                ?? ''
                            )
                        );


                    if (
                        $sellerLogo !== ''
                    ) {

                        $sellerLogoUrl =
                            'uploads/vendors/' .
                            rawurlencode(
                                basename(
                                    $sellerLogo
                                )
                            );

                    } else {

                        $sellerLogoUrl =
                            '';
                    }

                    ?>


                    <div class="hh-seller-card">


                        <!-- SELLER HEADER -->

                        <div class="hh-seller-header">

                            <div class="hh-seller-info">

                                <div class="hh-seller-logo">

                                    <?php if (
                                        $sellerLogoUrl !== ''
                                    ): ?>

                                        <img
                                            src="<?= odEscape(
                                                $sellerLogoUrl
                                            ) ?>"
                                            alt="Seller"
                                        >

                                    <?php else: ?>

                                        <i
                                            class="bi bi-shop"
                                        ></i>

                                    <?php endif; ?>

                                </div>


                                <div>

                                    <div class="hh-seller-name">

                                        <?= odEscape(
                                            $seller[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <div class="hh-seller-sub">

                                        <?= odEscape(
                                            odVendorStatusMessage(
                                                $sellerStatus
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <span
                                class="
                                    hh-status-badge
                                    <?= odEscape(
                                        $sellerStatusClass
                                    ) ?>
                                "
                            >

                                <i
                                    class="bi <?= odEscape(
                                        $sellerStatusIcon
                                    ) ?>"
                                ></i>

                                <?= odEscape(
                                    $sellerStatus
                                ) ?>

                            </span>

                        </div>


                        <!-- PRODUCTS -->

                        <?php foreach (
                            $seller['items']
                            as $item
                        ): ?>


                            <?php

                            $hasReview =
                                !empty(
                                    $item[
                                        'review_id'
                                    ]
                                );


                            $itemVendorStatus =
                                strtolower(
                                    trim(
                                        (string) (
                                            $item[
                                                'vendor_status'
                                            ]
                                            ?? ''
                                        )
                                    )
                                );


                            $canReview =
                                !$hasReview &&
                                $itemVendorStatus ===
                                'completed';


                            $reviewLocked =
                                !$hasReview &&
                                !$canReview;


                            $reviewRating =
                                (int) (
                                    $item[
                                        'review_rating'
                                    ]
                                    ?? 0
                                );

                            ?>


                            <article class="hh-product">


                                <!-- PRODUCT IMAGE -->

                                <a
                                    href="<?= odEscape(
                                        BASE_URL
                                    ) ?>product_details.php?id=<?= (int)
                                        $item[
                                            'product_id'
                                        ] ?>"
                                    class="hh-product-image"
                                >

                                    <img
                                        src="<?= odEscape(
                                            odImage(
                                                $item[
                                                    'image'
                                                ]
                                            )
                                        ) ?>"
                                        alt="<?= odEscape(
                                            $item[
                                                'product_name'
                                            ]
                                        ) ?>"
                                    >

                                </a>


                                <!-- PRODUCT INFO -->

                                <div class="hh-product-info">

                                    <h3>

                                        <a
                                            href="<?= odEscape(
                                                BASE_URL
                                            ) ?>product_details.php?id=<?= (int)
                                                $item[
                                                    'product_id'
                                                ] ?>"
                                        >

                                            <?= odEscape(
                                                $item[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </a>

                                    </h3>


                                    <div class="hh-product-meta">

                                        <span>

                                            <i class="bi bi-shop"></i>

                                            <?= odEscape(
                                                $item[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </span>


                                        <span>
                                            •
                                        </span>


                                        <span>

                                            Qty:
                                            <?= (int)
                                                $item[
                                                    'quantity'
                                                ] ?>

                                        </span>


                                        <span>
                                            •
                                        </span>


                                        <span>

                                            Verified Purchase

                                            <i
                                                class="bi bi-patch-check-fill"
                                                style="
                                                    color:#2563eb;
                                                "
                                            ></i>

                                        </span>

                                    </div>


                                    <div class="hh-product-pricing">

                                        <span>

                                            Unit Price:

                                            <strong>

                                                RM
                                                <?= odMoney(
                                                    $item[
                                                        'unit_price'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </span>


                                        <span>

                                            Subtotal:

                                            <strong>

                                                RM
                                                <?= odMoney(
                                                    $item[
                                                        'subtotal'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </span>

                                    </div>

                                </div>


                                <!-- REVIEW ACTION -->

                                <div class="hh-review-action">


                                    <?php if (
                                        $hasReview
                                    ): ?>


                                        <div class="hh-review-completed">

                                            <i
                                                class="bi bi-patch-check-fill"
                                            ></i>

                                            Reviewed

                                        </div>


                                        <?php if (
                                            $reviewRating > 0
                                        ): ?>

                                            <span class="hh-review-stars-small">

                                                <?php

                                                for (
                                                    $star = 1;
                                                    $star <= 5;
                                                    $star++
                                                ) {

                                                    echo
                                                        $star <=
                                                        $reviewRating
                                                            ? '★'
                                                            : '☆';
                                                }

                                                ?>

                                            </span>

                                        <?php endif; ?>


                                        <span class="hh-review-hint">

                                            Thank you for sharing
                                            your experience.

                                        </span>


                                    <?php elseif (
                                        $canReview
                                    ): ?>


                                        <a
                                            href="<?= odEscape(
                                                BASE_URL
                                            ) ?>review.php?order_detail_id=<?= (int)
                                                $item[
                                                    'order_detail_id'
                                                ] ?>"
                                            class="hh-review-button"
                                        >

                                            <i
                                                class="bi bi-stars"
                                            ></i>

                                            Write Review

                                        </a>


                                        <span class="hh-review-hint">

                                            Verified review unlocked ✨

                                        </span>


                                    <?php elseif (
                                        $reviewLocked
                                    ): ?>


                                        <div class="hh-review-locked">

                                            <i
                                                class="bi bi-lock-fill"
                                            ></i>

                                            Review Locked

                                        </div>


                                        <span class="hh-review-hint">

                                            Available after this
                                            seller order is completed.

                                        </span>


                                    <?php endif; ?>


                                </div>

                            </article>

                        <?php endforeach; ?>


                        <?php if (
                            !empty(
                                $seller[
                                    'tracking_number'
                                ]
                            )
                        ): ?>

                            <div
                                style="
                                    padding:
                                        0 20px 20px;
                                "
                            >

                                <div class="hh-tracking-box">

                                    <i class="bi bi-truck"></i>

                                    Tracking information provided
                                    by seller.

                                    <?php if (!empty($seller['courier_name'])): ?>
                                        <div style="margin-top:8px;"><strong>Courier:</strong> <?= odEscape($seller['courier_name']) ?></div>
                                    <?php endif; ?>

                                    <div class="hh-tracking-number">
                                        <?= odEscape($seller['tracking_number']) ?>
                                    </div>

                                    <?php $trackingUrl = odTrackingUrl($seller['courier_name'] ?? '', $seller['tracking_number'] ?? ''); ?>
                                    <?php if ($trackingUrl !== ''): ?>
                                        <div style="margin-top:10px;">
                                            <a href="<?= odEscape($trackingUrl) ?>" target="_blank" rel="noopener noreferrer" class="hh-btn hh-btn-primary">
                                                <i class="bi bi-box-arrow-up-right"></i> Track Parcel
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endif; ?>


                    </div>

                <?php endforeach; ?>


            <?php endif; ?>

        </section>


        <!-- =====================================================
             PAYMENT + DELIVERY
        ====================================================== -->

        <div class="hh-info-grid">


            <!-- PAYMENT -->

            <section class="hh-info-card">

                <div class="hh-section-heading">

                    <div class="hh-section-heading-left">

                        <div class="hh-section-heading-icon">

                            <i
                                class="bi bi-credit-card-fill"
                            ></i>

                        </div>


                        <div>

                            <span>
                                Transaction
                            </span>

                            <h2>
                                Payment Details
                            </h2>

                        </div>

                    </div>

                </div>


                <div class="hh-info-row">

                    <span>
                        Payment Method
                    </span>

                    <strong>

                        <?= odEscape(
                            $paymentDisplay
                        ) ?>

                    </strong>

                </div>


                <div class="hh-info-row">

                    <span>
                        Payment Status
                    </span>

                    <strong>

                        <span
                            class="
                                hh-payment-status
                                <?= odEscape(
                                    odStatusClass(
                                        $paymentStatus
                                    )
                                ) ?>
                            "
                        >

                            <?= odEscape(
                                $paymentStatus
                            ) ?>

                        </span>

                    </strong>

                </div>


                <div class="hh-info-row">

                    <span>
                        Amount
                    </span>

                    <strong>

                        RM
                        <?= odMoney(
                            $payment[
                                'amount'
                            ]
                            ?? $grandTotal
                        ) ?>

                    </strong>

                </div>


                <?php if (
                    !empty(
                        $payment[
                            'payment_gateway'
                        ]
                    )
                ): ?>

                    <div class="hh-info-row">

                        <span>
                            Payment Gateway
                        </span>

                        <strong>

                            <?= odEscape(
                                $payment[
                                    'payment_gateway'
                                ]
                            ) ?>

                        </strong>

                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $payment[
                            'transaction_reference'
                        ]
                    )
                ): ?>

                    <div class="hh-info-row">

                        <span>
                            Transaction Reference
                        </span>

                        <strong>

                            <?= odEscape(
                                $payment[
                                    'transaction_reference'
                                ]
                            ) ?>

                        </strong>

                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $payment[
                            'payment_date'
                        ]
                    )
                ): ?>

                    <div class="hh-info-row">

                        <span>
                            Payment Date
                        </span>

                        <strong>

                            <?= odEscape(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $payment[
                                            'payment_date'
                                        ]
                                    )
                                )
                            ) ?>

                        </strong>

                    </div>

                <?php endif; ?>


            </section>


            <!-- DELIVERY -->

            <section class="hh-info-card">

                <div class="hh-section-heading">

                    <div class="hh-section-heading-left">

                        <div class="hh-section-heading-icon">

                            <i
                                class="bi <?= odEscape(
                                    $deliveryIcon
                                ) ?>"
                            ></i>

                        </div>


                        <div>

                            <span>
                                Fulfilment
                            </span>

                            <h2>
                                Delivery Details
                            </h2>

                        </div>

                    </div>

                </div>


                <div class="hh-info-row">

                    <span>
                        Delivery Method
                    </span>

                    <strong>

                        <?= odEscape(
                            $deliveryMethod !== ''
                                ? $deliveryMethod
                                : '—'
                        ) ?>

                    </strong>

                </div>


                <div class="hh-info-row">

                    <span>
                        Order Date
                    </span>

                    <strong>

                        <?= odEscape(
                            date(
                                'd M Y, h:i A',
                                strtotime(
                                    $order[
                                        'order_date'
                                    ]
                                )
                            )
                        ) ?>

                    </strong>

                </div>


                <?php if (
                    !empty(
                        $order[
                            'completed_date'
                        ]
                    )
                ): ?>

                    <div class="hh-info-row">

                        <span>
                            Completed Date
                        </span>

                        <strong>

                            <?= odEscape(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order[
                                            'completed_date'
                                        ]
                                    )
                                )
                            ) ?>

                        </strong>

                    </div>

                <?php endif; ?>


                <?php if (
                    $deliveryMethod !==
                    'Pickup' &&
                    !empty(
                        $order[
                            'delivery_address'
                        ]
                    )
                ): ?>

                    <div class="hh-info-row">

                        <span>
                            Delivery Address
                        </span>

                        <strong>

                            <?= nl2br(
                                odEscape(
                                    $order[
                                        'delivery_address'
                                    ]
                                )
                            ) ?>

                        </strong>

                    </div>

                <?php endif; ?>


                <div class="hh-tracking-box">

                    <?= odEscape(
                        $deliveryDescription
                    ) ?>

                </div>

            </section>

        </div>


        <!-- =====================================================
             ORDER SUMMARY
        ====================================================== -->

        <section
            class="hh-section"
            style="
                margin-top:24px;
            "
        >

            <div class="hh-section-heading">

                <div class="hh-section-heading-left">

                    <div class="hh-section-heading-icon">

                        <i class="bi bi-receipt-cutoff"></i>

                    </div>


                    <div>

                        <span>
                            Payment Breakdown
                        </span>

                        <h2>
                            Order Summary
                        </h2>

                    </div>

                </div>

            </div>


            <div class="hh-total-box">

                <div class="hh-total-row">

                    <span>
                        Product Subtotal
                    </span>

                    <strong>

                        RM
                        <?= odMoney(
                            $productSubtotal
                        ) ?>

                    </strong>

                </div>


                <div class="hh-total-row">

                    <span>
                        Delivery Fee
                    </span>

                    <strong>

                        RM
                        <?= odMoney(
                            $totalDeliveryFee
                        ) ?>

                    </strong>

                </div>


                <div class="hh-total-row">

                    <span>
                        Grand Total
                    </span>

                    <strong>

                        RM
                        <?= odMoney(
                            $grandTotal
                        ) ?>

                    </strong>

                </div>

            </div>


            <div class="hh-bottom-actions">

                <a
                    href="<?= odEscape(
                        BASE_URL
                    ) ?>order.php"
                    class="
                        hh-btn
                        hh-btn-secondary
                    "
                >

                    <i class="bi bi-arrow-left"></i>

                    Back to Orders

                </a>


                <a
                    href="<?= odEscape(
                        BASE_URL
                    ) ?>product.php"
                    class="
                        hh-btn
                        hh-btn-primary
                    "
                >

                    <i class="bi bi-bag"></i>

                    Continue Shopping

                </a>

            </div>

        </section>


    </div>

</main>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>