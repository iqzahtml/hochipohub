<?php
/**
 * =========================================================
 * HOCHIPOHUB - MAIL / SEND MAIL
 * File: mail/send_mail.php
 * =========================================================
 *
 * Purpose:
 * - Send email from HochipoHub
 * - Used for OTP / MFA
 * - Password reset
 * - Account related notifications
 *
 * Usage:
 *
 * require_once __DIR__ . '/send_mail.php';
 *
 * sendMail(
 *     'user@email.com',
 *     'Your OTP Code',
 *     '<h2>Your OTP is 123456</h2>'
 * );
 *
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| Prevent direct unwanted output
|--------------------------------------------------------------------------
*/

if (!defined('HOCHIPOHUB_MAIL')) {
    define('HOCHIPOHUB_MAIL', true);
}


/*
|--------------------------------------------------------------------------
| Load config
|--------------------------------------------------------------------------
|
| config.php should contain the application configuration.
|
*/

$configFile = dirname(__DIR__) . '/config.php';

if (file_exists($configFile)) {
    require_once $configFile;
}


/*
|--------------------------------------------------------------------------
| Mail configuration
|--------------------------------------------------------------------------
|
| DO NOT hard-code real email passwords here.
|
| You can define these values in config.php:
|
| MAIL_HOST
| MAIL_PORT
| MAIL_USERNAME
| MAIL_PASSWORD
| MAIL_FROM_EMAIL
| MAIL_FROM_NAME
|
|--------------------------------------------------------------------------
*/

$mailHost = defined('MAIL_HOST')
    ? MAIL_HOST
    : 'smtp.gmail.com';

$mailPort = defined('MAIL_PORT')
    ? MAIL_PORT
    : 587;

$mailUsername = defined('MAIL_USERNAME')
    ? MAIL_USERNAME
    : '';

$mailPassword = defined('MAIL_PASSWORD')
    ? MAIL_PASSWORD
    : '';

$mailFromEmail = defined('MAIL_FROM_EMAIL')
    ? MAIL_FROM_EMAIL
    : $mailUsername;

$mailFromName = defined('MAIL_FROM_NAME')
    ? MAIL_FROM_NAME
    : 'HochipoHub';


/*
|--------------------------------------------------------------------------
| Send Mail Function
|--------------------------------------------------------------------------
*/

