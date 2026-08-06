<?php

session_start();

require_once "../config/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}



$payments=$conn->query("


SELECT


payments.*,

users.name



FROM payments



JOIN users

ON payments.user_id = users.user_id



ORDER BY payment_id DESC



");



?>


<!DOCTYPE html>

<html>

<head>

<title>
Payment Management
</title>


<link rel="stylesheet"

href="../assets/css/admin.css">


</head>



<body>


<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>
Payment Management
</h1>



<table class="admin-table">


<tr>

<th>
Payment ID
</th>

<th>
Customer
</th>

<th>
Amount
</th>

<th>
Method
</th>

<th>
Status
</th>

</tr>



<?php while($row=$payments->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['payment_id']; ?>

</td>



<td>

<?= $row['name']; ?>

</td>



<td>

RM <?= $row['amount']; ?>

</td>



<td>

<?= $row['payment_method']; ?>

</td>



<td>

<?= $row['status']; ?>

</td>


</tr>


<?php } ?>


</table>



</div>



<?php include "../includes/footer.php"; ?>


</body>

</html>