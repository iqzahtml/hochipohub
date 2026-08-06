<?php

require_once dirname(__DIR__) . '/config.php';


$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);


if($conn->connect_error){

    die(
        "Database Connection Failed: "
        .$conn->connect_error
    );

}


$conn->set_charset(
    "utf8mb4"
);


?>