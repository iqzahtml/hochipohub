<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - USER PROFILE
|--------------------------------------------------------------------------
| File:
| profile.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$db = getDB();

$user_id =
    (int) $_SESSION['user_id'];

$error = '';
$success = '';


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        password,
        profile_image,
        role,
        status,
        mfa_enabled,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch();


if (!$user) {

    session_destroy();

    header('Location: index.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_profile'
) {

    $csrf_token =
        $_POST['csrf_token'] ?? '';

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!verifyCsrfToken($csrf_token)) {

        $error =
            'Invalid security token. Please try again.';

    } elseif ($name === '') {

        $error =
            'Name is required.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | CHECK EMAIL
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
            $user_id
        ]);

        $emailExists =
            $stmt->fetch();


        if ($emailExists) {

            $error =
                'This email is already being used.';

        } else {


            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */

            try {

                $stmt = $db->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        email = ?,
                        phone = ?
                    WHERE user_id = ?
                ");

                $stmt->execute([
                    $name,
                    $email,
                    $phone !== ''
                        ? $phone
                        : null,
                    $user_id
                ]);


                /*
                |--------------------------------------------------------------------------
                | UPDATE SESSION
                |--------------------------------------------------------------------------
                */

                $_SESSION['user_name'] =
                    $name;

                $_SESSION['user_email'] =
                    $email;


                $success =
                    'Profile updated successfully.';


                /*
                |--------------------------------------------------------------------------
                | REFRESH USER
                |--------------------------------------------------------------------------
                */

                $stmt = $db->prepare("
                    SELECT
                        user_id,
                        name,
                        email,
                        phone,
                        password,
                        profile_image,
                        role,
                        status,
                        mfa_enabled,
                        created_at
                    FROM users
                    WHERE user_id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $user_id
                ]);

                $user =
                    $stmt->fetch();

            } catch (Exception $e) {

                $error =
                    'Unable to update profile. Please try again.';
            }
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
    ($_POST['action'] ?? '') === 'change_password'
) {

    $csrf_token =
        $_POST['csrf_token'] ?? '';

    $current_password =
        $_POST['current_password'] ?? '';

    $new_password =
        $_POST['new_password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    if (!verifyCsrfToken($csrf_token)) {

        $error =
            'Invalid security token. Please try again.';

    } elseif (
        $current_password === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $error =
            'Please fill in all password fields.';

    } elseif (
        !password_verify(
            $current_password,
            $user['password']
        )
    ) {

        $error =
            'Current password is incorrect.';

    } elseif (
        strlen($new_password) < 8
    ) {

        $error =
            'New password must contain at least 8 characters.';

    } elseif (
        $new_password !== $confirm_password
    ) {

        $error =
            'New password and confirmation password do not match.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | UPDATE PASSWORD
        |--------------------------------------------------------------------------
        */

        try {

            $hashedPassword =
                password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

            $stmt = $db->prepare("
                UPDATE users
                SET password = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $hashedPassword,
                $user_id
            ]);


            $success =
                'Password changed successfully.';


        } catch (Exception $e) {

            $error =
                'Unable to change password. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE MFA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_mfa'
) {

    $csrf_token =
        $_POST['csrf_token'] ?? '';

    $mfa_enabled =
        isset($_POST['mfa_enabled'])
            ? 1
            : 0;


    if (!verifyCsrfToken($csrf_token)) {

        $error =
            'Invalid security token.';

    } else {

        $stmt = $db->prepare("
            UPDATE users
            SET mfa_enabled = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $mfa_enabled,
            $user_id
        ]);


        $user['mfa_enabled'] =
            $mfa_enabled;

        $success =
            'MFA settings updated successfully.';
    }
}


/*
|--------------------------------------------------------------------------
| PROFILE IMAGE
|--------------------------------------------------------------------------
*/

