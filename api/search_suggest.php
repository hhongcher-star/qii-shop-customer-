<?php
session_start();

require_once __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

header('Content-Type: application/json; charset=utf-8');

function qii_asset_path($path) {
    $path = trim((string)$path);
    if ($path === '') return 'images/logo.png';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    $path = ltrim($path, '/');
    if (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) return $path;
    return 'uploads/' . $path;
}

$q = trim($_GET['q'] ?? '');
$results = [];

try {
    if ($q !== '') {
        $sql = "
            SELECT id, sku, name, image_url, price, stock 
            FROM products 
            WHERE COALESCE(status, 'active') = 'active'
              AND (name LIKE ? OR sku LIKE ?)
            ORDER BY created_at DESC
            LIMIT 10
        ";

        $stmt = $pdo->prepare($sql);
        $like = "%{$q}%";
        $stmt->execute([$like, $like]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 统一图片路径到真实商品目录
        foreach ($results as &$row) {
            $row['image_url'] = qii_asset_path($row['image_url'] ?? '');
        }
    }

    echo json_encode([
        "keywords" => $q,
        "results"  => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Search suggest failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "搜索暂时不可用"], JSON_UNESCAPED_UNICODE);
}
