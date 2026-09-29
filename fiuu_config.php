<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - FIUU LIVE CONFIGURATION
|--------------------------------------------------------------------------
| Production / Live configuration
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ENVIRONMENT
|--------------------------------------------------------------------------
*/

define(
    'FIUU_ENVIRONMENT',
    'production'
);


/*
|--------------------------------------------------------------------------
| MERCHANT ID
|--------------------------------------------------------------------------
*/

define(
    'FIUU_MERCHANT_ID',
    'hochipohub'
);


/*
|--------------------------------------------------------------------------
| FIUU KEYS
|--------------------------------------------------------------------------
| Replace these with the CURRENT keys from your FIUU Merchant Portal.
| Do not share these keys publicly.
|--------------------------------------------------------------------------
*/

define(
    'FIUU_VERIFY_KEY',
    'c991b4dd2497ba85c06c181f16b9110e'
);

define(
    'FIUU_SECRET_KEY',
    'c991b4dd2497ba85c06c181f16b9110e'
);


/*
|--------------------------------------------------------------------------
| HOCHIPOHUB PUBLIC PAYMENT BASE URL
|--------------------------------------------------------------------------
|
| ngrok already points directly to the HOCHIPOHUB project root.
|
| Therefore:
|
| CORRECT:
| https://zippy-shifty-treat.ngrok-free.dev
|
| WRONG:
| https://zippy-shifty-treat.ngrok-free.dev/hochipohub
|
| Do NOT put "/" at the end.
|--------------------------------------------------------------------------
*/

define(
    'HOCHIPOHUB_PAYMENT_BASE_URL',
    'https://zippy-shifty-treat.ngrok-free.dev'
);


/*
|--------------------------------------------------------------------------
| FIUU LIVE PAYMENT URL
|--------------------------------------------------------------------------
*/

define(
    'FIUU_PAYMENT_BASE_URL',
    'https://pay.fiuu.com/RMS/pay/' .
    FIUU_MERCHANT_ID
);


/*
|--------------------------------------------------------------------------
| RETURN URL
|--------------------------------------------------------------------------
|
| Customer browser returns here after FIUU payment.
|--------------------------------------------------------------------------
*/

define(
    'FIUU_RETURN_URL',
    HOCHIPOHUB_PAYMENT_BASE_URL .
    '/fiuu_return.php'
);


/*
|--------------------------------------------------------------------------
| CALLBACK URL
|--------------------------------------------------------------------------
|
| FIUU server sends payment confirmation here.
|
| This endpoint must be publicly accessible.
|--------------------------------------------------------------------------
*/

define(
    'FIUU_CALLBACK_URL',
    HOCHIPOHUB_PAYMENT_BASE_URL .
    '/fiuu_callback.php'
);


/*
|--------------------------------------------------------------------------
| CANCEL URL
|--------------------------------------------------------------------------
|
| Customer returns here when payment is cancelled.
|--------------------------------------------------------------------------
*/

define(
    'FIUU_CANCEL_URL',
    HOCHIPOHUB_PAYMENT_BASE_URL .
    '/fiuu_return.php?cancel=1'
);


/*
|--------------------------------------------------------------------------
| CURRENCY
|--------------------------------------------------------------------------
*/

define(
    'FIUU_CURRENCY',
    'MYR'
);


/*
|--------------------------------------------------------------------------
| CONFIG DEBUG
|--------------------------------------------------------------------------
*/

define(
    'FIUU_CONFIG_LOADED_FROM',
    __FILE__
);