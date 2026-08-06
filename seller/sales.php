<?php

session_start();

require_once "../cdatabase/db.php";



if(!isset($_SESSION['user_id']) || $_SESSION['role']!="vendor"){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



$stmt=$conn->prepare("


SELECT vendor_id

FROM vendors

WHERE user_id=?


");



$stmt->bind_param(

"i",

$user_id

);



$stmt->execute();



$vendor=$stmt->get_result()->fetch_assoc();



$vendor_id=$vendor['vendor_id'];





$sales=$conn->prepare("


SELECT


COUNT(vendor_order_id) AS total_orders,


SUM(subtotal) AS total_sales



FROM vendor_orders



WHERE vendor_id=?



AND vendor_status != 'Cancelled'



");



$sales->bind_param(

"i",

$vendor_id

);



$sales->execute();



$data=$sales->get_result()->fetch_assoc();



?>



<!DOCTYPE html>

<html>

<head>

<title>
Sales Report
</title>


<link rel="stylesheet" href="../assets/css/vendor.css">


</head>


<body>



<h1>
Sales Report
</h1>



<div class="dashboard-cards">



<div class="dashboard-card">


<h3>
Total Orders
</h3>


<p>

<?= $data['total_orders']; ?>

</p>


</div>




<div class="dashboard-card">


<h3>
Total Sales
</h3>


<p>

RM <?= number_format($data['total_sales'] ?? 0,2); ?>

</p>


</div>



</div>



</body>

</html>