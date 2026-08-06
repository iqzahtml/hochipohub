<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Main Configuration
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

define(
    'BASE_URL',
    'http://localhost/hochipohub/'
);


/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

define('DB_HOST', 'localhost');
define('DB_NAME', 'hochipohub');
define('DB_USER', 'root');
define('DB_PASS', '');


/*
|--------------------------------------------------------------------------
| Upload Directories
|--------------------------------------------------------------------------
*/

define(
    'PRODUCT_UPLOAD_PATH',
   __DIR__. '/uploads/products/'
);

define(
    'VENDOR_UPLOAD_PATH',
    __DIR__ . '/uploads/vendors/'
);

define(
    'REVIEW_UPLOAD_PATH',
    __DIR__. '/uploads/reviews/'
);

define(
    'PROFILE_UPLOAD_PATH',
    __DIR__. '/uploads/profiles/'
);


/*
|--------------------------------------------------------------------------
| Upload URLs
|--------------------------------------------------------------------------
*/

define(
    'PRODUCT_UPLOAD_URL',
    BASE_URL . 'uploads/products/'
);

define(
    'VENDOR_UPLOAD_URL',
    BASE_URL . 'uploads/vendors/'
);

define(
    'REVIEW_UPLOAD_URL',
    BASE_URL . 'uploads/reviews/'
);

define(
    'PROFILE_UPLOAD_URL',
    BASE_URL . 'uploads/profiles/'
);


/*
|--------------------------------------------------------------------------
| Image Directory
|--------------------------------------------------------------------------
*/

define(
    'IMAGE_URL',
    BASE_URL . 'image/'
);


/*
|--------------------------------------------------------------------------
| Security Settings
|--------------------------------------------------------------------------
*/

define(
    'SESSION_TIMEOUT',
    3600
);

define(
    'MFA_EXPIRY_MINUTES',
    5
);

define(
    'PASSWORD_RESET_EXPIRY_MINUTES',
    10
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
| Error Reporting
|--------------------------------------------------------------------------
|
| Development mode:
| true  = show errors
| false = hide errors
|
*/

define('DEVELOPMENT_MODE', true);

if (DEVELOPMENT_MODE === true) {

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

} else {

    error_reporting(0);
    ini_set('display_errors', '0');
}


/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Kuala_Lumpur');