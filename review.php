<?php

session_start();

require_once "database/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



if(isset($_POST['submit_review'])){


$product_id=$_POST['product_id'];

$rating=$_POST['rating'];

$review=$_POST['review'];



$stmt=$conn->prepare("


INSERT INTO reviews


(

customer_id,

product_id,

rating,

review

)



VALUES

(?,?,?,?)



");



$stmt->bind_param(

"iiis",

$user_id,

$product_id,

$rating,

$review

);



$stmt->execute();



header("Location: product_details.php?id=".$product_id);


exit();


}



?>



<!DOCTYPE html>

<html>


<head>

<title>

Write Review

</title>


</head>


<body>



<h1>
Write Review
</h1>



<form method="POST">



<input type="hidden"

name="product_id"

value="<?= $_GET['id']; ?>">





<label>
Rating
</label>


<select name="rating">


<option value="5">
5
</option>


<option value="4">
4
</option>


<option value="3">
3
</option>


<option value="2">
2
</option>


<option value="1">
1
</option>



</select>





<textarea name="review"

placeholder="Your review"></textarea>




<button name="submit_review">

Submit

</button>



</form>



</body>


</html>