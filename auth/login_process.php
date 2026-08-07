<?php

require_once "../config.php";

require_once "../database/db.php";

require_once "../includes/session.php";



if($_SERVER['REQUEST_METHOD'] !== "POST"){


    header(
        "Location: ".BASE_URL."index.php"
    );


    exit();


}





$email = mysqli_real_escape_string(
    $conn,
    $_POST['email']
);



$password = $_POST['password'];





$query = "

SELECT *

FROM users

WHERE email='$email'

LIMIT 1

";



$result = $conn->query($query);






if($result && $result->num_rows > 0){



    $user = $result->fetch_assoc();





    if(password_verify($password,$user['password'])){





        $_SESSION['user_id'] =
            $user['user_id'];



        $_SESSION['name'] =
            $user['name'];



        $_SESSION['role'] =
            $user['role'];






        /*
        |--------------------------------------------------------------------------
        | Redirect Based On Role
        |--------------------------------------------------------------------------
        */





        if($user['role']=="admin"){


            header(
                "Location: ".BASE_URL."admin/dashboard.php"
            );


        }




        elseif($user['role']=="vendor"){


            header(
                "Location: ".BASE_URL."seller/dashboard.php"
            );


        }





        else{


            header(
                "Location: ".BASE_URL."dashboard.php"
            );


        }





        exit();




    }



}






setFlashMessage(
    "error",
    "Invalid email or password."
);



header(
    "Location: ".BASE_URL."index.php"
);


exit();