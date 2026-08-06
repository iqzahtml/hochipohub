<?php

session_start();

require_once "config/db.php";


if(!isset($_GET['id'])){

header("Location: order.php");

exit();

}


$order_id=$_GET['id'];



$order=$conn->query("


SELECT *

FROM orders

WHERE order_id='$order_id'


")->fetch_assoc();




$items=$conn->query("


SELECT


order_details.*,

products.product_name,

products.image


FROM order_details



JOIN products

ON order_details.product_id=products.product_id



WHERE order_id='$order_id'


");



?>


<!DOCTYPE html>

<html>


<head>


<title>Order Details</title>


<link rel="stylesheet" href="assets/css/style.css">


</head>



<body>



<?php include "includes/navbar.php"; ?>



<section class="container">


<div class="admin-panel">


<h1>

Order #<?php echo $order_id; ?>

</h1>




<p>

Status:

<?php echo $order['order_status']; ?>

</p>




<table class="admin-table">


<tr>


<th>
Product
</th>


<th>
Quantity
</th>


<th>
Price
</th>


</tr>




<?php while($item=$items->fetch_assoc()){ ?>


<tr>


<td>

<?php echo $item['product_name']; ?>

</td>



<td>

<?php echo $item['quantity']; ?>

</td>


<td>

RM <?php echo number_format($item['subtotal'],2); ?>

</td>


</tr>



<?php } ?>



</table>



<h2>

Total:
RM <?php echo number_format($order['total_amount'],2); ?>

</h2>




</div>



</section>



<?php include "includes/footer.php"; ?>


</body>

</html>