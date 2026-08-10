/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CART JAVASCRIPT
|--------------------------------------------------------------------------
| File: js/cart.js
|--------------------------------------------------------------------------
| Handles:
| - Cart quantity update
| - Remove cart item
| - Cart total calculation
| - AJAX requests
| - Loading states
| - Empty cart state
|--------------------------------------------------------------------------
*/

'use strict';


/*
|--------------------------------------------------------------------------
| DOM READY
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    initCart();

});


/*
|--------------------------------------------------------------------------
| INITIALIZE CART
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

    return document.querySelector('.cart-container') ||
           document.querySelector('.cart-page') ||
           document.querySelector('[data-cart-container]');

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


    decreaseButtons.forEach(function (button) {

        button.addEventListener('click', function (event) {

            event.preventDefault();

            const cartItem =
                button.closest(
                    '[data-cart-item], .cart-item'
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
                parseInt(input.value, 10) || 1;

            const minimum =
                parseInt(
                    input.getAttribute('min'),
                    10
                ) || 1;

            quantity--;

            if (quantity < minimum) {
                quantity = minimum;
            }

            input.value = quantity;

            updateCartItem(cartItem, quantity);

        });

    });


    increaseButtons.forEach(function (button) {

        button.addEventListener('click', function (event) {

            event.preventDefault();

            const cartItem =
                button.closest(
                    '[data-cart-item], .cart-item'
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
                parseInt(input.value, 10) || 1;

            const maximum =
                parseInt(
                    input.getAttribute('max'),
                    10
                );

            quantity++;

            if (
                !isNaN(maximum) &&
                quantity > maximum
            ) {
                quantity = maximum;
            }

            input.value = quantity;

            updateCartItem(cartItem, quantity);

        });

    });

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


    inputs.forEach(function (input) {

        input.addEventListener(
            'change',
            function () {

                const cartItem =
                    input.closest(
                        '[data-cart-item], .cart-item'
                    );

                if (!cartItem) {
                    return;
                }

                let quantity =
                    parseInt(input.value, 10);

                const minimum =
                    parseInt(
                        input.getAttribute('min'),
                        10
                    ) || 1;

                if (
                    isNaN(quantity) ||
                    quantity < minimum
                ) {
                    quantity = minimum;
                }


                const maximum =
                    parseInt(
                        input.getAttribute('max'),
                        10
                    );


                if (
                    !isNaN(maximum) &&
                    quantity > maximum
                ) {
                    quantity = maximum;
                }


                input.value = quantity;

                updateCartItem(
                    cartItem,
                    quantity
                );

            }
        );


        input.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Enter') {

                    event.preventDefault();

                    input.blur();

                }

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| REMOVE CART BUTTONS
|--------------------------------------------------------------------------
*/

function bindRemoveButtons() {

    const removeButtons =
        document.querySelectorAll(
            '[data-cart-remove], .cart-remove-btn'
        );


    removeButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                const cartItem =
                    button.closest(
                        '[data-cart-item], .cart-item'
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

    });

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


    if (!cartId) {

        console.error(
            'Cart ID not found.'
        );

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
        getAjaxUrl('add_cart.php'),
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }
    )
    .then(function (response) {

        if (!response.ok) {
            throw new Error(
                'Network response was not OK.'
            );
        }

        return response.json();

    })
    .then(function (data) {

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

        } else {

            showCartMessage(
                data.message ||
                'Unable to update cart.',
                'error'
            );

        }

    })
    .catch(function (error) {

        console.error(
            'Cart update error:',
            error
        );

        showCartMessage(
            'Something went wrong while updating your cart.',
            'error'
        );

    })
    .finally(function () {

        setCartItemLoading(
            cartItem,
            false
        );

    });

}


/*
|--------------------------------------------------------------------------
| UPDATE CART ITEM UI
|--------------------------------------------------------------------------
*/

