/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CART JAVASCRIPT
|--------------------------------------------------------------------------
| Handles:
| - Add to cart
| - Update cart quantity
| - Remove cart item
| - Cart count
| - Cart subtotal
| - Grand total
| - AJAX requests
| - Loading states
| - Cart notifications
| - Guest user handling
|--------------------------------------------------------------------------
*/

"use strict";


/* ==============================================================
   CART CONFIGURATION
============================================================== */

const CartConfig = {

    addUrl: "ajax/add_cart.php",

    updateUrl: "ajax/update_cart.php",

    removeUrl: "ajax/remove_cart.php",

    cartPage: "cart.php",

    loginPage: "index.php",

    minQuantity: 1,

    maxQuantity: 999,

    currency: "RM"

};


/* ==============================================================
   CART STATE
============================================================== */

const CartState = {

    isLoading: false,

    items: {},

    count: 0,

    subtotal: 0,

    shipping: 0,

    total: 0

};


/* ==============================================================
   DOM READY
============================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        initCart();

    }
);


/* ==============================================================
   INITIALISE CART
============================================================== */

function initCart() {

    initAddToCartButtons();

    initQuantityControls();

    initRemoveButtons();

    initQuantityInputs();

    initCartPage();

    updateCartCountFromDOM();

}


/* ==============================================================
   ADD TO CART BUTTONS
============================================================== */

function initAddToCartButtons() {

    const buttons =
        document.querySelectorAll(
            ".add-cart-btn, .product-add-cart-btn, [data-add-cart]"
        );

    buttons.forEach(function (button) {

        if (
            button.dataset.cartInitialized === "true"
        ) {
            return;
        }

        button.dataset.cartInitialized = "true";

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const productId =
                    getProductIdFromButton(
                        button
                    );

                if (!productId) {

                    showCartToast(
                        "Product information is missing.",
                        "error"
                    );

                    return;

                }

                let quantity = 1;

                const quantityInput =
                    findRelatedQuantityInput(
                        button
                    );

                if (quantityInput) {

                    quantity =
                        parseInt(
                            quantityInput.value,
                            10
                        ) || 1;

                }

                addToCart(
                    productId,
                    quantity,
                    button
                );

            }
        );

    });

}


/* ==============================================================
   GET PRODUCT ID
============================================================== */

