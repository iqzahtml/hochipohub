<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM CUSTOMER ORDERS
|--------------------------------------------------------------------------
| File:
| order.php
|--------------------------------------------------------------------------
|
| Customer order history page.
|
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

if (!function_exists('ordersEscape')) {

    function ordersEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('ordersStatusClass')) {

    function ordersStatusClass($status): string
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


if (!function_exists('ordersStatusIcon')) {

    function ordersStatusIcon($status): string
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


if (!function_exists('ordersStatusMessage')) {

    function ordersStatusMessage($status): string
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
                'Order completed successfully.',

            'processing' =>
                'Seller is preparing your order.',

            'ready' =>
                'Your order is ready.',

            'shipped' =>
                'Your order is on the way.',

            'cancelled' =>
                'This order has been cancelled.',

            default =>
                'Waiting for seller processing.'
        };
    }
}


if (!function_exists('ordersPaymentClass')) {

    function ordersPaymentClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        return match ($status) {

            'paid',
            'completed',
            'success' =>
                'success',

            'failed',
            'cancelled' =>
                'danger',

            default =>
                'pending'
        };
    }
}


/*
|--------------------------------------------------------------------------
| VERIFY CUSTOMER
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            user_id,
            name,
            email,
            role

        FROM users

        WHERE user_id = ?

        LIMIT 1
    ");


$stmt->execute([
    $userId
]);


$user =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$user) {

    $_SESSION['error'] =
        'User account not found.';


    redirect(
        BASE_URL .
        'index.php'
    );
}


if (
    strtolower(
        trim(
            (string)
            $user['role']
        )
    ) !== 'customer'
) {

    $_SESSION['error'] =
        'Customer access required.';


    redirect(
        BASE_URL .
        'dashboard.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET ORDERS
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            o.order_id,
            o.order_date,
            o.total_amount,
            o.delivery_method,
            o.delivery_address,
            o.tracking_number,
            o.order_status,
            o.completed_date,

            p.payment_status,
            p.payment_method

        FROM orders o

        LEFT JOIN payments p
            ON o.order_id =
               p.order_id

        WHERE o.customer_id = ?

        ORDER BY
            o.order_date DESC
    ");


$stmt->execute([
    $userId
]);


$orders =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalOrders =
    count(
        $orders
    );


$completedOrders = 0;

$pendingOrders = 0;

$cancelledOrders = 0;

$totalSpent = 0;


foreach ($orders as $order) {

    $status =
        strtolower(
            trim(
                (string)
                $order['order_status']
            )
        );


    if (
        in_array(
            $status,
            [
                'completed',
                'delivered'
            ],
            true
        )
    ) {

        $completedOrders++;


        $totalSpent +=
            (float)
            $order['total_amount'];
    }


    if (
        in_array(
            $status,
            [
                'pending',
                'processing',
                'ready',
                'shipped'
            ],
            true
        )
    ) {

        $pendingOrders++;
    }


    if (
        in_array(
            $status,
            [
                'cancelled',
                'failed'
            ],
            true
        )
    ) {

        $cancelledOrders++;
    }
}


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
    'My Orders - HochipoHub';


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

.hh-orders-page {

    width:
        100%;

    min-height:
        100vh;

    padding:
        42px
        24px
        75px;

    overflow-x:
        hidden;

    color:
        #14213d;

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


.hh-orders-container {

    width:
        100%;

    max-width:
        1340px;

    margin:
        0 auto;

}


/* ================================================================
   HERO
================================================================ */

.hh-orders-hero {

    position:
        relative;

    min-height:
        320px;

    margin-bottom:
        22px;

    padding:
        46px
        50px;

    overflow:
        hidden;

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        360px;

    align-items:
        center;

    gap:
        38px;

    color:
        #ffffff;

    background:

        linear-gradient(
            115deg,
            #0b2c6b 0%,
            #154a98 48%,
            #2784ee 100%
        );

    border-radius:
        28px;

    box-shadow:

        0
        20px
        50px
        rgba(23,79,165,.16);

}


.hh-orders-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        305px;

    height:
        305px;

    top:
        -160px;

    right:
        -65px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.08);

}


