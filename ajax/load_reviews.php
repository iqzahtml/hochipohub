<?php

require_once "../config.php";
require_once "../database/db.php";

$productID = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;

if ($productID <= 0) {

    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID.'
    ]);

    exit();
}


$query = $conn->prepare("

    SELECT

        reviews.review_id,
        reviews.rating,
        reviews.review,
        reviews.review_date,

        users.name

    FROM reviews

    INNER JOIN users

        ON reviews.customer_id = users.user_id

    WHERE reviews.product_id = ?

    AND reviews.status = 'Visible'

    ORDER BY reviews.review_date DESC

");


$query->bind_param(
    "i",
    $productID
);


$query->execute();


$result = $query->get_result();


$reviews = [];


while ($row = $result->fetch_assoc()) {

    $reviews[] = $row;

}


header(
    'Content-Type: application/json'
);


echo json_encode([

    'success' => true,

    'reviews' => $reviews,

    'count' => count($reviews)

]);


exit();