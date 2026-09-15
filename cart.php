<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CUSTOMER CART
|--------------------------------------------------------------------------
| File: cart.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';


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
| LOGIN
|--------------------------------------------------------------------------
*/

$userId =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


$userRole =
    strtolower(
        trim(
            (string) (
                $_SESSION['role']
                ?? $_SESSION['user_role']
                ?? ''
            )
        )
    );


if ($userId <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}


if ($userRole !== 'customer') {

    header(
        'Location: ' .
        BASE_URL .
        'dashboard.php'
    );

    exit;
}


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
        $image =
            trim(
                (string) $image
            );


        if ($image === '') {
            return '';
        }


        if (
            str_starts_with(
                $image,
                'http://'
            )
            ||
            str_starts_with(
                $image,
                'https://'
            )
        ) {
            return $image;
        }


        if (
            str_starts_with(
                $image,
                'uploads/'
            )
        ) {

            return
                BASE_URL .
                ltrim(
                    $image,
                    '/'
                );
        }


        return
            BASE_URL .
            'uploads/products/' .
            rawurlencode(
                basename(
                    $image
                )
            );
    }
}


if (!function_exists('cartVendorLogo')) {

    function cartVendorLogo($image): string
    {
        $image =
            trim(
                (string) $image
            );


        if ($image === '') {
            return '';
        }


        if (
            str_starts_with(
                $image,
                'http://'
            )
            ||
            str_starts_with(
                $image,
                'https://'
            )
        ) {
            return $image;
        }


        if (
            str_starts_with(
                $image,
                'uploads/'
            )
        ) {

            return
                BASE_URL .
                ltrim(
                    $image,
                    '/'
                );
        }


        return
            BASE_URL .
            'uploads/vendors/' .
            rawurlencode(
                basename(
                    $image
                )
            );
    }
}


