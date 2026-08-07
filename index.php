<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Homepage
|--------------------------------------------------------------------------
|
| Uses:
| - products
| - vendors
| - categories
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle = "Home";



/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/


$categoryQuery = "

SELECT *

FROM categories

ORDER BY created_at DESC

LIMIT 6

";


$categories = $conn->query($categoryQuery);




/*
|--------------------------------------------------------------------------
| Get Latest Products
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



?>


<?php include "includes/header.php"; ?>





<!-- =========================================================
     HERO SECTION
========================================================= -->


<section class="hero-section">


<div class="hero-content">



<h1>

Discover Local Products

</h1>



<p>

Support local vendors and discover unique products
with HochipoHub.

</p>



<div class="hero-buttons">


<a href="<?= BASE_URL; ?>catalog.php"

class="btn-primary">

Shop Now

</a>



<a href="<?= BASE_URL; ?>vendor.php"

class="btn-secondary">

Explore Vendors

</a>


</div>



</div>





<div class="hero-image">


<img

src="<?= BANNER_URL; ?>"

alt="HochipoHub Banner"

>


</div>



</section>








<!-- =========================================================
     CATEGORY SECTION
========================================================= -->


<section class="category-section">


<div class="section-header">


<h2>

Explore Categories

</h2>



<a href="<?= BASE_URL; ?>catalog.php">

View All

</a>


</div>





<div class="category-grid">



<?php if($categories && $categories->num_rows > 0): ?>



<?php while($category = $categories->fetch_assoc()): ?>



<div class="category-card">



<a href="<?= BASE_URL; ?>category.php?id=<?= $category['category_id']; ?>">





<?php if(!empty($category['category_image'])): ?>


<img

src="<?= BASE_URL; ?>uploads/categories/<?= htmlspecialchars($category['category_image']); ?>"

alt="<?= htmlspecialchars($category['category_name']); ?>"

>


<?php else: ?>


<img

src="<?= IMAGE_URL; ?>logo.jpg"

alt="Category"

>


<?php endif; ?>





<h3>

<?= htmlspecialchars($category['category_name']); ?>

</h3>



</a>



</div>




<?php endwhile; ?>



<?php endif; ?>



</div>


</section>









<!-- =========================================================
     PRODUCT SECTION
========================================================= -->


<section class="product-section">



<div class="section-header">


<h2>

Latest Products

</h2>



<a href="<?= BASE_URL; ?>catalog.php">

View All

</a>


</div>








<div class="product-grid">





<?php if($products && $products->num_rows > 0): ?>



<?php while($product = $products->fetch_assoc()): ?>



<div class="product-card">






<div class="product-image">



<img

src="<?= productImage($product['image']); ?>"

alt="<?= htmlspecialchars($product['product_name']); ?>"

>


</div>








<div class="product-info">



<span class="product-category">

<?= htmlspecialchars($product['category_name']); ?>

</span>




<h3>


<?= htmlspecialchars($product['product_name']); ?>


</h3>





<p class="vendor-name">


<i class="fa-solid fa-store"></i>


<?= htmlspecialchars($product['business_name']); ?>


</p>






<p class="product-price">


<?= price($product['price']); ?>


</p>






<a

href="<?= BASE_URL; ?>product_details.php?id=<?= $product['product_id']; ?>"

class="view-product-btn"

>


View Product


</a>





</div>



</div>




<?php endwhile; ?>



<?php else: ?>



<div class="empty-product">


<p>

No products available yet.

</p>


</div>



<?php endif; ?>





</div>


</section>









<!-- =========================================================
     CTA SECTION
========================================================= -->


<section class="cta-section">


<h2>

Want to become a vendor?

</h2>



<p>

Sell your products and grow your business with HochipoHub.

</p>



<a href="<?= BASE_URL; ?>vendor.php"

class="btn-primary">


Become Vendor


</a>



</section>






<?php include "includes/footer.php"; ?>