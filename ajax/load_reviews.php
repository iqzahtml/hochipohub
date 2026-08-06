<?php

require_once "../config/db.php";


header("Content-Type: application/json");



if(!isset($_POST['product_id'])){


echo json_encode([

"status"=>"error",

"message"=>"Product ID required"

]);


exit();


}



$product_id=$_POST['product_id'];



$stmt=$conn->prepare("


SELECT


reviews.review_id,

reviews.rating,

reviews.review,

reviews.image,

reviews.review_date,


users.name,


users.profile_image



FROM reviews



JOIN users


ON reviews.customer_id = users.user_id



WHERE reviews.product_id=?

AND reviews.status='Visible'



ORDER BY reviews.review_date DESC



");



$stmt->bind_param(

"i",

$product_id

);



$stmt->execute();



$result=$stmt->get_result();



$reviews=[];



while($row=$result->fetch_assoc()){



$reviews[]=$row;



}



echo json_encode([


"status"=>"success",


"data"=>$reviews



]);

?>