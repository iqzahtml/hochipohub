<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){

    header("Location: ../auth/login.php");
    exit();

}



$orders=$conn->query("

SELECT


orders.*,


users.name,


users.email



FROM orders



JOIN users

ON orders.customer_id = users.user_id



ORDER BY orders.order_id DESC


");



?>


<!DOCTYPE html>

<html>

<head>

<title>
Manage Orders
</title>


<link rel="stylesheet" href="../assets/css/admin.css">

</head>


<body>


<h1>
Order Management
</h1>



<table border="1">


<tr>

<th>
Order ID
</th>

<th>
Customer
</th>

<th>
Date
</th>

<th>
Amount
</th>

<th>
Status
</th>


</tr>



<?php while($row=$orders->fetch_assoc()){ ?>


<tr>


<td>

#<?= $row['order_id']; ?>

</td>



<td>

<?= htmlspecialchars($row['name']); ?>

<br>

<?= htmlspecialchars($row['email']); ?>

</td>




<td>

<?= $row['order_date']; ?>

</td>



<td>

RM <?= number_format($row['total_amount'],2); ?>

</td>



<td>

<?= $row['order_status']; ?>

</td>



</tr>



<?php } ?>


</table>



</body>

</html>