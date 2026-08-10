<?php
/**
 * HOCHIPOHUB
 * Admin - Vendors Management
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

$admin_id =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| APPROVE / REJECT APPLICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['application_action'])
) {

    $application_id =
        (int) ($_POST['application_id'] ?? 0);

    $action =
        $_POST['application_action'] ?? '';


    if (
        $application_id <= 0 ||
        !in_array(
            $action,
            ['approve', 'reject'],
            true
        )
    ) {

        header("Location: vendors.php?error=invalid");
        exit;
    }


    try {

        $db->beginTransaction();


        /*
         * Get application
         */

        $stmt = $db->prepare("
            SELECT
                application_id,
                user_id,
                business_name,
                reason,
                status
            FROM vendor_applications
            WHERE application_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([
            $application_id
        ]);

        $application =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$application) {

            $db->rollBack();

            header("Location: vendors.php?error=notfound");
            exit;
        }


        /*
         * APPROVE
         */

        if ($action === 'approve') {

            /*
             * Update application
             */

            $stmt = $db->prepare("
                UPDATE vendor_applications
                SET
                    status = 'Approved',
                    reviewed_at = NOW(),
                    reviewed_by = ?
                WHERE application_id = ?
            ");

            $stmt->execute([
                $admin_id,
                $application_id
            ]);


            /*
             * Change user role
             */

            $stmt = $db->prepare("
                UPDATE users
                SET
                    role = 'vendor',
                    status = 'active'
                WHERE user_id = ?
            ");

            $stmt->execute([
                $application['user_id']
            ]);


            /*
             * Check existing vendor record
             */

            $stmt = $db->prepare("
                SELECT vendor_id
                FROM vendors
                WHERE user_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $application['user_id']
            ]);

            $existingVendor =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if ($existingVendor) {

                /*
                 * Update existing vendor
                 */

                $stmt = $db->prepare("
                    UPDATE vendors
                    SET
                        business_name = ?,
                        approval_status = 'Approved'
                    WHERE user_id = ?
                ");

                $stmt->execute([
                    $application['business_name'],
                    $application['user_id']
                ]);

            } else {

                /*
                 * Create vendor
                 */

                $stmt = $db->prepare("
                    INSERT INTO vendors
                    (
                        user_id,
                        business_name,
                        approval_status
                    )
                    VALUES (?, ?, 'Approved')
                ");

                $stmt->execute([
                    $application['user_id'],
                    $application['business_name']
                ]);
            }


            /*
             * Admin log
             */

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
                'Approved vendor application',
                'vendor_application',
                $application_id
            ]);


            $db->commit();


            header(
                "Location: vendors.php?success=approved"
            );

            exit;
        }


        /*
         * REJECT
         */

        if ($action === 'reject') {

            $stmt = $db->prepare("
                UPDATE vendor_applications
                SET
                    status = 'Rejected',
                    reviewed_at = NOW(),
                    reviewed_by = ?
                WHERE application_id = ?
            ");

            $stmt->execute([
                $admin_id,
                $application_id
            ]);


            /*
             * If vendor record already exists,
             * mark it rejected.
             */

            $stmt = $db->prepare("
                UPDATE vendors
                SET approval_status = 'Rejected'
                WHERE user_id = ?
            ");

            $stmt->execute([
                $application['user_id']
            ]);


            /*
             * Keep user as customer
             */

            $stmt = $db->prepare("
                UPDATE users
                SET
                    role = 'customer'
                WHERE user_id = ?
                  AND role != 'admin'
            ");

            $stmt->execute([
                $application['user_id']
            ]);


            /*
             * Admin log
             */

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
                'Rejected vendor application',
                'vendor_application',
                $application_id
            ]);


            $db->commit();


            header(
                "Location: vendors.php?success=rejected"
            );

            exit;
        }


    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        header(
            "Location: vendors.php?error=process"
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE VENDOR STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_vendor_status'])
) {

    $vendor_id =
        (int) ($_POST['vendor_id'] ?? 0);

    $approval_status =
        $_POST['approval_status'] ?? '';


    $allowed_status = [
        'Pending',
        'Approved',
        'Rejected',
        'Suspended'
    ];


    if (
        $vendor_id <= 0 ||
        !in_array(
            $approval_status,
            $allowed_status,
            true
        )
    ) {

        header(
            "Location: vendors.php?error=invalid"
        );

        exit;
    }


    try {

        $db->beginTransaction();


        /*
         * Get vendor user
         */

        $stmt = $db->prepare("
            SELECT user_id
            FROM vendors
            WHERE vendor_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $vendor_id
        ]);

        $vendor =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$vendor) {

            $db->rollBack();

            header(
                "Location: vendors.php?error=notfound"
            );

            exit;
        }


        /*
         * Update vendor
         */

        $stmt = $db->prepare("
            UPDATE vendors
            SET approval_status = ?
            WHERE vendor_id = ?
        ");

        $stmt->execute([
            $approval_status,
            $vendor_id
        ]);


        /*
         * Update user status
         */

        if ($approval_status === 'Suspended') {

            $stmt = $db->prepare("
                UPDATE users
                SET status = 'suspended'
                WHERE user_id = ?
                  AND role = 'vendor'
            ");

            $stmt->execute([
                $vendor['user_id']
            ]);

        } elseif ($approval_status === 'Approved') {

            $stmt = $db->prepare("
                UPDATE users
                SET
                    role = 'vendor',
                    status = 'active'
                WHERE user_id = ?
            ");

            $stmt->execute([
                $vendor['user_id']
            ]);
        }


        /*
         * Admin log
         */

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
            'Updated vendor approval status to ' .
            $approval_status,
            'vendor',
            $vendor_id
        ]);


        $db->commit();


        header(
            "Location: vendors.php?success=status"
        );

        exit;

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        header(
            "Location: vendors.php?error=update"
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH / FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$status_filter =
    $_GET['status'] ?? '';


/*
|--------------------------------------------------------------------------
| VENDORS
|--------------------------------------------------------------------------
*/

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

    $value =
        '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
}


if (
    in_array(
        $status_filter,
        [
            'Pending',
            'Approved',
            'Rejected',
            'Suspended'
        ],
        true
    )
) {

    $sql .= "
        AND v.approval_status = ?
    ";

    $params[] =
        $status_filter;
}


$sql .= "
    ORDER BY v.created_at DESC
";


$stmt =
    $db->prepare($sql);

$stmt->execute($params);

$vendors =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| APPLICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT

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

    ORDER BY va.created_at DESC
");

$applications =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*)
    FROM vendors
");

$total_vendors =
    (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM vendors
    WHERE approval_status = 'Approved'
");

$approved_vendors =
    (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM vendors
    WHERE approval_status = 'Pending'
");

$pending_vendors =
    (int) $stmt->fetchColumn();


$stmt = $db->query("
    SELECT COUNT(*)
    FROM vendors
    WHERE approval_status = 'Suspended'
");

$suspended_vendors =
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
        Vendors | HochipoHub Admin
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
                    Vendors
                </h1>

                <p>
                    Manage HochipoHub vendors and applications.
                </p>

            </div>

        </div>


        <!-- ALERT -->

        <?php if (isset($_GET['success'])): ?>

            <div class="admin-alert success">

                <?php

                switch ($_GET['success']) {

                    case 'approved':
                        echo
                            'Vendor application approved successfully.';
                        break;

                    case 'rejected':
                        echo
                            'Vendor application rejected.';
                        break;

                    case 'status':
                        echo
                            'Vendor status updated successfully.';
                        break;

                    default:
                        echo
                            'Action completed successfully.';
                }

                ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['error'])): ?>

            <div class="admin-alert error">

                Unable to process the vendor request.

            </div>

        <?php endif; ?>


        <!-- STATS -->

        <section class="admin-stats">


            <div class="stat-card">

                <span class="stat-label">
                    Total Vendors
                </span>

                <strong>
                    <?= $total_vendors ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Approved
                </span>

                <strong>
                    <?= $approved_vendors ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Pending
                </span>

                <strong>
                    <?= $pending_vendors ?>
                </strong>

            </div>


            <div class="stat-card">

                <span class="stat-label">
                    Suspended
                </span>

                <strong>
                    <?= $suspended_vendors ?>
                </strong>

            </div>


        </section>


        <!-- PENDING APPLICATIONS -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Pending Vendor Applications
                    </h2>

                    <p>
                        Review applications before approving vendors.
                    </p>

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

                    <?php if (empty($applications)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="empty-state"
                            >

                                No pending applications.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($applications as $application): ?>

                            <tr>


                                <td>

                                    #<?= (int)
                                        $application['application_id'] ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $application['applicant_name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= htmlspecialchars(
                                            $application['applicant_email']
                                        ) ?>

                                    </small>

                                    <small>

                                        <?= htmlspecialchars(
                                            $application['applicant_phone']
                                            ?? '-'
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $application['business_name']
                                    ) ?>

                                </td>


                                <td>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $application['reason']
                                            ?? '-'
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $application['created_at']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <div class="table-actions">


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= (int)
                                                    $application['application_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_action"
                                                value="approve"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-btn small"
                                                onclick="return confirm('Approve this vendor application?');"
                                            >
                                                Approve
                                            </button>

                                        </form>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= (int)
                                                    $application['application_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_action"
                                                value="reject"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-btn small danger"
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


        <!-- FILTER -->

        <section class="admin-panel">

            <form
                method="GET"
                class="admin-filter-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Search vendor or owner..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="Pending"
                        <?= $status_filter === 'Pending'
                            ? 'selected'
                            : '' ?>
                    >
                        Pending
                    </option>

                    <option
                        value="Approved"
                        <?= $status_filter === 'Approved'
                            ? 'selected'
                            : '' ?>
                    >
                        Approved
                    </option>

                    <option
                        value="Rejected"
                        <?= $status_filter === 'Rejected'
                            ? 'selected'
                            : '' ?>
                    >
                        Rejected
                    </option>

                    <option
                        value="Suspended"
                        <?= $status_filter === 'Suspended'
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
                    href="vendors.php"
                    class="admin-btn secondary"
                >
                    Reset
                </a>

            </form>

        </section>


        <!-- VENDOR LIST -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Vendor List
                    </h2>

                    <p>
                        <?= count($vendors) ?>
                        vendor(s) found
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


                    <?php if (empty($vendors)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty-state"
                            >

                                No vendors found.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($vendors as $vendor): ?>

                            <tr>


                                <td>

                                    #<?= (int)
                                        $vendor['vendor_id'] ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $vendor['business_name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= htmlspecialchars(
                                            $vendor['business_address']
                                            ?? '-'
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $vendor['owner_name']
                                        ) ?>

                                    </strong>

                                    <small>

                                        <?= htmlspecialchars(
                                            $vendor['owner_email']
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $vendor['category']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $vendor['delivery_method']
                                        ?? '-'
                                    ) ?>

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
                                            value="<?= (int)
                                                $vendor['vendor_id'] ?>"
                                        >


                                        <select
                                            name="approval_status"
                                            onchange="this.form.submit()"
                                        >

                                            <option
                                                value="Pending"
                                                <?= $vendor['approval_status'] === 'Pending'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Pending
                                            </option>


                                            <option
                                                value="Approved"
                                                <?= $vendor['approval_status'] === 'Approved'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Approved
                                            </option>


                                            <option
                                                value="Rejected"
                                                <?= $vendor['approval_status'] === 'Rejected'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Rejected
                                            </option>


                                            <option
                                                value="Suspended"
                                                <?= $vendor['approval_status'] === 'Suspended'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Suspended
                                            </option>

                                        </select>

                                    </form>

                                </td>


                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $vendor['created_at']
                                        )
                                    ) ?>

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