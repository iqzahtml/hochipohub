/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL SCRIPT
|--------------------------------------------------------------------------
| Global JavaScript
|
| Handles:
| - Mobile navigation
| - Navbar interactions
| - Dropdown menus
| - Sticky navbar
| - Scroll effects
| - Back to top
| - Alert / notification
| - Loading overlay
| - Image fallback
| - Generic quantity controls
| - Password visibility
| - Copy buttons
| - Confirm actions
| - Auto-hide messages
| - Smooth scrolling
| - Escape key handling
| - Global utility functions
|--------------------------------------------------------------------------
*/

"use strict";


/* ==============================================================
   DOM READY
============================================================== */

document.addEventListener("DOMContentLoaded", function () {

    initMobileMenu();

    initDropdowns();

    initStickyNavbar();

    initScrollEffects();

    initBackToTop();

    initAutoHideMessages();

    initImageFallback();

    initPasswordToggle();

    initQuantityControls();

    initCopyButtons();

    initConfirmActions();

    initSmoothScroll();

    initEscapeHandler();

    initSearchClearButtons();

    initTooltips();

    initExternalLinks();

    initFormLoading();

});


/* ==============================================================
   MOBILE MENU
============================================================== */

function initMobileMenu() {

    const menuButton =
        document.querySelector(
            "#mobileMenuButton, .mobile-menu-button, .menu-toggle"
        );

    const mobileMenu =
        document.querySelector(
            "#mobileMenu, .mobile-menu, .navbar-menu"
        );


    if (!menuButton || !mobileMenu) {
        return;
    }


    menuButton.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            const isOpen =
                mobileMenu.classList.toggle(
                    "active"
                );


            menuButton.classList.toggle(
                "active",
                isOpen
            );


            menuButton.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );


            document.body.classList.toggle(
                "menu-open",
                isOpen
            );

        }
    );


    /* ----------------------------------------------------------
       Close menu when clicking outside
    ---------------------------------------------------------- */

    document.addEventListener(
        "click",
        function (event) {

            if (!mobileMenu.classList.contains("active")) {
                return;
            }


            if (
                !mobileMenu.contains(event.target) &&
                !menuButton.contains(event.target)
            ) {

                closeMobileMenu();

            }

        }
    );


    /* ----------------------------------------------------------
       Close menu after clicking navigation link
    ---------------------------------------------------------- */

    mobileMenu
        .querySelectorAll("a")
        .forEach(function (link) {

            link.addEventListener(
                "click",
                function () {

                    closeMobileMenu();

                }
            );

        });


    function closeMobileMenu() {

        mobileMenu.classList.remove(
            "active"
        );

        menuButton.classList.remove(
            "active"
        );

        menuButton.setAttribute(
            "aria-expanded",
            "false"
        );

        document.body.classList.remove(
            "menu-open"
        );

    }

}


/* ==============================================================
   DROPDOWN MENUS
============================================================== */

function initDropdowns() {

    const dropdowns =
        document.querySelectorAll(
            ".dropdown, .nav-dropdown"
        );


    if (!dropdowns.length) {
        return;
    }


    dropdowns.forEach(function (dropdown) {

        const trigger =
            dropdown.querySelector(
                ".dropdown-toggle, .nav-dropdown-toggle"
            );

        const menu =
            dropdown.querySelector(
                ".dropdown-menu, .nav-dropdown-menu"
            );


        if (!trigger || !menu) {
            return;
        }


        trigger.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                const isOpen =
                    dropdown.classList.toggle(
                        "active"
                    );


                trigger.setAttribute(
                    "aria-expanded",
                    isOpen ? "true" : "false"
                );

            }
        );


        menu.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );

    });


    document.addEventListener(
        "click",
        function () {

            dropdowns.forEach(
                function (dropdown) {

                    dropdown.classList.remove(
                        "active"
                    );


                    const trigger =
                        dropdown.querySelector(
                            ".dropdown-toggle, .nav-dropdown-toggle"
                        );


                    if (trigger) {

                        trigger.setAttribute(
                            "aria-expanded",
                            "false"
                        );

                    }

                }
            );

        }
    );

}


