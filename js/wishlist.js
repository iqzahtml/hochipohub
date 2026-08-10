/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - WISHLIST.JS
|--------------------------------------------------------------------------
| Handles:
| - Add wishlist
| - Remove wishlist
| - Toggle wishlist
| - Wishlist counter
| - AJAX requests
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | WISHLIST BUTTONS
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    ".wishlist-btn, " +
                    ".add-wishlist, " +
                    ".wishlist-button, " +
                    "[data-wishlist]"
                );

            if (!button) {
                return;
            }

            event.preventDefault();

            toggleWishlist(button);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | REMOVE BUTTON
    |--------------------------------------------------------------------------
    */

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

            const wishlistId =
                removeButton.dataset.wishlistId;

            const productId =
                removeButton.dataset.productId;

            removeWishlist(
                removeButton,
                wishlistId,
                productId
            );

        }
    );

});


/*
|--------------------------------------------------------------------------
| TOGGLE WISHLIST
|--------------------------------------------------------------------------
*/

function toggleWishlist(button) {

    if (
        button.dataset.loading === "true"
    ) {
        return;
    }


    const productId =
        button.dataset.productId ||
        button.getAttribute("data-id");


    if (!productId) {

        console.error(
            "Wishlist: product ID not found."
        );

        return;
    }


    const isActive =
        button.classList.contains(
            "active"
        ) ||
        button.classList.contains(
            "in-wishlist"
        );


    const action =
        isActive
            ? "remove"
            : "add";


    button.dataset.loading =
        "true";

    button.classList.add(
        "wishlist-loading"
    );


    const formData =
        new FormData();

    formData.append(
        "product_id",
        productId
    );

    formData.append(
        "action",
        action
    );


    fetch(
        "ajax/add_wishlist.php",
        {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        }
    )

    .then(function (response) {

        if (!response.ok) {

            throw new Error(
                "Network response failed."
            );

        }

        return response.json();

    })

    .then(function (data) {

        /*
        |--------------------------------------------------------------------------
        | LOGIN REQUIRED
        |--------------------------------------------------------------------------
        */

        if (
            data.login_required ||
            data.status === "login_required"
        ) {

            showWishlistMessage(
                "Please login to use wishlist.",
                "warning"
            );

            setTimeout(function () {

                window.location.href =
                    "index.php?login=required";

            }, 900);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        if (
            data.success === true ||
            data.status === "success"
        ) {

            if (action === "add") {

                setWishlistActive(
                    button,
                    true
                );

                showWishlistMessage(
                    data.message ||
                    "Added to wishlist.",
                    "success"
                );

            } else {

                setWishlistActive(
                    button,
                    false
                );

                showWishlistMessage(
                    data.message ||
                    "Removed from wishlist.",
                    "success"
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE COUNTER
            |--------------------------------------------------------------------------
            */

            if (
                data.count !== undefined
            ) {

                updateWishlistCounter(
                    data.count
                );

            } else {

                refreshWishlistCounter();

            }


            /*
            |--------------------------------------------------------------------------
            | REMOVE CARD WHEN ON WISHLIST PAGE
            |--------------------------------------------------------------------------
            */

            if (
                action === "remove"
            ) {

                const item =
                    button.closest(
                        ".wishlist-item, " +
                        ".wishlist-card, " +
                        "[data-wishlist-item]"
                    );

                if (item) {

                    item.classList.add(
                        "wishlist-removing"
                    );

                    setTimeout(function () {

                        item.remove();

                        checkWishlistEmpty();

                    }, 300);

                }

            }

        } else {

            showWishlistMessage(
                data.message ||
                "Unable to update wishlist.",
                "error"
            );

        }

    })

    .catch(function (error) {

        console.error(
            "Wishlist error:",
            error
        );

        showWishlistMessage(
            "Something went wrong. Please try again.",
            "error"
        );

    })

    .finally(function () {

        button.dataset.loading =
            "false";

        button.classList.remove(
            "wishlist-loading"
        );

    });

}


/*
|--------------------------------------------------------------------------
| REMOVE WISHLIST
|--------------------------------------------------------------------------
*/

function removeWishlist(
    button,
    wishlistId,
    productId
) {

    if (
        button.dataset.loading ===
        "true"
    ) {
        return;
    }


    if (!productId) {

        productId =
            button.dataset.productId;

    }


    if (!productId) {

        console.error(
            "Wishlist: product ID missing."
        );

        return;
    }


    button.dataset.loading =
        "true";


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


    fetch(
        "ajax/add_wishlist.php",
        {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        }
    )

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (
            data.success === true ||
            data.status === "success"
        ) {

            const item =
                button.closest(
                    ".wishlist-item, " +
                    ".wishlist-card, " +
                    "[data-wishlist-item]"
                );


            if (item) {

                item.classList.add(
                    "wishlist-removing"
                );

                setTimeout(function () {

                    item.remove();

                    checkWishlistEmpty();

                }, 300);

            }


            updateWishlistCounter(
                data.count
            );


            showWishlistMessage(
                data.message ||
                "Removed from wishlist.",
                "success"
            );

        } else {

            showWishlistMessage(
                data.message ||
                "Unable to remove wishlist item.",
                "error"
            );

        }

    })

    .catch(function (error) {

        console.error(
            "Remove wishlist error:",
            error
        );

        showWishlistMessage(
            "Something went wrong.",
            "error"
        );

    })

    .finally(function () {

        button.dataset.loading =
            "false";

    });

}


/*
|--------------------------------------------------------------------------
| SET WISHLIST ACTIVE
|--------------------------------------------------------------------------
*/

function setWishlistActive(
    button,
    active
) {

    if (active) {

        button.classList.add(
            "active"
        );

        button.classList.add(
            "in-wishlist"
        );

        button.setAttribute(
            "aria-pressed",
            "true"
        );

        button.setAttribute(
            "title",
            "Remove from wishlist"
        );


        /*
        | Change common icon styles
        */

        const icon =
            button.querySelector(
                ".wishlist-icon, i, span"
            );

        if (icon) {

            if (
                icon.dataset.wishlistIcon
            ) {

                icon.textContent =
                    icon.dataset.wishlistIcon;

            }

        }

    } else {

        button.classList.remove(
            "active"
        );

        button.classList.remove(
            "in-wishlist"
        );

        button.setAttribute(
            "aria-pressed",
            "false"
        );

        button.setAttribute(
            "title",
            "Add to wishlist"
        );

    }

}


/*
|--------------------------------------------------------------------------
| UPDATE WISHLIST COUNTER
|--------------------------------------------------------------------------
*/

function updateWishlistCounter(
    count
) {

    if (
        count === undefined ||
        count === null
    ) {
        return;
    }


    const counters =
        document.querySelectorAll(
            ".wishlist-count, " +
            ".wishlist-counter, " +
            "[data-wishlist-count]"
        );


    counters.forEach(function (counter) {

        counter.textContent =
            count;


        if (
            parseInt(count, 10) <= 0
        ) {

            counter.classList.add(
                "empty"
            );

        } else {

            counter.classList.remove(
                "empty"
            );

        }

    });

}


/*
|--------------------------------------------------------------------------
| REFRESH WISHLIST COUNTER
|--------------------------------------------------------------------------
*/

function refreshWishlistCounter() {

    fetch(
        "ajax/add_wishlist.php?action=count",
        {
            method: "GET",
            credentials: "same-origin"
        }
    )

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (
            data.count !== undefined
        ) {

            updateWishlistCounter(
                data.count
            );

        }

    })

    .catch(function (error) {

        console.error(
            "Wishlist counter error:",
            error
        );

    });

}


