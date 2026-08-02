<?php
/*
|--------------------------------------------------------------------------
| HochipoHub - Login Modal
|--------------------------------------------------------------------------
*/
?>

<div
    class="modal-overlay"
    id="loginModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="loginModalTitle"
    hidden
>

    <div class="auth-modal login-modal">

        <!-- CLOSE -->

        <button
            type="button"
            class="modal-close"
            data-close-modal="loginModal"
            aria-label="Close login"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <!-- LEFT / VISUAL SIDE -->

        <div class="auth-modal-visual">

            <div class="auth-visual-decoration decoration-one"></div>
            <div class="auth-visual-decoration decoration-two"></div>
            <div class="auth-visual-decoration decoration-three"></div>

            <div class="auth-visual-content">

                <div class="auth-visual-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>

                <span class="auth-eyebrow">
                    WELCOME BACK
                </span>

                <h2>
                    Your next
                    <span>favourite</span>
                    find is waiting.
                </h2>

                <p>
                    Jump back into HochipoHub and discover
                    products from local vendors.
                </p>

                <div class="auth-mini-stats">

                    <div>
                        <strong>01</strong>
                        <span>Discover</span>
                    </div>

                    <div>
                        <strong>02</strong>
                        <span>Shop</span>
                    </div>

                    <div>
                        <strong>03</strong>
                        <span>Support</span>
                    </div>

                </div>

            </div>

        </div>


        <!-- RIGHT / FORM SIDE -->

        <div class="auth-modal-form">

            <div class="auth-form-header">

                <div class="mobile-auth-icon">

                    <i class="fa-solid fa-bag-shopping"></i>

                </div>

                <span class="auth-form-label">
                    HOCHIPOHUB ACCOUNT
                </span>

                <h2 id="loginModalTitle">
                    Welcome back.
                </h2>

                <p>
                    Login to continue your HochipoHub journey.
                </p>

            </div>


            <!-- LOGIN ERROR -->

            <div
                class="auth-alert auth-alert-error"
                id="loginAlert"
                role="alert"
                hidden
            >

                <i class="fa-solid fa-circle-exclamation"></i>

                <span id="loginAlertMessage">
                    Invalid email or password.
                </span>

            </div>


            <!-- LOGIN FORM -->

            <form
                action="<?php echo BASE_URL; ?>auth/login_process.php"
                method="POST"
                id="loginForm"
                class="auth-form"
                novalidate
            >

                <!-- CSRF -->

                <?php if (
                    function_exists('csrfField')
                ): ?>

                    <?php echo csrfField(); ?>

                <?php endif; ?>


                <!-- EMAIL -->

                <div class="form-group">

                    <label
                        for="loginEmail"
                    >
                        Email
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-regular fa-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            id="loginEmail"
                            placeholder="Enter your email"autocomplete="email"
                            maxlength="100"
                            required
                        >

                    </div>

                    <small
                        class="field-error"
                        id="loginEmailError"
                    ></small>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <div class="form-label-row">

                        <label
                            for="loginPassword"
                        >
                            Password
                        </label>

                        <button
                            type="button"
                            class="forgot-password-link"
                            data-open-modal="forgotPasswordModal"
                        >
                            Forgot password?
                        </button>

                    </div>


                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="loginPassword"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            maxlength="255"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-target="loginPassword"
                            aria-label="Show password"
                        >

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>

                    <small
                        class="field-error"
                        id="loginPasswordError"
                    ></small>

                </div>


                <!-- REMEMBER -->

                <div class="form-options">

                    <label class="checkbox-label">

                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                        >

                        <span class="custom-checkbox"></span>

                        <span>
                            Remember me
                        </span>

                    </label>

                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="auth-submit-btn"
                    id="loginSubmitBtn"
                >

                    <span class="btn-text">
                        Login to HochipoHub
                    </span>

                    <span
                        class="btn-loader"
                        hidden
                    >
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Logging in...
                    </span>

                    <i class="fa-solid fa-arrow-right btn-arrow"></i>

                </button>


                <!-- SECURITY NOTE -->

                <div class="auth-security-note">

                    <i class="fa-solid fa-shield-halved"></i>

                    <span>
                        Your account is protected with
                        secure authentication.
                    </span>

                </div>

            </form>


            <!-- REGISTER -->

            <div class="auth-switch">

                <span>
                    Don't have an account?
                </span>

                <button
                    type="button"
                    data-switch-modal="loginModal"
                    data-target-modal="registerModal"
                >
                    Create one
                    <i class="fa-solid fa-arrow-right"></i>
                </button></div>


            <!-- VENDOR CTA -->

            <div class="auth-vendor-cta">

                <div class="vendor-cta-icon">

                    <i class="fa-solid fa-store"></i>

                </div>

                <div>

                    <strong>
                        Want to sell on HochipoHub?
                    </strong>

                    <span>
                        Create a vendor account and grow your business.
                    </span>

                </div>

                <button
                    type="button"
                    data-switch-modal="loginModal"
                    data-target-modal="registerModal"
                    data-register-role="vendor"
                    aria-label="Become a vendor"
                >

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </div>

        </div>

    </div>

