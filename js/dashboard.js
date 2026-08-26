/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - DASHBOARD JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/dashboard.js
|
| Functions:
| - Dashboard card interaction
| - Mobile dashboard behaviour
| - Status animation
| - Product image fallback
| - Prevent double-click on dashboard links
| - Dashboard number animation
| - Dashboard entrance animation
|--------------------------------------------------------------------------
*/

(function () {

    "use strict";


    /*
    |--------------------------------------------------------------------------
    | DOM READY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            initDashboard();
            initProductImages();
            initDashboardLinks();
            initDashboardHover();
            initStatusIndicators();
            checkDashboardMobile();

            setTimeout(
                animateDashboardNumbers,
                250
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | MAIN DASHBOARD INITIALIZATION
    |--------------------------------------------------------------------------
    */

    function initDashboard() {

        const dashboardPage =
            document.querySelector(
                ".dashboard-page"
            );

        if (!dashboardPage) {
            return;
        }

        dashboardPage.classList.add(
            "dashboard-ready"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT IMAGE FALLBACK
    |--------------------------------------------------------------------------
    */

    function initProductImages() {

        const images =
            document.querySelectorAll(
                ".dashboard-product-image img"
            );

        if (!images.length) {
            return;
        }

        images.forEach(function (image) {

            image.addEventListener(
                "error",
                function () {

                    image.style.display = "none";

                    const placeholder =
                        image.parentElement
                            ? image.parentElement.querySelector(
                                ".dashboard-product-placeholder"
                            )
                            : null;

                    if (placeholder) {

                        placeholder.style.display =
                            "flex";

                    }

                }
            );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD LINKS
    |--------------------------------------------------------------------------
    | Prevent accidental multiple clicks.
    |--------------------------------------------------------------------------
    */

    function initDashboardLinks() {

        const links =
            document.querySelectorAll(
                ".dashboard-view-link, .dashboard-action"
            );

        if (!links.length) {
            return;
        }

        links.forEach(function (link) {

            link.addEventListener(
                "click",
                function () {

                    if (
                        link.classList.contains(
                            "dashboard-link-loading"
                        )
                    ) {
                        return;
                    }

                    link.classList.add(
                        "dashboard-link-loading"
                    );

                    /*
                    |----------------------------------------------------------
                    | Do not permanently disable normal navigation.
                    | Remove loading state if browser stays on page.
                    |----------------------------------------------------------
                    */

                    setTimeout(
                        function () {

                            link.classList.remove(
                                "dashboard-link-loading"
                            );

                        },
                        1800
                    );

                }
            );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD CARD HOVER
    |--------------------------------------------------------------------------
    */

    function initDashboardHover() {

        const cards =
            document.querySelectorAll(
                ".dashboard-stat"
            );

        if (!cards.length) {
            return;
        }

        cards.forEach(function (card) {

            card.addEventListener(
                "mouseenter",
                function () {

                    card.classList.add(
                        "dashboard-stat-active"
                    );

                }
            );

            card.addEventListener(
                "mouseleave",
                function () {

                    card.classList.remove(
                        "dashboard-stat-active"
                    );

                }
            );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS INDICATORS
    |--------------------------------------------------------------------------
    */

    function initStatusIndicators() {

        const statuses =
            document.querySelectorAll(
                ".dashboard-status"
            );

        if (!statuses.length) {
            return;
        }

        statuses.forEach(function (status) {

            status.classList.add(
                "dashboard-status-ready"
            );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | NUMBER ANIMATION
    |--------------------------------------------------------------------------
    | Animates dashboard statistic numbers.
    |--------------------------------------------------------------------------
    */

    function animateDashboardNumbers() {

        const values =
            document.querySelectorAll(
                ".dashboard-stat-value, .stat-card strong"
            );

        if (!values.length) {
            return;
        }

        values.forEach(function (element) {

            /*
            |------------------------------------------------------------------
            | Prevent animation twice
            |------------------------------------------------------------------
            */

            if (
                element.dataset.dashboardAnimated === "true"
            ) {
                return;
            }

            const text =
                element.textContent.trim();

            /*
            |------------------------------------------------------------------
            | Skip currency, percentage and formatted values.
            |------------------------------------------------------------------
            */

            if (
                text.includes("RM") ||
                text.includes("%") ||
                text.includes(",")
            ) {
                return;
            }

            const target =
                parseInt(
                    text.replace(/[^\d-]/g, ""),
                    10
                );

            if (
                Number.isNaN(target) ||
                target <= 0
            ) {
                return;
            }

            element.dataset.dashboardAnimated =
                "true";

            let current = 0;

            const duration = 700;
            const steps = 30;

            const increment =
                target / steps;

            const intervalTime =
                duration / steps;

            const timer =
                setInterval(
                    function () {

                        current += increment;

                        if (current >= target) {

                            current = target;

                            clearInterval(timer);

                        }

                        element.textContent =
                            Math.floor(current)
                                .toLocaleString();

                    },
                    intervalTime
                );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | MOBILE DASHBOARD
    |--------------------------------------------------------------------------
    */

    function checkDashboardMobile() {

        const dashboard =
            document.querySelector(
                ".dashboard-page"
            );

        if (!dashboard) {
            return;
        }

        if (window.innerWidth <= 700) {

            dashboard.classList.add(
                "dashboard-mobile"
            );

        } else {

            dashboard.classList.remove(
                "dashboard-mobile"
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | WINDOW RESIZE
    |--------------------------------------------------------------------------
    */

    let resizeTimer = null;

    window.addEventListener(
        "resize",
        function () {

            clearTimeout(resizeTimer);

            resizeTimer =
                setTimeout(
                    checkDashboardMobile,
                    120
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD SCROLL
    |--------------------------------------------------------------------------
    */

    function dashboardScrollToTop() {

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });

    }


    /*
    |--------------------------------------------------------------------------
    | EXPOSE FUNCTIONS
    |--------------------------------------------------------------------------
    */

    window.dashboardScrollToTop =
        dashboardScrollToTop;

    window.animateDashboardNumbers =
        animateDashboardNumbers;

})();