<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '/hochipohub/';

$loginEmail =
    $_SESSION['login_email'] ?? '';

$loginSuccess =
    $_SESSION['login_success'] ?? '';

$loginError =
    $_SESSION['login_error'] ?? '';

?>

<div
    class="modal-overlay"
    id="loginModal"
    aria-hidden="true"
>

    <div
        class="auth-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="loginModalTitle"
    >

        <!-- =====================================================
             CLOSE BUTTON
        ====================================================== -->

        <button
            type="button"
            class="modal-close"
            data-modal-close="loginModal"
            aria-label="Close login"
        >
            ×
        </button>


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <div class="auth-modal-header">

            <div class="auth-modal-icon">
                👋
            </div>

            <span class="auth-eyebrow">
                WELCOME BACK
            </span>

            <h2 id="loginModalTitle">
                Login to HochipoHub
            </h2>

            <p>
                Your marketplace is waiting for you.
            </p>

        </div>


        <!-- =====================================================
             SUCCESS
        ====================================================== -->

        <?php if (!empty($loginSuccess)): ?>

            <div class="auth-alert auth-alert-success">

                <?= htmlspecialchars(
                    $loginSuccess,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

            <?php
            unset($_SESSION['login_success']);
            ?>

        <?php endif; ?>


        <!-- =====================================================
             ERROR
        ====================================================== -->

        <?php if (!empty($loginError)): ?>

            <div class="auth-alert auth-alert-error">

                <?= htmlspecialchars(
                    $loginError,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

            <?php
            unset($_SESSION['login_error']);
            ?>

        <?php endif; ?>


        <!-- =====================================================
             LOGIN FORM
        ====================================================== -->

        <form
            action="<?= htmlspecialchars(
                $baseUrl . 'auth/login_process.php',
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            method="POST"
            class="auth-form"
            id="loginForm"
        >


            <!-- =================================================
                 CSRF
            ================================================== -->

            <?php if (function_exists('csrfToken')): ?>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        csrfToken(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            <?php elseif (isset($_SESSION['csrf_token'])): ?>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            <?php endif; ?>


            <!-- =================================================
                 EMAIL
            ================================================== -->

            <div class="form-group">

                <label for="loginEmail">
                    Email Address
                </label>

                <input
                    type="email"
                    id="loginEmail"
                    name="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    value="<?= htmlspecialchars(
                        $loginEmail,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================== -->

            <div class="form-group">

                <div class="form-label-row">

                    <label for="loginPassword">
                        Password
                    </label>

                    <a
                        href="<?= htmlspecialchars(
                            $baseUrl .
                            'auth/forgot_password.php',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="form-link"
                    >
                        Forgot password?
                    </a>

                </div>


                <div
                    class="password-input"
                    id="loginPasswordWrapper"
                >

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
                        id="loginPasswordToggle"
                        class="password-toggle"
                        aria-label="Show password"
                        aria-pressed="false"
                        title="Show password"
                    >

                        <span
                            id="loginPasswordEye"
                            aria-hidden="true"
                        >
                            👁
                        </span>

                    </button>

                </div>

            </div>


            <!-- =================================================
                 REMEMBER ME
            ================================================== -->

            <label class="checkbox-row">

                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >

                <span>
                    Remember me
                </span>

            </label>


            <!-- =================================================
                 LOGIN
            ================================================== -->

            <button
                type="submit"
                class="auth-submit"
            >

                <span>
                    Login
                </span>

                <span>
                    →
                </span>

            </button>


            <!-- =================================================
                 DIVIDER
            ================================================== -->

            <div class="auth-divider">

                <span>
                    OR
                </span>

            </div>


            <!-- =================================================
                 REGISTER
            ================================================== -->

            <p class="auth-switch">

                Don't have an account?

                <button
                    type="button"
                    class="auth-switch-button"
                    data-modal-switch="loginModal"
                    data-modal-target="registerModal"
                >
                    Create one
                </button>

            </p>


        </form>

    </div>

</div>


<!-- ===============================================================
     PASSWORD TOGGLE FIX
================================================================ -->

<style>

#loginPasswordWrapper {
    position: relative !important;
    width: 100% !important;
}


#loginPassword {
    position: relative !important;

    z-index: 1 !important;

    padding-right: 58px !important;
}


#loginPasswordToggle {
    position: absolute !important;

    top: 50% !important;
    right: 8px !important;

    width: 40px !important;
    height: 40px !important;

    min-width: 40px !important;
    min-height: 40px !important;

    margin: 0 !important;
    padding: 0 !important;

    display: flex !important;

    align-items: center !important;
    justify-content: center !important;

    transform:
        translateY(-50%) !important;

    border: 0 !important;

    outline: 0 !important;

    background:
        transparent !important;

    color: #ffffff !important;

    border-radius: 9px !important;

    cursor: pointer !important;

    pointer-events: auto !important;

    z-index: 99999999 !important;

    user-select: none !important;

    touch-action: manipulation !important;
}


#loginPasswordToggle:hover {
    background:
        rgba(255,255,255,.12) !important;
}


