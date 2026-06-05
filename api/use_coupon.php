<?php
require_once __DIR__ . '/../app/bootstrap.php';
qii_start_session();

header('Content-Type: application/json; charset=UTF-8');
qii_verify_frontend_csrf();

echo json_encode([
    'success' => true,
    'msg' => '优惠码将在订单提交成功后记录使用',
], JSON_UNESCAPED_UNICODE);
exit;
?>
