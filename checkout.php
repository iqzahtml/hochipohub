<?php

session_start();


require_once "database/db.php";



if(!isset($_SESSION['user_id'])){


header("Location: auth/login.php");


exit();


}



$user_id=$_SESSION['user_id'];





/*
|--------------------------------------------------------------------------
| GET CUSTOMER CART
|--------------------------------------------------------------------------
*/


$cart=$conn->prepare("


SELECT


cart.product_id,


cart.quantity,



products.price,

products.vendor_id,



products.product_name



FROM cart



JOIN products


ON cart.product_id = products.product_id



WHERE cart.customer_id=?



");


$cart->bind_param(

"i",

$user_id

);



$cart->execute();



$items=$cart->get_result();




if($items->num_rows==0){


header("Location: cart.php");


exit();


}







if(isset($_POST['checkout'])){



$delivery=$_POST['delivery_method'];

$address=$_POST['delivery_address'];





$total=0;

$cart_data=[];





while($item=$items->fetch_assoc()){



$subtotal=$item['price']*$item['quantity'];



$total += $subtotal;



$cart_data[]=$item;



}




/*
|--------------------------------------------------------------------------
| CREATE MAIN ORDER
|--------------------------------------------------------------------------
*/


$order=$conn->prepare("


INSERT INTO orders


(

customer_id,

total_amount,

delivery_method,

delivery_address,

order_status

)



VALUES

(?,?,?,?, 'Pending')



");



$order->bind_param(

"idss",

$user_id,

$total,

$delivery,

$address

);



$order->execute();



$order_id=$conn->insert_id;







/*
|--------------------------------------------------------------------------
| INSERT ORDER DETAILS
|--------------------------------------------------------------------------
*/


foreach($cart_data as $item){



$subtotal=$item['price']*$item['quantity'];




$detail=$conn->prepare("


INSERT INTO order_details


(

order_id,

product_id,

quantity,

unit_price,

subtotal

)



VALUES

(?,?,?,?,?)



");



$detail->bind_param(

"iiidd",

$order_id,

$item['product_id'],

$item['quantity'],

$item['price'],

$subtotal

);



$detail->execute();







/*
|--------------------------------------------------------------------------
| CREATE VENDOR ORDER
|--------------------------------------------------------------------------
*/


$vendor=$item['vendor_id'];




$check_vendor=$conn->prepare("


SELECT vendor_order_id

FROM vendor_orders

WHERE order_id=?

AND vendor_id=?



");



$check_vendor->bind_param(

"ii",

$order_id,

$vendor

);



$check_vendor->execute();



$result=$check_vendor->get_result();





if($result->num_rows==0){



$vendor_order=$conn->prepare("


INSERT INTO vendor_orders


(

order_id,

vendor_id,

subtotal,

vendor_status


)



VALUES

(?,?,?, 'Pending')



");



$vendor_order->bind_param(

"iid",

$order_id,

$vendor,

$subtotal

);



$vendor_order->execute();



}

else{



$update_vendor=$conn->prepare("


UPDATE vendor_orders


SET subtotal=subtotal+?



WHERE order_id=?

AND vendor_id=?



");



$update_vendor->bind_param(

"dii",

$subtotal,

$order_id,

$vendor

);



$update_vendor->execute();



}




/*
|--------------------------------------------------------------------------
| UPDATE STOCK
|--------------------------------------------------------------------------
*/


$stock=$conn->prepare("


UPDATE products


SET stock_quantity = stock_quantity - ?


WHERE product_id=?



");


$stock->bind_param(

"ii",

$item['quantity'],

$item['product_id']

);



$stock->execute();



}





/*
|--------------------------------------------------------------------------
| CLEAR CART
|--------------------------------------------------------------------------
*/


$clear=$conn->prepare("


DELETE FROM cart


WHERE customer_id=?



");



$clear->bind_param(

"i",

$user_id

);



$clear->execute();





header("Location: payment.php?order_id=".$order_id);


exit();



}




/*
RELOAD CART FOR DISPLAY

*/

$cart->execute();

$items=$cart->get_result();



?>



<!DOCTYPE html>

<html>


<head>

<title>
Checkout
</title>


<link rel="stylesheet" href="css/checkout.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<div class="checkout-container">



<h1>
Checkout
</h1>




<form method="POST">



<label>

Delivery Method

</label>


<select name="delivery_method">


<option value="Pickup">

Pickup

</option>


<option value="Postage">

Postage

</option>



</select>




<label>

Delivery Address

</label>



<textarea name="delivery_address"></textarea>




<button name="checkout">

Place Order

</button>



</form>



</div>



</body>


</html>