<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}


/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

function isLogin(){

    return isset($_SESSION['user_id']);

}


/*
|--------------------------------------------------------------------------
| Alias
|--------------------------------------------------------------------------
*/

function isLoggedIn(){

    return isset($_SESSION['user_id']);

}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

function currentUserId(){

    return $_SESSION['user_id'] ?? null;

}


function currentUserName(){

    return $_SESSION['user_name'] ?? null;

}


function currentUserRole(){

    return $_SESSION['role'] ?? null;

}


/*
|--------------------------------------------------------------------------
| Login Session
|--------------------------------------------------------------------------
*/

function loginUser($user){

    $_SESSION['user_id'] =
        $user['user_id'];

    $_SESSION['user_name'] =
        $user['name'];

    $_SESSION['role'] =
        $user['role'];

}


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

function logoutUser(){

    session_unset();

    session_destroy();

}



?>