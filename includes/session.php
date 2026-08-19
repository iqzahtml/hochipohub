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
| - MFA session
| - Password reset session
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

    $secure =
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off';

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
    | Start fresh session
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
| GET CURRENT USER ID
|--------------------------------------------------------------------------
*/

if (!function_exists('getCurrentUserId')) {

    function getCurrentUserId()
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
        return
            $_SESSION['user_name']
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
        return
            $_SESSION['user_email']
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
        return
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER ROLE
|--------------------------------------------------------------------------
*/

if (!function_exists('getCurrentUserRole')) {

    function getCurrentUserRole()
    {
        return
            $_SESSION['role']
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
        return
            $_SESSION['status']
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


        /*
        |--------------------------------------------------------------------------
        | Regenerate session ID
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
                    (string) (
                        $user['role']
                        ?? 'customer'
                    )
                )
            );

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
| LOGIN USER
|--------------------------------------------------------------------------
|
| verify_otp.php uses:
|
| loginUser($user)
|
|--------------------------------------------------------------------------
*/

if (!function_exists('loginUser')) {

    function loginUser($user)
    {
        return createLoginSession($user);
    }
}


/*
|--------------------------------------------------------------------------
| MFA SESSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SET MFA PENDING USER
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
            !isset(
                $_SESSION['mfa_pending_user']
            )
        ) {

            return null;
        }


        $userId =
            (int) $_SESSION['mfa_pending_user'];


        if ($userId <= 0) {

            return null;
        }


        return $userId;
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
| CHECK MFA REQUIREMENT
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

        $userId =
            (int) $userId;


        if ($userId <= 0) {

            return false;
        }


        $_SESSION['reset_user_id'] =
            $userId;


        /*
        |--------------------------------------------------------------------------
        | Reset has not been verified yet
        |--------------------------------------------------------------------------
        */

        $_SESSION[
            'password_reset_verified'
        ] = false;


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


        $userId =
            (int) $_SESSION['reset_user_id'];


        if ($userId <= 0) {

            return null;
        }


        return $userId;
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
            $_SESSION['reset_user_id']
        );

        unset(
            $_SESSION['reset_email']
        );

        unset(
            $_SESSION[
                'password_reset_verified'
            ]
        );


        return true;
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


        return true;
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
            isset(
                $_SESSION[
                    'password_reset_verified'
                ]
            )
            &&
            $_SESSION[
                'password_reset_verified'
            ] === true
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


        if (
            ini_get(
                'session.use_cookies'
            )
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


        session_destroy();


        return true;
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

if (!function_exists('setFlashMessage')) {

    function setFlashMessage(
        $type,
        $message
    )
    {

        $_SESSION[
            'flash_message'
        ] = [

            'type' =>
                (string) $type,

            'message' =>
                (string) $message
        ];


        return true;
    }
}


/*
|--------------------------------------------------------------------------
| SAFE FLASH MESSAGE
|--------------------------------------------------------------------------
|
| Used by:
|
| send_otp.php
| verify_otp.php
| reset_password.php
|
|--------------------------------------------------------------------------
*/

if (!function_exists('setFlashMessageSafe')) {

    function setFlashMessageSafe(
        $type,
        $message
    )
    {

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
                $_SESSION[
                    'flash_message'
                ]
            )
        ) {

            return null;
        }


        $message =
            $_SESSION[
                'flash_message'
            ];


        unset(
            $_SESSION[
                'flash_message'
            ]
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
        | Previously requested page
        |--------------------------------------------------------------------------
        */

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

            return
                'admin/dashboard.php';
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

            return
                'seller/dashboard.php';
        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        return
            'dashboard.php';
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
                'Location: ' .
                BASE_URL .
                'admin/dashboard.php'
            );

            exit;
        }


        if ($role === 'vendor') {

            header(
                'Location: ' .
                BASE_URL .
                'seller/dashboard.php'
            );

            exit;
        }


        header(
            'Location: ' .
            BASE_URL .
            'dashboard.php'
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
    )
    {

        return
            $_SESSION[$key]
            ?? $default;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (!function_exists('generateCsrfToken')) {

    function generateCsrfToken()
    {

        if (
            empty(
                $_SESSION[
                    'csrf_token'
                ]
            )
        ) {

            $_SESSION[
                'csrf_token'
            ] =
                bin2hex(
                    random_bytes(32)
                );
        }


        return
            $_SESSION[
                'csrf_token'
            ];
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
| END SESSION
|--------------------------------------------------------------------------
*/

?>