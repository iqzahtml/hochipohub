<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
| File:
|     auth/login_process.php
|
| Purpose:
|     Authenticate customer / vendor / admin
|     and redirect according to role.
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

$email = trim(
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
            role
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
    | VERIFY PASSWORD
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
    | GET ROLE
    |--------------------------------------------------------------------------
    */

    $role = strtolower(
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
    | We explicitly set BOTH:
    |
    | $_SESSION['role']
    | $_SESSION['user_role']
    |
    | because your vendor dashboard checks:
    |
    | $_SESSION['role']
    |
    */

    session_regenerate_id(true);


    $_SESSION['logged_in'] = true;

    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['user_name'] =
        $user['name'] ?? '';

    $_SESSION['user_email'] =
        $user['email'] ?? '';

    $_SESSION['role'] =
        $role;

    $_SESSION['user_role'] =
        $role;

    $_SESSION['remember_me'] =
        $remember;

    $_SESSION['login_time'] =
        time();

    $_SESSION['last_activity'] =
        time();


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
    | UPDATE ACTIVITY
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
    | REDIRECT BY ROLE
    |--------------------------------------------------------------------------
    */

    if ($role === 'admin') {

        redirect(
            BASE_URL .
            'admin/dashboard.php'
        );

        exit;
    }


    if ($role === 'vendor') {

        redirect(
            BASE_URL .
            'seller/dashboard.php'
        );

        exit;
    }


    if ($role === 'customer') {

        redirect(
            BASE_URL .
            'dashboard.php'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Unable to determine account type.';

    redirect(
        BASE_URL .
        'index.php?login=1'
    );

    exit;


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
            'Something went wrong while logging in. ' .
            'Please try again later.';

    }


    $_SESSION['login_email'] =
        $email;


    redirect(
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}