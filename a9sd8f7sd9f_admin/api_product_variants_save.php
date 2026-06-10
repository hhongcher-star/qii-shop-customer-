<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

function json_response(bool $success, string $message, array $extra = []): never {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
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

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $type = uploaded_mime_type($file['tmp_name']);

    if (!in_array($type, $allowed, true) || getimagesize($file['tmp_name']) === false) {
        return '只支持 JPG、PNG、GIF 或 WebP 图片';
    }

    return null;
}

function upload_variant_image(string $field, string $existing = ''): string {
    if (!has_uploaded_file($field)) {
        return $existing;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $type = uploaded_mime_type($_FILES[$field]['tmp_name']);

    if (!isset($allowed[$type])) {
        throw new RuntimeException('Unsupported image type');
    }

    $dir = dirname(__DIR__) . '/images/products';

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create upload directory');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$type];

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Cannot save uploaded image');
    }

    return 'images/products/' . $filename;
}

function is_non_negative_integer(string $value): bool {
    return filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0]
    ]) !== false;
}

function sku_exists(PDO $pdo, string $sku, int $productId, int $variantId): bool {
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
          AND NOT (g.product_id = ? AND v.id = ?)

        LIMIT 1
    ");
    $stmt->execute([$sku, $productId, $sku, $productId, $variantId]);
    return (bool)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

try {
    verify_csrf();

    $productId = (int)($_POST['product_id'] ?? $_POST['id'] ?? 0);

    if ($productId <= 0) {
    json_response(true, '规格已暂存，请再按“保存商品”完成新增', [
        'draft' => true,
        'product_id' => 0
    ]);
}

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);

    if (!$stmt->fetchColumn()) {
        json_response(false, '商品不存在或已被删除');
    }

    $variantIds = $_POST['variant_id'] ?? [];
    $variantNames = $_POST['variant_name'] ?? [];
    $variantSkus = $_POST['variant_sku'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $variantStocks = $_POST['variant_stock'] ?? [];
    $variantExistingImages = $_POST['variant_existing_image'] ?? [];

    $variantCount = max(
        count($variantIds),
        count($variantNames),
        count($variantSkus),
        count($variantPrices),
        count($variantStocks),
        count($variantExistingImages)
    );

    $errors = [];
    $cleanVariants = [];
    $submittedSkus = [];

    for ($i = 0; $i < $variantCount; $i++) {
        $variantId = (int)($variantIds[$i] ?? 0);
        $name = trim((string)($variantNames[$i] ?? ''));
        $sku = trim((string)($variantSkus[$i] ?? ''));
        $price = trim((string)($variantPrices[$i] ?? ''));
        $stock = trim((string)($variantStocks[$i] ?? ''));
        $image = trim((string)($variantExistingImages[$i] ?? ''));
        $field = 'variant_image_' . $i;

        $hasAnyInput = $name !== '' || $sku !== '' || $price !== '' || $stock !== '' || $image !== '' || has_uploaded_file($field);

        if (!$hasAnyInput) {
            continue;
        }

        if ($name === '') {
            $errors[] = '第 ' . ($i + 1) . ' 个规格缺少名称';
        }

        if ($sku === '') {
            $errors[] = '第 ' . ($i + 1) . ' 个规格缺少 SKU';
        }

        if ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $errors[] = '第 ' . ($i + 1) . ' 个规格价格不正确';
        }

        if (!is_non_negative_integer($stock)) {
            $errors[] = '第 ' . ($i + 1) . ' 个规格库存必须是非负整数';
        }

        if ($image === '' && !has_uploaded_file($field)) {
            $errors[] = '第 ' . ($i + 1) . ' 个规格必须上传图片';
        }

        if ($uploadError = image_upload_error($field)) {
            $errors[] = '第 ' . ($i + 1) . ' 个规格：' . $uploadError;
        }

        if ($sku !== '') {
            $skuKey = strtolower($sku);

            if (isset($submittedSkus[$skuKey])) {
                $errors[] = '规格 SKU 不能重复：' . $sku;
            } elseif (sku_exists($pdo, $sku, $productId, $variantId)) {
                $errors[] = 'SKU 已被其他商品或规格使用：' . $sku;
            }

            $submittedSkus[$skuKey] = true;
        }

        $cleanVariants[] = [
            'id' => $variantId,
            'name' => $name,
            'sku' => $sku,
            'price' => (float)$price,
            'stock' => (int)$stock,
            'image' => $image,
            'field' => $field,
            'sort_order' => $i + 1,
        ];
    }

    if (!$cleanVariants) {
        json_response(false, '至少需要一个完整规格');
    }

    if ($errors) {
        json_response(false, implode('；', array_unique($errors)));
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id
        FROM product_groups
        WHERE product_id = ?
        ORDER BY sort_order, id
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $groupId = (int)$stmt->fetchColumn();

    if ($groupId === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO product_groups (product_id, group_name, sort_order)
            VALUES (?, '规格', 1)
        ");
        $stmt->execute([$productId]);
        $groupId = (int)$pdo->lastInsertId();
    }

    $keptVariantIds = [];

    foreach ($cleanVariants as $variant) {
        $imageUrl = upload_variant_image($variant['field'], $variant['image']);

        if ($variant['id'] > 0) {
            $stmt = $pdo->prepare("
                UPDATE product_variants v
                INNER JOIN product_groups g ON g.id = v.group_id
                SET v.group_id = ?,
                    v.variant_name = ?,
                    v.sku = ?,
                    v.price = ?,
                    v.stock = ?,
                    v.image_url = ?,
                    v.sort_order = ?
                WHERE v.id = ?
                  AND g.product_id = ?
            ");
            $stmt->execute([
                $groupId,
                $variant['name'],
                $variant['sku'],
                $variant['price'],
                $variant['stock'],
                $imageUrl,
                $variant['sort_order'],
                $variant['id'],
                $productId
            ]);

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare("
                    SELECT 1
                    FROM product_variants v
                    INNER JOIN product_groups g ON g.id = v.group_id
                    WHERE v.id = ?
                      AND g.product_id = ?
                ");
                $check->execute([$variant['id'], $productId]);

                if (!$check->fetchColumn()) {
                    throw new RuntimeException('Variant does not belong to this product');
                }
            }

            $keptVariantIds[] = $variant['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO product_variants
                    (group_id, variant_name, sku, price, stock, image_url, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $groupId,
                $variant['name'],
                $variant['sku'],
                $variant['price'],
                $variant['stock'],
                $imageUrl,
                $variant['sort_order']
            ]);

            $keptVariantIds[] = (int)$pdo->lastInsertId();
        }
    }

    if ($keptVariantIds) {
        $placeholders = implode(',', array_fill(0, count($keptVariantIds), '?'));
        $params = array_merge([$productId], $keptVariantIds);

        $stmt = $pdo->prepare("
            DELETE v FROM product_variants v
            INNER JOIN product_groups g ON g.id = v.group_id
            WHERE g.product_id = ?
              AND v.id NOT IN ($placeholders)
        ");
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("
            DELETE v FROM product_variants v
            INNER JOIN product_groups g ON g.id = v.group_id
            WHERE g.product_id = ?
        ");
        $stmt->execute([$productId]);
    }

    $firstVariant = $cleanVariants[0];
    $totalStock = array_sum(array_column($cleanVariants, 'stock'));

    $stmt = $pdo->prepare("
        UPDATE products
        SET sku = ?,
            price = ?,
            stock = ?,
            has_variant = 1,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $firstVariant['sku'],
        $firstVariant['price'],
        $totalStock,
        $productId
    ]);

    $pdo->commit();

    json_response(true, '规格保存成功', [
        'product_id' => $productId,
        'variant_ids' => $keptVariantIds
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Variant save failed: ' . $e->getMessage());
    json_response(false, '规格保存失败，请稍后重试');
}