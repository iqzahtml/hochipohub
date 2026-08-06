<?php

session_start();

require_once "database/db.php";



/*
|--------------------------------------------------------------------------
| Featured Products
|--------------------------------------------------------------------------
*/

$products=$conn->query("

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


");



/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/


$categories=$conn->query("


SELECT *


FROM categories


ORDER BY category_name ASC


LIMIT 6



");



?>



<!DOCTYPE html>

<html>


<head>

<title>
HochipoHub
</title>


<link rel="stylesheet" href="css/style.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<section class="hero">


<h1>
Discover Local Products
</h1>


<p>
Support local vendors with HochipoHub
</p>



<a href="catalog.php">

Shop Now

</a>


</section>




<section class="categories">


<h2>
Categories
</h2>



<div class="category-grid">


<?php while($cat=$categories->fetch_assoc()){ ?>


<a href="category.php?id=<?= $cat['category_id']; ?>">



<img src="uploads/categories/<?= $cat['category_image']; ?>">


<h3>

<?= htmlspecialchars($cat['category_name']); ?>

</h3>


</a>



<?php } ?>



</div>


</section>




<section class="products">


<h2>
Latest Products
</h2>



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


</section>



<?php include "includes/footer.php"; ?>


</body>


</html>