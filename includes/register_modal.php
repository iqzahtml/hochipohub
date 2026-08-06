<?php
/*
|--------------------------------------------------------------------------
| HochipoHub - Register Modal
|--------------------------------------------------------------------------
*/
?>

<div
    class="modal-overlay"
    id="registerModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="registerModalTitle"
    hidden
>

    <div class="auth-modal register-modal">

        <!-- CLOSE BUTTON -->

        <button
            type="button"
            class="modal-close"
            data-close-modal="registerModal"
            aria-label="Close registration"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <!-- =====================================================
             VISUAL SIDE
        ====================================================== -->

        <div class="auth-modal-visual register-visual">

            <div class="auth-visual-decoration decoration-one"></div>
            <div class="auth-visual-decoration decoration-two"></div>
            <div class="auth-visual-decoration decoration-three"></div>

            <div class="auth-visual-content">

                <div class="auth-visual-icon">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>

                <span class="auth-eyebrow">
                    JOIN THE HUB
                </span>

                <h2>
                    Your local
                    <span>marketplace</span>
                    starts here.
                </h2>

                <p>
                    Shop unique products, support local businesses,
                    or build your own store with HochipoHub.
                </p>


                <!-- BENEFITS -->

                <div class="register-benefits">

                    <div class="register-benefit">

                        <span class="benefit-icon">
                            <i class="fa-solid fa-compass"></i>
                        </span>

                        <div>

                            <strong>
                                Discover
                            </strong>

                            <small>
                                Find products you won't see everywhere.
                            </small>

                        </div>

                    </div>


                    <div class="register-benefit">

                        <span class="benefit-icon">
                            <i class="fa-solid fa-store"></i>
                        </span>

                        <div>

                            <strong>
                                Support Local
                            </strong>

                            <small>
                                Shop directly from local vendors.
                            </small>

                        </div>

                    </div>


                    <div class="register-benefit">

                        <span class="benefit-icon">
                            <i class="fa-solid fa-rocket"></i>
                        </span>

                        <div>

                            <strong>
                                Sell & Grow
                            </strong>

                            <small>
                                Turn your products into a business.
                            </small>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             FORM SIDE
        ====================================================== -->

        <div class="auth-modal-form">

            <div class="auth-form-header">

                <div class="mobile-auth-icon">

                    <i class="fa-solid fa-user-plus"></i>

                </div>

                <span class="auth-form-label">
                    CREATE ACCOUNT
                </span>

                <h2 id="registerModalTitle">
                    Let's get you in.
                </h2><p>
                    Create your HochipoHub account in a few steps.
                </p>

            </div>


            <!-- ERROR -->

            <div
                class="auth-alert auth-alert-error"
                id="registerAlert"
                role="alert"
                hidden
            >

                <i class="fa-solid fa-circle-exclamation"></i>

                <span id="registerAlertMessage"></span>

            </div>


            <!-- SUCCESS -->

            <div
                class="auth-alert auth-alert-success"
                id="registerSuccess"
                role="status"
                hidden
            >

                <i class="fa-solid fa-circle-check"></i>

                <span id="registerSuccessMessage"></span>

            </div>


            <!-- =================================================
                 ACCOUNT TYPE
            ================================================== -->

            <div class="account-type-section">

                <label class="account-type-label">
                    I want to...
                </label>


                <div class="account-type-options">

                    <!-- CUSTOMER -->

                    <label
                        class="account-type-card active"
                        data-role-card="customer"
                    >

                        <input
                            type="radio"
                            name="register_role"
                            value="customer"
                            checked
                        >

                        <span class="account-type-check">

                            <i class="fa-solid fa-check"></i>

                        </span>


                        <span class="account-type-icon">

                            <i class="fa-solid fa-bag-shopping"></i>

                        </span>


                        <span class="account-type-content">

                            <strong>
                                Shop
                            </strong>

                            <small>
                                I'm a customer
                            </small>

                        </span>

                    </label>


                    <!-- VENDOR -->

                    <label
                        class="account-type-card"
                        data-role-card="vendor"
                    >

                        <input
                            type="radio"
                            name="register_role"
                            value="vendor"
                        >

                        <span class="account-type-check">

                            <i class="fa-solid fa-check"></i>

                        </span>


                        <span class="account-type-icon">

                            <i class="fa-solid fa-store"></i>

                        </span>


                        <span class="account-type-content">

                            <strong>
                                Sell
                            </strong>

                            <small>
                                I'm a vendor
                            </small>

                        </span>

                    </label>

                </div>

            </div>


            <!-- =================================================
                 REGISTRATION FORM
            ================================================== -->

            <form
                action="<?php echo BASE_URL; ?>auth/register_process.php"
                method="POST"
                id="registerForm"
                class="auth-form"
                novalidate
            >

                <?php if (
                    function_exists('csrfField')
                ): ?>

                    <?php echo csrfField(); ?>

                <?php endif; ?>


                <!-- ROLE -->

                <input
                    type="hidden"
                    name="role"id="registerRole"
                    value="customer"
                >


                <!-- =============================================
                     FULL NAME
                ============================================== -->

                <div class="form-group">

                    <label for="registerName">
                        Full name
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-regular fa-user"></i>

                        <input
                            type="text"
                            name="name"
                            id="registerName"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            maxlength="100"
                            required
                        >

                    </div>

                    <small
                        class="field-error"
                        id="registerNameError"
                    ></small>

                </div>


                <!-- =============================================
                     EMAIL
                ============================================== -->

                <div class="form-group">

                    <label for="registerEmail">
                        Email address
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-regular fa-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            id="registerEmail"
                            placeholder="you@example.com"
                            autocomplete="email"
                            maxlength="100"
                            required
                        >

                    </div>

                    <small
                        class="field-error"
                        id="registerEmailError"
                    ></small>

                </div>


                <!-- =============================================
                     PHONE
                ============================================== -->

                <div class="form-group">

                    <label for="registerPhone">

                        Phone number

                        <span class="optional-label">
                            Optional
                        </span>

                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-phone"></i>

                        <input
                            type="tel"
                            name="phone"
                            id="registerPhone"
                            placeholder="01X-XXXXXXX"
                            autocomplete="tel"
                            maxlength="20"
                        >

                    </div>

                    <small
                        class="field-error"
                        id="registerPhoneError"
                    ></small>

                </div>


                <!-- =============================================
                     PASSWORD
                ============================================== -->

                <div class="form-group">

                    <label for="registerPassword">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="registerPassword"
                            placeholder="Create a strong password"
                            autocomplete="new-password"
                            maxlength="255"
                            required
                        >

                        <buttontype="button"
                            class="password-toggle"
                            data-password-target="registerPassword"
                            aria-label="Show password"
                        >

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>


                    <!-- PASSWORD STRENGTH -->

                    <div class="password-strength">

                        <div class="strength-bar">

                            <span
                                id="strengthBar"
                                class="strength-progress"
                            ></span>

                        </div>


                        <span
                            id="passwordStrengthText"
                            class="strength-text"
                        >
                            Password strength
                        </span>

                    </div>


                    <small
                        class="field-error"
                        id="registerPasswordError"
                    ></small>

                </div>


                <!-- =============================================
                     CONFIRM PASSWORD
                ============================================== -->

                <div class="form-group">

                    <label for="registerConfirmPassword">
                        Confirm password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            name="confirm_password"
                            id="registerConfirmPassword"
                            placeholder="Repeat your password"
                            autocomplete="new-password"
                            maxlength="255"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-target="registerConfirmPassword"
                            aria-label="Show password"
                        >

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>

                    <small
                        class="field-error"
                        id="registerConfirmPasswordError"
                    ></small>

                </div>


                <!-- =============================================
                     TERMS
                ============================================== -->

                <div class="form-options register-terms">

                    <label class="checkbox-label">

                        <input
                            type="checkbox"
                            name="agree_terms"
                            value="1"
                            id="registerTerms"
                            required
                        >

                        <span class="custom-checkbox"></span>

                        <span>
                            I agree to the
                            <a
                                href="#"
                                onclick="return false;"
                            >
                                Terms
                            </a>
                            and
                            <a
                                href="#"
                                onclick="return false;"
                            >
                                Privacy Policy
                            </a>.
                        </span>

                    </label>

                    <small
                        class="field-error"
                        id="registerTermsError"
                    ></small>

                </div><!-- =============================================
                     VENDOR INFORMATION NOTICE
                ============================================== -->

                <div
                    class="vendor-registration-note"
                    id="vendorRegistrationNote"
                    hidden
                >

                    <div class="vendor-note-icon">

                        <i class="fa-solid fa-store"></i>

                    </div>


                    <div class="vendor-note-content">

                        <strong>
                            Starting as a vendor?
                        </strong>

                        <p>
                            After creating your account,
                            you'll complete your business
                            profile before your store can
                            be reviewed by our admin team.
                        </p>

                    </div>

                </div>


                <!-- =============================================
                     SUBMIT
                ============================================== -->

                <button
                    type="submit"
                    class="auth-submit-btn"
                    id="registerSubmitBtn"
                >

                    <span class="btn-text">
                        Create My Account
                    </span>

                    <span
                        class="btn-loader"
                        hidden
                    >

                        <i class="fa-solid fa-spinner fa-spin"></i>

                        Creating account...

                    </span>

                    <i class="fa-solid fa-arrow-right btn-arrow"></i>

                </button>


                <!-- SECURITY -->

                <div class="auth-security-note">

                    <i class="fa-solid fa-shield-halved"></i>

                    <span>
                        Your password is securely protected.
                    </span>

                </div>

            </form>


            <!-- =================================================
                 LOGIN SWITCH
            ================================================== -->

            <div class="auth-switch">

                <span>
                    Already have an account?
                </span>

                <button
                    type="button"
                    data-switch-modal="registerModal"
                    data-target-modal="loginModal"
                >

                    Login instead

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </div>


            <!-- =================================================
                 VENDOR INFORMATION
            ================================================== -->

            <div class="vendor-info-strip">

                <div class="vendor-info-icon">

                    <i class="fa-solid fa-chart-line"></i>

                </div>

                <div>

                    <strong>
                        Built for local businesses.
                    </strong>

                    <span>
                        Add multiple products, manage orders,
                        track sales and grow your store.
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =============================================================
     VENDOR REGISTRATION SUCCESS / NEXT STEP MODAL
