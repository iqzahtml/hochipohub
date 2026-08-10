<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = getDB();

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    redirect(BASE_URL . 'index.php');
}

$adminId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE COMMISSION RATE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_rate'])
) {

    $rate = isset($_POST['commission_rate'])
        ? (float) $_POST['commission_rate']
        : DEFAULT_COMMISSION_RATE;

    if ($rate < 0 || $rate > 100) {

        $errorMessage =
            'Commission rate must be between 0% and 100%.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Update all active/current commission settings
            |--------------------------------------------------------------------------
            */

            $checkTable = $db->query("
                SHOW TABLES LIKE 'commission'
            ");

            if (!$checkTable->fetchColumn()) {

                $errorMessage =
                    'Commission table was not found in the database.';

            } else {

                $columns = $db->query("
                    SHOW COLUMNS FROM commission
                ")->fetchAll(PDO::FETCH_COLUMN);

                /*
                |--------------------------------------------------------------------------
                | Detect common commission rate column
                |--------------------------------------------------------------------------
                */

                $rateColumn = null;

                foreach (
                    [
                        'commission_rate',
                        'rate',
                        'percentage',
                        'commission_percentage'
                    ] as $candidate
                ) {

                    if (
                        in_array(
                            $candidate,
                            $columns,
                            true
                        )
                    ) {

                        $rateColumn = $candidate;
                        break;
                    }
                }

                if (!$rateColumn) {

                    $errorMessage =
                        'Commission rate column was not found.';

                } else {

                    $idColumn = null;

                    foreach (
                        [
                            'commission_id',
                            'id'
                        ] as $candidate
                    ) {

                        if (
                            in_array(
                                $candidate,
                                $columns,
                                true
                            )
                        ) {

                            $idColumn = $candidate;
                            break;
                        }
                    }

                    $updatedAtColumn = null;

                    foreach (
                        [
                            'updated_at',
                            'modified_at'
                        ] as $candidate
                    ) {

                        if (
                            in_array(
                                $candidate,
                                $columns,
                                true
                            )
                        ) {

                            $updatedAtColumn =
                                $candidate;

                            break;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Find latest commission record
                    |--------------------------------------------------------------------------
                    */

                    if ($idColumn) {

                        $latestStmt =
                            $db->query("
                                SELECT *
                                FROM commission
                                ORDER BY `$idColumn` DESC
                                LIMIT 1
                            ");

                    } else {

                        $latestStmt =
                            $db->query("
                                SELECT *
                                FROM commission
                                LIMIT 1
                            ");
                    }

                    $latest =
                        $latestStmt->fetch();

                    if ($latest) {

                        $sql = "
                            UPDATE commission
                            SET `$rateColumn` = :rate
                        ";

                        if ($updatedAtColumn) {

                            $sql .= ",
                                `$updatedAtColumn`
                                = NOW()
                            ";
                        }

                        if ($idColumn) {

                            $sql .= "
                                WHERE `$idColumn`
                                = :record_id
                                LIMIT 1
                            ";
                        }

                        $stmt =
                            $db->prepare($sql);

                        $params = [
                            ':rate' => $rate
                        ];

                        if ($idColumn) {

                            $params[':record_id'] =
                                $latest[$idColumn];
                        }

                        $stmt->execute($params);

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Insert first commission setting
                        |--------------------------------------------------------------------------
                        */

                        $insertColumns = [
                            "`$rateColumn`"
                        ];

                        $insertValues = [
                            ':rate'
                        ];

                        $params = [
                            ':rate' => $rate
                        ];

                        if (
                            in_array(
                                'created_at',
                                $columns,
                                true
                            )
                        ) {

                            $insertColumns[] =
                                '`created_at`';

                            $insertValues[] =
                                'NOW()';
                        }

                        $insertSql = "
                            INSERT INTO commission (
                                "
                                . implode(
                                    ', ',
                                    $insertColumns
                                )
                                . "
                            )
                            VALUES (
                                "
                                . implode(
                                    ', ',
                                    $insertValues
                                )
                                . "
                            )
                        ";

                        $stmt =
                            $db->prepare(
                                $insertSql
                            );

                        $stmt->execute(
                            $params
                        );
                    }

                    $successMessage =
                        'Commission rate updated successfully.';
                }
            }

        } catch (Throwable $e) {

            $errorMessage =
                APP_DEBUG
                    ? $e->getMessage()
                    : 'Unable to update commission rate.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET CURRENT COMMISSION RATE
|--------------------------------------------------------------------------
*/

$currentRate =
    DEFAULT_COMMISSION_RATE;

$totalCommission =
    0;

$totalSales =
    0;

$commissionRecords = [];

try {

    $tableCheck = $db->query("
        SHOW TABLES LIKE 'commission'
    ");

    if ($tableCheck->fetchColumn()) {

        $columns = $db->query("
            SHOW COLUMNS FROM commission
        ")->fetchAll(PDO::FETCH_COLUMN);

        $rateColumn = null;

        foreach (
            [
                'commission_rate',
                'rate',
                'percentage',
                'commission_percentage'
            ] as $candidate
        ) {

            if (
                in_array(
                    $candidate,
                    $columns,
                    true
                )
            ) {

                $rateColumn =
                    $candidate;

                break;
            }
        }

        $idColumn = null;

        foreach (
            [
                'commission_id',
                'id'
            ] as $candidate
        ) {

            if (
                in_array(
                    $candidate,
                    $columns,
                    true
                )
            ) {

                $idColumn =
                    $candidate;

                break;
            }
        }

        if ($rateColumn) {

            if ($idColumn) {

                $rateStmt =
                    $db->query("
                        SELECT `$rateColumn`
                        FROM commission
                        ORDER BY `$idColumn` DESC
                        LIMIT 1
                    ");

            } else {

                $rateStmt =
                    $db->query("
                        SELECT `$rateColumn`
                        FROM commission
                        LIMIT 1
                    ");
            }

            $databaseRate =
                $rateStmt->fetchColumn();

            if (
                $databaseRate !== false &&
                $databaseRate !== null
            ) {

                $currentRate =
                    (float) $databaseRate;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Commission Records
        |--------------------------------------------------------------------------
        */

        $selectColumns = [];

        foreach (
            [
                'commission_id',
                'id',
                'order_id',
                'vendor_id',
                'commission_rate',
                'rate',
                'commission_amount',
                'amount',
                'created_at'
            ] as $column
        ) {

            if (
                in_array(
                    $column,
                    $columns,
                    true
                )
            ) {

                $selectColumns[] =
                    "`$column`";
            }
        }

        if (!empty($selectColumns)) {

            $commissionQuery = "
                SELECT "
                . implode(
                    ', ',
                    $selectColumns
                )
                . "
                FROM commission
            ";

            if ($idColumn) {

                $commissionQuery .= "
                    ORDER BY `$idColumn` DESC
                ";
            }

            $commissionQuery .= "
                LIMIT 100
            ";

            $commissionStmt =
                $db->query(
                    $commissionQuery
                );

            $commissionRecords =
                $commissionStmt->fetchAll();
        }
    }

} catch (Throwable $e) {

    if (APP_DEBUG) {

        $errorMessage =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| TOTAL COMMISSION / SALES
|--------------------------------------------------------------------------
*/

try {

    $ordersTable =
        $db->query("
            SHOW TABLES LIKE 'orders'
        ");

    if ($ordersTable->fetchColumn()) {

        $salesStmt = $db->query("
            SELECT
                COALESCE(
                    SUM(total_amount),
                    0
                )
            FROM orders
            WHERE status NOT IN (
                'Cancelled',
                'Rejected'
            )
        ");

        $totalSales =
            (float) $salesStmt->fetchColumn();

        $totalCommission =
            $totalSales *
            ($currentRate / 100);
    }

} catch (Throwable $e) {

    $totalSales =
        0;

    $totalCommission =
        0;
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
        Commission Management |
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

        .commission-page {
            min-height: 100vh;
            padding: 35px 4%;
            background:
                radial-gradient(
                    circle at 10% 5%,
                    rgba(37,99,235,.12),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 15%,
                    rgba(14,165,233,.10),
                    transparent 25%
                ),
                #f8fbff;
        }

        .commission-container {
            max-width: 1400px;
            margin: auto;
        }

        .commission-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            margin-bottom: 25px;
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    #0b2a66,
                    #1d4ed8 55%,
                    #0284c7
                );
            color: white;
            box-shadow:
                0 25px 60px
                rgba(29,78,216,.22);
        }

        .commission-hero::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            top: -150px;
            right: -70px;
            border-radius: 50%;
            background:
                rgba(255,255,255,.08);
        }

        .commission-hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-kicker {
            margin-bottom: 8px;
            color: rgba(
                255,
                255,
                255,
                .65
            );
            font-size: 10px;
            font-weight: 950;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .commission-hero h1 {
            margin: 0 0 8px;
            font-size: clamp(
                28px,
                5vw,
                44px
            );
            font-weight: 950;
        }

        .commission-hero p {
            max-width: 700px;
            margin: 0;
            color: rgba(
                255,
                255,
                255,
                .75
            );
            font-size: 12px;
            line-height: 1.7;
        }

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            padding: 23px;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .summary-label {
            margin-bottom: 9px;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .6px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            font-size: 27px;
            font-weight: 950;
        }

        .summary-value.blue {
            color: #2563eb;
        }

        .summary-value.cyan {
            color: #0284c7;
        }

        .summary-note {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 8px;
        }

        .content-grid {
            display: grid;
            grid-template-columns:
                minmax(280px, .7fr)
                minmax(0, 1.3fr);
            gap: 20px;
        }

        .panel {
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 22px;
            background: white;
            box-shadow:
                0 12px 35px
                rgba(15,23,42,.05);
        }

        .panel-header {
            padding: 20px 22px;
            border-bottom:
                1px solid #eff6ff;
        }

        .panel-header h2 {
            margin: 0 0 4px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .panel-header p {
            margin: 0;
            color: #94a3b8;
            font-size: 9px;
        }

        .panel-body {
            padding: 22px;
        }

        .rate-display {
            margin-bottom: 22px;
            padding: 22px;
            border-radius: 18px;
            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f0f9ff
                );
            text-align: center;
        }

        .rate-display small {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .rate-display strong {
            color: #1d4ed8;
            font-size: 48px;
            font-weight: 950;
        }

        .rate-form {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .form-label {
            color: #334155;
            font-size: 9px;
            font-weight: 900;
        }

        .rate-input-wrap {
            position: relative;
        }

        .rate-input {
            width: 100%;
            box-sizing: border-box;
            padding: 14px 48px 14px 15px;
            border: 1px solid #bfdbfe;
            border-radius: 13px;
            outline: none;
            background: #f8fbff;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
        }

        .rate-input:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 4px
                rgba(37,99,235,.08);
        }

        .percent-symbol {
            position: absolute;
            top: 50%;
            right: 15px;
            transform:
                translateY(-50%);
            color: #64748b;
            font-size: 12px;
            font-weight: 950;
        }

        .update-btn {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 13px;
            cursor: pointer;
            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #0284c7
                );
            color: white;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .5px;
            box-shadow:
                0 10px 25px
                rgba(37,99,235,.20);
            transition: .2s ease;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                0 14px 30px
                rgba(37,99,235,.27);
        }

        .message {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 800;
            line-height: 1.5;
        }

        .message.success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .message.error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .commission-table {
            width: 100%;
            border-collapse: collapse;
        }

        .commission-table th {
            padding: 12px;
            border-bottom:
                1px solid #e2e8f0;
            color: #64748b;
            font-size: 8px;
            font-weight: 950;
            text-align: left;
            text-transform: uppercase;
        }

        .commission-table td {
            padding: 14px 12px;
            border-bottom:
                1px solid #f1f5f9;
            color: #334155;
            font-size: 9px;
        }

        .commission-table tr:last-child td {
            border-bottom: 0;
        }

        .amount {
            color: #1d4ed8;
            font-weight: 950;
        }

        .rate-pill {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 8px;
            font-weight: 900;
        }

        .empty-state {
            padding: 45px 20px;
            text-align: center;
        }

        .empty-icon {
            margin-bottom: 10px;
            font-size: 30px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 5px;
            color: #334155;
            font-size: 11px;
        }

        .empty-state span {
            color: #94a3b8;
            font-size: 9px;
        }

        @media (max-width: 900px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .commission-page {
                padding: 25px 15px;
            }

            .commission-hero {
                padding: 25px 20px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<main class="commission-page">

    <div class="commission-container">

        <section class="commission-hero">

            <div class="commission-hero-content">

                <div class="hero-kicker">
                    HochipoHub Admin
                </div>

                <h1>
                    Commission Control
                </h1>

                <p>
                    Control the marketplace
                    commission rate and monitor
                    the platform's estimated
                    commission revenue.
                </p>

            </div>

        </section>


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


        <!-- SUMMARY -->

        <section class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Current Commission Rate
                </div>

                <div class="summary-value blue">
                    <?= number_format(
                        $currentRate,
                        2
                    ) ?>%
                </div>

                <div class="summary-note">
                    Current marketplace setting
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Marketplace Sales
                </div>

                <div class="summary-value">
                    <?= formatPrice(
                        $totalSales
                    ) ?>
                </div>

                <div class="summary-note">
                    Non-cancelled orders
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Estimated Commission
                </div>

                <div class="summary-value cyan">
                    <?= formatPrice(
                        $totalCommission
                    ) ?>
                </div>

                <div class="summary-note">
                    Based on current rate
                </div>

            </div>

        </section>


        <div class="content-grid">

            <!-- RATE SETTINGS -->

            <section class="panel">

                <div class="panel-header">

                    <h2>
                        Commission Settings
                    </h2>

                    <p>
                        Update the platform commission
                        percentage.
                    </p>

                </div>

                <div class="panel-body">

                    <div class="rate-display">

                        <small>
                            Active Rate
                        </small>

                        <strong>
                            <?= number_format(
                                $currentRate,
                                2
                            ) ?>%
                        </strong>

                    </div>


                    <form
                        method="POST"
                        class="rate-form"
                    >

                        <label
                            class="form-label"
                            for="commission_rate"
                        >
                            New Commission Rate
                        </label>

                        <div
                            class="rate-input-wrap"
                        >

                            <input
                                type="number"
                                id="commission_rate"
                                name="commission_rate"
                                class="rate-input"
                                min="0"
                                max="100"
                                step="0.01"
                                value="<?= e(
                                    number_format(
                                        $currentRate,
                                        2,
                                        '.',
                                        ''
                                    )
                                ) ?>"
                                required
                            >

                            <span
                                class="percent-symbol"
                            >
                                %
                            </span>

                        </div>


                        <button
                            type="submit"
                            name="update_rate"
                            value="1"
                            class="update-btn"
                        >
                            UPDATE COMMISSION RATE
                        </button>

                    </form>

                </div>

            </section>


            <!-- COMMISSION RECORDS -->

            <section class="panel">

                <div class="panel-header">

                    <h2>
                        Commission Records
                    </h2>

                    <p>
                        Latest commission entries
                        recorded by the marketplace.
                    </p>

                </div>

                <div class="panel-body">

                    <?php if (
                        empty($commissionRecords)
                    ): ?>

                        <div class="empty-state">

                            <div
                                class="empty-icon"
                            >
                                💰
                            </div>

                            <strong>
                                No commission records yet
                            </strong>

                            <span>
                                Commission activity will
                                appear here once records
                                are created.
                            </span>

                        </div>

                    <?php else: ?>

                        <div class="table-wrap">

                            <table
                                class="commission-table"
                            >

                                <thead>

                                    <tr>

                                        <?php
                                        $recordColumns =
                                            array_keys(
                                                $commissionRecords[0]
                                            );
                                        ?>

                                        <?php foreach (
                                            $recordColumns
                                            as $column
                                        ): ?>

                                            <th>
                                                <?= e(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $column
                                                    )
                                                ) ?>
                                            </th>

                                        <?php endforeach; ?>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach (
                                        $commissionRecords
                                        as $record
                                    ): ?>

                                        <tr>

                                            <?php foreach (
                                                $recordColumns
                                                as $column
                                            ): ?>

                                                <td>

                                                    <?php

                                                    $value =
                                                        $record[
                                                            $column
                                                        ];

                                                    if (
                                                        str_contains(
                                                            strtolower(
                                                                $column
                                                            ),
                                                            'amount'
                                                        )
                                                        ||
                                                        str_contains(
                                                            strtolower(
                                                                $column
                                                            ),
                                                            'commission'
                                                        )
                                                        &&
                                                        is_numeric(
                                                            $value
                                                        )
                                                    ) {

                                                        echo '<span class="amount">'
                                                            . e(
                                                                formatPrice(
                                                                    $value
                                                                )
                                                            )
                                                            . '</span>';

                                                    } elseif (
                                                        str_contains(
                                                            strtolower(
                                                                $column
                                                            ),
                                                            'rate'
                                                        )
                                                        &&
                                                        is_numeric(
                                                            $value
                                                        )
                                                    ) {

                                                        echo '<span class="rate-pill">'
                                                            . e(
                                                                $value
                                                            )
                                                            . '%</span>';

                                                    } else {

                                                        echo e(
                                                            $value
                                                        );
                                                    }

                                                    ?>

                                                </td>

                                            <?php endforeach; ?>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </div>

</main>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>

</body>

</html>
