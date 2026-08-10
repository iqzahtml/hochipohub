/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL SCRIPT
|--------------------------------------------------------------------------
| Handles:
| - Mobile navigation
| - Scroll effects
| - Back to top
| - Alert dismissal
| - Dropdowns
| - Password visibility
| - Confirm actions
| - Loading states
| - Toast notifications
| - General UI
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {


    /* ===============================================/* ==========================================================================
   HOCHIPOHUB - GLOBAL JAVASCRIPT
   ========================================================================== */

"use strict";


/* ==========================================================================
   DOM READY
   ========================================================================== */

document.addEventListener("DOMContentLoaded", function () {

    initMobileNavbar();
    initDropdowns();
    initAlerts();
    initImageFallback();
    initScrollEffects();
    initQuantityInputs();
    initPasswordToggle();
    initAutoHideMessages();

});


/* ==========================================================================
   MOBILE NAVBAR
   ========================================================================== */

function initMobileNavbar() {

    const toggle =
        document.querySelector(".navbar-toggle");

    const menu =
        document.querySelector(".navbar-menu");

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener("click", function (event) {

        event.stopPropagation();

        menu.classList.toggle("active");

        toggle.classList.toggle("active");

        const expanded =
            menu.classList.contains("active");

        toggle.setAttribute(
            "aria-expanded",
            expanded ? "true" : "false"
        );

    });


    document.addEventListener("click", function (event) {

        if (
            !menu.contains(event.target) &&
            !toggle.contains(event.target)
        ) {

            menu.classList.remove("active");
            toggle.classList.remove("active");

            toggle.setAttribute(
                "aria-expanded",
                "false"
            );
        }

    });

}


/* ==========================================================================
   DROPDOWNS
   ========================================================================== */

function initDropdowns() {

    const dropdowns =
        document.querySelectorAll(
            ".dropdown, .nav-dropdown"
        );

    dropdowns.forEach(function (dropdown) {

        const trigger =
            dropdown.querySelector(
                ".dropdown-toggle, .nav-dropdown-toggle"
            );

        if (!trigger) {
            return;
        }

        trigger.addEventListener("click", function (event) {

            if (window.innerWidth <= 900) {

                event.preventDefault();

                dropdowns.forEach(function (item) {

                    if (item !== dropdown) {
                        item.classList.remove("active");
                    }

                });

                dropdown.classList.toggle("active");
            }

        });

    });

}


/* ==========================================================================
   ALERT CLOSE
   ========================================================================== */

function initAlerts() {

    document.addEventListener(
        "click",
        function (event) {

            const closeButton =
                event.target.closest(
                    ".alert-close, [data-dismiss='alert']"
                );

            if (!closeButton) {
                return;
            }

            const alert =
                closeButton.closest(
                    ".alert, .message, .flash-message"
                );

            if (!alert) {
                return;
            }

            alert.style.opacity = "0";

            alert.style.transform =
                "translateY(-5px)";

            setTimeout(function () {

                alert.remove();

            }, 250);

        }
    );

}


/* ==========================================================================
   AUTO HIDE SUCCESS / ERROR MESSAGES
   ========================================================================== */

function initAutoHideMessages() {

    const messages =
        document.querySelectorAll(
            ".alert-success.auto-hide, " +
            ".success-message.auto-hide"
        );

    messages.forEach(function (message) {

        setTimeout(function () {

            if (!document.body.contains(message)) {
                return;
            }

            message.style.opacity = "0";

            message.style.transform =
                "translateY(-5px)";

            setTimeout(function () {

                message.remove();

            }, 300);

        }, 4000);

    });

}


/* ==========================================================================
   IMAGE FALLBACK
   ========================================================================== */

function initImageFallback() {

    const images =
        document.querySelectorAll("img");

    images.forEach(function (image) {

        image.addEventListener(
            "error",
            function () {

                if (
                    image.dataset.fallbackApplied === "true"
                ) {
                    return;
                }

                image.dataset.fallbackApplied =
                    "true";

                const type =
                    image.dataset.imageType || "product";

                if (type === "vendor") {

                    image.src =
                        getBaseUrl() +
                        "image/vendors/default-vendor.jpg";

                } else {

                    image.src =
                        getBaseUrl() +
                        "image/product/default-product.jpg";

                }

            }
        );

    });

}


/* ==========================================================================
   GET BASE URL
   ========================================================================== */

function getBaseUrl() {

    if (
        typeof window.HOCHIPOHUB_BASE_URL !==
        "undefined"
    ) {

        return window.HOCHIPOHUB_BASE_URL;
    }

    const meta =
        document.querySelector(
            'meta[name="base-url"]'
        );

    if (meta) {
        return meta.getAttribute("content");
    }

    const path =
        window.location.pathname;

    const marker =
        "/hochipohub/";

    const position =
        path.indexOf(marker);

    if (position !== -1) {

        return (
            window.location.origin +
            path.substring(
                0,
                position + marker.length
            )
        );
    }

    return "/";
}


/* ==========================================================================
   SCROLL EFFECTS
   ========================================================================== */

function initScrollEffects() {

    const navbar =
        document.querySelector(".navbar");

    if (!navbar) {
        return;
    }

    function updateNavbar() {

        if (window.scrollY > 20) {

            navbar.classList.add(
                "navbar-scrolled"
            );

        } else {

            navbar.classList.remove(
                "navbar-scrolled"
            );

        }

    }

    updateNavbar();

    window.addEventListener(
        "scroll",
        updateNavbar,
        {
            passive: true
        }
    );

}


/* ==========================================================================
   QUANTITY INPUTS
   ========================================================================== */

function initQuantityInputs() {

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    "[data-quantity-action]"
                );

            if (!button) {
                return;
            }

            const action =
                button.dataset.quantityAction;

            const targetSelector =
                button.dataset.quantityTarget;

            let input = null;

            if (targetSelector) {

                input =
                    document.querySelector(
                        targetSelector
                    );

            } else {

                const wrapper =
                    button.closest(
                        ".quantity-control, " +
                        ".product-quantity, " +
                        ".quantity-selector"
                    );

                if (wrapper) {

                    input =
                        wrapper.querySelector(
                            "input[type='number']"
                        );
                }

            }

            if (!input) {
                return;
            }

            let value =
                parseInt(input.value, 10);

            if (Number.isNaN(value)) {
                value = 1;
            }

            const min =
                parseInt(
                    input.min || "1",
                    10
                );

            const max =
                parseInt(
                    input.max || "999",
                    10
                );

            if (action === "increase") {

                value++;

            } else if (action === "decrease") {

                value--;

            }

            value =
                Math.max(
                    min,
                    Math.min(
                        max,
                        value
                    )
                );

            input.value = value;

            input.dispatchEvent(
                new Event(
                    "change",
                    {
                        bubbles: true
                    }
                )
            );

        }
    );

}


