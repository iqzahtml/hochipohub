<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FORGOT PASSWORD
|--------------------------------------------------------------------------
| File:
| auth/forgot_password.php
|
| Purpose:
| 1. Ask user for email
| 2. Find account
| 3. Save reset user ID into session
| 4. Redirect to send_otp.php
|
| FLOW:
|
| forgot_password.php
|        ↓
| send_otp.php?type=reset
|        ↓
| verify_otp.php?type=reset
|        ↓
| reset_password.php
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
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl =
    defined('BASE_URL')
        ? rtrim(
            BASE_URL,
            '/'
        ) . '/'
        : '/hochipoHub/';


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET')
    === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | GET EMAIL
    |--------------------------------------------------------------------------
    */

    $email =
        trim(
            $_POST['email'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        setFlashMessage(
            'error',
            'Please enter your email address.'
        );


        redirect(
            $baseUrl .
            'auth/forgot_password.php'
        );
    }


    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        setFlashMessage(
            'error',
            'Please enter a valid email address.'
        );


        redirect(
            $baseUrl .
            'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FIND USER
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->prepare("
                SELECT
                    user_id,
                    name,
                    email,
                    role,
                    status
                FROM users
                WHERE email = ?
                LIMIT 1
            ");


        $stmt->execute([
            $email
        ]);


        $user =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | DEBUG MODE
        |--------------------------------------------------------------------------
        */

        if (
            defined('APP_DEBUG') &&
            APP_DEBUG
        ) {

            die(
                'Forgot password database error: '
                . e(
                    $e->getMessage()
                )
            );
        }


        setFlashMessage(
            'error',
            'Unable to process your request. Please try again.'
        );


        redirect(
            $baseUrl .
            'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        setFlashMessage(
            'error',
            'No account was found with that email address.'
        );


        redirect(
            $baseUrl .
            'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $user['status']
        )
        &&
        strtolower(
            trim(
                (string)
                $user['status']
            )
        ) !== 'active'
    ) {

        setFlashMessage(
            'error',
            'This account is not active. Please contact support.'
        );


        redirect(
            $baseUrl .
            'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE RESET USER
    |--------------------------------------------------------------------------
    |
    | send_otp.php calls:
    |
    | getResetUser()
    |
    | getResetUser() reads:
    |
    | $_SESSION['reset_user_id']
    |
    |--------------------------------------------------------------------------
    */

    $resetSaved =
        setResetUser(
            $user['user_id']
        );


    if (!$resetSaved) {

        setFlashMessage(
            'error',
            'Unable to start password reset. Please try again.'
        );


        redirect(
            $baseUrl .
            'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE EMAIL
    |--------------------------------------------------------------------------
    */

    $_SESSION[
        'reset_email'
    ] =
        $user['email'];


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO SEND OTP
    |--------------------------------------------------------------------------
    */

    redirect(
        $baseUrl .
        'auth/send_otp.php?type=reset'
    );
}


/*
|--------------------------------------------------------------------------
| GET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = null;

if (
    function_exists(
        'getFlashMessage'
    )
) {

    $flash =
        getFlashMessage();
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

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
        Forgot Password -
        <?= e(
            defined('APP_NAME')
                ? APP_NAME
                : 'HochipoHub'
        ) ?>
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


        .forgot-container {

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


        .forgot-icon {

            width: 64px;

            height: 64px;

            border-radius: 18px;

            background:
                #2563eb;

            color:
                #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            margin-bottom: 22px;
        }


        .eyebrow {

            font-size: 12px;

            font-weight: 700;

            color:
                #2563eb;

            letter-spacing: 2px;
        }


        h1 {

            margin:
                8px 0 10px;

            color:
                #0f172a;

            font-size: 30px;
        }


        .description {

            color:
                #64748b;

            line-height: 1.6;

            margin-bottom: 25px;
        }


        .alert {

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            line-height: 1.5;
        }


        .alert.error {

            background:
                #fef2f2;

            color:
                #b91c1c;
        }


        .alert.success {

            background:
                #ecfdf5;

            color:
                #047857;
        }


        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 700;

            color:
                #334155;
        }


        input[type="email"] {

            width: 100%;

            padding: 15px;

            border:
                2px solid
                #dbeafe;

            border-radius: 12px;

            font-size: 15px;

            outline: none;

            transition:
                border-color
                0.2s;
        }


        input[type="email"]:focus {

            border-color:
                #2563eb;
        }


        button {

            width: 100%;

            border: none;

            padding: 16px;

            border-radius: 12px;

            background:
                #2563eb;

            color:
                #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition:
                background
                0.2s;
        }


        button:hover {

            background:
                #1d4ed8;
        }


        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            color:
                #2563eb;

            text-decoration: none;

            font-size: 14px;
        }


        .back-link:hover {

            text-decoration:
                underline;
        }

    </style>

</head>


<body>


<div class="forgot-container">


    <!-- ICON -->

    <div class="forgot-icon">

        🔐

    </div>


    <!-- EYEBROW -->

    <span class="eyebrow">

        PASSWORD RESET

    </span>


    <!-- TITLE -->

    <h1>

        Forgot Your Password?

    </h1>


    <!-- DESCRIPTION -->

    <p class="description">

        Enter the email address associated
        with your HochipoHub account.
        We'll send you a verification code
        to reset your password.

    </p>


    <!-- FLASH MESSAGE -->

    <?php if ($flash): ?>

        <div
            class="
                alert
                <?= (
                    ($flash['type'] ?? '')
                    === 'error'
                )
                    ? 'error'
                    : 'success'
                ?>
            "
        >

            <?= e(
                $flash['message']
                ?? ''
            ) ?>

        </div>

    <?php endif; ?>


    <!-- FORGOT PASSWORD FORM -->

    <form
        method="POST"
        action="<?= e(
            $baseUrl .
            'auth/forgot_password.php'
        ) ?>"
    >


        <div class="form-group">


            <label
                for="email"
            >

                Email Address

            </label>


            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                autocomplete="email"
                required
            >


        </div>


        <button
            type="submit"
        >

            SEND VERIFICATION CODE

        </button>


    </form>


    <!-- BACK TO LOGIN -->

    <a
        href="<?= e(
            $baseUrl .
            'index.php'
        ) ?>"
        class="back-link"
    >

        ← Back to Login

    </a>


</div>


</body>

</html>