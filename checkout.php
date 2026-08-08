<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Customer Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header(
        'Location: ' .
        site_url('index.php?login=required')
    );
    exit;
}

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get Customer
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

$stmt->close();

if (!$user) {
    session_destroy();

    header(
        'Location: ' .
        site_url('index.php')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Cart
|--------------------------------------------------------------------------
*/

$cartItems = [];

$stmt = $conn->prepare("
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
        v.delivery_method AS vendor_delivery_method,

        cat.category_name

    FROM cart c

    INNER JOIN products p
        ON c.product_id = p.product_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN categories cat
        ON p.category_id = cat.category_id

    WHERE c.customer_id = ?

    ORDER BY v.business_name ASC,
             p.product_name ASC
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Validate Cart
|--------------------------------------------------------------------------
*/

$invalidItems = [];

foreach ($cartItems as $item) {

    if (
        $item['status'] !== 'Available' ||
        $item['stock_quantity'] <= 0 ||
        $item['quantity'] > $item['stock_quantity']
    ) {
        $invalidItems[] = $item;
    }
}


/*
|--------------------------------------------------------------------------
| Redirect if Cart Empty
|--------------------------------------------------------------------------
*/

if (empty($cartItems)) {

    header(
        'Location: ' .
        site_url('cart.php?empty=1')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Group Cart by Vendor
|--------------------------------------------------------------------------
*/

$vendorGroups = [];

foreach ($cartItems as $item) {

    $vendorId = (int) $item['vendor_id'];

    if (!isset($vendorGroups[$vendorId])) {

        $vendorGroups[$vendorId] = [
            'vendor_id' => $vendorId,
            'business_name' =>
                $item['business_name'],

            'delivery_method' =>
                $item['vendor_delivery_method'],

            'items' => [],

            'subtotal' => 0
        ];
    }

    $itemSubtotal =
        (float)$item['price'] *
        (int)$item['quantity'];

    $item['item_subtotal'] = $itemSubtotal;

    $vendorGroups[$vendorId]['items'][] = $item;

    $vendorGroups[$vendorId]['subtotal'] +=
        $itemSubtotal;
}


/*
|--------------------------------------------------------------------------
| Calculate Total
|--------------------------------------------------------------------------
*/

$subtotal = 0;

foreach ($cartItems as $item) {

    $subtotal +=
        (float)$item['price'] *
        (int)$item['quantity'];
}


/*
|--------------------------------------------------------------------------
| Delivery Fee
|--------------------------------------------------------------------------
|
| Simple marketplace rule:
|
| Pickup  = RM0
| Postage  = RM5 per vendor
|
| This matches the multi-vendor database structure.
|
*/

$defaultDelivery = 'Postage';

$deliveryFee = count($vendorGroups) * 5;

$totalAmount =
    $subtotal +
    $deliveryFee;


/*
|--------------------------------------------------------------------------
| Previous Form Values
|--------------------------------------------------------------------------
*/

$deliveryMethod =
    isset($_SESSION['checkout_delivery_method'])
        ? $_SESSION['checkout_delivery_method']
        : $defaultDelivery;

$deliveryAddress =
    isset($_SESSION['checkout_delivery_address'])
        ? $_SESSION['checkout_delivery_address']
        : '';

$error = '';

if (
    isset($_SESSION['checkout_error'])
) {
    $error = $_SESSION['checkout_error'];
    unset($_SESSION['checkout_error']);
}


/*
|--------------------------------------------------------------------------
| Delivery Method Change
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        isset($_POST['action'])
            ? $_POST['action']
            : '';

    if ($action === 'continue_payment') {

        $deliveryMethod =
            isset($_POST['delivery_method'])
                ? trim($_POST['delivery_method'])
                : '';

        $deliveryAddress =
            isset($_POST['delivery_address'])
                ? trim($_POST['delivery_address'])
                : '';


        /*
        |--------------------------------------------------------------------------
        | Validate Delivery Method
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $deliveryMethod,
                ['Pickup', 'Postage'],
                true
            )
        ) {

            $error =
                'Please select a valid delivery method.';
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Address
        |--------------------------------------------------------------------------
        */

        if (
            $error === '' &&
            $deliveryMethod === 'Postage' &&
            $deliveryAddress === ''
        ) {

            $error =
                'Please enter your delivery address.';
        }


        /*
        |--------------------------------------------------------------------------
        | Check Stock Again
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            foreach ($cartItems as $item) {

                if (
                    $item['status'] !== 'Available'
                ) {

                    $error =
                        $item['product_name'] .
                        ' is no longer available.';

                    break;
                }

                if (
                    (int)$item['quantity'] >
                    (int)$item['stock_quantity']
                ) {

                    $error =
                        'Not enough stock for ' .
                        $item['product_name'] .
                        '.';

                    break;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Save Checkout Information
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            $_SESSION[
                'checkout_delivery_method'
            ] = $deliveryMethod;

            $_SESSION[
                'checkout_delivery_address'
            ] = $deliveryAddress;


            /*
            |--------------------------------------------------------------------------
            | Go to Payment
            |--------------------------------------------------------------------------
            */

            header(
                'Location: ' .
                site_url('payment.php')
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Dynamic Delivery Preview
|--------------------------------------------------------------------------
*/

$postageFee =
    count($vendorGroups) * 5;

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
        Checkout | <?php echo SITE_NAME; ?>
    </title>

    <link
        rel="stylesheet"
        href="<?php
            echo site_url('css/style.css');
        ?>"
    >

    <link
        rel="stylesheet"
        href="<?php
            echo site_url('css/checkout.css');
        ?>"
    >

    <link
        rel="stylesheet"
        href="<?php
            echo site_url('css/responsive.css');
        ?>"
    >

</head>

<body>

<div class="checkout-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="checkout-topbar">

        <a
            href="<?php
                echo site_url('cart.php');
            ?>"
            class="checkout-back"
        >
            ← Back to Cart
        </a>

        <div class="checkout-brand">

            <span>
                HOCHIPO
            </span>

            <strong>
                HUB
            </strong>

        </div>

        <div class="secure-label">
            🔒 Secure Checkout
        </div>

    </header>


    <!-- =====================================================
         PROGRESS
    ====================================================== -->

    <div class="checkout-progress">

        <div class="progress-step active">

            <span>
                01
            </span>

            <p>
                Delivery
            </p>

        </div>

        <div class="progress-line"></div>

        <div class="progress-step">

            <span>
                02
            </span>

            <p>
                Payment
            </p>

        </div>

        <div class="progress-line"></div>

        <div class="progress-step">

            <span>
                03
            </span>

            <p>
                Confirmation
            </p>

        </div>

    </div>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="checkout-container">


        <?php if ($error !== ''): ?>

            <div class="checkout-alert">

                <span>
                    !
                </span>

                <div>

                    <strong>
                        Checkout couldn't continue
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars($error);
                        ?>
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <div class="checkout-layout">


            <!-- =================================================
                 LEFT
            ================================================== -->

            <section class="checkout-main">


                <!-- CUSTOMER -->

                <div class="checkout-card">

                    <div class="checkout-card-title">

                        <div class="checkout-title-icon">
                            01
                        </div>

                        <div>

                            <span>
                                CUSTOMER
                            </span>

                            <h2>
                                Your Details
                            </h2>

                        </div>

                    </div>


                    <div class="customer-grid">

                        <div class="customer-field">

                            <label>
                                Full Name
                            </label>

                            <div class="customer-value">
                                <?php
                                echo htmlspecialchars(
                                    $user['name']
                                );
                                ?>
                            </div>

                        </div>


                        <div class="customer-field">

                            <label>
                                Email
                            </label>

                            <div class="customer-value">
                                <?php
                                echo htmlspecialchars(
                                    $user['email']
                                );
                                ?>
                            </div>

                        </div>


                        <div class="customer-field">

                            <label>
                                Phone
                            </label>

                            <div class="customer-value">

                                <?php
                                echo !empty($user['phone'])
                                    ? htmlspecialchars(
                                        $user['phone']
                                    )
                                    : 'Not provided';
                                ?>

                            </div>

                        </div>


                        <a
                            href="<?php
                                echo site_url(
                                    'profile.php'
                                );
                            ?>"
                            class="edit-profile-link"
                        >
                            Edit Profile →
                        </a>

                    </div>

                </div>


                <!-- DELIVERY -->

                <form
                    method="POST"
                    action="checkout.php"
                    id="checkoutForm"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="continue_payment"
                    >


                    <div class="checkout-card">

                        <div class="checkout-card-title">

                            <div class="checkout-title-icon">
                                02
                            </div>

                            <div>

                                <span>
                                    DELIVERY
                                </span>

                                <h2>
                                    How should we get it to you?
                                </h2>

                            </div>

                        </div>


                        <div class="delivery-options">


                            <!-- PICKUP -->

                            <label
                                class="
                                    delivery-option
                                    <?php
                                    echo $deliveryMethod ===
                                        'Pickup'
                                        ? 'selected'
                                        : '';
                                    ?>
                                "
                            >

                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="Pickup"
                                    <?php
                                    echo $deliveryMethod ===
                                        'Pickup'
                                        ? 'checked'
                                        : '';
                                    ?>
                                >

                                <div class="delivery-radio"></div>

                                <div class="delivery-icon">
                                    🛍️
                                </div>

                                <div class="delivery-info">

                                    <strong>
                                        Self Pickup
                                    </strong>

                                    <p>
                                        Collect directly
                                        from the vendor.
                                    </p>

                                </div>

                                <b>
                                    FREE
                                </b>

                            </label>


                            <!-- POSTAGE -->

                            <label
                                class="
                                    delivery-option
                                    <?php
                                    echo $deliveryMethod ===
                                        'Postage'
                                        ? 'selected'
                                        : '';
                                    ?>
                                "
                            >

                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="Postage"
                                    <?php
                                    echo $deliveryMethod ===
                                        'Postage'
                                        ? 'checked'
                                        : '';
                                    ?>
                                >

                                <div class="delivery-radio"></div>

                                <div class="delivery-icon">
                                    📦
                                </div>

                                <div class="delivery-info">

                                    <strong>
                                        Postage
                                    </strong>

                                    <p>
                                        Delivered to your
                                        address.
                                    </p>

                                </div>

                                <b>
                                    RM
                                    <?php
                                    echo number_format(
                                        $postageFee,
                                        2
                                    );
                                    ?>
                                </b>

                            </label>

                        </div>


                        <!-- ADDRESS -->

                        <div
                            class="address-section"
                            id="addressSection"
                        >

                            <label
                                for="delivery_address"
                            >
                                Delivery Address
                            </label>

                            <textarea
                                name="delivery_address"
                                id="delivery_address"
                                rows="4"
                                placeholder="House / room number, street, area, postcode, state..."
                            ><?php
                            echo htmlspecialchars(
                                $deliveryAddress
                            );
                            ?></textarea>

                            <small>
                                Make sure your address is
                                complete and accurate.
                            </small>

                        </div>

                    </div>


                    <!-- VENDOR BREAKDOWN -->

                    <div class="checkout-card">

                        <div class="checkout-card-title">

                            <div class="checkout-title-icon">
                                03
                            </div>

                            <div>

                                <span>
                                    MULTI-VENDOR ORDER
                                </span>

                                <h2>
                                    Your Vendors
                                </h2>

                            </div>

                        </div>


                        <div class="vendor-order-list">

                            <?php foreach (
                                $vendorGroups
                                as $vendor
                            ): ?>

                                <div class="vendor-order">

                                    <div
                                        class="vendor-order-header"
                                    >

                                        <div>

                                            <span>
                                                VENDOR
                                            </span>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $vendor[
                                                        'business_name'
                                                    ]
                                                );
                                                ?>
                                            </strong>

                                        </div>

                                        <b>
                                            RM
                                            <?php
                                            echo number_format(
                                                $vendor['subtotal'],
                                                2
                                            );
                                            ?>
                                        </b>

                                    </div>


                                    <?php foreach (
                                        $vendor['items']
                                        as $item
                                    ): ?>

                                        <div
                                            class="checkout-item"
                                        >

                                            <div
                                                class="
                                                    checkout-item-image
                                                "
                                            >

                                                <?php if (
                                                    !empty(
                                                        $item['image']
                                                    )
                                                ): ?>

                                                    <img
                                                        src="<?php
                                                        echo site_url(
                                                            'image/product/' .
                                                            ltrim(
                                                                $item[
                                                                    'image'
                                                                ],
                                                                '/'
                                                            )
                                                        );
                                                        ?>"
                                                        alt="<?php
                                                        echo htmlspecialchars(
                                                            $item[
                                                                'product_name'
                                                            ]
                                                        );
                                                        ?>"
                                                    >

                                                <?php else: ?>

                                                    <div>
                                                        ✦
                                                    </div>

                                                <?php endif; ?>

                                            </div>


                                            <div
                                                class="
                                                    checkout-item-info
                                                "
                                            >

                                                <strong>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $item[
                                                            'product_name'
                                                        ]
                                                    );
                                                    ?>
                                                </strong>

                                                <span>
                                                    Qty:
                                                    <?php
                                                    echo (int)
                                                        $item[
                                                            'quantity'
                                                        ];
                                                    ?>
                                                </span>

                                            </div>


                                            <strong
                                                class="
                                                    checkout-item-price
                                                "
                                            >

                                                RM
                                                <?php
                                                echo number_format(
                                                    $item[
                                                        'item_subtotal'
                                                    ],
                                                    2
                                                );
                                                ?>

                                            </strong>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <!-- CONTINUE -->

                    <button
                        type="submit"
                        class="continue-payment-btn"
                    >

                        Continue to Payment

                        <span>
                            →
                        </span>

                    </button>

                </form>

            </section>


            <!-- =================================================
                 RIGHT SUMMARY
            ================================================== -->

            <aside class="checkout-summary">

                <div class="summary-card">

                    <div class="summary-header">

                        <span>
                            ORDER SUMMARY
                        </span>

                        <h2>
                            Your Basket
                        </h2>

                    </div>


                    <div class="summary-items">

                        <?php foreach (
                            $cartItems
                            as $item
                        ): ?>

                            <div class="summary-item">

                                <div>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $item[
                                                'product_name'
                                            ]
                                        );
                                        ?>
                                    </strong>

                                    <span>
                                        ×
                                        <?php
                                        echo (int)
                                            $item['quantity'];
                                        ?>
                                    </span>

                                </div>

                                <b>
                                    RM
                                    <?php
                                    echo number_format(
                                        (float)$item['price'] *
                                        (int)$item['quantity'],
                                        2
                                    );
                                    ?>
                                </b>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong id="summarySubtotal">
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

                        <strong id="summaryDelivery">
                            RM
                            <?php
                            echo number_format(
                                $postageFee,
                                2
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="summary-total">

                        <span>
                            Total
                        </span>

                        <strong id="summaryTotal">
                            RM
                            <?php
                            echo number_format(
                                $totalAmount,
                                2
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="secure-box">

                        <span>
                            🔐
                        </span>

                        <div>

                            <strong>
                                Secure checkout
                            </strong>

                            <p>
                                Your payment details are
                                protected.
                            </p>

                        </div>

                    </div>

                </div>

            </aside>

        </div>

    </main>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const deliveryOptions =
            document.querySelectorAll(
                'input[name="delivery_method"]'
            );

        const addressSection =
            document.getElementById(
                'addressSection'
            );

        const addressInput =
            document.getElementById(
                'delivery_address'
            );

        const deliveryDisplay =
            document.getElementById(
                'summaryDelivery'
            );

        const totalDisplay =
            document.getElementById(
                'summaryTotal'
            );

        const subtotal =
            <?php echo json_encode($subtotal); ?>;

        const vendorCount =
            <?php echo count($vendorGroups); ?>;

        function updateDelivery() {

            let selected =
                document.querySelector(
                    'input[name="delivery_method"]:checked'
                );

            if (!selected) {
                return;
            }

            if (
                selected.value === 'Postage'
            ) {

                addressSection.style.display =
                    'block';

                addressInput.required = true;

                const fee =
                    vendorCount * 5;

                const total =
                    subtotal + fee;

                deliveryDisplay.textContent =
                    'RM ' + fee.toFixed(2);

                totalDisplay.textContent =
                    'RM ' + total.toFixed(2);

            } else {

                addressSection.style.display =
                    'none';

                addressInput.required = false;

                deliveryDisplay.textContent =
                    'RM 0.00';

                totalDisplay.textContent =
                    'RM ' + subtotal.toFixed(2);
            }

            document
                .querySelectorAll(
                    '.delivery-option'
                )
                .forEach(function (option) {

                    option.classList.remove(
                        'selected'
                    );

                });

            selected
                .closest('.delivery-option')
                .classList.add('selected');
        }


        deliveryOptions.forEach(
            function (radio) {

                radio.addEventListener(
                    'change',
                    updateDelivery
                );

            }
        );


        updateDelivery();

    }
);

</script>


<style>

/* =========================================================
   CHECKOUT PAGE
========================================================= */

.checkout-page {
    min-height: 100vh;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37,99,235,.12),
            transparent 25%
        ),
        #020617;

    color: #f8fafc;
}


/* =========================================================
   TOPBAR
========================================================= */

.checkout-topbar {
    display: grid;

    grid-template-columns:
        1fr auto 1fr;

    align-items: center;

    gap: 20px;

    padding: 20px 6%;

    border-bottom:
        1px solid
        rgba(148,163,184,.12);

    background:
        rgba(2,6,23,.8);

    backdrop-filter: blur(18px);
}

.checkout-back {
    color: #94a3b8;

    font-size: 13px;
    font-weight: 700;
}

.checkout-back:hover {
    color: #7dd3fc;
}

.checkout-brand {
    font-size: 19px;
    font-weight: 950;
    letter-spacing: 2px;
}

.checkout-brand span {
    color: white;
}

.checkout-brand strong {
    color: #38bdf8;
}

.secure-label {
    justify-self: end;

    color: #64748b;

    font-size: 12px;
    font-weight: 700;
}


/* =========================================================
   PROGRESS
========================================================= */

.checkout-progress {
    display: flex;

    align-items: center;
    justify-content: center;

    gap: 13px;

    padding: 28px 20px;
}

.progress-step {
    display: flex;

    align-items: center;

    gap: 9px;

    color: #475569;
}

.progress-step span {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 31px;
    height: 31px;

    border-radius: 50%;

    border:
        1px solid
        #334155;

    font-size: 10px;
    font-weight: 900;
}

.progress-step p {
    margin: 0;

    font-size: 12px;
    font-weight: 800;
}

.progress-step.active {
    color: #7dd3fc;
}

.progress-step.active span {
    border-color: #38bdf8;

    background:
        rgba(14,165,233,.14);

    color: #38bdf8;
}

.progress-line {
    width: 70px;
    height: 1px;

    background: #1e293b;
}


/* =========================================================
   CONTAINER
========================================================= */

.checkout-container {
    width: 88%;
    max-width: 1250px;

    margin: auto;

    padding-bottom: 80px;
}

.checkout-layout {
    display: grid;

    grid-template-columns:
        minmax(0, 1.6fr)
        minmax(320px, .8fr);

    align-items: start;

    gap: 25px;
}


/* =========================================================
   ALERT
========================================================= */

.checkout-alert {
    display: flex;

    gap: 13px;

    margin-bottom: 20px;

    padding: 16px 18px;

    border:
        1px solid
        rgba(248,113,113,.25);

    border-radius: 15px;

    background:
        rgba(127,29,29,.18);

    color: #fecaca;
}

.checkout-alert > span {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 28px;
    height: 28px;

    flex-shrink: 0;

    border-radius: 50%;

    background:
        rgba(248,113,113,.15);

    color: #f87171;

    font-weight: 900;
}

.checkout-alert strong {
    color: #fca5a5;
}

.checkout-alert p {
    margin: 4px 0 0;

    color: #94a3b8;
    font-size: 13px;
}


/* =========================================================
   CARDS
========================================================= */

.checkout-card {
    margin-bottom: 20px;

    padding: 26px;

    border:
        1px solid
        rgba(148,163,184,.13);

    border-radius: 22px;

    background:
        linear-gradient(
            145deg,
            rgba(15,23,42,.96),
            rgba(8,47,87,.58)
        );

    box-shadow:
        0 15px 50px
        rgba(0,0,0,.12);
}

.checkout-card-title {
    display: flex;

    align-items: center;

    gap: 14px;

    margin-bottom: 25px;
}

.checkout-title-icon {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 42px;
    height: 42px;

    flex-shrink: 0;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-size: 11px;
    font-weight: 900;
}

.checkout-card-title span {
    color: #38bdf8;

    font-size: 10px;
    font-weight: 900;

    letter-spacing: 1.5px;
}

.checkout-card-title h2 {
    margin: 3px 0 0;

    font-size: 22px;
    font-weight: 900;
}


/* =========================================================
   CUSTOMER
========================================================= */

.customer-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 17px;
}

