<?php

require_once "config.php";
require_once "database/db.php";
require_once "includes/functions.php";
require_once "includes/session.php";


$pageTitle="Commission";


requireLogin();


if(currentUserRole()!="vendor"){


header(
"Location: dashboard.php"
);


exit();


}



$userID=currentUserID();





$query="

SELECT


orders.order_id,


orders.total_amount,


orders.created_at




FROM orders





INNER JOIN order_details

ON orders.order_id = order_details.order_id





INNER JOIN products

ON order_details.product_id = products.product_id





INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id





WHERE vendors.user_id='$userID'




ORDER BY orders.created_at DESC



";



$result=$conn->query($query);




?>



<?php include "includes/header.php"; ?>


<section class="commission-page">


<div class="page-title">

<h1>
Vendor Commission
</h1>

</div>





<table class="commission-table">


<tr>

<th>
Order ID
</th>


<th>
Sales
</th>


<th>
Commission (5%)
</th>


</tr>




<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>

#<?= $row['order_id']; ?>

</td>


<td>

RM <?= number_format($row['total_amount'],2); ?>

</td>


<td>

RM <?= number_format($row['total_amount']*0.05,2); ?>

</td>


</tr>


<?php } ?>



</table>



</section>



<?php include "includes/footer.php"; ?>