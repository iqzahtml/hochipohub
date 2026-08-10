<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ORDER DETAILS
|--------------------------------------------------------------------------
| File:
| order_details.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

$db = getDB();

requireLogin();

$userId = (int) currentUserId();


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;


if ($orderId <= 0) {

    $_SESSION['error'] =
        'Invalid order.';

    redirect(
        BASE_URL . 'order.php'
    );

}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
|
| IMPORTANT:
| customer_id = current user
|
| This prevents one customer from viewing
| another customer's order.
|
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        o.order_id,
        o.customer_id,
        o.order_date,
        o.total_amount,
        o.delivery_method,
        o.delivery_address,
        o.tracking_number,
        o.order_status,
        o.completed_date,

        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone

    FROM orders o

    INNER JOIN users u
        ON o.customer_id = u.user_id

    WHERE o.order_id = ?
      AND o.customer_id = ?

    LIMIT 1
");

$stmt->execute([
    $orderId,
    $userId
]);

$order =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {

    $_SESSION['error'] =
        'Order not found or you do not have permission to view it.';

    redirect(
        BASE_URL . 'order.php'
    );

}


/*
|--------------------------------------------------------------------------
| GET ORDER DETAILS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        od.order_detail_id,
        od.product_id,
        od.quantity,
        od.unit_price,
        od.subtotal,

        p.product_name,
        p.image,

        v.vendor_id,
        v.business_name

    FROM order_details od

    INNER JOIN products p
        ON od.product_id = p.product_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    WHERE od.order_id = ?

    ORDER BY od.order_detail_id ASC
");

$stmt->execute([
    $orderId
]);

$items =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET VENDOR ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        vo.vendor_order_id,
        vo.vendor_id,
        vo.subtotal,
        vo.delivery_fee,
        vo.vendor_status,
        vo.tracking_number,
        vo.created_at,
        vo.completed_at,

        v.business_name

    FROM vendor_orders vo

    INNER JOIN vendors v
        ON vo.vendor_id = v.vendor_id

    WHERE vo.order_id = ?

    ORDER BY vo.vendor_order_id ASC
");

$stmt->execute([
    $orderId
]);

$vendorOrders =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET PAYMENT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        payment_id,
        payment_method,
        payment_status,
        payment_date,
        amount,
        transaction_reference

    FROM payments

    WHERE order_id = ?

    ORDER BY payment_id DESC

    LIMIT 1
");

$stmt->execute([
    $orderId
]);

$payment =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CALCULATE ITEM COUNT
|--------------------------------------------------------------------------
*/

$totalItems = 0;

foreach ($items as $item) {

    $totalItems +=
        (int) $item['quantity'];

}


