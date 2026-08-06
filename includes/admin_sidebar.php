<?php

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}


if($_SESSION['role'] !== "admin"){

    exit("Access Denied");

}

?>


<aside class="dashboard-sidebar admin-sidebar">


<div class="sidebar-header">

<h2>
Admin Panel
</h2>

<p>
HochipoHub
</p>

</div>




<nav class="sidebar-menu">


<a href="../admin/dashboard.php">

<i class="fa-solid fa-chart-line"></i>

Dashboard

</a>




<a href="../admin/users.php">

<i class="fa-solid fa-users"></i>

Users

</a>





<a href="../admin/vendors.php">

<i class="fa-solid fa-store"></i>

Vendors

</a>





<a href="../admin/products.php">

<i class="fa-solid fa-box"></i>

Products

</a>





<a href="../admin/orders.php">

<i class="fa-solid fa-cart-shopping"></i>

Orders

</a>





<a href="../admin/inventory.php">

<i class="fa-solid fa-warehouse"></i>

Inventory

</a>





<a href="../admin/payment.php">

<i class="fa-solid fa-credit-card"></i>

Payment

</a>





<a href="../admin/commission.php">

<i class="fa-solid fa-money-bill"></i>

Commission

</a>





<a href="../admin/reviews.php">

<i class="fa-solid fa-star"></i>

Reviews

</a>





<a href="../admin/settings.php">

<i class="fa-solid fa-gear"></i>

Settings

</a>





<a href="../auth/logout.php"
class="logout-link">


<i class="fa-solid fa-right-from-bracket"></i>

Logout


</a>




</nav>


</aside>