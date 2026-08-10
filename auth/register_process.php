<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER PROCESS
|--------------------------------------------------------------------------
| File:
| auth/register_process.php
|
| Purpose:
| Handle new customer/vendor registration.
|
| Form fields:
| - name
| - email
| - phone
| - role
| - password
| - confirm_password
| - terms
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

$name =
    trim(
        $_POST['name']
        ?? ''
    );

$email =
    trim(
        $_POST['email']
        ?? ''
    );

$phone =
    trim(
        $_POST['phone']
        ?? ''
    );

$role =
    strtolower(
        trim(
            $_POST['role']
            ?? 'customer'
        )
    );

$password =
    $_POST['password']
    ?? '';

$confirmPassword =
    $_POST['confirm_password']
    ?? '';

$terms =
    isset(
        $_POST['terms']
    )
    &&
    $_POST['terms'] === '1';


/*
|--------------------------------------------------------------------------
| KEEP FORM DATA
|--------------------------------------------------------------------------
|
| Used to repopulate the form if registration fails.
|--------------------------------------------------------------------------
*/

$_SESSION['register_old'] = [

    'name' =>
        $name,

    'email' =>
        $email,

    'phone' =>
        $phone,

    'role' =>
        $role

];


/*
|--------------------------------------------------------------------------
| VALIDATE NAME
|--------------------------------------------------------------------------
*/

if (
    $name === ''
    ||
    mb_strlen($name) < 2
    ||
    mb_strlen($name) > 100
) {

    $_SESSION['register_error'] =
        'Please enter a valid full name.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


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
    ||
    strlen($email) > 150
) {

    $_SESSION['register_error'] =
        'Please enter a valid email address.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE PHONE
|--------------------------------------------------------------------------
*/

if (
    $phone === ''
) {

    $_SESSION['register_error'] =
        'Please enter your phone number.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


/*
|--------------------------------------------------------------------------
| PHONE FORMAT
|--------------------------------------------------------------------------
|
| Allows:
| 0123456789
| +60123456789
| 01-23456789
| spaces and brackets
|--------------------------------------------------------------------------
*/

$cleanPhone =
    preg_replace(
        '/[\s\-\(\)]/',
        '',
        $phone
    );


if (
    $cleanPhone === null
    ||
    !preg_match(
        '/^\+?[0-9]{9,15}$/',
        $cleanPhone
    )
) {

    $_SESSION['register_error'] =
        'Please enter a valid phone number.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE ROLE
|--------------------------------------------------------------------------
|
| Only customer and vendor can register.
| Admin accounts must never be created through
| the public registration form.
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

    $_SESSION['register_error'] =
        'Invalid account type selected.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $password === ''
    ||
    strlen($password) < 6
) {

    $_SESSION['register_error'] =
        'Password must contain at least 6 characters.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


/*
|--------------------------------------------------------------------------
| CONFIRM PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $password !== $confirmPassword
) {

    $_SESSION['register_error'] =
        'Passwords do not match.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}


/*
|--------------------------------------------------------------------------
| TERMS
|--------------------------------------------------------------------------
*/

if (
    !$terms
) {

    $_SESSION['register_error'] =
        'You must agree to the terms and conditions.';

    redirect(
        BASE_URL . 'index.php?register=1'
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

    $stmt =
        $db->prepare("
            SELECT
                user_id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

    $stmt->execute([
        $email
    ]);


    if (
        $stmt->fetch()
    ) {

        $_SESSION['register_error'] =
            'An account with this email already exists.';

        redirect(
            BASE_URL . 'index.php?register=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PHONE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT
                user_id
            FROM users
            WHERE phone = ?
            LIMIT 1
        ");

    $stmt->execute([
        $cleanPhone
    ]);


    if (
        $stmt->fetch()
    ) {

        $_SESSION['register_error'] =
            'An account with this phone number already exists.';

        redirect(
            BASE_URL . 'index.php?register=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HASH PASSWORD
    |--------------------------------------------------------------------------
    */

    $passwordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    if (
        $passwordHash === false
    ) {

        $_SESSION['register_error'] =
            'Unable to secure your password. Please try again.';

        redirect(
            BASE_URL . 'index.php?register=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    |
    | New accounts start as active.
    |
    | Vendor approval is handled separately through
    | the vendors table.
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
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
                ?,
                ?,
                ?,
                ?,
                ?,
                'active'
            )
        ");

    $stmt->execute([

        $name,
        $email,
        $cleanPhone,
        $passwordHash,
        $role

    ]);


    /*
    |--------------------------------------------------------------------------
    | GET NEW USER ID
    |--------------------------------------------------------------------------
    */

    $userId =
        (int) $db->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | VENDOR ACCOUNT
    |--------------------------------------------------------------------------
    |
    | If the user registers as a vendor,
    | create the vendor record.
    |
    | Vendor starts as Pending and must be
    | approved by admin.
    |--------------------------------------------------------------------------
    */

    if (
        $role === 'vendor'
    ) {

        $stmt =
            $db->prepare("
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
                    'Pending'
                )
            ");

        $stmt->execute([

            $userId,
            $name

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
    | CLEAR OLD FORM DATA
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['register_old']
    );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_success'] =
        'Account created successfully. '
        . 'You can now login.';


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO LOGIN
    |--------------------------------------------------------------------------
    */

    redirect(
        BASE_URL . 'index.php?login=1'
    );


/*
|--------------------------------------------------------------------------
| DATABASE ERROR
|--------------------------------------------------------------------------
*/

} catch (
    PDOException $e
) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        isset($db)
        &&
        $db instanceof PDO
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR MESSAGE
    |--------------------------------------------------------------------------
    */

    if (
        APP_DEBUG
    ) {

        $_SESSION['register_error'] =
            'Database error: '
            . $e->getMessage();

    } else {

        $_SESSION['register_error'] =
            'Something went wrong while creating your account. '
            . 'Please try again later.';
    }


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BACK
    |--------------------------------------------------------------------------
    */

    redirect(
        BASE_URL . 'index.php?register=1'
    );
}