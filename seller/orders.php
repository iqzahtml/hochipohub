<?php

session_start();

require_once "../database/db.php";



if(!isset($_SESSION['user_id']) || $_SESSION['role']!="vendor"){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];




$vendor_stmt=$conn->prepare("

SELECT vendor_id

FROM vendors

WHERE user_id=?

");


$vendor_stmt->bind_param(

"i",

$user_id

);


$vendor_stmt->execute();



$vendor=$vendor_stmt->get_result()->fetch_assoc();



$vendor_id=$vendor['vendor_id'];





$orders=$conn->prepare("


SELECT


vendor_orders.vendor_order_id,


vendor_orders.order_id,


vendor_orders.subtotal,


vendor_orders.delivery_fee,


vendor_orders.vendor_status,


orders.order_date,


users.name



FROM vendor_orders



JOIN orders

ON vendor_orders.order_id = orders.order_id



JOIN users

ON orders.customer_id = users.user_id



WHERE vendor_orders.vendor_id = ?



ORDER BY vendor_orders.vendor_order_id DESC



");




$orders->bind_param(

"i",

$vendor_id

);



$orders->execute();



$result=$orders->get_result();



?>



<!DOCTYPE html>

<html>

<head>

<title>
Vendor Orders
</title>

<link rel="stylesheet" href="../assets/css/vendor.css">

</head>


<body>



<h1>
Customer Orders
</h1>




<table border="1">


<tr>

<th>
Vendor Order ID
</th>

<th>
Customer
</th>

<th>
Date
</th>

<th>
Subtotal
</th>

<th>
Status
</th>


</tr>



<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>

#<?= $row['vendor_order_id']; ?>

</td>



<td>

<?= htmlspecialchars($row['name']); ?>

</td>



<td>

<?= $row['order_date']; ?>

</td>



<td>

RM <?= number_format($row['subtotal'],2); ?>

</td>



<td>

<?= $row['vendor_status']; ?>

</td>


</tr>



<?php } ?>


</table>



</body>

</html>