<?php
// Local database connection for XAMPP/Laragon.
// Database name: qi_shop

$host = "127.0.0.1";
$db   = "qi_shop";
$user = "root";
$pass = "";

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