/* ==========================================================================
   PASSWORD TOGGLE
   ========================================================================== */

function initPasswordToggle() {

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    "[data-password-toggle]"
                );

            if (!button) {
                return;
            }

            const selector =
                button.dataset.passwordToggle;

            let input = null;

            if (selector) {

                input =
                    document.querySelector(
                        selector
                    );

            } else {

                input =
                    button
                    .closest(".password-wrapper")
                    ?.querySelector(
                        "input"
                    );

            }

            if (!input) {
                return;
            }

            if (
                input.type === "password"
            ) {

                input.type = "text";

                button.classList.add(
                    "password-visible"
                );

            } else {

                input.type = "password";

                button.classList.remove(
                    "password-visible"
                );

            }

        }
    );

}


/* ==========================================================================
   FORM SUBMIT LOADING
   ========================================================================== */

document.addEventListener(
    "submit",
    function (event) {

        const form =
            event.target;

        if (
            form.dataset.noLoading ===
            "true"
        ) {
            return;
        }

        const submitButton =
            form.querySelector(
                "button[type='submit'], " +
                "input[type='submit']"
            );

        if (!submitButton) {
            return;
        }

        if (
            form.dataset.allowMultipleSubmit ===
            "true"
        ) {
            return;
        }

        setTimeout(function () {

            if (!event.defaultPrevented) {

                submitButton.disabled =
                    true;

                submitButton.dataset.originalText =
                    submitButton.innerHTML;

                if (
                    submitButton.tagName ===
                    "BUTTON"
                ) {

                    submitButton.innerHTML =
                        "Processing...";

                }

            }

        }, 0);

    }
);


