<?php
// =========================================================
// HOCHIPOHUB - GLOBAL FUNCTIONS
// File: includes/functions.php
// =========================================================

/*
|--------------------------------------------------------------------------
| NOTE
|--------------------------------------------------------------------------
| File ini menggunakan $conn yang datang daripada database/db.php.
|
| Pastikan page yang menggunakan functions.php sudah include:
|
| require_once __DIR__ . '/../database/db.php';
| require_once __DIR__ . '/functions.php';
|
|--------------------------------------------------------------------------
*/


// =========================================================
// ESCAPE OUTPUT
// =========================================================

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


// =========================================================
// REDIRECT
// =========================================================

if (!function_exists('redirect')) {

    function redirect($url)
    {
        header("Location: " . $url);
        exit;
    }
}


// =========================================================
// CHECK LOGIN
// =========================================================

if (!function_exists('isLoggedIn')) {

    function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }
}


// =========================================================
// GET CURRENT USER ID
// =========================================================

if (!function_exists('getCurrentUserId')) {

    function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}


// =========================================================
// GET CURRENT USER ROLE
// =========================================================

if (!function_exists('getCurrentUserRole')) {

    function getCurrentUserRole()
    {
        return $_SESSION['role'] ?? null;
    }
}


// =========================================================
// CHECK ROLE
// =========================================================

if (!function_exists('hasRole')) {

    function hasRole($role)
    {
        return (
            isset($_SESSION['role']) &&
            $_SESSION['role'] === $role
        );
    }
}


// =========================================================
// REQUIRE LOGIN
// =========================================================

if (!function_exists('requireLogin')) {

    function requireLogin($loginPage = 'index.php')
    {
        if (!isLoggedIn()) {
            redirect($loginPage);
        }
    }
}


// =========================================================
// REQUIRE ADMIN
// =========================================================

if (!function_exists('requireAdmin')) {

    function requireAdmin($loginPage = '../index.php')
    {
        if (
            !isset($_SESSION['user_id']) ||
            !isset($_SESSION['role']) ||
            $_SESSION['role'] !== 'admin'
        ) {
            redirect($loginPage);
        }
    }
}


// =========================================================
// REQUIRE CUSTOMER
// =========================================================

if (!function_exists('requireCustomer')) {

    function requireCustomer($loginPage = '../index.php')
    {
        if (
            !isset($_SESSION['user_id']) ||
            !isset($_SESSION['role']) ||
            $_SESSION['role'] !== 'customer'
        ) {
            redirect($loginPage);
        }
    }
}


// =========================================================
// REQUIRE VENDOR
// =========================================================

if (!function_exists('requireVendor')) {

    function requireVendor($loginPage = '../index.php')
    {
        if (
            !isset($_SESSION['user_id']) ||
            !isset($_SESSION['role']) ||
            $_SESSION['role'] !== 'vendor'
        ) {
            redirect($loginPage);
        }
    }
}


// =========================================================
// GET USER BY ID
// =========================================================

if (!function_exists('getUserById')) {

    function getUserById($conn, $userId)
    {
        $sql = "
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
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $user ?: null;
    }
}


// =========================================================
// GET USER NAME
// =========================================================

if (!function_exists('getUserName')) {

    function getUserName($conn, $userId)
    {
        $user = getUserById($conn, $userId);

        return $user['name'] ?? 'User';
    }
}


// =========================================================
// GET VENDOR BY USER ID
// =========================================================

if (!function_exists('getVendorByUserId')) {

    function getVendorByUserId($conn, $userId)
    {
        $sql = "
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
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $vendor = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $vendor ?: null;
    }
}


// =========================================================
// GET VENDOR BY ID
// =========================================================

if (!function_exists('getVendorById')) {

    function getVendorById($conn, $vendorId)
    {
        $sql = "
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
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $vendorId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $vendor = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $vendor ?: null;
    }
}


// =========================================================
// GET PRODUCT BY ID
// =========================================================

if (!function_exists('getProductById')) {

    function getProductById($conn, $productId)
    {
        $sql = "
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
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $productId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $product = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $product ?: null;
    }
}


