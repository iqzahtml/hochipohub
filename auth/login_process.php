<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
| Login for:
| - Customer
| - Vendor
| - Admin
|
| Admin accounts are NOT registered through the frontend.
| Admin must already exist in the users table.
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$email = trim(
    $_POST['email'] ?? ''
);

$password = $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATE INPUT
|--------------------------------------------------------------------------
*/

if ($email === '' || $password === '') {

    $_SESSION['login_error'] =
        'Email and password are required.';

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL FORMAT
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION['login_error'] =
        'Please enter a valid email address.';

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | DATABASE
    |--------------------------------------------------------------------------
    */

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

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | INVALID LOGIN
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $_SESSION['login_error'] =
            'Invalid email or password.';

        $_SESSION['login_email'] =
            $email;

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PASSWORD
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

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    $status = strtolower(
        trim(
            (string) ($user['status'] ?? 'active')
        )
    );

    if ($status !== 'active') {

        $_SESSION['login_error'] =
            'Your account is not active.';

        $_SESSION['login_email'] =
            $email;

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ROLE
    |--------------------------------------------------------------------------
    */

    $role = strtolower(
        trim(
            (string) $user['role']
        )
    );

    $allowedRoles = [
        'admin',
        'vendor',
        'customer'
    ];

    if (!in_array($role, $allowedRoles, true)) {

        $_SESSION['login_error'] =
            'Invalid account role.';

        $_SESSION['login_email'] =
            $email;

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REGENERATE SESSION
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | STORE LOGIN SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['name'] =
        $user['name'];

    $_SESSION['email'] =
        $user['email'];

    $_SESSION['role'] =
        $role;


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
    | REDIRECT BASED ON ROLE
    |--------------------------------------------------------------------------
    */

    switch ($role) {

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        case 'admin':

            header(
                'Location: ' .
                BASE_URL .
                'admin/dashboard.php'
            );

            exit;


        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        case 'vendor':

            header(
                'Location: ' .
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

            header(
                'Location: ' .
                BASE_URL .
                'dashboard.php'
            );

            exit;
    }


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE / SYSTEM ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Unable to login at the moment. Please try again.';

    $_SESSION['login_email'] =
        $email;


    /*
    |--------------------------------------------------------------------------
    | DEVELOPMENT DEBUG
    |--------------------------------------------------------------------------
    */

    if (defined('APP_DEBUG') && APP_DEBUG === true) {

        $_SESSION['login_error'] =
            'Login error: ' .
            $e->getMessage();
    }


    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}