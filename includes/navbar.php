<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Navbar
|--------------------------------------------------------------------------
|
| Used by:
| - index.php
| - catalog.php
| - product.php
| - dashboard.php
| - seller/*
| - admin/*
|
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/includes/session.php';


?>


<nav class="navbar">


<div class="navbar-container">



<!-- LOGO -->

<div class="logo">


<a href="<?= BASE_URL; ?>index.php">


<img 
src="<?= IMAGE_URL; ?>logo.jpg"
alt="HochipoHub Logo"
>


<span>
HochipoHub
</span>


</a>


</div>





<!-- NAVIGATION -->


<ul class="nav-links">



<li>

<a href="<?= BASE_URL; ?>index.php">

Home

</a>

</li>




<li>

<a href="<?= BASE_URL; ?>catalog.php">

Catalog

</a>

</li>




<li>

<a href="<?= BASE_URL; ?>vendor.php">

Vendors

</a>

</li>





<?php if(isLogin() && isCustomer()){ ?>


<li>

<a href="<?= BASE_URL; ?>cart.php">


<i class="fa-solid fa-cart-shopping"></i>

Cart


<span class="nav-count">

<?= $cartCount ?? 0; ?>

</span>


</a>

</li>





<li>

<a href="<?= BASE_URL; ?>wishlist.php">


<i class="fa-solid fa-heart"></i>

Wishlist


<span class="nav-count">

<?= $wishlistCount ?? 0; ?>

</span>


</a>

</li>



<?php } ?>





<?php if(isLogin() && isSeller()){ ?>


<li>

<a href="<?= BASE_URL; ?>seller/dashboard.php">


Seller Dashboard


</a>

</li>


<?php } ?>





<?php if(isLogin() && isAdmin()){ ?>


<li>

<a href="<?= BASE_URL; ?>admin/dashboard.php">


Admin Panel


</a>

</li>


<?php } ?>




</ul>







<!-- USER MENU -->


<div class="nav-user">



<?php if(!isLogin()){ ?>



<button 
class="btn-login"
onclick="openLoginModal()"
>


Login


</button>




<button 
class="btn-register"
onclick="openRegisterModal()"
>


Register


</button>



<?php } else { ?>




<div class="user-dropdown">


<button class="user-btn">


<i class="fa-solid fa-user"></i>


<?= htmlspecialchars(currentUserName()); ?>


<i class="fa-solid fa-chevron-down"></i>


</button>




<div class="dropdown-menu">



<a href="<?= BASE_URL; ?>profile.php">

Profile

</a>



<?php if(isCustomer()){ ?>


<a href="<?= BASE_URL; ?>order.php">

My Orders

</a>


<?php } ?>




<?php if(isSeller()){ ?>


<a href="<?= BASE_URL; ?>seller/products.php">

My Products

</a>


<?php } ?>





<a href="<?= BASE_URL; ?>auth/logout.php">


Logout


</a>



</div>


</div>




<?php } ?>



</div>





<!-- MOBILE MENU -->


<div class="mobile-menu-btn">


<i class="fa-solid fa-bars"></i>


</div>



</div>


</nav>