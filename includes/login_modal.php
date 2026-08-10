<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN MODAL
|--------------------------------------------------------------------------
*/

$baseUrl = defined('BASE_URL')
    ? BASE_URL
    : '/hochipohub/';

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

        <button
            type="button"
            class="modal-close"
            data-modal-close="loginModal"
            aria-label="Close"
        >
            ×
        </button>


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


        <?php if (
            isset($_SESSION['error']) &&
            !empty($_SESSION['error'])
        ): ?>

            <div class="auth-alert auth-alert-error">

                <?= htmlspecialchars(
                    $_SESSION['error'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>


        <form
            action="<?= $baseUrl ?>auth/login_process.php"
            method="POST"
            class="auth-form"
            id="loginForm"
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

                <label for="loginEmail">
                    Email Address
                </label>

                <input
                    type="email"
                    id="loginEmail"
                    name="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                >

            </div>


            <div class="form-group">

                <div class="form-label-row">

                    <label for="loginPassword">
                        Password
                    </label>

                    <a
                        href="<?= $baseUrl ?>auth/forgot_password.php"
                        class="form-link"
                    >
                        Forgot password?
                    </a>

                </div>


                <div class="password-input">

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
                        data-password-target="loginPassword"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


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


            <div class="auth-divider">

                <span>
                    OR
                </span>

            </div>


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