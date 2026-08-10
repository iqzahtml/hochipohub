<?php
/*
 * HOCHIPOHUB
 * includes/login_modal.php
 *
 * Login modal
 * Handles:
 * - Email
 * - Password
 * - Remember me
 * - Login form submission to auth/login_process.php
 * - Link to forgot password
 * - Link to register
 */
?>

<div class="login-modal-overlay" id="loginModal">

    <div class="login-modal">

        <!-- CLOSE BUTTON -->
        <button
            type="button"
            class="login-modal-close"
            id="closeLoginModal"
            aria-label="Close login modal">
            &times;
        </button>

        <!-- HEADER -->
        <div class="login-modal-header">

            <div class="login-modal-icon">
                <i class="fas fa-user-circle"></i>
            </div>

            <h2>Welcome Back!</h2>

            <p>
                Login to continue shopping on HochipoHub.
            </p>

        </div>


        <!-- LOGIN FORM -->
        <form
            action="auth/login_process.php"
            method="POST"
            id="loginForm"
            class="login-form"
            novalidate>

            <!-- EMAIL -->
            <div class="form-group">

                <label for="loginEmail">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-envelope"></i>

                    <input
                        type="email"
                        name="email"
                        id="loginEmail"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required>

                </div>

                <small
                    class="form-error"
                    id="loginEmailError">
                </small>

            </div>


            <!-- PASSWORD -->
            <div class="form-group">

                <label for="loginPassword">
                    Password
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        name="password"
                        id="loginPassword"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        id="toggleLoginPassword"
                        aria-label="Show password">

                        <i class="fas fa-eye"></i>

                    </button>

                </div>

                <small
                    class="form-error"
                    id="loginPasswordError">
                </small>

            </div>


            <!-- OPTIONS -->
            <div class="login-options">

                <label class="remember-me">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1">

                    <span>
                        Remember me
                    </span>

                </label>


                <a
                    href="auth/forgot_password.php"
                    class="forgot-password">

                    Forgot Password?

                </a>

            </div>


            <!-- LOGIN BUTTON -->
            <button
                type="submit"
                class="login-submit-btn"
                id="loginSubmitBtn">

                <span class="login-btn-text">
                    Login
                </span>

                <span
                    class="login-btn-loading"
                    style="display:none;">

                    <i class="fas fa-spinner fa-spin"></i>
                    Logging in...

                </span>

            </button>


            <!-- LOGIN MESSAGE -->
            <div
                id="loginMessage"
                class="login-message"
                style="display:none;">
            </div>

        </form>


        <!-- REGISTER -->
        <div class="login-register">

            <span>
                Don't have an account?
            </span>

            <a
                href="javascript:void(0)"
                id="openRegisterFromLogin">

                Create Account

            </a>

        </div>


        <!-- SECURITY NOTICE -->
        <div class="login-security">

            <i class="fas fa-shield-alt"></i>

            <span>
                Your account information is protected.
            </span>

        </div>

    </div>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    /*
     * LOGIN MODAL
     */

    const loginModal =
        document.getElementById("loginModal");

    const closeLoginModal =
        document.getElementById("closeLoginModal");

    const togglePassword =
        document.getElementById("toggleLoginPassword");

    const passwordInput =
        document.getElementById("loginPassword");


    /*
     * OPEN LOGIN MODAL
     */

    window.openLoginModal = function () {

        if (!loginModal) {
            return;
        }

        loginModal.classList.add("active");

        document.body.classList.add(
            "modal-open"
        );

        setTimeout(function () {

            const emailInput =
                document.getElementById("loginEmail");

            if (emailInput) {
                emailInput.focus();
            }

        }, 200);

    };


    /*
     * CLOSE LOGIN MODAL
     */

    window.closeLoginModal = function () {

        if (!loginModal) {
            return;
        }

        loginModal.classList.remove("active");

        document.body.classList.remove(
            "modal-open"
        );

    };


    /*
     * CLOSE BUTTON
     */

    if (closeLoginModal) {

        closeLoginModal.addEventListener(
            "click",
            function () {

                closeLoginModal();

            }
        );

    }


    /*
     * CLICK OUTSIDE MODAL
     */

    if (loginModal) {

        loginModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === loginModal
                ) {

                    closeLoginModal();

                }

            }
        );

    }


    /*
     * ESCAPE KEY
     */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                loginModal &&
                loginModal.classList.contains("active")
            ) {

                closeLoginModal();

            }

        }
    );


    /*
     * PASSWORD TOGGLE
     */

    if (
        togglePassword &&
        passwordInput
    ) {

        togglePassword.addEventListener(
            "click",
            function () {

                if (
                    passwordInput.type ===
                    "password"
                ) {

                    passwordInput.type =
                        "text";

                    togglePassword.innerHTML =
                        '<i class="fas fa-eye-slash"></i>';

                    togglePassword.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    passwordInput.type =
                        "password";

                    togglePassword.innerHTML =
                        '<i class="fas fa-eye"></i>';

                    togglePassword.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            }
        );

    }


    /*
     * REGISTER FROM LOGIN
     */

    const registerLink =
        document.getElementById(
            "openRegisterFromLogin"
        );

    if (registerLink) {

        registerLink.addEventListener(
            "click",
            function () {

                closeLoginModal();

                if (
                    typeof window.openRegisterModal ===
                    "function"
                ) {

                    window.openRegisterModal();

                }

            }
        );

    }


    /*
     * LOGIN VALIDATION
     */

    const loginForm =
        document.getElementById("loginForm");

    if (loginForm) {

        loginForm.addEventListener(
            "submit",
            function (event) {

                const email =
                    document.getElementById(
                        "loginEmail"
                    );

                const password =
                    document.getElementById(
                        "loginPassword"
                    );

                const emailError =
                    document.getElementById(
                        "loginEmailError"
                    );

                const passwordError =
                    document.getElementById(
                        "loginPasswordError"
                    );

                let valid = true;


                /*
                 * CLEAR ERRORS
                 */

                emailError.textContent = "";
                passwordError.textContent = "";

                email.classList.remove(
                    "input-error"
                );

                password.classList.remove(
                    "input-error"
                );


                /*
                 * EMAIL
                 */

                if (
                    !email.value.trim()
                ) {

                    emailError.textContent =
                        "Please enter your email.";

                    email.classList.add(
                        "input-error"
                    );

                    valid = false;

                } else if (
                    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/
                        .test(email.value.trim())
                ) {

                    emailError.textContent =
                        "Please enter a valid email.";

                    email.classList.add(
                        "input-error"
                    );

                    valid = false;

                }


                /*
                 * PASSWORD
                 */

                if (
                    !password.value
                ) {

                    passwordError.textContent =
                        "Please enter your password.";

                    password.classList.add(
                        "input-error"
                    );

                    valid = false;

                }


                /*
                 * STOP SUBMISSION
                 */

                if (!valid) {

                    event.preventDefault();

                    return;

                }


                /*
                 * LOADING STATE
                 */

                const submitButton =
                    document.getElementById(
                        "loginSubmitBtn"
                    );

                const buttonText =
                    submitButton.querySelector(
                        ".login-btn-text"
                    );

                const loadingText =
                    submitButton.querySelector(
                        ".login-btn-loading"
                    );

                if (buttonText) {
                    buttonText.style.display =
                        "none";
                }

                if (loadingText) {
                    loadingText.style.display =
                        "inline-flex";
                }

                submitButton.disabled =
                    true;

            }
        );

    }

});
</script>