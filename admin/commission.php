<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB
| ADMIN - COMMISSION MANAGEMENT
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
| ADMIN CHECK
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
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
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

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result = $stmt->get_result();

$currentUser =
    $result->fetch_assoc();

$stmt->close();


if (!$currentUser) {

    session_destroy();

    header(
        'Location: ' .
        site_url('index.php')
    );

    exit;
}


if ($currentUser['role'] !== 'admin') {

    header(
        'Location: ' .
        site_url('dashboard.php')
    );

    exit;
}


if ($currentUser['status'] !== 'active') {

    session_destroy();

    header(
        'Location: ' .
        site_url('index.php?account=inactive')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$statusFilter =
    $_GET['status'] ?? 'all';

$vendorFilter =
    isset($_GET['vendor_id'])
        ? (int) $_GET['vendor_id']
        : 0;

$search =
    trim(
        $_GET['search'] ?? ''
    );


$allowedStatuses = [
    'all',
    'Pending',
    'Paid',
    'Cancelled'
];


if (
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $statusFilter = 'all';

}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$summary = [
    'total' => 0,
    'pending' => 0,
    'paid' => 0,
    'cancelled' => 0
];


$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Paid'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS paid,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Cancelled'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS cancelled
    FROM commission
");


if ($result) {

    $row =
        $result->fetch_assoc();

    $summary['total'] =
        (int) ($row['total'] ?? 0);

    $summary['pending'] =
        (int) ($row['pending'] ?? 0);

    $summary['paid'] =
        (int) ($row['paid'] ?? 0);

    $summary['cancelled'] =
        (int) ($row['cancelled'] ?? 0);

}


/*
|--------------------------------------------------------------------------
| COMMISSION AMOUNT SUMMARY
|--------------------------------------------------------------------------
*/

$amountSummary = [
    'total' => 0,
    'pending' => 0,
    'paid' => 0
];


$result = $conn->query("
    SELECT

        COALESCE(
            SUM(commission_amount),
            0
        ) AS total,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS pending,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Paid'
                    THEN commission_amount
                    ELSE 0
                END
            ),
            0
        ) AS paid

    FROM commission
");


if ($result) {

    $row =
        $result->fetch_assoc();

    $amountSummary['total'] =
        (float) ($row['total'] ?? 0);

    $amountSummary['pending'] =
        (float) ($row['pending'] ?? 0);

    $amountSummary['paid'] =
        (float) ($row['paid'] ?? 0);

}


/*
|--------------------------------------------------------------------------
| GET VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];

$result = $conn->query("
    SELECT
        vendor_id,
        business_name
    FROM vendors
    ORDER BY business_name ASC
");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $vendors[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| BUILD COMMISSION QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        c.commission_id,
        c.order_id,
        c.vendor_id,
        c.commission_rate,
        c.commission_amount,
        c.status,
        c.created_at,

        v.business_name,

        u.name AS vendor_owner

    FROM commission c

    INNER JOIN vendors v
        ON c.vendor_id = v.vendor_id

    LEFT JOIN users u
        ON v.user_id = u.user_id

    WHERE 1 = 1
";


$params = [];

$types = "";


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter !== 'all') {

    $sql .= "
        AND c.status = ?
    ";

    $params[] =
        $statusFilter;

    $types .= "s";

}


/*
|--------------------------------------------------------------------------
| VENDOR FILTER
|--------------------------------------------------------------------------
*/

if ($vendorFilter > 0) {

    $sql .= "
        AND c.vendor_id = ?
    ";

    $params[] =
        $vendorFilter;

    $types .= "i";

}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            v.business_name LIKE ?
            OR u.name LIKE ?
            OR CAST(c.order_id AS CHAR) LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $types .= "sss";

}


$sql .= "
    ORDER BY c.created_at DESC
";


/*
|--------------------------------------------------------------------------
| PREPARE
|--------------------------------------------------------------------------
*/

$stmt =
    $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$result =
    $stmt->get_result();


$commissions = [];


while (
    $row =
    $result->fetch_assoc()
) {

    $commissions[] =
        $row;

}


$stmt->close();


/*
|--------------------------------------------------------------------------
| FORMAT MONEY
|--------------------------------------------------------------------------
*/

