<?php

require_once "../config.php";
require_once "../database/db.php";


$search =
    trim($_GET['q'] ?? '');


header(
    'Content-Type: application/json'
);


if ($search === '') {

    echo json_encode([]);

    exit();

}


$searchValue =
    "%" . $search . "%";


$query = $conn->prepare("

    SELECT

        products.product_id,
        products.product_name,
        products.price,
        products.image,

        vendors.business_name

    FROM products

    INNER JOIN vendors

        ON products.vendor_id =
           vendors.vendor_id

    WHERE products.status = 'Available'

    AND (

        products.product_name LIKE ?

        OR vendors.business_name LIKE ?

    )

    ORDER BY products.product_name ASC

    LIMIT 10

");


$query->bind_param(
    "ss",
    $searchValue,
    $searchValue
);


$query->execute();


$result =
    $query->get_result();


$products = [];


while (
    $row = $result->fetch_assoc()
) {

    $products[] = $row;

}


echo json_encode(
    $products
);

exit();