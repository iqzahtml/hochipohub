<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEND OTP
|--------------------------------------------------------------------------
| File:
| auth/send_otp.php
|
| Purpose:
| - Generate OTP
| - Save OTP into database
| - Send OTP through PHPMailer
| - Support Password Reset OTP
| - Support MFA OTP
| - SHOW ERRORS DIRECTLY
|
| IMPORTANT:
| - This file DOES NOT redirect to homepage when SMTP fails.
| - SMTP errors are displayed directly for debugging.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD SYSTEM FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PHPMailer AUTOLOAD
|--------------------------------------------------------------------------
*/

$autoloadPath =
    dirname(__DIR__) . '/vendor/autoload.php';


if (!file_exists($autoloadPath)) {

    showOtpError(
        'PHPMailer Not Found',
        'The PHPMailer vendor/autoload.php file could not be found.'
    );
}

require_once $autoloadPath;


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| SMTP CONFIGURATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| SMTP USERNAME = Gmail account that SENDS the OTP.
|
| SMTP PASSWORD = Gmail APP PASSWORD.
|
| DO NOT use the user's database email here.
|
|--------------------------------------------------------------------------
*/

$smtpUsername =
    'hochipohub941@gmail.com';

$smtpPassword =
    'lhge llhk vzap pujl';


$smtpHost =
    'smtp.gmail.com';

$smtpPort =
    587;


/*
|--------------------------------------------------------------------------
| CHECK SMTP CONFIGURATION
|--------------------------------------------------------------------------
*/

if (
    $smtpUsername === 'hochipohub941@gmail.com' ||
    $smtpPassword === 'lhge llhk vzap pujl'
) {

    showOtpError(
        'SMTP Setup Required',
        'You have not entered the SMTP sender email and App Password yet.'
    );
}


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


/*
|--------------------------------------------------------------------------
| VALID OTP TYPE
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $type,
        ['reset', 'mfa'],
        true
    )
) {

    showOtpError(
        'Invalid OTP Request',
        'The OTP type must be either reset or mfa.'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $pdo = getDB();

} catch (Throwable $e) {

    showOtpError(
        'Database Connection Error',
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| GET USER ID FROM SESSION
|--------------------------------------------------------------------------
*/

if ($type === 'reset') {

    $userId =
        getResetUser();

} else {

    $userId =
        getMfaPendingUser();
}


/*
|--------------------------------------------------------------------------
| CHECK USER ID
|--------------------------------------------------------------------------
*/

if ($userId === null || $userId === '') {

    showOtpError(
        'OTP Session Expired',
        'No password-reset or MFA user was found in the current session.'
    );
}


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

