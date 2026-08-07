<?php

require_once "config.php";
require_once "database/db.php";
require_once "includes/functions.php";
require_once "includes/session.php";

$pageTitle = "Dashboard";

requireLogin();

$userID = currentUserID();



/*
|--------------------------------------------------------------------------
| Count Orders
|--------------------------------------------------------------------------
*/

$orderQuery = "

SELECT COUNT(*) AS total

FROM orders

WHERE user_id='$userID'

";


$orderResult = $conn->query($orderQuery);

$orderCount = $orderResult->fetch_assoc()['total'];





/*
|--------------------------------------------------------------------------
| Count Wishlist
|--------------------------------------------------------------------------
*/

$wishQuery = "

SELECT COUNT(*) AS total

FROM wishlist

WHERE user_id='$userID'

";


$wishResult = $conn->query($wishQuery);

$wishCount = $wishResult->fetch_assoc()['total'];





/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

$recentQuery = "

SELECT *

FROM orders

WHERE user_id='$userID'

ORDER BY created_at DESC

LIMIT 5

";


$recentOrders = $conn->query($recentQuery);



?>

<?php include "includes/header.php"; ?>

<section class="dashboard-page">


<div class="page-title">

<h1>
My Dashboard
</h1>

<p>
Welcome back to HochipoHub.
</p>

</div>




<div class="dashboard-card-grid">


<div class="dashboard-card">

<h3>
Orders
</h3>

<strong>
<?= $orderCount; ?>
</strong>

</div>




<div class="dashboard-card">

<h3>
Wishlist
</h3>

<strong>
<?= $wishCount; ?>
</strong>

</div>




<div class="dashboard-card">

<h3>
Account
</h3>

<a href="profile.php">
Manage Profile
</a>

</div>


</div>





<div class="dashboard-section">

<h2>
Recent Orders
</h2>



<?php if($recentOrders && $recentOrders->num_rows > 0){ ?>


<?php while($order=$recentOrders->fetch_assoc()){ ?>


<div class="order-card">


<h3>
Order #<?= $order['order_id']; ?>
</h3>


<p>
Status:
<?= htmlspecialchars($order['order_status']); ?>
</p>


<p>
RM <?= number_format($order['total_amount'],2); ?>
</p>


<a href="order_details.php?id=<?= $order['order_id']; ?>">
View
</a>


</div>


<?php } ?>


<?php }else{ ?>


<p>
No order yet.
</p>


<?php } ?>


</div>



</section>


<?php include "includes/footer.php"; ?>