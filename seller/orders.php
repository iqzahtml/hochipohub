<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER ORDERS
|--------------------------------------------------------------------------
| File:
| seller/orders.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}


$currentRole = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);


if ($currentRole !== 'vendor') {
    header('Location: ../dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


$userId = (int) $_SESSION['user_id'];

$error = '';

$success = '';


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function sellerOrderEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function sellerOrderMoney($value): string
{
    return number_format(
        (float) $value,
        2
    );
}


function sellerOrderMapsUrl($address): string
{
    return
        'https://www.google.com/maps/dir/?api=1&destination=' .
        rawurlencode(
            trim((string) $address)
        );
}


function sellerOrderStatusClass($status): string
{
    $status = strtolower(
        trim((string) $status)
    );

    switch ($status) {

        case 'pending':
            return 'pending';

        case 'processing':
            return 'processing';

        case 'ready':
            return 'ready';

        case 'shipped':
            return 'shipped';

        case 'completed':
            return 'completed';

        case 'cancelled':
            return 'cancelled';

        default:
            return 'default';
    }
}


function sellerPaymentStatusClass($status): string
{
    $status = strtolower(
        trim((string) $status)
    );

    switch ($status) {

        case 'paid':
            return 'paid';

        case 'failed':
            return 'failed';

        case 'refunded':
            return 'refunded';

        case 'pending':
        default:
            return 'pending';
    }
}


function sellerPaymentLabel(
    $paymentMethod,
    $paymentStatus,
    $deliveryMethod
): string {

    $paymentMethod =
        trim((string) $paymentMethod);

    $paymentStatus =
        strtolower(
            trim((string) $paymentStatus)
        );

    $deliveryMethod =
        trim((string) $deliveryMethod);


    if ($paymentMethod === 'Cash') {

        if ($paymentStatus === 'paid') {
            return 'CASH PAID';
        }

        if ($deliveryMethod === 'Pickup') {
            return 'CASH AT PICKUP';
        }

        if ($deliveryMethod === 'Vendor Delivery') {
            return 'CASH TO COLLECT';
        }

        return 'CASH PAYMENT';
    }


    if ($paymentStatus === 'paid') {
        return 'PAID';
    }


    if ($paymentStatus === 'failed') {
        return 'PAYMENT FAILED';
    }


    if ($paymentStatus === 'refunded') {
        return 'REFUNDED';
    }


    return 'PAYMENT PENDING';
}


function sellerIsOnlinePayment($paymentMethod): bool
{
    return in_array(
        trim((string) $paymentMethod),
        [
            'FPX',
            'Credit Card',
            'Debit Card'
        ],
        true
    );
}


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case 'status':

            $success =
                'Order status updated successfully.';

            break;


        case 'tracking':

            $success =
                'Courier and tracking information updated successfully.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

try {

    $vendorStmt =
        $db->prepare("
            SELECT

                v.vendor_id,
                v.user_id,
                v.business_name,
                v.business_logo,
                v.business_description,
                v.business_address,
                v.category,
                v.delivery_method,
                v.postage_fee,
                v.allow_vendor_delivery,
                v.cod_enabled,
                v.vendor_delivery_fee,
                v.commission_rate,
                v.approval_status,

                u.name,
                u.email,
                u.phone

            FROM vendors v

            INNER JOIN users u
                ON u.user_id =
                   v.user_id

            WHERE v.user_id = ?

            LIMIT 1
        ");


    $vendorStmt->execute([
        $userId
    ]);


    $vendor =
        $vendorStmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $vendor = false;
}


if (!$vendor) {

    header(
        'Location: setup_profile.php'
    );

    exit;
}


$vendorId =
    (int)
    $vendor['vendor_id'];


$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| UPDATE ORDER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        trim(
            (string) (
                $_POST['action']
                ?? ''
            )
        );


    $vendorOrderId =
        (int) (
            $_POST['vendor_order_id']
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | VERIFY SELLER ORDER
    |--------------------------------------------------------------------------
    */

    try {

        $checkStmt =
            $db->prepare("
                SELECT

                    vo.vendor_order_id,
                    vo.order_id,
                    vo.vendor_id,
                    vo.vendor_status,
                    vo.tracking_number,
                    vo.courier_name,

                    o.delivery_method,
                    o.delivery_address,
                    o.order_status,

                    p.payment_id,
                    p.payment_method,
                    p.payment_status

                FROM vendor_orders vo

                INNER JOIN orders o
                    ON o.order_id =
                       vo.order_id

                LEFT JOIN payments p
                    ON p.payment_id = (

                        SELECT p2.payment_id

                        FROM payments p2

                        WHERE p2.order_id =
                              o.order_id

                        ORDER BY
                            p2.payment_id DESC

                        LIMIT 1
                    )

                WHERE
                    vo.vendor_order_id = ?

                AND
                    vo.vendor_id = ?

                LIMIT 1
            ");


        $checkStmt->execute([
            $vendorOrderId,
            $vendorId
        ]);


        $ownedOrder =
            $checkStmt->fetch(
                PDO::FETCH_ASSOC
            );


    } catch (Throwable $e) {

        $ownedOrder = false;
    }


    if (!$ownedOrder) {

        $error =
            'Invalid seller order.';

    } else {


        $ownedPaymentMethod =
            trim(
                (string) (
                    $ownedOrder['payment_method']
                    ?? ''
                )
            );


        $ownedPaymentStatus =
            strtolower(
                trim(
                    (string) (
                        $ownedOrder['payment_status']
                        ?? 'pending'
                    )
                )
            );


        $ownedIsOnlinePayment =
            sellerIsOnlinePayment(
                $ownedPaymentMethod
            );


        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        if ($action === 'update_status') {

            $newStatus =
                trim(
                    (string) (
                        $_POST['vendor_status']
                        ?? ''
                    )
                );


            $allowedStatuses = [
                'Pending',
                'Processing',
                'Ready',
                'Shipped',
                'Completed',
                'Cancelled'
            ];


            if (
                !in_array(
                    $newStatus,
                    $allowedStatuses,
                    true
                )
            ) {

                $error =
                    'Invalid order status.';


            } elseif (
                $ownedIsOnlinePayment &&
                $ownedPaymentStatus !== 'paid' &&
                in_array(
                    $newStatus,
                    [
                        'Processing',
                        'Ready',
                        'Shipped',
                        'Completed'
                    ],
                    true
                )
            ) {

                $error =
                    'This online payment has not been confirmed as Paid. ' .
                    'You cannot process this order yet.';


            } else {

                try {

                    $db->beginTransaction();


                    $completedDate =
                        $newStatus === 'Completed'
                            ? date('Y-m-d H:i:s')
                            : null;


                    $updateStmt =
                        $db->prepare("
                            UPDATE vendor_orders

                            SET
                                vendor_status = ?,

                                completed_at =
                                    CASE

                                        WHEN ? = 'Completed'

                                        THEN COALESCE(
                                            completed_at,
                                            ?
                                        )

                                        WHEN ? <> 'Completed'

                                        THEN NULL

                                        ELSE completed_at

                                    END

                            WHERE vendor_order_id = ?

                            AND vendor_id = ?
                        ");


                    $updateStmt->execute([
                        $newStatus,
                        $newStatus,
                        $completedDate,
                        $newStatus,
                        $vendorOrderId,
                        $vendorId
                    ]);


                    $orderId =
                        (int)
                        $ownedOrder['order_id'];


                    $statusStmt =
                        $db->prepare("
                            SELECT
                                vendor_status

                            FROM vendor_orders

                            WHERE order_id = ?
                        ");


                    $statusStmt->execute([
                        $orderId
                    ]);


                    $allStatuses =
                        $statusStmt->fetchAll(
                            PDO::FETCH_COLUMN
                        );


                    $mainOrderStatus =
                        'Pending';


                    if (!empty($allStatuses)) {

                        $allCompleted =
                            true;

                        $allCancelled =
                            true;

                        $hasProcessing =
                            false;


                        foreach (
                            $allStatuses
                            as $status
                        ) {

                            if ($status !== 'Completed') {
                                $allCompleted = false;
                            }


                            if ($status !== 'Cancelled') {
                                $allCancelled = false;
                            }


                            if (
                                in_array(
                                    $status,
                                    [
                                        'Processing',
                                        'Ready',
                                        'Shipped',
                                        'Completed'
                                    ],
                                    true
                                )
                            ) {

                                $hasProcessing = true;
                            }
                        }


                        if ($allCompleted) {

                            $mainOrderStatus =
                                'Completed';

                        } elseif ($allCancelled) {

                            $mainOrderStatus =
                                'Cancelled';

                        } elseif ($hasProcessing) {

                            $mainOrderStatus =
                                'Processing';

                        } else {

                            $mainOrderStatus =
                                'Pending';
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | MAIN ORDER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $mainOrderStatus ===
                        'Completed'
                    ) {

                        $mainUpdate =
                            $db->prepare("
                                UPDATE orders

                                SET
                                    order_status =
                                        'Completed',

                                    completed_date =
                                        COALESCE(
                                            completed_date,
                                            NOW()
                                        )

                                WHERE order_id = ?
                            ");


                        $mainUpdate->execute([
                            $orderId
                        ]);


                    } else {

                        $mainUpdate =
                            $db->prepare("
                                UPDATE orders

                                SET
                                    order_status = ?,

                                    completed_date =
                                        CASE

                                            WHEN ? <> 'Completed'

                                            THEN NULL

                                            ELSE completed_date

                                        END

                                WHERE order_id = ?
                            ");


                        $mainUpdate->execute([
                            $mainOrderStatus,
                            $mainOrderStatus,
                            $orderId
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CASH PAYMENT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $mainOrderStatus ===
                        'Completed'
                    ) {

                        $paymentLookup =
                            $db->prepare("
                                SELECT

                                    payment_id,
                                    payment_method,
                                    payment_status

                                FROM payments

                                WHERE order_id = ?

                                ORDER BY
                                    payment_id DESC

                                LIMIT 1
                            ");


                        $paymentLookup->execute([
                            $orderId
                        ]);


                        $payment =
                            $paymentLookup->fetch(
                                PDO::FETCH_ASSOC
                            );


                        if (
                            $payment &&
                            $payment['payment_method']
                            === 'Cash'
                        ) {

                            $paymentUpdate =
                                $db->prepare("
                                    UPDATE payments

                                    SET
                                        payment_status =
                                            'Paid',

                                        payment_date =
                                            COALESCE(
                                                payment_date,
                                                NOW()
                                            )

                                    WHERE payment_id = ?
                                ");


                            $paymentUpdate->execute([
                                (int)
                                $payment['payment_id']
                            ]);
                        }
                    }


                    $db->commit();


                    header(
                        'Location: orders.php?success=status'
                    );

                    exit;


                } catch (Throwable $e) {

                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }


                    $error =
                        'Unable to update order status. ' .
                        $e->getMessage();
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | COURIER + TRACKING
        |--------------------------------------------------------------------------
        */

        elseif (
            $action ===
            'update_tracking'
        ) {

            $courierName =
                trim(
                    (string) (
                        $_POST['courier_name']
                        ?? ''
                    )
                );


            $trackingNumber =
                trim(
                    (string) (
                        $_POST['tracking_number']
                        ?? ''
                    )
                );


            $allowedCouriers = [
                'J&T Express',
                'Pos Laju',
                'Ninja Van',
                'DHL eCommerce',
                'Shopee Express',
                'Flash Express',
                'Others'
            ];


            if (
                $ownedOrder['delivery_method']
                !== 'Postage'
            ) {

                $error =
                    'Courier and tracking information are only used for Postage orders.';


            } elseif (
                $courierName === ''
            ) {

                $error =
                    'Please select a courier.';


            } elseif (
                !in_array(
                    $courierName,
                    $allowedCouriers,
                    true
                )
            ) {

                $error =
                    'Invalid courier selected.';


            } elseif (
                $trackingNumber === ''
            ) {

                $error =
                    'Please enter the tracking number.';


            } elseif (
                mb_strlen(
                    $courierName
                ) > 100
            ) {

                $error =
                    'Courier name is too long.';


            } elseif (
                mb_strlen(
                    $trackingNumber
                ) > 100
            ) {

                $error =
                    'Tracking number is too long.';


            } else {

                try {

                    $trackingStmt =
                        $db->prepare("
                            UPDATE vendor_orders

                            SET
                                courier_name = ?,
                                tracking_number = ?

                            WHERE
                                vendor_order_id = ?

                            AND
                                vendor_id = ?
                        ");


                    $trackingStmt->execute([
                        $courierName,
                        $trackingNumber,
                        $vendorOrderId,
                        $vendorId
                    ]);


                    header(
                        'Location: orders.php?success=tracking'
                    );

                    exit;


                } catch (Throwable $e) {

                    $error =
                        'Unable to update courier and tracking information. ' .
                        $e->getMessage();
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$statusFilter =
    trim(
        (string) (
            $_GET['status']
            ?? 'All'
        )
    );


$validFilters = [
    'All',
    'Pending',
    'Processing',
    'Ready',
    'Shipped',
    'Completed',
    'Cancelled'
];


if (
    !in_array(
        $statusFilter,
        $validFilters,
        true
    )
) {

    $statusFilter =
        'All';
}


/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

$statsStmt =
    $db->prepare("
        SELECT

            COUNT(*) AS total_orders,

            SUM(
                CASE
                    WHEN vendor_status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending_orders,

            SUM(
                CASE
                    WHEN vendor_status = 'Processing'
                    THEN 1
                    ELSE 0
                END
            ) AS processing_orders,

            SUM(
                CASE
                    WHEN vendor_status = 'Completed'
                    THEN 1
                    ELSE 0
                END
            ) AS completed_orders

        FROM vendor_orders

        WHERE vendor_id = ?
    ");


$statsStmt->execute([
    $vendorId
]);


$orderStatsRaw =
    $statsStmt->fetch(
        PDO::FETCH_ASSOC
    );


$orderStats = [

    'total' =>
        (int) (
            $orderStatsRaw['total_orders']
            ?? 0
        ),

    'pending' =>
        (int) (
            $orderStatsRaw['pending_orders']
            ?? 0
        ),

    'processing' =>
        (int) (
            $orderStatsRaw['processing_orders']
            ?? 0
        ),

    'completed' =>
        (int) (
            $orderStatsRaw['completed_orders']
            ?? 0
        )
];


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        vo.vendor_order_id,
        vo.order_id,
        vo.vendor_id,
        vo.subtotal,
        vo.delivery_fee,
        vo.vendor_status,
        vo.tracking_number,
        vo.courier_name,
        vo.created_at
            AS vendor_order_created,
        vo.completed_at,

        o.customer_id,
        o.order_date,
        o.total_amount
            AS main_order_total,
        o.delivery_method,
        o.delivery_address,
        o.order_status
            AS main_order_status,
        o.completed_date,

        u.name
            AS customer_name,
        u.email
            AS customer_email,
        u.phone
            AS customer_phone,

        pmt.payment_id,
        pmt.payment_method,
        pmt.payment_status,
        pmt.payment_date,
        pmt.amount
            AS payment_amount,
        pmt.transaction_reference,
        pmt.payment_gateway,
        pmt.gateway_order_reference

    FROM vendor_orders vo

    INNER JOIN orders o
        ON o.order_id =
           vo.order_id

    INNER JOIN users u
        ON u.user_id =
           o.customer_id

    LEFT JOIN payments pmt
        ON pmt.payment_id = (

            SELECT
                p2.payment_id

            FROM payments p2

            WHERE
                p2.order_id =
                o.order_id

            ORDER BY
                p2.payment_id DESC

            LIMIT 1
        )

    WHERE vo.vendor_id = ?
";


$params = [
    $vendorId
];


if ($statusFilter !== 'All') {

    $sql .= "
        AND vo.vendor_status = ?
    ";

    $params[] =
        $statusFilter;
}


$sql .= "
    ORDER BY
        vo.created_at DESC,
        vo.vendor_order_id DESC
";


$orderStmt =
    $db->prepare(
        $sql
    );


$orderStmt->execute(
    $params
);


$orders =
    $orderStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| ITEMS
|--------------------------------------------------------------------------
*/

$itemStmt =
    $db->prepare("
        SELECT

            od.order_detail_id,
            od.product_id,
            od.quantity,
            od.unit_price,
            od.subtotal,

            p.product_name,
            p.image

        FROM order_details od

        INNER JOIN products p
            ON p.product_id =
               od.product_id

        WHERE
            od.order_id = ?

        AND
            p.vendor_id = ?

        ORDER BY
            od.order_detail_id ASC
    ");


foreach (
    $orders
    as &$order
) {

    $itemStmt->execute([

        (int)
        $order['order_id'],

        $vendorId
    ]);


    $order['items'] =
        $itemStmt->fetchAll(
            PDO::FETCH_ASSOC
        );
}


unset($order);


/*
|--------------------------------------------------------------------------
| TOPBAR
|--------------------------------------------------------------------------
*/

$vendorInitial =
    strtoupper(
        substr(
            trim(
                (string) (
                    $vendor['name']
                    ?? 'V'
                )
            ),
            0,
            1
        )
    );

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
    Orders - HochipoHub
</title>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>


<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>


<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<link
    rel="stylesheet"
    href="../css/style.css"
>


<link
    rel="stylesheet"
    href="../css/vendor.css"
>


<link
    rel="stylesheet"
    href="../css/responsive.css"
>


<style>

/* =========================================================
   PAGE
========================================================= */

* {
    box-sizing: border-box;
}


body.seller-orders-body {

    margin: 0;

    min-height: 100vh;

    overflow-x: hidden;

    color: #14213d;

    background: #f6f8fc;

    font-family:
        Inter,
        Arial,
        sans-serif;
}


/* =========================================================
   MAIN — SAME AS ADD PRODUCT
========================================================= */

.seller-orders-main {

    width:
        calc(
            100% -
            var(--seller-sidebar)
        );

    min-height: 100vh;

    margin-left:
        var(--seller-sidebar);

    background:
        radial-gradient(
            circle at 95% 8%,
            rgba(
                37,
                99,
                235,
                .07
            ),
            transparent 22%
        ),
        #f6f8fc;
}


/* =========================================================
   TOPBAR
========================================================= */

.seller-orders-topbar {

    height: 72px;

    padding:
        0 32px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background:
        rgba(
            255,
            255,
            255,
            .95
        );

    border-bottom:
        1px solid
        #e8edf5;
}


.seller-orders-topbar-label {

    color: #94a3b8;

    font-size: 11px;

    font-weight: 700;
}


.seller-orders-topbar-user {

    display: flex;

    align-items: center;

    gap: 9px;
}


.seller-orders-topbar-avatar {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #3b82f6,
            #6366f1
        );

    border-radius: 50%;

    font-size: 12px;

    font-weight: 900;
}


.seller-orders-topbar-user strong {

    display: block;

    color: #14213d;

    font-size: 11px;
}


.seller-orders-topbar-user small {

    display: block;

    margin-top: 2px;

    color: #94a3b8;

    font-size: 8px;
}


/* =========================================================
   CONTENT
========================================================= */

.seller-orders-content {

    width: 100%;

    max-width: 1450px;

    margin:
        0 auto;

    padding:
        28px 32px
        60px;
}


/* =========================================================
   HEADING
========================================================= */

.seller-orders-heading {

    margin-bottom: 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.seller-orders-eyebrow {

    display: block;

    margin-bottom: 5px;

    color: #2563eb;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.5px;
}


.seller-orders-heading h1 {

    margin: 0;

    color: #14213d;

    font-size:
        clamp(
            25px,
            3vw,
            33px
        );

    font-weight: 900;

    letter-spacing: -.8px;
}


.seller-orders-heading p {

    margin:
        7px 0 0;

    color: #7b879c;

    font-size: 11px;
}


.seller-orders-dashboard-button {

    min-height: 42px;

    padding:
        0 15px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #475569;

    background: #ffffff;

    border:
        1px solid
        #dfe6ef;

    border-radius: 11px;

    box-shadow:
        0 8px 20px
        rgba(
            40,
            65,
            120,
            .04
        );

    font-size: 9px;

    font-weight: 800;

    text-decoration: none;
}


/* =========================================================
   HERO — SAME DESIGN AS ADD PRODUCT
========================================================= */

.seller-orders-hero {

    position: relative;

    overflow: hidden;

    min-height: 170px;

    margin-bottom: 22px;

    padding: 31px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 30px;

    color: #ffffff;

    background:
        linear-gradient(
            110deg,
            #08265a 0%,
            #123d8c 48%,
            #2783ef 100%
        );

    border-radius: 23px;

    box-shadow:
        0 17px 38px
        rgba(
            18,
            70,
            150,
            .13
        );
}


.seller-orders-hero::before {

    content: "";

    position: absolute;

    width: 220px;

    height: 220px;

    top: -130px;

    right: -40px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .08
        );
}


.seller-orders-hero::after {

    content: "";

    position: absolute;

    width: 145px;

    height: 145px;

    right: 155px;

    bottom: -100px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .05
        );
}


.seller-orders-hero-copy {

    position: relative;

    z-index: 2;

    max-width: 650px;
}


.seller-orders-hero-label {

    display: block;

    margin-bottom: 8px;

    color: #a8d4ff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.4px;
}


.seller-orders-hero h2 {

    margin:
        0 0 8px;

    color: #ffffff;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 25px;

    font-weight: 800;

    letter-spacing: -.6px;
}


.seller-orders-hero p {

    max-width: 600px;

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            .76
        );

    font-size: 10px;

    line-height: 1.7;
}


.seller-orders-hero-icon {

    position: relative;

    z-index: 2;

    width: 72px;

    height: 72px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

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
            .22
        );

    border-radius: 20px;

    backdrop-filter:
        blur(10px);

    font-size: 25px;
}


/* =========================================================
   ALERT
========================================================= */

.seller-orders-alert {

    margin-bottom: 20px;

    padding:
        15px 17px;

    display: flex;

    align-items: center;

    gap: 10px;

    border-radius: 13px;

    font-size: 10px;
}


.seller-orders-alert.success {

    color: #166534;

    background: #f0fdf4;

    border:
        1px solid
        #bbf7d0;
}


.seller-orders-alert.error {

    color: #991b1b;

    background: #fef2f2;

    border:
        1px solid
        #fecaca;
}


/* =========================================================
   STATS
========================================================= */

.seller-orders-stats {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap: 15px;

    margin-bottom: 22px;
}


.seller-orders-stat {

    min-height: 104px;

    padding: 18px;

    display: flex;

    align-items: center;

    gap: 13px;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 17px;

    box-shadow:
        0 7px 22px
        rgba(
            40,
            65,
            120,
            .045
        );
}


.seller-orders-stat-icon {

    width: 45px;

    height: 45px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 13px;

    font-size: 16px;
}


.seller-orders-stat-copy span {

    display: block;

    margin-bottom: 3px;

    color: #8492a7;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: .5px;
}


.seller-orders-stat-copy strong {

    color: #14213d;

    font-size: 22px;

    font-weight: 900;
}


/* =========================================================
   TOOLBAR
========================================================= */

.seller-orders-toolbar {

    margin-bottom: 18px;

    padding:
        15px 17px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 16px;
}


.seller-orders-toolbar-title {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #334155;

    font-size: 10px;

    font-weight: 900;
}


.seller-orders-filter {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 6px;
}


.seller-orders-filter a {

    padding:
        8px 10px;

    color: #64748b;

    background: #ffffff;

    border:
        1px solid
        #e1e7ef;

    border-radius: 9px;

    font-size: 8px;

    font-weight: 800;

    text-decoration: none;
}


.seller-orders-filter a.active {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #3b82f6
        );

    border-color: #2563eb;
}


/* =========================================================
   ORDER LIST
========================================================= */

.seller-order-list {

    display: grid;

    gap: 18px;
}


.seller-order-card {

    overflow: hidden;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 21px;

    box-shadow:
        0 12px 32px
        rgba(
            40,
            65,
            120,
            .05
        );
}


/* =========================================================
   ORDER HEADER
========================================================= */

.seller-order-header {

    min-height: 78px;

    padding:
        17px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background:
        #fbfdff;

    border-bottom:
        1px solid
        #edf1f7;
}


.seller-order-number {

    display: flex;

    align-items: center;

    gap: 11px;
}


.seller-order-number-icon {

    width: 42px;

    height: 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 12px;

    font-size: 14px;
}


.seller-order-number strong {

    display: block;

    margin-bottom: 3px;

    color: #14213d;

    font-size: 11px;

    font-weight: 900;
}


.seller-order-number span {

    color: #8b98ab;

    font-size: 8px;
}


.seller-order-badges {

    display: flex;

    flex-wrap: wrap;

    justify-content: flex-end;

    gap: 6px;
}


/* =========================================================
   BADGES
========================================================= */

.seller-order-status,
.seller-payment-badge {

    min-height: 25px;

    padding:
        0 9px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    border-radius: 999px;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .4px;

    text-transform: uppercase;
}


.seller-order-status.pending,
.seller-payment-badge.pending {

    color: #a16207;

    background: #fff7d6;
}


.seller-order-status.processing {

    color: #1d4ed8;

    background: #eaf2ff;
}


.seller-order-status.ready {

    color: #6d28d9;

    background: #f2eaff;
}


.seller-order-status.shipped {

    color: #0369a1;

    background: #e8f7ff;
}


.seller-order-status.completed,
.seller-payment-badge.paid {

    color: #047857;

    background: #e8f8ef;
}


.seller-order-status.cancelled,
.seller-payment-badge.failed {

    color: #b42318;

    background: #fff0ee;
}


.seller-payment-badge.refunded {

    color: #6d28d9;

    background: #f2edff;
}


/* =========================================================
   ORDER BODY
========================================================= */

.seller-order-body {

    display: grid;

    grid-template-columns:
        minmax(
            0,
            1.35fr
        )
        minmax(
            280px,
            .65fr
        );
}


.seller-order-main {

    padding: 21px;

    border-right:
        1px solid
        #edf1f5;
}


.seller-order-side {

    padding: 21px;

    background: #fbfcff;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.seller-order-section-title {

    margin:
        0 0 12px;

    display: flex;

    align-items: center;

    gap: 7px;

    color: #334155;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: .5px;

    text-transform: uppercase;
}


.seller-order-section-title i {

    color: #2563eb;
}


/* =========================================================
   CUSTOMER
========================================================= */

.seller-customer-card {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 10px;

    margin-bottom: 22px;
}


.seller-customer-info {

    padding:
        12px 13px;

    background: #fbfdff;

    border:
        1px solid
        #e7edf5;

    border-radius: 11px;
}


.seller-customer-info span {

    display: block;

    margin-bottom: 4px;

    color: #8b98ab;

    font-size: 7px;

    font-weight: 900;

    text-transform: uppercase;
}


.seller-customer-info strong {

    color: #334155;

    font-size: 9px;

    word-break: break-word;
}


/* =========================================================
   DELIVERY
========================================================= */

.seller-delivery-card {

    margin-bottom: 22px;

    padding: 14px;

    background:
        #f8fbff;

    border:
        1px solid
        #e4ebf5;

    border-radius: 13px;
}


.seller-delivery-row {

    margin-bottom: 9px;

    display: flex;

    justify-content: space-between;

    gap: 15px;

    color: #718198;

    font-size: 8px;
}


.seller-delivery-row:last-child {
    margin-bottom: 0;
}


.seller-delivery-row strong {

    color: #334155;

    text-align: right;
}


.seller-delivery-address {

    margin-top: 12px;

    padding-top: 12px;

    border-top:
        1px solid
        #e3eaf3;
}


.seller-delivery-address p {

    margin:
        0 0 9px;

    color: #52647c;

    font-size: 8px;

    line-height: 1.65;
}


.seller-order-navigate {

    min-height: 33px;

    padding:
        0 10px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    color: #ffffff;

    background: #2563eb;

    border-radius: 8px;

    font-size: 8px;

    font-weight: 900;

    text-decoration: none;
}


/* =========================================================
   ITEMS
========================================================= */

.seller-order-items {

    display: grid;

    gap: 5px;
}


.seller-order-item {

    padding:
        10px 0;

    display: grid;

    grid-template-columns:
        56px
        minmax(
            0,
            1fr
        )
        auto;

    align-items: center;

    gap: 11px;

    border-bottom:
        1px solid
        #edf1f5;
}


.seller-order-item:last-child {
    border-bottom: 0;
}


.seller-order-item-image {

    width: 56px;

    height: 56px;

    overflow: hidden;

    background: #eef3f8;

    border-radius: 10px;
}


.seller-order-item-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.seller-order-item-placeholder {

    width: 100%;

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #a7b3c3;
}


.seller-order-item-info {

    min-width: 0;
}


.seller-order-item-info strong {

    display: block;

    overflow: hidden;

    margin-bottom: 4px;

    color: #293b57;

    font-size: 9px;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.seller-order-item-info span {

    color: #8a98aa;

    font-size: 8px;
}


.seller-order-item-price {

    text-align: right;
}


.seller-order-item-price strong {

    display: block;

    color: #1d4ed8;

    font-size: 9px;
}


.seller-order-item-price span {

    color: #94a3b8;

    font-size: 7px;
}


/* =========================================================
   SIDE BOX
========================================================= */

.seller-side-box {

    margin-bottom: 14px;

    padding: 14px;

    background: #ffffff;

    border:
        1px solid
        #e4eaf2;

    border-radius: 13px;
}


.seller-side-box:last-child {
    margin-bottom: 0;
}


.seller-side-heading {

    margin-bottom: 12px;

    display: flex;

    align-items: center;

    gap: 7px;

    color: #334155;

    font-size: 8px;

    font-weight: 900;

    text-transform: uppercase;
}


.seller-side-heading i {

    color: #2563eb;
}


.seller-side-row {

    margin-bottom: 8px;

    display: flex;

    justify-content: space-between;

    gap: 14px;

    color: #74839a;

    font-size: 8px;
}


.seller-side-row:last-child {
    margin-bottom: 0;
}


.seller-side-row strong {

    color: #334155;

    text-align: right;
}


/* =========================================================
   PAYMENT MESSAGE
========================================================= */

.seller-payment-message {

    margin-top: 11px;

    padding:
        11px 12px;

    border-radius: 10px;

    font-size: 8px;

    line-height: 1.65;
}


.seller-payment-message strong {

    display: block;

    margin-bottom: 4px;

    font-size: 8px;
}


.seller-payment-message.success {

    color: #067647;

    background: #ecfdf3;

    border:
        1px solid
        #abefc6;
}


.seller-payment-message.warning {

    color: #9a6700;

    background: #fff8df;

    border:
        1px solid
        #ffe5a3;
}


.seller-payment-message.danger {

    color: #b42318;

    background: #fff0ef;

    border:
        1px solid
        #ffc9c5;
}


.seller-payment-message.info {

    color: #175cd3;

    background: #eef5ff;

    border:
        1px solid
        #c8dcff;
}


.seller-transaction {

    margin-top: 9px;

    padding:
        9px 10px;

    color: #52647c;

    background: #f7f9fc;

    border-radius: 8px;

    font-size: 7px;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   FORMS
========================================================= */

.seller-order-form {

    display: grid;

    gap: 8px;
}


.seller-order-form label {

    color: #64748b;

    font-size: 8px;

    font-weight: 800;
}


.seller-order-form select,
.seller-order-form input {

    width: 100%;

    min-height: 39px;

    padding:
        0 11px;

    outline: none;

    color: #334155;

    background: #fbfdff;

    border:
        1px solid
        #dce5ef;

    border-radius: 9px;

    font-family: inherit;

    font-size: 8px;
}


.seller-order-form select:focus,
.seller-order-form input:focus {

    background: #ffffff;

    border-color: #3b82f6;

    box-shadow:
        0 0 0 3px
        rgba(
            59,
            130,
            246,
            .08
        );
}


.seller-order-button {

    min-height: 39px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d67df
        );

    border: 0;

    border-radius: 9px;

    font-family: inherit;

    font-size: 8px;

    font-weight: 900;

    cursor: pointer;
}


.seller-order-button.secondary {

    color: #475569;

    background: #edf2f7;
}


/* =========================================================
   TRACKING SAVED INFO
========================================================= */

.seller-tracking-current {

    margin-bottom: 12px;

    padding: 11px 12px;

    color: #1e4d8d;

    background: #eff6ff;

    border:
        1px solid
        #d5e6ff;

    border-radius: 10px;

    font-size: 8px;

    line-height: 1.65;
}


.seller-tracking-current-row {

    display: flex;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 5px;
}


.seller-tracking-current-row:last-child {

    margin-bottom: 0;
}


.seller-tracking-current-row span {

    color: #6b7f99;
}


.seller-tracking-current-row strong {

    color: #173b73;

    text-align: right;

    word-break: break-all;
}


/* =========================================================
   LOCKED
========================================================= */

.seller-order-blocked {

    padding: 12px;

    color: #92400e;

    background: #fff8e5;

    border:
        1px solid
        #fed7aa;

    border-radius: 10px;

    font-size: 8px;

    line-height: 1.65;
}


.seller-order-blocked strong {

    display: block;

    margin-bottom: 4px;
}


/* =========================================================
   EMPTY
========================================================= */

.seller-orders-empty {

    padding:
        60px 25px;

    text-align: center;

    background: #ffffff;

    border:
        1px solid
        #e5eaf2;

    border-radius: 21px;

    box-shadow:
        0 12px 32px
        rgba(
            40,
            65,
            120,
            .04
        );
}


.seller-orders-empty-icon {

    width: 68px;

    height: 68px;

    margin:
        0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 18px;

    font-size: 24px;
}


.seller-orders-empty h2 {

    margin:
        0 0 6px;

    color: #14213d;

    font-size: 16px;
}


.seller-orders-empty p {

    margin: 0;

    color: #8997ab;

    font-size: 9px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1100px
) {

    .seller-orders-stats {

        grid-template-columns:
            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );
    }


    .seller-order-body {

        grid-template-columns:
            1fr;
    }


    .seller-order-main {

        border-right: 0;

        border-bottom:
            1px solid
            #edf1f5;
    }
}


@media (
    max-width: 850px
) {

    .seller-orders-main {

        width: 100%;

        margin-left: 0;
    }


    .seller-orders-topbar {

        padding-left: 70px;
    }


    .seller-orders-content {

        padding:
            24px 20px
            50px;
    }
}


@media (
    max-width: 600px
) {

    .seller-orders-topbar-user
    > div:last-child {

        display: none;
    }


    .seller-orders-content {

        padding:
            20px 14px
            45px;
    }


    .seller-orders-heading {

        align-items: flex-start;

        flex-direction: column;
    }


    .seller-orders-dashboard-button {

        width: 100%;
    }


    .seller-orders-hero {

        min-height: auto;

        padding: 24px;

        align-items: flex-start;

        border-radius: 19px;
    }


    .seller-orders-hero h2 {

        font-size: 20px;
    }


    .seller-orders-hero-icon {

        width: 54px;

        height: 54px;

        border-radius: 15px;

        font-size: 19px;
    }


    .seller-orders-stats {

        grid-template-columns:
            1fr;
    }


    .seller-orders-toolbar {

        align-items: flex-start;

        flex-direction: column;
    }


    .seller-order-header {

        align-items: flex-start;

        flex-direction: column;
    }


    .seller-order-badges {

        justify-content: flex-start;
    }


    .seller-customer-card {

        grid-template-columns:
            1fr;
    }


    .seller-order-item {

        grid-template-columns:
            50px
            minmax(
                0,
                1fr
            );
    }


    .seller-order-item-image {

        width: 50px;

        height: 50px;
    }


    .seller-order-item-price {

        grid-column: 2;

        text-align: left;
    }
}

</style>

</head>


<body
    class="
        seller-dashboard-page
        seller-orders-body
    "
>


<?php

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<main class="seller-orders-main">


    <!-- =========================================================
         TOPBAR
    ========================================================== -->

    <header class="seller-orders-topbar">


        <span class="seller-orders-topbar-label">

            Seller Center

        </span>


        <div class="seller-orders-topbar-user">


            <div class="seller-orders-topbar-avatar">

                <?= sellerOrderEscape(
                    $vendorInitial
                ) ?>

            </div>


            <div>

                <strong>

                    <?= sellerOrderEscape(
                        $vendor['name']
                        ?? 'Vendor'
                    ) ?>

                </strong>

                <small>
                    Vendor
                </small>

            </div>

        </div>

    </header>


    <div class="seller-orders-content">


        <!-- =====================================================
             HEADING
        ====================================================== -->

        <section class="seller-orders-heading">


            <div>

                <span class="seller-orders-eyebrow">

                    ORDER MANAGEMENT

                </span>


                <h1>

                    My Orders

                </h1>


                <p>

                    Manage orders received by
                    <?= sellerOrderEscape(
                        $vendor['business_name']
                    ) ?>.

                </p>

            </div>


            <a
                href="dashboard.php"
                class="seller-orders-dashboard-button"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Dashboard

            </a>

        </section>


        <!-- =====================================================
             HERO
        ====================================================== -->

        <section class="seller-orders-hero">


            <div class="seller-orders-hero-copy">


                <span class="seller-orders-hero-label">

                    SELLER WORKSPACE

                </span>


                <h2>

                    Keep every customer order moving.

                </h2>


                <p>

                    Review purchased products, customer
                    information, payment status and delivery
                    details before updating each order.

                </p>

            </div>


            <div class="seller-orders-hero-icon">

                <i class="fa-solid fa-bag-shopping"></i>

            </div>

        </section>


        <!-- =====================================================
             ALERT
        ====================================================== -->

        <?php if ($success !== ''): ?>

            <div class="seller-orders-alert success">

                <i class="fa-solid fa-circle-check"></i>

                <?= sellerOrderEscape(
                    $success
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <div class="seller-orders-alert error">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <?= sellerOrderEscape(
                    $error
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             STATS
        ====================================================== -->

        <section class="seller-orders-stats">


            <article class="seller-orders-stat">

                <div class="seller-orders-stat-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>

                <div class="seller-orders-stat-copy">

                    <span>
                        TOTAL ORDERS
                    </span>

                    <strong>
                        <?= $orderStats['total'] ?>
                    </strong>

                </div>

            </article>


            <article class="seller-orders-stat">

                <div class="seller-orders-stat-icon">

                    <i class="fa-regular fa-clock"></i>

                </div>

                <div class="seller-orders-stat-copy">

                    <span>
                        PENDING
                    </span>

                    <strong>
                        <?= $orderStats['pending'] ?>
                    </strong>

                </div>

            </article>


            <article class="seller-orders-stat">

                <div class="seller-orders-stat-icon">

                    <i class="fa-solid fa-gears"></i>

                </div>

                <div class="seller-orders-stat-copy">

                    <span>
                        PROCESSING
                    </span>

                    <strong>
                        <?= $orderStats['processing'] ?>
                    </strong>

                </div>

            </article>


            <article class="seller-orders-stat">

                <div class="seller-orders-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div class="seller-orders-stat-copy">

                    <span>
                        COMPLETED
                    </span>

                    <strong>
                        <?= $orderStats['completed'] ?>
                    </strong>

                </div>

            </article>


        </section>


        <!-- =====================================================
             FILTER
        ====================================================== -->

        <section class="seller-orders-toolbar">


            <div class="seller-orders-toolbar-title">

                <i class="fa-solid fa-list-check"></i>

                Seller Orders

            </div>


            <div class="seller-orders-filter">


                <?php foreach (
                    $validFilters
                    as $filter
                ): ?>

                    <a
                        href="orders.php?status=<?= urlencode(
                            $filter
                        ) ?>"
                        class="<?= $statusFilter ===
                            $filter
                                ? 'active'
                                : '' ?>"
                    >

                        <?= sellerOrderEscape(
                            $filter
                        ) ?>

                    </a>

                <?php endforeach; ?>


            </div>

        </section>


        <!-- =====================================================
             ORDERS
        ====================================================== -->

        <?php if (empty($orders)): ?>


            <section class="seller-orders-empty">

                <div class="seller-orders-empty-icon">

                    <i class="fa-solid fa-box-open"></i>

                </div>

                <h2>
                    No orders found
                </h2>

                <p>

                    Orders matching this status
                    will appear here.

                </p>

            </section>


        <?php else: ?>


            <div class="seller-order-list">


                <?php foreach (
                    $orders
                    as $order
                ): ?>


                    <?php

                    $deliveryMethod =
                        trim(
                            (string) (
                                $order['delivery_method']
                                ?? ''
                            )
                        );


                    $paymentMethod =
                        trim(
                            (string) (
                                $order['payment_method']
                                ?? ''
                            )
                        );


                    $paymentStatus =
                        trim(
                            (string) (
                                $order['payment_status']
                                ?? 'Pending'
                            )
                        );


                    $paymentStatusLower =
                        strtolower(
                            $paymentStatus
                        );


                    $vendorStatus =
                        trim(
                            (string) (
                                $order['vendor_status']
                                ?? 'Pending'
                            )
                        );


                    $statusClass =
                        sellerOrderStatusClass(
                            $vendorStatus
                        );


                    $paymentStatusClass =
                        sellerPaymentStatusClass(
                            $paymentStatus
                        );


                    $paymentLabel =
                        sellerPaymentLabel(
                            $paymentMethod,
                            $paymentStatus,
                            $deliveryMethod
                        );


                    $isCash =
                        $paymentMethod ===
                        'Cash';


                    $isOnline =
                        sellerIsOnlinePayment(
                            $paymentMethod
                        );


                    $isOnlinePaid =
                        $isOnline &&
                        $paymentStatusLower ===
                        'paid';


                    $onlineProcessingBlocked =
                        $isOnline &&
                        !$isOnlinePaid;


                    $sellerSubtotal =
                        (float) (
                            $order['subtotal']
                            ?? 0
                        );


                    $sellerDeliveryFee =
                        (float) (
                            $order['delivery_fee']
                            ?? 0
                        );


                    $sellerTotal =
                        $sellerSubtotal +
                        $sellerDeliveryFee;


                    $deliveryAddress =
                        trim(
                            (string) (
                                $order['delivery_address']
                                ?? ''
                            )
                        );


                    $savedCourier =
                        trim(
                            (string) (
                                $order['courier_name']
                                ?? ''
                            )
                        );


                    $savedTracking =
                        trim(
                            (string) (
                                $order['tracking_number']
                                ?? ''
                            )
                        );

                    ?>


                    <article class="seller-order-card">


                        <!-- =========================================
                             HEADER
                        ========================================== -->

                        <div class="seller-order-header">


                            <div class="seller-order-number">


                                <div class="seller-order-number-icon">

                                    <i class="fa-solid fa-receipt"></i>

                                </div>


                                <div>

                                    <strong>

                                        Order #<?= (int)
                                            $order['order_id'] ?>

                                    </strong>


                                    <span>

                                        <?= sellerOrderEscape(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $order[
                                                        'vendor_order_created'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </div>


                            <div class="seller-order-badges">


                                <span
                                    class="
                                        seller-order-status
                                        <?= sellerOrderEscape(
                                            $statusClass
                                        ) ?>
                                    "
                                >

                                    <?= sellerOrderEscape(
                                        $vendorStatus
                                    ) ?>

                                </span>


                                <span
                                    class="
                                        seller-payment-badge
                                        <?= sellerOrderEscape(
                                            $paymentStatusClass
                                        ) ?>
                                    "
                                >

                                    <?php if (
                                        $paymentStatusLower ===
                                        'paid'
                                    ): ?>

                                        <i class="fa-solid fa-circle-check"></i>

                                    <?php elseif (
                                        $paymentStatusLower ===
                                        'failed'
                                    ): ?>

                                        <i class="fa-solid fa-circle-xmark"></i>

                                    <?php else: ?>

                                        <i class="fa-regular fa-clock"></i>

                                    <?php endif; ?>


                                    <?= sellerOrderEscape(
                                        $paymentLabel
                                    ) ?>

                                </span>

                            </div>

                        </div>


                        <!-- =========================================
                             BODY
                        ========================================== -->

                        <div class="seller-order-body">


                            <!-- =====================================
                                 LEFT
                            ====================================== -->

                            <div class="seller-order-main">


                                <h3 class="seller-order-section-title">

                                    <i class="fa-solid fa-user"></i>

                                    Customer Information

                                </h3>


                                <div class="seller-customer-card">


                                    <div class="seller-customer-info">

                                        <span>
                                            Customer
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $order[
                                                    'customer_name'
                                                ]
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-customer-info">

                                        <span>
                                            Phone
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $order[
                                                    'customer_phone'
                                                ]
                                                ?: '-'
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-customer-info">

                                        <span>
                                            Email
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $order[
                                                    'customer_email'
                                                ]
                                                ?: '-'
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-customer-info">

                                        <span>
                                            Delivery
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $deliveryMethod
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>


                                <!-- DELIVERY -->

                                <h3 class="seller-order-section-title">

                                    <i class="fa-solid fa-truck"></i>

                                    Delivery Information

                                </h3>


                                <div class="seller-delivery-card">


                                    <div class="seller-delivery-row">

                                        <span>
                                            Method
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $deliveryMethod
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-delivery-row">

                                        <span>
                                            Delivery Fee
                                        </span>

                                        <strong>

                                            RM <?= sellerOrderMoney(
                                                $sellerDeliveryFee
                                            ) ?>

                                        </strong>

                                    </div>


                                    <?php if (
                                        $deliveryMethod ===
                                        'Postage' &&
                                        $savedCourier !== ''
                                    ): ?>

                                        <div class="seller-delivery-row">

                                            <span>
                                                Courier
                                            </span>

                                            <strong>
                                                <?= sellerOrderEscape(
                                                    $savedCourier
                                                ) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        $deliveryMethod ===
                                        'Postage' &&
                                        $savedTracking !== ''
                                    ): ?>

                                        <div class="seller-delivery-row">

                                            <span>
                                                Tracking Number
                                            </span>

                                            <strong>
                                                <?= sellerOrderEscape(
                                                    $savedTracking
                                                ) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        $deliveryMethod ===
                                        'Pickup'
                                    ): ?>


                                        <div class="seller-delivery-address">

                                            <p>

                                                Customer will collect
                                                this order from your store.

                                            </p>


                                            <?php if (
                                                !empty(
                                                    $vendor[
                                                        'business_address'
                                                    ]
                                                )
                                            ): ?>

                                                <p>

                                                    <strong>
                                                        Pickup Location:
                                                    </strong>

                                                    <br>

                                                    <?= sellerOrderEscape(
                                                        $vendor[
                                                            'business_address'
                                                        ]
                                                    ) ?>

                                                </p>

                                            <?php endif; ?>

                                        </div>


                                    <?php elseif (
                                        $deliveryAddress !== ''
                                    ): ?>


                                        <div class="seller-delivery-address">

                                            <p>

                                                <strong>
                                                    Customer Address:
                                                </strong>

                                                <br>

                                                <?= nl2br(
                                                    sellerOrderEscape(
                                                        $deliveryAddress
                                                    )
                                                ) ?>

                                            </p>


                                            <?php if (
                                                $deliveryMethod ===
                                                'Vendor Delivery'
                                            ): ?>

                                                <a
                                                    href="<?= sellerOrderEscape(
                                                        sellerOrderMapsUrl(
                                                            $deliveryAddress
                                                        )
                                                    ) ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="seller-order-navigate"
                                                >

                                                    <i class="fa-solid fa-location-arrow"></i>

                                                    Navigate

                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    <?php endif; ?>


                                </div>


                                <!-- ITEMS -->

                                <h3 class="seller-order-section-title">

                                    <i class="fa-solid fa-bag-shopping"></i>

                                    Your Items

                                </h3>


                                <div class="seller-order-items">


                                    <?php foreach (
                                        $order['items']
                                        as $item
                                    ): ?>


                                        <?php

                                        $productImage =
                                            trim(
                                                (string) (
                                                    $item['image']
                                                    ?? ''
                                                )
                                            );


                                        if (
                                            $productImage !== '' &&
                                            !preg_match(
                                                '/^https?:\/\//i',
                                                $productImage
                                            ) &&
                                            !str_starts_with(
                                                $productImage,
                                                '../'
                                            )
                                        ) {

                                            if (
                                                str_starts_with(
                                                    $productImage,
                                                    'uploads/'
                                                )
                                            ) {

                                                $productImage =
                                                    '../' .
                                                    $productImage;

                                            } else {

                                                $productImage =
                                                    '../uploads/products/' .
                                                    rawurlencode(
                                                        basename(
                                                            $productImage
                                                        )
                                                    );
                                            }
                                        }

                                        ?>


                                        <div class="seller-order-item">


                                            <div class="seller-order-item-image">


                                                <?php if (
                                                    $productImage !== ''
                                                ): ?>

                                                    <img
                                                        src="<?= sellerOrderEscape(
                                                            $productImage
                                                        ) ?>"
                                                        alt="<?= sellerOrderEscape(
                                                            $item[
                                                                'product_name'
                                                            ]
                                                        ) ?>"
                                                    >

                                                <?php else: ?>

                                                    <div class="seller-order-item-placeholder">

                                                        <i class="fa-solid fa-image"></i>

                                                    </div>

                                                <?php endif; ?>

                                            </div>


                                            <div class="seller-order-item-info">

                                                <strong>

                                                    <?= sellerOrderEscape(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <span>

                                                    Qty:
                                                    <?= (int)
                                                        $item[
                                                            'quantity'
                                                        ] ?>

                                                    &nbsp;•&nbsp;

                                                    RM <?= sellerOrderMoney(
                                                        $item[
                                                            'unit_price'
                                                        ]
                                                    ) ?>

                                                    each

                                                </span>

                                            </div>


                                            <div class="seller-order-item-price">

                                                <strong>

                                                    RM <?= sellerOrderMoney(
                                                        $item[
                                                            'subtotal'
                                                        ]
                                                    ) ?>

                                                </strong>

                                                <span>
                                                    Item subtotal
                                                </span>

                                            </div>

                                        </div>


                                    <?php endforeach; ?>


                                </div>

                            </div>


                            <!-- =====================================
                                 RIGHT
                            ====================================== -->

                            <aside class="seller-order-side">


                                <!-- PAYMENT -->

                                <div class="seller-side-box">


                                    <div class="seller-side-heading">

                                        <i class="fa-solid fa-wallet"></i>

                                        Payment

                                    </div>


                                    <div class="seller-side-row">

                                        <span>
                                            Method
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $paymentMethod !== ''
                                                    ? $paymentMethod
                                                    : '-'
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-side-row">

                                        <span>
                                            Status
                                        </span>

                                        <strong>

                                            <?= sellerOrderEscape(
                                                $paymentStatus
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-side-row">

                                        <span>
                                            Product Subtotal
                                        </span>

                                        <strong>

                                            RM <?= sellerOrderMoney(
                                                $sellerSubtotal
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-side-row">

                                        <span>
                                            Delivery Fee
                                        </span>

                                        <strong>

                                            RM <?= sellerOrderMoney(
                                                $sellerDeliveryFee
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="seller-side-row">

                                        <span>
                                            Seller Total
                                        </span>

                                        <strong>

                                            RM <?= sellerOrderMoney(
                                                $sellerTotal
                                            ) ?>

                                        </strong>

                                    </div>


                                    <!-- PAYMENT MESSAGE -->

                                    <?php if (
                                        $isOnline &&
                                        $paymentStatusLower ===
                                        'paid'
                                    ): ?>

                                        <div class="seller-payment-message success">

                                            <strong>

                                                <i class="fa-solid fa-circle-check"></i>

                                                Payment Confirmed

                                            </strong>

                                            Customer has successfully
                                            completed the online payment.
                                            You may process this order.

                                        </div>


                                    <?php elseif (
                                        $isOnline &&
                                        $paymentStatusLower ===
                                        'failed'
                                    ): ?>

                                        <div class="seller-payment-message danger">

                                            <strong>

                                                <i class="fa-solid fa-circle-xmark"></i>

                                                Payment Failed

                                            </strong>

                                            Do not process or ship this
                                            order because the payment failed.

                                        </div>


                                    <?php elseif (
                                        $isOnline &&
                                        $paymentStatusLower !==
                                        'paid'
                                    ): ?>

                                        <div class="seller-payment-message warning">

                                            <strong>

                                                <i class="fa-solid fa-clock"></i>

                                                Waiting for Payment

                                            </strong>

                                            Payment has not been confirmed
                                            as Paid yet. Processing is
                                            temporarily disabled.

                                        </div>


                                    <?php elseif (
                                        $isCash &&
                                        $deliveryMethod ===
                                        'Pickup' &&
                                        $paymentStatusLower !==
                                        'paid'
                                    ): ?>

                                        <div class="seller-payment-message info">

                                            <strong>

                                                <i class="fa-solid fa-money-bill-wave"></i>

                                                Cash at Pickup

                                            </strong>

                                            Collect cash from the customer
                                            when they collect the order.

                                        </div>


                                    <?php elseif (
                                        $isCash &&
                                        $deliveryMethod ===
                                        'Vendor Delivery' &&
                                        $paymentStatusLower !==
                                        'paid'
                                    ): ?>

                                        <div class="seller-payment-message info">

                                            <strong>

                                                <i class="fa-solid fa-truck"></i>

                                                Cash on Delivery

                                            </strong>

                                            Collect cash from the customer
                                            when you deliver this order.

                                        </div>


                                    <?php elseif (
                                        $isCash &&
                                        $paymentStatusLower ===
                                        'paid'
                                    ): ?>

                                        <div class="seller-payment-message success">

                                            <strong>

                                                <i class="fa-solid fa-circle-check"></i>

                                                Cash Collected

                                            </strong>

                                            This cash payment has been
                                            marked as Paid.

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        !$isCash &&
                                        !empty(
                                            $order[
                                                'transaction_reference'
                                            ]
                                        )
                                    ): ?>

                                        <div class="seller-transaction">

                                            <strong>
                                                Transaction:
                                            </strong>

                                            <?= sellerOrderEscape(
                                                $order[
                                                    'transaction_reference'
                                                ]
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                </div>


                                <!-- ORDER STATUS -->

                                <div class="seller-side-box">


                                    <div class="seller-side-heading">

                                        <i class="fa-solid fa-arrows-rotate"></i>

                                        Order Status

                                    </div>


                                    <?php if (
                                        $onlineProcessingBlocked
                                    ): ?>

                                        <div class="seller-order-blocked">

                                            <strong>

                                                <i class="fa-solid fa-lock"></i>

                                                Processing Locked

                                            </strong>

                                            Online payment must be
                                            confirmed as Paid before
                                            this order can be processed.

                                        </div>

                                    <?php endif; ?>


                                    <form
                                        method="POST"
                                        class="seller-order-form"
                                        style="margin-top:12px;"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update_status"
                                        >

                                        <input
                                            type="hidden"
                                            name="vendor_order_id"
                                            value="<?= (int)
                                                $order[
                                                    'vendor_order_id'
                                                ] ?>"
                                        >


                                        <label>
                                            Update Status
                                        </label>


                                        <select
                                            name="vendor_status"
                                            required
                                        >

                                            <option
                                                value="Pending"
                                                <?= $vendorStatus ===
                                                    'Pending'
                                                        ? 'selected'
                                                        : '' ?>
                                            >
                                                Pending
                                            </option>


                                            <option
                                                value="Processing"
                                                <?= $vendorStatus ===
                                                    'Processing'
                                                        ? 'selected'
                                                        : '' ?>
                                                <?= $onlineProcessingBlocked
                                                    ? 'disabled'
                                                    : '' ?>
                                            >
                                                Processing
                                            </option>


                                            <option
                                                value="Ready"
                                                <?= $vendorStatus ===
                                                    'Ready'
                                                        ? 'selected'
                                                        : '' ?>
                                                <?= $onlineProcessingBlocked
                                                    ? 'disabled'
                                                    : '' ?>
                                            >
                                                Ready
                                            </option>


                                            <option
                                                value="Shipped"
                                                <?= $vendorStatus ===
                                                    'Shipped'
                                                        ? 'selected'
                                                        : '' ?>
                                                <?= $onlineProcessingBlocked
                                                    ? 'disabled'
                                                    : '' ?>
                                            >
                                                Shipped
                                            </option>


                                            <option
                                                value="Completed"
                                                <?= $vendorStatus ===
                                                    'Completed'
                                                        ? 'selected'
                                                        : '' ?>
                                                <?= $onlineProcessingBlocked
                                                    ? 'disabled'
                                                    : '' ?>
                                            >
                                                Completed
                                            </option>


                                            <option
                                                value="Cancelled"
                                                <?= $vendorStatus ===
                                                    'Cancelled'
                                                        ? 'selected'
                                                        : '' ?>
                                            >
                                                Cancelled
                                            </option>

                                        </select>


                                        <button
                                            type="submit"
                                            class="seller-order-button"
                                        >

                                            <i class="fa-solid fa-floppy-disk"></i>

                                            Update Status

                                        </button>

                                    </form>

                                </div>


                                <!-- =================================================
                                     POSTAGE COURIER + TRACKING
                                ================================================== -->

                                <?php if (
                                    $deliveryMethod ===
                                    'Postage'
                                ): ?>


                                    <div class="seller-side-box">


                                        <div class="seller-side-heading">

                                            <i class="fa-solid fa-truck-fast"></i>

                                            Postage Tracking

                                        </div>


                                        <?php if (
                                            $savedCourier !== '' ||
                                            $savedTracking !== ''
                                        ): ?>

                                            <div class="seller-tracking-current">


                                                <div class="seller-tracking-current-row">

                                                    <span>
                                                        Courier
                                                    </span>

                                                    <strong>
                                                        <?= sellerOrderEscape(
                                                            $savedCourier !== ''
                                                                ? $savedCourier
                                                                : '-'
                                                        ) ?>
                                                    </strong>

                                                </div>


                                                <div class="seller-tracking-current-row">

                                                    <span>
                                                        Tracking No.
                                                    </span>

                                                    <strong>
                                                        <?= sellerOrderEscape(
                                                            $savedTracking !== ''
                                                                ? $savedTracking
                                                                : '-'
                                                        ) ?>
                                                    </strong>

                                                </div>


                                            </div>

                                        <?php endif; ?>


                                        <form
                                            method="POST"
                                            class="seller-order-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_tracking"
                                            >

                                            <input
                                                type="hidden"
                                                name="vendor_order_id"
                                                value="<?= (int)
                                                    $order[
                                                        'vendor_order_id'
                                                    ] ?>"
                                            >


                                            <label>
                                                Courier
                                            </label>


                                            <select
                                                name="courier_name"
                                                required
                                            >

                                                <option value="">
                                                    Select Courier
                                                </option>


                                                <option
                                                    value="J&T Express"
                                                    <?= $savedCourier ===
                                                        'J&T Express'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    J&T Express
                                                </option>


                                                <option
                                                    value="Pos Laju"
                                                    <?= $savedCourier ===
                                                        'Pos Laju'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    Pos Laju
                                                </option>


                                                <option
                                                    value="Ninja Van"
                                                    <?= $savedCourier ===
                                                        'Ninja Van'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    Ninja Van
                                                </option>


                                                <option
                                                    value="DHL eCommerce"
                                                    <?= $savedCourier ===
                                                        'DHL eCommerce'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    DHL eCommerce
                                                </option>


                                                <option
                                                    value="Shopee Express"
                                                    <?= $savedCourier ===
                                                        'Shopee Express'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    Shopee Express
                                                </option>


                                                <option
                                                    value="Flash Express"
                                                    <?= $savedCourier ===
                                                        'Flash Express'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    Flash Express
                                                </option>


                                                <option
                                                    value="Others"
                                                    <?= $savedCourier ===
                                                        'Others'
                                                            ? 'selected'
                                                            : '' ?>
                                                >
                                                    Others
                                                </option>

                                            </select>


                                            <label>
                                                Tracking Number
                                            </label>


                                            <input
                                                type="text"
                                                name="tracking_number"
                                                value="<?= sellerOrderEscape(
                                                    $savedTracking
                                                ) ?>"
                                                maxlength="100"
                                                placeholder="Example: 630123456789"
                                                autocomplete="off"
                                                required
                                            >


                                            <button
                                                type="submit"
                                                class="
                                                    seller-order-button
                                                    secondary
                                                "
                                            >

                                                <i class="fa-solid fa-barcode"></i>

                                                Save Tracking

                                            </button>

                                        </form>

                                    </div>


                                <?php endif; ?>


                            </aside>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>