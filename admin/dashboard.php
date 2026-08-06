<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


$user_id=$_SESSION['user_id'];



$user=$conn->query("

SELECT *

FROM users

WHERE user_id='$user_id'

")->fetch_assoc();



if($user['role']!="admin"){

    echo "Access denied";
    exit();

}




$totalUsers=$conn->query("

SELECT COUNT(*) AS total

FROM users

")->fetch_assoc()['total'];




$totalProducts=$conn->query("

SELECT COUNT(*) AS total

FROM products

")->fetch_assoc()['total'];




$totalOrders=$conn->query("

SELECT COUNT(*) AS total

FROM orders

")->fetch_assoc()['total'];




$totalVendors=$conn->query("

SELECT COUNT(*) AS total

FROM vendors

")->fetch_assoc()['total'];



?>

<!DOCTYPE html>

<html>

<head>

<title>Admin Dashboard</title>


<link rel="stylesheet" href="../assets/css/style.css">

<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>


<?php include "../includes/navbar.php"; ?>



<section class="dashboard-page">


<div class="dashboard-container">


<h1>
Admin Dashboard
</h1>



<div class="dashboard-grid">



<div class="dashboard-card">

<h3>
Users
</h3>

<p>
<?php echo $totalUsers; ?>
</p>


<a href="users.php">
Manage Users
</a>

</div>





<div class="dashboard-card">

<h3>
Products
</h3>

<p>
<?php echo $totalProducts; ?>
</p>


<a href="products.php">
Manage Products
</a>

</div>





<div class="dashboard-card">

<h3>
Orders
</h3>

<p>
<?php echo $totalOrders; ?>
</p>


<a href="orders.php">
View Orders
</a>

</div>





<div class="dashboard-card">

<h3>
Vendors
</h3>

<p>
<?php echo $totalVendors; ?>
</p>


<a href="vendors.php">
Manage Vendors
</a>

</div>



</div>



</div>

</section>



<?php include "../includes/footer.php"; ?>


</body>

</html>