<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Global Functions
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) .
    '/database/db.php';

require_once DIR . '/session.php';


/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

function cleanInput($value)
{
    return htmlspecialchars(
        trim($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Generate Secure OTP
|--------------------------------------------------------------------------
*/

function generateOTP($length = 6)
{
    $min = pow(10, $length - 1);

    $max = pow(10, $length) - 1;

    return (string) random_int(
        $min,
        $max
    );
}


/*
|--------------------------------------------------------------------------
| Generate Secure Token
|--------------------------------------------------------------------------
*/

function generateToken($length = 32)
{
    return bin2hex(
        random_bytes($length)
    );
}


/*
|--------------------------------------------------------------------------
| Password Hash
|--------------------------------------------------------------------------
*/

function hashPassword($password)
{
    return password_hash(
        $password,
        PASSWORD_DEFAULT
    );
}


/*
|--------------------------------------------------------------------------
| Password Verify
|--------------------------------------------------------------------------
*/

function verifyPassword(
    $password,
    $hashedPassword
) {

    return password_verify(
        $password,
        $hashedPassword
    );
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

function redirect($url)
{
    header(
        'Location: ' . $url
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Redirect To
|--------------------------------------------------------------------------
*/

function redirectTo($page)
{
    redirect(
        BASE_URL . $page
    );
}


/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

function setFlashMessage(
    $type,
    $message
) {

    $_SESSION['flash_message'] = [

        'type' => $type,

        'message' => $message

    ];
}


/*
|--------------------------------------------------------------------------
| Get Flash Message
|--------------------------------------------------------------------------
*/

function getFlashMessage()
{
    if (
        isset(
            $_SESSION['flash_message']
        )
    ) {

        $message =
            $_SESSION['flash_message'];

        unset(
            $_SESSION['flash_message']
        );

        return $message;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Format Currency
|--------------------------------------------------------------------------
*/

function formatPrice($price)
{
    return 'RM ' .
        number_format(
            (float) $price,
            2
        );
}


/*
|--------------------------------------------------------------------------
| Format Date
|--------------------------------------------------------------------------
*/

function formatDate($date)
{
    if (!$date) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );
}


/*
|--------------------------------------------------------------------------
| Check Email
|--------------------------------------------------------------------------
*/

function isValidEmail($email)
{
    return filter_var(
        $email,FILTER_VALIDATE_EMAIL
    );
}


/*
|--------------------------------------------------------------------------
| Get User By ID
|--------------------------------------------------------------------------
*/

function getUserById($userId)
{
    global $pdo;

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
            created_at
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Get Vendor By User ID
|--------------------------------------------------------------------------
*/

function getVendorByUserId($userId)
{
    global $pdo;

    $sql = "
        SELECT
            v.*,
            u.name,
            u.email,
            u.phone
        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.user_id = ?

        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Get Vendor By ID
|--------------------------------------------------------------------------
*/

function getVendorById($vendorId)
{
    global $pdo;

    $sql = "
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
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $vendorId
    ]);

    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Get Product By ID
|--------------------------------------------------------------------------
*/

function getProductById($productId)
{
    global $pdo;

    $sql = "
        SELECT
            p.*,

            v.vendor_id,
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

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $productId
    ]);

    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Get Product Rating
|--------------------------------------------------------------------------
*/

function getProductRating($productId)
{
    global $pdo;

    $sql = "
        SELECT
            AVG(rating) AS average_rating,
            COUNT(review_id) AS total_reviews

        FROM reviews

        WHERE product_id = ?

        AND status = 'Visible'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $productId
    ]);

    $result = $stmt->fetch();

    return [

        'average' =>
            round(
                (float)
                ($result['average_rating'] ?? 0),
                1
            ),

        'total' =>
            (int)
            ($result['total_reviews'] ?? 0)

    ];
}


/*
|--------------------------------------------------------------------------
| Check Wishlist
|--------------------------------------------------------------------------
*/

function isInWishlist(
    $userId,
    $productId
) {

    global $pdo;

    $sql = "
        SELECT wishlist_id

        FROM wishlist

        WHERE user_id = ?

        AND product_id = ?

        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userId,
        $productId
    ]);

    return $stmt->fetch()
        !== false;
}


/*
|--------------------------------------------------------------------------
| Get Cart Count|--------------------------------------------------------------------------
*/

function getCartCount($userId)
{
    global $pdo;

    $sql = "
        SELECT
            COALESCE(
                SUM(quantity),
                0
            ) AS total

        FROM cart

        WHERE customer_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    $result = $stmt->fetch();

    return (int)
        ($result['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| Get Wishlist Count
|--------------------------------------------------------------------------
*/

function getWishlistCount($userId)
{
    global $pdo;

    $sql = "
        SELECT COUNT(*) AS total

        FROM wishlist

        WHERE user_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    $result = $stmt->fetch();

    return (int)
        ($result['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| Get Order Count
|--------------------------------------------------------------------------
*/

function getOrderCount($userId)
{
    global $pdo;

    $sql = "
        SELECT COUNT(*) AS total

        FROM orders

        WHERE customer_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    $result = $stmt->fetch();

    return (int)
        ($result['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| Get Vendor Product Count
|--------------------------------------------------------------------------
*/

function getVendorProductCount(
    $vendorId
) {

    global $pdo;

    $sql = "
        SELECT COUNT(*) AS total

        FROM products

        WHERE vendor_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $vendorId
    ]);

    $result = $stmt->fetch();

    return (int)
        ($result['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| Upload Validation
|--------------------------------------------------------------------------
*/

function validateImageUpload(
    $file,
    $maxSize = 5242880
) {

    if (
        !isset($file['error'])
        ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {

        return [
            'success' => false,
            'message' => 'Invalid image upload.'
        ];
    }


    if ($file['size'] > $maxSize) {

        return [
            'success' => false,
            'message' =>
                'Image size must not exceed 5MB.'
        ];
    }


    $allowedTypes = [

        'image/jpeg',

        'image/png',

        'image/webp'

    ];


    $finfo = new finfo(
        FILEINFO_MIME_TYPE
    );

    $mimeType =
        $finfo->file(
            $file['tmp_name']
        );


    if (
        !in_array(
            $mimeType,
            $allowedTypes,
            true
        )
    ) {

        return [
            'success' => false,
            'message' =>
                'Only JPG, PNG and WEBP images are allowed.'
        ];
    }


    return [
        'success' => true,
        'message' => 'Valid image.'
    ];
}


/*
|--------------------------------------------------------------------------
| Safe File Name
|--------------------------------------------------------------------------
*/

function generateSafeFileName(
    $originalName
) {

    $extension =
        strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );

    return uniqid(
        'hochipo_',
        true
    ) . '.' . $extension;
}


/*
|--------------------------------------------------------------------------
| Product Image URL
|--------------------------------------------------------------------------
*/

function productImage(
    $image
) {

    if (
        empty($image)
    ) {

        return IMAGE_URL .
            'products/default-product.jpg';
    }

    return PRODUCT_UPLOAD_URL .rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| Vendor Logo URL
|--------------------------------------------------------------------------
*/

function vendorLogo(
    $logo
) {

    if (
        empty($logo)
    ) {

        return IMAGE_URL .
            'vendors/default-vendor.jpg';
    }

    return VENDOR_UPLOAD_URL .
        rawurlencode($logo);
}


/*
|--------------------------------------------------------------------------
| Profile Image URL
|--------------------------------------------------------------------------
*/

function profileImage(
    $image
) {

    if (
        empty($image)
    ) {

        return IMAGE_URL .
            'default-profile.jpg';
    }

    return PROFILE_UPLOAD_URL .
        rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

function csrfToken()
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


/*
|--------------------------------------------------------------------------
| Verify CSRF Token
|--------------------------------------------------------------------------
*/

function verifyCsrfToken(
    $token
) {

    return isset(
        $_SESSION['csrf_token']
    )
    &&
    hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| Create Admin Log
|--------------------------------------------------------------------------
*/

function createAdminLog(
    $adminId,
    $action,
    $targetType = null,
    $targetId = null
) {

    global $pdo;

    $sql = "
        INSERT INTO admin_logs
        (
            admin_id,
            action,
            target_type,
            target_id
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        $adminId,
        $action,
        $targetType,
        $targetId
    ]);
}