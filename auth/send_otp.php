<?php

require_once "../config.php";

require_once "../database/db.php";

require_once "../includes/functions.php";

require_once "../mail/send_mail.php";





if($_SERVER['REQUEST_METHOD']!="POST"){


header(
"Location: forgot_password.php"
);


exit();


}






$email=mysqli_real_escape_string(

$conn,

$_POST['email']

);







/*
|--------------------------------------------------------------------------
| Check User
|--------------------------------------------------------------------------
*/


$result=$conn->query("

SELECT user_id,name

FROM users

WHERE email='$email'

");






if($result->num_rows==0){


setFlashMessage(

"error",

"Email not found."

);



header(
"Location: forgot_password.php"
);



exit();


}







$user=$result->fetch_assoc();





/*
|--------------------------------------------------------------------------
| Generate OTP
|--------------------------------------------------------------------------
*/


$otp = rand(
100000,
999999
);







/*
|--------------------------------------------------------------------------
| Store OTP
|--------------------------------------------------------------------------
*/


$conn->query("

INSERT INTO password_reset

(

user_id,

email,

otp,

expires_at

)


VALUES

(

'{$user['user_id']}',

'$email',

'$otp',

DATE_ADD(NOW(),INTERVAL 10 MINUTE)

)



");








$message="

<h2>

HochipoHub Password Reset

</h2>


<p>

Your OTP code is:

<b>$otp</b>

</p>


<p>

OTP expires in 10 minutes.

</p>

";








sendMail(

$email,

"Password Reset OTP",

$message

);







$_SESSION['reset_email']=$email;





header(

"Location: verify_otp.php"

);



exit();