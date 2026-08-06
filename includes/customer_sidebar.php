<?php

if(!isset($_SESSION['user_id'])){

header("Location: ../login.php");

exit();

}



?>


<aside class="dashboard-sidebar customer-sidebar">



<div class="sidebar-header">


<h2>

Customer

</h2>


<p>

HochipoHub

</p>


</div>





<nav class="sidebar-menu">



<a href="../dashboard.php">

<i class="fa-solid fa-house"></i>

Dashboard

</a>





<a href="../product.php">

<i class="fa-solid fa-store"></i>

Shop

</a>





<a href="../wishlist.php">

<i class="fa-solid fa-heart"></i>

Wishlist

</a>





<a href="../cart.php">

<i class="fa-solid fa-cart-shopping"></i>

Cart

</a>





<a href="../order.php">

<i class="fa-solid fa-box"></i>

My Orders

</a>





<a href="../profile.php">

<i class="fa-solid fa-user"></i>

Profile

</a>





<a href="../review.php">

<i class="fa-solid fa-star"></i>

Reviews

</a>





<a href="../contact.php">

<i class="fa-solid fa-message"></i>

Contact

</a>





<a href="../auth/logout.php"

class="logout-link">


<i class="fa-solid fa-right-from-bracket"></i>

Logout


</a>




</nav>


</aside>