<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VERIFY OTP
|--------------------------------------------------------------------------
| File:
| auth/verify_otp.php
|
| Supported:
|
| type=reset
| type=mfa
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';


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


if (
    !in_array(
        $type,
        ['reset', 'mfa'],
        true
    )
) {

    setFlashMessageSafe(
        'error',
        'Invalid verification request.'
    );

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

if ($type === 'reset') {

    $userId =
        getResetUser();

} else {

    $userId =
        getMfaPendingUser();
}


if ($userId === null) {

    setFlashMessageSafe(
        'error',
        'Verification session has expired.'
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

$pdo = getDB();


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
| HANDLE POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code =
        trim(
            $_POST['otp']
            ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $code === ''
        ||
        !preg_match(
            '/^[0-9]{6}$/',
            $code
        )
    ) {

        setFlashMessageSafe(
            'error',
            'Please enter the 6-digit verification code.'
        );

        redirect(
            BASE_URL
            . 'auth/verify_otp.php?type='
            . urlencode($type)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD RESET OTP
    |--------------------------------------------------------------------------
    */

    if ($type === 'reset') {

        $stmt =
            $pdo->prepare("
                SELECT
                    reset_id,
                    user_id,
                    reset_code,
                    expires_at,
                    used_at
                FROM password_resets
                WHERE user_id = ?
                AND reset_code = ?
                AND used_at IS NULL
                ORDER BY reset_id DESC
                LIMIT 1
            ");


        $stmt->execute([
            $userId,
            $code
        ]);


        $reset =
            $stmt->fetch();


        if (!$reset) {

            setFlashMessageSafe(
                'error',
                'Invalid or already used reset code.'
            );

            redirect(
                BASE_URL
                . 'auth/verify_otp.php?type=reset'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK EXPIRY
        |--------------------------------------------------------------------------
        */

        if (
            strtotime(
                $reset['expires_at']
            ) < time()
        ) {

            setFlashMessageSafe(
                'error',
                'The reset code has expired. Please request a new code.'
            );

            redirect(
                BASE_URL
                . 'auth/verify_otp.php?type=reset'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MARK RESET CODE AS USED
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            $stmt =
                $pdo->prepare("
                    UPDATE password_resets
                    SET used_at = CURRENT_TIMESTAMP
                    WHERE reset_id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $reset['reset_id']
            ]);


            $stmt =
                $pdo->prepare("
                    UPDATE users
                    SET
                        reset_code = NULL,
                        reset_expiry = NULL
                    WHERE user_id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $userId
            ]);


            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | MARK RESET VERIFIED
            |--------------------------------------------------------------------------
            */

            $_SESSION[
                'password_reset_verified'
            ] = true;


            /*
            |--------------------------------------------------------------------------
            | REDIRECT TO RESET PASSWORD
            |--------------------------------------------------------------------------
            */

            redirect(
                BASE_URL
                . 'auth/reset_password.php'
            );


        } catch (PDOException $e) {

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();
            }


            if (APP_DEBUG) {

                die(
                    'OTP verification error: '
                    . e(
                        $e->getMessage()
                    )
                );
            }


            setFlashMessageSafe(
                'error',
                'Unable to verify the reset code.'
            );


            redirect(
                BASE_URL
                . 'auth/verify_otp.php?type=reset'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MFA OTP
    |--------------------------------------------------------------------------
    */

    if ($type === 'mfa') {

        $stmt =
            $pdo->prepare("
                SELECT
                    id,
                    user_id,
                    code,
                    expires_at,
                    used_at
                FROM mfa_codes
                WHERE user_id = ?
                AND code = ?
                AND used_at IS NULL
                ORDER BY id DESC
                LIMIT 1
            ");


        $stmt->execute([
            $userId,
            $code
        ]);


        $mfa =
            $stmt->fetch();


        if (!$mfa) {

            setFlashMessageSafe(
                'error',
                'Invalid or already used MFA code.'
            );

            redirect(
                BASE_URL
                . 'auth/verify_otp.php?type=mfa'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK EXPIRY
        |--------------------------------------------------------------------------
        */

        if (
            strtotime(
                $mfa['expires_at']
            ) < time()
        ) {

            setFlashMessageSafe(
                'error',
                'The MFA code has expired. Please request a new code.'
            );

            redirect(
                BASE_URL
                . 'auth/verify_otp.php?type=mfa'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MARK MFA AS USED
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            $stmt =
                $pdo->prepare("
                    UPDATE mfa_codes
                    SET used_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $mfa['id']
            ]);


            $stmt =
                $pdo->prepare("
                    UPDATE users
                    SET
                        mfa_code = NULL,
                        mfa_expiry = NULL
                    WHERE user_id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $userId
            ]);


            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | MARK MFA VERIFIED
            |--------------------------------------------------------------------------
            */

            markMfaVerified();


            /*
            |--------------------------------------------------------------------------
            | COMPLETE LOGIN
            |--------------------------------------------------------------------------
            |
            | If login_process.php stored the user temporarily
            | in mfa_pending_user, fetch the user and login.
            |--------------------------------------------------------------------------
            */

            loginUser(
                $user
            );


            clearMfaPendingUser();


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            setFlashMessageSafe(
                'success',
                'Login verification successful.'
            );


            /*
            |--------------------------------------------------------------------------
            | ROLE-BASED REDIRECT
            |--------------------------------------------------------------------------
            */

            if (
                $user['role'] === 'admin'
            ) {

                redirect(
                    BASE_URL
                    . 'admin/dashboard.php'
                );
            }


            redirect(
                BASE_URL
                . 'dashboard.php'
            );


        } catch (PDOException $e) {

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();
            }


            if (APP_DEBUG) {

                die(
                    'MFA verification error: '
                    . e(
                        $e->getMessage()
                    )
                );
            }


            setFlashMessageSafe(
                'error',
                'Unable to verify MFA code.'
            );


            redirect(
                BASE_URL
                . 'auth/verify_otp.php?type=mfa'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash =
    getFlashMessage();


/*
|--------------------------------------------------------------------------
| OTP PAGE
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
        Verify OTP - <?= e(APP_NAME) ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .otp-container {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 22px;
            padding: 40px;
            box-shadow:
                0 20px 50px
                rgba(15, 23, 42, 0.12);
        }

        .otp-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .eyebrow {
            font-size: 12px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 2px;
        }

        h1 {
            margin: 8px 0;
            color: #0f172a;
        }

        .description {
            color: #64748b;
            line-height: 1.6;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 10px;
            margin: 20px 0;
            background: #eff6ff;
            color: #1e40af;
        }

        .alert.error {
            background: #fef2f2;
            color: #b91c1c;
        }

        .otp-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #dbeafe;
            border-radius: 12px;
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 8px;
            outline: none;
        }

        .otp-input:focus {
            border-color: #2563eb;
        }

        .submit-button {
            width: 100%;
            border: none;
            margin-top: 18px;
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
            margin-top: 20px;
            color: #2563eb;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="otp-container">

    <div class="otp-icon">
        <?= $type === 'reset' ? '↻' : '✓' ?>
    </div>


    <span class="eyebrow">
        <?= $type === 'reset'
            ? 'PASSWORD RESET'
            : 'LOGIN VERIFICATION'
        ?>
    </span>


    <h1>
        Verify Your Code
    </h1>


    <p class="description">

        We sent a 6-digit verification code
        to your registered email address.

    </p>


    <?php if ($flash): ?>

        <div
            class="alert <?= $flash['type'] === 'error'
                ? 'error'
                : ''
            ?>"
        >

            <?= e(
                $flash['message']
            ) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="<?= BASE_URL ?>auth/verify_otp.php"
    >

        <input
            type="hidden"
            name="type"
            value="<?= e($type) ?>"
        >


        <input
            type="text"
            name="otp"
            class="otp-input"
            placeholder="000000"
            maxlength="6"
            pattern="[0-9]{6}"
            inputmode="numeric"
            autocomplete="one-time-code"
            required
        >


        <button
            type="submit"
            class="submit-button"
        >
            VERIFY CODE
        </button>

    </form>


    <a
        href="<?= BASE_URL ?>auth/send_otp.php?type=<?= e($type) ?>"
        class="back-link"
    >
        Didn't receive the code? Send again
    </a>

</div>

</body>

</html>