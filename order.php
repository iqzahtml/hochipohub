<?php

session_start();

require_once "config/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}



$user=$_SESSION['user_id'];



$result=$conn->query("


SELECT *

FROM orders

WHERE customer_id='$user'


ORDER BY order_date DESC



");

?>


<!DOCTYPE html>

<html>


<head>

<title>My Orders</title>


<link rel="stylesheet" href="assets/css/style.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<section class="container">


<h1>

My Orders

</h1>




<div class="admin-panel">


<table class="admin-table">


<tr>


<th>
Order ID
</th>


<th>
Date
</th>


<th>
Total
</th>


<th>
Status
</th>


<th>
Action
</th>


</tr>




<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>

#<?php echo $row['order_id']; ?>

</td>



<td>

<?php echo $row['order_date']; ?>

</td>



<td>

RM <?php echo number_format($row['total_amount'],2); ?>

</td>



<td>

<?php echo $row['order_status']; ?>

</td>




<td>


<a href="order_details.php?id=<?php echo $row['order_id']; ?>">

View

</a>


</td>



</tr>


<?php } ?>


</table>



</div>



</section>



<?php include "includes/footer.php"; ?>


</body>

</html>