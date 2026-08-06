<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX UPDATE CART
|--------------------------------------------------------------------------
| File:
| ajax/update_cart.php
|
| Handles:
| - Customer authentication
| - Cart item validation
| - Quantity validation
| - Stock validation
| - Updating cart quantity
| - Recalculating cart subtotal
| - Recalculating cart total
| - Returning JSON response
|--------------------------------------------------------------------------
*/


/* ==============================================================
   SESSION
============================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ==============================================================
   JSON RESPONSE HEADER
============================================================== */

header('Content-Type: application/json; charset=UTF-8');


/* ==============================================================
   JSON RESPONSE FUNCTION
============================================================== */

function jsonResponse(
    bool $success,
    string $message,
    array $extra = []
): void {

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $extra
        )
    );

    exit;
}


/* ==============================================================
   ONLY POST REQUEST
============================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    jsonResponse(
        false,
        'Invalid request method.'
    );
}


/* ==============================================================
   CHECK CUSTOMER LOGIN
============================================================== */

if (!isset($_SESSION['customer_id'])) {

    http_response_code(401);

    jsonResponse(
        false,
        'Please login before updating your cart.',
        [
            'login_required' => true
        ]
    );
}


/* ==============================================================
   USER ID
============================================================== */

$userId = (int) $_SESSION['customer_id'];


/* ==============================================================
   CHECK ROLE
============================================================== */

$userRole = $_SESSION['role'] ?? 'customer';


if ($userRole !== 'customer') {

    jsonResponse(
        false,
        'Only customers can update their cart.'
    );
}


/* ==============================================================
   GET CART ID
============================================================== */

$cartId = filter_input(
    INPUT_POST,
    'cart_id',
    FILTER_VALIDATE_INT
);


if (!$cartId || $cartId <= 0) {

    jsonResponse(
        false,
        'Invalid cart item.'
    );
}


/* ==============================================================
   GET QUANTITY
============================================================== */

$quantity = filter_input(
    INPUT_POST,
    'quantity',
    FILTER_VALIDATE_INT
);


/* ==============================================================
   VALIDATE QUANTITY
============================================================== */

if ($quantity === false || $quantity === null) {

    jsonResponse(
        false,
        'Invalid quantity.'
    );
}


$quantity = (int) $quantity;


if ($quantity < 1) {

    jsonResponse(
        false,
        'Quantity must be at least 1.'
    );
}


/* ==============================================================
   MAXIMUM QUANTITY SAFETY
============================================================== */

if ($quantity > 9999) {

    jsonResponse(
        false,
        'Quantity is too high.'
    );
}


/* ==============================================================
   DATABASE
============================================================== */

require_once __DIR__ . '/../database/db.php';


/* ==============================================================
   CHECK DATABASE CONNECTION
============================================================== */

if (!isset($conn) || !($conn instanceof mysqli)) {

    http_response_code(500);

    jsonResponse(
        false,
        'Database connection is unavailable.'
    );
}


/* ==============================================================
   MYSQLI ERROR MODE
============================================================== */

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


/* ==============================================================
   FIND CART ITEM
============================================================== */

try {

    $cartQuery = "
        SELECT

            c.cart_id,

            c.customer_id,

            c.product_id,

            c.quantity AS current_quantity,

            p.product_name,

            p.price,

            p.stock_quantity,

            p.status,

            v.approval_status

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        WHERE c.cart_id = ?

        AND c.customer_id = ?

        LIMIT 1
    ";


    $cartStmt =
        $conn->prepare(
            $cartQuery
        );


    $cartStmt->bind_param(
        'ii',
        $cartId,
        $userId
    );


    $cartStmt->execute();


    $cartResult =
        $cartStmt->get_result();


    if ($cartResult->num_rows === 0) {

        $cartStmt->close();

        jsonResponse(
            false,
            'Cart item not found.'
        );
    }


    $cartItem =
        $cartResult->fetch_assoc();


    $cartStmt->close();


} catch (Throwable $e) {

    error_log(
        'HOCHIPOHUB update_cart lookup error: ' .
        $e->getMessage()
    );


    http_response_code(500);

    jsonResponse(
        false,
        'Unable to find cart item.'
    );
}


/* ==============================================================
   CHECK PRODUCT STATUS
============================================================== */

if ($cartItem['status'] !== 'Available') {

    jsonResponse(
        false,
        'This product is no longer available.'
    );
}


/* ==============================================================
   CHECK VENDOR STATUS
============================================================== */

