<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

$userId = (int) $_SESSION['user_id'];

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| REMOVE WISHLIST ITEM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['remove_wishlist'])
) {

    $productId = (int) (
        $_POST['product_id'] ?? 0
    );

    if ($productId <= 0) {

        $errorMessage =
            'Invalid product.';

    } else {

        try {

            $removeStmt = $db->prepare("
                DELETE FROM wishlist
                WHERE user_id = :user_id
                AND product_id = :product_id
            ");

            $removeStmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId
            ]);

            if ($removeStmt->rowCount() > 0) {

                $successMessage =
                    'Product removed from your wishlist.';

            } else {

                $errorMessage =
                    'Product was not found in your wishlist.';
            }

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to remove wishlist item.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET WISHLIST
|--------------------------------------------------------------------------
*/

$wishlistItems = [];

$wishlistStmt = $db->prepare("
    SELECT
        w.wishlist_id,
        w.product_id,
        w.created_at AS wishlist_date,

        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,

        c.category_name,

        v.vendor_id,
        v.business_name

    FROM wishlist w

    INNER JOIN products p
        ON p.product_id = w.product_id

    INNER JOIN categories c
        ON c.category_id = p.category_id

    INNER JOIN vendors v
        ON v.vendor_id = p.vendor_id

    WHERE w.user_id = :user_id

    ORDER BY w.created_at DESC
");

$wishlistStmt->execute([
    ':user_id' => $userId
]);

$wishlistItems =
    $wishlistStmt->fetchAll();

$totalWishlist =
    count($wishlistItems);

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
        Wishlist | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/wishlist.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .wishlist-page {
            min-height: 100vh;
            padding: 45px 5%;
            background:
                radial-gradient(
                    circle at 8% 8%,
                    rgba(37,99,235,.14),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 92% 18%,
                    rgba(14,165,233,.10),
                    transparent 28%
                ),
                #f8fbff;
        }

        .wishlist-container {
            max-width: 1200px;
            margin: auto;
        }

        .wishlist-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .wishlist-kicker {
            display: block;
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .wishlist-header h1 {
            margin: 0 0 7px;
            color: #0f172a;
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 950;
        }

        .wishlist-header p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .wishlist-count {
            padding: 10px 15px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 950;
            white-space: nowrap;
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 17px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 800;
        }

        .alert.success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .alert.error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .wishlist-card {
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 22px;
            background: white;
            box-shadow:
                0 15px 40px rgba(15,23,42,.06);
            transition: .2s ease;
        }

        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow:
                0 22px 50px rgba(37,99,235,.13);
        }

        .wishlist-image-wrap {
            position: relative;
            height: 230px;
            background: #eff6ff;
        }

        .wishlist-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wishlist-heart {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,.95);
            color: #ef4444;
            font-size: 17px;
            box-shadow:
                0 5px 15px rgba(15,23,42,.1);
        }

        .wishlist-category {
            position: absolute;
            left: 12px;
            bottom: 12px;
            padding: 6px 9px;
            border-radius: 999px;
            background: rgba(15,23,42,.78);
            color: white;
            font-size: 9px;
            font-weight: 900;
        }

        .wishlist-content {
            padding: 18px;
        }

        .wishlist-content h2 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .wishlist-vendor {
            margin-bottom: 10px;
            color: #64748b;
            font-size: 10px;
        }

        .wishlist-vendor a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 900;
        }

        .wishlist-description {
            display: -webkit-box;
            height: 37px;
            margin-bottom: 15px;
            overflow: hidden;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.7;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .wishlist-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 15px;
        }

        .wishlist-price {
            color: #1d4ed8;
            font-size: 19px;
            font-weight: 950;
        }

        .stock-status {
            font-size: 9px;
            font-weight: 900;
        }

        .stock-status.available {
            color: #16a34a;
        }

        .stock-status.out {
            color: #dc2626;
        }

        .wishlist-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .wishlist-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 11px;
            border: none;
            border-radius: 11px;
            text-decoration: none;
            font-size: 10px;
            font-weight: 950;
            cursor: pointer;
        }

        .view-btn {
            background: #2563eb;
            color: white;
        }

        .remove-btn {
            background: #fee2e2;
            color: #b91c1c;
        }

        .empty-wishlist {
            padding: 80px 25px;
            border: 1px dashed #bfdbfe;
            border-radius: 25px;
            background: white;
            text-align: center;
        }

        .empty-wishlist-icon {
            margin-bottom: 15px;
            font-size: 50px;
        }

        .empty-wishlist h2 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 23px;
            font-weight: 950;
        }

        .empty-wishlist p {
            max-width: 450px;
            margin: 0 auto 20px;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.7;
        }

        .browse-btn {
            display: inline-flex;
            padding: 12px 18px;
            border-radius: 12px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            font-size: 11px;
            font-weight: 950;
        }

        @media (max-width: 950px) {

            .wishlist-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 650px) {

            .wishlist-page {
                padding: 30px 16px;
            }

            .wishlist-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="wishlist-page">

    <div class="wishlist-container">

        <header class="wishlist-header">

            <div>

                <span class="wishlist-kicker">
                    Your Collection
                </span>

                <h1>
                    Wishlist
                </h1>

                <p>
                    Products you don't want to lose track of.
                </p>

            </div>

            <span class="wishlist-count">
                <?= $totalWishlist ?>
                saved
            </span>

        </header>


        <?php if (
            $successMessage !== ''
        ): ?>

            <div class="alert success">
                ✓ <?= e($successMessage) ?>
            </div>

        <?php endif; ?>


        <?php if (
            $errorMessage !== ''
        ): ?>

            <div class="alert error">
                ⚠ <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>


        <?php if (
            empty($wishlistItems)
        ): ?>

            <section class="empty-wishlist">

                <div class="empty-wishlist-icon">
                    ♡
                </div>

                <h2>
                    Your wishlist is empty
                </h2>

                <p>
                    Found something you love?
                    Save it here and come back to it later.
                </p>

                <a
                    href="<?= BASE_URL ?>catalog.php"
                    class="browse-btn"
                >
                    Explore Products
                </a>

            </section>

        <?php else: ?>

            <section class="wishlist-grid">

                <?php foreach (
                    $wishlistItems
                    as $item
                ): ?>

                    <?php

                    $productImage =
                        productImageUrl(
                            $item['image']
                        );

                    $isAvailable =
                        $item['status'] === 'Available'
                        &&
                        (int) $item['stock_quantity'] > 0;

                    ?>

                    <article class="wishlist-card">

                        <div class="wishlist-image-wrap">

                            <img
                                src="<?= e($productImage) ?>"
                                alt="<?= e(
                                    $item['product_name']
                                ) ?>"
                                class="wishlist-image"
                                onerror="this.src='<?= e(
                                    BASE_URL .
                                    'image/product/default-product.jpg'
                                ) ?>'"
                            >

                            <span class="wishlist-heart">
                                ♥
                            </span>

                            <span
                                class="wishlist-category"
                            >
                                <?= e(
                                    $item['category_name']
                                ) ?>
                            </span>

                        </div>


                        <div class="wishlist-content">

                            <h2>
                                <?= e(
                                    $item['product_name']
                                ) ?>
                            </h2>


                            <div class="wishlist-vendor">

                                Sold by

                                <a
                                    href="<?= BASE_URL ?>vendor.php?id=<?= (int) $item['vendor_id'] ?>"
                                >
                                    <?= e(
                                        $item['business_name']
                                    ) ?>
                                </a>

                            </div>


                            <div
                                class="wishlist-description"
                            >
                                <?= e(
                                    $item['description']
                                    ?: 'No description available.'
                                ) ?>
                            </div>


                            <div
                                class="wishlist-price-row"
                            >

                                <span
                                    class="wishlist-price"
                                >
                                    <?= formatPrice(
                                        $item['price']
                                    ) ?>
                                </span>


                                <?php if (
                                    $isAvailable
                                ): ?>

                                    <span
                                        class="stock-status available"
                                    >
                                        ● In Stock
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="stock-status out"
                                    >
                                        ● Out of Stock
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div
                                class="wishlist-actions"
                            >

                                <a
                                    href="<?= BASE_URL ?>product_details.php?id=<?= (int) $item['product_id'] ?>"
                                    class="wishlist-btn view-btn"
                                >
                                    View Product
                                </a>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('Remove this product from your wishlist?');"
                                >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int) $item['product_id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="remove_wishlist"
                                        value="1"
                                        class="wishlist-btn remove-btn"
                                    >
                                        Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>

</html>