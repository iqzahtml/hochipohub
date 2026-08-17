<?php

require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect(
        BASE_URL . 'index.php'
    );
}

$email = trim(
    $_POST['email'] ?? ''
);

$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {

    $_SESSION['login_error'] =
        'Email and password are required.';

    $_SESSION['login_email'] =
        $email;

    redirect(
        BASE_URL . 'index.php?login=1'
    );
}

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
            password,
            role,
            status
        FROM users
        WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | USER NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $_SESSION['login_error'] =
            'Email not found in database.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PASSWORD
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
            'Password does not match the password stored for this account.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK STATUS
    |--------------------------------------------------------------------------
    */

    if (
        isset($user['status']) &&
        strtolower(
            trim(
                (string) $user['status']
            )
        ) !== 'active'
    ) {

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
    | VALIDATE ROLE
    |--------------------------------------------------------------------------
    */

    $role = strtolower(
        trim(
            (string) $user['role']
        )
    );

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
            'Invalid account role.';

        redirect(
            BASE_URL . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN SUCCESS
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);

    $_SESSION['user_id'] =
        (int) $user['user_id'];

    $_SESSION['user_name'] =
        $user['name'];

    $_SESSION['user_email'] =
        $user['email'];

    $_SESSION['name'] =
        $user['name'];

    $_SESSION['email'] =
        $user['email'];

    $_SESSION['role'] =
        $role;

    $_SESSION['user_role'] =
        $role;

    $_SESSION['status'] =
        $user['status'] ?? 'active';

    $_SESSION['user_status'] =
        $_SESSION['status'];

    $_SESSION['logged_in'] =
        true;

    $_SESSION['login_time'] =
        time();

    $_SESSION['last_activity'] =
        time();


    /*
    |--------------------------------------------------------------------------
    | REDIRECT
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

    redirect(
        BASE_URL . 'dashboard.php'
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DEBUG
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Login error: ' . $e->getMessage();

    $_SESSION['login_email'] =
        $email;

    redirect(
        BASE_URL . 'index.php?login=1'
    );
}