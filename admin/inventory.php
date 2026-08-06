<?php

session_start();

require_once "../config/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}



$products=$conn->query("


SELECT


product_id,

product_name,

stock



FROM products



ORDER BY stock ASC



");


?>


<!DOCTYPE html>

<html>

<head>

<title>
Inventory
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>


<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>
Inventory Management
</h1>



<table class="admin-table">


<tr>

<th>
Product ID
</th>

<th>
Product
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

<?= $row['product_id']; ?>

</td>


<td>

<?= $row['product_name']; ?>

</td>



<td>

<?= $row['stock']; ?>

</td>



<td>


<?php

if($row['stock'] <= 5){

echo "<span class='stock-low'>Low Stock</span>";

}

else{

echo "<span class='stock-ok'>Available</span>";

}


?>


</td>


</tr>


<?php } ?>


</table>


</div>


<?php include "../includes/footer.php"; ?>


</body>

</html>