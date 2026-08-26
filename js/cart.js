/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CART / MARKETPLACE JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/cart.js
|--------------------------------------------------------------------------
|
| Handles:
| - Catalog Add to Cart
| - Catalog Add to Wishlist
| - Cart quantity update
| - Remove cart item
| - Cart total calculation
| - AJAX requests
| - Cart / Wishlist navigation badges
| - Toast notifications
|
|--------------------------------------------------------------------------
*/

'use strict';


/*
|--------------------------------------------------------------------------
| DOM READY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        initMarketplaceActions();

        initCart();

    }
);


/*
|--------------------------------------------------------------------------
| MARKETPLACE ACTIONS
|--------------------------------------------------------------------------
*/

function initMarketplaceActions() {

    bindAddToCartButtons();

    bindWishlistButtons();

}


/*
|--------------------------------------------------------------------------
| ADD TO CART BUTTONS
|--------------------------------------------------------------------------
*/

function bindAddToCartButtons() {

    const buttons =
        document.querySelectorAll(
            '.add-cart-btn, [data-add-cart]'
        );


    buttons.forEach(
        function (button) {

            if (
                button.dataset.cartBound === 'true'
            ) {
                return;
            }


            button.dataset.cartBound =
                'true';


            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    const productId =
                        button.dataset.productId;


                    const csrfToken =
                        getCsrfToken(
                            button
                        );


                    if (!productId) {

                        showMarketplaceMessage(
                            'Product ID not found.',
                            'error'
                        );

                        return;
                    }


                    if (!csrfToken) {

                        showMarketplaceMessage(
                            'Security token not found. Please refresh the page.',
                            'error'
                        );

                        return;
                    }


                    addProductToCart(
                        button,
                        productId,
                        csrfToken
                    );

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| ADD PRODUCT TO CART
|--------------------------------------------------------------------------
*/

function addProductToCart(
    button,
    productId,
    csrfToken
) {

    const originalHtml =
        button.innerHTML;


    setMarketplaceButtonLoading(
        button,
        true,
        'Adding...'
    );


    const formData =
        new FormData();


    formData.append(
        'product_id',
        productId
    );


    formData.append(
        'quantity',
        '1'
    );


    formData.append(
        'csrf_token',
        csrfToken
    );


    fetch(
        getAjaxUrl(
            'add_cart.php'
        ),
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With':
                    'XMLHttpRequest'
            }
        }
    )
    .then(
        async function (response) {

            let data;


            try {

                data =
                    await response.json();

            } catch (error) {

                throw new Error(
                    'Invalid server response.'
                );

            }


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Unable to add product to cart.'
                );

            }


            return data;

        }
    )
    .then(
        function (data) {

            if (
                data.success !== true &&
                data.status !== 'success'
            ) {

                throw new Error(
                    data.message ||
                    'Unable to add product to cart.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS MESSAGE
            |--------------------------------------------------------------------------
            */

            showMarketplaceMessage(
                data.message ||
                'Product added to cart.',
                'success'
            );


            /*
            |--------------------------------------------------------------------------
            | CART COUNT
            |--------------------------------------------------------------------------
            */

            if (
                data.cart_count !== undefined
            ) {

                updateCartCount(
                    data.cart_count
                );


                updateNavigationBadge(
                    'cart',
                    data.cart_count
                );

            }


            /*
            |--------------------------------------------------------------------------
            | BUTTON STATE
            |--------------------------------------------------------------------------
            */

            button.classList.add(
                'marketplace-added'
            );


            button.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Added
            `;


            setTimeout(
                function () {

                    button.innerHTML =
                        originalHtml;


                    button.classList.remove(
                        'marketplace-added'
                    );

                },
                1600
            );

        }
    )
    .catch(
        function (error) {

            console.error(
                'Add cart error:',
                error
            );


            showMarketplaceMessage(
                error.message ||
                'Something went wrong while adding the product.',
                'error'
            );


            button.innerHTML =
                originalHtml;

        }
    )
    .finally(
        function () {

            setMarketplaceButtonLoading(
                button,
                false
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| WISHLIST BUTTONS
|--------------------------------------------------------------------------
*/

function bindWishlistButtons() {

    const buttons =
        document.querySelectorAll(
            '.wishlist-btn, [data-add-wishlist]'
        );


    buttons.forEach(
        function (button) {

            if (
                button.dataset.wishlistBound ===
                'true'
            ) {
                return;
            }


            button.dataset.wishlistBound =
                'true';


            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    const productId =
                        button.dataset.productId;


                    const csrfToken =
                        getCsrfToken(
                            button
                        );


                    if (!productId) {

                        showMarketplaceMessage(
                            'Product ID not found.',
                            'error'
                        );

                        return;
                    }


                    if (!csrfToken) {

                        showMarketplaceMessage(
                            'Security token not found. Please refresh the page.',
                            'error'
                        );

                        return;
                    }


                    addProductToWishlist(
                        button,
                        productId,
                        csrfToken
                    );

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| ADD PRODUCT TO WISHLIST
|--------------------------------------------------------------------------
*/

function addProductToWishlist(
    button,
    productId,
    csrfToken
) {

    const originalHtml =
        button.innerHTML;


    setMarketplaceButtonLoading(
        button,
        true
    );


    const formData =
        new FormData();


    formData.append(
        'product_id',
        productId
    );


    formData.append(
        'csrf_token',
        csrfToken
    );


    fetch(
        getAjaxUrl(
            'add_wishlist.php'
        ),
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With':
                    'XMLHttpRequest'
            }
        }
    )
    .then(
        async function (response) {

            let data;


            try {

                data =
                    await response.json();

            } catch (error) {

                throw new Error(
                    'Invalid server response.'
                );

            }


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Unable to add product to wishlist.'
                );

            }


            return data;

        }
    )
    .then(
        function (data) {

            if (
                data.success !== true &&
                data.status !== 'success'
            ) {

                throw new Error(
                    data.message ||
                    'Unable to add product to wishlist.'
                );

            }


            showMarketplaceMessage(
                data.message ||
                'Product added to wishlist.',
                data.already_exists
                    ? 'info'
                    : 'success'
            );


            /*
            |--------------------------------------------------------------------------
            | UPDATE WISHLIST COUNT
            |--------------------------------------------------------------------------
            */

            if (
                data.wishlist_count !==
                undefined
            ) {

                updateWishlistCount(
                    data.wishlist_count
                );


                updateNavigationBadge(
                    'wishlist',
                    data.wishlist_count
                );

            }


            /*
            |--------------------------------------------------------------------------
            | HEART ACTIVE
            |--------------------------------------------------------------------------
            */

            button.classList.add(
                'wishlist-active'
            );


            button.innerHTML =
                '<i class="bi bi-heart-fill"></i>';


            button.title =
                'Saved to wishlist';


            button.setAttribute(
                'aria-label',
                'Saved to wishlist'
            );


            /*
            |--------------------------------------------------------------------------
            | IF ALREADY EXISTS DON'T RESET HEART
            |--------------------------------------------------------------------------
            */

            if (!data.already_exists) {

                setTimeout(
                    function () {

                        button.classList.add(
                            'wishlist-active'
                        );

                    },
                    100
                );

            }

        }
    )
    .catch(
        function (error) {

            console.error(
                'Wishlist error:',
                error
            );


            showMarketplaceMessage(
                error.message ||
                'Something went wrong while adding to wishlist.',
                'error'
            );


            button.innerHTML =
                originalHtml;

        }
    )
    .finally(
        function () {

            setMarketplaceButtonLoading(
                button,
                false
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| GET CSRF TOKEN
|--------------------------------------------------------------------------
*/

function getCsrfToken(
    element
) {

    /*
    |--------------------------------------------------------------------------
    | BUTTON DATA
    |--------------------------------------------------------------------------
    */

    if (
        element &&
        element.dataset.csrfToken
    ) {

        return element.dataset.csrfToken;

    }


    /*
    |--------------------------------------------------------------------------
    | PAGE META
    |--------------------------------------------------------------------------
    */

    const meta =
        document.querySelector(
            'meta[name="csrf-token"]'
        );


    if (
        meta &&
        meta.content
    ) {

        return meta.content;

    }


    /*
    |--------------------------------------------------------------------------
    | HIDDEN INPUT FALLBACK
    |--------------------------------------------------------------------------
    */

    const input =
        document.querySelector(
            'input[name="csrf_token"]'
        );


    if (input) {

        return input.value;

    }


    return '';

}


/*
|--------------------------------------------------------------------------
| BUTTON LOADING
|--------------------------------------------------------------------------
*/

function setMarketplaceButtonLoading(
    button,
    loading,
    text
) {

    if (!button) {
        return;
    }


    if (loading) {

        button.dataset.wasDisabled =
            button.disabled
                ? 'true'
                : 'false';


        button.disabled =
            true;


        button.classList.add(
            'marketplace-button-loading'
        );


        if (text) {

            button.innerHTML = `
                <span class="marketplace-spinner"></span>
                ${text}
            `;

        }

    } else {

        if (
            button.dataset.wasDisabled !==
            'true'
        ) {

            button.disabled =
                false;

        }


        button.classList.remove(
            'marketplace-button-loading'
        );

    }

}


/*
|--------------------------------------------------------------------------
| UPDATE NAVIGATION BADGE
|--------------------------------------------------------------------------
*/

function updateNavigationBadge(
    type,
    count
) {

    count =
        parseInt(
            count,
            10
        ) || 0;


    let selector = '';


    if (type === 'cart') {

        selector =
            'a[href$="cart.php"]';

    } else if (
        type === 'wishlist'
    ) {

        selector =
            'a[href$="wishlist.php"]';

    }


    if (!selector) {
        return;
    }


    const links =
        document.querySelectorAll(
            selector
        );


    links.forEach(
        function (link) {

            let badge =
                link.querySelector(
                    '.sidebar-badge'
                );


            if (count <= 0) {

                if (badge) {
                    badge.remove();
                }

                return;
            }


            if (!badge) {

                badge =
                    document.createElement(
                        'span'
                    );


                badge.className =
                    'sidebar-badge';


                link.appendChild(
                    badge
                );

            }


            badge.textContent =
                count > 99
                    ? '99+'
                    : count;

        }
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE WISHLIST COUNT
|--------------------------------------------------------------------------
*/

function updateWishlistCount(
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
            '[data-wishlist-count], .wishlist-count'
        )
        .forEach(
            function (element) {

                element.textContent =
                    count;

            }
        );

}


/*
|--------------------------------------------------------------------------
| INITIALIZE CART PAGE
|--------------------------------------------------------------------------
*/

function initCart() {

    bindQuantityButtons();

    bindRemoveButtons();

    bindQuantityInputs();

    bindCheckoutButton();

    calculateCartTotals();

}


/*
|--------------------------------------------------------------------------
| GET CART CONTAINER
|--------------------------------------------------------------------------
*/

function getCartContainer() {

    return (
        document.querySelector(
            '.hh-cart-container'
        ) ||
        document.querySelector(
            '.cart-container'
        ) ||
        document.querySelector(
            '.cart-page'
        ) ||
        document.querySelector(
            '[data-cart-container]'
        )
    );

}


/*
|--------------------------------------------------------------------------
| QUANTITY BUTTONS
|--------------------------------------------------------------------------
*/

function bindQuantityButtons() {

    const decreaseButtons =
        document.querySelectorAll(
            '[data-cart-decrease], .cart-qty-decrease'
        );


    const increaseButtons =
        document.querySelectorAll(
            '[data-cart-increase], .cart-qty-increase'
        );


    decreaseButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    const cartItem =
                        button.closest(
                            '[data-cart-item], .cart-item, .hh-cart-product'
                        );


                    if (!cartItem) {
                        return;
                    }


                    const input =
                        cartItem.querySelector(
                            'input[type="number"], .cart-quantity-input'
                        );


                    if (!input) {
                        return;
                    }


                    let quantity =
                        parseInt(
                            input.value,
                            10
                        ) || 1;


                    const minimum =
                        parseInt(
                            input.getAttribute(
                                'min'
                            ),
                            10
                        ) || 1;


                    quantity--;


                    if (
                        quantity <
                        minimum
                    ) {

                        quantity =
                            minimum;

                    }


                    input.value =
                        quantity;


                    updateCartItem(
                        cartItem,
                        quantity
                    );

                }
            );

        }
    );


    increaseButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    const cartItem =
                        button.closest(
                            '[data-cart-item], .cart-item, .hh-cart-product'
                        );


                    if (!cartItem) {
                        return;
                    }


                    const input =
                        cartItem.querySelector(
                            'input[type="number"], .cart-quantity-input'
                        );


                    if (!input) {
                        return;
                    }


                    let quantity =
                        parseInt(
                            input.value,
                            10
                        ) || 1;


                    const maximum =
                        parseInt(
                            input.getAttribute(
                                'max'
                            ),
                            10
                        );


                    quantity++;


                    if (
                        !isNaN(maximum) &&
                        quantity > maximum
                    ) {

                        quantity =
                            maximum;

                    }


                    input.value =
                        quantity;


                    updateCartItem(
                        cartItem,
                        quantity
                    );

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| MANUAL QUANTITY INPUT
|--------------------------------------------------------------------------
*/

function bindQuantityInputs() {

    const inputs =
        document.querySelectorAll(
            '.cart-quantity-input, input[data-cart-quantity]'
        );


    inputs.forEach(
        function (input) {

            input.addEventListener(
                'change',
                function () {

                    const cartItem =
                        input.closest(
                            '[data-cart-item], .cart-item, .hh-cart-product'
                        );


                    if (!cartItem) {
                        return;
                    }


                    let quantity =
                        parseInt(
                            input.value,
                            10
                        );


                    const minimum =
                        parseInt(
                            input.getAttribute(
                                'min'
                            ),
                            10
                        ) || 1;


                    if (
                        isNaN(quantity) ||
                        quantity < minimum
                    ) {

                        quantity =
                            minimum;

                    }


                    const maximum =
                        parseInt(
                            input.getAttribute(
                                'max'
                            ),
                            10
                        );


                    if (
                        !isNaN(maximum) &&
                        quantity > maximum
                    ) {

                        quantity =
                            maximum;

                    }


                    input.value =
                        quantity;


                    updateCartItem(
                        cartItem,
                        quantity
                    );

                }
            );


            input.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key ===
                        'Enter'
                    ) {

                        event.preventDefault();

                        input.blur();

                    }

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE BUTTONS
|--------------------------------------------------------------------------
*/

function bindRemoveButtons() {

    const removeButtons =
        document.querySelectorAll(
            '[data-cart-remove], .cart-remove-btn'
        );


    removeButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    const cartItem =
                        button.closest(
                            '[data-cart-item], .cart-item, .hh-cart-product'
                        );


                    if (!cartItem) {
                        return;
                    }


                    const cartId =
                        button.dataset.cartId ||
                        cartItem.dataset.cartId ||
                        cartItem.dataset.id;


                    if (!cartId) {

                        console.error(
                            'Cart ID not found.'
                        );

                        return;

                    }


                    removeCartItem(
                        cartItem,
                        cartId
                    );

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE CART ITEM
|--------------------------------------------------------------------------
*/

function updateCartItem(
    cartItem,
    quantity
) {

    const cartId =
        cartItem.dataset.cartId ||
        cartItem.dataset.id;


    /*
    |--------------------------------------------------------------------------
    | New cart.php uses normal forms.
    | If there is no cart ID data attribute, let PHP form handle it.
    |--------------------------------------------------------------------------
    */

    if (!cartId) {
        return;
    }


    setCartItemLoading(
        cartItem,
        true
    );


    const formData =
        new FormData();


    formData.append(
        'cart_id',
        cartId
    );


    formData.append(
        'quantity',
        quantity
    );


    fetch(
        getAjaxUrl(
            'update_cart.php'
        ),
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }
    )
    .then(
        function (response) {

            if (!response.ok) {

                throw new Error(
                    'Network response was not OK.'
                );

            }


            return response.json();

        }
    )
    .then(
        function (data) {

            if (
                data.success === true ||
                data.status === 'success'
            ) {

                updateCartItemUI(
                    cartItem,
                    data
                );


                calculateCartTotals();


                updateCartCount(
                    data.cart_count
                );


                updateNavigationBadge(
                    'cart',
                    data.cart_count
                );

            } else {

                showMarketplaceMessage(
                    data.message ||
                    'Unable to update cart.',
                    'error'
                );

            }

        }
    )
    .catch(
        function (error) {

            console.error(
                'Cart update error:',
                error
            );


            showMarketplaceMessage(
                'Something went wrong while updating your cart.',
                'error'
            );

        }
    )
    .finally(
        function () {

            setCartItemLoading(
                cartItem,
                false
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE CART UI
|--------------------------------------------------------------------------
*/

function updateCartItemUI(
    cartItem,
    data
) {

    const subtotalElement =
        cartItem.querySelector(
            '[data-item-subtotal], .cart-item-subtotal'
        );


    if (
        subtotalElement &&
        data.item_subtotal !== undefined
    ) {

        subtotalElement.textContent =
            formatMoney(
                data.item_subtotal
            );

    }


    if (
        data.subtotal !== undefined
    ) {

        const itemSubtotal =
            cartItem.querySelector(
                '.item-subtotal'
            );


        if (itemSubtotal) {

            itemSubtotal.textContent =
                formatMoney(
                    data.subtotal
                );

        }

    }

}


/*
|--------------------------------------------------------------------------
| REMOVE CART ITEM
|--------------------------------------------------------------------------
*/

function removeCartItem(
    cartItem,
    cartId
) {

    if (
        !confirm(
            'Remove this product from your cart?'
        )
    ) {
        return;
    }


    setCartItemLoading(
        cartItem,
        true
    );


    const formData =
        new FormData();


    formData.append(
        'cart_id',
        cartId
    );


    fetch(
        getAjaxUrl(
            'remove_cart.php'
        ),
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }
    )
    .then(
        function (response) {

            if (!response.ok) {

                throw new Error(
                    'Network response was not OK.'
                );

            }


            return response.json();

        }
    )
    .then(
        function (data) {

            if (
                data.success === true ||
                data.status === 'success'
            ) {

                removeCartItemUI(
                    cartItem
                );


                updateCartCount(
                    data.cart_count
                );


                updateNavigationBadge(
                    'cart',
                    data.cart_count
                );


                calculateCartTotals();


                checkEmptyCart();

            } else {

                showMarketplaceMessage(
                    data.message ||
                    'Unable to remove item.',
                    'error'
                );

            }

        }
    )
    .catch(
        function (error) {

            console.error(
                'Remove cart error:',
                error
            );


            showMarketplaceMessage(
                'Something went wrong while removing the item.',
                'error'
            );

        }
    )
    .finally(
        function () {

            setCartItemLoading(
                cartItem,
                false
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE CART UI
|--------------------------------------------------------------------------
*/

function removeCartItemUI(
    cartItem
) {

    cartItem.style.opacity =
        '0';


    cartItem.style.transform =
        'translateX(20px)';


    setTimeout(
        function () {

            cartItem.remove();

        },
        250
    );

}


/*
|--------------------------------------------------------------------------
| CALCULATE CART TOTALS
|--------------------------------------------------------------------------
*/

function calculateCartTotals() {

    let subtotal = 0;


    const cartItems =
        document.querySelectorAll(
            '[data-cart-item], .cart-item'
        );


    cartItems.forEach(
        function (item) {

            const priceElement =
                item.querySelector(
                    '[data-item-price], .cart-item-price'
                );


            const quantityInput =
                item.querySelector(
                    'input[type="number"], .cart-quantity-input'
                );


            if (
                !priceElement ||
                !quantityInput
            ) {
                return;
            }


            const price =
                parseFloat(
                    priceElement.dataset.price ||
                    priceElement.textContent
                        .replace(
                            /[^0-9.-]+/g,
                            ''
                        )
                ) || 0;


            const quantity =
                parseInt(
                    quantityInput.value,
                    10
                ) || 0;


            subtotal +=
                price *
                quantity;

        }
    );


    const subtotalElements =
        document.querySelectorAll(
            '[data-cart-subtotal], .cart-subtotal'
        );


    subtotalElements.forEach(
        function (element) {

            element.textContent =
                formatMoney(
                    subtotal
                );

        }
    );


    const deliveryElement =
        document.querySelector(
            '[data-cart-delivery], .cart-delivery'
        );


    let delivery = 0;


    if (deliveryElement) {

        delivery =
            parseFloat(
                deliveryElement.dataset.amount ||
                deliveryElement.textContent
                    .replace(
                        /[^0-9.-]+/g,
                        ''
                    )
            ) || 0;

    }


    const total =
        subtotal +
        delivery;


    const totalElements =
        document.querySelectorAll(
            '[data-cart-total], .cart-total'
        );


    totalElements.forEach(
        function (element) {

            element.textContent =
                formatMoney(
                    total
                );

        }
    );

}


/*
|--------------------------------------------------------------------------
| EMPTY CART
|--------------------------------------------------------------------------
*/

function checkEmptyCart() {

    const cartItems =
        document.querySelectorAll(
            '[data-cart-item], .cart-item, .hh-cart-product'
        );


    if (
        cartItems.length > 0
    ) {
        return;
    }


    const cartList =
        document.querySelector(
            '[data-cart-list], .cart-items, .hh-cart-items'
        );


    if (cartList) {

        cartList.innerHTML = `
            <div class="cart-empty">
                <div class="cart-empty-icon">
                    🛒
                </div>

                <h3>
                    Your cart is empty
                </h3>

                <p>
                    Looks like you haven't added
                    anything to your cart yet.
                </p>

                <a href="catalog.php">
                    Start Shopping
                </a>
            </div>
        `;

    }

}


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

function bindCheckoutButton() {

    const checkoutButtons =
        document.querySelectorAll(
            '.cart-checkout-btn, [data-cart-checkout], .hh-checkout-button'
        );


    checkoutButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function (event) {

                    const cartItems =
                        document.querySelectorAll(
                            '[data-cart-item], .cart-item, .hh-cart-product'
                        );


                    if (
                        cartItems.length === 0
                    ) {

                        event.preventDefault();


                        showMarketplaceMessage(
                            'Your cart is empty.',
                            'error'
                        );

                    }

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/

function updateCartCount(
    count
) {

    if (
        count === undefined ||
        count === null
    ) {
        return;
    }


    const elements =
        document.querySelectorAll(
            '[data-cart-count], .cart-count'
        );


    elements.forEach(
        function (element) {

            element.textContent =
                count;

        }
    );

}


/*
|--------------------------------------------------------------------------
| CART ITEM LOADING
|--------------------------------------------------------------------------
*/

function setCartItemLoading(
    cartItem,
    loading
) {

    if (!cartItem) {
        return;
    }


    if (loading) {

        cartItem.classList.add(
            'cart-item-loading'
        );


        cartItem
            .querySelectorAll(
                'button, input'
            )
            .forEach(
                function (element) {

                    element.disabled =
                        true;

                }
            );

    } else {

        cartItem.classList.remove(
            'cart-item-loading'
        );


        cartItem
            .querySelectorAll(
                'button, input'
            )
            .forEach(
                function (element) {

                    element.disabled =
                        false;

                }
            );

    }

}


/*
|--------------------------------------------------------------------------
| AJAX URL
|--------------------------------------------------------------------------
*/

function getAjaxUrl(
    file
) {

    if (
        typeof SITE_URL !==
        'undefined' &&
        SITE_URL
    ) {

        return (
            String(SITE_URL)
                .replace(
                    /\/+$/,
                    ''
                ) +
            '/ajax/' +
            file
        );

    }


    /*
    |--------------------------------------------------------------------------
    | If page is in project root
    |--------------------------------------------------------------------------
    */

    return (
        'ajax/' +
        file
    );

}


/*
|--------------------------------------------------------------------------
| MONEY
|--------------------------------------------------------------------------
*/

function formatMoney(
    amount
) {

    const number =
        parseFloat(
            amount
        ) || 0;


    return (
        'RM ' +
        number.toLocaleString(
            'en-MY',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        )
    );

}


/*
|--------------------------------------------------------------------------
| TOAST
|--------------------------------------------------------------------------
*/

function showMarketplaceMessage(
    message,
    type
) {

    let container =
        document.querySelector(
            '.marketplace-toast-container'
        );


    if (!container) {

        container =
            document.createElement(
                'div'
            );


        container.className =
            'marketplace-toast-container';


        document.body.appendChild(
            container
        );

    }


    const toast =
        document.createElement(
            'div'
        );


    const toastType =
        type ||
        'info';


    toast.className =
        'marketplace-toast ' +
        toastType;


    let icon = 'bi-info-circle-fill';


    if (
        toastType ===
        'success'
    ) {

        icon =
            'bi-check-circle-fill';

    } else if (
        toastType ===
        'error'
    ) {

        icon =
            'bi-exclamation-circle-fill';

    } else if (
        toastType ===
        'info'
    ) {

        icon =
            'bi-heart-fill';

    }


    toast.innerHTML = `
        <div class="marketplace-toast-icon">
            <i class="bi ${icon}"></i>
        </div>

        <div class="marketplace-toast-copy">
            ${escapeHtml(message)}
        </div>

        <button
            type="button"
            class="marketplace-toast-close"
            aria-label="Close"
        >
            <i class="bi bi-x"></i>
        </button>
    `;


    container.appendChild(
        toast
    );


    requestAnimationFrame(
        function () {

            toast.classList.add(
                'show'
            );

        }
    );


    const closeButton =
        toast.querySelector(
            '.marketplace-toast-close'
        );


    if (closeButton) {

        closeButton.addEventListener(
            'click',
            function () {

                removeMarketplaceToast(
                    toast
                );

            }
        );

    }


    setTimeout(
        function () {

            removeMarketplaceToast(
                toast
            );

        },
        3200
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE TOAST
|--------------------------------------------------------------------------
*/

function removeMarketplaceToast(
    toast
) {

    if (
        !toast ||
        !toast.parentNode
    ) {
        return;
    }


    toast.classList.remove(
        'show'
    );


    setTimeout(
        function () {

            if (
                toast.parentNode
            ) {

                toast.remove();

            }

        },
        250
    );

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(
    value
) {

    return String(
        value ?? ''
    )
    .replace(
        /&/g,
        '&amp;'
    )
    .replace(
        /</g,
        '&lt;'
    )
    .replace(
        />/g,
        '&gt;'
    )
    .replace(
        /"/g,
        '&quot;'
    )
    .replace(
        /'/g,
        '&#039;'
    );

}


/*
|--------------------------------------------------------------------------
| OLD CART MESSAGE ALIAS
|--------------------------------------------------------------------------
*/

function showCartMessage(
    message,
    type
) {

    showMarketplaceMessage(
        message,
        type
    );

}


/*
|--------------------------------------------------------------------------
| TOAST CSS
|--------------------------------------------------------------------------
*/

(function injectMarketplaceStyles() {

    if (
        document.getElementById(
            'marketplace-toast-css'
        )
    ) {
        return;
    }


    const style =
        document.createElement(
            'style'
        );


    style.id =
        'marketplace-toast-css';


    style.textContent = `

        .marketplace-toast-container {
            position: fixed;
            top: 92px;
            right: 24px;
            z-index: 99999;
            width: min(360px, calc(100vw - 30px));
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .marketplace-toast {
            width: 100%;
            min-height: 66px;
            padding: 13px 13px 13px 14px;

            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) 27px;
            align-items: center;
            gap: 10px;

            background: rgba(255,255,255,.97);
            border: 1px solid #e1e8f1;
            border-radius: 15px;

            box-shadow:
                0 18px 45px rgba(28,54,100,.16);

            backdrop-filter: blur(12px);

            opacity: 0;
            transform: translateY(-10px) translateX(12px);

            transition:
                opacity .22s ease,
                transform .22s ease;

            pointer-events: auto;
        }

        .marketplace-toast.show {
            opacity: 1;
            transform: translateY(0) translateX(0);
        }

        .marketplace-toast-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            font-size: 15px;
        }

        .marketplace-toast.success
        .marketplace-toast-icon {
            color: #15803d;
            background: #ecfdf3;
        }

        .marketplace-toast.error
        .marketplace-toast-icon {
            color: #dc2626;
            background: #fef2f2;
        }

        .marketplace-toast.info
        .marketplace-toast-icon {
            color: #e11d48;
            background: #fff1f2;
        }

        .marketplace-toast-copy {
            color: #334155;
            font-family: Inter, Arial, sans-serif;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.55;
        }

        .marketplace-toast-close {
            width: 27px;
            height: 27px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #94a3b8;
            background: transparent;

            border: 0;
            border-radius: 8px;

            cursor: pointer;
        }

        .marketplace-toast-close:hover {
            color: #334155;
            background: #f1f5f9;
        }

        .marketplace-button-loading {
            opacity: .72;
            cursor: wait !important;
        }

        .marketplace-spinner {
            width: 12px;
            height: 12px;

            display: inline-block;

            border: 2px solid rgba(255,255,255,.45);
            border-top-color: #ffffff;
            border-radius: 50%;

            animation:
                marketplace-spin .65s linear infinite;
        }

        .marketplace-added {
            background:
                linear-gradient(
                    135deg,
                    #16a34a,
                    #22c55e
                ) !important;
        }

        .wishlist-active {
            color: #e11d48 !important;
            background: #fff1f2 !important;
            border-color: #fecdd3 !important;
        }

        @keyframes marketplace-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 600px) {

            .marketplace-toast-container {
                top: 82px;
                right: 15px;
                left: 15px;
                width: auto;
            }

        }

    `;


    document.head.appendChild(
        style
    );

})();


/*
|--------------------------------------------------------------------------
| GLOBALS
|--------------------------------------------------------------------------
*/

window.updateCartItem =
    updateCartItem;


window.removeCartItem =
    removeCartItem;


window.calculateCartTotals =
    calculateCartTotals;


window.updateCartCount =
    updateCartCount;


window.updateWishlistCount =
    updateWishlistCount;


window.showMarketplaceMessage =
    showMarketplaceMessage;