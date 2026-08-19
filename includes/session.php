<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SESSION MANAGEMENT
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
| UPDATE ACTIVITY
|--------------------------------------------------------------------------
*/

$_SESSION['last_activity'] = time();


/*
|--------------------------------------------------------------------------
| USER HELPERS
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
| LOGIN SESSION
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

        $_SESSION['logged_in'] = true;

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
| LOGIN REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('getLoginRedirect')) {

    function getLoginRedirect()
    {

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


        if (
            ($_SESSION['role'] ?? '') === 'admin'
        ) {

            return 'admin/dashboard.php';
        }


        if (
            ($_SESSION['role'] ?? '') === 'vendor'
        ) {

            return 'seller/dashboard.php';
        }


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
| SESSION VALUE
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
| CSRF
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


if (
    empty(
        $_SESSION['csrf_token']
    )
) {

    generateCsrfToken();
}


/*
|--------------------------------------------------------------------------
| FLASH COMPATIBILITY
|--------------------------------------------------------------------------
*/

if (!function_exists('setFlashMessageSafe')) {

    function setFlashMessageSafe(
        $type,
        $message
    ) {

        if (function_exists('setFlashMessage')) {

            setFlashMessage(
                $type,
                $message
            );

            return;
        }

        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

?>