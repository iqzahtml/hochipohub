<?php

session_start();

require_once "../database/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../auth/login.php");

exit();

}



$user_id=$_SESSION['user_id'];



if(isset($_POST['update'])){


$name=$_POST['name'];

$phone=$_POST['phone'];



$stmt=$conn->prepare("


UPDATE users


SET


name=?,


phone=?


WHERE user_id=?



");


$stmt->bind_param(

"ssi",

$name,

$phone,

$user_id

);



$stmt->execute();



$message="Profile updated";



}



$user=$conn->query("


SELECT *


FROM users


WHERE user_id=$user_id



")->fetch_assoc();



?>


<!DOCTYPE html>

<html>


<head>

<title>
Profile
</title>


<link rel="stylesheet" href="../assets/css/style.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<h1>
My Profile
</h1>




<?php if(isset($message)){ ?>

<p>

<?= $message; ?>

</p>

<?php } ?>



<form method="POST">



<label>
Name
</label>


<input type="text"

name="name"

value="<?= htmlspecialchars($user['name']); ?>">





<label>
Email
</label>


<input type="email"

value="<?= htmlspecialchars($user['email']); ?>"

readonly>





<label>
Phone
</label>


<input type="text"

name="phone"

value="<?= htmlspecialchars($user['phone']); ?>">





<button name="update">

Update

</button>



</form>



</body>


</html>