/* ==============================================================
   STICKY NAVBAR
============================================================== */

function initStickyNavbar() {

    const navbar =
        document.querySelector(
            ".navbar, #navbar, header"
        );


    if (!navbar) {
        return;
    }


    const scrollThreshold = 20;


    function updateNavbar() {

        if (
            window.scrollY >
            scrollThreshold
        ) {

            navbar.classList.add(
                "scrolled"
            );

        } else {

            navbar.classList.remove(
                "scrolled"
            );

        }

    }


    window.addEventListener(
        "scroll",
        updateNavbar,
        {
            passive: true
        }
    );


    updateNavbar();

}


/* ==============================================================
   SCROLL EFFECTS
============================================================== */

function initScrollEffects() {

    const elements =
        document.querySelectorAll(
            "[data-scroll-reveal], .scroll-reveal"
        );


    if (!elements.length) {
        return;
    }


    if (
        !("IntersectionObserver" in window)
    ) {

        elements.forEach(
            function (element) {

                element.classList.add(
                    "is-visible"
                );

            }
        );

        return;
    }


    const observer =
        new IntersectionObserver(
            function (entries) {

                entries.forEach(
                    function (entry) {

                        if (
                            entry.isIntersecting
                        ) {

                            entry.target.classList.add(
                                "is-visible"
                            );

                            observer.unobserve(
                                entry.target
                            );

                        }

                    }
                );

            },
            {
                threshold: 0.12
            }
        );


    elements.forEach(
        function (element) {

            observer.observe(
                element
            );

        }
    );

}


/* ==============================================================
   BACK TO TOP
============================================================== */

function initBackToTop() {

    const button =
        document.querySelector(
            "#backToTop, .back-to-top"
        );


    if (!button) {
        return;
    }


    function updateButton() {

        if (
            window.scrollY >
            400
        ) {

            button.classList.add(
                "visible"
            );

        } else {

            button.classList.remove(
                "visible"
            );

        }

    }


    window.addEventListener(
        "scroll",
        updateButton,
        {
            passive: true
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


    updateButton();

}


/* ==============================================================
   AUTO HIDE MESSAGES
============================================================== */

function initAutoHideMessages() {

    const messages =
        document.querySelectorAll(
            ".alert[data-auto-hide], " +
            ".alert.auto-hide, " +
            ".success-message, " +
            ".error-message"
        );


    messages.forEach(
        function (message) {

            const customDuration =
                parseInt(
                    message.dataset.autoHide,
                    10
                );


            const duration =
                Number.isFinite(
                    customDuration
                )
                    ? customDuration
                    : 5000;


            setTimeout(
                function () {

                    message.classList.add(
                        "fade-out"
                    );


                    setTimeout(
                        function () {

                            if (
                                message.parentNode
                            ) {

                                message.remove();

                            }

                        },
                        400
                    );

                },
                duration
            );

        }
    );

}


/* ==============================================================
   IMAGE FALLBACK
============================================================== */

function initImageFallback() {

    const images =
        document.querySelectorAll(
            "img"
        );


    images.forEach(
        function (image) {

            if (
                image.dataset.fallbackBound
            ) {
                return;
            }


            image.dataset.fallbackBound =
                "true";


            image.addEventListener(
                "error",
                function () {

                    if (
                        image.dataset.fallbackApplied
                    ) {
                        return;
                    }


                    image.dataset.fallbackApplied =
                        "true";


                    const fallback =
                        image.dataset.fallback ||
                        "image/product-placeholder.jpg";


                    image.src =
                        fallback;


                    image.classList.add(
                        "image-fallback"
                    );

                }
            );

        }
    );

}


/* ==============================================================
   PASSWORD VISIBILITY
============================================================== */

function initPasswordToggle() {

    const toggles =
        document.querySelectorAll(
            ".password-toggle, " +
            "[data-password-toggle]"
        );


    toggles.forEach(
        function (toggle) {

            toggle.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();


                    let targetId =
                        toggle.dataset.target;


                    if (!targetId) {

                        targetId =
                            toggle.getAttribute(
                                "data-password-toggle"
                            );

                    }


                    let input;


                    if (targetId) {

                        input =
                            document.getElementById(
                                targetId
                            );

                    } else {

                        input =
                            toggle
                                .closest(".password-wrapper, .input-group")
                                ?.querySelector(
                                    "input[type='password'], input[type='text']"
                                );

                    }


                    if (!input) {
                        return;
                    }


                    const isPassword =
                        input.type === "password";


                    input.type =
                        isPassword
                            ? "text"
                            : "password";


                    toggle.classList.toggle(
                        "active",
                        isPassword
                    );


                    toggle.setAttribute(
                        "aria-label",
                        isPassword
                            ? "Hide password"
                            : "Show password"
                    );


                    const icon =
                        toggle.querySelector(
                           ("i")
                        );


                    if (icon) {

                        icon.classList.toggle(
                            "fa-eye",
                            !isPassword
                        );

                        icon.classList.toggle(
                            "fa-eye-slash",
                            isPassword
                        );

                    }

                }
            );

        }
    );

}


