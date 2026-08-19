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
| - Store OTP in database
| - Send OTP through PHPMailer
| - Support password reset OTP
| - Support MFA OTP
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIG
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| LOAD SESSION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| LOAD FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PHPMailer AUTOLOAD
|--------------------------------------------------------------------------
*/

$autoloadPath =
    dirname(__DIR__) . '/vendor/autoload.php';


if (!file_exists($autoloadPath)) {

    setFlashMessageSafe(
        'error',
        'Mailer system is not available. Please make sure the vendor folder and PHPMailer are installed.'
    );

    redirect(
        BASE_URL . 'index.php'
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
| OTP EXPIRY
|--------------------------------------------------------------------------
|
| If config.php already defines OTP_EXPIRY_MINUTES,
| use that value.
|
| Otherwise default to 10 minutes.
|--------------------------------------------------------------------------
*/

if (defined('OTP_EXPIRY_MINUTES')) {

    $expiryMinutes =
        (int) OTP_EXPIRY_MINUTES;

} else {

    $expiryMinutes = 10;
}


/*
|--------------------------------------------------------------------------
| SAFETY CHECK
|--------------------------------------------------------------------------
*/

if ($expiryMinutes <= 0) {

    $expiryMinutes = 10;
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
| VALIDATE REQUEST TYPE
|--------------------------------------------------------------------------
*/

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

try {

    $pdo = getDB();

} catch (Throwable $e) {

    if (
        defined('APP_DEBUG') &&
        APP_DEBUG
    ) {

        die(
            'Database connection error: '
            . e(
                $e->getMessage()
            )
        );
    }

    setFlashMessageSafe(
        'error',
        'Unable to connect to database.'
    );

    redirect(
        BASE_URL . 'index.php'
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
| CHECK OTP SESSION
|--------------------------------------------------------------------------
*/

if (
    $userId === null ||
    $userId === ''
) {

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
| CONVERT USER ID
|--------------------------------------------------------------------------
*/

$userId =
    (int) $userId;


if ($userId <= 0) {

    setFlashMessageSafe(
        'error',
        'Invalid user account.'
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


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

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
| CHECK EMAIL
|--------------------------------------------------------------------------
*/

$userEmail =
    trim(
        (string) (
            $user['email']
            ?? ''
        )
    );


if (
    $userEmail === '' ||
    !filter_var(
        $userEmail,
        FILTER_VALIDATE_EMAIL
    )
) {

    setFlashMessageSafe(
        'error',
        'The email address associated with this account is invalid.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| GENERATE OTP
|--------------------------------------------------------------------------
|
| 6-digit OTP
|
|--------------------------------------------------------------------------
*/

try {

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

} catch (Throwable $e) {

    setFlashMessageSafe(
        'error',
        'Unable to generate verification code.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| CALCULATE EXPIRY
|--------------------------------------------------------------------------
*/

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
| STORE OTP
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
        | UPDATE USER CURRENT RESET CODE
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


    /*
    |--------------------------------------------------------------------------
    | MFA OTP
    |--------------------------------------------------------------------------
    */

    } else {

        /*
        |--------------------------------------------------------------------------
        | INSERT MFA CODE HISTORY
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
        | UPDATE USER CURRENT MFA CODE
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


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
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

        die(
            'OTP database error: '
            . e(
                $e->getMessage()
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMAL ERROR
    |--------------------------------------------------------------------------
    */

    setFlashMessageSafe(
        'error',
        'Unable to generate OTP. Please try again.'
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
    | SMTP CONFIGURATION
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Gantikan:
    |
    | YOUR_EMAIL@gmail.com
    |
    | YOUR_APP_PASSWORD
    |
    | dengan Gmail dan Gmail App Password sebenar.
    |
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();


    /*
    |--------------------------------------------------------------------------
    | SMTP HOST
    |--------------------------------------------------------------------------
    */

    $mail->Host =
        'smtp.gmail.com';


    /*
    |--------------------------------------------------------------------------
    | SMTP AUTH
    |--------------------------------------------------------------------------
    */

    $mail->SMTPAuth =
        true;


    /*
    |--------------------------------------------------------------------------
    | SMTP USERNAME
    |--------------------------------------------------------------------------
    */

    $mail->Username =
        'YOUR_EMAIL@gmail.com';


    /*
    |--------------------------------------------------------------------------
    | SMTP PASSWORD
    |--------------------------------------------------------------------------
    */

    $mail->Password =
        'YOUR_APP_PASSWORD';


    /*
    |--------------------------------------------------------------------------
    | SMTP ENCRYPTION
    |--------------------------------------------------------------------------
    */

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;


    /*
    |--------------------------------------------------------------------------
    | SMTP PORT
    |--------------------------------------------------------------------------
    */

    $mail->Port =
        587;


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
        'YOUR_EMAIL@gmail.com',
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
        $userEmail,
        $user['name'] ?? ''
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
    | EMAIL SUBJECT
    |--------------------------------------------------------------------------
    */

    $mail->Subject =
        $subject;


    /*
    |--------------------------------------------------------------------------
    | HTML EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);


    $userName =
        e(
            $user['name']
            ?? 'User'
        );


    $safeTitle =
        e($title);


    $safeDescription =
        e($description);


    $safeOtp =
        e($otp);


    $safeExpiry =
        e($expiryMinutes);


    $currentYear =
        date('Y');


    $mail->Body = '

        <div style="
            font-family:Arial,Helvetica,sans-serif;
            background:#f1f5f9;
            padding:40px 20px;
        ">

            <div style="
                max-width:560px;
                margin:0 auto;
                background:#ffffff;
                border-radius:18px;
                padding:35px;
                box-shadow:0 10px 30px rgba(15,23,42,0.08);
            ">

                <h1 style="
                    color:#2563eb;
                    margin:0 0 5px;
                    font-size:28px;
                ">
                    HochipoHub
                </h1>


                <h2 style="
                    color:#0f172a;
                    margin:20px 0 10px;
                ">
                    ' . $safeTitle . '
                </h2>


                <p style="
                    color:#334155;
                    line-height:1.6;
                ">
                    Hi ' . $userName . ',
                </p>


                <p style="
                    color:#334155;
                    line-height:1.6;
                ">
                    ' . $safeDescription . '
                </p>


                <div style="
                    margin:30px 0;
                    padding:25px 20px;
                    background:#eff6ff;
                    border-radius:14px;
                    text-align:center;
                ">

                    <div style="
                        font-size:13px;
                        color:#64748b;
                        margin-bottom:10px;
                        font-weight:bold;
                        letter-spacing:1px;
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


                <p style="
                    color:#334155;
                    line-height:1.6;
                ">

                    This verification code will expire in

                    <strong>
                        ' . $safeExpiry . ' minutes
                    </strong>.

                </p>


                <p style="
                    color:#64748b;
                    font-size:13px;
                    line-height:1.6;
                ">

                    If you did not request this verification
                    code, you can safely ignore this email.

                </p>


                <hr style="
                    border:none;
                    border-top:1px solid #e2e8f0;
                    margin:25px 0;
                ">


                <p style="
                    color:#94a3b8;
                    font-size:12px;
                    margin:0;
                ">

                    &copy; ' . $currentYear . ' HochipoHub

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
        . 'Hi '
        . ($user['name'] ?? 'User')
        . ",\n\n"
        . $description
        . "\n\n"
        . 'Your OTP code is: '
        . $otp
        . "\n\n"
        . 'This code expires in '
        . $expiryMinutes
        . " minutes.\n\n"
        . 'If you did not request this code, please ignore this email.';


    /*
    |--------------------------------------------------------------------------
    | SEND
    |--------------------------------------------------------------------------
    */

    $mail->send();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */

    setFlashMessageSafe(
        'success',
        'A verification code has been sent to your email.'
    );


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO VERIFY OTP
    |--------------------------------------------------------------------------
    */

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


} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | EMAIL FAILED
    |--------------------------------------------------------------------------
    */

    if (
        defined('APP_DEBUG') &&
        APP_DEBUG
    ) {

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


    /*
    |--------------------------------------------------------------------------
    | REDIRECT
    |--------------------------------------------------------------------------
    */

    redirect(
        BASE_URL . 'index.php'
    );
}