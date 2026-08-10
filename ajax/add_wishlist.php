<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX ADD WISHLIST
|--------------------------------------------------------------------------
| File:
| ajax/add_wishlist.php
|
| Purpose:
| Add a product to customer's wishlist.
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
| ONLY POST REQUEST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Please login to add products to your wishlist.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER
|--------------------------------------------------------------------------
*/

if (!isCustomer()) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Only customers can use the wishlist.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

if (!isAccountActive()) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Your account is not active.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF VALIDATION
|--------------------------------------------------------------------------
*/

$csrfToken =
    $_POST['csrf_token']
    ?? '';


if (
    !verifyCsrfToken(
        $csrfToken
    )
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid security token. Please refresh the page and try again.'
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
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );


if (
    !$productId
    ||
    $productId <= 0
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid product.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CURRENT CUSTOMER
|--------------------------------------------------------------------------
*/

$customerId =
    currentUserId();


if (
    !$customerId
) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Customer session not found.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK PRODUCT
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
                v.approval_status

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            WHERE p.product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $productId
    ]);


    $product =
        $stmt->fetch();


} catch (
    PDOException $e
) {

    http_response_code(500);

    if (
        APP_DEBUG
    ) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Unable to load product.'
        ]);
    }

    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUCT NOT FOUND
|--------------------------------------------------------------------------
*/

if (
    !$product
) {

    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Product not found.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUCT AVAILABILITY
|--------------------------------------------------------------------------
*/

if (
    $product['status']
    !== 'Available'
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'This product is currently unavailable.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| VENDOR APPROVAL
|--------------------------------------------------------------------------
*/

if (
    $product['approval_status']
    !== 'Approved'
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'This product is not available for purchase.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK EXISTING WISHLIST
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
        $stmt->fetch();


} catch (
    PDOException $e
) {

    http_response_code(500);

    if (
        APP_DEBUG
    ) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Unable to check your wishlist.'
        ]);
    }

    exit;
}


/*
|--------------------------------------------------------------------------
| ALREADY IN WISHLIST
|--------------------------------------------------------------------------
*/

if (
    $existingWishlist
) {

    $wishlistCount =
        getWishlistCount(
            $db,
            $customerId
        );


    echo json_encode([
        'success' => true,
        'already_exists' => true,
        'message' =>
            'This product is already in your wishlist.',
        'wishlist_count' => $wishlistCount,
        'product_id' => $productId
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ADD TO WISHLIST
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    $stmt =
        $db->prepare("
            INSERT INTO wishlist (
                user_id,
                product_id
            )

            VALUES (
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
    | GET UPDATED COUNT
    |--------------------------------------------------------------------------
    */

    $wishlistCount =
        getWishlistCount(
            $db,
            $customerId
        );


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

    echo json_encode([
        'success' => true,
        'already_exists' => false,
        'message' =>
            $product['product_name']
            . ' has been added to your wishlist.',
        'wishlist_count' => $wishlistCount,
        'product_id' => $productId
    ]);

    exit;


} catch (
    PDOException $e
) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE ENTRY
    |--------------------------------------------------------------------------
    */

    if (
        $e->getCode() === '23000'
    ) {

        $wishlistCount =
            getWishlistCount(
                $db,
                $customerId
            );


        echo json_encode([
            'success' => true,
            'already_exists' => true,
            'message' =>
                'This product is already in your wishlist.',
            'wishlist_count' => $wishlistCount,
            'product_id' => $productId
        ]);

        exit;
    }


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
            'success' => false,
            'message' => $e->getMessage()
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' =>
                'Unable to add product to wishlist.'
        ]);
    }

    exit;
}