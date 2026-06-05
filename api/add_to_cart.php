<?php
require_once __DIR__ . '/../app/bootstrap.php';
qii_start_session();
require __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

header('Content-Type: application/json; charset=UTF-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    qii_verify_frontend_csrf();
}

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

$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$mode = $_GET['mode'] ?? 'add';

if ($mode === 'getCart') {
    echo json_encode([
        'success' => true,
        'count' => array_sum(array_column($_SESSION['cart'], 'qty')),
        'cart' => qii_normalize_cart(array_values($_SESSION['cart'])),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($mode === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'count' => 0, 'cart' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$productId = (int)($_POST['id'] ?? 0);
$variantId = (int)($_POST['variant_id'] ?? 0);
$variantName = trim((string)($_POST['variant_name'] ?? ''));
$qty = max(1, (int)($_POST['qty'] ?? 1));

if ($mode === 'removeOne') {
    foreach ($_SESSION['cart'] as $key => &$item) {
        if ((int)$item['product_id'] === $productId && (int)$item['variant_id'] === $variantId) {
            $item['qty']--;
            if ($item['qty'] <= 0) unset($_SESSION['cart'][$key]);
            break;
        }
    }
    unset($item);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    echo json_encode([
        'success' => true,
        'count' => array_sum(array_column($_SESSION['cart'], 'qty')),
        'cart' => qii_normalize_cart(array_values($_SESSION['cart'])),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($mode === 'add') {
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($variantId > 0) {
        $stmt = $pdo->prepare("
            SELECT p.name AS product_name, p.image_url AS product_image,
                   v.stock, COALESCE(v.price, p.price) AS price, v.image_url AS variant_image, v.variant_name
            FROM product_variants v
            INNER JOIN product_groups g ON g.id = v.group_id
            INNER JOIN products p ON p.id = g.product_id
            WHERE v.id = ? AND p.id = ? AND COALESCE(p.status, 'active') = 'active'
            LIMIT 1
        ");
        $stmt->execute([$variantId, $productId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT name AS product_name, image_url AS product_image,
                   stock, price, NULL AS variant_image, '' AS variant_name
            FROM products
            WHERE id = ? AND COALESCE(status, 'active') = 'active'
            LIMIT 1
        ");
        $stmt->execute([$productId]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => '商品已下架'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stock = (int)$row['stock'];
    if ($stock <= 0) {
        echo json_encode(['success' => false, 'message' => '库存不足'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ((int)$item['product_id'] === $productId && (int)$item['variant_id'] === $variantId) {
            if ((int)$item['qty'] + $qty > $stock) {
                echo json_encode(['success' => false, 'message' => '库存不足'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $item['qty'] += $qty;
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'product_name' => qii_text($row['product_name']),
            'variant_id' => $variantId,
            'variant_name' => qii_text($variantName !== '' ? $variantName : ($row['variant_name'] ?? '')),
            'price' => (float)$row['price'],
            'img' => qii_asset_path($row['variant_image'] ?: $row['product_image']),
            'qty' => $qty,
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => array_sum(array_column($_SESSION['cart'], 'qty')),
    'cart' => qii_normalize_cart(array_values($_SESSION['cart'])),
], JSON_UNESCAPED_UNICODE);
exit;
?>
