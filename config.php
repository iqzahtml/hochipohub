<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL CONFIGURATION
|--------------------------------------------------------------------------
| File:
| config.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

define('DB_HOST', 'localhost');

define('DB_NAME', 'hochipohub');

define('DB_USER', 'root');

define('DB_PASS', '');

define('DB_CHARSET', 'utf8mb4');


/*
|--------------------------------------------------------------------------
| APPLICATION CONFIGURATION
|--------------------------------------------------------------------------
*/

define(
    'SITE_NAME',
    'HochipoHub'
);

define(
    'APP_NAME',
    'HochipoHub'
);

define(
    'BASE_URL',
    'http://localhost/hochipoHub/'
);


/*
|--------------------------------------------------------------------------
| SMTP CONFIGURATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| SMTP_USERNAME
| = Gmail account used by HochipoHub to SEND emails.
|
| SMTP_PASSWORD
| = Gmail APP PASSWORD.
|
| It is NOT the normal Gmail password.
|
|--------------------------------------------------------------------------
*/

define(
    'SMTP_HOST',
    'smtp.gmail.com'
);

define(
    'SMTP_PORT',
    587
);

define(
    'SMTP_USERNAME',
    'hochipohub941@gmail.com'
);

define(
    'SMTP_PASSWORD',
    'lhgellhkvzappujl'
);

define(
    'SMTP_FROM_EMAIL',
    'hochipohub941@gmail.com'
);

define(
    'SMTP_FROM_NAME',
    'HochipoHub'
);


/*
|--------------------------------------------------------------------------
| OTP CONFIGURATION
|--------------------------------------------------------------------------
*/

define(
    'OTP_EXPIRY_MINUTES',
    10
);


/*
|--------------------------------------------------------------------------
| DEBUG
|--------------------------------------------------------------------------
*/

define(
    'APP_DEBUG',
    true
);


/*
|--------------------------------------------------------------------------
| UPLOAD PATH
|--------------------------------------------------------------------------
*/

define(
    'PRODUCT_UPLOAD_PATH',
    __DIR__ . '/uploads/products/'
);

define(
    'VENDOR_UPLOAD_PATH',
    __DIR__ . '/uploads/vendors/'
);


/*
|--------------------------------------------------------------------------
| UPLOAD URL
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


/*
|--------------------------------------------------------------------------
| DEFAULT COMMISSION
|--------------------------------------------------------------------------
*/

define(
    'DEFAULT_COMMISSION_RATE',
    5.00
);


/*
|--------------------------------------------------------------------------
| PDO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

function getDB()
{
    static $db = null;

    if ($db instanceof PDO) {

        return $db;
    }


    $dsn =
        'mysql:host='
        . DB_HOST
        . ';dbname='
        . DB_NAME
        . ';charset='
        . DB_CHARSET;


    try {

        $db = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false
            ]
        );


        return $db;


    } catch (PDOException $e) {

        if (APP_DEBUG) {

            die(
                'Database connection failed: '
                . htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }


        die(
            'Database connection failed.'
        );
    }
}


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
| LOGIN
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset(
        $_SESSION['user_id']
    );
}


/*
|--------------------------------------------------------------------------
| CURRENT USER ID
|--------------------------------------------------------------------------
*/

function currentUserId()
{
    return $_SESSION['user_id']
        ?? null;
}


/*
|--------------------------------------------------------------------------
| CURRENT USER ROLE
|--------------------------------------------------------------------------
*/

function currentUserRole()
{
    return $_SESSION['role']
        ?? null;
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

function hasRole($role)
{
    return (
        isset($_SESSION['role'])
        &&
        $_SESSION['role'] === $role
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return hasRole('admin');
}


/*
|--------------------------------------------------------------------------
| VENDOR
|--------------------------------------------------------------------------
*/

function isVendor()
{
    return hasRole('vendor');
}


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

function isCustomer()
{
    return hasRole('customer');
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isLoggedIn()) {

        $_SESSION['error'] =
            'Please login to continue.';

        redirect(
            BASE_URL . 'index.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
*/

function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {

        $_SESSION['error'] =
            'Access denied.';

        redirect(
            BASE_URL . 'index.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE VENDOR
|--------------------------------------------------------------------------
*/

function requireVendor()
{
    requireLogin();

    if (!isVendor()) {

        $_SESSION['error'] =
            'Vendor access required.';

        redirect(
            BASE_URL . 'index.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER
|--------------------------------------------------------------------------
*/

function requireCustomer()
{
    requireLogin();

    if (!isCustomer()) {

        $_SESSION['error'] =
            'Customer access required.';

        redirect(
            BASE_URL . 'index.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| GET CSRF TOKEN
|--------------------------------------------------------------------------
*/

function csrfToken()
{
    return $_SESSION['csrf_token'];
}


/*
|--------------------------------------------------------------------------
| VERIFY CSRF TOKEN
|--------------------------------------------------------------------------
*/

function verifyCsrfToken($token)
{
    return (
        isset(
            $_SESSION['csrf_token']
        )
        &&
        is_string($token)
        &&
        hash_equals(
            $_SESSION['csrf_token'],
            $token
        )
    );
}


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

function setFlash(
    $type,
    $message
) {

    $_SESSION['flash'] = [
        'type' =>
            $type,

        'message' =>
            $message
    ];
}


function getFlash()
{
    if (
        !isset(
            $_SESSION['flash']
        )
    ) {

        return null;
    }


    $flash =
        $_SESSION['flash'];


    unset(
        $_SESSION['flash']
    );


    return $flash;
}


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORIES
|--------------------------------------------------------------------------
*/

if (
    !is_dir(
        PRODUCT_UPLOAD_PATH
    )
) {

    @mkdir(
        PRODUCT_UPLOAD_PATH,
        0777,
        true
    );
}


if (
    !is_dir(
        VENDOR_UPLOAD_PATH
    )
) {

    @mkdir(
        VENDOR_UPLOAD_PATH,
        0777,
        true
    );
}

?>