<?php
/**
 * HOCHIPOHUB
 * Admin - Settings
 */

require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    strtolower(trim($_SESSION['role'] ?? '')) !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

$success = '';
$error = '';


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('settings_e')) {

    function settings_e($value)
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
| GET ADMIN DATA
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        profile_image,
        mfa_enabled,
        created_at
    FROM users
    WHERE user_id = ?
      AND role = 'admin'
    LIMIT 1
");

$stmt->execute([
    $admin_id
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$admin) {

    session_destroy();

    header("Location: ../index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );


    if (
        $name === '' ||
        $email === ''
    ) {

        $error =
            "Name and email are required.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE EMAIL
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                  AND user_id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $email,
                $admin_id
            ]);


            if ($stmt->fetch()) {

                $error =
                    "This email is already being used.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | CHECK DUPLICATE PHONE
                |--------------------------------------------------------------------------
                */

                if ($phone !== '') {

                    $stmt = $db->prepare("
                        SELECT user_id
                        FROM users
                        WHERE phone = ?
                          AND user_id != ?
                        LIMIT 1
                    ");

                    $stmt->execute([
                        $phone,
                        $admin_id
                    ]);


                    if ($stmt->fetch()) {

                        $error =
                            "This phone number is already being used.";
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE
                |--------------------------------------------------------------------------
                */

                if ($error === '') {

                    $stmt = $db->prepare("
                        UPDATE users
                        SET
                            name = ?,
                            email = ?,
                            phone = ?
                        WHERE user_id = ?
                          AND role = 'admin'
                    ");

                    $stmt->execute([
                        $name,
                        $email,
                        $phone !== ''
                            ? $phone
                            : null,
                        $admin_id
                    ]);


                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;


                    /*
                    |--------------------------------------------------------------------------
                    | ADMIN LOG
                    |--------------------------------------------------------------------------
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
                        'Updated admin profile',
                        'user',
                        $admin_id
                    ]);


                    $success =
                        "Profile updated successfully.";


                    $admin['name'] =
                        $name;

                    $admin['email'] =
                        $email;

                    $admin['phone'] =
                        $phone;
                }
            }

        } catch (PDOException $e) {

            error_log(
                'Admin profile update error: ' .
                $e->getMessage()
            );

            $error =
                "Unable to update profile.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['change_password'])
) {

    $current_password =
        $_POST['current_password'] ?? '';

    $new_password =
        $_POST['new_password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    if (
        $current_password === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $error =
            "All password fields are required.";

    } elseif (
        $new_password !== $confirm_password
    ) {

        $error =
            "New passwords do not match.";

    } elseif (
        strlen($new_password) < 8
    ) {

        $error =
            "Password must contain at least 8 characters.";

    } else {

        try {

            $stmt = $db->prepare("
                SELECT password
                FROM users
                WHERE user_id = ?
                  AND role = 'admin'
                LIMIT 1
            ");

            $stmt->execute([
                $admin_id
            ]);

            $row =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (
                !$row ||
                !password_verify(
                    $current_password,
                    $row['password']
                )
            ) {

                $error =
                    "Current password is incorrect.";

            } else {

                $hashed_password =
                    password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );


                $stmt = $db->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE user_id = ?
                      AND role = 'admin'
                ");

                $stmt->execute([
                    $hashed_password,
                    $admin_id
                ]);


                /*
                |--------------------------------------------------------------------------
                | ADMIN LOG
                |--------------------------------------------------------------------------
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
                    'Changed admin password',
                    'user',
                    $admin_id
                ]);


                $success =
                    "Password changed successfully.";
            }

        } catch (PDOException $e) {

            error_log(
                'Admin password update error: ' .
                $e->getMessage()
            );

            $error =
                "Unable to change password.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| MFA TOGGLE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle_mfa'])
) {

    $mfa_enabled =
        isset($_POST['mfa_enabled'])
            ? (int) $_POST['mfa_enabled']
            : 0;


    $mfa_enabled =
        $mfa_enabled === 1
            ? 1
            : 0;


    try {

        $stmt = $db->prepare("
            UPDATE users
            SET mfa_enabled = ?
            WHERE user_id = ?
              AND role = 'admin'
        ");

        $stmt->execute([
            $mfa_enabled,
            $admin_id
        ]);


        $admin['mfa_enabled'] =
            $mfa_enabled;


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
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

            $mfa_enabled
                ? 'Enabled MFA'
                : 'Disabled MFA',

            'user',

            $admin_id
        ]);


        $success =
            $mfa_enabled
                ? "MFA enabled successfully."
                : "MFA disabled successfully.";

    } catch (PDOException $e) {

        error_log(
            'Admin MFA update error: ' .
            $e->getMessage()
        );

        $error =
            "Unable to update MFA setting.";
    }
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
        Settings | HochipoHub Admin
    </title>


    <!--
    |--------------------------------------------------------------------------
    | SAME ADMIN FONT / DESIGN SYSTEM
    |--------------------------------------------------------------------------
    -->

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
        | SETTINGS PAGE
        |--------------------------------------------------------------------------
        */

        .settings-page {
            min-height: 100vh;
            padding: 32px;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(37, 99, 235, 0.10),
                    transparent 30%
                ),
                #f8fafc;
        }

        .settings-container {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGE HEADER
        |--------------------------------------------------------------------------
        */

        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .settings-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .settings-header-icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #ffffff;

            font-size: 25px;
            font-weight: 900;

            box-shadow:
                0 10px 25px
                rgba(37, 99, 235, 0.20);
        }

        .settings-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 32px;
            font-weight: 900;

            line-height: 1.1;
        }

        .settings-header p {
            margin: 7px 0 0;

            color: #64748b;

            font-size: 14px;
        }

        .settings-admin-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 10px 16px;

            border-radius: 999px;

            background: #eff6ff;
            border: 1px solid #bfdbfe;

            color: #2563eb;

            font-size: 12px;
            font-weight: 900;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .settings-alert {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 14px 17px;

            margin-bottom: 20px;

            border-radius: 12px;

            font-size: 13px;
            font-weight: 800;
        }

        .settings-alert.success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }

        .settings-alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }


        /*
        |--------------------------------------------------------------------------
        | SETTINGS GRID
        |--------------------------------------------------------------------------
        */

        .settings-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1.4fr)
                minmax(320px, 0.8fr);

            gap: 20px;

            align-items: start;
        }

        .settings-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | CARD
        |--------------------------------------------------------------------------
        */

        .settings-card {
            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, 0.06);
        }

        .settings-card-header {
            display: flex;
            align-items: flex-start;
            gap: 13px;

            padding: 22px 24px;

            border-bottom: 1px solid #e2e8f0;
        }

        .settings-card-icon {
            width: 40px;
            height: 40px;

            flex: 0 0 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: #eff6ff;
            color: #2563eb;

            font-size: 16px;
            font-weight: 900;
        }

        .settings-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 17px;
            font-weight: 900;
        }

        .settings-card-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 12px;

            line-height: 1.5;
        }

        .settings-card-body {
            padding: 24px;
        }


        /*
        |--------------------------------------------------------------------------
        | PROFILE SUMMARY
        |--------------------------------------------------------------------------
        */

        .profile-summary {
            display: flex;
            align-items: center;
            gap: 15px;

            margin-bottom: 24px;

            padding: 16px;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );

            border: 1px solid #dbeafe;
        }

        .profile-avatar {
            width: 52px;
            height: 52px;

            flex: 0 0 52px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #ffffff;

            font-size: 19px;
            font-weight: 900;
        }

        .profile-summary strong {
            display: block;

            color: #0f172a;

            font-size: 14px;
            font-weight: 900;
        }

        .profile-summary span {
            display: block;

            margin-top: 3px;

            color: #64748b;

            font-size: 12px;
        }


        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .settings-form {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }

        .settings-form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .settings-form-group.full {
            grid-column: 1 / -1;
        }

        .settings-form-group label {
            color: #334155;

            font-size: 12px;
            font-weight: 900;
        }

        .settings-form-group input {
            width: 100%;

            min-height: 44px;

            padding: 0 13px;

            border: 1px solid #cbd5e1;
            border-radius: 10px;

            background: #ffffff;

            color: #0f172a;

            font-family: inherit;
            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .settings-form-group input::placeholder {
            color: #94a3b8;
        }

        .settings-form-group input:hover {
            border-color: #94a3b8;
        }

        .settings-form-group input:focus {
            outline: none;

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, .10);
        }

        .settings-form-note {
            color: #94a3b8;

            font-size: 11px;

            line-height: 1.4;
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .settings-submit {
            grid-column: 1 / -1;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 44px;

            padding: 0 20px;

            border: 0;
            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-family: inherit;

            font-size: 12px;
            font-weight: 900;

            cursor: pointer;

            box-shadow:
                0 6px 16px
                rgba(37, 99, 235, .18);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .settings-submit:hover {
            transform: translateY(-1px);

            box-shadow:
                0 9px 22px
                rgba(37, 99, 235, .25);
        }


        /*
        |--------------------------------------------------------------------------
        | PASSWORD
        |--------------------------------------------------------------------------
        */

        .password-fields {
            display: flex;
            flex-direction: column;
            gap: 17px;
        }


        /*
        |--------------------------------------------------------------------------
        | MFA
        |--------------------------------------------------------------------------
        */

        .security-status {
            display: flex;
            align-items: center;
            gap: 13px;

            margin-bottom: 18px;

            padding: 15px;

            border-radius: 13px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;
        }

        .security-status-icon {
            width: 42px;
            height: 42px;

            flex: 0 0 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            font-size: 17px;
            font-weight: 900;
        }

        .security-status-icon.enabled {
            background: #dcfce7;
            color: #15803d;
        }

        .security-status-icon.disabled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .security-status strong {
            display: block;

            color: #0f172a;

            font-size: 13px;
            font-weight: 900;
        }

        .security-status p {
            margin: 4px 0 0;

            color: #64748b;

            font-size: 11px;

            line-height: 1.5;
        }

        .mfa-button {
            width: 100%;

            min-height: 43px;

            border: 0;
            border-radius: 10px;

            font-family: inherit;

            font-size: 12px;
            font-weight: 900;

            cursor: pointer;

            transition:
                background .2s ease,
                transform .2s ease;
        }

        .mfa-button.enable {
            background: #2563eb;
            color: #ffffff;
        }

        .mfa-button.enable:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .mfa-button.disable {
            background: #fee2e2;
            color: #b91c1c;
        }

        .mfa-button.disable:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }


        /*
        |--------------------------------------------------------------------------
        | ACCOUNT INFORMATION
        |--------------------------------------------------------------------------
        */

        .account-info {
            display: flex;
            flex-direction: column;
        }

        .account-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;

            padding: 14px 0;

            border-bottom: 1px solid #f1f5f9;
        }

        .account-info-row:first-child {
            padding-top: 0;
        }

        .account-info-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .account-info-label {
            color: #64748b;

            font-size: 12px;
            font-weight: 700;
        }

        .account-info-value {
            color: #0f172a;

            font-size: 12px;
            font-weight: 900;

            text-align: right;
        }

        .account-role {
            display: inline-flex;

            padding: 5px 9px;

            border-radius: 999px;

            background: #eff6ff;
            color: #2563eb;

            font-size: 10px;
            font-weight: 900;
        }


        /*
        |--------------------------------------------------------------------------
        | SECURITY TIP
        |--------------------------------------------------------------------------
        */

        .security-tip {
            padding: 15px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #f5f3ff,
                    #eff6ff
                );

            border: 1px solid #ddd6fe;
        }

        .security-tip strong {
            display: block;

            margin-bottom: 5px;

            color: #4338ca;

            font-size: 12px;
            font-weight: 900;
        }

        .security-tip p {
            margin: 0;

            color: #64748b;

            font-size: 11px;

            line-height: 1.6;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .settings-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .settings-page {
                padding: 20px;
            }

            .settings-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .settings-form {
                grid-template-columns: 1fr;
            }

            .settings-form-group.full {
                grid-column: auto;
            }

            .settings-submit {
                grid-column: auto;
            }

        }

        @media (max-width: 500px) {

            .settings-page {
                padding: 15px;
            }

            .settings-header h1 {
                font-size: 26px;
            }

            .settings-header-icon {
                width: 50px;
                height: 50px;
                border-radius: 15px;
            }

            .settings-card-header {
                padding: 18px;
            }

            .settings-card-body {
                padding: 18px;
            }

            .settings-header-left {
                align-items: flex-start;
            }

            .account-info-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }

            .account-info-value {
                text-align: left;
            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <?php

    $sidebar =
        dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    if (file_exists($sidebar)) {
        require_once $sidebar;
    }

    ?>


    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="admin-main">


        <div class="settings-page">

            <div class="settings-container">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <header class="settings-header">

                    <div class="settings-header-left">

                        <div class="settings-header-icon">
                            ⚙
                        </div>

                        <div>

                            <h1>
                                Settings
                            </h1>

                            <p>
                                Manage your administrator account and security.
                            </p>

                        </div>

                    </div>


                    <div class="settings-admin-badge">
                        ADMIN CONTROL
                    </div>

                </header>


                <!-- =================================================
                     ALERTS
                ================================================== -->

                <?php if ($success !== ''): ?>

                    <div class="settings-alert success">

                        ✓

                        <span>
                            <?= settings_e($success) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if ($error !== ''): ?>

                    <div class="settings-alert error">

                        !

                        <span>
                            <?= settings_e($error) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     CONTENT GRID
                ================================================== -->

                <div class="settings-grid">


                    <!-- =================================================
                         LEFT COLUMN
                    ================================================== -->

                    <div class="settings-column">


                        <!-- =============================================
                             PROFILE
                        ============================================== -->

                        <section class="settings-card">

                            <div class="settings-card-header">

                                <div class="settings-card-icon">
                                    👤
                                </div>

                                <div>

                                    <h2>
                                        Administrator Profile
                                    </h2>

                                    <p>
                                        Update your personal administrator account information.
                                    </p>

                                </div>

                            </div>


                            <div class="settings-card-body">


                                <div class="profile-summary">

                                    <div class="profile-avatar">

                                        <?= settings_e(
                                            strtoupper(
                                                substr(
                                                    $admin['name'] ?? 'A',
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>

                                    </div>

                                    <div>

                                        <strong>
                                            <?= settings_e(
                                                $admin['name']
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= settings_e(
                                                $admin['email']
                                            ) ?>
                                        </span>

                                    </div>

                                </div>


                                <form
                                    method="POST"
                                    class="settings-form"
                                >

                                    <input
                                        type="hidden"
                                        name="update_profile"
                                        value="1"
                                    >


                                    <div class="settings-form-group">

                                        <label>
                                            Full Name
                                        </label>

                                        <input
                                            type="text"
                                            name="name"
                                            value="<?= settings_e(
                                                $admin['name']
                                            ) ?>"
                                            placeholder="Enter your name"
                                            required
                                        >

                                    </div>


                                    <div class="settings-form-group">

                                        <label>
                                            Email Address
                                        </label>

                                        <input
                                            type="email"
                                            name="email"
                                            value="<?= settings_e(
                                                $admin['email']
                                            ) ?>"
                                            placeholder="Enter your email"
                                            required
                                        >

                                    </div>


                                    <div class="settings-form-group full">

                                        <label>
                                            Phone Number
                                        </label>

                                        <input
                                            type="text"
                                            name="phone"
                                            value="<?= settings_e(
                                                $admin['phone'] ?? ''
                                            ) ?>"
                                            placeholder="Enter phone number"
                                        >

                                        <span class="settings-form-note">
                                            Your phone number is used for administrator account information.
                                        </span>

                                    </div>


                                    <button
                                        type="submit"
                                        class="settings-submit"
                                    >
                                        Save Profile Changes
                                    </button>

                                </form>

                            </div>

                        </section>


                        <!-- =============================================
                             PASSWORD
                        ============================================== -->

                        <section class="settings-card">

                            <div class="settings-card-header">

                                <div class="settings-card-icon">
                                    🔒
                                </div>

                                <div>

                                    <h2>
                                        Change Password
                                    </h2>

                                    <p>
                                        Keep your administrator account protected with a strong password.
                                    </p>

                                </div>

                            </div>


                            <div class="settings-card-body">

                                <form
                                    method="POST"
                                    class="password-fields"
                                >

                                    <input
                                        type="hidden"
                                        name="change_password"
                                        value="1"
                                    >


                                    <div class="settings-form-group">

                                        <label>
                                            Current Password
                                        </label>

                                        <input
                                            type="password"
                                            name="current_password"
                                            autocomplete="current-password"
                                            placeholder="Enter current password"
                                            required
                                        >

                                    </div>


                                    <div class="settings-form-group">

                                        <label>
                                            New Password
                                        </label>

                                        <input
                                            type="password"
                                            name="new_password"
                                            minlength="8"
                                            autocomplete="new-password"
                                            placeholder="Minimum 8 characters"
                                            required
                                        >

                                    </div>


                                    <div class="settings-form-group">

                                        <label>
                                            Confirm New Password
                                        </label>

                                        <input
                                            type="password"
                                            name="confirm_password"
                                            minlength="8"
                                            autocomplete="new-password"
                                            placeholder="Repeat new password"
                                            required
                                        >

                                    </div>


                                    <button
                                        type="submit"
                                        class="settings-submit"
                                    >
                                        Change Password
                                    </button>

                                </form>

                            </div>

                        </section>


                    </div>


                    <!-- =================================================
                         RIGHT COLUMN
                    ================================================== -->

                    <div class="settings-column">


                        <!-- =============================================
                             MFA
                        ============================================== -->

                        <section class="settings-card">

                            <div class="settings-card-header">

                                <div class="settings-card-icon">
                                    🛡
                                </div>

                                <div>

                                    <h2>
                                        Account Security
                                    </h2>

                                    <p>
                                        Manage additional security protection.
                                    </p>

                                </div>

                            </div>


                            <div class="settings-card-body">


                                <div class="security-status">

                                    <div
                                        class="
                                            security-status-icon
                                            <?= $admin['mfa_enabled']
                                                ? 'enabled'
                                                : 'disabled'
                                            ?>
                                        "
                                    >

                                        <?= $admin['mfa_enabled']
                                            ? '✓'
                                            : '!'
                                        ?>

                                    </div>


                                    <div>

                                        <strong>

                                            <?= $admin['mfa_enabled']
                                                ? 'MFA Enabled'
                                                : 'MFA Disabled'
                                            ?>

                                        </strong>

                                        <p>

                                            <?= $admin['mfa_enabled']
                                                ? 'Your account has an additional security layer.'
                                                : 'Your account does not currently have MFA protection.'
                                            ?>

                                        </p>

                                    </div>

                                </div>


                                <form
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="toggle_mfa"
                                        value="1"
                                    >

                                    <input
                                        type="hidden"
                                        name="mfa_enabled"
                                        value="<?= $admin['mfa_enabled']
                                            ? 0
                                            : 1
                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="
                                            mfa-button
                                            <?= $admin['mfa_enabled']
                                                ? 'disable'
                                                : 'enable'
                                            ?>
                                        "
                                    >

                                        <?= $admin['mfa_enabled']
                                            ? 'Disable MFA'
                                            : 'Enable MFA'
                                        ?>

                                    </button>

                                </form>

                            </div>

                        </section>


                        <!-- =============================================
                             ACCOUNT INFORMATION
                        ============================================== -->

                        <section class="settings-card">

                            <div class="settings-card-header">

                                <div class="settings-card-icon">
                                    ℹ
                                </div>

                                <div>

                                    <h2>
                                        Account Information
                                    </h2>

                                    <p>
                                        Basic administrator account details.
                                    </p>

                                </div>

                            </div>


                            <div class="settings-card-body">

                                <div class="account-info">


                                    <div class="account-info-row">

                                        <span class="account-info-label">
                                            User ID
                                        </span>

                                        <span class="account-info-value">
                                            #<?= (int)
                                                $admin['user_id']
                                            ?>
                                        </span>

                                    </div>


                                    <div class="account-info-row">

                                        <span class="account-info-label">
                                            Role
                                        </span>

                                        <span class="account-info-value">

                                            <span class="account-role">
                                                Administrator
                                            </span>

                                        </span>

                                    </div>


                                    <div class="account-info-row">

                                        <span class="account-info-label">
                                            Email
                                        </span>

                                        <span class="account-info-value">
                                            <?= settings_e(
                                                $admin['email']
                                            ) ?>
                                        </span>

                                    </div>


                                    <div class="account-info-row">

                                        <span class="account-info-label">
                                            Account Created
                                        </span>

                                        <span class="account-info-value">

                                            <?= !empty(
                                                $admin['created_at']
                                            )
                                                ? date(
                                                    'd M Y',
                                                    strtotime(
                                                        $admin['created_at']
                                                    )
                                                )
                                                : '-'
                                            ?>

                                        </span>

                                    </div>


                                </div>

                            </div>

                        </section>


                        <!-- =============================================
                             SECURITY TIP
                        ============================================== -->

                        <section class="security-tip">

                            <strong>
                                🔐 Security Recommendation
                            </strong>

                            <p>
                                Use a unique password with at least 8 characters and keep MFA enabled whenever possible.
                            </p>

                        </section>


                    </div>


                </div>


            </div>

        </div>


    </main>

</div>


</body>

</html>