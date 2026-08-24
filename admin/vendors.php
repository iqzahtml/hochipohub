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

    <!-- =========================================================
         POPPINS FONT
    ========================================================== -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >

    <style>

        /* =========================================================
           POPPINS - GLOBAL PAGE FONT
        ========================================================= */

        html,
        body,
        button,
        input,
        select,
        textarea {

            font-family:
                'Poppins',
                sans-serif;
        }

        .vendors-page,
        .vendors-page *,
        .vendors-topbar,
        .vendors-topbar * {

            font-family:
                'Poppins',
                sans-serif;
        }


        /* =========================================================
           HOCHIPOHUB VENDORS PAGE
           PREMIUM BLUE ADMIN UI
        ========================================================= */

        .vendors-page {

            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --blue-deep: #172554;
            --blue-light: #eff6ff;
            --blue-soft: #dbeafe;
            --cyan: #38bdf8;

            --text: #172033;
            --muted: #71809a;
            --border: #e6ebf2;
            --background: #f6f8fc;
            --white: #ffffff;

            padding: 0 34px 55px;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .vendors-topbar {

            min-height: 92px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            margin: 0 -34px 28px;
            padding: 0 34px;

            background: rgba(255,255,255,.96);

            border-bottom: 1px solid #edf0f5;
        }

        .vendors-header-left {

            display: flex;
            align-items: center;

            gap: 18px;
        }

        .vendors-header-text h1 {

            margin: 0;

            font-size: 31px;
            line-height: 1.1;

            font-weight: 800;

            color: #111827;

            letter-spacing: -.8px;
        }

        .vendors-header-text p {

            margin: 7px 0 0;

            color: var(--muted);

            font-size: 14px;

            font-weight: 500;
        }


        /* =========================================================
           HERO
        ========================================================= */

        .vendors-hero {

            position: relative;

            overflow: hidden;

            min-height: 225px;

            display: flex;
            align-items: center;

            padding: 38px 42px;

            border-radius: 28px;

            background:
                radial-gradient(
                    circle at 88% 15%,
                    rgba(96,165,250,.42),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 74% 100%,
                    rgba(56,189,248,.25),
                    transparent 34%
                ),
                linear-gradient(
                    135deg,
                    #172554 0%,
                    #1d4ed8 48%,
                    #2563eb 100%
                );

            box-shadow:
                0 20px 50px rgba(37,99,235,.22);

            margin-bottom: 32px;
        }

        .vendors-hero::before {

            content: "";

            position: absolute;

            width: 330px;
            height: 330px;

            right: -100px;
            top: -130px;

            border: 1px solid rgba(255,255,255,.14);

            border-radius: 50%;
        }

        .vendors-hero::after {

            content: "";

            position: absolute;

            width: 240px;
            height: 240px;

            right: 40px;
            bottom: -145px;

            border: 1px solid rgba(255,255,255,.10);

            border-radius: 50%;
        }

        .vendors-hero-content {

            position: relative;

            z-index: 2;

            max-width: 760px;
        }

        .vendors-hero-label {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            padding: 8px 14px;

            margin-bottom: 17px;

            border-radius: 999px;

            border: 1px solid rgba(255,255,255,.20);

            background: rgba(255,255,255,.10);

            color: white;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1px;

            text-transform: uppercase;

            backdrop-filter: blur(10px);
        }

        .vendors-hero-label i {

            width: 8px;
            height: 8px;

            display: inline-block;

            border-radius: 50%;

            background: #bfdbfe;

            box-shadow:
                0 0 0 5px rgba(191,219,254,.13);
        }

        .vendors-hero h2 {

            margin: 0;

            color: #ffffff;

            font-size: clamp(30px, 3vw, 44px);

            line-height: 1.05;

            font-weight: 800;

            letter-spacing: -1.5px;
        }

        .vendors-hero p {

            max-width: 700px;

            margin: 15px 0 0;

            color: rgba(255,255,255,.82);

            font-size: 14px;

            line-height: 1.7;

            font-weight: 500;
        }

        .vendors-hero-number {

            position: absolute;

            z-index: 3;

            right: 65px;
            top: 50%;

            transform: translateY(-50%);

            text-align: center;
        }

        .vendors-hero-number strong {

            display: block;

            color: white;

            font-size: 68px;

            line-height: 1;

            font-weight: 800;

            letter-spacing: -4px;
        }

        .vendors-hero-number span {

            display: block;

            margin-top: 8px;

            color: rgba(255,255,255,.70);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.2px;

            text-transform: uppercase;
        }


        /* =========================================================
           STATISTICS
        ========================================================= */

        .vendors-stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0,1fr));

            gap: 17px;

            margin-bottom: 35px;
        }

        .vendor-stat-card {

            position: relative;

            overflow: hidden;

            min-height: 145px;

            padding: 23px;

            background: white;

            border: 1px solid var(--border);

            border-radius: 20px;

            box-shadow:
                0 7px 22px rgba(30,50,90,.055);

            transition: .25s ease;
        }

        .vendor-stat-card:hover {

            transform: translateY(-4px);

            box-shadow:
                0 15px 32px rgba(30,50,90,.10);
        }

        .vendor-stat-card::after {

            content: "";

            position: absolute;

            width: 110px;
            height: 110px;

            right: -45px;
            bottom: -50px;

            border-radius: 50%;

            background: var(--blue-light);
        }

        .vendor-stat-top {

            position: relative;

            z-index: 2;

            display: flex;

            justify-content: space-between;
        }

        .vendor-stat-icon {

            width: 45px;
            height: 45px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: var(--blue-light);

            color: var(--blue);
        }

        .vendor-stat-icon svg {

            width: 22px;
            height: 22px;
        }

        .vendor-stat-card strong {

            position: relative;

            z-index: 2;

            display: block;

            margin-top: 17px;

            color: #111827;

            font-size: 31px;

            line-height: 1;

            font-weight: 800;
        }

        .vendor-stat-card span {

            position: relative;

            z-index: 2;

            display: block;

            margin-top: 7px;

            color: var(--muted);

            font-size: 12px;

            font-weight: 600;
        }

        .vendor-stat-card.pending .vendor-stat-icon {

            color: #d97706;

            background: #fff7ed;
        }

        .vendor-stat-card.suspended .vendor-stat-icon {

            color: #dc2626;

            background: #fef2f2;
        }


        /* =========================================================
           PANEL
        ========================================================= */

        .vendors-panel {

            overflow: hidden;

            margin-bottom: 25px;

            background: white;

            border: 1px solid var(--border);

            border-radius: 22px;

            box-shadow:
                0 8px 28px rgba(30,50,90,.055);
        }

        .vendors-panel-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 23px 25px;

            border-bottom: 1px solid #edf0f5;
        }

        .vendors-panel-title {

            display: flex;

            align-items: center;

            gap: 13px;
        }

        .vendors-panel-icon {

            width: 42px;
            height: 42px;

            display: flex;

            align-items: center;
            justify-content: center;

            color: var(--blue);

            background: var(--blue-light);

            border-radius: 12px;
        }

        .vendors-panel-icon svg {

            width: 21px;
            height: 21px;
        }

        .vendors-panel-header h2 {

            margin: 0;

            color: #172033;

            font-size: 17px;

            font-weight: 800;
        }

        .vendors-panel-header p {

            margin: 4px 0 0;

            color: var(--muted);

            font-size: 12px;

            font-weight: 500;
        }

        .vendor-count-badge {

            padding: 7px 11px;

            color: var(--blue);

            background: var(--blue-light);

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;
        }


        /* =========================================================
           ALERT
        ========================================================= */

        .vendors-alert {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 14px 17px;

            margin-bottom: 22px;

            border-radius: 14px;

            font-size: 13px;

            font-weight: 600;
        }

        .vendors-alert.success {

            color: #166534;

            background: #f0fdf4;

            border: 1px solid #bbf7d0;
        }

        .vendors-alert.error {

            color: #991b1b;

            background: #fef2f2;

            border: 1px solid #fecaca;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .vendors-table-wrapper {

            width: 100%;

            overflow-x: auto;
        }

        .vendors-table {

            width: 100%;

            min-width: 850px;

            border-collapse: collapse;
        }

        .vendors-table th {

            padding: 14px 20px;

            text-align: left;

            background: #f8fafc;

            border-bottom: 1px solid #e8edf4;

            color: #7b89a2;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .9px;

            text-transform: uppercase;

            white-space: nowrap;
        }

        .vendors-table td {

            padding: 18px 20px;

            border-bottom: 1px solid #eef1f5;

            color: #27344a;

            font-size: 12px;

            font-weight: 500;

            vertical-align: middle;
        }

        .vendors-table tbody tr {

            transition: .2s ease;
        }

        .vendors-table tbody tr:hover {

            background: #f8fbff;
        }

        .vendors-table tbody tr:last-child td {

            border-bottom: none;
        }


        /* =========================================================
           VENDOR PERSON
        ========================================================= */

        .vendor-person {

            display: flex;

            align-items: center;

            gap: 11px;
        }

        .vendor-avatar {

            width: 38px;
            height: 38px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 11px;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #60a5fa
                );

            font-size: 13px;

            font-weight: 800;
        }

        .vendor-person-info strong {

            display: block;

            color: #172033;

            font-size: 12px;

            font-weight: 800;
        }

        .vendor-person-info small {

            display: block;

            margin-top: 3px;

            color: #8a96a9;

            font-size: 10px;
        }


        /* =========================================================
           BUSINESS
        ========================================================= */

        .vendor-business strong {

            display: block;

            color: #172033;

            font-weight: 800;
        }

        .vendor-business small {

            display: block;

            max-width: 210px;

            margin-top: 4px;

            color: #8a96a9;

            font-size: 10px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .vendor-id {

            color: var(--blue);

            font-weight: 800;
        }

        .vendor-category {

            display: inline-flex;

            padding: 6px 9px;

            border-radius: 8px;

            background: #f1f5f9;

            color: #526176;

            font-size: 10px;

            font-weight: 700;
        }

        .vendor-delivery {

            color: #5c6b82;

            font-size: 11px;

            font-weight: 600;
        }


        /* =========================================================
           STATUS SELECT
        ========================================================= */

        .vendor-status-form {

            display: inline-block;
        }

        .vendor-status-select {

            min-width: 116px;

            padding: 8px 28px 8px 11px;

            border-radius: 9px;

            border: 1px solid #dce3ed;

            background: #f8fafc;

            color: #334155;

            font-family: 'Poppins', sans-serif;

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;

            outline: none;

            transition: .2s ease;
        }

        .vendor-status-select:hover,
        .vendor-status-select:focus {

            border-color: var(--blue);

            background: white;

            box-shadow:
                0 0 0 3px rgba(37,99,235,.10);
        }

        .vendor-status-select.approved {

            color: #166534;

            background: #f0fdf4;

            border-color: #bbf7d0;
        }

        .vendor-status-select.pending {

            color: #92400e;

            background: #fffbeb;

            border-color: #fde68a;
        }

        .vendor-status-select.rejected {

            color: #991b1b;

            background: #fef2f2;

            border-color: #fecaca;
        }

        .vendor-status-select.suspended {

            color: #7f1d1d;

            background: #fef2f2;

            border-color: #fecaca;
        }


        /* =========================================================
           FILTER
        ========================================================= */

        .vendors-filter {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 21px 25px;

            background: #ffffff;
        }

        .vendors-search {

            position: relative;

            flex: 1;
        }

        .vendors-search svg {

            position: absolute;

            left: 14px;
            top: 50%;

            width: 17px;
            height: 17px;

            transform: translateY(-50%);

            color: #8b98aa;

            pointer-events: none;
        }

        .vendors-search input {

            width: 100%;

            height: 44px;

            box-sizing: border-box;

            padding: 0 15px 0 42px;

            border: 1px solid #dfe5ed;

            border-radius: 11px;

            outline: none;

            color: #172033;

            background: #f8fafc;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 500;

            transition: .2s ease;
        }

        .vendors-search input:focus {

            border-color: var(--blue);

            background: white;

            box-shadow:
                0 0 0 3px rgba(37,99,235,.09);
        }

        .vendors-filter select {

            width: 155px;

            height: 44px;

            padding: 0 12px;

            border: 1px solid #dfe5ed;

            border-radius: 11px;

            outline: none;

            background: #f8fafc;

            color: #526176;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }

        .vendors-filter select:focus {

            border-color: var(--blue);

            box-shadow:
                0 0 0 3px rgba(37,99,235,.09);
        }

        .vendors-filter-btn {

            height: 44px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 0 19px;

            border: none;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: white;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 7px 16px rgba(37,99,235,.20);

            transition: .2s ease;
        }

        .vendors-filter-btn:hover {

            transform: translateY(-1px);

            box-shadow:
                0 10px 20px rgba(37,99,235,.27);
        }

        .vendors-reset {

            height: 44px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 0 16px;

            border: 1px solid #dfe5ed;

            border-radius: 11px;

            color: #526176;

            background: white;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 700;

            text-decoration: none;

            transition: .2s ease;
        }

        .vendors-reset:hover {

            color: var(--blue);

            border-color: #bfdbfe;

            background: var(--blue-light);
        }


        /* =========================================================
           APPLICATION ACTIONS
        ========================================================= */

        .application-actions {

            display: flex;

            align-items: center;

            gap: 7px;
        }

        .application-btn {

            height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding: 0 12px;

            border-radius: 9px;

            font-family: 'Poppins', sans-serif;

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;

            transition: .2s ease;
        }

        .application-btn.approve {

            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .application-btn.approve:hover {

            color: white;

            background: #16a34a;

            border-color: #16a34a;
        }

        .application-btn.reject {

            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #dc2626;
        }

        .application-btn.reject:hover {

            color: white;

            background: #dc2626;

            border-color: #dc2626;
        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .vendor-empty {

            padding: 55px 20px !important;

            text-align: center !important;

            color: #94a3b8 !important;
        }

        .vendor-empty-icon {

            width: 54px;
            height: 54px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin: 0 auto 13px;

            border-radius: 16px;

            background: #f1f5f9;

            color: #94a3b8;
        }

        .vendor-empty-icon svg {

            width: 25px;
            height: 25px;
        }

        .vendor-empty strong {

            display: block;

            color: #526176;

            font-size: 13px;

            font-weight: 800;
        }

        .vendor-empty span {

            display: block;

            margin-top: 5px;

            font-size: 11px;

            color: #9aa6b7;
        }


        /* =========================================================
           DATE
        ========================================================= */

        .vendor-date {

            color: #64748b;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {

            .vendors-page {

                padding-left: 25px;
                padding-right: 25px;
            }

            .vendors-topbar {

                margin-left: -25px;
                margin-right: -25px;

                padding-left: 25px;
                padding-right: 25px;
            }

            .vendors-hero-number {

                right: 40px;
            }

            .vendors-stats {

                grid-template-columns:
                    repeat(2, minmax(0,1fr));
            }
        }


        @media (max-width: 900px) {

            .vendors-hero {

                padding: 30px;
            }

            .vendors-hero-number {

                display: none;
            }

            .vendors-filter {

                flex-wrap: wrap;
            }

            .vendors-search {

                flex: 1 1 100%;
            }

            .vendors-filter select {

                flex: 1;
            }
        }


        @media (max-width: 650px) {

            .vendors-page {

                padding: 0 15px 40px;
            }

            .vendors-topbar {

                margin-left: -15px;
                margin-right: -15px;

                padding-left: 15px;
                padding-right: 15px;

                min-height: 78px;
            }

            .vendors-header-text h1 {

                font-size: 25px;
            }

            .vendors-header-text p {

                font-size: 12px;
            }

            .vendors-hero {

                min-height: auto;

                padding: 27px 23px;

                border-radius: 21px;
            }

            .vendors-hero h2 {

                font-size: 29px;
            }

            .vendors-hero p {

                font-size: 12px;
            }

            .vendors-stats {

                grid-template-columns: 1fr;

                gap: 12px;
            }

            .vendors-section-heading {

                align-items: flex-start;

                flex-direction: column;
            }

            .vendors-panel-header {

                align-items: flex-start;

                flex-direction: column;

                padding: 19px;
            }

            .vendors-filter {

                padding: 17px;

                flex-direction: column;

                align-items: stretch;
            }

            .vendors-filter select {

                width: 100%;
            }

            .vendors-filter-btn,
            .vendors-reset {

                width: 100%;
            }

            .vendors-table th,
            .vendors-table td {

                padding-left: 14px;
                padding-right: 14px;
            }
        }

    </style>

</head>


<body>

<div class="admin-wrapper">

    <?php require_once dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>


    <main class="admin-main">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <header class="vendors-topbar">

            <div class="vendors-header-left">

                <button
                    type="button"
                    id="adminSidebarToggle"
                    class="admin-sidebar-toggle"
                    aria-label="Open sidebar"
                    aria-expanded="false"
                >
                    ☰
                </button>

                <div class="vendors-header-text">

                    <h1>Vendors</h1>

                    <p>
                        Manage marketplace sellers and vendor applications.
                    </p>

                </div>

            </div>

        </header>


        <div class="vendors-page">


            <!-- =================================================
                 ALERT
            ================================================== -->

            <?php if (isset($_GET['success'])): ?>

                <div class="vendors-alert success">

                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>

                    <span>

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

                    </span>

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['error'])): ?>

                <div class="vendors-alert error">

                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        />

                        <path d="M12 8v4"/>

                        <path d="M12 16h.01"/>
                    </svg>

                    <span>
                        Unable to process the vendor request.
                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 HERO
            ================================================== -->

            <section class="vendors-hero">

                <div class="vendors-hero-content">

                    <div class="vendors-hero-label">

                        <i></i>

                        Vendor Management Center

                    </div>

                    <h2>
                        Grow your marketplace<br>
                        with trusted vendors.
                    </h2>

                    <p>
                        Review applications, monitor vendor activity,
                        manage approval status and keep your HochipoHub
                        marketplace running smoothly.
                    </p>

                </div>


                <div class="vendors-hero-number">

                    <strong>
                        <?= number_format($total_vendors) ?>
                    </strong>

                    <span>
                        Total Vendors
                    </span>

                </div>

            </section>


            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="vendors-stats">


                <!-- TOTAL -->

                <div class="vendor-stat-card">

                    <div class="vendor-stat-top">

                        <div class="vendor-stat-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                />

                                <path
                                    d="M22 21v-2a4 4 0 0 0-3-3.87"
                                />

                                <path
                                    d="M16 3.13a4 4 0 0 1 0 7.75"
                                />
                            </svg>

                        </div>

                    </div>

                    <strong>
                        <?= number_format($total_vendors) ?>
                    </strong>

                    <span>
                        Total Vendors
                    </span>

                </div>


                <!-- APPROVED -->

                <div class="vendor-stat-card">

                    <div class="vendor-stat-top">

                        <div class="vendor-stat-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"
                                />

                                <path d="M8 7h8"/>

                                <path d="m9 14 2 2 4-4"/>
                            </svg>

                        </div>

                    </div>

                    <strong>
                        <?= number_format($approved_vendors) ?>
                    </strong>

                    <span>
                        Approved Vendors
                    </span>

                </div>


                <!-- PENDING -->

                <div class="vendor-stat-card pending">

                    <div class="vendor-stat-top">

                        <div class="vendor-stat-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

                                <path d="M12 7v5l3 2"/>
                            </svg>

                        </div>

                    </div>

                    <strong>
                        <?= number_format($pending_vendors) ?>
                    </strong>

                    <span>
                        Pending Vendors
                    </span>

                </div>


                <!-- SUSPENDED -->

                <div class="vendor-stat-card suspended">

                    <div class="vendor-stat-top">

                        <div class="vendor-stat-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

                                <path d="M8 8l8 8"/>

                                <path d="M16 8l-8 8"/>
                            </svg>

                        </div>

                    </div>

                    <strong>
                        <?= number_format($suspended_vendors) ?>
                    </strong>

                    <span>
                        Suspended Vendors
                    </span>

                </div>


            </div>


            <!-- =================================================
                 PENDING APPLICATIONS
            ================================================== -->

            <section class="vendors-panel">


                <div class="vendors-panel-header">

                    <div class="vendors-panel-title">

                        <div class="vendors-panel-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                />

                                <path d="M19 8v6"/>

                                <path d="M22 11h-6"/>
                            </svg>

                        </div>


                        <div>

                            <h2>
                                Pending Vendor Applications
                            </h2>

                            <p>
                                Review applications before approving vendors.
                            </p>

                        </div>

                    </div>


                    <div class="vendor-count-badge">

                        <?= count($applications) ?>

                        Pending

                    </div>

                </div>


                <div class="vendors-table-wrapper">

                    <table class="vendors-table">

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
                                    class="vendor-empty"
                                >

                                    <div class="vendor-empty-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        >
                                            <path
                                                d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"
                                            />

                                            <path d="M8 12h8"/>

                                            <path d="M8 16h5"/>
                                        </svg>

                                    </div>

                                    <strong>
                                        No pending applications
                                    </strong>

                                    <span>
                                        New vendor applications will appear here.
                                    </span>

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($applications as $a): ?>

                                <?php

                                $applicantInitial =
                                    strtoupper(
                                        substr(
                                            trim($a['applicant_name'] ?? 'A'),
                                            0,
                                            1
                                        )
                                    );

                                ?>

                                <tr>


                                    <td>

                                        <span class="vendor-id">
                                            #<?= (int) $a['application_id'] ?>
                                        </span>

                                    </td>


                                    <td>

                                        <div class="vendor-person">

                                            <div class="vendor-avatar">
                                                <?= e($applicantInitial) ?>
                                            </div>

                                            <div class="vendor-person-info">

                                                <strong>
                                                    <?= e($a['applicant_name']) ?>
                                                </strong>

                                                <small>
                                                    <?= e($a['applicant_email']) ?>
                                                </small>

                                                <small>
                                                    <?= e($a['applicant_phone'] ?? '-') ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="vendor-business">

                                            <strong>
                                                <?= e($a['business_name']) ?>
                                            </strong>

                                            <small>
                                                Vendor Application
                                            </small>

                                        </div>

                                    </td>


                                    <td>

                                        <div
                                            style="
                                                max-width:230px;
                                                line-height:1.55;
                                                color:#64748b;
                                                font-size:11px;
                                                font-family:'Poppins',sans-serif;
                                            "
                                        >
                                            <?= nl2br(e($a['reason'] ?? '-')) ?>
                                        </div>

                                    </td>


                                    <td>

                                        <span class="vendor-date">

                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime($a['created_at'])
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <div class="application-actions">


                                            <form method="POST">

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
                                                    type="submit"
                                                    class="application-btn approve"
                                                    onclick="return confirm('Approve this vendor application?');"
                                                >

                                                    <svg
                                                        width="13"
                                                        height="13"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2.5"
                                                    >
                                                        <path d="M20 6 9 17l-5-5"/>
                                                    </svg>

                                                    Approve

                                                </button>

                                            </form>


                                            <form method="POST">

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
                                                    type="submit"
                                                    class="application-btn reject"
                                                    onclick="return confirm('Reject this vendor application?');"
                                                >

                                                    <svg
                                                        width="13"
                                                        height="13"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2.5"
                                                    >
                                                        <path d="M18 6 6 18"/>

                                                        <path d="m6 6 12 12"/>
                                                    </svg>

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


            <!-- =================================================
                 SEARCH / FILTER
            ================================================== -->

            <section class="vendors-panel">


                <div class="vendors-panel-header">

                    <div class="vendors-panel-title">

                        <div class="vendors-panel-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="7"
                                />

                                <path d="m20 20-4-4"/>
                            </svg>

                        </div>


                        <div>

                            <h2>
                                Find Vendors
                            </h2>

                            <p>
                                Search and filter your marketplace vendors.
                            </p>

                        </div>

                    </div>

                </div>


                <form
                    method="GET"
                    class="vendors-filter"
                >


                    <div class="vendors-search">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                            />

                            <path d="m20 20-4-4"/>
                        </svg>


                        <input
                            type="text"
                            name="search"
                            placeholder="Search vendor, owner or email..."
                            value="<?= e($search) ?>"
                        >

                    </div>


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
                        type="submit"
                        class="vendors-filter-btn"
                    >

                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                            />

                            <path d="m20 20-4-4"/>
                        </svg>

                        Search

                    </button>


                    <a
                        href="vendors.php"
                        class="vendors-reset"
                    >
                        Reset
                    </a>


                </form>

            </section>


            <!-- =================================================
                 VENDOR LIST
            ================================================== -->

            <section class="vendors-panel">


                <div class="vendors-panel-header">

                    <div class="vendors-panel-title">

                        <div class="vendors-panel-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M3 21h18"/>

                                <path d="M5 21V8l7-4 7 4v13"/>

                                <path d="M9 21v-5h6v5"/>

                                <path d="M8 10h1"/>

                                <path d="M15 10h1"/>

                                <path d="M8 13h1"/>

                                <path d="M15 13h1"/>
                            </svg>

                        </div>


                        <div>

                            <h2>
                                Vendor List
                            </h2>

                            <p>
                                <?= count($vendors) ?>
                                vendor(s) found in marketplace.
                            </p>

                        </div>

                    </div>


                    <div class="vendor-count-badge">

                        <?= number_format(count($vendors)) ?>

                        Vendors

                    </div>

                </div>


                <div class="vendors-table-wrapper">

                    <table class="vendors-table">

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
                                    class="vendor-empty"
                                >

                                    <div class="vendor-empty-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                        >

                                            <path
                                                d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"
                                            />

                                            <path d="M8 12h8"/>

                                            <path d="M8 16h5"/>

                                        </svg>

                                    </div>

                                    <strong>
                                        No vendors found
                                    </strong>

                                    <span>
                                        Try changing your search or status filter.
                                    </span>

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($vendors as $v): ?>

                                <?php

                                $ownerInitial =
                                    strtoupper(
                                        substr(
                                            trim($v['owner_name'] ?? 'V'),
                                            0,
                                            1
                                        )
                                    );

                                $statusClass =
                                    strtolower(
                                        $v['approval_status'] ?? 'pending'
                                    );

                                ?>

                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <span class="vendor-id">
                                            #<?= (int) $v['vendor_id'] ?>
                                        </span>

                                    </td>


                                    <!-- BUSINESS -->

                                    <td>

                                        <div class="vendor-business">

                                            <strong>
                                                <?= e($v['business_name']) ?>
                                            </strong>

                                            <small>
                                                <?= e(
                                                    $v['business_address'] ?? '-'
                                                ) ?>
                                            </small>

                                        </div>

                                    </td>


                                    <!-- OWNER -->

                                    <td>

                                        <div class="vendor-person">

                                            <div class="vendor-avatar">
                                                <?= e($ownerInitial) ?>
                                            </div>

                                            <div class="vendor-person-info">

                                                <strong>
                                                    <?= e($v['owner_name']) ?>
                                                </strong>

                                                <small>
                                                    <?= e($v['owner_email']) ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span class="vendor-category">

                                            <?= e(
                                                $v['category'] ?? 'General'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DELIVERY -->

                                    <td>

                                        <span class="vendor-delivery">

                                            <?= e(
                                                $v['delivery_method'] ?? '-'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <form
                                            method="POST"
                                            class="vendor-status-form"
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
                                                class="vendor-status-select <?= e($statusClass) ?>"
                                                onchange="this.form.submit()"
                                            >

                                                <?php foreach (
                                                    [
                                                        'Pending',
                                                        'Approved',
                                                        'Rejected',
                                                        'Suspended'
                                                    ] as $s
                                                ): ?>

                                                    <option
                                                        value="<?= e($s) ?>"
                                                        <?= $v['approval_status'] === $s
                                                            ? 'selected'
                                                            : '' ?>
                                                    >

                                                        <?= e($s) ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </form>

                                    </td>


                                    <!-- JOINED -->

                                    <td>

                                        <span class="vendor-date">

                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $v['created_at']
                                                    )
                                                )
                                            ) ?>

                                        </span>

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

document.addEventListener('DOMContentLoaded', function () {

    const toggle =
        document.getElementById('adminSidebarToggle');

    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', function () {

        const wrapper =
            document.querySelector('.admin-wrapper');

        if (!wrapper) {
            return;
        }

        wrapper.classList.toggle('sidebar-open');

        const isOpen =
            wrapper.classList.contains('sidebar-open');

        toggle.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

    });

});


/*
|--------------------------------------------------------------------------
| UPDATE STATUS SELECT COLOR
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    const selects =
        document.querySelectorAll(
            '.vendor-status-select'
        );

    selects.forEach(function (select) {

        function updateStatusColor() {

            select.classList.remove(
                'pending',
                'approved',
                'rejected',
                'suspended'
            );

            const value =
                select.value.toLowerCase();

            select.classList.add(value);
        }

        updateStatusColor();

        select.addEventListener(
            'change',
            updateStatusColor
        );

    });

});

</script>


</body>

</html>