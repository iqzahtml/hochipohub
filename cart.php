<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . site_url('index.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Remove item
|--------------------------------------------------------------------------
*/
if (isset($_GET['remove'])) {

    $cartId = (int) $_GET['remove'];

    $stmt = $conn->prepare("
        DELETE FROM cart
        WHERE cart_id = ?
        AND customer_id = ?
    ");

    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();
    $stmt->close();

    header('Location: cart.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Update quantity
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {

    if (!empty($_POST['quantity']) && is_array($_POST['quantity'])) {

        foreach ($_POST['quantity'] as $cartId => $quantity) {

            $cartId = (int) $cartId;
            $quantity = (int) $quantity;

            if ($quantity < 1) {
                $quantity = 1;
            }

            $stmt = $conn->prepare("
                UPDATE cart c
                INNER JOIN products p
                    ON c.product_id = p.product_id
                SET c.quantity = LEAST(?, p.stock_quantity)
                WHERE c.cart_id = ?
                AND c.customer_id = ?
                AND p.status = 'Available'
            ");

            $stmt->bind_param(
                "iii",
                $quantity,
                $cartId,
                $userId
            );

            $stmt->execute();
            $stmt->close();
        }
    }

    header('Location: cart.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get cart items
|--------------------------------------------------------------------------
*/
$cartItems = [];

$stmt = $conn->prepare("
    SELECT
        c.cart_id,
        c.quantity,

        p.product_id,
        p.product_name,
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

    INNER JOIN categories cat
        ON p.category_id = cat.category_id

    WHERE c.customer_id = ?

    ORDER BY c.created_at DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Calculate totals
|--------------------------------------------------------------------------
*/
$subtotal = 0;
$totalItems = 0;

foreach ($cartItems as $item) {

    $itemSubtotal =
        (float)$item['price'] *
        (int)$item['quantity'];

    $subtotal += $itemSubtotal;

    $totalItems += (int)$item['quantity'];
}

$deliveryFee = 0;
$grandTotal = $subtotal + $deliveryFee;

function cartImage($image)
{
    if (!empty($image)) {
        return site_url(
            'image/product/' . ltrim($image, '/')
        );
    }

    return DEFAULT_PRODUCT_IMAGE;
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
        Shopping Cart | <?php echo SITE_NAME; ?>
    </title>

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/cart.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >

</head>

<body>

<div class="cart-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="cart-header">

        <div>

            <span class="cart-eyebrow">
                YOUR SHOPPING BAG
            </span>

            <h1>
                My Cart
                <span>
                    (<?php echo $totalItems; ?>)
                </span>
            </h1>

            <p>
                Review your picks before checking out.
            </p>

        </div>

        <a
            href="<?php echo site_url('catalog.php'); ?>"
            class="continue-shopping"
        >
            ← Continue Shopping
        </a>

    </div>


    <?php if (empty($cartItems)): ?>

        <!-- =================================================
             EMPTY CART
        ================================================== -->

        <div class="empty-cart">

            <div class="empty-cart-icon">
                🛒
            </div>

            <h2>
                Your cart is empty.
            </h2>

            <p>
                Nothing here yet. Go find something worth
                spending your money on.
            </p>

            <a
                href="<?php echo site_url('catalog.php'); ?>"
                class="cart-primary-btn"
            >
                Explore Products →
            </a>

        </div>

    <?php else: ?>

        <!-- =================================================
             CART CONTENT
        ================================================== -->

        <form
            method="POST"
            action="cart.php"
        >

            <div class="cart-layout">

                <!-- =========================================
                     ITEMS
                ========================================== -->

                <div class="cart-items-section">

                    <div class="cart-section-heading">

                        <div>

                            <h2>
                                Your Items
                            </h2>

                            <p>
                                Products from local vendors
                            </p>

                        </div>

                        <span>
                            <?php echo count($cartItems); ?>
                            products
                        </span>

                    </div>


                    <?php foreach ($cartItems as $item): ?>

                        <?php

                        $itemTotal =
                            (float)$item['price'] *
                            (int)$item['quantity'];

                        $stock =
                            (int)$item['stock_quantity'];

                        ?>

                        <div class="cart-item">

                            <!-- IMAGE -->

                            <a
                                href="<?php
                                    echo site_url(
                                        'product_details.php?id=' .
                                        (int)$item['product_id']
                                    );
                                ?>"
                                class="cart-product-image"
                            >

                                <img
                                    src="<?php
                                        echo htmlspecialchars(
                                            cartImage(
                                                $item['image']
                                            )
                                        );
                                    ?>"
                                    alt="<?php
                                        echo htmlspecialchars(
                                            $item['product_name']
                                        );
                                    ?>"
                                    onerror="
                                        this.src='<?php
                                            echo DEFAULT_PRODUCT_IMAGE;
                                        ?>';
                                    "
                                >

                            </a>


                            <!-- INFO -->

                            <div class="cart-product-info">

                                <span class="cart-category">

                                    <?php
                                    echo htmlspecialchars(
                                        $item['category_name']
                                    );
                                    ?>

                                </span>

                                <a
                                    href="<?php
                                        echo site_url(
                                            'product_details.php?id=' .
                                            (int)$item['product_id']
                                        );
                                    ?>"
                                    class="cart-product-name"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $item['product_name']
                                    );
                                    ?>

                                </a>

                                <p class="cart-vendor">

                                    Sold by
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $item['business_name']
                                        );
                                        ?>
                                    </strong>

                                </p>

                                <div class="cart-unit-price">

                                    RM
                                    <?php
                                    echo number_format(
                                        (float)$item['price'],
                                        2
                                    );
                                    ?>

                                    each

                                </div>

                            </div>


                            <!-- QUANTITY -->

                            <div class="cart-quantity">

                                <label>
                                    Quantity
                                </label>

                                <div class="quantity-control">

                                    <button
                                        type="button"
                                        class="quantity-minus"
                                    >
                                        −
                                    </button>

                                    <input
                                        type="number"
                                        name="quantity[
                                            <?php
                                            echo (int)$item['cart_id'];
                                            ?>
                                        ]"
                                        value="<?php
                                            echo (int)$item['quantity'];
                                        ?>"
                                        min="1"
                                        max="<?php
                                            echo max(1, $stock);
                                        ?>"
                                    >

                                    <button
                                        type="button"
                                        class="quantity-plus"
                                    >
                                        +
                                    </button>

                                </div>

                                <small>

                                    <?php if ($stock > 0): ?>

                                        <?php echo $stock; ?>
                                        left in stock

                                    <?php else: ?>

                                        Out of stock

                                    <?php endif; ?>

                                </small>

                            </div>


                            <!-- TOTAL -->

                            <div class="cart-item-total">

                                <span>
                                    Total
                                </span>

                                <strong>

                                    RM
                                    <?php
                                    echo number_format(
                                        $itemTotal,
                                        2
                                    );
                                    ?>

                                </strong>

                                <a
                                    href="cart.php?remove=<?php
                                        echo (int)$item['cart_id'];
                                    ?>"
                                    class="remove-item"
                                    onclick="
                                        return confirm(
                                            'Remove this item from your cart?'
                                        );
                                    "
                                >
                                    Remove
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <div class="cart-update-row">

                        <button
                            type="submit"
                            name="update_cart"
                            class="update-cart-btn"
                        >
                            ↻ Update Cart
                        </button>

                    </div>

                </div>


                <!-- =========================================
                     SUMMARY
                ========================================== -->

                <aside class="cart-summary">

                    <div class="summary-glow"></div>

                    <div class="summary-content">

                        <span class="summary-eyebrow">
                            ORDER SUMMARY
                        </span>

                        <h2>
                            Almost there.
                        </h2>


                        <div class="summary-line">

                            <span>
                                Items
                            </span>

                            <strong>
                                <?php echo $totalItems; ?>
                            </strong>

                        </div>


                        <div class="summary-line">

                            <span>
                                Subtotal
                            </span>

                            <strong>

                                RM
                                <?php
                                echo number_format(
                                    $subtotal,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="summary-line">

                            <span>
                                Delivery
                            </span>

                            <strong>

                                <?php if ($deliveryFee > 0): ?>

                                    RM
                                    <?php
                                    echo number_format(
                                        $deliveryFee,
                                        2
                                    );
                                    ?>

                                <?php else: ?>

                                    FREE

                                <?php endif; ?>

                            </strong>

                        </div>


                        <div class="summary-divider"></div>


                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <strong>

                                RM
                                <?php
                                echo number_format(
                                    $grandTotal,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <a
                            href="<?php
                                echo site_url('checkout.php');
                            ?>"
                            class="checkout-btn"
                        >
                            Proceed to Checkout
                            <span>→</span>
                        </a>


                        <div class="secure-note">

                            🔒 Secure checkout

                        </div>

                    </div>

                </aside>

            </div>

        </form>

    <?php endif; ?>

</div>


<script>

document.querySelectorAll('.quantity-control')
.forEach(function(control) {

    const minus =
        control.querySelector('.quantity-minus');

    const plus =
        control.querySelector('.quantity-plus');

    const input =
        control.querySelector('input');

    minus.addEventListener('click', function() {

        let value =
            parseInt(input.value) || 1;

        if (value > 1) {
            input.value = value - 1;
        }

    });


    plus.addEventListener('click', function() {

        let value =
            parseInt(input.value) || 1;

        let max =
            parseInt(input.max) || 999;

        if (value < max) {
            input.value = value + 1;
        }

    });

});

</script>

</body>
</html>