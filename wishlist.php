<?php

session_start();

require_once "config/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: login.php");
    exit();

}


$user_id=$_SESSION['user_id'];



$query="


SELECT


wishlist.wishlist_id,


products.*,


vendors.business_name


FROM wishlist



JOIN products

ON wishlist.product_id=products.product_id



JOIN vendors

ON products.vendor_id=vendors.vendor_id



WHERE wishlist.customer_id=?



";



$stmt=$conn->prepare($query);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result=$stmt->get_result();


?>



<!DOCTYPE html>

<html>


<head>

<title>Wishlist | HochipoHub</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/wishlist.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<section class="wishlist-page">


<div class="wishlist-container">


<h1>

My Wishlist

</h1>




<div class="wishlist-grid">



<?php while($row=$result->fetch_assoc()){ ?>



<div class="wishlist-card">



<img src="assets/uploads/products/<?php echo $row['image']; ?>">





<h3>

<?php echo $row['product_name']; ?>

</h3>



<p>

<?php echo $row['business_name']; ?>

</p>



<strong>

RM <?php echo number_format($row['price'],2); ?>

</strong>



<div>


<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

View

</a>


<a href="ajax/add_wishlist.php?remove=<?php echo $row['wishlist_id']; ?>">

Remove

</a>


</div>



</div>



<?php } ?>



</div>


</div>


</section>




<?php include "includes/footer.php"; ?>


</body>


</html>