<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/categories.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$categoryRows = qii_categories($pdo, false);
$categories = [];
foreach ($categoryRows as $key => $row) {
    $categories[$key] = $row['name'];
}

function qii_text($text): string {
    return (string)$text;
}

function image_upload_error(string $field): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '图片超过服务器允许的大小',
            UPLOAD_ERR_PARTIAL => '图片上传不完整，请重试',
            default => '图片上传失败，请重试',
        };
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        return '图片不能超过 3MB';
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $type = uploaded_mime_type($file['tmp_name']);
    if (!isset($allowed[$type]) || getimagesize($file['tmp_name']) === false) {
        return '只支持 JPG、PNG、GIF 或 WebP 图片';
    }

    return null;
}

function upload_admin_image(string $field, ?string $existing = null): ?string {
    if (!has_uploaded_file($field)) {
        return $existing;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $type = uploaded_mime_type($_FILES[$field]['tmp_name']);
    $dir = dirname(__DIR__) . '/images/products';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('无法创建图片目录');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$type];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('无法保存上传图片');
    }

    return 'images/products/' . $filename;
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

function uploaded_mime_type(string $tmpName): string {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  if (!$finfo) return '';
  $type = finfo_file($finfo, $tmpName);
  finfo_close($finfo);
  return is_string($type) ? $type : '';
}

function is_non_negative_integer(string $value): bool {
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false;
}

