<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX LOAD PRODUCTS
|--------------------------------------------------------------------------
| File:
| ajax/load_products.php
|
| Purpose:
| Load available products dynamically.
|
| Supported filters:
| - category_id
| - keyword
| - limit
| - offset
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
| ONLY GET REQUEST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'GET'
) {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

$categoryId =
    filter_input(
        INPUT_GET,
        'category_id',
        FILTER_VALIDATE_INT
    );


if (
    $categoryId === false
    ||
    $categoryId === null
    ||
    $categoryId <= 0
) {

    $categoryId = null;
}


/*
|--------------------------------------------------------------------------
| KEYWORD FILTER
|--------------------------------------------------------------------------
*/

$keyword =
    trim(
        $_GET['keyword']
        ?? ''
    );


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
    $limit === false
    ||
    $limit === null
) {

    $limit = 12;
}


/*
|--------------------------------------------------------------------------
| LIMIT RANGE
|--------------------------------------------------------------------------
*/

$limit =
    max(
        1,
        min(
            $limit,
            100
        )
    );


/*
|--------------------------------------------------------------------------
| OFFSET
|--------------------------------------------------------------------------
*/

$offset =
    filter_input(
        INPUT_GET,
        'offset',
        FILTER_VALIDATE_INT
    );


if (
    $offset === false
    ||
    $offset === null
    ||
    $offset < 0
) {

    $offset = 0;
}


/*
|--------------------------------------------------------------------------
| DATABASE QUERY
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            p.vendor_id,
            p.category_id,
            p.created_at,

            v.business_name,
            v.business_logo,

            c.category_name

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.status = 'Available'

        AND p.stock_quantity > 0

        AND v.approval_status = 'Approved'
    ";


    /*
    |--------------------------------------------------------------------------
    | PARAMETERS
    |--------------------------------------------------------------------------
    */

    $params = [];


    /*
    |--------------------------------------------------------------------------
    | CATEGORY FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $categoryId !== null
    ) {

        $sql .= "
            AND p.category_id = ?
        ";

        $params[] =
            $categoryId;
    }


    /*
    |--------------------------------------------------------------------------
    | KEYWORD SEARCH
    |--------------------------------------------------------------------------
    */

    if (
        $keyword !== ''
    ) {

        $search =
            '%' . $keyword . '%';


        $sql .= "
            AND (
                p.product_name LIKE ?
                OR p.description LIKE ?
                OR v.business_name LIKE ?
                OR c.category_name LIKE ?
            )
        ";


        $params[] =
            $search;

        $params[] =
            $search;

        $params[] =
            $search;

        $params[] =
            $search;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY p.created_at DESC
    ";


    /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    |
    | LIMIT and OFFSET are integers already
    | validated above, so they can safely be
    | inserted into the SQL statement.
    |
    */

    $sql .= "
        LIMIT {$limit}
        OFFSET {$offset}
    ";


    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $products =
        $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | TOTAL COUNT
    |--------------------------------------------------------------------------
    */

    $countSql = "
        SELECT COUNT(*)

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.status = 'Available'

        AND p.stock_quantity > 0

        AND v.approval_status = 'Approved'
    ";


    $countParams = [];


    /*
    |--------------------------------------------------------------------------
    | CATEGORY COUNT FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $categoryId !== null
    ) {

        $countSql .= "
            AND p.category_id = ?
        ";

        $countParams[] =
            $categoryId;
    }


    /*
    |--------------------------------------------------------------------------
    | KEYWORD COUNT FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $keyword !== ''
    ) {

        $search =
            '%' . $keyword . '%';


        $countSql .= "
            AND (
                p.product_name LIKE ?
                OR p.description LIKE ?
                OR v.business_name LIKE ?
                OR c.category_name LIKE ?
            )
        ";


        $countParams[] =
            $search;

        $countParams[] =
            $search;

        $countParams[] =
            $search;

        $countParams[] =
            $search;
    }


    /*
    |--------------------------------------------------------------------------
    | EXECUTE COUNT
    |--------------------------------------------------------------------------
    */

    $countStmt =
        $db->prepare(
            $countSql
        );


    $countStmt->execute(
        $countParams
    );


    $total =
        (int)
        $countStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | FORMAT PRODUCTS
    |--------------------------------------------------------------------------
    */

    $formattedProducts = [];


    foreach (
        $products
        as $product
    ) {

        $formattedProducts[] = [

            'product_id' =>
                (int)
                $product['product_id'],

            'product_name' =>
                $product['product_name'],

            'description' =>
                $product['description'],

            'price' =>
                (float)
                $product['price'],

            'formatted_price' =>
                formatMoney(
                    (float)
                    $product['price']
                ),

            'stock_quantity' =>
                (int)
                $product['stock_quantity'],

            'image' =>
                $productImage =
                    productImageUrl(
                        $product['image']
                    ),

            'status' =>
                $product['status'],

            'vendor_id' =>
                (int)
                $product['vendor_id'],

            'business_name' =>
                $product['business_name'],

            'business_logo' =>
                vendorLogoUrl(
                    $product['business_logo']
                ),

            'category_id' =>
                (int)
                $product['category_id'],

            'category_name' =>
                $product['category_name'],

            'created_at' =>
                $product['created_at'],

            'formatted_date' =>
                formatDateTime(
                    $product['created_at']
                )
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PAGINATION INFORMATION
    |--------------------------------------------------------------------------
    */

    $nextOffset =
        $offset + $limit;


    $hasMore =
        $nextOffset < $total;


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' =>
            true,

        'message' =>
            'Products loaded successfully.',

        'products' =>
            $formattedProducts,

        'count' =>
            count(
                $formattedProducts
            ),

        'total' =>
            $total,

        'limit' =>
            $limit,

        'offset' =>
            $offset,

        'next_offset' =>
            $hasMore
            ? $nextOffset
            : null,

        'has_more' =>
            $hasMore

    ]);

    exit;


} catch (
    PDOException $e
) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    http_response_code(500);


    if (
        APP_DEBUG
    ) {

        echo json_encode([

            'success' =>
                false,

            'message' =>
                $e->getMessage()

        ]);

    } else {

        echo json_encode([

            'success' =>
                false,

            'message' =>
                'Unable to load products.'

        ]);
    }

    exit;
}