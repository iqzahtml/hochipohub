<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Vendor Listing
|--------------------------------------------------------------------------
|
| Database:
| - vendors
|
|--------------------------------------------------------------------------
*/


require_once "config.php";

require_once "database/db.php";

require_once "includes/functions.php";

require_once "includes/session.php";



$pageTitle = "Vendors";






/*
|--------------------------------------------------------------------------
| Get Vendors
|--------------------------------------------------------------------------
*/


$query = "

SELECT *

FROM vendors

WHERE status='Approved'

ORDER BY created_at DESC

";



$vendors = $conn->query($query);



?>



<?php include "includes/header.php"; ?>







<section class="vendor-page">







<div class="page-title">


<h1>

Local Vendors

</h1>



<p>

Support local businesses through HochipoHub.

</p>


</div>









<div class="vendor-grid">






<?php if($vendors && $vendors->num_rows > 0){ ?>







<?php while($vendor = $vendors->fetch_assoc()){ ?>






<div class="vendor-card">








<div class="vendor-image">





<?php if(!empty($vendor['business_logo'])){ ?>



<img

src="<?= BASE_URL; ?>uploads/vendors/<?= htmlspecialchars($vendor['business_logo']); ?>"

alt="<?= htmlspecialchars($vendor['business_name']); ?>"

>



<?php }else{ ?>



<img

src="<?= IMAGE_URL; ?>logo.jpg"

alt="Vendor Logo"

>



<?php } ?>





</div>









<div class="vendor-info">







<h2>


<?= htmlspecialchars($vendor['business_name']); ?>


</h2>






<p>


<?= htmlspecialchars(

substr(

$vendor['business_description'],

0,

120

)

); ?>


...


</p>








<a

href="<?= BASE_URL; ?>product.php?vendor=<?= $vendor['vendor_id']; ?>"

class="view-product-btn"

>


View Products


</a>







</div>







</div>






<?php } ?>








<?php }else{ ?>





<div class="empty-product">


<h3>

No Vendors Available

</h3>



<p>

No approved vendors yet.

</p>


</div>





<?php } ?>







</div>









</section>







<?php include "includes/footer.php"; ?>