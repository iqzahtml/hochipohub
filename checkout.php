<?php

session_start();

require_once "config/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: login.php");
    exit();

}


$user_id=$_SESSION['user_id'];



/*
|--------------------------------------------------------------------------
| GET CUSTOMER INFO
|--------------------------------------------------------------------------
*/

$user_query=$conn->prepare("

SELECT *

FROM users

WHERE user_id=?

");


$user_query->bind_param(
    "i",
    $user_id
);


$user_query->execute();


$user=$user_query->get_result()->fetch_assoc();




/*
|--------------------------------------------------------------------------
| GET CART ITEMS
|--------------------------------------------------------------------------
*/


$query="


SELECT


cart.cart_id,

cart.quantity,


products.product_id,

products.product_name,

products.price,

products.image,


vendors.business_name


FROM cart



JOIN products

ON cart.product_id=products.product_id



JOIN vendors

ON products.vendor_id=vendors.vendor_id



WHERE cart.customer_id=?



";



$stmt=$conn->prepare($query);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$cart=$stmt->get_result();



$total=0;



?>


<!DOCTYPE html>

<html>

<head>

<title>Checkout | HochipoHub</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/checkout.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<section class="checkout-page">


<div class="checkout-container">



<h1>

Checkout

</h1>




<form action="payment.php"
method="POST">





<div class="checkout-layout">





<div class="checkout-form">



<h2>

Delivery Information

</h2>




<div class="checkout-input">


<label>

Name

</label>


<input

type="text"

name="name"

value="<?php echo $user['name']; ?>"

required>


</div>





<div class="checkout-input">


<label>

Phone

</label>


<input

type="text"

name="phone"

value="<?php echo $user['phone']; ?>"

required>


</div>





<div class="checkout-input">


<label>

Address

</label>


<textarea

name="address"

required></textarea>


</div>







<h2>

Delivery Method

</h2>


<select name="delivery_method"
required>


<option value="Postage">

Postage

</option>


<option value="Pickup">

Pickup

</option>



</select>





</div>









<div class="checkout-summary">



<h2>

Order Summary

</h2>




<?php while($item=$cart->fetch_assoc()){



$item_total=$item['price']*$item['quantity'];

$total += $item_total;


?>



<div class="checkout-item">


<div>


<h4>

<?php echo $item['product_name']; ?>

</h4>


<p>

<?php echo $item['business_name']; ?>

</p>


</div>



<span>

RM <?php echo number_format($item_total,2); ?>

</span>



</div>



<?php } ?>





<hr>




<div class="checkout-total">


<strong>

Total

</strong>



<strong>

RM <?php echo number_format($total,2); ?>

</strong>



</div>



<input type="hidden"
name="total"
value="<?php echo $total; ?>">





<button class="checkout-btn">

Continue Payment

</button>




</div>





</div>



</form>



</div>


</section>





<?php include "includes/footer.php"; ?>


</body>

</html>