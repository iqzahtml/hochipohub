<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX PRODUCT SEARCH
|--------------------------------------------------------------------------
| File:
| ajax/search_product.php
|
| Functions:
| - Live product search
| - Search by product name
| - Search by vendor/business name
| - Search by category
| - Return product suggestions as JSON
|--------------------------------------------------------------------------
*/

declare(strict_types=1);


// ==========================================================================
// SESSION
// ==========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================================================
// JSON HEADER
// ==========================================================================

header('Content-Type: application/json; charset=UTF-8');


// ==========================================================================
// DATABASE
// ==========================================================================

require_once __DIR__ . '/../database/db.php';


// ==========================================================================
// DATABASE CONNECTION CHECK
// ==========================================================================

if (!isset($conn) || !($conn instanceof mysqli)) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.',
        'products' => []
    ]);

    exit;
}


// ==========================================================================
// RESPONSE FUNCTION
// ==========================================================================

function searchResponse(
    bool $success,
    string $message = '',
    array $data = [],
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// ==========================================================================
// GET SEARCH KEYWORD
// ==========================================================================

$keyword = '';

if (isset($_GET['q'])) {

    $keyword = trim(
        (string) $_GET['q']
    );

} elseif (isset($_GET['search'])) {

    $keyword = trim(
        (string) $_GET['search']
    );

} elseif (isset($_POST['q'])) {

    $keyword = trim(
        (string) $_POST['q']
    );

} elseif (isset($_POST['search'])) {

    $keyword = trim(
        (string) $_POST['search']
    );

}


// ==========================================================================
// CLEAN SEARCH KEYWORD
// ==========================================================================

$keyword = preg_replace(
    '/\s+/',
    ' ',
    $keyword
);

$keyword = trim(
    (string) $keyword
);


// ==========================================================================
// SEARCH LENGTH
// ==========================================================================

if ($keyword === '') {

    searchResponse(
        true,
        '',
        [
            'products' => [],
            'count' => 0
        ]
    );

}


// ==========================================================================
// LIMIT
// ==========================================================================

$limit = 8;


// ==========================================================================
// SEARCH TERM
// ==========================================================================

$searchTerm = '%' . $keyword . '%';


// ==========================================================================
// PRODUCT SEARCH
// ==========================================================================
//
// Search through:
// 1. Product name
// 2. Product description
// 3. Vendor business name
// 4. Category name
//
// Only Available products are returned.
// ==========================================================================

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
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories c
        ON p.category_id = c.category_id

    WHERE

        p.status = 'Available'

        AND p.stock_quantity > 0

        AND v.approval_status = 'Approved'

        AND (

            p.product_name LIKE ?

            OR p.description LIKE ?

            OR v.business_name LIKE ?

            OR c.category_name LIKE ?

        )

    ORDER BY

        CASE

            WHEN p.product_name LIKE ? THEN 1

            WHEN v.business_name LIKE ? THEN 2

            WHEN c.category_name LIKE ? THEN 3

            ELSE 4

        END,

        p.created_at DESC

    LIMIT ?
";


// ==========================================================================
// PREPARE
// ==========================================================================

$stmt = $conn->prepare($sql);

if (!$stmt) {

    searchResponse(
        false,
        'Unable to search products.',
        [
            'products' => []
        ],
        500
    );

}


// ==========================================================================
// BIND PARAMETERS
// ==========================================================================

$stmt->bind_param(
    'sssssssi',
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $limit
);


// ==========================================================================
// EXECUTE
// ==========================================================================

if (!$stmt->execute()) {

    $stmt->close();

    searchResponse(
        false,
        'Product search failed.',
        [
            'products' => []
        ],
        500
    );

}


// ==========================================================================
// GET RESULT
// ==========================================================================

$result = $stmt->get_result();


// ==========================================================================
// PRODUCTS ARRAY
// ==========================================================================

$products = [];


// ==========================================================================
// LOOP PRODUCTS
// ==========================================================================

while ($row = $result->fetch_assoc()) {

    // ----------------------------------------------------------------------
    // Product image
    // ----------------------------------------------------------------------

    $image = '';

    if (
        isset($row['image']) &&
        trim((string) $row['image']) !== ''
    ) {

        $image = trim(
            (string) $row['image']
        );

    }


    // ----------------------------------------------------------------------
    // Vendor logo
    // ----------------------------------------------------------------------

    $vendorLogo = '';

    if (
        isset($row['business_logo']) &&
        trim((string) $row['business_logo']) !== ''
    ) {

        $vendorLogo = trim(
            (string) $row['business_logo']
        );

    }


    // ----------------------------------------------------------------------
    // Price
    // ----------------------------------------------------------------------

    $price = number_format(
        (float) $row['price'],
        2,
        '.',
        ','
    );


    // ----------------------------------------------------------------------
    // Stock
    // ----------------------------------------------------------------------

    $stock = (int) (
        $row['stock_quantity'] ?? 0
    );


    // ----------------------------------------------------------------------
    // Description
    // ----------------------------------------------------------------------

    $description = trim(
        strip_tags(
            (string) (
                $row['description'] ?? ''
            )
        )
    );


    // ----------------------------------------------------------------------
    // Short description
    // ----------------------------------------------------------------------

    if (mb_strlen($description) > 100) {

        $description =
            mb_substr(
                $description,
                0,
                100
            ) . '...';

    }


    // ----------------------------------------------------------------------
    // Add product
    // ----------------------------------------------------------------------

    $products[] = [

        'product_id' => (int) $row['product_id'],

        'product_name' => htmlspecialchars(
            (string) $row['product_name'],
            ENT_QUOTES,
            'UTF-8'
        ),

        'description' => htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        ),

        'price' => $price,

        'price_raw' => (float) $row['price'],

        'stock_quantity' => $stock,

        'image' => $image,

        'status' => $row['status'],

        'vendor_id' => (int) $row['vendor_id'],

        'business_name' => htmlspecialchars(
            (string) $row['business_name'],
            ENT_QUOTES,
            'UTF-8'
        ),

        'business_logo' => $vendorLogo,

        'category_id' => (int) $row['category_id'],

        'category_name' => htmlspecialchars(
            (string) $row['category_name'],
            ENT_QUOTES,
            'UTF-8'
        ),

        'url' =>
            'product_details.php?id=' .
            (int) $row['product_id']

    ];

}


// ==========================================================================
// CLOSE STATEMENT
// ==========================================================================

$stmt->close();


// ==========================================================================
// RETURN RESULTS
// ==========================================================================

searchResponse(
    true,
    '',
    [
        'products' => $products,
        'count' => count($products),
        'keyword' => htmlspecialchars(
            $keyword,
            ENT_QUOTES,
            'UTF-8'
        )
    ]
);