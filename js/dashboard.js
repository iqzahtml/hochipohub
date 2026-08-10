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
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD INITIALIZATION
    |--------------------------------------------------------------------------
    */

    initDashboard();
    initProductImages();
    initDashboardLinks();
    initDashboardHover();
    initStatusIndicators();

});


/*
|--------------------------------------------------------------------------
| MAIN DASHBOARD INITIALIZATION
|--------------------------------------------------------------------------
*/

function initDashboard() {

    const dashboardPage =
        document.querySelector(".dashboard-page");

    if (!dashboardPage) {
        return;
    }

    dashboardPage.classList.add("dashboard-ready");

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

    images.forEach(function (image) {

        image.addEventListener("error", function () {

            image.style.display = "none";

            const placeholder =
                image.parentElement.querySelector(
                    ".dashboard-product-placeholder"
                );

            if (placeholder) {
                placeholder.style.display = "flex";
            }

        });

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

    links.forEach(function (link) {

        link.addEventListener("click", function () {

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

        });

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
            ".dashboard-stat-value"
        );

    values.forEach(function (element) {

        const text =
            element.textContent.trim();

        /*
        |--------------------------------------------------------------------------
        | Skip RM values
        |--------------------------------------------------------------------------
        */

        if (
            text.includes("RM") ||
            text.includes("%") ||
            text.includes(",")
        ) {
            return;
        }

        const target =
            parseInt(text, 10);

        if (
            Number.isNaN(target) ||
            target <= 0
        ) {
            return;
        }

        let current = 0;

        const duration = 700;

        const steps = 30;

        const increment =
            target / steps;

        const intervalTime =
            duration / steps;

        const timer =
            setInterval(function () {

                current += increment;

                if (current >= target) {

                    current = target;

                    clearInterval(timer);

                }

                element.textContent =
                    Math.floor(current)
                        .toLocaleString();

            }, intervalTime);

    });

}


/*
|--------------------------------------------------------------------------
| RUN NUMBER ANIMATION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        setTimeout(
            animateDashboardNumbers,
            250
        );

    }
);


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

window.addEventListener(
    "resize",
    checkDashboardMobile
);


/*
|--------------------------------------------------------------------------
| INITIAL MOBILE CHECK
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    checkDashboardMobile
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