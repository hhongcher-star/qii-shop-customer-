<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/customers.php';
qii_start_session();
require __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

header('Content-Type: text/plain; charset=UTF-8');
qii_verify_frontend_csrf();

$order_number = trim((string)($_POST['order_number'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$postcode = trim((string)($_POST['postcode'] ?? ''));
$state = trim((string)($_POST['state'] ?? ''));
$orderNote = trim((string)($_POST['order_note'] ?? ''));
if (function_exists('mb_substr')) {
    $orderNote = mb_substr($orderNote, 0, 500, 'UTF-8');
} else {
    $orderNote = substr($orderNote, 0, 500);
}

if ($order_number === '') exit('NO_ORDER');
if ($name === '') exit('NO_ADDRESS');

$po = $_SESSION['pending_order'] ?? null;
if (!$po || ($po['order_number'] ?? '') !== $order_number || empty($po['items']) || !is_array($po['items'])) {
    exit('NO_SESSION_ORDER');
}

$region = (string)($po['region'] ?? '');
if (!in_array($region, ['west', 'east', 'hold'], true)) exit('INVALID_REGION');
$receiptToken = (string)($po['receipt_token'] ?? '');

$couponCode = '';
if (!empty($_SESSION['coupon_code'][$order_number])) {
    $couponCode = (string)$_SESSION['coupon_code'][$order_number];
} elseif (!empty($_SESSION['coupon_code_pending'])) {
    $couponCode = (string)$_SESSION['coupon_code_pending'];
}

qii_ensure_order_security_columns($pdo);
qii_ensure_customer_tables($pdo);
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('SELECT id FROM orders WHERE order_number=? LIMIT 1');
    $stmt->execute([$order_number]);
    $existingOrderId = $stmt->fetchColumn();
    if ($existingOrderId) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE order_id=?');
        $stmt->execute([(int)$existingOrderId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->commit();
            echo 'OK';
            unset($_SESSION['pending_order'], $_SESSION['coupon_code'][$order_number], $_SESSION['coupon_code_pending']);
            exit;
        }
    }

    $freshItems = [];
    $subtotal = 0.0;

    foreach ($po['items'] as $item) {
        $qty = (int)($item['qty'] ?? 0);
        $variantId = (int)($item['variant_id'] ?? 0);
        $productId = (int)($item['product_id'] ?? 0);
        if ($qty <= 0) throw new RuntimeException('BAD_ITEM_QTY');

        if ($variantId > 0) {
            $stmt = $pdo->prepare("
                SELECT p.id AS product_id, p.name AS product_name, v.id AS variant_id,
                       v.variant_name, COALESCE(v.price, p.price) AS price, v.stock, v.sku
                FROM product_variants v
                INNER JOIN product_groups g ON g.id = v.group_id
                INNER JOIN products p ON p.id = g.product_id
                WHERE v.id = ? AND p.id = ? AND COALESCE(p.status, 'active') = 'active'
                LIMIT 1
            ");
            $stmt->execute([$variantId, $productId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id AS product_id, name AS product_name, 0 AS variant_id,
                       '' AS variant_name, price, stock, sku
                FROM products
                WHERE id = ? AND COALESCE(status, 'active') = 'active'
                LIMIT 1
            ");
            $stmt->execute([$productId]);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('BAD_ITEM_ID');
        if ((int)$row['stock'] < $qty) throw new RuntimeException('OUT_OF_STOCK');

        $price = (float)$row['price'];
        $subtotal += $price * $qty;
        $freshItems[] = [
            'product_id' => (int)$row['product_id'],
            'variant_id' => (int)$row['variant_id'],
            'product_name' => qii_text($row['product_name']),
            'variant_name' => qii_text($row['variant_name']),
            'sku' => (string)($row['sku'] ?? ''),
            'qty' => $qty,
            'price' => $price,
        ];
    }

    $shipping = qii_shipping_for_region($subtotal, $region);
    $newOrderStatus = $region === 'hold' ? 'stored_uncombined' : 'pending';
    $discount = 0.0;
    $couponId = null;
    if ($couponCode !== '') {
        $couponResult = qii_calculate_coupon($pdo, $couponCode, $subtotal);
        if ($couponResult['valid']) {
            $discount = (float)$couponResult['discount'];
            $couponId = (int)$couponResult['coupon']['id'];
        }
    }
    $grandTotal = max(0, $subtotal + $shipping - $discount);

    if ($existingOrderId) {
        $orderId = (int)$existingOrderId;
        $customerId = qii_customer_id();
        $stmt = $pdo->prepare("
            UPDATE orders SET
                customer_id=COALESCE(customer_id, ?),
                total=?, shipping=?, discount=?, coupon_code=?, grand_total=?, region=?,
                addr_name=?, addr_phone=?, addr_address=?, addr_postcode=?, addr_state=?, order_note=?,
                order_status=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$customerId, $subtotal, $shipping, $discount, $couponCode ?: null, $grandTotal, $region, $name, $phone, $address, $postcode, $state, $orderNote ?: null, $newOrderStatus, $orderId]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                customer_id, order_number, receipt_token, total, shipping, discount, coupon_code, grand_total, region,
                addr_name, addr_phone, addr_address, addr_postcode, addr_state, order_note,
                order_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([qii_customer_id(), $order_number, $receiptToken ?: null, $subtotal, $shipping, $discount, $couponCode ?: null, $grandTotal, $region, $name, $phone, $address, $postcode, $state, $orderNote ?: null, $newOrderStatus]);
        $orderId = (int)$pdo->lastInsertId();
    }

    foreach ($freshItems as $item) {
        $stmt = $pdo->prepare('
            INSERT INTO order_items (order_id, product_name, variant_name, quantity, price, sku)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$orderId, $item['product_name'], $item['variant_name'], $item['qty'], $item['price'], $item['sku']]);

        if ($item['variant_id'] > 0) {
            $stockStmt = $pdo->prepare('UPDATE product_variants SET stock = stock - ? WHERE id=? AND stock >= ?');
            $stockStmt->execute([$item['qty'], $item['variant_id'], $item['qty']]);
        } else {
            $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?');
            $stockStmt->execute([$item['qty'], $item['product_id'], $item['qty']]);
        }
        if ($stockStmt->rowCount() === 0) throw new RuntimeException('OUT_OF_STOCK');
    }

    if ($couponId !== null) {
        qii_increment_coupon_usage($pdo, $couponId);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Submit order failed: ' . $e->getMessage());
    exit($e->getMessage() === 'OUT_OF_STOCK' ? 'OUT_OF_STOCK' : 'SUBMIT_ORDER_FAILED');
}

echo 'OK';
unset($_SESSION['pending_order'], $_SESSION['coupon_code'][$order_number], $_SESSION['coupon_code_pending']);
?>
