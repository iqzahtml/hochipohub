<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REVIEWS
|--------------------------------------------------------------------------
| File:
| review.php
|
| Purpose:
| - Display product reviews
| - Allow logged-in customers to submit reviews
| - Prevent duplicate review for same product
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$productId = (int) ($_GET['product_id'] ?? 0);

if ($productId <= 0) {
    redirect('catalog.php');
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.*,
        v.business_name,
        v.business_logo,
        c.category_name
    FROM products p
    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id
    INNER JOIN categories c
        ON p.category_id = c.category_id
    WHERE p.product_id = ?
    LIMIT 1
");

$stmt->execute([$productId]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('catalog.php');
}


/*
|--------------------------------------------------------------------------
| SUBMIT REVIEW
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCustomer();

    $customerId = (int) getUserId();

    $rating = (int) ($_POST['rating'] ?? 0);

    $reviewText = trim(
        $_POST['review'] ?? ''
    );

    $csrfToken =
        $_POST['csrf_token'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    if (!validateCsrfToken($csrfToken)) {

        setFlashMessage(
            'error',
            'Invalid security token. Please try again.'
        );

        redirect(
            'review.php?product_id=' .
            $productId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE RATING
    |--------------------------------------------------------------------------
    */

    if ($rating < 1 || $rating > 5) {

        setFlashMessage(
            'error',
            'Please select a rating between 1 and 5.'
        );

        redirect(
            'review.php?product_id=' .
            $productId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE REVIEW
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT review_id
        FROM reviews
        WHERE customer_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $customerId,
        $productId
    ]);

    if ($stmt->fetch()) {

        setFlashMessage(
            'error',
            'You have already reviewed this product.'
        );

        redirect(
            'review.php?product_id=' .
            $productId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT REVIEW
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        INSERT INTO reviews
        (
            customer_id,
            product_id,
            rating,
            review,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'Visible'
        )
    ");

    $stmt->execute([
        $customerId,
        $productId,
        $rating,
        $reviewText
    ]);


    setFlashMessage(
        'success',
        'Your review has been submitted successfully.'
    );

    redirect(
        'review.php?product_id=' .
        $productId
    );
}


/*
|--------------------------------------------------------------------------
| GET REVIEWS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
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

    WHERE r.product_id = ?
    AND r.status = 'Visible'

    ORDER BY r.review_date DESC
");

$stmt->execute([$productId]);

$reviews =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_reviews,
        COALESCE(
            AVG(rating),
            0
        ) AS average_rating

    FROM reviews

    WHERE product_id = ?
    AND status = 'Visible'
");

$stmt->execute([$productId]);

$reviewSummary =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CHECK USER REVIEW
|--------------------------------------------------------------------------
*/

$userReviewExists = false;

if (isLoggedIn() && isCustomer()) {

    $stmt = $db->prepare("
        SELECT review_id
        FROM reviews
        WHERE customer_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        getUserId(),
        $productId
    ]);

    $userReviewExists =
        (bool) $stmt->fetch();
}


$pageTitle =
    'Reviews - ' .
    $product['product_name'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

if (isLoggedIn()) {

    if (isCustomer()) {
        require_once __DIR__ . '/includes/customer_sidebar.php';
    } elseif (isVendor()) {
        require_once __DIR__ . '/includes/vendor_sidebar.php';
    }
}

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <!-- PRODUCT HEADER -->

        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    PRODUCT REVIEWS
                </span>

                <h1>
                    <?= e($product['product_name']) ?>
                </h1>

                <p>
                    <?= e($product['business_name']) ?>
                </p>

            </div>


            <div>

                <a
                    href="product_details.php?id=<?= $productId ?>"
                    class="btn btn-secondary"
                >
                    ← Back to Product
                </a>

            </div>

        </section>


        <!-- REVIEW SUMMARY -->

        <section class="stats-grid">

            <div class="stat-card">

                <span class="stat-label">
                    Average Rating
                </span>

                <strong class="stat-value">

                    <?= number_format(
                        (float) $reviewSummary['average_rating'],
                        1
                    ) ?>

                    / 5

                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Total Reviews
                </span>

                <strong class="stat-value">

                    <?= (int)
                        $reviewSummary['total_reviews'] ?>

                </strong>

            </div>

        </section>


        <!-- FLASH MESSAGE -->

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


        <!-- ADD REVIEW -->

        <?php if (
            isLoggedIn() &&
            isCustomer() &&
            !$userReviewExists
        ): ?>

            <section class="dashboard-section">

                <div class="section-heading">

                    <div>

                        <span class="small-label">
                            SHARE YOUR EXPERIENCE
                        </span>

                        <h2>
                            Write a Review
                        </h2>

                    </div>

                </div>


                <form
                    method="POST"
                    class="dashboard-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(
                            generateCsrfToken()
                        ) ?>"
                    >


                    <div class="form-group">

                        <label>
                            Rating
                        </label>

                        <select
                            name="rating"
                            required
                        >

                            <option value="">
                                Select rating
                            </option>

                            <option value="5">
                                ★★★★★ - Excellent
                            </option>

                            <option value="4">
                                ★★★★☆ - Good
                            </option>

                            <option value="3">
                                ★★★☆☆ - Average
                            </option>

                            <option value="2">
                                ★★☆☆☆ - Poor
                            </option>

                            <option value="1">
                                ★☆☆☆☆ - Very Poor
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Review
                        </label>

                        <textarea
                            name="review"
                            rows="5"
                            placeholder="Tell others what you think..."
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Submit Review
                    </button>

                </form>

            </section>

        <?php elseif (
            $userReviewExists
        ): ?>

            <div class="alert alert-info">

                You have already reviewed this product.

            </div>

        <?php endif; ?>


        <!-- REVIEWS -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        CUSTOMER FEEDBACK
                    </span>

                    <h2>
                        All Reviews
                    </h2>

                </div>

            </div>


            <?php if (
                empty($reviews)
            ): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ⭐
                    </div>

                    <h3>
                        No reviews yet
                    </h3>

                    <p>
                        Be the first customer to review
                        this product.
                    </p>

                </div>

            <?php else: ?>

                <div class="review-list">

                    <?php foreach (
                        $reviews
                        as $review
                    ): ?>

                        <article
                            class="review-card"
                        >

                            <div>

                                <strong>
                                    <?= e(
                                        $review['name']
                                    ) ?>
                                </strong>

                                <div>

                                    <?= str_repeat(
                                        '★',
                                        (int) $review['rating']
                                    ) ?>

                                    <?= str_repeat(
                                        '☆',
                                        5 -
                                        (int) $review['rating']
                                    ) ?>

                                </div>

                            </div>


                            <div>

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

                            </div>


                            <?php if (
                                !empty(
                                    $review['review']
                                )
                            ): ?>

                                <p>

                                    <?= nl2br(
                                        e(
                                            $review['review']
                                        )
                                    ) ?>

                                </p>

                            <?php endif; ?>

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