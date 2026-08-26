<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - USER PROFILE
|--------------------------------------------------------------------------
| File: profile.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$db = getDB();

$user_id = (int) $_SESSION['user_id'];

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

$user = $stmt->fetch(PDO::FETCH_ASSOC);


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

    $csrf_token = $_POST['csrf_token'] ?? '';

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');


    if (!verifyCsrfToken($csrf_token)) {

        $error = 'Invalid security token. Please try again.';

    } elseif ($name === '') {

        $error = 'Name is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

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

        $emailExists = $stmt->fetch();


        if ($emailExists) {

            $error = 'This email is already being used.';

        } else {

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
                    $phone !== '' ? $phone : null,
                    $user_id
                ]);


                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;

                $success = 'Profile updated successfully.';


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

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

            } catch (Exception $e) {

                $error = 'Unable to update profile. Please try again.';
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

    $csrf_token = $_POST['csrf_token'] ?? '';

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    if (!verifyCsrfToken($csrf_token)) {

        $error = 'Invalid security token. Please try again.';

    } elseif (
        $current_password === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $error = 'Please fill in all password fields.';

    } elseif (
        !password_verify(
            $current_password,
            $user['password']
        )
    ) {

        $error = 'Current password is incorrect.';

    } elseif (strlen($new_password) < 8) {

        $error = 'New password must contain at least 8 characters.';

    } elseif ($new_password !== $confirm_password) {

        $error = 'New password and confirmation password do not match.';

    } else {

        try {

            $hashedPassword = password_hash(
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

            $user['password'] = $hashedPassword;

            $success = 'Password changed successfully.';

        } catch (Exception $e) {

            $error = 'Unable to change password. Please try again.';
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

    $csrf_token = $_POST['csrf_token'] ?? '';

    $mfa_enabled = isset($_POST['mfa_enabled'])
        ? 1
        : 0;


    if (!verifyCsrfToken($csrf_token)) {

        $error = 'Invalid security token.';

    } else {

        try {

            $stmt = $db->prepare("
                UPDATE users
                SET mfa_enabled = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $mfa_enabled,
                $user_id
            ]);

            $user['mfa_enabled'] = $mfa_enabled;

            $success = 'MFA settings updated successfully.';

        } catch (Exception $e) {

            $error = 'Unable to update MFA settings.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| PROFILE IMAGE
|--------------------------------------------------------------------------
*/

$profileImage =
    !empty($user['profile_image'])
        ? 'uploads/vendors/' . basename($user['profile_image'])
        : 'image/logo.jpg';


/*
|--------------------------------------------------------------------------
| DISPLAY DATA
|--------------------------------------------------------------------------
*/

$userInitial = strtoupper(
    mb_substr(
        trim($user['name']),
        0,
        1
    )
);

$role = ucfirst($user['role']);

$status = ucfirst($user['status']);

$statusClass =
    strtolower($user['status']) === 'active'
        ? 'active'
        : 'inactive';

$joinedDate = !empty($user['created_at'])
    ? date(
        'd M Y',
        strtotime($user['created_at'])
    )
    : '-';


$pageTitle = 'My Profile - ' . SITE_NAME;

$hideSiteMainWrapper = true;

$extraCSS = ['dashboard.css'];

require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/customer_sidebar.php';

?>


<style>

/* ================================================================
   HOCHIPOHUB PREMIUM PROFILE
================================================================ */

.profile-premium-page {

    min-height: 100vh;

    padding: 30px 34px 70px;

    background:
        radial-gradient(
            circle at 90% 4%,
            rgba(59, 130, 246, .10),
            transparent 25%
        ),
        radial-gradient(
            circle at 25% 75%,
            rgba(99, 102, 241, .06),
            transparent 30%
        ),
        #f5f8fc;

    color: #17233c;

    font-family:
        Inter,
        Arial,
        sans-serif;
}


.profile-premium-container {

    width: 100%;

    max-width: 1400px;

    margin: 0 auto;
}


/* ================================================================
   PAGE HEADING
================================================================ */

.profile-page-heading {

    margin-bottom: 23px;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;
}


.profile-heading-eyebrow {

    display: block;

    margin-bottom: 6px;

    color: #2563eb;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 1.6px;
}


.profile-page-heading h1 {

    margin: 0;

    color: #14213d;

    font-size: clamp(28px, 4vw, 37px);

    font-weight: 900;

    letter-spacing: -1.2px;
}


.profile-page-heading p {

    margin: 8px 0 0;

    color: #8390a4;

    font-size: 11px;

    line-height: 1.6;
}


.profile-back-btn {

    min-height: 43px;

    padding: 0 16px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    color: #52647c;

    background: rgba(255,255,255,.9);

    border: 1px solid #dfe7f0;

    border-radius: 12px;

    box-shadow:
        0 8px 20px
        rgba(40, 65, 120, .04);

    font-size: 9px;

    font-weight: 800;

    text-decoration: none;

    transition: .2s ease;
}


.profile-back-btn:hover {

    color: #2563eb;

    transform: translateY(-2px);

    box-shadow:
        0 10px 24px
        rgba(40, 65, 120, .08);
}


/* ================================================================
   HERO
================================================================ */

.profile-hero {

    position: relative;

    min-height: 235px;

    margin-bottom: 23px;

    padding: 35px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 30px;

    background:
        linear-gradient(
            115deg,
            #071f4c 0%,
            #0c3477 43%,
            #1764c5 72%,
            #318af0 100%
        );

    border-radius: 26px;

    box-shadow:
        0 20px 45px
        rgba(20, 70, 150, .16);
}


.profile-hero::before {

    content: "";

    position: absolute;

    width: 300px;

    height: 300px;

    top: -180px;

    right: -50px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}


.profile-hero::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    right: 220px;

    bottom: -125px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.05);
}


.profile-hero-left {

    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    gap: 22px;
}


/* ================================================================
   AVATAR
================================================================ */

.profile-avatar-wrap {

    position: relative;

    flex-shrink: 0;
}


.profile-avatar {

    width: 112px;

    height: 112px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #60a5fa,
            #6366f1
        );

    border:
        4px solid
        rgba(255,255,255,.85);

    border-radius: 27px;

    box-shadow:
        0 15px 35px
        rgba(0,0,0,.20);

    font-size: 38px;

    font-weight: 900;
}


.profile-avatar img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.profile-online {

    position: absolute;

    width: 21px;

    height: 21px;

    right: -4px;

    bottom: 5px;

    background: #22c55e;

    border: 4px solid #0d3d82;

    border-radius: 50%;
}


.profile-hero-info {

    color: #ffffff;
}


.profile-hero-label {

    display: block;

    margin-bottom: 7px;

    color: #9bc9ff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.4px;
}


.profile-hero-info h2 {

    margin: 0 0 7px;

    color: #ffffff;

    font-size: 27px;

    font-weight: 900;

    letter-spacing: -.7px;
}


.profile-hero-email {

    margin: 0 0 13px;

    color: rgba(255,255,255,.72);

    font-size: 10px;
}


.profile-badges {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;
}


.profile-badge {

    min-height: 27px;

    padding: 0 10px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.17);

    border-radius: 999px;

    font-size: 7px;

    font-weight: 800;

    backdrop-filter: blur(8px);
}


.profile-badge i {

    font-size: 7px;
}


.profile-hero-security {

    position: relative;

    z-index: 2;

    min-width: 215px;

    padding: 18px;

    background:
        rgba(255,255,255,.10);

    border:
        1px solid
        rgba(255,255,255,.15);

    border-radius: 17px;

    backdrop-filter: blur(10px);
}


.profile-security-top {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 11px;
}


.profile-security-icon {

    width: 37px;

    height: 37px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.14);

    border-radius: 10px;

    font-size: 13px;
}


