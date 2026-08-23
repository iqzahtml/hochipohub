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
| database/db.php creates $db as PDO connection.
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
                    trim((string) $status)
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

try {

    $result = $conn->query("
        SELECT
            COALESCE(
                SUM(commission_amount),
                0
            ) AS total
        FROM commission
    ");

    if ($result) {

        /*
        | PDO uses fetch(), NOT fetch_assoc()
        */

        $row = $result->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $summary['total'] =
                (float) (
                    $row['total']
                    ?? 0
                );
        }
    }

} catch (PDOException $e) {

    $summary['total'] = 0;
}

/*
|--------------------------------------------------------------------------
| STATUS COUNTS
|--------------------------------------------------------------------------
*/

try {

    $result = $conn->query("
        SELECT
            status,
            COUNT(*) AS total
        FROM commission
        GROUP BY status
    ");

    if ($result) {

        while (
            $row =
            $result->fetch(PDO::FETCH_ASSOC)
        ) {

            $status = $row['status'] ?? '';

            $count = (int) (
                $row['total'] ?? 0
            );

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

} catch (PDOException $e) {

    $summary['pending'] = 0;
    $summary['paid'] = 0;
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

$params = [];

/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter !== 'all') {

    $sql .= "
        AND c.status = :status
    ";

    $params[':status'] =
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
                LIKE :search1

            OR CAST(c.order_id AS CHAR)
                LIKE :search2

            OR CAST(c.vendor_id AS CHAR)
                LIKE :search3

            OR v.business_name
                LIKE :search4

            OR u.name
                LIKE :search5

            OR u.email
                LIKE :search6
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[':search1'] =
        $searchValue;

    $params[':search2'] =
        $searchValue;

    $params[':search3'] =
        $searchValue;

    $params[':search4'] =
        $searchValue;

    $params[':search5'] =
        $searchValue;

    $params[':search6'] =
        $searchValue;
}

/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY c.created_at DESC
    LIMIT 100
";

/*
|--------------------------------------------------------------------------
| EXECUTE COMMISSION QUERY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $conn->prepare($sql);

    $stmt->execute($params);

    /*
    | PDO uses fetch(PDO::FETCH_ASSOC)
    */

    while (
        $row =
        $stmt->fetch(PDO::FETCH_ASSOC)
    ) {

        $commissionRows[] =
            $row;
    }

} catch (PDOException $e) {

    $commissionRows = [];

    /*
    | Do not expose database details to users.
    | For development, you can inspect the PHP error log.
    */

    error_log(
        'Commission query error: ' .
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

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

        /* --------------------------------------------------------------
           HEADER
        -------------------------------------------------------------- */

        .commission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .commission-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .commission-header-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #4f46e5
            );
            color: #ffffff;
            font-size: 26px;
            font-weight: 900;
            box-shadow:
                0 10px 25px
                rgba(37, 99, 235, .20);
        }

        .commission-header h1 {
            margin: 0;
            color: #0f172a;
            font-size: 32px;
            font-weight: 900;
            line-height: 1.1;
        }

        .commission-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .commission-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 16px;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
            font-size: 12px;
            font-weight: 900;
        }

        /* --------------------------------------------------------------
           SUMMARY
        -------------------------------------------------------------- */

        .commission-summary {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .commission-stat {
            position: relative;
            overflow: hidden;
            padding: 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, .05);
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .commission-stat:hover {
            transform: translateY(-3px);
            box-shadow:
                0 14px 32px
                rgba(15, 23, 42, .08);
        }

        .commission-stat::after {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            right: -35px;
            bottom: -45px;
            border-radius: 50%;
            background: #eff6ff;
        }

        .commission-stat-label {
            position: relative;
            z-index: 1;
            display: block;
            margin-bottom: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .commission-stat-value {
            position: relative;
            z-index: 1;
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

        /* --------------------------------------------------------------
           FILTER
        -------------------------------------------------------------- */

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
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #334155;
            font-family: inherit;
            font-size: 13px;
        }

        .commission-search input {
            width: 100%;
            padding: 0 13px;
        }

        .commission-filter select {
            min-width: 145px;
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
            font-family: inherit;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            transition: background .2s ease;
        }

        .commission-btn:hover {
            background: #1d4ed8;
        }

        .commission-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            background: #ffffff;
        }

        .commission-reset:hover {
            background: #f8fafc;
            color: #2563eb;
            border-color: #93c5fd;
        }

        /* --------------------------------------------------------------
           CARD
        -------------------------------------------------------------- */

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
            gap: 20px;
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
            padding: 7px 11px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        /* --------------------------------------------------------------
           TABLE
        -------------------------------------------------------------- */

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
            white-space: nowrap;
        }

        .commission-table td {
            padding: 17px 18px;
            border-top: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .commission-table tbody tr {
            transition: background .15s ease;
        }

        .commission-table tbody tr:hover td {
            background: #f8fafc;
        }

        .commission-id {
            display: inline-flex;
            align-items: center;
            padding: 6px 9px;
            border-radius: 8px;
            background: #eff6ff;
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
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
        }

        .commission-rate {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 800;
        }

        .commission-amount {
            color: #059669;
            font-weight: 900;
            white-space: nowrap;
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

        /* --------------------------------------------------------------
           EMPTY
        -------------------------------------------------------------- */

        .commission-empty {
            padding: 75px 20px;
            text-align: center;
        }

        .commission-empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
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
            margin: 8px auto 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        /* --------------------------------------------------------------
           RESPONSIVE
        -------------------------------------------------------------- */

        @media (max-width: 1000px) {

            .commission-summary {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 800px) {

            .commission-page {
                padding: 20px;
            }

            .commission-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .commission-header-left {
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

            .commission-filter select {
                width: 100%;
            }

            .commission-reset {
                width: 100%;
            }

        }

        @media (max-width: 500px) {

            .commission-header h1 {
                font-size: 26px;
            }

            .commission-header-icon {
                width: 50px;
                height: 50px;
                border-radius: 15px;
            }

            .commission-search {
                flex-direction: column;
            }

            .commission-btn {
                width: 100%;
            }

            .commission-card-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- ==============================================================
         SIDEBAR
    ============================================================== -->

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


    <!-- ==============================================================
         MAIN
    ============================================================== -->

    <main class="admin-main">

        <div class="commission-page">

            <div class="commission-container">

                <!-- HEADER -->

                <header class="commission-header">

                    <div class="commission-header-left">

                        <div class="commission-header-icon">
                            %
                        </div>

                        <div>

                            <h1>
                                Commission
                            </h1>

                            <p>
                                Monitor commission generated from vendor transactions.
                            </p>

                        </div>

                    </div>

                    <div class="commission-admin-badge">
                        ADMIN CONTROL
                    </div>

                </header>


                <!-- ==================================================
                     SUMMARY
                ================================================== -->

                <section class="commission-summary">

                    <!-- TOTAL -->

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


                    <!-- PENDING -->

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


                    <!-- PAID -->

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


                <!-- ==================================================
                     FILTER
                ================================================== -->

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


                <!-- ==================================================
                     COMMISSION TABLE
                ================================================== -->

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

                                        <!-- ID -->

                                        <td>

                                            <span class="commission-id">

                                                #

                                                <?= (int)
                                                    (
                                                        $row[
                                                            'commission_id'
                                                        ] ?? 0
                                                    ) ?>

                                            </span>

                                        </td>


                                        <!-- ORDER -->

                                        <td>

                                            <span class="commission-order">

                                                #

                                                <?= (int)
                                                    (
                                                        $row[
                                                            'order_id'
                                                        ] ?? 0
                                                    ) ?>

                                            </span>

                                        </td>


                                        <!-- VENDOR -->

                                        <td>

                                            <span
                                                class="
                                                    commission-vendor
                                                "
                                            >

                                                <?= commission_e(
                                                    !empty(
                                                        $row[
                                                            'business_name'
                                                        ]
                                                    )
                                                        ? $row[
                                                            'business_name'
                                                        ]
                                                        : 'Unknown Vendor'
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


                                        <!-- RATE -->

                                        <td>

                                            <span
                                                class="
                                                    commission-rate
                                                "
                                            >

                                                <?= number_format(
                                                    (float)
                                                    (
                                                        $row[
                                                            'commission_rate'
                                                        ] ?? 0
                                                    ),
                                                    2
                                                ) ?>

                                                %

                                            </span>

                                        </td>


                                        <!-- COMMISSION -->

                                        <td>

                                            <span
                                                class="
                                                    commission-amount
                                                "
                                            >

                                                <?= commission_money(
                                                    $row[
                                                        'commission_amount'
                                                    ] ?? 0
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?php

                                            $rowStatus =
                                                $row[
                                                    'status'
                                                ] ?? 'Pending';

                                            ?>

                                            <span
                                                class="
                                                    commission-status
                                                    <?= commission_e(
                                                        commission_status_class(
                                                            $rowStatus
                                                        )
                                                    ) ?>
                                                "
                                            >

                                                <?= commission_e(
                                                    $rowStatus
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?= commission_e(
                                                commission_date(
                                                    $row[
                                                        'created_at'
                                                    ] ?? null
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