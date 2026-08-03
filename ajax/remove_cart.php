<?php

/**
 * ============================================================
 * HOCHIPOHUB - REMOVE CART ITEM
 * ============================================================
 *
 * File:
 * ajax/remove_cart.php
 *
 * Purpose:
 * - Remove product from customer's cart
 * - Uses AJAX
 * - Returns JSON response
 * - Checks customer login
 * - Prevents deleting another customer's cart item
 *
 * Expected AJAX POST:
 * cart_id
 *
 * Example:
 * POST cart_id=15
 *
 * ============================================================
 */

declare(strict_types=1);


/* ============================================================
   RESPONSE HEADER
============================================================ */

header('Content-Type: application/json; charset=UTF-8');


/* ============================================================
   START SESSION
============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ============================================================
   DATABASE
============================================================ */

require_once __DIR__ . '/../database/db.php';


/* ============================================================
   HELPER: JSON RESPONSE
============================================================ */

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


/* ============================================================
   ONLY POST REQUEST
============================================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    jsonResponse(
        false,
        'Invalid request method.'
    );
}


/* ============================================================
   CHECK LOGIN
============================================================ */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    http_response_code(401);

    jsonResponse(
        false,
        'Please login to manage your cart.'
    );
}


/* ============================================================
   CHECK CUSTOMER ROLE
============================================================ */

if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] !== 'customer'
) {

    http_response_code(403);

    jsonResponse(
        false,
        'Only customers can manage the shopping cart.'
    );
}


/* ============================================================
   GET CUSTOMER ID
============================================================ */

$customerId = (int) $_SESSION['user_id'];


/* ============================================================
   GET CART ID
============================================================ */

$cartId = filter_input(
    INPUT_POST,
    'cart_id',
    FILTER_VALIDATE_INT
);


if (!$cartId || $cartId <= 0) {

    http_response_code(400);

    jsonResponse(
        false,
        'Invalid cart item.'
    );
}


/* ============================================================
   CHECK DATABASE CONNECTION
============================================================ */

if (!isset($conn) || !($conn instanceof mysqli)) {

    http_response_code(500);

    jsonResponse(
        false,
        'Database connection error.'
    );
}


/* ============================================================
   FIND CART ITEM
============================================================ */

/*
 * IMPORTANT:
 *
 * We check BOTH:
 *
 * cart_id
 * customer_id
 *
 * This prevents a customer from deleting another customer's
 * cart item simply by changing the cart_id in the request.
 */

$checkSql = "
    SELECT
        cart_id,
        customer_id,
        product_id,
        quantity
    FROM cart
    WHERE cart_id = ?
      AND customer_id = ?
    LIMIT 1
";


$checkStmt = $conn->prepare($checkSql);


if (!$checkStmt) {

    http_response_code(500);

    jsonResponse(
        false,
        'Unable to process cart request.'
    );
}


$checkStmt->bind_param(
    'ii',
    $cartId,
    $customerId
);


$checkStmt->execute();


$result = $checkStmt->get_result();


if ($result->num_rows === 0) {

    $checkStmt->close();

    http_response_code(404);

    jsonResponse(
        false,
        'Cart item was not found.'
    );
}


$cartItem = $result->fetch_assoc();


$checkStmt->close();


/* ============================================================
   DELETE CART ITEM
============================================================ */

$deleteSql = "
    DELETE FROM cart
    WHERE cart_id = ?
      AND customer_id = ?
";


$deleteStmt = $conn->prepare($deleteSql);


if (!$deleteStmt) {

    http_response_code(500);

    jsonResponse(
        false,
        'Unable to remove cart item.'
    );
}


$deleteStmt->bind_param(
    'ii',
    $cartId,
    $customerId
);


if (!$deleteStmt->execute()) {

    $deleteStmt->close();

    http_response_code(500);

    jsonResponse(
        false,
        'Failed to remove product from cart.'
    );
}


$deletedRows = $deleteStmt->affected_rows;


$deleteStmt->close();


/* ============================================================
   VERIFY DELETE
============================================================ */

if ($deletedRows <= 0) {

    http_response_code(404);

    jsonResponse(
        false,
        'Cart item could not be removed.'
    );
}


/* ============================================================
   GET UPDATED CART COUNT
============================================================ */

$countSql = "
    SELECT COALESCE(SUM(quantity), 0) AS cart_count
    FROM cart
    WHERE customer_id = ?
";


$countStmt = $conn->prepare($countSql);


$cartCount = 0;


if ($countStmt) {

    $countStmt->bind_param(
        'i',
        $customerId
    );

    $countStmt->execute();

    $countResult = $countStmt->get_result();

    if ($countRow = $countResult->fetch_assoc()) {

        $cartCount = (int) $countRow['cart_count'];
    }

    $countStmt->close();
}


/* ============================================================
   SUCCESS RESPONSE
============================================================ */

jsonResponse(
    true,
    'Product removed from cart successfully.',
    [
        'cart_id' => $cartId,
        'product_id' => (int) $cartItem['product_id'],
        'cart_count' => $cartCount
    ]
);