.profile-security-top small {

    display: block;

    margin-bottom: 2px;

    color: #a9d2ff;

    font-size: 7px;

    font-weight: 800;
}


.profile-security-top strong {

    color: #ffffff;

    font-size: 10px;
}


.profile-security-progress {

    height: 5px;

    overflow: hidden;

    background:
        rgba(255,255,255,.14);

    border-radius: 999px;
}


.profile-security-progress span {

    display: block;

    width:
        <?= !empty($user['mfa_enabled'])
            ? '100%'
            : '70%' ?>;

    height: 100%;

    background: #ffffff;

    border-radius: inherit;
}


.profile-security-text {

    margin-top: 8px;

    color: rgba(255,255,255,.65);

    font-size: 7px;
}


/* ================================================================
   ALERT
================================================================ */

.profile-alert {

    margin-bottom: 20px;

    padding: 14px 17px;

    display: flex;

    align-items: center;

    gap: 9px;

    border-radius: 12px;

    font-size: 9px;

    font-weight: 600;
}


.profile-alert.error {

    color: #991b1b;

    background: #fef2f2;

    border: 1px solid #fecaca;
}


.profile-alert.success {

    color: #166534;

    background: #f0fdf4;

    border: 1px solid #bbf7d0;
}


/* ================================================================
   LAYOUT
================================================================ */

