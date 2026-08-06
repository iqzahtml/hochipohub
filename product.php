<?php

session_start();

require_once "config/db.php";



/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

$category_id = isset($_GET['category']) 
? $_GET['category'] 
: '';



$search = isset($_GET['search'])
? $_GET['search']
: '';




$query = "

SELECT

products.*,

vendors.business_name,

categories.category_name


FROM products


JOIN vendors

ON products.vendor_id = vendors.vendor_id


JOIN categories

ON products.category_id = categories.category_id


WHERE products.status='Available'


";




$params=[];

$types="";



if(!empty($category_id)){


$query .= " AND products.category_id=? ";


$params[]=$category_id;

$types.="i";


}




if(!empty($search)){


$query .= " AND products.product_name LIKE ? ";


$params[]="%".$search."%";

$types.="s";


}



$query.=" ORDER BY products.created_at DESC";





$stmt=$conn->prepare($query);



if(!empty($params)){


$stmt->bind_param(
    $types,
    ...$params
);


}



$stmt->execute();


$result=$stmt->get_result();



?>


<!DOCTYPE html>

<html>


<head>

<title>Products | HochipoHub</title>


<link rel="stylesheet" href="assets/css/style.css">

<link rel="stylesheet" href="assets/css/product.css">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>


<body>



<?php include "includes/navbar.php"; ?>




<section class="product-page">


<div class="product-container">



<div class="product-page-header">


<div class="product-page-heading">


<span class="product-page-eyebrow">

PRODUCTS

</span>



<h1>

Find Your Favourite Products

</h1>



<p>

Shop from different trusted HochipoHub vendors.

</p>


</div>


</div>





<div class="product-filter-bar">


<form method="GET"
class="product-filter-left">


<input

type="text"

name="search"

placeholder="Search product..."

value="<?php echo htmlspecialchars($search); ?>"


class="admin-input"



>


<button class="product-action-btn primary">


<i class="fa-solid fa-search"></i>

Search


</button>


</form>



</div>







<div class="product-grid">



<?php if($result->num_rows>0){ ?>



<?php while($row=$result->fetch_assoc()){ ?>



<div class="product-card">


<div class="product-card-image">


<?php if($row['image']){ ?>

<img src="assets/uploads/products/<?php echo $row['image']; ?>">


<?php }else{ ?>


<div class="product-image-placeholder">

<i class="fa-solid fa-image"></i>

</div>


<?php } ?>



</div>






<div class="product-card-content">


<span class="product-card-category">

<?php echo $row['category_name']; ?>

</span>




<a href="product_details.php?id=<?php echo $row['product_id']; ?>"
class="product-card-title">


<?php echo htmlspecialchars($row['product_name']); ?>


</a>




<div class="product-card-vendor">


<i class="fa-solid fa-store"></i>


<?php echo $row['business_name']; ?>


</div>






<div class="product-card-footer">


<div class="product-price">


<span class="product-price-current">

RM <?php echo number_format($row['price'],2); ?>


</span>


</div>



<a href="product_details.php?id=<?php echo $row['product_id']; ?>"
class="add-cart-btn">


<i class="fa-solid fa-eye"></i>


</a>



</div>



</div>



</div>



<?php } ?>


<?php }else{ ?>


<div class="product-empty">


<h3>

No Product Found

</h3>


<p>

Try another keyword.

</p>


</div>


<?php } ?>



</div>



</div>


</section>




<?php include "includes/footer.php"; ?>



</body>


</html>