$profileImage =
    !empty($user['profile_image'])
        ? 'uploads/vendors/' .
          basename(
              $user['profile_image']
          )
        : 'image/logo.jpg';


$pageTitle =
    'My Profile - ' . SITE_NAME;

$hideSiteMainWrapper = true;
$extraCSS = ['dashboard.css'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/customer_sidebar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <section class="dashboard-header">

            <div>

                <span class="small-label">
                    ACCOUNT
                </span>

                <h1>
                    My Profile
                </h1>

                <p>
                    Manage your personal account information.
                </p>

            </div>

        </section>


        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <!-- =====================================================
             PROFILE INFORMATION
        ====================================================== -->

        <section class="dashboard-section">


            <div class="profile-header">


                <div class="profile-image-wrapper">

                    <img
                        src="<?= e($profileImage) ?>"
                        alt="Profile"
                        class="profile-image"
                    >

                </div>


                <div>

                    <h2>
                        <?= e($user['name']) ?>
                    </h2>

                    <p>
                        <?= e($user['email']) ?>
                    </p>

                    <span
                        class="status-badge status-success"
                    >
                        <?= e(
                            ucfirst(
                                $user['role']
                            )
                        ) ?>
                    </span>

                </div>


            </div>


            <hr>


            <form
                method="POST"
                class="dashboard-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update_profile"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(
                        csrfToken()
                    ) ?>"
                >


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e(
                            $user['name']
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e(
                            $user['email']
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="phone">
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= e(
                            $user['phone'] ?? ''
                        ) ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Profile
                </button>

            </form>


        </section>


        <!-- =====================================================
             CHANGE PASSWORD
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        SECURITY
                    </span>

                    <h2>
                        Change Password
                    </h2>

                </div>

            </div>


            <form
                method="POST"
                class="dashboard-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="change_password"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(
                        csrfToken()
                    ) ?>"
                >


                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Change Password
                </button>

            </form>


        </section>


        <!-- =====================================================
             MFA
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        SECURITY
                    </span>

                    <h2>
                        Multi-Factor Authentication
                    </h2>

                </div>

            </div>


            <form
                method="POST"
                class="dashboard-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update_mfa"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(
                        csrfToken()
                    ) ?>"
                >


                <label class="checkbox-label">

                    <input
                        type="checkbox"
                        name="mfa_enabled"
                        value="1"
                        <?= !empty(
                            $user['mfa_enabled']
                        )
                            ? 'checked'
                            : '' ?>
                    >

                    Enable Multi-Factor Authentication

                </label>


                <p class="form-help">

                    MFA adds an additional verification step
                    when logging into your account.

                </p>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save MFA Settings
                </button>

            </form>


        </section>


        <!-- =====================================================
             ACCOUNT INFORMATION
        ====================================================== -->

        <section class="dashboard-section">


            <div class="section-heading">

                <div>

                    <span class="small-label">
                        ACCOUNT
                    </span>

                    <h2>
                        Account Information
                    </h2>

                </div>

            </div>


            <div class="account-info-grid">


                <div class="info-card">

                    <span>
                        User ID
                    </span>

                    <strong>
                        #<?= (int)
                            $user['user_id'] ?>
                    </strong>

                </div>


                <div class="info-card">

                    <span>
                        Role
                    </span>

                    <strong>
                        <?= e(
                            ucfirst(
                                $user['role']
                            )
                        ) ?>
                    </strong>

                </div>


                <div class="info-card">

                    <span>
                        Account Status
                    </span>

                    <strong>
                        <?= e(
                            ucfirst(
                                $user['status']
                            )
                        ) ?>
                    </strong>

                </div>


                <div class="info-card">

                    <span>
                        Joined
                    </span>

                    <strong>

                        <?= e(
                            date(
                                'd M Y',
                                strtotime(
                                    $user['created_at']
                                )
                            )
                        ) ?>

                    </strong>

                </div>


            </div>


        </section>


    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
