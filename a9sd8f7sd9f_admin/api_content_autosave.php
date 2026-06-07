<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/content_settings.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

verify_csrf();
$key = trim((string)($_POST['key'] ?? ''));
$value = (string)($_POST['value'] ?? '');
$allowed = [
    'hero_title','hero_subtitle','hero_description','hero_button',
    'about_title','about_text','gift_title','gift_text','daily_title','daily_text',
    'shop_title','shop_promo_title','shop_promo_text','shop_promo_button',
    'contact_title','contact_description','contact_button','contact_social_text',
    'announcement_title','announcement_intro','announcement_quality','announcement_storage',
    'announcement_shipping','announcement_dispatch','announcement_warning','announcement_button',
    'variant_choose_title','variant_quantity_title','variant_max_text','variant_shipping_text',
    'variant_quality_text','variant_return_text','variant_cart_button',
];

if (!in_array($key, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Unsupported content key', 'key' => $key], JSON_UNESCAPED_UNICODE);
    exit;
}

qii_save_content($pdo, [$key => qii_sanitize_rich_text($value)]);
echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
