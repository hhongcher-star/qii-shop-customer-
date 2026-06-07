<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/categories.php';
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

$categoryRows = qii_categories($pdo);
$categories = array_map(fn($row) => $row['name'], $categoryRows);

function ensure_product_admin_columns(PDO $pdo): void {
    $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('brand', $columns, true)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN brand VARCHAR(120) NULL AFTER category");
    }
    if (!in_array('status', $columns, true)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active' AFTER brand");
    }
    $variantColumns = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sku', $variantColumns, true)) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN sku VARCHAR(100) NULL AFTER variant_name");
    }
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

function upload_admin_image(string $field, ?string $existing = null): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $type = mime_content_type($_FILES[$field]['tmp_name']);
    if (!isset($allowed[$type]) || $_FILES[$field]['size'] > 3 * 1024 * 1024 || getimagesize($_FILES[$field]['tmp_name']) === false) {
        return $existing;
    }
    $dir = dirname(__DIR__) . '/images/products';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = uniqid('product_', true) . '.' . $allowed[$type];
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $filename)) {
        return 'images/products/' . $filename;
    }
    return $existing;
}

function asset_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '../images/logo.png';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    return '../' . ltrim($path, '/');
}

function has_uploaded_file(string $field): bool {
    return !empty($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
}

ensure_product_admin_columns($pdo);

$id = (int)($_GET['id'] ?? 0);
$product = [
    'id' => 0,
    'sku' => '',
    'name' => '',
    'price' => '0.00',
    'stock' => 0,
    'warning_level' => 5,
    'category' => 'phone',
    'brand' => '',
    'status' => 'active',
    'image_url' => '',
];
$variants = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($found) $product = array_merge($product, $found);

    $stmt = $pdo->prepare("
        SELECT v.*
        FROM product_variants v
        INNER JOIN product_groups g ON g.id = v.group_id
        WHERE g.product_id=?
        ORDER BY v.sort_order ASC, v.id ASC
    ");
    $stmt->execute([$id]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$variants) {
    $variants = [[
        'id' => 0,
        'variant_name' => '',
        'sku' => $product['sku'] ? $product['sku'] . '-01' : '',
        'price' => $product['price'] ?? '0.00',
        'stock' => $product['stock'] ?? 0,
        'image_url' => '',
    ]];
}

$initialProductType = ((int)($product['has_variant'] ?? 0) === 1 || ($id > 0 && count($variants) > 1)) ? 'variant' : 'single';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? 'phone';
    if (!isset($categories[$category])) $category = 'phone';
    $brand = trim($_POST['brand'] ?? '');
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $productType = ($_POST['product_type'] ?? 'single') === 'variant' ? 'variant' : 'single';
    $singleSku = trim((string)($_POST['single_sku'] ?? ''));
    $singlePrice = trim((string)($_POST['single_price'] ?? '0.00'));
    $singleStock = trim((string)($_POST['single_stock'] ?? '0'));

    $currentImage = $_POST['current_image'] ?? '';
    $imageUrl = upload_admin_image('image', $currentImage);

    $variantNames = $_POST['variant_name'] ?? [];
    $variantSkus = $_POST['variant_sku'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $variantStocks = $_POST['variant_stock'] ?? [];
    $variantExistingImages = $_POST['variant_existing_image'] ?? [];

    $errors = [];
    if ($name === '') {
        $errors[] = '商品名称不能为空';
    }
    if ($currentImage === '' && !has_uploaded_file('image')) {
        $errors[] = '商品主图必须上传';
    }

    $cleanVariants = [];
    if ($productType === 'single') {
        if ($singleSku === '') $errors[] = '单一商品必须填写 SKU';
        if ($singlePrice === '' || !is_numeric($singlePrice) || (float)$singlePrice < 0) $errors[] = '单一商品价格不正确';
        if ($singleStock === '' || !is_numeric($singleStock) || (int)$singleStock < 0) $errors[] = '单一商品库存不正确';
    } else {
    $variantCount = max(count($variantNames), count($variantSkus), count($variantPrices), count($variantStocks), count($variantExistingImages));
    for ($i = 0; $i < $variantCount; $i++) {
        $variantName = trim((string)($variantNames[$i] ?? ''));
        $variantSku = trim((string)($variantSkus[$i] ?? ''));
        $variantPrice = trim((string)($variantPrices[$i] ?? ''));
        $variantStock = trim((string)($variantStocks[$i] ?? ''));
        $variantImage = trim((string)($variantExistingImages[$i] ?? ''));
        $field = 'variant_image_' . $i;
        $hasAnyInput = $variantName !== '' || $variantSku !== '' || $variantPrice !== '' || $variantStock !== '' || $variantImage !== '' || has_uploaded_file($field);
        if (!$hasAnyInput) continue;

        if ($variantName === '') $errors[] = '第 ' . ($i + 1) . ' 个规格缺少名称';
        if ($variantSku === '') $errors[] = '第 ' . ($i + 1) . ' 个规格缺少 SKU';
        if ($variantPrice === '' || !is_numeric($variantPrice) || (float)$variantPrice < 0) $errors[] = '第 ' . ($i + 1) . ' 个规格价格不正确';
        if ($variantStock === '' || !is_numeric($variantStock) || (int)$variantStock < 0) $errors[] = '第 ' . ($i + 1) . ' 个规格库存不正确';
        if ($variantImage === '' && !has_uploaded_file($field)) $errors[] = '第 ' . ($i + 1) . ' 个规格必须上传图片';

        $cleanVariants[] = [
            'index' => $i,
            'name' => $variantName,
            'sku' => $variantSku,
            'price' => (float)$variantPrice,
            'stock' => (int)$variantStock,
            'image' => $variantImage,
            'field' => $field,
        ];
    }
    if (!$cleanVariants) {
        $errors[] = '至少需要一个完整规格';
    }

    }

    if ($errors) {
        $error = implode('；', $errors);
    } else {

    $firstPrice = $productType === 'single' ? (float)$singlePrice : $cleanVariants[0]['price'];
    $totalStock = $productType === 'single' ? (int)$singleStock : array_sum(array_column($cleanVariants, 'stock'));
    $baseSku = $productType === 'single' ? $singleSku : $cleanVariants[0]['sku'];
    $hasVariant = $productType === 'variant' ? 1 : 0;

    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET sku=?, name=?, price=?, stock=?, category=?, brand=?, status=?, image_url=?, has_variant=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$baseSku, $name, $firstPrice, $totalStock, $category, $brand, $status, $imageUrl, $hasVariant, $id]);
        } else {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0) FROM products WHERE category=?");
            $stmt->execute([$category]);
            $sortOrder = (int)$stmt->fetchColumn() + 1;
            $stmt = $pdo->prepare("
                INSERT INTO products (sku, name, price, stock, warning_level, category, brand, status, image_url, has_variant, sort_order, created_at)
                VALUES (?, ?, ?, ?, 5, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$baseSku, $name, $firstPrice, $totalStock, $category, $brand, $status, $imageUrl, $hasVariant, $sortOrder]);
            $id = (int)$pdo->lastInsertId();
        }

        $pdo->prepare("DELETE v FROM product_variants v INNER JOIN product_groups g ON g.id=v.group_id WHERE g.product_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_groups WHERE product_id=?")->execute([$id]);
        if ($productType === 'variant') {
        $stmt = $pdo->prepare("INSERT INTO product_groups (product_id, group_name, sort_order) VALUES (?, '规格', 1)");
        $stmt->execute([$id]);
        $groupId = (int)$pdo->lastInsertId();

        foreach ($cleanVariants as $variant) {
            $variantImage = upload_admin_image($variant['field'], $variant['image']);
            $stmt = $pdo->prepare("
                INSERT INTO product_variants (group_id, variant_name, sku, price, stock, image_url, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $groupId,
                $variant['name'],
                $variant['sku'],
                $variant['price'],
                $variant['stock'],
                $variantImage,
                $variant['index'] + 1,
            ]);
        }

        }

        $pdo->commit();
        header('Location: product_editor.php?id=' . $id . '&saved=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = '保存失败：' . $e->getMessage();
    }
}
}