/* ==============================================================
   GENERIC QUANTITY CONTROLS
============================================================== */

function initQuantityControls() {

    const controls =
        document.querySelectorAll(
            ".quantity-control"
        );


    controls.forEach(
        function (control) {

            const input =
                control.querySelector(
                    ".quantity-input, input[type='number']"
                );


            const minus =
                control.querySelector(
                    ".quantity-minus, [data-action='decrease']"
                );


            const plus =
                control.querySelector(
                    ".quantity-plus, [data-action='increase']"
                );


            if (!input) {
                return;
            }


            const getMin =
                function () {

                    const value =
                        parseInt(
                            input.min,
                            10
                        );

                    return Number.isFinite(value)
                        ? value
                        : 1;

                };


            const getMax =
                function () {

                    const value =
                        parseInt(
                            input.max,
                            10
                        );

                    return Number.isFinite(value)
                        ? value
                        : 999;

                };


            function updateQuantity(
                amount
            ) {

                let value =
                    parseInt(
                        input.value,
                        10
                    );


                if (
                    !Number.isFinite(value)
                ) {

                    value =
                        getMin();

                }


                value += amount;


                value =
                    Math.max(
                        getMin(),
                        Math.min(
                            getMax(),
                            value
                        )
                    );


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

            }


            if (minus) {

                minus.addEventListener(
                    "click",
                    function (event) {

                        event.preventDefault();

                        updateQuantity(-1);

                    }
                );

            }


            if (plus) {

                plus.addEventListener(
                    "click",
                    function (event) {

                        event.preventDefault();

                        updateQuantity(1);

                    }
                );

            }


            input.addEventListener(
                "input",
                function () {

                    let value =
                        parseInt(
                            input.value,
                            10
                        );


                    if (
                        !Number.isFinite(value)
                    ) {
                        return;
                    }


                    if (
                        value <
                        getMin()
                    ) {

                        input.value =
                            getMin();

                    }


                    if (
                        value >
                        getMax()
                    ) {

                        input.value =
                            getMax();

                    }

                }
            );

        }
    );

}


/* ==============================================================
   COPY TO CLIPBOARD
============================================================== */

function initCopyButtons() {

    const buttons =
        document.querySelectorAll(
            "[data-copy], .copy-button"
        );


    buttons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                async function (event) {

                    event.preventDefault();


                    let text =
                        button.dataset.copy;


                    if (!text) {

                        const targetId =
                            button.dataset.copyTarget;


                        if (targetId) {

                            const target =
                                document.getElementById(
                                    targetId
                                );


                            if (target) {

                                text =
                                    target.value ||
                                    target.textContent;

                            }

                        }

                    }


                    if (!text) {
                        return;
                    }


                    try {

                        await navigator.clipboard.writeText(
                            text.trim()
                        );


                        const original =
                            button.innerHTML;


                        button.innerHTML =
                            '<i class="fa-solid fa-check"></i> Copied';


                        button.classList.add(
                            "copied"
                        );


                        setTimeout(
                            function () {

                                button.innerHTML =
                                    original;

                                button.classList.remove(
                                    "copied"
                                );

                            },
                            1500
                        );


                    } catch (error) {

                        console.error(
                            "Copy failed:",
                            error
                        );

                        showGlobalNotification(
                            "Unable to copy.",
                            "error"
                        );

                    }

                }
            );

        }
    );

}


