<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Global Functions
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| DATABASE HELPER
|--------------------------------------------------------------------------
*/

function getDatabase()
{
    static $database = null;

    if ($database === null) {

        require dirname(__DIR__) . '/database/db.php';

        $database = $pdo;
    }

    return $database;
}


/*
|--------------------------------------------------------------------------
| ESCAPE OUTPUT
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

function redirect(string $url): void
{
    header(
        'Location: ' . $url
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

function setFlashMessage(
    string $type,
    string $message
): void {

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


function getFlashMessage(): ?array
{
    if (
        !isset($_SESSION['flash'])
        ||
        !is_array($_SESSION['flash'])
    ) {

        return null;
    }

    $message =
        $_SESSION['flash'];

    unset(
        $_SESSION['flash']
    );

    return $message;
}


/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

function getUserById(
    PDO $pdo,
    int $userId
): ?array {

    $stmt = $pdo->prepare("
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
        $userId
    ]);

    $user =
        $stmt->fetch();

    return $user ?: null;
}


function getUserByEmail(
    PDO $pdo,
    string $email
): ?array {

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $user =
        $stmt->fetch();

    return $user ?: null;
}


function currentUser(
    PDO $pdo
): ?array {

    if (!isLoggedIn()) {
        return null;
    }

    return getUserById(
        $pdo,
        currentUserId()
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR
|--------------------------------------------------------------------------
*/

function getVendorByUserId(
    PDO $pdo,
    int $userId
): ?array {

    $stmt = $pdo->prepare("
        SELECT
            v.*,
            u.name,
            u.email,
            u.phone,
            u.profile_image
        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.user_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $userId
    ]);

    $vendor =
        $stmt->fetch();

    return $vendor ?: null;
}


function getVendorById(
    PDO $pdo,
    int $vendorId
): ?array {

    $stmt = $pdo->prepare("
        SELECT
            v.*,
            u.name,
            u.email,
            u.phone
        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.vendor_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $vendorId
    ]);

    $vendor =
        $stmt->fetch();

    return $vendor ?: null;
}


/*
|--------------------------------------------------------------------------
| CATEGORY
|--------------------------------------------------------------------------
*/

function getCategories(
    PDO $pdo
): array {

    $stmt = $pdo->query("
        SELECT
            category_id,
            category_name,
            category_image,
            created_at

        FROM categories

        ORDER BY category_name ASC
    ");

    return $stmt->fetchAll();
}


function getCategoryById(
    PDO $pdo,
    int $categoryId
): ?array {

    $stmt = $pdo->prepare("
        SELECT *
        FROM categories
        WHERE category_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $categoryId
    ]);

    $category =
        $stmt->fetch();

    return $category ?: null;
}


/*
|--------------------------------------------------------------------------
| PRODUCT
|--------------------------------------------------------------------------
*/

function getProductById(
    PDO $pdo,
    int $productId
): ?array {

    $stmt = $pdo->prepare("
        SELECT
            p.*,

            v.vendor_id,
            v.business_name,
            v.business_logo,
            v.business_description,

            c.category_id,
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
        $productId
    ]);

    $product =
        $stmt->fetch();

    return $product ?: null;
}


