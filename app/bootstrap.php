<?php
declare(strict_types=1);

const QII_APP_ENV = 'production';

if (QII_APP_ENV === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!function_exists('qii_start_session')) {
function qii_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }
}
}

if (!function_exists('qii_frontend_csrf_token')) {
function qii_frontend_csrf_token(): string
{
    qii_start_session();
    if (empty($_SESSION['frontend_csrf_token'])) {
        $_SESSION['frontend_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['frontend_csrf_token'];
}
}

if (!function_exists('qii_frontend_csrf_meta')) {
function qii_frontend_csrf_meta(): void
{
    echo '<meta name="qii-csrf-token" content="' . htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') . '">' . "\n";
}
}

if (!function_exists('qii_verify_frontend_csrf')) {
function qii_verify_frontend_csrf(): void
{
    qii_start_session();
    $token = $_SERVER['HTTP_X_QII_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($token) || !hash_equals($_SESSION['frontend_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid request token'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
}

if (!function_exists('qii_text')) {
function qii_text($text): string
{
    $text = (string)$text;
    if ($text === '') return '';

    if (preg_match('/[ÂµÃžÃ•ÃšÃÃ¾â•”â•â•‘â•£â•â•—â–“â–‘â”¤â”â””â”´â”¬â”œâ”¼µÞÕÚÐþ╔═║╣╝╗▓░┤┐└┴┬├┼]/u', $text)) {
        $fixed = @iconv('UTF-8', 'CP850//IGNORE', $text);
        if (is_string($fixed) && $fixed !== '' && preg_match('/[\x{4E00}-\x{9FFF}]/u', $fixed)) {
            return $fixed;
        }
    }

    return $text;
}
}

if (!function_exists('qii_asset_path')) {
function qii_asset_path($path): string
{
    $path = trim((string)$path);
    if ($path === '') return 'images/logo.png';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    $path = ltrim($path, '/');
    if (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) return $path;
    return 'uploads/' . $path;
}
}

if (!function_exists('qii_shipping_for_region')) {
function qii_shipping_for_region(float $subtotal, string $region): float
{
    if ($region === 'west') return $subtotal >= 65 ? 0.0 : 10.0;
    if ($region === 'east') return $subtotal >= 80 ? 0.0 : 15.0;
    if ($region === 'hold') return 0.0;
    throw new InvalidArgumentException('INVALID_REGION');
}
}

if (!function_exists('qii_calculate_coupon')) {
function qii_calculate_coupon(PDO $pdo, string $code, float $subtotal): array
{
    $code = trim($code);
    if ($code === '') return ['valid' => false, 'message' => '请输入优惠码'];

    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code=? LIMIT 1');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) return ['valid' => false, 'message' => '优惠码不存在'];
    if (($coupon['status'] ?? '') !== 'active') return ['valid' => false, 'message' => '优惠码未启用'];

    $today = date('Y-m-d');
    if ((!empty($coupon['start_date']) && $today < $coupon['start_date']) || (!empty($coupon['end_date']) && $today > $coupon['end_date'])) {
        return ['valid' => false, 'message' => '优惠码不在有效期内'];
    }

    $maxUsage = array_key_exists('max_usage', $coupon) && $coupon['max_usage'] !== null ? (int)$coupon['max_usage'] : null;
    $usedCount = (int)($coupon['used_count'] ?? 0);
    if ($maxUsage !== null && $usedCount >= $maxUsage) {
        return ['valid' => false, 'message' => '此优惠码已达使用上限'];
    }

    $minOrder = (float)($coupon['min_order'] ?? 0);
    if ($subtotal < $minOrder) {
        return ['valid' => false, 'message' => '未达到最低订单金额：RM ' . number_format($minOrder, 2)];
    }

    $discount = max(0.0, min($subtotal, (float)($coupon['discount_amount'] ?? 0)));
    return [
        'valid' => true,
        'message' => '优惠码使用成功 - 减 RM ' . number_format($discount, 2),
        'coupon' => $coupon,
        'discount' => $discount,
    ];
}
}

if (!function_exists('qii_increment_coupon_usage')) {
function qii_increment_coupon_usage(PDO $pdo, int $couponId): void
{
    $stmt = $pdo->prepare("
        UPDATE coupons
        SET used_count = used_count + 1,
            status = CASE
                WHEN max_usage IS NOT NULL AND used_count + 1 >= max_usage THEN 'inactive'
                ELSE status
            END
        WHERE id = ?
    ");
    $stmt->execute([$couponId]);
}
}

if (!function_exists('qii_ensure_order_security_columns')) {
function qii_ensure_order_security_columns(PDO $pdo): void
{
    $columns = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('receipt_token', $columns, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN receipt_token VARCHAR(128) NULL AFTER order_number');
    }
    if (!in_array('coupon_code', $columns, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(80) NULL AFTER discount');
    }
    if (!in_array('order_note', $columns, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN order_note TEXT NULL AFTER addr_state');
    }
}
}
?>
