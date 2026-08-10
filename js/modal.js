/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - MODAL JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/modal.js
|
| Handles:
| - Login modal
| - Register modal
| - Open / close modal
| - Switch Login <-> Register
| - Click outside
| - ESC key
| - Body scroll lock
| - Password visibility
| - Register password validation
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    initModals();
    initPasswordToggle();
    initRegisterValidation();

});


/*
|--------------------------------------------------------------------------
| INITIALIZE MODALS
|--------------------------------------------------------------------------
*/

function initModals() {

    /*
    |--------------------------------------------------------------------------
    | OPEN BUTTONS
    |--------------------------------------------------------------------------
    |
    | Navbar uses:
    |
    | data-modal-open="loginModal"
    | data-modal-open="registerModal"
    |
    */

    document
        .querySelectorAll("[data-modal-open]")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                const modalId =
                    button.getAttribute("data-modal-open");

                openModal(modalId);

            });

        });


    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTONS
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll("[data-modal-close]")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                const modalId =
                    button.getAttribute("data-modal-close");

                closeModal(modalId);

            });

        });


    /*
    |--------------------------------------------------------------------------
    | LOGIN <-> REGISTER SWITCH
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll("[data-modal-switch]")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                const currentModal =
                    button.getAttribute("data-modal-switch");

                const targetModal =
                    button.getAttribute("data-modal-target");

                closeModal(currentModal);

                setTimeout(function () {

                    openModal(targetModal);

                }, 150);

            });

        });


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE MODAL
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(".modal-overlay")
        .forEach(function (overlay) {

            overlay.addEventListener("click", function (event) {

                if (event.target === overlay) {

                    closeModal(overlay.id);

                }

            });

        });


    /*
    |--------------------------------------------------------------------------
    | ESC KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            closeAllModals();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | OLD STYLE TRIGGERS
    |--------------------------------------------------------------------------
    | Keep these for compatibility with other pages/buttons.
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            "[data-modal='login'], .open-login-modal, #openLoginModal"
        )
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();

                openModal("loginModal");

            });

        });


    document
        .querySelectorAll(
            "[data-modal='register'], .open-register-modal, #openRegisterModal"
        )
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();

                openModal("registerModal");

            });

        });


    /*
    |--------------------------------------------------------------------------
    | URL LOGIN REQUIRED
    |--------------------------------------------------------------------------
    */

    checkLoginRequired();

}


/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/

function openModal(modalId) {

    const modal =
        document.getElementById(modalId);

    if (!modal) {

        console.warn(
            "Modal not found:",
            modalId
        );

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE OTHER MODALS FIRST
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(".modal-overlay")
        .forEach(function (otherModal) {

            if (otherModal !== modal) {

                otherModal.classList.remove("show");
                otherModal.classList.remove("active");

                otherModal.setAttribute(
                    "aria-hidden",
                    "true"
                );

            }

        });


    /*
    |--------------------------------------------------------------------------
    | SHOW MODAL
    |--------------------------------------------------------------------------
    */

    modal.classList.add("show");
    modal.classList.add("active");

    modal.setAttribute(
        "aria-hidden",
        "false"
    );


    /*
    |--------------------------------------------------------------------------
    | LOCK BODY SCROLL
    |--------------------------------------------------------------------------
    */

    document.body.classList.add(
        "modal-open"
    );


    document.body.style.overflow =
        "hidden";


    /*
    |--------------------------------------------------------------------------
    | FOCUS FIRST INPUT
    |--------------------------------------------------------------------------
    */

    setTimeout(function () {

        const firstInput =
            modal.querySelector(
                "input:not([type='hidden']), textarea, select"
            );

        if (firstInput) {

            firstInput.focus();

        }

    }, 200);

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/

function closeModal(modalId) {

    const modal =
        document.getElementById(modalId);

    if (!modal) {

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | HIDE MODAL
    |--------------------------------------------------------------------------
    */

    modal.classList.remove("show");
    modal.classList.remove("active");

    modal.setAttribute(
        "aria-hidden",
        "true"
    );


    /*
    |--------------------------------------------------------------------------
    | CHECK OTHER MODALS
    |--------------------------------------------------------------------------
    */

    const anotherModalOpen =
        document.querySelector(
            ".modal-overlay.show"
        );


    if (!anotherModalOpen) {

        document.body.classList.remove(
            "modal-open"
        );

        document.body.style.overflow = "";

    }

}


/*
|--------------------------------------------------------------------------
| CLOSE ALL MODALS
|--------------------------------------------------------------------------
*/

function closeAllModals() {

    document
        .querySelectorAll(".modal-overlay")
        .forEach(function (modal) {

            modal.classList.remove("show");
            modal.classList.remove("active");

            modal.setAttribute(
                "aria-hidden",
                "true"
            );

        });


    document.body.classList.remove(
        "modal-open"
    );

    document.body.style.overflow = "";

}


/*
|--------------------------------------------------------------------------
| LOGIN MODAL
|--------------------------------------------------------------------------
*/

function openLoginModal() {

    openModal("loginModal");

}


/*
|--------------------------------------------------------------------------
| REGISTER MODAL
|--------------------------------------------------------------------------
*/

function openRegisterModal() {

    openModal("registerModal");

}


/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD MODAL
|--------------------------------------------------------------------------
*/

function openForgotPasswordModal() {

    openModal("forgotPasswordModal");

}


/*
|--------------------------------------------------------------------------
| CLOSE LOGIN
|--------------------------------------------------------------------------
*/

function closeLoginModal() {

    closeModal("loginModal");

}


/*
|--------------------------------------------------------------------------
| CLOSE REGISTER
|--------------------------------------------------------------------------
*/

function closeRegisterModal() {

    closeModal("registerModal");

}


/*
|--------------------------------------------------------------------------
| CLOSE FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

function closeForgotPasswordModal() {

    closeModal("forgotPasswordModal");

}


/*
|--------------------------------------------------------------------------
| PASSWORD TOGGLE
|--------------------------------------------------------------------------
*/

function initPasswordToggle() {

    document
        .querySelectorAll("[data-password-target]")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();

                const targetId =
                    button.getAttribute(
                        "data-password-target"
                    );

                const input =
                    document.getElementById(targetId);

                if (!input) {

                    return;

                }


                if (input.type === "password") {

                    input.type = "text";

                    button.textContent = "🙈";

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    input.type = "password";

                    button.textContent = "👁";

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            });

        });

}


