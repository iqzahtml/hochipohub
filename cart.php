<?php

session_start();

require_once "config/db.php";


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/


if(!isset($_SESSION['user_id'])){

    header("Location: login.php");
    exit();

}



$user_id = $_SESSION['user_id'];



/*
|--------------------------------------------------------------------------
| GET CART DATA
|--------------------------------------------------------------------------
*/


$query = "

SELECT

cart.cart_id,
cart.quantity,

products.product_id,
products.product_name,
products.price,
products.image,
products.stock_quantity,

vendors.vendor_id,
vendors.business_name,
vendors.business_logo,

categories.category_name


FROM cart


INNER JOIN products

ON cart.product_id = products.product_id


INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id


INNER JOIN categories

ON products.category_id = categories.category_id


WHERE cart.customer_id = ?


ORDER BY vendors.business_name ASC



";



$stmt = $conn->prepare($query);

$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();



$total = 0;


?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Your Cart | HochipoHub</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/cart.css">


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">


</head>



<body>



<?php include "includes/navbar.php"; ?>



<section class="cart-page">


<div class="cart-container">



<div class="cart-header">


<div>

<h1>
<i class="fa-solid fa-cart-shopping"></i>
 Shopping Cart
</h1>


<p>
Review your items before checkout
</p>


</div>



<span class="cart-count-label">

<?php echo $result->num_rows; ?> Items

</span>


</div>





<div class="cart-layout">



<div class="cart-items">



<?php


if($result->num_rows > 0){



$current_vendor = "";



while($row = $result->fetch_assoc()){



$item_total = 
$row['price'] * $row['quantity'];


$total += $item_total;




/*
|--------------------------------------------------------------------------
| Vendor Group
|--------------------------------------------------------------------------
*/


if($current_vendor != $row['vendor_id']){


if($current_vendor != ""){

echo "</div>";

}


$current_vendor = $row['vendor_id'];

?>


<div class="cart-vendor-group">


<div class="cart-vendor-header">


<div class="cart-vendor-logo">


<?php if(!empty($row['business_logo'])){ ?>


<img src="assets/uploads/vendors/<?php echo $row['business_logo']; ?>">


<?php }else{ ?>


<i class="fa-solid fa-store"></i>


<?php } ?>


</div>



<div class="cart-vendor-info">


<h3>

<?php echo htmlspecialchars($row['business_name']); ?>

</h3>


<span>
Vendor
</span>


</div>



</div>



<?php

}



?>



<div class="cart-item">



<div class="cart-item-image">


<?php if(!empty($row['image'])){ ?>


<img src="assets/uploads/products/<?php echo $row['image']; ?>">


<?php }else{ ?>


<i class="fa-solid fa-image"></i>


<?php } ?>


</div>





<div class="cart-item-info">


<span class="cart-item-category">

<?php echo $row['category_name']; ?>

</span>



<h4>


<a href="product_details.php?id=<?php echo $row['product_id']; ?>">

<?php echo htmlspecialchars($row['product_name']); ?>


</a>


</h4>




<div class="cart-item-price">

RM <?php echo number_format($row['price'],2); ?>

</div>




<div class="cart-quantity">


<button onclick="updateCart(
<?php echo $row['cart_id']; ?>,
'minus'
)">
-
</button>



<input 
type="text"
value="<?php echo $row['quantity']; ?>"
readonly
>




<button onclick="updateCart(
<?php echo $row['cart_id']; ?>,
'plus'
)">
+
</button>


</div>




</div>




<div>


<button 
class="cart-remove"
onclick="removeCart(
<?php echo $row['cart_id']; ?>
)">

<i class="fa-solid fa-trash"></i>

</button>


</div>



</div>



<?php


}



echo "</div>";



}else{


?>


<div class="empty-cart">


<div>


<div class="empty-cart-icon">

<i class="fa-solid fa-cart-shopping"></i>

</div>



<h2>
Your cart is empty
</h2>



<p>
Start shopping and add your favourite products.
</p>



<a href="product.php" class="continue-shopping">

Browse Products

</a>


</div>


</div>



<?php

}


?>



</div>





<!-- SUMMARY -->


<div class="cart-summary">


<h2>
Order Summary
</h2>



<div class="summary-row">


<span>
Subtotal
</span>


<strong>

RM <?php echo number_format($total,2); ?>

</strong>


</div>



<div class="summary-row">


<span>
Delivery
</span>


<strong>
Calculated later
</strong>


</div>



<div class="summary-total">


<span>
Total
</span>


<strong>

RM <?php echo number_format($total,2); ?>

</strong>


</div>




<a href="checkout.php" 
class="cart-checkout-btn">


Proceed Checkout


</a>



<div class="cart-payment-info">


<p>

<i class="fa-solid fa-shield-halved"></i>

 Secure payment via FPX / Card

</p>


</div>



</div>



</div>



</div>


</section>





<?php include "includes/footer.php"; ?>




<script src="assets/js/cart.js"></script>


</body>

</html>