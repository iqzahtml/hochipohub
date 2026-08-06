<?php

if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}




if($_SESSION['role'] !== "vendor"){


exit("Access Denied");


}


?>


<aside class="dashboard-sidebar vendor-sidebar">


<div class="sidebar-header">


<h2>

Vendor Panel

</h2>


<p>

HochipoHub

</p>


</div>




<nav class="sidebar-menu">



<a href="../vendor/dashboard.php">


<i class="fa-solid fa-chart-line"></i>


Dashboard


</a>





<a href="../vendor/products.php">


<i class="fa-solid fa-box"></i>


My Products


</a>





<a href="../vendor/add_product.php">


<i class="fa-solid fa-plus"></i>


Add Product


</a>





<a href="../vendor/orders.php">


<i class="fa-solid fa-cart-shopping"></i>


Orders


</a>





<a href="../vendor/inventory.php">


<i class="fa-solid fa-warehouse"></i>


Inventory


</a>





<a href="../vendor/commission.php">


<i class="fa-solid fa-money-bill-transfer"></i>


Commission


</a>





<a href="../vendor/payment.php">


<i class="fa-solid fa-wallet"></i>


Payment


</a>





<a href="../vendor/profile.php">


<i class="fa-solid fa-user"></i>


Profile


</a>





<a href="../auth/logout.php"

class="logout-link">



<i class="fa-solid fa-right-from-bracket"></i>


Logout



</a>




</nav>


</aside>