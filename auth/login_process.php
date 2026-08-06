<?php

session_start();

require_once "../database/db.php";



if(isset($_POST['login'])){


$email=$_POST['email'];

$password=$_POST['password'];




$stmt=$conn->prepare("


SELECT *

FROM users

WHERE email=?



");


$stmt->bind_param(
"s",
$email
);


$stmt->execute();



$result=$stmt->get_result();



if($result->num_rows==1){


$user=$result->fetch_assoc();



if(password_verify($password,$user['password'])){


$_SESSION['user_id']=$user['user_id'];

$_SESSION['name']=$user['name'];

$_SESSION['role']=$user['role'];



if($user['role']=="admin"){


header("Location: ../admin/dashboard.php");


}

elseif($user['role']=="vendor"){


header("Location: ../seller/dashboard.php");


}

else{


header("Location: ../dashboard.php");


}



exit();



}



}



$_SESSION['error']="Invalid email or password";


header("Location: ../login.php");


exit();


?>