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


    /* ==============================================================
       MOBILE NAVIGATION
    ============================================================== */

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