.customer-field {
    padding: 15px;

    border:
        1px solid
        rgba(148,163,184,.1);

    border-radius: 13px;

    background:
        rgba(2,6,23,.3);
}

.customer-field label {
    display: block;

    margin-bottom: 7px;

    color: #64748b;

    font-size: 10px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 1px;
}

.customer-value {
    color: #e2e8f0;

    font-size: 14px;
    font-weight: 700;
}

.edit-profile-link {
    align-self: center;

    color: #38bdf8;

    font-size: 12px;
    font-weight: 800;
}


/* =========================================================
   DELIVERY
========================================================= */

.delivery-options {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 13px;
}

.delivery-option {
    position: relative;

    display: grid;

    grid-template-columns:
        auto auto 1fr auto;

    align-items: center;

    gap: 12px;

    padding: 17px;

    border:
        1px solid
        rgba(148,163,184,.13);

    border-radius: 16px;

    cursor: pointer;

    transition: .25s ease;
}

.delivery-option:hover,
.delivery-option.selected {
    border-color:
        rgba(56,189,248,.55);

    background:
        rgba(14,165,233,.08);
}

.delivery-option input {
    position: absolute;

    opacity: 0;
}

.delivery-radio {
    width: 17px;
    height: 17px;

    border:
        2px solid
        #475569;

    border-radius: 50%;
}