// =========================================================
// FORMAT PRICE
// =========================================================

if (!function_exists('formatPrice')) {

    function formatPrice($price)
    {
        return 'RM ' . number_format(
            (float) $price,
            2
        );
    }
}


// =========================================================
// GET PRODUCT IMAGE
// =========================================================

if (!function_exists('getProductImage')) {

    function getProductImage($image)
    {
        if (empty($image)) {
            return 'image/logo.jpg';
        }

        return 'uploads/products/' . basename($image);
    }
}


// =========================================================
// GET VENDOR IMAGE
// =========================================================

if (!function_exists('getVendorImage')) {

    function getVendorImage($image)
    {
        if (empty($image)) {
            return 'image/logo.jpg';
        }

        return 'uploads/vendors/' . basename($image);
    }
}


// =========================================================
// PRODUCT STOCK STATUS
// =========================================================

if (!function_exists('getStockStatus')) {

    function getStockStatus($quantity)
    {
        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            return 'Out of Stock';
        }

        if ($quantity <= 5) {
            return 'Low Stock';
        }

        return 'In Stock';
    }
}


// =========================================================
// PRODUCT STATUS
// =========================================================

if (!function_exists('updateProductStatus')) {

    function updateProductStatus($conn, $productId)
    {
        $product = getProductById(
            $conn,
            $productId
        );

        if (!$product) {
            return false;
        }

        $quantity = (int) $product['stock_quantity'];

        if ($quantity <= 0) {
            $status = 'Out of Stock';
        } else {
            $status = 'Available';
        }

        $sql = "
            UPDATE products
            SET status = ?
            WHERE product_id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $status,
            $productId
        );

        $success = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        return $success;
    }
}


// =========================================================
// GET CART COUNT
// =========================================================

if (!function_exists('getCartCount')) {

    function getCartCount($conn, $userId)
    {
        $sql = "
            SELECT COALESCE(
                SUM(quantity),
                0
            ) AS total
            FROM cart
            WHERE customer_id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $row = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0);
    }
}


// =========================================================
// GET WISHLIST COUNT
// =========================================================

if (!function_exists('getWishlistCount')) {

    function getWishlistCount($conn, $userId)
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM wishlist
            WHERE user_id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $row = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0);
    }
}


// =========================================================
// CHECK WISHLIST
// =========================================================

if (!function_exists('isInWishlist')) {

    function isInWishlist($conn, $userId, $productId)
    {
        $sql = "
            SELECT wishlist_id
            FROM wishlist
            WHERE user_id = ?
            AND product_id = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $userId,
            $productId
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_store_result($stmt);

        $exists = mysqli_stmt_num_rows($stmt) > 0;

        mysqli_stmt_close($stmt);

        return $exists;
    }
}


// =========================================================
// CHECK CART
// =========================================================

if (!function_exists('isInCart')) {

    function isInCart($conn, $userId, $productId)
    {
        $sql = "
            SELECT cart_id
            FROM cart
            WHERE customer_id = ?
            AND product_id = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $userId,
            $productId
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_store_result($stmt);

        $exists = mysqli_stmt_num_rows($stmt) > 0;

        mysqli_stmt_close($stmt);

        return $exists;
    }
}


// =========================================================
// GET ORDER BY ID
// =========================================================

if (!function_exists('getOrderById')) {

    function getOrderById($conn, $orderId)
    {
        $sql = "
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
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $orderId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $order = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $order ?: null;
    }
}


// =========================================================
// GET ORDER DETAILS
// =========================================================

