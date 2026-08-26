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
| - Click outside to close
| - Escape to close
| - Auto open register after registration error
| - Auto open login after successful registration
|--------------------------------------------------------------------------
*/

(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | START MODAL SYSTEM
    |--------------------------------------------------------------------------
    */

    function initModalSystem() {

        const loginModal =
            document.getElementById('loginModal');

        const registerModal =
            document.getElementById('registerModal');


        /*
        |--------------------------------------------------------------------------
        | CHECK MODAL
        |--------------------------------------------------------------------------
        */

        if (!loginModal && !registerModal) {

            console.warn(
                'HOCHIPOHUB: Login/Register modal tidak dijumpai.'
            );

            return;

        }


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
            | Tutup modal lain dahulu
            |--------------------------------------------------------------
            */

            if (
                loginModal &&
                loginModal !== modal
            ) {

                loginModal.classList.remove('active');
                loginModal.classList.remove('show');

                loginModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

            }


            if (
                registerModal &&
                registerModal !== modal
            ) {

                registerModal.classList.remove('active');
                registerModal.classList.remove('show');

                registerModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

            }


            /*
            |--------------------------------------------------------------
            | BUKA MODAL
            |--------------------------------------------------------------
            */

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
            |--------------------------------------------------------------
            | FOCUS INPUT
            |--------------------------------------------------------------
            */

            setTimeout(
                function () {

                    const firstInput =
                        modal.querySelector(
                            'input:not([type="hidden"]):not([type="checkbox"])'
                        );


                    if (firstInput) {

                        firstInput.focus();

                    }

                },
                150
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


            const loginOpen =
                loginModal &&
                (
                    loginModal.classList.contains('active') ||
                    loginModal.classList.contains('show')
                );


            const registerOpen =
                registerModal &&
                (
                    registerModal.classList.contains('active') ||
                    registerModal.classList.contains('show')
                );


            if (
                !loginOpen &&
                !registerOpen
            ) {

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

                currentModal.classList.remove(
                    'active'
                );

                currentModal.classList.remove(
                    'show'
                );

                currentModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

            }


            openModal(
                targetModal
            );

        }


        /*
        |--------------------------------------------------------------------------
        | OPEN LOGIN / REGISTER
        |--------------------------------------------------------------------------
        |
        | Guna event delegation.
        |
        | data-modal-open="loginModal"
        | data-modal-open="registerModal"
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function (event) {

                const button =
                    event.target.closest(
                        '[data-modal-open]'
                    );


                if (!button) {

                    return;

                }


                const targetId =
                    button.getAttribute(
                        'data-modal-open'
                    );


                if (!targetId) {

                    return;

                }


                const targetModal =
                    document.getElementById(
                        targetId
                    );


                if (!targetModal) {

                    console.error(
                        'HOCHIPOHUB: Modal tidak dijumpai:',
                        targetId
                    );

                    return;

                }


                /*
                |----------------------------------------------------------
                | PENTING:
                | Jangan bagi button ikut default action.
                |----------------------------------------------------------
                */

                event.preventDefault();


                const currentModal =
                    button.getAttribute(
                        'data-modal-switch'
                    );


                if (currentModal) {

                    const current =
                        document.getElementById(
                            currentModal
                        );


                    switchModal(
                        current,
                        targetModal
                    );

                } else {

                    openModal(
                        targetModal
                    );

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SWITCH BUTTON
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | data-modal-switch="registerModal"
        | data-modal-target="loginModal"
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


                const targetId =
                    button.getAttribute(
                        'data-modal-target'
                    );


                if (!targetId) {

                    return;

                }


                const targetModal =
                    document.getElementById(
                        targetId
                    );


                if (!targetModal) {

                    console.error(
                        'HOCHIPOHUB: Target modal tidak dijumpai:',
                        targetId
                    );

                    return;

                }


                event.preventDefault();


                let currentModal = null;


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

                    currentModal = loginModal;

                }


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

                    currentModal = registerModal;

                }


                switchModal(
                    currentModal,
                    targetModal
                );

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


                event.preventDefault();


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

                    return;

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
                |----------------------------------------------------------
                | LOGIN
                |----------------------------------------------------------
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
                |----------------------------------------------------------
                | REGISTER
                |----------------------------------------------------------
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
                250
            );

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
                250
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CLEAN URL
        |--------------------------------------------------------------------------
        |
        | Buat selepas check parameter.
        |--------------------------------------------------------------------------
        */

        if (
            (
                urlParams.get('login') === '1' ||
                urlParams.get('register') === '1'
            ) &&
            window.history &&
            window.history.replaceState
        ) {

            window.history.replaceState(
                {},
                document.title,
                window.location.pathname
            );

        }


        /*
        |--------------------------------------------------------------------------
        | PHP ALREADY OPENED MODAL
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

            document.body.classList.add(
                'modal-open'
            );

        }


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

            document.body.classList.add(
                'modal-open'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE
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