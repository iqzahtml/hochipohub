<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN ORDERS
|--------------------------------------------------------------------------
| File: admin/orders.php
|--------------------------------------------------------------------------
| Database:
| - PDO
| - database/db.php provides $db
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CONFIG + DATABASE
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| database/db.php:
|
| $db = getDB();
|
*/

$db = getDB();


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
    $_SESSION['role'] !== 'admin'
) {

    header("Location: ../index.php");
    exit;
}


$admin_id = (int) $_SESSION['user_id'];


$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function money($value)
{
    return "RM " . number_format(
        (float) $value,
        2
    );
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

    $order_id = isset($_POST['order_id'])
        ? (int) $_POST['order_id']
        : 0;

    $new_status = isset($_POST['order_status'])
        ? trim($_POST['order_status'])
        : '';


    $allowed_status = [
        'Pending',
        'Processing',
        'Completed',
        'Cancelled'
    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDATE ORDER ID
    |--------------------------------------------------------------------------
    */

    if ($order_id <= 0) {

        $error = "Invalid order ID.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
    |--------------------------------------------------------------------------
    */

    elseif (!in_array(
        $new_status,
        $allowed_status,
        true
    )) {

        $error = "Invalid order status.";

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    else {

        try {

            /*
            |--------------------------------------------------------------------------
            | COMPLETED
            |--------------------------------------------------------------------------
            */

            if ($new_status === 'Completed') {

                $stmt = $db->prepare("
                    UPDATE orders

                    SET
                        order_status = :status,
                        completed_date = NOW()

                    WHERE order_id = :order_id
                ");

            }


            /*
            |--------------------------------------------------------------------------
            | OTHER STATUS
            |--------------------------------------------------------------------------
            */

            else {

                $stmt = $db->prepare("
                    UPDATE orders

                    SET
                        order_status = :status,
                        completed_date = NULL

                    WHERE order_id = :order_id
                ");

            }


            $stmt->execute([
                ':status' => $new_status,
                ':order_id' => $order_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $action =
                "Updated order #"
                . $order_id
                . " status to "
                . $new_status;


            $target_type = "order";


            $log = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )

                VALUES
                (
                    :admin_id,
                    :action,
                    :target_type,
                    :target_id
                )
            ");


            $log->execute([
                ':admin_id' => $admin_id,
                ':action' => $action,
                ':target_type' => $target_type,
                ':target_id' => $order_id
            ]);


            $message =
                "Order #"
                . $order_id
                . " status updated successfully.";


        } catch (PDOException $e) {

            error_log(
                "HOCHIPOHUB ADMIN ORDERS UPDATE ERROR: "
                . $e->getMessage()
            );


            $error =
                "Failed to update order status.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| ORDER DETAILS
|--------------------------------------------------------------------------
*/

$selected_order = null;

$order_items = [];

$vendor_orders = [];


if (isset($_GET['view'])) {

    $view_order_id =
        (int) $_GET['view'];


    if ($view_order_id > 0) {

        try {

            /*
            |--------------------------------------------------------------------------
            | MAIN ORDER
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
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

                WHERE o.order_id = :order_id

                LIMIT 1
            ");


            $stmt->execute([
                ':order_id' => $view_order_id
            ]);


            $selected_order =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /*
            |--------------------------------------------------------------------------
            | ORDER ITEMS
            |--------------------------------------------------------------------------
            */

            if ($selected_order) {

                $stmt = $db->prepare("
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

                    WHERE od.order_id = :order_id

                    ORDER BY
                        od.order_detail_id ASC
                ");


                $stmt->execute([
                    ':order_id' => $view_order_id
                ]);


                $order_items =
                    $stmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                /*
                |--------------------------------------------------------------------------
                | VENDOR ORDERS
                |--------------------------------------------------------------------------
                */

                $stmt = $db->prepare("
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

                    WHERE vo.order_id = :order_id

                    ORDER BY
                        vo.vendor_order_id ASC
                ");


                $stmt->execute([
                    ':order_id' => $view_order_id
                ]);


                $vendor_orders =
                    $stmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );
            }


        } catch (PDOException $e) {

            error_log(
                "HOCHIPOHUB ADMIN ORDER DETAILS ERROR: "
                . $e->getMessage()
            );


            $error =
                "Unable to load order details.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH ALL ORDERS
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
                    SELECT
                        p.payment_status

                    FROM payments p

                    WHERE
                        p.order_id = o.order_id

                    ORDER BY
                        p.payment_id DESC

                    LIMIT 1
                ),

                'Pending'

            ) AS payment_status,


            COALESCE(

                (
                    SELECT
                        COUNT(*)

                    FROM order_details od

                    WHERE
                        od.order_id = o.order_id
                ),

                0

            ) AS total_items,


            COALESCE(

                (
                    SELECT
                        COUNT(*)

                    FROM vendor_orders vo

                    WHERE
                        vo.order_id = o.order_id
                ),

                0

            ) AS vendor_count


        FROM orders o


        INNER JOIN users u

            ON o.customer_id = u.user_id


        ORDER BY
            o.order_date DESC

    ";


    $stmt =
        $db->query($sql);


    $orders =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    error_log(
        "HOCHIPOHUB ADMIN FETCH ORDERS ERROR: "
        . $e->getMessage()
    );


    $error =
        "Unable to load orders.";
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$total_orders =
    count($orders);


$pending_orders = 0;

$processing_orders = 0;

$completed_orders = 0;

$cancelled_orders = 0;


foreach ($orders as $order) {

    switch (
        $order['order_status']
    ) {

        case 'Pending':

            $pending_orders++;

            break;


        case 'Processing':

            $processing_orders++;

            break;


        case 'Completed':

            $completed_orders++;

            break;


        case 'Cancelled':

            $cancelled_orders++;

            break;
    }
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
        Manage Orders | HochipoHub Admin
    </title>


    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->


    <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>


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


            <a
                href="orders.php"
                class="active"
            >
                Orders
            </a>


            <a href="payments.php">
                Payments
            </a>


            <a href="commission.php">
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

    <!-- =====================================================
         MAIN
    ====================================================== -->


    <main class="admin-main">


        <!-- =================================================
             HEADER
        ================================================== -->


        <header class="admin-header">


            <div>

                <h1>
                    Orders
                </h1>


                <p>
                    Manage and monitor customer orders.
                </p>

            </div>


        </header>



        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->


        <?php if ($message): ?>

            <div class="admin-alert success">

                <?= e($message) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->


        <?php if ($error): ?>

            <div class="admin-alert error">

                <?= e($error) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             STATISTICS
        ================================================== -->


        <section class="admin-stats">


            <!-- TOTAL -->


            <div class="stat-card">

                <span>
                    Total Orders
                </span>


                <strong>
                    <?= e($total_orders) ?>
                </strong>

            </div>



            <!-- PENDING -->


            <div class="stat-card">

                <span>
                    Pending
                </span>


                <strong>
                    <?= e($pending_orders) ?>
                </strong>

            </div>



            <!-- PROCESSING -->


            <div class="stat-card">

                <span>
                    Processing
                </span>


                <strong>
                    <?= e($processing_orders) ?>
                </strong>

            </div>



            <!-- COMPLETED -->


            <div class="stat-card">

                <span>
                    Completed
                </span>


                <strong>
                    <?= e($completed_orders) ?>
                </strong>

            </div>



            <!-- CANCELLED -->


            <div class="stat-card">

                <span>
                    Cancelled
                </span>


                <strong>
                    <?= e($cancelled_orders) ?>
                </strong>

            </div>


        </section>



        <!-- =================================================
             SELECTED ORDER DETAILS
        ================================================== -->


        <?php if ($selected_order): ?>


            <section class="admin-card">


                <!-- HEADER -->


                <div class="card-header">


                    <div>


                        <h2>

                            Order #

                            <?= e(
                                $selected_order['order_id']
                            ) ?>

                        </h2>


                        <p>

                            <?= e(
                                $selected_order['order_date']
                            ) ?>

                        </p>


                    </div>


                    <a
                        href="orders.php"
                        class="admin-btn secondary"
                    >
                        Back
                    </a>


                </div>



                <!-- ORDER INFORMATION -->


                <div class="order-info-grid">


                    <!-- CUSTOMER -->


                    <div>


                        <h3>
                            Customer
                        </h3>


                        <p>

                            <?= e(
                                $selected_order['customer_name']
                            ) ?>

                        </p>


                        <p>

                            <?= e(
                                $selected_order['customer_email']
                            ) ?>

                        </p>


                        <p>

                            <?= e(
                                $selected_order['customer_phone']
                                ?? '-'
                            ) ?>

                        </p>


                    </div>



                    <!-- DELIVERY -->


                    <div>


                        <h3>
                            Delivery
                        </h3>


                        <p>

                            <?= e(
                                $selected_order['delivery_method']
                                ?? '-'
                            ) ?>

                        </p>


                        <?php if (
                            !empty(
                                $selected_order['delivery_address']
                            )
                        ): ?>


                            <p>

                                <?= nl2br(
                                    e(
                                        $selected_order[
                                            'delivery_address'
                                        ]
                                    )
                                ) ?>

                            </p>


                        <?php endif; ?>


                    </div>



                    <!-- STATUS -->


                    <div>


                        <h3>
                            Order Status
                        </h3>


                        <span class="status-badge">

                            <?= e(
                                $selected_order['order_status']
                            ) ?>

                        </span>


                    </div>



                    <!-- TOTAL -->


                    <div>


                        <h3>
                            Total
                        </h3>


                        <strong>

                            <?= money(
                                $selected_order['total_amount']
                            ) ?>

                        </strong>


                    </div>


                </div>



                <!-- =================================================
                     ORDER ITEMS
                ================================================== -->


                <h3 class="section-title">

                    Order Items

                </h3>



                <div class="admin-table-wrapper">


                    <table class="admin-table">


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
                            empty($order_items)
                        ): ?>


                            <tr>

                                <td colspan="5">

                                    No order items found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $order_items
                                as $item
                            ): ?>


                                <tr>


                                    <td>

                                        <?= e(
                                            $item[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $item[
                                                'quantity'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= money(
                                            $item[
                                                'unit_price'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= money(
                                            $item[
                                                'subtotal'
                                            ]
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>



                <!-- =================================================
                     VENDOR ORDERS
                ================================================== -->


                <h3 class="section-title">

                    Vendor Sub-orders

                </h3>



                <div class="admin-table-wrapper">


                    <table class="admin-table">


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
                            empty($vendor_orders)
                        ): ?>


                            <tr>

                                <td colspan="5">

                                    No vendor orders found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $vendor_orders
                                as $vo
                            ): ?>


                                <tr>


                                    <td>

                                        <?= e(
                                            $vo[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= money(
                                            $vo[
                                                'subtotal'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= money(
                                            $vo[
                                                'delivery_fee'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <span class="status-badge">

                                            <?= e(
                                                $vo[
                                                    'vendor_status'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= e(
                                            $vo[
                                                'tracking_number'
                                            ] ?: '-'
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


        <?php endif; ?>



        <!-- =================================================
             ALL ORDERS
        ================================================== -->


        <section class="admin-card">


            <div class="card-header">


                <div>


                    <h2>
                        All Orders
                    </h2>


                    <p>
                        Customer orders and payment information.
                    </p>


                </div>


            </div>



            <div class="admin-table-wrapper">


                <table class="admin-table">


                    <thead>


                        <tr>


                            <th>
                                Order ID
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
                        empty($orders)
                    ): ?>


                        <tr>

                            <td colspan="9">

                                No orders found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $orders
                            as $order
                        ): ?>


                            <tr>


                                <!-- ORDER ID -->


                                <td>

                                    #

                                    <?= e(
                                        $order[
                                            'order_id'
                                        ]
                                    ) ?>

                                </td>



                                <!-- CUSTOMER -->


                                <td>


                                    <strong>

                                        <?= e(
                                            $order[
                                                'customer_name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= e(
                                            $order[
                                                'customer_email'
                                            ]
                                        ) ?>

                                    </small>


                                </td>



                                <!-- DATE -->


                                <td>

                                    <?= e(
                                        $order[
                                            'order_date'
                                        ]
                                    ) ?>

                                </td>



                                <!-- ITEMS -->


                                <td>

                                    <?= e(
                                        $order[
                                            'total_items'
                                        ]
                                    ) ?>

                                </td>



                                <!-- VENDORS -->


                                <td>

                                    <?= e(
                                        $order[
                                            'vendor_count'
                                        ]
                                    ) ?>

                                </td>



                                <!-- TOTAL -->


                                <td>


                                    <strong>

                                        <?= money(
                                            $order[
                                                'total_amount'
                                            ]
                                        ) ?>

                                    </strong>


                                </td>



                                <!-- PAYMENT -->


                                <td>


                                    <span class="status-badge">

                                        <?= e(
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
                                        action=""
                                    >


                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= e(
                                                $order[
                                                    'order_id'
                                                ]
                                            ) ?>"
                                        >


                                        <select
                                            name="order_status"
                                            onchange="this.form.submit()"
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
                                                    value="<?= e(
                                                        $status
                                                    ) ?>"
                                                    <?= (
                                                        $order[
                                                            'order_status'
                                                        ] === $status
                                                    )
                                                        ? 'selected'
                                                        : ''
                                                    ?>
                                                >

                                                    <?= e(
                                                        $status
                                                    ) ?>

                                                </option>


                                            <?php endforeach; ?>


                                        </select>


                                        <input
                                            type="hidden"
                                            name="update_status"
                                            value="1"
                                        >


                                    </form>


                                </td>



                                <!-- ACTION -->


                                <td>


                                    <a
                                        href="orders.php?view=<?= e(
                                            $order[
                                                'order_id'
                                            ]
                                        ) ?>"
                                        class="admin-btn small"
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


    </main>


</div>


</body>


</html>