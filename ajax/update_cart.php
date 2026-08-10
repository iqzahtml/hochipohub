<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX UPDATE CART
|--------------------------------------------------------------------------
| File:
| ajax/update_cart.php
|
| Purpose:
| Update the quantity of an item in the customer's shopping cart.
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
            'Only customers can update the shopping cart.'

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
| CART ID
|--------------------------------------------------------------------------
*/

$cartId =
    filter_input(
        INPUT_POST,
        'cart_id',
        FILTER_VALIDATE_INT
    );


/*
|--------------------------------------------------------------------------
| QUANTITY
|--------------------------------------------------------------------------
*/

$quantity =
    filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );


/*
|--------------------------------------------------------------------------
| VALIDATE CART ID
|--------------------------------------------------------------------------
*/

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
| VALIDATE QUANTITY
|--------------------------------------------------------------------------
*/

if (
    $quantity === false
    ||
    $quantity === null
    ||
    $quantity < 1
) {

    http_response_code(400);

    echo json_encode([

        'success' => false,

        'message' =>
            'Quantity must be at least 1.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE CART
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | GET CART ITEM + PRODUCT STOCK
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                c.cart_id,
                c.customer_id,
                c.product_id,
                c.quantity AS current_quantity,

                p.product_name,
                p.price,
                p.stock_quantity,
                p.status

            FROM cart c

            INNER JOIN products p
                ON c.product_id = p.product_id

            WHERE c.cart_id = ?

            AND c.customer_id = ?

            LIMIT 1
        ");


    $stmt->execute([

        $cartId,

        $customerId

    ]);


    $cartItem =
        $stmt->fetch();


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
    | CHECK PRODUCT STATUS
    |--------------------------------------------------------------------------
    */

    if (
        $cartItem['status']
        !== 'Available'
    ) {

        http_response_code(400);

        echo json_encode([

            'success' => false,

            'message' =>
                'This product is no longer available.'

        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK STOCK
    |--------------------------------------------------------------------------
    */

    $stockQuantity =
        (int)
        $cartItem['stock_quantity'];


    if (
        $stockQuantity <= 0
    ) {

        http_response_code(400);

        echo json_encode([

            'success' => false,

            'message' =>
                'This product is out of stock.'

        ]);

        exit;
    }


    if (
        $quantity > $stockQuantity
    ) {

        http_response_code(400);

        echo json_encode([

            'success' => false,

            'message' =>
                'Only '
                . $stockQuantity
                . ' item(s) available in stock.',

            'available_stock' =>
                $stockQuantity

        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE QUANTITY
    |--------------------------------------------------------------------------
    */

    $updateStmt =
        $db->prepare("
            UPDATE cart

            SET quantity = ?

            WHERE cart_id = ?

            AND customer_id = ?
        ");


    $updateStmt->execute([

        $quantity,

        $cartId,

        $customerId

    ]);


    /*
    |--------------------------------------------------------------------------
    | UPDATED ITEM TOTAL
    |--------------------------------------------------------------------------
    */

    $price =
        (float)
        $cartItem['price'];


    $itemTotal =
        $price * $quantity;


    /*
    |--------------------------------------------------------------------------
    | UPDATED CART COUNT
    |--------------------------------------------------------------------------
    */

    $cartCount =
        getCartCount(
            $db,
            $customerId
        );


    /*
    |--------------------------------------------------------------------------
    | UPDATED CART TOTAL
    |--------------------------------------------------------------------------
    */

    $cartTotal =
        getCartTotal(
            $db,
            $customerId
        );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'message' =>
            'Cart updated successfully.',

        'cart_id' =>
            $cartId,

        'product_id' =>
            (int)
            $cartItem['product_id'],

        'quantity' =>
            $quantity,

        'price' =>
            $price,

        'item_total' =>
            $itemTotal,

        'formatted_item_total' =>
            formatMoney(
                $itemTotal
            ),

        'cart_count' =>
            $cartCount,

        'cart_total' =>
            $cartTotal,

        'formatted_cart_total' =>
            formatMoney(
                $cartTotal
            ),

        'available_stock' =>
            $stockQuantity

    ]);

    exit;


} catch (
    PDOException $e
) {

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
                'Unable to update cart.'

        ]);
    }

    exit;
}