<?php
/*
|--------------------------------------------------------------------------
| HochipoHub Configuration
|--------------------------------------------------------------------------
| Main configuration file
| Used by:
| - database/db.php
| - includes/*
| - auth/*
| - seller/*
| - admin/*
| - ajax/*
| - mail/*
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Website Information
|--------------------------------------------------------------------------
*/

define('SITE_NAME', 'HochipoHub');

define('SITE_VERSION', '1.0.0');

define('BASE_URL', 'http://localhost/hochipohub/');

define('ROOT_PATH', __DIR__);

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

define('DB_HOST', 'localhost');

define('DB_NAME', 'hochipohub');

define('DB_USER', 'root');

/*
|--------------------------------------------------------------------------
| Laragon default password is empty.
| Change if necessary.
|--------------------------------------------------------------------------
*/

define('DB_PASS', '');

/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Kuala_Lumpur');

/*
|--------------------------------------------------------------------------
| Upload Folder
|--------------------------------------------------------------------------
*/

define(
    'UPLOAD_PATH',
    ROOT_PATH . '/uploads/'
);

define(
    'PRODUCT_UPLOAD_PATH',
    UPLOAD_PATH . 'products/'
);

define(
    'VENDOR_UPLOAD_PATH',
    UPLOAD_PATH . 'vendors/'
);

define(
    'PROFILE_UPLOAD_PATH',
    UPLOAD_PATH . 'profiles/'
);

define(
    'REVIEW_UPLOAD_PATH',
    UPLOAD_PATH . 'reviews/'
);

/*
|--------------------------------------------------------------------------
| Upload URL
|--------------------------------------------------------------------------
*/

define(
    'UPLOAD_URL',
    BASE_URL . 'uploads/'
);

define(
    'PRODUCT_UPLOAD_URL',
    UPLOAD_URL . 'products/'
);

define(
    'VENDOR_UPLOAD_URL',
    UPLOAD_URL . 'vendors/'
);

define(
    'PROFILE_UPLOAD_URL',
    UPLOAD_URL . 'profiles/'
);

define(
    'REVIEW_UPLOAD_URL',
    UPLOAD_URL . 'reviews/'
);

/*
|--------------------------------------------------------------------------
| Static Images
|--------------------------------------------------------------------------
*/

define(
    'IMAGE_URL',
    BASE_URL . 'image/'
);

define(
    'LOGO_URL',
    IMAGE_URL . 'logo.jpg'
);

define(
    'BANNER_URL',
    IMAGE_URL . 'banner.jpg'
);

/*
|--------------------------------------------------------------------------
| Seller Folder
|--------------------------------------------------------------------------
*/

define(
    'SELLER_FOLDER',
    BASE_URL . 'seller/'
);

/*
|--------------------------------------------------------------------------
| Admin Folder
|--------------------------------------------------------------------------
*/

define(
    'ADMIN_FOLDER',
    BASE_URL . 'admin/'
);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

define(
    'SESSION_TIMEOUT',
    60 * 60
);

define(
    'REMEMBER_ME_DAYS',
    30
);

/*
|--------------------------------------------------------------------------
| OTP / MFA
|--------------------------------------------------------------------------
*/

define(
    'OTP_LENGTH',
    6
);

define(
    'OTP_EXPIRE_MINUTES',
    5
);

define(
    'MAX_OTP_ATTEMPT',
    5
);

/*
|--------------------------------------------------------------------------
| Password Reset
|--------------------------------------------------------------------------
*/

define(
    'RESET_CODE_LENGTH',
    6
);

define(
    'RESET_EXPIRE_MINUTES',
    10
);

/*
|--------------------------------------------------------------------------
| Upload Restriction
|--------------------------------------------------------------------------
*/

define(
    'MAX_IMAGE_SIZE',
    5 * 1024 * 1024
);

$allowedImageTypes = [

    'image/jpeg',

    'image/png',

    'image/webp'

];

/*
|--------------------------------------------------------------------------
| Product
|--------------------------------------------------------------------------
*/

define(
    'DEFAULT_PRODUCT_IMAGE',
    'default-product.png'
);

define(
    'PRODUCT_PER_PAGE',
    12
);

/*
|--------------------------------------------------------------------------
| Review
|--------------------------------------------------------------------------
*/

define(
    'MAX_REVIEW_IMAGE',
    3
);

/*
|--------------------------------------------------------------------------
| Commission
|--------------------------------------------------------------------------
*/

define(
    'DEFAULT_COMMISSION_RATE',
    5.00
);

/*
|--------------------------------------------------------------------------
| Currency
|--------------------------------------------------------------------------
*/

define(
    'CURRENCY',
    'RM'
);

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

define(
    'PAGINATION_LIMIT',
    12
);

/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

ini_set(
    'session.cookie_httponly',
    1
);

ini_set(
    'session.use_only_cookies',
    1
);

/*
|--------------------------------------------------------------------------
| Development Mode
|--------------------------------------------------------------------------
|
| true  = show errors
| false = hide errors
|
*/

define(
    'DEVELOPMENT_MODE',
    true
);

if (DEVELOPMENT_MODE) {

    error_reporting(E_ALL);

    ini_set(
        'display_errors',
        1
    );

} else {

    error_reporting(0);

    ini_set(
        'display_errors',
        0
    );

}

/*
|--------------------------------------------------------------------------
| Email
|--------------------------------------------------------------------------
|
| Used by PHPMailer
|
*/

define(
    'MAIL_FROM_NAME',
    'HochipoHub'
);

/*
|--------------------------------------------------------------------------
| Default Redirect
|--------------------------------------------------------------------------
*/

define(
    'HOME_PAGE',
    BASE_URL . 'index.php'
);

define(
    'LOGIN_PAGE',
    BASE_URL . 'auth/login.php'
);

define(
    'SELLER_DASHBOARD',
    BASE_URL . 'seller/dashboard.php'
);

define(
    'ADMIN_DASHBOARD',
    BASE_URL . 'admin/dashboard.php'
);

define(
    'CUSTOMER_DASHBOARD',
    BASE_URL . 'dashboard.php'
);