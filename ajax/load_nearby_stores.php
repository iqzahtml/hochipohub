<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOAD NEARBY STORES
|--------------------------------------------------------------------------
| File:
| ajax/load_nearby_stores.php
|
| Function:
| - Receive customer latitude and longitude
| - Calculate distance between customer and vendors
| - Filter by radius
| - Sort nearest store first
| - Return JSON to nearby_stores.php
|--------------------------------------------------------------------------
*/

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| LOAD PROJECT FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| RESPONSE FUNCTION
|--------------------------------------------------------------------------
*/

function nearbyJsonResponse(
    bool $success,
    string $message,
    array $data = [],
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    nearbyJsonResponse(
        false,
        'Invalid request method.',
        [],
        405
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $db = getDB();

    if (!($db instanceof PDO)) {

        nearbyJsonResponse(
            false,
            'Database connection is not available.',
            [],
            500
        );
    }

} catch (Throwable $e) {

    nearbyJsonResponse(
        false,
        'Unable to connect to the database.',
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| RECEIVE CUSTOMER LOCATION
|--------------------------------------------------------------------------
*/

$latitudeInput =
    trim(
        (string) (
            $_POST['latitude']
            ?? ''
        )
    );


$longitudeInput =
    trim(
        (string) (
            $_POST['longitude']
            ?? ''
        )
    );


$radiusInput =
    trim(
        (string) (
            $_POST['radius']
            ?? '20'
        )
    );


/*
|--------------------------------------------------------------------------
| VALIDATE LATITUDE
|--------------------------------------------------------------------------
*/

if (
    $latitudeInput === '' ||
    !is_numeric($latitudeInput)
) {

    nearbyJsonResponse(
        false,
        'Invalid customer latitude.',
        [],
        422
    );
}


$customerLatitude =
    (float) $latitudeInput;


if (
    $customerLatitude < -90 ||
    $customerLatitude > 90
) {

    nearbyJsonResponse(
        false,
        'Customer latitude must be between -90 and 90.',
        [],
        422
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE LONGITUDE
|--------------------------------------------------------------------------
*/

if (
    $longitudeInput === '' ||
    !is_numeric($longitudeInput)
) {

    nearbyJsonResponse(
        false,
        'Invalid customer longitude.',
        [],
        422
    );
}


$customerLongitude =
    (float) $longitudeInput;


if (
    $customerLongitude < -180 ||
    $customerLongitude > 180
) {

    nearbyJsonResponse(
        false,
        'Customer longitude must be between -180 and 180.',
        [],
        422
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE RADIUS
|--------------------------------------------------------------------------
|
| Allowed:
| 5
| 10
| 20
| all
|
|--------------------------------------------------------------------------
*/

$allowedRadius = [
    '5',
    '10',
    '20',
    'all'
];


if (
    !in_array(
        strtolower($radiusInput),
        $allowedRadius,
        true
    )
) {

    $radiusInput = '20';
}


$radius =
    strtolower($radiusInput);


/*
|--------------------------------------------------------------------------
| HAVERSINE FORMULA
|--------------------------------------------------------------------------
|
| Earth radius = 6371 KM
|
| Distance:
|
| customer coordinate
|          ↓
|    Haversine Formula
|          ↓
| vendor coordinate
|          ↓
| distance in KM
|
|--------------------------------------------------------------------------
*/

$distanceFormula = "
    (
        6371 * ACOS(
            LEAST(
                1,
                GREATEST(
                    -1,
                    COS(
                        RADIANS(:customer_latitude_1)
                    )
                    *
                    COS(
                        RADIANS(v.latitude)
                    )
                    *
                    COS(
                        RADIANS(v.longitude)
                        -
                        RADIANS(:customer_longitude)
                    )
                    +
                    SIN(
                        RADIANS(:customer_latitude_2)
                    )
                    *
                    SIN(
                        RADIANS(v.latitude)
                    )
                )
            )
        )
    )
";


/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
|
| Only show:
| - Approved vendors
| - Vendor has latitude
| - Vendor has longitude
|
| Product count:
| - Only products that are not Hidden
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        v.vendor_id,
        v.user_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.business_address,
        v.latitude,
        v.longitude,
        v.category,
        v.delivery_method,
        v.postage_fee,
        v.allow_vendor_delivery,
        v.cod_enabled,
        v.vendor_delivery_fee,
        v.commission_rate,
        v.approval_status,

        u.name AS owner_name,

        COUNT(
            DISTINCT p.product_id
        ) AS product_count,

        {$distanceFormula}
        AS distance_km

    FROM vendors v

    INNER JOIN users u
        ON u.user_id = v.user_id

    LEFT JOIN products p
        ON p.vendor_id = v.vendor_id
        AND p.status <> 'Hidden'

    WHERE
        v.approval_status = 'Approved'

        AND v.latitude IS NOT NULL

        AND v.longitude IS NOT NULL

        AND v.latitude BETWEEN -90 AND 90

        AND v.longitude BETWEEN -180 AND 180

    GROUP BY

        v.vendor_id,
        v.user_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.business_address,
        v.latitude,
        v.longitude,
        v.category,
        v.delivery_method,
        v.postage_fee,
        v.allow_vendor_delivery,
        v.cod_enabled,
        v.vendor_delivery_fee,
        v.commission_rate,
        v.approval_status,
        u.name

";


/*
|--------------------------------------------------------------------------
| RADIUS FILTER
|--------------------------------------------------------------------------
|
| We calculate distance first inside a subquery.
| This allows us to filter using distance_km safely.
|
|--------------------------------------------------------------------------
*/

$finalSql = "

    SELECT *

    FROM (
        {$sql}
    ) AS nearby_vendor_list

";


if ($radius !== 'all') {

    $finalSql .= "

        WHERE distance_km <= :radius

    ";
}


$finalSql .= "

    ORDER BY
        distance_km ASC,
        business_name ASC

";


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare(
            $finalSql
        );


    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    |
    | PDO native prepared statements can complain when the same named
    | placeholder is reused.
    |
    | Therefore:
    | customer_latitude_1
    | customer_latitude_2
    |
    | are intentionally separate.
    |
    |--------------------------------------------------------------------------
    */

    $stmt->bindValue(
        ':customer_latitude_1',
        $customerLatitude,
        PDO::PARAM_STR
    );


    $stmt->bindValue(
        ':customer_longitude',
        $customerLongitude,
        PDO::PARAM_STR
    );


    $stmt->bindValue(
        ':customer_latitude_2',
        $customerLatitude,
        PDO::PARAM_STR
    );


    if ($radius !== 'all') {

        $stmt->bindValue(
            ':radius',
            (float) $radius,
            PDO::PARAM_STR
        );
    }


    $stmt->execute();


    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    nearbyJsonResponse(
        false,
        'Unable to load nearby stores.',
        [
            'error' => $e->getMessage()
        ],
        500
    );
}


/*
|--------------------------------------------------------------------------
| FORMAT RESULT
|--------------------------------------------------------------------------
*/

$stores = [];


foreach ($vendors as $vendor) {


    /*
    |--------------------------------------------------------------------------
    | LOGO
    |--------------------------------------------------------------------------
    */

    $logoUrl = null;


    if (
        isset($vendor['business_logo']) &&
        trim(
            (string) $vendor['business_logo']
        ) !== ''
    ) {

        $logoFile =
            basename(
                (string) $vendor['business_logo']
            );


        $logoUrl =
            BASE_URL .
            'uploads/vendors/' .
            rawurlencode(
                $logoFile
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DISTANCE
    |--------------------------------------------------------------------------
    */

    $distance =
        isset($vendor['distance_km'])
            ? (float) $vendor['distance_km']
            : 0.00;


    /*
    |--------------------------------------------------------------------------
    | DISTANCE TEXT
    |--------------------------------------------------------------------------
    */

    if ($distance < 1) {

        $distanceMeters =
            (int) round(
                $distance * 1000
            );


        $distanceText =
            $distanceMeters .
            ' m away';

    } else {

        $distanceText =
            number_format(
                $distance,
                1
            ) .
            ' km away';
    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERY OPTIONS
    |--------------------------------------------------------------------------
    */

    $deliveryOptions = [];


    $deliveryMethod =
        (string) (
            $vendor['delivery_method']
            ?? ''
        );


    if (
        $deliveryMethod === 'Pickup' ||
        $deliveryMethod === 'Both'
    ) {

        $deliveryOptions[] =
            'Pickup';
    }


    if (
        $deliveryMethod === 'Postage' ||
        $deliveryMethod === 'Both'
    ) {

        $deliveryOptions[] =
            'Postage';
    }


    if (
        (int) (
            $vendor['allow_vendor_delivery']
            ?? 0
        ) === 1
    ) {

        $deliveryOptions[] =
            'Vendor Delivery';
    }


    /*
    |--------------------------------------------------------------------------
    | STORE URL
    |--------------------------------------------------------------------------
    |
    | Existing project has vendor.php.
    |
    |--------------------------------------------------------------------------
    */

    $storeUrl =
        BASE_URL .
        'vendor.php?vendor_id=' .
        (int) $vendor['vendor_id'];


    /*
    |--------------------------------------------------------------------------
    | STORE DATA
    |--------------------------------------------------------------------------
    */

    $stores[] = [

        'vendor_id' =>
            (int) $vendor['vendor_id'],

        'business_name' =>
            (string) (
                $vendor['business_name']
                ?? ''
            ),

        'business_logo' =>
            $logoUrl,

        'business_description' =>
            (string) (
                $vendor['business_description']
                ?? ''
            ),

        'business_address' =>
            (string) (
                $vendor['business_address']
                ?? ''
            ),

        'category' =>
            (string) (
                $vendor['category']
                ?? ''
            ),

        'latitude' =>
            (float) $vendor['latitude'],

        'longitude' =>
            (float) $vendor['longitude'],

        'distance_km' =>
            round(
                $distance,
                3
            ),

        'distance_text' =>
            $distanceText,

        'product_count' =>
            (int) (
                $vendor['product_count']
                ?? 0
            ),

        'delivery_method' =>
            $deliveryMethod,

        'delivery_options' =>
            $deliveryOptions,

        'postage_fee' =>
            number_format(
                (float) (
                    $vendor['postage_fee']
                    ?? 0
                ),
                2,
                '.',
                ''
            ),

        'vendor_delivery_enabled' =>
            (
                (int) (
                    $vendor['allow_vendor_delivery']
                    ?? 0
                ) === 1
            ),

        'vendor_delivery_fee' =>
            number_format(
                (float) (
                    $vendor['vendor_delivery_fee']
                    ?? 0
                ),
                2,
                '.',
                ''
            ),

        'cod_enabled' =>
            (
                (int) (
                    $vendor['cod_enabled']
                    ?? 0
                ) === 1
            ),

        'store_url' =>
            $storeUrl
    ];
}


/*
|--------------------------------------------------------------------------
| SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/

nearbyJsonResponse(
    true,
    count($stores) > 0
        ? 'Nearby stores loaded successfully.'
        : 'No stores found within the selected distance.',
    [
        'customer_location' => [

            'latitude' =>
                round(
                    $customerLatitude,
                    8
                ),

            'longitude' =>
                round(
                    $customerLongitude,
                    8
                )
        ],

        'radius' =>
            $radius,

        'store_count' =>
            count($stores),

        'stores' =>
            $stores
    ]
);