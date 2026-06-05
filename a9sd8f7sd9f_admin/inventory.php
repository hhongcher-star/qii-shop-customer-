<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$categories = [
  'phone' => '手机配件',
  'hair' => '发夹发饰',
  'snack' => '零食',
  'creative' => '文创',
  'case' => '手机壳',
  'nail' => '穿戴甲',
  'scent' => '香片',
  'doll' => '娃娃',
  'stationery' => '文具',
];

function qii_text($text) {
    $text = (string)$text;
    if ($text === '') return '';
    if (preg_match('/[Ã‚ÂµÃƒÅ¾Ãƒâ€¢ÃƒÅ¡ÃƒÂÃƒÂ¾Ã¢â€¢â€Ã¢â€¢ÂÃ¢â€¢â€˜Ã¢â€¢Â£Ã¢â€¢ÂÃ¢â€¢â€”Ã¢â€“â€œÃ¢â€“â€˜Ã¢â€Â¤Ã¢â€ÂÃ¢â€â€Ã¢â€Â´Ã¢â€Â¬Ã¢â€Å“Ã¢â€Â¼]/u', $text)) {
        $fixed = @iconv('UTF-8', 'CP850//IGNORE', $text);
        if (is_string($fixed) && $fixed !== '' && preg_match('/[\x{4E00}-\x{9FFF}]/u', $fixed)) return $fixed;
    }
    return $text;
}

function img_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '../images/logo.png';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    return '../' . ltrim($path, '/');
}

