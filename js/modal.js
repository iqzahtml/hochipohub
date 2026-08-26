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
    | INITIALIZE MODAL SYSTEM
    |--------------------------------------------------------------------------
    */

    function initModalSystem() {

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

            setTimeout(function () {

                const firstInput =
                    modal.querySelector(
                        'input:not([type="hidden"])'
                    );


                if (firstInput) {

                    firstInput.focus();

                }

            }, 200);

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


            /*
            |--------------------------------------------------------------------------
            | ONLY REMOVE BODY LOCK IF BOTH MODALS CLOSED
            |--------------------------------------------------------------------------
            */

            const loginIsOpen =
                loginModal &&
                (
                    loginModal.classList.contains('active') ||
                    loginModal.classList.contains('show')
                );


            const registerIsOpen =
                registerModal &&
                (
                    registerModal.classList.contains('active') ||
                    registerModal.classList.contains('show')
                );


            if (!loginIsOpen && !registerIsOpen) {

                document.body.classList.remove(
                    'modal-open'
                );

            }

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


            setTimeout(function () {

                if (targetModal) {

                    openModal(
                        targetModal
                    );

                }

            }, 150);

        }


        /*
        |--------------------------------------------------------------------------
        | OPEN / SWITCH MODAL BUTTON
        |--------------------------------------------------------------------------
        |
        | SUPPORT:
        |
        | data-modal-open="loginModal"
        | data-modal-open="registerModal"
        |
        | data-modal-target="loginModal"
        | data-modal-target="registerModal"
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function (event) {

                /*
                |--------------------------------------------------------------------------
                | SAFETY CHECK
                |--------------------------------------------------------------------------
                */

                if (
                    !event.target ||
                    typeof event.target.closest !== 'function'
                ) {

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | FIND BUTTON
                |--------------------------------------------------------------------------
                */

                const button =
                    event.target.closest(
                        '[data-modal-open], [data-modal-target]'
                    );


                if (!button) {

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | GET TARGET
                |--------------------------------------------------------------------------
                */

                let targetId =
                    button.getAttribute(
                        'data-modal-open'
                    );


                /*
                |--------------------------------------------------------------------------
                | FALLBACK
                |--------------------------------------------------------------------------
                */

                if (!targetId) {

                    targetId =
                        button.getAttribute(
                            'data-modal-target'
                        );

                }


                if (!targetId) {

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | GET MODAL
                |--------------------------------------------------------------------------
                */

                const targetModal =
                    document.getElementById(
                        targetId
                    );


                if (!targetModal) {

                    console.warn(
                        'HOCHIPOHUB Modal not found:',
                        targetId
                    );

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | STOP NORMAL BUTTON ACTION
                |--------------------------------------------------------------------------
                */

                event.preventDefault();

                event.stopPropagation();


                /*
                |--------------------------------------------------------------------------
                | CURRENT MODAL
                |--------------------------------------------------------------------------
                */

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
                | NORMAL OPEN
                |--------------------------------------------------------------------------
                */

                else {

                    openModal(
                        targetModal
                    );

                }

            },
            true
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE BUTTON
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function (event) {

                if (
                    !event.target ||
                    typeof event.target.closest !== 'function'
                ) {

                    return;

                }


                const closeButton =
                    event.target.closest(
                        '[data-modal-close]'
                    );


                if (!closeButton) {

                    return;

                }


                event.preventDefault();

                event.stopPropagation();


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

            },
            true
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

                    return;

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
                    event.target === termsModal
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
                        termsModal.classList.contains(
                            'show'
                        ) ||
                        termsModal.classList.contains(
                            'active'
                        )
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
                    (
                        loginModal.classList.contains(
                            'active'
                        ) ||
                        loginModal.classList.contains(
                            'show'
                        )
                    )
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
                    (
                        registerModal.classList.contains(
                            'active'
                        ) ||
                        registerModal.classList.contains(
                            'show'
                        )
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


            setTimeout(function () {

                const loginEmail =
                    document.getElementById(
                        'loginEmail'
                    );


                if (loginEmail) {

                    loginEmail.focus();

                }

            }, 300);


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
        | index.php?register=1
        |--------------------------------------------------------------------------
        */

        if (
            registerModal &&
            urlParams.get('register') === '1'
        ) {

            openModal(
                registerModal
            );


            setTimeout(function () {

                const registerEmail =
                    document.getElementById(
                        'registerEmail'
                    );


                if (registerEmail) {

                    registerEmail.focus();

                }

            }, 300);


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
        | PHP ALREADY OPENED REGISTER MODAL
        |--------------------------------------------------------------------------
        */

        if (
            registerModal &&
            (
                registerModal.classList.contains(
                    'active'
                ) ||
                registerModal.classList.contains(
                    'show'
                )
            )
        ) {

            registerModal.classList.add(
                'active'
            );

            registerModal.classList.add(
                'show'
            );


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
            (
                loginModal.classList.contains(
                    'active'
                ) ||
                loginModal.classList.contains(
                    'show'
                )
            )
        ) {

            loginModal.classList.add(
                'active'
            );

            loginModal.classList.add(
                'show'
            );


            loginModal.setAttribute(
                'aria-hidden',
                'false'
            );


            document.body.classList.add(
                'modal-open'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | START
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initModalSystem
        );

    } else {

        initModalSystem();

    }


})();