<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX PRODUCT SEARCH
|--------------------------------------------------------------------------
| File:
| ajax/search_product.php
|
| Purpose:
| - Live AJAX product search
| - Search by product
| - Search by description
| - Search by vendor
| - Search by category
| - Return JSON response
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
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


if (!($db instanceof PDO)) {

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' =>
                'Database connection is not available.',
            'products' => []
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'GET'
    &&
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    http_response_code(405);

    echo json_encode(
        [
            'success' => false,
            'message' =>
                'Invalid request method.',
            'products' => []
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET KEYWORD
|--------------------------------------------------------------------------
*/

$keyword =
    $_GET['keyword']
    ?? $_POST['keyword']
    ?? '';


$keyword =
    trim(
        (string) $keyword
    );


/*
|--------------------------------------------------------------------------
| EMPTY KEYWORD
|--------------------------------------------------------------------------
*/

if ($keyword === '') {

    echo json_encode(
        [
            'success' => true,
            'keyword' => '',
            'count' => 0,
            'products' => []
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| RESULT LIMIT
|--------------------------------------------------------------------------
*/

$limit =
    isset($_GET['limit'])
        ? (int) $_GET['limit']
        : 8;


if ($limit < 1) {
    $limit = 8;
}


if ($limit > 20) {
    $limit = 20;
}


/*
|--------------------------------------------------------------------------
| IMAGE HELPER
|--------------------------------------------------------------------------
*/

if (
    !function_exists(
        'ajaxSearchImage'
    )
) {

    function ajaxSearchImage(
        $image
    ): string {

        $image =
            trim(
                (string) $image
            );


        /*
        |--------------------------------------------------------------------------
        | DEFAULT IMAGE
        |--------------------------------------------------------------------------
        */

        if ($image === '') {

            return
                BASE_URL .
                'image/logo.jpeg';

        }


        /*
        |--------------------------------------------------------------------------
        | EXTERNAL IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            strpos(
                $image,
                'http://'
            ) === 0
            ||
            strpos(
                $image,
                'https://'
            ) === 0
        ) {

            return $image;

        }


        /*
        |--------------------------------------------------------------------------
        | ALREADY HAS UPLOAD PATH
        |--------------------------------------------------------------------------
        */

        if (
            strpos(
                $image,
                'uploads/'
            ) === 0
        ) {

            return
                BASE_URL .
                ltrim(
                    $image,
                    '/'
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT IMAGE FILENAME
        |--------------------------------------------------------------------------
        */

        return
            BASE_URL .
            'uploads/products/' .
            rawurlencode(
                basename(
                    $image
                )
            );

    }

}


/*
|--------------------------------------------------------------------------
| SEARCH PRODUCTS
|--------------------------------------------------------------------------
*/

try {

    $searchTerm =
        '%' .
        $keyword .
        '%';


    /*
    |--------------------------------------------------------------------------
    | QUERY
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
            p.created_at,

            v.vendor_id,
            v.business_name,
            v.business_logo,

            c.category_id,
            c.category_name

        FROM products p

        INNER JOIN vendors v
            ON p.vendor_id =
               v.vendor_id

        INNER JOIN categories c
            ON p.category_id =
               c.category_id

        WHERE
        (
            p.product_name LIKE ?

            OR p.description LIKE ?

            OR v.business_name LIKE ?

            OR c.category_name LIKE ?
        )

        AND p.status != 'Hidden'

        AND v.approval_status =
            'Approved'

        ORDER BY

            CASE

                WHEN p.product_name LIKE ?
                    THEN 1

                WHEN v.business_name LIKE ?
                    THEN 2

                WHEN c.category_name LIKE ?
                    THEN 3

                ELSE 4

            END ASC,

            p.created_at DESC

        LIMIT {$limit}
    ";


    /*
    |--------------------------------------------------------------------------
    | PREPARE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare(
            $sql
        );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    $stmt->execute(
        [
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,

            $searchTerm,
            $searchTerm,
            $searchTerm
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | FETCH PRODUCTS
    |--------------------------------------------------------------------------
    */

    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


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

        $productId =
            (int) (
                $product[
                    'product_id'
                ]
                ?? 0
            );


        if ($productId <= 0) {
            continue;
        }


        $price =
            (float) (
                $product[
                    'price'
                ]
                ?? 0
            );


        $stockQuantity =
            (int) (
                $product[
                    'stock_quantity'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | STOCK STATUS
        |--------------------------------------------------------------------------
        */

        if ($stockQuantity <= 0) {

            $stockStatus =
                'Out of Stock';

        } elseif (
            $stockQuantity <= 5
        ) {

            $stockStatus =
                'Low Stock';

        } else {

            $stockStatus =
                'In Stock';

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT RESPONSE
        |--------------------------------------------------------------------------
        */

        $formattedProducts[] = [

            'product_id' =>
                $productId,

            'product_name' =>
                (string) (
                    $product[
                        'product_name'
                    ]
                    ?? ''
                ),

            'description' =>
                (string) (
                    $product[
                        'description'
                    ]
                    ?? ''
                ),

            'price' =>
                $price,

            'formatted_price' =>
                number_format(
                    $price,
                    2
                ),

            'stock_quantity' =>
                $stockQuantity,

            'stock_status' =>
                $stockStatus,

            'image' =>
                (string) (
                    $product[
                        'image'
                    ]
                    ?? ''
                ),

            'image_url' =>
                ajaxSearchImage(
                    $product[
                        'image'
                    ]
                    ?? ''
                ),

            'status' =>
                (string) (
                    $product[
                        'status'
                    ]
                    ?? ''
                ),

            'vendor_id' =>
                (int) (
                    $product[
                        'vendor_id'
                    ]
                    ?? 0
                ),

            'business_name' =>
                (string) (
                    $product[
                        'business_name'
                    ]
                    ?? ''
                ),

            'business_logo' =>
                (string) (
                    $product[
                        'business_logo'
                    ]
                    ?? ''
                ),

            'category_id' =>
                (int) (
                    $product[
                        'category_id'
                    ]
                    ?? 0
                ),

            'category_name' =>
                (string) (
                    $product[
                        'category_name'
                    ]
                    ?? ''
                ),

            'product_url' =>
                BASE_URL .
                'product_details.php?id=' .
                $productId

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [
            'success' =>
                true,

            'keyword' =>
                $keyword,

            'count' =>
                count(
                    $formattedProducts
                ),

            'products' =>
                $formattedProducts
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );


    exit;


} catch (
    Throwable $e
) {

    /*
    |--------------------------------------------------------------------------
    | ERROR RESPONSE
    |--------------------------------------------------------------------------
    */

    http_response_code(500);


    $message =
        'Unable to search products.';


    if (
        defined('APP_DEBUG')
        &&
        APP_DEBUG
    ) {

        $message =
            $e->getMessage();

    }


    echo json_encode(
        [
            'success' =>
                false,

            'message' =>
                $message,

            'products' =>
                []
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );


    exit;

}