if (!function_exists('getOrderDetails')) {

    function getOrderDetails($conn, $orderId)
    {
        $sql = "
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

            ORDER BY od.order_detail_id ASC
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $orderId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $details = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $details[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $details;
    }
}


// =========================================================
// GET VENDOR ORDERS
// =========================================================

if (!function_exists('getVendorOrders')) {

    function getVendorOrders($conn, $vendorId)
    {
        $sql = "
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

            ORDER BY vo.created_at DESC
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $vendorId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $orders = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $orders;
    }
}


// =========================================================
// GET PAYMENT BY ORDER
// =========================================================

if (!function_exists('getPaymentByOrder')) {

    function getPaymentByOrder($conn, $orderId)
    {
        $sql = "
            SELECT *
            FROM payments
            WHERE order_id = ?
            ORDER BY payment_id DESC
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $orderId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $payment = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $payment ?: null;
    }
}


// =========================================================
// STATUS BADGE CLASS
// =========================================================

if (!function_exists('statusClass')) {

    function statusClass($status)
    {
        $status = strtolower(
            trim((string) $status)
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


// =========================================================
// GET COMMISSION AMOUNT
// =========================================================

if (!function_exists('calculateCommission')) {

    function calculateCommission($subtotal, $rate)
    {
        return (
            (float) $subtotal *
            ((float) $rate / 100)
        );
    }
}


// =========================================================
// ADMIN LOG
// =========================================================

if (!function_exists('addAdminLog')) {

    function addAdminLog(
        $conn,
        $adminId,
        $action,
        $targetType = null,
        $targetId = null
    ) {

        $sql = "
            INSERT INTO admin_logs
            (
                admin_id,
                action,
                target_type,
                target_id
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "issi",
            $adminId,
            $action,
            $targetType,
            $targetId
        );

        $success = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        return $success;
    }
}


// =========================================================
// CSRF TOKEN
// =========================================================

if (!function_exists('generateCsrfToken')) {

    function generateCsrfToken()
    {
        if (
            !isset($_SESSION['csrf_token']) ||
            empty($_SESSION['csrf_token'])
        ) {

            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}


// =========================================================
// VERIFY CSRF TOKEN
// =========================================================

if (!function_exists('verifyCsrfToken')) {

    function verifyCsrfToken($token)
    {
        return (
            isset($_SESSION['csrf_token']) &&
            hash_equals(
                $_SESSION['csrf_token'],
                (string) $token
            )
        );
    }
}


// =========================================================
// FLASH MESSAGE
// =========================================================

if (!function_exists('setFlashMessage')) {

    function setFlashMessage($type, $message)
    {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}


// =========================================================
// GET FLASH MESSAGE
// =========================================================

if (!function_exists('getFlashMessage')) {

    function getFlashMessage()
    {
        if (
            !isset($_SESSION['flash_message'])
        ) {
            return null;
        }

        $message = $_SESSION['flash_message'];

        unset($_SESSION['flash_message']);

        return $message;
    }
}


// =========================================================
// SAFE REQUEST VALUE
// =========================================================

if (!function_exists('post')) {

    function post($key, $default = '')
    {
        return isset($_POST[$key])
            ? trim($_POST[$key])
            : $default;
    }
}


// =========================================================
// GET REQUEST VALUE
// =========================================================

if (!function_exists('get')) {

    function get($key, $default = '')
    {
        return isset($_GET[$key])
            ? trim($_GET[$key])
            : $default;
    }
}


// =========================================================
// VALIDATE EMAIL
// =========================================================

if (!function_exists('isValidEmail')) {

    function isValidEmail($email)
    {
        return filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }
}


// =========================================================
// GENERATE RANDOM CODE
// =========================================================

if (!function_exists('generateCode')) {

    function generateCode($length = 6)
    {
        $characters = '0123456789';

        $code = '';

        for ($i = 0; $i < $length; $i++) {

            $code .= $characters[
                random_int(
                    0,
                    strlen($characters) - 1
                )
            ];

        }

        return $code;
    }
}


// =========================================================
// GET PAGINATION OFFSET
// =========================================================

if (!function_exists('getPaginationOffset')) {

    function getPaginationOffset(
        $page,
        $perPage
    ) {

        $page = max(
            1,
            (int) $page
        );

        $perPage = max(
            1,
            (int) $perPage
        );

        return (
            ($page - 1) *
            $perPage
        );
    }
}


// =========================================================
// TOTAL PAGES
// =========================================================

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
?>