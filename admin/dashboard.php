<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ' .
        site_url('index.php?login=required')
    );

    exit;
}


$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        profile_image,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result =
    $stmt->get_result();

$user =
    $result->fetch_assoc();

$stmt->close();


if (!$user) {

    session_destroy();

    header(
        'Location: ' .
        site_url('index.php')
    );

    exit;
}


if ($user['role'] !== 'admin') {

    header(
        'Location: ' .
        site_url('dashboard.php')
    );

    exit;
}


if ($user['status'] !== 'active') {

    session_destroy();

    header(
        'Location: ' .
        site_url(
            'index.php?account=inactive'
        )
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DEFAULT STATS
|--------------------------------------------------------------------------
*/

$stats = [

    'users' => 0,

    'customers' => 0,

    'vendors' => 0,

    'pending_vendors' => 0,

    'products' => 0,

    'orders' => 0,

    'pending_orders' => 0,

    'sales' => 0,

    'commission' => 0

];


$recentOrders = [];

$recentProducts = [];

$recentVendors = [];

$recentUsers = [];


/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");


if ($result) {

    $stats['users'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| CUSTOMERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");


if ($result) {

    $stats['customers'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| APPROVED VENDORS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM vendors
    WHERE approval_status = 'Approved'
");


if ($result) {

    $stats['vendors'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| PENDING VENDORS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM vendor_applications
    WHERE status = 'Pending'
");


if ($result) {

    $stats['pending_vendors'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");


if ($result) {

    $stats['products'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");


if ($result) {

    $stats['orders'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| PENDING ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Pending'
");


if ($result) {

    $stats['pending_orders'] =
        (int)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN order_status != 'Cancelled'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total
    FROM orders
");


if ($result) {

    $stats['sales'] =
        (float)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| TOTAL COMMISSION
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN status != 'Cancelled'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS total
    FROM commission
");


if ($result) {

    $stats['commission'] =
        (float)
        (
            $result
                ->fetch_assoc()['total']
            ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT

        o.order_id,
        o.order_date,
        o.total_amount,
        o.order_status,

        u.name AS customer_name

    FROM orders o

    INNER JOIN users u
        ON o.customer_id = u.user_id

    ORDER BY
        o.order_date DESC

    LIMIT 8
");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $recentOrders[] =
            $row;

    }

}


/*
|--------------------------------------------------------------------------
| RECENT PRODUCTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT

        p.product_id,
        p.product_name,
        p.price,
        p.stock_quantity,
        p.status,
        p.image,

        v.business_name

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.vendor_id

    ORDER BY
        p.created_at DESC

    LIMIT 6
");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $recentProducts[] =
            $row;

    }

}


/*
|--------------------------------------------------------------------------
| RECENT VENDORS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT

        v.vendor_id,
        v.business_name,
        v.approval_status,
        v.created_at,

        u.name AS owner_name

    FROM vendors v

    LEFT JOIN users u
        ON v.user_id = u.user_id

    ORDER BY
        v.created_at DESC

    LIMIT 6
");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $recentVendors[] =
            $row;

    }

}


/*
|--------------------------------------------------------------------------
| RECENT USERS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT

        user_id,
        name,
        email,
        role,
        status,
        created_at

    FROM users

    ORDER BY
        created_at DESC

    LIMIT 6
");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $recentUsers[] =
            $row;

    }

}


/*
|--------------------------------------------------------------------------
| FORMAT HELPERS
|--------------------------------------------------------------------------
*/

function admin_dashboard_money(
    $amount
) {

    return 'RM ' .
        number_format(
            (float) $amount,
            2
        );

}


function admin_dashboard_date(
    $date
) {

    if (!$date) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );

}


function admin_dashboard_status(
    $status
) {

    return 'status-' .
        strtolower(
            str_replace(
                ' ',
                '-',
                $status
            )
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
        Admin Dashboard |
        <?php
        echo htmlspecialchars(
            SITE_NAME
        );
        ?>
    </title>


    <link
        rel="stylesheet"
        href="<?php
        echo site_url(
            'css/style.css'
        );
        ?>"
    >

    <link
        rel="stylesheet"
        href="<?php
        echo site_url(
            'css/admin.css'
        );
        ?>"
    >

    <link
        rel="stylesheet"
        href="<?php
        echo site_url(
            'css/responsive.css'
        );
        ?>"
    >


    <style>

        .admin-dashboard {

            min-height: 100vh;

            padding:
                40px 0 80px;

            background:

                radial-gradient(
                    circle at 10% 0%,
                    rgba(
                        37,
                        99,
                        235,
                        .18
                    ),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 90% 10%,
                    rgba(
                        14,
                        165,
                        233,
                        .13
                    ),
                    transparent 25%
                ),

                linear-gradient(
                    145deg,
                    #020617,
                    #061a35 55%,
                    #020617
                );

            color: #f8fafc;

        }


        .admin-dashboard-container {

            width: 90%;

            max-width: 1400px;

            margin: auto;

        }


        .admin-hero {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            padding: 30px;

            margin-bottom: 20px;

            border:
                1px solid
                rgba(
                    56,
                    189,
                    248,
                    .16
                );

            border-radius: 23px;

            background:

                linear-gradient(
                    135deg,
                    rgba(
                        15,
                        23,
                        42,
                        .95
                    ),
                    rgba(
                        8,
                        47,
                        73,
                        .72
                    )
                );

        }


        .admin-hero small {

            color: #38bdf8;

            font-size: 8px;

            font-weight: 950;

            letter-spacing: 1.8px;

        }


        .admin-hero h1 {

            margin:
                8px 0 0;

            font-size: 36px;

            font-weight: 950;

        }


        .admin-hero h1 span {

            color: #38bdf8;

        }


        .admin-hero p {

            margin:
                9px 0 0;

            color: #64748b;

            font-size: 10px;

            line-height: 1.6;

        }


        .admin-role {

            padding:
                16px 23px;

            border:
                1px solid
                rgba(
                    56,
                    189,
                    248,
                    .16
                );

            border-radius: 15px;

            background:
                rgba(
                    2,
                    6,
                    23,
                    .45
                );

            text-align: center;

        }


        .admin-role span {

            display: block;

            color: #475569;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 1px;

        }


        .admin-role strong {

            display: block;

            margin-top: 5px;

            color: #7dd3fc;

            font-size: 15px;

            text-transform:
                uppercase;

        }


        .admin-stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 13px;

            margin-bottom: 20px;

        }


        .admin-stat {

            padding: 19px;

            border:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .09
                );

            border-radius: 17px;

            background:
                rgba(
                    15,
                    23,
                    42,
                    .78
                );

            transition: .2s ease;

        }


        .admin-stat:hover {

            transform:
                translateY(-3px);

            border-color:
                rgba(
                    56,
                    189,
                    248,
                    .25
                );

        }


        .admin-stat-label {

            color: #64748b;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-transform:
                uppercase;

        }


        .admin-stat-value {

            display: block;

            margin-top: 7px;

            color: #f8fafc;

            font-size: 24px;

            font-weight: 950;

        }


        .admin-stat-sub {

            display: block;

            margin-top: 4px;

            color: #475569;

            font-size: 8px;

        }


        .admin-grid {

            display: grid;

            grid-template-columns:
                1.45fr 1fr;

            gap: 18px;

        }


        .admin-card {

            overflow: hidden;

            border:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .08
                );

            border-radius: 18px;

            background:
                rgba(
                    15,
                    23,
                    42,
                    .78
                );

        }


        .admin-card-header {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            padding:
                17px 19px;

            border-bottom:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .06
                );

        }


        .admin-card-header h2 {

            margin: 0;

            color: #e2e8f0;

            font-size: 13px;

            font-weight: 900;

        }


        .admin-card-header span {

            color: #475569;

            font-size: 8px;

        }


        .admin-link {

            color: #38bdf8;

            font-size: 8px;

            font-weight: 900;

            text-decoration: none;

        }


        .admin-order {

            display: flex;

            justify-content:
                space-between;

            gap: 15px;

            padding:
                14px 19px;

            border-bottom:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .05
                );

        }


        .admin-order:last-child {

            border-bottom: 0;

        }


        .admin-order-id {

            color: #cbd5e1;

            font-size: 10px;

            font-weight: 850;

        }


        .admin-order-meta {

            display: block;

            margin-top: 4px;

            color: #475569;

            font-size: 8px;

        }


        .admin-order-right {

            text-align: right;

        }


        .admin-order-amount {

            color: #f8fafc;

            font-size: 10px;

            font-weight: 900;

        }


        .admin-status {

            display: inline-flex;

            margin-top: 5px;

            padding:
                4px 7px;

            border-radius: 99px;

            font-size: 7px;

            font-weight: 900;

        }


        .status-pending {

            background:
                rgba(
                    250,
                    204,
                    21,
                    .08
                );

            color: #fde047;

        }


        .status-processing {

            background:
                rgba(
                    56,
                    189,
                    248,
                    .08
                );

            color: #7dd3fc;

        }


        .status-completed,
        .status-paid,
        .status-approved {

            background:
                rgba(
                    34,
                    197,
                    94,
                    .08
                );

            color: #86efac;

        }


        .status-cancelled,
        .status-rejected {

            background:
                rgba(
                    239,
                    68,
                    68,
                    .08
                );

            color: #fca5a5;

        }


        .admin-product {

            display: flex;

            align-items: center;

            gap: 10px;

            padding:
                12px 19px;

            border-bottom:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .05
                );

        }


        .admin-product:last-child {

            border-bottom: 0;

        }


        .admin-product-image {

            width: 42px;

            height: 42px;

            flex-shrink: 0;

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 11px;

            background:
                #020617;

            color: #334155;

        }


        .admin-product-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .admin-product-info {

            flex: 1;

            min-width: 0;

        }


        .admin-product-name {

            display: block;

            overflow: hidden;

            color: #cbd5e1;

            font-size: 9px;

            font-weight: 850;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .admin-product-vendor {

            display: block;

            margin-top: 3px;

            color: #475569;

            font-size: 7px;

        }


        .admin-product-price {

            color: #7dd3fc;

            font-size: 9px;

            font-weight: 900;

        }


        .admin-actions {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 8px;

            padding: 16px;

        }


        .admin-action {

            padding: 13px;

            border:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .08
                );

            border-radius: 11px;

            background:
                rgba(
                    2,
                    6,
                    23,
                    .35
                );

            color: #94a3b8;

            font-size: 8px;

            font-weight: 850;

            text-decoration: none;

            transition: .2s ease;

        }


        .admin-action:hover {

            border-color:
                rgba(
                    56,
                    189,
                    248,
                    .25
                );

            color: #7dd3fc;

            transform:
                translateY(-2px);

        }


        .admin-action strong {

            display: block;

            margin-bottom: 4px;

            color: #38bdf8;

            font-size: 17px;

        }


        .admin-empty {

            padding: 40px 20px;

            color: #475569;

            font-size: 9px;

            text-align: center;

        }


        @media (
            max-width: 1050px
        ) {

            .admin-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .admin-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (
            max-width: 600px
        ) {

            .admin-hero {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }

            .admin-role {

                width: 100%;

                box-sizing:
                    border-box;

            }

            .admin-stats {

                grid-template-columns:
                    1fr;

            }

            .admin-actions {

                grid-template-columns:
                    1fr;

            }

        }

    </style>

</head>


<body>


<?php

require_once __DIR__ .
    '/../includes/navbar.php';

?>


<main class="admin-dashboard">


    <div
        class="admin-dashboard-container"
    >


        <!-- HERO -->

        <section class="admin-hero">

            <div>

                <small>
                    ADMIN CONTROL CENTER
                </small>

                <h1>

                    Welcome,
                    <span>
                        <?php
                        echo htmlspecialchars(
                            $user['name']
                        );
                        ?>
                    </span>

                </h1>

                <p>
                    Manage the HochipoHub
                    marketplace from one
                    central dashboard.
                </p>

            </div>


            <div class="admin-role">

                <span>
                    ACCOUNT ROLE
                </span>

                <strong>
                    Administrator
                </strong>

            </div>

        </section>


        <!-- STATS -->

        <section class="admin-stats">


            <div class="admin-stat">

                <span
                    class="admin-stat-label"
                >
                    Users
                </span>

                <strong
                    class="admin-stat-value"
                >
                    <?php
                    echo number_format(
                        $stats['users']
                    );
                    ?>
                </strong>

                <span
                    class="admin-stat-sub"
                >
                    All registered users
                </span>

            </div>


            <div class="admin-stat">

                <span
                    class="admin-stat-label"
                >
                    Vendors
                </span>

                <strong
                    class="admin-stat-value"
                >
                    <?php
                    echo number_format(
                        $stats['vendors']
                    );
                    ?>
                </strong>

                <span
                    class="admin-stat-sub"
                >
                    Approved vendors
                </span>

            </div>


            <div class="admin-stat">

                <span
                    class="admin-stat-label"
                >
                    Products
                </span>

                <strong
                    class="admin-stat-value"
                >
                    <?php
                    echo number_format(
                        $stats['products']
                    );
                    ?>
                </strong>

                <span
                    class="admin-stat-sub"
                >
                    Marketplace products
                </span>

            </div>


            <div class="admin-stat">

                <span
                    class="admin-stat-label"
                >
                    Sales
                </span>

                <strong
                    class="admin-stat-value"
                >
                    <?php
                    echo admin_dashboard_money(
                        $stats['sales']
                    );
                    ?>
                </strong>

                <span
                    class="admin-stat-sub"
                >
                    Non-cancelled orders
                </span>

            </div>


        </section>


        <!-- SECONDARY STATS -->

        <section
            class="admin-actions"
            style="
                margin-bottom:20px;
                padding:0;
            "
        >


            <a
                href="<?php
                echo site_url(
                    'admin/vendors.php'
                );
                ?>"
                class="admin-action"
            >

                <strong>
                    <?php
                    echo number_format(
                        $stats['pending_vendors']
                    );
                    ?>
                </strong>

                Pending Vendor Applications

            </a>


            <a
                href="<?php
                echo site_url(
                    'admin/orders.php'
                );
                ?>"
                class="admin-action"
            >

                <strong>
                    <?php
                    echo number_format(
                        $stats['pending_orders']
                    );
                    ?>
                </strong>

                Pending Orders

            </a>


            <a
                href="<?php
                echo site_url(
                    'admin/commission.php'
                );
                ?>"
                class="admin-action"
            >

                <strong>
                    <?php
                    echo admin_dashboard_money(
                        $stats['commission']
                    );
                    ?>
                </strong>

                Total Commission

            </a>


            <a
                href="<?php
                echo site_url(
                    'admin/users.php'
                );
                ?>"
                class="admin-action"
            >

                <strong>
                    <?php
                    echo number_format(
                        $stats['customers']
                    );
                    ?>
                </strong>

                Customers

            </a>


        </section>


        <!-- MAIN GRID -->

        <div class="admin-grid">


            <!-- RECENT ORDERS -->

            <section class="admin-card">

                <div
                    class="admin-card-header"
                >

                    <div>

                        <h2>
                            Recent Orders
                        </h2>

                        <span>
                            Latest marketplace
                            transactions
                        </span>

                    </div>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/orders.php'
                        );
                        ?>"
                        class="admin-link"
                    >
                        VIEW ALL →
                    </a>

                </div>


                <?php if (
                    empty($recentOrders)
                ): ?>

                    <div
                        class="admin-empty"
                    >
                        No orders yet.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentOrders
                        as $order
                    ): ?>


                        <div
                            class="admin-order"
                        >

                            <div>

                                <span
                                    class="admin-order-id"
                                >

                                    Order #

                                    <?php
                                    echo (int)
                                        $order[
                                            'order_id'
                                        ];
                                    ?>

                                </span>


                                <span
                                    class="admin-order-meta"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $order[
                                            'customer_name'
                                        ]
                                    );
                                    ?>

                                    ·

                                    <?php
                                    echo admin_dashboard_date(
                                        $order[
                                            'order_date'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                            <div
                                class="admin-order-right"
                            >

                                <span
                                    class="admin-order-amount"
                                >

                                    <?php
                                    echo admin_dashboard_money(
                                        $order[
                                            'total_amount'
                                        ]
                                    );
                                    ?>

                                </span>


                                <span
                                    class="
                                        admin-status
                                        <?php
                                        echo admin_dashboard_status(
                                            $order[
                                                'order_status'
                                            ]
                                        );
                                        ?>
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $order[
                                            'order_status'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </section>


            <!-- QUICK ACTIONS -->

            <section class="admin-card">

                <div
                    class="admin-card-header"
                >

                    <div>

                        <h2>
                            Admin Tools
                        </h2>

                        <span>
                            Manage marketplace
                        </span>

                    </div>

                </div>


                <div
                    class="admin-actions"
                >


                    <a
                        href="<?php
                        echo site_url(
                            'admin/users.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <strong>
                            U
                        </strong>

                        Users

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/vendors.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <strong>
                            V
                        </strong>

                        Vendors

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/products.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <strong>
                            P
                        </strong>

                        Products

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/orders.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <strong>
                            O
                        </strong>

                        Orders

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/commission.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <strong>
                            %
                        </strong>

                        Commission

                    </a>


                    <a
                        href="<?php
                        echo site_url(
                            'dashboard.php'
                        );
                        ?>"
                        class="admin-action"
                    >

                        <strong>
                            ↗
                        </strong>

                        Main Dashboard

                    </a>


                </div>

            </section>


            <!-- PRODUCTS -->

            <section class="admin-card">

                <div
                    class="admin-card-header"
                >

                    <div>

                        <h2>
                            Recent Products
                        </h2>

                        <span>
                            Latest catalogue
                            additions
                        </span>

                    </div>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/products.php'
                        );
                        ?>"
                        class="admin-link"
                    >
                        MANAGE →
                    </a>

                </div>


                <?php if (
                    empty($recentProducts)
                ): ?>

                    <div
                        class="admin-empty"
                    >
                        No products yet.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentProducts
                        as $product
                    ): ?>


                        <div
                            class="admin-product"
                        >


                            <div
                                class="
                                    admin-product-image
                                "
                            >

                                <?php if (
                                    !empty(
                                        $product[
                                            'image'
                                        ]
                                    )
                                ): ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            site_url(
                                                'image/product/' .
                                                $product[
                                                    'image'
                                                ]
                                            )
                                        );
                                        ?>"
                                        alt=""
                                    >

                                <?php else: ?>

                                    P

                                <?php endif; ?>

                            </div>


                            <div
                                class="
                                    admin-product-info
                                "
                            >

                                <span
                                    class="
                                        admin-product-name
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $product[
                                            'product_name'
                                        ]
                                    );
                                    ?>

                                </span>


                                <span
                                    class="
                                        admin-product-vendor
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $product[
                                            'business_name'
                                        ]
                                    );
                                    ?>

                                    · Stock:

                                    <?php
                                    echo number_format(
                                        (int)
                                        $product[
                                            'stock_quantity'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                            <span
                                class="
                                    admin-product-price
                                "
                            >

                                <?php
                                echo admin_dashboard_money(
                                    $product[
                                        'price'
                                    ]
                                );
                                ?>

                            </span>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </section>


            <!-- RECENT VENDORS -->

            <section class="admin-card">

                <div
                    class="admin-card-header"
                >

                    <div>

                        <h2>
                            Recent Vendors
                        </h2>

                        <span>
                            Vendor activity
                        </span>

                    </div>


                    <a
                        href="<?php
                        echo site_url(
                            'admin/vendors.php'
                        );
                        ?>"
                        class="admin-link"
                    >
                        MANAGE →
                    </a>

                </div>


                <?php if (
                    empty($recentVendors)
                ): ?>

                    <div
                        class="admin-empty"
                    >
                        No vendors yet.
                    </div>

                <?php else: ?>


                    <?php foreach (
                        $recentVendors
                        as $vendor
                    ): ?>


                        <div
                            class="admin-order"
                        >

                            <div>

                                <span
                                    class="admin-order-id"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $vendor[
                                            'business_name'
                                        ]
                                    );
                                    ?>

                                </span>


                                <span
                                    class="admin-order-meta"
                                >

                                    Owner:
                                    <?php
                                    echo htmlspecialchars(
                                        $vendor[
                                            'owner_name'
                                        ] ??
                                        'Unknown'
                                    );
                                    ?>

                                </span>

                            </div>


                            <div
                                class="admin-order-right"
                            >

                                <span
                                    class="
                                        admin-status
                                        <?php
                                        echo admin_dashboard_status(
                                            $vendor[
                                                'approval_status'
                                            ]
                                        );
                                        ?>
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $vendor[
                                            'approval_status'
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </section>


        </div>


    </div>


</main>


<?php

require_once __DIR__ .
    '/../includes/footer.php';

?>


</body>

</html>