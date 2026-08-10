<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
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
    |
    | Only use columns that are part of the known users table.
    |
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
    | PASSWORD
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
    | LOGIN USER
    |--------------------------------------------------------------------------
    */

    if (function_exists('loginUser')) {

        loginUser($user);

    } else {

        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;

        $_SESSION['user_id'] =
            $user['user_id'];

        $_SESSION['user_name'] =
            $user['name'];

        $_SESSION['user_email'] =
            $user['email'];

        $_SESSION['user_role'] =
            $role;

    }


    /*
    |--------------------------------------------------------------------------
    | REMEMBER ME
    |--------------------------------------------------------------------------
    */

    $_SESSION['remember_me'] =
        $remember;


    /*
    |--------------------------------------------------------------------------
    | ACTIVITY
    |--------------------------------------------------------------------------
    */

    if (function_exists('updateSessionActivity')) {

        updateSessionActivity();

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR LOGIN DATA
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

        case 'admin':

            redirect(
                BASE_URL .
                'admin/dashboard.php'
            );

            exit;


        case 'vendor':

            redirect(
                BASE_URL .
                'dashboard.php'
            );

            exit;


        case 'customer':

            redirect(
                BASE_URL .
                'dashboard.php'
            );

            exit;


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