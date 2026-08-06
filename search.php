<?php

require_once "../database/db.php";



$keyword=$_GET['q'] ?? '';



$search="%".$keyword."%";



$stmt=$conn->prepare("


SELECT


products.*,


vendors.business_name



FROM products



JOIN vendors


ON products.vendor_id=vendors.vendor_id



WHERE products.product_name LIKE ?


AND products.status='Available'


ORDER BY product_id DESC



");


$stmt->bind_param(

"s",

$search

);



$stmt->execute();



$result=$stmt->get_result();



?>


<!DOCTYPE html>

<html>


<head>

<title>
Search
</title>


<link rel="stylesheet" href="../assets/css/product.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<h1>

Search Result:
<?= htmlspecialchars($keyword); ?>

</h1>




<div class="product-grid">


<?php while($row=$result->fetch_assoc()){ ?>



<div class="product-card">


<img src="../assets/uploads/products/<?= $row['image']; ?>">



<h3>

<?= htmlspecialchars($row['product_name']); ?>

</h3>



<p>

RM <?= number_format($row['price'],2); ?>

</p>



<a href="product_details.php?id=<?= $row['product_id']; ?>">

View

</a>



</div>



<?php } ?>



</div>



</body>


</html>