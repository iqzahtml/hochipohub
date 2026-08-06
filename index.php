<?php

session_start();

require_once "config/db.php";


/*
|--------------------------------------------------------------------------
| GET FEATURED PRODUCTS
|--------------------------------------------------------------------------
*/

$query = "

SELECT

products.*,

vendors.business_name,

categories.category_name


FROM products


JOIN vendors

ON products.vendor_id = vendors.vendor_id


JOIN categories

ON products.category_id = categories.category_id


WHERE products.status='Available'


ORDER BY products.created_at DESC


LIMIT 8

";


$products = $conn->query($query);



/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $conn->query("

SELECT *

FROM categories

LIMIT 6

");

?>

<!DOCTYPE html>

<html>

<head>

<title>HochipoHub | Marketplace</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/product.css">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>


<body>


<?php include "includes/navbar.php"; ?>



<section class="hero">


<div class="hero-content">


<h1>

Discover Amazing Products

From Local Vendors

</h1>


<p>

Shop unique products from trusted sellers.

</p>



<a href="product.php" class="primary-btn">

Explore Now

</a>


</div>


</section>





<section class="container">


<div class="section-title">

<h2>

Popular Categories

</h2>

</div>




<div class="category-grid">


<?php while($cat=$categories->fetch_assoc()){ ?>


<a href="category.php?id=<?php echo $cat['category_id']; ?>"
class="category-card">


<div class="category-card-icon">

<i class="fa-solid fa-layer-group"></i>

</div>



<h3>

<?php echo $cat['category_name']; ?>

</h3>


<p>

Explore products

</p>


</a>



<?php } ?>


</div>



</section>







<section class="container">


<div class="section-title">


<h2>

Latest Products

</h2>


<a href="product.php">

View All

</a>


</div>




<div class="product-grid">


<?php while($row=$products->fetch_assoc()){ ?>


<div class="product-card">


<div class="product-card-image">


<?php if($row['image']){ ?>


<img src="assets/uploads/products/<?php echo $row['image']; ?>">


<?php }else{ ?>


<div class="product-image-placeholder">

<i class="fa-solid fa-image"></i>

</div>


<?php } ?>


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




<p class="product-card-vendor">

<i class="fa-solid fa-store"></i>

<?php echo $row['business_name']; ?>


</p>




<div class="product-card-footer">


<strong class="product-price-current">


RM <?php echo number_format($row['price'],2); ?>


</strong>



<a href="product_details.php?id=<?php echo $row['product_id']; ?>"
class="add-cart-btn">


<i class="fa-solid fa-eye"></i>


</a>



</div>


</div>



</div>


<?php } ?>


</div>


</section>





<?php include "includes/footer.php"; ?>


</body>

</html>