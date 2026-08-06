<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role']!="vendor"){

    header("Location: ../auth/login.php");
    exit();

}



$user_id=$_SESSION['user_id'];



$vendor_stmt=$conn->prepare("

SELECT vendor_id

FROM vendors

WHERE user_id=?

");


$vendor_stmt->bind_param(

"i",

$user_id

);


$vendor_stmt->execute();


$vendor=$vendor_stmt->get_result()->fetch_assoc();



if(!$vendor){

    header("Location: setup_profile.php");
    exit();

}



$vendor_id=$vendor['vendor_id'];



$product_id=$_GET['id'];



$product_stmt=$conn->prepare("

SELECT *

FROM products

WHERE product_id=?

AND vendor_id=?

");


$product_stmt->bind_param(

"ii",

$product_id,

$vendor_id

);


$product_stmt->execute();


$product=$product_stmt->get_result()->fetch_assoc();



if(!$product){

    exit("Product not found");

}




if(isset($_POST['update'])){


$product_name=$_POST['product_name'];

$description=$_POST['description'];

$price=$_POST['price'];

$stock=$_POST['stock_quantity'];

$status=$_POST['status'];




if($_FILES['image']['name']!=""){


$image=$_FILES['image']['name'];

$tmp=$_FILES['image']['tmp_name'];



move_uploaded_file(

$tmp,

"../assets/uploads/products/".$image

);



$update=$conn->prepare("


UPDATE products

SET

product_name=?,

description=?,

price=?,

stock_quantity=?,

status=?,

image=?


WHERE product_id=?

AND vendor_id=?


");



$update->bind_param(

"ssdisiii",

$product_name,

$description,

$price,

$stock,

$status,

$image,

$product_id,

$vendor_id

);



}

else{



$update=$conn->prepare("


UPDATE products

SET

product_name=?,

description=?,

price=?,

stock_quantity=?,

status=?


WHERE product_id=?

AND vendor_id=?


");



$update->bind_param(

"ssdisii",

$product_name,

$description,

$price,

$stock,

$status,

$product_id,

$vendor_id

);



}



$update->execute();



header("Location: products.php");

exit();



}


?>



<!DOCTYPE html>

<html>

<head>

<title>
Edit Product
</title>


<link rel="stylesheet" href="../assets/css/vendor.css">


</head>


<body>



<h1>
Edit Product
</h1>




<form method="POST"

enctype="multipart/form-data">


<label>
Product Name
</label>


<input type="text"

name="product_name"

value="<?= htmlspecialchars($product['product_name']); ?>">





<label>
Description
</label>


<textarea name="description">

<?= htmlspecialchars($product['description']); ?>

</textarea>





<label>
Price
</label>


<input type="number"

step="0.01"

name="price"

value="<?= $product['price']; ?>">





<label>
Stock Quantity
</label>


<input type="number"

name="stock_quantity"

value="<?= $product['stock_quantity']; ?>">





<label>
Status
</label>


<select name="status">


<option value="Available">

Available

</option>


<option value="Out of Stock">

Out of Stock

</option>


<option value="Hidden">

Hidden

</option>


</select>





<label>
Image
</label>


<input type="file"

name="image">





<button name="update">

Update Product

</button>



</form>



</body>

</html>