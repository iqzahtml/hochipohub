<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX REMOVE CART
|--------------------------------------------------------------------------
| File:
| ajax/remove_cart.php
|
| Purpose:
| Remove an item from the customer's shopping cart.
|
| Required:
| - cart_id
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

        'message' =>
            'Invalid request method.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isLoggedIn()
) {

    http_response_code(401);

    echo json_encode([

        'success' => false,

        'message' =>
            'Please login to continue.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CUSTOMER CHECK
|--------------------------------------------------------------------------
*/

if (
    !isCustomer()
) {

    http_response_code(403);

    echo json_encode([

        'success' => false,

        'message' =>
            'Only customers can manage the shopping cart.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CART ID
|--------------------------------------------------------------------------
*/

$cartId =
    filter_input(
        INPUT_POST,
        'cart_id',
        FILTER_VALIDATE_INT
    );


if (
    $cartId === false
    ||
    $cartId === null
    ||
    $cartId <= 0
) {

    http_response_code(400);

    echo json_encode([

        'success' => false,

        'message' =>
            'Invalid cart item.'

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
    $customerId === null
) {

    http_response_code(401);

    echo json_encode([

        'success' => false,

        'message' =>
            'Unable to identify the current customer.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| REMOVE CART ITEM
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | CHECK CART ITEM OWNERSHIP
    |--------------------------------------------------------------------------
    */

    $checkStmt =
        $db->prepare("
            SELECT
                cart_id,
                product_id,
                quantity

            FROM cart

            WHERE cart_id = ?

            AND customer_id = ?

            LIMIT 1
        ");


    $checkStmt->execute([

        $cartId,

        $customerId

    ]);


    $cartItem =
        $checkStmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | CART ITEM NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (
        !$cartItem
    ) {

        http_response_code(404);

        echo json_encode([

            'success' => false,

            'message' =>
                'Cart item not found.'

        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CART ITEM
    |--------------------------------------------------------------------------
    */

    $deleteStmt =
        $db->prepare("
            DELETE FROM cart

            WHERE cart_id = ?

            AND customer_id = ?
        ");


    $deleteStmt->execute([

        $cartId,

        $customerId

    ]);


    /*
    |--------------------------------------------------------------------------
    | VERIFY DELETE
    |--------------------------------------------------------------------------
    */

    if (
        $deleteStmt->rowCount() === 0
    ) {

        http_response_code(400);

        echo json_encode([

            'success' => false,

            'message' =>
                'Unable to remove cart item.'

        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATED CART SUMMARY
    |--------------------------------------------------------------------------
    */

    $cartCount =
        getCartCount(
            $db,
            $customerId
        );


    $cartTotal =
        getCartTotal(
            $db,
            $customerId
        );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'message' =>
            'Item removed from cart.',

        'cart_id' =>
            $cartId,

        'cart_count' =>
            $cartCount,

        'cart_total' =>
            $cartTotal,

        'formatted_total' =>
            formatMoney(
                $cartTotal
            )

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

            'success' => false,

            'message' =>
                $e->getMessage()

        ]);

    } else {

        echo json_encode([

            'success' => false,

            'message' =>
                'Unable to remove cart item.'

        ]);
    }

    exit;
}