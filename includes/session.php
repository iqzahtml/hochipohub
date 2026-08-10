<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SESSION & AUTHENTICATION
|--------------------------------------------------------------------------
| File:
| includes/session.php
|
| Purpose:
| Central session management, authentication,
| role checking, flash messages and OTP/reset state.
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_name(SESSION_NAME);

    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
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
    return $_SESSION['name'] ?? '';
}


/*
|--------------------------------------------------------------------------
| CURRENT USER EMAIL
|--------------------------------------------------------------------------
*/

function currentUserEmail(): string
{
    return $_SESSION['email'] ?? '';
}


/*
|--------------------------------------------------------------------------
| CURRENT USER PHONE
|--------------------------------------------------------------------------
*/

function currentUserPhone(): string
{
    return $_SESSION['phone'] ?? '';
}


/*
|--------------------------------------------------------------------------
| CURRENT USER ROLE
|--------------------------------------------------------------------------
*/

function currentUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}


/*
|--------------------------------------------------------------------------
| CURRENT USER PROFILE IMAGE
|--------------------------------------------------------------------------
*/

function currentUserProfileImage(): string
{
    return $_SESSION['profile_image'] ?? '';
}


/*
|--------------------------------------------------------------------------
| LOGIN USER
|--------------------------------------------------------------------------
|
| Expected user array:
|
| user_id
| name
| email
| phone
| role
| profile_image
|
|--------------------------------------------------------------------------
*/

function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] =
        (int) ($user['user_id'] ?? 0);

    $_SESSION['name'] =
        $user['name'] ?? '';

    $_SESSION['email'] =
        $user['email'] ?? '';

    $_SESSION['phone'] =
        $user['phone'] ?? '';

    $_SESSION['role'] =
        $user['role'] ?? 'customer';

    $_SESSION['profile_image'] =
        $user['profile_image'] ?? '';

    $_SESSION['logged_in'] =
        true;

    $_SESSION['login_time'] =
        time();

    $_SESSION['last_activity'] =
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
        ini_get('session.use_cookies')
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

        setFlashMessage(
            'warning',
            'Please login to continue.'
        );

        redirect(
            BASE_URL . 'index.php'
        );
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

        redirect(
            BASE_URL . 'dashboard.php'
        );
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

        setFlashMessage(
            'error',
            'You do not have permission to access this page.'
        );

        redirect(
            BASE_URL . 'dashboard.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER
|--------------------------------------------------------------------------
*/

function requireCustomer(): void
{
    requireRole('customer');
}


/*
|--------------------------------------------------------------------------
| REQUIRE VENDOR
|--------------------------------------------------------------------------
*/

function requireVendor(): void
{
    requireRole('vendor');
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
*/

function requireAdmin(): void
{
    requireRole('admin');
}


/*
|--------------------------------------------------------------------------
| ROLE HELPERS
|--------------------------------------------------------------------------
*/

function isCustomer(): bool
{
    return (
        isLoggedIn()
        &&
        currentUserRole() === 'customer'
    );
}


function isVendor(): bool
{
    return (
        isLoggedIn()
        &&
        currentUserRole() === 'vendor'
    );
}


function isAdmin(): bool
{
    return (
        isLoggedIn()
        &&
        currentUserRole() === 'admin'
    );
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

function setFlashMessage(
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
| GET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

function getFlashMessage(): ?array
{
    if (
        empty($_SESSION['flash'])
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
| MFA / OTP VERIFIED
|--------------------------------------------------------------------------
*/

function isMfaVerified(): bool
{
    return (
        !empty(
            $_SESSION['mfa_verified']
        )
    );
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

    $_SESSION[
        'mfa_pending_user_id'
    ] = $userId;
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

    return (int) $_SESSION[
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

    return (int) $_SESSION[
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

    if (!isLoggedIn()) {
        return;
    }


    if (
        empty(
            $_SESSION['last_activity']
        )
    ) {

        $_SESSION['last_activity'] =
            time();

        return;
    }


    if (
        time()
        -
        (int) $_SESSION['last_activity']
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
    if (isLoggedIn()) {

        $_SESSION['last_activity'] =
            time();
    }
}


/*
|--------------------------------------------------------------------------
| INITIAL SESSION ACTIVITY
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {

    updateSessionActivity();
}