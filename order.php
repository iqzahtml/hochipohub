<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CUSTOMER ORDERS
|--------------------------------------------------------------------------
| File:
| order.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

$db = getDB();

requireLogin();

$userId = (int) currentUserId();


/*
|--------------------------------------------------------------------------
| VERIFY CUSTOMER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([
    $userId
]);

$user =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    $_SESSION['error'] =
        'User account not found.';

    redirect(
        BASE_URL . 'index.php'
    );

}


if ($user['role'] !== 'customer') {

    $_SESSION['error'] =
        'Customer access required.';

    redirect(
        BASE_URL . 'dashboard.php'
    );

}


/*
|--------------------------------------------------------------------------
| GET CUSTOMER ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        o.order_id,
        o.order_date,
        o.total_amount,
        o.delivery_method,
        o.delivery_address,
        o.tracking_number,
        o.order_status,
        o.completed_date,

        p.payment_status,
        p.payment_method

    FROM orders o

    LEFT JOIN payments p
        ON o.order_id = p.order_id

    WHERE o.customer_id = ?

    ORDER BY o.order_date DESC
");

$stmt->execute([
    $userId
]);

$orders =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| COUNT
|--------------------------------------------------------------------------
*/

$totalOrders =
    count($orders);

$completedOrders = 0;
$pendingOrders = 0;
$cancelledOrders = 0;

foreach ($orders as $order) {

    if ($order['order_status'] === 'Completed') {

        $completedOrders++;

    }

    if (
        $order['order_status'] === 'Pending' ||
        $order['order_status'] === 'Processing'
    ) {

        $pendingOrders++;

    }

    if (
        $order['order_status'] === 'Cancelled'
    ) {

        $cancelledOrders++;

    }

}


$pageTitle = 'My Orders';
$extraCSS = [
    'order.css'
];


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/customer_sidebar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    CUSTOMER CENTER
                </span>

                <h1>
                    My Orders
                </h1>

                <p>
                    View and track all your HochipoHub orders.
                </p>

            </div>

        </section>



        <!-- =====================================================
             FLASH
        ====================================================== -->

        <?php if (!empty($_SESSION['success'])): ?>

            <div class="alert alert-success">

                <?= e($_SESSION['success']) ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>


        <?php if (!empty($_SESSION['error'])): ?>

            <div class="alert alert-danger">

                <?= e($_SESSION['error']) ?>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>



        <!-- =====================================================
             STATS
        ====================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <span class="stat-label">
                    Total Orders
                </span>

                <strong class="stat-value">
                    <?= $totalOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    In Progress
                </span>

                <strong class="stat-value">
                    <?= $pendingOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Completed
                </span>

                <strong class="stat-value">
                    <?= $completedOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Cancelled
                </span>

                <strong class="stat-value">
                    <?= $cancelledOrders ?>
                </strong>

            </div>


        </section>



        <!-- =====================================================
             ORDER LIST
        ====================================================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        ORDER HISTORY
                    </span>

                    <h2>
                        Your Orders
                    </h2>

                </div>

            </div>


            <?php if (empty($orders)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>
                        No orders yet
                    </h3>

                    <p>
                        You haven't placed any orders.
                        Start shopping and your orders
                        will appear here.
                    </p>


                    <a
                        href="<?= e(BASE_URL) ?>catalog.php"
                        class="btn btn-primary"
                    >
                        Start Shopping
                    </a>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table class="dashboard-table">

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Delivery
                                </th>

                                <th>
                                    Payment
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

                            <?php foreach (
                                $orders
                                as $order
                            ): ?>


                                <tr>


                                    <!-- ORDER -->

                                    <td>

                                        <strong>

                                            #
                                            <?= (int)
                                                $order[
                                                    'order_id'
                                                ] ?>

                                        </strong>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?= e(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $order[
                                                        'order_date'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                        <br>

                                        <small>

                                            <?= e(
                                                date(
                                                    'h:i A',
                                                    strtotime(
                                                        $order[
                                                            'order_date'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- TOTAL -->

                                    <td>

                                        <strong>

                                            RM
                                            <?= number_format(
                                                (float)
                                                $order[
                                                    'total_amount'
                                                ],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- DELIVERY -->

                                    <td>

                                        <?= e(
                                            $order[
                                                'delivery_method'
                                            ] ?? '—'
                                        ) ?>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td>

                                        <?php

                                        $paymentStatus =
                                            $order[
                                                'payment_status'
                                            ] ?? 'Pending';

                                        ?>


                                        <span
                                            class="
                                                status-badge
                                                status-<?= e(
                                                    strtolower(
                                                        $paymentStatus
                                                    )
                                                )
                                                === 'paid'
                                                    ? 'success'
                                                    : 'warning'
                                                ?>
                                            "
                                        >

                                            <?= e(
                                                $paymentStatus
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ORDER STATUS -->

                                    <td>

                                        <?php

                                        $status =
                                            $order[
                                                'order_status'
                                            ];

                                        $statusClass =
                                            'default';

                                        if (
                                            $status ===
                                            'Completed'
                                        ) {

                                            $statusClass =
                                                'success';

                                        } elseif (
                                            $status ===
                                            'Pending' ||
                                            $status ===
                                            'Processing'
                                        ) {

                                            $statusClass =
                                                'warning';

                                        } elseif (
                                            $status ===
                                            'Cancelled'
                                        ) {

                                            $statusClass =
                                                'danger';

                                        }

                                        ?>


                                        <span
                                            class="
                                                status-badge
                                                status-<?= e(
                                                    $statusClass
                                                ) ?>
                                            "
                                        >

                                            <?= e($status) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <a
                                            href="<?= e(BASE_URL) ?>order_details.php?id=<?= (int) $order['order_id'] ?>"
                                            class="btn btn-secondary"
                                        >
                                            View
                                        </a>

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

require_once __DIR__ . '/includes/footer.php';

?>