<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX SEARCH PRODUCT
|--------------------------------------------------------------------------
| File:
| ajax/search_product.php
|
| Purpose:
| Search marketplace products asynchronously.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/database/db.php';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=UTF-8'
);


/*
|--------------------------------------------------------------------------
| ONLY GET / POST REQUEST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'GET'
    &&
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    http_response_code(405);

    echo json_encode([

        'success' => false,

        'message' =>
            'Invalid request method.',

        'products' => []

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET SEARCH KEYWORD
|--------------------------------------------------------------------------
|
| Supports:
| ?keyword=phone
|
| or POST:
| keyword=phone
|
|--------------------------------------------------------------------------
*/

$keyword = $_GET['keyword']
    ?? $_POST['keyword']
    ?? '';


$keyword =
    trim(
        (string) $keyword
    );


/*
|--------------------------------------------------------------------------
| VALIDATE KEYWORD
|--------------------------------------------------------------------------
*/

if (
    $keyword === ''
) {

    echo json_encode([

        'success' => true,

        'message' =>
            'Please enter a search keyword.',

        'products' => []

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| LIMIT
|--------------------------------------------------------------------------
*/

$limit =
    filter_input(
        INPUT_GET,
        'limit',
        FILTER_VALIDATE_INT
    );


if (
    !$limit
    ||
    $limit < 1
) {

    $limit = 20;
}


$limit =
    min(
        $limit,
        50
    );


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

try {

    $search =
        '%' . $keyword . '%';


    /*
    |--------------------------------------------------------------------------
    | PRODUCT QUERY
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                p.product_id,
                p.product_name,
                p.description,
                p.price,
                p.stock_quantity,
                p.image,
                p.status,
                p.created_at,

                v.vendor_id,
                v.business_name,
                v.business_logo,

                c.category_id,
                c.category_name

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN categories c
                ON p.category_id = c.category_id

            WHERE

            (
                p.product_name LIKE ?

                OR p.description LIKE ?

                OR v.business_name LIKE ?

                OR c.category_name LIKE ?
            )

            AND p.status = 'Available'

            AND p.stock_quantity > 0

            AND v.approval_status = 'Approved'

            ORDER BY
                p.created_at DESC

            LIMIT {$limit}
        ");


    $stmt->execute([

        $search,

        $search,

        $search,

        $search

    ]);


    $products =
        $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | FORMAT PRODUCT IMAGE
    |--------------------------------------------------------------------------
    */

    foreach (
        $products
        as &$product
    ) {

        $product['image_url'] =
            productImageUrl(
                $product['image']
            );


        $product['formatted_price'] =
            formatMoney(
                (float)
                $product['price']
            );
    }

    unset($product);


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'keyword' =>
            $keyword,

        'count' =>
            count($products),

        'products' =>
            $products

    ]);

    exit;


} catch (
    PDOException $e
) {

    http_response_code(500);


    if (
        APP_DEBUG
    ) {

        echo json_encode([

            'success' => false,

            'message' =>
                $e->getMessage(),

            'products' => []

        ]);

    } else {

        echo json_encode([

            'success' => false,

            'message' =>
                'Unable to search products.',

            'products' => []

        ]);
    }

    exit;
}