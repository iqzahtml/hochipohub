<?php

require_once __DIR__ . '/config.php';

requireLogin();


/*
|--------------------------------------------------------------------------
| REDIRECT OTHER ROLES
|--------------------------------------------------------------------------
*/

$role = strtolower(
    trim(
        $_SESSION['role'] ?? ''
    )
);

if ($role === 'admin') {

    header(
        'Location: ' .
        BASE_URL .
        'admin/dashboard.php'
    );

    exit;
}

if ($role === 'vendor') {

    header(
        'Location: ' .
        BASE_URL .
        'seller/dashboard.php'
    );

    exit;
}

if ($role !== 'customer') {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

$userId = (int)
    $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        profile_image
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


if (!$user) {

    session_unset();

    session_destroy();

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PAGE VARIABLES FOR NAVBAR
|--------------------------------------------------------------------------
*/

$isLoggedIn = true;

$userName =
    $user['name'];

$userRole =
    'customer';

$currentPage =
    'dashboard.php';


/*
|--------------------------------------------------------------------------
| DEFAULT COUNTS
|--------------------------------------------------------------------------
*/

$orderCount = 0;

$wishlistCount = 0;

$cartCount = 0;


/*
|--------------------------------------------------------------------------
| GET ORDER COUNT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE customer_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $orderCount =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $orderCount = 0;

}


/*
|--------------------------------------------------------------------------
| GET WISHLIST COUNT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $wishlistCount =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $wishlistCount = 0;

}


/*
|--------------------------------------------------------------------------
| GET CART COUNT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM cart
        WHERE user_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $cartCount =
        (int) $stmt->fetchColumn();

} catch (Throwable $e) {

    $cartCount = 0;

}

?>
<?php
$pageTitle = 'Dashboard';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/customer_sidebar.php';
?>
<main class="dashboard-page">

    <div class="dashboard-container">


        <!-- =====================================================
             WELCOME
        ====================================================== -->

        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    WELCOME BACK,
                </span>

                <h1>
                    <?= e($userName) ?> 👋
                </h1>

                <p>
                    Welcome to your HochipoHub customer dashboard.
                </p>

            </div>

        </section>


        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <section class="dashboard-stats">


            <!-- ORDERS -->

            <a
                href="order.php"
                class="dashboard-stat-card"
            >

                <div class="stat-icon">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="stat-content">

                    <span>
                        My Orders
                    </span>

                    <strong>
                        <?= (int)($orderCount ?? 0) ?>
                    </strong>

                    <small>
                        View your order history
                    </small>

                </div>

            </a>


            <!-- WISHLIST -->

            <a
                href="wishlist.php"
                class="dashboard-stat-card"
            >

                <div class="stat-icon pink">

                    <i class="bi bi-heart"></i>

                </div>


                <div class="stat-content">

                    <span>
                        Wishlist
                    </span>

                    <strong>
                        <?= (int)($wishlistCount ?? 0) ?>
                    </strong>

                    <small>
                        Items in your wishlist
                    </small>

                </div>

            </a>


            <!-- CART -->

            <a
                href="cart.php"
                class="dashboard-stat-card"
            >

                <div class="stat-icon green">

                    <i class="bi bi-cart3"></i>

                </div>


                <div class="stat-content">

                    <span>
                        Shopping Cart
                    </span>

                    <strong>
                        <?= (int)($cartCount ?? 0) ?>
                    </strong>

                    <small>
                        Items in your cart
                    </small>

                </div>

            </a>


        </section>


        <!-- =====================================================
             QUICK ACTIONS
        ====================================================== -->

        <section class="quick-actions">


            <a
                href="catalog.php"
                class="quick-action"
            >

                <div class="quick-action-icon">

                    <i class="bi bi-bag"></i>

                </div>

                <div class="quick-action-content">

                    <strong>
                        Browse Products
                    </strong>

                    <span>
                        Shop Now →
                    </span>

                </div>

            </a>


            <a
                href="cart.php"
                class="quick-action"
            >

                <div class="quick-action-icon">

                    <i class="bi bi-cart3"></i>

                </div>

                <div class="quick-action-content">

                    <strong>
                        Shopping Cart
                    </strong>

                    <span>
                        View Cart →
                    </span>

                </div>

            </a>


            <a
                href="order.php"
                class="quick-action"
            >

                <div class="quick-action-icon">

                    <i class="bi bi-box-seam"></i>

                </div>

                <div class="quick-action-content">

                    <strong>
                        My Orders
                    </strong>

                    <span>
                        View Orders →
                    </span>

                </div>

            </a>


            <a
                href="wishlist.php"
                class="quick-action"
            >

                <div class="quick-action-icon">

                    <i class="bi bi-heart"></i>

                </div>

                <div class="quick-action-content">

                    <strong>
                        Wishlist
                    </strong>

                    <span>
                        View Wishlist →
                    </span>

                </div>

            </a>


            <a
                href="profile.php"
                class="quick-action"
            >

                <div class="quick-action-icon">

                    <i class="bi bi-person"></i>

                </div>

                <div class="quick-action-content">

                    <strong>
                        My Profile
                    </strong>

                    <span>
                        Edit Profile →
                    </span>

                </div>

            </a>


        </section>


        <!-- =====================================================
             ACCOUNT INFORMATION
        ====================================================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        ACCOUNT
                    </span>

                    <h2>
                        Account Information
                    </h2>

                </div>

            </div>


            <div class="account-info-grid">


                <div class="info-card">

                    <span>
                        Name
                    </span>

                    <strong>
                        <?= e($user['name'] ?? $userName) ?>
                    </strong>

                </div>


                <div class="info-card">

                    <span>
                        Email
                    </span>

                    <strong>
                        <?= e($user['email'] ?? '') ?>
                    </strong>

                </div>


                <div class="info-card">

                    <span>
                        Phone
                    </span>

                    <strong>
                        <?= e($user['phone'] ?? 'Not provided') ?>
                    </strong>

                </div>


                <div class="info-card">

                    <span>
                        Account Status
                    </span>

                    <strong>
                        <?= e(
                            ucfirst(
                                $user['status'] ?? 'Active'
                            )
                        ) ?>
                    </strong>

                </div>


            </div>

        </section>


    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Customer Dashboard | HochipoHub
    </title>

    <link
        rel="stylesheet"
        href="<?= e(BASE_URL) ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(BASE_URL) ?>css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(BASE_URL) ?>css/responsive.css"
    >

</head>

<body>

<?php
include __DIR__ .
    '/includes/navbar.php';
?>


<div class="dashboard-layout">

    <?php

    $sidebar =
        __DIR__ .
        '/includes/customer_sidebar.php';

    if (file_exists($sidebar)) {

        include $sidebar;

    }

    ?>


    <main class="dashboard-content">

        <div class="page-header">

            <div>

                <h1>
                    Welcome,
                    <?= e($user['name']) ?>
                </h1>

                <p>
                    Welcome to your HochipoHub customer dashboard.
                </p>

            </div>

        </div>


        <div class="stats-grid">

            <div class="stat-card">

                <span>
                    My Orders
                </span>

                <strong>
                    <?= $orderCount ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Wishlist
                </span>

                <strong>
                    <?= $wishlistCount ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Shopping Cart
                </span>

                <strong>
                    <?= $cartCount ?>
                </strong>

            </div>

        </div>


        <div
            style="
                margin-top:30px;
                display:grid;
                grid-template-columns:
                    repeat(auto-fit,minmax(220px,1fr));
                gap:20px;
            "
        >

            <a
                href="<?= e(BASE_URL) ?>catalog.php"
                class="stat-card"
                style="text-decoration:none;"
            >

                <span>
                    Browse Products
                </span>

                <strong>
                    Shop Now →
                </strong>

            </a>


            <a
                href="<?= e(BASE_URL) ?>cart.php"
                class="stat-card"
                style="text-decoration:none;"
            >

                <span>
                    Shopping Cart
                </span>

                <strong>
                    View Cart →
                </strong>

            </a>


            <a
                href="<?= e(BASE_URL) ?>order.php"
                class="stat-card"
                style="text-decoration:none;"
            >

                <span>
                    My Orders
                </span>

                <strong>
                    View Orders →
                </strong>

            </a>


            <a
                href="<?= e(BASE_URL) ?>wishlist.php"
                class="stat-card"
                style="text-decoration:none;"
            >

                <span>
                    Wishlist
                </span>

                <strong>
                    View Wishlist →
                </strong>

            </a>


            <a
                href="<?= e(BASE_URL) ?>profile.php"
                class="stat-card"
                style="text-decoration:none;"
            >

                <span>
                    My Profile
                </span>

                <strong>
                    Edit Profile →
                </strong>

            </a>

        </div>


        <div
            style="
                margin-top:30px;
                background:#ffffff;
                padding:25px;
                border-radius:16px;
            "
        >

            <h2>
                Account Information
            </h2>


            <p>

                <strong>
                    Name:
                </strong>

                <?= e($user['name']) ?>

            </p>


            <p>

                <strong>
                    Email:
                </strong>

                <?= e($user['email']) ?>

            </p>


            <?php if (!empty($user['phone'])): ?>

                <p>

                    <strong>
                        Phone:
                    </strong>

                    <?= e($user['phone']) ?>

                </p>

            <?php endif; ?>

        </div>

    </main>

</div>


<?php

$footer =
    __DIR__ .
    '/includes/footer.php';

if (file_exists($footer)) {

    include $footer;

}

?>

</body>

</html>