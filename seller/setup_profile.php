<?php


session_start();


require_once "../config/db.php";



if(!isset($_SESSION['user_id'])){


header("Location: ../auth/login.php");


exit();


}



$user_id=$_SESSION['user_id'];





if(isset($_POST['save'])){


$business_name = $_POST['business_name'];

$description = $_POST['business_description'];

$address = $_POST['business_address'];

$delivery = $_POST['delivery_method'];



$check = $conn->prepare("

SELECT vendor_id

FROM vendors

WHERE user_id = ?

");



$check->bind_param(

"i",

$user_id

);



$check->execute();



$result=$check->get_result();





if($result->num_rows > 0){



$update=$conn->prepare("

UPDATE vendors

SET

business_name=?,

business_description=?,

business_address=?,

delivery_method=?

WHERE user_id=?

");



$update->bind_param(

"ssssi",

$business_name,

$description,

$address,

$delivery,

$user_id

);



$update->execute();



}

else{



$insert=$conn->prepare("

INSERT INTO vendors

(

user_id,

business_name,

business_description,

business_address,

delivery_method,

approval_status

)

VALUES

(?,?,?,?,?,'Pending')

");



$insert->bind_param(

"issss",

$user_id,

$business_name,

$description,

$address,

$delivery

);



$insert->execute();



}



header("Location: dashboard.php");

exit();



}


?>



<!DOCTYPE html>

<html>

<head>

<title>
Setup Vendor Profile
</title>


<link rel="stylesheet" href="../assets/css/vendor.css">


</head>


<body>



<?php include "../includes/navbar.php"; ?>



<div class="vendor-container">


<h1>
Vendor Profile
</h1>



<form method="POST">



<label>
Business Name
</label>


<input type="text"

name="business_name"

required>



<label>
Business Description
</label>


<textarea name="business_description"></textarea>




<label>
Business Address
</label>


<textarea name="business_address"></textarea>




<label>
Delivery Method
</label>


<select name="delivery_method">


<option value="Pickup">
Pickup
</option>


<option value="Postage">
Postage
</option>


<option value="Both">
Both
</option>


</select>




<button name="save">

Save Profile

</button>



</form>


</div>



</body>

</html>