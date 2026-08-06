<?php

session_start();

require_once "config/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}



$user_id=$_SESSION['user_id'];



if(isset($_POST['submit'])){


$product_id=$_POST['product_id'];

$rating=$_POST['rating'];

$comment=$_POST['comment'];



$stmt=$conn->prepare("


INSERT INTO reviews

(
customer_id,
product_id,
rating,
comment
)


VALUES(?,?,?,?)


");



$stmt->bind_param(

"iiis",

$user_id,

$product_id,

$rating,

$comment

);



$stmt->execute();



header("Location: review.php");

exit();


}




$products=$conn->query("


SELECT *

FROM products


");

?>


<!DOCTYPE html>

<html>


<head>

<title>Review</title>


<link rel="stylesheet" href="assets/css/style.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<section class="container">


<h1>

Write Review

</h1>




<form method="POST"
class="admin-form">


<label>

Product

</label>


<select name="product_id">


<?php while($p=$products->fetch_assoc()){ ?>


<option value="<?php echo $p['product_id']; ?>">


<?php echo $p['product_name']; ?>


</option>



<?php } ?>


</select>





<label>

Rating

</label>


<select name="rating">


<option value="5">
5 Stars
</option>


<option value="4">
4 Stars
</option>


<option value="3">
3 Stars
</option>


<option value="2">
2 Stars
</option>


<option value="1">
1 Star
</option>



</select>




<textarea

name="comment"

placeholder="Your review"

required></textarea>




<button name="submit">

Submit Review

</button>



</form>



</section>



<?php include "includes/footer.php"; ?>


</body>


</html>