if ($cartItem['approval_status'] !== 'Approved') {

    jsonResponse(
        false,
        'This vendor is currently unavailable.'
    );
}


/* ==============================================================
   CHECK STOCK
============================================================== */

$stockQuantity =
    (int) $cartItem['stock_quantity'];


if ($stockQuantity <= 0) {

    jsonResponse(
        false,
        'This product is currently out of stock.'
    );
}


/* ==============================================================
   QUANTITY CANNOT EXCEED STOCK
============================================================== */

if ($quantity > $stockQuantity) {

    jsonResponse(
        false,
        'Only ' .
        $stockQuantity .
        ' item(s) are available.',

        [
            'stock' =>
                $stockQuantity,

            'requested_quantity' =>
                $quantity,

            'available_quantity' =>
                $stockQuantity
        ]
    );
}


/* ==============================================================
   CALCULATE ITEM TOTAL
============================================================== */

$unitPrice =
    (float) $cartItem['price'];


$itemTotal =
    $unitPrice * $quantity;


/* ==============================================================
   UPDATE DATABASE
============================================================== */

try {

    $conn->begin_transaction();


    $updateQuery = "
        UPDATE cart

        SET quantity = ?

        WHERE cart_id = ?

        AND customer_id = ?
    ";


    $updateStmt =
        $conn->prepare(
            $updateQuery
        );


    $updateStmt->bind_param(
        'iii',
        $quantity,
        $cartId,
        $userId
    );


    $updateStmt->execute();


    if ($updateStmt->affected_rows < 0) {

        $updateStmt->close();

        throw new Exception(
            'Unable to update cart item.'
        );
    }


    $updateStmt->close();


    /* ==========================================================
       GET CART SUBTOTAL
    ========================================================== */

    $subtotalQuery = "
        SELECT

            COALESCE(
                SUM(
                    c.quantity * p.price
                ),
                0
            ) AS subtotal,

            COALESCE(
                SUM(c.quantity),
                0
            ) AS cart_count,

            COUNT(c.cart_id) AS item_count

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        WHERE c.customer_id = ?
    ";


    $subtotalStmt =
        $conn->prepare(
            $subtotalQuery
        );


    $subtotalStmt->bind_param(
        'i',
        $userId
    );


    $subtotalStmt->execute();


    $subtotalResult =
        $subtotalStmt->get_result();


    $summary =
        $subtotalResult->fetch_assoc();


    $subtotalStmt->close();


    /* ==========================================================
       VALUES
    ========================================================== */

    $subtotal =
        (float) (
            $summary['subtotal'] ?? 0
        );


    $cartCount =
        (int) (
            $summary['cart_count'] ?? 0
        );


    $itemCount =
        (int) (
            $summary['item_count'] ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | HOCHIPOHUB DELIVERY
    |--------------------------------------------------------------------------
    | Current cart page uses subtotal first.
    | Final delivery charge can later be calculated
    | separately during checkout.
    */

    $deliveryFee = 0.00;


    $total =
        $subtotal + $deliveryFee;


    /* ==========================================================
       COMMIT
    ========================================================== */

    $conn->commit();


} catch (Throwable $e) {

    try {

        $conn->rollback();

    } catch (Throwable $rollbackError) {

        error_log(
            'HOCHIPOHUB rollback error: ' .
            $rollbackError->getMessage()
        );
    }


    error_log(
        'HOCHIPOHUB update_cart error: ' .
        $e->getMessage()
    );


    http_response_code(500);

    jsonResponse(
        false,
        'Unable to update your cart.'
    );
}


/* ==============================================================
   RESPONSE
============================================================== */

jsonResponse(
    true,

    'Cart updated successfully.',

    [

        'cart_id' =>
            $cartId,

        'product_id' =>
            (int) $cartItem['product_id'],

        'product_name' =>
            $cartItem['product_name'],

        'quantity' =>
            $quantity,

        'previous_quantity' =>
            (int) $cartItem['current_quantity'],

        'stock' =>
            $stockQuantity,

        'unit_price' =>
            $unitPrice,

        'item_total' =>
            round(
                $itemTotal,
                2
            ),

        'subtotal' =>
            round(
                $subtotal,
                2
            ),

        'delivery_fee' =>
            round(
                $deliveryFee,
                2
            ),

        'total' =>
            round(
                $total,
                2
            ),

        'cart_count' =>
            $cartCount,

        'item_count' =>
            $itemCount

    ]
);