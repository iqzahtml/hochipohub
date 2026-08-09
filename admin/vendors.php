<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

$search = trim($_GET['search'] ?? '');

$vendors = [];

$totalVendors = 0;
$totalProducts = 0;
$totalSales = 0;

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| LOAD VENDORS
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT

            u.user_id,
            u.name,
            u.email,
            u.phone,
            u.profile_image,

            COUNT(
                DISTINCT p.product_id
            ) AS product_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN o.order_id IS NOT NULL
                        THEN oi.quantity * oi.price
                        ELSE 0
                    END
                ),
                0
            ) AS total_sales

        FROM users u

        LEFT JOIN products p
            ON p.vendor_id = u.user_id

        LEFT JOIN order_items oi
            ON oi.product_id = p.product_id

        LEFT JOIN orders o
            ON o.order_id = oi.order_id

        WHERE u.role = 'vendor'
    ";

    $params = [];

    if ($search !== '') {

        $sql .= "
            AND (
                u.name LIKE :search
                OR u.email LIKE :search
                OR u.phone LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';
    }

    $sql .= "
        GROUP BY
            u.user_id,
            u.name,
            u.email,
            u.phone,
            u.profile_image

        ORDER BY
            u.user_id DESC
    ";

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $vendors =
        $stmt->fetchAll();

} catch (PDOException $e) {

    $vendors = [];

    $errorMessage = APP_DEBUG
        ? $e->getMessage()
        : 'Unable to load vendors.';
}

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->query("
        SELECT
            COUNT(*) AS total_vendors
        FROM users
        WHERE role = 'vendor'
    ");

    $totalVendors =
        (int) (
            $stmt->fetch()[
                'total_vendors'
            ] ?? 0
        );

} catch (PDOException $e) {

    $totalVendors = 0;
}