.hh-orders-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        190px;

    height:
        190px;

    right:
        185px;

    bottom:
        -137px;

    border-radius:
        50%;

    background:
        rgba(111,231,243,.11);

}


.hh-orders-hero-copy {

    position:
        relative;

    z-index:
        2;

}


.hh-orders-pill {

    min-height:
        33px;

    padding:
        0 13px;

    margin-bottom:
        18px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        900;

}


.hh-orders-hero h1 {

    margin:
        0;

    color:
        #ffffff;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:

        clamp(
            35px,
            4.5vw,
            55px
        );

    line-height:
        1.08;

    font-weight:
        800;

    letter-spacing:
        -1.8px;

}


.hh-orders-hero h1 span {

    color:
        #6fe7f3;

}


.hh-orders-hero p {

    max-width:
        590px;

    margin:
        15px
        0
        0;

    color:
        rgba(255,255,255,.76);

    font-size:
        11px;

    line-height:
        1.75;

}


.hh-orders-hero-actions {

    margin-top:
        22px;

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        9px;

}


.hh-orders-shop {

    min-height:
        43px;

    padding:
        0 15px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    color:
        #1757ad;

    background:
        #ffffff;

    border-radius:
        11px;

    font-size:
        8px;

    font-weight:
        900;

    text-decoration:
        none;

}


.hh-orders-wishlist {

    min-height:
        43px;

    padding:
        0 15px;

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
        rgba(255,255,255,.10);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius:
        11px;

    font-size:
        8px;

    font-weight:
        900;

    text-decoration:
        none;

}


/* ================================================================
   HERO VISUAL
================================================================ */

.hh-orders-art {

    position:
        relative;

    z-index:
        2;

    height:
        220px;

}


.hh-orders-main-icon {

    position:
        absolute;

    width:
        150px;

    height:
        150px;

    top:
        30px;

    right:
        80px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius:
        39px;

    backdrop-filter:
        blur(12px);

    font-size:
        61px;

    transform:
        rotate(-4deg);

}


.hh-orders-floating {

    position:
        absolute;

    min-width:
        145px;

    padding:
        11px
        13px;

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        #26405f;

    background:
        rgba(255,255,255,.96);

    border-radius:
        12px;

    box-shadow:

        0
        14px
        32px
        rgba(5,35,80,.17);

    font-size:
        8px;

    font-weight:
        850;

}


.hh-orders-floating i {

    width:
        31px;

    height:
        31px;

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
        9px;

}


.hh-orders-floating.one {

    top:
        4px;

    left:
        0;

}


.hh-orders-floating.two {

    right:
        0;

    bottom:
        4px;

}


/* ================================================================
   FLASH
================================================================ */

.hh-orders-alert {

    margin-bottom:
        18px;

    padding:
        14px
        16px;

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    border-radius:
        12px;

    font-size:
        9px;

    font-weight:
        700;

}


.hh-orders-alert.success {

    color:
        #166534;

    background:
        #f0fdf4;

    border:
        1px solid #bbf7d0;

}


.hh-orders-alert.error {

    color:
        #991b1b;

    background:
        #fef2f2;

    border:
        1px solid #fecaca;

}


/* ================================================================
   STATS
================================================================ */

.hh-orders-stats {

    margin-bottom:
        22px;

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
        14px;

}


.hh-orders-stat {

    position:
        relative;

    min-height:
        101px;

    padding:
        17px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    background:
        #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius:
        17px;

    box-shadow:

        0
        8px
        24px
        rgba(40,65,120,.045);

}


.hh-orders-stat::after {

    content:
        "";

    position:
        absolute;

    width:
        75px;

    height:
        75px;

    right:
        -25px;

    bottom:
        -35px;

    border-radius:
        50%;

    background:
        #f2f6ff;

}


.hh-orders-stat-icon {

    width:
        45px;

    height:
        45px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        12px;

    font-size:
        15px;

}


.hh-orders-stat-icon.blue {

    color:
        #2563eb;

    background:
        #eff6ff;

}


.hh-orders-stat-icon.orange {

    color:
        #c2410c;

    background:
        #fff7ed;

}


.hh-orders-stat-icon.green {

    color:
        #15803d;

    background:
        #ecfdf3;

}


