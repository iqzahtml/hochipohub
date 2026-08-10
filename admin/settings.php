<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| CURRENT SETTINGS
|--------------------------------------------------------------------------
*/

$settings = [
    'site_name' => APP_NAME,
    'commission_rate' => DEFAULT_COMMISSION_RATE,
    'currency' => CURRENCY_SYMBOL,
    'timezone' => 'Asia/Kuala_Lumpur'
];

/*
|--------------------------------------------------------------------------
| LOAD SETTINGS TABLE IF AVAILABLE
|--------------------------------------------------------------------------
*/

try {

    $tableCheck = $db->query("
        SHOW TABLES LIKE 'settings'
    ");

    if ($tableCheck->fetch()) {

        $stmt = $db->query("
            SELECT setting_key, setting_value
            FROM settings
        ");

        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {

            $key = $row['setting_key'];

            if (array_key_exists($key, $settings)) {

                $settings[$key] =
                    $row['setting_value'];
            }
        }
    }

} catch (PDOException $e) {

    if (APP_DEBUG) {
        $errorMessage = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE SETTINGS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_settings'])
) {

    $siteName =
        trim($_POST['site_name'] ?? '');

    $commissionRate =
        (float) (
            $_POST['commission_rate']
            ?? DEFAULT_COMMISSION_RATE
        );

    $currency =
        trim($_POST['currency'] ?? 'RM');

    $timezone =
        trim(
            $_POST['timezone']
            ?? 'Asia/Kuala_Lumpur'
        );

    if ($siteName === '') {

        $errorMessage =
            'Site name cannot be empty.';

    } elseif (
        $commissionRate < 0 ||
        $commissionRate > 100
    ) {

        $errorMessage =
            'Commission rate must be between 0% and 100%.';

    } elseif (
        !in_array(
            $timezone,
            timezone_identifiers_list(),
            true
        )
    ) {

        $errorMessage =
            'Invalid timezone selected.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK SETTINGS TABLE
            |--------------------------------------------------------------------------
            */

            $tableCheck = $db->query("
                SHOW TABLES LIKE 'settings'
            ");

            if (!$tableCheck->fetch()) {

                $errorMessage =
                    'Settings table does not exist in the database.';

            } else {

                $saveStmt = $db->prepare("
                    INSERT INTO settings
                        (
                            setting_key,
                            setting_value
                        )
                    VALUES
                        (
                            :setting_key,
                            :setting_value
                        )
                    ON DUPLICATE KEY UPDATE
                        setting_value =
                            VALUES(setting_value)
                ");

                $values = [
                    'site_name' =>
                        $siteName,

                    'commission_rate' =>
                        number_format(
                            $commissionRate,
                            2,
                            '.',
                            ''
                        ),

                    'currency' =>
                        $currency,

                    'timezone' =>
                        $timezone
                ];

                foreach (
                    $values
                    as $key => $value
                ) {

                    $saveStmt->execute([
                        ':setting_key' =>
                            $key,

                        ':setting_value' =>
                            $value
                    ]);
                }

                $settings =
                    array_merge(
                        $settings,
                        $values
                    );

                $successMessage =
                    'Settings updated successfully.';
            }

        } catch (PDOException $e) {

            $errorMessage = APP_DEBUG
                ? $e->getMessage()
                : 'Unable to save settings.';
        }
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
        Settings |
        <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/admin.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .settings-page {
            min-height: 100vh;

            padding:
                35px 4%
                60px;

            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(37,99,235,.14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 20%,
                    rgba(14,165,233,.12),
                    transparent 25%
                ),
                #f8fbff;
        }

        .settings-container {
            max-width: 1200px;
            margin: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .settings-hero {
            position: relative;

            overflow: hidden;

            margin-bottom: 24px;

            padding: 35px;

            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #020617,
                    #172554 35%,
                    #1d4ed8 68%,
                    #0284c7
                );

            color: white;

            box-shadow:
                0 25px 65px
                rgba(29,78,216,.22);
        }

        .settings-hero::before {
            content: "";

            position: absolute;

            width: 370px;
            height: 370px;

            top: -230px;
            right: -80px;

            border-radius: 50%;

            background:
                rgba(96,165,250,.14);
        }

        .settings-hero::after {
            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            right: 230px;
            bottom: -175px;

            border-radius: 50%;

            background:
                rgba(56,189,248,.09);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;

            color:
                rgba(255,255,255,.62);

            font-size: 9px;
            font-weight: 950;

            letter-spacing: 2px;

            text-transform: uppercase;
        }

        .settings-hero h1 {
            margin: 0 0 8px;

            font-size:
                clamp(
                    29px,
                    5vw,
                    45px
                );

            font-weight: 950;
        }

        .settings-hero p {
            max-width: 680px;

            margin: 0;

            color:
                rgba(255,255,255,.75);

            font-size: 11px;

            line-height: 1.7;
        }

        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        .message {
            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 12px;

            font-size: 9px;
            font-weight: 850;
        }

        .message.success {
            border:
                1px solid #bbf7d0;

            background: #f0fdf4;

            color: #166534;
        }

        .message.error {
            border:
                1px solid #fecaca;

            background: #fef2f2;

            color: #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | GRID
        |--------------------------------------------------------------------------
        */

        .settings-grid {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;
        }

        .settings-card {
            padding: 25px;

            border:
                1px solid #dbeafe;

            border-radius: 23px;

            background: white;

            box-shadow:
                0 12px 40px
                rgba(15,23,42,.055);
        }

        .settings-card.full {
            grid-column:
                1 / -1;
        }

        .card-heading {
            display: flex;

            align-items: center;

            gap: 11px;

            margin-bottom: 20px;

            padding-bottom: 15px;

            border-bottom:
                1px solid #eff6ff;
        }

        .card-icon {
            display: flex;

            align-items: center;
            justify-content: center;

            width: 37px;
            height: 37px;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #e0f2fe
                );

            color: #2563eb;

            font-size: 17px;
        }

        .card-heading h2 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;
            font-weight: 950;
        }

        .card-heading p {
            margin: 3px 0 0;

            color: #94a3b8;

            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-group {
            margin-bottom: 17px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 8px;
            font-weight: 950;

            text-transform: uppercase;

            letter-spacing: .5px;
        }

        .form-input,
        .form-select {
            width: 100%;

            box-sizing: border-box;

            padding: 12px 13px;

            border:
                1px solid #dbeafe;

            border-radius: 11px;

            outline: none;

            background: #fbfdff;

            color: #0f172a;

            font-family: inherit;

            font-size: 9px;

            transition:
                .2s ease;
        }

        .form-input:focus,
        .form-select:focus {
            border-color: #2563eb;

            background: white;

            box-shadow:
                0 0 0 4px
                rgba(37,99,235,.06);
        }

        .form-help {
            margin-top: 6px;

            color: #94a3b8;

            font-size: 7px;

            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | READONLY
        |--------------------------------------------------------------------------
        */

        .readonly-box {
            width: 100%;

            box-sizing: border-box;

            padding: 12px 13px;

            border:
                1px solid #e2e8f0;

            border-radius: 11px;

            background: #f8fafc;

            color: #64748b;

            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | INFO CARDS
        |--------------------------------------------------------------------------
        */

        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 10px;
        }

        .info-item {
            padding: 15px;

            border:
                1px solid #e0f2fe;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #f8fbff,
                    #eff6ff
                );
        }

        .info-item span {
            display: block;

            margin-bottom: 5px;

            color: #94a3b8;

            font-size: 7px;
            font-weight: 850;

            text-transform: uppercase;
        }

        .info-item strong {
            color: #1e40af;

            font-size: 11px;
            font-weight: 950;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        .save-area {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-top: 22px;

            padding-top: 20px;

            border-top:
                1px solid #eff6ff;
        }

        .save-note {
            color: #94a3b8;

            font-size: 8px;

            line-height: 1.5;
        }

        .save-button {
            padding: 12px 22px;

            border: 0;

            border-radius: 11px;

            cursor: pointer;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0284c7
                );

            color: white;

            font-size: 8px;
            font-weight: 950;

            box-shadow:
                0 8px 20px
                rgba(37,99,235,.18);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .save-button:hover {
            transform:
                translateY(-2px);

            box-shadow:
                0 12px 28px
                rgba(37,99,235,.25);
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 850px) {

            .settings-grid {
                grid-template-columns: 1fr;
            }

            .settings-card.full {
                grid-column: auto;
            }

            .info-grid {
                grid-template-columns:
                    1fr;
            }

        }

        @media (max-width: 600px) {

            .settings-page {
                padding:
                    25px 15px 50px;
            }

            .settings-hero {
                padding: 27px 21px;
            }

            .settings-card {
                padding: 19px;
            }

            .save-area {
                align-items: stretch;

                flex-direction: column;
            }

            .save-button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php
require_once __DIR__ . '/../includes/navbar.php';
?>


<main class="settings-page">

    <div class="settings-container">


        <!-- HERO -->

        <section class="settings-hero">

            <div class="hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    System Settings
                </h1>

                <p>
                    Manage your marketplace configuration,
                    commission rate, currency and regional
                    preferences.
                </p>

            </div>

        </section>


        <!-- MESSAGE -->

        <?php if (
            $successMessage !== ''
        ): ?>

            <div class="message success">

                ✓
                <?= e(
                    $successMessage
                ) ?>

            </div>

        <?php endif; ?>


        <?php if (
            $errorMessage !== ''
        ): ?>

            <div class="message error">

                ⚠
                <?= e(
                    $errorMessage
                ) ?>

            </div>

        <?php endif; ?>


        <!-- SETTINGS -->

        <form
            method="POST"
            action=""
        >

            <div class="settings-grid">


                <!-- GENERAL -->

                <section class="settings-card">

                    <div class="card-heading">

                        <div class="card-icon">
                            ⚙
                        </div>

                        <div>

                            <h2>
                                General Settings
                            </h2>

                            <p>
                                Basic marketplace information
                            </p>

                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="site_name"
                        >
                            Application Name
                        </label>

                        <input
                            type="text"
                            id="site_name"
                            name="site_name"
                            class="form-input"
                            value="<?= e(
                                $settings[
                                    'site_name'
                                ]
                            ) ?>"
                            maxlength="100"
                            required
                        >

                        <div class="form-help">
                            This name will be displayed
                            throughout the marketplace.
                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                        >
                            Base URL
                        </label>

                        <div class="readonly-box">
                            <?= e(
                                BASE_URL
                            ) ?>
                        </div>

                        <div class="form-help">
                            Configured in config.php.
                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                        >
                            Database
                        </label>

                        <div class="readonly-box">
                            <?= e(
                                DB_NAME
                            ) ?>
                        </div>

                    </div>

                </section>


                <!-- MARKETPLACE -->

                <section class="settings-card">

                    <div class="card-heading">

                        <div class="card-icon">
                            💰
                        </div>

                        <div>

                            <h2>
                                Marketplace
                            </h2>

                            <p>
                                Financial configuration
                            </p>

                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="commission_rate"
                        >
                            Commission Rate (%)
                        </label>

                        <input
                            type="number"
                            id="commission_rate"
                            name="commission_rate"
                            class="form-input"
                            value="<?= e(
                                $settings[
                                    'commission_rate'
                                ]
                            ) ?>"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        >

                        <div class="form-help">
                            Percentage deducted from vendor
                            transactions.
                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="currency"
                        >
                            Currency
                        </label>

                        <input
                            type="text"
                            id="currency"
                            name="currency"
                            class="form-input"
                            value="<?= e(
                                $settings[
                                    'currency'
                                ]
                            ) ?>"
                            maxlength="10"
                            required
                        >

                        <div class="form-help">
                            Example: RM, USD, SGD.
                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                        >
                            Default Commission
                        </label>

                        <div class="readonly-box">
                            <?= number_format(
                                DEFAULT_COMMISSION_RATE,
                                2
                            ) ?>%
                        </div>

                    </div>

                </section>


                <!-- REGIONAL -->

                <section class="settings-card">

                    <div class="card-heading">

                        <div class="card-icon">
                            🌐
                        </div>

                        <div>

                            <h2>
                                Regional Settings
                            </h2>

                            <p>
                                Timezone and regional preferences
                            </p>

                        </div>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="timezone"
                        >
                            Timezone
                        </label>

                        <select
                            id="timezone"
                            name="timezone"
                            class="form-select"
                        >

                            <?php

                            $timezones = [
                                'Asia/Kuala_Lumpur' =>
                                    'Malaysia — Kuala Lumpur',

                                'Asia/Singapore' =>
                                    'Singapore',

                                'Asia/Bangkok' =>
                                    'Thailand — Bangkok',

                                'Asia/Jakarta' =>
                                    'Indonesia — Jakarta',

                                'Asia/Manila' =>
                                    'Philippines — Manila',

                                'Asia/Tokyo' =>
                                    'Japan — Tokyo',

                                'Asia/Seoul' =>
                                    'South Korea — Seoul',

                                'Asia/Hong_Kong' =>
                                    'Hong Kong',

                                'UTC' =>
                                    'UTC'
                            ];

                            foreach (
                                $timezones
                                as $timezoneValue =>
                                $timezoneLabel
                            ):

                            ?>

                                <option
                                    value="<?= e(
                                        $timezoneValue
                                    ) ?>"
                                    <?= $settings[
                                        'timezone'
                                    ] ===
                                    $timezoneValue
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e(
                                        $timezoneLabel
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                        >
                            Current Server Time
                        </label>

                        <div class="readonly-box">

                            <?= e(
                                date(
                                    'd M Y, h:i A'
                                )
                            ) ?>

                        </div>

                    </div>

                </section>


                <!-- SYSTEM INFO -->

                <section class="settings-card">

                    <div class="card-heading">

                        <div class="card-icon">
                            🛡️
                        </div>

                        <div>

                            <h2>
                                System Information
                            </h2>

                            <p>
                                Current application configuration
                            </p>

                        </div>

                    </div>


                    <div class="info-grid">


                        <div class="info-item">

                            <span>
                                Debug Mode
                            </span>

                            <strong>
                                <?= APP_DEBUG
                                    ? 'ON'
                                    : 'OFF'
                                ?>
                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                PHP Version
                            </span>

                            <strong>
                                <?= e(
                                    PHP_VERSION
                                ) ?>
                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                Currency
                            </span>

                            <strong>
                                <?= e(
                                    $settings[
                                        'currency'
                                    ]
                                ) ?>
                            </strong>

                        </div>


                    </div>

                </section>


                <!-- SAVE -->

                <section class="settings-card full">

                    <div class="save-area">

                        <div class="save-note">

                            Changes will be applied to the
                            marketplace configuration after
                            saving.

                        </div>

                        <button
                            type="submit"
                            name="save_settings"
                            value="1"
                            class="save-button"
                        >
                            SAVE SETTINGS
                        </button>

                    </div>

                </section>

            </div>

        </form>

    </div>

</main>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>

</body>

</html>