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

<!-- =========================================================
     REGISTER MODAL
========================================================= -->

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

        <!-- =================================================
             CLOSE REGISTER
        ================================================== -->

        <button
            type="button"
            class="modal-close"
            data-modal-close="registerModal"
            aria-label="Close register"
        >
            ×
        </button>


        <!-- =================================================
             HEADER
        ================================================== -->

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


        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

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


        <!-- =================================================
             REGISTER FORM
        ================================================== -->

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


                <!-- EMAIL -->

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


                <!-- PHONE -->

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
                 TERMS CHECKBOX
                 
                 IMPORTANT:
                 Use DIV instead of LABEL.
                 This prevents the Terms button from
                 accidentally triggering the checkbox.
            ================================================== -->

            <div class="checkbox-row">

                <input
                    type="checkbox"
                    id="registerTerms"
                    name="terms"
                    value="1"
                    required
                >

                <span>

                    I agree to the

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
                 CREATE ACCOUNT BUTTON
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
     TERMS & CONDITIONS POPUP

     IMPORTANT:
     This is OUTSIDE registerModal.

     Register stays open behind it.
========================================================= -->

<div
    class="terms-overlay"
    id="termsModal"
    aria-hidden="true"
>

    <div
        class="terms-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="termsModalTitle"
    >


        <!-- =================================================
             TERMS HEADER
        ================================================== -->

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


        <!-- =================================================
             TERMS CONTENT
        ================================================== -->

        <div class="terms-content">


            <h3>
                1. Acceptance of Terms
            </h3>

            <p>
                By creating an account on HOCHIPOHUB,
                you agree to comply with and be bound by
                these Terms &amp; Conditions.
            </p>


            <h3>
                2. Account Registration
            </h3>

            <p>
                You agree to provide accurate, complete
                and up-to-date information when creating
                your HOCHIPOHUB account.
            </p>


            <h3>
                3. User Responsibilities
            </h3>

            <p>
                You are responsible for maintaining the
                confidentiality of your account information
                and password. You must not use HOCHIPOHUB
                for unlawful, fraudulent or harmful
                activities.
            </p>


            <h3>
                4. Customer Responsibilities
            </h3>

            <p>
                Customers are responsible for reviewing
                product information, prices, quantities
                and other order details before completing
                a purchase.
            </p>


            <h3>
                5. Vendor Responsibilities
            </h3>

            <p>
                Vendors are responsible for ensuring that
                their product information, prices,
                descriptions and images are accurate and
                not misleading.
            </p>


            <h3>
                6. Orders and Payments
            </h3>

            <p>
                Customers agree to provide correct
                information when placing an order and
                completing payment. Orders may be subject
                to availability and applicable platform
                policies.
            </p>


            <h3>
                7. Privacy
            </h3>

            <p>
                HOCHIPOHUB respects the privacy of its
                users. Personal information provided during
                registration and transactions should be
                handled in accordance with applicable
                privacy requirements.
            </p>


            <h3>
                8. Account Suspension or Termination
            </h3>

            <p>
                HOCHIPOHUB reserves the right to suspend
                or terminate an account if the user
                violates these Terms &amp; Conditions or
                engages in activities that may harm the
                platform or other users.
            </p>


            <h3>
                9. Changes to Terms
            </h3>

            <p>
                HOCHIPOHUB may update these Terms &amp;
                Conditions from time to time. Users are
                encouraged to review the terms whenever
                they use the platform.
            </p>


            <h3>
                10. Agreement
            </h3>

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


        <!-- =================================================
             I UNDERSTAND BUTTON
        ================================================== -->

        <button
            type="button"
            class="terms-agree-button"
            id="termsAgreeButton"
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
     REGISTER JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | GET ELEMENTS
        |--------------------------------------------------------------------------
        */

        const registerModal =
            document.getElementById(
                'registerModal'
            );

        const termsModal =
            document.getElementById(
                'termsModal'
            );

        const openTermsButton =
            document.getElementById(
                'openTermsModal'
            );

        const agreeTermsButton =
            document.getElementById(
                'termsAgreeButton'
            );

        const termsCheckbox =
            document.getElementById(
                'registerTerms'
            );


        /*
        |--------------------------------------------------------------------------
        | PASSWORD TOGGLE FUNCTION
        |--------------------------------------------------------------------------
        */

        function setupPasswordToggle(
            inputId,
            buttonId
        ) {

            const input =
                document.getElementById(
                    inputId
                );

            const button =
                document.getElementById(
                    buttonId
                );


            if (
                !input ||
                !button
            ) {

                return;

            }


            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();


                    if (
                        input.type ===
                        'password'
                    ) {

                        input.type =
                            'text';

                        button.innerHTML =
                            '🙈';

                        button.setAttribute(
                            'aria-label',
                            'Hide password'
                        );

                        button.setAttribute(
                            'title',
                            'Hide password'
                        );

                    } else {

                        input.type =
                            'password';

                        button.innerHTML =
                            '👁';

                        button.setAttribute(
                            'aria-label',
                            'Show password'
                        );

                        button.setAttribute(
                            'title',
                            'Show password'
                        );

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | PASSWORD 1
        |--------------------------------------------------------------------------
        */

        setupPasswordToggle(
            'registerPassword',
            'registerPasswordToggle'
        );


        /*
        |--------------------------------------------------------------------------
        | PASSWORD 2
        |--------------------------------------------------------------------------
        */

        setupPasswordToggle(
            'registerConfirmPassword',
            'registerConfirmPasswordToggle'
        );


        /*
        |--------------------------------------------------------------------------
        | OPEN TERMS
        |--------------------------------------------------------------------------
        */

        if (openTermsButton) {

            openTermsButton.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();


                    /*
                    |----------------------------------------------------------
                    | MAKE SURE REGISTER STAYS OPEN
                    |----------------------------------------------------------
                    */

                    if (registerModal) {

                        registerModal.classList.add(
                            'active'
                        );

                        registerModal.setAttribute(
                            'aria-hidden',
                            'false'
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | OPEN TERMS ON TOP
                    |----------------------------------------------------------
                    */

                    if (termsModal) {

                        termsModal.classList.add(
                            'show'
                        );

                        termsModal.setAttribute(
                            'aria-hidden',
                            'false'
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | PREVENT BACKGROUND SCROLL
                    |----------------------------------------------------------
                    */

                    document.body.classList.add(
                        'terms-open'
                    );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | I UNDERSTAND & AGREE
        |--------------------------------------------------------------------------
        */

        if (agreeTermsButton) {

            agreeTermsButton.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();

                    event.stopPropagation();


                    /*
                    |----------------------------------------------------------
                    | CHECK TERMS
                    |----------------------------------------------------------
                    */

                    if (termsCheckbox) {

                        termsCheckbox.checked =
                            true;


                        /*
                        |------------------------------------------------------
                        | TRIGGER CHANGE EVENT
                        |------------------------------------------------------
                        */

                        termsCheckbox.dispatchEvent(
                            new Event(
                                'change',
                                {
                                    bubbles: true
                                }
                            )
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | CLOSE TERMS ONLY
                    |----------------------------------------------------------
                    */

                    if (termsModal) {

                        termsModal.classList.remove(
                            'show'
                        );

                        termsModal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | KEEP REGISTER OPEN
                    |----------------------------------------------------------
                    */

                    if (registerModal) {

                        registerModal.classList.add(
                            'active'
                        );

                        registerModal.setAttribute(
                            'aria-hidden',
                            'false'
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | REMOVE TERMS SCROLL LOCK
                    |----------------------------------------------------------
                    */

                    document.body.classList.remove(
                        'terms-open'
                    );


                    /*
                    |----------------------------------------------------------
                    | RETURN TO REGISTER
                    |----------------------------------------------------------
                    */

                    setTimeout(
                        function () {

                            const nameInput =
                                document.getElementById(
                                    'registerName'
                                );

                            if (nameInput) {

                                nameInput.focus();

                            }

                        },
                        100
                    );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CLICK OUTSIDE TERMS
        |--------------------------------------------------------------------------
        */

        if (termsModal) {

            termsModal.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target ===
                        termsModal
                    ) {

                        termsModal.classList.remove(
                            'show'
                        );

                        termsModal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                        document.body.classList.remove(
                            'terms-open'
                        );

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | ESCAPE KEY
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key !==
                    'Escape'
                ) {

                    return;

                }


                if (
                    termsModal &&
                    termsModal.classList.contains(
                        'show'
                    )
                ) {

                    termsModal.classList.remove(
                        'show'
                    );

                    termsModal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                    document.body.classList.remove(
                        'terms-open'
                    );

                }

            }
        );

    }
);

</script>


<?php

/*
|--------------------------------------------------------------------------
| CLEAR OLD REGISTER DATA
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['register_old']
);

?>