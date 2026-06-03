<?php
// ==========================
// 数据库连接配置 (Hostinger - qii.shoppp)
// ==========================

$host = "127.0.0.1"; // Hostinger 默认 MySQL 主机
$db   = "u751690829_Qiishop";   // ✅ 你的数据库名称
$user = "u751690829_qiishop2";  // ✅ 你的数据库用户
$pass = "Hong63@555555";        // ✅ 你的数据库密码

try {
    // 使用 PDO 连接 MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);

    // 错误报告模式
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 默认获取模式为关联数组
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ==========================
    // ✅ 统一时区
    // ==========================
    date_default_timezone_set("Asia/Kuala_Lumpur");
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {
    die("❌ 数据库连接失败：" . $e->getMessage());
}
?>