function sendMail(
    $recipientEmail,
    $subject,
    $message,
    $recipientName = ''
) {

    global
        $mailHost,
        $mailPort,
        $mailUsername,
        $mailPassword,
        $mailFromEmail,
        $mailFromName;


    /*
    |--------------------------------------------------------------------------
    | Validate email
    |--------------------------------------------------------------------------
    */

    if (
        empty($recipientEmail) ||
        !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)
    ) {

        return [
            'success' => false,
            'message' => 'Invalid recipient email address.'
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Validate SMTP credentials
    |--------------------------------------------------------------------------
    */

    if (
        empty($mailUsername) ||
        empty($mailPassword)
    ) {

        return [
            'success' => false,
            'message' => 'Mail SMTP credentials are not configured.'
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Check PHPMailer
    |--------------------------------------------------------------------------
    |
    | PHPMailer is recommended instead of PHP mail().
    |
    */

    $phpMailerPaths = [

        dirname(__DIR__) . '/vendor/autoload.php',

        dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php',

        dirname(__DIR__) . '/PHPMailer/PHPMailer.php'

    ];


    $composerLoaded = false;


    foreach ($phpMailerPaths as $path) {

        if (file_exists($path)) {

            require_once $path;

            $composerLoaded = true;

            break;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Try PHPMailer
    |--------------------------------------------------------------------------
    */

    if (
        class_exists('PHPMailer\PHPMailer\PHPMailer')
    ) {

        try {

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);


            /*
            |--------------------------------------------------------------------------
            | SMTP
            |--------------------------------------------------------------------------
            */

            $mail->isSMTP();

            $mail->Host = $mailHost;

            $mail->SMTPAuth = true;

            $mail->Username = $mailUsername;

            $mail->Password = $mailPassword;

            $mail->SMTPSecure =
                \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = $mailPort;


            /*
            |--------------------------------------------------------------------------
            | Sender
            |--------------------------------------------------------------------------
            */

            $mail->setFrom(
                $mailFromEmail,
                $mailFromName
            );


            /*
            |--------------------------------------------------------------------------
            | Recipient
            |--------------------------------------------------------------------------
            */

            $mail->addAddress(
                $recipientEmail,
                $recipientName
            );


            /*
            |--------------------------------------------------------------------------
            | Email format
            |--------------------------------------------------------------------------
            */

            $mail->isHTML(true);

            $mail->CharSet = 'UTF-8';

            $mail->Subject = $subject;

            $mail->Body = $message;


            /*
            |--------------------------------------------------------------------------
            | Plain text fallback
            |--------------------------------------------------------------------------
            */

            $mail->AltBody = strip_tags(
                str_replace(
                    [
                        '<br>',
                        '<br/>',
                        '<br />'
                    ],
                    PHP_EOL,
                    $message
                )
            );


            /*
            |--------------------------------------------------------------------------
            | Send
            |--------------------------------------------------------------------------
            */

            $mail->send();


            return [
                'success' => true,
                'message' => 'Email sent successfully.'
            ];

        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => 'Email could not be sent.',
                'error' => $e->getMessage()
            ];

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Fallback: PHP mail()
    |--------------------------------------------------------------------------
    |
    | This is only a fallback.
    | SMTP + PHPMailer is much more reliable.
    |
    */

    $headers = [];

    $headers[] =
        'MIME-Version: 1.0';

    $headers[] =
        'Content-Type: text/html; charset=UTF-8';

    $headers[] =
        'From: ' .
        $mailFromName .
        ' <' .
        $mailFromEmail .
        '>';

    $headers[] =
        'Reply-To: ' .
        $mailFromEmail;

    $headers[] =
        'X-Mailer: PHP/' .
        phpversion();


    $headersString = implode(
        "\r\n",
        $headers
    );


    $sent = mail(
        $recipientEmail,
        $subject,
        $message,
        $headersString
    );


    if ($sent) {

        return [
            'success' => true,
            'message' => 'Email sent successfully.'
        ];

    }


    return [
        'success' => false,
        'message' => 'Unable to send email.'
    ];

}


/*
|--------------------------------------------------------------------------
| OTP Email
|--------------------------------------------------------------------------
*/

function sendOTPEmail(
    $recipientEmail,
    $otp,
    $recipientName = ''
) {

    $subject = 'HochipoHub - Your Verification Code';


    $message = '
    <!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>HochipoHub Verification</title>

    </head>

    <body
        style="
            margin:0;
            padding:0;
            background:#f1f5ff;
            font-family:Arial, sans-serif;
        "
    >

        <div
            style="
                max-width:600px;
                margin:40px auto;
                background:#ffffff;
                border-radius:18px;
                overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,0.08);
            "
        >

            <div
                style="
                    padding:30px;
                    text-align:center;
                    background:#2563eb;
                    color:#ffffff;
                "
            >

                <h1
                    style="
                        margin:0;
                        font-size:28px;
                    "
                >
                    HOCHIPOHUB
                </h1>

                <p
                    style="
                        margin:8px 0 0;
                        opacity:0.9;
                    "
                >
                    Marketplace for Everyone
                </p>

            </div>


            <div
                style="
                    padding:35px;
                    text-align:center;
                "
            >

                <h2
                    style="
                        color:#172554;
                    "
                >
                    Verify Your Account
                </h2>

                <p
                    style="
                        color:#64748b;
                        line-height:1.6;
                    "
                >
                    Hello ' .
                    htmlspecialchars($recipientName) .
                    ',
                    <br><br>

                    Use the verification code below
                    to continue your HochipoHub account verification.
                </p>


                <div
                    style="
                        margin:30px 0;
                        padding:20px;
                        background:#eff6ff;
                        border-radius:12px;
                    "
                >

                    <span
                        style="
                            display:block;
                            font-size:34px;
                            font-weight:bold;
                            letter-spacing:8px;
                            color:#2563eb;
                        "
                    >
                        ' .
                        htmlspecialchars($otp) .
                        '
                    </span>

                </div>


                <p
                    style="
                        color:#64748b;
                        font-size:14px;
                    "
                >
                    This verification code is temporary.
                    Do not share it with anyone.
                </p>

            </div>


            <div
                style="
                    padding:20px;
                    text-align:center;
                    background:#f8fafc;
                    color:#94a3b8;
                    font-size:12px;
                "
            >

                &copy; ' .
                date('Y') .
                ' HochipoHub.
                All rights reserved.

            </div>

        </div>

    </body>

    </html>
    ';


    return sendMail(
        $recipientEmail,
        $subject,
        $message,
        $recipientName
    );

}


/*
|--------------------------------------------------------------------------
| Password Reset Email
|--------------------------------------------------------------------------
*/

function sendPasswordResetEmail(
    $recipientEmail,
    $resetCode,
    $recipientName = ''
) {

    $subject =
        'HochipoHub - Password Reset Code';


    $message = '
    <!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Password Reset</title>

    </head>


    <body
        style="
            margin:0;
            padding:0;
            background:#f1f5ff;
            font-family:Arial,sans-serif;
        "
    >

        <div
            style="
                max-width:600px;
                margin:40px auto;
                background:#ffffff;
                border-radius:18px;
                overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,0.08);
            "
        >

            <div
                style="
                    padding:30px;
                    background:#2563eb;
                    text-align:center;
                    color:#ffffff;
                "
            >

                <h1 style="margin:0;">
                    HOCHIPOHUB
                </h1>

            </div>


            <div
                style="
                    padding:35px;
                    text-align:center;
                "
            >

                <h2
                    style="
                        color:#172554;
                    "
                >
                    Reset Your Password
                </h2>


                <p
                    style="
                        color:#64748b;
                        line-height:1.6;
                    "
                >

                    Hello ' .
                    htmlspecialchars($recipientName) .
                    ',

                    <br><br>

                    We received a request to reset
                    your HochipoHub password.

                </p>


                <div
                    style="
                        margin:30px 0;
                        padding:20px;
                        background:#eff6ff;
                        border-radius:12px;
                    "
                >

                    <span
                        style="
                            display:block;
                            font-size:32px;
                            font-weight:bold;
                            letter-spacing:7px;
                            color:#2563eb;
                        "
                    >
                        ' .
                        htmlspecialchars($resetCode) .
                        '
                    </span>

                </div>


                <p
                    style="
                        color:#64748b;
                        font-size:14px;
                    "
                >

                    If you did not request a password reset,
                    you can safely ignore this email.

                </p>

            </div>


            <div
                style="
                    padding:20px;
                    text-align:center;
                    background:#f8fafc;
                    color:#94a3b8;
                    font-size:12px;
                "
            >

                &copy; ' .
                date('Y') .
                ' HochipoHub.
                All rights reserved.

            </div>

        </div>

    </body>

    </html>
    ';


    return sendMail(
        $recipientEmail,
        $subject,
        $message,
        $recipientName
    );

}


/*
|--------------------------------------------------------------------------
| General Notification Email
|--------------------------------------------------------------------------
*/

function sendNotificationEmail(
    $recipientEmail,
    $subject,
    $content,
    $recipientName = ''
) {

    $message = '
    <!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>' .
        htmlspecialchars($subject) .
        '</title>

    </head>


    <body
        style="
            margin:0;
            padding:0;
            background:#f1f5ff;
            font-family:Arial,sans-serif;
        "
    >

        <div
            style="
                max-width:600px;
                margin:40px auto;
                background:#ffffff;
                border-radius:18px;
                overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,0.08);
            "
        >

            <div
                style="
                    padding:25px;
                    text-align:center;
                    background:#2563eb;
                    color:#ffffff;
                "
            >

                <h1 style="margin:0;">
                    HOCHIPOHUB
                </h1>

            </div>


            <div
                style="
                    padding:35px;
                    color:#334155;
                    line-height:1.7;
                "
            >

                <p>
                    Hello ' .
                    htmlspecialchars($recipientName) .
                    ',
                </p>

                <h2
                    style="
                        color:#172554;
                    "
                >
                    ' .
                    htmlspecialchars($subject) .
                    '
                </h2>

                <div>
                    ' .
                    $content .
                    '
                </div>

            </div>


            <div
                style="
                    padding:20px;
                    text-align:center;
                    background:#f8fafc;
                    color:#94a3b8;
                    font-size:12px;
                "
            >

                &copy; ' .
                date('Y') .
                ' HochipoHub.
                All rights reserved.

            </div>

        </div>

    </body>

    </html>
    ';


    return sendMail(
        $recipientEmail,
        $subject,
        $message,
        $recipientName
    );

}
?>