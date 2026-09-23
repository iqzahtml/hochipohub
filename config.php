<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL CONFIGURATION
|--------------------------------------------------------------------------
|
| Supported access:
|
| 1. Localhost
|    http://localhost/hochipohub/
|
| 2. Ngrok
|    https://xxxxx.ngrok-free.dev/hochipohub/
|
| 3. Laragon Virtual Host
|    http://hochipohub.test/
|
| 4. Cloudflare Tunnel
|    https://xxxxx.trycloudflare.com/
|
| 5. Public Domain
|    https://hochipohub.jtmkpmj.com/
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DETECT CURRENT HOST
|--------------------------------------------------------------------------
*/

$currentHost = strtolower(
    trim(
        (string) (
            $_SERVER['HTTP_HOST']
            ?? 'localhost'
        )
    )
);


/*
|--------------------------------------------------------------------------
| REMOVE PORT FOR HOST DETECTION
|--------------------------------------------------------------------------
*/

$hostWithoutPort = preg_replace(
    '/:\d+$/',
    '',
    $currentHost
);


/*
|--------------------------------------------------------------------------
| DETECT HTTPS
|--------------------------------------------------------------------------
*/

$isHttps = false;


/*
|--------------------------------------------------------------------------
| DIRECT HTTPS
|--------------------------------------------------------------------------
*/

if (
    !empty($_SERVER['HTTPS']) &&
    strtolower(
        (string) $_SERVER['HTTPS']
    ) !== 'off'
) {
    $isHttps = true;
}


/*
|--------------------------------------------------------------------------
| REVERSE PROXY HTTPS
|--------------------------------------------------------------------------
|
| Ngrok / Cloudflare may terminate HTTPS before forwarding
| the request to Apache.
|
|--------------------------------------------------------------------------
*/

if (
    !empty(
        $_SERVER['HTTP_X_FORWARDED_PROTO']
    )
) {

    $forwardedProto =
        strtolower(
            trim(
                explode(
                    ',',
                    (string)
                    $_SERVER['HTTP_X_FORWARDED_PROTO']
                )[0]
            )
        );

    if ($forwardedProto === 'https') {
        $isHttps = true;
    }
}


/*
|--------------------------------------------------------------------------
| CURRENT SCHEME
|--------------------------------------------------------------------------
*/

$currentScheme =
    $isHttps
        ? 'https'
        : 'http';


/*
|--------------------------------------------------------------------------
| DETECT ACCESS TYPE
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOCALHOST
|--------------------------------------------------------------------------
*/

$isLocalhost =
    $hostWithoutPort === 'localhost'
    ||
    $hostWithoutPort === '127.0.0.1';


/*
|--------------------------------------------------------------------------
| LARAGON VIRTUAL HOST
|--------------------------------------------------------------------------
*/

$isLaragonVirtualHost =
    $hostWithoutPort === 'hochipohub.test';


/*
|--------------------------------------------------------------------------
| PUBLIC DOMAIN
|--------------------------------------------------------------------------
*/

$isPublicDomain =
    $hostWithoutPort === 'hochipohub.jtmkpmj.com';


/*
|--------------------------------------------------------------------------
| NGROK
|--------------------------------------------------------------------------
*/

$isNgrok =
    str_contains(
        $hostWithoutPort,
        'ngrok-free.dev'
    )
    ||
    str_contains(
        $hostWithoutPort,
        'ngrok.io'
    );


/*
|--------------------------------------------------------------------------
| CLOUDFLARE
|--------------------------------------------------------------------------
*/

$isCloudflare =
    str_contains(
        $hostWithoutPort,
        'trycloudflare.com'
    );


/*
|--------------------------------------------------------------------------
| DETECT DOCUMENT ROOT
|--------------------------------------------------------------------------
|
| Public domain Apache VirtualHost points directly to:
|
| C:\laragon\www\hochipohub
|
| Therefore assets must use:
|
| /css/style.css
| /css/modal.css
| /js/modal.js
| /image/logo.jpeg
|
| NOT:
|
| /hochipohub/css/style.css
|
|--------------------------------------------------------------------------
*/

$documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
    ? str_replace(
        '\\',
        '/',
        rtrim(
            (string) $_SERVER['DOCUMENT_ROOT'],
            '/\\'
        )
    )
    : '';

$projectRoot = str_replace(
    '\\',
    '/',
    rtrim(
        __DIR__,
        '/\\'
    )
);

$isProjectDocumentRoot =
    $documentRoot !== ''
    &&
    strtolower($documentRoot)
        === strtolower($projectRoot);


/*
|--------------------------------------------------------------------------
| PROJECT BASE PATH
|--------------------------------------------------------------------------
|
| localhost:
| /hochipohub/
|
| ngrok:
| /hochipohub/
|
| hochipohub.test:
| /
|
| trycloudflare.com:
| /
|
| hochipohub.jtmkpmj.com:
| /
|
|--------------------------------------------------------------------------
*/

if (
    $isPublicDomain ||
    $isLaragonVirtualHost ||
    $isCloudflare ||
    $isProjectDocumentRoot
) {

    $basePath = '/';

} else {

    $basePath = '/hochipohub/';
}


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

