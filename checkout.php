<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Checkout Page
|--------------------------------------------------------------------------
|
| Flow:
| Cart -> Checkout -> Payment
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Checkout";



requireLogin();



$userID = currentUserID();





/*
|--------------------------------------------------------------------------
| Get Customer Cart
|--------------------------------------------------------------------------
*/


$query = "

SELECT


cart.cart_id,


cart.quantity,



products.product_id,

products.product_name,

products.price,

products.image





FROM cart





INNER JOIN products

ON cart.product_id = products.product_id





WHERE cart.user_id='$userID'



";



$cart = $conn->query($query);





$total = 0;



?>



<?php include "includes/header.php"; ?>







<section class="checkout-page">






<div class="page-title">


<h1>

Checkout

</h1>



<p>

Complete your order details.

</p>


</div>









<div class="checkout-container">







<div class="checkout-form">





<h2>

Shipping Information

</h2>






<form

action="payment.php"

method="POST"

>







<div class="form-group">


<label>

Full Name

</label>


<input

type="text"

name="name"

required

>


</div>








<div class="form-group">


<label>

Phone Number

</label>


<input

type="text"

name="phone"

required

>


</div>







<div class="form-group">


<label>

Shipping Address

</label>


<textarea

name="address"

rows="5"

required

></textarea>


</div>







<input

type="hidden"

name="total"

value="<?= $total; ?>"

>







<button

type="submit"

class="btn-primary"

>


Continue Payment


</button>





</form>





</div>









<div class="checkout-summary">





<h2>

Order Summary

</h2>








<?php if($cart && $cart->num_rows > 0){ ?>






<?php while($item=$cart->fetch_assoc()){ ?>




<?php

$itemTotal = 
$item['price'] * $item['quantity'];

$total += $itemTotal;


?>







<div class="checkout-item">



<img

src="<?= productImage($item['image']); ?>"

>



<div>


<h4>

<?= htmlspecialchars($item['product_name']); ?>

</h4>


<p>

Qty:
<?= $item['quantity']; ?>

</p>



<p>

RM <?= number_format($itemTotal,2); ?>

</p>


</div>



</div>







<?php } ?>







<div class="checkout-total">


<h3>

Total:

RM <?= number_format($total,2); ?>


</h3>


</div>






<?php }else{ ?>



<p>

Your cart is empty.

</p>



<?php } ?>






</div>








</div>






</section>








<?php include "includes/footer.php"; ?>