try {

    $user =
        getUserById(
            $pdo,
            $userId
        );

} catch (Throwable $e) {

    showOtpError(
        'Database Error',
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| CHECK USER
|--------------------------------------------------------------------------
*/

if (!$user) {

    if ($type === 'reset') {

        clearResetUser();

    } else {

        clearMfaPendingUser();
    }


    showOtpError(
        'User Not Found',
        'The user account associated with this OTP request could not be found.'
    );
}


/*
|--------------------------------------------------------------------------
| CHECK USER EMAIL
|--------------------------------------------------------------------------
*/

$recipientEmail =
    trim(
        $user['email'] ?? ''
    );


if (
    $recipientEmail === '' ||
    !filter_var(
        $recipientEmail,
        FILTER_VALIDATE_EMAIL
    )
) {

    showOtpError(
        'Invalid User Email',
        'The user account does not have a valid email address.'
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


/*
|--------------------------------------------------------------------------
| OTP EXPIRY
|--------------------------------------------------------------------------
|
| We intentionally do NOT depend on OTP_EXPIRY_MINUTES
| because that constant caused your previous error.
|
|--------------------------------------------------------------------------
*/

$expiryMinutes = 10;


$expiryTimestamp =
    time()
    +
    (
        $expiryMinutes * 60
    );


$expiryDate =
    date(
        'Y-m-d H:i:s',
        $expiryTimestamp
    );


/*
|--------------------------------------------------------------------------
| SAVE OTP TO DATABASE
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | PASSWORD RESET OTP
    |--------------------------------------------------------------------------
    */

    if ($type === 'reset') {

        /*
        |--------------------------------------------------------------------------
        | Insert reset history
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
        | Update current reset code
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

    }


    /*
    |--------------------------------------------------------------------------
    | MFA OTP
    |--------------------------------------------------------------------------
    */

    else {

        /*
        |--------------------------------------------------------------------------
        | Insert MFA code
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
        | Update current MFA code
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


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


} catch (Throwable $e) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    showOtpError(
        'OTP Database Error',
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| CREATE PHPMailer
|--------------------------------------------------------------------------
*/

$mail =
    new PHPMailer(true);


/*
|--------------------------------------------------------------------------
| SMTP DEBUGGING
|--------------------------------------------------------------------------
|
| Set to 0 normally.
|
| If authentication still fails, change to 2 temporarily.
|
|--------------------------------------------------------------------------
*/

$mail->SMTPDebug = 0;


/*
|--------------------------------------------------------------------------
| SEND EMAIL
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();


    $mail->Host =
        $smtpHost;


    $mail->SMTPAuth =
        true;


    $mail->Username =
        $smtpUsername;


    $mail->Password =
        $smtpPassword;


    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;


    $mail->Port =
        $smtpPort;


    /*
    |--------------------------------------------------------------------------
    | CHARACTER SET
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
        $smtpUsername,
        defined('APP_NAME')
            ? APP_NAME
            : 'HochipoHub'
    );


    /*
    |--------------------------------------------------------------------------
    | RECIPIENT
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $recipientEmail,
        $user['name'] ?? 'User'
    );


    /*
    |--------------------------------------------------------------------------
    | EMAIL TYPE
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


    /*
    |--------------------------------------------------------------------------
    | HTML EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);


    $mail->Subject =
        $subject;


    $safeName =
        htmlspecialchars(
            $user['name'] ?? 'User',
            ENT_QUOTES,
            'UTF-8'
        );


    $safeOtp =
        htmlspecialchars(
            $otp,
            ENT_QUOTES,
            'UTF-8'
        );


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
                box-shadow:
                    0 10px 30px
                    rgba(0,0,0,0.08);
            ">

                <h1 style="
                    color:#2563eb;
                    margin-bottom:5px;
                ">
                    HochipoHub
                </h1>

                <h2>
                    ' . htmlspecialchars(
                        $title,
                        ENT_QUOTES,
                        'UTF-8'
                    ) . '
                </h2>

                <p>
                    Hi ' . $safeName . ',
                </p>

                <p>
                    ' . htmlspecialchars(
                        $description,
                        ENT_QUOTES,
                        'UTF-8'
                    ) . '
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
                        ' . $safeOtp . '
                    </strong>

                </div>

                <p>

                    This code will expire in

                    <strong>
                        ' . $expiryMinutes . ' minutes
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


    /*
    |--------------------------------------------------------------------------
    | PLAIN TEXT EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->AltBody =
        $title
        . "\n\n"
        . 'Your OTP code is: '
        . $otp
        . "\n\n"
        . 'This code expires in '
        . $expiryMinutes
        . ' minutes.';


    /*
    |--------------------------------------------------------------------------
    | SEND
    |--------------------------------------------------------------------------
    */

    $mail->send();


} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | SMTP FAILED
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | DO NOT REDIRECT TO HOMEPAGE.
    |
    |--------------------------------------------------------------------------
    */

    $errorMessage =
        $mail->ErrorInfo;


    if (
        empty($errorMessage)
    ) {

        $errorMessage =
            $e->getMessage();
    }


    showSmtpError(
        $errorMessage,
        $smtpHost,
        $smtpPort,
        $smtpUsername,
        $recipientEmail
    );
}


/*
|--------------------------------------------------------------------------
| EMAIL SUCCESS
|--------------------------------------------------------------------------
*/

if ($type === 'reset') {

    setFlashMessageSafe(
        'success',
        'A verification code has been sent to your email.'
    );


    redirect(
        BASE_URL .
        'auth/verify_otp.php?type=reset'
    );

}


if ($type === 'mfa') {

    setFlashMessageSafe(
        'success',
        'A verification code has been sent to your email.'
    );


    redirect(
        BASE_URL .
        'auth/verify_otp.php?type=mfa'
    );
}


/*
|--------------------------------------------------------------------------
| ERROR PAGE FUNCTION
|--------------------------------------------------------------------------
*/

function showOtpError(
    $title,
    $message
) {

    $safeTitle =
        htmlspecialchars(
            (string) $title,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeMessage =
        htmlspecialchars(
            (string) $message,
            ENT_QUOTES,
            'UTF-8'
        );


    $backUrl =
        defined('BASE_URL')
            ? BASE_URL . 'auth/forgot_password.php'
            : '../auth/forgot_password.php';


    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            HochipoHub - Error
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {

                margin: 0;

                min-height: 100vh;

                display: flex;

                align-items: center;

                justify-content: center;

                padding: 20px;

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                background:
                    linear-gradient(
                        135deg,
                        #eff6ff,
                        #dbeafe
                    );
            }

            .container {

                width: 100%;

                max-width: 650px;

                background: #ffffff;

                border-radius: 22px;

                padding: 40px;

                box-shadow:
                    0 20px 50px
                    rgba(
                        15,
                        23,
                        42,
                        0.12
                    );
            }

            .icon {

                width: 64px;

                height: 64px;

                display: flex;

                align-items: center;

                justify-content: center;

                border-radius: 18px;

                background: #fee2e2;

                font-size: 30px;

                margin-bottom: 20px;
            }

            .eyebrow {

                color: #dc2626;

                font-size: 12px;

                font-weight: 700;

                letter-spacing: 2px;
            }

            h1 {

                color: #0f172a;

                margin:
                    8px 0 12px;

                font-size: 28px;
            }

            .description {

                color: #64748b;

                line-height: 1.6;
            }

            .error-box {

                margin-top: 25px;

                padding: 20px;

                background: #fef2f2;

                border:
                    1px solid
                    #fecaca;

                border-radius: 12px;
            }

            .error-label {

                font-weight: 700;

                color: #991b1b;

                margin-bottom: 10px;
            }

            pre {

                margin: 0;

                white-space: pre-wrap;

                word-break: break-word;

                font-family:
                    Consolas,
                    monospace;

                font-size: 14px;

                color: #7f1d1d;

                line-height: 1.6;
            }

            a {

                display: inline-block;

                margin-top: 25px;

                padding:
                    13px 20px;

                background: #2563eb;

                color: #ffffff;

                text-decoration: none;

                border-radius: 10px;

                font-weight: 700;
            }

            a:hover {

                background: #1d4ed8;
            }

        </style>

    </head>

    <body>

        <div class="container">

            <div class="icon">
                ⚠️
            </div>

            <div class="eyebrow">
                OTP ERROR
            </div>

            <h1>
                <?= $safeTitle ?>
            </h1>

            <p class="description">

                The OTP process could not be completed.

                The exact error is displayed below
                so it can be fixed.

            </p>

            <div class="error-box">

                <div class="error-label">
                    Error Details
                </div>

                <pre><?= $safeMessage ?></pre>

            </div>

            <a href="<?= htmlspecialchars(
                $backUrl,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

                ← Back to Forgot Password

            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| SMTP ERROR PAGE
|--------------------------------------------------------------------------
*/

function showSmtpError(
    $errorMessage,
    $smtpHost,
    $smtpPort,
    $smtpUsername,
    $recipientEmail
) {

    $safeError =
        htmlspecialchars(
            (string) $errorMessage,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeHost =
        htmlspecialchars(
            (string) $smtpHost,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeUsername =
        htmlspecialchars(
            (string) $smtpUsername,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeRecipient =
        htmlspecialchars(
            (string) $recipientEmail,
            ENT_QUOTES,
            'UTF-8'
        );


    $backUrl =
        defined('BASE_URL')
            ? BASE_URL . 'auth/forgot_password.php'
            : '../auth/forgot_password.php';


    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            HochipoHub - SMTP Error
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {

                margin: 0;

                min-height: 100vh;

                display: flex;

                align-items: center;

                justify-content: center;

                padding: 20px;

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                background:
                    linear-gradient(
                        135deg,
                        #eff6ff,
                        #dbeafe
                    );
            }

            .container {

                width: 100%;

                max-width: 720px;

                background: #ffffff;

                border-radius: 22px;

                padding: 40px;

                box-shadow:
                    0 20px 50px
                    rgba(
                        15,
                        23,
                        42,
                        0.12
                    );
            }

            .icon {

                width: 64px;

                height: 64px;

                display: flex;

                align-items: center;

                justify-content: center;

                border-radius: 18px;

                background: #fef3c7;

                font-size: 30px;

                margin-bottom: 20px;
            }

            .eyebrow {

                color: #d97706;

                font-size: 12px;

                font-weight: 700;

                letter-spacing: 2px;
            }

            h1 {

                color: #0f172a;

                margin:
                    8px 0 12px;

                font-size: 28px;
            }

            .description {

                color: #64748b;

                line-height: 1.6;
            }

            .error-box {

                margin-top: 25px;

                padding: 20px;

                background: #fef2f2;

                border:
                    1px solid
                    #fecaca;

                border-radius: 12px;
            }

            .error-label {

                color: #991b1b;

                font-weight: 700;

                margin-bottom: 10px;
            }

            pre {

                margin: 0;

                white-space: pre-wrap;

                word-break: break-word;

                font-family:
                    Consolas,
                    monospace;

                font-size: 14px;

                color: #7f1d1d;

                line-height: 1.6;
            }

            .info-box {

                margin-top: 20px;

                padding: 18px;

                background: #eff6ff;

                border:
                    1px solid
                    #bfdbfe;

                border-radius: 12px;

                color: #1e3a8a;

                line-height: 1.8;

                font-size: 14px;
            }

            .info-box strong {

                color: #1e40af;
            }

            a {

                display: inline-block;

                margin-top: 25px;

                padding:
                    13px 20px;

                background: #2563eb;

                color: #ffffff;

                text-decoration: none;

                border-radius: 10px;

                font-weight: 700;
            }

            a:hover {

                background: #1d4ed8;
            }

        </style>

    </head>

    <body>

        <div class="container">

            <div class="icon">
                📧
            </div>

            <div class="eyebrow">
                SMTP ERROR
            </div>

            <h1>
                OTP Email Could Not Be Sent
            </h1>

            <p class="description">

                The OTP was generated successfully,
                but PHPMailer could not send the email.

                The exact SMTP error is shown below.

            </p>


            <div class="error-box">

                <div class="error-label">
                    PHPMailer Error
                </div>

                <pre><?= $safeError ?></pre>

            </div>


            <div class="info-box">

                <strong>
                    SMTP Host:
                </strong>

                <?= $safeHost ?>

                <br>

                <strong>
                    SMTP Port:
                </strong>

                <?= (int) $smtpPort ?>

                <br>

                <strong>
                    SMTP Sender:
                </strong>

                <?= $safeUsername ?>

                <br>

                <strong>
                    OTP Recipient:
                </strong>

                <?= $safeRecipient ?>

            </div>


            <a href="<?= htmlspecialchars(
                $backUrl,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

                ← Back to Forgot Password

            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}