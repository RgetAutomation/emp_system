<?php
$host = 'kodama.proxy.rlwy.net';
$port = 20655;
$db = 'railway';
$user = 'root';
$pass = 'vwslSLgNhkEaFHrWoxFJDhcFMJXxHFGu';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    echo "SUCCESS: Database is connected!\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
