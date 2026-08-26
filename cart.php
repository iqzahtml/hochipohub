<?php

/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - CART
|--------------------------------------------------------------------------
| File: cart.php
|
| Purpose:
| - Display customer's shopping cart
| - Support multiple vendors in one cart
| - Calculate subtotal
| - Allow quantity update
| - Remove product from cart
| - Proceed to checkout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];

$db = getDB();


/*
|--------------------------------------------------------------------------
| HANDLE REMOVE ITEM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart'])) {

    $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);

    if ($cart_id) {

        $stmt = $db->prepare("
            DELETE FROM cart
            WHERE cart_id = ?
            AND customer_id = ?
        ");

        $stmt->execute([
            $cart_id,
            $user_id
        ]);
    }

    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| HANDLE UPDATE QUANTITY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {

    $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($cart_id && $quantity !== false && $quantity > 0) {

        /*
        |--------------------------------------------------------------------------
        | CHECK AVAILABLE STOCK
        |--------------------------------------------------------------------------
        */

        $stockStmt = $db->prepare("
            SELECT p.stock_quantity
            FROM cart c
            INNER JOIN products p
                ON c.product_id = p.product_id
            WHERE c.cart_id = ?
            AND c.customer_id = ?
            LIMIT 1
        ");

        $stockStmt->execute([
            $cart_id,
            $user_id
        ]);

        $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

        if ($stock) {

            $availableStock = (int) $stock['stock_quantity'];

            if ($availableStock > 0) {

                if ($quantity > $availableStock) {
                    $quantity = $availableStock;
                }

                $stmt = $db->prepare("
                    UPDATE cart
                    SET quantity = ?
                    WHERE cart_id = ?
                    AND customer_id = ?
                ");

                $stmt->execute([
                    $quantity,
                    $cart_id,
                    $user_id
                ]);
            }
        }
    }

    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET CART ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
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
        v.business_logo,

        cat.category_id,
        cat.category_name

    FROM cart c

    INNER JOIN products p
        ON c.product_id = p.product_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories cat
        ON p.category_id = cat.category_id

    WHERE c.customer_id = ?

    ORDER BY v.business_name ASC, p.product_name ASC
");

$stmt->execute([$user_id]);

$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CALCULATE TOTALS
|--------------------------------------------------------------------------
*/

$subtotal = 0;
$totalItems = 0;

foreach ($cartItems as $item) {

    $quantity = (int) $item['quantity'];
    $price = (float) $item['price'];

    $subtotal += ($price * $quantity);
    $totalItems += $quantity;
}


/*
|--------------------------------------------------------------------------
| DELIVERY FEE
|--------------------------------------------------------------------------
| Delivery fee is handled during checkout.
|--------------------------------------------------------------------------
*/

$deliveryFee = 0;

$total = $subtotal + $deliveryFee;


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle = "My Cart";

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
        <?php echo htmlspecialchars($pageTitle); ?> | HochipoHub
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/cart.css"
    >

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >

</head>

<body>


<?php
/*
|--------------------------------------------------------------------------
| NAVBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/customer_sidebar.php';
?>


<main class="cart-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <section class="cart-header">

        <div class="cart-header-content">

            <span class="cart-label">
                YOUR SHOPPING BAG
            </span>

            <h1>
                My Cart
            </h1>

            <p>
                Review your items before checking out.
            </p>

        </div>

    </section>


    <!-- =====================================================
         CART CONTENT
    ====================================================== -->

    <section class="cart-container">

        <?php if (empty($cartItems)): ?>

            <!-- =================================================
                 EMPTY CART
            ================================================== -->

            <div class="empty-cart">

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h2>
                    Your cart is empty
                </h2>

                <p>
                    Looks like you haven't added anything yet.
                </p>

                <a
                    href="catalog.php"
                    class="btn-primary"
                >
                    Explore Products
                </a>

            </div>

        <?php else: ?>


            <div class="cart-layout">


                <!-- =============================================
                     CART ITEMS
                ============================================== -->

                <div class="cart-items-section">


                    <div class="cart-items-header">

                        <div>
                            <h2>
                                Cart Items
                            </h2>

                            <span>
                                <?php echo $totalItems; ?>
                                item<?php echo $totalItems != 1 ? 's' : ''; ?>
                            </span>
                        </div>

                    </div>


                    <?php
                    /*
                    |--------------------------------------------------------------------------
                    | GROUP ITEMS BY VENDOR
                    |--------------------------------------------------------------------------
                    */

                    $vendors = [];

                    foreach ($cartItems as $item) {

                        $vendorId = $item['vendor_id'];

                        if (!isset($vendors[$vendorId])) {
                            $vendors[$vendorId] = [
                                'vendor_id' => $vendorId,
                                'business_name' => $item['business_name'],
                                'business_logo' => $item['business_logo'],
                                'items' => []
                            ];
                        }

                        $vendors[$vendorId]['items'][] = $item;
                    }
                    ?>


                    <?php foreach ($vendors as $vendor): ?>

                        <div class="cart-vendor-group">


                            <!-- Vendor Header -->

                            <div class="cart-vendor-header">

                                <div class="vendor-info">

                                    <?php if (!empty($vendor['business_logo'])): ?>

                                        <img
                                            src="<?php echo htmlspecialchars($vendor['business_logo']); ?>"
                                            alt="<?php echo htmlspecialchars($vendor['business_name']); ?>"
                                        >

                                    <?php else: ?>

                                        <div class="vendor-placeholder">
                                            🏪
                                        </div>

                                    <?php endif; ?>


                                    <div>

                                        <span>
                                            SELLER
                                        </span>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $vendor['business_name']
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>


                            <!-- Vendor Products -->

                            <?php foreach ($vendor['items'] as $item): ?>

                                <?php

                                $quantity = (int) $item['quantity'];
                                $price = (float) $item['price'];

                                $itemSubtotal = $price * $quantity;

                                ?>


                                <div class="cart-item">


                                    <!-- Product Image -->

                                    <div class="cart-product-image">

                                        <?php if (!empty($item['image'])): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($item['image']); ?>"
                                                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                            >

                                        <?php else: ?>

                                            <div class="product-placeholder">
                                                📦
                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <!-- Product Information -->

                                    <div class="cart-product-info">

                                        <span class="cart-category">

                                            <?php
                                            echo htmlspecialchars(
                                                $item['category_name']
                                            );
                                            ?>

                                        </span>

                                        <h3>

                                            <a
                                                href="product_details.php?id=<?php echo (int) $item['product_id']; ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $item['product_name']
                                                );
                                                ?>

                                            </a>

                                        </h3>

                                        <p class="cart-price">

                                            RM
                                            <?php
                                            echo number_format(
                                                $price,
                                                2
                                            );
                                            ?>

                                        </p>


                                        <?php if ($item['status'] !== 'Available'): ?>

                                            <span class="cart-warning">
                                                Product unavailable
                                            </span>

                                        <?php elseif ($item['stock_quantity'] <= 0): ?>

                                            <span class="cart-warning">
                                                Out of stock
                                            </span>

                                        <?php elseif ($quantity > $item['stock_quantity']): ?>

                                            <span class="cart-warning">
                                                Only
                                                <?php
                                                echo (int) $item['stock_quantity'];
                                                ?>
                                                available
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <!-- Quantity -->

                                    <div class="cart-quantity">

                                        <form
                                            method="POST"
                                            action="cart.php"
                                        >

                                            <input
                                                type="hidden"
                                                name="cart_id"
                                                value="<?php echo (int) $item['cart_id']; ?>"
                                            >

                                            <label>
                                                Qty
                                            </label>

                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?php echo $quantity; ?>"
                                                min="1"
                                                max="<?php echo max(1, (int) $item['stock_quantity']); ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="update_cart"
                                                class="btn-update-cart"
                                            >
                                                Update
                                            </button>

                                        </form>

                                    </div>


                                    <!-- Subtotal -->

                                    <div class="cart-item-total">

                                        <span>
                                            Subtotal
                                        </span>

                                        <strong>

                                            RM
                                            <?php
                                            echo number_format(
                                                $itemSubtotal,
                                                2
                                            );
                                            ?>

                                        </strong>

                                    </div>


                                    <!-- Remove -->

                                    <div class="cart-remove">

                                        <form
                                            method="POST"
                                            action="cart.php"
                                            onsubmit="return confirm('Remove this product from your cart?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="cart_id"
                                                value="<?php echo (int) $item['cart_id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="remove_cart"
                                                class="btn-remove-cart"
                                                title="Remove item"
                                            >
                                                ×
                                            </button>

                                        </form>

                                    </div>


                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endforeach; ?>


                    <!-- Continue Shopping -->

                    <div class="cart-continue">

                        <a
                            href="catalog.php"
                            class="btn-secondary"
                        >
                            ← Continue Shopping
                        </a>

                    </div>


                </div>


                <!-- =============================================
                     ORDER SUMMARY
                ============================================== -->

                <aside class="cart-summary">

                    <div class="summary-card">

                        <div class="summary-heading">

                            <span>
                                ORDER SUMMARY
                            </span>

                            <h2>
                                Your Total
                            </h2>

                        </div>


                        <div class="summary-row">

                            <span>
                                Items
                            </span>

                            <strong>
                                <?php echo $totalItems; ?>
                            </strong>

                        </div>


                        <div class="summary-row">

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


                        <div class="summary-row">

                            <span>
                                Delivery
                            </span>

                            <strong>
                                Calculated at checkout
                            </strong>

                        </div>


                        <div class="summary-divider"></div>


                        <div class="summary-total">

                            <span>
                                Estimated Total
                            </span>

                            <strong>

                                RM
                                <?php
                                echo number_format(
                                    $total,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <a
                            href="checkout.php"
                            class="btn-checkout"
                        >
                            Proceed to Checkout
                            →
                        </a>


                        <div class="secure-checkout">

                            🔒 Secure checkout

                        </div>

                    </div>


                    <!-- Multi Vendor Notice -->

                    <div class="multi-vendor-note">

                        <strong>
                            🏪 Multiple Sellers?
                        </strong>

                        <p>
                            Your order can contain products from
                            different vendors. Each vendor will
                            process their own items separately.
                        </p>

                    </div>

                </aside>


            </div>


        <?php endif; ?>

    </section>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>


<script src="js/cart.js"></script>
<script src="js/script.js"></script>

</body>
</html>
