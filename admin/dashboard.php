<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){

    header("Location: auth/login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| TOTAL USERS
|--------------------------------------------------------------------------
*/

$user=$conn->query("

SELECT COUNT(*) AS total

FROM users

");


$total_users=$user->fetch_assoc()['total'];



/*
|--------------------------------------------------------------------------
| TOTAL VENDORS
|--------------------------------------------------------------------------
*/


$vendor=$conn->query("

SELECT COUNT(*) AS total

FROM vendors

WHERE approval_status='Approved'

");


$total_vendors=$vendor->fetch_assoc()['total'];



/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTS
|--------------------------------------------------------------------------
*/


$product=$conn->query("

SELECT COUNT(*) AS total

FROM products

");


$total_products=$product->fetch_assoc()['total'];



/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/


$sales=$conn->query("

SELECT SUM(total_amount) AS total

FROM orders

WHERE order_status='Completed'

");



$total_sales=$sales->fetch_assoc()['total'] ?? 0;



?>


<!DOCTYPE html>

<html>

<head>

<title>
Admin Dashboard
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>


<?php include "includes/navbar.php"; ?>


<div class="admin-layout">


<?php include "includes/admin_sidebar.php"; ?>


<main class="admin-content">


<h1>
Admin Dashboard
</h1>



<div class="dashboard-cards">



<div class="dashboard-card">

<h3>
Users
</h3>


<p>
<?= $total_users ?>
</p>


</div>




<div class="dashboard-card">

<h3>
Vendors
</h3>


<p>
<?= $total_vendors ?>
</p>


</div>




<div class="dashboard-card">

<h3>
Products
</h3>


<p>
<?= $total_products ?>
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



</body>

</html>