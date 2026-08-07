<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Global Functions
|--------------------------------------------------------------------------
*/



/*
|--------------------------------------------------------------------------
| Product Image
|--------------------------------------------------------------------------
*/


function productImage($image){


    if(!empty($image)){


        return BASE_URL.
        "uploads/products/".
        $image;


    }



    return BASE_URL.
    "image/logo.jpg";


}






/*
|--------------------------------------------------------------------------
| Vendor Image
|--------------------------------------------------------------------------
*/


function vendorImage($image){


    if(!empty($image)){


        return BASE_URL.
        "uploads/vendors/".
        $image;


    }



    return BASE_URL.
    "image/logo.jpg";


}






/*
|--------------------------------------------------------------------------
| Price Format
|--------------------------------------------------------------------------
*/


function price($amount){


    return "RM ".
    number_format(
        $amount,
        2
    );


}






/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/


function redirect($page){


    header(

        "Location: ".BASE_URL.$page

    );


    exit();


}






/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/


function setFlashMessage(
    $type,
    $message
){


    $_SESSION['flash']=[


        'type'=>$type,

        'message'=>$message


    ];



}






function getFlashMessage(){


    if(isset($_SESSION['flash'])){


        $msg=$_SESSION['flash'];


        unset($_SESSION['flash']);


        return $msg;


    }


    return null;


}






/*
|--------------------------------------------------------------------------
| Cart Count
|--------------------------------------------------------------------------
*/


function getCartCount($userID){


    global $conn;



    $query="

    SELECT COUNT(*) AS total

    FROM cart

    WHERE user_id='$userID'

    ";



    $result=$conn->query($query);



    return 
    $result->fetch_assoc()['total'] ?? 0;



}






/*
|--------------------------------------------------------------------------
| Wishlist Count
|--------------------------------------------------------------------------
*/


function getWishlistCount($userID){


    global $conn;



    $query="

    SELECT COUNT(*) AS total

    FROM wishlist

    WHERE user_id='$userID'

    ";



    $result=$conn->query($query);



    return 
    $result->fetch_assoc()['total'] ?? 0;



}