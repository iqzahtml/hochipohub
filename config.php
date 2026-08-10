<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL CONFIGURATION
|--------------------------------------------------------------------------
| File:
| config.php
|
| Purpose:
| Central configuration for the whole HochipoHub system.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ERROR REPORTING
|--------------------------------------------------------------------------
*/

define('APP_DEBUG', true);

if (APP_DEBUG) {

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

} else {

    error_reporting(0);
    ini_set('display_errors', '0');
}


/*
|--------------------------------------------------------------------------
| APPLICATION
|--------------------------------------------------------------------------
*/

define(
    'APP_NAME',
    'HochipoHub'
);


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
|
| Laragon:
| http://localhost/hochipohub/
|
*/

define(
    'BASE_URL',
    'http://localhost/hochipohub/'
);


/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
|
| Database:
| hochipohub
|
| DO NOT create another PDO connection in other files.
| Use:
|
| $db = getDB();
|
|--------------------------------------------------------------------------
*/

define(
    'DB_HOST',
    'localhost'
);

define(
    'DB_NAME',
    'hochipohub'
);

define(
    'DB_USER',
    'root'
);

define(
    'DB_PASS',
    ''
);

define(
    'DB_CHARSET',
    'utf8mb4'
);


/*
|--------------------------------------------------------------------------
| ROOT PATH
|--------------------------------------------------------------------------
*/

define(
    'ROOT_PATH',
    __DIR__ . DIRECTORY_SEPARATOR
);


/*
|--------------------------------------------------------------------------
| UPLOAD PATHS
|--------------------------------------------------------------------------
|
| Structure:
|
| uploads/
| ├── products/
| └── vendors/
|
|--------------------------------------------------------------------------
*/

define(
    'UPLOAD_PATH',
    ROOT_PATH
    . 'uploads'
    . DIRECTORY_SEPARATOR
);

define(
    'PRODUCT_UPLOAD_PATH',
    UPLOAD_PATH
    . 'products'
    . DIRECTORY_SEPARATOR
);

define(
    'VENDOR_UPLOAD_PATH',
    UPLOAD_PATH
    . 'vendors'
    . DIRECTORY_SEPARATOR
);


/*
|--------------------------------------------------------------------------
| UPLOAD URL
|--------------------------------------------------------------------------
*/

define(
    'UPLOAD_URL',
    BASE_URL . 'uploads/'
);


/*
|--------------------------------------------------------------------------
| IMAGE PATHS
|--------------------------------------------------------------------------
|
| Structure:
|
| image/
| ├── banner.jpg
| ├── logo.jpg
| ├── product/
| │   └── default-product.jpg
| └── vendors/
|     └── default-vendor.jpg
|
|--------------------------------------------------------------------------
*/

define(
    'IMAGE_URL',
    BASE_URL . 'image/'
);

define(
    'PRODUCT_IMAGE_URL',
    IMAGE_URL . 'product/'
);

define(
    'VENDOR_IMAGE_URL',
    IMAGE_URL . 'vendors/'
);


/*
|--------------------------------------------------------------------------
| SECURITY / SESSION
|--------------------------------------------------------------------------
*/

define(
    'SESSION_NAME',
    'hochipo_session'
);


/*
|--------------------------------------------------------------------------
| PASSWORD RESET / OTP
|--------------------------------------------------------------------------
*/

define(
    'OTP_EXPIRY_MINUTES',
    10
);


/*
|--------------------------------------------------------------------------
| MARKETPLACE SETTINGS
|--------------------------------------------------------------------------
*/

define(
    'DEFAULT_COMMISSION_RATE',
    5.00
);


/*
|--------------------------------------------------------------------------
| CURRENCY
|--------------------------------------------------------------------------
*/

define(
    'CURRENCY',
    'RM'
);

define(
    'CURRENCY_SYMBOL',
    'RM'
);


/*
|--------------------------------------------------------------------------
| TIMEZONE
|--------------------------------------------------------------------------
*/

date_default_timezone_set(
    'Asia/Kuala_Lumpur'
);


/*
|--------------------------------------------------------------------------
| HELPER - BASE URL
|--------------------------------------------------------------------------
*/

function baseUrl(
    string $path = ''
): string {

    return BASE_URL
        . ltrim(
            $path,
            '/\\'
        );
}


/*
|--------------------------------------------------------------------------
| HELPER - ASSET URL
|--------------------------------------------------------------------------
*/

function assetUrl(
    string $path
): string {

    return BASE_URL
        . ltrim(
            $path,
            '/\\'
        );
}


/*
|--------------------------------------------------------------------------
| HELPER - PRODUCT IMAGE URL
|--------------------------------------------------------------------------
*/

