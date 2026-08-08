<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";

if (!isLoggedIn()) {

    header("Location: " . BASE_URL . "index.php");
    exit();

}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: " . BASE_URL . "catalog.php");
    exit();

}

$userID = currentUserID();

$productID = isset($_POST['product_id'])
    ? (int) $_POST['product_id']
    : 0;

if ($productID <= 0) {

    header("Location: " . BASE_URL . "catalog.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Check Product
|--------------------------------------------------------------------------
*/

$productQuery = $conn->prepare("

    SELECT product_id

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

    header("Location: " . BASE_URL . "catalog.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Check Existing Wishlist
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("

    SELECT wishlist_id

    FROM wishlist

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


/*
|--------------------------------------------------------------------------
| Add If Not Existing
|--------------------------------------------------------------------------
*/

if ($existing->num_rows === 0) {

    $insert = $conn->prepare("

        INSERT INTO wishlist

        (
            user_id,
            product_id,
            created_at
        )

        VALUES

        (
            ?,
            ?,
            NOW()
        )

    ");

    $insert->bind_param(
        "ii",
        $userID,
        $productID
    );

    $insert->execute();

}


header(
    "Location: " .
    BASE_URL .
    "wishlist.php"
);

exit();