.hh-orders-stat-icon.red {

    color:
        #dc2626;

    background:
        #fef2f2;

}


.hh-orders-stat-copy {

    position:
        relative;

    z-index:
        2;

}


.hh-orders-stat-copy span {

    display:
        block;

    margin-bottom:
        4px;

    color:
        #8a98aa;

    font-size:
        6px;

    font-weight:
        850;

    letter-spacing:
        .7px;

}


.hh-orders-stat-copy strong {

    display:
        block;

    color:
        #17233c;

    font-size:
        20px;

    font-weight:
        900;

}


/* ================================================================
   ORDERS PANEL
================================================================ */

.hh-orders-panel {

    overflow:
        hidden;

    background:
        #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius:
        21px;

    box-shadow:

        0
        11px
        30px
        rgba(40,65,120,.05);

}


.hh-orders-panel-header {

    min-height:
        94px;

    padding:
        20px
        22px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        18px;

    border-bottom:
        1px solid #edf1f5;

}


.hh-orders-panel-title {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


.hh-orders-panel-icon {

    width:
        47px;

    height:
        47px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #438bf2
        );

    border-radius:
        13px;

    box-shadow:

        0
        8px
        18px
        rgba(37,99,235,.17);

    font-size:
        15px;

}


.hh-orders-panel-title small {

    display:
        block;

    margin-bottom:
        2px;

    color:
        #2563eb;

    font-size:
        6px;

    font-weight:
        900;

    letter-spacing:
        .8px;

}


.hh-orders-panel-title h2 {

    margin:
        0
        0
        3px;

    color:
        #17233c;

    font-size:
        15px;

    font-weight:
        900;

}


.hh-orders-panel-title p {

    margin:
        0;

    color:
        #8b98aa;

    font-size:
        7px;

}


.hh-orders-count {

    min-height:
        31px;

    padding:
        0
        11px;

    display:
        inline-flex;

    align-items:
        center;

    color:
        #2563eb;

    background:
        #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius:
        999px;

    font-size:
        7px;

    font-weight:
        900;

}


/* ================================================================
   ORDER LIST
================================================================ */

.hh-orders-list {

    padding:
        18px;

    display:
        flex;

    flex-direction:
        column;

    gap:
        12px;

    background:
        #f9fbfe;

}


/* ================================================================
   ORDER CARD
================================================================ */

.hh-order-card {

    position:
        relative;

    overflow:
        hidden;

    background:
        #ffffff;

    border:
        1px solid #e1e8f2;

    border-radius:
        16px;

    transition:
        transform .18s ease,
        border-color .18s ease,
        box-shadow .18s ease;

}


.hh-order-card:hover {

    transform:
        translateY(-2px);

    border-color:
        #c8dcf5;

    box-shadow:

        0
        12px
        27px
        rgba(40,65,120,.08);

}


.hh-order-card-top {

    padding:
        16px
        18px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    border-bottom:
        1px solid #edf1f5;

}


.hh-order-number {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

}


.hh-order-number-icon {

    width:
        43px;

    height:
        43px;

    flex-shrink:
        0;

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
        15px;

}


.hh-order-number small {

    display:
        block;

    margin-bottom:
        2px;

    color:
        #8b98aa;

    font-size:
        6px;

    font-weight:
        850;

    letter-spacing:
        .7px;

}


.hh-order-number strong {

    color:
        #17233c;

    font-size:
        13px;

    font-weight:
        900;

}


/* ================================================================
   BADGES
================================================================ */

.hh-order-badges {

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    justify-content:
        flex-end;

    gap:
        7px;

}


.hh-order-status {

    min-height:
        29px;

    padding:
        0
        9px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    border-radius:
        999px;

    font-size:
        6px;

    font-weight:
        900;

}


.hh-order-status.pending {

    color:
        #b45309;

    background:
        #fff7ed;

}


.hh-order-status.processing {

    color:
        #1d4ed8;

    background:
        #eff6ff;

}


.hh-order-status.success {

    color:
        #15803d;

    background:
        #ecfdf3;

}


.hh-order-status.danger {

    color:
        #dc2626;

    background:
        #fef2f2;

}


.hh-payment-status {

    min-height:
        29px;

    padding:
        0
        9px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    border-radius:
        999px;

    font-size:
        6px;

    font-weight:
        900;

}


