<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN REVIEWS
|--------------------------------------------------------------------------
| File: admin/reviews.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';


$db = getDB();


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    strtolower(
        trim(
            $_SESSION['role']
            ?? ''
        )
    ) !== 'admin'
) {

    header('Location: ../index.php');
    exit;
}


$adminId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('reviewEscape')) {

    function reviewEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('reviewDate')) {

    function reviewDate($date): string
    {
        if (!$date) {
            return '-';
        }


        $timestamp =
            strtotime($date);


        if (!$timestamp) {
            return '-';
        }


        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }
}


if (!function_exists('reviewStatusClass')) {

    function reviewStatusClass($status): string
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );


        if ($status === 'hidden') {
            return 'hidden';
        }


        return 'visible';
    }
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';


if (isset($_GET['success'])) {

    if ($_GET['success'] === 'status') {

        $message =
            'Review status updated successfully.';

        $messageType =
            'success';
    }

    elseif ($_GET['success'] === 'deleted') {

        $message =
            'Review deleted successfully.';

        $messageType =
            'success';
    }
}


if (isset($_GET['error'])) {

    $messageType =
        'error';


    switch ($_GET['error']) {

        case 'notfound':

            $message =
                'Review not found.';

            break;


        case 'update':

            $message =
                'Unable to update review status.';

            break;


        case 'delete':

            $message =
                'Unable to delete review.';

            break;


        case 'security':

            $message =
                'Invalid security token. Please refresh and try again.';

            break;


        default:

            $message =
                'Unable to process the request.';

            break;
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

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: reviews.php?error=security'
        );

        exit;
    }


    $reviewId =
        (int) (
            $_POST['review_id']
            ?? 0
        );


    $status =
        trim(
            $_POST['status']
            ?? ''
        );


    $allowedStatuses = [

        'Visible',
        'Hidden'

    ];


    if (
        $reviewId <= 0 ||
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {

        header(
            'Location: reviews.php?error=update'
        );

        exit;
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | CHECK REVIEW
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT review_id
                FROM reviews
                WHERE review_id = ?
                LIMIT 1
            ");


        $stmt->execute([
            $reviewId
        ]);


        if (
            !$stmt->fetch(
                PDO::FETCH_ASSOC
            )
        ) {

            header(
                'Location: reviews.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                UPDATE reviews

                SET status = ?

                WHERE review_id = ?
            ");


        $stmt->execute([
            $status,
            $reviewId
        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $log =
            $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        $log->execute([

            $adminId,

            'Updated review status to ' .
            $status,

            'review',

            $reviewId

        ]);


        header(
            'Location: reviews.php?success=status'
        );

        exit;

    }

    catch (Throwable $e) {

        error_log(
            'Review status update error: ' .
            $e->getMessage()
        );


        header(
            'Location: reviews.php?error=update'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| DELETE REVIEW
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_review'])
) {

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: reviews.php?error=security'
        );

        exit;
    }


    $reviewId =
        (int) (
            $_POST['review_id']
            ?? 0
        );


    if ($reviewId <= 0) {

        header(
            'Location: reviews.php?error=delete'
        );

        exit;
    }


    try {

        $db->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | GET REVIEW
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    review_id,
                    image

                FROM reviews

                WHERE review_id = ?

                LIMIT 1

                FOR UPDATE
            ");


        $stmt->execute([
            $reviewId
        ]);


        $review =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$review) {

            $db->rollBack();


            header(
                'Location: reviews.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE REVIEW
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                DELETE FROM reviews
                WHERE review_id = ?
            ");


        $stmt->execute([
            $reviewId
        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $log =
            $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        $log->execute([

            $adminId,

            'Deleted review',

            'review',

            $reviewId

        ]);


        $db->commit();


        /*
        |--------------------------------------------------------------------------
        | DELETE REVIEW IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $review['image']
            )
        ) {

            $imageFile =
                __DIR__ .
                '/../uploads/products/' .
                basename(
                    $review['image']
                );


            if (
                file_exists($imageFile) &&
                is_file($imageFile)
            ) {

                @unlink(
                    $imageFile
                );
            }
        }


        header(
            'Location: reviews.php?success=deleted'
        );

        exit;

    }

    catch (Throwable $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }


        error_log(
            'Review deletion error: ' .
            $e->getMessage()
        );


        header(
            'Location: reviews.php?error=delete'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$ratingFilter =
    (int) (
        $_GET['rating']
        ?? 0
    );


$statusFilter =
    $_GET['status']
    ?? '';


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
        AND
        (
            p.product_name LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
            OR r.review LIKE ?
        )
    ";


    $searchValue =
        '%' .
        $search .
        '%';


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
    $ratingFilter >= 1 &&
    $ratingFilter <= 5
) {

    $sql .= "
        AND r.rating = ?
    ";


    $params[] =
        $ratingFilter;
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $statusFilter === 'Visible' ||
    $statusFilter === 'Hidden'
) {

    $sql .= "
        AND r.status = ?
    ";


    $params[] =
        $statusFilter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        r.review_date DESC,
        r.review_id DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

$reviews = [];


try {

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $reviews =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $reviews =
        [];


    error_log(
        'Reviews query error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalReviews = 0;
$visibleReviews = 0;
$hiddenReviews = 0;
$averageRating = 0;


/*
|--------------------------------------------------------------------------
| TOTAL REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT COUNT(*)
            FROM reviews
        ");


    $totalReviews =
        (int)
        $stmt->fetchColumn();

}

catch (Throwable $e) {

    $totalReviews =
        0;
}


/*
|--------------------------------------------------------------------------
| VISIBLE REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT COUNT(*)
            FROM reviews
            WHERE status = 'Visible'
        ");


    $visibleReviews =
        (int)
        $stmt->fetchColumn();

}

catch (Throwable $e) {

    $visibleReviews =
        0;
}


/*
|--------------------------------------------------------------------------
| HIDDEN REVIEWS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT COUNT(*)
            FROM reviews
            WHERE status = 'Hidden'
        ");


    $hiddenReviews =
        (int)
        $stmt->fetchColumn();

}

catch (Throwable $e) {

    $hiddenReviews =
        0;
}


/*
|--------------------------------------------------------------------------
| AVERAGE RATING
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->query("
            SELECT
                ROUND(
                    AVG(rating),
                    1
                )

            FROM reviews
        ");


    $averageRating =
        $stmt->fetchColumn();


    if ($averageRating === null) {

        $averageRating =
            0;
    }

}

catch (Throwable $e) {

    $averageRating =
        0;
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


    <!-- ============================================================
         POPPINS
    ============================================================= -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

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
        | ROOT
        |--------------------------------------------------------------------------
        */

        :root {

            --review-sidebar-width:
                260px;

            --review-blue:
                #2563eb;

            --review-navy:
                #08265a;

            --review-border:
                #dce7f3;

            --review-text:
                #0b2d63;

            --review-muted:
                #8294b3;

        }


        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing:
                border-box;

        }


        html,
        body {

            margin:
                0;

            padding:
                0;

            min-height:
                100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                #eef5fd;

        }


        body {

            overflow-x:
                hidden;

        }


        button,
        input,
        select {

            font-family:
                inherit;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR FONT
        |--------------------------------------------------------------------------
        */

        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {

            font-family:
                'Poppins',
                sans-serif !important;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .reviews-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --review-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --review-sidebar-width
                    )
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .reviews-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                38px
                35px
                70px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .reviews-hero {

            position:
                relative;

            min-height:
                155px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                34px
                38px;

            margin-bottom:
                26px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius:
                26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .reviews-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                260px;

            height:
                260px;

            right:
                -70px;

            top:
                -140px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .reviews-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                170px;

            height:
                170px;

            right:
                155px;

            bottom:
                -110px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .reviews-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .reviews-hero h1 {

            margin:
                0
                0
                8px;

            color:
                #ffffff;

            font-size:
                38px;

            line-height:
                1.05;

            font-weight:
                800;

            letter-spacing:
                -1.5px;

        }


        .reviews-hero p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size:
                14px;

            font-weight:
                500;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO ICON
        |--------------------------------------------------------------------------
        */

        .reviews-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                82px;

            height:
                82px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .26
                );

            border-radius:
                22px;

            background:

                linear-gradient(
                    145deg,
                    rgba(
                        255,
                        255,
                        255,
                        .20
                    ),
                    rgba(
                        255,
                        255,
                        255,
                        .10
                    )
                );

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .25
                ),

                0
                12px
                30px
                rgba(
                    0,
                    35,
                    100,
                    .18
                );

            font-size:
                34px;

            line-height:
                1;

        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .reviews-alert {

            margin-bottom:
                22px;

            padding:
                14px
                17px;

            border-radius:
                12px;

            font-size:
                11px;

            font-weight:
                600;

        }


        .reviews-alert.success {

            color:
                #166534;

            background:
                #ecfdf5;

            border:
                1px solid
                #bbf7d0;

        }


        .reviews-alert.error {

            color:
                #991b1b;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | STATISTICS
        |--------------------------------------------------------------------------
        */

        .reviews-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                18px;

            margin-bottom:
                30px;

        }


        .review-stat {

            position:
                relative;

            min-height:
                150px;

            overflow:
                hidden;

            padding:
                26px
                24px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --review-border
                );

            border-top:
                4px solid
                #2563eb;

            border-radius:
                20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .review-stat::after {

            content:
                "";

            position:
                absolute;

            right:
                -29px;

            bottom:
                -45px;

            width:
                110px;

            height:
                110px;

            border-radius:
                50%;

            background:
                #edf4ff;

        }


        .review-stat.visible {

            border-top-color:
                #16a34a;

        }


        .review-stat.visible::after {

            background:
                #eaf9ef;

        }


        .review-stat.hidden {

            border-top-color:
                #ef4444;

        }


        .review-stat.hidden::after {

            background:
                #fff0f1;

        }


        .review-stat.rating {

            border-top-color:
                #f59e0b;

        }


        .review-stat.rating::after {

            background:
                #fff7df;

        }


        .review-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                15px;

            color:
                #61728e;

            font-size:
                10px;

            font-weight:
                800;

            letter-spacing:
                .75px;

            text-transform:
                uppercase;

        }


        .review-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #0b326d;

            font-size:
                32px;

            line-height:
                1;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .reviews-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --review-border
                );

            border-radius:
                24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .reviews-panel-header {

            min-height:
                110px;

            padding:
                26px
                30px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid
                #e7edf5;

        }


        .reviews-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .reviews-panel-icon {

            width:
                53px;

            height:
                53px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                16px;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size:
                22px;

            line-height:
                1;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .reviews-panel-header h2 {

            margin:
                0
                0
                5px;

            color:
                #092e65;

            font-size:
                20px;

            font-weight:
                800;

        }


        .reviews-panel-header p {

            margin:
                0;

            color:
                #8999b4;

            font-size:
                11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT
        |--------------------------------------------------------------------------
        */

        .reviews-count {

            min-height:
                36px;

            padding:
                0
                16px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #d6e7ff;

            border-radius:
                999px;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .reviews-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .reviews-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1.5fr
                )

                minmax(
                    150px,
                    .5fr
                )

                minmax(
                    150px,
                    .5fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .reviews-filter input,
        .reviews-filter select {

            width:
                100%;

            height:
                43px;

            padding:
                0
                13px;

            outline:
                none;

            color:
                #26354e;

            background:
                #ffffff;

            border:
                1px solid
                #d8e3ef;

            border-radius:
                10px;

            font-size:
                10px;

        }


        .reviews-filter input::placeholder {

            color:
                #96a5b9;

        }


        .reviews-filter input:focus,
        .reviews-filter select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .review-btn {

            min-height:
                43px;

            padding:
                0
                17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .review-btn-primary {

            color:
                #ffffff;

            border:
                0;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            box-shadow:

                0
                7px
                15px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

        }


        .review-btn-secondary {

            color:
                #66758b;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ee;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .reviews-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .reviews-table {

            width:
                100%;

            min-width:
                1250px;

            border-collapse:
                collapse;

        }


        .reviews-table thead {

            background:
                #f6f9fd;

        }


        .reviews-table th {

            height:
                44px;

            padding:
                0
                16px;

            color:
                #65758f;

            border-bottom:
                1px solid
                #dfe7f0;

            font-size:
                8px;

            font-weight:
                800;

            text-align:
                left;

            letter-spacing:
                .55px;

            text-transform:
                uppercase;

            white-space:
                nowrap;

        }


        .reviews-table td {

            padding:
                16px;

            color:
                #435169;

            border-bottom:
                1px solid
                #edf1f6;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .reviews-table tbody tr:hover {

            background:
                #f9fbff;

        }


        .reviews-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW ID
        |--------------------------------------------------------------------------
        */

        .review-id {

            color:
                #8796ac;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        .review-customer {

            min-width:
                170px;

        }


        .review-customer strong {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #112b55;

            font-size:
                10px;

            font-weight:
                800;

        }


        .review-customer small {

            display:
                block;

            max-width:
                190px;

            overflow:
                hidden;

            color:
                #8897ac;

            font-size:
                8px;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        */

        .review-product {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            min-width:
                200px;

        }


        .review-product-image {

            width:
                44px;

            height:
                44px;

            flex-shrink:
                0;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                10px;

            font-size:
                17px;

        }


        .review-product-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        .review-product-name {

            max-width:
                180px;

            color:
                #2563eb;

            font-size:
                9px;

            font-weight:
                800;

            line-height:
                1.4;

            text-decoration:
                none;

        }


        .review-product-name:hover {

            text-decoration:
                underline;

        }


        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        .review-rating {

            min-width:
                100px;

        }


        .review-stars {

            display:
                block;

            color:
                #f59e0b;

            font-size:
                13px;

            letter-spacing:
                1px;

            white-space:
                nowrap;

        }


        .review-rating-number {

            display:
                block;

            margin-top:
                3px;

            color:
                #94a3b8;

            font-size:
                8px;

            font-weight:
                700;

        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW TEXT
        |--------------------------------------------------------------------------
        */

        .review-content {

            max-width:
                300px;

        }


        .review-text {

            display:
                -webkit-box;

            overflow:
                hidden;

            -webkit-box-orient:
                vertical;

            -webkit-line-clamp:
                3;

            color:
                #526176;

            font-size:
                9px;

            line-height:
                1.55;

            word-break:
                break-word;

        }


        .review-image-link {

            display:
                inline-flex;

            margin-top:
                7px;

            padding:
                5px
                8px;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                7px;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .review-status {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        .review-status::before {

            content:
                "";

            width:
                5px;

            height:
                5px;

            border-radius:
                50%;

        }


        .review-status.visible {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .review-status.visible::before {

            background:
                #22c55e;

        }


        .review-status.hidden {

            color:
                #b91c1c;

            background:
                #fff1f2;

        }


        .review-status.hidden::before {

            background:
                #ef4444;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS SELECT
        |--------------------------------------------------------------------------
        */

        .review-status-form {

            margin:
                0;

        }


        .review-status-select {

            min-width:
                105px;

            height:
                34px;

            padding:
                0
                9px;

            outline:
                none;

            color:
                #334155;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ef;

            border-radius:
                9px;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .review-status-select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        .review-date {

            color:
                #7c8ca3;

            font-size:
                8px;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        .review-delete-form {

            margin:
                0;

        }


        .review-delete-btn {

            min-height:
                32px;

            padding:
                0
                11px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #b91c1c;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

            border-radius:
                8px;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .review-delete-btn:hover {

            background:
                #fee2e2;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .reviews-empty {

            padding:
                75px
                20px;

            text-align:
                center;

        }


        .reviews-empty-icon {

            width:
                62px;

            height:
                62px;

            margin:
                0
                auto
                15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                17px;

            font-size:
                28px;

        }


        .reviews-empty h3 {

            margin:
                0
                0
                6px;

            color:
                #49617f;

            font-size:
                14px;

            font-weight:
                800;

        }


        .reviews-empty p {

            max-width:
                430px;

            margin:
                0 auto;

            color:
                #94a3b8;

            font-size:
                10px;

            line-height:
                1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {

            .reviews-stats {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .reviews-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .reviews-filter input {

                grid-column:
                    1 / -1;

            }

        }


        @media (max-width: 900px) {

            :root {

                --review-sidebar-width:
                    0px;

            }


            .reviews-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .reviews-content {

                padding:
                    25px
                    20px
                    50px;

            }


            .reviews-hero {

                min-height:
                    140px;

                padding:
                    28px;

            }


            .reviews-hero h1 {

                font-size:
                    31px;

            }


            .reviews-hero-icon {

                width:
                    67px;

                height:
                    67px;

                font-size:
                    28px;

            }

        }


        @media (max-width: 650px) {

            .reviews-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .reviews-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .reviews-hero h1 {

                font-size:
                    27px;

            }


            .reviews-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .reviews-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .reviews-stats {

                grid-template-columns:
                    1fr;

                gap:
                    12px;

            }


            .review-stat {

                min-height:
                    120px;

            }


            .reviews-panel-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                padding:
                    20px
                    17px;

            }


            .reviews-filter {

                grid-template-columns:
                    1fr;

            }


            .reviews-filter input {

                grid-column:
                    auto;

            }


            .review-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    /*
    |--------------------------------------------------------------------------
    | ADMIN SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="reviews-main">


        <div class="reviews-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="reviews-hero">


                <div class="reviews-hero-text">

                    <h1>
                        Reviews
                    </h1>

                    <p>
                        Monitor customer feedback and manage review visibility.
                    </p>

                </div>


                <div class="reviews-hero-icon">

                    ⭐

                </div>


            </section>


            <!-- =====================================================
                 MESSAGE
            ====================================================== -->

            <?php if ($message !== ''): ?>


                <div
                    class="
                        reviews-alert
                        <?= reviewEscape(
                            $messageType
                        ) ?>
                    "
                >

                    <?= reviewEscape(
                        $message
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="reviews-stats">


                <!-- TOTAL -->

                <div class="review-stat">

                    <span class="review-stat-label">

                        Total Reviews

                    </span>


                    <strong class="review-stat-value">

                        <?= number_format(
                            $totalReviews
                        ) ?>

                    </strong>

                </div>


                <!-- VISIBLE -->

                <div
                    class="
                        review-stat
                        visible
                    "
                >

                    <span class="review-stat-label">

                        Visible Reviews

                    </span>


                    <strong class="review-stat-value">

                        <?= number_format(
                            $visibleReviews
                        ) ?>

                    </strong>

                </div>


                <!-- HIDDEN -->

                <div
                    class="
                        review-stat
                        hidden
                    "
                >

                    <span class="review-stat-label">

                        Hidden Reviews

                    </span>


                    <strong class="review-stat-value">

                        <?= number_format(
                            $hiddenReviews
                        ) ?>

                    </strong>

                </div>


                <!-- RATING -->

                <div
                    class="
                        review-stat
                        rating
                    "
                >

                    <span class="review-stat-label">

                        Average Rating

                    </span>


                    <strong class="review-stat-value">

                        <?= number_format(
                            (float)
                            $averageRating,
                            1
                        ) ?>

                        / 5

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 REVIEW PANEL
            ====================================================== -->

            <section class="reviews-panel">


                <!-- =================================================
                     PANEL HEADER
                ================================================== -->

                <div class="reviews-panel-header">


                    <div class="reviews-panel-title">


                        <div class="reviews-panel-icon">

                            💬

                        </div>


                        <div>

                            <h2>
                                Customer Reviews
                            </h2>

                            <p>
                                Search, filter and manage marketplace reviews.
                            </p>

                        </div>


                    </div>


                    <span class="reviews-count">

                        <?= number_format(
                            count(
                                $reviews
                            )
                        ) ?>

                        reviews

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="reviews-filter-wrapper">


                    <form
                        method="GET"
                        action="reviews.php"
                        class="reviews-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= reviewEscape(
                                $search
                            ) ?>"
                            placeholder="Search customer, product or review..."
                            autocomplete="off"
                        >


                        <!-- RATING -->

                        <select
                            name="rating"
                            aria-label="Filter rating"
                        >

                            <option value="0">

                                All Ratings

                            </option>


                            <?php for (
                                $ratingOption = 5;
                                $ratingOption >= 1;
                                $ratingOption--
                            ): ?>


                                <option
                                    value="<?= $ratingOption ?>"
                                    <?= $ratingFilter ===
                                        $ratingOption
                                            ? 'selected'
                                            : '' ?>
                                >

                                    <?= $ratingOption ?>

                                    <?= $ratingOption === 1
                                        ? 'Star'
                                        : 'Stars' ?>

                                </option>


                            <?php endfor; ?>


                        </select>


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter status"
                        >

                            <option value="">

                                All Status

                            </option>


                            <option
                                value="Visible"
                                <?= $statusFilter ===
                                    'Visible'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Visible

                            </option>


                            <option
                                value="Hidden"
                                <?= $statusFilter ===
                                    'Hidden'
                                        ? 'selected'
                                        : '' ?>
                            >

                                Hidden

                            </option>


                        </select>


                        <!-- SEARCH -->

                        <button
                            type="submit"
                            class="
                                review-btn
                                review-btn-primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="reviews.php"
                            class="
                                review-btn
                                review-btn-secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     EMPTY
                ================================================== -->

                <?php if (
                    empty(
                        $reviews
                    )
                ): ?>


                    <div class="reviews-empty">


                        <div class="reviews-empty-icon">

                            📝

                        </div>


                        <h3>

                            No reviews found

                        </h3>


                        <p>

                            Customer reviews will appear here when users submit feedback for their purchased products.

                        </p>


                    </div>


                <?php else: ?>


                    <!-- =================================================
                         TABLE
                    ================================================== -->

                    <div class="reviews-table-wrapper">


                        <table class="reviews-table">


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


                                    <?php

                                    $reviewId =
                                        (int)
                                        (
                                            $item[
                                                'review_id'
                                            ]
                                            ?? 0
                                        );


                                    $rating =
                                        (int)
                                        (
                                            $item[
                                                'rating'
                                            ]
                                            ?? 0
                                        );


                                    $status =
                                        $item[
                                            'status'
                                        ]
                                        ?? 'Visible';


                                    $statusClass =
                                        reviewStatusClass(
                                            $status
                                        );


                                    $productImage =
                                        trim(
                                            $item[
                                                'product_image'
                                            ]
                                            ?? ''
                                        );


                                    $reviewImage =
                                        trim(
                                            $item[
                                                'image'
                                            ]
                                            ?? ''
                                        );

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span class="review-id">

                                                #<?= $reviewId ?>

                                            </span>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>


                                            <div class="review-customer">

                                                <strong>

                                                    <?= reviewEscape(
                                                        $item[
                                                            'customer_name'
                                                        ]
                                                        ?? 'Unknown Customer'
                                                    ) ?>

                                                </strong>


                                                <small>

                                                    <?= reviewEscape(
                                                        $item[
                                                            'customer_email'
                                                        ]
                                                        ?? '-'
                                                    ) ?>

                                                </small>

                                            </div>


                                        </td>


                                        <!-- PRODUCT -->

                                        <td>


                                            <div class="review-product">


                                                <div class="review-product-image">


                                                    <?php if (
                                                        $productImage !== ''
                                                    ): ?>


                                                        <img
                                                            src="<?= reviewEscape(
                                                                '../uploads/products/' .
                                                                rawurlencode(
                                                                    basename(
                                                                        $productImage
                                                                    )
                                                                )
                                                            ) ?>"
                                                            alt="<?= reviewEscape(
                                                                $item[
                                                                    'product_name'
                                                                ]
                                                                ?? 'Product'
                                                            ) ?>"
                                                            onerror="
                                                                this.style.display='none';
                                                                this.parentElement.innerHTML='📦';
                                                            "
                                                        >


                                                    <?php else: ?>


                                                        📦


                                                    <?php endif; ?>


                                                </div>


                                                <a
                                                    href="../product_details.php?id=<?= (int)
                                                        (
                                                            $item[
                                                                'product_id'
                                                            ]
                                                            ?? 0
                                                        ) ?>"
                                                    target="_blank"
                                                    class="review-product-name"
                                                >

                                                    <?= reviewEscape(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                        ?? 'Unknown Product'
                                                    ) ?>

                                                </a>


                                            </div>


                                        </td>


                                        <!-- RATING -->

                                        <td>


                                            <div class="review-rating">


                                                <span class="review-stars">


                                                    <?php for (
                                                        $i = 1;
                                                        $i <= 5;
                                                        $i++
                                                    ): ?>


                                                        <?= $i <= $rating
                                                            ? '★'
                                                            : '☆' ?>


                                                    <?php endfor; ?>


                                                </span>


                                                <span class="review-rating-number">

                                                    <?= $rating ?>/5

                                                </span>


                                            </div>


                                        </td>


                                        <!-- REVIEW -->

                                        <td>


                                            <div class="review-content">


                                                <div class="review-text">

                                                    <?= nl2br(
                                                        reviewEscape(
                                                            $item[
                                                                'review'
                                                            ]
                                                            ?? ''
                                                        )
                                                    ) ?>

                                                </div>


                                                <?php if (
                                                    $reviewImage !== ''
                                                ): ?>


                                                    <a
                                                        href="<?= reviewEscape(
                                                            '../uploads/products/' .
                                                            rawurlencode(
                                                                basename(
                                                                    $reviewImage
                                                                )
                                                            )
                                                        ) ?>"
                                                        target="_blank"
                                                        class="review-image-link"
                                                    >

                                                        View Image

                                                    </a>


                                                <?php endif; ?>


                                            </div>


                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="reviews.php"
                                                class="review-status-form"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= reviewEscape(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= $reviewId ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="update_status"
                                                    value="1"
                                                >


                                                <select
                                                    name="status"
                                                    class="review-status-select"
                                                    onchange="
                                                        if (
                                                            confirm(
                                                                'Change review status to ' +
                                                                this.value +
                                                                '?'
                                                            )
                                                        ) {
                                                            this.form.submit();
                                                        } else {
                                                            window.location.reload();
                                                        }
                                                    "
                                                >

                                                    <option
                                                        value="Visible"
                                                        <?= $status ===
                                                            'Visible'
                                                                ? 'selected'
                                                                : '' ?>
                                                    >

                                                        Visible

                                                    </option>


                                                    <option
                                                        value="Hidden"
                                                        <?= $status ===
                                                            'Hidden'
                                                                ? 'selected'
                                                                : '' ?>
                                                    >

                                                        Hidden

                                                    </option>


                                                </select>


                                            </form>


                                            <div
                                                style="
                                                    margin-top:
                                                        7px;
                                                "
                                            >

                                                <span
                                                    class="
                                                        review-status
                                                        <?= reviewEscape(
                                                            $statusClass
                                                        ) ?>
                                                    "
                                                >

                                                    <?= reviewEscape(
                                                        $status
                                                    ) ?>

                                                </span>

                                            </div>


                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <span class="review-date">

                                                <?= reviewEscape(
                                                    reviewDate(
                                                        $item[
                                                            'review_date'
                                                        ]
                                                        ?? null
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTION -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="reviews.php"
                                                class="review-delete-form"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to permanently delete this review?'
                                                    );
                                                "
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= reviewEscape(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="delete_review"
                                                    value="1"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= $reviewId ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="review-delete-btn"
                                                >

                                                    Delete

                                                </button>


                                            </form>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php endif; ?>


            </section>


        </div>


    </main>


</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH SYNC
    |--------------------------------------------------------------------------
    */

    function syncReviewsSidebar() {

        const main =
            document.querySelector(
                '.reviews-main'
            );


        if (!main) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            document.documentElement
                .style
                .setProperty(
                    '--review-sidebar-width',
                    '0px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        if (!sidebar) {

            document.documentElement
                .style
                .setProperty(
                    '--review-sidebar-width',
                    '260px'
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | REAL SIDEBAR WIDTH
        |--------------------------------------------------------------------------
        */

        const rect =
            sidebar
                .getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement
                .style
                .setProperty(
                    '--review-sidebar-width',
                    rect.right + 'px'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncReviewsSidebar();


            setTimeout(
                syncReviewsSidebar,
                100
            );


            setTimeout(
                syncReviewsSidebar,
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncReviewsSidebar
    );

</script>


</body>

</html>