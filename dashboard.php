<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CUSTOMER ONLY
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'customer'
) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    }

    if ($_SESSION['role'] === 'vendor') {
        header("Location: seller/dashboard.php");
        exit;
    }

    header("Location: index.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET CUSTOMER INFORMATION
|--------------------------------------------------------------------------
*/

$db = getDB();

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        phone
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();

    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET ORDER COUNT
|--------------------------------------------------------------------------
*/

$orderCount = 0;

$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM orders
    WHERE customer_id = ?
");

$stmt->execute([$userId]);

$orderCount = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| GET WISHLIST COUNT
|--------------------------------------------------------------------------
|
| Kalau table wishlist ada.
|
*/

$wishlistCount = 0;

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    $wishlistCount = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $wishlistCount = 0;
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
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >

</head>

<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>


<div class="dashboard-layout">

    <?php

    if (file_exists(
        __DIR__ . '/includes/customer_sidebar.php'
    )) {

        include __DIR__ . '/includes/customer_sidebar.php';

    }

    ?>


    <main class="dashboard-content">

        <div class="page-header">

            <div>

                <h1>
                    Welcome,
                    <?= htmlspecialchars($user['name']) ?>
                </h1>

                <p>
                    Welcome to your HochipoHub customer dashboard.
                </p>

            </div>

        </div>


        <!-- STATISTICS -->

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
                    Account
                </span>

                <strong>
                    Customer
                </strong>

            </div>

        </div>


        <!-- CUSTOMER MENU -->

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
                href="catalog.php"
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
                href="cart.php"
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
                href="order.php"
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
                href="wishlist.php"
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
                href="profile.php"
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


        <!-- CUSTOMER INFORMATION -->

        <div
            style="
                margin-top:30px;
                background:#fff;
                padding:25px;
                border-radius:16px;
            "
        >

            <h2>
                Account Information
            </h2>

            <p>
                <strong>Name:</strong>
                <?= htmlspecialchars($user['name']) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= htmlspecialchars($user['email']) ?>
            </p>

            <?php if (!empty($user['phone'])): ?>

                <p>
                    <strong>Phone:</strong>
                    <?= htmlspecialchars($user['phone']) ?>
                </p>

            <?php endif; ?>

        </div>

    </main>

</div>


<?php include __DIR__ . '/includes/footer.php'; ?>

</body>

</html>