function getProductIdFromButton(button) {

    if (!button) {
        return null;
    }

    const possibleValues = [

        button.dataset.productId,

        button.getAttribute(
            "data-product"
        ),

        button.getAttribute(
            "data-id"
        )

    ];

    for (
        let i = 0;
        i < possibleValues.length;
        i++
    ) {

        if (
            possibleValues[i] &&
            !isNaN(
                parseInt(
                    possibleValues[i],
                    10
                )
            )
        ) {

            return parseInt(
                possibleValues[i],
                10
            );

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Try closest product card
    |--------------------------------------------------------------------------
    */

    const card =
        button.closest(
            ".product-card, .product-details, .product-info"
        );

    if (card) {

        const element =
            card.querySelector(
                "[data-product-id]"
            );

        if (element) {

            const id =
                element.dataset.productId;

            if (id) {

                return parseInt(
                    id,
                    10
                );

            }

        }

    }

    return null;

}


/* ==============================================================
   FIND RELATED QUANTITY INPUT
============================================================== */

function findRelatedQuantityInput(button) {

    if (!button) {
        return null;
    }

    const parent =
        button.closest(
            ".product-purchase-box, .product-card, .product-info"
        );

    if (!parent) {
        return null;
    }

    return parent.querySelector(
        ".quantity-input, [data-quantity-input]"
    );

}


/* ==============================================================
   ADD TO CART
============================================================== */

async function addToCart(
    productId,
    quantity = 1,
    button = null
) {

    if (CartState.isLoading) {
        return;
    }

    productId =
        parseInt(
            productId,
            10
        );

    quantity =
        parseInt(
            quantity,
            10
        );

    if (
        !productId ||
        productId <= 0
    ) {

        showCartToast(
            "Invalid product.",
            "error"
        );

        return;

    }

    quantity =
        clampQuantity(
            quantity
        );

    if (button) {

        setCartButtonLoading(
            button,
            true
        );

    }

    try {

        CartState.isLoading = true;

        const response =
            await sendCartRequest(
                CartConfig.addUrl,
                {
                    product_id: productId,
                    quantity: quantity
                }
            );

        if (
            response.redirect
        ) {

            window.location.href =
                response.redirect;

            return;

        }

        if (
            response.login_required
        ) {

            showCartToast(
                "Please login to add products to your cart.",
                "warning"
            );

            setTimeout(
                function () {

                    window.location.href =
                        response.login_url ||
                        CartConfig.loginPage;

                },
                900
            );

            return;

        }

        if (
            response.success
        ) {

            updateCartCount(
                response.cart_count
            );

            updateCartSummary(
                response
            );

            markProductAsAdded(
                button
            );

            showCartToast(
                response.message ||
                "Product added to cart.",
                "success"
            );

        } else {

            showCartToast(
                response.message ||
                "Unable to add product to cart.",
                "error"
            );

        }

    } catch (error) {

        console.error(
            "Add to cart error:",
            error
        );

        showCartToast(
            "Something went wrong. Please try again.",
            "error"
        );

    } finally {

        CartState.isLoading = false;

        if (button) {

            setCartButtonLoading(
                button,
                false
            );

        }

    }

}


/* ==============================================================
   CART QUANTITY CONTROLS
============================================================== */

function initQuantityControls() {

    const buttons =
        document.querySelectorAll(
            ".quantity-btn, [data-quantity-action]"
        );

    buttons.forEach(function (button) {

        if (
            button.dataset.quantityInitialized ===
            "true"
        ) {

            return;

        }

        button.dataset.quantityInitialized =
            "true";

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const action =
                    button.dataset.quantityAction ||
                    getQuantityAction(
                        button
                    );

                const wrapper =
                    button.closest(
                        ".quantity-control, .cart-quantity"
                    );

                if (!wrapper) {
                    return;
                }

                const input =
                    wrapper.querySelector(
                        ".quantity-input, [data-quantity-input]"
                    );

                if (!input) {
                    return;
                }

                let quantity =
                    parseInt(
                        input.value,
                        10
                    ) || 1;

                if (
                    action === "increase" ||
                    action === "plus"
                ) {

                    quantity++;

                } else if (
                    action === "decrease" ||
                    action === "minus"
                ) {

                    quantity--;

                }

                quantity =
                    clampQuantity(
                        quantity
                    );

                input.value =
                    quantity;

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

    });

}


/* ==============================================================
   GET QUANTITY ACTION
============================================================== */

function getQuantityAction(button) {

    if (!button) {
        return "";
    }

    if (
        button.classList.contains(
            "quantity-plus"
        ) ||
        button.classList.contains(
            "plus"
        )
    ) {

        return "increase";

    }

    if (
        button.classList.contains(
            "quantity-minus"
        ) ||
        button.classList.contains(
            "minus"
        )
    ) {

        return "decrease";

    }

    const icon =
        button.querySelector(
            "i"
        );

    if (icon) {

        if (
            icon.classList.contains(
                "fa-plus"
            )
        ) {

            return "increase";

        }

        if (
            icon.classList.contains(
                "fa-minus"
            )
        ) {

            return "decrease";

        }

    }

    return "";

}


/* ==============================================================
   QUANTITY INPUT
============================================================== */

function initQuantityInputs() {

    const inputs =
        document.querySelectorAll(
            ".quantity-input, [data-quantity-input]"
        );

    inputs.forEach(function (input) {

        if (
            input.dataset.quantityInputInitialized ===
            "true"
        ) {

            return;

        }

        input.dataset.quantityInputInitialized =
            "true";

        input.addEventListener(
            "input",
            function () {

                input.value =
                    input.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

        input.addEventListener(
            "change",
            function () {

                let quantity =
                    parseInt(
                        input.value,
                        10
                    ) || 1;

                quantity =
                    clampQuantity(
                        quantity
                    );

                input.value =
                    quantity;

                const cartItem =
                    input.closest(
                        "[data-cart-item], .cart-item"
                    );

                if (!cartItem) {
                    return;
                }

                const productId =
                    getProductIdFromCartItem(
                        cartItem
                    );

                if (productId) {

                    updateCartItem(
                        productId,
                        quantity,
                        cartItem
                    );

                }

            }
        );

    });

}


/* ==============================================================
   CLAMP QUANTITY
============================================================== */

function clampQuantity(quantity) {

    quantity =
        parseInt(
            quantity,
            10
        );

    if (
        isNaN(quantity)
    ) {

        quantity =
            CartConfig.minQuantity;

    }

    return Math.max(
        CartConfig.minQuantity,
        Math.min(
            CartConfig.maxQuantity,
            quantity
        )
    );

}


/* ==============================================================
   GET PRODUCT ID FROM CART ITEM
============================================================== */

function getProductIdFromCartItem(
    cartItem
) {

    if (!cartItem) {
        return null;
    }

    const values = [

        cartItem.dataset.productId,

        cartItem.getAttribute(
            "data-product-id"
        ),

        cartItem.querySelector(
            "[data-product-id]"
        )?.dataset.productId

    ];

    for (
        let i = 0;
        i < values.length;
        i++
    ) {

        if (
            values[i] &&
            !isNaN(
                parseInt(
                    values[i],
                    10
                )
            )
        ) {

            return parseInt(
                values[i],
                10
            );

        }

    }

    return null;

}


/* ==============================================================
   UPDATE CART ITEM
============================================================== */

async function updateCartItem(
    productId,
    quantity,
    cartItem = null
) {

    if (
        !productId
    ) {
        return;
    }

    quantity =
        clampQuantity(
            quantity
        );

    if (cartItem) {

        cartItem.classList.add(
            "cart-item-updating"
        );

    }

    try {

        const response =
            await sendCartRequest(
                CartConfig.updateUrl,
                {
                    product_id:
                        productId,

                    quantity:
                        quantity
                }
            );

        if (
            response.login_required
        ) {

            redirectToLogin();

            return;

        }

        if (
            response.success
        ) {

            updateCartCount(
                response.cart_count
            );

            updateCartSummary(
                response
            );

            updateCartItemPrice(
                cartItem,
                response
            );

            showCartToast(
                response.message ||
                "Cart updated.",
                "success"
            );

            /*
            |--------------------------------------------------------------------------
            | If backend returns empty cart
            |--------------------------------------------------------------------------
            */

            if (
                response.cart_empty ||
                response.cart_count === 0
            ) {

                showEmptyCart();

            }

        } else {

            showCartToast(
                response.message ||
                "Unable to update cart.",
                "error"
            );

        }

    } catch (error) {

        console.error(
            "Update cart error:",
            error
        );

        showCartToast(
            "Unable to update cart.",
            "error"
        );

    } finally {

        if (cartItem) {

            cartItem.classList.remove(
                "cart-item-updating"
            );

        }

    }

}


/* ==============================================================
   UPDATE CART ITEM PRICE
============================================================== */

function updateCartItemPrice(
    cartItem,
    response
) {

    if (
        !cartItem ||
        !response
    ) {

        return;

    }

    if (
        response.item_subtotal !==
        undefined
    ) {

        const subtotal =
            cartItem.querySelector(
                ".cart-item-subtotal, [data-item-subtotal]"
            );

        if (subtotal) {

            subtotal.textContent =
                formatCurrency(
                    response.item_subtotal
                );

        }

    }

}


/* ==============================================================
   REMOVE BUTTONS
============================================================== */

function initRemoveButtons() {

    const buttons =
        document.querySelectorAll(
            ".remove-cart-item, [data-remove-cart]"
        );

    buttons.forEach(function (button) {

        if (
            button.dataset.removeInitialized ===
            "true"
        ) {

            return;

        }

        button.dataset.removeInitialized =
            "true";

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const cartItem =
                    button.closest(
                        "[data-cart-item], .cart-item"
                    );

                const productId =
                    getProductIdFromCartItem(
                        cartItem
                    ) ||
                    button.dataset.productId;

                if (!productId) {

                    showCartToast(
                        "Unable to identify product.",
                        "error"
                    );

                    return;

                }

                removeCartItem(
                    productId,
                    cartItem,
                    button
                );

            }
        );

    });

}


/* ==============================================================
   REMOVE CART ITEM
============================================================== */

async function removeCartItem(
    productId,
    cartItem = null,
    button = null
) {

    const confirmed =
        await confirmCartRemoval();

    if (!confirmed) {
        return;
    }

    if (cartItem) {

        cartItem.classList.add(
            "cart-item-removing"
        );

    }

    if (button) {

        button.disabled = true;

    }

    try {

        const response =
            await sendCartRequest(
                CartConfig.removeUrl,
                {
                    product_id:
                        productId
                }
            );

        if (
            response.login_required
        ) {

            redirectToLogin();

            return;

        }

        if (
            response.success
        ) {

            if (cartItem) {

                removeCartItemFromDOM(
                    cartItem
                );

            }

            updateCartCount(
                response.cart_count
            );

            updateCartSummary(
                response
            );

            showCartToast(
                response.message ||
                "Item removed from cart.",
                "success"
            );

            if (
                response.cart_empty ||
                response.cart_count === 0
            ) {

                showEmptyCart();

            }

        } else {

            if (cartItem) {

                cartItem.classList.remove(
                    "cart-item-removing"
                );

            }

            showCartToast(
                response.message ||
                "Unable to remove item.",
                "error"
            );

        }

    } catch (error) {

        console.error(
            "Remove cart error:",
            error
        );

        if (cartItem) {

            cartItem.classList.remove(
                "cart-item-removing"
            );

        }

        showCartToast(
            "Unable to remove item.",
            "error"
        );

    } finally {

        if (button) {

            button.disabled = false;

        }

    }

}


/* ==============================================================
   CONFIRM REMOVE
============================================================== */

function confirmCartRemoval() {

    /*
    |--------------------------------------------------------------------------
    | If browser confirmation is enough
    |--------------------------------------------------------------------------
    */

    return Promise.resolve(
        window.confirm(
            "Remove this product from your cart?"
        )
    );

}


/* ==============================================================
   REMOVE ITEM FROM DOM
============================================================== */

function removeCartItemFromDOM(
    cartItem
) {

    if (!cartItem) {
        return;
    }

    cartItem.style.opacity =
        "0";

    cartItem.style.transform =
        "translateX(30px)";

    cartItem.style.transition =
        "opacity 0.25s ease, transform 0.25s ease";

    setTimeout(
        function () {

            cartItem.remove();

        },
        250
    );

}


/* ==============================================================
   UPDATE CART COUNT
============================================================== */

function updateCartCount(
    count
) {

    count =
        parseInt(
            count,
            10
        ) || 0;

    CartState.count =
        count;

    const elements =
        document.querySelectorAll(
            ".cart-count, [data-cart-count]"
        );

    elements.forEach(function (element) {

        element.textContent =
            count;

        if (count > 0) {

            element.classList.add(
                "has-items"
            );

            element.style.display =
                "flex";

        } else {

            element.classList.remove(
                "has-items"
            );

            /*
            |--------------------------------------------------------------------------
            | Keep badge hidden when cart is empty
            |--------------------------------------------------------------------------
            */

            element.style.display =
                "none";

        }

    });

}


/* ==============================================================
   GET CART COUNT FROM DOM
============================================================== */

function updateCartCountFromDOM() {

    const element =
        document.querySelector(
            ".cart-count, [data-cart-count]"
        );

    if (!element) {
        return;
    }

    const count =
        parseInt(
            element.textContent,
            10
        ) || 0;

    updateCartCount(
        count
    );

}


/* ==============================================================
   CART SUMMARY
============================================================== */

function updateCartSummary(
    response
) {

    if (!response) {
        return;
    }

    if (
        response.subtotal !==
        undefined
    ) {

        CartState.subtotal =
            parseFloat(
                response.subtotal
            ) || 0;

    }

    if (
        response.shipping !==
        undefined
    ) {

        CartState.shipping =
            parseFloat(
                response.shipping
            ) || 0;

    }

    if (
        response.total !==
        undefined
    ) {

        CartState.total =
            parseFloat(
                response.total
            ) || 0;

    }

    updateCartSummaryDOM();

}


/* ==============================================================
   UPDATE CART SUMMARY DOM
============================================================== */

function updateCartSummaryDOM() {

    const subtotalElements =
        document.querySelectorAll(
            ".cart-subtotal, [data-cart-subtotal]"
        );

    subtotalElements.forEach(function (element) {

        element.textContent =
            formatCurrency(
                CartState.subtotal
            );

    });


    const shippingElements =
        document.querySelectorAll(
            ".cart-shipping, [data-cart-shipping]"
        );

    shippingElements.forEach(function (element) {

        element.textContent =
            CartState.shipping > 0
                ? formatCurrency(
                    CartState.shipping
                )
                : "FREE";

    });


    const totalElements =
        document.querySelectorAll(
            ".cart-total, [data-cart-total]"
        );

    totalElements.forEach(function (element) {

        element.textContent =
            formatCurrency(
                CartState.total
            );

    });

}


/* ==============================================================
   EMPTY CART
============================================================== */

function showEmptyCart() {

    const cartContainer =
        document.querySelector(
            ".cart-items, [data-cart-items]"
        );

    if (!cartContainer) {
        return;
    }

    const existingItems =
        cartContainer.querySelectorAll(
            "[data-cart-item], .cart-item"
        );

    if (
        existingItems.length > 0
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Don't duplicate empty state
    |--------------------------------------------------------------------------
    */

    if (
        cartContainer.querySelector(
            ".cart-empty"
        )
    ) {

        return;

    }

    const empty =
        document.createElement(
            "div"
        );

    empty.className =
        "cart-empty";

    empty.innerHTML = `
        <div class="cart-empty-icon">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>

        <h3>Your cart is empty</h3>

        <p>
            Looks like you haven't added
            anything to your cart yet.
        </p>

        <a href="catalog.php" class="cart-empty-btn">
            <i class="fa-solid fa-store"></i>
            Explore Products
        </a>
    `;

    cartContainer.appendChild(
        empty
    );

}


/* ==============================================================
   SEND CART AJAX REQUEST
============================================================== */

async function sendCartRequest(
    url,
    data
) {

    const formData =
        new FormData();

    Object.keys(data).forEach(
        function (key) {

            formData.append(
                key,
                data[key]
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    const csrfToken =
        getCSRFToken();

    if (csrfToken) {

        formData.append(
            "csrf_token",
            csrfToken
        );

    }


    const response =
        await fetch(
            url,
            {
                method: "POST",

                body: formData,

                headers: {

                    "X-Requested-With":
                        "XMLHttpRequest",

                    "Accept":
                        "application/json"

                },

                credentials:
                    "same-origin"
            }
        );


    /*
    |--------------------------------------------------------------------------
    | HTTP ERROR
    |--------------------------------------------------------------------------
    */

    if (!response.ok) {

        throw new Error(
            `HTTP error: ${response.status}`
        );

    }


    /*
    |--------------------------------------------------------------------------
    | JSON RESPONSE
    |--------------------------------------------------------------------------
    */

    const contentType =
        response.headers.get(
            "content-type"
        );


    if (
        contentType &&
        contentType.includes(
            "application/json"
        )
    ) {

        return await response.json();

    }


    /*
    |--------------------------------------------------------------------------
    | Unexpected response
    |--------------------------------------------------------------------------
    */

    const text =
        await response.text();

    console.error(
        "Unexpected server response:",
        text
    );

    throw new Error(
        "Server returned an invalid response."
    );

}


/* ==============================================================
   GET CSRF TOKEN
============================================================== */

function getCSRFToken() {

    const meta =
        document.querySelector(
            'meta[name="csrf-token"]'
        );

    if (meta) {

        return meta.getAttribute(
            "content"
        );

    }

    const input =
        document.querySelector(
            'input[name="csrf_token"]'
        );

    if (input) {

        return input.value;

    }

    return "";

}


/* ==============================================================
   CART BUTTON LOADING
============================================================== */

function setCartButtonLoading(
    button,
    loading
) {

    if (!button) {
        return;
    }

    if (loading) {

        button.disabled =
            true;

        button.classList.add(
            "loading"
        );

        button.dataset.originalHTML =
            button.innerHTML;

        button.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
        `;

    } else {

        button.disabled =
            false;

        button.classList.remove(
            "loading"
        );

        if (
            button.dataset.originalHTML
        ) {

            button.innerHTML =
                button.dataset.originalHTML;

            delete button.dataset.originalHTML;

        }

    }

}


/* ==============================================================
   MARK PRODUCT AS ADDED
============================================================== */

function markProductAsAdded(
    button
) {

    if (!button) {
        return;
    }

    button.classList.add(
        "added"
    );

    const original =
        button.innerHTML;

    button.dataset.addedHTML =
        original;

    button.innerHTML = `
        <i class="fa-solid fa-check"></i>
    `;

    setTimeout(
        function () {

            if (
                button.dataset.addedHTML
            ) {

                button.innerHTML =
                    button.dataset.addedHTML;

                delete button.dataset.addedHTML;

            }

            button.classList.remove(
                "added"
            );

        },
        1300
    );

}


/* ==============================================================
   TOAST NOTIFICATION
============================================================== */

function showCartToast(
    message,
    type = "success"
) {

    let container =
        document.querySelector(
            ".hochipohub-toast-container"
        );

    if (!container) {

        container =
            document.createElement(
                "div"
            );

        container.className =
            "hochipohub-toast-container";

        document.body.appendChild(
            container
        );

    }

    const toast =
        document.createElement(
            "div"
        );

    toast.className =
        `hochipohub-toast toast-${type}`;


    let icon =
        "fa-circle-check";


    if (type === "error") {

        icon =
            "fa-circle-xmark";

    } else if (type === "warning") {

        icon =
            "fa-triangle-exclamation";

    } else if (type === "info") {

        icon =
            "fa-circle-info";

    }


    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fa-solid ${icon}"></i>
        </div>

        <div class="toast-message">
            ${escapeHTML(message)}
        </div>

        <button
            type="button"
            class="toast-close"
            aria-label="Close notification"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;


    container.appendChild(
        toast
    );


    requestAnimationFrame(
        function () {

            toast.classList.add(
                "show"
            );

        }
    );


    const close =
        toast.querySelector(
            ".toast-close"
        );


    if (close) {

        close.addEventListener(
            "click",
            function () {

                removeToast(
                    toast
                );

            }
        );

    }


    setTimeout(
        function () {

            removeToast(
                toast
            );

        },
        3500
    );

}


/* ==============================================================
   REMOVE TOAST
============================================================== */

function removeToast(
    toast
) {

    if (!toast) {
        return;
    }

    toast.classList.remove(
        "show"
    );

    setTimeout(
        function () {

            if (
                toast.parentElement
            ) {

                toast.remove();

            }

        },
        250
    );

}


/* ==============================================================
   FORMAT CURRENCY
============================================================== */

function formatCurrency(
    amount
) {

    amount =
        parseFloat(
            amount
        ) || 0;

    return (
        CartConfig.currency +
        " " +
        amount.toFixed(2)
    );

}


/* ==============================================================
   ESCAPE HTML
============================================================== */

function escapeHTML(
    value
) {

    const div =
        document.createElement(
            "div"
        );

    div.textContent =
        String(
            value ?? ""
        );

    return div.innerHTML;

}


/* ==============================================================
   REDIRECT TO LOGIN
============================================================== */

function redirectToLogin() {

    const currentPage =
        window.location.href;

    const loginUrl =
        `${CartConfig.loginPage}?login=1&redirect=${encodeURIComponent(currentPage)}`;

    window.location.href =
        loginUrl;

}


/* ==============================================================
   CART PAGE INITIALISATION
============================================================== */

function initCartPage() {

    const cartPage =
        document.querySelector(
            ".cart-page, [data-cart-page]"
        );

    if (!cartPage) {
        return;
    }

    calculateLocalCartTotals();

}


/* ==============================================================
   LOCAL CART TOTAL CALCULATION
============================================================== */

function calculateLocalCartTotals() {

    const items =
        document.querySelectorAll(
            "[data-cart-item], .cart-item"
        );

    if (
        items.length === 0
    ) {

        return;

    }

    let subtotal = 0;

    items.forEach(function (item) {

        const priceElement =
            item.querySelector(
                "[data-item-price], .cart-item-price"
            );

        const quantityElement =
            item.querySelector(
                ".quantity-input, [data-quantity-input]"
            );

        if (
            !priceElement ||
            !quantityElement
        ) {

            return;

        }

        const price =
            parseFloat(
                priceElement.dataset.itemPrice ||
                priceElement.textContent.replace(
                    /[^0-9.]/g,
                    ""
                )
            ) || 0;

        const quantity =
            parseInt(
                quantityElement.value,
                10
            ) || 1;

        const itemSubtotal =
            price * quantity;

        subtotal +=
            itemSubtotal;

        const subtotalElement =
            item.querySelector(
                ".cart-item-subtotal, [data-item-subtotal]"
            );

        if (subtotalElement) {

            subtotalElement.textContent =
                formatCurrency(
                    itemSubtotal
                );

        }

    });


    CartState.subtotal =
        subtotal;

    updateCartSummaryDOM();

}


/* ==============================================================
   CART CLEAR BUTTON
============================================================== */

document.addEventListener(
    "click",
    function (event) {

        const button =
            event.target.closest(
                "[data-clear-cart], .clear-cart"
            );

        if (!button) {
            return;
        }

        event.preventDefault();

        clearCart();

    }
);


/* ==============================================================
   CLEAR CART
============================================================== */

async function clearCart() {

    const confirmed =
        window.confirm(
            "Are you sure you want to remove all items from your cart?"
        );

    if (!confirmed) {
        return;
    }

    const items =
        document.querySelectorAll(
            "[data-cart-item], .cart-item"
        );

    for (
        const item of items
    ) {

        const productId =
            getProductIdFromCartItem(
                item
            );

        if (!productId) {
            continue;
        }

        try {

            await sendCartRequest(
                CartConfig.removeUrl,
                {
                    product_id:
                        productId
                }
            );

            item.remove();

        } catch (error) {

            console.error(
                "Clear cart error:",
                error
            );

        }

    }

    updateCartCount(
        0
    );

    CartState.subtotal =
        0;

    CartState.shipping =
        0;

    CartState.total =
        0;

    updateCartSummaryDOM();

    showEmptyCart();

    showCartToast(
        "Your cart has been cleared.",
        "success"
    );

}


/* ==============================================================
   CART LINK NAVIGATION
============================================================== */

document.addEventListener(
    "click",
    function (event) {

        const link =
            event.target.closest(
                "[data-cart-link]"
            );

        if (!link) {
            return;
        }

        if (
            CartState.count <= 0
        ) {

            /*
            |--------------------------------------------------------------------------
            | Allow normal navigation even if empty.
            |--------------------------------------------------------------------------
            */

            return;

        }

    }
);


/* ==============================================================
   AJAX ADD CART - EVENT DELEGATION
============================================================== */

document.addEventListener(
    "click",
    function (event) {

        const button =
            event.target.closest(
                "[data-add-cart]"
            );

        if (!button) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Already handled by initAddToCartButtons
        |--------------------------------------------------------------------------
        */

        if (
            button.dataset.cartInitialized ===
            "true"
        ) {

            return;

        }

        event.preventDefault();

        const productId =
            button.dataset.productId;

        if (!productId) {

            showCartToast(
                "Product information is missing.",
                "error"
            );

            return;

        }

        addToCart(
            productId,
            1,
            button
        );

    }
);


/* ==============================================================
   CART API - MANUAL ACCESS
============================================================== */

window.HochipoCart = {

    add: function (
        productId,
        quantity = 1
    ) {

        return addToCart(
            productId,
            quantity
        );

    },

    update: function (
        productId,
        quantity
    ) {

        return updateCartItem(
            productId,
            quantity
        );

    },

    remove: function (
        productId
    ) {

        return removeCartItem(
            productId
        );

    },

    clear: function () {

        return clearCart();

    },

    count: function () {

        return CartState.count;

    },

    state: CartState

};


/* ==============================================================
   CART TOAST CSS
============================================================== */

(function injectCartToastCSS() {

    if (
        document.getElementById(
            "hochipohub-cart-toast-css"
        )
    ) {

        return;

    }

    const style =
        document.createElement(
            "style"
        );

    style.id =
        "hochipohub-cart-toast-css";

    style.textContent = `

        .hochipohub-toast-container {
            position: fixed;
            top: 22px;
            right: 22px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(360px, calc(100vw - 30px));
            pointer-events: none;
        }

        .hochipohub-toast {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 13px 14px;
            border: 1px solid rgba(20, 100, 216, 0.12);
            border-radius: 14px;
            background: #ffffff;
            color: #10213f;
            box-shadow:
                0 15px 40px rgba(4, 30, 70, 0.16);
            opacity: 0;
            transform: translateY(-12px) scale(0.97);
            transition:
                opacity 0.25s ease,
                transform 0.25s ease;
            pointer-events: auto;
        }

        .hochipohub-toast.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .toast-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eaf5ff;
            color: #1464d8;
        }

        .toast-message {
            flex: 1;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.45;
        }

        .toast-close {
            width: 27px;
            height: 27px;
            flex-shrink: 0;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #8492a8;
            cursor: pointer;
        }

        .toast-close:hover {
            background: #f2f6fb;
            color: #10213f;
        }

        .toast-error .toast-icon {
            background: #fff0f3;
            color: #e0445c;
        }

        .toast-warning .toast-icon {
            background: #fff8e8;
            color: #c48112;
        }

        .toast-info .toast-icon {
            background: #edf5ff;
            color: #1464d8;
        }

        .cart-item-updating {
            opacity: 0.65;
            pointer-events: none;
        }

        .cart-item-removing {
            opacity: 0.55;
            pointer-events: none;
        }

        .cart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            padding: 35px 20px;
            text-align: center;
        }

        .cart-empty-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            border-radius: 22px;
            background:
                linear-gradient(
                    135deg,
                    #eaf5ff,
                    #f0edff
                );
            color: #1464d8;
            font-size: 25px;
        }

        .cart-empty h3 {
            margin: 0 0 7px;
            color: #10213f;
            font-size: 18px;
            font-weight: 850;
        }

        .cart-empty p {
            max-width: 360px;
            margin: 0 0 17px;
            color: #71819a;
            font-size: 10px;
            line-height: 1.6;
        }

        .cart-empty-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 40px;
            padding: 0 15px;
            border-radius: 10px;
            background:
                linear-gradient(
                    110deg,
                    #06245a,
                    #1464d8,
                    #725cff
                );
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            text-decoration: none;
            box-shadow:
                0 8px 20px rgba(20, 100, 216, 0.2);
        }

        .cart-empty-btn:hover {
            transform: translateY(-1px);
        }

        @media (max-width: 600px) {

            .hochipohub-toast-container {
                top: 12px;
                right: 12px;
                left: 12px;
                width: auto;
            }

        }

    `;

    document.head.appendChild(
        style
    );

})();