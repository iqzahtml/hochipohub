/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - MAIN JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/script.js
|
| Main functions:
| - Navbar mobile menu
| - Search interaction
| - Dropdown menus
| - Flash messages
| - Scroll behaviour
| - Password visibility
| - Quantity controls
| - Back to top
| - General UI interactions
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    initMobileMenu();
    initDropdowns();
    initSearch();
    initFlashMessages();
    initPasswordToggle();
    initQuantityControls();
    initBackToTop();
    initSmoothScroll();
    initNavbarScroll();
    initGeneralButtons();

});


/*
|--------------------------------------------------------------------------
| MOBILE MENU
|--------------------------------------------------------------------------
*/

function initMobileMenu() {

    const menuButton =
        document.querySelector(
            ".mobile-menu-toggle, .menu-toggle, #mobileMenuToggle"
        );


    const mobileMenu =
        document.querySelector(
            ".mobile-menu, .navbar-menu, #mobileMenu"
        );


    if (
        !menuButton ||
        !mobileMenu
    ) {
        return;
    }


    menuButton.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            mobileMenu.classList.toggle(
                "active"
            );

            menuButton.classList.toggle(
                "active"
            );

            document.body.classList.toggle(
                "menu-open"
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Close when clicking outside
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            if (
                !mobileMenu.contains(
                    event.target
                ) &&
                !menuButton.contains(
                    event.target
                )
            ) {

                mobileMenu.classList.remove(
                    "active"
                );

                menuButton.classList.remove(
                    "active"
                );

                document.body.classList.remove(
                    "menu-open"
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Close after clicking link
    |--------------------------------------------------------------------------
    */

    const links =
        mobileMenu.querySelectorAll(
            "a"
        );


    links.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {

                mobileMenu.classList.remove(
                    "active"
                );

                menuButton.classList.remove(
                    "active"
                );

                document.body.classList.remove(
                    "menu-open"
                );

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| DROPDOWNS
|--------------------------------------------------------------------------
*/

function initDropdowns() {

    const dropdowns =
        document.querySelectorAll(
            ".dropdown"
        );


    dropdowns.forEach(function (dropdown) {

        const toggle =
            dropdown.querySelector(
                ".dropdown-toggle"
            );


        if (!toggle) {
            return;
        }


        toggle.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                /*
                |--------------------------------------------------------------------------
                | Close other dropdowns
                |--------------------------------------------------------------------------
                */

                dropdowns.forEach(
                    function (other) {

                        if (
                            other !==
                            dropdown
                        ) {

                            other.classList.remove(
                                "active"
                            );

                        }

                    }
                );


                dropdown.classList.toggle(
                    "active"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Click outside
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function () {

            dropdowns.forEach(
                function (dropdown) {

                    dropdown.classList.remove(
                        "active"
                    );

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

function initSearch() {

    const searchForms =
        document.querySelectorAll(
            ".search-form, form[action*='search']"
        );


    searchForms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const input =
                    form.querySelector(
                        "input[name='q'], input[name='search'], input[type='search']"
                    );


                if (!input) {
                    return;
                }


                const keyword =
                    input.value.trim();


                if (
                    keyword.length === 0
                ) {

                    event.preventDefault();

                    input.focus();

                    return;

                }

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Search clear button
    |--------------------------------------------------------------------------
    */

    const clearButtons =
        document.querySelectorAll(
            ".search-clear, [data-search-clear]"
        );


    clearButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const form =
                    button.closest(
                        "form"
                    );


                if (!form) {
                    return;
                }


                const input =
                    form.querySelector(
                        "input[type='search'], input[name='q'], input[name='search']"
                    );


                if (input) {

                    input.value = "";

                    input.focus();

                }

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGES
|--------------------------------------------------------------------------
*/

function initFlashMessages() {

    const messages =
        document.querySelectorAll(
            ".alert, .flash-message, .success-message, .error-message"
        );


    messages.forEach(function (message) {

        const closeButton =
            message.querySelector(
                ".alert-close, .close-alert"
            );


        if (closeButton) {

            closeButton.addEventListener(
                "click",
                function () {

                    removeFlashMessage(
                        message
                    );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Auto hide
        |--------------------------------------------------------------------------
        */

        if (
            message.dataset.autohide !==
            "false"
        ) {

            setTimeout(
                function () {

                    removeFlashMessage(
                        message
                    );

                },
                5000
            );

        }

    });

}


/*
|--------------------------------------------------------------------------
| REMOVE FLASH MESSAGE
|--------------------------------------------------------------------------
*/

function removeFlashMessage(
    element
) {

    if (!element) {
        return;
    }


    element.classList.add(
        "message-hide"
    );


    setTimeout(
        function () {

            if (
                element.parentNode
            ) {

                element.parentNode.removeChild(
                    element
                );

            }

        },
        300
    );

}


/*
|--------------------------------------------------------------------------
| PASSWORD TOGGLE
|--------------------------------------------------------------------------
*/

function initPasswordToggle() {

    const buttons =
        document.querySelectorAll(
            ".password-toggle, [data-password-toggle]"
        );


    buttons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                const targetId =
                    button.dataset.target ||
                    button.getAttribute(
                        "data-password-toggle"
                    );


                let input = null;


                if (targetId) {

                    input =
                        document.getElementById(
                            targetId
                        );

                }


                if (!input) {

                    input =
                        button.parentElement
                            ?.querySelector(
                                "input[type='password'], input[type='text']"
                            );

                }


                if (!input) {
                    return;
                }


                if (
                    input.type ===
                    "password"
                ) {

                    input.type =
                        "text";

                    button.classList.add(
                        "active"
                    );

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    input.type =
                        "password";

                    button.classList.remove(
                        "active"
                    );

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| QUANTITY CONTROLS
|--------------------------------------------------------------------------
*/

function initQuantityControls() {

    const quantityGroups =
        document.querySelectorAll(
            ".quantity-control, .quantity-wrapper"
        );


    quantityGroups.forEach(function (group) {

        const input =
            group.querySelector(
                "input[type='number']"
            );


        if (!input) {
            return;
        }


        const decrease =
            group.querySelector(
                ".quantity-minus, [data-quantity-minus]"
            );


        const increase =
            group.querySelector(
                ".quantity-plus, [data-quantity-plus]"
            );


        if (decrease) {

            decrease.addEventListener(
                "click",
                function () {

                    changeQuantity(
                        input,
                        -1
                    );

                }
            );

        }


        if (increase) {

            increase.addEventListener(
                "click",
                function () {

                    changeQuantity(
                        input,
                        1
                    );

                }
            );

        }

    });

}


/*
|--------------------------------------------------------------------------
| CHANGE QUANTITY
|--------------------------------------------------------------------------
*/

function changeQuantity(
    input,
    amount
) {

    if (!input) {
        return;
    }


    let value =
        parseInt(
            input.value,
            10
        );


    if (
        Number.isNaN(value)
    ) {

        value = 1;

    }


    const min =
        input.min !== ""
            ? parseInt(
                input.min,
                10
            )
            : 1;


    const max =
        input.max !== ""
            ? parseInt(
                input.max,
                10
            )
            : 999;


    value += amount;


    if (value < min) {
        value = min;
    }


    if (value > max) {
        value = max;
    }


    input.value =
        value;


    input.dispatchEvent(
        new Event(
            "change",
            {
                bubbles: true
            }
        )
    );


    input.dispatchEvent(
        new Event(
            "input",
            {
                bubbles: true
            }
        )
    );

}


/*
|--------------------------------------------------------------------------
| BACK TO TOP
|--------------------------------------------------------------------------
*/

function initBackToTop() {

    const button =
        document.querySelector(
            "#backToTop, .back-to-top"
        );


    if (!button) {
        return;
    }


    window.addEventListener(
        "scroll",
        function () {

            if (
                window.scrollY >
                400
            ) {

                button.classList.add(
                    "show"
                );

            } else {

                button.classList.remove(
                    "show"
                );

            }

        }
    );


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });

        }
    );

}


/*
|--------------------------------------------------------------------------
| SMOOTH SCROLL
|--------------------------------------------------------------------------
*/

function initSmoothScroll() {

    const links =
        document.querySelectorAll(
            "a[href^='#']"
        );


    links.forEach(function (link) {

        link.addEventListener(
            "click",
            function (event) {

                const href =
                    link.getAttribute(
                        "href"
                    );


                if (
                    !href ||
                    href === "#"
                ) {
                    return;
                }


                const target =
                    document.querySelector(
                        href
                    );


                if (!target) {
                    return;
                }


                event.preventDefault();


                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| NAVBAR SCROLL
|--------------------------------------------------------------------------
*/

function initNavbarScroll() {

    const navbar =
        document.querySelector(
            ".navbar, header, .site-header"
        );


    if (!navbar) {
        return;
    }


    let lastScroll =
        window.scrollY;


    window.addEventListener(
        "scroll",
        function () {

            const currentScroll =
                window.scrollY;


            if (
                currentScroll >
                50
            ) {

                navbar.classList.add(
                    "navbar-scrolled"
                );

            } else {

                navbar.classList.remove(
                    "navbar-scrolled"
                );

            }


            lastScroll =
                currentScroll;

        }
    );

}


/*
|--------------------------------------------------------------------------
| GENERAL BUTTONS
|--------------------------------------------------------------------------
*/

function initGeneralButtons() {

    const buttons =
        document.querySelectorAll(
            "[data-loading]"
        );


    buttons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                if (
                    button.dataset.loadingActive ===
                    "true"
                ) {
                    return;
                }


                button.dataset.loadingActive =
                    "true";


                const original =
                    button.innerHTML;


                button.dataset.originalContent =
                    original;


                button.innerHTML =
                    "PLEASE WAIT...";


                button.classList.add(
                    "button-loading"
                );


                /*
                |--------------------------------------------------------------------------
                | Do not disable links
                |--------------------------------------------------------------------------
                */

                if (
                    button.tagName !==
                    "A"
                ) {

                    button.disabled =
                        true;

                }

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| WINDOW RESIZE
|--------------------------------------------------------------------------
*/

window.addEventListener(
    "resize",
    function () {

        /*
        | Close mobile menu on desktop
        */

        if (
            window.innerWidth >
            900
        ) {

            const mobileMenu =
                document.querySelector(
                    ".mobile-menu, .navbar-menu, #mobileMenu"
                );


            const menuButton =
                document.querySelector(
                    ".mobile-menu-toggle, .menu-toggle, #mobileMenuToggle"
                );


            if (mobileMenu) {

                mobileMenu.classList.remove(
                    "active"
                );

            }


            if (menuButton) {

                menuButton.classList.remove(
                    "active"
                );

            }


            document.body.classList.remove(
                "menu-open"
            );

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

        if (
            event.key ===
            "Escape"
        ) {

            /*
            | Close dropdowns
            */

            document
                .querySelectorAll(
                    ".dropdown.active"
                )
                .forEach(
                    function (dropdown) {

                        dropdown.classList.remove(
                            "active"
                        );

                    }
                );


            /*
            | Close mobile menu
            */

            const menu =
                document.querySelector(
                    ".mobile-menu.active, .navbar-menu.active"
                );


            if (menu) {

                menu.classList.remove(
                    "active"
                );

                document.body.classList.remove(
                    "menu-open"
                );

            }

        }

    }
);


/*
|--------------------------------------------------------------------------
| EXPOSE GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
*/

window.changeQuantity =
    changeQuantity;

window.removeFlashMessage =
    removeFlashMessage;