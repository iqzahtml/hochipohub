<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER PROCESS
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

if ($name === '') {

    $_SESSION['register_error'] =
        'Name is required.';

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
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

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
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

    $_SESSION['register_error'] =
        'Phone number is required.';

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
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

    $_SESSION['register_error'] =
        'Invalid account type.';

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if (strlen($password) < 6) {

    $_SESSION['register_error'] =
        'Password must contain at least 6 characters.';

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CONFIRM PASSWORD
|--------------------------------------------------------------------------
*/

if ($password !== $confirmPassword) {

    $_SESSION['register_error'] =
        'Passwords do not match.';

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
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

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE REGISTER
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
            'This email is already registered.';

        header(
            'Location: ' .
            BASE_URL .
            'index.php?register=1'
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
            'This phone number is already registered.';

        header(
            'Location: ' .
            BASE_URL .
            'index.php?register=1'
        );

        exit;
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
    |
    | Ikut database users awak.
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

        ':name' => $name,

        ':email' => $email,

        ':phone' => $cleanPhone,

        ':password' => $passwordHash,

        ':role' => $role

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
    | VENDOR PROFILE
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
    | CLEAR OLD DATA
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['register_old']
    );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_success'] =
        'Account created successfully. Please login.';

    $_SESSION['login_email'] =
        $email;


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
    | DEBUG
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