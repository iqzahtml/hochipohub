<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}




$vendors=$conn->query("


SELECT *


FROM vendors


ORDER BY vendor_id DESC



");





if(isset($_GET['approve'])){


$id=$_GET['approve'];



$conn->query("


UPDATE vendors


SET status='approved'


WHERE vendor_id='$id'


");



header("Location: vendors.php");


exit();


}





if(isset($_GET['reject'])){


$id=$_GET['reject'];



$conn->query("


UPDATE vendors


SET status='rejected'


WHERE vendor_id='$id'


");



header("Location: vendors.php");


exit();


}



?>


<!DOCTYPE html>

<html>


<head>


<title>
Vendor Management
</title>


<link rel="stylesheet"

href="../assets/css/admin.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>

Vendor Management

</h1>




<table class="admin-table">


<tr>


<th>ID</th>

<th>Business Name</th>

<th>Email</th>

<th>Status</th>

<th>Action</th>


</tr>




<?php while($row=$vendors->fetch_assoc()){ ?>



<tr>


<td>

<?php echo $row['vendor_id']; ?>

</td>



<td>

<?php echo $row['business_name']; ?>

</td>




<td>

<?php echo $row['email']; ?>

</td>




<td>

<?php echo $row['status']; ?>

</td>




<td>



<a href="?approve=<?php echo $row['vendor_id']; ?>">

Approve

</a>



|

<a href="?reject=<?php echo $row['vendor_id']; ?>">

Reject

</a>



</td>



</tr>




<?php } ?>



</table>



</div>



<?php include "../includes/footer.php"; ?>


</body>

</html>