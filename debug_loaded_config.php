<?php

header('Content-Type: text/plain');

require __DIR__ . '/config.php';

echo "=== SERVER ===" . PHP_EOL;
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NONE') . PHP_EOL;
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NONE') . PHP_EOL;
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NONE') . PHP_EOL;

echo PHP_EOL . "=== CONFIG RESULT ===" . PHP_EOL;
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . PHP_EOL;

echo PHP_EOL . "=== FILES PHP ACTUALLY LOADED ===" . PHP_EOL;

foreach (get_included_files() as $file) {
    echo $file . PHP_EOL;
}

echo PHP_EOL . "=== CONFIG FILE ON DISK ===" . PHP_EOL;
echo "Expected config: " . realpath(__DIR__ . '/config.php') . PHP_EOL;
echo "Modified: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/config.php')) . PHP_EOL;

echo PHP_EOL . "=== CHECK NEW FIX EXISTS ===" . PHP_EOL;

$configContent = file_get_contents(__DIR__ . '/config.php');

echo strpos(
    $configContent,
    "IMPORTANT BASE PATH RULE"
) !== false
    ? "NEW CONFIG FOUND"
    : "OLD CONFIG FOUND";

?>
