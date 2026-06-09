<?php
// Database connection.
// Local development automatically uses the XAMPP/Laragon database.
// Production keeps the Hostinger database values unless environment variables are set.

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000'], true)
    || PHP_SAPI === 'cli';

if ($isLocal) {
    $host = getenv('QII_DB_HOST') ?: '127.0.0.1';
    $db   = getenv('QII_DB_NAME') ?: 'qi_shop';
    $user = getenv('QII_DB_USER') ?: 'root';
    $pass = getenv('QII_DB_PASS') ?: '';
} else {
    $host = getenv('QII_DB_HOST') ?: 'localhost';
    $db   = getenv('QII_DB_NAME') ?: 'u751690829_qi_shop';
    $user = getenv('QII_DB_USER') ?: 'u751690829_qi_shop_user';
    $pass = getenv('QII_DB_PASS') ?: '#M!uYL8y';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    date_default_timezone_set("Asia/Kuala_Lumpur");
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed.");
}
?>
