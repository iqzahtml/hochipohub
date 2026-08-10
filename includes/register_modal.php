<?php
/*
|--------------------------------------------------------------------------
| REGISTER MODAL
|--------------------------------------------------------------------------
| File: includes/register_modal.php
| Purpose:
| - Display customer/vendor registration form
| - Submit registration to auth/register_process.php
|--------------------------------------------------------------------------
*/
?>

<div id="registerModal" class="modal-overlay" aria-hidden="true">

    <div class="modal-container register-modal">

        <!-- Close Button -->
        <button
            type="button"
            class="modal-close"
            onclick="closeRegisterModal()"
            aria-label="Close">
            &times;
        </button>

        <!-- Header -->
        <div class="modal-header">

            <div class="modal-icon">
                <i class="fas fa-user-plus"></i>
            </div>

            <h2>Create Your Account</h2>

            <p>
                Join HochipoHub and discover products from local vendors.
            </p>

        </div>


        <!-- Register Form -->
        <form
            id="registerForm"
            action="auth/register_process.php"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off">

            <!-- Name -->
            <div class="form-group">

                <label for="registerName">
                    Full Name
                    <span class="required">*</span>
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-user"></i>

                    <input
                        type="text"
                        id="registerName"
                        name="name"
                        placeholder="Enter your full name"
                        maxlength="100"
                        required>

                </div>

                <small
                    class="form-error"
                    id="registerNameError">
                </small>

            </div>


            <!-- Email -->
            <div class="form-group">

                <label for="registerEmail">
                    Email Address
                    <span class="required">*</span>
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-envelope"></i>

                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        placeholder="example@email.com"
                        maxlength="100"
                        required>

                </div>

                <small
                    class="form-error"
                    id="registerEmailError">
                </small>

            </div>


            <!-- Phone -->
            <div class="form-group">

                <label for="registerPhone">
                    Phone Number
                    <span class="required">*</span>
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-phone"></i>

                    <input
                        type="tel"
                        id="registerPhone"
                        name="phone"
                        placeholder="0123456789"
                        maxlength="20"
                        required>

                </div>

                <small
                    class="form-error"
                    id="registerPhoneError">
                </small>

            </div>


            <!-- Account Type -->
            <div class="form-group">

                <label for="registerRole">
                    Account Type
                    <span class="required">*</span>
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-users"></i>

                    <select
                        id="registerRole"
                        name="role"
                        required
                        onchange="toggleVendorFields()">

                        <option value="">
                            Select account type
                        </option>

                        <option value="customer">
                            Customer
                        </option>

                        <option value="vendor">
                            Vendor
                        </option>

                    </select>

                </div>

            </div>


            <!-- Vendor Information -->
            <div
                id="vendorRegisterFields"
                class="vendor-register-fields"
                style="display:none;">

                <div class="vendor-section-title">

                    <i class="fas fa-store"></i>

                    <span>
                        Vendor Information
                    </span>

                </div>


                <!-- Business Name -->
                <div class="form-group">

                    <label for="businessName">
                        Business Name
                        <span class="required">*</span>
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-store"></i>

                        <input
                            type="text"
                            id="businessName"
                            name="business_name"
                            placeholder="Enter your business name"
                            maxlength="150">

                    </div>

                </div>


                <!-- Business Category -->
                <div class="form-group">

                    <label for="businessCategory">
                        Business Category
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-tags"></i>

                        <input
                            type="text"
                            id="businessCategory"
                            name="category"
                            placeholder="Example: Food, Fashion, Technology"
                            maxlength="100">

                    </div>

                </div>


                <!-- Business Description -->
                <div class="form-group">

                    <label for="businessDescription">
                        Business Description
                    </label>

                    <div class="input-wrapper textarea-wrapper">

                        <i class="fas fa-align-left"></i>

                        <textarea
                            id="businessDescription"
                            name="business_description"
                            placeholder="Tell customers about your business..."
                            rows="4"></textarea>

                    </div>

                </div>


                <!-- Business Address -->
                <div class="form-group">

                    <label for="businessAddress">
                        Business Address
                    </label>

                    <div class="input-wrapper textarea-wrapper">

                        <i class="fas fa-map-marker-alt"></i>

                        <textarea
                            id="businessAddress"
                            name="business_address"
                            placeholder="Enter your business address..."
                            rows="3"></textarea>

                    </div>

                </div>


                <!-- Delivery Method -->
                <div class="form-group">

                    <label for="deliveryMethod">
                        Delivery Method
                    </label>

                    <div class="input-wrapper">

                        <i class="fas fa-truck"></i>

                        <select
                            id="deliveryMethod"
                            name="delivery_method">

                            <option value="Both">
                                Pickup & Postage
                            </option>

                            <option value="Pickup">
                                Pickup Only
                            </option>

                            <option value="Postage">
                                Postage Only
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- Password -->
            <div class="form-group">

                <label for="registerPassword">
                    Password
                    <span class="required">*</span>
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        id="registerPassword"
                        name="password"
                        placeholder="Create a password"
                        minlength="8"
                        maxlength="255"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('registerPassword', this)"
                        aria-label="Show password">

                        <i class="fas fa-eye"></i>

                    </button>

                </div>

                <small class="password-hint">
                    Password must contain at least 8 characters.
                </small>

                <small
                    class="form-error"
                    id="registerPasswordError">
                </small>

            </div>


            <!-- Confirm Password -->
            <div class="form-group">

                <label for="registerConfirmPassword">
                    Confirm Password
                    <span class="required">*</span>
                </label>

                <div class="input-wrapper">

                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        id="registerConfirmPassword"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        minlength="8"
                        maxlength="255"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('registerConfirmPassword', this)"
                        aria-label="Show password">

                        <i class="fas fa-eye"></i>

                    </button>

                </div>

                <small
                    class="form-error"
                    id="registerConfirmPasswordError">
                </small>

            </div>


            <!-- Profile Image -->
            <div class="form-group">

                <label for="registerProfileImage">
                    Profile Image
                    <span class="optional">
                        (Optional)
                    </span>
                </label>

                <div class="file-input-wrapper">

                    <input
                        type="file"
                        id="registerProfileImage"
                        name="profile_image"
                        accept="image/jpeg,image/png,image/jpg,image/webp">

                    <label
                        for="registerProfileImage"
                        class="file-input-label">

                        <i class="fas fa-cloud-upload-alt"></i>

                        <span id="profileImageText">
                            Choose profile image
                        </span>

                    </label>

                </div>

            </div>


            <!-- Terms -->
            <div class="form-checkbox">

                <input
                    type="checkbox"
                    id="registerTerms"
                    name="terms"
                    value="1"
                    required>

                <label for="registerTerms">

                    I agree to the
                    <a href="contact.php">
                        HochipoHub Terms & Conditions
                    </a>
                    and Privacy Policy.

                </label>

            </div>


            <!-- MFA -->
            <div class="mfa-info">

                <i class="fas fa-shield-alt"></i>

                <div>

                    <strong>
                        Account Security
                    </strong>

                    <p>
                        Multi-factor authentication is enabled
                        by default to help protect your account.
                    </p>

                </div>

            </div>


            <!-- Submit -->
            <button
                type="submit"
                class="btn-register"
                id="registerSubmitBtn">

                <i class="fas fa-user-plus"></i>

                <span>
                    Create Account
                </span>

            </button>


            <!-- Login Link -->
            <div class="modal-switch">

                <span>
                    Already have an account?
                </span>

                <button
                    type="button"
                    onclick="switchToLogin()">

                    Login here

                </button>

            </div>

        </form>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Toggle Vendor Fields
