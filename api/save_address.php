<?php
require_once __DIR__ . '/../app/bootstrap.php';
qii_start_session();
require __DIR__ . "/../a9sd8f7sd9f_admin/config.php";

header("Content-Type: text/plain; charset=UTF-8");
qii_verify_frontend_csrf();

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    exit("❌ Invalid request method");
}

// 收到资料
$order_number = trim($_POST['order_number'] ?? '');
$name        = trim($_POST['name'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$address     = trim($_POST['address'] ?? '');
$postcode    = trim($_POST['postcode'] ?? '');
$state       = trim($_POST['state'] ?? '');
$orderNote   = trim($_POST['order_note'] ?? '');

// 必填检查
if (!$order_number || !$name || !$phone) {
    exit("❌ 请填写收件人姓名和联系电话");
}

qii_ensure_order_security_columns($pdo);

// 额外安全验证：订单必须属于当前 session
if (!isset($_SESSION['pending_order']) || 
    $_SESSION['pending_order']['order_number'] !== $order_number) {

    exit("❌ Order session expired or invalid");
}

try {
    // 检查订单是否存在
    $check = $pdo->prepare("SELECT id FROM orders WHERE order_number = ? LIMIT 1");
    $check->execute([$order_number]);

    if ($check->rowCount() === 0) {
        exit("❌ Order not found");
    }

    // 更新订单地址 + 状态（金额不动）
    $stmt = $pdo->prepare("
        UPDATE orders SET
            addr_name = ?,
            addr_phone = ?,
            addr_address = ?,
            addr_postcode = ?,
            addr_state = ?,
            order_note = ?,
            order_status = CASE
                WHEN region = 'hold' AND order_status = 'stored_combined' THEN 'stored_combined'
                WHEN region = 'hold' THEN 'stored_uncombined'
                ELSE 'pending'
            END
        WHERE order_number = ?
        LIMIT 1
    ");

    $stmt->execute([
        $name,
        $phone,
        $address,
        $postcode,
        $state,
        $orderNote ?: null,
        $order_number
    ]);

    // 同步回 SESSION，避免刷新后又变回空
    $_SESSION['pending_order']['addr_name']     = $name;
    $_SESSION['pending_order']['addr_phone']    = $phone;
    $_SESSION['pending_order']['addr_address']  = $address;
    $_SESSION['pending_order']['addr_postcode'] = $postcode;
    $_SESSION['pending_order']['addr_state']    = $state;
    $_SESSION['pending_order']['order_note']    = $orderNote;

    echo "OK";

} catch (Exception $e) {
    http_response_code(500);
    echo "❌ Error: " . $e->getMessage();
}
