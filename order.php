<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Create Order
|--------------------------------------------------------------------------
|
| Flow:
| Cart -> Order -> Order Details
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Order Success";



requireLogin();



$userID = currentUserID();






if($_SERVER['REQUEST_METHOD'] !== 'POST'){


    header(
        "Location: cart.php"
    );


    exit();


}





$name = mysqli_real_escape_string(
    $conn,
    $_POST['name']
);


$phone = mysqli_real_escape_string(
    $conn,
    $_POST['phone']
);


$address = mysqli_real_escape_string(
    $conn,
    $_POST['address']
);


$total = mysqli_real_escape_string(
    $conn,
    $_POST['total']
);


$paymentMethod = mysqli_real_escape_string(
    $conn,
    $_POST['payment_method']
);







/*
|--------------------------------------------------------------------------
| Get Cart Items
|--------------------------------------------------------------------------
*/


$cartQuery = "

SELECT

cart.product_id,

cart.quantity,

products.price


FROM cart



INNER JOIN products

ON cart.product_id = products.product_id



WHERE cart.user_id='$userID'


";



$cartResult = $conn->query($cartQuery);




if(!$cartResult || $cartResult->num_rows == 0){


    header(
        "Location: cart.php"
    );


    exit();


}






/*
|--------------------------------------------------------------------------
| Create Order
|--------------------------------------------------------------------------
*/


$orderSQL = "

INSERT INTO orders

(

user_id,

total_amount,

shipping_address,

payment_method,

payment_status,

order_status,

created_at

)


VALUES

(

'$userID',

'$total',

'$address',

'$paymentMethod',

'Pending',

'Processing',

NOW()

)


";





if($conn->query($orderSQL)){


    $orderID = $conn->insert_id;



}else{


    die("Order failed");


}









/*
|--------------------------------------------------------------------------
| Insert Order Details
|--------------------------------------------------------------------------
*/


while($item = $cartResult->fetch_assoc()){



$productID = $item['product_id'];

$quantity = $item['quantity'];

$price = $item['price'];





$detailSQL = "

INSERT INTO order_details

(

order_id,

product_id,

quantity,

price

)


VALUES

(

'$orderID',

'$productID',

'$quantity',

'$price'

)



";



$conn->query($detailSQL);



}







/*
|--------------------------------------------------------------------------
| Clear Cart
|--------------------------------------------------------------------------
*/


$conn->query("

DELETE FROM cart

WHERE user_id='$userID'

");








?>



<?php include "includes/header.php"; ?>







<section class="order-success">





<div class="success-box">





<i class="fa-solid fa-circle-check"></i>




<h1>

Order Placed Successfully!

</h1>





<p>

Thank you for shopping with HochipoHub.

</p>







<a

href="<?= BASE_URL; ?>order_details.php?id=<?= $orderID; ?>"

class="btn-primary"

>


View Order


</a>







</div>






</section>








<?php include "includes/footer.php"; ?>