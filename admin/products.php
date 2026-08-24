<?php

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


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
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {

    header('Location: ../index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {

        try {

            $db->beginTransaction();

            foreach (
                [
                    'wishlist',
                    'cart',
                    'reviews',
                    'inventory'
                ] as $table
            ) {

                $stmt = $db->prepare(
                    "DELETE FROM {$table}
                     WHERE product_id = ?"
                );

                $stmt->execute([$id]);
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING ORDERS
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare(
                "SELECT COUNT(*)
                 FROM order_details
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);


            if ((int) $stmt->fetchColumn() > 0) {

                $db->rollBack();

                header(
                    'Location: products.php?error=order'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK PRODUCT
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare(
                "SELECT product_id
                 FROM products
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);


            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

                $db->rollBack();

                header(
                    'Location: products.php?error=notfound'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE PRODUCT
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare(
                "DELETE FROM products
                 WHERE product_id = ?"
            );

            $stmt->execute([$id]);


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare(
                "INSERT INTO admin_logs
                    (
                        admin_id,
                        action,
                        target_type,
                        target_id
                    )
                 VALUES
                    (?, ?, ?, ?)"
            );

            $stmt->execute([
                (int) $_SESSION['user_id'],
                'Deleted product',
                'product',
                $id
            ]);


            $db->commit();


            header(
                'Location: products.php?success=deleted'
            );

            exit;


        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log($e->getMessage());

            header(
                'Location: products.php?error=delete'
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $id =
        (int) (
            $_POST['product_id']
            ?? 0
        );

    $status =
        $_POST['status']
        ?? '';


    $allowed = [
        'Available',
        'Out of Stock',
        'Hidden'
    ];


    if (
        $id > 0 &&
        in_array(
            $status,
            $allowed,
            true
        )
    ) {

        try {

            $stmt = $db->prepare(
                "UPDATE products
                 SET status = ?
                 WHERE product_id = ?"
            );

            $stmt->execute([
                $status,
                $id
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare(
                "INSERT INTO admin_logs
                    (
                        admin_id,
                        action,
                        target_type,
                        target_id
                    )
                 VALUES
                    (?, ?, ?, ?)"
            );

            $stmt->execute([
                $_SESSION['user_id'],
                'Updated product status to ' . $status,
                'product',
                $id
            ]);


            header(
                'Location: products.php?success=status'
            );

            exit;


        } catch (PDOException $e) {

            error_log($e->getMessage());

            header(
                'Location: products.php?error=update'
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );

$status_filter =
    $_GET['status']
    ?? '';

$category_filter =
    (int) (
        $_GET['category']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $db
    ->query(
        "SELECT
            category_id,
            category_name
         FROM categories
         ORDER BY category_name ASC"
    )
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| PRODUCT QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        p.created_at,

        c.category_name,

        v.vendor_id,
        v.business_name,

        u.name AS vendor_name

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.category_id

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    INNER JOIN users u
        ON v.user_id = u.user_id

    WHERE 1 = 1
";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            p.product_name LIKE ?
            OR v.business_name LIKE ?
            OR u.name LIKE ?
        )
    ";

    $value =
        "%{$search}%";

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status_filter !== '') {

    $sql .= "
        AND p.status = ?
    ";

    $params[] =
        $status_filter;
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($category_filter > 0) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] =
        $category_filter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY p.created_at DESC
";


$stmt =
    $db->prepare($sql);

$stmt->execute($params);

$products =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| PRODUCT STATISTICS
|--------------------------------------------------------------------------
*/

$total_products =
    (int) $db
        ->query(
            "SELECT COUNT(*)
             FROM products"
        )
        ->fetchColumn();


$available_products =
    (int) $db
        ->query(
            "SELECT COUNT(*)
             FROM products
             WHERE status = 'Available'"
        )
        ->fetchColumn();


$out_of_stock =
    (int) $db
        ->query(
            "SELECT COUNT(*)
             FROM products
             WHERE status = 'Out of Stock'"
        )
        ->fetchColumn();


$hidden_products =
    (int) $db
        ->query(
            "SELECT COUNT(*)
             FROM products
             WHERE status = 'Hidden'"
        )
        ->fetchColumn();

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Products | HochipoHub Admin
    </title>


    <!-- Poppins -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Existing CSS -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <!-- Product Page UI -->

    <style>

        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }


        html,
        body {

            margin: 0;
            padding: 0;

            font-family:
                'Poppins',
                sans-serif !important;

        }


        body {

            background:
                linear-gradient(
                    135deg,
                    #f4f8ff 0%,
                    #eef5ff 45%,
                    #f8fbff 100%
                );

            color: #0f172a;

        }


        /* =========================================================
           FORCE POPPINS THROUGH SIDEBAR
        ========================================================= */

        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {

            font-family:
                'Poppins',
                sans-serif !important;

        }


        /* =========================================================
           REMOVE DEAD HAMBURGER BUTTON
        ========================================================= */

        #adminSidebarToggle,
        .admin-sidebar-toggle {

            display: none !important;

        }


        /* =========================================================
           MAIN AREA
        ========================================================= */

        .admin-main {

            padding:
                34px
                38px
                60px;

        }


        /* =========================================================
           TOP HEADER
        ========================================================= */

        .admin-topbar {

            margin-bottom: 28px;

            background:
                linear-gradient(
                    135deg,
                    #ffffff,
                    #f8fbff
                );

            border:
                1px solid #dbeafe;

            border-radius: 24px;

            padding:
                28px 30px;

            box-shadow:
                0 18px 45px
                rgba(
                    30,
                    64,
                    175,
                    0.08
                );

        }


        .admin-header-left {

            display: flex;

            align-items: center;

            gap: 20px;

        }


        .admin-topbar h1 {

            margin: 0;

            font-size: 34px;

            line-height: 1.2;

            font-weight: 800;

            letter-spacing: -1px;

            background:
                linear-gradient(
                    90deg,
                    #0f2f87,
                    #1769e0,
                    #21a4ff
                );

            -webkit-background-clip: text;

            -webkit-text-fill-color:
                transparent;

            background-clip: text;

        }


        .admin-topbar p {

            margin:
                8px 0 0;

            color: #64748b;

            font-size: 14px;

            font-weight: 500;

        }


        /* =========================================================
           ALERT
        ========================================================= */

        .admin-alert {

            border-radius: 16px;

            padding:
                15px 18px;

            margin-bottom: 22px;

            font-size: 14px;

            font-weight: 600;

            border: 1px solid transparent;

        }


        .admin-alert.success {

            background: #ecfdf5;

            color: #047857;

            border-color: #a7f3d0;

        }


        .admin-alert.error {

            background: #fef2f2;

            color: #b91c1c;

            border-color: #fecaca;

        }


        /* =========================================================
           STATISTICS
        ========================================================= */

        .admin-stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 24px;

        }


        .stat-card {

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    #f4f8ff
                );

            border:
                1px solid #dbeafe;

            border-radius: 22px;

            padding:
                24px 22px;

            box-shadow:
                0 15px 35px
                rgba(
                    30,
                    64,
                    175,
                    0.08
                );

            transition:
                transform .25s ease,
                box-shadow .25s ease;

        }


        .stat-card::before {

            content: '';

            position: absolute;

            width: 90px;

            height: 90px;

            right: -35px;

            top: -35px;

            border-radius: 50%;

            background:
                rgba(
                    37,
                    99,
                    235,
                    0.08
                );

        }


        .stat-card:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 22px 45px
                rgba(
                    30,
                    64,
                    175,
                    0.13
                );

        }


        .stat-label {

            display: block;

            position: relative;

            color: #64748b;

            font-size: 12px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: .7px;

            margin-bottom: 8px;

        }


        .stat-card strong {

            position: relative;

            display: block;

            color: #0f2f87;

            font-size: 31px;

            line-height: 1;

            font-weight: 800;

        }


        /* =========================================================
           PANELS
        ========================================================= */

        .admin-panel {

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 24px;

            padding: 26px;

            margin-bottom: 24px;

            box-shadow:
                0 15px 40px
                rgba(
                    15,
                    23,
                    42,
                    0.06
                );

        }


        /* =========================================================
           FILTER
        ========================================================= */

        .admin-filter-form {

            display: grid;

            grid-template-columns:
                minmax(260px, 1.7fr)
                minmax(150px, .8fr)
                minmax(170px, .9fr)
                auto
                auto;

            gap: 12px;

            align-items: center;

        }


        .admin-filter-form input,
        .admin-filter-form select {

            width: 100%;

            min-height: 48px;

            border:
                1.5px solid #dbeafe;

            border-radius: 13px;

            padding:
                0 15px;

            background: #f8fbff;

            color: #1e293b;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 13px;

            font-weight: 500;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;

        }


        .admin-filter-form input:focus,
        .admin-filter-form select:focus {

            background: #ffffff;

            border-color: #3b82f6;

            box-shadow:
                0 0 0 4px
                rgba(
                    59,
                    130,
                    246,
                    .10
                );

        }


        .admin-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 46px;

            padding:
                0 20px;

            border-radius: 12px;

            border: none;

            text-decoration: none;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;

        }


        .admin-btn:hover {

            transform:
                translateY(-2px);

        }


        .admin-btn.primary {

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #1455c0,
                    #1877f2,
                    #229cff
                );

            box-shadow:
                0 10px 22px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .admin-btn.primary:hover {

            box-shadow:
                0 14px 28px
                rgba(
                    37,
                    99,
                    235,
                    .30
                );

        }


        .admin-btn.secondary {

            color: #1d4ed8;

            background: #eff6ff;

            border:
                1px solid #dbeafe;

        }


        /* =========================================================
           PANEL HEADER
        ========================================================= */

        .panel-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 20px;

        }


        .panel-header h2 {

            margin: 0;

            color: #0f172a;

            font-size: 21px;

            font-weight: 800;

        }


        .panel-header p {

            margin:
                5px 0 0;

            color: #64748b;

            font-size: 13px;

            font-weight: 500;

        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

            border:
                1px solid #e2e8f0;

            border-radius: 18px;

        }


        .admin-table {

            width: 100%;

            min-width: 1050px;

            border-collapse: separate;

            border-spacing: 0;

            font-family:
                'Poppins',
                sans-serif;

        }


        .admin-table thead th {

            padding:
                16px 15px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fbff
                );

            color: #64748b;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .7px;

            white-space: nowrap;

            border-bottom:
                1px solid #dbeafe;

        }


        .admin-table tbody td {

            padding:
                17px 15px;

            color: #334155;

            font-size: 13px;

            font-weight: 500;

            border-bottom:
                1px solid #eef2f7;

            vertical-align: middle;

        }


        .admin-table tbody tr {

            transition:
                background .2s ease;

        }


        .admin-table tbody tr:hover {

            background:
                #f8fbff;

        }


        .admin-table tbody tr:last-child td {

            border-bottom: none;

        }


        .admin-table strong {

            color: #172554;

            font-weight: 700;

        }


        .admin-table small {

            display: block;

            margin-top: 4px;

            max-width: 180px;

            color: #94a3b8;

            font-size: 11px;

            line-height: 1.5;

        }


        /* =========================================================
           PRODUCT INFO
        ========================================================= */

        .product-table-info {

            display: flex;

            align-items: center;

            gap: 12px;

            min-width: 230px;

        }


        .table-product-image {

            width: 52px;

            height: 52px;

            flex-shrink: 0;

            object-fit: cover;

            border-radius: 14px;

            border:
                1px solid #dbeafe;

            background: #eff6ff;

            box-shadow:
                0 5px 14px
                rgba(
                    37,
                    99,
                    235,
                    .10
                );

        }


        .product-table-info strong {

            display: block;

            max-width: 190px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status-select {

            min-width: 120px;

            min-height: 38px;

            padding:
                0 10px;

            border:
                1px solid #dbeafe;

            border-radius: 10px;

            background: #f8fbff;

            color: #1e3a8a;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 11px;

            font-weight: 700;

            outline: none;

            cursor: pointer;

        }


        .status-select:focus {

            border-color:
                #3b82f6;

            box-shadow:
                0 0 0 3px
                rgba(
                    59,
                    130,
                    246,
                    .10
                );

        }


        /* =========================================================
           ACTION BUTTONS
        ========================================================= */

        .table-actions {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .admin-btn.small {

            min-height: 35px;

            padding:
                0 13px;

            border-radius: 9px;

            background: #eff6ff;

            color: #1d4ed8;

            border:
                1px solid #dbeafe;

            font-size: 11px;

        }


        .admin-btn.small:hover {

            background: #dbeafe;

        }


        .admin-btn.small.danger {

            color: #dc2626;

            background: #fef2f2;

            border-color: #fecaca;

        }


        .admin-btn.small.danger:hover {

            background: #fee2e2;

        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {

            padding:
                60px 20px !important;

            text-align: center;

            color: #94a3b8 !important;

            font-size: 14px !important;

            font-weight: 600 !important;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .admin-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .admin-filter-form {

                grid-template-columns:
                    1fr 1fr;

            }

        }


        @media (max-width: 700px) {

            .admin-main {

                padding:
                    20px 15px 40px;

            }


            .admin-topbar {

                padding:
                    22px;

            }


            .admin-topbar h1 {

                font-size: 27px;

            }


            .admin-stats {

                grid-template-columns:
                    1fr;

            }


            .admin-filter-form {

                grid-template-columns:
                    1fr;

            }


            .admin-panel {

                padding: 18px;

                border-radius: 18px;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php
    require_once
        dirname(__DIR__)
        . '/includes/admin_sidebar.php';
    ?>


    <main class="admin-main">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <header class="admin-topbar">

            <div class="admin-header-left">

                <div>

                    <h1>
                        Products
                    </h1>

                    <p>
                        Manage all products listed by
                        HochipoHub vendors.
                    </p>

                </div>

            </div>

        </header>


        <!-- =====================================================
             SUCCESS MESSAGE
        ====================================================== -->

        <?php if (isset($_GET['success'])): ?>

            <div class="admin-alert success">

                <?php if (
                    $_GET['success'] === 'deleted'
                ): ?>

                    Product deleted successfully.

                <?php else: ?>

                    Product status updated successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if (isset($_GET['error'])): ?>

            <div class="admin-alert error">

                <?php if (
                    $_GET['error'] === 'order'
                ): ?>

                    This product cannot be deleted because
                    it is already connected to an existing order.

                <?php elseif (
                    $_GET['error'] === 'notfound'
                ): ?>

                    Product not found.

                <?php else: ?>

                    Unable to process the request.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <section class="admin-stats">


            <div class="stat-card">

                <span class="stat-label">
                    Total Products
                </span>

                <strong>
                    <?= number_format($total_products) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Available
                </span>

                <strong>
                    <?= number_format($available_products) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Out of Stock
                </span>

                <strong>
                    <?= number_format($out_of_stock) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Hidden
                </span>

                <strong>
                    <?= number_format($hidden_products) ?>
                </strong>

            </div>


        </section>


        <!-- =====================================================
             FILTER
        ====================================================== -->

        <section class="admin-panel">


            <form
                method="GET"
                class="admin-filter-form"
            >


                <input
                    type="text"
                    name="search"
                    placeholder="Search product or vendor..."
                    value="<?= e($search) ?>"
                >


                <select name="status">

                    <option value="">
                        All Status
                    </option>


                    <?php foreach (
                        [
                            'Available',
                            'Out of Stock',
                            'Hidden'
                        ] as $s
                    ): ?>

                        <option
                            value="<?= e($s) ?>"
                            <?= $status_filter === $s
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= e($s) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <select name="category">

                    <option value="">
                        All Categories
                    </option>


                    <?php foreach (
                        $categories
                        as $c
                    ): ?>

                        <option
                            value="<?= (int) $c['category_id'] ?>"
                            <?= $category_filter ==
                                (int) $c['category_id']
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= e(
                                $c['category_name']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    class="admin-btn primary"
                    type="submit"
                >
                    Search
                </button>


                <a
                    class="admin-btn secondary"
                    href="products.php"
                >
                    Reset
                </a>


            </form>


        </section>


        <!-- =====================================================
             PRODUCT LIST
        ====================================================== -->

        <section class="admin-panel">


            <div class="panel-header">

                <div>

                    <h2>
                        Product List
                    </h2>

                    <p>

                        <?= count($products) ?>

                        product(s) found

                    </p>

                </div>

            </div>


            <div class="table-wrapper">


                <table class="admin-table">


                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                Vendor
                            </th>

                            <th>
                                Category
                            </th>

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
                                Created
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (!$products): ?>


                        <tr>

                            <td
                                colspan="9"
                                class="empty-state"
                            >

                                No products found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $products
                            as $p
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <strong>
                                        #<?= (int)
                                            $p['product_id']
                                        ?>
                                    </strong>

                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <div
                                        class="product-table-info"
                                    >


                                        <?php

                                        $image =
                                            trim(
                                                $p['image']
                                                ?? ''
                                            );

                                        ?>


                                        <?php if (
                                            $image !== ''
                                        ): ?>

                                            <img
                                                class="table-product-image"
                                                src="../uploads/products/<?= e(
                                                    basename($image)
                                                ) ?>"
                                                alt="<?= e(
                                                    $p['product_name']
                                                ) ?>"
                                                onerror="
                                                    this.style.display='none';
                                                "
                                            >

                                        <?php endif; ?>


                                        <div>

                                            <strong>

                                                <?= e(
                                                    $p['product_name']
                                                ) ?>

                                            </strong>


                                            <?php if (
                                                !empty(
                                                    $p['description']
                                                )
                                            ): ?>

                                                <small>

                                                    <?= e(
                                                        $p['description']
                                                    ) ?>

                                                </small>

                                            <?php endif; ?>


                                        </div>


                                    </div>

                                </td>


                                <!-- VENDOR -->

                                <td>

                                    <strong>

                                        <?= e(
                                            $p['business_name']
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= e(
                                            $p['vendor_name']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= e(
                                        $p['category_name']
                                    ) ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $p['price'],
                                            2
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- STOCK -->

                                <td>

                                    <?= (int)
                                        $p['stock_quantity']
                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <form
                                        method="POST"
                                        class="inline-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)
                                                $p['product_id']
                                            ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="update_status"
                                            value="1"
                                        >


                                        <select
                                            class="status-select"
                                            name="status"
                                            onchange="
                                                this.form.submit()
                                            "
                                        >


                                            <?php foreach (
                                                [
                                                    'Available',
                                                    'Out of Stock',
                                                    'Hidden'
                                                ] as $s
                                            ): ?>


                                                <option
                                                    value="<?= e($s) ?>"
                                                    <?= $p['status'] === $s
                                                        ? 'selected'
                                                        : ''
                                                    ?>
                                                >

                                                    <?= e($s) ?>

                                                </option>


                                            <?php endforeach; ?>


                                        </select>


                                    </form>


                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= e(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $p['created_at']
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>


                                    <div
                                        class="table-actions"
                                    >


                                        <a
                                            class="admin-btn small"
                                            href="../product_details.php?id=<?= (int)
                                                $p['product_id']
                                            ?>"
                                            target="_blank"
                                        >
                                            View
                                        </a>


                                        <a
                                            class="admin-btn small danger"
                                            href="products.php?delete=<?= (int)
                                                $p['product_id']
                                            ?>"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to delete this product?'
                                                );
                                            "
                                        >
                                            Delete
                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>


    </main>


</div>


</body>

</html>