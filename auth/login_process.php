<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ' . BASE_URL . 'index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$email = trim($_POST['email'] ?? '');

$password = $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($email === '' || $password === '') {

    $_SESSION['error'] =
        'Email and password are required.';

    header('Location: ' . BASE_URL . 'index.php?login=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $db = getDB();

} catch (Exception $e) {

    $_SESSION['error'] =
        'Database connection failed.';

    header('Location: ' . BASE_URL . 'index.php');
    exit;
}


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
        status,
        mfa_enabled
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
| CHECK USER
|--------------------------------------------------------------------------
*/

if (!$user) {

    $_SESSION['error'] =
        'Invalid email or password.';

    header('Location: ' . BASE_URL . 'index.php?login=1');
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

    header('Location: ' . BASE_URL . 'index.php?login=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

$status = strtolower(
    trim(
        $user['status'] ?? 'active'
    )
);

if ($status !== 'active') {

    $_SESSION['error'] =
        'Your account is not active.';

    header('Location: ' . BASE_URL . 'index.php?login=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| NORMALIZE ROLE
|--------------------------------------------------------------------------
*/

$role = strtolower(
    trim(
        $user['role'] ?? 'customer'
    )
);


/*
|--------------------------------------------------------------------------
| VALIDATE ROLE
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'admin',
    'vendor',
    'customer'
];

if (
    !in_array(
        $role,
        $allowedRoles,
        true
    )
) {

    $_SESSION['error'] =
        'Invalid user role.';

    header('Location: ' . BASE_URL . 'index.php?login=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN SESSION
|--------------------------------------------------------------------------
*/

$user['role'] = $role;

if (!createLoginSession($user)) {

    $_SESSION['error'] =
        'Unable to create login session.';

    header('Location: ' . BASE_URL . 'index.php?login=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| COMPATIBILITY SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['name'] =
    $user['name'];

$_SESSION['email'] =
    $user['email'];

$_SESSION['role'] =
    $role;

$_SESSION['user_role'] =
    $role;

$_SESSION['status'] =
    $status;


/*
|--------------------------------------------------------------------------
| REDIRECT BY ROLE
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


    /*
    |--------------------------------------------------------------------------
    | DEFAULT
    |--------------------------------------------------------------------------
    */

    default:

        $_SESSION['error'] =
            'Invalid user role.';

        header(
            'Location: ' .
            BASE_URL .
            'index.php'
        );

        exit;
}