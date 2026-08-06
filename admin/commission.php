<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



/*
|--------------------------------------------------------------------------
| GET COMMISSION DATA
|--------------------------------------------------------------------------
*/


$commission=$conn->query("


SELECT


orders.order_id,

users.name,

orders.total_amount,

commission.amount,

commission.status



FROM commission



JOIN orders

ON commission.order_id = orders.order_id



JOIN users

ON orders.user_id = users.user_id



ORDER BY commission.commission_id DESC



");



?>

<!DOCTYPE html>

<html>

<head>

<title>Commission Management</title>

<link rel="stylesheet" href="../assets/css/admin.css">

</head>


<body>


<?php include "../includes/navbar.php"; ?>


<div class="dashboard-container">


<h1>
Commission Management
</h1>



<table class="admin-table">


<tr>

<th>
Order ID
</th>

<th>
Customer
</th>

<th>
Order Amount
</th>

<th>
Commission
</th>

<th>
Status
</th>


</tr>



<?php while($row=$commission->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['order_id']; ?>

</td>


<td>

<?= $row['name']; ?>

</td>


<td>

RM <?= $row['total_amount']; ?>

</td>


<td>

RM <?= $row['amount']; ?>

</td>


<td>

<?= $row['status']; ?>

</td>


</tr>


<?php } ?>


</table>


</div>


<?php include "../includes/footer.php"; ?>


</body>

</html>