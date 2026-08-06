<?php


session_start();


require_once "../database/db.php";



if($_SESSION['role']!="admin"){

exit();

}




$vendors=$conn->query("


SELECT


vendors.*,


users.name,


users.email



FROM vendors



JOIN users

ON vendors.user_id = users.user_id



ORDER BY vendor_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Vendor Management
</title>

<link rel="stylesheet" href="../assets/css/admin.css">


</head>



<body>



<h1>
Vendors
</h1>




<table border="1">


<tr>

<th>
Business
</th>

<th>
Owner
</th>

<th>
Email
</th>

<th>
Status
</th>

</tr>



<?php while($row=$vendors->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['business_name']; ?>

</td>


<td>

<?= $row['name']; ?>

</td>


<td>

<?= $row['email']; ?>

</td>


<td>

<?= $row['approval_status']; ?>

</td>



</tr>



<?php } ?>



</table>



</body>


</html>