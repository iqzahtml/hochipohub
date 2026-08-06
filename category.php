<?php

require_once "database/db.php";



$category_id=$_GET['id'];



$category=$conn->prepare("


SELECT category_name

FROM categories

WHERE category_id=?


");


$category->bind_param(

"i",

$category_id

);



$category->execute();


$category_name=$category->get_result()->fetch_assoc();





$stmt=$conn->prepare("


SELECT *


FROM products


WHERE category_id=?


AND status='Available'


ORDER BY product_id DESC



");


$stmt->bind_param(

"i",

$category_id

);



$stmt->execute();



$products=$stmt->get_result();



?>


<!DOCTYPE html>

<html>


<head>

<title>

<?= $category_name['category_name']; ?>

</title>


<link rel="stylesheet" href="css/product.css">


</head>



<body>



<?php include "includes/navbar.php"; ?>



<h1>

<?= htmlspecialchars($category_name['category_name']); ?>

</h1>



<div class="product-grid">


<?php while($row=$products->fetch_assoc()){ ?>



<div class="product-card">


<img src="uploads/products/<?= $row['image']; ?>">


<h3>

<?= htmlspecialchars($row['product_name']); ?>

</h3>


<p>

RM <?= number_format($row['price'],2); ?>

</p>



<a href="product_details.php?id=<?= $row['product_id']; ?>">

View

</a>


</div>



<?php } ?>



</div>



</body>


</html>