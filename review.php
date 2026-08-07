<?php

require_once "config.php";
require_once "database/db.php";
require_once "includes/functions.php";
require_once "includes/session.php";


$pageTitle = "Reviews";


requireLogin();


$userID = currentUserID();





/*
|--------------------------------------------------------------------------
| Submit Review
|--------------------------------------------------------------------------
*/


if($_SERVER['REQUEST_METHOD']=="POST"){



$productID = mysqli_real_escape_string(
    $conn,
    $_POST['product_id']
);


$rating = mysqli_real_escape_string(
    $conn,
    $_POST['rating']
);


$review = mysqli_real_escape_string(
    $conn,
    $_POST['review']
);




$sql = "

INSERT INTO reviews

(

customer_id,

product_id,

rating,

review,

status,

review_date

)


VALUES

(

'$userID',

'$productID',

'$rating',

'$review',

'Visible',

NOW()

)

";



$conn->query($sql);



}





/*
|--------------------------------------------------------------------------
| Customer Purchased Products
|--------------------------------------------------------------------------
*/


$query = "

SELECT DISTINCT


products.product_id,

products.product_name



FROM order_details



INNER JOIN orders

ON order_details.order_id = orders.order_id




INNER JOIN products

ON order_details.product_id = products.product_id





WHERE orders.user_id='$userID'



";



$products=$conn->query($query);



?>



<?php include "includes/header.php"; ?>


<section class="review-page">



<div class="page-title">

<h1>
Write Review
</h1>

</div>






<div class="review-box">


<form method="POST">


<div class="form-group">


<label>
Product
</label>


<select name="product_id">


<?php while($product=$products->fetch_assoc()){ ?>


<option value="<?= $product['product_id']; ?>">

<?= htmlspecialchars($product['product_name']); ?>

</option>


<?php } ?>


</select>



</div>





<div class="form-group">


<label>
Rating
</label>


<select name="rating">


<option value="5">
★★★★★
</option>


<option value="4">
★★★★
</option>


<option value="3">
★★★
</option>


<option value="2">
★★
</option>


<option value="1">
★
</option>


</select>


</div>






<div class="form-group">


<label>
Review
</label>


<textarea

name="review"

rows="5"

required

></textarea>


</div>





<button class="btn-primary">

Submit Review

</button>


</form>


</div>


</section>



<?php include "includes/footer.php"; ?>