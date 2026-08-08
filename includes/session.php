<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Session & Authentication
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
|
| config.php already starts the session.
| This check prevents session_start() from
| being called twice.
|
*/

if (session_status() === PHP_SESSION_NONE) {

    session_name(
        SESSION_NAME
    );

    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
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

function currentUserId(): ?int
{
    if (!isLoggedIn()) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}


/*
|--------------------------------------------------------------------------
| CURRENT USER NAME
|--------------------------------------------------------------------------
*/

function currentUserName(): string
{
    return $_SESSION['user_name']
        ?? '';
}


/*
|--------------------------------------------------------------------------
| CURRENT USER EMAIL
|--------------------------------------------------------------------------
*/

function currentUserEmail(): string
{
    return $_SESSION['user_email']
        ?? '';
}


/*
|--------------------------------------------------------------------------
| CURRENT USER ROLE
|--------------------------------------------------------------------------
*/

function currentUserRole(): ?string
{
    return $_SESSION['user_role']
        ?? null;
}


/*
|--------------------------------------------------------------------------
| LOGIN USER
|--------------------------------------------------------------------------
|
| Called after successful authentication.
|
*/

function loginUser(
    array $user
): void {

    session_regenerate_id(
        true
    );


    $_SESSION['user_id'] =
        (int) $user['user_id'];


    $_SESSION['user_name'] =
        $user['name'];


    $_SESSION['user_email'] =
        $user['email'];


    $_SESSION['user_role'] =
        $user['role'];


    $_SESSION['user_status'] =
        $user['status'];


    $_SESSION['logged_in'] =
        true;


    $_SESSION['login_time'] =
        time();
}


/*
|--------------------------------------------------------------------------
| LOGOUT USER
|--------------------------------------------------------------------------
*/

function logoutUser(): void
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
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }


    session_destroy();
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (!isLoggedIn()) {

        setFlashMessageSafe(
            'warning',
            'Please login to continue.'
        );


        header(
            'Location: ' .
            BASE_URL .
            'index.php'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE GUEST
|--------------------------------------------------------------------------
*/

function requireGuest(): void
{
    if (isLoggedIn()) {

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
| REQUIRE ROLE
|--------------------------------------------------------------------------
*/

function requireRole(
    string|array $roles
): void {

    requireLogin();


    $allowedRoles =
        is_array($roles)
        ? $roles
        : [$roles];


    $currentRole =
        currentUserRole();


    if (
        !in_array(
            $currentRole,
            $allowedRoles,
            true
        )
    ) {

        setFlashMessageSafe(
            'error',
            'You do not have permission to access this page.'
        );


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
| REQUIRE CUSTOMER
|--------------------------------------------------------------------------
*/

function requireCustomer(): void
{
    requireRole(
        'customer'
    );
}


/*
|--------------------------------------------------------------------------
| REQUIRE VENDOR
|--------------------------------------------------------------------------
*/

function requireVendor(): void
{
    requireRole(
        'vendor'
    );
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
*/

function requireAdmin(): void
{
    requireRole(
        'admin'
    );
}


/*
|--------------------------------------------------------------------------
| SAFE FLASH MESSAGE
|--------------------------------------------------------------------------
|
| functions.php may not be loaded yet when
| session.php is used by itself.
|
*/

function setFlashMessageSafe(
    string $type,
    string $message
): void {

    $_SESSION['flash'] = [

        'type' =>
            $type,

        'message' =>
            $message

    ];
}


/*
|--------------------------------------------------------------------------
| MFA
|--------------------------------------------------------------------------
*/

function isMfaVerified(): bool
{
    return isset(
        $_SESSION['mfa_verified']
    )
    &&
    $_SESSION['mfa_verified'] === true;
}


function markMfaVerified(): void
{
    $_SESSION['mfa_verified'] =
        true;
}


function clearMfaVerification(): void
{
    unset(
        $_SESSION['mfa_verified']
    );
}


/*
|--------------------------------------------------------------------------
| MFA PENDING USER
|--------------------------------------------------------------------------
*/

function setMfaPendingUser(
    int $userId
): void {

    $_SESSION['mfa_pending_user_id'] =
        $userId;
}


function getMfaPendingUser(): ?int
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


function clearMfaPendingUser(): void
{
    unset(
        $_SESSION[
            'mfa_pending_user_id'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| PASSWORD RESET USER
|--------------------------------------------------------------------------
*/

function setResetUser(
    int $userId
): void {

    $_SESSION[
        'reset_user_id'
    ] = $userId;
}


function getResetUser(): ?int
{
    if (
        empty(
            $_SESSION[
                'reset_user_id'
            ]
        )
    ) {

        return null;
    }


    return (int)
        $_SESSION[
            'reset_user_id'
        ];
}


function clearResetUser(): void
{
    unset(
        $_SESSION[
            'reset_user_id'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/

function checkSessionTimeout(
    int $timeout = 7200
): void {

    if (
        !isLoggedIn()
    ) {

        return;
    }


    if (
        !isset(
            $_SESSION['login_time']
        )
    ) {

        $_SESSION['login_time'] =
            time();

        return;
    }


    if (
        time()
        -
        $_SESSION['login_time']
        >
        $timeout
    ) {

        logoutUser();


        header(
            'Location: ' .
            BASE_URL .
            'index.php'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE SESSION ACTIVITY
|--------------------------------------------------------------------------
*/

function updateSessionActivity(): void
{
    if (
        isLoggedIn()
    ) {

        $_SESSION[
            'last_activity'
        ] = time();
    }
}


/*
|--------------------------------------------------------------------------
| CHECK ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

function isAccountActive(): bool
{
    return (
        isset(
            $_SESSION['user_status']
        )
        &&
        $_SESSION['user_status']
            === 'active'
    );
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK HELPERS
|--------------------------------------------------------------------------
*/

function isCustomer(): bool
{
    return (
        isLoggedIn()
        &&
        currentUserRole()
            === 'customer'
    );
}


function isVendor(): bool
{
    return (
        isLoggedIn()
        &&
        currentUserRole()
            === 'vendor'
    );
}


function isAdmin(): bool
{
    return (
        isLoggedIn()
        &&
        currentUserRole()
            === 'admin'
    );
}


/*
|--------------------------------------------------------------------------
| INITIAL SESSION ACTIVITY
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {

    updateSessionActivity();

}