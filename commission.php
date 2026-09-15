<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER COMMISSION
|--------------------------------------------------------------------------
| File: commission.php
|--------------------------------------------------------------------------
| Seller Center commission & earnings page
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG / DATABASE / SESSION / FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


requireLogin();


$db = getDB();


if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION['user_id']
    ?? 0
);


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
        return number_format(
            (float) $amount,
            2
        );
    }
}


if (!function_exists('commissionDate')) {

    function commissionDate($value): string
    {
        if (empty($value)) {
            return '—';
        }


        $time = strtotime(
            (string) $value
        );


        if (!$time) {
            return '—';
        }


        return date(
            'd M Y, h:i A',
            $time
        );
    }
}


if (!function_exists('commissionStatusClass')) {

    function commissionStatusClass($status): string
    {
        $status = strtolower(
            trim(
                (string) $status
            )
        );


        switch ($status) {

            case 'paid':
                return 'paid';

            case 'pending':
                return 'pending';

            default:
                return 'default';
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
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


    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

    $user = false;
}


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
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
            (string) (
                $user['role']
                ?? ''
            )
        )
    ) !== 'vendor'
) {

    header(
        'Location: ' .
        BASE_URL .
        'dashboard.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
|
| IMPORTANT:
| commission_rate now comes directly from vendors table.
|
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT
            v.vendor_id,
            v.user_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.business_address,
            v.category,
            v.delivery_method,
            v.allow_vendor_delivery,
            v.cod_enabled,
            v.cod_delivery_fee,
            v.commission_rate,
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


    $vendor = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

    $vendor = false;
}


/*
|--------------------------------------------------------------------------
| VENDOR NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$vendor) {

    header(
        'Location: ' .
        BASE_URL .
        'dashboard.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VENDOR DATA
|--------------------------------------------------------------------------
*/

$vendorId = (int) $vendor['vendor_id'];


$currentCommissionRate = (float) (
    $vendor['commission_rate']
    ?? 0
);


$vendorApprovalStatus =
    $vendor['approval_status']
    ?? 'Pending';


/*
|--------------------------------------------------------------------------
| SIDEBAR SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    $vendor['business_name'];

$_SESSION['vendor_approval_status'] =
    $vendorApprovalStatus;


/*
|--------------------------------------------------------------------------
| COMMISSION SUMMARY
|--------------------------------------------------------------------------
|
| Gross sales:
| Sum of vendor order subtotal linked to commission records.
|
| Total commission:
| Sum of commission_amount.
|
| Net earnings:
| Gross sales - commission.
|
|--------------------------------------------------------------------------
*/

$summary = [

    'total_records' => 0,

    'gross_sales' => 0.00,

    'total_commission' => 0.00,

    'paid_commission' => 0.00,

    'pending_commission' => 0.00,

    'net_earnings' => 0.00,

    'paid_records' => 0,

    'pending_records' => 0

];


try {

    $stmt = $db->prepare("
        SELECT

            COUNT(c.commission_id)
                AS total_records,

            COALESCE(
                SUM(
                    COALESCE(
                        vo.subtotal,
                        0
                    )
                ),
                0
            ) AS gross_sales,

            COALESCE(
                SUM(
                    c.commission_amount
                ),
                0
            ) AS total_commission,

            COALESCE(
                SUM(
                    CASE

                        WHEN c.status = 'Paid'
                        THEN c.commission_amount

                        ELSE 0

                    END
                ),
                0
            ) AS paid_commission,

            COALESCE(
                SUM(
                    CASE

                        WHEN c.status = 'Pending'
                        THEN c.commission_amount

                        ELSE 0

                    END
                ),
                0
            ) AS pending_commission,

            COALESCE(
                SUM(
                    CASE

                        WHEN c.status = 'Paid'
                        THEN 1

                        ELSE 0

                    END
                ),
                0
            ) AS paid_records,

            COALESCE(
                SUM(
                    CASE

                        WHEN c.status = 'Pending'
                        THEN 1

                        ELSE 0

                    END
                ),
                0
            ) AS pending_records

        FROM commission c

        LEFT JOIN vendor_orders vo
            ON c.vendor_order_id =
               vo.vendor_order_id

        WHERE c.vendor_id = ?
    ");


    $stmt->execute([
        $vendorId
    ]);


    $row = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if ($row) {

        $summary['total_records'] =
            (int) (
                $row['total_records']
                ?? 0
            );


        $summary['gross_sales'] =
            (float) (
                $row['gross_sales']
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


        $summary['paid_records'] =
            (int) (
                $row['paid_records']
                ?? 0
            );


        $summary['pending_records'] =
            (int) (
                $row['pending_records']
                ?? 0
            );
    }


    $summary['net_earnings'] =
        max(
            0,
            $summary['gross_sales']
            -
            $summary['total_commission']
        );

} catch (Throwable $e) {

    /*
    | Keep defaults if query fails.
    */
}


/*
|--------------------------------------------------------------------------
| COMMISSION HISTORY
|--------------------------------------------------------------------------
*/

$commissions = [];


try {

    $stmt = $db->prepare("
        SELECT

            c.commission_id,
            c.vendor_id,
            c.order_id,
            c.vendor_order_id,
            c.commission_rate,
            c.commission_amount,
            c.status,
            c.created_at,
            c.updated_at,

            vo.subtotal
                AS vendor_subtotal,

            vo.delivery_fee,

            vo.vendor_status,

            vo.tracking_number,

            o.order_date,

            o.order_status,

            p.payment_method,

            p.payment_status

        FROM commission c

        INNER JOIN orders o
            ON c.order_id =
               o.order_id

        LEFT JOIN vendor_orders vo
            ON c.vendor_order_id =
               vo.vendor_order_id

        LEFT JOIN payments p
            ON p.payment_id = (
                SELECT p2.payment_id

                FROM payments p2

                WHERE p2.order_id =
                      c.order_id

                ORDER BY
                    p2.payment_id DESC

                LIMIT 1
            )

        WHERE c.vendor_id = ?

        ORDER BY
            c.created_at DESC,
            c.commission_id DESC
    ");


    $stmt->execute([
        $vendorId
    ]);


    $commissions =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $commissions = [];
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Commission - ' .
    (
        $vendor['business_name']
        ?? 'Seller'
    );


/*
|--------------------------------------------------------------------------
| AVATAR INITIAL
|--------------------------------------------------------------------------
*/

$vendorUserName =
    trim(
        (string) (
            $vendor['name']
            ?? 'Vendor'
        )
    );


$avatarInitial =
    strtoupper(
        substr(
            $vendorUserName,
            0,
            1
        )
    );

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
        <?= commissionEscape(
            $pageTitle
        ) ?>
    </title>


    <!-- GOOGLE FONTS -->

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


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- PROJECT CSS -->

    <link
        rel="stylesheet"
        href="<?= commissionEscape(
            BASE_URL
        ) ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= commissionEscape(
            BASE_URL
        ) ?>css/vendor.css"
    >

    <link
        rel="stylesheet"
        href="<?= commissionEscape(
            BASE_URL
        ) ?>css/responsive.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        :root {
            --commission-sidebar: 260px;
        }


        body.seller-commission-page {

            margin:
                0;

            min-height:
                100vh;

            overflow-x:
                hidden;

            color:
                #17233c;

            background:
                #f5f7fb;

            font-family:
                Inter,
                Arial,
                sans-serif;
        }


        /* ============================================================
           MAIN
        ============================================================ */

        .commission-main {

            width:
                calc(
                    100% -
                    var(
                        --seller-sidebar,
                        260px
                    )
                );

            min-height:
                100vh;

            margin-left:
                var(
                    --seller-sidebar,
                    260px
                );

            background:

                radial-gradient(
                    circle at 96% 4%,
                    rgba(
                        37,
                        99,
                        235,
                        .07
                    ),
                    transparent 23%
                ),

                #f6f8fc;
        }


        /* ============================================================
           TOPBAR
        ============================================================ */

        .commission-topbar {

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
                    .97
                );

            border-bottom:
                1px solid #e7edf5;
        }


        .commission-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;
        }


        .commission-user {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;
        }


        .commission-avatar {

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
                    #2563eb,
                    #6366f1
                );

            border-radius:
                50%;

            font-size:
                12px;

            font-weight:
                900;
        }


        .commission-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

            font-weight:
                800;
        }


        .commission-user small {

            display:
                block;

            margin-top:
                2px;

            color:
                #94a3b8;

            font-size:
                8px;
        }


        /* ============================================================
           CONTENT
        ============================================================ */

        .commission-content {

            width:
                100%;

            max-width:
                1480px;

            margin:
                0 auto;

            padding:
                30px 32px 65px;
        }


        /* ============================================================
           HEADING
        ============================================================ */

        .commission-heading {

            margin-bottom:
                22px;
        }


        .commission-eyebrow {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #2563eb;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                1.5px;
        }


        .commission-heading h1 {

            margin:
                0;

            color:
                #14213d;

            font-family:
                Poppins,
                Inter,
                sans-serif;

            font-size:
                clamp(
                    26px,
                    3vw,
                    34px
                );

            font-weight:
                800;

            letter-spacing:
                -.8px;
        }


        .commission-heading p {

            margin:
                7px 0 0;

            color:
                #7d8ba0;

            font-size:
                11px;

            line-height:
                1.6;
        }


        /* ============================================================
           HERO
        ============================================================ */

        .commission-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                190px;

            margin-bottom:
                22px;

            padding:
                32px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                30px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    115deg,
                    #08265a 0%,
                    #123d8c 50%,
                    #2783ef 100%
                );

            border-radius:
                24px;

            box-shadow:

                0 18px 42px
                rgba(
                    18,
                    70,
                    150,
                    .14
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

            top:
                -150px;

            right:
                -60px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .08
                );

            border-radius:
                50%;
        }


        .commission-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                160px;

            height:
                160px;

            right:
                145px;

            bottom:
                -115px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .05
                );

            border-radius:
                50%;
        }


        .commission-hero-copy {

            position:
                relative;

            z-index:
                2;
        }


        .commission-hero-label {

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
                1.4px;
        }


        .commission-hero h2 {

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


        .commission-hero p {

            max-width:
                720px;

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .78
                );

            font-size:
                10px;

            line-height:
                1.75;
        }


        .commission-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                78px;

            height:
                78px;

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
                21px;

            font-size:
                27px;
        }


        /* ============================================================
           APPROVAL ALERT
        ============================================================ */

        .commission-alert {

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
                1px solid #fde68a;

            border-radius:
                12px;

            font-size:
                9px;

            font-weight:
                700;
        }


        /* ============================================================
           CURRENT RATE CARD
        ============================================================ */

        .commission-rate-panel {

            margin-bottom:
                22px;

            padding:
                20px;

            display:
                grid;

            grid-template-columns:
                auto
                minmax(
                    0,
                    1fr
                )
                auto;

            align-items:
                center;

            gap:
                15px;

            background:
                #ffffff;

            border:
                1px solid #dde7f3;

            border-radius:
                17px;

            box-shadow:

                0 8px 22px
                rgba(
                    40,
                    65,
                    120,
                    .045
                );
        }


        .commission-rate-icon {

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
                #7c3aed;

            background:
                #f5f3ff;

            border-radius:
                13px;

            font-size:
                17px;
        }


        .commission-rate-copy small {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #8b99ad;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .7px;
        }


        .commission-rate-copy strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                12px;

            font-weight:
                900;
        }


        .commission-rate-copy p {

            margin:
                4px 0 0;

            color:
                #8190a5;

            font-size:
                8px;

            line-height:
                1.6;
        }


        .commission-rate-value {

            min-width:
                105px;

            min-height:
                48px;

            padding:
                0 15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #7c3aed;

            background:
                #f5f3ff;

            border:
                1px solid #e5ddff;

            border-radius:
                13px;

            font-size:
                19px;

            font-weight:
                900;
        }


        /* ============================================================
           STATS
        ============================================================ */

        .commission-stats {

            margin-bottom:
                22px;

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
                16px;
        }


        .commission-stat {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                145px;

            padding:
                20px;

            background:
                #ffffff;

            border:
                1px solid #e4eaf2;

            border-radius:
                18px;

            box-shadow:

                0 9px 25px
                rgba(
                    40,
                    65,
                    120,
                    .05
                );
        }


        .commission-stat::after {

            content:
                "";

            position:
                absolute;

            width:
                95px;

            height:
                95px;

            right:
                -34px;

            bottom:
                -40px;

            background:
                #eef4ff;

            border-radius:
                50%;
        }


        .commission-stat.green::after {
            background:
                #ecfdf3;
        }


        .commission-stat.orange::after {
            background:
                #fff7ed;
        }


        .commission-stat.purple::after {
            background:
                #f5f3ff;
        }


        .commission-stat-icon {

            position:
                relative;

            z-index:
                2;

            width:
                41px;

            height:
                41px;

            margin-bottom:
                13px;

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
                15px;
        }


        .commission-stat.green
        .commission-stat-icon {

            color:
                #16a34a;

            background:
                #ecfdf3;
        }


        .commission-stat.orange
        .commission-stat-icon {

            color:
                #ea580c;

            background:
                #fff7ed;
        }


        .commission-stat.purple
        .commission-stat-icon {

            color:
                #7c3aed;

            background:
                #f5f3ff;
        }


        .commission-stat-label {

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


        .commission-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #14213d;

            font-size:
                21px;

            font-weight:
                900;
        }


        /* ============================================================
           FINANCE FLOW
        ============================================================ */

        .commission-flow {

            margin-bottom:
                22px;

            padding:
                20px;

            display:
                grid;

            grid-template-columns:
                1fr auto 1fr auto 1fr;

            align-items:
                center;

            gap:
                14px;

            background:
                #ffffff;

            border:
                1px solid #e4eaf2;

            border-radius:
                18px;

            box-shadow:

                0 8px 23px
                rgba(
                    40,
                    65,
                    120,
                    .045
                );
        }


        .commission-flow-card {

            min-height:
                95px;

            padding:
                15px;

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            background:
                #fbfdff;

            border:
                1px solid #e6ecf4;

            border-radius:
                13px;
        }


        .commission-flow-icon {

            width:
                40px;

            height:
                40px;

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
                11px;
        }


        .commission-flow-card.deduction
        .commission-flow-icon {

            color:
                #dc2626;

            background:
                #fef2f2;
        }


        .commission-flow-card.net
        .commission-flow-icon {

            color:
                #15803d;

            background:
                #ecfdf3;
        }


        .commission-flow-card span {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #8a98aa;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .6px;
        }


        .commission-flow-card strong {

            color:
                #17345f;

            font-size:
                14px;

            font-weight:
                900;
        }


        .commission-flow-symbol {

            color:
                #a2afbf;

            font-size:
                17px;

            font-weight:
                900;
        }


        /* ============================================================
           INFO PANEL
        ============================================================ */

        .commission-info {

            margin-bottom:
                22px;

            padding:
                18px;

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
                14px;

            background:

                linear-gradient(
                    135deg,
                    #f8fbff,
                    #edf5ff
                );

            border:
                1px solid #dce9f8;

            border-radius:
                16px;
        }


        .commission-info-icon {

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
                1px solid #dbeafe;

            border-radius:
                13px;

            font-size:
                17px;
        }


        .commission-info strong {

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


        .commission-info p {

            margin:
                0;

            color:
                #76889f;

            font-size:
                8px;

            line-height:
                1.7;
        }


        /* ============================================================
           HISTORY PANEL
        ============================================================ */

        .commission-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid #e5eaf2;

            border-radius:
                21px;

            box-shadow:

                0 11px 30px
                rgba(
                    40,
                    65,
                    120,
                    .055
                );
        }


        .commission-panel-header {

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
                1px solid #edf1f5;
        }


        .commission-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;
        }


        .commission-panel-icon {

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

                0 8px 18px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

            font-size:
                15px;
        }


        .commission-panel-title h2 {

            margin:
                0 0 4px;

            color:
                #14213d;

            font-size:
                16px;

            font-weight:
                900;
        }


        .commission-panel-title p {

            margin:
                0;

            color:
                #8b99ad;

            font-size:
                8px;
        }


        .commission-record-count {

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
                1px solid #dbeafe;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                900;
        }


        /* ============================================================
           TABLE
        ============================================================ */

        .commission-table-wrap {

            width:
                100%;

            overflow-x:
                auto;
        }


        .commission-table {

            width:
                100%;

            min-width:
                1180px;

            border-collapse:
                collapse;
        }


        .commission-table thead {

            background:
                #f8fafc;
        }


        .commission-table th {

            height:
                46px;

            padding:
                0 16px;

            color:
                #64748b;

            border-bottom:
                1px solid #e6ebf2;

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

            white-space:
                nowrap;
        }


        .commission-table td {

            padding:
                15px 16px;

            color:
                #52647d;

            border-bottom:
                1px solid #edf1f5;

            font-size:
                9px;

            vertical-align:
                middle;
        }


        .commission-table tbody tr:hover {

            background:
                #fbfdff;
        }


        .commission-table tbody tr:last-child td {

            border-bottom:
                0;
        }


        .commission-id {

            color:
                #2563eb;

            font-size:
                9px;

            font-weight:
                900;
        }


        .commission-order {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;
        }


        .commission-order-icon {

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
        }


        .commission-order strong {

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


        .commission-order small {

            color:
                #94a3b8;

            font-size:
                7px;
        }


        .commission-rate-badge {

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


        .commission-money {

            color:
                #12366a;

            font-size:
                10px;

            font-weight:
                900;

            white-space:
                nowrap;
        }


        .commission-money.deduction {

            color:
                #dc2626;
        }


        .commission-money.net {

            color:
                #15803d;
        }


        .commission-status {

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


        .commission-status::before {

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


        .commission-status.paid {

            color:
                #15803d;

            background:
                #ecfdf3;
        }


        .commission-status.pending {

            color:
                #b45309;

            background:
                #fffbeb;
        }


        .commission-status.default {

            color:
                #64748b;

            background:
                #f1f5f9;
        }


        /* ============================================================
           EMPTY
        ============================================================ */

        .commission-empty {

            padding:
                70px 20px;

            text-align:
                center;
        }


        .commission-empty-icon {

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


        .commission-empty h3 {

            margin:
                0 0 6px;

            color:
                #14213d;

            font-size:
                14px;

            font-weight:
                900;
        }


        .commission-empty p {

            margin:
                0;

            color:
                #8492a6;

            font-size:
                9px;
        }


        /* ============================================================
           RESPONSIVE
        ============================================================ */

        @media (
            max-width: 1180px
        ) {

            .commission-stats {

                grid-template-columns:

                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }


            .commission-flow {

                grid-template-columns:
                    1fr;
            }


            .commission-flow-symbol {

                text-align:
                    center;

                transform:
                    rotate(
                        90deg
                    );
            }
        }


        @media (
            max-width: 768px
        ) {

            .commission-main {

                width:
                    100%;

                margin-left:
                    0;
            }


            .commission-topbar {

                padding:
                    0 20px;
            }


            .commission-content {

                padding:
                    24px 20px 50px;
            }


            .commission-rate-panel {

                grid-template-columns:
                    auto
                    minmax(
                        0,
                        1fr
                    );
            }


            .commission-rate-value {

                grid-column:
                    1 / -1;

                width:
                    100%;
            }
        }


        @media (
            max-width: 600px
        ) {

            .commission-content {

                padding:
                    20px 14px 45px;
            }


            .commission-user > div:last-child {

                display:
                    none;
            }


            .commission-hero {

                min-height:
                    auto;

                padding:
                    24px;

                align-items:
                    flex-start;
            }


            .commission-hero h2 {

                font-size:
                    20px;
            }


            .commission-hero-icon {

                width:
                    54px;

                height:
                    54px;

                font-size:
                    19px;
            }


            .commission-stats {

                grid-template-columns:
                    1fr;
            }


            .commission-panel-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .commission-info {

                grid-template-columns:
                    1fr;
            }
        }

    </style>

</head>


<body
    class="
        seller-dashboard-page
        seller-commission-page
    "
>


<?php

/*
|--------------------------------------------------------------------------
| SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/vendor_sidebar.php';

?>


<main class="commission-main">


    <!-- ============================================================
         TOPBAR
    ============================================================= -->

    <header class="commission-topbar">


        <span class="commission-topbar-label">

            Seller Center

        </span>


        <div class="commission-user">


            <div class="commission-avatar">

                <?= commissionEscape(
                    $avatarInitial
                ) ?>

            </div>


            <div>

                <strong>

                    <?= commissionEscape(
                        $vendorUserName
                    ) ?>

                </strong>

                <small>

                    <?= commissionEscape(
                        $vendor[
                            'business_name'
                        ]
                        ?? 'Vendor'
                    ) ?>

                </small>

            </div>


        </div>


    </header>


    <!-- ============================================================
         CONTENT
    ============================================================= -->

    <div class="commission-content">


        <!-- ========================================================
             PAGE HEADING
        ========================================================= -->

        <section class="commission-heading">


            <span class="commission-eyebrow">

                FINANCE & COMMISSION

            </span>


            <h1>

                Commission & Earnings

            </h1>


            <p>

                Track sales, commission deduction and
                estimated earnings for

                <strong>
                    <?= commissionEscape(
                        $vendor[
                            'business_name'
                        ]
                    ) ?>
                </strong>.

            </p>


        </section>


        <!-- ========================================================
             HERO
        ========================================================= -->

        <section class="commission-hero">


            <div class="commission-hero-copy">


                <span class="commission-hero-label">

                    SELLER FINANCE

                </span>


                <h2>

                    Know exactly how much you earn.

                </h2>


                <p>

                    Every vendor order stores the commission rate
                    used for that transaction. Your store's current
                    commission setting is shown separately, while
                    historical records continue using the rate that
                    was saved when each order was created.

                </p>


            </div>


            <div class="commission-hero-icon">

                <i class="fa-solid fa-wallet"></i>

            </div>


        </section>


        <!-- ========================================================
             APPROVAL ALERT
        ========================================================= -->

        <?php if (
            strtolower(
                trim(
                    (string)
                    $vendorApprovalStatus
                )
            ) !== 'approved'
        ): ?>


            <div class="commission-alert">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <span>

                    Your vendor account is currently

                    <strong>

                        <?= commissionEscape(
                            $vendorApprovalStatus
                        ) ?>

                    </strong>.

                    Commission information may be limited
                    until the store is approved.

                </span>

            </div>


        <?php endif; ?>


        <!-- ========================================================
             CURRENT COMMISSION RATE
        ========================================================= -->

        <section class="commission-rate-panel">


            <div class="commission-rate-icon">

                <i class="fa-solid fa-percent"></i>

            </div>


            <div class="commission-rate-copy">

                <small>

                    CURRENT STORE COMMISSION RATE

                </small>

                <strong>

                    Your configured commission rate

                </strong>

                <p>

                    This value comes directly from your vendor
                    profile. New eligible orders use this rate
                    when their commission record is created.

                </p>

            </div>


            <div class="commission-rate-value">

                <?= number_format(
                    $currentCommissionRate,
                    2
                ) ?>%

            </div>


        </section>


        <!-- ========================================================
             STATS
        ========================================================= -->

        <section class="commission-stats">


            <!-- GROSS SALES -->

            <article class="commission-stat">


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-chart-line"></i>

                </div>


                <span class="commission-stat-label">

                    GROSS SALES

                </span>


                <strong class="commission-stat-value">

                    RM
                    <?= commissionMoney(
                        $summary[
                            'gross_sales'
                        ]
                    ) ?>

                </strong>


            </article>


            <!-- COMMISSION -->

            <article
                class="
                    commission-stat
                    orange
                "
            >


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-circle-minus"></i>

                </div>


                <span class="commission-stat-label">

                    TOTAL COMMISSION

                </span>


                <strong class="commission-stat-value">

                    RM
                    <?= commissionMoney(
                        $summary[
                            'total_commission'
                        ]
                    ) ?>

                </strong>


            </article>


            <!-- NET -->

            <article
                class="
                    commission-stat
                    green
                "
            >


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-wallet"></i>

                </div>


                <span class="commission-stat-label">

                    NET EARNINGS

                </span>


                <strong class="commission-stat-value">

                    RM
                    <?= commissionMoney(
                        $summary[
                            'net_earnings'
                        ]
                    ) ?>

                </strong>


            </article>


            <!-- RECORDS -->

            <article
                class="
                    commission-stat
                    purple
                "
            >


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>


                <span class="commission-stat-label">

                    COMMISSION RECORDS

                </span>


                <strong class="commission-stat-value">

                    <?= number_format(
                        $summary[
                            'total_records'
                        ]
                    ) ?>

                </strong>


            </article>


        </section>


        <!-- ========================================================
             FINANCE FLOW
        ========================================================= -->

        <section class="commission-flow">


            <article class="commission-flow-card">


                <div class="commission-flow-icon">

                    <i class="fa-solid fa-bag-shopping"></i>

                </div>


                <div>

                    <span>

                        SALES SUBTOTAL

                    </span>

                    <strong>

                        RM
                        <?= commissionMoney(
                            $summary[
                                'gross_sales'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


            <div class="commission-flow-symbol">

                −

            </div>


            <article
                class="
                    commission-flow-card
                    deduction
                "
            >


                <div class="commission-flow-icon">

                    <i class="fa-solid fa-percent"></i>

                </div>


                <div>

                    <span>

                        COMMISSION DEDUCTION

                    </span>

                    <strong>

                        RM
                        <?= commissionMoney(
                            $summary[
                                'total_commission'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


            <div class="commission-flow-symbol">

                =

            </div>


            <article
                class="
                    commission-flow-card
                    net
                "
            >


                <div class="commission-flow-icon">

                    <i class="fa-solid fa-sack-dollar"></i>

                </div>


                <div>

                    <span>

                        ESTIMATED NET EARNINGS

                    </span>

                    <strong>

                        RM
                        <?= commissionMoney(
                            $summary[
                                'net_earnings'
                            ]
                        ) ?>

                    </strong>

                </div>


            </article>


        </section>


        <!-- ========================================================
             COMMISSION STATUS SUMMARY
        ========================================================= -->

        <section class="commission-stats">


            <article
                class="
                    commission-stat
                    green
                "
            >


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <span class="commission-stat-label">

                    PAID COMMISSION

                </span>


                <strong class="commission-stat-value">

                    RM
                    <?= commissionMoney(
                        $summary[
                            'paid_commission'
                        ]
                    ) ?>

                </strong>


            </article>


            <article
                class="
                    commission-stat
                    orange
                "
            >


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <span class="commission-stat-label">

                    PENDING COMMISSION

                </span>


                <strong class="commission-stat-value">

                    RM
                    <?= commissionMoney(
                        $summary[
                            'pending_commission'
                        ]
                    ) ?>

                </strong>


            </article>


            <article class="commission-stat">


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-check-double"></i>

                </div>


                <span class="commission-stat-label">

                    PAID RECORDS

                </span>


                <strong class="commission-stat-value">

                    <?= number_format(
                        $summary[
                            'paid_records'
                        ]
                    ) ?>

                </strong>


            </article>


            <article
                class="
                    commission-stat
                    purple
                "
            >


                <div class="commission-stat-icon">

                    <i class="fa-solid fa-hourglass-half"></i>

                </div>


                <span class="commission-stat-label">

                    PENDING RECORDS

                </span>


                <strong class="commission-stat-value">

                    <?= number_format(
                        $summary[
                            'pending_records'
                        ]
                    ) ?>

                </strong>


            </article>


        </section>


        <!-- ========================================================
             INFORMATION
        ========================================================= -->

        <section class="commission-info">


            <div class="commission-info-icon">

                <i class="fa-solid fa-circle-info"></i>

            </div>


            <div>

                <strong>

                    How commission works

                </strong>


                <p>

                    Your store has a current commission rate of

                    <strong>

                        <?= number_format(
                            $currentCommissionRate,
                            2
                        ) ?>%

                    </strong>.

                    When an eligible order is created, that rate is
                    stored inside the commission record. Therefore,
                    changing your commission rate later does not change
                    the historical rate for previous transactions.
                    Net earnings shown here are calculated from the
                    vendor product subtotal minus platform commission.
                    Delivery fees are not included in the product sales
                    subtotal calculation on this page.

                </p>

            </div>


        </section>


        <!-- ========================================================
             HISTORY
        ========================================================= -->

        <section class="commission-panel">


            <div class="commission-panel-header">


                <div class="commission-panel-title">


                    <div class="commission-panel-icon">

                        <i class="fa-solid fa-money-bill-transfer"></i>

                    </div>


                    <div>

                        <h2>

                            Commission History

                        </h2>

                        <p>

                            Detailed commission and earnings for
                            each vendor order.

                        </p>

                    </div>


                </div>


                <span class="commission-record-count">

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


            <?php if (
                empty(
                    $commissions
                )
            ): ?>


                <div class="commission-empty">


                    <div class="commission-empty-icon">

                        <i class="fa-solid fa-coins"></i>

                    </div>


                    <h3>

                        No commission records yet

                    </h3>


                    <p>

                        Commission records will appear here
                        when eligible customer orders are created.

                    </p>


                </div>


            <?php else: ?>


                <div class="commission-table-wrap">


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
                                    Vendor Order
                                </th>

                                <th>
                                    Sales Subtotal
                                </th>

                                <th>
                                    Rate
                                </th>

                                <th>
                                    Commission
                                </th>

                                <th>
                                    Net Earnings
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $commissions
                                as $commission
                            ): ?>


                                <?php

                                $commissionStatus =
                                    $commission[
                                        'status'
                                    ]
                                    ?? 'Pending';


                                $statusClass =
                                    commissionStatusClass(
                                        $commissionStatus
                                    );


                                $salesSubtotal =
                                    (float) (
                                        $commission[
                                            'vendor_subtotal'
                                        ]
                                        ?? 0
                                    );


                                $commissionAmount =
                                    (float) (
                                        $commission[
                                            'commission_amount'
                                        ]
                                        ?? 0
                                    );


                                $netEarning =
                                    max(
                                        0,
                                        $salesSubtotal
                                        -
                                        $commissionAmount
                                    );


                                $recordRate =
                                    (float) (
                                        $commission[
                                            'commission_rate'
                                        ]
                                        ?? 0
                                    );


                                $paymentMethod =
                                    $commission[
                                        'payment_method'
                                    ]
                                    ?? '—';


                                $paymentStatus =
                                    $commission[
                                        'payment_status'
                                    ]
                                    ?? 'Pending';

                                ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <span class="commission-id">

                                            #<?= (int)
                                                $commission[
                                                    'commission_id'
                                                ] ?>

                                        </span>

                                    </td>


                                    <!-- ORDER -->

                                    <td>

                                        <div class="commission-order">


                                            <div class="commission-order-icon">

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

                                                    <?= commissionEscape(
                                                        commissionDate(
                                                            $commission[
                                                                'order_date'
                                                            ]
                                                        )
                                                    ) ?>

                                                </small>

                                            </div>


                                        </div>

                                    </td>


                                    <!-- VENDOR ORDER -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $commission[
                                                    'vendor_order_id'
                                                ]
                                            )
                                        ): ?>

                                            <span class="commission-id">

                                                #<?= (int)
                                                    $commission[
                                                        'vendor_order_id'
                                                    ] ?>

                                            </span>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <!-- SALES -->

                                    <td>

                                        <span class="commission-money">

                                            RM
                                            <?= commissionMoney(
                                                $salesSubtotal
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- RATE -->

                                    <td>

                                        <span class="commission-rate-badge">

                                            <?= number_format(
                                                $recordRate,
                                                2
                                            ) ?>%

                                        </span>

                                    </td>


                                    <!-- COMMISSION -->

                                    <td>

                                        <span
                                            class="
                                                commission-money
                                                deduction
                                            "
                                        >

                                            − RM
                                            <?= commissionMoney(
                                                $commissionAmount
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- NET -->

                                    <td>

                                        <span
                                            class="
                                                commission-money
                                                net
                                            "
                                        >

                                            RM
                                            <?= commissionMoney(
                                                $netEarning
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td>

                                        <strong
                                            style="
                                                display:block;
                                                color:#334155;
                                                font-size:9px;
                                            "
                                        >

                                            <?= commissionEscape(
                                                $paymentMethod
                                            ) ?>

                                        </strong>

                                        <small
                                            style="
                                                display:block;
                                                margin-top:3px;
                                                color:#94a3b8;
                                                font-size:7px;
                                            "
                                        >

                                            <?= commissionEscape(
                                                $paymentStatus
                                            ) ?>

                                        </small>

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
                                                $commissionStatus
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?= commissionEscape(
                                            commissionDate(
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