<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PAYMENT PAGE
|--------------------------------------------------------------------------
| File:
| payment.php
|
| Purpose:
| - Display payment information for an order
| - Allow customer to select payment method
| - Create/update payment record
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$db = getDB();

$user_id = (int) $_SESSION['user_id'];

$order_id = isset($_GET['order_id'])
    ? (int) $_GET['order_id']
    : (int) ($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: order.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        o.*,
        u.name AS customer_name,
        u.email AS customer_email
    FROM orders o
    INNER JOIN users u
        ON o.customer_id = u.user_id
    WHERE o.order_id = ?
    AND o.customer_id = ?
    LIMIT 1
");

$stmt->execute([
    $order_id,
    $user_id
]);

$order = $stmt->fetch();


if (!$order) {
    header('Location: order.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESS PAYMENT
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payment_method =
        trim($_POST['payment_method'] ?? '');

    $csrf_token =
        $_POST['csrf_token'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    if (!verifyCsrfToken($csrf_token)) {

        $error =
            'Invalid security token. Please try again.';

    } elseif (
        !in_array(
            $payment_method,
            [
                'FPX',
                'Credit Card',
                'Debit Card',
                'Cash'
            ],
            true
        )
    ) {

        $error =
            'Please select a valid payment method.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING PAYMENT
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare("
            SELECT *
            FROM payments
            WHERE order_id = ?
            ORDER BY payment_id DESC
            LIMIT 1
        ");

        $stmt->execute([$order_id]);

        $existing_payment =
            $stmt->fetch();


        /*
        |--------------------------------------------------------------------------
        | GENERATE TRANSACTION REFERENCE
        |--------------------------------------------------------------------------
        */

        $transaction_reference =
            'HCH-' .
            date('YmdHis') .
            '-' .
            strtoupper(
                bin2hex(random_bytes(3))
            );


        try {

            $db->beginTransaction();


            if (
                $existing_payment &&
                $existing_payment['payment_status'] === 'Paid'
            ) {

                $error =
                    'This order has already been paid.';

                $db->rollBack();

            } else {

                /*
                |--------------------------------------------------------------------------
                | CREATE PAYMENT
                |--------------------------------------------------------------------------
                */

                if ($existing_payment) {

                    $stmt = $db->prepare("
                        UPDATE payments
                        SET
                            payment_method = ?,
                            payment_status = 'Paid',
                            payment_date = NOW(),
                            amount = ?,
                            transaction_reference = ?
                        WHERE payment_id = ?
                    ");

                    $stmt->execute([
                        $payment_method,
                        $order['total_amount'],
                        $transaction_reference,
                        $existing_payment['payment_id']
                    ]);

                } else {

                    $stmt = $db->prepare("
                        INSERT INTO payments
                        (
                            order_id,
                            payment_method,
                            payment_status,
                            payment_date,
                            amount,
                            transaction_reference
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            'Paid',
                            NOW(),
                            ?,
                            ?
                        )
                    ");

                    $stmt->execute([
                        $order_id,
                        $payment_method,
                        $order['total_amount'],
                        $transaction_reference
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE ORDER
                |--------------------------------------------------------------------------
                */

                $stmt = $db->prepare("
                    UPDATE orders
                    SET order_status = 'Processing'
                    WHERE order_id = ?
                    AND customer_id = ?
                ");

                $stmt->execute([
                    $order_id,
                    $user_id
                ]);


                /*
                |--------------------------------------------------------------------------
                | UPDATE VENDOR ORDERS
                |--------------------------------------------------------------------------
                */

                $stmt = $db->prepare("
                    UPDATE vendor_orders
                    SET vendor_status = 'Processing'
                    WHERE order_id = ?
                    AND vendor_status = 'Pending'
                ");

                $stmt->execute([
                    $order_id
                ]);


                $db->commit();


                $success =
                    'Payment completed successfully.';


                /*
                |--------------------------------------------------------------------------
                | REFRESH ORDER
                |--------------------------------------------------------------------------
                */

                $stmt = $db->prepare("
                    SELECT *
                    FROM orders
                    WHERE order_id = ?
                    AND customer_id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $order_id,
                    $user_id
                ]);

                $order = $stmt->fetch();

            }

        } catch (Exception $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $error =
                'Payment could not be processed. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET PAYMENT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM payments
    WHERE order_id = ?
    ORDER BY payment_id DESC
    LIMIT 1
");

$stmt->execute([$order_id]);

$payment = $stmt->fetch();


$pageTitle = 'Payment - Order #' . $order_id;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">

        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    CHECKOUT
                </span>

                <h1>
                    Payment
                </h1>

                <p>
                    Complete payment for Order #<?= $order_id ?>
                </p>

            </div>

        </section>


        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        ORDER SUMMARY
                    </span>

                    <h2>
                        Order #<?= $order_id ?>
                    </h2>

                </div>

            </div>


            <div class="payment-summary">

                <p>
                    <strong>Customer:</strong>
                    <?= e($order['customer_name']) ?>
                </p>

                <p>
                    <strong>Order Date:</strong>
                    <?= e(
                        date(
                            'd M Y, h:i A',
                            strtotime($order['order_date'])
                        )
                    ) ?>
                </p>

                <p>
                    <strong>Delivery:</strong>
                    <?= e($order['delivery_method']) ?>
                </p>

                <h2>
                    RM <?= number_format(
                        (float) $order['total_amount'],
                        2
                    ) ?>
                </h2>

            </div>


            <?php if (
                $payment &&
                $payment['payment_status'] === 'Paid'
            ): ?>

                <div class="alert alert-success">

                    <strong>
                        Payment completed.
                    </strong>

                    <br>

                    Method:
                    <?= e($payment['payment_method']) ?>

                    <br>

                    Reference:
                    <?= e(
                        $payment['transaction_reference']
                    ) ?>

                </div>

                <a
                    href="order_details.php?order_id=<?= $order_id ?>"
                    class="btn btn-primary"
                >
                    View Order
                </a>


            <?php else: ?>


                <form
                    method="POST"
                    class="dashboard-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrfToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= $order_id ?>"
                    >


                    <div class="form-group">

                        <label for="payment_method">
                            Payment Method
                        </label>

                        <select
                            name="payment_method"
                            id="payment_method"
                            required
                        >

                            <option value="">
                                Select payment method
                            </option>

                            <option value="FPX">
                                FPX
                            </option>

                            <option value="Credit Card">
                                Credit Card
                            </option>

                            <option value="Debit Card">
                                Debit Card
                            </option>

                            <option value="Cash">
                                Cash
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Pay RM
                        <?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>
                    </button>

                </form>

            <?php endif; ?>


        </section>

    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>