/* ==========================================================================
   CONFIRM ACTION
   ========================================================================== */

document.addEventListener(
    "click",
    function (event) {

        const element =
            event.target.closest(
                "[data-confirm]"
            );

        if (!element) {
            return;
        }

        const message =
            element.dataset.confirm ||
            "Are you sure you want to continue?";

        if (!window.confirm(message)) {

            event.preventDefault();

        }

    }
);


/* ==========================================================================
   FORMAT CURRENCY
   ========================================================================== */

function formatCurrency(amount) {

    const number =
        parseFloat(amount);

    if (Number.isNaN(number)) {
        return "RM 0.00";
    }

    return (
        "RM " +
        number.toFixed(2)
    );

}


/* ==========================================================================
   DEBOUNCE
   ========================================================================== */

function debounce(
    callback,
    delay = 300
) {

    let timeout;

    return function () {

        const context = this;
        const args = arguments;

        clearTimeout(timeout);

        timeout =
            setTimeout(
                function () {

                    callback.apply(
                        context,
                        args
                    );

                },
                delay
            );

    };

}


/* ==========================================================================
   SHOW TOAST
   ========================================================================== */

function showToast(
    message,
    type = "success"
) {

    let container =
        document.querySelector(
            ".toast-container"
        );

    if (!container) {

        container =
            document.createElement(
                "div"
            );

        container.className =
            "toast-container";

        container.style.position =
            "fixed";

        container.style.right =
            "20px";

        container.style.bottom =
            "20px";

        container.style.zIndex =
            "10000";

        container.style.display =
            "flex";

        container.style.flexDirection =
            "column";

        container.style.gap =
            "10px";

        document.body.appendChild(
            container
        );

    }


    const toast =
        document.createElement(
            "div"
        );

    toast.className =
        "hochipo-toast " +
        "toast-" +
        type;

    toast.textContent =
        message;

    toast.style.padding =
        "12px 17px";

    toast.style.borderRadius =
        "12px";

    toast.style.background =
        "#0f172a";

    toast.style.color =
        "#ffffff";

    toast.style.fontSize =
        "13px";

    toast.style.fontWeight =
        "700";

    toast.style.boxShadow =
        "0 10px 30px rgba(0,0,0,.2)";

    toast.style.animation =
        "toastIn .25s ease";

    container.appendChild(
        toast
    );


    setTimeout(function () {

        toast.style.opacity =
            "0";

        toast.style.transform =
            "translateY(10px)";

        toast.style.transition =
            "all .25s ease";

        setTimeout(function () {

            toast.remove();

        }, 250);

    }, 3000);

}


/* ==========================================================================
   EXPORT GLOBAL HELPERS
   ========================================================================== */

window.HochipoHub = {

    baseUrl: getBaseUrl,

    formatCurrency:
        formatCurrency,

    debounce:
        debounce,

    showToast:
        showToast

};


/* ==========================================================================
   MOBILE NAVIGATION
   ========================================================================== */

