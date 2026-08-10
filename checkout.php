<?php
// =========================================================
// HOCHIPO HUB
// File: checkout.php
// Customer Checkout
//
// FLOW:
//
// cart
//   ↓
// checkout.php
//   ↓
// orders
//   ↓
// order_details
//   ↓
// vendor_orders
//   ↓
// payment.php
//
// =========================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


// =========================================================
// DATABASE CONNECTION
// =========================================================

$db = $conn ?? $pdo ?? null;

if (!$db) {
    die('Database connection not found.');
}


// =========================================================
// HELPER
// =========================================================

function checkout_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =========================================================
// LOGIN CHECK
// =========================================================

if (!isset($_SESSION['user_id'])) {

    header('Location: index.php');
    exit;
}

$customer_id = (int) $_SESSION['user_id'];


// =========================================================
// GET CUSTOMER INFORMATION
// =========================================================

$customer = null;

try {

    $sql = "
        SELECT
            user_id,
            name,
            email,
            phone

        FROM users

        WHERE user_id = ?
        LIMIT 1
    ";

    if ($db instanceof PDO) {

        $stmt = $db->prepare($sql);
        $stmt->execute([$customer_id]);

        $customer = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

    } else {

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'i',
            $customer_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $customer = $result->fetch_assoc();

        $stmt->close();
    }

} catch (Throwable $e) {

    $customer = null;
}

if (!$customer) {

    session_destroy();

    header('Location: index.php');
    exit;
}


// =========================================================
// GET CART ITEMS
// =========================================================

$cart_items = [];

try {

    $sql = "
        SELECT

            c.cart_id,
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

            cat.category_name

        FROM cart c

        INNER JOIN products p
            ON c.product_id = p.product_id

        INNER JOIN vendors v
            ON p.vendor_id = v.vendor_id

        LEFT JOIN categories cat
            ON p.category_id = cat.category_id

        WHERE c.customer_id = ?

        ORDER BY v.business_name ASC,
                 p.product_name ASC
    ";

    if ($db instanceof PDO) {

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $customer_id
        ]);

        $cart_items =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } else {

        $stmt = $db->prepare($sql);

        $stmt->bind_param(
            'i',
            $customer_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }

        $stmt->close();
    }

} catch (Throwable $e) {

    $cart_items = [];
}


// =========================================================
// EMPTY CART
// =========================================================

if (empty($cart_items)) {

    header('Location: cart.php');
    exit;
}


// =========================================================
// VALIDATE STOCK
// =========================================================

$stock_error = '';

foreach ($cart_items as $item) {

    $quantity =
        (int) $item['quantity'];

    $stock =
        (int) $item['stock_quantity'];

    $status =
        $item['status'];

    if ($status !== 'Available') {

        $stock_error =
            $item['product_name'] .
            ' is currently unavailable.';

        break;
    }

    if ($quantity <= 0) {

        $stock_error =
            'Invalid quantity for ' .
            $item['product_name'] . '.';

        break;
    }

    if ($quantity > $stock) {

        $stock_error =
            'Not enough stock for ' .
            $item['product_name'] .
            '. Available stock: ' .
            $stock . '.';

        break;
    }
}


// =========================================================
// CALCULATE TOTAL
// =========================================================

$subtotal = 0;

foreach ($cart_items as $item) {

    $subtotal +=
        (float) $item['price'] *
        (int) $item['quantity'];
}


// =========================================================
// DELIVERY
//
// Database only supports:
// Pickup
// Postage
//
// No automatic delivery fee is added here.
// =========================================================

$delivery_method =
    $_POST['delivery_method']
    ?? 'Pickup';

if (
    !in_array(
        $delivery_method,
        ['Pickup', 'Postage'],
        true
    )
) {

    $delivery_method = 'Pickup';
}

$delivery_address =
    trim(
        $_POST['delivery_address']
        ?? ''
    );


// =========================================================
// PROCESS CHECKOUT
// =========================================================

