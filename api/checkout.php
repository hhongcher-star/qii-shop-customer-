<?php
require_once __DIR__ . '/../app/bootstrap.php';
qii_start_session();
require_once __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

header('Content-Type: application/json; charset=utf-8');
qii_verify_frontend_csrf();

function qii_normalize_cart(array $cart): array
{
    foreach ($cart as &$item) {
        if (isset($item['product_name'])) $item['product_name'] = qii_text($item['product_name']);
        if (isset($item['name'])) $item['name'] = qii_text($item['name']);
        if (isset($item['variant_name'])) $item['variant_name'] = qii_text($item['variant_name']);
    }
    unset($item);
    return $cart;
}

if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'msg' => '购物袋是空的'], JSON_UNESCAPED_UNICODE);
    exit;
}

$region = $_POST['region'] ?? null;
if (!in_array($region, ['west', 'east'], true)) {
    echo json_encode(['success' => false, 'msg' => '请选择地区（西马 / 东马）才能结账'], JSON_UNESCAPED_UNICODE);
    exit;
}

$total = 0.0;
foreach ($_SESSION['cart'] as $item) {
    $variantId = (int)($item['variant_id'] ?? 0);
    $productId = (int)($item['product_id'] ?? 0);
    $qty = (int)($item['qty'] ?? 0);
    if ($qty <= 0) {
        echo json_encode(['success' => false, 'msg' => '购物车数量不正确'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($variantId > 0) {
        $stmt = $pdo->prepare("
            SELECT v.stock, COALESCE(v.price, p.price) AS price
            FROM product_variants v
            INNER JOIN product_groups g ON g.id = v.group_id
            INNER JOIN products p ON p.id = g.product_id
            WHERE v.id = ? AND p.id = ? AND COALESCE(p.status, 'active') = 'active'
            LIMIT 1
        ");
        $stmt->execute([$variantId, $productId]);
    } else {
        $stmt = $pdo->prepare("SELECT stock, price FROM products WHERE id = ? AND COALESCE(status, 'active') = 'active' LIMIT 1");
        $stmt->execute([$productId]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'msg' => '未找到商品：' . qii_text($item['product_name'] ?? '商品')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stock = (int)$row['stock'];
    if ($stock < $qty) {
        echo json_encode(['success' => false, 'msg' => '库存不足：' . qii_text($item['product_name'] ?? '商品') . "（剩余 $stock）"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total += (float)$row['price'] * $qty;
}

$shipping = qii_shipping_for_region($total, $region);
$orderNumber = 'QII' . date('ymdHis') . random_int(100, 999);
$receiptToken = bin2hex(random_bytes(24));

$_SESSION['current_order'] = $orderNumber;
$_SESSION['pending_order'] = [
    'order_number' => $orderNumber,
    'receipt_token' => $receiptToken,
    'items' => qii_normalize_cart($_SESSION['cart']),
    'region' => $region,
    'total' => $total,
    'shipping' => $shipping,
    'grand_total' => $total + $shipping,
    'created_at' => date('Y-m-d H:i:s'),
];

echo json_encode([
    'success' => true,
    'order_number' => $orderNumber,
    'redirect' => 'receipt.php?order_number=' . urlencode($orderNumber) . '&token=' . urlencode($receiptToken),
], JSON_UNESCAPED_UNICODE);
exit;
?>
