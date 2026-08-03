/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FORM VALIDATION
|--------------------------------------------------------------------------
| Handles:
| - Login validation
| - Register validation
| - Password confirmation
| - Email validation
| - Phone validation
| - Password strength
| - Vendor registration validation
| - Real-time validation
| - Form submission protection
|--------------------------------------------------------------------------
*/

"use strict";


/* ==============================================================
   DOM READY
============================================================== */

document.addEventListener("DOMContentLoaded", function () {

    initLoginValidation();
    initRegisterValidation();
    initPasswordStrength();
    initRealtimeValidation();
    initPasswordToggle();

});


/* ==============================================================
   VALIDATION CONFIG
============================================================== */

const ValidationConfig = {

    minPasswordLength: 8,

    maxPasswordLength: 72,

    minNameLength: 2,

    maxNameLength: 100,

    phoneMinLength: 9,

    phoneMaxLength: 15

};


/* ==============================================================
   REGEX
============================================================== */

const ValidationRegex = {

    email:
        /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,

    phone:
        /^[0-9+\-\s()]{9,20}$/,

    name:
        /^[A-Za-zÀ-ÿ\s.'-]{2,100}$/,

    passwordUppercase:
        /[A-Z]/,

    passwordLowercase:
        /[a-z]/,

    passwordNumber:
        /[0-9]/,

    passwordSpecial:
        /[^A-Za-z0-9]/

};


/* ==============================================================
   HELPER - GET ELEMENT
============================================================== */

function getElement(selector, parent = document) {

    if (!selector) {
        return null;
    }

    return parent.querySelector(selector);

}


/* ==============================================================
   HELPER - GET VALUE
============================================================== */

function getValue(element) {

    if (!element) {
        return "";
    }

    return element.value.trim();

}


/* ==============================================================
   HELPER - SHOW ERROR
============================================================== */

function showValidationError(input, message) {

    if (!input) {
        return false;
    }

    clearValidationError(input);

    input.classList.add("is-invalid");

    input.setAttribute("aria-invalid", "true");

    let errorElement =
        input.parentElement.querySelector(
            ".validation-error"
        );

    if (!errorElement) {

        errorElement =
            document.createElement("small");

        errorElement.className =
            "validation-error";

        input.parentElement.appendChild(
            errorElement
        );

    }

    errorElement.textContent = message;

    return false;

}


/* ==============================================================
   HELPER - SHOW SUCCESS
============================================================== */

function showValidationSuccess(input) {

    if (!input) {
        return true;
    }

    clearValidationError(input);

    input.classList.add("is-valid");

    input.setAttribute("aria-invalid", "false");

    return true;

}


/* ==============================================================
   HELPER - CLEAR ERROR
============================================================== */

function clearValidationError(input) {

    if (!input) {
        return;
    }

    input.classList.remove("is-invalid");

    input.classList.remove("is-valid");

    input.removeAttribute("aria-invalid");

    const errorElement =
        input.parentElement.querySelector(
            ".validation-error"
        );

    if (errorElement) {
        errorElement.remove();
    }

}


/* ==============================================================
   REQUIRED FIELD
============================================================== */

function validateRequired(input, fieldName = "This field") {

    if (!input) {
        return true;
    }

    const value = getValue(input);

    if (value === "") {

        return showValidationError(
            input,
            `${fieldName} is required.`
        );

    }

    return showValidationSuccess(input);

}


/* ==============================================================
   NAME VALIDATION
============================================================== */

function validateName(input) {

    if (!input) {
        return true;
    }

    const value = getValue(input);

    if (value === "") {

        return showValidationError(
            input,
            "Name is required."
        );

    }

    if (
        value.length <
        ValidationConfig.minNameLength
    ) {

        return showValidationError(
            input,
            "Name is too short."
        );

    }

    if (
        value.length >
        ValidationConfig.maxNameLength
    ) {

        return showValidationError(
            input,
            "Name is too long."
        );

    }

    if (!ValidationRegex.name.test(value)) {

        return showValidationError(
            input,
            "Please enter a valid name."
        );

    }

    return showValidationSuccess(input);

}


/* ==============================================================
   EMAIL VALIDATION
============================================================== */

function validateEmail(input) {

    if (!input) {
        return true;
    }

    const value = getValue(input);

    if (value === "") {

        return showValidationError(
            input,
            "Email address is required."
        );

    }

    if (!ValidationRegex.email.test(value)) {

        return showValidationError(
            input,
            "Please enter a valid email address."
        );

    }

    return showValidationSuccess(input);

}


/* ==============================================================
   PHONE VALIDATION
============================================================== */

function validatePhone(input) {

    if (!input) {
        return true;
    }

    const value = getValue(input);

    if (value === "") {

        return showValidationError(
            input,
            "Phone number is required."
        );

    }

    if (!ValidationRegex.phone.test(value)) {

        return showValidationError(
            input,
            "Please enter a valid phone number."
        );

    }

    const digitsOnly =
        value.replace(/\D/g, "");

    if (
        digitsOnly.length <
        ValidationConfig.phoneMinLength
    ) {

        return showValidationError(
            input,
            "Phone number is too short."
        );

    }

    if (
        digitsOnly.length >
        ValidationConfig.phoneMaxLength
    ) {

        return showValidationError(
            input,
            "Phone number is too long."
        );

    }

    return showValidationSuccess(input);

}


/* ==============================================================
   PASSWORD VALIDATION
============================================================== */

function validatePassword(input) {

    if (!input) {
        return true;
    }

    const value = input.value;

    if (value === "") {

        return showValidationError(
            input,
            "Password is required."
        );

    }

    if (
        value.length <
        ValidationConfig.minPasswordLength
    ) {

        return showValidationError(
            input,
            `Password must contain at least ${ValidationConfig.minPasswordLength} characters.`
        );

    }

    if (
        value.length >
        ValidationConfig.maxPasswordLength
    ) {

        return showValidationError(
            input,
            "Password is too long."
        );

    }

    if (!ValidationRegex.passwordUppercase.test(value)) {

        return showValidationError(
            input,
            "Password must contain at least one uppercase letter."
        );

    }

    if (!ValidationRegex.passwordLowercase.test(value)) {

        return showValidationError(
            input,
            "Password must contain at least one lowercase letter."
        );

    }

    if (!ValidationRegex.passwordNumber.test(value)) {

        return showValidationError(
            input,
            "Password must contain at least one number."
        );

    }

    if (!ValidationRegex.passwordSpecial.test(value)) {

        return showValidationError(
            input,
            "Password must contain at least one special character."
        );

    }

    return showValidationSuccess(input);

}


/* ==============================================================
   CONFIRM PASSWORD
============================================================== */

function validateConfirmPassword(
    passwordInput,
    confirmInput
) {

    if (!passwordInput || !confirmInput) {
        return true;
    }

    const password =
        passwordInput.value;

    const confirmation =
        confirmInput.value;

    if (confirmation === "") {

        return showValidationError(
            confirmInput,
            "Please confirm your password."
        );

    }

    if (password !== confirmation) {

        return showValidationError(
            confirmInput,
            "Passwords do not match."
        );

    }

    return showValidationSuccess(
        confirmInput
    );

}


/* ==============================================================
   LOGIN VALIDATION
============================================================== */

function initLoginValidation() {

    const loginForms =
        document.querySelectorAll(
            "#loginForm, .login-form"
        );

    loginForms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function (event) {

                let valid = true;

                const email =
                    form.querySelector(
                        'input[name="email"], #loginEmail'
                    );

                const password =
                    form.querySelector(
                        'input[name="password"], #loginPassword'
                    );

                if (email) {

                    if (!validateEmail(email)) {
                        valid = false;
                    }

                }

                if (password) {

                    if (
                        !validateRequired(
                            password,
                            "Password"
                        )
                    ) {

                        valid = false;

                    }

                }

                if (!valid) {

                    event.preventDefault();

                    focusFirstInvalidField(form);

                }

            }
        );

    });

}


