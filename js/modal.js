/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - MODAL JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/modal.js
|
| Functions:
| - Open modal
| - Close modal
| - Login modal
| - Register modal
| - Close when clicking outside
| - Close using ESC
| - Prevent background scrolling
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    initModals();

});


/*
|--------------------------------------------------------------------------
| INITIALIZE MODALS
|--------------------------------------------------------------------------
*/

function initModals() {

    /*
    |--------------------------------------------------------------------------
    | FIND MODALS
    |--------------------------------------------------------------------------
    */

    const modals =
        document.querySelectorAll(
            ".modal, .auth-modal"
        );


    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTONS
    |--------------------------------------------------------------------------
    */

    const closeButtons =
        document.querySelectorAll(
            ".modal-close, .close-modal, [data-modal-close]"
        );


    closeButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const modal =
                    button.closest(
                        ".modal, .auth-modal"
                    );

                if (modal) {
                    closeModal(modal);
                }

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE MODAL
    |--------------------------------------------------------------------------
    */

    modals.forEach(function (modal) {

        modal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === modal
                ) {
                    closeModal(modal);
                }

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | LOGIN TRIGGERS
    |--------------------------------------------------------------------------
    */

    const loginTriggers =
        document.querySelectorAll(
            "[data-modal='login'], .open-login-modal, #openLoginModal"
        );


    loginTriggers.forEach(function (trigger) {

        trigger.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                openModalByName(
                    "loginModal"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | REGISTER TRIGGERS
    |--------------------------------------------------------------------------
    */

    const registerTriggers =
        document.querySelectorAll(
            "[data-modal='register'], .open-register-modal, #openRegisterModal"
        );


    registerTriggers.forEach(function (trigger) {

        trigger.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                openModalByName(
                    "registerModal"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */

    const forgotTriggers =
        document.querySelectorAll(
            "[data-modal='forgot'], .open-forgot-modal, #openForgotModal"
        );


    forgotTriggers.forEach(function (trigger) {

        trigger.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                openModalByName(
                    "forgotPasswordModal"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | SWITCH LOGIN -> REGISTER
    |--------------------------------------------------------------------------
    */

    const registerSwitch =
        document.querySelectorAll(
            ".switch-to-register, [data-switch='register']"
        );


    registerSwitch.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                closeAllModals();

                openModalByName(
                    "registerModal"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | SWITCH REGISTER -> LOGIN
    |--------------------------------------------------------------------------
    */

    const loginSwitch =
        document.querySelectorAll(
            ".switch-to-login, [data-switch='login']"
        );


    loginSwitch.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                closeAllModals();

                openModalByName(
                    "loginModal"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
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
    | REMOVE HIDDEN STATE
    |--------------------------------------------------------------------------
    */

    modal.classList.add("active");

    modal.classList.add("show");

    modal.style.display = "flex";

    /*
    |--------------------------------------------------------------------------
    | PREVENT BODY SCROLL
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

        const input =
            modal.querySelector(
                "input:not([type='hidden']), textarea, select"
            );

        if (input) {

            input.focus();

        }

    }, 150);

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

    modal.classList.remove("active");

    modal.classList.remove("show");

    /*
    |--------------------------------------------------------------------------
    | Allow CSS transition to finish
    |--------------------------------------------------------------------------
    */

    setTimeout(function () {

        if (
            !modal.classList.contains("active") &&
            !modal.classList.contains("show")
        ) {

            modal.style.display = "none";

        }

    }, 200);


    /*
    |--------------------------------------------------------------------------
    | RESTORE BODY SCROLL
    |--------------------------------------------------------------------------
    */

    document.body.classList.remove(
        "modal-open"
    );

    document.body.style.overflow = "";


    /*
    |--------------------------------------------------------------------------
    | RESET FORM
    |--------------------------------------------------------------------------
    */

    const form =
        modal.querySelector("form");

    if (form) {

        /*
        | Only reset if explicitly allowed.
        */

        if (
            form.dataset.resetOnClose ===
            "true"
        ) {

            form.reset();

        }

    }

}


/*
|--------------------------------------------------------------------------
| OPEN MODAL BY ID / NAME
|--------------------------------------------------------------------------
*/

function openModalByName(name) {

    let modal =
        document.getElementById(name);


    /*
    |--------------------------------------------------------------------------
    | Alternative naming
    |--------------------------------------------------------------------------
    */

    if (!modal) {

        modal =
            document.querySelector(
                "." + name
            );

    }


    if (!modal) {

        console.warn(
            "Modal not found:",
            name
        );

        return;

    }


    closeAllModals();

    openModal(modal);

}


/*
|--------------------------------------------------------------------------
| CLOSE ALL MODALS
|--------------------------------------------------------------------------
*/

function closeAllModals() {

    const modals =
        document.querySelectorAll(
            ".modal, .auth-modal"
        );


    modals.forEach(function (modal) {

        modal.classList.remove(
            "active"
        );

        modal.classList.remove(
            "show"
        );

        modal.style.display =
            "none";

    });


    document.body.classList.remove(
        "modal-open"
    );

    document.body.style.overflow =
        "";

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
| FORGOT PASSWORD MODAL
|--------------------------------------------------------------------------
*/

function openForgotPasswordModal() {

    openModalByName(
        "forgotPasswordModal"
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE LOGIN MODAL
|--------------------------------------------------------------------------
*/

function closeLoginModal() {

    const modal =
        document.getElementById(
            "loginModal"
        );

    closeModal(modal);

}


/*
|--------------------------------------------------------------------------
| CLOSE REGISTER MODAL
|--------------------------------------------------------------------------
*/

function closeRegisterModal() {

    const modal =
        document.getElementById(
            "registerModal"
        );

    closeModal(modal);

}


/*
|--------------------------------------------------------------------------
| CLOSE FORGOT PASSWORD MODAL
|--------------------------------------------------------------------------
*/

function closeForgotPasswordModal() {

    const modal =
        document.getElementById(
            "forgotPasswordModal"
        );

    closeModal(modal);

}


/*
|--------------------------------------------------------------------------
| SHOW LOGIN MODAL FROM URL
|--------------------------------------------------------------------------
| Example:
| index.php?login=required
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

    }

}


/*
|--------------------------------------------------------------------------
| CHECK URL AFTER PAGE LOAD
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        checkLoginRequired();

    }
);


/*
|--------------------------------------------------------------------------
| REMOVE MODAL QUERY PARAMETER
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
| FORM SUBMIT LOADING
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "submit",
    function (event) {

        const form =
            event.target;

        if (
            !form.closest(
                ".modal, .auth-modal"
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

        /*
        |--------------------------------------------------------------------------
        | Prevent double submission
        |--------------------------------------------------------------------------
        */

        if (
            button.dataset.submitting ===
            "true"
        ) {

            event.preventDefault();

            return;

        }


        button.dataset.submitting =
            "true";


        /*
        |--------------------------------------------------------------------------
        | Preserve original text
        |--------------------------------------------------------------------------
        */

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
| EXPOSE GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
*/

window.openModal =
    openModal;

window.closeModal =
    closeModal;

window.openModalByName =
    openModalByName;

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