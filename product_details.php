<?php

session_start();

require_once "config/db.php";



if(!isset($_GET['id'])){


header("Location: product.php");

exit();


}


$product_id=$_GET['id'];




$query="


SELECT


products.*,


vendors.vendor_id,

vendors.business_name,


categories.category_name


FROM products


JOIN vendors

ON products.vendor_id=vendors.vendor_id



JOIN categories

ON products.category_id=categories.category_id



WHERE products.product_id=?



";



$stmt=$conn->prepare($query);


$stmt->bind_param(
"i",
$product_id
);


$stmt->execute();


$product=$stmt->get_result()->fetch_assoc();




if(!$product){

echo "Product not found";

exit();

}



?>



<!DOCTYPE html>

<html>


<head>


<title>

<?php echo $product['product_name']; ?>

</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/product.css">



</head>


<body>


<?php include "includes/navbar.php"; ?>





<section class="product-details">



<div class="product-details-layout">





<div class="product-gallery">



<div class="product-main-image">


<img src="assets/uploads/products/<?php echo $product['image']; ?>">


</div>



</div>







<div class="product-info">



<span class="product-info-category">

<?php echo $product['category_name']; ?>

</span>





<h1 class="product-info-title">

<?php echo htmlspecialchars($product['product_name']); ?>

</h1>




<div class="product-info-vendor">


<i class="fa-solid fa-store"></i>


Sold by:


<a href="vendor.php?id=<?php echo $product['vendor_id']; ?>">


<?php echo $product['business_name']; ?>


</a>


</div>







<div class="product-info-price">


RM <?php echo number_format($product['price'],2); ?>


</div>





<div class="product-info-description">


<h3>

Description

</h3>



<p>

<?php echo nl2br($product['description']); ?>

</p>


</div>







<div class="product-purchase-box">



<form action="ajax/add_cart.php"
method="POST">



<input type="hidden"
name="product_id"
value="<?php echo $product_id; ?>">





<div class="quantity-control">


<input

type="number"

name="quantity"

value="1"

min="1"

class="quantity-input"


>


</div>




<button class="product-buy-btn">


<i class="fa-solid fa-cart-shopping"></i>


Add To Cart


</button>



</form>


</div>







<div class="vendor-mini-card">


<strong>

<?php echo $product['business_name']; ?>

</strong>


</div>




</div>



</div>



</section>







<section class="product-tabs-section">



<h2>

Customer Reviews

</h2>



<?php


$reviews=$conn->query("


SELECT *

FROM reviews

WHERE product_id='$product_id'


ORDER BY created_at DESC


");



while($review=$reviews->fetch_assoc()){


?>


<div class="review-item">


<strong>

<?php echo $review['rating']; ?>/5 ⭐

</strong>



<p>

<?php echo $review['comment']; ?>


</p>



</div>


<?php } ?>



</section>







<?php include "includes/footer.php"; ?>



</body>


</html>