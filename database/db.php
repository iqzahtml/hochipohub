<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Database Connection
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| PDO Database Connection
|--------------------------------------------------------------------------
*/

try {

    $dsn = "mysql:host=" . DB_HOST .
           ";dbname=" . DB_NAME .
           ";charset=utf8mb4";

    $options = [

        PDO::ATTR_ERRMODE =>
            PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE =>
            PDO::FETCH_ASSOC,

        PDO::ATTR_EMULATE_PREPARES =>
            false

    ];

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        $options
    );


} catch (PDOException $e) {

    if (DEVELOPMENT_MODE === true) {

        die(
            "Database Connection Failed: " .
            htmlspecialchars($e->getMessage())
        );

    } else {

        die(
            "Unable to connect to the database. Please try again later."
        );
    }
}