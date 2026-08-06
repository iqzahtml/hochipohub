<?php

require_once "../database/db.php";


$keyword="%".$_POST['keyword']."%";



$stmt=$conn->prepare("


SELECT *

FROM products

WHERE product_name LIKE ?

AND status='Available'


");


$stmt->bind_param(

"s",

$keyword

);



$stmt->execute();



$result=$stmt->get_result();



while($row=$result->fetch_assoc()){


?>


<div class="search-item">


<a href="../hochipohub/product_details.php?id=<?= $row['product_id']; ?>">


<?= htmlspecialchars($row['product_name']); ?>


</a>


</div>



<?php

}

?>