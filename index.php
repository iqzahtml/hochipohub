<?php

$pageTitle = "Home";

require_once "includes/header.php";


/*
|--------------------------------------------------------------------------
| Featured Products
|--------------------------------------------------------------------------
*/

$productQuery = "

SELECT

products.*,

vendors.business_name,

categories.category_name


FROM products


INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id


INNER JOIN categories

ON products.category_id = categories.category_id


WHERE products.status = 'Available'


ORDER BY products.created_at DESC


LIMIT 8

";


$products = $conn->query($productQuery);



/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$categoryQuery = "

SELECT *

FROM categories

ORDER BY category_name ASC

LIMIT 6

";


$categories = $conn->query($categoryQuery);



?>


<section class="hero">


<div class="hero-content">


<h1>
Discover Local Products
</h1>


<p>
Support local vendors with HochipoHub
</p>


<a href="<?= BASE_URL ?>catalog.php"
class="btn">

Shop Now

</a>


</div>


</section>




<section class="categories">


<h2>
Popular Categories
</h2>



<div class="category-grid">



<?php if($categories && $categories->num_rows > 0): ?>


<?php while($cat = $categories->fetch_assoc()): ?>


<div class="category-card">


<a href="<?= BASE_URL ?>category.php?id=<?= $cat['category_id']; ?>">



<?php if(!empty($cat['category_image'])): ?>


<img src="<?= BASE_URL ?>uploads/categories/<?= htmlspecialchars($cat['category_image']); ?>">


<?php endif; ?>



<h3>

<?= htmlspecialchars($cat['category_name']); ?>

</h3>



</a>


</div>


<?php endwhile; ?>


<?php else: ?>


<p>
No categories available.
</p>


<?php endif; ?>



</div>


</section>





<section class="products">


<h2>
Latest Products
</h2>



<div class="product-grid">



<?php if($products && $products->num_rows > 0): ?>



<?php while($product = $products->fetch_assoc()): ?>



<div class="product-card">



<?php if(!empty($product['image'])): ?>


<img src="<?= BASE_URL ?>uploads/products/<?= htmlspecialchars($product['image']); ?>">


<?php endif; ?>



<h3>

<?= htmlspecialchars($product['product_name']); ?>

</h3>



<p class="vendor-name">

<?= htmlspecialchars($product['business_name']); ?>

</p>



<p class="price">

RM <?= number_format($product['price'],2); ?>

</p>



<a href="<?= BASE_URL ?>product_details.php?id=<?= $product['product_id']; ?>"
class="btn">


View Product


</a>



</div>



<?php endwhile; ?>



<?php else: ?>


<p>
No products available.
</p>



<?php endif; ?>



</div>


</section>



<?php require_once "includes/footer.php"; ?>