<?php
session_start();
require __DIR__ . "/a9sd8f7sd9f_admin/config.php";
date_default_timezone_set("Asia/Kuala_Lumpur");

header("Content-Type: application/json; charset=UTF-8");

$code = $_POST['code'] ?? "";

if ($code === "") {
    echo json_encode(["success" => false, "msg" => "缺少优惠码"]);
    exit;
}

// 读取优惠码
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? LIMIT 1");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(["success" => false, "msg" => "优惠码不存在"]);
    exit;
}

// 如果有上限，则检查是否已满
if ($coupon['max_usage'] !== null && $coupon['used_count'] >= $coupon['max_usage']) {
    echo json_encode(["success" => false, "msg" => "优惠码已达到使用次数"]);
    exit;
}

// 写入 +1，并在达到上限时自动置为 inactive
$update = $pdo->prepare("
    UPDATE coupons 
    SET used_count = used_count + 1,
        status = CASE 
                    WHEN max_usage IS NOT NULL AND used_count + 1 >= max_usage 
                    THEN 'inactive' 
                    ELSE status 
                 END
    WHERE id=?
");
$update->execute([$coupon['id']]);

echo json_encode(["success" => true, "msg" => "优惠码已记录使用"]);
exit;

