<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FORGOT PASSWORD
|--------------------------------------------------------------------------
| File:
| auth/forgot_password.php
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) .
    '/includes/session.php';

require_once dirname(__DIR__) .
    '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$pdo = getDB();


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash =
    getFlashMessageSafe();


/*
|--------------------------------------------------------------------------
| HANDLE FORM
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET')
    ===
    'POST'
) {

    $email =
        trim(
            $_POST['email']
            ??
            ''
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */

    if (
        $email === ''
    ) {

        setFlashMessageSafe(
            'error',
            'Please enter your email address.'
        );

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        setFlashMessageSafe(
            'error',
            'Please enter a valid email address.'
        );

    } else {


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


        } catch (
            PDOException $e
        ) {

            if (APP_DEBUG) {

                die(
                    '<h3>Database Error</h3>'
                    .
                    e(
                        $e->getMessage()
                    )
                );
            }


            setFlashMessageSafe(
                'error',
                'Unable to start password reset.'
            );


            $user = null;
        }


        /*
        |--------------------------------------------------------------------------
        | USER NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (
            $user === false
            ||
            $user === null
        ) {

            setFlashMessageSafe(
                'error',
                'No account was found with that email address.'
            );

        } else {


            /*
            |--------------------------------------------------------------------------
            | ACCOUNT STATUS
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $user['status']
                )
                &&
                strtolower(
                    $user['status']
                )
                !==
                'active'
            ) {

                setFlashMessageSafe(
                    'error',
                    'This account is not active. Please contact support.'
                );

            } else {


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
                | REDIRECT TO SEND OTP
                |--------------------------------------------------------------------------
                */

                redirect(
                    BASE_URL
                    .
                    'auth/send_otp.php?type=reset'
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET FLASH AGAIN
    |--------------------------------------------------------------------------
    */

    $flash =
        getFlashMessageSafe();
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
        Forgot Password - HochipoHub
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

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            margin-bottom: 22px;
        }


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


        .alert {

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;
        }


        .alert.error {

            background: #fef2f2;

            color: #b91c1c;
        }


        .alert.success {

            background: #ecfdf5;

            color: #047857;
        }


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


        input[type="email"] {

            width: 100%;

            padding: 15px;

            border:
                2px solid #dbeafe;

            border-radius: 12px;

            font-size: 15px;

            outline: none;
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

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;
        }


        button:hover {

            background:
                #1d4ed8;
        }


        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #2563eb;

            text-decoration: none;

            font-size: 14px;
        }

    </style>

</head>


<body>


<div class="forgot-container">


    <div class="forgot-icon">

        🔐

    </div>


    <span class="eyebrow">

        PASSWORD RESET

    </span>


    <h1>

        Forgot Your Password?

    </h1>


    <p class="description">

        Enter the email address associated
        with your HochipoHub account.
        We'll send you a verification code
        to reset your password.

    </p>


    <?php if ($flash): ?>

        <div
            class="alert
            <?= e(
                $flash['type']
                === 'success'
                ? 'success'
                : 'error'
            ) ?>"
        >

            <?= e(
                $flash['message']
            ) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action=""
    >

        <div class="form-group">

            <label for="email">

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


    <a
        href="<?= e(
            BASE_URL . 'index.php'
        ) ?>"
        class="back-link"
    >

        ← Back to Login

    </a>


</div>


</body>

</html>