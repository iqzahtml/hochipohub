<?php

require_once "database/db.php";


header("Content-Type: text/html");



$category=$_POST['category'] ?? '';



if($category!=""){


$stmt=$conn->prepare("

SELECT *

FROM products

WHERE category_id=?

AND status='Available'

ORDER BY product_id DESC

");


$stmt->bind_param(

"i",

$category

);



$stmt->execute();



$result=$stmt->get_result();



}

else{


$result=$conn->query("

SELECT *

FROM products

WHERE status='Available'

ORDER BY product_id DESC

");


}



while($row=$result->fetch_assoc()){


?>

<div class="product-card">


<img src="../assets/uploads/products/<?= $row['image']; ?>">


<h3>

<?= htmlspecialchars($row['product_name']); ?>

</h3>


<p>

RM <?= number_format($row['price'],2); ?>

</p>


<button class="add-cart"

data-id="<?= $row['product_id']; ?>">

Add Cart

</button>



</div>


<?php

}

?>