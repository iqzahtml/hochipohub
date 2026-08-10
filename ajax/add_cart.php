<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX ADD TO CART
|--------------------------------------------------------------------------
| File:
| ajax/add_cart.php
|
| Purpose:
| Add a product to customer's shopping cart.
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
| AJAX RESPONSE
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
        'message' => 'Please login to add products to your cart.'
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
        'message' => 'Only customers can add products to the cart.'
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
        'message' => 'Invalid security token. Please refresh the page and try again.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
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
| GET QUANTITY
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
| DEFAULT QUANTITY
|--------------------------------------------------------------------------
*/

if (
    $quantity === false
    ||
    $quantity === null
) {

    $quantity = 1;
}


/*
|--------------------------------------------------------------------------
| VALIDATE QUANTITY
|--------------------------------------------------------------------------
*/

if (
    $quantity <= 0
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Quantity must be at least 1.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| LIMIT REQUESTED QUANTITY
|--------------------------------------------------------------------------
|
| Prevent unreasonable quantities from
| being submitted through the browser.
|
*/

if (
    $quantity > 999
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Quantity is too large.'
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
| GET PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT
                product_id,
                product_name,
                price,
                stock_quantity,
                status,
                vendor_id

            FROM products

            WHERE product_id = ?

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

    if (APP_DEBUG) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

    } else {

        http_response_code(500);

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
| PRODUCT STATUS
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
| STOCK VALIDATION
|--------------------------------------------------------------------------
*/

$stockQuantity =
    (int) $product['stock_quantity'];


if (
    $stockQuantity <= 0
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'This product is out of stock.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK EXISTING CART ITEM
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT
                cart_id,
                quantity

            FROM cart

            WHERE customer_id = ?

            AND product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $customerId,
        $productId
    ]);


    $existingCart =
        $stmt->fetch();


} catch (
    PDOException $e
) {

    if (APP_DEBUG) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Unable to check your cart.'
        ]);
    }

    exit;
}


/*
|--------------------------------------------------------------------------
| CALCULATE NEW QUANTITY
|--------------------------------------------------------------------------
*/

if (
    $existingCart
) {

    $newQuantity =
        (int) $existingCart['quantity']
        +
        $quantity;

} else {

    $newQuantity =
        $quantity;
}


/*
|--------------------------------------------------------------------------
| STOCK LIMIT
|--------------------------------------------------------------------------
*/

if (
    $newQuantity > $stockQuantity
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' =>
            'Only '
            . $stockQuantity
            . ' item(s) available in stock.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE TRANSACTION
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | UPDATE EXISTING ITEM
    |--------------------------------------------------------------------------
    */

    if (
        $existingCart
    ) {

        $stmt =
            $db->prepare("
                UPDATE cart

                SET quantity = ?

                WHERE cart_id = ?

                AND customer_id = ?
            ");


        $stmt->execute([
            $newQuantity,
            $existingCart['cart_id'],
            $customerId
        ]);


    /*
    |--------------------------------------------------------------------------
    | INSERT NEW ITEM
    |--------------------------------------------------------------------------
    */

    } else {

        $stmt =
            $db->prepare("
                INSERT INTO cart (
                    customer_id,
                    product_id,
                    quantity
                )

                VALUES (
                    ?,
                    ?,
                    ?
                )
            ");


        $stmt->execute([
            $customerId,
            $productId,
            $quantity
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GET UPDATED CART COUNT
    |--------------------------------------------------------------------------
    */

    $cartCount =
        getCartCount(
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
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'message' =>
            $product['product_name']
            . ' has been added to your cart.',
        'cart_count' => $cartCount,
        'product_id' => $productId,
        'quantity' => $newQuantity
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
    | ERROR RESPONSE
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
            'message' => 'Unable to add product to cart.'
        ]);
    }

    exit;
}