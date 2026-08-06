<?php

session_start();

require_once "database/db.php";


header("Content-Type: application/json");



if(!isset($_SESSION['user_id'])){

echo json_encode([
"status"=>"error"
]);

exit();

}



$user_id=$_SESSION['user_id'];

$product_id=$_POST['product_id'];



$check=$conn->prepare("

SELECT wishlist_id

FROM wishlist

WHERE user_id=?

AND product_id=?

");


$check->bind_param(

"ii",

$user_id,

$product_id

);



$check->execute();



if($check->get_result()->num_rows>0){


echo json_encode([

"status"=>"exists"

]);


exit();


}



$stmt=$conn->prepare("

INSERT INTO wishlist

(user_id,product_id)

VALUES(?,?)

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