<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER PROCESS
|--------------------------------------------------------------------------
| File:
| auth/register_process.php
|
| Purpose:
| - Process registration
| - Validate customer/vendor registration
| - Check duplicate email
| - Check duplicate phone
| - Keep registration modal OPEN when error occurs
| - Preserve entered information
| - Redirect to login modal after successful registration
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST REQUEST
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

$confirmPassword = $_POST['confirm_password'] ?? '';

$terms = isset($_POST['terms']);


/*
|--------------------------------------------------------------------------
| SAVE OLD FORM DATA
|--------------------------------------------------------------------------
|
| We save the information so it can be displayed again
| when the registration form is reopened after an error.
|
| Password is NOT saved.
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
| REGISTER ERROR FUNCTION
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| When an error happens:
|
| 1. Save the error
| 2. Save the entered data
| 3. Tell the homepage to open register modal
| 4. Redirect to index.php
|
| The JavaScript will automatically open the modal.
|--------------------------------------------------------------------------
*/

function registerError($message)
{
    $_SESSION['register_error'] = $message;

    $_SESSION['open_register_modal'] = true;

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
| VALIDATE PHONE FORMAT
|--------------------------------------------------------------------------
|
| Optional basic validation.
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^01[0-9]{8,9}$/',
        $cleanPhone
    )
) {

    registerError(
        'Please enter a valid Malaysian phone number.'
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
| VALIDATE CONFIRM PASSWORD
|--------------------------------------------------------------------------
*/

if ($password !== $confirmPassword) {

    registerError(
        'Passwords do not match.'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE TERMS
|--------------------------------------------------------------------------
*/

if (!$terms) {

    registerError(
        'You must agree to the Terms & Conditions.'
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
            'This email is already registered. Please use another email.'
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
            'This phone number is already registered. Please use another phone number.'
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

    $userId = (int) $db->lastInsertId();


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
        $_SESSION['register_error'],
        $_SESSION['open_register_modal']
    );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_success'] =
        'Account created successfully. Please login.';


    $_SESSION['login_email'] =
        $email;


    /*
    |--------------------------------------------------------------------------
    | OPEN LOGIN MODAL
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
    | SAVE ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_error'] =
        'Registration error: ' .
        $e->getMessage();


    /*
    |--------------------------------------------------------------------------
    | KEEP REGISTER MODAL OPEN
    |--------------------------------------------------------------------------
    */

    $_SESSION['open_register_modal'] = true;


    /*
    |--------------------------------------------------------------------------
    | RETURN TO REGISTER MODAL
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}