const menuToggle =
    document.querySelector(
        ".menu-toggle, .navbar-toggle, #menu-toggle"
    );

    const mobileMenu =
        document.querySelector(
            ".navbar-menu, .nav-menu, .mobile-menu"
        );

    if (menuToggle && mobileMenu) {

        menuToggle.addEventListener("click", function () {

            mobileMenu.classList.toggle("active");

            menuToggle.classList.toggle("active");

            const expanded =
                menuToggle.classList.contains("active");

            menuToggle.setAttribute(
                "aria-expanded",
                expanded
            );

        });

    }


    /* ==============================================================
       CLOSE MOBILE MENU WHEN CLICK OUTSIDE
    ============================================================== */

    document.addEventListener("click", function (event) {

        if (!menuToggle || !mobileMenu) {
            return;
        }

        if (
            !mobileMenu.contains(event.target) &&
            !menuToggle.contains(event.target)
        ) {

            mobileMenu.classList.remove("active");

            menuToggle.classList.remove("active");

            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );

        }

    });


    /* ==============================================================
       NAVBAR SCROLL EFFECT
    ============================================================== */

    const navbar =
        document.querySelector(
            ".navbar, .main-navbar, header"
        );

    function updateNavbar() {

        if (!navbar) {
            return;
        }

        if (window.scrollY > 30) {

            navbar.classList.add("scrolled");

        } else {

            navbar.classList.remove("scrolled");

        }

    }

    window.addEventListener(
        "scroll",
        updateNavbar,
        { passive: true }
    );

    updateNavbar();


    /* ==============================================================
       BACK TO TOP
    ============================================================== */

    const backToTop =
        document.querySelector(
            "#backToTop, .back-to-top"
        );

    if (backToTop) {

        function toggleBackToTop() {

            if (window.scrollY > 400) {

                backToTop.classList.add("show");

            } else {

                backToTop.classList.remove("show");

            }

        }

        window.addEventListener(
            "scroll",
            toggleBackToTop,
            { passive: true }
        );

        toggleBackToTop();

        backToTop.addEventListener(
            "click",
            function () {

                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });

            }
        );

    }


    /* ==============================================================
       AUTO DISMISS ALERT
    ============================================================== */

    document
        .querySelectorAll(
            ".alert[data-auto-dismiss]"
        )
        .forEach(alert => {

            const duration =
                Number(
                    alert.dataset.autoDismiss
                ) || 5000;

            setTimeout(() => {

                alert.classList.add("alert-hide");

                setTimeout(() => {

                    alert.remove();

                }, 300);

            }, duration);

        });


    /* ==============================================================
       MANUAL ALERT CLOSE
    ============================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const closeButton =
                event.target.closest(
                    ".alert-close, .close-alert"
                );

            if (!closeButton) {
                return;
            }

            const alert =
                closeButton.closest(".alert");

            if (!alert) {
                return;
            }

            alert.classList.add("alert-hide");

            setTimeout(() => {
                alert.remove();
            }, 300);

        }
    );


    /* ==============================================================
       DROPDOWN
    ============================================================== */

    const dropdownTriggers =
        document.querySelectorAll(
            "[data-dropdown-toggle]"
        );

    dropdownTriggers.forEach(trigger => {

        trigger.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const targetId =
                    trigger.dataset.dropdownToggle;

                const dropdown =
                    document.getElementById(
                        targetId
                    );

                if (!dropdown) {
                    return;
                }

                document
                    .querySelectorAll(
                        ".dropdown-menu.active"
                    )
                    .forEach(menu => {

                        if (menu !== dropdown) {
                            menu.classList.remove("active");
                        }

                    });

                dropdown.classList.toggle("active");

            }
        );

    });


    /* ==============================================================
       CLOSE DROPDOWN OUTSIDE
    ============================================================== */

    document.addEventListener(
        "click",
        function (event) {

            if (
                event.target.closest(
                    "[data-dropdown-toggle]"
                ) ||
                event.target.closest(
                    ".dropdown-menu"
                )
            ) {
                return;
            }

            document
                .querySelectorAll(
                    ".dropdown-menu.active"
                )
                .forEach(menu => {

                    menu.classList.remove("active");

                });

        }
    );


    /* ==============================================================
       PASSWORD VISIBILITY
    ============================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    ".password-toggle, [data-password-toggle]"
                );

            if (!button) {
                return;
            }

            const targetSelector =
                button.dataset.passwordToggle;

            let input;

            if (targetSelector) {

                input =
                    document.querySelector(
                        targetSelector
                    );

            } else {

                input =
                    button
                        .closest(".password-wrapper")
                        ?.querySelector(
                            "input"
                        );

            }

            if (!input) {
                return;
            }

            if (input.type === "password") {

                input.type = "text";

                button.innerHTML = `
                    <i class="fa-solid fa-eye-slash"></i>
                `;

            } else {

                input.type = "password";

                button.innerHTML = `
                    <i class="fa-solid fa-eye"></i>
                `;

            }

        }
    );


    /* ==============================================================
       CONFIRM ACTION
    ============================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const target =
                event.target.closest(
                    "[data-confirm]"
                );

            if (!target) {
                return;
            }

            const message =
                target.dataset.confirm ||
                "Are you sure you want to continue?";

            if (!window.confirm(message)) {

                event.preventDefault();

            }

        }
    );


    /* ==============================================================
       FORM SUBMIT LOADING
    ============================================================== */

    document
        .querySelectorAll(
            "form[data-loading]"
        )
        .forEach(form => {

            form.addEventListener(
                "submit",
                function () {

                    const button =
                        form.querySelector(
                            '[type="submit"]'
                        );

                    if (!button) {
                        return;
                    }

                    if (
                        button.dataset.originalText
                    ) {
                        return;
                    }

                    button.dataset.originalText =
                        button.innerHTML;

                    button.disabled = true;

                    button.innerHTML = `
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Please wait...
                    `;

                }
            );

        });


    /* ==============================================================
       IMAGE FALLBACK
    ============================================================== */

    document
        .querySelectorAll("img")
        .forEach(image => {

            image.addEventListener(
                "error",
                function () {

                    if (
                        this.dataset.fallbackApplied
                    ) {
                        return;
                    }

                    this.dataset.fallbackApplied =
                        "true";

                    this.style.objectFit =
                        "contain";

                    this.src =
                        "image/logo.jpg";

                }
            );

        });


    /* ==============================================================
       TOAST SYSTEM
    ============================================================== */

    window.showToast = function (
        message,
        type = "info",
        duration = 3500
    ) {

        let container =
            document.querySelector(
                ".toast-container"
            );

        if (!container) {

            container =
                document.createElement("div");

            container.className =
                "toast-container";

            document.body.appendChild(
                container
            );

        }

        const toast =
            document.createElement("div");

        toast.className =
            `toast toast-${type}`;

        let icon =
            "fa-circle-info";

        if (type === "success") {
            icon = "fa-circle-check";
        }

        if (type === "error") {
            icon = "fa-circle-exclamation";
        }

        if (type === "warning") {
            icon = "fa-triangle-exclamation";
        }

        toast.innerHTML = `
            <i class="fa-solid ${icon}"></i>

            <span>${message}</span>

            <button
                type="button"
                class="toast-close"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {

            toast.classList.add("show");

        });

        const removeToast = () => {

            toast.classList.remove("show");

            setTimeout(() => {

                toast.remove();

            }, 300);

        };

        toast
            .querySelector(".toast-close")
            .addEventListener(
                "click",
                removeToast
            );

        setTimeout(
            removeToast,
            duration
        );

    };


    /* ==============================================================
       GLOBAL ESCAPE KEY
    ============================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key !== "Escape") {
                return;
            }

            document
                .querySelectorAll(
                    ".dropdown-menu.active"
                )
                .forEach(menu => {

                    menu.classList.remove(
                        "active"
                    );

                });

            document
                .querySelectorAll(
                    ".modal.active, .modal.show"
                )
                .forEach(modal => {

                    modal.classList.remove(
                        "active",
                        "show"
                    );

                });

        }
    );


    /* ==============================================================
       SMOOTH INTERNAL LINKS
    ============================================================== */

    document
        .querySelectorAll(
            'a[href^="#"]'
        )
        .forEach(link => {

            link.addEventListener(
                "click",
                function (event) {

                    const id =
                        this.getAttribute("href");

                    if (
                        !id ||
                        id === "#"
                    ) {
                        return;
                    }

                    const target =
                        document.querySelector(id);

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


    /* ==============================================================
       ACTIVE NAVIGATION LINK
    ============================================================== */

    const currentPage =
        window.location.pathname
            .split("/")
            .pop();

    document
        .querySelectorAll(
            ".navbar a[href], .nav-menu a[href]"
        )
        .forEach(link => {

            const href =
                link.getAttribute("href");

            if (!href) {
                return;
            }

            const linkPage =
                href.split("/").pop();

            if (
                linkPage &&
                linkPage === currentPage
            ) {

                link.classList.add("active");

            }

        });


});