.hh-payment-status.success {

    color:
        #15803d;

    background:
        #ecfdf3;

}


.hh-payment-status.pending {

    color:
        #7c3aed;

    background:
        #f5f3ff;

}


.hh-payment-status.danger {

    color:
        #dc2626;

    background:
        #fef2f2;

}


/* ================================================================
   ORDER BODY
================================================================ */

.hh-order-card-body {

    padding:
        17px
        18px;

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


.hh-order-info {

    min-width:
        0;

    padding:
        12px;

    background:
        #fbfdff;

    border:
        1px solid #e7ecf3;

    border-radius:
        11px;

}


.hh-order-info-icon {

    width:
        31px;

    height:
        31px;

    margin-bottom:
        8px;

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
        8px;

    font-size:
        11px;

}


.hh-order-info span {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #96a2b2;

    font-size:
        5px;

    font-weight:
        900;

    letter-spacing:
        .6px;

}


.hh-order-info strong {

    display:
        block;

    overflow:
        hidden;

    color:
        #354760;

    font-size:
        8px;

    font-weight:
        850;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

}


.hh-order-info.total strong {

    color:
        #1d5dcc;

    font-size:
        11px;

}


/* ================================================================
   BOTTOM
================================================================ */

.hh-order-card-bottom {

    padding:
        14px
        18px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    background:
        #fcfdff;

    border-top:
        1px solid #edf1f5;

}


.hh-order-progress {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        #76869c;

    font-size:
        7px;

}


.hh-order-progress-icon {

    width:
        30px;

    height:
        30px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        8px;

}


.hh-order-progress-icon.pending {

    color:
        #b45309;

    background:
        #fff7ed;

}


.hh-order-progress-icon.processing {

    color:
        #2563eb;

    background:
        #eff6ff;

}


.hh-order-progress-icon.success {

    color:
        #15803d;

    background:
        #ecfdf3;

}


.hh-order-progress-icon.danger {

    color:
        #dc2626;

    background:
        #fef2f2;

}


.hh-view-order {

    min-height:
        38px;

    padding:
        0
        13px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #377fef
        );

    border-radius:
        9px;

    box-shadow:

        0
        7px
        16px
        rgba(37,99,235,.17);

    font-size:
        7px;

    font-weight:
        900;

    text-decoration:
        none;

    transition:
        transform .18s ease;

}


.hh-view-order:hover {

    color:
        #ffffff;

    transform:
        translateY(-1px);

}


/* ================================================================
   EMPTY
================================================================ */

.hh-orders-empty {

    min-height:
        420px;

    padding:
        55px
        25px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    text-align:
        center;

    background:
        #ffffff;

}


.hh-orders-empty-art {

    position:
        relative;

    width:
        150px;

    height:
        145px;

    margin-bottom:
        18px;

}


.hh-orders-empty-main {

    position:
        absolute;

    width:
        105px;

    height:
        105px;

    top:
        18px;

    left:
        22px;

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

    border:
        1px solid #dbeafe;

    border-radius:
        28px;

    font-size:
        42px;

    transform:
        rotate(-5deg);

}


.hh-orders-empty-art::before {

    content:
        "";

    position:
        absolute;

    width:
        38px;

    height:
        38px;

    right:
        0;

    top:
        7px;

    background:
        #f5f3ff;

    border-radius:
        11px;

    transform:
        rotate(8deg);

}


.hh-orders-empty-art::after {

    content:
        "";

    position:
        absolute;

    width:
        30px;

    height:
        30px;

    left:
        0;

    bottom:
        8px;

    background:
        #ecfdf3;

    border-radius:
        9px;

    transform:
        rotate(-8deg);

}


.hh-orders-empty small {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #2563eb;

    font-size:
        7px;

    font-weight:
        900;

    letter-spacing:
        .9px;

}


.hh-orders-empty h2 {

    margin:
        0;

    color:
        #17233c;

    font-size:
        24px;

    font-weight:
        900;

}


.hh-orders-empty p {

    max-width:
        460px;

    margin:
        10px
        auto
        18px;

    color:
        #8291a5;

    font-size:
        9px;

    line-height:
        1.7;

}


