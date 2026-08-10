<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - GLOBAL CONFIGURATION
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| ERROR REPORTING
|--------------------------------------------------------------------------
|
| Development mode.
| Tukar kepada false bila deploy ke production.
|
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
| FILE / UPLOAD PATHS
|--------------------------------------------------------------------------
*/

define(
    'ROOT_PATH',
    dirname(__FILE__) . DIRECTORY_SEPARATOR
);

define(
    'UPLOAD_PATH',
    ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR
);

define(
    'PRODUCT_UPLOAD_PATH',
    UPLOAD_PATH . 'products' . DIRECTORY_SEPARATOR
);

define(
    'VENDOR_UPLOAD_PATH',
    UPLOAD_PATH . 'vendors' . DIRECTORY_SEPARATOR
);


/*
|--------------------------------------------------------------------------
| IMAGE URLS
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

define(
    'UPLOAD_URL',
    BASE_URL . 'uploads/'
);


/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

define(
    'SESSION_NAME',
    'hochipo_session'
);


/*
|--------------------------------------------------------------------------
| PASSWORD RESET / MFA
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
|
| Commission rate boleh diubah kemudian.
| Database commission table memang menyokong rate ini.
|
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

function baseUrl(string $path = ''): string
{
    return BASE_URL . ltrim(
        $path,
        '/\\'
    );
}


/*
|--------------------------------------------------------------------------
| HELPER - ASSET URL
|--------------------------------------------------------------------------
*/

function assetUrl(string $path): string
{
    return BASE_URL . ltrim(
        $path,
        '/\\'
    );
}


/*
|--------------------------------------------------------------------------
| HELPER - PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

function productImageUrl(
    ?string $image
): string {

    if (
        empty($image)
    ) {

        return BASE_URL
            . 'image/product/default-product.jpg';
    }

    /*
    |--------------------------------------------------------------------------
    | If database already stores a full path
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            $image,
            '/'
        )
        ||
        str_contains(
            $image,
            '\\'
        )
    ) {

        return BASE_URL . ltrim(
            str_replace(
                '\\',
                '/',
                $image
            ),
            '/'
        );
    }

    return PRODUCT_IMAGE_URL
        . rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| HELPER - VENDOR IMAGE
|--------------------------------------------------------------------------
*/

function vendorImageUrl(
    ?string $image
): string {

    if (
        empty($image)
    ) {

        return BASE_URL
            . 'image/vendors/default-vendor.jpg';
    }

    if (
        str_contains(
            $image,
            '/'
        )
        ||
        str_contains(
            $image,
            '\\'
        )
    ) {

        return BASE_URL . ltrim(
            str_replace(
                '\\',
                '/',
                $image
            ),
            '/'
        );
    }

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
| One centralized connection.
|
*/

function getDB(): PDO
{
    static $pdo = null;

    if (
        $pdo instanceof PDO
    ) {

        return $pdo;
    }


    $dsn =
        'mysql:host='
        . DB_HOST
        . ';dbname='
        . DB_NAME
        . ';charset='
        . DB_CHARSET;


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

        if (APP_DEBUG) {

            die(
                '<div style="
                    font-family:Arial;
                    padding:30px;
                    background:#0f172a;
                    color:#fff;
                    min-height:100vh;
                ">
                    <h2 style="color:#60a5fa;">
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
                    ">'
                    . e($e->getMessage())
                    . '</pre>

                    <p>
                        Check:
                    </p>

                    <ul>
                        <li>Laragon / MySQL is running</li>
                        <li>Database name is <strong>hochipohub</strong></li>
                        <li>MySQL username is correct</li>
                        <li>MySQL password is correct</li>
                    </ul>
                </div>'
            );
        }

        http_response_code(500);

        exit(
            'Database connection failed.'
        );
    }
}