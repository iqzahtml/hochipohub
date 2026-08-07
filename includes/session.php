<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Session Management
|--------------------------------------------------------------------------
|
| Handles:
| - Login session
| - User information
| - Role checking
|
|--------------------------------------------------------------------------
*/


if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



/*
|--------------------------------------------------------------------------
| Check User Login
|--------------------------------------------------------------------------
*/


function isLogin()

{

    return isset($_SESSION['user_id']);

}



/*
|--------------------------------------------------------------------------
| Get Current User ID
|--------------------------------------------------------------------------
*/


function currentUserID()

{

    return $_SESSION['user_id'] ?? null;

}



/*
|--------------------------------------------------------------------------
| Get Current User Name
|--------------------------------------------------------------------------
*/


function currentUserName()

{

    return $_SESSION['user_name'] ?? '';

}



/*
|--------------------------------------------------------------------------
| Get Current User Email
|--------------------------------------------------------------------------
*/


function currentUserEmail()

{

    return $_SESSION['user_email'] ?? '';

}



/*
|--------------------------------------------------------------------------
| Get Current User Role
|--------------------------------------------------------------------------
*/


function currentUserRole()

{

    return $_SESSION['role'] ?? '';

}



/*
|--------------------------------------------------------------------------
| Login User
|--------------------------------------------------------------------------
*/


function loginUser($user)

{

    session_regenerate_id(true);



    $_SESSION['user_id'] = $user['user_id'];

    $_SESSION['user_name'] = $user['name'];

    $_SESSION['user_email'] = $user['email'];

    $_SESSION['role'] = $user['role'];

}



/*
|--------------------------------------------------------------------------
| Logout User
|--------------------------------------------------------------------------
*/


function logoutUser()

{

    $_SESSION = [];



    if(isset($_COOKIE[session_name()])){


        setcookie(

            session_name(),

            '',

            time()-42000,

            '/'

        );


    }



    session_destroy();


}



/*
|--------------------------------------------------------------------------
| Role Checking
|--------------------------------------------------------------------------
*/


function checkRole($role)

{

    if(!isset($_SESSION['role'])){


        return false;


    }



    return $_SESSION['role'] === $role;


}



/*
|--------------------------------------------------------------------------
| Customer
|--------------------------------------------------------------------------
*/


function isCustomer()

{

    return checkRole('customer');

}



/*
|--------------------------------------------------------------------------
| Seller / Vendor
|--------------------------------------------------------------------------
*/


function isSeller()

{

    return checkRole('vendor');

}



/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/


function isAdmin()

{

    return checkRole('admin');

}



/*
|--------------------------------------------------------------------------
| Protect Page
|--------------------------------------------------------------------------
*/


function requireLogin()

{

    if(!isLogin()){


        header(

            "Location: "

            .BASE_URL

            ."auth/login.php"

        );


        exit();


    }


}



/*
|--------------------------------------------------------------------------
| Protect Seller Page
|--------------------------------------------------------------------------
*/


function requireSeller()

{

    requireLogin();



    if(!isSeller()){


        header(

            "Location: "

            .BASE_URL

            ."index.php"

        );


        exit();


    }


}



/*
|--------------------------------------------------------------------------
| Protect Admin Page
|--------------------------------------------------------------------------
*/


function requireAdmin()

{

    requireLogin();



    if(!isAdmin()){


        header(

            "Location: "

            .BASE_URL

            ."index.php"

        );


        exit();


    }


}



/*
|--------------------------------------------------------------------------
| Session Timeout
|--------------------------------------------------------------------------
*/


function checkSessionTimeout()

{

    if(isset($_SESSION['last_activity'])){


        if(

            time()

            -

            $_SESSION['last_activity']

            >

            SESSION_TIMEOUT

        ){


            logoutUser();



            header(

                "Location: "

                .BASE_URL

                ."auth/login.php"

            );


            exit();


        }


    }



    $_SESSION['last_activity']=time();


}



checkSessionTimeout();


?>