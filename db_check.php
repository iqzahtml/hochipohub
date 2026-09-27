<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$db = getDB();

echo '<h2>HOCHIPOHUB DATABASE CHECK</h2>';

echo '<pre>';

try {

    $info = $db->query("
        SELECT
            DATABASE() AS database_name,
            @@hostname AS mysql_hostname,
            @@port AS mysql_port
    ")->fetch(PDO::FETCH_ASSOC);

    echo "DATABASE NAME : " . ($info['database_name'] ?? '-') . PHP_EOL;
    echo "MYSQL HOSTNAME: " . ($info['mysql_hostname'] ?? '-') . PHP_EOL;
    echo "MYSQL PORT    : " . ($info['mysql_port'] ?? '-') . PHP_EOL;

    echo PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;
    echo "VENDOR_ORDERS COLUMN CHECK" . PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;

    $stmt = $db->query("
        SHOW COLUMNS FROM vendor_orders
    ");

    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {

        echo $column['Field'] . PHP_EOL;
    }

    echo PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;

    $columnNames = array_column($columns, 'Field');

    echo "courier_name   : "
        . (in_array('courier_name', $columnNames, true) ? 'EXISTS' : 'NOT EXISTS')
        . PHP_EOL;

    echo "tracking_number: "
        . (in_array('tracking_number', $columnNames, true) ? 'EXISTS' : 'NOT EXISTS')
        . PHP_EOL;

    echo "completed_at   : "
        . (in_array('completed_at', $columnNames, true) ? 'EXISTS' : 'NOT EXISTS')
        . PHP_EOL;

} catch (Throwable $e) {

    echo "ERROR:" . PHP_EOL;
    echo $e->getMessage();
}

echo '</pre>';