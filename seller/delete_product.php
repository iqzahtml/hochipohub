<?php
require_once '../database/db.php';
require_once '../includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK VENDOR ROLE
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET VENDOR ID
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT vendor_id
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$vendor = $result->fetch_assoc();

$stmt->close();

if (!$vendor) {
    header("Location: dashboard.php?error=vendor_not_found");
    exit;
}

$vendor_id = (int) $vendor['vendor_id'];

/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php?error=invalid_product");
    exit;
}

$product_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| VERIFY PRODUCT BELONGS TO THIS VENDOR
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT product_id, product_name, image
    FROM products
    WHERE product_id = ?
    AND vendor_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $product_id, $vendor_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    header("Location: products.php?error=product_not_found");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK WHETHER PRODUCT HAS ORDER HISTORY
|--------------------------------------------------------------------------
|
| Product yang pernah digunakan dalam order_details jangan terus delete
| sebab ia akan menyebabkan historical order data hilang / FK conflict.
|
| Jadi kita tukar status kepada Hidden jika sudah pernah dipesan.
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM order_details
    WHERE product_id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$order_data = $result->fetch_assoc();

$stmt->close();

$has_orders = ((int) $order_data['total'] > 0);

/*
|--------------------------------------------------------------------------
| DELETE / HIDE PRODUCT
|--------------------------------------------------------------------------
*/
if ($has_orders) {

    /*
    |--------------------------------------------------------------------------
    | PRODUCT PERNAH ADA DALAM ORDER
    |--------------------------------------------------------------------------
    | Jangan delete.
    | Tukar kepada Hidden.
    |--------------------------------------------------------------------------
    */
    $stmt = $conn->prepare("
        UPDATE products
        SET status = 'Hidden'
        WHERE product_id = ?
        AND vendor_id = ?
    ");

    $stmt->bind_param("ii", $product_id, $vendor_id);

    if ($stmt->execute()) {
        $stmt->close();

        header("Location: products.php?success=product_hidden");
        exit;
    }

    $stmt->close();

    header("Location: products.php?error=update_failed");
    exit;

} else {

    /*
    |--------------------------------------------------------------------------
    | PRODUCT BELUM PERNAH DIBELI
    |--------------------------------------------------------------------------
    | Safe untuk delete.
    |--------------------------------------------------------------------------
    */
    $conn->begin_transaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | DELETE INVENTORY
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("
            DELETE FROM inventory
            WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | DELETE CART
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("
            DELETE FROM cart
            WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | DELETE WISHLIST
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("
            DELETE FROM wishlist
            WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | DELETE REVIEWS
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("
            DELETE FROM reviews
            WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | DELETE PRODUCT
        |--------------------------------------------------------------------------
        */
        $stmt = $conn->prepare("
            DELETE FROM products
            WHERE product_id = ?
            AND vendor_id = ?
        ");

        $stmt->bind_param("ii", $product_id, $vendor_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete product.");
        }

        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */
        $conn->commit();

        /*
        |--------------------------------------------------------------------------
        | DELETE PRODUCT IMAGE
        |--------------------------------------------------------------------------
        */
        if (!empty($product['image'])) {

            $image_path = '../uploads/products/' . $product['image'];

            if (file_exists($image_path) && is_file($image_path)) {
                unlink($image_path);
            }
        }

        header("Location: products.php?success=product_deleted");
        exit;

    } catch (Exception $e) {

        /*
        |--------------------------------------------------------------------------
        | ROLLBACK
        |--------------------------------------------------------------------------
        */
        $conn->rollback();

        header("Location: products.php?error=delete_failed");
        exit;
    }
}
?>