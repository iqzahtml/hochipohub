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
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: ../index.php");
    exit;
}


$admin_id =
    (int) $_SESSION['user_id'];

$success = '';
$error = '';


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

$admin =
    $stmt->fetch(PDO::FETCH_ASSOC);


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

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');


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
             * Duplicate email
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
                 * Duplicate phone
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


                    $_SESSION['name'] =
                        $name;

                    $_SESSION['email'] =
                        $email;


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
                    'Changed admin password',
                    'user',
                    $admin_id
                ]);


                $success =
                    "Password changed successfully.";
            }

        } catch (PDOException $e) {

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
                ? "MFA enabled."
                : "MFA disabled.";

    } catch (PDOException $e) {

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


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <div>

                <h1>
                    Settings
                </h1>

                <p>
                    Manage your administrator account and security.
                </p>

            </div>


            <div class="admin-user">

                <span>
                    <?= htmlspecialchars(
                        $admin['name']
                    ) ?>
                </span>

                <small>
                    Administrator
                </small>

            </div>

        </header>


        <!-- ALERTS -->

        <?php if ($success !== ''): ?>

            <div class="admin-alert success">

                <?= htmlspecialchars(
                    $success
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <div class="admin-alert error">

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>

        <?php endif; ?>


        <!-- PROFILE -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Administrator Profile
                    </h2>

                    <p>
                        Update your personal administrator account information.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="admin-form"
            >

                <input
                    type="hidden"
                    name="update_profile"
                    value="1"
                >


                <div class="form-group">

                    <label>
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars(
                            $admin['name']
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $admin['email']
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?= htmlspecialchars(
                            $admin['phone'] ?? ''
                        ) ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="admin-btn primary"
                >
                    Save Profile
                </button>

            </form>

        </section>


        <!-- PASSWORD -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Change Password
                    </h2>

                    <p>
                        Keep your administrator account protected with a strong password.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="admin-form"
            >

                <input
                    type="hidden"
                    name="change_password"
                    value="1"
                >


                <div class="form-group">

                    <label>
                        Current Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        New Password
                    </label>

                    <input
                        type="password"
                        name="new_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="admin-btn primary"
                >
                    Change Password
                </button>

            </form>

        </section>


        <!-- MFA -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Multi-Factor Authentication
                    </h2>

                    <p>
                        Manage additional security protection for your administrator account.
                    </p>

                </div>

            </div>


            <div class="settings-row">

                <div>

                    <strong>
                        MFA Status
                    </strong>

                    <p>

                        <?=
                            $admin['mfa_enabled']
                                ? 'MFA is currently enabled and protecting your account.'
                                : 'MFA is currently disabled for your account.'
                        ?>

                    </p>

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
                        value="<?= $admin['mfa_enabled'] ? 0 : 1 ?>"
                    >


                    <button
                        type="submit"
                        class="admin-btn <?= $admin['mfa_enabled'] ? 'danger' : 'primary' ?>"
                    >

                        <?= $admin['mfa_enabled']
                            ? 'Disable MFA'
                            : 'Enable MFA'
                        ?>

                    </button>

                </form>

            </div>

        </section>


        <!-- ACCOUNT INFORMATION -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Account Information
                    </h2>

                    <p>
                        Basic information about this administrator account.
                    </p>

                </div>

            </div>


            <div class="settings-info">

                <p>

                    <strong>
                        User ID
                    </strong>

                    #<?= (int) $admin['user_id'] ?>

                </p>


                <p>

                    <strong>
                        Role
                    </strong>

                    Administrator

                </p>


                <p>

                    <strong>
                        Account Created
                    </strong>

                    <?= date(
                        'd M Y, h:i A',
                        strtotime(
                            $admin['created_at']
                        )
                    ) ?>

                </p>

            </div>

        </section>


    </main>

</div>

</body>

</html>