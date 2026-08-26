<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER ORDERS
|--------------------------------------------------------------------------
| File:
| seller/orders.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/session.php';


/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/functions.php';


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

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ../index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| VENDOR CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    strtolower(
        (string) $_SESSION['role']
    ) !== 'vendor'
) {

    header(
        'Location: ../dashboard.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (
    !isset($db) ||
    !($db instanceof PDO)
) {

    $db =
        getDB();

}


if (!($db instanceof PDO)) {

    die(
        'Database connection is not available.'
    );

}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('sellerOrderEscape')) {

    function sellerOrderEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


if (!function_exists('sellerOrderStatusClass')) {

    function sellerOrderStatusClass($status): string
    {
        switch (
            strtolower(
                trim(
                    (string) $status
                )
            )
        ) {

            case 'pending':
                return 'pending';

            case 'processing':
                return 'processing';

            case 'ready':
                return 'ready';

            case 'shipped':
                return 'shipped';

            case 'completed':
                return 'completed';

            case 'cancelled':
                return 'cancelled';

            default:
                return 'default';

        }
    }

}


/*
|--------------------------------------------------------------------------
| VENDOR
|--------------------------------------------------------------------------
|
| Get full vendor/user data so shared seller sidebar matches Dashboard.
|
|--------------------------------------------------------------------------
*/

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


if (!$vendor) {

    header(
        'Location: setup_profile.php'
    );

    exit;

}


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
| ALLOWED STATUSES
|--------------------------------------------------------------------------
*/

$allowedStatuses = [

    'Pending',
    'Processing',
    'Ready',
    'Shipped',
    'Completed',
    'Cancelled'

];


/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $vendorOrderId =
        isset($_POST['vendor_order_id'])
            ? (int) $_POST['vendor_order_id']
            : 0;


    $newStatus =
        trim(
            $_POST['vendor_status']
            ?? ''
        );


    if (
        $vendorOrderId > 0 &&
        in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {


        /*
        |--------------------------------------------------------------------------
        | VERIFY OWNERSHIP
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    vendor_order_id

                FROM vendor_orders

                WHERE vendor_order_id = ?

                AND vendor_id = ?

                LIMIT 1
            ");


        $stmt->execute([

            $vendorOrderId,

            $vendorId

        ]);


        $exists =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($exists) {


            /*
            |--------------------------------------------------------------------------
            | COMPLETED
            |--------------------------------------------------------------------------
            */

            if ($newStatus === 'Completed') {

                $stmt =
                    $db->prepare("
                        UPDATE vendor_orders

                        SET
                            vendor_status = ?,
                            completed_at = NOW()

                        WHERE vendor_order_id = ?

                        AND vendor_id = ?
                    ");


                $stmt->execute([

                    $newStatus,

                    $vendorOrderId,

                    $vendorId

                ]);

            }


            /*
            |--------------------------------------------------------------------------
            | OTHER STATUS
            |--------------------------------------------------------------------------
            */

            else {

                $stmt =
                    $db->prepare("
                        UPDATE vendor_orders

                        SET
                            vendor_status = ?,
                            completed_at = NULL

                        WHERE vendor_order_id = ?

                        AND vendor_id = ?
                    ");


                $stmt->execute([

                    $newStatus,

                    $vendorOrderId,

                    $vendorId

                ]);

            }


            header(
                'Location: orders.php?success=status_updated'
            );

            exit;

        }

    }


    header(
        'Location: orders.php?error=invalid_status'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| UPDATE TRACKING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_tracking'])
) {

    $vendorOrderId =
        isset($_POST['vendor_order_id'])
            ? (int) $_POST['vendor_order_id']
            : 0;


    $trackingNumber =
        trim(
            $_POST['tracking_number']
            ?? ''
        );


    if ($vendorOrderId > 0) {

        $stmt =
            $db->prepare("
                UPDATE vendor_orders

                SET tracking_number = ?

                WHERE vendor_order_id = ?

                AND vendor_id = ?
            ");


        $stmt->execute([

            $trackingNumber,

            $vendorOrderId,

            $vendorId

        ]);


        header(
            'Location: orders.php?success=tracking_updated'
        );

        exit;

    }


    header(
        'Location: orders.php?error=invalid_order'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$statusFilter =
    trim(
        $_GET['status']
        ?? ''
    );


if (
    $statusFilter !== '' &&
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $statusFilter = '';

}


/*
|--------------------------------------------------------------------------
| ORDER STATISTICS
|--------------------------------------------------------------------------
*/

$orderStats = [

    'total'      => 0,
    'pending'    => 0,
    'processing' => 0,
    'ready'      => 0,
    'shipped'    => 0,
    'completed'  => 0,
    'cancelled'  => 0

];


try {

    $stmt =
        $db->prepare("
            SELECT

                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN vendor_status = 'Pending'
                        THEN 1 ELSE 0
                    END
                ) AS pending,

                SUM(
                    CASE
                        WHEN vendor_status = 'Processing'
                        THEN 1 ELSE 0
                    END
                ) AS processing,

                SUM(
                    CASE
                        WHEN vendor_status = 'Ready'
                        THEN 1 ELSE 0
                    END
                ) AS ready,

                SUM(
                    CASE
                        WHEN vendor_status = 'Shipped'
                        THEN 1 ELSE 0
                    END
                ) AS shipped,

                SUM(
                    CASE
                        WHEN vendor_status = 'Completed'
                        THEN 1 ELSE 0
                    END
                ) AS completed,

                SUM(
                    CASE
                        WHEN vendor_status = 'Cancelled'
                        THEN 1 ELSE 0
                    END
                ) AS cancelled

            FROM vendor_orders

            WHERE vendor_id = ?
        ");


    $stmt->execute([
        $vendorId
    ]);


    $stats =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if ($stats) {

        foreach (
            $orderStats
            as $key => $value
        ) {

            $orderStats[$key] =
                (int) (
                    $stats[$key]
                    ?? 0
                );

        }

    }

}

catch (Throwable $e) {

}


/*
|--------------------------------------------------------------------------
| GET ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];


$sql = "
    SELECT

        vo.vendor_order_id,
        vo.order_id,
        vo.subtotal,
        vo.delivery_fee,
        vo.vendor_status,
        vo.tracking_number,
        vo.created_at,
        vo.completed_at,

        o.order_date,
        o.delivery_method,
        o.delivery_address,

        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone

    FROM vendor_orders vo

    INNER JOIN orders o
        ON vo.order_id = o.order_id

    INNER JOIN users u
        ON o.customer_id = u.user_id

    WHERE vo.vendor_id = ?
";


$params = [
    $vendorId
];


if ($statusFilter !== '') {

    $sql .= "
        AND vo.vendor_status = ?
    ";


    $params[] =
        $statusFilter;

}


$sql .= "
    ORDER BY vo.created_at DESC
";


try {

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $orders =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $orders = [];

}


/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS
|--------------------------------------------------------------------------
*/

foreach ($orders as &$order) {

    $order['items'] = [];


    try {

        $stmt =
            $db->prepare("
                SELECT

                    od.order_detail_id,
                    od.product_id,
                    od.quantity,
                    od.unit_price,
                    od.subtotal,

                    p.product_name,
                    p.image

                FROM order_details od

                INNER JOIN products p
                    ON od.product_id = p.product_id

                WHERE od.order_id = ?

                AND p.vendor_id = ?

                ORDER BY
                    od.order_detail_id ASC
            ");


        $stmt->execute([

            $order['order_id'],

            $vendorId

        ]);


        $order['items'] =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    }

    catch (Throwable $e) {

        $order['items'] = [];

    }

}


unset($order);


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Orders | Seller | HochipoHub';

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
        <?= sellerOrderEscape(
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
        href="../css/style.css"
    >


    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >


    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>


        /* ==========================================================
           PAGE
        ========================================================== */

        .seller-orders-page {

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

        .seller-orders-main {

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
                        .06
                    ),
                    transparent 24%
                ),

                #f6f8fc;

        }


        /* ==========================================================
           TOPBAR
        ========================================================== */

        .seller-orders-topbar {

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
                    .95
                );

            border-bottom:
                1px solid
                #e8edf5;

        }


        .seller-orders-topbar-label {

            color:
                #94a3b8;

            font-size:
                11px;

            font-weight:
                700;

        }


        .seller-orders-user {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

        }


        .seller-orders-avatar {

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


        .seller-orders-user strong {

            display:
                block;

            color:
                #14213d;

            font-size:
                11px;

        }


        .seller-orders-user small {

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

        .seller-orders-content {

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
           HEADER
        ========================================================== */

        .seller-orders-header {

            margin-bottom:
                22px;

        }


        .seller-orders-eyebrow {

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


        .seller-orders-header h1 {

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


        .seller-orders-header p {

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

        .seller-orders-hero {

            position:
                relative;

            overflow:
                hidden;

            min-height:
                165px;

            margin-bottom:
                22px;

            padding:
                30px;

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


        .seller-orders-hero::before {

            content: "";

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


        .seller-orders-hero::after {

            content: "";

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


        .seller-orders-hero-copy {

            position:
                relative;

            z-index:
                2;

        }


        .seller-orders-hero-label {

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


        .seller-orders-hero h2 {

            margin:
                0 0 8px;

            color:
                #ffffff;

            font-family:
                Poppins,
                Inter,
                sans-serif;

            font-size:
                24px;

            font-weight:
                800;

        }


        .seller-orders-hero p {

            max-width:
                600px;

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .76
                );

            font-size:
                10px;

            line-height:
                1.7;

        }


        .seller-orders-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                70px;

            height:
                70px;

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
                19px;

            font-size:
                24px;

        }


        /* ==========================================================
           ALERT
        ========================================================== */

        .seller-orders-alert {

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

            border-radius:
                12px;

            font-size:
                9px;

            font-weight:
                700;

        }


        .seller-orders-alert.success {

            color:
                #166534;

            background:
                #f0fdf4;

            border:
                1px solid
                #bbf7d0;

        }


        .seller-orders-alert.error {

            color:
                #b91c1c;

            background:
                #fef2f2;

            border:
                1px solid
                #fecaca;

        }


        /* ==========================================================
           STATS
        ========================================================== */

        .seller-orders-stats {

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


        .seller-order-stat {

            position:
                relative;

            min-height:
                128px;

            overflow:
                hidden;

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


        .seller-order-stat::after {

            content: "";

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


        .seller-order-stat.orange::after {

            background:
                #fff7ed;

        }


        .seller-order-stat.green::after {

            background:
                #ecfdf3;

        }


        .seller-order-stat.purple::after {

            background:
                #f5f3ff;

        }


        .seller-order-stat-icon {

            position:
                relative;

            z-index:
                2;

            width:
                39px;

            height:
                39px;

            margin-bottom:
                11px;

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


        .seller-order-stat.orange
        .seller-order-stat-icon {

            color:
                #ea580c;

            background:
                #fff7ed;

        }


        .seller-order-stat.green
        .seller-order-stat-icon {

            color:
                #16a34a;

            background:
                #ecfdf3;

        }


        .seller-order-stat.purple
        .seller-order-stat-icon {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        .seller-order-stat span {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                4px;

            color:
                #7d899d;

            font-size:
                7px;

            font-weight:
                900;

            letter-spacing:
                .7px;

        }


        .seller-order-stat strong {

            position:
                relative;

            z-index:
                2;

            color:
                #14213d;

            font-size:
                25px;

            font-weight:
                900;

        }


        /* ==========================================================
           FILTER
        ========================================================== */

        .seller-orders-filter {

            margin-bottom:
                22px;

            padding:
                14px;

            display:
                flex;

            flex-wrap:
                wrap;

            align-items:
                center;

            gap:
                7px;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                17px;

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


        .seller-orders-filter a {

            min-height:
                34px;

            padding:
                0 11px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #64748b;

            background:
                #f8fafc;

            border:
                1px solid
                #edf1f5;

            border-radius:
                9px;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        .seller-orders-filter a:hover {

            color:
                #2563eb;

            background:
                #eff6ff;

        }


        .seller-orders-filter a.active {

            color:
                #ffffff;

            background:
                #2563eb;

            border-color:
                #2563eb;

        }


        /* ==========================================================
           ORDERS LIST
        ========================================================== */

        .seller-orders-list {

            display:
                flex;

            flex-direction:
                column;

            gap:
                20px;

        }


        /* ==========================================================
           ORDER CARD
        ========================================================== */

        .seller-order-card {

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


        /* ==========================================================
           ORDER HEADER
        ========================================================== */

        .seller-order-header {

            min-height:
                86px;

            padding:
                19px 22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                18px;

            background:

                linear-gradient(
                    135deg,
                    #fbfdff,
                    #f4f8ff
                );

            border-bottom:
                1px solid
                #e9eef5;

        }


        .seller-order-number {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

        }


        .seller-order-number-icon {

            width:
                44px;

            height:
                44px;

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
                #eaf2ff;

            border-radius:
                12px;

            font-size:
                15px;

        }


        .seller-order-number strong {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #14213d;

            font-size:
                13px;

            font-weight:
                900;

        }


        .seller-order-number small {

            color:
                #8b99ad;

            font-size:
                8px;

        }


        /* ==========================================================
           STATUS
        ========================================================== */

        .seller-order-status {

            min-height:
                29px;

            padding:
                0 10px;

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

            text-transform:
                uppercase;

        }


        .seller-order-status::before {

            content: "";

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .seller-order-status.pending {

            color:
                #b45309;

            background:
                #fffbeb;

        }


        .seller-order-status.processing {

            color:
                #2563eb;

            background:
                #eff6ff;

        }


        .seller-order-status.ready {

            color:
                #7c3aed;

            background:
                #f5f3ff;

        }


        .seller-order-status.shipped {

            color:
                #0369a1;

            background:
                #f0f9ff;

        }


        .seller-order-status.completed {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .seller-order-status.cancelled {

            color:
                #b91c1c;

            background:
                #fef2f2;

        }


        .seller-order-status.default {

            color:
                #64748b;

            background:
                #f1f5f9;

        }


        /* ==========================================================
           ORDER CONTENT
        ========================================================== */

        .seller-order-body {

            padding:
                22px;

        }


        .seller-order-info-grid {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                14px;

            margin-bottom:
                20px;

        }


        .seller-order-info-card {

            padding:
                16px;

            background:
                #f8fafc;

            border:
                1px solid
                #edf1f5;

            border-radius:
                14px;

        }


        .seller-order-info-title {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

            margin-bottom:
                10px;

            color:
                #2563eb;

            font-size:
                8px;

            font-weight:
                900;

            letter-spacing:
                .5px;

        }


        .seller-order-info-card strong {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #233653;

            font-size:
                10px;

        }


        .seller-order-info-card p {

            margin:
                3px 0;

            color:
                #718198;

            font-size:
                8px;

            line-height:
                1.6;

        }


        /* ==========================================================
           ITEMS
        ========================================================== */

        .seller-order-items {

            margin-bottom:
                19px;

        }


        .seller-order-items-heading {

            margin:
                0 0 10px;

            color:
                #14213d;

            font-size:
                11px;

            font-weight:
                900;

        }


        .seller-order-item {

            min-height:
                78px;

            padding:
                10px;

            display:
                grid;

            grid-template-columns:
                58px
                minmax(
                    0,
                    1fr
                )
                auto;

            align-items:
                center;

            gap:
                12px;

            border-top:
                1px solid
                #edf1f5;

        }


        .seller-order-item:first-of-type {

            border-top:
                0;

        }


        .seller-order-item-image {

            width:
                58px;

            height:
                58px;

            overflow:
                hidden;

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

            border:
                1px solid
                #dbeafe;

            border-radius:
                11px;

            font-size:
                18px;

        }


        .seller-order-item-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                contain;

            object-position:
                center;

        }


        .seller-order-item-name strong {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #14213d;

            font-size:
                10px;

            font-weight:
                900;

        }


        .seller-order-item-name span {

            color:
                #8492a6;

            font-size:
                8px;

        }


        .seller-order-item-total {

            color:
                #16396e;

            font-size:
                10px;

            font-weight:
                900;

            white-space:
                nowrap;

        }


        /* ==========================================================
           TOTAL SUMMARY
        ========================================================== */

        .seller-order-summary {

            margin-bottom:
                19px;

            padding:
                15px 16px;

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                12px;

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
                14px;

        }


        .seller-order-summary-item span {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #8493aa;

            font-size:
                7px;

            font-weight:
                800;

            letter-spacing:
                .5px;

        }


        .seller-order-summary-item strong {

            color:
                #12366a;

            font-size:
                13px;

            font-weight:
                900;

        }


        /* ==========================================================
           ACTION AREA
        ========================================================== */

        .seller-order-actions {

            display:
                grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                14px;

        }


        .seller-order-action-card {

            padding:
                16px;

            background:
                #fbfcfe;

            border:
                1px solid
                #e8edf4;

            border-radius:
                14px;

        }


        .seller-order-action-card label {

            display:
                block;

            margin-bottom:
                7px;

            color:
                #334155;

            font-size:
                8px;

            font-weight:
                900;

        }


        .seller-order-action-row {

            display:
                grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                auto;

            gap:
                8px;

        }


        .seller-order-action-row select,
        .seller-order-action-row input {

            width:
                100%;

            height:
                39px;

            padding:
                0 10px;

            outline:
                none;

            color:
                #334155;

            background:
                #ffffff;

            border:
                1px solid
                #dce5ef;

            border-radius:
                9px;

            font-family:
                inherit;

            font-size:
                8px;

        }


        .seller-order-action-row select:focus,
        .seller-order-action-row input:focus {

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
                    .07
                );

        }


        .seller-order-action-row button {

            min-height:
                39px;

            padding:
                0 12px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                5px;

            border-radius:
                9px;

            font-family:
                inherit;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .seller-order-update-btn {

            color:
                #ffffff;

            background:
                #2563eb;

            border:
                0;

        }


        .seller-order-track-btn {

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

        }


        /* ==========================================================
           EMPTY
        ========================================================== */

        .seller-orders-empty {

            padding:
                72px 25px;

            text-align:
                center;

            background:
                #ffffff;

            border:
                1px solid
                #e5eaf2;

            border-radius:
                21px;

            box-shadow:

                0
                10px
                28px
                rgba(
                    40,
                    65,
                    120,
                    .05
                );

        }


        .seller-orders-empty-icon {

            width:
                64px;

            height:
                64px;

            margin:
                0 auto 14px;

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
                18px;

            font-size:
                25px;

        }


        .seller-orders-empty h3 {

            margin:
                0 0 6px;

            color:
                #14213d;

            font-size:
                15px;

            font-weight:
                900;

        }


        .seller-orders-empty p {

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

            .seller-orders-stats {

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
            max-width: 900px
        ) {

            .seller-order-info-grid,
            .seller-order-actions {

                grid-template-columns:
                    1fr;

            }

        }


        @media (
            max-width: 768px
        ) {

            .seller-orders-main {

                width:
                    100%;

                margin-left:
                    0;

            }


            .seller-orders-topbar {

                padding:
                    0 20px;

            }


            .seller-orders-content {

                padding:
                    24px 20px 50px;

            }

        }


        @media (
            max-width: 600px
        ) {

            .seller-orders-user
            > div:last-child {

                display:
                    none;

            }


            .seller-orders-content {

                padding:
                    20px 14px 45px;

            }


            .seller-orders-hero {

                min-height:
                    auto;

                padding:
                    23px;

                align-items:
                    flex-start;

            }


            .seller-orders-hero h2 {

                font-size:
                    20px;

            }


            .seller-orders-hero-icon {

                width:
                    53px;

                height:
                    53px;

                font-size:
                    19px;

            }


            .seller-orders-stats {

                grid-template-columns:
                    1fr;

            }


            .seller-order-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .seller-order-body {

                padding:
                    15px;

            }


            .seller-order-item {

                grid-template-columns:
                    50px
                    minmax(
                        0,
                        1fr
                    );

            }


            .seller-order-item-image {

                width:
                    50px;

                height:
                    50px;

            }


            .seller-order-item-total {

                grid-column:
                    2;

            }


            .seller-order-summary {

                grid-template-columns:
                    1fr;

            }


            .seller-order-action-row {

                grid-template-columns:
                    1fr;

            }


            .seller-order-action-row button {

                width:
                    100%;

            }

        }


    </style>


</head>


<body class="seller-dashboard-page seller-orders-page">


<?php

/*
|--------------------------------------------------------------------------
| SHARED SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<!-- ===============================================================
     MAIN
================================================================ -->

<main class="seller-orders-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-orders-topbar">


        <span class="seller-orders-topbar-label">

            Seller Center

        </span>


        <div class="seller-orders-user">


            <div class="seller-orders-avatar">

                <?= sellerOrderEscape(
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

                    <?= sellerOrderEscape(
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

    <div class="seller-orders-content">


        <!-- =======================================================
             PAGE HEADER
        ======================================================== -->

        <section class="seller-orders-header">


            <span class="seller-orders-eyebrow">

                ORDER MANAGEMENT

            </span>


            <h1>

                My Orders

            </h1>


            <p>

                Manage customer orders containing products
                from <?= sellerOrderEscape(
                    $vendor['business_name']
                ) ?>.

            </p>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-orders-hero">


            <div class="seller-orders-hero-copy">


                <span class="seller-orders-hero-label">

                    SELLER WORKSPACE

                </span>


                <h2>

                    Keep every order moving.

                </h2>


                <p>

                    Review purchased items, customer delivery
                    details, update fulfilment progress and save
                    tracking information from one place.

                </p>


            </div>


            <div class="seller-orders-hero-icon">

                <i class="fa-solid fa-bag-shopping"></i>

            </div>


        </section>



        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if (
            isset(
                $_GET['success']
            )
        ): ?>


            <div
                class="
                    seller-orders-alert
                    success
                "
            >

                <i class="fa-solid fa-circle-check"></i>


                <?php if (
                    $_GET['success']
                    === 'status_updated'
                ): ?>

                    Order status updated successfully.

                <?php elseif (
                    $_GET['success']
                    === 'tracking_updated'
                ): ?>

                    Tracking number updated successfully.

                <?php else: ?>

                    Order updated successfully.

                <?php endif; ?>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (
            isset(
                $_GET['error']
            )
        ): ?>


            <div
                class="
                    seller-orders-alert
                    error
                "
            >

                <i class="fa-solid fa-triangle-exclamation"></i>

                Unable to process the requested action.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             STATISTICS
        ======================================================== -->

        <section class="seller-orders-stats">


            <article class="seller-order-stat">


                <div class="seller-order-stat-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>


                <span>
                    TOTAL ORDERS
                </span>


                <strong>

                    <?= number_format(
                        $orderStats['total']
                    ) ?>

                </strong>


            </article>



            <article
                class="
                    seller-order-stat
                    orange
                "
            >


                <div class="seller-order-stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <span>
                    PENDING
                </span>


                <strong>

                    <?= number_format(
                        $orderStats['pending']
                    ) ?>

                </strong>


            </article>



            <article
                class="
                    seller-order-stat
                    purple
                "
            >


                <div class="seller-order-stat-icon">

                    <i class="fa-solid fa-truck-fast"></i>

                </div>


                <span>
                    IN PROGRESS
                </span>


                <strong>

                    <?= number_format(
                        $orderStats['processing'] +
                        $orderStats['ready'] +
                        $orderStats['shipped']
                    ) ?>

                </strong>


            </article>



            <article
                class="
                    seller-order-stat
                    green
                "
            >


                <div class="seller-order-stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <span>
                    COMPLETED
                </span>


                <strong>

                    <?= number_format(
                        $orderStats['completed']
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =======================================================
             FILTER
        ======================================================== -->

        <nav class="seller-orders-filter">


            <a
                href="orders.php"
                class="<?= $statusFilter === ''
                    ? 'active'
                    : '' ?>"
            >

                All

            </a>


            <?php foreach (
                $allowedStatuses
                as $status
            ): ?>


                <a
                    href="orders.php?status=<?= urlencode(
                        $status
                    ) ?>"
                    class="<?= $statusFilter === $status
                        ? 'active'
                        : '' ?>"
                >

                    <?= sellerOrderEscape(
                        $status
                    ) ?>

                </a>


            <?php endforeach; ?>


        </nav>



        <!-- =======================================================
             EMPTY
        ======================================================== -->

        <?php if (
            empty(
                $orders
            )
        ): ?>


            <section class="seller-orders-empty">


                <div class="seller-orders-empty-icon">

                    <i class="fa-solid fa-box-open"></i>

                </div>


                <h3>

                    No orders found

                </h3>


                <p>

                    You don't have any orders matching
                    this filter yet.

                </p>


            </section>


        <?php else: ?>


            <!-- ===================================================
                 ORDERS
            ==================================================== -->

            <div class="seller-orders-list">


                <?php foreach (
                    $orders
                    as $order
                ): ?>


                    <?php

                    $statusClass =
                        sellerOrderStatusClass(
                            $order[
                                'vendor_status'
                            ]
                        );


                    $vendorOrderId =
                        (int)
                        $order[
                            'vendor_order_id'
                        ];


                    $orderId =
                        (int)
                        $order[
                            'order_id'
                        ];

                    ?>


                    <article class="seller-order-card">


                        <!-- =========================================
                             HEADER
                        ========================================== -->

                        <div class="seller-order-header">


                            <div class="seller-order-number">


                                <div class="seller-order-number-icon">

                                    <i class="fa-solid fa-receipt"></i>

                                </div>


                                <div>


                                    <strong>

                                        Order #<?= $orderId ?>

                                    </strong>


                                    <small>

                                        Vendor Order #<?= $vendorOrderId ?>

                                    </small>


                                </div>


                            </div>


                            <span
                                class="
                                    seller-order-status
                                    <?= sellerOrderEscape(
                                        $statusClass
                                    ) ?>
                                "
                            >

                                <?= sellerOrderEscape(
                                    $order[
                                        'vendor_status'
                                    ]
                                ) ?>

                            </span>


                        </div>



                        <!-- =========================================
                             BODY
                        ========================================== -->

                        <div class="seller-order-body">


                            <!-- =====================================
                                 CUSTOMER + DELIVERY
                            ====================================== -->

                            <div class="seller-order-info-grid">


                                <section class="seller-order-info-card">


                                    <div class="seller-order-info-title">

                                        <i class="fa-solid fa-user"></i>

                                        CUSTOMER

                                    </div>


                                    <strong>

                                        <?= sellerOrderEscape(
                                            $order[
                                                'customer_name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <p>

                                        <?= sellerOrderEscape(
                                            $order[
                                                'customer_email'
                                            ]
                                        ) ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $order[
                                                'customer_phone'
                                            ]
                                        )
                                    ): ?>


                                        <p>

                                            <?= sellerOrderEscape(
                                                $order[
                                                    'customer_phone'
                                                ]
                                            ) ?>

                                        </p>


                                    <?php endif; ?>


                                </section>



                                <section class="seller-order-info-card">


                                    <div class="seller-order-info-title">

                                        <i class="fa-solid fa-truck"></i>

                                        DELIVERY

                                    </div>


                                    <strong>

                                        <?= sellerOrderEscape(
                                            $order[
                                                'delivery_method'
                                            ]
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $order[
                                                'delivery_address'
                                            ]
                                        )
                                    ): ?>


                                        <p>

                                            <?= nl2br(
                                                sellerOrderEscape(
                                                    $order[
                                                        'delivery_address'
                                                    ]
                                                )
                                            ) ?>

                                        </p>


                                    <?php endif; ?>


                                    <p>

                                        Ordered:
                                        <?= sellerOrderEscape(
                                            $order[
                                                'order_date'
                                            ]
                                        ) ?>

                                    </p>


                                </section>


                            </div>



                            <!-- =====================================
                                 ITEMS
                            ====================================== -->

                            <section class="seller-order-items">


                                <h3 class="seller-order-items-heading">

                                    Order Items

                                </h3>


                                <?php foreach (
                                    $order['items']
                                    as $item
                                ): ?>


                                    <div class="seller-order-item">


                                        <div class="seller-order-item-image">


                                            <?php if (
                                                !empty(
                                                    $item[
                                                        'image'
                                                    ]
                                                )
                                            ): ?>


                                                <img
                                                    src="../uploads/products/<?= sellerOrderEscape(
                                                        rawurlencode(
                                                            basename(
                                                                $item[
                                                                    'image'
                                                                ]
                                                            )
                                                        )
                                                    ) ?>"
                                                    alt="<?= sellerOrderEscape(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>"
                                                    onerror="
                                                        this.style.display='none';
                                                        this.parentElement.innerHTML='<i class=&quot;fa-solid fa-image&quot;></i>';
                                                    "
                                                >


                                            <?php else: ?>


                                                <i class="fa-solid fa-image"></i>


                                            <?php endif; ?>


                                        </div>


                                        <div class="seller-order-item-name">


                                            <strong>

                                                <?= sellerOrderEscape(
                                                    $item[
                                                        'product_name'
                                                    ]
                                                ) ?>

                                            </strong>


                                            <span>

                                                <?= (int)
                                                    $item[
                                                        'quantity'
                                                    ] ?>

                                                × RM

                                                <?= number_format(
                                                    (float)
                                                    $item[
                                                        'unit_price'
                                                    ],
                                                    2
                                                ) ?>

                                            </span>


                                        </div>


                                        <div class="seller-order-item-total">

                                            RM
                                            <?= number_format(
                                                (float)
                                                $item[
                                                    'subtotal'
                                                ],
                                                2
                                            ) ?>

                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </section>



                            <!-- =====================================
                                 SUMMARY
                            ====================================== -->

                            <section class="seller-order-summary">


                                <div class="seller-order-summary-item">


                                    <span>
                                        SUBTOTAL
                                    </span>


                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $order[
                                                'subtotal'
                                            ],
                                            2
                                        ) ?>

                                    </strong>


                                </div>


                                <div class="seller-order-summary-item">


                                    <span>
                                        DELIVERY FEE
                                    </span>


                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $order[
                                                'delivery_fee'
                                            ],
                                            2
                                        ) ?>

                                    </strong>


                                </div>


                            </section>



                            <!-- =====================================
                                 ACTIONS
                            ====================================== -->

                            <section class="seller-order-actions">


                                <!-- STATUS -->

                                <form
                                    method="POST"
                                    class="seller-order-action-card"
                                >


                                    <input
                                        type="hidden"
                                        name="vendor_order_id"
                                        value="<?= $vendorOrderId ?>"
                                    >


                                    <label>

                                        Update Order Status

                                    </label>


                                    <div class="seller-order-action-row">


                                        <select
                                            name="vendor_status"
                                            required
                                        >


                                            <?php foreach (
                                                $allowedStatuses
                                                as $status
                                            ): ?>


                                                <option
                                                    value="<?= sellerOrderEscape(
                                                        $status
                                                    ) ?>"
                                                    <?= $order[
                                                        'vendor_status'
                                                    ] === $status
                                                        ? 'selected'
                                                        : '' ?>
                                                >

                                                    <?= sellerOrderEscape(
                                                        $status
                                                    ) ?>

                                                </option>


                                            <?php endforeach; ?>


                                        </select>


                                        <button
                                            type="submit"
                                            name="update_status"
                                            class="seller-order-update-btn"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                            Update

                                        </button>


                                    </div>


                                </form>



                                <!-- TRACKING -->

                                <form
                                    method="POST"
                                    class="seller-order-action-card"
                                >


                                    <input
                                        type="hidden"
                                        name="vendor_order_id"
                                        value="<?= $vendorOrderId ?>"
                                    >


                                    <label>

                                        Tracking Number

                                    </label>


                                    <div class="seller-order-action-row">


                                        <input
                                            type="text"
                                            name="tracking_number"
                                            value="<?= sellerOrderEscape(
                                                $order[
                                                    'tracking_number'
                                                ]
                                                ?? ''
                                            ) ?>"
                                            placeholder="Enter tracking number"
                                        >


                                        <button
                                            type="submit"
                                            name="update_tracking"
                                            class="seller-order-track-btn"
                                        >

                                            <i class="fa-solid fa-floppy-disk"></i>

                                            Save

                                        </button>


                                    </div>


                                </form>


                            </section>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>


</html>