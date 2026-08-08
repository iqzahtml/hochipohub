<?php

/*
|--------------------------------------------------------------------------
| HochipoHub - Database Connection
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| DATABASE INSTANCE
|--------------------------------------------------------------------------
|
| Semua page yang perlukan database boleh guna:
|
| $db = getDB();
|
|--------------------------------------------------------------------------
*/

try {

    $db = getDB();

} catch (PDOException $e) {

    if (APP_DEBUG) {

        die(
            '<div style="
                font-family:Arial,sans-serif;
                padding:30px;
                background:#020617;
                color:#e2e8f0;
                min-height:100vh;
            ">

                <div style="
                    max-width:700px;
                    margin:auto;
                    background:#0f172a;
                    padding:30px;
                    border-radius:20px;
                    border:1px solid #1e40af;
                    box-shadow:0 20px 50px rgba(0,0,0,.35);
                ">

                    <div style="
                        font-size:14px;
                        color:#60a5fa;
                        font-weight:bold;
                        margin-bottom:10px;
                    ">
                        HOCHIPOHUB DATABASE
                    </div>

                    <h2 style="
                        margin-top:0;
                        color:#fff;
                    ">
                        Database Connection Failed
                    </h2>

                    <p style="
                        color:#94a3b8;
                        line-height:1.6;
                    ">
                        The application could not connect to the
                        <strong style="color:#60a5fa;">
                            hochipohub
                        </strong>
                        database.
                    </p>

                    <pre style="
                        background:#020617;
                        padding:18px;
                        border-radius:12px;
                        color:#f87171;
                        overflow:auto;
                    ">'
                    . htmlspecialchars(
                        $e->getMessage(),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '</pre>

                    <p style="
                        color:#94a3b8;
                        line-height:1.6;
                    ">
                        Make sure Laragon MySQL is running and that
                        the database credentials in
                        <strong style="color:#60a5fa;">
                            config.php
                        </strong>
                        are correct.
                    </p>

                </div>

            </div>'
        );
    }

    http_response_code(500);

    exit(
        'Unable to connect to database.'
    );
}