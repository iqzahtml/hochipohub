/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - DASHBOARD JS
|--------------------------------------------------------------------------
| Dashboard interactions
| Sidebar
| Mobile navigation
| Stats animation
| Charts
| Tabs
| Notifications
| Confirm actions
| AJAX dashboard refresh
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* ==============================================================
       SIDEBAR TOGGLE
    ============================================================== */

    const sidebar = document.querySelector(".dashboard-sidebar");
    const sidebarToggle = document.querySelector(
        ".sidebar-toggle, #sidebarToggle, [data-sidebar-toggle]"
    );
    const sidebarOverlay = document.querySelector(
        ".sidebar-overlay, #sidebarOverlay"
    );

    function openSidebar() {

        if (!sidebar) return;

        sidebar.classList.add("active");

        if (sidebarOverlay) {
            sidebarOverlay.classList.add("active");
        }

        document.body.classList.add("sidebar-open");
    }


    function closeSidebar() {

        if (!sidebar) return;

        sidebar.classList.remove("active");

        if (sidebarOverlay) {
            sidebarOverlay.classList.remove("active");
        }

        document.body.classList.remove("sidebar-open");
    }


    if (sidebarToggle) {

        sidebarToggle.addEventListener("click", function (event) {

            event.preventDefault();

            if (sidebar && sidebar.classList.contains("active")) {
                closeSidebar();
            } else {
                openSidebar();
            }

        });

    }


    if (sidebarOverlay) {

        sidebarOverlay.addEventListener("click", function () {

            closeSidebar();

        });

    }


    /* ==============================================================
       CLOSE SIDEBAR WHEN CLICKING NAVIGATION LINK
    ============================================================== */

    const sidebarLinks = document.querySelectorAll(
        ".dashboard-sidebar a, .vendor-sidebar a, .customer-sidebar a, .admin-sidebar a"
    );


    sidebarLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            if (window.innerWidth <= 850) {

                closeSidebar();

            }

        });

    });


    /* ==============================================================
       ESCAPE KEY
    ============================================================== */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            closeSidebar();

        }

    });


    /* ==============================================================
       ACTIVE SIDEBAR LINK
    ============================================================== */

    const currentPage = window.location.pathname
        .split("/")
        .pop()
        .toLowerCase();


    sidebarLinks.forEach(function (link) {

        const href = link.getAttribute("href");

        if (!href) return;

        const linkPage = href
            .split("/")
            .pop()
            .split("?")[0]
            .toLowerCase();


        if (
            linkPage &&
            currentPage &&
            linkPage === currentPage
        ) {

            link.classList.add("active");

        }

    });


    /* ==============================================================
       DROPDOWN MENU
    ============================================================== */

    const dropdownButtons = document.querySelectorAll(
        "[data-dashboard-dropdown]"
    );


    dropdownButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const dropdown = button.closest(
                ".dashboard-dropdown, .sidebar-dropdown"
            );

            if (!dropdown) return;

            dropdown.classList.toggle("active");

        });

    });


    /* ==============================================================
       STAT NUMBER ANIMATION
    ============================================================== */

    const statNumbers = document.querySelectorAll(
        "[data-count], .dashboard-stat-number[data-value]"
    );


    function animateNumber(element) {

        let target = element.dataset.count ||
                     element.dataset.value;

        if (!target) return;

        target = parseFloat(
            String(target).replace(/,/g, "")
        );


        if (isNaN(target)) return;


        const duration = 900;

        const startTime = performance.now();


        function update(currentTime) {

            const elapsed = currentTime - startTime;

            const progress = Math.min(
                elapsed / duration,
                1
            );


            const eased =
                1 - Math.pow(1 - progress, 3);


            const current =
                target * eased;


            if (Number.isInteger(target)) {

                element.textContent =
                    Math.floor(current).toLocaleString();

            } else {

                element.textContent =
                    current.toFixed(2);

            }


            if (progress < 1) {

                requestAnimationFrame(update);

            } else {

                if (Number.isInteger(target)) {

                    element.textContent =
                        target.toLocaleString();

                } else {

                    element.textContent =
                        target.toFixed(2);

                }

            }

        }


        requestAnimationFrame(update);

    }


    /* ==============================================================
       INTERSECTION OBSERVER FOR STATS
    ============================================================== */

    if ("IntersectionObserver" in window) {

        const observer = new IntersectionObserver(
            function (entries, observerInstance) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        animateNumber(entry.target);

                        observerInstance.unobserve(
                            entry.target
                        );

                    }

                });

            },
            {
                threshold: 0.3
            }
        );


        statNumbers.forEach(function (number) {

            observer.observe(number);

        });

    } else {

        statNumbers.forEach(function (number) {

            animateNumber(number);

        });

    }


    /* ==============================================================
       DASHBOARD TABS
    ============================================================== */

    const dashboardTabs = document.querySelectorAll(
        "[data-dashboard-tab]"
    );


    dashboardTabs.forEach(function (tab) {

        tab.addEventListener("click", function (event) {

            event.preventDefault();

            const targetID =
                tab.dataset.dashboardTab;


            dashboardTabs.forEach(function (item) {

                item.classList.remove("active");

            });


            tab.classList.add("active");


            const panels = document.querySelectorAll(
                "[data-dashboard-panel]"
            );


            panels.forEach(function (panel) {

                panel.classList.remove("active");

            });


            const targetPanel =
                document.querySelector(
                    `[data-dashboard-panel="${targetID}"]`
                );


            if (targetPanel) {

                targetPanel.classList.add("active");

            }

        });

    });


    /* ==============================================================
       FILTER BUTTONS
    ============================================================== */

    const filterButtons = document.querySelectorAll(
        "[data-dashboard-filter]"
    );


    filterButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const filter =
                button.dataset.dashboardFilter;


            filterButtons.forEach(function (item) {

                item.classList.remove("active");

            });


            button.classList.add("active");


            const rows = document.querySelectorAll(
                "[data-dashboard-status]"
            );


            rows.forEach(function (row) {

                const status =
                    row.dataset.dashboardStatus;


                if (
                    filter === "all" ||
                    !filter
                ) {

                    row.style.display = "";

                    return;

                }


                if (
                    status &&
                    status.toLowerCase() ===
                    filter.toLowerCase()
                ) {

                    row.style.display = "";

                } else {

                    row.style.display = "none";

                }

            });

        });

    });


    /* ==============================================================
       NOTIFICATION DROPDOWN
    ============================================================== */

    const notificationButton = document.querySelector(
        ".notification-button, #notificationButton, [data-notification-toggle]"
    );


    const notificationDropdown = document.querySelector(
        ".notification-dropdown, #notificationDropdown"
    );


    if (
        notificationButton &&
        notificationDropdown
    ) {

        notificationButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                notificationDropdown.classList.toggle(
                    "active"
                );

            }
        );


        document.addEventListener(
            "click",
            function (event) {

                if (
                    !notificationDropdown.contains(
                        event.target
                    ) &&
                    !notificationButton.contains(
                        event.target
                    )
                ) {

                    notificationDropdown.classList.remove(
                        "active"
                    );

                }

            }
        );

    }


    /* ==============================================================
       MARK NOTIFICATIONS AS READ
    ============================================================== */

    const markReadButton = document.querySelector(
        "[data-mark-notifications-read]"
    );


    if (markReadButton) {

        markReadButton.addEventListener(
            "click",
            function () {

                const badges = document.querySelectorAll(
                    ".notification-count, .notification-badge"
                );


                badges.forEach(function (badge) {

                    badge.textContent = "0";

                    badge.style.display = "none";

                });


                const notifications =
                    document.querySelectorAll(
                        ".notification-item.unread"
                    );


                notifications.forEach(function (item) {

                    item.classList.remove("unread");

                });

            }
        );

    }


    /* ==============================================================
       DELETE / CANCEL CONFIRMATION
    ============================================================== */

    const confirmButtons = document.querySelectorAll(
        "[data-confirm]"
    );


    confirmButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            const message =
                button.dataset.confirm ||
                "Are you sure you want to continue?";


            if (!window.confirm(message)) {

                event.preventDefault();

            }

        });

    });


    /* ==============================================================
       AUTO DISMISS ALERT
    ============================================================== */

    const alerts = document.querySelectorAll(
        ".dashboard-alert, .alert[data-auto-dismiss]"
    );


    alerts.forEach(function (alert) {

        const delay =
            parseInt(
                alert.dataset.autoDismiss ||
                "5000",
                10
            );


        if (delay > 0) {

            setTimeout(function () {

                alert.classList.add("hide");


                setTimeout(function () {

                    alert.remove();

                }, 300);

            }, delay);

        }

    });


    /* ==============================================================
       CLOSE ALERT BUTTON
    ============================================================== */

    const alertCloseButtons = document.querySelectorAll(
        ".alert-close, [data-close-alert]"
    );


    alertCloseButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const alert =
                button.closest(
                    ".dashboard-alert, .alert"
                );


            if (!alert) return;


            alert.classList.add("hide");


            setTimeout(function () {

                alert.remove();

            }, 300);

        });

    });


    /* ==============================================================
       TABLE SELECT ALL
    ============================================================== */

    const selectAllCheckboxes = document.querySelectorAll(
        "[data-select-all]"
    );


    selectAllCheckboxes.forEach(function (selectAll) {

        selectAll.addEventListener("change", function () {

            const table =
                selectAll.closest("table");


            if (!table) return;


            const checkboxes =
                table.querySelectorAll(
                    "tbody input[type='checkbox']"
                );


            checkboxes.forEach(function (checkbox) {

                checkbox.checked =
                    selectAll.checked;

            });

        });

    });


    /* ==============================================================
       DELETE SELECTED ITEMS
    ============================================================== */

    const bulkDeleteButtons = document.querySelectorAll(
        "[data-bulk-delete]"
    );


    bulkDeleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const table =
                button.closest(
                    ".dashboard-table-wrapper, .table-responsive"
                );


            if (!table) return;


            const selected =
                table.querySelectorAll(
                    "tbody input[type='checkbox']:checked"
                );


            if (selected.length === 0) {

                showDashboardMessage(
                    "Please select at least one item.",
                    "warning"
                );

                return;

            }


            const confirmed =
                window.confirm(
                    `Delete ${selected.length} selected item(s)?`
                );


            if (!confirmed) return;


            selected.forEach(function (checkbox) {

                const row =
                    checkbox.closest("tr");


                if (row) {

                    row.remove();

                }

            });

        });

    });


    /* ==============================================================
       SEARCH DASHBOARD TABLE
    ============================================================== */

    const dashboardSearchInputs =
        document.querySelectorAll(
            "[data-dashboard-table-search]"
        );


    dashboardSearchInputs.forEach(function (input) {

        input.addEventListener(
            "input",
            function () {

                const searchValue =
                    input.value
                        .trim()
                        .toLowerCase();


                const targetSelector =
                    input.dataset.dashboardTableSearch;


                let table;


                if (targetSelector) {

                    table =
                        document.querySelector(
                            targetSelector
                        );

                } else {

                    table =
                        input.closest(
                            ".dashboard-card, .dashboard-section"
                        );

                }


                if (!table) return;


                const rows =
                    table.querySelectorAll(
                        "tbody tr"
                    );


                rows.forEach(function (row) {

                    const text =
                        row.textContent
                            .toLowerCase();


                    row.style.display =
                        text.includes(searchValue)
                            ? ""
                            : "none";

                });

            }
        );

    });


    /* ==============================================================
       COPY TO CLIPBOARD
    ============================================================== */

    const copyButtons = document.querySelectorAll(
        "[data-copy]"
    );


    copyButtons.forEach(function (button) {

        button.addEventListener("click", async function () {

            const value =
                button.dataset.copy;


            if (!value) return;


            try {

                await navigator.clipboard.writeText(
                    value
                );


                const originalText =
                    button.innerHTML;


                button.innerHTML =
                    '<i class="fa-solid fa-check"></i> Copied';


                setTimeout(function () {

                    button.innerHTML =
                        originalText;

                }, 1500);


            } catch (error) {

                console.error(
                    "Copy failed:",
                    error
                );

            }

        });

    });


    /* ==============================================================
       PASSWORD VISIBILITY
    ============================================================== */

    const passwordToggles =
        document.querySelectorAll(
            "[data-password-toggle]"
        );


    passwordToggles.forEach(function (toggle) {

        toggle.addEventListener("click", function () {

            const targetSelector =
                toggle.dataset.passwordToggle;


            const input =
                document.querySelector(
                    targetSelector
                );


            if (!input) return;


            if (input.type === "password") {

                input.type = "text";


                toggle.innerHTML =
                    '<i class="fa-solid fa-eye-slash"></i>';

            } else {

                input.type = "password";


                toggle.innerHTML =
                    '<i class="fa-solid fa-eye"></i>';

            }

        });

    });


    /* ==============================================================
       RESPONSIVE WINDOW
    ============================================================== */

    window.addEventListener(
        "resize",
        function () {

            if (window.innerWidth > 850) {

                if (sidebarOverlay) {

                    sidebarOverlay.classList.remove(
                        "active"
                    );

                }

                if (sidebar) {

                    sidebar.classList.remove(
                        "active"
                    );

                }

                document.body.classList.remove(
                    "sidebar-open"
                );

            }

        }
    );


    /* ==============================================================
       DASHBOARD MESSAGE
    ============================================================== */

    function showDashboardMessage(
        message,
        type = "info"
    ) {

        let container =
            document.querySelector(
                ".dashboard-message-container"
            );


        if (!container) {

            container =
                document.createElement("div");

            container.className =
                "dashboard-message-container";


            document.body.appendChild(
                container
            );

        }


        const messageBox =
            document.createElement("div");


        messageBox.className =
            `dashboard-message ${type}`;


        messageBox.innerHTML = `
            <span>${escapeHTML(message)}</span>

            <button type="button"
                    class="dashboard-message-close"
                    aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;


        container.appendChild(
            messageBox
        );


        const closeButton =
            messageBox.querySelector(
                ".dashboard-message-close"
            );


        closeButton.addEventListener(
            "click",
            function () {

                removeMessage(
                    messageBox
                );

            }
        );


        setTimeout(function () {

            removeMessage(
                messageBox
            );

        }, 3500);

    }


    function removeMessage(element) {

        if (!element) return;


        element.classList.add("hide");


        setTimeout(function () {

            element.remove();

        }, 250);

    }


    /* ==============================================================
       ESCAPE HTML
    ============================================================== */

    function escapeHTML(value) {

        const div =
            document.createElement("div");


        div.textContent =
            value;


        return div.innerHTML;

    }


    /* ==============================================================
       EXPOSE MESSAGE FUNCTION
    ============================================================== */

    window.HochipoHubDashboard = {

        message: showDashboardMessage,

        closeSidebar: closeSidebar,

        openSidebar: openSidebar

    };


});