.profile-main-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        310px;

    gap: 22px;

    align-items: start;
}


.profile-content-column {

    display: flex;

    flex-direction: column;

    gap: 20px;
}


.profile-side-column {

    display: flex;

    flex-direction: column;

    gap: 17px;
}


/* ================================================================
   CARDS
================================================================ */

.profile-card {

    overflow: hidden;

    background:
        rgba(255,255,255,.96);

    border:
        1px solid #e5eaf2;

    border-radius: 20px;

    box-shadow:
        0 12px 32px
        rgba(40, 65, 120, .05);
}


.profile-card-header {

    min-height: 82px;

    padding: 19px 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    border-bottom:
        1px solid #edf1f6;
}


.profile-card-title {

    display: flex;

    align-items: center;

    gap: 12px;
}


.profile-card-icon {

    width: 43px;

    height: 43px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background:
        linear-gradient(
            135deg,
            #eff6ff,
            #e7f0ff
        );

    border-radius: 12px;

    font-size: 14px;
}


.profile-card-title h3 {

    margin: 0 0 4px;

    color: #17233c;

    font-size: 14px;

    font-weight: 900;
}


.profile-card-title p {

    margin: 0;

    color: #8b98aa;

    font-size: 8px;

    line-height: 1.5;
}


.profile-card-body {

    padding: 23px;
}


/* ================================================================
   FORM
================================================================ */

.profile-form-grid {

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 17px;
}


.profile-form-group {

    margin-bottom: 18px;
}


.profile-form-group.full {

    grid-column: 1 / -1;
}


.profile-form-group label {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-bottom: 7px;

    color: #34445d;

    font-size: 9px;

    font-weight: 800;
}


.profile-form-group label i {

    color: #2563eb;

    font-size: 9px;
}


.profile-form-group input {

    width: 100%;

    height: 45px;

    padding: 0 13px;

    outline: none;

    color: #263a55;

    background: #fbfdff;

    border: 1px solid #dce5ef;

    border-radius: 11px;

    font-family: inherit;

    font-size: 10px;

    transition: .18s ease;
}


.profile-form-group input:focus {

    background: #ffffff;

    border-color: #3b82f6;

    box-shadow:
        0 0 0 3px
        rgba(59,130,246,.08);
}


.profile-form-group small {

    display: block;

    margin-top: 6px;

    color: #9aa6b7;

    font-size: 7px;
}


/* ================================================================
   PASSWORD
================================================================ */

.profile-password-wrap {

    position: relative;
}


.profile-password-wrap input {

    padding-right: 44px;
}


.profile-password-toggle {

    position: absolute;

    width: 35px;

    height: 35px;

    top: 5px;

    right: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #8a99ad;

    background: transparent;

    border: 0;

    border-radius: 8px;

    cursor: pointer;
}


.profile-password-toggle:hover {

    color: #2563eb;

    background: #eff6ff;
}


/* ================================================================
   BUTTON
================================================================ */