/*
|--------------------------------------------------------------------------
| CHECK EMPTY WISHLIST
|--------------------------------------------------------------------------
*/

function checkWishlistEmpty() {

    const items =
        document.querySelectorAll(
            ".wishlist-item, " +
            ".wishlist-card, " +
            "[data-wishlist-item]"
        );


    if (items.length > 0) {
        return;
    }


    const container =
        document.querySelector(
            ".wishlist-list, " +
            ".wishlist-grid, " +
            ".wishlist-container"
        );


    if (!container) {
        return;
    }


    const empty =
        document.createElement("div");

    empty.className =
        "wishlist-empty";

    empty.innerHTML = `
        <div class="wishlist-empty-icon">♡</div>
        <h3>Your wishlist is empty</h3>
        <p>Products you save will appear here.</p>
    `;


    container.innerHTML = "";

    container.appendChild(
        empty
    );

}


/*
|--------------------------------------------------------------------------
| WISHLIST MESSAGE
|--------------------------------------------------------------------------
*/

function showWishlistMessage(
    message,
    type = "success"
) {

    let container =
        document.querySelector(
            ".wishlist-message-container"
        );


    if (!container) {

        container =
            document.createElement("div");

        container.className =
            "wishlist-message-container";


        container.style.position =
            "fixed";

        container.style.top =
            "90px";

        container.style.right =
            "20px";

        container.style.zIndex =
            "99999";

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


    const messageElement =
        document.createElement("div");


    messageElement.className =
        "wishlist-message wishlist-message-" +
        type;


    messageElement.textContent =
        message;


    messageElement.style.padding =
        "12px 16px";

    messageElement.style.borderRadius =
        "12px";

    messageElement.style.fontSize =
        "13px";

    messageElement.style.fontWeight =
        "700";

    messageElement.style.background =
        "#0f172a";

    messageElement.style.color =
        "#f8fafc";

    messageElement.style.border =
        "1px solid rgba(56,189,248,.25)";

    messageElement.style.boxShadow =
        "0 15px 40px rgba(0,0,0,.25)";


    container.appendChild(
        messageElement
    );


    setTimeout(function () {

        messageElement.style.opacity =
            "0";

        messageElement.style.transform =
            "translateY(-5px)";

        messageElement.style.transition =
            "all .25s ease";


        setTimeout(function () {

            messageElement.remove();

        }, 250);

    }, 2500);

}


/*
|--------------------------------------------------------------------------
| INITIAL WISHLIST STATE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const buttons =
            document.querySelectorAll(
                ".wishlist-btn, " +
                ".add-wishlist, " +
                ".wishlist-button, " +
                "[data-wishlist]"
            );


        buttons.forEach(function (button) {

            const active =
                button.dataset.wishlisted ===
                "true";


            if (active) {

                setWishlistActive(
                    button,
                    true
                );

            }

        });

    }
);