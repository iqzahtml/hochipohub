<?php

require_once "../config.php";

require_once "../database/db.php";

require_once "../includes/functions.php";





if(!isset($_SESSION['reset_email'])){


header(

"Location: forgot_password.php"

);


exit();


}




$email=$_SESSION['reset_email'];






if($_SERVER['REQUEST_METHOD']=="POST"){



$otp=mysqli_real_escape_string(

$conn,

$_POST['otp']

);






$result=$conn->query("

SELECT *

FROM password_reset

WHERE email='$email'

AND otp='$otp'

AND expires_at > NOW()

ORDER BY reset_id DESC

LIMIT 1


");







if($result->num_rows>0){



$_SESSION['otp_verified']=true;



header(

"Location: reset_password.php"

);



exit();



}else{



setFlashMessage(

"error",

"Invalid or expired OTP."

);



}



}



?>



<?php include "../includes/header.php"; ?>



<section class="auth-page">


<div class="auth-box">


<h1>

Verify OTP

</h1>




<form method="POST">



<div class="form-group">


<label>

OTP Code

</label>


<input

type="text"

name="otp"

maxlength="6"

required

>


</div>





<button class="btn-primary">

Verify

</button>



</form>



</div>


</section>



<?php include "../includes/footer.php"; ?>