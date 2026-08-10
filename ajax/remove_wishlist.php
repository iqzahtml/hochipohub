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


$wishlistID =
    (int) ($_GET['id'] ?? 0);


$productID =
    (int) ($_GET['product_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| Remove By Wishlist ID
|--------------------------------------------------------------------------
*/

if ($wishlistID > 0) {

    $query = $conn->prepare("

        DELETE FROM wishlist

        WHERE wishlist_id = ?

        AND user_id = ?

    ");

    $query->bind_param(
        "ii",
        $wishlistID,
        $userID
    );


/*
|--------------------------------------------------------------------------
| Remove By Product ID
|--------------------------------------------------------------------------
*/

} elseif ($productID > 0) {

    $query = $conn->prepare("

        DELETE FROM wishlist

        WHERE product_id = ?

        AND user_id = ?

    ");

    $query->bind_param(
        "ii",
        $productID,
        $userID
    );


} else {

    header(
        "Location: " .
        BASE_URL .
        "wishlist.php"
    );

    exit();

}


$query->execute();


header(
    "Location: " .
    BASE_URL .
    "wishlist.php"
);

exit();