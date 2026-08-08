<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Global Footer
|--------------------------------------------------------------------------
*/

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER['PHP_SELF'] ?? 'index.php'
    );

?>

</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="site-footer">

    <div class="footer-container">


        <!-- =================================================
             BRAND
        ================================================== -->

        <div class="footer-brand">

            <a
                href="<?php echo BASE_URL; ?>index.php"
                class="footer-logo"
            >

                <img
                    src="<?php echo BASE_URL; ?>image/logo.jpg"
                    alt="HochipoHub"
                >

                <span>
                    HochipoHub
                </span>

            </a>


            <p class="footer-description">

                Discover local products,
                support local businesses,
                and shop with confidence.

            </p>


            <div class="footer-socials">

                <a
                    href="#"
                    aria-label="Facebook"
                    title="Facebook"
                >

                    <i class="fa-brands fa-facebook-f"></i>

                </a>


                <a
                    href="#"
                    aria-label="Instagram"
                    title="Instagram"
                >

                    <i class="fa-brands fa-instagram"></i>

                </a>


                <a
                    href="#"
                    aria-label="TikTok"
                    title="TikTok"
                >

                    <i class="fa-brands fa-tiktok"></i>

                </a>


                <a
                    href="#"
                    aria-label="X"
                    title="X"
                >

                    <i class="fa-brands fa-x-twitter"></i>

                </a>

            </div>

        </div>


        <!-- =================================================
             QUICK LINKS
        ================================================== -->

        <div class="footer-column">

            <h3>
                Explore
            </h3>


            <ul>

                <li>

                    <a
                        href="<?php echo BASE_URL; ?>index.php"
                    >
                        Home
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>catalog.php"
                    >
                        Catalog
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>category.php"
                    >
                        Categories
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>vendor.php"
                    >
                        Vendors
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>search.php"
                    >
                        Search Products
                    </a>

                </li>

            </ul>

        </div>


        <!-- =================================================
             CUSTOMER
        ================================================== -->

        <div class="footer-column">

            <h3>
                Customer
            </h3>


            <ul>

                <li>

                    <a
                        href="<?php echo BASE_URL; ?>cart.php"
                    >
                        Shopping Cart
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>wishlist.php"
                    >
                        Wishlist
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>order.php"
                    >
                        My Orders
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>profile.php"
                    >
                        My Profile
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>contact.php"
                    >
                        Contact Us
                    </a>

                </li>

            </ul>

        </div>


        <!-- =================================================
             SELLER
        ================================================== -->

        <div class="footer-column">

            <h3>
                Sell With Us
            </h3>


            <ul>

                <li>

                    <a
                        href="<?php echo BASE_URL; ?>seller/setup_profile.php"
                    >
                        Become a Vendor
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>seller/dashboard.php"
                    >
                        Seller Dashboard
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>seller/products.php"
                    >
                        Manage Products
                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>seller/orders.php"
                    >
                        Manage Orders
                    </a>

                </li>

            </ul>

        </div>


        <!-- =================================================
             CONTACT
        ================================================== -->

        <div class="footer-column footer-contact">

            <h3>
                Get In Touch
            </h3>


            <div class="contact-item">

                <i class="fa-solid fa-location-dot"></i>

                <span>
                    Malaysia
                </span>

            </div>


            <div class="contact-item">

                <i class="fa-solid fa-envelope"></i>

                <span>
                    support@hochipohub.com
                </span>

            </div>


            <div class="contact-item">

                <i class="fa-solid fa-clock"></i>

                <span>
                    Mon - Fri, 9AM - 6PM
                </span>

            </div>

        </div>

    </div>


    <!-- =================================================
         FOOTER BOTTOM
    ================================================== -->

    <div class="footer-bottom">

        <div class="footer-bottom-container">

            <p>

                &copy;

                <?php echo date('Y'); ?>

                HochipoHub.
                All rights reserved.

            </p>


            <div class="footer-bottom-links">

                <a href="#">
                    Privacy Policy
                </a>

                <a href="#">
                    Terms &amp; Conditions
                </a>

            </div>

        </div>

    </div>

</footer>


<!-- =========================================================
     GLOBAL JAVASCRIPT
========================================================= -->

<script
    src="<?php echo BASE_URL; ?>js/script.js"
    defer
></script>

<script
    src="<?php echo BASE_URL; ?>js/modal.js"
    defer
></script>

<script
    src="<?php echo BASE_URL; ?>js/validation.js"
    defer
></script>


<!-- =========================================================
     PAGE-SPECIFIC JAVASCRIPT
========================================================= -->

<?php

$pageJs = [

    'cart.php'
        => 'cart.js',

    'checkout.php'
        => 'checkout.js',

    'dashboard.php'
        => 'dashboard.js',

    'review.php'
        => 'review.js',

    'search.php'
        => 'search.js',

    'wishlist.php'
        => 'wishlist.js'

];


if (
    isset(
        $pageJs[$currentPage]
    )
):

?>

<script
    src="<?php echo BASE_URL; ?>js/<?php echo $pageJs[$currentPage]; ?>"
    defer
></script>

<?php endif; ?>


</body>

</html>