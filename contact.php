<?php


session_start();


?>


<!DOCTYPE html>

<html>


<head>

<title>
Contact
</title>


<link rel="stylesheet" href="css/style.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<div class="contact-box">


<h1>
Contact HochipoHub
</h1>



<p>
Need help? Contact our support team.
</p>



<form method="POST">



<input type="text"

placeholder="Name"

required>




<input type="email"

placeholder="Email"

required>




<textarea placeholder="Message"></textarea>




<button>

Send Message

</button>



</form>



</div>



</body>


</html>