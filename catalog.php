<?php

require_once "config/db.php";

session_start();



$query="

SELECT

products.*,

vendors.business_name,

categories.category_name


FROM products


JOIN vendors

ON products.vendor_id=vendors.vendor_id


JOIN categories

ON products.category_id=categories.category_id


WHERE products.status='Available'


ORDER BY products.created_at DESC


";


$result=$conn->query($query);


?>


<!DOCTYPE html>

<html>

<head>

<title>Catalog | HochipoHub</title>


<link rel="stylesheet" href="assets/css/product.css">


</head>


<body>


<?php include "includes/navbar.php"; ?>


<section class="product-page">


<div class="product-container">



<div class="product-page-header">


<div>


<span class="product-page-eyebrow">

CATALOG

</span>


<h1>

All Products

</h1>


<p>

Discover products from different vendors.

</p>


</div>


</div>




<div class="product-grid">



<?php while($row=$result->fetch_assoc()){ ?>


<div class="product-card">


<div class="product-card-image">


<img src="assets/uploads/products/<?php echo $row['image']; ?>">


</div>



<div class="product-card-content">


<span class="product-card-category">

<?php echo $row['category_name']; ?>

</span>



<h3 class="product-card-title">

<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

<?php echo $row['product_name']; ?>

</a>

</h3>



<div class="product-card-vendor">

<i class="fa-solid fa-store"></i>

<?php echo $row['business_name']; ?>

</div>




<div class="product-card-footer">


<strong class="product-price-current">

RM <?php echo number_format($row['price'],2); ?>

</strong>



<a class="add-cart-btn"
href="product_details.php?id=<?php echo $row['product_id']; ?>">


<i class="fa-solid fa-cart-shopping"></i>


</a>


</div>



</div>


</div>


<?php } ?>



</div>



</div>


</section>



<?php include "includes/footer.php"; ?>


</body>

</html>