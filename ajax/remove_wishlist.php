<?php

session_start();

require_once "../config/db.php";


header("Content-Type: application/json");


$user_id=$_SESSION['user_id'];

$product_id=$_POST['product_id'];



$stmt=$conn->prepare("

DELETE FROM wishlist

WHERE user_id=?

AND product_id=?

");


$stmt->bind_param(

"ii",

$user_id,

$product_id

);



$stmt->execute();



echo json_encode([

"status"=>"success"

]);

?>