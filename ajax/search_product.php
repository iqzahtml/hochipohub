<?php

require_once "../config.php";
require_once "../database/db.php";



$search = isset($_GET['q'])
    ? trim($_GET['q'])
    : '';



header(
    'Content-Type: application/json'
);



if ($search === '') {

    echo json_encode([]);

    exit();

}



$search = $conn->real_escape_string($search);



$query = "

    SELECT

        products.product_id,

        products.product_name,

        products.price,

        products.image,

        vendors.business_name


    FROM products


    INNER JOIN vendors

        ON products.vendor_id = vendors.vendor_id


    WHERE products.status = 'Available'


    AND (

        products.product_name
        LIKE '%$search%'

        OR

        vendors.business_name
        LIKE '%$search%'

    )


    ORDER BY products.product_name ASC


    LIMIT 10

";



$result = $conn->query($query);



$products = [];



if ($result) {

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;

    }

}



echo json_encode($products);


exit();