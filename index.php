<?php

require_once "config/db.php";



$latest=$conn->query("


SELECT


products.*,

vendors.business_name


FROM products


JOIN vendors

ON products.vendor_id=vendors.vendor_id



ORDER BY product_id DESC



LIMIT 8


");




$categories=$conn->query("


SELECT *

FROM categories


LIMIT 6


");



?>


<!DOCTYPE html>

<html>


<head>


<title>

HochipoHub Marketplace

</title>



<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/product.css">


</head>


<body>


<?php include "includes/navbar.php"; ?>





<section class="hero-section">


<div class="hero-content">


<h1>

Discover Local Products

with HochipoHub

</h1>



<p>

Support local vendors and shop everything in one place.

</p>




<a href="catalog.php">

Start Shopping

</a>



</div>


</section>





<section class="container">



<h2>

Categories

</h2>




<div class="category-grid">



<?php while($cat=$categories->fetch_assoc()){ ?>



<a href="category.php?id=<?php echo $cat['category_id']; ?>"
class="category-card">


<h3>

<?php echo $cat['category_name']; ?>

</h3>


</a>



<?php } ?>



</div>



</section>





<section class="container">



<h2>

Latest Products

</h2>



<div class="product-grid">



<?php while($row=$latest->fetch_assoc()){ ?>


<div class="product-card">


<img src="assets/uploads/products/<?php echo $row['image']; ?>">



<h3>

<?php echo $row['product_name']; ?>

</h3>



<p>

RM <?php echo number_format($row['price'],2); ?>

</p>



<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

View

</a>


</div>



<?php } ?>



</div>



</section>




<?php include "includes/footer.php"; ?>


</body>

</html>