/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - MODAL JAVASCRIPT
|--------------------------------------------------------------------------
|
| Handles:
| - Login modal
| - Register modal
| - Modal switching
| - Close button
| - Click outside
| - ESC
| - Password visibility
| - Register password validation
| - URL modal opening
|
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
    | OPEN MODAL
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-modal-open]").forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const modalId =
                button.getAttribute("data-modal-open");

            openModal(modalId);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-modal-close]").forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const modalId =
                button.getAttribute("data-modal-close");

            closeModal(modalId);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | SWITCH LOGIN <-> REGISTER
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-modal-switch]").forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

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
    | CLICK OUTSIDE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".modal-overlay").forEach(function (modal) {

        modal.addEventListener("click", function (event) {

            if (event.target === modal) {

                closeModal(modal.id);

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            closeAllModals();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | PASSWORD TOGGLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-password-target]").forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const targetId =
                button.getAttribute("data-password-target");

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


    /*
    |--------------------------------------------------------------------------
    | REGISTER PASSWORD MATCH
    |--------------------------------------------------------------------------
    */

    const registerForm =
        document.getElementById("registerForm");

    if (registerForm) {

        registerForm.addEventListener("submit", function (event) {

            const password =
                document.getElementById("registerPassword");

            const confirmPassword =
                document.getElementById(
                    "registerConfirmPassword"
                );

            if (
                password &&
                confirmPassword &&
                password.value !== confirmPassword.value
            ) {

                event.preventDefault();

                alert("Passwords do not match.");

                confirmPassword.focus();

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK URL
    |--------------------------------------------------------------------------
    */

    checkModalFromURL();

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
            "HochipoHub modal not found:",
            modalId
        );

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE OTHER MODALS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".modal-overlay").forEach(function (item) {

        item.classList.remove("show");

        item.setAttribute(
            "aria-hidden",
            "true"
        );

    });


    /*
    |--------------------------------------------------------------------------
    | OPEN TARGET
    |--------------------------------------------------------------------------
    */

    modal.classList.add("show");

    modal.setAttribute(
        "aria-hidden",
        "false"
    );


    /*
    |--------------------------------------------------------------------------
    | PREVENT SCROLL
    |--------------------------------------------------------------------------
    */

    document.body.classList.add("modal-open");

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

    }, 200);

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/

function closeModal(modalId) {

    const modal =
        typeof modalId === "string"
            ? document.getElementById(modalId)
            : modalId;

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
    | CHECK OTHER MODALS
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

    document.querySelectorAll(".modal-overlay").forEach(function (modal) {

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
| LOGIN
|--------------------------------------------------------------------------
*/

function openLoginModal() {

    openModal("loginModal");

}


/*
|--------------------------------------------------------------------------
| REGISTER
|--------------------------------------------------------------------------
*/

function openRegisterModal() {

    openModal("registerModal");

}


/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
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
| CHECK URL
|--------------------------------------------------------------------------
|
| Supports:
|
| ?login=1
| ?login=required
| ?register=1
| ?register=required
| ?modal=login
| ?modal=register
|
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

        openLoginModal();

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

        openRegisterModal();

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

        openLoginModal();

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

        openRegisterModal();

        cleanModalURL();

        return;

    }

}


/*
|--------------------------------------------------------------------------
| CLEAN MODAL URL
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