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