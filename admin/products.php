<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}


$admin_id=$_SESSION['user_id'];

$check=$conn->query("

SELECT role 

FROM users 

WHERE user_id='$admin_id'

")->fetch_assoc();



if($check['role']!="admin"){

    exit("Access denied");

}




/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/


if(isset($_GET['delete'])){


$id=$_GET['delete'];


$conn->query("

DELETE FROM products

WHERE product_id='$id'

");


header("Location: products.php");

exit();

}





/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/


$products=$conn->query("


SELECT


products.*,

vendors.business_name,

categories.category_name


FROM products



LEFT JOIN vendors

ON products.vendor_id=vendors.vendor_id



LEFT JOIN categories

ON products.category_id=categories.category_id



ORDER BY products.product_id DESC


");



?>


<!DOCTYPE html>

<html>

<head>

<title>
Products Management
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>
Products Management
</h1>




<a href="../add_product.php" class="admin-btn">

Add Product

</a>




<table class="admin-table">


<tr>

<th>ID</th>

<th>Image</th>

<th>Name</th>

<th>Vendor</th>

<th>Category</th>

<th>Price</th>

<th>Action</th>


</tr>




<?php while($row=$products->fetch_assoc()){ ?>


<tr>


<td>

<?php echo $row['product_id']; ?>

</td>



<td>

<img width="50"

src="../assets/uploads/products/<?php echo $row['image']; ?>">

</td>




<td>

<?php echo $row['product_name']; ?>

</td>




<td>

<?php echo $row['business_name']; ?>

</td>



<td>

<?php echo $row['category_name']; ?>

</td>



<td>

RM <?php echo $row['price']; ?>

</td>




<td>


<a href="products.php?delete=<?php echo $row['product_id']; ?>"

onclick="return confirm('Delete product?')">

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