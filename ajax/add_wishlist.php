<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - WISHLIST AJAX
|--------------------------------------------------------------------------
| File:
| ajax/add_wishlist.php
|
| Functions:
| - Add product to wishlist
| - Remove product from wishlist
| - Check wishlist status
| - Get wishlist count
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
// DATABASE CHECK
// ==========================================================================

if (!isset($conn) || !($conn instanceof mysqli)) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);

    exit;
}


// ==========================================================================
// JSON RESPONSE FUNCTION
// ==========================================================================

function wishlistResponse(
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
// GET USER ID
// ==========================================================================

$userId = 0;

if (isset($_SESSION['user_id'])) {

    $userId = (int) $_SESSION['user_id'];

}


// ==========================================================================
// LOGIN CHECK
// ==========================================================================

if ($userId <= 0) {

    wishlistResponse(
        false,
        'Please login first to use wishlist.',
        [
            'login_required' => true,
            'wishlisted' => false
        ],
        401
    );

}


// ==========================================================================
// CUSTOMER ONLY
// ==========================================================================

$role = $_SESSION['role'] ?? 'customer';

if ($role !== 'customer') {

    wishlistResponse(
        false,
        'Only customers can use wishlist.',
        [
            'wishlisted' => false
        ],
        403
    );

}


// ==========================================================================
// GET ACTION
// ==========================================================================

$action = '';

if (isset($_POST['action'])) {

    $action = trim(
        (string) $_POST['action']
    );

} elseif (isset($_GET['action'])) {

    $action = trim(
        (string) $_GET['action']
    );

}

$action = strtolower($action);


// ==========================================================================
// GET PRODUCT ID
// ==========================================================================

$productId = 0;

if (isset($_POST['product_id'])) {

    $productId = (int) $_POST['product_id'];

} elseif (isset($_GET['product_id'])) {

    $productId = (int) $_GET['product_id'];

}


// ==========================================================================
// GET WISHLIST COUNT
// ==========================================================================

if ($action === 'count') {

    $sql = "
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        wishlistResponse(
            false,
            'Unable to get wishlist count.',
            [],
            500
        );

    }

    $stmt->bind_param(
        'i',
        $userId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $count = (int) (
        $row['total'] ?? 0
    );

    $stmt->close();

    wishlistResponse(
        true,
        '',
        [
            'count' => $count
        ]
    );

}


// ==========================================================================
// CHECK / STATUS
// ==========================================================================

if (
    $action === 'check' ||
    $action === 'status'
) {

    if ($productId <= 0) {

        wishlistResponse(
            false,
            'Invalid product ID.',
            [],
            400
        );

    }

    $sql = "
        SELECT wishlist_id
        FROM wishlist
        WHERE user_id = ?
        AND product_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        wishlistResponse(
            false,
            'Unable to check wishlist status.',
            [],
            500
        );

    }

    $stmt->bind_param(
        'ii',
        $userId,
        $productId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $exists = (
        $result->num_rows > 0
    );

    $stmt->close();

    wishlistResponse(
        true,
        '',
        [
            'wishlisted' => $exists
        ]
    );

}


// ==========================================================================
// VALIDATE PRODUCT ID
// ==========================================================================

if (
    $action === 'add' ||
    $action === 'remove' ||
    $action === 'toggle'
) {

    if ($productId <= 0) {

        wishlistResponse(
            false,
            'Invalid product ID.',
            [],
            400
        );

    }

}


// ==========================================================================
// CHECK PRODUCT EXISTS
// ==========================================================================

if (
    $action === 'add' ||
    $action === 'remove' ||
    $action === 'toggle'
) {

    $sql = "
        SELECT
            product_id,
            status
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        wishlistResponse(
            false,
            'Unable to verify product.',
            [],
            500
        );

    }

    $stmt->bind_param(
        'i',
        $productId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $product = $result->fetch_assoc();

    $stmt->close();

    if (!$product) {

        wishlistResponse(
            false,
            'Product not found.',
            [],
            404
        );

    }

}


// ==========================================================================
// CHECK CURRENT WISHLIST STATUS
// ==========================================================================

$currentWishlisted = false;

if (
    $action === 'add' ||
    $action === 'remove' ||
    $action === 'toggle'
) {

    $checkSql = "
        SELECT wishlist_id
        FROM wishlist
        WHERE user_id = ?
        AND product_id = ?
        LIMIT 1
    ";

    $checkStmt =
        $conn->prepare($checkSql);

    if (!$checkStmt) {

        wishlistResponse(
            false,
            'Unable to check wishlist.',
            [],
            500
        );

    }

    $checkStmt->bind_param(
        'ii',
        $userId,
        $productId
    );

    $checkStmt->execute();

    $checkResult =
        $checkStmt->get_result();

    $currentWishlisted =
        ($checkResult->num_rows > 0);

    $checkStmt->close();

}


// ==========================================================================
// TOGGLE
// ==========================================================================

if ($action === 'toggle') {

    if ($currentWishlisted) {

        $action = 'remove';

    } else {

        $action = 'add';

    }

}


// ==========================================================================
// ADD WISHLIST
// ==========================================================================

if ($action === 'add') {

    // ----------------------------------------------------------------------
    // Already exists
    // ----------------------------------------------------------------------

    if ($currentWishlisted) {

        $countSql = "
            SELECT COUNT(*) AS total
            FROM wishlist
            WHERE user_id = ?
        ";

        $countStmt =
            $conn->prepare($countSql);

        $count = 0;

        if ($countStmt) {

            $countStmt->bind_param(
                'i',
                $userId
            );

            $countStmt->execute();

            $countResult =
                $countStmt->get_result();

            $countRow =
                $countResult->fetch_assoc();

            $count =
                (int) ($countRow['total'] ?? 0);

            $countStmt->close();

        }

        wishlistResponse(
            true,
            'Product is already in your wishlist.',
            [
                'wishlisted' => true,
                'count' => $count,
                'already_exists' => true
            ]
        );

    }


    // ----------------------------------------------------------------------
    // INSERT
    // ----------------------------------------------------------------------

    $sql = "
        INSERT INTO wishlist (
            user_id,
            product_id
        )
        VALUES (?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        wishlistResponse(
            false,
            'Unable to add product to wishlist.',
            [],
            500
        );

    }

    $stmt->bind_param(
        'ii',
        $userId,
        $productId
    );

    if (!$stmt->execute()) {

        $stmt->close();

        wishlistResponse(
            false,
            'Failed to add product to wishlist.',
            [],
            500
        );

    }

    $stmt->close();


    // ----------------------------------------------------------------------
    // GET COUNT
    // ----------------------------------------------------------------------

    $countSql = "
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = ?
    ";

    $countStmt =
        $conn->prepare($countSql);

    $count = null;

    if ($countStmt) {

        $countStmt->bind_param(
            'i',
            $userId
        );

        $countStmt->execute();

        $countResult =
            $countStmt->get_result();

        $countRow =
            $countResult->fetch_assoc();

        $count =
            (int) ($countRow['total'] ?? 0);

        $countStmt->close();

    }


    wishlistResponse(
        true,
        'Product added to wishlist.',
        [
            'wishlisted' => true,
            'count' => $count
        ]
    );

}


// ==========================================================================
// REMOVE WISHLIST
// ==========================================================================

if ($action === 'remove') {

    $sql = "
        DELETE FROM wishlist
        WHERE user_id = ?
        AND product_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        wishlistResponse(
            false,
            'Unable to remove product from wishlist.',
            [],
            500
        );

    }

    $stmt->bind_param(
        'ii',
        $userId,
        $productId
    );

    if (!$stmt->execute()) {

        $stmt->close();

        wishlistResponse(
            false,
            'Failed to remove product from wishlist.',
            [],
            500
        );

    }

    $stmt->close();


    // ----------------------------------------------------------------------
    // GET UPDATED COUNT
    // ----------------------------------------------------------------------

    $countSql = "
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = ?
    ";

    $countStmt =
        $conn->prepare($countSql);

    $count = null;

    if ($countStmt) {

        $countStmt->bind_param(
            'i',
            $userId
        );

        $countStmt->execute();

        $countResult =
            $countStmt->get_result();

        $countRow =
            $countResult->fetch_assoc();

        $count =
            (int) ($countRow['total'] ?? 0);

        $countStmt->close();

    }


    wishlistResponse(
        true,
        'Product removed from wishlist.',
        [
            'wishlisted' => false,
            'count' => $count
        ]
    );

}


// ==========================================================================
// INVALID ACTION
// ==========================================================================

wishlistResponse(
    false,
    'Invalid wishlist action.',
    [
        'allowed_actions' => [
            'add',
            'remove',
            'toggle',
            'check',
            'status',
            'count'
        ]
    ],
    400
);