<?php


session_start();


require_once "../database/db.php";



if(!isset($_SESSION['user_id'])){


header("Location: ../auth/login.php");


exit();


}



$user_id=$_SESSION['user_id'];




$get_vendor=$conn->prepare("

SELECT vendor_id

FROM vendors

WHERE user_id=?

");



$get_vendor->bind_param(

"i",

$user_id

);



$get_vendor->execute();



$vendor=$get_vendor->get_result()->fetch_assoc();




if(!$vendor){

header("Location: setup_profile.php");

exit();

}




$vendor_id=$vendor['vendor_id'];




if(isset($_POST['add'])){


$product_name=$_POST['product_name'];

$category_id=$_POST['category_id'];

$description=$_POST['description'];

$price=$_POST['price'];

$stock=$_POST['stock_quantity'];





$image=$_FILES['image']['name'];

$tmp=$_FILES['image']['tmp_name'];




$upload="../assets/uploads/products/".$image;



move_uploaded_file(

$tmp,

$upload

);






$stmt=$conn->prepare("


INSERT INTO products

(

vendor_id,

category_id,

product_name,

description,

price,

stock_quantity,

image

)

VALUES

(?,?,?,?,?,?,?)



");



$stmt->bind_param(

"iissdis",

$vendor_id,

$category_id,

$product_name,

$description,

$price,

$stock,

$image

);



$stmt->execute();





header("Location: products.php");


exit();



}




$categories=$conn->query("

SELECT *

FROM categories

");



?>



<!DOCTYPE html>

<html>

<head>

<title>
Add Product
</title>

</head>


<body>



<form method="POST"

enctype="multipart/form-data">



<input type="text"

name="product_name"

placeholder="Product Name"

required>




<select name="category_id">


<?php while($cat=$categories->fetch_assoc()){ ?>


<option value="<?= $cat['category_id']; ?>">


<?= $cat['category_name']; ?>


</option>


<?php } ?>


</select>




<textarea name="description"></textarea>



<input type="number"

step="0.01"

name="price"

placeholder="Price">





<input type="number"

name="stock_quantity"

placeholder="Stock">





<input type="file"

name="image">



<button name="add">

Add Product

</button>



</form>



</body>

</html>