.delivery-option.selected
.delivery-radio {
    border:
        5px solid
        #38bdf8;
}

.delivery-icon {
    font-size: 22px;
}

.delivery-info strong {
    display: block;

    color: white;

    font-size: 13px;
}

.delivery-info p {
    margin: 3px 0 0;

    color: #64748b;

    font-size: 10px;
}

.delivery-option > b {
    color: #7dd3fc;

    font-size: 11px;
}


/* =========================================================
   ADDRESS
========================================================= */

.address-section {
    margin-top: 20px;
}

.address-section label {
    display: block;

    margin-bottom: 8px;

    color: #cbd5e1;

    font-size: 12px;
    font-weight: 800;
}

.address-section textarea {
    width: 100%;

    box-sizing: border-box;

    resize: vertical;

    padding: 14px;

    border:
        1px solid
        rgba(148,163,184,.15);

    border-radius: 14px;

    outline: none;

    background:
        rgba(2,6,23,.45);

    color: white;

    font: inherit;

    line-height: 1.5;
}

.address-section textarea:focus {
    border-color: #38bdf8;
}

.address-section small {
    display: block;

    margin-top: 7px;

    color: #475569;

    font-size: 10px;
}


/* =========================================================
   VENDOR ORDERS
========================================================= */

.vendor-order {
    overflow: hidden;

    margin-bottom: 15px;

    border:
        1px solid
        rgba(148,163,184,.1);

    border-radius: 16px;

    background:
        rgba(2,6,23,.25);
}

