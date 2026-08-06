<?php

session_start();

require_once "../config/db.php";


header("Content-Type: application/json");



$product_id = $_POST['product_id'] ?? '';



if(empty($product_id)){


echo json_encode([

"status"=>"error",

"message"=>"Product ID missing"

]);


exit();


}




$sql = "

SELECT


reviews.review_id,

reviews.rating,

reviews.comment,

reviews.created_at,


users.name,


users.profile_image



FROM reviews



JOIN users

ON reviews.user_id = users.user_id



WHERE reviews.product_id = ?



ORDER BY reviews.review_id DESC



";




$stmt=$conn->prepare($sql);



$stmt->bind_param(

"i",

$product_id

);



$stmt->execute();



$result=$stmt->get_result();



$reviews=[];




while($row=$result->fetch_assoc()){



$reviews[]=[


"review_id"=>$row['review_id'],


"user_name"=>$row['name'],


"profile_image"=>$row['profile_image'],


"rating"=>$row['rating'],


"comment"=>$row['comment'],


"date"=>$row['created_at']



];


}





/*
|--------------------------------------------------------------------------
| Calculate Average Rating
|--------------------------------------------------------------------------
*/



$avgQuery=$conn->prepare("


SELECT AVG(rating) AS average_rating

FROM reviews

WHERE product_id=?


");



$avgQuery->bind_param(

"i",

$product_id

);



$avgQuery->execute();



$avgResult=$avgQuery->get_result()->fetch_assoc();



$average = round(
$avgResult['average_rating'] ?? 0,
1
);







echo json_encode([


"status"=>"success",


"average_rating"=>$average,


"total_reviews"=>count($reviews),


"reviews"=>$reviews



]);



?>