<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/functions.php";


/*
|--------------------------------------------------------------------------
| Check Verification
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['reset_verified']) ||
    $_SESSION['reset_verified'] !== true ||
    !isset($_SESSION['reset_id']) ||
    !isset($_SESSION['reset_user_id'])
) {

    header("Location: forgot_password.php");
    exit();

}


$resetID =
    (int) $_SESSION['reset_id'];

$userID =
    (int) $_SESSION['reset_user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $password =
        $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (strlen($password) < 8) {

        setFlashMessage(
            "error",
            "Password must be at least 8 characters."
        );

    } elseif ($password !== $confirmPassword) {

        setFlashMessage(
            "error",
            "Passwords do not match."
        );

    } else {


        /*
        |--------------------------------------------------------------------------
        | Hash Password
        |--------------------------------------------------------------------------
        */

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        /*
        |--------------------------------------------------------------------------
        | Update User Password
        |--------------------------------------------------------------------------
        */

        $update = $conn->prepare("

            UPDATE users

            SET
                password = ?,
                reset_code = NULL,
                reset_expiry = NULL

            WHERE user_id = ?

        ");

        $update->bind_param(
            "si",
            $hashedPassword,
            $userID
        );


        if ($update->execute()) {


            /*
            |--------------------------------------------------------------------------
            | Mark Reset Code Used
            |--------------------------------------------------------------------------
            */

            $markUsed = $conn->prepare("

                UPDATE password_resets

                SET used_at = NOW()

                WHERE reset_id = ?

            ");

            $markUsed->bind_param(
                "i",
                $resetID
            );

            $markUsed->execute();


            /*
            |--------------------------------------------------------------------------
            | Clear Reset Session
            |--------------------------------------------------------------------------
            */

            unset(
                $_SESSION['reset_user_id'],
                $_SESSION['reset_email'],
                $_SESSION['reset_verified'],
                $_SESSION['reset_id']
            );


            setFlashMessage(
                "success",
                "Password changed successfully. Please login."
            );


            header(
                "Location: " .
                BASE_URL .
                "index.php"
            );

            exit();

        }


        setFlashMessage(
            "error",
            "Unable to reset password."
        );

    }

}

?>

<?php include "../includes/header.php"; ?>

<section class="auth-page">

    <div class="auth-box">

        <h1>Create New Password</h1>

        <p>
            Enter your new password below.
        </p>

        <form method="POST">

            <div class="form-group">

                <label for="password">
                    New Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn-primary"
            >
                Reset Password
            </button>

        </form>

    </div>

</section>

<?php include "../includes/footer.php"; ?>