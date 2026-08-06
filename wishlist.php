<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



$stmt=$conn->prepare("


SELECT


wishlist.wishlist_id,


products.product_id,


products.product_name,


products.price,


products.image,


vendors.business_name



FROM wishlist



JOIN products


ON wishlist.product_id=products.product_id



JOIN vendors


ON products.vendor_id=vendors.vendor_id



WHERE wishlist.user_id=?



ORDER BY wishlist_id DESC



");



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

<title>
Wishlist
</title>


<link rel="stylesheet" href="../assets/css/wishlist.css">


</head>



<body>


<?php include "../includes/navbar.php"; ?>



<div class="wishlist-container">



<h1>

My Wishlist

</h1>




<?php if($result->num_rows==0){ ?>

<p>
No wishlist item.
</p>

<?php } ?>





<?php while($row=$result->fetch_assoc()){ ?>



<div class="wishlist-card">



<img src="../assets/uploads/products/<?= $row['image']; ?>">



<h3>

<?= htmlspecialchars($row['product_name']); ?>

</h3>



<p>

Vendor:

<?= htmlspecialchars($row['business_name']); ?>

</p>



<p>

RM <?= number_format($row['price'],2); ?>

</p>




<button class="remove-wishlist"

data-id="<?= $row['product_id']; ?>">


Remove


</button>



<a href="product_details.php?id=<?= $row['product_id']; ?>">

View Product

</a>




</div>



<?php } ?>



</div>



<script src="../assets/js/wishlist.js"></script>



</body>


</html>