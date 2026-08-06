<?php

require_once "config/db.php";


$id=$_GET['id'] ?? 0;



$category=$conn->query("


SELECT *

FROM categories

WHERE category_id='$id'


")->fetch_assoc();




$products=$conn->query("


SELECT *

FROM products


WHERE category_id='$id'


");



?>


<!DOCTYPE html>

<html>


<head>

<title>

Category

</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/product.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<section class="product-page">


<div class="product-container">


<h1>

<?php echo $category['category_name']; ?>

</h1>



<div class="product-grid">


<?php while($row=$products->fetch_assoc()){ ?>



<div class="product-card">


<img src="assets/uploads/products/<?php echo $row['image']; ?>">



<h3>

<?php echo $row['product_name']; ?>

</h3>


<p>

RM <?php echo $row['price']; ?>

</p>



<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

View

</a>



</div>


<?php } ?>


</div>



</div>


</section>



<?php include "includes/footer.php"; ?>


</body>

</html>