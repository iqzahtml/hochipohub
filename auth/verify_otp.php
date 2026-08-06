<?php

session_start();

require_once "database/db.php";



$email=$_SESSION['otp_email'] ?? '';

$otp=$_POST['otp'];



$stmt=$conn->prepare("


SELECT *

FROM users

WHERE email=?

AND otp=?


");


$stmt->bind_param(

"ss",

$email,

$otp

);



$stmt->execute();



$result=$stmt->get_result();



if($result->num_rows==1){


$user=$result->fetch_assoc();



if(strtotime($user['otp_expiry']) >= time()){


$_SESSION['reset_email']=$email;


header("Location: ../reset_password.php");


exit();



}



}



$_SESSION['error']="Invalid OTP";


header("Location: ../verify_otp.php");


exit();



?>