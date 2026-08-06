<?php


session_start();


require_once "database/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){

exit();

}



$payments=$conn->query("


SELECT


payments.*,


orders.customer_id,


users.name



FROM payments



JOIN orders

ON payments.order_id = orders.order_id



JOIN users

ON orders.customer_id = users.user_id



ORDER BY payment_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Payment Management
</title>


<link rel="stylesheet" href="css/admin.css">


</head>


<body>


<h1>
Payments
</h1>



<table border="1">


<tr>

<th>
Payment ID
</th>

<th>
Order
</th>

<th>
Customer
</th>

<th>
Method
</th>

<th>
Amount
</th>

<th>
Status
</th>

</tr>



<?php while($row=$payments->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['payment_id']; ?>

</td>



<td>

#<?= $row['order_id']; ?>

</td>



<td>

<?= htmlspecialchars($row['name']); ?>

</td>



<td>

<?= $row['payment_method']; ?>

</td>



<td>

RM <?= number_format($row['amount'],2); ?>

</td>



<td>

<?= $row['payment_status']; ?>

</td>



</tr>



<?php } ?>



</table>



</body>


</html>