$error_message = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['place_order'])
) {

    // -----------------------------------------------------
    // CSRF-LIKE SESSION TOKEN
    // -----------------------------------------------------

    if (
        !isset(
            $_SESSION['checkout_token']
        )
    ) {

        $_SESSION['checkout_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    $submitted_token =
        $_POST['checkout_token']
        ?? '';

    if (
        !hash_equals(
            $_SESSION['checkout_token'],
            $submitted_token
        )
    ) {

        $error_message =
            'Invalid checkout request. Please try again.';

    } elseif ($stock_error !== '') {

        $error_message =
            $stock_error;

    } elseif (
        $delivery_method === 'Postage'
        &&
        $delivery_address === ''
    ) {

        $error_message =
            'Please enter your delivery address.';

    } else {

        try {

            // =================================================
            // START TRANSACTION
            // =================================================

            if ($db instanceof PDO) {

                $db->beginTransaction();

            } else {

                $db->begin_transaction();
            }


            // =================================================
            // RE-CHECK CART + STOCK
            // Prevent checkout using stale cart information.
            // =================================================

            $verify_sql = "
                SELECT

                    c.cart_id,
                    c.product_id,
                    c.quantity,

                    p.product_name,
                    p.price,
                    p.stock_quantity,
                    p.status,

                    p.vendor_id

                FROM cart c

                INNER JOIN products p
                    ON c.product_id = p.product_id

                WHERE c.customer_id = ?

                FOR UPDATE
            ";

            $verified_items = [];

            if ($db instanceof PDO) {

                $stmt =
                    $db->prepare(
                        $verify_sql
                    );

                $stmt->execute([
                    $customer_id
                ]);

                $verified_items =
                    $stmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );

            } else {

                $stmt =
                    $db->prepare(
                        $verify_sql
                    );

                $stmt->bind_param(
                    'i',
                    $customer_id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                while (
                    $row =
                    $result->fetch_assoc()
                ) {

                    $verified_items[] =
                        $row;
                }

                $stmt->close();
            }


            if (empty($verified_items)) {

                throw new Exception(
                    'Your cart is empty.'
                );
            }


            // =================================================
            // CALCULATE VERIFIED TOTAL
            // =================================================

            $verified_total = 0;

            foreach (
                $verified_items
                as $item
            ) {

                if (
                    $item['status']
                    !== 'Available'
                ) {

                    throw new Exception(
                        $item['product_name'] .
                        ' is no longer available.'
                    );
                }

                if (
                    (int)
                    $item['quantity']
                    >
                    (int)
                    $item['stock_quantity']
                ) {

                    throw new Exception(
                        'Not enough stock for ' .
                        $item['product_name'] .
                        '.'
                    );
                }

                $verified_total +=
                    (float)
                    $item['price']
                    *
                    (int)
                    $item['quantity'];
            }


            // =================================================
            // CREATE MAIN ORDER
            // =================================================

            $order_sql = "
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
            ";

            if ($db instanceof PDO) {

                $stmt =
                    $db->prepare(
                        $order_sql
                    );

                $stmt->execute([
                    $customer_id,
                    $verified_total,
                    $delivery_method,
                    $delivery_address
                ]);

                $order_id =
                    (int)
                    $db->lastInsertId();

            } else {

                $stmt =
                    $db->prepare(
                        $order_sql
                    );

                $stmt->bind_param(
                    'idss',
                    $customer_id,
                    $verified_total,
                    $delivery_method,
                    $delivery_address
                );

                $stmt->execute();

                $order_id =
                    (int)
                    $db->insert_id;

                $stmt->close();
            }


            if ($order_id <= 0) {

                throw new Exception(
                    'Unable to create order.'
                );
            }


            // =================================================
            // CREATE ORDER DETAILS
            // =================================================

            $detail_sql = "
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
            ";


            // Used to calculate vendor subtotals.
            $vendor_totals = [];


            foreach (
                $verified_items
                as $item
            ) {

                $product_id =
                    (int)
                    $item['product_id'];

                $quantity =
                    (int)
                    $item['quantity'];

                $unit_price =
                    (float)
                    $item['price'];

                $item_subtotal =
                    $unit_price *
                    $quantity;

                $vendor_id =
                    (int)
                    $item['vendor_id'];


                // ---------------------------------------------
                // ORDER DETAIL
                // ---------------------------------------------

                if ($db instanceof PDO) {

                    $stmt =
                        $db->prepare(
                            $detail_sql
                        );

                    $stmt->execute([
                        $order_id,
                        $product_id,
                        $quantity,
                        $unit_price,
                        $item_subtotal
                    ]);

                } else {

                    $stmt =
                        $db->prepare(
                            $detail_sql
                        );

                    $stmt->bind_param(
                        'iiidd',
                        $order_id,
                        $product_id,
                        $quantity,
                        $unit_price,
                        $item_subtotal
                    );

                    $stmt->execute();

                    $stmt->close();
                }


                // ---------------------------------------------
                // GROUP BY VENDOR
                // ---------------------------------------------

                if (
                    !isset(
                        $vendor_totals[
                            $vendor_id
                        ]
                    )
                ) {

                    $vendor_totals[
                        $vendor_id
                    ] = 0;
                }

                $vendor_totals[
                    $vendor_id
                ] += $item_subtotal;


                // ---------------------------------------------
                // REDUCE STOCK
                // ---------------------------------------------

                $stock_sql = "
                    UPDATE products

                    SET
                        stock_quantity =
                            stock_quantity - ?

                    WHERE product_id = ?

                      AND stock_quantity >= ?
                ";

                if ($db instanceof PDO) {

                    $stmt =
                        $db->prepare(
                            $stock_sql
                        );

                    $stmt->execute([
                        $quantity,
                        $product_id,
                        $quantity
                    ]);

                    if (
                        $stmt->rowCount()
                        !== 1
                    ) {

                        throw new Exception(
                            'Unable to update stock for ' .
                            $item['product_name'] .
                            '.'
                        );
                    }

                } else {

                    $stmt =
                        $db->prepare(
                            $stock_sql
                        );

                    $stmt->bind_param(
                        'iii',
                        $quantity,
                        $product_id,
                        $quantity
                    );

                    $stmt->execute();

                    if (
                        $stmt->affected_rows
                        !== 1
                    ) {

                        $stmt->close();

                        throw new Exception(
                            'Unable to update stock for ' .
                            $item['product_name'] .
                            '.'
                        );
                    }

                    $stmt->close();
                }


                // ---------------------------------------------
                // UPDATE PRODUCT STATUS
                // ---------------------------------------------

                $status_sql = "
                    UPDATE products

                    SET status =
                        CASE
                            WHEN stock_quantity <= 0
                            THEN 'Out of Stock'

                            ELSE 'Available'
                        END

                    WHERE product_id = ?
                ";

                if ($db instanceof PDO) {

                    $stmt =
                        $db->prepare(
                            $status_sql
                        );

                    $stmt->execute([
                        $product_id
                    ]);

                } else {

                    $stmt =
                        $db->prepare(
                            $status_sql
                        );

                    $stmt->bind_param(
                        'i',
                        $product_id
                    );

                    $stmt->execute();

                    $stmt->close();
                }


                // ---------------------------------------------
                // UPDATE INVENTORY TABLE
                // ---------------------------------------------

                $inventory_check_sql = "
                    SELECT
                        inventory_id

                    FROM inventory

                    WHERE product_id = ?

                    LIMIT 1
                ";

                $inventory_exists = false;

                if ($db instanceof PDO) {

                    $stmt =
                        $db->prepare(
                            $inventory_check_sql
                        );

                    $stmt->execute([
                        $product_id
                    ]);

                    $inventory_exists =
                        (bool)
                        $stmt->fetch(
                            PDO::FETCH_ASSOC
                        );

                } else {

                    $stmt =
                        $db->prepare(
                            $inventory_check_sql
                        );

                    $stmt->bind_param(
                        'i',
                        $product_id
                    );

                    $stmt->execute();

                    $result =
                        $stmt->get_result();

                    $inventory_exists =
                        (bool)
                        $result->fetch_assoc();

                    $stmt->close();
                }


                if ($inventory_exists) {

                    $inventory_update_sql = "
                        UPDATE inventory

                        SET quantity = ?

                        WHERE product_id = ?
                    ";

                    /*
                     * Re-read current product stock.
                     */

                    $stock_read_sql = "
                        SELECT stock_quantity
                        FROM products
                        WHERE product_id = ?
                    ";

                    if ($db instanceof PDO) {

                        $stmt =
                            $db->prepare(
                                $stock_read_sql
                            );

                        $stmt->execute([
                            $product_id
                        ]);

                        $stock_row =
                            $stmt->fetch(
                                PDO::FETCH_ASSOC
                            );

                        $current_stock =
                            (int)
                            $stock_row[
                                'stock_quantity'
                            ];

                    } else {

                        $stmt =
                            $db->prepare(
                                $stock_read_sql
                            );

                        $stmt->bind_param(
                            'i',
                            $product_id
                        );

                        $stmt->execute();

                        $result =
                            $stmt->get_result();

                        $stock_row =
                            $result->fetch_assoc();

                        $current_stock =
                            (int)
                            $stock_row[
                                'stock_quantity'
                            ];

                        $stmt->close();
                    }


                    if ($db instanceof PDO) {

                        $stmt =
                            $db->prepare(
                                $inventory_update_sql
                            );

                        $stmt->execute([
                            $current_stock,
                            $product_id
                        ]);

                    } else {

                        $stmt =
                            $db->prepare(
                                $inventory_update_sql
                            );

                        $stmt->bind_param(
                            'ii',
                            $current_stock,
                            $product_id
                        );

                        $stmt->execute();

                        $stmt->close();
                    }

                } else {

                    $inventory_insert_sql = "
                        INSERT INTO inventory
                        (
                            product_id,
                            quantity
                        )

                        SELECT
                            product_id,
                            stock_quantity

                        FROM products

                        WHERE product_id = ?
                    ";

                    if ($db instanceof PDO) {

                        $stmt =
                            $db->prepare(
                                $inventory_insert_sql
                            );

                        $stmt->execute([
                            $product_id
                        ]);

                    } else {

                        $stmt =
                            $db->prepare(
                                $inventory_insert_sql
                            );

                        $stmt->bind_param(
                            'i',
                            $product_id
                        );

                        $stmt->execute();

                        $stmt->close();
                    }
                }
            }


            // =================================================
            // CREATE VENDOR ORDERS
            //
            // One main order can contain many vendors.
            // Each vendor gets their own sub-order.
            // =================================================

            $vendor_order_sql = "
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
            ";


            foreach (
                $vendor_totals
                as $vendor_id => $vendor_subtotal
            ) {

                if ($db instanceof PDO) {

                    $stmt =
                        $db->prepare(
                            $vendor_order_sql
                        );

                    $stmt->execute([
                        $order_id,
                        $vendor_id,
                        $vendor_subtotal
                    ]);

                } else {

                    $stmt =
                        $db->prepare(
                            $vendor_order_sql
                        );

                    $stmt->bind_param(
                        'iid',
                        $order_id,
                        $vendor_id,
                        $vendor_subtotal
                    );

                    $stmt->execute();

                    $stmt->close();
                }
            }


            // =================================================
            // CLEAR CART
            // =================================================

            $clear_cart_sql = "
                DELETE FROM cart

                WHERE customer_id = ?
            ";

            if ($db instanceof PDO) {

                $stmt =
                    $db->prepare(
                        $clear_cart_sql
                    );

                $stmt->execute([
                    $customer_id
                ]);

            } else {

                $stmt =
                    $db->prepare(
                        $clear_cart_sql
                    );

                $stmt->bind_param(
                    'i',
                    $customer_id
                );

                $stmt->execute();

                $stmt->close();
            }


            // =================================================
            // COMMIT
            // =================================================

            if ($db instanceof PDO) {

                $db->commit();

            } else {

                $db->commit();
            }


            // =================================================
            // STORE ORDER ID
            // =================================================

            $_SESSION['last_order_id'] =
                $order_id;


            // Regenerate checkout token
            $_SESSION['checkout_token'] =
                bin2hex(
                    random_bytes(32)
                );


            // =================================================
            // GO TO PAYMENT
            // =================================================

            header(
                'Location: payment.php?order_id=' .
                $order_id
            );

            exit;

        } catch (Throwable $e) {

            // -----------------------------------------------
            // ROLLBACK
            // -----------------------------------------------

            try {

                if ($db instanceof PDO) {

                    if (
                        $db->inTransaction()
                    ) {

                        $db->rollBack();
                    }

                } else {

                    $db->rollback();
                }

            } catch (Throwable $rollbackError) {
                // Ignore rollback failure.
            }


            $error_message =
                $e->getMessage();
        }
    }
}


// =========================================================
// CREATE CHECKOUT TOKEN
// =========================================================

if (
    !isset(
        $_SESSION['checkout_token']
    )
) {

    $_SESSION['checkout_token'] =
        bin2hex(
            random_bytes(32)
        );
}

$checkout_token =
    $_SESSION['checkout_token'];


// =========================================================
// PREPARE IMAGE PATH
// =========================================================

function checkout_product_image(
    $image
): string {

    $image =
        trim(
            (string) $image
        );

    if ($image === '') {
        return 'image/logo.jpg';
    }

    return
        'image/product/' .
        ltrim(
            $image,
            '/\\'
        );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Checkout | HochipoHub
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/checkout.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >

    <style>

        /* =====================================================
           HOCHIPO HUB CHECKOUT
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            background:
                #f4f7ff;

            color:
                #10265e;

            font-family:
                Inter,
                Poppins,
                Arial,
                sans-serif;
        }

        .checkout-page {
            max-width: 1250px;

            margin: auto;

            padding:
                45px 25px 80px;
        }

        /* =====================================================
           HEADER
        ===================================================== */

        .checkout-heading {
            margin-bottom: 30px;
        }

        .checkout-kicker {
            display: inline-block;

            padding:
                7px 12px;

            border-radius:
                999px;

            background:
                #e8f2ff;

            color:
                #0868ff;

            font-size:
                11px;

            font-weight:
                900;

            text-transform:
                uppercase;

            letter-spacing:
                .7px;
        }

        .checkout-heading h1 {
            margin:
                13px 0 7px;

            font-size:
                clamp(32px, 5vw, 48px);

            letter-spacing:
                -2px;

            color:
                #10265e;
        }

        .checkout-heading p {
            margin: 0;

            color:
                #7d8ba5;

            font-size:
                14px;
        }

        /* =====================================================
           LAYOUT
        ===================================================== */

        .checkout-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1.5fr)
                minmax(330px, .8fr);

            gap: 25px;

            align-items: start;
        }

        .checkout-left,
        .checkout-right {
            display: flex;

            flex-direction: column;

            gap: 20px;
        }

        /* =====================================================
           CARD
        ===================================================== */

        .checkout-card {
            padding:
                25px;

            border:
                1px solid
                rgba(18,70,160,.08);

            border-radius:
                24px;

            background:
                #ffffff;

            box-shadow:
                0 15px 45px
                rgba(28,65,130,.07);
        }

        .checkout-card-title {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 22px;
        }

        .checkout-number {
            width: 36px;
            height: 36px;

            display: flex;

            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius:
                12px;

            background:
                linear-gradient(
                    135deg,
                    #0759dc,
                    #00a5ff
                );

            color:
                white;

            font-weight:
                900;
        }

        .checkout-card-title h2 {
            margin: 0;

            font-size:
                19px;

            color:
                #10265e;
        }

        .checkout-card-title p {
            margin:
                3px 0 0;

            color:
                #8995aa;

            font-size:
                12px;
        }

        /* =====================================================
           CUSTOMER INFO
        ===================================================== */

        .customer-info {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 14px;
        }

        .info-box {
            padding:
                15px;

            border-radius:
                15px;

            background:
                #f5f8ff;
        }

        .info-label {
            margin-bottom:
                5px;

            color:
                #8995aa;

            font-size:
                10px;

            font-weight:
                800;

            text-transform:
                uppercase;
        }

        .info-value {
            color:
                #18346e;

            font-size:
                14px;

            font-weight:
                700;

            word-break:
                break-word;
        }

        /* =====================================================
           DELIVERY
        ===================================================== */

        .delivery-options {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 13px;

            margin-bottom:
                18px;
        }

        .delivery-option {
            position: relative;
        }

        .delivery-option input {
            position: absolute;

            opacity: 0;
        }

        .delivery-label {
            display: block;

            padding:
                17px;

            border:
                2px solid
                #e6ebf5;

            border-radius:
                17px;

            cursor: pointer;

            transition:
                .2s ease;
        }

        .delivery-label:hover {
            border-color:
                #9bbcff;
        }

        .delivery-option input:checked
        + .delivery-label {
            border-color:
                #0868ff;

            background:
                #eef5ff;

            box-shadow:
                0 8px 20px
                rgba(0,95,255,.08);
        }

        .delivery-label strong {
            display: block;

            margin-bottom:
                4px;

            color:
                #18346e;

            font-size:
                14px;
        }

        .delivery-label span {
            color:
                #8995aa;

            font-size:
                11px;
        }

        .address-field {
            display: none;
        }

        .address-field.show {
            display: block;
        }

        .address-field label {
            display: block;

            margin-bottom:
                8px;

            color:
                #354a76;

            font-size:
                12px;

            font-weight:
                800;
        }

        .address-field textarea {
            width: 100%;

            min-height:
                110px;

            padding:
                14px;

            resize:
                vertical;

            border:
                1px solid
                #dce3f0;

            border-radius:
                14px;

            outline:
                none;

            color:
                #18346e;

            font-family:
                inherit;

            font-size:
                13px;
        }

        .address-field textarea:focus {
            border-color:
                #0868ff;

            box-shadow:
                0 0 0 4px
                rgba(8,104,255,.08);
        }

        /* =====================================================
           CART ITEMS
        ===================================================== */

        .checkout-items {
            display:
                flex;

            flex-direction:
                column;

            gap:
                14px;
        }

        .checkout-item {
            display:
                flex;

            align-items:
                center;

            gap:
                14px;

            padding:
                13px;

            border-radius:
                17px;

            background:
                #f7f9fd;
        }

        .checkout-item-image {
            width:
                72px;

            height:
                72px;

            flex-shrink:
                0;

            object-fit:
                cover;

            border-radius:
                14px;

            background:
                #eaf2ff;
        }

        .checkout-item-info {
            min-width:
                0;

            flex:
                1;
        }

        .checkout-item-info h3 {
            margin:
                0 0 4px;

            overflow:
                hidden;

            color:
                #18346e;

            font-size:
                14px;

            white-space:
                nowrap;

            text-overflow:
                ellipsis;
        }

        .checkout-item-vendor {
            margin-bottom:
                7px;

            color:
                #8995aa;

            font-size:
                11px;
        }

        .checkout-item-quantity {
            color:
                #647493;

            font-size:
                11px;
        }

        .checkout-item-price {
            text-align:
                right;

            color:
                #0759dc;

            font-size:
                15px;

            font-weight:
                900;

            white-space:
                nowrap;
        }

        /* =====================================================
           SUMMARY
        ===================================================== */

        .summary-card {
            position:
                sticky;

            top:
                20px;
        }

        .summary-title {
            margin:
                0 0 22px;

            color:
                #10265e;

            font-size:
                20px;
        }

        .summary-row {
            display:
                flex;

            justify-content:
                space-between;

            gap:
                20px;

            margin-bottom:
                14px;

            color:
                #75839e;

            font-size:
                13px;
        }

        .summary-row strong {
            color:
                #18346e;
        }

        .summary-divider {
            height:
                1px;

            margin:
                18px 0;

            background:
                #e9edf5;
        }

        .summary-total {
            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;
        }

        .summary-total span {
            color:
                #536482;

            font-size:
                13px;

            font-weight:
                700;
        }

        .summary-total strong {
            color:
                #0759dc;

            font-size:
                25px;

            font-weight:
                900;
        }

        .place-order-btn {
            width:
                100%;

            margin-top:
                23px;

            min-height:
                55px;

            border:
                0;

            border-radius:
                16px;

            background:
                linear-gradient(
                    135deg,
                    #0759dc,
                    #008cff
                );

            color:
                white;

            cursor:
                pointer;

            font-family:
                inherit;

            font-size:
                14px;

            font-weight:
                900;

            box-shadow:
                0 12px 30px
                rgba(0,90,230,.22);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .place-order-btn:hover {
            transform:
                translateY(-3px);

            box-shadow:
                0 17px 35px
                rgba(0,90,230,.28);
        }

        .back-cart {
            display:
                block;

            margin-top:
                14px;

            text-align:
                center;

            color:
                #0868ff;

            font-size:
                12px;

            font-weight:
                800;
        }

        /* =====================================================
           ERROR
        ===================================================== */

        .checkout-error {
            margin-bottom:
                22px;

            padding:
                15px 17px;

            border:
                1px solid
                #ffc9c9;

            border-radius:
                15px;

            background:
                #fff3f3;

            color:
                #b42323;

            font-size:
                13px;

            font-weight:
                700;
        }

        /* =====================================================
           SECURE NOTE
        ===================================================== */

        .secure-note {
            margin-top:
                17px;

            padding:
                13px;

            border-radius:
                13px;

            background:
                #f1f8ff;

            color:
                #6f7e98;

            font-size:
                11px;

            line-height:
                1.5;

            text-align:
                center;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .checkout-layout {
                grid-template-columns:
                    1fr;
            }

            .summary-card {
                position:
                    static;
            }

        }

        @media (max-width: 600px) {

            .checkout-page {
                padding:
                    30px 17px 60px;
            }

            .customer-info,
            .delivery-options {
                grid-template-columns:
                    1fr;
            }

            .checkout-card {
                padding:
                    19px;
            }

            .checkout-item-image {
                width:
                    60px;

                height:
                    60px;
            }

        }

    </style>

</head>


<body>

<?php
require_once __DIR__ . '/includes/navbar.php';
?>


<main class="checkout-page">


    <!-- =====================================================
         HEADING
    ====================================================== -->

    <div class="checkout-heading">

        <span class="checkout-kicker">
            Checkout
        </span>

        <h1>
            Almost yours.
        </h1>

        <p>
            Confirm your details before we send your order
            to the payment step.
        </p>

    </div>


    <?php if (
        $error_message !== ''
    ): ?>

        <div class="checkout-error">
            ⚠️
            <?= checkout_e(
                $error_message
            ); ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="checkout.php"
        id="checkoutForm"
    >

        <input
            type="hidden"
            name="checkout_token"
            value="<?= checkout_e(
                $checkout_token
            ); ?>"
        >


        <div class="checkout-layout">


            <!-- =================================================
                 LEFT
            ================================================== -->

            <div class="checkout-left">


                <!-- =============================================
                     CUSTOMER
                ============================================== -->

                <section class="checkout-card">

                    <div class="checkout-card-title">

                        <div class="checkout-number">
                            01
                        </div>

                        <div>

                            <h2>
                                Customer details
                            </h2>

                            <p>
                                Your account information
                            </p>

                        </div>

                    </div>


                    <div class="customer-info">

                        <div class="info-box">

                            <div class="info-label">
                                Name
                            </div>

                            <div class="info-value">
                                <?= checkout_e(
                                    $customer['name']
                                ); ?>
                            </div>

                        </div>


                        <div class="info-box">

                            <div class="info-label">
                                Phone
                            </div>

                            <div class="info-value">
                                <?= checkout_e(
                                    $customer['phone']
                                    ?: 'Not provided'
                                ); ?>
                            </div>

                        </div>


                        <div class="info-box">

                            <div class="info-label">
                                Email
                            </div>

                            <div class="info-value">
                                <?= checkout_e(
                                    $customer['email']
                                ); ?>
                            </div>

                        </div>

                    </div>

                </section>


                <!-- =============================================
                     DELIVERY
                ============================================== -->

                <section class="checkout-card">

                    <div class="checkout-card-title">

                        <div class="checkout-number">
                            02
                        </div>

                        <div>

                            <h2>
                                Delivery method
                            </h2>

                            <p>
                                Choose how you want to receive
                                your order.
                            </p>

                        </div>

                    </div>


                    <div class="delivery-options">


                        <!-- PICKUP -->

                        <div class="delivery-option">

                            <input
                                type="radio"
                                name="delivery_method"
                                id="pickup"
                                value="Pickup"
                                <?= $delivery_method === 'Pickup'
                                    ? 'checked'
                                    : ''; ?>
                            >

                            <label
                                for="pickup"
                                class="delivery-label"
                            >

                                <strong>
                                    📍 Pickup
                                </strong>

                                <span>
                                    Collect your order
                                    from the vendor.
                                </span>

                            </label>

                        </div>


                        <!-- POSTAGE -->

                        <div class="delivery-option">

                            <input
                                type="radio"
                                name="delivery_method"
                                id="postage"
                                value="Postage"
                                <?= $delivery_method === 'Postage'
                                    ? 'checked'
                                    : ''; ?>
                            >

                            <label
                                for="postage"
                                class="delivery-label"
                            >

                                <strong>
                                    📦 Postage
                                </strong>

                                <span>
                                    Have your order
                                    delivered.
                                </span>

                            </label>

                        </div>

                    </div>


                    <div
                        class="address-field
                        <?= $delivery_method === 'Postage'
                            ? 'show'
                            : ''; ?>"
                        id="addressField"
                    >

                        <label
                            for="delivery_address"
                        >
                            Delivery address
                        </label>

                        <textarea
                            name="delivery_address"
                            id="delivery_address"
                            placeholder="Enter your complete delivery address..."
                        ><?= checkout_e(
                            $delivery_address
                        ); ?></textarea>

                    </div>

                </section>


                <!-- =============================================
                     ORDER ITEMS
                ============================================== -->

                <section class="checkout-card">

                    <div class="checkout-card-title">

                        <div class="checkout-number">
                            03
                        </div>

                        <div>

                            <h2>
                                Your order
                            </h2>

                            <p>
                                <?= count(
                                    $cart_items
                                ); ?>
                                product(s) in this order.
                            </p>

                        </div>

                    </div>


                    <div class="checkout-items">

                        <?php foreach (
                            $cart_items
                            as $item
                        ): ?>

                            <?php

                                $item_quantity =
                                    (int)
                                    $item['quantity'];

                                $item_price =
                                    (float)
                                    $item['price'];

                                $item_total =
                                    $item_quantity *
                                    $item_price;

                                $item_image =
                                    checkout_product_image(
                                        $item['image']
                                    );

                            ?>


                            <div
                                class="checkout-item"
                            >

                                <img
                                    src="<?= checkout_e(
                                        $item_image
                                    ); ?>"
                                    alt="<?= checkout_e(
                                        $item['product_name']
                                    ); ?>"
                                    class="checkout-item-image"
                                    onerror="
                                        this.src='image/logo.jpg';
                                    "
                                >


                                <div
                                    class="checkout-item-info"
                                >

                                    <h3>
                                        <?= checkout_e(
                                            $item[
                                                'product_name'
                                            ]
                                        ); ?>
                                    </h3>

                                    <div
                                        class="checkout-item-vendor"
                                    >
                                        <?= checkout_e(
                                            $item[
                                                'business_name'
                                            ]
                                        ); ?>
                                    </div>

                                    <div
                                        class="checkout-item-quantity"
                                    >
                                        Qty:
                                        <?= $item_quantity; ?>
                                        × RM
                                        <?= number_format(
                                            $item_price,
                                            2
                                        ); ?>
                                    </div>

                                </div>


                                <div
                                    class="checkout-item-price"
                                >

                                    RM
                                    <?= number_format(
                                        $item_total,
                                        2
                                    ); ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>


            </div>


            <!-- =================================================
                 RIGHT
            ================================================== -->

            <div class="checkout-right">

                <section
                    class="
                        checkout-card
                        summary-card
                    "
                >

                    <h2 class="summary-title">
                        Order summary
                    </h2>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <strong>
                            <?= count(
                                $cart_items
                            ); ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>
                            RM
                            <?= number_format(
                                $subtotal,
                                2
                            ); ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Delivery
                        </span>

                        <strong>
                            Calculated by vendor
                        </strong>

                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-total">

                        <span>
                            Order total
                        </span>

                        <strong>
                            RM
                            <?= number_format(
                                $subtotal,
                                2
                            ); ?>
                        </strong>

                    </div>


                    <button
                        type="submit"
                        name="place_order"
                        value="1"
                        class="place-order-btn"
                    >
                        Continue to Payment →
                    </button>


                    <a
                        href="cart.php"
                        class="back-cart"
                    >
                        ← Back to cart
                    </a>


                    <div class="secure-note">

                        🔒 Your order information is processed
                        securely. Payment details are handled
                        on the next step.

                    </div>

                </section>

            </div>


        </div>

    </form>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const pickup =
            document.getElementById(
                'pickup'
            );

        const postage =
            document.getElementById(
                'postage'
            );

        const addressField =
            document.getElementById(
                'addressField'
            );

        const address =
            document.getElementById(
                'delivery_address'
            );


        function updateDeliveryUI() {

            if (
                postage &&
                postage.checked
            ) {

                addressField.classList.add(
                    'show'
                );

                address.required = true;

            } else {

                addressField.classList.remove(
                    'show'
                );

                address.required = false;
            }
        }


        if (pickup) {

            pickup.addEventListener(
                'change',
                updateDeliveryUI
            );
        }


        if (postage) {

            postage.addEventListener(
                'change',
                updateDeliveryUI
            );
        }


        updateDeliveryUI();

    }
);

</script>


</body>
</html>