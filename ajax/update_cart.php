<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX UPDATE CART
|--------------------------------------------------------------------------
| File:
| ajax/update_cart.php
|
| Functions:
| - Verify customer login
| - Validate cart item
| - Validate quantity
| - Check current product stock
| - Update cart quantity
| - Recalculate subtotal
| - Recalculate total
| - Return JSON response
|--------------------------------------------------------------------------
*/


/* ==============================================================
   SESSION
============================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ==============================================================
   JSON HEADER
============================================================== */

header('Content-Type: application/json; charset=UTF-8');


/* ==============================================================
   JSON RESPONSE HELPER
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
        ),
        JSON_UNESCAPED_UNICODE
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
   CHECK LOGIN
============================================================== */

if (!isset($_SESSION['user_id'])) {

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

$userId = (int) $_SESSION['user_id'];


/* ==============================================================
   CHECK USER ROLE
============================================================== */

$userRole = $_SESSION['role'] ?? 'customer';

if ($userRole !== 'customer') {

    jsonResponse(
        false,
        'Only customers can update the cart.'
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


if ($quantity === false || $quantity === null) {

    jsonResponse(
        false,
        'Invalid quantity.'
    );
}


/* ==============================================================
   QUANTITY VALIDATION
============================================================== */

if ($quantity < 1) {

    jsonResponse(
        false,
        'Quantity must be at least 1.'
    );
}


if ($quantity > 9999) {

    jsonResponse(
        false,
        'Quantity is too large.'
    );
}


/* ==============================================================
   DATABASE CONNECTION
============================================================== */

require_once __DIR__ . '/../database/db.php';


/* ==============================================================
   CHECK MYSQL CONNECTION
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
   GET CART ITEM + PRODUCT
============================================================== */

try {

    $query = "
        SELECT
            c.cart_id,
            c.customer_id,
            c.product_id,
            c.quantity AS current_quantity,

            p.product_name,
            p.price,
            p.stock_quantity,
            p.status,

            v.vendor_id,
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


    $stmt = $conn->prepare($query);


    $stmt->bind_param(
        'ii',
        $cartId,
        $userId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    if ($result->num_rows === 0) {

        $stmt->close();

        jsonResponse(
            false,
            'Cart item not found.'
        );
    }


    $cartItem =
        $result->fetch_assoc();


    $stmt->close();


} catch (Throwable $e) {

    error_log(
        'HOCHIPOHUB update_cart lookup error: ' .
        $e->getMessage()
    );


    http_response_code(500);

    jsonResponse(
        false,
        'Unable to retrieve cart item.'
    );
}


/* ==============================================================
   CHECK PRODUCT STATUS
============================================================== */

if ($cartItem['status'] !== 'Available') {

    jsonResponse(
        false,
        'This product is currently unavailable.'
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
   GET CURRENT STOCK
============================================================== */

$stockQuantity =
    (int) $cartItem['stock_quantity'];


if ($stockQuantity <= 0) {

    jsonResponse(
        false,
        'This product is currently out of stock.',
        [
            'stock' => 0
        ]
    );
}


/* ==============================================================
   CHECK REQUESTED QUANTITY AGAINST STOCK
============================================================== */

if ($quantity > $stockQuantity) {

    jsonResponse(
        false,
        'Only ' .
        $stockQuantity .
        ' item(s) are available.',
        [
            'stock' => $stockQuantity,

            'requested_quantity' =>
                $quantity
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
   UPDATE CART
============================================================== */

try {

    $conn->begin_transaction();


    $updateQuery = "
        UPDATE cart

        SET quantity = ?

        WHERE cart_id = ?
        AND customer_id = ?
        LIMIT 1
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


    $updateStmt->close();


    /* ==========================================================
       GET UPDATED CART ITEMS
    ========================================================== */

    $cartQuery = "
        SELECT
            c.cart_id,
            c.product_id,
            c.quantity,
            p.price

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        WHERE c.customer_id = ?
    ";


    $cartStmt =
        $conn->prepare(
            $cartQuery
        );


    $cartStmt->bind_param(
        'i',
        $userId
    );


    $cartStmt->execute();


    $cartResult =
        $cartStmt->get_result();


    $subtotal = 0;

    $cartCount = 0;

    $itemCount = 0;


    while (
        $row =
        $cartResult->fetch_assoc()
    ) {

        $rowQuantity =
            (int) $row['quantity'];


        $rowPrice =
            (float) $row['price'];


        $subtotal +=
            $rowQuantity * $rowPrice;


        $cartCount +=
            $rowQuantity;


        $itemCount++;

    }


    $cartStmt->close();


    /* ==========================================================
       SHIPPING
    ========================================================== */

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | Shipping calculation is kept simple here.
    |
    | Actual delivery calculation can later be handled inside
    | checkout.php according to:
    |
    | - Pickup
    | - Postage
    | - Vendor
    | - Address
    |--------------------------------------------------------------------------
    */

    $shipping = 0.00;


    /* ==========================================================
       GRAND TOTAL
    ========================================================== */

    $total =
        $subtotal + $shipping;


    /* ==========================================================
       COMMIT
    ========================================================== */

    $conn->commit();


} catch (Throwable $e) {

    try {

        $conn->rollback();

    } catch (Throwable $rollbackError) {

        error_log(
            'HOCHIPOHUB update_cart rollback error: ' .
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

        'shipping' =>
            round(
                $shipping,
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
            $itemCount,

        'stock' =>
            $stockQuantity
    ]
);