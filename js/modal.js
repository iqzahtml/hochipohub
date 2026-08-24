/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - MODAL JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/modal.js
|
| Purpose:
| - Open login modal
| - Open register modal
| - Switch modal
| - Close modal
| - Auto open register after registration error
| - Auto open login after successful registration
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
                document.getElementById('loginModal');

            const registerModal =
                document.getElementById('registerModal');


            /*
            |--------------------------------------------------------------------------
            | OPEN MODAL
            |--------------------------------------------------------------------------
            */

            function openModal(modal) {

                if (!modal) {
                    return;
                }


                modal.classList.add('active');

                modal.classList.add('show');

                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );


                document.body.classList.add(
                    'modal-open'
                );


                /*
                |--------------------------------------------------------------------------
                | FOCUS FIRST INPUT
                |--------------------------------------------------------------------------
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


                modal.classList.remove('active');

                modal.classList.remove('show');

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
                    |--------------------------------------------------------------------------
                    | SWITCH
                    |--------------------------------------------------------------------------
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
                    |--------------------------------------------------------------------------
                    | OPEN
                    |--------------------------------------------------------------------------
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
                    |--------------------------------------------------------------------------
                    | LOGIN
                    |--------------------------------------------------------------------------
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
                    |--------------------------------------------------------------------------
                    | REGISTER
                    |--------------------------------------------------------------------------
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
                    |--------------------------------------------------------------------------
                    | TERMS
                    |--------------------------------------------------------------------------
                    */

                    const termsModal =
                        document.getElementById(
                            'termsModal'
                        );


                    if (
                        termsModal &&
                        event.target === termsModal
                    ) {

                        termsModal.classList.remove(
                            'show'
                        );

                        termsModal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                        document.body.classList.remove(
                            'terms-open'
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

                    if (event.key !== 'Escape') {
                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | TERMS
                    |--------------------------------------------------------------------------
                    */

                    const termsModal =
                        document.getElementById(
                            'termsModal'
                        );


                    if (
                        termsModal &&
                        (
                            termsModal.classList.contains('show') ||
                            termsModal.classList.contains('active')
                        )
                    ) {

                        termsModal.classList.remove(
                            'show'
                        );

                        termsModal.classList.remove(
                            'active'
                        );

                        termsModal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                        document.body.classList.remove(
                            'terms-open'
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN
                    |--------------------------------------------------------------------------
                    */

                    if (
                        loginModal &&
                        loginModal.classList.contains('active')
                    ) {

                        closeModal(
                            loginModal
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | REGISTER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        registerModal &&
                        registerModal.classList.contains('active')
                    ) {

                        closeModal(
                            registerModal
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | URL PARAMETERS
            |--------------------------------------------------------------------------
            */

            const urlParams =
                new URLSearchParams(
                    window.location.search
                );


            /*
            |--------------------------------------------------------------------------
            | SUCCESSFUL REGISTRATION
            |--------------------------------------------------------------------------
            |
            | index.php?login=1
            |--------------------------------------------------------------------------
            */

            if (
                loginModal &&
                urlParams.get('login') === '1'
            ) {

                openModal(
                    loginModal
                );


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
                |--------------------------------------------------------------------------
                | CLEAN URL
                |--------------------------------------------------------------------------
                */

                if (
                    window.history &&
                    window.history.replaceState
                ) {

                    window.history.replaceState(
                        {},
                        document.title,
                        window.location.pathname
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | REGISTRATION ERROR
            |--------------------------------------------------------------------------
            |
            | THIS IS THE IMPORTANT PART.
            |
            | When PHP sends:
            |
            | index.php?register=1
            |
            | the register modal opens automatically.
            |
            |--------------------------------------------------------------------------
            */

            if (
                registerModal &&
                urlParams.get('register') === '1'
            ) {

                /*
                |--------------------------------------------------------------------------
                | OPEN REGISTER MODAL IMMEDIATELY
                |--------------------------------------------------------------------------
                */

                openModal(
                    registerModal
                );


                /*
                |--------------------------------------------------------------------------
                | FOCUS EMAIL
                |--------------------------------------------------------------------------
                */

                setTimeout(
                    function () {

                        const registerEmail =
                            document.getElementById(
                                'registerEmail'
                            );


                        if (registerEmail) {

                            registerEmail.focus();

                        }

                    },
                    300
                );


                /*
                |--------------------------------------------------------------------------
                | CLEAN URL
                |--------------------------------------------------------------------------
                |
                | Remove ?register=1 after modal has opened.
                |
                |--------------------------------------------------------------------------
                */

                if (
                    window.history &&
                    window.history.replaceState
                ) {

                    window.history.replaceState(
                        {},
                        document.title,
                        window.location.pathname
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | PHP ALREADY OPENED REGISTER MODAL
            |--------------------------------------------------------------------------
            */

            if (
                registerModal &&
                registerModal.classList.contains('active')
            ) {

                registerModal.classList.add('show');

                registerModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.classList.add(
                    'modal-open'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | PHP ALREADY OPENED LOGIN MODAL
            |--------------------------------------------------------------------------
            */

            if (
                loginModal &&
                loginModal.classList.contains('active')
            ) {

                loginModal.classList.add('show');

                loginModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.classList.add(
                    'modal-open'
                );

            }

        }
    );

})();