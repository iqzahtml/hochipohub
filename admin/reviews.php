<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$ratingFilter = trim($_GET['rating'] ?? 'all');

$reviews = [];

$successMessage = '';
$errorMessage = '';

$totalReviews = 0;
$averageRating = 0;
$fiveStarReviews = 0;
$oneStarReviews = 0;

/*
|--------------------------------------------------------------------------
| DELETE REVIEW
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_review'])
) {

    $reviewId = (int) (
        $_POST['review_id'] ?? 0
    );

    if ($reviewId <= 0) {

        $errorMessage =
            'Invalid review selected.';

    } else {

        try {

            $stmt = $db->prepare("
                DELETE FROM reviews
                WHERE review_id = :review_id
                LIMIT 1
            ");

            $stmt->execute([
                ':review_id' => $reviewId
            ]);

            if ($stmt->rowCount() > 0) {

                $successMessage =
                    'Review deleted successfully.';

            } else {

                $errorMessage =
                    'Review could not be found.';
            }

        } catch (PDOException $e) {

            $errorMessage = APP_DEBUG
                ? $e->getMessage()
                : 'Unable to delete review.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $summaryStmt = $db->query("
        SELECT

            COUNT(*) AS total_reviews,

            COALESCE(
                AVG(rating),
                0
            ) AS average_rating,

            COALESCE(
                SUM(
                    CASE
                        WHEN rating = 5
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS five_star_reviews,

            COALESCE(
                SUM(
                    CASE
                        WHEN rating = 1
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS one_star_reviews

        FROM reviews
    ");

    $summary =
        $summaryStmt->fetch();

    if ($summary) {

        $totalReviews =
            (int) $summary[
                'total_reviews'
            ];

        $averageRating =
            (float) $summary[
                'average_rating'
            ];

        $fiveStarReviews =
            (int) $summary[
                'five_star_reviews'
            ];

        $oneStarReviews =
            (int) $summary[
                'one_star_reviews'
            ];
    }

} catch (PDOException $e) {

    if (APP_DEBUG) {

        $errorMessage =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| GET REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT

            r.review_id,
            r.user_id,
            r.product_id,
            r.rating,
            r.comment,
            r.created_at,

            u.name AS customer_name,
            u.email AS customer_email,

            p.product_name

        FROM reviews r

        LEFT JOIN users u
            ON r.user_id = u.user_id

        LEFT JOIN products p
            ON r.product_id = p.product_id

        WHERE 1 = 1
    ";

    $params = [];

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            AND (
                r.comment LIKE :search

                OR u.name LIKE :search

                OR u.email LIKE :search

                OR p.product_name LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';
    }

    /*
    |--------------------------------------------------------------------------
    | RATING FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $ratingFilter !== 'all' &&
        in_array(
            $ratingFilter,
            ['1', '2', '3', '4', '5'],
            true
        )
    ) {

        $sql .= "
            AND r.rating = :rating
        ";

        $params[':rating'] =
            (int) $ratingFilter;
    }

    $sql .= "
        ORDER BY r.created_at DESC
    ";

    $stmt =
        $db->prepare($sql);

    $stmt->execute(
        $params
    );

    $reviews =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $reviews = [];

    $errorMessage = APP_DEBUG
        ? $e->getMessage()
        : 'Unable to load reviews.';
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
        Reviews Management |
        <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .reviews-page {
            min-height: 100vh;

            padding:
                35px 4%
                60px;

            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(37,99,235,.14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 20%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                #f8fbff;
        }

        .reviews-container {
            max-width: 1500px;
            margin: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .reviews-hero {
            position: relative;

            overflow: hidden;

            margin-bottom: 24px;

            padding: 35px;

            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #020617,
                    #172554 35%,
                    #1d4ed8 68%,
                    #0284c7
                );

            color: white;

            box-shadow:
                0 25px 65px
                rgba(29,78,216,.22);
        }

        .reviews-hero::before {
            content: "";

            position: absolute;

            width: 370px;
            height: 370px;

            top: -220px;
            right: -80px;

            border-radius: 50%;

            background:
                rgba(96,165,250,.14);
        }

        .reviews-hero::after {
            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            right: 250px;
            bottom: -175px;

            border-radius: 50%;

            background:
                rgba(56,189,248,.09);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;

            color:
                rgba(255,255,255,.62);

            font-size: 9px;
            font-weight: 950;

            letter-spacing: 2px;

            text-transform: uppercase;
        }

        .reviews-hero h1 {
            margin: 0 0 8px;

            font-size:
                clamp(
                    29px,
                    5vw,
                    46px
                );

            font-weight: 950;
        }

        .reviews-hero p {
            max-width: 700px;

            margin: 0;

            color:
                rgba(255,255,255,.75);

            font-size: 11px;

            line-height: 1.7;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 14px;

            margin-bottom: 22px;
        }

        .summary-card {
            padding: 19px;

            border:
                1px solid #dbeafe;

            border-radius: 20px;

            background: white;

            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .summary-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 8px;
            font-weight: 950;

            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;

            font-size: 25px;
            font-weight: 950;
        }

        .summary-value.blue {
            color: #2563eb;
        }

        .summary-value.green {
            color: #16a34a;
        }

        .summary-value.red {
            color: #dc2626;
        }

        .summary-note {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | MESSAGES
        |--------------------------------------------------------------------------
        */

        .message {
            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 12px;

            font-size: 9px;
            font-weight: 850;
        }

        .message.success {
            border:
                1px solid #bbf7d0;

            background: #f0fdf4;

            color: #166534;
        }

        .message.error {
            border:
                1px solid #fecaca;

            background: #fef2f2;

            color: #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .reviews-panel {
            overflow: hidden;

            border:
                1px solid #dbeafe;

            border-radius: 23px;

            background: white;

            box-shadow:
                0 12px 40px
                rgba(15,23,42,.055);
        }

        .panel-header {
            padding: 21px 23px;

            border-bottom:
                1px solid #eff6ff;
        }

        .panel-header h2 {
            margin: 0 0 4px;

            color: #0f172a;

            font-size: 15px;
            font-weight: 950;
        }

        .panel-header p {
            margin: 0;

            color: #94a3b8;

            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .filter-area {
            padding: 16px 22px;

            border-bottom:
                1px solid #eff6ff;

            background: #fbfdff;
        }

        .filter-form {
            display: grid;

            grid-template-columns:
                1.7fr
                1fr
                auto;

            gap: 8px;

            align-items: center;
        }

        .filter-input,
        .filter-select {
            width: 100%;

            padding: 11px 12px;

            border:
                1px solid #dbeafe;

            border-radius: 11px;

            outline: none;

            background: white;

            color: #334155;

            font-size: 9px;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(37,99,235,.06);
        }

        .filter-button {
            padding: 11px 17px;

            border: 0;

            border-radius: 11px;

            cursor: pointer;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0284c7
                );

            color: white;

            font-size: 8px;
            font-weight: 950;
        }

        .clear-filter {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            margin-top: 10px;

            padding: 9px 13px;

            border:
                1px solid #dbeafe;

            border-radius: 10px;

            background: white;

            color: #64748b;

            text-decoration: none;

            font-size: 8px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | REVIEW LIST
        |--------------------------------------------------------------------------
        */

        .reviews-list {
            padding: 5px 22px 22px;
        }

        .review-card {
            display: grid;

            grid-template-columns:
                190px
                1fr
                auto;

            gap: 20px;

            align-items: start;

            padding: 21px 0;

            border-bottom:
                1px solid #eff6ff;
        }

        .review-card:last-child {
            border-bottom: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        .customer-name {
            margin-bottom: 4px;

            color: #0f172a;

            font-size: 10px;
            font-weight: 950;
        }

        .customer-email {
            color: #94a3b8;

            font-size: 7px;

            word-break: break-word;
        }

        /*
        |--------------------------------------------------------------------------
        | REVIEW CONTENT
        |--------------------------------------------------------------------------
        */

        .product-name {
            margin-bottom: 8px;

            color: #2563eb;

            font-size: 9px;
            font-weight: 950;
        }

        .stars {
            display: flex;

            gap: 2px;

            margin-bottom: 9px;
        }

        .star {
            font-size: 13px;
        }

        .star.active {
            color: #f59e0b;
        }

        .star.inactive {
            color: #cbd5e1;
        }

        .review-comment {
            color: #475569;

            font-size: 9px;

            line-height: 1.7;
        }

        .no-comment {
            color: #94a3b8;

            font-size: 8px;

            font-style: italic;
        }

        /*
        |--------------------------------------------------------------------------
        | META
        |--------------------------------------------------------------------------
        */

        .review-date {
            color: #94a3b8;

            font-size: 7px;

            white-space: nowrap;
        }

        .review-id {
            margin-top: 5px;

            color: #cbd5e1;

            font-size: 7px;

            text-align: right;
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        .delete-review {
            margin-top: 12px;

            padding: 7px 10px;

            border:
                1px solid #fecaca;

            border-radius: 8px;

            background: #fef2f2;

            color: #dc2626;

            cursor: pointer;

            font-size: 7px;
            font-weight: 950;
        }

        .delete-review:hover {
            background: #fee2e2;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .empty-state {
            padding: 70px 20px;

            text-align: center;
        }

        .empty-icon {
            margin-bottom: 12px;

            font-size: 42px;
        }

        .empty-state strong {
            display: block;

            margin-bottom: 6px;

            color: #334155;

            font-size: 13px;
            font-weight: 950;
        }

        .empty-state span {
            color: #94a3b8;

            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 950px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .review-card {
                grid-template-columns:
                    1fr auto;
            }

            .review-customer {
                grid-column: 1 / -1;
            }

        }

        @media (max-width: 650px) {

            .reviews-page {
                padding:
                    25px 15px 50px;
            }

            .reviews-hero {
                padding: 27px 21px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .review-card {
                display: block;
            }

            .review-meta {
                margin-top: 12px;
            }

            .review-id {
                text-align: left;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/navbar.php';
?>


<main class="reviews-page">

    <div class="reviews-container">


        <!-- HERO -->

        <section class="reviews-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    Review Management
                </h1>

                <p>
                    Monitor customer feedback,
                    product ratings and marketplace
                    experience from one place.
                </p>

            </div>

        </section>


        <!-- MESSAGES -->

        <?php if (
            $successMessage !== ''
        ): ?>

            <div class="message success">

                ✓
                <?= e(
                    $successMessage
                ) ?>

            </div>

        <?php endif; ?>


        <?php if (
            $errorMessage !== ''
        ): ?>

            <div class="message error">

                ⚠
                <?= e(
                    $errorMessage
                ) ?>

            </div>

        <?php endif; ?>


        <!-- SUMMARY -->

        <section class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Reviews
                </div>

                <div
                    class="
                        summary-value
                        blue
                    "
                >
                    <?= number_format(
                        $totalReviews
                    ) ?>
                </div>

                <div class="summary-note">
                    Customer reviews
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Average Rating
                </div>

                <div
                    class="
                        summary-value
                        green
                    "
                >
                    <?= number_format(
                        $averageRating,
                        1
                    ) ?>
                    / 5
                </div>

                <div class="summary-note">
                    Overall marketplace rating
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    5 Star Reviews
                </div>

                <div
                    class="
                        summary-value
                        green
                    "
                >
                    <?= number_format(
                        $fiveStarReviews
                    ) ?>
                </div>

                <div class="summary-note">
                    Excellent ratings
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    1 Star Reviews
                </div>

                <div
                    class="
                        summary-value
                        red
                    "
                >
                    <?= number_format(
                        $oneStarReviews
                    ) ?>
                </div>

                <div class="summary-note">
                    Reviews requiring attention
                </div>

            </div>

        </section>


        <!-- PANEL -->

        <section class="reviews-panel">


            <div class="panel-header">

                <h2>
                    Customer Reviews
                </h2>

                <p>
                    Search and filter customer
                    feedback.
                </p>

            </div>


            <!-- FILTER -->

            <div class="filter-area">

                <form
                    method="GET"
                    class="filter-form"
                >

                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="
                            Search customer,
                            product or review...
                        "
                        value="<?= e(
                            $search
                        ) ?>"
                    >


                    <select
                        name="rating"
                        class="filter-select"
                    >

                        <option
                            value="all"
                        >
                            All Ratings
                        </option>

                        <option
                            value="5"
                            <?= $ratingFilter === '5'
                                ? 'selected'
                                : '' ?>
                        >
                            ★★★★★ — 5 Stars
                        </option>

                        <option
                            value="4"
                            <?= $ratingFilter === '4'
                                ? 'selected'
                                : '' ?>
                        >
                            ★★★★☆ — 4 Stars
                        </option>

                        <option
                            value="3"
                            <?= $ratingFilter === '3'
                                ? 'selected'
                                : '' ?>
                        >
                            ★★★☆☆ — 3 Stars
                        </option>

                        <option
                            value="2"
                            <?= $ratingFilter === '2'
                                ? 'selected'
                                : '' ?>
                        >
                            ★★☆☆☆ — 2 Stars
                        </option>

                        <option
                            value="1"
                            <?= $ratingFilter === '1'
                                ? 'selected'
                                : '' ?>
                        >
                            ★☆☆☆☆ — 1 Star
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="filter-button"
                    >
                        FILTER
                    </button>

                </form>


                <?php if (
                    $search !== '' ||
                    $ratingFilter !== 'all'
                ): ?>

                    <a
                        href="<?= BASE_URL ?>admin/reviews.php"
                        class="clear-filter"
                    >
                        Clear Filters
                    </a>

                <?php endif; ?>

            </div>


            <!-- REVIEWS -->

            <?php if (
                empty($reviews)
            ): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        💬
                    </div>

                    <strong>
                        No reviews found
                    </strong>

                    <span>
                        There are no reviews matching
                        your current filter.
                    </span>

                </div>

            <?php else: ?>


                <div class="reviews-list">

                    <?php foreach (
                        $reviews
                        as $review
                    ): ?>

                        <?php

                        $rating =
                            max(
                                1,
                                min(
                                    5,
                                    (int) (
                                        $review[
                                            'rating'
                                        ] ?? 0
                                    )
                                )
                            );

                        $customerName =
                            !empty(
                                $review[
                                    'customer_name'
                                ]
                            )
                            ? $review[
                                'customer_name'
                            ]
                            : 'Unknown Customer';

                        $customerEmail =
                            $review[
                                'customer_email'
                            ] ?? '';

                        $productName =
                            !empty(
                                $review[
                                    'product_name'
                                ]
                            )
                            ? $review[
                                'product_name'
                            ]
                            : 'Unknown Product';

                        ?>

                        <article
                            class="review-card"
                        >


                            <!-- CUSTOMER -->

                            <div
                                class="
                                    review-customer
                                "
                            >

                                <div
                                    class="
                                        customer-name
                                    "
                                >
                                    <?= e(
                                        $customerName
                                    ) ?>
                                </div>

                                <?php if (
                                    $customerEmail !== ''
                                ): ?>

                                    <div
                                        class="
                                            customer-email
                                        "
                                    >
                                        <?= e(
                                            $customerEmail
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- REVIEW -->

                            <div
                                class="
                                    review-content
                                "
                            >

                                <div
                                    class="
                                        product-name
                                    "
                                >
                                    <?= e(
                                        $productName
                                    ) ?>
                                </div>


                                <div
                                    class="stars"
                                    aria-label="
                                        <?= $rating ?>
                                        out of 5 stars
                                    "
                                >

                                    <?php for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ): ?>

                                        <span
                                            class="
                                                star
                                                <?= $i <= $rating
                                                    ? 'active'
                                                    : 'inactive' ?>
                                            "
                                        >
                                            ★
                                        </span>

                                    <?php endfor; ?>

                                </div>


                                <?php if (
                                    trim(
                                        $review[
                                            'comment'
                                        ] ?? ''
                                    ) !== ''
                                ): ?>

                                    <div
                                        class="
                                            review-comment
                                        "
                                    >
                                        <?= nl2br(
                                            e(
                                                $review[
                                                    'comment'
                                                ]
                                            )
                                        ) ?>
                                    </div>

                                <?php else: ?>

                                    <div
                                        class="
                                            no-comment
                                        "
                                    >
                                        No comment provided.
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- META -->

                            <div
                                class="review-meta"
                            >

                                <div
                                    class="
                                        review-date
                                    "
                                >
                                    <?= !empty(
                                        $review[
                                            'created_at'
                                        ]
                                    )
                                        ? e(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $review[
                                                        'created_at'
                                                    ]
                                                )
                                            )
                                        )
                                        : 'Unknown date'
                                    ?>
                                </div>


                                <div
                                    class="
                                        review-id
                                    "
                                >
                                    Review
                                    #<?= (int) $review[
                                        'review_id'
                                    ] ?>
                                </div>


                                <form
                                    method="POST"
                                    onsubmit="
                                        return confirm(
                                            'Are you sure you want to delete this review?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="review_id"
                                        value="<?= (int) $review[
                                            'review_id'
                                        ] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_review"
                                        value="1"
                                        class="
                                            delete-review
                                        "
                                    >
                                        DELETE
                                    </button>

                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>

</body>

</html>