<?php
require_once __DIR__ . '/../app/bootstrap.php';
qii_start_session();

header('Content-Type: text/plain; charset=UTF-8');
qii_verify_frontend_csrf();

$code = trim((string)($_POST['code'] ?? ''));
if ($code === '') {
    http_response_code(400);
    echo 'missing code';
    exit;
}

if (!empty($_SESSION['current_order'])) {
    $orderNumber = (string)$_SESSION['current_order'];
    if (!isset($_SESSION['coupon_code']) || !is_array($_SESSION['coupon_code'])) {
        $_SESSION['coupon_code'] = [];
    }
    $_SESSION['coupon_code'][$orderNumber] = $code;
} else {
    $_SESSION['coupon_code_pending'] = $code;
}

echo 'saved';
exit;
?>
