<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN COMMISSION
|--------------------------------------------------------------------------
| File: admin/commission.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';


$db = getDB();


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ../index.php');
    exit;
}


if (
    !isset($_SESSION['role']) ||
    strtolower(
        trim(
            $_SESSION['role']
        )
    ) !== 'admin'
) {

    header('Location: ../index.php');
    exit;
}


$adminId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('commissionEscape')) {

    function commissionEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('commissionMoney')) {

    function commissionMoney($amount): string
    {
        return 'RM ' .
            number_format(
                (float) $amount,
                2
            );
    }
}


if (!function_exists('commissionDate')) {

    function commissionDate($date): string
    {
        if (!$date) {
            return '-';
        }


        $timestamp =
            strtotime($date);


        if (!$timestamp) {
            return '-';
        }


        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }
}


if (!function_exists('commissionStatusClass')) {

    function commissionStatusClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        if ($status === 'paid') {
            return 'paid';
        }


        return 'pending';
    }
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$statusFilter =
    $_GET['status']
    ?? 'all';


$allowedStatuses = [

    'all',
    'Pending',
    'Paid'

];


if (
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $statusFilter =
        'all';
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$summary = [

    'total' => 0,
    'pending' => 0,
    'paid' => 0,
    'records' => 0

];


/*
|--------------------------------------------------------------------------
| TOTAL COMMISSION
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT
                COALESCE(
                    SUM(commission_amount),
                    0
                )

            FROM commission
        ");


    $summary['total'] =
        (float)
        $stmt->fetchColumn();

}

catch (Throwable $e) {

    $summary['total'] =
        0;


    error_log(
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL RECORDS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT COUNT(*)
            FROM commission
        ");


    $summary['records'] =
        (int)
        $stmt->fetchColumn();

}

catch (Throwable $e) {

    $summary['records'] =
        0;
}


/*
|--------------------------------------------------------------------------
| STATUS COUNTS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT
                status,
                COUNT(*) AS total

            FROM commission

            GROUP BY status
        ");


    while (
        $row =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        $status =
            $row['status']
            ?? '';


        $count =
            (int) (
                $row['total']
                ?? 0
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

catch (Throwable $e) {

    $summary['pending'] =
        0;


    $summary['paid'] =
        0;


    error_log(
        $e->getMessage()
    );
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
        AND
        (
            CAST(
                c.commission_id
                AS CHAR
            )
            LIKE :search1

            OR CAST(
                c.order_id
                AS CHAR
            )
            LIKE :search2

            OR CAST(
                c.vendor_id
                AS CHAR
            )
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
        '%' .
        $search .
        '%';


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
    ORDER BY
        c.created_at DESC,
        c.commission_id DESC

    LIMIT 100
";


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $commissionRows =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $commissionRows =
        [];


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


    <!-- ============================================================
         POPPINS
    ============================================================= -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | ROOT
        |--------------------------------------------------------------------------
        */

        :root {

            --commission-sidebar-width:
                260px;

            --commission-blue:
                #2563eb;

            --commission-navy:
                #08265a;

            --commission-border:
                #dce7f3;

            --commission-text:
                #0b2d63;

            --commission-muted:
                #8294b3;

        }


        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing:
                border-box;

        }


        html,
        body {

            margin:
                0;

            padding:
                0;

            min-height:
                100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                #eef5fd;

        }


        body {

            overflow-x:
                hidden;

        }


        button,
        input,
        select {

            font-family:
                inherit;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR FONT
        |--------------------------------------------------------------------------
        */

        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {

            font-family:
                'Poppins',
                sans-serif !important;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .commission-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --commission-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --commission-sidebar-width
                    )
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .commission-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                38px
                35px
                70px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .commission-hero {

            position:
                relative;

            min-height:
                155px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                34px
                38px;

            margin-bottom:
                26px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius:
                26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .commission-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                260px;

            height:
                260px;

            right:
                -70px;

            top:
                -140px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .commission-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                170px;

            height:
                170px;

            right:
                155px;

            bottom:
                -110px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .commission-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .commission-hero h1 {

            margin:
                0
                0
                8px;

            color:
                #ffffff;

            font-size:
                38px;

            line-height:
                1.05;

            font-weight:
                800;

            letter-spacing:
                -1.5px;

        }


        .commission-hero p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size:
                14px;

            font-weight:
                500;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO EMOJI
        |--------------------------------------------------------------------------
        */

        .commission-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                82px;

            height:
                82px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .26
                );

            border-radius:
                22px;

            background:

                linear-gradient(
                    145deg,
                    rgba(
                        255,
                        255,
                        255,
                        .20
                    ),
                    rgba(
                        255,
                        255,
                        255,
                        .10
                    )
                );

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .25
                ),

                0
                12px
                30px
                rgba(
                    0,
                    35,
                    100,
                    .18
                );

            font-size:
                34px;

            line-height:
                1;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .commission-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                18px;

            margin-bottom:
                30px;

        }


        .commission-stat {

            position:
                relative;

            min-height:
                150px;

            overflow:
                hidden;

            padding:
                26px
                24px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --commission-border
                );

            border-top:
                4px solid
                #2563eb;

            border-radius:
                20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .commission-stat::after {

            content:
                "";

            position:
                absolute;

            right:
                -29px;

            bottom:
                -45px;

            width:
                110px;

            height:
                110px;

            border-radius:
                50%;

            background:
                #edf4ff;

        }


        .commission-stat.records {

            border-top-color:
                #8b5cf6;

        }


        .commission-stat.records::after {

            background:
                #f4efff;

        }


        .commission-stat.pending {

            border-top-color:
                #f59e0b;

        }


        .commission-stat.pending::after {

            background:
                #fff7df;

        }


        .commission-stat.paid {

            border-top-color:
                #16a34a;

        }


        .commission-stat.paid::after {

            background:
                #eaf9ef;

        }


        .commission-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                15px;

            color:
                #61728e;

            font-size:
                10px;

            font-weight:
                800;

            letter-spacing:
                .75px;

            text-transform:
                uppercase;

        }


        .commission-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #0b326d;

            font-size:
                32px;

            line-height:
                1;

            font-weight:
                800;

        }


        .commission-stat-value.money {

            font-size:
                25px;

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .commission-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --commission-border
                );

            border-radius:
                24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .commission-panel-header {

            min-height:
                110px;

            padding:
                26px
                30px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid
                #e7edf5;

        }


        .commission-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .commission-panel-icon {

            width:
                53px;

            height:
                53px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                16px;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size:
                22px;

            line-height:
                1;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .commission-panel-header h2 {

            margin:
                0
                0
                5px;

            color:
                #092e65;

            font-size:
                20px;

            font-weight:
                800;

        }


        .commission-panel-header p {

            margin:
                0;

            color:
                #8999b4;

            font-size:
                11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT
        |--------------------------------------------------------------------------
        */

        .commission-count {

            min-height:
                36px;

            padding:
                0
                16px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #d6e7ff;

            border-radius:
                999px;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .commission-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .commission-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    260px,
                    1.6fr
                )

                minmax(
                    160px,
                    .55fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .commission-filter input,
        .commission-filter select {

            width:
                100%;

            height:
                43px;

            padding:
                0
                13px;

            outline:
                none;

            color:
                #26354e;

            background:
                #ffffff;

            border:
                1px solid
                #d8e3ef;

            border-radius:
                10px;

            font-size:
                10px;

        }


        .commission-filter input::placeholder {

            color:
                #96a5b9;

        }


        .commission-filter input:focus,
        .commission-filter select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .commission-btn {

            min-height:
                43px;

            padding:
                0
                17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .commission-btn-primary {

            color:
                #ffffff;

            border:
                0;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            box-shadow:

                0
                7px
                15px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

        }


        .commission-btn-secondary {

            color:
                #66758b;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ee;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE WRAPPER
        |--------------------------------------------------------------------------
        */

        .commission-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .commission-table {

            width:
                100%;

            min-width:
                1000px;

            border-collapse:
                collapse;

        }


        .commission-table thead {

            background:
                #f6f9fd;

        }


        .commission-table th {

            height:
                44px;

            padding:
                0
                16px;

            color:
                #65758f;

            border-bottom:
                1px solid
                #dfe7f0;

            font-size:
                8px;

            font-weight:
                800;

            text-align:
                left;

            letter-spacing:
                .55px;

            text-transform:
                uppercase;

            white-space:
                nowrap;

        }


        .commission-table td {

            padding:
                16px;

            color:
                #435169;

            border-bottom:
                1px solid
                #edf1f6;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .commission-table tbody tr:hover {

            background:
                #f9fbff;

        }


        .commission-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        .commission-id {

            color:
                #8796ac;

            font-size:
                9px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        .commission-order {

            color:
                #2563eb;

            font-size:
                9px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        .commission-vendor {

            min-width:
                170px;

        }


        .commission-vendor strong {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #112b55;

            font-size:
                10px;

            font-weight:
                800;

        }


        .commission-vendor small {

            display:
                block;

            max-width:
                180px;

            overflow:
                hidden;

            color:
                #8897ac;

            font-size:
                8px;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | RATE
        |--------------------------------------------------------------------------
        */

        .commission-rate {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            color:
                #52647f;

            background:
                #f1f5f9;

            border:
                1px solid
                #e2e8f0;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | AMOUNT
        |--------------------------------------------------------------------------
        */

        .commission-amount {

            color:
                #15803d;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .commission-status {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        .commission-status::before {

            content:
                "";

            width:
                5px;

            height:
                5px;

            border-radius:
                50%;

        }


        .commission-status.pending {

            color:
                #a16207;

            background:
                #fffbea;

        }


        .commission-status.pending::before {

            background:
                #eab308;

        }


        .commission-status.paid {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .commission-status.paid::before {

            background:
                #22c55e;

        }


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        .commission-date {

            color:
                #7c8ca3;

            font-size:
                8px;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .commission-empty {

            padding:
                75px
                20px;

            text-align:
                center;

        }


        .commission-empty-icon {

            width:
                62px;

            height:
                62px;

            margin:
                0
                auto
                15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                17px;

            font-size:
                28px;

        }


        .commission-empty h3 {

            margin:
                0
                0
                6px;

            color:
                #49617f;

            font-size:
                14px;

            font-weight:
                800;

        }


        .commission-empty p {

            max-width:
                430px;

            margin:
                0 auto;

            color:
                #94a3b8;

            font-size:
                10px;

            line-height:
                1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {

            .commission-stats {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .commission-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .commission-filter input {

                grid-column:
                    1 / -1;

            }

        }


        @media (max-width: 900px) {

            :root {

                --commission-sidebar-width:
                    0px;

            }


            .commission-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .commission-content {

                padding:
                    25px
                    20px
                    50px;

            }


            .commission-hero {

                min-height:
                    140px;

                padding:
                    28px;

            }


            .commission-hero h1 {

                font-size:
                    31px;

            }


            .commission-hero-icon {

                width:
                    67px;

                height:
                    67px;

                font-size:
                    28px;

            }

        }


        @media (max-width: 650px) {

            .commission-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .commission-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .commission-hero h1 {

                font-size:
                    27px;

            }


            .commission-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .commission-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .commission-stats {

                grid-template-columns:
                    1fr;

                gap:
                    12px;

            }


            .commission-stat {

                min-height:
                    120px;

            }


            .commission-panel-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                padding:
                    20px
                    17px;

            }


            .commission-filter {

                grid-template-columns:
                    1fr;

            }


            .commission-filter input {

                grid-column:
                    auto;

            }


            .commission-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    /*
    |--------------------------------------------------------------------------
    | ADMIN SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="commission-main">


        <div class="commission-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="commission-hero">


                <div class="commission-hero-text">

                    <h1>
                        Commission
                    </h1>

                    <p>
                        Monitor commission generated from HochipoHub vendor transactions.
                    </p>

                </div>


                <div class="commission-hero-icon">

                    💰

                </div>


            </section>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="commission-stats">


                <!-- TOTAL COMMISSION -->

                <div class="commission-stat">

                    <span class="commission-stat-label">

                        Total Commission

                    </span>


                    <strong
                        class="
                            commission-stat-value
                            money
                        "
                    >

                        <?= commissionEscape(
                            commissionMoney(
                                $summary['total']
                            )
                        ) ?>

                    </strong>

                </div>


                <!-- TOTAL RECORDS -->

                <div
                    class="
                        commission-stat
                        records
                    "
                >

                    <span class="commission-stat-label">

                        Total Records

                    </span>


                    <strong class="commission-stat-value">

                        <?= number_format(
                            $summary['records']
                        ) ?>

                    </strong>

                </div>


                <!-- PENDING -->

                <div
                    class="
                        commission-stat
                        pending
                    "
                >

                    <span class="commission-stat-label">

                        Pending

                    </span>


                    <strong class="commission-stat-value">

                        <?= number_format(
                            $summary['pending']
                        ) ?>

                    </strong>

                </div>


                <!-- PAID -->

                <div
                    class="
                        commission-stat
                        paid
                    "
                >

                    <span class="commission-stat-label">

                        Paid

                    </span>


                    <strong class="commission-stat-value">

                        <?= number_format(
                            $summary['paid']
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 COMMISSION PANEL
            ====================================================== -->

            <section class="commission-panel">


                <!-- =================================================
                     PANEL HEADER
                ================================================== -->

                <div class="commission-panel-header">


                    <div class="commission-panel-title">


                        <div class="commission-panel-icon">

                            💸

                        </div>


                        <div>

                            <h2>
                                Commission Records
                            </h2>

                            <p>
                                Review commission generated from vendor orders.
                            </p>

                        </div>


                    </div>


                    <span class="commission-count">

                        <?= number_format(
                            count(
                                $commissionRows
                            )
                        ) ?>

                        records

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="commission-filter-wrapper">


                    <form
                        method="GET"
                        action="commission.php"
                        class="commission-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= commissionEscape(
                                $search
                            ) ?>"
                            placeholder="Search commission, order, vendor or owner..."
                            autocomplete="off"
                        >


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter commission status"
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


                        <!-- SEARCH BUTTON -->

                        <button
                            type="submit"
                            class="
                                commission-btn
                                commission-btn-primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="commission.php"
                            class="
                                commission-btn
                                commission-btn-secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     EMPTY
                ================================================== -->

                <?php if (
                    empty(
                        $commissionRows
                    )
                ): ?>


                    <div class="commission-empty">


                        <div class="commission-empty-icon">

                            🧾

                        </div>


                        <h3>

                            No commission records found

                        </h3>


                        <p>

                            Commission records will appear here when vendor transactions generate marketplace commission.

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


                                    <?php

                                    $rowStatus =
                                        $row['status']
                                        ?? 'Pending';


                                    $statusClass =
                                        commissionStatusClass(
                                            $rowStatus
                                        );

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span class="commission-id">

                                                #<?= (int)
                                                    (
                                                        $row[
                                                            'commission_id'
                                                        ]
                                                        ?? 0
                                                    ) ?>

                                            </span>

                                        </td>


                                        <!-- ORDER -->

                                        <td>

                                            <span class="commission-order">

                                                #<?= (int)
                                                    (
                                                        $row[
                                                            'order_id'
                                                        ]
                                                        ?? 0
                                                    ) ?>

                                            </span>

                                        </td>


                                        <!-- VENDOR -->

                                        <td>


                                            <div class="commission-vendor">


                                                <strong>

                                                    <?= commissionEscape(
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

                                                </strong>


                                                <?php if (
                                                    !empty(
                                                        $row[
                                                            'vendor_owner'
                                                        ]
                                                    )
                                                ): ?>


                                                    <small>

                                                        <?= commissionEscape(
                                                            $row[
                                                                'vendor_owner'
                                                            ]
                                                        ) ?>

                                                    </small>


                                                <?php endif; ?>


                                                <?php if (
                                                    !empty(
                                                        $row[
                                                            'vendor_email'
                                                        ]
                                                    )
                                                ): ?>


                                                    <small>

                                                        <?= commissionEscape(
                                                            $row[
                                                                'vendor_email'
                                                            ]
                                                        ) ?>

                                                    </small>


                                                <?php endif; ?>


                                            </div>


                                        </td>


                                        <!-- RATE -->

                                        <td>

                                            <span class="commission-rate">

                                                <?= number_format(
                                                    (float)
                                                    (
                                                        $row[
                                                            'commission_rate'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    2
                                                ) ?>%

                                            </span>

                                        </td>


                                        <!-- COMMISSION -->

                                        <td>

                                            <span class="commission-amount">

                                                <?= commissionEscape(
                                                    commissionMoney(
                                                        $row[
                                                            'commission_amount'
                                                        ]
                                                        ?? 0
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="
                                                    commission-status
                                                    <?= commissionEscape(
                                                        $statusClass
                                                    ) ?>
                                                "
                                            >

                                                <?= commissionEscape(
                                                    $rowStatus
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <span class="commission-date">

                                                <?= commissionEscape(
                                                    commissionDate(
                                                        $row[
                                                            'created_at'
                                                        ]
                                                        ?? null
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


    </main>


</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH SYNC
    |--------------------------------------------------------------------------
    */

    function syncCommissionSidebar() {

        const main =
            document.querySelector(
                '.commission-main'
            );


        if (!main) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            document.documentElement
                .style
                .setProperty(
                    '--commission-sidebar-width',
                    '0px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        /*
        |--------------------------------------------------------------------------
        | FALLBACK WIDTH
        |--------------------------------------------------------------------------
        */

        if (!sidebar) {

            document.documentElement
                .style
                .setProperty(
                    '--commission-sidebar-width',
                    '260px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | REAL SIDEBAR WIDTH
        |--------------------------------------------------------------------------
        */

        const rect =
            sidebar
                .getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement
                .style
                .setProperty(
                    '--commission-sidebar-width',
                    rect.right + 'px'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncCommissionSidebar();


            setTimeout(
                syncCommissionSidebar,
                100
            );


            setTimeout(
                syncCommissionSidebar,
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncCommissionSidebar
    );

</script>


</body>

</html>