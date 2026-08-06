<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id'])){

exit();

}



$user_id=$_SESSION['user_id'];



$stmt=$conn->prepare("


SELECT


commission.*,


vendors.business_name



FROM commission



JOIN vendors


ON commission.vendor_id=vendors.vendor_id



WHERE vendors.user_id=?



ORDER BY commission_id DESC



");



$stmt->bind_param(

"i",

$user_id

);



$stmt->execute();



$result=$stmt->get_result();



?>



<!DOCTYPE html>

<html>


<head>

<title>
Commission
</title>


</head>


<body>



<h1>
Commission History
</h1>




<table border="1">


<tr>

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




<?php while($row=$result->fetch_assoc()){ ?>


<tr>


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