<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
| File:
| includes/functions.php
|
| Database:
| PDO
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ESCAPE OUTPUT
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('redirect')) {

    function redirect($url)
    {
        header(
            'Location: ' . $url
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

if (!function_exists('getCurrentUserId')) {

    function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}


if (!function_exists('getCurrentUserRole')) {

    function getCurrentUserRole()
    {
        return $_SESSION['role'] ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| GET USER BY ID
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserById')) {

    function getUserById(
        PDO $db,
        $userId
    ) {

        $stmt = $db->prepare("
            SELECT
                user_id,
                name,
                email,
                phone,
                profile_image,
                role,
                status,
                mfa_enabled,
                created_at,
                updated_at

            FROM users

            WHERE user_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $userId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET USER NAME
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserNameFromDB')) {

    function getUserNameFromDB(
        PDO $db,
        $userId
    ) {

        $user =
            getUserById(
                $db,
                $userId
            );

        return $user['name']
            ?? 'User';
    }
}


/*
|--------------------------------------------------------------------------
| GET VENDOR BY USER ID
|--------------------------------------------------------------------------
*/

if (!function_exists('getVendorByUserId')) {

    function getVendorByUserId(
        PDO $db,
        $userId
    ) {

        $stmt = $db->prepare("
            SELECT
                vendor_id,
                user_id,
                business_name,
                business_logo,
                business_description,
                business_address,
                category,
                delivery_method,
                approval_status,
                created_at,
                updated_at

            FROM vendors

            WHERE user_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $userId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET VENDOR BY ID
|--------------------------------------------------------------------------
*/

if (!function_exists('getVendorById')) {

    function getVendorById(
        PDO $db,
        $vendorId
    ) {

        $stmt = $db->prepare("
            SELECT
                v.*,

                u.name,
                u.email,
                u.phone,
                u.status AS user_status

            FROM vendors v

            INNER JOIN users u
                ON v.user_id = u.user_id

            WHERE v.vendor_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $vendorId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT BY ID
|--------------------------------------------------------------------------
*/

if (!function_exists('getProductById')) {

    function getProductById(
        PDO $db,
        $productId
    ) {

        $stmt = $db->prepare("
            SELECT

                p.*,

                v.business_name,
                v.business_logo,

                c.category_name

            FROM products p

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            INNER JOIN categories c
                ON p.category_id = c.category_id

            WHERE p.product_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $productId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| FORMAT PRICE
|--------------------------------------------------------------------------
*/

if (!function_exists('formatPrice')) {

    function formatPrice($price)
    {
        return 'RM ' .
            number_format(
                (float) $price,
                2
            );
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('getProductImage')) {

    function getProductImage($image)
    {
        if (empty($image)) {

            return BASE_URL .
                'image/logo.jpg';
        }

        return BASE_URL .
            'uploads/products/' .
            basename($image);
    }
}


/*
|--------------------------------------------------------------------------
| VENDOR IMAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('getVendorImage')) {

    function getVendorImage($image)
    {
        if (empty($image)) {

            return BASE_URL .
                'image/logo.jpg';
        }

        return BASE_URL .
            'uploads/vendors/' .
            basename($image);
    }
}


/*
|--------------------------------------------------------------------------
| STOCK STATUS
|--------------------------------------------------------------------------
*/

if (!function_exists('getStockStatus')) {

    function getStockStatus($quantity)
    {
        $quantity =
            (int) $quantity;

        if ($quantity <= 0) {

            return 'Out of Stock';
        }

        if ($quantity <= 5) {

            return 'Low Stock';
        }

        return 'In Stock';
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STATUS
|--------------------------------------------------------------------------
*/

if (!function_exists('updateProductStatus')) {

    function updateProductStatus(
        PDO $db,
        $productId
    ) {

        $product =
            getProductById(
                $db,
                $productId
            );

        if (!$product) {
            return false;
        }

        $quantity =
            (int) $product['stock_quantity'];

        $status =
            $quantity <= 0
                ? 'Out of Stock'
                : 'Available';

        $stmt = $db->prepare("
            UPDATE products

            SET status = ?

            WHERE product_id = ?
        ");

        return $stmt->execute([
            $status,
            (int) $productId
        ]);
    }
}


/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/

if (!function_exists('getCartCount')) {

    function getCartCount(
        PDO $db,
        $userId
    ) {

        $stmt = $db->prepare("
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                ) AS total

            FROM cart

            WHERE customer_id = ?
        ");

        $stmt->execute([
            (int) $userId
        ]);

        $row =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return (int) (
            $row['total'] ?? 0
        );
    }
}


/*
|--------------------------------------------------------------------------
| WISHLIST COUNT
|--------------------------------------------------------------------------
*/

if (!function_exists('getWishlistCount')) {

    function getWishlistCount(
        PDO $db,
        $userId
    ) {

        $stmt = $db->prepare("
            SELECT
                COUNT(*) AS total

            FROM wishlist

            WHERE user_id = ?
        ");

        $stmt->execute([
            (int) $userId
        ]);

        $row =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return (int) (
            $row['total'] ?? 0
        );
    }
}


/*
|--------------------------------------------------------------------------
| CHECK WISHLIST
|--------------------------------------------------------------------------
*/

if (!function_exists('isInWishlist')) {

    function isInWishlist(
        PDO $db,
        $userId,
        $productId
    ) {

        $stmt = $db->prepare("
            SELECT wishlist_id

            FROM wishlist

            WHERE user_id = ?
            AND product_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $userId,
            (int) $productId
        ]);

        return (bool)
            $stmt->fetchColumn();
    }
}


/*
|--------------------------------------------------------------------------
| CHECK CART
|--------------------------------------------------------------------------
*/

if (!function_exists('isInCart')) {

    function isInCart(
        PDO $db,
        $userId,
        $productId
    ) {

        $stmt = $db->prepare("
            SELECT cart_id

            FROM cart

            WHERE customer_id = ?
            AND product_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $userId,
            (int) $productId
        ]);

        return (bool)
            $stmt->fetchColumn();
    }
}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
*/

if (!function_exists('getOrderById')) {

    function getOrderById(
        PDO $db,
        $orderId
    ) {

        $stmt = $db->prepare("
            SELECT

                o.*,

                u.name AS customer_name,
                u.email AS customer_email,
                u.phone AS customer_phone

            FROM orders o

            INNER JOIN users u
                ON o.customer_id = u.user_id

            WHERE o.order_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            (int) $orderId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| GET ORDER DETAILS
|--------------------------------------------------------------------------
*/

if (!function_exists('getOrderDetails')) {

    function getOrderDetails(
        PDO $db,
        $orderId
    ) {

        $stmt = $db->prepare("
            SELECT

                od.*,

                p.product_name,
                p.image,
                p.vendor_id,

                v.business_name

            FROM order_details od

            INNER JOIN products p
                ON od.product_id = p.product_id

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            WHERE od.order_id = ?

            ORDER BY
                od.order_detail_id ASC
        ");

        $stmt->execute([
            (int) $orderId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/*
|--------------------------------------------------------------------------
| GET VENDOR ORDERS
|--------------------------------------------------------------------------
*/

if (!function_exists('getVendorOrders')) {

    function getVendorOrders(
        PDO $db,
        $vendorId
    ) {

        $stmt = $db->prepare("
            SELECT

                vo.*,

                o.customer_id,
                o.order_date,
                o.order_status,

                u.name AS customer_name

            FROM vendor_orders vo

            INNER JOIN orders o
                ON vo.order_id = o.order_id

            INNER JOIN users u
                ON o.customer_id = u.user_id

            WHERE vo.vendor_id = ?

            ORDER BY
                vo.created_at DESC
        ");

        $stmt->execute([
            (int) $vendorId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/*
|--------------------------------------------------------------------------
| PAYMENT BY ORDER
|--------------------------------------------------------------------------
*/

if (!function_exists('getPaymentByOrder')) {

    function getPaymentByOrder(
        PDO $db,
        $orderId
    ) {

        $stmt = $db->prepare("
            SELECT *

            FROM payments

            WHERE order_id = ?

            ORDER BY payment_id DESC

            LIMIT 1
        ");

        $stmt->execute([
            (int) $orderId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }
}


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

if (!function_exists('statusClass')) {

    function statusClass($status)
    {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );

        return match ($status) {

            'active',
            'approved',
            'available',
            'paid',
            'completed',
            'visible',
            'ready',
            'shipped',
            'in stock'
                => 'success',

            'pending',
            'processing',
            'low stock'
                => 'warning',

            'rejected',
            'cancelled',
            'failed',
            'hidden',
            'inactive',
            'suspended',
            'out of stock'
                => 'danger',

            'refunded'
                => 'info',

            default
                => 'default'
        };
    }
}


/*
|--------------------------------------------------------------------------
| CALCULATE COMMISSION
|--------------------------------------------------------------------------
*/

if (!function_exists('calculateCommission')) {

    function calculateCommission(
        $subtotal,
        $rate
    ) {

        return (
            (float) $subtotal *
            ((float) $rate / 100)
        );
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN LOG
|--------------------------------------------------------------------------
*/

if (!function_exists('addAdminLog')) {

    function addAdminLog(
        PDO $db,
        $adminId,
        $action,
        $targetType = null,
        $targetId = null
    ) {

        $stmt = $db->prepare("
            INSERT INTO admin_logs
            (
                admin_id,
                action,
                target_type,
                target_id
            )

            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            (int) $adminId,
            $action,
            $targetType,
            $targetId !== null
                ? (int) $targetId
                : null
        ]);
    }
}


/*
|--------------------------------------------------------------------------
| POST HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('post')) {

    function post(
        $key,
        $default = ''
    ) {

        return isset($_POST[$key])
            ? trim($_POST[$key])
            : $default;
    }
}


/*
|--------------------------------------------------------------------------
| GET HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('get')) {

    function get(
        $key,
        $default = ''
    ) {

        return isset($_GET[$key])
            ? trim($_GET[$key])
            : $default;
    }
}


/*
|--------------------------------------------------------------------------
| EMAIL VALIDATION
|--------------------------------------------------------------------------
*/

if (!function_exists('isValidEmail')) {

    function isValidEmail($email)
    {
        return filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }
}


/*
|--------------------------------------------------------------------------
| GENERATE CODE
|--------------------------------------------------------------------------
*/

if (!function_exists('generateCode')) {

    function generateCode(
        $length = 6
    ) {

        $characters =
            '0123456789';

        $code = '';

        for (
            $i = 0;
            $i < $length;
            $i++
        ) {

            $code .=
                $characters[
                    random_int(
                        0,
                        strlen($characters) - 1
                    )
                ];
        }

        return $code;
    }
}


/*
|--------------------------------------------------------------------------
| PAGINATION OFFSET
|--------------------------------------------------------------------------
*/

if (!function_exists('getPaginationOffset')) {

    function getPaginationOffset(
        $page,
        $perPage
    ) {

        $page =
            max(
                1,
                (int) $page
            );

        $perPage =
            max(
                1,
                (int) $perPage
            );

        return (
            ($page - 1) *
            $perPage
        );
    }
}


/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

if (!function_exists('getTotalPages')) {

    function getTotalPages(
        $totalRows,
        $perPage
    ) {

        if ($perPage <= 0) {
            return 1;
        }

        return max(
            1,
            (int) ceil(
                $totalRows / $perPage
            )
        );
    }
}