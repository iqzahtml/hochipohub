/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - MODAL JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/modal.js
|
| Handles:
| - Login modal
| - Register modal
| - Modal switching
| - Close button
| - Click outside
| - ESC
| - Body scroll
| - Password visibility
| - Register password confirmation
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        initModals();
        initPasswordToggles();
        initRegisterValidation();
        checkLoginRequired();

    }
);


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
    | Supports:
    |
    | data-modal-open="loginModal"
    | data-modal-open="registerModal"
    |
    |--------------------------------------------------------------------------
    */

    const openButtons =
        document.querySelectorAll(
            "[data-modal-open]"
        );


    openButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    const modalId =
                        button.getAttribute(
                            "data-modal-open"
                        );

                    openModalByName(
                        modalId
                    );

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTONS
    |--------------------------------------------------------------------------
    */

    const closeButtons =
        document.querySelectorAll(
            "[data-modal-close]"
        );


    closeButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    const modalId =
                        button.getAttribute(
                            "data-modal-close"
                        );

                    closeModalByName(
                        modalId
                    );

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SWITCH LOGIN <-> REGISTER
    |--------------------------------------------------------------------------
    */

    const switchButtons =
        document.querySelectorAll(
            "[data-modal-switch]"
        );


    switchButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    const targetModal =
                        button.getAttribute(
                            "data-modal-target"
                        );

                    if (!targetModal) {
                        return;
                    }

                    openModalByName(
                        targetModal
                    );

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE
    |--------------------------------------------------------------------------
    */

    const overlays =
        document.querySelectorAll(
            ".modal-overlay"
        );


    overlays.forEach(
        function (overlay) {

            overlay.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === overlay
                    ) {

                        closeModal(
                            overlay
                        );

                    }

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESC KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" ||
                event.key === "Esc"
            ) {

                closeAllModals();

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/

function openModal(modal) {

    if (!modal) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE OTHER MODALS FIRST
    |--------------------------------------------------------------------------
    */

    closeAllModals();


    /*
    |--------------------------------------------------------------------------
    | SHOW MODAL
    |--------------------------------------------------------------------------
    */

    modal.classList.add(
        "show"
    );

    modal.setAttribute(
        "aria-hidden",
        "false"
    );


    /*
    |--------------------------------------------------------------------------
    | PREVENT BACKGROUND SCROLL
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

    setTimeout(
        function () {

            const firstInput =
                modal.querySelector(
                    "input:not([type='hidden']), textarea, select"
                );

            if (firstInput) {

                firstInput.focus();

            }

        },
        200
    );

}


/*
|--------------------------------------------------------------------------
| OPEN MODAL BY ID
|--------------------------------------------------------------------------
*/

function openModalByName(name) {

    if (!name) {
        return;
    }


    const modal =
        document.getElementById(
            name
        );


    if (!modal) {

        console.warn(
            "HochipoHub modal not found:",
            name
        );

        return;

    }


    openModal(
        modal
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/

function closeModal(modal) {

    if (!modal) {
        return;
    }


    modal.classList.remove(
        "show"
    );


    modal.setAttribute(
        "aria-hidden",
        "true"
    );


    updateBodyModalState();

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL BY NAME
|--------------------------------------------------------------------------
*/

function closeModalByName(name) {

    if (!name) {
        return;
    }


    const modal =
        document.getElementById(
            name
        );


    if (!modal) {
        return;
    }


    closeModal(
        modal
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE ALL MODALS
|--------------------------------------------------------------------------
*/

function closeAllModals() {

    const modals =
        document.querySelectorAll(
            ".modal-overlay"
        );


    modals.forEach(
        function (modal) {

            modal.classList.remove(
                "show"
            );

            modal.setAttribute(
                "aria-hidden",
                "true"
            );

        }
    );


    document.body.classList.remove(
        "modal-open"
    );


    document.body.style.overflow =
        "";

}


/*
|--------------------------------------------------------------------------
| UPDATE BODY MODAL STATE
|--------------------------------------------------------------------------
*/

function updateBodyModalState() {

    const activeModal =
        document.querySelector(
            ".modal-overlay.show"
        );


    if (activeModal) {

        document.body.classList.add(
            "modal-open"
        );

        document.body.style.overflow =
            "hidden";

    } else {

        document.body.classList.remove(
            "modal-open"
        );

        document.body.style.overflow =
            "";

    }

}


/*
|--------------------------------------------------------------------------
| LOGIN MODAL
|--------------------------------------------------------------------------
*/

function openLoginModal() {

    openModalByName(
        "loginModal"
    );

}


/*
|--------------------------------------------------------------------------
| REGISTER MODAL
|--------------------------------------------------------------------------
*/

function openRegisterModal() {

    openModalByName(
        "registerModal"
    );

}


/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

function openForgotPasswordModal() {

    openModalByName(
        "forgotPasswordModal"
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE LOGIN
|--------------------------------------------------------------------------
*/

function closeLoginModal() {

    closeModalByName(
        "loginModal"
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE REGISTER
|--------------------------------------------------------------------------
*/

function closeRegisterModal() {

    closeModalByName(
        "registerModal"
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

function closeForgotPasswordModal() {

    closeModalByName(
        "forgotPasswordModal"
    );

}


/*
|--------------------------------------------------------------------------
| PASSWORD TOGGLE
|--------------------------------------------------------------------------
*/

function initPasswordToggles() {

    const toggleButtons =
        document.querySelectorAll(
            "[data-password-target]"
        );


    toggleButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

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


                    if (
                        input.type ===
                        "password"
                    ) {

                        input.type =
                            "text";

                        button.textContent =
                            "🙈";

                        button.setAttribute(
                            "aria-label",
                            "Hide password"
                        );

                    } else {

                        input.type =
                            "password";

                        button.textContent =
                            "👁";

                        button.setAttribute(
                            "aria-label",
                            "Show password"
                        );

                    }

                }
            );

        }
    );

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

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| CHECK LOGIN REQUIRED
|--------------------------------------------------------------------------
|
| Example:
|
| index.php?login=required
|
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

        openLoginModal();

        cleanModalUrl();

    }


    if (
        params.get("register") ===
        "required"
    ) {

        openRegisterModal();

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


    url.searchParams.delete(
        "login"
    );

    url.searchParams.delete(
        "register"
    );

    url.searchParams.delete(
        "modal"
    );


    window.history.replaceState(
        {},
        document.title,
        url.pathname +
        url.search
    );

}


/*
|--------------------------------------------------------------------------
| GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
*/

window.openModal =
    openModal;


window.closeModal =
    closeModal;


window.openModalByName =
    openModalByName;


window.closeModalByName =
    closeModalByName;


window.closeAllModals =
    closeAllModals;


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