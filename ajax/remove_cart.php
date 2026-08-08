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
    (int) ($_GET['id'] ?? 0);


if ($cartID <= 0) {

    header("Location: " . BASE_URL . "cart.php");
    exit();

}


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


header(
    "Location: " .
    BASE_URL .
    "cart.php"
);

exit();