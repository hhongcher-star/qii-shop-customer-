<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$allowedStatuses = ['pending', 'awaiting_payment', 'paid', 'shipped', 'completed', 'cancelled'];

function status_label(string $status): string {
    return [
        'pending' => '待付款',
        'awaiting_payment' => '待付款',
        'paid' => '待发货',
        'shipped' => '已发货',
        'completed' => '已完成',
        'cancelled' => '已取消',
        'draft' => '草稿',
    ][$status] ?? $status;
}

function status_class(string $status): string {
    return match ($status) {
        'paid' => 'ship',
        'shipped' => 'sent',
        'completed' => 'done',
        'cancelled' => 'cancel',
        default => 'pending',
    };
}

function redirect_order(array $extra = []): void {
    $query = array_merge($_GET, $extra);
    header('Location: order.php?' . http_build_query($query));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $orderNumber = trim($_POST['order_number'] ?? '');
    $status = $_POST['status'] ?? '';
    if ($orderNumber && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status=?, updated_at=NOW() WHERE order_number=?");
        $stmt->execute([$status, $orderNumber]);
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
            $pdo->prepare("DELETE FROM orders WHERE id IN ($in)")->execute($ids);
        }
    }
    redirect_order(['msg' => '批量操作已完成']);
}

$search = trim($_GET['search'] ?? '');
$orderStatus = $_GET['order_status'] ?? '';
$payStatus = $_GET['pay_status'] ?? '';
$delivery = $_GET['delivery'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
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
if ($payStatus === 'paid') {
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
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id=o.id
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
];

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
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main order-page">
  <header class="order-topbar">
    <div class="title-wrap">
      <h1><i class="fa-solid fa-bag-shopping"></i> 订单管理</h1>
      <p>管理所有订单，查看订单状态、处理发货和退款。</p>
    </div>
    
    <span class="date-pill"><i class="fa-regular fa-calendar"></i><?= htmlspecialchars($dateFrom ?: '2026-06-01') ?> ~ <?= htmlspecialchars($dateTo ?: date('Y-m-d')) ?><i class="fa-solid fa-chevron-down"></i></span>
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
          <?php foreach ($allowedStatuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $orderStatus===$status?'selected':'' ?>><?= status_label($status) ?></option><?php endforeach; ?>
        </select>
        <select name="pay_status">
          <option value="">支付状态</option>
          <option value="paid" <?= $payStatus==='paid'?'selected':'' ?>>已支付</option>
          <option value="unpaid" <?= $payStatus==='unpaid'?'selected':'' ?>>待付款</option>
        </select>
        <select name="delivery">
          <option value="">配送方式</option>
          <option value="jt" <?= $delivery==='jt'?'selected':'' ?>>J&T Express</option>
          <option value="poslaju" <?= $delivery==='poslaju'?'selected':'' ?>>Poslaju</option>
          <option value="shopee" <?= $delivery==='shopee'?'selected':'' ?>>Shopee Express</option>
        </select>
        <a href="order.php" class="reset-btn"><i class="fa-solid fa-rotate-right"></i> 重置</a>
      </div>
      <button type="submit" class="visually-hidden" aria-hidden="true"></button>
    </form>

    
  </section>

  <section class="order-table-card">
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
          <th>配送方式</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$orders): ?><tr><td colspan="9" class="empty">暂无订单记录。</td></tr><?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <?php
            $paid = in_array($o['order_status'], ['paid', 'shipped', 'completed'], true);
            $deliveryName = ($o['addr_state'] ?? '') === 'Johor' ? 'J&T Express' : 'Shopee Express';
            $amount = (float)($o['grand_total'] ?: $o['total']);
          ?>
          <tr class="order-row">
            <td class="check-cell"><input form="bulkForm" type="checkbox" name="order_ids[]" value="<?= (int)$o['id'] ?>"></td>
            <td class="order-info"><strong><?= htmlspecialchars($o['order_number']) ?></strong><small>共 <?= (int)$o['item_count'] ?> 件商品</small></td>
            <td><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
            <td class="receiver-cell"><strong><?= htmlspecialchars($o['addr_name'] ?? '-') ?></strong><small><?= htmlspecialchars($o['addr_phone'] ?? '') ?></small></td>
            <td><strong>RM <?= number_format($amount, 2) ?></strong></td>
            <td><span class="state-pill <?= $paid ? 'paid' : 'unpaid' ?>"><?= $paid ? '已支付' : '待付款' ?></span></td>
            <td><span class="state-pill <?= status_class($o['order_status']) ?>"><?= status_label($o['order_status']) ?></span></td>
            <td class="delivery-cell"><strong><?= $deliveryName ?></strong><small><?= htmlspecialchars(($o['addr_postcode'] ?? '') . ' ' . ($o['addr_state'] ?? '')) ?></small></td>
            <td>
              <div class="order-actions">
                <a href="../receipt.php?order_number=<?= urlencode($o['order_number']) ?><?= !empty($o['receipt_token']) ? '&token=' . urlencode($o['receipt_token']) : '' ?>" target="_blank">查看</a>
                <form method="post" class="status-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="order_number" value="<?= htmlspecialchars($o['order_number']) ?>">
                  <select name="status">
                    <?php foreach ($allowedStatuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $o['order_status']===$status?'selected':'' ?>><?= status_label($status) ?></option><?php endforeach; ?>
                  </select>
                  <button type="submit" name="update_status">
  <i class="fa-solid fa-check"></i> 保存
</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

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
</script>
<script src="js/product_admin.js?v=20260604"></script>
</body>
</html>
