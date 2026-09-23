<?php
require __DIR__ . '/config.php';

header('Content-Type: text/plain');

echo "HTTP_HOST = " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . PHP_EOL;
echo "DOCUMENT_ROOT = " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . PHP_EOL;
echo "__DIR__ = " . __DIR__ . PHP_EOL;
echo "BASE_URL = " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . PHP_EOL;
?>
