<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB SESSION MANAGEMENT
|--------------------------------------------------------------------------
| File:
|     includes/session.php
|
| Purpose:
| - Start secure PHP session
| - Store login information
| - Check authentication
| - Check user roles
| - Provide helper functions
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Settings
    |--------------------------------------------------------------------------
    */

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
| Session Timeout
|--------------------------------------------------------------------------
|
| Automatically log out inactive users after 2 hours.
|
*/

$sessionTimeout = 7200;

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $sessionTimeout
) {

    /*
    |--------------------------------------------------------------------------
    | Destroy Expired Session
    |--------------------------------------------------------------------------
    */

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"] ?? '',
            $params["secure"],
            $params["httponly"]
        );

    }

    session_destroy();

    /*
    |--------------------------------------------------------------------------
    | Start New Session
    |--------------------------------------------------------------------------
    */

    session_start();

}

$_SESSION['last_activity'] = time();


/*
|--------------------------------------------------------------------------
| Authentication Functions
|--------------------------------------------------------------------------
*/


/**
 * Check whether user is logged in.
 *
 * @return bool
 */

function isLoggedIn()
{
    return (
        isset($_SESSION['user_id']) &&
        !empty($_SESSION['user_id'])
    );
}


/**
 * Get current user ID.
 *
 * @return int|null
 */

function getUserId()
{
    if (!isLoggedIn()) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}


/**
 * Get current user's name.
 *
 * @return string
 */

function getUserName()
{
    return $_SESSION['user_name'] ?? '';
}


/**
 * Get current user's email.
 *
 * @return string
 */

function getUserEmail()
{
    return $_SESSION['user_email'] ?? '';
}


/**
 * Get current user's role.
 *
 * @return string
 */

function getUserRole()
{
    return $_SESSION['user_role'] ?? '';
}


/**
 * Get current user's status.
 *
 * @return string
 */

function getUserStatus()
{
    return $_SESSION['user_status'] ?? '';
}


/*
|--------------------------------------------------------------------------
| Role Checking
|--------------------------------------------------------------------------
*/


/**
 * Check whether current user is admin.
 *
 * @return bool
 */

function isAdmin()
{
    return (
        isLoggedIn() &&
        getUserRole() === 'admin'
    );
}


/**
 * Check whether current user is vendor.
 *
 * @return bool
 */

function isVendor()
{
    return (
        isLoggedIn() &&
        getUserRole() === 'vendor'
    );
}


/**
 * Check whether current user is customer.
 *
 * @return bool
 */

function isCustomer()
{
    return (
        isLoggedIn() &&
        getUserRole() === 'customer'
    );
}


/**
 * Check specific role.
 *
 * @param string $role
 * @return bool
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
| Authentication Protection
|--------------------------------------------------------------------------
*/


/**
 * Require login.
 *
 * Redirect user to index page if not logged in.
 */

function requireLogin()
{
    if (!isLoggedIn()) {

        $_SESSION['login_required'] = true;

        /*
        |--------------------------------------------------------------------------
        | Save Requested Page
        |--------------------------------------------------------------------------
        */

        $_SESSION['redirect_after_login'] =
            $_SERVER['REQUEST_URI'] ?? 'index.php';

        header("Location: index.php");

        exit;

    }
}


/**
 * Require admin account.
 */

function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {

        header("Location: ../index.php");

        exit;

    }

}


/**
 * Require vendor account.
 */

function requireVendor()
{
    requireLogin();

    if (!isVendor()) {

        header("Location: ../index.php");

        exit;

    }

}


/**
 * Require customer account.
 */

function requireCustomer()
{
    requireLogin();

    if (!isCustomer()) {

        header("Location: index.php");

        exit;

    }

}


/**
 * Require one of several roles.
 *
 * Example:
 *
 * requireRole(['admin', 'vendor']);
 *
 */

function requireRole($roles)
{
    requireLogin();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array(getUserRole(), $roles, true)) {

        header("Location: index.php");

        exit;

    }

}


