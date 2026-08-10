<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEND OTP
|--------------------------------------------------------------------------
| File:
| auth/send_otp.php
|
| Purpose:
| Sends OTP through PHPMailer.
|
| Supported OTP types:
|
| reset = Password Reset OTP
| mfa   = Multi-Factor Authentication OTP
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

$autoloadPath =
    dirname(__DIR__)
    . '/vendor/autoload.php';


if (!file_exists($autoloadPath)) {

    setFlashMessageSafe(
        'error',
        'Mailer system is not available.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


require_once $autoloadPath;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| REQUEST TYPE
|--------------------------------------------------------------------------
*/

$type =
    strtolower(
        trim(
            $_POST['type']
            ?? $_GET['type']
            ?? 'reset'
        )
    );


if (
    !in_array(
        $type,
        ['reset', 'mfa'],
        true
    )
) {

    setFlashMessageSafe(
        'error',
        'Invalid OTP request.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

if ($type === 'reset') {

    $userId =
        getResetUser();

} else {

    $userId =
        getMfaPendingUser();
}


if ($userId === null) {

    setFlashMessageSafe(
        'error',
        'OTP session has expired. Please start the process again.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$user =
    getUserById(
        $pdo,
        $userId
    );


if (!$user) {

    if ($type === 'reset') {

        clearResetUser();

    } else {

        clearMfaPendingUser();
    }


    setFlashMessageSafe(
        'error',
        'User account could not be found.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| GENERATE OTP
|--------------------------------------------------------------------------
*/

$otp =
    str_pad(
        (string) random_int(
            0,
            999999
        ),
        6,
        '0',
        STR_PAD_LEFT
    );


$expiryMinutes =
    OTP_EXPIRY_MINUTES;


$expiryTimestamp =
    time()
    +
    (
        $expiryMinutes
        *
        60
    );


$expiryDate =
    date(
        'Y-m-d H:i:s',
        $expiryTimestamp
    );


/*
|--------------------------------------------------------------------------
| STORE OTP
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    if ($type === 'reset') {

        /*
        |--------------------------------------------------------------------------
        | PASSWORD RESET
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare("
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


        $stmt->execute([
            $userId,
            $otp,
            $expiryDate
        ]);


        /*
        |--------------------------------------------------------------------------
        | UPDATE CURRENT RESET CODE
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare("
                UPDATE users
                SET
                    reset_code = ?,
                    reset_expiry = ?
                WHERE user_id = ?
                LIMIT 1
            ");


        $stmt->execute([
            $otp,
            $expiryDate,
            $userId
        ]);

    } else {

        /*
        |--------------------------------------------------------------------------
        | MFA
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare("
                INSERT INTO mfa_codes
                (
                    user_id,
                    code,
                    expires_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?
                )
            ");


        $stmt->execute([
            $userId,
            $otp,
            $expiryDate
        ]);


        /*
        |--------------------------------------------------------------------------
        | UPDATE CURRENT MFA CODE
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare("
                UPDATE users
                SET
                    mfa_code = ?,
                    mfa_expiry = ?
                WHERE user_id = ?
                LIMIT 1
            ");


        $stmt->execute([
            $otp,
            $expiryDate,
            $userId
        ]);
    }


    $pdo->commit();


} catch (PDOException $e) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    if (APP_DEBUG) {

        die(
            'OTP database error: '
            . e(
                $e->getMessage()
            )
        );
    }


    setFlashMessageSafe(
        'error',
        'Unable to generate OTP.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| SEND EMAIL
|--------------------------------------------------------------------------
*/

$mail =
    new PHPMailer(true);


try {

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Tukar settings ini ikut email SMTP kau.
    |
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();

    $mail->Host =
        'smtp.gmail.com';

    $mail->SMTPAuth =
        true;

    $mail->Username =
        'YOUR_EMAIL@gmail.com';

    $mail->Password =
        'YOUR_APP_PASSWORD';

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port =
        587;


    /*
    |--------------------------------------------------------------------------
    | SENDER
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        'YOUR_EMAIL@gmail.com',
        APP_NAME
    );


    /*
    |--------------------------------------------------------------------------
    | RECIPIENT
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $user['email'],
        $user['name']
    );


    /*
    |--------------------------------------------------------------------------
    | EMAIL CONTENT
    |--------------------------------------------------------------------------
    */

    if ($type === 'reset') {

        $subject =
            'HochipoHub - Password Reset Code';

        $title =
            'Password Reset';

        $description =
            'Use the verification code below to reset your HochipoHub password.';

    } else {

        $subject =
            'HochipoHub - Login Verification Code';

        $title =
            'Login Verification';

        $description =
            'Use the verification code below to complete your login.';
    }


    $mail->isHTML(true);

    $mail->Subject =
        $subject;


    $mail->Body = '
        <div style="
            font-family:Arial,sans-serif;
            background:#f1f5f9;
            padding:40px;
        ">

            <div style="
                max-width:560px;
                margin:auto;
                background:#ffffff;
                border-radius:18px;
                padding:35px;
            ">

                <h1 style="
                    color:#2563eb;
                    margin-bottom:5px;
                ">
                    HochipoHub
                </h1>

                <h2>
                    ' . e($title) . '
                </h2>

                <p>
                    Hi ' . e($user['name']) . ',
                </p>

                <p>
                    ' . e($description) . '
                </p>

                <div style="
                    margin:30px 0;
                    padding:20px;
                    background:#eff6ff;
                    border-radius:12px;
                    text-align:center;
                ">

                    <div style="
                        font-size:13px;
                        color:#64748b;
                        margin-bottom:8px;
                    ">
                        YOUR OTP CODE
                    </div>

                    <strong style="
                        font-size:32px;
                        letter-spacing:8px;
                        color:#1d4ed8;
                    ">
                        ' . e($otp) . '
                    </strong>

                </div>

                <p>
                    This code will expire in
                    <strong>
                        ' . e($expiryMinutes) . ' minutes
                    </strong>.
                </p>

                <p style="
                    color:#64748b;
                    font-size:13px;
                ">
                    If you did not request this code,
                    please ignore this email.
                </p>

                <hr>

                <p style="
                    color:#94a3b8;
                    font-size:12px;
                ">
                    © ' . date('Y') . ' HochipoHub
                </p>

            </div>

        </div>
    ';


    $mail->AltBody =
        $title
        . "\n\n"
        . 'Your OTP code is: '
        . $otp
        . "\n\n"
        . 'This code expires in '
        . $expiryMinutes
        . ' minutes.';


    $mail->send();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    setFlashMessageSafe(
        'success',
        'A verification code has been sent to your email.'
    );


    if ($type === 'reset') {

        redirect(
            BASE_URL
            . 'auth/verify_otp.php?type=reset'
        );

    } else {

        redirect(
            BASE_URL
            . 'auth/verify_otp.php?type=mfa'
        );
    }


} catch (
    Exception $e
) {

    /*
    |--------------------------------------------------------------------------
    | EMAIL FAILED
    |--------------------------------------------------------------------------
    */

    if (APP_DEBUG) {

        setFlashMessageSafe(
            'error',
            'Unable to send email: '
            . $mail->ErrorInfo
        );

    } else {

        setFlashMessageSafe(
            'error',
            'Unable to send verification email. Please try again.'
        );
    }


    redirect(
        BASE_URL . 'index.php'
    );
}