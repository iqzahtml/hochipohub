<?php

require_once "config/db.php";



$products=$conn->query("


SELECT


products.*,

vendors.business_name


FROM products



JOIN vendors

ON products.vendor_id=vendors.vendor_id



ORDER BY product_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>


<title>

Catalog

</title>



<link rel="stylesheet" href="assets/css/product.css">


</head>



<body>



<?php include "includes/navbar.php"; ?>



<section class="product-page">


<div class="product-container">



<h1>

Product Catalog

</h1>



<div class="product-grid">



<?php while($row=$products->fetch_assoc()){ ?>


<div class="product-card">


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



<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

View Product

</a>



</div>



<?php } ?>



</div>



</div>


</section>




<?php include "includes/footer.php"; ?>


</body>


</html>