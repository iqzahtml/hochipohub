<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Session Management
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Session Timeout
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['last_activity'])) {

    $inactiveTime =
        time() - $_SESSION['last_activity'];

    if ($inactiveTime > SESSION_TIMEOUT) {

        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);
        unset($_SESSION['mfa_verified']);

        $_SESSION['session_expired'] = true;
    }
}


/*
|--------------------------------------------------------------------------
| Update Last Activity
|--------------------------------------------------------------------------
*/

$_SESSION['last_activity'] = time();


/*
|--------------------------------------------------------------------------
| Login Check
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| MFA Check
|--------------------------------------------------------------------------
*/

function isMFAVerified()
{
    return isset($_SESSION['mfa_verified'])
        && $_SESSION['mfa_verified'] === true;
}


/*
|--------------------------------------------------------------------------
| Current User ID
|--------------------------------------------------------------------------
*/

function currentUserId()
{
    return $_SESSION['user_id'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Current User Name
|--------------------------------------------------------------------------
*/

function currentUserName()
{
    return $_SESSION['user_name'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Current User Email
|--------------------------------------------------------------------------
*/

function currentUserEmail()
{
    return $_SESSION['user_email'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Current User Role
|--------------------------------------------------------------------------
*/

function currentUserRole()
{
    return $_SESSION['user_role'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Check User Role
|--------------------------------------------------------------------------
*/

function hasRole($role)
{
    return isLoggedIn()
        && currentUserRole() === $role;
}


/*
|--------------------------------------------------------------------------
| Customer Check
|--------------------------------------------------------------------------
*/

function isCustomer()
{
    return hasRole('customer');
}


/*
|--------------------------------------------------------------------------
| Vendor Check
|--------------------------------------------------------------------------
*/

function isVendor()
{
    return hasRole('vendor');
}


/*
|--------------------------------------------------------------------------
| Admin Check
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return hasRole('admin');
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isLoggedIn()) {

        header(
            'Location: ' .BASE_URL .
            'index.php?login=required'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Customer
|--------------------------------------------------------------------------
*/

function requireCustomer()
{
    requireLogin();

    if (!isCustomer()) {

        header(
            'Location: ' .
            BASE_URL .
            'index.php?error=unauthorized'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Vendor
|--------------------------------------------------------------------------
*/

function requireVendor()
{
    requireLogin();

    if (!isVendor()) {

        header(
            'Location: ' .
            BASE_URL .
            'index.php?error=unauthorized'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Admin
|--------------------------------------------------------------------------
*/

function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {

        header(
            'Location: ' .
            BASE_URL .
            'index.php?error=unauthorized'
        );

        exit;
    }
}