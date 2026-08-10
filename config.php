<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL CONFIGURATION
|--------------------------------------------------------------------------
| File:
| config.php
|
| Purpose:
| - Database configuration
| - PDO connection
| - Application configuration
| - Helper function for database access
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

define('SITE_NAME', 'HochipoHub');

define(
    'BASE_URL',
    'http://localhost/hochipoHub/'
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
| DEFAULT COMMISSION RATE
|--------------------------------------------------------------------------
|
| 5% default commission.
| Admin can still manage commission records from admin panel.
|--------------------------------------------------------------------------
*/

define('DEFAULT_COMMISSION_RATE', 5.00);


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

    $dsn = 'mysql:host=' . DB_HOST .
           ';dbname=' . DB_NAME .
           ';charset=' . DB_CHARSET;

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

        die(
            'Database connection failed. Please check your database configuration.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| SECURITY / ESCAPE HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| REDIRECT HELPER
|--------------------------------------------------------------------------
*/

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| CURRENT USER ID
|--------------------------------------------------------------------------
*/

function currentUserId()
{
    return $_SESSION['user_id'] ?? null;
}


/*
|--------------------------------------------------------------------------
| CURRENT USER ROLE
|--------------------------------------------------------------------------
*/

function currentUserRole()
{
    return $_SESSION['role'] ?? null;
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

function hasRole($role)
{
    return (
        isset($_SESSION['role']) &&
        $_SESSION['role'] === $role
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return hasRole('admin');
}


/*
|--------------------------------------------------------------------------
| VENDOR CHECK
|--------------------------------------------------------------------------
*/

function isVendor()
{
    return hasRole('vendor');
}


/*
|--------------------------------------------------------------------------
| CUSTOMER CHECK
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

if (!isset($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
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
        isset($_SESSION['csrf_token']) &&
        hash_equals(
            $_SESSION['csrf_token'],
            $token
        )
    );
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


/*
|--------------------------------------------------------------------------
| GET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

function getFlash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];

    unset($_SESSION['flash']);

    return $flash;
}


/*
|--------------------------------------------------------------------------
| CREATE UPLOAD DIRECTORIES
|--------------------------------------------------------------------------
*/

if (!is_dir(PRODUCT_UPLOAD_PATH)) {
    @mkdir(
        PRODUCT_UPLOAD_PATH,
        0777,
        true
    );
}

if (!is_dir(VENDOR_UPLOAD_PATH)) {
    @mkdir(
        VENDOR_UPLOAD_PATH,
        0777,
        true
    );
}