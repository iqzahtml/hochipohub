<?php

session_start();

require_once "database/db.php";


if($_SESSION['role']!="admin"){

header("Location: index.php");

exit();

}



$users=$conn->query("

SELECT *

FROM users

ORDER BY user_id DESC

");


?>


<!DOCTYPE html>

<html>

<head>

<title>
Manage Users
</title>


<link rel="stylesheet" href="css/admin.css">

</head>


<body>


<h1>
Users Management
</h1>



<table border="1">


<tr>

<th>ID</th>

<th>Name</th>

<th>Email</th>

<th>Role</th>

<th>Status</th>

</tr>



<?php while($row=$users->fetch_assoc()){ ?>


<tr>


<td>

<?= $row['user_id']; ?>

</td>


<td>

<?= htmlspecialchars($row['name']); ?>

</td>


<td>

<?= htmlspecialchars($row['email']); ?>

</td>


<td>

<?= $row['role']; ?>

</td>


<td>

<?= $row['status']; ?>

</td>


</tr>


<?php } ?>


</table>



</body>

</html>