function sku_exists(PDO $pdo, string $sku, int $productId, int $variantId = 0): bool {

    $stmt = $pdo->prepare("
        SELECT 1
        FROM products
        WHERE sku = ?
          AND id <> ?

        UNION ALL

        SELECT 1
        FROM product_variants v
        INNER JOIN product_groups g ON g.id = v.group_id
        WHERE v.sku = ?
          AND NOT (
              g.product_id = ?
              AND v.id = ?
          )

        LIMIT 1
    ");

    $stmt->execute([
        $sku,
        $productId,
        $sku,
        $productId,
        $variantId
    ]);

    return (bool)$stmt->fetchColumn();
}

function json_response(bool $success, string $message, array $extra = []): never {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

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
    if (!$found) {
        http_response_code(404);
        exit('商品不存在');
    }
    $product = array_merge($product, $found);

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
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim((string)($_POST['category'] ?? ''));
    if (!isset($categories[$category])) {
        $category = array_key_first($categories) ?? 'phone';
    }
    $brand = trim($_POST['brand'] ?? '');
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $productType = ($_POST['product_type'] ?? 'single') === 'variant' ? 'variant' : 'single';
    $singleSku = trim((string)($_POST['single_sku'] ?? ''));
    $singlePrice = trim((string)($_POST['single_price'] ?? '0.00'));
    $singleStock = trim((string)($_POST['single_stock'] ?? '0'));

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            if ($isAjax) {
                json_response(false, '商品不存在或已被删除');
            }
            exit('商品不存在或已被删除');
        }
    }

    $currentImage = trim((string)($_POST['current_image'] ?? ''));

    $variantNames = $_POST['variant_name'] ?? [];
    $variantSkus = $_POST['variant_sku'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $variantStocks = $_POST['variant_stock'] ?? [];
    $variantExistingImages = $_POST['variant_existing_image'] ?? [];
    $variantIds = $_POST['variant_id'] ?? [];

    $errors = [];
    if ($name === '') {
        $errors[] = '商品名称不能为空';
    }
    if ($currentImage === '' && !has_uploaded_file('image')) {
        $errors[] = '商品主图必须上传';
    }
    if ($uploadError = image_upload_error('image')) {
        $errors[] = '商品主图：' . $uploadError;
    }

    $cleanVariants = [];
    if ($productType === 'single') {
        if ($singleSku === '') $errors[] = '单一商品必须填写 SKU';
        if ($singlePrice === '' || !is_numeric($singlePrice) || (float)$singlePrice < 0) $errors[] = '单一商品价格不正确';
        if (!is_non_negative_integer($singleStock)) $errors[] = '单一商品库存必须是非负整数';
        if ($singleSku !== '' && sku_exists($pdo, $singleSku, $id)) $errors[] = 'SKU 已被其他商品或规格使用';
    } else {
        $variantCount = max(count($variantNames), count($variantSkus), count($variantPrices), count($variantStocks), count($variantExistingImages), count($variantIds));
        $submittedSkus = [];
        for ($i = 0; $i < $variantCount; $i++) {
            $variantId = (int)($variantIds[$i] ?? 0);
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
            if (!is_non_negative_integer($variantStock)) $errors[] = '第 ' . ($i + 1) . ' 个规格库存必须是非负整数';
            if ($variantImage === '' && !has_uploaded_file($field)) $errors[] = '第 ' . ($i + 1) . ' 个规格必须上传图片';
            if ($uploadError = image_upload_error($field)) $errors[] = '第 ' . ($i + 1) . ' 个规格：' . $uploadError;
            if ($variantSku !== '') {
                $normalizedSku = strtolower($variantSku);
                if (isset($submittedSkus[$normalizedSku])) {
                    $errors[] = '规格 SKU 不能重复：' . $variantSku;
                } elseif (sku_exists($pdo, $variantSku, $id, $variantId)) {
                    $errors[] = 'SKU 已被其他商品或规格使用：' . $variantSku;
                }
                $submittedSkus[$normalizedSku] = true;
            }

            $cleanVariants[] = [
                'id' => $variantId,
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
        $error = implode('；', array_unique($errors));
        if ($isAjax) json_response(false, $error);
    } else {

    $firstPrice = $productType === 'single' ? (float)$singlePrice : $cleanVariants[0]['price'];
    $totalStock = $productType === 'single' ? (int)$singleStock : array_sum(array_column($cleanVariants, 'stock'));
    $baseSku = $productType === 'single' ? $singleSku : $cleanVariants[0]['sku'];
    $hasVariant = $productType === 'variant' ? 1 : 0;

    $pdo->beginTransaction();
    try {
        $imageUrl = upload_admin_image('image', $currentImage);
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

        if ($productType === 'variant') {
            $stmt = $pdo->prepare("SELECT id FROM product_groups WHERE product_id = ? ORDER BY sort_order, id LIMIT 1");
            $stmt->execute([$id]);
            $groupId = (int)$stmt->fetchColumn();
            if ($groupId === 0) {
                $stmt = $pdo->prepare("INSERT INTO product_groups (product_id, group_name, sort_order) VALUES (?, '规格', 1)");
                $stmt->execute([$id]);
                $groupId = (int)$pdo->lastInsertId();
            }

            $keptVariantIds = [];
            foreach ($cleanVariants as $variant) {
                $variantImage = upload_admin_image($variant['field'], $variant['image']);
                if ($variant['id'] > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE product_variants v
                        INNER JOIN product_groups g ON g.id = v.group_id
                        SET v.group_id=?, v.variant_name=?, v.sku=?, v.price=?, v.stock=?, v.image_url=?, v.sort_order=?
                        WHERE v.id=? AND g.product_id=?
                    ");
                    $stmt->execute([$groupId, $variant['name'], $variant['sku'], $variant['price'], $variant['stock'], $variantImage, $variant['index'] + 1, $variant['id'], $id]);
                    if ($stmt->rowCount() === 0) {
                        $check = $pdo->prepare("SELECT 1 FROM product_variants v INNER JOIN product_groups g ON g.id=v.group_id WHERE v.id=? AND g.product_id=?");
                        $check->execute([$variant['id'], $id]);
                        if (!$check->fetchColumn()) throw new RuntimeException('规格不存在或不属于当前商品');
                    }
                    $keptVariantIds[] = $variant['id'];
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO product_variants (group_id, variant_name, sku, price, stock, image_url, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$groupId, $variant['name'], $variant['sku'], $variant['price'], $variant['stock'], $variantImage, $variant['index'] + 1]);
                    $keptVariantIds[] = (int)$pdo->lastInsertId();
                }
            }

            if ($keptVariantIds) {
              $placeholders = implode(',', array_fill(0, count($keptVariantIds), '?'));
              $params = array_merge([$id], $keptVariantIds);

              $pdo->prepare("
                DELETE v FROM product_variants v
                INNER JOIN product_groups g ON g.id=v.group_id
                WHERE g.product_id=? AND v.id NOT IN ($placeholders)
              ")->execute($params);
            } else {
              $pdo->prepare("
                DELETE v FROM product_variants v
                INNER JOIN product_groups g ON g.id=v.group_id
                WHERE g.product_id=?
              ")->execute([$id]);
            }
        } else {
          $pdo->prepare("
            DELETE v FROM product_variants v
            INNER JOIN product_groups g ON g.id = v.group_id
            WHERE g.product_id=?
          ")->execute([$id]);

          $pdo->prepare("
            DELETE FROM product_groups
            WHERE product_id=?
          ")->execute([$id]);
        }

        $pdo->commit();
        if ($isAjax) json_response(true, '商品保存成功', ['id' => $id]);

        header('Location: product_editor.php?id=' . $id . '&saved=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log(sprintf('Product save failed (product_id=%d): %s', $id, $e->getMessage()));
        $error = '保存失败，请稍后重试';
        if ($isAjax) json_response(false, $error);
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
  <link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
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
                  <option
                    value="<?= htmlspecialchars($key) ?>"
                    <?= ($product['category'] ?? '') === $key ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars($label) ?>
                  </option>
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
                  <input type="hidden" name="variant_id[]" value="<?= (int)($v['id'] ?? 0) ?>">
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
        </section>
        <div class="variant-save-row">
  <button type="button" id="saveVariantBtn" class="save-action">
    <i class="fa-solid fa-floppy-disk"></i> 保存规格
  </button>
</div>
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
              <button type="button" id="adjustPreviewImageBtn">
  <i class="fa-regular fa-image"></i> 调整图片
</button>
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
  <div id="cropperModal" style="
position: fixed;
inset: 0;
background: rgba(0,0,0,.7);
display: none;
align-items: center;
justify-content: center;
z-index: 99999;
padding: 24px;
">
  <div style="
    background: white;
    border-radius: 24px;
    padding: 20px;
    max-width: 900px;
    width: 100%;
  ">
    <div style="max-height:70vh;overflow:auto;">
      <img id="cropperImage" style="max-width:100%;">
    </div>

    <div style="
      display:flex;
      justify-content:flex-end;
      gap:12px;
      margin-top:18px;
    ">
      <button type="button" id="cancelCropBtn" class="cancel-action">
        取消
      </button>

      <button type="button" id="applyCropBtn" class="save-action">
        使用图片
      </button>
    </div>
  </div>
</div>
</main>

<template id="variantTemplate">
  <div class="variant-row" draggable="true">
    <button type="button" class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></button>
    <label class="variant-image">
      <input data-image-input type="file" name="__IMAGE_NAME__" accept="image/*">
      <input type="hidden" name="variant_id[]" value="0">
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
<script>
document.getElementById('productEditorForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.currentTarget;
    const btn = form.querySelector('.editor-actions button[type="submit"]');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 保存中...';
    }

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        if (data.success) {
            const targetId = data.id || form.querySelector('input[name="id"]')?.value || '';
            window.location.href = targetId ? `product_editor.php?id=${encodeURIComponent(targetId)}&saved=1` : 'product.php';
            return;
        }

        showToast(data.message || '保存失败，请检查必填资料', true);
    } catch (err) {
        console.error(err);
        showToast('保存失败，请稍后再试', true);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
});

function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.innerText = message;
    toast.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        background: ${isError ? '#ff4d6d' : '#ff4fa3'};
        color: white;
        padding: 14px 20px;
        border-radius: 14px;
        z-index: 9999;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(0,0,0,.12);
        animation: fadeIn .2s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 2500);
}
document.getElementById('saveVariantBtn')?.addEventListener('click', async () => {
    const form = document.getElementById('productEditorForm');
    const btn = document.getElementById('saveVariantBtn');

    if (!form || !btn) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 保存中...';

    try {
        const formData = new FormData(form);

        const response = await fetch('api_product_variants_save.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
    showToast(data.message || '规格保存成功');

    if (data.draft) {
        return;
    }

    setTimeout(() => {
        location.reload();
    }, 700);
} else {
            showToast(data.message || '规格保存失败', true);
        }

    } catch (err) {
        console.error(err);
        showToast('规格保存失败，请稍后再试', true);

    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> 保存规格';
    }
});

function toggleVariantSaveButton() {
    const productType = document.querySelector('[name="product_type"]')?.value;
    const saveVariantBtn = document.getElementById('saveVariantBtn');

    if (!saveVariantBtn) return;

    const row = saveVariantBtn.closest('.variant-save-row');

    if (row) {
        row.style.display = productType === 'variant' ? 'flex' : 'none';
    }
}

document.querySelector('[name="product_type"]')?.addEventListener('change', toggleVariantSaveButton);
toggleVariantSaveButton();
let cropper = null;
let activeInput = null;

const cropperModal =
    document.getElementById('cropperModal');

const cropperImage =
    document.getElementById('cropperImage');

document.querySelectorAll('[data-image-input]')
.forEach(input => {

    input.addEventListener('change', e => {

        const file = e.target.files?.[0];

        if (!file) return;

        activeInput = input;

        const reader = new FileReader();

        reader.onload = ev => {

            cropperImage.src = ev.target.result;

            cropperModal.style.display = 'flex';

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                background: false,
            });
        };

        reader.readAsDataURL(file);
    });
});

document.getElementById('cancelCropBtn')
?.addEventListener('click', () => {

    cropperModal.style.display = 'none';

    if (cropper) {
        cropper.destroy();
        cropper = null;
    }

    if (activeInput) {
        activeInput.value = '';
    }
});

document.getElementById('applyCropBtn')
?.addEventListener('click', () => {

    if (!cropper || !activeInput) return;

    cropper.getCroppedCanvas({
        width: 800,
        height: 800,
        imageSmoothingQuality: 'high'
    }).toBlob(blob => {

        const croppedFile = new File(
            [blob],
            'cropped.jpg',
            {
                type: 'image/jpeg'
            }
        );

        const dt = new DataTransfer();

        dt.items.add(croppedFile);

        activeInput.files = dt.files;

        const previewSelector =
            activeInput.dataset.previewTarget;

        if (previewSelector) {

            const preview =
                document.querySelector(previewSelector);

            if (preview) {
    const croppedUrl = URL.createObjectURL(blob);
    preview.src = croppedUrl;

    if (preview.id === 'mainPreview') {
        const cardImg = document.getElementById('cardPreviewImg');
        if (cardImg) cardImg.src = croppedUrl;
    }
}

        } else {

            const img =
                activeInput.closest('label')
                ?.querySelector('img');

            if (img) {
                img.src =
                    URL.createObjectURL(blob);
            }
        }

        cropperModal.style.display = 'none';

        cropper.destroy();

        cropper = null;

    }, 'image/jpeg', 0.92);
});
document.getElementById('mainPreview')?.addEventListener('click', () => {
    const input = document.querySelector('input[name="image"]');
    const preview = document.getElementById('mainPreview');

    if (!input || !preview || !preview.src) return;

    activeInput = input;
    cropperImage.src = preview.src;
    cropperModal.style.display = 'flex';

    if (cropper) {
        cropper.destroy();
    }

    cropper = new Cropper(cropperImage, {
        aspectRatio: 1,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        responsive: true,
        background: false,
    });
});
document.getElementById('adjustPreviewImageBtn')?.addEventListener('click', () => {
    document.getElementById('mainPreview')?.click();
});
</script>
</body>
</html>
