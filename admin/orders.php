<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN ORDERS
|--------------------------------------------------------------------------
| File: admin/orders.php
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

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
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

if (!function_exists('orderEscape')) {

    function orderEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('orderMoney')) {

    function orderMoney($value): string
    {
        return 'RM ' .
            number_format(
                (float) $value,
                2
            );
    }
}


if (!function_exists('orderDate')) {

    function orderDate($date): string
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


if (!function_exists('orderStatusClass')) {

    function orderStatusClass($status): string
    {
        switch ($status) {

            case 'Completed':
                return 'completed';

            case 'Processing':
                return 'processing';

            case 'Cancelled':
                return 'cancelled';

            case 'Pending':
            default:
                return 'pending';
        }
    }
}


if (!function_exists('paymentStatusClass')) {

    function paymentStatusClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        if (
            in_array(
                $status,
                [
                    'paid',
                    'completed',
                    'success',
                    'successful'
                ],
                true
            )
        ) {

            return 'paid';
        }


        if (
            in_array(
                $status,
                [
                    'failed',
                    'cancelled',
                    'refunded'
                ],
                true
            )
        ) {

            return 'failed';
        }


        return 'pending';
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$error = '';


if (
    isset($_GET['success']) &&
    $_GET['success'] === 'status'
) {

    $message =
        'Order status updated successfully.';
}


if (isset($_GET['error'])) {

    switch ($_GET['error']) {

        case 'security':

            $error =
                'Invalid security token. Please refresh and try again.';

            break;


        case 'invalid':

            $error =
                'Invalid order information.';

            break;


        case 'notfound':

            $error =
                'Order not found.';

            break;


        default:

            $error =
                'Unable to process the order request.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: orders.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | INPUT
    |--------------------------------------------------------------------------
    */

    $orderId =
        isset($_POST['order_id'])
            ? (int) $_POST['order_id']
            : 0;


    $newStatus =
        isset($_POST['order_status'])
            ? trim(
                $_POST['order_status']
            )
            : '';


    $allowedStatuses = [

        'Pending',
        'Processing',
        'Completed',
        'Cancelled'

    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $orderId <= 0 ||
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        header(
            'Location: orders.php?error=invalid'
        );

        exit;
    }


    try {

        $db->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | CHECK ORDER
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT order_id
                FROM orders
                WHERE order_id = ?
                LIMIT 1
                FOR UPDATE
            ");


        $stmt->execute([
            $orderId
        ]);


        if (
            !$stmt->fetch(
                PDO::FETCH_ASSOC
            )
        ) {

            $db->rollBack();


            header(
                'Location: orders.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        if ($newStatus === 'Completed') {

            $stmt =
                $db->prepare("
                    UPDATE orders

                    SET
                        order_status = ?,
                        completed_date = NOW()

                    WHERE order_id = ?
                ");

        }

        else {

            $stmt =
                $db->prepare("
                    UPDATE orders

                    SET
                        order_status = ?,
                        completed_date = NULL

                    WHERE order_id = ?
                ");
        }


        $stmt->execute([
            $newStatus,
            $orderId
        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $action =
            'Updated order #' .
            $orderId .
            ' status to ' .
            $newStatus;


        $log =
            $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        $log->execute([

            $adminId,
            $action,
            'order',
            $orderId

        ]);


        $db->commit();


        $redirect =
            'orders.php?success=status';


        if (
            isset($_POST['return_view']) &&
            (int) $_POST['return_view'] > 0
        ) {

            $redirect .=
                '&view=' .
                (int) $_POST['return_view'];
        }


        header(
            'Location: ' .
            $redirect
        );

        exit;

    }

    catch (Throwable $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }


        error_log(
            'HOCHIPOHUB ADMIN ORDERS UPDATE ERROR: ' .
            $e->getMessage()
        );


        header(
            'Location: orders.php?error=update'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SELECTED ORDER DETAILS
|--------------------------------------------------------------------------
*/

$selectedOrder = null;
$orderItems = [];
$vendorOrders = [];


if (
    isset($_GET['view']) &&
    (int) $_GET['view'] > 0
) {

    $viewOrderId =
        (int) $_GET['view'];


    try {

        /*
        |--------------------------------------------------------------------------
        | MAIN ORDER
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT

                    o.order_id,
                    o.customer_id,
                    o.order_date,
                    o.total_amount,
                    o.delivery_method,
                    o.delivery_address,
                    o.tracking_number,
                    o.order_status,
                    o.completed_date,

                    u.name AS customer_name,
                    u.email AS customer_email,
                    u.phone AS customer_phone

                FROM orders o

                INNER JOIN users u
                    ON o.customer_id = u.user_id

                WHERE o.order_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $viewOrderId
        ]);


        $selectedOrder =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        if ($selectedOrder) {

            $stmt =
                $db->prepare("
                    SELECT

                        od.order_detail_id,
                        od.product_id,
                        od.quantity,
                        od.unit_price,
                        od.subtotal,

                        p.product_name,
                        p.image,

                        v.vendor_id,
                        v.business_name

                    FROM order_details od

                    INNER JOIN products p
                        ON od.product_id = p.product_id

                    INNER JOIN vendors v
                        ON p.vendor_id = v.vendor_id

                    WHERE od.order_id = ?

                    ORDER BY od.order_detail_id ASC
                ");


            $stmt->execute([
                $viewOrderId
            ]);


            $orderItems =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /*
            |--------------------------------------------------------------------------
            | VENDOR ORDERS
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT

                        vo.vendor_order_id,
                        vo.vendor_id,
                        vo.subtotal,
                        vo.delivery_fee,
                        vo.vendor_status,
                        vo.tracking_number,
                        vo.created_at,
                        vo.completed_at,

                        v.business_name

                    FROM vendor_orders vo

                    INNER JOIN vendors v
                        ON vo.vendor_id = v.vendor_id

                    WHERE vo.order_id = ?

                    ORDER BY vo.vendor_order_id ASC
                ");


            $stmt->execute([
                $viewOrderId
            ]);


            $vendorOrders =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }

    }

    catch (Throwable $e) {

        error_log(
            'HOCHIPOHUB ADMIN ORDER DETAILS ERROR: ' .
            $e->getMessage()
        );


        $error =
            'Unable to load order details.';
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$statusFilter =
    $_GET['status']
    ?? '';


$paymentFilter =
    $_GET['payment']
    ?? '';


/*
|--------------------------------------------------------------------------
| FETCH ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];


try {

    $sql = "
        SELECT

            o.order_id,
            o.customer_id,
            o.order_date,
            o.total_amount,
            o.delivery_method,
            o.delivery_address,
            o.tracking_number,
            o.order_status,
            o.completed_date,

            u.name AS customer_name,
            u.email AS customer_email,

            COALESCE(
                (
                    SELECT p.payment_status

                    FROM payments p

                    WHERE p.order_id = o.order_id

                    ORDER BY p.payment_id DESC

                    LIMIT 1
                ),
                'Pending'
            ) AS payment_status,

            COALESCE(
                (
                    SELECT COUNT(*)

                    FROM order_details od

                    WHERE od.order_id = o.order_id
                ),
                0
            ) AS total_items,

            COALESCE(
                (
                    SELECT COUNT(*)

                    FROM vendor_orders vo

                    WHERE vo.order_id = o.order_id
                ),
                0
            ) AS vendor_count

        FROM orders o

        INNER JOIN users u
            ON o.customer_id = u.user_id

        WHERE 1 = 1
    ";


    $params = [];


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
                    o.order_id AS CHAR
                ) LIKE ?

                OR u.name LIKE ?

                OR u.email LIKE ?

                OR o.tracking_number LIKE ?
            )
        ";


        $searchValue =
            '%' .
            $search .
            '%';


        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER STATUS
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $statusFilter,
            [
                'Pending',
                'Processing',
                'Completed',
                'Cancelled'
            ],
            true
        )
    ) {

        $sql .= "
            AND o.order_status = ?
        ";


        $params[] =
            $statusFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT FILTER
    |--------------------------------------------------------------------------
    */

    if ($paymentFilter === 'Paid') {

        $sql .= "
            AND EXISTS
            (
                SELECT 1

                FROM payments px

                WHERE px.payment_id =
                (
                    SELECT MAX(py.payment_id)

                    FROM payments py

                    WHERE py.order_id = o.order_id
                )

                AND LOWER(px.payment_status)
                    IN
                    (
                        'paid',
                        'completed',
                        'success',
                        'successful'
                    )
            )
        ";

    }

    elseif ($paymentFilter === 'Pending') {

        $sql .= "
            AND
            (
                NOT EXISTS
                (
                    SELECT 1
                    FROM payments px
                    WHERE px.order_id = o.order_id
                )

                OR EXISTS
                (
                    SELECT 1

                    FROM payments px

                    WHERE px.payment_id =
                    (
                        SELECT MAX(py.payment_id)

                        FROM payments py

                        WHERE py.order_id = o.order_id
                    )

                    AND LOWER(px.payment_status)
                        NOT IN
                        (
                            'paid',
                            'completed',
                            'success',
                            'successful',
                            'failed',
                            'cancelled',
                            'refunded'
                        )
                )
            )
        ";

    }

    elseif ($paymentFilter === 'Failed') {

        $sql .= "
            AND EXISTS
            (
                SELECT 1

                FROM payments px

                WHERE px.payment_id =
                (
                    SELECT MAX(py.payment_id)

                    FROM payments py

                    WHERE py.order_id = o.order_id
                )

                AND LOWER(px.payment_status)
                    IN
                    (
                        'failed',
                        'cancelled',
                        'refunded'
                    )
            )
        ";
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY
            o.order_date DESC,
            o.order_id DESC
    ";


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


    error_log(
        'HOCHIPOHUB ADMIN FETCH ORDERS ERROR: ' .
        $e->getMessage()
    );


    $error =
        'Unable to load orders.';
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalOrders = 0;
$pendingOrders = 0;
$processingOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;


try {

    $totalOrders =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM orders
            ")
            ->fetchColumn();


    $pendingOrders =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE order_status = 'Pending'
            ")
            ->fetchColumn();


    $processingOrders =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE order_status = 'Processing'
            ")
            ->fetchColumn();


    $completedOrders =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE order_status = 'Completed'
            ")
            ->fetchColumn();


    $cancelledOrders =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE order_status = 'Cancelled'
            ")
            ->fetchColumn();

}

catch (Throwable $e) {

    error_log(
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
        Orders | HochipoHub Admin
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
         CSS
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

            --orders-sidebar-width:
                260px;

            --orders-blue:
                #2563eb;

            --orders-navy:
                #08265a;

            --orders-border:
                #dce7f3;

            --orders-text:
                #0b2d63;

            --orders-muted:
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
        select,
        textarea {

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

        .orders-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --orders-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --orders-sidebar-width
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

        .orders-content {

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

        .orders-hero {

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


        .orders-hero::before {

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


        .orders-hero::after {

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


        .orders-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .orders-hero h1 {

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


        .orders-hero p {

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
        | HERO ICON
        |--------------------------------------------------------------------------
        */

        .orders-hero-icon {

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
        | ALERT
        |--------------------------------------------------------------------------
        */

        .orders-alert {

            margin-bottom:
                22px;

            padding:
                14px
                17px;

            border-radius:
                12px;

            font-size:
                11px;

            font-weight:
                600;

        }


        .orders-alert.success {

            color:
                #166534;

            background:
                #ecfdf5;

            border:
                1px solid
                #bbf7d0;

        }


        .orders-alert.error {

            color:
                #991b1b;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .orders-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    5,
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


        .order-stat {

            position:
                relative;

            min-height:
                145px;

            overflow:
                hidden;

            padding:
                25px
                22px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --orders-border
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


        .order-stat::after {

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


        .order-stat.pending {

            border-top-color:
                #f59e0b;

        }


        .order-stat.pending::after {

            background:
                #fff7df;

        }


        .order-stat.processing {

            border-top-color:
                #3b82f6;

        }


        .order-stat.completed {

            border-top-color:
                #16a34a;

        }


        .order-stat.completed::after {

            background:
                #eaf9ef;

        }


        .order-stat.cancelled {

            border-top-color:
                #ef4444;

        }


        .order-stat.cancelled::after {

            background:
                #fff0f1;

        }


        .order-stat-label {

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


        .order-stat-value {

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


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .orders-panel {

            overflow:
                hidden;

            margin-bottom:
                28px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --orders-border
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

        .orders-panel-header {

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


        .orders-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .orders-panel-icon {

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


        .orders-panel-header h2 {

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


        .orders-panel-header p {

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

        .orders-count {

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

        .orders-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .orders-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1.5fr
                )

                minmax(
                    150px,
                    .5fr
                )

                minmax(
                    150px,
                    .5fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .orders-filter input,
        .orders-filter select {

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


        .orders-filter input:focus,
        .orders-filter select:focus {

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

        .order-btn {

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


        .order-btn-primary {

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

        }


        .order-btn-secondary {

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
        | ORDER DETAIL GRID
        |--------------------------------------------------------------------------
        */

        .order-detail-grid {

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
                14px;

            padding:
                25px
                28px;

        }


        .order-detail-card {

            padding:
                19px;

            background:

                linear-gradient(
                    145deg,
                    #f9fbff,
                    #f0f6ff
                );

            border:
                1px solid
                #dce8f7;

            border-radius:
                17px;

        }


        .order-detail-title {

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
                9px;

            font-weight:
                800;

            text-transform:
                uppercase;

            letter-spacing:
                .6px;

        }


        .order-detail-card strong {

            color:
                #0b326d;

            font-size:
                14px;

            font-weight:
                800;

        }


        .order-detail-card p {

            margin:
                5px
                0;

            color:
                #61728e;

            font-size:
                9px;

            line-height:
                1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | SUB SECTION
        |--------------------------------------------------------------------------
        */

        .order-subsection {

            padding:
                0
                28px
                28px;

        }


        .order-subsection-title {

            margin:
                5px
                0
                14px;

            color:
                #092e65;

            font-size:
                15px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .orders-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .orders-table {

            width:
                100%;

            min-width:
                1100px;

            border-collapse:
                collapse;

        }


        .orders-table thead {

            background:
                #f6f9fd;

        }


        .orders-table th {

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


        .orders-table td {

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


        .orders-table tbody tr:hover {

            background:
                #f9fbff;

        }


        .orders-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | ORDER ID
        |--------------------------------------------------------------------------
        */

        .order-id {

            color:
                #2563eb;

            font-size:
                9px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        .order-customer {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            min-width:
                190px;

        }


        .order-avatar {

            width:
                39px;

            height:
                39px;

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
                    #60a5fa
                );

            border-radius:
                10px;

            font-size:
                12px;

            font-weight:
                800;

        }


        .order-customer strong {

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


        .order-customer small {

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
        | STATUS BADGES
        |--------------------------------------------------------------------------
        */

        .order-status-badge,
        .payment-status-badge {

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

            white-space:
                nowrap;

        }


        .order-status-badge::before,
        .payment-status-badge::before {

            content:
                "";

            width:
                5px;

            height:
                5px;

            border-radius:
                50%;

            background:
                currentColor;

        }


        .order-status-badge.pending {

            color:
                #a16207;

            background:
                #fffbea;

        }


        .order-status-badge.processing {

            color:
                #1d4ed8;

            background:
                #eff6ff;

        }


        .order-status-badge.completed {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .order-status-badge.cancelled {

            color:
                #b91c1c;

            background:
                #fff1f2;

        }


        .payment-status-badge.paid {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .payment-status-badge.pending {

            color:
                #a16207;

            background:
                #fffbea;

        }


        .payment-status-badge.failed {

            color:
                #b91c1c;

            background:
                #fff1f2;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS SELECT
        |--------------------------------------------------------------------------
        */

        .order-status-select {

            min-width:
                118px;

            height:
                34px;

            padding:
                0
                9px;

            outline:
                none;

            color:
                #334155;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ef;

            border-radius:
                9px;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .order-status-select:focus {

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
        | VIEW BUTTON
        |--------------------------------------------------------------------------
        */

        .order-view-btn {

            min-height:
                32px;

            padding:
                0
                11px;

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
                8px;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .orders-empty {

            padding:
                70px
                20px !important;

            color:
                #94a3b8 !important;

            text-align:
                center;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1250px) {

            .orders-stats {

                grid-template-columns:

                    repeat(
                        3,
                        1fr
                    );

            }


            .order-detail-grid {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .orders-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .orders-filter input {

                grid-column:
                    1 / -1;

            }

        }


        @media (max-width: 900px) {

            :root {

                --orders-sidebar-width:
                    0px;

            }


            .orders-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .orders-content {

                padding:
                    25px
                    20px
                    50px;

            }


            .orders-hero {

                min-height:
                    140px;

                padding:
                    28px;

            }


            .orders-hero h1 {

                font-size:
                    31px;

            }


            .orders-hero-icon {

                width:
                    67px;

                height:
                    67px;

                font-size:
                    28px;

            }

        }


        @media (max-width: 650px) {

            .orders-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .orders-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .orders-hero h1 {

                font-size:
                    27px;

            }


            .orders-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .orders-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .orders-stats {

                grid-template-columns:
                    1fr;

            }


            .order-detail-grid {

                grid-template-columns:
                    1fr;

            }


            .orders-panel-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                padding:
                    20px
                    17px;

            }


            .orders-filter {

                grid-template-columns:
                    1fr;

            }


            .orders-filter input {

                grid-column:
                    auto;

            }


            .order-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="orders-main">


        <div class="orders-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="orders-hero">


                <div class="orders-hero-text">

                    <h1>
                        Orders
                    </h1>

                    <p>
                        Manage, monitor and track every HochipoHub customer order.
                    </p>

                </div>


                <div class="orders-hero-icon">

                    🧾

                </div>


            </section>


            <!-- =====================================================
                 MESSAGE
            ====================================================== -->

            <?php if ($message !== ''): ?>


                <div
                    class="
                        orders-alert
                        success
                    "
                >

                    <?= orderEscape(
                        $message
                    ) ?>

                </div>


            <?php endif; ?>


            <?php if ($error !== ''): ?>


                <div
                    class="
                        orders-alert
                        error
                    "
                >

                    <?= orderEscape(
                        $error
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="orders-stats">


                <div class="order-stat">

                    <span class="order-stat-label">

                        Total Orders

                    </span>


                    <strong class="order-stat-value">

                        <?= number_format(
                            $totalOrders
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        order-stat
                        pending
                    "
                >

                    <span class="order-stat-label">

                        Pending

                    </span>


                    <strong class="order-stat-value">

                        <?= number_format(
                            $pendingOrders
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        order-stat
                        processing
                    "
                >

                    <span class="order-stat-label">

                        Processing

                    </span>


                    <strong class="order-stat-value">

                        <?= number_format(
                            $processingOrders
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        order-stat
                        completed
                    "
                >

                    <span class="order-stat-label">

                        Completed

                    </span>


                    <strong class="order-stat-value">

                        <?= number_format(
                            $completedOrders
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        order-stat
                        cancelled
                    "
                >

                    <span class="order-stat-label">

                        Cancelled

                    </span>


                    <strong class="order-stat-value">

                        <?= number_format(
                            $cancelledOrders
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 SELECTED ORDER
            ====================================================== -->

            <?php if ($selectedOrder): ?>


                <section class="orders-panel">


                    <div class="orders-panel-header">


                        <div class="orders-panel-title">


                            <div class="orders-panel-icon">

                                🔎

                            </div>


                            <div>

                                <h2>

                                    Order
                                    #<?= (int)
                                        $selectedOrder[
                                            'order_id'
                                        ] ?>

                                </h2>


                                <p>

                                    <?= orderEscape(
                                        orderDate(
                                            $selectedOrder[
                                                'order_date'
                                            ]
                                            ?? null
                                        )
                                    ) ?>

                                </p>

                            </div>


                        </div>


                        <a
                            href="orders.php"
                            class="
                                order-btn
                                order-btn-secondary
                            "
                        >

                            ← Back to Orders

                        </a>


                    </div>


                    <!-- =================================================
                         ORDER INFO
                    ================================================== -->

                    <div class="order-detail-grid">


                        <!-- CUSTOMER -->

                        <div class="order-detail-card">

                            <div class="order-detail-title">

                                👤 Customer

                            </div>


                            <strong>

                                <?= orderEscape(
                                    $selectedOrder[
                                        'customer_name'
                                    ]
                                ) ?>

                            </strong>


                            <p>

                                <?= orderEscape(
                                    $selectedOrder[
                                        'customer_email'
                                    ]
                                ) ?>

                            </p>


                            <p>

                                <?= orderEscape(
                                    $selectedOrder[
                                        'customer_phone'
                                    ]
                                    ?? '-'
                                ) ?>

                            </p>

                        </div>


                        <!-- DELIVERY -->

                        <div class="order-detail-card">

                            <div class="order-detail-title">

                                🚚 Delivery

                            </div>


                            <strong>

                                <?= orderEscape(
                                    $selectedOrder[
                                        'delivery_method'
                                    ]
                                    ?? '-'
                                ) ?>

                            </strong>


                            <?php if (
                                !empty(
                                    $selectedOrder[
                                        'delivery_address'
                                    ]
                                )
                            ): ?>


                                <p>

                                    <?= nl2br(
                                        orderEscape(
                                            $selectedOrder[
                                                'delivery_address'
                                            ]
                                        )
                                    ) ?>

                                </p>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $selectedOrder[
                                        'tracking_number'
                                    ]
                                )
                            ): ?>


                                <p>

                                    Tracking:
                                    <?= orderEscape(
                                        $selectedOrder[
                                            'tracking_number'
                                        ]
                                    ) ?>

                                </p>


                            <?php endif; ?>

                        </div>


                        <!-- STATUS -->

                        <div class="order-detail-card">

                            <div class="order-detail-title">

                                📌 Order Status

                            </div>


                            <span
                                class="
                                    order-status-badge
                                    <?= orderEscape(
                                        orderStatusClass(
                                            $selectedOrder[
                                                'order_status'
                                            ]
                                        )
                                    ) ?>
                                "
                            >

                                <?= orderEscape(
                                    $selectedOrder[
                                        'order_status'
                                    ]
                                ) ?>

                            </span>

                        </div>


                        <!-- TOTAL -->

                        <div class="order-detail-card">

                            <div class="order-detail-title">

                                💰 Order Total

                            </div>


                            <strong>

                                <?= orderEscape(
                                    orderMoney(
                                        $selectedOrder[
                                            'total_amount'
                                        ]
                                    )
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <!-- =================================================
                         ORDER ITEMS
                    ================================================== -->

                    <div class="order-subsection">


                        <h3 class="order-subsection-title">

                            📦 Order Items

                        </h3>


                        <div class="orders-table-wrapper">


                            <table class="orders-table">


                                <thead>

                                    <tr>

                                        <th>
                                            Product
                                        </th>

                                        <th>
                                            Vendor
                                        </th>

                                        <th>
                                            Quantity
                                        </th>

                                        <th>
                                            Unit Price
                                        </th>

                                        <th>
                                            Subtotal
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php if (
                                        empty(
                                            $orderItems
                                        )
                                    ): ?>


                                        <tr>

                                            <td
                                                colspan="5"
                                                class="orders-empty"
                                            >

                                                No order items found.

                                            </td>

                                        </tr>


                                    <?php else: ?>


                                        <?php foreach (
                                            $orderItems
                                            as $item
                                        ): ?>


                                            <tr>

                                                <td>

                                                    <strong>

                                                        <?= orderEscape(
                                                            $item[
                                                                'product_name'
                                                            ]
                                                        ) ?>

                                                    </strong>

                                                </td>


                                                <td>

                                                    <?= orderEscape(
                                                        $item[
                                                            'business_name'
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= number_format(
                                                        (int)
                                                        $item[
                                                            'quantity'
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= orderEscape(
                                                        orderMoney(
                                                            $item[
                                                                'unit_price'
                                                            ]
                                                        )
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <strong>

                                                        <?= orderEscape(
                                                            orderMoney(
                                                                $item[
                                                                    'subtotal'
                                                                ]
                                                            )
                                                        ) ?>

                                                    </strong>

                                                </td>

                                            </tr>


                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                </tbody>


                            </table>


                        </div>


                    </div>


                    <!-- =================================================
                         VENDOR SUB ORDERS
                    ================================================== -->

                    <div class="order-subsection">


                        <h3 class="order-subsection-title">

                            🏪 Vendor Sub-orders

                        </h3>


                        <div class="orders-table-wrapper">


                            <table class="orders-table">


                                <thead>

                                    <tr>

                                        <th>
                                            Vendor
                                        </th>

                                        <th>
                                            Subtotal
                                        </th>

                                        <th>
                                            Delivery Fee
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Tracking
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php if (
                                        empty(
                                            $vendorOrders
                                        )
                                    ): ?>


                                        <tr>

                                            <td
                                                colspan="5"
                                                class="orders-empty"
                                            >

                                                No vendor orders found.

                                            </td>

                                        </tr>


                                    <?php else: ?>


                                        <?php foreach (
                                            $vendorOrders
                                            as $vendorOrder
                                        ): ?>


                                            <tr>

                                                <td>

                                                    <strong>

                                                        <?= orderEscape(
                                                            $vendorOrder[
                                                                'business_name'
                                                            ]
                                                        ) ?>

                                                    </strong>

                                                </td>


                                                <td>

                                                    <?= orderEscape(
                                                        orderMoney(
                                                            $vendorOrder[
                                                                'subtotal'
                                                            ]
                                                        )
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= orderEscape(
                                                        orderMoney(
                                                            $vendorOrder[
                                                                'delivery_fee'
                                                            ]
                                                        )
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <span
                                                        class="
                                                            order-status-badge
                                                            processing
                                                        "
                                                    >

                                                        <?= orderEscape(
                                                            $vendorOrder[
                                                                'vendor_status'
                                                            ]
                                                        ) ?>

                                                    </span>

                                                </td>


                                                <td>

                                                    <?= orderEscape(
                                                        $vendorOrder[
                                                            'tracking_number'
                                                        ]
                                                        ?: '-'
                                                    ) ?>

                                                </td>

                                            </tr>


                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                </tbody>


                            </table>


                        </div>


                    </div>


                </section>


            <?php endif; ?>


            <!-- =====================================================
                 ORDERS PANEL
            ====================================================== -->

            <section class="orders-panel">


                <div class="orders-panel-header">


                    <div class="orders-panel-title">


                        <div class="orders-panel-icon">

                            📦

                        </div>


                        <div>

                            <h2>
                                Order Management
                            </h2>

                            <p>
                                Search, filter and manage customer orders.
                            </p>

                        </div>


                    </div>


                    <span class="orders-count">

                        <?= number_format(
                            count(
                                $orders
                            )
                        ) ?>

                        orders

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="orders-filter-wrapper">


                    <form
                        method="GET"
                        action="orders.php"
                        class="orders-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= orderEscape(
                                $search
                            ) ?>"
                            placeholder="Search order, customer, email or tracking..."
                            autocomplete="off"
                        >


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter order status"
                        >

                            <option value="">

                                All Status

                            </option>


                            <?php foreach (
                                [
                                    'Pending',
                                    'Processing',
                                    'Completed',
                                    'Cancelled'
                                ]
                                as $status
                            ): ?>


                                <option
                                    value="<?= orderEscape(
                                        $status
                                    ) ?>"
                                    <?= $statusFilter === $status
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= orderEscape(
                                        $status
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- PAYMENT -->

                        <select
                            name="payment"
                            aria-label="Filter payment status"
                        >

                            <option value="">

                                All Payments

                            </option>


                            <option
                                value="Paid"
                                <?= $paymentFilter ===
                                    'Paid'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Paid

                            </option>


                            <option
                                value="Pending"
                                <?= $paymentFilter ===
                                    'Pending'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Pending

                            </option>


                            <option
                                value="Failed"
                                <?= $paymentFilter ===
                                    'Failed'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Failed

                            </option>


                        </select>


                        <button
                            type="submit"
                            class="
                                order-btn
                                order-btn-primary
                            "
                        >

                            Search

                        </button>


                        <a
                            href="orders.php"
                            class="
                                order-btn
                                order-btn-secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="orders-table-wrapper">


                    <table class="orders-table">


                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Items
                                </th>

                                <th>
                                    Vendors
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (
                                empty(
                                    $orders
                                )
                            ): ?>


                                <tr>

                                    <td
                                        colspan="9"
                                        class="orders-empty"
                                    >

                                        No orders found.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $orders
                                    as $order
                                ): ?>


                                    <?php

                                    $orderId =
                                        (int)
                                        $order[
                                            'order_id'
                                        ];


                                    $initial =
                                        strtoupper(
                                            substr(
                                                trim(
                                                    $order[
                                                        'customer_name'
                                                    ]
                                                    ?? 'U'
                                                ),
                                                0,
                                                1
                                            )
                                        );


                                    $statusClass =
                                        orderStatusClass(
                                            $order[
                                                'order_status'
                                            ]
                                        );


                                    $paymentClass =
                                        paymentStatusClass(
                                            $order[
                                                'payment_status'
                                            ]
                                        );

                                    ?>


                                    <tr>


                                        <!-- ORDER -->

                                        <td>

                                            <span class="order-id">

                                                #<?= $orderId ?>

                                            </span>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>


                                            <div class="order-customer">


                                                <div class="order-avatar">

                                                    <?= orderEscape(
                                                        $initial
                                                    ) ?>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= orderEscape(
                                                            $order[
                                                                'customer_name'
                                                            ]
                                                        ) ?>

                                                    </strong>


                                                    <small>

                                                        <?= orderEscape(
                                                            $order[
                                                                'customer_email'
                                                            ]
                                                        ) ?>

                                                    </small>

                                                </div>


                                            </div>


                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?= orderEscape(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $order[
                                                            'order_date'
                                                        ]
                                                    )
                                                )
                                            ) ?>


                                            <small>

                                                <?= orderEscape(
                                                    date(
                                                        'h:i A',
                                                        strtotime(
                                                            $order[
                                                                'order_date'
                                                            ]
                                                        )
                                                    )
                                                ) ?>

                                            </small>

                                        </td>


                                        <!-- ITEMS -->

                                        <td>

                                            <strong>

                                                <?= number_format(
                                                    (int)
                                                    $order[
                                                        'total_items'
                                                    ]
                                                ) ?>

                                            </strong>

                                            <small>
                                                item(s)
                                            </small>

                                        </td>


                                        <!-- VENDORS -->

                                        <td>

                                            <strong>

                                                <?= number_format(
                                                    (int)
                                                    $order[
                                                        'vendor_count'
                                                    ]
                                                ) ?>

                                            </strong>

                                            <small>
                                                vendor(s)
                                            </small>

                                        </td>


                                        <!-- TOTAL -->

                                        <td>

                                            <strong>

                                                <?= orderEscape(
                                                    orderMoney(
                                                        $order[
                                                            'total_amount'
                                                        ]
                                                    )
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- PAYMENT -->

                                        <td>

                                            <span
                                                class="
                                                    payment-status-badge
                                                    <?= orderEscape(
                                                        $paymentClass
                                                    ) ?>
                                                "
                                            >

                                                <?= orderEscape(
                                                    $order[
                                                        'payment_status'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="orders.php"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= orderEscape(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="order_id"
                                                    value="<?= $orderId ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="update_status"
                                                    value="1"
                                                >


                                                <?php if (
                                                    $selectedOrder &&
                                                    (int)
                                                    $selectedOrder[
                                                        'order_id'
                                                    ] === $orderId
                                                ): ?>


                                                    <input
                                                        type="hidden"
                                                        name="return_view"
                                                        value="<?= $orderId ?>"
                                                    >


                                                <?php endif; ?>


                                                <select
                                                    name="order_status"
                                                    class="order-status-select"
                                                    onchange="
                                                        if (
                                                            confirm(
                                                                'Change order status to ' +
                                                                this.value +
                                                                '?'
                                                            )
                                                        ) {
                                                            this.form.submit();
                                                        } else {
                                                            window.location.reload();
                                                        }
                                                    "
                                                >


                                                    <?php foreach (
                                                        [
                                                            'Pending',
                                                            'Processing',
                                                            'Completed',
                                                            'Cancelled'
                                                        ]
                                                        as $status
                                                    ): ?>


                                                        <option
                                                            value="<?= orderEscape(
                                                                $status
                                                            ) ?>"
                                                            <?= $order[
                                                                'order_status'
                                                            ] === $status
                                                                ? 'selected'
                                                                : '' ?>
                                                        >

                                                            <?= orderEscape(
                                                                $status
                                                            ) ?>

                                                        </option>


                                                    <?php endforeach; ?>


                                                </select>


                                            </form>


                                            <div
                                                style="
                                                    margin-top:
                                                        7px;
                                                "
                                            >

                                                <span
                                                    class="
                                                        order-status-badge
                                                        <?= orderEscape(
                                                            $statusClass
                                                        ) ?>
                                                    "
                                                >

                                                    <?= orderEscape(
                                                        $order[
                                                            'order_status'
                                                        ]
                                                    ) ?>

                                                </span>

                                            </div>


                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <a
                                                href="orders.php?view=<?= $orderId ?>"
                                                class="order-view-btn"
                                            >

                                                View

                                            </a>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


        </div>


    </main>


</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH
    |--------------------------------------------------------------------------
    */

    function syncOrdersSidebar() {

        const main =
            document.querySelector(
                '.orders-main'
            );


        if (!main) {
            return;
        }


        if (
            window.innerWidth <= 900
        ) {

            document.documentElement
                .style
                .setProperty(
                    '--orders-sidebar-width',
                    '0px'
                );


            return;
        }


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


        if (!sidebar) {

            document.documentElement
                .style
                .setProperty(
                    '--orders-sidebar-width',
                    '260px'
                );


            return;
        }


        const rect =
            sidebar
                .getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement
                .style
                .setProperty(
                    '--orders-sidebar-width',
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

            syncOrdersSidebar();


            setTimeout(
                syncOrdersSidebar,
                100
            );


            setTimeout(
                syncOrdersSidebar,
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
        syncOrdersSidebar
    );

</script>


</body>

</html>