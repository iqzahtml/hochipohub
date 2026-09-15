<?php

require_once __DIR__ . '/fiuu_config.php';

header(
    'Content-Type: text/plain; charset=UTF-8'
);


echo "FIUU CONFIG TEST\n";
echo "============================\n\n";


echo "Config File:\n";

echo defined('FIUU_CONFIG_LOADED_FROM')
    ? FIUU_CONFIG_LOADED_FROM
    : 'NOT LOADED';


echo "\n\n";


echo "Environment: ";

echo defined('FIUU_ENVIRONMENT')
    ? FIUU_ENVIRONMENT
    : 'NOT FOUND';


echo "\n";


echo "Merchant ID: ";

echo defined('FIUU_MERCHANT_ID')
    ? FIUU_MERCHANT_ID
    : 'NOT FOUND';


echo "\n";


/*
|--------------------------------------------------------------------------
| VERIFY KEY
|--------------------------------------------------------------------------
*/

echo "Verify Key: ";

if (
    defined('FIUU_VERIFY_KEY') &&
    trim((string) FIUU_VERIFY_KEY) !== '' &&
    FIUU_VERIFY_KEY !==
        'MASUKKAN_VERIFY_KEY_KAT_SINI'
) {

    echo "OK";

} else {

    echo "FAIL";
}


echo "\n";


/*
|--------------------------------------------------------------------------
| SECRET KEY
|--------------------------------------------------------------------------
*/

echo "Secret Key: ";

if (
    defined('FIUU_SECRET_KEY') &&
    trim((string) FIUU_SECRET_KEY) !== '' &&
    FIUU_SECRET_KEY !==
        'MASUKKAN_SECRET_KEY_KAT_SINI'
) {

    echo "OK";

} else {

    echo "FAIL";
}


echo "\n";


/*
|--------------------------------------------------------------------------
| CONFIG READY
|--------------------------------------------------------------------------
*/

echo "Config Ready: ";

if (
    defined('FIUU_CONFIG_READY') &&
    FIUU_CONFIG_READY
) {

    echo "YES";

} else {

    echo "NO";
}


echo "\n";


/*
|--------------------------------------------------------------------------
| RAW CONFIG VALUE
|--------------------------------------------------------------------------
*/

echo "Config Ready Raw Value: ";

if (!defined('FIUU_CONFIG_READY')) {

    echo "NOT DEFINED";

} elseif (FIUU_CONFIG_READY) {

    echo "TRUE";

} else {

    echo "FALSE";
}


echo "\n\n";


/*
|--------------------------------------------------------------------------
| PAYMENT URL
|--------------------------------------------------------------------------
*/

echo "Payment URL:\n";

echo defined('FIUU_PAYMENT_BASE_URL')
    ? FIUU_PAYMENT_BASE_URL
    : 'NOT FOUND';


echo "\n";