/* ==============================================================
   REGISTER VALIDATION
============================================================== */

function initRegisterValidation() {

    const registerForms =
        document.querySelectorAll(
            "#registerForm, .register-form"
        );

    registerForms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function (event) {

                let valid = true;

                const name =
                    form.querySelector(
                        'input[name="name"], #registerName'
                    );

                const email =
                    form.querySelector(
                        'input[name="email"], #registerEmail'
                    );

                const phone =
                    form.querySelector(
                        'input[name="phone"], #registerPhone'
                    );

                const password =
                    form.querySelector(
                        'input[name="password"], #registerPassword'
                    );

                const confirmPassword =
                    form.querySelector(
                        'input[name="confirm_password"], input[name="password_confirmation"], #confirmPassword, #registerConfirmPassword'
                    );

                if (name) {

                    if (!validateName(name)) {
                        valid = false;
                    }

                }

                if (email) {

                    if (!validateEmail(email)) {
                        valid = false;
                    }

                }

                if (phone) {

                    if (!validatePhone(phone)) {
                        valid = false;
                    }

                }

                if (password) {

                    if (!validatePassword(password)) {
                        valid = false;
                    }

                }

                if (
                    password &&
                    confirmPassword
                ) {

                    if (
                        !validateConfirmPassword(
                            password,
                            confirmPassword
                        )
                    ) {

                        valid = false;

                    }

                }

                /*
                |--------------------------------------------------------------------------
                | VENDOR REGISTRATION
                |--------------------------------------------------------------------------
                */

                const role =
                    form.querySelector(
                        'input[name="role"]:checked, select[name="role"]'
                    );

                if (
                    role &&
                    role.value.toLowerCase() === "vendor"
                ) {

                    const businessName =
                        form.querySelector(
                            'input[name="business_name"], #businessName'
                        );

                    const businessAddress =
                        form.querySelector(
                            'textarea[name="business_address"], #businessAddress'
                        );

                    if (businessName) {

                        if (
                            !validateRequired(
                                businessName,
                                "Business name"
                            )
                        ) {

                            valid = false;

                        }

                    }

                    if (businessAddress) {

                        if (
                            !validateRequired(
                                businessAddress,
                                "Business address"
                            )
                        ) {

                            valid = false;

                        }

                    }

                }

                if (!valid) {

                    event.preventDefault();

                    focusFirstInvalidField(form);

                }

            }
        );

    });

}


