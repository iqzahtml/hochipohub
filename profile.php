<?php

session_start();

require_once "database/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: login.php");

exit();

}


$id=$_SESSION['user_id'];



if(isset($_POST['update'])){


$name=$_POST['name'];

$email=$_POST['email'];

$phone=$_POST['phone'];



$conn->query("


UPDATE users SET


name='$name',

email='$email',

phone='$phone'


WHERE user_id='$id'


");



}



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

Profile

</title>


<link rel="stylesheet" href="assets/css/style.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<section class="container">


<h1>

My Profile

</h1>



<form method="POST"
class="admin-form">



<input

type="text"

name="name"

value="<?php echo $user['name']; ?>">





<input

type="email"

name="email"

value="<?php echo $user['email']; ?>">





<input

type="text"

name="phone"

value="<?php echo $user['phone']; ?>">





<button name="update">

Save Profile

</button>



</form>



</section>




<?php include "includes/footer.php"; ?>


</body>

</html>