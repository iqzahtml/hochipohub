<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGOUT
|--------------------------------------------------------------------------
| File:
| auth/logout.php
|
| Purpose:
| Destroy the current user session and
| return the user to the marketplace.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| LOGOUT USER
|--------------------------------------------------------------------------
*/

logoutUser();


/*
|--------------------------------------------------------------------------
| START NEW SESSION
|--------------------------------------------------------------------------
|
| logoutUser() destroys the old session.
| We start a fresh session so a logout message
| can be stored safely.
|
*/

if (
    session_status() === PHP_SESSION_NONE
) {

    session_name(
        SESSION_NAME
    );

    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGOUT MESSAGE
|--------------------------------------------------------------------------
*/

$_SESSION['flash'] = [

    'type' =>
        'success',

    'message' =>
        'You have been logged out successfully.'

];


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(
    'Location: ' .
    BASE_URL .
    'index.php'
);

exit;