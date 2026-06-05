<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

function money_value($value): string {
    return 'RM ' . number_format((float)$value, 2);
}

function qii_text($text) {
    $text = (string)$text;
    if ($text === '') return '';

    if (preg_match('/[ÂµÃžÃ•ÃšÃÃ¾â•”â•â•‘â•£â•â•—â–“â–‘â”¤â”â””â”´â”¬â”œâ”¼]/u', $text)) {
        $fixed = @iconv('UTF-8', 'CP850//IGNORE', $text);
        if (is_string($fixed) && $fixed !== '' && preg_match('/[\x{4E00}-\x{9FFF}]/u', $fixed)) {
            return $fixed;
        }
    }

    return $text;
}

function scalar(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$todaySales = (float)scalar($pdo, "SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE DATE(created_at)=CURDATE()");
$monthSales = (float)scalar($pdo, "SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())");
$totalSales = (float)scalar($pdo, "SELECT COALESCE(SUM(grand_total),0) FROM orders");
$avgOrder = (float)scalar($pdo, "SELECT COALESCE(AVG(grand_total),0) FROM orders WHERE order_status <> 'draft'");

$todayOrders = (int)scalar($pdo, "SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()");
$awaitingPayment = (int)scalar($pdo, "SELECT COUNT(*) FROM orders WHERE order_status IN ('pending','awaiting_payment')");
$toShip = (int)scalar($pdo, "SELECT COUNT(*) FROM orders WHERE order_status='paid'");
$completed = (int)scalar($pdo, "SELECT COUNT(*) FROM orders WHERE order_status='completed'");
$cancelled = (int)scalar($pdo, "SELECT COUNT(*) FROM orders WHERE order_status='cancelled'");

$productTotal = (int)scalar($pdo, "SELECT COUNT(*) FROM products");
$lowStockCount = (int)scalar($pdo, "SELECT COUNT(*) FROM products WHERE stock <= warning_level AND stock > 0");
$outOfStock = (int)scalar($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 0");
$activeCoupons = (int)scalar($pdo, "SELECT COUNT(*) FROM coupons WHERE status='active'");
$usedCoupons = (int)scalar($pdo, "SELECT COALESCE(SUM(used_count),0) FROM coupons");
$pendingMessages = (int)scalar($pdo, "SELECT COUNT(*) FROM messages");

$topProducts = $pdo->query("
    SELECT oi.product_name, oi.sku, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    GROUP BY oi.product_name, oi.sku
    ORDER BY sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$hotProductCount = count($topProducts);

$lowProducts = $pdo->query("
    SELECT sku, name, stock, warning_level
    FROM products
    WHERE stock <= warning_level
    ORDER BY stock ASC, id DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$recentOrders = $pdo->query("
    SELECT order_number, grand_total, total, order_status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$couponRows = $pdo->query("
    SELECT code, discount_amount, min_order, status, used_count, end_date
    FROM coupons
    ORDER BY used_count DESC, id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

function daily_chart(PDO $pdo, int $days): array {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS date, COALESCE(SUM(grand_total),0) AS total
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)
    ");
    $stmt->execute([$days - 1]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $labels = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i day"));
        $labels[] = date('m/d', strtotime($date));
        $values[] = (float)($rows[$date] ?? 0);
    }

    return ['labels' => $labels, 'values' => $values];
}

function monthly_chart(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(grand_total),0) AS total
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $labels = [];
    $values = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i month"));
        $labels[] = date('M', strtotime($month . '-01'));
        $values[] = (float)($rows[$month] ?? 0);
    }

    return ['labels' => $labels, 'values' => $values];
}

$chartData = [
    '7d' => daily_chart($pdo, 7),
    '30d' => daily_chart($pdo, 30),
    '12m' => monthly_chart($pdo),
];

$overview = [
    ['group' => 'Sales', 'icon' => 'fa-wallet', 'label' => '今日销售额', 'value' => money_value($todaySales)],
    ['group' => 'Sales', 'icon' => 'fa-calendar-days', 'label' => '本月销售额', 'value' => money_value($monthSales)],
    ['group' => 'Sales', 'icon' => 'fa-sack-dollar', 'label' => '总销售额', 'value' => money_value($totalSales)],
    ['group' => 'Sales', 'icon' => 'fa-chart-simple', 'label' => '平均订单金额', 'value' => money_value($avgOrder)],
    ['group' => 'Orders', 'icon' => 'fa-bag-shopping', 'label' => '今日订单', 'value' => $todayOrders],
    ['group' => 'Orders', 'icon' => 'fa-clock', 'label' => '待付款', 'value' => $awaitingPayment],
    ['group' => 'Orders', 'icon' => 'fa-truck-fast', 'label' => '待发货', 'value' => $toShip],
    ['group' => 'Orders', 'icon' => 'fa-circle-check', 'label' => '已完成', 'value' => $completed],
    ['group' => 'Orders', 'icon' => 'fa-circle-xmark', 'label' => '已取消', 'value' => $cancelled],
    ['group' => 'Products', 'icon' => 'fa-boxes-stacked', 'label' => '商品总数', 'value' => $productTotal],
    ['group' => 'Products', 'icon' => 'fa-triangle-exclamation', 'label' => '库存不足', 'value' => $lowStockCount],
    ['group' => 'Products', 'icon' => 'fa-fire', 'label' => '热卖商品', 'value' => $hotProductCount],
    ['group' => 'Products', 'icon' => 'fa-box-open', 'label' => '缺货商品', 'value' => $outOfStock],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Qii Admin | Qii.shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .admin-logo-text {
      margin: 0;
      color: var(--pink);
      font-size: 36px;
      font-weight: 900;
      letter-spacing: 0;
      font-family: "Segoe Script", "Microsoft YaHei", cursive;
    }

    .notice-button {
      width: 46px;
      height: 46px;
      border-radius: 16px;
      background: #fff;
      color: var(--pink);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      position: relative;
    }

    .notice-button span {
      position: absolute;
      top: -6px;
      right: -4px;
      min-width: 22px;
      height: 22px;
      padding: 0 6px;
      border-radius: 999px;
      background: var(--pink);
      color: #fff;
      font-size: 12px;
      line-height: 22px;
    }

    .welcome-card {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 24px;
      align-items: center;
      padding: 28px;
      margin-bottom: 22px;
    }

    .welcome-logo {
      width: 104px;
      height: 104px;
      border-radius: 32px;
      object-fit: cover;
      background: #fff;
      box-shadow: 0 14px 34px rgba(244, 63, 143, 0.18);
    }

    .welcome-copy h1 {
      margin: 0 0 8px;
      font-size: 30px;
    }

    .welcome-copy p {
      margin: 0;
      color: var(--muted);
      font-weight: 700;
    }

    .date-pill,
    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 999px;
      background: var(--pink-soft);
      color: var(--pink);
      font-weight: 800;
      text-decoration: none;
    }

    .overview-group {
      margin-bottom: 22px;
    }

    .overview-group h2,
    .section-head h2 {
      margin: 0 0 14px;
      font-size: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }

    .metric-card,
    .mini-card {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 18px;
      border: 1px solid var(--line);
      border-radius: 20px;
      background: #fff;
      min-height: 104px;
    }

    .metric-icon {
      display: grid;
      place-items: center;
      width: 50px;
      height: 50px;
      border-radius: 18px;
      background: var(--pink-soft);
      color: var(--pink);
      font-size: 22px;
      flex: 0 0 auto;
    }

    .metric-label {
      margin: 0 0 4px;
      color: var(--muted);
      font-weight: 700;
    }

    .metric-value {
      margin: 0;
      font-size: 28px;
      font-weight: 900;
      line-height: 1.1;
    }

    .section-card {
      padding: 24px;
      margin-bottom: 22px;
    }

    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }

    .chart-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .chart-actions button {
      padding: 9px 12px;
      box-shadow: none;
      background: #fff;
      color: var(--pink);
      border: 1px solid var(--line);
    }

    .chart-actions button.active {
      background: var(--pink);
      color: #fff;
    }

    .two-column {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 22px;
    }

    .rows-list {
      display: grid;
      gap: 12px;
    }

    .data-row,
    .order-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 14px;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f7e5ee;
    }

    .order-row {
      grid-template-columns: 1fr auto auto;
    }

    .data-row:last-child,
    .order-row:last-child { border-bottom: 0; }

    .data-row strong,
    .order-row strong {
      display: block;
      margin-bottom: 3px;
      font-size: 17px;
    }

    .data-row small,
    .order-row small {
      color: var(--muted);
      font-weight: 700;
    }

    @media (max-width: 1200px) {
      .metrics-grid { grid-template-columns: repeat(2, 1fr); }
      .two-column { grid-template-columns: 1fr; }
    }

    @media (max-width: 700px) {
      body {
        background: linear-gradient(180deg, #ffe8f2 0%, #fff 22%, #fff8fb 100%);
      }

      .main {
        padding-top: 18px;
      }

      .topbar {
        display: grid;
        grid-template-columns: 1fr 44px;
        align-items: center;
      }

      .admin-logo-text {
        text-align: left;
        font-size: 30px;
      }

      .notice-button {
        width: 44px;
        height: 44px;
      }

      .welcome-card {
        grid-template-columns: 82px 1fr;
        gap: 14px;
        padding: 20px;
        border-radius: 22px;
      }

      .welcome-logo {
        width: 82px;
        height: 82px;
        border-radius: 28px;
      }

      .welcome-copy h1 {
        font-size: 23px;
      }

      .date-pill {
        grid-column: 1 / -1;
        width: fit-content;
      }

      .metrics-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

/* 普通 dashboard 小卡 */
.metric-card {
  display: grid;
  align-content: center;
  gap: 9px;
  min-height: 176px;
  padding: 16px;
  border-radius: 22px;
}

.metric-icon {
  width: 50px;
  height: 50px;
  border-radius: 16px;
  font-size: 22px;
}

.metric-label {
  font-size: 13px;
  line-height: 1.2;
}

.metric-value {
  font-size: 30px;
  line-height: 1;
  overflow-wrap: anywhere;
}

/* Coupon / Promotion 那两个卡专用 */
.two-column .section-card .mini-card {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 78px;
  padding: 16px;
  border-radius: 18px;
}

.two-column .section-card .metrics-grid {
  grid-template-columns: 1fr;
  gap: 12px;
}

.two-column .section-card .mini-card .metric-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  font-size: 18px;
}

.two-column .section-card .mini-card .metric-label {
  font-size: 13px;
  writing-mode: horizontal-tb;
  line-height: 1.2;
  margin-bottom: 4px;
}

.two-column .section-card .mini-card .metric-value {
  font-size: 22px;
}
      .section-card {
        padding: 18px;
        border-radius: 22px;
      }

      .section-head {
        align-items: flex-start;
        flex-direction: column;
      }

      .chart-actions {
        width: 100%;
      }

      .chart-actions button {
        flex: 1;
      }

      .order-row,
      .data-row {
        grid-template-columns: 1fr auto;
      }

      .order-row .status-pill {
        grid-column: 1 / -1;
        width: fit-content;
      }

    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main">
  <div class="topbar">
    <h1 class="admin-logo-text">Qii Admin ❤</h1>
  </div>

  <section class="welcome-card page-card">
    <img src="../images/logo.png" alt="Qii.shop Logo" class="welcome-logo">
    <div class="welcome-copy">
      <h1>欢迎回来，admin 🎀</h1>
      <p>今天也要开心营业哦～</p>
    </div>
    <span class="date-pill"><i class="fa-regular fa-calendar"></i><?= date('Y-m-d') ?></span>
  </section>

  <?php foreach (['Sales', 'Orders', 'Products'] as $group): ?>
    <section class="overview-group">
      <h2><i class="fa-solid fa-star soft-icon"></i><?= $group ?> Overview</h2>
      <div class="metrics-grid">
        <?php foreach ($overview as $item): ?>
          <?php if ($item['group'] !== $group) continue; ?>
          <div class="metric-card">
            <span class="metric-icon"><i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i></span>
            <div><p class="metric-label"><?= htmlspecialchars($item['label']) ?></p><p class="metric-value"><?= htmlspecialchars((string)$item['value']) ?></p></div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <section class="section-card page-card">
    <div class="section-head">
      <h2><i class="fa-solid fa-chart-line soft-icon"></i> Revenue Chart</h2>
      <div class="chart-actions">
        <button type="button" class="range-btn active" data-range="7d">7天</button>
        <button type="button" class="range-btn" data-range="30d">30天</button>
        <button type="button" class="range-btn" data-range="12m">12个月</button>
        <button type="button" class="type-btn active" data-type="line">Line</button>
        <button type="button" class="type-btn" data-type="bar">Bar</button>
      </div>
    </div>
    <canvas id="salesChart" height="96"></canvas>
  </section>

  <section class="two-column">
    <div class="section-card page-card">
      <div class="section-head"><h2><i class="fa-solid fa-fire soft-icon"></i> Top Selling Products</h2></div>
      <div class="rows-list">
        <?php if (!$topProducts): ?><div class="data-row"><strong>暂无销售资料</strong><small>订单完成后会显示。</small></div><?php endif; ?>
        <?php foreach ($topProducts as $p): ?>
          <div class="data-row">
            <div><strong><?= htmlspecialchars(qii_text($p['product_name'] ?? '')) ?></strong><small><?= htmlspecialchars($p['sku'] ?? '-') ?> · Sold <?= (int)$p['sold'] ?></small></div>
            <b><?= money_value($p['revenue'] ?? 0) ?></b>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="section-card page-card">
      <div class="section-head"><h2><i class="fa-solid fa-triangle-exclamation soft-icon"></i> Low Stock Alert</h2><a href="inventory.php?cat=lowstock" class="status-pill">处理库存</a></div>
      <div class="rows-list">
        <?php if (!$lowProducts): ?><div class="data-row"><strong>库存正常</strong><small>暂时没有低库存商品。</small></div><?php endif; ?>
        <?php foreach ($lowProducts as $p): ?>
          <div class="data-row">
            <div><strong><?= htmlspecialchars(qii_text($p['name'] ?? '')) ?></strong><small><?= htmlspecialchars($p['sku'] ?? '-') ?> · 预警 <?= (int)$p['warning_level'] ?></small></div>
            <b><?= (int)$p['stock'] ?></b>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section-card page-card">
    <div class="section-head"><h2><i class="fa-regular fa-clipboard soft-icon"></i> Recent Orders</h2><a class="status-pill" href="order.php">查看全部</a></div>
    <div class="rows-list">
      <?php if (!$recentOrders): ?><div class="order-row"><strong>暂无订单</strong><small>有新订单后会显示在这里。</small></div><?php endif; ?>
      <?php foreach ($recentOrders as $order): ?>
        <div class="order-row">
          <div><strong>#<?= htmlspecialchars($order['order_number'] ?? '') ?></strong><small><?= htmlspecialchars($order['created_at'] ?? '') ?></small></div>
          <b><?= money_value($order['grand_total'] ?: $order['total']) ?></b>
          <span class="status-pill"><?= htmlspecialchars($order['order_status'] ?? 'pending') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="two-column">
    <div class="section-card page-card">
      <div class="section-head"><h2><i class="fa-solid fa-ticket soft-icon"></i> Coupon / Promotion Dashboard</h2><a href="discount_center.php" class="status-pill">管理优惠</a></div>
      <div class="metrics-grid">
        <div class="mini-card"><span class="metric-icon"><i class="fa-solid fa-ticket"></i></span><div><p class="metric-label">启用优惠</p><p class="metric-value"><?= $activeCoupons ?></p></div></div>
        <div class="mini-card"><span class="metric-icon"><i class="fa-solid fa-tags"></i></span><div><p class="metric-label">使用次数</p><p class="metric-value"><?= $usedCoupons ?></p></div></div>
      </div>
    </div>

  </section>
</main>

<script>
const chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
let currentRange = '7d';
let currentType = 'line';
const ctx = document.getElementById('salesChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 260);
gradient.addColorStop(0, 'rgba(244, 63, 143, 0.28)');
gradient.addColorStop(1, 'rgba(244, 63, 143, 0.02)');

let salesChart = buildChart();

function buildChart() {
  return new Chart(ctx, {
    type: currentType,
    data: {
      labels: chartData[currentRange].labels,
      datasets: [{
        data: chartData[currentRange].values,
        borderColor: '#f43f8f',
        backgroundColor: currentType === 'line' ? gradient : 'rgba(244, 63, 143, 0.58)',
        fill: currentType === 'line',
        tension: 0.38,
        borderRadius: 12,
        pointRadius: currentType === 'line' ? 5 : 0,
        pointBackgroundColor: '#f43f8f',
        pointBorderColor: '#fff',
        pointBorderWidth: 3
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, grid: { color: '#f7dce9' } }
      }
    }
  });
}

function refreshChart() {
  salesChart.destroy();
  salesChart = buildChart();
}

document.querySelectorAll('.range-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    currentRange = btn.dataset.range;
    document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    refreshChart();
  });
});

document.querySelectorAll('.type-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    currentType = btn.dataset.type;
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    refreshChart();
  });
});
</script>
</body>
</html>