/*
|--------------------------------------------------------------------------
| AJAX QUANTITY UPDATE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['ajax_quantity'])
) {

    header(
        'Content-Type: application/json; charset=UTF-8'
    );


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
        !$cartId
        ||
        $quantity === false
        ||
        $quantity < 1
    ) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' =>
                'Invalid cart quantity.'
        ]);

        exit;
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | GET CART PRODUCT
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    c.cart_id,
                    c.quantity,
                    p.product_id,
                    p.price,
                    p.stock_quantity,
                    p.status

                FROM cart c

                INNER JOIN products p
                    ON c.product_id =
                       p.product_id

                WHERE c.cart_id = ?

                AND c.customer_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $cartId,
            $userId
        ]);


        $cartRow =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$cartRow) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Cart item not found.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT STATUS
        |--------------------------------------------------------------------------
        */

        if (
            $cartRow['status'] !==
            'Available'
        ) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' =>
                    'This product is no longer available.'
            ]);

            exit;
        }


        $stockQuantity =
            (int)
            $cartRow['stock_quantity'];


        if ($stockQuantity <= 0) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' =>
                    'This product is out of stock.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | LIMIT QUANTITY
        |--------------------------------------------------------------------------
        */

        if (
            $quantity >
            $stockQuantity
        ) {

            $quantity =
                $stockQuantity;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | CART TOTALS
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT

                    COALESCE(
                        SUM(
                            c.quantity *
                            p.price
                        ),
                        0
                    ) AS subtotal,

                    COALESCE(
                        SUM(
                            c.quantity
                        ),
                        0
                    ) AS total_items

                FROM cart c

                INNER JOIN products p
                    ON c.product_id =
                       p.product_id

                WHERE c.customer_id = ?
            ");


        $stmt->execute([
            $userId
        ]);


        $totals =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        $itemSubtotal =
            (float)
            $cartRow['price'] *
            $quantity;


        $cartSubtotal =
            (float) (
                $totals['subtotal']
                ?? 0
            );


        $cartCount =
            (int) (
                $totals['total_items']
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        echo json_encode([
            'success' =>
                true,

            'message' =>
                'Quantity saved.',

            'quantity' =>
                $quantity,

            'stock_quantity' =>
                $stockQuantity,

            'item_subtotal' =>
                round(
                    $itemSubtotal,
                    2
                ),

            'cart_subtotal' =>
                round(
                    $cartSubtotal,
                    2
                ),

            'cart_total' =>
                round(
                    $cartSubtotal,
                    2
                ),

            'cart_count' =>
                $cartCount
        ]);

        exit;


    } catch (Throwable $e) {

        http_response_code(500);

        echo json_encode([
            'success' =>
                false,

            'message' =>
                'Unable to update cart.'
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| REMOVE CART ITEM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
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
| GET CART
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
            ON c.product_id =
               p.product_id

        INNER JOIN vendors v
            ON p.vendor_id =
               v.vendor_id

        INNER JOIN categories cat
            ON p.category_id =
               cat.category_id

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


foreach (
    $cartItems
    as $item
) {

    $quantity =
        (int)
        $item['quantity'];


    $price =
        (float)
        $item['price'];


    $subtotal +=
        $price *
        $quantity;


    $totalItems +=
        $quantity;


    if (
        $item['status'] !==
        'Available'
        ||
        (int)
        $item['stock_quantity']
        <= 0
        ||
        $quantity >
        (int)
        $item['stock_quantity']
    ) {

        $hasUnavailableItems =
            true;
    }
}


$total =
    $subtotal;


/*
|--------------------------------------------------------------------------
| NAV COUNTS
|--------------------------------------------------------------------------
*/

$cartCount =
    $totalItems;


$wishlistCount = 0;


try {

    $stmt =
        $db->prepare("
            SELECT COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");


    $stmt->execute([
        $userId
    ]);


    $wishlistCount =
        (int)
        $stmt->fetchColumn();


} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| GROUP BY VENDOR
|--------------------------------------------------------------------------
*/

$vendors = [];


foreach (
    $cartItems
    as $item
) {

    $vendorId =
        (int)
        $item['vendor_id'];


    if (
        !isset(
            $vendors[$vendorId]
        )
    ) {

        $vendors[$vendorId] = [

            'vendor_id' =>
                $vendorId,

            'business_name' =>
                $item[
                    'business_name'
                ],

            'business_logo' =>
                $item[
                    'business_logo'
                ],

            'items' =>
                []
        ];
    }


    $vendors[$vendorId]['items'][] =
        $item;
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'My Cart';


$hideSiteMainWrapper =
    true;


$extraCSS = [
    'cart.css',
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

/* ================================================================
   SMART QUANTITY
================================================================ */

.hh-smart-quantity {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
}


.hh-smart-quantity-label {
    color: #8a98ad;

    font-size: 7px;
    font-weight: 900;

    letter-spacing: .8px;
}


.hh-quantity-stepper {
    position: relative;

    min-width: 158px;
    height: 50px;

    padding: 5px;

    display: grid;

    grid-template-columns:
        40px
        minmax(50px,1fr)
        40px;

    align-items: center;

    gap: 4px;

    background:
        linear-gradient(
            145deg,
            #f5f8ff,
            #eef4ff
        );

    border:
        1px solid
        #d8e5fb;

    border-radius: 16px;

    box-shadow:
        inset
        0 1px 0
        rgba(255,255,255,.9),

        0 7px 18px
        rgba(37,99,235,.06);

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        transform .2s ease;
}


.hh-quantity-stepper.updating {
    border-color:
        #93baf7;

    box-shadow:
        0
        0
        0
        4px
        rgba(37,99,235,.07);
}


.hh-quantity-btn {
    width: 40px;
    height: 40px;

    padding: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid
        #dce7f8;

    border-radius: 11px;

    color: #2563eb;

    background: #ffffff;

    font-family:
        Inter,
        Arial,
        sans-serif;

    font-size: 22px;
    font-weight: 700;

    line-height: 1;

    cursor: pointer;

    box-shadow:
        0
        4px
        10px
        rgba(30,70,150,.06);

    transition:
        .18s ease;
}


.hh-quantity-btn:hover:not(:disabled) {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #397bf0
        );

    border-color:
        #2563eb;

    transform:
        translateY(-1px);

    box-shadow:
        0
        7px
        16px
        rgba(37,99,235,.20);
}


.hh-quantity-btn:active:not(:disabled) {
    transform:
        scale(.93);
}


.hh-quantity-btn:disabled {
    opacity: .35;
    cursor: not-allowed;

    box-shadow: none;
}


.hh-quantity-value {
    width: 100%;

    border: 0 !important;

    outline: 0 !important;

    padding: 0 !important;

    color: #16233c !important;

    background: transparent !important;

    box-shadow: none !important;

    text-align: center;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size: 14px !important;
    font-weight: 900 !important;

    appearance: textfield;
    -moz-appearance: textfield;
}


.hh-quantity-value::-webkit-outer-spin-button,
.hh-quantity-value::-webkit-inner-spin-button {
    margin: 0;

    -webkit-appearance: none;
}


.hh-quantity-meta {
    min-height: 15px;

    display: flex;

    align-items: center;

    gap: 5px;

    color: #7c8da3;

    font-size: 7px;
    font-weight: 700;
}


.hh-quantity-meta i {
    color: #16a34a;

    font-size: 8px;
}


.hh-quantity-meta.saving {
    color: #2563eb;
}


.hh-quantity-meta.saving i {
    color: #2563eb;

    animation:
        hhQuantitySpin
        .75s linear
        infinite;
}


.hh-quantity-meta.error {
    color: #dc2626;
}


.hh-quantity-meta.error i {
    color: #dc2626;

    animation: none;
}


@keyframes hhQuantitySpin {

    to {
        transform:
            rotate(360deg);
    }
}


/* ================================================================
   QUANTITY NUMBER ANIMATION
================================================================ */

.hh-quantity-value.bump {
    animation:
        hhQuantityBump
        .22s ease;
}


@keyframes hhQuantityBump {

    0% {
        transform:
            scale(1);
    }

    50% {
        transform:
            scale(1.18);
    }

    100% {
        transform:
            scale(1);
    }
}


/* ================================================================
   CART PRODUCT TRANSITION
================================================================ */

.hh-cart-product {
    transition:
        opacity .2s ease,
        transform .2s ease;
}


/* ================================================================
   MOBILE
================================================================ */

@media (max-width: 720px) {

    .hh-quantity-stepper {
        min-width: 150px;
    }
}

</style>


<main class="hh-cart-page">

    <div
        class="hh-cart-container"
        data-cart-container
    >


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

                    <span>
                        Ready When You Are.
                    </span>

                </h1>


                <p>

                    Review your favourite finds,
                    adjust quantities and continue
                    to secure checkout when
                    everything looks perfect.

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
                        !empty($cartItems)
                        &&
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

                        <strong data-cart-count-display>
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
             STATS
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

                    <strong data-cart-count-display>

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
                            count(
                                $vendors
                            )
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

                    <strong
                        data-cart-total
                    >

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
             REMOVE SUCCESS
        ======================================================== -->

        <?php if (
            isset($_GET['success'])
            &&
            $_GET['success'] ===
            'removed'
        ): ?>


            <div class="hh-cart-alert success">

                <i class="bi bi-check-circle-fill"></i>

                Item removed from your cart.

            </div>


        <?php endif; ?>



        <!-- =======================================================
             EMPTY
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

                        Explore products from
                        HochipoHub's local sellers
                        and add something you love.

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


        <?php else: ?>


            <?php if (
                $hasUnavailableItems
            ): ?>


                <div class="hh-cart-alert warning">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    <div>

                        <strong>
                            Some items need your attention.
                        </strong>

                        Remove unavailable items
                        before checkout.

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

                <div
                    class="hh-cart-items"
                    data-cart-list
                >


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

                                    <span data-cart-count-display>
                                        <?= number_format(
                                            $totalItems
                                        ) ?>
                                    </span>

                                    item<?= $totalItems !== 1
                                        ? 's'
                                        : '' ?>

                                    from

                                    <?= number_format(
                                        count(
                                            $vendors
                                        )
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
                                $vendor[
                                    'business_logo'
                                ]
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
                                                    $vendor[
                                                        'business_name'
                                                    ]
                                                ) ?>"
                                                onerror="
                                                    this.style.display='none';
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
                                                $vendor[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </strong>

                                    </div>


                                </div>



                                <a
                                    href="vendor.php?id=<?= (int)
                                        $vendor[
                                            'vendor_id'
                                        ] ?>"
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
                                        (int)
                                        $item[
                                            'quantity'
                                        ];


                                    $price =
                                        (float)
                                        $item[
                                            'price'
                                        ];


                                    $itemSubtotal =
                                        $price *
                                        $quantity;


                                    $stockQuantity =
                                        (int)
                                        $item[
                                            'stock_quantity'
                                        ];


                                    $productStatus =
                                        (string)
                                        $item[
                                            'status'
                                        ];


                                    $productAvailable =
                                        $productStatus ===
                                        'Available'
                                        &&
                                        $stockQuantity > 0;


                                    $productImage =
                                        cartProductImage(
                                            $item[
                                                'image'
                                            ]
                                        );

                                    ?>


                                    <div
                                        class="
                                            hh-cart-product
                                            <?= !$productAvailable
                                                ? 'unavailable'
                                                : '' ?>
                                        "
                                        data-cart-item
                                        data-cart-id="<?= (int)
                                            $item[
                                                'cart_id'
                                            ] ?>"
                                        data-item-price="<?= cartEscape(
                                            number_format(
                                                $price,
                                                2,
                                                '.',
                                                ''
                                            )
                                        ) ?>"
                                    >


                                        <!-- IMAGE -->

                                        <a
                                            href="product_details.php?id=<?= (int)
                                                $item[
                                                    'product_id'
                                                ] ?>"
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
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>"
                                                >


                                            <?php else: ?>


                                                <i class="bi bi-image"></i>


                                            <?php endif; ?>


                                        </a>



                                        <!-- PRODUCT INFO -->

                                        <div class="hh-cart-product-info">


                                            <span class="hh-cart-category">

                                                <?= cartEscape(
                                                    $item[
                                                        'category_name'
                                                    ]
                                                ) ?>

                                            </span>


                                            <h3>

                                                <a
                                                    href="product_details.php?id=<?= (int)
                                                        $item[
                                                            'product_id'
                                                        ] ?>"
                                                >

                                                    <?= cartEscape(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>

                                                </a>

                                            </h3>


                                            <div
                                                class="hh-cart-product-price cart-item-price"
                                                data-price="<?= cartEscape(
                                                    number_format(
                                                        $price,
                                                        2,
                                                        '.',
                                                        ''
                                                    )
                                                ) ?>"
                                            >

                                                RM
                                                <?= number_format(
                                                    $price,
                                                    2
                                                ) ?>

                                            </div>



                                            <?php if (
                                                !$productAvailable
                                            ): ?>


                                                <div class="hh-product-warning">

                                                    <i class="bi bi-x-circle"></i>

                                                    <?= cartEscape(
                                                        $productStatus
                                                    ) ?>

                                                </div>


                                            <?php elseif (
                                                $stockQuantity <= 5
                                            ): ?>


                                                <div class="hh-product-warning">

                                                    <i class="bi bi-exclamation-circle"></i>

                                                    Only
                                                    <?= $stockQuantity ?>
                                                    left.

                                                </div>


                                            <?php else: ?>


                                                <div class="hh-product-available">

                                                    <i class="bi bi-check-circle"></i>

                                                    In stock

                                                </div>


                                            <?php endif; ?>


                                        </div>



                                        <!-- =================================
                                             SMART QUANTITY
                                        ================================== -->

                                        <div class="hh-smart-quantity">


                                            <span class="hh-smart-quantity-label">

                                                QUANTITY

                                            </span>


                                            <div
                                                class="hh-quantity-stepper"
                                                data-quantity-stepper
                                            >


                                                <button
                                                    type="button"
                                                    class="hh-quantity-btn"
                                                    data-cart-decrease
                                                    aria-label="Decrease quantity"
                                                    <?= (
                                                        !$productAvailable
                                                        ||
                                                        $quantity <= 1
                                                    )
                                                        ? 'disabled'
                                                        : '' ?>
                                                >
                                                    −
                                                </button>


                                                <input
                                                    type="number"
                                                    class="hh-quantity-value"
                                                    data-cart-quantity
                                                    value="<?= $quantity ?>"
                                                    min="1"
                                                    max="<?= max(
                                                        1,
                                                        $stockQuantity
                                                    ) ?>"
                                                    inputmode="numeric"
                                                    aria-label="Product quantity"
                                                    <?= !$productAvailable
                                                        ? 'disabled'
                                                        : '' ?>
                                                >


                                                <button
                                                    type="button"
                                                    class="hh-quantity-btn"
                                                    data-cart-increase
                                                    aria-label="Increase quantity"
                                                    <?= (
                                                        !$productAvailable
                                                        ||
                                                        $quantity >=
                                                        $stockQuantity
                                                    )
                                                        ? 'disabled'
                                                        : '' ?>
                                                >
                                                    +
                                                </button>


                                            </div>


                                            <div
                                                class="hh-quantity-meta"
                                                data-quantity-status
                                            >

                                                <?php if (
                                                    $productAvailable
                                                ): ?>

                                                    <i class="bi bi-cloud-check"></i>

                                                    Auto saved

                                                <?php else: ?>

                                                    <i class="bi bi-exclamation-circle"></i>

                                                    Unavailable

                                                <?php endif; ?>

                                            </div>


                                        </div>



                                        <!-- SUBTOTAL -->

                                        <div class="hh-cart-product-total">

                                            <span>
                                                SUBTOTAL
                                            </span>

                                            <strong
                                                data-item-subtotal
                                            >

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
                                                    $item[
                                                        'cart_id'
                                                    ] ?>"
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

                            Your totals update automatically
                            whenever you change quantity.

                        </p>



                        <div class="hh-summary-row">

                            <span>
                                Items
                            </span>

                            <strong
                                data-cart-count-display
                            >

                                <?= number_format(
                                    $totalItems
                                ) ?>

                            </strong>

                        </div>



                        <div class="hh-summary-row">

                            <span>
                                Sellers
                            </span>

                            <strong>

                                <?= number_format(
                                    count(
                                        $vendors
                                    )
                                ) ?>

                            </strong>

                        </div>



                        <div class="hh-summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong
                                data-cart-subtotal
                            >

                                RM
                                <?= number_format(
                                    $subtotal,
                                    2
                                ) ?>

                            </strong>

                        </div>



                        <div class="hh-summary-row">

                            <span>
                                Delivery
                            </span>

                            <strong class="delivery-text">
                                At checkout
                            </strong>

                        </div>



                        <div class="hh-summary-divider"></div>



                        <div class="hh-summary-total">

                            <div>

                                <span>
                                    ESTIMATED TOTAL
                                </span>

                                <small>
                                    Excluding delivery fee
                                </small>

                            </div>


                            <strong
                                data-cart-total
                            >

                                RM
                                <?= number_format(
                                    $total,
                                    2
                                ) ?>

                            </strong>

                        </div>



                        <?php if (
                            !$hasUnavailableItems
                        ): ?>


                            <a
                                href="checkout.php"
                                class="hh-checkout-button"
                                data-cart-checkout
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



                    <div class="hh-multi-vendor">

                        <div>

                            <i class="bi bi-shop-window"></i>

                        </div>

                        <section>

                            <strong>
                                Shopping from multiple sellers?
                            </strong>

                            <p>

                                Each HochipoHub vendor
                                processes their part of
                                the order independently.

                            </p>

                        </section>

                    </div>


                </aside>


            </section>


        <?php endif; ?>


    </div>

</main>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>


<script src="js/cart.js"></script>