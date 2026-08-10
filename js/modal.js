/*

HOCHIPOHUB - MODAL JAVASCRIPT

–––––––––––––––––––––––––––––––––––––

Handles:

- Login modal

- Register modal

- Forgot password modal

- Modal switching

- Close button

- Click outside

- ESC

- Password visibility

- Register password validation

- URL modal opening

–––––––––––––––––––––––––––––––––––––

*/

(function () {

"use strict";
/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/
function openModal(modalId) {
    if (!modalId) {
        return;
    }
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error(
            "HochipoHub: Modal tidak dijumpai:",
            modalId
        );
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | CLOSE OTHER MODALS
    |--------------------------------------------------------------------------
    */
    document
        .querySelectorAll(".modal-overlay")
        .forEach(function (item) {
            item.classList.remove("show");
            item.setAttribute(
                "aria-hidden",
                "true"
            );
        });
    /*
    |--------------------------------------------------------------------------
    | OPEN TARGET MODAL
    |--------------------------------------------------------------------------
    */
    modal.classList.add("show");
    modal.setAttribute(
        "aria-hidden",
        "false"
    );
    /*
    |--------------------------------------------------------------------------
    | PREVENT BODY SCROLL
    |--------------------------------------------------------------------------
    */
    document.body.classList.add(
        "modal-open"
    );
    document.body.style.overflow = "hidden";
    /*
    |--------------------------------------------------------------------------
    | FOCUS FIRST INPUT
    |--------------------------------------------------------------------------
    */
    setTimeout(function () {
        const firstInput =
            modal.querySelector(
                "input:not([type='hidden'])"
            );
        if (firstInput) {
            firstInput.focus();
        }
    }, 100);
}
/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/
function closeModal(modalId) {
    let modal = null;
    if (typeof modalId === "string") {
        modal =
            document.getElementById(modalId);
    } else if (modalId instanceof HTMLElement) {
        modal = modalId;
    }
    if (!modal) {
        return;
    }
    modal.classList.remove("show");
    modal.setAttribute(
        "aria-hidden",
        "true"
    );
    /*
    |--------------------------------------------------------------------------
    | CHECK IF OTHER MODAL IS STILL OPEN
    |--------------------------------------------------------------------------
    */
    const activeModal =
        document.querySelector(
            ".modal-overlay.show"
        );
    if (!activeModal) {
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
| DOM READY
|--------------------------------------------------------------------------
*/
document.addEventListener(
    "DOMContentLoaded",
    function () {
        /*
        |--------------------------------------------------------------------------
        | OPEN MODAL
        |--------------------------------------------------------------------------
        |
        | Uses EVENT DELEGATION.
        | So buttons in navbar/mobile menu will still work.
        |
        |--------------------------------------------------------------------------
        */
        document.addEventListener(
            "click",
            function (event) {
                const openButton =
                    event.target.closest(
                        "[data-modal-open]"
                    );
                if (openButton) {
                    event.preventDefault();
                    const modalId =
                        openButton.getAttribute(
                            "data-modal-open"
                        );
                    openModal(modalId);
                    return;
                }
                /*
                |--------------------------------------------------------------------------
                | CLOSE MODAL
                |--------------------------------------------------------------------------
                */
                const closeButton =
                    event.target.closest(
                        "[data-modal-close]"
                    );
                if (closeButton) {
                    event.preventDefault();
                    const modalId =
                        closeButton.getAttribute(
                            "data-modal-close"
                        );
                    closeModal(modalId);
                    return;
                }
                /*
                |--------------------------------------------------------------------------
                | SWITCH MODAL
                |--------------------------------------------------------------------------
                */
                const switchButton =
                    event.target.closest(
                        "[data-modal-switch]"
                    );
                if (switchButton) {
                    event.preventDefault();
                    const currentModal =
                        switchButton.getAttribute(
                            "data-modal-switch"
                        );
                    const targetModal =
                        switchButton.getAttribute(
                            "data-modal-target"
                        );
                    closeModal(currentModal);
                    setTimeout(function () {
                        openModal(targetModal);
                    }, 100);
                    return;
                }
                /*
                |--------------------------------------------------------------------------
                | PASSWORD TOGGLE
                |--------------------------------------------------------------------------
                */
                const passwordButton =
                    event.target.closest(
                        "[data-password-target]"
                    );
                if (passwordButton) {
                    event.preventDefault();
                    const targetId =
                        passwordButton.getAttribute(
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
                        input.type === "password"
                    ) {
                        input.type = "text";
                        passwordButton.textContent =
                            "🙈";
                        passwordButton.setAttribute(
                            "aria-label",
                            "Hide password"
                        );
                    } else {
                        input.type = "password";
                        passwordButton.textContent =
                            "👁";
                        passwordButton.setAttribute(
                            "aria-label",
                            "Show password"
                        );
                    }
                }
            }
        );
        /*
        |--------------------------------------------------------------------------
        | CLICK OUTSIDE MODAL
        |--------------------------------------------------------------------------
        */
        document.addEventListener(
            "click",
            function (event) {
                if (
                    event.target.classList.contains(
                        "modal-overlay"
                    )
                ) {
                    closeModal(event.target);
                }
            }
        );
        /*
        |--------------------------------------------------------------------------
        | ESCAPE KEY
        |--------------------------------------------------------------------------
        */
        document.addEventListener(
            "keydown",
            function (event) {
                if (event.key === "Escape") {
                    closeAllModals();
                }
            }
        );
        /*
        |--------------------------------------------------------------------------
        | REGISTER PASSWORD VALIDATION
        |--------------------------------------------------------------------------
        */
        const registerForm =
            document.getElementById(
                "registerForm"
            );
        if (registerForm) {
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
        | URL MODAL
        |--------------------------------------------------------------------------
        */
        checkModalFromURL();
    }
);
/*
|--------------------------------------------------------------------------
| CHECK URL
|--------------------------------------------------------------------------
*/
function checkModalFromURL() {
    const params =
        new URLSearchParams(
            window.location.search
        );
    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */
    if (
        params.get("login") === "1" ||
        params.get("login") === "required"
    ) {
        openModal("loginModal");
        cleanModalURL();
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */
    if (
        params.get("register") === "1" ||
        params.get("register") === "required"
    ) {
        openModal("registerModal");
        cleanModalURL();
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | MODAL LOGIN
    |--------------------------------------------------------------------------
    */
    if (
        params.get("modal") === "login"
    ) {
        openModal("loginModal");
        cleanModalURL();
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | MODAL REGISTER
    |--------------------------------------------------------------------------
    */
    if (
        params.get("modal") === "register"
    ) {
        openModal("registerModal");
        cleanModalURL();
        return;
    }
}
/*
|--------------------------------------------------------------------------
| CLEAN URL
|--------------------------------------------------------------------------
*/
function cleanModalURL() {
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
        url.pathname + url.search
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
window.closeAllModals =
    closeAllModals;
window.openLoginModal =
    function () {
        openModal("loginModal");
    };
window.openRegisterModal =
    function () {
        openModal("registerModal");
    };
window.openForgotPasswordModal =
    function () {
        openModal("forgotPasswordModal");
    };
window.closeLoginModal =
    function () {
        closeModal("loginModal");
    };
window.closeRegisterModal =
    function () {
        closeModal("registerModal");
    };
window.closeForgotPasswordModal =
    function () {
        closeModal("forgotPasswordModal");
    };

})();