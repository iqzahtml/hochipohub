<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FORGOT PASSWORD
|--------------------------------------------------------------------------
| File:
|     auth/forgot_password.php
|
| Flow:
|
| Login
|   ↓
| Forgot Password
|   ↓
| Enter Email
|   ↓
| Send OTP
|   ↓
| Verify OTP
|   ↓
| Reset Password
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '/hochipohub/';


/*
|--------------------------------------------------------------------------
| HANDLE FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim(
        $_POST['email'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        setFlashMessageSafe(
            'error',
            'Please enter your email address.'
        );

        redirect(
            $baseUrl . 'auth/forgot_password.php'
        );
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        setFlashMessageSafe(
            'error',
            'Please enter a valid email address.'
        );

        redirect(
            $baseUrl . 'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATABASE
    |--------------------------------------------------------------------------
    */

    try {

        $pdo = getDB();


        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
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

        $user = $stmt->fetch(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | USER NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            setFlashMessageSafe(
                'error',
                'No account was found with that email address.'
            );

            redirect(
                $baseUrl . 'auth/forgot_password.php'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK ACCOUNT STATUS
        |--------------------------------------------------------------------------
        */

        if (
            isset($user['status']) &&
            strtolower(
                trim(
                    $user['status']
                )
            ) !== 'active'
        ) {

            setFlashMessageSafe(
                'error',
                'This account is not active. Please contact support.'
            );

            redirect(
                $baseUrl . 'auth/forgot_password.php'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | STORE RESET USER
        |--------------------------------------------------------------------------
        */

        setResetUser(
            $user['user_id']
        );


        /*
        |--------------------------------------------------------------------------
        | STORE EMAIL FOR DISPLAY
        |--------------------------------------------------------------------------
        */

        $_SESSION['reset_email'] =
            $user['email'];


        /*
        |--------------------------------------------------------------------------
        | REDIRECT TO SEND OTP
        |--------------------------------------------------------------------------
        */

        redirect(
            $baseUrl
            . 'auth/send_otp.php?type=reset'
        );

    } catch (PDOException $e) {

        if (defined('APP_DEBUG') && APP_DEBUG) {

            die(
                'Forgot password database error: '
                . e(
                    $e->getMessage()
                )
            );
        }


        setFlashMessageSafe(
            'error',
            'Something went wrong. Please try again.'
        );

        redirect(
            $baseUrl . 'auth/forgot_password.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = getFlashMessage();

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
        Forgot Password - <?= e(APP_NAME) ?>
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
                    #eef2ff
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

            border-radius: 24px;

            padding: 40px;

            box-shadow:
                0 25px 70px
                rgba(15, 23, 42, 0.15);
        }


        .forgot-icon {

            width: 64px;

            height: 64px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 20px;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #ffffff;

            font-size: 28px;

            box-shadow:
                0 10px 25px
                rgba(37, 99, 235, 0.25);
        }


        .eyebrow {

            display: block;

            margin-bottom: 7px;

            color: #2563eb;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 1.5px;
        }


        h1 {

            margin: 0 0 10px;

            color: #0f172a;

            font-size: 28px;

            font-weight: 800;
        }


        .description {

            margin: 0 0 25px;

            color: #64748b;

            font-size: 14px;

            line-height: 1.6;
        }


        .alert {

            padding: 13px 15px;

            margin-bottom: 20px;

            border-radius: 12px;

            font-size: 13px;

            line-height: 1.5;
        }


        .alert.error {

            background: #fef2f2;

            border:
                1px solid
                #fecaca;

            color: #b91c1c;
        }


        .alert.success {

            background: #ecfdf5;

            border:
                1px solid
                #a7f3d0;

            color: #047857;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: #334155;

            font-size: 13px;

            font-weight: 700;
        }


        .form-group input {

            width: 100%;

            height: 48px;

            padding: 0 14px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 12px;

            background: #f8fafc;

            color: #0f172a;

            font-size: 14px;

            outline: none;

            transition: 0.2s ease;
        }


        .form-group input:focus {

            background: #ffffff;

            border-color: #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(37, 99, 235, 0.10);
        }


        .submit-button {

            width: 100%;

            min-height: 48px;

            border: none;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #ffffff;

            font-size: 14px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.25);

            transition: 0.2s ease;
        }


        .submit-button:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 28px
                rgba(37, 99, 235, 0.32);
        }


        .back-link {

            display: block;

            margin-top: 20px;

            text-align: center;

            color: #2563eb;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;
        }


        .back-link:hover {

            text-decoration: underline;
        }


        @media (max-width: 600px) {

            .forgot-container {

                padding: 28px 22px;

                border-radius: 20px;
            }

            h1 {

                font-size: 24px;
            }
        }

    </style>

</head>


<body>


<div class="forgot-container">


    <div class="forgot-icon">

        🔐

    </div>


    <span class="eyebrow">

        ACCOUNT RECOVERY

    </span>


    <h1>

        Forgot Password?

    </h1>


    <p class="description">

        Enter the email address connected
        to your HochipoHub account.
        We'll send you a 6-digit verification
        code to reset your password.

    </p>


    <?php if ($flash): ?>

        <div
            class="alert <?= $flash['type'] === 'error'
                ? 'error'
                : 'success'
            ?>"
        >

            <?= e(
                $flash['message']
            ) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="<?= e(
            $baseUrl . 'auth/forgot_password.php'
        ) ?>"
    >


        <div class="form-group">

            <label for="forgotEmail">

                Email Address

            </label>


            <input
                type="email"
                id="forgotEmail"
                name="email"
                placeholder="you@example.com"
                autocomplete="email"
                required
            >

        </div>


        <button
            type="submit"
            class="submit-button"
        >

            SEND VERIFICATION CODE →

        </button>


    </form>


    <a
        href="<?= e($baseUrl . 'index.php') ?>"
        class="back-link"
    >

        ← Back to Login

    </a>


</div>


</body>

</html>