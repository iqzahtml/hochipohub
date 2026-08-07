<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Payment Page
|--------------------------------------------------------------------------
|
| Payment selection before creating order
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Payment";



requireLogin();



if($_SERVER['REQUEST_METHOD'] !== 'POST'){


    header(

        "Location: cart.php"

    );


    exit();


}



$name = htmlspecialchars($_POST['name']);

$phone = htmlspecialchars($_POST['phone']);

$address = htmlspecialchars($_POST['address']);

$total = htmlspecialchars($_POST['total']);



?>



<?php include "includes/header.php"; ?>







<section class="payment-page">






<div class="page-title">


<h1>

Choose Payment Method

</h1>



<p>

Select your preferred payment option.

</p>


</div>








<div class="payment-container">







<form

action="order.php"

method="POST"

>






<input

type="hidden"

name="name"

value="<?= $name; ?>"

>



<input

type="hidden"

name="phone"

value="<?= $phone; ?>"

>



<input

type="hidden"

name="address"

value="<?= $address; ?>"

>



<input

type="hidden"

name="total"

value="<?= $total; ?>"

>







<div class="payment-option">



<label>


<input

type="radio"

name="payment_method"

value="Online Banking"

required

>


<span>

Online Banking

</span>


</label>



</div>








<div class="payment-option">



<label>


<input

type="radio"

name="payment_method"

value="E-Wallet"

>


<span>

E-Wallet

</span>


</label>



</div>








<div class="payment-option">



<label>


<input

type="radio"

name="payment_method"

value="Cash On Delivery"

>


<span>

Cash On Delivery

</span>


</label>



</div>








<div class="payment-summary">


<h3>

Total Payment

</h3>


<h2>

RM <?= number_format($total,2); ?>

</h2>



</div>







<button

type="submit"

class="btn-primary"

>


Confirm Order


</button>






</form>







</div>







</section>








<?php include "includes/footer.php"; ?>