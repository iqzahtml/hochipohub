document.addEventListener("DOMContentLoaded", function () {

    console.log("HochipoHub modal.js loaded");


    /* =========================================================
       OPEN MODAL
    ========================================================= */

    const openButtons = document.querySelectorAll(
        "[data-modal-open]"
    );

    openButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            const modalId = button.getAttribute(
                "data-modal-open"
            );

            openModal(modalId);

        });

    });


    /* =========================================================
       CLOSE MODAL
    ========================================================= */

    const closeButtons = document.querySelectorAll(
        "[data-modal-close]"
    );

    closeButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const modalId = button.getAttribute(
                "data-modal-close"
            );

            closeModal(modalId);

        });

    });


    /* =========================================================
       SWITCH LOGIN / REGISTER
    ========================================================= */

    const switchButtons = document.querySelectorAll(
        "[data-modal-switch]"
    );

    switchButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const currentModal = button.getAttribute(
                "data-modal-switch"
            );

            const targetModal = button.getAttribute(
                "data-modal-target"
            );

            closeModal(currentModal);

            setTimeout(function () {

                openModal(targetModal);

            }, 150);

        });

    });


    /* =========================================================
       CLICK OUTSIDE MODAL
    ========================================================= */

    document.querySelectorAll(
        ".modal-overlay"
    ).forEach(function (modal) {

        modal.addEventListener("click", function (event) {

            if (event.target === modal) {

                closeModal(modal.id);

            }

        });

    });


    /* =========================================================
       ESCAPE KEY
    ========================================================= */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            closeAllModals();

        }

    });


    /* =========================================================
       PASSWORD TOGGLE
    ========================================================= */

    document.querySelectorAll(
        "[data-password-target]"
    ).forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            const targetId = button.getAttribute(
                "data-password-target"
            );

            const input = document.getElementById(
                targetId
            );

            if (!input) {

                console.error(
                    "Password input not found:",
                    targetId
                );

                return;

            }


            /* SHOW PASSWORD */

            if (input.type === "password") {

                input.type = "text";

                button.textContent = "🙈";

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            }

            /* HIDE PASSWORD */

            else {

                input.type = "password";

                button.textContent = "👁";

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        });

    });


    /* =========================================================
       REGISTER PASSWORD VALIDATION
    ========================================================= */

    const registerForm = document.getElementById(
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

});


/* =========================================================
   OPEN MODAL FUNCTION
========================================================= */

function openModal(modalId) {

    const modal = document.getElementById(
        modalId
    );

    if (!modal) {

        console.error(
            "AUTH MODAL NOT FOUND:",
            modalId
        );

        return;

    }


    document.querySelectorAll(
        ".modal-overlay"
    ).forEach(function (item) {

        item.classList.remove("show");

        item.setAttribute(
            "aria-hidden",
            "true"
        );

    });


    modal.classList.add("show");

    modal.setAttribute(
        "aria-hidden",
        "false"
    );


    document.body.classList.add(
        "modal-open"
    );

    document.body.style.overflow = "hidden";


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


/* =========================================================
   CLOSE MODAL FUNCTION
========================================================= */

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


    if (
        !document.querySelector(
            ".modal-overlay.show"
        )
    ) {

        document.body.classList.remove(
            "modal-open"
        );

        document.body.style.overflow = "";

    }

}


/* =========================================================
   CLOSE ALL MODALS
========================================================= */

function closeAllModals() {

    document.querySelectorAll(
        ".modal-overlay"
    ).forEach(function (modal) {

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


/* =========================================================
   GLOBAL FUNCTIONS
========================================================= */

window.openModal = openModal;

window.closeModal = closeModal;

window.closeAllModals = closeAllModals;