.vendor-order:last-child {
    margin-bottom: 0;
}

.vendor-order-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 15px 17px;

    border-bottom:
        1px solid
        rgba(148,163,184,.08);
}

.vendor-order-header span {
    display: block;

    color: #475569;

    font-size: 9px;
    font-weight: 900;

    letter-spacing: 1px;
}

.vendor-order-header strong {
    display: block;

    margin-top: 3px;

    color: #e2e8f0;

    font-size: 13px;
}

.vendor-order-header > b {
    color: #7dd3fc;

    font-size: 14px;
}


/* =========================================================
   CHECKOUT ITEM
========================================================= */

.checkout-item {
    display: flex;

    align-items: center;

    gap: 13px;

    padding: 12px 17px;
}

.checkout-item-image {
    width: 52px;
    height: 52px;

    flex-shrink: 0;

    overflow: hidden;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #0f3d78,
            #172554
        );
}

.checkout-item-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}

.checkout-item-image > div {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 100%;
    height: 100%;

    color: #38bdf8;
}

.checkout-item-info {
    flex: 1;
}

.checkout-item-info strong {
    display: block;

    color: #e2e8f0;

    font-size: 12px;
}

.checkout-item-info span {
    display: block;

    margin-top: 4px;

    color: #64748b;

    font-size: 10px;
}

