<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - RESET PASSWORD
|--------------------------------------------------------------------------
| File:
| auth/reset_password.php
|
| Purpose:
| - Allow user to create a new password
| - Only accessible after successful OTP verification
| - Show success popup after password is changed
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CORE FILES
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| APPLICATION NAME
|--------------------------------------------------------------------------
*/

$appName = defined('SITE_NAME')
    ? SITE_NAME
    : 'HochipoHub';


/*
|--------------------------------------------------------------------------
| GET RESET USER
|--------------------------------------------------------------------------
*/

$resetUserId = getResetUser();


/*
|--------------------------------------------------------------------------
| CHECK RESET SESSION
|--------------------------------------------------------------------------
*/

if ($resetUserId === null) {

    setFlashMessageSafe(
        'error',
        'Password reset session expired. Please request a new reset code.'
    );

    redirect(
        BASE_URL . 'auth/forgot_password.php'
    );
}


/*
|--------------------------------------------------------------------------
| CHECK OTP VERIFICATION
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['password_reset_verified']
    )
    ||
    $_SESSION['password_reset_verified'] !== true
) {

    setFlashMessageSafe(
        'error',
        'Please verify the verification code first.'
    );

    redirect(
        BASE_URL
        . 'auth/verify_otp.php?type=reset'
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
| GET USER
|--------------------------------------------------------------------------
*/

$user = getUserById(
    $pdo,
    $resetUserId
);


if (!$user) {

    clearResetUser();

    unset(
        $_SESSION['password_reset_verified']
    );

    setFlashMessageSafe(
        'error',
        'User account could not be found.'
    );

    redirect(
        BASE_URL . 'auth/forgot_password.php'
    );
}


/*
|--------------------------------------------------------------------------
| SUCCESS STATE
|--------------------------------------------------------------------------
|
| This variable controls whether the success screen
| should be displayed after password reset.
|--------------------------------------------------------------------------
*/

$passwordResetSuccess = false;


/*
|--------------------------------------------------------------------------
| ERROR MESSAGE
|--------------------------------------------------------------------------
*/

$errorMessage = '';


