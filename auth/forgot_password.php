<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FORGOT PASSWORD
|--------------------------------------------------------------------------
| File:
| auth/forgot_password.php
|
| Purpose:
| Handle password reset request.
|
| Flow:
| Forgot Password
|      ↓
| Enter Email
|      ↓
| Check User
|      ↓
| Generate OTP
|      ↓
| Store OTP temporarily
|      ↓
| Send OTP
|      ↓
| Verify OTP
|      ↓
| Reset Password
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| REQUIRE GUEST
|--------------------------------------------------------------------------
|
| Logged-in users do not need password recovery.
|
*/

if (isLoggedIn()) {

    redirect(
        BASE_URL . 'dashboard.php'
    );
}


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /*
    |--------------------------------------------------------------------------
    | GET EMAIL
    |--------------------------------------------------------------------------
    */

    $email = trim(
        $_POST['email']
        ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */

    if (
        $email === ''
        ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $_SESSION['forgot_error'] =
            'Please enter a valid email address.';

        $_SESSION['forgot_email'] =
            $email;

        redirect(
            BASE_URL . 'index.php?forgot=1'
        );
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
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare("
            SELECT
                user_id,
                name,
                email,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        $user =
            $stmt->fetch();


        /*
        |--------------------------------------------------------------------------
        | USER NOT FOUND
        |--------------------------------------------------------------------------
        |
        | We do not reveal whether an email exists.
        | This prevents email/account enumeration.
        |
        */

        if (!$user) {

            $_SESSION['forgot_success'] =
                'If an account exists with this email, '
                . 'a verification code will be sent.';

            redirect(
                BASE_URL . 'index.php?forgot=1'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK ACCOUNT STATUS
        |--------------------------------------------------------------------------
        */

        if (
            isset($user['status'])
            &&
            strtolower(
                (string) $user['status']
            ) !== 'active'
        ) {

            $_SESSION['forgot_error'] =
                'This account is currently unavailable.';

            redirect(
                BASE_URL . 'index.php?forgot=1'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATE OTP
        |--------------------------------------------------------------------------
        |
        | 6-digit numeric OTP.
        |
        */

        $otp = (string) random_int(
            100000,
            999999
        );


        /*
        |--------------------------------------------------------------------------
        | OTP EXPIRY
        |--------------------------------------------------------------------------
        */

        $otpExpiry =
            time()
            +
            (
                OTP_EXPIRY_MINUTES
                *
                60
            );


        /*
        |--------------------------------------------------------------------------
        | STORE RESET INFORMATION
        |--------------------------------------------------------------------------
        */

        $_SESSION['reset_user_id'] =
            (int) $user['user_id'];

        $_SESSION['reset_email'] =
            $user['email'];

        $_SESSION['reset_name'] =
            $user['name'];

        $_SESSION['reset_otp'] =
            password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

        $_SESSION['reset_otp_expires'] =
            $otpExpiry;

        $_SESSION['reset_otp_attempts'] =
            0;


        /*
        |--------------------------------------------------------------------------
        | CLEAR OLD RESET STATE
        |--------------------------------------------------------------------------
        */

        unset(
            $_SESSION['reset_verified']
        );


        /*
        |--------------------------------------------------------------------------
        | PREPARE OTP FOR EMAIL
        |--------------------------------------------------------------------------
        |
        | send_otp.php will use these session values.
        |
        */

        $_SESSION['otp_code_for_mail'] =
            $otp;


        /*
        |--------------------------------------------------------------------------
        | REDIRECT TO SEND OTP
        |--------------------------------------------------------------------------
        */

        redirect(
            BASE_URL . 'auth/send_otp.php'
        );


    } catch (
        PDOException $e
    ) {

        /*
        |--------------------------------------------------------------------------
        | DEVELOPMENT ERROR
        |--------------------------------------------------------------------------
        */

        if (APP_DEBUG) {

            $_SESSION['forgot_error'] =
                'Database error: '
                . $e->getMessage();

        } else {

            $_SESSION['forgot_error'] =
                'Something went wrong. '
                . 'Please try again later.';
        }


        redirect(
            BASE_URL . 'index.php?forgot=1'
        );
    }
}


/*
|--------------------------------------------------------------------------
| DIRECT ACCESS
|--------------------------------------------------------------------------
|
| If user opens this PHP file directly without POST,
| send them back to the forgot-password interface.
|
*/

redirect(
    BASE_URL . 'index.php?forgot=1'
);