/* ==============================================================
   PASSWORD STRENGTH
============================================================== */

function calculatePasswordStrength(password) {

    let score = 0;

    if (!password) {
        return 0;
    }

    if (password.length >= 8) {
        score++;
    }

    if (password.length >= 12) {
        score++;
    }

    if (ValidationRegex.passwordUppercase.test(password)) {
        score++;
    }

    if (ValidationRegex.passwordLowercase.test(password)) {
        score++;
    }

    if (ValidationRegex.passwordNumber.test(password)) {
        score++;
    }

    if (ValidationRegex.passwordSpecial.test(password)) {
        score++;
    }

    return Math.min(score, 6);

}


/* ==============================================================
   PASSWORD STRENGTH LABEL
============================================================== */

function getPasswordStrengthLabel(score) {

    if (score <= 1) {
        return "Very Weak";
    }

    if (score === 2) {
        return "Weak";
    }

    if (score === 3) {
        return "Fair";
    }

    if (score === 4) {
        return "Good";
    }

    if (score === 5) {
        return "Strong";
    }

    return "Very Strong";

}


/* ==============================================================
   PASSWORD STRENGTH UI
============================================================== */

function updatePasswordStrength(input) {

    if (!input) {
        return;
    }

    const password =
        input.value;

    let strengthContainer =
        input.parentElement.parentElement.querySelector(
            ".password-strength"
        );

    if (!strengthContainer) {

        strengthContainer =
            document.createElement("div");

        strengthContainer.className =
            "password-strength";

        strengthContainer.innerHTML = `
            <div class="password-strength-bar">
                <span></span>
            </div>
            <div class="password-strength-text">
                <span>Password strength</span>
            </div>
        `;

        input.parentElement.parentElement.appendChild(
            strengthContainer
        );

    }

    const score =
        calculatePasswordStrength(password);

    const bar =
        strengthContainer.querySelector(
            ".password-strength-bar span"
        );

    const text =
        strengthContainer.querySelector(
            ".password-strength-text span"
        );

    if (!password) {

        bar.style.width = "0%";

        text.textContent =
            "Password strength";

        strengthContainer.className =
            "password-strength";

        return;

    }

    const percentage =
        Math.round((score / 6) * 100);

    bar.style.width =
        `${percentage}%`;

    text.textContent =
        getPasswordStrengthLabel(score);

    strengthContainer.className =
        `password-strength strength-${score}`;

}


/* ==============================================================
   INIT PASSWORD STRENGTH
============================================================== */

