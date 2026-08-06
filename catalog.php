<?php

require_once "../config/db.php";



$products=$conn->query("


SELECT


products.*,


vendors.business_name,


categories.category_name



FROM products



JOIN vendors

ON products.vendor_id=vendors.vendor_id



JOIN categories

ON products.category_id=categories.category_id



WHERE products.status='Available'



ORDER BY products.product_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Catalog
</title>


<link rel="stylesheet" href="../assets/css/product.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<h1>
Product Catalog
</h1>




<div class="product-grid">


<?php while($row=$products->fetch_assoc()){ ?>



<div class="product-card">


<img src="../assets/uploads/products/<?= $row['image']; ?>">



<h3>

<?= htmlspecialchars($row['product_name']); ?>

</h3>



<p>

<?= htmlspecialchars($row['business_name']); ?>

</p>




<p>

RM <?= number_format($row['price'],2); ?>

</p>



<a href="product_details.php?id=<?= $row['product_id']; ?>">

View Product

</a>


</div>



<?php } ?>


</div>




</body>

</html>