$pageTitle =
    'Order #' .
    $orderId;


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
                    ORDER DETAILS
                </span>

                <h1>

                    Order
                    #<?= $orderId ?>

                </h1>

                <p>

                    Placed on

                    <?= e(
                        date(
                            'd M Y, h:i A',
                            strtotime(
                                $order[
                                    'order_date'
                                ]
                            )
                        )
                    ) ?>

                </p>

            </div>


            <div>

                <a
                    href="<?= e(BASE_URL) ?>order.php"
                    class="btn btn-secondary"
                >
                    ← Back to Orders
                </a>

            </div>

        </section>



        <!-- =====================================================
             ORDER STATUS
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        ORDER STATUS
                    </span>

                    <h2>
                        Current Status
                    </h2>

                </div>

            </div>


            <?php

            $status =
                $order['order_status'];

            $statusClass =
                'default';

            if (
                $status === 'Completed'
            ) {

                $statusClass =
                    'success';

            } elseif (
                $status === 'Pending' ||
                $status === 'Processing'
            ) {

                $statusClass =
                    'warning';

            } elseif (
                $status === 'Cancelled'
            ) {

                $statusClass =
                    'danger';

            }

            ?>


            <div
                class="alert alert-<?= e(
                    $statusClass
                ) ?>"
            >

                <strong>
                    <?= e($status) ?>
                </strong>


                <?php if (
                    $status === 'Pending'
                ): ?>

                    — Your order is waiting
                    to be processed.

                <?php elseif (
                    $status === 'Processing'
                ): ?>

                    — Your order is currently
                    being processed.

                <?php elseif (
                    $status === 'Completed'
                ): ?>

                    — Your order has been
                    completed.

                <?php elseif (
                    $status === 'Cancelled'
                ): ?>

                    — This order has been
                    cancelled.

                <?php endif; ?>

            </div>


        </section>



        <!-- =====================================================
             ORDER SUMMARY
        ====================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <span class="stat-label">
                    Total Items
                </span>

                <strong class="stat-value">
                    <?= $totalItems ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Order Total
                </span>

                <strong class="stat-value">

                    RM
                    <?= number_format(
                        (float)
                        $order[
                            'total_amount'
                        ],
                        2
                    ) ?>

                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Delivery
                </span>

                <strong
                    class="stat-value"
                    style="font-size:20px;"
                >

                    <?= e(
                        $order[
                            'delivery_method'
                        ] ?? '—'
                    ) ?>

                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Payment
                </span>

                <strong
                    class="stat-value"
                    style="font-size:20px;"
                >

                    <?= e(
                        $payment[
                            'payment_status'
                        ] ?? 'Pending'
                    ) ?>

                </strong>

            </div>


        </section>



        <!-- =====================================================
             PRODUCTS
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        PURCHASED ITEMS
                    </span>

                    <h2>
                        Order Items
                    </h2>

                </div>

            </div>


            <?php if (empty($items)): ?>

                <div class="empty-state">

                    <h3>
                        No order items found
                    </h3>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table class="dashboard-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Vendor
                                </th>

                                <th>
                                    Unit Price
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $items
                                as $item
                            ): ?>

                                <tr>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div
                                            style="
                                                display:flex;
                                                align-items:center;
                                                gap:12px;
                                            "
                                        >

                                            <?php if (
                                                !empty(
                                                    $item['image']
                                                )
                                            ): ?>

                                                <img
                                                    src="<?= e(BASE_URL) ?>uploads/products/<?= e(basename($item['image'])) ?>"
                                                    alt="<?= e($item['product_name']) ?>"
                                                    style="
                                                        width:60px;
                                                        height:60px;
                                                        object-fit:cover;
                                                        border-radius:10px;
                                                    "
                                                >

                                            <?php else: ?>

                                                <div
                                                    style="
                                                        width:60px;
                                                        height:60px;
                                                        display:flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        background:#eef2ff;
                                                        border-radius:10px;
                                                    "
                                                >
                                                    📦
                                                </div>

                                            <?php endif; ?>


                                            <div>

                                                <strong>

                                                    <?= e(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- VENDOR -->

                                    <td>

                                        <?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- UNIT PRICE -->

                                    <td>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $item[
                                                'unit_price'
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td>

                                        <?= (int)
                                            $item[
                                                'quantity'
                                            ] ?>

                                    </td>


                                    <!-- SUBTOTAL -->

                                    <td>

                                        <strong>

                                            RM
                                            <?= number_format(
                                                (float)
                                                $item[
                                                    'subtotal'
                                                ],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>


                        <tfoot>

                            <tr>

                                <td
                                    colspan="4"
                                    style="text-align:right;"
                                >

                                    <strong>
                                        Total
                                    </strong>

                                </td>

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

                            </tr>

                        </tfoot>


                    </table>

                </div>


            <?php endif; ?>


        </section>



        <!-- =====================================================
             VENDOR SUB ORDERS
        ====================================================== -->

        <?php if (!empty($vendorOrders)): ?>

            <section class="dashboard-section">


                <div class="section-heading">

                    <div>

                        <span class="small-label">
                            MULTI-VENDOR ORDER
                        </span>

                        <h2>
                            Vendor Orders
                        </h2>

                    </div>

                </div>


                <div class="table-wrapper">

                    <table class="dashboard-table">

                        <thead>

                            <tr>

                                <th>
                                    Vendor
                                </th>

                                <th>
                                    Subtotal
                                </th>

                                <th>
                                    Delivery Fee
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Tracking
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $vendorOrders
                                as $vendorOrder
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= e(
                                                $vendorOrder[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $vendorOrder[
                                                'subtotal'
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $vendorOrder[
                                                'delivery_fee'
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php

                                        $vendorStatus =
                                            $vendorOrder[
                                                'vendor_status'
                                            ];

                                        $vendorStatusClass =
                                            'default';

                                        if (
                                            $vendorStatus ===
                                            'Completed'
                                        ) {

                                            $vendorStatusClass =
                                                'success';

                                        } elseif (
                                            $vendorStatus ===
                                            'Pending' ||
                                            $vendorStatus ===
                                            'Processing' ||
                                            $vendorStatus ===
                                            'Ready' ||
                                            $vendorStatus ===
                                            'Shipped'
                                        ) {

                                            $vendorStatusClass =
                                                'warning';

                                        } elseif (
                                            $vendorStatus ===
                                            'Cancelled'
                                        ) {

                                            $vendorStatusClass =
                                                'danger';

                                        }

                                        ?>


                                        <span
                                            class="
                                                status-badge
                                                status-<?= e(
                                                    $vendorStatusClass
                                                )
                                                ?>
                                            "
                                        >

                                            <?= e(
                                                $vendorStatus
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $vendorOrder[
                                                    'tracking_number'
                                                ]
                                            )
                                        ): ?>

                                            <?= e(
                                                $vendorOrder[
                                                    'tracking_number'
                                                ]
                                            ) ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            </section>

        <?php endif; ?>



        <!-- =====================================================
             DELIVERY INFORMATION
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        DELIVERY
                    </span>

                    <h2>
                        Delivery Information
                    </h2>

                </div>

            </div>


            <div
                class="info-grid"
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(220px, 1fr)
                        );
                    gap:20px;
                "
            >


                <div class="info-card">

                    <span class="small-label">
                        METHOD
                    </span>

                    <h3>

                        <?= e(
                            $order[
                                'delivery_method'
                            ] ?? '—'
                        ) ?>

                    </h3>

                </div>


                <div class="info-card">

                    <span class="small-label">
                        TRACKING NUMBER
                    </span>

                    <h3>

                        <?php if (
                            !empty(
                                $order[
                                    'tracking_number'
                                ]
                            )
                        ): ?>

                            <?= e(
                                $order[
                                    'tracking_number'
                                ]
                            ) ?>

                        <?php else: ?>

                            Not available yet

                        <?php endif; ?>

                    </h3>

                </div>


                <?php if (
                    $order[
                        'delivery_method'
                    ] === 'Postage'
                ): ?>

                    <div class="info-card">

                        <span class="small-label">
                            DELIVERY ADDRESS
                        </span>

                        <p>

                            <?= nl2br(
                                e(
                                    $order[
                                        'delivery_address'
                                    ] ?? ''
                                )
                            ) ?>

                        </p>

                    </div>

                <?php endif; ?>


            </div>


        </section>



        <!-- =====================================================
             PAYMENT INFORMATION
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        PAYMENT
                    </span>

                    <h2>
                        Payment Information
                    </h2>

                </div>

            </div>


            <?php if ($payment): ?>


                <div
                    class="info-grid"
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fit,
                                minmax(220px, 1fr)
                            );
                        gap:20px;
                    "
                >


                    <div class="info-card">

                        <span class="small-label">
                            METHOD
                        </span>

                        <h3>

                            <?= e(
                                $payment[
                                    'payment_method'
                                ] ?? '—'
                            ) ?>

                        </h3>

                    </div>


                    <div class="info-card">

                        <span class="small-label">
                            STATUS
                        </span>

                        <h3>

                            <?= e(
                                $payment[
                                    'payment_status'
                                ] ?? 'Pending'
                            ) ?>

                        </h3>

                    </div>


                    <div class="info-card">

                        <span class="small-label">
                            AMOUNT
                        </span>

                        <h3>

                            RM
                            <?= number_format(
                                (float)
                                $payment[
                                    'amount'
                                ],
                                2
                            ) ?>

                        </h3>

                    </div>


                    <div class="info-card">

                        <span class="small-label">
                            TRANSACTION REFERENCE
                        </span>

                        <h3>

                            <?php if (
                                !empty(
                                    $payment[
                                        'transaction_reference'
                                    ]
                                )
                            ): ?>

                                <?= e(
                                    $payment[
                                        'transaction_reference'
                                    ]
                                ) ?>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </h3>

                    </div>


                </div>


            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        💳
                    </div>

                    <h3>
                        Payment information unavailable
                    </h3>

                    <p>
                        Payment information has not been
                        recorded for this order yet.
                    </p>

                </div>

            <?php endif; ?>


        </section>


    </div>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>