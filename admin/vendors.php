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
| APPROVE / REJECT VENDOR APPLICATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_action'])) {

    $application_id = (int) ($_POST['application_id'] ?? 0);
    $action = $_POST['application_action'] ?? '';

    if (
        $application_id <= 0 ||
        !in_array($action, ['approve', 'reject'], true)
    ) {
        header('Location: vendors.php?error=invalid');
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare(
            "SELECT application_id, user_id, business_name, reason, status
             FROM vendor_applications
             WHERE application_id = ?
             LIMIT 1
             FOR UPDATE"
        );

        $stmt->execute([$application_id]);
        $a = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$a) {
            $db->rollBack();
            header('Location: vendors.php?error=notfound');
            exit;
        }

        if ($action === 'approve') {

            $stmt = $db->prepare(
                "UPDATE vendor_applications
                 SET status = 'Approved',
                     reviewed_at = NOW(),
                     reviewed_by = ?
                 WHERE application_id = ?"
            );

            $stmt->execute([
                $admin_id,
                $application_id
            ]);

            $stmt = $db->prepare(
                "UPDATE users
                 SET role = 'vendor',
                     status = 'active'
                 WHERE user_id = ?"
            );

            $stmt->execute([$a['user_id']]);

            $stmt = $db->prepare(
                "SELECT vendor_id
                 FROM vendors
                 WHERE user_id = ?
                 LIMIT 1"
            );

            $stmt->execute([$a['user_id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {

                $stmt = $db->prepare(
                    "UPDATE vendors
                     SET business_name = ?,
                         approval_status = 'Approved'
                     WHERE user_id = ?"
                );

                $stmt->execute([
                    $a['business_name'],
                    $a['user_id']
                ]);

            } else {

                $stmt = $db->prepare(
                    "INSERT INTO vendors
                        (user_id, business_name, approval_status)
                     VALUES
                        (?, ?, 'Approved')"
                );

                $stmt->execute([
                    $a['user_id'],
                    $a['business_name']
                ]);
            }

            $log = 'Approved vendor application';

        } else {

            $stmt = $db->prepare(
                "UPDATE vendor_applications
                 SET status = 'Rejected',
                     reviewed_at = NOW(),
                     reviewed_by = ?
                 WHERE application_id = ?"
            );

            $stmt->execute([
                $admin_id,
                $application_id
            ]);

            $stmt = $db->prepare(
                "UPDATE vendors
                 SET approval_status = 'Rejected'
                 WHERE user_id = ?"
            );

            $stmt->execute([$a['user_id']]);

            $stmt = $db->prepare(
                "UPDATE users
                 SET role = 'customer'
                 WHERE user_id = ?
                 AND role != 'admin'"
            );

            $stmt->execute([$a['user_id']]);

            $log = 'Rejected vendor application';
        }

        $stmt = $db->prepare(
            "INSERT INTO admin_logs
                (admin_id, action, target_type, target_id)
             VALUES
                (?, ?, ?, ?)"
        );

        $stmt->execute([
            $admin_id,
            $log,
            'vendor_application',
            $application_id
        ]);

        $db->commit();

        header(
            'Location: vendors.php?success=' .
            ($action === 'approve' ? 'approved' : 'rejected')
        );
        exit;

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log($e->getMessage());

        header('Location: vendors.php?error=process');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE VENDOR STATUS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vendor_status'])) {

    $vendor_id = (int) ($_POST['vendor_id'] ?? 0);
    $approval = $_POST['approval_status'] ?? '';

    $allowed = [
        'Pending',
        'Approved',
        'Rejected',
        'Suspended'
    ];

    if (
        $vendor_id <= 0 ||
        !in_array($approval, $allowed, true)
    ) {
        header('Location: vendors.php?error=invalid');
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare(
            "SELECT user_id
             FROM vendors
             WHERE vendor_id = ?
             LIMIT 1"
        );

        $stmt->execute([$vendor_id]);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$v) {
            $db->rollBack();
            header('Location: vendors.php?error=notfound');
            exit;
        }

        $stmt = $db->prepare(
            "UPDATE vendors
             SET approval_status = ?
             WHERE vendor_id = ?"
        );

        $stmt->execute([
            $approval,
            $vendor_id
        ]);

        if ($approval === 'Suspended') {

            $stmt = $db->prepare(
                "UPDATE users
                 SET status = 'suspended'
                 WHERE user_id = ?
                 AND role = 'vendor'"
            );

            $stmt->execute([$v['user_id']]);

        } elseif ($approval === 'Approved') {

            $stmt = $db->prepare(
                "UPDATE users
                 SET role = 'vendor',
                     status = 'active'
                 WHERE user_id = ?"
            );

            $stmt->execute([$v['user_id']]);
        }

        $stmt = $db->prepare(
            "INSERT INTO admin_logs
                (admin_id, action, target_type, target_id)
             VALUES
                (?, ?, ?, ?)"
        );

        $stmt->execute([
            $admin_id,
            'Updated vendor approval status to ' . $approval,
            'vendor',
            $vendor_id
        ]);

        $db->commit();

        header('Location: vendors.php?success=status');
        exit;

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log($e->getMessage());

        header('Location: vendors.php?error=update');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GET VENDOR DATA
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$sql = "
    SELECT
        v.vendor_id,
        v.user_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.business_address,
        v.category,
        v.delivery_method,
        v.approval_status,
        v.created_at,
        u.name AS owner_name,
        u.email AS owner_email,
        u.phone AS owner_phone,
        u.status AS user_status
    FROM vendors v
    INNER JOIN users u
        ON v.user_id = u.user_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            v.business_name LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $x = "%$search%";

    array_push(
        $params,
        $x,
        $x,
        $x
    );
}

if (
    in_array(
        $status_filter,
        ['Pending', 'Approved', 'Rejected', 'Suspended'],
        true
    )
) {

    $sql .= " AND v.approval_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY v.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PENDING APPLICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $db->query(
    "SELECT
        va.application_id,
        va.user_id,
        va.business_name,
        va.reason,
        va.status,
        va.created_at,
        u.name AS applicant_name,
        u.email AS applicant_email,
        u.phone AS applicant_phone
     FROM vendor_applications va
     INNER JOIN users u
        ON va.user_id = u.user_id
     WHERE va.status = 'Pending'
     ORDER BY va.created_at DESC"
);

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| VENDOR STATISTICS
|--------------------------------------------------------------------------
*/

$total_vendors = (int) $db
    ->query("SELECT COUNT(*) FROM vendors")
    ->fetchColumn();

$approved_vendors = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM vendors
         WHERE approval_status = 'Approved'"
    )
    ->fetchColumn();

