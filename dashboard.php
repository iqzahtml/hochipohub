<?php

session_start();

require_once "database/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



$orders=$conn->prepare("


SELECT COUNT(*) AS total


FROM orders


WHERE customer_id=?



");



$orders->bind_param(

"i",

$user_id

);



$orders->execute();



$total_orders=$orders->get_result()->fetch_assoc()['total'];




$wishlist=$conn->prepare("


SELECT COUNT(*) AS total


FROM wishlist


WHERE user_id=?



");



$wishlist->bind_param(

"i",

$user_id

);



$wishlist->execute();



$total_wishlist=$wishlist->get_result()->fetch_assoc()['total'];



?>



<!DOCTYPE html>

<html>


<head>

<title>
Dashboard
</title>


<link rel="stylesheet" href="css/style.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<h1>
Dashboard
</h1>



<div class="dashboard-cards">



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
Wishlist
</h3>


<p>

<?= $total_wishlist; ?>

</p>


</div>



</div>



</body>


</html>