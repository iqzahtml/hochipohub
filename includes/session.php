<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SESSION MANAGEMENT
|--------------------------------------------------------------------------
| File:
| includes/session.php
|
| Purpose:
| - Start session
| - Manage login session
| - Role checking
| - Authentication protection
| - Flash messages
| - CSRF token
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    $secure = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/

$sessionTimeout = 7200;

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $sessionTimeout
) {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    session_start();
}

$_SESSION['last_activity'] = time();


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return (
        isset($_SESSION['user_id']) &&
        !empty($_SESSION['user_id'])
    );
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}


/*
|--------------------------------------------------------------------------
| GET USER NAME
|--------------------------------------------------------------------------
*/

function getUserName()
{
    return $_SESSION['name']
        ?? $_SESSION['user_name']
        ?? '';
}


/*
|--------------------------------------------------------------------------
| GET USER EMAIL
|--------------------------------------------------------------------------
*/

function getUserEmail()
{
    return $_SESSION['email']
        ?? $_SESSION['user_email']
        ?? '';
}


/*
|--------------------------------------------------------------------------
| GET USER ROLE
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Main config uses:
|
| $_SESSION['role']
|
|--------------------------------------------------------------------------
*/

function getUserRole()
{
    return $_SESSION['role'] ?? '';
}


/*
|--------------------------------------------------------------------------
| GET USER STATUS
|--------------------------------------------------------------------------
*/

function getUserStatus()
{
    return $_SESSION['status']
        ?? $_SESSION['user_status']
        ?? 'active';
}


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return (
        isLoggedIn() &&
        getUserRole() === 'admin'
    );
}


/*
|--------------------------------------------------------------------------
| VENDOR
|--------------------------------------------------------------------------
*/

function isVendor()
{
    return (
        isLoggedIn() &&
        getUserRole() === 'vendor'
    );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

function isCustomer()
{
    return (
        isLoggedIn() &&
        getUserRole() === 'customer'
    );
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

function hasRole($role)
{
    return (
        isLoggedIn() &&
        getUserRole() === $role
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

        $_SESSION['redirect_after_login'] =
            $_SERVER['REQUEST_URI'] ?? 'index.php';

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL . 'index.php'
                : '../index.php')
        );

        exit;
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

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL . 'index.php'
                : '../index.php')
        );

        exit;
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

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL . 'index.php'
                : '../index.php')
        );

        exit;
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

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL . 'index.php'
                : '../index.php')
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE ROLE
|--------------------------------------------------------------------------
*/

function requireRole($roles)
{
    requireLogin();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (
        !in_array(
            getUserRole(),
            $roles,
            true
        )
    ) {

        $_SESSION['error'] =
            'Access denied.';

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL . 'index.php'
                : '../index.php')
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CREATE LOGIN SESSION
|--------------------------------------------------------------------------
*/

function createLoginSession($user)
{
    if (
        !isset($user['user_id']) ||
        empty($user['user_id'])
    ) {
        return false;
    }

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | MAIN SESSION VALUES
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['name'] =
        $user['name'] ?? '';

    $_SESSION['email'] =
        $user['email'] ?? '';

    $_SESSION['role'] =
        $user['role'] ?? 'customer';

    $_SESSION['status'] =
        $user['status'] ?? 'active';


    /*
    |--------------------------------------------------------------------------
    | BACKWARD COMPATIBILITY
    |--------------------------------------------------------------------------
    |
    | Some older pages may still use these.
    |
    */

    $_SESSION['user_name'] =
        $_SESSION['name'];

    $_SESSION['user_email'] =
        $_SESSION['email'];

    $_SESSION['user_role'] =
        $_SESSION['role'];

    $_SESSION['user_status'] =
        $_SESSION['status'];


    /*
    |--------------------------------------------------------------------------
    | LOGIN INFORMATION
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_time'] =
        time();

    $_SESSION['last_activity'] =
        time();

    $_SESSION['logged_in'] =
        true;


    /*
    |--------------------------------------------------------------------------
    | MFA
    |--------------------------------------------------------------------------
    */

    $_SESSION['mfa_enabled'] =
        !empty($user['mfa_enabled']);

    $_SESSION['mfa_verified'] =
        false;


    return true;
}


/*
|--------------------------------------------------------------------------
| MFA
|--------------------------------------------------------------------------
*/

function markMfaVerified()
{
    $_SESSION['mfa_verified'] = true;
}


function isMfaVerified()
{
    return !empty(
        $_SESSION['mfa_verified']
    );
}


function requiresMfa()
{
    return (
        isLoggedIn() &&
        !empty($_SESSION['mfa_enabled']) &&
        !isMfaVerified()
    );
}


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

function logoutUser()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
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


function getFlashMessage()
{
    if (
        !isset($_SESSION['flash_message'])
    ) {
        return null;
    }

    $message =
        $_SESSION['flash_message'];

    unset(
        $_SESSION['flash_message']
    );

    return $message;
}


/*
|--------------------------------------------------------------------------
| REDIRECT AFTER LOGIN
|--------------------------------------------------------------------------
*/

function getLoginRedirect()
{
    if (
        isset(
            $_SESSION['redirect_after_login']
        )
    ) {

        $redirect =
            $_SESSION['redirect_after_login'];

        unset(
            $_SESSION['redirect_after_login']
        );

        return $redirect;
    }


    if (isAdmin()) {

        return 'admin/dashboard.php';
    }


    if (isVendor()) {

        return 'seller/dashboard.php';
    }


    return 'dashboard.php';
}


/*
|--------------------------------------------------------------------------
| REDIRECT BY ROLE
|--------------------------------------------------------------------------
*/

function redirectByRole()
{
    if (isAdmin()) {

        header(
            'Location: admin/dashboard.php'
        );

        exit;
    }


    if (isVendor()) {

        header(
            'Location: seller/dashboard.php'
        );

        exit;
    }


    header(
        'Location: dashboard.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| SESSION VALUE
|--------------------------------------------------------------------------
*/

function sessionValue(
    $key,
    $default = null
) {

    return $_SESSION[$key]
        ?? $default;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


function generateCsrfToken()
{
    if (
        empty($_SESSION['csrf_token'])
    ) {

        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    return $_SESSION['csrf_token'];
}


function validateCsrfToken($token)
{
    if (
        empty($token) ||
        empty($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        (string) $token
    );
}