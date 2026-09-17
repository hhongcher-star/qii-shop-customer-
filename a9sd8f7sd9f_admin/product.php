<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/categories.php';
require_once __DIR__ . '/../app/product_images.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$categoryRows = qii_categories($pdo, false);
$activeCategoryRows = qii_categories($pdo, true);
$categories = [];
foreach ($categoryRows as $key => $row) {
    $categories[$key] = $row['name'];
}
$activeCategories = [];
foreach ($activeCategoryRows as $key => $row) {
    $activeCategories[$key] = $row['name'];
}

function product_img(?string $path): string {
    return qii_product_image_url($path);
}

function category_name_html(string $html): string {
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    $html = preg_replace('/<(div|p)[^>]*>/i', '<br>', $html);
    $html = preg_replace('/<div><br><\/div>|<p><br><\/p>/i', '<br>', $html);
    $html = preg_replace('/<\/div>\s*<div>|<\/p>\s*<p>/i', '<br>', $html);
    $html = preg_replace('/<\/?(div|p)[^>]*>/i', '', $html);
    $html = strip_tags($html, '<br>');
    $html = preg_replace('/<br\s*\/?>/i', '<br>', $html);
    $html = preg_replace('/^(<br>)+|(<br>)+$/i', '', $html);

    if (!class_exists('DOMDocument')) {
        return trim(strip_tags($html, '<br>'));
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $renderNode = function (DOMNode $node) use (&$renderNode): string {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->nodeValue, ENT_QUOTES, 'UTF-8');
        }
        if (!$node instanceof DOMElement) return '';
        if (strtolower($node->tagName) === 'br') return '<br>';

        $children = '';
        foreach ($node->childNodes as $child) {
            $children .= $renderNode($child);
        }

        return $children;
    };

    $root = $document->getElementsByTagName('div')->item(0);
    $clean = '';
    if ($root) {
        foreach ($root->childNodes as $child) {
            $clean .= $renderNode($child);
        }
    }
    return trim($clean);
}

function category_name_text(string $html): string {
    return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
}

function category_name_admin_html(string $html): string {
    return category_name_html($html);
}

function category_name_plain(string $html): string {
    return category_name_text($html);
}

