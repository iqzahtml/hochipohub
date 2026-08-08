<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Database Connection
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

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

    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {

        die(
            '<div style="
                font-family:Arial;
                padding:30px;
                color:#b91c1c;
                background:#fee2e2;
                margin:30px;
                border-radius:12px;
            ">
                <h2>Database Connection Failed</h2>
                <p>' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                ) .
                '</p>
            </div>'
        );

    }

    die(
        'Unable to connect to the database. Please try again later.'
    );
}