$pending_vendors = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM vendors
         WHERE approval_status = 'Pending'"
    )
    ->fetchColumn();

$suspended_vendors = (int) $db
    ->query(
        "SELECT COUNT(*)
         FROM vendors
         WHERE approval_status = 'Suspended'"
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

    <title>Vendors | HochipoHub Admin</title>

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
                        <h1>Vendors</h1>
                        <p>Manage HochipoHub vendors and applications.</p>
                    </div>

                </div>

            </header>

            <?php if (isset($_GET['success'])): ?>

                <div class="admin-alert success">

                    <?php
                    $s = $_GET['success'];

                    echo $s === 'approved'
                        ? 'Vendor application approved successfully.'
                        : (
                            $s === 'rejected'
                                ? 'Vendor application rejected.'
                                : (
                                    $s === 'status'
                                        ? 'Vendor status updated successfully.'
                                        : 'Action completed successfully.'
                                )
                        );
                    ?>

                </div>

            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>

                <div class="admin-alert error">
                    Unable to process the vendor request.
                </div>

            <?php endif; ?>

            <!-- Statistics -->

            <section class="admin-stats">

                <?php
                foreach (
                    [
                        ['Total Vendors', $total_vendors],
                        ['Approved', $approved_vendors],
                        ['Pending', $pending_vendors],
                        ['Suspended', $suspended_vendors]
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

            <!-- Pending Vendor Applications -->

            <section class="admin-panel">

                <div class="panel-header">

                    <div>
                        <h2>Pending Vendor Applications</h2>
                        <p>Review applications before approving vendors.</p>
                    </div>

                </div>

                <div class="table-wrapper">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Applicant</th>
                                <th>Business</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!$applications): ?>

                                <tr>
                                    <td
                                        colspan="6"
                                        class="empty-state"
                                    >
                                        No pending applications.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($applications as $a): ?>

                                    <tr>

                                        <td>
                                            #<?= (int) $a['application_id'] ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= e($a['applicant_name']) ?>
                                            </strong>

                                            <small>
                                                <?= e($a['applicant_email']) ?>
                                            </small>

                                            <small>
                                                <?= e($a['applicant_phone'] ?? '-') ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= e($a['business_name']) ?>
                                        </td>

                                        <td>
                                            <?= nl2br(e($a['reason'] ?? '-')) ?>
                                        </td>

                                        <td>
                                            <?= e(date('d M Y', strtotime($a['created_at']))) ?>
                                        </td>

                                        <td>

                                            <div class="table-actions">

                                                <form
                                                    method="POST"
                                                    class="inline-form"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="application_id"
                                                        value="<?= (int) $a['application_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="application_action"
                                                        value="approve"
                                                    >

                                                    <button
                                                        class="admin-btn small"
                                                        type="submit"
                                                        onclick="return confirm('Approve this vendor application?');"
                                                    >
                                                        Approve
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    class="inline-form"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="application_id"
                                                        value="<?= (int) $a['application_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="application_action"
                                                        value="reject"
                                                    >

                                                    <button
                                                        class="admin-btn small danger"
                                                        type="submit"
                                                        onclick="return confirm('Reject this vendor application?');"
                                                    >
                                                        Reject
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

            <!-- Filter -->

            <section class="admin-panel">

                <form
                    method="GET"
                    class="admin-filter-form"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search vendor or owner..."
                        value="<?= e($search) ?>"
                    >

                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <?php foreach (
                            ['Pending', 'Approved', 'Rejected', 'Suspended']
                            as $s
                        ): ?>

                            <option
                                value="<?= e($s) ?>"
                                <?= $status_filter === $s ? 'selected' : '' ?>
                            >
                                <?= e($s) ?>
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
                        href="vendors.php"
                    >
                        Reset
                    </a>

                </form>

            </section>

            <!-- Vendor List -->

            <section class="admin-panel">

                <div class="panel-header">

                    <div>
                        <h2>Vendor List</h2>

                        <p>
                            <?= count($vendors) ?> vendor(s) found
                        </p>
                    </div>

                </div>

                <div class="table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Business</th>
                                <th>Owner</th>
                                <th>Category</th>
                                <th>Delivery</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!$vendors): ?>

                                <tr>
                                    <td
                                        colspan="7"
                                        class="empty-state"
                                    >
                                        No vendors found.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($vendors as $v): ?>

                                    <tr>

                                        <td>
                                            #<?= (int) $v['vendor_id'] ?>
                                        </td>

                                        <td>

                                            <strong>
                                                <?= e($v['business_name']) ?>
                                            </strong>

                                            <small>
                                                <?= e($v['business_address'] ?? '-') ?>
                                            </small>

                                        </td>

                                        <td>

                                            <strong>
                                                <?= e($v['owner_name']) ?>
                                            </strong>

                                            <small>
                                                <?= e($v['owner_email']) ?>
                                            </small>

                                        </td>

                                        <td>
                                            <?= e($v['category'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= e($v['delivery_method'] ?? '-') ?>
                                        </td>

                                        <td>

                                            <form
                                                method="POST"
                                                class="inline-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="update_vendor_status"
                                                    value="1"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="vendor_id"
                                                    value="<?= (int) $v['vendor_id'] ?>"
                                                >

                                                <select
                                                    name="approval_status"
                                                    onchange="this.form.submit()"
                                                >

                                                    <?php foreach (
                                                        ['Pending', 'Approved', 'Rejected', 'Suspended']
                                                        as $s
                                                    ): ?>

                                                        <option
                                                            value="<?= e($s) ?>"
                                                            <?= $v['approval_status'] === $s ? 'selected' : '' ?>
                                                        >
                                                            <?= e($s) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </form>

                                        </td>

                                        <td>
                                            <?= e(date('d M Y', strtotime($v['created_at']))) ?>
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