<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

// ✅ Qii.shop 分类
$categories = [
  'phone'   => '📱 手机配件',
  'hair'    => '🎀 发夹发饰',
  'snack'   => '🍭 零食',
  'creative'=> '💗 文创',
  'case'    => '💖 手机壳',
  'nail'    => '💅 穿戴甲',
  'scent'   => '🌸 香片',
  'doll'    => '🧸 娃娃',
  'stationery' => '✏️ 文具'
];
$cat = $_GET['cat'] ?? array_key_first($categories);

// ===================================
// 图片上传函数
// ===================================
function uploadImage($fileInput) {
  if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
      $allowed = ['image/jpeg','image/png','image/gif'];
      if (!in_array($_FILES[$fileInput]['type'], $allowed)) return null;
      if ($_FILES[$fileInput]['size'] > 2*1024*1024) return null;
      $ext = strtolower(pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION));
      $filename = uniqid().".".$ext;
      $targetDir = __DIR__."/../uploads/";
      if (!is_dir($targetDir)) mkdir($targetDir,0777,true);
      $target = $targetDir.$filename;
      if (move_uploaded_file($_FILES[$fileInput]['tmp_name'],$target))
        return "uploads/".$filename;
  }
  return null;
}

// ===================================
// 添加商品
// ===================================
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_product'])) {
  $sku=$_POST['sku'];
  $name=$_POST['name'];
  $price=floatval($_POST['price']);
  $stock=intval($_POST['stock']);
  $category=$_POST['category'];
  if (!array_key_exists($category,$categories)) $category=array_key_first($categories);

  $stmt=$pdo->prepare("SELECT MAX(sort_order) FROM products WHERE category=?");
  $stmt->execute([$category]);
  $max_sort=$stmt->fetchColumn();
  $sort_order=$max_sort!==null ? $max_sort+1 : 1;

  $image_url=uploadImage('image');
  $stmt=$pdo->prepare("INSERT INTO products (sku,name,price,stock,category,image_url,sort_order,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
  $stmt->execute([$sku,$name,$price,$stock,$category,$image_url,$sort_order]);

  header("Location: products.php?cat=$category&msg=".urlencode("✅ 商品已添加"));
  exit;
}

// ===================================
// 添加规格组
// ===================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {

  $product_id = intval($_POST['product_id']);
  $group_name = trim($_POST['group_name']);

  if ($group_name !== '') {
      // sort_order
      $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM product_groups WHERE product_id=?");
      $stmt->execute([$product_id]);
      $max = $stmt->fetchColumn();
      $sort_order = $max ? $max + 1 : 1;

      $stmt = $pdo->prepare("INSERT INTO product_groups (product_id, group_name, sort_order) VALUES (?, ?, ?)");
      $stmt->execute([$product_id, $group_name, $sort_order]);
  }

  header("Location: products.php?cat=$cat&variants=$product_id");
  exit;
}

// ===================================
// 添加子规格项
// ===================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variant'])) {

  $group_id = intval($_POST['group_id']);
  $variant_name = trim($_POST['variant_name']);
  $price = $_POST['price'] !== '' ? floatval($_POST['price']) : null;
  $stock = intval($_POST['stock']);
  $image_url = null;

  // 上传图片
  if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES['variant_image']['name'], PATHINFO_EXTENSION));
      $filename = uniqid().".".$ext;
      $dir = __DIR__."/../uploads/";
      if (!is_dir($dir)) mkdir($dir, 0777, true);
      move_uploaded_file($_FILES['variant_image']['tmp_name'], $dir.$filename);
      $image_url = "uploads/".$filename;
  }

  // sort_order
  $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM product_variants WHERE group_id=?");
  $stmt->execute([$group_id]);
  $max = $stmt->fetchColumn();
  $sort_order = $max ? $max + 1 : 1;

  // 插入
  $stmt = $pdo->prepare("INSERT INTO product_variants (group_id, variant_name, price, stock, image_url, sort_order)
                         VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$group_id, $variant_name, $price, $stock, $image_url, $sort_order]);

  // 查 product_id 回去继续展开页面
  $stmt = $pdo->prepare("SELECT product_id FROM product_groups WHERE id=? LIMIT 1");
  $stmt->execute([$group_id]);
  $product_id = $stmt->fetchColumn();

  header("Location: products.php?cat=$cat&variants=$product_id");
  exit;
}

