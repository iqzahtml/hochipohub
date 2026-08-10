<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";
require_once "../includes/functions.php";


$pageTitle = "Vendor Dashboard";


if (!isLoggedIn()) {

    header("Location: " . BASE_URL . "index.php");
    exit();

}


if (currentUserRole() !== 'vendor') {

    header("Location: " . BASE_URL . "index.php");
    exit();

}


$userID =
    (int) currentUserID();


/*
|--------------------------------------------------------------------------
| Get Vendor
|--------------------------------------------------------------------------
*/

$vendorQuery = $conn->prepare("

    SELECT

        vendor_id,
        business_name,
        approval_status

    FROM vendors

    WHERE user_id = ?

    LIMIT 1

");


$vendorQuery->bind_param(
    "i",
    $userID
);


$vendorQuery->execute();


$vendorResult =
    $vendorQuery->get_result();


if ($vendorResult->num_rows === 0) {

    header(
        "Location: setup_profile.php"
    );

    exit();

}


$vendor =
    $vendorResult->fetch_assoc();


$vendorID =
    (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| Product Count
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("

    SELECT COUNT(*) AS total

    FROM products

    WHERE vendor_id = ?

");


$stmt->bind_param(
    "i",
    $vendorID
);


$stmt->execute();


$productCount =
    $stmt
        ->get_result()
        ->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| Vendor Order Count
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("

    SELECT COUNT(*) AS total

    FROM vendor_orders

    WHERE vendor_id = ?

");


$stmt->bind_param(
    "i",
    $vendorID
);


$stmt->execute();


$orderCount =
    $stmt
        ->get_result()
        ->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| Vendor Sales
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("

    SELECT

        COALESCE(
            SUM(subtotal),
            0
        ) AS total

    FROM vendor_orders

    WHERE vendor_id = ?

    AND vendor_status != 'Cancelled'

");


$stmt->bind_param(
    "i",
    $vendorID
);


$stmt->execute();


$totalSales =
    $stmt
        ->get_result()
        ->fetch_assoc()['total'];

?>

<?php include "../includes/header.php"; ?>

<section class="dashboard-page">

    <div class="page-title">

        <h1>
            Vendor Dashboard
        </h1>

        <p>
            Welcome back,
            <?= htmlspecialchars(
                $vendor['business_name']
            ); ?>
        </p>

        <p>
            Approval Status:
            <strong>
                <?= htmlspecialchars(
                    $vendor['approval_status']
                ); ?>
            </strong>
        </p>

    </div>


    <div class="dashboard-card-grid">

        <div class="dashboard-card">

            <h3>
                Products
            </h3>

            <strong>
                <?= (int)$productCount; ?>
            </strong>

        </div>


        <div class="dashboard-card">

            <h3>
                Vendor Orders
            </h3>

            <strong>
                <?= (int)$orderCount; ?>
            </strong>

        </div>


        <div class="dashboard-card">

            <h3>
                Sales
            </h3>

            <strong>
                RM <?= number_format(
                    $totalSales,
                    2
                ); ?>
            </strong>

        </div>

    </div>


    <div class="dashboard-section">

        <h2>
            Quick Actions
        </h2>

        <a
            href="add_product.php"
            class="btn-primary"
        >
            Add Product
        </a>

        <a
            href="products.php"
            class="btn-primary"
        >
            Manage Products
        </a>

        <a
            href="orders.php"
            class="btn-primary"
        >
            View Orders
        </a>

    </div>

</section>

<?php include "../includes/footer.php"; ?>