/*
|--------------------------------------------------------------------------
| HANDLE PASSWORD RESET
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | GET FORM VALUES
    |--------------------------------------------------------------------------
    */

    $password =
        $_POST['password']
        ?? '';

    $confirmPassword =
        $_POST['confirm_password']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        $errorMessage =
            'Please enter your new password.';

    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD LENGTH
    |--------------------------------------------------------------------------
    */

    elseif (strlen($password) < 6) {

        $errorMessage =
            'Password must contain at least 6 characters.';

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM PASSWORD
    |--------------------------------------------------------------------------
    */

    elseif ($password !== $confirmPassword) {

        $errorMessage =
            'Passwords do not match.';

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        try {

            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | UPDATE USER PASSWORD
            |--------------------------------------------------------------------------
            */

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
                (int) $resetUserId
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
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
            | PASSWORD RESET SUCCESS
            |--------------------------------------------------------------------------
            */

            $passwordResetSuccess = true;


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
                defined('APP_DEBUG')
                &&
                APP_DEBUG
            ) {

                $errorMessage =
                    'Password reset database error: '
                    .
                    $e->getMessage();

            } else {

                $errorMessage =
                    'Unable to reset your password. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = null;

if (function_exists('getFlashMessage')) {

    $flash = getFlashMessage();

} elseif (function_exists('getFlash')) {

    $flash = getFlash();
}

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
        Reset Password - <?= e($appName) ?>
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

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

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN CONTAINER
        |--------------------------------------------------------------------------
        */

        .reset-container {

            width: 100%;

            max-width: 460px;

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


        /*
        |--------------------------------------------------------------------------
        | ICON
        |--------------------------------------------------------------------------
        */

        .reset-icon {

            width: 64px;

            height: 64px;

            border-radius: 18px;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            margin-bottom: 22px;
        }


        /*
        |--------------------------------------------------------------------------
        | TEXT
        |--------------------------------------------------------------------------
        */

        .eyebrow {

            font-size: 12px;

            font-weight: 700;

            color: #2563eb;

            letter-spacing: 2px;
        }


        h1 {

            margin:
                8px 0 10px;

            color: #0f172a;

            font-size: 30px;
        }


        .description {

            color: #64748b;

            line-height: 1.6;

            margin-bottom: 25px;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert {

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            line-height: 1.5;

            background: #fef2f2;

            color: #b91c1c;
        }


        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 700;

            color: #334155;
        }


        input[type="password"] {

            width: 100%;

            padding: 15px;

            border:
                2px solid #dbeafe;

            border-radius: 12px;

            font-size: 15px;

            outline: none;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }


        input[type="password"]:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    0.1
                );
        }


        .password-hint {

            margin-top: 7px;

            font-size: 12px;

            color: #64748b;
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .reset-button {

            width: 100%;

            border: none;

            padding: 16px;

            border-radius: 12px;

            background: #2563eb;

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s;
        }


        .reset-button:hover {

            background: #1d4ed8;
        }


        /*
        |--------------------------------------------------------------------------
        | BACK LINK
        |--------------------------------------------------------------------------
        */

        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #2563eb;

            text-decoration: none;

            font-size: 14px;
        }


        .back-link:hover {

            text-decoration: underline;
        }


        /*
        |--------------------------------------------------------------------------
        | SECURITY NOTE
        |--------------------------------------------------------------------------
        */

        .security-note {

            margin-top: 25px;

            padding: 14px;

            border-radius: 12px;

            background: #f8fafc;

            color: #64748b;

            font-size: 12px;

            line-height: 1.5;
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS OVERLAY
        |--------------------------------------------------------------------------
        */

        .success-overlay {

            position: fixed;

            inset: 0;

            background:
                rgba(
                    15,
                    23,
                    42,
                    0.55
                );

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            z-index: 9999;

            animation:
                fadeIn 0.25s ease;
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS MODAL
        |--------------------------------------------------------------------------
        */

        .success-modal {

            width: 100%;

            max-width: 430px;

            background: #ffffff;

            border-radius: 24px;

            padding: 40px 32px;

            text-align: center;

            box-shadow:
                0 25px 70px
                rgba(
                    15,
                    23,
                    42,
                    0.25
                );

            animation:
                popupIn 0.3s ease;
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS ICON
        |--------------------------------------------------------------------------
        */

        .success-icon {

            width: 76px;

            height: 76px;

            margin:
                0 auto 20px;

            border-radius: 50%;

            background: #dcfce7;

            color: #16a34a;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 38px;

            font-weight: 700;
        }


        .success-modal h2 {

            margin:
                0 0 10px;

            color: #0f172a;

            font-size: 25px;
        }


        .success-modal p {

            margin:
                0 0 25px;

            color: #64748b;

            line-height: 1.6;

            font-size: 15px;
        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN BUTTON
        |--------------------------------------------------------------------------
        */

        .login-button {

            display: block;

            width: 100%;

            padding: 15px;

            border-radius: 12px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            font-size: 15px;

            font-weight: 700;

            transition:
                background 0.2s;
        }


        .login-button:hover {

            background: #1d4ed8;
        }


        /*
        |--------------------------------------------------------------------------
        | ANIMATION
        |--------------------------------------------------------------------------
        */

        @keyframes fadeIn {

            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }

        }


        @keyframes popupIn {

            from {

                opacity: 0;

                transform:
                    translateY(20px)
                    scale(0.96);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }

        }

    </style>

</head>


<body>


<?php if ($passwordResetSuccess): ?>

    <!--
    |--------------------------------------------------------------------------
    | SUCCESS POPUP
    |--------------------------------------------------------------------------
    -->

    <div class="success-overlay">


        <div class="success-modal">


            <div class="success-icon">

                ✓

            </div>


            <h2>

                Password Changed Successfully!

            </h2>


            <p>

                Your HochipoHub password has been
                successfully updated.

                You can now use your new password
                to log in to your account.

            </p>


            <a
                href="<?= e(
                    BASE_URL . 'index.php'
                ) ?>"
                class="login-button"
            >

                BACK TO LOGIN

            </a>


        </div>


    </div>

<?php endif; ?>


<?php if (!$passwordResetSuccess): ?>


    <div class="reset-container">


        <div class="reset-icon">

            🔐

        </div>


        <span class="eyebrow">

            PASSWORD RESET

        </span>


        <h1>

            Create New Password

        </h1>


        <p class="description">

            Your verification code has been
            successfully verified.

            Create a new password for your
            HochipoHub account below.

        </p>


        <?php if ($errorMessage !== ''): ?>

            <div class="alert">

                <?= e(
                    $errorMessage
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($flash): ?>

            <div class="alert">

                <?= e(
                    $flash['message']
                    ?? ''
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="<?= e(
                BASE_URL
                . 'auth/reset_password.php'
            ) ?>"
        >


            <div class="form-group">


                <label
                    for="password"
                >

                    New Password

                </label>


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your new password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >


                <div class="password-hint">

                    Minimum 6 characters.

                </div>


            </div>


            <div class="form-group">


                <label
                    for="confirm_password"
                >

                    Confirm New Password

                </label>


                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your new password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >


            </div>


            <button
                type="submit"
                class="reset-button"
            >

                RESET PASSWORD

            </button>


        </form>


        <a
            href="<?= e(
                BASE_URL . 'index.php'
            ) ?>"
            class="back-link"
        >

            ← Back to Login

        </a>


        <div class="security-note">

            🔒 Your password is securely encrypted
            before being stored.

        </div>


    </div>

<?php endif; ?>


</body>

</html>