/*
|--------------------------------------------------------------------------
| Login Session
|--------------------------------------------------------------------------
*/


/**
 * Create login session.
 *
 * This function should be called after the password
 * has already been verified in auth/login_process.php.
 *
 * @param array $user
 */

function createLoginSession($user)
{

    /*
    |--------------------------------------------------------------------------
    | Validate User ID
    |--------------------------------------------------------------------------
    */

    if (
        !isset($user['user_id']) ||
        empty($user['user_id'])
    ) {

        return false;

    }


    /*
    |--------------------------------------------------------------------------
    | Regenerate Session ID
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | Store User Information
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['user_name'] =
        $user['name'] ?? '';

    $_SESSION['user_email'] =
        $user['email'] ?? '';

    $_SESSION['user_role'] =
        $user['role'] ?? 'customer';

    $_SESSION['user_status'] =
        $user['status'] ?? 'active';


    /*
    |--------------------------------------------------------------------------
    | Login Time
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_time'] =
        time();

    $_SESSION['last_activity'] =
        time();


    /*
    |--------------------------------------------------------------------------
    | MFA
    |--------------------------------------------------------------------------
    */

    $_SESSION['mfa_enabled'] =
        !empty($user['mfa_enabled']);

    $_SESSION['mfa_verified'] =
        false;


    /*
    |--------------------------------------------------------------------------
    | Login State
    |--------------------------------------------------------------------------
    */

    $_SESSION['logged_in'] = true;


    return true;

}


/*
|--------------------------------------------------------------------------
| MFA Session
|--------------------------------------------------------------------------
*/


/**
 * Mark MFA as verified.
 */

function markMfaVerified()
{
    $_SESSION['mfa_verified'] = true;
}


/**
 * Check MFA status.
 *
 * @return bool
 */

function isMfaVerified()
{
    return (
        !empty($_SESSION['mfa_verified'])
    );
}


/**
 * Check whether MFA is required.
 */

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
| Logout
|--------------------------------------------------------------------------
*/


/**
 * Destroy current login session.
 */

function logoutUser()
{

    /*
    |--------------------------------------------------------------------------
    | Clear Session Data
    |--------------------------------------------------------------------------
    */

    $_SESSION = [];


    /*
    |--------------------------------------------------------------------------
    | Remove Session Cookie
    |--------------------------------------------------------------------------
    */

    if (ini_get("session.use_cookies")) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"] ?? '',
            $params["secure"],
            $params["httponly"]
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Destroy Session
    |--------------------------------------------------------------------------
    */

    session_destroy();

}


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/


/**
 * Set flash message.
 *
 * @param string $type
 * @param string $message
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


/**
 * Get flash message.
 *
 * Removes message after reading.
 *
 * @return array|null
 */

function getFlashMessage()
{

    if (
        !isset(
            $_SESSION['flash_message']
        )
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
| Redirect After Login
|--------------------------------------------------------------------------
*/


/**
 * Get stored redirect URL.
 *
 * @return string
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

    /*
    |--------------------------------------------------------------------------
    | Default Redirect Based On Role
    |--------------------------------------------------------------------------
    */

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
| Redirect User Based On Role
|--------------------------------------------------------------------------
*/


function redirectByRole()
{

    if (isAdmin()) {

        header(
            "Location: admin/dashboard.php"
        );

        exit;

    }

    if (isVendor()) {

        header(
            "Location: seller/dashboard.php"
        );

        exit;

    }

    header(
        "Location: dashboard.php"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Safe Session Value
|--------------------------------------------------------------------------
*/


/**
 * Get a session value safely.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */

function sessionValue(
    $key,
    $default = null
) {

    return $_SESSION[$key] ?? $default;

}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
|
| Used for forms that modify database information.
|
*/


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


/**
 * Validate CSRF token.
 *
 * @param string $token
 * @return bool
 */

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
        $token
    );

}


/*
|--------------------------------------------------------------------------
| Generate Token Automatically
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['csrf_token'])
) {

    generateCsrfToken();

}

?>