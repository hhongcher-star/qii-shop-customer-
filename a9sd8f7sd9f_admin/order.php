<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/customers.php';
qii_ensure_customer_tables($pdo);
date_default_timezone_set('Asia/Kuala_Lumpur');

$allowedStatuses = ['pending', 'awaiting_payment', 'paid', 'shipped', 'completed', 'stored_uncombined', 'stored_combined', 'cancelled'];
$displayStatuses = ['pending', 'paid', 'shipped', 'completed', 'stored_uncombined', 'stored_combined', 'cancelled'];

function status_label(string $status): string {
    return [
        'pending' => '待付款',
        'awaiting_payment' => '待付款',
        'paid' => '已付款',
        'shipped' => '已发货',
        'completed' => '已完成',
        'stored_uncombined' => '存单未合单',
        'stored_combined' => '存单已合单',
        'cancelled' => '已取消',
        'draft' => '草稿',
    ][$status] ?? $status;
}

function status_class(string $status): string {
    return match ($status) {
        'paid' => 'ship',
        'shipped' => 'sent',
        'completed' => 'done',
        'stored_uncombined' => 'hold',
        'stored_combined' => 'combined',
        'cancelled' => 'cancel',
        default => 'pending',
    };
}

function redirect_order(array $extra = []): void {
    $query = array_merge($_GET, $extra);
    header('Location: order.php?' . http_build_query($query));
    exit;
}

