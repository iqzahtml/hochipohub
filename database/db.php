<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Database Connection
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

/*
|--------------------------------------------------------------------------
| MySQLi Connection
|--------------------------------------------------------------------------
*/

$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

if ($conn->connect_error) {

    if (DEVELOPMENT_MODE === true) {

        die("Database Connection Failed: " . $conn->connect_error);

    } else {

        die("Unable to connect to database.");

    }

}

$conn->set_charset("utf8mb4");

?>