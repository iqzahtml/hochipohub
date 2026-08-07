<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Footer
|--------------------------------------------------------------------------
|
| Used by:
| - all pages
|
|--------------------------------------------------------------------------
*/

?>


</main>


<footer class="footer">



<div class="footer-container">



<div class="footer-brand">


<img

src="<?= IMAGE_URL; ?>logo.jpg"

alt="HochipoHub Logo"

>



<h3>

HochipoHub

</h3>



<p>

Discover local products.
Support local vendors.

</p>



</div>






<div class="footer-links">



<h4>

Quick Links

</h4>



<a href="<?= BASE_URL; ?>index.php">

Home

</a>



<a href="<?= BASE_URL; ?>catalog.php">

Catalog

</a>



<a href="<?= BASE_URL; ?>vendor.php">

Vendors

</a>



<a href="<?= BASE_URL; ?>contact.php">

Contact

</a>



</div>








<div class="footer-links">



<h4>

Account

</h4>



<?php if(isLogin()){ ?>



<a href="<?= BASE_URL; ?>profile.php">

Profile

</a>



<a href="<?= BASE_URL; ?>auth/logout.php">

Logout

</a>



<?php }else{ ?>



<a href="#" onclick="openLoginModal()">

Login

</a>



<a href="#" onclick="openRegisterModal()">

Register

</a>



<?php } ?>



</div>








<div class="footer-social">



<h4>

Follow Us

</h4>




<div class="social-icons">


<a href="#">

<i class="fa-brands fa-facebook"></i>

</a>



<a href="#">

<i class="fa-brands fa-instagram"></i>

</a>



<a href="#">

<i class="fa-brands fa-tiktok"></i>

</a>



</div>



</div>




</div>







<div class="footer-bottom">


<p>


© <?= date('Y'); ?> HochipoHub.
All Rights Reserved.


</p>


</div>




</footer>







<!-- LOGIN REGISTER POPUP -->

<?php include dirname(__FILE__) . '/login_modal.php'; ?>

<?php include dirname(__FILE__) . '/register_modal.php'; ?>







<!-- JAVASCRIPT -->


<script src="<?= BASE_URL; ?>js/script.js"></script>


<script src="<?= BASE_URL; ?>js/modal.js"></script>


<script src="<?= BASE_URL; ?>js/search.js"></script>



<?php if(isset($additionalJS)): ?>


<?php foreach($additionalJS as $js): ?>


<script src="<?= BASE_URL; ?>js/<?= $js; ?>"></script>


<?php endforeach; ?>


<?php endif; ?>




</body>

</html>