<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/categories.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '请求方式不正确'], JSON_UNESCAPED_UNICODE);
    exit;
}

verify_csrf();

$category = trim((string)($_POST['category'] ?? ''));
$ids = json_decode((string)($_POST['product_ids'] ?? '[]'), true);
$categories = qii_categories($pdo, false);

if (!isset($categories[$category]) || !is_array($ids)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => '分类或商品顺序无效'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
$stmt = $pdo->prepare("SELECT id FROM products WHERE category = ? AND COALESCE(status, 'active') = 'active' ORDER BY sort_order, id");
$stmt->execute([$category]);
$expectedIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$submitted = $ids;
$expected = $expectedIds;
sort($submitted);
sort($expected);
if ($submitted !== $expected) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => '商品列表已经变化，请刷新后重试'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo->beginTransaction();
try {
    $update = $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ? AND category = ?");
    foreach ($ids as $index => $productId) {
        $update->execute([$index + 1, $productId, $category]);
    }
    $nextOrder = count($ids) + 1;
    $inactive = $pdo->prepare("
        SELECT id
        FROM products
        WHERE category = ? AND COALESCE(status, 'active') <> 'active'
        ORDER BY sort_order, id
    ");
    $inactive->execute([$category]);
    foreach ($inactive->fetchAll(PDO::FETCH_COLUMN) as $productId) {
        $update->execute([$nextOrder++, (int)$productId, $category]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => '商品顺序已保存'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Product sort save failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '保存失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
}
