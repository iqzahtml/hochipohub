<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR INVENTORY
|--------------------------------------------------------------------------
| File:
| inventory.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

$db = getDB();

requireLogin();

$userId = (int) currentUserId();


/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        role,
        status
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    $_SESSION['error'] =
        'User account could not be found.';

    redirect(
        BASE_URL . 'index.php'
    );

}


/*
|--------------------------------------------------------------------------
| ONLY VENDOR
|--------------------------------------------------------------------------
*/

if ($user['role'] !== 'vendor') {

    $_SESSION['error'] =
        'Vendor access required.';

    redirect(
        BASE_URL . 'dashboard.php'
    );

}


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        vendor_id,
        business_name,
        approval_status
    FROM vendors
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$vendor = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$vendor) {

    $_SESSION['error'] =
        'Vendor profile not found.';

    redirect(
        BASE_URL . 'dashboard.php'
    );

}


$vendorId = (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| UPDATE INVENTORY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {

        $_SESSION['error'] =
            'Invalid security token. Please try again.';

        redirect(
            BASE_URL . 'inventory.php'
        );

    }


    $productId =
        isset($_POST['product_id'])
            ? (int) $_POST['product_id']
            : 0;

    $quantity =
        isset($_POST['quantity'])
            ? (int) $_POST['quantity']
            : -1;


    if ($productId <= 0 || $quantity < 0) {

        $_SESSION['error'] =
            'Please enter a valid stock quantity.';

        redirect(
            BASE_URL . 'inventory.php'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY PRODUCT BELONGS TO VENDOR
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT
            product_id,
            product_name
        FROM products
        WHERE product_id = ?
          AND vendor_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $productId,
        $vendorId
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        $_SESSION['error'] =
            'You cannot update this product.';

        redirect(
            BASE_URL . 'inventory.php'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    try {

        $db->beginTransaction();


        /*
        | Update products table
        */

        $stmt = $db->prepare("
            UPDATE products

            SET
                stock_quantity = ?,
                status =
                    CASE
                        WHEN ? <= 0
                            THEN 'Out of Stock'

                        ELSE 'Available'
                    END

            WHERE product_id = ?
              AND vendor_id = ?
        ");

        $stmt->execute([
            $quantity,
            $quantity,
            $productId,
            $vendorId
        ]);


        /*
        | Check inventory record
        */

        $stmt = $db->prepare("
            SELECT inventory_id
            FROM inventory
            WHERE product_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $productId
        ]);

        $inventory =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if ($inventory) {

            $stmt = $db->prepare("
                UPDATE inventory

                SET
                    quantity = ?,
                    last_updated = CURRENT_TIMESTAMP

                WHERE product_id = ?
            ");

            $stmt->execute([
                $quantity,
                $productId
            ]);

        } else {

            $stmt = $db->prepare("
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
            ");

            $stmt->execute([
                $productId,
                $quantity
            ]);

        }


        $db->commit();


        $_SESSION['success'] =
            'Inventory for "' .
            $product['product_name'] .
            '" has been updated successfully.';


    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $_SESSION['error'] =
            'Unable to update inventory. Please try again.';

    }


    redirect(
        BASE_URL . 'inventory.php'
    );

}


/*
|--------------------------------------------------------------------------
| GET INVENTORY
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT

        p.product_id,
        p.product_name,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        p.updated_at,

        c.category_name,

        i.inventory_id,
        i.quantity AS inventory_quantity,
        i.last_updated

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.category_id

    LEFT JOIN inventory i
        ON p.product_id = i.product_id

    WHERE p.vendor_id = ?

    ORDER BY p.updated_at DESC
");

$stmt->execute([
    $vendorId
]);

$inventory =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalProducts = count($inventory);

$totalStock = 0;
$lowStock = 0;
$outOfStock = 0;

foreach ($inventory as $item) {

    $quantity =
        $item['inventory_quantity'] !== null
            ? (int) $item['inventory_quantity']
            : (int) $item['stock_quantity'];

    $totalStock += $quantity;

    if ($quantity <= 0) {

        $outOfStock++;

    } elseif ($quantity <= 5) {

        $lowStock++;

    }

}


$pageTitle =
    'Inventory - ' .
    $vendor['business_name'];


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/vendor_sidebar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    VENDOR CENTER
                </span>

                <h1>
                    Inventory
                </h1>

                <p>
                    Manage stock levels for your products.
                </p>

            </div>


            <div>

                <a
                    href="<?= e(BASE_URL) ?>seller/add_product.php"
                    class="btn btn-primary"
                >
                    + Add Product
                </a>

            </div>

        </section>



        <!-- =====================================================
             APPROVAL WARNING
        ====================================================== -->

        <?php if (
            $vendor['approval_status'] !== 'Approved'
        ): ?>

            <div class="alert alert-warning">

                Your vendor account is currently

                <strong>
                    <?= e($vendor['approval_status']) ?>
                </strong>.

                Some vendor features may remain unavailable
                until your account is approved.

            </div>

        <?php endif; ?>



        <!-- =====================================================
             FLASH
        ====================================================== -->

        <?php if (!empty($_SESSION['success'])): ?>

            <div class="alert alert-success">

                <?= e($_SESSION['success']) ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>


        <?php if (!empty($_SESSION['error'])): ?>

            <div class="alert alert-danger">

                <?= e($_SESSION['error']) ?>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>



        <!-- =====================================================
             STATS
        ====================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <span class="stat-label">
                    Total Products
                </span>

                <strong class="stat-value">
                    <?= $totalProducts ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Total Stock
                </span>

                <strong class="stat-value">
                    <?= $totalStock ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Low Stock
                </span>

                <strong class="stat-value">
                    <?= $lowStock ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Out of Stock
                </span>

                <strong class="stat-value">
                    <?= $outOfStock ?>
                </strong>

            </div>


        </section>



        <!-- =====================================================
             INVENTORY TABLE
        ====================================================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="small-label">
                        STOCK MANAGEMENT
                    </span>

                    <h2>
                        Product Inventory
                    </h2>

                </div>

            </div>


            <?php if (empty($inventory)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>
                        No products yet
                    </h3>

                    <p>
                        Add your first product to start
                        managing inventory.
                    </p>


                    <a
                        href="<?= e(BASE_URL) ?>seller/add_product.php"
                        class="btn btn-primary"
                    >
                        Add Product
                    </a>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table class="dashboard-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Current Stock
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Update Stock
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $inventory
                                as $item
                            ): ?>

                                <?php

                                $quantity =
                                    $item[
                                        'inventory_quantity'
                                    ] !== null

                                        ? (int)
                                            $item[
                                                'inventory_quantity'
                                            ]

                                        : (int)
                                            $item[
                                                'stock_quantity'
                                            ];

                                ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div
                                            style="
                                                display:flex;
                                                align-items:center;
                                                gap:12px;
                                            "
                                        >

                                            <?php if (
                                                !empty(
                                                    $item['image']
                                                )
                                            ): ?>

                                                <img
                                                    src="<?= e(BASE_URL) ?>uploads/products/<?= e(basename($item['image'])) ?>"
                                                    alt="<?= e($item['product_name']) ?>"
                                                    style="
                                                        width:55px;
                                                        height:55px;
                                                        object-fit:cover;
                                                        border-radius:10px;
                                                    "
                                                >

                                            <?php else: ?>

                                                <div
                                                    style="
                                                        width:55px;
                                                        height:55px;
                                                        display:flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        background:#eef2ff;
                                                        border-radius:10px;
                                                    "
                                                >
                                                    📦
                                                </div>

                                            <?php endif; ?>


                                            <strong>

                                                <?= e(
                                                    $item[
                                                        'product_name'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </div>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <?= e(
                                            $item[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $item['price'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <strong>

                                            <?= $quantity ?>

                                        </strong>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if (
                                            $quantity <= 0
                                        ): ?>

                                            <span
                                                class="status-badge status-danger"
                                            >
                                                Out of Stock
                                            </span>

                                        <?php elseif (
                                            $quantity <= 5
                                        ): ?>

                                            <span
                                                class="status-badge status-warning"
                                            >
                                                Low Stock
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="status-badge status-success"
                                            >
                                                In Stock
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- UPDATE -->

                                    <td>

                                        <form
                                            method="POST"
                                            action="<?= e(BASE_URL) ?>inventory.php"
                                            style="
                                                display:flex;
                                                gap:8px;
                                                align-items:center;
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(csrfToken()) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $item['product_id'] ?>"
                                            >


                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?= $quantity ?>"
                                                min="0"
                                                style="
                                                    width:90px;
                                                "
                                                required
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                            >
                                                Update
                                            </button>

                                        </form>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php endif; ?>

        </section>


    </div>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>