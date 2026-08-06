<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



$orders=$conn->prepare("


SELECT


orders.*



FROM orders



WHERE customer_id=?



ORDER BY order_id DESC



");



$orders->bind_param(

"i",

$user_id

);



$orders->execute();



$result=$orders->get_result();



?>


<!DOCTYPE html>

<html>


<head>

<title>
My Orders
</title>


<link rel="stylesheet" href="../assets/css/style.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<div class="order-container">



<h1>
My Orders
</h1>




<?php while($row=$result->fetch_assoc()){ ?>



<div class="order-card">



<h3>

Order #<?= $row['order_id']; ?>

</h3>



<p>

Date:

<?= $row['order_date']; ?>

</p>




<p>

Total:

RM <?= number_format($row['total_amount'],2); ?>

</p>



<p>

Status:

<?= $row['order_status']; ?>

</p>



<a href="order_details.php?id=<?= $row['order_id']; ?>">


View Details


</a>



</div>



<?php } ?>



</div>



</body>


</html>