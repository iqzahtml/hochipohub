<?php

session_start();


?>


<!DOCTYPE html>

<html>

<head>

<title>
Forgot Password
</title>

</head>


<body>


<form method="POST"

action="send_otp.php">


<input type="email"

name="email"

placeholder="Email"

required>



<button>

Send OTP

</button>


</form>


</body>

</html>