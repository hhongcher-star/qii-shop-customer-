<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

// ✅ Qii.shop 分类
$categories = [
  'phone'       => '📱 手机配件',
  'hair'        => '🎀 发夹发饰',
  'snack'       => '🍭 零食',
  'creative'    => '💗 文创',
  'case'        => '💖 手机壳',
  'nail'        => '💅 穿戴甲',
  'scent'       => '🌸 香片',
  'doll'        => '🧸 娃娃',
  'stationery'  => '✏️ 文具',
  'lowstock'    => '⚠️ 库存不足'
];

$cat = $_GET['cat'] ?? 'snack';

// ✅ 更新库存 / 预警值
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    if (isset($_POST['stock'])) {
        $stmt = $pdo->prepare("UPDATE products SET stock=? WHERE id=?");
        $stmt->execute([intval($_POST['stock']), $id]);
    }
    if (isset($_POST['warning_level'])) {
        $stmt = $pdo->prepare("UPDATE products SET warning_level=? WHERE id=?");
        $stmt->execute([intval($_POST['warning_level']), $id]);
    }
    header("Location: inventory.php?cat=$cat&msg=" . urlencode("✅ 更新成功"));
    exit;
}

// ✅ 查询商品
if ($cat === 'lowstock') {
    $sql = "SELECT id, sku, name, category, stock, warning_level 
            FROM products 
            WHERE stock < warning_level 
            ORDER BY category, id DESC";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT id, sku, name, category, stock, warning_level 
                           FROM products 
                           WHERE category = ?
                           ORDER BY id DESC");
    $stmt->execute([$cat]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>库存管理 | Qii.shoppp</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Inter', 'Noto Sans SC', sans-serif;
      background: #f7f8fb;
      color: #2c3e50;
    }

    .main-content {
      margin-left: 230px;
      padding: 30px;
    }

    h2 {
      font-weight: 600;
      color: #111;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .category-nav {
      margin: 15px 0;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .category-nav a {
      padding: 6px 12px;
      border: 1px solid #444;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      color: #333;
      transition: all .2s ease;
      background: #fff;
    }

    .category-nav a:hover {
      background: #1e1f26;
      color: #fff;
    }

    .category-nav a.active {
      background: #1e1f26;
      color: #fff;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    th, td {
      padding: 12px;
      border-bottom: 1px solid #eee;
      text-align: center;
    }

    th {
      background: #1e1f26;
      color: #fff;
    }

    tr:nth-child(even) td {
      background: #f8f9fb;
    }

    .btn-update {
      background: #4b6cb7;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
    }

    .btn-update:hover {
      background: #3a56a1;
    }

    .msg {
      background: #e8f5e9;
      color: #2e7d32;
      padding: 10px 12px;
      border-radius: 6px;
      margin-bottom: 10px;
      font-size: 14px;
    }

    @media (max-width:768px){
      .main-content{margin-left:0;padding:15px;}
      th,td{font-size:13px;padding:8px;}
    }
  </style>
</head>
<body>

<?php include 'includes/admin_header.php'; ?> <!-- ✅ 从统一文件引入黑灰侧栏 -->

<div class="main-content">
  <h2><i class="fa-solid fa-boxes-stacked"></i> 库存管理</h2>

  <div class="category-nav">
    <?php foreach($categories as $key => $label): ?>
      <a href="inventory.php?cat=<?= $key ?>" class="<?= $cat===$key?'active':'' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <table>
    <tr>
      <th>ID</th>
      <th>SKU</th>
      <th>商品名</th>
      <th>分类</th>
      <th>库存</th>
      <th>预警值</th>
      <th>操作</th>
    </tr>
    <?php foreach($products as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['sku']) ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= isset($categories[$p['category']]) ? $categories[$p['category']] : $p['category'] ?></td>
        <td>
          <form method="post" style="display:flex;justify-content:center;gap:6px;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="number" name="stock" value="<?= $p['stock'] ?>" style="width:80px;">
            <button type="submit" class="btn-update">💾</button>
          </form>
        </td>
        <td>
          <form method="post" style="display:flex;justify-content:center;gap:6px;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="number" name="warning_level" value="<?= $p['warning_level'] ?>" style="width:80px;">
            <button type="submit" class="btn-update">⚙️</button>
          </form>
        </td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="stock" value="<?= $p['stock']+1 ?>">
            <button type="submit" class="btn-update">➕</button>
          </form>
          <form method="post" style="display:inline;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="stock" value="<?= max(0, $p['stock']-1) ?>">
            <button type="submit" class="btn-update">➖</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

</body>
</html>