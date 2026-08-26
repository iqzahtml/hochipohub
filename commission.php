<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER COMMISSION
|--------------------------------------------------------------------------
| File:
| commission.php
|--------------------------------------------------------------------------
|
| This file is located in project root but belongs to Seller Center.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

requireLogin();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$userId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('sellerCommissionEscape')) {

    function sellerCommissionEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('sellerCommissionDate')) {

    function sellerCommissionDate($value): string
    {
        if (empty($value)) {

            return '-';

        }


        $timestamp =
            strtotime(
                (string) $value
            );


        if (!$timestamp) {

            return '-';

        }


        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }

}


if (!function_exists('sellerCommissionStatusClass')) {

    function sellerCommissionStatusClass($status): string
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


        if ($status === 'pending') {

            return 'pending';

        }


        return 'default';
    }

}


/*
|--------------------------------------------------------------------------
| CHECK USER
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                user_id,
                name,
                email,
                phone,
                role,
                status

            FROM users

            WHERE user_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $userId
    ]);


    $user =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $user = false;

}


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    header(
        'Location: index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| VENDOR ONLY
|--------------------------------------------------------------------------
*/

if (
    strtolower(
        trim(
            (string) $user['role']
        )
    ) !== 'vendor'
) {

    header(
        'Location: dashboard.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                v.vendor_id,
                v.business_name,
                v.business_logo,
                v.business_description,
                v.business_address,
                v.category,
                v.delivery_method,
                v.approval_status,
                v.created_at,

                u.name,
                u.email,
                u.phone

            FROM vendors v

            INNER JOIN users u
                ON v.user_id = u.user_id

            WHERE v.user_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $userId
    ]);


    $vendor =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $vendor = false;

}


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    header(
        'Location: dashboard.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| VENDOR ID
|--------------------------------------------------------------------------
*/

$vendorId =
    (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| SIDEBAR SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];


$_SESSION['vendor_approval_status'] =
    $vendor['approval_status'];


/*
|--------------------------------------------------------------------------
| COMMISSION SUMMARY
|--------------------------------------------------------------------------
*/

$summary = [

    'total_records'     => 0,
    'total_commission'  => 0,
    'paid_commission'   => 0,
    'pending_commission'=> 0

];


try {

    $stmt =
        $db->prepare("
            SELECT

                COUNT(*) AS total_records,

                COALESCE(
                    SUM(
                        commission_amount
                    ),
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


    $stmt->execute([
        $vendorId
    ]);


    $row =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($row) {

        $summary['total_records'] =
            (int) (
                $row['total_records']
                ?? 0
            );


        $summary['total_commission'] =
            (float) (
                $row['total_commission']
                ?? 0
            );


        $summary['paid_commission'] =
            (float) (
                $row['paid_commission']
                ?? 0
            );


        $summary['pending_commission'] =
            (float) (
                $row['pending_commission']
                ?? 0
            );

    }

}

catch (Throwable $e) {

}


/*
|--------------------------------------------------------------------------
| COMMISSION RECORDS
|--------------------------------------------------------------------------
*/

$commissions = [];


try {

    $stmt =
        $db->prepare("
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

            ORDER BY
                c.created_at DESC
        ");


    $stmt->execute([
        $vendorId
    ]);


    $commissions =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $commissions = [];

}


/*
|--------------------------------------------------------------------------
| DERIVED DATA
|--------------------------------------------------------------------------
*/

$effectiveRate = 0;


if (!empty($commissions)) {

    $latestCommission =
        $commissions[0];


    $effectiveRate =
        (float) (
            $latestCommission[
                'commission_rate'
            ]
            ?? 0
        );

}


$netSalesAfterCommission =
    0;


foreach ($commissions as $commission) {

    $subtotal =
        (float) (
            $commission['subtotal']
            ?? 0
        );


    $commissionAmount =
        (float) (
            $commission['commission_amount']
            ?? 0
        );


    $netSalesAfterCommission +=
        max(
            0,
            $subtotal -
            $commissionAmount
        );

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Commission - ' .
    $vendor['business_name'];

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
        <?= sellerCommissionEscape(
            $pageTitle
        ) ?>
    </title>


    <!-- ============================================================
         GOOGLE FONT
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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         FONT AWESOME
    ============================================================= -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <link
        rel="stylesheet"
        href="css/vendor.css"
    >


    <link
        rel="stylesheet"
        href="css/responsive.css"
    >


    <style>


        /* ==========================================================
           PAGE
        ========================================================== */

        .seller-commission-page {

            margin: 0;

            min-height:
                100vh;

            overflow-x:
                hidden;

            color:
                #14213d;

            background:
                #f6f8fc;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        /* ==========================================================
           MAIN
        ========================================================== */

        .seller-commission-main {

            width:
                calc(
                    100% -
                    var(
                        --seller-sidebar
                    )
                );

            min-height:
                100vh;

            margin-left:
                var(
                    --seller-sidebar
                );

            background:

                radial-gradient(
                    circle at 95% 5%,
                    rgba(
                        37,
                        99,
                        235,
                        .065
                    ),
                    transparent 24%
                ),

                #f6f8fc;

        }


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .seller-commission-topbar {

            height:
                72px;

            padding:
                0 32px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .96
                );

            border-bottom:
                1px solid
                #e8edf5;

        }


        .seller-commission-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-commission-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-commission-avatar {

            width:
                38px;

            height:
                38px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #3b82f6,
                    #6366f1
                );

            border-radius:
                50%;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-commission-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-commission-user small {

            display:
                block;

            margin-top:
                2px;

            color:
                #94a3b8;

            font-size:
                8px;

        }


        /* ==========================================================
           CONTENT
        ========================================================== */

        .seller-commission-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                28px 32px 60px;

        }


        /* ==========================================================
           PAGE HEADER
        ========================================================== */

        .seller-commission-heading {

            margin-bottom:
                22px;

        }


        .seller-commission-eyebrow {

            display:
                block;

            margin-bottom:
                5px;

            color:
                #2563eb;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.5px;

        }


        .seller-commission-heading h1 {

            margin:
                0;

            color:
                #14213d;

            font-size:

                clamp(
                    25px,
                    3vw,
                    33px
                );

            font-weight:
                900;

            letter-spacing:
                -.8px;

        }


        .seller-commission-heading p {

            margin:
                7px 0 0;

            color:
                #7b879c;

            font-size:
                11px;

        }


        /* ==========================================================
           HERO
        ========================================================== */

        .seller-commission-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                175px;

            margin-bottom:
                22px;

            padding:
                31px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                25px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123d8c 48%,
                    #2783ef 100%
                );

            border-radius:
                23px;

            box-shadow:

                0
                17px
                38px
                rgba(
                    18,
                    70,
                    150,
                    .13
                );

        }


        .seller-commission-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                220px;

            height:
                220px;

            top:
                -130px;

            right:
                -45px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .08
                );

        }


        .seller-commission-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                145px;

            height:
                145px;

            right:
                150px;

            bottom:
                -100px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

        }


        .seller-commission-hero-copy {

            position:
                relative;

            z-index:
                2;

        }


        .seller-commission-hero-label {

            display:
                block;

            margin-bottom:
                8px;

            color:
                #a8d4ff;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.3px;

        }


        .seller-commission-hero h2 {

            margin:
                0 0 8px;

            color:
                #ffffff;

            font-family:
                Poppins,
                Inter,
                sans-serif;

            font-size:
                25px;

            font-weight:
                800;

        }


        .seller-commission-hero p {

            max-width:
                625px;

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .77
                );

            font-size:
                10px;

            line-height:
                1.7;

        }


        .seller-commission-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                72px;

            height:
                72px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .13
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .22
                );

            border-radius:
                20px;

            font-size:
                25px;

        }


        /* ==========================================================
           APPROVAL ALERT
        ========================================================== */

        .seller-commission-alert {

            margin-bottom:
                20px;

            padding:
                14px 16px;

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            color:
                #92400e;

            background:
                #fffbeb;

            border:
                1px solid
                #fde68a;

            border-radius:
                12px;

            font-size:
                9px;

            font-weight:
                700;

        }


        /* ==========================================================
           STATS
        ========================================================== */

        .seller-commission-stats {

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
                17px;

            margin-bottom:
                22px;

        }


        .seller-commission-stat {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                140px;

            padding:
                20px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                18px;

            box-shadow:

                0
                9px
                25px
                rgba(
                    40,
                    65,
                    120,
                    .05
                );

        }


        .seller-commission-stat::after {

            content:
                "";

            position:
                absolute;

            width:
                90px;

            height:
                90px;

            right:
                -32px;

            bottom:
                -38px;

            border-radius:
                50%;

            background:
                #eef4ff;

        }


        .seller-commission-stat.green::after {

            background:
                #ecfdf3;

        }


        .seller-commission-stat.orange::after {

            background:
                #fff7ed;

        }


        .seller-commission-stat.purple::after {

            background:
                #f5f3ff;

        }


        .seller-commission-stat-icon {

            position:
                relative;

            z-index:
                2;

            width:
                40px;

            height:
                40px;

            margin-bottom:
                12px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                11px;

            font-size:
                14px;

        }


        .seller-commission-stat.green
        .seller-commission-stat-icon {

            color:
                #16a34a;

            background:
                #ecfdf3;

        }


        .seller-commission-stat.orange
        .seller-commission-stat-icon {

            color:
                #ea580c;

            background:
                #fff7ed;

        }


        .seller-commission-stat.purple
        .seller-commission-stat-icon {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        .seller-commission-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                5px;

            color:
                #7d899d;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .8px;

        }


        .seller-commission-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #14213d;

            font-size:
                22px;

            line-height:
                1.2;

            font-weight:
                900;

        }


        /* ==========================================================
           INFORMATION ROW
        ========================================================== */

        .seller-commission-insights {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                17px;

            margin-bottom:
                22px;

        }


        .seller-commission-insight {

            padding:
                18px;

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                16px;

            box-shadow:

                0
                8px
                22px
                rgba(
                    40,
                    65,
                    120,
                    .04
                );

        }


        .seller-commission-insight-icon {

            width:
                45px;

            height:
                45px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                12px;

            font-size:
                15px;

        }


        .seller-commission-insight small {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #8b99ad;

            font-size:
                7px;

            font-weight:
                800;

            letter-spacing:
                .6px;

        }


        .seller-commission-insight strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                12px;

            font-weight:
                900;

        }


        .seller-commission-insight p {

            margin:
                3px 0 0;

            color:
                #8090a7;

            font-size:
                8px;

            line-height:
                1.55;

        }


        /* ==========================================================
           INFO PANEL
        ========================================================== */

        .seller-commission-info {

            margin-bottom:
                22px;

            padding:
                19px;

            display:
                grid;

            grid-template-columns:
                auto
                minmax(
                    0,
                    1fr
                );

            align-items:
                center;

            gap:
                15px;

            background:

                linear-gradient(
                    135deg,
                    #f8fbff,
                    #edf5ff
                );

            border:
                1px solid
                #dce9f8;

            border-radius:
                16px;

        }


        .seller-commission-info-icon {

            width:
                48px;

            height:
                48px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #ffffff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                13px;

            font-size:
                17px;

        }


        .seller-commission-info strong {

            display:
                block;

            margin-bottom:
                5px;

            color:
                #17345f;

            font-size:
                11px;

            font-weight:
                900;

        }


        .seller-commission-info p {

            margin:
                0;

            color:
                #76889f;

            font-size:
                8px;

            line-height:
                1.65;

        }


        /* ==========================================================
           TABLE PANEL
        ========================================================== */

        .seller-commission-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                21px;

            box-shadow:

                0
                11px
                30px
                rgba(
                    40,
                    65,
                    120,
                    .055
                );

        }


        .seller-commission-panel-header {

            min-height:
                88px;

            padding:
                20px 22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                18px;

            border-bottom:
                1px solid
                #edf1f5;

        }


        .seller-commission-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

        }


        .seller-commission-panel-icon {

            width:
                45px;

            height:
                45px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #3b82f6
                );

            border-radius:
                12px;

            box-shadow:

                0
                8px
                18px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

            font-size:
                15px;

        }


        .seller-commission-panel-title h2 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                16px;

            font-weight:
                900;

        }


        .seller-commission-panel-title p {

            margin:
                0;

            color:
                #8b99ad;

            font-size:
                8px;

        }


        .seller-commission-count {

            min-height:
                32px;

            padding:
                0 11px;

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
                #dbeafe;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        /* ==========================================================
           TABLE
        ========================================================== */

        .seller-commission-table-wrap {

            width:
                100%;

            overflow-x:
                auto;

        }


        .seller-commission-table {

            width:
                100%;

            min-width:
                1000px;

            border-collapse:
                collapse;

        }


        .seller-commission-table thead {

            background:
                #f8fafc;

        }


        .seller-commission-table th {

            height:
                44px;

            padding:
                0 17px;

            color:
                #64748b;

            border-bottom:
                1px solid
                #e6ebf2;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .55px;

            text-align:
                left;

            text-transform:
                uppercase;

        }


        .seller-commission-table td {

            padding:
                15px 17px;

            color:
                #52647d;

            border-bottom:
                1px solid
                #edf1f5;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .seller-commission-table tbody tr:hover {

            background:
                #fbfdff;

        }


        .seller-commission-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /* ==========================================================
           ID
        ========================================================== */

        .seller-commission-id {

            color:
                #2563eb;

            font-size:
                9px;

            font-weight:
                900;

        }


        /* ==========================================================
           ORDER
        ========================================================== */

        .seller-commission-order {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-commission-order-icon {

            width:
                34px;

            height:
                34px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                9px;

            font-size:
                10px;

        }


        .seller-commission-order strong {

            display:
                block;

            margin-bottom:
                2px;

            color:
                #14213d;

            font-size:
                9px;

            font-weight:
                900;

        }


        .seller-commission-order small {

            color:
                #94a3b8;

            font-size:
                7px;

        }


        /* ==========================================================
           RATE
        ========================================================== */

        .seller-commission-rate {

            min-height:
                27px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #7c3aed;

            background:
                #f5f3ff;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                900;

        }


        /* ==========================================================
           MONEY
        ========================================================== */

        .seller-commission-money {

            color:
                #12366a;

            font-size:
                10px;

            font-weight:
                900;

        }


        .seller-commission-money.highlight {

            color:
                #2563eb;

        }


        /* ==========================================================
           STATUS
        ========================================================== */

        .seller-commission-status {

            min-height:
                28px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            border-radius:
                999px;

            font-size:
                7px;

            font-weight:
                900;

        }


        .seller-commission-status::before {

            content:
                "";

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .seller-commission-status.paid {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .seller-commission-status.pending {

            color:
                #b45309;

            background:
                #fffbeb;

        }


        .seller-commission-status.default {

            color:
                #64748b;

            background:
                #f1f5f9;

        }


        /* ==========================================================
           EMPTY
        ========================================================== */

        .seller-commission-empty {

            padding:
                68px 20px;

            text-align:
                center;

        }


        .seller-commission-empty-icon {

            width:
                62px;

            height:
                62px;

            margin:
                0 auto 13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border-radius:
                17px;

            font-size:
                24px;

        }


        .seller-commission-empty h3 {

            margin:
                0 0 6px;

            color:
                #14213d;

            font-size:
                14px;

            font-weight:
                900;

        }


        .seller-commission-empty p {

            margin:
                0;

            color:
                #8492a6;

            font-size:
                9px;

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (
            max-width: 1150px
        ) {

            .seller-commission-stats {

                grid-template-columns:

                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

            }

        }


        @media (
            max-width: 850px
        ) {

            .seller-commission-insights {

                grid-template-columns:
                    1fr;

            }

        }


        @media (
            max-width: 768px
        ) {

            .seller-commission-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-commission-topbar {

                padding:
                    0 20px;

            }


            .seller-commission-content {

                padding:
                    24px 20px 50px;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-commission-user
            > div:last-child {

                display:
                    none;

            }


            .seller-commission-content {

                padding:
                    20px 14px 45px;

            }


            .seller-commission-hero {

                min-height:
                    auto;

                padding:
                    23px;

                align-items:
                    flex-start;

            }


            .seller-commission-hero h2 {

                font-size:
                    20px;

            }


            .seller-commission-hero-icon {

                width:
                    53px;

                height:
                    53px;

                font-size:
                    19px;

            }


            .seller-commission-stats {

                grid-template-columns:
                    1fr;

            }


            .seller-commission-panel-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-commission-info {

                grid-template-columns:
                    1fr;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-commission-page">


<?php

/*
|--------------------------------------------------------------------------
| SHARED SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-commission-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-commission-topbar">


        <span class="seller-commission-topbar-label">

            Seller Center

        </span>


        <div class="seller-commission-user">


            <div class="seller-commission-avatar">

                <?= sellerCommissionEscape(
                    strtoupper(
                        substr(
                            $vendor['name']
                            ?? 'V',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div>


                <strong>

                    <?= sellerCommissionEscape(
                        $vendor['name']
                        ?? 'Vendor'
                    ) ?>

                </strong>


                <small>

                    Vendor

                </small>


            </div>


        </div>


    </header>



    <!-- ===========================================================
         CONTENT
    ============================================================ -->

    <div class="seller-commission-content">


        <!-- =======================================================
             PAGE HEADING
        ======================================================== -->

        <section class="seller-commission-heading">


            <span class="seller-commission-eyebrow">

                FINANCE & COMMISSION

            </span>


            <h1>

                Commission

            </h1>


            <p>

                Track platform commission generated from
                orders under

                <?= sellerCommissionEscape(
                    $vendor['business_name']
                ) ?>.

            </p>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-commission-hero">


            <div class="seller-commission-hero-copy">


                <span class="seller-commission-hero-label">

                    SELLER FINANCE

                </span>


                <h2>

                    See exactly where your commission goes.

                </h2>


                <p>

                    Review commission charged on vendor orders,
                    track pending and paid amounts, and understand
                    how each transaction contributes to your
                    overall store finances.

                </p>


            </div>


            <div class="seller-commission-hero-icon">

                <i class="fa-solid fa-circle-dollar-to-slot"></i>

            </div>


        </section>



        <!-- =======================================================
             APPROVAL ALERT
        ======================================================== -->

        <?php if (
            strtolower(
                trim(
                    (string)
                    $vendor['approval_status']
                )
            ) !== 'approved'
        ): ?>


            <div class="seller-commission-alert">

                <i class="fa-solid fa-triangle-exclamation"></i>


                Your vendor account is currently

                <strong>

                    <?= sellerCommissionEscape(
                        $vendor['approval_status']
                    ) ?>

                </strong>.

                Commission information may be limited until
                your store is approved.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="seller-commission-stats">


            <!-- TOTAL -->

            <article class="seller-commission-stat">


                <div class="seller-commission-stat-icon">

                    <i class="fa-solid fa-coins"></i>

                </div>


                <span class="seller-commission-stat-label">

                    TOTAL COMMISSION

                </span>


                <strong class="seller-commission-stat-value">

                    RM
                    <?= number_format(
                        $summary[
                            'total_commission'
                        ],
                        2
                    ) ?>

                </strong>


            </article>



            <!-- PAID -->

            <article
                class="
                    seller-commission-stat
                    green
                "
            >


                <div class="seller-commission-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <span class="seller-commission-stat-label">

                    PAID COMMISSION

                </span>


                <strong class="seller-commission-stat-value">

                    RM
                    <?= number_format(
                        $summary[
                            'paid_commission'
                        ],
                        2
                    ) ?>

                </strong>


            </article>



            <!-- PENDING -->

            <article
                class="
                    seller-commission-stat
                    orange
                "
            >


                <div class="seller-commission-stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <span class="seller-commission-stat-label">

                    PENDING COMMISSION

                </span>


                <strong class="seller-commission-stat-value">

                    RM
                    <?= number_format(
                        $summary[
                            'pending_commission'
                        ],
                        2
                    ) ?>

                </strong>


            </article>



            <!-- RECORDS -->

            <article
                class="
                    seller-commission-stat
                    purple
                "
            >


                <div class="seller-commission-stat-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>


                <span class="seller-commission-stat-label">

                    COMMISSION RECORDS

                </span>


                <strong class="seller-commission-stat-value">

                    <?= number_format(
                        $summary[
                            'total_records'
                        ]
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =======================================================
             INSIGHTS
        ======================================================== -->

        <section class="seller-commission-insights">


            <!-- RATE -->

            <article class="seller-commission-insight">


                <div class="seller-commission-insight-icon">

                    <i class="fa-solid fa-percent"></i>

                </div>


                <div>


                    <small>

                        LATEST COMMISSION RATE

                    </small>


                    <strong>

                        <?= number_format(
                            $effectiveRate,
                            2
                        ) ?>%

                    </strong>


                    <p>

                        Based on your most recent commission record.

                    </p>


                </div>


            </article>



            <!-- NET -->

            <article class="seller-commission-insight">


                <div class="seller-commission-insight-icon">

                    <i class="fa-solid fa-wallet"></i>

                </div>


                <div>


                    <small>

                        ESTIMATED NET SALES

                    </small>


                    <strong>

                        RM
                        <?= number_format(
                            $netSalesAfterCommission,
                            2
                        ) ?>

                    </strong>


                    <p>

                        Order subtotal minus commission shown
                        in available records.

                    </p>


                </div>


            </article>


        </section>



        <!-- =======================================================
             EXPLANATION
        ======================================================== -->

        <section class="seller-commission-info">


            <div class="seller-commission-info-icon">

                <i class="fa-solid fa-circle-info"></i>

            </div>


            <div>


                <strong>

                    How commission works

                </strong>


                <p>

                    Each eligible vendor order can generate a
                    commission record. The commission amount shown
                    below is calculated using the rate stored for
                    that transaction. Pending records have not yet
                    been marked as paid, while Paid records represent
                    completed commission processing.

                </p>


            </div>


        </section>



        <!-- =======================================================
             HISTORY
        ======================================================== -->

        <section class="seller-commission-panel">


            <!-- ===================================================
                 HEADER
            ==================================================== -->

            <div class="seller-commission-panel-header">


                <div class="seller-commission-panel-title">


                    <div class="seller-commission-panel-icon">

                        <i class="fa-solid fa-money-bill-transfer"></i>

                    </div>


                    <div>


                        <h2>

                            Commission History

                        </h2>


                        <p>

                            Review commission generated from
                            your vendor transactions.

                        </p>


                    </div>


                </div>


                <span class="seller-commission-count">

                    <?= number_format(
                        count(
                            $commissions
                        )
                    ) ?>

                    record<?= count($commissions) !== 1
                        ? 's'
                        : '' ?>

                </span>


            </div>



            <!-- ===================================================
                 EMPTY
            ==================================================== -->

            <?php if (
                empty(
                    $commissions
                )
            ): ?>


                <div class="seller-commission-empty">


                    <div class="seller-commission-empty-icon">

                        <i class="fa-solid fa-coins"></i>

                    </div>


                    <h3>

                        No commission yet

                    </h3>


                    <p>

                        Commission records will appear here
                        when your products generate eligible orders.

                    </p>


                </div>


            <?php else: ?>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="seller-commission-table-wrap">


                    <table class="seller-commission-table">


                        <thead>


                            <tr>


                                <th>

                                    Commission

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

                                    Commission Amount

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


                                <?php

                                $statusClass =
                                    sellerCommissionStatusClass(
                                        $commission[
                                            'status'
                                        ]
                                    );


                                $orderAmount =
                                    (float) (
                                        $commission[
                                            'subtotal'
                                        ]
                                        ?? 0
                                    );

                                ?>


                                <tr>


                                    <!-- ===========================
                                         COMMISSION ID
                                    ============================ -->

                                    <td>


                                        <span class="seller-commission-id">

                                            #<?= (int)
                                                $commission[
                                                    'commission_id'
                                                ] ?>

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         ORDER
                                    ============================ -->

                                    <td>


                                        <div class="seller-commission-order">


                                            <div class="seller-commission-order-icon">

                                                <i class="fa-solid fa-receipt"></i>

                                            </div>


                                            <div>


                                                <strong>

                                                    Order
                                                    #<?= (int)
                                                        $commission[
                                                            'order_id'
                                                        ] ?>

                                                </strong>


                                                <small>

                                                    <?= sellerCommissionEscape(
                                                        sellerCommissionDate(
                                                            $commission[
                                                                'order_date'
                                                            ]
                                                        )
                                                    ) ?>

                                                </small>


                                            </div>


                                        </div>


                                    </td>



                                    <!-- ===========================
                                         VENDOR ORDER
                                    ============================ -->

                                    <td>


                                        <?php if (
                                            !empty(
                                                $commission[
                                                    'vendor_order_id'
                                                ]
                                            )
                                        ): ?>


                                            <span class="seller-commission-id">

                                                #<?= (int)
                                                    $commission[
                                                        'vendor_order_id'
                                                    ] ?>

                                            </span>


                                        <?php else: ?>


                                            —


                                        <?php endif; ?>


                                    </td>



                                    <!-- ===========================
                                         ORDER AMOUNT
                                    ============================ -->

                                    <td>


                                        <span class="seller-commission-money">

                                            RM
                                            <?= number_format(
                                                $orderAmount,
                                                2
                                            ) ?>

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         RATE
                                    ============================ -->

                                    <td>


                                        <span class="seller-commission-rate">

                                            <?= number_format(
                                                (float)
                                                $commission[
                                                    'commission_rate'
                                                ],
                                                2
                                            ) ?>%

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         COMMISSION
                                    ============================ -->

                                    <td>


                                        <span
                                            class="
                                                seller-commission-money
                                                highlight
                                            "
                                        >

                                            RM
                                            <?= number_format(
                                                (float)
                                                $commission[
                                                    'commission_amount'
                                                ],
                                                2
                                            ) ?>

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         STATUS
                                    ============================ -->

                                    <td>


                                        <span
                                            class="
                                                seller-commission-status
                                                <?= sellerCommissionEscape(
                                                    $statusClass
                                                ) ?>
                                            "
                                        >

                                            <?= sellerCommissionEscape(
                                                $commission[
                                                    'status'
                                                ]
                                            ) ?>

                                        </span>


                                    </td>



                                    <!-- ===========================
                                         DATE
                                    ============================ -->

                                    <td>

                                        <?= sellerCommissionEscape(
                                            sellerCommissionDate(
                                                $commission[
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


</main>


</body>


</html>