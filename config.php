<?php

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$db = getDB();

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| CHECK USER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| ONLY VENDOR
|--------------------------------------------------------------------------
*/

if ($user['role'] !== 'vendor') {

    header("Location: dashboard.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        vendor_id,
        business_name,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$vendor = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$vendor) {

    header("Location: dashboard.php");
    exit;

}


$vendor_id =
    (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| COMMISSION SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        COUNT(*) AS total_records,

        COALESCE(
            SUM(commission_amount),
            0
        ) AS total_commission,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Paid'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_commission,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS pending_commission

    FROM commission

    WHERE vendor_id = ?
");

$stmt->execute([$vendor_id]);

$summary =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| COMMISSION RECORDS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        c.commission_id,
        c.order_id,
        c.vendor_order_id,
        c.commission_rate,
        c.commission_amount,
        c.status,
        c.created_at,

        vo.subtotal,
        vo.vendor_status,

        o.order_date,
        o.order_status

    FROM commission c

    INNER JOIN orders o
        ON c.order_id = o.order_id

    LEFT JOIN vendor_orders vo
        ON c.vendor_order_id =
           vo.vendor_order_id

    WHERE c.vendor_id = ?

    ORDER BY c.created_at DESC
");

$stmt->execute([$vendor_id]);

$commissions =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


$pageTitle =
    "Commission - " .
    $vendor['business_name'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/vendor_sidebar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    VENDOR CENTER
                </span>

                <h1>
                    Commission
                </h1>

                <p>
                    Track commission generated from your orders.
                </p>

            </div>

        </section>


        <?php if (
            $vendor['approval_status'] !== 'Approved'
        ): ?>

            <div class="alert alert-warning">

                Your vendor account is currently

                <strong>
                    <?= htmlspecialchars(
                        $vendor['approval_status']
                    ) ?>
                </strong>.

                Commission information may be limited until
                your vendor account is approved.

            </div>

        <?php endif; ?>


        <section class="stats-grid">


            <div class="stat-card">

                <span class="stat-label">
                    Total Commission
                </span>

                <strong class="stat-value">

                    RM
                    <?= number_format(
                        (float) $summary['total_commission'],
                        2
                    ) ?>

                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Paid Commission
                </span>

                <strong class="stat-value">

                    RM
                    <?= number_format(
                        (float) $summary['paid_commission'],
                        2
                    ) ?>

                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Pending Commission
                </span>

                <strong class="stat-value">

                    RM
                    <?= number_format(
                        (float) $summary['pending_commission'],
                        2
                    ) ?>

                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Commission Records
                </span>

                <strong class="stat-value">

                    <?= (int) $summary['total_records'] ?>

                </strong>

            </div>


        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        TRANSACTIONS
                    </span>

                    <h2>
                        Commission History
                    </h2>

                </div>

            </div>


            <?php if (empty($commissions)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        💰
                    </div>

                    <h3>
                        No commission yet
                    </h3>

                    <p>
                        Commission records will appear here
                        when your products generate orders.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table class="dashboard-table">

                        <thead>

                            <tr>

                                <th>
                                    Commission ID
                                </th>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Vendor Order
                                </th>

                                <th>
                                    Order Amount
                                </th>

                                <th>
                                    Rate
                                </th>

                                <th>
                                    Commission
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $commissions
                                as $commission
                            ): ?>

                                <tr>

                                    <td>

                                        #
                                        <?= (int)
                                            $commission[
                                                'commission_id'
                                            ] ?>

                                    </td>


                                    <td>

                                        #
                                        <?= (int)
                                            $commission[
                                                'order_id'
                                            ] ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $commission[
                                                    'vendor_order_id'
                                                ]
                                            )
                                        ): ?>

                                            #

                                            <?= (int)
                                                $commission[
                                                    'vendor_order_id'
                                                ] ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $commission[
                                                'subtotal'
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            (float)
                                            $commission[
                                                'commission_rate'
                                            ],
                                            2
                                        ) ?>%

                                    </td>


                                    <td>

                                        <strong>

                                            RM
                                            <?= number_format(
                                                (float)
                                                $commission[
                                                    'commission_amount'
                                                ],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?php if (
                                            $commission['status']
                                            === 'Paid'
                                        ): ?>

                                            <span class="status-badge status-success">
                                                Paid
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge status-warning">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $commission[
                                                        'created_at'
                                                    ]
                                                )
                                            )
                                        ) ?>

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


<?php require_once __DIR__ . '/includes/footer.php'; ?>