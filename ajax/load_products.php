<?php

require_once "../config.php";
require_once "../database/db.php";


$search =
    trim($_GET['search'] ?? '');


$categoryID =
    (int) ($_GET['category_id'] ?? 0);


$sql = "

    SELECT

        products.product_id,
        products.product_name,
        products.description,
        products.price,
        products.stock_quantity,
        products.image,
        products.status,

        vendors.business_name,

        categories.category_name

    FROM products

    INNER JOIN vendors

        ON products.vendor_id =
           vendors.vendor_id

    INNER JOIN categories

        ON products.category_id =
           categories.category_id

    WHERE products.status = 'Available'

";


$params = [];
$types = "";


if ($search !== '') {

    $sql .= "

        AND (

            products.product_name LIKE ?

            OR vendors.business_name LIKE ?

            OR categories.category_name LIKE ?

        )

    ";

    $searchValue =
        "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";

}


if ($categoryID > 0) {

    $sql .= "

        AND products.category_id = ?

    ";

    $params[] = $categoryID;

    $types .= "i";

}


$sql .= "

    ORDER BY products.created_at DESC

";


$stmt =
    $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();


$result =
    $stmt->get_result();


$products = [];


while (
    $row = $result->fetch_assoc()
) {

    $products[] = $row;

}


header(
    'Content-Type: application/json'
);


echo json_encode(
    $products
);

exit();