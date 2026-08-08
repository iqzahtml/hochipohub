<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/functions.php";

$pageTitle = "Forgot Password";

?>

<?php include "../includes/header.php"; ?>

<section class="auth-page">

    <div class="auth-box">

        <h1>Forgot Password</h1>

        <p>
            Enter your registered email address to receive a reset code.
        </p>

        <form action="send_otp.php" method="POST">

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                >

            </div>

            <button
                type="submit"
                class="btn-primary"
            >
                Send OTP
            </button>

        </form>

    </div>

</section>

<?php include "../includes/footer.php"; ?>