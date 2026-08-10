<?php
// =========================================================
// HOCHIPO HUB - GLOBAL FOOTER
// File: includes/footer.php
// =========================================================
?>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->
    <footer class="site-footer">

        <div class="footer-container">


            <!-- =================================================
                 FOOTER BRAND
            ================================================== -->
            <div class="footer-column footer-brand">

                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>index.php"
                   class="footer-logo">

                    <div class="footer-logo-icon">
                        H
                    </div>

                    <div class="footer-logo-text">
                        <strong>HOCHIPO</strong>
                        <span>HUB</span>
                    </div>

                </a>


                <p class="footer-description">

                    Your trusted digital marketplace for discovering
                    products and supporting local vendors.

                </p>


                <!-- Social Media -->
                <div class="footer-social">

                    <a
                        href="#"
                        aria-label="Facebook"
                        class="footer-social-link"
                    >
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>


                    <a
                        href="#"
                        aria-label="Instagram"
                        class="footer-social-link"
                    >
                        <i class="fa-brands fa-instagram"></i>
                    </a>


                    <a
                        href="#"
                        aria-label="TikTok"
                        class="footer-social-link"
                    >
                        <i class="fa-brands fa-tiktok"></i>
                    </a>


                    <a
                        href="#"
                        aria-label="X"
                        class="footer-social-link"
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
                    Quick Links
                </h3>

                <ul class="footer-links">

                    <li>
                        <a href="../index.php">
                            Home
                        </a>
                    </li>

                    <li>
                        <a href="../catalog.php">
                            Products
                        </a>
                    </li>

                    <li>
                        <a href="../category.php">
                            Categories
                        </a>
                    </li>

                    <li>
                        <a href="../vendor.php">
                            Vendors
                        </a>
                    </li>

                    <li>
                        <a href="../contact.php">
                            Contact
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

                <ul class="footer-links">

                    <li>
                        <a href="../cart.php">
                            My Cart
                        </a>
                    </li>

                    <li>
                        <a href="../wishlist.php">
                            Wishlist
                        </a>
                    </li>

                    <li>
                        <a href="../order.php">
                            My Orders
                        </a>
                    </li>

                    <li>
                        <a href="../profile.php">
                            My Profile
                        </a>
                    </li>

                    <li>
                        <a href="../contact.php">
                            Help & Support
                        </a>
                    </li>

                </ul>

            </div>


            <!-- =================================================
                 VENDOR
            ================================================== -->
            <div class="footer-column">

                <h3>
                    Become a Vendor
                </h3>

                <ul class="footer-links">

                    <li>
                        <a href="../seller/setup_profile.php">
                            Start Selling
                        </a>
                    </li>

                    <li>
                        <a href="../seller/dashboard.php">
                            Seller Dashboard
                        </a>
                    </li>

                    <li>
                        <a href="../seller/products.php">
                            Manage Products
                        </a>
                    </li>

                    <li>
                        <a href="../seller/orders.php">
                            Manage Orders
                        </a>
                    </li>

                    <li>
                        <a href="../seller/sales.php">
                            Sales
                        </a>
                    </li>

                </ul>

            </div>


            <!-- =================================================
                 CONTACT
            ================================================== -->
            <div class="footer-column">

                <h3>
                    Get In Touch
                </h3>


                <div class="footer-contact-item">

                    <i class="fa-solid fa-envelope"></i>

                    <span>
                        support@hochipohub.com
                    </span>

                </div>


                <div class="footer-contact-item">

                    <i class="fa-solid fa-phone"></i>

                    <span>
                        +60 12-345 6789
                    </span>

                </div>


                <div class="footer-contact-item">

                    <i class="fa-solid fa-location-dot"></i>

                    <span>
                        Johor, Malaysia
                    </span>

                </div>

            </div>

        </div>


        <!-- =====================================================
             FOOTER BOTTOM
        ====================================================== -->
        <div class="footer-bottom">

            <div class="footer-bottom-container">

                <p>
                    &copy;
                    <?php echo date('Y'); ?>
                    HochipoHub. All rights reserved.
                </p>


                <div class="footer-bottom-links">

                    <a href="../contact.php">
                        Contact Us
                    </a>

                    <span>
                        |
                    </span>

                    <a href="../index.php">
                        HochipoHub
                    </a>

                </div>

            </div>

        </div>

    </footer>


    <!-- =====================================================
         GLOBAL JAVASCRIPT
    ====================================================== -->

    <!-- Main Script -->
    <script src="../js/script.js"></script>

    <!-- Validation -->
    <script src="../js/validation.js"></script>


</body>
</html>