</div>


<!-- =============================================================
     FORGOT PASSWORD MODAL
============================================================== -->

<div
    class="modal-overlay"
    id="forgotPasswordModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="forgotPasswordTitle"
    hidden
>

    <div class="auth-small-modal">

        <button
            type="button"
            class="modal-close"
            data-close-modal="forgotPasswordModal"
            aria-label="Close forgot password"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="small-modal-icon">

            <i class="fa-solid fa-key"></i>

        </div>


        <div class="auth-form-header">

            <span class="auth-form-label">
                ACCOUNT RECOVERY
            </span>

            <h2 id="forgotPasswordTitle">
                Forgot your password?
            </h2>

            <p>
                Enter your email or phone number and
                we'll send you a verification code.
            </p>

        </div>


        <div
            class="auth-alert auth-alert-error"
            id="forgotAlert"
            hidden
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <span id="forgotAlertMessage"></span>

        </div>


        <form
            action="<?php echo BASE_URL; ?>auth/forgot_password.php"
            method="POST"
            id="forgotPasswordForm"
            class="auth-form"
            novalidate
        >

            <?php if (
                function_exists('csrfField')
            ): ?>

                <?php echo csrfField(); ?>

            <?php endif; ?>


            <div class="form-group">

                <label for="forgotIdentifier">
                    Email or phone number
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-at"></i>

                    <input
                        type="text"
                        name="identifier"
                        id="forgotIdentifier"
                        placeholder="Enter your email or phone"
                        autocomplete="email"
                        maxlength="100"
                        required
                    >

                </div>

                <small
                    class="field-error"
                    id="forgotIdentifierError"
                ></small>

            </div>


            <button
                type="submit"
                class="auth-submit-btn"
                id="forgotSubmitBtn"
            >

                <span class="btn-text">
                    Send Verification Code
                </span>

                <span
                    class="btn-loader"
                    hidden
                >

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    Sending...

                </span>

                <i class="fa-solid fa-paper-plane btn-arrow"></i>

            </button>

        </form>


        <div class="auth-back-link">

            <buttontype="button"
                data-switch-modal="forgotPasswordModal"
                data-target-modal="loginModal"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Login

            </button>

        </div>

    </div>

</div>


<!-- =============================================================
     MFA VERIFICATION MODAL
============================================================== -->

<div
    class="modal-overlay"
    id="mfaModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="mfaTitle"
    hidden
