<?php

session_start();

require_once "../inventory/db.php";


if(!isset($_SESSION['user_id'])){

exit();

}


$user_id=$_SESSION['user_id'];



$stmt=$conn->prepare("


SELECT


inventory.*,


products.product_name


FROM inventory



JOIN products


ON inventory.product_id=products.product_id



JOIN vendors


ON products.vendor_id=vendors.vendor_id



WHERE vendors.user_id=?



");


$stmt->bind_param(

"i",

$user_id

);



$stmt->execute();



$result=$stmt->get_result();



?>



<!DOCTYPE html>

<html>


<head>

<title>
Inventory
</title>


</head>


<body>



<h1>
Inventory
</h1>



<table border="1">


<tr>

<th>
Product
</th>

<th>
Quantity
</th>

<th>
Updated
</th>

</tr>



<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>

<?= htmlspecialchars($row['product_name']); ?>

</td>


<td>

<?= $row['quantity']; ?>

</td>


<td>

<?= $row['last_updated']; ?>

</td>



</tr>



<?php } ?>



</table>



</body>


</html>