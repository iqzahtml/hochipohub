<?php

session_start();

require_once "../database/db.php";


header("Content-Type: application/json");


if(!isset($_SESSION['user_id'])){

exit();

}


$user_id=$_SESSION['user_id'];

$cart_id=$_POST['cart_id'];



$stmt=$conn->prepare("

DELETE FROM cart

WHERE cart_id=?

AND customer_id=?

");


$stmt->bind_param(

"ii",

$cart_id,

$user_id

);



$stmt->execute();



echo json_encode([

"status"=>"success"

]);


?>