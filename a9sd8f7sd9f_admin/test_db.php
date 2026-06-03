<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT NOW() AS current_time");
$row = $stmt->fetch();
echo "✅ 数据库连接成功！当前时间：" . $row['current_time'];
?>
