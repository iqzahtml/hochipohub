<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
| File:
| auth/login_process.php
|
| Purpose:
| Handle user authentication.
|
| Flow:
|
| Login Modal
|      ↓
| POST email + password
|      ↓
| Validate input
|      ↓
| Find user
|      ↓
| Verify password
|      ↓
| Check account status
|      ↓
| Create session
|      ↓
| Redirect by role
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST ALLOWED
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

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
    $_POST['email']
    ?? ''
);

$password =
    $_POST['password']
    ?? '';

$remember =
    isset($_POST['remember'])
    &&
    $_POST['remember'] === '1';


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL
|--------------------------------------------------------------------------
*/

if (
    $email === ''
    ||
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
}


/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $password === ''
) {

    $_SESSION['login_error'] =
        'Please enter your password.';

    $_SESSION['login_email'] =
        $email;

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
            status,
            mfa_enabled,
            created_at,
            updated_at

        FROM users

        WHERE email = ?

        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $user =
        $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | USER NOT FOUND
    |--------------------------------------------------------------------------
    |
    | Do not reveal whether the email exists.
    |
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
        empty($user['password'])
        ||
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

    $accountStatus =
        strtolower(
            trim(
                (string) (
                    $user['status']
                    ?? 'active'
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | INACTIVE ACCOUNT
    |--------------------------------------------------------------------------
    */

    if (
        $accountStatus !== 'active'
    ) {

        $_SESSION['login_error'] =
            'Your account is not active. '
            . 'Please contact the administrator.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ROLE
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [

        'customer',
        'vendor',
        'admin'

    ];


    $role =
        strtolower(
            trim(
                (string) (
                    $user['role']
                    ?? ''
                )
            )
        );


    if (
        !in_array(
            $role,
            $allowedRoles,
            true
        )
    ) {

        $_SESSION['login_error'] =
            'Your account has an invalid role. '
            . 'Please contact the administrator.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN USER
    |--------------------------------------------------------------------------
    |
    | loginUser() will:
    |
    | - regenerate session ID
    | - store user ID
    | - store name
    | - store email
    | - store role
    | - store status
    | - mark logged in
    |
    */

    loginUser(
        $user
    );


    /*
    |--------------------------------------------------------------------------
    | REMEMBER ME
    |--------------------------------------------------------------------------
    |
    | For now we store the preference in session.
    |
    | A persistent remember-me token can be added later
    | without changing the login form.
    |
    */

    $_SESSION['remember_me'] =
        $remember;


    /*
    |--------------------------------------------------------------------------
    | UPDATE LAST ACTIVITY
    |--------------------------------------------------------------------------
    */

    updateSessionActivity();


    /*
    |--------------------------------------------------------------------------
    | CLEAR OLD LOGIN DATA
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['login_error'],
        $_SESSION['login_email']
    );


    /*
    |--------------------------------------------------------------------------
    | ROLE-BASED REDIRECT
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

            break;


        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        case 'vendor':

            redirect(
                BASE_URL .
                'dashboard.php'
            );

            break;


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

            break;


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        default:

            logoutUser();

            $_SESSION['login_error'] =
                'Unable to determine your account type.';

            redirect(
                BASE_URL .
                'index.php?login=1'
            );
    }


/*
|--------------------------------------------------------------------------
| DATABASE ERROR
|--------------------------------------------------------------------------
*/

} catch (
    PDOException $e
) {

    if (APP_DEBUG) {

        $_SESSION['login_error'] =
            'Database error: '
            . $e->getMessage();

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
}