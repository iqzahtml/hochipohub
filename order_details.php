<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Order Details
|--------------------------------------------------------------------------
|
| Customer order information
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Order Details";



requireLogin();



$userID = currentUserID();






if(!isset($_GET['id'])){


    header(

        "Location: order.php"

    );


    exit();


}





$orderID = mysqli_real_escape_string(

    $conn,

    $_GET['id']

);









/*
|--------------------------------------------------------------------------
| Get Order Information
|--------------------------------------------------------------------------
*/


$orderQuery = "

SELECT *

FROM orders

WHERE order_id='$orderID'

AND user_id='$userID'

LIMIT 1

";



$orderResult = $conn->query($orderQuery);




if(!$orderResult || $orderResult->num_rows == 0){


    header(

        "Location: order.php"

    );


    exit();


}




$order = $orderResult->fetch_assoc();









/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/


$itemQuery = "

SELECT



order_details.*,



products.product_name,

products.image,



vendors.business_name




FROM order_details





INNER JOIN products

ON order_details.product_id = products.product_id






INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id





WHERE order_details.order_id='$orderID'



";





$items = $conn->query($itemQuery);





?>



<?php include "includes/header.php"; ?>







<section class="order-detail-page">







<div class="page-title">


<h1>

Order Details

</h1>



<p>

Order #<?= $order['order_id']; ?>

</p>


</div>








<div class="order-summary-card">





<h2>

Order Information

</h2>






<p>

Status:

<span class="status">

<?= htmlspecialchars($order['order_status']); ?>

</span>


</p>






<p>

Payment:

<?= htmlspecialchars($order['payment_method']); ?>


</p>






<p>

Payment Status:

<?= htmlspecialchars($order['payment_status']); ?>


</p>






<p>

Shipping Address:

<br>

<?= nl2br(htmlspecialchars($order['shipping_address'])); ?>


</p>







<h3>

Total:

RM <?= number_format($order['total_amount'],2); ?>


</h3>






</div>









<h2 class="section-title">

Products

</h2>









<div class="order-products">







<?php while($item = $items->fetch_assoc()){ ?>







<div class="order-product-card">





<div class="order-product-image">



<img

src="<?= productImage($item['image']); ?>"

alt="<?= htmlspecialchars($item['product_name']); ?>"

>


</div>







<div class="order-product-info">





<h3>


<?= htmlspecialchars($item['product_name']); ?>


</h3>






<p>


Seller:


<?= htmlspecialchars($item['business_name']); ?>


</p>






<p>


Quantity:

<?= $item['quantity']; ?>


</p>






<p>


Price:

RM <?= number_format($item['price'],2); ?>


</p>







</div>






</div>







<?php } ?>







</div>








<a

href="<?= BASE_URL; ?>order.php"

class="btn-primary"

>


Back To Orders


</a>







</section>








<?php include "includes/footer.php"; ?>