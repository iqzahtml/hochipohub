<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL FOOTER
|--------------------------------------------------------------------------
*/

$baseUrl = defined('BASE_URL')
    ? BASE_URL
    : '/hochipohub/';

?>

        </div>
        <!-- END PAGE WRAPPER -->


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer class="site-footer">

            <div class="footer-container">


                <!-- =================================================
                     BRAND
                ================================================== -->

                <div class="footer-brand">

                    <a
                        href="<?= $baseUrl ?>index.php"
                        class="brand footer-logo"
                    >

                        <span class="brand-mark">
                            H
                        </span>

                        <span class="brand-text">
                            Hochipo<span>Hub</span>
                        </span>

                    </a>


                    <p>
                        Your local marketplace for discovering
                        products, supporting vendors and shopping
                        smarter.
                    </p>


                    <div class="footer-socials">

                        <a
                            href="#"
                            aria-label="Instagram"
                            title="Instagram"
                        >
                            ◎
                        </a>

                        <a
                            href="#"
                            aria-label="Facebook"
                            title="Facebook"
                        >
                            f
                        </a>

                        <a
                            href="#"
                            aria-label="TikTok"
                            title="TikTok"
                        >
                            ♪
                        </a>

                    </div>

                </div>


                <!-- =================================================
                     SHOP
                ================================================== -->

                <div class="footer-column">

                    <h3>
                        Shop
                    </h3>

                    <a href="<?= $baseUrl ?>catalog.php">
                        All Products
                    </a>

                    <a href="<?= $baseUrl ?>category.php">
                        Categories
                    </a>

                    <a href="<?= $baseUrl ?>vendor.php">
                        Vendors
                    </a>

                    <a href="<?= $baseUrl ?>wishlist.php">
                        Wishlist
                    </a>

                </div>


                <!-- =================================================
                     SELL
                ================================================== -->

                <div class="footer-column">

                    <h3>
                        Sell
                    </h3>

                    <a href="<?= $baseUrl ?>seller/dashboard.php">
                        Seller Center
                    </a>

                    <a href="<?= $baseUrl ?>seller/setup_profile.php">
                        Become a Vendor
                    </a>

                    <a href="<?= $baseUrl ?>seller/add_product.php">
                        Add Product
                    </a>

                    <a href="<?= $baseUrl ?>seller/orders.php">
                        Seller Orders
                    </a>

                </div>


                <!-- =================================================
                     SUPPORT
                ================================================== -->

                <div class="footer-column">

                    <h3>
                        Support
                    </h3>

                    <a href="<?= $baseUrl ?>contact.php">
                        Contact Us
                    </a>

                    <a href="<?= $baseUrl ?>order.php">
                        Track Order
                    </a>

                    <a href="<?= $baseUrl ?>profile.php">
                        My Account
                    </a>

                    <a href="<?= $baseUrl ?>index.php">
                        Help Centre
                    </a>

                </div>


            </div>


            <!-- =====================================================
                 FOOTER BOTTOM
            ====================================================== -->

            <div class="footer-bottom">

                <div class="footer-bottom-inner">

                    <span>
                        ©️ <?= date('Y') ?> HochipoHub.
                        All rights reserved.
                    </span>

                    <span>
                        Made for local sellers & shoppers.
                    </span>

                </div>

            </div>

        </footer>


        <!-- =====================================================
             LOGIN / REGISTER MODALS
        ====================================================== -->

        <?php

        /*
        |--------------------------------------------------------------------------
        | LOAD LOGIN MODAL
        |--------------------------------------------------------------------------
        */

        if (
            file_exists(
                __DIR__ . '/login_modal.php'
            )
        ) {

            require_once
                __DIR__ . '/login_modal.php';

        }


        /*
        |--------------------------------------------------------------------------
        | LOAD REGISTER MODAL
        |--------------------------------------------------------------------------
        */

        if (
            file_exists(
                __DIR__ . '/register_modal.php'
            )
        ) {

            require_once
                __DIR__ . '/register_modal.php';

        }

        ?>


        <!-- =====================================================
             MODAL JAVASCRIPT
        ====================================================== -->

        <script
            src="<?= $baseUrl ?>js/modal.js"
        ></script>


    </body>

</html>