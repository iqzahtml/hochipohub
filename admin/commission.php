<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN COMMISSION
| File: admin/commission.php
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
    strtolower(trim($_SESSION['role'])) !== 'admin'
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

    while (
        $row =
        $stmt->fetch(PDO::FETCH_ASSOC)
    ) {

        $commissionRows[] =
            $row;
    }

} catch (PDOException $e) {

    $commissionRows = [];

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

        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family:
                "Poppins",
                Arial,
                Helvetica,
                sans-serif;
            background: #eef4ff;
            color: #0f172a;
        }

        /*
        IMPORTANT:
        Keep sidebar typography consistent with this page.
        */

        .admin-layout,
        .admin-layout *,
        .admin-sidebar,
        .admin-sidebar * {
            font-family:
                "Poppins",
                Arial,
                Helvetica,
                sans-serif;
        }

        .admin-main {
            min-width: 0;
        }

        /* =========================================================
           PAGE
        ========================================================= */

        .commission-page {

            min-height: 100vh;

            padding:
                34px
                36px
                50px;

            background:

                radial-gradient(
                    circle at 95% 0%,
                    rgba(37, 99, 235, 0.18),
                    transparent 26%
                ),

                radial-gradient(
                    circle at 70% 80%,
                    rgba(59, 130, 246, 0.08),
                    transparent 30%
                ),

                linear-gradient(
                    135deg,
                    #f8fbff 0%,
                    #eef5ff 48%,
                    #f8fbff 100%
                );
        }

        .commission-container {

            width: 100%;

            max-width: 1550px;

            margin: 0 auto;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .commission-header {

            position: relative;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 24px;

            margin-bottom: 30px;

            padding: 28px 30px;

            border:
                1px solid
                rgba(191, 219, 254, 0.8);

            border-radius: 26px;

            background:
                linear-gradient(
                    135deg,
                    #ffffff 0%,
                    #f4f8ff 100%
                );

            box-shadow:
                0 18px 45px
                rgba(30, 64, 175, 0.09);

            overflow: hidden;
        }

        .commission-header::before {

            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            right: -80px;
            top: -120px;

            border-radius: 50%;

            background:
                rgba(37, 99, 235, 0.12);

            pointer-events: none;
        }

        .commission-header-left {

            position: relative;
            z-index: 1;

            display: flex;

            align-items: center;

            gap: 18px;
        }

        .commission-header-icon {

            width: 64px;
            height: 64px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 20px;

            background:
                linear-gradient(
                    135deg,
                    #0f4cdb,
                    #2563eb,
                    #38bdf8
                );

            color: #ffffff;

            font-size: 28px;

            font-weight: 900;

            box-shadow:
                0 14px 30px
                rgba(37, 99, 235, 0.28);

            position: relative;
        }

        .commission-header-icon::after {

            content: "";

            position: absolute;

            inset: 2px;

            border-radius: 18px;

            border:
                1px solid
                rgba(255,255,255,0.28);
        }

        .commission-header h1 {

            margin: 0;

            color: #0b1f49;

            font-size: 32px;

            line-height: 1.1;

            font-weight: 900;

            letter-spacing: -0.7px;
        }

        .commission-header p {

            margin:
                7px
                0
                0;

            color: #64748b;

            font-size: 13px;

            font-weight: 500;
        }

        .commission-admin-badge {

            position: relative;

            z-index: 2;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding:
                11px
                17px;

            border-radius: 999px;

            background:
                #eaf2ff;

            border:
                1px solid
                #bfdbfe;

            color:
                #1d4ed8;

            font-size: 11px;

            font-weight: 900;

            letter-spacing: 0.5px;

            box-shadow:
                0 7px 18px
                rgba(37,99,235,0.08);
        }

        .commission-admin-badge::before {

            content: "";

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #22c55e;

            box-shadow:
                0 0 0 4px
                rgba(34,197,94,0.12);
        }

        /* =========================================================
           SUMMARY CARDS
        ========================================================= */

        .commission-summary {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 24px;
        }

        .commission-stat {

            position: relative;

            min-height: 150px;

            overflow: hidden;

            padding: 25px;

            border-radius: 22px;

            border:
                1px solid
                rgba(191,219,254,0.8);

            background:
                rgba(255,255,255,0.96);

            box-shadow:
                0 12px 30px
                rgba(15,23,42,0.06);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }

        .commission-stat:hover {

            transform:
                translateY(-5px);

            border-color:
                #93c5fd;

            box-shadow:
                0 20px 42px
                rgba(37,99,235,0.13);
        }

        .commission-stat::before {

            content: "";

            position: absolute;

            left: 0;
            top: 0;

            width: 100%;
            height: 4px;

            background:
                linear-gradient(
                    90deg,
                    #1d4ed8,
                    #38bdf8
                );
        }

        .commission-stat::after {

            content: "";

            position: absolute;

            width: 130px;
            height: 130px;

            right: -55px;
            bottom: -65px;

            border-radius: 50%;

            background:
                rgba(37,99,235,0.07);
        }

        .commission-stat-label {

            position: relative;

            z-index: 2;

            display: block;

            margin-bottom: 10px;

            color: #64748b;

            font-size: 11px;

            font-weight: 900;

            text-transform: uppercase;

            letter-spacing: 0.8px;
        }

        .commission-stat-value {

            position: relative;

            z-index: 2;

            display: block;

            font-size: 30px;

            line-height: 1.15;

            font-weight: 900;

            letter-spacing: -0.5px;
        }

        .commission-stat-value.blue {

            color:
                #1d4ed8;
        }

        .commission-stat-value.yellow {

            color:
                #d97706;
        }

        .commission-stat-value.green {

            color:
                #059669;
        }

        .commission-stat-sub {

            position: relative;

            z-index: 2;

            display: block;

            margin-top: 8px;

            color: #94a3b8;

            font-size: 11px;

            font-weight: 600;
        }

        /* =========================================================
           FILTER
        ========================================================= */

        .commission-filter {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 22px;

            padding: 14px;

            border:
                1px solid
                #dbeafe;

            border-radius: 18px;

            background:
                rgba(255,255,255,0.92);

            box-shadow:
                0 10px 28px
                rgba(15,23,42,0.05);
        }

        .commission-search {

            display: flex;

            flex: 1;

            gap: 9px;
        }

        .commission-search input,
        .commission-filter select {

            min-height: 46px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 12px;

            background:
                #ffffff;

            color:
                #1e293b;

            font-family:
                "Poppins",
                Arial,
                sans-serif;

            font-size: 12px;

            font-weight: 600;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .commission-search input {

            width: 100%;

            padding:
                0
                15px;
        }

        .commission-search input::placeholder {

            color:
                #94a3b8;
        }

        .commission-filter select {

            min-width: 155px;

            padding:
                0
                13px;

            cursor: pointer;
        }

        .commission-search input:hover,
        .commission-filter select:hover {

            border-color:
                #93c5fd;
        }

        .commission-search input:focus,
        .commission-filter select:focus {

            outline: none;

            border-color:
                #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(37,99,235,0.10);

            background:
                #fbfdff;
        }

        .commission-btn {

            min-height: 46px;

            padding:
                0
                20px;

            border: none;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #1d4ed8,
                    #2563eb
                );

            color:
                #ffffff;

            font-family:
                "Poppins",
                Arial,
                sans-serif;

            font-size: 11px;

            font-weight: 900;

            letter-spacing: 0.5px;

            cursor: pointer;

            box-shadow:
                0 8px 18px
                rgba(37,99,235,0.20);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .commission-btn:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(37,99,235,0.28);
        }

        .commission-reset {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 46px;

            padding:
                0
                17px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 12px;

            color:
                #475569;

            font-size: 11px;

            font-weight: 900;

            text-decoration: none;

            background:
                #ffffff;

            transition:
                all .2s ease;
        }

        .commission-reset:hover {

            color:
                #1d4ed8;

            border-color:
                #93c5fd;

            background:
                #eff6ff;

            transform:
                translateY(-1px);
        }

        /* =========================================================
           MAIN CARD
        ========================================================= */

        .commission-card {

            overflow: hidden;

            border:
                1px solid
                #dbeafe;

            border-radius: 22px;

            background:
                #ffffff;

            box-shadow:
                0 16px 40px
                rgba(15,23,42,0.07);
        }

        .commission-card-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            padding:
                23px
                25px;

            border-bottom:
                1px solid
                #e5edf9;

            background:
                linear-gradient(
                    135deg,
                    #ffffff,
                    #f8fbff
                );
        }

        .commission-card-header h2 {

            margin: 0;

            color:
                #0b1f49;

            font-size: 18px;

            font-weight: 900;

            letter-spacing:
                -0.3px;
        }

        .commission-card-header p {

            margin:
                5px
                0
                0;

            color:
                #64748b;

            font-size: 11px;

            font-weight: 500;
        }

        .commission-record-count {

            display: inline-flex;

            align-items: center;

            padding:
                8px
                12px;

            border-radius: 999px;

            background:
                #eff6ff;

            color:
                #2563eb;

            border:
                1px solid
                #dbeafe;

            font-size: 10px;

            font-weight: 900;

            white-space: nowrap;
        }

        /* =========================================================
           TABLE
        ========================================================= */

        .commission-table-wrapper {

            width: 100%;

            overflow-x: auto;
        }

        .commission-table {

            width: 100%;

            min-width: 930px;

            border-collapse: separate;

            border-spacing: 0;
        }

        .commission-table th {

            padding:
                15px
                18px;

            background:
                #f1f6ff;

            color:
                #64748b;

            font-size: 10px;

            font-weight: 900;

            text-align: left;

            text-transform:
                uppercase;

            letter-spacing:
                0.7px;

            white-space:
                nowrap;

            border-bottom:
                1px solid
                #dbeafe;
        }

        .commission-table th:first-child {

            padding-left:
                25px;
        }

        .commission-table td {

            padding:
                17px
                18px;

            border-bottom:
                1px solid
                #eef2f7;

            color:
                #334155;

            font-size: 12px;

            font-weight: 500;

            vertical-align:
                middle;
        }

        .commission-table td:first-child {

            padding-left:
                25px;
        }

        .commission-table tbody tr {

            transition:
                background .18s ease,
                transform .18s ease;
        }

        .commission-table tbody tr:hover {

            background:
                #f8fbff;
        }

        .commission-table tbody tr:last-child td {

            border-bottom:
                none;
        }

        /* =========================================================
           ID
        ========================================================= */

        .commission-id {

            display: inline-flex;

            align-items: center;

            padding:
                6px
                9px;

            border-radius:
                9px;

            background:
                #eaf2ff;

            border:
                1px solid
                #dbeafe;

            color:
                #1d4ed8;

            font-size:
                11px;

            font-weight:
                900;
        }

        /* =========================================================
           ORDER
        ========================================================= */

        .commission-order {

            color:
                #475569;

            font-weight:
                800;
        }

        /* =========================================================
           VENDOR
        ========================================================= */

        .commission-vendor {

            display:
                block;

            color:
                #0f172a;

            font-size:
                12px;

            font-weight:
                900;
        }

        .commission-owner {

            display:
                block;

            margin-top:
                4px;

            color:
                #94a3b8;

            font-size:
                10px;

            font-weight:
                600;
        }

        /* =========================================================
           RATE
        ========================================================= */

        .commission-rate {

            display:
                inline-flex;

            align-items:
                center;

            padding:
                6px
                9px;

            border-radius:
                8px;

            background:
                #f1f5f9;

            color:
                #475569;

            font-size:
                10px;

            font-weight:
                900;
        }

        /* =========================================================
           AMOUNT
        ========================================================= */

        .commission-amount {

            color:
                #059669;

            font-size:
                12px;

            font-weight:
                900;

            white-space:
                nowrap;
        }

        /* =========================================================
           STATUS
        ========================================================= */

        .commission-status {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            padding:
                7px
                11px;

            border-radius:
                999px;

            font-size:
                10px;

            font-weight:
                900;

            white-space:
                nowrap;
        }

        .commission-status::before {

            content:
                "";

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;
        }

        .commission-status-pending {

            background:
                #fff7df;

            color:
                #b45309;

            border:
                1px solid
                #fde68a;
        }

        .commission-status-pending::before {

            background:
                #f59e0b;
        }

        .commission-status-paid {

            background:
                #ecfdf5;

            color:
                #047857;

            border:
                1px solid
                #a7f3d0;
        }

        .commission-status-paid::before {

            background:
                #10b981;
        }

        /* =========================================================
           DATE
        ========================================================= */

        .commission-date {

            color:
                #64748b;

            font-size:
                10px;

            font-weight:
                600;

            white-space:
                nowrap;
        }

        /* =========================================================
           EMPTY
        ========================================================= */

        .commission-empty {

            padding:
                80px
                20px;

            text-align:
                center;
        }

        .commission-empty-icon {

            width:
                70px;

            height:
                70px;

            margin:
                0
                auto
                17px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                22px;

            background:
                linear-gradient(
                    135deg,
                    #eaf2ff,
                    #dbeafe
                );

            border:
                1px solid
                #bfdbfe;

            color:
                #2563eb;

            font-size:
                29px;

            font-weight:
                900;

            box-shadow:
                0 12px 25px
                rgba(37,99,235,0.10);
        }

        .commission-empty h3 {

            margin:
                0;

            color:
                #0f172a;

            font-size:
                17px;

            font-weight:
                900;
        }

        .commission-empty p {

            max-width:
                450px;

            margin:
                8px
                auto
                0;

            color:
                #64748b;

            font-size:
                12px;

            line-height:
                1.7;
        }

        /* =========================================================
           SCROLLBAR
        ========================================================= */

        .commission-table-wrapper::-webkit-scrollbar {

            height:
                8px;
        }

        .commission-table-wrapper::-webkit-scrollbar-track {

            background:
                #eef4ff;
        }

        .commission-table-wrapper::-webkit-scrollbar-thumb {

            background:
                #93c5fd;

            border-radius:
                999px;
        }

        .commission-table-wrapper::-webkit-scrollbar-thumb:hover {

            background:
                #60a5fa;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .commission-summary {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

            .commission-page {

                padding:
                    25px;
            }
        }

        @media (max-width: 900px) {

            .commission-summary {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .commission-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }

            .commission-admin-badge {

                align-self:
                    flex-start;
            }

            .commission-filter {

                flex-wrap:
                    wrap;
            }

            .commission-search {

                width:
                    100%;
                flex-basis:
                    100%;
            }

            .commission-filter select {

                flex:
                    1;
            }
        }

        @media (max-width: 650px) {

            .commission-page {

                padding:
                    18px;
            }

            .commission-header {

                padding:
                    22px;
            }

            .commission-header-left {

                align-items:
                    flex-start;
            }

            .commission-header-icon {

                width:
                    52px;

                height:
                    52px;

                border-radius:
                    16px;

                font-size:
                    23px;
            }

            .commission-header h1 {

                font-size:
                    25px;
            }

            .commission-header p {

                font-size:
                    11px;
            }

            .commission-summary {

                grid-template-columns:
                    1fr;
            }

            .commission-stat {

                min-height:
                    130px;
            }

            .commission-filter {

                flex-direction:
                    column;

                align-items:
                    stretch;
            }

            .commission-search {

                flex-direction:
                    column;
            }

            .commission-btn,
            .commission-reset,
            .commission-filter select {

                width:
                    100%;
            }

            .commission-card-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                padding:
                    20px;
            }
        }

        @media (max-width: 420px) {

            .commission-page {

                padding:
                    13px;
            }

            .commission-header {

                padding:
                    18px;
            }

            .commission-header-left {

                gap:
                    12px;
            }

            .commission-header h1 {

                font-size:
                    22px;
            }

            .commission-stat {

                padding:
                    20px;
            }

            .commission-stat-value {

                font-size:
                    25px;
            }
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- =========================================================
         SIDEBAR
         
         IMPORTANT:
         DO NOT ADD ANOTHER MANUAL SIDEBAR HERE.
         admin_sidebar.php is the ONLY sidebar.
    ========================================================== -->

    <?php
    require_once dirname(__DIR__) . '/includes/admin_sidebar.php';
    ?>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main class="admin-main">

        <div class="commission-page">

            <div class="commission-container">


                <!-- =================================================
                     HEADER
                ================================================== -->

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


                <!-- =================================================
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

                        <span class="commission-stat-sub">
                            Overall commission generated
                        </span>

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

                        <span class="commission-stat-sub">
                            Commission awaiting payment
                        </span>

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

                        <span class="commission-stat-sub">
                            Successfully paid commissions
                        </span>

                    </div>

                </section>


                <!-- =================================================
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


                <!-- =================================================
                     COMMISSION RECORDS
                ================================================== -->

                <section class="commission-card">


                    <!-- CARD HEADER -->

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


                    <!-- =================================================
                         EMPTY STATE
                    ================================================== -->

                    <?php if (empty($commissionRows)): ?>

                        <div class="commission-empty">

                            <div class="commission-empty-icon">
                                %
                            </div>

                            <h3>
                                No commission records found
                            </h3>

                            <p>
                                Commission records will appear here when
                                vendor transactions generate commission.
                            </p>

                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             TABLE
                        ================================================== -->

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

                                            <span class="commission-vendor">

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

                                                <span class="commission-owner">

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

                                            <span class="commission-rate">

                                                <?= number_format(
                                                    (float)
                                                    (
                                                        $row[
                                                            'commission_rate'
                                                        ] ?? 0
                                                    ),
                                                    2
                                                ) ?>%

                                            </span>

                                        </td>


                                        <!-- COMMISSION -->

                                        <td>

                                            <span class="commission-amount">

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

                                            <span class="commission-date">

                                                <?= commission_e(
                                                    commission_date(
                                                        $row[
                                                            'created_at'
                                                        ] ?? null
                                                    )
                                                ) ?>

                                            </span>

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