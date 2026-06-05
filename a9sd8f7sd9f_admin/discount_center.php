<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

function coupon_type_label(array $coupon): string {
    $amount = (float)($coupon['discount_amount'] ?? 0);
    if ($amount <= 0) return '免运费';
    if ($amount > 0 && $amount < 1) return '百分比折扣';
    if ($amount > 0 && $amount <= 100 && str_contains(strtoupper((string)($coupon['code'] ?? '')), 'SALE')) return '百分比折扣';
    return '固定金额';
}

function coupon_value_text(array $coupon): string {
    $type = coupon_type_label($coupon);
    $amount = (float)($coupon['discount_amount'] ?? 0);
    if ($type === '免运费') return 'RM 0.00';
    if ($type === '百分比折扣') return rtrim(rtrim(number_format($amount, 2), '0'), '.') . '%';
    return 'RM ' . number_format($amount, 2);
}

function coupon_status(array $coupon): array {
    $today = date('Y-m-d');
    if (!empty($coupon['end_date']) && $coupon['end_date'] < $today) {
        return ['已过期', 'expired'];
    }
    if (($coupon['status'] ?? '') !== 'active') {
        return ['已停用', 'inactive'];
    }
    return ['启用中', 'active'];
}

if (isset($_POST['add_coupon'])) {
    verify_csrf();
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'inactive';
    $stmt = $pdo->prepare("
        INSERT INTO coupons (code, description, discount_amount, min_order, start_date, end_date, status, max_usage, used_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([
        trim($_POST['code']),
        trim($_POST['description']),
        (float)$_POST['discount_amount'],
        ($_POST['min_order'] === '' ? null : (float)$_POST['min_order']),
        $_POST['start_date'],
        $_POST['end_date'],
        $status,
        ($_POST['max_usage'] === '' ? null : (int)$_POST['max_usage']),
    ]);
    header('Location: discount_center.php?msg=' . urlencode('优惠码已新增'));
    exit;
}

if (isset($_POST['update_coupon'])) {
    verify_csrf();
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'inactive';
    $stmt = $pdo->prepare("
        UPDATE coupons
        SET code=?, description=?, discount_amount=?, min_order=?, start_date=?, end_date=?, status=?, max_usage=?
        WHERE id=?
    ");
    $stmt->execute([
        trim($_POST['code']),
        trim($_POST['description']),
        (float)$_POST['discount_amount'],
        ($_POST['min_order'] === '' ? null : (float)$_POST['min_order']),
        $_POST['start_date'],
        $_POST['end_date'],
        $status,
        ($_POST['max_usage'] === '' ? null : (int)$_POST['max_usage']),
        (int)$_POST['coupon_id'],
    ]);
    header('Location: discount_center.php?msg=' . urlencode('优惠码已更新'));
    exit;
}

if (isset($_POST['del_coupon'])) {
    verify_csrf();
    $pdo->prepare('DELETE FROM coupons WHERE id=?')->execute([(int)$_POST['del_coupon']]);
    header('Location: discount_center.php?msg=' . urlencode('优惠码已删除'));
    exit;
}

$search = trim($_GET['search'] ?? '');
$description = trim($_GET['description'] ?? '');
$type = $_GET['type'] ?? '';
$minOrder = trim($_GET['min_order'] ?? '');
$maxUsage = trim($_GET['max_usage'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$status = $_GET['status'] ?? '';
$enabled = $_GET['enabled'] ?? '';
$sort = $_GET['sort'] ?? 'created_desc';
$limit = max(10, min(50, (int)($_GET['limit'] ?? 10)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(code LIKE ? OR CAST(id AS CHAR) LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($description !== '') {
    $where[] = 'description LIKE ?';
    $params[] = "%$description%";
}
if ($minOrder !== '') {
    $where[] = 'min_order >= ?';
    $params[] = (float)$minOrder;
}
if ($maxUsage !== '') {
    $where[] = 'max_usage <= ?';
    $params[] = (int)$maxUsage;
}
if ($startDate !== '') {
    $where[] = 'start_date >= ?';
    $params[] = $startDate;
}
if ($endDate !== '') {
    $where[] = 'end_date <= ?';
    $params[] = $endDate;
}
if ($enabled !== '' && in_array($enabled, ['active', 'inactive'], true)) {
    $where[] = 'status = ?';
    $params[] = $enabled;
}
if ($status === 'expired') {
    $where[] = 'end_date < CURDATE()';
} elseif ($status === 'soon') {
    $where[] = 'end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
} elseif ($status === 'valid') {
    $where[] = 'end_date >= CURDATE()';
}
if ($type === 'free_shipping') {
    $where[] = 'discount_amount <= 0';
} elseif ($type === 'fixed') {
    $where[] = 'discount_amount > 0';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = match ($sort) {
    'code_asc' => 'code ASC',
    'usage_desc' => 'used_count DESC',
    'end_asc' => 'end_date ASC',
    default => 'id DESC',
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM coupons $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("SELECT * FROM coupons $whereSql ORDER BY $orderSql LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'all' => (int)$pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn(),
    'active' => (int)$pdo->query("SELECT COUNT(*) FROM coupons WHERE status='active' AND end_date >= CURDATE()")->fetchColumn(),
    'inactive' => (int)$pdo->query("SELECT COUNT(*) FROM coupons WHERE status!='active'")->fetchColumn(),
    'soon' => (int)$pdo->query("SELECT COUNT(*) FROM coupons WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
    'used' => (int)$pdo->query("SELECT COALESCE(SUM(used_count),0) FROM coupons")->fetchColumn(),
];
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>优惠管理 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/discount_admin.css?v=20260604b">
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main discount-page">
  <header class="discount-topbar">
    <div class="title-wrap">
      <h1><i class="fa-solid fa-ticket"></i> 优惠管理</h1>
      <p>创建和管理优惠码，设置使用规则和有效期。</p>
    </div>
    <a class="outline-action desktop-only" href="#coupon-list"><i class="fa-regular fa-clock"></i> 使用记录</a>
    <button class="primary-action" type="button" onclick="document.getElementById('couponCreate').classList.toggle('open')"><i class="fa-solid fa-plus"></i> 新增优惠码</button>
    
  </header>

  <?php if ($msg): ?><div class="discount-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <section id="couponCreate" class="create-panel glass-panel">
    <h2><i class="fa-solid fa-plus"></i> <span data-form-title>新增优惠码</span></h2>
    <form method="post" class="coupon-create-form">
      <?= csrf_field() ?>
      <input type="hidden" name="add_coupon" value="1" data-form-mode>
      <input type="hidden" name="coupon_id" value="" data-coupon-id>
      <input type="text" name="code" placeholder="优惠码，如 QII5" required data-edit-field="code">
      <input type="text" name="description" placeholder="说明" data-edit-field="description">
      <input type="number" step="0.01" name="discount_amount" placeholder="优惠值 / 金额 (RM)" required data-edit-field="discount_amount">
      <input type="number" step="0.01" name="min_order" placeholder="最低订单金额" data-edit-field="min_order">
      <input type="number" step="1" name="max_usage" placeholder="最大使用次数" data-edit-field="max_usage">
      <input type="date" name="start_date" required data-edit-field="start_date">
      <input type="date" name="end_date" required data-edit-field="end_date">
      <select name="status" data-edit-field="status">
        <option value="active">启用</option>
        <option value="inactive">停用</option>
      </select>
      <button type="submit"><i class="fa-solid fa-floppy-disk"></i> 保存优惠码</button>
    </form>
  </section>

  <section class="filter-panel glass-panel">
    <button class="filter-title" type="button" data-filter-toggle><span><i class="fa-solid fa-filter"></i> 筛选条件</span><i class="fa-solid fa-chevron-down"></i></button>
    <form method="get" class="discount-filter">
      <label><span>优惠码 / 名称 / ID</span><input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="请输入优惠码"></label>
      <label><span>说明</span><input name="description" value="<?= htmlspecialchars($description) ?>" placeholder="请输入说明"></label>
      <label><span>优惠类型</span><select name="type"><option value="">全部类型</option><option value="fixed" <?= $type==='fixed'?'selected':'' ?>>固定金额</option><option value="free_shipping" <?= $type==='free_shipping'?'selected':'' ?>>免运费</option></select></label>
      <label><span>最低订单金额 (RM)</span><input type="number" step="0.01" name="min_order" value="<?= htmlspecialchars($minOrder) ?>" placeholder="请输入金额"></label>
      <label><span>最大使用次数</span><input type="number" name="max_usage" value="<?= htmlspecialchars($maxUsage) ?>" placeholder="请输入次数"></label>
      <label><span>有效期开始</span><input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
      <label><span>有效期结束</span><input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
      <label><span>状态</span><select name="status"><option value="">全部状态</option><option value="valid" <?= $status==='valid'?'selected':'' ?>>有效中</option><option value="soon" <?= $status==='soon'?'selected':'' ?>>即将过期</option><option value="expired" <?= $status==='expired'?'selected':'' ?>>已过期</option></select></label>
      <label><span>启用状态</span><select name="enabled"><option value="">全部状态</option><option value="active" <?= $enabled==='active'?'selected':'' ?>>启用中</option><option value="inactive" <?= $enabled==='inactive'?'selected':'' ?>>已停用</option></select></label>
      <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i> 搜索</button>
      <a href="discount_center.php" class="reset-btn"><i class="fa-solid fa-rotate-right"></i> 重置</a>
    </form>
  </section>

  <section class="discount-stats">
    <article><span><i class="fa-solid fa-ticket"></i></span><p>全部优惠码</p><strong><?= $stats['all'] ?></strong></article>
    <article class="purple"><span><i class="fa-solid fa-check"></i></span><p>启用中</p><strong><?= $stats['active'] ?></strong></article>
    <article class="orange"><span><i class="fa-solid fa-pause"></i></span><p>已停用</p><strong><?= $stats['inactive'] ?></strong></article>
    <article class="green"><span><i class="fa-regular fa-clock"></i></span><p>即将过期</p><strong><?= $stats['soon'] ?></strong><small>7 天内到期</small></article>
    <article class="blue"><span><i class="fa-solid fa-gift"></i></span><p>已使用次数</p><strong><?= number_format($stats['used']) ?></strong><small>总使用次数</small></article>
  </section>

  <section id="coupon-list" class="coupon-table-card glass-panel">
    <div class="table-head">
      <h2>优惠码列表</h2>
      <form method="get" class="sort-form">
        <?php foreach ($_GET as $key => $value): if (in_array($key, ['sort', 'page'], true)) continue; ?>
          <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>
        <label>排序：
          <select name="sort" onchange="this.form.submit()">
            <option value="created_desc" <?= $sort==='created_desc'?'selected':'' ?>>创建时间</option>
            <option value="code_asc" <?= $sort==='code_asc'?'selected':'' ?>>优惠码</option>
            <option value="usage_desc" <?= $sort==='usage_desc'?'selected':'' ?>>使用次数</option>
            <option value="end_asc" <?= $sort==='end_asc'?'selected':'' ?>>到期时间</option>
          </select>
        </label>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th><input type="checkbox"></th>
          <th>优惠码<br>说明</th>
          <th>优惠类型<br>优惠值</th>
          <th>最低订单金额<br>有效期</th>
          <th>状态<br>使用次数</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$coupons): ?><tr><td colspan="6" class="empty">暂无优惠码。</td></tr><?php endif; ?>
        <?php foreach ($coupons as $c): ?>
          <?php [$statusText, $statusClass] = coupon_status($c); $typeText = coupon_type_label($c); ?>
          <tr>
            <td class="coupon-check" data-label="选择"><input type="checkbox"></td>
            <td class="coupon-code" data-label="优惠码 / 说明"><strong><?= htmlspecialchars($c['code']) ?></strong><small>ID: <?= (int)$c['id'] ?></small><span><?= htmlspecialchars($c['description'] ?? '') ?></span></td>
            <td><span class="type-pill <?= $typeText === '免运费' ? 'ship' : ($typeText === '百分比折扣' ? 'percent' : 'fixed') ?>"><?= $typeText ?></span><strong><?= coupon_value_text($c) ?></strong></td>
            <td><strong>RM <?= number_format((float)($c['min_order'] ?? 0), 2) ?></strong><small><?= htmlspecialchars($c['start_date']) ?><br>~ <?= htmlspecialchars($c['end_date']) ?></small></td>
            <td><span class="status-pill <?= $statusClass ?>"><?= $statusText ?></span><small><?= (int)($c['used_count'] ?? 0) ?> / <?= $c['max_usage'] === null ? '不限' : (int)$c['max_usage'] ?></small></td>
            <td>
              <div class="coupon-actions">
                <button
                  type="button"
                  aria-label="编辑"
                  data-edit-coupon
                  data-id="<?= (int)$c['id'] ?>"
                  data-code="<?= htmlspecialchars($c['code'], ENT_QUOTES) ?>"
                  data-description="<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>"
                  data-discount_amount="<?= htmlspecialchars((string)$c['discount_amount'], ENT_QUOTES) ?>"
                  data-min_order="<?= htmlspecialchars((string)($c['min_order'] ?? ''), ENT_QUOTES) ?>"
                  data-max_usage="<?= htmlspecialchars((string)($c['max_usage'] ?? ''), ENT_QUOTES) ?>"
                  data-start_date="<?= htmlspecialchars((string)$c['start_date'], ENT_QUOTES) ?>"
                  data-end_date="<?= htmlspecialchars((string)$c['end_date'], ENT_QUOTES) ?>"
                  data-status="<?= htmlspecialchars((string)$c['status'], ENT_QUOTES) ?>"
                ><i class="fa-solid fa-pen"></i></button>
                <form method="post" onsubmit="return confirm('确定删除这个优惠码吗？');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="del_coupon" value="<?= (int)$c['id'] ?>">
                  <button type="submit" aria-label="删除" class="delete-btn">
  <i class="fa-solid fa-trash"></i>
</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <footer class="discount-footer">
    <span>共 <?= $totalRows ?> 条记录</span>
    <nav>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
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
<script src="js/product_admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var panel = document.getElementById('couponCreate');
  var mode = document.querySelector('[data-form-mode]');
  var title = document.querySelector('[data-form-title]');
  var idField = document.querySelector('[data-coupon-id]');

  document.querySelectorAll('[data-edit-coupon]').forEach(function (button) {
    button.addEventListener('click', function () {
      panel.classList.add('open');
      mode.name = 'update_coupon';
      mode.value = '1';
      title.textContent = '编辑优惠码';
      idField.value = button.dataset.id || '';
      ['code','description','discount_amount','min_order','max_usage','start_date','end_date','status'].forEach(function (key) {
        var field = document.querySelector('[data-edit-field="' + key + '"]');
        if (field) field.value = button.dataset[key] || '';
      });
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  var filterToggle = document.querySelector('[data-filter-toggle]');
  var filterPanel = document.querySelector('.filter-panel');
  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function () {
      filterPanel.classList.toggle('filters-open');
    });
  }
});
</script>
</body>
</html>