.profile-form-actions {

    margin-top: 4px;

    padding-top: 18px;

    display: flex;

    justify-content: flex-end;

    border-top: 1px solid #edf1f6;
}


.profile-save-btn {

    min-height: 42px;

    padding: 0 17px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d67dc
        );

    border: 0;

    border-radius: 10px;

    box-shadow:
        0 9px 20px
        rgba(37,99,235,.20);

    font-family: inherit;

    font-size: 9px;

    font-weight: 800;

    cursor: pointer;

    transition: .18s ease;
}


.profile-save-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(37,99,235,.27);
}


/* ================================================================
   MFA
================================================================ */

.profile-mfa-box {

    padding: 18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    background:
        linear-gradient(
            135deg,
            #f8fbff,
            #eff6ff
        );

    border: 1px solid #dceaff;

    border-radius: 15px;
}


.profile-mfa-info {

    display: flex;

    align-items: center;

    gap: 13px;
}


.profile-mfa-icon {

    width: 47px;

    height: 47px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #ffffff;

    border:
        1px solid #dce8f7;

    border-radius: 13px;

    font-size: 16px;

    box-shadow:
        0 6px 15px
        rgba(40,65,120,.05);
}


.profile-mfa-info strong {

    display: block;

    margin-bottom: 4px;

    color: #243650;

    font-size: 10px;
}


.profile-mfa-info p {

    max-width: 420px;

    margin: 0;

    color: #8493a8;

    font-size: 8px;

    line-height: 1.6;
}


/* ================================================================
   SWITCH
================================================================ */

.profile-switch {

    position: relative;

    width: 47px;

    height: 25px;

    flex-shrink: 0;

    display: inline-block;
}


.profile-switch input {

    width: 0;

    height: 0;

    opacity: 0;
}


.profile-slider {

    position: absolute;

    inset: 0;

    background: #cbd5e1;

    border-radius: 999px;

    cursor: pointer;

    transition: .25s;
}


.profile-slider::before {

    content: "";

    position: absolute;

    width: 19px;

    height: 19px;

    left: 3px;

    top: 3px;

    background: #ffffff;

    border-radius: 50%;

    box-shadow:
        0 2px 6px
        rgba(0,0,0,.18);

    transition: .25s;
}


.profile-switch input:checked + .profile-slider {

    background: #2563eb;
}


.profile-switch input:checked + .profile-slider::before {

    transform: translateX(22px);
}


/* ================================================================
   SIDE PROFILE CARD
================================================================ */

.profile-summary-card {

    position: relative;

    overflow: hidden;

    padding: 23px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #0b2c67,
            #256dcc
        );

    border-radius: 19px;

    box-shadow:
        0 13px 30px
        rgba(20,70,145,.14);
}


.profile-summary-card::after {

    content: "";

    position: absolute;

    width: 125px;

    height: 125px;

    right: -50px;

    bottom: -55px;

    background:
        rgba(255,255,255,.07);

    border-radius: 50%;
}


.profile-summary-card > * {

    position: relative;

    z-index: 2;
}


.profile-summary-avatar {

    width: 55px;

    height: 55px;

    margin-bottom: 13px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.13);

    border:
        1px solid
        rgba(255,255,255,.20);

    border-radius: 15px;

    font-size: 20px;

    font-weight: 900;
}


.profile-summary-avatar img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.profile-summary-card small {

    display: block;

    margin-bottom: 5px;

    color: #a8d3ff;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .8px;
}


.profile-summary-card h3 {

    margin: 0 0 4px;

    color: #ffffff;

    font-size: 15px;

    font-weight: 900;
}


.profile-summary-card p {

    margin: 0;

    color: rgba(255,255,255,.68);

    font-size: 8px;
}


/* ================================================================
   ACCOUNT INFORMATION
================================================================ */

.profile-info-card {

    padding: 20px;

    background: #ffffff;

    border: 1px solid #e5eaf2;

    border-radius: 18px;

    box-shadow:
        0 9px 25px
        rgba(40,65,120,.045);
}


.profile-info-card-header {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 16px;
}


.profile-info-card-header i {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 10px;

    font-size: 12px;
}


