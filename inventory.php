<?php
// =========================================================
// HOCHIPO HUB - INVENTORY PAGE
// File: inventory.php
// =========================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

// ---------------------------------------------------------
// ACCESS CONTROL
// ---------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'customer';

// Only vendor/admin can manage inventory
if (!in_array($user_role, ['vendor', 'admin'], true)) {
    header("Location: dashboard.php");
    exit;
}

// ---------------------------------------------------------
// DATABASE CONNECTION
// Supports $conn / $pdo depending on database/db.php
// ---------------------------------------------------------
$db = $conn ?? $pdo ?? null;

if (!$db) {
    die("Database connection not found.");
}

// ---------------------------------------------------------
// HELPER
// ---------------------------------------------------------
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------
// FETCH INVENTORY
// ---------------------------------------------------------
$inventory = [];
$total_products = 0;
$total_stock = 0;
$out_of_stock = 0;
$low_stock = 0;

try {

    if ($user_role === 'vendor') {

        /*
         * Vendor can ONLY see products belonging
         * to their own vendor account.
         */

        $sql = "
            SELECT
                i.inventory_id,
                i.product_id,
                i.quantity AS inventory_quantity,
                i.last_updated,

                p.product_name,
                p.price,
                p.stock_quantity,
                p.image,
                p.status,

                v.vendor_id,
                v.business_name,

                c.category_name

            FROM inventory i

            INNER JOIN products p
                ON i.product_id = p.product_id

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            LEFT JOIN categories c
                ON p.category_id = c.category_id

            WHERE v.user_id = ?

            ORDER BY i.last_updated DESC
        ";

        if ($db instanceof PDO) {

            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {

            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $inventory[] = $row;
            }

            $stmt->close();
        }

    } else {

        /*
         * Admin can see ALL inventory.
         */

        $sql = "
            SELECT
                i.inventory_id,
                i.product_id,
                i.quantity AS inventory_quantity,
                i.last_updated,

                p.product_name,
                p.price,
                p.stock_quantity,
                p.image,
                p.status,

                v.vendor_id,
                v.business_name,

                c.category_name

            FROM inventory i

            INNER JOIN products p
                ON i.product_id = p.product_id

            INNER JOIN vendors v
                ON p.vendor_id = v.vendor_id

            LEFT JOIN categories c
                ON p.category_id = c.category_id

            ORDER BY i.last_updated DESC
        ";

        if ($db instanceof PDO) {

            $stmt = $db->query($sql);
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {

            $result = $db->query($sql);

            while ($row = $result->fetch_assoc()) {
                $inventory[] = $row;
            }
        }
    }

    // -----------------------------------------------------
    // STATISTICS
    // -----------------------------------------------------
    foreach ($inventory as $item) {

        $qty = (int) $item['inventory_quantity'];

        $total_products++;
        $total_stock += $qty;

        if ($qty <= 0) {
            $out_of_stock++;
        } elseif ($qty <= 5) {
            $low_stock++;
        }
    }

} catch (Throwable $e) {

    $error_message = "Unable to load inventory.";

    // Uncomment during development only:
    // $error_message = $e->getMessage();
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

    <title>Inventory | HochipoHub</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

    <style>

        /* =====================================================
           INVENTORY PAGE
           GEN Z BLUE UI
        ===================================================== */

        .inventory-page {
            min-height: 100vh;
            padding: 35px;
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(0, 102, 255, .18),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 20%,
                    rgba(0, 212, 255, .13),
                    transparent 28%
                ),
                #f5f8ff;
        }

        .inventory-container {
            max-width: 1400px;
            margin: auto;
        }

        .inventory-hero {
            position: relative;
            overflow: hidden;
            padding: 35px;
            margin-bottom: 25px;
            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #071b52,
                    #0639a6 50%,
                    #008cff
                );

            color: white;
            box-shadow:
                0 20px 50px rgba(0, 72, 180, .25);
        }

        .inventory-hero::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            right: -70px;
            top: -100px;

            border-radius: 50%;

            background:
                rgba(255,255,255,.09);
        }

        .inventory-hero h1 {
            position: relative;
            z-index: 2;
            margin: 0 0 8px;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .inventory-hero p {
            position: relative;
            z-index: 2;
            margin: 0;
            color: rgba(255,255,255,.78);
            font-size: 15px;
        }

        .inventory-stats {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 18px;
            margin-bottom: 25px;
        }

        .inventory-stat {
            padding: 22px;
            border-radius: 22px;

            background: rgba(255,255,255,.88);

            border: 1px solid
                rgba(31, 91, 255, .09);

            box-shadow:
                0 10px 30px
                rgba(20, 55, 120, .08);
        }

        .inventory-stat-label {
            font-size: 13px;
            font-weight: 700;
            color: #71809e;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .inventory-stat-value {
            margin-top: 7px;
            font-size: 30px;
            font-weight: 850;
            color: #10265e;
        }

        .inventory-stat-icon {
            float: right;
            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: #eaf2ff;
            color: #0866ff;

            font-size: 20px;
            font-weight: 800;
        }

        .inventory-card {
            overflow: hidden;

            background: rgba(255,255,255,.94);

            border-radius: 25px;

            border: 1px solid
                rgba(20, 65, 160, .08);

            box-shadow:
                0 15px 45px
                rgba(30, 65, 130, .09);
        }

        .inventory-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 25px 27px;

            border-bottom: 1px solid #edf1f8;
        }

        .inventory-card-header h2 {
            margin: 0;
            color: #10265e;
            font-size: 21px;
        }

        .inventory-card-header span {
            color: #71809e;
            font-size: 13px;
        }

        .inventory-table-wrap {
            overflow-x: auto;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .inventory-table th {
            padding: 16px 20px;

            text-align: left;

            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;

            color: #71809e;
            background: #f7f9fd;
        }

        .inventory-table td {
            padding: 17px 20px;

            border-top: 1px solid #edf1f8;

            color: #27385f;
            font-size: 14px;
        }

        .inventory-product {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .inventory-product-image {
            width: 55px;
            height: 55px;

            flex-shrink: 0;

            border-radius: 15px;

            object-fit: cover;

            background:
                linear-gradient(
                    135deg,
                    #e9f1ff,
                    #d7e7ff
                );
        }

        .inventory-product-name {
            font-weight: 800;
            color: #10265e;
        }

        .inventory-category {
            margin-top: 3px;
            font-size: 12px;
            color: #8491aa;
        }

        .stock-number {
            font-size: 17px;
            font-weight: 800;
            color: #10265e;
        }

        .stock-bar {
            width: 100px;
            height: 6px;
            margin-top: 6px;

            overflow: hidden;

            border-radius: 20px;
            background: #e8edf6;
        }

        .stock-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        .stock-good span {
            width: 100%;
            background: #087cff;
        }

        .stock-low span {
            width: 35%;
            background: #ffb020;
        }

        .stock-empty span {
            width: 4%;
            background: #ff4d67;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 7px 11px;

            border-radius: 999px;

            font-size: 11px;
            font-weight: 800;
        }

        .status-available {
            color: #08733f;
            background: #e4faef;
        }

        .status-out {
            color: #b42338;
            background: #ffebee;
        }

        .status-hidden {
            color: #65728b;
            background: #eef1f5;
        }

        .vendor-name {
            font-weight: 700;
            color: #344774;
        }

        .empty-inventory {
            padding: 70px 25px;
            text-align: center;
        }

        .empty-icon {
            width: 75px;
            height: 75px;

            margin: 0 auto 18px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 23px;

            background: #eaf2ff;
            color: #087cff;

            font-size: 32px;
        }

        .empty-inventory h3 {
            margin: 0 0 8px;
            color: #10265e;
        }

        .empty-inventory p {
            margin: 0;
            color: #7b88a1;
            font-size: 14px;
        }

        .inventory-error {
            margin-bottom: 20px;
            padding: 15px 18px;

            border-radius: 15px;

            background: #fff0f2;
            color: #b42338;

            font-weight: 700;
        }

        @media (max-width: 900px) {

            .inventory-page {
                padding: 20px;
            }

            .inventory-stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 550px) {

            .inventory-stats {
                grid-template-columns: 1fr;
            }

            .inventory-hero {
                padding: 25px;
            }

            .inventory-hero h1 {
                font-size: 27px;
            }

        }

    </style>

</head>

<body>

<?php
/*
 * Navbar is intentionally loaded here.
 * It uses the existing includes/navbar.php.
 */
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="inventory-page">

    <div class="inventory-container">

        <!-- =================================================
             HERO
        ================================================== -->

        <section class="inventory-hero">

            <h1>Inventory Control 📦</h1>

            <p>
                Track your products, monitor stock levels
                and keep everything under control.
            </p>

        </section>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if (isset($error_message)): ?>

            <div class="inventory-error">
                <?= h($error_message); ?>
            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="inventory-stats">

            <div class="inventory-stat">

                <div class="inventory-stat-icon">
                    #
                </div>

                <div class="inventory-stat-label">
                    Products
                </div>

                <div class="inventory-stat-value">
                    <?= number_format($total_products); ?>
                </div>

            </div>


            <div class="inventory-stat">

                <div class="inventory-stat-icon">
                    📦
                </div>

                <div class="inventory-stat-label">
                    Total Stock
                </div>

                <div class="inventory-stat-value">
                    <?= number_format($total_stock); ?>
                </div>

            </div>


            <div class="inventory-stat">

                <div class="inventory-stat-icon">
                    !
                </div>

                <div class="inventory-stat-label">
                    Low Stock
                </div>

                <div class="inventory-stat-value">
                    <?= number_format($low_stock); ?>
                </div>

            </div>


            <div class="inventory-stat">

                <div class="inventory-stat-icon">
                    ×
                </div>

                <div class="inventory-stat-label">
                    Out of Stock
                </div>

                <div class="inventory-stat-value">
                    <?= number_format($out_of_stock); ?>
                </div>

            </div>

        </section>


        <!-- =================================================
             INVENTORY TABLE
        ================================================== -->

        <section class="inventory-card">

            <div class="inventory-card-header">

                <div>
                    <h2>Product Inventory</h2>

                    <span>
                        <?= number_format($total_products); ?>
                        product(s) listed
                    </span>
                </div>

            </div>


            <?php if (!empty($inventory)): ?>

                <div class="inventory-table-wrap">

                    <table class="inventory-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <?php if ($user_role === 'admin'): ?>

                                    <th>
                                        Vendor
                                    </th>

                                <?php endif; ?>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Last Updated
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($inventory as $item): ?>

                            <?php

                                $qty =
                                    (int)
                                    $item['inventory_quantity'];

                                if ($qty <= 0) {

                                    $stock_class =
                                        'stock-empty';

                                } elseif ($qty <= 5) {

                                    $stock_class =
                                        'stock-low';

                                } else {

                                    $stock_class =
                                        'stock-good';
                                }


                                $product_status =
                                    $item['status'] ?? 'Available';


                                if (
                                    $product_status ===
                                    'Out of Stock'
                                ) {

                                    $badge_class =
                                        'status-out';

                                } elseif (
                                    $product_status ===
                                    'Hidden'
                                ) {

                                    $badge_class =
                                        'status-hidden';

                                } else {

                                    $badge_class =
                                        'status-available';
                                }


                                $image =
                                    trim(
                                        $item['image'] ?? ''
                                    );

                                if ($image !== '') {

                                    /*
                                     * Images stored in:
                                     * image/product/
                                     */

                                    $image_path =
                                        'image/product/' .
                                        ltrim(
                                            $image,
                                            '/\\'
                                        );

                                } else {

                                    $image_path =
                                        'image/logo.jpg';
                                }

                            ?>

                            <tr>

                                <!-- PRODUCT -->

                                <td>

                                    <div
                                        class="inventory-product"
                                    >

                                        <img
                                            class="inventory-product-image"
                                            src="<?= h($image_path); ?>"
                                            alt="<?= h($item['product_name']); ?>"
                                            onerror="this.src='image/logo.jpg';"
                                        >

                                        <div>

                                            <div
                                                class="inventory-product-name"
                                            >
                                                <?= h(
                                                    $item['product_name']
                                                ); ?>
                                            </div>

                                            <div
                                                class="inventory-category"
                                            >
                                                <?= h(
                                                    $item['category_name']
                                                    ?? 'Uncategorized'
                                                ); ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- VENDOR -->

                                <?php if (
                                    $user_role ===
                                    'admin'
                                ): ?>

                                    <td>

                                        <span
                                            class="vendor-name"
                                        >
                                            <?= h(
                                                $item['business_name']
                                            ); ?>
                                        </span>

                                    </td>

                                <?php endif; ?>


                                <!-- PRICE -->

                                <td>

                                    <strong>
                                        RM
                                        <?= number_format(
                                            (float)
                                            $item['price'],
                                            2
                                        ); ?>
                                    </strong>

                                </td>


                                <!-- STOCK -->

                                <td>

                                    <div
                                        class="stock-number"
                                    >
                                        <?= number_format(
                                            $qty
                                        ); ?>
                                    </div>

                                    <div
                                        class="stock-bar
                                        <?= h($stock_class); ?>"
                                    >
                                        <span></span>
                                    </div>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="status-badge
                                        <?= h($badge_class); ?>"
                                    >

                                        <?= h(
                                            $product_status
                                        ); ?>

                                    </span>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= !empty(
                                        $item['last_updated']
                                    )
                                        ? h(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $item[
                                                        'last_updated'
                                                    ]
                                                )
                                            )
                                        )
                                        : '-';
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-inventory">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>
                        No inventory yet
                    </h3>

                    <p>
                        Add a product first and its inventory
                        record will appear here.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>

</body>
</html>