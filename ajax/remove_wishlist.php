<?php

session_start();

require_once "../config/db.php";


header("Content-Type: application/json");





if(!isset($_SESSION['user_id'])){


echo json_encode([

"status"=>"error",

"message"=>"Login required"

]);


exit();


}





$user_id=$_SESSION['user_id'];



$product_id=$_POST['product_id'] ?? '';





if(empty($product_id)){



echo json_encode([

"status"=>"error",

"message"=>"Product ID missing"

]);


exit();


}







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



if($stmt->execute()){



echo json_encode([


"status"=>"success",


"message"=>"Removed from wishlist"



]);



}

else{


echo json_encode([


"status"=>"error",


"message"=>"Failed to remove"



]);


}



?>