.profile-info-card-header h4 {

    margin: 0;

    color: #17233c;

    font-size: 11px;

    font-weight: 900;
}


.profile-info-list {

    display: flex;

    flex-direction: column;

    gap: 0;
}


.profile-info-row {

    min-height: 48px;

    padding: 10px 0;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    border-bottom:
        1px solid #eef2f6;
}


.profile-info-row:last-child {

    border-bottom: 0;
}


.profile-info-row span {

    color: #8a98ab;

    font-size: 8px;
}


.profile-info-row strong {

    max-width: 150px;

    overflow: hidden;

    color: #34445d;

    font-size: 8px;

    font-weight: 800;

    text-align: right;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.profile-status-dot {

    display: inline-flex;

    align-items: center;

    gap: 5px;
}


.profile-status-dot::before {

    content: "";

    width: 6px;

    height: 6px;

    background: #22c55e;

    border-radius: 50%;
}


/* ================================================================
   SECURITY TIP
================================================================ */

.profile-tip-card {

    padding: 20px;

    background:
        linear-gradient(
            135deg,
            #fffbeb,
            #fffdf5
        );

    border: 1px solid #fde7a7;

    border-radius: 18px;
}


.profile-tip-icon {

    width: 40px;

    height: 40px;

    margin-bottom: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #b7791f;

    background: #fff7d6;

    border-radius: 11px;

    font-size: 13px;
}


.profile-tip-card h4 {

    margin: 0 0 6px;

    color: #5f481b;

    font-size: 11px;

    font-weight: 900;
}


.profile-tip-card p {

    margin: 0;

    color: #8b784d;

    font-size: 8px;

    line-height: 1.7;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1100px) {

    .profile-main-grid {

        grid-template-columns: 1fr;
    }


    .profile-side-column {

        display: grid;

        grid-template-columns:
            repeat(3, 1fr);
    }
}


@media (max-width: 800px) {

    .profile-premium-page {

        padding:
            24px
            20px
            55px;
    }


    .profile-hero {

        align-items: flex-start;

        flex-direction: column;
    }


    .profile-hero-security {

        width: 100%;
    }


    .profile-form-grid {

        grid-template-columns: 1fr;
    }


    .profile-form-group.full {

        grid-column: auto;
    }


    .profile-side-column {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 560px) {

    .profile-premium-page {

        padding:
            20px
            14px
            45px;
    }


    .profile-page-heading {

        align-items: flex-start;

        flex-direction: column;
    }


    .profile-back-btn {

        width: 100%;
    }


    .profile-hero {

        padding: 24px;

        border-radius: 20px;
    }


    .profile-hero-left {

        align-items: flex-start;

        flex-direction: column;
    }


    .profile-avatar {

        width: 85px;

        height: 85px;

        border-radius: 21px;
    }


    .profile-hero-info h2 {

        font-size: 22px;
    }


    .profile-card-body {

        padding: 18px;
    }


    .profile-mfa-box {

        align-items: flex-start;

        flex-direction: column;
    }


    .profile-form-actions {

        display: block;
    }


    .profile-save-btn {

        width: 100%;
    }
}

</style>


<!-- ===============================================================
     PROFILE PAGE
================================================================ -->

<main class="dashboard-page profile-premium-page">


    <div class="profile-premium-container">


        <!-- =======================================================
             PAGE HEADING
        ======================================================== -->

        <section class="profile-page-heading">


            <div>

                <span class="profile-heading-eyebrow">

                    MY ACCOUNT

                </span>


                <h1>

                    Profile Settings

                </h1>


                <p>

                    Manage your personal information,
                    password and account security.

                </p>

            </div>


            <a
                href="dashboard.php"
                class="profile-back-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Dashboard

            </a>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="profile-hero">


            <div class="profile-hero-left">


                <div class="profile-avatar-wrap">


                    <div class="profile-avatar">


                        <img
                            src="<?= e($profileImage) ?>"
                            alt="<?= e($user['name']) ?>"
                            onerror="
                                this.style.display='none';
                                this.parentElement.innerHTML='<?= e($userInitial) ?>';
                            "
                        >


                    </div>


                    <?php if (
                        strtolower($user['status']) === 'active'
                    ): ?>

                        <span class="profile-online"></span>

                    <?php endif; ?>


                </div>


                <div class="profile-hero-info">


                    <span class="profile-hero-label">

                        HOCHIPOHUB MEMBER

                    </span>


                    <h2>

                        <?= e($user['name']) ?>

                    </h2>


                    <p class="profile-hero-email">

                        <i class="fa-regular fa-envelope"></i>

                        <?= e($user['email']) ?>

                    </p>


                    <div class="profile-badges">


                        <span class="profile-badge">

                            <i class="fa-solid fa-user"></i>

                            <?= e($role) ?>

                        </span>


                        <span class="profile-badge">

                            <i class="fa-solid fa-circle-check"></i>

                            <?= e($status) ?>

                        </span>


                        <span class="profile-badge">

                            <i class="fa-regular fa-calendar"></i>

                            Since <?= e($joinedDate) ?>

                        </span>


                    </div>


                </div>


            </div>



            <!-- SECURITY STATUS -->

            <div class="profile-hero-security">


                <div class="profile-security-top">


                    <div class="profile-security-icon">

                        <i class="fa-solid fa-shield-halved"></i>

                    </div>


                    <div>

                        <small>
                            ACCOUNT SECURITY
                        </small>

                        <strong>

                            <?= !empty($user['mfa_enabled'])
                                ? 'Strong Protection'
                                : 'Standard Protection' ?>

                        </strong>

                    </div>


                </div>


                <div class="profile-security-progress">

                    <span></span>

                </div>


                <div class="profile-security-text">

                    <?= !empty($user['mfa_enabled'])
                        ? 'Multi-factor authentication is enabled.'
                        : 'Enable MFA for stronger account protection.' ?>

                </div>


            </div>


        </section>



        <!-- =======================================================
             ALERT
        ======================================================== -->

        <?php if ($error): ?>

            <div class="profile-alert error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="profile-alert success">

                <i class="fa-solid fa-circle-check"></i>

                <?= e($success) ?>

            </div>

        <?php endif; ?>



        <!-- =======================================================
             MAIN GRID
        ======================================================== -->

        <div class="profile-main-grid">


            <!-- ===================================================
                 LEFT
            ==================================================== -->

            <div class="profile-content-column">



                <!-- ===============================================
                     PERSONAL INFORMATION
                ================================================ -->

                <section class="profile-card">


                    <div class="profile-card-header">


                        <div class="profile-card-title">


                            <div class="profile-card-icon">

                                <i class="fa-regular fa-user"></i>

                            </div>


                            <div>

                                <h3>
                                    Personal Information
                                </h3>

                                <p>
                                    Update your basic account details.
                                </p>

                            </div>


                        </div>


                    </div>


                    <div class="profile-card-body">


                        <form method="POST">


                            <input
                                type="hidden"
                                name="action"
                                value="update_profile"
                            >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >


                            <div class="profile-form-grid">


                                <div class="profile-form-group">


                                    <label for="name">

                                        <i class="fa-regular fa-user"></i>

                                        Full Name

                                    </label>


                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        value="<?= e($user['name']) ?>"
                                        placeholder="Enter your full name"
                                        required
                                    >


                                </div>



                                <div class="profile-form-group">


                                    <label for="email">

                                        <i class="fa-regular fa-envelope"></i>

                                        Email Address

                                    </label>


                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?= e($user['email']) ?>"
                                        placeholder="example@email.com"
                                        required
                                    >


                                </div>



                                <div class="profile-form-group full">


                                    <label for="phone">

                                        <i class="fa-solid fa-phone"></i>

                                        Phone Number

                                    </label>


                                    <input
                                        type="text"
                                        id="phone"
                                        name="phone"
                                        value="<?= e(
                                            $user['phone'] ?? ''
                                        ) ?>"
                                        placeholder="Example: 0123456789"
                                    >


                                    <small>

                                        Used for account and order communication.

                                    </small>


                                </div>


                            </div>


                            <div class="profile-form-actions">


                                <button
                                    type="submit"
                                    class="profile-save-btn"
                                >

                                    <i class="fa-solid fa-floppy-disk"></i>

                                    Save Changes

                                </button>


                            </div>


                        </form>


                    </div>


                </section>



                <!-- ===============================================
                     PASSWORD
                ================================================ -->

                <section class="profile-card">


                    <div class="profile-card-header">


                        <div class="profile-card-title">


                            <div class="profile-card-icon">

                                <i class="fa-solid fa-lock"></i>

                            </div>


                            <div>

                                <h3>
                                    Change Password
                                </h3>

                                <p>
                                    Keep your HochipoHub account protected.
                                </p>

                            </div>


                        </div>


                    </div>


                    <div class="profile-card-body">


                        <form method="POST">


                            <input
                                type="hidden"
                                name="action"
                                value="change_password"
                            >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >


                            <div class="profile-form-grid">


                                <div class="profile-form-group full">


                                    <label for="current_password">

                                        <i class="fa-solid fa-key"></i>

                                        Current Password

                                    </label>


                                    <div class="profile-password-wrap">


                                        <input
                                            type="password"
                                            id="current_password"
                                            name="current_password"
                                            placeholder="Enter current password"
                                            required
                                        >


                                        <button
                                            type="button"
                                            class="profile-password-toggle"
                                            data-target="current_password"
                                        >

                                            <i class="fa-regular fa-eye"></i>

                                        </button>


                                    </div>


                                </div>



                                <div class="profile-form-group">


                                    <label for="new_password">

                                        <i class="fa-solid fa-lock"></i>

                                        New Password

                                    </label>


                                    <div class="profile-password-wrap">


                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            minlength="8"
                                            placeholder="Minimum 8 characters"
                                            required
                                        >


                                        <button
                                            type="button"
                                            class="profile-password-toggle"
                                            data-target="new_password"
                                        >

                                            <i class="fa-regular fa-eye"></i>

                                        </button>


                                    </div>


                                </div>



                                <div class="profile-form-group">


                                    <label for="confirm_password">

                                        <i class="fa-solid fa-check"></i>

                                        Confirm Password

                                    </label>


                                    <div class="profile-password-wrap">


                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            minlength="8"
                                            placeholder="Repeat new password"
                                            required
                                        >


                                        <button
                                            type="button"
                                            class="profile-password-toggle"
                                            data-target="confirm_password"
                                        >

                                            <i class="fa-regular fa-eye"></i>

                                        </button>


                                    </div>


                                </div>


                            </div>


                            <div class="profile-form-actions">


                                <button
                                    type="submit"
                                    class="profile-save-btn"
                                >

                                    <i class="fa-solid fa-shield-halved"></i>

                                    Update Password

                                </button>


                            </div>


                        </form>


                    </div>


                </section>



                <!-- ===============================================
                     MFA
                ================================================ -->

                <section class="profile-card">


                    <div class="profile-card-header">


                        <div class="profile-card-title">


                            <div class="profile-card-icon">

                                <i class="fa-solid fa-mobile-screen-button"></i>

                            </div>


                            <div>

                                <h3>
                                    Multi-Factor Authentication
                                </h3>

                                <p>
                                    Add an extra verification layer when signing in.
                                </p>

                            </div>


                        </div>


                    </div>


                    <div class="profile-card-body">


                        <form method="POST">


                            <input
                                type="hidden"
                                name="action"
                                value="update_mfa"
                            >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >


                            <div class="profile-mfa-box">


                                <div class="profile-mfa-info">


                                    <div class="profile-mfa-icon">

                                        <i class="fa-solid fa-shield-halved"></i>

                                    </div>


                                    <div>


                                        <strong>

                                            Two-Step Verification

                                        </strong>


                                        <p>

                                            Require an additional verification
                                            step whenever your HochipoHub
                                            account is accessed.

                                        </p>


                                    </div>


                                </div>


                                <label class="profile-switch">


                                    <input
                                        type="checkbox"
                                        name="mfa_enabled"
                                        value="1"
                                        <?= !empty($user['mfa_enabled'])
                                            ? 'checked'
                                            : '' ?>
                                    >


                                    <span class="profile-slider"></span>


                                </label>


                            </div>


                            <div class="profile-form-actions">


                                <button
                                    type="submit"
                                    class="profile-save-btn"
                                >

                                    <i class="fa-solid fa-floppy-disk"></i>

                                    Save Security Settings

                                </button>


                            </div>


                        </form>


                    </div>


                </section>


            </div>



            <!-- ===================================================
                 RIGHT
            ==================================================== -->

            <aside class="profile-side-column">



                <!-- ===============================================
                     PROFILE SUMMARY
                ================================================ -->

                <section class="profile-summary-card">


                    <div class="profile-summary-avatar">


                        <img
                            src="<?= e($profileImage) ?>"
                            alt="<?= e($user['name']) ?>"
                            onerror="
                                this.style.display='none';
                                this.parentElement.innerHTML='<?= e($userInitial) ?>';
                            "
                        >


                    </div>


                    <small>
                        ACCOUNT HOLDER
                    </small>


                    <h3>

                        <?= e($user['name']) ?>

                    </h3>


                    <p>

                        <?= e($user['email']) ?>

                    </p>


                </section>



                <!-- ===============================================
                     ACCOUNT INFORMATION
                ================================================ -->

                <section class="profile-info-card">


                    <div class="profile-info-card-header">


                        <i class="fa-regular fa-address-card"></i>


                        <h4>

                            Account Information

                        </h4>


                    </div>


                    <div class="profile-info-list">


                        <div class="profile-info-row">

                            <span>
                                User ID
                            </span>

                            <strong>
                                #<?= (int) $user['user_id'] ?>
                            </strong>

                        </div>


                        <div class="profile-info-row">

                            <span>
                                Role
                            </span>

                            <strong>
                                <?= e($role) ?>
                            </strong>

                        </div>


                        <div class="profile-info-row">

                            <span>
                                Status
                            </span>

                            <strong class="profile-status-dot">
                                <?= e($status) ?>
                            </strong>

                        </div>


                        <div class="profile-info-row">

                            <span>
                                MFA
                            </span>

                            <strong>

                                <?= !empty($user['mfa_enabled'])
                                    ? 'Enabled'
                                    : 'Disabled' ?>

                            </strong>

                        </div>


                        <div class="profile-info-row">

                            <span>
                                Joined
                            </span>

                            <strong>
                                <?= e($joinedDate) ?>
                            </strong>

                        </div>


                    </div>


                </section>



                <!-- ===============================================
                     SECURITY TIP
                ================================================ -->

                <section class="profile-tip-card">


                    <div class="profile-tip-icon">

                        <i class="fa-regular fa-lightbulb"></i>

                    </div>


                    <h4>

                        Security Tip

                    </h4>


                    <p>

                        Use a unique password for HochipoHub
                        and enable Multi-Factor Authentication
                        for stronger account protection.

                    </p>


                </section>


            </aside>


        </div>


    </div>


</main>



<script>

/*
|--------------------------------------------------------------------------
| PASSWORD VISIBILITY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const buttons =
            document.querySelectorAll(
                '.profile-password-toggle'
            );


        buttons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const targetId =
                            this.getAttribute(
                                'data-target'
                            );


                        const input =
                            document.getElementById(
                                targetId
                            );


                        if (!input) {
                            return;
                        }


                        const icon =
                            this.querySelector('i');


                        if (
                            input.type === 'password'
                        ) {

                            input.type = 'text';

                            icon.classList.remove(
                                'fa-eye'
                            );

                            icon.classList.add(
                                'fa-eye-slash'
                            );

                        } else {

                            input.type = 'password';

                            icon.classList.remove(
                                'fa-eye-slash'
                            );

                            icon.classList.add(
                                'fa-eye'
                            );

                        }

                    }
                );

            }
        );

    }
);

</script>


<?php

require_once __DIR__ . '/includes/footer.php';

?>