<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";
require_once "../includes/functions.php";



$pageTitle = "Vendor Dashboard";



requireRole('vendor');



$userID = currentUserID();



/*
|--------------------------------------------------------------------------
| Get Vendor
|--------------------------------------------------------------------------
*/

$vendorQuery = $conn->prepare("

    SELECT

        vendor_id,
        business_name

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
    $vendor['vendor_id'];





/*
|--------------------------------------------------------------------------
| Product Count
|--------------------------------------------------------------------------
*/

$productQuery = $conn->prepare("

    SELECT COUNT(*) AS total

    FROM products

    WHERE vendor_id = ?

");

$productQuery->bind_param(
    "i",
    $vendorID
);

$productQuery->execute();

$productCount =
    $productQuery
        ->get_result()
        ->fetch_assoc()['total'];





/*
|--------------------------------------------------------------------------
| Total Orders
|--------------------------------------------------------------------------
*/

$orderQuery = $conn->prepare("

    SELECT COUNT(DISTINCT order_details.order_id) AS total

    FROM order_details

    INNER JOIN products

        ON order_details.product_id =
           products.product_id

    WHERE products.vendor_id = ?

");

$orderQuery->bind_param(
    "i",
    $vendorID
);

$orderQuery->execute();

$orderCount =
    $orderQuery
        ->get_result()
        ->fetch_assoc()['total'];





/*
|--------------------------------------------------------------------------
| Total Sales
|--------------------------------------------------------------------------
*/

$salesQuery = $conn->prepare("

    SELECT

        COALESCE(
            SUM(
                order_details.quantity *
                order_details.price
            ),
            0
        ) AS total

    FROM order_details

    INNER JOIN products

        ON order_details.product_id =
           products.product_id

    WHERE products.vendor_id = ?

");

$salesQuery->bind_param(
    "i",
    $vendorID
);

$salesQuery->execute();

$totalSales =
    $salesQuery
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
<?= htmlspecialchars($vendor['business_name']); ?>
</p>

</div>





<div class="dashboard-card-grid">



<div class="dashboard-card">

<h3>
Products
</h3>

<strong>
<?= $productCount; ?>
</strong>

</div>




<div class="dashboard-card">

<h3>
Orders
</h3>

<strong>
<?= $orderCount; ?>
</strong>

</div>




<div class="dashboard-card">

<h3>
Total Sales
</h3>

<strong>
RM <?= number_format($totalSales, 2); ?>
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