/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - MODAL JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/modal.js
|
| Functions:
| - Open login modal
| - Open register modal
| - Switch modal
| - Close modal
| - Password show/hide
| - Auto login popup support
|--------------------------------------------------------------------------
*/

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | PAGE READY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {


            /*
            |--------------------------------------------------------------------------
            | GET MODALS
            |--------------------------------------------------------------------------
            */

            const loginModal =
                document.getElementById(
                    'loginModal'
                );

            const registerModal =
                document.getElementById(
                    'registerModal'
                );


            /*
            |--------------------------------------------------------------------------
            | OPEN MODAL
            |--------------------------------------------------------------------------
            */

            function openModal(modal) {

                if (!modal) {
                    return;
                }


                modal.classList.add(
                    'active'
                );


                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );


                document.body.classList.add(
                    'modal-open'
                );


                /*
                |--------------------------------------------------------------
                | FOCUS INPUT
                |--------------------------------------------------------------
                */

                setTimeout(
                    function () {

                        const firstInput =
                            modal.querySelector(
                                'input:not([type="hidden"])'
                            );


                        if (firstInput) {

                            firstInput.focus();

                        }

                    },
                    200
                );

            }


            /*
            |--------------------------------------------------------------------------
            | CLOSE MODAL
            |--------------------------------------------------------------------------
            */

            function closeModal(modal) {

                if (!modal) {
                    return;
                }


                modal.classList.remove(
                    'active'
                );


                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );


                document.body.classList.remove(
                    'modal-open'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | SWITCH MODAL
            |--------------------------------------------------------------------------
            */

            function switchModal(
                currentModal,
                targetModal
            ) {

                if (currentModal) {

                    closeModal(
                        currentModal
                    );

                }


                setTimeout(
                    function () {

                        if (targetModal) {

                            openModal(
                                targetModal
                            );

                        }

                    },
                    150
                );

            }


            /*
            |--------------------------------------------------------------------------
            | OPEN MODAL BUTTONS
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'click',
                function (event) {


                    const button =
                        event.target.closest(
                            '[data-modal-target]'
                        );


                    if (!button) {

                        return;

                    }


                    event.preventDefault();


                    const targetId =
                        button.getAttribute(
                            'data-modal-target'
                        );


                    const targetModal =
                        document.getElementById(
                            targetId
                        );


                    const currentId =
                        button.getAttribute(
                            'data-modal-switch'
                        );


                    /*
                    |----------------------------------------------------------
                    | SWITCH
                    |----------------------------------------------------------
                    */

                    if (currentId) {

                        const currentModal =
                            document.getElementById(
                                currentId
                            );


                        switchModal(
                            currentModal,
                            targetModal
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | OPEN
                    |----------------------------------------------------------
                    */

                    else {

                        openModal(
                            targetModal
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | CLOSE BUTTON
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'click',
                function (event) {


                    const closeButton =
                        event.target.closest(
                            '[data-modal-close]'
                        );


                    if (!closeButton) {

                        return;

                    }


                    const modalId =
                        closeButton.getAttribute(
                            'data-modal-close'
                        );


                    const modal =
                        document.getElementById(
                            modalId
                        );


                    closeModal(
                        modal
                    );

                }
            );


            /*
            |--------------------------------------------------------------------------
            | CLICK OUTSIDE
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'click',
                function (event) {


                    if (
                        loginModal &&
                        event.target === loginModal
                    ) {

                        closeModal(
                            loginModal
                        );

                    }


                    if (
                        registerModal &&
                        event.target === registerModal
                    ) {

                        closeModal(
                            registerModal
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | ESC
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'keydown',
                function (event) {


                    if (
                        event.key !== 'Escape'
                    ) {

                        return;

                    }


                    if (
                        loginModal &&
                        loginModal.classList.contains(
                            'active'
                        )
                    ) {

                        closeModal(
                            loginModal
                        );

                    }


                    if (
                        registerModal &&
                        registerModal.classList.contains(
                            'active'
                        )
                    ) {

                        closeModal(
                            registerModal
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | PASSWORD SHOW / HIDE
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'click',
                function (event) {


                    const toggle =
                        event.target.closest(
                            '[data-password-target]'
                        );


                    if (!toggle) {

                        return;

                    }


                    event.preventDefault();


                    const targetId =
                        toggle.getAttribute(
                            'data-password-target'
                        );


                    const passwordInput =
                        document.getElementById(
                            targetId
                        );


                    if (!passwordInput) {

                        console.error(
                            'Password input not found: ' +
                            targetId
                        );

                        return;

                    }


                    /*
                    |----------------------------------------------------------
                    | SHOW PASSWORD
                    |----------------------------------------------------------
                    */

                    if (
                        passwordInput.type ===
                        'password'
                    ) {

                        passwordInput.type =
                            'text';


                        toggle.textContent =
                            '🙈';


                        toggle.setAttribute(
                            'aria-label',
                            'Hide password'
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | HIDE PASSWORD
                    |----------------------------------------------------------
                    */

                    else {

                        passwordInput.type =
                            'password';


                        toggle.textContent =
                            '👁';


                        toggle.setAttribute(
                            'aria-label',
                            'Show password'
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | AUTO OPEN LOGIN
            |--------------------------------------------------------------------------
            |
            | This supports both:
            |
            | 1. ?login=1
            |
            | 2. PHP-generated .active class
            |--------------------------------------------------------------------------
            */

            const urlParams =
                new URLSearchParams(
                    window.location.search
                );


            if (
                loginModal &&
                urlParams.get('login') === '1'
            ) {

                openModal(
                    loginModal
                );

            }


            /*
            |--------------------------------------------------------------------------
            | AUTO OPEN REGISTER
            |--------------------------------------------------------------------------
            */

            if (
                registerModal &&
                urlParams.get('register') === '1'
            ) {

                openModal(
                    registerModal
                );

            }


            /*
            |--------------------------------------------------------------------------
            | IF PHP ALREADY OPENED LOGIN
            |--------------------------------------------------------------------------
            |
            | PHP adds:
            |
            | class="modal-overlay active"
            |
            | We make sure body also gets modal-open.
            |--------------------------------------------------------------------------
            */

            if (
                loginModal &&
                loginModal.classList.contains(
                    'active'
                )
            ) {

                document.body.classList.add(
                    'modal-open'
                );


                /*
                |--------------------------------------------------------------
                | FOCUS PASSWORD
                |--------------------------------------------------------------
                */

                const loginPassword =
                    document.getElementById(
                        'loginPassword'
                    );


                if (loginPassword) {

                    setTimeout(
                        function () {

                            loginPassword.focus();

                        },
                        300
                    );

                }

            }


        }
    );

})();