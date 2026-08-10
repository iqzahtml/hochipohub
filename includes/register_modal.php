<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER MODAL
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = defined('BASE_URL')
    ? BASE_URL
    : '/hochipohub/';

$registerOld = $_SESSION['register_old'] ?? [];

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


        <!-- REGISTER FORM -->
        <form
            action="<?= htmlspecialchars(
                $baseUrl . 'auth/register_process.php',
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


            <!-- ACCOUNT TYPE -->
            <div class="form-group">

                <label for="registerRole">
                    Account Type
                </label>

                <select
                    id="registerRole"
                    name="role"
                    required
                >

                    <option
                        value="customer"
                        <?= (
                            ($registerOld['role'] ?? 'customer')
                            === 'customer'
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Customer
                    </option>

                    <option
                        value="vendor"
                        <?= (
                            ($registerOld['role'] ?? '')
                            === 'vendor'
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
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
                        data-password-target="registerPassword"
                        aria-label="Show password"
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
                        data-password-target="registerConfirmPassword"
                        aria-label="Show password"
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

<?php

unset(
    $_SESSION['register_old']
);

?>