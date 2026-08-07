<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Catalog Page
|--------------------------------------------------------------------------
|
| Database:
| - products
| - vendors
| - categories
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Catalog";




/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/


$search = "";

$categoryID = "";



if(isset($_GET['search'])){

    $search = mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );

}



if(isset($_GET['category'])){

    $categoryID = mysqli_real_escape_string(
        $conn,
        $_GET['category']
    );

}






/*
|--------------------------------------------------------------------------
| Product Query
|--------------------------------------------------------------------------
*/


$sql = "

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

";





if(!empty($search)){


$sql .= "

AND (

products.product_name LIKE '%$search%'

OR

vendors.business_name LIKE '%$search%'

OR

categories.category_name LIKE '%$search%'

)

";


}




if(!empty($categoryID)){


$sql .= "

AND products.category_id='$categoryID'

";


}





$sql .= "

ORDER BY products.created_at DESC

";




$result = $conn->query($sql);





/*
|--------------------------------------------------------------------------
| Category List
|--------------------------------------------------------------------------
*/


$categoryQuery = "

SELECT *

FROM categories

ORDER BY category_name ASC

";


$categoryResult = $conn->query($categoryQuery);





?>



<?php include "includes/header.php"; ?>





<section class="catalog-page">



<div class="catalog-header">



<h1>

Explore Products

</h1>



<p>

Find unique products from local vendors.

</p>



</div>







<!-- FILTER -->


<div class="catalog-filter">



<form method="GET">



<input

type="text"

name="search"

placeholder="Search products..."

value="<?= htmlspecialchars($search); ?>"

>



<select name="category">



<option value="">

All Categories

</option>



<?php while($cat = $categoryResult->fetch_assoc()){ ?>



<option

value="<?= $cat['category_id']; ?>"

<?=

($categoryID == $cat['category_id'])

?

'selected'

:

''

?>

>


<?= htmlspecialchars($cat['category_name']); ?>


</option>



<?php } ?>



</select>




<button type="submit">


Search


</button>



</form>



</div>









<!-- PRODUCT GRID -->


<div class="product-grid">






<?php if($result && $result->num_rows > 0){ ?>





<?php while($product = $result->fetch_assoc()){ ?>





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






<?php }else{ ?>




<div class="empty-product">


<h3>

No Product Found

</h3>



<p>

Try another search or category.

</p>



</div>




<?php } ?>






</div>






</section>







<?php include "includes/footer.php"; ?>