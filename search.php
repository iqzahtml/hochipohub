<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Search Page
|--------------------------------------------------------------------------
|
| Search:
| - Products
| - Vendors
| - Categories
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Search";





/*
|--------------------------------------------------------------------------
| Search Keyword
|--------------------------------------------------------------------------
*/


$keyword = "";



if(isset($_GET['q'])){


    $keyword = mysqli_real_escape_string(

        $conn,

        $_GET['q']

    );


}








/*
|--------------------------------------------------------------------------
| Search Query
|--------------------------------------------------------------------------
*/


$results = null;



if(!empty($keyword)){



$query = "

SELECT



products.*,


vendors.business_name,


categories.category_name





FROM products





INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id






INNER JOIN categories

ON products.category_id = categories.category_id





WHERE products.status='Available'




AND

(


products.product_name LIKE '%$keyword%'




OR vendors.business_name LIKE '%$keyword%'




OR categories.category_name LIKE '%$keyword%'



)





ORDER BY products.created_at DESC



";





$results = $conn->query($query);



}






?>



<?php include "includes/header.php"; ?>








<section class="search-page">







<div class="page-title">



<h1>

Search Result

</h1>





<?php if(!empty($keyword)){ ?>


<p>

Showing results for:

<strong>

<?= htmlspecialchars($keyword); ?>

</strong>


</p>



<?php } ?>



</div>








<div class="search-box">



<form method="GET">



<input

type="text"

name="q"

placeholder="Search products..."

value="<?= htmlspecialchars($keyword); ?>"

>




<button type="submit">


<i class="fa-solid fa-search"></i>


Search


</button>



</form>



</div>









<div class="product-grid">






<?php if($results && $results->num_rows > 0){ ?>






<?php while($product = $results->fetch_assoc()){ ?>







<div class="product-card">







<div class="product-image">


<img

src="<?= productImage($product['image']); ?>"

alt="<?= htmlspecialchars($product['product_name']); ?>"

>


</div>








<div class="product-info">







<span class="product-category">


<?= htmlspecialchars($product['category_name']); ?>


</span>







<h3>


<?= htmlspecialchars($product['product_name']); ?>


</h3>








<p class="vendor-name">


<i class="fa-solid fa-store"></i>


<?= htmlspecialchars($product['business_name']); ?>


</p>








<p class="product-price">


<?= price($product['price']); ?>


</p>







<a

href="<?= BASE_URL; ?>product_details.php?id=<?= $product['product_id']; ?>"

class="view-product-btn"

>


View Details


</a>





</div>







</div>






<?php } ?>







<?php }elseif(!empty($keyword)){ ?>





<div class="empty-product">


<h3>

No Result Found

</h3>



<p>

Try another keyword.

</p>



</div>







<?php }else{ ?>




<div class="empty-product">


<h3>

Search Product

</h3>



<p>

Enter keyword to find products.

</p>


</div>






<?php } ?>







</div>








</section>








<?php include "includes/footer.php"; ?>