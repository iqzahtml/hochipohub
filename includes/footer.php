<!-- =========================================================
     HOCHIPOHUB - GLOBAL FOOTER
     ========================================================= -->
</main>
<footer class="site-footer">
<div class="footer-container">
    <div class="footer-grid">
        <!-- =================================================
             BRAND
             ================================================= -->
        <div class="footer-column">
            <div class="footer-brand">
                Hochipo<span>Hub</span>
            </div>
            <p class="footer-description">
                A marketplace built to connect
                customers with local vendors and unique
                products — all in one place.
            </p>
        </div>
        <!-- =================================================
             QUICK LINKS
             ================================================= -->
        <div class="footer-column">
            <h4>
                Quick Links
            </h4>
            <a href="<?= htmlspecialchars(
                navUrl('index.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Home
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('catalog.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Catalog
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('vendor.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Vendors
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('contact.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Contact
            </a>
        </div>
        <!-- =================================================
             CUSTOMER
             ================================================= -->
        <div class="footer-column">
            <h4>
                Customer
            </h4>
            <a href="<?= htmlspecialchars(
                navUrl('cart.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Shopping Cart
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('wishlist.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Wishlist
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('order.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                My Orders
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('profile.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                My Profile
            </a>
        </div>
        <!-- =================================================
             SELLER
             ================================================= -->
        <div class="footer-column">
            <h4>
                Seller
            </h4>
            <a href="<?= htmlspecialchars(
                navUrl('seller/dashboard.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Vendor Dashboard
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('seller/products.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                My Products
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('seller/add_product.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Add Product
            </a>
            <a href="<?= htmlspecialchars(
                navUrl('seller/orders.php'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                Orders
            </a>
        </div>
    </div>
    <!-- =====================================================
         FOOTER BOTTOM
         ===================================================== -->
    <div class="footer-bottom">
        © <?= date('Y') ?>
        HochipoHub.
        All rights reserved.
    </div>
</div>
</footer>