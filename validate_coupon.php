<?php
session_start();
require __DIR__ . "/a9sd8f7sd9f_admin/config.php";
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=UTF-8');

$code = $_POST['code'] ?? '';
$total = floatval($_POST['total'] ?? 0);

// Basic validation
if ($code === '') {
    echo json_encode(["success" => false, "msg" => "请输入优惠码"], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? LIMIT 1");
$stmt->execute([$code]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    echo json_encode(["success"=>false, "msg"=>"优惠码不存在"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($c['status'] ?? '') !== 'active') {
    echo json_encode(["success"=>false, "msg"=>"优惠码未启用"], JSON_UNESCAPED_UNICODE);
    exit;
}

$today = date("Y-m-d");
if ((!empty($c['start_date']) && $today < $c['start_date']) || (!empty($c['end_date']) && $today > $c['end_date'])) {
    echo json_encode(["success"=>false, "msg"=>"优惠码不在有效期内"], JSON_UNESCAPED_UNICODE);
    exit;
}

$max_usage = isset($c['max_usage']) ? (is_null($c['max_usage']) ? null : intval($c['max_usage'])) : null;
$used_count = isset($c['used_count']) ? intval($c['used_count']) : 0;
// 如果有限制次数且已达上限
if (!is_null($max_usage) && $used_count >= $max_usage) {
    echo json_encode([
        "success" => false,
        "msg" => "❌ 此优惠码已达使用上限"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$min_order = floatval($c['min_order'] ?? 0);
if ($total < $min_order) {
    echo json_encode(["success"=>false, "msg"=>"未达到最低订单金额：RM {$min_order}"], JSON_UNESCAPED_UNICODE);
    exit;
}

$discount_amount = floatval($c['discount_amount'] ?? 0);
$new_total = max(0, $total - $discount_amount);

// Optionally mark which code is used in session
$_SESSION["coupon_used"] = $code;

echo json_encode([
    "success" => true,
    "msg" => "🎉 优惠码使用成功 - 减 RM {$discount_amount}",
    "discount" => $discount_amount,
    "new_total" => number_format($new_total, 2)
], JSON_UNESCAPED_UNICODE);

// 成功后，记录使用次数 +1
try {
    if (isset($c['id'])) {
        $upd = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $upd->execute([$c['id']]);
    }
} catch (Exception $e) {
    // 忽略写入失败，避免影响前端体验
}
exit;
?>