$isEdit = (int)$product['id'] > 0;
$selectedProductType = ($_POST['product_type'] ?? $initialProductType) === 'variant' ? 'variant' : 'single';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? '编辑商品' : '新增商品' ?> | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/product_admin.css?v=20260604">
  <style>
    .product-type-card { margin-bottom: 24px; }
    .single-product-fields { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .single-product-fields[hidden] { display: none !important; }
    .variant-save-row { display: flex; justify-content: flex-end; margin-top: 18px; }
    .variant-save-row .save-action { min-width: 180px; }
    .variant-field { grid-column: 3; display: grid; grid-template-columns: 110px minmax(0, 1fr); align-items: center; gap: 12px; }
    .variant-field span { color: #746982; font-weight: 900; font-size: 14px; }
    .variant-field input { width: 100%; }
    .variant-section-hidden { display: none !important; }
    @media (max-width: 760px) {
      .single-product-fields { grid-template-columns: 1fr; }
      .variant-field { grid-template-columns: 82px minmax(0, 1fr); }
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main product-editor-page">
  <header class="editor-topbar">
    <a href="product.php" class="back-link"><i class="fa-solid fa-arrow-left"></i></a>
    <div>
      <h1><?= $isEdit ? '编辑商品' : '新增商品' ?> ✨</h1>
      <p>商品管理 <i class="fa-solid fa-chevron-right"></i> <?= $isEdit ? '编辑商品' : '新增商品' ?></p>
    </div>
  </header>

  <?php if (!empty($error)): ?><div class="editor-alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (isset($_GET['saved'])): ?><div class="editor-alert success">商品已保存</div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="productEditorForm" data-product-form data-product-type-value="<?= htmlspecialchars($selectedProductType) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
    <input type="hidden" name="current_image" value="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
    <section class="editor-card product-type-card">
      <h2><i class="fa-solid fa-box"></i> 商品形式</h2>
      <div class="form-grid">
        <label class="field">
          <span>商品类型 <b>*</b></span>
          <select name="product_type" data-product-type>
            <option value="single" <?= $selectedProductType === 'single' ? 'selected' : '' ?>>单一商品</option>
            <option value="variant" <?= $selectedProductType === 'variant' ? 'selected' : '' ?>>有规格商品</option>
          </select>
        </label>
        <div class="single-product-fields" data-single-fields>
          <label class="field">
            <span>SKU <b>*</b></span>
            <input data-preview-variant name="single_sku" value="<?= htmlspecialchars($_POST['single_sku'] ?? ($product['sku'] ?? '')) ?>" placeholder="例如 A001">
          </label>
          <label class="field">
            <span>价格 (RM) <b>*</b></span>
            <input data-preview-price name="single_price" type="number" step="0.01" value="<?= htmlspecialchars($_POST['single_price'] ?? ($product['price'] ?? '0.00')) ?>" placeholder="0.00">
          </label>
          <label class="field">
            <span>库存 <b>*</b></span>
            <input name="single_stock" type="number" value="<?= htmlspecialchars($_POST['single_stock'] ?? ($product['stock'] ?? 0)) ?>" placeholder="0">
          </label>
        </div>
      </div>
    </section>

    <div class="editor-layout">
      <div class="editor-main">
        <section class="editor-card">
          <h2><i class="fa-solid fa-file-lines"></i> 商品基本资料</h2>
          <div class="form-grid">
            <label class="field wide">
              <span>商品名称 <b>*</b></span>
              <input data-preview-name name="name" value="<?= htmlspecialchars(qii_text($product['name'] ?? '')) ?>" placeholder="请输入商品名称" required>
            </label>
            <label class="field">
              <span>商品状态</span>
              <span class="switch-line"><input type="checkbox" name="status" <?= ($product['status'] ?? 'active') === 'active' ? 'checked' : '' ?>><i></i> 上架</span>
            </label>
            <label class="field">
              <span>商品分类 <b>*</b></span>
              <select data-preview-category name="category" required>
                <?php foreach ($categories as $key => $label): ?>
                  <option value="<?= htmlspecialchars($key) ?>" <?= ($product['category'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="field">
              <span>品牌（可选）</span>
              <input name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>" placeholder="请输入品牌名称">
            </label>
            <label class="upload-field wide">
              <span>商品主图 <b>*</b></span>
              <input data-image-input data-preview-target="#mainPreview" type="file" name="image" accept="image/*">
              <div class="upload-box">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <strong>点击上传商品主图</strong>
                <small>支持 JPG / PNG，建议尺寸 800x800</small>
              </div>
            </label>
            <div class="image-preview-panel">
              <span>主图预览</span>
              <img id="mainPreview" src="<?= htmlspecialchars(asset_url($product['image_url'] ?? '')) ?>" alt="主图预览">
            </div>
          </div>
        </section>

        <section class="editor-card">
          <div class="section-row">
            <div>
              <h2><i class="fa-solid fa-tag"></i> 规格管理 (Variant)</h2>
              <p>添加商品的不同规格，例如颜色、款式、尺寸等</p>
            </div>
            <button class="outline-action" type="button" data-add-variant><i class="fa-solid fa-plus"></i> 添加规格</button>
          </div>

          <div class="variant-table" id="variantList">
            <div class="variant-head">
              <span></span><span>规格图片</span><span>规格名称</span><span>SKU</span><span>价格 (RM)</span><span>库存</span><span>操作</span>
            </div>
            <?php foreach ($variants as $i => $v): ?>
              <div class="variant-row" draggable="true">
                <button type="button" class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></button>
                <label class="variant-image">
                  <input data-image-input data-preview-target="#variantPreview<?= $i ?>" type="file" name="variant_image_<?= $i ?>" accept="image/*">
                  <input type="hidden" name="variant_existing_image[]" value="<?= htmlspecialchars($v['image_url'] ?? '') ?>">
                  <img id="variantPreview<?= $i ?>" src="<?= htmlspecialchars(($v['image_url'] ?? '') !== '' ? asset_url($v['image_url']) : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Crect width='80' height='80' rx='16' fill='%23fff4fa'/%3E%3C/svg%3E") ?>" alt="">
                  <i class="fa-solid fa-upload"></i>
                </label>
                <label class="variant-field"><span>规格名称</span><input data-preview-variant name="variant_name[]" value="<?= htmlspecialchars(qii_text($v['variant_name'] ?? '')) ?>" placeholder="蓝色小挂件"></label>
                <label class="variant-field"><span>SKU</span><input name="variant_sku[]" value="<?= htmlspecialchars($v['sku'] ?? (($product['sku'] ?? '') ?: '')) ?>" placeholder="A001-BLUE"></label>
                <label class="variant-field"><span>价格 (RM)</span><input data-preview-price name="variant_price[]" type="number" step="0.01" value="<?= htmlspecialchars($v['price'] ?? $product['price'] ?? '0.00') ?>"></label>
                <label class="variant-field"><span>库存</span><input name="variant_stock[]" type="number" value="<?= htmlspecialchars($v['stock'] ?? 0) ?>"></label>
                <button type="button" class="delete-variant" data-delete-variant><i class="fa-regular fa-trash-can"></i></button>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="variant-save-row">
            <button type="submit" name="save_variants" value="1" class="save-action"><i class="fa-solid fa-floppy-disk"></i> 保存规格</button>
          </div>
        </section>
      </div>

      <aside class="editor-side">
        <section class="preview-card">
          <h2><i class="fa-regular fa-eye"></i> 商品预览</h2>
          <div class="shop-preview">
            <div class="shop-awning">Qii.shop ❤</div>
            <img id="cardPreviewImg" src="<?= htmlspecialchars(asset_url($product['image_url'] ?? '')) ?>" alt="">
            <div class="shop-preview-body">
              <h3 id="cardPreviewName"><?= htmlspecialchars(qii_text($product['name'] ?: '商品名称将显示在这里')) ?></h3>
              <span id="cardPreviewTag"><?= htmlspecialchars($categories[$product['category']] ?? '多种规格可选') ?></span>
              <strong id="cardPreviewPrice">RM <?= number_format((float)($variants[0]['price'] ?? $product['price'] ?? 0), 2) ?></strong>
              <button type="button"><i class="fa-regular fa-eye"></i> 查看商品详情</button>
            </div>
          </div>
        </section>

        <section class="tip-card">
          <h2><i class="fa-regular fa-lightbulb"></i> 小贴士</h2>
          <ul>
            <li>建议主图尺寸 800x800 像素</li>
            <li>支持 JPG、PNG 格式</li>
            <li>规格图片建议正方形</li>
            <li>SKU 建议唯一且易于识别</li>
            <li>库存设置为 0 将自动标记为缺货</li>
          </ul>
        </section>
      </aside>
    </div>

    <div class="editor-actions">
      <a href="product.php" class="cancel-action">取消</a>
      <button type="submit" class="save-action"><i class="fa-solid fa-lock"></i> 保存商品</button>
    </div>
  </form>
</main>

<template id="variantTemplate">
  <div class="variant-row" draggable="true">
    <button type="button" class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></button>
    <label class="variant-image">
      <input data-image-input type="file" name="__IMAGE_NAME__" accept="image/*">
      <input type="hidden" name="variant_existing_image[]" value="">
      <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Crect width='80' height='80' rx='16' fill='%23fff4fa'/%3E%3C/svg%3E" alt="">
      <i class="fa-solid fa-upload"></i>
    </label>
    <label class="variant-field"><span>规格名称</span><input data-preview-variant name="variant_name[]" placeholder="规格名称"></label>
    <label class="variant-field"><span>SKU</span><input name="variant_sku[]" placeholder="SKU"></label>
    <label class="variant-field"><span>价格 (RM)</span><input data-preview-price name="variant_price[]" type="number" step="0.01" value="0.00"></label>
    <label class="variant-field"><span>库存</span><input name="variant_stock[]" type="number" value="0"></label>
    <button type="button" class="delete-variant" data-delete-variant><i class="fa-regular fa-trash-can"></i></button>
  </div>
</template>

<script src="js/product_admin.js?v=20260604"></script>
</body>
</html>