.checkout-item-price {
    color: #cbd5e1;

    font-size: 12px;
}


/* =========================================================
   BUTTON
========================================================= */

.continue-payment-btn {
    display: flex;

    align-items: center;
    justify-content: space-between;

    width: 100%;

    margin-top: 5px;

    padding: 18px 21px;

    border: 0;

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: white;

    font-size: 14px;
    font-weight: 900;

    cursor: pointer;

    box-shadow:
        0 12px 30px
        rgba(14,165,233,.18);

    transition: .25s ease;
}

.continue-payment-btn:hover {
    transform: translateY(-2px);

    box-shadow:
        0 18px 35px
        rgba(14,165,233,.27);
}

.continue-payment-btn span {
    font-size: 22px;
}


/* =========================================================
   SUMMARY
========================================================= */

.checkout-summary {
    position: sticky;

    top: 20px;
}

.summary-card {
    overflow: hidden;

    border:
        1px solid
        rgba(56,189,248,.17);

    border-radius: 22px;

    background:
        linear-gradient(
            145deg,
            #071a35,
            #020617
        );

    box-shadow:
        0 20px 70px
        rgba(0,0,0,.25);
}

.summary-header {
    padding: 25px;

    border-bottom:
        1px solid
        rgba(148,163,184,.09);
}

