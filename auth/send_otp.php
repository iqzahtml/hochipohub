<?php

session_start();

require_once "../config/db.php";



$email=$_POST['email'];




$stmt=$conn->prepare("


SELECT user_id

FROM users

WHERE email=?


");


$stmt->bind_param(
"s",
$email
);


$stmt->execute();


$result=$stmt->get_result();



if($result->num_rows==0){

$_SESSION['error']="Email not found";

header("Location: ../forgot_password.php");

exit();

}



$otp=random_int(
100000,
999999
);



$expiry=date(
"Y-m-d H:i:s",
strtotime("+10 minutes")
);




$update=$conn->prepare("


UPDATE users

SET otp=?,

otp_expiry=?

WHERE email=?



");



$update->bind_param(

"sss",

$otp,

$expiry,

$email

);



$update->execute();



/*
|--------------------------------------------------------------------------
| EMAIL SYSTEM PLACEHOLDER
|--------------------------------------------------------------------------
| Integrate PHPMailer here
|--------------------------------------------------------------------------
*/



$_SESSION['otp_email']=$email;



$_SESSION['success']="OTP sent";


header("Location: ../verify_otp.php");


exit();



?>