function product_ajax_request(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function product_json_response(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$deleteError = '';
$categoryError = '';
$bulkProductError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    verify_csrf();
    $name = category_name_html((string)($_POST['category_name'] ?? ''));
    $key = strtolower(trim($_POST['category_key'] ?? ''));
    $emoji = trim($_POST['category_emoji'] ?? '');
    $key = trim(preg_replace('/[^a-z0-9_-]+/', '-', $key), '-');

    if (category_name_text($name) === '' || $key === '') {
        $categoryError = '分类名称和分类代号不能为空。';
    } elseif (isset($categoryRows[$key])) {
        $categoryError = '这个分类代号已经存在。';
    } else {
        $sortOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_categories")->fetchColumn();
        $pdo->prepare("INSERT INTO product_categories (category_key, name, emoji, sort_order) VALUES (?, ?, ?, ?)")
            ->execute([$key, $name, $emoji, $sortOrder]);
        header('Location: product.php?category_added=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_category') {
    verify_csrf();
    $key = $_POST['category_key'] ?? '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
    $stmt->execute([$key]);
    if ((int)$stmt->fetchColumn() > 0) {
        $categoryError = '这个分类仍有商品，不能删除。';
    } else {
        $pdo->prepare("DELETE FROM product_categories WHERE category_key = ?")->execute([$key]);
        header('Location: product.php?category_deleted=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_category') {
    verify_csrf();
    $oldKey = trim($_POST['old_category_key'] ?? '');
    $newKey = strtolower(trim($_POST['category_key'] ?? ''));
    $name = category_name_html((string)($_POST['category_name'] ?? ''));
    $emoji = trim($_POST['category_emoji'] ?? '');
    $newKey = trim(preg_replace('/[^a-z0-9_-]+/', '-', $newKey), '-');

    if ($oldKey === '' || $newKey === '' || category_name_text($name) === '') {
        $categoryError = '分类名称和分类代号不能为空。';
    } elseif ($newKey !== $oldKey && isset($categoryRows[$newKey])) {
        $categoryError = '新的分类代号已经存在。';
    } else {
        $pdo->beginTransaction();
        try {
            if ($newKey !== $oldKey) {
                $pdo->prepare("UPDATE products SET category = ? WHERE category = ?")->execute([$newKey, $oldKey]);
            }
            $pdo->prepare("UPDATE product_categories SET category_key = ?, name = ?, emoji = ? WHERE category_key = ?")
                ->execute([$newKey, $name, $emoji, $oldKey]);
            $pdo->commit();
            if (product_ajax_request()) {
                product_json_response([
                    'ok' => true,
                    'old_key' => $oldKey,
                    'key' => $newKey,
                    'name_html' => $name,
                    'name_text' => category_name_plain($name),
                    'emoji' => $emoji,
                ]);
            }
            header('Location: product.php?category_updated=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Category update failed: ' . $e->getMessage());
            $categoryError = '分类修改失败，请稍后重试。';
            if (product_ajax_request()) {
                product_json_response(['ok' => false, 'message' => $categoryError]);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'move_category') {
    verify_csrf();
    $key = trim($_POST['category_key'] ?? '');
    $direction = ($_POST['direction'] ?? '') === 'up' ? 'up' : 'down';

    $stmt = $pdo->prepare("SELECT id, sort_order FROM product_categories WHERE category_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $stmt = $pdo->prepare("
            SELECT id, sort_order
            FROM product_categories
            WHERE sort_order {$operator} ?
            ORDER BY sort_order {$order}, id {$order}
            LIMIT 1
        ");
        $stmt->execute([(int)$current['sort_order']]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target) {
            $pdo->beginTransaction();
            try {
                $temporaryOrder = -((int)$current['id']);
                $pdo->prepare("UPDATE product_categories SET sort_order = ? WHERE id = ?")
                    ->execute([$temporaryOrder, (int)$current['id']]);
                $pdo->prepare("UPDATE product_categories SET sort_order = ? WHERE id = ?")
                    ->execute([(int)$current['sort_order'], (int)$target['id']]);
                $pdo->prepare("UPDATE product_categories SET sort_order = ? WHERE id = ?")
                    ->execute([(int)$target['sort_order'], (int)$current['id']]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Category reorder failed: ' . $e->getMessage());
                $categoryError = '分类排序失败，请稍后重试。';
            }
        }
    }

    if ($categoryError === '') {
        header('Location: product.php?category_moved=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    verify_csrf();

    $deleteId = (int)($_POST['product_id'] ?? 0);

    if ($deleteId > 0) {
        try {
            $pdo->prepare("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id = ?")
                ->execute([$deleteId]);

            header('Location: product.php?deleted=1');
            exit;
        } catch (Throwable $e) {
            error_log(sprintf('Product delete failed (product_id=%d): %s', $deleteId, $e->getMessage()));
            $deleteError = '删除失败，请稍后重试。';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_product_status') {
    verify_csrf();

    $bulkMode = ($_POST['bulk_mode'] ?? '') === 'restore' ? 'restore' : 'delete';
    $targetStatus = $bulkMode === 'restore' ? 'active' : 'inactive';
    $replacementCategory = trim((string)($_POST['replacement_category'] ?? ''));
    $postedProductIds = $_POST['product_ids'] ?? [];
    $selectedIds = is_array($postedProductIds)
        ? array_values(array_unique(array_filter(array_map('intval', $postedProductIds))))
        : [];

    if (!$selectedIds) {
        $bulkProductError = '请先选择商品。';
    } else {
        try {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            if ($bulkMode === 'restore') {
                $missingCategoryStmt = $pdo->prepare("
                    SELECT p.id
                    FROM products p
                    LEFT JOIN product_categories c
                        ON c.category_key = p.category
                       AND c.status = 'active'
                    WHERE p.id IN ($placeholders)
                      AND c.id IS NULL
                ");
                $missingCategoryStmt->execute($selectedIds);
                $missingCategoryIds = array_map('intval', $missingCategoryStmt->fetchAll(PDO::FETCH_COLUMN));

                if ($missingCategoryIds) {
                    if (!isset($activeCategories[$replacementCategory])) {
                        $bulkProductError = '此分类已没，请选择新分类后再复原。';
                        throw new RuntimeException('RESTORE_CATEGORY_REQUIRED');
                    }

                    $missingPlaceholders = implode(',', array_fill(0, count($missingCategoryIds), '?'));
                    $replaceStmt = $pdo->prepare("UPDATE products SET category = ?, updated_at = NOW() WHERE id IN ($missingPlaceholders)");
                    $replaceStmt->execute(array_merge([$replacementCategory], $missingCategoryIds));
                }
            }

            $stmt = $pdo->prepare("UPDATE products SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$targetStatus], $selectedIds));

            $redirectFlag = $bulkMode === 'restore' ? 'restored' : 'bulk_deleted';
            header('Location: product.php?' . $redirectFlag . '=' . (int)$stmt->rowCount());
            exit;
        } catch (Throwable $e) {
            if ($e->getMessage() !== 'RESTORE_CATEGORY_REQUIRED') {
                error_log(sprintf('Bulk product status failed (mode=%s): %s', $bulkMode, $e->getMessage()));
                $bulkProductError = '批量处理失败，请稍后重试。';
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$cat = $_GET['cat'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$perPage = 32;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["COALESCE(p.status, 'active') = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($cat !== '' && isset($categories[$cat])) {
    $where[] = "p.category = ?";
    $params[] = $cat;
}

$countSql = "SELECT COUNT(*) FROM products p" . ($where ? " WHERE " . implode(" AND ", $where) : '');
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$filteredProductCount = (int)$countStmt->fetchColumn();
$totalProductCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE COALESCE(status, 'active') = 'active'")->fetchColumn();
$totalPages = max(1, (int)ceil($filteredProductCount / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$orderSql = match ($sort) {
    'price_asc' => 'min_price ASC, p.id DESC',
    'price_desc' => 'min_price DESC, p.id DESC',
    'stock_asc' => 'total_stock ASC, p.id DESC',
    'stock_desc' => 'total_stock DESC, p.id DESC',
    default => 'p.id DESC',
};

$sql = "
    SELECT
        p.*,
        COUNT(v.id) AS variant_count,
        COALESCE(SUM(v.stock), p.stock) AS total_stock,
        COALESCE(MIN(v.price), p.price) AS min_price
    FROM products p
    LEFT JOIN product_groups g ON g.product_id = p.id
    LEFT JOIN product_variants v ON v.group_id = g.id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY p.id ORDER BY {$orderSql} LIMIT {$perPage} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$bulkProductsStmt = $pdo->query("
    SELECT id, name, category, status, image_url
    FROM products
    ORDER BY COALESCE(status, 'active') = 'inactive', id DESC
");
$bulkProducts = $bulkProductsStmt->fetchAll(PDO::FETCH_ASSOC);
$productListQuery = $_GET;
unset($productListQuery['status']);
$productListReturnUrl = 'product.php' . ($productListQuery ? '?' . http_build_query($productListQuery) : '');
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品管理 | Qii.shop Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="css/product_admin.css?v=20260605">
</head>

<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main product-admin-page">

    <header class="product-topbar">
        <div>
            <h1>商品管理</h1>
            <p>管理所有商品，查看库存、规格和上架状态</p>
            <div class="product-count-monitor"><i class="fa-solid fa-chart-simple"></i> 当前上架 <strong><?= number_format($totalProductCount) ?></strong> 个商品</div>
        </div>

        <div class="product-topbar-actions">
            <a href="product_editor.php" class="primary-action">
                <i class="fa-solid fa-plus"></i>
                新增商品
            </a>
            <button type="button" class="primary-action bulk-action" onclick="document.getElementById('bulkProductManager').showModal()">
                <i class="fa-solid fa-layer-group"></i>
                批量下架/复原
            </button>
            <button type="button" class="primary-action category-action" onclick="document.getElementById('categoryManager').showModal()">
                <i class="fa-solid fa-folder-plus"></i>
                分类管理
            </button>
        </div>
    </header>

    <form class="product-filters glass-card" method="get">
        <label class="search-field">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
                type="search" 
                name="search" 
                value="<?= htmlspecialchars($search) ?>" 
                placeholder="搜索商品名称 / SKU"
            >
        </label>

        <select name="cat">
            <option value="">全部分类</option>
            <?php foreach ($categories as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $cat === $key ? 'selected' : '' ?>>
                    <?= htmlspecialchars(str_replace("\n", ' / ', category_name_plain($label))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>最新商品</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>价格低到高</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>价格高到低</option>
            <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>库存少到多</option>
            <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>库存多到少</option>
        </select>

        <button class="filter-button" type="submit" title="筛选">
            <i class="fa-solid fa-sliders"></i>
        </button>
    </form>

    <?php if ($deleteError): ?>
        <div class="editor-alert">
            <?= htmlspecialchars($deleteError) ?>
        </div>
    <?php endif; ?>

    <?php if ($bulkProductError): ?>
        <div class="editor-alert">
            <?= htmlspecialchars($bulkProductError) ?>
        </div>
    <?php endif; ?>

    <?php if ($categoryError): ?>
        <div class="editor-alert"><?= htmlspecialchars($categoryError) ?></div>
    <?php endif; ?>

    <dialog id="categoryManager" class="category-dialog">
        <div class="category-dialog-head">
            <h2>商品分类管理</h2>
            <button type="button" class="category-dialog-close" onclick="this.closest('dialog').close()" aria-label="关闭">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="post" class="category-add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_category">
            <input type="hidden" name="category_name" data-category-name-input required>
            <div class="category-rich-input" contenteditable="true" data-category-name-editor data-placeholder="分类名称，例如：包包"></div>
            <input name="category_key" placeholder="英文代号，例如：bag" pattern="[A-Za-z0-9_-]+" required>
            <input name="category_emoji" placeholder="图标，例如：👜" maxlength="20">
            <button type="submit" class="primary-action"><i class="fa-solid fa-plus"></i> 添加</button>
        </form>
        <div class="category-list">
            <?php foreach ($categoryRows as $key => $row): ?>
                <div class="category-edit-row">
                    <div class="category-order-buttons">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="move_category">
                            <input type="hidden" name="category_key" value="<?= htmlspecialchars($key) ?>">
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" title="上移"><i class="fa-solid fa-chevron-up"></i></button>
                        </form>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="move_category">
                            <input type="hidden" name="category_key" value="<?= htmlspecialchars($key) ?>">
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" title="下移"><i class="fa-solid fa-chevron-down"></i></button>
                        </form>
                    </div>
                    <form method="post" class="category-edit-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="edit_category">
                        <input type="hidden" name="old_category_key" value="<?= htmlspecialchars($key) ?>">
                        <input class="category-emoji-input" name="category_emoji" value="<?= htmlspecialchars($row['emoji']) ?>" aria-label="分类图标">
                        <input type="hidden" name="category_name" value="<?= htmlspecialchars($row['name']) ?>" data-category-name-input required>
                        <div class="category-rich-input" contenteditable="true" data-category-name-editor aria-label="分类名称" data-placeholder="分类名称"><?= category_name_admin_html($row['name']) ?></div>
                        <input name="category_key" value="<?= htmlspecialchars($key) ?>" pattern="[A-Za-z0-9_-]+" aria-label="分类代号" required>
                        <button type="submit" class="category-save-button" title="保存"><i class="fa-solid fa-floppy-disk"></i></button>
                    </form>
                    <form method="post" class="category-delete-form" onsubmit="return confirm('确定删除这个分类吗？');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_key" value="<?= htmlspecialchars($key) ?>">
                        <button type="submit" class="icon-button danger" title="删除"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </dialog>

    <dialog id="bulkProductManager" class="bulk-product-dialog">
        <form method="post" data-bulk-product-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="bulk_product_status">
            <input type="hidden" name="bulk_mode" value="delete" data-bulk-product-mode>
            <input type="hidden" name="replacement_category" value="" data-bulk-replacement-category>

            <div class="category-dialog-head">
                <div>
                    <h2>批量商品处理</h2>
                    <p>下架后商品会从前台隐藏，之后可以在这里复原。</p>
                </div>
                <button type="button" class="category-dialog-close" onclick="this.closest('dialog').close()" aria-label="关闭">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="bulk-product-toolbar">
                <button type="button" class="soft-button" data-bulk-select="active"><i class="fa-solid fa-check-double"></i> 选择上架商品</button>
                <button type="button" class="soft-button" data-bulk-select="inactive"><i class="fa-solid fa-clock-rotate-left"></i> 选择已下架</button>
                <button type="button" class="soft-button" data-bulk-select="all"><i class="fa-solid fa-border-all"></i> 全选</button>
                <button type="button" class="soft-button" data-bulk-select="none"><i class="fa-regular fa-square"></i> 清空</button>
                <span class="bulk-product-selected">已选择 <b data-bulk-selected-count>0</b> 个</span>
            </div>

            <div class="bulk-product-grid" aria-label="全部商品">
                <?php foreach ($bulkProducts as $p): ?>
                    <?php
                        $isBulkActive = ($p['status'] ?? 'active') === 'active';
                        $bulkProductCategory = $p['category'] ?? '';
                        $hasActiveCategory = isset($activeCategories[$bulkProductCategory]);
                    ?>
                    <label class="bulk-product-tile <?= $isBulkActive ? 'is-active' : 'is-inactive' ?>">
                        <input type="checkbox" name="product_ids[]" value="<?= (int)$p['id'] ?>" data-bulk-product-checkbox data-status="<?= $isBulkActive ? 'active' : 'inactive' ?>" data-category-active="<?= $hasActiveCategory ? '1' : '0' ?>">
                        <span class="bulk-product-check"><i class="fa-solid fa-check"></i></span>
                        <img src="<?= htmlspecialchars(product_img($p['image_url'] ?? '')) ?>" alt="<?= htmlspecialchars($p['name'] ?? '') ?>">
                        <strong><?= htmlspecialchars($p['name'] ?? '') ?></strong>
                        <small><?= category_name_admin_html((string)($categories[$bulkProductCategory] ?? $bulkProductCategory)) ?></small>
                        <em><?= $isBulkActive ? '上架中' : '已下架，可复原' ?></em>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="bulk-product-footer">
                <button type="button" class="soft-button" onclick="this.closest('dialog').close()">取消</button>
                <button type="submit" class="outline-action" data-bulk-submit="restore"><i class="fa-solid fa-rotate-left"></i> 复原选中商品</button>
                <button type="submit" class="save-action danger-action" data-bulk-submit="delete"><i class="fa-solid fa-eye-slash"></i> 下架选中商品</button>
            </div>
        </form>
    </dialog>

    <dialog id="restoreCategoryDialog" class="restore-category-dialog">
        <div class="category-dialog-head">
            <div>
                <h2>此分类已没</h2>
                <p>选中的商品原本分类已经不在前台，要不要换分类后再复原？</p>
            </div>
            <button type="button" class="category-dialog-close" data-restore-category-cancel aria-label="关闭">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <label class="restore-category-field">
            <span>换去分类</span>
            <select data-restore-category-select>
                <?php foreach ($activeCategories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars(str_replace("\n", ' / ', category_name_plain((string)$label))) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="restore-category-actions">
            <button type="button" class="soft-button" data-restore-category-cancel>取消</button>
            <button type="button" class="save-action" data-restore-category-confirm><i class="fa-solid fa-rotate-left"></i> 换分类并复原</button>
        </div>
    </dialog>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="editor-alert success">
            商品已下架，可在“批量下架/复原”里复原。
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['bulk_deleted'])): ?>
        <div class="editor-alert success">
            已下架 <?= (int)$_GET['bulk_deleted'] ?> 个商品，可在“批量下架/复原”里复原。
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['restored'])): ?>
        <div class="editor-alert success">
            已复原 <?= (int)$_GET['restored'] ?> 个商品。
        </div>
    <?php endif; ?>

    <section class="product-grid" aria-label="商品列表">

        <?php if (!$products): ?>
            <div class="empty-card glass-card">
                暂时没有符合条件的商品。
            </div>
        <?php endif; ?>

        <?php foreach ($products as $p): ?>
            <?php
                $variantCount = (int)($p['variant_count'] ?? 0);
                $totalStock = (int)($p['total_stock'] ?? 0);
                $minPrice = (float)($p['min_price'] ?? 0);
                $isActive = ($p['status'] ?? 'active') === 'active';
                $productName = $p['name'] ?? '';
                $productCategory = $p['category'] ?? '';
            ?>

            <article class="product-card glass-card">

                <div class="product-image-wrap">
                    <img 
                        src="<?= htmlspecialchars(product_img($p['image_url'] ?? '')) ?>"
                        alt="<?= htmlspecialchars($productName) ?>"
                    >

                    <button class="more-button" type="button" aria-label="更多">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                </div>

                <div class="product-card-body">
                    <h2><?= htmlspecialchars($productName) ?></h2>

                    <span class="category-badge">
                        <?= category_name_admin_html((string)($categories[$productCategory] ?? $productCategory)) ?>
                    </span>

                    <p class="product-price">
                        RM <?= number_format($minPrice, 2) ?> 起
                    </p>

                    <div class="product-meta">
                        <span>
                            <i class="fa-regular fa-tags"></i>
                            规格：<?= $variantCount ?: 1 ?> 个
                        </span>

                        <span>
                            <i class="fa-solid fa-cube"></i>
                            库存：<?= $totalStock ?>
                        </span>
                    </div>

                    <span class="status-badge <?= $isActive ? 'active' : 'inactive' ?>">
                        <?= $isActive ? '上架中' : '已下架' ?>
                    </span>
                </div>

                <footer class="product-actions">
                    <a href="product_editor.php?<?= htmlspecialchars(http_build_query(['id' => (int)$p['id'], 'return' => $productListReturnUrl])) ?>" class="soft-button" data-preserve-product-scroll>
                        <i class="fa-solid fa-pen"></i>
                        编辑
                    </a>

                    <a href="product_editor.php?<?= htmlspecialchars(http_build_query(['id' => (int)$p['id'], 'return' => $productListReturnUrl])) ?>" class="soft-button detail" data-preserve-product-scroll>
                        <i class="fa-regular fa-eye"></i>
                        查看详情
                    </a>

                    <form method="post" onsubmit="return confirm('确定下架这个商品吗？商品会从前台隐藏，之后可以复原。');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                        <button type="submit" class="icon-button danger" title="下架">
                            <i class="fa-solid fa-eye-slash"></i>
                        </button>
                    </form>
                </footer>

            </article>
        <?php endforeach; ?>

    </section>

    <?php if ($totalPages > 1): ?>
        <nav class="admin-pagination" aria-label="商品分页">
            <?php $pageQuery = $_GET; ?>
            <?php if ($page > 1): $pageQuery['page'] = $page - 1; ?><a href="?<?= htmlspecialchars(http_build_query($pageQuery)) ?>">上一页</a><?php endif; ?>
            <span>第 <?= $page ?> / <?= $totalPages ?> 页 · 共 <?= number_format($filteredProductCount) ?> 个</span>
            <?php if ($page < $totalPages): $pageQuery['page'] = $page + 1; ?><a href="?<?= htmlspecialchars(http_build_query($pageQuery)) ?>">下一页</a><?php endif; ?>
        </nav>
    <?php endif; ?>

</main>

<style>
.product-topbar { grid-template-columns: 1fr auto; }
.product-topbar-actions { display: flex; align-items: center; gap: 12px; }
.product-filters { grid-template-columns: minmax(260px, 1.5fr) repeat(2, minmax(160px, 1fr)) auto; }
.product-count-monitor { display:inline-flex; align-items:center; gap:8px; margin-top:10px; padding:8px 13px; border:1px solid #f4c9dc; border-radius:999px; background:rgba(255,255,255,.72); color:#7b5267; font-size:14px; }
.product-count-monitor strong { color:#d93682; font-size:17px; }
.admin-pagination { display:flex; align-items:center; justify-content:center; gap:16px; margin:28px 0 8px; }
.admin-pagination a, .admin-pagination span { padding:11px 16px; border-radius:12px; background:#fff; border:1px solid #f2c9da; color:#795568; text-decoration:none; font-weight:700; }
.category-action { border: 0; cursor: pointer; }
.bulk-action { border: 0; cursor: pointer; background: linear-gradient(135deg, #7c3aed, #ec4899); }
.category-dialog { width: min(760px, calc(100% - 32px)); max-height: min(82vh, 700px); border: 0; border-radius: 20px; padding: 24px; overflow: hidden; box-shadow: 0 24px 70px rgba(100,40,75,.25); }
.category-dialog::backdrop { background: rgba(45,25,38,.35); backdrop-filter: blur(4px); }
.bulk-product-dialog { width: min(1180px, calc(100% - 32px)); max-height: min(88vh, 820px); border: 0; border-radius: 20px; padding: 24px; overflow: hidden; box-shadow: 0 24px 70px rgba(100,40,75,.25); }
.bulk-product-dialog::backdrop { background: rgba(45,25,38,.35); backdrop-filter: blur(4px); }
.restore-category-dialog { width: min(520px, calc(100% - 32px)); border: 0; border-radius: 20px; padding: 24px; box-shadow: 0 24px 70px rgba(100,40,75,.25); }
.restore-category-dialog::backdrop { background: rgba(45,25,38,.35); backdrop-filter: blur(4px); }
.bulk-product-dialog[open] form { display: flex; flex-direction: column; max-height: calc(min(88vh, 820px) - 48px); min-height: 0; }
.bulk-product-dialog .category-dialog-head p { margin: 6px 0 0; color: #8f7182; font-weight: 700; }
.restore-category-dialog .category-dialog-head p { margin: 6px 0 0; color: #8f7182; font-weight: 700; }
.restore-category-field { display: grid; gap: 10px; margin: 18px 0 22px; color: #2d2340; font-weight: 800; }
.restore-category-field select { width: 100%; min-height: 52px; padding: 0 14px; border: 1px solid #f2c9da; border-radius: 14px; background: #fff; color: #2d2340; font: inherit; }
.restore-category-actions { display: flex; justify-content: flex-end; gap: 10px; }
.restore-category-actions button { border: 0; cursor: pointer; }
.bulk-product-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 16px; }
.bulk-product-toolbar .soft-button { min-height: 40px; border-radius: 12px; cursor: pointer; }
.bulk-product-selected { margin-left: auto; color: #76596b; font-weight: 800; }
.bulk-product-selected b { color: #d93682; }
.bulk-product-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; min-height: 0; overflow-y: auto; padding: 2px 4px 12px 0; }
.bulk-product-tile { position: relative; display: grid; gap: 8px; min-width: 0; padding: 10px; border: 1px solid #f2c9da; border-radius: 14px; background: #fff; cursor: pointer; transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
.bulk-product-tile:hover { transform: translateY(-2px); border-color: #ff8ec4; box-shadow: 0 14px 32px rgba(255,79,163,.12); }
.bulk-product-tile input { position: absolute; opacity: 0; pointer-events: none; }
.bulk-product-tile img { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 10px; background: #fff0f7; }
.bulk-product-tile strong { min-height: 38px; overflow: hidden; color: #2d2340; font-size: 13px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.bulk-product-tile small { overflow: hidden; color: #ff4fa3; font-weight: 800; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
.bulk-product-tile em { width: fit-content; padding: 4px 8px; border-radius: 999px; background: #dcfce7; color: #15803d; font-style: normal; font-weight: 800; font-size: 11px; }
.bulk-product-tile.is-inactive { background: #f8fafc; }
.bulk-product-tile.is-inactive img { filter: grayscale(.45); opacity: .72; }
.bulk-product-tile.is-inactive em { background: #e2e8f0; color: #64748b; }
.bulk-product-check { position: absolute; top: 8px; right: 8px; display: grid; place-items: center; width: 28px; height: 28px; border: 1px solid #f2c9da; border-radius: 50%; background: rgba(255,255,255,.92); color: transparent; }
.bulk-product-tile.is-selected,
.bulk-product-tile:has(input:checked) { border-color: #ff4fa3; box-shadow: 0 0 0 3px rgba(255,79,163,.16); }
.bulk-product-tile.is-selected .bulk-product-check,
.bulk-product-tile:has(input:checked) .bulk-product-check { background: #ff4fa3; color: #fff; border-color: #ff4fa3; }
.bulk-product-footer { display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid #f4d5e3; }
.bulk-product-footer button { border: 0; cursor: pointer; }
.danger-action { background: linear-gradient(135deg, #ef4444, #ff2f91); }
.category-dialog-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.category-dialog-head h2 { margin: 0; color: #29203d; }
.category-dialog-head .category-dialog-close { position: static; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; flex: 0 0 44px; margin: 0; padding: 0; border: 0; border-radius: 50%; background: #fff5fa; box-shadow: none; font-size: 22px; color: #796d7d; cursor: pointer; }
.category-add-form { display: grid; grid-template-columns: minmax(190px, 1.4fr) minmax(120px, .8fr) 90px auto; gap: 10px; margin-bottom: 20px; align-items: stretch; }
.category-add-form input,
.category-rich-input { min-width: 0; min-height: 48px; padding: 12px 14px; border: 1px solid #f2c9da; border-radius: 10px; font-size: 15px; box-sizing: border-box; background:#fff; }
.category-rich-input { overflow: auto; line-height: 1.18; white-space: pre-wrap; outline: none; }
.category-rich-input:empty::before { content: attr(data-placeholder); color: #9b929e; }
.category-add-form .primary-action { min-width: 110px; border: 0; cursor: pointer; }
.category-list { display: grid; gap: 8px; max-height: 430px; overflow-y: auto; overscroll-behavior: contain; padding-right: 4px; }
.category-list > div { display: grid; grid-template-columns: 32px minmax(0, 1fr) 42px; align-items: center; gap: 8px; padding: 8px; background: #fff5fa; border-radius: 10px; }
.category-list form { margin: 0; }
.category-order-buttons { display: grid; gap: 4px; }
.category-order-buttons button { width: 30px; height: 25px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border: 1px solid #f3c9db; border-radius: 8px; background: #fff; color: #d94b8a; cursor: pointer; }
.category-order-buttons button:hover { border-color: #ff78b3; background: #fff0f7; }
.category-edit-form { display: grid; grid-template-columns: 54px minmax(150px, 1fr) 150px 42px; align-items: stretch; gap: 8px; min-width: 0; }
.category-edit-form input,
.category-edit-form .category-rich-input { width: 100%; min-width: 0; min-height: 52px; box-sizing: border-box; padding: 10px; border: 1px solid #f2c9da; border-radius: 10px; background: #fff; font: inherit; }
.category-edit-form .category-rich-input { line-height: 1.18; }
.category-edit-form .category-emoji-input { padding: 0; text-align: center; font-size: 18px; }
.category-save-button { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #bde8d2; border-radius: 12px; background: #fff; color: #23a66f; cursor: pointer; }
.category-save-button.saved { background: #d8f8e7; border-color: #74d6a3; }
.category-save-button:disabled { opacity: .65; cursor: wait; }
.category-list .icon-button {
  position: static;
  inset: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  min-width: 40px;
  height: 40px;
  min-height: 40px;
  margin: 0;
  padding: 0;
  border: 1px solid #ffc9dd;
  border-radius: 12px;
  background: #fff;
  color: #ff3c91;
  box-shadow: none;
}
@media (max-width: 700px) {
  html:has(.category-dialog[open]),
  body:has(.category-dialog[open]),
  html:has(.bulk-product-dialog[open]),
  body:has(.bulk-product-dialog[open]),
  html:has(.restore-category-dialog[open]),
  body:has(.restore-category-dialog[open]) {
    overflow: hidden !important;
  }
  .product-topbar { grid-template-columns: 1fr; }
  .product-topbar-actions { display: grid; grid-template-columns: 1fr; width: 100%; gap: 10px; }
  .product-topbar-actions .primary-action { width: 100%; min-width: 0; min-height: 50px; padding: 0 10px; justify-content: center; font-size: 14px; }
  .category-dialog {
    position: fixed;
    top: 10px;
    right: 10px;
    bottom: 10px;
    left: 10px;
    width: auto !important;
    height: auto !important;
    max-width: none !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 20px 16px 16px;
    border-radius: 20px;
    overflow: hidden !important;
  }
  .category-dialog[open] {
    display: flex !important;
    flex-direction: column;
  }
  .bulk-product-dialog {
    position: fixed;
    top: 10px;
    right: 10px;
    bottom: 10px;
    left: 10px;
    width: auto !important;
    height: auto !important;
    max-width: none !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 18px 14px 14px;
    border-radius: 20px;
    overflow: hidden !important;
  }
  .bulk-product-dialog[open] form { max-height: 100%; }
  .bulk-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .bulk-product-toolbar { display: grid; grid-template-columns: 1fr 1fr; }
  .bulk-product-selected { grid-column: 1 / -1; margin-left: 0; }
  .bulk-product-footer { display: grid; grid-template-columns: 1fr; }
  .restore-category-dialog {
    width: auto !important;
    margin: auto 12px !important;
    padding: 18px 14px;
  }
  .restore-category-actions { display: grid; grid-template-columns: 1fr; }
  .category-dialog-head {
    position: static;
    flex: 0 0 auto;
    z-index: 5;
    margin: 0 0 14px;
    padding: 0;
    background: #fff;
  }
  .category-dialog-head h2 { font-size: 24px; line-height: 1.2; }
  .category-add-form { flex: 0 0 auto; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
  .category-add-form .category-rich-input { grid-column: 1 / -1; }
  .category-add-form input,
  .category-add-form .category-rich-input { width: 100%; min-height: 48px; box-sizing: border-box; }
  .category-add-form .primary-action { width: 100%; min-height: 52px; }
  .category-list {
    display: grid;
    flex: 1 1 0;
    min-height: 0;
    max-height: none !important;
    overflow-x: hidden !important;
    overflow-y: scroll !important;
    gap: 8px;
    padding: 0 3px 24px 0;
    touch-action: pan-y;
    overscroll-behavior-y: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-gutter: stable;
  }
  .category-list > div { grid-template-columns: 30px minmax(0, 1fr) 42px; padding: 8px 6px; gap: 6px; }
  .category-edit-form { grid-template-columns: 46px minmax(0, 1fr) 42px; gap: 6px; }
  .category-edit-form .category-rich-input { grid-column: 2 / -1; }
  .category-edit-form input[name="category_key"] { grid-column: 2 / 3; }
  .category-edit-form .category-save-button { grid-column: 3; grid-row: 2; height: 52px; }
  .category-edit-form .category-emoji-input { grid-row: 1 / span 2; min-height: 110px; }
  .category-edit-form input:not(.category-emoji-input),
  .category-edit-form .category-rich-input { min-height: 52px; }
  .category-list .icon-button { width: 38px; min-width: 38px; height: 38px; min-height: 38px; }
}
@media (max-width: 390px) {
  .category-dialog { inset: 8px; padding: 18px 12px 12px; }
  .category-list > div { grid-template-columns: 28px minmax(0, 1fr) 40px; gap: 5px; }
  .category-order-buttons button { width: 28px; }
}
</style>

<script src="js/product_admin.js?v=20260605"></script>
<script>
(() => {
  const scrollKey = 'qiiProductScroll:' + window.location.pathname + window.location.search;
  const savedScroll = sessionStorage.getItem(scrollKey);

  if (savedScroll !== null) {
    sessionStorage.removeItem(scrollKey);
    requestAnimationFrame(() => {
      window.scrollTo(0, Math.max(0, parseInt(savedScroll, 10) || 0));
    });
  }

  document.querySelectorAll('[data-preserve-product-scroll]').forEach((link) => {
    link.addEventListener('click', () => {
      sessionStorage.setItem(scrollKey, String(window.scrollY || window.pageYOffset || 0));
    });
  });

  const bulkForm = document.querySelector('[data-bulk-product-form]');
  if (bulkForm) {
    const selectedCount = bulkForm.querySelector('[data-bulk-selected-count]');
    const modeInput = bulkForm.querySelector('[data-bulk-product-mode]');
    const replacementCategoryInput = bulkForm.querySelector('[data-bulk-replacement-category]');
    const checkboxes = [...bulkForm.querySelectorAll('[data-bulk-product-checkbox]')];
    const restoreCategoryDialog = document.getElementById('restoreCategoryDialog');
    const restoreCategorySelect = restoreCategoryDialog?.querySelector('[data-restore-category-select]');
    const restoreCategoryConfirm = restoreCategoryDialog?.querySelector('[data-restore-category-confirm]');

    const syncBulkCount = () => {
      checkboxes.forEach((box) => {
        box.closest('.bulk-product-tile')?.classList.toggle('is-selected', box.checked);
      });
      if (selectedCount) selectedCount.textContent = String(checkboxes.filter((box) => box.checked).length);
    };

    bulkForm.querySelectorAll('[data-bulk-select]').forEach((button) => {
      button.addEventListener('click', () => {
        const target = button.dataset.bulkSelect;
        checkboxes.forEach((box) => {
          box.checked = target === 'all' || (target !== 'none' && box.dataset.status === target);
        });
        syncBulkCount();
      });
    });

    checkboxes.forEach((box) => box.addEventListener('change', syncBulkCount));

    bulkForm.querySelectorAll('[data-bulk-submit]').forEach((button) => {
      button.addEventListener('click', () => {
        if (modeInput) modeInput.value = button.dataset.bulkSubmit === 'restore' ? 'restore' : 'delete';
        if (replacementCategoryInput && button.dataset.bulkSubmit !== 'restore') replacementCategoryInput.value = '';
      });
    });

    bulkForm.addEventListener('submit', (event) => {
      const checked = checkboxes.filter((box) => box.checked);
      const mode = modeInput?.value === 'restore' ? 'restore' : 'delete';
      if (!checked.length) {
        event.preventDefault();
        alert('请先选择商品。');
        return;
      }

      if (mode === 'restore') {
        const missingCategory = checked.some((box) => box.dataset.categoryActive !== '1');
        if (missingCategory && replacementCategoryInput && !replacementCategoryInput.value) {
          event.preventDefault();
          restoreCategoryDialog?.showModal();
          return;
        }
      }

      if (mode === 'delete') {
        const activeCount = checked.filter((box) => box.dataset.status === 'active').length;
        if (!confirm(`确定下架 ${checked.length} 个商品吗？商品会从前台隐藏，但之后可以复原。${activeCount ? '' : ' 你选到的都是已下架商品。'}`)) {
          event.preventDefault();
        }
        return;
      }

      if (!confirm(`确定复原 ${checked.length} 个商品吗？复原后会重新上架。`)) {
        event.preventDefault();
      }
    });

    restoreCategoryDialog?.querySelectorAll('[data-restore-category-cancel]').forEach((button) => {
      button.addEventListener('click', () => restoreCategoryDialog.close());
    });

    restoreCategoryConfirm?.addEventListener('click', () => {
      if (!restoreCategorySelect?.value) {
        alert('请先选择分类。');
        return;
      }
      if (replacementCategoryInput) replacementCategoryInput.value = restoreCategorySelect.value;
      if (modeInput) modeInput.value = 'restore';
      restoreCategoryDialog?.close();
      bulkForm.requestSubmit(bulkForm.querySelector('[data-bulk-submit="restore"]'));
    });

    syncBulkCount();
  }

  function syncCategoryEditor(form) {
    const editor = form.querySelector('[data-category-name-editor]');
    const input = form.querySelector('[data-category-name-input]');
    if (editor && input) {
      input.value = editor.innerText
        .replace(/\r\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim()
        .replace(/\n/g, '<br>');
    }
  }

  document.querySelectorAll('.category-add-form, .category-edit-form').forEach((form) => {
    const editor = form.querySelector('[data-category-name-editor]');
    if (!editor) return;

    editor.addEventListener('input', () => syncCategoryEditor(form));
    editor.addEventListener('keyup', () => syncCategoryEditor(form));
    editor.addEventListener('mouseup', () => syncCategoryEditor(form));
    editor.addEventListener('blur', () => syncCategoryEditor(form));
    editor.addEventListener('touchend', () => syncCategoryEditor(form));
    form.addEventListener('submit', () => syncCategoryEditor(form));
    syncCategoryEditor(form);
  });

  document.querySelectorAll('.category-edit-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      syncCategoryEditor(form);
      const button = form.querySelector('.category-save-button');
      const oldKeyInput = form.querySelector('input[name="old_category_key"]');
      const keyInput = form.querySelector('input[name="category_key"]');
      const editor = form.querySelector('[data-category-name-editor]');
      const nameInput = form.querySelector('[data-category-name-input]');
      button.disabled = true;

      try {
        const response = await fetch('product.php', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: new FormData(form),
        });
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.message || 'save failed');

        if (oldKeyInput) oldKeyInput.value = data.key;
        if (keyInput) keyInput.value = data.key;
        if (editor) editor.innerHTML = data.name_html || '';
        if (nameInput) nameInput.value = data.name_html || '';
        button.classList.add('saved');
        setTimeout(() => button.classList.remove('saved'), 900);
      } catch (error) {
        alert(error.message || '分类保存失败，请再按一次。');
      } finally {
        button.disabled = false;
      }
    });
  });
})();
</script>
</body>
</html>
