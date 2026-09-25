/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VALIDATION.JS
|--------------------------------------------------------------------------
| Handles:
| - Required fields
| - Email validation
| - Malaysian phone validation
| - Password validation
| - Password confirmation
| - Register form
| - Login form
| - Checkout form
| - Product form
|--------------------------------------------------------------------------
*/


document.addEventListener("DOMContentLoaded", function () {


    /*
    |--------------------------------------------------------------------------
    | FORM VALIDATION
    |--------------------------------------------------------------------------
    */

    const forms =
        document.querySelectorAll("form");


    forms.forEach(function (form) {


        form.addEventListener("submit", function (event) {


            /*
            |--------------------------------------------------------------------------
            | SKIP FORMS THAT DISABLE VALIDATION
            |--------------------------------------------------------------------------
            */

            if (
                form.dataset.validate === "false" ||
                form.classList.contains("no-validation")
            ) {

                return;
            }


            let valid = true;


            clearFormErrors(form);


            /*
            |--------------------------------------------------------------------------
            | REQUIRED INPUTS
            |--------------------------------------------------------------------------
            */

            const requiredFields =
                form.querySelectorAll(
                    "[required]"
                );


            requiredFields.forEach(function (field) {


                if (
                    field.disabled ||
                    field.type === "hidden"
                ) {

                    return;
                }


                const value =
                    getFieldValue(field);


                if (value === "") {


                    showFieldError(
                        field,
                        "This field is required."
                    );


                    valid = false;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | EMAIL VALIDATION
            |--------------------------------------------------------------------------
            */

            const emailFields =
                form.querySelectorAll(
                    'input[type="email"]'
                );


            emailFields.forEach(function (field) {


                if (
                    field.value.trim() === "" ||
                    field.disabled
                ) {

                    return;
                }


                if (!isValidEmail(field.value)) {


                    showFieldError(
                        field,
                        "Please enter a valid email address."
                    );


                    valid = false;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | PHONE VALIDATION
            |--------------------------------------------------------------------------
            |
            | Register phone examples:
            |
            | 0123456789
            | 012-3456789
            | 01112345678
            | 011-12345678
            |
            |--------------------------------------------------------------------------
            */

            const phoneFields =
                form.querySelectorAll(
                    'input[type="tel"], input[name="phone"]'
                );


            phoneFields.forEach(function (field) {


                if (
                    field.value.trim() === "" ||
                    field.disabled
                ) {

                    return;
                }


                if (!isValidPhone(field.value)) {


                    showFieldError(
                        field,
                        "Please enter a valid Malaysian phone number."
                    );


                    valid = false;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | PASSWORD VALIDATION
            |--------------------------------------------------------------------------
            |
            | Problem 6 will add the special-character requirement.
            | For now this keeps the existing generic validation behaviour.
            |
            |--------------------------------------------------------------------------
            */

            const passwordFields =
                form.querySelectorAll(
                    'input[type="password"][data-password]'
                );


            passwordFields.forEach(function (field) {


                if (
                    field.value === "" ||
                    field.disabled
                ) {

                    return;
                }


                if (!isValidPassword(field.value)) {


                    showFieldError(
                        field,
                        "Password must contain at least 8 characters."
                    );


                    valid = false;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | CONFIRM PASSWORD
            |--------------------------------------------------------------------------
            */

            const confirmPassword =
                form.querySelector(
                    'input[name="confirm_password"], ' +
                    'input[name="password_confirmation"], ' +
                    'input[id="confirmPassword"]'
                );


            const password =
                form.querySelector(
                    'input[name="password"]'
                );


            if (
                confirmPassword &&
                password &&
                confirmPassword.value !== ""
            ) {


                if (
                    confirmPassword.value !==
                    password.value
                ) {


                    showFieldError(
                        confirmPassword,
                        "Passwords do not match."
                    );


                    valid = false;

                }

            }


            /*
            |--------------------------------------------------------------------------
            | REQUIRED CHECKBOXES
            |--------------------------------------------------------------------------
            */

            const requiredCheckboxes =
                form.querySelectorAll(
                    'input[type="checkbox"][required]'
                );


            requiredCheckboxes.forEach(function (checkbox) {


                if (
                    checkbox.disabled
                ) {

                    return;
                }


                if (!checkbox.checked) {


                    showFieldError(
                        checkbox,
                        "Please accept this option."
                    );


                    valid = false;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | NUMBER MIN / MAX
            |--------------------------------------------------------------------------
            */

            const numberFields =
                form.querySelectorAll(
                    'input[type="number"]'
                );


            numberFields.forEach(function (field) {


                if (
                    field.value === "" ||
                    field.disabled
                ) {

                    return;
                }


                const value =
                    parseFloat(field.value);


                const min =
                    field.getAttribute("min");


                const max =
                    field.getAttribute("max");


                if (
                    min !== null &&
                    value < parseFloat(min)
                ) {


                    showFieldError(
                        field,
                        "Value cannot be lower than " +
                        min +
                        "."
                    );


                    valid = false;

                }


                if (
                    max !== null &&
                    value > parseFloat(max)
                ) {


                    showFieldError(
                        field,
                        "Value cannot be higher than " +
                        max +
                        "."
                    );


                    valid = false;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | PREVENT SUBMIT IF INVALID
            |--------------------------------------------------------------------------
            */

            if (!valid) {


                event.preventDefault();


                const firstError =
                    form.querySelector(
                        ".validation-error"
                    );


                if (firstError) {


                    const target =
                        firstError.closest(
                            ".form-group, .input-group, .field"
                        ) ||
                        firstError;


                    target.scrollIntoView({

                        behavior: "smooth",
                        block: "center"

                    });

                }

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | LIVE VALIDATION
    |--------------------------------------------------------------------------
    |
    | Remove an error when the user starts correcting the field.
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "input",
        function (event) {


            const field =
                event.target;


            if (
                !field.matches(
                    "input, textarea, select"
                )
            ) {

                return;
            }


            removeFieldError(field);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | PASSWORD STRENGTH
    |--------------------------------------------------------------------------
    */

    const passwordInputs =
        document.querySelectorAll(
            'input[type="password"]'
        );


    passwordInputs.forEach(function (input) {


        input.addEventListener(
            "input",
            function () {


                updatePasswordStrength(
                    this
                );

            }
        );

    });

});


/*
|--------------------------------------------------------------------------
| GET FIELD VALUE
|--------------------------------------------------------------------------
*/

function getFieldValue(field) {


    if (field.type === "checkbox") {


        return field.checked
            ? "checked"
            : "";

    }


    return field.value.trim();

}


/*
|--------------------------------------------------------------------------
| EMAIL VALIDATION
|--------------------------------------------------------------------------
*/

function isValidEmail(email) {


    const pattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    return pattern.test(
        String(email).trim()
    );

}


/*
|--------------------------------------------------------------------------
| MALAYSIAN PHONE VALIDATION
|--------------------------------------------------------------------------
|
| Accepted input:
|
| 0123456789
| 012-3456789
| 01112345678
| 011-12345678
| 012 3456789
|
| Spaces, dashes and brackets are removed before validation.
|
| After cleaning:
|
| - Must begin with 01
| - Must contain only numbers
| - Must contain 10 or 11 digits in total
|
|--------------------------------------------------------------------------
*/

function isValidPhone(phone) {


    const cleaned =
        String(phone)
            .trim()
            .replace(
                /[\s\-()]/g,
                ""
            );


    return /^01[0-9]{8,9}$/.test(
        cleaned
    );

}


/*
|--------------------------------------------------------------------------
| PASSWORD VALIDATION
|--------------------------------------------------------------------------
|
| Generic password validation.
|
| Problem 6 will make the REGISTER password require a special character.
|--------------------------------------------------------------------------
*/

function isValidPassword(password) {


    return password.length >= 8;

}


/*
|--------------------------------------------------------------------------
| SHOW FIELD ERROR
|--------------------------------------------------------------------------
*/

function showFieldError(field, message) {


    removeFieldError(field);


    field.classList.add(
        "validation-invalid"
    );


    field.setAttribute(
        "aria-invalid",
        "true"
    );


    const wrapper =
        field.closest(
            ".form-group, .input-group, .field, .form-field"
        ) ||
        field.parentElement;


    if (!wrapper) {

        return;
    }


    const error =
        document.createElement(
            "small"
        );


    error.className =
        "validation-error";


    error.textContent =
        message;


    wrapper.appendChild(
        error
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE FIELD ERROR
|--------------------------------------------------------------------------
*/

function removeFieldError(field) {


    field.classList.remove(
        "validation-invalid"
    );


    field.removeAttribute(
        "aria-invalid"
    );


    const wrapper =
        field.closest(
            ".form-group, .input-group, .field, .form-field"
        ) ||
        field.parentElement;


    if (!wrapper) {

        return;
    }


    const error =
        wrapper.querySelector(
            ".validation-error"
        );


    if (error) {

        error.remove();
    }

}


/*
|--------------------------------------------------------------------------
| CLEAR FORM ERRORS
|--------------------------------------------------------------------------
*/

function clearFormErrors(form) {


    form.querySelectorAll(
        ".validation-error"
    ).forEach(function (error) {


        error.remove();

    });


    form.querySelectorAll(
        ".validation-invalid"
    ).forEach(function (field) {


        field.classList.remove(
            "validation-invalid"
        );


        field.removeAttribute(
            "aria-invalid"
        );

    });

}


/*
|--------------------------------------------------------------------------
| PASSWORD STRENGTH
|--------------------------------------------------------------------------
*/

function updatePasswordStrength(input) {


    const password =
        input.value;


    const wrapper =
        input.closest(
            ".form-group, .input-group, .field, .form-field"
        ) ||
        input.parentElement;


    if (!wrapper) {

        return;
    }


    let strength =
        wrapper.querySelector(
            ".password-strength"
        );


    if (!strength) {


        strength =
            document.createElement(
                "div"
            );


        strength.className =
            "password-strength";


        input.insertAdjacentElement(
            "afterend",
            strength
        );

    }


    if (password === "") {


        strength.innerHTML = "";


        strength.className =
            "password-strength";


        return;

    }


    let score = 0;


    if (password.length >= 8) {

        score++;
    }


    if (/[A-Z]/.test(password)) {

        score++;
    }


    if (/[a-z]/.test(password)) {

        score++;
    }


    if (/[0-9]/.test(password)) {

        score++;
    }


    if (/[^A-Za-z0-9]/.test(password)) {

        score++;
    }


    let text = "";
    let level = "";


    if (score <= 2) {


        text = "Weak";
        level = "weak";


    } else if (score <= 4) {


        text = "Medium";
        level = "medium";


    } else {


        text = "Strong";
        level = "strong";

    }


    strength.className =
        "password-strength " +
        level;


    strength.textContent =
        "Password strength: " +
        text;

}


/*
|--------------------------------------------------------------------------
| PREVENT MULTIPLE SUBMISSIONS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "submit",
    function (event) {


        const form =
            event.target;


        if (
            !(form instanceof HTMLFormElement)
        ) {

            return;
        }


        if (
            form.dataset.preventDoubleSubmit ===
            "false"
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | ONLY DISABLE AFTER VALIDATION HAS PASSED
        |--------------------------------------------------------------------------
        |
        | setTimeout allows the main validation handler to call
        | preventDefault() first if the form contains an error.
        |
        |--------------------------------------------------------------------------
        */

        setTimeout(function () {


            if (event.defaultPrevented) {

                return;
            }


            const submitButtons =
                form.querySelectorAll(
                    'button[type="submit"], input[type="submit"]'
                );


            submitButtons.forEach(function (button) {


                button.disabled = true;


                if (
                    button.tagName === "BUTTON"
                ) {


                    if (
                        !button.dataset.originalHtml
                    ) {

                        button.dataset.originalHtml =
                            button.innerHTML;
                    }


                    button.textContent =
                        "Processing...";


                } else {


                    if (
                        !button.dataset.originalValue
                    ) {

                        button.dataset.originalValue =
                            button.value;
                    }


                    button.value =
                        "Processing...";

                }

            });

        }, 50);

    }
);