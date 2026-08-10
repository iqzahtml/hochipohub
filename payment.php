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
| GET ORDER ID
|--------------------------------------------------------------------------
*/
$orderId = filter_input(
    INPUT_GET,
    'order_id',
    FILTER_VALIDATE_INT
);
if (!$orderId) {
    redirect(baseUrl('order.php'));
}
/*
|--------------------------------------------------------------------------
| GET CUSTOMER ORDER
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Customer can only access their own order.
|
*/
$stmt = $db->prepare("
    SELECT
        o.order_id,
        o.customer_id,
        o.order_date,
        o.total_amount,
        o.delivery_method,
        o.delivery_address,
        o.order_status,
        p.payment_id,
        p.payment_method,
        p.payment_status,
        p.payment_date,
        p.amount,
        p.transaction_reference
    FROM orders o
    LEFT JOIN payments p
        ON p.order_id = o.order_id
    WHERE o.order_id = ?
      AND o.customer_id = ?
    LIMIT 1
");
$stmt->execute([
    $orderId,
    $userId
]);
$order = $stmt->fetch();
/*
|--------------------------------------------------------------------------
| ORDER NOT FOUND
|--------------------------------------------------------------------------
*/
if (!$order) {
    redirect(baseUrl('order.php'));
}
/*
|--------------------------------------------------------------------------
| PROCESS PAYMENT
|--------------------------------------------------------------------------
*/
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = trim(
        $_POST['payment_method'] ?? ''
    );
    /*
    |--------------------------------------------------------------------------
    | VALID PAYMENT METHODS
    |--------------------------------------------------------------------------
    */
    $allowedMethods = [
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ];
    if (
        !in_array(
            $paymentMethod,
            $allowedMethods,
            true
        )
    ) {
        $errors[] =
            'Please select a valid payment method.';
    }
    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE PAYMENT
    |--------------------------------------------------------------------------
    */
    if (
        empty($errors)
        &&
        !empty($order['payment_status'])
        &&
        $order['payment_status'] === 'Paid'
    ) {
        $errors[] =
            'This order has already been paid.';
    }
    /*
    |--------------------------------------------------------------------------
    | PROCESS PAYMENT
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            /*
            |--------------------------------------------------------------------------
            | CHECK AGAIN INSIDE TRANSACTION
            |--------------------------------------------------------------------------
            */
            $checkStmt = $db->prepare("
                SELECT
                    o.order_id,
                    o.total_amount,
                    o.order_status,
                    p.payment_id,
                    p.payment_status
                FROM orders o
                LEFT JOIN payments p
                    ON p.order_id = o.order_id
                WHERE o.order_id = ?
                  AND o.customer_id = ?
                FOR UPDATE
            ");
            $checkStmt->execute([
                $orderId,
                $userId
            ]);
            $currentOrder =
                $checkStmt->fetch();
            if (!$currentOrder) {
                throw new Exception(
                    'Order not found.'
                );
            }
            if (
                !empty(
                    $currentOrder['payment_status']
                )
                &&
                $currentOrder['payment_status']
                === 'Paid'
            ) {
                throw new Exception(
                    'This order has already been paid.'
                );
            }
            /*
            |--------------------------------------------------------------------------
            | TRANSACTION REFERENCE
            |--------------------------------------------------------------------------
            */
            $transactionReference =
                'HCH-'
                . date('YmdHis')
                . '-'
                . $orderId
                . '-'
                . random_int(
                    1000,
                    9999
                );
            /*
            |--------------------------------------------------------------------------
            | PAYMENT DATE
            |--------------------------------------------------------------------------
            */
            $paymentDate =
                date('Y-m-d H:i:s');
            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING PAYMENT
            |--------------------------------------------------------------------------
            */
            $existingPaymentStmt =
                $db->prepare("
                    SELECT payment_id
                    FROM payments
                    WHERE order_id = ?
                    LIMIT 1
                ");
            $existingPaymentStmt->execute([
                $orderId
            ]);
            $existingPayment =
                $existingPaymentStmt->fetch();
            /*
            |--------------------------------------------------------------------------
            | UPDATE OR INSERT PAYMENT
            |--------------------------------------------------------------------------
            */
            if ($existingPayment) {
                $updatePayment = $db->prepare("
                    UPDATE payments
                    SET
                        payment_method = ?,
                        payment_status = 'Paid',
                        payment_date = ?,
                        amount = ?,
                        transaction_reference = ?
                    WHERE payment_id = ?
                ");
                $updatePayment->execute([
                    $paymentMethod,
                    $paymentDate,
                    $currentOrder['total_amount'],
                    $transactionReference,
                    $existingPayment['payment_id']
                ]);
            } else {
                $insertPayment = $db->prepare("
                    INSERT INTO payments (
                        order_id,
                        payment_method,
                        payment_status,
                        payment_date,
                        amount,
                        transaction_reference
                    )
                    VALUES (
                        ?,
                        ?,
                        'Paid',
                        ?,
                        ?,
                        ?
                    )
                ");
                $insertPayment->execute([
                    $orderId,
                    $paymentMethod,
                    $paymentDate,
                    $currentOrder['total_amount'],
                    $transactionReference
                ]);
            }
            /*
            |--------------------------------------------------------------------------
            | UPDATE MAIN ORDER STATUS
            |--------------------------------------------------------------------------
            */
            $updateOrder = $db->prepare("
                UPDATE orders
                SET
                    order_status = 'Processing'
                WHERE order_id = ?
                  AND customer_id = ?
            ");
            $updateOrder->execute([
                $orderId,
                $userId
            ]);
            /*
            |--------------------------------------------------------------------------
            | UPDATE VENDOR ORDERS
            |--------------------------------------------------------------------------
            |
            | Only pending vendor orders move to Processing.
            |
            */
            $updateVendorOrders = $db->prepare("
                UPDATE vendor_orders
                SET
                    vendor_status = 'Processing'
                WHERE order_id = ?
                  AND vendor_status = 'Pending'
            ");
            $updateVendorOrders->execute([
                $orderId
            ]);
            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */
            $db->commit();
            /*
            |--------------------------------------------------------------------------
            | REDIRECT TO ORDER DETAILS
            |--------------------------------------------------------------------------
            */
            redirect(
                baseUrl(
                    'order_details.php?order_id='
                    . $orderId
                    . '&payment=success'
                )
            );
        } catch (
            Throwable $e
        ) {
            if (
                $db->inTransaction()
            ) {
                $db->rollBack();
            }
            if (APP_DEBUG) {
                $errors[] =
                    $e->getMessage();
            } else {
                $errors[] =
                    'Payment could not be processed. Please try again.';
            }
        }
    }
}
/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/
$pageTitle =
    'Payment - Order #' . $orderId;
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
    <?= e($pageTitle) ?> |
    <?= e(APP_NAME) ?>
</title>
<link
    rel="stylesheet"
    href="<?= assetUrl('css/style.css') ?>"
>
<link
    rel="stylesheet"
    href="<?= assetUrl('css/checkout.css') ?>"
>
<link
    rel="stylesheet"
    href="<?= assetUrl('css/responsive.css') ?>"
>
<style>
    .payment-page {
        min-height: 100vh;
        padding:
            45px
            20px
            80px;
        background:
            radial-gradient(
                circle at 10% 10%,
                rgba(
                    37,
                    99,
                    235,
                    .18
                ),
                transparent 32%
            ),
            radial-gradient(
                circle at 90% 30%,
                rgba(
                    59,
                    130,
                    246,
                    .12
                ),
                transparent 30%
            ),
            #020617;
    }
    .payment-container {
        width:
            min(
                1050px,
                100%
            );
        margin:
            auto;
    }
    .payment-header {
        margin-bottom:
            30px;
    }
    .payment-header h1 {
        margin:
            0;
        color:
            #fff;
        font-size:
            38px;
        font-weight:
            850;
        letter-spacing:
            -1px;
    }
    .payment-header p {
        color:
            #94a3b8;
        margin:
            8px 0 0;
    }
    .payment-layout {
        display:
            grid;
        grid-template-columns:
            1.4fr
            .8fr;
        gap:
            24px;
    }
    .payment-card {
        padding:
            28px;
        border-radius:
            26px;
        background:
            linear-gradient(
                145deg,
                rgba(
                    15,
                    23,
                    42,
                    .98
                ),
                rgba(
                    15,
                    23,
                    42,
                    .86
                )
            );
        border:
            1px solid
            rgba(
                59,
                130,
                246,
                .2
            );
        box-shadow:
            0 25px 70px
            rgba(
                0,
                0,
                0,
                .3
            );
    }
    .card-title {
        color:
            #fff;
        font-size:
            20px;
        font-weight:
            800;
        margin-bottom:
            22px;
    }
    .payment-option {
        position:
            relative;
        margin-bottom:
            13px;
    }
    .payment-option input {
        position:
            absolute;
        opacity:
            0;
    }
    .payment-option label {
        display:
            flex;
        align-items:
            center;
        gap:
            15px;
        padding:
            17px;
        border-radius:
            17px;
        cursor:
            pointer;
        background:
            rgba(
                30,
                41,
                59,
                .55
            );
        border:
            1px solid
            rgba(
                148,
                163,
                184,
                .1
            );
        transition:
            .2s ease;
    }
    .payment-option label:hover {
        border-color:
            rgba(
                59,
                130,
                246,
                .55
            );
        transform:
            translateY(
                -2px
            );
    }
    .payment-option
    input:checked
    + label {
        border-color:
            #3b82f6;
        background:
            rgba(
                37,
                99,
                235,
                .14
            );
        box-shadow:
            0 8px 25px
            rgba(
                37,
                99,
                235,
                .12
            );
    }
    .payment-icon {
        width:
            44px;
        height:
            44px;
        display:
            flex;
        align-items:
            center;
        justify-content:
            center;
        border-radius:
            13px;
        background:
            rgba(
                59,
                130,
                246,
                .13
            );
        font-size:
            21px;
    }
    .payment-text strong {
        display:
            block;
        color:
            #fff;
        font-size:
            15px;
    }
    .payment-text span {
        display:
            block;
        margin-top:
            3px;
        color:
            #64748b;
        font-size:
            12px;
    }
    .summary-row {
        display:
            flex;
        justify-content:
            space-between;
        gap:
            15px;
        padding:
            12px 0;
        color:
            #94a3b8;
        border-bottom:
            1px solid
            rgba(
                148,
                163,
                184,
                .08
            );
    }
    .summary-row strong {
        color:
            #e2e8f0;
    }
    .summary-total {
        display:
            flex;
        justify-content:
            space-between;
        align-items:
            center;
        padding-top:
            20px;
        color:
            #fff;
        font-size:
            22px;
        font-weight:
            850;
    }
    .summary-total span:last-child {
        color:
            #60a5fa;
    }
    .pay-button {
        width:
            100%;
        margin-top:
            24px;
        padding:
            15px;
        border:
            0;
        border-radius:
            16px;
        cursor:
            pointer;
        color:
            #fff;
        font-size:
            15px;
        font-weight:
            800;
        background:
            linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );
        box-shadow:
            0 12px 30px
            rgba(
                37,
                99,
                235,
                .3
            );
        transition:
            .2s ease;
    }
    .pay-button:hover {
        transform:
            translateY(
                -2px
            );
        box-shadow:
            0 16px 35px
            rgba(
                37,
                99,
                235,
                .45
            );
    }
    .alert {
        padding:
            15px 17px;
        margin-bottom:
            20px;
        border-radius:
            15px;
        color:
            #fecaca;
        background:
            rgba(
                239,
                68,
                68,
                .1
            );
        border:
            1px solid
            rgba(
                239,
                68,
                68,
                .2
            );
    }
    .order-status {
        display:
            inline-flex;
        padding:
            7px 12px;
        border-radius:
            999px;
        color:
            #60a5fa;
        background:
            rgba(
                59,
                130,
                246,
                .1
            );
        font-size:
            12px;
        font-weight:
            800;
    }
    .back-link {
        display:
            inline-block;
        margin-top:
            18px;
        color:
            #60a5fa;
        text-decoration:
            none;
        font-size:
            14px;
        font-weight:
            700;
    }
    .back-link:hover {
        color:
            #93c5fd;
    }
    .secure-note {
        margin-top:
            20px;
        padding:
            14px;
        border-radius:
            14px;
        color:
            #64748b;
        background:
            rgba(
                2,
                6,
                23,
                .55
            );
        font-size:
            12px;
        line-height:
            1.6;
    }
    @media (
        max-width: 800px
    ) {
        .payment-page {
            padding:
                25px
                15px
                60px;
        }
        .payment-layout {
            grid-template-columns:
                1fr;
        }
        .payment-header h1 {
            font-size:
                30px;
        }
    }
</style>
</head>
<body>
<?php
require_once
    __DIR__
    . '/includes/navbar.php';
?>
<main class="payment-page">
<div class="payment-container">
    <div class="payment-header">
        <h1>
            Complete Your Payment
        </h1>
        <p>
            Secure payment for Order
            #<?= e($orderId) ?>
        </p>
    </div>
    <?php if (!empty($errors)): ?>
        <div class="alert">
            <?php foreach ($errors as $error): ?>
                <div>
                    <?= e($error) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="payment-layout">
        <!-- PAYMENT METHOD -->
        <section class="payment-card">
            <div class="card-title">
                Choose Payment Method
            </div>
            <form
                method="POST"
                action=""
                id="paymentForm"
            >
                <div class="payment-option">
                    <input
                        type="radio"
                        id="fpx"
                        name="payment_method"
                        value="FPX"
                        required
                    >
                    <label for="fpx">
                        <div class="payment-icon">
                            🏦
                        </div>
                        <div class="payment-text">
                            <strong>
                                FPX Online Banking
                            </strong>
                            <span>
                                Pay securely through online banking
                            </span>
                        </div>
                    </label>
                </div>
                <div class="payment-option">
                    <input
                        type="radio"
                        id="credit"
                        name="payment_method"
                        value="Credit Card"
                    >
                    <label for="credit">
                        <div class="payment-icon">
                            💳
                        </div>
                        <div class="payment-text">
                            <strong>
                                Credit Card
                            </strong>
                            <span>
                                Visa / Mastercard
                            </span>
                        </div>
                    </label>
                </div>
                <div class="payment-option">
                    <input
                        type="radio"
                        id="debit"
                        name="payment_method"
                        value="Debit Card"
                    >
                    <label for="debit">
                        <div class="payment-icon">
                            💳
                        </div>
                        <div class="payment-text">
                            <strong>
                                Debit Card
                            </strong>
                            <span>
                                Pay using your debit card
                            </span>
                        </div>
                    </label>
                </div>
                <div class="payment-option">
                    <input
                        type="radio"
                        id="cash"
                        name="payment_method"
                        value="Cash"
                    >
                    <label for="cash">
                        <div class="payment-icon">
                            💵
                        </div>
                        <div class="payment-text">
                            <strong>
                                Cash
                            </strong>
                            <span>
                                Cash payment for pickup orders
                            </span>
                        </div>
                    </label>
                </div>
                <button
                    type="submit"
                    class="pay-button"
                >
                    Pay
                    <?= formatPrice(
                        $order['total_amount']
                    ) ?>
                    →
                </button>
            </form>
            <div class="secure-note">
                🔒 Your payment information is processed
                securely. HochipoHub does not store your
                card details.
            </div>
            <a
                href="<?= baseUrl(
                    'order_details.php?order_id='
                    . $orderId
                ) ?>"
                class="back-link"
            >
                ← Back to Order Details
            </a>
        </section>
        <!-- ORDER SUMMARY -->
        <aside class="payment-card">
            <div class="card-title">
                Order Summary
            </div>
            <div class="summary-row">
                <span>
                    Order ID
                </span>
                <strong>
                    #<?= e($orderId) ?>
                </strong>
            </div>
            <div class="summary-row">
                <span>
                    Order Date
                </span>
                <strong>
                    <?= e(
                        date(
                            'd M Y',
                            strtotime(
                                $order['order_date']
                            )
                        )
                    ) ?>
                </strong>
            </div>
            <div class="summary-row">
                <span>
                    Delivery
                </span>
                <strong>
                    <?= e(
                        $order['delivery_method']
                        ?: 'Not specified'
                    ) ?>
                </strong>
            </div>
            <div class="summary-row">
                <span>
                    Order Status
                </span>
                <span class="order-status">
                    <?= e(
                        $order['order_status']
                    ) ?>
                </span>
            </div>
            <div class="summary-total">
                <span>
                    Total
                </span>
                <span>
                    <?= formatPrice(
                        $order['total_amount']
                    ) ?>
                </span>
            </div>
        </aside>
    </div>
</div>
</main>
<?php
require_once
    __DIR__
    . '/includes/footer.php';
?>
</body>
</html>