.hh-orders-empty a {

    min-height:
        42px;

    padding:
        0
        15px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        #2563eb;

    border-radius:
        10px;

    font-size:
        8px;

    font-weight:
        900;

    text-decoration:
        none;

}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1050px) {

    .hh-orders-stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .hh-order-card-body {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media (max-width: 850px) {

    .hh-orders-page {

        padding:
            30px
            18px
            60px;

    }


    .hh-orders-hero {

        grid-template-columns:
            1fr;

        min-height:
            auto;

        padding:
            37px;

    }


    .hh-orders-art {

        display:
            none;

    }

}


@media (max-width: 650px) {

    .hh-orders-page {

        padding:
            21px
            13px
            50px;

    }


    .hh-orders-hero {

        padding:
            28px
            23px;

        border-radius:
            21px;

    }


    .hh-orders-hero h1 {

        font-size:
            30px;

    }


    .hh-orders-hero-actions {

        flex-direction:
            column;

    }


    .hh-orders-shop,
    .hh-orders-wishlist {

        width:
            100%;

    }


    .hh-orders-stats {

        grid-template-columns:
            1fr;

    }


    .hh-orders-panel-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .hh-order-card-top {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .hh-order-badges {

        justify-content:
            flex-start;

    }


    .hh-order-card-body {

        grid-template-columns:
            1fr;

    }


    .hh-order-card-bottom {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .hh-view-order {

        width:
            100%;

    }

}

</style>


<!-- ===============================================================
     MY ORDERS
================================================================ -->

<main class="hh-orders-page">


    <div class="hh-orders-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-orders-hero">


            <div class="hh-orders-hero-copy">


                <span class="hh-orders-pill">

                    <i class="bi bi-box-seam"></i>

                    CUSTOMER ORDER CENTER

                </span>


                <h1>

                    Keep Track of

                    <span>
                        Every Order.
                    </span>

                </h1>


                <p>

                    View your HochipoHub purchase history,
                    check payment and delivery status,
                    track seller progress and open full
                    order details from one place.

                </p>


                <div class="hh-orders-hero-actions">


                    <a
                        href="catalog.php"
                        class="hh-orders-shop"
                    >

                        <i class="bi bi-bag"></i>

                        Continue Shopping

                    </a>


                    <a
                        href="wishlist.php"
                        class="hh-orders-wishlist"
                    >

                        <i class="bi bi-heart"></i>

                        My Wishlist

                    </a>


                </div>


            </div>



            <!-- HERO VISUAL -->

            <div class="hh-orders-art">


                <div class="hh-orders-main-icon">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="hh-orders-floating one">

                    <i class="bi bi-receipt"></i>

                    <span>

                        <?= number_format(
                            $totalOrders
                        ) ?>

                        total orders

                    </span>

                </div>


                <div class="hh-orders-floating two">

                    <i class="bi bi-check-circle"></i>

                    <span>

                        <?= number_format(
                            $completedOrders
                        ) ?>

                        completed

                    </span>

                </div>


            </div>


        </section>



        <!-- =======================================================
             FLASH
        ======================================================== -->

        <?php if (
            !empty(
                $_SESSION['success']
            )
        ): ?>


            <div class="
                hh-orders-alert
                success
            ">

                <i class="bi bi-check-circle-fill"></i>

                <?= ordersEscape(
                    $_SESSION['success']
                ) ?>

            </div>


            <?php

            unset(
                $_SESSION['success']
            );

            ?>


        <?php endif; ?>



        <?php if (
            !empty(
                $_SESSION['error']
            )
        ): ?>


            <div class="
                hh-orders-alert
                error
            ">

                <i class="bi bi-exclamation-circle-fill"></i>

                <?= ordersEscape(
                    $_SESSION['error']
                ) ?>

            </div>


            <?php

            unset(
                $_SESSION['error']
            );

            ?>


        <?php endif; ?>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="hh-orders-stats">


            <!-- TOTAL -->

            <article class="hh-orders-stat">


                <div class="
                    hh-orders-stat-icon
                    blue
                ">

                    <i class="bi bi-receipt"></i>

                </div>


                <div class="hh-orders-stat-copy">

                    <span>
                        TOTAL ORDERS
                    </span>

                    <strong>

                        <?= number_format(
                            $totalOrders
                        ) ?>

                    </strong>

                </div>


            </article>



            <!-- IN PROGRESS -->

            <article class="hh-orders-stat">


                <div class="
                    hh-orders-stat-icon
                    orange
                ">

                    <i class="bi bi-clock-history"></i>

                </div>


                <div class="hh-orders-stat-copy">

                    <span>
                        IN PROGRESS
                    </span>

                    <strong>

                        <?= number_format(
                            $pendingOrders
                        ) ?>

                    </strong>

                </div>


            </article>



            <!-- COMPLETE -->

            <article class="hh-orders-stat">


                <div class="
                    hh-orders-stat-icon
                    green
                ">

                    <i class="bi bi-check-circle"></i>

                </div>


                <div class="hh-orders-stat-copy">

                    <span>
                        COMPLETED
                    </span>

                    <strong>

                        <?= number_format(
                            $completedOrders
                        ) ?>

                    </strong>

                </div>


            </article>



            <!-- CANCELLED -->

            <article class="hh-orders-stat">


                <div class="
                    hh-orders-stat-icon
                    red
                ">

                    <i class="bi bi-x-circle"></i>

                </div>


                <div class="hh-orders-stat-copy">

                    <span>
                        CANCELLED
                    </span>

                    <strong>

                        <?= number_format(
                            $cancelledOrders
                        ) ?>

                    </strong>

                </div>


            </article>


        </section>



        <!-- =======================================================
             ORDER HISTORY
        ======================================================== -->

        <section class="hh-orders-panel">


            <!-- HEADER -->

            <div class="hh-orders-panel-header">


                <div class="hh-orders-panel-title">


                    <div class="hh-orders-panel-icon">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <div>

                        <small>
                            ORDER HISTORY
                        </small>

                        <h2>
                            Your Orders
                        </h2>

                        <p>

                            Review your purchases,
                            delivery and payment progress.

                        </p>

                    </div>


                </div>


                <span class="hh-orders-count">

                    <?= number_format(
                        $totalOrders
                    ) ?>

                    order<?= $totalOrders !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>



            <!-- ===================================================
                 EMPTY
            ==================================================== -->

            <?php if (
                empty(
                    $orders
                )
            ): ?>


                <div class="hh-orders-empty">


                    <div class="hh-orders-empty-art">

                        <div class="hh-orders-empty-main">

                            <i class="bi bi-box-seam"></i>

                        </div>

                    </div>


                    <small>
                        START YOUR JOURNEY
                    </small>


                    <h2>

                        No orders yet

                    </h2>


                    <p>

                        You haven't placed an order yet.
                        Explore products from HochipoHub
                        sellers and your purchase history
                        will appear here.

                    </p>


                    <a href="catalog.php">

                        <i class="bi bi-bag"></i>

                        Start Shopping

                    </a>


                </div>



            <!-- ===================================================
                 ORDERS
            ==================================================== -->

            <?php else: ?>


                <div class="hh-orders-list">


                    <?php foreach (
                        $orders
                        as $order
                    ): ?>


                        <?php

                        $orderId =
                            (int)
                            $order[
                                'order_id'
                            ];


                        $orderStatus =
                            $order[
                                'order_status'
                            ]
                            ?? 'Pending';


                        $orderStatusClass =
                            ordersStatusClass(
                                $orderStatus
                            );


                        $orderStatusIcon =
                            ordersStatusIcon(
                                $orderStatus
                            );


                        $orderMessage =
                            ordersStatusMessage(
                                $orderStatus
                            );


                        $paymentStatus =
                            $order[
                                'payment_status'
                            ]
                            ?? 'Pending';


                        $paymentClass =
                            ordersPaymentClass(
                                $paymentStatus
                            );


                        $paymentMethod =
                            $order[
                                'payment_method'
                            ]
                            ?? '—';


                        $tracking =
                            trim(
                                (string) (
                                    $order[
                                        'tracking_number'
                                    ]
                                    ?? ''
                                )
                            );

                        ?>


                        <article class="hh-order-card">


                            <!-- ===============================
                                 TOP
                            ================================ -->

                            <div class="hh-order-card-top">


                                <div class="hh-order-number">


                                    <div class="hh-order-number-icon">

                                        <i class="bi bi-receipt"></i>

                                    </div>


                                    <div>

                                        <small>
                                            ORDER NUMBER
                                        </small>


                                        <strong>

                                            #<?= $orderId ?>

                                        </strong>

                                    </div>


                                </div>



                                <div class="hh-order-badges">


                                    <!-- PAYMENT -->

                                    <span
                                        class="
                                            hh-payment-status
                                            <?= ordersEscape(
                                                $paymentClass
                                            ) ?>
                                        "
                                    >

                                        <i class="bi bi-credit-card"></i>

                                        <?= ordersEscape(
                                            $paymentStatus
                                        ) ?>

                                    </span>



                                    <!-- ORDER STATUS -->

                                    <span
                                        class="
                                            hh-order-status
                                            <?= ordersEscape(
                                                $orderStatusClass
                                            ) ?>
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                <?= ordersEscape(
                                                    $orderStatusIcon
                                                ) ?>
                                            "
                                        ></i>


                                        <?= ordersEscape(
                                            $orderStatus
                                        ) ?>

                                    </span>


                                </div>


                            </div>



                            <!-- ===============================
                                 INFORMATION
                            ================================ -->

                            <div class="hh-order-card-body">


                                <!-- DATE -->

                                <div class="hh-order-info">


                                    <div class="hh-order-info-icon">

                                        <i class="bi bi-calendar3"></i>

                                    </div>


                                    <span>
                                        ORDER DATE
                                    </span>


                                    <strong>

                                        <?= ordersEscape(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $order[
                                                        'order_date'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                        ·

                                        <?= ordersEscape(
                                            date(
                                                'h:i A',
                                                strtotime(
                                                    $order[
                                                        'order_date'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </strong>


                                </div>



                                <!-- DELIVERY -->

                                <div class="hh-order-info">


                                    <div class="hh-order-info-icon">

                                        <?php if (
                                            $order[
                                                'delivery_method'
                                            ] === 'Postage'
                                        ): ?>

                                            <i class="bi bi-truck"></i>

                                        <?php else: ?>

                                            <i class="bi bi-shop"></i>

                                        <?php endif; ?>

                                    </div>


                                    <span>
                                        DELIVERY
                                    </span>


                                    <strong>

                                        <?= ordersEscape(
                                            $order[
                                                'delivery_method'
                                            ]
                                            ?? '—'
                                        ) ?>

                                    </strong>


                                </div>



                                <!-- PAYMENT -->

                                <div class="hh-order-info">


                                    <div class="hh-order-info-icon">

                                        <i class="bi bi-credit-card"></i>

                                    </div>


                                    <span>
                                        PAYMENT METHOD
                                    </span>


                                    <strong>

                                        <?= ordersEscape(
                                            $paymentMethod
                                        ) ?>

                                    </strong>


                                </div>



                                <!-- TOTAL -->

                                <div class="
                                    hh-order-info
                                    total
                                ">


                                    <div class="hh-order-info-icon">

                                        <i class="bi bi-cash-stack"></i>

                                    </div>


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


                            </div>



                            <!-- ===============================
                                 BOTTOM
                            ================================ -->

                            <div class="hh-order-card-bottom">


                                <div class="hh-order-progress">


                                    <div
                                        class="
                                            hh-order-progress-icon
                                            <?= ordersEscape(
                                                $orderStatusClass
                                            ) ?>
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                <?= ordersEscape(
                                                    $orderStatusIcon
                                                ) ?>
                                            "
                                        ></i>

                                    </div>


                                    <div>

                                        <?= ordersEscape(
                                            $orderMessage
                                        ) ?>


                                        <?php if (
                                            $tracking !== ''
                                        ): ?>


                                            <br>

                                            <strong>

                                                Tracking:
                                                <?= ordersEscape(
                                                    $tracking
                                                ) ?>

                                            </strong>


                                        <?php endif; ?>


                                    </div>


                                </div>



                                <a
                                    href="order_details.php?id=<?= $orderId ?>"
                                    class="hh-view-order"
                                >

                                    View Details

                                    <i class="bi bi-arrow-right"></i>

                                </a>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


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