<?php

session_start();

require_once "config/db.php";



if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}



$id=$_SESSION['user_id'];



$user=$conn->query("


SELECT *

FROM users

WHERE user_id='$id'


")->fetch_assoc();



?>



<!DOCTYPE html>

<html>


<head>


<title>

Dashboard

</title>



<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/admin.css">


</head>



<body>



<?php include "includes/navbar.php"; ?>




<section class="dashboard-page">


<div class="dashboard-container">



<h1>

Welcome,

<?php echo $user['name']; ?>

</h1>





<div class="dashboard-grid">





<div class="dashboard-card">


<h3>

My Orders

</h3>


<a href="order.php">

View Orders

</a>


</div>





<?php if($user['role']=="vendor"){ ?>



<div class="dashboard-card">


<h3>

Inventory

</h3>


<a href="inventory.php">

Manage

</a>


</div>





<div class="dashboard-card">


<h3>

Commission

</h3>


<a href="commission.php">

View

</a>


</div>




<?php } ?>





<?php if($user['role']=="admin"){ ?>



<div class="dashboard-card">


<h3>

Admin Panel

</h3>


<a href="admin/index.php">

Open

</a>


</div>



<?php } ?>





<div class="dashboard-card">


<h3>

Profile

</h3>


<a href="profile.php">

Edit

</a>


</div>





</div>




</div>


</section>



<?php include "includes/footer.php"; ?>


</body>

</html>