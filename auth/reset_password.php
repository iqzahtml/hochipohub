<?php

require_once "../config.php";

require_once "../database/db.php";

require_once "../includes/functions.php";

require_once "../includes/session.php";





/*
|--------------------------------------------------------------------------
| Check OTP Verification
|--------------------------------------------------------------------------
*/


if(
    !isset($_SESSION['otp_verified'])
    ||
    !isset($_SESSION['reset_email'])
){


    header(

        "Location: forgot_password.php"

    );


    exit();


}





$email = $_SESSION['reset_email'];






/*
|--------------------------------------------------------------------------
| Reset Password
|--------------------------------------------------------------------------
*/


if($_SERVER['REQUEST_METHOD']=="POST"){



    $password = $_POST['password'];

    $confirmPassword = $_POST['confirm_password'];





    if($password !== $confirmPassword){



        setFlashMessage(

            "error",

            "Password does not match."

        );



    }else{



        $newPassword = password_hash(

            $password,

            PASSWORD_DEFAULT

        );






        $update = $conn->query("


        UPDATE users


        SET password='$newPassword'


        WHERE email='$email'


        ");







        if($update){



            /*
            |--------------------------------------------------------------------------
            | Clear Reset Session
            |--------------------------------------------------------------------------
            */


            unset($_SESSION['reset_email']);

            unset($_SESSION['otp_verified']);





            setFlashMessage(

                "success",

                "Password successfully changed. Please login."

            );




            header(

                "Location: ".BASE_URL."index.php"

            );


            exit();




        }



    }


}



?>



<?php include "../includes/header.php"; ?>



<section class="auth-page">



<div class="auth-box">



<h1>

Create New Password

</h1>




<form method="POST">





<div class="form-group">


<label>

New Password

</label>



<input

type="password"

name="password"

required

>



</div>







<div class="form-group">


<label>

Confirm Password

</label>



<input

type="password"

name="confirm_password"

required

>



</div>







<button

type="submit"

class="btn-primary"

>

Reset Password

</button>




</form>




</div>



</section>



<?php include "../includes/footer.php"; ?>