<?php
require_once __DIR__ . '/../app/customers.php';
qii_start_session();
require_once __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

header('Content-Type: application/json; charset=UTF-8');
qii_verify_frontend_csrf();
qii_ensure_customer_tables($pdo);

$customerId = qii_customer_id();
if (!$customerId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'login_required' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid product'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM products WHERE id=? AND COALESCE(status, 'active')='active' LIMIT 1");
$stmt->execute([$productId]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare('SELECT 1 FROM customer_favorites WHERE customer_id=? AND product_id=?');
$stmt->execute([$customerId, $productId]);
$isFavorite = (bool)$stmt->fetchColumn();

if ($isFavorite) {
    $pdo->prepare('DELETE FROM customer_favorites WHERE customer_id=? AND product_id=?')->execute([$customerId, $productId]);
    $isFavorite = false;
} else {
    $pdo->prepare('INSERT IGNORE INTO customer_favorites (customer_id, product_id) VALUES (?, ?)')->execute([$customerId, $productId]);
    $isFavorite = true;
}

echo json_encode(['success' => true, 'favorite' => $isFavorite], JSON_UNESCAPED_UNICODE);
?>
