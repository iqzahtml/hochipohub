/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CHECKOUT JAVASCRIPT
|--------------------------------------------------------------------------
| File: js/checkout.js
|--------------------------------------------------------------------------
| Handles:
| - Checkout form
| - Delivery method
| - Payment method
| - Address validation
| - Order total
| - Checkout confirmation
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

        initCheckout();

    }
);


/*
|--------------------------------------------------------------------------
| INITIALIZE CHECKOUT
|--------------------------------------------------------------------------
*/

function initCheckout() {

    bindDeliveryMethod();
    bindPaymentMethod();
    bindCheckoutForm();
    bindQuantityChanges();

    calculateCheckoutTotal();

}


/*
|--------------------------------------------------------------------------
| DELIVERY METHOD
|--------------------------------------------------------------------------
*/

function bindDeliveryMethod() {

    const deliveryOptions =
        document.querySelectorAll(
            'input[name="delivery_method"]'
        );


    deliveryOptions.forEach(
        function (option) {

            option.addEventListener(
                'change',
                function () {

                    updateDeliveryUI(
                        option.value
                    );

                    calculateCheckoutTotal();

                }
            );

        }
    );


    const selected =
        document.querySelector(
            'input[name="delivery_method"]:checked'
        );


    if (selected) {

        updateDeliveryUI(
            selected.value
        );

    }

}


/*
|--------------------------------------------------------------------------
| UPDATE DELIVERY UI
|--------------------------------------------------------------------------
*/

function updateDeliveryUI(
    method
) {

    const addressSection =
        document.querySelector(
            '[data-delivery-address], .delivery-address-section'
        );


    const pickupSection =
        document.querySelector(
            '[data-pickup-info], .pickup-info'
        );


    const normalized =
        String(method)
            .toLowerCase();


    const isPickup =
        normalized.includes(
            'pickup'
        );


    if (addressSection) {

        addressSection.style.display =
            isPickup
                ? 'none'
                : '';

    }


    if (pickupSection) {

        pickupSection.style.display =
            isPickup
                ? ''
                : 'none';

    }

}


/*
|--------------------------------------------------------------------------
| PAYMENT METHOD
|--------------------------------------------------------------------------
*/

function bindPaymentMethod() {

    const paymentOptions =
        document.querySelectorAll(
            'input[name="payment_method"]'
        );


    paymentOptions.forEach(
        function (option) {

            option.addEventListener(
                'change',
                function () {

                    updatePaymentUI(
                        option.value
                    );

                }
            );

        }
    );


    const selected =
        document.querySelector(
            'input[name="payment_method"]:checked'
        );


    if (selected) {

        updatePaymentUI(
            selected.value
        );

    }

}


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT UI
|--------------------------------------------------------------------------
*/

