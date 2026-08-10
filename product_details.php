<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PRODUCT DETAILS
|--------------------------------------------------------------------------
| File:
| product_details.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

$product_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : (int) ($_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    header('Location: product.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.*,

        v.vendor_id,
        v.business_name,
        v.business_logo,
        v.business_description,

        c.category_id,
        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE p.product_id = ?

    LIMIT 1
");

$stmt->execute([$product_id]);

$product = $stmt->fetch();


if (!$product) {
    header('Location: product.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

$productImage = !empty($product['image'])
    ? 'uploads/products/' . basename($product['image'])
    : 'image/logo.jpg';


/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        COUNT(*) AS review_count,
        COALESCE(
            AVG(rating),
            0
        ) AS average_rating
    FROM reviews
    WHERE product_id = ?
    AND status = 'Visible'
");

$stmt->execute([$product_id]);

$reviewSummary =
    $stmt->fetch();


/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        r.*,
        u.name AS customer_name
    FROM reviews r

    INNER JOIN users u
        ON r.customer_id = u.user_id

    WHERE r.product_id = ?
    AND r.status = 'Visible'

    ORDER BY r.review_date DESC
");

$stmt->execute([$product_id]);

$reviews =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$user_id =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


/*
|--------------------------------------------------------------------------
| CART / WISHLIST STATUS
|--------------------------------------------------------------------------
*/

$inCart = false;
$inWishlist = false;
$cartQuantity = 0;


if ($user_id > 0) {

    $stmt = $db->prepare("
        SELECT quantity
        FROM cart
        WHERE customer_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $user_id,
        $product_id
    ]);

    $cartRow = $stmt->fetch();

    if ($cartRow) {

        $inCart = true;

        $cartQuantity =
            (int) $cartRow['quantity'];
    }


    $stmt = $db->prepare("
        SELECT wishlist_id
        FROM wishlist
        WHERE user_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $user_id,
        $product_id
    ]);

    $inWishlist =
        (bool) $stmt->fetch();

}


$pageTitle =
    $product['product_name'] .
    ' - ' .
    SITE_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="product-details-page">

    <div class="dashboard-container">


        <section class="product-detail-wrapper">


            <div class="product-detail-image">

                <img
                    src="<?= e($productImage) ?>"
                    alt="<?= e($product['product_name']) ?>"
                >

            </div>


            <div class="product-detail-info">

                <span class="small-label">

                    <?= e(
                        $product['category_name']
                    ) ?>

                </span>


                <h1>
                    <?= e(
                        $product['product_name']
                    ) ?>
                </h1>


                <div class="product-rating">

                    ⭐

                    <?= number_format(
                        (float)
                        $reviewSummary[
                            'average_rating'
                        ],
                        1
                    ) ?>

                    /

                    5

                    <span>
                        (
                        <?= (int)
                            $reviewSummary[
                                'review_count'
                            ] ?>

                        reviews)
                    </span>

                </div>


                <div class="product-price">

                    RM

                    <?= number_format(
                        (float)
                        $product['price'],
                        2
                    ) ?>

                </div>


                <p class="product-description">

                    <?= nl2br(
                        e(
                            $product[
                                'description'
                            ] ?? ''
                        )
                    ) ?>

                </p>


                <div class="product-stock">

                    <?php if (
                        (int)
                        $product[
                            'stock_quantity'
                        ] > 0
                    ): ?>

                        <span class="status-badge status-success">

                            In Stock:
                            <?= (int)
                                $product[
                                    'stock_quantity'
                                ] ?>

                        </span>

                    <?php else: ?>

                        <span class="status-badge status-danger">
                            Out of Stock
                        </span>

                    <?php endif; ?>

                </div>


                <div class="product-vendor">

                    <strong>
                        Sold by
                    </strong>

                    <p>
                        <?= e(
                            $product[
                                'business_name'
                            ]
                        ) ?>
                    </p>

                </div>


                <?php if (
                    (int)
                    $product[
                        'stock_quantity'
                    ] > 0
                ): ?>


                    <?php if ($user_id > 0): ?>

                        <form
                            action="ajax/add_cart.php"
                            method="POST"
                            class="product-cart-form"
                        >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= $product_id ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(
                                    csrfToken()
                                ) ?>"
                            >


                            <label>
                                Quantity
                            </label>


                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                max="<?= (int)
                                    $product[
                                        'stock_quantity'
                                    ] ?>"
                                value="1"
                                required
                            >


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <?= $inCart
                                    ? 'Add More to Cart'
                                    : 'Add to Cart' ?>
                            </button>

                        </form>


                        <form
                            action="ajax/add_wishlist.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= $product_id ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(
                                    csrfToken()
                                ) ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-secondary"
                            >

                                <?= $inWishlist
                                    ? '♥ In Wishlist'
                                    : '♡ Add to Wishlist' ?>

                            </button>

                        </form>


                    <?php else: ?>

                        <a
                            href="index.php"
                            class="btn btn-primary"
                        >
                            Login to Purchase
                        </a>

                    <?php endif; ?>


                <?php endif; ?>


            </div>

        </section>


        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        CUSTOMER FEEDBACK
                    </span>

                    <h2>
                        Reviews
                    </h2>

                </div>

            </div>


            <?php if (empty($reviews)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ⭐
                    </div>

                    <h3>
                        No reviews yet
                    </h3>

                    <p>
                        Be the first customer to review this product.
                    </p>

                </div>


            <?php else: ?>


                <div class="reviews-list">

                    <?php foreach ($reviews as $review): ?>

                        <article class="review-card">

                            <div class="review-header">

                                <strong>
                                    <?= e(
                                        $review[
                                            'customer_name'
                                        ]
                                    ) ?>
                                </strong>

                                <span>

                                    <?= str_repeat(
                                        '⭐',
                                        (int)
                                        $review['rating']
                                    ) ?>

                                </span>

                            </div>


                            <?php if (
                                !empty(
                                    $review['review']
                                )
                            ): ?>

                                <p>
                                    <?= nl2br(
                                        e(
                                            $review[
                                                'review'
                                            ]
                                        )
                                    ) ?>
                                </p>

                            <?php endif; ?>


                            <small>

                                <?= e(
                                    date(
                                        'd M Y',
                                        strtotime(
                                            $review[
                                                'review_date'
                                            ]
                                        )
                                    )
                                ) ?>

                            </small>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


        </section>


    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>