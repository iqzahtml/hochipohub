<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId || $productId <= 0) {
    header('Location: ' . BASE_URL . 'product.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.product_id,
        p.vendor_id,
        p.category_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        p.created_at,
        p.updated_at,

        v.business_name,
        v.business_logo,
        v.business_description,
        v.business_address,
        v.delivery_method,
        v.approval_status,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE p.product_id = :product_id
    LIMIT 1
");

$stmt->execute([
    ':product_id' => $productId
]);

$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . BASE_URL . 'product.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

if (empty($product['image'])) {

    $productImage =
        BASE_URL . 'image/product/default-product.jpg';

} elseif (
    str_contains($product['image'], '/') ||
    str_contains($product['image'], '\\')
) {

    $productImage =
        BASE_URL .
        ltrim(
            str_replace(
                '\\',
                '/',
                $product['image']
            ),
            '/'
        );

} else {

    $productImage =
        PRODUCT_IMAGE_URL .
        rawurlencode($product['image']);
}

/*
|--------------------------------------------------------------------------
| VENDOR IMAGE
|--------------------------------------------------------------------------
*/

if (empty($product['business_logo'])) {

    $vendorImage =
        BASE_URL . 'image/vendors/default-vendor.jpg';

} elseif (
    str_contains($product['business_logo'], '/') ||
    str_contains($product['business_logo'], '\\')
) {

    $vendorImage =
        BASE_URL .
        ltrim(
            str_replace(
                '\\',
                '/',
                $product['business_logo']
            ),
            '/'
        );

} else {

    $vendorImage =
        VENDOR_IMAGE_URL .
        rawurlencode($product['business_logo']);
}

/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

$reviewStmt = $db->prepare("
    SELECT
        COUNT(*) AS total_reviews,
        COALESCE(AVG(rating), 0) AS average_rating
    FROM reviews
    WHERE product_id = :product_id
    AND status = 'Visible'
");

$reviewStmt->execute([
    ':product_id' => $productId
]);

$reviewSummary = $reviewStmt->fetch();

$totalReviews = (int) (
    $reviewSummary['total_reviews'] ?? 0
);

$averageRating = (float) (
    $reviewSummary['average_rating'] ?? 0
);

/*
|--------------------------------------------------------------------------
| GET REVIEWS
|--------------------------------------------------------------------------
*/

$reviewsStmt = $db->prepare("
    SELECT
        r.review_id,
        r.rating,
        r.review,
        r.image,
        r.review_date,

        u.user_id,
        u.name,
        u.profile_image

    FROM reviews r

    INNER JOIN users u
        ON r.customer_id = u.user_id

    WHERE r.product_id = :product_id
    AND r.status = 'Visible'

    ORDER BY r.review_date DESC
");

$reviewsStmt->execute([
    ':product_id' => $productId
]);

$reviews = $reviewsStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| LOGIN USER
|--------------------------------------------------------------------------
*/

$currentUserId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;

$currentUserRole = $_SESSION['role'] ?? '';

/*
|--------------------------------------------------------------------------
| WISHLIST STATUS
|--------------------------------------------------------------------------
*/

$isWishlisted = false;

if ($currentUserId > 0) {

    $wishlistStmt = $db->prepare("
        SELECT wishlist_id
        FROM wishlist
        WHERE user_id = :user_id
        AND product_id = :product_id
        LIMIT 1
    ");

    $wishlistStmt->execute([
        ':user_id' => $currentUserId,
        ':product_id' => $productId
    ]);

    $isWishlisted = (bool) $wishlistStmt->fetch();
}

/*
|--------------------------------------------------------------------------
| CART QUANTITY
|--------------------------------------------------------------------------
*/

$cartQuantity = 0;

if ($currentUserId > 0) {

    $cartStmt = $db->prepare("
        SELECT quantity
        FROM cart
        WHERE customer_id = :customer_id
        AND product_id = :product_id
        LIMIT 1
    ");

    $cartStmt->execute([
        ':customer_id' => $currentUserId,
        ':product_id' => $productId
    ]);

    $cartRow = $cartStmt->fetch();

    if ($cartRow) {
        $cartQuantity = (int) $cartRow['quantity'];
    }
}

/*
|--------------------------------------------------------------------------
| RELATED PRODUCTS
|--------------------------------------------------------------------------
*/

$relatedStmt = $db->prepare("
    SELECT
        p.product_id,
        p.product_name,
        p.price,
        p.image,
        p.stock_quantity,

        v.business_name,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE p.category_id = :category_id
    AND p.product_id != :product_id
    AND p.status = 'Available'

    ORDER BY p.created_at DESC

    LIMIT 4
");

$relatedStmt->execute([
    ':category_id' => (int) $product['category_id'],
    ':product_id' => $productId
]);

$relatedProducts = $relatedStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function detailsProductImage(?string $image): string
{
    if (empty($image)) {
        return BASE_URL . 'image/product/default-product.jpg';
    }

    if (
        str_contains($image, '/') ||
        str_contains($image, '\\')
    ) {
        return BASE_URL . ltrim(
            str_replace('\\', '/', $image),
            '/'
        );
    }

    return PRODUCT_IMAGE_URL . rawurlencode($image);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($product['product_name']) ?>
        | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/product.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .details-page {
            min-height: 100vh;
            padding: 40px 5%;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(37, 99, 235, 0.12),
                    transparent 35%
                ),
                #f8fbff;
        }

        .details-container {
            max-width: 1250px;
            margin: 0 auto;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .product-main {
            display: grid;
            grid-template-columns:
                minmax(0, 1.05fr)
                minmax(0, 0.95fr);
            gap: 40px;
            padding: 30px;
            border: 1px solid #dbeafe;
            border-radius: 30px;
            background: #ffffff;
            box-shadow:
                0 25px 60px rgba(15, 23, 42, 0.09);
        }

        .product-gallery {
            position: relative;
            overflow: hidden;
            min-height: 520px;
            border-radius: 24px;
            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );
        }

        .main-product-image {
            width: 100%;
            height: 100%;
            min-height: 520px;
            object-fit: cover;
        }

        .product-category-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.82);
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            backdrop-filter: blur(10px);
        }

        .details-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .details-label {
            margin-bottom: 10px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .details-title {
            margin: 0 0 15px;
            color: #0f172a;
            font-size: clamp(30px, 4vw, 48px);
            line-height: 1.05;
            font-weight: 950;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stars {
            color: #f59e0b;
            letter-spacing: 2px;
        }

        .rating-number {
            color: #0f172a;
            font-weight: 900;
        }

        .review-count {
            color: #64748b;
            font-size: 13px;
        }

        .details-price {
            margin-bottom: 20px;
            color: #1d4ed8;
            font-size: 34px;
            font-weight: 950;
        }

        .details-description {
            margin-bottom: 25px;
            color: #64748b;
            line-height: 1.8;
            font-size: 15px;
        }

        .stock-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 16px;
            margin-bottom: 22px;
            border-radius: 16px;
            background: #eff6ff;
        }

        .stock-label {
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .stock-value {
            color: #166534;
            font-weight: 950;
        }

        .stock-value.out {
            color: #b91c1c;
        }

        .quantity-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .quantity-label {
            color: #334155;
            font-size: 13px;
            font-weight: 900;
        }

        .quantity-input {
            width: 90px;
            padding: 12px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            outline: none;
            text-align: center;
            font-weight: 800;
        }

        .action-row {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .btn-primary,
        .btn-wishlist {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            padding: 0 20px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .btn-primary {
            flex: 1;
            border: none;
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            cursor: not-allowed;
            background: #94a3b8;
            transform: none;
        }

        .btn-wishlist {
            min-width: 55px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #2563eb;
        }

        .btn-wishlist.active {
            background: #2563eb;
            color: #ffffff;
        }

        .vendor-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #f8fafc;
        }

        .vendor-logo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dbeafe;
        }

        .vendor-info {
            flex: 1;
        }

        .vendor-name {
            margin: 0 0 4px;
            color: #0f172a;
            font-weight: 950;
        }

        .vendor-delivery {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .section {
            margin-top: 50px;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .section-heading h2 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 950;
        }

        .section-heading p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .reviews-grid {
            display: grid;
            gap: 15px;
        }

        .review-card {
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #ffffff;
        }

        .review-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 10px;
        }

        .review-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: #dbeafe;
        }

        .review-user-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .review-date {
            color: #94a3b8;
            font-size: 11px;
        }

        .review-stars {
            color: #f59e0b;
        }

        .review-text {
            margin: 0;
            color: #475569;
            line-height: 1.7;
            font-size: 14px;
        }

        .no-reviews {
            padding: 35px;
            border: 2px dashed #bfdbfe;
            border-radius: 18px;
            background: #f8fbff;
            color: #64748b;
            text-align: center;
        }

        .related-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .related-card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            transition: 0.2s ease;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow:
                0 18px 35px rgba(37, 99, 235, 0.12);
        }

        .related-image {
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: #eff6ff;
        }

        .related-content {
            padding: 15px;
        }

        .related-name {
            display: -webkit-box;
            overflow: hidden;
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .related-vendor {
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
        }

        .related-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .related-price {
            color: #2563eb;
            font-weight: 950;
        }

        .related-link {
            color: #2563eb;
            font-size: 11px;
            font-weight: 900;
            text-decoration: none;
        }

        .alert-message {
            display: none;
            padding: 13px 16px;
            margin-bottom: 15px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
        }

        @media (max-width: 900px) {

            .product-main {
                grid-template-columns: 1fr;
            }

            .product-gallery,
            .main-product-image {
                min-height: 400px;
            }

            .related-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {

            .details-page {
                padding: 25px 16px;
            }

            .product-main {
                padding: 15px;
                border-radius: 22px;
            }

            .product-gallery,
            .main-product-image {
                min-height: 330px;
            }

            .details-title {
                font-size: 32px;
            }

            .action-row {
                flex-direction: column;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="details-page">

    <div class="details-container">

        <div class="breadcrumb">

            <a href="<?= BASE_URL ?>product.php">
                Products
            </a>

            <span>›</span>

            <span>
                <?= e($product['category_name']) ?>
            </span>

            <span>›</span>

            <span>
                <?= e($product['product_name']) ?>
            </span>

        </div>


        <section class="product-main">

            <div class="product-gallery">

                <img
                    src="<?= e($productImage) ?>"
                    alt="<?= e($product['product_name']) ?>"
                    class="main-product-image"
                    onerror="this.src='<?= e(BASE_URL . 'image/product/default-product.jpg') ?>'"
                >

                <span class="product-category-badge">
                    <?= e($product['category_name']) ?>
                </span>

            </div>


            <div class="details-content">

                <div class="details-label">
                    HochipoHub Product
                </div>

                <h1 class="details-title">
                    <?= e($product['product_name']) ?>
                </h1>


                <div class="rating-row">

                    <span class="stars">

                        <?php
                        $roundedRating = (int) round(
                            $averageRating
                        );

                        for ($i = 1; $i <= 5; $i++):
                        ?>

                            <?= $i <= $roundedRating ? '★' : '☆' ?>

                        <?php endfor; ?>

                    </span>

                    <span class="rating-number">
                        <?= number_format(
                            $averageRating,
                            1
                        ) ?>
                    </span>

                    <span class="review-count">
                        (<?= $totalReviews ?> reviews)
                    </span>

                </div>


                <div class="details-price">
                    <?= formatPrice($product['price']) ?>
                </div>


                <p class="details-description">

                    <?= nl2br(
                        e(
                            $product['description']
                            ?: 'No product description available.'
                        )
                    ) ?>

                </p>


                <div class="stock-box">

                    <span class="stock-label">
                        Stock Availability
                    </span>

                    <?php if ((int) $product['stock_quantity'] > 0): ?>

                        <span class="stock-value">
                            <?= (int) $product['stock_quantity'] ?>
                            available
                        </span>

                    <?php else: ?>

                        <span class="stock-value out">
                            Out of Stock
                        </span>

                    <?php endif; ?>

                </div>


                <div
                    id="actionMessage"
                    class="alert-message"
                ></div>


                <?php if ((int) $product['stock_quantity'] > 0): ?>

                    <div class="quantity-row">

                        <span class="quantity-label">
                            Quantity
                        </span>

                        <input
                            type="number"
                            id="productQuantity"
                            class="quantity-input"
                            value="<?= max(
                                1,
                                min(
                                    $cartQuantity ?: 1,
                                    (int) $product['stock_quantity']
                                )
                            ) ?>"
                            min="1"
                            max="<?= (int) $product['stock_quantity'] ?>"
                        >

                    </div>


                    <div class="action-row">

                        <button
                            type="button"
                            class="btn-primary"
                            id="addCartButton"
                        >
                            🛒 Add to Cart
                        </button>

                        <button
                            type="button"
                            class="btn-wishlist <?= $isWishlisted ? 'active' : '' ?>"
                            id="wishlistButton"
                            title="Add to wishlist"
                        >
                            <?= $isWishlisted ? '♥' : '♡' ?>
                        </button>

                    </div>

                <?php else: ?>

                    <div class="action-row">

                        <button
                            type="button"
                            class="btn-primary"
                            disabled
                        >
                            Out of Stock
                        </button>

                        <button
                            type="button"
                            class="btn-wishlist <?= $isWishlisted ? 'active' : '' ?>"
                            id="wishlistButton"
                        >
                            <?= $isWishlisted ? '♥' : '♡' ?>
                        </button>

                    </div>

                <?php endif; ?>


                <div class="vendor-card">

                    <img
                        src="<?= e($vendorImage) ?>"
                        alt="<?= e($product['business_name']) ?>"
                        class="vendor-logo"
                        onerror="this.src='<?= e(BASE_URL . 'image/vendors/default-vendor.jpg') ?>'"
                    >

                    <div class="vendor-info">

                        <p class="vendor-name">
                            <?= e($product['business_name']) ?>
                        </p>

                        <p class="vendor-delivery">
                            Delivery:
                            <?= e($product['delivery_method']) ?>
                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- REVIEWS -->

        <section class="section">

            <div class="section-heading">

                <div>

                    <h2>
                        Customer Reviews
                    </h2>

                    <p>
                        Real feedback from HochipoHub customers.
                    </p>

                </div>

            </div>


            <div class="reviews-grid">

                <?php if (empty($reviews)): ?>

                    <div class="no-reviews">
                        No reviews yet for this product.
                    </div>

                <?php else: ?>

                    <?php foreach ($reviews as $review): ?>

                        <?php
                        $reviewAvatar = BASE_URL .
                            'image/vendors/default-vendor.jpg';

                        if (!empty($review['profile_image'])) {

                            if (
                                str_contains(
                                    $review['profile_image'],
                                    '/'
                                ) ||
                                str_contains(
                                    $review['profile_image'],
                                    '\\'
                                )
                            ) {

                                $reviewAvatar =
                                    BASE_URL .
                                    ltrim(
                                        str_replace(
                                            '\\',
                                            '/',
                                            $review['profile_image']
                                        ),
                                        '/'
                                    );

                            }
                        }
                        ?>

                        <article class="review-card">

                            <div class="review-top">

                                <div class="review-user">

                                    <img
                                        src="<?= e($reviewAvatar) ?>"
                                        alt="<?= e($review['name']) ?>"
                                        class="review-avatar"
                                    >

                                    <div>

                                        <div class="review-user-name">
                                            <?= e($review['name']) ?>
                                        </div>

                                        <div class="review-date">
                                            <?= date(
                                                'd M Y',
                                                strtotime(
                                                    $review['review_date']
                                                )
                                            ) ?>
                                        </div>

                                    </div>

                                </div>


                                <div class="review-stars">

                                    <?php for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ): ?>

                                        <?= $i <= (int) $review['rating']
                                            ? '★'
                                            : '☆' ?>

                                    <?php endfor; ?>

                                </div>

                            </div>


                            <?php if (!empty($review['review'])): ?>

                                <p class="review-text">
                                    <?= nl2br(
                                        e($review['review'])
                                    ) ?>
                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>


        <!-- RELATED PRODUCTS -->

        <?php if (!empty($relatedProducts)): ?>

            <section class="section">

                <div class="section-heading">

                    <div>

                        <h2>
                            You Might Also Like
                        </h2>

                        <p>
                            More products from the same category.
                        </p>

                    </div>

                </div>


                <div class="related-grid">

                    <?php foreach (
                        $relatedProducts
                        as $related
                    ): ?>

                        <article class="related-card">

                            <img
                                src="<?= e(
                                    detailsProductImage(
                                        $related['image']
                                    )
                                ) ?>"
                                alt="<?= e(
                                    $related['product_name']
                                ) ?>"
                                class="related-image"
                                loading="lazy"
                            >


                            <div class="related-content">

                                <h3 class="related-name">
                                    <?= e(
                                        $related['product_name']
                                    ) ?>
                                </h3>

                                <div class="related-vendor">
                                    <?= e(
                                        $related['business_name']
                                    ) ?>
                                </div>


                                <div class="related-bottom">

                                    <span class="related-price">
                                        <?= formatPrice(
                                            $related['price']
                                        ) ?>
                                    </span>

                                    <a
                                        href="<?= BASE_URL ?>product_details.php?id=<?= (int) $related['product_id'] ?>"
                                        class="related-link"
                                    >
                                        View →
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


<?php require_once __DIR__ . '/includes/footer.php'; ?>


<script>

const productId = <?= (int) $productId ?>;
const baseUrl = <?= json_encode(BASE_URL) ?>;

const addCartButton =
    document.getElementById('addCartButton');

const wishlistButton =
    document.getElementById('wishlistButton');

const quantityInput =
    document.getElementById('productQuantity');

const actionMessage =
    document.getElementById('actionMessage');


function showActionMessage(
    message,
    success = true
) {

    if (!actionMessage) {
        return;
    }

    actionMessage.textContent = message;

    actionMessage.style.display = 'block';

    actionMessage.style.background =
        success
            ? '#dcfce7'
            : '#fee2e2';

    actionMessage.style.color =
        success
            ? '#166534'
            : '#991b1b';
}


/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/

if (addCartButton) {

    addCartButton.addEventListener(
        'click',
        async function () {

            const quantity =
                quantityInput
                    ? parseInt(
                        quantityInput.value,
                        10
                    )
                    : 1;

            if (
                !quantity ||
                quantity < 1
            ) {

                showActionMessage(
                    'Please enter a valid quantity.',
                    false
                );

                return;
            }


            addCartButton.disabled = true;

            addCartButton.textContent =
                'Adding...';


            try {

                const response =
                    await fetch(
                        baseUrl + 'ajax/add_cart.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded'
                            },

                            body:
                                'product_id='
                                + encodeURIComponent(productId)
                                + '&quantity='
                                + encodeURIComponent(quantity)
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    showActionMessage(
                        data.message ||
                        'Product added to cart.',
                        true
                    );

                    addCartButton.textContent =
                        '✓ Added to Cart';

                } else {

                    showActionMessage(
                        data.message ||
                        'Unable to add product to cart.',
                        false
                    );

                    addCartButton.textContent =
                        '🛒 Add to Cart';
                }

            } catch (error) {

                showActionMessage(
                    'Something went wrong. Please try again.',
                    false
                );

                addCartButton.textContent =
                    '🛒 Add to Cart';

            }


            addCartButton.disabled = false;

        }
    );

}


/*
|--------------------------------------------------------------------------
| WISHLIST
|--------------------------------------------------------------------------
*/

if (wishlistButton) {

    wishlistButton.addEventListener(
        'click',
        async function () {

            wishlistButton.disabled = true;


            try {

                const response =
                    await fetch(
                        baseUrl + 'ajax/add_wishlist.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded'
                            },

                            body:
                                'product_id='
                                + encodeURIComponent(productId)
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    const added =
                        data.action === 'added'
                        || data.added === true;

                    wishlistButton.classList.toggle(
                        'active',
                        added
                    );

                    wishlistButton.textContent =
                        added
                            ? '♥'
                            : '♡';

                    showActionMessage(
                        data.message ||
                        (
                            added
                                ? 'Added to wishlist.'
                                : 'Removed from wishlist.'
                        ),
                        true
                    );

                } else {

                    showActionMessage(
                        data.message ||
                        'Unable to update wishlist.',
                        false
                    );

                }

            } catch (error) {

                showActionMessage(
                    'Please login to use wishlist.',
                    false
                );

            }


            wishlistButton.disabled = false;

        }
    );

}

</script>


<script src="<?= BASE_URL ?>js/script.js"></script>

</body>

</html>