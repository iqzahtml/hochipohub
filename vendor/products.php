<?php


session_start();


require_once "../config/db.php";



if(!isset($_SESSION['user_id'])){


header("Location: ../auth/login.php");


exit();


}



$user_id=$_SESSION['user_id'];




$stmt=$conn->prepare("

SELECT vendor_id

FROM vendors

WHERE user_id=?

");



$stmt->bind_param(

"i",

$user_id

);



$stmt->execute();



$vendor=$stmt->get_result()->fetch_assoc();




$products=$conn->prepare("

SELECT *

FROM products

WHERE vendor_id=?

ORDER BY product_id DESC

");



$products->bind_param(

"i",

$vendor['vendor_id']

);



$products->execute();



$result=$products->get_result();



?>



<!DOCTYPE html>

<html>

<head>

<title>
My Products
</title>


<link rel="stylesheet" href="../assets/css/vendor.css">


</head>



<body>


<h1>
My Products
</h1>


<a href="add_product.php">

Add Product

</a>



<table border="1">


<tr>

<th>
Image
</th>

<th>
Name
</th>

<th>
Price
</th>

<th>
Stock
</th>

<th>
Action
</th>


</tr>



<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>


<img src="../assets/uploads/products/<?= $row['image']; ?>"

width="70">


</td>



<td>

<?= $row['product_name']; ?>

</td>



<td>

RM <?= $row['price']; ?>

</td>




<td>

<?= $row['stock_quantity']; ?>

</td>



<td>


<a href="edit_product.php?id=<?= $row['product_id']; ?>">

Edit

</a>



<a href="delete_product.php?id=<?= $row['product_id']; ?>">

Delete

</a>


</td>


</tr>


<?php } ?>


</table>


</body>

</html>