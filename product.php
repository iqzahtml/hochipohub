<?php

require_once "database/db.php";



$products=$conn->query("


SELECT


products.*,


vendors.business_name



FROM products



JOIN vendors

ON products.vendor_id=vendors.vendor_id



WHERE products.status='Available'


ORDER BY product_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Products
</title>


<link rel="stylesheet" href="css/product.css">


</head>


<body>


<?php include "../includes/navbar.php"; ?>



<h1>
All Products
</h1>




<div class="product-grid">


<?php while($row=$products->fetch_assoc()){ ?>



<div class="product-card">



<img src="uploads/products/<?= $row['image']; ?>">



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




<a href="product_details.php?id=<?= $row['product_id']; ?>">

Details

</a>



</div>



<?php } ?>



</div>



</body>

</html>