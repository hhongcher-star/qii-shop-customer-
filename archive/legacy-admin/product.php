<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$categories = [
  'phone' => '📱 手机配件',
  'hair' => '🎀 发夹发饰',
  'snack' => '🍭 零食',
  'creative' => '💗 文创',
  'case' => '💖 手机壳',
  'nail' => '💅 穿戴甲',
  'scent' => '🌸 香片',
  'doll' => '🧸 娃娃',
  'stationery' => '✏️ 文具'
];

$cat = $_GET['cat'] ?? array_key_first($categories);
if (!array_key_exists($cat, $categories)) {
  $cat = array_key_first($categories);
}

function redirectProducts($cat, $extra = '') {
  $url = "products.php?cat=" . urlencode($cat);
  if ($extra) $url .= "&" . $extra;
  header("Location: $url");
  exit;
}

function uploadImage($inputName) {
  if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }

  $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  $fileType = mime_content_type($_FILES[$inputName]['tmp_name']);

  if (!in_array($fileType, $allowedTypes)) return null;
  if ($_FILES[$inputName]['size'] > 2 * 1024 * 1024) return null;

  $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
  $filename = uniqid('img_', true) . "." . $ext;

  $targetDir = __DIR__ . "/../uploads/";
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  $target = $targetDir . $filename;

  if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $target)) {
    return "uploads/" . $filename;
  }

  return null;
}

/* 添加商品 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
  $sku = trim($_POST['sku'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $price = floatval($_POST['price'] ?? 0);
  $stock = intval($_POST['stock'] ?? 0);
  $category = $_POST['category'] ?? $cat;

  if (!array_key_exists($category, $categories)) {
    $category = $cat;
  }

  if ($sku !== '' && $name !== '') {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM products WHERE category = ?");
    $stmt->execute([$category]);
    $sortOrder = $stmt->fetchColumn();

    $imageUrl = uploadImage('image');

    $stmt = $pdo->prepare("
      INSERT INTO products 
      (sku, name, price, stock, category, image_url, sort_order, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$sku, $name, $price, $stock, $category, $imageUrl, $sortOrder]);

    redirectProducts($category, "msg=" . urlencode("✅ 商品已添加"));
  }
}

/* 添加规格组 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
  $productId = intval($_POST['product_id'] ?? 0);
  $groupName = trim($_POST['group_name'] ?? '');

  if ($productId > 0 && $groupName !== '') {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_groups WHERE product_id = ?");
    $stmt->execute([$productId]);
    $sortOrder = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
      INSERT INTO product_groups (product_id, group_name, sort_order)
      VALUES (?, ?, ?)
    ");
    $stmt->execute([$productId, $groupName, $sortOrder]);
  }

  redirectProducts($cat, "variants=" . $productId);
}

/* 添加子规格 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variant'])) {
  $groupId = intval($_POST['group_id'] ?? 0);
  $variantName = trim($_POST['variant_name'] ?? '');
  $price = ($_POST['price'] ?? '') !== '' ? floatval($_POST['price']) : null;
  $stock = intval($_POST['stock'] ?? 0);
  $imageUrl = uploadImage('variant_image');

  if ($groupId > 0 && $variantName !== '') {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_variants WHERE group_id = ?");
    $stmt->execute([$groupId]);
    $sortOrder = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
      INSERT INTO product_variants 
      (group_id, variant_name, price, stock, image_url, sort_order)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$groupId, $variantName, $price, $stock, $imageUrl, $sortOrder]);
  }

  $stmt = $pdo->prepare("SELECT product_id FROM product_groups WHERE id = ?");
  $stmt->execute([$groupId]);
  $productId = $stmt->fetchColumn();

  redirectProducts($cat, "variants=" . intval($productId));
}

/* 删除规格组 */
if (isset($_GET['delete_group'], $_GET['product_id'])) {
  $groupId = intval($_GET['delete_group']);
  $productId = intval($_GET['product_id']);

  $pdo->prepare("DELETE FROM product_groups WHERE id = ?")->execute([$groupId]);

  redirectProducts($cat, "variants=" . $productId);
}

