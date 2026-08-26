<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - PREMIUM CHECKOUT
|--------------------------------------------------------------------------
| File:
| checkout.php
|--------------------------------------------------------------------------
|
| Features:
| - Customer checkout
| - Delivery method
| - Delivery address
| - Payment method
| - Order summary
| - Multi-vendor order creation
| - Stock reduction
| - Inventory sync
| - Payment record
| - Commission record
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


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

    die(
        'Database connection is not available.'
    );
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$user_id =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

$currentRole =
    strtolower(
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

    header(
        'Location: dashboard.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';

$success = false;


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('checkoutEscape')) {

    function checkoutEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('checkoutProductImage')) {

    function checkoutProductImage($image): string
    {
        $image =
            trim(
                (string) $image
            );


        if ($image === '') {
            return '';
        }


        if (
            str_starts_with(
                $image,
                'http://'
            ) ||
            str_starts_with(
                $image,
                'https://'
            )
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
}


/*
|--------------------------------------------------------------------------
| GET CART
|--------------------------------------------------------------------------
*/

$stmt =
    $db->prepare("
        SELECT

            c.cart_id,
            c.product_id,
            c.quantity,

            p.product_name,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,

            v.vendor_id,
            v.business_name

        FROM cart c

        INNER JOIN products p
            ON c.product_id =
               p.product_id

        INNER JOIN vendors v
            ON p.vendor_id =
               v.vendor_id

        WHERE c.customer_id = ?

        ORDER BY
            v.business_name ASC,
            p.product_name ASC
    ");


$stmt->execute([
    $user_id
]);


$cartItems =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| EMPTY CART
|--------------------------------------------------------------------------
*/

if (empty($cartItems)) {

    header(
        'Location: cart.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CALCULATE TOTAL
|--------------------------------------------------------------------------
*/

$subtotal = 0;

$totalItems = 0;

$vendorIds = [];


foreach ($cartItems as $item) {

    $quantity =
        (int) $item['quantity'];


    $price =
        (float) $item['price'];


    $subtotal +=
        $price *
        $quantity;


    $totalItems +=
        $quantity;


    $vendorIds[
        (int) $item['vendor_id']
    ] = true;
}


$vendorCount =
    count(
        $vendorIds
    );


/*
|--------------------------------------------------------------------------
| DELIVERY
|--------------------------------------------------------------------------
|
| Current database logic uses RM0 delivery fee.
| Keep total consistent with existing checkout backend.
|
|--------------------------------------------------------------------------
*/

$deliveryFee = 0.00;

$grandTotal =
    $subtotal +
    $deliveryFee;


/*
|--------------------------------------------------------------------------
| CHECK STOCK / STATUS
|--------------------------------------------------------------------------
*/

foreach ($cartItems as $item) {


    if (
        strtolower(
            trim(
                (string)
                $item['status']
            )
        ) !== 'available'
    ) {

        $error =
            $item['product_name'] .
            ' is currently unavailable.';

        break;
    }


    if (
        (int) $item['quantity'] >
        (int) $item['stock_quantity']
    ) {

        $error =
            'Insufficient stock for ' .
            $item['product_name'];

        break;
    }


    if (
        (int) $item['stock_quantity']
        <= 0
    ) {

        $error =
            $item['product_name'] .
            ' is out of stock.';

        break;
    }
}


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

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
| CHECKOUT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($error)
) {

    $delivery_method =
        $_POST['delivery_method']
        ?? '';


    $delivery_address =
        trim(
            $_POST['delivery_address']
            ?? ''
        );


    $payment_method =
        $_POST['payment_method']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    $allowedDelivery = [
        'Pickup',
        'Postage'
    ];


    $allowedPayment = [
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ];


    if (
        !in_array(
            $delivery_method,
            $allowedDelivery,
            true
        )
    ) {

        $error =
            'Please select a valid delivery method.';


    } elseif (
        $delivery_method === 'Postage' &&
        empty($delivery_address)
    ) {

        $error =
            'Delivery address is required for postage.';


    } elseif (
        !in_array(
            $payment_method,
            $allowedPayment,
            true
        )
    ) {

        $error =
            'Please select a valid payment method.';
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE ORDER
    |--------------------------------------------------------------------------
    */

    if (empty($error)) {

        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | CREATE MAIN ORDER
            |--------------------------------------------------------------------------
            */

            $stmt =
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


            $stmt->execute([

                $user_id,

                $subtotal,

                $delivery_method,

                $delivery_method === 'Postage'
                    ? $delivery_address
                    : null

            ]);


            $order_id =
                (int)
                $db->lastInsertId();


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
            | GROUP ITEMS BY VENDOR
            |--------------------------------------------------------------------------
            */

            $vendorTotals = [];


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


                $itemSubtotal =
                    $quantity *
                    $price;


                /*
                |--------------------------------------------------------------------------
                | ORDER DETAIL
                |--------------------------------------------------------------------------
                */

                $detailStmt->execute([

                    $order_id,

                    (int)
                    $item['product_id'],

                    $quantity,

                    $price,

                    $itemSubtotal

                ]);


                /*
                |--------------------------------------------------------------------------
                | VENDOR TOTAL
                |--------------------------------------------------------------------------
                */

                $vendor_id =
                    (int)
                    $item['vendor_id'];


                if (
                    !isset(
                        $vendorTotals[
                            $vendor_id
                        ]
                    )
                ) {

                    $vendorTotals[
                        $vendor_id
                    ] = 0;
                }


                $vendorTotals[
                    $vendor_id
                ] +=
                    $itemSubtotal;


                /*
                |--------------------------------------------------------------------------
                | REDUCE STOCK
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
                                    WHEN
                                        stock_quantity - ? <= 0
                                    THEN
                                        'Out of Stock'
                                    ELSE
                                        'Available'
                                END

                        WHERE product_id = ?

                        AND stock_quantity >= ?
                    ");


                $stockStmt->execute([

                    $quantity,

                    $quantity,

                    (int)
                    $item['product_id'],

                    $quantity

                ]);


                if (
                    $stockStmt->rowCount()
                    === 0
                ) {

                    throw new Exception(
                        'Stock changed for ' .
                        $item['product_name'] .
                        '. Please try again.'
                    );
                }


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
                                VALUES(quantity)
                    ");


                $newStock =
                    (int)
                    $item['stock_quantity']
                    -
                    $quantity;


                $inventoryStmt->execute([

                    (int)
                    $item['product_id'],

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
                        0.00,
                        'Pending'
                    )
                ");


            foreach (
                $vendorTotals
                as $vendor_id =>
                   $vendorTotal
            ) {

                $vendorOrderStmt->execute([

                    $order_id,

                    $vendor_id,

                    $vendorTotal

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | PAYMENT
            |--------------------------------------------------------------------------
            */

            $paymentStatus =
                'Pending';


            $paymentStmt =
                $db->prepare("
                    INSERT INTO payments
                    (
                        order_id,
                        payment_method,
                        payment_status,
                        amount
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


            $paymentStmt->execute([

                $order_id,

                $payment_method,

                $paymentStatus,

                $subtotal

            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMISSION
            |--------------------------------------------------------------------------
            */

            $commissionRate =
                5.00;


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


            foreach (
                $vendorTotals
                as $vendor_id =>
                   $vendorTotal
            ) {


                /*
                |--------------------------------------------------------------------------
                | VENDOR ORDER ID
                |--------------------------------------------------------------------------
                */

                $vendorOrderLookup =
                    $db->prepare("
                        SELECT
                            vendor_order_id

                        FROM vendor_orders

                        WHERE order_id = ?

                        AND vendor_id = ?

                        LIMIT 1
                    ");


                $vendorOrderLookup->execute([

                    $order_id,

                    $vendor_id

                ]);


                $vendorOrder =
                    $vendorOrderLookup->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (!$vendorOrder) {

                    throw new Exception(
                        'Unable to create vendor order.'
                    );
                }


                $commissionAmount =
                    $vendorTotal *
                    (
                        $commissionRate /
                        100
                    );


                $commissionStmt->execute([

                    $vendor_id,

                    $order_id,

                    (int)
                    $vendorOrder[
                        'vendor_order_id'
                    ],

                    $commissionRate,

                    $commissionAmount

                ]);
            }


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
                $user_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $db->commit();


            $success = true;


            header(
                'Location: order_details.php?id=' .
                $order_id .
                '&success=1'
            );


            exit;


        } catch (Throwable $e) {


            if (
                $db->inTransaction()
            ) {

                $db->rollBack();
            }


            $error =
                'Checkout failed: ' .
                $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| CUSTOMER NAV COUNTS
|--------------------------------------------------------------------------
*/

$cartCount =
    $totalItems;


$wishlistCount = 0;


try {

    $wishlistStmt =
        $db->prepare("
            SELECT COUNT(*)

            FROM wishlist

            WHERE user_id = ?
        ");


    $wishlistStmt->execute([
        $user_id
    ]);


    $wishlistCount =
        (int)
        $wishlistStmt->fetchColumn();


} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Checkout - HochipoHub';


$hideSiteMainWrapper =
    true;


$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

/* ================================================================
   PAGE
================================================================ */

.hh-checkout-page {

    width:
        100%;

    min-height:
        100vh;

    padding:
        42px
        24px
        75px;

    overflow-x:
        hidden;

    color:
        #14213d;

    background:

        radial-gradient(
            circle at 92% 4%,
            rgba(59,130,246,.08),
            transparent 24%
        ),

        linear-gradient(
            180deg,
            #f5f8ff 0%,
            #f8faff 55%,
            #ffffff 100%
        );

    font-family:
        Inter,
        Arial,
        sans-serif;

}


.hh-checkout-container {

    width:
        100%;

    max-width:
        1340px;

    margin:
        0 auto;

}


/* ================================================================
   HERO
================================================================ */

.hh-checkout-hero {

    position:
        relative;

    min-height:
        290px;

    margin-bottom:
        22px;

    padding:
        43px
        50px;

    overflow:
        hidden;

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        340px;

    align-items:
        center;

    gap:
        35px;

    color:
        #ffffff;

    background:

        linear-gradient(
            115deg,
            #0b2c6b 0%,
            #154a98 48%,
            #2784ee 100%
        );

    border-radius:
        27px;

    box-shadow:

        0
        20px
        50px
        rgba(23,79,165,.15);

}


.hh-checkout-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        290px;

    height:
        290px;

    top:
        -155px;

    right:
        -65px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.08);

}


.hh-checkout-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        175px;

    height:
        175px;

    right:
        170px;

    bottom:
        -125px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.055);

}


.hh-checkout-hero-copy {

    position:
        relative;

    z-index:
        2;

}


.hh-checkout-pill {

    min-height:
        33px;

    padding:
        0
        13px;

    margin-bottom:
        17px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #ffffff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.22);

    border-radius:
        999px;

    font-size:
        9px;

    font-weight:
        850;

}


.hh-checkout-hero h1 {

    margin:
        0;

    color:
        #ffffff;

    font-family:
        Poppins,
        Inter,
        sans-serif;

    font-size:

        clamp(
            34px,
            4.3vw,
            51px
        );

    line-height:
        1.08;

    font-weight:
        800;

    letter-spacing:
        -1.7px;

}


.hh-checkout-hero h1 span {

    color:
        #6fe7f3;

}


.hh-checkout-hero p {

    max-width:
        590px;

    margin:
        14px
        0
        0;

    color:
        rgba(255,255,255,.77);

    font-size:
        11px;

    line-height:
        1.75;

}


/* ================================================================
   HERO ART
================================================================ */

.hh-checkout-hero-art {

    position:
        relative;

    z-index:
        2;

    height:
        205px;

}


.hh-checkout-main-icon {

    position:
        absolute;

    width:
        145px;

    height:
        145px;

    top:
        28px;

    right:
        75px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius:
        38px;

    backdrop-filter:
        blur(10px);

    font-size:
        57px;

    transform:
        rotate(-4deg);

}


.hh-checkout-floating {

    position:
        absolute;

    min-width:
        140px;

    padding:
        11px
        13px;

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        #26405f;

    background:
        rgba(255,255,255,.96);

    border-radius:
        12px;

    box-shadow:
        0
        14px
        32px
        rgba(5,35,80,.16);

    font-size:
        8px;

    font-weight:
        850;

}


.hh-checkout-floating i {

    width:
        32px;

    height:
        32px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #eff6ff;

    border-radius:
        9px;

}


.hh-checkout-floating.one {

    top:
        3px;

    left:
        0;

}


.hh-checkout-floating.two {

    right:
        0;

    bottom:
        3px;

}


/* ================================================================
   PROGRESS
================================================================ */

.hh-checkout-progress {

    margin-bottom:
        22px;

    padding:
        18px
        22px;

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        13px;

    background:
        #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius:
        18px;

    box-shadow:
        0
        8px
        24px
        rgba(40,65,120,.045);

}


.hh-progress-step {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

}


.hh-progress-number {

    width:
        37px;

    height:
        37px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:
        #2563eb;

    border-radius:
        10px;

    font-size:
        10px;

    font-weight:
        900;

}


.hh-progress-step:nth-child(2)
.hh-progress-number {

    background:
        #7c3aed;

}


.hh-progress-step:nth-child(3)
.hh-progress-number {

    background:
        #16a34a;

}


.hh-progress-step span {

    display:
        block;

    color:
        #8b98aa;

    font-size:
        6px;

    font-weight:
        850;

    letter-spacing:
        .6px;

}


.hh-progress-step strong {

    display:
        block;

    margin-top:
        2px;

    color:
        #263a55;

    font-size:
        9px;

    font-weight:
        900;

}


/* ================================================================
   ERROR
================================================================ */

.hh-checkout-error {

    margin-bottom:
        20px;

    padding:
        14px
        16px;

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    color:
        #991b1b;

    background:
        #fef2f2;

    border:
        1px solid #fecaca;

    border-radius:
        12px;

    font-size:
        9px;

    font-weight:
        700;

}


/* ================================================================
   LAYOUT
================================================================ */

.hh-checkout-layout {

    display:
        grid;

    grid-template-columns:

        minmax(
            0,
            1fr
        )

        360px;

    align-items:
        start;

    gap:
        21px;

}


/* ================================================================
   MAIN FORM
================================================================ */

.hh-checkout-left {

    min-width:
        0;

    display:
        flex;

    flex-direction:
        column;

    gap:
        18px;

}


.hh-checkout-section {

    overflow:
        hidden;

    background:
        #ffffff;

    border:
        1px solid #e2e9f3;

    border-radius:
        20px;

    box-shadow:
        0
        10px
        28px
        rgba(40,65,120,.045);

}


.hh-checkout-section-header {

    min-height:
        88px;

    padding:
        19px
        22px;

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    border-bottom:
        1px solid #edf1f6;

}


.hh-checkout-section-icon {

    width:
        45px;

    height:
        45px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #438bf2
        );

    border-radius:
        12px;

    box-shadow:
        0
        8px
        18px
        rgba(37,99,235,.18);

}


.hh-checkout-section-header.purple
.hh-checkout-section-icon {

    background:

        linear-gradient(
            135deg,
            #7c3aed,
            #9b6af5
        );

}


.hh-checkout-section-header h2 {

    margin:
        0
        0
        3px;

    color:
        #17233c;

    font-size:
        15px;

    font-weight:
        900;

}


.hh-checkout-section-header p {

    margin:
        0;

    color:
        #8b98aa;

    font-size:
        8px;

}


.hh-checkout-section-body {

    padding:
        22px;

}


/* ================================================================
   DELIVERY OPTIONS
================================================================ */

.hh-delivery-grid {

    display:
        grid;

    grid-template-columns:
        1fr
        1fr;

    gap:
        13px;

}


.hh-choice {

    position:
        relative;

    min-height:
        122px;

    padding:
        17px;

    cursor:
        pointer;

}


.hh-choice input {

    position:
        absolute;

    opacity:
        0;

    pointer-events:
        none;

}


.hh-choice-content {

    width:
        100%;

    height:
        100%;

    min-height:
        110px;

    padding:
        16px;

    display:
        flex;

    align-items:
        flex-start;

    gap:
        12px;

    background:
        #fbfdff;

    border:
        1px solid #dfe7f1;

    border-radius:
        14px;

    transition:
        .18s ease;

}


.hh-choice input:checked +
.hh-choice-content {

    background:
        #eff6ff;

    border-color:
        #7db0f9;

    box-shadow:
        0
        0
        0
        3px
        rgba(59,130,246,.07);

}


.hh-choice-icon {

    width:
        41px;

    height:
        41px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #ffffff;

    border:
        1px solid #dce8f7;

    border-radius:
        11px;

    font-size:
        14px;

}


.hh-choice strong {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #263a55;

    font-size:
        10px;

    font-weight:
        900;

}


.hh-choice span {

    display:
        block;

    color:
        #8593a7;

    font-size:
        7px;

    line-height:
        1.6;

}


/* ================================================================
   ADDRESS
================================================================ */

.hh-address-field {

    display:
        none;

    margin-top:
        17px;

}


.hh-address-field label {

    display:
        block;

    margin-bottom:
        7px;

    color:
        #334155;

    font-size:
        8px;

    font-weight:
        850;

}


.hh-address-field textarea {

    width:
        100%;

    min-height:
        125px;

    padding:
        13px;

    resize:
        vertical;

    outline:
        none;

    color:
        #34445d;

    background:
        #fbfdff;

    border:
        1px solid #dbe5f0;

    border-radius:
        11px;

    font-family:
        inherit;

    font-size:
        9px;

    line-height:
        1.6;

}


.hh-address-field textarea:focus {

    background:
        #ffffff;

    border-color:
        #3b82f6;

    box-shadow:
        0
        0
        0
        3px
        rgba(59,130,246,.08);

}


/* ================================================================
   PAYMENT
================================================================ */

.hh-payment-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        11px;

}


.hh-payment-option {

    position:
        relative;

    cursor:
        pointer;

}


.hh-payment-option input {

    position:
        absolute;

    opacity:
        0;

}


.hh-payment-box {

    min-height:
        81px;

    padding:
        13px;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    color:
        #334155;

    background:
        #fbfdff;

    border:
        1px solid #dfe7f1;

    border-radius:
        12px;

    transition:
        .18s ease;

}


.hh-payment-option input:checked +
.hh-payment-box {

    color:
        #1d4ed8;

    background:
        #eff6ff;

    border-color:
        #7db0f9;

    box-shadow:
        0
        0
        0
        3px
        rgba(59,130,246,.07);

}


.hh-payment-icon {

    width:
        39px;

    height:
        39px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #ffffff;

    border:
        1px solid #dce8f7;

    border-radius:
        10px;

    font-size:
        14px;

}


.hh-payment-box strong {

    display:
        block;

    color:
        inherit;

    font-size:
        9px;

    font-weight:
        900;

}


.hh-payment-box small {

    display:
        block;

    margin-top:
        3px;

    color:
        #8d9aae;

    font-size:
        6px;

}


/* ================================================================
   SIDEBAR
================================================================ */

.hh-checkout-right {

    position:
        sticky;

    top:
        22px;

    display:
        flex;

    flex-direction:
        column;

    gap:
        15px;

}


.hh-summary-card {

    overflow:
        hidden;

    background:
        #ffffff;

    border:
        1px solid #e1e8f2;

    border-radius:
        20px;

    box-shadow:
        0
        12px
        31px
        rgba(40,65,120,.06);

}


.hh-summary-header {

    padding:
        21px;

    border-bottom:
        1px solid #edf1f5;

}


.hh-summary-header-icon {

    width:
        43px;

    height:
        43px;

    margin-bottom:
        12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #438bf2
        );

    border-radius:
        12px;

}


.hh-summary-header small {

    display:
        block;

    margin-bottom:
        3px;

    color:
        #2563eb;

    font-size:
        6px;

    font-weight:
        900;

    letter-spacing:
        .8px;

}


.hh-summary-header h2 {

    margin:
        0;

    color:
        #17233c;

    font-size:
        17px;

    font-weight:
        900;

}


/* ================================================================
   ITEMS
================================================================ */

.hh-checkout-items {

    max-height:
        340px;

    overflow-y:
        auto;

}


.hh-checkout-item {

    padding:
        14px
        18px;

    display:
        grid;

    grid-template-columns:
        62px
        minmax(0,1fr)
        auto;

    align-items:
        center;

    gap:
        10px;

    border-bottom:
        1px solid #edf1f5;

}


.hh-checkout-item:last-child {

    border-bottom:
        0;

}


.hh-checkout-item-image {

    width:
        62px;

    height:
        62px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #2563eb;

    background:
        #f1f6ff;

    border:
        1px solid #deE9f7;

    border-radius:
        11px;

    font-size:
        20px;

}


.hh-checkout-item-image img {

    width:
        100%;

    height:
        100%;

    padding:
        4px;

    object-fit:
        contain;

    object-position:
        center;

}


.hh-checkout-item-info {

    min-width:
        0;

}


.hh-checkout-item-info h3 {

    margin:
        0
        0
        4px;

    overflow:
        hidden;

    color:
        #263a55;

    font-size:
        9px;

    font-weight:
        900;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

}


.hh-checkout-item-info span {

    display:
        block;

    color:
        #8291a5;

    font-size:
        6px;

}


.hh-checkout-item-info small {

    display:
        block;

    margin-top:
        3px;

    color:
        #2563eb;

    font-size:
        6px;

    font-weight:
        800;

}


.hh-checkout-item-price {

    color:
        #183d71;

    font-size:
        8px;

    font-weight:
        900;

    white-space:
        nowrap;

}


/* ================================================================
   TOTAL
================================================================ */

.hh-summary-totals {

    padding:
        18px
        20px;

    background:
        #fbfdff;

    border-top:
        1px solid #edf1f5;

}


.hh-summary-row {

    min-height:
        32px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

}


.hh-summary-row span {

    color:
        #7f8da0;

    font-size:
        7px;

}


.hh-summary-row strong {

    color:
        #31445f;

    font-size:
        8px;

    font-weight:
        850;

}


.hh-summary-divider {

    height:
        1px;

    margin:
        12px 0;

    background:
        #e5eaf1;

}


.hh-summary-grand {

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        12px;

}


.hh-summary-grand span {

    display:
        block;

    color:
        #7b899d;

    font-size:
        7px;

    font-weight:
        900;

    letter-spacing:
        .6px;

}


.hh-summary-grand small {

    display:
        block;

    margin-top:
        3px;

    color:
        #a0acbb;

    font-size:
        6px;

}


.hh-summary-grand strong {

    color:
        #2563eb;

    font-size:
        20px;

    font-weight:
        900;

    white-space:
        nowrap;

}


/* ================================================================
   PLACE ORDER
================================================================ */

.hh-place-order {

    width:
        100%;

    min-height:
        50px;

    margin-top:
        17px;

    padding:
        0
        15px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    color:
        #ffffff;

    background:

        linear-gradient(
            135deg,
            #2563eb,
            #347eee
        );

    border:
        0;

    border-radius:
        11px;

    box-shadow:
        0
        9px
        20px
        rgba(37,99,235,.22);

    font-family:
        inherit;

    font-size:
        9px;

    font-weight:
        900;

    cursor:
        pointer;

    transition:
        .18s ease;

}


.hh-place-order:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0
        12px
        25px
        rgba(37,99,235,.28);

}


.hh-place-order:disabled {

    opacity:
        .65;

    cursor:
        wait;

    transform:
        none;

}


/* ================================================================
   SECURITY
================================================================ */

.hh-security-card {

    padding:
        17px;

    display:
        flex;

    align-items:
        flex-start;

    gap:
        11px;

    background:

        linear-gradient(
            135deg,
            #f3fbf6,
            #ecfdf3
        );

    border:
        1px solid #c8f1d5;

    border-radius:
        16px;

}


.hh-security-icon {

    width:
        38px;

    height:
        38px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #15803d;

    background:
        #ffffff;

    border:
        1px solid #d6f4df;

    border-radius:
        10px;

}


.hh-security-card strong {

    display:
        block;

    margin-bottom:
        4px;

    color:
        #24583a;

    font-size:
        8px;

    font-weight:
        900;

}


.hh-security-card p {

    margin:
        0;

    color:
        #658772;

    font-size:
        7px;

    line-height:
        1.6;

}


/* ================================================================
   BACK
================================================================ */

.hh-back-cart {

    min-height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    color:
        #52647c;

    background:
        #ffffff;

    border:
        1px solid #dce5ef;

    border-radius:
        11px;

    font-size:
        8px;

    font-weight:
        850;

    text-decoration:
        none;

}


.hh-back-cart:hover {

    color:
        #2563eb;

    background:
        #f8fbff;

}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1050px) {

    .hh-checkout-layout {

        grid-template-columns:
            1fr;

    }


    .hh-checkout-right {

        position:
            static;

        display:
            grid;

        grid-template-columns:
            1fr
            1fr;

    }


    .hh-summary-card {

        grid-row:
            span 2;

    }

}


@media (max-width: 850px) {

    .hh-checkout-page {

        padding:
            30px
            18px
            60px;

    }


    .hh-checkout-hero {

        grid-template-columns:
            1fr;

        min-height:
            auto;

        padding:
            37px;

    }


    .hh-checkout-hero-art {

        display:
            none;

    }


    .hh-checkout-progress {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 650px) {

    .hh-checkout-page {

        padding:
            21px
            13px
            50px;

    }


    .hh-checkout-hero {

        padding:
            28px
            23px;

        border-radius:
            21px;

    }


    .hh-checkout-hero h1 {

        font-size:
            31px;

    }


    .hh-delivery-grid,
    .hh-payment-grid {

        grid-template-columns:
            1fr;

    }


    .hh-checkout-section-body {

        padding:
            17px;

    }


    .hh-checkout-right {

        display:
            flex;

    }


    .hh-checkout-item {

        grid-template-columns:
            55px
            minmax(0,1fr);

    }


    .hh-checkout-item-image {

        width:
            55px;

        height:
            55px;

    }


    .hh-checkout-item-price {

        grid-column:
            2;

    }

}

</style>


<!-- ===============================================================
     CHECKOUT
================================================================ -->

<main class="hh-checkout-page">


    <div class="hh-checkout-container">


        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="hh-checkout-hero">


            <div class="hh-checkout-hero-copy">


                <span class="hh-checkout-pill">

                    <i class="bi bi-shield-check"></i>

                    SECURE CHECKOUT

                </span>


                <h1>

                    Almost There.

                    <span>
                        Complete Your Order.
                    </span>

                </h1>


                <p>

                    Choose how you'd like to receive your
                    order, select a payment method and
                    review everything before placing
                    your HochipoHub order.

                </p>


            </div>



            <!-- HERO ART -->

            <div class="hh-checkout-hero-art">


                <div class="hh-checkout-main-icon">

                    <i class="bi bi-bag-check"></i>

                </div>


                <div class="hh-checkout-floating one">

                    <i class="bi bi-box-seam"></i>

                    <span>

                        <?= number_format(
                            $totalItems
                        ) ?>

                        items

                    </span>

                </div>


                <div class="hh-checkout-floating two">

                    <i class="bi bi-shield-lock"></i>

                    <span>
                        Secure order
                    </span>

                </div>


            </div>


        </section>



        <!-- =======================================================
             PROGRESS
        ======================================================== -->

        <section class="hh-checkout-progress">


            <div class="hh-progress-step">


                <div class="hh-progress-number">
                    1
                </div>


                <div>

                    <span>
                        STEP ONE
                    </span>

                    <strong>
                        Delivery
                    </strong>

                </div>


            </div>



            <div class="hh-progress-step">


                <div class="hh-progress-number">
                    2
                </div>


                <div>

                    <span>
                        STEP TWO
                    </span>

                    <strong>
                        Payment
                    </strong>

                </div>


            </div>



            <div class="hh-progress-step">


                <div class="hh-progress-number">
                    3
                </div>


                <div>

                    <span>
                        FINAL STEP
                    </span>

                    <strong>
                        Place Order
                    </strong>

                </div>


            </div>


        </section>



        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (
            !empty($error)
        ): ?>


            <div class="hh-checkout-error">

                <i class="bi bi-exclamation-circle-fill"></i>

                <?= checkoutEscape(
                    $error
                ) ?>

            </div>


        <?php endif; ?>



        <!-- =======================================================
             FORM
        ======================================================== -->

        <form
            method="POST"
            action=""
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

                    <section class="hh-checkout-section">


                        <div class="hh-checkout-section-header">


                            <div class="hh-checkout-section-icon">

                                <i class="bi bi-truck"></i>

                            </div>


                            <div>

                                <h2>
                                    Delivery Method
                                </h2>

                                <p>

                                    Choose how you want
                                    to receive your order.

                                </p>

                            </div>


                        </div>



                        <div class="hh-checkout-section-body">


                            <div class="hh-delivery-grid">


                                <!-- PICKUP -->

                                <label class="hh-choice">


                                    <input
                                        type="radio"
                                        name="delivery_method"
                                        value="Pickup"
                                        <?= $selectedDelivery === 'Pickup'
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >


                                    <div class="hh-choice-content">


                                        <div class="hh-choice-icon">

                                            <i class="bi bi-shop"></i>

                                        </div>


                                        <div>

                                            <strong>
                                                Pickup
                                            </strong>

                                            <span>

                                                Collect your order
                                                directly from the
                                                seller. No delivery
                                                address required.

                                            </span>

                                        </div>


                                    </div>


                                </label>



                                <!-- POSTAGE -->

                                <label class="hh-choice">


                                    <input
                                        type="radio"
                                        name="delivery_method"
                                        value="Postage"
                                        <?= $selectedDelivery === 'Postage'
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >


                                    <div class="hh-choice-content">


                                        <div class="hh-choice-icon">

                                            <i class="bi bi-box-seam"></i>

                                        </div>


                                        <div>

                                            <strong>
                                                Postage
                                            </strong>

                                            <span>

                                                Have your order
                                                delivered to your
                                                preferred address.

                                            </span>

                                        </div>


                                    </div>


                                </label>


                            </div>



                            <!-- ADDRESS -->

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

                    <section class="hh-checkout-section">


                        <div class="
                            hh-checkout-section-header
                            purple
                        ">


                            <div class="hh-checkout-section-icon">

                                <i class="bi bi-credit-card"></i>

                            </div>


                            <div>

                                <h2>
                                    Payment Method
                                </h2>

                                <p>

                                    Select your preferred
                                    payment option.

                                </p>

                            </div>


                        </div>



                        <div class="hh-checkout-section-body">


                            <div class="hh-payment-grid">


                                <!-- FPX -->

                                <label class="hh-payment-option">


                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="FPX"
                                        <?= $selectedPayment === 'FPX'
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >


                                    <div class="hh-payment-box">


                                        <div class="hh-payment-icon">

                                            <i class="bi bi-bank"></i>

                                        </div>


                                        <div>

                                            <strong>
                                                Online Banking
                                            </strong>

                                            <small>
                                                FPX
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
                                        <?= $selectedPayment === 'Credit Card'
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >


                                    <div class="hh-payment-box">


                                        <div class="hh-payment-icon">

                                            <i class="bi bi-credit-card-2-front"></i>

                                        </div>


                                        <div>

                                            <strong>
                                                Credit Card
                                            </strong>

                                            <small>
                                                Card payment
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
                                        <?= $selectedPayment === 'Debit Card'
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >


                                    <div class="hh-payment-box">


                                        <div class="hh-payment-icon">

                                            <i class="bi bi-credit-card"></i>

                                        </div>


                                        <div>

                                            <strong>
                                                Debit Card
                                            </strong>

                                            <small>
                                                Direct card payment
                                            </small>

                                        </div>


                                    </div>


                                </label>



                                <!-- CASH -->

                                <label class="hh-payment-option">


                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="Cash"
                                        <?= $selectedPayment === 'Cash'
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >


                                    <div class="hh-payment-box">


                                        <div class="hh-payment-icon">

                                            <i class="bi bi-cash-stack"></i>

                                        </div>


                                        <div>

                                            <strong>
                                                Cash
                                            </strong>

                                            <small>
                                                Pay offline
                                            </small>

                                        </div>


                                    </div>


                                </label>


                            </div>


                        </div>


                    </section>


                </div>



                <!-- =================================================
                     RIGHT
                ================================================== -->

                <aside class="hh-checkout-right">


                    <!-- =============================================
                         SUMMARY
                    ============================================== -->

                    <section class="hh-summary-card">


                        <div class="hh-summary-header">


                            <div class="hh-summary-header-icon">

                                <i class="bi bi-receipt"></i>

                            </div>


                            <small>
                                ORDER SUMMARY
                            </small>


                            <h2>
                                Review Your Order
                            </h2>


                        </div>



                        <!-- ITEMS -->

                        <div class="hh-checkout-items">


                            <?php foreach (
                                $cartItems
                                as $item
                            ): ?>


                                <?php

                                $image =
                                    checkoutProductImage(
                                        $item['image']
                                        ?? ''
                                    );


                                $lineTotal =
                                    (float)
                                    $item['price']
                                    *
                                    (int)
                                    $item['quantity'];

                                ?>


                                <div class="hh-checkout-item">


                                    <div class="hh-checkout-item-image">


                                        <?php if (
                                            $image !== ''
                                        ): ?>


                                            <img
                                                src="<?= checkoutEscape(
                                                    $image
                                                ) ?>"
                                                alt="<?= checkoutEscape(
                                                    $item[
                                                        'product_name'
                                                    ]
                                                ) ?>"
                                                onerror="
                                                    this.style.display='none';
                                                    this.parentElement.innerHTML='<i class=&quot;bi bi-image&quot;></i>';
                                                "
                                            >


                                        <?php else: ?>


                                            <i class="bi bi-image"></i>


                                        <?php endif; ?>


                                    </div>



                                    <div class="hh-checkout-item-info">


                                        <h3>

                                            <?= checkoutEscape(
                                                $item[
                                                    'product_name'
                                                ]
                                            ) ?>

                                        </h3>


                                        <span>

                                            <?= checkoutEscape(
                                                $item[
                                                    'business_name'
                                                ]
                                            ) ?>

                                        </span>


                                        <small>

                                            Qty:
                                            <?= (int)
                                                $item[
                                                    'quantity'
                                                ] ?>

                                        </small>


                                    </div>



                                    <div class="hh-checkout-item-price">

                                        RM
                                        <?= number_format(
                                            $lineTotal,
                                            2
                                        ) ?>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>



                        <!-- TOTALS -->

                        <div class="hh-summary-totals">


                            <div class="hh-summary-row">

                                <span>
                                    Items
                                </span>

                                <strong>

                                    <?= number_format(
                                        $totalItems
                                    ) ?>

                                </strong>

                            </div>


                            <div class="hh-summary-row">

                                <span>
                                    Sellers
                                </span>

                                <strong>

                                    <?= number_format(
                                        $vendorCount
                                    ) ?>

                                </strong>

                            </div>


                            <div class="hh-summary-row">

                                <span>
                                    Subtotal
                                </span>

                                <strong>

                                    RM
                                    <?= number_format(
                                        $subtotal,
                                        2
                                    ) ?>

                                </strong>

                            </div>


                            <div class="hh-summary-row">

                                <span>
                                    Delivery
                                </span>

                                <strong>
                                    RM 0.00
                                </strong>

                            </div>


                            <div class="hh-summary-divider"></div>


                            <div class="hh-summary-grand">


                                <div>

                                    <span>
                                        TOTAL
                                    </span>

                                    <small>
                                        Final order amount
                                    </small>

                                </div>


                                <strong>

                                    RM
                                    <?= number_format(
                                        $grandTotal,
                                        2
                                    ) ?>

                                </strong>


                            </div>



                            <!-- PLACE ORDER -->

                            <button
                                type="submit"
                                class="hh-place-order"
                                id="placeOrderButton"
                            >


                                <span>

                                    <i class="bi bi-lock-fill"></i>

                                    Place Order

                                </span>


                                <i class="bi bi-arrow-right"></i>


                            </button>


                        </div>


                    </section>



                    <!-- =============================================
                         SECURITY
                    ============================================== -->

                    <section class="hh-security-card">


                        <div class="hh-security-icon">

                            <i class="bi bi-shield-check"></i>

                        </div>


                        <div>

                            <strong>
                                Secure checkout
                            </strong>


                            <p>

                                Review your delivery,
                                payment and order details
                                carefully before confirming.

                            </p>

                        </div>


                    </section>



                    <!-- BACK CART -->

                    <a
                        href="cart.php"
                        class="hh-back-cart"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Back to Cart

                    </a>


                </aside>


            </div>


        </form>


    </div>


</main>



<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | DELIVERY
        |--------------------------------------------------------------------------
        */

        const deliveryInputs =
            document.querySelectorAll(
                'input[name="delivery_method"]'
            );


        const addressField =
            document.getElementById(
                'addressField'
            );


        const address =
            document.getElementById(
                'delivery_address'
            );


        function updateAddressField() {

            const selected =
                document.querySelector(
                    'input[name="delivery_method"]:checked'
                );


            if (
                selected &&
                selected.value === 'Postage'
            ) {

                addressField.style.display =
                    'block';


                address.required =
                    true;


            } else {

                addressField.style.display =
                    'none';


                address.required =
                    false;

            }
        }


        deliveryInputs.forEach(
            function (input) {

                input.addEventListener(
                    'change',
                    updateAddressField
                );

            }
        );


        updateAddressField();



        /*
        |--------------------------------------------------------------------------
        | PLACE ORDER LOADING
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById(
                'checkoutForm'
            );


        const button =
            document.getElementById(
                'placeOrderButton'
            );


        if (
            form &&
            button
        ) {

            form.addEventListener(
                'submit',
                function () {


                    /*
                    |--------------------------------------------------------------------------
                    | LET BROWSER VALIDATE FIRST
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !form.checkValidity()
                    ) {

                        return;
                    }


                    button.disabled =
                        true;


                    button.innerHTML = `

                        <span>

                            <i class="bi bi-hourglass-split"></i>

                            Processing Order...

                        </span>

                        <i class="bi bi-arrow-right"></i>

                    `;

                }
            );
        }

    }
);

</script>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/includes/footer.php';

?>