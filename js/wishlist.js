/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - WISHLIST JS
|--------------------------------------------------------------------------
| Handles:
| - Add wishlist
| - Remove wishlist
| - Toggle wishlist
| - Wishlist counter
| - AJAX requests
| - Button state
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {


    /* ==============================================================
       WISHLIST BUTTONS
    ============================================================== */

    const wishlistButtons =
        document.querySelectorAll(
            ".product-wishlist, [data-wishlist]"
        );

    wishlistButtons.forEach(button => {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();

                toggleWishlist(button);

            }
        );

    });


    /* ==============================================================
       TOGGLE WISHLIST
    ============================================================== */

    async function toggleWishlist(button) {

        if (
            button.dataset.loading === "true"
        ) {
            return;
        }

        const productId =
            button.dataset.productId ||
            button.dataset.wishlist;

        if (!productId) {

            console.error(
                "Wishlist error: Product ID missing."
            );

            return;

        }

        button.dataset.loading = "true";

        const wasActive =
            button.classList.contains("active");

        const icon =
            button.querySelector("i");

        const originalIcon =
            icon
                ? icon.className
                : "";

        /* ----------------------------------------------------------
           Loading state
        ---------------------------------------------------------- */

        if (icon) {

            icon.className =
                "fa-solid fa-spinner fa-spin";

        }

        try {

            const formData =
                new FormData();

            formData.append(
                "product_id",
                productId
            );

            formData.append(
                "action",
                wasActive
                    ? "remove"
                    : "add"
            );


            /* ------------------------------------------------------
               AJAX REQUEST
            ------------------------------------------------------ */

            const response =
                await fetch(
                    "ajax/wishlist.php",
                    {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                );


            if (!response.ok) {

                throw new Error(
                    `HTTP error: ${response.status}`
                );

            }


            const result =
                await response.json();


            /* ------------------------------------------------------
               RESPONSE
            ------------------------------------------------------ */

            if (result.success) {

                const active =
                    !wasActive;

                updateWishlistButton(
                    button,
                    active
                );

                updateWishlistCounters(
                    result.count
                );


                /* --------------------------------------------------
                   Toast
                -------------------------------------------------- */

                if (
                    typeof window.showToast ===
                    "function"
                ) {

                    window.showToast(
                        result.message ||
                        (
                            active
                                ? "Added to wishlist."
                                : "Removed from wishlist."
                        ),
                        "success"
                    );

                }

            } else {

                if (icon) {
                    icon.className =
                        originalIcon;
                }

                if (
                    typeof window.showToast ===
                    "function"
                ) {

                    window.showToast(
                        result.message ||
                        "Unable to update wishlist.",
                        "error"
                    );

                }

            }

        } catch (error) {

            console.error(
                "Wishlist error:",
                error
            );

            if (icon) {
                icon.className =
                    originalIcon;
            }

            if (
                typeof window.showToast ===
                "function"
            ) {

                window.showToast(
                    "Something went wrong. Please try again.",
                    "error"
                );

            }

        } finally {

            button.dataset.loading = "false";

        }

    }


    /* ==============================================================
       UPDATE WISHLIST BUTTON
    ============================================================== */

    function updateWishlistButton(
        button,
        active
    ) {

        const icon =
            button.querySelector("i");

        if (active) {

            button.classList.add(
                "active"
            );

            button.setAttribute(
                "aria-pressed",
                "true"
            );

            button.setAttribute(
                "title",
                "Remove from wishlist"
            );

            if (icon) {

                icon.className =
                    "fa-solid fa-heart";

            }

        } else {

            button.classList.remove(
                "active"
            );

            button.setAttribute(
                "aria-pressed",
                "false"
            );

            button.setAttribute(
                "title",
                "Add to wishlist"
            );

            if (icon) {

                icon.className =
                    "fa-regular fa-heart";

            }

        }

    }


    /* ==============================================================
       UPDATE WISHLIST COUNTERS
    ============================================================== */

    function updateWishlistCounters(
        count
    ) {

        if (
            count === undefined ||
            count === null
        ) {
            return;
        }

        document
            .querySelectorAll(
                ".wishlist-count, [data-wishlist-count]"
            )
            .forEach(counter => {

                counter.textContent =
                    count;

                if (Number(count) > 0) {

                    counter.classList.add(
                        "has-items"
                    );

                } else {

                    counter.classList.remove(
                        "has-items"
                    );

                }

            });

    }


    /* ==============================================================
       REMOVE FROM WISHLIST PAGE
    ============================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const removeButton =
                event.target.closest(
                    ".remove-wishlist"
                );

            if (!removeButton) {
                return;
            }

            event.preventDefault();

            const productId =
                removeButton.dataset.productId;

            if (!productId) {
                return;
            }

            removeWishlistItem(
                removeButton,
                productId
            );

        }
    );


    /* ==============================================================
       REMOVE WISHLIST ITEM
    ============================================================== */

    async function removeWishlistItem(
        button,
        productId
    ) {

        if (
            button.dataset.loading === "true"
        ) {
            return;
        }

        button.dataset.loading = "true";

        const formData =
            new FormData();

        formData.append(
            "product_id",
            productId
        );

        formData.append(
            "action",
            "remove"
        );

        try {

            const response =
                await fetch(
                    "ajax/wishlist.php",
                    {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                );

            const result =
                await response.json();

            if (result.success) {

                const item =
                    button.closest(
                        ".wishlist-item, .product-card"
                    );

                if (item) {

                    item.style.opacity = "0";

                    item.style.transform =
                        "scale(0.95)";

                    item.style.transition =
                        "all 0.25s ease";

                    setTimeout(() => {

                        item.remove();

                        checkEmptyWishlist();

                    }, 250);

                }

                updateWishlistCounters(
                    result.count
                );

                if (
                    typeof window.showToast ===
                    "function"
                ) {

                    window.showToast(
                        result.message ||
                        "Removed from wishlist.",
                        "success"
                    );

                }

            } else {

                if (
                    typeof window.showToast ===
                    "function"
                ) {

                    window.showToast(
                        result.message ||
                        "Unable to remove item.",
                        "error"
                    );

                }

            }

        } catch (error) {

            console.error(
                "Remove wishlist error:",
                error
            );

            if (
                typeof window.showToast ===
                "function"
            ) {

                window.showToast(
                    "Something went wrong.",
                    "error"
                );

            }

        } finally {

            button.dataset.loading =
                "false";

        }

    }


    /* ==============================================================
       CHECK EMPTY WISHLIST
    ============================================================== */

    function checkEmptyWishlist() {

        const wishlistContainer =
            document.querySelector(
                ".wishlist-grid, .wishlist-list"
            );

        if (!wishlistContainer) {
            return;
        }

        const items =
            wishlistContainer.querySelectorAll(
                ".wishlist-item, .product-card"
            );

        if (items.length === 0) {

            wishlistContainer.innerHTML = `
                <div class="product-empty">
                    <div class="product-empty-content">

                        <div class="product-empty-icon">
                            <i class="fa-regular fa-heart"></i>
                        </div>

                        <h3>Your wishlist is empty</h3>

                        <p>
                            Save products you love
                            and come back to them later.
                        </p>

                        <a
                            href="catalog.php"
                            class="product-action-btn primary"
                        >
                            <i class="fa-solid fa-bag-shopping"></i>
                            Explore Products
                        </a>

                    </div>
                </div>
            `;

        }

    }


    /* ==============================================================
       INITIAL WISHLIST STATE
    ============================================================== */

    document
        .querySelectorAll(
            ".product-wishlist[data-active]"
        )
        .forEach(button => {

            const active =
                button.dataset.active === "true" ||
                button.dataset.active === "1";

            updateWishlistButton(
                button,
                active
            );

        });


});