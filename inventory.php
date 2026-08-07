<?php

require_once "config.php";
require_once "database/db.php";
require_once "includes/functions.php";
require_once "includes/session.php";


$pageTitle="Inventory";


requireLogin();


if(currentUserRole() != "vendor"){


    header(
        "Location: dashboard.php"
    );

    exit();

}



$userID=currentUserID();





/*
|--------------------------------------------------------------------------
| Get Vendor ID
|--------------------------------------------------------------------------
*/


$vendorQuery="

SELECT vendor_id

FROM vendors

WHERE user_id='$userID'

LIMIT 1

";


$vendorResult=$conn->query($vendorQuery);

$vendor=$vendorResult->fetch_assoc();


$vendorID=$vendor['vendor_id'];







/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
*/


$query="

SELECT *

FROM products

WHERE vendor_id='$vendorID'

ORDER BY created_at DESC

";



$products=$conn->query($query);



?>



<?php include "includes/header.php"; ?>


<section class="inventory-page">


<div class="page-title">

<h1>
Inventory
</h1>


</div>






<div class="product-grid">



<?php while($product=$products->fetch_assoc()){ ?>



<div class="product-card">


<img

src="<?= productImage($product['image']); ?>"

>


<h3>

<?= htmlspecialchars($product['product_name']); ?>

</h3>



<p>

Stock:

<?= $product['stock_quantity']; ?>

</p>



<a href="seller/edit_product.php?id=<?= $product['product_id']; ?>">

Edit

</a>



</div>



<?php } ?>



</div>



</section>



<?php include "includes/footer.php"; ?>