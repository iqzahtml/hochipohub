<?php
/**
 * HOCHIPOHUB
 * Admin - Reviews Management
 */

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE REVIEW STATUS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $review_id = (int) ($_POST['review_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed_status = [
        'Visible',
        'Hidden'
    ];

    if (
        $review_id > 0 &&
        in_array($status, $allowed_status, true)
    ) {

        try {

            $stmt = $db->prepare("
                UPDATE reviews
                SET status = ?
                WHERE review_id = ?
            ");

            $stmt->execute([
                $status,
                $review_id
            ]);

            /*
             * Admin log
             */

            $stmt = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                'Updated review status to ' . $status,
                'review',
                $review_id
            ]);

            header("Location: reviews.php?success=status");
            exit;

        } catch (PDOException $e) {

            header("Location: reviews.php?error=update");
            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE REVIEW
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $review_id = (int) $_GET['delete'];

    if ($review_id > 0) {

        try {

            /*
             * Get review image first
             */

            $stmt = $db->prepare("
                SELECT image
                FROM reviews
                WHERE review_id = ?
            ");

            $stmt->execute([
                $review_id
            ]);

            $review = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$review) {

                header("Location: reviews.php?error=notfound");
                exit;
            }


            /*
             * Delete review
             */

            $stmt = $db->prepare("
                DELETE FROM reviews
                WHERE review_id = ?
            ");

            $stmt->execute([
                $review_id
            ]);


            /*
             * Delete image if it exists
             */

            if (!empty($review['image'])) {

                $imageFile = dirname(__DIR__) .
                    '/uploads/products/' .
                    basename($review['image']);

                if (file_exists($imageFile)) {
                    @unlink($imageFile);
                }
            }


            /*
             * Admin log
             */

            $stmt = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                'Deleted review',
                'review',
                $review_id
            ]);


            header("Location: reviews.php?success=deleted");
            exit;

        } catch (PDOException $e) {

            header("Location: reviews.php?error=delete");
            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$rating_filter = (int) (
    $_GET['rating'] ?? 0
);

$status_filter = $_GET['status'] ?? '';


/*
|--------------------------------------------------------------------------
| REVIEWS QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        r.review_id,
        r.rating,
        r.review,
        r.image,
        r.status,
        r.review_date,

        u.user_id,
        u.name AS customer_name,
        u.email AS customer_email,

        p.product_id,
        p.product_name,
        p.image AS product_image

    FROM reviews r

    INNER JOIN users u
        ON r.customer_id = u.user_id

    INNER JOIN products p
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
            p.product_name LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
            OR r.review LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| RATING FILTER
|--------------------------------------------------------------------------
*/

if ($rating_filter >= 1 && $rating_filter <= 5) {

    $sql .= "
        AND r.rating = ?
    ";

    $params[] = $rating_filter;
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $status_filter === 'Visible' ||
    $status_filter === 'Hidden'
) {

    $sql .= "
        AND r.status = ?
    ";

    $params[] = $status_filter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY r.review_date DESC
";


$stmt = $db->prepare($sql);

$stmt->execute($params);

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*)
    FROM reviews
");

$total_reviews = (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM reviews
    WHERE status = 'Visible'
");

$visible_reviews = (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM reviews
    WHERE status = 'Hidden'
");

$hidden_reviews = (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT ROUND(AVG(rating), 1)
    FROM reviews
");

$average_rating = $stmt->fetchColumn();

if ($average_rating === null) {
    $average_rating = 0;
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

    <title>Reviews | HochipoHub Admin</title>

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

</head>

<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->

    <?php

    $admin_sidebar =
        dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    if (file_exists($admin_sidebar)) {
        require_once $admin_sidebar;
    }

    ?>


    <!-- MAIN -->

    <main class="admin-main">


        <!-- TOP BAR -->

        <div class="admin-topbar">

            <div>

                <h1>
                    Reviews
                </h1>

                <p>
                    Monitor and manage customer reviews.
                </p>

            </div>


            <div class="admin-user">

                <span>

                    <?= htmlspecialchars(
                        $_SESSION['name'] ??
                        'Administrator'
                    ) ?>

                </span>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if (isset($_GET['success'])): ?>

            <div class="admin-alert success">

                <?php if ($_GET['success'] === 'status'): ?>

                    Review status updated successfully.

                <?php elseif ($_GET['success'] === 'deleted'): ?>

                    Review deleted successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if (isset($_GET['error'])): ?>

            <div class="admin-alert error">

                <?php if ($_GET['error'] === 'notfound'): ?>

                    Review not found.

                <?php else: ?>

                    Unable to process the request.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- STATISTICS -->

        <section class="admin-stats">


            <div class="stat-card">

                <span class="stat-label">
                    Total Reviews
                </span>

                <strong>
                    <?= $total_reviews ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Visible
                </span>

                <strong>
                    <?= $visible_reviews ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Hidden
                </span>

                <strong>
                    <?= $hidden_reviews ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Average Rating
                </span>

                <strong>
                    <?= number_format(
                        (float) $average_rating,
                        1
                    ) ?>
                    / 5
                </strong>

            </div>


        </section>


        <!-- FILTER -->

        <section class="admin-panel">


            <form
                method="GET"
                class="admin-filter-form"
            >


                <input
                    type="text"
                    name="search"
                    placeholder="Search customer, product or review..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select name="rating">

                    <option value="">
                        All Ratings
                    </option>


                    <option
                        value="5"
                        <?= $rating_filter === 5 ? 'selected' : '' ?>
                    >
                        5 Stars
                    </option>


                    <option
                        value="4"
                        <?= $rating_filter === 4 ? 'selected' : '' ?>
                    >
                        4 Stars
                    </option>


                    <option
                        value="3"
                        <?= $rating_filter === 3 ? 'selected' : '' ?>
                    >
                        3 Stars
                    </option>


                    <option
                        value="2"
                        <?= $rating_filter === 2 ? 'selected' : '' ?>
                    >
                        2 Stars
                    </option>


                    <option
                        value="1"
                        <?= $rating_filter === 1 ? 'selected' : '' ?>
                    >
                        1 Star
                    </option>

                </select>


                <select name="status">

                    <option value="">
                        All Status
                    </option>


                    <option
                        value="Visible"
                        <?= $status_filter === 'Visible' ? 'selected' : '' ?>
                    >
                        Visible
                    </option>


                    <option
                        value="Hidden"
                        <?= $status_filter === 'Hidden' ? 'selected' : '' ?>
                    >
                        Hidden
                    </option>

                </select>


                <button
                    type="submit"
                    class="admin-btn primary"
                >
                    Search
                </button>


                <a
                    href="reviews.php"
                    class="admin-btn secondary"
                >
                    Reset
                </a>


            </form>

        </section>


        <!-- REVIEWS -->

        <section class="admin-panel">


            <div class="panel-header">

                <div>

                    <h2>
                        Customer Reviews
                    </h2>

                    <p>
                        <?= count($reviews) ?>
                        review(s) found
                    </p>

                </div>

            </div>


            <div class="table-wrapper">


                <table class="admin-table">


                    <thead>

                    <tr>

                        <th>ID</th>

                        <th>Customer</th>

                        <th>Product</th>

                        <th>Rating</th>

                        <th>Review</th>

                        <th>Status</th>

                        <th>Date</th>

                        <th>Action</th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($reviews)): ?>


                        <tr>

                            <td
                                colspan="8"
                                class="empty-state"
                            >

                                No reviews found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($reviews as $item): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    #<?= (int) $item['review_id'] ?>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $item['customer_name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= htmlspecialchars(
                                            $item['customer_email']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <a
                                        href="../product_details.php?id=<?= (int) $item['product_id'] ?>"
                                        target="_blank"
                                    >

                                        <?= htmlspecialchars(
                                            $item['product_name']
                                        ) ?>

                                    </a>

                                </td>


                                <!-- RATING -->

                                <td>

                                    <span class="rating-stars">

                                        <?php

                                        $rating =
                                            (int) $item['rating'];

                                        for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ):

                                        ?>

                                            <?= $i <= $rating ? '★' : '☆' ?>

                                        <?php endfor; ?>

                                    </span>

                                    <small>

                                        <?= $rating ?>/5

                                    </small>

                                </td>


                                <!-- REVIEW -->

                                <td>

                                    <div class="review-text">

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $item['review'] ??
                                                ''
                                            )
                                        ) ?>

                                    </div>


                                    <?php if (!empty($item['image'])): ?>

                                        <?php

                                        $reviewImage =
                                            '../uploads/products/' .
                                            basename(
                                                $item['image']
                                            );

                                        ?>

                                        <a
                                            href="<?= htmlspecialchars($reviewImage) ?>"
                                            target="_blank"
                                        >

                                            View Image

                                        </a>

                                    <?php endif; ?>


                                </td>


                                <!-- STATUS -->

                                <td>

                                    <form
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="review_id"
                                            value="<?= (int) $item['review_id'] ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="update_status"
                                            value="1"
                                        >


                                        <select
                                            name="status"
                                            onchange="this.form.submit()"
                                            class="status-select"
                                        >

                                            <option
                                                value="Visible"
                                                <?= $item['status'] === 'Visible'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Visible
                                            </option>


                                            <option
                                                value="Hidden"
                                                <?= $item['status'] === 'Hidden'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Hidden
                                            </option>

                                        </select>

                                    </form>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $item['review_date']
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <div class="table-actions">


                                        <a
                                            href="reviews.php?delete=<?= (int) $item['review_id'] ?>"
                                            class="admin-btn small danger"
                                            onclick="return confirm('Are you sure you want to permanently delete this review?');"
                                        >

                                            Delete

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </section>


    </main>

</div>

</body>

</html>