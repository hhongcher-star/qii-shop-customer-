
<?php
// Qii.shoppp - Add To Cart API with Variant/No-Variant support
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/a9sd8f7sd9f_admin/config.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$mode = $_GET['mode'] ?? 'add';

$product_id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$variant_id   = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$variant_name = isset($_POST['variant_name']) ? trim($_POST['variant_name']) : '';
$price        = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
$img          = isset($_POST['img']) ? trim($_POST['img']) : '';
$qty          = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
$qty = max(1, $qty);

// ---------- get cart ----------
if ($mode === 'getCart') {
    $count = array_sum(array_column($_SESSION['cart'], 'qty'));
    echo json_encode([
        'success' => true,
        'count'   => $count,
        'cart'    => array_values($_SESSION['cart'])
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- clear cart ----------
if ($mode === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'count' => 0, 'cart' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- remove one ----------
if ($mode === 'removeOne') {
    foreach ($_SESSION['cart'] as $k => &$item) {
        if ($item['product_id'] == $product_id && $item['variant_id'] == $variant_id) {
            $item['qty']--;
            if ($item['qty'] <= 0) {
                unset($_SESSION['cart'][$k]);
            }
            break;
        }
    }
    unset($item);
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    $count = array_sum(array_column($_SESSION['cart'], 'qty'));
    echo json_encode(['success' => true, 'count' => $count, 'cart' => array_values($_SESSION['cart'])], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- add ----------
if ($mode === 'add') {
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => '?? ID ??'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stock         = 0;
    $variant_price = $price;
    $product_name  = '';
    $final_img     = $img;

    // base product name
    $stmt = $pdo->prepare('SELECT name FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$product_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product_name = $row['name'];
    }

    if ($variant_id === 0) {
        // no variant -> use product stock/price/image
        $stmt = $pdo->prepare('SELECT stock, price, image_url FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => '?????'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stock         = (int)$row['stock'];
        $variant_price = $row['price'] !== null ? (float)$row['price'] : $price;
        $final_img     = 'a9sd8f7sd9f_admin/' . ($row['image_url'] ?? '');
        if ($variant_name === '') {
            $variant_name = '??';
        }
    } else {
        // variant path
        $stmt = $pdo->prepare('SELECT stock, price, image_url FROM product_variants WHERE id = ? LIMIT 1');
        $stmt->execute([$variant_id]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$variant) {
            echo json_encode(['success' => false, 'message' => '?????'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stock         = (int)$variant['stock'];
        $variant_price = $variant['price'] !== null ? (float)$variant['price'] : $price;

        if (!empty($variant['image_url'])) {
            $final_img = 'a9sd8f7sd9f_admin/' . $variant['image_url'];
        }
    }

    if ($stock <= 0) {
        echo json_encode(['success' => false, 'message' => '????'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // merge cart line if same product+variant
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id && $item['variant_id'] == $variant_id) {
            if ($item['qty'] + $qty <= $stock) {
                $item['qty'] += $qty;
            } else {
                echo json_encode(['success' => false, 'message' => '??????'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id'   => $product_id,
            'product_name' => $product_name,
            'variant_id'   => $variant_id,
            'variant_name' => $variant_name,
            'price'        => $variant_price,
            'img'          => $final_img,
            'qty'          => $qty,
        ];
    }
}

$count = array_sum(array_column($_SESSION['cart'], 'qty'));
echo json_encode([
    'success' => true,
    'count'   => $count,
    'cart'    => array_values($_SESSION['cart'])
], JSON_UNESCAPED_UNICODE);
exit;
