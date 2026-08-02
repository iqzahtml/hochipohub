/*
|--------------------------------------------------------------------------
| HochipoHub - Modal Controller
|--------------------------------------------------------------------------
| Handles:
| - Login modal
| - Register modal
| - Forgot password modal
| - MFA modal
| - Reset password modal
| - Customer / Vendor selection
| - Password visibility
| - OTP input
| - Modal switching
| - ESC key
| - Overlay click
| - Body scroll lock
|--------------------------------------------------------------------------
*/

(function () {

    "use strict";


    /* =========================================================
       DOM READY
    ========================================================== */

    document.addEventListener("DOMContentLoaded", function () {

        initModalSystem();

        initPasswordToggles();

        initAccountTypeSelector();

        initOtpInputs();

        initPasswordStrength();

        initPasswordRequirements();

        initModalForms();

        initForgotPasswordFlow();

        initGlobalModalTriggers();

        initEscapeKey();

        initOverlayClose();

        initBackToTop();

    });


    /* =========================================================
       MODAL SYSTEM
    ========================================================== */

    function initModalSystem() {

        window.HochipoModal = {

            open: function (modalId) {

                openModal(modalId);

            },

            close: function (modalId) {

                closeModal(modalId);

            },

            closeAll: function () {

                closeAllModals();

            }

        };

    }


    /* =========================================================
       OPEN MODAL
    ========================================================== */

    function openModal(modalId) {

        const modal = document.getElementById(modalId);

        if (!modal) {

            console.warn(
                "HochipoHub: Modal not found:",
                modalId
            );

            return;

        }


        /*
        | Hide all other modals first
        */

        document
            .querySelectorAll(".modal-overlay")
            .forEach(function (item) {

                if (item !== modal) {

                    item.classList.remove("active");

                    item.setAttribute(
                        "hidden",
                        ""
                    );

                }

            });


        /*
        | Show requested modal
        */

        modal.removeAttribute("hidden");


        /*
        | Force browser reflow
        | This allows CSS animation to trigger.
        */

        void modal.offsetWidth;


        modal.classList.add("active");


        /*
        | Prevent background scrolling
        */

        document.body.classList.add(
            "modal-open"
        );


        /*
        | Focus first suitable input
        */

        setTimeout(function () {

            const firstInput =
                modal.querySelector(
                    "input:not([type='hidden']), textarea, select"
                );

            if (firstInput) {

                firstInput.focus();

            }

        }, 150);

    }


    /* =========================================================
       CLOSE MODAL
    ========================================================== */

    function closeModal(modalId) {

        const modal =
            document.getElementById(modalId);

        if (!modal) {

            return;

        }


        modal.classList.remove("active");


        setTimeout(function () {

            modal.setAttribute(
                "hidden",
                ""
            );

            /*
            | Only remove body lock if
            | no modal remains open.
            */

            const activeModal =
                document.querySelector(
                    ".modal-overlay.active"
                );

            if (!activeModal) {

                document.body.classList.remove("modal-open"
                );

            }

        }, 250);

    }


    /* =========================================================
       CLOSE ALL MODALS
    ========================================================== */

    function closeAllModals() {

        document
            .querySelectorAll(".modal-overlay")
            .forEach(function (modal) {

                modal.classList.remove(
                    "active"
                );

                modal.setAttribute(
                    "hidden",
                    ""
                );

            });


        document.body.classList.remove(
            "modal-open"
        );

    }


    /* =========================================================
       GLOBAL MODAL TRIGGERS
    ========================================================== */

    function initGlobalModalTriggers() {

        document.addEventListener(
            "click",
            function (event) {

                /*
                | Open modal
                */

                const openTrigger =
                    event.target.closest(
                        "[data-open-modal]"
                    );

                if (openTrigger) {

                    event.preventDefault();

                    const modalId =
                        openTrigger.getAttribute(
                            "data-open-modal"
                        );

                    openModal(modalId);

                    return;

                }


                /*
                | Close modal
                */

                const closeTrigger =
                    event.target.closest(
                        "[data-close-modal]"
                    );

                if (closeTrigger) {

                    event.preventDefault();

                    const modalId =
                        closeTrigger.getAttribute(
                            "data-close-modal"
                        );

                    closeModal(modalId);

                    return;

                }


                /*
                | Switch modal
                */

                const switchTrigger =
                    event.target.closest(
                        "[data-switch-modal]"
                    );

                if (switchTrigger) {

                    event.preventDefault();

                    const currentModal =
                        switchTrigger.getAttribute(
                            "data-switch-modal"
                        );

                    const targetModal =
                        switchTrigger.getAttribute(
                            "data-target-modal"
                        );


                    /*
                    | Vendor CTA can tell register
                    | modal to select Vendor.
                    */

                    const registerRole =
                        switchTrigger.getAttribute(
                            "data-register-role"
                        );


                    closeModal(currentModal);


                    setTimeout(function () {

                        if (
                            registerRole === "vendor"
                        ) {

                            setRegisterRole(
                                "vendor"
                            );

                        }


                        openModal(targetModal);

                    }, 280);

                }

            }
        );

    }


    /* =========================================================
       ESC KEY
    ========================================================== */

    function initEscapeKey() {

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key !== "Escape"
                ) {

                    return;

                }


                const activeModal =
                    document.querySelector(
                        ".modal-overlay.active"
                    );if (activeModal) {

                    closeModal(
                        activeModal.id
                    );

                }

            }
        );

    }


    /* =========================================================
       OVERLAY CLICK
    ========================================================== */

    function initOverlayClose() {

        document.addEventListener(
            "click",
            function (event) {

                if (
                    event.target.classList.contains(
                        "modal-overlay"
                    )
                ) {

                    /*
                    | Don't close if user clicks
                    | outside only when explicitly
                    | allowed.
                    */

                    closeModal(
                        event.target.id
                    );

                }

            }
        );

    }


    /* =========================================================
       PASSWORD TOGGLE
    ========================================================== */

    function initPasswordToggles() {

        document.addEventListener(
            "click",
            function (event) {

                const button =
                    event.target.closest(
                        ".password-toggle"
                    );

                if (!button) {

                    return;

                }


                event.preventDefault();


                const targetId =
                    button.getAttribute(
                        "data-password-target"
                    );


                const input =
                    document.getElementById(
                        targetId
                    );


                if (!input) {

                    return;

                }


                const icon =
                    button.querySelector("i");


                if (
                    input.type === "password"
                ) {

                    input.type = "text";


                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );


                    if (icon) {

                        icon.classList.remove(
                            "fa-eye"
                        );

                        icon.classList.add(
                            "fa-eye-slash"
                        );

                    }

                } else {

                    input.type = "password";


                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );


                    if (icon) {

                        icon.classList.remove(
                            "fa-eye-slash"
                        );

                        icon.classList.add(
                            "fa-eye"
                        );

                    }

                }

            }
        );

    }


    /* =========================================================
       ACCOUNT TYPE
    ========================================================== */

    function initAccountTypeSelector() {

        const cards =
            document.querySelectorAll(
                "[data-role-card]"
            );


        cards.forEach(function (card) {

            const radio =
                card.querySelector(
                    "input[type='radio']"
                );


            if (!radio) {

                return;

            }


            card.addEventListener(
                "click",
                function () {

                    radio.checked = true;

                    setRegisterRole(
                        radio.value
                    );

                }
            );


            radio.addEventListener(
                "change",
                function () {

                    setRegisterRole(
                        radio.value
                    );

                }
            );

        });


        /*| Check initial role
        */

        const selectedRole =
            document.querySelector(
                "input[name='register_role']:checked"
            );


        if (selectedRole) {

            setRegisterRole(
                selectedRole.value
            );

        }

    }


    /* =========================================================
       SET REGISTER ROLE
    ========================================================== */

    function setRegisterRole(role) {

        const roleInput =
            document.getElementById(
                "registerRole"
            );


        if (roleInput) {

            roleInput.value = role;

        }


        /*
        | Update cards
        */

        document
            .querySelectorAll(
                "[data-role-card]"
            )
            .forEach(function (card) {

                const cardRole =
                    card.getAttribute(
                        "data-role-card"
                    );


                if (
                    cardRole === role
                ) {

                    card.classList.add(
                        "active"
                    );

                } else {

                    card.classList.remove(
                        "active"
                    );

                }

            });


        /*
        | Vendor information
        */

        const vendorNote =
            document.getElementById(
                "vendorRegistrationNote"
            );


        if (vendorNote) {

            if (
                role === "vendor"
            ) {

                vendorNote.removeAttribute(
                    "hidden"
                );

                vendorNote.classList.add(
                    "visible"
                );

            } else {

                vendorNote.setAttribute(
                    "hidden",
                    ""
                );

                vendorNote.classList.remove(
                    "visible"
                );

            }

        }


        /*
        | Change submit button text
        */

        const registerButton =
            document.getElementById(
                "registerSubmitBtn"
            );


        if (registerButton) {

            const text =
                registerButton.querySelector(
                    ".btn-text"
                );


            if (text) {

                if (
                    role === "vendor"
                ) {

                    text.textContent =
                        "Create Vendor Account";

                } else {

                    text.textContent =
                        "Create My Account";

                }

            }

        }

    }


    /* =========================================================
       OTP INPUTS
    ========================================================== */

    function initOtpInputs() {

        const otpInputs =
            document.querySelectorAll(
                ".otp-input"
            );


        if (!otpInputs.length) {

            return;

        }


        otpInputs.forEach(
            function (input, index) {


                /*
                | Only allow numbers
                */

                input.addEventListener(
                    "input",
                    function () {

                        this.value =
                            this.value.replace(
                                /[^0-9]/g,
                                ""
                            );


                        if (
                            this.value.length === 1
                        ) {

                            const next =
                                otpInputs[index + 1];

                            if (next) {

                                next.focus();

                            }

                        }

                    }
                );


                /*
                | Backspace moves backwards
                */

                input.addEventListener("keydown",
                    function (event) {

                        if (
                            event.key ===
                            "Backspace" &&
                            this.value === ""
                        ) {

                            const previous =
                                otpInputs[index - 1];

                            if (previous) {

                                previous.focus();

                            }

                        }

                    }
                );


                /*
                | Paste complete OTP
                */

                input.addEventListener(
                    "paste",
                    function (event) {

                        event.preventDefault();


                        const pasted =
                            (
                                event.clipboardData ||
                                window.clipboardData
                            )
                            .getData("text")
                            .replace(
                                /[^0-9]/g,
                                ""
                            );


                        if (!pasted) {

                            return;

                        }


                        otpInputs.forEach(
                            function (
                                otpInput,
                                otpIndex
                            ) {

                                otpInput.value =
                                    pasted.charAt(
                                        otpIndex
                                    );

                            }
                        );


                        const last =
                            otpInputs[
                                Math.min(
                                    pasted.length,
                                    otpInputs.length
                                ) - 1
                            ];


                        if (last) {

                            last.focus();

                        }

                    }
                );

            }
        );

    }


    /* =========================================================
       PASSWORD STRENGTH
    ========================================================== */

    function initPasswordStrength() {

        const password =
            document.getElementById(
                "registerPassword"
            );


        if (!password) {

            return;

        }


        password.addEventListener(
            "input",
            function () {

                updatePasswordStrength(
                    this.value
                );

            }
        );

    }


    function updatePasswordStrength(password) {

        const bar =
            document.getElementById(
                "strengthBar"
            );


        const text =
            document.getElementById(
                "passwordStrengthText"
            );


        if (!bar || !text) {

            return;

        }


        if (!password.length) {

            bar.style.width = "0%";

            text.textContent =
                "Password strength";

            text.className =
                "strength-text";

            return;

        }


        let score = 0;


        /*
        | Length
        */

        if (
            password.length >= 8
        ) {

            score++;

        }


        if (
            password.length >= 12
        ) {

            score++;

        }


        /*
        | Lowercase
        */

        if (
            /[a-z]/.test(password)
        ) {

            score++;

        }


        /*
        | Uppercase
        */

        if (
            /[A-Z]/.test(password)
        ) {

            score++;

        }


        /*
        | Number
        */

        if (
            /[0-9]/.test(password)
        ) {

            score++;

        }


        /*
        | Special character
        */

        if (/[^A-Za-z0-9]/.test(password)
        ) {

            score++;

        }


        let percentage =
            (score / 6) * 100;


        bar.style.width =
            percentage + "%";


        text.className =
            "strength-text";


        if (score <= 2) {

            text.textContent =
                "Weak password";

            text.classList.add(
                "weak"
            );

        } else if (score <= 4) {

            text.textContent =
                "Medium password";

            text.classList.add(
                "medium"
            );

        } else {

            text.textContent =
                "Strong password";

            text.classList.add(
                "strong"
            );

        }

    }


    /* =========================================================
       PASSWORD REQUIREMENTS
    ========================================================== */

    function initPasswordRequirements() {

        const password =
            document.getElementById(
                "newPassword"
            );


        if (password) {

            password.addEventListener(
                "input",
                function () {

                    updateRequirement(
                        "reqLength",
                        this.value.length >= 8
                    );


                    updateRequirement(
                        "reqUppercase",
                        /[A-Z]/.test(
                            this.value
                        )
                    );


                    updateRequirement(
                        "reqNumber",
                        /[0-9]/.test(
                            this.value
                        )
                    );

                }
            );

        }

    }


    function updateRequirement(
        elementId,
        valid
    ) {

        const element =
            document.getElementById(
                elementId
            );


        if (!element) {

            return;

        }


        const icon =
            element.querySelector("i");


        if (valid) {

            element.classList.add(
                "valid"
            );


            if (icon) {

                icon.classList.remove(
                    "fa-circle"
                );

                icon.classList.add(
                    "fa-circle-check"
                );

            }

        } else {

            element.classList.remove(
                "valid"
            );


            if (icon) {

                icon.classList.remove(
                    "fa-circle-check"
                );

                icon.classList.add(
                    "fa-circle"
                );

            }

        }

    }


    /* =========================================================
       FORM LOADING STATE
    ========================================================== */

    function setFormLoading(
        form,
        loading
    ) {

        if (!form) {

            return;

        }


        const button =
            form.querySelector(
                "button[type='submit']"
            );


        if (!button) {

            return;

        }


        const text =
            button.querySelector(
                ".btn-text"
            );


        const loader =
            button.querySelector(
                ".btn-loader"
            );


        const arrow =
            button.querySelector(
                ".btn-arrow"
            );


        if (loading) {

            button.disabled = true;

            button.classList.add(
                "loading"
            );


            if (text) {

                text.setAttribute(
                    "hidden",
                    ""
                );

            }


            if (loader) {

                loader.removeAttribute(
                    "hidden"
                );

            }


            if (arrow) {

                arrow.setAttribute(
                    "hidden",
                    ""
                );}

        } else {

            button.disabled = false;

            button.classList.remove(
                "loading"
            );


            if (text) {

                text.removeAttribute(
                    "hidden"
                );

            }


            if (loader) {

                loader.setAttribute(
                    "hidden",
                    ""
                );

            }


            if (arrow) {

                arrow.removeAttribute(
                    "hidden"
                );

            }

        }

    }


    /* =========================================================
       ALERT HELPERS
    ========================================================== */

    function showAlert(
        alertId,
        message
    ) {

        const alert =
            document.getElementById(
                alertId
            );


        if (!alert) {

            return;

        }


        const messageElement =
            alert.querySelector(
                "span"
            );


        if (messageElement) {

            messageElement.textContent =
                message;

        }


        alert.removeAttribute(
            "hidden"
        );

        alert.classList.add(
            "visible"
        );

    }


    function hideAlert(alertId) {

        const alert =
            document.getElementById(
                alertId
            );


        if (!alert) {

            return;

        }


        alert.setAttribute(
            "hidden",
            ""
        );

        alert.classList.remove(
            "visible"
        );

    }


    /* =========================================================
       FORM VALIDATION
    ========================================================== */

    function initModalForms() {

        const loginForm =
            document.getElementById(
                "loginForm"
            );


        const registerForm =
            document.getElementById(
                "registerForm"
            );


        const mfaForm =
            document.getElementById(
                "mfaForm"
            );


        const resetForm =
            document.getElementById(
                "resetPasswordForm"
            );


        if (loginForm) {

            loginForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        !validateLoginForm(
                            loginForm
                        )
                    ) {

                        event.preventDefault();

                        return;

                    }


                    setFormLoading(
                        loginForm,
                        true
                    );

                }
            );

        }


        if (registerForm) {

            registerForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        !validateRegisterForm(
                            registerForm
                        )
                    ) {

                        event.preventDefault();

                        return;

                    }


                    setFormLoading(
                        registerForm,
                        true
                    );

                }
            );

        }


        if (mfaForm) {

            mfaForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        !validateMfaForm(
                            mfaForm
                        )
                    ) {

                        event.preventDefault();

                        return;

                    }


                    setFormLoading(
                        mfaForm,
                        true
                    );

                }
            );

        }


        if (resetForm) {

            resetForm.addEventListener(
                "submit",
                function (event) {if (
                        !validateResetForm(
                            resetForm
                        )
                    ) {

                        event.preventDefault();

                        return;

                    }


                    setFormLoading(
                        resetForm,
                        true
                    );

                }
            );

        }

    }


    /* =========================================================
       LOGIN VALIDATION
    ========================================================== */

    function validateLoginForm(form) {

        clearFieldErrors(form);


        const email =
            document.getElementById(
                "loginEmail"
            );


        const password =
            document.getElementById(
                "loginPassword"
            );


        let valid = true;


        if (
            !email ||
            !email.value.trim()
        ) {

            showFieldError(
                "loginEmailError",
                "Please enter your email."
            );

            valid = false;

        } else if (
            !isValidEmail(
                email.value.trim()
            )
        ) {

            showFieldError(
                "loginEmailError",
                "Please enter a valid email."
            );

            valid = false;

        }


        if (
            !password ||
            !password.value
        ) {

            showFieldError(
                "loginPasswordError",
                "Please enter your password."
            );

            valid = false;

        }


        return valid;

    }


    /* =========================================================
       REGISTER VALIDATION
    ========================================================== */

    function validateRegisterForm(form) {

        clearFieldErrors(form);


        const name =
            document.getElementById(
                "registerName"
            );


        const email =
            document.getElementById(
                "registerEmail"
            );


        const phone =
            document.getElementById(
                "registerPhone"
            );


        const password =
            document.getElementById(
                "registerPassword"
            );


        const confirmPassword =
            document.getElementById(
                "registerConfirmPassword"
            );


        const terms =
            document.getElementById(
                "registerTerms"
            );


        let valid = true;


        if (
            !name ||
            name.value.trim().length < 2
        ) {

            showFieldError(
                "registerNameError",
                "Please enter your full name."
            );

            valid = false;

        }


        if (
            !email ||
            !isValidEmail(
                email.value.trim()
            )
        ) {

            showFieldError(
                "registerEmailError",
                "Please enter a valid email."
            );

            valid = false;

        }


        if (
            phone &&
            phone.value.trim()
        ) {

            const cleanPhone =
                phone.value.replace(
                    /[\s-]/g,
                    ""
                );


            if (
                !/^01[0-9]{8,10}$/.test(
                    cleanPhone
                )
            ) {

                showFieldError(
                    "registerPhoneError",
                    "Please enter a valid Malaysian phone number."
                );

                valid = false;

            }

        }


        if (
            !password ||
            password.value.length < 8
        ) {

            showFieldError(
                "registerPasswordError",
                "Password must be at least 8 characters."
            );

            valid = false;

        }


        if (
            password &&
            confirmPassword &&password.value !==
            confirmPassword.value
        ) {

            showFieldError(
                "registerConfirmPasswordError",
                "Passwords do not match."
            );

            valid = false;

        }


        if (
            !terms ||
            !terms.checked
        ) {

            showFieldError(
                "registerTermsError",
                "Please accept the terms and privacy policy."
            );

            valid = false;

        }


        return valid;

    }


    /* =========================================================
       MFA VALIDATION
    ========================================================== */

    function validateMfaForm(form) {

        const inputs =
            form.querySelectorAll(
                ".otp-input"
            );


        let code = "";


        inputs.forEach(
            function (input) {

                code +=
                    input.value.trim();

            }
        );


        if (
            code.length !== 6
        ) {

            showAlert(
                "mfaAlert",
                "Please enter the complete 6-digit verification code."
            );

            return false;

        }


        hideAlert(
            "mfaAlert"
        );


        return true;

    }


    /* =========================================================
       RESET PASSWORD VALIDATION
    ========================================================== */

    function validateResetForm(form) {

        clearFieldErrors(form);


        const password =
            document.getElementById(
                "newPassword"
            );


        const confirmPassword =
            document.getElementById(
                "confirmPassword"
            );


        let valid = true;


        if (
            !password ||
            password.value.length < 8
        ) {

            showFieldError(
                "newPasswordError",
                "Password must be at least 8 characters."
            );

            valid = false;

        }


        if (
            password &&
            confirmPassword &&
            password.value !==
            confirmPassword.value
        ) {

            showFieldError(
                "confirmPasswordError",
                "Passwords do not match."
            );

            valid = false;

        }


        return valid;

    }


    /* =========================================================
       FIELD ERROR
    ========================================================== */

    function showFieldError(
        elementId,
        message
    ) {

        const element =
            document.getElementById(
                elementId
            );


        if (!element) {

            return;

        }


        element.textContent =
            message;

        element.classList.add(
            "visible"
        );


        const group =
            element.closest(
                ".form-group"
            );


        if (group) {

            group.classList.add(
                "has-error"
            );

        }

    }


    function clearFieldErrors(form) {

        if (!form) {

            return;

        }


        form
            .querySelectorAll(
                ".field-error"
            )
            .forEach(
                function (element) {

                    element.textContent =
                        "";

                    element.classList.remove(
                        "visible"
                    );

                }
            );


        form
            .querySelectorAll(
                ".has-error"
            )
            .forEach(
                function (element) {

                    element.classList.remove(
                        "has-error"
                    );

                }
            );

    }


    /* =========================================================
       EMAIL VALIDATION
    ========================================================== */

    function isValidEmail(email) {return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            .test(email);

    }


    /* =========================================================
       FORGOT PASSWORD
    ========================================================== */

    function initForgotPasswordFlow() {

        const form =
            document.getElementById(
                "forgotPasswordForm"
            );


        if (!form) {

            return;

        }


        form.addEventListener(
            "submit",
            function (event) {

                const identifier =
                    document.getElementById(
                        "forgotIdentifier"
                    );


                if (
                    !identifier ||
                    !identifier.value.trim()
                ) {

                    event.preventDefault();


                    showFieldError(
                        "forgotIdentifierError",
                        "Please enter your email or phone number."
                    );


                    return;

                }


                clearFieldErrors(form);

                setFormLoading(
                    form,
                    true
                );

            }
        );

    }


    /* =========================================================
       RESEND OTP
    ========================================================== */

    function initResendOtp() {

        const button =
            document.getElementById(
                "resendOtpBtn"
            );


        if (!button) {

            return;

        }


        button.addEventListener(
            "click",
            function () {

                if (
                    button.disabled
                ) {

                    return;

                }


                button.disabled =
                    true;


                button.classList.add(
                    "loading"
                );


                /*
                | Actual resend request will be
                | connected to send_otp.php later.
                */

                setTimeout(
                    function () {

                        button.disabled =
                            false;

                        button.classList.remove(
                            "loading"
                        );


                        showToast(
                            "Verification code sent.",
                            "Check your email or phone for the new code.",
                            "success"
                        );

                    },
                    1000
                );

            }
        );

    }


    /* =========================================================
       TOAST
    ========================================================== */

    function showToast(
        title,
        message,
        type
    ) {

        const toast =
            document.getElementById(
                "hochipoToast"
            );


        if (!toast) {

            return;

        }


        const titleElement =
            document.getElementById(
                "toastTitle"
            );


        const messageElement =
            document.getElementById(
                "toastMessage"
            );


        const icon =
            document.getElementById(
                "toastIcon"
            );


        if (titleElement) {

            titleElement.textContent =
                title;

        }


        if (messageElement) {

            messageElement.textContent =
                message;

        }


        toast.classList.remove(
            "success",
            "error",
            "warning",
            "info"
        );


        toast.classList.add(
            type || "success"
        );


        if (icon) {

            icon.className =
                "fa-solid";


            if (
                type === "error"
            ) {

                icon.classList.add(
                    "fa-circle-xmark"
                );

            } else if (type === "warning"
            ) {

                icon.classList.add(
                    "fa-triangle-exclamation"
                );

            } else {

                icon.classList.add(
                    "fa-circle-check"
                );

            }

        }


        toast.classList.add(
            "show"
        );


        setTimeout(
            function () {

                toast.classList.remove(
                    "show"
                );

            },
            4000
        );

    }


    /* =========================================================
       TOAST CLOSE
    ========================================================== */

    const toastClose =
        document.getElementById(
            "toastClose"
        );


    if (toastClose) {

        toastClose.addEventListener(
            "click",
            function () {

                const toast =
                    document.getElementById(
                        "hochipoToast"
                    );


                if (toast) {

                    toast.classList.remove(
                        "show"
                    );

                }

            }
        );

    }


    /* =========================================================
       BACK TO TOP
    ========================================================== */

    function initBackToTop() {

        const button =
            document.getElementById(
                "backToTop"
            );


        if (!button) {

            return;

        }


        window.addEventListener(
            "scroll",
            function () {

                if (
                    window.scrollY > 400
                ) {

                    button.classList.add(
                        "visible"
                    );

                } else {

                    button.classList.remove(
                        "visible"
                    );

                }

            }
        );


        button.addEventListener(
            "click",
            function () {

                window.scrollTo({

                    top: 0,

                    behavior: "smooth"

                });

            }
        );

    }


    /* =========================================================
       GLOBAL FUNCTIONS
    ========================================================== */

    window.openHochipoModal =
        openModal;


    window.closeHochipoModal =
        closeModal;


    window.showHochipoToast =
        showToast;


    window.setRegisterRole =
        setRegisterRole;


    /* =========================================================
       INITIALISE RESEND OTP
    ========================================================== */

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            initResendOtp();

        }
    );


})();