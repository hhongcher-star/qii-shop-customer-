<?php
// 🩷 Qii.shoppp Checkout 结算逻辑
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/a9sd8f7sd9f_admin/config.php';

// ----------------------------------------------------
// 1) 检查购物车
// ----------------------------------------------------
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode([
        "success" => false,
        "msg" => "购物袋是空的～🎀"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------
// 2) 必须收到地区（East / West）
// ----------------------------------------------------
$region = $_POST['region'] ?? null;

if (!$region || !in_array($region, ['west', 'east'])) {
    echo json_encode([
        "success" => false,
        "msg" => "请选择地区（西马 / 东马）才能结账💕"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------
// 3) 计算总价 + 检查库存
// ----------------------------------------------------
$total = 0;

foreach ($_SESSION['cart'] as $item) {

    $stmt = $pdo->prepare("SELECT stock FROM products WHERE sku = ? LIMIT 1");
    $stmt->execute([$item['sku']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            "success" => false,
            "msg" => "未找到商品：" . htmlspecialchars($item['name'])
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stock = intval($row['stock']);
    if ($stock < $item['qty']) {
        echo json_encode([
            "success" => false,
            "msg" => "库存不足：" . htmlspecialchars($item['name']) . "（剩余 $stock）"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total += $item['price'] * $item['qty'];
}

// ----------------------------------------------------
// 4) 运费计算
// ----------------------------------------------------
if ($region === 'west') {
    $shipping = ($total >= 65) ? 0 : 10;
} else {
    $shipping = ($total >= 80) ? 0 : 15;
}

$grand_total = $total + $shipping;

// ----------------------------------------------------
// 5) ⭐ 生成一次性的临时订单号（不写数据库）
// ----------------------------------------------------
$order_number = "QII" . date("ymdHis") . rand(100, 999);

// ----------------------------------------------------
// 6) 写入 Session 用于 receipt.php 显示
// ----------------------------------------------------
$_SESSION['pending_order'] = [
    "order_number" => $order_number,
    "items"        => $_SESSION['cart'],
    "region"       => $region,
    "total"        => $total,
    "shipping"     => $shipping,
    "grand_total"  => $grand_total,
    "created_at"   => date("Y-m-d H:i:s")
];

// ----------------------------------------------------
// 7) 返回成功，跳去 receipt.php
// ----------------------------------------------------
echo json_encode([
    "success" => true,
    "order_number" => $order_number,
    "redirect" => "receipt.php?order_number=" . urlencode($order_number)
], JSON_UNESCAPED_UNICODE);
exit;
?>
