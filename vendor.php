<?php

require_once "database/db.php";


$vendors=$conn->query("


SELECT


vendors.*,


users.name,


users.email



FROM vendors



JOIN users


ON vendors.user_id=users.user_id



WHERE vendors.approval_status='Approved'



ORDER BY vendors.vendor_id DESC



");



?>


<!DOCTYPE html>

<html>


<head>

<title>
Vendors
</title>


<link rel="stylesheet" href="css/vendor.css">


</head>



<body>



<?php include "../includes/navbar.php"; ?>



<h1>
Local Vendors
</h1>



<div class="vendor-grid">


<?php while($row=$vendors->fetch_assoc()){ ?>



<div class="vendor-card">



<?php if($row['business_logo']){ ?>


<img src="uploads/vendors/<?= $row['business_logo']; ?>">


<?php } ?>



<h2>

<?= htmlspecialchars($row['business_name']); ?>

</h2>




<p>

Owner:

<?= htmlspecialchars($row['name']); ?>

</p>



<p>

Category:

<?= htmlspecialchars($row['category']); ?>

</p>



<p>

<?= htmlspecialchars($row['business_description']); ?>

</p>



<a href="catalog.php?vendor=<?= $row['vendor_id']; ?>">

View Products

</a>



</div>



<?php } ?>



</div>



</body>


</html>