<?php
/**
 * =========================================================
 * HOCHIPOHUB - VENDOR SIDEBAR
 * File: includes/vendor_sidebar.php
 * =========================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Get current vendor information
|--------------------------------------------------------------------------
*/
$vendorName = 'Vendor';
$businessName = 'My Store';
$vendorLogo = null;

if (isset($_SESSION['user_id'])) {

    $userId = (int) $_SESSION['user_id'];

    /*
    |--------------------------------------------------------------------------
    | Use existing database connection
    |--------------------------------------------------------------------------
    */
    if (!isset($conn)) {

        $dbFile = dirname(__DIR__) . '/database/db.php';

        if (file_exists($dbFile)) {
            require_once $dbFile;
        }
    }

    if (isset($conn) && $conn instanceof mysqli) {

        $sql = "
            SELECT 
                u.name,
                u.profile_image,
                v.business_name,
                v.business_logo,
                v.approval_status
            FROM users u
            LEFT JOIN vendors v
                ON u.user_id = v.user_id
            WHERE u.user_id = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {

                $vendor = mysqli_fetch_assoc($result);

                if (!empty($vendor['name'])) {
                    $vendorName = $vendor['name'];
                }

                if (!empty($vendor['business_name'])) {
                    $businessName = $vendor['business_name'];
                }

                if (!empty($vendor['business_logo'])) {
                    $vendorLogo = $vendor['business_logo'];
                }

                /*
                |--------------------------------------------------------------------------
                | Store vendor information in session
                |--------------------------------------------------------------------------
                */
                $_SESSION['vendor_name'] = $vendorName;
                $_SESSION['business_name'] = $businessName;

                if (!empty($vendor['approval_status'])) {
                    $_SESSION['vendor_approval_status'] = $vendor['approval_status'];
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/*
|--------------------------------------------------------------------------
| Current page
|--------------------------------------------------------------------------
*/
$currentPage = basename($_SERVER['PHP_SELF']);

/*
|--------------------------------------------------------------------------
| Sidebar link helper
|--------------------------------------------------------------------------
*/
function vendorSidebarActive($page)
{
    global $currentPage;

    return $currentPage === $page ? 'active' : '';
}

/*
|--------------------------------------------------------------------------
| Vendor logo
|--------------------------------------------------------------------------
*/
$logoPath = '';

if (!empty($vendorLogo)) {

    if (
        strpos($vendorLogo, 'uploads/') === 0 ||
        strpos($vendorLogo, 'uploads\\') === 0
    ) {
        $logoPath = '../' . str_replace('\\', '/', $vendorLogo);
    } else {
        $logoPath = '../uploads/vendors/' . basename($vendorLogo);
    }
}
?>

<!-- =========================================================
     VENDOR SIDEBAR
========================================================= -->

<aside class="vendor-sidebar" id="vendorSidebar">

    <!-- Sidebar Header -->
    <div class="vendor-sidebar-header">

        <div class="vendor-brand">

            <div class="vendor-brand-icon">
                <i class="fas fa-store"></i>
            </div>

            <div class="vendor-brand-text">
                <h2>HOCHIPO<span>HUB</span></h2>
                <small>Vendor Panel</small>
            </div>

        </div>

        <button
            type="button"
            class="vendor-sidebar-close"
            id="vendorSidebarClose"
            aria-label="Close sidebar"
        >
            <i class="fas fa-times"></i>
        </button>

    </div>


    <!-- =====================================================
         VENDOR PROFILE
    ====================================================== -->

    <div class="vendor-profile">

        <div class="vendor-profile-image">

            <?php if (!empty($logoPath)): ?>

                <img
                    src="<?php echo htmlspecialchars($logoPath); ?>"
                    alt="Business Logo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >

                <div
                    class="vendor-default-avatar"
                    style="display:none;"
                >
                    <i class="fas fa-store"></i>
                </div>

            <?php else: ?>

                <div class="vendor-default-avatar">
                    <i class="fas fa-store"></i>
                </div>

            <?php endif; ?>

        </div>


        <div class="vendor-profile-info">

            <strong>
                <?php echo htmlspecialchars($businessName); ?>
            </strong>

            <span>
                <?php echo htmlspecialchars($vendorName); ?>
            </span>

        </div>

    </div>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav class="vendor-navigation">

        <div class="vendor-nav-title">
            <span>MAIN MENU</span>
        </div>


        <!-- Dashboard -->
        <a
            href="dashboard.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('dashboard.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-chart-line"></i>
            </span>

            <span class="vendor-nav-text">
                Dashboard
            </span>
        </a>


        <!-- Products -->
        <a
            href="products.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('products.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-box"></i>
            </span>

            <span class="vendor-nav-text">
                My Products
            </span>
        </a>


        <!-- Add Product -->
        <a
            href="add_product.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('add_product.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-plus-circle"></i>
            </span>

            <span class="vendor-nav-text">
                Add Product
            </span>
        </a>


        <!-- Orders -->
        <a
            href="orders.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('orders.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-shopping-bag"></i>
            </span>

            <span class="vendor-nav-text">
                Orders
            </span>
        </a>


        <!-- Sales -->
        <a
            href="sales.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('sales.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-chart-bar"></i>
            </span>

            <span class="vendor-nav-text">
                Sales
            </span>
        </a>


        <!-- =================================================
             MANAGEMENT
        ================================================== -->

        <div class="vendor-nav-title">
            <span>MANAGEMENT</span>
        </div>


        <!-- Inventory -->
        <a
            href="../inventory.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('inventory.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-warehouse"></i>
            </span>

            <span class="vendor-nav-text">
                Inventory
            </span>
        </a>


        <!-- Commission -->
        <a
            href="../commission.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('commission.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-coins"></i>
            </span>

            <span class="vendor-nav-text">
                Commission
            </span>
        </a>


        <!-- Setup Profile -->
        <a
            href="setup_profile.php"
            class="vendor-nav-link <?php echo vendorSidebarActive('setup_profile.php'); ?>"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-store-alt"></i>
            </span>

            <span class="vendor-nav-text">
                Store Profile
            </span>
        </a>


        <!-- Divider -->
        <div class="vendor-nav-divider"></div>


        <!-- View Marketplace -->
        <a
            href="../catalog.php"
            class="vendor-nav-link"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-shopping-cart"></i>
            </span>

            <span class="vendor-nav-text">
                View Marketplace
            </span>
        </a>


        <!-- Main Dashboard -->
        <a
            href="../dashboard.php"
            class="vendor-nav-link"
        >
            <span class="vendor-nav-icon">
                <i class="fas fa-home"></i>
            </span>

            <span class="vendor-nav-text">
                Customer Dashboard
            </span>
        </a>

    </nav>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->

    <div class="vendor-sidebar-footer">

        <a
            href="../profile.php"
            class="vendor-footer-link"
        >
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>


        <a
            href="../auth/logout.php"
            class="vendor-footer-link logout"
            onclick="return confirm('Are you sure you want to logout?');"
        >
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>

    </div>

</aside>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="vendor-sidebar-overlay"
    id="vendorSidebarOverlay"
></div>


<!-- =========================================================
     SIDEBAR JAVASCRIPT
========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('vendorSidebar');
    const closeButton = document.getElementById('vendorSidebarClose');
    const overlay = document.getElementById('vendorSidebarOverlay');

    /*
    |--------------------------------------------------------------------------
    | Open sidebar
    |--------------------------------------------------------------------------
    */
    const openSidebar = function () {

        if (sidebar) {
            sidebar.classList.add('open');
        }

        if (overlay) {
            overlay.classList.add('show');
        }

        document.body.classList.add('vendor-sidebar-open');
    };


    /*
    |--------------------------------------------------------------------------
    | Close sidebar
    |--------------------------------------------------------------------------
    */
    const closeSidebar = function () {

        if (sidebar) {
            sidebar.classList.remove('open');
        }

        if (overlay) {
            overlay.classList.remove('show');
        }

        document.body.classList.remove('vendor-sidebar-open');
    };


    /*
    |--------------------------------------------------------------------------
    | Close button
    |--------------------------------------------------------------------------
    */
    if (closeButton) {

        closeButton.addEventListener(
            'click',
            closeSidebar
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Overlay
    |--------------------------------------------------------------------------
    */
    if (overlay) {

        overlay.addEventListener(
            'click',
            closeSidebar
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Existing toggle button
    |--------------------------------------------------------------------------
    */
    const toggleButtons = document.querySelectorAll(
        '[data-vendor-sidebar-toggle]'
    );

    toggleButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            openSidebar
        );

    });

});
</script>