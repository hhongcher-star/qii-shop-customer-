<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $order_number = $_POST['order_number'] ?? '';
    $status = $_POST['status'] ?? '';
    $allowedStatuses = ['pending', 'awaiting_payment', 'paid', 'shipped', 'completed'];

    if ($order_number && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status=?, updated_at=NOW() WHERE order_number=?");
        $stmt->execute([$status, $order_number]);

        $_SESSION['msg'] = "✅ 订单状态已更新";
    } else {
        $_SESSION['msg'] = "⚠️ 更新失败：缺少参数";
    }
}

header("Location: order.php");
exit;
