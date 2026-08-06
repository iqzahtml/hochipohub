<?php

session_start();

require_once "../config/db.php";

require_once "../mail/send_mail.php";



if(!isset($_POST['email'])){


    header("Location: ../forgot_password.php");

    exit();

}



$email = trim($_POST['email']);





/*
|--------------------------------------------------------------------------
| CHECK USER EMAIL
|--------------------------------------------------------------------------
*/


$stmt = $conn->prepare("

SELECT user_id, name

FROM users

WHERE email = ?

");



$stmt->bind_param(

    "s",

    $email

);



$stmt->execute();



$result = $stmt->get_result();





if($result->num_rows == 0){


    $_SESSION['error'] = "Email not registered";


    header("Location: ../forgot_password.php");


    exit();


}




$user = $result->fetch_assoc();





/*
|--------------------------------------------------------------------------
| GENERATE OTP
|--------------------------------------------------------------------------
*/


$otp = random_int(

    100000,

    999999

);



$expiry = date(

    "Y-m-d H:i:s",

    strtotime("+10 minutes")

);






/*
|--------------------------------------------------------------------------
| SAVE OTP TO DATABASE
|--------------------------------------------------------------------------
*/


$update = $conn->prepare("


UPDATE users


SET


reset_code = ?,


reset_expiry = ?


WHERE email = ?



");




$update->bind_param(

    "sss",

    $otp,

    $expiry,

    $email

);




if(!$update->execute()){



    $_SESSION['error']="Failed to generate OTP";


    header("Location: ../forgot_password.php");


    exit();


}







/*
|--------------------------------------------------------------------------
| SEND OTP EMAIL
|--------------------------------------------------------------------------
*/


$message = "


<div style='font-family:Arial;padding:20px;'>


<h2>
HochipoHub Password Reset
</h2>



<p>
Hello {$user['name']},
</p>



<p>
Your OTP verification code is:
</p>



<h1 style='letter-spacing:5px;'>

$otp

</h1>



<p>
This code will expire in 10 minutes.
</p>



<p>
If you did not request this, please ignore this email.
</p>



</div>


";





$mailSent = sendMail(


    $email,


    "HochipoHub OTP Verification",


    $message


);







if($mailSent){



    $_SESSION['otp_email'] = $email;



    $_SESSION['success']="OTP has been sent to your email";



    header("Location: ../verify_otp.php");


    exit();



}

else{



    $_SESSION['error']="Failed to send email";


    header("Location: ../forgot_password.php");


    exit();



}



?>