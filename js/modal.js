document.addEventListener(
"DOMContentLoaded",
function () {

    console.log(
        "HochipoHub modal.js loaded"
    );
    /*
    |--------------------------------------------------------------------------
    | OPEN BUTTONS
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
                    event.stopPropagation();
                    const modalId =
                        button.getAttribute(
                            "data-modal-open"
                        );
                    console.log(
                        "Opening modal:",
                        modalId
                    );
                    openModal(modalId);
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
                    closeModal(modalId);
                }
            );
        }
    );
    /*
    |--------------------------------------------------------------------------
    | SWITCH MODALS
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
                    const currentModal =
                        button.getAttribute(
                            "data-modal-switch"
                        );
                    const targetModal =
                        button.getAttribute(
                            "data-modal-target"
                        );
                    closeModal(
                        currentModal
                    );
                    setTimeout(
                        function () {
                            openModal(
                                targetModal
                            );
                        },
                        150
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
    document
        .querySelectorAll(
            ".modal-overlay"
        )
        .forEach(
            function (modal) {
                modal.addEventListener(
                    "click",
                    function (event) {
                        if (
                            event.target ===
                            modal
                        ) {
                            closeModal(
                                modal.id
                            );
                        }
                    }
                );
            }
        );
    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */
    document.addEventListener(
        "keydown",
        function (event) {
            if (
                event.key ===
                "Escape"
            ) {
                closeAllModals();
            }
        }
    );
    /*
    |--------------------------------------------------------------------------
    | PASSWORD TOGGLE
    |--------------------------------------------------------------------------
    */
    document
        .querySelectorAll(
            "[data-password-target]"
        )
        .forEach(
            function (button) {
                button.addEventListener(
                    "click",
                    function (event) {
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
                        if (
                            input.type ===
                            "password"
                        ) {
                            input.type =
                                "text";
                            button.textContent =
                                "🙈";
                        } else {
                            input.type =
                                "password";
                            button.textContent =
                                "👁";
                        }
                    }
                );
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
}

);

/*

OPEN MODAL

–––––––––––––––––––––––––––––––––––––

*/

function openModal(modalId) {

const modal =
    document.getElementById(
        modalId
    );
if (!modal) {
    console.error(
        "AUTH MODAL NOT FOUND:",
        modalId
    );
    return;
}
document
    .querySelectorAll(
        ".modal-overlay"
    )
    .forEach(
        function (item) {
            item.classList.remove(
                "show"
            );
            item.setAttribute(
                "aria-hidden",
                "true"
            );
        }
    );
modal.classList.add(
    "show"
);
modal.setAttribute(
    "aria-hidden",
    "false"
);
document.body.classList.add(
    "modal-open"
);
document.body.style.overflow =
    "hidden";
setTimeout(
    function () {
        const firstInput =
            modal.querySelector(
                "input:not([type='hidden'])"
            );
        if (firstInput) {
            firstInput.focus();
        }
    },
    100
);

}

/*

CLOSE MODAL

–––––––––––––––––––––––––––––––––––––

*/

function closeModal(modalId) {

const modal =
    typeof modalId === "string"
        ? document.getElementById(
            modalId
        )
        : modalId;
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
if (
    !document.querySelector(
        ".modal-overlay.show"
    )
) {
    document.body.classList.remove(
        "modal-open"
    );
    document.body.style.overflow =
        "";
}

}

/*

CLOSE ALL

–––––––––––––––––––––––––––––––––––––

*/

function closeAllModals() {

document
    .querySelectorAll(
        ".modal-overlay"
    )
    .forEach(
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

GLOBAL

–––––––––––––––––––––––––––––––––––––

*/

window.openModal =
openModal;

window.closeModal =
closeModal;

window.closeAllModals =
closeAllModals;