>

    <div class="auth-small-modal mfa-modal">

        <button
            type="button"
            class="modal-close"
            data-close-modal="mfaModal"
            aria-label="Close MFA verification"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="small-modal-icon mfa-icon">

            <i class="fa-solid fa-shield-halved"></i>

        </div>


        <div class="auth-form-header">

            <span class="auth-form-label">
                SECURITY CHECK
            </span>

            <h2 id="mfaTitle">
                Verify it's you.
            </h2>

            <p>
                Enter the 6-digit verification code
                sent to your registered contact.
            </p>

        </div>


        <div
            class="auth-alert auth-alert-error"
            id="mfaAlert"
            hidden
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <span id="mfaAlertMessage"></span>

        </div>


        <form
            action="<?php echo BASE_URL; ?>auth/verify_otp.php"
            method="POST"
            id="mfaForm"
            class="auth-form"
            novalidate
        >

            <?php if (
                function_exists('csrfField')
            ): ?>

                <?php echo csrfField(); ?>

            <?php endif; ?>


            <input
                type="hidden"
                name="verification_type"
                value="login"
            >


            <div class="otp-input-group">

                <input
                    type="text"
                    name="otp_1"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                >

                <input
                    type="text"
                    name="otp_2"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >

                <input
                    type="text"
                    name="otp_3"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >

                <input
                    type="text"
                    name="otp_4"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >

                <input
                    type="text"
                    name="otp_5"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >

                <input
                    type="text"
                    name="otp_6"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >

            </div>


            <button
                type="submit"
                class="auth-submit-btn"
                id="mfaSubmitBtn"
            >

                <span class="btn-text">
                    Verify Code
                </span>

                <span
                    class="btn-loader"
                    hidden
                ><i class="fa-solid fa-spinner fa-spin"></i>

                    Verifying...

                </span>

                <i class="fa-solid fa-arrow-right btn-arrow"></i>

            </button>


            <div class="otp-resend">

                <span>
                    Didn't receive the code?
                </span>

                <button
                    type="button"
                    id="resendOtpBtn"
                >
                    Resend code
                </button>

            </div>


            <div
                class="otp-countdown"
                id="otpCountdown"
                hidden
            >

                Code expires in
                <strong id="otpTimer">
                    05:00
                </strong>

            </div>

        </form>

    </div>

</div>


<!-- =============================================================
     RESET PASSWORD MODAL
============================================================== -->

<div
    class="modal-overlay"
    id="resetPasswordModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="resetPasswordTitle"
    hidden
>

    <div class="auth-small-modal">

        <button
            type="button"
            class="modal-close"
            data-close-modal="resetPasswordModal"
            aria-label="Close reset password"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="small-modal-icon">

            <i class="fa-solid fa-lock"></i>

        </div>


        <div class="auth-form-header">

            <span class="auth-form-label">
                NEW PASSWORD
            </span>

            <h2 id="resetPasswordTitle">
                Create a new password.
            </h2>

            <p>
                Choose a strong password for your account.
            </p>

        </div>


        <div
            class="auth-alert auth-alert-error"
            id="resetAlert"
            hidden
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <span id="resetAlertMessage"></span>

        </div>


        <form
            action="<?php echo BASE_URL; ?>auth/reset_password.php"
            method="POST"
            id="resetPasswordForm"
            class="auth-form"
            novalidate
        >

            <?php if (
                function_exists('csrfField')
            ): ?>

                <?php echo csrfField(); ?>

            <?php endif; ?>


            <input
                type="hidden"
                name="reset_token"
                id="resetToken"
                value=""
            >


            <!-- NEW PASSWORD -->

            <div class="form-group">

                <label for="newPassword">
                    New password
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        name="new_password"
                        id="newPassword"
                        placeholder="Create a new password"
                        autocomplete="new-password"
                        maxlength="255"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="newPassword"
                        aria-label="Show password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>

                <small
                    class="field-error"
                    id="newPasswordError"
                ></small>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirmPassword">
                    Confirm password
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-lock"></i><input
                        type="password"
                        name="confirm_password"
                        id="confirmPassword"
                        placeholder="Re-enter your password"
                        autocomplete="new-password"
                        maxlength="255"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="confirmPassword"
                        aria-label="Show password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>

                <small
                    class="field-error"
                    id="confirmPasswordError"
                ></small>

            </div>


            <!-- PASSWORD REQUIREMENTS -->

            <div class="password-requirements">

                <span class="requirements-title">
                    Password should contain:
                </span>


                <div
                    class="requirement"
                    id="reqLength"
                >

                    <i class="fa-regular fa-circle"></i>

                    At least 8 characters

                </div>


                <div
                    class="requirement"
                    id="reqUppercase"
                >

                    <i class="fa-regular fa-circle"></i>

                    One uppercase letter

                </div>


                <div
                    class="requirement"
                    id="reqNumber"
                >

                    <i class="fa-regular fa-circle"></i>

                    One number

                </div>

            </div>


            <button
                type="submit"
                class="auth-submit-btn"
                id="resetSubmitBtn"
            >

                <span class="btn-text">
                    Reset Password
                </span>

                <span
                    class="btn-loader"
                    hidden
                >

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    Resetting...

                </span>

                <i class="fa-solid fa-check btn-arrow"></i>

            </button>

        </form>

    </div>

</div>