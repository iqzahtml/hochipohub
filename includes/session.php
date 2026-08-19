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
| - Session timeout
| - Login session
| - Password reset session
| - MFA pending session
| - MFA verification
| - Logout
| - Flash messages
| - CSRF helper
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

    /*
    |--------------------------------------------------------------------------
    | START NEW SESSION
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
*/

if (!function_exists('createLoginSession')) {

    function createLoginSession($user)
    {

        /*
        |--------------------------------------------------------------------------
        | VALIDATE USER
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
        | REGENERATE SESSION ID
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);


        /*
        |--------------------------------------------------------------------------
        | USER INFORMATION
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
        | ROLE
        |--------------------------------------------------------------------------
        */

        $_SESSION['role'] =
            strtolower(
                trim(
                    (string)
                    ($user['role'] ?? 'customer')
                )
            );


        /*
        |--------------------------------------------------------------------------
        | OLD ROLE COMPATIBILITY
        |--------------------------------------------------------------------------
        */

        $_SESSION['user_role'] =
            $_SESSION['role'];


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        $_SESSION['status'] =
            $user['status'] ?? 'active';

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
            !empty(
                $user['mfa_enabled']
            );

        $_SESSION['mfa_verified'] =
            false;


        return true;
    }
}


/*
|--------------------------------------------------------------------------
| MFA
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| MARK MFA VERIFIED
|--------------------------------------------------------------------------
*/

if (!function_exists('markMfaVerified')) {

    function markMfaVerified()
    {
        $_SESSION['mfa_verified'] =
            true;

        return true;
    }
}


/*
|--------------------------------------------------------------------------
| CHECK MFA VERIFIED
|--------------------------------------------------------------------------
*/

if (!function_exists('isMfaVerified')) {

    function isMfaVerified()
    {
        return !empty(
            $_SESSION['mfa_verified']
        );
    }
}


/*
|--------------------------------------------------------------------------
| CHECK MFA REQUIRED
|--------------------------------------------------------------------------
*/

if (!function_exists('requiresMfa')) {

    function requiresMfa()
    {

        return (
            isset(
                $_SESSION['user_id']
            )
            &&
            !empty(
                $_SESSION['mfa_enabled']
            )
            &&
            !isMfaVerified()
        );
    }
}


/*
|--------------------------------------------------------------------------
| MFA PENDING USER
|--------------------------------------------------------------------------
|
| Used by login_process.php
| before MFA verification.
|--------------------------------------------------------------------------
*/


if (!function_exists('setMfaPendingUser')) {

    function setMfaPendingUser($userId)
    {

        $_SESSION['mfa_pending_user'] =
            (int) $userId;

        return true;
    }
}


if (!function_exists('getMfaPendingUser')) {

    function getMfaPendingUser()
    {

        if (
            !isset(
                $_SESSION['mfa_pending_user']
            )
        ) {

            return null;
        }

        return (int)
            $_SESSION['mfa_pending_user'];
    }
}


if (!function_exists('clearMfaPendingUser')) {

    function clearMfaPendingUser()
    {

        unset(
            $_SESSION['mfa_pending_user']
        );

        return true;
    }
}


/*
|--------------------------------------------------------------------------
| PASSWORD RESET SESSION
|--------------------------------------------------------------------------
|
| Used by:
|
| forgot_password.php
| send_otp.php
| verify_otp.php
| reset_password.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SET RESET USER
|--------------------------------------------------------------------------
*/

if (!function_exists('setResetUser')) {

    function setResetUser($userId)
    {

        $_SESSION['reset_user_id'] =
            (int) $userId;

        return true;
    }
}


/*
|--------------------------------------------------------------------------
| GET RESET USER
|--------------------------------------------------------------------------
*/

if (!function_exists('getResetUser')) {

    function getResetUser()
    {

        if (
            !isset(
                $_SESSION['reset_user_id']
            )
        ) {

            return null;
        }

        return (int)
            $_SESSION['reset_user_id'];
    }
}


/*
|--------------------------------------------------------------------------
| CLEAR RESET USER
|--------------------------------------------------------------------------
*/

if (!function_exists('clearResetUser')) {

    function clearResetUser()
    {

        unset(
            $_SESSION['reset_user_id'],
            $_SESSION['reset_email'],
            $_SESSION['password_reset_verified']
        );

        return true;
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
        | CLEAR SESSION
        |--------------------------------------------------------------------------
        */

        $_SESSION = [];


        /*
        |--------------------------------------------------------------------------
        | REMOVE SESSION COOKIE
        |--------------------------------------------------------------------------
        */

        if (
            ini_get('session.use_cookies')
        ) {

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
        | DESTROY SESSION
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

        return true;
    }
}


/*
|--------------------------------------------------------------------------
| SAFE FLASH MESSAGE
|--------------------------------------------------------------------------
|
| Some existing pages use:
|
| setFlashMessageSafe()
|
|--------------------------------------------------------------------------
*/

if (!function_exists('setFlashMessageSafe')) {

    function setFlashMessageSafe(
        $type,
        $message
    ) {

        return setFlashMessage(
            $type,
            $message
        );
    }
}


/*
|--------------------------------------------------------------------------
| GET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

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
| LOGIN REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('getLoginRedirect')) {

    function getLoginRedirect()
    {

        /*
        |--------------------------------------------------------------------------
        | PREVIOUSLY REQUESTED PAGE
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
        | ADMIN
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
        | VENDOR
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
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        return 'dashboard.php';
    }
}


/*
|--------------------------------------------------------------------------
| REDIRECT BY ROLE
|--------------------------------------------------------------------------
*/

if (!function_exists('redirectByRole')) {

    function redirectByRole()
    {

        $role =
            $_SESSION['role']
            ??
            $_SESSION['user_role']
            ??
            '';


        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        if ($role === 'admin') {

            header(
                'Location: admin/dashboard.php'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        if ($role === 'vendor') {

            header(
                'Location: seller/dashboard.php'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

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
| CSRF TOKEN
|--------------------------------------------------------------------------
|
| config.php normally provides:
|
| csrfToken()
| verifyCsrfToken()
|
| We only provide generateCsrfToken()
| if it doesn't already exist.
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