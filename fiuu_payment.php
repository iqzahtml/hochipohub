<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FIUU PAYMENT
|--------------------------------------------------------------------------
| Hosted Payment Page
| Extended Verify Payment Enabled
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/fiuu_config.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


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
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| CUSTOMER SESSION
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION['user_id']
    ?? 0
);


if ($userId <= 0) {
    die('Invalid customer session.');
}


/*
|--------------------------------------------------------------------------
| FIUU CONFIG
|--------------------------------------------------------------------------
*/

if (
    !defined('FIUU_MERCHANT_ID') ||
    trim((string) FIUU_MERCHANT_ID) === ''
) {
    die('Fiuu Merchant ID is missing.');
}


if (
    !defined('FIUU_VERIFY_KEY') ||
    trim((string) FIUU_VERIFY_KEY) === ''
) {
    die('Fiuu Verify Key is missing.');
}


if (
    !defined('FIUU_SECRET_KEY') ||
    trim((string) FIUU_SECRET_KEY) === ''
) {
    die('Fiuu Secret Key is missing.');
}


/*
|--------------------------------------------------------------------------
| ORDER ID
|--------------------------------------------------------------------------
*/

$orderId = isset($_GET['order_id'])
    ? (int) $_GET['order_id']
    : 0;


if ($orderId <= 0) {
    die('Invalid order ID.');
}


/*
|--------------------------------------------------------------------------
| GET ORDER + PAYMENT + CUSTOMER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        o.order_id,
        o.customer_id,
        o.total_amount,
        o.order_status,

        p.payment_id,
        p.payment_method,
        p.payment_status,
        p.amount,
        p.payment_gateway,

        u.name,
        u.email,
        u.phone

    FROM orders o

    INNER JOIN payments p
        ON p.order_id = o.order_id

    INNER JOIN users u
        ON u.user_id = o.customer_id

    WHERE o.order_id = ?
    AND o.customer_id = ?

    ORDER BY p.payment_id DESC

    LIMIT 1
");


$stmt->execute([
    $orderId,
    $userId
]);


$order = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$order) {
    die('Order not found.');
}


/*
|--------------------------------------------------------------------------
| ONLINE PAYMENT METHOD ONLY
|--------------------------------------------------------------------------
*/

$onlineMethods = [
    'FPX',
    'Credit Card',
    'Debit Card'
];


if (
    !in_array(
        $order['payment_method'],
        $onlineMethods,
        true
    )
) {
    die('This order does not use Fiuu online payment.');
}


/*
|--------------------------------------------------------------------------
| ALREADY PAID
|--------------------------------------------------------------------------
*/

if (
    strtolower(
        trim(
            (string) $order['payment_status']
        )
    ) === 'paid'
) {

    header(
        'Location: order_details.php?id=' .
        $orderId
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| MERCHANT
|--------------------------------------------------------------------------
*/

$merchantId = trim(
    (string) FIUU_MERCHANT_ID
);


$verifyKey = trim(
    (string) FIUU_VERIFY_KEY
);


/*
|--------------------------------------------------------------------------
| FIUU ORDER ID
|--------------------------------------------------------------------------
*/

$fiuuOrderId = (string) $orderId;


/*
|--------------------------------------------------------------------------
| AMOUNT
|--------------------------------------------------------------------------
*/

$amount = number_format(
    (float) $order['amount'],
    2,
    '.',
    ''
);


if ((float) $amount <= 0) {
    die('Invalid payment amount.');
}


/*
|--------------------------------------------------------------------------
| CURRENCY
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Extended Verify Payment requires currency in vcode.
|
|--------------------------------------------------------------------------
*/

$currency = 'MYR';


/*
|--------------------------------------------------------------------------
| CUSTOMER DETAILS
|--------------------------------------------------------------------------
*/

$billName = trim(
    (string) (
        $order['name']
        ?? ''
    )
);


$billEmail = trim(
    (string) (
        $order['email']
        ?? ''
    )
);


$billMobile = trim(
    (string) (
        $order['phone']
        ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| NAME
|--------------------------------------------------------------------------
*/

if ($billName === '') {
    die('Customer name is required.');
}


/*
|--------------------------------------------------------------------------
| EMAIL
|--------------------------------------------------------------------------
*/

if ($billEmail === '') {
    die('Customer email is required.');
}


if (
    !filter_var(
        $billEmail,
        FILTER_VALIDATE_EMAIL
    )
) {
    die('Customer email format is invalid.');
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

if ($billMobile === '') {
    die('Customer mobile number is required.');
}


/*
|--------------------------------------------------------------------------
| CLEAN MOBILE
|--------------------------------------------------------------------------
*/

$billMobile = preg_replace(
    '/[^0-9+]/',
    '',
    $billMobile
);


if ($billMobile === '') {
    die('Customer mobile number format is invalid.');
}


/*
|--------------------------------------------------------------------------
| BILL DESCRIPTION
|--------------------------------------------------------------------------
*/

$billDescription =
    'HochipoHub Order ' .
    $fiuuOrderId;


/*
|--------------------------------------------------------------------------
| EXTENDED VCODE
|--------------------------------------------------------------------------
|
| FIUU EXTENDED VERIFY FORMULA:
|
| vcode =
|
| md5(
|     amount
|     . merchantID
|     . orderID
|     . verifyKey
|     . currency
| )
|
|--------------------------------------------------------------------------
*/

$vcode = md5(
    $amount .
    $merchantId .
    $fiuuOrderId .
    $verifyKey .
    $currency
);


/*
|--------------------------------------------------------------------------
| FIUU PRODUCTION URL
|--------------------------------------------------------------------------
*/

$paymentUrl =
    'https://pay.fiuu.com/RMS/pay/' .
    rawurlencode(
        $merchantId
    );


/*
|--------------------------------------------------------------------------
| PAYMENT REQUEST PARAMETERS
|--------------------------------------------------------------------------
*/

$params = [

    'amount' =>
        $amount,

    'orderid' =>
        $fiuuOrderId,

    'bill_name' =>
        $billName,

    'bill_email' =>
        $billEmail,

    'bill_mobile' =>
        $billMobile,

    'bill_desc' =>
        $billDescription,

    'currency' =>
        $currency,

    'returnurl' =>
        FIUU_RETURN_URL,

    'callbackurl' =>
        FIUU_CALLBACK_URL,

    'cancelurl' =>
        FIUU_CANCEL_URL,

    'vcode' =>
        $vcode
];


/*
|--------------------------------------------------------------------------
| BUILD QUERY STRING
|--------------------------------------------------------------------------
*/

$queryString = http_build_query(
    $params,
    '',
    '&',
    PHP_QUERY_RFC3986
);


/*
|--------------------------------------------------------------------------
| FINAL URL
|--------------------------------------------------------------------------
*/

$finalPaymentUrl =
    $paymentUrl .
    '?' .
    $queryString;


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT RECORD
|--------------------------------------------------------------------------
*/

$updateStmt = $db->prepare("
    UPDATE payments

    SET
        payment_gateway = 'Fiuu'

    WHERE payment_id = ?
");


$updateStmt->execute([
    (int) $order['payment_id']
]);


/*
|--------------------------------------------------------------------------
| SESSION BACKUP
|--------------------------------------------------------------------------
*/

$_SESSION['fiuu_last_order_id'] =
    $orderId;


/*
|--------------------------------------------------------------------------
| REDIRECT TO FIUU
|--------------------------------------------------------------------------
*/

header(
    'Location: ' .
    $finalPaymentUrl
);

exit;