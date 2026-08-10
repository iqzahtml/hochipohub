<?php
require_once '../database/db.php';
require_once '../includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT vendor_id, business_name, approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$vendor = $result->fetch_assoc();

$stmt->close();

if (!$vendor) {
    header("Location: setup_profile.php");
    exit;
}

$vendor_id = (int) $vendor['vendor_id'];

/*
|--------------------------------------------------------------------------
| UPDATE VENDOR ORDER STATUS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $vendor_order_id = isset($_POST['vendor_order_id'])
        ? (int) $_POST['vendor_order_id']
        : 0;

    $new_status = trim($_POST['vendor_status'] ?? '');

    $allowed_status = [
        'Pending',
        'Processing',
        'Ready',
        'Shipped',
        'Completed',
        'Cancelled'
    ];

    if (
        $vendor_order_id > 0 &&
        in_array($new_status, $allowed_status, true)
    ) {

        /*
        |--------------------------------------------------------------------------
        | VERIFY THIS ORDER BELONGS TO CURRENT VENDOR
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("
            SELECT vendor_order_id
            FROM vendor_orders
            WHERE vendor_order_id = ?
            AND vendor_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ii",
            $vendor_order_id,
            $vendor_id
        );

        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();

        $stmt->close();

        if ($exists) {

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */
            if ($new_status === 'Completed') {

                $stmt = $conn->prepare("
                    UPDATE vendor_orders
                    SET
                        vendor_status = ?,
                        completed_at = NOW()
                    WHERE vendor_order_id = ?
                    AND vendor_id = ?
                ");

                $stmt->bind_param(
                    "sii",
                    $new_status,
                    $vendor_order_id,
                    $vendor_id
                );

            } else {

                $stmt = $conn->prepare("
                    UPDATE vendor_orders
                    SET
                        vendor_status = ?,
                        completed_at = NULL
                    WHERE vendor_order_id = ?
                    AND vendor_id = ?
                ");

                $stmt->bind_param(
                    "sii",
                    $new_status,
                    $vendor_order_id,
                    $vendor_id
                );
            }

            $stmt->execute();
            $stmt->close();

            header("Location: orders.php?success=status_updated");
            exit;
        }
    }

    header("Location: orders.php?error=invalid_status");
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE TRACKING NUMBER
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tracking'])) {

    $vendor_order_id = isset($_POST['vendor_order_id'])
        ? (int) $_POST['vendor_order_id']
        : 0;

    $tracking_number = trim($_POST['tracking_number'] ?? '');

    if ($vendor_order_id > 0) {

        $stmt = $conn->prepare("
            UPDATE vendor_orders
            SET tracking_number = ?
            WHERE vendor_order_id = ?
            AND vendor_id = ?
        ");

        $stmt->bind_param(
            "sii",
            $tracking_number,
            $vendor_order_id,
            $vendor_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: orders.php?success=tracking_updated");
        exit;
    }

    header("Location: orders.php?error=invalid_order");
    exit;
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/
$status_filter = trim($_GET['status'] ?? '');

$allowed_filter = [
    'Pending',
    'Processing',
    'Ready',
    'Shipped',
    'Completed',
    'Cancelled'
];

if (
    $status_filter !== '' &&
    !in_array($status_filter, $allowed_filter, true)
) {
    $status_filter = '';
}

/*
|--------------------------------------------------------------------------
| GET ORDERS
|--------------------------------------------------------------------------
*/
$orders = [];

if ($status_filter !== '') {

    $stmt = $conn->prepare("
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
        AND vo.vendor_status = ?

        ORDER BY vo.created_at DESC
    ");

    $stmt->bind_param(
        "is",
        $vendor_id,
        $status_filter
    );

} else {

    $stmt = $conn->prepare("
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

        ORDER BY vo.created_at DESC
    ");

    $stmt->bind_param(
        "i",
        $vendor_id
    );
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| GET ITEMS FOR EACH VENDOR ORDER
|--------------------------------------------------------------------------
*/
foreach ($orders as &$order) {

    $order['items'] = [];

    $stmt = $conn->prepare("
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

        ORDER BY od.order_detail_id ASC
    ");

    $stmt->bind_param(
        "ii",
        $order['order_id'],
        $vendor_id
    );

    $stmt->execute();

    $items_result = $stmt->get_result();

    while ($item = $items_result->fetch_assoc()) {
        $order['items'][] = $item;
    }

    $stmt->close();
}

unset($order);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Orders | Seller | HochipoHub</title>

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

</head>

<body>

<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">

    <?php include '../includes/vendor_sidebar.php'; ?>

    <main class="dashboard-content">

        <div class="page-header">

            <div>
                <h1>My Orders</h1>

                <p>
                    Manage orders containing your products.
                </p>
            </div>

        </div>


        <?php if (isset($_GET['success'])): ?>

            <div class="alert alert-success">

                <?php if ($_GET['success'] === 'status_updated'): ?>

                    Order status updated successfully.

                <?php elseif ($_GET['success'] === 'tracking_updated'): ?>

                    Tracking number updated successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['error'])): ?>

            <div class="alert alert-danger">

                Unable to process the requested action.

            </div>

        <?php endif; ?>


        <!-- FILTER -->

        <div class="filter-bar">

            <a
                href="orders.php"
                class="<?= $status_filter === '' ? 'active' : '' ?>"
            >
                All
            </a>

            <?php foreach ($allowed_filter as $status): ?>

                <a
                    href="?status=<?= urlencode($status) ?>"
                    class="<?= $status_filter === $status ? 'active' : '' ?>"
                >
                    <?= htmlspecialchars($status) ?>
                </a>

            <?php endforeach; ?>

        </div>


        <?php if (empty($orders)): ?>

            <div class="empty-state">

                <h3>No orders found</h3>

                <p>
                    You don't have any orders matching this filter.
                </p>

            </div>

        <?php else: ?>


            <div class="orders-list">

                <?php foreach ($orders as $order): ?>

                    <div class="order-card">

                        <div class="order-card-header">

                            <div>

                                <strong>
                                    Order #<?= (int) $order['order_id'] ?>
                                </strong>

                                <small>
                                    Vendor Order
                                    #<?= (int) $order['vendor_order_id'] ?>
                                </small>

                            </div>

                            <span class="status-badge">

                                <?= htmlspecialchars(
                                    $order['vendor_status']
                                ) ?>

                            </span>

                        </div>


                        <div class="order-customer">

                            <strong>
                                Customer
                            </strong>

                            <p>
                                <?= htmlspecialchars(
                                    $order['customer_name']
                                ) ?>
                            </p>

                            <p>
                                <?= htmlspecialchars(
                                    $order['customer_email']
                                ) ?>
                            </p>

                            <?php if (!empty($order['customer_phone'])): ?>

                                <p>
                                    <?= htmlspecialchars(
                                        $order['customer_phone']
                                    ) ?>
                                </p>

                            <?php endif; ?>

                        </div>


                        <div class="order-items">

                            <h4>Items</h4>

                            <?php foreach ($order['items'] as $item): ?>

                                <div class="order-item">

                                    <?php if (!empty($item['image'])): ?>

                                        <img
                                            src="../uploads/products/<?= htmlspecialchars($item['image']) ?>"
                                            alt="<?= htmlspecialchars($item['product_name']) ?>"
                                        >

                                    <?php endif; ?>

                                    <div>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $item['product_name']
                                            ) ?>
                                        </strong>

                                        <p>
                                            <?= (int) $item['quantity'] ?>
                                            × RM
                                            <?= number_format(
                                                (float)$item['unit_price'],
                                                2
                                            ) ?>
                                        </p>

                                    </div>

                                    <strong>
                                        RM
                                        <?= number_format(
                                            (float)$item['subtotal'],
                                            2
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div class="order-summary">

                            <div>
                                <span>Subtotal</span>

                                <strong>
                                    RM
                                    <?= number_format(
                                        (float)$order['subtotal'],
                                        2
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Delivery Fee</span>

                                <strong>
                                    RM
                                    <?= number_format(
                                        (float)$order['delivery_fee'],
                                        2
                                    ) ?>
                                </strong>
                            </div>

                        </div>


                        <div class="order-info">

                            <p>
                                <strong>Delivery:</strong>
                                <?= htmlspecialchars(
                                    $order['delivery_method']
                                ) ?>
                            </p>

                            <?php if (!empty($order['delivery_address'])): ?>

                                <p>
                                    <strong>Address:</strong>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $order['delivery_address']
                                        )
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <p>
                                <strong>Order Date:</strong>
                                <?= htmlspecialchars(
                                    $order['order_date']
                                ) ?>
                            </p>

                        </div>


                        <!-- UPDATE STATUS -->

                        <form
                            method="POST"
                            class="order-action-form"
                        >

                            <input
                                type="hidden"
                                name="vendor_order_id"
                                value="<?= (int)$order['vendor_order_id'] ?>"
                            >

                            <label>
                                Update Status
                            </label>

                            <select
                                name="vendor_status"
                                required
                            >

                                <?php foreach ($allowed_filter as $status): ?>

                                    <option
                                        value="<?= htmlspecialchars($status) ?>"
                                        <?= $order['vendor_status'] === $status
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= htmlspecialchars($status) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button
                                type="submit"
                                name="update_status"
                                class="btn btn-primary"
                            >
                                Update
                            </button>

                        </form>


                        <!-- TRACKING -->

                        <form
                            method="POST"
                            class="order-action-form"
                        >

                            <input
                                type="hidden"
                                name="vendor_order_id"
                                value="<?= (int)$order['vendor_order_id'] ?>"
                            >

                            <label>
                                Tracking Number
                            </label>

                            <input
                                type="text"
                                name="tracking_number"
                                value="<?= htmlspecialchars(
                                    $order['tracking_number'] ?? ''
                                ) ?>"
                                placeholder="Enter tracking number"
                            >

                            <button
                                type="submit"
                                name="update_tracking"
                                class="btn btn-secondary"
                            >
                                Save Tracking
                            </button>

                        </form>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>