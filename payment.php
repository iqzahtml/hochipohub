<?php

session_start();

require_once "database/db.php";


if(!isset($_SESSION['user_id'])){

    header("Location: auth/login.php");
    exit();

}


$user_id=$_SESSION['user_id'];

$order_id=$_GET['order_id'];



$order=$conn->prepare("


SELECT *


FROM orders


WHERE order_id=?

AND customer_id=?



");



$order->bind_param(

"ii",

$order_id,

$user_id

);



$order->execute();



$data=$order->get_result()->fetch_assoc();



if(!$data){

    exit("Order not found");

}



if(isset($_POST['pay'])){


$method=$_POST['payment_method'];



$payment=$conn->prepare("


INSERT INTO payments


(

order_id,

payment_method,

payment_status,

payment_date,

amount


)


VALUES

(

?,

?,

'Paid',

NOW(),

?

)



");



$payment->bind_param(

"isd",

$order_id,

$method,

$data['total_amount']

);



$payment->execute();





$update=$conn->prepare("


UPDATE orders


SET order_status='Processing'


WHERE order_id=?



");



$update->bind_param(

"i",

$order_id

);



$update->execute();



header("Location: order_details.php?id=".$order_id);


exit();



}


?>



<!DOCTYPE html>

<html>


<head>

<title>
Payment
</title>


<link rel="stylesheet" href="css/checkout.css">


</head>



<body>


<?php include "includes/navbar.php"; ?>



<div class="payment-box">


<h1>
Payment
</h1>



<h3>

Order #<?= $order_id; ?>

</h3>



<p>

Amount:

RM <?= number_format($data['total_amount'],2); ?>

</p>



<form method="POST">



<select name="payment_method">


<option value="FPX">

FPX

</option>


<option value="Credit Card">

Credit Card

</option>


<option value="Debit Card">

Debit Card

</option>


<option value="Cash">

Cash

</option>



</select>




<button name="pay">

Pay Now

</button>



</form>



</div>



</body>


</html>