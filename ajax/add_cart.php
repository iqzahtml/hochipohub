<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX ADD TO CART
|--------------------------------------------------------------------------
| File:
| ajax/add_cart.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/database/db.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| JSON ONLY
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=UTF-8'
);


/*
|--------------------------------------------------------------------------
| PREVENT PHP HTML ERRORS FROM BREAKING JSON
|--------------------------------------------------------------------------
*/

ini_set(
    'display_errors',
    '0'
);


/*
|--------------------------------------------------------------------------
| JSON RESPONSE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('cartJsonResponse')) {

    function cartJsonResponse(
        bool $success,
        string $message,
        array $extra = [],
        int $statusCode = 200
    ): void {

        http_response_code(
            $statusCode
        );


        echo json_encode(
            array_merge(
                [
                    'success' => $success,
                    'message' => $message
                ],
                $extra
            ),
            JSON_UNESCAPED_UNICODE
        );


        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF COMPATIBILITY
|--------------------------------------------------------------------------
|
| Your project has used different CSRF function names:
|
| validateCsrfToken()
| verifyCsrfToken()
|
| This supports both.
|
|--------------------------------------------------------------------------
*/

if (!function_exists('cartValidateCsrf')) {

    function cartValidateCsrf(
        string $token
    ): bool {

        if ($token === '') {
            return false;
        }


        if (
            function_exists(
                'validateCsrfToken'
            )
        ) {

            return (bool)
                validateCsrfToken(
                    $token
                );
        }


        if (
            function_exists(
                'verifyCsrfToken'
            )
        ) {

            return (bool)
                verifyCsrfToken(
                    $token
                );
        }


        return false;
    }
}


/*
|--------------------------------------------------------------------------
| CURRENT USER COMPATIBILITY
|--------------------------------------------------------------------------
*/

