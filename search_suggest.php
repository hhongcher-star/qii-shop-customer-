<?php
session_start();

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . '/a9sd8f7sd9f_admin/config.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$results = [];

try {
    if ($q !== '') {
        $sql = "
            SELECT id, sku, name, image_url, price, stock 
            FROM products 
            WHERE name LIKE ? 
               OR sku LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ";

        $stmt = $pdo->prepare($sql);
        $like = "%{$q}%";
        $stmt->execute([$like, $like]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 统一图片路径到真实商品目录
        foreach ($results as &$row) {
            if (!empty($row['image_url'])) {
                $row['image_url'] = 'a9sd8f7sd9f_admin/' . ltrim($row['image_url'], '/');
            }
        }
    }

    echo json_encode([
        "keywords" => $q,
        "results"  => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
