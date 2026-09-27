<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$db = getDB();

echo '<h2>HOCHIPOHUB - FIX COURIER COLUMN</h2>';
echo '<pre>';

try {

    $check = $db->query("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'vendor_orders'
        AND COLUMN_NAME = 'courier_name'
    ");

    $exists = (int) $check->fetchColumn();

    if ($exists === 0) {

        $db->exec("
            ALTER TABLE vendor_orders
            ADD COLUMN courier_name VARCHAR(100) NULL
            AFTER vendor_status
        ");

        echo "SUCCESS: courier_name has been added." . PHP_EOL;

    } else {

        echo "INFO: courier_name already exists." . PHP_EOL;
    }

    echo PHP_EOL;
    echo "CURRENT VENDOR_ORDERS COLUMNS:" . PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;

    $columns = $db->query("
        SHOW COLUMNS FROM vendor_orders
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        echo $column['Field'] . PHP_EOL;
    }

} catch (Throwable $e) {

    echo "ERROR:" . PHP_EOL;
    echo $e->getMessage();
}

echo '</pre>';