function productImageUrl(
    ?string $image
): string {

    /*
    |--------------------------------------------------------------------------
    | Default image
    |--------------------------------------------------------------------------
    */

    if (
        empty($image)
    ) {

        return PRODUCT_IMAGE_URL
            . 'default-product.jpg';
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Windows path
    |--------------------------------------------------------------------------
    */

    $image = str_replace(
        '\\',
        '/',
        $image
    );


    /*
    |--------------------------------------------------------------------------
    | Already a full URL
    |--------------------------------------------------------------------------
    */

    if (
        filter_var(
            $image,
            FILTER_VALIDATE_URL
        )
    ) {

        return $image;
    }


    /*
    |--------------------------------------------------------------------------
    | Remove leading slash
    |--------------------------------------------------------------------------
    */

    $image = ltrim(
        $image,
        '/'
    );


    /*
    |--------------------------------------------------------------------------
    | Already contains image/ or uploads/
    |--------------------------------------------------------------------------
    */

    if (
        str_starts_with(
            $image,
            'image/'
        )
        ||
        str_starts_with(
            $image,
            'uploads/'
        )
    ) {

        return BASE_URL . $image;
    }


    /*
    |--------------------------------------------------------------------------
    | Filename only
    |--------------------------------------------------------------------------
    */

    return PRODUCT_IMAGE_URL
        . rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| HELPER - VENDOR IMAGE URL
|--------------------------------------------------------------------------
*/

function vendorImageUrl(
    ?string $image
): string {

    /*
    |--------------------------------------------------------------------------
    | Default image
    |--------------------------------------------------------------------------
    */

    if (
        empty($image)
    ) {

        return VENDOR_IMAGE_URL
            . 'default-vendor.jpg';
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Windows path
    |--------------------------------------------------------------------------
    */

    $image = str_replace(
        '\\',
        '/',
        $image
    );


    /*
    |--------------------------------------------------------------------------
    | Already a full URL
    |--------------------------------------------------------------------------
    */

    if (
        filter_var(
            $image,
            FILTER_VALIDATE_URL
        )
    ) {

        return $image;
    }


    /*
    |--------------------------------------------------------------------------
    | Remove leading slash
    |--------------------------------------------------------------------------
    */

    $image = ltrim(
        $image,
        '/'
    );


    /*
    |--------------------------------------------------------------------------
    | Already contains image/ or uploads/
    |--------------------------------------------------------------------------
    */

    if (
        str_starts_with(
            $image,
            'image/'
        )
        ||
        str_starts_with(
            $image,
            'uploads/'
        )
    ) {

        return BASE_URL . $image;
    }


    /*
    |--------------------------------------------------------------------------
    | Filename only
    |--------------------------------------------------------------------------
    */

    return VENDOR_IMAGE_URL
        . rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| HELPER - ESCAPE HTML
|--------------------------------------------------------------------------
*/

function e(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| HELPER - FORMAT PRICE
|--------------------------------------------------------------------------
*/

function formatPrice(
    float|int|string $amount
): string {

    return CURRENCY_SYMBOL
        . ' '
        . number_format(
            (float) $amount,
            2
        );
}


/*
|--------------------------------------------------------------------------
| HELPER - REDIRECT
|--------------------------------------------------------------------------
*/

function redirect(
    string $url
): never {

    header(
        'Location: ' . $url
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CREATE REQUIRED DIRECTORIES
|--------------------------------------------------------------------------
*/

$requiredDirectories = [

    UPLOAD_PATH,

    PRODUCT_UPLOAD_PATH,

    VENDOR_UPLOAD_PATH

];


foreach (
    $requiredDirectories
    as $directory
) {

    if (
        !is_dir($directory)
    ) {

        @mkdir(
            $directory,
            0755,
            true
        );
    }
}


/*
|--------------------------------------------------------------------------
| PDO DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Centralized PDO connection.
|
| Every PHP file should use:
|
| $db = getDB();
|
| Do NOT create another PDO connection.
|
|--------------------------------------------------------------------------
*/

function getDB(): PDO
{
    static $pdo = null;


    /*
    |--------------------------------------------------------------------------
    | Reuse existing connection
    |--------------------------------------------------------------------------
    */

    if (
        $pdo instanceof PDO
    ) {

        return $pdo;
    }


    /*
    |--------------------------------------------------------------------------
    | PDO DSN
    |--------------------------------------------------------------------------
    */

    $dsn =
        'mysql:host='
        . DB_HOST
        . ';dbname='
        . DB_NAME
        . ';charset='
        . DB_CHARSET;


    /*
    |--------------------------------------------------------------------------
    | PDO OPTIONS
    |--------------------------------------------------------------------------
    */

    $options = [

        PDO::ATTR_ERRMODE =>
            PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE =>
            PDO::FETCH_ASSOC,

        PDO::ATTR_EMULATE_PREPARES =>
            false,

        PDO::ATTR_STRINGIFY_FETCHES =>
            false

    ];


    /*
    |--------------------------------------------------------------------------
    | CREATE CONNECTION
    |--------------------------------------------------------------------------
    */

    try {

        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            $options
        );

        return $pdo;

    } catch (
        PDOException $e
    ) {

        /*
        |--------------------------------------------------------------------------
        | DEVELOPMENT ERROR
        |--------------------------------------------------------------------------
        */

        if (
            APP_DEBUG
        ) {

            die(
                '<div style="
                    font-family:Arial,sans-serif;
                    padding:30px;
                    background:#0f172a;
                    color:#fff;
                    min-height:100vh;
                ">

                    <h2 style="
                        color:#60a5fa;
                        margin-bottom:10px;
                    ">
                        HochipoHub Database Error
                    </h2>

                    <p>
                        Unable to connect to MySQL database.
                    </p>

                    <pre style="
                        background:#020617;
                        padding:20px;
                        border-radius:12px;
                        overflow:auto;
                        white-space:pre-wrap;
                    ">'
                    . e(
                        $e->getMessage()
                    )
                    . '</pre>

                    <p>
                        Please check:
                    </p>

                    <ul>

                        <li>
                            Laragon / MySQL is running
                        </li>

                        <li>
                            Database name is
                            <strong>
                                hochipohub
                            </strong>
                        </li>

                        <li>
                            MySQL username is correct
                        </li>

                        <li>
                            MySQL password is correct
                        </li>

                    </ul>

                </div>'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCTION ERROR
        |--------------------------------------------------------------------------
        */

        http_response_code(
            500
        );

        exit(
            'Database connection failed.'
        );
    }
}