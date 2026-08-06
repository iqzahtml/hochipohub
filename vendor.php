<?php

session_start();

require_once "database/db.php";


if(!isset($_GET['id'])){

    header("Location: index.php");
    exit();

}


$vendor_id=$_GET['id'];



$vendor=$conn->query("

SELECT *

FROM vendors

WHERE vendor_id='$vendor_id'

")->fetch_assoc();



$products=$conn->query("


SELECT *

FROM products


WHERE vendor_id='$vendor_id'


");



?>



<!DOCTYPE html>

<html>


<head>


<title>

<?php echo $vendor['business_name']; ?>

</title>



<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/vendor.css">

<link rel="stylesheet" href="assets/css/product.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<section class="vendor-page">


<div class="vendor-container">



<div class="vendor-header">



<div class="vendor-logo">


<img src="assets/uploads/vendors/<?php echo $vendor['logo']; ?>">


</div>




<div>


<h1>

<?php echo $vendor['business_name']; ?>

</h1>


<p>

<?php echo $vendor['description']; ?>

</p>


</div>



</div>






<h2>

Products

</h2>





<div class="product-grid">



<?php while($row=$products->fetch_assoc()){ ?>



<div class="product-card">



<div class="product-card-image">


<img src="assets/uploads/products/<?php echo $row['image']; ?>">


</div>



<div class="product-card-content">


<h3 class="product-card-title">

<?php echo $row['product_name']; ?>

</h3>




<p>

RM <?php echo number_format($row['price'],2); ?>

</p>



<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

View Product

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