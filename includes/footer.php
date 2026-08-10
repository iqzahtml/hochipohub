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


                <!-- BRAND -->

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


                <!-- SHOP -->

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


                <!-- SELL -->

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


                <!-- SUPPORT -->

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


            <!-- BOTTOM -->

            <div class="footer-bottom">

                <div class="footer-bottom-inner">

                    <span>
                        © <?= date('Y') ?> HochipoHub.
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
        | Automatically load authentication modals
        |--------------------------------------------------------------------------
        */

        if (file_exists(__DIR__ . '/login_modal.php')) {

            require_once __DIR__ . '/login_modal.php';

        }


        if (file_exists(__DIR__ . '/register_modal.php')) {

            require_once __DIR__ . '/register_modal.php';

        }

        ?>


        <!-- =====================================================
             MAIN JAVASCRIPT
        ====================================================== -->

        <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {


                /*
                |--------------------------------------------------------------------------
                | OPEN MODAL
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '[data-modal-open]'
                    )
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const modalId =
                                    button.getAttribute(
                                        'data-modal-open'
                                    );

                                openModal(modalId);

                            }
                        );

                    });


                /*
                |--------------------------------------------------------------------------
                | CLOSE MODAL
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '[data-modal-close]'
                    )
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const modalId =
                                    button.getAttribute(
                                        'data-modal-close'
                                    );

                                closeModal(modalId);

                            }
                        );

                    });


                /*
                |--------------------------------------------------------------------------
                | SWITCH MODAL
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '[data-modal-switch]'
                    )
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const currentModal =
                                    button.getAttribute(
                                        'data-modal-switch'
                                    );

                                const targetModal =
                                    button.getAttribute(
                                        'data-modal-target'
                                    );

                                closeModal(
                                    currentModal
                                );

                                setTimeout(
                                    function () {

                                        openModal(
                                            targetModal
                                        );

                                    },
                                    180
                                );

                            }
                        );

                    });


                /*
                |--------------------------------------------------------------------------
                | CLOSE WHEN CLICK OUTSIDE
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '.modal-overlay'
                    )
                    .forEach(function (modal) {

                        modal.addEventListener(
                            'click',
                            function (event) {

                                if (
                                    event.target === modal
                                ) {

                                    closeModal(
                                        modal.id
                                    );

                                }

                            }
                        );

                    });


                /*
                |--------------------------------------------------------------------------
                | ESCAPE KEY
                |--------------------------------------------------------------------------
                */

                document.addEventListener(
                    'keydown',
                    function (event) {

                        if (
                            event.key === 'Escape'
                        ) {

                            document
                                .querySelectorAll(
                                    '.modal-overlay.show'
                                )
                                .forEach(
                                    function (modal) {

                                        closeModal(
                                            modal.id
                                        );

                                    }
                                );

                        }

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | PASSWORD TOGGLE
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '[data-password-target]'
                    )
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const targetId =
                                    button.getAttribute(
                                        'data-password-target'
                                    );

                                const input =
                                    document.getElementById(
                                        targetId
                                    );

                                if (!input) {
                                    return;
                                }

                                if (
                                    input.type ===
                                    'password'
                                ) {

                                    input.type =
                                        'text';

                                    button.textContent =
                                        '🙈';

                                } else {

                                    input.type =
                                        'password';

                                    button.textContent =
                                        '👁';

                                }

                            }
                        );

                    });


                /*
                |--------------------------------------------------------------------------
                | REGISTER PASSWORD MATCH
                |--------------------------------------------------------------------------
                */

                const registerForm =
                    document.getElementById(
                        'registerForm'
                    );

                if (registerForm) {

                    registerForm.addEventListener(
                        'submit',
                        function (event) {

                            const password =
                                document.getElementById(
                                    'registerPassword'
                                );

                            const confirmPassword =
                                document.getElementById(
                                    'registerConfirmPassword'
                                );

                            if (
                                password &&
                                confirmPassword &&
                                password.value !==
                                confirmPassword.value
                            ) {

                                event.preventDefault();

                                alert(
                                    'Passwords do not match.'
                                );

                                confirmPassword.focus();

                            }

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | PREVENT BODY SCROLL WHEN MODAL OPEN
                |--------------------------------------------------------------------------
                */

                function updateBodyModalState() {

                    const activeModal =
                        document.querySelector(
                            '.modal-overlay.show'
                        );

                    document.body.classList.toggle(
                        'modal-open',
                        !!activeModal
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | GLOBAL FUNCTIONS
                |--------------------------------------------------------------------------
                */

                window.openModal =
                    function (modalId) {

                        const modal =
                            document.getElementById(
                                modalId
                            );

                        if (!modal) {
                            return;
                        }

                        modal.classList.add(
                            'show'
                        );

                        modal.setAttribute(
                            'aria-hidden',
                            'false'
                        );

                        document.body.classList.add(
                            'modal-open'
                        );


                        const firstInput =
                            modal.querySelector(
                                'input:not([type="hidden"])'
                            );

                        if (firstInput) {

                            setTimeout(
                                function () {

                                    firstInput.focus();

                                },
                                250
                            );

                        }

                    };


                window.closeModal =
                    function (modalId) {

                        const modal =
                            document.getElementById(
                                modalId
                            );

                        if (!modal) {
                            return;
                        }

                        modal.classList.remove(
                            'show'
                        );

                        modal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                        updateBodyModalState();

                    };

            }
        );

        </script>


    </body>

</html>