<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Register Modal
|--------------------------------------------------------------------------
*/

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

?>

<!-- =========================================================
     REGISTER MODAL
========================================================= -->

<div
    class="modal-overlay"
    id="registerModal"
    aria-hidden="true"
>

    <div
        class="modal-container register-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="registerModalTitle"
    >


        <!-- =================================================
             CLOSE BUTTON
        ================================================== -->

        <button
            type="button"
            class="modal-close"
            data-modal-close="registerModal"
            aria-label="Close registration"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <!-- =================================================
             REGISTER HEADER
        ================================================== -->

        <div class="modal-header">

            <div class="modal-icon">

                <i class="fa-solid fa-user-plus"></i>

            </div>


            <div>

                <h2 id="registerModalTitle">
                    Join HochipoHub
                </h2>

                <p>
                    Create your account and start exploring.
                </p>

            </div>

        </div>


        <!-- =================================================
             REGISTER FORM
        ================================================== -->

        <form
            action="<?php echo BASE_URL; ?>auth/register_process.php"
            method="POST"
            id="registerForm"
            class="auth-form"
            novalidate
        >


            <!-- =================================================
                 CSRF
            ================================================== -->

            <?php if (
                function_exists('csrfToken')
            ): ?>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars(
                        csrfToken(),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            <?php endif; ?>


            <!-- =================================================
                 FULL NAME
            ================================================== -->

            <div class="form-group">

                <label
                    for="registerName"
                >

                    <i class="fa-solid fa-user"></i>

                    Full Name

                </label>


                <div class="input-wrapper">

                    <i class="fa-regular fa-user input-icon"></i>

                    <input
                        type="text"
                        id="registerName"
                        name="name"
                        placeholder="Enter your full name"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >

                </div>


                <small
                    class="form-error"
                    id="registerNameError"
                ></small>

            </div>


            <!-- =================================================
                 EMAIL
            ================================================== -->

            <div class="form-group">

                <label
                    for="registerEmail"
                >

                    <i class="fa-solid fa-envelope"></i>

                    Email Address

                </label>


                <div class="input-wrapper">

                    <i class="fa-regular fa-envelope input-icon"></i>

                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        placeholder="Enter your email"
                        maxlength="100"
                        autocomplete="email"
                        required
                    >

                </div>


                <small
                    class="form-error"
                    id="registerEmailError"
                ></small>

            </div>


            <!-- =================================================
                 PHONE
            ================================================== -->

            <div class="form-group">

                <label
                    for="registerPhone"
                >

                    <i class="fa-solid fa-phone"></i>

                    Phone Number

                    <span class="optional-label">
                        Optional
                    </span>

                </label>


                <div class="input-wrapper">

                    <i class="fa-solid fa-phone input-icon"></i>

                    <input
                        type="tel"
                        id="registerPhone"
                        name="phone"
                        placeholder="e.g. 0123456789"
                        maxlength="20"
                        autocomplete="tel"
                    >

                </div>


                <small
                    class="form-error"
                    id="registerPhoneError"
                ></small>

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================== -->

            <div class="form-group">

                <label
                    for="registerPassword"
                >

                    <i class="fa-solid fa-lock"></i>

                    Password

                </label>


                <div class="input-wrapper">

                    <i class="fa-solid fa-lock input-icon"></i>


                    <input
                        type="password"
                        id="registerPassword"
                        name="password"
                        placeholder="Create a password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="registerPassword"
                        aria-label="Show password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>


                <!-- PASSWORD STRENGTH -->

                <div
                    class="password-strength"
                    id="registerPasswordStrength"
                >

                    <div class="strength-bar">

                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>

                    </div>


                    <small
                        class="strength-text"
                        id="passwordStrengthText"
                    >
                        Use at least 8 characters.
                    </small>

                </div>


                <small
                    class="form-error"
                    id="registerPasswordError"
                ></small>

            </div>


            <!-- =================================================
                 CONFIRM PASSWORD
            ================================================== -->

            <div class="form-group">

                <label
                    for="registerConfirmPassword"
                >

                    <i class="fa-solid fa-shield-halved"></i>

                    Confirm Password

                </label>


                <div class="input-wrapper">

                    <i class="fa-solid fa-lock input-icon"></i>


                    <input
                        type="password"
                        id="registerConfirmPassword"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="registerConfirmPassword"
                        aria-label="Show password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>


                <small
                    class="form-error"
                    id="registerConfirmPasswordError"
                ></small>

            </div>


            <!-- =================================================
                 TERMS
            ================================================== -->

            <div class="form-options">

                <label
                    class="checkbox-label terms-checkbox"
                >

                    <input
                        type="checkbox"
                        name="agree_terms"
                        value="1"
                        required
                    >

                    <span class="custom-checkbox"></span>


                    <span>

                        I agree to the
                        <a href="#">
                            Terms &amp; Conditions
                        </a>
                        and
                        <a href="#">
                            Privacy Policy
                        </a>.

                    </span>

                </label>


                <small
                    class="form-error"
                    id="registerTermsError"
                ></small>

            </div>


            <!-- =================================================
                 SUBMIT
            ================================================== -->

            <button
                type="submit"
                class="auth-submit-btn"
                id="registerSubmitBtn"
            >

                <span class="btn-text">

                    Create Account

                    <i class="fa-solid fa-arrow-right"></i>

                </span>


                <span
                    class="btn-loading"
                    hidden
                >

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    Creating account...

                </span>

            </button>


            <!-- =================================================
                 LOGIN LINK
            ================================================== -->

            <div class="auth-switch">

                <span>
                    Already have an account?
                </span>


                <button
                    type="button"
                    class="auth-switch-btn"
                    data-modal-open="loginModal"
                    data-modal-close="registerModal"
                >

                    Login here

                </button>

            </div>

        </form>


        <!-- =================================================
             REGISTER STATUS
        ================================================== -->

        <div
            class="auth-message"
            id="registerMessage"
            role="alert"
            aria-live="polite"
        ></div>

    </div>

</div>