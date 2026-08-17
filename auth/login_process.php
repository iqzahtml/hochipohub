<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ONLY POST
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

$password =
    $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $email === '' ||
    $password === ''
) {

    $_SESSION['error'] =
        'Email and password are required.';

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
    | GET USER
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

    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | USER NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $_SESSION['error'] =
            'Invalid email or password.';

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

        $_SESSION['error'] =
            'Invalid email or password.';

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK STATUS
    |--------------------------------------------------------------------------
    */

    if (
        isset($user['status']) &&
        strtolower(
            trim($user['status'])
        ) !== 'active'
    ) {

        $_SESSION['error'] =
            'Your account is not active.';

        header(
            'Location: ' .
            BASE_URL .
            'index.php?login=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN SUCCESS
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['name'] =
        $user['name'];

    $_SESSION['email'] =
        $user['email'];

    $_SESSION['role'] =
        strtolower(
            trim($user['role'])
        );


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BY ROLE
    |--------------------------------------------------------------------------
    */

    switch ($_SESSION['role']) {

        case 'admin':

            header(
                'Location: ' .
                BASE_URL .
                'admin/dashboard.php'
            );

            exit;


        case 'vendor':

            header(
                'Location: ' .
                BASE_URL .
                'seller/dashboard.php'
            );

            exit;


        case 'customer':

            header(
                'Location: ' .
                BASE_URL .
                'dashboard.php'
            );

            exit;


        default:

            session_unset();

            session_destroy();

            session_start();

            $_SESSION['error'] =
                'Invalid user role.';

            header(
                'Location: ' .
                BASE_URL .
                'index.php?login=1'
            );

            exit;

    }


} catch (Throwable $e) {

    $_SESSION['error'] =
        'Login error: ' .
        $e->getMessage();

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;

}