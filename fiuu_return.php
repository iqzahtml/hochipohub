<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FIUU RETURN
|--------------------------------------------------------------------------
| Customer browser returns here after Fiuu.
|
| IMPORTANT:
| - This file NEVER logs the customer out.
| - Browser return data is NOT trusted to mark a payment as Paid.
| - fiuu_callback.php remains responsible for payment confirmation.
| - Customer is redirected back into the normal HochipoHub customer page.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/fiuu_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection unavailable.');
}

/*
|--------------------------------------------------------------------------
| KEEP EXISTING CUSTOMER SESSION
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: index.php?login=1');
    exit;
}

$role = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);

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

if ($orderId <= 0) {
    header('Location: order.php');
    exit;
}

$cancelled =
    isset($_GET['cancel']) &&
    $_GET['cancel'] === '1';

/*
|--------------------------------------------------------------------------
| VERIFY ORDER BELONGS TO LOGGED-IN CUSTOMER
|--------------------------------------------------------------------------
*/

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

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: order.php');
    exit;
}

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
| CLEAR ONLY THE FIUU HELPER VALUE
| Customer login/session remains untouched.
|--------------------------------------------------------------------------
*/

unset($_SESSION['fiuu_last_order_id']);

/*
|--------------------------------------------------------------------------
| REDIRECT BACK TO CUSTOMER ORDER DETAILS
|--------------------------------------------------------------------------
*/

$paymentResult = 'processing';

if ($cancelled) {
    $paymentResult = 'cancelled';
} elseif ($paymentStatus === 'paid') {
    $paymentResult = 'success';
} elseif ($paymentStatus === 'failed') {
    $paymentResult = 'failed';
} elseif ($paymentStatus === 'refunded') {
    $paymentResult = 'refunded';
}

header(
    'Location: order_details.php?id=' .
    $orderId .
    '&payment=' .
    rawurlencode($paymentResult)
);

exit;