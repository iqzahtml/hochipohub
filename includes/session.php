<?php

if(session_status() == PHP_SESSION_NONE){

    session_start();

}



function isLogin(){

    return isset($_SESSION['user_id']);

}




function requireLogin(){

    if(!isLogin()){

        header("Location: ../auth/login.php");

        exit();

    }

}




function userRole(){

    return $_SESSION['role'] ?? null;

}




function requireRole($role){


    if(!isset($_SESSION['role']) || $_SESSION['role'] != $role){


        header("Location: ../hochipohub/index.php");

        exit();


    }


}

?>