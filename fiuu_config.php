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
| IMPORTANT:
| Do NOT use localhost here.
|
| Current ngrok public URL:
| https://zippy-shifty-treat.ngrok-free.dev
|
| HochipoHub project:
| /hochipohub
|
| Do NOT put "/" at the end.
|--------------------------------------------------------------------------
*/

define(
    'HOCHIPOHUB_PAYMENT_BASE_URL',
    'https://zippy-shifty-treat.ngrok-free.dev/hochipohub'
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
| Customer browser will return here after Fiuu payment.
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
| Fiuu server sends the actual payment confirmation here.
|
| This MUST be publicly accessible.
|
| Current callback:
| https://zippy-shifty-treat.ngrok-free.dev/hochipohub/fiuu_callback.php
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
| Customer is returned here when payment is cancelled.
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