/* ==============================================================
   CONFIRM ACTIONS
============================================================== */

function initConfirmActions() {

    const elements =
        document.querySelectorAll(
            "[data-confirm]"
        );


    elements.forEach(
        function (element) {

            element.addEventListener(
                "click",
                function (event) {

                    const message =
                        element.dataset.confirm ||
                        "Are you sure you want to continue?";


                    const confirmed =
                        window.confirm(
                            message
                        );


                    if (!confirmed) {

                        event.preventDefault();

                    }

                }
            );

        }
    );

}


/* ==============================================================
   SMOOTH SCROLL
============================================================== */

function initSmoothScroll() {

    const links =
        document.querySelectorAll(
            "a[href^='#']"
        );


    links.forEach(
        function (link) {

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


                    const navbar =
                        document.querySelector(
                            ".navbar, #navbar"
                        );


                    const navbarHeight =
                        navbar
                            ? navbar.offsetHeight
                            : 0;


                    const targetPosition =
                        target.getBoundingClientRect().top +
                        window.pageYOffset -
                        navbarHeight -
                        10;


                    window.scrollTo({
                        top:
                            targetPosition,
                        behavior:
                            "smooth"
                    });

                }
            );

        }
    );

}


/* ==============================================================
   ESCAPE KEY
============================================================== */

function initEscapeHandler() {

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key !== "Escape"
            ) {
                return;
            }


            /* --------------------------------------------------
               Close dropdowns
            -------------------------------------------------- */

            document
                .querySelectorAll(
                    ".dropdown.active, .nav-dropdown.active"
                )
                .forEach(
                    function (dropdown) {

                        dropdown.classList.remove(
                            "active"
                        );

                    }
                );


            /* --------------------------------------------------
               Close mobile menu
            -------------------------------------------------- */

            document
                .querySelectorAll(
                    ".mobile-menu.active, .navbar-menu.active"
                )
                .forEach(
                    function (menu) {

                        menu.classList.remove(
                            "active"
                        );

                    }
                );


            document.body.classList.remove(
                "menu-open"
            );


            /* --------------------------------------------------
               Close generic overlays
            -------------------------------------------------- */

            document
                .querySelectorAll(
                    ".overlay.active, .search-overlay.active"
                )
                .forEach(
                    function (overlay) {

                        overlay.classList.remove(
                            "active"
                        );

                    }
                );

        }
    );

}


/* ==============================================================
   SEARCH CLEAR BUTTON
============================================================== */

function initSearchClearButtons() {

    const buttons =
        document.querySelectorAll(
            ".search-clear, [data-search-clear]"
        );


    buttons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();


                    const wrapper =
                        button.closest(
                            ".search-box, .search-wrapper, .search-container"
                        );


                    const input =
                        wrapper
                            ? wrapper.querySelector(
                                "input[type='search'], input[name='search'], input[name='q']"
                            )
                            : document.querySelector(
                                "input[type='search'], input[name='search'], input[name='q']"
                            );


                    if (!input) {
                        return;
                    }


                    input.value = "";

                    input.focus();


                    input.dispatchEvent(
                        new Event(
                            "input",
                            {
                                bubbles: true
                            }
                        )
                    );

                }
            );

        }
    );

}


/* ==============================================================
   TOOLTIPS
============================================================== */

function initTooltips() {

    const elements =
        document.querySelectorAll(
            "[data-tooltip]"
        );


    elements.forEach(
        function (element) {

            element.setAttribute(
                "title",
                element.dataset.tooltip
            );

        }
    );

}


/* ==============================================================
   EXTERNAL LINKS
============================================================== */

