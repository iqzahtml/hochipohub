<?php


session_start();


require_once "../database/db.php";



if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){

exit();

}



$reviews=$conn->query("


SELECT


reviews.*,


users.name,


products.product_name



FROM reviews



JOIN users

ON reviews.customer_id = users.user_id



JOIN products

ON reviews.product_id = products.product_id



ORDER BY review_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Review Management
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>



<h1>
Reviews
</h1>



<table border="1">


<tr>

<th>
Customer
</th>

<th>
Product
</th>

<th>
Rating
</th>

<th>
Review
</th>

<th>
Status
</th>


</tr>




<?php while($row=$reviews->fetch_assoc()){ ?>


<tr>


<td>

<?= htmlspecialchars($row['name']); ?>

</td>



<td>

<?= htmlspecialchars($row['product_name']); ?>

</td>



<td>

<?= $row['rating']; ?>/5

</td>



<td>

<?= htmlspecialchars($row['review']); ?>

</td>



<td>

<?= $row['status']; ?>

</td>


</tr>



<?php } ?>


</table>



</body>


</html>