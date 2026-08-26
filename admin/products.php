<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN PRODUCTS
|--------------------------------------------------------------------------
| File: admin/products.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';


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
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| ESCAPE HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e($value): string
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


$adminId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';


if (isset($_GET['success'])) {

    if ($_GET['success'] === 'deleted') {

        $message =
            'Product deleted successfully.';

        $messageType =
            'success';
    }

    elseif ($_GET['success'] === 'status') {

        $message =
            'Product status updated successfully.';

        $messageType =
            'success';
    }
}


if (isset($_GET['error'])) {

    $messageType =
        'error';


    switch ($_GET['error']) {

        case 'order':

            $message =
                'This product cannot be deleted because it is already connected to an existing order.';

            break;


        case 'notfound':

            $message =
                'Product not found.';

            break;


        case 'security':

            $message =
                'Invalid security token. Please refresh the page and try again.';

            break;


        case 'invalid':

            $message =
                'Invalid product information.';

            break;


        default:

            $message =
                'Unable to process the request.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| POST ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: products.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT STATUS
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['update_status'])) {

        $productId =
            (int) (
                $_POST['product_id']
                ?? 0
            );


        $status =
            $_POST['status']
            ?? '';


        $allowedStatuses = [
            'Available',
            'Out of Stock',
            'Hidden'
        ];


        if (
            $productId <= 0 ||
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            header(
                'Location: products.php?error=invalid'
            );

            exit;
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK PRODUCT
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT product_id
                    FROM products
                    WHERE product_id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $productId
            ]);


            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

                header(
                    'Location: products.php?error=notfound'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    UPDATE products
                    SET status = ?
                    WHERE product_id = ?
                ");


            $stmt->execute([
                $status,
                $productId
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    INSERT INTO admin_logs
                    (
                        admin_id,
                        action,
                        target_type,
                        target_id
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


            $stmt->execute([

                $adminId,

                'Updated product status to ' .
                $status,

                'product',

                $productId

            ]);


            header(
                'Location: products.php?success=status'
            );

            exit;

        }

        catch (Throwable $e) {

            error_log(
                $e->getMessage()
            );


            header(
                'Location: products.php?error=update'
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['delete_product'])) {

        $productId =
            (int) (
                $_POST['product_id']
                ?? 0
            );


        if ($productId <= 0) {

            header(
                'Location: products.php?error=invalid'
            );

            exit;
        }


        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | CHECK PRODUCT
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT
                        product_id,
                        product_name
                    FROM products
                    WHERE product_id = ?
                    LIMIT 1
                    FOR UPDATE
                ");


            $stmt->execute([
                $productId
            ]);


            $existingProduct =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$existingProduct) {

                $db->rollBack();


                header(
                    'Location: products.php?error=notfound'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK ORDER DETAILS
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT COUNT(*)
                    FROM order_details
                    WHERE product_id = ?
                ");


            $stmt->execute([
                $productId
            ]);


            $orderCount =
                (int)
                $stmt->fetchColumn();


            if ($orderCount > 0) {

                $db->rollBack();


                header(
                    'Location: products.php?error=order'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | REMOVE RELATED RECORDS
            |--------------------------------------------------------------------------
            */

            $relatedTables = [

                'wishlist',
                'cart',
                'reviews',
                'inventory'

            ];


            foreach ($relatedTables as $table) {

                $stmt =
                    $db->prepare(
                        "DELETE FROM {$table}
                         WHERE product_id = ?"
                    );


                $stmt->execute([
                    $productId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE PRODUCT
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    DELETE FROM products
                    WHERE product_id = ?
                ");


            $stmt->execute([
                $productId
            ]);


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    INSERT INTO admin_logs
                    (
                        admin_id,
                        action,
                        target_type,
                        target_id
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


            $stmt->execute([

                $adminId,

                'Deleted product: ' .
                $existingProduct['product_name'],

                'product',

                $productId

            ]);


            $db->commit();


            header(
                'Location: products.php?success=deleted'
            );

            exit;

        }

        catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }


            error_log(
                $e->getMessage()
            );


            header(
                'Location: products.php?error=delete'
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


$statusFilter =
    $_GET['status']
    ?? '';


$categoryFilter =
    (int) (
        $_GET['category']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];


try {

    $stmt =
        $db->query("
            SELECT
                category_id,
                category_name
            FROM categories
            ORDER BY category_name ASC
        ");


    $categories =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $categories = [];

    error_log(
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| PRODUCTS QUERY
|--------------------------------------------------------------------------
*/

$products = [];


try {

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

            c.category_id,
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
            AND
            (
                p.product_name LIKE ?
                OR v.business_name LIKE ?
                OR u.name LIKE ?
                OR c.category_name LIKE ?
            )
        ";


        $searchValue =
            '%' .
            $search .
            '%';


        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $statusFilter,
            [
                'Available',
                'Out of Stock',
                'Hidden'
            ],
            true
        )
    ) {

        $sql .= "
            AND p.status = ?
        ";


        $params[] =
            $statusFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY FILTER
    |--------------------------------------------------------------------------
    */

    if ($categoryFilter > 0) {

        $sql .= "
            AND p.category_id = ?
        ";


        $params[] =
            $categoryFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY
            p.created_at DESC,
            p.product_id DESC
    ";


    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (Throwable $e) {

    $products = [];

    error_log(
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalProducts = 0;
$availableProducts = 0;
$outOfStockProducts = 0;
$hiddenProducts = 0;


try {

    $totalProducts =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM products
            ")
            ->fetchColumn();


    $availableProducts =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM products
                WHERE status = 'Available'
            ")
            ->fetchColumn();


    $outOfStockProducts =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM products
                WHERE status = 'Out of Stock'
            ")
            ->fetchColumn();


    $hiddenProducts =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM products
                WHERE status = 'Hidden'
            ")
            ->fetchColumn();

}

catch (Throwable $e) {

    error_log(
        $e->getMessage()
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
        Products | HochipoHub Admin
    </title>


    <!-- POPPINS -->

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


    <!-- ADMIN CSS -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | ROOT
        |--------------------------------------------------------------------------
        */

        :root {

            --product-sidebar-width: 260px;

            --product-blue:
                #2563eb;

            --product-navy:
                #08265a;

            --product-background:
                #eef5fd;

            --product-border:
                #dce7f3;

            --product-text:
                #0b2d63;

            --product-muted:
                #8294b3;

        }


        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {

            box-sizing: border-box;

        }


        html,
        body {

            margin: 0;

            padding: 0;

            min-height: 100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                #eef5fd;

        }


        body {

            overflow-x: hidden;

        }


        button,
        input,
        select {

            font-family:
                inherit;

        }


        /*
        |--------------------------------------------------------------------------
        | FORCE FONT ON SIDEBAR
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .products-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --product-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --product-sidebar-width
                    )
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .products-content {

            width:
                100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                38px
                35px
                70px;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .products-hero {

            position:
                relative;

            min-height:
                155px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                34px
                38px;

            margin-bottom:
                26px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius:
                26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .products-hero::before {

            content:
                "";

            position:
                absolute;

            width:
                260px;

            height:
                260px;

            right:
                -70px;

            top:
                -140px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .products-hero::after {

            content:
                "";

            position:
                absolute;

            width:
                170px;

            height:
                170px;

            right:
                155px;

            bottom:
                -110px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .products-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .products-hero h1 {

            margin:
                0 0 8px;

            color:
                #ffffff;

            font-size:
                38px;

            line-height:
                1.05;

            font-weight:
                800;

            letter-spacing:
                -1.5px;

        }


        .products-hero p {

            margin:
                0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size:
                14px;

            font-weight:
                500;

        }


        /*
        |--------------------------------------------------------------------------
        | HERO ICON
        |--------------------------------------------------------------------------
        */

        .products-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                82px;

            height:
                82px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .25
                );

            border-radius:
                22px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .14
                );

            color:
                #ffffff;

            font-size:
                34px;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .products-alert {

            margin-bottom:
                22px;

            padding:
                14px
                17px;

            border-radius:
                12px;

            font-size:
                11px;

            font-weight:
                600;

        }


        .products-alert.success {

            color:
                #166534;

            background:
                #ecfdf5;

            border:
                1px solid
                #bbf7d0;

        }


        .products-alert.error {

            color:
                #991b1b;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .products-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                18px;

            margin-bottom:
                30px;

        }


        .product-stat {

            position:
                relative;

            min-height:
                150px;

            overflow:
                hidden;

            padding:
                26px
                24px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --product-border
                );

            border-top:
                4px solid
                #2563eb;

            border-radius:
                20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .product-stat::after {

            content:
                "";

            position:
                absolute;

            right:
                -29px;

            bottom:
                -45px;

            width:
                110px;

            height:
                110px;

            border-radius:
                50%;

            background:
                #edf4ff;

        }


        .product-stat.available {

            border-top-color:
                #16a34a;

        }


        .product-stat.available::after {

            background:
                #eaf9ef;

        }


        .product-stat.out {

            border-top-color:
                #f59e0b;

        }


        .product-stat.out::after {

            background:
                #fff7df;

        }


        .product-stat.hidden {

            border-top-color:
                #8b5cf6;

        }


        .product-stat.hidden::after {

            background:
                #f4efff;

        }


        .product-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                15px;

            color:
                #61728e;

            font-size:
                10px;

            font-weight:
                800;

            letter-spacing:
                .75px;

            text-transform:
                uppercase;

        }


        .product-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #0b326d;

            font-size:
                32px;

            line-height:
                1;

            font-weight:
                800;

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .products-panel {

            overflow:
                hidden;

            margin-bottom:
                28px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --product-border
                );

            border-radius:
                24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .products-panel-header {

            min-height:
                110px;

            padding:
                26px
                30px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid
                #e7edf5;

        }


        .products-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .products-panel-icon {

            width:
                53px;

            height:
                53px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                16px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size:
                22px;

            font-weight:
                800;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .products-panel-header h2 {

            margin:
                0 0 5px;

            color:
                #092e65;

            font-size:
                20px;

            font-weight:
                800;

        }


        .products-panel-header p {

            margin:
                0;

            color:
                #8999b4;

            font-size:
                11px;

        }


        /*
        |--------------------------------------------------------------------------
        | COUNT
        |--------------------------------------------------------------------------
        */

        .products-count {

            min-height:
                36px;

            padding:
                0 16px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #d6e7ff;

            border-radius:
                999px;

            font-size:
                10px;

            font-weight:
                800;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .products-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .products-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1.6fr
                )

                minmax(
                    140px,
                    .55fr
                )

                minmax(
                    160px,
                    .65fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .products-filter input,
        .products-filter select {

            width:
                100%;

            height:
                43px;

            padding:
                0 13px;

            outline:
                none;

            color:
                #26354e;

            background:
                #ffffff;

            border:
                1px solid
                #d8e3ef;

            border-radius:
                10px;

            font-size:
                10px;

        }


        .products-filter input::placeholder {

            color:
                #96a5b9;

        }


        .products-filter input:focus,
        .products-filter select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .product-btn {

            min-height:
                43px;

            padding:
                0 17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .product-btn.primary {

            color:
                #ffffff;

            border:
                0;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            box-shadow:

                0
                7px
                15px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

        }


        .product-btn.secondary {

            color:
                #66758b;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ee;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .products-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .products-table {

            width:
                100%;

            min-width:
                1120px;

            border-collapse:
                collapse;

        }


        .products-table thead {

            background:
                #f6f9fd;

        }


        .products-table th {

            height:
                44px;

            padding:
                0 16px;

            color:
                #65758f;

            border-bottom:
                1px solid
                #dfe7f0;

            font-size:
                8px;

            font-weight:
                800;

            text-align:
                left;

            letter-spacing:
                .55px;

            text-transform:
                uppercase;

            white-space:
                nowrap;

        }


        .products-table td {

            padding:
                16px;

            color:
                #435169;

            border-bottom:
                1px solid
                #edf1f6;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .products-table tbody tr:hover {

            background:
                #f9fbff;

        }


        .products-table tbody tr:last-child td {

            border-bottom:
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT INFO
        |--------------------------------------------------------------------------
        */

        .product-info {

            display:
                flex;

            align-items:
                center;

            gap:
                11px;

            min-width:
                220px;

        }


        .product-image {

            width:
                46px;

            height:
                46px;

            flex-shrink:
                0;

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
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                11px;

            font-size:
                18px;

        }


        .product-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        .product-info strong,
        .vendor-info strong {

            display:
                block;

            margin-bottom:
                3px;

            color:
                #112b55;

            font-size:
                10px;

            font-weight:
                800;

        }


        .product-info small,
        .vendor-info small {

            display:
                block;

            max-width:
                190px;

            overflow:
                hidden;

            color:
                #8897ac;

            font-size:
                8px;

            text-overflow:
                ellipsis;

            white-space:
                nowrap;

        }


        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        .product-category {

            min-height:
                27px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            color:
                #52647f;

            background:
                #f1f5f9;

            border:
                1px solid
                #e2e8f0;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                700;

        }


        .stock-badge {

            min-height:
                27px;

            padding:
                0 9px;

            display:
                inline-flex;

            align-items:
                center;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        .stock-badge.good {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .stock-badge.low {

            color:
                #a16207;

            background:
                #fffbea;

        }


        .stock-badge.out {

            color:
                #b91c1c;

            background:
                #fff1f2;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS SELECT
        |--------------------------------------------------------------------------
        */

        .product-status-select {

            min-width:
                115px;

            height:
                34px;

            padding:
                0 10px;

            outline:
                none;

            color:
                #1e3a8a;

            background:
                #f8fbff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                9px;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .product-status-select:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .10
                );

        }


        /*
        |--------------------------------------------------------------------------
        | ACTION
        |--------------------------------------------------------------------------
        */

        .product-actions {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

        }


        .product-actions form {

            margin:
                0;

        }


        .action-btn {

            min-height:
                32px;

            padding:
                0 11px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                8px;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

        }


        .action-btn.view {

            color:
                #1d4ed8;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

        }


        .action-btn.delete {

            color:
                #b91c1c;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .products-empty {

            padding:
                70px
                20px !important;

            color:
                #94a3b8 !important;

            text-align:
                center;

        }


        .products-empty strong {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #49617f;

            font-size:
                11px;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (
            max-width: 1200px
        ) {

            .products-stats {

                grid-template-columns:

                    repeat(
                        2,
                        1fr
                    );

            }


            .products-filter {

                grid-template-columns:

                    1fr
                    1fr;

            }


            .products-filter input {

                grid-column:
                    1 / -1;

            }

        }


        @media (
            max-width: 900px
        ) {

            :root {

                --product-sidebar-width:
                    0px;

            }


            .products-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .products-content {

                padding:
                    25px
                    20px
                    50px;

            }


            .products-hero {

                min-height:
                    140px;

                padding:
                    28px;

            }


            .products-hero h1 {

                font-size:
                    31px;

            }


            .products-hero-icon {

                width:
                    67px;

                height:
                    67px;

            }

        }


        @media (
            max-width: 650px
        ) {

            .products-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .products-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .products-hero h1 {

                font-size:
                    27px;

            }


            .products-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .products-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .products-stats {

                grid-template-columns:
                    1fr;

                gap:
                    12px;

            }


            .product-stat {

                min-height:
                    120px;

            }


            .products-panel-header {

                padding:
                    20px
                    17px;

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }


            .products-filter {

                grid-template-columns:
                    1fr;

            }


            .products-filter input {

                grid-column:
                    auto;

            }


            .product-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    /*
    |--------------------------------------------------------------------------
    | ADMIN SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="products-main">


        <div class="products-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="products-hero">


                <div class="products-hero-text">

                    <h1>
                        Products
                    </h1>

                    <p>
                        Monitor and manage all products listed by HochipoHub vendors.
                    </p>

                </div>


                <div class="products-hero-icon">
                    ◇
                </div>


            </section>


            <!-- =====================================================
                 MESSAGE
            ====================================================== -->

            <?php if ($message !== ''): ?>


                <div
                    class="
                        products-alert
                        <?= e($messageType) ?>
                    "
                >

                    <?= e($message) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="products-stats">


                <div class="product-stat">

                    <span class="product-stat-label">
                        Total Products
                    </span>


                    <strong class="product-stat-value">

                        <?= number_format(
                            $totalProducts
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        product-stat
                        available
                    "
                >

                    <span class="product-stat-label">
                        Available
                    </span>


                    <strong class="product-stat-value">

                        <?= number_format(
                            $availableProducts
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        product-stat
                        out
                    "
                >

                    <span class="product-stat-label">
                        Out of Stock
                    </span>


                    <strong class="product-stat-value">

                        <?= number_format(
                            $outOfStockProducts
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        product-stat
                        hidden
                    "
                >

                    <span class="product-stat-label">
                        Hidden
                    </span>


                    <strong class="product-stat-value">

                        <?= number_format(
                            $hiddenProducts
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 PRODUCT PANEL
            ====================================================== -->

            <section class="products-panel">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="products-panel-header">


                    <div class="products-panel-title">


                        <div class="products-panel-icon">
                            ◇
                        </div>


                        <div>

                            <h2>
                                Product Management
                            </h2>

                            <p>
                                Search, filter and manage marketplace products.
                            </p>

                        </div>


                    </div>


                    <span class="products-count">

                        <?= number_format(
                            count(
                                $products
                            )
                        ) ?>

                        products

                    </span>


                </div>


                <!-- =================================================
                     FILTER
                ================================================== -->

                <div class="products-filter-wrapper">


                    <form
                        method="GET"
                        action="products.php"
                        class="products-filter"
                    >


                        <!-- SEARCH -->

                        <input
                            type="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search product, vendor or category..."
                            autocomplete="off"
                        >


                        <!-- STATUS -->

                        <select
                            name="status"
                            aria-label="Filter product status"
                        >

                            <option value="">
                                All Status
                            </option>


                            <?php foreach (
                                [
                                    'Available',
                                    'Out of Stock',
                                    'Hidden'
                                ]
                                as $status
                            ): ?>


                                <option
                                    value="<?= e(
                                        $status
                                    ) ?>"
                                    <?= $statusFilter === $status
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= e($status) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- CATEGORY -->

                        <select
                            name="category"
                            aria-label="Filter category"
                        >

                            <option value="0">
                                All Categories
                            </option>


                            <?php foreach ($categories as $category): ?>


                                <option
                                    value="<?= (int)
                                        $category[
                                            'category_id'
                                        ] ?>"
                                    <?= $categoryFilter ===
                                        (int)
                                        $category[
                                            'category_id'
                                        ]
                                            ? 'selected'
                                            : '' ?>
                                >

                                    <?= e(
                                        $category[
                                            'category_name'
                                        ]
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <!-- SEARCH -->

                        <button
                            type="submit"
                            class="
                                product-btn
                                primary
                            "
                        >

                            Search

                        </button>


                        <!-- RESET -->

                        <a
                            href="products.php"
                            class="
                                product-btn
                                secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="products-table-wrapper">


                    <table class="products-table">


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


                            <?php if (empty($products)): ?>


                                <tr>

                                    <td
                                        colspan="9"
                                        class="products-empty"
                                    >

                                        <strong>
                                            No products found
                                        </strong>

                                        Try another search keyword or filter.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($products as $product): ?>


                                    <?php

                                    $productId =
                                        (int)
                                        $product[
                                            'product_id'
                                        ];


                                    $image =
                                        trim(
                                            $product['image']
                                            ?? ''
                                        );


                                    $stock =
                                        (int)
                                        $product[
                                            'stock_quantity'
                                        ];


                                    if ($stock <= 0) {

                                        $stockClass =
                                            'out';
                                    }

                                    elseif ($stock <= 10) {

                                        $stockClass =
                                            'low';
                                    }

                                    else {

                                        $stockClass =
                                            'good';
                                    }

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <strong>
                                                #<?= $productId ?>
                                            </strong>

                                        </td>


                                        <!-- PRODUCT -->

                                        <td>


                                            <div class="product-info">


                                                <div class="product-image">


                                                    <?php if ($image !== ''): ?>


                                                        <img
                                                            src="<?= e(
                                                                '../uploads/products/' .
                                                                rawurlencode(
                                                                    basename(
                                                                        $image
                                                                    )
                                                                )
                                                            ) ?>"
                                                            alt="<?= e(
                                                                $product[
                                                                    'product_name'
                                                                ]
                                                            ) ?>"
                                                            onerror="
                                                                this.style.display='none';
                                                                this.parentElement.innerHTML='◇';
                                                            "
                                                        >


                                                    <?php else: ?>


                                                        ◇


                                                    <?php endif; ?>


                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= e(
                                                            $product[
                                                                'product_name'
                                                            ]
                                                        ) ?>

                                                    </strong>


                                                    <?php if (
                                                        !empty(
                                                            $product[
                                                                'description'
                                                            ]
                                                        )
                                                    ): ?>


                                                        <small
                                                            title="<?= e(
                                                                $product[
                                                                    'description'
                                                                ]
                                                            ) ?>"
                                                        >

                                                            <?= e(
                                                                $product[
                                                                    'description'
                                                                ]
                                                            ) ?>

                                                        </small>


                                                    <?php else: ?>


                                                        <small>
                                                            No description
                                                        </small>


                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                        </td>


                                        <!-- VENDOR -->

                                        <td>


                                            <div class="vendor-info">

                                                <strong>

                                                    <?= e(
                                                        $product[
                                                            'business_name'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <small>

                                                    <?= e(
                                                        $product[
                                                            'vendor_name'
                                                        ]
                                                    ) ?>

                                                </small>

                                            </div>


                                        </td>


                                        <!-- CATEGORY -->

                                        <td>

                                            <span class="product-category">

                                                <?= e(
                                                    $product[
                                                        'category_name'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- PRICE -->

                                        <td>

                                            <strong>

                                                RM
                                                <?= number_format(
                                                    (float)
                                                    $product[
                                                        'price'
                                                    ],
                                                    2
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- STOCK -->

                                        <td>

                                            <span
                                                class="
                                                    stock-badge
                                                    <?= e(
                                                        $stockClass
                                                    ) ?>
                                                "
                                            >

                                                <?= number_format(
                                                    $stock
                                                ) ?>

                                                units

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <form
                                                method="POST"
                                                action="products.php"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="update_status"
                                                    value="1"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="product_id"
                                                    value="<?= $productId ?>"
                                                >


                                                <select
                                                    name="status"
                                                    class="product-status-select"
                                                    onchange="
                                                        if (
                                                            confirm(
                                                                'Change product status to ' +
                                                                this.value +
                                                                '?'
                                                            )
                                                        ) {
                                                            this.form.submit();
                                                        } else {
                                                            window.location.reload();
                                                        }
                                                    "
                                                >


                                                    <?php foreach (
                                                        [
                                                            'Available',
                                                            'Out of Stock',
                                                            'Hidden'
                                                        ]
                                                        as $status
                                                    ): ?>


                                                        <option
                                                            value="<?= e(
                                                                $status
                                                            ) ?>"
                                                            <?= $product[
                                                                'status'
                                                            ] === $status
                                                                ? 'selected'
                                                                : '' ?>
                                                        >

                                                            <?= e(
                                                                $status
                                                            ) ?>

                                                        </option>


                                                    <?php endforeach; ?>


                                                </select>


                                            </form>


                                        </td>


                                        <!-- CREATED -->

                                        <td>

                                            <?= !empty(
                                                $product[
                                                    'created_at'
                                                ]
                                            )
                                                ? e(
                                                    date(
                                                        'd M Y',
                                                        strtotime(
                                                            $product[
                                                                'created_at'
                                                            ]
                                                        )
                                                    )
                                                )
                                                : '-' ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>


                                            <div class="product-actions">


                                                <!-- VIEW -->

                                                <a
                                                    href="../product_details.php?id=<?= $productId ?>"
                                                    target="_blank"
                                                    class="
                                                        action-btn
                                                        view
                                                    "
                                                >

                                                    View

                                                </a>


                                                <!-- DELETE -->

                                                <form
                                                    method="POST"
                                                    action="products.php"
                                                    onsubmit="
                                                        return confirm(
                                                            'Are you sure you want to delete this product?'
                                                        );
                                                    "
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
                                                            $csrfToken
                                                        ) ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="delete_product"
                                                        value="1"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= $productId ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="
                                                            action-btn
                                                            delete
                                                        "
                                                    >

                                                        Delete

                                                    </button>


                                                </form>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


        </div>


    </main>


</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH SYNC
    |--------------------------------------------------------------------------
    |
    | Prevent content from going behind admin sidebar.
    |
    |--------------------------------------------------------------------------
    */

    function syncProductsSidebar() {

        const main =
            document.querySelector(
                '.products-main'
            );


        if (!main) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            document.documentElement.style.setProperty(
                '--product-sidebar-width',
                '0px'
            );


            main.style.marginLeft =
                '0px';


            main.style.width =
                '100%';


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FIND SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        if (!sidebar) {

            document.documentElement.style.setProperty(
                '--product-sidebar-width',
                '260px'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | REAL SIDEBAR WIDTH
        |--------------------------------------------------------------------------
        */

        const rect =
            sidebar.getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement.style.setProperty(
                '--product-sidebar-width',
                rect.right + 'px'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncProductsSidebar();


            setTimeout(
                syncProductsSidebar,
                100
            );


            setTimeout(
                syncProductsSidebar,
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncProductsSidebar
    );

</script>


</body>

</html>