function getAvailableProducts(
    PDO $pdo,
    int $limit = 12
): array {

    $limit =
        max(1, min($limit, 100));

    $stmt = $pdo->query("
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

        WHERE p.status = 'Available'

        AND v.approval_status = 'Approved'

        AND p.stock_quantity > 0

        ORDER BY p.created_at DESC

        LIMIT {$limit}
    ");

    return $stmt->fetchAll();
}


function getProductsByCategory(
    PDO $pdo,
    int $categoryId
): array {

    $stmt = $pdo->prepare("
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

        WHERE p.category_id = ?

        AND p.status = 'Available'

        AND v.approval_status = 'Approved'

        ORDER BY p.created_at DESC
    ");

    $stmt->execute([
        $categoryId
    ]);

    return $stmt->fetchAll();
}


function searchProducts(
    PDO $pdo,
    string $keyword
): array {

    $keyword =
        trim($keyword);

    if ($keyword === '') {
        return [];
    }

    $search =
        '%' . $keyword . '%';

    $stmt = $pdo->prepare("
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

        WHERE
        (
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR v.business_name LIKE ?
            OR c.category_name LIKE ?
        )

        AND p.status = 'Available'

        AND v.approval_status = 'Approved'

        ORDER BY p.created_at DESC
    ");

    $stmt->execute([
        $search,
        $search,
        $search,
        $search
    ]);

    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

function getCartCount(
    PDO $pdo,
    int $customerId
): int {

    $stmt = $pdo->prepare("
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

    return (int) $stmt->fetchColumn();
}


function getCartItems(
    PDO $pdo,
    int $customerId
): array {

    $stmt = $pdo->prepare("
        SELECT
            c.cart_id,
            c.customer_id,
            c.product_id,
            c.quantity,

            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,

            v.vendor_id,
            v.business_name,

            cat.category_name

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories cat
            ON p.category_id = cat.category_id

        WHERE c.customer_id = ?

        ORDER BY c.created_at DESC
    ");

    $stmt->execute([
        $customerId
    ]);

    return $stmt->fetchAll();
}


function getCartTotal(
    PDO $pdo,
    int $customerId
): float {

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(
                SUM(
                    c.quantity * p.price
                ),
                0
            )

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        WHERE c.customer_id = ?
    ");

    $stmt->execute([
        $customerId
    ]);

    return (float) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| WISHLIST
|--------------------------------------------------------------------------
*/

function getWishlistCount(
    PDO $pdo,
    int $userId
): int {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    return (int) $stmt->fetchColumn();
}


function getWishlistItems(
    PDO $pdo,
    int $userId
): array {

    $stmt = $pdo->prepare("
        SELECT
            w.wishlist_id,
            w.product_id,
            w.created_at,

            p.product_name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,

            v.business_name,

            c.category_name

        FROM wishlist w

        INNER JOIN products p
            ON w.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE w.user_id = ?

        ORDER BY w.created_at DESC
    ");

    $stmt->execute([
        $userId
    ]);

    return $stmt->fetchAll();
}


function isInWishlist(
    PDO $pdo,
    int $userId,
    int $productId
): bool {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM wishlist

        WHERE user_id = ?
        AND product_id = ?
    ");

    $stmt->execute([
        $userId,
        $productId
    ]);

    return (int) $stmt->fetchColumn() > 0;
}


/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

function getProductReviews(
    PDO $pdo,
    int $productId
): array {

    $stmt = $pdo->prepare("
        SELECT
            r.*,

            u.name,
            u.profile_image

        FROM reviews r

        INNER JOIN users u
            ON r.customer_id = u.user_id

        WHERE r.product_id = ?

        AND r.status = 'Visible'

        ORDER BY r.review_date DESC
    ");

    $stmt->execute([
        $productId
    ]);

    return $stmt->fetchAll();
}


function getProductRating(
    PDO $pdo,
    int $productId
): array {

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(
                AVG(rating),
                0
            ) AS average_rating,

            COUNT(*) AS total_reviews

        FROM reviews

        WHERE product_id = ?

        AND status = 'Visible'
    ");

    $stmt->execute([
        $productId
    ]);

    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

function getCustomerOrders(
    PDO $pdo,
    int $customerId
): array {

    $stmt = $pdo->prepare("
        SELECT
            o.*,

            p.payment_status,
            p.payment_method

        FROM orders o

        LEFT JOIN payments p
            ON o.order_id = p.order_id

        WHERE o.customer_id = ?

        ORDER BY o.order_date DESC
    ");

    $stmt->execute([
        $customerId
    ]);

    return $stmt->fetchAll();
}


function getOrderById(
    PDO $pdo,
    int $orderId
): ?array {

    $stmt = $pdo->prepare("
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
        $orderId
    ]);

    $order =
        $stmt->fetch();

    return $order ?: null;
}


function getOrderDetails(
    PDO $pdo,
    int $orderId
): array {

    $stmt = $pdo->prepare("
        SELECT
            od.*,

            p.product_name,
            p.image,

            v.vendor_id,
            v.business_name

        FROM order_details od

        INNER JOIN products p
            ON od.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        WHERE od.order_id = ?

        ORDER BY od.order_detail_id ASC
    ");

    $stmt->execute([
        $orderId
    ]);

    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| VENDOR ORDERS
|--------------------------------------------------------------------------
*/

function getVendorOrders(
    PDO $pdo,
    int $vendorId
): array {

    $stmt = $pdo->prepare("
        SELECT
            vo.*,

            o.customer_id,
            o.order_date,
            o.order_status,
            o.delivery_method,

            u.name AS customer_name,
            u.email AS customer_email,
            u.phone AS customer_phone

        FROM vendor_orders vo

        INNER JOIN orders o
            ON vo.order_id = o.order_id

        INNER JOIN users u
            ON o.customer_id = u.user_id

        WHERE vo.vendor_id = ?

        ORDER BY vo.created_at DESC
    ");

    $stmt->execute([
        $vendorId
    ]);

    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| INVENTORY
|--------------------------------------------------------------------------
*/

function getInventoryByProduct(
    PDO $pdo,
    int $productId
): ?array {

    $stmt = $pdo->prepare("
        SELECT *
        FROM inventory
        WHERE product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $productId
    ]);

    $inventory =
        $stmt->fetch();

    return $inventory ?: null;
}


/*
|--------------------------------------------------------------------------
| COMMISSION
|--------------------------------------------------------------------------
*/

function getVendorCommission(
    PDO $pdo,
    int $vendorId
): array {

    $stmt = $pdo->prepare("
        SELECT
            c.*,

            vo.subtotal,
            vo.vendor_status

        FROM commission c

        LEFT JOIN vendor_orders vo
            ON c.vendor_order_id =
               vo.vendor_order_id

        WHERE c.vendor_id = ?

        ORDER BY c.created_at DESC
    ");

    $stmt->execute([
        $vendorId
    ]);

    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| VENDOR LIST
|--------------------------------------------------------------------------
*/

function getApprovedVendors(
    PDO $pdo
): array {

    $stmt = $pdo->query("
        SELECT
            v.*,

            u.name,
            u.email

        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.approval_status = 'Approved'

        AND u.status = 'active'

        ORDER BY v.business_name ASC
    ");

    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| IMAGE HELPERS
|--------------------------------------------------------------------------
*/

function productImageUrl(
    ?string $image
): string {

    if (
        empty($image)
    ) {

        return BASE_URL .
               'image/logo.jpg';
    }

    return PRODUCT_UPLOAD_URL .
           rawurlencode(
               basename($image)
           );
}


function vendorLogoUrl(
    ?string $image
): string {

    if (
        empty($image)
    ) {

        return BASE_URL .
               'image/logo.jpg';
    }

    return VENDOR_UPLOAD_URL .
           rawurlencode(
               basename($image)
           );
}


/*
|--------------------------------------------------------------------------
| FORMAT MONEY
|--------------------------------------------------------------------------
*/

function formatMoney(
    float $amount
): string {

    return CURRENCY .
        ' ' .
        number_format(
            $amount,
            2
        );
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function formatDateTime(
    ?string $date
): string {

    if (
        empty($date)
    ) {

        return '-';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

function csrfToken(): string
{
    if (
        empty(
            $_SESSION['csrf_token']
        )
    ) {

        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    return $_SESSION['csrf_token'];
}


function verifyCsrfToken(
    ?string $token
): bool {

    if (
        empty($token)
        ||
        empty(
            $_SESSION['csrf_token']
        )
    ) {

        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| ORDER STATUS
|--------------------------------------------------------------------------
*/

function orderStatusClass(
    string $status
): string {

    return match ($status) {

        'Pending' =>
            'status-pending',

        'Processing' =>
            'status-processing',

        'Completed' =>
            'status-completed',

        'Cancelled' =>
            'status-cancelled',

        default =>
            'status-default'
    };
}


/*
|--------------------------------------------------------------------------
| PRODUCT STATUS
|--------------------------------------------------------------------------
*/

function productStatusClass(
    string $status
): string {

    return match ($status) {

        'Available' =>
            'status-available',

        'Out of Stock' =>
            'status-out',

        'Hidden' =>
            'status-hidden',

        default =>
            'status-default'
    };
}