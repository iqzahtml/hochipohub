<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CUSTOMER WISHLIST
|--------------------------------------------------------------------------
| File:
| wishlist.php
|--------------------------------------------------------------------------
|
| Purpose:
| - Display customer's saved products
| - Remove products from wishlist
| - Add products to cart
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
| CUSTOMER LOGIN
|--------------------------------------------------------------------------
*/

requireCustomer();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

$userId =
    (int) getUserId();


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('wishlistEscape')) {

    function wishlistEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('wishlistImage')) {

    function wishlistImage($image): string
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
            ) ||
            str_starts_with(
                $image,
                'https://'
            ) ||
            str_starts_with(
                $image,
                'uploads/'
            )
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


if (!function_exists('wishlistStockClass')) {

    function wishlistStockClass(
        $status,
        $stock
    ): string {

        $status =
            trim(
                (string) $status
            );


        $stock =
            (int) $stock;


        if (
            $status !== 'Available' ||
            $stock <= 0
        ) {

            return 'out';
        }


        if ($stock <= 5) {

            return 'low';
        }


        return 'available';
    }
}


if (!function_exists('wishlistStockText')) {

    function wishlistStockText(
        $status,
        $stock
    ): string {

        $status =
            trim(
                (string) $status
            );


        $stock =
            (int) $stock;


        if ($status === 'Hidden') {

            return 'Unavailable';
        }


        if (
            $status !== 'Available' ||
            $stock <= 0
        ) {

            return 'Out of Stock';
        }


        if ($stock <= 5) {

            return 'Only ' .
                $stock .
                ' left';
        }


        return 'In Stock';
    }
}


