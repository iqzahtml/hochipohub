/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SMART CART
|--------------------------------------------------------------------------
| File: js/cart.js
|--------------------------------------------------------------------------
| Features:
| - Premium minus / plus stepper
| - Automatic AJAX quantity update
| - No Update button
| - Live item subtotal
| - Live cart subtotal
| - Live estimated total
| - Live navbar cart badge
| - Stock limit
| - Auto saved status
|--------------------------------------------------------------------------
*/

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState ===
        'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initializeSmartCart
        );

    } else {

        initializeSmartCart();
    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE CART
    |--------------------------------------------------------------------------
    */

    function initializeSmartCart() {

        const cartItems =
            document.querySelectorAll(
                '[data-cart-item]'
            );


        if (!cartItems.length) {
            return;
        }


        cartItems.forEach(
            function (cartItem) {

                initializeCartItem(
                    cartItem
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CART ITEM
    |--------------------------------------------------------------------------
    */

    function initializeCartItem(
        cartItem
    ) {

        if (
            cartItem.dataset.smartCartBound ===
            'true'
        ) {
            return;
        }


        cartItem.dataset.smartCartBound =
            'true';


        const decreaseButton =
            cartItem.querySelector(
                '[data-cart-decrease]'
            );


        const increaseButton =
            cartItem.querySelector(
                '[data-cart-increase]'
            );


        const quantityInput =
            cartItem.querySelector(
                '[data-cart-quantity]'
            );


        if (!quantityInput) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MINUS
        |--------------------------------------------------------------------------
        */

        if (decreaseButton) {

            decreaseButton.addEventListener(
                'click',
                function () {

                    changeQuantity(
                        cartItem,
                        quantityInput,
                        -1
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PLUS
        |--------------------------------------------------------------------------
        */

        if (increaseButton) {

            increaseButton.addEventListener(
                'click',
                function () {

                    changeQuantity(
                        cartItem,
                        quantityInput,
                        1
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MANUAL INPUT
        |--------------------------------------------------------------------------
        */

        quantityInput.addEventListener(
            'change',
            function () {

                const quantity =
                    normalizeQuantity(
                        quantityInput
                    );


                quantityInput.value =
                    quantity;


                saveQuantity(
                    cartItem,
                    quantityInput,
                    quantity
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | ENTER
        |--------------------------------------------------------------------------
        */

        quantityInput.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key ===
                    'Enter'
                ) {

                    event.preventDefault();

                    quantityInput.blur();
                }

            }
        );


        updateQuantityButtons(
            cartItem,
            quantityInput
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE QUANTITY
    |--------------------------------------------------------------------------
    */

    function changeQuantity(
        cartItem,
        input,
        amount
    ) {

        if (
            cartItem.dataset.updating ===
            'true'
        ) {
            return;
        }


        const current =
            parseInt(
                input.value,
                10
            ) || 1;


        const minimum =
            parseInt(
                input.min,
                10
            ) || 1;


        const maximum =
            parseInt(
                input.max,
                10
            );


        let next =
            current +
            amount;


        if (
            next <
            minimum
        ) {

            next =
                minimum;
        }


        if (
            !isNaN(maximum)
            &&
            next >
            maximum
        ) {

            next =
                maximum;
        }


        /*
        |--------------------------------------------------------------------------
        | NO CHANGE
        |--------------------------------------------------------------------------
        */

        if (
            next ===
            current
        ) {

            updateQuantityButtons(
                cartItem,
                input
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE DISPLAY
        |--------------------------------------------------------------------------
        */

        input.value =
            next;


        animateQuantity(
            input
        );


        updateQuantityButtons(
            cartItem,
            input
        );


        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        saveQuantity(
            cartItem,
            input,
            next
        );

    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE INPUT
    |--------------------------------------------------------------------------
    */

    function normalizeQuantity(
        input
    ) {

        let quantity =
            parseInt(
                input.value,
                10
            );


        const minimum =
            parseInt(
                input.min,
                10
            ) || 1;


        const maximum =
            parseInt(
                input.max,
                10
            );


        if (
            isNaN(quantity)
            ||
            quantity <
            minimum
        ) {

            quantity =
                minimum;
        }


        if (
            !isNaN(maximum)
            &&
            quantity >
            maximum
        ) {

            quantity =
                maximum;
        }


        return quantity;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE QUANTITY
    |--------------------------------------------------------------------------
    */

    async function saveQuantity(
        cartItem,
        input,
        quantity
    ) {

        const cartId =
            cartItem.dataset.cartId;


        if (!cartId) {

            setQuantityStatus(
                cartItem,
                'error',
                'Cart ID missing'
            );

            return;
        }


        const previousQuantity =
            parseInt(
                cartItem.dataset.savedQuantity
                ||
                input.defaultValue
                ||
                input.value,
                10
            ) || 1;


        cartItem.dataset.updating =
            'true';


        setQuantityLoading(
            cartItem,
            true
        );


        setQuantityStatus(
            cartItem,
            'saving',
            'Saving...'
        );


        /*
        |--------------------------------------------------------------------------
        | DISABLE BUTTON WHILE SAVING
        |--------------------------------------------------------------------------
        */

        setStepperDisabled(
            cartItem,
            true
        );


        const formData =
            new FormData();


        formData.append(
            'ajax_quantity',
            '1'
        );


        formData.append(
            'cart_id',
            cartId
        );


        formData.append(
            'quantity',
            quantity
        );


        try {

            const response =
                await fetch(
                    'cart.php',
                    {
                        method:
                            'POST',

                        body:
                            formData,

                        credentials:
                            'same-origin',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest'
                        }
                    }
                );


            /*
            |--------------------------------------------------------------------------
            | GET RAW RESPONSE
            |--------------------------------------------------------------------------
            */

            const responseText =
                await response.text();


            let data;


            try {

                data =
                    JSON.parse(
                        responseText
                    );

            } catch (error) {

                console.error(
                    'Invalid cart response:',
                    responseText
                );


                throw new Error(
                    'Server did not return valid JSON.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | ERROR
            |--------------------------------------------------------------------------
            */

            if (
                !response.ok
                ||
                data.success !==
                true
            ) {

                throw new Error(
                    data.message
                    ||
                    'Unable to update quantity.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | QUANTITY FROM SERVER
            |--------------------------------------------------------------------------
            */

            const savedQuantity =
                parseInt(
                    data.quantity,
                    10
                ) || quantity;


            input.value =
                savedQuantity;


            input.defaultValue =
                savedQuantity;


            cartItem.dataset.savedQuantity =
                savedQuantity;


            /*
            |--------------------------------------------------------------------------
            | ITEM SUBTOTAL
            |--------------------------------------------------------------------------
            */

            const itemSubtotal =
                cartItem.querySelector(
                    '[data-item-subtotal]'
                );


            if (
                itemSubtotal
                &&
                data.item_subtotal !==
                undefined
            ) {

                itemSubtotal.textContent =
                    formatMoney(
                        data.item_subtotal
                    );


                animateSubtotal(
                    itemSubtotal
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CART SUBTOTAL
            |--------------------------------------------------------------------------
            */

            if (
                data.cart_subtotal !==
                undefined
            ) {

                document
                    .querySelectorAll(
                        '[data-cart-subtotal]'
                    )
                    .forEach(
                        function (element) {

                            element.textContent =
                                formatMoney(
                                    data.cart_subtotal
                                );

                        }
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | CART TOTAL
            |--------------------------------------------------------------------------
            */

            if (
                data.cart_total !==
                undefined
            ) {

                document
                    .querySelectorAll(
                        '[data-cart-total]'
                    )
                    .forEach(
                        function (element) {

                            element.textContent =
                                formatMoney(
                                    data.cart_total
                                );

                        }
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | ITEM COUNT
            |--------------------------------------------------------------------------
            */

            if (
                data.cart_count !==
                undefined
            ) {

                updateCartCount(
                    data.cart_count
                );


                updateNavigationCartBadge(
                    data.cart_count
                );
            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            setQuantityStatus(
                cartItem,
                'saved',
                'Auto saved'
            );


            animateQuantity(
                input
            );


        } catch (error) {

            console.error(
                'Cart quantity error:',
                error
            );


            /*
            |--------------------------------------------------------------------------
            | RESTORE OLD VALUE
            |--------------------------------------------------------------------------
            */

            input.value =
                previousQuantity;


            setQuantityStatus(
                cartItem,
                'error',
                error.message ||
                'Update failed'
            );


            showCartToast(
                error.message ||
                'Unable to update cart.',
                'error'
            );


        } finally {

            delete cartItem.dataset.updating;


            setQuantityLoading(
                cartItem,
                false
            );


            setStepperDisabled(
                cartItem,
                false
            );


            updateQuantityButtons(
                cartItem,
                input
            );
        }

    }


    /*
    |--------------------------------------------------------------------------
    | STEPPER BUTTON STATE
    |--------------------------------------------------------------------------
    */

    function updateQuantityButtons(
        cartItem,
        input
    ) {

        const decrease =
            cartItem.querySelector(
                '[data-cart-decrease]'
            );


        const increase =
            cartItem.querySelector(
                '[data-cart-increase]'
            );


        const quantity =
            parseInt(
                input.value,
                10
            ) || 1;


        const minimum =
            parseInt(
                input.min,
                10
            ) || 1;


        const maximum =
            parseInt(
                input.max,
                10
            );


        if (decrease) {

            decrease.disabled =
                quantity <=
                minimum;
        }


        if (increase) {

            increase.disabled =
                !isNaN(maximum)
                &&
                quantity >=
                maximum;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | TEMPORARY DISABLE
    |--------------------------------------------------------------------------
    */

    function setStepperDisabled(
        cartItem,
        disabled
    ) {

        const buttons =
            cartItem.querySelectorAll(
                '.hh-quantity-btn'
            );


        buttons.forEach(
            function (button) {

                button.disabled =
                    disabled;

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LOADING STYLE
    |--------------------------------------------------------------------------
    */

    function setQuantityLoading(
        cartItem,
        loading
    ) {

        const stepper =
            cartItem.querySelector(
                '[data-quantity-stepper]'
            );


        if (!stepper) {
            return;
        }


        if (loading) {

            stepper.classList.add(
                'updating'
            );

        } else {

            stepper.classList.remove(
                'updating'
            );
        }

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    function setQuantityStatus(
        cartItem,
        state,
        text
    ) {

        const status =
            cartItem.querySelector(
                '[data-quantity-status]'
            );


        if (!status) {
            return;
        }


        status.classList.remove(
            'saving',
            'error'
        );


        if (
            state ===
            'saving'
        ) {

            status.classList.add(
                'saving'
            );


            status.innerHTML = `
                <i class="bi bi-arrow-repeat"></i>
                ${escapeHtml(text)}
            `;


        } else if (
            state ===
            'error'
        ) {

            status.classList.add(
                'error'
            );


            status.innerHTML = `
                <i class="bi bi-exclamation-circle"></i>
                ${escapeHtml(text)}
            `;


        } else {

            status.innerHTML = `
                <i class="bi bi-cloud-check"></i>
                ${escapeHtml(text)}
            `;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | CART COUNT
    |--------------------------------------------------------------------------
    */

    function updateCartCount(
        count
    ) {

        count =
            parseInt(
                count,
                10
            ) || 0;


        document
            .querySelectorAll(
                '[data-cart-count-display]'
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
    | NAV BADGE
    |--------------------------------------------------------------------------
    */

    function updateNavigationCartBadge(
        count
    ) {

        count =
            parseInt(
                count,
                10
            ) || 0;


        const cartLinks =
            document.querySelectorAll(
                'a[href$="cart.php"]'
            );


        cartLinks.forEach(
            function (link) {

                let badge =
                    link.querySelector(
                        '.sidebar-badge'
                    );


                /*
                |--------------------------------------------------------------------------
                | REMOVE
                |--------------------------------------------------------------------------
                */

                if (
                    count <= 0
                ) {

                    if (badge) {
                        badge.remove();
                    }

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE
                |--------------------------------------------------------------------------
                */

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
    | QUANTITY ANIMATION
    |--------------------------------------------------------------------------
    */

    function animateQuantity(
        input
    ) {

        input.classList.remove(
            'bump'
        );


        void input.offsetWidth;


        input.classList.add(
            'bump'
        );


        setTimeout(
            function () {

                input.classList.remove(
                    'bump'
                );

            },
            230
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SUBTOTAL ANIMATION
    |--------------------------------------------------------------------------
    */

    function animateSubtotal(
        element
    ) {

        element.animate(
            [
                {
                    transform:
                        'translateY(0)',
                    opacity:
                        1
                },
                {
                    transform:
                        'translateY(-3px)',
                    opacity:
                        .55
                },
                {
                    transform:
                        'translateY(0)',
                    opacity:
                        1
                }
            ],
            {
                duration:
                    260,

                easing:
                    'ease'
            }
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

        amount =
            parseFloat(
                amount
            ) || 0;


        return (
            'RM ' +
            amount.toLocaleString(
                'en-MY',
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        2
                }
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SMALL TOAST
    |--------------------------------------------------------------------------
    */

    function showCartToast(
        message,
        type
    ) {

        let container =
            document.getElementById(
                'hhCartToastContainer'
            );


        if (!container) {

            container =
                document.createElement(
                    'div'
                );


            container.id =
                'hhCartToastContainer';


            container.style.position =
                'fixed';

            container.style.top =
                '90px';

            container.style.right =
                '24px';

            container.style.zIndex =
                '999999';

            container.style.display =
                'flex';

            container.style.flexDirection =
                'column';

            container.style.gap =
                '10px';


            document.body.appendChild(
                container
            );
        }


        const toast =
            document.createElement(
                'div'
            );


        toast.style.minWidth =
            '260px';

        toast.style.maxWidth =
            '360px';

        toast.style.padding =
            '14px 16px';

        toast.style.background =
            '#ffffff';

        toast.style.border =
            type === 'error'
                ? '1px solid #fecaca'
                : '1px solid #bbf7d0';

        toast.style.borderRadius =
            '14px';

        toast.style.boxShadow =
            '0 16px 40px rgba(15,23,42,.14)';

        toast.style.fontSize =
            '12px';

        toast.style.fontWeight =
            '700';

        toast.style.color =
            type === 'error'
                ? '#b91c1c'
                : '#166534';


        toast.textContent =
            message;


        container.appendChild(
            toast
        );


        setTimeout(
            function () {

                toast.style.opacity =
                    '0';

                toast.style.transform =
                    'translateY(-5px)';

                toast.style.transition =
                    '.2s ease';

            },
            2600
        );


        setTimeout(
            function () {

                toast.remove();

            },
            2900
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE
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

})();