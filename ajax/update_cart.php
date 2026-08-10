<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";


if (!isLoggedIn()) {

    header("Location: " . BASE_URL . "index.php");
    exit();

}


$customerID =
    (int) currentUserID();


$cartID =
    (int) ($_POST['cart_id'] ?? 0);


$quantity =
    (int) ($_POST['quantity'] ?? 1);


if ($cartID <= 0) {

    header("Location: " . BASE_URL . "cart.php");
    exit();

}


if ($quantity < 1) {

    $quantity = 1;

}


/*
|--------------------------------------------------------------------------
| Check Stock
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("

    SELECT
        products.stock_quantity

    FROM cart

    INNER JOIN products

        ON cart.product_id =
           products.product_id

    WHERE cart.cart_id = ?

    AND cart.customer_id = ?

    LIMIT 1

");

$check->bind_param(
    "ii",
    $cartID,
    $customerID
);

$check->execute();

$result =
    $check->get_result();


if ($result->num_rows === 0) {

    header("Location: " . BASE_URL . "cart.php");
    exit();

}


$product =
    $result->fetch_assoc();


$stock =
    (int)$product['stock_quantity'];


if ($quantity > $stock) {

    $quantity = $stock;

}


if ($quantity <= 0) {

    $delete = $conn->prepare("

        DELETE FROM cart

        WHERE cart_id = ?

        AND customer_id = ?

    ");

    $delete->bind_param(
        "ii",
        $cartID,
        $customerID
    );

    $delete->execute();


} else {


    $update = $conn->prepare("

        UPDATE cart

        SET quantity = ?

        WHERE cart_id = ?

        AND customer_id = ?

    ");

    $update->bind_param(
        "iii",
        $quantity,
        $cartID,
        $customerID
    );

    $update->execute();

}


header(
    "Location: " .
    BASE_URL .
    "cart.php"
);

exit();