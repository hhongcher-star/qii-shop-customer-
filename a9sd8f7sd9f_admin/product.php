<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/categories.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$categoryRows = qii_categories($pdo, false);
$categories = array_map(fn($row) => $row['name'], $categoryRows);

function ensure_product_admin_columns(PDO $pdo): void {
    $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('brand', $columns, true)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN brand VARCHAR(120) NULL AFTER category");
    }

    if (!in_array('status', $columns, true)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active' AFTER brand");
    }
}

function product_img(?string $path): string {
    $path = trim((string)$path);

    if ($path === '') {
        return '../images/logo.png';
    }

    if (preg_match('#^(https?:)?//#', $path)) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

ensure_product_admin_columns($pdo);

$deleteError = '';
$categoryError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    verify_csrf();
    $name = trim($_POST['category_name'] ?? '');
    $key = strtolower(trim($_POST['category_key'] ?? ''));
    $emoji = trim($_POST['category_emoji'] ?? '') ?: '🛍️';
    $key = trim(preg_replace('/[^a-z0-9_-]+/', '-', $key), '-');

    if ($name === '' || $key === '') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    verify_csrf();

    $deleteId = (int)($_POST['product_id'] ?? 0);

    if ($deleteId > 0) {
        $pdo->beginTransaction();

        try {
            $pdo->prepare("
                DELETE v FROM product_variants v
                INNER JOIN product_groups g ON g.id = v.group_id
                WHERE g.product_id = ?
            ")->execute([$deleteId]);

            $pdo->prepare("DELETE FROM product_groups WHERE product_id = ?")->execute([$deleteId]);
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$deleteId]);

            $pdo->commit();

            header('Location: product.php?deleted=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $deleteError = '删除失败：' . $e->getMessage();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$cat = $_GET['cat'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$where = [];
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

if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
    $where[] = "p.status = ?";
    $params[] = $status;
}

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

$sql .= " GROUP BY p.id ORDER BY {$orderSql}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        </div>

        <div class="product-topbar-actions">
            <a href="product_editor.php" class="primary-action">
                <i class="fa-solid fa-plus"></i>
                新增商品
            </a>
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
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status">
            <option value="">全部状态</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>上架中</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>已下架</option>
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

    <?php if ($categoryError): ?>
        <div class="editor-alert"><?= htmlspecialchars($categoryError) ?></div>
    <?php endif; ?>

    <dialog id="categoryManager" class="category-dialog">
        <div class="category-dialog-head">
            <h2>商品分类管理</h2>
            <button type="button" onclick="this.closest('dialog').close()" aria-label="关闭">&times;</button>
        </div>
        <form method="post" class="category-add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_category">
            <input name="category_name" placeholder="分类名称，例如：包包" required>
            <input name="category_key" placeholder="英文代号，例如：bag" pattern="[A-Za-z0-9_-]+" required>
            <input name="category_emoji" placeholder="图标，例如：👜" maxlength="20">
            <button type="submit" class="primary-action"><i class="fa-solid fa-plus"></i> 添加</button>
        </form>
        <div class="category-list">
            <?php foreach ($categoryRows as $key => $row): ?>
                <div>
                    <span><?= htmlspecialchars($row['emoji']) ?> <?= htmlspecialchars($row['name']) ?></span>
                    <code><?= htmlspecialchars($key) ?></code>
                    <form method="post" onsubmit="return confirm('确定删除这个分类吗？');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_key" value="<?= htmlspecialchars($key) ?>">
                        <button type="submit" class="icon-button danger" title="删除"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </dialog>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="editor-alert success">
            商品已删除
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
                        <?= htmlspecialchars($categories[$productCategory] ?? $productCategory) ?>
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
                    <a href="product_editor.php?id=<?= (int)$p['id'] ?>" class="soft-button">
                        <i class="fa-solid fa-pen"></i>
                        编辑
                    </a>

                    <a href="product_editor.php?id=<?= (int)$p['id'] ?>" class="soft-button detail">
                        <i class="fa-regular fa-eye"></i>
                        查看详情
                    </a>

                    <form method="post" onsubmit="return confirm('确定删除这个商品吗？');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                        <button type="submit" class="icon-button danger" title="删除">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </footer>

            </article>
        <?php endforeach; ?>

    </section>

</main>

<style>
.product-topbar { grid-template-columns: 1fr auto; }
.product-topbar-actions { display: flex; align-items: center; gap: 12px; }
.category-action { border: 0; cursor: pointer; }
.category-dialog { width: min(620px, calc(100% - 32px)); max-height: min(82vh, 700px); border: 0; border-radius: 20px; padding: 24px; overflow: hidden; box-shadow: 0 24px 70px rgba(100,40,75,.25); }
.category-dialog::backdrop { background: rgba(45,25,38,.35); backdrop-filter: blur(4px); }
.category-dialog-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.category-dialog-head h2 { margin: 0; color: #29203d; }
.category-dialog-head button { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; flex: 0 0 44px; border: 0; border-radius: 50%; background: #fff5fa; font-size: 28px; color: #796d7d; cursor: pointer; }
.category-add-form { display: grid; grid-template-columns: 1.3fr 1fr .7fr auto; gap: 10px; margin-bottom: 20px; }
.category-add-form input { min-width: 0; height: 48px; padding: 0 14px; border: 1px solid #f2c9da; border-radius: 10px; font-size: 15px; }
.category-add-form .primary-action { min-width: 110px; border: 0; cursor: pointer; }
.category-list { display: grid; gap: 8px; max-height: 430px; overflow-y: auto; overscroll-behavior: contain; padding-right: 4px; }
.category-list > div { display: grid; grid-template-columns: 1fr 120px 42px; align-items: center; gap: 10px; padding: 10px 12px; background: #fff5fa; border-radius: 10px; }
.category-list > div > span { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.category-list code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.category-list form { margin: 0; }
@media (max-width: 700px) {
  .product-topbar { grid-template-columns: 1fr; }
  .product-topbar-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; gap: 10px; }
  .product-topbar-actions .primary-action { width: 100%; min-width: 0; min-height: 50px; padding: 0 10px; justify-content: center; font-size: 14px; }
  .category-dialog {
    position: fixed;
    inset: 12px;
    width: auto;
    max-width: none;
    max-height: none;
    margin: 0;
    padding: 20px 16px 16px;
    border-radius: 20px;
  }
  .category-dialog[open] { display: flex; flex-direction: column; }
  .category-dialog-head { flex: 0 0 auto; margin-bottom: 14px; }
  .category-dialog-head h2 { font-size: 24px; line-height: 1.2; }
  .category-add-form { flex: 0 0 auto; grid-template-columns: 1fr; gap: 10px; margin-bottom: 16px; }
  .category-add-form input { width: 100%; height: 48px; box-sizing: border-box; }
  .category-add-form .primary-action { width: 100%; min-height: 52px; }
  .category-list { flex: 1 1 auto; min-height: 0; max-height: none; gap: 8px; padding-right: 2px; }
  .category-list > div { grid-template-columns: minmax(0, 1fr) 92px 42px; min-height: 58px; padding: 8px 10px; }
  .category-list > div > span { font-size: 16px; }
  .category-list code { font-size: 12px; text-align: left; }
}
@media (max-width: 390px) {
  .category-dialog { inset: 8px; padding: 18px 12px 12px; }
  .category-list > div { grid-template-columns: minmax(0, 1fr) 74px 40px; gap: 6px; }
}
</style>

<script src="js/product_admin.js?v=20260605"></script>
</body>
</html>
