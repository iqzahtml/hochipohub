<?php

session_start();

require_once "../config/db.php";


header("Content-Type: application/json");


if(!isset($_SESSION['user_id'])){

exit();

}



$user_id=$_SESSION['user_id'];

$cart_id=$_POST['cart_id'];

$quantity=$_POST['quantity'];



if($quantity <=0){

$delete=$conn->prepare("

DELETE FROM cart

WHERE cart_id=?

AND customer_id=?

");


$delete->bind_param(

"ii",

$cart_id,

$user_id

);


$delete->execute();



}

else{


$update=$conn->prepare("

UPDATE cart

SET quantity=?

WHERE cart_id=?

AND customer_id=?

");


$update->bind_param(

"iii",

$quantity,

$cart_id,

$user_id

);


$update->execute();



}



echo json_encode([

"status"=>"success"

]);


?>