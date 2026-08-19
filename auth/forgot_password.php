<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = getFlashMessage();


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

        setFlashMessage(
            'error',
            'Please enter your email address.'
        );

        redirect(
            BASE_URL . 'auth/forgot_password.php'
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
            BASE_URL . 'auth/forgot_password.php'
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

            setFlashMessage(
                'error',
                'No account was found with that email address.'
            );

            redirect(
                BASE_URL . 'auth/forgot_password.php'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK STATUS
        |--------------------------------------------------------------------------
        */

        if (
            isset($user['status']) &&
            strtolower(
                trim(
                    (string) $user['status']
                )
            ) !== 'active'
        ) {

            setFlashMessage(
                'error',
                'This account is not currently active.'
            );

            redirect(
                BASE_URL . 'auth/forgot_password.php'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE RESET USER
        |--------------------------------------------------------------------------
        */

        setResetUser(
            $user['user_id']
        );


        $_SESSION['reset_email'] =
            $user['email'];


        /*
        |--------------------------------------------------------------------------
        | SEND OTP
        |--------------------------------------------------------------------------
        */

        redirect(
            BASE_URL .
            'auth/send_otp.php?type=reset'
        );


    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | DEBUG
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
            'Something went wrong. Please try again.'
        );


        redirect(
            BASE_URL . 'auth/forgot_password.php'
        );
    }
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
        Forgot Password -
        <?= e(APP_NAME) ?>
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

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            margin-bottom: 20px;
        }


        .eyebrow {

            display: block;

            font-size: 12px;

            font-weight: 700;

            color: #2563eb;

            letter-spacing: 2px;

            margin-bottom: 8px;
        }


        h1 {

            margin: 0 0 12px;

            color: #0f172a;

            font-size: 30px;
        }


        .description {

            color: #64748b;

            line-height: 1.6;

            margin-bottom: 25px;
        }


        .alert {

            padding: 14px 16px;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 14px;
        }


        .alert-error {

            background: #fef2f2;

            color: #b91c1c;

            border:
                1px solid #fecaca;
        }


        .alert-success {

            background: #ecfdf5;

            color: #047857;

            border:
                1px solid #a7f3d0;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 700;

            color: #334155;
        }


        input {

            width: 100%;

            padding: 15px 16px;

            border:
                2px solid #dbeafe;

            border-radius: 12px;

            font-size: 15px;

            outline: none;

            margin-bottom: 18px;
        }


        input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(
                    37,
                    99,
                    235,
                    0.10
                );
        }


        .submit-button {

            width: 100%;

            border: none;

            padding: 16px;

            border-radius: 12px;

            background: #2563eb;

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;
        }


        .submit-button:hover {

            background: #1d4ed8;
        }


        .back-link {

            display: block;

            text-align: center;

            margin-top: 22px;

            color: #2563eb;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;
        }


        .back-link:hover {

            text-decoration: underline;
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
        We'll send you a 6-digit verification
        code to reset your password.

    </p>


    <?php if ($flash): ?>

        <div
            class="
                alert
                <?= $flash['type'] === 'error'
                    ? 'alert-error'
                    : 'alert-success'
                ?>
            "
        >

            <?= e(
                $flash['message']
            ) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="<?= e(
            BASE_URL .
            'auth/forgot_password.php'
        ) ?>"
    >

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


        <button
            type="submit"
            class="submit-button"
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