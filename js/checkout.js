/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CHECKOUT JS
|--------------------------------------------------------------------------
| Handles:
| - Checkout form validation
| - Delivery method
| - Delivery address
| - Payment method
| - Order summary
| - Quantity controls
| - Payment selection
| - Checkout submission
|--------------------------------------------------------------------------
*/

"use strict";

document.addEventListener("DOMContentLoaded", function () {

    /* ==============================================================
       ELEMENTS
    ============================================================== */

    const checkoutForm = document.querySelector("#checkoutForm");

    const deliveryMethodInputs = document.querySelectorAll(
        'input[name="delivery_method"]'
    );

    const deliveryAddress = document.querySelector("#deliveryAddress");

    const addressWrapper = document.querySelector(
        "#deliveryAddressWrapper"
    );

    const paymentMethodInputs = document.querySelectorAll(
        'input[name="payment_method"]'
    );

    const quantityInputs = document.querySelectorAll(
        ".checkout-quantity-input"
    );

    const decreaseButtons = document.querySelectorAll(
        ".checkout-quantity-decrease"
    );

    const increaseButtons = document.querySelectorAll(
        ".checkout-quantity-increase"
    );

    const subtotalElement = document.querySelector("#checkoutSubtotal");

    const deliveryFeeElement = document.querySelector("#checkoutDeliveryFee");

    const totalElement = document.querySelector("#checkoutTotal");

    const checkoutMessage = document.querySelector(
        "#checkoutMessage"
    );


    /* ==============================================================
       CONFIGURATION
    ============================================================== */

    const DELIVERY_FEES = {
        Pickup: 0,
        Postage: 5
    };


    /* ==============================================================
       HELPER - FORMAT MONEY
    ============================================================== */

    function formatMoney(amount) {

        amount = Number(amount) || 0;

        return "RM " + amount.toFixed(2);

    }


    /* ==============================================================
       HELPER - GET NUMBER
    ============================================================== */

    function getNumber(value) {

        const number = parseFloat(value);

        return Number.isFinite(number) ? number : 0;

    }


    /* ==============================================================
       GET SELECTED DELIVERY METHOD
    ============================================================== */

    function getSelectedDeliveryMethod() {

        const selected = document.querySelector(
            'input[name="delivery_method"]:checked'
        );

        return selected ? selected.value : "Postage";

    }


    /* ==============================================================
       GET SELECTED PAYMENT METHOD
    ============================================================== */

    function getSelectedPaymentMethod() {

        const selected = document.querySelector(
            'input[name="payment_method"]:checked'
        );

        return selected ? selected.value : "";

    }


    /* ==============================================================
       UPDATE DELIVERY ADDRESS
    ============================================================== */

    function updateDeliveryAddress() {

        const method = getSelectedDeliveryMethod();

        if (!addressWrapper) {
            return;
        }

        if (method === "Pickup") {

            addressWrapper.style.display = "none";

            if (deliveryAddress) {

                deliveryAddress.removeAttribute("required");

            }

        } else {

            addressWrapper.style.display = "block";

            if (deliveryAddress) {

                deliveryAddress.setAttribute("required", "required");

            }

        }

        updateCheckoutTotal();

    }


    /* ==============================================================
       UPDATE CHECKOUT TOTAL
    ============================================================== */

    function updateCheckoutTotal() {

        let subtotal = 0;

        const checkoutItems = document.querySelectorAll(
            ".checkout-item"
        );


        /* ----------------------------------------------------------
           Calculate subtotal from checkout items
        ---------------------------------------------------------- */

        checkoutItems.forEach(function (item) {

            const priceElement = item.querySelector(
                ".checkout-item-price"
            );

            const quantityInput = item.querySelector(
                ".checkout-quantity-input"
            );

            const itemSubtotalElement = item.querySelector(
                ".checkout-item-subtotal"
            );

            const price = priceElement
                ? getNumber(
                    priceElement.dataset.price ||
                    priceElement.textContent.replace(/[^0-9.]/g, "")
                )
                : 0;

            const quantity = quantityInput
                ? Math.max(
                    1,
                    parseInt(quantityInput.value, 10) || 1
                )
                : 1;

            const itemSubtotal = price * quantity;

            subtotal += itemSubtotal;


            /* Update individual subtotal */

            if (itemSubtotalElement) {

                itemSubtotalElement.textContent =
                    formatMoney(itemSubtotal);

            }

        });


        /* ----------------------------------------------------------
           Alternative subtotal source
           If checkout page already provides data-subtotal
        ---------------------------------------------------------- */

        if (checkoutItems.length === 0 && subtotalElement) {

            subtotal = getNumber(
                subtotalElement.dataset.subtotal ||
                subtotalElement.textContent.replace(/[^0-9.]/g, "")
            );

        }


        const deliveryMethod = getSelectedDeliveryMethod();

        const deliveryFee =
            DELIVERY_FEES[deliveryMethod] !== undefined
                ? DELIVERY_FEES[deliveryMethod]
                : 0;


        const total = subtotal + deliveryFee;


        /* ----------------------------------------------------------
           Update UI
        ---------------------------------------------------------- */

        if (subtotalElement) {

            subtotalElement.textContent =
                formatMoney(subtotal);

        }


        if (deliveryFeeElement) {

            deliveryFeeElement.textContent =
                deliveryFee === 0
                    ? "FREE"
                    : formatMoney(deliveryFee);

        }


        if (totalElement) {

            totalElement.textContent =
                formatMoney(total);

        }


        /* ----------------------------------------------------------
           Store values for later use
        ---------------------------------------------------------- */

        if (checkoutForm) {

            checkoutForm.dataset.subtotal = subtotal.toFixed(2);

            checkoutForm.dataset.deliveryFee =
                deliveryFee.toFixed(2);

            checkoutForm.dataset.total =
                total.toFixed(2);

        }

    }


    /* ==============================================================
       QUANTITY - UPDATE
    ============================================================== */

    function updateQuantity(input, change) {

        if (!input) {
            return;
        }

        let currentQuantity =
            parseInt(input.value, 10) || 1;

        const min =
            parseInt(input.min, 10) || 1;

        const max =
            parseInt(input.max, 10) || 999;

        currentQuantity += change;

        if (currentQuantity < min) {
            currentQuantity = min;
        }

        if (currentQuantity > max) {
            currentQuantity = max;
        }

        input.value = currentQuantity;

        updateCheckoutTotal();

    }


    /* ==============================================================
       DECREASE QUANTITY
    ============================================================== */

    decreaseButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const item =
                button.closest(".checkout-item");

            if (!item) {
                return;
            }

            const input =
                item.querySelector(
                    ".checkout-quantity-input"
                );

            updateQuantity(input, -1);

        });

    });


    /* ==============================================================
       INCREASE QUANTITY
    ============================================================== */

    increaseButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const item =
                button.closest(".checkout-item");

            if (!item) {
                return;
            }

            const input =
                item.querySelector(
                    ".checkout-quantity-input"
                );

            updateQuantity(input, 1);

        });

    });


    /* ==============================================================
       MANUAL QUANTITY INPUT
    ============================================================== */

    quantityInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            let value =
                parseInt(input.value, 10);

            const min =
                parseInt(input.min, 10) || 1;

            const max =
                parseInt(input.max, 10) || 999;


            if (!Number.isFinite(value)) {

                value = min;

            }


            if (value < min) {

                value = min;

            }


            if (value > max) {

                value = max;

            }


            input.value = value;

            updateCheckoutTotal();

        });


        input.addEventListener("change", function () {

            updateCheckoutTotal();

        });

    });


    /* ==============================================================
       DELIVERY METHOD
    ============================================================== */

    deliveryMethodInputs.forEach(function (input) {

        input.addEventListener("change", function () {

            updateDeliveryAddress();

        });

    });


    /* ==============================================================
       PAYMENT METHOD
    ============================================================== */

    paymentMethodInputs.forEach(function (input) {

        input.addEventListener("change", function () {

            const method =
                getSelectedPaymentMethod();


            /*
             * Remove selected state from all payment cards
             */

            document
                .querySelectorAll(".payment-method-card")
                .forEach(function (card) {

                    card.classList.remove("active");

                });


            /*
             * Add selected state
             */

            const selectedCard =
                input.closest(".payment-method-card");

            if (selectedCard) {

                selectedCard.classList.add("active");

            }


            /*
             * Store selected method
             */

            if (checkoutForm) {

                checkoutForm.dataset.paymentMethod =
                    method;

            }

        });

    });


    /* ==============================================================
       VALIDATE DELIVERY ADDRESS
    ============================================================== */

    function validateDeliveryAddress() {

        const method =
            getSelectedDeliveryMethod();


        if (method === "Pickup") {

            return true;

        }


        if (!deliveryAddress) {

            return true;

        }


        const address =
            deliveryAddress.value.trim();


        if (address.length < 10) {

            showCheckoutMessage(
                "Please enter a complete delivery address.",
                "error"
            );

            deliveryAddress.focus();

            return false;

        }


        return true;

    }


    /* ==============================================================
       VALIDATE PAYMENT METHOD
    ============================================================== */

    function validatePaymentMethod() {

        const paymentMethod =
            getSelectedPaymentMethod();


        if (!paymentMethod) {

            showCheckoutMessage(
                "Please select a payment method.",
                "error"
            );

            return false;

        }


        return true;

    }


    /* ==============================================================
       VALIDATE CHECKOUT
    ============================================================== */

    function validateCheckout() {

        if (!checkoutForm) {

            return false;

        }


        /*
         * Browser validation
         */

        if (!checkoutForm.checkValidity()) {

            checkoutForm.reportValidity();

            return false;

        }


        /*
         * Delivery validation
         */

        if (!validateDeliveryAddress()) {

            return false;

        }


        /*
         * Payment validation
         */

        if (!validatePaymentMethod()) {

            return false;

        }


        /*
         * Check total
         */

        const total =
            getNumber(
                checkoutForm.dataset.total
            );


        if (total <= 0) {

            showCheckoutMessage(
                "Your cart is empty.",
                "error"
            );

            return false;

        }


        return true;

    }


    /* ==============================================================
       CHECKOUT MESSAGE
    ============================================================== */

    function showCheckoutMessage(message, type = "error") {

        if (!checkoutMessage) {

            /*
             * Fallback if no message container exists
             */

            alert(message);

            return;

        }


        checkoutMessage.textContent =
            message;

        checkoutMessage.className =
            "checkout-message " + type;

        checkoutMessage.style.display =
            "block";


        /*
         * Scroll message into view
         */

        checkoutMessage.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });

    }


    /* ==============================================================
       CLEAR CHECKOUT MESSAGE
    ============================================================== */

    function clearCheckoutMessage() {

        if (!checkoutMessage) {

            return;

        }

        checkoutMessage.textContent = "";

        checkoutMessage.className =
            "checkout-message";

        checkoutMessage.style.display =
            "none";

    }


    /* ==============================================================
       SUBMIT CHECKOUT
    ============================================================== */

    if (checkoutForm) {

        checkoutForm.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();

                clearCheckoutMessage();


                /*
                 * Validate
                 */

                if (!validateCheckout()) {

                    return;

                }


                /*
                 * Prevent double submission
                 */

                const submitButton =
                    checkoutForm.querySelector(
                        'button[type="submit"]'
                    );


                if (submitButton) {

                    submitButton.disabled = true;

                    submitButton.dataset.originalText =
                        submitButton.innerHTML;

                    submitButton.innerHTML =
                        '<i class="fas fa-spinner fa-spin"></i> Processing...';

                }


                /*
                 * Submit normally after JS validation
                 *
                 * This allows PHP checkout process
                 * to handle the actual order creation.
                 */

                checkoutForm.submit();

            }
        );

    }


    /* ==============================================================
       PAYMENT METHOD CARD CLICK
    ============================================================== */

    document
        .querySelectorAll(".payment-method-card")
        .forEach(function (card) {

            card.addEventListener("click", function () {

                const radio =
                    card.querySelector(
                        'input[type="radio"]'
                    );

                if (!radio) {
                    return;
                }

                radio.checked = true;

                radio.dispatchEvent(
                    new Event("change", {
                        bubbles: true
                    })
                );

            });

        });


    /* ==============================================================
       DELIVERY CARD CLICK
    ============================================================== */

    document
        .querySelectorAll(".delivery-method-card")
        .forEach(function (card) {

            card.addEventListener("click", function () {

                const radio =
                    card.querySelector(
                        'input[type="radio"]'
                    );

                if (!radio) {
                    return;
                }

                radio.checked = true;

                radio.dispatchEvent(
                    new Event("change", {
                        bubbles: true
                    })
                );

            });

        });


    /* ==============================================================
       PREVENT INVALID QUANTITY KEYBOARD INPUT
    ============================================================== */

    quantityInputs.forEach(function (input) {

        input.addEventListener("keydown", function (event) {

            const blockedKeys = [
                "-",
                "+",
                "e",
                "E",
                "."
            ];


            if (blockedKeys.includes(event.key)) {

                event.preventDefault();

            }

        });

    });


    /* ==============================================================
       ADDRESS CHARACTER COUNTER
    ============================================================== */

    if (deliveryAddress) {

        const counter =
            document.querySelector(
                "#addressCharacterCount"
            );


        if (counter) {

            function updateAddressCounter() {

                counter.textContent =
                    deliveryAddress.value.length;

            }


            deliveryAddress.addEventListener(
                "input",
                updateAddressCounter
            );


            updateAddressCounter();

        }

    }


    /* ==============================================================
       AUTO-FILL PAYMENT METHOD
    ============================================================== */

    const firstPaymentMethod =
        document.querySelector(
            'input[name="payment_method"]'
        );


    if (
        firstPaymentMethod &&
        !document.querySelector(
            'input[name="payment_method"]:checked'
        )
    ) {

        firstPaymentMethod.checked = true;

        firstPaymentMethod.dispatchEvent(
            new Event("change", {
                bubbles: true
            })
        );

    }


    /* ==============================================================
       INITIALISE
    ============================================================== */

    updateDeliveryAddress();

    updateCheckoutTotal();


    /* ==============================================================
       EXPOSE FUNCTIONS
       Useful if HTML buttons call them directly.
    ============================================================== */

    window.HochipoHubCheckout = {

        updateTotal: updateCheckoutTotal,

        validate: validateCheckout,

        showMessage: showCheckoutMessage,

        clearMessage: clearCheckoutMessage,

        getDeliveryMethod:
            getSelectedDeliveryMethod,

        getPaymentMethod:
            getSelectedPaymentMethod

    };

});