/*
|--------------------------------------------------------------------------
| REGISTER PASSWORD VALIDATION
|--------------------------------------------------------------------------
*/

function initRegisterValidation() {

    const registerForm =
        document.getElementById(
            "registerForm"
        );

    if (!registerForm) {

        return;

    }


    registerForm.addEventListener(
        "submit",
        function (event) {

            const password =
                document.getElementById(
                    "registerPassword"
                );

            const confirmPassword =
                document.getElementById(
                    "registerConfirmPassword"
                );


            if (
                password &&
                confirmPassword &&
                password.value !==
                confirmPassword.value
            ) {

                event.preventDefault();

                alert(
                    "Passwords do not match."
                );

                confirmPassword.focus();

                return;

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| CHECK LOGIN REQUIRED
|--------------------------------------------------------------------------
*/

function checkLoginRequired() {

    const params =
        new URLSearchParams(
            window.location.search
        );


    if (
        params.get("login") ===
        "required"
    ) {

        openModal("loginModal");

        cleanModalUrl();

    }


    if (
        params.get("register") ===
        "required"
    ) {

        openModal("registerModal");

        cleanModalUrl();

    }

}


/*
|--------------------------------------------------------------------------
| CLEAN MODAL URL
|--------------------------------------------------------------------------
*/

function cleanModalUrl() {

    const url =
        new URL(
            window.location.href
        );


    url.searchParams.delete("login");
    url.searchParams.delete("register");
    url.searchParams.delete("modal");


    window.history.replaceState(
        {},
        document.title,
        url.pathname +
        url.search
    );

}


/*
|--------------------------------------------------------------------------
| FORM SUBMIT PROTECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "submit",
    function (event) {

        const form =
            event.target;


        if (
            !form.closest(
                ".modal-overlay"
            )
        ) {

            return;

        }


        const button =
            form.querySelector(
                "button[type='submit'], input[type='submit']"
            );


        if (!button) {

            return;

        }


        if (
            button.dataset.submitting ===
            "true"
        ) {

            event.preventDefault();

            return;

        }


        button.dataset.submitting =
            "true";


        if (
            button.tagName ===
            "BUTTON"
        ) {

            button.dataset.originalText =
                button.innerHTML;

            button.innerHTML =
                "PLEASE WAIT...";

        }


        button.disabled = true;

    }
);


/*
|--------------------------------------------------------------------------
| GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
*/

window.openModal =
    openModal;

window.closeModal =
    closeModal;

window.closeAllModals =
    closeAllModals;

window.openModalByName =
    openModal;

window.openLoginModal =
    openLoginModal;

window.openRegisterModal =
    openRegisterModal;

window.openForgotPasswordModal =
    openForgotPasswordModal;

window.closeLoginModal =
    closeLoginModal;

window.closeRegisterModal =
    closeRegisterModal;

window.closeForgotPasswordModal =
    closeForgotPasswordModal;