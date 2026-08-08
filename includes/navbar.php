<?php

require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/includes/session.php';

?>

<nav class="navbar">

    <div class="navbar-container">

        <!-- LOGO -->

        <div class="logo">

            <a href="<?php echo BASE_URL; ?>index.php">

                <span class="logo-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </span>

                <span class="logo-text">
                    Hochipo<span>Hub</span>
                </span>

            </a>

        </div>


        <!-- NAVIGATION -->

        <ul class="navbar-menu">

            <li>

                <a href="<?php echo BASE_URL; ?>index.php">

                    <i class="fa-solid fa-house"></i>

                    <span>Home</span>

                </a>

            </li>


            <li>

                <a href="<?php echo BASE_URL; ?>catalog.php">

                    <i class="fa-solid fa-store"></i>

                    <span>Catalog</span>

                </a>

            </li>


            <li>

                <a href="<?php echo BASE_URL; ?>vendor.php">

                    <i class="fa-solid fa-shop"></i>

                    <span>Vendors</span>

                </a>

            </li>


            <?php if (isLoggedIn()): ?>


                <?php if (currentUserRole() === 'customer'): ?>

                    <li>

                        <a href="<?php echo BASE_URL; ?>cart.php">

                            <i class="fa-solid fa-cart-shopping"></i>

                            <span>Cart</span>

                        </a>

                    </li>


                    <li>

                        <a href="<?php echo BASE_URL; ?>wishlist.php">

                            <i class="fa-solid fa-heart"></i>

                            <span>Wishlist</span>

                        </a>

                    </li>


                <?php endif; ?>


                <li>

                    <a href="<?php echo BASE_URL; ?>dashboard.php">

                        <i class="fa-solid fa-chart-line"></i>

                        <span>Dashboard</span>

                    </a>

                </li>


                <li>

                    <a href="<?php echo BASE_URL; ?>profile.php">

                        <i class="fa-solid fa-user"></i>

                        <span>
                            <?php echo htmlspecialchars(
                                currentUserName(),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </span>

                    </a>

                </li>


                <li>

                    <a
                        href="<?php echo BASE_URL; ?>auth/logout.php"
                        class="nav-login-btn logout-btn"
                    >

                        <i class="fa-solid fa-right-from-bracket"></i>

                        <span>Logout</span>

                    </a>

                </li>


            <?php else: ?>


                <li>

                    <button
                        type="button"
                        class="nav-login-btn"
                        id="openLoginModal"
                    >

                        <i class="fa-solid fa-right-to-bracket"></i>

                        <span>Login</span>

                    </button>

                </li>


                <li>

                    <button
                        type="button"
                        class="nav-register-btn"
                        id="openRegisterModal"
                    >

                        <i class="fa-solid fa-user-plus"></i>

                        <span>Register</span>

                    </button>

                </li>


            <?php endif; ?>

        </ul>


        <!-- MOBILE BUTTON -->

        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            aria-label="Open navigation menu"
        >

            <i class="fa-solid fa-bars"></i>

        </button>

    </div>

</nav>