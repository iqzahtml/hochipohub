<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = currentUserId();

$pageTitle = 'Dashboard';


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        profile_image,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch();


if (!$user) {

    session_destroy();

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| ORDER STATISTICS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        COUNT(*) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status = 'Processing'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS processing_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status = 'Completed'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS completed_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status = 'Cancelled'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS cancelled_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status != 'Cancelled'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_spent

    FROM orders

    WHERE customer_id = ?
");

$stmt->execute([$userId]);

$orderStats = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT COALESCE(
        SUM(quantity),
        0
    )
    FROM cart
    WHERE customer_id = ?
");

$stmt->execute([$userId]);

$cartCount = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| WISHLIST COUNT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM wishlist
    WHERE user_id = ?
");

$stmt->execute([$userId]);

$wishlistCount = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| REVIEW COUNT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM reviews
    WHERE customer_id = ?
");

$stmt->execute([$userId]);

$reviewCount = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.delivery_method,
        o.order_status,

        p.payment_status

    FROM orders o

    LEFT JOIN payments p
        ON p.order_id = o.order_id

    WHERE o.customer_id = ?

    ORDER BY o.order_date DESC

    LIMIT 5
");

$stmt->execute([$userId]);

$recentOrders = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| RECENT WISHLIST PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        w.wishlist_id,

        p.product_id,
        p.product_name,
        p.price,
        p.image,
        p.status,

        v.business_name

    FROM wishlist w

    INNER JOIN products p
        ON p.product_id = w.product_id

    INNER JOIN vendors v
        ON v.vendor_id = p.vendor_id

    WHERE w.user_id = ?

    ORDER BY w.created_at DESC

    LIMIT 4
");

$stmt->execute([$userId]);

$wishlistProducts = $stmt->fetchAll();


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>

<main class="dashboard-page">


    <!-- =====================================================
         DASHBOARD HEADER
    ====================================================== -->

    <section class="dashboard-header">

        <div class="dashboard-header-content">

            <div>

                <span class="dashboard-label">
                    MY DASHBOARD
                </span>

                <h1>
                    Welcome back,
                    <span><?= e($user['name']) ?></span> 👋
                </h1>

                <p>
                    Manage your orders, wishlist
                    and HochipoHub account.
                </p>

            </div>


            <div class="dashboard-user-badge">

                <?php if (!empty($user['profile_image'])): ?>

                    <img
                        src="<?= e($user['profile_image']) ?>"
                        alt="Profile"
                    >

                <?php else: ?>

                    <div class="dashboard-avatar">
                        <?= strtoupper(
                            substr($user['name'], 0, 1)
                        ) ?>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="dashboard-container">

        <div class="dashboard-stats">


            <div class="stat-card">

                <div class="stat-icon">
                    📦
                </div>

                <div>

                    <span>
                        Total Orders
                    </span>

                    <strong>
                        <?= (int) $orderStats['total_orders'] ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⏳
                </div>

                <div>

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?= (int) $orderStats['pending_orders'] ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🚚
                </div>

                <div>

                    <span>
                        Processing
                    </span>

                    <strong>
                        <?= (int) $orderStats['processing_orders'] ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ❤️
                </div>

                <div>

                    <span>
                        Wishlist
                    </span>

                    <strong>
                        <?= $wishlistCount ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🛒
                </div>

                <div>

                    <span>
                        Cart Items
                    </span>

                    <strong>
                        <?= $cartCount ?>
                    </strong>

                </div>

            </div>


        </div>



        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <div class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span>
                        QUICK ACCESS
                    </span>

                    <h2>
                        What do you want to do?
                    </h2>

                </div>

            </div>


            <div class="quick-actions">


                <a
                    href="<?= BASE_URL ?>catalog.php"
                    class="quick-action-card"
                >

                    <span class="quick-action-icon">
                        🛍️
                    </span>

                    <strong>
                        Browse Products
                    </strong>

                    <small>
                        Discover products from local vendors.
                    </small>

                </a>


                <a
                    href="<?= BASE_URL ?>cart.php"
                    class="quick-action-card"
                >

                    <span class="quick-action-icon">
                        🛒
                    </span>

                    <strong>
                        My Cart
                    </strong>

                    <small>
                        <?= $cartCount ?> item(s) in your cart.
                    </small>

                </a>


                <a
                    href="<?= BASE_URL ?>wishlist.php"
                    class="quick-action-card"
                >

                    <span class="quick-action-icon">
                        ❤️
                    </span>

                    <strong>
                        Wishlist
                    </strong>

                    <small>
                        <?= $wishlistCount ?> saved product(s).
                    </small>

                </a>


                <a
                    href="<?= BASE_URL ?>profile.php"
                    class="quick-action-card"
                >

                    <span class="quick-action-icon">
                        👤
                    </span>

                    <strong>
                        My Profile
                    </strong>

                    <small>
                        Manage your account information.
                    </small>

                </a>


            </div>

        </div>



        <!-- =================================================
             RECENT ORDERS
        ================================================== -->

        <div class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span>
                        ORDER ACTIVITY
                    </span>

                    <h2>
                        Recent Orders
                    </h2>

                </div>

                <a
                    href="<?= BASE_URL ?>order.php"
                    class="view-all"
                >
                    View All →
                </a>

            </div>


            <?php if (!empty($recentOrders)): ?>

                <div class="orders-table-wrapper">

                    <table class="dashboard-orders-table">

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Delivery
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($recentOrders as $order): ?>

                                <tr>

                                    <td>

                                        <a
                                            href="<?= BASE_URL ?>order_details.php?id=<?= (int) $order['order_id'] ?>"
                                        >
                                            #<?= (int) $order['order_id'] ?>
                                        </a>

                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $order['order_date']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>
                                        <?= e(
                                            $order['delivery_method']
                                        ) ?>
                                    </td>


                                    <td>

                                        RM
                                        <?= number_format(
                                            (float) $order['total_amount'],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php

                                        $paymentStatus =
                                            $order['payment_status']
                                            ?? 'Pending';

                                        ?>

                                        <span
                                            class="status-badge payment-<?= strtolower(
                                                str_replace(
                                                    ' ',
                                                    '-',
                                                    $paymentStatus
                                                )
                                            ) ?>"
                                        >
                                            <?= e($paymentStatus) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge status-<?= strtolower(
                                                $order['order_status']
                                            ) ?>"
                                        >
                                            <?= e(
                                                $order['order_status']
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-dashboard">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>
                        No orders yet
                    </h3>

                    <p>
                        Your recent orders will appear here.
                    </p>

                    <a
                        href="<?= BASE_URL ?>catalog.php"
                        class="dashboard-btn"
                    >
                        Start Shopping →
                    </a>

                </div>

            <?php endif; ?>

        </div>



        <!-- =================================================
             WISHLIST PREVIEW
        ================================================== -->

        <div class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span>
                        SAVED PRODUCTS
                    </span>

                    <h2>
                        Your Wishlist
                    </h2>

                </div>

                <a
                    href="<?= BASE_URL ?>wishlist.php"
                    class="view-all"
                >
                    View Wishlist →
                </a>

            </div>


            <?php if (!empty($wishlistProducts)): ?>

                <div class="wishlist-preview-grid">

                    <?php foreach ($wishlistProducts as $product): ?>

                        <a
                            href="<?= BASE_URL ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                            class="wishlist-preview-card"
                        >

                            <div class="wishlist-preview-image">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="<?= e($product['image']) ?>"
                                        alt="<?= e(
                                            $product['product_name']
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <div class="no-product-image">
                                        🛍️
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="wishlist-preview-content">

                                <small>
                                    <?= e(
                                        $product['business_name']
                                    ) ?>
                                </small>

                                <h3>
                                    <?= e(
                                        $product['product_name']
                                    ) ?>
                                </h3>

                                <strong>
                                    RM
                                    <?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-dashboard">

                    <div class="empty-icon">
                        ❤️
                    </div>

                    <h3>
                        Your wishlist is empty
                    </h3>

                    <p>
                        Save products you love and
                        find them here later.
                    </p>

                    <a
                        href="<?= BASE_URL ?>catalog.php"
                        class="dashboard-btn"
                    >
                        Explore Products →
                    </a>

                </div>

            <?php endif; ?>

        </div>



        <!-- =================================================
             ACCOUNT SUMMARY
        ================================================== -->

        <div class="dashboard-account-card">

            <div>

                <span>
                    ACCOUNT
                </span>

                <h2>
                    <?= e($user['name']) ?>
                </h2>

                <p>
                    <?= e($user['email']) ?>
                </p>

            </div>


            <div class="account-meta">

                <div>

                    <span>
                        Role
                    </span>

                    <strong>
                        <?= ucfirst(
                            e($user['role'])
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Status
                    </span>

                    <strong>
                        <?= ucfirst(
                            e($user['status'])
                        ) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Reviews
                    </span>

                    <strong>
                        <?= $reviewCount ?>
                    </strong>

                </div>

            </div>


            <a
                href="<?= BASE_URL ?>profile.php"
                class="dashboard-btn"
            >
                Manage Profile →
            </a>

        </div>


    </section>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>