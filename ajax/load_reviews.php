<?php

require_once "../config/db.php";


$product_id=$_POST['product_id'];



$stmt=$conn->prepare("

SELECT

reviews.*,

users.name


FROM reviews


JOIN users

ON reviews.customer_id=users.user_id


WHERE product_id=?

AND reviews.status='Visible'


ORDER BY review_id DESC


");


$stmt->bind_param(

"i",

$product_id

);



$stmt->execute();



$result=$stmt->get_result();



while($row=$result->fetch_assoc()){


?>


<div class="review-box">


<h4>

<?= htmlspecialchars($row['name']); ?>

</h4>


<p>

Rating:
<?= $row['rating']; ?>/5

</p>


<p>

<?= htmlspecialchars($row['review']); ?>

</p>


</div>



<?php

}

?>