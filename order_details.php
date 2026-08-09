<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ORDER DETAILS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    redirect(
        baseUrl('index.php')
    );
}


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$orderId || $orderId <= 0) {
    redirect(
        baseUrl('order.php')
    );
}


/*
|--------------------------------------------------------------------------
| FETCH MAIN ORDER
|--------------------------------------------------------------------------
|
| Customer can ONLY access their own order.
|
*/

$orderStmt = $db->prepare("
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
        ON o.customer_id = u.user_id

    WHERE o.order_id = ?
    AND o.customer_id = ?

    LIMIT 1
");

$orderStmt->execute([
    $orderId,
    $userId
]);

$order = $orderStmt->fetch();


if (!$order) {

    http_response_code(404);

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
            Order Not Found | <?= e(APP_NAME) ?>
        </title>

        <link
            rel="stylesheet"
            href="<?= assetUrl('css/style.css') ?>"
        >

        <link
            rel="stylesheet"
            href="<?= assetUrl('css/responsive.css') ?>"
        >

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

    </head>

    <body>

        <?php
        require_once __DIR__ . '/includes/navbar.php';
        ?>

        <main>

            <section
                class="home-section"
                style="min-height:70vh;display:flex;align-items:center;"
            >

                <div
                    class="section-container"
                    style="text-align:center;"
                >

                    <div
                        style="
                            width:90px;
                            height:90px;
                            margin:0 auto 25px;
                            border-radius:28px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            background:rgba(37,99,235,.12);
                            color:#3b82f6;
                            font-size:36px;
                        "
                    >

                        <i class="fa-solid fa-receipt"></i>

                    </div>

                    <h1>
                        Order Not Found
                    </h1>

                    <p>
                        This order does not exist or you do not have
                        permission to view it.
                    </p>

                    <a
                        href="<?= baseUrl('order.php') ?>"
                        class="btn-primary"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Back To Orders

                    </a>

                </div>

            </section>

        </main>

        <?php
        require_once __DIR__ . '/includes/footer.php';
        ?>

    </body>
    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH ORDER ITEMS
|--------------------------------------------------------------------------
*/

$itemStmt = $db->prepare("
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
        ON od.product_id = p.product_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    WHERE od.order_id = ?

    ORDER BY od.order_detail_id ASC
");

$itemStmt->execute([
    $orderId
]);

$orderItems = $itemStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| FETCH VENDOR ORDERS
|--------------------------------------------------------------------------
*/

$vendorOrderStmt = $db->prepare("
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
        ON vo.vendor_id = v.vendor_id

    WHERE vo.order_id = ?

    ORDER BY vo.vendor_order_id ASC
");

$vendorOrderStmt->execute([
    $orderId
]);

$vendorOrders = $vendorOrderStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| FETCH PAYMENT
|--------------------------------------------------------------------------
*/

$paymentStmt = $db->prepare("
    SELECT

        payment_id,
        payment_method,
        payment_status,
        payment_date,
        amount,
        transaction_reference

    FROM payments

    WHERE order_id = ?

    ORDER BY payment_id DESC

    LIMIT 1
");

$paymentStmt->execute([
    $orderId
]);

$payment = $paymentStmt->fetch();


/*
|--------------------------------------------------------------------------
| STATUS HELPER
|--------------------------------------------------------------------------
*/

$orderStatus = $order['order_status'] ?? 'Pending';

$orderStatusClass = match ($orderStatus) {

    'Completed' => 'status-success',

    'Processing' => 'status-processing',

    'Cancelled' => 'status-cancelled',

    default => 'status-pending'

};


/*
|--------------------------------------------------------------------------
| PAYMENT STATUS
|--------------------------------------------------------------------------
*/

$paymentStatus = $payment['payment_status'] ?? 'Pending';

$paymentStatusClass = match ($paymentStatus) {

    'Paid' => 'status-success',

    'Failed',
    'Refunded' => 'status-cancelled',

    default => 'status-pending'

};


/*
|--------------------------------------------------------------------------
| DELIVERY ADDRESS
|--------------------------------------------------------------------------
*/

$deliveryAddress = trim(
    (string) (
        $order['delivery_address'] ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| ORDER DATE
|--------------------------------------------------------------------------
*/

$orderDate = !empty(
    $order['order_date']
)
    ? date(
        'd M Y, h:i A',
        strtotime(
            $order['order_date']
        )
    )
    : '-';


/*
|--------------------------------------------------------------------------
| TOTAL ITEMS
|--------------------------------------------------------------------------
*/

$totalItems = 0;

foreach (
    $orderItems as $item
) {

    $totalItems += (int) $item['quantity'];

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

        Order #<?= (int) $order['order_id'] ?>

        |

        <?= e(APP_NAME) ?>

    </title>


    <meta
        name="description"
        content="View your HochipoHub order details."
    >


    <link
        rel="stylesheet"
        href="<?= assetUrl('css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= assetUrl('css/responsive.css') ?>"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        .order-details-page {
            padding: 50px 0 90px;
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(37, 99, 235, .10),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 30%,
                    rgba(59, 130, 246, .08),
                    transparent 28%
                );
            min-height: 100vh;
        }


        .order-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 22px;
        }


        .order-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }


        .order-breadcrumb {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 13px;
            color: #64748b;
            font-size: 13px;
        }


        .order-breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }


        .order-title {
            margin: 0;
            color: #0f172a;
            font-size: clamp(30px, 5vw, 46px);
            line-height: 1;
            letter-spacing: -1.8px;
        }


        .order-title span {
            color: #2563eb;
        }


        .order-date {
            margin-top: 10px;
            color: #64748b;
            font-size: 14px;
        }


        .order-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 12px 17px;
            border-radius: 13px;
            background: #fff;
            border: 1px solid #dbeafe;
            color: #1e3a8a;
            text-decoration: none;
            font-weight: 800;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .06);
            transition: .2s ease;
        }


        .order-back-btn:hover {
            transform: translateY(-2px);
            border-color: #60a5fa;
            box-shadow: 0 12px 30px rgba(37, 99, 235, .12);
        }


        .order-status-hero {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            padding: 27px;
            margin-bottom: 25px;
            background:
                linear-gradient(
                    135deg,
                    #0f172a,
                    #172554 58%,
                    #1d4ed8
                );
            color: #fff;
            box-shadow:
                0 20px 50px rgba(15, 23, 42, .18);
        }


        .order-status-hero::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            right: -70px;
            top: -100px;
            background: rgba(96, 165, 250, .18);
        }


        .order-status-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }


        .status-label {
            color: #93c5fd;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }


        .status-main {
            margin-top: 6px;
            font-size: 28px;
            font-weight: 900;
        }


        .status-description {
            margin-top: 6px;
            color: #cbd5e1;
            font-size: 14px;
        }


        .big-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }


        .big-status-badge i {
            font-size: 9px;
        }


        .big-status-badge.status-success {
            background: rgba(34, 197, 94, .16);
            color: #86efac;
            border: 1px solid rgba(134, 239, 172, .22);
        }


        .big-status-badge.status-processing {
            background: rgba(96, 165, 250, .16);
            color: #bfdbfe;
            border: 1px solid rgba(191, 219, 254, .22);
        }


        .big-status-badge.status-pending {
            background: rgba(250, 204, 21, .14);
            color: #fde68a;
            border: 1px solid rgba(253, 230, 138, .20);
        }


        .big-status-badge.status-cancelled {
            background: rgba(248, 113, 113, .14);
            color: #fecaca;
            border: 1px solid rgba(254, 202, 202, .20);
        }


        .order-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(280px, .8fr);
            gap: 24px;
            align-items: start;
        }


        .order-card {
            background: rgba(255,255,255,.94);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 23px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .06);
            margin-bottom: 22px;
        }


        .order-card-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }


        .order-card-heading h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
        }


        .order-card-heading p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }


        .items-list {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }


        .order-item {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr) auto;
            gap: 15px;
            align-items: center;
            padding: 13px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            transition: .2s ease;
        }


        .order-item:hover {
            border-color: #bfdbfe;
            transform: translateY(-1px);
        }


        .order-item-image {
            width: 78px;
            height: 78px;
            border-radius: 15px;
            overflow: hidden;
            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #eff6ff
                );
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 24px;
        }


        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .order-item-name {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
        }


        .order-item-vendor {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 12px;
        }


        .order-item-vendor i {
            color: #2563eb;
        }


        .order-item-qty {
            margin-top: 9px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }


        .order-item-price {
            text-align: right;
        }


        .order-item-price strong {
            display: block;
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
        }


        .order-item-price span {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
        }


        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 9px 0;
            color: #64748b;
            font-size: 14px;
        }


        .summary-row strong {
            color: #0f172a;
        }


        .summary-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 9px 0;
        }


        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding-top: 12px;
        }


        .summary-total span {
            color: #0f172a;
            font-weight: 900;
        }


        .summary-total strong {
            color: #2563eb;
            font-size: 25px;
            font-weight: 950;
        }


        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 13px;
        }


        .info-box {
            padding: 15px;
            border-radius: 17px;
            background: #f8fafc;
            border: 1px solid #eef2f7;
        }


        .info-box.full {
            grid-column: 1 / -1;
        }


        .info-box-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 7px;
        }


        .info-box-label i {
            color: #2563eb;
        }


        .info-box-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.55;
        }


        .payment-method {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .payment-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #2563eb;
        }


        .payment-status {
            margin-top: 3px;
            font-size: 11px;
            font-weight: 900;
        }


        .payment-status.status-success {
            color: #16a34a;
        }


        .payment-status.status-pending {
            color: #d97706;
        }


        .payment-status.status-cancelled {
            color: #dc2626;
        }


        .vendor-order {
            padding: 17px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            margin-bottom: 13px;
            background: #fff;
        }


        .vendor-order:last-child {
            margin-bottom: 0;
        }


        .vendor-order-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }


        .vendor-order-name {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .vendor-order-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .vendor-order-name strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
        }


        .vendor-order-name span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 11px;
        }


        .vendor-status {
            padding: 7px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }


        .vendor-order-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }


        .vendor-meta-box {
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fafc;
        }


        .vendor-meta-box span {
            display: block;
            color: #64748b;
            font-size: 10px;
        }


        .vendor-meta-box strong {
            display: block;
            margin-top: 3px;
            color: #0f172a;
            font-size: 13px;
        }


        .tracking-box {
            margin-top: 12px;
            padding: 11px 13px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 12px;
            font-weight: 800;
        }


        .empty-items {
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
        }


        .empty-items i {
            color: #93c5fd;
            font-size: 35px;
            margin-bottom: 13px;
        }


        @media (max-width: 900px) {

            .order-layout {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 650px) {

            .order-details-page {
                padding-top: 30px;
            }


            .order-topbar {
                flex-direction: column;
            }


            .order-status-content {
                flex-direction: column;
                align-items: flex-start;
            }


            .order-status-hero {
                padding: 22px;
            }


            .order-card {
                padding: 17px;
                border-radius: 20px;
            }


            .order-item {
                grid-template-columns: 62px minmax(0, 1fr);
            }


            .order-item-image {
                width: 62px;
                height: 62px;
            }


            .order-item-price {
                grid-column: 2;
                text-align: left;
                padding-top: 2px;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .info-box.full {
                grid-column: auto;
            }


            .vendor-order-top {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body class="order-details-page-body">


<?php
require_once __DIR__ . '/includes/navbar.php';
?>


<main class="order-details-page">

    <div class="order-details-container">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <div class="order-topbar">

            <div>

                <div class="order-breadcrumb">

                    <a href="<?= baseUrl('order.php') ?>">
                        My Orders
                    </a>

                    <i class="fa-solid fa-chevron-right"></i>

                    <span>
                        Order Details
                    </span>

                </div>


                <h1 class="order-title">

                    Order
                    <span>
                        #<?= (int) $order['order_id'] ?>
                    </span>

                </h1>


                <p class="order-date">

                    <i class="fa-regular fa-calendar"></i>

                    Placed on <?= e($orderDate) ?>

                </p>

            </div>


            <a
                href="<?= baseUrl('order.php') ?>"
                class="order-back-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back To Orders

            </a>

        </div>



        <!-- =====================================================
             STATUS HERO
        ====================================================== -->

        <section class="order-status-hero">

            <div class="order-status-content">

                <div>

                    <div class="status-label">
                        ORDER STATUS
                    </div>


                    <div class="status-main">

                        <?= e($orderStatus) ?>

                    </div>


                    <div class="status-description">

                        <?php if ($orderStatus === 'Pending'): ?>

                            Your order has been received and is
                            waiting to be processed.

                        <?php elseif ($orderStatus === 'Processing'): ?>

                            Your order is currently being prepared.

                        <?php elseif ($orderStatus === 'Completed'): ?>

                            Your order has been completed successfully.

                        <?php elseif ($orderStatus === 'Cancelled'): ?>

                            This order has been cancelled.

                        <?php else: ?>

                            Your order status has been updated.

                        <?php endif; ?>

                    </div>

                </div>


                <div class="
                    big-status-badge
                    <?= e($orderStatusClass) ?>
                ">

                    <i class="fa-solid fa-circle"></i>

                    <?= e($orderStatus) ?>

                </div>

            </div>

        </section>



        <!-- =====================================================
             MAIN LAYOUT
        ====================================================== -->

        <div class="order-layout">


            <!-- =================================================
                 LEFT COLUMN
            ================================================== -->

            <div>


                <!-- =============================================
                     ORDER ITEMS
                ============================================== -->

                <section class="order-card">

                    <div class="order-card-heading">

                        <div>

                            <h2>
                                Items In This Order
                            </h2>

                            <p>
                                <?= $totalItems ?>
                                item<?= $totalItems === 1 ? '' : 's' ?>
                            </p>

                        </div>

                    </div>


                    <?php if (!empty($orderItems)): ?>

                        <div class="items-list">

                            <?php foreach ($orderItems as $item): ?>

                                <div class="order-item">


                                    <a
                                        href="<?= baseUrl(
                                            'product_details.php?id='
                                            . (int) $item['product_id']
                                        ) ?>"
                                        class="order-item-image"
                                    >

                                        <?php if (
                                            !empty(
                                                $item['image']
                                            )
                                        ): ?>

                                            <img
                                                src="<?= productImageUrl(
                                                    $item['image']
                                                ) ?>"
                                                alt="<?= e(
                                                    $item['product_name']
                                                ) ?>"
                                            >

                                        <?php else: ?>

                                            <i class="fa-solid fa-box"></i>

                                        <?php endif; ?>

                                    </a>


                                    <div>

                                        <h3 class="order-item-name">

                                            <?= e(
                                                $item['product_name']
                                            ) ?>

                                        </h3>


                                        <div class="order-item-vendor">

                                            <i
                                                class="fa-solid fa-store"
                                            ></i>

                                            <?= e(
                                                $item['business_name']
                                            ) ?>

                                        </div>


                                        <div class="order-item-qty">

                                            Qty:
                                            <?= (int) $item['quantity'] ?>

                                            ×

                                            <?= formatPrice(
                                                $item['unit_price']
                                            ) ?>

                                        </div>

                                    </div>


                                    <div class="order-item-price">

                                        <strong>

                                            <?= formatPrice(
                                                $item['subtotal']
                                            ) ?>

                                        </strong>

                                        <span>
                                            Subtotal
                                        </span>

                                    </div>


                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-items">

                            <i class="fa-solid fa-box-open"></i>

                            <h3>
                                No order items found
                            </h3>

                        </div>

                    <?php endif; ?>

                </section>



                <!-- =============================================
                     VENDOR ORDERS
                ============================================== -->

                <?php if (!empty($vendorOrders)): ?>

                    <section class="order-card">

                        <div class="order-card-heading">

                            <div>

                                <h2>
                                    Vendor Orders
                                </h2>

                                <p>
                                    Your order is separated by vendor.
                                </p>

                            </div>

                        </div>


                        <?php foreach (
                            $vendorOrders
                            as $vendorOrder
                        ): ?>

                            <div class="vendor-order">

                                <div class="vendor-order-top">

                                    <div class="vendor-order-name">

                                        <div class="vendor-order-icon">

                                            <i class="fa-solid fa-store"></i>

                                        </div>


                                        <div>

                                            <strong>

                                                <?= e(
                                                    $vendorOrder[
                                                        'business_name'
                                                    ]
                                                ) ?>

                                            </strong>

                                            <span>

                                                Vendor Order #

                                                <?= (int)
                                                    $vendorOrder[
                                                        'vendor_order_id'
                                                    ] ?>

                                            </span>

                                        </div>

                                    </div>


                                    <span class="vendor-status">

                                        <?= e(
                                            $vendorOrder[
                                                'vendor_status'
                                            ]
                                        ) ?>

                                    </span>

                                </div>


                                <div class="vendor-order-meta">

                                    <div class="vendor-meta-box">

                                        <span>
                                            Subtotal
                                        </span>

                                        <strong>

                                            <?= formatPrice(
                                                $vendorOrder[
                                                    'subtotal'
                                                ]
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="vendor-meta-box">

                                        <span>
                                            Delivery Fee
                                        </span>

                                        <strong>

                                            <?= formatPrice(
                                                $vendorOrder[
                                                    'delivery_fee'
                                                ] ?? 0
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>


                                <?php if (
                                    !empty(
                                        $vendorOrder[
                                            'tracking_number'
                                        ]
                                    )
                                ): ?>

                                    <div class="tracking-box">

                                        <i
                                            class="fa-solid fa-truck-fast"
                                        ></i>

                                        Tracking:

                                        <?= e(
                                            $vendorOrder[
                                                'tracking_number'
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </section>

                <?php endif; ?>

            </div>



            <!-- =================================================
                 RIGHT COLUMN
            ================================================== -->

            <aside>


                <!-- =============================================
                     ORDER SUMMARY
                ============================================== -->

                <section class="order-card">

                    <div class="order-card-heading">

                        <div>

                            <h2>
                                Order Summary
                            </h2>

                        </div>

                    </div>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <strong>
                            <?= $totalItems ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Delivery
                        </span>

                        <strong>

                            <?= e(
                                $order[
                                    'delivery_method'
                                ] ?? '-'
                            ) ?>

                        </strong>

                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-total">

                        <span>
                            Total
                        </span>

                        <strong>

                            <?= formatPrice(
                                $order['total_amount']
                            ) ?>

                        </strong>

                    </div>

                </section>



                <!-- =============================================
                     DELIVERY INFORMATION
                ============================================== -->

                <section class="order-card">

                    <div class="order-card-heading">

                        <div>

                            <h2>
                                Delivery
                            </h2>

                        </div>

                    </div>


                    <div class="info-grid">

                        <div class="info-box">

                            <div class="info-box-label">

                                <i class="fa-solid fa-truck"></i>

                                Method

                            </div>

                            <div class="info-box-value">

                                <?= e(
                                    $order[
                                        'delivery_method'
                                    ] ?? '-'
                                ) ?>

                            </div>

                        </div>


                        <div class="info-box">

                            <div class="info-box-label">

                                <i class="fa-solid fa-location-dot"></i>

                                Address

                            </div>

                            <div class="info-box-value">

                                <?php if (
                                    $deliveryAddress
                                ): ?>

                                    <?= nl2br(
                                        e($deliveryAddress)
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </div>

                        </div>


                        <?php if (
                            !empty(
                                $order['tracking_number']
                            )
                        ): ?>

                            <div class="info-box full">

                                <div class="info-box-label">

                                    <i
                                        class="fa-solid fa-barcode"
                                    ></i>

                                    Tracking Number

                                </div>

                                <div class="info-box-value">

                                    <?= e(
                                        $order[
                                            'tracking_number'
                                        ]
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>



                <!-- =============================================
                     PAYMENT
                ============================================== -->

                <section class="order-card">

                    <div class="order-card-heading">

                        <div>

                            <h2>
                                Payment
                            </h2>

                        </div>

                    </div>


                    <?php if ($payment): ?>

                        <div class="payment-method">

                            <div class="payment-icon">

                                <?php

                                $paymentIcon = match (
                                    $payment[
                                        'payment_method'
                                    ] ?? ''
                                ) {

                                    'FPX'
                                        => 'fa-building-columns',

                                    'Credit Card',
                                    'Debit Card'
                                        => 'fa-credit-card',

                                    'Cash'
                                        => 'fa-money-bill',

                                    default
                                        => 'fa-wallet'

                                };

                                ?>

                                <i
                                    class="fa-solid <?= e(
                                        $paymentIcon
                                    ) ?>"
                                ></i>

                            </div>


                            <div>

                                <strong>

                                    <?= e(
                                        $payment[
                                            'payment_method'
                                        ] ?? '-'
                                    ) ?>

                                </strong>


                                <div class="
                                    payment-status
                                    <?= e(
                                        $paymentStatusClass
                                    ) ?>
                                ">

                                    <?= e(
                                        $paymentStatus
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <?php if (
                            !empty(
                                $payment[
                                    'transaction_reference'
                                ]
                            )
                        ): ?>

                            <div
                                style="
                                    margin-top:15px;
                                    padding-top:14px;
                                    border-top:1px solid #e2e8f0;
                                "
                            >

                                <div
                                    style="
                                        color:#64748b;
                                        font-size:11px;
                                        font-weight:800;
                                        margin-bottom:5px;
                                    "
                                >

                                    TRANSACTION REFERENCE

                                </div>


                                <div
                                    style="
                                        color:#0f172a;
                                        font-size:12px;
                                        font-weight:900;
                                        word-break:break-all;
                                    "
                                >

                                    <?= e(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                    <?php else: ?>

                        <div
                            style="
                                text-align:center;
                                padding:15px 5px;
                                color:#64748b;
                            "
                        >

                            <i
                                class="fa-solid fa-wallet"
                                style="
                                    font-size:28px;
                                    color:#93c5fd;
                                    margin-bottom:9px;
                                "
                            ></i>

                            <p
                                style="
                                    margin:0;
                                    font-size:13px;
                                "
                            >
                                Payment information is not available.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>



                <!-- =============================================
                     CUSTOMER INFORMATION
                ============================================== -->

                <section class="order-card">

                    <div class="order-card-heading">

                        <div>

                            <h2>
                                Customer
                            </h2>

                        </div>

                    </div>


                    <div class="info-grid">

                        <div class="info-box full">

                            <div class="info-box-label">

                                <i class="fa-solid fa-user"></i>

                                Name

                            </div>

                            <div class="info-box-value">

                                <?= e(
                                    $order[
                                        'customer_name'
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div class="info-box">

                            <div class="info-box-label">

                                <i class="fa-solid fa-envelope"></i>

                                Email

                            </div>

                            <div class="info-box-value">

                                <?= e(
                                    $order[
                                        'customer_email'
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div class="info-box">

                            <div class="info-box-label">

                                <i class="fa-solid fa-phone"></i>

                                Phone

                            </div>

                            <div class="info-box-value">

                                <?= e(
                                    $order[
                                        'customer_phone'
                                    ] ?? '-'
                                ) ?>

                            </div>

                        </div>

                    </div>

                </section>


            </aside>

        </div>

    </div>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>


<script src="<?= assetUrl('js/script.js') ?>"></script>

</body>

</html>