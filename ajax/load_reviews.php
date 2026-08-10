<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX LOAD REVIEWS
|--------------------------------------------------------------------------
| File:
| ajax/load_reviews.php
|
| Purpose:
| Load product reviews dynamically using AJAX.
|
| Required:
| - product_id
|
| Optional:
| - limit
| - offset
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/database/db.php';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=UTF-8'
);


/*
|--------------------------------------------------------------------------
| ONLY GET REQUEST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'GET'
) {

    http_response_code(405);

    echo json_encode([

        'success' =>
            false,

        'message' =>
            'Invalid request method.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId =
    filter_input(
        INPUT_GET,
        'product_id',
        FILTER_VALIDATE_INT
    );


if (
    $productId === false
    ||
    $productId === null
    ||
    $productId <= 0
) {

    http_response_code(400);

    echo json_encode([

        'success' =>
            false,

        'message' =>
            'Invalid product ID.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| LIMIT
|--------------------------------------------------------------------------
*/

$limit =
    filter_input(
        INPUT_GET,
        'limit',
        FILTER_VALIDATE_INT
    );


if (
    $limit === false
    ||
    $limit === null
) {

    $limit = 10;
}


/*
|--------------------------------------------------------------------------
| LIMIT RANGE
|--------------------------------------------------------------------------
*/

$limit =
    max(
        1,
        min(
            $limit,
            50
        )
    );


/*
|--------------------------------------------------------------------------
| OFFSET
|--------------------------------------------------------------------------
*/

$offset =
    filter_input(
        INPUT_GET,
        'offset',
        FILTER_VALIDATE_INT
    );


if (
    $offset === false
    ||
    $offset === null
    ||
    $offset < 0
) {

    $offset = 0;
}


/*
|--------------------------------------------------------------------------
| LOAD REVIEWS
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | REVIEW QUERY
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                r.review_id,
                r.product_id,
                r.customer_id,
                r.rating,
                r.comment,
                r.status,
                r.review_date,

                u.name,
                u.profile_image

            FROM reviews r

            INNER JOIN users u
                ON r.customer_id = u.user_id

            WHERE r.product_id = ?

            AND r.status = 'Visible'

            ORDER BY r.review_date DESC

            LIMIT {$limit}

            OFFSET {$offset}
        ");


    $stmt->execute([

        $productId

    ]);


    $reviews =
        $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | TOTAL REVIEWS
    |--------------------------------------------------------------------------
    */

    $countStmt =
        $db->prepare("
            SELECT COUNT(*)

            FROM reviews

            WHERE product_id = ?

            AND status = 'Visible'
        ");


    $countStmt->execute([

        $productId

    ]);


    $total =
        (int)
        $countStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | RATING SUMMARY
    |--------------------------------------------------------------------------
    */

    $ratingStmt =
        $db->prepare("
            SELECT

                COALESCE(
                    AVG(rating),
                    0
                ) AS average_rating,

                COUNT(*) AS total_reviews

            FROM reviews

            WHERE product_id = ?

            AND status = 'Visible'
        ");


    $ratingStmt->execute([

        $productId

    ]);


    $ratingSummary =
        $ratingStmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | FORMAT REVIEWS
    |--------------------------------------------------------------------------
    */

    $formattedReviews = [];


    foreach (
        $reviews
        as $review
    ) {

        /*
        |--------------------------------------------------------------------------
        | PROFILE IMAGE
        |--------------------------------------------------------------------------
        */

        $profileImage = '';

        if (
            !empty(
                $review['profile_image']
            )
        ) {

            $profileImage =
                BASE_URL
                . ltrim(
                    str_replace(
                        '\\',
                        '/',
                        $review[
                            'profile_image'
                        ]
                    ),
                    '/'
                );

        }


        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        $rating =
            max(
                1,
                min(
                    5,
                    (int)
                    $review['rating']
                )
            );


        /*
        |--------------------------------------------------------------------------
        | STAR DISPLAY
        |--------------------------------------------------------------------------
        */

        $stars = '';

        for (
            $i = 1;
            $i <= 5;
            $i++
        ) {

            $stars .=
                $i <= $rating
                ? '★'
                : '☆';
        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW DATA
        |--------------------------------------------------------------------------
        */

        $formattedReviews[] = [

            'review_id' =>
                (int)
                $review['review_id'],

            'product_id' =>
                (int)
                $review['product_id'],

            'customer_id' =>
                (int)
                $review['customer_id'],

            'customer_name' =>
                $review['name'],

            'profile_image' =>
                $profileImage,

            'rating' =>
                $rating,

            'stars' =>
                $stars,

            'comment' =>
                $review['comment'],

            'status' =>
                $review['status'],

            'review_date' =>
                $review['review_date'],

            'formatted_date' =>
                formatDateTime(
                    $review['review_date']
                )
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */

    $nextOffset =
        $offset + $limit;


    $hasMore =
        $nextOffset < $total;


    /*
    |--------------------------------------------------------------------------
    | AVERAGE RATING
    |--------------------------------------------------------------------------
    */

    $averageRating =
        round(
            (float)
            (
                $ratingSummary[
                    'average_rating'
                ]
                ?? 0
            ),
            1
        );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' =>
            true,

        'message' =>
            'Reviews loaded successfully.',

        'reviews' =>
            $formattedReviews,

        'count' =>
            count(
                $formattedReviews
            ),

        'total' =>
            $total,

        'average_rating' =>
            $averageRating,

        'total_reviews' =>
            (int)
            (
                $ratingSummary[
                    'total_reviews'
                ]
                ?? 0
            ),

        'limit' =>
            $limit,

        'offset' =>
            $offset,

        'next_offset' =>
            $hasMore
            ? $nextOffset
            : null,

        'has_more' =>
            $hasMore

    ]);

    exit;


} catch (
    PDOException $e
) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    http_response_code(500);


    if (
        APP_DEBUG
    ) {

        echo json_encode([

            'success' =>
                false,

            'message' =>
                $e->getMessage()

        ]);

    } else {

        echo json_encode([

            'success' =>
                false,

            'message' =>
                'Unable to load reviews.'

        ]);
    }

    exit;
}