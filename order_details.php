<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];

$order_id=$_GET['id'];




$order=$conn->prepare("


SELECT


orders.*,


users.name,


users.email



FROM orders



JOIN users


ON orders.customer_id=users.user_id



WHERE orders.order_id=?

AND orders.customer_id=?



");



$order->bind_param(

"ii",

$order_id,

$user_id

);



$order->execute();



$order_data=$order->get_result()->fetch_assoc();



if(!$order_data){

exit("Order not found");

}





$details=$conn->prepare("


SELECT


order_details.*,


products.product_name,


products.image,



vendors.business_name



FROM order_details



JOIN products


ON order_details.product_id=products.product_id



JOIN vendors


ON products.vendor_id=vendors.vendor_id



WHERE order_id=?



");



$details->bind_param(

"i",

$order_id

);



$details->execute();



$items=$details->get_result();



?>



<!DOCTYPE html>

<html>


<head>

<title>
Order Details
</title>


<link rel="stylesheet" href="../assets/css/style.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<div class="order-details">



<h1>

Order #<?= $order_id; ?>

</h1>




<p>

Customer:

<?= htmlspecialchars($order_data['name']); ?>

</p>



<p>

Status:

<?= $order_data['order_status']; ?>

</p>



<table border="1">


<tr>

<th>
Product
</th>


<th>
Vendor
</th>


<th>
Quantity
</th>


<th>
Price
</th>


<th>
Subtotal
</th>

</tr>




<?php while($row=$items->fetch_assoc()){ ?>



<tr>


<td>

<?= htmlspecialchars($row['product_name']); ?>

</td>



<td>

<?= htmlspecialchars($row['business_name']); ?>

</td>



<td>

<?= $row['quantity']; ?>

</td>



<td>

RM <?= number_format($row['unit_price'],2); ?>

</td>



<td>

RM <?= number_format($row['subtotal'],2); ?>

</td>



</tr>



<?php } ?>



</table>



</div>



</body>


</html>