function redirect_inventory(array $extra = []): void {
    $query = array_merge($_GET, $extra);
    header('Location: inventory.php?' . http_build_query($query));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = $_POST['type'] ?? 'product';
    $id = (int)($_POST['id'] ?? 0);
    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || ($_POST['ajax'] ?? '') === '1';
    $newStock = null;
    $warningLevel = null;

    if ($id > 0) {
        $table = $type === 'variant' ? 'product_variants' : 'products';
        if (isset($_POST['adjust'])) {
            $delta = (int)$_POST['adjust'];
            $stmt = $pdo->prepare("UPDATE $table SET stock = GREATEST(0, stock + ?) WHERE id=?");
            $stmt->execute([$delta, $id]);
        } elseif (isset($_POST['stock'])) {
            $stmt = $pdo->prepare("UPDATE $table SET stock=? WHERE id=?");
            $stmt->execute([max(0, (int)$_POST['stock']), $id]);
        }
        if ($table === 'products' && isset($_POST['warning_level'])) {
            $stmt = $pdo->prepare("UPDATE products SET warning_level=? WHERE id=?");
            $stmt->execute([max(0, (int)$_POST['warning_level']), $id]);
        }
        $stockStmt = $pdo->prepare("SELECT stock FROM $table WHERE id=?");
        $stockStmt->execute([$id]);
        $newStock = (int)$stockStmt->fetchColumn();
        if ($table === 'products') {
            $warnStmt = $pdo->prepare("SELECT warning_level FROM products WHERE id=?");
            $warnStmt->execute([$id]);
            $warningLevel = (int)$warnStmt->fetchColumn();
        } else {
            $warnStmt = $pdo->prepare("
                SELECT p.warning_level
                FROM product_variants v
                INNER JOIN product_groups g ON g.id = v.group_id
                INNER JOIN products p ON p.id = g.product_id
                WHERE v.id=?
            ");
            $warnStmt->execute([$id]);
            $warningLevel = (int)$warnStmt->fetchColumn();
        }
    }
    if ($isAjax) {
        $state = $newStock <= 0 ? '缺货' : ($newStock <= $warningLevel ? '库存不足' : '正常');
        $stateClass = $newStock <= 0 ? 'out' : ($newStock <= $warningLevel ? 'low' : 'normal');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'stock' => $newStock,
            'warning' => $warningLevel,
            'state' => $state,
            'stateClass' => $stateClass,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    redirect_inventory(['msg' => '库存已更新']);
}

$search = trim($_GET['search'] ?? '');
$cat = $_GET['cat'] ?? '';
$stockStatus = $_GET['stock_status'] ?? '';
$warnStatus = $_GET['warn_status'] ?? '';
$productMode = $_GET['product_mode'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$sql = "
    SELECT
      p.id AS product_id,
      CASE WHEN v.id IS NULL THEN 'product' ELSE 'variant' END AS item_type,
      COALESCE(v.id, p.id) AS item_id,
      p.name AS product_name,
      p.category,
      p.warning_level,
      p.has_variant,
      p.image_url AS product_image,
      p.sku AS product_sku,
      v.variant_name,
      v.sku AS variant_sku,
      v.image_url AS variant_image,
      COALESCE(v.stock, p.stock) AS stock
    FROM products p
    LEFT JOIN product_groups g ON g.product_id = p.id
    LEFT JOIN product_variants v ON v.group_id = g.id
";

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR v.variant_name LIKE ? OR v.sku LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
}
if ($cat !== '' && isset($categories[$cat])) {
    $where[] = "p.category=?";
    $params[] = $cat;
}
if ($productMode === 'single') {
    $where[] = "COALESCE(p.has_variant, 0) = 0";
} elseif ($productMode === 'variant') {
    $where[] = "COALESCE(p.has_variant, 0) = 1";
}
if ($stockStatus === 'out') {
    $where[] = "COALESCE(v.stock, p.stock) <= 0";
} elseif ($stockStatus === 'low') {
    $where[] = "COALESCE(v.stock, p.stock) > 0 AND COALESCE(v.stock, p.stock) <= p.warning_level";
} elseif ($stockStatus === 'normal') {
    $where[] = "COALESCE(v.stock, p.stock) > p.warning_level";
}
if ($warnStatus === 'warning') {
    $where[] = "COALESCE(v.stock, p.stock) <= p.warning_level";
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);

$orderSql = match ($sort) {
    'stock_asc' => 'stock ASC, product_id DESC',
    'stock_desc' => 'stock DESC, product_id DESC',
    'name' => 'product_name ASC',
    default => 'product_id DESC, item_id DESC',
};
$sql .= " ORDER BY $orderSql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
foreach ($rows as $r) {
    $productId = (int)$r['product_id'];
    if (!isset($products[$productId])) {
        $products[$productId] = [
            'id' => $productId,
            'name' => $r['product_name'],
            'category' => $r['category'],
            'warning_level' => (int)$r['warning_level'],
            'has_variant' => (int)($r['has_variant'] ?? 0),
            'image' => $r['product_image'],
            'sku' => $r['product_sku'],
            'variants' => [],
            'total_stock' => 0,
            'low_count' => 0,
            'out_count' => 0,
        ];
    }
    $stock = (int)$r['stock'];
    $warning = (int)$r['warning_level'];
    $variantId = $r['item_type'] === 'variant' ? (int)$r['item_id'] : 0;
    $products[$productId]['variants'][] = [
      'id' => $variantId ?: $productId,
      'type' => $r['item_type'],
      'name' => $r['variant_name'] ?: '默认规格',
      'sku' => $r['variant_sku'] ?: $r['product_sku'],
      'image' => $r['variant_image'] ?: $r['product_image'],
      'stock' => $stock,
      'warning' => $warning,
    ];
    $products[$productId]['total_stock'] += $stock;
    if ($stock <= 0) $products[$productId]['out_count']++;
    elseif ($stock <= $warning) $products[$productId]['low_count']++;
}

$rows = [];
foreach ($products as $p) {
    $rows[] = [
        'product_id' => $p['id'],
        'item_type' => 'product',
        'item_id' => $p['id'],
        'product_name' => $p['name'],
        'category' => $p['category'],
        'warning_level' => $p['warning_level'],
        'has_variant' => $p['has_variant'],
        'product_image' => $p['image'],
        'product_sku' => $p['sku'],
        'variant_name' => ((int)$p['has_variant'] === 1)
    ? count($p['variants']) . ' 个规格'
    : '单一商品',
        'variant_sku' => '',
        'variant_image' => '',
        'stock' => $p['total_stock'],
    ];
}

$totalStock = (int)$pdo->query("
    SELECT COALESCE(SUM(stock), 0) FROM (
      SELECT v.stock FROM product_variants v
      UNION ALL
      SELECT p.stock FROM products p
      WHERE NOT EXISTS (
        SELECT 1 FROM product_groups g INNER JOIN product_variants v2 ON v2.group_id=g.id WHERE g.product_id=p.id
      )
    ) x
")->fetchColumn();

$lowCount = 0;
$outCount = 0;
foreach ($rows as $r) {
    if ((int)$r['stock'] <= 0) $outCount++;
    elseif ((int)$r['stock'] <= (int)$r['warning_level']) $lowCount++;
}
$todayAdjust = 0;
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>库存管理 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/inventory_admin.css?v=20260604">
  <style>
    @media (max-width: 760px) {
      .inventory-table-card { overflow-x: auto !important; overflow-y: visible !important; padding: 0 !important; -webkit-overflow-scrolling: touch; }
      .inventory-table-card table { display: table !important; width: 1120px !important; min-width: 1120px !important; table-layout: fixed !important; border-collapse: collapse !important; }
      .inventory-table-card thead { display: table-header-group !important; }
      .inventory-table-card tbody { display: table-row-group !important; }
      .inventory-table-card tr, .inventory-row { display: table-row !important; padding: 0 !important; }
      .inventory-table-card th,
      .inventory-table-card td,
      .inventory-row td,
      .inventory-row td:nth-child(3),
      .inventory-row td:nth-child(4),
      .inventory-row td:nth-child(5),
      .inventory-row td:nth-child(7),
      .inventory-row td:nth-child(8),
      .inventory-row td:nth-child(9) {
        display: table-cell !important;
        padding: 14px 12px !important;
        border-bottom: 1px solid #ffe0ee !important;
        vertical-align: middle !important;
        text-align: center !important;
        white-space: nowrap !important;
      }
      .inventory-table-card th { background: #fff4fa !important; color: var(--muted) !important; font-size: 14px !important; font-weight: 900 !important; word-break: keep-all !important; }
      .inventory-table-card th:nth-child(1), .inventory-table-card td:nth-child(1) { width: 52px !important; }
      .inventory-table-card th:nth-child(2), .inventory-table-card td:nth-child(2) { width: 250px !important; }
      .inventory-table-card th:nth-child(3), .inventory-table-card td:nth-child(3) { width: 120px !important; }
      .inventory-table-card th:nth-child(4), .inventory-table-card td:nth-child(4) { width: 140px !important; }
      .inventory-table-card th:nth-child(5), .inventory-table-card td:nth-child(5) { width: 120px !important; }
      .inventory-table-card th:nth-child(6), .inventory-table-card td:nth-child(6) { width: 120px !important; }
      .inventory-table-card th:nth-child(7), .inventory-table-card td:nth-child(7) { width: 120px !important; }
      .inventory-table-card th:nth-child(8), .inventory-table-card td:nth-child(8) { width: 140px !important; }
      .inventory-table-card th:nth-child(9), .inventory-table-card td:nth-child(9) { width: 170px !important; }
      .inventory-row td:nth-child(8) { margin-top: 0 !important; }
      .inventory-row td:nth-child(7) > span::before { content: none !important; }
      .product-cell { display: grid !important; grid-template-columns: 64px minmax(0, 1fr) !important; gap: 12px !important; align-items: center !important; text-align: left !important; }
      .product-cell img { width: 64px !important; height: 64px !important; }
      .stock-actions { display: flex !important; justify-content: center !important; gap: 8px !important; }
      /* Old variant-specific mobile style removed because row expansion now uses table rows */
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main inventory-page">
  <header class="inventory-topbar">
    <div class="title-wrap">
      <h1><i class="fa-solid fa-boxes-stacked"></i> 库存管理</h1>
      <p>管理商品规格库存，调整库存数量和预警值，确保库存充足。</p>
    </div>
    
    <span class="date-pill"><i class="fa-regular fa-calendar"></i><?= date('Y-m-d') ?><i class="fa-solid fa-chevron-down"></i></span>
  </header>

  <section class="inventory-stats">
    <article class="stat-card"><span><i class="fa-solid fa-box"></i></span><div><p>总库存数量</p><strong><?= number_format($totalStock) ?></strong><small>所有规格库存总数</small></div></article>
    <article class="stat-card orange"><span><i class="fa-solid fa-exclamation"></i></span><div><p>库存不足</p><strong><?= $lowCount ?></strong><small>低于预警值</small></div></article>
    <article class="stat-card red"><span><i class="fa-solid fa-box-open"></i></span><div><p>缺货数量</p><strong><?= $outCount ?></strong><small>库存为 0</small></div></article>
    <article class="stat-card purple"><span><i class="fa-solid fa-chart-column"></i></span><div><p>今日调整</p><strong><?= $todayAdjust ?></strong><small>库存变动记录</small></div></article>
  </section>

  <?php if ($msg): ?><div class="inventory-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <section class="inventory-tools glass-panel">
    <form method="get" class="inventory-filter">
      <label class="search-field"><i class="fa-solid fa-magnifying-glass"></i><input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索商品名称 / 规格 / SKU"></label>
      <select name="cat">
        <option value="">选择分类</option>
        <?php foreach ($categories as $key => $label): ?><option value="<?= htmlspecialchars($key) ?>" <?= $cat===$key?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
      </select>
      <select name="product_mode">
        <option value="">全部商品类型</option>
        <option value="single" <?= $productMode==='single'?'selected':'' ?>>单一商品</option>
        <option value="variant" <?= $productMode==='variant'?'selected':'' ?>>有规格商品</option>
      </select>
      <select name="stock_status">
        <option value="">库存状态</option>
        <option value="normal" <?= $stockStatus==='normal'?'selected':'' ?>>正常</option>
        <option value="low" <?= $stockStatus==='low'?'selected':'' ?>>库存不足</option>
        <option value="out" <?= $stockStatus==='out'?'selected':'' ?>>缺货</option>
      </select>
      <select name="warn_status">
        <option value="">预警状态</option>
        <option value="warning" <?= $warnStatus==='warning'?'selected':'' ?>>需要补货</option>
      </select>
      <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> 筛选</button>
      <a href="inventory.php" class="reset-btn"><i class="fa-solid fa-rotate-right"></i> 重置</a>
    </form>
      </section>

  <section class="inventory-table-card">
    <div class="mobile-list-head"><strong>共 <?= count($rows) ?> 条记录</strong><span>排序：最新 <i class="fa-solid fa-arrow-up-short-wide"></i></span></div>
    <table>
      <thead>
        <tr>
          <th><input type="checkbox"></th>
          <th>商品信息</th>
          <th>规格信息</th>
          <th>SKU</th>
          <th>分类</th>
          <th>当前库存</th>
          <th>预警值</th>
          <th>状态</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="9" class="empty">暂无库存记录。</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $stock = (int)$r['stock'];
            $warning = (int)$r['warning_level'];
            $state = $stock <= 0 ? '缺货' : ($stock <= $warning ? '库存不足' : '正常');
            $stateClass = $stock <= 0 ? 'out' : ($stock <= $warning ? 'low' : 'normal');
            $sku = $r['variant_sku'] ?: $r['product_sku'];
            $image = $r['variant_image'] ?: $r['product_image'];
          ?>
          <tr class="inventory-row" data-inventory-row data-row-kind="product" data-product-id="<?= (int)$r['product_id'] ?>">
            <td class="check-cell"><input type="checkbox"></td>
            <td class="product-cell"><img src="<?= htmlspecialchars(img_url($image)) ?>" alt=""><div><strong><?= htmlspecialchars(qii_text($r['product_name'])) ?></strong></div></td>
            <td><?= htmlspecialchars(qii_text($r['variant_name'] ?: '默认规格')) ?></td>
            <td><?= htmlspecialchars($sku ?? '-') ?></td>
            <td><span class="cat-pill"><?= htmlspecialchars($categories[$r['category']] ?? $r['category']) ?></span></td>
            <td class="stock-number" data-stock-cell><?= $stock ?></td>
            <td>
              <?php if ($r['item_type'] === 'product'): ?>
                <form method="post" class="warning-form" data-inventory-form>
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="product">
                  <input type="hidden" name="id" value="<?= (int)$r['item_id'] ?>">
                  <input type="number" name="warning_level" value="<?= $warning ?>">
                  <button title="保存预警"><i class="fa-solid fa-pen"></i></button>
                </form>
              <?php else: ?>
                <span><?= $warning ?></span>
              <?php endif; ?>
            </td>
            <td><span class="state-pill <?= $stateClass ?>" data-state-pill><?= $state ?></span></td>
            <td>
              <div class="stock-actions">
                <?php if ((int)($r['has_variant'] ?? 0) === 0): ?>
                  <form method="post" data-inventory-form><?= csrf_field() ?><input type="hidden" name="type" value="product"><input type="hidden" name="id" value="<?= (int)$r['item_id'] ?>"><input type="hidden" name="adjust" value="1"><button><i class="fa-solid fa-plus"></i></button></form>
                  <form method="post" data-inventory-form><?= csrf_field() ?><input type="hidden" name="type" value="product"><input type="hidden" name="id" value="<?= (int)$r['item_id'] ?>"><input type="hidden" name="adjust" value="-1"><button class="minus"><i class="fa-solid fa-minus"></i></button></form>
                <?php endif; ?>
                <?php if ((int)($r['has_variant'] ?? 0) === 1): ?>
                  <button type="button" class="history" data-toggle-variants="<?= (int)$r['product_id'] ?>"><i class="fa-solid fa-chevron-down"></i></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
            <?php if ((int)($r['has_variant'] ?? 0) === 1): ?>
            <?php foreach ($products[(int)$r['product_id']]['variants'] as $v): ?>
  <?php
    $vState = $v['stock'] <= 0 ? '缺货' : ($v['stock'] <= $v['warning'] ? '库存不足' : '正常');
    $vStateClass = $v['stock'] <= 0 ? 'out' : ($v['stock'] <= $v['warning'] ? 'low' : 'normal');
  ?>

  <tr class="variant-detail-row" data-variant-panel="<?= (int)$r['product_id'] ?>" data-inventory-row data-row-kind="variant" data-product-id="<?= (int)$r['product_id'] ?>" hidden>
    <td></td>
    <td class="product-cell">
      <img src="<?= htmlspecialchars(img_url($v['image'])) ?>" alt="">
      <div><strong><?= htmlspecialchars(qii_text($v['name'])) ?></strong></div>
    </td>
    <td><?= htmlspecialchars(qii_text($v['name'])) ?></td>
    <td><?= htmlspecialchars($v['sku'] ?: '-') ?></td>
    <td><span class="cat-pill"><?= htmlspecialchars($categories[$r['category']] ?? $r['category']) ?></span></td>
    <td class="stock-number" data-stock-cell><?= (int)$v['stock'] ?></td>
    <td><?= (int)$v['warning'] ?></td>
    <td><span class="state-pill <?= $vStateClass ?>" data-state-pill><?= $vState ?></span></td>
    <td>
      <div class="stock-actions">
        <form method="post" data-inventory-form><?= csrf_field() ?><input type="hidden" name="type" value="<?= htmlspecialchars($v['type']) ?>"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="adjust" value="1"><button><i class="fa-solid fa-plus"></i></button></form>
        <form method="post" data-inventory-form><?= csrf_field() ?><input type="hidden" name="type" value="<?= htmlspecialchars($v['type']) ?>"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="adjust" value="-1"><button class="minus"><i class="fa-solid fa-minus"></i></button></form>
      </div>
    </td>
  </tr>
<?php endforeach; ?>
<?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  
    <button type="button" class="load-more">加载更多 <i class="fa-solid fa-chevron-down"></i></button>
  </section>
</main>

<script src="js/product_admin.js?v=20260604"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-toggle-variants]').forEach(function (button) {
    button.addEventListener('click', function () {
      var panels = document.querySelectorAll('[data-variant-panel="' + button.dataset.toggleVariants + '"]');
      if (!panels.length) return;
      var nextHidden = !panels[0].hidden;
      panels.forEach(function (panel) { panel.hidden = nextHidden; });
      button.classList.toggle('open', !nextHidden);
    });
  });

  document.querySelectorAll('[data-inventory-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const button = form.querySelector('button');
      const row = form.closest('[data-inventory-row]');
      const fd = new FormData(form);
      fd.append('ajax', '1');
      if (button) button.disabled = true;

      fetch('inventory.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success || !row) return;
        const stockCell = row.querySelector('[data-stock-cell]');
        const statePill = row.querySelector('[data-state-pill]');
        const oldStock = stockCell ? parseInt(stockCell.textContent || '0', 10) : 0;
        if (stockCell) stockCell.textContent = data.stock;
        if (row.dataset.rowKind === 'variant') {
          const parentRow = document.querySelector('[data-row-kind="product"][data-product-id="' + row.dataset.productId + '"]');
          const parentStockCell = parentRow?.querySelector('[data-stock-cell]');
          if (parentStockCell) {
            const parentStock = parseInt(parentStockCell.textContent || '0', 10);
            parentStockCell.textContent = Math.max(0, parentStock + (parseInt(data.stock, 10) - oldStock));
          }
        }
        if (statePill) {
          statePill.textContent = data.state;
          statePill.className = 'state-pill ' + data.stateClass;
        }
      })
      .catch(function () {
        form.submit();
      })
      .finally(function () {
        if (button) button.disabled = false;
      });
    });
  });
});
</script>
</body>
</html>