// ===================================
// 删除规格组
// ===================================
if (isset($_GET['delete_group'], $_GET['product_id'])) {
  $gid = intval($_GET['delete_group']);
  $pid = intval($_GET['product_id']);

  $pdo->prepare("DELETE FROM product_groups WHERE id=?")->execute([$gid]);

  header("Location: products.php?cat=$cat&variants=$pid");
  exit;
}

// ===================================
// — 删除子规格项
// ===================================
if (isset($_GET['delete_variant'], $_GET['product_id'])) {
  $vid = intval($_GET['delete_variant']);
  $pid = intval($_GET['product_id']);

  $pdo->prepare("DELETE FROM product_variants WHERE id=?")->execute([$vid]);

  header("Location: products.php?cat=$cat&variants=$pid");
  exit;
}

// ===================================
// 删除商品
// ===================================
if (isset($_GET['delete'])) {
  $id=intval($_GET['delete']);
  $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
  header("Location: product.php?cat=$cat&msg=".urlencode("❌ 商品已删除"));
  exit;
}

// ===================================
// 上下移动排序
// ===================================
if (isset($_GET['move'],$_GET['id'])) {
  $id=intval($_GET['id']); $move=$_GET['move'];

  $stmt=$pdo->prepare("SELECT * FROM products WHERE id=?");
  $stmt->execute([$id]);
  $p=$stmt->fetch(PDO::FETCH_ASSOC);

  if ($p) {
    $current=(int)$p['sort_order'];
    $category=$p['category'];
    if ($move==='up') {
      $stmt=$pdo->prepare("SELECT * FROM products WHERE category=? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1");
      $stmt->execute([$category,$current]);
    } else {
      $stmt=$pdo->prepare("SELECT * FROM products WHERE category=? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1");
      $stmt->execute([$category,$current]);
    }
    $target=$stmt->fetch(PDO::FETCH_ASSOC);
    if ($target) {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE products SET sort_order=? WHERE id=?")->execute([$target['sort_order'],$p['id']]);
      $pdo->prepare("UPDATE products SET sort_order=? WHERE id=?")->execute([$current,$target['id']]);
      $pdo->commit();
    }
  }
  header("Location: product.php?cat=$cat");
  exit;
}

// ===================================
// 重新整理排序
// ===================================
$stmt=$pdo->prepare("SELECT id FROM products WHERE category=? ORDER BY sort_order ASC,id ASC");
$stmt->execute([$cat]);
$ids=$stmt->fetchAll(PDO::FETCH_COLUMN);
foreach($ids as $i=>$pid){
  $pdo->prepare("UPDATE products SET sort_order=? WHERE id=?")->execute([$i+1,$pid]);
}