|--------------------------------------------------------------------------
*/

function toggleVendorFields() {

    const role = document.getElementById("registerRole");

    const vendorFields =
        document.getElementById("vendorRegisterFields");

    const businessName =
        document.getElementById("businessName");

    if (!role || !vendorFields) {
        return;
    }

    if (role.value === "vendor") {

        vendorFields.style.display = "block";

        if (businessName) {
            businessName.required = true;
        }

    } else {

        vendorFields.style.display = "none";

        if (businessName) {
            businessName.required = false;
        }

    }

}


/*
|--------------------------------------------------------------------------
| Toggle Password
|--------------------------------------------------------------------------
*/

function togglePassword(inputId, button) {

    const input =
        document.getElementById(inputId);

    if (!input || !button) {
        return;
    }

    const icon =
        button.querySelector("i");

    if (input.type === "password") {

        input.type = "text";

        if (icon) {
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }

        button.setAttribute(
            "aria-label",
            "Hide password"
        );

    } else {

        input.type = "password";

        if (icon) {
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }

        button.setAttribute(
            "aria-label",
            "Show password"
        );

    }

}


/*
|--------------------------------------------------------------------------
| Profile Image Name
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const imageInput =
        document.getElementById("registerProfileImage");

    const imageText =
        document.getElementById("profileImageText");

    if (imageInput && imageText) {

        imageInput.addEventListener(
            "change",
            function () {

                if (this.files.length > 0) {

                    imageText.textContent =
                        this.files[0].name;

                } else {

                    imageText.textContent =
                        "Choose profile image";

                }

            }
        );

    }

});


/*
|--------------------------------------------------------------------------
| Register Form Validation
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const form =
        document.getElementById("registerForm");

    if (!form) {
        return;
    }

    form.addEventListener("submit", function (event) {

        let valid = true;

        const name =
            document.getElementById("registerName");

        const email =
            document.getElementById("registerEmail");

        const phone =
            document.getElementById("registerPhone");

        const role =
            document.getElementById("registerRole");

        const password =
            document.getElementById("registerPassword");

        const confirmPassword =
            document.getElementById(
                "registerConfirmPassword"
            );

        const terms =
            document.getElementById("registerTerms");


        /*
        |--------------------------------------------------------------------------
        | Clear Errors
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll("#registerForm .form-error")
            .forEach(function (error) {

                error.textContent = "";

            });


        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        */

        if (
            !name ||
            name.value.trim().length < 2
        ) {

            showRegisterError(
                "registerNameError",
                "Please enter your full name."
            );

            valid = false;

        }


        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        if (
            !email ||
            !/^[^\s@]+@[^\s@]+\.[^\s@]+$/
                .test(email.value.trim())
        ) {

            showRegisterError(
                "registerEmailError",
                "Please enter a valid email address."
            );

            valid = false;

        }


        /*
        |--------------------------------------------------------------------------
        | Phone
        |--------------------------------------------------------------------------
        */

        if (
            !phone ||
            !/^[0-9+\-\s]{8,20}$/
                .test(phone.value.trim())
        ) {

            showRegisterError(
                "registerPhoneError",
                "Please enter a valid phone number."
            );

            valid = false;

        }


        /*
        |--------------------------------------------------------------------------
        | Role
        |--------------------------------------------------------------------------
        */

        if (
            !role ||
            !["customer", "vendor"]
                .includes(role.value)
        ) {

            valid = false;

            alert(
                "Please select an account type."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Vendor Business Name
        |--------------------------------------------------------------------------
        */

        if (
            role &&
            role.value === "vendor"
        ) {

            const businessName =
                document.getElementById("businessName");

            if (
                !businessName ||
                businessName.value.trim() === ""
            ) {

                valid = false;

                alert(
                    "Please enter your business name."
                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Password
        |--------------------------------------------------------------------------
        */

        if (
            !password ||
            password.value.length < 8
        ) {

            showRegisterError(
                "registerPasswordError",
                "Password must contain at least 8 characters."
            );

            valid = false;

        }


        /*
        |--------------------------------------------------------------------------
        | Confirm Password
        |--------------------------------------------------------------------------
        */

        if (
            !confirmPassword ||
            password.value !== confirmPassword.value
        ) {

            showRegisterError(
                "registerConfirmPasswordError",
                "Passwords do not match."
            );

            valid = false;

        }


        /*
        |--------------------------------------------------------------------------
        | Terms
        |--------------------------------------------------------------------------
        */

        if (
            !terms ||
            !terms.checked
        ) {

            valid = false;

            alert(
                "Please agree to the Terms & Conditions."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Stop Submit
        |--------------------------------------------------------------------------
        */

        if (!valid) {

            event.preventDefault();

            return false;

        }


        /*
        |--------------------------------------------------------------------------
        | Loading State
        |--------------------------------------------------------------------------
        */

        const submitButton =
            document.getElementById(
                "registerSubmitBtn"
            );

        if (submitButton) {

            submitButton.disabled = true;

            submitButton.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> ' +
                '<span>Creating Account...</span>';

        }

    });

});


/*
|--------------------------------------------------------------------------
| Show Error
|--------------------------------------------------------------------------
*/

function showRegisterError(
    elementId,
    message
) {

    const element =
        document.getElementById(elementId);

    if (element) {
        element.textContent = message;
    }

}


/*
|--------------------------------------------------------------------------
| Switch To Login
|--------------------------------------------------------------------------
*/

function switchToLogin() {

    if (typeof closeRegisterModal === "function") {
        closeRegisterModal();
    }

    if (typeof openLoginModal === "function") {
        setTimeout(function () {
            openLoginModal();
        }, 150);
    }

}

</script>