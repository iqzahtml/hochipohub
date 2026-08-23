<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN COMMISSION
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

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('commission_e')) {

    function commission_e($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('commission_money')) {

    function commission_money($amount)
    {
        return 'RM ' . number_format(
            (float) $amount,
            2
        );
    }
}

if (!function_exists('commission_date')) {

    function commission_date($date)
    {

        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }
}

if (!function_exists('commission_status_class')) {

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
    'Paid'
];

if (!in_array(
    $statusFilter,
    $allowedStatuses,
    true
)) {

    $statusFilter = 'all';
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$summary = [
    'total' => 0,
    'pending' => 0,
    'paid' => 0
];

/*
|--------------------------------------------------------------------------
| TOTAL COMMISSION
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        COALESCE(
            SUM(commission_amount),
            0
        ) AS total
    FROM commission
");

if ($result) {

    $row = $result->fetch_assoc();

    $summary['total'] =
        (float) (
            $row['total']
            ?? 0
        );
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

            $summary['pending'] =
                $count;
        }

        if ($status === 'Paid') {

            $summary['paid'] =
                $count;
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
        c.vendor_order_id,
        c.commission_rate,
        c.commission_amount,
        c.status,
        c.created_at,

        v.business_name,

        u.name AS vendor_owner,
        u.email AS vendor_email

    FROM commission c

    LEFT JOIN vendors v
        ON c.vendor_id = v.vendor_id

    LEFT JOIN users u
        ON v.user_id = u.user_id

    WHERE 1 = 1
";

$types = '';
$params = [];

/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter !== 'all') {

    $sql .= "
        AND c.status = ?
    ";

    $types .= 's';

    $params[] =
        $statusFilter;
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            CAST(c.commission_id AS CHAR)
                LIKE ?

            OR CAST(c.order_id AS CHAR)
                LIKE ?

            OR CAST(c.vendor_id AS CHAR)
                LIKE ?

            OR v.business_name
                LIKE ?

            OR u.name
                LIKE ?

            OR u.email
                LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $types .= 'ssssss';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

$sql .= "
    ORDER BY c.created_at DESC
    LIMIT 100
";

/*
|--------------------------------------------------------------------------
| PREPARE COMMISSION QUERY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    $stmt->execute();

    $result =
        $stmt->get_result();

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $commissionRows[] =
            $row;
    }

    $stmt->close();
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
        Commission | HochipoHub Admin
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

        .commission-page {
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

        .commission-container {
            max-width: 1500px;
            margin: 0 auto;
        }

        .commission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .commission-header h1 {
            margin: 0;
            color: #0f172a;
            font-size: 32px;
            font-weight: 900;
        }

        .commission-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .commission-admin-badge {
            padding: 10px 16px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 900;
        }

        .commission-summary {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .commission-stat {
            padding: 23px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, .05);
        }

        .commission-stat-label {
            display: block;
            margin-bottom: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .commission-stat-value {
            display: block;
            color: #0f172a;
            font-size: 28px;
            font-weight: 900;
        }

        .commission-stat-value.blue {
            color: #2563eb;
        }

        .commission-stat-value.yellow {
            color: #d97706;
        }

        .commission-stat-value.green {
            color: #059669;
        }

        .commission-filter {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow:
                0 6px 20px
                rgba(15, 23, 42, .04);
        }

        .commission-search {
            display: flex;
            flex: 1;
            gap: 8px;
        }

        .commission-search input,
        .commission-filter select {
            min-height: 42px;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
        }

        .commission-search input {
            width: 100%;
            padding: 0 13px;
        }

        .commission-filter select {
            padding: 0 12px;
            cursor: pointer;
        }

        .commission-search input:focus,
        .commission-filter select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, .10);
        }

        .commission-btn {
            min-height: 42px;
            padding: 0 18px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }

        .commission-btn:hover {
            background: #1d4ed8;
        }

        .commission-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }

        .commission-reset:hover {
            background: #f8fafc;
            color: #2563eb;
        }

        .commission-card {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, .06);
        }

        .commission-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .commission-card-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
        }

        .commission-card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .commission-record-count {
            padding: 7px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
        }

        .commission-table-wrapper {
            overflow-x: auto;
        }

        .commission-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .commission-table th {
            padding: 15px 18px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .commission-table td {
            padding: 17px 18px;
            border-top: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
        }

        .commission-table tr:hover td {
            background: #f8fafc;
        }

        .commission-id {
            color: #2563eb;
            font-weight: 900;
        }

        .commission-order {
            color: #475569;
            font-weight: 800;
        }

        .commission-vendor {
            display: block;
            color: #0f172a;
            font-weight: 900;
        }

        .commission-owner {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .commission-rate {
            font-weight: 800;
            color: #475569;
        }

        .commission-amount {
            color: #059669;
            font-weight: 900;
        }

        .commission-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .commission-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .commission-status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .commission-empty {
            padding: 70px 20px;
            text-align: center;
        }

        .commission-empty-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 27px;
            font-weight: 900;
        }

        .commission-empty h3 {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
        }

        .commission-empty p {
            max-width: 430px;
            margin: 7px auto 0;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 800px) {

            .commission-page {
                padding: 20px;
            }

            .commission-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .commission-summary {
                grid-template-columns: 1fr;
            }

            .commission-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .commission-search {
                width: 100%;
            }

        }

        @media (max-width: 500px) {

            .commission-search {
                flex-direction: column;
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

            <a href="payments.php">
                Payments
            </a>

            <a
                href="commission.php"
                class="active"
            >
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

        <div class="commission-page">

            <div class="commission-container">

                <header class="commission-header">

                    <div>

                        <h1>
                            Commission
                        </h1>

                        <p>
                            Monitor commission generated from vendor transactions.
                        </p>

                    </div>

                    <div class="commission-admin-badge">
                        ADMIN CONTROL
                    </div>

                </header>


                <!-- SUMMARY -->

                <section class="commission-summary">

                    <div class="commission-stat">

                        <span class="commission-stat-label">
                            Total Commission
                        </span>

                        <strong
                            class="
                                commission-stat-value
                                blue
                            "
                        >

                            <?= commission_money(
                                $summary['total']
                            ) ?>

                        </strong>

                    </div>


                    <div class="commission-stat">

                        <span class="commission-stat-label">
                            Pending
                        </span>

                        <strong
                            class="
                                commission-stat-value
                                yellow
                            "
                        >

                            <?= number_format(
                                $summary['pending']
                            ) ?>

                        </strong>

                    </div>


                    <div class="commission-stat">

                        <span class="commission-stat-label">
                            Paid
                        </span>

                        <strong
                            class="
                                commission-stat-value
                                green
                            "
                        >

                            <?= number_format(
                                $summary['paid']
                            ) ?>

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
                            value="<?= commission_e(
                                $search
                            ) ?>"
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
                            <?= $statusFilter === 'all'
                                ? 'selected'
                                : '' ?>
                        >
                            All Status
                        </option>

                        <option
                            value="Pending"
                            <?= $statusFilter === 'Pending'
                                ? 'selected'
                                : '' ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Paid"
                            <?= $statusFilter === 'Paid'
                                ? 'selected'
                                : '' ?>
                        >
                            Paid
                        </option>

                    </select>


                    <a
                        href="commission.php"
                        class="commission-reset"
                    >
                        RESET
                    </a>

                </form>


                <!-- COMMISSION TABLE -->

                <section class="commission-card">

                    <div class="commission-card-header">

                        <div>

                            <h2>
                                Commission Records
                            </h2>

                            <p>
                                Latest vendor commission activity.
                            </p>

                        </div>

                        <span class="commission-record-count">

                            <?= number_format(
                                count($commissionRows)
                            ) ?>

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

                                            <span
                                                class="
                                                    commission-id
                                                "
                                            >

                                                #

                                                <?= (int)
                                                    $row[
                                                        'commission_id'
                                                    ] ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    commission-order
                                                "
                                            >

                                                #

                                                <?= (int)
                                                    $row[
                                                        'order_id'
                                                    ] ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    commission-vendor
                                                "
                                            >

                                                <?= commission_e(
                                                    $row[
                                                        'business_name'
                                                    ]
                                                    ?: 'Unknown Vendor'
                                                ) ?>

                                            </span>


                                            <?php if (
                                                !empty(
                                                    $row[
                                                        'vendor_owner'
                                                    ]
                                                )
                                            ): ?>

                                                <span
                                                    class="
                                                        commission-owner
                                                    "
                                                >

                                                    <?= commission_e(
                                                        $row[
                                                            'vendor_owner'
                                                        ]
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    commission-rate
                                                "
                                            >

                                                <?= number_format(
                                                    (float)
                                                    $row[
                                                        'commission_rate'
                                                    ],
                                                    2
                                                ) ?>

                                                %

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    commission-amount
                                                "
                                            >

                                                <?= commission_money(
                                                    $row[
                                                        'commission_amount'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="
                                                    commission-status
                                                    <?= commission_e(
                                                        commission_status_class(
                                                            $row[
                                                                'status'
                                                            ]
                                                        )
                                                    ) ?>
                                                "
                                            >

                                                <?= commission_e(
                                                    $row[
                                                        'status'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= commission_e(
                                                commission_date(
                                                    $row[
                                                        'created_at'
                                                    ]
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

        </div>

    </main>

</div>

</body>

</html>