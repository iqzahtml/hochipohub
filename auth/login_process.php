<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
| File:
| auth/login_process.php
|
| Purpose:
| - Process user login
| - Verify email and password
| - Check account status
| - Create secure login session
| - Redirect user according to role
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$email = trim(
    $_POST['email'] ?? ''
);

$password =
    $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATE INPUT
|--------------------------------------------------------------------------
*/

if (
    $email === '' ||
    $password === ''
) {

    $_SESSION['login_error'] =
        'Email and password are required.';

    redirect(
        BASE_URL . 'index.php?login=1'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $pdo = getDB();


    /*
    |--------------------------------------------------------------------------
    | FIND USER
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            user_id,
            name,
            email,
            password,
            role,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | USER NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $_SESSION['login_error'] =
            'Invalid email or password.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY PASSWORD
    |--------------------------------------------------------------------------
    */

    if (
        !password_verify(
            $password,
            $user['password']
        )
    ) {

        $_SESSION['login_error'] =
            'Invalid email or password.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    $status = strtolower(
        trim(
            (string) (
                $user['status']
                ?? 'active'
            )
        )
    );

    if ($status !== 'active') {

        $_SESSION['login_error'] =
            'Your account is not active.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE ROLE
    |--------------------------------------------------------------------------
    */

    $role = strtolower(
        trim(
            (string) (
                $user['role']
                ?? ''
            )
        )
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE ROLE
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $role,
            [
                'admin',
                'vendor',
                'customer'
            ],
            true
        )
    ) {

        $_SESSION['login_error'] =
            'Invalid user role.';

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REGENERATE SESSION ID
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | CREATE LOGIN SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['user_name'] =
        $user['name'];

    $_SESSION['user_email'] =
        $user['email'];

    /*
    |--------------------------------------------------------------------------
    | COMPATIBILITY SESSION VALUES
    |--------------------------------------------------------------------------
    */

    $_SESSION['name'] =
        $user['name'];

    $_SESSION['email'] =
        $user['email'];


    /*
    |--------------------------------------------------------------------------
    | ROLE
    |--------------------------------------------------------------------------
    */

    $_SESSION['role'] =
        $role;

    $_SESSION['user_role'] =
        $role;


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    $_SESSION['status'] =
        $status;

    $_SESSION['user_status'] =
        $status;


    /*
    |--------------------------------------------------------------------------
    | LOGIN INFORMATION
    |--------------------------------------------------------------------------
    */

    $_SESSION['logged_in'] =
        true;

    $_SESSION['login_time'] =
        time();

    $_SESSION['last_activity'] =
        time();


    /*
    |--------------------------------------------------------------------------
    | CLEAR LOGIN ERRORS
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['login_error'],
        $_SESSION['error'],
        $_SESSION['login_email']
    );


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BY ROLE
    |--------------------------------------------------------------------------
    */

    if ($role === 'admin') {

        redirect(
            BASE_URL . 'admin/dashboard.php'
        );
    }


    if ($role === 'vendor') {

        redirect(
            BASE_URL . 'seller/dashboard.php'
        );
    }


    if ($role === 'customer') {

        redirect(
            BASE_URL . 'dashboard.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Unable to determine your account role.';

    redirect(
        BASE_URL . 'index.php?login=1'
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | LOG REAL ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'Hochipohub Login Error: '
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | USER-FRIENDLY ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Unable to login right now. Please try again.';

    redirect(
        BASE_URL . 'index.php?login=1'
    );
}