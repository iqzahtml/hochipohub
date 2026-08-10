<?php

require_once "../config.php";

require_once "../database/db.php";

require_once "../includes/session.php";





if($_SERVER['REQUEST_METHOD'] !== "POST"){


header(
"Location: ".BASE_URL."index.php"
);


exit();


}







$name = mysqli_real_escape_string(
$conn,
$_POST['name']
);



$email = mysqli_real_escape_string(
$conn,
$_POST['email']
);



$phone = mysqli_real_escape_string(
$conn,
$_POST['phone']
);



$password = password_hash(
$_POST['password'],
PASSWORD_DEFAULT
);



$role = mysqli_real_escape_string(
$conn,
$_POST['role']
);









/*
|--------------------------------------------------------------------------
| Check Existing Email
|--------------------------------------------------------------------------
*/


$check = $conn->query("

SELECT user_id

FROM users

WHERE email='$email'

");





if($check->num_rows > 0){


setFlashMessage(

"error",

"Email already registered."

);



header(
"Location: ".BASE_URL."index.php"
);


exit();


}








/*
|--------------------------------------------------------------------------
| Insert User
|--------------------------------------------------------------------------
*/


$sql="

INSERT INTO users

(

name,

email,

phone,

password,

role

)


VALUES

(

'$name',

'$email',

'$phone',

'$password',

'$role'

)



";







if($conn->query($sql)){



setFlashMessage(

"success",

"Registration successful. Please login."

);



}else{



setFlashMessage(

"error",

"Registration failed."

);



}






header(
"Location: ".BASE_URL."index.php"
);


exit();