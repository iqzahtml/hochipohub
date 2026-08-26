<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN PAYMENTS
|--------------------------------------------------------------------------
| File: admin/payments.php
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

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';


$db = getDB();


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    strtolower(
        trim(
            $_SESSION['role']
            ?? ''
        )
    ) !== 'admin'
) {

    header('Location: ../index.php');
    exit;
}


$adminId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('paymentEscape')) {

    function paymentEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('paymentMoney')) {

    function paymentMoney($value): string
    {
        return 'RM ' .
            number_format(
                (float) $value,
                2
            );
    }
}


if (!function_exists('paymentStatusClass')) {

    function paymentStatusClass($status): string
    {
        switch ($status) {

            case 'Paid':
                return 'paid';

            case 'Failed':
                return 'failed';

            case 'Refunded':
                return 'refunded';

            case 'Pending':
            default:
                return 'pending';
        }
    }
}


if (!function_exists('paymentDate')) {

    function paymentDate($date): string
    {
        if (!$date) {
            return '-';
        }


        $timestamp =
            strtotime($date);


        if (!$timestamp) {
            return '-';
        }


        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$error = '';


if (
    isset($_GET['success']) &&
    $_GET['success'] === 'status'
) {

    $message =
        'Payment status updated successfully.';
}


if (isset($_GET['error'])) {

    switch ($_GET['error']) {

        case 'security':

            $error =
                'Invalid security token. Please refresh and try again.';

            break;


        case 'invalid':

            $error =
                'Invalid payment information.';

            break;


        case 'notfound':

            $error =
                'Payment record not found.';

            break;


        default:

            $error =
                'Unable to process the payment request.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_payment'])
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: payments.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | INPUT
    |--------------------------------------------------------------------------
    */

    $paymentId =
        isset($_POST['payment_id'])
            ? (int) $_POST['payment_id']
            : 0;


    $newStatus =
        isset($_POST['payment_status'])
            ? trim(
                $_POST['payment_status']
            )
            : '';


    $allowedStatuses = [

        'Pending',
        'Paid',
        'Failed',
        'Refunded'

    ];


    if (
        $paymentId <= 0 ||
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        header(
            'Location: payments.php?error=invalid'
        );

        exit;
    }


    try {

        $db->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | CHECK PAYMENT
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    payment_id,
                    order_id

                FROM payments

                WHERE payment_id = ?

                LIMIT 1

                FOR UPDATE
            ");


        $stmt->execute([
            $paymentId
        ]);


        $payment =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$payment) {

            $db->rollBack();


            header(
                'Location: payments.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE PAYMENT
        |--------------------------------------------------------------------------
        */

        if ($newStatus === 'Paid') {

            $stmt =
                $db->prepare("
                    UPDATE payments

                    SET
                        payment_status = ?,
                        payment_date = COALESCE(
                            payment_date,
                            NOW()
                        )

                    WHERE payment_id = ?
                ");

        }

        else {

            $stmt =
                $db->prepare("
                    UPDATE payments

                    SET
                        payment_status = ?

                    WHERE payment_id = ?
                ");
        }


        $stmt->execute([
            $newStatus,
            $paymentId
        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $action =
            'Updated payment #' .
            $paymentId .
            ' status to ' .
            $newStatus;


        $log =
            $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        $log->execute([

            $adminId,
            $action,
            'payment',
            $paymentId

        ]);


        $db->commit();


        header(
            'Location: payments.php?success=status'
        );

        exit;

    }

    catch (Throwable $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }


        error_log(
            'HOCHIPOHUB ADMIN PAYMENT UPDATE ERROR: ' .
            $e->getMessage()
        );


        header(
            'Location: payments.php?error=update'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$statusFilter =
    $_GET['status']
    ?? '';


$methodFilter =
    $_GET['method']
    ?? '';


/*
|--------------------------------------------------------------------------
| PAYMENT METHODS
|--------------------------------------------------------------------------
*/

$paymentMethods = [];


try {

    $stmt =
        $db->query("
            SELECT DISTINCT payment_method

            FROM payments

            WHERE payment_method IS NOT NULL

            AND payment_method != ''

            ORDER BY payment_method ASC
        ");


    $paymentMethods =
        $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );

}

catch (Throwable $e) {

    $paymentMethods = [];
}


/*
|--------------------------------------------------------------------------
| FETCH PAYMENTS
|--------------------------------------------------------------------------
*/

$payments = [];


try {

    $sql = "
        SELECT

            p.payment_id,
            p.order_id,
            p.payment_method,
            p.payment_status,
            p.payment_date,
            p.amount,
            p.transaction_reference,

            o.order_date,
            o.order_status,

            u.user_id AS customer_id,
            u.name AS customer_name,
            u.email AS customer_email

        FROM payments p

        INNER JOIN orders o
            ON p.order_id = o.order_id

        INNER JOIN users u
            ON o.customer_id = u.user_id

        WHERE 1 = 1
    ";


    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            AND
            (
                CAST(
                    p.payment_id AS CHAR
                ) LIKE ?

                OR CAST(
                    p.order_id AS CHAR
                ) LIKE ?

                OR u.name LIKE ?

                OR u.email LIKE ?

                OR p.transaction_reference LIKE ?
            )
        ";


        $searchValue =
            '%' .
            $search .
            '%';


        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $statusFilter,
            [
                'Pending',
                'Paid',
                'Failed',
                'Refunded'
            ],
            true
        )
    ) {

        $sql .= "
            AND p.payment_status = ?
        ";


        $params[] =
            $statusFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | METHOD FILTER
    |--------------------------------------------------------------------------
    */

    if ($methodFilter !== '') {

        $sql .= "
            AND p.payment_method = ?
        ";


        $params[] =
            $methodFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY
            p.payment_id DESC
    ";


    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $payments =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $payments = [];


    error_log(
        'HOCHIPOHUB ADMIN FETCH PAYMENTS ERROR: ' .
        $e->getMessage()
    );


    $error =
        'Unable to load payments.';
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalPayments = 0;

$pendingPayments = 0;
$paidPayments = 0;
$failedPayments = 0;
$refundedPayments = 0;

$totalPaidAmount = 0;
$totalPendingAmount = 0;
$totalRefundedAmount = 0;


try {

    /*
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    */

    $totalPayments =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM payments
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PENDING
    |--------------------------------------------------------------------------
    */

    $pendingPayments =
        (int)
        $db
            ->query("
                SELECT COUNT(*)

                FROM payments

                WHERE payment_status = 'Pending'
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PAID
    |--------------------------------------------------------------------------
    */

    $paidPayments =
        (int)
        $db
            ->query("
                SELECT COUNT(*)

                FROM payments

                WHERE payment_status = 'Paid'
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | FAILED
    |--------------------------------------------------------------------------
    */

    $failedPayments =
        (int)
        $db
            ->query("
                SELECT COUNT(*)

                FROM payments

                WHERE payment_status = 'Failed'
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | REFUNDED
    |--------------------------------------------------------------------------
    */

    $refundedPayments =
        (int)
        $db
            ->query("
                SELECT COUNT(*)

                FROM payments

                WHERE payment_status = 'Refunded'
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PAID AMOUNT
    |--------------------------------------------------------------------------
    */

    $totalPaidAmount =
        (float)
        $db
            ->query("
                SELECT
                    COALESCE(
                        SUM(amount),
                        0
                    )

                FROM payments

                WHERE payment_status = 'Paid'
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PENDING AMOUNT
    |--------------------------------------------------------------------------
    */

    $totalPendingAmount =
        (float)
        $db
            ->query("
                SELECT
                    COALESCE(
                        SUM(amount),
                        0
                    )

                FROM payments

                WHERE payment_status = 'Pending'
            ")
            ->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | REFUNDED AMOUNT
    |--------------------------------------------------------------------------
    */

    $totalRefundedAmount =
        (float)
        $db
            ->query("
                SELECT
                    COALESCE(
                        SUM(amount),
                        0
                    )

                FROM payments

                WHERE payment_status = 'Refunded'
            ")
            ->fetchColumn();

}

catch (Throwable $e) {

    error_log(
        'HOCHIPOHUB PAYMENT STATISTICS ERROR: ' .
        $e->getMessage()
    );
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
        Payments | HochipoHub Admin
    </title>


    <!-- ============================================================
         POPPINS
    ============================================================= -->

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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | ROOT
        |--------------------------------------------------------------------------
        */

        :root {

            --payments-sidebar-width:
                260px;

            --payments-border:
                #dce7f3;

            --payments-text:
                #0b2d63;

            --payments-muted:
                #8294b3;

        }


        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing:
                border-box;

        }


        html,
        body {

            margin:
                0;

            padding:
                0;

            min-height:
                100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                #eef5fd;

        }


        body {

            overflow-x:
                hidden;

        }


        button,
        input,
        select {

            font-family:
                inherit;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR FONT
        |--------------------------------------------------------------------------
        */

        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {

            font-family:
                'Poppins',
                sans-serif !important;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .payments-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --payments-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --payments-sidebar-width
                    )
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .payments-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                38px
                35px
                70px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .payments-hero {

            position:
                relative;

            min-height:
                155px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                34px
                38px;

            margin-bottom:
                26px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius:
                26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .payments-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                260px;

            height:
                260px;

            right:
                -70px;

            top:
                -140px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .payments-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                170px;

            height:
                170px;

            right:
                155px;

            bottom:
                -110px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .payments-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .payments-hero h1 {

            margin:
                0
                0
                8px;

            color:
                #ffffff;

            font-size:
                38px;

            line-height:
                1.05;

            font-weight:
                800;

            letter-spacing:
                -1.5px;

        }


        .payments-hero p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size:
                14px;

            font-weight:
                500;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO ICON
        |--------------------------------------------------------------------------
        */

        .payments-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                82px;

            height:
                82px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .26
                );

            border-radius:
                22px;

            background:

                linear-gradient(
                    145deg,
                    rgba(
                        255,
                        255,
                        255,
                        .20
                    ),
                    rgba(
                        255,
                        255,
                        255,
                        .10
                    )
                );

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .25
                ),

                0
                12px
                30px
                rgba(
                    0,
                    35,
                    100,
                    .18
                );

            font-size:
                34px;

            line-height:
                1;

        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .payments-alert {

            margin-bottom:
                22px;

            padding:
                14px
                17px;

            border-radius:
                12px;

            font-size:
                11px;

            font-weight:
                600;

        }


        .payments-alert.success {

            color:
                #166534;

            background:
                #ecfdf5;

            border:
                1px solid
                #bbf7d0;

        }


        .payments-alert.error {

            color:
                #991b1b;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .payments-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    5,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                18px;

            margin-bottom:
                30px;

        }


        .payment-stat {

            position:
                relative;

            min-height:
                150px;

            overflow:
                hidden;

            padding:
                25px
                22px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --payments-border
                );

            border-top:
                4px solid
                #2563eb;

            border-radius:
                20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .payment-stat::after {

            content:
                "";

            position:
                absolute;

            right:
                -29px;

            bottom:
                -45px;

            width:
                110px;

            height:
                110px;

            border-radius:
                50%;

            background:
                #edf4ff;

        }


        .payment-stat.paid {

            border-top-color:
                #16a34a;

        }


        .payment-stat.paid::after {

            background:
                #eaf9ef;

        }


        .payment-stat.pending {

            border-top-color:
                #f59e0b;

        }


        .payment-stat.pending::after {

            background:
                #fff7df;

        }


        .payment-stat.failed {

            border-top-color:
                #ef4444;

        }


        .payment-stat.failed::after {

            background:
                #fff0f1;

        }


        .payment-stat.refunded {

            border-top-color:
                #8b5cf6;

        }


        .payment-stat.refunded::after {

            background:
                #f4efff;

        }


        .payment-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                15px;

            color:
                #61728e;

            font-size:
                10px;

            font-weight:
                800;

            letter-spacing:
                .75px;

            text-transform:
                uppercase;

        }


        .payment-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #0b326d;

            font-size:
                32px;

            line-height:
                1;

            font-weight:
                800;

        }


        .payment-stat-money {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-top:
                10px;

            color:
                #2563eb;

            font-size:
                10px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .payments-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --payments-border
                );

            border-radius:
                24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .payments-panel-header {

            min-height:
                110px;

            padding:
                26px
                30px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid
                #e7edf5;

        }


        .payments-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .payments-panel-icon {

            width:
                53px;

            height:
                53px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                16px;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size:
                22px;

            line-height:
                1;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .payments-panel-header h2 {

            margin:
                0
                0
                5px;

            color:
                #092e65;

            font-size:
                20px;

            font-weight:
                800;

        }


        .payments-panel-header p {

            margin:
                0;

            color:
                #8999b4;

            font-size:
                11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT
        |--------------------------------------------------------------------------
        */

        .payments-count {

            min-height:
                36px;

            padding:
                0
                16px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #d6e7ff;

            border-radius:
                999px;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .payments-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .payments-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1.5fr
                )

                minmax(
                    150px,
                    .5fr
                )

                minmax(
                    150px,
                    .5fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .payments-filter input,
        .payments-filter select {

            width:
                100%;

            height:
                43px;

            padding:
                0
                13px;

            outline:
                none;

            color:
                #26354e;

            background:
                #ffffff;

            border:
                1px solid
                #d8e3ef;

            border-radius:
                10px;

            font-size:
                10px;

        }


        .payments-filter input:focus,
        .payments-filter select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .payment-btn {

            min-height:
                43px;

            padding:
                0
                17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .payment-btn-primary {

            color:
                #ffffff;

            border:
                0;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

        }


        .payment-btn-secondary {

            color:
                #66758b;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ee;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .payments-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .payments-table {

            width:
                100%;

            min-width:
                1180px;

            border-collapse:
                collapse;

        }


        .payments-table thead {

            background:
                #f6f9fd;

        }


        .payments-table th {

            height:
                44px;

            padding:
                0
                16px;

            color:
                #65758f;

            border-bottom:
                1px solid
                #dfe7f0;

            font-size:
                8px;

            font-weight:
                800;

            text-align:
                left;

            letter-spacing:
                .55px;

            text-transform:
                uppercase;

            white-space:
                nowrap;

        }


        .payments-table td {

            padding:
                16px;

            color:
                #435169;

            border-bottom:
                1px solid
                #edf1f6;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .payments-table tbody tr:hover {

            background:
                #f9fbff;

        }


        .payments-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        .payment-id {

            color:
                #2563eb;

            font-size:
                9px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | ORDER LINK
        |--------------------------------------------------------------------------
        */

        .payment-order-link {

            color:
                #1d4ed8;

            font-size:
                9px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        .payment-order-link:hover {

            text-decoration:
                underline;

        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        .payment-customer {

            min-width:
                180px;

        }


        .payment-customer strong {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #112b55;

            font-size:
                10px;

            font-weight:
                800;

        }


        .payment-customer small {

            display:
                block;

            max-width:
                190px;

            overflow:
                hidden;

            color:
                #8897ac;

            font-size:
                8px;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | METHOD
        |--------------------------------------------------------------------------
        */

        .payment-method {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            color:
                #52647f;

            background:
                #f1f5f9;

            border:
                1px solid
                #e2e8f0;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                700;

        }


        /*
        |--------------------------------------------------------------------------
        | AMOUNT
        |--------------------------------------------------------------------------
        */

        .payment-amount {

            color:
                #15803d;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | REFERENCE
        |--------------------------------------------------------------------------
        */

        .payment-reference {

            display:
                inline-block;

            max-width:
                170px;

            overflow:
                hidden;

            color:
                #64748b;

            font-size:
                8px;

            font-weight:
                700;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        .payment-date {

            color:
                #7c8ca3;

            font-size:
                8px;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .payment-status {

            min-height:
                27px;

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
                8px;

            font-weight:
                800;

        }


        .payment-status::before {

            content:
                "";

            width:
                5px;

            height:
                5px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .payment-status.pending {

            color:
                #a16207;

            background:
                #fffbea;

        }


        .payment-status.paid {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .payment-status.failed {

            color:
                #b91c1c;

            background:
                #fff1f2;

        }


        .payment-status.refunded {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS SELECT
        |--------------------------------------------------------------------------
        */

        .payment-status-select {

            min-width:
                115px;

            height:
                34px;

            padding:
                0
                9px;

            outline:
                none;

            color:
                #334155;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ef;

            border-radius:
                9px;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .payment-status-select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .payments-empty {

            padding:
                75px
                20px;

            text-align:
                center;

        }


        .payments-empty-icon {

            width:
                62px;

            height:
                62px;

            margin:
                0
                auto
                15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                17px;

            font-size:
                28px;

        }


        .payments-empty h3 {

            margin:
                0
                0
                6px;

            color:
                #49617f;

            font-size:
                14px;

            font-weight:
                800;

        }


        .payments-empty p {

            max-width:
                430px;

            margin:
                0 auto;

            color:
                #94a3b8;

            font-size:
                10px;

            line-height:
                1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1250px) {

            .payments-stats {

                grid-template-columns:

                    repeat(
                        3,
                        1fr
                    );

            }


            .payments-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .payments-filter input {

                grid-column:
                    1 / -1;

            }

        }


        @media (max-width: 900px) {

            :root {

                --payments-sidebar-width:
                    0px;

            }


            .payments-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .payments-content {

                padding:
                    25px
                    20px
                    50px;

            }


            .payments-hero {

                min-height:
                    140px;

                padding:
                    28px;

            }


            .payments-hero h1 {

                font-size:
                    31px;

            }


            .payments-hero-icon {

                width:
                    67px;

                height:
                    67px;

                font-size:
                    28px;

            }

        }


        @media (max-width: 650px) {

            .payments-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .payments-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .payments-hero h1 {

                font-size:
                    27px;

            }


            .payments-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .payments-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .payments-stats {

                grid-template-columns:
                    1fr;

            }


            .payments-panel-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                padding:
                    20px
                    17px;

            }


            .payments-filter {

                grid-template-columns:
                    1fr;

            }


            .payments-filter input {

                grid-column:
                    auto;

            }


            .payment-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="payments-main">


        <div class="payments-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="payments-hero">


                <div class="payments-hero-text">

                    <h1>
                        Payments
                    </h1>

                    <p>
                        Monitor and manage all HochipoHub customer payment transactions.
                    </p>

                </div>


                <div class="payments-hero-icon">

                    💳

                </div>


            </section>


            <!-- =====================================================
                 MESSAGE
            ====================================================== -->

            <?php if ($message !== ''): ?>


                <div
                    class="
                        payments-alert
                        success
                    "
                >

                    <?= paymentEscape(
                        $message
                    ) ?>

                </div>


            <?php endif; ?>


            <?php if ($error !== ''): ?>


                <div
                    class="
                        payments-alert
                        error
                    "
                >

                    <?= paymentEscape(
                        $error
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="payments-stats">


                <!-- TOTAL -->

                <div class="payment-stat">

                    <span class="payment-stat-label">

                        Total Payments

                    </span>


                    <strong class="payment-stat-value">

                        <?= number_format(
                            $totalPayments
                        ) ?>

                    </strong>

                </div>


                <!-- PAID -->

                <div
                    class="
                        payment-stat
                        paid
                    "
                >

                    <span class="payment-stat-label">

                        Paid

                    </span>


                    <strong class="payment-stat-value">

                        <?= number_format(
                            $paidPayments
                        ) ?>

                    </strong>


                    <span class="payment-stat-money">

                        <?= paymentEscape(
                            paymentMoney(
                                $totalPaidAmount
                            )
                        ) ?>

                    </span>

                </div>


                <!-- PENDING -->

                <div
                    class="
                        payment-stat
                        pending
                    "
                >

                    <span class="payment-stat-label">

                        Pending

                    </span>


                    <strong class="payment-stat-value">

                        <?= number_format(
                            $pendingPayments
                        ) ?>

                    </strong>


                    <span class="payment-stat-money">

                        <?= paymentEscape(
                            paymentMoney(
                                $totalPendingAmount
                            )
                        ) ?>

                    </span>

                </div>


                <!-- FAILED -->

                <div
                    class="
                        payment-stat
                        failed
                    "
                >

                    <span class="payment-stat-label">

                        Failed

                    </span>


                    <strong class="payment-stat-value">

                        <?= number_format(
                            $failedPayments
                        ) ?>

                    </strong>

                </div>


                <!-- REFUNDED -->

                <div
                    class="
                        payment-stat
                        refunded
                    "
                >

                    <span class="payment-stat-label">

                        Refunded

                    </span>


                    <strong class="payment-stat-value">

                        <?= number_format(
                            $refundedPayments
                        ) ?>

                    </strong>


                    <span class="payment-stat-money">

                        <?= paymentEscape(
                            paymentMoney(
                                $totalRefundedAmount
                            )
                        ) ?>

                    </span>

                </div>


            </section>


            <!-- =====================================================
                 PAYMENT PANEL
            ====================================================== -->

            <section class="payments-panel">


                <!-- =================================================
                     PANEL HEADER
                ================================================== -->

                <div class="payments-panel-header">


                    <div class="payments-panel-title">


                        <div class="payments-panel-icon">

                            💵

                        </div>


                        <div>

                            <h2>
                                Payment Transactions
                            </h2>

                            <p>
                                Search, filter and manage customer payment records.
                            </p>

                        </div>


                    </div>


                    <span class="payments-count">

                        <?= number_format(
                            count(
                                $payments
                            )
                        ) ?>

                        transactions

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="payments-filter-wrapper">


                    <form
                        method="GET"
                        action="payments.php"
                        class="payments-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= paymentEscape(
                                $search
                            ) ?>"
                            placeholder="Search payment, order, customer or reference..."
                            autocomplete="off"
                        >


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter payment status"
                        >

                            <option value="">

                                All Status

                            </option>


                            <?php foreach (
                                [
                                    'Pending',
                                    'Paid',
                                    'Failed',
                                    'Refunded'
                                ]
                                as $status
                            ): ?>


                                <option
                                    value="<?= paymentEscape(
                                        $status
                                    ) ?>"
                                    <?= $statusFilter === $status
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= paymentEscape(
                                        $status
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- METHOD -->

                        <select
                            name="method"
                            aria-label="Filter payment method"
                        >

                            <option value="">

                                All Methods

                            </option>


                            <?php foreach (
                                $paymentMethods
                                as $method
                            ): ?>


                                <option
                                    value="<?= paymentEscape(
                                        $method
                                    ) ?>"
                                    <?= $methodFilter === $method
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= paymentEscape(
                                        $method
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- SEARCH BUTTON -->

                        <button
                            type="submit"
                            class="
                                payment-btn
                                payment-btn-primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="payments.php"
                            class="
                                payment-btn
                                payment-btn-secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     EMPTY
                ================================================== -->

                <?php if (
                    empty(
                        $payments
                    )
                ): ?>


                    <div class="payments-empty">


                        <div class="payments-empty-icon">

                            🧾

                        </div>


                        <h3>

                            No payment records found

                        </h3>


                        <p>

                            Payment transactions will appear here when customers make payments for their HochipoHub orders.

                        </p>


                    </div>


                <?php else: ?>


                    <!-- =================================================
                         TABLE
                    ================================================== -->

                    <div class="payments-table-wrapper">


                        <table class="payments-table">


                            <thead>

                                <tr>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Order
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Method
                                    </th>

                                    <th>
                                        Amount
                                    </th>

                                    <th>
                                        Reference
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Update
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $payments
                                    as $payment
                                ): ?>


                                    <?php

                                    $paymentId =
                                        (int)
                                        $payment[
                                            'payment_id'
                                        ];


                                    $paymentStatus =
                                        $payment[
                                            'payment_status'
                                        ]
                                        ?? 'Pending';


                                    $statusClass =
                                        paymentStatusClass(
                                            $paymentStatus
                                        );

                                    ?>


                                    <tr>


                                        <!-- PAYMENT -->

                                        <td>

                                            <span class="payment-id">

                                                #<?= $paymentId ?>

                                            </span>

                                        </td>


                                        <!-- ORDER -->

                                        <td>

                                            <a
                                                href="orders.php?view=<?= (int)
                                                    $payment[
                                                        'order_id'
                                                    ] ?>"
                                                class="payment-order-link"
                                            >

                                                #<?= (int)
                                                    $payment[
                                                        'order_id'
                                                    ] ?>

                                            </a>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>


                                            <div class="payment-customer">

                                                <strong>

                                                    <?= paymentEscape(
                                                        $payment[
                                                            'customer_name'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <small>

                                                    <?= paymentEscape(
                                                        $payment[
                                                            'customer_email'
                                                        ]
                                                    ) ?>

                                                </small>

                                            </div>


                                        </td>


                                        <!-- METHOD -->

                                        <td>

                                            <span class="payment-method">

                                                <?= paymentEscape(
                                                    $payment[
                                                        'payment_method'
                                                    ]
                                                    ?: '-'
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- AMOUNT -->

                                        <td>

                                            <span class="payment-amount">

                                                <?= paymentEscape(
                                                    paymentMoney(
                                                        $payment[
                                                            'amount'
                                                        ]
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- REFERENCE -->

                                        <td>


                                            <?php if (
                                                !empty(
                                                    $payment[
                                                        'transaction_reference'
                                                    ]
                                                )
                                            ): ?>


                                                <span
                                                    class="payment-reference"
                                                    title="<?= paymentEscape(
                                                        $payment[
                                                            'transaction_reference'
                                                        ]
                                                    ) ?>"
                                                >

                                                    <?= paymentEscape(
                                                        $payment[
                                                            'transaction_reference'
                                                        ]
                                                    ) ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="payment-reference">

                                                    -

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <span class="payment-date">

                                                <?= paymentEscape(
                                                    paymentDate(
                                                        $payment[
                                                            'payment_date'
                                                        ]
                                                        ?? null
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="
                                                    payment-status
                                                    <?= paymentEscape(
                                                        $statusClass
                                                    ) ?>
                                                "
                                            >

                                                <?= paymentEscape(
                                                    $paymentStatus
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- UPDATE -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="payments.php"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= paymentEscape(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="payment_id"
                                                    value="<?= $paymentId ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="update_payment"
                                                    value="1"
                                                >


                                                <select
                                                    name="payment_status"
                                                    class="payment-status-select"
                                                    onchange="
                                                        if (
                                                            confirm(
                                                                'Change payment status to ' +
                                                                this.value +
                                                                '?'
                                                            )
                                                        ) {
                                                            this.form.submit();
                                                        } else {
                                                            window.location.reload();
                                                        }
                                                    "
                                                >


                                                    <?php foreach (
                                                        [
                                                            'Pending',
                                                            'Paid',
                                                            'Failed',
                                                            'Refunded'
                                                        ]
                                                        as $status
                                                    ): ?>


                                                        <option
                                                            value="<?= paymentEscape(
                                                                $status
                                                            ) ?>"
                                                            <?= $paymentStatus ===
                                                                $status
                                                                    ? 'selected'
                                                                    : '' ?>
                                                        >

                                                            <?= paymentEscape(
                                                                $status
                                                            ) ?>

                                                        </option>


                                                    <?php endforeach; ?>


                                                </select>


                                            </form>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php endif; ?>


            </section>


        </div>


    </main>


</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH SYNC
    |--------------------------------------------------------------------------
    */

    function syncPaymentsSidebar() {

        const main =
            document.querySelector(
                '.payments-main'
            );


        if (!main) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            document.documentElement
                .style
                .setProperty(
                    '--payments-sidebar-width',
                    '0px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        if (!sidebar) {

            document.documentElement
                .style
                .setProperty(
                    '--payments-sidebar-width',
                    '260px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | REAL WIDTH
        |--------------------------------------------------------------------------
        */

        const rect =
            sidebar
                .getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement
                .style
                .setProperty(
                    '--payments-sidebar-width',
                    rect.right + 'px'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncPaymentsSidebar();


            setTimeout(
                syncPaymentsSidebar,
                100
            );


            setTimeout(
                syncPaymentsSidebar,
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncPaymentsSidebar
    );

</script>


</body>

</html>