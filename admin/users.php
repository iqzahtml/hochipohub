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
$role = trim($_GET['role'] ?? 'all');

$users = [];

$totalUsers = 0;
$totalCustomers = 0;
$totalVendors = 0;
$totalAdmins = 0;

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_user'])
) {

    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId <= 0) {

        $errorMessage = 'Invalid user selected.';

    } elseif (
        $userId === (int) $_SESSION['user_id']
    ) {

        $errorMessage =
            'You cannot delete your own admin account.';

    } else {

        try {

            $stmt = $db->prepare("
                DELETE FROM users
                WHERE user_id = :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            if ($stmt->rowCount() > 0) {

                $successMessage =
                    'User deleted successfully.';

            } else {

                $errorMessage =
                    'User could not be found.';
            }

        } catch (PDOException $e) {

            $errorMessage = APP_DEBUG
                ? $e->getMessage()
                : 'Unable to delete user.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| USER SUMMARY
|--------------------------------------------------------------------------
*/

try {

    $stmt = $db->query("
        SELECT

            COUNT(*) AS total_users,

            COALESCE(
                SUM(
                    CASE
                        WHEN role = 'customer'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS customers,

            COALESCE(
                SUM(
                    CASE
                        WHEN role = 'vendor'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS vendors,

            COALESCE(
                SUM(
                    CASE
                        WHEN role = 'admin'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS admins

        FROM users
    ");

    $summary = $stmt->fetch();

    if ($summary) {

        $totalUsers =
            (int) $summary['total_users'];

        $totalCustomers =
            (int) $summary['customers'];

        $totalVendors =
            (int) $summary['vendors'];

        $totalAdmins =
            (int) $summary['admins'];
    }

} catch (PDOException $e) {

    $errorMessage = APP_DEBUG
        ? $e->getMessage()
        : 'Unable to load user summary.';
}

/*
|--------------------------------------------------------------------------
| LOAD USERS
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT
            user_id,
            name,
            email,
            phone,
            profile_image,
            role

        FROM users

        WHERE 1 = 1
    ";

    $params = [];

    if ($search !== '') {

        $sql .= "
            AND (
                name LIKE :search
                OR email LIKE :search
                OR phone LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';
    }

    if (
        $role !== 'all' &&
        in_array(
            $role,
            ['customer', 'vendor', 'admin'],
            true
        )
    ) {

        $sql .= "
            AND role = :role
        ";

        $params[':role'] = $role;
    }

    $sql .= "
        ORDER BY user_id DESC
    ";

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $users = $stmt->fetchAll();

} catch (PDOException $e) {

    $users = [];

    $errorMessage = APP_DEBUG
        ? $e->getMessage()
        : 'Unable to load users.';
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
        Users |
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

        .admin-page {
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

        .admin-container {
            max-width: 1500px;
            margin: auto;
        }

        .admin-hero {
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

        .admin-hero::before {
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

        .admin-hero h1 {
            margin: 0 0 8px;

            font-size:
                clamp(29px,5vw,46px);

            font-weight: 950;
        }

        .admin-hero p {
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

        .message.success {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #166534;
        }

        .message.error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #991b1b;
        }

        .stats-grid {
            display: grid;

            grid-template-columns:
                repeat(4,1fr);

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
            color: #0284c7;
        }

        .stat-card:nth-child(3)
        .stat-value {
            color: #16a34a;
        }

        .stat-card:nth-child(4)
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
            display: grid;

            grid-template-columns:
                1.7fr
                1fr
                auto;

            gap: 8px;
        }

        .filter-input,
        .filter-select {
            width: 100%;

            box-sizing: border-box;

            padding: 11px 12px;

            border: 1px solid #dbeafe;

            border-radius: 11px;

            outline: none;

            background: white;

            color: #334155;

            font-size: 9px;
        }

        .filter-button {
            padding: 11px 17px;

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

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;

            min-width: 850px;

            border-collapse: collapse;
        }

        th {
            padding: 13px 18px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fbff;

            color: #64748b;

            font-size: 7px;
            font-weight: 950;

            text-align: left;

            text-transform: uppercase;
        }

        td {
            padding: 16px 18px;

            border-bottom:
                1px solid #eff6ff;

            color: #475569;

            font-size: 8px;
        }

        tbody tr:hover {
            background: #f8fbff;
        }

        .user-info {
            display: flex;

            align-items: center;

            gap: 10px;
        }

        .avatar {
            display: flex;

            align-items: center;
            justify-content: center;

            width: 37px;
            height: 37px;

            flex-shrink: 0;

            overflow: hidden;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #e0f2fe
                );

            color: #2563eb;

            font-size: 12px;
            font-weight: 950;
        }

        .avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .user-name {
            color: #0f172a;

            font-size: 9px;
            font-weight: 950;
        }

        .user-id {
            margin-top: 3px;

            color: #94a3b8;

            font-size: 7px;
        }

        .email {
            color: #475569;

            word-break: break-word;
        }

        .role-badge {
            display: inline-flex;

            padding: 6px 9px;

            border-radius: 8px;

            font-size: 7px;
            font-weight: 950;

            text-transform: uppercase;
        }

        .role-customer {
            background: #eff6ff;
            color: #2563eb;
        }

        .role-vendor {
            background: #ecfdf5;
            color: #059669;
        }

        .role-admin {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .delete-button {
            padding: 7px 10px;

            border: 1px solid #fecaca;

            border-radius: 8px;

            background: #fef2f2;

            color: #dc2626;

            cursor: pointer;

            font-size: 7px;
            font-weight: 950;
        }

        .delete-button:hover {
            background: #fee2e2;
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

        @media (max-width: 900px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2,1fr);
            }

            .filter-form {
                grid-template-columns:
                    1fr;
            }

        }

        @media (max-width: 600px) {

            .admin-page {
                padding:
                    25px 15px 50px;
            }

            .admin-hero {
                padding: 27px 21px;
            }

            .stats-grid {
                grid-template-columns:
                    1fr;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="admin-page">

    <div class="admin-container">

        <section class="admin-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    User Management
                </h1>

                <p>
                    Manage customers, vendors and
                    administrator accounts from one place.
                </p>

            </div>

        </section>

        <?php if ($successMessage !== ''): ?>

            <div class="message success">
                ✓ <?= e($successMessage) ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>

            <div class="message error">
                ⚠ <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-label">
                    Total Users
                </div>

                <div class="stat-value">
                    <?= number_format($totalUsers) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    Customers
                </div>

                <div class="stat-value">
                    <?= number_format($totalCustomers) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    Vendors
                </div>

                <div class="stat-value">
                    <?= number_format($totalVendors) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    Admins
                </div>

                <div class="stat-value">
                    <?= number_format($totalAdmins) ?>
                </div>

            </div>

        </section>

        <section class="panel">

            <div class="panel-header">

                <h2>
                    All Users
                </h2>

                <p>
                    Search and filter registered users.
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
                        placeholder="Search name, email or phone..."
                        value="<?= e($search) ?>"
                    >

                    <select
                        name="role"
                        class="filter-select"
                    >

                        <option
                            value="all"
                        >
                            All Roles
                        </option>

                        <option
                            value="customer"
                            <?= $role === 'customer'
                                ? 'selected'
                                : '' ?>
                        >
                            Customer
                        </option>

                        <option
                            value="vendor"
                            <?= $role === 'vendor'
                                ? 'selected'
                                : '' ?>
                        >
                            Vendor
                        </option>

                        <option
                            value="admin"
                            <?= $role === 'admin'
                                ? 'selected'
                                : '' ?>
                        >
                            Admin
                        </option>

                    </select>

                    <button
                        type="submit"
                        class="filter-button"
                    >
                        FILTER
                    </button>

                </form>

            </div>

            <?php if (empty($users)): ?>

                <div class="empty-state">

                    <div class="icon">
                        👥
                    </div>

                    <strong>
                        No users found
                    </strong>

                    <span>
                        Try changing your search or filter.
                    </span>

                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    User
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $users
                                as $user
                            ): ?>

                                <?php

                                $name =
                                    $user['name']
                                    ?: 'Unknown User';

                                $initial =
                                    strtoupper(
                                        substr(
                                            $name,
                                            0,
                                            1
                                        )
                                    );

                                $userRole =
                                    $user['role']
                                    ?? 'customer';

                                ?>

                                <tr>

                                    <td>

                                        <div class="user-info">

                                            <div class="avatar">

                                                <?php if (
                                                    !empty(
                                                        $user[
                                                            'profile_image'
                                                        ]
                                                    )
                                                ): ?>

                                                    <img
                                                        src="<?= e(
                                                            vendorImageUrl(
                                                                $user[
                                                                    'profile_image'
                                                                ]
                                                            )
                                                        ) ?>"
                                                        alt="<?= e(
                                                            $name
                                                        ) ?>"
                                                    >

                                                <?php else: ?>

                                                    <?= e(
                                                        $initial
                                                    ) ?>

                                                <?php endif; ?>

                                            </div>

                                            <div>

                                                <div class="user-name">
                                                    <?= e($name) ?>
                                                </div>

                                                <div class="user-id">
                                                    ID #<?= (int) $user[
                                                        'user_id'
                                                    ] ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <div class="email">
                                            <?= e(
                                                $user['email']
                                            ) ?>
                                        </div>

                                    </td>

                                    <td>
                                        <?= e(
                                            $user['phone']
                                            ?? '-'
                                        ) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="
                                                role-badge
                                                role-<?= e(
                                                    $userRole
                                                ) ?>
                                            "
                                        >
                                            <?= e(
                                                $userRole
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php if (
                                            (int) $user[
                                                'user_id'
                                            ] !==
                                            (int) $_SESSION[
                                                'user_id'
                                            ]
                                        ): ?>

                                            <form
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to delete this user?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="user_id"
                                                    value="<?= (int) $user[
                                                        'user_id'
                                                    ] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_user"
                                                    value="1"
                                                    class="delete-button"
                                                >
                                                    DELETE
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span
                                                style="
                                                    color:#94a3b8;
                                                    font-size:7px;
                                                    font-weight:800;
                                                "
                                            >
                                                CURRENT ACCOUNT
                                            </span>

                                        <?php endif; ?>

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
require_once __DIR__ . '/../includes/footer.php';
?>

</body>

</html>