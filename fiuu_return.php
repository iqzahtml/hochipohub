<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FIUU RETURN
|--------------------------------------------------------------------------
| Customer browser returns here after Fiuu.
|
| IMPORTANT:
| - This file NEVER logs the customer out.
| - Existing customer session is preserved.
| - Browser return data is NOT trusted to mark payment as Paid.
| - fiuu_callback.php remains responsible for payment confirmation.
| - Customer is redirected to the customer dashboard.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/fiuu_config.php';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection unavailable.');
}


/*
|--------------------------------------------------------------------------
| KEEP EXISTING CUSTOMER SESSION
|--------------------------------------------------------------------------
| DO NOT destroy, regenerate or unset the customer login session.
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);

$role = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);


/*
|--------------------------------------------------------------------------
| CUSTOMER SESSION CHECK
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {

    /*
    |--------------------------------------------------------------------------
    | Session is missing.
    | This is NOT a logout.
    | Redirect customer to normal login page.
    |--------------------------------------------------------------------------
    */

    header('Location: index.php?login=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

if ($role !== '' && $role !== 'customer') {
    header('Location: dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId = 0;

if (isset($_POST['orderid'])) {

    $orderId = (int) $_POST['orderid'];

} elseif (isset($_GET['orderid'])) {

    $orderId = (int) $_GET['orderid'];

} elseif (isset($_SESSION['fiuu_last_order_id'])) {

    $orderId = (int) $_SESSION['fiuu_last_order_id'];
}


/*
|--------------------------------------------------------------------------
| CANCEL STATUS
|--------------------------------------------------------------------------
*/

$cancelled =
    isset($_GET['cancel']) &&
    $_GET['cancel'] === '1';


/*
|--------------------------------------------------------------------------
| DEFAULT PAYMENT RESULT
|--------------------------------------------------------------------------
*/

$paymentResult = 'processing';


/*
|--------------------------------------------------------------------------
| VERIFY ORDER
|--------------------------------------------------------------------------
*/

if ($orderId > 0) {

    $stmt = $db->prepare("
        SELECT
            o.order_id,
            o.customer_id,
            o.order_status,

            p.payment_method,
            p.payment_status,
            p.transaction_reference,
            p.payment_date

        FROM orders o

        LEFT JOIN payments p
            ON p.payment_id = (
                SELECT p2.payment_id
                FROM payments p2
                WHERE p2.order_id = o.order_id
                ORDER BY p2.payment_id DESC
                LIMIT 1
            )

        WHERE o.order_id = ?
          AND o.customer_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $orderId,
        $userId
    ]);

    $order = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | ORDER BELONGS TO CUSTOMER
    |--------------------------------------------------------------------------
    */

    if ($order) {

        $paymentStatus = strtolower(
            trim(
                (string) (
                    $order['payment_status']
                    ?? 'pending'
                )
            )
        );


        /*
        |--------------------------------------------------------------------------
        | DETERMINE DISPLAY RESULT
        |--------------------------------------------------------------------------
        | We only READ payment status from database.
        | We do NOT trust browser return data to mark payment as Paid.
        |--------------------------------------------------------------------------
        */

        if ($cancelled) {

            $paymentResult = 'cancelled';

        } elseif ($paymentStatus === 'paid') {

            $paymentResult = 'success';

        } elseif ($paymentStatus === 'failed') {

            $paymentResult = 'failed';

        } elseif ($paymentStatus === 'refunded') {

            $paymentResult = 'refunded';

        } else {

            $paymentResult = 'processing';
        }
    }
}


/*
|--------------------------------------------------------------------------
| CLEAR ONLY FIUU TEMPORARY SESSION VALUE
|--------------------------------------------------------------------------
| IMPORTANT:
| Do NOT session_destroy().
| Do NOT unset user_id.
| Do NOT unset role.
| Customer stays logged in.
|--------------------------------------------------------------------------
*/

unset($_SESSION['fiuu_last_order_id']);


/*
|--------------------------------------------------------------------------
| SAVE PAYMENT RESULT FOR DASHBOARD
|--------------------------------------------------------------------------
*/

$_SESSION['fiuu_payment_result'] =
    $paymentResult;

if ($orderId > 0) {

    $_SESSION['fiuu_payment_order_id'] =
        $orderId;
}


/*
|--------------------------------------------------------------------------
| REDIRECT TO CUSTOMER DASHBOARD
|--------------------------------------------------------------------------
*/

header(
    'Location: dashboard.php?payment=' .
    rawurlencode($paymentResult) .
    (
        $orderId > 0
            ? '&order_id=' . $orderId
            : ''
    )
);

exit;