if (!function_exists('cartCurrentUserId')) {

    function cartCurrentUserId(): int
    {
        if (
            function_exists(
                'currentUserId'
            )
        ) {

            return (int)
                currentUserId();
        }


        if (
            function_exists(
                'getUserId'
            )
        ) {

            return (int)
                getUserId();
        }


        return (int) (
            $_SESSION['user_id']
            ?? 0
        );
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN COMPATIBILITY
|--------------------------------------------------------------------------
*/

if (!function_exists('cartIsLoggedIn')) {

    function cartIsLoggedIn(): bool
    {
        if (
            function_exists(
                'isLoggedIn'
            )
        ) {

            return (bool)
                isLoggedIn();
        }


        return !empty(
            $_SESSION['user_id']
        );
    }
}


/*
|--------------------------------------------------------------------------
| CUSTOMER ROLE COMPATIBILITY
|--------------------------------------------------------------------------
*/

if (!function_exists('cartIsCustomer')) {

    function cartIsCustomer(): bool
    {
        if (
            function_exists(
                'isCustomer'
            )
        ) {

            return (bool)
                isCustomer();
        }


        $role =
            strtolower(
                trim(
                    (string) (
                        $_SESSION['role']
                        ?? $_SESSION['user_role']
                        ?? ''
                    )
                )
            );


        return $role === 'customer';
    }
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    if (
        !isset($db) ||
        !($db instanceof PDO)
    ) {

        $db = getDB();
    }


    if (!($db instanceof PDO)) {

        cartJsonResponse(
            false,
            'Database connection is not available.',
            [],
            500
        );
    }


} catch (Throwable $e) {

    cartJsonResponse(
        false,
        'Unable to connect to database.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    cartJsonResponse(
        false,
        'Invalid request method.',
        [],
        405
    );
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!cartIsLoggedIn()) {

    cartJsonResponse(
        false,
        'Please login to add products to your cart.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER ONLY
|--------------------------------------------------------------------------
*/

if (!cartIsCustomer()) {

    cartJsonResponse(
        false,
        'Only customer accounts can add products to cart.',
        [],
        403
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$csrfToken =
    trim(
        (string) (
            $_POST['csrf_token']
            ?? ''
        )
    );


if (
    !cartValidateCsrf(
        $csrfToken
    )
) {

    cartJsonResponse(
        false,
        'Invalid security token. Please refresh the page and try again.',
        [],
        403
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId =
    filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );


if (
    !$productId ||
    $productId <= 0
) {

    cartJsonResponse(
        false,
        'Invalid product.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| QUANTITY
|--------------------------------------------------------------------------
*/

$quantity =
    filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );


if (
    $quantity === false ||
    $quantity === null
) {

    $quantity = 1;
}


$quantity =
    (int) $quantity;


if ($quantity <= 0) {

    cartJsonResponse(
        false,
        'Quantity must be at least 1.',
        [],
        400
    );
}


if ($quantity > 999) {

    cartJsonResponse(
        false,
        'Quantity is too large.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER ID
|--------------------------------------------------------------------------
*/

$customerId =
    cartCurrentUserId();


if ($customerId <= 0) {

    cartJsonResponse(
        false,
        'Customer session not found.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                p.product_id,
                p.product_name,
                p.price,
                p.stock_quantity,
                p.status,
                p.vendor_id,

                v.approval_status,

                u.status AS vendor_user_status

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN users u
                ON v.user_id = u.user_id

            WHERE p.product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $productId
    ]);


    $product =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    cartJsonResponse(
        false,
        'Unable to load product.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$product) {

    cartJsonResponse(
        false,
        'Product not found.',
        [],
        404
    );
}


/*
|--------------------------------------------------------------------------
| NORMALIZE STATUS
|--------------------------------------------------------------------------
*/

$productStatus =
    strtolower(
        trim(
            (string)
            $product['status']
        )
    );


$vendorApproval =
    strtolower(
        trim(
            (string)
            $product['approval_status']
        )
    );


$vendorUserStatus =
    strtolower(
        trim(
            (string)
            $product['vendor_user_status']
        )
    );


/*
|--------------------------------------------------------------------------
| PRODUCT STATUS
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $productStatus,
        [
            'available',
            'active'
        ],
        true
    )
) {

    cartJsonResponse(
        false,
        'This product is currently unavailable.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR APPROVAL
|--------------------------------------------------------------------------
*/

if (
    $vendorApproval !==
    'approved'
) {

    cartJsonResponse(
        false,
        'This seller is not currently approved.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR ACCOUNT
|--------------------------------------------------------------------------
*/

if (
    $vendorUserStatus !==
    'active'
) {

    cartJsonResponse(
        false,
        'This seller is currently unavailable.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| STOCK
|--------------------------------------------------------------------------
*/

$stockQuantity =
    (int)
    $product['stock_quantity'];


if ($stockQuantity <= 0) {

    cartJsonResponse(
        false,
        'This product is out of stock.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| EXISTING CART
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                cart_id,
                quantity

            FROM cart

            WHERE customer_id = ?

            AND product_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $customerId,
        $productId
    ]);


    $existingCart =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    cartJsonResponse(
        false,
        'Unable to check your cart.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| NEW QUANTITY
|--------------------------------------------------------------------------
*/

if ($existingCart) {

    $newQuantity =
        (int)
        $existingCart['quantity']
        +
        $quantity;

} else {

    $newQuantity =
        $quantity;
}


/*
|--------------------------------------------------------------------------
| STOCK LIMIT
|--------------------------------------------------------------------------
*/

if (
    $newQuantity >
    $stockQuantity
) {

    cartJsonResponse(
        false,
        'Only ' .
        $stockQuantity .
        ' item(s) available in stock.',
        [],
        400
    );
}


/*
|--------------------------------------------------------------------------
| SAVE CART
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    if ($existingCart) {


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                UPDATE cart

                SET quantity = ?

                WHERE cart_id = ?

                AND customer_id = ?
            ");


        $stmt->execute([
            $newQuantity,
            (int)
            $existingCart['cart_id'],
            $customerId
        ]);


    } else {


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                INSERT INTO cart
                (
                    customer_id,
                    product_id,
                    quantity
                )

                VALUES
                (
                    ?,
                    ?,
                    ?
                )
            ");


        $stmt->execute([
            $customerId,
            $productId,
            $quantity
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CART COUNT
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                COALESCE(
                    SUM(quantity),
                    0
                )

            FROM cart

            WHERE customer_id = ?
        ");


    $stmt->execute([
        $customerId
    ]);


    $cartCount =
        (int)
        $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    cartJsonResponse(
        true,
        $product['product_name'] .
        ' has been added to your cart.',
        [
            'cart_count' =>
                $cartCount,

            'product_id' =>
                $productId,

            'quantity' =>
                $newQuantity
        ]
    );


} catch (Throwable $e) {


    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    cartJsonResponse(
        false,
        'Unable to add product to cart.',
        [],
        500
    );
}