function initPasswordStrength() {

    const passwordInputs =
        document.querySelectorAll(
            'input[type="password"][name="password"], #registerPassword'
        );

    passwordInputs.forEach(function (input) {

        input.addEventListener(
            "input",
            function () {

                updatePasswordStrength(input);

            }
        );

    });

}


/* ==============================================================
   REALTIME VALIDATION
============================================================== */

function initRealtimeValidation() {

    const emailInputs =
        document.querySelectorAll(
            'input[type="email"], input[name="email"]'
        );

    emailInputs.forEach(function (input) {

        input.addEventListener(
            "blur",
            function () {

                validateEmail(input);

            }
        );

    });


    const phoneInputs =
        document.querySelectorAll(
            'input[name="phone"], input[type="tel"]'
        );

    phoneInputs.forEach(function (input) {

        input.addEventListener(
            "blur",
            function () {

                validatePhone(input);

            }
        );

    });


    const nameInputs =
        document.querySelectorAll(
            'input[name="name"], #registerName'
        );

    nameInputs.forEach(function (input) {

        input.addEventListener(
            "blur",
            function () {

                validateName(input);

            }
        );

    });


    const passwordInputs =
        document.querySelectorAll(
            'input[name="password"], #registerPassword'
        );

    passwordInputs.forEach(function (input) {

        input.addEventListener(
            "blur",
            function () {

                validatePassword(input);

            }
        );

    });


    const confirmInputs =
        document.querySelectorAll(
            'input[name="confirm_password"], input[name="password_confirmation"], #confirmPassword, #registerConfirmPassword'
        );

    confirmInputs.forEach(function (input) {

        input.addEventListener(
            "blur",
            function () {

                const form =
                    input.closest("form");

                if (!form) {
                    return;
                }

                const password =
                    form.querySelector(
                        'input[name="password"], #registerPassword'
                    );

                if (password) {

                    validateConfirmPassword(
                        password,
                        input
                    );

                }

            }
        );

    });

}


/* ==============================================================
   PASSWORD SHOW / HIDE
============================================================== */

function initPasswordToggle() {

    const toggleButtons =
        document.querySelectorAll(
            "[data-password-toggle], .password-toggle"
        );

    toggleButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                let targetSelector =
                    button.getAttribute(
                        "data-target"
                    );

                let input = null;

                if (targetSelector) {

                    input =
                        document.querySelector(
                            targetSelector
                        );

                }

                if (!input) {

                    input =
                        button.parentElement.querySelector(
                            'input[type="password"], input[type="text"]'
                        );

                }

                if (!input) {
                    return;
                }

                if (
                    input.type === "password"
                ) {

                    input.type = "text";

                    button.classList.add(
                        "active"
                    );

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                    if (button.querySelector("i")) {

                        button.querySelector("i").className =
                            "fa-solid fa-eye-slash";

                    }

                } else {

                    input.type = "password";

                    button.classList.remove(
                        "active"
                    );

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                    if (button.querySelector("i")) {

                        button.querySelector("i").className =
                            "fa-solid fa-eye";

                    }

                }

            }
        );

    });

}


/* ==============================================================
   FOCUS FIRST INVALID FIELD
============================================================== */

function focusFirstInvalidField(form) {

    if (!form) {
        return;
    }

    const invalid =
        form.querySelector(
            ".is-invalid"
        );

    if (invalid) {

        invalid.focus();

        invalid.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });

    }

}


/* ==============================================================
   PREVENT DOUBLE SUBMISSION
============================================================== */

