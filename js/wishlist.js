/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - WISHLIST JS
|--------------------------------------------------------------------------
| Handles:
| - Add / remove wishlist
| - Wishlist button state
| - AJAX wishlist request
| - Login checking
| - Wishlist counter
| - Toast notification
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* ==============================================================
       CONFIGURATION
    ============================================================== */

    const WishlistConfig = {

        addUrl: "ajax/wishlist.php",

        removeUrl: "ajax/wishlist.php",

        loginUrl: "index.php",

        selectors: {

            wishlistButton: ".product-wishlist",

            wishlistToggle: "[data-wishlist]",

            wishlistCount: ".wishlist-count",

            productCard: ".product-card"

        }

    };


    /* ==============================================================
       INITIALIZATION
    ============================================================== */

    initWishlist();


    function initWishlist() {

        bindWishlistButtons();

        updateWishlistCounter();

    }


    /* ==============================================================
       BIND WISHLIST BUTTONS
    ============================================================== */

    function bindWishlistButtons() {

        const buttons = document.querySelectorAll(
            WishlistConfig.selectors.wishlistButton +
            ", " +
            WishlistConfig.selectors.wishlistToggle
        );


        buttons.forEach(function (button) {

            if (button.dataset.wishlistBound === "true") {
                return;
            }


            button.dataset.wishlistBound = "true";


            button.addEventListener("click", function (event) {

                event.preventDefault();

                event.stopPropagation();

                handleWishlistClick(button);

            });

        });

    }


    /* ==============================================================
       HANDLE WISHLIST CLICK
    ============================================================== */

    function handleWishlistClick(button) {

        const productId =
            button.dataset.productId ||
            button.getAttribute("data-product-id");


        if (!productId) {

            console.error(
                "HOCHIPOHUB Wishlist: Product ID is missing."
            );

            showToast(
                "Product information is missing.",
                "error"
            );

            return;

        }


        const isActive =
            button.classList.contains("active") ||
            button.dataset.wishlisted === "true";


        if (button.dataset.loading === "true") {
            return;
        }


        if (isActive) {

            removeFromWishlist(
                productId,
                button
            );

        } else {

            addToWishlist(
                productId,
                button
            );

        }

    }


    /* ==============================================================
       ADD TO WISHLIST
    ============================================================== */

    function addToWishlist(productId, button) {

        setButtonLoading(button, true);


        sendWishlistRequest(
            "add",
            productId
        )
        .then(function (response) {

            if (!response.success) {

                handleWishlistError(
                    response,
                    button
                );

                return;

            }


            setWishlistState(
                button,
                true
            );


            updateWishlistCounter(
                response.count
            );


            showToast(
                response.message ||
                "Added to wishlist!",
                "success"
            );


            animateWishlist(button);

        })
        .catch(function (error) {

            console.error(
                "Wishlist add error:",
                error
            );


            showToast(
                "Something went wrong. Please try again.",
                "error"
            );

        })
        .finally(function () {

            setButtonLoading(
                button,
                false
            );

        });

    }


    /* ==============================================================
       REMOVE FROM WISHLIST
    ============================================================== */

    function removeFromWishlist(productId, button) {

        setButtonLoading(button, true);


        sendWishlistRequest(
            "remove",
            productId
        )
        .then(function (response) {

            if (!response.success) {

                handleWishlistError(
                    response,
                    button
                );

                return;

            }


            setWishlistState(
                button,
                false
            );


            updateWishlistCounter(
                response.count
            );


            showToast(
                response.message ||
                "Removed from wishlist.",
                "success"
            );


            removeWishlistCardIfNeeded(button);

        })
        .catch(function (error) {

            console.error(
                "Wishlist remove error:",
                error
            );


            showToast(
                "Something went wrong. Please try again.",
                "error"
            );

        })
        .finally(function () {

            setButtonLoading(
                button,
                false
            );

        });

    }


    /* ==============================================================
       SEND AJAX REQUEST
    ============================================================== */

    function sendWishlistRequest(
        action,
        productId
    ) {

        const formData = new FormData();


        formData.append(
            "action",
            action
        );


        formData.append(
            "product_id",
            productId
        );


        return fetch(
            WishlistConfig.addUrl,
            {

                method: "POST",

                body: formData,

                headers: {

                    "X-Requested-With":
                        "XMLHttpRequest"

                }

            }
        )
        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "HTTP error: " +
                    response.status
                );

            }


            return response.json();

        });

    }


    /* ==============================================================
       SET WISHLIST STATE
    ============================================================== */

    function setWishlistState(
        button,
        active
    ) {

        button.classList.toggle(
            "active",
            active
        );


        button.dataset.wishlisted =
            active ? "true" : "false";


        const icon =
            button.querySelector("i");


        if (icon) {

            if (active) {

                icon.classList.remove(
                    "fa-regular"
                );

                icon.classList.add(
                    "fa-solid"
                );

                icon.setAttribute(
                    "aria-label",
                    "Remove from wishlist"
                );

            } else {

                icon.classList.remove(
                    "fa-solid"
                );

                icon.classList.add(
                    "fa-regular"
                );

                icon.setAttribute(
                    "aria-label",
                    "Add to wishlist"
                );

            }

        }


        button.setAttribute(
            "aria-pressed",
            active ? "true" : "false"
        );


        button.setAttribute(
            "title",
            active
                ? "Remove from wishlist"
                : "Add to wishlist"
        );

    }


    /* ==============================================================
       LOADING STATE
    ============================================================== */

    function setButtonLoading(
        button,
        loading
    ) {

        button.dataset.loading =
            loading ? "true" : "false";


        if (loading) {

            button.classList.add(
                "wishlist-loading"
            );


            button.disabled = true;


            const icon =
                button.querySelector("i");


            if (icon) {

                button.dataset.originalIcon =
                    icon.className;


                icon.className =
                    "fa-solid fa-spinner fa-spin";

            }

        } else {

            button.classList.remove(
                "wishlist-loading"
            );


            button.disabled = false;


            const icon =
                button.querySelector("i");


            if (icon) {

                if (
                    button.dataset.originalIcon
                ) {

                    icon.className =
                        button.dataset.originalIcon;

                } else {

                    const active =
                        button.classList.contains(
                            "active"
                        );


                    icon.className =
                        active
                            ? "fa-solid fa-heart"
                            : "fa-regular fa-heart";

                }

            }

        }

    }


    /* ==============================================================
       UPDATE WISHLIST COUNTER
    ============================================================== */

    function updateWishlistCounter(
        count = null
    ) {

        const counters =
            document.querySelectorAll(
                WishlistConfig.selectors.wishlistCount
            );


        if (!counters.length) {
            return;
        }


        if (count !== null) {

            setCounterValue(
                counters,
                count
            );

            return;

        }


        fetchWishlistCount()
            .then(function (response) {

                if (
                    response &&
                    response.success
                ) {

                    setCounterValue(
                        counters,
                        response.count
                    );

                }

            })
            .catch(function (error) {

                console.warn(
                    "Unable to update wishlist count:",
                    error
                );

            });

    }


    /* ==============================================================
       FETCH WISHLIST COUNT
    ============================================================== */

    function fetchWishlistCount() {

        return fetch(
            WishlistConfig.addUrl +
            "?action=count",
            {

                method: "GET",

                headers: {

                    "X-Requested-With":
                        "XMLHttpRequest"

                }

            }
        )
        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Unable to fetch wishlist count."
                );

            }


            return response.json();

        });

    }


    /* ==============================================================
       SET COUNTER VALUE
    ============================================================== */

    function setCounterValue(
        counters,
        count
    ) {

        let numericCount =
            parseInt(count, 10);


        if (isNaN(numericCount)) {
            numericCount = 0;
        }


        counters.forEach(function (counter) {

            counter.textContent =
                numericCount;


            if (numericCount > 0) {

                counter.classList.add(
                    "has-items"
                );

                counter.style.display =
                    "flex";

            } else {

                counter.classList.remove(
                    "has-items"
                );

                counter.style.display =
                    "none";

            }

        });

    }


    /* ==============================================================
       REMOVE WISHLIST CARD
    ============================================================== */

    function removeWishlistCardIfNeeded(
        button
    ) {

        const wishlistPage =
            document.body.classList.contains(
                "wishlist-page"
            );


        if (!wishlistPage) {
            return;
        }


        const card =
            button.closest(
                WishlistConfig.selectors.productCard
            );


        if (!card) {
            return;
        }


        card.style.transition =
            "opacity 0.25s ease, transform 0.25s ease";


        card.style.opacity = "0";


        card.style.transform =
            "scale(0.95)";


        setTimeout(function () {

            card.remove();

            checkWishlistEmptyState();

        }, 260);

    }


    /* ==============================================================
       CHECK EMPTY WISHLIST
    ============================================================== */

    function checkWishlistEmptyState() {

        const grid =
            document.querySelector(
                ".product-grid"
            );


        if (!grid) {
            return;
        }


        const cards =
            grid.querySelectorAll(
                ".product-card"
            );


        if (cards.length > 0) {
            return;
        }


        const existingEmpty =
            grid.querySelector(
                ".product-empty"
            );


        if (existingEmpty) {
            return;
        }


        const empty =
            document.createElement("div");


        empty.className =
            "product-empty";


        empty.innerHTML = `

            <div class="product-empty-content">

                <div class="product-empty-icon">

                    <i class="fa-regular fa-heart"></i>

                </div>

                <h3>Your wishlist is empty</h3>

                <p>
                    Save products you love and
                    come back to them anytime.
                </p>

                <a
                    href="catalog.php"
                    class="product-action-btn primary"
                    style="margin-top:14px;"
                >
                    <i class="fa-solid fa-store"></i>
                    Browse Products
                </a>

            </div>

        `;


        grid.appendChild(empty);

    }


    /* ==============================================================
       ANIMATE WISHLIST BUTTON
    ============================================================== */

    function animateWishlist(button) {

        button.classList.remove(
            "wishlist-pop"
        );


        void button.offsetWidth;


        button.classList.add(
            "wishlist-pop"
        );


        setTimeout(function () {

            button.classList.remove(
                "wishlist-pop"
            );

        }, 450);

    }


    /* ==============================================================
       ERROR HANDLER
    ============================================================== */

    function handleWishlistError(
        response,
        button
    ) {

        if (
            response.login_required ||
            response.status === "login_required"
        ) {

            showLoginRequired();

            return;

        }


        showToast(
            response.message ||
            "Unable to update wishlist.",
            "error"
        );

    }


    /* ==============================================================
       LOGIN REQUIRED
    ============================================================== */

    function showLoginRequired() {

        const loginModal =
            document.querySelector(
                "#loginModal"
            );


        if (loginModal) {

            loginModal.classList.add(
                "active"
            );

            loginModal.style.display =
                "flex";


            document.body.classList.add(
                "modal-open"
            );

            return;

        }


        showToast(
            "Please login to use your wishlist.",
            "info"
        );


        setTimeout(function () {

            window.location.href =
                WishlistConfig.loginUrl;

        }, 1200);

    }


    /* ==============================================================
       TOAST NOTIFICATION
    ============================================================== */

    function showToast(
        message,
        type = "info"
    ) {

        let container =
            document.querySelector(
                ".hochipo-toast-container"
            );


        if (!container) {

            container =
                document.createElement("div");


            container.className =
                "hochipo-toast-container";


            document.body.appendChild(
                container
            );

        }


        const toast =
            document.createElement("div");


        toast.className =
            "hochipo-toast " +
            "hochipo-toast-" +
            type;


        let icon =
            "fa-circle-info";


        if (type === "success") {

            icon = "fa-circle-check";

        } else if (type === "error") {

            icon = "fa-circle-xmark";

        } else if (type === "warning") {

            icon = "fa-triangle-exclamation";

        }


        toast.innerHTML = `

            <i class="fa-solid ${icon}"></i>

            <span></span>

            <button
                type="button"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        `;


        toast.querySelector(
            "span"
        ).textContent = message;


        container.appendChild(
            toast
        );


        requestAnimationFrame(function () {

            toast.classList.add(
                "show"
            );

        });


        const closeButton =
            toast.querySelector(
                "button"
            );


        closeButton.addEventListener(
            "click",
            function () {

                removeToast(toast);

            }
        );


        const timeout =
            setTimeout(function () {

                removeToast(toast);

            }, 3000);


        toast.dataset.timeout =
            timeout;

    }


    /* ==============================================================
       REMOVE TOAST
    ============================================================== */

    function removeToast(toast) {

        if (!toast) {
            return;
        }


        const timeout =
            toast.dataset.timeout;


        if (timeout) {

            clearTimeout(timeout);

        }


        toast.classList.remove(
            "show"
        );


        setTimeout(function () {

            if (toast.parentNode) {

                toast.parentNode.removeChild(
                    toast
                );

            }

        }, 250);

    }


    /* ==============================================================
       DYNAMIC CONTENT SUPPORT
    ============================================================== */

    window.HochipoWishlist = {

        add: function (
            productId,
            button
        ) {

            addToWishlist(
                productId,
                button
            );

        },


        remove: function (
            productId,
            button
        ) {

            removeFromWishlist(
                productId,
                button
            );

        },


        refresh: function () {

            bindWishlistButtons();

            updateWishlistCounter();

        },


        updateCounter: function (
            count
        ) {

            updateWishlistCounter(
                count
            );

        }

    };


});