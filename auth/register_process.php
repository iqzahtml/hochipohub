<?php

session_start();

require_once "../database/db.php";


if(isset($_POST['register'])){


$name = trim($_POST['name']);

$email = trim($_POST['email']);

$password = $_POST['password'];

$role = $_POST['role'] ?? "customer";




$check = $conn->prepare("

SELECT user_id

FROM users

WHERE email=?

");


$check->bind_param(
"s",
$email
);


$check->execute();


$result=$check->get_result();



if($result->num_rows > 0){

$_SESSION['error']="Email already registered";

header("Location: ../register.php");

exit();

}




$hash=password_hash(
$password,
PASSWORD_DEFAULT
);




$stmt=$conn->prepare("


INSERT INTO users

(name,email,password,role)

VALUES (?,?,?,?)



");



$stmt->bind_param(

"ssss",

$name,

$email,

$hash,

$role

);



if($stmt->execute()){


$_SESSION['success']="Registration successful";


header("Location: ../login.php");


}else{


$_SESSION['error']="Registration failed";


header("Location: ../register.php");


}



exit();


}

?>