function initExternalLinks() {

    const currentHost =
        window.location.hostname;


    document
        .querySelectorAll(
            "a[href]"
        )
        .forEach(
            function (link) {

                const href =
                    link.getAttribute(
                        "href"
                    );


                if (
                    !href ||
                    href.startsWith("#") ||
                    href.startsWith("/") ||
                    href.startsWith("javascript:")
                ) {
                    return;
                }


                try {

                    const url =
                        new URL(
                            href,
                            window.location.href
                        );


                    if (
                        url.hostname &&
                        url.hostname !== currentHost
                    ) {

                        link.setAttribute(
                            "target",
                            "_blank"
                        );

                        link.setAttribute(
                            "rel",
                            "noopener noreferrer"
                        );

                    }

                } catch (error) {

                    /* Invalid URL - ignore */

                }

            }
        );

}


/* ==============================================================
   FORM LOADING STATE
============================================================== */

function initFormLoading() {

    const forms =
        document.querySelectorAll(
            "form[data-loading]"
        );


    forms.forEach(
        function (form) {

            form.addEventListener(
                "submit",
                function () {

                    const button =
                        form.querySelector(
                            "button[type='submit'], input[type='submit']"
                        );


                    if (!button) {
                        return;
                    }


                    if (
                        button.disabled
                    ) {
                        return;
                    }


                    button.dataset.originalText =
                        button.innerHTML;


                    button.disabled =
                        true;


                    if (
                        button.tagName === "BUTTON"
                    ) {

                        button.innerHTML =
                            '<i class="fa-solid fa-spinner fa-spin"></i> Please wait...';

                    }

                }
            );

        }
    );

}


/* ==============================================================
   GLOBAL NOTIFICATION
============================================================== */

