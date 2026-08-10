<?php
session_start();

require_once "../config.php";
require_once "../database/db.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_payment'])
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

        $error = "Invalid payment ID.";

    } elseif (!in_array($new_status, $allowed_status, true)) {

        $error = "Invalid payment status.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | UPDATE PAYMENT
        |--------------------------------------------------------------------------
        */

        if ($new_status === 'Paid') {

            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = ?,
                    payment_date = COALESCE(payment_date, NOW())
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
                "si",
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
                        "i",
                        $payment_id
                    );

                    $get_order->execute();

                    $result = $get_order->get_result();

                    $payment_row = $result->fetch_assoc();

                    if ($payment_row) {
                        $order_id = (int) $payment_row['order_id'];
                    }

                    $get_order->close();
                }


                /*
                |--------------------------------------------------------------------------
                | ADMIN LOG
                |--------------------------------------------------------------------------
                */

                $action = "Updated payment #".$payment_id.
                          " status to ".$new_status;

                $target_type = "payment";

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
                        "issi",
                        $admin_id,
                        $action,
                        $target_type,
                        $payment_id
                    );

                    $log->execute();

                    $log->close();
                }

                $message =
                    "Payment #".$payment_id.
                    " status updated successfully.";

            } else {

                $error = "Failed to update payment status.";
            }

            $stmt->close();

        } else {

            $error = "Database error.";
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
| PAYMENT STATISTICS
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

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function money($value)
{
    return "RM " . number_format(
        (float) $value,
        2
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Payments | HochipoHub Admin</title>

    <link rel="stylesheet"
          href="../css/admin.css">

</head>

<body>

<div class="admin-layout">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

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

            <a href="payments.php"
               class="active">
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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="admin-main">

        <header class="admin-header">

            <div>

                <h1>
                    Payments
                </h1>

                <p>
                    Monitor customer payment transactions.
                </p>

            </div>

        </header>


        <!-- =================================================
             ALERT
        ================================================== -->

        <?php if ($message): ?>

            <div class="admin-alert success">

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="admin-alert error">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="admin-stats">


            <div class="stat-card">

                <span>
                    Total Payments
                </span>

                <strong>
                    <?= $total_payments ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Paid
                </span>

                <strong>
                    <?= $paid_payments ?>
                </strong>

                <small>
                    <?= money($total_paid_amount) ?>
                </small>

            </div>


            <div class="stat-card">

                <span>
                    Pending
                </span>

                <strong>
                    <?= $pending_payments ?>
                </strong>

                <small>
                    <?= money($total_pending_amount) ?>
                </small>

            </div>


            <div class="stat-card">

                <span>
                    Failed
                </span>

                <strong>
                    <?= $failed_payments ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Refunded
                </span>

                <strong>
                    <?= $refunded_payments ?>
                </strong>

                <small>
                    <?= money($total_refunded_amount) ?>
                </small>

            </div>


        </section>


        <!-- =================================================
             PAYMENTS TABLE
        ================================================== -->

        <section class="admin-card">

            <div class="card-header">

                <div>

                    <h2>
                        Payment Transactions
                    </h2>

                    <p>
                        All payment records from customer orders.
                    </p>

                </div>

            </div>


            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            Payment ID
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
                            Transaction Reference
                        </th>

                        <th>
                            Payment Date
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($payments)): ?>

                        <tr>

                            <td colspan="9">

                                No payment records found.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($payments as $payment): ?>

                            <tr>


                                <!-- PAYMENT ID -->

                                <td>

                                    <strong>
                                        #<?= e(
                                            $payment['payment_id']
                                        ) ?>
                                    </strong>

                                </td>


                                <!-- ORDER -->

                                <td>

                                    <a href="orders.php?view=<?= e(
                                        $payment['order_id']
                                    ) ?>">

                                        #<?= e(
                                            $payment['order_id']
                                        ) ?>

                                    </a>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <strong>

                                        <?= e(
                                            $payment['customer_name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= e(
                                            $payment['customer_email']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- METHOD -->

                                <td>

                                    <?= e(
                                        $payment['payment_method']
                                            ?: '-'
                                    ) ?>

                                </td>


                                <!-- AMOUNT -->

                                <td>

                                    <strong>

                                        <?= money(
                                            $payment['amount']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- TRANSACTION REFERENCE -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $payment[
                                                'transaction_reference'
                                            ]
                                        )
                                    ): ?>

                                        <code>

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


                                <!-- PAYMENT DATE -->

                                <td>

                                    <?= e(
                                        $payment['payment_date']
                                            ?: '-'
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span class="status-badge">

                                        <?= e(
                                            $payment[
                                                'payment_status'
                                            ]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- UPDATE -->

                                <td>

                                    <form method="POST">

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

                                            foreach (
                                                $statuses
                                                as $status
                                            ):
                                            ?>

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


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>