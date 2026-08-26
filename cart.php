<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CUSTOMER CART
|--------------------------------------------------------------------------
| File:
| cart.php
|--------------------------------------------------------------------------
|
| Purpose:
| - Display customer shopping cart
| - Support multiple vendors
| - Update quantity
| - Remove cart items
| - Calculate subtotal
| - Continue to checkout
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DATABASE / SESSION / FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('cartEscape')) {

    function cartEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('cartProductImage')) {

    function cartProductImage($image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }


        /*
        |--------------------------------------------------------------------------
        | ALREADY FULL / RELATIVE PATH
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with($image, 'http://') ||
            str_starts_with($image, 'https://') ||
            str_starts_with($image, 'uploads/')
        ) {
            return $image;
        }


        return
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('cartVendorLogo')) {

    function cartVendorLogo($image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }


        if (
            str_starts_with($image, 'http://') ||
            str_starts_with($image, 'https://') ||
            str_starts_with($image, 'uploads/')
        ) {
            return $image;
        }


        return
            'uploads/vendors/' .
            rawurlencode(
                basename($image)
            );
    }
}


/*
|--------------------------------------------------------------------------
| REMOVE CART ITEM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_cart'])
) {

    $cartId =
        filter_input(
            INPUT_POST,
            'cart_id',
            FILTER_VALIDATE_INT
        );


    if ($cartId) {

        $stmt =
            $db->prepare("
                DELETE FROM cart

                WHERE cart_id = ?

                AND customer_id = ?
            ");


        $stmt->execute([
            $cartId,
            $userId
        ]);
    }


    header(
        'Location: cart.php?success=removed'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE QUANTITY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_cart'])
) {

    $cartId =
        filter_input(
            INPUT_POST,
            'cart_id',
            FILTER_VALIDATE_INT
        );


    $quantity =
        filter_input(
            INPUT_POST,
            'quantity',
            FILTER_VALIDATE_INT
        );


    if (
        $cartId &&
        $quantity !== false &&
        $quantity > 0
    ) {


        /*
        |--------------------------------------------------------------------------
        | CHECK PRODUCT + STOCK + STATUS
        |--------------------------------------------------------------------------
        */

        $stockStmt =
            $db->prepare("
                SELECT

                    p.stock_quantity,
                    p.status

                FROM cart c

                INNER JOIN products p
                    ON c.product_id = p.product_id

                WHERE c.cart_id = ?

                AND c.customer_id = ?

                LIMIT 1
            ");


        $stockStmt->execute([
            $cartId,
            $userId
        ]);


        $stock =
            $stockStmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($stock) {

            $availableStock =
                (int) $stock['stock_quantity'];


            $productStatus =
                trim(
                    (string) $stock['status']
                );


            /*
            |--------------------------------------------------------------------------
            | ONLY UPDATE IF AVAILABLE
            |--------------------------------------------------------------------------
            */

            if (
                $availableStock > 0 &&
                $productStatus === 'Available'
            ) {

                if ($quantity > $availableStock) {
                    $quantity = $availableStock;
                }


                $stmt =
                    $db->prepare("
                        UPDATE cart

                        SET quantity = ?

                        WHERE cart_id = ?

                        AND customer_id = ?
                    ");


                $stmt->execute([
                    $quantity,
                    $cartId,
                    $userId
                ]);


                header(
                    'Location: cart.php?success=updated'
                );

                exit;
            }
        }
    }


    header(
        'Location: cart.php?error=quantity'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET CART ITEMS
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            c.cart_id,
            c.product_id,
            c.quantity,

            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,

            v.vendor_id,
            v.business_name,
            v.business_logo,

            cat.category_id,
            cat.category_name

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories cat
            ON p.category_id = cat.category_id

        WHERE c.customer_id = ?

        ORDER BY
            v.business_name ASC,
            p.product_name ASC
    ");


$stmt->execute([
    $userId
]);


$cartItems =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| TOTALS
|--------------------------------------------------------------------------
*/

$subtotal = 0;

$totalItems = 0;

$hasUnavailableItems = false;


foreach ($cartItems as $item) {

    $quantity =
        (int) $item['quantity'];


    $price =
        (float) $item['price'];


    $subtotal +=
        $price * $quantity;


    $totalItems +=
        $quantity;


    /*
    |--------------------------------------------------------------------------
    | CHECK WHETHER CHECKOUT SHOULD BE ALLOWED
    |--------------------------------------------------------------------------
    */

    if (
        $item['status'] !== 'Available' ||
        (int) $item['stock_quantity'] <= 0 ||
        $quantity > (int) $item['stock_quantity']
    ) {

        $hasUnavailableItems = true;
    }
}


/*
|--------------------------------------------------------------------------
| DELIVERY
|--------------------------------------------------------------------------
*/

$deliveryFee = 0;

$total =
    $subtotal +
    $deliveryFee;


/*
|--------------------------------------------------------------------------
| SIDEBAR CART BADGE
|--------------------------------------------------------------------------
*/

$cartCount =
    $totalItems;


/*
|--------------------------------------------------------------------------
| WISHLIST COUNT
|--------------------------------------------------------------------------
*/

$wishlistCount = 0;


try {

    $wishlistStmt =
        $db->prepare("
            SELECT COUNT(*) AS total

            FROM wishlist

            WHERE customer_id = ?
        ");


    $wishlistStmt->execute([
        $userId
    ]);


    $wishlistRow =
        $wishlistStmt->fetch(
            PDO::FETCH_ASSOC
        );


    $wishlistCount =
        (int) (
            $wishlistRow['total']
            ?? 0
        );

} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| GROUP CART BY VENDOR
|--------------------------------------------------------------------------
*/

$vendors = [];


foreach ($cartItems as $item) {

    $vendorId =
        (int) $item['vendor_id'];


    if (!isset($vendors[$vendorId])) {

        $vendors[$vendorId] = [

            'vendor_id' =>
                $vendorId,

            'business_name' =>
                $item['business_name'],

            'business_logo' =>
                $item['business_logo'],

            'items' => []

        ];
    }


    $vendors[$vendorId]['items'][] =
        $item;
}


/*
|--------------------------------------------------------------------------
| PAGE CONFIG
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Catalog uses includes/header.php.
| Cart now uses the same shared header too.
|
|--------------------------------------------------------------------------
*/

$pageTitle =
    'My Cart';


$extraCSS = [
    'cart.css',
    'dashboard.css'
];


$hideSiteMainWrapper =
    true;


/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/header.php';


/*
|--------------------------------------------------------------------------
| CUSTOMER NAVIGATION
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<!-- ===============================================================
     CART PAGE
================================================================ -->

<main class="hh-cart-page">


    <div class="hh-cart-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-cart-hero">


            <div class="hh-cart-hero-content">


                <span class="hh-cart-hero-pill">

                    <i class="bi bi-cart3"></i>

                    YOUR SHOPPING BAG

                </span>


                <h1>

                    Your Cart,
                    <span>Ready When You Are.</span>

                </h1>


                <p>

                    Review your favourite finds, adjust quantities
                    and continue to secure checkout when everything
                    looks perfect.

                </p>


                <div class="hh-cart-hero-actions">


                    <a
                        href="catalog.php"
                        class="hh-cart-hero-primary"
                    >

                        <i class="bi bi-bag"></i>

                        Continue Shopping

                    </a>


                    <?php if (
                        !empty($cartItems) &&
                        !$hasUnavailableItems
                    ): ?>


                        <a
                            href="checkout.php"
                            class="hh-cart-hero-secondary"
                        >

                            Checkout

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    <?php endif; ?>


                </div>


            </div>



            <!-- ===================================================
                 HERO VISUAL
            ==================================================== -->

            <div class="hh-cart-hero-visual">


                <div class="hh-cart-big-icon">

                    <i class="bi bi-cart-check"></i>

                </div>


                <div class="hh-cart-floating-card card-one">

                    <span>

                        <i class="bi bi-bag-check"></i>

                    </span>


                    <div>

                        <small>
                            ITEMS
                        </small>

                        <strong>
                            <?= number_format(
                                $totalItems
                            ) ?>
                        </strong>

                    </div>

                </div>


                <div class="hh-cart-floating-card card-two">

                    <span>

                        <i class="bi bi-shield-check"></i>

                    </span>


                    <div>

                        <small>
                            CHECKOUT
                        </small>

                        <strong>
                            Secure
                        </strong>

                    </div>

                </div>


            </div>


        </section>



        <!-- =======================================================
             QUICK STATS
        ======================================================== -->

        <section class="hh-cart-stats">


            <article class="hh-cart-stat">


                <div class="hh-cart-stat-icon blue">

                    <i class="bi bi-bag"></i>

                </div>


                <div>

                    <span>
                        ITEMS IN CART
                    </span>

                    <strong>
                        <?= number_format(
                            $totalItems
                        ) ?>
                    </strong>

                </div>


            </article>



            <article class="hh-cart-stat">


                <div class="hh-cart-stat-icon purple">

                    <i class="bi bi-shop"></i>

                </div>


                <div>

                    <span>
                        SELLERS
                    </span>

                    <strong>
                        <?= number_format(
                            count($vendors)
                        ) ?>
                    </strong>

                </div>


            </article>



            <article class="hh-cart-stat">


                <div class="hh-cart-stat-icon green">

                    <i class="bi bi-wallet2"></i>

                </div>


                <div>

                    <span>
                        ESTIMATED TOTAL
                    </span>

                    <strong>

                        RM
                        <?= number_format(
                            $total,
                            2
                        ) ?>

                    </strong>

                </div>


            </article>


        </section>



        <!-- =======================================================
             MESSAGE
        ======================================================== -->

        <?php if (
            isset($_GET['success'])
        ): ?>


            <div class="hh-cart-alert success">


                <i class="bi bi-check-circle-fill"></i>


                <?php if (
                    $_GET['success'] === 'removed'
                ): ?>

                    Item removed from your cart.

                <?php elseif (
                    $_GET['success'] === 'updated'
                ): ?>

                    Cart quantity updated successfully.

                <?php else: ?>

                    Cart updated successfully.

                <?php endif; ?>


            </div>


        <?php endif; ?>


        <?php if (
            isset($_GET['error'])
        ): ?>


            <div class="hh-cart-alert error">


                <i class="bi bi-exclamation-circle-fill"></i>

                We couldn't update that item.
                Check its availability and stock quantity.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             EMPTY CART
        ======================================================== -->

        <?php if (empty($cartItems)): ?>


            <section class="hh-empty-cart">


                <div class="hh-empty-art">


                    <div class="hh-empty-circle circle-one"></div>

                    <div class="hh-empty-circle circle-two"></div>


                    <div class="hh-empty-cart-icon">

                        <i class="bi bi-cart3"></i>

                    </div>


                    <div class="hh-empty-mini-box box-one">

                        <i class="bi bi-bag-heart"></i>

                    </div>


                    <div class="hh-empty-mini-box box-two">

                        <i class="bi bi-box-seam"></i>

                    </div>


                </div>


                <div class="hh-empty-copy">


                    <span>
                        YOUR NEXT FIND IS WAITING
                    </span>


                    <h2>

                        Your cart is feeling
                        a little empty.

                    </h2>


                    <p>

                        Explore products from HochipoHub's local
                        sellers and add something you love.
                        Everything you add will appear right here.

                    </p>


                    <div class="hh-empty-actions">


                        <a
                            href="catalog.php"
                            class="hh-empty-primary"
                        >

                            <i class="bi bi-bag"></i>

                            Explore Products

                        </a>


                        <a
                            href="wishlist.php"
                            class="hh-empty-secondary"
                        >

                            <i class="bi bi-heart"></i>

                            View Wishlist

                        </a>


                    </div>


                </div>


            </section>



            <!-- ===================================================
                 EMPTY PAGE BENEFITS
            ==================================================== -->

            <section class="hh-cart-benefits">


                <article>


                    <div>

                        <i class="bi bi-shop"></i>

                    </div>


                    <span>
                        SHOP LOCAL
                    </span>


                    <h3>
                        Discover local sellers
                    </h3>


                    <p>

                        Browse products from independent
                        HochipoHub vendors in one marketplace.

                    </p>


                </article>



                <article>


                    <div>

                        <i class="bi bi-shield-check"></i>

                    </div>


                    <span>
                        SHOP SAFELY
                    </span>


                    <h3>
                        Secure checkout flow
                    </h3>


                    <p>

                        Review your order and delivery information
                        before confirming your purchase.

                    </p>


                </article>



                <article>


                    <div>

                        <i class="bi bi-box-seam"></i>

                    </div>


                    <span>
                        ORDER TRACKING
                    </span>


                    <h3>
                        Keep track of purchases
                    </h3>


                    <p>

                        Your orders stay organised in one place
                        after checkout.

                    </p>


                </article>


            </section>


        <?php else: ?>


            <!-- ===================================================
                 UNAVAILABLE WARNING
            ==================================================== -->

            <?php if ($hasUnavailableItems): ?>


                <div class="hh-cart-alert warning">


                    <i class="bi bi-exclamation-triangle-fill"></i>


                    <div>

                        <strong>
                            Some items need your attention.
                        </strong>

                        Remove or update unavailable items
                        before proceeding to checkout.

                    </div>


                </div>


            <?php endif; ?>



            <!-- ===================================================
                 CART BODY
            ==================================================== -->

            <section class="hh-cart-layout">


                <!-- =================================================
                     ITEMS
                ================================================== -->

                <div class="hh-cart-items">


                    <div class="hh-cart-section-header">


                        <div class="hh-cart-section-title">


                            <div class="hh-cart-section-icon">

                                <i class="bi bi-bag-check"></i>

                            </div>


                            <div>

                                <span>
                                    SHOPPING CART
                                </span>

                                <h2>
                                    Your Items
                                </h2>

                                <p>

                                    <?= number_format(
                                        $totalItems
                                    ) ?>

                                    item<?= $totalItems !== 1
                                        ? 's'
                                        : '' ?>

                                    from

                                    <?= number_format(
                                        count($vendors)
                                    ) ?>

                                    seller<?= count($vendors) !== 1
                                        ? 's'
                                        : '' ?>.

                                </p>

                            </div>


                        </div>


                    </div>



                    <!-- =============================================
                         VENDORS
                    ============================================== -->

                    <?php foreach (
                        $vendors
                        as $vendor
                    ): ?>


                        <?php

                        $vendorLogo =
                            cartVendorLogo(
                                $vendor['business_logo']
                            );

                        ?>


                        <article class="hh-cart-vendor">


                            <!-- =====================================
                                 VENDOR HEADER
                            ====================================== -->

                            <div class="hh-cart-vendor-header">


                                <div class="hh-cart-vendor-info">


                                    <div class="hh-cart-vendor-logo">


                                        <?php if (
                                            $vendorLogo !== ''
                                        ): ?>


                                            <img
                                                src="<?= cartEscape(
                                                    $vendorLogo
                                                ) ?>"
                                                alt="<?= cartEscape(
                                                    $vendor['business_name']
                                                ) ?>"
                                                onerror="
                                                    this.style.display='none';
                                                    this.parentElement.innerHTML='<i class=&quot;bi bi-shop&quot;></i>';
                                                "
                                            >


                                        <?php else: ?>


                                            <i class="bi bi-shop"></i>


                                        <?php endif; ?>


                                    </div>


                                    <div>


                                        <span>
                                            SELLER
                                        </span>


                                        <strong>

                                            <?= cartEscape(
                                                $vendor['business_name']
                                            ) ?>

                                        </strong>


                                    </div>


                                </div>


                                <a
                                    href="vendor.php?id=<?= (int)
                                        $vendor['vendor_id'] ?>"
                                    class="hh-cart-view-store"
                                >

                                    View Store

                                    <i class="bi bi-arrow-right"></i>

                                </a>


                            </div>



                            <!-- =====================================
                                 PRODUCTS
                            ====================================== -->

                            <div class="hh-cart-product-list">


                                <?php foreach (
                                    $vendor['items']
                                    as $item
                                ): ?>


                                    <?php

                                    $quantity =
                                        (int) $item['quantity'];


                                    $price =
                                        (float) $item['price'];


                                    $itemSubtotal =
                                        $price *
                                        $quantity;


                                    $stockQuantity =
                                        (int) $item['stock_quantity'];


                                    $productStatus =
                                        (string) $item['status'];


                                    $productAvailable =
                                        $productStatus === 'Available' &&
                                        $stockQuantity > 0;


                                    $productImage =
                                        cartProductImage(
                                            $item['image']
                                        );

                                    ?>


                                    <div
                                        class="
                                            hh-cart-product
                                            <?= !$productAvailable
                                                ? 'unavailable'
                                                : '' ?>
                                        "
                                    >


                                        <!-- IMAGE -->

                                        <a
                                            href="product_details.php?id=<?= (int)
                                                $item['product_id'] ?>"
                                            class="hh-cart-product-image"
                                        >


                                            <?php if (
                                                $productImage !== ''
                                            ): ?>


                                                <img
                                                    src="<?= cartEscape(
                                                        $productImage
                                                    ) ?>"
                                                    alt="<?= cartEscape(
                                                        $item['product_name']
                                                    ) ?>"
                                                    onerror="
                                                        this.style.display='none';
                                                        this.parentElement.innerHTML='<i class=&quot;bi bi-image&quot;></i>';
                                                    "
                                                >


                                            <?php else: ?>


                                                <i class="bi bi-image"></i>


                                            <?php endif; ?>


                                        </a>



                                        <!-- INFO -->

                                        <div class="hh-cart-product-info">


                                            <span class="hh-cart-category">

                                                <?= cartEscape(
                                                    $item['category_name']
                                                ) ?>

                                            </span>


                                            <h3>


                                                <a
                                                    href="product_details.php?id=<?= (int)
                                                        $item['product_id'] ?>"
                                                >

                                                    <?= cartEscape(
                                                        $item['product_name']
                                                    ) ?>

                                                </a>


                                            </h3>


                                            <div class="hh-cart-product-price">

                                                RM
                                                <?= number_format(
                                                    $price,
                                                    2
                                                ) ?>

                                            </div>



                                            <!-- STATUS -->

                                            <?php if (
                                                $productStatus === 'Hidden'
                                            ): ?>


                                                <div class="hh-product-warning">

                                                    <i class="bi bi-eye-slash"></i>

                                                    Product is no longer available.

                                                </div>


                                            <?php elseif (
                                                $productStatus !== 'Available'
                                            ): ?>


                                                <div class="hh-product-warning">

                                                    <i class="bi bi-x-circle"></i>

                                                    <?= cartEscape(
                                                        $productStatus
                                                    ) ?>

                                                </div>


                                            <?php elseif (
                                                $stockQuantity <= 0
                                            ): ?>


                                                <div class="hh-product-warning">

                                                    <i class="bi bi-x-circle"></i>

                                                    Out of stock.

                                                </div>


                                            <?php elseif (
                                                $quantity >
                                                $stockQuantity
                                            ): ?>


                                                <div class="hh-product-warning">

                                                    <i class="bi bi-exclamation-circle"></i>

                                                    Only
                                                    <?= $stockQuantity ?>
                                                    available.

                                                </div>


                                            <?php else: ?>


                                                <div class="hh-product-available">

                                                    <i class="bi bi-check-circle"></i>

                                                    In stock

                                                </div>


                                            <?php endif; ?>


                                        </div>



                                        <!-- QUANTITY -->

                                        <div class="hh-cart-product-quantity">


                                            <span>
                                                QUANTITY
                                            </span>


                                            <form
                                                method="POST"
                                                action="cart.php"
                                                class="hh-quantity-form"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="cart_id"
                                                    value="<?= (int)
                                                        $item['cart_id'] ?>"
                                                >


                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value="<?= $quantity ?>"
                                                    min="1"
                                                    max="<?= max(
                                                        1,
                                                        $stockQuantity
                                                    ) ?>"
                                                    <?= !$productAvailable
                                                        ? 'disabled'
                                                        : '' ?>
                                                >


                                                <button
                                                    type="submit"
                                                    name="update_cart"
                                                    <?= !$productAvailable
                                                        ? 'disabled'
                                                        : '' ?>
                                                >

                                                    Update

                                                </button>


                                            </form>


                                        </div>



                                        <!-- PRICE -->

                                        <div class="hh-cart-product-total">


                                            <span>
                                                SUBTOTAL
                                            </span>


                                            <strong>

                                                RM
                                                <?= number_format(
                                                    $itemSubtotal,
                                                    2
                                                ) ?>

                                            </strong>


                                        </div>



                                        <!-- REMOVE -->

                                        <form
                                            method="POST"
                                            action="cart.php"
                                            class="hh-cart-remove-form"
                                            onsubmit="
                                                return confirm(
                                                    'Remove this product from your cart?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="cart_id"
                                                value="<?= (int)
                                                    $item['cart_id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="remove_cart"
                                                class="hh-cart-remove"
                                                title="Remove item"
                                                aria-label="Remove item"
                                            >

                                                <i class="bi bi-trash3"></i>

                                            </button>


                                        </form>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                        </article>


                    <?php endforeach; ?>



                    <!-- =============================================
                         CONTINUE
                    ============================================== -->

                    <a
                        href="catalog.php"
                        class="hh-cart-continue"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Continue Shopping

                    </a>


                </div>



                <!-- =================================================
                     SUMMARY
                ================================================== -->

                <aside class="hh-cart-summary">


                    <div class="hh-cart-summary-card">


                        <div class="hh-summary-icon">

                            <i class="bi bi-receipt"></i>

                        </div>


                        <span class="hh-summary-label">

                            ORDER SUMMARY

                        </span>


                        <h2>

                            Your Total

                        </h2>


                        <p class="hh-summary-description">

                            Review your cart before moving
                            to checkout.

                        </p>



                        <!-- ROW -->

                        <div class="hh-summary-row">


                            <span>
                                Items
                            </span>


                            <strong>
                                <?= number_format(
                                    $totalItems
                                ) ?>
                            </strong>


                        </div>



                        <!-- ROW -->

                        <div class="hh-summary-row">


                            <span>
                                Sellers
                            </span>


                            <strong>
                                <?= number_format(
                                    count($vendors)
                                ) ?>
                            </strong>


                        </div>



                        <!-- ROW -->

                        <div class="hh-summary-row">


                            <span>
                                Subtotal
                            </span>


                            <strong>

                                RM
                                <?= number_format(
                                    $subtotal,
                                    2
                                ) ?>

                            </strong>


                        </div>



                        <!-- ROW -->

                        <div class="hh-summary-row">


                            <span>
                                Delivery
                            </span>


                            <strong class="delivery-text">

                                At checkout

                            </strong>


                        </div>



                        <div class="hh-summary-divider"></div>



                        <!-- TOTAL -->

                        <div class="hh-summary-total">


                            <div>

                                <span>
                                    ESTIMATED TOTAL
                                </span>

                                <small>
                                    Excluding delivery fee
                                </small>

                            </div>


                            <strong>

                                RM
                                <?= number_format(
                                    $total,
                                    2
                                ) ?>

                            </strong>


                        </div>



                        <!-- CHECKOUT -->

                        <?php if (
                            !$hasUnavailableItems
                        ): ?>


                            <a
                                href="checkout.php"
                                class="hh-checkout-button"
                            >

                                <span>

                                    <i class="bi bi-lock-fill"></i>

                                    Proceed to Checkout

                                </span>


                                <i class="bi bi-arrow-right"></i>

                            </a>


                        <?php else: ?>


                            <div class="hh-checkout-disabled">


                                <i class="bi bi-exclamation-triangle"></i>

                                Resolve unavailable items first

                            </div>


                        <?php endif; ?>



                        <!-- SECURE -->

                        <div class="hh-secure-row">


                            <i class="bi bi-shield-check"></i>


                            <div>

                                <strong>
                                    Secure checkout
                                </strong>

                                <span>
                                    Review before confirming
                                </span>

                            </div>


                        </div>


                    </div>



                    <!-- =============================================
                         MULTI VENDOR
                    ============================================== -->

                    <div class="hh-multi-vendor">


                        <div>

                            <i class="bi bi-shop-window"></i>

                        </div>


                        <section>

                            <strong>
                                Shopping from multiple sellers?
                            </strong>


                            <p>

                                Each HochipoHub vendor will
                                process their part of your
                                order independently.

                            </p>

                        </section>


                    </div>


                </aside>


            </section>


        <?php endif; ?>


    </div>


</main>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/footer.php';

?>


<script src="js/cart.js"></script>
<script src="js/script.js"></script>