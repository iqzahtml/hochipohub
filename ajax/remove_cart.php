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



$userID = currentUserID();

$cartID = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;



if ($cartID <= 0) {

    header(
        "Location: " . BASE_URL . "cart.php"
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| Delete Only User's Own Cart Item
|--------------------------------------------------------------------------
*/

$query = $conn->prepare("

    DELETE FROM cart

    WHERE cart_id = ?

    AND user_id = ?

");

$query->bind_param(
    "ii",
    $cartID,
    $userID
);

$query->execute();



header(
    "Location: " . BASE_URL . "cart.php"
);

exit();