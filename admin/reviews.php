<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN REVIEWS
|--------------------------------------------------------------------------
| File: admin/reviews.php
|--------------------------------------------------------------------------
| Admin review management page.
| Uses PDO connection from database/db.php.
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$db = $db;

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    strtolower(trim($_SESSION['role'] ?? '')) !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('review_e')) {

    function review_e($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('review_date')) {

    function review_date($date)
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }
}

if (!function_exists('review_status_class')) {

    function review_status_class($status)
    {
        return 'review-status-' .
            strtolower(
                trim((string) $status)
            );
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE REVIEW STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $review_id =
        (int) ($_POST['review_id'] ?? 0);

    $status =
        trim($_POST['status'] ?? '');

    $allowed_status = [
        'Visible',
        'Hidden'
    ];

    if (
        $review_id > 0 &&
        in_array(
            $status,
            $allowed_status,
            true
        )
    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */

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
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $log = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $log->execute([
                $admin_id,
                'Updated review status to ' . $status,
                'review',
                $review_id
            ]);


            header(
                "Location: reviews.php?success=status"
            );

            exit;

        } catch (PDOException $e) {

            error_log('Review status update error: ' .
                $e->getMessage()
            );

            header(
                "Location: reviews.php?error=update"
            );

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

    $review_id =
        (int) $_GET['delete'];

    if ($review_id > 0) {

        try {

            /*
            |--------------------------------------------------------------------------
            | GET REVIEW IMAGE
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                SELECT image
                FROM reviews
                WHERE review_id = ?
            ");

            $stmt->execute([
                $review_id
            ]);

            $review =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$review) {

                header(
                    "Location: reviews.php?error=notfound"
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE REVIEW
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                DELETE FROM reviews
                WHERE review_id = ?
            ");

            $stmt->execute([
                $review_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | DELETE REVIEW IMAGE
            |--------------------------------------------------------------------------
            */

            if (!empty($review['image'])) {

                $imageFile =
                    dirname(__DIR__) .
                    '/uploads/products/' .
                    basename(
                        $review['image']
                    );

                if (
                    file_exists(
                        $imageFile
                    )
                ) {

                    @unlink(
                        $imageFile
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $log = $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $log->execute([
                $admin_id,
                'Deleted review',
                'review',
                $review_id
            ]);


            header(
                "Location: reviews.php?success=deleted"
            );

            exit;

        } catch (PDOException $e) {

            error_log(
                'Review deletion error: ' .
                $e->getMessage()
            );

            header(
                "Location: reviews.php?error=delete"
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search'] ?? ''
    );

$rating_filter =
    (int) (
        $_GET['rating'] ?? 0
    );

$status_filter =
    $_GET['status'] ?? '';


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

    $searchValue =
        '%' . $search . '%';

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;
}


/*
|--------------------------------------------------------------------------
| RATING FILTER
|--------------------------------------------------------------------------
*/

if (
    $rating_filter >= 1 &&
    $rating_filter <= 5
) {

    $sql .= "
        AND r.rating = ?
    ";

    $params[] =
        $rating_filter;
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

    $params[] =
        $status_filter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY r.review_date DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

$reviews = [];

try {

    $stmt =
        $db->prepare($sql);

    $stmt->execute($params);

    $reviews =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    error_log(
        'Reviews query error: ' .
        $e->getMessage()
    );

    $reviews = [];
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$total_reviews = 0;
$visible_reviews = 0;
$hidden_reviews = 0;
$average_rating = 0;


/*
|--------------------------------------------------------------------------
| TOTAL REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->query("
        SELECT COUNT(*)
        FROM reviews
    ");

    $total_reviews =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $total_reviews = 0;
}


/*
|--------------------------------------------------------------------------
| VISIBLE REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->query("
        SELECT COUNT(*)
        FROM reviews
        WHERE status = 'Visible'
    ");

    $visible_reviews =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $visible_reviews = 0;
}


/*
|--------------------------------------------------------------------------
| HIDDEN REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->query("
        SELECT COUNT(*)
        FROM reviews
        WHERE status = 'Hidden'
    ");

    $hidden_reviews =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $hidden_reviews = 0;
}


/*
|--------------------------------------------------------------------------
| AVERAGE RATING
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->query("
        SELECT ROUND(
            AVG(rating),
            1
        )FROM reviews
    ");

    $average_rating =
        $stmt->fetchColumn();

    if (
        $average_rating === null
    ) {

        $average_rating = 0;
    }

} catch (PDOException $e) {

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

    <title>
        Reviews | HochipoHub Admin
    </title>


    <!--
    |--------------------------------------------------------------------------
    | SAME ADMIN CSS
    |--------------------------------------------------------------------------
    -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | REVIEW PAGE
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

        .review-page {
            min-height: 100vh;

            padding: 35px;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(
                        37,
                        99,
                        235,
                        .10
                    ),
                    transparent 30%
                ),
                #f8fafc;
        }

        .review-container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .review-header {
            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 28px;
        }

        .review-header-left {
            display: flex;

            align-items: center;

            gap: 16px;
        }

        .review-header-icon {
            width: 58px;
            height: 58px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #ffffff;

            font-size: 25px;
            font-weight: 900;

            box-shadow:
                0 10px 25px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );
        }

        .review-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 32px;

            font-weight: 900;

            line-height: 1.1;
        }

        .review-header p {
            margin: 7px 0 0;

            color: #64748b;

            font-size: 14px;
        }

        .review-admin-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 10px 16px;

            border-radius: 999px;

            background: #eff6ff;

            border: 1px solid #bfdbfe;

            color: #2563eb;

            font-size: 12px;

            font-weight: 900;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .review-alert {
            padding: 14px 18px;

            margin-bottom: 20px;

            border-radius: 12px;

            font-size: 13px;

            font-weight: 800;
        }

        .review-alert.success {
            background: #ecfdf5;border: 1px solid #a7f3d0;

            color: #047857;
        }

        .review-alert.error {
            background: #fef2f2;

            border: 1px solid #fecaca;

            color: #b91c1c;
        }


        /*
        |--------------------------------------------------------------------------
        | STATISTICS
        |--------------------------------------------------------------------------
        */

        .review-stats {
            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 18px;

            margin-bottom: 24px;
        }

        .review-stat {
            position: relative;

            overflow: hidden;

            padding: 24px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 20px;

            box-shadow:
                0 8px 25px
                rgba(
                    15,
                    23,
                    42,
                    .05
                );

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .review-stat:hover {
            transform:
                translateY(-3px);

            box-shadow:
                0 14px 32px
                rgba(
                    15,
                    23,
                    42,
                    .08
                );
        }

        .review-stat::after {
            content: "";

            position: absolute;

            width: 100px;
            height: 100px;

            right: -35px;
            bottom: -45px;

            border-radius: 50%;

            background: #eff6ff;
        }

        .review-stat-label {
            position: relative;

            z-index: 1;

            display: block;

            margin-bottom: 10px;

            color: #64748b;

            font-size: 12px;

            font-weight: 900;

            text-transform: uppercase;

            letter-spacing: .5px;
        }

        .review-stat-value {
            position: relative;

            z-index: 1;

            display: block;

            color: #0f172a;

            font-size: 28px;

            font-weight: 900;
        }

        .review-stat-value.blue {
            color: #2563eb;
        }

        .review-stat-value.green {
            color: #059669;
        }

        .review-stat-value.red {
            color: #dc2626;
        }

        .review-stat-value.yellow {
            color: #d97706;
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER CARD
        |--------------------------------------------------------------------------
        */

        .review-filter-card {
            padding: 20px;

            margin-bottom: 20px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 18px;

            box-shadow:
                0 6px 20px
                rgba(
                    15,
                    23,
                    42,
                    .04
                );
        }

        .review-filter-header {
            margin-bottom: 15px;
        }

        .review-filter-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 17px;

            font-weight: 900;
        }

        .review-filter-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 12px;
        }

        .review-filter-form {
            display: grid;

            grid-template-columns:
                minmax(220px, 1fr)
                160px
                160px
                auto
                auto;

            gap: 10px;

            align-items: center;
        }

        .review-filter-form input,
        .review-filter-form select {
            width: 100%;

            min-height: 42px;

            padding: 0 13px;

            border:1px solid #cbd5e1;

            border-radius: 10px;

            background: #ffffff;

            color: #334155;

            font-family: inherit;

            font-size: 13px;
        }

        .review-filter-form input:focus,
        .review-filter-form select:focus {
            outline: none;

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    .10
                );
        }

        .review-filter-btn {
            min-height: 42px;

            padding: 0 18px;

            border: 0;

            border-radius: 10px;

            background: #2563eb;

            color: #ffffff;

            font-family: inherit;

            font-size: 12px;

            font-weight: 900;

            cursor: pointer;

            transition:
                background .2s ease;
        }

        .review-filter-btn:hover {
            background: #1d4ed8;
        }

        .review-reset-btn {
            min-height: 42px;

            padding: 0 16px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border:
                1px solid #cbd5e1;

            border-radius: 10px;

            background: #ffffff;

            color: #64748b;

            text-decoration: none;

            font-size: 12px;

            font-weight: 900;
        }

        .review-reset-btn:hover {
            background: #f8fafc;

            border-color: #93c5fd;

            color: #2563eb;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN CARD
        |--------------------------------------------------------------------------
        */

        .review-card {
            overflow: hidden;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 20px;

            box-shadow:
                0 10px 30px
                rgba(
                    15,
                    23,
                    42,
                    .06
                );
        }

        .review-card-header {
            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            padding: 22px 24px;

            border-bottom:
                1px solid #e2e8f0;
        }

        .review-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 18px;

            font-weight: 900;
        }

        .review-card-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 12px;
        }

        .review-record-count {
            padding: 7px 11px;

            border-radius: 999px;

            background: #f1f5f9;

            color: #475569;

            font-size: 11px;

            font-weight: 900;

            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .review-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        .review-table {
            width: 100%;

            min-width: 1250px;

            border-collapse:
                collapse;
        }

        .review-table th {
            padding: 15px 18px;

            background: #f8fafc;

            color: #64748b;

            font-size: 11px;

            font-weight: 900;

            text-align: left;

            text-transform:
                uppercase;

            letter-spacing: .5px;

            white-space: nowrap;
        }

        .review-table td {
            padding: 17px 18px;

            border-top:
                1px solid #f1f5f9;

            color: #334155;

            font-size: 13px;

            vertical-align: middle;}

        .review-table tbody tr {
            transition:
                background .15s ease;
        }

        .review-table tbody tr:hover td {
            background: #f8fafc;
        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW ID
        |--------------------------------------------------------------------------
        */

        .review-id {
            display: inline-flex;

            align-items: center;

            padding: 6px 9px;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-weight: 900;

            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        .review-customer strong {
            display: block;

            color: #0f172a;

            font-weight: 900;
        }

        .review-customer small {
            display: block;

            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        */

        .review-product {
            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 190px;
        }

        .review-product-image {
            width: 44px;
            height: 44px;

            flex-shrink: 0;

            overflow: hidden;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: #f1f5f9;

            border:
                1px solid #e2e8f0;
        }

        .review-product-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .review-product-image-empty {
            color: #94a3b8;

            font-size: 16px;

            font-weight: 900;
        }

        .review-product-name {
            color: #2563eb;

            font-weight: 900;

            text-decoration: none;

            line-height: 1.35;
        }

        .review-product-name:hover {
            text-decoration: underline;
        }


        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        .review-rating {
            min-width: 90px;
        }

        .review-stars {
            display: block;

            color: #f59e0b;

            font-size: 15px;

            letter-spacing: 1px;

            white-space: nowrap;
        }

        .review-rating-number {
            display: block;

            margin-top: 3px;

            color: #94a3b8;

            font-size: 11px;

            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW TEXT
        |--------------------------------------------------------------------------
        */

        .review-content {
            max-width: 330px;
        }

        .review-text {
            display: -webkit-box;

            overflow: hidden;

            -webkit-box-orient: vertical;

            -webkit-line-clamp: 3;

            color: #475569;

            line-height: 1.55;

            word-break: break-word;
        }

        .review-image-link {
            display: inline-flex;

            margin-top: 8px;

            padding: 5px 9px;

            border-radius: 7px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 10px;

            font-weight: 900;

            text-decoration: none;
        }

        .review-image-link:hover {
            background: #dbeafe;
        }/*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .review-status {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 900;

            white-space: nowrap;
        }

        .review-status-visible {
            background: #dcfce7;

            color: #166534;
        }

        .review-status-hidden {
            background: #fee2e2;

            color: #991b1b;
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS FORM
        |--------------------------------------------------------------------------
        */

        .review-status-form {
            margin: 0;
        }

        .review-status-select {
            min-width: 105px;

            padding: 8px 10px;

            border:
                1px solid #cbd5e1;

            border-radius: 9px;

            background: #ffffff;

            color: #334155;

            font-family: inherit;

            font-size: 12px;

            font-weight: 800;

            cursor: pointer;
        }

        .review-status-select:hover {
            border-color: #94a3b8;
        }

        .review-status-select:focus {
            outline: none;

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    .10
                );
        }


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        .review-date {
            color: #64748b;

            font-size: 12px;

            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | ACTION
        |--------------------------------------------------------------------------
        */

        .review-delete-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 34px;

            padding: 0 12px;

            border:
                1px solid #fecaca;

            border-radius: 9px;

            background: #fff1f2;

            color: #dc2626;

            font-size: 11px;

            font-weight: 900;

            text-decoration: none;

            transition:
                background .2s ease,
                border-color .2s ease;
        }

        .review-delete-btn:hover {
            background: #fee2e2;

            border-color: #fca5a5;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .review-empty {
            padding: 75px 20px;

            text-align: center;
        }

        .review-empty-icon {
            width: 64px;
            height: 64px;

            margin:
                0 auto 16px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 20px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 25px;

            font-weight: 900;
        }

        .review-empty h3 {
            margin: 0;

            color: #0f172a;

            font-size: 17px;

            font-weight: 900;
        }

        .review-empty p {
            max-width: 430px;

            margin:
                8px auto 0;

            color: #64748b;

            font-size: 13px;

            line-height: 1.6;
        }


        /*|--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1100px) {

            .review-stats {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .review-filter-form {
                grid-template-columns:
                    1fr
                    1fr
                    1fr
                    auto
                    auto;
            }

        }


        @media (max-width: 800px) {

            .review-page {
                padding: 20px;
            }

            .review-header {
                flex-direction: column;

                align-items: flex-start;
            }

            .review-header-left {
                align-items: flex-start;
            }

            .review-stats {
                grid-template-columns: 1fr;
            }

            .review-filter-form {
                grid-template-columns: 1fr;
            }

            .review-filter-btn,
            .review-reset-btn {
                width: 100%;
            }

            .review-card-header {
                align-items: flex-start;

                flex-direction: column;
            }

        }


        @media (max-width: 500px) {

            .review-page {
                padding: 15px;
            }

            .review-header h1 {
                font-size: 26px;
            }

            .review-header-icon {
                width: 50px;
                height: 50px;

                border-radius: 15px;
            }

            .review-header-left {
                gap: 12px;
            }

        }

    </style>

</head>


<body>

<div class="admin-layout">


    <!-- ==========================================================
         SIDEBAR
    =========================================================== -->

    <?php
        require_once
            dirname(__DIR__) .
            '/includes/admin_sidebar.php';
    ?>


    <!-- ==========================================================
         MAIN
    =========================================================== -->

    <main class="admin-main">

        <div class="review-page">

            <div class="review-container">


                <!-- ==================================================
                     HEADER
                ================================================== -->

                <header class="review-header">

                    <div class="review-header-left">

                        <div class="review-header-icon">
                            ★
                        </div>

                        <div>

                            <h1>
                                Reviews
                            </h1>

                            <p>
                                Monitor customer feedback and manage review visibility.
                            </p>

                        </div>

                    </div>


                    <div class="review-admin-badge">
                        ADMIN CONTROL
                    </div>

                </header>


                <!-- ==================================================
                     ALERT
                ================================================== -->

                <?php if (
                    isset($_GET['success'])
                ): ?>

                    <div class="review-alert success">

                        <?php if (
                            $_GET['success'] === 'status'
                        ): ?>

                            Review status updated successfully.

                        <?php elseif (
                            $_GET['success'] === 'deleted'
                        ): ?>

                            Review deleted successfully.

                        <?php endif; ?>

                    </div><?php endif; ?>


                <?php if (
                    isset($_GET['error'])
                ): ?>

                    <div class="review-alert error">

                        <?php if (
                            $_GET['error'] === 'notfound'
                        ): ?>

                            Review not found.

                        <?php elseif (
                            $_GET['error'] === 'update'
                        ): ?>

                            Unable to update review status.

                        <?php elseif (
                            $_GET['error'] === 'delete'
                        ): ?>

                            Unable to delete review.

                        <?php else: ?>

                            Unable to process the request.

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <!-- ==================================================
                     STATISTICS
                ================================================== -->

                <section class="review-stats">


                    <!-- TOTAL -->

                    <div class="review-stat">

                        <span
                            class="
                                review-stat-label
                            "
                        >
                            Total Reviews
                        </span>

                        <strong
                            class="
                                review-stat-value
                                blue
                            "
                        >
                            <?= number_format(
                                $total_reviews
                            ) ?>
                        </strong>

                    </div>


                    <!-- VISIBLE -->

                    <div class="review-stat">

                        <span
                            class="
                                review-stat-label
                            "
                        >
                            Visible Reviews
                        </span>

                        <strong
                            class="
                                review-stat-value
                                green
                            "
                        >
                            <?= number_format(
                                $visible_reviews
                            ) ?>
                        </strong>

                    </div>


                    <!-- HIDDEN -->

                    <div class="review-stat">

                        <span
                            class="
                                review-stat-label
                            "
                        >
                            Hidden Reviews
                        </span>

                        <strong
                            class="
                                review-stat-value
                                red
                            "
                        >
                            <?= number_format(
                                $hidden_reviews
                            ) ?>
                        </strong>

                    </div>


                    <!-- AVERAGE -->

                    <div class="review-stat">

                        <span
                            class="
                                review-stat-label
                            "
                        >
                            Average Rating
                        </span>

                        <strong
                            class="
                                review-stat-value
                                yellow
                            "
                        >
                            <?= number_format(
                                (float)
                                $average_rating,
                                1) ?>

                            / 5
                        </strong>

                    </div>


                </section>


                <!-- ==================================================
                     FILTER
                ================================================== -->

                <section class="review-filter-card">


                    <div
                        class="
                            review-filter-header
                        "
                    >

                        <h2>
                            Review Filters
                        </h2>

                        <p>
                            Search reviews by customer, product, rating or status.
                        </p>

                    </div>


                    <form
                        method="GET"
                        class="review-filter-form"
                    >


                        <!-- SEARCH -->

                        <input
                            type="text"
                            name="search"
                            placeholder="Search customer, product or review..."
                            value="<?= review_e(
                                $search
                            ) ?>"
                        >


                        <!-- RATING -->

                        <select name="rating">

                            <option value="">
                                All Ratings
                            </option>

                            <option
                                value="5"
                                <?= (
                                    $rating_filter === 5
                                )
                                    ? 'selected'
                                    : '' ?>
                            >
                                5 Stars
                            </option>

                            <option
                                value="4"
                                <?= (
                                    $rating_filter === 4
                                )
                                    ? 'selected'
                                    : '' ?>
                            >
                                4 Stars
                            </option>

                            <option
                                value="3"
                                <?= (
                                    $rating_filter === 3
                                )
                                    ? 'selected'
                                    : '' ?>
                            >
                                3 Stars
                            </option>

                            <option
                                value="2"
                                <?= (
                                    $rating_filter === 2
                                )
                                    ? 'selected'
                                    : '' ?>
                            >
                                2 Stars
                            </option>

                            <option
                                value="1"
                                <?= (
                                    $rating_filter === 1
                                )
                                    ? 'selected'
                                    : '' ?>
                            >
                                1 Star
                            </option>

                        </select>


                        <!-- STATUS -->

                        <select name="status">

                            <option value="">
                                All Status
                            </option>

                            <option
                                value="Visible"
                                <?= (
                                    $status_filter ===
                                    'Visible')
                                    ? 'selected'
                                    : '' ?>
                            >
                                Visible
                            </option>

                            <option
                                value="Hidden"
                                <?= (
                                    $status_filter ===
                                    'Hidden'
                                )
                                    ? 'selected'
                                    : '' ?>
                            >
                                Hidden
                            </option>

                        </select>


                        <!-- SEARCH BUTTON -->

                        <button
                            type="submit"
                            class="
                                review-filter-btn
                            "
                        >
                            SEARCH
                        </button>


                        <!-- RESET -->

                        <a
                            href="reviews.php"
                            class="
                                review-reset-btn
                            "
                        >
                            RESET
                        </a>


                    </form>

                </section>


                <!-- ==================================================
                     REVIEW CARD
                ================================================== -->

                <section class="review-card">


                    <div
                        class="
                            review-card-header
                        "
                    >

                        <div>

                            <h2>
                                Customer Reviews
                            </h2>

                            <p>
                                Latest customer feedback and review activity.
                            </p>

                        </div>


                        <span
                            class="
                                review-record-count
                            "
                        >

                            <?= number_format(
                                count($reviews)
                            ) ?>

                            records

                        </span>

                    </div>


                    <!-- ==================================================
                         EMPTY
                    ================================================== -->

                    <?php if (
                        empty($reviews)
                    ): ?>

                        <div
                            class="
                                review-empty
                            "
                        >

                            <div
                                class="
                                    review-empty-icon
                                "
                            >
                                ★
                            </div>

                            <h3>
                                No reviews found
                            </h3>

                            <p>
                                Customer reviews will appear here when users submit feedback for their purchased products.
                            </p>

                        </div>


                    <?php else: ?>


                        <!-- ==================================================
                             TABLE
                        ================================================== -->

                        <div
                            class="
                                review-table-wrapper
                            "
                        >

                            <table
                                class="review-table
                                "
                            >


                                <thead>

                                    <tr>

                                        <th>
                                            ID
                                        </th>

                                        <th>
                                            Customer
                                        </th>

                                        <th>
                                            Product
                                        </th>

                                        <th>
                                            Rating
                                        </th>

                                        <th>
                                            Review
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Date
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach (
                                    $reviews
                                    as $item
                                ): ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span
                                                class="
                                                    review-id
                                                "
                                            >

                                                #

                                                <?= (int)
                                                    (
                                                        $item[
                                                            'review_id'
                                                        ] ?? 0
                                                    ) ?>

                                            </span>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>

                                            <div
                                                class="
                                                    review-customer
                                                "
                                            >

                                                <strong>

                                                    <?= review_e(
                                                        $item[
                                                            'customer_name'
                                                        ]
                                                    ) ?>

                                                </strong>

                                                <small>

                                                    <?= review_e(
                                                        $item[
                                                            'customer_email'
                                                        ]
                                                    ) ?>

                                                </small>

                                            </div>

                                        </td>


                                        <!-- PRODUCT -->

                                        <td>

                                            <divclass="
                                                    review-product
                                                "
                                            >


                                                <!-- PRODUCT IMAGE -->

                                                <div
                                                    class="
                                                        review-product-image
                                                    "
                                                >

                                                    <?php

                                                    $productImage =
                                                        '';

                                                    if (
                                                        !empty(
                                                            $item[
                                                                'product_image'
                                                            ]
                                                        )
                                                    ) {

                                                        $productImage =
                                                            '../uploads/products/' .
                                                            basename(
                                                                $item[
                                                                    'product_image'
                                                                ]
                                                            );
                                                    }

                                                    ?>


                                                    <?php if (
                                                        $productImage &&
                                                        file_exists(
                                                            dirname(
                                                                DIR
                                                            ) .
                                                            '/uploads/products/' .
                                                            basename(
                                                                $item[
                                                                    'product_image'
                                                                ]
                                                            )
                                                        )
                                                    ): ?>

                                                        <img
                                                            src="<?= review_e(
                                                                $productImage
                                                            ) ?>"
                                                            alt="<?= review_e(
                                                                $item[
                                                                    'product_name'
                                                                ]
                                                            ) ?>"
                                                        >

                                                    <?php else: ?>

                                                        <span
                                                            class="
                                                                review-product-image-empty
                                                            "
                                                        >
                                                            ▪
                                                        </span><?php endif; ?>

                                                </div>


                                                <!-- PRODUCT NAME -->

                                                <a
                                                    href="../product_details.php?id=<?= (int) $item['product_id'] ?>"
                                                    target="_blank"
                                                    class="
                                                        review-product-name
                                                    "
                                                >

                                                    <?= review_e(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    ) ?>

                                                </a>


                                            </div>

                                        </td>


                                        <!-- RATING -->

                                        <td>

                                            <?php

                                            $rating =
                                                (int)
                                                (
                                                    $item[
                                                        'rating'
                                                    ] ?? 0
                                                );

                                            ?>

                                            <div
                                                class="
                                                    review-rating
                                                "
                                            >

                                                <span
                                                    class="
                                                        review-stars
                                                    "
                                                >

                                                    <?php

                                                    for (
                                                        $i = 1;
                                                        $i <= 5;
                                                        $i++
                                                    ):

                                                    ?>

                                                        <?= (
                                                            $i <=
                                                            $rating
                                                        )
                                                            ? '★'
                                                            : '☆'
                                                        ?>

                                                    <?php endfor; ?>

                                                </span>


                                                <span
                                                    class="
                                                        review-rating-number
                                                    "
                                                >

                                                    <?= $rating ?>/5

                                                </span>

                                            </div>

                                        </td>


                                        <!-- REVIEW -->

                                        <td>

                                            <div
                                                class="
                                                    review-content"
                                            >

                                                <div
                                                    class="
                                                        review-text
                                                    "
                                                >

                                                    <?= nl2br(
                                                        review_e(
                                                            $item[
                                                                'review'
                                                            ] ?? ''
                                                        )
                                                    ) ?>

                                                </div>


                                                <?php if (
                                                    !empty(
                                                        $item[
                                                            'image'
                                                        ]
                                                    )
                                                ): ?>

                                                    <?php

                                                    $reviewImage =
                                                        '../uploads/products/' .
                                                        basename(
                                                            $item[
                                                                'image'
                                                            ]
                                                        );

                                                    ?>

                                                    <a
                                                        href="<?= review_e(
                                                            $reviewImage
                                                        ) ?>"
                                                        target="_blank"
                                                        class="
                                                            review-image-link
                                                        "
                                                    >
                                                        VIEW IMAGE
                                                    </a>

                                                <?php endif; ?>


                                            </div>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <form
                                                method="POST"
                                                class="
                                                    review-status-form
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= (int)
                                                        $item[
                                                            'review_id'
                                                        ] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="update_status"
                                                    value="1"
                                                >


                                                <selectname="status"
                                                    class="
                                                        review-status-select
                                                    "
                                                    onchange="this.form.submit()"
                                                >

                                                    <option
                                                        value="Visible"
                                                        <?= (
                                                            $item[
                                                                'status'
                                                            ] ===
                                                            'Visible'
                                                        )
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        Visible
                                                    </option>

                                                    <option
                                                        value="Hidden"
                                                        <?= (
                                                            $item[
                                                                'status'
                                                            ] ===
                                                            'Hidden'
                                                        )
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        Hidden
                                                    </option>

                                                </select>

                                            </form>


                                            <div
                                                style="
                                                    margin-top:7px;
                                                "
                                            >

                                                <span
                                                    class="
                                                        review-status
                                                        <?= review_e(
                                                            review_status_class(
                                                                $item[
                                                                    'status'
                                                                ]
                                                            )
                                                        ) ?>
                                                    "
                                                >

                                                    <?= review_e(
                                                        $item[
                                                            'status'
                                                        ]
                                                    ) ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <span
                                                class="
                                                    review-date
                                                "
                                            ><?= review_e(
                                                    review_date(
                                                        $item[
                                                            'review_date'
                                                        ]
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <a
                                                href="reviews.php?delete=<?= (int)
                                                    $item[
                                                        'review_id'
                                                    ] ?>"
                                                class="
                                                    review-delete-btn
                                                "
                                                onclick="
                                                    return confirm(
                                                        'Are you sure you want to permanently delete this review?'
                                                    );
                                                "
                                            >
                                                DELETE
                                            </a>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>


                    <?php endif; ?>


                </section>


            </div>

        </div>

    </main>


</div>


</body>

</html>