/*
|--------------------------------------------------------------------------
| REMOVE FROM WISHLIST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_wishlist'])
) {

    $productId =
        (int) (
            $_POST['product_id']
            ?? 0
        );


    $csrfToken =
        $_POST['csrf_token']
        ?? '';


    if (
        !validateCsrfToken(
            $csrfToken
        )
    ) {

        setFlashMessage(
            'error',
            'Invalid security token.'
        );


        redirect(
            'wishlist.php'
        );
    }


    if ($productId > 0) {

        $stmt =
            $db->prepare("
                DELETE FROM wishlist

                WHERE user_id = ?

                AND product_id = ?
            ");


        $stmt->execute([
            $userId,
            $productId
        ]);


        setFlashMessage(
            'success',
            'Product removed from wishlist.'
        );
    }


    redirect(
        'wishlist.php'
    );
}


/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_to_cart'])
) {

    $productId =
        (int) (
            $_POST['product_id']
            ?? 0
        );


    $csrfToken =
        $_POST['csrf_token']
        ?? '';


    if (
        !validateCsrfToken(
            $csrfToken
        )
    ) {

        setFlashMessage(
            'error',
            'Invalid security token.'
        );


        redirect(
            'wishlist.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET PRODUCT
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                product_id,
                stock_quantity,
                status

            FROM products

            WHERE product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $productId
    ]);


    $product =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$product) {

        setFlashMessage(
            'error',
            'Product not found.'
        );


        redirect(
            'wishlist.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AVAILABILITY
    |--------------------------------------------------------------------------
    */

    if (
        $product['status'] !== 'Available' ||
        (int) $product['stock_quantity'] <= 0
    ) {

        setFlashMessage(
            'error',
            'This product is currently unavailable.'
        );


        redirect(
            'wishlist.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK CART
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                cart_id,
                quantity

            FROM cart

            WHERE customer_id = ?

            AND product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $userId,
        $productId
    ]);


    $cartItem =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | UPDATE EXISTING CART
    |--------------------------------------------------------------------------
    */

    if ($cartItem) {

        $newQuantity =
            (int) $cartItem['quantity'] + 1;


        if (
            $newQuantity >
            (int) $product['stock_quantity']
        ) {

            setFlashMessage(
                'error',
                'You cannot add more than the available stock.'
            );


            redirect(
                'wishlist.php'
            );
        }


        $stmt =
            $db->prepare("
                UPDATE cart

                SET quantity = ?

                WHERE cart_id = ?
            ");


        $stmt->execute([
            $newQuantity,
            (int) $cartItem['cart_id']
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT CART
    |--------------------------------------------------------------------------
    */

    else {

        $stmt =
            $db->prepare("
                INSERT INTO cart
                (
                    customer_id,
                    product_id,
                    quantity
                )

                VALUES
                (
                    ?,
                    ?,
                    1
                )
            ");


        $stmt->execute([
            $userId,
            $productId
        ]);
    }


    setFlashMessage(
        'success',
        'Product added to your cart.'
    );


    redirect(
        'wishlist.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET WISHLIST
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            w.wishlist_id,
            w.created_at AS wishlist_date,

            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,

            v.vendor_id,
            v.business_name,

            c.category_name

        FROM wishlist w

        INNER JOIN products p
            ON w.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE w.user_id = ?

        ORDER BY
            w.created_at DESC
    ");


$stmt->execute([
    $userId
]);


$wishlist =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$wishlistCount =
    count(
        $wishlist
    );


$availableCount = 0;

$outOfStockCount = 0;


foreach ($wishlist as $item) {

    if (
        $item['status'] === 'Available' &&
        (int) $item['stock_quantity'] > 0
    ) {

        $availableCount++;

    } else {

        $outOfStockCount++;
    }
}


/*
|--------------------------------------------------------------------------
| CART COUNT FOR SHARED CUSTOMER NAV
|--------------------------------------------------------------------------
*/

$cartCount = 0;


try {

    $cartCountStmt =
        $db->prepare("
            SELECT

                COALESCE(
                    SUM(quantity),
                    0
                ) AS total

            FROM cart

            WHERE customer_id = ?
        ");


    $cartCountStmt->execute([
        $userId
    ]);


    $cartCountRow =
        $cartCountStmt->fetch(
            PDO::FETCH_ASSOC
        );


    $cartCount =
        (int) (
            $cartCountRow['total']
            ?? 0
        );

} catch (Throwable $e) {

    $cartCount = 0;
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'My Wishlist - ' .
    SITE_NAME;


$hideSiteMainWrapper =
    true;


$extraCSS = [
    'wishlist.css',
    'dashboard.css'
];


/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/header.php';


/*
|--------------------------------------------------------------------------
| SHARED CUSTOMER NAV
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/customer_sidebar.php';


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

$flash =
    getFlashMessage();

?>


<!-- ===============================================================
     WISHLIST PAGE
================================================================ -->

<main class="hh-wishlist-page">


    <div class="hh-wishlist-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-wishlist-hero">


            <div class="hh-wishlist-hero-copy">


                <span class="hh-wishlist-pill">

                    <i class="bi bi-heart-fill"></i>

                    SAVED FOR LATER

                </span>


                <h1>

                    Your Favourite
                    <span>Finds Live Here.</span>

                </h1>


                <p>

                    Keep track of products you love,
                    revisit them anytime and move them
                    straight into your cart when you're ready.

                </p>


                <div class="hh-wishlist-hero-actions">


                    <a
                        href="catalog.php"
                        class="hh-wishlist-primary"
                    >

                        <i class="bi bi-bag"></i>

                        Explore Products

                    </a>


                    <?php if ($cartCount > 0): ?>


                        <a
                            href="cart.php"
                            class="hh-wishlist-secondary"
                        >

                            <i class="bi bi-cart3"></i>

                            View Cart

                        </a>


                    <?php endif; ?>


                </div>


            </div>



            <!-- HERO VISUAL -->

            <div class="hh-wishlist-hero-visual">


                <div class="hh-wishlist-heart-main">

                    <i class="bi bi-heart-fill"></i>

                </div>


                <div class="hh-wishlist-float float-one">

                    <i class="bi bi-bag-heart"></i>

                    <span>

                        <?= number_format(
                            $wishlistCount
                        ) ?>

                        saved

                    </span>

                </div>


                <div class="hh-wishlist-float float-two">

                    <i class="bi bi-check-circle"></i>

                    <span>

                        <?= number_format(
                            $availableCount
                        ) ?>

                        available

                    </span>

                </div>


            </div>


        </section>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="hh-wishlist-stats">


            <article>


                <div class="hh-wishlist-stat-icon pink">

                    <i class="bi bi-heart"></i>

                </div>


                <div>

                    <span>
                        SAVED ITEMS
                    </span>

                    <strong>
                        <?= number_format(
                            $wishlistCount
                        ) ?>
                    </strong>

                </div>


            </article>



            <article>


                <div class="hh-wishlist-stat-icon green">

                    <i class="bi bi-check-circle"></i>

                </div>


                <div>

                    <span>
                        AVAILABLE
                    </span>

                    <strong>
                        <?= number_format(
                            $availableCount
                        ) ?>
                    </strong>

                </div>


            </article>



            <article>


                <div class="hh-wishlist-stat-icon orange">

                    <i class="bi bi-exclamation-circle"></i>

                </div>


                <div>

                    <span>
                        UNAVAILABLE
                    </span>

                    <strong>
                        <?= number_format(
                            $outOfStockCount
                        ) ?>
                    </strong>

                </div>


            </article>


        </section>



        <!-- =======================================================
             FLASH
        ======================================================== -->

        <?php if ($flash): ?>


            <div
                class="
                    hh-wishlist-alert
                    <?= wishlistEscape(
                        $flash['type']
                    ) ?>
                "
            >


                <?php if (
                    $flash['type'] === 'success'
                ): ?>


                    <i class="bi bi-check-circle-fill"></i>


                <?php else: ?>


                    <i class="bi bi-exclamation-circle-fill"></i>


                <?php endif; ?>


                <?= wishlistEscape(
                    $flash['message']
                ) ?>


            </div>


        <?php endif; ?>



        <!-- =======================================================
             EMPTY
        ======================================================== -->

        <?php if (empty($wishlist)): ?>


            <section class="hh-wishlist-empty">


                <div class="hh-wishlist-empty-art">


                    <div class="hh-wishlist-empty-heart">

                        <i class="bi bi-heart"></i>

                    </div>


                    <div class="hh-wishlist-mini mini-one">

                        <i class="bi bi-stars"></i>

                    </div>


                    <div class="hh-wishlist-mini mini-two">

                        <i class="bi bi-bag"></i>

                    </div>


                </div>


                <div class="hh-wishlist-empty-copy">


                    <span>
                        BUILD YOUR COLLECTION
                    </span>


                    <h2>

                        Nothing saved yet —
                        find something you love.

                    </h2>


                    <p>

                        Tap the wishlist button on any product
                        and it'll be saved here for you to
                        revisit later.

                    </p>


                    <div class="hh-wishlist-empty-actions">


                        <a
                            href="catalog.php"
                            class="hh-wishlist-empty-primary"
                        >

                            <i class="bi bi-bag"></i>

                            Browse Products

                        </a>


                        <a
                            href="vendor.php"
                            class="hh-wishlist-empty-secondary"
                        >

                            <i class="bi bi-shop"></i>

                            Explore Vendors

                        </a>


                    </div>


                </div>


            </section>



            <!-- ===================================================
                 BENEFITS
            ==================================================== -->

            <section class="hh-wishlist-benefits">


                <article>


                    <div>

                        <i class="bi bi-heart"></i>

                    </div>


                    <span>
                        SAVE IT
                    </span>


                    <h3>
                        Keep products you love
                    </h3>


                    <p>

                        Save interesting products without
                        needing to add them to your cart immediately.

                    </p>


                </article>



                <article>


                    <div>

                        <i class="bi bi-arrow-repeat"></i>

                    </div>


                    <span>
                        COME BACK
                    </span>


                    <h3>
                        Revisit products anytime
                    </h3>


                    <p>

                        Your wishlist keeps your favourites
                        organised in one easy place.

                    </p>


                </article>



                <article>


                    <div>

                        <i class="bi bi-cart-plus"></i>

                    </div>


                    <span>
                        READY TO BUY
                    </span>


                    <h3>
                        Move favourites to cart
                    </h3>


                    <p>

                        Add available products directly
                        to your shopping cart.

                    </p>


                </article>


            </section>


        <?php else: ?>


            <!-- ===================================================
                 WISHLIST PANEL
            ==================================================== -->

            <section class="hh-wishlist-panel">


                <!-- PANEL HEADER -->

                <div class="hh-wishlist-panel-header">


                    <div class="hh-wishlist-panel-title">


                        <div class="hh-wishlist-panel-icon">

                            <i class="bi bi-heart-fill"></i>

                        </div>


                        <div>

                            <span>
                                YOUR COLLECTION
                            </span>

                            <h2>
                                Saved Products
                            </h2>

                            <p>

                                Products you've saved
                                to revisit later.

                            </p>

                        </div>


                    </div>


                    <span class="hh-wishlist-count">

                        <?= number_format(
                            $wishlistCount
                        ) ?>

                        item<?= $wishlistCount !== 1
                            ? 's'
                            : '' ?>

                    </span>


                </div>



                <!-- PRODUCT GRID -->

                <div class="hh-wishlist-grid">


                    <?php foreach (
                        $wishlist
                        as $item
                    ): ?>


                        <?php

                        $productImage =
                            wishlistImage(
                                $item['image']
                            );


                        $stockClass =
                            wishlistStockClass(
                                $item['status'],
                                $item['stock_quantity']
                            );


                        $stockText =
                            wishlistStockText(
                                $item['status'],
                                $item['stock_quantity']
                            );


                        $canBuy =
                            $item['status'] === 'Available' &&
                            (int) $item['stock_quantity'] > 0;

                        ?>


                        <article
                            class="
                                hh-wishlist-card
                                <?= !$canBuy
                                    ? 'unavailable'
                                    : '' ?>
                            "
                        >


                            <!-- IMAGE -->

                            <div class="hh-wishlist-image-wrap">


                                <a
                                    href="product_details.php?id=<?= (int)
                                        $item['product_id'] ?>"
                                    class="hh-wishlist-image-link"
                                >


                                    <?php if (
                                        $productImage !== ''
                                    ): ?>


                                        <img
                                            src="<?= wishlistEscape(
                                                $productImage
                                            ) ?>"
                                            alt="<?= wishlistEscape(
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



                                <!-- CATEGORY -->

                                <span class="hh-wishlist-image-badge">

                                    <?= wishlistEscape(
                                        $item['category_name']
                                    ) ?>

                                </span>



                                <!-- REMOVE HEART -->

                                <form
                                    method="POST"
                                    class="hh-wishlist-heart-form"
                                    onsubmit="
                                        return confirm(
                                            'Remove this product from your wishlist?'
                                        );
                                    "
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= wishlistEscape(
                                            generateCsrfToken()
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int)
                                            $item['product_id'] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="remove_wishlist"
                                        class="hh-wishlist-heart-button"
                                        title="Remove from wishlist"
                                        aria-label="Remove from wishlist"
                                    >

                                        <i class="bi bi-heart-fill"></i>

                                    </button>


                                </form>


                            </div>



                            <!-- CONTENT -->

                            <div class="hh-wishlist-card-body">


                                <div class="hh-wishlist-vendor">


                                    <i class="bi bi-shop"></i>


                                    <?= wishlistEscape(
                                        $item['business_name']
                                    ) ?>


                                </div>


                                <h3>


                                    <a
                                        href="product_details.php?id=<?= (int)
                                            $item['product_id'] ?>"
                                    >

                                        <?= wishlistEscape(
                                            $item['product_name']
                                        ) ?>

                                    </a>


                                </h3>



                                <!-- META -->

                                <div class="hh-wishlist-card-meta">


                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float) $item['price'],
                                            2
                                        ) ?>

                                    </strong>


                                    <span
                                        class="
                                            hh-wishlist-stock
                                            <?= wishlistEscape(
                                                $stockClass
                                            ) ?>
                                        "
                                    >

                                        <i class="bi bi-circle-fill"></i>

                                        <?= wishlistEscape(
                                            $stockText
                                        ) ?>

                                    </span>


                                </div>



                                <!-- SAVED DATE -->

                                <?php if (
                                    !empty(
                                        $item['wishlist_date']
                                    )
                                ): ?>


                                    <div class="hh-wishlist-saved-date">

                                        <i class="bi bi-bookmark-heart"></i>

                                        Saved

                                        <?= wishlistEscape(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $item['wishlist_date']
                                                )
                                            )
                                        ) ?>

                                    </div>


                                <?php endif; ?>



                                <!-- ACTIONS -->

                                <div class="hh-wishlist-actions">


                                    <?php if ($canBuy): ?>


                                        <form method="POST">


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= wishlistEscape(
                                                    generateCsrfToken()
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)
                                                    $item['product_id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="add_to_cart"
                                                class="hh-wishlist-cart-button"
                                            >

                                                <i class="bi bi-cart-plus"></i>

                                                Add to Cart

                                            </button>


                                        </form>


                                    <?php else: ?>


                                        <button
                                            type="button"
                                            class="
                                                hh-wishlist-cart-button
                                                disabled
                                            "
                                            disabled
                                        >

                                            <i class="bi bi-x-circle"></i>

                                            Unavailable

                                        </button>


                                    <?php endif; ?>


                                    <a
                                        href="product_details.php?id=<?= (int)
                                            $item['product_id'] ?>"
                                        class="hh-wishlist-view-button"
                                    >

                                        View Product

                                        <i class="bi bi-arrow-right"></i>

                                    </a>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


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