// ===================================
// 查询商品
// ===================================
$stmt=$pdo->prepare("SELECT * FROM products WHERE category=? ORDER BY sort_order ASC,id DESC");
$stmt->execute([$cat]);
$products=$stmt->fetchAll(PDO::FETCH_ASSOC);
$msg=$_GET['msg']??'';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>商品管理 | Qii.shop</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { font-family:"Inter","Noto Sans SC",sans-serif;margin:0;background:#f8f9fb;color:#333; }
main{margin-left:230px;padding:25px;}

/* 🧁 分类导航 */
.category-nav{margin:10px 0;display:flex;flex-wrap:wrap;gap:8px;}
.category-nav a{padding:8px 14px;border:1px solid #000;border-radius:6px;text-decoration:none;color:#333;background:#fff;transition:.2s;}
.category-nav a.active{background:#000;color:#fff;}

/* 🧺 表格区 */
.table-wrapper{overflow-x:auto;margin-top:15px;}
table{width:100%;border-collapse:collapse;min-width:720px;background:#fff;}
th,td{border:1px solid #ccc;padding:10px;text-align:center;}
th{background:#f2f2f2;}
tr:hover{background:#f9f9f9;}
.thumb{width:50px;height:50px;object-fit:cover;border-radius:4px;}

/* 按钮 */
.btn{padding:5px 10px;border-radius:4px;margin:2px;text-decoration:none;display:inline-block;font-size:13px;}
.btn-edit{background:#007bff;color:#fff;border:none;}
.btn-edit:hover{background:#0056b3;}
.btn-move{background:#eee;color:#333;border:1px solid #999;}
.btn-move:hover{background:#ddd;}
.btn-delete{background:#dc3545;color:#fff;border:none;}
.btn-delete:hover{background:#b02a37;}
.msg{padding:8px;margin:10px 0;background:#e8f5e9;color:#2e7d32;border-radius:6px;}
form.add-form input,form.add-form select{padding:6px;margin:3px;border:1px solid #ccc;border-radius:4px;}

/* 📱 手机版优化 */
@media (max-width: 768px) {
  main {
    margin-left: 0;
    padding: 15px;
  }

  .category-nav {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 8px;
  }
  .category-nav a {
    display: inline-block;
    font-size: 13px;
    padding: 6px 10px;
    border-radius: 4px;
    margin-right: 10px;
  }

  .add-form input,
  .add-form select,
  .add-form button {
    width: 100%;
    margin-bottom: 10px;
  }

  table {
    font-size: 12px;
    min-width: unset;
  }

  th,
  td {
    padding: 6px;
  }

  .btn {
    font-size: 11px;
    padding: 4px 7px;
    margin: 1px;
  }

  .thumb {
    width: 38px;
    height: 38px;
  }

  /* 表格滑动支持 */
  .table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
}
</style>
</head>
<body>

<?php include 'includes/admin_header.php'; ?> <!-- 🧊 后台侧栏 -->

<main>
  <h2>商品列表</h2>

  <!-- 分类导航 -->
  <div class="category-nav">
    <?php foreach($categories as $key=>$label): ?>
      <a href="?cat=<?= $key ?>" class="<?= $cat===$key?'active':'' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- 添加商品 -->
  <form method="post" enctype="multipart/form-data" class="add-form">
    <input type="hidden" name="add_product" value="1">
    <input type="text" name="sku" placeholder="SKU" required>
    <input type="text" name="name" placeholder="商品名" required>
    <input type="number" step="0.01" name="price" placeholder="价格" required>
    <input type="number" name="stock" placeholder="库存" required>
    <select name="category">
      <?php foreach($categories as $key=>$label): ?>
        <option value="<?= $key ?>" <?= $cat===$key?'selected':'' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <input type="file" name="image">
    <button type="submit" class="btn btn-edit">➕ 添加</button>
  </form>

  <!-- 商品表格 -->
  <div class="table-wrapper">
    <table>
      <tr><th>ID</th><th>SKU</th><th>图片</th><th>商品名</th><th>价格</th><th>库存</th><th>排序</th><th>操作</th></tr>
      <?php foreach($products as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['sku']) ?></td>
        <td><?php if($p['image_url']): ?><img src="../<?= $p['image_url'] ?>" class="thumb"><?php endif; ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td>RM <?= number_format($p['price'],2) ?></td>
        <td><?= $p['stock'] ?></td>
        <td><?= $p['sort_order'] ?></td>
        <td>
  <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-edit">✏️ 编辑</a>

  <button type="button" class="btn btn-edit" onclick="toggleVariant(<?= $p['id'] ?>)">🎀 规格</button>

  <a href="products.php?cat=<?= $cat ?>&move=up&id=<?= $p['id'] ?>" class="btn btn-move">⬆</a>
  <a href="products.php?cat=<?= $cat ?>&move=down&id=<?= $p['id'] ?>" class="btn btn-move">⬇</a>

  <a href="products.php?cat=<?= $cat ?>&delete=<?= $p['id'] ?>" 
     class="btn btn-delete" 
     onclick="return confirm('确定删除?')">🗑 删除</a>
</td>
      </tr>
      <tr id="variants_box_<?= $p['id'] ?>" style="display:none;">
        <td colspan="8" style="background:#fff7fe; padding:15px;">
            
            <?php include 'variant_box.php'; ?>

        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
<script>
function toggleVariant(id) {
    let box = document.getElementById("variants_box_" + id);
    box.style.display = (box.style.display === "none") ? "table-row" : "none";
}
</script>
</body>
</html>





