<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: ../auth/login.php");
    exit();

}



$user_id=$_SESSION['user_id'];



$cart_stmt=$conn->prepare("


SELECT


cart.cart_id,


cart.quantity,



products.product_id,

products.product_name,

products.price,

products.image,

products.stock_quantity,



vendors.business_name



FROM cart



JOIN products

ON cart.product_id = products.product_id



JOIN vendors

ON products.vendor_id = vendors.vendor_id



WHERE cart.customer_id = ?



ORDER BY cart.cart_id DESC



");



$cart_stmt->bind_param(

"i",

$user_id

);



$cart_stmt->execute();



$cart_items=$cart_stmt->get_result();



$total=0;


?>



<!DOCTYPE html>

<html>


<head>

<title>
Shopping Cart
</title>


<link rel="stylesheet" href="css/cart.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<div class="cart-container">



<h1>
My Cart
</h1>




<?php if($cart_items->num_rows==0){ ?>


<p>
Your cart is empty.
</p>


<?php } ?>




<?php while($row=$cart_items->fetch_assoc()){ 



$subtotal=$row['price']*$row['quantity'];

$total += $subtotal;



?>



<div class="cart-item">



<img src="uploads/products/<?= $row['image']; ?>">



<div>


<h3>

<?= htmlspecialchars($row['product_name']); ?>

</h3>



<p>

Vendor:

<?= htmlspecialchars($row['business_name']); ?>

</p>



<p>

RM <?= number_format($row['price'],2); ?>

</p>



<input type="number"

class="cart-qty"

data-id="<?= $row['cart_id']; ?>"

value="<?= $row['quantity']; ?>"

min="1">



<p>

Subtotal:

RM <?= number_format($subtotal,2); ?>

</p>




<button class="remove-cart"

data-id="<?= $row['cart_id']; ?>">

Remove

</button>



</div>


</div>



<?php } ?>




<div class="cart-summary">


<h2>

Total:

RM <?= number_format($total,2); ?>

</h2>



<a href="checkout.php">

Proceed Checkout

</a>


</div>




</div>




<script src="js/script.js"></script>


</body>


</html>