/* 删除子规格 */
if (isset($_GET['delete_variant'], $_GET['product_id'])) {
  $variantId = intval($_GET['delete_variant']);
  $productId = intval($_GET['product_id']);

  $pdo->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$variantId]);

  redirectProducts($cat, "variants=" . $productId);
}

/* 删除商品 */
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);

  $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

  redirectProducts($cat, "msg=" . urlencode("❌ 商品已删除"));
}

/* 商品排序 */
if (isset($_GET['move'], $_GET['id'])) {
  $id = intval($_GET['id']);
  $move = $_GET['move'];

  $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->execute([$id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($product) {
    $currentOrder = intval($product['sort_order']);
    $category = $product['category'];

    if ($move === 'up') {
      $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE category = ? AND sort_order < ?
        ORDER BY sort_order DESC
        LIMIT 1
      ");
    } else {
      $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE category = ? AND sort_order > ?
        ORDER BY sort_order ASC
        LIMIT 1
      ");
    }

    $stmt->execute([$category, $currentOrder]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($target) {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ?")
          ->execute([$target['sort_order'], $product['id']]);
      $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ?")
          ->execute([$currentOrder, $target['id']]);
      $pdo->commit();
    }
  }

  redirectProducts($cat);
}

/* 整理排序 */
$stmt = $pdo->prepare("SELECT id FROM products WHERE category = ? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$cat]);
$productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($productIds as $index => $productId) {
  $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ?")
      ->execute([$index + 1, $productId]);
}

/* 查询商品 */
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY sort_order ASC, id DESC");
$stmt->execute([$cat]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';
$openVariantId = intval($_GET['variants'] ?? 0);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">

<?php require_once __DIR__ . '/includes/seo.php'; ?>
<?php qii_seo_meta([
  'title' => 'Product Management | qii.shoppp',
  'description' => 'Product management page for qii.shoppp.',
  'path' => '/products.php',
  'robots' => 'noindex, nofollow'
]); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
  --pink: #ff4fa3;
  --pink-soft: #fff1f8;
  --dark: #2d2340;
  --muted: #8f88a3;
  --border: #ffd4e8;
  --bg: #fff7fb;
  --card: #ffffff;
}

body {
  margin: 0;
  background: var(--bg);
  color: var(--dark);
  font-family: "Inter", "Noto Sans SC", sans-serif;
}

main {
  margin-left: 230px;
  padding: 28px;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.page-head h2 {
  margin: 0;
  font-size: 30px;
}

.category-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.category-nav a {
  padding: 9px 15px;
  border-radius: 999px;
  text-decoration: none;
  background: #fff;
  color: var(--dark);
  border: 1px solid var(--border);
  font-weight: 700;
}

.category-nav a.active {
  background: var(--pink);
  color: white;
}

.msg {
  background: #e8f8ee;
  color: #237a3b;
  padding: 12px 15px;
  border-radius: 14px;
  margin-bottom: 16px;
  font-weight: 700;
}

.add-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 22px;
  padding: 18px;
  margin-bottom: 22px;
  box-shadow: 0 12px 30px rgba(255, 79, 163, .08);
}

.add-form {
  display: grid;
  grid-template-columns: repeat(6, 1fr) auto;
  gap: 10px;
  align-items: center;
}

.add-form input,
.add-form select {
  padding: 11px 12px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: #fff;
  outline: none;
}

.table-wrapper {
  overflow-x: auto;
  background: #fff;
  border-radius: 22px;
  border: 1px solid var(--border);
  box-shadow: 0 12px 30px rgba(255, 79, 163, .08);
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

th {
  background: var(--pink-soft);
  color: var(--dark);
  padding: 14px 12px;
  font-size: 13px;
}

td {
  border-top: 1px solid #ffe0ef;
  padding: 13px 12px;
  text-align: center;
  vertical-align: middle;
}

tr:hover td {
  background: #fffafd;
}

.thumb {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 14px;
  border: 1px solid var(--border);
}

.btn {
  display: inline-block;
  padding: 7px 11px;
  border-radius: 10px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  margin: 2px;
}

.btn-primary {
  background: var(--pink);
  color: white;
}

.btn-blue {
  background: #4f7cff;
  color: white;
}

.btn-gray {
  background: #f2f2f5;
  color: var(--dark);
}

.btn-danger {
  background: #ff4d6d;
  color: white;
}

.variant-row td {
  background: #fff7fe !important;
  padding: 18px;
}

@media (max-width: 768px) {
  main {
    margin-left: 0;
    padding: 16px;
  }

  .page-head h2 {
    font-size: 24px;
  }

  .category-nav {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 8px;
  }

  .category-nav a {
    white-space: nowrap;
    font-size: 13px;
  }

  .add-form {
    grid-template-columns: 1fr;
  }

  table {
    min-width: 780px;
    font-size: 12px;
  }

  .btn {
    font-size: 11px;
    padding: 6px 8px;
  }

  .thumb {
    width: 44px;
    height: 44px;
  }
}
</style>
</head>

<body>

<?php include 'includes/admin_header.php'; ?>

<main>
  <div class="page-head">
    <h2>商品管理</h2>
  </div>

  <div class="category-nav">
    <?php foreach ($categories as $key => $label): ?>
      <a href="products.php?cat=<?= urlencode($key) ?>" class="<?= $cat === $key ? 'active' : '' ?>">
        <?= htmlspecialchars($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="add-card">
    <form method="post" enctype="multipart/form-data" class="add-form">
      <input type="hidden" name="add_product" value="1">

      <input type="text" name="sku" placeholder="SKU" required>
      <input type="text" name="name" placeholder="商品名" required>
      <input type="number" step="0.01" name="price" placeholder="价格" required>
      <input type="number" name="stock" placeholder="库存" required>

      <select name="category">
        <?php foreach ($categories as $key => $label): ?>
          <option value="<?= htmlspecialchars($key) ?>" <?= $cat === $key ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="file" name="image" accept="image/*">

      <button type="submit" class="btn btn-primary">➕ 添加</button>
    </form>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>SKU</th>
          <th>图片</th>
          <th>商品名</th>
          <th>价格</th>
          <th>库存</th>
          <th>排序</th>
          <th>操作</th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($products)): ?>
          <tr>
            <td colspan="8">暂无商品</td>
          </tr>
        <?php endif; ?>

        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= intval($p['id']) ?></td>
            <td><?= htmlspecialchars($p['sku']) ?></td>
            <td>
              <?php if (!empty($p['image_url'])): ?>
                <img src="../<?= htmlspecialchars($p['image_url']) ?>" class="thumb">
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td>RM <?= number_format(floatval($p['price']), 2) ?></td>
            <td><?= intval($p['stock']) ?></td>
            <td><?= intval($p['sort_order']) ?></td>
            <td>
              <a href="edit_product.php?id=<?= intval($p['id']) ?>" class="btn btn-blue">✏️ 编辑</a>

              <button type="button" class="btn btn-primary" onclick="toggleVariant(<?= intval($p['id']) ?>)">
                🎀 规格
              </button>

              <a href="products.php?cat=<?= urlencode($cat) ?>&move=up&id=<?= intval($p['id']) ?>" class="btn btn-gray">▲</a>
              <a href="products.php?cat=<?= urlencode($cat) ?>&move=down&id=<?= intval($p['id']) ?>" class="btn btn-gray">▼</a>

              <a href="products.php?cat=<?= urlencode($cat) ?>&delete=<?= intval($p['id']) ?>"
                 class="btn btn-danger"
                 onclick="return confirm('确定删除这个商品吗？')">
                🗑 删除
              </a>
            </td>
          </tr>

          <tr id="variants_box_<?= intval($p['id']) ?>" 
              class="variant-row"
              style="display: <?= $openVariantId === intval($p['id']) ? 'table-row' : 'none' ?>;">
            <td colspan="8">
              <?php include 'variant_box.php'; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
function toggleVariant(id) {
  const box = document.getElementById("variants_box_" + id);
  if (!box) return;

  box.style.display = box.style.display === "none" ? "table-row" : "none";
}
</script>

</body>
</html>