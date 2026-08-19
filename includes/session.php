<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SESSION MANAGEMENT
|--------------------------------------------------------------------------
| File:
| includes/session.php
|
| Purpose:
| - Start PHP session
| - Session timeout
| - Login session
| - MFA session
| - Password reset session
| - Logout
| - Flash messages
| - CSRF compatibility
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


if (!function_exists('getUserId')) {

    function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}


if (!function_exists('getUserName')) {

    function getUserName()
    {
        return $_SESSION['user_name']
            ?? $_SESSION['name']
            ?? '';
    }
}


if (!function_exists('getUserEmail')) {

    function getUserEmail()
    {
        return $_SESSION['user_email']
            ?? $_SESSION['email']
            ?? '';
    }
}


if (!function_exists('getUserRole')) {

    function getUserRole()
    {
        return $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? '';
    }
}


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
| CREATE LOGIN SESSION
|--------------------------------------------------------------------------
*/

if (!function_exists('createLoginSession')) {

    function createLoginSession($user)
    {

        if (
            !isset($user['user_id']) ||
            empty($user['user_id'])
        ) {
            return false;
        }


        session_regenerate_id(true);


        $_SESSION['user_id'] =
            (int) $user['user_id'];

        $_SESSION['user_name'] =
            $user['name'] ?? '';

        $_SESSION['user_email'] =
            $user['email'] ?? '';


        $_SESSION['role'] =
            strtolower(
                trim(
                    (string) (
                        $user['role']
                        ?? 'customer'
                    )
                )
            );


        $_SESSION['user_role'] =
            $_SESSION['role'];


        $_SESSION['status'] =
            $user['status'] ?? 'active';

        $_SESSION['user_status'] =
            $_SESSION['status'];


        $_SESSION['login_time'] =
            time();

        $_SESSION['last_activity'] =
            time();

        $_SESSION['logged_in'] =
            true;


        $_SESSION['mfa_enabled'] =
            !empty($user['mfa_enabled']);

        $_SESSION['mfa_verified'] =
            false;


        return true;
    }
}


/*
|--------------------------------------------------------------------------
| MFA SESSION
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
| PASSWORD RESET SESSION
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

        $_SESSION['password_reset_started'] =
            true;

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
*/

if (!function_exists('clearResetUser')) {

    function clearResetUser()
    {
        unset(
            $_SESSION['reset_user_id'],
            $_SESSION['password_reset_started'],
            $_SESSION['password_reset_verified']
        );
    }
}


/*
|--------------------------------------------------------------------------
| MARK PASSWORD RESET VERIFIED
|--------------------------------------------------------------------------
*/

if (!function_exists('markPasswordResetVerified')) {

    function markPasswordResetVerified()
    {
        $_SESSION[
            'password_reset_verified'
        ] = true;
    }
}


/*
|--------------------------------------------------------------------------
| CHECK PASSWORD RESET VERIFIED
|--------------------------------------------------------------------------
*/

if (!function_exists('isPasswordResetVerified')) {

    function isPasswordResetVerified()
    {
        return (
            !empty(
                $_SESSION[
                    'password_reset_verified'
                ]
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| MFA PENDING USER
|--------------------------------------------------------------------------
*/


if (!function_exists('setMfaPendingUser')) {

    function setMfaPendingUser($userId)
    {
        $_SESSION['mfa_pending_user_id'] =
            (int) $userId;

        return true;
    }
}


if (!function_exists('getMfaPendingUser')) {

    function getMfaPendingUser()
    {
        if (
            empty(
                $_SESSION[
                    'mfa_pending_user_id'
                ]
            )
        ) {
            return null;
        }

        return (int)
            $_SESSION[
                'mfa_pending_user_id'
            ];
    }
}


if (!function_exists('clearMfaPendingUser')) {

    function clearMfaPendingUser()
    {
        unset(
            $_SESSION[
                'mfa_pending_user_id'
            ]
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
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
|
| Supports BOTH:
|
| setFlashMessage()
| getFlashMessage()
|
| and compatibility:
|
| setFlashMessageSafe()
|
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
| SAFE FLASH MESSAGE
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
| FLASH COMPATIBILITY WITH config.php
|--------------------------------------------------------------------------
*/


if (!function_exists('setFlash')) {

    function setFlash(
        $type,
        $message
    ) {

        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}


if (!function_exists('getFlash')) {

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
}


/*
|--------------------------------------------------------------------------
| LOGIN REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('getLoginRedirect')) {

    function getLoginRedirect()
    {

        if (
            isset(
                $_SESSION[
                    'redirect_after_login'
                ]
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


        $role =
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? '';


        if ($role === 'admin') {

            return
                BASE_URL .
                'admin/dashboard.php';
        }


        if ($role === 'vendor') {

            return
                BASE_URL .
                'seller/dashboard.php';
        }


        return
            BASE_URL .
            'dashboard.php';
    }
}


/*
|--------------------------------------------------------------------------
| REDIRECT USER BY ROLE
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

            redirect(
                BASE_URL .
                'admin/dashboard.php'
            );
        }


        if ($role === 'vendor') {

            redirect(
                BASE_URL .
                'seller/dashboard.php'
            );
        }


        redirect(
            BASE_URL .
            'dashboard.php'
        );
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


        return
            $_SESSION['csrf_token'];
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


/*
|--------------------------------------------------------------------------
| END SESSION FILE
|--------------------------------------------------------------------------
*/

?>