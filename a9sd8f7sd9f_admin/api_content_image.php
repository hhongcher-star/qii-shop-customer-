<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/content_settings.php';

header('Content-Type: application/json; charset=utf-8');
verify_csrf();

$key = trim((string)($_POST['key'] ?? ''));
$allowedKeys = ['hero_image', 'about_image', 'gift_image', 'daily_image', 'shop_promo_image', 'contact_image'];
if (!in_array($key, $allowedKeys, true) || empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['success' => false]);
    exit;
}

$types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
$mime = mime_content_type($_FILES['image']['tmp_name']);
if (!isset($types[$mime]) || $_FILES['image']['size'] > 5 * 1024 * 1024 || getimagesize($_FILES['image']['tmp_name']) === false) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => '图片格式或大小不正确']);
    exit;
}

$dir = dirname(__DIR__) . '/images/content';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$filename = $key . '_' . uniqid() . '.' . $types[$mime];
$relative = 'images/content/' . $filename;
if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $filename)) {
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit;
}

qii_save_content($pdo, [$key => $relative]);
echo json_encode(['success' => true, 'url' => '../' . $relative], JSON_UNESCAPED_UNICODE);
