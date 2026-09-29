<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - DELETE PRODUCT
|--------------------------------------------------------------------------
| File: seller/delete_product.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK VENDOR ROLE
|--------------------------------------------------------------------------
*/

$role = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);

if ($role !== 'vendor') {
    header('Location: ../dashboard.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET VENDOR ID
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT vendor_id
        FROM vendors
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userId
    ]);

    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    header('Location: dashboard.php?error=vendor_not_found');
    exit;
}

if (!$vendor) {
    header('Location: dashboard.php?error=vendor_not_found');
    exit;
}

$vendorId = (int) $vendor['vendor_id'];

/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    header('Location: products.php?error=invalid_product');
    exit;
}

$productId = (int) $_GET['id'];

if ($productId <= 0) {
    header('Location: products.php?error=invalid_product');
    exit;
}

/*
|--------------------------------------------------------------------------
| VERIFY PRODUCT BELONGS TO THIS VENDOR
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT
            product_id,
            product_name,
            image
        FROM products
        WHERE product_id = ?
        AND vendor_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $productId,
        $vendorId
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    header('Location: products.php?error=product_not_found');
    exit;
}

if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK WHETHER PRODUCT HAS ORDER HISTORY
|--------------------------------------------------------------------------
|
| Product yang pernah digunakan dalam order_details tidak akan
| dipadam kerana order history perlu dikekalkan.
|
| Product tersebut akan ditukar kepada Hidden.
|
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM order_details
        WHERE product_id = ?
    ");

    $stmt->execute([
        $productId
    ]);

    $hasOrders = ((int) $stmt->fetchColumn() > 0);

} catch (Throwable $e) {

    header('Location: products.php?error=delete_failed');
    exit;
}

/*
|--------------------------------------------------------------------------
| PRODUCT HAS ORDER HISTORY
|--------------------------------------------------------------------------
*/

if ($hasOrders) {

    try {

        $stmt = $db->prepare("
            UPDATE products
            SET status = 'Hidden'
            WHERE product_id = ?
            AND vendor_id = ?
        ");

        $stmt->execute([
            $productId,
            $vendorId
        ]);

        header('Location: products.php?success=product_hidden');
        exit;

    } catch (Throwable $e) {

        header('Location: products.php?error=update_failed');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| PRODUCT HAS NO ORDER HISTORY
|--------------------------------------------------------------------------
|
| Product selamat untuk dipadam.
|
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | DELETE INVENTORY
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        DELETE FROM inventory
        WHERE product_id = ?
    ");

    $stmt->execute([
        $productId
    ]);

    /*
    |--------------------------------------------------------------------------
    | DELETE CART
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        DELETE FROM cart
        WHERE product_id = ?
    ");

    $stmt->execute([
        $productId
    ]);

    /*
    |--------------------------------------------------------------------------
    | DELETE WISHLIST
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        DELETE FROM wishlist
        WHERE product_id = ?
    ");

    $stmt->execute([
        $productId
    ]);

    /*
    |--------------------------------------------------------------------------
    | DELETE REVIEWS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        DELETE FROM reviews
        WHERE product_id = ?
    ");

    $stmt->execute([
        $productId
    ]);

    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        DELETE FROM products
        WHERE product_id = ?
        AND vendor_id = ?
    ");

    $stmt->execute([
        $productId,
        $vendorId
    ]);

    /*
    |--------------------------------------------------------------------------
    | MAKE SURE PRODUCT WAS DELETED
    |--------------------------------------------------------------------------
    */

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException(
            'Product could not be deleted.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $db->commit();

} catch (Throwable $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    header('Location: products.php?error=delete_failed');
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT IMAGE
|--------------------------------------------------------------------------
|
| Delete image only AFTER database transaction is successful.
|
|--------------------------------------------------------------------------
*/

if (!empty($product['image'])) {

    $imageName = basename(
        (string) $product['image']
    );

    $imagePath =
        __DIR__
        . '/../uploads/products/'
        . $imageName;

    if (
        is_file($imagePath) &&
        file_exists($imagePath)
    ) {

        @unlink($imagePath);
    }
}

/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

header('Location: products.php?success=product_deleted');
exit;
?>