<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/functions.php";


if (!isset($_SESSION['reset_user_id'])) {

    header("Location: forgot_password.php");
    exit();

}


$userID =
    (int) $_SESSION['reset_user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $resetCode =
        trim($_POST['otp'] ?? '');


    if ($resetCode === '') {

        setFlashMessage(
            "error",
            "Please enter the OTP."
        );

    } else {


        /*
        |--------------------------------------------------------------------------
        | Find Valid Reset Code
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("

            SELECT
                reset_id,
                reset_code,
                expires_at

            FROM password_resets

            WHERE user_id = ?

            AND reset_code = ?

            AND used_at IS NULL

            AND expires_at > NOW()

            ORDER BY reset_id DESC

            LIMIT 1

        ");

        $stmt->bind_param(
            "is",
            $userID,
            $resetCode
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        if ($result->num_rows === 0) {

            setFlashMessage(
                "error",
                "Invalid or expired OTP."
            );

        } else {

            $reset =
                $result->fetch_assoc();


            /*
            |--------------------------------------------------------------------------
            | Mark OTP Verified
            |--------------------------------------------------------------------------
            */

            $_SESSION['reset_verified'] =
                true;

            $_SESSION['reset_id'] =
                $reset['reset_id'];


            setFlashMessage(
                "success",
                "OTP verified successfully."
            );


            header(
                "Location: reset_password.php"
            );

            exit();

        }

    }

}

?>

<?php include "../includes/header.php"; ?>

<section class="auth-page">

    <div class="auth-box">

        <h1>Verify OTP</h1>

        <p>
            Enter the 6-digit OTP sent to your email.
        </p>

        <form method="POST">

            <div class="form-group">

                <label for="otp">
                    OTP Code
                </label>

                <input
                    type="text"
                    id="otp"
                    name="otp"
                    maxlength="6"
                    minlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn-primary"
            >
                Verify OTP
            </button>

        </form>

    </div>

</section>

<?php include "../includes/footer.php"; ?>