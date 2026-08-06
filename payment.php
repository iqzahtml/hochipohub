<?php

session_start();

require_once "config/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}



if($_SERVER['REQUEST_METHOD']!="POST"){

header("Location: cart.php");

exit();

}



$user_id=$_SESSION['user_id'];



$total=$_POST['total'];

$address=$_POST['address'];

$delivery=$_POST['delivery_method'];





/*
|--------------------------------------------------------------------------
| CREATE ORDER
|--------------------------------------------------------------------------
*/


$order=$conn->prepare("


INSERT INTO orders

(
customer_id,
total_amount,
delivery_method,
delivery_address
)


VALUES(?,?,?,?)



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
| MOVE CART TO ORDER DETAILS
|--------------------------------------------------------------------------
*/


$cart=$conn->query("


SELECT *

FROM cart


JOIN products

ON cart.product_id=products.product_id


WHERE customer_id='$user_id'


");




while($item=$cart->fetch_assoc()){



$subtotal=$item['price']*$item['quantity'];



$conn->query("


INSERT INTO order_details

(
order_id,
product_id,
quantity,
unit_price,
subtotal

)

VALUES

(
'$order_id',
'".$item['product_id']."',
'".$item['quantity']."',
'".$item['price']."',
'$subtotal'

)


");



}





/*
|--------------------------------------------------------------------------
| PAYMENT RECORD
|--------------------------------------------------------------------------
*/



$conn->query("


INSERT INTO payments

(
order_id,
payment_method,
amount

)


VALUES

(
'$order_id',
'FPX',
'$total'

)



");





/*
|--------------------------------------------------------------------------
| CLEAR CART
|--------------------------------------------------------------------------
*/


$conn->query("


DELETE FROM cart

WHERE customer_id='$user_id'


");




header(

"Location: order_details.php?id=".$order_id

);


exit();


?>