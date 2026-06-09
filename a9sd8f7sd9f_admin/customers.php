<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/customers.php';
qii_ensure_customer_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer_notes'])) {
    verify_csrf();
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $notes = trim((string)($_POST['admin_notes'] ?? ''));
    $tags = trim((string)($_POST['admin_tags'] ?? ''));
    if ($customerId > 0) {
        $stmt = $pdo->prepare('UPDATE customers SET admin_notes=?, admin_tags=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$notes ?: null, $tags ?: null, $customerId]);
    }
    header('Location: customers.php?customer_id=' . $customerId . '&saved=1');
    exit;
}

$search = trim((string)($_GET['search'] ?? ''));
$tagFilter = trim((string)($_GET['tag'] ?? ''));
$params = [];
$where = '';
if ($search !== '') {
    $where = 'WHERE (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
    $params = ["%$search%", "%$search%", "%$search%"];
}
if ($tagFilter !== '') {
    $where .= ($where ? ' AND ' : 'WHERE ') . 'c.admin_tags LIKE ?';
    $params[] = "%$tagFilter%";
}

$stmt = $pdo->prepare("
    SELECT c.*, COUNT(o.id) AS order_count,
           COALESCE(SUM(o.grand_total),0) AS total_spent,
           SUM(CASE WHEN o.order_status IN ('stored_uncombined','stored_combined') THEN 1 ELSE 0 END) AS hold_count,
           MAX(o.created_at) AS last_order_at
    FROM customers c
    LEFT JOIN orders o ON o.customer_id=c.id
    $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$selectedId = (int)($_GET['customer_id'] ?? ($customers[0]['id'] ?? 0));
$selected = null;
foreach ($customers as $row) {
    if ((int)$row['id'] === $selectedId) $selected = $row;
}

$orders = [];
$holdOrders = [];
if ($selected) {
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) AS item_count
        FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id
        WHERE o.customer_id=? GROUP BY o.id ORDER BY o.created_at DESC
    ");
    $stmt->execute([$selectedId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $holdOrders = array_values(array_filter($orders, fn($o) => in_array($o['order_status'], ['stored_uncombined','stored_combined'], true)));
}

function customer_status(string $status): string {
    return [
        'pending'=>'待付款','awaiting_payment'=>'待付款','paid'=>'待发货','shipped'=>'已发货',
        'completed'=>'已完成','stored_uncombined'=>'存单未合单','stored_combined'=>'存单已合单',
        'cancelled'=>'已取消','draft'=>'草稿',
    ][$status] ?? $status;
}

function render_customer_orders(array $orders): void {
    if (!$orders) {
        echo '<div class="empty">暂无记录</div>';
        return;
    }
    echo '<div class="table-wrap"><table><thead><tr><th>订单号</th><th>下单时间</th><th>商品</th><th>金额</th><th>状态</th><th>操作</th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $receipt = '../receipt.php?order_number=' . urlencode($o['order_number']);
        if (!empty($o['receipt_token'])) $receipt .= '&token=' . urlencode($o['receipt_token']);
        echo '<tr><td><strong>' . htmlspecialchars($o['order_number']) . '</strong></td>';
        echo '<td>' . date('Y-m-d H:i', strtotime($o['created_at'])) . '</td>';
        echo '<td>' . (int)$o['item_count'] . ' 件</td>';
        echo '<td>RM ' . number_format((float)($o['grand_total'] ?: $o['total']), 2) . '</td>';
        echo '<td><span class="pill">' . htmlspecialchars(customer_status((string)$o['order_status'])) . '</span></td>';
        echo '<td><a class="view" target="_blank" href="' . htmlspecialchars($receipt) . '">查看</a></td></tr>';
    }
    echo '</tbody></table></div>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>用户管理 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *{box-sizing:border-box}body{margin:0;background:#fff7fb;color:#413642;font-family:Arial,sans-serif}.main{margin-left:280px;padding:24px}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:16px}h1{margin:0 0 6px;color:#ed4d94}.top p{margin:0;color:#8d7b86;font-size:13px}.search{display:flex;background:#fff;border:1px solid #f5d8e5;border-radius:7px;padding:5px}.search input{width:230px;border:0;outline:0;padding:0 10px}.search button,.save-btn{border:0;border-radius:5px;background:#ed4d94;color:#fff;padding:10px 16px;font-weight:800}.layout{display:grid;grid-template-columns:300px minmax(0,1fr);gap:16px}.panel{background:#fff;border:1px solid #f4dbe6;border-radius:8px;overflow:hidden;box-shadow:0 8px 22px rgba(201,75,130,.06)}.list-title{padding:15px;border-bottom:1px solid #f5e3eb;font-weight:900}.customer{display:grid;grid-template-columns:42px 1fr;gap:10px;padding:13px;border-bottom:1px solid #f6e6ed;text-decoration:none;color:#413642}.customer:hover,.customer.active{background:#fff0f6}.avatar,.profile-avatar{display:grid;place-items:center;border-radius:50%;background:#ffe8f2;color:#ed4d94}.avatar{width:42px;height:42px}.customer small{display:block;color:#8d7b86;font-size:11px;margin-top:3px}.customer-meta{display:flex;gap:7px;margin-top:5px;font-size:10px;color:#9e8995}.profile{position:relative;display:flex;align-items:center;gap:18px;min-height:145px;padding:22px;background:linear-gradient(110deg,#fff,#fff0f7);overflow:hidden}.profile:after{content:"";position:absolute;right:20px;bottom:-20px;width:150px;height:120px;background:url("../images/27.png") center/contain no-repeat;opacity:.65}.profile-avatar{width:82px;height:82px;flex:0 0 82px;font-size:30px;border:4px solid #fff}.profile-copy{position:relative;z-index:1}.profile-copy h2{margin:0 0 8px}.profile-copy p{margin:5px 0;color:#796973;font-size:13px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:14px;border-top:1px solid #f5e3eb}.stat{padding:14px;background:#fff7fb;border-radius:7px}.stat span,.stat strong{display:block}.stat span{font-size:11px;color:#97838f;margin-bottom:6px}.stat strong{color:#ed4d94;font-size:18px}.tabs{display:flex;gap:28px;padding:0 18px;border-top:1px solid #f5e3eb;border-bottom:1px solid #f5e3eb}.tab{border:0;background:none;padding:15px 4px 12px;color:#8d7b86;cursor:pointer}.tab.active{color:#ed4d94;border-bottom:2px solid #ed4d94;font-weight:900}.tab-panel{display:none}.tab-panel.active{display:block}.panel-title{padding:15px 18px;font-weight:900}.empty{padding:34px;text-align:center;color:#8d7b86}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:12px 15px;text-align:left;border-top:1px solid #f6e6ed;font-size:12px}th{background:#fff7fb;color:#8d7b86}.pill{display:inline-flex;padding:5px 9px;border-radius:999px;background:#fff0f6;color:#ed4d94;font-weight:900}.view{color:#ed4d94;text-decoration:none;font-weight:900}.notes-form{padding:18px}.notes-form label{display:block;margin-bottom:7px;font-weight:900;font-size:13px}.notes-form input,.notes-form textarea{width:100%;border:1px solid #f0ccd9;border-radius:7px;padding:11px;margin-bottom:16px;outline:none}.notes-form textarea{min-height:150px;resize:vertical}.saved{margin-bottom:14px;padding:10px;background:#eafaf1;color:#24844c;border-radius:6px}
    @media(max-width:900px){.main{margin-left:0;padding:14px 10px 90px}.top,.layout{display:block}.search{margin-top:12px}.search input{width:100%}.panel{margin-bottom:12px}.profile:after{opacity:.15}.stats{grid-template-columns:1fr 1fr}.tabs{overflow-x:auto;white-space:nowrap}.customer-meta{flex-wrap:wrap}}
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>
<main class="main">
  <header class="top"><div><h1><i class="fa-solid fa-users"></i> 用户管理</h1><p>查看用户订单、存单记录以及内部备注。</p></div><form class="search"><input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索姓名、邮箱、电话"><button>搜索</button></form></header>
  <form class="search" method="get" style="margin:0 0 14px auto;max-width:420px"><input name="tag" value="<?= htmlspecialchars($tagFilter) ?>" placeholder="输入标签筛选用户"><button>筛选标签</button></form>
  <section class="layout">
    <aside class="panel"><div class="list-title">客户列表（<?= count($customers) ?>）</div><?php if(!$customers):?><div class="empty">暂无用户</div><?php endif;?><?php foreach($customers as $c):?><a class="customer <?= (int)$c['id']===$selectedId?'active':'' ?>" href="?customer_id=<?= (int)$c['id'] ?>"><span class="avatar"><i class="fa-solid fa-user"></i></span><span><strong><?= htmlspecialchars($c['name']) ?></strong><small><?= htmlspecialchars($c['phone'] ?: $c['email']) ?></small><span class="customer-meta"><b><?= (int)$c['order_count'] ?> 订单</b><b><?= (int)$c['hold_count'] ?> 存单</b><b>RM <?= number_format((float)$c['total_spent'],2) ?></b></span></span></a><?php endforeach;?></aside>
    <section class="panel">
      <?php if(!$selected):?><div class="empty">请选择一个用户</div><?php else:?>
      <div class="profile"><span class="profile-avatar"><i class="fa-solid fa-user"></i></span><div class="profile-copy"><h2><?= htmlspecialchars($selected['name']) ?></h2><p><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($selected['email']) ?></p><p><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($selected['phone'] ?: '未填写电话') ?></p><p>加入时间：<?= date('Y-m-d',strtotime($selected['created_at'])) ?></p></div></div>
      <div class="stats"><div class="stat"><span>总订单</span><strong><?= (int)$selected['order_count'] ?></strong></div><div class="stat"><span>存单</span><strong><?= (int)$selected['hold_count'] ?></strong></div><div class="stat"><span>总消费</span><strong>RM <?= number_format((float)$selected['total_spent'],2) ?></strong></div><div class="stat"><span>最后下单</span><strong style="font-size:13px"><?= $selected['last_order_at']?date('Y-m-d',strtotime($selected['last_order_at'])):'-' ?></strong></div></div>
      <div class="tabs"><button class="tab active" data-tab="history">历史订单</button><button class="tab" data-tab="holds">存单记录</button><button class="tab" data-tab="notes">备注 / 标签</button></div>
      <div class="tab-panel active" data-panel="history"><div class="panel-title">历史订单（<?= count($orders) ?>）</div><?php render_customer_orders($orders); ?></div>
      <div class="tab-panel" data-panel="holds"><div class="panel-title">存单记录（<?= count($holdOrders) ?>）</div><?php render_customer_orders($holdOrders); ?></div>
      <div class="tab-panel" data-panel="notes"><form class="notes-form" method="post"><?= csrf_field() ?><input type="hidden" name="customer_id" value="<?= (int)$selectedId ?>"><?php if(isset($_GET['saved'])):?><div class="saved">备注和标签已保存</div><?php endif;?><label for="admin_tags">标签</label><input id="admin_tags" name="admin_tags" value="<?= htmlspecialchars((string)($selected['admin_tags'] ?? '')) ?>" placeholder="例如：VIP, 常买文创, 需要优先联系"><label for="admin_notes">管理员备注</label><textarea id="admin_notes" name="admin_notes" placeholder="只有管理员可以看到"><?= htmlspecialchars((string)($selected['admin_notes'] ?? '')) ?></textarea><button class="save-btn" name="save_customer_notes">保存备注与标签</button></form></div>
      <?php endif;?>
    </section>
  </section>
</main>
<script>
document.addEventListener("click",event=>{const tab=event.target.closest("[data-tab]");if(!tab)return;document.querySelectorAll("[data-tab]").forEach(x=>x.classList.toggle("active",x===tab));document.querySelectorAll("[data-panel]").forEach(x=>x.classList.toggle("active",x.dataset.panel===tab.dataset.tab));});
</script>
</body></html>
