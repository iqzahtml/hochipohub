<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Product Details
|--------------------------------------------------------------------------
|
| Database:
| - products
| - vendors
| - categories
| - reviews
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Product Details";





/*
|--------------------------------------------------------------------------
| Check Product ID
|--------------------------------------------------------------------------
*/


if(!isset($_GET['id'])){


    header(
        "Location: product.php"
    );


    exit();


}



$productID = mysqli_real_escape_string(

    $conn,

    $_GET['id']

);








/*
|--------------------------------------------------------------------------
| Product Information
|--------------------------------------------------------------------------
*/


$productQuery = "

SELECT


products.*,


vendors.business_name,


vendors.business_logo,


vendors.business_description,



categories.category_name




FROM products





INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id





INNER JOIN categories

ON products.category_id = categories.category_id






WHERE products.product_id='$productID'



LIMIT 1



";




$productResult = $conn->query($productQuery);




if(!$productResult || $productResult->num_rows == 0){


    header(

        "Location: product.php"

    );


    exit();


}





$product = $productResult->fetch_assoc();









/*
|--------------------------------------------------------------------------
| Reviews
|--------------------------------------------------------------------------
*/


$reviewQuery = "

SELECT


reviews.*,


users.name



FROM reviews




INNER JOIN users

ON reviews.customer_id = users.user_id





WHERE reviews.product_id='$productID'

AND reviews.status='Visible'




ORDER BY reviews.review_date DESC



";




$reviews = $conn->query($reviewQuery);









/*
|--------------------------------------------------------------------------
| Rating Average
|--------------------------------------------------------------------------
*/


$ratingQuery = "

SELECT


AVG(rating) AS average_rating,


COUNT(*) AS total_review




FROM reviews





WHERE product_id='$productID'

AND status='Visible'



";



$ratingResult = $conn->query($ratingQuery);


$rating = $ratingResult->fetch_assoc();





?>



<?php include "includes/header.php"; ?>







<section class="product-detail-page">







<div class="product-detail-container">







<!-- IMAGE -->



<div class="product-detail-image">


<img

src="<?= productImage($product['image']); ?>"

alt="<?= htmlspecialchars($product['product_name']); ?>"

>


</div>








<!-- INFO -->



<div class="product-detail-info">






<span class="product-category">


<?= htmlspecialchars($product['category_name']); ?>


</span>







<h1>


<?= htmlspecialchars($product['product_name']); ?>


</h1>









<p class="vendor-name">


<i class="fa-solid fa-store"></i>


Sold by:


<?= htmlspecialchars($product['business_name']); ?>


</p>







<p class="product-description">


<?= nl2br(

htmlspecialchars(

$product['description']

)

); ?>


</p>







<h2 class="product-price">


<?= price($product['price']); ?>


</h2>








<p>


Stock:


<strong>

<?= $product['stock_quantity']; ?>

</strong>


</p>









<?php if($product['stock_quantity'] > 0){ ?>





<form

action="ajax/add_cart.php"

method="POST"

>


<input

type="hidden"

name="product_id"

value="<?= $product['product_id']; ?>"

>



<div class="quantity-box">


<input

type="number"

name="quantity"

value="1"

min="1"

max="<?= $product['stock_quantity']; ?>"

>


</div>






<button

type="submit"

class="btn-primary"

>


<i class="fa-solid fa-cart-plus"></i>


Add To Cart


</button>



</form>






<?php }else{ ?>



<button

class="btn-disabled"

disabled

>


Out Of Stock


</button>



<?php } ?>










<form

action="ajax/add_wishlist.php"

method="POST"

>



<input

type="hidden"

name="product_id"

value="<?= $product['product_id']; ?>"

>



<button

type="submit"

class="wishlist-btn"

>


<i class="fa-solid fa-heart"></i>


Add Wishlist


</button>



</form>







</div>






</div>













<!-- REVIEW SECTION -->



<section class="review-section">





<h2>


Customer Reviews


</h2>






<div class="rating-summary">


<h3>


<?= number_format(

$rating['average_rating'] ?? 0,

1

); ?>


/ 5


</h3>


<p>


<?= $rating['total_review'] ?? 0; ?>

reviews


</p>


</div>







<?php if($reviews && $reviews->num_rows > 0){ ?>





<?php while($review = $reviews->fetch_assoc()){ ?>





<div class="review-card">



<h4>


<?= htmlspecialchars($review['name']); ?>


</h4>




<div class="stars">


<?= str_repeat(

"⭐",

$review['rating']

); ?>


</div>




<p>


<?= htmlspecialchars($review['review']); ?>


</p>




</div>






<?php } ?>





<?php }else{ ?>



<p>

No reviews yet.

</p>



<?php } ?>





</section>







</section>









<?php include "includes/footer.php"; ?>