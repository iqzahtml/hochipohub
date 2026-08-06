<?php

session_start();

require_once "../config/db.php";



if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}



$user_id=$_SESSION['user_id'];



$admin=$conn->query("

SELECT role

FROM users

WHERE user_id='$user_id'

")->fetch_assoc();



if($admin['role']!="admin"){

    exit("Access denied");

}






if(isset($_GET['delete'])){


$id=$_GET['delete'];



$conn->query("

DELETE FROM users

WHERE user_id='$id'

");


header("Location: users.php");

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


<link rel="stylesheet" href="../assets/css/style.css">

<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<section class="dashboard-page">


<div class="dashboard-container">



<h1>
Users Management
</h1>




<table class="admin-table">


<tr>

<th>
ID
</th>

<th>
Name
</th>

<th>
Email
</th>

<th>
Role
</th>

<th>
Action
</th>

</tr>




<?php while($row=$users->fetch_assoc()){ ?>


<tr>


<td>

<?php echo $row['user_id']; ?>

</td>



<td>

<?php echo $row['name']; ?>

</td>




<td>

<?php echo $row['email']; ?>

</td>




<td>

<?php echo $row['role']; ?>

</td>




<td>


<a href="users.php?delete=<?php echo $row['user_id']; ?>"
onclick="return confirm('Delete user?')">

Delete

</a>



</td>



</tr>



<?php } ?>



</table>



</div>


</section>




<?php include "../includes/footer.php"; ?>


</body>

</html>