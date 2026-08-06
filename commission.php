<?php

session_start();

require_once "config/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}



$user=$_SESSION['user_id'];



$vendor=$conn->query("


SELECT *

FROM vendors


WHERE user_id='$user'


")->fetch_assoc();



if(!$vendor){

echo "Vendor only";

exit();

}



$vendor_id=$vendor['vendor_id'];




$result=$conn->query("


SELECT


SUM(order_details.subtotal) AS sales



FROM order_details



JOIN products

ON order_details.product_id=products.product_id



JOIN orders

ON order_details.order_id=orders.order_id



WHERE products.vendor_id='$vendor_id'


");



$data=$result->fetch_assoc();



$sales=$data['sales'] ?? 0;



$commission=$sales*0.05;



$income=$sales-$commission;



?>



<!DOCTYPE html>

<html>


<head>


<title>

Commission

</title>



<link rel="stylesheet" href="assets/css/style.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<section class="container">



<h1>

Vendor Commission

</h1>




<div class="dashboard-card">


<h3>

Total Sales

</h3>


<p>

RM <?php echo number_format($sales,2); ?>

</p>


</div>




<div class="dashboard-card">


<h3>

Platform Commission (5%)

</h3>


<p>

RM <?php echo number_format($commission,2); ?>

</p>


</div>




<div class="dashboard-card">


<h3>

Your Earnings

</h3>


<p>

RM <?php echo number_format($income,2); ?>

</p>


</div>



</section>



<?php include "includes/footer.php"; ?>


</body>

</html>