define(
    'BASE_URL',
    $basePath
);


/*
|--------------------------------------------------------------------------
| ABSOLUTE BASE URL
|--------------------------------------------------------------------------
*/

define(
    'ABSOLUTE_BASE_URL',
    $currentScheme
    . '://'
    . $currentHost
    . BASE_URL
);


/*
|--------------------------------------------------------------------------
| HOST TYPE CONSTANTS
|--------------------------------------------------------------------------
*/

define(
    'IS_LOCALHOST',
    $isLocalhost
);

define(
    'IS_LARAGON_VIRTUAL_HOST',
    $isLaragonVirtualHost
);

define(
    'IS_PUBLIC_DOMAIN',
    $isPublicDomain
);

define(
    'IS_NGROK',
    $isNgrok
);

define(
    'IS_CLOUDFLARE',
    $isCloudflare
);


/*
|--------------------------------------------------------------------------
| PUBLIC SHORT URL
|--------------------------------------------------------------------------
*/

define(
    'PUBLIC_SHORT_URL',
    'https://bit.ly/4jggF1v'
);


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set(
        'session.use_only_cookies',
        '1'
    );

    ini_set(
        'session.use_strict_mode',
        '1'
    );

    session_set_cookie_params([
        'lifetime' => 0,

        'path' => '/',

        'domain' => '',

        /*
        |--------------------------------------------------------------------------
        | Keep false for localhost HTTP compatibility.
        |--------------------------------------------------------------------------
        */

        'secure' => false,

        'httponly' => true,

        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

define(
    'DB_HOST',
    'localhost'
);

define(
    'DB_NAME',
    'hochipohub'
);

define(
    'DB_USER',
    'root'
);

define(
    'DB_PASS',
    ''
);

define(
    'DB_CHARSET',
    'utf8mb4'
);


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


/*
|--------------------------------------------------------------------------
| SMTP CONFIGURATION
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


/*
|--------------------------------------------------------------------------
| SMTP PASSWORD
|--------------------------------------------------------------------------
|
| Password is read from the server environment.
| Do not store the Gmail App Password directly in this file.
|
|--------------------------------------------------------------------------
*/

define(
    'SMTP_PASSWORD',
    getenv('lhgellhkvzappujl') ?: ''
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
    __DIR__
    . '/uploads/products/'
);

define(
    'VENDOR_UPLOAD_PATH',
    __DIR__
    . '/uploads/vendors/'
);


/*
|--------------------------------------------------------------------------
| UPLOAD URL
|--------------------------------------------------------------------------
*/

define(
    'PRODUCT_UPLOAD_URL',
    BASE_URL
    . 'uploads/products/'
);

define(
    'VENDOR_UPLOAD_URL',
    BASE_URL
    . 'uploads/vendors/'
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


    /*
    |--------------------------------------------------------------------------
    | REUSE CONNECTION
    |--------------------------------------------------------------------------
    */

    if ($db instanceof PDO) {
        return $db;
    }


    /*
    |--------------------------------------------------------------------------
    | DSN
    |--------------------------------------------------------------------------
    */

    $dsn =
        'mysql:host='
        . DB_HOST
        . ';dbname='
        . DB_NAME
        . ';charset='
        . DB_CHARSET;


    /*
    |--------------------------------------------------------------------------
    | CONNECT
    |--------------------------------------------------------------------------
    */

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


        /*
        |--------------------------------------------------------------------------
        | DEVELOPMENT ERROR
        |--------------------------------------------------------------------------
        */

        if (
            defined('APP_DEBUG') &&
            APP_DEBUG
        ) {

            die(
                'Database connection failed: '
                . htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCTION ERROR
        |--------------------------------------------------------------------------
        */

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
            'Location: '
            . $url
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN STATUS
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset(
        $_SESSION['user_id']
    )
    &&
    !empty(
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
    if (
        !isset(
            $_SESSION['role']
        )
    ) {
        return false;
    }

    $currentRole =
        strtolower(
            trim(
                (string)
                $_SESSION['role']
            )
        );

    $requiredRole =
        strtolower(
            trim(
                (string)
                $role
            )
        );

    return $currentRole
        === $requiredRole;
}


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return hasRole(
        'admin'
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR CHECK
|--------------------------------------------------------------------------
*/

function isVendor()
{
    return hasRole(
        'vendor'
    );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER CHECK
|--------------------------------------------------------------------------
*/

function isCustomer()
{
    return hasRole(
        'customer'
    );
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
            BASE_URL
            . 'index.php'
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
            BASE_URL
            . 'index.php'
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
            BASE_URL
            . 'index.php'
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
            BASE_URL
            . 'index.php'
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
    return $_SESSION[
        'csrf_token'
    ];
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
        is_string(
            $token
        )
        &&
        hash_equals(
            $_SESSION['csrf_token'],
            $token
        )
    );
}


/*
|--------------------------------------------------------------------------
| SET FLASH MESSAGE
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


/*
|--------------------------------------------------------------------------
| GET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

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
| CREATE PRODUCT UPLOAD DIRECTORY
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


/*
|--------------------------------------------------------------------------
| CREATE VENDOR UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

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