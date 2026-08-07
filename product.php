<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Product Listing
|--------------------------------------------------------------------------
|
| Database:
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



$pageTitle = "Products";





/*
|--------------------------------------------------------------------------
| Get All Products
|--------------------------------------------------------------------------
*/


$query = "

SELECT


products.*,


vendors.business_name,


vendors.business_logo,


categories.category_name



FROM products




INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id






INNER JOIN categories

ON products.category_id = categories.category_id





WHERE products.status='Available'





ORDER BY products.created_at DESC



";




$result = $conn->query($query);





?>



<?php include "includes/header.php"; ?>






<section class="product-page">






<div class="page-title">


<h1>

All Products

</h1>



<p>

Discover products from HochipoHub vendors.

</p>


</div>









<div class="product-grid">







<?php if($result && $result->num_rows > 0){ ?>







<?php while($product = $result->fetch_assoc()){ ?>







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


<i class="fa-solid fa-shop"></i>


<?= htmlspecialchars($product['business_name']); ?>


</p>









<p class="product-description">


<?= htmlspecialchars(

substr(

$product['description'],

0,

80

)

); ?>


...</p>








<div class="product-bottom">






<span class="product-price">


<?= price($product['price']); ?>


</span>







<?php if($product['stock_quantity'] > 0){ ?>



<span class="stock available">


Available


</span>




<?php }else{ ?>



<span class="stock unavailable">


Out of Stock


</span>




<?php } ?>






</div>









<a

href="<?= BASE_URL; ?>product_details.php?id=<?= $product['product_id']; ?>"

class="view-product-btn"

>


View Product


</a>







</div>








</div>








<?php } ?>








<?php }else{ ?>







<div class="empty-product">


<h3>

No Products Available

</h3>



<p>

Vendor has not uploaded products yet.

</p>



</div>







<?php } ?>






</div>






</section>









<?php include "includes/footer.php"; ?>