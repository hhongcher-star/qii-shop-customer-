<?php
session_start();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';

function qii_asset_path($path) {
    $path = trim((string)$path);
    if ($path === '') return 'images/logo.png';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    $path = ltrim($path, '/');
    if (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) return $path;
    return 'uploads/' . $path;
}

function qii_product_payload($p) {
    return htmlspecialchars(json_encode([
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (float)$p['price'],
        'stock' => (int)$p['stock'],
        'has_variant' => isset($p['has_variant']) ? (int)$p['has_variant'] : 0,
        'img' => qii_asset_path($p['image_url'] ?? ''),
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
$products = [];

if ($q !== '') {
    $keywords = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
    $conditions = [];
    $params = [];

    foreach ($keywords as $kw) {
        $conditions[] = "(name LIKE ? OR sku LIKE ?)";
        $params[] = "%{$kw}%";
        $params[] = "%{$kw}%";
    }

    $sql = "SELECT * FROM products";
    $conditions[] = "COALESCE(status, 'active') = 'active'";
    if ($conditions) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><?php require_once __DIR__ . '/../includes/seo.php'; ?>
<?php qii_seo_meta([
  'title' => 'Search qii.shoppp | Find Cute Accessories & Gifts',
  'description' => 'Search qii.shoppp for cute phone charms, accessories, hair clips, stationery, dolls and kawaii gifts.',
  'path' => '/search.php',
  'robots' => 'noindex, follow',
  'keywords' => 'search qii shop, cute accessories search, kawaii gifts Malaysia'
]); ?><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/shop.css">
<style>
:root {
  --pink: #f6bdd9;
  --pink-dark: #e5679c;
}

.search-wrapper {
  max-width: 1000px;
  margin: 100px auto 40px;
  padding: 0 20px;
}

.search-wrapper h2 {
  font-family: "Patrick Hand", cursive;
  color: var(--pink-dark);
  margin-bottom: 18px;
  text-align: center;
  font-size: 1.6rem;
}

.search-results {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.product-card {
  background: #fff;
  border-radius: 18px;
  border: 1px solid #fad2e1;
  padding: 15px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15);
  transition: transform .25s ease;
}
.product-card:hover { transform: translateY(-3px); }

.product-info {
  display: flex;
  gap: 15px;
  align-items: center;
  position: relative;
  min-width: 0;
}
.product-info img {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  border: 1px solid #f6bdd9;
  object-fit: cover;
  flex: 0 0 auto;
}

.product-text h4 {
  margin: 0;
  font-size: 1rem;
  color: var(--pink-dark);
}
.product-text p {
  margin: 2px 0;
  font-size: 0.85rem;
  color: #666;
}
.product-text .price {
  margin-top: 5px;
  font-weight: bold;
  color: var(--pink-dark);
}

.add-btn {
  background: var(--pink);
  border: none;
  padding: 8px 16px;
  color: white;
  font-family: "Patrick Hand", cursive;
  border-radius: 18px;
  cursor: pointer;
  transition: all .3s ease;
  white-space: nowrap;
}
.add-btn:hover { background: var(--pink-dark); transform: scale(1.05); }
.add-btn:disabled {
  background: #e4c0cb;
  color: white;
  cursor: not-allowed;
  opacity: 0.8;
}

.soldout-tag {
  position: absolute;
  top: -4px;
  left: -4px;
  background: var(--pink-dark);
  color: #fff;
  font-size: 0.8rem;
  font-family: "Patrick Hand", cursive;
  padding: 3px 8px;
  border-radius: 6px;
  transform: rotate(-8deg);
  box-shadow: 0 2px 6px rgba(229,103,156,0.3);
}

.qii-toast {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%) translateY(30px);
  background: linear-gradient(180deg,#FFB5D3,#FF95C2);
  color: white;
  padding: 14px 24px;
  border-radius: 18px;
  font-size: 15px;
  box-shadow: 0 6px 14px rgba(255,120,160,0.35);
  opacity: 0;
  transition: all .35s ease;
  z-index: 6000;
  pointer-events: none;
}
.qii-toast.show {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

@media (max-width: 768px) {
  .search-wrapper {
    margin-top: 40px;
  }

  .product-card {
    flex-direction: column;
    align-items: stretch;
  }

  .product-info {
    align-items: center;
  }

  .add-btn {
    width: 100%;
  }
}
</style>
</head>

<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="search-wrapper">
  <h2>æœç´¢ç»“æžœï¼š<?= htmlspecialchars($q) ?></h2>
  <div class="search-results">
    <?php if ($products): ?>
      <?php foreach ($products as $p): ?>
        <div class="product-card">
          <div class="product-info">
            <?php if ($p['stock'] <= 0): ?>
              <div class="soldout-tag">SOLD OUT</div>
            <?php endif; ?>
            <img src="<?= htmlspecialchars(qii_asset_path($p['image_url'] ?? '')) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <div class="product-text">
              <h4><?= htmlspecialchars($p['name']) ?></h4>
              <?php if (!empty($p['sku'])): ?><p>编号：<?= htmlspecialchars($p['sku']) ?></p><?php endif; ?>
              <p>åº“å­˜ï¼š<?= (int)$p['stock'] ?></p>
              <div class="price">RM <?= number_format($p['price'], 2) ?></div>
            </div>
          </div>

          <?php if ($p['stock'] > 0): ?>
            <button class="add-btn" onclick='openVariantModal(<?= qii_product_payload($p) ?>)'>é€‰æ‹©è§„æ ¼</button>
          <?php else: ?>
            <button class="add-btn" disabled>å”®ç½„</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center;color:#aaa;margin:40px 0;">没有找到相关商品。</p>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
<?php include __DIR__ . "/../components/variant_modal.php"; ?>

<div id="qiiToast" class="qii-toast">å·²åŠ å…¥è´­ç‰©è½¦</div>
<script>
function qiiToast(msg) {
  const toast = document.getElementById("qiiToast");
  if (!toast) return;
  toast.textContent = msg;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 2000);
}
</script>
</body>
</html>
