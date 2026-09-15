<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CHECKOUT
|--------------------------------------------------------------------------
| File: checkout.php
|
| FINAL VERSION
|
| Supports:
| - Pickup
| - Postage
| - Vendor Delivery
| - Vendor configurable postage fee
| - Vendor configurable delivery fee
| - FPX via Fiuu
| - Credit Card via Fiuu
| - Debit Card via Fiuu
| - Cash at Pickup
| - Cash on Delivery
| - Multi-vendor cart
| - Vendor orders
| - Vendor commission
| - Stock update
| - Inventory update
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/fiuu_config.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

requireLogin();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

$currentRole = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);

if (
    $currentRole !== '' &&
    $currentRole !== 'customer'
) {
    header('Location: dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function checkoutEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function checkoutMoney($value): string
{
    return number_format(
        (float) $value,
        2,
        '.',
        ''
    );
}


function checkoutProductImage($image): string
{
    $image = trim((string) $image);

    if ($image === '') {
        return '';
    }

    if (
        str_starts_with($image, 'http://') ||
        str_starts_with($image, 'https://')
    ) {
        return $image;
    }

    if (
        str_starts_with(
            $image,
            'uploads/'
        )
    ) {
        return $image;
    }

    return
        'uploads/products/' .
        rawurlencode(
            basename($image)
        );
}


function checkoutMapsUrl($address): string
{
    return
        'https://www.google.com/maps/dir/?api=1&destination=' .
        rawurlencode(
            trim((string) $address)
        );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER DETAILS
|--------------------------------------------------------------------------
*/

$customerStmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        phone
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$customerStmt->execute([
    $userId
]);

$customer =
    $customerStmt->fetch(
        PDO::FETCH_ASSOC
    );

if (!$customer) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| VALUES
|--------------------------------------------------------------------------
*/

$error = '';

$selectedDelivery =
    trim(
        (string) (
            $_POST['delivery_method']
            ?? ''
        )
    );

$selectedAddress =
    trim(
        (string) (
            $_POST['delivery_address']
            ?? ''
        )
    );

$selectedPayment =
    trim(
        (string) (
            $_POST['payment_method']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| GET CART
|--------------------------------------------------------------------------
*/

$cartStmt = $db->prepare("
    SELECT

        c.cart_id,
        c.customer_id,
        c.product_id,
        c.quantity,

        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,

        v.vendor_id,
        v.business_name,
        v.business_address,

        v.delivery_method
            AS vendor_delivery_method,

        v.postage_fee,

        v.allow_vendor_delivery,

        v.cod_enabled,

        v.vendor_delivery_fee,

        v.commission_rate

    FROM cart c

    INNER JOIN products p
        ON p.product_id =
           c.product_id

    INNER JOIN vendors v
        ON v.vendor_id =
           p.vendor_id

    WHERE c.customer_id = ?

    ORDER BY
        v.business_name ASC,
        p.product_name ASC
");

$cartStmt->execute([
    $userId
]);

$cartItems =
    $cartStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| EMPTY CART
|--------------------------------------------------------------------------
*/

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| BUILD VENDOR DATA
|--------------------------------------------------------------------------
*/

$vendors = [];

$subtotal = 0.00;

$totalItems = 0;

foreach ($cartItems as $item) {

    $vendorId =
        (int) $item['vendor_id'];

    if (
        !isset(
            $vendors[$vendorId]
        )
    ) {

        $vendors[$vendorId] = [

            'vendor_id' =>
                $vendorId,

            'business_name' =>
                (string) (
                    $item['business_name']
                    ?? 'Seller'
                ),

            'business_address' =>
                trim(
                    (string) (
                        $item['business_address']
                        ?? ''
                    )
                ),

            'delivery_method' =>
                (string) (
                    $item[
                        'vendor_delivery_method'
                    ]
                    ?? 'Both'
                ),

            'postage_fee' =>
                max(
                    0,
                    (float) (
                        $item['postage_fee']
                        ?? 0
                    )
                ),

            'allow_vendor_delivery' =>
                (int) (
                    $item[
                        'allow_vendor_delivery'
                    ]
                    ?? 0
                ),

            'cod_enabled' =>
                (int) (
                    $item[
                        'cod_enabled'
                    ]
                    ?? 0
                ),

            'vendor_delivery_fee' =>
                max(
                    0,
                    (float) (
                        $item[
                            'vendor_delivery_fee'
                        ]
                        ?? 0
                    )
                ),

            'commission_rate' =>
                max(
                    0,
                    min(
                        100,
                        (float) (
                            $item[
                                'commission_rate'
                            ]
                            ?? 5
                        )
                    )
                ),

            'subtotal' =>
                0.00
        ];
    }


    $quantity =
        max(
            0,
            (int) $item['quantity']
        );

    $price =
        max(
            0,
            (float) $item['price']
        );

    $itemSubtotal =
        $quantity *
        $price;


    $vendors[$vendorId]['subtotal'] +=
        $itemSubtotal;


    $subtotal +=
        $itemSubtotal;


    $totalItems +=
        $quantity;
}


/*
|--------------------------------------------------------------------------
| DELIVERY AVAILABILITY
|--------------------------------------------------------------------------
*/

$pickupAvailable = true;

$postageAvailable = true;

$vendorDeliveryAvailable = true;

$vendorDeliveryCodAvailable = true;


foreach ($vendors as $vendor) {

    $deliveryMethod =
        trim(
            (string) $vendor[
                'delivery_method'
            ]
        );


    /*
    |--------------------------------------------------------------------------
    | PICKUP
    |--------------------------------------------------------------------------
    */

    if (
        $deliveryMethod !== 'Pickup' &&
        $deliveryMethod !== 'Both'
    ) {

        $pickupAvailable = false;
    }


    /*
    |--------------------------------------------------------------------------
    | POSTAGE
    |--------------------------------------------------------------------------
    */

    if (
        $deliveryMethod !== 'Postage' &&
        $deliveryMethod !== 'Both'
    ) {

        $postageAvailable = false;
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR DELIVERY
    |--------------------------------------------------------------------------
    */

    if (
        (int) $vendor[
            'allow_vendor_delivery'
        ] !== 1
    ) {

        $vendorDeliveryAvailable = false;
    }


    /*
    |--------------------------------------------------------------------------
    | COD
    |--------------------------------------------------------------------------
    */

    if (
        (int) $vendor[
            'allow_vendor_delivery'
        ] !== 1 ||
        (int) $vendor[
            'cod_enabled'
        ] !== 1
    ) {

        $vendorDeliveryCodAvailable = false;
    }
}


/*
|--------------------------------------------------------------------------
| TOTAL POSTAGE FEE
|--------------------------------------------------------------------------
*/

$postageFee = 0.00;

foreach ($vendors as $vendor) {

    $postageFee +=
        max(
            0,
            (float) $vendor[
                'postage_fee'
            ]
        );
}


/*
|--------------------------------------------------------------------------
| TOTAL VENDOR DELIVERY FEE
|--------------------------------------------------------------------------
*/

$vendorDeliveryFee = 0.00;

foreach ($vendors as $vendor) {

    $vendorDeliveryFee +=
        max(
            0,
            (float) $vendor[
                'vendor_delivery_fee'
            ]
        );
}


/*
|--------------------------------------------------------------------------
| CURRENT DELIVERY FEE
|--------------------------------------------------------------------------
*/

$currentDeliveryFee = 0.00;

if (
    $selectedDelivery ===
    'Postage'
) {

    $currentDeliveryFee =
        $postageFee;

} elseif (
    $selectedDelivery ===
    'Vendor Delivery'
) {

    $currentDeliveryFee =
        $vendorDeliveryFee;
}


$grandTotal =
    $subtotal +
    $currentDeliveryFee;


/*
|--------------------------------------------------------------------------
| STOCK VALIDATION
|--------------------------------------------------------------------------
*/

foreach ($cartItems as $item) {

    $productStatus =
        strtolower(
            trim(
                (string) $item['status']
            )
        );


    if (
        $productStatus !==
        'available'
    ) {

        $error =
            $item['product_name'] .
            ' is currently unavailable.';

        break;
    }


    $availableStock =
        (int) $item[
            'stock_quantity'
        ];


    $requestedQuantity =
        (int) $item[
            'quantity'
        ];


    if ($availableStock <= 0) {

        $error =
            $item['product_name'] .
            ' is out of stock.';

        break;
    }


    if (
        $requestedQuantity >
        $availableStock
    ) {

        $error =
            'Insufficient stock for ' .
            $item['product_name'] .
            '.';

        break;
    }
}


/*
|--------------------------------------------------------------------------
| PLACE ORDER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $error === ''
) {

    /*
    |--------------------------------------------------------------------------
    | FORM VALUES
    |--------------------------------------------------------------------------
    */

    $deliveryMethod =
        trim(
            (string) (
                $_POST[
                    'delivery_method'
                ]
                ?? ''
            )
        );


    $deliveryAddress =
        trim(
            (string) (
                $_POST[
                    'delivery_address'
                ]
                ?? ''
            )
        );


    $paymentMethod =
        trim(
            (string) (
                $_POST[
                    'payment_method'
                ]
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | ALLOWED VALUES
    |--------------------------------------------------------------------------
    */

    $allowedDeliveryMethods = [
        'Pickup',
        'Postage',
        'Vendor Delivery'
    ];


    $allowedPaymentMethods = [
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ];


    /*
    |--------------------------------------------------------------------------
    | ONLINE PAYMENT
    |--------------------------------------------------------------------------
    */

    $isOnlinePayment =
        in_array(
            $paymentMethod,
            [
                'FPX',
                'Credit Card',
                'Debit Card'
            ],
            true
        );


    /*
    |--------------------------------------------------------------------------
    | DELIVERY VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $deliveryMethod,
            $allowedDeliveryMethods,
            true
        )
    ) {

        $error =
            'Please select a valid delivery method.';

    } elseif (
        $deliveryMethod ===
        'Pickup' &&
        !$pickupAvailable
    ) {

        $error =
            'Pickup is not available for all sellers in your cart.';

    } elseif (
        $deliveryMethod ===
        'Postage' &&
        !$postageAvailable
    ) {

        $error =
            'Postage is not available for all sellers in your cart.';

    } elseif (
        $deliveryMethod ===
        'Vendor Delivery' &&
        !$vendorDeliveryAvailable
    ) {

        $error =
            'Vendor Delivery is not available for all sellers in your cart.';

    } elseif (
        (
            $deliveryMethod ===
            'Postage' ||

            $deliveryMethod ===
            'Vendor Delivery'
        ) &&
        $deliveryAddress === ''
    ) {

        $error =
            'Please enter your delivery address.';

    } elseif (
        !in_array(
            $paymentMethod,
            $allowedPaymentMethods,
            true
        )
    ) {

        $error =
            'Please select a valid payment method.';

    } elseif (
        $deliveryMethod ===
        'Postage' &&
        $paymentMethod ===
        'Cash'
    ) {

        $error =
            'Cash payment is not available for Postage.';

    } elseif (
        $deliveryMethod ===
        'Vendor Delivery' &&
        $paymentMethod ===
        'Cash' &&
        !$vendorDeliveryCodAvailable
    ) {

        $error =
            'Cash on Delivery is not available for all sellers in your cart.';
    }


    /*
    |--------------------------------------------------------------------------
    | FINAL DELIVERY FEE
    |--------------------------------------------------------------------------
    */

    $finalDeliveryFee = 0.00;


    if (
        $error === '' &&
        $deliveryMethod ===
        'Postage'
    ) {

        foreach ($vendors as $vendor) {

            $finalDeliveryFee +=
                max(
                    0,
                    (float) $vendor[
                        'postage_fee'
                    ]
                );
        }
    }


    if (
        $error === '' &&
        $deliveryMethod ===
        'Vendor Delivery'
    ) {

        foreach ($vendors as $vendor) {

            $finalDeliveryFee +=
                max(
                    0,
                    (float) $vendor[
                        'vendor_delivery_fee'
                    ]
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FINAL TOTAL
    |--------------------------------------------------------------------------
    */

    $finalGrandTotal =
        $subtotal +
        $finalDeliveryFee;


    /*
    |--------------------------------------------------------------------------
    | CREATE ORDER
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | MAIN ORDER
            |--------------------------------------------------------------------------
            */

            $orderStmt =
                $db->prepare("
                    INSERT INTO orders
                    (
                        customer_id,
                        total_amount,
                        delivery_method,
                        delivery_address,
                        order_status
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending'
                    )
                ");


            $storedAddress =
                $deliveryMethod ===
                'Pickup'

                    ? null

                    : $deliveryAddress;


            $orderStmt->execute([

                $userId,

                checkoutMoney(
                    $finalGrandTotal
                ),

                $deliveryMethod,

                $storedAddress
            ]);


            $orderId =
                (int)
                $db->lastInsertId();


            if ($orderId <= 0) {

                throw new Exception(
                    'Unable to create order.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | ORDER DETAILS
            |--------------------------------------------------------------------------
            */

            $detailStmt =
                $db->prepare("
                    INSERT INTO order_details
                    (
                        order_id,
                        product_id,
                        quantity,
                        unit_price,
                        subtotal
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


            /*
            |--------------------------------------------------------------------------
            | STOCK UPDATE
            |--------------------------------------------------------------------------
            */

            $stockStmt =
                $db->prepare("
                    UPDATE products

                    SET
                        stock_quantity =
                            stock_quantity - ?,

                        status =
                            CASE

                                WHEN stock_quantity - ? <= 0
                                THEN 'Out of Stock'

                                ELSE status

                            END

                    WHERE product_id = ?

                    AND stock_quantity >= ?

                    AND status = 'Available'
                ");


            /*
            |--------------------------------------------------------------------------
            | INVENTORY
            |--------------------------------------------------------------------------
            */

            $inventoryStmt =
                $db->prepare("
                    INSERT INTO inventory
                    (
                        product_id,
                        quantity
                    )

                    VALUES
                    (
                        ?,
                        ?
                    )

                    ON DUPLICATE KEY UPDATE

                        quantity =
                            VALUES(quantity),

                        last_updated =
                            CURRENT_TIMESTAMP
                ");


            /*
            |--------------------------------------------------------------------------
            | INSERT ITEMS + REDUCE STOCK
            |--------------------------------------------------------------------------
            */

            foreach (
                $cartItems
                as $item
            ) {

                $quantity =
                    (int)
                    $item['quantity'];


                $price =
                    (float)
                    $item['price'];


                $lineSubtotal =
                    $quantity *
                    $price;


                /*
                |--------------------------------------------------------------------------
                | ORDER DETAIL
                |--------------------------------------------------------------------------
                */

                $detailStmt->execute([

                    $orderId,

                    (int)
                    $item[
                        'product_id'
                    ],

                    $quantity,

                    checkoutMoney(
                        $price
                    ),

                    checkoutMoney(
                        $lineSubtotal
                    )
                ]);


                /*
                |--------------------------------------------------------------------------
                | REDUCE STOCK
                |--------------------------------------------------------------------------
                */

                $stockStmt->execute([

                    $quantity,

                    $quantity,

                    (int)
                    $item[
                        'product_id'
                    ],

                    $quantity
                ]);


                if (
                    $stockStmt->rowCount()
                    === 0
                ) {

                    throw new Exception(
                        'Stock changed for ' .
                        $item[
                            'product_name'
                        ] .
                        '. Please return to cart and try again.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CURRENT STOCK
                |--------------------------------------------------------------------------
                */

                $stockLookup =
                    $db->prepare("
                        SELECT
                            stock_quantity

                        FROM products

                        WHERE product_id = ?

                        LIMIT 1
                    ");


                $stockLookup->execute([
                    (int)
                    $item[
                        'product_id'
                    ]
                ]);


                $newStock =
                    (int)
                    $stockLookup
                        ->fetchColumn();


                /*
                |--------------------------------------------------------------------------
                | UPDATE INVENTORY
                |--------------------------------------------------------------------------
                */

                $inventoryStmt->execute([

                    (int)
                    $item[
                        'product_id'
                    ],

                    max(
                        0,
                        $newStock
                    )
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | VENDOR ORDERS
            |--------------------------------------------------------------------------
            */

            $vendorOrderStmt =
                $db->prepare("
                    INSERT INTO vendor_orders
                    (
                        order_id,
                        vendor_id,
                        subtotal,
                        delivery_fee,
                        vendor_status
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending'
                    )
                ");


            /*
            |--------------------------------------------------------------------------
            | COMMISSION
            |--------------------------------------------------------------------------
            */

            $commissionStmt =
                $db->prepare("
                    INSERT INTO commission
                    (
                        vendor_id,
                        order_id,
                        vendor_order_id,
                        commission_rate,
                        commission_amount,
                        status
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending'
                    )
                ");


            /*
            |--------------------------------------------------------------------------
            | CREATE VENDOR ORDERS
            |--------------------------------------------------------------------------
            */

            foreach (
                $vendors
                as $vendor
            ) {

                $vendorId =
                    (int)
                    $vendor[
                        'vendor_id'
                    ];


                $vendorSubtotal =
                    (float)
                    $vendor[
                        'subtotal'
                    ];


                /*
                |--------------------------------------------------------------------------
                | PER-VENDOR DELIVERY FEE
                |--------------------------------------------------------------------------
                */

                $vendorFee =
                    0.00;


                if (
                    $deliveryMethod ===
                    'Postage'
                ) {

                    $vendorFee =
                        max(
                            0,
                            (float)
                            $vendor[
                                'postage_fee'
                            ]
                        );

                } elseif (
                    $deliveryMethod ===
                    'Vendor Delivery'
                ) {

                    $vendorFee =
                        max(
                            0,
                            (float)
                            $vendor[
                                'vendor_delivery_fee'
                            ]
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | COMMISSION RATE
                |--------------------------------------------------------------------------
                */

                $commissionRate =
                    max(
                        0,
                        min(
                            100,
                            (float)
                            $vendor[
                                'commission_rate'
                            ]
                        )
                    );


                /*
                |--------------------------------------------------------------------------
                | COMMISSION AMOUNT
                |--------------------------------------------------------------------------
                |
                | Commission is calculated on product subtotal only.
                | Delivery fee is NOT included.
                |
                |--------------------------------------------------------------------------
                */

                $commissionAmount =
                    $vendorSubtotal *
                    (
                        $commissionRate /
                        100
                    );


                /*
                |--------------------------------------------------------------------------
                | CREATE VENDOR ORDER
                |--------------------------------------------------------------------------
                */

                $vendorOrderStmt
                    ->execute([

                        $orderId,

                        $vendorId,

                        checkoutMoney(
                            $vendorSubtotal
                        ),

                        checkoutMoney(
                            $vendorFee
                        )
                    ]);


                $vendorOrderId =
                    (int)
                    $db->lastInsertId();


                if (
                    $vendorOrderId <= 0
                ) {

                    throw new Exception(
                        'Unable to create seller order.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE COMMISSION
                |--------------------------------------------------------------------------
                */

                $commissionStmt
                    ->execute([

                        $vendorId,

                        $orderId,

                        $vendorOrderId,

                        checkoutMoney(
                            $commissionRate
                        ),

                        checkoutMoney(
                            $commissionAmount
                        )
                    ]);
            }


            /*
            |--------------------------------------------------------------------------
            | PAYMENT
            |--------------------------------------------------------------------------
            */

            $paymentGateway =
                $isOnlinePayment
                    ? 'Fiuu'
                    : null;


            $paymentStmt =
                $db->prepare("
                    INSERT INTO payments
                    (
                        order_id,
                        payment_method,
                        payment_status,
                        amount,
                        payment_gateway,
                        gateway_order_reference
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        'Pending',
                        ?,
                        ?,
                        ?
                    )
                ");


            $gatewayOrderReference =
                $isOnlinePayment
                    ? (string) $orderId
                    : null;


            $paymentStmt->execute([

                $orderId,

                $paymentMethod,

                checkoutMoney(
                    $finalGrandTotal
                ),

                $paymentGateway,

                $gatewayOrderReference
            ]);


            /*
            |--------------------------------------------------------------------------
            | CLEAR CART
            |--------------------------------------------------------------------------
            */

            $clearCart =
                $db->prepare("
                    DELETE FROM cart
                    WHERE customer_id = ?
                ");


            $clearCart->execute([
                $userId
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $db->commit();


            /*
            |--------------------------------------------------------------------------
            | ONLINE PAYMENT -> FIUU
            |--------------------------------------------------------------------------
            */

            if ($isOnlinePayment) {

                $_SESSION[
                    'fiuu_last_order_id'
                ] =
                    $orderId;


                header(
                    'Location: fiuu_payment.php?order_id=' .
                    $orderId
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | CASH -> ORDER DETAILS
            |--------------------------------------------------------------------------
            */

            header(
                'Location: order_details.php?id=' .
                $orderId .
                '&success=1'
            );

            exit;


        } catch (Throwable $exception) {

            if (
                $db->inTransaction()
            ) {
                $db->rollBack();
            }


            $error =
                'Unable to place order. ' .
                $exception->getMessage();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE SELECTED VALUES
    |--------------------------------------------------------------------------
    */

    $selectedDelivery =
        $deliveryMethod;


    $selectedAddress =
        $deliveryAddress;


    $selectedPayment =
        $paymentMethod;


    /*
    |--------------------------------------------------------------------------
    | REFRESH CURRENT DELIVERY FEE
    |--------------------------------------------------------------------------
    */

    if (
        $selectedDelivery ===
        'Postage'
    ) {

        $currentDeliveryFee =
            $postageFee;

    } elseif (
        $selectedDelivery ===
        'Vendor Delivery'
    ) {

        $currentDeliveryFee =
            $vendorDeliveryFee;

    } else {

        $currentDeliveryFee =
            0.00;
    }


    $grandTotal =
        $subtotal +
        $currentDeliveryFee;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

$hideSiteMainWrapper = true;

$extraCSS = [
    'dashboard.css'
];

require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

* {
    box-sizing: border-box;
}


/* =========================================================
   PAGE
========================================================= */

.hh-checkout-page {

    width: 100%;

    min-height: 100vh;

    padding:
        36px 28px
        70px;

    color: #14213d;

    background:
        #f6f8fc;
}


.hh-checkout-container {

    width: 100%;

    max-width: 1260px;

    margin: 0 auto;
}


/* =========================================================
   HERO
========================================================= */

.hh-checkout-hero {

    position: relative;

    overflow: hidden;

    margin-bottom: 24px;

    padding: 30px 32px;

    border-radius: 24px;

    background:
        linear-gradient(
            135deg,
            #1264f6,
            #4169e1 58%,
            #6366f1
        );

    color: #ffffff;

    box-shadow:
        0 18px 50px
        rgba(
            37,
            99,
            235,
            0.18
        );
}


.hh-checkout-hero::after {

    content: "";

    position: absolute;

    width: 260px;

    height: 260px;

    right: -80px;

    top: -110px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            0.10
        );
}


.hh-checkout-hero-content {

    position: relative;

    z-index: 2;
}


.hh-checkout-hero-label {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 10px;

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.08em;

    opacity: 0.9;
}


.hh-checkout-hero h1 {

    margin:
        0 0 7px;

    font-size: 30px;

    font-weight: 900;

    letter-spacing: -0.7px;
}


.hh-checkout-hero p {

    max-width: 700px;

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            0.85
        );

    font-size: 13px;

    line-height: 1.7;
}


/* =========================================================
   ALERT
========================================================= */

.hh-checkout-error {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 20px;

    padding: 15px 17px;

    border:
        1px solid
        #ffd1ce;

    border-radius: 14px;

    color: #b42318;

    background: #fff1f0;

    font-size: 13px;

    font-weight: 650;
}


/* =========================================================
   LAYOUT
========================================================= */

.hh-checkout-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        350px;

    gap: 22px;

    align-items: start;
}


.hh-checkout-left {

    display: grid;

    gap: 20px;
}


.hh-card {

    overflow: hidden;

    border:
        1px solid
        #e5eaf2;

    border-radius: 20px;

    background: #ffffff;

    box-shadow:
        0 8px 28px
        rgba(
            31,
            41,
            55,
            0.05
        );
}


.hh-card-header {

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        21px 23px;

    border-bottom:
        1px solid
        #edf0f5;
}


.hh-card-icon {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 42px;

    height: 42px;

    border-radius: 13px;

    background: #edf4ff;

    color: #2563eb;

    font-size: 18px;
}


.hh-card-header h2 {

    margin: 0;

    font-size: 16px;

    font-weight: 850;
}


.hh-card-header p {

    margin:
        4px 0 0;

    color: #8290a5;

    font-size: 11px;
}


.hh-card-body {

    padding: 23px;
}


/* =========================================================
   DELIVERY OPTIONS
========================================================= */

.hh-delivery-grid {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 13px;
}


.hh-option {

    position: relative;

    cursor: pointer;
}


.hh-option input {

    position: absolute;

    opacity: 0;

    pointer-events: none;
}


.hh-option-box {

    min-height: 145px;

    height: 100%;

    padding: 18px;

    border:
        1.5px solid
        #e2e8f0;

    border-radius: 15px;

    background: #ffffff;

    transition:
        all 0.2s ease;
}


.hh-option-box:hover {

    border-color:
        #a8c2ff;

    transform:
        translateY(-1px);
}


.hh-option-icon {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 42px;

    height: 42px;

    margin-bottom: 13px;

    border-radius: 12px;

    background: #edf4ff;

    color: #2563eb;

    font-size: 18px;
}


.hh-option-box strong {

    display: block;

    margin-bottom: 6px;

    color: #263248;

    font-size: 13px;
}


.hh-option-box small {

    display: block;

    color: #8290a5;

    font-size: 10px;

    line-height: 1.6;
}


.hh-option-box .hh-fee {

    display: inline-block;

    margin-top: 10px;

    padding:
        5px 8px;

    border-radius: 7px;

    background:
        #f2f6ff;

    color: #315da8;

    font-size: 9px;

    font-weight: 850;
}


.hh-option input:checked +
.hh-option-box {

    border-color: #2563eb;

    background: #f5f8ff;

    box-shadow:
        0 0 0 3px
        rgba(
            37,
            99,
            235,
            0.08
        );
}


.hh-option.disabled {

    cursor: not-allowed;

    opacity: 0.45;
}


.hh-option.disabled
.hh-option-box:hover {

    transform: none;
}


/* =========================================================
   PICKUP LOCATION
========================================================= */

.hh-pickup-locations {

    display: none;

    margin-top: 20px;

    padding-top: 20px;

    border-top:
        1px solid
        #edf0f5;
}


.hh-pickup-locations h3 {

    margin:
        0 0 12px;

    font-size: 13px;
}


.hh-pickup-list {

    display: grid;

    gap: 10px;
}


.hh-pickup-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16px;

    padding: 13px 14px;

    border:
        1px solid
        #e5eaf2;

    border-radius: 12px;

    background: #fafcff;
}


.hh-pickup-store {

    min-width: 0;
}


.hh-pickup-store strong {

    display: block;

    margin-bottom: 4px;

    color: #263248;

    font-size: 11px;
}


.hh-pickup-store span {

    display: block;

    color: #78869d;

    font-size: 9px;

    line-height: 1.5;
}


.hh-navigate {

    flex: 0 0 auto;

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding:
        8px 10px;

    border-radius: 8px;

    color: #ffffff;

    background: #2563eb;

    text-decoration: none;

    font-size: 9px;

    font-weight: 800;
}


/* =========================================================
   ADDRESS
========================================================= */

.hh-address-field {

    display: none;

    margin-top: 20px;
}


.hh-address-field label {

    display: block;

    margin-bottom: 8px;

    color: #344054;

    font-size: 11px;

    font-weight: 800;
}


.hh-address-field textarea {

    width: 100%;

    min-height: 110px;

    padding: 13px 14px;

    resize: vertical;

    border:
        1px solid
        #dce3ed;

    border-radius: 12px;

    outline: none;

    color: #273142;

    background: #ffffff;

    font-family: inherit;

    font-size: 11px;

    line-height: 1.6;
}


.hh-address-field textarea:focus {

    border-color: #3b82f6;

    box-shadow:
        0 0 0 3px
        rgba(
            59,
            130,
            246,
            0.08
        );
}


/* =========================================================
   PAYMENT
========================================================= */

.hh-payment-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap: 12px;
}


.hh-payment-option {

    position: relative;

    cursor: pointer;
}


.hh-payment-option input {

    position: absolute;

    opacity: 0;

    pointer-events: none;
}


.hh-payment-box {

    display: flex;

    align-items: center;

    gap: 13px;

    min-height: 78px;

    padding: 14px;

    border:
        1.5px solid
        #e2e8f0;

    border-radius: 13px;

    background: #ffffff;

    transition:
        all 0.2s ease;
}


.hh-payment-icon {

    flex: 0 0 auto;

    display: flex;

    align-items: center;

    justify-content: center;

    width: 42px;

    height: 42px;

    border-radius: 12px;

    background: #edf4ff;

    color: #2563eb;

    font-size: 17px;
}


.hh-payment-copy strong {

    display: block;

    margin-bottom: 3px;

    color: #253149;

    font-size: 11px;
}


.hh-payment-copy small {

    display: block;

    color: #8491a6;

    font-size: 9px;

    line-height: 1.5;
}


.hh-payment-option input:checked +
.hh-payment-box {

    border-color: #2563eb;

    background: #f5f8ff;

    box-shadow:
        0 0 0 3px
        rgba(
            37,
            99,
            235,
            0.08
        );
}


.hh-payment-option.disabled {

    cursor: not-allowed;

    opacity: 0.45;
}


.hh-payment-note {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-top: 16px;

    padding: 13px 14px;

    border-radius: 12px;

    color: #52647c;

    background: #f5f8fd;

    font-size: 10px;

    line-height: 1.6;
}


.hh-payment-note i {

    margin-top: 1px;

    color: #2563eb;

    font-size: 15px;
}


/* =========================================================
   CART ITEMS
========================================================= */

.hh-item-list {

    display: grid;

    gap: 12px;
}


.hh-cart-item {

    display: grid;

    grid-template-columns:
        64px
        minmax(0, 1fr)
        auto;

    gap: 13px;

    align-items: center;

    padding:
        11px 0;

    border-bottom:
        1px solid
        #edf0f5;
}


.hh-cart-item:last-child {

    border-bottom: 0;
}


.hh-product-image {

    width: 64px;

    height: 64px;

    overflow: hidden;

    border-radius: 12px;

    background: #eef2f7;
}


.hh-product-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.hh-product-placeholder {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    height: 100%;

    color: #9ba7b8;

    font-size: 18px;
}


.hh-product-info {

    min-width: 0;
}


.hh-product-info strong {

    display: block;

    overflow: hidden;

    margin-bottom: 5px;

    color: #263248;

    font-size: 11px;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.hh-product-info span {

    color: #8390a4;

    font-size: 9px;
}


.hh-product-price {

    text-align: right;
}


.hh-product-price strong {

    display: block;

    color: #1d4ed8;

    font-size: 11px;
}


.hh-product-price small {

    color: #8a96a9;

    font-size: 9px;
}


/* =========================================================
   SUMMARY
========================================================= */

.hh-summary-card {

    position: sticky;

    top: 20px;

    overflow: hidden;

    border:
        1px solid
        #e5eaf2;

    border-radius: 20px;

    background: #ffffff;

    box-shadow:
        0 12px 35px
        rgba(
            31,
            41,
            55,
            0.08
        );
}


.hh-summary-header {

    padding: 22px;

    border-bottom:
        1px solid
        #edf0f5;
}


.hh-summary-header h2 {

    margin: 0;

    color: #202b40;

    font-size: 16px;
}


.hh-summary-body {

    padding: 20px 22px;
}


.hh-summary-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 13px;

    color: #708097;

    font-size: 11px;
}


.hh-summary-row strong {

    color: #28364d;

    font-size: 11px;
}


.hh-summary-divider {

    height: 1px;

    margin:
        17px 0;

    background: #edf0f5;
}


.hh-summary-total {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;
}


.hh-summary-total span {

    color: #263248;

    font-size: 13px;

    font-weight: 800;
}


.hh-summary-total strong {

    color: #1769ff;

    font-size: 20px;

    font-weight: 900;
}


.hh-place-order {

    width: 100%;

    min-height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    border: 0;

    border-radius: 12px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1769ff,
            #5b5df6
        );

    font-family: inherit;

    font-size: 11px;

    font-weight: 850;

    cursor: pointer;

    box-shadow:
        0 9px 24px
        rgba(
            37,
            99,
            235,
            0.22
        );
}


.hh-place-order:disabled {

    cursor: not-allowed;

    opacity: 0.65;
}


.hh-secure-note {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    margin-top: 13px;

    color: #8a96a9;

    font-size: 8px;
}


/* =========================================================
   SELLER FEE BREAKDOWN
========================================================= */

.hh-fee-breakdown {

    margin-top: 17px;

    padding-top: 15px;

    border-top:
        1px solid
        #edf0f5;
}


.hh-fee-breakdown h4 {

    margin:
        0 0 10px;

    font-size: 10px;
}


.hh-vendor-fee-row {

    display: flex;

    justify-content: space-between;

    gap: 12px;

    margin-top: 8px;

    color: #8190a5;

    font-size: 9px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1050px
) {

    .hh-checkout-layout {

        grid-template-columns:
            1fr;
    }


    .hh-summary-card {

        position: static;
    }
}


@media (
    max-width: 760px
) {

    .hh-checkout-page {

        padding:
            22px 14px
            50px;
    }


    .hh-checkout-hero {

        padding:
            25px 21px;
    }


    .hh-checkout-hero h1 {

        font-size: 25px;
    }


    .hh-delivery-grid {

        grid-template-columns:
            1fr;
    }


    .hh-payment-grid {

        grid-template-columns:
            1fr;
    }


    .hh-card-header,
    .hh-card-body {

        padding:
            18px;
    }
}

</style>


<div class="hh-checkout-page">

<div class="hh-checkout-container">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hh-checkout-hero">

        <div class="hh-checkout-hero-content">

            <div class="hh-checkout-hero-label">

                <i class="bi bi-bag-check-fill"></i>

                Secure Checkout

            </div>

            <h1>
                Complete Your Order
            </h1>

            <p>
                Select how you want to receive your order
                and choose your preferred payment method.
            </p>

        </div>

    </section>


    <!-- =====================================================
         ERROR
    ====================================================== -->

    <?php if ($error !== ''): ?>

        <div class="hh-checkout-error">

            <i class="bi bi-exclamation-circle-fill"></i>

            <div>
                <?= checkoutEscape(
                    $error
                ) ?>
            </div>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="checkout.php"
        id="checkoutForm"
    >

        <div class="hh-checkout-layout">


            <!-- =================================================
                 LEFT
            ================================================== -->

            <div class="hh-checkout-left">


                <!-- =============================================
                     DELIVERY
                ============================================== -->

                <section class="hh-card">

                    <div class="hh-card-header">

                        <div class="hh-card-icon">

                            <i class="bi bi-truck"></i>

                        </div>

                        <div>

                            <h2>
                                Delivery Method
                            </h2>

                            <p>
                                Choose how you want to receive
                                your order.
                            </p>

                        </div>

                    </div>


                    <div class="hh-card-body">

                        <div class="hh-delivery-grid">


                            <!-- PICKUP -->

                            <label
                                class="hh-option <?= !$pickupAvailable
                                    ? 'disabled'
                                    : '' ?>"
                            >

                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="Pickup"
                                    <?= $selectedDelivery ===
                                        'Pickup'
                                            ? 'checked'
                                            : '' ?>
                                    <?= !$pickupAvailable
                                        ? 'disabled'
                                        : '' ?>
                                >

                                <div class="hh-option-box">

                                    <div class="hh-option-icon">

                                        <i class="bi bi-shop"></i>

                                    </div>

                                    <strong>
                                        Pickup
                                    </strong>

                                    <small>
                                        Collect from seller
                                        location.
                                    </small>

                                    <span class="hh-fee">
                                        FREE
                                    </span>

                                </div>

                            </label>


                            <!-- POSTAGE -->

                            <label
                                class="hh-option <?= !$postageAvailable
                                    ? 'disabled'
                                    : '' ?>"
                            >

                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="Postage"
                                    <?= $selectedDelivery ===
                                        'Postage'
                                            ? 'checked'
                                            : '' ?>
                                    <?= !$postageAvailable
                                        ? 'disabled'
                                        : '' ?>
                                >

                                <div class="hh-option-box">

                                    <div class="hh-option-icon">

                                        <i class="bi bi-box-seam"></i>

                                    </div>

                                    <strong>
                                        Postage
                                    </strong>

                                    <small>
                                        Delivered using courier
                                        service.
                                    </small>

                                    <span class="hh-fee">

                                        RM <?= checkoutEscape(
                                            checkoutMoney(
                                                $postageFee
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </label>


                            <!-- VENDOR DELIVERY -->

                            <label
                                class="hh-option <?= !$vendorDeliveryAvailable
                                    ? 'disabled'
                                    : '' ?>"
                            >

                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="Vendor Delivery"
                                    <?= $selectedDelivery ===
                                        'Vendor Delivery'
                                            ? 'checked'
                                            : '' ?>
                                    <?= !$vendorDeliveryAvailable
                                        ? 'disabled'
                                        : '' ?>
                                >

                                <div class="hh-option-box">

                                    <div class="hh-option-icon">

                                        <i class="bi bi-scooter"></i>

                                    </div>

                                    <strong>
                                        Vendor Delivery
                                    </strong>

                                    <small>
                                        Seller delivers directly
                                        to you.
                                    </small>

                                    <span class="hh-fee">

                                        RM <?= checkoutEscape(
                                            checkoutMoney(
                                                $vendorDeliveryFee
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </label>

                        </div>


                        <!-- =========================================
                             PICKUP LOCATIONS
                        ========================================== -->

                        <div
                            class="hh-pickup-locations"
                            id="pickupLocations"
                        >

                            <h3>
                                Pickup Locations
                            </h3>

                            <div class="hh-pickup-list">

                                <?php foreach (
                                    $vendors
                                    as $vendor
                                ): ?>

                                    <div class="hh-pickup-item">

                                        <div class="hh-pickup-store">

                                            <strong>

                                                <?= checkoutEscape(
                                                    $vendor[
                                                        'business_name'
                                                    ]
                                                ) ?>

                                            </strong>

                                            <span>

                                                <?= checkoutEscape(
                                                    $vendor[
                                                        'business_address'
                                                    ] !== ''
                                                        ? $vendor[
                                                            'business_address'
                                                        ]
                                                        : 'Address not provided.'
                                                ) ?>

                                            </span>

                                        </div>


                                        <?php if (
                                            $vendor[
                                                'business_address'
                                            ] !== ''
                                        ): ?>

                                            <a
                                                href="<?= checkoutEscape(
                                                    checkoutMapsUrl(
                                                        $vendor[
                                                            'business_address'
                                                        ]
                                                    )
                                                ) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="hh-navigate"
                                            >

                                                <i class="bi bi-geo-alt-fill"></i>

                                                Navigate

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>


                        <!-- =========================================
                             ADDRESS
                        ========================================== -->

                        <div
                            class="hh-address-field"
                            id="addressField"
                        >

                            <label for="delivery_address">

                                <i class="bi bi-geo-alt"></i>

                                Delivery Address

                            </label>

                            <textarea
                                id="delivery_address"
                                name="delivery_address"
                                rows="4"
                                placeholder="Enter your full delivery address..."
                            ><?= checkoutEscape(
                                $selectedAddress
                            ) ?></textarea>

                        </div>

                    </div>

                </section>


                <!-- =============================================
                     PAYMENT
                ============================================== -->

                <section class="hh-card">

                    <div class="hh-card-header">

                        <div class="hh-card-icon">

                            <i class="bi bi-credit-card"></i>

                        </div>

                        <div>

                            <h2>
                                Payment Method
                            </h2>

                            <p>
                                Online payments are processed
                                through Fiuu.
                            </p>

                        </div>

                    </div>


                    <div class="hh-card-body">

                        <div class="hh-payment-grid">


                            <!-- FPX -->

                            <label class="hh-payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="FPX"
                                    <?= $selectedPayment ===
                                        'FPX'
                                            ? 'checked'
                                            : '' ?>
                                >

                                <div class="hh-payment-box">

                                    <div class="hh-payment-icon">

                                        <i class="bi bi-bank"></i>

                                    </div>

                                    <div class="hh-payment-copy">

                                        <strong>
                                            FPX
                                        </strong>

                                        <small>
                                            Online Banking via Fiuu
                                        </small>

                                    </div>

                                </div>

                            </label>


                            <!-- CREDIT CARD -->

                            <label class="hh-payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="Credit Card"
                                    <?= $selectedPayment ===
                                        'Credit Card'
                                            ? 'checked'
                                            : '' ?>
                                >

                                <div class="hh-payment-box">

                                    <div class="hh-payment-icon">

                                        <i class="bi bi-credit-card-fill"></i>

                                    </div>

                                    <div class="hh-payment-copy">

                                        <strong>
                                            Credit Card
                                        </strong>

                                        <small>
                                            Secure card payment
                                            via Fiuu
                                        </small>

                                    </div>

                                </div>

                            </label>


                            <!-- DEBIT CARD -->

                            <label class="hh-payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="Debit Card"
                                    <?= $selectedPayment ===
                                        'Debit Card'
                                            ? 'checked'
                                            : '' ?>
                                >

                                <div class="hh-payment-box">

                                    <div class="hh-payment-icon">

                                        <i class="bi bi-credit-card-2-front-fill"></i>

                                    </div>

                                    <div class="hh-payment-copy">

                                        <strong>
                                            Debit Card
                                        </strong>

                                        <small>
                                            Secure debit payment
                                            via Fiuu
                                        </small>

                                    </div>

                                </div>

                            </label>


                            <!-- CASH -->

                            <label
                                class="hh-payment-option"
                                id="cashOption"
                            >

                                <input
                                    type="radio"
                                    id="cashPayment"
                                    name="payment_method"
                                    value="Cash"
                                    <?= $selectedPayment ===
                                        'Cash'
                                            ? 'checked'
                                            : '' ?>
                                >

                                <div class="hh-payment-box">

                                    <div class="hh-payment-icon">

                                        <i class="bi bi-cash-stack"></i>

                                    </div>

                                    <div class="hh-payment-copy">

                                        <strong id="cashTitle">
                                            Cash
                                        </strong>

                                        <small id="cashText">
                                            Depends on selected delivery.
                                        </small>

                                    </div>

                                </div>

                            </label>

                        </div>


                        <div
                            class="hh-payment-note"
                            id="paymentNote"
                        >

                            <i class="bi bi-shield-lock-fill"></i>

                            <div>

                                Select a payment method.
                                FPX, Credit Card and Debit Card
                                will redirect you to Fiuu.

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =============================================
                     ITEMS
                ============================================== -->

                <section class="hh-card">

                    <div class="hh-card-header">

                        <div class="hh-card-icon">

                            <i class="bi bi-bag"></i>

                        </div>

                        <div>

                            <h2>
                                Order Items
                            </h2>

                            <p>
                                <?= (int) $totalItems ?>
                                item(s) in this order.
                            </p>

                        </div>

                    </div>


                    <div class="hh-card-body">

                        <div class="hh-item-list">

                            <?php foreach (
                                $cartItems
                                as $item
                            ): ?>

                                <?php

                                $imageUrl =
                                    checkoutProductImage(
                                        $item['image']
                                        ?? ''
                                    );

                                $lineSubtotal =
                                    (float)
                                    $item['price'] *
                                    (int)
                                    $item['quantity'];

                                ?>

                                <div class="hh-cart-item">

                                    <div class="hh-product-image">

                                        <?php if (
                                            $imageUrl !== ''
                                        ): ?>

                                            <img
                                                src="<?= checkoutEscape(
                                                    $imageUrl
                                                ) ?>"
                                                alt="<?= checkoutEscape(
                                                    $item[
                                                        'product_name'
                                                    ]
                                                ) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="hh-product-placeholder">

                                                <i class="bi bi-image"></i>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <div class="hh-product-info">

                                        <strong>

                                            <?= checkoutEscape(
                                                $item[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= checkoutEscape(
                                                $item[
                                                    'business_name'
                                                ]
                                            ) ?>

                                            &nbsp;•&nbsp;

                                            Qty:
                                            <?= (int)
                                                $item[
                                                    'quantity'
                                                ] ?>

                                        </span>

                                    </div>


                                    <div class="hh-product-price">

                                        <strong>

                                            RM <?= checkoutEscape(
                                                checkoutMoney(
                                                    $lineSubtotal
                                                )
                                            ) ?>

                                        </strong>

                                        <small>

                                            RM <?= checkoutEscape(
                                                checkoutMoney(
                                                    $item[
                                                        'price'
                                                    ]
                                                )
                                            ) ?>
                                            each

                                        </small>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </section>

            </div>


            <!-- =================================================
                 RIGHT
            ================================================== -->

            <aside>

                <div class="hh-summary-card">

                    <div class="hh-summary-header">

                        <h2>
                            Order Summary
                        </h2>

                    </div>


                    <div class="hh-summary-body">

                        <div class="hh-summary-row">

                            <span>
                                Items
                            </span>

                            <strong>
                                <?= (int) $totalItems ?>
                            </strong>

                        </div>


                        <div class="hh-summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>

                                RM <?= checkoutEscape(
                                    checkoutMoney(
                                        $subtotal
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <div class="hh-summary-row">

                            <span>
                                Delivery Fee
                            </span>

                            <strong
                                id="deliveryFeeDisplay"
                            >

                                RM <?= checkoutEscape(
                                    checkoutMoney(
                                        $currentDeliveryFee
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="hh-fee-breakdown"
                            id="feeBreakdown"
                        >

                            <h4>
                                Seller Delivery Fees
                            </h4>

                            <?php foreach (
                                $vendors
                                as $vendor
                            ): ?>

                                <div
                                    class="hh-vendor-fee-row"
                                    data-vendor-postage="<?= checkoutEscape(
                                        checkoutMoney(
                                            $vendor[
                                                'postage_fee'
                                            ]
                                        )
                                    ) ?>"
                                    data-vendor-delivery="<?= checkoutEscape(
                                        checkoutMoney(
                                            $vendor[
                                                'vendor_delivery_fee'
                                            ]
                                        )
                                    ) ?>"
                                >

                                    <span>

                                        <?= checkoutEscape(
                                            $vendor[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </span>

                                    <span class="vendorFeeValue">
                                        -
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div class="hh-summary-divider"></div>


                        <div class="hh-summary-total">

                            <span>
                                Total
                            </span>

                            <strong
                                id="grandTotalDisplay"
                            >

                                RM <?= checkoutEscape(
                                    checkoutMoney(
                                        $grandTotal
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <button
                            type="submit"
                            class="hh-place-order"
                            id="placeOrderButton"
                        >

                            <span id="placeOrderText">
                                Place Order
                            </span>

                            <i class="bi bi-arrow-right"></i>

                        </button>


                        <div class="hh-secure-note">

                            <i class="bi bi-shield-check"></i>

                            Secure checkout powered by HochipoHub

                        </div>

                    </div>

                </div>

            </aside>

        </div>

    </form>

</div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | VALUES FROM PHP
        |--------------------------------------------------------------------------
        */

        const subtotal =
            <?= json_encode(
                (float) $subtotal
            ) ?>;


        const postageFee =
            <?= json_encode(
                (float) $postageFee
            ) ?>;


        const vendorDeliveryFee =
            <?= json_encode(
                (float) $vendorDeliveryFee
            ) ?>;


        const vendorDeliveryCodAvailable =
            <?= $vendorDeliveryCodAvailable
                ? 'true'
                : 'false' ?>;


        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const checkoutForm =
            document.getElementById(
                'checkoutForm'
            );


        const addressField =
            document.getElementById(
                'addressField'
            );


        const addressInput =
            document.getElementById(
                'delivery_address'
            );


        const pickupLocations =
            document.getElementById(
                'pickupLocations'
            );


        const deliveryFeeDisplay =
            document.getElementById(
                'deliveryFeeDisplay'
            );


        const grandTotalDisplay =
            document.getElementById(
                'grandTotalDisplay'
            );


        const cashOption =
            document.getElementById(
                'cashOption'
            );


        const cashPayment =
            document.getElementById(
                'cashPayment'
            );


        const cashTitle =
            document.getElementById(
                'cashTitle'
            );


        const cashText =
            document.getElementById(
                'cashText'
            );


        const paymentNote =
            document.getElementById(
                'paymentNote'
            );


        const placeOrderButton =
            document.getElementById(
                'placeOrderButton'
            );


        const placeOrderText =
            document.getElementById(
                'placeOrderText'
            );


        const vendorFeeRows =
            document.querySelectorAll(
                '.hh-vendor-fee-row'
            );


        /*
        |--------------------------------------------------------------------------
        | MONEY
        |--------------------------------------------------------------------------
        */

        function money(value) {

            return (
                'RM ' +
                Number(value).toFixed(2)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | GET DELIVERY
        |--------------------------------------------------------------------------
        */

        function selectedDelivery() {

            return document.querySelector(
                'input[name="delivery_method"]:checked'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | GET PAYMENT
        |--------------------------------------------------------------------------
        */

        function selectedPayment() {

            return document.querySelector(
                'input[name="payment_method"]:checked'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DELIVERY FEE
        |--------------------------------------------------------------------------
        */

        function getDeliveryFee() {

            const delivery =
                selectedDelivery();


            if (!delivery) {
                return 0;
            }


            if (
                delivery.value ===
                'Postage'
            ) {

                return postageFee;
            }


            if (
                delivery.value ===
                'Vendor Delivery'
            ) {

                return vendorDeliveryFee;
            }


            return 0;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE VENDOR FEE BREAKDOWN
        |--------------------------------------------------------------------------
        */

        function updateVendorFeeBreakdown() {

            const delivery =
                selectedDelivery();


            vendorFeeRows.forEach(
                function (row) {

                    const output =
                        row.querySelector(
                            '.vendorFeeValue'
                        );


                    if (!output) {
                        return;
                    }


                    if (!delivery) {

                        output.textContent =
                            '-';

                        return;
                    }


                    if (
                        delivery.value ===
                        'Postage'
                    ) {

                        output.textContent =
                            money(
                                Number(
                                    row.dataset
                                        .vendorPostage
                                    || 0
                                )
                            );


                    } else if (
                        delivery.value ===
                        'Vendor Delivery'
                    ) {

                        output.textContent =
                            money(
                                Number(
                                    row.dataset
                                        .vendorDelivery
                                    || 0
                                )
                            );


                    } else {

                        output.textContent =
                            'FREE';
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PAYMENT MESSAGE
        |--------------------------------------------------------------------------
        */

        function updatePaymentMessage() {

            const payment =
                selectedPayment();


            if (!payment) {

                paymentNote.innerHTML =
                    '<i class="bi bi-shield-lock-fill"></i>' +
                    '<div>' +
                    'Select a payment method. FPX, Credit Card ' +
                    'and Debit Card are processed securely through Fiuu.' +
                    '</div>';


                placeOrderText.textContent =
                    'Place Order';

                return;
            }


            if (
                payment.value ===
                'FPX'
            ) {

                paymentNote.innerHTML =
                    '<i class="bi bi-bank"></i>' +
                    '<div>' +
                    '<strong>FPX via Fiuu</strong><br>' +
                    'You will be redirected to Fiuu to complete online banking payment.' +
                    '</div>';


                placeOrderText.textContent =
                    'Proceed to Fiuu';


            } else if (
                payment.value ===
                'Credit Card'
            ) {

                paymentNote.innerHTML =
                    '<i class="bi bi-credit-card"></i>' +
                    '<div>' +
                    '<strong>Credit Card via Fiuu</strong><br>' +
                    'You will be redirected to Fiuu to complete your card payment.' +
                    '</div>';


                placeOrderText.textContent =
                    'Proceed to Fiuu';


            } else if (
                payment.value ===
                'Debit Card'
            ) {

                paymentNote.innerHTML =
                    '<i class="bi bi-credit-card-2-front"></i>' +
                    '<div>' +
                    '<strong>Debit Card via Fiuu</strong><br>' +
                    'You will be redirected to Fiuu to complete your debit card payment.' +
                    '</div>';


                placeOrderText.textContent =
                    'Proceed to Fiuu';


            } else if (
                payment.value ===
                'Cash'
            ) {

                paymentNote.innerHTML =
                    '<i class="bi bi-cash-stack"></i>' +
                    '<div>' +
                    '<strong>Cash Payment</strong><br>' +
                    'Cash is collected by the seller and does not go through Fiuu.' +
                    '</div>';


                placeOrderText.textContent =
                    'Place Order';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE DELIVERY UI
        |--------------------------------------------------------------------------
        */

        function updateDeliveryUI() {

            const delivery =
                selectedDelivery();


            /*
            |--------------------------------------------------------------------------
            | PICKUP LOCATION
            |--------------------------------------------------------------------------
            */

            if (pickupLocations) {

                pickupLocations.style.display =
                    (
                        delivery &&
                        delivery.value ===
                        'Pickup'
                    )

                        ? 'block'

                        : 'none';
            }


            /*
            |--------------------------------------------------------------------------
            | DELIVERY ADDRESS
            |--------------------------------------------------------------------------
            */

            if (
                addressField &&
                addressInput
            ) {

                const needsAddress =
                    delivery &&
                    (
                        delivery.value ===
                        'Postage' ||

                        delivery.value ===
                        'Vendor Delivery'
                    );


                addressField.style.display =
                    needsAddress
                        ? 'block'
                        : 'none';


                addressInput.required =
                    Boolean(
                        needsAddress
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | DELIVERY FEE
            |--------------------------------------------------------------------------
            */

            const fee =
                getDeliveryFee();


            deliveryFeeDisplay.textContent =
                money(fee);


            grandTotalDisplay.textContent =
                money(
                    subtotal +
                    fee
                );


            updateVendorFeeBreakdown();


            /*
            |--------------------------------------------------------------------------
            | CASH RULES
            |--------------------------------------------------------------------------
            */

            cashOption.classList.remove(
                'disabled'
            );


            cashPayment.disabled =
                false;


            if (!delivery) {

                cashTitle.textContent =
                    'Cash';


                cashText.textContent =
                    'Select a delivery method first.';


                cashPayment.disabled =
                    true;


                cashOption.classList.add(
                    'disabled'
                );


            } else if (
                delivery.value ===
                'Pickup'
            ) {

                cashTitle.textContent =
                    'Cash at Pickup';


                cashText.textContent =
                    'Pay seller when collecting your order.';


            } else if (
                delivery.value ===
                'Postage'
            ) {

                cashTitle.textContent =
                    'Cash';


                cashText.textContent =
                    'Not available for Postage.';


                cashPayment.disabled =
                    true;


                cashOption.classList.add(
                    'disabled'
                );


                if (
                    cashPayment.checked
                ) {

                    cashPayment.checked =
                        false;
                }


            } else if (
                delivery.value ===
                'Vendor Delivery'
            ) {

                cashTitle.textContent =
                    'Cash on Delivery';


                if (
                    vendorDeliveryCodAvailable
                ) {

                    cashText.textContent =
                        'Pay seller when your order is delivered.';


                } else {

                    cashText.textContent =
                        'COD is not available for all sellers.';


                    cashPayment.disabled =
                        true;


                    cashOption.classList.add(
                        'disabled'
                    );


                    if (
                        cashPayment.checked
                    ) {

                        cashPayment.checked =
                            false;
                    }
                }
            }


            updatePaymentMessage();
        }


        /*
        |--------------------------------------------------------------------------
        | DELIVERY EVENT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                'input[name="delivery_method"]'
            )
            .forEach(
                function (input) {

                    input.addEventListener(
                        'change',
                        updateDeliveryUI
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | PAYMENT EVENT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                'input[name="payment_method"]'
            )
            .forEach(
                function (input) {

                    input.addEventListener(
                        'change',
                        updatePaymentMessage
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | INITIAL
        |--------------------------------------------------------------------------
        */

        updateDeliveryUI();

        updatePaymentMessage();


        /*
        |--------------------------------------------------------------------------
        | SUBMIT VALIDATION
        |--------------------------------------------------------------------------
        */

        if (checkoutForm) {

            checkoutForm.addEventListener(
                'submit',
                function (event) {

                    const delivery =
                        selectedDelivery();


                    const payment =
                        selectedPayment();


                    /*
                    |--------------------------------------------------------------------------
                    | DELIVERY REQUIRED
                    |--------------------------------------------------------------------------
                    */

                    if (!delivery) {

                        event.preventDefault();

                        alert(
                            'Please select a delivery method.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | ADDRESS REQUIRED
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (
                            delivery.value ===
                            'Postage' ||

                            delivery.value ===
                            'Vendor Delivery'
                        ) &&
                        addressInput &&
                        addressInput
                            .value
                            .trim() === ''
                    ) {

                        event.preventDefault();

                        alert(
                            'Please enter your delivery address.'
                        );

                        addressInput.focus();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PAYMENT REQUIRED
                    |--------------------------------------------------------------------------
                    */

                    if (!payment) {

                        event.preventDefault();

                        alert(
                            'Please select a payment method.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | NO CASH FOR POSTAGE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        delivery.value ===
                        'Postage' &&
                        payment.value ===
                        'Cash'
                    ) {

                        event.preventDefault();

                        alert(
                            'Cash payment is not available for Postage.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | COD CHECK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        delivery.value ===
                        'Vendor Delivery' &&
                        payment.value ===
                        'Cash' &&
                        !vendorDeliveryCodAvailable
                    ) {

                        event.preventDefault();

                        alert(
                            'COD is not available for all sellers in your cart.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DISABLE BUTTON
                    |--------------------------------------------------------------------------
                    */

                    if (placeOrderButton) {

                        placeOrderButton.disabled =
                            true;


                        placeOrderButton.innerHTML =
                            '<span>Processing Order...</span>' +
                            '<i class="bi bi-hourglass-split"></i>';
                    }
                }
            );
        }

    }
);

</script>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>