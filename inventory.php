<?php

session_start();

require_once "database/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}



$user=$_SESSION['user_id'];



$vendor=$conn->query("

SELECT *

FROM vendors

WHERE user_id='$user'


")->fetch_assoc();



if(!$vendor){

echo "Vendor account required";

exit();

}



$vendor_id=$vendor['vendor_id'];





if(isset($_POST['update'])){


$product=$_POST['product_id'];

$stock=$_POST['stock'];



$conn->query("


UPDATE products SET


stock='$stock'


WHERE product_id='$product'


");


}





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

Inventory

</title>



<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/admin.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<section class="dashboard-page">


<div class="dashboard-container">



<h1>

Inventory Management

</h1>





<table class="admin-table">


<tr>


<th>
Product
</th>


<th>
Stock
</th>


<th>
Action
</th>


</tr>




<?php while($row=$products->fetch_assoc()){ ?>


<tr>



<td>

<?php echo $row['product_name']; ?>

</td>




<td>


<form method="POST">


<input

type="number"

name="stock"

value="<?php echo $row['stock']; ?>">



<input

type="hidden"

name="product_id"

value="<?php echo $row['product_id']; ?>">



</td>




<td>


<button name="update">

Update

</button>


</form>


</td>



</tr>



<?php } ?>



</table>



</div>


</section>



<?php include "includes/footer.php"; ?>


</body>

</html>