<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX REMOVE WISHLIST
|--------------------------------------------------------------------------
| File:
| ajax/remove_wishlist.php
|
| Purpose:
| Remove a product from the customer's wishlist.
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

header('Content-Type: application/json; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| DEFAULT RESPONSE
|--------------------------------------------------------------------------
*/

$response = [

    'success' => false,

    'message' => 'Unable to remove item from wishlist.'

];


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {

    http_response_code(401);

    $response['message'] =
        'Please login to manage your wishlist.';

    echo json_encode($response);

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER
|--------------------------------------------------------------------------
*/

if (!isCustomer()) {

    http_response_code(403);

    $response['message'] =
        'Only customers can manage wishlist.';

    echo json_encode($response);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$userId = currentUserId();


if (!$userId) {

    http_response_code(401);

    $response['message'] =
        'User session is invalid.';

    echo json_encode($response);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| VALIDATE PRODUCT ID
|--------------------------------------------------------------------------
*/

if (
    !$productId
    ||
    $productId <= 0
) {

    http_response_code(400);

    $response['message'] =
        'Invalid product selected.';

    echo json_encode($response);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| CHECK WISHLIST ITEM
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            wishlist_id

        FROM wishlist

        WHERE user_id = ?

        AND product_id = ?

        LIMIT 1
    ");

    $stmt->execute([

        $userId,

        $productId

    ]);

    $wishlistItem =
        $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | ITEM NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$wishlistItem) {

        http_response_code(404);

        $response['message'] =
            'Product is not in your wishlist.';

        echo json_encode($response);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE FROM WISHLIST
    |--------------------------------------------------------------------------
    */

    $deleteStmt = $pdo->prepare("
        DELETE FROM wishlist

        WHERE wishlist_id = ?

        AND user_id = ?
    ");

    $deleteStmt->execute([

        $wishlistItem['wishlist_id'],

        $userId

    ]);


    /*
    |--------------------------------------------------------------------------
    | CHECK DELETE RESULT
    |--------------------------------------------------------------------------
    */

    if (
        $deleteStmt->rowCount() > 0
    ) {

        /*
        |--------------------------------------------------------------------------
        | GET UPDATED WISHLIST COUNT
        |--------------------------------------------------------------------------
        */

        $countStmt = $pdo->prepare("
            SELECT COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");

        $countStmt->execute([

            $userId

        ]);

        $wishlistCount =
            (int) $countStmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | SUCCESS RESPONSE
        |--------------------------------------------------------------------------
        */

        $response = [

            'success' => true,

            'message' =>
                'Product removed from wishlist.',

            'product_id' =>
                $productId,

            'wishlist_count' =>
                $wishlistCount

        ];

        echo json_encode($response);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE FAILED
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    $response['message'] =
        'Failed to remove product from wishlist.';

    echo json_encode($response);

    exit;


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    if (APP_DEBUG) {

        $response['message'] =
            'Database error: '
            . $e->getMessage();

    } else {

        $response['message'] =
            'A database error occurred. Please try again.';
    }

    echo json_encode($response);

    exit;
}