function is_ajax_request(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_response(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function new_order_seen_orders_file(): string {
    return __DIR__ . '/../logs/order-seen-orders.json';
}

function new_order_seen_orders(): array {
    $file = new_order_seen_orders_file();
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? array_values(array_filter(array_map('strval', $data))) : [];
}

function mark_new_order_seen(string $orderNumber): void {
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') return;
    $dir = dirname(new_order_seen_orders_file());
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $seen = new_order_seen_orders();
    $seen[] = $orderNumber;
    file_put_contents(
        new_order_seen_orders_file(),
        json_encode(array_values(array_unique($seen)), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

function restore_order_stock(PDO $pdo, int $orderId): void {
    $stmt = $pdo->prepare("SELECT product_name, variant_name, quantity, sku FROM order_items WHERE order_id=?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $qty = max(0, (int)($item['quantity'] ?? 0));
        if ($qty <= 0) continue;

        $sku = trim((string)($item['sku'] ?? ''));
        $restored = false;

        if ($sku !== '') {
            $variantStmt = $pdo->prepare("UPDATE product_variants SET stock = stock + ? WHERE sku = ? LIMIT 1");
            $variantStmt->execute([$qty, $sku]);
            $restored = $variantStmt->rowCount() > 0;

            if (!$restored) {
                $productStmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE sku = ? LIMIT 1");
                $productStmt->execute([$qty, $sku]);
                $restored = $productStmt->rowCount() > 0;
            }
        }

        if (!$restored) {
            $nameStmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE name = ? LIMIT 1");
            $nameStmt->execute([$qty, $item['product_name'] ?? '']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_new_orders_seen'])) {
    verify_csrf();
    $orderNumber = trim($_POST['order_number'] ?? '');
    mark_new_order_seen($orderNumber);
    if (is_ajax_request()) {
        json_response(['ok' => true, 'order_number' => $orderNumber]);
    }
    redirect_order(['msg' => '新订单已确认']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $orderNumber = trim($_POST['order_number'] ?? '');
    $status = $_POST['status'] ?? '';
    if ($orderNumber && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status=?, updated_at=NOW() WHERE order_number=?");
        $stmt->execute([$status, $orderNumber]);
        if (is_ajax_request()) {
            json_response([
                'ok' => true,
                'order_number' => $orderNumber,
                'status' => $status,
                'label' => status_label($status),
                'class' => status_class($status),
            ]);
        }
    } elseif (is_ajax_request()) {
        json_response(['ok' => false, 'message' => 'Invalid order status']);
    }
    redirect_order(['msg' => '订单状态已更新']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    verify_csrf();
    $ids = array_map('intval', $_POST['order_ids'] ?? []);
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        if ($_POST['bulk_action'] === 'ship') {
            $pdo->prepare("UPDATE orders SET order_status='shipped', updated_at=NOW() WHERE id IN ($in)")->execute($ids);
        } elseif ($_POST['bulk_action'] === 'complete') {
            $pdo->prepare("UPDATE orders SET order_status='completed', updated_at=NOW() WHERE id IN ($in)")->execute($ids);
        } elseif ($_POST['bulk_action'] === 'delete') {
            foreach ($ids as $id) {
                restore_order_stock($pdo, (int)$id);
            }
            $pdo->prepare("DELETE FROM orders WHERE id IN ($in)")->execute($ids);
        }
    }
    redirect_order(['msg' => '批量操作已完成']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId > 0) {
        restore_order_stock($pdo, $orderId);
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id=?");
        $stmt->execute([$orderId]);
        if (is_ajax_request()) {
            json_response(['ok' => true, 'order_id' => $orderId]);
        }
    } elseif (is_ajax_request()) {
        json_response(['ok' => false, 'message' => 'Invalid order id']);
    }
    redirect_order(['msg' => '订单已删除']);
}

$search = trim($_GET['search'] ?? '');
$orderStatus = $_GET['order_status'] ?? '';
$payStatus = $_GET['pay_status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(10, min(50, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(o.order_number LIKE ? OR o.addr_name LIKE ? OR o.addr_phone LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
if ($orderStatus !== '' && in_array($orderStatus, $allowedStatuses, true)) {
    $where[] = "o.order_status=?";
    $params[] = $orderStatus;
}
if ($payStatus === 'hold') {
    $where[] = "o.order_status IN ('stored_uncombined','stored_combined')";
} elseif ($payStatus === 'paid') {
    $where[] = "o.order_status IN ('paid','shipped','completed')";
} elseif ($payStatus === 'unpaid') {
    $where[] = "o.order_status IN ('pending','awaiting_payment')";
}
if ($dateFrom !== '') {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereSql");
$stmt->execute($params);
$totalOrders = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalOrders / $limit));

$stmt = $pdo->prepare("
    SELECT o.*, c.name AS customer_name, c.email AS customer_email, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id=o.id
    LEFT JOIN customers c ON c.id=o.customer_id
    $whereSql
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'all' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending','awaiting_payment')")->fetchColumn(),
    'paid' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='paid'")->fetchColumn(),
    'shipped' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='shipped'")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='completed'")->fetchColumn(),
    'stored' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('stored_uncombined','stored_combined')")->fetchColumn(),
];

$seenOrderNumbers = new_order_seen_orders();
$seenSql = '';
if ($seenOrderNumbers) {
    $seenSql = ' AND o.order_number NOT IN (' . implode(',', array_fill(0, count($seenOrderNumbers), '?')) . ')';
}
$newOrderStmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id=o.id
    WHERE o.order_status <> 'draft' $seenSql
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 20
");
$newOrderStmt->execute($seenOrderNumbers);
$newOrders = $newOrderStmt->fetchAll(PDO::FETCH_ASSOC);
$countSql = $seenOrderNumbers
    ? 'SELECT COUNT(*) FROM orders o WHERE o.order_status <> \'draft\' AND o.order_number NOT IN (' . implode(',', array_fill(0, count($seenOrderNumbers), '?')) . ')'
    : 'SELECT COUNT(*) FROM orders o WHERE o.order_status <> \'draft\'';
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($seenOrderNumbers);
$newOrderCount = (int)$countStmt->fetchColumn();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>订单管理 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/order_admin.css?v=20260604">
  <style>
    .delete-order-form { display: inline-flex; margin: 0; }
    .delete-order-btn {
      border: 1px solid #ffc4d8;
      background: #fff5f8;
      color: #e83f7d;
      border-radius: 14px;
      padding: 9px 12px;
      font-weight: 900;
      cursor: pointer;
    }
    .delete-order-btn:hover { background: #ffe7ef; }
   .table-top-scroll {
  overflow-x: auto;
  overflow-y: hidden;
  height: 18px;
  margin: 0 0 8px 0;
}

.table-top-scroll div {
  height: 1px;
}

.order-table-card {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;

  scrollbar-width: none; /* Firefox */
}

.order-table-card::-webkit-scrollbar {
  display: none; /* Chrome */
}

.order-table-card table {
  min-width: 1500px;
  width: max-content;
}
    .date-range-form { margin: 0; }
    .date-range-form .date-pill { gap: 8px; padding: 7px 10px; }
    .date-field { display: grid; gap: 2px; padding: 5px 10px; border: 1px solid #f5ccdd; border-radius: 11px; background: #fff; }
    .date-field span { color: #a08895; font-size: 10px; font-weight: 800; }
    .date-range-form input[type="date"] {
      width: 138px;
      min-height: 24px;
      border: 0;
      outline: 0;
      background: transparent;
      color: inherit;
      font: inherit;
      cursor: pointer;
    }
    .date-range-submit {
      min-width: 64px;
      height: 42px;
      padding: 0 14px;
      border: 0;
      border-radius: 11px;
      background: #ff4f9a;
      color: #fff;
      font-weight: 900;
      cursor: pointer;
    }
    .new-order-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 42px;
      padding: 0 15px;
      border: 0;
      border-radius: 12px;
      background: linear-gradient(135deg, #ff6aad, #f43f8f);
      color: #fff;
      font-weight: 900;
      cursor: pointer;
      box-shadow: 0 12px 24px rgba(244,63,143,.2);
    }
    .new-order-btn b {
      min-width: 24px;
      height: 24px;
      display: inline-grid;
      place-items: center;
      border-radius: 999px;
      background: #fff;
      color: #f43f8f;
      padding: 0 7px;
    }
    .new-order-dialog {
      width: min(680px, calc(100% - 32px));
      border: 0;
      border-radius: 22px;
      padding: 24px;
      box-shadow: 0 24px 70px rgba(85,35,62,.25);
    }
    .new-order-dialog::backdrop { background: rgba(45,25,38,.35); backdrop-filter: blur(4px); }
    .new-order-head { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; }
    .new-order-head h2 { margin:0; color:#2b223d; }
    .new-order-close { width:42px; height:42px; border:0; border-radius:50%; background:#fff0f7; color:#7d7081; font-size:24px; cursor:pointer; }
    .new-order-list { display:grid; gap:10px; max-height:430px; overflow:auto; padding-right:4px; }
    .new-order-item { display:grid; grid-template-columns:1fr auto; gap:12px; padding:14px; border:1px solid #f5ccdd; border-radius:14px; background:#fff8fb; }
    .new-order-item strong, .new-order-item small { display:block; }
    .new-order-item strong { color:#2b223d; }
    .new-order-item small { margin-top:4px; color:#7d7081; font-weight:700; }
    .new-order-item-actions { display:flex; align-items:center; gap:8px; }
    .new-order-item a { color:#f43f8f; font-weight:900; text-decoration:none; }
    .new-order-item form { margin:0; }
    .new-order-known { min-width:84px; min-height:38px; border:0; border-radius:12px; background:#f43f8f; color:#fff; font-weight:900; cursor:pointer; }
    .new-order-known:disabled { opacity:.6; cursor:wait; }
    .new-order-empty { padding:34px 16px; text-align:center; color:#7d7081; font-weight:900; }
    @media (max-width: 760px) {
      .date-range-form .date-pill { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
      .date-range-form .date-pill > .fa-calendar, .date-separator { display: none; }
      .date-field, .date-range-form input[type="date"] { width: 100%; box-sizing: border-box; }
      .date-range-submit { grid-column: 1 / -1; width: 100%; }
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main order-page">
  <header class="order-topbar">
    <div class="title-wrap">
      <h1><i class="fa-solid fa-bag-shopping"></i> 订单管理</h1>
      <p>管理所有订单，查看订单状态、处理发货和退款。</p>
    </div>
    
    <form method="get" class="date-range-form">
      <?php foreach ($_GET as $key => $value): if (in_array($key, ['date_from', 'date_to', 'page'], true) || is_array($value)) continue; ?>
        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
      <?php endforeach; ?>
      <span class="date-pill">
        <i class="fa-regular fa-calendar"></i>
        <button type="button" class="new-order-btn" onclick="document.getElementById('newOrderDialog')?.showModal()">
          <i class="fa-solid fa-bell"></i>
          新增订单
          <b id="newOrderCountBadge"><?= $newOrderCount ?></b>
        </button>
        <label class="date-field"><span>开始日期</span><input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom ?: date('Y-m-01')) ?>"></label>
        <span class="date-separator">至</span>
        <label class="date-field"><span>结束日期</span><input type="date" name="date_to" value="<?= htmlspecialchars($dateTo ?: date('Y-m-d')) ?>"></label>
        <button type="submit" class="date-range-submit">查询</button>
      </span>
    </form>
  </header>

  <section class="order-stats">
    <article class="stat-card"><span><i class="fa-solid fa-bag-shopping"></i></span><div><p>全部订单</p><strong><?= $stats['all'] ?></strong><small>较昨日 ↑ 12%</small></div></article>
    <article class="stat-card orange"><span><i class="fa-solid fa-clock"></i></span><div><p>待付款</p><strong><?= $stats['pending'] ?></strong><small>较昨日 ↑ 8%</small></div></article>
    <article class="stat-card purple"><span><i class="fa-solid fa-truck"></i></span><div><p>待发货</p><strong><?= $stats['paid'] ?></strong><small>较昨日 ↑ 15%</small></div></article>
    <article class="stat-card blue"><span><i class="fa-solid fa-box"></i></span><div><p>已发货</p><strong><?= $stats['shipped'] ?></strong><small>较昨日 ↑ 10%</small></div></article>
    <article class="stat-card green"><span><i class="fa-solid fa-circle-check"></i></span><div><p>已完成</p><strong><?= $stats['completed'] ?></strong><small>较昨日 ↑ 18%</small></div></article>
  </section>

  <?php if ($msg): ?><div class="order-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <section class="order-tools glass-panel">
    <form method="get" class="order-filter">
      <label class="search-field"><i class="fa-solid fa-magnifying-glass"></i><input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="输入订单号、收件人、手机号搜索"></label>
      <button type="button" class="filter-btn" id="toggleFilters"><i class="fa-solid fa-filter"></i> 筛选</button>

      <div id="filterPanel" class="mobile-filter-panel">
        <select name="order_status">
          <option value="">订单状态</option>
          <?php foreach ($displayStatuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $orderStatus===$status?'selected':'' ?>><?= status_label($status) ?></option><?php endforeach; ?>
        </select>
        <select name="pay_status">
          <option value="">邮费 / 支付状态</option>
          <option value="paid" <?= $payStatus==='paid'?'selected':'' ?>>已支付</option>
          <option value="unpaid" <?= $payStatus==='unpaid'?'selected':'' ?>>待付款</option>
          <option value="hold" <?= $payStatus==='hold'?'selected':'' ?>>存单</option>
        </select>
        <a href="order.php" class="reset-btn"><i class="fa-solid fa-rotate-right"></i> 重置</a>
      </div>
      <button type="submit" class="visually-hidden" aria-hidden="true"></button>
    </form>

    
  </section>
  <div class="table-top-scroll" id="tableTopScroll">
    <div id="tableTopScrollInner"></div>
  </div>

  <section class="order-table-card" id="orderTableScroll">
    <div class="mobile-list-head"><strong>共 <?= $totalOrders ?> 条记录</strong><span>每页显示 <?= $limit ?> 条</span></div>
    <table>
      <thead>
        <tr>
          <th><input type="checkbox" onclick="document.querySelectorAll('input[name*=order_ids]').forEach(cb => cb.checked = this.checked);"></th>
          <th>订单号</th>
          <th>下单时间</th>
          <th>收件人</th>
          <th>金额 (RM)</th>
          <th>支付状态</th>
          <th>订单状态</th>
          <th>地址 / 备注</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$orders): ?><tr><td colspan="9" class="empty">暂无订单记录。</td></tr><?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <?php
            $isHoldOrder = in_array($o['order_status'], ['stored_uncombined', 'stored_combined'], true);
            $paid = in_array($o['order_status'], ['paid', 'shipped', 'completed'], true);
            $amount = (float)($o['grand_total'] ?: $o['total']);
            $fullAddress = trim(implode(' ', array_filter([
                $o['addr_address'] ?? '',
                $o['addr_postcode'] ?? '',
                $o['addr_state'] ?? '',
            ])));
          ?>
          <tr class="order-row" data-order-row>
            <td class="check-cell"><input form="bulkForm" type="checkbox" name="order_ids[]" value="<?= (int)$o['id'] ?>"></td>
            <td class="order-info"><strong><?= htmlspecialchars($o['order_number']) ?></strong><small>共 <?= (int)$o['item_count'] ?> 件商品</small></td>
            <td><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
            <td class="receiver-cell">
              <strong><?= htmlspecialchars($o['addr_name'] ?? '-') ?></strong>
              <small><?= htmlspecialchars($o['addr_phone'] ?? '') ?></small>
              <small style="color:<?= $o['customer_id'] ? '#d94b8a' : '#9a8d95' ?>;font-weight:800;">
                <?= $o['customer_id'] ? '会员：' . htmlspecialchars($o['customer_name'] ?: ('用户 #' . $o['customer_id'])) : '游客订单（无登录账号）' ?>
              </small>
            </td>
            <td><strong>RM <?= number_format($amount, 2) ?></strong></td>
            <td><span class="state-pill <?= $isHoldOrder ? 'hold' : ($paid ? 'paid' : 'unpaid') ?>"><?= $isHoldOrder ? '存单' : ($paid ? '已支付' : '待付款') ?></span></td>
            <td><span class="state-pill <?= status_class($o['order_status']) ?>" data-order-status-pill><?= status_label($o['order_status']) ?></span></td>
            <td class="delivery-cell">
              <strong><?= htmlspecialchars($fullAddress ?: '未填写地址') ?></strong>
              <small>备注：<?= htmlspecialchars(trim((string)($o['order_note'] ?? '')) ?: '无') ?></small>
            </td>
            <td>
              <div class="order-actions">
                <a href="../receipt.php?order_number=<?= urlencode($o['order_number']) ?><?= !empty($o['receipt_token']) ? '&token=' . urlencode($o['receipt_token']) : '' ?>" target="_blank">查看</a>
                <form method="post" class="status-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="order_number" value="<?= htmlspecialchars($o['order_number']) ?>">
                  <select name="status">
                    <?php foreach ($displayStatuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= ($o['order_status']===$status || ($status === 'pending' && $o['order_status'] === 'awaiting_payment'))?'selected':'' ?>><?= status_label($status) ?></option><?php endforeach; ?>
                  </select>
                  <button type="submit" name="update_status">
  <i class="fa-solid fa-check"></i> 保存
</button>
                </form>
                <form method="post" class="delete-order-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <button type="submit" name="delete_order" class="delete-order-btn">
                    <i class="fa-solid fa-trash"></i> 删除
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <dialog id="newOrderDialog" class="new-order-dialog">
    <div class="new-order-head">
      <h2><i class="fa-solid fa-bell"></i> 新增订单 <span id="newOrderTitleCount"><?= $newOrderCount > 0 ? '(' . $newOrderCount . ')' : '' ?></span></h2>
      <button type="button" class="new-order-close" onclick="this.closest('dialog').close()" aria-label="关闭">&times;</button>
    </div>
    <div class="new-order-list">
      <?php if (!$newOrders): ?>
        <div class="new-order-empty" id="newOrderEmpty">暂时没有新的订单。</div>
      <?php endif; ?>
      <?php foreach ($newOrders as $newOrder): ?>
        <?php
          $newAddress = trim(implode(' ', array_filter([
              $newOrder['addr_address'] ?? '',
              $newOrder['addr_postcode'] ?? '',
              $newOrder['addr_state'] ?? '',
          ])));
        ?>
        <article class="new-order-item" data-new-order-card>
          <div>
            <strong><?= htmlspecialchars($newOrder['order_number']) ?> · RM <?= number_format((float)($newOrder['grand_total'] ?? $newOrder['total'] ?? 0), 2) ?></strong>
            <small><?= date('Y-m-d H:i', strtotime($newOrder['created_at'])) ?> · <?= htmlspecialchars($newOrder['addr_name'] ?? '-') ?> · <?= htmlspecialchars($newOrder['addr_phone'] ?? '') ?></small>
            <small><?= (int)$newOrder['item_count'] ?> 件商品 · <?= htmlspecialchars(status_label((string)$newOrder['order_status'])) ?></small>
            <small><?= htmlspecialchars($newAddress ?: '未填写地址') ?></small>
          </div>
          <div class="new-order-item-actions">
            <a href="../receipt.php?order_number=<?= urlencode($newOrder['order_number']) ?><?= !empty($newOrder['receipt_token']) ? '&token=' . urlencode($newOrder['receipt_token']) : '' ?>" target="_blank">查看</a>
            <form method="post" data-new-order-seen-form>
              <?= csrf_field() ?>
              <input type="hidden" name="order_number" value="<?= htmlspecialchars($newOrder['order_number']) ?>">
              <button type="submit" name="mark_new_orders_seen" class="new-order-known">知道了</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </dialog>

  <footer class="order-pagination">
    <span>共 <?= $totalOrders ?> 条记录</span>
    <nav>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="<?= $i===$page?'active':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <form method="get" class="limit-form">
      <?php foreach ($_GET as $key => $value): if ($key === 'limit') continue; ?>
        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
      <?php endforeach; ?>
      <span>每页显示</span>
      <select name="limit" onchange="this.form.submit()">
        <option value="10" <?= $limit===10?'selected':'' ?>>10 条</option>
        <option value="20" <?= $limit===20?'selected':'' ?>>20 条</option>
        <option value="50" <?= $limit===50?'selected':'' ?>>50 条</option>
      </select>
    </form>
  </footer>
</main>

<script>
  const toggleBtn = document.getElementById('toggleFilters');
  const filterPanel = document.getElementById('filterPanel');
  const bulkPanel = document.getElementById('bulkForm');

  toggleBtn?.addEventListener('click', (event) => {
    if (window.innerWidth <= 760) {
      filterPanel?.classList.toggle('show');
      bulkPanel?.classList.toggle('show');
      return;
    }

    event.preventDefault();
    toggleBtn.closest('form')?.submit();
  });
  const topScroll = document.getElementById('tableTopScroll');
const topInner = document.getElementById('tableTopScrollInner');
const tableScroll = document.getElementById('orderTableScroll');
const table = tableScroll?.querySelector('table');

if (topScroll && topInner && tableScroll && table) {
  topInner.style.width = table.offsetWidth + 'px';

  topScroll.addEventListener('scroll', () => {
    tableScroll.scrollLeft = topScroll.scrollLeft;
  });

  tableScroll.addEventListener('scroll', () => {
    topScroll.scrollLeft = tableScroll.scrollLeft;
  });
}

document.querySelectorAll('[data-new-order-seen-form]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const card = form.closest('[data-new-order-card]');
    const formData = new FormData(form);
    formData.append('mark_new_orders_seen', '1');
    button.disabled = true;

    try {
      const response = await fetch('order.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error('mark failed');

      card?.remove();
      const badge = document.getElementById('newOrderCountBadge');
      const titleCount = document.getElementById('newOrderTitleCount');
      const loadedRemaining = document.querySelectorAll('[data-new-order-card]').length;
      const nextCount = Math.max(0, (parseInt(badge?.textContent || '0', 10) || 0) - 1);
      if (badge) badge.textContent = String(nextCount);
      if (titleCount) titleCount.textContent = nextCount > 0 ? `(${nextCount})` : '';

      const list = document.querySelector('.new-order-list');
      if (list && loadedRemaining === 0 && !document.getElementById('newOrderEmpty')) {
        const empty = document.createElement('div');
        empty.className = 'new-order-empty';
        empty.id = 'newOrderEmpty';
        empty.textContent = '暂时没有新的订单。';
        list.appendChild(empty);
      }
    } catch (error) {
      button.disabled = false;
      alert('确认失败，请再按一次。');
    }
  });
});

document.querySelectorAll('.status-form').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const row = form.closest('[data-order-row]');
    const pill = row?.querySelector('[data-order-status-pill]');
    const formData = new FormData(form);
    formData.append('update_status', '1');
    button.disabled = true;

    try {
      const response = await fetch('order.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error('save failed');
      if (pill) {
        pill.className = `state-pill ${data.class || 'pending'}`;
        pill.textContent = data.label || formData.get('status');
      }
    } catch (error) {
      alert('保存失败，请再按一次。');
    } finally {
      button.disabled = false;
    }
  });
});

document.querySelectorAll('.delete-order-form').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!confirm('确定要删除这个订单吗？库存会自动加回去。')) return;
    const button = form.querySelector('button[type="submit"]');
    const row = form.closest('[data-order-row]');
    const formData = new FormData(form);
    formData.append('delete_order', '1');
    button.disabled = true;

    try {
      const response = await fetch('order.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error('delete failed');
      row?.remove();
    } catch (error) {
      button.disabled = false;
      alert('删除失败，请再按一次。');
    }
  });
});
</script>
<script src="js/product_admin.js?v=20260604"></script>
</body>
</html>
