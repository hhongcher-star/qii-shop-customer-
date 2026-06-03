<?php
require_once __DIR__ . "/config.php";
date_default_timezone_set("Asia/Kuala_Lumpur");

$product_id = intval($_GET['id'] ?? 0);
if ($product_id <= 0) die("Invalid Product");

// =========================
// 获取商品
// =========================
$stmt = $pdo->prepare("SELECT name FROM products WHERE id=?");
$stmt->execute([$product_id]);
$productName = $stmt->fetchColumn();

if (!$productName) die("Product not found");

// =========================
// 图片上传函数（子规格）
// =========================
function uploadVariantImage($inputName) {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== 0) return null;

    $allowed = ['image/jpeg','image/png','image/gif'];
    if (!in_array($_FILES[$inputName]['type'], $allowed)) return null;

    if ($_FILES[$inputName]['size'] > 3 * 1024 * 1024) return null;

    $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
    $newName = uniqid("var_") . "." . $ext;

    $dir = __DIR__ . "/uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $target = $dir . $newName;

    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $target))
        return "uploads/" . $newName;

    return null;
}


// ------------------------------------------------------------
// 1️⃣ 添加规格组
// ------------------------------------------------------------
if (isset($_POST['add_group'])) {
    $groupName = trim($_POST['group_name']);

    // sort order
    $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM product_groups WHERE product_id=?");
    $stmt->execute([$product_id]);
    $max = $stmt->fetchColumn();
    $sort = $max ? $max + 1 : 1;

    $stmt = $pdo->prepare("
        INSERT INTO product_groups (product_id, group_name, sort_order, created_at)
        VALUES (?,?,?,NOW())
    ");
    $stmt->execute([$product_id, $groupName, $sort]);

    header("Location: variant_box.php?id=$product_id");
    exit;
}


// ------------------------------------------------------------
// 2️⃣ 删除规格组（并删除子规格）
// ------------------------------------------------------------
if (isset($_GET['delete_group'])) {
    $gid = intval($_GET['delete_group']);

    $pdo->prepare("DELETE FROM product_variants WHERE group_id=?")->execute([$gid]);
    $pdo->prepare("DELETE FROM product_groups WHERE id=?")->execute([$gid]);

    header("Location: variant_box.php?id=$product_id");
    exit;
}


// ------------------------------------------------------------
// 3️⃣ 添加子规格
// ------------------------------------------------------------
if (isset($_POST['add_variant'])) {
    $group_id = intval($_POST['group_id']);
    $variant_name = trim($_POST['variant_name']);
    $price = $_POST['price'] !== "" ? floatval($_POST['price']) : null;
    $stock = intval($_POST['stock']);

    $image_url = uploadVariantImage('variant_image');

    // sort order
    $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM product_variants WHERE group_id=?");
    $stmt->execute([$group_id]);
    $max = $stmt->fetchColumn();
    $sort = $max ? $max + 1 : 1;

    $stmt = $pdo->prepare("
        INSERT INTO product_variants (group_id, variant_name, price, stock, image_url, sort_order, created_at)
        VALUES (?,?,?,?,?,?,NOW())
    ");
    $stmt->execute([$group_id, $variant_name, $price, $stock, $image_url, $sort]);

    header("Location: variant_box.php?id=$product_id");
    exit;
}


// ------------------------------------------------------------
// 4️⃣ 删除子规格
// ------------------------------------------------------------
if (isset($_GET['delete_variant'])) {
    $vid = intval($_GET['delete_variant']);
    $pdo->prepare("DELETE FROM product_variants WHERE id=?")->execute([$vid]);

    header("Location: variant_box.php?id=$product_id");
    exit;
}


// ------------------------------------------------------------
// 5️⃣ 排序（规格组）
// ------------------------------------------------------------
if (isset($_GET['move_group'], $_GET['group_id'])) {
    $gid = intval($_GET['group_id']);
    $movement = $_GET['move_group'];

    // 当前组
    $stmt = $pdo->prepare("SELECT * FROM product_groups WHERE id=?");
    $stmt->execute([$gid]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($g) {
        $current = $g['sort_order'];

        if ($movement == 'up') {
            $stmt = $pdo->prepare("
                SELECT * FROM product_groups 
                WHERE product_id=? AND sort_order < ? 
                ORDER BY sort_order DESC LIMIT 1
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM product_groups 
                WHERE product_id=? AND sort_order > ? 
                ORDER BY sort_order ASC LIMIT 1
            ");
        }
        $stmt->execute([$product_id, $current]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE product_groups SET sort_order=? WHERE id=?")
                ->execute([$target['sort_order'], $gid]);
            $pdo->prepare("UPDATE product_groups SET sort_order=? WHERE id=?")
                ->execute([$current, $target['id']]);
            $pdo->commit();
        }
    }
    header("Location: variant_box.php?id=$product_id");
    exit;
}


// ------------------------------------------------------------
// 6️⃣ 排序（子规格）
// ------------------------------------------------------------
if (isset($_GET['move_variant'], $_GET['variant_id'])) {
    $vid = intval($_GET['variant_id']);
    $movement = $_GET['move_variant'];

    // 当前 variant
    $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id=?");
    $stmt->execute([$vid]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($v) {
        $gid = $v['group_id'];
        $current = $v['sort_order'];

        if ($movement == 'up') {
            $stmt = $pdo->prepare("
                SELECT * FROM product_variants 
                WHERE group_id=? AND sort_order < ?
                ORDER BY sort_order DESC LIMIT 1
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM product_variants 
                WHERE group_id=? AND sort_order > ?
                ORDER BY sort_order ASC LIMIT 1
            ");
        }

        $stmt->execute([$gid, $current]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE product_variants SET sort_order=? WHERE id=?")
                ->execute([$target['sort_order'], $vid]);
            $pdo->prepare("UPDATE product_variants SET sort_order=? WHERE id=?")
                ->execute([$current, $target['id']]);
            $pdo->commit();
        }
    }

    header("Location: variant_box.php?id=$product_id");
    exit;
}


// ==========================
// 读取所有组与项
// ==========================
$stmt = $pdo->prepare("SELECT * FROM product_groups WHERE product_id=? ORDER BY sort_order ASC");
$stmt->execute([$product_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>规格管理</title>
<style>
body { font-family:Arial;background:#f7f7f7;margin:0;padding:20px; }
.box { background:#fff;padding:15px;border-radius:8px;border:1px solid #ddd; }
.btn { padding:4px 8px;border-radius:4px;text-decoration:none;margin-left:5px; }
.btn-edit { background:#007bff;color:#fff; }
.btn-delete { background:#dc3545;color:#fff; }
.btn-move { background:#eee;border:1px solid #aaa;color:#333; }
</style>
</head>
<body>
<a href="products.php" 
   style="
     display:inline-block;
     margin-bottom:15px;
     padding:8px 14px;
     background:#007bff;
     color:#fff;
     text-decoration:none;
     border-radius:6px;
     font-size:14px;">
  ⬅ 返回商品列表
</a>
<h2>🎀 规格管理：<?= htmlspecialchars($productName) ?></h2>

<!-- 添加规格组 -->
<div class="box">
<form method="post">
  <input type="hidden" name="add_group" value="1">
  <input type="text" name="group_name" placeholder="规格组名（如 Color）" required>
  <button class="btn btn-edit">➕ 添加规格组</button>
</form>
</div>
<br>

<?php foreach ($groups as $g): ?>
<div class="box" style="margin-bottom:15px;">
  <h3>
    <?= htmlspecialchars($g['group_name']) ?>

    <a href="?id=<?= $product_id ?>&move_group=up&group_id=<?= $g['id'] ?>" class="btn btn-move">⬆</a>
    <a href="?id=<?= $product_id ?>&move_group=down&group_id=<?= $g['id'] ?>" class="btn btn-move">⬇</a>
    <a href="?id=<?= $product_id ?>&delete_group=<?= $g['id'] ?>" class="btn btn-delete">删除组</a>
  </h3>

  <!-- 添加子规格 -->
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="add_variant" value="1">
    <input type="hidden" name="group_id" value="<?= $g['id'] ?>">

    <input type="text" name="variant_name" placeholder="项名（如 Purple）" required>
    <input type="number" step="0.01" name="price" placeholder="覆盖价格（可空）">
    <input type="number" name="stock" placeholder="库存">
    <input type="file" name="variant_image">

    <button class="btn btn-edit">➕ 添加项</button>
  </form>

  <hr>

  <?php
    $stmt2 = $pdo->prepare("SELECT * FROM product_variants WHERE group_id=? ORDER BY sort_order ASC");
    $stmt2->execute([$g['id']]);
    $variants = $stmt2->fetchAll(PDO::FETCH_ASSOC);
  ?>

  <?php foreach ($variants as $v): ?>
    <div style="padding:5px;border-bottom:1px dashed #ddd;">
      <?= htmlspecialchars($v['variant_name']) ?>  
      <?php if ($v['image_url']): ?>
        <img src="<?= $v['image_url'] ?>" width="30" style="margin-left:10px;border-radius:4px;">
      <?php endif; ?>

      （库存 <?= $v['stock'] ?>）

      <a href="?id=<?= $product_id ?>&move_variant=up&variant_id=<?= $v['id'] ?>" class="btn btn-move" style="float:right;">⬆</a>
      <a href="?id=<?= $product_id ?>&move_variant=down&variant_id=<?= $v['id'] ?>" class="btn btn-move" style="float:right;">⬇</a>

      <!-- ⭐ 新增编辑按钮 -->
      <a href="edit_variant.php?id=<?= $v['id'] ?>&product=<?= $product_id ?>"
         class="btn btn-edit" style="float:right;margin-right:8px;">编辑</a>

      <a href="?id=<?= $product_id ?>&delete_variant=<?= $v['id'] ?>" 
         class="btn btn-delete" style="float:right;margin-right:8px;">删除</a>

      <div style="clear:both;"></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

</body>
</html>
