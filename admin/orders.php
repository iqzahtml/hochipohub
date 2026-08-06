<?php

session_start();

require_once "../config/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}



if(isset($_GET['status'])){


$id=$_GET['id'];

$status=$_GET['status'];



$stmt=$conn->prepare("


UPDATE orders


SET status=?


WHERE order_id=?


");



$stmt->bind_param(

"si",

$status,

$id

);



$stmt->execute();



header("Location: orders.php");

exit();


}





$orders=$conn->query("


SELECT


orders.*,


users.name



FROM orders



JOIN users

ON orders.user_id=users.user_id



ORDER BY order_id DESC



");



?>



<!DOCTYPE html>

<html>

<head>

<title>
Orders
</title>


<link rel="stylesheet"

href="../assets/css/admin.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>

Orders Management

</h1>



<table class="admin-table">


<tr>


<th>ID</th>

<th>Customer</th>

<th>Total</th>

<th>Status</th>

<th>Action</th>


</tr>



<?php while($row=$orders->fetch_assoc()){ ?>


<tr>


<td>

<?php echo $row['order_id']; ?>

</td>



<td>

<?php echo $row['name']; ?>

</td>



<td>

RM <?php echo $row['total_amount']; ?>

</td>



<td>

<?php echo $row['status']; ?>

</td>



<td>


<a href="?id=<?php echo $row['order_id']; ?>&status=completed">

Complete

</a>


</td>


</tr>


<?php } ?>



</table>


</div>



<?php include "../includes/footer.php"; ?>


</body>

</html>