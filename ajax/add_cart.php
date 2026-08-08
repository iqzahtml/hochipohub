<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";



/*
|--------------------------------------------------------------------------
| Login Check
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {

    header(
        "Location: " . BASE_URL . "index.php"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| Only POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " . BASE_URL . "catalog.php"
    );

    exit();

}



$userID = currentUserID();

$productID = isset($_POST['product_id'])
    ? (int) $_POST['product_id']
    : 0;

$quantity = isset($_POST['quantity'])
    ? (int) $_POST['quantity']
    : 1;



if ($productID <= 0) {

    header(
        "Location: " . BASE_URL . "catalog.php"
    );

    exit();

}



if ($quantity < 1) {

    $quantity = 1;

}



/*
|--------------------------------------------------------------------------
| Check Product
|--------------------------------------------------------------------------
*/

$productQuery = $conn->prepare("

    SELECT product_id, status

    FROM products

    WHERE product_id = ?

    LIMIT 1

");

$productQuery->bind_param(
    "i",
    $productID
);

$productQuery->execute();

$productResult = $productQuery->get_result();



if ($productResult->num_rows === 0) {

    header(
        "Location: " . BASE_URL . "catalog.php"
    );

    exit();

}



$product = $productResult->fetch_assoc();



if (strtolower($product['status']) !== 'available') {

    header(
        "Location: " . BASE_URL .
        "product_details.php?id=" . $productID
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| Check Existing Cart Item
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("

    SELECT cart_id, quantity

    FROM cart

    WHERE user_id = ?

    AND product_id = ?

    LIMIT 1

");

$check->bind_param(
    "ii",
    $userID,
    $productID
);

$check->execute();

$existing = $check->get_result();



if ($existing->num_rows > 0) {

    $cart = $existing->fetch_assoc();

    $newQuantity =
        (int)$cart['quantity'] + $quantity;



    $update = $conn->prepare("

        UPDATE cart

        SET quantity = ?

        WHERE cart_id = ?

    ");

    $update->bind_param(
        "ii",
        $newQuantity,
        $cart['cart_id']
    );

    $update->execute();



} else {



    /*
    |--------------------------------------------------------------------------
    | Add New Item
    |--------------------------------------------------------------------------
    */

    $insert = $conn->prepare("

        INSERT INTO cart

        (
            user_id,
            product_id,
            quantity,
            created_at
        )

        VALUES

        (
            ?,
            ?,
            ?,
            NOW()
        )

    ");

    $insert->bind_param(
        "iii",
        $userID,
        $productID,
        $quantity
    );

    $insert->execute();

}



/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: " . BASE_URL . "cart.php"
);

exit();