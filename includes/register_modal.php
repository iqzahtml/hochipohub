<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER MODAL
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . '/'
    : '/hochipohub/';


/*
|--------------------------------------------------------------------------
| OLD REGISTER DATA
|--------------------------------------------------------------------------
*/

$registerOld =
    $_SESSION['register_old'] ?? [];


/*
|--------------------------------------------------------------------------
| REGISTER ERROR
|--------------------------------------------------------------------------
*/

$registerError =
    $_SESSION['register_error'] ?? '';


/*
|--------------------------------------------------------------------------
| CHECK WHETHER REGISTER SHOULD OPEN
|--------------------------------------------------------------------------
*/

$openRegister =
    !empty($_SESSION['open_register_modal'])
    ||
    isset($_GET['register']);


/*
|--------------------------------------------------------------------------
| MODAL CLASS
|--------------------------------------------------------------------------
*/

$registerModalClass =
    $openRegister
        ? 'modal-overlay active show'
        : 'modal-overlay';

?>

<!-- =========================================================
     REGISTER MODAL
========================================================= -->

<div
    class="<?= $registerModalClass ?>"
    id="registerModal"
    aria-hidden="<?= $openRegister ? 'false' : 'true' ?>"
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


        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if (!empty($registerError)): ?>

            <div class="auth-alert auth-alert-error">

                <?= htmlspecialchars(
                    $registerError,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             REGISTER FORM
        ====================================================== -->

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
                 FULL NAME
            ================================================== -->

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


            <!-- =================================================
                 EMAIL + PHONE
            ================================================== -->

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


            <!-- =================================================
                 ACCOUNT TYPE
            ================================================== -->

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


            <!-- =================================================
                 PASSWORD
            ================================================== -->

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


            <!-- =================================================
                 CONFIRM PASSWORD
            ================================================== -->

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


            <!-- =================================================
                 TERMS
            ================================================== -->

            <div class="checkbox-row">

                <input
                    type="checkbox"
                    id="registerTerms"
                    name="terms"
                    value="1"
                    required
                    disabled
                >

                <span>

                    I agree to

                    <button
                        type="button"
                        id="openTermsModal"
                        class="terms-link-button"
                    >
                        Terms &amp; Conditions
                    </button>

                </span>

            </div>


            <!-- =================================================
                 CREATE ACCOUNT
            ================================================== -->

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


            <!-- =================================================
                 LOGIN
            ================================================== -->

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
     TERMS MODAL
========================================================= -->

<div
    id="termsModal"
    class="terms-overlay"
    aria-hidden="true"
>

    <div
        class="terms-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="termsModalTitle"
    >

        <div class="terms-modal-header">

            <div class="terms-icon">
                📜
            </div>

            <div>

                <span class="terms-eyebrow">
                    HOCHIPOHUB
                </span>

                <h2 id="termsModalTitle">
                    Terms &amp; Conditions
                </h2>

            </div>

        </div>


        <div
            class="terms-content"
            id="termsContent"
        >

            <h3>1. Acceptance of Terms</h3>

            <p>
                By creating an account on HOCHIPOHUB,
                you agree to comply with and be bound by
                these Terms &amp; Conditions.
            </p>


            <h3>2. Account Registration</h3>

            <p>
                You agree to provide accurate, complete
                and up-to-date information when creating
                your HOCHIPOHUB account.
            </p>


            <h3>3. User Responsibilities</h3>

            <p>
                You are responsible for maintaining the
                confidentiality of your account information
                and password.
            </p>


            <h3>4. Customer Responsibilities</h3>

            <p>
                Customers are responsible for reviewing
                product information, prices, quantities
                and other order details before completing
                a purchase.
            </p>


            <h3>5. Vendor Responsibilities</h3>

            <p>
                Vendors are responsible for ensuring that
                their product information, prices,
                descriptions and images are accurate.
            </p>


            <h3>6. Orders and Payments</h3>

            <p>
                Customers agree to provide correct
                information when placing an order and
                completing payment.
            </p>


            <h3>7. Privacy</h3>

            <p>
                HOCHIPOHUB respects the privacy of its
                users.
            </p>


            <h3>8. Account Suspension or Termination</h3>

            <p>
                HOCHIPOHUB reserves the right to suspend
                or terminate an account if the user
                violates these Terms &amp; Conditions.
            </p>


            <h3>9. Changes to Terms</h3>

            <p>
                HOCHIPOHUB may update these Terms &amp;
                Conditions from time to time.
            </p>


            <h3>10. Agreement</h3>

            <p>
                By clicking
                <strong>
                    "I Understand &amp; Agree"
                </strong>,
                you confirm that you have read,
                understood and agreed to these
                Terms &amp; Conditions.
            </p>

        </div>


        <button
            type="button"
            class="terms-agree-button"
            id="termsAgreeButton"
            disabled
        >

            <span>
                I Understand &amp; Agree
            </span>

            <span>
                ✓
            </span>

        </button>

    </div>

</div>


<!-- =========================================================
     TERMS JAVASCRIPT
========================================================= -->

<script>

(function () {

    'use strict';


    function initTermsSystem() {

        const registerModal =
            document.getElementById('registerModal');

        const termsModal =
            document.getElementById('termsModal');

        const openTermsButton =
            document.getElementById('openTermsModal');

        const agreeTermsButton =
            document.getElementById('termsAgreeButton');

        const termsCheckbox =
            document.getElementById('registerTerms');

        const termsContent =
            document.getElementById('termsContent');


        if (
            !openTermsButton ||
            !termsModal ||
            !agreeTermsButton ||
            !termsContent
        ) {
            return;
        }


        let termsHaveBeenRead = false;


        /*
        |--------------------------------------------------------------------------
        | OPEN TERMS
        |--------------------------------------------------------------------------
        */

        openTermsButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                termsHaveBeenRead = false;

                agreeTermsButton.disabled = true;

                termsContent.scrollTop = 0;

                termsModal.classList.add('show');

                termsModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                if (registerModal) {

                    registerModal.classList.add('active');
                    registerModal.classList.add('show');

                    registerModal.setAttribute(
                        'aria-hidden',
                        'false'
                    );
                }

                document.body.classList.add(
                    'terms-open'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CHECK SCROLL
        |--------------------------------------------------------------------------
        */

        termsContent.addEventListener(
            'scroll',
            function () {

                const scrollTop =
                    termsContent.scrollTop;

                const clientHeight =
                    termsContent.clientHeight;

                const scrollHeight =
                    termsContent.scrollHeight;

                const reachedBottom =
                    scrollTop + clientHeight >=
                    scrollHeight - 10;

                if (reachedBottom) {

                    termsHaveBeenRead = true;

                    agreeTermsButton.disabled =
                        false;

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | AGREE
        |--------------------------------------------------------------------------
        */

        agreeTermsButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                if (!termsHaveBeenRead) {

                    alert(
                        'Please read all Terms & Conditions first.'
                    );

                    return;
                }

                if (termsCheckbox) {

                    termsCheckbox.disabled = false;

                    termsCheckbox.checked = true;

                }

                termsModal.classList.remove('show');

                termsModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.classList.remove(
                    'terms-open'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | PASSWORD TOGGLE
        |--------------------------------------------------------------------------
        */

        function setupPasswordToggle(
            inputId,
            buttonId
        ) {

            const input =
                document.getElementById(inputId);

            const button =
                document.getElementById(buttonId);

            if (!input || !button) {
                return;
            }

            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    if (input.type === 'password') {

                        input.type = 'text';

                        button.innerHTML = '🙈';

                    } else {

                        input.type = 'password';

                        button.innerHTML = '👁';

                    }

                }
            );
        }


        setupPasswordToggle(
            'registerPassword',
            'registerPasswordToggle'
        );

        setupPasswordToggle(
            'registerConfirmPassword',
            'registerConfirmPasswordToggle'
        );

    }


    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            initTermsSystem
        );

    } else {

        initTermsSystem();

    }

})();

</script>


<?php

/*
|--------------------------------------------------------------------------
| CLEAR TEMP SESSION DATA
|--------------------------------------------------------------------------
|
| The form has already used the values above.
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['register_old'],
    $_SESSION['register_error'],
    $_SESSION['open_register_modal']
);

?>