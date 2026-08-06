<?php

require_once __DIR__.'/session.php';

?>


<nav class="navbar">


<div class="logo">

<a href="<?= BASE_URL ?>index.php">

HochipoHub

</a>

</div>



<ul>


<li>

<a href="<?= BASE_URL ?>catalog.php">

Catalog

</a>

</li>



<li>

<a href="<?= BASE_URL ?>vendor.php">

Vendor

</a>

</li>




<?php if(isLoggedIn()): ?>


<li>

<a href="<?= BASE_URL ?>cart.php">

Cart

</a>

</li>



<li>

<a href="<?= BASE_URL ?>wishlist.php">

Wishlist

</a>

</li>



<li>

<a href="<?= BASE_URL ?>dashboard.php">

Dashboard

</a>

</li>



<li>

<a href="<?= BASE_URL ?>auth/logout.php">

Logout

</a>

</li>



<?php else: ?>


<li>

<button onclick="openLoginModal()">

Login

</button>

</li>


<li>

<button onclick="openRegisterModal()">

Register

</button>

</li>


<?php endif; ?>



</ul>



</nav>



<?php

if(!isLoggedIn()){

    include __DIR__.'/login_modal.php';

    include __DIR__.'/register_modal.php';

}

?>