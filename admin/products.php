<?php

session_start();

require_once "../config/db.php";


if($_SESSION['role']!="admin"){

exit();

}



$products=$conn->query("


SELECT


products.*,


vendors.business_name



FROM products



JOIN vendors

ON products.vendor_id = vendors.vendor_id



ORDER BY product_id DESC



");


?>



<!DOCTYPE html>

<html>

<head>

<title>
Products
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>


<h1>
Products Management
</h1>



<table border="1">


<tr>

<th>
Product
</th>

<th>
Vendor
</th>

<th>
Price
</th>

<th>
Stock
</th>

<th>
Status
</th>

</tr>



<?php while($row=$products->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['product_name']; ?>

</td>



<td>

<?= $row['business_name']; ?>

</td>



<td>

RM <?= $row['price']; ?>

</td>



<td>

<?= $row['stock_quantity']; ?>

</td>



<td>

<?= $row['status']; ?>

</td>


</tr>


<?php } ?>



</table>


</body>

</html>