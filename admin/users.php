<?php
/**
 * HOCHIPOHUB
 * Admin - Users Management
 */

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| UPDATE USER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_user'])
) {

    $user_id =
        (int) ($_POST['user_id'] ?? 0);

    $role =
        $_POST['role'] ?? '';

    $status =
        $_POST['status'] ?? '';


    $allowed_roles = [
        'customer',
        'vendor',
        'admin'
    ];

    $allowed_status = [
        'active',
        'inactive',
        'pending',
        'suspended'
    ];


    if ($user_id <= 0) {

        header("Location: users.php?error=invalid");
        exit;
    }


    if (!in_array($role, $allowed_roles, true)) {

        header("Location: users.php?error=invalid");
        exit;
    }


    if (!in_array($status, $allowed_status, true)) {

        header("Location: users.php?error=invalid");
        exit;
    }


    /*
     * Prevent admin from disabling own account.
     */

    if ($user_id === $admin_id) {

        header("Location: users.php?error=self");
        exit;
    }


    try {

        /*
         * Get old user
         */

        $stmt = $db->prepare("
            SELECT
                role,
                status
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $user_id
        ]);

        $oldUser =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$oldUser) {

            header("Location: users.php?error=notfound");
            exit;
        }


        /*
         * Update
         */

        $stmt = $db->prepare("
            UPDATE users
            SET
                role = ?,
                status = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $role,
            $status,
            $user_id
        ]);


        /*
         * Admin log
         */

        $action =
            "Updated user #{$user_id} role to {$role} and status to {$status}";


        $stmt = $db->prepare("
            INSERT INTO admin_logs
            (
                admin_id,
                action,
                target_type,
                target_id
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $admin_id,
            $action,
            'user',
            $user_id
        ]);


        header("Location: users.php?success=updated");
        exit;

    } catch (PDOException $e) {

        header("Location: users.php?error=update");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$role_filter =
    $_GET['role'] ?? '';

$status_filter =
    $_GET['status'] ?? '';


/*
|--------------------------------------------------------------------------
| USER QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        u.user_id,
        u.name,
        u.email,
        u.phone,
        u.role,
        u.status,
        u.mfa_enabled,
        u.created_at,

        v.vendor_id,
        v.business_name,
        v.approval_status

    FROM users u

    LEFT JOIN vendors v
        ON u.user_id = v.user_id

    WHERE 1 = 1
";

$params = [];


if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )
    ";

    $value =
        '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
}


if (
    in_array(
        $role_filter,
        [
            'customer',
            'vendor',
            'admin'
        ],
        true
    )
) {

    $sql .= "
        AND u.role = ?
    ";

    $params[] =
        $role_filter;
}


if (
    in_array(
        $status_filter,
        [
            'active',
            'inactive',
            'pending',
            'suspended'
        ],
        true
    )
) {

    $sql .= "
        AND u.status = ?
    ";

    $params[] =
        $status_filter;
}


$sql .= "
    ORDER BY u.created_at DESC
";


$stmt =
    $db->prepare($sql);

$stmt->execute($params);

$users =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*)
    FROM users
");

$total_users =
    (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$total_customers =
    (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'vendor'
");

$total_vendors =
    (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'admin'
");

$total_admins =
    (int) $stmt->fetchColumn();

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
        Users | HochipoHub Admin
    </title>

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

</head>

<body>

<div class="admin-wrapper">

    <?php

    $sidebar =
        dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    if (file_exists($sidebar)) {
        require_once $sidebar;
    }

    ?>


    <main class="admin-main">


        <div class="admin-topbar">

            <div>

                <h1>
                    Users
                </h1>

                <p>
                    Manage HochipoHub user accounts.
                </p>

            </div>

        </div>


        <?php if (isset($_GET['success'])): ?>

            <div class="admin-alert success">

                User updated successfully.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['error'])): ?>

            <div class="admin-alert error">

                <?php

                $error =
                    $_GET['error'];

                if ($error === 'self') {

                    echo
                        'You cannot modify your own administrator account from this page.';

                } elseif ($error === 'notfound') {

                    echo
                        'User not found.';

                } else {

                    echo
                        'Unable to process the request.';
                }

                ?>

            </div>

        <?php endif; ?>


        <!-- STATISTICS -->

        <section class="admin-stats">


            <div class="stat-card">

                <span class="stat-label">
                    Total Users
                </span>

                <strong>
                    <?= $total_users ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Customers
                </span>

                <strong>
                    <?= $total_customers ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Vendors
                </span>

                <strong>
                    <?= $total_vendors ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Admins
                </span>

                <strong>
                    <?= $total_admins ?>
                </strong>

            </div>


        </section>


        <!-- FILTER -->

        <section class="admin-panel">

            <form
                method="GET"
                class="admin-filter-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Search name, email or phone..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select name="role">

                    <option value="">
                        All Roles
                    </option>

                    <option
                        value="customer"
                        <?= $role_filter === 'customer'
                            ? 'selected'
                            : '' ?>
                    >
                        Customer
                    </option>

                    <option
                        value="vendor"
                        <?= $role_filter === 'vendor'
                            ? 'selected'
                            : '' ?>
                    >
                        Vendor
                    </option>

                    <option
                        value="admin"
                        <?= $role_filter === 'admin'
                            ? 'selected'
                            : '' ?>
                    >
                        Admin
                    </option>

                </select>


                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="active"
                        <?= $status_filter === 'active'
                            ? 'selected'
                            : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= $status_filter === 'inactive'
                            ? 'selected'
                            : '' ?>
                    >
                        Inactive
                    </option>

                    <option
                        value="pending"
                        <?= $status_filter === 'pending'
                            ? 'selected'
                            : '' ?>
                    >
                        Pending
                    </option>

                    <option
                        value="suspended"
                        <?= $status_filter === 'suspended'
                            ? 'selected'
                            : '' ?>
                    >
                        Suspended
                    </option>

                </select>


                <button
                    type="submit"
                    class="admin-btn primary"
                >
                    Search
                </button>


                <a
                    href="users.php"
                    class="admin-btn secondary"
                >
                    Reset
                </a>

            </form>

        </section>


        <!-- USERS TABLE -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        User List
                    </h2>

                    <p>
                        <?= count($users) ?>
                        user(s) found
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>ID</th>

                        <th>User</th>

                        <th>Phone</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>MFA</th>

                        <th>Joined</th>

                        <th>Action</th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($users)): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="empty-state"
                            >
                                No users found.
                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    #<?= (int) $user['user_id'] ?>
                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $user['name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= htmlspecialchars(
                                            $user['email']
                                        ) ?>

                                    </small>

                                    <?php if (!empty($user['business_name'])): ?>

                                        <small>

                                            <?= htmlspecialchars(
                                                $user['business_name']
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user['phone'] ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <form
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="update_user"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['user_id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="<?= htmlspecialchars(
                                                $user['status']
                                            ) ?>"
                                        >

                                        <select
                                            name="role"
                                            onchange="this.form.submit()"
                                            <?= (int) $user['user_id'] === $admin_id
                                                ? 'disabled'
                                                : '' ?>
                                        >

                                            <option
                                                value="customer"
                                                <?= $user['role'] === 'customer'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Customer
                                            </option>

                                            <option
                                                value="vendor"
                                                <?= $user['role'] === 'vendor'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Vendor
                                            </option>

                                            <option
                                                value="admin"
                                                <?= $user['role'] === 'admin'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Admin
                                            </option>

                                        </select>

                                    </form>

                                </td>


                                <td>

                                    <?php if (
                                        (int) $user['user_id'] ===
                                        $admin_id
                                    ): ?>

                                        <span>
                                            <?= htmlspecialchars(
                                                $user['status']
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <form
                                            method="POST"
                                            class="inline-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="update_user"
                                                value="1"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= (int) $user['user_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="role"
                                                value="<?= htmlspecialchars(
                                                    $user['role']
                                                ) ?>"
                                            >

                                            <select
                                                name="status"
                                                onchange="this.form.submit()"
                                            >

                                                <option
                                                    value="active"
                                                    <?= $user['status'] === 'active'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Active
                                                </option>

                                                <option
                                                    value="inactive"
                                                    <?= $user['status'] === 'inactive'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Inactive
                                                </option>

                                                <option
                                                    value="pending"
                                                    <?= $user['status'] === 'pending'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Pending
                                                </option>

                                                <option
                                                    value="suspended"
                                                    <?= $user['status'] === 'suspended'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Suspended
                                                </option>

                                            </select>

                                        </form>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= $user['mfa_enabled']
                                        ? 'Enabled'
                                        : 'Disabled'
                                    ?>

                                </td>


                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <a
                                        href="../profile.php?id=<?= (int) $user['user_id'] ?>"
                                        class="admin-btn small"
                                        target="_blank"
                                    >
                                        View
                                    </a>

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