function updateCartItemUI(
    cartItem,
    data
) {

    /*
    |--------------------------------------------------------------------------
    | ITEM SUBTOTAL
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | ITEM TOTAL
    |--------------------------------------------------------------------------
    */

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
        getAjaxUrl('remove_cart.php'),
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }
    )
    .then(function (response) {

        if (!response.ok) {
            throw new Error(
                'Network response was not OK.'
            );
        }

        return response.json();

    })
    .then(function (data) {

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


            calculateCartTotals();


            checkEmptyCart();

        } else {

            showCartMessage(
                data.message ||
                'Unable to remove item.',
                'error'
            );

        }

    })
    .catch(function (error) {

        console.error(
            'Remove cart error:',
            error
        );

        showCartMessage(
            'Something went wrong while removing the item.',
            'error'
        );

    })
    .finally(function () {

        setCartItemLoading(
            cartItem,
            false
        );

    });

}


/*
|--------------------------------------------------------------------------
| REMOVE ITEM UI
|--------------------------------------------------------------------------
*/

function removeCartItemUI(
    cartItem
) {

    cartItem.style.opacity = '0';
    cartItem.style.transform = 'translateX(20px)';


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


    cartItems.forEach(function (item) {

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
                    .replace(/[^0-9.-]+/g, '')
            ) || 0;


        const quantity =
            parseInt(
                quantityInput.value,
                10
            ) || 0;


        subtotal +=
            price * quantity;

    });


    /*
    |--------------------------------------------------------------------------
    | UPDATE SUBTOTAL
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | DELIVERY
    |--------------------------------------------------------------------------
    */

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
                    .replace(/[^0-9.-]+/g, '')
            ) || 0;

    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    */

    const total =
        subtotal + delivery;


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
| CHECK EMPTY CART
|--------------------------------------------------------------------------
*/

function checkEmptyCart() {

    const cartItems =
        document.querySelectorAll(
            '[data-cart-item], .cart-item'
        );


    if (
        cartItems.length > 0
    ) {
        return;
    }


    const cartList =
        document.querySelector(
            '[data-cart-list], .cart-items'
        );


    if (cartList) {

        cartList.innerHTML = `
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h3>Your cart is empty</h3>
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
| CHECKOUT BUTTON
|--------------------------------------------------------------------------
*/

function bindCheckoutButton() {

    const checkoutButtons =
        document.querySelectorAll(
            '.cart-checkout-btn, [data-cart-checkout]'
        );


    checkoutButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function (event) {

                    const cartItems =
                        document.querySelectorAll(
                            '[data-cart-item], .cart-item'
                        );


                    if (
                        cartItems.length === 0
                    ) {

                        event.preventDefault();

                        showCartMessage(
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
            .querySelectorAll('button, input')
            .forEach(
                function (element) {

                    element.disabled = true;

                }
            );

    } else {

        cartItem.classList.remove(
            'cart-item-loading'
        );


        cartItem
            .querySelectorAll('button, input')
            .forEach(
                function (element) {

                    element.disabled = false;

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

    /*
    |--------------------------------------------------------------------------
    | Try existing global SITE URL
    |--------------------------------------------------------------------------
    */

    if (
        typeof SITE_URL !== 'undefined'
    ) {

        return SITE_URL +
               'ajax/' +
               file;

    }


    /*
    |--------------------------------------------------------------------------
    | Relative fallback
    |--------------------------------------------------------------------------
    */

    return 'ajax/' + file;

}


/*
|--------------------------------------------------------------------------
| FORMAT MONEY
|--------------------------------------------------------------------------
*/

function formatMoney(
    amount
) {

    const number =
        parseFloat(amount) || 0;


    return 'RM ' +
        number.toLocaleString(
            'en-MY',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


/*
|--------------------------------------------------------------------------
| CART MESSAGE
|--------------------------------------------------------------------------
*/

function showCartMessage(
    message,
    type
) {

    let messageBox =
        document.querySelector(
            '.cart-message'
        );


    if (!messageBox) {

        messageBox =
            document.createElement(
                'div'
            );

        messageBox.className =
            'cart-message';


        document.body.appendChild(
            messageBox
        );

    }


    messageBox.textContent =
        message;


    messageBox.className =
        'cart-message ' +
        (type || 'info');


    messageBox.classList.add(
        'show'
    );


    setTimeout(
        function () {

            messageBox.classList.remove(
                'show'
            );

        },
        3000
    );

}


/*
|--------------------------------------------------------------------------
| GLOBAL FUNCTIONS
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