<?php

session_start();

require_once "../config/db.php";


if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}



if(isset($_POST['save'])){


$site_name=$_POST['site_name'];

$email=$_POST['email'];



$conn->query("


UPDATE settings


SET


site_name='$site_name',

email='$email'


WHERE id=1



");


$message="Settings updated";


}




$settings=$conn->query("

SELECT *

FROM settings

LIMIT 1


")->fetch_assoc();



?>


<!DOCTYPE html>

<html>

<head>

<title>
Settings
</title>


<link rel="stylesheet"

href="../assets/css/admin.css">


</head>


<body>


<?php include "../includes/navbar.php"; ?>



<div class="dashboard-container">


<h1>
System Settings
</h1>



<?php if(isset($message)){ ?>

<p>
<?= $message; ?>
</p>

<?php } ?>



<form method="POST"

class="admin-form">



<label>
Website Name
</label>


<input type="text"

name="site_name"

value="<?= $settings['site_name']; ?>">



<label>
Email
</label>


<input type="email"

name="email"

value="<?= $settings['email']; ?>">



<button type="submit"

name="save">

Save Settings

</button>



</form>



</div>



<?php include "../includes/footer.php"; ?>


</body>

</html>