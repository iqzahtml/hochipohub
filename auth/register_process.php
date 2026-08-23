<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER PROCESS
|--------------------------------------------------------------------------
| File:
| auth/register_process.php
|
| Purpose:
| - Process new registration
| - Create customer/vendor account
| - After successful registration:
|   automatically open LOGIN modal
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: ' . BASE_URL . 'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$name = trim($_POST['name'] ?? '');

$email = trim($_POST['email'] ?? '');

$phone = trim($_POST['phone'] ?? '');

$role = strtolower(
    trim(
        $_POST['role'] ?? 'customer'
    )
);

$password = $_POST['password'] ?? '';

$confirmPassword =
    $_POST['confirm_password'] ?? '';

$terms = isset($_POST['terms']);


/*
|--------------------------------------------------------------------------
| KEEP OLD DATA
|--------------------------------------------------------------------------
*/

$_SESSION['register_old'] = [
    'name'  => $name,
    'email' => $email,
    'phone' => $phone,
    'role'  => $role
];


/*
|--------------------------------------------------------------------------
| HELPER - REDIRECT BACK TO REGISTER
|--------------------------------------------------------------------------
*/

function registerError($message)
{
    $_SESSION['register_error'] = $message;

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE NAME
|--------------------------------------------------------------------------
*/

if ($name === '') {

    registerError(
        'Name is required.'
    );
}


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

    registerError(
        'Please enter a valid email address.'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE PHONE
|--------------------------------------------------------------------------
*/

$cleanPhone = preg_replace(
    '/[\s\-\(\)]/',
    '',
    $phone
);

if ($cleanPhone === '') {

    registerError(
        'Phone number is required.'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE ROLE
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'customer',
    'vendor'
];

if (
    !in_array(
        $role,
        $allowedRoles,
        true
    )
) {

    registerError(
        'Invalid account type.'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if (strlen($password) < 6) {

    registerError(
        'Password must contain at least 6 characters.'
    );
}


/*
|--------------------------------------------------------------------------
| CONFIRM PASSWORD
|--------------------------------------------------------------------------
*/

if ($password !== $confirmPassword) {

    registerError(
        'Passwords do not match.'
    );
}


/*
|--------------------------------------------------------------------------
| TERMS
|--------------------------------------------------------------------------
*/

if (!$terms) {

    registerError(
        'You must agree to the terms and conditions.'
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
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    if ($stmt->fetch()) {

        registerError(
            'This email is already registered.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PHONE
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT user_id
        FROM users
        WHERE phone = ?
        LIMIT 1
    ");

    $stmt->execute([
        $cleanPhone
    ]);

    if ($stmt->fetch()) {

        registerError(
            'This phone number is already registered.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HASH PASSWORD
    |--------------------------------------------------------------------------
    */

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | INSERT USER
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        INSERT INTO users
        (
            name,
            email,
            phone,
            password,
            role,
            status
        )
        VALUES
        (
            :name,
            :email,
            :phone,
            :password,
            :role,
            'active'
        )
    ");

    $stmt->execute([

        ':name' =>
            $name,

        ':email' =>
            $email,

        ':phone' =>
            $cleanPhone,

        ':password' =>
            $passwordHash,

        ':role' =>
            $role

    ]);


    /*
    |--------------------------------------------------------------------------
    | GET USER ID
    |--------------------------------------------------------------------------
    */

    $userId = (int)
        $db->lastInsertId();


    if ($userId <= 0) {

        throw new Exception(
            'Failed to create user.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE VENDOR PROFILE
    |--------------------------------------------------------------------------
    */

    if ($role === 'vendor') {

        $vendorStmt = $db->prepare("
            INSERT INTO vendors
            (
                user_id,
                business_name,
                approval_status
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $vendorStmt->execute([
            $userId,
            $name,
            'Pending'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | CLEAR REGISTER DATA
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['register_old'],
        $_SESSION['register_error']
    );


    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | Save success message and email.
    |
    | DO NOT LOGIN THE USER HERE.
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_success'] =
        'Account created successfully. Please login.';

    $_SESSION['login_email'] =
        $email;


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO LOGIN MODAL
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        isset($db) &&
        $db instanceof PDO &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_error'] =
        'Registration error: ' .
        $e->getMessage();


    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}