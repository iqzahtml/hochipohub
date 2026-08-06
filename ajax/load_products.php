<?php

session_start();

require_once "../config/db.php";


header("Content-Type: application/json");



/*
|--------------------------------------------------------------------------
| GET FILTER DATA
|--------------------------------------------------------------------------
*/


$category_id = $_POST['category_id'] ?? '';

$keyword = $_POST['keyword'] ?? '';

$page = $_POST['page'] ?? 1;



$limit = 12;

$offset = ($page - 1) * $limit;




/*
|--------------------------------------------------------------------------
| BASE QUERY
|--------------------------------------------------------------------------
*/


$sql = "

SELECT


products.product_id,

products.product_name,

products.price,

products.image,

products.stock,


categories.category_name,


vendors.business_name


FROM products



LEFT JOIN categories

ON products.category_id = categories.category_id



LEFT JOIN vendors

ON products.vendor_id = vendors.vendor_id



WHERE 1=1



";




$params = [];

$types = "";





/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/


if(!empty($category_id)){


$sql .= "

AND products.category_id = ?

";


$params[] = $category_id;

$types .= "i";


}





/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/


if(!empty($keyword)){


$sql .= "

AND products.product_name LIKE ?

";


$search = "%".$keyword."%";


$params[] = $search;

$types .= "s";


}





/*
|--------------------------------------------------------------------------
| ORDER + LIMIT
|--------------------------------------------------------------------------
*/


$sql .= "

ORDER BY products.product_id DESC

LIMIT ? OFFSET ?

";



$params[] = $limit;

$params[] = $offset;


$types .= "ii";







$stmt = $conn->prepare($sql);



if(!empty($params)){


$stmt->bind_param(

$types,

...$params

);


}



$stmt->execute();



$result = $stmt->get_result();





/*
|--------------------------------------------------------------------------
| RETURN PRODUCT DATA
|--------------------------------------------------------------------------
*/


$products = [];



while($row = $result->fetch_assoc()){



$products[] = [


"product_id" => $row['product_id'],


"product_name" => $row['product_name'],


"price" => number_format(
$row['price'],
2
),



"image" => $row['image'],



"stock" => $row['stock'],



"category" => $row['category_name'],



"vendor" => $row['business_name']



];



}





echo json_encode([


"status" => "success",


"data" => $products



]);



?>