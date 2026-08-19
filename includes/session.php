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
| - Manage password reset session
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
        | ROLE
        |--------------------------------------------------------------------------
        */

        $_SESSION['role'] = strtolower(
            trim(
                (string) (
                    $user['role']
                    ?? 'customer'
                )
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Compatibility
        |--------------------------------------------------------------------------
        */

        $_SESSION['user_role'] =
            $_SESSION['role'];


        /*
        |--------------------------------------------------------------------------
        | USER STATUS
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
            !empty($user['mfa_enabled']);

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
| SET MFA PENDING USER
|--------------------------------------------------------------------------
|
| Used when login requires OTP verification.
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


/*
|--------------------------------------------------------------------------
| GET MFA PENDING USER
|--------------------------------------------------------------------------
*/

if (!function_exists('getMfaPendingUser')) {

    function getMfaPendingUser()
    {

        if (
            empty(
                $_SESSION['mfa_pending_user']
            )
        ) {

            return null;
        }


        return (int)
            $_SESSION['mfa_pending_user'];
    }
}


/*
|--------------------------------------------------------------------------
| CLEAR MFA PENDING USER
|--------------------------------------------------------------------------
*/

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
| MARK MFA VERIFIED
|--------------------------------------------------------------------------
*/

if (!function_exists('markMfaVerified')) {

    function markMfaVerified()
    {
        $_SESSION['mfa_verified'] =
            true;
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
| CHECK MFA REQUIREMENT
|--------------------------------------------------------------------------
*/

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
| PASSWORD RESET SESSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SET RESET USER
|--------------------------------------------------------------------------
|
| Stores the user ID temporarily during
| the password reset process.
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
            empty(
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
|
| Called after password has been successfully
| changed or reset flow expires.
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
| SAFE FLASH MESSAGE
|--------------------------------------------------------------------------
|
| Some authentication files use:
|
| setFlashMessageSafe()
|
| Keep this as a compatibility wrapper.
|--------------------------------------------------------------------------
*/

if (!function_exists('setFlashMessageSafe')) {

    function setFlashMessageSafe(
        $type,
        $message
    ) {

        setFlashMessage(
            $type,
            $message
        );
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
| We only provide generateCsrfToken()
| for compatibility with older pages.
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