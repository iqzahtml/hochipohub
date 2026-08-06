<?php


session_start();


require_once "database/db.php";



if($_SESSION['role']!="admin"){

exit();

}



$inventory=$conn->query("


SELECT


inventory.*,


products.product_name



FROM inventory



JOIN products

ON inventory.product_id = products.product_id



ORDER BY inventory_id DESC



");



?>


<!DOCTYPE html>

<html>

<head>

<title>
Inventory
</title>


<link rel="stylesheet" href="css/admin.css">

</head>


<body>


<h1>
Inventory Management
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




<?php while($row=$inventory->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['product_name']; ?>

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