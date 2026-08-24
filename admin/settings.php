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

$admin_id = (int) $_SESSION['user_id'];

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

$admin = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$admin) {

    $_SESSION = [];

    if (
        ini_get('session.use_cookies')
    ) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }

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
        trim(
            $_POST['name'] ?? ''
        );

    $email =
        trim(
            $_POST['email'] ?? ''
        );

    $phone =
        trim(
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
             * Check duplicate email
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
                 * Check duplicate phone
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


                    /*
                     * Update session
                     */

                    $_SESSION['name'] =
                        $name;

                    $_SESSION['email'] =
                        $email;

                    $_SESSION['user_name'] =
                        $name;

                    $_SESSION['user_email'] =
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


                    /*
                     * Refresh admin data
                     */

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
                'HochipoHub Admin Profile Error: ' .
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
        $new_password !==
        $confirm_password
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

            /*
             * Get current password
             */

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
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


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

            error_log(
                'HochipoHub Admin Password Error: ' .
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

        error_log(
            'HochipoHub Admin MFA Error: ' .
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
    /*
     * CENTRAL ADMIN SIDEBAR
     *
     * Sidebar is maintained only in:
     *
     * includes/admin_sidebar.php
     */
    $admin_sidebar =
        dirname(__DIR__) .
        '/includes/admin_sidebar.php';

    if (file_exists($admin_sidebar)) {
        require_once $admin_sidebar;
    }
    ?>


    <main class="admin-main">


        <!-- TOP BAR -->

        <div class="admin-topbar">

            <div>

                <h1>
                    Settings
                </h1>

                <p>
                    Manage your administrator account.
                </p>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if ($success !== ''): ?>

            <div class="admin-alert success">

                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if ($error !== ''): ?>

            <div class="admin-alert error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
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
                        Update your personal account information.
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
                            $admin['name'],
                            ENT_QUOTES,
                            'UTF-8'
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
                            $admin['email'],
                            ENT_QUOTES,
                            'UTF-8'
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
                            $admin['phone'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
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


        <!-- CHANGE PASSWORD -->

        <section class="admin-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Change Password
                    </h2>

                    <p>
                        Use a strong password with at least 8 characters.
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
                        Manage MFA protection for your administrator account.
                    </p>

                </div>

            </div>


            <div class="settings-row">

                <div>

                    <strong>
                        MFA Status
                    </strong>

                    <p>

                        <?= $admin['mfa_enabled']
                            ? 'MFA is currently enabled.'
                            : 'MFA is currently disabled.'
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
                        value="<?= $admin['mfa_enabled']
                            ? 0
                            : 1 ?>"
                    >


                    <button
                        type="submit"
                        class="admin-btn <?= $admin['mfa_enabled']
                            ? 'danger'
                            : 'primary' ?>"
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

                </div>

            </div>


            <div class="settings-info">

                <p>

                    <strong>
                        User ID:
                    </strong>

                    #<?= (int)
                        $admin['user_id'] ?>

                </p>


                <p>

                    <strong>
                        Role:
                    </strong>

                    Administrator

                </p>


                <p>

                    <strong>
                        Account Created:
                    </strong>

                    <?= !empty(
                        $admin['created_at']
                    )
                        ? date(
                            'd M Y, h:i A',
                            strtotime(
                                $admin['created_at']
                            )
                        )
                        : '-'
                    ?>

                </p>

            </div>

        </section>


    </main>

</div>

</body>

</html>