<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
| File:
|     auth/login_process.php
|
| Purpose:
| - Process customer/vendor/admin login
| - Create login session
| - Store correct user role
| - Redirect user based on role
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| ONLY POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect(
        BASE_URL . 'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| FORM DATA
|--------------------------------------------------------------------------
*/

$email =
    trim(
        $_POST['email'] ?? ''
    );

$password =
    $_POST['password'] ?? '';

$remember =
    isset($_POST['remember']) &&
    $_POST['remember'] === '1';


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL
|--------------------------------------------------------------------------
*/

if (
    $email === '' ||
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    $_SESSION['login_error'] =
        'Please enter a valid email address.';

    $_SESSION['login_email'] =
        $email;

    redirect(
        BASE_URL . 'index.php?login=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if ($password === '') {

    $_SESSION['login_error'] =
        'Please enter your password.';

    $_SESSION['login_email'] =
        $email;

    redirect(
        BASE_URL . 'index.php?login=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $db = getDB();


    /*
    |--------------------------------------------------------------------------
    | FIND USER
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT
            user_id,
            name,
            email,
            phone,
            password,
            profile_image,
            role,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");


    $stmt->execute([
        $email
    ]);


    $user =
        $stmt->fetch(PDO::FETCH_ASSOC);


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

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD CHECK
    |--------------------------------------------------------------------------
    */

    if (
        empty($user['password']) ||
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

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    $status =
        strtolower(
            trim(
                (string) (
                    $user['status'] ?? 'active'
                )
            )
        );


    if (
        in_array(
            $status,
            [
                'inactive',
                'blocked',
                'suspended',
                'disabled'
            ],
            true
        )
    ) {

        $_SESSION['login_error'] =
            'Your account is currently inactive.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ROLE
    |--------------------------------------------------------------------------
    */

    $role =
        strtolower(
            trim(
                (string) (
                    $user['role'] ?? ''
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | ALLOWED ROLES
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        'customer',
        'vendor',
        'admin'
    ];


    if (
        !in_array(
            $role,
            $allowedRoles,
            true
        )
    ) {

        $_SESSION['login_error'] =
            'Invalid account role.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE LOGIN SESSION
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Vendor dashboard checks:
    |
    | $_SESSION['role'] === 'vendor'
    |
    | Therefore we MUST store role here.
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | BASIC USER SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['logged_in'] =
        true;


    $_SESSION['user_id'] =
        (int) $user['user_id'];


    $_SESSION['user_name'] =
        $user['name'] ?? '';


    $_SESSION['user_email'] =
        $user['email'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | IMPORTANT ROLE SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['role'] =
        $role;


    /*
    |--------------------------------------------------------------------------
    | COMPATIBILITY WITH OLD CODE
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_role'] =
        $role;


    /*
    |--------------------------------------------------------------------------
    | USER STATUS
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

    $_SESSION['login_time'] =
        time();


    $_SESSION['last_activity'] =
        time();


    /*
    |--------------------------------------------------------------------------
    | REMEMBER ME
    |--------------------------------------------------------------------------
    */

    $_SESSION['remember_me'] =
        $remember;


    /*
    |--------------------------------------------------------------------------
    | MFA
    |--------------------------------------------------------------------------
    */

    $_SESSION['mfa_enabled'] =
        !empty(
            $user['mfa_enabled']
            ?? false
        );


    $_SESSION['mfa_verified'] =
        false;


    /*
    |--------------------------------------------------------------------------
    | ACTIVITY
    |--------------------------------------------------------------------------
    */

    if (
        function_exists(
            'updateSessionActivity'
        )
    ) {

        updateSessionActivity();

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR LOGIN ERROR
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['login_error'],
        $_SESSION['login_email']
    );


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BY ROLE
    |--------------------------------------------------------------------------
    */

    switch ($role) {


        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        case 'admin':

            redirect(
                BASE_URL .
                'admin/dashboard.php'
            );

            exit;


        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Vendor goes to seller/dashboard.php
        |--------------------------------------------------------------------------
        */

        case 'vendor':

            redirect(
                BASE_URL .
                'seller/dashboard.php'
            );

            exit;


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        case 'customer':

            redirect(
                BASE_URL .
                'dashboard.php'
            );

            exit;


        /*
        |--------------------------------------------------------------------------
        | UNKNOWN
        |--------------------------------------------------------------------------
        */

        default:

            $_SESSION['login_error'] =
                'Unable to determine account type.';

            redirect(
                BASE_URL .
                'index.php?login=1'
            );

            exit;
    }


} catch (PDOException $e) {


    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    if (
        defined('APP_DEBUG') &&
        APP_DEBUG
    ) {

        $_SESSION['login_error'] =
            'Database error: ' .
            $e->getMessage();

    } else {

        $_SESSION['login_error'] =
            'Something went wrong while logging in. '
            . 'Please try again later.';
    }


    $_SESSION['login_email'] =
        $email;


    redirect(
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}