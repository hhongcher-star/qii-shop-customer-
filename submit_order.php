<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);
session_start();
require __DIR__ . "/a9sd8f7sd9f_admin/config.php";

header("Content-Type: text/plain; charset=UTF-8");
date_default_timezone_set("Asia/Kuala_Lumpur");

// ======================================
// 1️⃣ 获取 POST 参数
// ======================================
$order_number = trim($_POST["order_number"] ?? "");
$name         = trim($_POST["name"] ?? "");
$phone        = trim($_POST["phone"] ?? "");
$address      = trim($_POST["address"] ?? "");
$postcode     = trim($_POST["postcode"] ?? "");
$state        = trim($_POST["state"] ?? "");

if (!$order_number) exit("NO_ORDER");
if (!$name || !$phone || !$address || !$postcode || !$state)
    exit("NO_ADDRESS");

// ======================================
// 2️⃣ 获取 session 订单
// ======================================
$po = $_SESSION["pending_order"] ?? null;

if (!$po) exit("NO_SESSION_ORDER");

// ======================================
// 3️⃣ 更新订单
// ======================================
$discount = $_SESSION['coupon_discount'][$order_number] ?? 0;
$total = $po['total'];
$shipping = $po['shipping'];

$stmt = $pdo->prepare("
    UPDATE orders SET 
        addr_name=?, addr_phone=?, addr_address=?, addr_postcode=?, addr_state=?,
        shipping=?, discount=?, grand_total=?,
        order_status='pending'
    WHERE order_number=?
");

$stmt->execute([
    $name, $phone, $address, $postcode, $state,
    $shipping, $discount, ($total + $shipping - $discount),
    $order_number
]);

// HACK: 我们需要 order_id 来插入 order_items，但 UPDATE 不返回
//       所以我们必须再去 SELECT 一次
$stmt_get_id = $pdo->prepare("SELECT id FROM orders WHERE order_number=?");
$stmt_get_id->execute([$order_number]);
$order_id = $stmt_get_id->fetchColumn();

if (!$order_id) {
    exit("ORDER_ID_NOT_FOUND_AFTER_UPDATE");
}

// ======================================
// 4️⃣ 永远插入订单明细
// ======================================
foreach ($po["items"] as $item) {
    $stmt2 = $pdo->prepare("
        INSERT INTO order_items (order_id, product_name, quantity, price, sku)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt2->execute([
        $order_id,
        $item["name"],
        $item["qty"],
        $item["price"],
        $item["sku"] ?? ""
    ]);
}

// ======================================
// 5️⃣ 永远扣库存
// ======================================
foreach ($po["items"] as $item) {
    if (!empty($item["sku"])) {
        $pdo->prepare("
            UPDATE products SET stock = stock - ?
            WHERE sku=? AND stock >= ?
        ")->execute([
            $item["qty"],
            $item["sku"],
            $item["qty"],
        ]);
    }
}

echo "OK";

// ======================================
// 6️⃣ 清 session
// ======================================
unset($_SESSION["pending_order"]);
unset($_SESSION["coupon_discount"][$order_number]);
?>