#loginPasswordToggle:active {
    transform:
        translateY(-50%)
        scale(.92) !important;
}


#loginPasswordEye {
    display: flex !important;

    align-items: center !important;
    justify-content: center !important;

    pointer-events: none !important;

    font-size: 16px !important;

    line-height: 1 !important;
}

</style>


<script>

(function () {

    'use strict';


    function initializeLoginPasswordToggle() {

        const passwordInput =
            document.getElementById(
                'loginPassword'
            );


        const toggleButton =
            document.getElementById(
                'loginPasswordToggle'
            );


        const eyeIcon =
            document.getElementById(
                'loginPasswordEye'
            );


        /*
        |--------------------------------------------------------------------------
        | SAFETY
        |--------------------------------------------------------------------------
        */

        if (
            !passwordInput ||
            !toggleButton
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | PREVENT DUPLICATE INITIALIZATION
        |--------------------------------------------------------------------------
        */

        if (
            toggleButton.dataset.initialized ===
            'true'
        ) {

            return;
        }


        toggleButton.dataset.initialized =
            'true';


        /*
        |--------------------------------------------------------------------------
        | CLICK
        |--------------------------------------------------------------------------
        */

        toggleButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();


                const currentlyHidden =
                    passwordInput.type ===
                    'password';


                /*
                |--------------------------------------------------------------------------
                | SHOW
                |--------------------------------------------------------------------------
                */

                if (currentlyHidden) {

                    passwordInput.type =
                        'text';


                    toggleButton.setAttribute(
                        'aria-label',
                        'Hide password'
                    );


                    toggleButton.setAttribute(
                        'aria-pressed',
                        'true'
                    );


                    toggleButton.setAttribute(
                        'title',
                        'Hide password'
                    );


                    if (eyeIcon) {

                        eyeIcon.textContent =
                            '🙈';
                    }


                /*
                |--------------------------------------------------------------------------
                | HIDE
                |--------------------------------------------------------------------------
                */

                } else {

                    passwordInput.type =
                        'password';


                    toggleButton.setAttribute(
                        'aria-label',
                        'Show password'
                    );


                    toggleButton.setAttribute(
                        'aria-pressed',
                        'false'
                    );


                    toggleButton.setAttribute(
                        'title',
                        'Show password'
                    );


                    if (eyeIcon) {

                        eyeIcon.textContent =
                            '👁';
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | RETURN FOCUS
                |--------------------------------------------------------------------------
                */

                passwordInput.focus();


                /*
                |--------------------------------------------------------------------------
                | CURSOR AT END
                |--------------------------------------------------------------------------
                */

                try {

                    const length =
                        passwordInput.value.length;


                    passwordInput.setSelectionRange(
                        length,
                        length
                    );

                } catch (error) {

                    // Ignore browser selection errors.
                }

            },
            false
        );

    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState ===
        'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initializeLoginPasswordToggle
        );

    } else {

        initializeLoginPasswordToggle();
    }

})();

</script>