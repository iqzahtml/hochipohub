<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX ADD TO WISHLIST
|--------------------------------------------------------------------------
| File:
| ajax/add_wishlist.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| JSON
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=UTF-8'
);


/*
|--------------------------------------------------------------------------
| KEEP RESPONSE VALID JSON
|--------------------------------------------------------------------------
*/

ini_set(
    'display_errors',
    '0'
);


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

if (!function_exists('wishlistJsonResponse')) {

    function wishlistJsonResponse(
        bool $success,
        string $message,
        array $extra = [],
        int $statusCode = 200
    ): void {

        http_response_code(
            $statusCode
        );


        echo json_encode(
            array_merge(
                [
                    'success' => $success,
                    'message' => $message
                ],
                $extra
            ),
            JSON_UNESCAPED_UNICODE
        );


        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF COMPATIBILITY
|--------------------------------------------------------------------------
*/

if (!function_exists('wishlistValidateCsrf')) {

    function wishlistValidateCsrf(
        string $token
    ): bool {

        if ($token === '') {
            return false;
        }


        if (
            function_exists(
                'validateCsrfToken'
            )
        ) {

            return (bool)
                validateCsrfToken(
                    $token
                );
        }


        if (
            function_exists(
                'verifyCsrfToken'
            )
        ) {

            return (bool)
                verifyCsrfToken(
                    $token
                );
        }


        return false;
    }
}


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

if (!function_exists('wishlistCurrentUserId')) {

    function wishlistCurrentUserId(): int
    {
        if (
            function_exists(
                'currentUserId'
            )
        ) {

            return (int)
                currentUserId();
        }


        if (
            function_exists(
                'getUserId'
            )
        ) {

            return (int)
                getUserId();
        }


        return (int) (
            $_SESSION['user_id']
            ?? 0
        );
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!function_exists('wishlistLoggedIn')) {

    function wishlistLoggedIn(): bool
    {
        if (
            function_exists(
                'isLoggedIn'
            )
        ) {

            return (bool)
                isLoggedIn();
        }


        return !empty(
            $_SESSION['user_id']
        );
    }
}


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

if (!function_exists('wishlistIsCustomer')) {

    function wishlistIsCustomer(): bool
    {
        if (
            function_exists(
                'isCustomer'
            )
        ) {

            return (bool)
                isCustomer();
        }


        $role =
            strtolower(
                trim(
                    (string) (
                        $_SESSION['role']
                        ?? $_SESSION['user_role']
                        ?? ''
                    )
                )
            );


        return $role === 'customer';
    }
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    if (
        !isset($db) ||
        !($db instanceof PDO)
    ) {

        $db =
            getDB();
    }


    if (!($db instanceof PDO)) {

        wishlistJsonResponse(
            false,
            'Database connection is not available.',
            [],
            500
        );
    }


} catch (Throwable $e) {

    wishlistJsonResponse(
        false,
        'Unable to connect to database.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    wishlistJsonResponse(
        false,
        'Invalid request method.',
        [],
        405
    );
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!wishlistLoggedIn()) {

    wishlistJsonResponse(
        false,
        'Please login to add products to your wishlist.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER ONLY
|--------------------------------------------------------------------------
*/

if (!wishlistIsCustomer()) {

    wishlistJsonResponse(
        false,
        'Only customer accounts can use the wishlist.',
        [],
        403
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$csrfToken =
    trim(
        (string) (
            $_POST['csrf_token']
            ?? ''
        )
    );


if (
    !wishlistValidateCsrf(
        $csrfToken
    )
) {

    wishlistJsonResponse(
        false,
        'Invalid security token. Please refresh the page and try again.',
        [],
        403
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId =
    filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );


if (
    !$productId ||
    $productId <= 0
) {

    wishlistJsonResponse(
        false,
        'Invalid product.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER ID
|--------------------------------------------------------------------------
*/

$customerId =
    wishlistCurrentUserId();


if ($customerId <= 0) {

    wishlistJsonResponse(
        false,
        'Customer session not found.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                p.product_id,
                p.product_name,
                p.status,
                p.stock_quantity,

                v.vendor_id,
                v.approval_status,

                u.status AS vendor_user_status

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN users u
                ON v.user_id = u.user_id

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


} catch (Throwable $e) {

    wishlistJsonResponse(
        false,
        'Unable to load product.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$product) {

    wishlistJsonResponse(
        false,
        'Product not found.',
        [],
        404
    );
}


/*
|--------------------------------------------------------------------------
| NORMALIZE
|--------------------------------------------------------------------------
*/

$productStatus =
    strtolower(
        trim(
            (string)
            $product['status']
        )
    );


$vendorApproval =
    strtolower(
        trim(
            (string)
            $product['approval_status']
        )
    );


$vendorUserStatus =
    strtolower(
        trim(
            (string)
            $product['vendor_user_status']
        )
    );


/*
|--------------------------------------------------------------------------
| PRODUCT AVAILABLE
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $productStatus,
        [
            'available',
            'active'
        ],
        true
    )
) {

    wishlistJsonResponse(
        false,
        'This product is currently unavailable.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| STOCK
|--------------------------------------------------------------------------
*/

if (
    (int)
    $product['stock_quantity']
    <= 0
) {

    wishlistJsonResponse(
        false,
        'This product is currently out of stock.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR APPROVED
|--------------------------------------------------------------------------
*/

if (
    $vendorApproval !==
    'approved'
) {

    wishlistJsonResponse(
        false,
        'This seller is not currently approved.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR ACTIVE
|--------------------------------------------------------------------------
*/

if (
    $vendorUserStatus !==
    'active'
) {

    wishlistJsonResponse(
        false,
        'This seller is currently unavailable.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| EXISTING WISHLIST
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT
                wishlist_id

            FROM wishlist

            WHERE user_id = ?

            AND product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $customerId,
        $productId
    ]);


    $existingWishlist =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    wishlistJsonResponse(
        false,
        'Unable to check your wishlist.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| ALREADY EXISTS
|--------------------------------------------------------------------------
*/

if ($existingWishlist) {


    try {

        $stmt =
            $db->prepare("
                SELECT COUNT(*)

                FROM wishlist

                WHERE user_id = ?
            ");


        $stmt->execute([
            $customerId
        ]);


        $wishlistCount =
            (int)
            $stmt->fetchColumn();


    } catch (Throwable $e) {

        $wishlistCount = 0;
    }


    wishlistJsonResponse(
        true,
        'This product is already in your wishlist.',
        [
            'already_exists' =>
                true,

            'wishlist_count' =>
                $wishlistCount,

            'product_id' =>
                $productId
        ]
    );
}


/*
|--------------------------------------------------------------------------
| INSERT WISHLIST
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    $stmt =
        $db->prepare("
            INSERT INTO wishlist
            (
                user_id,
                product_id
            )

            VALUES
            (
                ?,
                ?
            )
        ");


    $stmt->execute([
        $customerId,
        $productId
    ]);


    /*
    |--------------------------------------------------------------------------
    | COUNT
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");


    $stmt->execute([
        $customerId
    ]);


    $wishlistCount =
        (int)
        $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    wishlistJsonResponse(
        true,
        $product['product_name'] .
        ' has been added to your wishlist.',
        [
            'already_exists' =>
                false,

            'wishlist_count' =>
                $wishlistCount,

            'product_id' =>
                $productId
        ]
    );


} catch (Throwable $e) {


    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE KEY
    |--------------------------------------------------------------------------
    */

    if (
        $e instanceof PDOException &&
        (string)
        $e->getCode() === '23000'
    ) {


        try {

            $stmt =
                $db->prepare("
                    SELECT COUNT(*)

                    FROM wishlist

                    WHERE user_id = ?
                ");


            $stmt->execute([
                $customerId
            ]);


            $wishlistCount =
                (int)
                $stmt->fetchColumn();


        } catch (Throwable $countError) {

            $wishlistCount = 0;
        }


        wishlistJsonResponse(
            true,
            'This product is already in your wishlist.',
            [
                'already_exists' =>
                    true,

                'wishlist_count' =>
                    $wishlistCount,

                'product_id' =>
                    $productId
            ]
        );
    }


    wishlistJsonResponse(
        false,
        'Unable to add product to wishlist.',
        [],
        500
    );
}