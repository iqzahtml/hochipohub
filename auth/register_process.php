<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER PROCESS
|--------------------------------------------------------------------------
| File:
| auth/register_process.php
|
| Purpose:
| - Register customer
| - Register vendor
| - Insert vendor profile
| - Store correct role
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST
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
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$name = trim(
    $_POST['name'] ?? ''
);

$email = trim(
    $_POST['email'] ?? ''
);

$phone = trim(
    $_POST['phone'] ?? ''
);

$role = strtolower(
    trim(
        $_POST['role'] ?? 'customer'
    )
);

$password = $_POST['password'] ?? '';

$confirmPassword = $_POST['confirm_password'] ?? '';

$terms = (
    isset($_POST['terms']) &&
    $_POST['terms'] === '1'
);


/*
|--------------------------------------------------------------------------
| KEEP OLD FORM DATA
|--------------------------------------------------------------------------
*/

$_SESSION['register_old'] = [

    'name' => $name,

    'email' => $email,

    'phone' => $phone,

    'role' => $role

];


/*
|--------------------------------------------------------------------------
| VALIDATE NAME
|--------------------------------------------------------------------------
*/

if (
    $name === '' ||
    mb_strlen($name) < 2 ||
    mb_strlen($name) > 100
) {

    $_SESSION['register_error'] =
        'Please enter a valid full name.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );

    exit;
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

    $_SESSION['register_error'] =
        'Please enter a valid email address.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE PHONE
|--------------------------------------------------------------------------
*/

if ($phone === '') {

    $_SESSION['register_error'] =
        'Please enter your phone number.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );

    exit;
}


$cleanPhone = preg_replace(
    '/[\s\-\(\)]/',
    '',
    $phone
);


if (
    $cleanPhone === null ||
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

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE ROLE
|--------------------------------------------------------------------------
|
| Customer atau Vendor sahaja.
| Admin tidak boleh register melalui form biasa.
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

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $password === '' ||
    strlen($password) < 6
) {

    $_SESSION['register_error'] =
        'Password must contain at least 6 characters.';

    redirect(
        BASE_URL . 'index.php?register=1'
    );

    exit;
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

    exit;
}


/*
|--------------------------------------------------------------------------
| TERMS
|--------------------------------------------------------------------------
*/

if (!$terms) {

    $_SESSION['register_error'] =
        'You must agree to the terms and conditions.';

    redirect(
        BASE_URL . 'index.php?register=1'
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

        $_SESSION['register_error'] =
            'An account with this email already exists.';

        redirect(
            BASE_URL . 'index.php?register=1'
        );

        exit;
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

        $_SESSION['register_error'] =
            'An account with this phone number already exists.';

        redirect(
            BASE_URL . 'index.php?register=1'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD HASH
    |--------------------------------------------------------------------------
    */

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    if ($passwordHash === false) {

        $_SESSION['register_error'] =
            'Unable to secure your password.';

        redirect(
            BASE_URL . 'index.php?register=1'
        );

        exit;
    }


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
            role
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
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

    $userId = (int) $db->lastInsertId();


    if ($userId <= 0) {

        throw new Exception(
            'Unable to create user account.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR REGISTRATION
    |--------------------------------------------------------------------------
    |
    | Kalau role = vendor,
    | create vendor record.
    |--------------------------------------------------------------------------
    */

    if ($role === 'vendor') {


        /*
        |--------------------------------------------------------------------------
        | CHECK VENDOR TABLE
        |--------------------------------------------------------------------------
        */

        $checkVendorTable = $db->query("
            SHOW TABLES LIKE 'vendors'
        ");


        if (
            !$checkVendorTable ||
            !$checkVendorTable->fetchColumn()
        ) {

            throw new Exception(
                'The vendors table does not exist.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT VENDOR
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | CHECK INSERT
        |--------------------------------------------------------------------------
        */

        if ($vendorStmt->rowCount() !== 1) {

            throw new Exception(
                'Vendor profile could not be created.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT DATABASE
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

    $_SESSION['login_success'] =
        'Account created successfully. '
        . 'You can now login.';


    /*
    |--------------------------------------------------------------------------
    | SAVE LOGIN EMAIL
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_email'] =
        $email;


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO LOGIN
    |--------------------------------------------------------------------------
    */

    redirect(
        BASE_URL . 'index.php?login=1'
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
    | DEBUG ERROR
    |--------------------------------------------------------------------------
    */

    if (
        defined('APP_DEBUG') &&
        APP_DEBUG
    ) {

        $_SESSION['register_error'] =
            'Registration error: ' .
            $e->getMessage();

    } else {

        $_SESSION['register_error'] =
            'Something went wrong while creating your account. '
            . 'Please try again later.';
    }


    /*
    |--------------------------------------------------------------------------
    | REDIRECT
    |--------------------------------------------------------------------------
    */

    redirect(
        BASE_URL . 'index.php?register=1'
    );

    exit;
}