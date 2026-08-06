<?php

require_once "config/db.php";


$key=$_GET['q'] ?? '';



$stmt=$conn->prepare("


SELECT


products.*,

vendors.business_name


FROM products


JOIN vendors

ON products.vendor_id=vendors.vendor_id



WHERE product_name LIKE ?



");



$value="%".$key."%";


$stmt->bind_param(
"s",
$value
);



$stmt->execute();



$result=$stmt->get_result();



?>


<!DOCTYPE html>

<html>


<head>


<title>

Search Result

</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/product.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<section class="product-page">


<div class="product-container">


<h1>

Search : <?php echo $key; ?>

</h1>



<div class="product-grid">



<?php while($row=$result->fetch_assoc()){ ?>



<div class="product-card">


<img src="assets/uploads/products/<?php echo $row['image']; ?>">



<h3>

<?php echo $row['product_name']; ?>

</h3>


<p>

RM <?php echo $row['price']; ?>

</p>



<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

View

</a>



</div>



<?php } ?>



</div>


</div>


</section>



<?php include "includes/footer.php"; ?>


</body>

</html>