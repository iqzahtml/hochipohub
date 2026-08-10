<aside class="sidebar vendor-sidebar">



<div class="sidebar-title">


<h2>

Vendor Panel<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Vendor Sidebar
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/session.php';

requireVendor();


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER['PHP_SELF'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| ACTIVE LINK
|--------------------------------------------------------------------------
*/

function vendorSidebarActive(
    string $page
): string {

    global $currentPage;

    return $currentPage === $page
        ? 'active'
        : '';
}


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
|--------------------------------------------------------------------------
*/

$vendor = null;

if (
    function_exists('getCurrentVendor')
) {

    $vendor =
        getCurrentVendor();
}

$businessName =
    $vendor['business_name']
    ?? 'My Store';

$businessLogo =
    $vendor['business_logo']
    ?? '';

$approvalStatus =
    $vendor['approval_status']
    ?? 'Pending';

?>

<!-- =========================================================
     VENDOR SIDEBAR
========================================================= -->

<aside
    class="dashboard-sidebar vendor-sidebar"
    id="vendorSidebar"
>


    <!-- =====================================================
         VENDOR PROFILE
    ====================================================== -->

    <div class="sidebar-profile vendor-profile">

        <div class="sidebar-avatar vendor-avatar">

            <?php if (
                !empty($businessLogo)
            ): ?>

                <img
                    src="<?php echo BASE_URL; ?>image/vendors/<?php echo htmlspecialchars(
                        $businessLogo,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                    alt="<?php echo htmlspecialchars(
                        $businessName,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            <?php else: ?>

                <span>

                    <?php

                    echo strtoupper(
                        substr(
                            $businessName,
                            0,
                            1
                        )
                    );

                    ?>

                </span>

            <?php endif; ?>

        </div>


        <div class="sidebar-profile-info">

            <strong>

                <?php

                echo htmlspecialchars(
                    $businessName,
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </strong>


            <small>

                Vendor Account

            </small>

        </div>

    </div>


    <!-- =====================================================
         APPROVAL STATUS
    ====================================================== -->

    <div
        class="vendor-status-badge
        <?php

        echo strtolower(
            $approvalStatus
        );

        ?>"
    >

        <?php

        if (
            $approvalStatus === 'Approved'
        ) {

            echo '<i class="fa-solid fa-circle-check"></i>';

        } elseif (
            $approvalStatus === 'Rejected'
        ) {

            echo '<i class="fa-solid fa-circle-xmark"></i>';

        } elseif (
            $approvalStatus === 'Suspended'
        ) {

            echo '<i class="fa-solid fa-ban"></i>';

        } else {

            echo '<i class="fa-solid fa-clock"></i>';

        }

        ?>


        <span>

            <?php

            echo htmlspecialchars(
                $approvalStatus,
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </span>

    </div>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav
        class="sidebar-navigation"
        aria-label="Vendor Navigation"
    >


        <div class="sidebar-section-title">

            <span>
                SELLER CENTER
            </span>

        </div>


        <!-- DASHBOARD -->

        <a
            href="<?php echo BASE_URL; ?>seller/dashboard.php"
            class="sidebar-link <?php echo vendorSidebarActive('dashboard.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-chart-line"></i>

            </span>


            <span class="sidebar-link-text">
                Dashboard
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="<?php echo BASE_URL; ?>seller/products.php"
            class="sidebar-link <?php echo vendorSidebarActive('products.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-boxes-stacked"></i>

            </span>


            <span class="sidebar-link-text">
                My Products
            </span>

        </a>


        <!-- ADD PRODUCT -->

        <a
            href="<?php echo BASE_URL; ?>seller/add_product.php"
            class="sidebar-link <?php echo vendorSidebarActive('add_product.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-circle-plus"></i>

            </span>


            <span class="sidebar-link-text">
                Add Product
            </span>

        </a>


        <!-- INVENTORY -->

        <a
            href="<?php echo BASE_URL; ?>inventory.php"
            class="sidebar-link <?php echo vendorSidebarActive('inventory.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-warehouse"></i>

            </span>


            <span class="sidebar-link-text">
                Inventory
            </span>

        </a>


        <div class="sidebar-section-title">

            <span>
                SALES
            </span>

        </div>


        <!-- ORDERS -->

        <a
            href="<?php echo BASE_URL; ?>seller/orders.php"
            class="sidebar-link <?php echo vendorSidebarActive('orders.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-receipt"></i>

            </span>


            <span class="sidebar-link-text">
                Orders
            </span>

        </a>


        <!-- SALES -->

        <a
            href="<?php echo BASE_URL; ?>seller/sales.php"
            class="sidebar-link <?php echo vendorSidebarActive('sales.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-chart-column"></i>

            </span>


            <span class="sidebar-link-text">
                Sales
            </span>

        </a>


        <!-- COMMISSION -->

        <a
            href="<?php echo BASE_URL; ?>commission.php"
            class="sidebar-link <?php echo vendorSidebarActive('commission.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-percent"></i>

            </span>


            <span class="sidebar-link-text">
                Commission
            </span>

        </a>


        <div class="sidebar-section-title">

            <span>
                STORE
            </span>

        </div>


        <!-- STORE PROFILE -->

        <a
            href="<?php echo BASE_URL; ?>seller/setup_profile.php"
            class="sidebar-link"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-store"></i>

            </span>


            <span class="sidebar-link-text">
                Store Profile
            </span>

        </a>


        <!-- PUBLIC STORE -->

        <a
            href="<?php echo BASE_URL; ?>vendor.php"
            class="sidebar-link"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-eye"></i>

            </span>


            <span class="sidebar-link-text">
                View Marketplace
            </span>

        </a>


        <!-- PROFILE -->

        <a
            href="<?php echo BASE_URL; ?>profile.php"
            class="sidebar-link <?php echo vendorSidebarActive('profile.php'); ?>"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-user"></i>

            </span>


            <span class="sidebar-link-text">
                Account Profile
            </span>

        </a>

    </nav>


    <!-- =====================================================
         QUICK ACTION
    ====================================================== -->

    <div class="sidebar-promo vendor-promo">

        <div class="sidebar-promo-icon">

            <i class="fa-solid fa-plus"></i>

        </div>


        <div class="sidebar-promo-content">

            <strong>
                Grow Your Store
            </strong>

            <p>
                Add products and reach more customers.
            </p>


            <a
                href="<?php echo BASE_URL; ?>seller/add_product.php"
            >

                Add Product

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </div>


    <!-- =====================================================
         LOGOUT
    ====================================================== -->

    <div class="sidebar-bottom">

        <a
            href="<?php echo BASE_URL; ?>auth/logout.php"
            class="sidebar-link sidebar-logout"
        >

            <span class="sidebar-link-icon">

                <i class="fa-solid fa-right-from-bracket"></i>

            </span>


            <span class="sidebar-link-text">
                Logout
            </span>

        </a>

    </div>

</aside>

</h2>


</div>





<ul>



<li>

<a href="<?= BASE_URL; ?>seller/dashboard.php">


<i class="fa-solid fa-store"></i>


Dashboard


</a>


</li>






<li>

<a href="<?= BASE_URL; ?>seller/products.php">


<i class="fa-solid fa-box"></i>


Products


</a>


</li>






<li>

<a href="<?= BASE_URL; ?>seller/add_product.php">


<i class="fa-solid fa-plus"></i>


Add Product


</a>


</li>






<li>

<a href="<?= BASE_URL; ?>inventory.php">


<i class="fa-solid fa-warehouse"></i>


Inventory


</a>


</li>






<li>

<a href="<?= BASE_URL; ?>commission.php">


<i class="fa-solid fa-money-bill"></i>


Commission


</a>


</li>






<li>

<a href="<?= BASE_URL; ?>seller/orders.php">


<i class="fa-solid fa-cart-shopping"></i>


Orders


</a>


</li>





</ul>



</aside>