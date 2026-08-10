<?php

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$db = getDB();

$user_id = (int) $_SESSION['user_id'];

$error = '';
$success = false;


/*
|--------------------------------------------------------------------------
| GET CART
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        c.cart_id,
        c.product_id,
        c.quantity,

        p.product_name,
        p.price,
        p.stock_quantity,
        p.image,

        v.vendor_id,
        v.business_name

    FROM cart c

    INNER JOIN products p
        ON c.product_id = p.product_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    WHERE c.customer_id = ?

    ORDER BY v.business_name ASC, p.product_name ASC
");

$stmt->execute([$user_id]);

$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


if (empty($cartItems)) {

    header("Location: cart.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CALCULATE TOTAL
|--------------------------------------------------------------------------
*/

$subtotal = 0;

foreach ($cartItems as $item) {

    $subtotal +=
        (float) $item['price'] *
        (int) $item['quantity'];

}


/*
|--------------------------------------------------------------------------
| CHECK STOCK
|--------------------------------------------------------------------------
*/

foreach ($cartItems as $item) {

    if ((int) $item['quantity'] > (int) $item['stock_quantity']) {

        $error =
            "Insufficient stock for " .
            $item['product_name'];

        break;
    }

}


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {

    $delivery_method =
        $_POST['delivery_method'] ?? '';

    $delivery_address =
        trim($_POST['delivery_address'] ?? '');

    $payment_method =
        $_POST['payment_method'] ?? '';


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


    if (!in_array($delivery_method, $allowedDelivery, true)) {

        $error = "Please select a valid delivery method.";

    } elseif (
        $delivery_method === 'Postage'
        && empty($delivery_address)
    ) {

        $error =
            "Delivery address is required for postage.";

    } elseif (
        !in_array($payment_method, $allowedPayment, true)
    ) {

        $error =
            "Please select a valid payment method.";

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

            $stmt = $db->prepare("
                INSERT INTO orders (
                    customer_id,
                    total_amount,
                    delivery_method,
                    delivery_address,
                    order_status
                )

                VALUES (?, ?, ?, ?, 'Pending')
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
                (int) $db->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | CREATE ORDER DETAILS
            |--------------------------------------------------------------------------
            */

            $detailStmt = $db->prepare("
                INSERT INTO order_details (
                    order_id,
                    product_id,
                    quantity,
                    unit_price,
                    subtotal
                )

                VALUES (?, ?, ?, ?, ?)
            ");


            /*
            |--------------------------------------------------------------------------
            | GROUP ITEMS BY VENDOR
            |--------------------------------------------------------------------------
            */

            $vendorTotals = [];


            foreach ($cartItems as $item) {

                $quantity =
                    (int) $item['quantity'];

                $price =
                    (float) $item['price'];

                $itemSubtotal =
                    $quantity * $price;


                /*
                |--------------------------------------------------------------------------
                | ORDER DETAIL
                |--------------------------------------------------------------------------
                */

                $detailStmt->execute([

                    $order_id,

                    (int) $item['product_id'],

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
                    (int) $item['vendor_id'];


                if (!isset($vendorTotals[$vendor_id])) {

                    $vendorTotals[$vendor_id] = 0;

                }


                $vendorTotals[$vendor_id]
                    += $itemSubtotal;


                /*
                |--------------------------------------------------------------------------
                | REDUCE STOCK
                |--------------------------------------------------------------------------
                */

                $stockStmt = $db->prepare("
                    UPDATE products

                    SET
                        stock_quantity =
                            stock_quantity - ?,

                        status =
                            CASE
                                WHEN stock_quantity - ? <= 0
                                THEN 'Out of Stock'
                                ELSE 'Available'
                            END

                    WHERE product_id = ?
                      AND stock_quantity >= ?
                ");


                $stockStmt->execute([

                    $quantity,

                    $quantity,

                    (int) $item['product_id'],

                    $quantity

                ]);


                if ($stockStmt->rowCount() === 0) {

                    throw new Exception(
                        "Stock changed for " .
                        $item['product_name'] .
                        ". Please try again."
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE INVENTORY
                |--------------------------------------------------------------------------
                */

                $inventoryStmt = $db->prepare("
                    INSERT INTO inventory (
                        product_id,
                        quantity
                    )

                    VALUES (?, ?)

                    ON DUPLICATE KEY UPDATE
                        quantity = VALUES(quantity)
                ");


                $newStock =
                    (int) $item['stock_quantity']
                    - $quantity;


                $inventoryStmt->execute([

                    (int) $item['product_id'],

                    max(0, $newStock)

                ]);

            }


            /*
            |--------------------------------------------------------------------------
            | CREATE VENDOR ORDERS
            |--------------------------------------------------------------------------
            */

            $vendorOrderStmt = $db->prepare("
                INSERT INTO vendor_orders (
                    order_id,
                    vendor_id,
                    subtotal,
                    delivery_fee,
                    vendor_status
                )

                VALUES (?, ?, ?, 0.00, 'Pending')
            ");


            foreach ($vendorTotals as $vendor_id => $vendorTotal) {

                $vendorOrderStmt->execute([

                    $order_id,

                    $vendor_id,

                    $vendorTotal

                ]);

            }


            /*
            |--------------------------------------------------------------------------
            | CREATE PAYMENT
            |--------------------------------------------------------------------------
            */

            $paymentStatus =
                $payment_method === 'Cash'
                    ? 'Pending'
                    : 'Pending';


            $paymentStmt = $db->prepare("
                INSERT INTO payments (
                    order_id,
                    payment_method,
                    payment_status,
                    amount
                )

                VALUES (?, ?, ?, ?)
            ");


            $paymentStmt->execute([

                $order_id,

                $payment_method,

                $paymentStatus,

                $subtotal

            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATE COMMISSION
            |--------------------------------------------------------------------------
            |
            | Default commission rate:
            | 5%
            |
            | Change this value later if needed.
            |
            */

            $commissionRate = 5.00;


            $commissionStmt = $db->prepare("
                INSERT INTO commission (
                    vendor_id,
                    order_id,
                    vendor_order_id,
                    commission_rate,
                    commission_amount,
                    status
                )

                VALUES (?, ?, ?, ?, ?, 'Pending')
            ");


            foreach ($vendorTotals as $vendor_id => $vendorTotal) {

                /*
                | Get vendor order ID
                */

                $vendorOrderLookup = $db->prepare("
                    SELECT vendor_order_id
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
                    $vendorOrderLookup->fetch(PDO::FETCH_ASSOC);


                if (!$vendorOrder) {

                    throw new Exception(
                        "Unable to create vendor order."
                    );

                }


                $commissionAmount =
                    $vendorTotal *
                    ($commissionRate / 100);


                $commissionStmt->execute([

                    $vendor_id,

                    $order_id,

                    (int) $vendorOrder['vendor_order_id'],

                    $commissionRate,

                    $commissionAmount

                ]);

            }


            /*
            |--------------------------------------------------------------------------
            | CLEAR CART
            |--------------------------------------------------------------------------
            */

            $clearCart = $db->prepare("
                DELETE FROM cart
                WHERE customer_id = ?
            ");

            $clearCart->execute([$user_id]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $db->commit();


            $success = true;


            header(
                "Location: order_details.php?id=" .
                $order_id .
                "&success=1"
            );

            exit;


        } catch (Exception $e) {

            if ($db->inTransaction()) {

                $db->rollBack();

            }

            $error =
                "Checkout failed: " .
                $e->getMessage();

        }

    }

}


$pageTitle = "Checkout - HochipoHub";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="checkout-page">

    <div class="checkout-container">

        <div class="checkout-header">

            <span class="small-label">
                HOCHIPOHUB
            </span>

            <h1>
                Checkout
            </h1>

            <p>
                Complete your order securely.
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            class="checkout-form"
        >


            <section class="checkout-section">

                <div class="section-heading">

                    <h2>
                        Delivery
                    </h2>

                </div>


                <div class="delivery-options">

                    <label class="delivery-option">

                        <input
                            type="radio"
                            name="delivery_method"
                            value="Pickup"
                            required
                        >

                        <div>

                            <strong>
                                Pickup
                            </strong>

                            <span>
                                Collect your order directly.
                            </span>

                        </div>

                    </label>


                    <label class="delivery-option">

                        <input
                            type="radio"
                            name="delivery_method"
                            value="Postage"
                            required
                        >

                        <div>

                            <strong>
                                Postage
                            </strong>

                            <span>
                                Deliver the order to your address.
                            </span>

                        </div>

                    </label>

                </div>


                <div
                    class="address-field"
                    id="addressField"
                >

                    <label for="delivery_address">
                        Delivery Address
                    </label>

                    <textarea
                        id="delivery_address"
                        name="delivery_address"
                        rows="4"
                        placeholder="Enter your full delivery address..."
                    ></textarea>

                </div>

            </section>


            <section class="checkout-section">

                <div class="section-heading">

                    <h2>
                        Payment
                    </h2>

                </div>


                <div class="payment-options">

                    <?php foreach (
                        [
                            'FPX' => 'Online Banking (FPX)',
                            'Credit Card' => 'Credit Card',
                            'Debit Card' => 'Debit Card',
                            'Cash' => 'Cash'
                        ]
                        as $value => $label
                    ): ?>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="<?= htmlspecialchars($value) ?>"
                                required
                            >

                            <span>
                                <?= htmlspecialchars($label) ?>
                            </span>

                        </label>

                    <?php endforeach; ?>

                </div>

            </section>


            <section class="checkout-section">

                <div class="section-heading">

                    <h2>
                        Order Summary
                    </h2>

                </div>


                <div class="checkout-items">

                    <?php foreach ($cartItems as $item): ?>

                        <div class="checkout-item">

                            <div class="checkout-item-image">

                                <?php if (!empty($item['image'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($item['image']) ?>"
                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                    >

                                <?php else: ?>

                                    <div>
                                        🛍️
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="checkout-item-info">

                                <h3>
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </h3>

                                <span>
                                    <?= htmlspecialchars($item['business_name']) ?>
                                </span>

                                <small>
                                    Qty:
                                    <?= (int) $item['quantity'] ?>
                                </small>

                            </div>


                            <div class="checkout-item-price">

                                RM
                                <?= number_format(
                                    (float) $item['price']
                                    * (int) $item['quantity'],
                                    2
                                ) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <div class="checkout-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        RM <?= number_format($subtotal, 2) ?>
                    </strong>

                </div>

            </section>


            <button
                type="submit"
                class="btn-checkout"
            >
                Place Order
            </button>


        </form>

    </div>

</main>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const deliveryInputs =
        document.querySelectorAll(
            'input[name="delivery_method"]'
        );

    const addressField =
        document.getElementById(
            "addressField"
        );

    const address =
        document.getElementById(
            "delivery_address"
        );


    function updateAddressField() {

        const selected =
            document.querySelector(
                'input[name="delivery_method"]:checked'
            );


        if (
            selected &&
            selected.value === "Postage"
        ) {

            addressField.style.display = "block";

            address.required = true;

        } else {

            addressField.style.display = "none";

            address.required = false;

            address.value = "";

        }

    }


    deliveryInputs.forEach(function (input) {

        input.addEventListener(
            "change",
            updateAddressField
        );

    });


    updateAddressField();

});

</script>


<?php require_once __DIR__ . '/includes/footer.php'; ?>