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
| - Auto login popup after registration
|--------------------------------------------------------------------------
*/

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | WAIT FOR PAGE
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


                /*
                |--------------------------------------------------------------
                | SHOW MODAL
                |--------------------------------------------------------------
                */

                modal.classList.add(
                    'active'
                );


                modal.classList.add(
                    'show'
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
                | FOCUS FIRST INPUT
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


                modal.classList.remove(
                    'show'
                );


                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );


                /*
                |--------------------------------------------------------------
                | REMOVE BODY LOCK
                |--------------------------------------------------------------
                */

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
            | OPEN / SWITCH MODAL BUTTON
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
                    | SWITCH MODAL
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
                    | OPEN MODAL
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
            | CLICK OUTSIDE MODAL
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'click',
                function (event) {


                    /*
                    |----------------------------------------------------------
                    | LOGIN
                    |----------------------------------------------------------
                    */

                    if (
                        loginModal &&
                        event.target === loginModal
                    ) {

                        closeModal(
                            loginModal
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | REGISTER
                    |----------------------------------------------------------
                    */

                    if (
                        registerModal &&
                        event.target === registerModal
                    ) {

                        closeModal(
                            registerModal
                        );

                    }


                    /*
                    |----------------------------------------------------------
                    | TERMS
                    |----------------------------------------------------------
                    */

                    const termsModal =
                        document.getElementById(
                            'termsModal'
                        );


                    if (
                        termsModal &&
                        event.target === termsModal
                    ) {

                        closeModal(
                            termsModal
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | ESCAPE KEY
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


                    /*
                    |----------------------------------------------------------
                    | TERMS
                    |----------------------------------------------------------
                    */

                    const termsModal =
                        document.getElementById(
                            'termsModal'
                        );


                    if (
                        termsModal &&
                        (
                            termsModal.classList.contains(
                                'show'
                            )
                            ||
                            termsModal.classList.contains(
                                'active'
                            )
                        )
                    ) {

                        closeModal(
                            termsModal
                        );

                        return;

                    }


                    /*
                    |----------------------------------------------------------
                    | LOGIN
                    |----------------------------------------------------------
                    */

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


                    /*
                    |----------------------------------------------------------
                    | REGISTER
                    |----------------------------------------------------------
                    */

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


                        toggle.setAttribute(
                            'aria-pressed',
                            'true'
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


                        toggle.setAttribute(
                            'aria-pressed',
                            'false'
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | AUTOMATIC LOGIN MODAL
            |--------------------------------------------------------------------------
            |
            | After successful registration:
            |
            | register_process.php
            |        ↓
            | index.php?login=1
            |        ↓
            | this code
            |        ↓
            | login modal opens automatically
            |
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


                /*
                |--------------------------------------------------------------
                | OPEN LOGIN
                |--------------------------------------------------------------
                */

                openModal(
                    loginModal
                );


                /*
                |--------------------------------------------------------------
                | FOCUS EMAIL
                |--------------------------------------------------------------
                */

                setTimeout(
                    function () {

                        const loginEmail =
                            document.getElementById(
                                'loginEmail'
                            );


                        if (loginEmail) {

                            loginEmail.focus();

                        }

                    },
                    300
                );


                /*
                |--------------------------------------------------------------
                | REMOVE ?login=1 FROM URL
                |--------------------------------------------------------------
                */

                if (
                    window.history &&
                    window.history.replaceState
                ) {

                    const cleanUrl =
                        window.location.pathname;


                    window.history.replaceState(
                        {},
                        document.title,
                        cleanUrl
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | AUTOMATIC REGISTER MODAL
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
            | PHP ALREADY OPENED LOGIN
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

            }


            /*
            |--------------------------------------------------------------------------
            | PHP ALREADY OPENED REGISTER
            |--------------------------------------------------------------------------
            */

            if (
                registerModal &&
                registerModal.classList.contains(
                    'active'
                )
            ) {

                document.body.classList.add(
                    'modal-open'
                );

            }

        }
    );

})();