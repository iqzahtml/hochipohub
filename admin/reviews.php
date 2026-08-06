<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}




if(isset($_GET['delete'])){


$id=$_GET['delete'];



$conn->query("


DELETE FROM reviews

WHERE review_id='$id'


");



header("Location: reviews.php");

exit();


}





$reviews=$conn->query("


SELECT


reviews.*,

users.name,

products.product_name



FROM reviews



JOIN users

ON reviews.user_id=users.user_id



JOIN products

ON reviews.product_id=products.product_id



ORDER BY review_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Reviews
</title>


<link rel="stylesheet"

href="../assets/css/admin.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>
Reviews Management
</h1>



<table class="admin-table">


<tr>


<th>
Product
</th>


<th>
User
</th>


<th>
Rating
</th>


<th>
Comment
</th>


<th>
Action
</th>


</tr>




<?php while($row=$reviews->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['product_name']; ?>

</td>


<td>

<?= $row['name']; ?>

</td>


<td>

<?= $row['rating']; ?>/5

</td>


<td>

<?= $row['comment']; ?>

</td>


<td>


<a href="?delete=<?= $row['review_id']; ?>"

onclick="return confirm('Delete review?')">

Delete

</a>


</td>


</tr>


<?php } ?>


</table>



</div>



<?php include "../includes/footer.php"; ?>


</body>

</html>