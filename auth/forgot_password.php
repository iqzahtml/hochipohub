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
            BASE_URL . 'auth/forgot_password.php'
        );
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        setFlashMessageSafe(
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

    $pdo = getDB();


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
            BASE_URL . 'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    if (
        isset($user['status']) &&
        strtolower($user['status']) !== 'active'
    ) {

        setFlashMessageSafe(
            'error',
            'This account is not currently active.'
        );

        redirect(
            BASE_URL . 'auth/forgot_password.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE RESET USER
    |--------------------------------------------------------------------------
    */

    $_SESSION['reset_user_id'] =
        (int) $user['user_id'];


    $_SESSION['reset_email'] =
        $user['email'];


    /*
    |--------------------------------------------------------------------------
    | SEND OTP
    |--------------------------------------------------------------------------
    */

    redirect(
        BASE_URL
        . 'auth/send_otp.php?type=reset'
    );
}


/*
|--------------------------------------------------------------------------
| FLASH
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


        .icon {

            width: 64px;

            height: 64px;

            border-radius: 18px;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            margin-bottom: 20px;
        }


        .eyebrow {

            font-size: 12px;

            font-weight: 700;

            color: #2563eb;

            letter-spacing: 2px;
        }


        h1 {

            margin:
                8px 0
                10px;

            color: #0f172a;
        }


        .description {

            color: #64748b;

            line-height: 1.6;

            margin-bottom: 25px;
        }


        .alert {

            padding: 13px 15px;

            border-radius: 10px;

            margin-bottom: 20px;

            background: #fef2f2;

            color: #b91c1c;
        }


        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            font-weight: 700;

            color: #334155;

            margin-bottom: 8px;
        }


        input {

            width: 100%;

            padding: 15px;

            border:
                2px solid
                #dbeafe;

            border-radius: 12px;

            font-size: 15px;

            outline: none;
        }


        input:focus {

            border-color: #2563eb;
        }


        button {

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


        button:hover {

            background: #1d4ed8;
        }


        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #2563eb;

            text-decoration: none;

            font-weight: 600;
        }

    </style>

</head>


<body>


<div class="forgot-container">


    <div class="icon">
        🔐
    </div>


    <span class="eyebrow">
        PASSWORD RESET
    </span>


    <h1>
        Forgot Your Password?
    </h1>


    <p class="description">

        Enter the email address registered
        to your HochipoHub account.
        We'll send you a 6-digit verification
        code.

    </p>


    <?php if ($flash): ?>

        <div class="alert">

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


        <button type="submit">

            SEND VERIFICATION CODE

        </button>

    </form>


    <a
        href="<?= BASE_URL ?>index.php"
        class="back-link"
    >
        ← Back to Login

    </a>


</div>


</body>

</html>