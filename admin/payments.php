<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$allowedPaymentStatuses = [
    'Pending',
    'Paid',
    'Failed',
    'Cancelled',
    'Refunded'
];

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_payment_status'])
) {

    $paymentId = (int) (
        $_POST['payment_id'] ?? 0
    );

    $newStatus = trim(
        $_POST['payment_status'] ?? ''
    );

    if ($paymentId <= 0) {

        $errorMessage =
            'Invalid payment selected.';

    } elseif (
        !in_array(
            $newStatus,
            $allowedPaymentStatuses,
            true
        )
    ) {

        $errorMessage =
            'Invalid payment status.';

    } else {

        try {

            $stmt = $db->prepare("
                UPDATE payments
                SET
                    payment_status = :status
                WHERE payment_id = :payment_id
                LIMIT 1
            ");

            $stmt->execute([
                ':status' => $newStatus,
                ':payment_id' => $paymentId
            ]);

            if ($stmt->rowCount() > 0) {

                $successMessage =
                    'Payment status updated successfully.';

            } else {

                $successMessage =
                    'Payment status saved.';
            }

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to update payment status.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| PAYMENT SUMMARY
|--------------------------------------------------------------------------
*/

$totalPayments = 0;
$paidPayments = 0;
$pendingPayments = 0;
$failedPayments = 0;
$totalPaidAmount = 0;

try {

    $summaryStmt = $db->query("
        SELECT

            COUNT(*) AS total_payments,

            COALESCE(
                SUM(
                    CASE
                        WHEN payment_status = 'Paid'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS paid_payments,

            COALESCE(
                SUM(
                    CASE
                        WHEN payment_status = 'Pending'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS pending_payments,

            COALESCE(
                SUM(
                    CASE
                        WHEN payment_status = 'Failed'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS failed_payments,

            COALESCE(
                SUM(
                    CASE
                        WHEN payment_status = 'Paid'
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS total_paid_amount

        FROM payments
    ");

    $summary =
        $summaryStmt->fetch();

    if ($summary) {

        $totalPayments =
            (int) $summary['total_payments'];

        $paidPayments =
            (int) $summary['paid_payments'];

        $pendingPayments =
            (int) $summary['pending_payments'];

        $failedPayments =
            (int) $summary['failed_payments'];

        $totalPaidAmount =
            (float) $summary['total_paid_amount'];
    }

} catch (Throwable $e) {

    if (APP_DEBUG) {
        $errorMessage =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| GET PAYMENTS
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

            o.customer_id,
            o.order_date,
            o.order_status,

            u.name AS customer_name,
            u.email AS customer_email

        FROM payments p

        INNER JOIN orders o
            ON o.order_id = p.order_id

        INNER JOIN users u
            ON u.user_id = o.customer_id

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
            AND (
                CAST(
                    p.payment_id
                    AS CHAR
                ) LIKE :search

                OR CAST(
                    p.order_id
                    AS CHAR
                ) LIKE :search

                OR u.name LIKE :search

                OR u.email LIKE :search

                OR p.transaction_reference LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    if (
        $statusFilter !== 'all' &&
        in_array(
            $statusFilter,
            $allowedPaymentStatuses,
            true
        )
    ) {

        $sql .= "
            AND p.payment_status = :status
        ";

        $params[':status'] =
            $statusFilter;
    }

    $sql .= "
        ORDER BY p.payment_date DESC
    ";

    $stmt =
        $db->prepare($sql);

    $stmt->execute(
        $params
    );

    $payments =
        $stmt->fetchAll();

} catch (Throwable $e) {

    if (APP_DEBUG) {

        $errorMessage =
            $e->getMessage();
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
        Payment Management |
        <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .payments-page {
            min-height: 100vh;
            padding: 35px 4%;
            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(37,99,235,.12),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 15%,
                    rgba(14,165,233,.10),
                    transparent 25%
                ),
                #f8fbff;
        }

        .payments-container {
            max-width: 1450px;
            margin: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .payments-hero {
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
            padding: 32px;
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    #061a40,
                    #1d4ed8 55%,
                    #0284c7
                );
            color: white;
            box-shadow:
                0 25px 60px
                rgba(29,78,216,.22);
        }

        .payments-hero::before {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            top: -190px;
            right: -80px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.08);
        }

        .payments-hero::after {
            content: "";
            position: absolute;
            width: 190px;
            height: 190px;
            right: 220px;
            bottom: -125px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.05);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;
            color:
                rgba(255,255,255,.65);
            font-size: 10px;
            font-weight: 950;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .payments-hero h1 {
            margin: 0 0 8px;
            font-size: clamp(
                28px,
                5vw,
                44px
            );
            font-weight: 950;
        }

        .payments-hero p {
            max-width: 720px;
            margin: 0;
            color:
                rgba(255,255,255,.76);
            font-size: 12px;
            line-height: 1.7;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .summary-card {
            padding: 20px;
            border:
                1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .summary-label {
            margin-bottom: 9px;
            color: #64748b;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            font-size: 25px;
            font-weight: 950;
        }

        .summary-value.blue {
            color: #2563eb;
        }

        .summary-value.green {
            color: #16a34a;
        }

        .summary-value.orange {
            color: #d97706;
        }

        .summary-value.red {
            color: #dc2626;
        }

        .summary-note {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | MESSAGES
        |--------------------------------------------------------------------------
        */

        .message {
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 800;
        }

        .message.success {
            border:
                1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .message.error {
            border:
                1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .payments-panel {
            overflow: hidden;
            border:
                1px solid #dbeafe;
            border-radius: 22px;
            background: white;
            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .panel-header {
            padding: 20px 22px;
            border-bottom:
                1px solid #eff6ff;
        }

        .panel-header h2 {
            margin: 0 0 4px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .panel-header p {
            margin: 0;
            color: #94a3b8;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .filter-area {
            padding: 15px 22px;
            border-bottom:
                1px solid #eff6ff;
            background: #fbfdff;
        }

        .search-row {
            display: flex;
            gap: 8px;
            margin-bottom: 13px;
        }

        .search-form {
            display: flex;
            flex: 1;
            gap: 8px;
        }

        .search-input {
            flex: 1;
            min-width: 0;
            padding: 12px 14px;
            border:
                1px solid #dbeafe;
            border-radius: 12px;
            outline: none;
            background: white;
            color: #0f172a;
            font-size: 10px;
        }

        .search-input:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 4px
                rgba(37,99,235,.07);
        }

        .search-btn {
            padding: 12px 17px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            background: #2563eb;
            color: white;
            font-size: 9px;
            font-weight: 950;
        }

        .clear-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 15px;
            border:
                1px solid #dbeafe;
            border-radius: 12px;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-size: 9px;
            font-weight: 900;
        }

        .status-tabs {
            display: flex;
            gap: 7px;
            overflow-x: auto;
        }

        .status-tab {
            flex-shrink: 0;
            padding: 8px 12px;
            border:
                1px solid #dbeafe;
            border-radius: 999px;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
        }

        .status-tab:hover {
            border-color: #93c5fd;
            color: #2563eb;
        }

        .status-tab.active {
            border-color: #2563eb;
            background: #2563eb;
            color: white;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrap {
            overflow-x: auto;
        }

        .payments-table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
        }

        .payments-table th {
            padding: 13px 15px;
            border-bottom:
                1px solid #e2e8f0;
            background: #f8fbff;
            color: #64748b;
            font-size: 8px;
            font-weight: 950;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .payments-table td {
            padding: 14px 15px;
            border-bottom:
                1px solid #f1f5f9;
            color: #334155;
            font-size: 9px;
            vertical-align: top;
        }

        .payments-table tbody tr:hover {
            background: #f8fbff;
        }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT DATA
        |--------------------------------------------------------------------------
        */

        .payment-id {
            color: #2563eb;
            font-size: 10px;
            font-weight: 950;
        }

        .payment-date {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 8px;
        }

        .order-number {
            color: #334155;
            font-size: 10px;
            font-weight: 950;
        }

        .customer-name {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 10px;
            font-weight: 950;
        }

        .customer-email {
            color: #94a3b8;
            font-size: 8px;
        }

        .amount {
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 950;
        }

        .method {
            color: #334155;
            font-size: 9px;
            font-weight: 850;
        }

        .transaction {
            display: block;
            max-width: 190px;
            color: #64748b;
            font-size: 8px;
            line-height: 1.5;
            word-break: break-all;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status-badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 7px;
            font-weight: 950;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-refunded {
            background: #e0e7ff;
            color: #4338ca;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        .update-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-select {
            padding: 7px 8px;
            border:
                1px solid #dbeafe;
            border-radius: 9px;
            outline: none;
            background: white;
            color: #334155;
            font-size: 8px;
            font-weight: 800;
        }

        .save-btn {
            padding: 7px 9px;
            border: 0;
            border-radius: 9px;
            cursor: pointer;
            background: #eff6ff;
            color: #2563eb;
            font-size: 7px;
            font-weight: 950;
        }

        .save-btn:hover {
            background: #dbeafe;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-icon {
            margin-bottom: 12px;
            font-size: 38px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 12px;
            font-weight: 950;
        }

        .empty-state span {
            color: #94a3b8;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .payments-page {
                padding: 25px 15px;
            }

            .payments-hero {
                padding: 25px 20px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .search-row {
                flex-direction: column;
            }

            .search-form {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<main class="payments-page">

    <div class="payments-container">

        <!-- HERO -->

        <section class="payments-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    Payment Management
                </h1>

                <p>
                    Monitor incoming payments,
                    transaction references and
                    payment statuses across
                    HochipoHub.
                </p>

            </div>

        </section>


        <!-- MESSAGES -->

        <?php if (
            $successMessage !== ''
        ): ?>

            <div class="message success">
                ✓
                <?= e(
                    $successMessage
                ) ?>
            </div>

        <?php endif; ?>


        <?php if (
            $errorMessage !== ''
        ): ?>

            <div class="message error">
                ⚠
                <?= e(
                    $errorMessage
                ) ?>
            </div>

        <?php endif; ?>


        <!-- SUMMARY -->

        <section class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Total Payments
                </div>

                <div class="summary-value blue">
                    <?= number_format(
                        $totalPayments
                    ) ?>
                </div>

                <div class="summary-note">
                    All payment records
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Paid
                </div>

                <div class="summary-value green">
                    <?= number_format(
                        $paidPayments
                    ) ?>
                </div>

                <div class="summary-note">
                    Successful payments
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Pending
                </div>

                <div class="summary-value orange">
                    <?= number_format(
                        $pendingPayments
                    ) ?>
                </div>

                <div class="summary-note">
                    Awaiting confirmation
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Paid Amount
                </div>

                <div class="summary-value">
                    <?= formatPrice(
                        $totalPaidAmount
                    ) ?>
                </div>

                <div class="summary-note">
                    Confirmed payment value
                </div>

            </div>

        </section>


        <!-- PAYMENT PANEL -->

        <section class="payments-panel">

            <div class="panel-header">

                <h2>
                    Payment Records
                </h2>

                <p>
                    Review transactions and
                    update payment status.
                </p>

            </div>


            <!-- FILTER -->

            <div class="filter-area">

                <div class="search-row">

                    <form
                        method="GET"
                        class="search-form"
                    >

                        <input
                            type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search payment ID, order ID, customer or transaction..."
                            value="<?= e(
                                $search
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="status"
                            value="<?= e(
                                $statusFilter
                            ) ?>"
                        >

                        <button
                            type="submit"
                            class="search-btn"
                        >
                            SEARCH
                        </button>

                    </form>


                    <?php if (
                        $search !== ''
                    ): ?>

                        <a
                            href="<?= BASE_URL ?>admin/payments.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </div>


                <div class="status-tabs">

                    <?php

                    $paymentTabs = [
                        'all' => 'All Payments',
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Failed' => 'Failed',
                        'Cancelled' => 'Cancelled',
                        'Refunded' => 'Refunded'
                    ];

                    foreach (
                        $paymentTabs
                        as $tabKey => $tabLabel
                    ):

                        $query =
                            '?status=' .
                            urlencode(
                                $tabKey
                            );

                        if (
                            $search !== ''
                        ) {

                            $query .=
                                '&search=' .
                                urlencode(
                                    $search
                                );
                        }

                    ?>

                        <a
                            href="<?= $query ?>"
                            class="
                                status-tab
                                <?= $statusFilter === $tabKey
                                    ? 'active'
                                    : '' ?>
                            "
                        >
                            <?= e(
                                $tabLabel
                            ) ?>
                        </a>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- TABLE -->

            <?php if (
                empty($payments)
            ): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        💳
                    </div>

                    <strong>
                        No payment records found
                    </strong>

                    <span>
                        No payments match the
                        current search or filter.
                    </span>

                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table
                        class="payments-table"
                    >

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
                                    Amount
                                </th>

                                <th>
                                    Method
                                </th>

                                <th>
                                    Transaction
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

                                $paymentStatus =
                                    $payment[
                                        'payment_status'
                                    ] ?? 'Pending';

                                $statusClass =
                                    strtolower(
                                        $paymentStatus
                                    );

                                ?>

                                <tr>

                                    <!-- PAYMENT -->

                                    <td>

                                        <div
                                            class="payment-id"
                                        >
                                            #<?= e(
                                                $payment[
                                                    'payment_id'
                                                ]
                                            ) ?>
                                        </div>

                                        <div
                                            class="payment-date"
                                        >

                                            <?php if (
                                                !empty(
                                                    $payment[
                                                        'payment_date'
                                                    ]
                                                )
                                            ): ?>

                                                <?= e(
                                                    date(
                                                        'd M Y',
                                                        strtotime(
                                                            $payment[
                                                                'payment_date'
                                                            ]
                                                        )
                                                    )
                                                ) ?>

                                                <br>

                                                <?= e(
                                                    date(
                                                        'h:i A',
                                                        strtotime(
                                                            $payment[
                                                                'payment_date'
                                                            ]
                                                        )
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                No date

                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <!-- ORDER -->

                                    <td>

                                        <div
                                            class="order-number"
                                        >
                                            #<?= e(
                                                $payment[
                                                    'order_id'
                                                ]
                                            ) ?>
                                        </div>

                                        <div
                                            class="payment-date"
                                        >
                                            <?= e(
                                                ucfirst(
                                                    $payment[
                                                        'order_status'
                                                    ] ?? ''
                                                )
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <strong
                                            class="customer-name"
                                        >
                                            <?= e(
                                                $payment[
                                                    'customer_name'
                                                ]
                                            ) ?>
                                        </strong>

                                        <span
                                            class="customer-email"
                                        >
                                            <?= e(
                                                $payment[
                                                    'customer_email'
                                                ]
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- AMOUNT -->

                                    <td>

                                        <span
                                            class="amount"
                                        >
                                            <?= formatPrice(
                                                $payment[
                                                    'amount'
                                                ]
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- METHOD -->

                                    <td>

                                        <span
                                            class="method"
                                        >
                                            <?= e(
                                                $payment[
                                                    'payment_method'
                                                ]
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- TRANSACTION -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $payment[
                                                    'transaction_reference'
                                                ]
                                            )
                                        ): ?>

                                            <span
                                                class="transaction"
                                            >
                                                <?= e(
                                                    $payment[
                                                        'transaction_reference'
                                                    ]
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="transaction"
                                            >
                                                No reference
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                status-badge
                                                status-<?= e(
                                                    $statusClass
                                                ) ?>
                                            "
                                        >
                                            <?= e(
                                                $paymentStatus
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- UPDATE -->

                                    <td>

                                        <form
                                            method="POST"
                                            class="update-form"
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
                                                class="status-select"
                                            >

                                                <?php foreach (
                                                    $allowedPaymentStatuses
                                                    as $status
                                                ): ?>

                                                    <option
                                                        value="<?= e(
                                                            $status
                                                        ) ?>"
                                                        <?= $paymentStatus === $status
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        <?= e(
                                                            $status
                                                        ) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                            <button
                                                type="submit"
                                                name="update_payment_status"
                                                value="1"
                                                class="save-btn"
                                            >
                                                SAVE
                                            </button>

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

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>

</body>

</html>