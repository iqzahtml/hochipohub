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


$productID =
    (int) ($_POST['product_id'] ?? 0);


$quantity =
    (int) ($_POST['quantity'] ?? 1);


if ($productID <= 0) {

    header("Location: " . BASE_URL . "catalog.php");
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

$product = $conn->prepare("

    SELECT
        product_id,
        stock_quantity,
        status

    FROM products

    WHERE product_id = ?

    LIMIT 1

");

$product->bind_param(
    "i",
    $productID
);

$product->execute();

$result =
    $product->get_result();


if ($result->num_rows === 0) {

    header("Location: " . BASE_URL . "catalog.php");
    exit();

}


$productData =
    $result->fetch_assoc();


if ($productData['status'] !== 'Available') {

    header(
        "Location: " .
        BASE_URL .
        "product_details.php?id=" .
        $productID
    );

    exit();

}


if ($quantity > (int)$productData['stock_quantity']) {

    $quantity =
        (int)$productData['stock_quantity'];

}


if ($quantity <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "product_details.php?id=" .
        $productID
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| Check Existing Cart
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("

    SELECT
        cart_id,
        quantity

    FROM cart

    WHERE customer_id = ?

    AND product_id = ?

    LIMIT 1

");

$check->bind_param(
    "ii",
    $customerID,
    $productID
);

$check->execute();

$existing =
    $check->get_result();


if ($existing->num_rows > 0) {

    $cart =
        $existing->fetch_assoc();


    $newQuantity =
        (int)$cart['quantity'] + $quantity;


    if (
        $newQuantity >
        (int)$productData['stock_quantity']
    ) {

        $newQuantity =
            (int)$productData['stock_quantity'];

    }


    $update = $conn->prepare("

        UPDATE cart

        SET quantity = ?

        WHERE cart_id = ?

        AND customer_id = ?

    ");

    $update->bind_param(
        "iii",
        $newQuantity,
        $cart['cart_id'],
        $customerID
    );

    $update->execute();


} else {


    $insert = $conn->prepare("

        INSERT INTO cart

        (
            customer_id,
            product_id,
            quantity
        )

        VALUES
        (
            ?,
            ?,
            ?
        )

    ");

    $insert->bind_param(
        "iii",
        $customerID,
        $productID,
        $quantity
    );

    $insert->execute();

}


header(
    "Location: " .
    BASE_URL .
    "cart.php"
);

exit();