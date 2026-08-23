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
             SUCCESS MESSAGE
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
             ERROR MESSAGE
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


                <!-- PASSWORD CONTAINER -->

                <div class="password-input">

                    <!-- PASSWORD INPUT -->

                    <input
                        type="password"
                        id="loginPassword"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <!-- EYE BUTTON -->

                    <button
                        type="button"
                        id="loginPasswordToggle"
                        class="password-toggle"
                        data-password-target="loginPassword"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        👁
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
                 LOGIN BUTTON
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


<!-- =========================================================
     AUTOMATIC LOGIN MODAL
========================================================= -->

<?php if (
    isset($_GET['login']) &&
    $_GET['login'] === '1'
): ?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /*
        |----------------------------------------------------------
        | OPEN LOGIN MODAL AUTOMATICALLY
        |----------------------------------------------------------
        */

        if (
            typeof window.openModal ===
            "function"
        ) {

            window.openModal(
                "loginModal"
            );

        }


        /*
        |----------------------------------------------------------
        | FOCUS PASSWORD
        |----------------------------------------------------------
        */

        setTimeout(
            function () {

                const password =
                    document.getElementById(
                        "loginPassword"
                    );

                if (password) {

                    password.focus();

                }

            },
            300
        );

    }
);

</script>

<?php endif; ?>


<!-- =========================================================
     PASSWORD SHOW / HIDE
========================================================= -->

<script>

(function () {

    /*
    |--------------------------------------------------------------------------
    | PASSWORD TOGGLE FUNCTION
    |--------------------------------------------------------------------------
    */

    function setupPasswordToggle() {

        const button =
            document.getElementById(
                "loginPasswordToggle"
            );

        const password =
            document.getElementById(
                "loginPassword"
            );


        /*
        |----------------------------------------------------------------------
        | CHECK ELEMENTS
        |----------------------------------------------------------------------
        */

        if (!button || !password) {

            return;

        }


        /*
        |----------------------------------------------------------------------
        | PREVENT DUPLICATE EVENT
        |----------------------------------------------------------------------
        */

        if (
            button.dataset.toggleReady === "true"
        ) {

            return;

        }


        button.dataset.toggleReady = "true";


        /*
        |----------------------------------------------------------------------
        | CLICK EVENT
        |----------------------------------------------------------------------
        */

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                /*
                |--------------------------------------------------------------
                | PASSWORD IS HIDDEN
                |--------------------------------------------------------------
                */

                if (
                    password.type === "password"
                ) {

                    password.type = "text";


                    /*
                    |----------------------------------------------------------
                    | CHANGE ICON
                    |----------------------------------------------------------
                    */

                    button.textContent = "🙈";


                    /*
                    |----------------------------------------------------------
                    | CHANGE ACCESSIBILITY
                    |----------------------------------------------------------
                    */

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                    button.setAttribute(
                        "aria-pressed",
                        "true"
                    );


                /*
                |--------------------------------------------------------------
                | PASSWORD IS VISIBLE
                |--------------------------------------------------------------
                */

                } else {

                    password.type = "password";


                    /*
                    |----------------------------------------------------------
                    | CHANGE ICON
                    |----------------------------------------------------------
                    */

                    button.textContent = "👁";


                    /*
                    |----------------------------------------------------------
                    | CHANGE ACCESSIBILITY
                    |----------------------------------------------------------
                    */

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                    button.setAttribute(
                        "aria-pressed",
                        "false"
                    );

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PAGE LOADED
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            setupPasswordToggle
        );

    } else {

        setupPasswordToggle();

    }


    /*
    |--------------------------------------------------------------------------
    | RUN AGAIN WHEN LOGIN MODAL IS OPENED
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const loginTrigger =
                event.target.closest(
                    '[data-modal-target="loginModal"]'
                );


            if (!loginTrigger) {

                return;

            }


            setTimeout(
                setupPasswordToggle,
                150
            );

        }
    );

})();

</script>