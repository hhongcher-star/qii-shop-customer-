<?php
require_once __DIR__ . "/config.php";
date_default_timezone_set("Asia/Kuala_Lumpur");

$variant_id = intval($_GET['id'] ?? 0);
$product_id = intval($_GET['product'] ?? 0);

if ($variant_id <= 0) die("Invalid Variant");

// 取资料
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id=? LIMIT 1");
$stmt->execute([$variant_id]);
$variant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$variant) die("Variant Not Found");

// 图片上传函数
function uploadVariantImage($inputName) {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== 0) return null;

    $allowed = ['image/jpeg','image/png','image/gif'];
    if (!in_array($_FILES[$inputName]['type'], $allowed)) return null;

    $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
    $newName = uniqid("var_") . "." . $ext;

    $dir = __DIR__ . "/uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $dir . $newName)) {
        return "uploads/" . $newName;
    }
    return null;
}

// =======================================
// 更新数据
// =======================================
if (isset($_POST["update_variant"])) {

    $new_name  = trim($_POST["variant_name"]);
    $new_price = $_POST["price"] !== "" ? floatval($_POST["price"]) : null;
    $new_stock = intval($_POST["stock"]);

    // 图片
    $new_img = uploadVariantImage("variant_image") ?: $variant["image_url"];

    $stmt = $pdo->prepare("
        UPDATE product_variants
        SET variant_name=?, price=?, stock=?, image_url=?
        WHERE id=?
    ");

    $stmt->execute([$new_name, $new_price, $new_stock, $new_img, $variant_id]);

    header("Location: variant_box.php?id=" . $product_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>编辑规格</title>
<style>
body { font-family:Arial; padding:20px; }
.box { background:#fff;padding:20px;border-radius:8px;border:1px solid #ddd;max-width:500px;margin:auto; }
input,button { padding:8px; margin:6px 0; width:100%; }
.btn { background:#007bff;color:#fff;border:none;border-radius:6px;font-size:16px; }
</style>
</head>

<body>

<h2>编辑规格：<?= htmlspecialchars($variant["variant_name"]) ?></h2>

<div class="box">
<form method="post" enctype="multipart/form-data">

    名称：
    <input type="text" name="variant_name" value="<?= htmlspecialchars($variant['variant_name']) ?>" required>

    覆盖价格：
    <input type="number" step="0.01" name="price" value="<?= $variant['price'] ?>">

    库存：
    <input type="number" name="stock" value="<?= $variant['stock'] ?>" required>

    图片：
    <?php if ($variant["image_url"]): ?>
      <br><img src="<?= $variant['image_url'] ?>" width="80"><br>
    <?php endif; ?>
    <input type="file" name="variant_image">

    <button class="btn" name="update_variant">保存修改</button>

</form>
</div>

</body>
</html>
