<?php

require_once dirname(__DIR__) . '/database/db.php';



/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

function redirect($url){

    header(
        "Location: ".$url
    );

    exit();

}



/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

function setFlashMessage($type,$message){

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

function getCartCount($user_id){

    global $conn;


    $sql="
    SELECT SUM(quantity) AS total

    FROM cart

    WHERE customer_id='$user_id'
    ";


    $result=$conn->query($sql);


    $row=$result->fetch_assoc();


    return $row['total'] ?? 0;

}



/*
|--------------------------------------------------------------------------
| Wishlist Count
|--------------------------------------------------------------------------
*/

function getWishlistCount($user_id){

    global $conn;


    $sql="
    SELECT COUNT(*) AS total

    FROM wishlist

    WHERE user_id='$user_id'
    ";


    $result=$conn->query($sql);


    $row=$result->fetch_assoc();


    return $row['total'] ?? 0;

}



/*
|--------------------------------------------------------------------------
| Sanitize
|--------------------------------------------------------------------------
*/

function clean($data){

    return htmlspecialchars(
        trim($data),
        ENT_QUOTES,
        'UTF-8'
    );

}


?>