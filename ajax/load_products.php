<?php

require_once "../config.php";
require_once "../database/db.php";



$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$categoryID = isset($_GET['category_id'])
    ? (int) $_GET['category_id']
    : 0;



/*
|--------------------------------------------------------------------------
| Base Query
|--------------------------------------------------------------------------
*/

$sql = "

SELECT

    products.product_id,

    products.product_name,

    products.price,

    products.image,

    products.status,

    vendors.business_name,

    categories.category_name


FROM products


INNER JOIN vendors

ON products.vendor_id = vendors.vendor_id


INNER JOIN categories

ON products.category_id = categories.category_id


WHERE products.status = 'Available'

";



/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $safeSearch =
        $conn->real_escape_string($search);


    $sql .= "

        AND (

            products.product_name
            LIKE '%$safeSearch%'

            OR

            categories.category_name
            LIKE '%$safeSearch%'

            OR

            vendors.business_name
            LIKE '%$safeSearch%'

        )

    ";

}



/*
|--------------------------------------------------------------------------
| Category Filter
|--------------------------------------------------------------------------
*/

if ($categoryID > 0) {

    $sql .= "

        AND products.category_id = $categoryID

    ";

}



/*
|--------------------------------------------------------------------------
| Latest Products
|--------------------------------------------------------------------------
*/

$sql .= "

ORDER BY products.created_at DESC

";



$result = $conn->query($sql);



/*
|--------------------------------------------------------------------------
| Return JSON
|--------------------------------------------------------------------------
*/

$products = [];



if ($result) {

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;

    }

}



header(
    'Content-Type: application/json'
);


echo json_encode(
    $products
);

exit();