<?php
/*
|--------------------------------------------------------------------------
| HochipoHub Database Connection
|--------------------------------------------------------------------------
| Database:
| - hochipohub
|
| Connection:
| - MySQLi
|
| Used by:
| - index.php
| - product.php
| - cart.php
| - auth/*
| - seller/*
| - admin/*
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) . '/config.php';



/*
|--------------------------------------------------------------------------
| Create Database Connection
|--------------------------------------------------------------------------
*/


$conn = new mysqli(

    DB_HOST,

    DB_USER,

    DB_PASS,

    DB_NAME

);



/*
|--------------------------------------------------------------------------
| Check Connection
|--------------------------------------------------------------------------
*/


if ($conn->connect_error) {


    if (DEVELOPMENT_MODE) {


        die(

            "Database Connection Failed: "

            . $conn->connect_error

        );


    } else {


        die(

            "Unable to connect database."

        );


    }


}



/*
|--------------------------------------------------------------------------
| Set Charset
|--------------------------------------------------------------------------
*/


$conn->set_charset(

    "utf8mb4"

);



/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
| Escape database input
|--------------------------------------------------------------------------
*/


function escape($value)

{

    global $conn;


    return $conn->real_escape_string(

        trim($value)

    );

}



?>