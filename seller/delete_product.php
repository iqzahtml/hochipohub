<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role']!="vendor"){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



$id=$_GET['id'];



$vendor=$conn->prepare("

SELECT vendor_id

FROM vendors

WHERE user_id=?

");


$vendor->bind_param(

"i",

$user_id

);


$vendor->execute();


$data=$vendor->get_result()->fetch_assoc();



$vendor_id=$data['vendor_id'];




$product=$conn->prepare("


SELECT image

FROM products

WHERE product_id=?

AND vendor_id=?


");



$product->bind_param(

"ii",

$id,

$vendor_id

);



$product->execute();



$result=$product->get_result();



if($result->num_rows>0){



$row=$result->fetch_assoc();




if($row['image']){


$file="../assets/uploads/products/".$row['image'];


if(file_exists($file)){


unlink($file);


}


}





$delete=$conn->prepare("


DELETE FROM products

WHERE product_id=?

AND vendor_id=?


");


$delete->bind_param(

"ii",

$id,

$vendor_id

);



$delete->execute();



}



header("Location: products.php");


exit();



?>