<?php

session_start();

require_once "../config/db.php";


header("Content-Type: application/json");


if(!isset($_SESSION['user_id'])){

echo json_encode([
    "status"=>"error",
    "message"=>"Please login first"
]);

exit();

}



$user_id=$_SESSION['user_id'];

$product_id=$_POST['product_id'];

$quantity=$_POST['quantity'] ?? 1;



/*
CHECK PRODUCT
*/

$product=$conn->prepare("

SELECT product_id, stock_quantity

FROM products

WHERE product_id=?

AND status='Available'

");


$product->bind_param(

"i",

$product_id

);


$product->execute();


$result=$product->get_result();



if($result->num_rows==0){

echo json_encode([
"status"=>"error",
"message"=>"Product unavailable"
]);

exit();

}




$data=$result->fetch_assoc();



if($data['stock_quantity'] < $quantity){

echo json_encode([
"status"=>"error",
"message"=>"Not enough stock"
]);

exit();

}



/*
CHECK EXIST CART
*/


$check=$conn->prepare("

SELECT cart_id

FROM cart

WHERE customer_id=?

AND product_id=?

");


$check->bind_param(

"ii",

$user_id,

$product_id

);


$check->execute();



$exist=$check->get_result();



if($exist->num_rows>0){


$update=$conn->prepare("

UPDATE cart

SET quantity = quantity + ?

WHERE customer_id=?

AND product_id=?

");


$update->bind_param(

"iii",

$quantity,

$user_id,

$product_id

);



$update->execute();



}

else{


$insert=$conn->prepare("

INSERT INTO cart

(customer_id,product_id,quantity)

VALUES(?,?,?)

");


$insert->bind_param(

"iii",

$user_id,

$product_id,

$quantity

);



$insert->execute();



}



echo json_encode([

"status"=>"success",

"message"=>"Added to cart"

]);

?>