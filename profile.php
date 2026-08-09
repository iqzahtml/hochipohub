<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$userStmt = $db->prepare("
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
        created_at,
        updated_at
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$userStmt->execute([
    ':user_id' => $userId
]);

$user = $userStmt->fetch();

if (!$user) {
    session_destroy();
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| GET VENDOR PROFILE
|--------------------------------------------------------------------------
*/

$vendor = null;

if ($user['role'] === 'vendor') {

    $vendorStmt = $db->prepare("
        SELECT
            vendor_id,
            user_id,
            business_name,
            business_logo,
            business_description,
            business_address,
            category,
            delivery_method,
            approval_status,
            created_at,
            updated_at
        FROM vendors
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $vendorStmt->execute([
        ':user_id' => $userId
    ]);

    $vendor = $vendorStmt->fetch();
}

/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['update_profile'])
) {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    if ($name === '') {

        $errorMessage =
            'Name cannot be empty.';

    } elseif (strlen($name) > 100) {

        $errorMessage =
            'Name must not exceed 100 characters.';

    } elseif (strlen($phone) > 20) {

        $errorMessage =
            'Phone number must not exceed 20 characters.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE PHONE
            |--------------------------------------------------------------------------
            */

            if ($phone !== '') {

                $phoneCheck = $db->prepare("
                    SELECT user_id
                    FROM users
                    WHERE phone = :phone
                    AND user_id != :user_id
                    LIMIT 1
                ");

                $phoneCheck->execute([
                    ':phone' => $phone,
                    ':user_id' => $userId
                ]);

                if ($phoneCheck->fetch()) {

                    throw new Exception(
                        'This phone number is already registered.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE USER
            |--------------------------------------------------------------------------
            */

            $updateStmt = $db->prepare("
                UPDATE users
                SET
                    name = :name,
                    phone = :phone
                WHERE user_id = :user_id
            ");

            $updateStmt->execute([
                ':name' => $name,
                ':phone' => $phone !== ''
                    ? $phone
                    : null,
                ':user_id' => $userId
            ]);

            /*
            |--------------------------------------------------------------------------
            | UPDATE SESSION NAME
            |--------------------------------------------------------------------------
            */

            $_SESSION['name'] = $name;

            $successMessage =
                'Profile updated successfully.';

            /*
            |--------------------------------------------------------------------------
            | REFRESH USER
            |--------------------------------------------------------------------------
            */

            $userStmt->execute([
                ':user_id' => $userId
            ]);

            $user = $userStmt->fetch();

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to update profile.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['change_password'])
) {

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    if (
        $currentPassword === ''
        ||
        $newPassword === ''
        ||
        $confirmPassword === ''
    ) {

        $errorMessage =
            'Please fill in all password fields.';

    } elseif (
        !password_verify(
            $currentPassword,
            $user['password']
        )
    ) {

        $errorMessage =
            'Current password is incorrect.';

    } elseif (strlen($newPassword) < 8) {

        $errorMessage =
            'New password must contain at least 8 characters.';

    } elseif ($newPassword !== $confirmPassword) {

        $errorMessage =
            'New password and confirmation do not match.';

    } else {

        try {

            $hashedPassword =
                password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

            $passwordStmt = $db->prepare("
                UPDATE users
                SET password = :password
                WHERE user_id = :user_id
            ");

            $passwordStmt->execute([
                ':password' => $hashedPassword,
                ':user_id' => $userId
            ]);

            $successMessage =
                'Password changed successfully.';

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to change password.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| PROFILE IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['upload_profile_image'])
) {

    if (
        !isset($_FILES['profile_image'])
        ||
        $_FILES['profile_image']['error']
            !== UPLOAD_ERR_OK
    ) {

        $errorMessage =
            'Please select a valid image.';

    } else {

        $file = $_FILES['profile_image'];

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        $fileType =
            mime_content_type(
                $file['tmp_name']
            );

        $maxSize =
            5 * 1024 * 1024;

        if (
            !in_array(
                $fileType,
                $allowedTypes,
                true
            )
        ) {

            $errorMessage =
                'Only JPG, PNG and WEBP images are allowed.';

        } elseif ($file['size'] > $maxSize) {

            $errorMessage =
                'Image size must not exceed 5MB.';

        } else {

            try {

                if (
                    !is_dir(
                        PRODUCT_UPLOAD_PATH
                    )
                ) {

                    mkdir(
                        PRODUCT_UPLOAD_PATH,
                        0755,
                        true
                    );
                }

                $extensionMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $extension =
                    $extensionMap[$fileType];

                $fileName =
                    'profile_'
                    . $userId
                    . '_'
                    . time()
                    . '.'
                    . $extension;

                /*
                |--------------------------------------------------------------------------
                | PROFILE IMAGE SAVED INSIDE uploads/vendors
                |--------------------------------------------------------------------------
                */

                $profileDirectory =
                    VENDOR_UPLOAD_PATH;

                if (
                    !is_dir(
                        $profileDirectory
                    )
                ) {

                    mkdir(
                        $profileDirectory,
                        0755,
                        true
                    );
                }

                $destination =
                    $profileDirectory
                    . $fileName;

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    $relativePath =
                        'uploads/vendors/'
                        . $fileName;

                    $imageStmt = $db->prepare("
                        UPDATE users
                        SET profile_image = :profile_image
                        WHERE user_id = :user_id
                    ");

                    $imageStmt->execute([
                        ':profile_image' =>
                            $relativePath,
                        ':user_id' =>
                            $userId
                    ]);

                    $successMessage =
                        'Profile image updated successfully.';

                    $userStmt->execute([
                        ':user_id' => $userId
                    ]);

                    $user = $userStmt->fetch();

                } else {

                    $errorMessage =
                        'Failed to upload image.';
                }

            } catch (Throwable $e) {

                $errorMessage =
                    APP_DEBUG
                        ? $e->getMessage()
                        : 'Unable to upload image.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| REFRESH VENDOR DATA
|--------------------------------------------------------------------------
*/

if ($user['role'] === 'vendor') {

    $vendorStmt->execute([
        ':user_id' => $userId
    ]);

    $vendor = $vendorStmt->fetch();
}

/*
|--------------------------------------------------------------------------
| PROFILE IMAGE URL
|--------------------------------------------------------------------------
*/

$profileImage =
    BASE_URL
    . 'image/vendors/default-vendor.jpg';

if (!empty($user['profile_image'])) {

    if (
        str_contains(
            $user['profile_image'],
            '/'
        )
        ||
        str_contains(
            $user['profile_image'],
            '\\'
        )
    ) {

        $profileImage =
            BASE_URL
            . ltrim(
                str_replace(
                    '\\',
                    '/',
                    $user['profile_image']
                ),
                '/'
            );

    } else {

        $profileImage =
            VENDOR_IMAGE_URL
            . rawurlencode(
                $user['profile_image']
            );
    }
}

/*
|--------------------------------------------------------------------------
| ROLE DISPLAY
|--------------------------------------------------------------------------
*/

$roleLabel =
    ucfirst(
        $user['role']
    );

$statusLabel =
    ucfirst(
        $user['status']
    );

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
        My Profile | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .profile-page {
            min-height: 100vh;
            padding: 45px 5%;
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(37, 99, 235, .13),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 20%,
                    rgba(14, 165, 233, .10),
                    transparent 30%
                ),
                #f8fbff;
        }

        .profile-container {
            max-width: 1180px;
            margin: auto;
        }

        .profile-heading {
            margin-bottom: 30px;
        }

        .profile-heading span {
            display: block;
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .profile-heading h1 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 950;
        }

        .profile-heading p {
            margin: 0;
            color: #64748b;
            line-height: 1.7;
        }

        .profile-grid {
            display: grid;
            grid-template-columns:
                340px
                minmax(0, 1fr);
            gap: 25px;
        }

        .profile-card {
            padding: 28px;
            border: 1px solid #dbeafe;
            border-radius: 25px;
            background: #ffffff;
            box-shadow:
                0 20px 50px rgba(15, 23, 42, .07);
        }

        .profile-sidebar {
            text-align: center;
        }

        .avatar-wrapper {
            position: relative;
            width: 145px;
            height: 145px;
            margin: 0 auto 20px;
        }

        .profile-avatar {
            width: 145px;
            height: 145px;
            object-fit: cover;
            border: 5px solid #dbeafe;
            border-radius: 50%;
            background: #eff6ff;
        }

        .avatar-upload {
            position: absolute;
            right: 0;
            bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 4px solid #ffffff;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
        }

        .avatar-upload input {
            display: none;
        }

        .profile-name {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 23px;
            font-weight: 950;
        }

        .profile-email {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 13px;
            word-break: break-word;
        }

        .profile-tags {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-bottom: 25px;
        }

        .profile-tag {
            padding: 7px 12px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 900;
        }

        .profile-tag.status {
            background: #dcfce7;
            color: #166534;
        }

        .profile-meta {
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: left;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
        }

        .meta-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
        }

        .meta-value {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            text-align: right;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            margin-bottom: 20px;
        }

        .section-title h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 20px;
            font-weight: 950;
        }

        .section-title p {
            margin: 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #334155;
            font-size: 12px;
            font-weight: 900;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 15px;
            border: 1px solid #dbeafe;
            border-radius: 13px;
            outline: none;
            background: #f8fbff;
            color: #0f172a;
            font-family: inherit;
            transition: .2s ease;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow:
                0 0 0 4px rgba(37, 99, 235, .08);
        }

        .form-group input:disabled {
            color: #64748b;
            cursor: not-allowed;
            background: #f1f5f9;
        }

        .form-help {
            color: #94a3b8;
            font-size: 10px;
        }

        .button-row {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .profile-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 13px;
            background: #2563eb;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            transition: .2s ease;
        }

        .profile-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .password-box {
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .alert {
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 13px;
            font-size: 12px;
            font-weight: 800;
        }

        .alert.success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .alert.error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .vendor-info-box {
            margin-top: 20px;
            padding: 18px;
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            background: #eff6ff;
            text-align: left;
        }

        .vendor-info-box h3 {
            margin: 0 0 12px;
            color: #1e3a8a;
            font-size: 13px;
            font-weight: 950;
        }

        .vendor-info-item {
            margin-bottom: 8px;
            color: #475569;
            font-size: 11px;
            line-height: 1.6;
        }

        .vendor-info-item:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 850px) {

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                text-align: center;
            }

            .profile-meta {
                max-width: 400px;
                margin: auto;
            }

        }

        @media (max-width: 600px) {

            .profile-page {
                padding: 30px 16px;
            }

            .profile-card {
                padding: 20px;
                border-radius: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .button-row {
                justify-content: stretch;
            }

            .profile-btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="profile-page">

    <div class="profile-container">

        <div class="profile-heading">

            <span>
                Account Center
            </span>

            <h1>
                My Profile
            </h1>

            <p>
                Manage your personal information,
                account security and profile settings.
            </p>

        </div>


        <?php if ($successMessage !== ''): ?>

            <div class="alert success">
                ✓ <?= e($successMessage) ?>
            </div>

        <?php endif; ?>


        <?php if ($errorMessage !== ''): ?>

            <div class="alert error">
                ⚠ <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>


        <div class="profile-grid">

            <!-- SIDEBAR -->

            <aside class="profile-card profile-sidebar">

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="avatar-wrapper">

                        <img
                            src="<?= e($profileImage) ?>"
                            alt="Profile"
                            class="profile-avatar"
                            onerror="this.src='<?= e(
                                BASE_URL .
                                'image/vendors/default-vendor.jpg'
                            ) ?>'"
                        >

                        <label
                            class="avatar-upload"
                            title="Change profile image"
                        >

                            📷

                            <input
                                type="file"
                                name="profile_image"
                                accept="image/jpeg,image/png,image/webp"
                                onchange="this.form.submit()"
                            >

                            <input
                                type="hidden"
                                name="upload_profile_image"
                                value="1"
                            >

                        </label>

                    </div>

                </form>


                <h2 class="profile-name">
                    <?= e($user['name']) ?>
                </h2>

                <p class="profile-email">
                    <?= e($user['email']) ?>
                </p>


                <div class="profile-tags">

                    <span class="profile-tag">
                        <?= e($roleLabel) ?>
                    </span>

                    <span class="profile-tag status">
                        <?= e($statusLabel) ?>
                    </span>

                </div>


                <div class="profile-meta">

                    <div class="meta-row">

                        <span class="meta-label">
                            Account ID
                        </span>

                        <span class="meta-value">
                            #<?= (int) $user['user_id'] ?>
                        </span>

                    </div>


                    <div class="meta-row">

                        <span class="meta-label">
                            Member Since
                        </span>

                        <span class="meta-value">
                            <?= date(
                                'd M Y',
                                strtotime(
                                    $user['created_at']
                                )
                            ) ?>
                        </span>

                    </div>


                    <div class="meta-row">

                        <span class="meta-label">
                            MFA
                        </span>

                        <span class="meta-value">
                            <?= $user['mfa_enabled']
                                ? 'Enabled'
                                : 'Disabled' ?>
                        </span>

                    </div>

                </div>


                <?php if ($user['role'] === 'vendor' && $vendor): ?>

                    <div class="vendor-info-box">

                        <h3>
                            Vendor Information
                        </h3>

                        <div class="vendor-info-item">
                            <strong>
                                Business:
                            </strong>
                            <?= e(
                                $vendor['business_name']
                            ) ?>
                        </div>

                        <div class="vendor-info-item">
                            <strong>
                                Category:
                            </strong>
                            <?= e(
                                $vendor['category']
                                ?: 'Not specified'
                            ) ?>
                        </div>

                        <div class="vendor-info-item">
                            <strong>
                                Approval:
                            </strong>
                            <?= e(
                                $vendor['approval_status']
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>

            </aside>


            <!-- MAIN -->

            <section class="profile-card">

                <!-- PERSONAL INFORMATION -->

                <div class="form-section">

                    <div class="section-title">

                        <h2>
                            Personal Information
                        </h2>

                        <p>
                            Update your basic account details.
                        </p>

                    </div>


                    <form method="POST">

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="name">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    maxlength="100"
                                    value="<?= e(
                                        $user['name']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="phone">
                                    Phone Number
                                </label>

                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    maxlength="20"
                                    value="<?= e(
                                        $user['phone']
                                        ?? ''
                                    ) ?>"
                                    placeholder="e.g. 0123456789"
                                >

                            </div>


                            <div class="form-group full">

                                <label for="email">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    value="<?= e(
                                        $user['email']
                                    ) ?>"
                                    disabled
                                >

                                <span class="form-help">
                                    Email address cannot be changed here.
                                </span>

                            </div>

                        </div>


                        <div class="button-row">

                            <button
                                type="submit"
                                name="update_profile"
                                value="1"
                                class="profile-btn"
                            >
                                Save Changes
                            </button>

                        </div>

                    </form>

                </div>


                <!-- PASSWORD -->

                <div class="form-section password-box">

                    <div class="section-title">

                        <h2>
                            Account Security
                        </h2>

                        <p>
                            Change your account password.
                        </p>

                    </div>


                    <form method="POST">

                        <div class="form-grid">

                            <div class="form-group full">

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

                                <span class="form-help">
                                    Minimum 8 characters.
                                </span>

                            </div>


                            <div class="form-group">

                                <label for="confirm_password">
                                    Confirm Password
                                </label>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    required
                                >

                            </div>

                        </div>


                        <div class="button-row">

                            <button
                                type="submit"
                                name="change_password"
                                value="1"
                                class="profile-btn"
                            >
                                Update Password
                            </button>

                        </div>

                    </form>

                </div>

            </section>

        </div>

    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


<script>

const newPassword =
    document.getElementById('new_password');

const confirmPassword =
    document.getElementById('confirm_password');

if (
    newPassword &&
    confirmPassword
) {

    confirmPassword.addEventListener(
        'input',
        function () {

            if (
                confirmPassword.value !==
                newPassword.value
            ) {

                confirmPassword.setCustomValidity(
                    'Passwords do not match.'
                );

            } else {

                confirmPassword.setCustomValidity(
                    ''
                );
            }
        }
    );

}

</script>

</body>

</html>