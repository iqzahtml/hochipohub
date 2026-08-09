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

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage = '';

$editReview = null;

/*
|--------------------------------------------------------------------------
| DELETE REVIEW
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['delete_review'])
) {

    $reviewId = (int) (
        $_POST['review_id'] ?? 0
    );

    if ($reviewId <= 0) {

        $errorMessage =
            'Invalid review.';

    } else {

        try {

            $deleteStmt = $db->prepare("
                DELETE FROM reviews
                WHERE review_id = :review_id
                AND user_id = :user_id
            ");

            $deleteStmt->execute([
                ':review_id' => $reviewId,
                ':user_id' => $userId
            ]);

            if ($deleteStmt->rowCount() > 0) {

                $successMessage =
                    'Review deleted successfully.';

            } else {

                $errorMessage =
                    'Review not found or you do not have permission to delete it.';
            }

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to delete review.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| ADD / UPDATE REVIEW
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_review'])
) {

    $productId = (int) (
        $_POST['product_id'] ?? 0
    );

    $rating = (int) (
        $_POST['rating'] ?? 0
    );

    $comment = trim(
        $_POST['comment'] ?? ''
    );

    $reviewId = (int) (
        $_POST['review_id'] ?? 0
    );

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($productId <= 0) {

        $errorMessage =
            'Please select a valid product.';

    } elseif (
        $rating < 1 ||
        $rating > 5
    ) {

        $errorMessage =
            'Please select a rating between 1 and 5 stars.';

    } elseif ($comment === '') {

        $errorMessage =
            'Please write a review.';

    } elseif (strlen($comment) > 1000) {

        $errorMessage =
            'Review must not exceed 1000 characters.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK WHETHER CUSTOMER PURCHASED PRODUCT
            |--------------------------------------------------------------------------
            */

            $purchaseStmt = $db->prepare("
                SELECT od.order_detail_id
                FROM order_details od
                INNER JOIN orders o
                    ON o.order_id = od.order_id
                WHERE o.user_id = :user_id
                AND od.product_id = :product_id
                AND (
                    o.status = 'completed'
                    OR o.order_status = 'completed'
                    OR o.payment_status = 'paid'
                )
                LIMIT 1
            ");

            try {

                $purchaseStmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId
                ]);

            } catch (Throwable $purchaseError) {

                /*
                |--------------------------------------------------------------------------
                | FALLBACK FOR DATABASES WITHOUT order_status
                |--------------------------------------------------------------------------
                */

                $purchaseStmt = $db->prepare("
                    SELECT od.order_detail_id
                    FROM order_details od
                    INNER JOIN orders o
                        ON o.order_id = od.order_id
                    WHERE o.user_id = :user_id
                    AND od.product_id = :product_id
                    LIMIT 1
                ");

                $purchaseStmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId
                ]);
            }

            $purchased =
                $purchaseStmt->fetch();


            if (!$purchased) {

                $errorMessage =
                    'You can only review products that you purchased.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | UPDATE EXISTING REVIEW
                |--------------------------------------------------------------------------
                */

                if ($reviewId > 0) {

                    $updateReview = $db->prepare("
                        UPDATE reviews
                        SET
                            rating = :rating,
                            comment = :comment,
                            updated_at = NOW()
                        WHERE review_id = :review_id
                        AND user_id = :user_id
                        AND product_id = :product_id
                    ");

                    $updateReview->execute([
                        ':rating' => $rating,
                        ':comment' => $comment,
                        ':review_id' => $reviewId,
                        ':user_id' => $userId,
                        ':product_id' => $productId
                    ]);

                    if (
                        $updateReview->rowCount() > 0
                    ) {

                        $successMessage =
                            'Your review has been updated.';

                    } else {

                        $errorMessage =
                            'Review could not be updated.';
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | CHECK EXISTING REVIEW
                    |--------------------------------------------------------------------------
                    */

                    $existingStmt = $db->prepare("
                        SELECT review_id
                        FROM reviews
                        WHERE user_id = :user_id
                        AND product_id = :product_id
                        LIMIT 1
                    ");

                    $existingStmt->execute([
                        ':user_id' => $userId,
                        ':product_id' => $productId
                    ]);

                    $existingReview =
                        $existingStmt->fetch();


                    if ($existingReview) {

                        $errorMessage =
                            'You have already reviewed this product.';

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | INSERT REVIEW
                        |--------------------------------------------------------------------------
                        */

                        $insertReview = $db->prepare("
                            INSERT INTO reviews
                            (
                                user_id,
                                product_id,
                                rating,
                                comment,
                                created_at,
                                updated_at
                            )
                            VALUES
                            (
                                :user_id,
                                :product_id,
                                :rating,
                                :comment,
                                NOW(),
                                NOW()
                            )
                        ");

                        $insertReview->execute([
                            ':user_id' => $userId,
                            ':product_id' => $productId,
                            ':rating' => $rating,
                            ':comment' => $comment
                        ]);

                        $successMessage =
                            'Thank you! Your review has been submitted.';
                    }
                }
            }

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to save review.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| EDIT REVIEW
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['edit'])
) {

    $editId = (int) $_GET['edit'];

    if ($editId > 0) {

        $editStmt = $db->prepare("
            SELECT
                review_id,
                product_id,
                rating,
                comment
            FROM reviews
            WHERE review_id = :review_id
            AND user_id = :user_id
            LIMIT 1
        ");

        $editStmt->execute([
            ':review_id' => $editId,
            ':user_id' => $userId
        ]);

        $editReview =
            $editStmt->fetch();
    }
}

/*
|--------------------------------------------------------------------------
| GET CUSTOMER REVIEWS
|--------------------------------------------------------------------------
*/

$myReviews = [];

try {

    $reviewsStmt = $db->prepare("
        SELECT
            r.review_id,
            r.product_id,
            r.rating,
            r.comment,
            r.created_at,
            r.updated_at,
            p.product_name,
            p.image
        FROM reviews r
        INNER JOIN products p
            ON p.product_id = r.product_id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
    ");

    $reviewsStmt->execute([
        ':user_id' => $userId
    ]);

    $myReviews =
        $reviewsStmt->fetchAll();

} catch (Throwable $e) {

    if (APP_DEBUG) {
        $errorMessage =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| PRODUCTS CUSTOMER CAN REVIEW
|--------------------------------------------------------------------------
*/

$reviewableProducts = [];

try {

    $reviewableStmt = $db->prepare("
        SELECT DISTINCT
            p.product_id,
            p.product_name,
            p.image
        FROM order_details od
        INNER JOIN orders o
            ON o.order_id = od.order_id
        INNER JOIN products p
            ON p.product_id = od.product_id
        LEFT JOIN reviews r
            ON r.product_id = p.product_id
            AND r.user_id = :user_id
        WHERE o.user_id = :user_id
        AND r.review_id IS NULL
        ORDER BY p.product_name ASC
    ");

    $reviewableStmt->execute([
        ':user_id' => $userId
    ]);

    $reviewableProducts =
        $reviewableStmt->fetchAll();

} catch (Throwable $e) {

    if (APP_DEBUG) {
        $errorMessage =
            $e->getMessage();
    }
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
        Reviews | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fbff;
        }

        .review-page {
            min-height: 100vh;
            padding: 45px 5%;
            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(37, 99, 235, .14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 15%,
                    rgba(14, 165, 233, .12),
                    transparent 28%
                ),
                #f8fbff;
        }

        .review-container {
            max-width: 1180px;
            margin: auto;
        }

        .review-header {
            margin-bottom: 30px;
        }

        .review-kicker {
            display: inline-flex;
            padding: 7px 13px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .review-header h1 {
            margin: 14px 0 8px;
            color: #0f172a;
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 950;
        }

        .review-header p {
            margin: 0;
            max-width: 650px;
            color: #64748b;
            line-height: 1.7;
        }

        .review-layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1.35fr)
                minmax(300px, .65fr);
            gap: 24px;
        }

        .review-card {
            padding: 26px;
            border: 1px solid #dbeafe;
            border-radius: 25px;
            background: #ffffff;
            box-shadow:
                0 20px 55px rgba(15, 23, 42, .07);
        }

        .section-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .section-heading h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 20px;
            font-weight: 950;
        }

        .section-heading p {
            margin: 0;
            color: #94a3b8;
            font-size: 12px;
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

        .review-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .review-item {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr);
            gap: 16px;
            padding: 17px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #fbfdff;
        }

        .review-product-image {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
        }

        .review-product-name {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .stars {
            display: inline-flex;
            gap: 2px;
            margin-bottom: 8px;
            color: #f59e0b;
            font-size: 15px;
            letter-spacing: 1px;
        }

        .review-comment {
            margin: 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .review-date {
            margin-top: 9px;
            color: #94a3b8;
            font-size: 10px;
        }

        .review-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 12px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border: none;
            border-radius: 9px;
            text-decoration: none;
            font-size: 10px;
            font-weight: 900;
            cursor: pointer;
        }

        .edit-btn {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
        }

        .review-form {
            display: flex;
            flex-direction: column;
            gap: 17px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            color: #334155;
            font-size: 11px;
            font-weight: 950;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dbeafe;
            border-radius: 13px;
            outline: none;
            background: #f8fbff;
            color: #0f172a;
            font-family: inherit;
            font-size: 12px;
            transition: .2s ease;
        }

        .form-group textarea {
            min-height: 125px;
            resize: vertical;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow:
                0 0 0 4px rgba(37, 99, 235, .08);
        }

        .rating-picker {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 4px;
        }

        .rating-picker input {
            display: none;
        }

        .rating-picker label {
            color: #cbd5e1;
            cursor: pointer;
            font-size: 30px;
            line-height: 1;
            transition: .15s ease;
        }

        .rating-picker label:hover,
        .rating-picker label:hover ~ label,
        .rating-picker input:checked ~ label {
            color: #f59e0b;
            transform: scale(1.05);
        }

        .submit-btn {
            width: 100%;
            padding: 13px 18px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );
            color: #ffffff;
            font-size: 12px;
            font-weight: 950;
            cursor: pointer;
            transition: .2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                0 12px 25px rgba(37, 99, 235, .25);
        }

        .cancel-btn {
            display: block;
            margin-top: 8px;
            padding: 12px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            text-align: center;
            text-decoration: none;
            font-size: 11px;
            font-weight: 900;
        }

        .empty-state {
            padding: 45px 20px;
            border: 1px dashed #bfdbfe;
            border-radius: 18px;
            background: #f8fbff;
            text-align: center;
        }

        .empty-icon {
            margin-bottom: 12px;
            font-size: 35px;
        }

        .empty-state h3 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 16px;
            font-weight: 950;
        }

        .empty-state p {
            margin: 0;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.6;
        }

        .review-tip {
            margin-top: 20px;
            padding: 15px;
            border-radius: 15px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 11px;
            line-height: 1.7;
        }

        .review-tip strong {
            font-weight: 950;
        }

        @media (max-width: 900px) {

            .review-layout {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .review-page {
                padding: 30px 16px;
            }

            .review-card {
                padding: 20px;
                border-radius: 20px;
            }

            .review-item {
                grid-template-columns: 1fr;
            }

            .review-product-image {
                width: 90px;
                height: 90px;
            }

        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="review-page">

    <div class="review-container">

        <header class="review-header">

            <span class="review-kicker">
                Customer Experience
            </span>

            <h1>
                Your Reviews
            </h1>

            <p>
                Share your experience and help other
                customers discover products worth buying.
            </p>

        </header>


        <?php if ($successMessage !== ''): ?>

            <div class="alert success">
                ✓ <?= e($successMessage) ?>
            </div>

        <?php endif; ?>


        <?php if ($errorMessage !== ''): ?>

            <div class="alert error">
                ⚠ <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>


        <div class="review-layout">

            <!-- LEFT -->

            <section class="review-card">

                <div class="section-heading">

                    <div>

                        <h2>
                            My Reviews
                        </h2>

                        <p>
                            Reviews you have submitted.
                        </p>

                    </div>

                </div>


                <?php if (empty($myReviews)): ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            ⭐
                        </div>

                        <h3>
                            No reviews yet
                        </h3>

                        <p>
                            Purchase a product and share
                            your experience here.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="review-list">

                        <?php foreach (
                            $myReviews
                            as $review
                        ): ?>

                            <?php

                            $reviewImage =
                                productImageUrl(
                                    $review['image'] ?? ''
                                );

                            ?>

                            <article class="review-item">

                                <img
                                    src="<?= e($reviewImage) ?>"
                                    alt="<?= e(
                                        $review['product_name']
                                    ) ?>"
                                    class="review-product-image"
                                    onerror="this.src='<?= e(
                                        BASE_URL .
                                        'image/product/default-product.jpg'
                                    ) ?>'"
                                >


                                <div>

                                    <h3
                                        class="review-product-name"
                                    >
                                        <?= e(
                                            $review['product_name']
                                        ) ?>
                                    </h3>


                                    <div
                                        class="stars"
                                        aria-label="<?= (int) $review['rating'] ?> out of 5"
                                    >

                                        <?php for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ): ?>

                                            <?= $i <= $review['rating']
                                                ? '★'
                                                : '☆' ?>

                                        <?php endfor; ?>

                                    </div>


                                    <p
                                        class="review-comment"
                                    >
                                        <?= e(
                                            $review['comment']
                                        ) ?>
                                    </p>


                                    <div
                                        class="review-date"
                                    >
                                        Posted
                                        <?= date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $review['created_at']
                                            )
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $review['updated_at']
                                            )
                                            &&
                                            $review['updated_at']
                                            !==
                                            $review['created_at']
                                        ): ?>

                                            · Updated

                                        <?php endif; ?>

                                    </div>


                                    <div
                                        class="review-actions"
                                    >

                                        <a
                                            href="<?= BASE_URL ?>review.php?edit=<?= (int) $review['review_id'] ?>"
                                            class="action-btn edit-btn"
                                        >
                                            Edit
                                        </a>


                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Delete this review?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['review_id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_review"
                                                value="1"
                                                class="action-btn delete-btn"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>


            <!-- RIGHT -->

            <aside class="review-card">

                <div class="section-heading">

                    <div>

                        <h2>
                            <?= $editReview
                                ? 'Edit Review'
                                : 'Write a Review' ?>
                        </h2>

                        <p>
                            <?= $editReview
                                ? 'Update your existing review.'
                                : 'Tell us what you think.' ?>
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="review-form"
                >

                    <?php if ($editReview): ?>

                        <input
                            type="hidden"
                            name="review_id"
                            value="<?= (int) $editReview['review_id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $editReview['product_id'] ?>"
                        >

                        <div class="form-group">

                            <label>
                                Product
                            </label>

                            <?php

                            $editProductName = 'Selected Product';

                            $productNameStmt = $db->prepare("
                                SELECT product_name
                                FROM products
                                WHERE product_id = :product_id
                                LIMIT 1
                            ");

                            $productNameStmt->execute([
                                ':product_id' =>
                                    $editReview['product_id']
                            ]);

                            $editProduct =
                                $productNameStmt->fetch();

                            if ($editProduct) {
                                $editProductName =
                                    $editProduct['product_name'];
                            }

                            ?>

                            <select disabled>

                                <option selected>
                                    <?= e(
                                        $editProductName
                                    ) ?>
                                </option>

                            </select>

                        </div>

                    <?php else: ?>

                        <div class="form-group">

                            <label for="product_id">
                                Product
                            </label>

                            <select
                                id="product_id"
                                name="product_id"
                                required
                            >

                                <option value="">
                                    Select a product
                                </option>

                                <?php foreach (
                                    $reviewableProducts
                                    as $product
                                ): ?>

                                    <option
                                        value="<?= (int) $product['product_id'] ?>"
                                    >
                                        <?= e(
                                            $product['product_name']
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    <?php endif; ?>


                    <div class="form-group">

                        <label>
                            Rating
                        </label>

                        <div class="rating-picker">

                            <?php

                            $selectedRating =
                                $editReview
                                    ? (int) $editReview['rating']
                                    : 0;

                            ?>

                            <?php for (
                                $i = 5;
                                $i >= 1;
                                $i--
                            ): ?>

                                <input
                                    type="radio"
                                    id="star<?= $i ?>"
                                    name="rating"
                                    value="<?= $i ?>"
                                    <?= $selectedRating === $i
                                        ? 'checked'
                                        : '' ?>
                                    required
                                >

                                <label
                                    for="star<?= $i ?>"
                                    title="<?= $i ?> stars"
                                >
                                    ★
                                </label>

                            <?php endfor; ?>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="comment">
                            Your Review
                        </label>

                        <textarea
                            id="comment"
                            name="comment"
                            maxlength="1000"
                            placeholder="Tell other customers about your experience..."
                            required
                        ><?= $editReview
                            ? e($editReview['comment'])
                            : '' ?></textarea>

                    </div>


                    <button
                        type="submit"
                        name="save_review"
                        value="1"
                        class="submit-btn"
                    >
                        <?= $editReview
                            ? 'Update Review'
                            : 'Submit Review' ?>
                    </button>


                    <?php if ($editReview): ?>

                        <a
                            href="<?= BASE_URL ?>review.php"
                            class="cancel-btn"
                        >
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </form>


                <div class="review-tip">

                    <strong>
                        💡 Review tip
                    </strong>

                    <br>

                    Be honest and specific.
                    Your feedback helps other
                    HochipoHub customers make
                    better decisions.

                </div>

            </aside>

        </div>

    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>

</html>