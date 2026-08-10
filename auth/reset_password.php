<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - RESET PASSWORD
|--------------------------------------------------------------------------
| File:
| auth/reset_password.php
|
| Purpose:
| Allows user to create a new password after
| successfully verifying the password reset OTP.
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| ONLY ALLOW RESET FLOW
|--------------------------------------------------------------------------
*/

$resetUserId = getResetUser();

if ($resetUserId === null) {

    setFlashMessageSafe(
        'error',
        'Password reset session expired. Please request a new reset code.'
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

$pdo = getDB();

$user = getUserById(
    $pdo,
    $resetUserId
);

if (!$user) {

    clearResetUser();

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
| HANDLE FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        setFlashMessageSafe(
            'error',
            'Please enter your new password.'
        );

        redirect(
            BASE_URL . 'auth/reset_password.php'
        );
    }


    if (strlen($password) < 6) {

        setFlashMessageSafe(
            'error',
            'Password must contain at least 6 characters.'
        );

        redirect(
            BASE_URL . 'auth/reset_password.php'
        );
    }


    if ($password !== $confirmPassword) {

        setFlashMessageSafe(
            'error',
            'Passwords do not match.'
        );

        redirect(
            BASE_URL . 'auth/reset_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FIND VERIFIED RESET CODE
    |--------------------------------------------------------------------------
    |
    | verify_otp.php marks the OTP as used only after
    | successful verification.
    |
    | We use the session flag below to make sure the
    | user cannot directly access this page.
    |--------------------------------------------------------------------------
    */

    if (
        empty(
            $_SESSION['password_reset_verified']
        )
        ||
        $_SESSION['password_reset_verified']
            !== true
    ) {

        setFlashMessageSafe(
            'error',
            'Please verify the reset code first.'
        );

        redirect(
            BASE_URL . 'index.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    $hashedPassword =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    try {

        $pdo->beginTransaction();


        $stmt = $pdo->prepare("
            UPDATE users
            SET
                password = ?,
                reset_code = NULL,
                reset_expiry = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
            LIMIT 1
        ");


        $stmt->execute([
            $hashedPassword,
            $resetUserId
        ]);


        /*
        |--------------------------------------------------------------------------
        | CLEAR PASSWORD RESET HISTORY
        |--------------------------------------------------------------------------
        |
        | The verified OTP has already been marked as used.
        | We keep history for audit purposes.
        |--------------------------------------------------------------------------
        */


        $pdo->commit();


        /*
        |--------------------------------------------------------------------------
        | CLEAR RESET SESSION
        |--------------------------------------------------------------------------
        */

        clearResetUser();

        unset(
            $_SESSION['password_reset_verified']
        );


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        setFlashMessageSafe(
            'success',
            'Password reset successfully. You can now login with your new password.'
        );


        redirect(
            BASE_URL . 'index.php'
        );


    } catch (PDOException $e) {

        if (
            $pdo->inTransaction()
        ) {

            $pdo->rollBack();
        }


        if (APP_DEBUG) {

            die(
                'Password reset error: '
                . e(
                    $e->getMessage()
                )
            );
        }


        setFlashMessageSafe(
            'error',
            'Unable to reset your password. Please try again.'
        );


        redirect(
            BASE_URL . 'auth/reset_password.php'
        );
    }
}