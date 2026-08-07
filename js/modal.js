/*
|--------------------------------------------------------------------------
| HochipoHub Modal Controller
|--------------------------------------------------------------------------
|
| Handles:
| - Login Modal
| - Register Modal
|
|--------------------------------------------------------------------------
*/


document.addEventListener(
    "DOMContentLoaded",
    function(){

        const loginModal =
            document.getElementById("loginModal");


        const registerModal =
            document.getElementById("registerModal");



        /*
        |--------------------------------------------------------------------------
        | Close when click outside
        |--------------------------------------------------------------------------
        */


        window.addEventListener(
            "click",
            function(event){


                if(
                    event.target === loginModal
                ){

                    closeLoginModal();

                }



                if(
                    event.target === registerModal
                ){

                    closeRegisterModal();

                }


            }

        );



        /*
        |--------------------------------------------------------------------------
        | ESC button close
        |--------------------------------------------------------------------------
        */


        document.addEventListener(
            "keydown",
            function(event){


                if(event.key === "Escape"){


                    closeLoginModal();

                    closeRegisterModal();


                }


            }

        );


    }

);





/*
|--------------------------------------------------------------------------
| Open Login Modal
|--------------------------------------------------------------------------
*/


function openLoginModal()

{

    const modal =
        document.getElementById(
            "loginModal"
        );



    if(modal){


        modal.classList.add(
            "active"
        );


        document.body.style.overflow =
            "hidden";


    }


}






/*
|--------------------------------------------------------------------------
| Close Login Modal
|--------------------------------------------------------------------------
*/


function closeLoginModal()

{

    const modal =
        document.getElementById(
            "loginModal"
        );



    if(modal){


        modal.classList.remove(
            "active"
        );


        document.body.style.overflow =
            "auto";


    }


}






/*
|--------------------------------------------------------------------------
| Open Register Modal
|--------------------------------------------------------------------------
*/


function openRegisterModal()

{


    const modal =
        document.getElementById(
            "registerModal"
        );



    if(modal){


        modal.classList.add(
            "active"
        );


        document.body.style.overflow =
            "hidden";


    }


}







/*
|--------------------------------------------------------------------------
| Close Register Modal
|--------------------------------------------------------------------------
*/


function closeRegisterModal()

{


    const modal =
        document.getElementById(
            "registerModal"
        );



    if(modal){


        modal.classList.remove(
            "active"
        );


        document.body.style.overflow =
            "auto";


    }


}








/*
|--------------------------------------------------------------------------
| Switch Login -> Register
|--------------------------------------------------------------------------
*/


function switchRegister()

{


    closeLoginModal();


    setTimeout(

        function(){


            openRegisterModal();


        },

        200

    );


}








/*
|--------------------------------------------------------------------------
| Switch Register -> Login
|--------------------------------------------------------------------------
*/


function switchLogin()

{


    closeRegisterModal();



    setTimeout(

        function(){


            openLoginModal();


        },

        200

    );


}