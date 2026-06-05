<?php
require_once __DIR__ . '/../app/bootstrap.php';
qii_start_session();
require __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

header('Content-Type: application/json; charset=UTF-8');
qii_verify_frontend_csrf();

$code = trim((string)($_POST['code'] ?? ''));
$total = (float)($_POST['total'] ?? 0);

if ($code === '') {
    echo json_encode(['success' => false, 'msg' => '请输入优惠码'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = qii_calculate_coupon($pdo, $code, $total);
if (!$result['valid']) {
    echo json_encode(['success' => false, 'msg' => $result['message']], JSON_UNESCAPED_UNICODE);
    exit;
}

$discount = (float)$result['discount'];
echo json_encode([
    'success' => true,
    'msg' => $result['message'],
    'discount' => $discount,
    'new_total' => number_format(max(0, $total - $discount), 2),
], JSON_UNESCAPED_UNICODE);
exit;
?>
