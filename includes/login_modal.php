<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Login Modal
|--------------------------------------------------------------------------
*/

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

?>

<!-- =========================================================
     LOGIN MODAL
========================================================= -->

<div
    class="modal-overlay"
    id="loginModal"
    aria-hidden="true"
>

    <div
        class="modal-container login-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="loginModalTitle"
    >


        <!-- =================================================
             CLOSE BUTTON
        ================================================== -->

        <button
            type="button"
            class="modal-close"
            data-modal-close="loginModal"
            aria-label="Close login"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <!-- =================================================
             LOGIN HEADER
        ================================================== -->

        <div class="modal-header">

            <div class="modal-icon">

                <i class="fa-solid fa-right-to-bracket"></i>

            </div>


            <div>

                <h2 id="loginModalTitle">
                    Welcome Back!
                </h2>

                <p>
                    Login to continue your HochipoHub journey.
                </p>

            </div>

        </div>


        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            action="<?php echo BASE_URL; ?>auth/login_process.php"
            method="POST"
            id="loginForm"
            class="auth-form"
            novalidate
        >


            <!-- CSRF -->

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
                 EMAIL
            ================================================== -->

            <div class="form-group">

                <label
                    for="loginEmail"
                >

                    <i class="fa-solid fa-envelope"></i>

                    Email Address

                </label>


                <div class="input-wrapper">

                    <i class="fa-regular fa-envelope input-icon"></i>

                    <input
                        type="email"
                        id="loginEmail"
                        name="email"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                    >

                </div>


                <small
                    class="form-error"
                    id="loginEmailError"
                ></small>

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================== -->

            <div class="form-group">

                <div class="form-label-row">

                    <label
                        for="loginPassword"
                    >

                        <i class="fa-solid fa-lock"></i>

                        Password

                    </label>


                    <a
                        href="<?php echo BASE_URL; ?>auth/forgot_password.php"
                        class="forgot-password-link"
                    >
                        Forgot password?
                    </a>

                </div>


                <div class="input-wrapper">

                    <i class="fa-solid fa-lock input-icon"></i>


                    <input
                        type="password"
                        id="loginPassword"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="loginPassword"
                        aria-label="Show password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>


                <small
                    class="form-error"
                    id="loginPasswordError"
                ></small>

            </div>


            <!-- =================================================
                 REMEMBER ME
            ================================================== -->

            <div class="form-options">

                <label
                    class="checkbox-label"
                >

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


            <!-- =================================================
                 SUBMIT
            ================================================== -->

            <button
                type="submit"
                class="auth-submit-btn"
                id="loginSubmitBtn"
            >

                <span class="btn-text">
                    Login
                </span>

                <span
                    class="btn-loading"
                    hidden
                >

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    Signing in...

                </span>

            </button>


            <!-- =================================================
                 REGISTER LINK
            ================================================== -->

            <div class="auth-switch">

                <span>
                    Don't have an account?
                </span>

                <button
                    type="button"
                    class="auth-switch-btn"
                    data-modal-open="registerModal"
                    data-modal-close="loginModal"
                >
                    Create one
                </button>

            </div>

        </form>


        <!-- =================================================
             LOGIN STATUS
        ================================================== -->

        <div
            class="auth-message"
            id="loginMessage"
            role="alert"
            aria-live="polite"
        ></div>

    </div>

</div>