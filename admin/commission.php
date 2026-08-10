<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . site_url('index.php?login=required'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET CURRENT ADMIN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        role,
        status
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

if (!$admin) {
    session_destroy();

    header('Location: ' . site_url('index.php'));
    exit;
}

if ($admin['role'] !== 'admin') {
    header('Location: ' . site_url('dashboard.php'));
    exit;
}

if ($admin['status'] !== 'active') {
    session_destroy();

    header('Location: ' . site_url('index.php?account=inactive'));
    exit;
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$statusFilter = $_GET['status'] ?? 'all';

$allowedStatuses = [
    'all',
    'Pending',
    'Paid',
    'Completed',
    'Cancelled'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

/*
|--------------------------------------------------------------------------
| COMMISSION SUMMARY
|--------------------------------------------------------------------------
*/

$summary = [
    'total' => 0,
    'pending' => 0,
    'paid' => 0,
    'completed' => 0,
    'cancelled' => 0
];

/*
|--------------------------------------------------------------------------
| TOTAL COMMISSION
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        COALESCE(SUM(commission_amount), 0) AS total
    FROM commission
    WHERE status != 'Cancelled'
");

if ($result) {
    $row = $result->fetch_assoc();

    $summary['total'] = (float) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| STATUS COUNTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM commission
    GROUP BY status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $status = $row['status'];
        $count = (int) $row['total'];

        if ($status === 'Pending') {
            $summary['pending'] = $count;
        }

        if ($status === 'Paid') {
            $summary['paid'] = $count;
        }

        if ($status === 'Completed') {
            $summary['completed'] = $count;
        }

        if ($status === 'Cancelled') {
            $summary['cancelled'] = $count;
        }
    }
}

/*
|--------------------------------------------------------------------------
| COMMISSION LIST
|--------------------------------------------------------------------------
*/

$commissionRows = [];

$sql = "
    SELECT
        c.commission_id,
        c.order_id,
        c.vendor_id,
        c.commission_rate,
        c.commission_amount,
        c.status,
        c.created_at,

        v.business_name,

        u.name AS vendor_owner

    FROM commission c

    LEFT JOIN vendors v
        ON c.vendor_id = v.vendor_id

    LEFT JOIN users u
        ON v.user_id = u.user_id

    WHERE 1 = ?
";

$types = "i";
$params = [1];

/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter !== 'all') {

    $sql .= "
        AND c.status = ?
    ";

    $types .= "s";
    $params[] = $statusFilter;
}

/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            CAST(c.commission_id AS CHAR) LIKE ?
            OR CAST(c.order_id AS CHAR) LIKE ?
            OR v.business_name LIKE ?
            OR u.name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $types .= "ssss";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

$sql .= "
    ORDER BY c.created_at DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        $types,
        ...$params
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $commissionRows[] = $row;
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function commission_money($amount)
{
    return 'RM ' . number_format(
        (float) $amount,
        2
    );
}

function commission_date($date)
{
    if (!$date) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );
}

function commission_status_class($status)
{
    return 'commission-status-' .
        strtolower(
            str_replace(
                ' ',
                '-',
                $status
            )
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
        Commission |
        <?php echo htmlspecialchars(SITE_NAME); ?>
    </title>

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/admin.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >

    <style>

        .admin-page {
            min-height: 100vh;
            padding: 35px 0 80px;
            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(37, 99, 235, .16),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(14, 165, 233, .12),
                    transparent 25%
                ),
                linear-gradient(
                    145deg,
                    #020617,
                    #061a35 55%,
                    #020617
                );
            color: #f8fafc;
        }

        .admin-container {
            width: 90%;
            max-width: 1450px;
            margin: auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 950;
            letter-spacing: -1px;
        }

        .admin-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        .admin-header-badge {
            padding: 10px 15px;
            border: 1px solid rgba(56,189,248,.16);
            border-radius: 12px;
            background: rgba(14,165,233,.06);
            color: #7dd3fc;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .commission-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .commission-stat {
            padding: 20px;
            border: 1px solid rgba(148,163,184,.09);
            border-radius: 18px;
            background: rgba(15,23,42,.78);
        }

        .commission-stat-label {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .commission-stat-value {
            display: block;
            color: #f8fafc;
            font-size: 23px;
            font-weight: 950;
        }

        .commission-stat-value.blue {
            color: #7dd3fc;
        }

        .commission-stat-value.yellow {
            color: #fde047;
        }

        .commission-stat-value.green {
            color: #86efac;
        }

        .commission-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid rgba(148,163,184,.08);
            border-radius: 17px;
            background: rgba(15,23,42,.75);
        }

        .commission-search {
            display: flex;
            flex: 1;
            gap: 8px;
        }

        .commission-search input,
        .commission-filter select {
            min-height: 38px;
            box-sizing: border-box;
            border: 1px solid rgba(148,163,184,.12);
            border-radius: 10px;
            outline: none;
            background: rgba(2,6,23,.65);
            color: #cbd5e1;
            font-size: 10px;
        }

        .commission-search input {
            width: 100%;
            padding: 0 13px;
        }

        .commission-filter select {
            padding: 0 12px;
        }

        .commission-search input:focus,
        .commission-filter select:focus {
            border-color: rgba(56,189,248,.45);
        }

        .commission-btn {
            min-height: 38px;
            padding: 0 15px;
            border: 0;
            border-radius: 10px;
            background: #0284c7;
            color: white;
            font-size: 9px;
            font-weight: 900;
            cursor: pointer;
        }

        .commission-btn:hover {
            background: #0369a1;
        }

        .commission-reset {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0 13px;
            border: 1px solid rgba(148,163,184,.1);
            border-radius: 10px;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            text-decoration: none;
        }

        .commission-reset:hover {
            color: #7dd3fc;
            border-color: rgba(56,189,248,.25);
        }

        .commission-card {
            overflow: hidden;
            border: 1px solid rgba(148,163,184,.09);
            border-radius: 20px;
            background: rgba(15,23,42,.78);
        }

        .commission-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(148,163,184,.07);
        }

        .commission-card-header h2 {
            margin: 0;
            color: #e2e8f0;
            font-size: 14px;
            font-weight: 900;
        }

        .commission-card-header span {
            color: #475569;
            font-size: 9px;
        }

        .commission-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .commission-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .commission-table th {
            padding: 13px 16px;
            background: rgba(2,6,23,.28);
            color: #475569;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .8px;
            text-align: left;
            text-transform: uppercase;
        }

        .commission-table td {
            padding: 15px 16px;
            border-top: 1px solid rgba(148,163,184,.055);
            color: #94a3b8;
            font-size: 10px;
        }

        .commission-table tr:hover td {
            background: rgba(14,165,233,.025);
        }

        .commission-id {
            color: #7dd3fc;
            font-weight: 900;
        }

        .commission-vendor {
            color: #cbd5e1;
            font-weight: 850;
        }

        .commission-owner {
            display: block;
            margin-top: 3px;
            color: #475569;
            font-size: 8px;
        }

        .commission-amount {
            color: #86efac;
            font-weight: 950;
        }

        .commission-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border-radius: 99px;
            font-size: 8px;
            font-weight: 900;
        }

        .commission-status::before {
            content: "";
            width: 4px;
            height: 4px;
            border-radius: 50%;
        }

        .commission-status-pending {
            background: rgba(250,204,21,.08);
            color: #fde047;
        }

        .commission-status-pending::before {
            background: #facc15;
        }

        .commission-status-paid,
        .commission-status-completed {
            background: rgba(34,197,94,.08);
            color: #86efac;
        }

        .commission-status-paid::before,
        .commission-status-completed::before {
            background: #22c55e;
        }

        .commission-status-cancelled {
            background: rgba(239,68,68,.08);
            color: #fca5a5;
        }

        .commission-status-cancelled::before {
            background: #ef4444;
        }

        .commission-empty {
            padding: 55px 20px;
            text-align: center;
        }

        .commission-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            margin: 0 auto 12px;
            border-radius: 16px;
            background: rgba(14,165,233,.08);
            color: #38bdf8;
            font-size: 20px;
            font-weight: 950;
        }

        .commission-empty h3 {
            margin: 0;
            color: #cbd5e1;
            font-size: 13px;
        }

        .commission-empty p {
            margin: 6px auto 0;
            max-width: 350px;
            color: #475569;
            font-size: 9px;
        }

        @media (max-width: 900px) {

            .commission-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .commission-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .commission-search {
                width: 100%;
            }
        }

        @media (max-width: 550px) {

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .commission-summary {
                grid-template-columns: 1fr;
            }

            .commission-search {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>

<main class="admin-page">

    <div class="admin-container">

        <div class="admin-header">

            <div>

                <h1>Commission</h1>

                <p>
                    Monitor commission generated from HochipoHub vendor transactions.
                </p>

            </div>

            <div class="admin-header-badge">
                Admin Control
            </div>

        </div>


        <!-- SUMMARY -->

        <section class="commission-summary">

            <div class="commission-stat">

                <span class="commission-stat-label">
                    Total Commission
                </span>

                <strong class="commission-stat-value blue">
                    <?php
                    echo commission_money(
                        $summary['total']
                    );
                    ?>
                </strong>

            </div>


            <div class="commission-stat">

                <span class="commission-stat-label">
                    Pending
                </span>

                <strong class="commission-stat-value yellow">
                    <?php
                    echo number_format(
                        $summary['pending']
                    );
                    ?>
                </strong>

            </div>


            <div class="commission-stat">

                <span class="commission-stat-label">
                    Paid
                </span>

                <strong class="commission-stat-value green">
                    <?php
                    echo number_format(
                        $summary['paid']
                    );
                    ?>
                </strong>

            </div>


            <div class="commission-stat">

                <span class="commission-stat-label">
                    Cancelled
                </span>

                <strong class="commission-stat-value">
                    <?php
                    echo number_format(
                        $summary['cancelled']
                    );
                    ?>
                </strong>

            </div>

        </section>


        <!-- FILTER -->

        <form
            method="GET"
            class="commission-filter"
        >

            <div class="commission-search">

                <input
                    type="text"
                    name="search"
                    placeholder="Search commission, order, vendor..."
                    value="<?php
                    echo htmlspecialchars($search);
                    ?>"
                >

                <button
                    type="submit"
                    class="commission-btn"
                >
                    SEARCH
                </button>

            </div>


            <select
                name="status"
                onchange="this.form.submit()"
            >

                <option
                    value="all"
                    <?php
                    echo $statusFilter === 'all'
                        ? 'selected'
                        : '';
                    ?>
                >
                    All Status
                </option>

                <option
                    value="Pending"
                    <?php
                    echo $statusFilter === 'Pending'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Pending
                </option>

                <option
                    value="Paid"
                    <?php
                    echo $statusFilter === 'Paid'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Paid
                </option>

                <option
                    value="Completed"
                    <?php
                    echo $statusFilter === 'Completed'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Completed
                </option>

                <option
                    value="Cancelled"
                    <?php
                    echo $statusFilter === 'Cancelled'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Cancelled
                </option>

            </select>


            <a
                href="<?php
                echo site_url('admin/commission.php');
                ?>"
                class="commission-reset"
            >
                RESET
            </a>

        </form>


        <!-- TABLE -->

        <section class="commission-card">

            <div class="commission-card-header">

                <div>

                    <h2>
                        Commission Records
                    </h2>

                    <span>
                        Showing latest commission activity
                    </span>

                </div>

                <span>
                    <?php
                    echo number_format(
                        count($commissionRows)
                    );
                    ?>
                    records
                </span>

            </div>


            <?php if (empty($commissionRows)): ?>

                <div class="commission-empty">

                    <div class="commission-empty-icon">
                        %
                    </div>

                    <h3>
                        No commission records found
                    </h3>

                    <p>
                        Commission records will appear here when vendor transactions generate commission.
                    </p>

                </div>

            <?php else: ?>

                <div class="commission-table-wrapper">

                    <table class="commission-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Vendor
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
                            $commissionRows
                            as $row
                        ): ?>

                            <tr>

                                <td>

                                    <span class="commission-id">
                                        #
                                        <?php
                                        echo (int)
                                            $row[
                                                'commission_id'
                                            ];
                                        ?>
                                    </span>

                                </td>


                                <td>

                                    #
                                    <?php
                                    echo (int)
                                        $row['order_id'];
                                    ?>

                                </td>


                                <td>

                                    <span class="commission-vendor">

                                        <?php
                                        echo htmlspecialchars(
                                            $row[
                                                'business_name'
                                            ]
                                            ?: 'Unknown Vendor'
                                        );
                                        ?>

                                    </span>

                                    <?php if (
                                        !empty(
                                            $row[
                                                'vendor_owner'
                                            ]
                                        )
                                    ): ?>

                                        <span class="commission-owner">

                                            <?php
                                            echo htmlspecialchars(
                                                $row[
                                                    'vendor_owner'
                                                ]
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php
                                    echo number_format(
                                        (float)
                                        $row[
                                            'commission_rate'
                                        ],
                                        2
                                    );
                                    ?>
                                    %

                                </td>


                                <td>

                                    <span class="commission-amount">

                                        <?php
                                        echo commission_money(
                                            $row[
                                                'commission_amount'
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="
                                            commission-status
                                            <?php
                                            echo commission_status_class(
                                                $row['status']
                                            );
                                            ?>
                                        "
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $row['status']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo commission_date(
                                        $row['created_at']
                                    );
                                    ?>

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


<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

</body>

</html>