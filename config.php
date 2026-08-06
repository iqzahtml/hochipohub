<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
*/

define('SITE_NAME', 'HochipoHub');

define(
    'BASE_URL',
    'http://localhost/hochipohub/'
);


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

define('DB_HOST', 'localhost');
define('DB_NAME', 'hochipohub');
define('DB_USER', 'root');
define('DB_PASS', '');


/*
|--------------------------------------------------------------------------
| Root Path
|--------------------------------------------------------------------------
*/

define(
    'ROOT_PATH',
    __DIR__
);


/*
|--------------------------------------------------------------------------
| Upload
|--------------------------------------------------------------------------
*/

define(
    'UPLOAD_PATH',
    ROOT_PATH . '/uploads/'
);

define(
    'PRODUCT_PATH',
    UPLOAD_PATH . 'products/'
);

define(
    'VENDOR_PATH',
    UPLOAD_PATH . 'vendors/'
);

define(
    'PROFILE_PATH',
    UPLOAD_PATH . 'profiles/'
);

define(
    'REVIEW_PATH',
    UPLOAD_PATH . 'reviews/'
);


/*
|--------------------------------------------------------------------------
| Upload URL
|--------------------------------------------------------------------------
*/

define(
    'PRODUCT_URL',
    BASE_URL . 'uploads/products/'
);

define(
    'VENDOR_URL',
    BASE_URL . 'uploads/vendors/'
);

define(
    'PROFILE_URL',
    BASE_URL . 'uploads/profiles/'
);

define(
    'REVIEW_URL',
    BASE_URL . 'uploads/reviews/'
);


/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

define(
    'MFA_EXPIRY',
    5
);

define(
    'RESET_EXPIRY',
    10
);


/*
|--------------------------------------------------------------------------
| Development
|--------------------------------------------------------------------------
*/

define(
    'DEVELOPMENT_MODE',
    true
);


if(DEVELOPMENT_MODE){

    error_reporting(E_ALL);

    ini_set(
        'display_errors',
        1
    );

}else{

    error_reporting(0);

    ini_set(
        'display_errors',
        0
    );

}


/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

date_default_timezone_set(
    'Asia/Kuala_Lumpur'
);

?>