<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Footer
|--------------------------------------------------------------------------
| This file:
| - Closes the main content wrapper
| - Displays footer
| - Displays login/register modals
| - Loads JavaScript
| - Provides global UI elements
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Required Configuration
|--------------------------------------------------------------------------
*/

if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config.php';
}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$footerLoggedIn = false;
$footerUserRole = null;

if (function_exists('isLoggedIn')) {

    $footerLoggedIn =
        isLoggedIn();

    $footerUserRole =
        currentUserRole();
}

?>


        </main>
        <!-- END .main-content -->


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer
            class="main-footer"
            id="mainFooter"
        >

            <!-- =================================================
                 FOOTER TOP
            ================================================== -->

            <div class="footer-main">

                <div class="footer-container">


                    <!-- =========================================
                         BRAND
                    ========================================== -->

                    <div class="footer-brand-column">

                        <a
                            href="<?php echo BASE_URL; ?>index.php"
                            class="footer-brand"
                        >

                            <div class="footer-logo">

                                <img
                                    src="<?php echo BASE_URL; ?>image/logo.jpg"
                                    alt="<?php echo SITE_NAME; ?> Logo"
                                >

                            </div>


                            <div class="footer-brand-text">

                                <span class="footer-brand-name">
                                    Hochipo<span>Hub</span>
                                </span>

                                <span class="footer-brand-tagline">
                                    Discover. Shop. Support.
                                </span>

                            </div>

                        </a>


                        <p class="footer-description">

                            A vibrant marketplace connecting
                            customers with local vendors.
                            Discover unique products,
                            support local businesses,
                            and shop your way.

                        </p>


                        <!-- SOCIAL MEDIA -->

                        <div class="footer-socials">

                            <a
                                href="#"
                                class="social-link"
                                aria-label="Instagram"
                                title="Instagram"
                            >

                                <i class="fa-brands fa-instagram"></i>

                            </a>


                            <a
                                href="#"
                                class="social-link"
                                aria-label="TikTok"
                                title="TikTok"
                            >

                                <i class="fa-brands fa-tiktok"></i>

                            </a>


                            <a
                                href="#"
                                class="social-link"aria-label="Facebook"
                                title="Facebook"
                            >

                                <i class="fa-brands fa-facebook-f"></i>

                            </a>


                            <a
                                href="#"
                                class="social-link"
                                aria-label="X"
                                title="X"
                            >

                                <i class="fa-brands fa-x-twitter"></i>

                            </a>

                        </div>

                    </div>


                    <!-- =========================================
                         SHOP
                    ========================================== -->

                    <div class="footer-column">

                        <h3>
                            Explore
                        </h3>


                        <ul>

                            <li>

                                <a
                                    href="<?php echo BASE_URL; ?>catalog.php"
                                >

                                    Explore Products

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

                                    Find Vendors

                                </a>

                            </li>


                            <li>

                                <a
                                    href="<?php echo BASE_URL; ?>catalog.php?sort=trending"
                                >

                                    Trending

                                    <span class="footer-hot-badge">
                                        HOT
                                    </span>

                                </a>

                            </li>


                            <li>

                                <a
                                    href="<?php echo BASE_URL; ?>search.php"
                                >

                                    Search

                                </a>

                            </li>

                        </ul>

                    </div>


                    <!-- =========================================
                         CUSTOMER
                    ========================================== -->

                    <div class="footer-column">

                        <h3>
                            For Customers
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

                                <ahref="<?php echo BASE_URL; ?>profile.php"
                                >

                                    My Profile

                                </a>

                            </li>


                            <li>

                                <a
                                    href="<?php echo BASE_URL; ?>contact.php"
                                >

                                    Help & Support

                                </a>

                            </li>

                        </ul>

                    </div>


                    <!-- =========================================
                         SELLER
                    ========================================== -->

                    <div class="footer-column">

                        <h3>
                            For Vendors
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

                                    Vendor Dashboard

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


                            <li>

                                <a
                                    href="<?php echo BASE_URL; ?>seller/sales.php"
                                >

                                    Sales Analytics

                                </a>

                            </li>

                        </ul>

                    </div>


                    <!-- =========================================
                         CONTACT
                    ========================================== -->

                    <div class="footer-column footer-contact-column">

                        <h3>
                            Stay Connected
                        </h3>


                        <p class="footer-contact-text">

                            Got questions?
                            Want to collaborate?
                            We're here.

                        </p>


                        <a
                            href="<?php echo BASE_URL; ?>contact.php"
                            class="footer-contact-button"
                        >

                            Contact Us

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                        <!-- NEWSLETTER -->

                        <div class="footer-newsletter">

                            <span>
                                Get updates & drops
                            </span>


                            <form
                                action="#"
                                method="POST"
                                class="newsletter-form"
                                id="newsletterForm"
                            >

                                <div class="newsletter-input-wrapper">

                                    <iclass="fa-regular fa-envelope"
                                    ></i>


                                    <input
                                        type="email"
                                        name="newsletter_email"
                                        placeholder="Your email"
                                        autocomplete="email"
                                        required
                                    >


                                    <button
                                        type="submit"
                                        aria-label="Subscribe"
                                    >

                                        <i
                                            class="fa-solid fa-arrow-right"
                                        ></i>

                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 FOOTER BOTTOM
            ================================================== -->

            <div class="footer-bottom">

                <div class="footer-bottom-container">


                    <!-- COPYRIGHT -->

                    <p class="footer-copyright">

                        &copy;

                        <?php echo date('Y'); ?>

                        <strong>
                            HochipoHub
                        </strong>

                        . All rights reserved.

                    </p>


                    <!-- LEGAL LINKS -->

                    <div class="footer-legal-links">

                        <a
                            href="#"
                            onclick="return false;"
                        >
                            Privacy
                        </a>


                        <span>
                            •
                        </span>


                        <a
                            href="#"
                            onclick="return false;"
                        >
                            Terms
                        </a>


                        <span>
                            •
                        </span>


                        <a
                            href="<?php echo BASE_URL; ?>contact.php"
                        >
                            Support
                        </a>

                    </div>


                    <!-- BACK TO TOP -->

                    <button
                        type="button"
                        class="back-to-top"
                        id="backToTop"
                        aria-label="Back to top"
                        title="Back to top"
                    >

                        <i class="fa-solid fa-arrow-up"></i>

                    </button>

                </div>

            </div>

        </footer>


        <!-- =====================================================
             FLOATING CART BUTTON
        ====================================================== -->

        <?php if (
            $footerLoggedIn &&
            $footerUserRole === 'customer'
        ): ?>

            <a
                href="<?php echo BASE_URL; ?>cart.php"
                class="floating-cart"
                id="floatingCart"
                aria-label="Open shopping cart"
            >

                <span class="floating-cart-icon">

                    <i class="fa-solid fa-bag-shopping"></i>

                </span>


                <span class="floating-cart-text">

                    Cart

                </span>


                <span
                    class="floating-cart-count"
                    id="floatingCartCount"
                >

                    <?php

                    if (
                        function_exists('getCartCount')
                    ) {echo getCartCount(
                            currentUserId()
                        );

                    } else {

                        echo '0';

                    }

                    ?>

                </span>

            </a>

        <?php endif; ?>


        <!-- =====================================================
             LOGIN MODAL
        ====================================================== -->

        <?php

        $loginModalPath =
            __DIR__ . '/login_modal.php';

        if (
            file_exists($loginModalPath)
        ) {

            include $loginModalPath;

        }

        ?>


        <!-- =====================================================
             REGISTER MODAL
        ====================================================== -->

        <?php

        $registerModalPath =
            __DIR__. '/register_modal.php';

        if (
            file_exists($registerModalPath)
        ) {

            include $registerModalPath;

        }

        ?>


        <!-- =====================================================
             GLOBAL TOAST
        ====================================================== -->

        <div
            class="hochipo-toast"
            id="hochipoToast"
            role="status"
            aria-live="polite"
            aria-atomic="true"
        >

            <div class="toast-icon">

                <i
                    class="fa-solid fa-circle-check"
                    id="toastIcon"
                ></i>

            </div>


            <div class="toast-content">

                <strong
                    id="toastTitle"
                >
                    Success
                </strong>


                <span
                    id="toastMessage"
                >
                    Done!
                </span>

            </div>


            <button
                type="button"
                class="toast-close"
                id="toastClose"
                aria-label="Close notification"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <!-- =====================================================
             CONFIRMATION MODAL
        ====================================================== -->

        <div
            class="confirm-modal-overlay"
            id="confirmModal"
            hidden
        >

            <div
                class="confirm-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirmModalTitle"
            >

                <div class="confirm-modal-icon">

                    <i
                        class="fa-solid fa-circle-question"
                    ></i>

                </div>


                <h3
                    id="confirmModalTitle"
                >
                    Are you sure?
                </h3>


                <p
                    id="confirmModalMessage"
                >
                    Are you sure you want to continue?
                </p>


                <div class="confirm-modal-actions">

                    <button
                        type="button"
                        class="confirm-cancel-btn"
                        id="confirmCancel"
                    >

                        Cancel

                    </button>


                    <button
                        type="button"
                        class="confirm-submit-btn"
                        id="confirmSubmit"
                    >

                        Continue

                    </button>

                </div>

            </div>

        </div>


    </div>
    <!-- END .website-wrapper -->


    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->


    <!-- Main JS -->

    <script
        src="<?php echo BASE_URL; ?>js/script.js"
        defer
    ></script>


    <!-- Modal JS -->

    <scriptsrc="<?php echo BASE_URL; ?>js/modal.js"
        defer
    ></script>


    <!-- Validation JS -->

    <script
        src="<?php echo BASE_URL; ?>js/validation.js"
        defer
    ></script>


    <!-- Search JS -->

    <script
        src="<?php echo BASE_URL; ?>js/search.js"
        defer
    ></script>


    <!-- Cart JS -->

    <script
        src="<?php echo BASE_URL; ?>js/cart.js"
        defer
    ></script>


    <!-- Checkout JS -->

    <script
        src="<?php echo BASE_URL; ?>js/checkout.js"
        defer
    ></script>


    <!-- Dashboard JS -->

    <script
        src="<?php echo BASE_URL; ?>js/dashboard.js"
        defer
    ></script>


    <!-- Wishlist JS -->

    <script
        src="<?php echo BASE_URL; ?>js/wishlist.js"
        defer
    ></script>


    <!-- Review JS -->

    <script
        src="<?php echo BASE_URL; ?>js/review.js"
        defer
    ></script>


    <!-- =========================================================
         PAGE-SPECIFIC JS
    ========================================================== -->

    <?php if (
        isset($additionalJS)
        &&
        is_array($additionalJS)
    ): ?>

        <?php foreach (
            $additionalJS
            as $jsFile
        ): ?>

            <script
                src="<?php echo BASE_URL; ?>js/<?php echo htmlspecialchars(
                    $jsFile,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                defer
            ></script>

        <?php endforeach; ?>

    <?php endif; ?>


    <!-- =========================================================
         INLINE PAGE SCRIPT
    ========================================================== -->

    <?php if (
        isset($pageScript)
        &&
        !empty($pageScript)
    ): ?>

        <script>
            <?php echo $pageScript; ?>
        </script>

    <?php endif; ?>


</body>

</html>