<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FIUU CALLBACK
|--------------------------------------------------------------------------
| File: fiuu_callback.php
|
| IMPORTANT:
| - This is a SERVER-TO-SERVER endpoint.
| - Do NOT require customer login/session here.
| - Do NOT redirect customer here.
| - fiuu_return.php handles browser return.
| - This file updates the real payment status.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/fiuu_config.php';


/*
|--------------------------------------------------------------------------
| RESPONSE TYPE
|--------------------------------------------------------------------------
*/

header('Content-Type: text/plain; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {

    http_response_code(500);

    echo 'DATABASE_ERROR';

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK FIUU CONFIG
|--------------------------------------------------------------------------
*/

if (
    !defined('FIUU_SECRET_KEY') ||
    trim((string) FIUU_SECRET_KEY) === ''
) {

    http_response_code(500);

    echo 'CONFIG_ERROR';

    exit;
}


/*
|--------------------------------------------------------------------------
| GET FIUU CALLBACK VALUES
|--------------------------------------------------------------------------
|
| Fiuu normally POSTs these values:
|
| nbcb
| tranID
| orderid
| status
| domain
| amount
| currency
| appcode
| paydate
| skey
|
|--------------------------------------------------------------------------
*/

$nbcb =
    trim(
        (string) (
            $_POST['nbcb']
            ?? $_GET['nbcb']
            ?? ''
        )
    );


$tranID =
    trim(
        (string) (
            $_POST['tranID']
            ?? $_GET['tranID']
            ?? ''
        )
    );


$orderIdRaw =
    trim(
        (string) (
            $_POST['orderid']
            ?? $_GET['orderid']
            ?? ''
        )
    );


$status =
    trim(
        (string) (
            $_POST['status']
            ?? $_GET['status']
            ?? ''
        )
    );


$domain =
    trim(
        (string) (
            $_POST['domain']
            ?? $_GET['domain']
            ?? ''
        )
    );


$amount =
    trim(
        (string) (
            $_POST['amount']
            ?? $_GET['amount']
            ?? ''
        )
    );


$currency =
    trim(
        (string) (
            $_POST['currency']
            ?? $_GET['currency']
            ?? ''
        )
    );


$appcode =
    trim(
        (string) (
            $_POST['appcode']
            ?? $_GET['appcode']
            ?? ''
        )
    );


$paydate =
    trim(
        (string) (
            $_POST['paydate']
            ?? $_GET['paydate']
            ?? ''
        )
    );


$skey =
    trim(
        (string) (
            $_POST['skey']
            ?? $_GET['skey']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| VALIDATE REQUIRED VALUES
|--------------------------------------------------------------------------
*/

if (
    $tranID === '' ||
    $orderIdRaw === '' ||
    $status === '' ||
    $domain === '' ||
    $amount === '' ||
    $currency === '' ||
    $paydate === '' ||
    $skey === ''
) {

    http_response_code(400);

    echo 'MISSING_PARAMETER';

    exit;
}


/*
|--------------------------------------------------------------------------
| ORDER ID
|--------------------------------------------------------------------------
|
| HochipoHub uses integer order IDs.
|
|--------------------------------------------------------------------------
*/

$orderId =
    (int) $orderIdRaw;


if ($orderId <= 0) {

    http_response_code(400);

    echo 'INVALID_ORDER';

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFY FIUU SIGNATURE
|--------------------------------------------------------------------------
|
| key0:
|
| md5(
|     tranID .
|     orderid .
|     status .
|     domain .
|     amount .
|     currency
| )
|
| key1:
|
| md5(
|     paydate .
|     domain .
|     key0 .
|     appcode .
|     SECRET_KEY
| )
|
|--------------------------------------------------------------------------
*/

$key0 =
    md5(
        $tranID .
        $orderIdRaw .
        $status .
        $domain .
        $amount .
        $currency
    );


$expectedSkey =
    md5(
        $paydate .
        $domain .
        $key0 .
        $appcode .
        FIUU_SECRET_KEY
    );


/*
|--------------------------------------------------------------------------
| INVALID SIGNATURE
|--------------------------------------------------------------------------
*/

if (
    !hash_equals(
        strtolower($expectedSkey),
        strtolower($skey)
    )
) {

    http_response_code(400);

    echo 'INVALID_SIGNATURE';

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER + LATEST PAYMENT
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            o.order_id,
            o.customer_id,
            o.total_amount,
            o.order_status,

            p.payment_id,
            p.payment_method,
            p.payment_status,
            p.amount AS payment_amount,
            p.payment_gateway,
            p.transaction_reference,
            p.gateway_order_reference,
            p.payment_date

        FROM orders o

        LEFT JOIN payments p
            ON p.payment_id = (

                SELECT
                    p2.payment_id

                FROM payments p2

                WHERE
                    p2.order_id = o.order_id

                ORDER BY
                    p2.payment_id DESC

                LIMIT 1
            )

        WHERE
            o.order_id = ?

        LIMIT 1
    ");


$stmt->execute([
    $orderId
]);


$order =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| ORDER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$order) {

    http_response_code(404);

    echo 'ORDER_NOT_FOUND';

    exit;
}


/*
|--------------------------------------------------------------------------
| PAYMENT RECORD REQUIRED
|--------------------------------------------------------------------------
*/

$paymentId =
    (int) (
        $order['payment_id']
        ?? 0
    );


if ($paymentId <= 0) {

    http_response_code(404);

    echo 'PAYMENT_NOT_FOUND';

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFY ONLINE PAYMENT
|--------------------------------------------------------------------------
*/

$paymentMethod =
    trim(
        (string) (
            $order['payment_method']
            ?? ''
        )
    );


$allowedOnlineMethods = [
    'FPX',
    'Credit Card',
    'Debit Card'
];


if (
    !in_array(
        $paymentMethod,
        $allowedOnlineMethods,
        true
    )
) {

    http_response_code(400);

    echo 'NOT_ONLINE_PAYMENT';

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFY PAYMENT GATEWAY
|--------------------------------------------------------------------------
*/

$paymentGateway =
    trim(
        (string) (
            $order['payment_gateway']
            ?? ''
        )
    );


if (
    $paymentGateway !== '' &&
    strtolower($paymentGateway) !== 'fiuu'
) {

    http_response_code(400);

    echo 'INVALID_PAYMENT_GATEWAY';

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFY AMOUNT
|--------------------------------------------------------------------------
|
| Never mark an order Paid when the callback amount does not match
| the payment amount stored by HochipoHub.
|
|--------------------------------------------------------------------------
*/

$databaseAmount =
    number_format(
        (float) (
            $order['payment_amount']
            ?? $order['total_amount']
            ?? 0
        ),
        2,
        '.',
        ''
    );


$callbackAmount =
    number_format(
        (float) $amount,
        2,
        '.',
        ''
    );


if (
    !hash_equals(
        $databaseAmount,
        $callbackAmount
    )
) {

    http_response_code(400);

    echo 'AMOUNT_MISMATCH';

    exit;
}


/*
|--------------------------------------------------------------------------
| OPTIONAL CURRENCY CHECK
|--------------------------------------------------------------------------
|
| HochipoHub uses MYR.
|
|--------------------------------------------------------------------------
*/

if (
    strtoupper($currency) !== 'MYR'
) {

    http_response_code(400);

    echo 'INVALID_CURRENCY';

    exit;
}


/*
|--------------------------------------------------------------------------
| MAP FIUU STATUS
|--------------------------------------------------------------------------
|
| 00 = Successful
| 11 = Failed
| 22 = Pending
|
|--------------------------------------------------------------------------
*/

$newPaymentStatus = 'Pending';


switch ($status) {

    case '00':

        $newPaymentStatus =
            'Paid';

        break;


    case '11':

        $newPaymentStatus =
            'Failed';

        break;


    case '22':

        $newPaymentStatus =
            'Pending';

        break;


    default:

        /*
        |--------------------------------------------------------------------------
        | UNKNOWN STATUS
        |--------------------------------------------------------------------------
        |
        | Never assume an unknown value means Paid.
        |
        |--------------------------------------------------------------------------
        */

        $newPaymentStatus =
            'Pending';

        break;
}


/*
|--------------------------------------------------------------------------
| NORMALIZE PAYMENT DATE
|--------------------------------------------------------------------------
*/

$paymentDate = null;


if ($newPaymentStatus === 'Paid') {

    if ($paydate !== '') {

        $timestamp =
            strtotime(
                $paydate
            );


        if ($timestamp !== false) {

            $paymentDate =
                date(
                    'Y-m-d H:i:s',
                    $timestamp
                );
        }
    }


    if ($paymentDate === null) {

        $paymentDate =
            date(
                'Y-m-d H:i:s'
            );
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | LOCK PAYMENT ROW
    |--------------------------------------------------------------------------
    */

    $lockStmt =
        $db->prepare("
            SELECT

                payment_id,
                payment_status,
                transaction_reference

            FROM payments

            WHERE payment_id = ?

            FOR UPDATE
        ");


    $lockStmt->execute([
        $paymentId
    ]);


    $currentPayment =
        $lockStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$currentPayment) {

        throw new Exception(
            'Payment record no longer exists.'
        );
    }


    $currentStatus =
        strtolower(
            trim(
                (string) (
                    $currentPayment[
                        'payment_status'
                    ]
                    ?? ''
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | PROTECT ALREADY PAID PAYMENT
    |--------------------------------------------------------------------------
    |
    | If a payment is already Paid, a later duplicate/pending/failed
    | callback must not accidentally downgrade it.
    |
    |--------------------------------------------------------------------------
    */

    if (
        $currentStatus === 'paid' &&
        $newPaymentStatus !== 'Paid'
    ) {

        $newPaymentStatus =
            'Paid';
    }


    /*
    |--------------------------------------------------------------------------
    | PAID
    |--------------------------------------------------------------------------
    */

    if (
        $newPaymentStatus ===
        'Paid'
    ) {

        $updateStmt =
            $db->prepare("
                UPDATE payments

                SET

                    payment_status =
                        'Paid',

                    transaction_reference =
                        ?,

                    gateway_order_reference =
                        COALESCE(
                            gateway_order_reference,
                            ?
                        ),

                    payment_gateway =
                        'Fiuu',

                    payment_date =
                        COALESCE(
                            payment_date,
                            ?
                        )

                WHERE payment_id = ?
            ");


        $updateStmt->execute([

            $tranID,

            $orderIdRaw,

            $paymentDate,

            $paymentId
        ]);


    /*
    |--------------------------------------------------------------------------
    | FAILED
    |--------------------------------------------------------------------------
    */

    } elseif (
        $newPaymentStatus ===
        'Failed'
    ) {

        $updateStmt =
            $db->prepare("
                UPDATE payments

                SET

                    payment_status =
                        'Failed',

                    transaction_reference =
                        ?,

                    gateway_order_reference =
                        COALESCE(
                            gateway_order_reference,
                            ?
                        ),

                    payment_gateway =
                        'Fiuu'

                WHERE payment_id = ?
            ");


        $updateStmt->execute([

            $tranID,

            $orderIdRaw,

            $paymentId
        ]);


    /*
    |--------------------------------------------------------------------------
    | PENDING
    |--------------------------------------------------------------------------
    */

    } else {

        $updateStmt =
            $db->prepare("
                UPDATE payments

                SET

                    payment_status =
                        CASE

                            WHEN payment_status =
                                 'Paid'

                            THEN
                                'Paid'

                            ELSE
                                'Pending'

                        END,

                    transaction_reference =
                        CASE

                            WHEN transaction_reference IS NULL
                            OR transaction_reference = ''

                            THEN ?

                            ELSE
                                transaction_reference

                        END,

                    gateway_order_reference =
                        COALESCE(
                            gateway_order_reference,
                            ?
                        ),

                    payment_gateway =
                        'Fiuu'

                WHERE payment_id = ?
            ");


        $updateStmt->execute([

            $tranID,

            $orderIdRaw,

            $paymentId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $db->commit();


} catch (Throwable $exception) {

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | LOG ERROR SERVER SIDE
    |--------------------------------------------------------------------------
    |
    | Do not print database/internal exception details to Fiuu.
    |
    |--------------------------------------------------------------------------
    */

    error_log(
        'FIUU CALLBACK ERROR - ORDER ' .
        $orderId .
        ': ' .
        $exception->getMessage()
    );


    http_response_code(500);

    echo 'UPDATE_FAILED';

    exit;
}


/*
|--------------------------------------------------------------------------
| CALLBACK ACKNOWLEDGEMENT
|--------------------------------------------------------------------------
|
| Fiuu Callback IPN expects:
|
| CBTOKEN:MPSTATOK
|
| Keep this as plain text.
|
|--------------------------------------------------------------------------
*/

if (
    $nbcb === '1'
) {

    echo 'CBTOKEN:MPSTATOK';

    exit;
}


/*
|--------------------------------------------------------------------------
| DEFAULT RESPONSE
|--------------------------------------------------------------------------
|
| This also makes the endpoint usable if Fiuu calls it without nbcb.
|--------------------------------------------------------------------------
*/

echo 'OK';

exit;