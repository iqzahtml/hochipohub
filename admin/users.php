<?php

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| UPDATE USER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {

    $user_id = (int) ($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';

    if (
        $user_id <= 0 ||
        !in_array($role, ['customer', 'vendor', 'admin'], true) ||
        !in_array($status, ['active', 'inactive', 'pending', 'suspended'], true)
    ) {
        header('Location: users.php?error=invalid');
        exit;
    }

    if ($user_id === $admin_id) {
        header('Location: users.php?error=self');
        exit;
    }

    try {

        $stmt = $db->prepare(
            "SELECT role, status
             FROM users
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->execute([$user_id]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            header('Location: users.php?error=notfound');
            exit;
        }

        $stmt = $db->prepare(
            "UPDATE users
             SET role = ?,
                 status = ?
             WHERE user_id = ?"
        );

        $stmt->execute([
            $role,
            $status,
            $user_id
        ]);

        $stmt = $db->prepare(
            "INSERT INTO admin_logs
                (admin_id, action, target_type, target_id)
             VALUES
                (?, ?, ?, ?)"
        );

        $stmt->execute([
            $admin_id,
            "Updated user #{$user_id} role to {$role} and status to {$status}",
            'user',
            $user_id
        ]);

        header('Location: users.php?success=updated');
        exit;

    } catch (PDOException $e) {

        error_log($e->getMessage());

        header('Location: users.php?error=update');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

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

    $v = "%$search%";

    array_push(
        $params,
        $v,
        $v,
        $v
    );
}

if (
    in_array(
        $role_filter,
        ['customer', 'vendor', 'admin'],
        true
    )
) {

    $sql .= " AND u.role = ?";
    $params[] = $role_filter;
}

if (
    in_array(
        $status_filter,
        ['active', 'inactive', 'pending', 'suspended'],
        true
    )
) {

    $sql .= " AND u.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| USER STATISTICS
|--------------------------------------------------------------------------
*/

$total_users = (int) $db
    ->query("SELECT COUNT(*) FROM users")
    ->fetchColumn();

$total_customers = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'customer'"
    )
    ->fetchColumn();

$total_vendors = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'vendor'"
    )
    ->fetchColumn();

$total_admins = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'admin'"
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

    <title>Users | HochipoHub Admin</title>

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

        <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main">

            <!-- Header -->

            <header class="admin-topbar">

                <div class="admin-header-left">

                    <button
                        type="button"
                        id="adminSidebarToggle"
                        class="admin-sidebar-toggle"
                        aria-label="Open sidebar"
                        aria-expanded="false"
                    >
                        ☰
                    </button>

                    <div>

                        <h1>Users</h1>

                        <p>
                            Manage HochipoHub user accounts.
                        </p>

                    </div>

                </div>

            </header>

            <!-- Success Message -->

            <?php if (isset($_GET['success'])): ?>

                <div class="admin-alert success">
                    User updated successfully.
                </div>

            <?php endif; ?>

            <!-- Error Message -->

            <?php if (isset($_GET['error'])): ?>

                <div class="admin-alert error">

                    <?php

                    $err = $_GET['error'];

                    echo $err === 'self'
                        ? 'You cannot modify your own administrator account from this page.'
                        : (
                            $err === 'notfound'
                                ? 'User not found.'
                                : 'Unable to process the request.'
                        );

                    ?>

                </div>

            <?php endif; ?>

            <!-- Statistics -->

            <section class="admin-stats">

                <?php
                foreach (
                    [
                        ['Total Users', $total_users],
                        ['Customers', $total_customers],
                        ['Vendors', $total_vendors],
                        ['Admins', $total_admins]
                    ] as $s
                ):
                ?>

                    <div class="stat-card">

                        <span class="stat-label">
                            <?= e($s[0]) ?>
                        </span>

                        <strong>
                            <?= number_format($s[1]) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>

            </section>

            <!-- Filter -->

            <section class="admin-panel">

                <form
                    method="GET"
                    class="admin-filter-form"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search name, email or phone..."
                        value="<?= e($search) ?>"
                    >

                    <select name="role">

                        <option value="">
                            All Roles
                        </option>

                        <?php foreach (
                            ['customer', 'vendor', 'admin'] as $r
                        ): ?>

                            <option
                                value="<?= e($r) ?>"
                                <?= $role_filter === $r ? 'selected' : '' ?>
                            >
                                <?= ucfirst($r) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <?php foreach (
                            ['active', 'inactive', 'pending', 'suspended'] as $s
                        ): ?>

                            <option
                                value="<?= e($s) ?>"
                                <?= $status_filter === $s ? 'selected' : '' ?>
                            >
                                <?= ucfirst($s) ?>
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
                        href="users.php"
                    >
                        Reset
                    </a>

                </form>

            </section>

            <!-- User List -->

            <section class="admin-panel">

                <div class="panel-header">

                    <div>

                        <h2>User List</h2>

                        <p>
                            <?= count($users) ?> user(s) found
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

                            <?php if (!$users): ?>

                                <tr>

                                    <td
                                        colspan="8"
                                        class="empty-state"
                                    >
                                        No users found.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($users as $u): ?>

                                    <tr>

                                        <td>
                                            #<?= (int) $u['user_id'] ?>
                                        </td>

                                        <td>

                                            <strong>
                                                <?= e($u['name']) ?>
                                            </strong>

                                            <small>
                                                <?= e($u['email']) ?>
                                            </small>

                                            <?php if (!empty($u['business_name'])): ?>

                                                <small>
                                                    <?= e($u['business_name']) ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= e($u['phone'] ?? '-') ?>
                                        </td>

                                        <!-- Role -->

                                        <td>

                                            <?php if (
                                                (int) $u['user_id'] === $admin_id
                                            ): ?>

                                                <span>
                                                    <?= e($u['role']) ?>
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
                                                        value="<?= (int) $u['user_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="<?= e($u['status']) ?>"
                                                    >

                                                    <select
                                                        name="role"
                                                        onchange="this.form.submit()"
                                                    >

                                                        <?php foreach (
                                                            ['customer', 'vendor', 'admin'] as $r
                                                        ): ?>

                                                            <option
                                                                value="<?= e($r) ?>"
                                                                <?= $u['role'] === $r ? 'selected' : '' ?>
                                                            >
                                                                <?= ucfirst($r) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </form>

                                            <?php endif; ?>

                                        </td>

                                        <!-- Status -->

                                        <td>

                                            <?php if (
                                                (int) $u['user_id'] === $admin_id
                                            ): ?>

                                                <span class="admin-status status-active">
                                                    <?= e($u['status']) ?>
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
                                                        value="<?= (int) $u['user_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="role"
                                                        value="<?= e($u['role']) ?>"
                                                    >

                                                    <select
                                                        name="status"
                                                        onchange="this.form.submit()"
                                                    >

                                                        <?php foreach (
                                                            ['active', 'inactive', 'pending', 'suspended'] as $s
                                                        ): ?>

                                                            <option
                                                                value="<?= e($s) ?>"
                                                                <?= $u['status'] === $s ? 'selected' : '' ?>
                                                            >
                                                                <?= ucfirst($s) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </form>

                                            <?php endif; ?>

                                        </td>

                                        <!-- MFA -->

                                        <td>
                                            <?= $u['mfa_enabled'] ? 'Enabled' : 'Disabled' ?>
                                        </td>

                                        <!-- Joined -->

                                        <td>
                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime($u['created_at'])
                                                )
                                            ) ?>
                                        </td>

                                        <!-- Action -->

                                        <td>

                                            <a
                                                class="admin-btn small"
                                                href="../profile.php?id=<?= (int) $u['user_id'] ?>"
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