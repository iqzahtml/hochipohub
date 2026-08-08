<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Session Management
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'])
        && !empty($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| Current User ID
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
| Current User Name
|--------------------------------------------------------------------------
*/

function currentUserName(): string
{
    return $_SESSION['user_name'] ?? '';
}


/*
|--------------------------------------------------------------------------
| Current User Role
|--------------------------------------------------------------------------
*/

function currentUserRole(): string
{
    return $_SESSION['user_role'] ?? 'customer';
}


/*
|--------------------------------------------------------------------------
| Login User
|--------------------------------------------------------------------------
*/

function loginUser(
    int $userId,
    string $name,
    string $role
): void {

    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;

    $_SESSION['user_name'] = $name;

    $_SESSION['user_role'] = $role;

}


/*
|--------------------------------------------------------------------------
| Logout User
|--------------------------------------------------------------------------
*/

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (!isLoggedIn()) {

        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Please login to continue.'
        ];

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL
                : '/'
            )
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Role
|--------------------------------------------------------------------------
*/

function requireRole(string $role): void
{
    requireLogin();

    if (currentUserRole() !== $role) {

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'You do not have permission to access this page.'
        ];

        header(
            'Location: ' .
            (defined('BASE_URL')
                ? BASE_URL . 'index.php'
                : '/'
            )
        );

        exit;
    }
}