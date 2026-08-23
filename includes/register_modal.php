<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '/hochipohub/';

$registerOld =
    $_SESSION['register_old'] ?? [];

?>

<div
    class="modal-overlay"
    id="registerModal"
    aria-hidden="true"
>

    <div
        class="auth-modal auth-modal-register"
        role="dialog"
        aria-modal="true"
        aria-labelledby="registerModalTitle"
    >

        <!-- CLOSE -->

        <button
            type="button"
            class="modal-close"
            data-modal-close="registerModal"
            aria-label="Close register"
        >
            ×
        </button>


        <!-- HEADER -->

        <div class="auth-modal-header">

            <div class="auth-modal-icon">
                ✨
            </div>

            <span class="auth-eyebrow">
                JOIN THE HUB
            </span>

            <h2 id="registerModalTitle">
                Create your account
            </h2>

            <p>
                Shop local. Discover more. Sell smarter.
            </p>

        </div>


        <!-- ERROR -->

        <?php if (!empty($_SESSION['register_error'])): ?>

            <div class="auth-alert auth-alert-error">

                <?= htmlspecialchars(
                    $_SESSION['register_error'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

            <?php unset($_SESSION['register_error']); ?>

        <?php endif; ?>


        <!-- REGISTER FORM -->

        <form
            action="<?= htmlspecialchars(
                $baseUrl .
                'auth/register_process.php',
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            method="POST"
            class="auth-form"
            id="registerForm"
        >


            <!-- CSRF -->

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


            <!-- NAME -->

            <div class="form-group">

                <label for="registerName">
                    Full Name
                </label>

                <input
                    type="text"
                    id="registerName"
                    name="name"
                    placeholder="Your full name"
                    autocomplete="name"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $registerOld['name'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- EMAIL + PHONE -->

            <div class="form-grid-2">


                <div class="form-group">

                    <label for="registerEmail">
                        Email
                    </label>

                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="email"
                        value="<?= htmlspecialchars(
                            $registerOld['email'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="registerPhone">
                        Phone
                    </label>

                    <input
                        type="tel"
                        id="registerPhone"
                        name="phone"
                        placeholder="01X-XXXXXXX"
                        autocomplete="tel"
                        value="<?= htmlspecialchars(
                            $registerOld['phone'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>


            </div>


            <!-- ROLE -->

            <div class="form-group">

                <label for="registerRole">
                    Account Type
                </label>

                <select
                    id="registerRole"
                    name="role"
                    required
                >

                    <option value="customer">
                        Customer
                    </option>

                    <option value="vendor">
                        Vendor
                    </option>

                </select>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="registerPassword">
                    Password
                </label>


                <div class="password-input">

                    <input
                        type="password"
                        id="registerPassword"
                        name="password"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        minlength="6"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="registerPasswordToggle"
                        aria-label="Show password"
                        title="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="registerConfirmPassword">
                    Confirm Password
                </label>


                <div class="password-input">

                    <input
                        type="password"
                        id="registerConfirmPassword"
                        name="confirm_password"
                        placeholder="Repeat your password"
                        autocomplete="new-password"
                        minlength="6"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="registerConfirmPasswordToggle"
                        aria-label="Show password"
                        title="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- TERMS -->

            <label class="checkbox-row">

                <input
                    type="checkbox"
                    name="terms"
                    value="1"
                    required
                >

                <span>

                    I agree to the

                    <a href="#">
                        Terms & Conditions
                    </a>

                </span>

            </label>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="auth-submit"
            >

                <span>
                    Create Account
                </span>

                <span>
                    →
                </span>

            </button>


            <!-- LOGIN -->

            <p class="auth-switch">

                Already have an account?

                <button
                    type="button"
                    class="auth-switch-button"
                    data-modal-switch="registerModal"
                    data-modal-target="loginModal"
                >
                    Login
                </button>

            </p>


        </form>

    </div>

</div>


<!-- =========================================================
     REGISTER PASSWORD SCRIPT
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {


    /*
    |--------------------------------------------------------------------------
    | REGISTER PASSWORD
    |--------------------------------------------------------------------------
    */

    const registerPassword =
        document.getElementById(
            'registerPassword'
        );

    const registerPasswordButton =
        document.getElementById(
            'registerPasswordToggle'
        );


    if (
        registerPassword &&
        registerPasswordButton
    ) {

        registerPasswordButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                if (
                    registerPassword.type ===
                    'password'
                ) {

                    registerPassword.type =
                        'text';

                    registerPasswordButton.innerHTML =
                        '🙈';

                    registerPasswordButton.setAttribute(
                        'aria-label',
                        'Hide password'
                    );

                    registerPasswordButton.setAttribute(
                        'title',
                        'Hide password'
                    );

                } else {

                    registerPassword.type =
                        'password';

                    registerPasswordButton.innerHTML =
                        '👁';

                    registerPasswordButton.setAttribute(
                        'aria-label',
                        'Show password'
                    );

                    registerPasswordButton.setAttribute(
                        'title',
                        'Show password'
                    );

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM PASSWORD
    |--------------------------------------------------------------------------
    */

    const confirmPassword =
        document.getElementById(
            'registerConfirmPassword'
        );

    const confirmPasswordButton =
        document.getElementById(
            'registerConfirmPasswordToggle'
        );


    if (
        confirmPassword &&
        confirmPasswordButton
    ) {

        confirmPasswordButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                if (
                    confirmPassword.type ===
                    'password'
                ) {

                    confirmPassword.type =
                        'text';

                    confirmPasswordButton.innerHTML =
                        '🙈';

                    confirmPasswordButton.setAttribute(
                        'aria-label',
                        'Hide password'
                    );

                    confirmPasswordButton.setAttribute(
                        'title',
                        'Hide password'
                    );

                } else {

                    confirmPassword.type =
                        'password';

                    confirmPasswordButton.innerHTML =
                        '👁';

                    confirmPasswordButton.setAttribute(
                        'aria-label',
                        'Show password'
                    );

                    confirmPasswordButton.setAttribute(
                        'title',
                        'Show password'
                    );

                }

            }
        );

    }

});

</script>


<?php

unset(
    $_SESSION['register_old']
);

?>