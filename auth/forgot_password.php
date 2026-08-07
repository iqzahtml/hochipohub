<?php

require_once "../config.php";

require_once "../database/db.php";

require_once "../includes/functions.php";


$pageTitle="Forgot Password";


?>


<?php include "../includes/header.php"; ?>



<section class="auth-page">


<div class="auth-box">


<h1>

Forgot Password

</h1>


<p>

Enter your email to receive OTP.

</p>





<form action="send_otp.php" method="POST">



<div class="form-group">


<label>

Email

</label>


<input

type="email"

name="email"

required

>


</div>





<button

class="btn-primary"

type="submit"

>

Send OTP

</button>



</form>



</div>


</section>



<?php include "../includes/footer.php"; ?>