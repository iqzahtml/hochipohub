<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - WISHLIST
|--------------------------------------------------------------------------
| File:
| wishlist.php
|
| Purpose:
| - Display customer's wishlist
| - Remove products from wishlist
| - Add product to cart
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

requireCustomer();

$db = getDB();

$userId =
    (int) getUserId();


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
        (int) ($_POST['product_id'] ?? 0);

    $csrfToken =
        $_POST['csrf_token'] ?? '';


    if (
        !validateCsrfToken(
            $csrfToken
        )
    ) {

        setFlashMessage(
            'error',
            'Invalid security token.'
        );

        redirect('wishlist.php');
    }


    if ($productId > 0) {

        $stmt = $db->prepare("
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


    redirect('wishlist.php');
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
        (int) ($_POST['product_id'] ?? 0);

    $csrfToken =
        $_POST['csrf_token'] ?? '';


    if (
        !validateCsrfToken(
            $csrfToken
        )
    ) {

        setFlashMessage(
            'error',
            'Invalid security token.'
        );

        redirect('wishlist.php');
    }


    /*
    |--------------------------------------------------------------------------
    | GET PRODUCT
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
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

        redirect('wishlist.php');
    }


    if (
        $product['status'] !== 'Available' ||
        (int) $product['stock_quantity'] <= 0
    ) {

        setFlashMessage(
            'error',
            'This product is currently out of stock.'
        );

        redirect('wishlist.php');
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING CART
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
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

            redirect('wishlist.php');
        }


        $stmt = $db->prepare("
            UPDATE cart

            SET quantity = ?

            WHERE cart_id = ?
        ");

        $stmt->execute([
            $newQuantity,
            (int) $cartItem['cart_id']
        ]);

    } else {

        $stmt = $db->prepare("
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

    redirect('wishlist.php');
}


/*
|--------------------------------------------------------------------------
| GET WISHLIST
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
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

    ORDER BY w.created_at DESC
");

$stmt->execute([
    $userId
]);

$wishlist =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


$pageTitle =
    'My Wishlist - ' . SITE_NAME;

$hideSiteMainWrapper = true;
$extraCSS = ['dashboard.css'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/customer_sidebar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <!-- HEADER -->

        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    MY COLLECTION
                </span>

                <h1>
                    Wishlist
                </h1>

                <p>
                    Products you saved for later.
                </p>

            </div>


            <div>

                <span class="stat-value">

                    <?= count($wishlist) ?>

                    item(s)

                </span>

            </div>

        </section>


        <!-- FLASH -->

        <?php

        $flash =
            getFlashMessage();

        if ($flash):

        ?>

            <div class="alert alert-<?= e(
                $flash['type']
            ) ?>">

                <?= e(
                    $flash['message']
                ) ?>

            </div>

        <?php endif; ?>


        <!-- WISHLIST -->

        <section class="dashboard-section">

            <?php if (
                empty($wishlist)
            ): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ♡
                    </div>

                    <h3>
                        Your wishlist is empty
                    </h3>

                    <p>
                        Save products you love and
                        come back to them later.
                    </p>

                    <a
                        href="catalog.php"
                        class="btn btn-primary"
                    >
                        Explore Products
                    </a>

                </div>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach (
                        $wishlist
                        as $item
                    ): ?>

                        <article
                            class="product-card"
                        >

                            <!-- IMAGE -->

                            <a
                                href="product_details.php?id=<?= (int)
                                    $item[
                                        'product_id'
                                    ] ?>"
                            >

                                <img
                                    src="<?= e(
                                        getProductImage(
                                            $item[
                                                'image'
                                            ]
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $item[
                                            'product_name'
                                        ]
                                    ) ?>"
                                >

                            </a>


                            <div
                                class="product-card-body"
                            >

                                <small>

                                    <?= e(
                                        $item[
                                            'category_name'
                                        ]
                                    ) ?>

                                </small>


                                <h3>

                                    <a
                                        href="product_details.php?id=<?= (int)
                                            $item[
                                                'product_id'
                                            ] ?>"
                                    >

                                        <?= e(
                                            $item[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </a>

                                </h3>


                                <p>

                                    <?= e(
                                        $item[
                                            'business_name'
                                        ]
                                    ) ?>

                                </p>


                                <strong>

                                    RM
                                    <?= number_format(
                                        (float)
                                        $item['price'],
                                        2
                                    ) ?>

                                </strong>


                                <div>

                                    <span class="status-badge status-<?= e(
                                        statusClass(
                                            getStockStatus(
                                                $item[
                                                    'stock_quantity'
                                                ]
                                            )
                                        )
                                    ) ?>">

                                        <?= e(
                                            getStockStatus(
                                                $item[
                                                    'stock_quantity'
                                                ]
                                            )
                                        ) ?>

                                    </span>

                                </div>


                                <!-- ACTIONS -->

                                <div
                                    class="product-actions"
                                >

                                    <?php if (
                                        $item['status']
                                        === 'Available' &&
                                        (int)
                                        $item[
                                            'stock_quantity'
                                        ] > 0
                                    ): ?>

                                        <form
                                            method="POST"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(
                                                    generateCsrfToken()
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)
                                                    $item[
                                                        'product_id'
                                                    ] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="add_to_cart"
                                                class="btn btn-primary"
                                            >
                                                Add to Cart
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            disabled
                                        >
                                            Out of Stock
                                        </button>

                                    <?php endif; ?>


                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
                                                generateCsrfToken()
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)
                                                $item[
                                                    'product_id'
                                                ] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="remove_wishlist"
                                            class="btn btn-danger"
                                        >
                                            Remove
                                        </button>

                                    </form>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>
