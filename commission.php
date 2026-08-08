<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
| Commission page is for vendors only.
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . site_url('index.php?login=required'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get Current User
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

$userResult = $stmt->get_result();
$currentUser = $userResult->fetch_assoc();

$stmt->close();

if (!$currentUser) {
    session_destroy();
    header('Location: ' . site_url('index.php'));
    exit;
}

if ($currentUser['role'] !== 'vendor') {
    header('Location: ' . site_url('dashboard.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Vendor
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        vendor_id,
        user_id,
        business_name,
        business_logo,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$vendorResult = $stmt->get_result();
$vendor = $vendorResult->fetch_assoc();

$stmt->close();

if (!$vendor) {
    header('Location: ' . site_url('vendor.php'));
    exit;
}

$vendorId = (int) $vendor['vendor_id'];

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$statusFilter = isset($_GET['status'])
    ? trim($_GET['status'])
    : 'all';

$allowedStatuses = [
    'all',
    'Pending',
    'Paid'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$summary = [
    'total_commission' => 0,
    'pending_commission' => 0,
    'paid_commission' => 0,
    'total_orders' => 0
];

/*
|--------------------------------------------------------------------------
| Total Commission
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(commission_amount), 0) AS total_commission,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS pending_commission,
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
        COUNT(DISTINCT order_id) AS total_orders
    FROM commission
    WHERE vendor_id = ?
");

$stmt->bind_param("i", $vendorId);
$stmt->execute();

$summaryResult = $stmt->get_result();
$summaryData = $summaryResult->fetch_assoc();

$stmt->close();

if ($summaryData) {
    $summary = [
        'total_commission' =>
            (float) $summaryData['total_commission'],

        'pending_commission' =>
            (float) $summaryData['pending_commission'],

        'paid_commission' =>
            (float) $summaryData['paid_commission'],

        'total_orders' =>
            (int) $summaryData['total_orders']
    ];
}

/*
|--------------------------------------------------------------------------
| Commission Rate
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        commission_rate
    FROM commission
    WHERE vendor_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$stmt->bind_param("i", $vendorId);
$stmt->execute();

$rateResult = $stmt->get_result();
$rateData = $rateResult->fetch_assoc();

$stmt->close();

$commissionRate = $rateData
    ? (float) $rateData['commission_rate']
    : 0;

/*
|--------------------------------------------------------------------------
| Commission Records
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.commission_id,
        c.order_id,
        c.vendor_order_id,
        c.commission_rate,
        c.commission_amount,
        c.status,
        c.created_at,

        vo.subtotal,
        vo.delivery_fee,
        vo.vendor_status,
        vo.tracking_number,

        o.order_date,
        o.order_status,
        o.total_amount,

        u.name AS customer_name

    FROM commission c

    INNER JOIN orders o
        ON c.order_id = o.order_id

    LEFT JOIN vendor_orders vo
        ON c.vendor_order_id = vo.vendor_order_id

    INNER JOIN users u
        ON o.customer_id = u.user_id

    WHERE c.vendor_id = ?
";

$params = [$vendorId];
$types = "i";

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($statusFilter !== 'all') {
    $sql .= " AND c.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            CAST(c.order_id AS CHAR) LIKE ?
            OR CAST(c.commission_id AS CHAR) LIKE ?
            OR u.name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

$sql .= "
    ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$commissionResult = $stmt->get_result();

$commissionRecords = [];

while ($row = $commissionResult->fetch_assoc()) {
    $commissionRecords[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Helper
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
        Commission | <?php echo htmlspecialchars(SITE_NAME); ?>
    </title>

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/dashboard.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >

    <style>

        /* =====================================================
           COMMISSION PAGE
        ===================================================== */

        .commission-page {
            min-height: 100vh;

            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(37,99,235,.16),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                #020617;

            color: #f8fafc;
        }

        .commission-container {
            width: 90%;
            max-width: 1400px;

            margin: 0 auto;

            padding: 40px 0 80px;
        }

        /* =====================================================
           HERO
        ===================================================== */

        .commission-hero {
            position: relative;

            overflow: hidden;

            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 25px;

            margin-bottom: 25px;

            padding: 32px;

            border:
                1px solid
                rgba(56,189,248,.18);

            border-radius: 26px;

            background:
                linear-gradient(
                    135deg,
                    rgba(15,23,42,.97),
                    rgba(8,47,87,.72)
                );

            box-shadow:
                0 25px 80px
                rgba(0,0,0,.25);
        }

        .commission-hero::before {
            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            right: -80px;
            top: -100px;

            border-radius: 50%;

            background:
                rgba(14,165,233,.12);

            filter: blur(5px);
        }

        .commission-hero-content {
            position: relative;
            z-index: 1;
        }

        .commission-eyebrow {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            margin-bottom: 8px;

            color: #38bdf8;

            font-size: 10px;
            font-weight: 950;

            letter-spacing: 1.7px;
        }

        .commission-eyebrow::before {
            content: "";

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #22d3ee;

            box-shadow:
                0 0 14px
                rgba(34,211,238,.8);
        }

        .commission-hero h1 {
            margin: 0;

            font-size: clamp(28px, 4vw, 44px);

            line-height: 1;

            font-weight: 950;

            letter-spacing: -1.5px;
        }

        .commission-hero h1 span {
            color: #38bdf8;
        }

        .commission-hero p {
            max-width: 650px;

            margin: 12px 0 0;

            color: #64748b;

            font-size: 13px;

            line-height: 1.7;
        }

        .commission-rate-card {
            position: relative;
            z-index: 1;

            min-width: 190px;

            padding: 20px;

            border:
                1px solid
                rgba(56,189,248,.2);

            border-radius: 20px;

            background:
                rgba(2,6,23,.5);

            text-align: center;
        }

        .commission-rate-card span {
            display: block;

            margin-bottom: 7px;

            color: #64748b;

            font-size: 9px;
            font-weight: 900;

            letter-spacing: 1.3px;
        }

        .commission-rate-card strong {
            display: block;

            color: #7dd3fc;

            font-size: 34px;
            font-weight: 950;
        }

        .commission-rate-card small {
            color: #475569;

            font-size: 9px;
        }

        /* =====================================================
           STAT CARDS
        ===================================================== */

        .commission-stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            margin-bottom: 25px;
        }

        .commission-stat {
            position: relative;

            overflow: hidden;

            padding: 22px;

            border:
                1px solid
                rgba(148,163,184,.12);

            border-radius: 19px;

            background:
                rgba(15,23,42,.78);

            transition: .25s ease;
        }

        .commission-stat:hover {
            transform: translateY(-3px);

            border-color:
                rgba(56,189,248,.3);

            box-shadow:
                0 15px 40px
                rgba(0,0,0,.18);
        }

        .commission-stat::after {
            content: "";

            position: absolute;

            width: 80px;
            height: 80px;

            right: -30px;
            bottom: -35px;

            border-radius: 50%;

            background:
                rgba(14,165,233,.08);
        }

        .stat-label {
            display: block;

            margin-bottom: 9px;

            color: #64748b;

            font-size: 9px;
            font-weight: 900;

            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .stat-value {
            display: block;

            color: #f8fafc;

            font-size: 24px;
            font-weight: 950;
        }

        .stat-sub {
            display: block;

            margin-top: 5px;

            color: #475569;

            font-size: 10px;
        }

        .stat-icon {
            position: absolute;

            top: 20px;
            right: 20px;

            display: flex;

            align-items: center;
            justify-content: center;

            width: 34px;
            height: 34px;

            border-radius: 11px;

            background:
                rgba(14,165,233,.1);

            color: #38bdf8;

            font-size: 15px;
        }

        /* =====================================================
           TOOLBAR
        ===================================================== */

        .commission-toolbar {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 15px;

            padding: 17px 20px;

            border:
                1px solid
                rgba(148,163,184,.1);

            border-radius: 18px;

            background:
                rgba(15,23,42,.75);
        }

        .commission-toolbar-title strong {
            display: block;

            color: #e2e8f0;

            font-size: 15px;
        }

        .commission-toolbar-title span {
            display: block;

            margin-top: 3px;

            color: #475569;

            font-size: 10px;
        }

        .commission-filters {
            display: flex;

            align-items: center;

            gap: 9px;
        }

        .commission-search {
            width: 190px;

            padding: 10px 13px;

            border:
                1px solid
                rgba(148,163,184,.13);

            border-radius: 11px;

            outline: none;

            background:
                rgba(2,6,23,.6);

            color: white;

            font-size: 11px;
        }

        .commission-search:focus {
            border-color: #38bdf8;
        }

        .filter-btn {
            padding: 10px 13px;

            border:
                1px solid
                rgba(148,163,184,.12);

            border-radius: 11px;

            background:
                rgba(2,6,23,.55);

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            cursor: pointer;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color:
                rgba(56,189,248,.4);

            background:
                rgba(14,165,233,.1);

            color: #7dd3fc;
        }

        /* =====================================================
           TABLE
        ===================================================== */

        .commission-table-card {
            overflow: hidden;

            border:
                1px solid
                rgba(148,163,184,.1);

            border-radius: 21px;

            background:
                rgba(15,23,42,.78);
        }

        .commission-table-wrap {
            width: 100%;

            overflow-x: auto;
        }

        .commission-table {
            width: 100%;

            border-collapse: collapse;

            min-width: 900px;
        }

        .commission-table th {
            padding: 15px 18px;

            border-bottom:
                1px solid
                rgba(148,163,184,.09);

            background:
                rgba(2,6,23,.35);

            color: #475569;

            font-size: 9px;
            font-weight: 900;

            text-align: left;

            letter-spacing: 1px;

            text-transform: uppercase;
        }

        .commission-table td {
            padding: 17px 18px;

            border-bottom:
                1px solid
                rgba(148,163,184,.06);

            color: #cbd5e1;

            font-size: 11px;
        }

        .commission-table tr:last-child td {
            border-bottom: 0;
        }

        .commission-table tbody tr {
            transition: .2s ease;
        }

        .commission-table tbody tr:hover {
            background:
                rgba(14,165,233,.035);
        }

        .order-number {
            color: #7dd3fc;

            font-weight: 900;
        }

        .commission-id {
            display: block;

            margin-top: 3px;

            color: #475569;

            font-size: 9px;
        }

        .customer-name {
            color: #e2e8f0;

            font-weight: 800;
        }

        .date-text {
            color: #64748b;

            font-size: 10px;
        }

        .amount {
            color: #f8fafc;

            font-weight: 900;
        }

        .rate {
            color: #7dd3fc;

            font-weight: 900;
        }

        /* =====================================================
           STATUS
        ===================================================== */

        .commission-status {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 10px;

            border-radius: 99px;

            font-size: 9px;
            font-weight: 900;
        }

        .commission-status::before {
            content: "";

            width: 5px;
            height: 5px;

            border-radius: 50%;
        }

        .status-pending {
            border:
                1px solid
                rgba(250,204,21,.16);

            background:
                rgba(250,204,21,.08);

            color: #fde047;
        }

        .status-pending::before {
            background: #facc15;
        }

        .status-paid {
            border:
                1px solid
                rgba(34,197,94,.16);

            background:
                rgba(34,197,94,.08);

            color: #86efac;
        }

        .status-paid::before {
            background: #22c55e;
        }

        /* =====================================================
           EMPTY
        ===================================================== */

        .commission-empty {
            padding: 75px 25px;

            text-align: center;
        }

        .empty-icon {
            display: flex;

            align-items: center;
            justify-content: center;

            width: 65px;
            height: 65px;

            margin: 0 auto 15px;

            border-radius: 20px;

            background:
                rgba(14,165,233,.08);

            color: #38bdf8;

            font-size: 25px;
        }

        .commission-empty h3 {
            margin: 0;

            color: #cbd5e1;

            font-size: 17px;
        }

        .commission-empty p {
            max-width: 400px;

            margin: 8px auto 0;

            color: #475569;

            font-size: 11px;

            line-height: 1.6;
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 1000px) {

            .commission-stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 760px) {

            .commission-container {
                width: 92%;

                padding-top: 25px;
            }

            .commission-hero {
                flex-direction: column;

                align-items: flex-start;

                padding: 25px;
            }

            .commission-rate-card {
                width: 100%;

                box-sizing: border-box;
            }

            .commission-toolbar {
                flex-direction: column;

                align-items: stretch;
            }

            .commission-filters {
                flex-wrap: wrap;
            }

            .commission-search {
                flex: 1;

                width: auto;
            }

        }

        @media (max-width: 500px) {

            .commission-stats {
                grid-template-columns: 1fr;
            }

            .commission-hero h1 {
                font-size: 30px;
            }

            .commission-filters {
                display: grid;

                grid-template-columns:
                    repeat(3, 1fr);
            }

            .commission-search {
                grid-column:
                    1 / -1;

                width: 100%;

                box-sizing: border-box;
            }

            .filter-btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="commission-page">

    <?php
    /*
     * Existing navbar from project structure.
     */
    require_once __DIR__ . '/includes/navbar.php';
    ?>


    <main class="commission-container">


        <!-- =================================================
             HERO
        ================================================== -->

        <section class="commission-hero">

            <div class="commission-hero-content">

                <div class="commission-eyebrow">
                    VENDOR FINANCE
                </div>

                <h1>
                    Commission
                    <span>Hub.</span>
                </h1>

                <p>
                    Track HochipoHub's commission from your
                    vendor orders. Everything is calculated
                    from the commission records linked to
                    your orders.
                </p>

            </div>


            <div class="commission-rate-card">

                <span>
                    CURRENT RATE
                </span>

                <strong>
                    <?php
                    echo number_format(
                        $commissionRate,
                        2
                    );
                    ?>%
                </strong>

                <small>
                    Based on latest commission record
                </small>

            </div>

        </section>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="commission-stats">


            <div class="commission-stat">

                <div class="stat-icon">
                    RM
                </div>

                <span class="stat-label">
                    Total Commission
                </span>

                <strong class="stat-value">
                    <?php
                    echo commission_money(
                        $summary['total_commission']
                    );
                    ?>
                </strong>

                <span class="stat-sub">
                    All commission records
                </span>

            </div>


            <div class="commission-stat">

                <div class="stat-icon">
                    ⏳
                </div>

                <span class="stat-label">
                    Pending
                </span>

                <strong class="stat-value">
                    <?php
                    echo commission_money(
                        $summary['pending_commission']
                    );
                    ?>
                </strong>

                <span class="stat-sub">
                    Awaiting payment
                </span>

            </div>


            <div class="commission-stat">

                <div class="stat-icon">
                    ✓
                </div>

                <span class="stat-label">
                    Paid
                </span>

                <strong class="stat-value">
                    <?php
                    echo commission_money(
                        $summary['paid_commission']
                    );
                    ?>
                </strong>

                <span class="stat-sub">
                    Successfully paid
                </span>

            </div>


            <div class="commission-stat">

                <div class="stat-icon">
                    #
                </div>

                <span class="stat-label">
                    Orders
                </span>

                <strong class="stat-value">
                    <?php
                    echo number_format(
                        $summary['total_orders']
                    );
                    ?>
                </strong>

                <span class="stat-sub">
                    Orders with commission
                </span>

            </div>

        </section>


        <!-- =================================================
             TOOLBAR
        ================================================== -->

        <section class="commission-toolbar">

            <div class="commission-toolbar-title">

                <strong>
                    Commission Records
                </strong>

                <span>
                    Showing records belonging to
                    <?php
                    echo htmlspecialchars(
                        $vendor['business_name']
                    );
                    ?>
                </span>

            </div>


            <form
                method="GET"
                class="commission-filters"
            >

                <input
                    type="search"
                    name="search"
                    class="commission-search"
                    placeholder="Search order / customer..."
                    value="<?php
                    echo htmlspecialchars($search);
                    ?>"
                >


                <button
                    type="submit"
                    name="status"
                    value="all"
                    class="
                        filter-btn
                        <?php
                        echo $statusFilter === 'all'
                            ? 'active'
                            : '';
                        ?>
                    "
                >
                    All
                </button>


                <button
                    type="submit"
                    name="status"
                    value="Pending"
                    class="
                        filter-btn
                        <?php
                        echo $statusFilter === 'Pending'
                            ? 'active'
                            : '';
                        ?>
                    "
                >
                    Pending
                </button>


                <button
                    type="submit"
                    name="status"
                    value="Paid"
                    class="
                        filter-btn
                        <?php
                        echo $statusFilter === 'Paid'
                            ? 'active'
                            : '';
                        ?>
                    "
                >
                    Paid
                </button>

            </form>

        </section>


        <!-- =================================================
             TABLE
        ================================================== -->

        <section class="commission-table-card">

            <?php if (
                empty($commissionRecords)
            ): ?>

                <div class="commission-empty">

                    <div class="empty-icon">
                        ◈
                    </div>

                    <h3>
                        No commission records yet
                    </h3>

                    <p>
                        Commission records will appear here
                        when orders from your store have
                        commission information.
                    </p>

                </div>

            <?php else: ?>

                <div class="commission-table-wrap">

                    <table class="commission-table">

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Order Date
                                </th>

                                <th>
                                    Vendor Subtotal
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

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $commissionRecords
                            as $record
                        ): ?>

                            <tr>


                                <!-- ORDER -->

                                <td>

                                    <span class="order-number">

                                        #
                                        <?php
                                        echo (int)
                                            $record['order_id'];
                                        ?>

                                    </span>

                                    <span class="commission-id">

                                        Commission #
                                        <?php
                                        echo (int)
                                            $record['commission_id'];
                                        ?>

                                    </span>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <span class="customer-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $record[
                                                'customer_name'
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <span class="date-text">

                                        <?php
                                        echo commission_date(
                                            $record[
                                                'order_date'
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- SUBTOTAL -->

                                <td>

                                    <span class="amount">

                                        <?php
                                        echo commission_money(
                                            $record[
                                                'subtotal'
                                            ] ?? 0
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- RATE -->

                                <td>

                                    <span class="rate">

                                        <?php
                                        echo number_format(
                                            (float)
                                            $record[
                                                'commission_rate'
                                            ],
                                            2
                                        );
                                        ?>%

                                    </span>

                                </td>


                                <!-- COMMISSION -->

                                <td>

                                    <span class="amount">

                                        <?php
                                        echo commission_money(
                                            $record[
                                                'commission_amount'
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php
                                    $recordStatus =
                                        $record['status'];

                                    $statusClass =
                                        $recordStatus === 'Paid'
                                            ? 'status-paid'
                                            : 'status-pending';
                                    ?>

                                    <span
                                        class="
                                            commission-status
                                            <?php
                                            echo $statusClass;
                                            ?>
                                        "
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $recordStatus
                                        );
                                        ?>

                                    </span>

                                </td>


                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


    </main>

</div>

</body>

</html>