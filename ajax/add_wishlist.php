<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";


if (!isLoggedIn()) {

    header("Location: " . BASE_URL . "index.php");
    exit();

}


$userID =
    (int) currentUserID();


$productID =
    (int) ($_POST['product_id'] ?? 0);


if ($productID <= 0) {

    header("Location: " . BASE_URL . "catalog.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Check Product
|--------------------------------------------------------------------------
*/

$product = $conn->prepare("

    SELECT product_id

    FROM products

    WHERE product_id = ?

    LIMIT 1

");

$product->bind_param(
    "i",
    $productID
);

$product->execute();


if (
    $product->get_result()->num_rows === 0
) {

    header("Location: " . BASE_URL . "catalog.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Insert Wishlist
|--------------------------------------------------------------------------
*/

$insert = $conn->prepare("

    INSERT IGNORE INTO wishlist

    (
        user_id,
        product_id
    )

    VALUES
    (
        ?,
        ?
    )

");

$insert->bind_param(
    "ii",
    $userID,
    $productID
);

$insert->execute();


header(
    "Location: " .
    BASE_URL .
    "wishlist.php"
);

exit();