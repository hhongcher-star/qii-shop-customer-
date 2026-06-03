<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

/* =============================
   🎁 优惠码新增 / 删除
============================= */
if (isset($_POST['add_coupon'])) {
  $stmt = $pdo->prepare("INSERT INTO coupons (code, description, discount_amount, min_order, start_date, end_date, status, max_usage, used_count)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
  $stmt->execute([
    $_POST['code'],
    $_POST['description'],
    floatval($_POST['discount_amount']),
    ($_POST['min_order'] === '' ? null : floatval($_POST['min_order'])),
    $_POST['start_date'],
    $_POST['end_date'],
    $_POST['status'],
    ($_POST['max_usage'] === '' ? null : intval($_POST['max_usage']))
  ]);
  $msg = "🎁 优惠码已添加";
}

if (isset($_GET['del_coupon'])) {
  $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([intval($_GET['del_coupon'])]);
  $msg = "❌ 优惠码已删除";
}

/* =============================
   ✅ 数据加载
============================= */
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>优惠码管理 | Qii.shop</title>
<style>
body {
  font-family:'Inter','Noto Sans SC',sans-serif;
  background:#18191C;
  color:#f5f5f5;
  margin:0;
}
main {
  margin-left:240px;
  padding:25px;
}
h1 {
  font-size:22px;
  margin-top:0;
  color:#000;
}
h2 {
  margin-top:40px;
  border-bottom:1px solid #333;
  padding-bottom:5px;
  color:#ffb6c1; /* 粉红标题 */
}
table {
  width:100%;
  border-collapse:collapse;
  background:#1f1f23;
  margin-top:10px;
  border-radius:6px;
  overflow:hidden;
}
th,td {
  border:1px solid #333;
  padding:10px;
  text-align:center;
  color:#eee;
}
th {
  background:#292a2d;
  color:#fff;
  font-weight:600;
}
input,select {
  background:#2a2b2f;
  color:#fff;
  border:1px solid #555;
  border-radius:4px;
  padding:8px;
  font-size:14px;
}
input::placeholder {
  color:#aaa;
}
button {
  background:#00AEEF;
  border:none;
  color:white;
  padding:8px 16px;
  border-radius:6px;
  cursor:pointer;
  font-size:14px;
  font-weight:500;
  box-shadow:0 2px 5px rgba(0,0,0,0.2);
}
button:hover {
  background:#0090cc;
}
a {
  color:#00AEEF;
  text-decoration:none;
  font-weight:500;
}
a:hover {
  text-decoration:underline;
}
.msg {
  background:#2E7D32;
  padding:10px;
  border-radius:6px;
  margin-bottom:15px;
  color:#fff;
  font-weight:500;
}
form input, form select {
  margin:3px;
}
</style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>
<main>
<h1>🎁 优惠码管理中心</h1>
<?php if(isset($msg)): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- 🎁 优惠码 -->
<h2>新增优惠码</h2>
<form method="post">
  <input type="hidden" name="add_coupon" value="1">
  <input type="text" name="code" placeholder="优惠码 (如 QII5)" required>
  <input type="text" name="description" placeholder="说明">
  <input type="number" step="0.01" name="discount_amount" placeholder="扣减金额 (RM)" required>
  <input type="number" step="0.01" name="min_order" placeholder="最低订单金额">
  <input type="number" step="1" name="max_usage" placeholder="最大使用次数 (留空=不限)">
  <input type="date" name="start_date" required>
  <input type="date" name="end_date" required>
  <select name="status">
    <option value="active">启用</option>
    <option value="inactive">停用</option>
  </select>
  <button type="submit">➕ 添加优惠码</button>
</form>

<h2>优惠码列表</h2>
<table>
  <tr><th>ID</th><th>优惠码</th><th>说明</th><th>金额</th><th>最低金额</th><th>有效期</th><th>状态</th><th>操作</th></tr>
  <?php foreach($coupons as $c): ?>
  <tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['code']) ?></td>
    <td><?= htmlspecialchars($c['description']) ?></td>
    <td>RM <?= number_format($c['discount_amount'],2) ?></td>
    <td><?= number_format($c['min_order'],2) ?></td>
    <td><?= $c['start_date'] ?> 至 <?= $c['end_date'] ?></td>
    <td><?= $c['status']==='active'?'🟢 启用':'🔴 停用' ?></td>
    <td><a href="?del_coupon=<?= $c['id'] ?>" onclick="return confirm('确定删除此优惠码?')">删除</a></td>
  </tr>
  <?php endforeach; ?>
</table>

</main>
</body>
</html>
