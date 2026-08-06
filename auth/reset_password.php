<?php

session_start();

require_once "../database/db.php";



if(isset($_POST['reset'])){


$email=$_SESSION['reset_email'];


$password=$_POST['password'];



$new=password_hash(

$password,

PASSWORD_DEFAULT

);



$stmt=$conn->prepare("


UPDATE users

SET password=?,

otp=NULL,

otp_expiry=NULL


WHERE email=?


");



$stmt->bind_param(

"ss",

$new,

$email

);



$stmt->execute();



unset($_SESSION['reset_email']);



$_SESSION['success']="Password changed";


header("Location: ../login.php");


exit();


}



?>