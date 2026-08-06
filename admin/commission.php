<?php


session_start();


require_once "../config/db.php";



if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){

exit();

}




$commission=$conn->query("


SELECT



commission.*,


vendors.business_name



FROM commission



JOIN vendors

ON commission.vendor_id = vendors.vendor_id



ORDER BY commission_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Commission
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>



<h1>
Vendor Commission
</h1>




<table border="1">


<tr>

<th>
Vendor
</th>

<th>
Order
</th>

<th>
Rate
</th>

<th>
Amount
</th>

<th>
Status
</th>


</tr>



<?php while($row=$commission->fetch_assoc()){ ?>


<tr>


<td>

<?= htmlspecialchars($row['business_name']); ?>

</td>



<td>

#<?= $row['order_id']; ?>

</td>



<td>

<?= $row['commission_rate']; ?>%

</td>



<td>

RM <?= number_format($row['commission_amount'],2); ?>

</td>



<td>

<?= $row['status']; ?>

</td>



</tr>



<?php } ?>



</table>



</body>


</html>