function preventDoubleSubmission(form) {

    if (!form) {
        return;
    }

    if (
        form.dataset.submitting === "true"
    ) {
        return;
    }

    form.dataset.submitting = "true";

    const submitButtons =
        form.querySelectorAll(
            'button[type="submit"], input[type="submit"]'
        );

    submitButtons.forEach(function (button) {

        button.disabled = true;

        button.classList.add(
            "is-loading"
        );

        if (
            button.tagName.toLowerCase() ===
            "button"
        ) {

            button.dataset.originalText =
                button.innerHTML;

            button.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Processing...
            `;

        }

    });

}


/* ==============================================================
   RESET DOUBLE SUBMISSION
============================================================== */

function resetDoubleSubmission(form) {

    if (!form) {
        return;
    }

    form.dataset.submitting = "false";

    const submitButtons =
        form.querySelectorAll(
            'button[type="submit"], input[type="submit"]'
        );

    submitButtons.forEach(function (button) {

        button.disabled = false;

        button.classList.remove(
            "is-loading"
        );

        if (
            button.dataset.originalText
        ) {

            button.innerHTML =
                button.dataset.originalText;

            delete button.dataset.originalText;

        }

    });

}


/* ==============================================================
   FORM SUBMISSION PROTECTION
============================================================== */

document.addEventListener(
    "submit",
    function (event) {

        const form =
            event.target;

        if (!form || form.tagName !== "FORM") {
            return;
        }

        if (
            form.dataset.validationSkip ===
            "true"
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Don't lock the form if browser validation fails
        |--------------------------------------------------------------------------
        */

        if (!form.checkValidity()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent accidental double click
        |--------------------------------------------------------------------------
        */

        if (
            form.dataset.submitting === "true"
        ) {

            event.preventDefault();

            return;

        }

        /*
        |--------------------------------------------------------------------------
        | Only lock after validation has passed
        |--------------------------------------------------------------------------
        */

        setTimeout(function () {

            if (!event.defaultPrevented) {

                preventDoubleSubmission(
                    form
                );

            }

        }, 0);

    },
    true
);


/* ==============================================================
   CLEAR VALIDATION WHEN USER TYPES
============================================================== */

document.addEventListener(
    "input",
    function (event) {

        const input =
            event.target;

        if (!input.matches("input, textarea, select")) {
            return;
        }

        if (
            input.classList.contains(
                "is-invalid"
            )
        ) {

            clearValidationError(
                input
            );

        }

    }
);


/* ==============================================================
   OTP VALIDATION
============================================================== */

function validateOTP(input) {

    if (!input) {
        return false;
    }

    const value =
        getValue(input);

    if (value === "") {

        return showValidationError(
            input,
            "Verification code is required."
        );

    }

    if (!/^[0-9]{4,8}$/.test(value)) {

        return showValidationError(
            input,
            "Please enter a valid verification code."
        );

    }

    return showValidationSuccess(
        input
    );

}


/* ==============================================================
   INIT OTP VALIDATION
============================================================== */

function initOTPValidation() {

    const otpInputs =
        document.querySelectorAll(
            'input[name="otp"], input[name="mfa_code"], input[name="verification_code"], #otpCode'
        );

    otpInputs.forEach(function (input) {

        input.addEventListener(
            "input",
            function () {

                input.value =
                    input.value.replace(
                        /\D/g,
                        ""
                    );

                if (
                    input.value.length >= 4
                ) {

                    validateOTP(
                        input
                    );

                }

            }
        );

    });

}


/* ==============================================================
   FORGOT PASSWORD VALIDATION
============================================================== */

function initForgotPasswordValidation() {

    const forms =
        document.querySelectorAll(
            "#forgotPasswordForm, .forgot-password-form"
        );

    forms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const email =
                    form.querySelector(
                        'input[name="email"], #forgotEmail'
                    );

                const phone =
                    form.querySelector(
                        'input[name="phone"], #forgotPhone'
                    );

                let valid = false;

                if (email && getValue(email)) {

                    valid =
                        validateEmail(email);

                } else if (
                    phone &&
                    getValue(phone)
                ) {

                    valid =
                        validatePhone(phone);

                } else {

                    if (email) {

                        showValidationError(
                            email,
                            "Enter your email or phone number."
                        );

                    } else if (phone) {

                        showValidationError(
                            phone,
                            "Enter your email or phone number."
                        );

                    }

                    valid = false;

                }

                if (!valid) {

                    event.preventDefault();

                    focusFirstInvalidField(
                        form
                    );

                }

            }
        );

    });

}


/* ==============================================================
   INIT EVERYTHING
============================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        initOTPValidation();

        initForgotPasswordValidation();

    }
);


/* ==============================================================
   GLOBAL EXPORTS
============================================================== */

window.HochipoValidation = {

    validateRequired,

    validateName,

    validateEmail,

    validatePhone,

    validatePassword,

    validateConfirmPassword,

    validateOTP,

    calculatePasswordStrength,

    clearValidationError,

    showValidationError,

    showValidationSuccess,

    resetDoubleSubmission

};