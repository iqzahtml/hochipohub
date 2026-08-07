<?php

require_once "config.php";
require_once "database/db.php";
require_once "includes/functions.php";
require_once "includes/session.php";


$pageTitle = "Profile";


requireLogin();


$userID = currentUserID();





/*
|--------------------------------------------------------------------------
| Update Profile
|--------------------------------------------------------------------------
*/


if($_SERVER['REQUEST_METHOD']=="POST"){


$name = mysqli_real_escape_string(
$conn,
$_POST['name']
);


$phone = mysqli_real_escape_string(
$conn,
$_POST['phone']
);



$sql = "

UPDATE users

SET

name='$name',

phone='$phone'


WHERE user_id='$userID'

";



$conn->query($sql);



}





/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/


$query = "

SELECT *

FROM users

WHERE user_id='$userID'

LIMIT 1

";


$result=$conn->query($query);


$user=$result->fetch_assoc();



?>

<?php include "includes/header.php"; ?>


<section class="profile-page">


<div class="page-title">

<h1>
My Profile
</h1>

</div>




<div class="profile-box">


<form method="POST">



<div class="form-group">

<label>
Name
</label>

<input

type="text"

name="name"

value="<?= htmlspecialchars($user['name']); ?>"

>

</div>





<div class="form-group">

<label>
Email
</label>

<input

type="email"

value="<?= htmlspecialchars($user['email']); ?>"

disabled

>

</div>





<div class="form-group">

<label>
Phone
</label>

<input

type="text"

name="phone"

value="<?= htmlspecialchars($user['phone']); ?>"

>

</div>




<button class="btn-primary">

Save Profile

</button>



</form>


</div>


</section>


<?php include "includes/footer.php"; ?>