============================================================== -->

<div
    class="modal-overlay"
    id="vendorSetupNoticeModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="vendorSetupNoticeTitle"
    hidden
>

    <div class="auth-small-modal vendor-success-modal">

        <div class="success-animation">

            <div class="success-circle">

                <i class="fa-solid fa-check"></i>

            </div>

        </div><span class="auth-form-label">
            ACCOUNT CREATED
        </span>


        <h2 id="vendorSetupNoticeTitle">
            You're almost there!
        </h2>


        <p>
            Your vendor account has been created.
            Next, complete your business details so
            HochipoHub can review your store.
        </p>


        <div class="vendor-next-steps">

            <div class="next-step">

                <span>
                    01
                </span>

                <div>

                    <strong>
                        Complete profile
                    </strong>

                    <small>
                        Business name, category & details
                    </small>

                </div>

            </div>


            <div class="next-step">

                <span>
                    02
                </span>

                <div>

                    <strong>
                        Admin review
                    </strong>

                    <small>
                        Your application is reviewed
                    </small>

                </div>

            </div>


            <div class="next-step">

                <span>
                    03
                </span>

                <div>

                    <strong>
                        Start selling
                    </strong>

                    <small>
                        Add as many products as you want
                    </small>

                </div>

            </div>

        </div>


        <a
            href="<?php echo BASE_URL; ?>seller/setup_profile.php"
            class="auth-submit-btn vendor-setup-btn"
        >

            <span>
                Complete Vendor Profile
            </span>

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>

</div>