<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Category Page
|--------------------------------------------------------------------------
|
| Example:
| category.php?id=1
|
| Database:
| - categories
| - products
| - vendors
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Category";





/*
|--------------------------------------------------------------------------
| Get Category ID
|--------------------------------------------------------------------------
*/


if(!isset($_GET['id'])){


    header(

        "Location: catalog.php"

    );


    exit();


}



$categoryID = mysqli_real_escape_string(

    $conn,

    $_GET['id']

);







/*
|--------------------------------------------------------------------------
| Get Category Information
|--------------------------------------------------------------------------
*/


$categoryQuery = "

SELECT *

FROM categories

WHERE category_id='$categoryID'

LIMIT 1

";



$categoryResult = $conn->query($categoryQuery);




if(!$categoryResult || $categoryResult->num_rows == 0){


    header(

        "Location: catalog.php"

    );


    exit();


}



$category = $categoryResult->fetch_assoc();









/*
|--------------------------------------------------------------------------
| Get Products Under Category
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






WHERE products.category_id='$categoryID'

AND products.status='Available'





ORDER BY products.created_at DESC



";





$products = $conn->query($productQuery);






?>



<?php include "includes/header.php"; ?>







<section class="category-page">






<div class="category-banner">



<h1>


<?= htmlspecialchars($category['category_name']); ?>


</h1>



<p>


Explore products in this category


</p>



</div>








<div class="product-grid">






<?php if($products && $products->num_rows > 0){ ?>






<?php while($product = $products->fetch_assoc()){ ?>







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


View Details


</a>







</div>







</div>







<?php } ?>






<?php }else{ ?>






<div class="empty-product">


<h3>

No products available

</h3>



<p>

This category does not have products yet.

</p>



</div>







<?php } ?>








</div>







</section>








<?php include "includes/footer.php"; ?>