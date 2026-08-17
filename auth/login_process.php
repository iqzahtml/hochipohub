<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error'] = "Email and password are required.";
    header("Location: ../index.php");
    exit;
}

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

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK PASSWORD
|--------------------------------------------------------------------------
*/

if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK USER STATUS
|--------------------------------------------------------------------------
*/

if (
    isset($user['status']) &&
    strtolower($user['status']) !== 'active'
) {
    $_SESSION['error'] = "Your account is not active.";
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| LOGIN SUCCESS
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['user_id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = strtolower(trim($user['role']));

/*
|--------------------------------------------------------------------------
| REDIRECT BY ROLE
|--------------------------------------------------------------------------
*/

switch ($_SESSION['role']) {

    case 'admin':

        header("Location: ../admin/dashboard.php");
        exit;

    case 'vendor':

        header("Location: ../seller/dashboard.php");
        exit;

    case 'customer':

        header("Location: ../dashboard.php");
        exit;

    default:

        $_SESSION['error'] = "Invalid user role.";

        session_unset();
        session_destroy();

        header("Location: ../index.php");
        exit;
}