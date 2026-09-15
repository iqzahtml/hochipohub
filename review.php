<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VERIFIED PURCHASE REVIEW
|--------------------------------------------------------------------------
| File: review.php
|
| Features:
| - Review only from real purchased order item
| - Customer must own the order
| - Seller order must be Completed
| - One review per order_detail_id
| - Interactive 5-star rating
| - Review title
| - Review text
| - Review image upload
| - Verified Purchase badge
| - Product review summary
| - Rating breakdown
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


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


if (!function_exists('reviewImagePath')) {

    function reviewImagePath($image): string
    {
        $image = trim(
            (string) $image
        );

        if ($image === '') {
            return '';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
        ) {
            return $image;
        }

        return
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('reviewProductImage')) {

    function reviewProductImage($image): string
    {
        $image = trim(
            (string) $image
        );

        if ($image === '') {
            return 'image/logo.jpg';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
        ) {
            return $image;
        }

        return
            'uploads/products/' .
            rawurlencode(
                basename($image)
            );
    }
}


if (!function_exists('reviewCsrfToken')) {

    function reviewCsrfToken(): string
    {
        if (
            function_exists(
                'generateCsrfToken'
            )
        ) {
            return
                generateCsrfToken();
        }

        if (
            function_exists(
                'csrfToken'
            )
        ) {
            return
                csrfToken();
        }

        if (
            empty(
                $_SESSION[
                    'review_csrf_token'
                ]
            )
        ) {

            $_SESSION[
                'review_csrf_token'
            ] =
                bin2hex(
                    random_bytes(32)
                );
        }

        return
            $_SESSION[
                'review_csrf_token'
            ];
    }
}


if (!function_exists('reviewValidateCsrf')) {

    function reviewValidateCsrf($token): bool
    {
        $token =
            (string) $token;

        if (
            function_exists(
                'validateCsrfToken'
            )
        ) {
            return
                validateCsrfToken(
                    $token
                );
        }

        if (
            function_exists(
                'verifyCsrfToken'
            )
        ) {
            return
                verifyCsrfToken(
                    $token
                );
        }

        if (
            function_exists(
                'csrfToken'
            )
        ) {

            return
                hash_equals(
                    (string)
                    csrfToken(),
                    $token
                );
        }

        return
            isset(
                $_SESSION[
                    'review_csrf_token'
                ]
            ) &&
            hash_equals(
                (string)
                $_SESSION[
                    'review_csrf_token'
                ],
                $token
            );
    }
}


if (!function_exists('reviewRatingText')) {

    function reviewRatingText($rating): string
    {
        switch (
            (int) $rating
        ) {

            case 1:
                return 'Disappointed';

            case 2:
                return 'Could Be Better';

            case 3:
                return 'Good';

            case 4:
                return 'Love It';

            case 5:
                return 'Absolutely Amazing';

            default:
                return 'Select your rating';
        }
    }
}


/*
|--------------------------------------------------------------------------
| REQUEST PARAMETERS
|--------------------------------------------------------------------------
|
| Main flow:
| review.php?order_detail_id=XX
|
| product_id is still supported for review browsing.
|--------------------------------------------------------------------------
*/

$orderDetailId =
    isset(
        $_GET[
            'order_detail_id'
        ]
    )
        ? (int)
            $_GET[
                'order_detail_id'
            ]
        : 0;


$productId =
    isset(
        $_GET[
            'product_id'
        ]
    )
        ? (int)
            $_GET[
                'product_id'
            ]
        : 0;


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId =
    isset(
        $_SESSION[
            'user_id'
        ]
    )
        ? (int)
            $_SESSION[
                'user_id'
            ]
        : 0;


$currentRole =
    strtolower(
        trim(
            (string) (
                $_SESSION[
                    'role'
                ]
                ??
                $_SESSION[
                    'user_role'
                ]
                ??
                ''
            )
        )
    );


/*
|--------------------------------------------------------------------------
| PURCHASE / REVIEW CONTEXT
|--------------------------------------------------------------------------
*/

$purchase = null;

$canReview = false;

$alreadyReviewed = false;

$existingReview = null;

$reviewLockedReason = '';


/*
|--------------------------------------------------------------------------
| ORDER DETAIL MODE
|--------------------------------------------------------------------------
*/

if (
    $orderDetailId > 0
) {

    /*
    |--------------------------------------------------------------------------
    | Customer must be logged in
    |--------------------------------------------------------------------------
    */

    if (
        $userId <= 0
    ) {

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Customer account only
    |--------------------------------------------------------------------------
    */

    if (
        $currentRole !==
        'customer'
    ) {

        header(
            'Location: ' .
            BASE_URL .
            'dashboard.php'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get purchased item
    |--------------------------------------------------------------------------
    |
    | Security:
    | - order_detail must belong to current customer
    | - product must really exist in that order
    | - vendor order status is checked
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT

            od.order_detail_id,
            od.order_id,
            od.product_id,
            od.quantity,
            od.unit_price,
            od.subtotal,

            o.customer_id,
            o.order_date,
            o.order_status,
            o.completed_date,

            p.product_name,
            p.description,
            p.price,
            p.image,
            p.vendor_id,

            v.business_name,
            v.business_logo,

            c.category_name,

            vo.vendor_order_id,
            vo.vendor_status,
            vo.completed_at,

            r.review_id,
            r.rating AS existing_rating,
            r.review_title AS existing_review_title,
            r.review AS existing_review_text,
            r.image AS existing_review_image,
            r.helpful_count AS existing_helpful_count,
            r.review_date AS existing_review_date

        FROM order_details od

        INNER JOIN orders o
            ON od.order_id =
               o.order_id

        INNER JOIN products p
            ON od.product_id =
               p.product_id

        INNER JOIN vendors v
            ON p.vendor_id =
               v.vendor_id

        INNER JOIN categories c
            ON p.category_id =
               c.category_id

        LEFT JOIN vendor_orders vo
            ON vo.order_id =
               od.order_id
           AND vo.vendor_id =
               p.vendor_id

        LEFT JOIN reviews r
            ON r.order_detail_id =
               od.order_detail_id

        WHERE od.order_detail_id = ?
          AND o.customer_id = ?

        LIMIT 1
    ");


    $stmt->execute([
        $orderDetailId,
        $userId
    ]);


    $purchase =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | Invalid / чуж purchase
    |--------------------------------------------------------------------------
    */

    if (!$purchase) {

        $_SESSION['error'] =
            'This purchased item could not be found.';

        header(
            'Location: ' .
            BASE_URL .
            'order.php'
        );

        exit;
    }


    $productId =
        (int)
        $purchase[
            'product_id'
        ];


    /*
    |--------------------------------------------------------------------------
    | Already reviewed?
    |--------------------------------------------------------------------------
    */

    if (
        !empty(
            $purchase[
                'review_id'
            ]
        )
    ) {

        $alreadyReviewed =
            true;


        $existingReview = [
            'review_id' =>
                $purchase[
                    'review_id'
                ],

            'rating' =>
                $purchase[
                    'existing_rating'
                ],

            'review_title' =>
                $purchase[
                    'existing_review_title'
                ],

            'review' =>
                $purchase[
                    'existing_review_text'
                ],

            'image' =>
                $purchase[
                    'existing_review_image'
                ],

            'helpful_count' =>
                $purchase[
                    'existing_helpful_count'
                ],

            'review_date' =>
                $purchase[
                    'existing_review_date'
                ]
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Eligibility
    |--------------------------------------------------------------------------
    */

    $vendorStatus =
        strtolower(
            trim(
                (string) (
                    $purchase[
                        'vendor_status'
                    ]
                    ?? ''
                )
            )
        );


    if ($alreadyReviewed) {

        $canReview = false;

        $reviewLockedReason =
            'You have already reviewed this purchase.';

    } elseif (
        $vendorStatus ===
        'completed'
    ) {

        $canReview = true;

    } elseif (
        $vendorStatus ===
        'cancelled'
    ) {

        $canReview = false;

        $reviewLockedReason =
            'Cancelled purchases cannot be reviewed.';

    } else {

        $canReview = false;

        $reviewLockedReason =
            'Your review will unlock after the seller marks this order as Completed.';
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT
|--------------------------------------------------------------------------
*/

if (
    $productId <= 0
) {

    header(
        'Location: ' .
        BASE_URL .
        'product.php'
    );

    exit;
}


$stmt = $db->prepare("
    SELECT

        p.*,

        v.business_name,
        v.business_logo,

        c.category_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id =
           v.vendor_id

    INNER JOIN categories c
        ON p.category_id =
           c.category_id

    WHERE p.product_id = ?

    LIMIT 1
");


$stmt->execute([
    $productId
]);


$product =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$product) {

    header(
        'Location: ' .
        BASE_URL .
        'product.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| POST - SUBMIT REVIEW
|--------------------------------------------------------------------------
*/

if (
    $_SERVER[
        'REQUEST_METHOD'
    ] === 'POST'
) {

    /*
    |--------------------------------------------------------------------------
    | Login & role
    |--------------------------------------------------------------------------
    */

    if (
        $userId <= 0 ||
        $currentRole !==
        'customer'
    ) {

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Must come from purchased order detail
    |--------------------------------------------------------------------------
    */

    if (
        $orderDetailId <= 0 ||
        !$purchase
    ) {

        $_SESSION[
            'review_error'
        ] =
            'A verified purchase is required before submitting a review.';

        header(
            'Location: ' .
            BASE_URL .
            'product_details.php?id=' .
            $productId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $csrfToken =
        $_POST[
            'csrf_token'
        ]
        ?? '';


    if (
        !reviewValidateCsrf(
            $csrfToken
        )
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Invalid security token. Please refresh the page and try again.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Re-check eligibility from database
    |--------------------------------------------------------------------------
    |
    | Do NOT trust the UI.
    |--------------------------------------------------------------------------
    */

    $eligibilityStmt =
        $db->prepare("
            SELECT

                od.order_detail_id,
                od.order_id,
                od.product_id,

                o.customer_id,

                p.vendor_id,

                vo.vendor_status,

                r.review_id

            FROM order_details od

            INNER JOIN orders o
                ON od.order_id =
                   o.order_id

            INNER JOIN products p
                ON od.product_id =
                   p.product_id

            LEFT JOIN vendor_orders vo
                ON vo.order_id =
                   od.order_id
               AND vo.vendor_id =
                   p.vendor_id

            LEFT JOIN reviews r
                ON r.order_detail_id =
                   od.order_detail_id

            WHERE od.order_detail_id = ?
              AND o.customer_id = ?

            LIMIT 1
        ");


    $eligibilityStmt->execute([
        $orderDetailId,
        $userId
    ]);


    $eligibility =
        $eligibilityStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$eligibility) {

        $_SESSION[
            'review_error'
        ] =
            'This purchase could not be verified.';

        header(
            'Location: ' .
            BASE_URL .
            'order.php'
        );

        exit;
    }


    if (
        (int)
        $eligibility[
            'product_id'
        ] !==
        $productId
    ) {

        $_SESSION[
            'review_error'
        ] =
            'The selected product does not match this purchase.';

        header(
            'Location: ' .
            BASE_URL .
            'order.php'
        );

        exit;
    }


    if (
        !empty(
            $eligibility[
                'review_id'
            ]
        )
    ) {

        $_SESSION[
            'review_error'
        ] =
            'You have already reviewed this purchased item.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    if (
        strtolower(
            trim(
                (string) (
                    $eligibility[
                        'vendor_status'
                    ]
                    ?? ''
                )
            )
        ) !==
        'completed'
    ) {

        $_SESSION[
            'review_error'
        ] =
            'You can review this product after the seller order is completed.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | INPUTS
    |--------------------------------------------------------------------------
    */

    $rating =
        (int) (
            $_POST[
                'rating'
            ]
            ?? 0
        );


    $reviewTitle =
        trim(
            (string) (
                $_POST[
                    'review_title'
                ]
                ?? ''
            )
        );


    $reviewText =
        trim(
            (string) (
                $_POST[
                    'review'
                ]
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE RATING
    |--------------------------------------------------------------------------
    */

    if (
        $rating < 1 ||
        $rating > 5
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Please select a rating from 1 to 5 stars.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE TITLE
    |--------------------------------------------------------------------------
    */

    if (
        $reviewTitle === ''
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Please add a short title for your review.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    if (
        mb_strlen(
            $reviewTitle
        ) > 150
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Review title must be 150 characters or less.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE REVIEW TEXT
    |--------------------------------------------------------------------------
    */

    if (
        $reviewText === ''
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Please tell us about your experience.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    if (
        mb_strlen(
            $reviewText
        ) < 10
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Your review is a little too short. Please write at least 10 characters.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    if (
        mb_strlen(
            $reviewText
        ) > 2000
    ) {

        $_SESSION[
            'review_error'
        ] =
            'Review must be 2000 characters or less.';

        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    $reviewImage =
        null;


    if (
        isset(
            $_FILES[
                'review_image'
            ]
        ) &&
        isset(
            $_FILES[
                'review_image'
            ][
                'error'
            ]
        ) &&
        $_FILES[
            'review_image'
        ][
            'error'
        ] !==
        UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES[
                'review_image'
            ];


        if (
            $file[
                'error'
            ] !==
            UPLOAD_ERR_OK
        ) {

            $_SESSION[
                'review_error'
            ] =
                'The review image could not be uploaded.';

            header(
                'Location: ' .
                BASE_URL .
                'review.php?order_detail_id=' .
                $orderDetailId
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Maximum 5 MB
        |--------------------------------------------------------------------------
        */

        if (
            (int)
            $file[
                'size'
            ] >
            5 * 1024 * 1024
        ) {

            $_SESSION[
                'review_error'
            ] =
                'Review image must be 5MB or smaller.';

            header(
                'Location: ' .
                BASE_URL .
                'review.php?order_detail_id=' .
                $orderDetailId
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate MIME
        |--------------------------------------------------------------------------
        */

        $allowedTypes = [
            'image/jpeg' =>
                'jpg',

            'image/png' =>
                'png',

            'image/webp' =>
                'webp'
        ];


        $mimeType = '';


        if (
            function_exists(
                'finfo_open'
            )
        ) {

            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );


            if ($finfo) {

                $mimeType =
                    (string)
                    finfo_file(
                        $finfo,
                        $file[
                            'tmp_name'
                        ]
                    );

                finfo_close(
                    $finfo
                );
            }
        }


        if (
            $mimeType === ''
        ) {

            $imageInfo =
                @getimagesize(
                    $file[
                        'tmp_name'
                    ]
                );


            $mimeType =
                $imageInfo[
                    'mime'
                ]
                ?? '';
        }


        if (
            !isset(
                $allowedTypes[
                    $mimeType
                ]
            )
        ) {

            $_SESSION[
                'review_error'
            ] =
                'Only JPG, PNG or WEBP images are allowed.';

            header(
                'Location: ' .
                BASE_URL .
                'review.php?order_detail_id=' .
                $orderDetailId
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Save under existing uploads/products folder
        |--------------------------------------------------------------------------
        |
        | This keeps compatibility with your existing admin review code.
        |--------------------------------------------------------------------------
        */

        $uploadDirectory =
            __DIR__ .
            '/uploads/products/';


        if (
            !is_dir(
                $uploadDirectory
            )
        ) {

            @mkdir(
                $uploadDirectory,
                0775,
                true
            );
        }


        if (
            !is_dir(
                $uploadDirectory
            ) ||
            !is_writable(
                $uploadDirectory
            )
        ) {

            $_SESSION[
                'review_error'
            ] =
                'Review image upload directory is unavailable.';

            header(
                'Location: ' .
                BASE_URL .
                'review.php?order_detail_id=' .
                $orderDetailId
            );

            exit;
        }


        $extension =
            $allowedTypes[
                $mimeType
            ];


        $reviewImage =
            'review_' .
            $userId .
            '_' .
            $orderDetailId .
            '_' .
            bin2hex(
                random_bytes(6)
            ) .
            '.' .
            $extension;


        $destination =
            $uploadDirectory .
            $reviewImage;


        if (
            !move_uploaded_file(
                $file[
                    'tmp_name'
                ],
                $destination
            )
        ) {

            $_SESSION[
                'review_error'
            ] =
                'Failed to save the review image.';

            header(
                'Location: ' .
                BASE_URL .
                'review.php?order_detail_id=' .
                $orderDetailId
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT REVIEW
    |--------------------------------------------------------------------------
    */

    try {

        $db->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Final duplicate lock
        |--------------------------------------------------------------------------
        */

        $duplicateStmt =
            $db->prepare("
                SELECT review_id

                FROM reviews

                WHERE order_detail_id = ?

                LIMIT 1

                FOR UPDATE
            ");


        $duplicateStmt->execute([
            $orderDetailId
        ]);


        if (
            $duplicateStmt->fetch()
        ) {

            throw new RuntimeException(
                'This purchased item has already been reviewed.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Insert verified purchase review
        |--------------------------------------------------------------------------
        */

        $insertStmt =
            $db->prepare("
                INSERT INTO reviews
                (
                    customer_id,
                    product_id,
                    order_id,
                    order_detail_id,
                    rating,
                    review_title,
                    review,
                    image,
                    helpful_count,
                    status,
                    review_date
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    0,
                    'Visible',
                    NOW()
                )
            ");


        $insertStmt->execute([
            $userId,

            $productId,

            (int)
            $purchase[
                'order_id'
            ],

            $orderDetailId,

            $rating,

            $reviewTitle,

            $reviewText,

            $reviewImage
        ]);


        $db->commit();


        $_SESSION[
            'review_success'
        ] =
            'Your verified purchase review has been published successfully. ✨';


        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId .
            '&submitted=1'
        );

        exit;


    } catch (Throwable $e) {

        if (
            $db->inTransaction()
        ) {

            $db->rollBack();
        }


        /*
        |--------------------------------------------------------------------------
        | Remove image if database insert failed
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $reviewImage
            )
        ) {

            $imageFile =
                __DIR__ .
                '/uploads/products/' .
                basename(
                    $reviewImage
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


        error_log(
            'Review submission error: ' .
            $e->getMessage()
        );


        $_SESSION[
            'review_error'
        ] =
            $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Your review could not be submitted. Please try again.';


        header(
            'Location: ' .
            BASE_URL .
            'review.php?order_detail_id=' .
            $orderDetailId
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| REFRESH EXISTING REVIEW AFTER SUBMIT
|--------------------------------------------------------------------------
*/

if (
    $orderDetailId > 0
) {

    $existingStmt =
        $db->prepare("
            SELECT

                review_id,
                rating,
                review_title,
                review,
                image,
                helpful_count,
                review_date

            FROM reviews

            WHERE order_detail_id = ?
              AND customer_id = ?

            LIMIT 1
        ");


    $existingStmt->execute([
        $orderDetailId,
        $userId
    ]);


    $currentExistingReview =
        $existingStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (
        $currentExistingReview
    ) {

        $alreadyReviewed =
            true;

        $canReview =
            false;

        $existingReview =
            $currentExistingReview;
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT REVIEWS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        r.review_id,
        r.customer_id,
        r.product_id,
        r.order_id,
        r.order_detail_id,
        r.rating,
        r.review_title,
        r.review,
        r.image,
        r.helpful_count,
        r.review_date,

        u.name AS customer_name,
        u.profile_image,

        o.order_date

    FROM reviews r

    INNER JOIN users u
        ON r.customer_id =
           u.user_id

    LEFT JOIN orders o
        ON r.order_id =
           o.order_id

    WHERE r.product_id = ?
      AND r.status = 'Visible'

    ORDER BY
        r.review_date DESC
");


$stmt->execute([
    $productId
]);


$reviews =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


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
        ) AS average_rating,

        SUM(
            CASE
                WHEN rating = 5
                THEN 1
                ELSE 0
            END
        ) AS rating_5,

        SUM(
            CASE
                WHEN rating = 4
                THEN 1
                ELSE 0
            END
        ) AS rating_4,

        SUM(
            CASE
                WHEN rating = 3
                THEN 1
                ELSE 0
            END
        ) AS rating_3,

        SUM(
            CASE
                WHEN rating = 2
                THEN 1
                ELSE 0
            END
        ) AS rating_2,

        SUM(
            CASE
                WHEN rating = 1
                THEN 1
                ELSE 0
            END
        ) AS rating_1

    FROM reviews

    WHERE product_id = ?
      AND status = 'Visible'
");


$stmt->execute([
    $productId
]);


$reviewSummary =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$reviewSummary) {

    $reviewSummary = [
        'total_reviews' => 0,
        'average_rating' => 0,
        'rating_5' => 0,
        'rating_4' => 0,
        'rating_3' => 0,
        'rating_2' => 0,
        'rating_1' => 0
    ];
}


$totalReviews =
    (int) (
        $reviewSummary[
            'total_reviews'
        ]
        ?? 0
    );


$averageRating =
    (float) (
        $reviewSummary[
            'average_rating'
        ]
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

$reviewError =
    $_SESSION[
        'review_error'
    ]
    ?? '';


$reviewSuccess =
    $_SESSION[
        'review_success'
    ]
    ?? '';


unset(
    $_SESSION[
        'review_error'
    ],
    $_SESSION[
        'review_success'
    ]
);


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

$productImage =
    reviewProductImage(
        $product[
            'image'
        ]
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| SIDEBAR COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = 0;
$wishlistCount = 0;


if (
    $userId > 0 &&
    $currentRole ===
    'customer'
) {

    try {

        $stmt = $db->prepare("
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                )

            FROM cart

            WHERE customer_id = ?
        ");


        $stmt->execute([
            $userId
        ]);


        $cartCount =
            (int)
            $stmt->fetchColumn();

    } catch (Throwable $e) {

        $cartCount = 0;
    }


    try {

        $stmt = $db->prepare("
            SELECT COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");


        $stmt->execute([
            $userId
        ]);


        $wishlistCount =
            (int)
            $stmt->fetchColumn();

    } catch (Throwable $e) {

        $wishlistCount = 0;
    }
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Review ' .
    $product[
        'product_name'
    ] .
    ' - HochipoHub';


$hideSiteMainWrapper =
    true;


$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


if (
    $userId > 0 &&
    $currentRole ===
    'customer'
) {

    require_once __DIR__ .
        '/includes/customer_sidebar.php';
}

?>


<style>

* {
    box-sizing:
        border-box;
}


/* =========================================================
   PAGE
========================================================= */

.hh-review-page {

    min-height:
        100vh;

    padding:
        38px 24px 80px;

    color:
        #172b4d;

    background:
        radial-gradient(
            circle at 95% 3%,
            rgba(
                124,
                58,
                237,
                .10
            ),
            transparent 24%
        ),
        radial-gradient(
            circle at 3% 45%,
            rgba(
                37,
                99,
                235,
                .07
            ),
            transparent 23%
        ),
        linear-gradient(
            180deg,
            #f5f8ff 0%,
            #fafcff 50%,
            #ffffff 100%
        );

    font-family:
        Inter,
        Arial,
        sans-serif;
}


.hh-review-container {

    width:
        100%;

    max-width:
        1320px;

    margin:
        0 auto;
}


/* =========================================================
   BACK
========================================================= */

.hh-review-back {

    margin-bottom:
        18px;

    display:
        flex;

    flex-wrap:
        wrap;

    align-items:
        center;

    gap:
        9px;
}


.hh-review-back a {

    min-height:
        35px;

    padding:
        0 13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #516b8d;

    background:
        #ffffff;

    border:
        1px solid
        #dfe7f2;

    border-radius:
        11px;

    font-size:
        9px;

    font-weight:
        800;

    text-decoration:
        none;
}


/* =========================================================
   HERO
========================================================= */

.hh-review-hero {

    position:
        relative;

    overflow:
        hidden;

    margin-bottom:
        23px;

    min-height:
        320px;

    padding:
        42px;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        400px;

    align-items:
        center;

    gap:
        42px;

    color:
        #ffffff;

    background:
        linear-gradient(
            120deg,
            #25145e 0%,
            #5831b7 44%,
            #2563eb 100%
        );

    border-radius:
        30px;

    box-shadow:
        0 23px 55px
        rgba(
            73,
            47,
            166,
            .18
        );
}


.hh-review-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        390px;

    height:
        390px;

    right:
        -175px;

    top:
        -200px;

    border-radius:
        50%;

    background:
        rgba(
            255,
            255,
            255,
            .08
        );
}


.hh-review-hero::after {

    content:
        "★";

    position:
        absolute;

    right:
        33%;

    bottom:
        -82px;

    color:
        rgba(
            255,
            255,
            255,
            .055
        );

    font-size:
        240px;

    transform:
        rotate(
            -12deg
        );
}


.hh-review-hero-copy {

    position:
        relative;

    z-index:
        2;
}


.hh-review-eyebrow {

    width:
        fit-content;

    margin-bottom:
        14px;

    padding:
        8px 13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .19
        );

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        850;

    letter-spacing:
        .7px;

    text-transform:
        uppercase;
}


.hh-review-hero h1 {

    max-width:
        750px;

    margin:
        0 0 13px;

    font-size:
        clamp(
            34px,
            5vw,
            52px
        );

    line-height:
        1.04;

    letter-spacing:
        -1.8px;

    font-weight:
        850;
}


.hh-review-hero-copy > p {

    max-width:
        680px;

    margin:
        0;

    color:
        rgba(
            255,
            255,
            255,
            .76
        );

    font-size:
        12px;

    line-height:
        1.8;
}


/* =========================================================
   PRODUCT HERO CARD
========================================================= */

.hh-review-product-card {

    position:
        relative;

    z-index:
        2;

    padding:
        18px;

    display:
        grid;

    grid-template-columns:
        105px
        minmax(0, 1fr);

    align-items:
        center;

    gap:
        15px;

    color:
        #16345f;

    background:
        rgba(
            255,
            255,
            255,
            .95
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .45
        );

    border-radius:
        22px;

    box-shadow:
        0 15px 34px
        rgba(
            20,
            25,
            74,
            .16
        );
}


.hh-review-product-img {

    width:
        105px;

    height:
        105px;

    padding:
        8px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    overflow:
        hidden;

    background:
        #f4f8ff;

    border:
        1px solid
        #e1eaf7;

    border-radius:
        17px;
}


.hh-review-product-img img {

    width:
        100%;

    height:
        100%;

    object-fit:
        contain;
}


.hh-review-product-info {

    min-width:
        0;
}


.hh-review-product-category {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #7c3aed;

    font-size:
        8px;

    font-weight:
        850;

    text-transform:
        uppercase;

    letter-spacing:
        .7px;
}


.hh-review-product-info h3 {

    margin:
        0 0 5px;

    color:
        #11315e;

    font-size:
        14px;

    line-height:
        1.35;
}


.hh-review-product-info p {

    margin:
        0 0 8px;

    color:
        #8293aa;

    font-size:
        9px;
}


.hh-verified-pill {

    padding:
        6px 9px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    color:
        #047857;

    background:
        #ecfdf5;

    border:
        1px solid
        #a7f3d0;

    border-radius:
        999px;

    font-size:
        8px;

    font-weight:
        850;
}


/* =========================================================
   FLASH
========================================================= */

.hh-review-alert {

    margin-bottom:
        20px;

    padding:
        15px 17px;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    border-radius:
        15px;

    font-size:
        10px;

    font-weight:
        750;
}


.hh-review-alert.error {

    color:
        #b42318;

    background:
        #fff3f2;

    border:
        1px solid
        #fecaca;
}


.hh-review-alert.success {

    color:
        #067647;

    background:
        #ecfdf3;

    border:
        1px solid
        #a7f3d0;
}


/* =========================================================
   REVIEW GRID
========================================================= */

.hh-review-main-grid {

    margin-bottom:
        25px;

    display:
        grid;

    grid-template-columns:
        minmax(
            0,
            1.28fr
        )
        minmax(
            330px,
            .72fr
        );

    gap:
        22px;

    align-items:
        start;
}


/* =========================================================
   FORM CARD
========================================================= */

.hh-review-form-card,
.hh-rating-card,
.hh-all-reviews {

    background:
        #ffffff;

    border:
        1px solid
        #dfe8f4;

    border-radius:
        24px;

    box-shadow:
        0 16px 42px
        rgba(
            40,
            75,
            128,
            .065
        );
}


.hh-review-form-card {

    padding:
        30px;
}


.hh-review-title-row {

    margin-bottom:
        24px;

    display:
        flex;

    align-items:
        center;

    gap:
        13px;
}


.hh-review-title-icon {

    width:
        46px;

    height:
        46px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #7c3aed;

    background:
        #f4efff;

    border:
        1px solid
        #e7dcff;

    border-radius:
        14px;

    font-size:
        19px;
}


.hh-review-title-row span {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #899ab0;

    font-size:
        8px;

    font-weight:
        850;

    letter-spacing:
        .8px;

    text-transform:
        uppercase;
}


.hh-review-title-row h2 {

    margin:
        0;

    color:
        #153661;

    font-size:
        21px;
}


/* =========================================================
   STARS
========================================================= */

.hh-star-section {

    margin-bottom:
        23px;

    padding:
        23px;

    text-align:
        center;

    background:
        linear-gradient(
            135deg,
            #faf8ff,
            #f3f7ff
        );

    border:
        1px solid
        #e4e3f7;

    border-radius:
        19px;
}


.hh-star-label {

    margin-bottom:
        13px;

    display:
        block;

    color:
        #667895;

    font-size:
        10px;

    font-weight:
        800;
}


.hh-star-picker {

    display:
        inline-flex;

    flex-direction:
        row-reverse;

    justify-content:
        center;

    gap:
        7px;
}


.hh-star-picker input {

    position:
        absolute;

    opacity:
        0;

    pointer-events:
        none;
}


.hh-star-picker label {

    cursor:
        pointer;

    color:
        #dbe1eb;

    font-size:
        clamp(
            38px,
            5vw,
            54px
        );

    line-height:
        1;

    transition:
        transform .16s ease,
        color .16s ease,
        filter .16s ease;
}


.hh-star-picker label:hover,
.hh-star-picker label:hover ~ label {

    color:
        #f7b529;

    transform:
        translateY(
            -3px
        );

    filter:
        drop-shadow(
            0 5px 8px
            rgba(
                245,
                158,
                11,
                .25
            )
        );
}


.hh-star-picker input:checked ~ label {

    color:
        #f7b529;

    filter:
        drop-shadow(
            0 5px 8px
            rgba(
                245,
                158,
                11,
                .23
            )
        );
}


.hh-rating-reaction {

    min-height:
        25px;

    margin-top:
        12px;

    color:
        #5633a8;

    font-size:
        11px;

    font-weight:
        850;
}


/* =========================================================
   INPUTS
========================================================= */

.hh-form-group {

    margin-bottom:
        20px;
}


.hh-form-group label {

    margin-bottom:
        8px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    color:
        #213f68;

    font-size:
        10px;

    font-weight:
        800;
}


.hh-form-group label small {

    color:
        #94a3b8;

    font-size:
        8px;

    font-weight:
        650;
}


.hh-review-input,
.hh-review-textarea {

    width:
        100%;

    border:
        1px solid
        #dce5f0;

    outline:
        none;

    color:
        #18385f;

    background:
        #fbfdff;

    border-radius:
        14px;

    font-family:
        inherit;

    font-size:
        11px;

    transition:
        .18s ease;
}


.hh-review-input {

    height:
        47px;

    padding:
        0 15px;
}


.hh-review-textarea {

    min-height:
        145px;

    padding:
        15px;

    resize:
        vertical;

    line-height:
        1.7;
}


.hh-review-input:focus,
.hh-review-textarea:focus {

    border-color:
        #8b5cf6;

    background:
        #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(
            124,
            58,
            237,
            .08
        );
}


/* =========================================================
   PHOTO UPLOAD
========================================================= */

.hh-photo-upload {

    position:
        relative;

    min-height:
        150px;

    padding:
        22px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    text-align:
        center;

    background:
        #f9fbff;

    border:
        1.5px dashed
        #cbd8e9;

    border-radius:
        17px;

    cursor:
        pointer;

    transition:
        .18s ease;
}


.hh-photo-upload:hover {

    border-color:
        #8b5cf6;

    background:
        #faf8ff;
}


.hh-photo-upload input {

    position:
        absolute;

    inset:
        0;

    width:
        100%;

    height:
        100%;

    opacity:
        0;

    cursor:
        pointer;
}


.hh-photo-upload-icon {

    width:
        44px;

    height:
        44px;

    margin:
        0 auto 10px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #6d4bd1;

    background:
        #eee9ff;

    border-radius:
        13px;

    font-size:
        19px;
}


.hh-photo-upload strong {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #24456e;

    font-size:
        10px;
}


.hh-photo-upload span {

    color:
        #93a1b5;

    font-size:
        8px;
}


.hh-photo-preview {

    display:
        none;

    margin-top:
        12px;

    overflow:
        hidden;

    width:
        130px;

    height:
        130px;

    padding:
        5px;

    background:
        #ffffff;

    border:
        1px solid
        #dce5f0;

    border-radius:
        15px;
}


.hh-photo-preview img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    border-radius:
        10px;
}


/* =========================================================
   SUBMIT
========================================================= */

.hh-submit-review {

    width:
        100%;

    min-height:
        50px;

    margin-top:
        5px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    color:
        #ffffff;

    background:
        linear-gradient(
            105deg,
            #7c3aed,
            #2563eb
        );

    border:
        none;

    border-radius:
        15px;

    box-shadow:
        0 11px 27px
        rgba(
            95,
            57,
            202,
            .20
        );

    cursor:
        pointer;

    font-size:
        10px;

    font-weight:
        850;

    transition:
        .2s ease;
}


.hh-submit-review:hover {

    transform:
        translateY(
            -2px
        );

    box-shadow:
        0 15px 32px
        rgba(
            95,
            57,
            202,
            .26
        );
}


/* =========================================================
   LOCK / REVIEWED
========================================================= */

.hh-review-state {

    padding:
        34px 26px;

    text-align:
        center;

    background:
        linear-gradient(
            135deg,
            #f8faff,
            #f4f7fc
        );

    border:
        1px solid
        #e0e7f1;

    border-radius:
        19px;
}


.hh-review-state-icon {

    width:
        65px;

    height:
        65px;

    margin:
        0 auto 14px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #64748b;

    background:
        #ffffff;

    border:
        1px solid
        #e1e7ef;

    border-radius:
        20px;

    font-size:
        26px;
}


.hh-review-state.reviewed
.hh-review-state-icon {

    color:
        #047857;

    background:
        #ecfdf5;

    border-color:
        #a7f3d0;
}


.hh-review-state h3 {

    margin:
        0 0 7px;

    color:
        #1a3c66;

    font-size:
        17px;
}


.hh-review-state p {

    max-width:
        480px;

    margin:
        0 auto;

    color:
        #8191a7;

    font-size:
        10px;

    line-height:
        1.7;
}


.hh-existing-stars {

    margin:
        13px 0 8px;

    color:
        #f59e0b;

    font-size:
        22px;

    letter-spacing:
        2px;
}


/* =========================================================
   RATING SUMMARY
========================================================= */

.hh-rating-card {

    padding:
        27px;
}


.hh-rating-score {

    margin-bottom:
        24px;

    text-align:
        center;
}


.hh-rating-score strong {

    display:
        block;

    color:
        #133767;

    font-size:
        50px;

    line-height:
        1;

    letter-spacing:
        -2px;
}


.hh-rating-score-stars {

    margin:
        8px 0 5px;

    color:
        #f59e0b;

    font-size:
        17px;

    letter-spacing:
        2px;
}


.hh-rating-score span {

    color:
        #8a9bb1;

    font-size:
        9px;
}


.hh-breakdown-row {

    margin-bottom:
        11px;

    display:
        grid;

    grid-template-columns:
        38px
        minmax(0, 1fr)
        35px;

    align-items:
        center;

    gap:
        9px;

    color:
        #71849f;

    font-size:
        9px;
}


.hh-breakdown-bar {

    height:
        7px;

    overflow:
        hidden;

    background:
        #edf1f6;

    border-radius:
        999px;
}


.hh-breakdown-fill {

    height:
        100%;

    background:
        linear-gradient(
            90deg,
            #f59e0b,
            #f8c44e
        );

    border-radius:
        inherit;
}


/* =========================================================
   ALL REVIEWS
========================================================= */

.hh-all-reviews {

    padding:
        30px;
}


.hh-all-review-heading {

    margin-bottom:
        23px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;
}


.hh-all-review-heading span {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #8b9cb1;

    font-size:
        8px;

    font-weight:
        850;

    letter-spacing:
        .8px;

    text-transform:
        uppercase;
}


.hh-all-review-heading h2 {

    margin:
        0;

    color:
        #163962;

    font-size:
        21px;
}


.hh-review-total-pill {

    padding:
        7px 11px;

    color:
        #5b34b5;

    background:
        #f2edff;

    border:
        1px solid
        #e4d9ff;

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        850;
}


/* =========================================================
   REVIEW CARD
========================================================= */

.hh-customer-review {

    padding:
        22px 0;

    border-bottom:
        1px solid
        #edf1f6;
}


.hh-customer-review:last-child {

    border-bottom:
        none;
}


.hh-review-user {

    margin-bottom:
        12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;
}


.hh-review-user-left {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;
}


.hh-avatar {

    width:
        42px;

    height:
        42px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #2563eb
        );

    border-radius:
        50%;

    font-size:
        14px;

    font-weight:
        850;
}


.hh-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}


.hh-review-user-name {

    color:
        #183a63;

    font-size:
        11px;

    font-weight:
        850;
}


.hh-review-verified {

    margin-top:
        4px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        4px;

    color:
        #047857;

    font-size:
        8px;

    font-weight:
        800;
}


.hh-review-date {

    color:
        #91a0b3;

    font-size:
        8px;
}


.hh-review-card-stars {

    margin-bottom:
        7px;

    color:
        #f59e0b;

    font-size:
        13px;

    letter-spacing:
        1px;
}


.hh-customer-review h3 {

    margin:
        0 0 8px;

    color:
        #1a3b66;

    font-size:
        13px;
}


.hh-customer-review-text {

    margin:
        0;

    color:
        #657a96;

    font-size:
        10px;

    line-height:
        1.8;
}


.hh-review-photo {

    width:
        150px;

    height:
        150px;

    margin-top:
        13px;

    overflow:
        hidden;

    padding:
        5px;

    background:
        #f7faff;

    border:
        1px solid
        #dfe8f3;

    border-radius:
        15px;
}


.hh-review-photo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    border-radius:
        10px;
}


.hh-helpful {

    margin-top:
        13px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        #71839c;

    font-size:
        8px;

    font-weight:
        750;
}


/* =========================================================
   EMPTY
========================================================= */

.hh-review-empty {

    padding:
        45px 20px;

    text-align:
        center;

    color:
        #8a9bb1;
}


.hh-review-empty-icon {

    width:
        60px;

    height:
        60px;

    margin:
        0 auto 13px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #f59e0b;

    background:
        #fff9e8;

    border-radius:
        19px;

    font-size:
        25px;
}


.hh-review-empty h3 {

    margin:
        0 0 6px;

    color:
        #29476d;

    font-size:
        15px;
}


.hh-review-empty p {

    margin:
        0;

    font-size:
        9px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1050px
) {

    .hh-review-hero {

        grid-template-columns:
            1fr;
    }


    .hh-review-product-card {

        max-width:
            470px;
    }


    .hh-review-main-grid {

        grid-template-columns:
            1fr;
    }
}


@media (
    max-width: 650px
) {

    .hh-review-page {

        padding:
            20px 14px 60px;
    }


    .hh-review-hero {

        padding:
            30px 23px;

        border-radius:
            23px;
    }


    .hh-review-hero h1 {

        font-size:
            34px;
    }


    .hh-review-product-card {

        grid-template-columns:
            80px
            minmax(0, 1fr);
    }


    .hh-review-product-img {

        width:
            80px;

        height:
            80px;
    }


    .hh-review-form-card,
    .hh-rating-card,
    .hh-all-reviews {

        padding:
            21px;
    }


    .hh-star-picker {

        gap:
            3px;
    }


    .hh-star-picker label {

        font-size:
            40px;
    }


    .hh-review-user {

        align-items:
            flex-start;

        flex-direction:
            column;
    }
}

</style>


<main class="hh-review-page">

    <div class="hh-review-container">


        <!-- =====================================================
             BACK BUTTONS
        ====================================================== -->

        <div class="hh-review-back">

            <?php if (
                $purchase
            ): ?>

                <a
                    href="<?= reviewEscape(
                        BASE_URL
                    ) ?>order_details.php?id=<?= (int)
                        $purchase[
                            'order_id'
                        ] ?>"
                >

                    <i class="bi bi-arrow-left"></i>

                    Back to Order

                </a>

            <?php endif; ?>


            <a
                href="<?= reviewEscape(
                    BASE_URL
                ) ?>product_details.php?id=<?= (int)
                    $productId ?>"
            >

                <i class="bi bi-box-seam"></i>

                View Product

            </a>

        </div>


        <!-- =====================================================
             HERO
        ====================================================== -->

        <section class="hh-review-hero">

            <div class="hh-review-hero-copy">

                <div class="hh-review-eyebrow">

                    <i
                        class="bi bi-patch-check-fill"
                    ></i>

                    Verified Purchase Review

                </div>


                <h1>

                    Tell the story behind your purchase.

                </h1>


                <p>

                    Your experience helps the HochipoHub
                    community shop with confidence. Rate the
                    product, share what stood out and add a
                    real photo if you want.

                </p>

            </div>


            <div class="hh-review-product-card">

                <div class="hh-review-product-img">

                    <img
                        src="<?= reviewEscape(
                            $productImage
                        ) ?>"
                        alt="<?= reviewEscape(
                            $product[
                                'product_name'
                            ]
                        ) ?>"
                    >

                </div>


                <div class="hh-review-product-info">

                    <span class="hh-review-product-category">

                        <?= reviewEscape(
                            $product[
                                'category_name'
                            ]
                        ) ?>

                    </span>


                    <h3>

                        <?= reviewEscape(
                            $product[
                                'product_name'
                            ]
                        ) ?>

                    </h3>


                    <p>

                        <?= reviewEscape(
                            $product[
                                'business_name'
                            ]
                        ) ?>

                    </p>


                    <?php if (
                        $purchase
                    ): ?>

                        <span class="hh-verified-pill">

                            <i
                                class="bi bi-patch-check-fill"
                            ></i>

                            Verified Purchase

                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </section>


        <!-- =====================================================
             FLASH
        ====================================================== -->

        <?php if (
            $reviewError !== ''
        ): ?>

            <div class="hh-review-alert error">

                <i
                    class="bi bi-exclamation-circle-fill"
                ></i>

                <?= reviewEscape(
                    $reviewError
                ) ?>

            </div>

        <?php endif; ?>


        <?php if (
            $reviewSuccess !== ''
        ): ?>

            <div class="hh-review-alert success">

                <i
                    class="bi bi-check-circle-fill"
                ></i>

                <?= reviewEscape(
                    $reviewSuccess
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             REVIEW FORM + SUMMARY
        ====================================================== -->

        <div class="hh-review-main-grid">


            <!-- =================================================
                 LEFT
            ================================================== -->

            <section class="hh-review-form-card">

                <div class="hh-review-title-row">

                    <div class="hh-review-title-icon">

                        <i class="bi bi-stars"></i>

                    </div>


                    <div>

                        <span>
                            Share Your Experience
                        </span>

                        <h2>
                            Write Your Review
                        </h2>

                    </div>

                </div>


                <?php if (
                    $alreadyReviewed &&
                    $existingReview
                ): ?>


                    <!-- =========================================
                         ALREADY REVIEWED
                    ========================================== -->

                    <div class="hh-review-state reviewed">

                        <div class="hh-review-state-icon">

                            <i
                                class="bi bi-patch-check-fill"
                            ></i>

                        </div>


                        <h3>

                            Review Published ✨

                        </h3>


                        <div class="hh-existing-stars">

                            <?php

                            $existingRating =
                                (int) (
                                    $existingReview[
                                        'rating'
                                    ]
                                    ?? 0
                                );


                            for (
                                $star = 1;
                                $star <= 5;
                                $star++
                            ) {

                                echo
                                    $star <=
                                    $existingRating
                                        ? '★'
                                        : '☆';
                            }

                            ?>

                        </div>


                        <?php if (
                            !empty(
                                $existingReview[
                                    'review_title'
                                ]
                            )
                        ): ?>

                            <h3>

                                <?= reviewEscape(
                                    $existingReview[
                                        'review_title'
                                    ]
                                ) ?>

                            </h3>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $existingReview[
                                    'review'
                                ]
                            )
                        ): ?>

                            <p>

                                <?= nl2br(
                                    reviewEscape(
                                        $existingReview[
                                            'review'
                                        ]
                                    )
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $existingReview[
                                    'image'
                                ]
                            )
                        ): ?>

                            <div
                                class="hh-review-photo"
                                style="
                                    margin:
                                        17px auto 0;
                                "
                            >

                                <img
                                    src="<?= reviewEscape(
                                        reviewImagePath(
                                            $existingReview[
                                                'image'
                                            ]
                                        )
                                    ) ?>"
                                    alt="Your review photo"
                                >

                            </div>

                        <?php endif; ?>

                    </div>


                <?php elseif (
                    $canReview
                ): ?>


                    <!-- =========================================
                         REVIEW FORM
                    ========================================== -->

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        id="reviewForm"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= reviewEscape(
                                reviewCsrfToken()
                            ) ?>"
                        >


                        <!-- STAR RATING -->

                        <div class="hh-star-section">

                            <span class="hh-star-label">

                                How was your experience?

                            </span>


                            <div
                                class="hh-star-picker"
                                id="starPicker"
                            >

                                <input
                                    type="radio"
                                    id="star5"
                                    name="rating"
                                    value="5"
                                    required
                                >

                                <label
                                    for="star5"
                                    title="Absolutely Amazing"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star4"
                                    name="rating"
                                    value="4"
                                >

                                <label
                                    for="star4"
                                    title="Love It"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star3"
                                    name="rating"
                                    value="3"
                                >

                                <label
                                    for="star3"
                                    title="Good"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star2"
                                    name="rating"
                                    value="2"
                                >

                                <label
                                    for="star2"
                                    title="Could Be Better"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star1"
                                    name="rating"
                                    value="1"
                                >

                                <label
                                    for="star1"
                                    title="Disappointed"
                                >
                                    ★
                                </label>

                            </div>


                            <div
                                class="hh-rating-reaction"
                                id="ratingReaction"
                            >

                                Select your rating

                            </div>

                        </div>


                        <!-- TITLE -->

                        <div class="hh-form-group">

                            <label>

                                <span>
                                    Review Title
                                </span>

                                <small
                                    id="titleCounter"
                                >
                                    0 / 150
                                </small>

                            </label>


                            <input
                                type="text"
                                name="review_title"
                                id="reviewTitle"
                                class="hh-review-input"
                                maxlength="150"
                                placeholder="Example: Perfect condition and worth every ringgit"
                                required
                            >

                        </div>


                        <!-- REVIEW -->

                        <div class="hh-form-group">

                            <label>

                                <span>
                                    Tell us more
                                </span>

                                <small
                                    id="reviewCounter"
                                >
                                    0 / 2000
                                </small>

                            </label>


                            <textarea
                                name="review"
                                id="reviewText"
                                class="hh-review-textarea"
                                maxlength="2000"
                                minlength="10"
                                placeholder="What did you like about the product? How was the quality, packaging and overall experience?"
                                required
                            ></textarea>

                        </div>


                        <!-- PHOTO -->

                        <div class="hh-form-group">

                            <label>

                                <span>
                                    Add a Photo
                                </span>

                                <small>
                                    Optional · Max 5MB
                                </small>

                            </label>


                            <div class="hh-photo-upload">

                                <input
                                    type="file"
                                    name="review_image"
                                    id="reviewImage"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                >


                                <div>

                                    <div
                                        class="hh-photo-upload-icon"
                                    >

                                        <i
                                            class="bi bi-camera-fill"
                                        ></i>

                                    </div>


                                    <strong>

                                        Show other shoppers
                                        the real product

                                    </strong>


                                    <span>

                                        JPG, PNG or WEBP

                                    </span>

                                </div>

                            </div>


                            <div
                                class="hh-photo-preview"
                                id="photoPreview"
                            >

                                <img
                                    src=""
                                    alt="Review preview"
                                    id="photoPreviewImage"
                                >

                            </div>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="hh-submit-review"
                            id="submitReviewButton"
                        >

                            <i
                                class="bi bi-stars"
                            ></i>

                            Publish Verified Review

                        </button>

                    </form>


                <?php else: ?>


                    <!-- =========================================
                         LOCKED
                    ========================================== -->

                    <div class="hh-review-state">

                        <div class="hh-review-state-icon">

                            <i class="bi bi-lock-fill"></i>

                        </div>


                        <h3>
                            Review Locked
                        </h3>


                        <p>

                            <?= reviewEscape(
                                $reviewLockedReason !== ''
                                    ? $reviewLockedReason
                                    : 'Only completed verified purchases can be reviewed.'
                            ) ?>

                        </p>


                        <?php if (
                            $purchase
                        ): ?>

                            <div
                                style="
                                    margin-top:17px;
                                    color:#64748b;
                                    font-size:9px;
                                "
                            >

                                Current seller status:

                                <strong>

                                    <?= reviewEscape(
                                        $purchase[
                                            'vendor_status'
                                        ]
                                        ?? 'Pending'
                                    ) ?>

                                </strong>

                            </div>

                        <?php endif; ?>

                    </div>


                <?php endif; ?>

            </section>


            <!-- =================================================
                 RATING SUMMARY
            ================================================== -->

            <aside class="hh-rating-card">

                <div class="hh-rating-score">

                    <strong>

                        <?= number_format(
                            $averageRating,
                            1
                        ) ?>

                    </strong>


                    <div class="hh-rating-score-stars">

                        <?php

                        $roundedAverage =
                            (int)
                            round(
                                $averageRating
                            );


                        for (
                            $star = 1;
                            $star <= 5;
                            $star++
                        ) {

                            echo
                                $star <=
                                $roundedAverage
                                    ? '★'
                                    : '☆';
                        }

                        ?>

                    </div>


                    <span>

                        Based on
                        <?= number_format(
                            $totalReviews
                        ) ?>
                        verified review<?= $totalReviews !== 1
                            ? 's'
                            : '' ?>

                    </span>

                </div>


                <?php

                for (
                    $ratingRow = 5;
                    $ratingRow >= 1;
                    $ratingRow--
                ):

                    $ratingCount =
                        (int) (
                            $reviewSummary[
                                'rating_' .
                                $ratingRow
                            ]
                            ?? 0
                        );


                    $ratingPercent =
                        $totalReviews > 0
                            ? (
                                $ratingCount /
                                $totalReviews
                            ) * 100
                            : 0;

                ?>

                    <div class="hh-breakdown-row">

                        <span>

                            <?= $ratingRow ?>
                            ★

                        </span>


                        <div class="hh-breakdown-bar">

                            <div
                                class="hh-breakdown-fill"
                                style="
                                    width:
                                    <?= number_format(
                                        $ratingPercent,
                                        2,
                                        '.',
                                        ''
                                    ) ?>%;
                                "
                            ></div>

                        </div>


                        <span>

                            <?= $ratingCount ?>

                        </span>

                    </div>

                <?php endfor; ?>


                <?php if (
                    $purchase
                ): ?>

                    <div
                        style="
                            margin-top:22px;
                            padding:15px;
                            color:#376083;
                            background:#f4f9ff;
                            border:1px solid #dceaff;
                            border-radius:14px;
                            font-size:9px;
                            line-height:1.7;
                        "
                    >

                        <i
                            class="bi bi-shield-check"
                            style="
                                color:#2563eb;
                            "
                        ></i>

                        Your review is connected to
                        Order #

                        <strong>

                            <?= (int)
                                $purchase[
                                    'order_id'
                                ] ?>

                        </strong>

                        and will appear as a
                        Verified Purchase review.

                    </div>

                <?php endif; ?>

            </aside>

        </div>


        <!-- =====================================================
             ALL CUSTOMER REVIEWS
        ====================================================== -->

        <section class="hh-all-reviews">

            <div class="hh-all-review-heading">

                <div>

                    <span>
                        Real Customer Experiences
                    </span>

                    <h2>
                        Customer Reviews
                    </h2>

                </div>


                <div class="hh-review-total-pill">

                    <?= number_format(
                        $totalReviews
                    ) ?>

                    review<?= $totalReviews !== 1
                        ? 's'
                        : '' ?>

                </div>

            </div>


            <?php if (
                empty(
                    $reviews
                )
            ): ?>

                <div class="hh-review-empty">

                    <div class="hh-review-empty-icon">

                        ★

                    </div>


                    <h3>

                        No reviews yet

                    </h3>


                    <p>

                        Completed buyers can be the first
                        to share their experience.

                    </p>

                </div>

            <?php else: ?>


                <?php foreach (
                    $reviews
                    as $review
                ): ?>


                    <?php

                    $customerName =
                        trim(
                            (string) (
                                $review[
                                    'customer_name'
                                ]
                                ?? 'Customer'
                            )
                        );


                    $initial =
                        function_exists(
                            'mb_substr'
                        )
                            ? strtoupper(
                                mb_substr(
                                    $customerName,
                                    0,
                                    1
                                )
                            )
                            : strtoupper(
                                substr(
                                    $customerName,
                                    0,
                                    1
                                )
                            );


                    $profileImage =
                        trim(
                            (string) (
                                $review[
                                    'profile_image'
                                ]
                                ?? ''
                            )
                        );


                    $rating =
                        max(
                            1,
                            min(
                                5,
                                (int) (
                                    $review[
                                        'rating'
                                    ]
                                    ?? 1
                                )
                            )
                        );

                    ?>


                    <article class="hh-customer-review">

                        <div class="hh-review-user">

                            <div class="hh-review-user-left">

                                <div class="hh-avatar">

                                    <?php if (
                                        $profileImage !== ''
                                    ): ?>

                                        <img
                                            src="<?= reviewEscape(
                                                strpos(
                                                    $profileImage,
                                                    'uploads/'
                                                ) === 0
                                                    ? $profileImage
                                                    : 'uploads/' .
                                                        rawurlencode(
                                                            basename(
                                                                $profileImage
                                                            )
                                                        )
                                            ) ?>"
                                            alt="<?= reviewEscape(
                                                $customerName
                                            ) ?>"
                                        >

                                    <?php else: ?>

                                        <?= reviewEscape(
                                            $initial
                                        ) ?>

                                    <?php endif; ?>

                                </div>


                                <div>

                                    <div class="hh-review-user-name">

                                        <?= reviewEscape(
                                            $customerName
                                        ) ?>

                                    </div>


                                    <?php if (
                                        !empty(
                                            $review[
                                                'order_detail_id'
                                            ]
                                        )
                                    ): ?>

                                        <div class="hh-review-verified">

                                            <i
                                                class="bi bi-patch-check-fill"
                                            ></i>

                                            Verified Purchase

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="hh-review-date">

                                <?= reviewEscape(
                                    date(
                                        'd M Y',
                                        strtotime(
                                            $review[
                                                'review_date'
                                            ]
                                        )
                                    )
                                ) ?>

                            </div>

                        </div>


                        <div class="hh-review-card-stars">

                            <?php

                            for (
                                $star = 1;
                                $star <= 5;
                                $star++
                            ) {

                                echo
                                    $star <=
                                    $rating
                                        ? '★'
                                        : '☆';
                            }

                            ?>

                        </div>


                        <?php if (
                            !empty(
                                $review[
                                    'review_title'
                                ]
                            )
                        ): ?>

                            <h3>

                                <?= reviewEscape(
                                    $review[
                                        'review_title'
                                    ]
                                ) ?>

                            </h3>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $review[
                                    'review'
                                ]
                            )
                        ): ?>

                            <p class="hh-customer-review-text">

                                <?= nl2br(
                                    reviewEscape(
                                        $review[
                                            'review'
                                        ]
                                    )
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $review[
                                    'image'
                                ]
                            )
                        ): ?>

                            <div class="hh-review-photo">

                                <img
                                    src="<?= reviewEscape(
                                        reviewImagePath(
                                            $review[
                                                'image'
                                            ]
                                        )
                                    ) ?>"
                                    alt="Customer review photo"
                                >

                            </div>

                        <?php endif; ?>


                        <div class="hh-helpful">

                            <i
                                class="bi bi-hand-thumbs-up"
                            ></i>

                            Helpful

                            <?php if (
                                (int) (
                                    $review[
                                        'helpful_count'
                                    ]
                                    ?? 0
                                ) > 0
                            ): ?>

                                ·

                                <?= (int)
                                    $review[
                                        'helpful_count'
                                    ] ?>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>


            <?php endif; ?>

        </section>

    </div>

</main>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | STAR REACTION
        |--------------------------------------------------------------------------
        */

        const ratingInputs =
            document.querySelectorAll(
                'input[name="rating"]'
            );

        const ratingReaction =
            document.getElementById(
                'ratingReaction'
            );


        const ratingMessages = {

            1:
                '😕 Disappointed',

            2:
                '🙂 Could Be Better',

            3:
                '😊 Good',

            4:
                '😍 Love It',

            5:
                '🤩 Absolutely Amazing'
        };


        ratingInputs.forEach(
            function (input) {

                input.addEventListener(
                    'change',
                    function () {

                        const value =
                            parseInt(
                                this.value,
                                10
                            );


                        if (
                            ratingReaction &&
                            ratingMessages[
                                value
                            ]
                        ) {

                            ratingReaction.textContent =
                                ratingMessages[
                                    value
                                ];
                        }
                    }
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | CHARACTER COUNTERS
        |--------------------------------------------------------------------------
        */

        const reviewTitle =
            document.getElementById(
                'reviewTitle'
            );

        const titleCounter =
            document.getElementById(
                'titleCounter'
            );


        if (
            reviewTitle &&
            titleCounter
        ) {

            const updateTitleCount =
                function () {

                    titleCounter.textContent =
                        reviewTitle.value.length +
                        ' / 150';
                };


            reviewTitle.addEventListener(
                'input',
                updateTitleCount
            );

            updateTitleCount();
        }


        const reviewText =
            document.getElementById(
                'reviewText'
            );

        const reviewCounter =
            document.getElementById(
                'reviewCounter'
            );


        if (
            reviewText &&
            reviewCounter
        ) {

            const updateReviewCount =
                function () {

                    reviewCounter.textContent =
                        reviewText.value.length +
                        ' / 2000';
                };


            reviewText.addEventListener(
                'input',
                updateReviewCount
            );

            updateReviewCount();
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        const reviewImage =
            document.getElementById(
                'reviewImage'
            );

        const photoPreview =
            document.getElementById(
                'photoPreview'
            );

        const photoPreviewImage =
            document.getElementById(
                'photoPreviewImage'
            );


        if (
            reviewImage &&
            photoPreview &&
            photoPreviewImage
        ) {

            reviewImage.addEventListener(
                'change',
                function () {

                    const file =
                        this.files &&
                        this.files[
                            0
                        ];


                    if (!file) {

                        photoPreview.style.display =
                            'none';

                        photoPreviewImage.src =
                            '';

                        return;
                    }


                    if (
                        file.size >
                        5 * 1024 * 1024
                    ) {

                        alert(
                            'Image must be 5MB or smaller.'
                        );

                        this.value = '';

                        photoPreview.style.display =
                            'none';

                        return;
                    }


                    const allowed =
                        [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ];


                    if (
                        !allowed.includes(
                            file.type
                        )
                    ) {

                        alert(
                            'Only JPG, PNG or WEBP images are allowed.'
                        );

                        this.value = '';

                        photoPreview.style.display =
                            'none';

                        return;
                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            photoPreviewImage.src =
                                event.target.result;

                            photoPreview.style.display =
                                'block';
                        };


                    reader.readAsDataURL(
                        file
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PREVENT DOUBLE SUBMIT
        |--------------------------------------------------------------------------
        */

        const reviewForm =
            document.getElementById(
                'reviewForm'
            );

        const submitReviewButton =
            document.getElementById(
                'submitReviewButton'
            );


        if (
            reviewForm &&
            submitReviewButton
        ) {

            reviewForm.addEventListener(
                'submit',
                function () {

                    submitReviewButton.disabled =
                        true;

                    submitReviewButton.innerHTML =
                        '<i class="bi bi-hourglass-split"></i> Publishing Review...';
                }
            );
        }

    }
);

</script>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>