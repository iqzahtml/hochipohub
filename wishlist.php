<?php

require_once "config.php";
require_once "database/db.php";
require_once "includes/functions.php";
require_once "includes/session.php";


$pageTitle="Wishlist";


requireLogin();


$userID=currentUserID();





$query="

SELECT


wishlist.wishlist_id,


products.*,

vendors.business_name



FROM wishlist





INNER JOIN products

ON wishlist.product_id = products.product_id





INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id





WHERE wishlist.user_id='$userID'



ORDER BY wishlist.created_at DESC



";



$wishlist=$conn->query($query);



?>


<?php include "includes/header.php"; ?>


<section class="wishlist-page">



<div class="page-title">

<h1>
My Wishlist
</h1>

</div>





<div class="product-grid">


<?php if($wishlist && $wishlist->num_rows>0){ ?>


<?php while($item=$wishlist->fetch_assoc()){ ?>



<div class="product-card">


<img

src="<?= productImage($item['image']); ?>"

>


<h3>

<?= htmlspecialchars($item['product_name']); ?>

</h3>



<p>

<?= htmlspecialchars($item['business_name']); ?>

</p>



<p>

<?= price($item['price']); ?>

</p>




<a href="product_details.php?id=<?= $item['product_id']; ?>">

View Product

</a>




<a href="ajax/remove_wishlist.php?id=<?= $item['wishlist_id']; ?>">

Remove

</a>



</div>



<?php } ?>



<?php }else{ ?>


<div class="empty-product">

<h3>
Wishlist Empty
</h3>


<p>
You haven't saved any product yet.
</p>


</div>


<?php } ?>



</div>



</section>


<?php include "includes/footer.php"; ?>