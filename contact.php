<?php

require_once "database/db.php";



if(isset($_POST['send'])){


$name=$_POST['name'];

$email=$_POST['email'];

$message=$_POST['message'];



$stmt=$conn->prepare("


INSERT INTO contact_messages

(
name,
email,
message
)


VALUES(?,?,?)


");



$stmt->bind_param(

"sss",

$name,

$email,

$message

);



$stmt->execute();



$success="Message sent successfully";


}



?>


<!DOCTYPE html>

<html>


<head>


<title>

Contact Us

</title>



<link rel="stylesheet" href="assets/css/style.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>



<section class="container">


<h1>

Contact HochipoHub

</h1>



<?php

if(isset($success)){

echo "<p>$success</p>";

}

?>




<form method="POST"
class="admin-form">



<input

type="text"

name="name"

placeholder="Your Name"

required>




<input

type="email"

name="email"

placeholder="Email"

required>





<textarea

name="message"

placeholder="Message"

required></textarea>




<button name="send">

Send Message

</button>



</form>



</section>




<?php include "includes/footer.php"; ?>


</body>

</html>