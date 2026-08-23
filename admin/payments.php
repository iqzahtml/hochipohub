<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN PAYMENTS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
| database/db.php creates $db.
| This page uses $conn for compatibility.
|--------------------------------------------------------------------------
*/

$conn = $db;

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (
    !isset($_SESSION['role']) ||
    strtolower($_SESSION['role']) !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('money')) {

    function money($value)
    {
        return 'RM ' . number_format(
            (float) $value,
            2
        );
    }
}

if (!function_exists('payment_status_class')) {

    function payment_status_class($status)
    {
        return 'payment-status-' .
            strtolower(
                str_replace(
                    ' ',
                    '-',
                    $status
                )
            );
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

    $payment_id = isset($_POST['payment_id'])
        ? (int) $_POST['payment_id']
        : 0;

    $new_status = isset($_POST['payment_status'])
        ? trim($_POST['payment_status'])
        : '';

    $allowed_status = [
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ];

    if ($payment_id <= 0) {

        $error = 'Invalid payment ID.';

    } elseif (!in_array($new_status, $allowed_status, true)) {

        $error = 'Invalid payment status.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | UPDATE PAYMENT
        |--------------------------------------------------------------------------
        */

        if ($new_status === 'Paid') {

            $stmt = $conn->prepare("
                UPDATE payments
                SET
                    payment_status = ?,
                    payment_date = COALESCE(
                        payment_date,
                        NOW()
                    )
                WHERE payment_id = ?
            ");

        } else {

            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = ?
                WHERE payment_id = ?
            ");
        }

        if ($stmt) {

            $stmt->bind_param(
                'si',
                $new_status,
                $payment_id
            );

            if ($stmt->execute()) {

                /*
                |--------------------------------------------------------------------------
                | GET ORDER ID
                |--------------------------------------------------------------------------
                */

                $order_id = 0;

                $get_order = $conn->prepare("
                    SELECT order_id
                    FROM payments
                    WHERE payment_id = ?
                    LIMIT 1
                ");

                if ($get_order) {

                    $get_order->bind_param(
                        'i',
                        $payment_id
                    );

                    $get_order->execute();

                    $result = $get_order->get_result();

                    $row = $result->fetch_assoc();

                    if ($row) {
                        $order_id = (int) $row['order_id'];
                    }

                    $get_order->close();
                }

                /*
                |--------------------------------------------------------------------------
                | ADMIN LOG
                |--------------------------------------------------------------------------
                */

                $action =
                    'Updated payment #' .
                    $payment_id .
                    ' status to ' .
                    $new_status;

                $target_type = 'payment';

                $log = $conn->prepare("
                    INSERT INTO admin_logs
                    (
                        admin_id,
                        action,
                        target_type,
                        target_id
                    )
                    VALUES (?, ?, ?, ?)
                ");

                if ($log) {

                    $log->bind_param(
                        'issi',
                        $admin_id,
                        $action,
                        $target_type,
                        $payment_id
                    );

                    $log->execute();

                    $log->close();
                }

                $message =
                    'Payment #' .
                    $payment_id .
                    ' status updated successfully.';

            } else {

                $error =
                    'Failed to update payment status.';
            }

            $stmt->close();

        } else {

            $error =
                'Database error: ' .
                $conn->error;
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH PAYMENTS
|--------------------------------------------------------------------------
*/

$payments = [];

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

    ORDER BY p.payment_id DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $payments[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$total_payments = count($payments);

$pending_payments = 0;
$paid_payments = 0;
$failed_payments = 0;
$refunded_payments = 0;

$total_paid_amount = 0;
$total_pending_amount = 0;
$total_refunded_amount = 0;

foreach ($payments as $payment) {

    $status = $payment['payment_status'];

    $amount = (float) $payment['amount'];

    switch ($status) {

        case 'Pending':

            $pending_payments++;

            $total_pending_amount += $amount;

            break;

        case 'Paid':

            $paid_payments++;

            $total_paid_amount += $amount;

            break;

        case 'Failed':

            $failed_payments++;

            break;

        case 'Refunded':

            $refunded_payments++;

            $total_refunded_amount += $amount;

            break;
    }
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

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

    <style>

        .payment-page {
            min-height: 100vh;
            padding: 35px;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(37, 99, 235, .10),
                    transparent 30%
                ),
                #f8fafc;
        }

        .payment-container {
            max-width: 1500px;
            margin: 0 auto;
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            gap: 20px;
        }

        .payment-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 900;
            color: #0f172a;
        }

        .payment-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
        }

        .payment-alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 13px;
        }

        .payment-alert.success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .payment-alert.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .payment-stats {
            display: grid;
            grid-template-columns:
                repeat(5, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .payment-stat {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 22px;
            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, .05);
        }

        .payment-stat-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
        }

        .payment-stat-value {
            display: block;
            color: #0f172a;
            font-size: 26px;
            font-weight: 900;
        }

        .payment-stat-money {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .payment-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, .06);
        }

        .payment-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .payment-card-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
        }

        .payment-card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .payment-table-wrapper {
            overflow-x: auto;
        }

        .payment-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        .payment-table th {
            padding: 15px 18px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .payment-table td {
            padding: 17px 18px;
            border-top: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .payment-table tr:hover td {
            background: #f8fafc;
        }

        .payment-id {
            color: #2563eb;
            font-weight: 900;
        }

        .payment-customer strong {
            display: block;
            color: #0f172a;
            font-weight: 800;
        }

        .payment-customer small {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .payment-order-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 900;
        }

        .payment-order-link:hover {
            text-decoration: underline;
        }

        .payment-amount {
            color: #059669;
            font-weight: 900;
        }

        .payment-reference {
            padding: 5px 8px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .payment-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .payment-status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-status-refunded {
            background: #ede9fe;
            color: #6d28d9;
        }

        .payment-form select {
            min-width: 120px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #ffffff;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .payment-form select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, .10);
        }

        .payment-empty {
            padding: 70px 20px;
            text-align: center;
        }

        .payment-empty-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 26px;
            font-weight: 900;
        }

        .payment-empty h3 {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
        }

        .payment-empty p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 1100px) {

            .payment-stats {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }

        @media (max-width: 700px) {

            .payment-page {
                padding: 20px;
            }

            .payment-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .payment-stats {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 450px) {

            .payment-stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

    <aside class="admin-sidebar">

        <div class="admin-logo">

            <h2>
                Hochipo<span>Hub</span>
            </h2>

            <p>
                ADMIN PANEL
            </p>

        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="products.php">
                Products
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="vendors.php">
                Vendors
            </a>

            <a href="orders.php">
                Orders
            </a>

            <a
                href="payments.php"
                class="active"
            >
                Payments
            </a>

            <a href="commission.php">
                Commission
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="settings.php">
                Settings
            </a>

        </nav>

        <div class="admin-sidebar-bottom">

            <a href="../auth/logout.php">
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="admin-main">

        <div class="payment-page">

            <div class="payment-container">

                <header class="payment-header">

                    <div>

                        <h1>
                            Payments
                        </h1>

                        <p>
                            Monitor and manage all customer payment transactions.
                        </p>

                    </div>

                    <div class="admin-badge">
                        ADMIN CONTROL
                    </div>

                </header>


                <?php if ($message): ?>

                    <div class="payment-alert success">
                        <?= e($message) ?>
                    </div>

                <?php endif; ?>


                <?php if ($error): ?>

                    <div class="payment-alert error">
                        <?= e($error) ?>
                    </div>

                <?php endif; ?>


                <!-- STATISTICS -->

                <section class="payment-stats">

                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Total Payments
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($total_payments) ?>
                        </strong>

                    </div>


                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Paid
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($paid_payments) ?>
                        </strong>

                        <span class="payment-stat-money">
                            <?= money($total_paid_amount) ?>
                        </span>

                    </div>


                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Pending
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($pending_payments) ?>
                        </strong>

                        <span class="payment-stat-money">
                            <?= money($total_pending_amount) ?>
                        </span>

                    </div>


                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Failed
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($failed_payments) ?>
                        </strong>

                    </div>


                    <div class="payment-stat">

                        <span class="payment-stat-label">
                            Refunded
                        </span>

                        <strong class="payment-stat-value">
                            <?= number_format($refunded_payments) ?>
                        </strong>

                        <span class="payment-stat-money">
                            <?= money($total_refunded_amount) ?>
                        </span>

                    </div>

                </section>


                <!-- TABLE -->

                <section class="payment-card">

                    <div class="payment-card-header">

                        <div>

                            <h2>
                                Payment Transactions
                            </h2>

                            <p>
                                All payment records from customer orders.
                            </p>

                        </div>

                    </div>


                    <?php if (empty($payments)): ?>

                        <div class="payment-empty">

                            <div class="payment-empty-icon">
                                $
                            </div>

                            <h3>
                                No payment records
                            </h3>

                            <p>
                                Payment transactions will appear here when customers make payments.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="payment-table-wrapper">

                            <table class="payment-table">

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

                                <?php foreach ($payments as $payment): ?>

                                    <tr>

                                        <td>

                                            <span class="payment-id">

                                                #<?= e(
                                                    $payment['payment_id']
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                class="payment-order-link"
                                                href="orders.php?view=<?= e(
                                                    $payment['order_id']
                                                ) ?>"
                                            >

                                                #<?= e(
                                                    $payment['order_id']
                                                ) ?>

                                            </a>

                                        </td>


                                        <td>

                                            <div class="payment-customer">

                                                <strong>
                                                    <?= e(
                                                        $payment[
                                                            'customer_name'
                                                        ]
                                                    ) ?>
                                                </strong>

                                                <small>
                                                    <?= e(
                                                        $payment[
                                                            'customer_email'
                                                        ]
                                                    ) ?>
                                                </small>

                                            </div>

                                        </td>


                                        <td>

                                            <?= e(
                                                $payment[
                                                    'payment_method'
                                                ] ?: '-'
                                            ) ?>

                                        </td>


                                        <td>

                                            <span class="payment-amount">

                                                <?= money(
                                                    $payment['amount']
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php if (
                                                !empty(
                                                    $payment[
                                                        'transaction_reference'
                                                    ]
                                                )
                                            ): ?>

                                                <code class="payment-reference">

                                                    <?= e(
                                                        $payment[
                                                            'transaction_reference'
                                                        ]
                                                    ) ?>

                                                </code>

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                $payment['payment_date']
                                                    ?: '-'
                                            ) ?>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    payment-status
                                                    <?= e(
                                                        payment_status_class(
                                                            $payment[
                                                                'payment_status'
                                                            ]
                                                        )
                                                    )
                                                ?>"
                                            >

                                                <?= e(
                                                    $payment[
                                                        'payment_status'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <form
                                                method="POST"
                                                class="payment-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="payment_id"
                                                    value="<?= e(
                                                        $payment[
                                                            'payment_id'
                                                        ]
                                                    ) ?>"
                                                >

                                                <select
                                                    name="payment_status"
                                                    onchange="this.form.submit()"
                                                >

                                                    <?php
                                                    $statuses = [
                                                        'Pending',
                                                        'Paid',
                                                        'Failed',
                                                        'Refunded'
                                                    ];
                                                    ?>

                                                    <?php foreach (
                                                        $statuses
                                                        as $status
                                                    ): ?>

                                                        <option
                                                            value="<?= e(
                                                                $status
                                                            ) ?>"
                                                            <?= $payment[
                                                                'payment_status'
                                                            ] === $status
                                                                ? 'selected'
                                                                : '' ?>
                                                        >

                                                            <?= e(
                                                                $status
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                                <input
                                                    type="hidden"
                                                    name="update_payment"
                                                    value="1"
                                                >

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

        </div>

    </main>

</div>

</body>

</html>