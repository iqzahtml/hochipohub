<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Cart Page
|--------------------------------------------------------------------------
|
| Database:
| - cart
| - products
| - vendors
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "My Cart";



requireLogin();



$userID = currentUserID();





/*
|--------------------------------------------------------------------------
| Get Cart Items
|--------------------------------------------------------------------------
*/


$query = "

SELECT


cart.cart_id,


cart.quantity,



products.product_id,

products.product_name,

products.price,

products.image,



vendors.business_name




FROM cart





INNER JOIN products

ON cart.product_id = products.product_id





INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id





WHERE cart.user_id='$userID'





ORDER BY cart.created_at DESC



";




$cartItems = $conn->query($query);





$total = 0;



?>



<?php include "includes/header.php"; ?>







<section class="cart-page">






<div class="page-title">


<h1>

Shopping Cart

</h1>



<p>

Review your selected products before checkout.

</p>


</div>









<div class="cart-container">







<?php if($cartItems && $cartItems->num_rows > 0){ ?>






<div class="cart-list">





<?php while($item = $cartItems->fetch_assoc()){ ?>



<?php


$itemTotal = 
$item['price'] * $item['quantity'];


$total += $itemTotal;


?>







<div class="cart-card">








<div class="cart-image">


<img

src="<?= productImage($item['image']); ?>"

alt="<?= htmlspecialchars($item['product_name']); ?>"

>


</div>









<div class="cart-info">



<h3>


<?= htmlspecialchars($item['product_name']); ?>


</h3>





<p>


Seller:

<?= htmlspecialchars($item['business_name']); ?>


</p>





<p>


RM <?= number_format($item['price'],2); ?>


</p>






<form

action="ajax/update_cart.php"

method="POST"

>




<input

type="hidden"

name="cart_id"

value="<?= $item['cart_id']; ?>"

>





<input

type="number"

name="quantity"

value="<?= $item['quantity']; ?>"

min="1"

>






<button type="submit">


Update


</button>



</form>







<a

href="ajax/remove_cart.php?id=<?= $item['cart_id']; ?>"

class="remove-cart"


>


Remove


</a>





</div>







<div class="cart-total">


RM <?= number_format($itemTotal,2); ?>


</div>







</div>





<?php } ?>





</div>









<div class="cart-summary">





<h2>

Order Summary

</h2>





<div>


Subtotal:

<strong>

RM <?= number_format($total,2); ?>

</strong>


</div>







<a

href="<?= BASE_URL; ?>checkout.php"

class="btn-primary"

>


Proceed Checkout


</a>






</div>









<?php }else{ ?>







<div class="empty-product">


<h2>

Your cart is empty

</h2>



<p>

Start shopping and add products.

</p>




<a

href="<?= BASE_URL; ?>catalog.php"

class="btn-primary"

>


Shop Now


</a>



</div>







<?php } ?>







</div>






</section>







<?php include "includes/footer.php"; ?>