function admin_commission_money(
    $amount
) {

    return 'RM ' .
        number_format(
            (float) $amount,
            2
        );

}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function admin_commission_date(
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


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

function admin_commission_status_class(
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
        Commission |
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

        .admin-page {

            min-height: 100vh;

            padding:
                35px 0 80px;

            background:
                linear-gradient(
                    145deg,
                    #020617,
                    #061a35,
                    #020617
                );

            color: #f8fafc;

        }


        .admin-container {

            width: 90%;

            max-width: 1400px;

            margin: auto;

        }


        .admin-header {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

        }


        .admin-header h1 {

            margin: 0;

            color: #f8fafc;

            font-size: 30px;

            font-weight: 950;

        }


        .admin-header p {

            margin: 7px 0 0;

            color: #64748b;

            font-size: 11px;

        }


        .admin-back {

            display: inline-flex;

            align-items: center;

            padding:
                10px 15px;

            border:
                1px solid
                rgba(
                    56,
                    189,
                    248,
                    .18
                );

            border-radius: 10px;

            color: #7dd3fc;

            text-decoration: none;

            font-size: 10px;

            font-weight: 900;

        }


        .commission-summary {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 14px;

            margin-bottom: 20px;

        }


        .commission-stat {

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
                    .82
                );

        }


        .commission-stat-label {

            display: block;

            color: #64748b;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-transform:
                uppercase;

        }


        .commission-stat-value {

            display: block;

            margin-top: 8px;

            color: #f8fafc;

            font-size: 23px;

            font-weight: 950;

        }


        .commission-stat-sub {

            display: block;

            margin-top: 4px;

            color: #475569;

            font-size: 8px;

        }


        .commission-filter {

            display: flex;

            flex-wrap: wrap;

            gap: 9px;

            padding: 15px;

            margin-bottom: 18px;

            border:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .08
                );

            border-radius: 15px;

            background:
                rgba(
                    15,
                    23,
                    42,
                    .72
                );

        }


        .commission-filter input,
        .commission-filter select {

            min-height: 38px;

            padding:
                0 12px;

            border:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .12
                );

            border-radius: 9px;

            outline: none;

            background:
                #020617;

            color: #cbd5e1;

            font-size: 10px;

        }


        .commission-filter input {

            flex: 1;

            min-width: 190px;

        }


        .commission-filter button {

            min-height: 38px;

            padding:
                0 17px;

            border: 0;

            border-radius: 9px;

            background:
                #0284c7;

            color: white;

            cursor: pointer;

            font-size: 10px;

            font-weight: 900;

        }


        .commission-table-card {

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


        .commission-table-wrapper {

            overflow-x: auto;

        }


        .commission-table {

            width: 100%;

            border-collapse:
                collapse;

            min-width: 900px;

        }


        .commission-table th {

            padding:
                14px 16px;

            background:
                rgba(
                    2,
                    6,
                    23,
                    .55
                );

            color: #475569;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            text-align: left;

            text-transform:
                uppercase;

        }


        .commission-table td {

            padding:
                15px 16px;

            border-top:
                1px solid
                rgba(
                    148,
                    163,
                    184,
                    .055
                );

            color: #94a3b8;

            font-size: 10px;

        }


        .commission-table tr:hover td {

            background:
                rgba(
                    14,
                    165,
                    233,
                    .025
                );

        }


        .commission-id {

            color: #7dd3fc;

            font-weight: 900;

        }


        .commission-vendor {

            color: #e2e8f0;

            font-weight: 850;

        }


        .commission-owner {

            display: block;

            margin-top: 3px;

            color: #475569;

            font-size: 8px;

        }


        .commission-amount {

            color: #f8fafc;

            font-weight: 950;

        }


        .commission-status {

            display: inline-flex;

            padding:
                5px 9px;

            border-radius: 99px;

            font-size: 8px;

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


        .status-paid {

            background:
                rgba(
                    34,
                    197,
                    94,
                    .08
                );

            color: #86efac;

        }


        .status-cancelled {

            background:
                rgba(
                    239,
                    68,
                    68,
                    .08
                );

            color: #fca5a5;

        }


        .commission-empty {

            padding: 60px 20px;

            text-align: center;

        }


        .commission-empty-icon {

            font-size: 30px;

            color: #38bdf8;

        }


        .commission-empty h3 {

            margin: 12px 0 5px;

            color: #cbd5e1;

            font-size: 14px;

        }


        .commission-empty p {

            margin: 0;

            color: #475569;

            font-size: 9px;

        }


        @media (
            max-width: 850px
        ) {

            .commission-summary {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (
            max-width: 550px
        ) {

            .admin-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }

            .commission-summary {

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


<main class="admin-page">


    <div class="admin-container">


        <div class="admin-header">

            <div>

                <h1>
                    Commission
                </h1>

                <p>
                    Monitor vendor commissions
                    across HochipoHub.
                </p>

            </div>


            <a
                href="<?php
                echo site_url(
                    'dashboard.php'
                );
                ?>"
                class="admin-back"
            >
                ← Dashboard
            </a>

        </div>


        <!-- SUMMARY -->

        <section
            class="commission-summary"
        >


            <div class="commission-stat">

                <span
                    class="commission-stat-label"
                >
                    Total Records
                </span>

                <strong
                    class="commission-stat-value"
                >
                    <?php
                    echo number_format(
                        $summary['total']
                    );
                    ?>
                </strong>

                <span
                    class="commission-stat-sub"
                >
                    All commission records
                </span>

            </div>


            <div class="commission-stat">

                <span
                    class="commission-stat-label"
                >
                    Total Commission
                </span>

                <strong
                    class="commission-stat-value"
                >
                    <?php
                    echo admin_commission_money(
                        $amountSummary['total']
                    );
                    ?>
                </strong>

                <span
                    class="commission-stat-sub"
                >
                    Recorded commission
                </span>

            </div>


            <div class="commission-stat">

                <span
                    class="commission-stat-label"
                >
                    Pending
                </span>

                <strong
                    class="commission-stat-value"
                >
                    <?php
                    echo admin_commission_money(
                        $amountSummary['pending']
                    );
                    ?>
                </strong>

                <span
                    class="commission-stat-sub"
                >
                    Awaiting payment
                </span>

            </div>


            <div class="commission-stat">

                <span
                    class="commission-stat-label"
                >
                    Paid
                </span>

                <strong
                    class="commission-stat-value"
                >
                    <?php
                    echo admin_commission_money(
                        $amountSummary['paid']
                    );
                    ?>
                </strong>

                <span
                    class="commission-stat-sub"
                >
                    Paid commissions
                </span>

            </div>


        </section>


        <!-- FILTER -->

        <form
            method="GET"
            class="commission-filter"
        >


            <input
                type="search"
                name="search"
                value="<?php
                echo htmlspecialchars(
                    $search
                );
                ?>"
                placeholder="Search vendor, owner or order..."
            >


            <select
                name="status"
            >

                <option
                    value="all"
                >
                    All Status
                </option>

                <option
                    value="Pending"
                    <?php
                    echo $statusFilter === 'Pending'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Pending
                </option>

                <option
                    value="Paid"
                    <?php
                    echo $statusFilter === 'Paid'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Paid
                </option>

                <option
                    value="Cancelled"
                    <?php
                    echo $statusFilter === 'Cancelled'
                        ? 'selected'
                        : '';
                    ?>
                >
                    Cancelled
                </option>

            </select>


            <select
                name="vendor_id"
            >

                <option value="0">
                    All Vendors
                </option>


                <?php foreach (
                    $vendors
                    as $vendorOption
                ): ?>

                    <option
                        value="<?php
                        echo (int)
                            $vendorOption[
                                'vendor_id'
                            ];
                        ?>"
                        <?php
                        echo
                            $vendorFilter ===
                            (int)
                            $vendorOption[
                                'vendor_id'
                            ]
                                ? 'selected'
                                : '';
                        ?>
                    >

                        <?php
                        echo htmlspecialchars(
                            $vendorOption[
                                'business_name'
                            ]
                        );
                        ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <button
                type="submit"
            >
                FILTER
            </button>


        </form>


        <!-- TABLE -->

        <section
            class="commission-table-card"
        >


            <?php if (
                empty($commissions)
            ): ?>


                <div
                    class="commission-empty"
                >

                    <div
                        class="commission-empty-icon"
                    >
                        %
                    </div>

                    <h3>
                        No commission records
                    </h3>

                    <p>
                        No commission data
                        matches your current
                        filter.
                    </p>

                </div>


            <?php else: ?>


                <div
                    class="commission-table-wrapper"
                >

                    <table
                        class="commission-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Vendor
                                </th>

                                <th>
                                    Rate
                                </th>

                                <th>
                                    Commission
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $commissions
                                as $commission
                            ): ?>


                                <tr>


                                    <td>

                                        <span
                                            class="commission-id"
                                        >

                                            #
                                            <?php
                                            echo (int)
                                                $commission[
                                                    'commission_id'
                                                ];
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        #

                                        <?php
                                        echo (int)
                                            $commission[
                                                'order_id'
                                            ];
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="commission-vendor"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $commission[
                                                    'business_name'
                                                ]
                                            );
                                            ?>

                                        </span>


                                        <?php if (
                                            !empty(
                                                $commission[
                                                    'vendor_owner'
                                                ]
                                            )
                                        ): ?>

                                            <span
                                                class="commission-owner"
                                            >

                                                Owner:
                                                <?php
                                                echo htmlspecialchars(
                                                    $commission[
                                                        'vendor_owner'
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo number_format(
                                            (float)
                                            $commission[
                                                'commission_rate'
                                            ],
                                            2
                                        );
                                        ?>%

                                    </td>


                                    <td>

                                        <span
                                            class="commission-amount"
                                        >

                                            <?php
                                            echo admin_commission_money(
                                                $commission[
                                                    'commission_amount'
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                commission-status
                                                <?php
                                                echo admin_commission_status_class(
                                                    $commission[
                                                        'status'
                                                    ]
                                                );
                                                ?>
                                            "
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $commission[
                                                    'status'
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo admin_commission_date(
                                            $commission[
                                                'created_at'
                                            ]
                                        );
                                        ?>

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

require_once __DIR__ .
    '/../includes/footer.php';

?>


</body>

</html>