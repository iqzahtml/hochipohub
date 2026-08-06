<?php


session_start();


require_once "../database/db.php";


if(!isset($_SESSION['user_id']) || $_SESSION['role']!="admin"){

exit();

}




if(isset($_POST['update'])){


$name=$_POST['name'];

$email=$_POST['email'];



$stmt=$conn->prepare("


UPDATE users

SET

name=?,

email=?

WHERE user_id=?


");



$stmt->bind_param(

"ssi",

$name,

$email,

$_SESSION['user_id']

);



$stmt->execute();



$message="Settings Updated";



}



$user=$conn->query("


SELECT *

FROM users

WHERE user_id=".$_SESSION['user_id']



)->fetch_assoc();



?>


<!DOCTYPE html>

<html>


<head>

<title>
Admin Settings
</title>


<link rel="stylesheet" href="../assets/css/admin.css">


</head>


<body>



<h1>
Account Settings
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

name="email"

value="<?= htmlspecialchars($user['email']); ?>">





<button name="update">

Update

</button>



</form>



</body>


</html>