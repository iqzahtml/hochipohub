<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER MODAL
|--------------------------------------------------------------------------
*/

$baseUrl = defined('BASE_URL')
    ? BASE_URL
    : '/hochipohub/';

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

        <button
            type="button"
            class="modal-close"
            data-modal-close="registerModal"
            aria-label="Close"
        >
            ×
        </button>


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


        <?php if (
            isset($_SESSION['register_error']) &&
            !empty($_SESSION['register_error'])
        ): ?>

            <div class="auth-alert auth-alert-error">

                <?= htmlspecialchars(
                    $_SESSION['register_error'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

            <?php unset($_SESSION['register_error']); ?>

        <?php endif; ?>


        <form
            action="<?= $baseUrl ?>auth/register_process.php"
            method="POST"
            class="auth-form"
            id="registerForm"
        >

            <?php if (
                function_exists('csrfToken')
            ): ?>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        csrfToken(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            <?php elseif (
                isset($_SESSION['csrf_token'])
            ): ?>

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
                    required
                >

            </div>


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
                    >

                </div>

            </div>


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
                        data-password-target="registerPassword"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


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
                        data-password-target="registerConfirmPassword"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


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