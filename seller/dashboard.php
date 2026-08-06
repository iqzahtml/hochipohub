<?php

session_start();

require_once "database/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "vendor"){

    header("Location: auth/login.php");
    exit();

}


$user_id = $_SESSION['user_id'];



$vendor_query = $conn->prepare("

SELECT *

FROM vendors

WHERE user_id = ?

");


$vendor_query->bind_param(

    "i",

    $user_id

);


$vendor_query->execute();


$vendor = $vendor_query->get_result()->fetch_assoc();



if(!$vendor){

    header("Location: setup_profile.php");

    exit();

}



$vendor_id = $vendor['vendor_id'];




/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTS
|--------------------------------------------------------------------------
*/

$product_query = $conn->prepare("

SELECT COUNT(*) AS total

FROM products

WHERE vendor_id = ?

");


$product_query->bind_param(

    "i",

    $vendor_id

);


$product_query->execute();


$total_products = $product_query->get_result()->fetch_assoc()['total'];




/*
|--------------------------------------------------------------------------
| TOTAL VENDOR ORDERS
|--------------------------------------------------------------------------
*/


$order_query = $conn->prepare("

SELECT COUNT(*) AS total

FROM vendor_orders

WHERE vendor_id = ?

");


$order_query->bind_param(

    "i",

    $vendor_id

);


$order_query->execute();


$total_orders = $order_query->get_result()->fetch_assoc()['total'];




/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/


$sales_query = $conn->prepare("

SELECT SUM(subtotal) AS total

FROM vendor_orders

WHERE vendor_id = ?

AND vendor_status != 'Cancelled'

");


$sales_query->bind_param(

    "i",

    $vendor_id

);


$sales_query->execute();



$total_sales = $sales_query->get_result()->fetch_assoc()['total'] ?? 0;



?>


<!DOCTYPE html>

<html>

<head>

<title>
Vendor Dashboard
</title>


<link rel="stylesheet" href="css/vendor.css">


</head>


<body>


<?php include "includes/navbar.php"; ?>


<div class="dashboard-container">


<?php include "includes/vendor_sidebar.php"; ?>



<main class="dashboard-content">


<h1>

Welcome,

<?= htmlspecialchars($vendor['business_name']); ?>

</h1>



<div class="dashboard-cards">



<div class="dashboard-card">

<h3>
Products
</h3>


<p>

<?= $total_products; ?>

</p>


</div>




<div class="dashboard-card">

<h3>
Orders
</h3>


<p>

<?= $total_orders; ?>

</p>


</div>





<div class="dashboard-card">

<h3>
Sales
</h3>


<p>

RM <?= number_format($total_sales,2); ?>

</p>


</div>




</div>


</main>


</div>



<?php include "includes/footer.php"; ?>


</body>

</html>