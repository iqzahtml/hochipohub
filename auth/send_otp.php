<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/functions.php";
require_once "../mail/send_mail.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: forgot_password.php");
    exit();

}


$email = trim($_POST['email'] ?? '');


if ($email === '') {

    setFlashMessage(
        "error",
        "Please enter your email."
    );

    header("Location: forgot_password.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Find User
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("

    SELECT
        user_id,
        name,
        email,
        status

    FROM users

    WHERE email = ?

    LIMIT 1

");

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    setFlashMessage(
        "error",
        "No account is registered with this email."
    );

    header("Location: forgot_password.php");
    exit();

}


$user = $result->fetch_assoc();


if ($user['status'] === 'suspended') {

    setFlashMessage(
        "error",
        "This account has been suspended."
    );

    header("Location: forgot_password.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Generate 6 Digit Reset Code
|--------------------------------------------------------------------------
*/

$resetCode = (string) random_int(
    100000,
    999999
);


$expiresAt = date(
    'Y-m-d H:i:s',
    time() + (PASSWORD_RESET_EXPIRY_MINUTES * 60)
);


/*
|--------------------------------------------------------------------------
| Update Users Reset Fields
|--------------------------------------------------------------------------
*/

$updateUser = $conn->prepare("

    UPDATE users

    SET
        reset_code = ?,
        reset_expiry = ?

    WHERE user_id = ?

");

$updateUser->bind_param(
    "ssi",
    $resetCode,
    $expiresAt,
    $user['user_id']
);

$updateUser->execute();


/*
|--------------------------------------------------------------------------
| Store Password Reset History
|--------------------------------------------------------------------------
*/

$insertReset = $conn->prepare("

    INSERT INTO password_resets

    (
        user_id,
        reset_code,
        expires_at
    )

    VALUES
    (
        ?,
        ?,
        ?
    )

");

$insertReset->bind_param(
    "iss",
    $user['user_id'],
    $resetCode,
    $expiresAt
);

$insertReset->execute();


/*
|--------------------------------------------------------------------------
| Email
|--------------------------------------------------------------------------
*/

$message = "

<div style='font-family:Arial,sans-serif;'>

    <h2>HochipoHub Password Reset</h2>

    <p>
        Hello " .
        htmlspecialchars($user['name']) .
        ",
    </p>

    <p>
        Your password reset OTP is:
    </p>

    <h1 style='letter-spacing:8px;'>
        {$resetCode}
    </h1>

    <p>
        This code expires in " .
        PASSWORD_RESET_EXPIRY_MINUTES .
        " minutes.
    </p>

    <p>
        If you did not request this reset, you can ignore this email.
    </p>

</div>

";


$mailSent = sendMail(
    $email,
    "HochipoHub Password Reset OTP",
    $message
);


if (!$mailSent) {

    setFlashMessage(
        "error",
        "Unable to send OTP email. Please try again."
    );

    header("Location: forgot_password.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| Store Session
|--------------------------------------------------------------------------
*/

$_SESSION['reset_user_id'] =
    $user['user_id'];

$_SESSION['reset_email'] =
    $user['email'];


setFlashMessage(
    "success",
    "OTP has been sent to your email."
);


header("Location: verify_otp.php");

exit();