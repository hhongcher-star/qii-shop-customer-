<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

// === 更新订单状态（由本页处理）====
if (isset($_POST['update_status'])) {
    $order_number = $_POST['order_number'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($order_number && $status) {
        $stmt = $pdo->prepare("UPDATE orders SET order_status=?, updated_at=NOW() WHERE order_number=?");
        $stmt->execute([$status, $order_number]);
    }
    header("Location: order.php");
    exit;
}

// ✅ 批量删除功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_ids'])) {
    $ids = array_map('intval', $_POST['order_ids']);
    if (!empty($ids)) {
        $in = str_repeat('?,', count($ids) - 1) . '?';
        if (isset($_POST['delete_selected'])) {
            $pdo->prepare("DELETE FROM orders WHERE id IN ($in)")->execute($ids);
        }
    }
   header("Location: /a9sd8f7sd9f_admin/order.php");
    exit;
}

// ✅ 分页与搜索
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$month  = $_GET['month'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where .= " AND order_number LIKE ?";
    $params[] = "%$search%";
}
if ($month !== '') {
    $where .= " AND DATE_FORMAT(created_at, '%Y-%m') = ?";
    $params[] = $month;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders $where");
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

$sql = "SELECT * FROM orders $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>订单管理 - Qii.Shop 管理后台</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', 'Noto Sans SC', sans-serif;
      margin: 0;
      background: #f7f8fb;
      color: #2c3e50;
    }

    .main {
      margin-left: 230px;
      padding: 40px;
    }

    h1 {
      font-size: 22px;
      margin-bottom: 25px;
    }

    .search-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .search-bar input, .search-bar select {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
    }

    .search-bar button {
      background: #0984e3;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
    }

    .search-bar button:hover { background: #0768b1; }

    .btn-delete {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      margin-bottom: 10px;
    }
    .btn-delete:hover { background: #c0392b; }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      border-radius: 10px;
      overflow: hidden;
    }

    th, td {
      padding: 14px 18px;
      border-bottom: 1px solid #eee;
      text-align: center;
      font-size: 14px;
    }

    th {
      background: #f1f3f5;
      font-weight: 600;
    }

    tr:hover { background: #f9fafb; }

    .btn-view {
      padding: 6px 10px;
      border-radius: 6px;
      border: none;
      background: #3498db;
      color: white;
      cursor: pointer;
      font-size: 13px;
    }
    .btn-view:hover { background: #2980b9; }

    .pagination {
      margin-top: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
    }

    .pagination a {
      text-decoration: none;
      padding: 6px 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      color: #333;
    }

    .pagination a.active {
      background: #0984e3;
      color: white;
      border-color: #0984e3;
    }

    .no-data {
      text-align: center;
      padding: 40px 0;
      color: #888;
    }
  </style>
</head>
<body>

<!-- ✅ 统一导航 -->
<?php include 'includes/admin_header.php'; ?>

<!-- ✅ 主体内容 -->
<div class="main">
  <h1><i class="fa-solid fa-receipt"></i> 订单管理</h1>

  <!-- 搜索区 -->
  <form method="get" class="search-bar">
    <input type="text" name="search" placeholder="输入订单号..." value="<?= htmlspecialchars($search ?? '') ?>">
    <input type="month" name="month" value="<?= htmlspecialchars($month ?? '') ?>">
    <button type="submit">搜索</button>
  </form>

  <!-- 批量删除 -->
  <form method="post" id="delete-form" onsubmit="return confirm('确定要删除选中的订单吗？此操作不可恢复。');">
    <button type="submit" name="delete_selected" class="btn-delete">🗑 批量删除</button>
  </form>

    <table>
      <tr>
        <th><input type="checkbox" form="delete-form" onclick="document.querySelectorAll('input[name*=\'order_ids\']').forEach(cb => cb.checked = this.checked);"></th>
        <th>订单号</th>
        <th>下单时间</th>
        <th>总金额 (RM)</th>
        <th>收货信息</th>
        <th>订单状态</th>
        <th>查看收据</th>
      </tr>

      <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="no-data">暂无订单记录</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><input type="checkbox" form="delete-form" name="order_ids[]" value="<?= $o['id'] ?>"></td>
            <td><?= htmlspecialchars($o['order_number'] ?? '') ?></td>
            <td><?= $o['created_at'] ? date("Y年n月j日 H:i", strtotime($o['created_at'])) : '' ?></td>
            <td><?= number_format($o['total'] ?? 0, 2) ?></td>
            <td style="text-align:left;">
                <b><?= htmlspecialchars($o['addr_name'] ?? '') ?></b><br>
                📞 <?= htmlspecialchars($o['addr_phone'] ?? '') ?><br>
                🏡 <?= htmlspecialchars($o['addr_address'] ?? '') ?><br>
                <?= htmlspecialchars($o['addr_postcode'] ?? '') ?> <?= htmlspecialchars($o['addr_state'] ?? '') ?>
            </td>
            <td>
                <form method="post" style="display:flex; gap:6px; justify-content:center;">
                    <input type="hidden" name="order_number" value="<?= htmlspecialchars($o['order_number'] ?? '') ?>">

                    <select name="status" style="padding:6px; border-radius:6px;">
                        <option value="pending"          <?= ($o['order_status'] ?? 'pending') =='pending'?'selected':'' ?>>Pending</option>
                        <option value="awaiting_payment" <?= ($o['order_status'] ?? '')=='awaiting_payment'?'selected':'' ?>>Awaiting Payment</option>
                        <option value="paid"             <?= ($o['order_status'] ?? '')=='paid'?'selected':'' ?>>Paid</option>
                        <option value="shipped"          <?= ($o['order_status'] ?? '')=='shipped'?'selected':'' ?>>Shipped</option>
                        <option value="completed"        <?= ($o['order_status'] ?? '')=='completed'?'selected':'' ?>>Completed</option>
                    </select>

                    <button type="submit" name="update_status" class="btn-view">更新</button>
                </form>
            </td>
            <td>
              <a href="../receipt.php?order_number=<?= urlencode($o['order_number'] ?? '') ?>" target="_blank">
                <button type="button" class="btn-view">收据</button>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>

  <!-- 分页 -->
  <div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&month=<?= urlencode($month ?? '') ?>" class="<?= $i == $page ? 'active' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
</div>
</body>
</html>