function showGlobalNotification(
    message,
    type = "info",
    duration = 3500
) {

    let container =
        document.querySelector(
            "#globalNotificationContainer"
        );


    if (!container) {

        container =
            document.createElement("div");

        container.id =
            "globalNotificationContainer";

        container.className =
            "global-notification-container";

        document.body.appendChild(
            container
        );

    }


    const notification =
        document.createElement("div");


    notification.className =
        `global-notification ${type}`;


    let icon =
        "fa-circle-info";


    if (type === "success") {

        icon =
            "fa-circle-check";

    } else if (type === "error") {

        icon =
            "fa-circle-exclamation";

    } else if (type === "warning") {

        icon =
            "fa-triangle-exclamation";

    }


    notification.innerHTML = `
        <span class="global-notification-icon">
            <i class="fa-solid ${icon}"></i>
        </span>

        <span class="global-notification-message"></span>

        <button
            type="button"
            class="global-notification-close"
            aria-label="Close notification"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;


    const messageElement =
        notification.querySelector(
            ".global-notification-message"
        );


    messageElement.textContent =
        message;


    const closeButton =
        notification.querySelector(
            ".global-notification-close"
        );


    closeButton.addEventListener(
        "click",
        function () {

            removeNotification(
                notification
            );

        }
    );


    container.appendChild(
        notification
    );


    requestAnimationFrame(
        function () {

            notification.classList.add(
                "show"
            );

        }
    );


    setTimeout(
        function () {

            removeNotification(
                notification
            );

        },
        duration
    );

}


/* ==============================================================
   REMOVE NOTIFICATION
============================================================== */

function removeNotification(
    notification
) {

    if (!notification) {
        return;
    }


    notification.classList.remove(
        "show"
    );


    setTimeout(
        function () {

            if (
                notification.parentNode
            ) {

                notification.remove();

            }

        },
        300
    );

}


/* ==============================================================
   LOADING OVERLAY
============================================================== */

function showGlobalLoading(
    message = "Loading..."
) {

    let overlay =
        document.querySelector(
            "#globalLoadingOverlay"
        );


    if (!overlay) {

        overlay =
            document.createElement("div");

        overlay.id =
            "globalLoadingOverlay";

        overlay.className =
            "global-loading-overlay";


        overlay.innerHTML = `
            <div class="global-loading-box">

                <div class="global-loading-spinner">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </div>

                <p class="global-loading-message"></p>

            </div>
        `;


        document.body.appendChild(
            overlay
        );

    }


    const messageElement =
        overlay.querySelector(
            ".global-loading-message"
        );


    if (messageElement) {

        messageElement.textContent =
            message;

    }


    overlay.classList.add(
        "active"
    );


    document.body.classList.add(
        "loading-active"
    );

}


/* ==============================================================
   HIDE GLOBAL LOADING
============================================================== */

function hideGlobalLoading() {

    const overlay =
        document.querySelector(
            "#globalLoadingOverlay"
        );


    if (!overlay) {
        return;
    }


    overlay.classList.remove(
        "active"
    );


    document.body.classList.remove(
        "loading-active"
    );

}


/* ==============================================================
   DEBOUNCE
============================================================== */

function debounce(
    callback,
    delay = 300
) {

    let timeout;


    return function (...args) {

        clearTimeout(
            timeout
        );


        timeout =
            setTimeout(
                function () {

                    callback.apply(
                        this,
                        args
                    );

                },
                delay
            );

    };

}


/* ==============================================================
   THROTTLE
============================================================== */

function throttle(
    callback,
    delay = 100
) {

    let waiting = false;


    return function (...args) {

        if (waiting) {
            return;
        }


        callback.apply(
            this,
            args
        );


        waiting = true;


        setTimeout(
            function () {

                waiting = false;

            },
            delay
        );

    };

}


/* ==============================================================
   FORMAT PRICE
============================================================== */

function formatPrice(
    amount
) {

    const number =
        Number(amount);


    if (
        !Number.isFinite(number)
    ) {

        return "RM 0.00";

    }


    return new Intl.NumberFormat(
        "en-MY",
        {
            style: "currency",
            currency: "MYR",
            minimumFractionDigits: 2
        }
    ).format(number);

}


/* ==============================================================
   ESCAPE HTML
============================================================== */

function escapeHTML(
    value
) {

    if (
        value === null ||
        value === undefined
    ) {

        return "";

    }


    const div =
        document.createElement("div");


    div.textContent =
        String(value);


    return div.innerHTML;

}


/* ==============================================================
   GET URL PARAMETER
============================================================== */

function getUrlParameter(
    name
) {

    const params =
        new URLSearchParams(
            window.location.search
        );


    return params.get(
        name
    );

}


/* ==============================================================
   GLOBAL EVENT - CART UPDATED
============================================================== */

document.addEventListener(
    "hochipo:cartUpdated",
    function (event) {

        const count =
            event.detail?.count;


        if (
            count === undefined
        ) {
            return;
        }


        document
            .querySelectorAll(
                ".cart-count, [data-cart-count]"
            )
            .forEach(
                function (element) {

                    element.textContent =
                        count;


                    element.classList.toggle(
                        "empty",
                        Number(count) <= 0
                    );

                }
            );

    }
);


/* ==============================================================
   UPDATE CART COUNT
============================================================== */

function updateCartCount(
    count
) {

    const numericCount =
        Number(count);


    document
        .querySelectorAll(
            ".cart-count, [data-cart-count]"
        )
        .forEach(
            function (element) {

                element.textContent =
                    Number.isFinite(
                        numericCount
                    )
                        ? numericCount
                        : 0;


                element.classList.toggle(
                    "empty",
                    numericCount <= 0
                );

            }
        );


    document.dispatchEvent(
        new CustomEvent(
            "hochipo:cartUpdated",
            {
                detail: {
                    count:
                        numericCount
                }
            }
        )
    );

}


/* ==============================================================
   PAGE LOADING
============================================================== */

window.addEventListener(
    "load",
    function () {

        document.body.classList.add(
            "page-loaded"
        );


        const loader =
            document.querySelector(
                "#pageLoader, .page-loader"
            );


        if (loader) {

            loader.classList.add(
                "loaded"
            );


            setTimeout(
                function () {

                    loader.remove();

                },
                500
            );

        }

    }
);


/* ==============================================================
   GLOBAL PUBLIC API
============================================================== */

window.HochipoHub = {

    notification:
        showGlobalNotification,

    loading:
        showGlobalLoading,

    hideLoading:
        hideGlobalLoading,

    updateCartCount:
        updateCartCount,

    formatPrice:
        formatPrice,

    escapeHTML:
        escapeHTML,

    getUrlParameter:
        getUrlParameter,

    debounce:
        debounce,

    throttle:
        throttle

};