foreach (
    $vendors
    as $vendor
) {

    $totalProducts +=
        (int) (
            $vendor['product_count']
            ?? 0
        );

    $totalSales +=
        (float) (
            $vendor['total_sales']
            ?? 0
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
        Vendors |
        <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .vendors-page {
            min-height: 100vh;

            padding:
                35px 4%
                60px;

            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(37,99,235,.14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 20%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                #f8fbff;
        }

        .vendors-container {
            max-width: 1500px;
            margin: auto;
        }

        .vendors-hero {
            position: relative;

            overflow: hidden;

            margin-bottom: 24px;

            padding: 35px;

            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #020617,
                    #172554 35%,
                    #1d4ed8 68%,
                    #0284c7
                );

            color: white;

            box-shadow:
                0 25px 65px
                rgba(29,78,216,.22);
        }

        .vendors-hero::before {
            content: "";

            position: absolute;

            width: 370px;
            height: 370px;

            top: -220px;
            right: -80px;

            border-radius: 50%;

            background:
                rgba(96,165,250,.14);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;

            color:
                rgba(255,255,255,.62);

            font-size: 9px;
            font-weight: 950;

            letter-spacing: 2px;

            text-transform: uppercase;
        }

        .vendors-hero h1 {
            margin: 0 0 8px;

            font-size:
                clamp(29px,5vw,46px);

            font-weight: 950;
        }

        .vendors-hero p {
            max-width: 700px;

            margin: 0;

            color:
                rgba(255,255,255,.75);

            font-size: 11px;

            line-height: 1.7;
        }

        .message {
            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 12px;

            font-size: 9px;
            font-weight: 850;
        }

        .message.error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #991b1b;
        }

        .stats-grid {
            display: grid;

            grid-template-columns:
                repeat(3,1fr);

            gap: 14px;

            margin-bottom: 22px;
        }

        .stat-card {
            padding: 20px;

            border: 1px solid #dbeafe;

            border-radius: 20px;

            background: white;

            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .stat-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 8px;
            font-weight: 950;

            text-transform: uppercase;
        }

        .stat-value {
            color: #2563eb;

            font-size: 26px;
            font-weight: 950;
        }

        .stat-card:nth-child(2)
        .stat-value {
            color: #059669;
        }

        .stat-card:nth-child(3)
        .stat-value {
            color: #7c3aed;
        }

        .panel {
            overflow: hidden;

            border: 1px solid #dbeafe;

            border-radius: 23px;

            background: white;

            box-shadow:
                0 12px 40px
                rgba(15,23,42,.055);
        }

        .panel-header {
            padding: 21px 23px;

            border-bottom:
                1px solid #eff6ff;
        }

        .panel-header h2 {
            margin: 0 0 4px;

            color: #0f172a;

            font-size: 15px;
            font-weight: 950;
        }

        .panel-header p {
            margin: 0;

            color: #94a3b8;

            font-size: 8px;
        }

        .filter-area {
            padding: 16px 22px;

            border-bottom:
                1px solid #eff6ff;

            background: #fbfdff;
        }

        .filter-form {
            display: flex;

            gap: 8px;
        }

        .filter-input {
            flex: 1;

            padding: 11px 12px;

            border: 1px solid #dbeafe;

            border-radius: 11px;

            outline: none;

            background: white;

            font-size: 9px;
        }

        .filter-button {
            padding: 11px 18px;

            border: 0;

            border-radius: 11px;

            cursor: pointer;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0284c7
                );

            color: white;

            font-size: 8px;
            font-weight: 950;
        }

        .vendor-grid {
            display: grid;

            grid-template-columns:
                repeat(3,1fr);

            gap: 15px;

            padding: 22px;
        }

        .vendor-card {
            position: relative;

            overflow: hidden;

            padding: 20px;

            border: 1px solid #dbeafe;

            border-radius: 20px;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    #f8fbff
                );

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .vendor-card:hover {
            transform:
                translateY(-4px);

            box-shadow:
                0 15px 35px
                rgba(37,99,235,.10);
        }

        .vendor-top {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 17px;
        }

        .vendor-avatar {
            display: flex;

            align-items: center;
            justify-content: center;

            width: 48px;
            height: 48px;

            flex-shrink: 0;

            overflow: hidden;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #e0f2fe
                );

            color: #2563eb;

            font-size: 16px;
            font-weight: 950;
        }

        .vendor-avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .vendor-name {
            color: #0f172a;

            font-size: 11px;
            font-weight: 950;
        }

        .vendor-email {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 7px;

            word-break: break-word;
        }

        .vendor-phone {
            margin-top: 3px;

            color: #64748b;

            font-size: 7px;
        }

        .vendor-badge {
            position: absolute;

            top: 16px;
            right: 16px;

            padding: 5px 8px;

            border-radius: 7px;

            background: #ecfdf5;

            color: #059669;

            font-size: 6px;
            font-weight: 950;

            text-transform: uppercase;
        }

        .vendor-stats {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 8px;

            margin-top: 17px;
        }

        .vendor-stat {
            padding: 12px;

            border-radius: 12px;

            background: #eff6ff;
        }

        .vendor-stat span {
            display: block;

            margin-bottom: 4px;

            color: #94a3b8;

            font-size: 6px;
            font-weight: 900;

            text-transform: uppercase;
        }

        .vendor-stat strong {
            color: #1e40af;

            font-size: 10px;
            font-weight: 950;
        }

        .empty-state {
            padding: 70px 20px;

            text-align: center;
        }

        .empty-state .icon {
            margin-bottom: 10px;

            font-size: 40px;
        }

        .empty-state strong {
            display: block;

            margin-bottom: 5px;

            color: #334155;

            font-size: 13px;
        }

        .empty-state span {
            color: #94a3b8;

            font-size: 9px;
        }

        @media (max-width: 1050px) {

            .vendor-grid {
                grid-template-columns:
                    repeat(2,1fr);
            }

        }

        @media (max-width: 800px) {

            .stats-grid {
                grid-template-columns:
                    1fr;
            }

        }

        @media (max-width: 600px) {

            .vendors-page {
                padding:
                    25px 15px 50px;
            }

            .vendors-hero {
                padding: 27px 21px;
            }

            .vendor-grid {
                grid-template-columns:
                    1fr;

                padding: 15px;
            }

            .filter-form {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="vendors-page">

    <div class="vendors-container">

        <section class="vendors-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    Vendor Management
                </h1>

                <p>
                    Monitor registered vendors,
                    product listings and marketplace
                    sales performance.
                </p>

            </div>

        </section>

        <?php if ($errorMessage !== ''): ?>

            <div class="message error">
                ⚠ <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-label">
                    Total Vendors
                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalVendors
                    ) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    Listed Products
                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalProducts
                    ) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    Sales
                </div>

                <div class="stat-value">
                    <?= e(
                        formatPrice(
                            $totalSales
                        )
                    ) ?>
                </div>

            </div>

        </section>

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Registered Vendors
                </h2>

                <p>
                    View vendor information and
                    marketplace performance.
                </p>

            </div>

            <div class="filter-area">

                <form
                    method="GET"
                    class="filter-form"
                >

                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Search vendor name, email or phone..."
                        value="<?= e($search) ?>"
                    >

                    <button
                        type="submit"
                        class="filter-button"
                    >
                        SEARCH
                    </button>

                </form>

            </div>

            <?php if (empty($vendors)): ?>

                <div class="empty-state">

                    <div class="icon">
                        🏪
                    </div>

                    <strong>
                        No vendors found
                    </strong>

                    <span>
                        No registered vendor matches
                        your search.
                    </span>

                </div>

            <?php else: ?>

                <div class="vendor-grid">

                    <?php foreach (
                        $vendors
                        as $vendor
                    ): ?>

                        <?php

                        $vendorName =
                            $vendor['name']
                            ?: 'Unknown Vendor';

                        $initial =
                            strtoupper(
                                substr(
                                    $vendorName,
                                    0,
                                    1
                                )
                            );

                        ?>

                        <article
                            class="vendor-card"
                        >

                            <div class="vendor-badge">
                                Vendor
                            </div>

                            <div class="vendor-top">

                                <div class="vendor-avatar">

                                    <?php if (
                                        !empty(
                                            $vendor[
                                                'profile_image'
                                            ]
                                        )
                                    ): ?>

                                        <img
                                            src="<?= e(
                                                vendorImageUrl(
                                                    $vendor[
                                                        'profile_image'
                                                    ]
                                                )
                                            ) ?>"
                                            alt="<?= e(
                                                $vendorName
                                            ) ?>"
                                        >

                                    <?php else: ?>

                                        <?= e(
                                            $initial
                                        ) ?>

                                    <?php endif; ?>

                                </div>

                                <div>

                                    <div class="vendor-name">
                                        <?= e(
                                            $vendorName
                                        ) ?>
                                    </div>

                                    <div class="vendor-email">
                                        <?= e(
                                            $vendor['email']
                                        ) ?>
                                    </div>

                                    <div class="vendor-phone">
                                        <?= e(
                                            $vendor['phone']
                                            ?? '-'
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                            <div class="vendor-stats">

                                <div class="vendor-stat">

                                    <span>
                                        Products
                                    </span>

                                    <strong>
                                        <?= number_format(
                                            (int) (
                                                $vendor[
                                                    'product_count'
                                                ] ?? 0
                                            )
                                        ) ?>
                                    </strong>

                                </div>

                                <div class="vendor-stat">

                                    <span>
                                        Sales
                                    </span>

                                    <strong>
                                        <?= e(
                                            formatPrice(
                                                $vendor[
                                                    'total_sales'
                                                ] ?? 0
                                            )
                                        ) ?>
                                    </strong>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

</body>

</html>