.summary-header span {
    color: #38bdf8;

    font-size: 10px;
    font-weight: 900;

    letter-spacing: 1.5px;
}

.summary-header h2 {
    margin: 5px 0 0;

    font-size: 25px;
    font-weight: 900;
}

.summary-items {
    max-height: 310px;

    overflow-y: auto;

    padding: 17px 25px;
}

.summary-item {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 10px 0;
}

.summary-item > div {
    min-width: 0;
}

.summary-item strong {
    display: block;

    overflow: hidden;

    color: #cbd5e1;

    font-size: 12px;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.summary-item span {
    color: #475569;

    font-size: 10px;
}

.summary-item b {
    flex-shrink: 0;

    color: #94a3b8;

    font-size: 11px;
}

.summary-divider {
    height: 1px;

    margin: 0 25px;

    background:
        rgba(148,163,184,.09);
}

.summary-row,
.summary-total {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 11px 25px;
}

.summary-row {
    color: #64748b;

    font-size: 12px;
}

.summary-row strong {
    color: #cbd5e1;
}

.summary-total {
    padding-top: 20px;
    padding-bottom: 22px;

    color: white;

    font-size: 15px;
}

.summary-total strong {
    color: #7dd3fc;

    font-size: 25px;
    font-weight: 950;
}

.secure-box {
    display: flex;

    gap: 12px;

    margin: 0 18px 18px;

    padding: 14px;

    border:
        1px solid
        rgba(56,189,248,.1);

    border-radius: 14px;

    background:
        rgba(14,165,233,.05);
}

.secure-box > span {
    font-size: 20px;
}

.secure-box strong {
    color: #cbd5e1;

    font-size: 11px;
}

.secure-box p {
    margin: 3px 0 0;

    color: #475569;

    font-size: 9px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 950px) {

    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        position: static;

        order: -1;
    }

}

@media (max-width: 700px) {

    .checkout-topbar {
        grid-template-columns: 1fr auto;
    }

    .checkout-brand {
        display: none;
    }

    .secure-label {
        justify-self: end;
    }

    .checkout-progress {
        gap: 7px;
    }

    .progress-line {
        width: 25px;
    }

    .progress-step p {
        display: none;
    }

    .checkout-container {
        width: 92%;
    }

    .checkout-card {
        padding: 20px;
    }

    .customer-grid,
    .delivery-options {
        grid-template-columns: 1fr;
    }

    .delivery-option {
        grid-template-columns:
            auto auto 1fr auto;
    }

}

@media (max-width: 450px) {

    .checkout-progress {
        justify-content: space-between;
    }

    .checkout-card-title h2 {
        font-size: 19px;
    }

    .checkout-card-title {
        margin-bottom: 20px;
    }

    .summary-header {
        padding: 20px;
    }

    .summary-items {
        padding-left: 20px;
        padding-right: 20px;
    }

    .summary-divider {
        margin-left: 20px;
        margin-right: 20px;
    }

    .summary-row,
    .summary-total {
        padding-left: 20px;
        padding-right: 20px;
    }

}

</style>

</body>
</html>