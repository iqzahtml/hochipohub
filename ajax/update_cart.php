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



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " . BASE_URL . "cart.php"
    );

    exit();

}



$userID = currentUserID();

$cartID = isset($_POST['cart_id'])
    ? (int) $_POST['cart_id']
    : 0;

$quantity = isset($_POST['quantity'])
    ? (int) $_POST['quantity']
    : 1;



if ($cartID <= 0) {

    header(
        "Location: " . BASE_URL . "cart.php"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| Quantity Validation
|--------------------------------------------------------------------------
*/

if ($quantity < 1) {

    $quantity = 1;

}



/*
|--------------------------------------------------------------------------
| Update Only User's Own Cart
|--------------------------------------------------------------------------
*/

$query = $conn->prepare("

    UPDATE cart

    SET quantity = ?

    WHERE cart_id = ?

    AND user_id = ?

");

$query->bind_param(
    "iii",
    $quantity,
    $cartID,
    $userID
);

$query->execute();



header(
    "Location: " . BASE_URL . "cart.php"
);

exit();