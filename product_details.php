<?php

session_start();

require_once "database/db.php";


$product_id=$_GET['id'];



$stmt=$conn->prepare("


SELECT


products.*,


vendors.business_name,


categories.category_name



FROM products



JOIN vendors


ON products.vendor_id=vendors.vendor_id



JOIN categories


ON products.category_id=categories.category_id



WHERE products.product_id=?



");



$stmt->bind_param(

"i",

$product_id

);



$stmt->execute();



$product=$stmt->get_result()->fetch_assoc();



if(!$product){

exit("Product not found");

}




?>



<!DOCTYPE html>

<html>


<head>

<title>

<?= htmlspecialchars($product['product_name']); ?>

</title>


<link rel="stylesheet" href="css/product.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<div class="product-detail">



<img src="uploads/products/<?= $product['image']; ?>">





<div class="product-info">


<h1>

<?= htmlspecialchars($product['product_name']); ?>

</h1>



<h3>

RM <?= number_format($product['price'],2); ?>

</h3>




<p>

Category:

<?= $product['category_name']; ?>

</p>



<p>

Vendor:

<?= htmlspecialchars($product['business_name']); ?>

</p>



<p>

<?= nl2br(htmlspecialchars($product['description'])); ?>

</p>




<input type="number"

id="quantity"

value="1"

min="1"

max="<?= $product['stock_quantity']; ?>">





<button class="add-cart"

data-id="<?= $product_id; ?>">


Add To Cart


</button>





<button class="add-wishlist"

data-id="<?= $product_id; ?>">


Wishlist


</button>




</div>



</div>





<div id="review-section">


<h2>

Reviews

</h2>



<div id="review-list"></div>


</div>





<script src="js/script.js"></script>


</body>


</html>