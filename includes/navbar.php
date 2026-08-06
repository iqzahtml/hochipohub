<?php

require_once "session.php";


?>


<nav class="navbar">


<div class="logo">

<a href="../hochipohub/index.php">

HochipoHub

</a>

</div>



<ul>


<li>

<a href="../hochipohub/catalog.php">

Catalog

</a>

</li>


<li>

<a href="../hochipohub/vendor.php">

Vendor

</a>

</li>



<?php if(isLogin()){ ?>



<li>

<a href="../hochipohub/cart.php">

Cart

</a>

</li>



<li>

<a href="../hochipohub/wishlist.php">

Wishlist

</a>

</li>




<li>

<a href="../hochipohub/dashboard.php">

Dashboard

</a>

</li>



<?php } ?>





<?php if(!isLogin()){ ?>


<li>

<a href="../auth/login.php">

Login

</a>

</li>



<?php }else{ ?>



<li>

<a href="../auth/logout.php">

Logout

</a>

</li>



<?php } ?>



</ul>



</nav>