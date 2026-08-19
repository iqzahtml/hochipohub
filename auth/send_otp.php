<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEND OTP
|--------------------------------------------------------------------------
| File:
| auth/send_otp.php
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) .
    '/config.php';

require_once dirname(__DIR__) .
    '/includes/session.php';

require_once dirname(__DIR__) .
    '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

$autoloadPath =
    dirname(__DIR__)
    .
    '/vendor/autoload.php';


if (
    !file_exists(
        $autoloadPath
    )
) {

    die(
        '<h2>PHPMailer Error</h2>
        <p>vendor/autoload.php was not found.</p>
        <p>Run:</p>
        <pre>composer require phpmailer/phpmailer</pre>'
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
            $_GET['type']
            ??
            $_POST['type']
            ??
            'reset'
        )
    );


if (
    !in_array(
        $type,
        ['reset', 'mfa'],
        true
    )
) {

    die(
        '<h2>OTP Error</h2>
        <p>Invalid OTP request type.</p>'
    );
}


/*
|--------------------------------------------------------------------------
| GET DATABASE
|--------------------------------------------------------------------------
*/

$pdo =
    getDB();


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

if (
    $type === 'reset'
) {

    $userId =
        getResetUser();

} else {

    $userId =
        getMfaPendingUser();
}


/*
|--------------------------------------------------------------------------
| CHECK SESSION
|--------------------------------------------------------------------------
*/

if (
    $userId === null
) {

    die(
        '<h2>Password Reset Error</h2>
        <p>Password reset session is missing or expired.</p>
        <p>
            Please go back and start Forgot Password again.
        </p>
        <p>
            <a href="' .
            e(
                BASE_URL .
                'auth/forgot_password.php'
            )
            . '">
                ← Back to Forgot Password
            </a>
        </p>'
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


if (
    !$user
) {

    if (
        $type === 'reset'
    ) {

        clearResetUser();

    } else {

        clearMfaPendingUser();
    }


    die(
        '<h2>OTP Error</h2>
        <p>User account could not be found.</p>'
    );
}


/*
|--------------------------------------------------------------------------
| GENERATE OTP
|--------------------------------------------------------------------------
*/

$otp =
    str_pad(
        (string)
        random_int(
            0,
            999999
        ),
        6,
        '0',
        STR_PAD_LEFT
    );


/*
|--------------------------------------------------------------------------
| EXPIRY
|--------------------------------------------------------------------------
*/

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


    if (
        $type === 'reset'
    ) {

        /*
        |--------------------------------------------------------------------------
        | INSERT PASSWORD RESET HISTORY
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
        | UPDATE USERS
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
        | INSERT MFA CODE
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
        | UPDATE USERS
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


} catch (
    PDOException $e
) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    die(
        '<h2>OTP Database Error</h2>
        <p>' .
        e(
            $e->getMessage()
        )
        .
        '</p>'
    );
}


/*
|--------------------------------------------------------------------------
| CREATE MAIL
|--------------------------------------------------------------------------
*/

$mail =
    new PHPMailer(true);


try {

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();


    $mail->Host =
        SMTP_HOST;


    $mail->SMTPAuth =
        true;


    $mail->Username =
        SMTP_USERNAME;


    $mail->Password =
        SMTP_PASSWORD;


    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;


    $mail->Port =
        SMTP_PORT;


    /*
    |--------------------------------------------------------------------------
    | CHARSET
    |--------------------------------------------------------------------------
    */

    $mail->CharSet =
        'UTF-8';


    /*
    |--------------------------------------------------------------------------
    | SENDER
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        SMTP_FROM_EMAIL,
        SMTP_FROM_NAME
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
    | EMAIL TYPE
    |--------------------------------------------------------------------------
    */

    if (
        $type === 'reset'
    ) {

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


    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(
        true
    );


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
            ">
                HochipoHub
            </h1>

            <h2>
                ' .
                e($title)
                . '
            </h2>

            <p>
                Hi ' .
                e(
                    $user['name']
                )
                . ',
            </p>

            <p>
                ' .
                e(
                    $description
                )
                . '
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
                    ' .
                    e($otp)
                    . '
                </strong>

            </div>

            <p>
                This code will expire in
                <strong>
                    ' .
                    e(
                        $expiryMinutes
                    )
                    . '
                    minutes
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
                &copy; ' .
                date('Y')
                . '
                HochipoHub
            </p>

        </div>

    </div>
    ';


    /*
    |--------------------------------------------------------------------------
    | PLAIN TEXT VERSION
    |--------------------------------------------------------------------------
    */

    $mail->AltBody =
        $title
        .
        "\n\n"
        .
        'Your OTP code is: '
        .
        $otp
        .
        "\n\n"
        .
        'This code expires in '
        .
        $expiryMinutes
        .
        ' minutes.';


    /*
    |--------------------------------------------------------------------------
    | SEND
    |--------------------------------------------------------------------------
    */

    $mail->send();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    if (
        $type === 'reset'
    ) {

        setFlashMessageSafe(
            'success',
            'Verification code sent successfully. Check your email.'
        );


        redirect(
            BASE_URL
            .
            'auth/verify_otp.php?type=reset'
        );

    } else {

        setFlashMessageSafe(
            'success',
            'Verification code sent successfully.'
        );


        redirect(
            BASE_URL
            .
            'auth/verify_otp.php?type=mfa'
        );
    }


} catch (
    Exception $e
) {

    /*
    |--------------------------------------------------------------------------
    | SMTP ERROR
    |--------------------------------------------------------------------------
    |
    | DO NOT REDIRECT TO HOMEPAGE.
    | Show the actual SMTP error.
    |--------------------------------------------------------------------------
    */

    echo '<!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

        <title>
            SMTP Error - HochipoHub
        </title>

        <style>

            body {
                font-family: Arial;
                background: #f1f5f9;
                padding: 40px;
            }

            .box {
                max-width: 700px;
                margin: auto;
                background: white;
                padding: 30px;
                border-radius: 16px;
                box-shadow:
                    0 10px 30px
                    rgba(0,0,0,.08);
            }

            h2 {
                color: #dc2626;
            }

            .error {
                background: #fef2f2;
                color: #991b1b;
                padding: 15px;
                border-radius: 10px;
                word-break: break-word;
            }

            a {
                display: inline-block;
                margin-top: 20px;
                color: #2563eb;
                text-decoration: none;
            }

        </style>

    </head>

    <body>

        <div class="box">

            <h2>
                OTP EMAIL ERROR
            </h2>

            <div class="error">
                ' .
                e(
                    $mail->ErrorInfo
                )
                . '
            </div>

            <a href="' .
                e(
                    BASE_URL
                    .
                    'auth/forgot_password.php'
                )
                . '">

                ← Back to Forgot Password

            </a>

        </div>

    </body>

    </html>';

    exit;
}