function updatePaymentUI(
    method
) {

    const paymentSections =
        document.querySelectorAll(
            '[data-payment-section]'
        );


    paymentSections.forEach(
        function (section) {

            const sectionMethod =
                section.dataset.paymentSection;


            if (
                !sectionMethod ||
                sectionMethod === method
            ) {

                section.style.display =
                    '';

            } else {

                section.style.display =
                    'none';

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| CHECKOUT FORM
|--------------------------------------------------------------------------
*/

function bindCheckoutForm() {

    const forms =
        document.querySelectorAll(
            '#checkout-form, .checkout-form'
        );


    forms.forEach(
        function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    if (
                        !validateCheckoutForm(
                            form
                        )
                    ) {

                        event.preventDefault();

                        return;

                    }


                    const button =
                        form.querySelector(
                            'button[type="submit"]'
                        );


                    if (button) {

                        button.disabled =
                            true;


                        const originalText =
                            button.textContent;


                        button.dataset.originalText =
                            originalText;


                        button.textContent =
                            'Processing...';

                    }

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| VALIDATE CHECKOUT FORM
|--------------------------------------------------------------------------
*/

function validateCheckoutForm(
    form
) {

    clearCheckoutErrors(
        form
    );


    let valid = true;


    /*
    |--------------------------------------------------------------------------
    | DELIVERY
    |--------------------------------------------------------------------------
    */

    const deliveryMethod =
        form.querySelector(
            'input[name="delivery_method"]:checked'
        );


    if (!deliveryMethod) {

        showCheckoutError(
            form,
            'Please select a delivery method.'
        );

        valid = false;

    }


    /*
    |--------------------------------------------------------------------------
    | ADDRESS
    |--------------------------------------------------------------------------
    */

    if (deliveryMethod) {

        const method =
            deliveryMethod.value
                .toLowerCase();


        const isPickup =
            method.includes(
                'pickup'
            );


        if (!isPickup) {

            const address =
                form.querySelector(
                    '[name="address"], [name="shipping_address"]'
                );


            if (
                address &&
                address.value.trim() === ''
            ) {

                markInvalid(
                    address,
                    'Please enter your delivery address.'
                );

                valid = false;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    */

    const paymentMethod =
        form.querySelector(
            'input[name="payment_method"]:checked'
        );


    if (!paymentMethod) {

        showCheckoutError(
            form,
            'Please select a payment method.'
        );

        valid = false;

    }


    /*
    |--------------------------------------------------------------------------
    | PHONE
    |--------------------------------------------------------------------------
    */

    const phone =
        form.querySelector(
            '[name="phone"]'
        );


    if (
        phone &&
        phone.value.trim() !== ''
    ) {

        const phonePattern =
            /^[0-9+\-\s]{8,15}$/;


        if (
            !phonePattern.test(
                phone.value.trim()
            )
        ) {

            markInvalid(
                phone,
                'Please enter a valid phone number.'
            );

            valid = false;

        }

    }


    return valid;

}


/*
|--------------------------------------------------------------------------
| INVALID FIELD
|--------------------------------------------------------------------------
*/

function markInvalid(
    field,
    message
) {

    field.classList.add(
        'checkout-invalid'
    );


    field.setAttribute(
        'aria-invalid',
        'true'
    );


    let error =
        field.parentElement
            ?.querySelector(
                '.checkout-field-error'
            );


    if (!error) {

        error =
            document.createElement(
                'small'
            );

        error.className =
            'checkout-field-error';


        field.parentElement?.appendChild(
            error
        );

    }


    error.textContent =
        message;


    field.addEventListener(
        'input',
        function removeError() {

            field.classList.remove(
                'checkout-invalid'
            );

            field.removeAttribute(
                'aria-invalid'
            );

            error.remove();

            field.removeEventListener(
                'input',
                removeError
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| CLEAR CHECKOUT ERRORS
|--------------------------------------------------------------------------
*/

function clearCheckoutErrors(
    form
) {

    form.querySelectorAll(
        '.checkout-invalid'
    ).forEach(
        function (field) {

            field.classList.remove(
                'checkout-invalid'
            );

            field.removeAttribute(
                'aria-invalid'
            );

        }
    );


    form.querySelectorAll(
        '.checkout-field-error'
    ).forEach(
        function (error) {

            error.remove();

        }
    );


    form.querySelectorAll(
        '.checkout-form-error'
    ).forEach(
        function (error) {

            error.remove();

        }
    );

}


/*
|--------------------------------------------------------------------------
| CHECKOUT FORM ERROR
|--------------------------------------------------------------------------
*/

function showCheckoutError(
    form,
    message
) {

    let error =
        form.querySelector(
            '.checkout-form-error'
        );


    if (!error) {

        error =
            document.createElement(
                'div'
            );

        error.className =
            'checkout-form-error';


        form.prepend(
            error
        );

    }


    error.textContent =
        message;


    error.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

}


/*
|--------------------------------------------------------------------------
| QUANTITY CHANGES
|--------------------------------------------------------------------------
*/

function bindQuantityChanges() {

    const inputs =
        document.querySelectorAll(
            '.checkout-quantity, [data-checkout-quantity]'
        );


    inputs.forEach(
        function (input) {

            input.addEventListener(
                'change',
                function () {

                    calculateCheckoutTotal();

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| CALCULATE CHECKOUT TOTAL
|--------------------------------------------------------------------------
*/

function calculateCheckoutTotal() {

    let subtotal = 0;


    const items =
        document.querySelectorAll(
            '[data-checkout-item], .checkout-item'
        );


    items.forEach(
        function (item) {

            const priceElement =
                item.querySelector(
                    '[data-item-price], .checkout-item-price'
                );


            const quantityElement =
                item.querySelector(
                    'input[type="number"], [data-item-quantity], .checkout-item-quantity'
                );


            if (
                !priceElement ||
                !quantityElement
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
                    quantityElement.value ||
                    quantityElement.textContent,
                    10
                ) || 0;


            subtotal +=
                price * quantity;

        }
    );


    /*
    |--------------------------------------------------------------------------
    | If PHP already provides subtotal
    |--------------------------------------------------------------------------
    */

    const subtotalElement =
        document.querySelector(
            '[data-checkout-subtotal]'
        );


    if (
        subtotalElement &&
        subtotal === 0
    ) {

        subtotal =
            parseFloat(
                subtotalElement.dataset.amount
            ) || 0;

    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERY FEE
    |--------------------------------------------------------------------------
    */

    let deliveryFee = 0;


    const selectedDelivery =
        document.querySelector(
            'input[name="delivery_method"]:checked'
        );


    if (selectedDelivery) {

        const selectedContainer =
            selectedDelivery.closest(
                '[data-delivery-option], .delivery-option'
            );


        if (selectedContainer) {

            deliveryFee =
                parseFloat(
                    selectedContainer.dataset.fee ||
                    selectedDelivery.dataset.fee ||
                    0
                ) || 0;

        } else {

            deliveryFee =
                parseFloat(
                    selectedDelivery.dataset.fee ||
                    0
                ) || 0;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK DELIVERY ELEMENT
    |--------------------------------------------------------------------------
    */

    const deliveryElement =
        document.querySelector(
            '[data-checkout-delivery]'
        );


    if (
        deliveryElement &&
        selectedDelivery
    ) {

        const fee =
            parseFloat(
                deliveryElement.dataset.amount
            );


        if (!isNaN(fee)) {

            deliveryFee = fee;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    */

    const total =
        subtotal +
        deliveryFee;


    /*
    |--------------------------------------------------------------------------
    | UPDATE UI
    |--------------------------------------------------------------------------
    */

    updateCheckoutMoney(
        '[data-checkout-subtotal]',
        subtotal
    );


    updateCheckoutMoney(
        '[data-checkout-delivery]',
        deliveryFee
    );


    updateCheckoutMoney(
        '[data-checkout-total]',
        total
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE MONEY
|--------------------------------------------------------------------------
*/

function updateCheckoutMoney(
    selector,
    amount
) {

    const elements =
        document.querySelectorAll(
            selector
        );


    elements.forEach(
        function (element) {

            element.textContent =
                formatCheckoutMoney(
                    amount
                );

        }
    );

}


/*
|--------------------------------------------------------------------------
| FORMAT MONEY
|--------------------------------------------------------------------------
*/

function formatCheckoutMoney(
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
| PREVENT DOUBLE SUBMISSION
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'beforeunload',
    function () {

        const forms =
            document.querySelectorAll(
                '#checkout-form, .checkout-form'
            );


        forms.forEach(
            function (form) {

                const button =
                    form.querySelector(
                        'button[type="submit"]'
                    );


                if (
                    button &&
                    button.disabled
                ) {

                    button.disabled =
                        false;


                    if (
                        button.dataset.originalText
                    ) {

                        button.textContent =
                            button.dataset.originalText;

                    }

                }

            }
        );

    }
);


/*
|--------------------------------------------------------------------------
| GLOBAL FUNCTIONS
|--------------------------------------------------------------------------
*/

window.calculateCheckoutTotal =
    calculateCheckoutTotal;

window.validateCheckoutForm =
    validateCheckoutForm;

window.updateDeliveryUI =
    updateDeliveryUI;

window.updatePaymentUI =
    updatePaymentUI;