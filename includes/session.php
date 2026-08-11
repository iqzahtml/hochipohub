<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SESSION MANAGEMENT
|--------------------------------------------------------------------------
| File:
|     includes/session.php
|
| Purpose:
| - Start PHP session
| - Manage session timeout
| - Store login session
| - Manage MFA session
| - Logout
| - Flash message
| - CSRF helper
|
| IMPORTANT:
| Authentication functions such as:
|     isLoggedIn()
|     hasRole()
|     isAdmin()
|     isVendor()
|     isCustomer()
|     requireLogin()
|     requireAdmin()
|     requireVendor()
|     requireCustomer()
|
| are already defined in config.php.
|
| Therefore this file DOES NOT redeclare them.
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
|
| Automatically expire inactive sessions after 2 hours.
|--------------------------------------------------------------------------
*/

$sessionTimeout = 7200;

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $sessionTimeout
) {

    /*
    |--------------------------------------------------------------------------
    | Destroy expired session
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Start new session
    |--------------------------------------------------------------------------
    */

    session_start();
}


/*
|--------------------------------------------------------------------------
| UPDATE LAST ACTIVITY
|--------------------------------------------------------------------------
*/

$_SESSION['last_activity'] = time();


/*
|--------------------------------------------------------------------------
| USER SESSION HELPERS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserId')) {

    function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}


/*
|--------------------------------------------------------------------------
| GET USER NAME
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserName')) {

    function getUserName()
    {
        return $_SESSION['user_name']
            ?? $_SESSION['name']
            ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| GET USER EMAIL
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserEmail')) {

    function getUserEmail()
    {
        return $_SESSION['user_email']
            ?? $_SESSION['email']
            ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| GET USER ROLE
|--------------------------------------------------------------------------
|
| config.php uses:
|
| $_SESSION['role']
|
| Older session code may use:
|
| $_SESSION['user_role']
|
| We support BOTH.
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserRole')) {

    function getUserRole()
    {
        return $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| GET USER STATUS
|--------------------------------------------------------------------------
*/

if (!function_exists('getUserStatus')) {

    function getUserStatus()
    {
        return $_SESSION['status']
            ?? $_SESSION['user_status']
            ?? 'active';
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN SESSION
|--------------------------------------------------------------------------
|
| Called after successful login.
|--------------------------------------------------------------------------
*/

if (!function_exists('createLoginSession')) {

    function createLoginSession($user)
    {

        /*
        |--------------------------------------------------------------------------
        | Validate user
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
        | Regenerate session ID
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);


        /*
        |--------------------------------------------------------------------------
        | Store user information
        |--------------------------------------------------------------------------
        */

        $_SESSION['user_id'] =
            (int) $user['user_id'];

        $_SESSION['user_name'] =
            $user['name'] ?? '';

        $_SESSION['user_email'] =
            $user['email'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | config.php checks $_SESSION['role'].
        |
        | Normalize the role value to avoid case mismatch.
        |--------------------------------------------------------------------------
        */

        $_SESSION['role'] = strtolower(
            trim(
                (string) ($user['role'] ?? 'customer')
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Keep compatibility with old code
        |--------------------------------------------------------------------------
        */

        $_SESSION['user_role'] =
            $_SESSION['role'];


        /*
        |--------------------------------------------------------------------------
        | User status
        |--------------------------------------------------------------------------
        */

        $_SESSION['status'] =
            $user['status'] ?? 'active';

        $_SESSION['user_status'] =
            $_SESSION['status'];


        /*
        |--------------------------------------------------------------------------
        | Login information
        |--------------------------------------------------------------------------
        */

        $_SESSION['login_time'] =
            time();

        $_SESSION['last_activity'] =
            time();

        $_SESSION['logged_in'] = true;


        /*
        |--------------------------------------------------------------------------
        | MFA
        |--------------------------------------------------------------------------
        */

        $_SESSION['mfa_enabled'] =
            !empty($user['mfa_enabled']);

        $_SESSION['mfa_verified'] = false;


        return true;
    }
}


/*
|--------------------------------------------------------------------------
| MFA
|--------------------------------------------------------------------------
*/


if (!function_exists('markMfaVerified')) {

    function markMfaVerified()
    {
        $_SESSION['mfa_verified'] = true;
    }
}


if (!function_exists('isMfaVerified')) {

    function isMfaVerified()
    {
        return !empty(
            $_SESSION['mfa_verified']
        );
    }
}


if (!function_exists('requiresMfa')) {

    function requiresMfa()
    {

        return (
            isset($_SESSION['user_id']) &&
            !empty($_SESSION['mfa_enabled']) &&
            !isMfaVerified()
        );
    }
}


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

if (!function_exists('logoutUser')) {

    function logoutUser()
    {

        /*
        |--------------------------------------------------------------------------
        | Clear session
        |--------------------------------------------------------------------------
        */

        $_SESSION = [];


        /*
        |--------------------------------------------------------------------------
        | Remove session cookie
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Destroy session
        |--------------------------------------------------------------------------
        */

        session_destroy();
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('setFlashMessage')) {

    function setFlashMessage(
        $type,
        $message
    ) {

        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}


if (!function_exists('getFlashMessage')) {

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
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE COMPATIBILITY
|--------------------------------------------------------------------------
|
| Some pages may use setFlash() / getFlash()
| from config.php.
|
| These functions are already provided there.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOGIN REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('getLoginRedirect')) {

    function getLoginRedirect()
    {

        /*
        |--------------------------------------------------------------------------
        | Previously requested page
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $_SESSION['redirect_after_login']
            )
        ) {

            $redirect =
                $_SESSION[
                    'redirect_after_login'
                ];

            unset(
                $_SESSION[
                    'redirect_after_login'
                ]
            );

            return $redirect;
        }


        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        if (
            (
                $_SESSION['role']
                ?? ''
            ) === 'admin'
        ) {

            return 'admin/dashboard.php';
        }


        /*
        |--------------------------------------------------------------------------
        | Vendor
        |--------------------------------------------------------------------------
        */

        if (
            (
                $_SESSION['role']
                ?? ''
            ) === 'vendor'
        ) {

            return 'seller/dashboard.php';
        }


        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        return 'dashboard.php';
    }
}


/*
|--------------------------------------------------------------------------
| REDIRECT USER BASED ON ROLE
|--------------------------------------------------------------------------
*/

if (!function_exists('redirectByRole')) {

    function redirectByRole()
    {

        $role =
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? '';


        if ($role === 'admin') {

            header(
                'Location: admin/dashboard.php'
            );

            exit;
        }


        if ($role === 'vendor') {

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
}


/*
|--------------------------------------------------------------------------
| SAFE SESSION VALUE
|--------------------------------------------------------------------------
*/

if (!function_exists('sessionValue')) {

    function sessionValue(
        $key,
        $default = null
    ) {

        return $_SESSION[$key]
            ?? $default;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF COMPATIBILITY
|--------------------------------------------------------------------------
|
| config.php already provides:
|
| csrfToken()
| verifyCsrfToken()
|
| So we DON'T redeclare verifyCsrfToken().
|
| We only provide generateCsrfToken() for compatibility
| with older pages.
|--------------------------------------------------------------------------
*/

if (!function_exists('generateCsrfToken')) {

    function generateCsrfToken()
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
}


/*
|--------------------------------------------------------------------------
| MAKE SURE CSRF TOKEN EXISTS
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['csrf_token']
    )
) {

    generateCsrfToken();
}

?>